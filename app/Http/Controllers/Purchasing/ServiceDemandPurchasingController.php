<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceDemand\ListServiceDemandRequest;
use App\Http\Requests\ServiceDemand\StoreServiceDemandRequest;
use App\Http\Requests\ServiceDemand\UpdateServiceDemandRequest;
use App\Http\Resources\ServiceDemandItemResource;
use App\Models\BonCommend;
use App\Models\CONFIGURATION\Service;
use App\Models\Fournisseur;
use App\Models\Product;
use App\Models\ServiceDemendPurchcing;
use App\Services\Purchsing\order\ServiceDemandAnalyticsService;
use App\Services\Purchsing\order\ServiceDemandDataService;
use App\Services\Purchsing\order\ServiceDemandFournisseurService;
use App\Services\Purchsing\order\ServiceDemandItemService;
use App\Services\Purchsing\order\ServiceDemandListService;
use App\Services\Purchsing\order\ServiceDemandService;
use App\Services\Purchsing\order\ServiceDemandStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ServiceDemandPurchasingController extends Controller
{
    public function index(ListServiceDemandRequest $request)
    {
        // try {
        $serviceDemandListService = new ServiceDemandListService;

        // Get validated filters
        $filters = $request->validated();

        // Get demands using service
        $demands = $serviceDemandListService->getAll($filters);

        return response()->json([
            'success' => true,
            'data' => $demands,
        ]);

        // } catch (\Exception $e) {
        //     Log::error('Error fetching demands: '.$e->getMessage());

        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Failed to fetch demands',
        //     ], 500);
        // }
    }

    public function store(StoreServiceDemandRequest $request)
    {
        // try {
        $service = new ServiceDemandService;
        $demand = $service->create($request->validated());

        return response()->json([
            'success' => true,
            'data' => $demand,
            'message' => 'Demand created successfully',
        ]);

        // } catch (\Exception $e) {
        //     Log::error('Error creating demand: '.$e->getMessage());

        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Failed to create demand',
        //     ], 500);
        // }
    }

    public function show($id)
    {
        try {
            $service = new ServiceDemandService;
            $demand = $service->getById($id);

            // Transform items using ServiceDemandItemResource
            $demand->items = $demand->items->map(function ($item) {
                return new ServiceDemandItemResource($item);
            });

            // Load bon commends through service demand relationship
            if ($demand->id) {
                $bonCommends = BonCommend::where('service_demand_purchasing_id', $demand->id)
                    ->with(['fournisseur:id,company_name', 'creator:id,name', 'items'])
                    ->get();

                $demand->bonCommends = $bonCommends;
            }

            return response()->json([
                'success' => true,
                'data' => $demand,
            ]);

        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'not found') !== false) {
                Log::warning('Demand not found');

                return response()->json([
                    'success' => false,
                    'message' => 'Demand not found',
                ], 404);
            }

            if (strpos($e->getMessage(), 'Unauthorized') !== false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to view this demand',
                ], 403);
            }

            Log::error('Error fetching demand: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch demand',
            ], 500);
        }
    }

    public function update(UpdateServiceDemandRequest $request, $id)
    {
        try {
            $service = new ServiceDemandService;
            $demand = $service->update($id, $request->validated());

            return response()->json([
                'success' => true,
                'data' => $demand,
                'message' => 'Demand updated successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating demand: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update demand',
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $service = new ServiceDemandService;
            $service->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Demand deleted successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting demand: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete demand',
            ], 500);
        }
    }

    public function addItem(Request $request, $id)
    {
        $validatedData = $request->validate([
            'product_id' => 'nullable|required_without:pharmacy_product_id',
            'pharmacy_product_id' => 'nullable|required_without:product_id',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'nullable|numeric|min:0',
            'quantity_by_box' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        try {
            $service = new ServiceDemandItemService;
            $item = $service->addItem($id, $validatedData);

            return response()->json([
                'success' => true,
                'data' => new ServiceDemandItemResource($item),
                'message' => 'Item added successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Error adding item to demand: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to add item: '.$e->getMessage(),
            ], 500);
        }
    }

    public function updateItem(Request $request, $id, $itemId)
    {
        $validatedData = $request->validate([
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'nullable|numeric|min:0',
            'quantity_by_box' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        try {
            $service = new ServiceDemandItemService;
            $item = $service->updateItem($id, $itemId, $validatedData);

            return response()->json([
                'success' => true,
                'data' => $item,
                'message' => 'Item updated successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating item: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update item',
            ], 500);
        }
    }

    public function removeItem($id, $itemId)
    {
        try {
            $service = new ServiceDemandItemService;
            $service->removeItem($id, $itemId);

            return response()->json([
                'success' => true,
                'message' => 'Item removed successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Error removing item: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove item',
            ], 500);
        }
    }

    public function send($id)
    {
        try {
            $service = new ServiceDemandService;
            $demand = $service->send($id);

            return response()->json([
                'success' => true,
                'data' => $demand,
                'message' => 'Demand sent successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending demand: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to send demand',
            ], 500);
        }
    }

    public function getServices()
    {
        try {
            $dataService = new ServiceDemandDataService;
            $services = $dataService->getServices();

            return response()->json([
                'success' => true,
                'data' => $services,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching services: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch services',
            ], 500);
        }
    }

    public function getProducts(Request $request)
    {
        try {
            $dataService = new ServiceDemandDataService;
            $type = $request->get('type');
            $search = $request->get('search', '');
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 50);

            $result = $dataService->getProducts($type, $search, $page, $perPage);

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
                'meta' => $result['meta'],
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching products: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch products',
            ], 500);
        }
    }

    public function getStats()
    {
        try {
            $dataService = new ServiceDemandDataService;
            $stats = $dataService->getStats();

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching stats: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
            ], 500);
        }
    }

    public function getSuggestions(Request $request)
    {
        try {
            $dataService = new ServiceDemandDataService;
            $suggestions = $dataService->getSuggestions();

            return response()->json([
                'success' => true,
                'data' => [
                    'critical_low' => $suggestions['critical_low'],
                    'low_stock' => $suggestions['low_stock'],
                    'expiring_soon' => $suggestions['expiring_soon'],
                    'expired' => $suggestions['expired'],
                    'controlled_substances' => $suggestions['controlled_substances'],
                    'counts' => $suggestions['counts'],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching suggestions: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch suggestions',
            ], 500);
        }
    }

    /**
     * Assign a fournisseur to a specific service demand item
     */
    public function assignFournisseurToItem(Request $request, $demandId, $itemId)
    {
        $request->validate([
            'fournisseur_id' => 'required|exists:fournisseurs,id',
            'assigned_quantity' => 'required|integer|min:1',
            'unit_price' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $service = new ServiceDemandFournisseurService;
            $assignment = $service->assignFournisseur($demandId, $itemId, $request->validated());

            return response()->json([
                'success' => true,
                'data' => $assignment,
                'message' => 'Supplier assigned to item successfully',
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error assigning fournisseur to item: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to assign supplier to item',
            ], 500);
        }
    }

    /**
     * Bulk assign fournisseurs to multiple items
     */
    public function bulkAssignFournisseurs(Request $request, $demandId)
    {
        $request->validate([
            'assignments' => 'required|array|min:1',
            'assignments.*.item_id' => 'required|exists:service_demand_purchasing_items,id',
            'assignments.*.fournisseur_id' => 'required|exists:fournisseurs,id',
            'assignments.*.assigned_quantity' => 'required|integer|min:1',
            'assignments.*.unit_price' => 'nullable|numeric|min:0',
            'assignments.*.unit' => 'nullable|string|max:50',
            'assignments.*.notes' => 'nullable|string|max:500',
        ]);

        try {
            $service = new ServiceDemandFournisseurService;
            $result = $service->bulkAssignFournisseurs($demandId, $request->assignments);

            return response()->json([
                'success' => count($result['assignments']) > 0,
                'data' => $result['assignments'],
                'errors' => $result['errors'],
                'message' => count($result['assignments']).' assignments created successfully'.
                            (count($result['errors']) > 0 ? ', with '.count($result['errors']).' errors' : ''),
            ], count($result['assignments']) > 0 ? 201 : 400);

        } catch (\Exception $e) {
            Log::error('Error bulk assigning fournisseurs: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk assign suppliers',
            ], 500);
        }
    }

    /**
     * Update a fournisseur assignment
     */
    public function updateFournisseurAssignment(Request $request, $demandId, $itemId, $assignmentId)
    {
        $request->validate([
            'assigned_quantity' => 'required|integer|min:1',
            'unit_price' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
            'status' => 'nullable|in:pending,confirmed,ordered,received',
        ]);

        try {
            $service = new ServiceDemandFournisseurService;
            $assignment = $service->updateFournisseurAssignment($demandId, $itemId, $assignmentId, $request->validated());

            return response()->json([
                'success' => true,
                'data' => $assignment,
                'message' => 'Assignment updated successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating fournisseur assignment: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update assignment',
            ], 500);
        }
    }

    /**
     * Remove a fournisseur assignment
     */
    public function removeFournisseurAssignment($demandId, $itemId, $assignmentId)
    {
        try {
            $service = new ServiceDemandFournisseurService;
            $service->removeFournisseurAssignment($demandId, $itemId, $assignmentId);

            return response()->json([
                'success' => true,
                'message' => 'Assignment removed successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Error removing fournisseur assignment: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove assignment',
            ], 500);
        }
    }

    /**
     * Create facture proforma from service demand assignments
     */
    public function createFactureProformaFromAssignments(Request $request, $demandId)
    {
        $request->validate([
            'fournisseur_id' => 'required|exists:fournisseurs,id',
            'assignment_ids' => 'required|array|min:1',
            'assignment_ids.*' => 'exists:service_demand_item_fournisseurs,id',
        ]);

        try {
            $service = new ServiceDemandFournisseurService;
            $facture = $service->createFactureProformaFromAssignments($demandId, $request->fournisseur_id, $request->assignment_ids);

            return response()->json([
                'success' => true,
                'data' => $facture,
                'message' => 'Facture proforma created successfully',
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating facture proforma from assignments: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create facture proforma',
            ], 500);
        }
    }

    /**
     * Get all fournisseurs for dropdown
     */
    public function getFournisseurs()
    {
        try {
            $dataService = new ServiceDemandDataService;
            $fournisseurs = $dataService->getFournisseurs();

            return response()->json([
                'success' => true,
                'data' => $fournisseurs,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching fournisseurs: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch suppliers',
            ], 500);
        }
    }

    /**
     * Get service demand assignment summary
     */
    public function getAssignmentSummary($demandId)
    {
        try {
            $service = new ServiceDemandFournisseurService;
            $summary = $service->getAssignmentSummary($demandId);

            return response()->json([
                'success' => true,
                'data' => $summary,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching assignment summary: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch assignment summary',
            ], 500);
        }
    }

    /**
     * Update service demand status to 'factureprofram'
     */
    public function updateToFactureProforma(Request $request, $id)
    {
        try {
            $service = new ServiceDemandStatusService;
            $demand = $service->updateToFactureProforma($id);

            return response()->json([
                'success' => true,
                'data' => $demand,
                'message' => 'Service demand updated to proforma status successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating to facture proforma status: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update status',
            ], 500);
        }
    }

    /**
     * Update service demand status to 'boncommend'
     */
    public function updateToBonCommend(Request $request, $id)
    {
        try {
            $service = new ServiceDemandStatusService;
            $demand = $service->updateToBonCommend($id);

            return response()->json([
                'success' => true,
                'data' => $demand,
                'message' => 'Service demand updated to bon commend status successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating to bon commend status: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update status',
            ], 500);
        }
    }

    /**
     * Confirm proforma for service demand
     */
    public function confirmProforma(Request $request, $id)
    {
        try {
            $service = new ServiceDemandStatusService;
            $demand = $service->confirmProforma($id);

            return response()->json([
                'success' => true,
                'data' => $demand,
                'message' => 'Proforma confirmed successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Error confirming proforma: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm proforma',
            ], 500);
        }
    }

    /**
     * Confirm bon commend for service demand
     */
    public function confirmBonCommend(Request $request, $id)
    {
        try {
            $service = new ServiceDemandStatusService;
            $demand = $service->confirmBonCommend($id);

            return response()->json([
                'success' => true,
                'data' => $demand,
                'message' => 'Bon commend confirmed successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Error confirming bon commend: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm bon commend',
            ], 500);
        }
    }

    /**
     * Get detailed pricing history for a specific product-supplier combination
     */
    public function getDetailedSupplierHistory($productId, $supplierId)
    {
        try {
            $analyticsService = new ServiceDemandAnalyticsService;
            $pricingHistory = $analyticsService->getDetailedSupplierHistory($productId, $supplierId);

            return response()->json([
                'success' => true,
                'data' => $pricingHistory,
                'message' => 'Detailed pricing history retrieved successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve detailed pricing history: '.$e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    /**
     * Get supplier pricing history for a product
     */
    public function getSupplierPricingForProduct($productId)
    {
        try {
            $analyticsService = new ServiceDemandAnalyticsService;
            $result = $analyticsService->getSupplierPricingForProduct($productId);

            return response()->json([
                'success' => true,
                'data' => $result['suppliers'],
                'summary' => $result['summary'],
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching supplier pricing for product: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch supplier pricing data',
            ], 500);
        }
    }

    /**
     * Get enhanced supplier ratings and performance metrics
     */
    public function getSupplierRatings()
    {
        try {
            $analyticsService = new ServiceDemandAnalyticsService;
            $ratings = $analyticsService->getSupplierRatings();

            return response()->json([
                'success' => true,
                'data' => $ratings,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching supplier ratings: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch supplier ratings',
            ], 500);
        }
    }

    /**
     * Helper method to determine supplier performance tier
     */
    private function getPerformanceTier($rating, $totalOrders)
    {
        if ($rating >= 4.5 && $totalOrders >= 20) {
            return 'premium';
        } elseif ($rating >= 4.0 && $totalOrders >= 10) {
            return 'excellent';
        } elseif ($rating >= 3.5 && $totalOrders >= 5) {
            return 'good';
        } elseif ($rating >= 3.0) {
            return 'average';
        } else {
            return 'poor';
        }
    }

    /**
     * Add a workflow tracking note to the service demand
     */
    public function addWorkflowNote(Request $request, $id)
    {
        try {
            $serviceDemand = ServiceDemendPurchcing::findOrFail($id);

            $request->validate([
                'note' => 'required|string|max:500',
            ]);

            // Add the note to existing notes or create new
            $existingNotes = $serviceDemand->notes ? $serviceDemand->notes."\n" : '';
            $newNote = now()->format('Y-m-d H:i:s').' - '.$request->note;
            $serviceDemand->notes = $existingNotes.$newNote;
            $serviceDemand->save();

            return response()->json([
                'success' => true,
                'message' => 'Workflow note added successfully',
                'notes' => $serviceDemand->notes,
            ]);

        } catch (\Exception $e) {
            Log::error('Error adding workflow note: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to add workflow note',
            ], 500);
        }
    }

    /**
     * Get pricing history for pharmacy products
     * Pharmacy products track their own pricing in the pharmacy_products table
     */
    public function getPharmacyProductPricingHistory($productId)
    {
        try {
            // Validate productId
            if (! $productId || $productId === 'undefined' || ! is_numeric($productId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or missing pharmacy product ID',
                    'data' => [],
                ], 400);
            }

            // Get the pharmacy product
            $pharmacyProduct = \App\Models\PharmacyProduct::find($productId);

            if (! $pharmacyProduct) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pharmacy product not found',
                    'data' => [],
                ], 404);
            }

            // Get pricing history from pharmacy_stock_movements (internal transfers/purchases)
            $pricingHistory = \DB::table('pharmacy_stock_movement_items as psmi')
                ->join('pharmacy_stock_movements as psm', 'psmi.pharmacy_movement_id', '=', 'psm.id')
                ->leftJoin('services as providing', 'psm.providing_service_id', '=', 'providing.id')
                ->leftJoin('services as requesting', 'psm.requesting_service_id', '=', 'requesting.id')
                ->where('psmi.product_id', $productId) // Fixed: column is 'product_id' not 'pharmacy_product_id'
                ->select(
                    'psm.id as movement_id',
                    'psm.status as movement_type',
                    'psm.status',
                    'psm.created_at',
                    'psm.updated_at',
                    'providing.name as providing_service',
                    'requesting.name as requesting_service',
                    'psmi.requested_quantity',
                    'psmi.approved_quantity',
                    'psmi.executed_quantity',
                    'psmi.provided_quantity',
                    'psmi.notes'
                )
                ->orderBy('psm.created_at', 'desc')
                ->limit(100)
                ->get();

            // Get current pricing from the product itself
            $currentPricing = [
                'unit_cost' => $pharmacyProduct->unit_cost,
                'selling_price' => $pharmacyProduct->selling_price,
                'markup_percentage' => $pharmacyProduct->markup_percentage,
            ];

            // Calculate statistics
            $totalMovements = $pricingHistory->count();
            $totalQuantityMoved = $pricingHistory->sum('executed_quantity');

            return response()->json([
                'success' => true,
                'data' => [
                    'product' => [
                        'id' => $pharmacyProduct->id,
                        'name' => $pharmacyProduct->name ?? $pharmacyProduct->generic_name ?? $pharmacyProduct->brand_name,
                        'generic_name' => $pharmacyProduct->generic_name,
                        'brand_name' => $pharmacyProduct->brand_name,
                        'sku' => $pharmacyProduct->sku,
                        'barcode' => $pharmacyProduct->barcode,
                    ],
                    'current_pricing' => $currentPricing,
                    'movement_history' => $pricingHistory,
                    'statistics' => [
                        'total_movements' => $totalMovements,
                        'total_quantity_moved' => $totalQuantityMoved,
                        'average_quantity_per_movement' => $totalMovements > 0 ? round($totalQuantityMoved / $totalMovements, 2) : 0,
                    ],
                ],
                'message' => 'Pharmacy product pricing history retrieved successfully',
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching pharmacy product pricing history: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pharmacy product pricing history',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate a unique facture proforma code.
     * Format: FP-YYYY-MM-DD-XXXX (e.g., FP-2025-11-04-0001)
     */
    private function generateFactureProformaCode(): string
    {
        $prefix = 'FP';
        $date = now()->format('Y-m-d');
        $sequence = \App\Models\FactureProforma::whereDate('created_at', today())->count() + 1;

        return $prefix.'-'.$date.'-'.sprintf('%04d', $sequence);
    }
}
