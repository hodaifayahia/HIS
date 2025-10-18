<?php

namespace App\Services;

use App\Models\FactureProforma;
use App\Models\FactureProformaProduct;
use App\Models\Fournisseur;
use App\Models\ServiceDemendPurchcing;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FactureProformaService
{
    /**
     * Create a new facture proforma.
     */
    public function create(array $data): FactureProforma
    {
        return DB::transaction(function () use ($data) {
            $facture = FactureProforma::create([
                'fournisseur_id' => $data['fournisseur_id'],
                'service_demand_purchasing_id' => $data['service_demand_purchasing_id'] ?? null,
                'created_by' => Auth::id(),
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
                'date' => $data['date'] ?? now(),
            ]);

            if (! empty($data['products'])) {
                $this->addProductsToFacture($facture, $data['products']);
            }

            // Handle attachments
            if (! empty($data['attachments'])) {
                foreach ($data['attachments'] as $attachment) {
                    if (isset($attachment['file']) && $attachment['file'] instanceof \Illuminate\Http\UploadedFile) {
                        $path = $attachment['file']->store('facture-proforma-attachments', 'public');

                        $facture->attachments()->create([
                            'file_path' => $path,
                            'file_name' => $attachment['name'] ?? $attachment['file']->getClientOriginalName(),
                            'file_type' => $attachment['file']->getMimeType(),
                            'file_size' => $attachment['file']->getSize(),
                            'description' => $attachment['description'] ?? null,
                        ]);
                    }
                }
            }

            return $facture->load(['fournisseur', 'serviceDemand.service', 'creator', 'products.product', 'attachments']);
        });
    }

    /**
     * Update an existing facture proforma.
     */
    public function update(FactureProforma $facture, array $data): FactureProforma
    {
        if ($facture->status !== 'draft') {
            throw new \Exception('Cannot update facture proforma that is not in draft status');
        }

        return DB::transaction(function () use ($facture, $data) {
            $facture->update([
                'fournisseur_id' => $data['fournisseur_id'],
                'service_demand_purchasing_id' => $data['service_demand_purchasing_id'] ?? null,
            ]);

            // Remove existing products and add new ones
            $facture->products()->delete();

            if (! empty($data['products'])) {
                $this->addProductsToFacture($facture, $data['products']);
            }

            return $facture->load(['fournisseur', 'serviceDemand.service', 'creator', 'products.product']);
        });
    }

    /**
     * Add products to a facture proforma.
     */
    protected function addProductsToFacture(FactureProforma $facture, array $products): void
    {
        foreach ($products as $productData) {
            FactureProformaProduct::create([
                'factureproforma_id' => $facture->id,
                'product_id' => $productData['product_id'],
                'quantity' => $productData['quantity'] ?? 0,
                // Accept either unit_price or price from client payloads
                'price' => $productData['unit_price'] ?? $productData['price'] ?? 0,
                'unit' => $productData['unit'] ?? 'pieces',
            ]);
        }
    }

    /**
     * Create multiple facture proformas from a service demand with supplier assignments.
     */
    public function createFromServiceDemand(int $serviceDemandId, array $productAssignments): array
    {
        return DB::transaction(function () use ($serviceDemandId, $productAssignments) {
            $serviceDemand = ServiceDemendPurchcing::with('items.product')->findOrFail($serviceDemandId);

            // Group products by supplier
            $productsBySupplier = collect($productAssignments)->groupBy('fournisseur_id');

            $factureProformas = [];

            foreach ($productsBySupplier as $fournisseurId => $supplierProducts) {
                $facture = FactureProforma::create([
                    'fournisseur_id' => $fournisseurId,
                    'service_demand_purchasing_id' => $serviceDemandId,
                    'created_by' => Auth::id(),
                    'status' => 'draft',
                ]);

                foreach ($supplierProducts as $productData) {
                    $item = $serviceDemand->items->firstWhere('id', $productData['item_id']);

                    if ($item) {
                        FactureProformaProduct::create([
                            'factureproforma_id' => $facture->id,
                            'product_id' => $item->product_id,
                            'quantity' => $item->quantity,
                            'price' => $productData['price'],
                            'unit' => $item->product->unit ?? $item->product->forme ?? 'unit',
                        ]);
                    }
                }

                $facture->load(['fournisseur', 'serviceDemand.service', 'creator', 'products.product']);
                $factureProformas[] = $facture;
            }

            return $factureProformas;
        });
    }

    /**
     * Send facture proforma to supplier.
     */
    public function send(FactureProforma $facture): void
    {
        if ($facture->status !== 'draft') {
            throw new \Exception('Can only send draft facture proformas');
        }

        if ($facture->products->count() === 0) {
            throw new \Exception('Cannot send facture proforma without products');
        }

        $facture->update(['status' => 'sent']);
    }

    /**
     * Mark facture proforma as paid.
     */
    public function markAsPaid(FactureProforma $facture): void
    {
        if ($facture->status !== 'sent') {
            throw new \Exception('Can only mark sent facture proformas as paid');
        }

        $facture->update(['status' => 'paid']);
    }

    /**
     * Cancel facture proforma.
     */
    public function cancel(FactureProforma $facture): void
    {
        if ($facture->status === 'paid') {
            throw new \Exception('Cannot cancel paid facture proformas');
        }

        $facture->update(['status' => 'cancelled']);
    }

    /**
     * Get facture proforma statistics.
     */
    public function getStatistics(): array
    {
        $totalAmount = FactureProforma::with('products')->get()->sum('total_amount');

        return [
            'total_factures' => FactureProforma::count(),
            'draft_factures' => FactureProforma::draft()->count(),
            'sent_factures' => FactureProforma::sent()->count(),
            'paid_factures' => FactureProforma::paid()->count(),
            'cancelled_factures' => FactureProforma::cancelled()->count(),
            'total_amount' => $totalAmount,
            'this_month_factures' => FactureProforma::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'this_month_amount' => FactureProforma::with('products')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->get()
                ->sum('total_amount'),
        ];
    }

    /**
     * Get available service demands for facture proforma creation.
     */
    public function getAvailableServiceDemands(): \Illuminate\Database\Eloquent\Collection
    {
        return ServiceDemendPurchcing::with(['service:id,name', 'items.product:id,name,code,forme'])
            ->where('status', 'sent')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get active suppliers.
     */
    public function getActiveSuppliers(): \Illuminate\Database\Eloquent\Collection
    {
        return Fournisseur::select('id', 'name', 'company_name', 'email', 'phone')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    /**
     * Delete facture proforma if it's in draft status.
     */
    public function delete(FactureProforma $facture): void
    {
        if ($facture->status !== 'draft') {
            throw new \Exception('Cannot delete facture proforma that is not in draft status');
        }

        $facture->delete();
    }

    /**
     * Get filtered facture proformas with pagination.
     */
    public function getFilteredFactures(array $filters = [], int $perPage = 15)
    {
        $query = FactureProforma::with([
            'fournisseur:id,name,company_name',
            'serviceDemand.service:id,name',
            'creator:id,name',
            'products',
        ]);

        // Apply filters
        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['fournisseur_id'])) {
            $query->where('fournisseur_id', $filters['fournisseur_id']);
        }

        if (! empty($filters['service_demand_id'])) {
            $query->where('service_demand_purchasing_id', $filters['service_demand_id']);
        }

        if (! empty($filters['search'])) {
            $query->where('factureProformaCode', 'like', '%'.$filters['search'].'%');
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Handle file attachments
     */
    public function handleAttachments(array $attachments): array
    {
        $processedAttachments = [];

        foreach ($attachments as $attachment) {
            if ($attachment instanceof UploadedFile) {
                // Handle new file upload
                $filename = time().'_'.Str::random(10).'.'.$attachment->getClientOriginalExtension();
                $path = $attachment->storeAs('facture-proforma-attachments', $filename, 'public');

                $processedAttachments[] = [
                    'id' => Str::uuid()->toString(),
                    'name' => $attachment->getClientOriginalName(),
                    'filename' => $filename,
                    'path' => $path,
                    'url' => Storage::url($path),
                    'type' => $attachment->getClientMimeType(),
                    'size' => $attachment->getSize(),
                    'uploaded_at' => now()->format('Y-m-d H:i:s'),
                    'uploaded_by' => Auth::id(),
                ];
            } elseif (is_array($attachment)) {
                // Handle existing attachment data or new file in update
                if (isset($attachment['file']) && $attachment['file'] instanceof UploadedFile) {
                    // New file in update
                    $file = $attachment['file'];
                    $filename = time().'_'.Str::random(10).'.'.$file->getClientOriginalExtension();
                    $path = $file->storeAs('facture-proforma-attachments', $filename, 'public');

                    $processedAttachments[] = [
                        'id' => $attachment['id'] ?? Str::uuid()->toString(),
                        'name' => $attachment['name'] ?? $file->getClientOriginalName(),
                        'filename' => $filename,
                        'path' => $path,
                        'url' => Storage::url($path),
                        'type' => $attachment['type'] ?? $file->getClientMimeType(),
                        'size' => $attachment['size'] ?? $file->getSize(),
                        'uploaded_at' => $attachment['uploaded_at'] ?? now()->format('Y-m-d H:i:s'),
                        'uploaded_by' => Auth::id(),
                    ];
                } else {
                    // Existing attachment (no file change)
                    $processedAttachments[] = $attachment;
                }
            }
        }

        return $processedAttachments;
    }

    /**
     * Update facture proforma with attachments
     */
    public function updateWithAttachments(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $factureProforma = FactureProforma::findOrFail($id);

            // Handle attachments
            $attachmentsToKeep = [];

            // Process existing attachments to keep
            if (! empty($data['existing_attachments'])) {
                foreach ($data['existing_attachments'] as $existingAttachment) {
                    if (isset($existingAttachment['path'])) {
                        $attachmentsToKeep[] = [
                            'id' => $existingAttachment['id'] ?? null,
                            'path' => $existingAttachment['path'],
                            'name' => $existingAttachment['name'],
                            'original_filename' => $existingAttachment['original_filename'] ?? $existingAttachment['name'],
                            'description' => $existingAttachment['description'] ?? null,
                            'mime_type' => $existingAttachment['mime_type'] ?? null,
                            'size' => $existingAttachment['size'] ?? null,
                        ];
                    }
                }
            }

            // Process new attachments to upload
            if (! empty($data['attachments'])) {
                foreach ($data['attachments'] as $attachmentData) {
                    if (isset($attachmentData['file']) && $attachmentData['file'] instanceof \Illuminate\Http\UploadedFile) {
                        $file = $attachmentData['file'];
                        $path = $file->store('facture-proforma-attachments', 'public');

                        $attachmentsToKeep[] = [
                            'path' => $path,
                            'name' => $attachmentData['name'] ?? $file->getClientOriginalName(),
                            'original_filename' => $file->getClientOriginalName(),
                            'mime_type' => $file->getMimeType(),
                            'size' => $file->getSize(),
                            'description' => $attachmentData['description'] ?? null,
                            'uploaded_at' => now()->toDateTimeString(),
                        ];
                    }
                }
            }

            // Prepare update data
            $updateData = [
                'attachments' => $attachmentsToKeep,
            ];

            // Only update certain fields if provided
            $allowedFields = [
                'date', 'fournisseur_id', 'service_demand_purchasing_id',
                'status', 'notes', 'is_confirmed', 'confirmed_at', 'confirmed_by',
            ];

            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }

            $factureProforma->update($updateData);

            // Update products if provided
            if (! empty($data['products'])) {
                // Delete existing products
                $factureProforma->products()->delete();

                // Create new products
                foreach ($data['products'] as $productData) {
                    FactureProformaProduct::create([
                        'factureproforma_id' => $factureProforma->id,
                        'product_id' => $productData['product_id'],
                        'quantity' => $productData['quantity'],
                        'quantity_sended' => $productData['quantity_sended'] ?? 0,
                        'price' => $productData['unit_price'] ?? $productData['price'] ?? 0,
                        'unit' => $productData['unit'] ?? 'pieces',
                        'confirmation_status' => $productData['confirmation_status'] ?? 'pending',
                        'status' => $productData['status'] ?? 'pending',
                        'notes' => $productData['notes'] ?? null,
                    ]);
                }
            }

            // Load relationships and return
            return $factureProforma->fresh()->load([
                'fournisseur:id,company_name,contact_person,email,phone,address',
                'serviceDemand:id,status,created_at',
                'creator:id,name,email',
                'products.product:id,name,code_interne,category,forme,is_clinical,description',
            ]);
        });
    }

    /**
     * Confirm facture proforma
     */
    public function confirmFactureProforma(int $id)
    {
        return DB::transaction(function () use ($id) {
            $factureProforma = FactureProforma::findOrFail($id);

            // Validate that all products have quantity_sended > 0
            $invalidProducts = $factureProforma->products->filter(function ($product) {
                return ($product->quantity_sended ?? 0) <= 0 ||
                       ($product->quantity_sended ?? 0) > ($product->quantity ?? 0);
            });

            if ($invalidProducts->isNotEmpty()) {
                throw new \Exception('All products must have valid quantity sent before confirmation');
            }

            // Update facture proforma
            $factureProforma->update([
                'is_confirmed' => true,
                'confirmed_at' => now(),
                'confirmed_by' => Auth::id(),
                'status' => 'confirmed',
            ]);

            // Update all products to confirmed status
            $factureProforma->products()->update([
                'confirmation_status' => 'confirmed',
                'confirmed_at' => now(),
                'status' => 'confirmed',
            ]);

            return $factureProforma->load([
                'fournisseur:id,company_name,contact_person,email,phone,address',
                'serviceDemand:id,status,created_at',
                'creator:id,name,email',
                'products.product:id,name,code_interne,category,forme,is_clinical,description',
            ]);
        });
    }

    /**
     * Download attachment from facture proforma
     */
    public function downloadAttachment($id, $attachmentIndex)
    {
        // try {
        $factureProforma = FactureProforma::findOrFail($id);

        if (! isset($factureProforma->attachments[$attachmentIndex])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Attachment not found',
            ], 404);
        }

        $attachment = $factureProforma->attachments[$attachmentIndex];
        $filePath = storage_path('app/public/'.$attachment['path']);

        if (! file_exists($filePath)) {
            return response()->json([
                'status' => 'error',
                'message' => 'File not found',
            ], 404);
        }

        return response()->download($filePath, $attachment['name']);

        // } catch (\Exception $e) {
        //     Log::error('Error downloading attachment: ' . $e->getMessage());
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'Failed to download attachment'
        //     ], 500);
        // }
    }

    /**
     * Get all facture proformas with filters
     */
    public function getAllFactureProformas($filters = [])
    {
        $query = FactureProforma::with([
            'fournisseur:id,email,phone,address',
            'serviceDemand:id,status,created_at',
            'creator:id,name,email',
            'products.product:id,name,code_interne,category,forme,is_clinical,description',
        ]);

        // Apply filters
        if (isset($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['fournisseur_id'])) {
            $query->where('fournisseur_id', $filters['fournisseur_id']);
        }

        if (isset($filters['search'])) {
            $query->where('factureProformaCode', 'like', '%'.$filters['search'].'%');
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate(15);
    }

    /**
     * Create facture proforma with attachments
     */
    public function createWithAttachments(array $data): FactureProforma
    {
        return DB::transaction(function () use ($data) {
            $attachmentsData = [];

            // Process and store files first
            if (! empty($data['attachments']) && is_array($data['attachments'])) {
                foreach ($data['attachments'] as $attachmentData) {
                    if (isset($attachmentData['file']) && $attachmentData['file'] instanceof \Illuminate\Http\UploadedFile) {
                        $file = $attachmentData['file'];
                        $path = $file->store('facture-proforma-attachments', 'public');

                        $attachmentsData[] = [
                            'name' => $attachmentData['name'] ?? $file->getClientOriginalName(),
                            'original_filename' => $file->getClientOriginalName(),
                            'path' => $path,
                            'mime_type' => $file->getMimeType(),
                            'size' => $file->getSize(),
                            'description' => $attachmentData['description'] ?? null,
                            'uploaded_at' => now()->toDateTimeString(),
                        ];
                    }
                }
            }

            // Create facture with attachments stored as JSON
            $facture = FactureProforma::create([
                'fournisseur_id' => $data['fournisseur_id'],
                'service_demand_purchasing_id' => $data['service_demand_purchasing_id'] ?? null,
                'created_by' => Auth::id(),
                'status' => 'draft',
                'date' => $data['date'] ?? now(),
                'notes' => $data['notes'] ?? null,
                'attachments' => $attachmentsData, // Store as JSON
            ]);

            if (! empty($data['products'])) {
                $this->addProductsToFacture($facture, $data['products']);
            }

            return $facture->load(['fournisseur', 'serviceDemand.service', 'creator', 'products.product']);
        });
    }
}
