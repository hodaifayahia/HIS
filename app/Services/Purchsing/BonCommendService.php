<?php

namespace App\Services\Purchsing;

use App\Models\BonCommend;
use App\Models\BonCommendItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BonCommendService
{
    /**
     * Create bon commend with attachments
     */
    public function createWithAttachments(array $data): BonCommend
    {
        return DB::transaction(function () use ($data) {
            $attachmentsData = [];

            // Process and store files
            if (! empty($data['attachments']) && is_array($data['attachments'])) {
                foreach ($data['attachments'] as $attachmentData) {
                    if (isset($attachmentData['file']) && $attachmentData['file'] instanceof \Illuminate\Http\UploadedFile) {
                        $file = $attachmentData['file'];
                        $path = $file->store('bon-commend-attachments', 'public');

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

            // Create bon commend
            $bonCommend = BonCommend::create([
                'fournisseur_id' => $data['fournisseur_id'],
                'service_demand_purchasing_id' => $data['service_demand_purchasing_id'] ?? null,
                'order_date' => $data['order_date'] ?? now(),
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'department' => $data['department'] ?? null,
                'priority' => $data['priority'] ?? 'normal',
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
                'status' => $data['status'] ?? 'draft', // Allow passing status, default to draft
                'approval_status' => $data['approval_status'] ?? null, // Allow passing approval_status
                'attachments' => $attachmentsData,
            ]);

            // Create items
            if (! empty($data['items'])) {
                $this->addItemsToBonCommend($bonCommend, $data['items']);
            }

            return $bonCommend->load(['fournisseur', 'serviceDemand.service', 'creator', 'items.product']);
        });
    }

    /**
     * Update bon commend with attachments
     */
    public function updateWithAttachments(int $id, array $data): BonCommend
    {
        return DB::transaction(function () use ($id, $data) {
            $bonCommend = BonCommend::findOrFail($id);

            // Handle attachments
            $attachmentsToKeep = [];

            // Process existing attachments to keep
            if (! empty($data['existing_attachments'])) {
                foreach ($data['existing_attachments'] as $existingAttachment) {
                    if (isset($existingAttachment['path'])) {
                        $attachmentsToKeep[] = [
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
                        $path = $file->store('bon-commend-attachments', 'public');

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
                'order_date', 'expected_delivery_date', 'fournisseur_id',
                'service_demand_purchasing_id', 'department', 'priority',
                'status', 'notes', 'is_confirmed', 'confirmed_at', 'confirmed_by',
            ];

            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }

            $bonCommend->update($updateData);

            // Update items if provided
            if (! empty($data['items'])) {
                // Delete existing items
                $bonCommend->items()->delete();

                // Create new items (include price if provided)
                foreach ($data['items'] as $itemData) {
                    // Accept either 'price' or 'unit_price' from payload for compatibility
                    $price = null;
                    if (array_key_exists('price', $itemData)) {
                        $price = $itemData['price'];
                    } elseif (array_key_exists('unit_price', $itemData)) {
                        $price = $itemData['unit_price'];
                    }

                    BonCommendItem::create([
                        'bon_commend_id' => $bonCommend->id,
                        'product_id' => $itemData['product_id'],
                        'quantity' => $itemData['quantity'],
                        'quantity_desired' => $itemData['quantity_desired'] ?? $itemData['quantity'],
                        'quantity_sended' => $itemData['quantity_sended'] ?? 0,
                        'unit' => $itemData['unit'] ?? 'pieces',
                        'notes' => $itemData['notes'] ?? null,
                        'price' => $price,
                        'status' => $itemData['status'] ?? 'pending',
                    ]);
                }
            }

            return $bonCommend->fresh()->load([
                'fournisseur:id,company_name,contact_person,email,phone,address',
                'serviceDemand:id,status,created_at',
                'creator:id,name,email',
                'items.product:id,name,code_interne,category,forme,description',
            ]);
        });
    }

    /**
     * Add items to bon commend
     */
    protected function addItemsToBonCommend(BonCommend $bonCommend, array $items): void
    {
        foreach ($items as $itemData) {
            // Accept price from creation payload as 'price' or 'unit_price'
            $price = $itemData['price'] ?? $itemData['unit_price'] ?? null;

            BonCommendItem::create([
                'bon_commend_id' => $bonCommend->id,
                'product_id' => $itemData['product_id'],
                'quantity' => $itemData['quantity'],
                'quantity_desired' => $itemData['quantity_desired'] ?? $itemData['quantity'],
                'quantity_sended' => $itemData['quantity_sended'] ?? 0,
                'unit' => $itemData['unit'] ?? 'pieces',
                'notes' => $itemData['notes'] ?? null,
                'price' => $price,
                'status' => 'pending',
            ]);
        }
    }

    /**
     * Get all bon commends with filters
     */
    public function getAllBonCommends(array $filters = [])
    {
        $query = BonCommend::with([
            'fournisseur:id,company_name',
            'serviceDemand.service:id,name',
            'creator:id,name',
            'items',
        ]);

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['fournisseur_id'])) {
            $query->where('fournisseur_id', $filters['fournisseur_id']);
        }

        if (! empty($filters['search'])) {
            $query->where('bonCommendCode', 'like', '%'.$filters['search'].'%');
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate(15);
    }
}
