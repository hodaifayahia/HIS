<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\InventoryAuditProduct;
use App\Models\Stockage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\InventoryAuditProductExport;
use App\Exports\InventoryAuditProductTemplateExport;
use App\Imports\InventoryAuditProductImport;
use Barryvdh\DomPDF\Facade\Pdf;

class InventoryAuditProductController extends Controller
{
    /**
     * Get all products with their theoretical quantities for audit
     */
    public function getProductsForAudit(Request $request)
    {
        try {
            $query = Product::query()
                ->select(
                    'products.id as product_id',
                    'products.name as product_name',
                    'stockages.id as stockage_id',
                    'stockages.name as stockage_name',
                    DB::raw('COALESCE(SUM(inventories.quantity), 0) as theoretical_quantity')
                )
                ->leftJoin('inventories', 'products.id', '=', 'inventories.product_id')
                ->leftJoin('stockages', 'inventories.stockage_id', '=', 'stockages.id')
                ->groupBy('products.id', 'products.name', 'stockages.id', 'stockages.name');

            // Filter by stockage if provided
            if ($request->has('stockage_id') && $request->stockage_id) {
                $query->where('stockages.id', $request->stockage_id);
            }

            // Only include products with inventory records
            $query->havingRaw('COALESCE(SUM(inventories.quantity), 0) >= 0');

            $products = $query->orderBy('products.name')->get();

            return response()->json($products);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to load products for audit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save audit results and update inventory
     */
    public function saveAudit(Request $request)
    {
        dd($request->all());
        $request->validate([
            'audit_items' => 'required|array',
            'audit_items.*.product_id' => 'required|exists:products,id',
            'audit_items.*.stockage_id' => 'required|exists:stockages,id',
            'audit_items.*.theoretical_quantity' => 'required|numeric',
            'audit_items.*.actual_quantity' => 'required|numeric|min:0',
            'audit_items.*.difference' => 'required|numeric',
            'audit_items.*.variance_percent' => 'nullable|numeric',
            'audit_items.*.notes' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            $savedAudits = [];
            $updatedInventories = [];
            $itemsUpdatedCount = 0;

            foreach ($request->audit_items as $item) {
                // Ensure numeric values
                $theoreticalQty = floatval($item['theoretical_quantity']);
                $actualQty = floatval($item['actual_quantity']);
                $difference = floatval($item['difference']);

                // Save audit record
                $audit = InventoryAuditProduct::create([
                    'product_id' => $item['product_id'],
                    'stockage_id' => $item['stockage_id'],
                    'theoretical_quantity' => $theoreticalQty,
                    'actual_quantity' => $actualQty,
                    'difference' => $difference,
                    'variance_percent' => $item['variance_percent'] ?? 0,
                    'notes' => $item['notes'] ?? null,
                    'audited_by' => Auth::id(),
                    'audited_at' => now(),
                    'status' => 'completed'
                ]);

                $savedAudits[] = $audit;

                // Update inventory quantities if there's a significant difference
                if (abs($difference) > 0.001) {
                    // Get all inventory records for this product and stockage
                    $inventories = Inventory::where('product_id', $item['product_id'])
                        ->where('stockage_id', $item['stockage_id'])
                        ->orderBy('expiry_date', 'asc')
                        ->get();

                    if ($inventories->count() > 0) {
                        // Calculate the adjustment needed
                        $adjustment = $difference;
                        
                        // Adjust inventory records
                        // If positive difference (overage), add to the most recent batch
                        // If negative difference (shortage), deduct from oldest batches first (FIFO)
                        
                        if ($adjustment > 0) {
                            // Add to the most recent inventory record
                            $latestInventory = $inventories->last();
                            $latestInventory->quantity += $adjustment;
                            $latestInventory->total_units = $latestInventory->quantity * ($latestInventory->product->boite_de ?? 1);
                            $latestInventory->save();
                            $updatedInventories[] = $latestInventory;
                            $itemsUpdatedCount++;
                        } else {
                            // Deduct from oldest batches first
                            $remainingAdjustment = abs($adjustment);
                            
                            foreach ($inventories as $inventory) {
                                if ($remainingAdjustment <= 0) break;
                                
                                $deduction = min($inventory->quantity, $remainingAdjustment);
                                $inventory->quantity -= $deduction;
                                $inventory->total_units = $inventory->quantity * ($inventory->product->boite_de ?? 1);
                                $inventory->save();
                                $updatedInventories[] = $inventory;
                                $itemsUpdatedCount++;
                                
                                $remainingAdjustment -= $deduction;
                            }
                        }
                    } else {
                        // No existing inventory records, create a new one if positive adjustment
                        if ($adjustment > 0) {
                            $product = Product::find($item['product_id']);
                            $newInventory = Inventory::create([
                                'product_id' => $item['product_id'],
                                'stockage_id' => $item['stockage_id'],
                                'quantity' => $adjustment,
                                'total_units' => $adjustment * ($product->boite_de ?? 1),
                                'batch_number' => 'AUDIT-' . now()->format('YmdHis'),
                                'acquisition_price' => 0,
                                'selling_price' => 0,
                                'expiry_date' => now()->addYear()
                            ]);
                            $updatedInventories[] = $newInventory;
                            $itemsUpdatedCount++;
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Audit saved successfully. {$itemsUpdatedCount} inventory records updated.",
                'audits' => $savedAudits,
                'updated_inventories' => count($updatedInventories),
                'items_with_changes' => $itemsUpdatedCount
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Inventory Audit Save Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to save audit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download Excel template for audit
     */
    public function downloadTemplate()
    {
        try {
            return Excel::download(
                new InventoryAuditProductTemplateExport(),
                'inventory_audit_template_' . now()->format('Y-m-d') . '.xlsx'
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to download template',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export current audit data to Excel
     */
    public function exportToExcel(Request $request)
    {
        try {
            $stockageId = $request->input('stockage_id');
            
            return Excel::download(
                new InventoryAuditProductExport($stockageId),
                'inventory_audit_' . now()->format('Y-m-d') . '.xlsx'
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to export audit data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import audit data from Excel
     */
    public function importFromExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:10240'
        ]);

        try {
            $import = new InventoryAuditProductImport();
            Excel::import($import, $request->file('file'));

            return response()->json([
                'message' => 'Audit data imported successfully',
                'items' => $import->getImportedData()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to import audit data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate PDF report of audit results
     */
    public function generatePdfReport(Request $request)
    {
        $request->validate([
            'audit_items' => 'required|array',
            'audit_items.*.product_name' => 'required|string',
            'audit_items.*.stockage_name' => 'required|string',
            'audit_items.*.theoretical_quantity' => 'required|numeric',
            'audit_items.*.actual_quantity' => 'required|numeric',
            'audit_items.*.difference' => 'required|numeric',
            'audit_items.*.variance_percent' => 'nullable|numeric',
            'audit_items.*.notes' => 'nullable|string'
        ]);

        try {
            $auditItems = $request->audit_items;
            
            // Calculate summary
            $summary = [
                'total_items' => count($auditItems),
                'items_with_differences' => collect($auditItems)->filter(fn($item) => abs($item['difference']) > 0)->count(),
                'total_shortage' => collect($auditItems)->filter(fn($item) => $item['difference'] < 0)->sum(fn($item) => abs($item['difference'])),
                'total_overage' => collect($auditItems)->filter(fn($item) => $item['difference'] > 0)->sum('difference'),
            ];

            $pdf = Pdf::loadView('reports.inventory-audit', [
                'audit_items' => $auditItems,
                'summary' => $summary,
                'generated_at' => now()->format('Y-m-d H:i:s'),
                'generated_by' => Auth::user()->name ?? 'System'
            ]);

            return $pdf->download('inventory_audit_report_' . now()->format('Y-m-d') . '.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate PDF report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get audit history
     */
    public function getAuditHistory(Request $request)
    {
        try {
            $query = InventoryAuditProduct::with(['product', 'stockage', 'auditedBy'])
                ->orderBy('audited_at', 'desc');

            if ($request->has('product_id')) {
                $query->where('product_id', $request->product_id);
            }

            if ($request->has('stockage_id')) {
                $query->where('stockage_id', $request->stockage_id);
            }

            if ($request->has('days')) {
                $query->recent($request->days);
            }

            $audits = $query->paginate($request->input('per_page', 20));

            return response()->json($audits);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to load audit history',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
