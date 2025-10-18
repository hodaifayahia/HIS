<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FactureProforma;
use App\Models\FactureProformaProduct;
use App\Models\ServiceDemendPurchcing;
use App\Models\Fournisseur;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class FactureProformaController extends Controller
{
    /**
     * Display a listing of facture proformas.
     */
    public function index(Request $request)
    {
        // try {
            $query = FactureProforma::with([
                'fournisseur:id,company_name',
                'serviceDemand.service:id,name',
                'creator:id,name',
                'products',
                'attachments'
            ]);

            // Filter by status
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Filter by supplier
            if ($request->has('fournisseur_id')) {
                $query->where('fournisseur_id', $request->fournisseur_id);
            }

            // Filter by service demand
            if ($request->has('service_demand_id')) {
                $query->where('service_demand_purchasing_id', $request->service_demand_id);
            }

            // Search by facture code
            if ($request->has('search')) {
                $query->where('factureProformaCode', 'like', '%' . $request->search . '%');
            }

            // Date range filter
            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $factures = $query->orderBy('created_at', 'desc')->paginate(15);

            return response()->json([
                'status' => 'success',
                'data' => $factures
            ]);

        // } catch (\Exception $e) {
        //     Log::error('Error fetching facture proformas: ' . $e->getMessage());
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'Failed to fetch facture proformas'
        //     ], 500);
        // }
    }

    /**
     * Store a newly created facture proforma.
     */
    public function store(Request $request)
    {
        $request->validate([
            'fournisseur_id' => 'required|exists:fournisseurs,id',
            'service_demand_purchasing_id' => 'nullable|exists:service_demand_purchasings,id',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.price' => 'required|numeric',
            'products.*.unit' => 'required|string|max:50',
        ]);

 
            DB::beginTransaction();

            $facture = FactureProforma::create([
                'fournisseur_id' => $request->fournisseur_id,
                'service_demand_purchasing_id' => $request->service_demand_purchasing_id,
                'created_by' => Auth::id(),
                'status' => 'draft'
            ]);

            foreach ($request->products as $productData) {
                FactureProformaProduct::create([
                    'factureproforma_id' => $facture->id,
                    'product_id' => $productData['product_id'],
                    'quantity' => $productData['quantity'],
                    'price' => $productData['price'],
                    'unit' => $productData['unit'],
                ]);
            }

            DB::commit();

            $facture->load(['fournisseur', 'serviceDemand.service', 'creator']);

            return response()->json([
                'status' => 'success',
                'data' => $facture,
                'message' => 'Facture proforma created successfully'
            ], 201);

        
    }

    /**
     * Display the specified facture proforma.
     */
    public function show($id)
    {
        try {
            $facture = FactureProforma::with([
                'fournisseur',
                'serviceDemand.service',
                'serviceDemand.items.product',
                'creator',
                'products.product'
            ])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $facture
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Facture proforma not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching facture proforma: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch facture proforma'
            ], 500);
        }
    }

    /**
     * Update the specified facture proforma.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'fournisseur_id' => 'required|exists:fournisseurs,id',
            'service_demand_purchasing_id' => 'nullable|exists:service_demand_purchasings,id',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.price' => 'required|numeric|min:0',
            'products.*.unit' => 'required|string|max:50',
        ]);

        try {
            $facture = FactureProforma::findOrFail($id);

            // Only allow updates if status is draft
            if ($facture->status !== 'draft') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot update facture proforma that is not in draft status'
                ], 400);
            }

            DB::beginTransaction();

            $facture->update([
                'fournisseur_id' => $request->fournisseur_id,
                'service_demand_purchasing_id' => $request->service_demand_purchasing_id,
            ]);

            // Delete existing products and recreate them
            $facture->products()->delete();

            foreach ($request->products as $productData) {
                FactureProformaProduct::create([
                    'factureproforma_id' => $facture->id,
                    'product_id' => $productData['product_id'],
                    'quantity' => $productData['quantity'],
                    'price' => $productData['price'],
                    'unit' => $productData['unit'],
                ]);
            }

            DB::commit();

            $facture->load(['fournisseur', 'serviceDemand.service', 'creator', 'products.product']);

            return response()->json([
                'status' => 'success',
                'data' => $facture,
                'message' => 'Facture proforma updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating facture proforma: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update facture proforma'
            ], 500);
        }
    }

    /**
     * Remove the specified facture proforma.
     */
    public function destroy($id)
    {
        try {
            $facture = FactureProforma::findOrFail($id);

            // Only allow deletion if status is draft
            if ($facture->status !== 'draft') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete facture proforma that is not in draft status'
                ], 400);
            }

            $facture->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Facture proforma deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting facture proforma: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete facture proforma'
            ], 500);
        }
    }

    /**
     * Send the facture proforma to supplier.
     */
    public function send($id)
    {
        try {
            $facture = FactureProforma::findOrFail($id);

            if ($facture->status !== 'draft') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Can only send draft facture proformas'
                ], 400);
            }

            if ($facture->products->count() === 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot send facture proforma without products'
                ], 400);
            }

            $facture->update(['status' => 'sent']);

            return response()->json([
                'status' => 'success',
                'message' => 'Facture proforma sent successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending facture proforma: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send facture proforma'
            ], 500);
        }
    }

    /**
     * Mark facture proforma as paid.
     */
    public function markAsPaid($id)
    {
        try {
            $facture = FactureProforma::findOrFail($id);

            if ($facture->status !== 'sent') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Can only mark sent facture proformas as paid'
                ], 400);
            }

            $facture->update(['status' => 'paid']);

            return response()->json([
                'status' => 'success',
                'message' => 'Facture proforma marked as paid successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error marking facture proforma as paid: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to mark facture proforma as paid'
            ], 500);
        }
    }

    /**
     * Get all service demands that can be used for facture proforma.
     */
    public function getServiceDemands()
    {
        try {
            $serviceDemands = ServiceDemendPurchcing::with(['service:id,name', 'items.product:id,name,code'])
                ->where('status', 'sent')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $serviceDemands
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching service demands: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch service demands'
            ], 500);
        }
    }

    /**
     * Get all suppliers.
     */
    public function getFournisseurs()
    {
        try {
            $fournisseurs = Fournisseur::select('id', 'company_name', 'email', 'phone')
                ->where('is_active', true)
                ->orderBy('company_name')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $fournisseurs
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching fournisseurs: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch fournisseurs'
            ], 500);
        }
    }

    /**
     * Create facture proforma from service demand with supplier assignments.
     */
    public function createFromServiceDemand(Request $request)
    {
        $request->validate([
            'service_demand_id' => 'required|exists:service_demand_purchasings,id',
            'products' => 'required|array|min:1',
            'products.*.item_id' => 'required|exists:service_demand_purchasing_items,id',
            'products.*.fournisseur_id' => 'required|exists:fournisseurs,id',
            'products.*.price' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $serviceDemand = ServiceDemendPurchcing::with('items.product')->findOrFail($request->service_demand_id);

            // Group products by supplier
            $productsBySupplier = collect($request->products)->groupBy('fournisseur_id');

            $factureProformas = [];

            foreach ($productsBySupplier as $fournisseurId => $supplierProducts) {
                $facture = FactureProforma::create([
                    'fournisseur_id' => $fournisseurId,
                    'service_demand_purchasing_id' => $request->service_demand_id,
                    'created_by' => Auth::id(),
                    'status' => 'draft'
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

            DB::commit();

            return response()->json([
                'status' => 'success',
                'data' => $factureProformas,
                'message' => 'Facture proformas created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating facture proforma from service demand: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create facture proforma from service demand'
            ], 500);
        }
    }

    /**
     * Get facture proforma statistics
     */
    public function getStats()
    {
        try {
            $stats = [
                'total_proformas' => FactureProforma::count(),
                'draft_proformas' => FactureProforma::where('status', 'draft')->count(),
                'sent_proformas' => FactureProforma::where('status', 'sent')->count(),
                'approved_proformas' => FactureProforma::where('status', 'approved')->count(),
                'rejected_proformas' => FactureProforma::where('status', 'rejected')->count(),
                'completed_proformas' => FactureProforma::where('status', 'completed')->count(),
                'total_amount' => FactureProforma::with('products')->get()->sum('total_amount'),
                'this_month_proformas' => FactureProforma::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
            ];

            return response()->json([
                'status' => 'success',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching facture proforma stats: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch statistics'
            ], 500);
        }
    }

    /**
     * Get suppliers for dropdown
     */
    public function getSuppliers()
    {
        try {
            $suppliers = Fournisseur::select('id', 'company_name', 'email', 'phone', 'address')
                ->where('is_active', true)
                ->orderBy('company_name')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $suppliers
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching suppliers: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch suppliers'
            ], 500);
        }
    }

    /**
     * Get products for dropdown
     */
    public function getProducts()
    {
        try {
            $products = Product::select('id', 'name', 'product_code', 'unit')
                ->where('status', 'active')
                ->orderBy('name')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $products
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching products: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch products'
            ], 500);
        }
    }

    /**
     * Create facture proformas from service demands
     */
    public function createFromServiceDemands(Request $request)
    {
        $request->validate([
            'service_demand_ids' => 'required|array',
            'service_demand_ids.*' => 'exists:service_demend_purchcings,id'
        ]);

        try {
            DB::beginTransaction();

            $createdProformas = [];

            foreach ($request->service_demand_ids as $demandId) {
                $serviceDemand = ServiceDemendPurchcing::with(['items.product', 'service'])
                    ->findOrFail($demandId);

                if ($serviceDemand->status !== 'approved') {
                    continue; // Skip non-approved demands
                }

                // Create facture proforma
                $proforma = FactureProforma::create([
                    'factureProformaCode' => $this->generateCode(),
                    'service_demand_purchasing_id' => $serviceDemand->id,
                    'status' => 'draft',
                    'created_by' => Auth::id(),
                    'total_amount' => 0
                ]);

                $totalAmount = 0;

                // Create products from service demand items
                foreach ($serviceDemand->items as $item) {
                    if ($item->product) {
                        $productTotal = ($item->estimated_price ?? 0) * $item->quantity;
                        
                        FactureProformaProduct::create([
                            'facture_proforma_id' => $proforma->id,
                            'product_id' => $item->product_id,
                            'product_name' => $item->product->name,
                            'product_code' => $item->product->product_code,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->estimated_price ?? 0,
                            'total_price' => $productTotal
                        ]);

                        $totalAmount += $productTotal;
                    }
                }

                // Update total amount
                $proforma->update(['total_amount' => $totalAmount]);
                $createdProformas[] = $proforma->load(['products', 'serviceDemand']);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Facture proformas created successfully',
                'data' => $createdProformas
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating facture proformas from service demands: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create facture proformas'
            ], 500);
        }
    }

    /**
     * Download facture proforma as PDF
     */
    public function download($id)
    {
        try {
            $proforma = FactureProforma::with(['fournisseur', 'serviceDemand', 'products', 'creator'])
                ->findOrFail($id);

            // Generate PDF content
            $html = $this->generatePdfContent($proforma);
            
            // Use a PDF library to generate the PDF
            // For now, we'll return the HTML as a downloadable file
            // In production, you should use a proper PDF library like dompdf, tcpdf, etc.
            
            $filename = 'facture-proforma-' . ($proforma->factureProformaCode ?: 'FP-' . $proforma->id) . '.html';
            
            return response($html)
                ->header('Content-Type', 'text/html')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
                
        } catch (\Exception $e) {
            Log::error('Error downloading facture proforma: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to download facture proforma'
            ], 500);
        }
    }

    /**
     * Generate PDF content for facture proforma
     */
    private function generatePdfContent(FactureProforma $proforma)
    {
        $supplierName = $proforma->fournisseur ? $proforma->fournisseur->company_name : 'Not assigned';
        
        $html = "
        <h1>Facture Proforma: {$proforma->factureProformaCode}</h1>
        <p>Supplier: {$supplierName}</p>
        <p>Status: {$proforma->status}</p>
        <p>Total Amount: {$proforma->total_amount}</p>
        <p>Created: {$proforma->created_at->format('Y-m-d H:i:s')}</p>
        <hr>
        <h2>Products:</h2>
        <table border='1'>
            <tr>
                <th>Product</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Total</th>
            </tr>";

        foreach ($proforma->products as $product) {
            $productName = $product->product_name ?? ($product->product->name ?? 'Unknown Product');
            $unitPrice = $product->price ?? 0;
            $totalPrice = ($product->quantity ?? 0) * $unitPrice;
            
            $html .= "<tr>
                <td>{$productName}</td>
                <td>{$product->quantity}</td>
                <td>" . number_format($unitPrice, 2) . " DZD</td>
                <td>" . number_format($totalPrice, 2) . " DZD</td>
            </tr>";
        }

        $html .= "</table>";
        
        return $html;
    }

    /**
     * Get products for a specific facture proforma
     */
    public function getProformaProducts($id)
    {
        try {
            $proforma = FactureProforma::with([
                'products.product:id,name,product_code,forme,unit',
                'fournisseur:id,company_name'
            ])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'proforma' => [
                        'id' => $proforma->id,
                        'factureProformaCode' => $proforma->factureProformaCode,
                        'status' => $proforma->status,
                        'fournisseur' => $proforma->fournisseur,
                    ],
                    'products' => $proforma->products->map(function ($product) {
                        return [
                            'id' => $product->id,
                            'product_id' => $product->product_id,
                            'product_name' => $product->product->name ?? 'Unknown Product',
                            'product_code' => $product->product->product_code ?? '',
                            'quantity' => $product->quantity,
                            'price' => $product->price,
                            'unit' => $product->unit,
                            'total_price' => $product->quantity * $product->price,
                        ];
                    })
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching facture proforma products: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch products'
            ], 500);
        }
    }

    /**
     * Update product quantity in facture proforma
     */
    public function updateProductQuantity(Request $request, $id, $productId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
            'price' => 'nullable|numeric|min:0'
        ]);

        try {
            $proforma = FactureProforma::findOrFail($id);

            // Only allow updates if status is draft
            if ($proforma->status !== 'draft') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot update products in non-draft facture proforma'
                ], 400);
            }

            $product = FactureProformaProduct::where('factureproforma_id', $id)
                ->where('id', $productId)
                ->firstOrFail();

            $updateData = ['quantity' => $request->quantity];
            if ($request->has('price')) {
                $updateData['price'] = $request->price;
            }

            $product->update($updateData);

            return response()->json([
                'status' => 'success',
                'message' => 'Product quantity updated successfully',
                'data' => [
                    'quantity' => $product->quantity,
                    'price' => $product->price,
                    'total_price' => $product->quantity * $product->price
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating product quantity: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update product quantity'
            ], 500);
        }
    }

    /**
     * Upload attachment for facture proforma
     */
    public function uploadAttachment(Request $request, $id)
    {
        $request->validate([
            'attachment' => 'required|file|max:10240|mimes:pdf,doc,docx,jpg,jpeg,png',
            'filename' => 'required|string|max:255'
        ]);

        try {
            $proforma = FactureProforma::findOrFail($id);

            $file = $request->file('attachment');
            $filename = $request->filename;
            $path = $file->store('facture-proforma-attachments', 'public');

            $attachment = FactureProformaAttachment::create([
                'facture_proforma_id' => $proforma->id,
                'filename' => $filename,
                'original_filename' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Attachment uploaded successfully',
                'data' => $attachment
            ]);

        } catch (\Exception $e) {
            Log::error('Error uploading attachment: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to upload attachment'
            ], 500);
        }
    }

    /**
     * Download attachment
     */
    public function downloadAttachment($id, $attachmentId)
    {
        try {
            $attachment = FactureProformaAttachment::where('facture_proforma_id', $id)
                ->where('id', $attachmentId)
                ->firstOrFail();

            return response()->download(storage_path('app/public/' . $attachment->path), $attachment->filename);

        } catch (\Exception $e) {
            Log::error('Error downloading attachment: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to download attachment'
            ], 500);
        }
    }

    /**
     * Delete attachment
     */
    public function deleteAttachment($id, $attachmentId)
    {
        try {
            $attachment = FactureProformaAttachment::where('facture_proforma_id', $id)
                ->where('id', $attachmentId)
                ->firstOrFail();

            // Delete file from storage
            if (file_exists(storage_path('app/public/' . $attachment->path))) {
                unlink(storage_path('app/public/' . $attachment->path));
            }

            $attachment->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Attachment deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting attachment: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete attachment'
            ], 500);
        }
    }
}