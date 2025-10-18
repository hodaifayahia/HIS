<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BonCommend;
use App\Models\BonCommendItem;
use App\Models\FactureProforma;
use App\Models\ServiceDemendPurchcing;
use App\Models\Fournisseur;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class BonCommendController extends Controller
{
    /**
     * Display a listing of bon commends.
     */
    public function index(Request $request)
    {
        try {
            $query = BonCommend::with([
                'fournisseur:id,company_name',
                'serviceDemand.service:id,name',
                'creator:id,name',
                'items',
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

            // Search by bon commend code
            if ($request->has('search')) {
                $query->where('bonCommendCode', 'like', '%' . $request->search . '%');
            }

            // Date range filter
            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $bonCommends = $query->orderBy('created_at', 'desc')->paginate(15);

            return response()->json([
                'status' => 'success',
                'data' => $bonCommends
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching bon commends: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch bon commends'
            ], 500);
        }
    }

    /**
     * Store a newly created bon commend.
     */
    public function store(Request $request)
    {
        $request->validate([
            'fournisseur_id' => 'required|exists:fournisseurs,id',
            'service_demand_purchasing_id' => 'nullable|exists:service_demand_purchasings,id',
            'items' => 'required|array|min:1',
            'items.*.factureproforma_id' => 'required|exists:factureproformas,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.quantity_desired' => 'nullable|integer|min:0',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.unit' => 'required|string|max:50',
            'items.*.status' => 'nullable|string|in:pending,approved,rejected',
        ]);

      
            DB::beginTransaction();

            $bonCommend = BonCommend::create([
                'fournisseur_id' => $request->fournisseur_id,
                'service_demand_purchasing_id' => $request->service_demand_purchasing_id,
                'created_by' => Auth::id(),
                'status' => 'draft'
            ]);

            foreach ($request->items as $itemData) {
                BonCommendItem::create([
                    'bon_commend_id' => $bonCommend->id,
                    'factureproforma_id' => $itemData['factureproforma_id'],
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'quantity_desired' => $itemData['quantity_desired'] ?? null,
                    'price' => $itemData['price'],
                    'unit' => $itemData['unit'],
                    'status' => $itemData['status'] ?? 'pending',
                    'quntity_by_box' => $itemData['quantity_by_box'] ?? null,
                ]);
            }

            DB::commit();

            $bonCommend->load(['fournisseur', 'serviceDemand.service', 'creator', 'items.product', 'items.factureProforma']);

            return response()->json([
                'status' => 'success',
                'data' => $bonCommend,
                'message' => 'Bon commend created successfully'
            ], 201);

      
    }

    /**
     * Display the specified bon commend.
     */
    public function show($id)
    {
        try {
            $bonCommend = BonCommend::with([
                'fournisseur',
                'serviceDemand.service',
                'serviceDemand.items.product',
                'creator',
                'items.product',
                'items.factureProforma'
            ])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $bonCommend
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bon commend not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching bon commend: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch bon commend'
            ], 500);
        }
    }

    /**
     * Update the specified bon commend.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'fournisseur_id' => 'required|exists:fournisseurs,id',
            'service_demand_purchasing_id' => 'nullable|exists:service_demand_purchasings,id',
            'items' => 'required|array|min:1',
            'items.*.factureproforma_id' => 'required|exists:factureproformas,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.quantity_desired' => 'nullable|integer|min:0',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.unit' => 'required|string|max:50',
            'items.*.status' => 'nullable|string|in:pending,approved,rejected',
        ]);

        try {
            $bonCommend = BonCommend::findOrFail($id);

            // Only allow updates if status is draft or sent
            if (!in_array($bonCommend->status, ['draft', 'sent'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot update bon commend with status: ' . $bonCommend->status
                ], 422);
            }

            DB::beginTransaction();

            $bonCommend->update([
                'fournisseur_id' => $request->fournisseur_id,
                'service_demand_purchasing_id' => $request->service_demand_purchasing_id,
            ]);

            // Delete existing items and recreate
            $bonCommend->items()->delete();

            foreach ($request->items as $itemData) {
                BonCommendItem::create([
                    'bon_commend_id' => $bonCommend->id,
                    'factureproforma_id' => $itemData['factureproforma_id'],
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'quantity_desired' => $itemData['quantity_desired'] ?? null,
                    'price' => $itemData['price'],
                    'unit' => $itemData['unit'],
                    'status' => $itemData['status'] ?? 'pending',
                    'quntity_by_box' => $itemData['quantity_by_box'] ?? null,
                ]);
            }

            DB::commit();

            $bonCommend->load(['fournisseur', 'serviceDemand.service', 'creator', 'items.product', 'items.factureProforma']);

            return response()->json([
                'status' => 'success',
                'data' => $bonCommend,
                'message' => 'Bon commend updated successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bon commend not found'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating bon commend: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update bon commend'
            ], 500);
        }
    }

    /**
     * Remove the specified bon commend.
     */
    public function destroy($id)
    {
        try {
            $bonCommend = BonCommend::findOrFail($id);

            // Only allow deletion if status is draft
            if ($bonCommend->status !== 'draft') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete bon commend with status: ' . $bonCommend->status
                ], 422);
            }

            DB::beginTransaction();

            $bonCommend->items()->delete();
            $bonCommend->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Bon commend deleted successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bon commend not found'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting bon commend: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete bon commend'
            ], 500);
        }
    }

    /**
     * Create bon commend from facture proforma.
     */
    public function createFromFactureProforma(Request $request)
    {
        $request->validate([
            'factureproforma_id' => 'required|exists:factureproformas,id',
            'selected_products' => 'required|array|min:1',
            'selected_products.*.product_id' => 'required|exists:products,id',
            'selected_products.*.quantity' => 'required|integer|min:1',
            'selected_products.*.quantity_desired' => 'nullable|integer|min:0',
            'selected_products.*.price' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $factureProforma = FactureProforma::with('fournisseur')->findOrFail($request->factureproforma_id);

            $bonCommend = BonCommend::create([
                'fournisseur_id' => $factureProforma->fournisseur_id,
                'service_demand_purchasing_id' => $factureProforma->service_demand_purchasing_id,
                'created_by' => Auth::id(),
                'status' => 'draft'
            ]);

            foreach ($request->selected_products as $productData) {
                // Find the corresponding facture proforma product for unit info
                $fpProduct = $factureProforma->products()
                    ->where('product_id', $productData['product_id'])
                    ->first();

                BonCommendItem::create([
                    'bon_commend_id' => $bonCommend->id,
                    'factureproforma_id' => $factureProforma->id,
                    'product_id' => $productData['product_id'],
                    'quantity' => $productData['quantity'],
                    'quantity_desired' => $productData['quantity_desired'] ?? null,
                    'price' => $productData['price'],
                    'unit' => $fpProduct?->unit ?? 'Unit',
                    'status' => 'pending',
                ]);
            }

            DB::commit();

            $bonCommend->load(['fournisseur', 'serviceDemand.service', 'creator', 'items.product', 'items.factureProforma']);

            return response()->json([
                'status' => 'success',
                'data' => $bonCommend,
                'message' => 'Bon commend created from facture proforma successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating bon commend from facture proforma: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create bon commend from facture proforma'
            ], 500);
        }
    }

    /**
     * Update bon commend status.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:draft,sent,paid,cancelled'
        ]);

        try {
            $bonCommend = BonCommend::findOrFail($id);

            $bonCommend->update([
                'status' => $request->status
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $bonCommend,
                'message' => 'Bon commend status updated successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bon commend not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error updating bon commend status: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update bon commend status'
            ], 500);
        }
    }

    /**
     * Get all facture proformas for dropdown.
     */
    public function getFactureProformas()
    {
        try {
            $factureProformas = FactureProforma::with('fournisseur:id,company_name')
                ->select('id', 'factureProformaCode', 'fournisseur_id', 'status')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $factureProformas
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching facture proformas: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch facture proformas'
            ], 500);
        }
    }

    /**
     * Get all suppliers for dropdown.
     */
    public function getSuppliers()
    {
        try {
            $suppliers = Fournisseur::select('id', 'company_name', 'contact_person')
                ->where('is_active', true)
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
     * Get all products for dropdown.
     */
    public function getProducts()
    {
        try {
            $products = Product::select('id', 'name', 'code', 'product_code', 'unit_price')
                ->where('status', 'active')
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
     * Get statistics for dashboard.
     */
    public function getStats()
    {
        try {
            $stats = [
                'total' => BonCommend::count(),
                'draft' => BonCommend::where('status', 'draft')->count(),
                'sent' => BonCommend::where('status', 'sent')->count(),
                'paid' => BonCommend::where('status', 'paid')->count(),
                'cancelled' => BonCommend::where('status', 'cancelled')->count(),
                'this_month' => BonCommend::whereMonth('created_at', now()->month)->count(),
                'total_amount' => BonCommend::with('items')->get()->sum('total_amount')
            ];

            return response()->json([
                'status' => 'success',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching bon commend stats: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch statistics'
            ], 500);
        }
    }

    /**
     * Download PDF for bon commend.
     */
    public function download($id)
    {
        try {
            $bonCommend = BonCommend::with([
                'fournisseur',
                'serviceDemand.service',
                'creator',
                'items.product',
                'items.factureProforma'
            ])->findOrFail($id);

            // Generate PDF content
            $html = $this->generatePdfContent($bonCommend);
            
            $filename = 'bon-commend-' . ($bonCommend->bonCommendCode ?: 'BC-' . $bonCommend->id) . '.html';
            
            return response($html)
                ->header('Content-Type', 'text/html')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
                
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bon commend not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error downloading bon commend: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to download bon commend'
            ], 500);
        }
    }

    /**
     * Generate PDF content for bon commend.
     */
    private function generatePdfContent(BonCommend $bonCommend)
    {
        $supplierName = $bonCommend->fournisseur ? $bonCommend->fournisseur->company_name : 'Not assigned';
        $serviceName = $bonCommend->serviceDemand?->service?->name ?? 'N/A';
        $createdDate = $bonCommend->created_at->format('d/m/Y');
        
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Purchase Order - {$bonCommend->bonCommendCode}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .company-info { float: left; width: 50%; }
                .supplier-info { float: right; width: 45%; text-align: right; }
                .clear { clear: both; }
                .info-section { margin: 20px 0; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                .total-row { font-weight: bold; background-color: #f9f9f9; }
                .footer { margin-top: 30px; text-align: center; color: #666; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>PURCHASE ORDER (BON DE COMMANDE)</h1>
                <h2>{$bonCommend->bonCommendCode}</h2>
            </div>
            
            <div class='company-info'>
                <h3>From:</h3>
                <p><strong>Hospital Information System</strong></p>
                <p>Service: {$serviceName}</p>
                <p>Date: {$createdDate}</p>
                <p>Status: " . ucfirst($bonCommend->status) . "</p>
            </div>
            
            <div class='supplier-info'>
                <h3>To:</h3>
                <p><strong>{$supplierName}</strong></p>";
                
        if ($bonCommend->fournisseur) {
            if ($bonCommend->fournisseur->contact_person) {
                $html .= "<p>Contact: {$bonCommend->fournisseur->contact_person}</p>";
            }
            if ($bonCommend->fournisseur->phone) {
                $html .= "<p>Phone: {$bonCommend->fournisseur->phone}</p>";
            }
            if ($bonCommend->fournisseur->email) {
                $html .= "<p>Email: {$bonCommend->fournisseur->email}</p>";
            }
        }
        
        $html .= "
            </div>
            
            <div class='clear'></div>
            
            <div class='info-section'>
                <h3>Order Details</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Quantity </th>
                            <th>Unit</th>
                        </tr>
                    </thead>
                    <tbody>";
                    
        foreach ($bonCommend->items as $item) {
            $productName = $item->product ? $item->product->name : 'Unknown Product';
            
            $html .= "
                        <tr>
                            <td>{$productName}</td>
                            <td>{$item->quantity_desired}</td>
                            <td>{$item->unit}</td>
                        </tr>";
        }
        
        $html .= "
                    </tbody>
                </table>
            </div>
            
            <div class='info-section'>
                <h3>Instructions</h3>
                <p>This is a purchase order request. Please review the items and provide pricing and availability.</p>
                <p>Delivery should be coordinated with the requesting service.</p>
                <p>Please confirm receipt and provide estimated delivery dates.</p>
            </div>
            
            <div class='footer'>
                <p>Generated on " . now()->format('d/m/Y H:i') . "</p>
                <p>This is an automatically generated document.</p>
            </div>
        </body>
        </html>";
        
        return $html;
    }

    /**
     * Send bon commend to supplier.
     */
    public function send($id)
    {
        try {
            $bonCommend = BonCommend::findOrFail($id);

            if ($bonCommend->status !== 'draft') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only draft bon commends can be sent'
                ], 422);
            }

            $bonCommend->update(['status' => 'sent']);

            return response()->json([
                'status' => 'success',
                'data' => $bonCommend,
                'message' => 'Bon commend sent successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bon commend not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error sending bon commend: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send bon commend'
            ], 500);
        }
    }

    /**
     * Get items for a specific bon commend
     */
    public function getBonCommendItems($id)
    {
        try {
            $bonCommend = BonCommend::with([
                'items.product:id,name,product_code,forme,unit',
                'fournisseur:id,company_name'
            ])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'bonCommend' => [
                        'id' => $bonCommend->id,
                        'bonCommendCode' => $bonCommend->bonCommendCode,
                        'status' => $bonCommend->status,
                        'fournisseur' => $bonCommend->fournisseur,
                    ],
                    'items' => $bonCommend->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product_id' => $item->product_id,
                            'product_name' => $item->product->name ?? 'Unknown Product',
                            'product_code' => $item->product->product_code ?? '',
                            'quantity' => $item->quantity,
                            'quantity_desired' => $item->quantity_desired,
                            'price' => $item->price,
                            'unit' => $item->unit,
                            'status' => $item->status,
                            'total_price' => $item->quantity * $item->price,
                        ];
                    })
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching bon commend items: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch items'
            ], 500);
        }
    }

    /**
     * Update item quantity in bon commend
     */
    public function updateItemQuantity(Request $request, $id, $itemId)
    {
        $request->validate([
            'quantity' => 'nullable|integer|min:1',
            'quantity_desired' => 'nullable|integer|min:1',
            'price' => 'nullable|numeric|min:0'
        ]);

        try {
            $bonCommend = BonCommend::findOrFail($id);

            // Only allow updates if status is draft
            if ($bonCommend->status !== 'draft') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot update items in non-draft bon commend'
                ], 400);
            }

            $item = BonCommendItem::where('bon_commend_id', $id)
                ->where('id', $itemId)
                ->firstOrFail();

            $updateData = [];
            if ($request->has('quantity')) {
                $updateData['quantity'] = $request->quantity;
            }
            if ($request->has('quantity_desired')) {
                $updateData['quantity_desired'] = $request->quantity_desired;
            }
            if ($request->has('price')) {
                $updateData['price'] = $request->price;
            }

            $item->update($updateData);

            return response()->json([
                'status' => 'success',
                'message' => 'Item quantity updated successfully',
                'data' => [
                    'quantity' => $item->quantity,
                    'quantity_desired' => $item->quantity_desired,
                    'price' => $item->price,
                    'total_price' => $item->quantity * $item->price
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating item quantity: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update item quantity'
            ], 500);
        }
    }

    /**
     * Upload attachment for bon commend
     */
    public function uploadAttachment(Request $request, $id)
    {
        $request->validate([
            'attachment' => 'required|file|max:10240|mimes:pdf,doc,docx,jpg,jpeg,png',
            'filename' => 'required|string|max:255'
        ]);

        try {
            $bonCommend = BonCommend::findOrFail($id);

            $file = $request->file('attachment');
            $filename = $request->filename;
            $path = $file->store('bon-commend-attachments', 'public');

            $attachment = BonCommendAttachment::create([
                'bon_commend_id' => $bonCommend->id,
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
            $attachment = BonCommendAttachment::where('bon_commend_id', $id)
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
            $attachment = BonCommendAttachment::where('bon_commend_id', $id)
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
