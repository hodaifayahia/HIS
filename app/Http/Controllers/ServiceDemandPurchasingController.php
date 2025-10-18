<?php

namespace App\Http\Controllers;

use App\Models\CONFIGURATION\Service;
use App\Models\FactureProforma;
use App\Models\FactureProformaProduct;
use App\Models\Fournisseur;
use App\Models\Product;
use App\Models\ServiceDemandItemFournisseur;
use App\Models\ServiceDemendPurchcing;
use App\Models\ServiceDemendPurchcingItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ServiceDemandPurchasingController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = ServiceDemendPurchcing::with(['service', 'items.product']);

            // Filter by status if provided
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by service if provided
            if ($request->has('service_id')) {
                $query->where('service_id', $request->service_id);
            }

            // Search by demand code or notes
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('demand_code', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%");
                });
            }

            $demands = $query->orderBy('created_at', 'desc')->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $demands,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching demands: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch demands',
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'service_id' => 'nullable|exists:services,id',
            'expected_date' => 'nullable|date|after_or_equal:today',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $demand = ServiceDemendPurchcing::create([
                'service_id' => $request->service_id,
                'expected_date' => $request->expected_date,
                'notes' => $request->notes,
                'status' => 'draft',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $demand->load(['service', 'items.product']),
                'message' => 'Demand created successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating demand: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create demand',
            ], 500);
        }
    }

    public function show($id)
    {

        try {

            $demand = ServiceDemendPurchcing::with([
                'service',
                'items.product',
                'items.fournisseurAssignments.fournisseur:id,company_name,contact_person,email,phone',
                'items.fournisseurAssignments.assignedBy:id,name',
            ])
                ->findOrFail($id);

            // Load bon commends for each item manually since it's a complex relationship
            foreach ($demand->items as $item) {
                $item->bonCommends = $item->bonCommends()->with(['fournisseur:id,company_name', 'creator:id,name'])->get();
            }

            return response()->json([
                'success' => true,
                'data' => $demand,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Demand not found with ID: '.$id);

            return response()->json([
                'success' => false,
                'message' => 'Demand not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching demand: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch demand',
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'expected_date' => 'nullable|date|after_or_equal:today',
            'notes' => 'nullable|string',
        ]);

        try {
            $demand = ServiceDemendPurchcing::findOrFail($id);

            // Only allow updates if status is draft
            if ($demand->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update demand that has been sent',
                ], 403);
            }

            $demand->update([
                'service_id' => $request->service_id,
                'expected_date' => $request->expected_date,
                'notes' => $request->notes,
            ]);

            return response()->json([
                'success' => true,
                'data' => $demand->load(['service', 'items.product']),
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
            $demand = ServiceDemendPurchcing::findOrFail($id);

            // Only allow deletion if status is draft
            if ($demand->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete demand that has been sent',
                ], 403);
            }

            $demand->delete();

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
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'nullable|numeric|min:0',
            'quantity_by_box' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        try {
            $demand = ServiceDemendPurchcing::findOrFail($id);

            // Only allow adding items if status is draft
            if ($demand->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot add items to demand that has been sent',
                ], 403);
            }

            // Check if product already exists in demand
            $existingItem = $demand->items()->where('product_id', $request->product_id)->first();
            if ($existingItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product already exists in this demand',
                ], 409);
            }

            $item = ServiceDemendPurchcingItem::create([
                'service_demand_purchasing_id' => $demand->id,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
                'unit_price' => $request->unit_price,
                'quantity_by_box' => $request->quantity_by_box ?? false,
                'notes' => $request->notes,
            ]);

            return response()->json([
                'success' => true,
                'data' => $item->load('product'),
                'message' => 'Item added successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Error adding item to demand: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to add item',
            ], 500);
        }
    }

    public function updateItem(Request $request, $id, $itemId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'nullable|numeric|min:0',
            'quantity_by_box' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        try {
            $demand = ServiceDemendPurchcing::findOrFail($id);
            $item = $demand->items()->findOrFail($itemId);

            // Only allow updates if status is draft
            if ($demand->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update items for demand that has been sent',
                ], 403);
            }

            $item->update([
                'quantity' => $request->quantity,
                'unit_price' => $request->unit_price,
                'quantity_by_box' => $request->quantity_by_box ?? false,
                'notes' => $request->notes,
            ]);

            return response()->json([
                'success' => true,
                'data' => $item->load('product'),
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
            $demand = ServiceDemendPurchcing::findOrFail($id);
            $item = $demand->items()->findOrFail($itemId);

            // Only allow removal if status is draft
            if ($demand->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot remove items from demand that has been sent',
                ], 403);
            }

            $item->delete();

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
            $demand = ServiceDemendPurchcing::findOrFail($id);

            // Only allow sending if status is draft
            if ($demand->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Demand has already been sent',
                ], 403);
            }

            // Check if demand has items
            if ($demand->items()->count() === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot send demand without items',
                ], 400);
            }

            $demand->update(['status' => 'sent']);

            return response()->json([
                'success' => true,
                'data' => $demand->load(['service', 'items.product']),
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
            $services = Service::where('is_active', true)
                ->select('id', 'name', 'service_abv as service_code', 'description')
                ->orderBy('name')
                ->get();

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
        // try {
        $query = Product::query();

        // Filter by service if provided
        if ($request->has('service_id')) {
            // Add logic to filter products by service if there's a relationship
            // This depends on your product-service relationship structure
        }

        // Search by product name or code
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code_interne', 'like', "%{$search}%")
                    ->orWhere('designation', 'like', "%{$search}%");
            });
        }

        $products = $query->select('id', 'name', 'code_interne as product_code', 'designation', 'forme as unit', 'nom_commercial')
            ->whereIn('status', ['In Stock', 'Available', 'Active'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);

        // } catch (\Exception $e) {
        //     Log::error('Error fetching products: ' . $e->getMessage());
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Failed to fetch products'
        //     ], 500);
        // }
    }

    public function getStats()
    {
        try {
            $stats = [
                'total_demands' => ServiceDemendPurchcing::count(),
                'draft_demands' => ServiceDemendPurchcing::where('status', 'draft')->count(),
                'sent_demands' => ServiceDemendPurchcing::where('status', 'sent')->count(),
                'approved_demands' => ServiceDemendPurchcing::where('status', 'approved')->count(),
                'rejected_demands' => ServiceDemendPurchcing::where('status', 'rejected')->count(),
                'total_items' => ServiceDemendPurchcingItem::count(),
            ];

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
            // Critical low stock items (quantity <= 5)
            $criticalLowStock = collect();

            // Low stock items (quantity <= 20 but > 5)
            $lowStock = collect();

            // Expiring soon items (expiry within 30 days)
            $expiringSoon = collect();

            // Expired items
            $expired = collect();

            // Controlled substances (flagged items)
            $controlledSubstances = collect();

            // Fetch stock data from inventory/service-stock endpoint
            try {
                // Use DB queries to get stock information
                $stockItems = DB::table('inventories')
                    ->join('products', 'inventories.product_id', '=', 'products.id')
                    ->leftJoin('stockages', 'inventories.stockage_id', '=', 'stockages.id')
                    ->select(
                        'products.id as product_id',
                        'products.name as product_name',
                        'products.forme',
                        'products.code_interne as product_code',
                        'inventories.total_units as quantity',
                        'inventories.expiry_date',
                        'inventories.batch_number',
                        'stockages.name as stockage_name'
                    )
                    ->where('inventories.total_units', '>', 0)
                    ->get();

                foreach ($stockItems as $item) {
                    $quantity = (int) $item->quantity;
                    $productData = [
                        'product_id' => $item->product_id,
                        'product' => [
                            'id' => $item->product_id,
                            'name' => $item->product_name,
                            'forme' => $item->forme,
                            'product_code' => $item->product_code,
                        ],
                        'current_stock' => $quantity,
                        'suggested_quantity' => max(50, $quantity * 2), // Suggest double current stock or minimum 50
                        'suggested_price' => null,
                        'reason' => '',
                        'stockage_name' => $item->stockage_name,
                        'batch_number' => $item->batch_number,
                        'expiry_date' => $item->expiry_date,
                    ];

                    // Critical low stock
                    if ($quantity <= 5) {
                        $productData['reason'] = 'Critical low stock';
                        $productData['suggested_quantity'] = max(100, $quantity * 5);
                        $criticalLowStock->push($productData);
                    }
                    // Low stock
                    elseif ($quantity <= 20) {
                        $productData['reason'] = 'Low stock';
                        $productData['suggested_quantity'] = max(50, $quantity * 3);
                        $lowStock->push($productData);
                    }

                    // Check expiry dates
                    if ($item->expiry_date) {
                        $expiryDate = Carbon::parse($item->expiry_date);
                        $now = Carbon::now();
                        $thirtyDaysFromNow = $now->copy()->addDays(30);

                        // Expired items
                        if ($expiryDate->lte($now)) {
                            $productData['reason'] = 'Expired stock needs replacement';
                            $productData['suggested_quantity'] = max(50, $quantity);
                            $expired->push($productData);
                        }
                        // Expiring soon
                        elseif ($expiryDate->lte($thirtyDaysFromNow)) {
                            $productData['reason'] = 'Expiring soon, replacement needed';
                            $productData['suggested_quantity'] = max(30, $quantity);
                            $expiringSoon->push($productData);
                        }
                    }

                    // Controlled substances (example: check for specific keywords or categories)
                    if (stripos($item->product_name, 'morphine') !== false ||
                        stripos($item->product_name, 'opioid') !== false ||
                        stripos($item->product_name, 'narcotic') !== false) {
                        $productData['reason'] = 'Controlled substance monitoring';
                        $controlledSubstances->push($productData);
                    }
                }

                // Remove duplicates by product_id and take unique items
                $criticalLowStock = $criticalLowStock->unique('product_id')->values();
                $lowStock = $lowStock->unique('product_id')->values();
                $expiringSoon = $expiringSoon->unique('product_id')->values();
                $expired = $expired->unique('product_id')->values();
                $controlledSubstances = $controlledSubstances->unique('product_id')->values();

            } catch (\Exception $e) {
                Log::warning('Error fetching stock data for suggestions: '.$e->getMessage());
                // Continue with empty collections
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'critical_low' => $criticalLowStock,
                    'low_stock' => $lowStock,
                    'expiring_soon' => $expiringSoon,
                    'expired' => $expired,
                    'controlled_substances' => $controlledSubstances,
                    'counts' => [
                        'critical_low' => $criticalLowStock->count(),
                        'low_stock' => $lowStock->count(),
                        'expiring_soon' => $expiringSoon->count(),
                        'expired' => $expired->count(),
                        'controlled_substances' => $controlledSubstances->count(),
                    ],
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
            $demand = ServiceDemendPurchcing::findOrFail($demandId);
            $item = $demand->items()->findOrFail($itemId);

            // Check if item status allows assignment
            if ($item->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Can only assign fournisseurs to pending items',
                ], 400);
            }

            // Check if this fournisseur is already assigned to this item
            $existingAssignment = ServiceDemandItemFournisseur::where([
                'service_demand_purchasing_item_id' => $itemId,
                'fournisseur_id' => $request->fournisseur_id,
            ])->first();

            if ($existingAssignment) {
                return response()->json([
                    'success' => false,
                    'message' => 'This supplier is already assigned to this item',
                ], 400);
            }

            // Check if total assigned quantity doesn't exceed item quantity
            $totalAssigned = $item->fournisseurAssignments->sum('assigned_quantity');
            if ($totalAssigned + $request->assigned_quantity > $item->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Assigned quantity exceeds remaining item quantity',
                ], 400);
            }

            $assignment = ServiceDemandItemFournisseur::create([
                'service_demand_purchasing_item_id' => $itemId,
                'fournisseur_id' => $request->fournisseur_id,
                'assigned_quantity' => $request->assigned_quantity,
                'unit_price' => $request->unit_price,
                'unit' => $request->unit ?? $item->product->unit ?? 'unit',
                'notes' => $request->notes,
                'assigned_by' => Auth::id(),
                'status' => 'pending',
            ]);

            $assignment->load(['fournisseur:id,company_name,contact_person,email,phone', 'assignedBy:id,name']);

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
            DB::beginTransaction();

            $demand = ServiceDemendPurchcing::with('items.product')->findOrFail($demandId);
            $createdAssignments = [];
            $errors = [];

            foreach ($request->assignments as $assignmentData) {
                try {
                    $item = $demand->items()->findOrFail($assignmentData['item_id']);

                    // Check if item status allows assignment
                    if ($item->status !== 'pending') {
                        $errors[] = "Item {$item->product->name} is not in pending status";

                        continue;
                    }

                    // Check for existing assignment
                    $existingAssignment = ServiceDemandItemFournisseur::where([
                        'service_demand_purchasing_item_id' => $assignmentData['item_id'],
                        'fournisseur_id' => $assignmentData['fournisseur_id'],
                    ])->first();

                    if ($existingAssignment) {
                        $errors[] = "Supplier already assigned to item {$item->product->name}";

                        continue;
                    }

                    // Check quantity constraints
                    $totalAssigned = $item->fournisseurAssignments->sum('assigned_quantity');
                   

                    $assignment = ServiceDemandItemFournisseur::create([
                        'service_demand_purchasing_item_id' => $assignmentData['item_id'],
                        'fournisseur_id' => $assignmentData['fournisseur_id'],
                        'assigned_quantity' => $assignmentData['assigned_quantity'],
                        'unit_price' => $assignmentData['unit_price'] ?? null,
                        'unit' => $assignmentData['unit'] ?? $item->product->unit ?? 'unit',
                        'notes' => $assignmentData['notes'] ?? null,
                        'assigned_by' => Auth::id(),
                        'status' => 'pending',
                    ]);

                    $assignment->load(['fournisseur:id,company_name,contact_person,email,phone', 'assignedBy:id,name']);
                    $createdAssignments[] = $assignment;

                } catch (\Exception $e) {
                    $errors[] = 'Failed to assign item: '.$e->getMessage();
                }
            }

            DB::commit();

            return response()->json([
                'success' => count($createdAssignments) > 0,
                'data' => $createdAssignments,
                'errors' => $errors,
                'message' => count($createdAssignments).' assignments created successfully'.
                            (count($errors) > 0 ? ', with '.count($errors).' errors' : ''),
            ], count($createdAssignments) > 0 ? 201 : 400);

        } catch (\Exception $e) {
            DB::rollBack();
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
            $assignment = ServiceDemandItemFournisseur::where([
                'id' => $assignmentId,
                'service_demand_purchasing_item_id' => $itemId,
            ])->firstOrFail();

            $item = $assignment->item;

            // Check quantity constraints (excluding current assignment)
            $totalAssigned = $item->fournisseurAssignments()
                ->where('id', '!=', $assignmentId)
                ->sum('assigned_quantity');

            if ($totalAssigned + $request->assigned_quantity > $item->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Updated quantity exceeds item quantity',
                ], 400);
            }

            $assignment->update([
                'assigned_quantity' => $request->assigned_quantity,
                'unit_price' => $request->unit_price,
                'unit' => $request->unit ?? $assignment->unit,
                'notes' => $request->notes,
                'status' => $request->status ?? $assignment->status,
            ]);

            $assignment->load(['fournisseur:id,company_name,contact_person,email,phone', 'assignedBy:id,name']);

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
            $assignment = ServiceDemandItemFournisseur::where([
                'id' => $assignmentId,
                'service_demand_purchasing_item_id' => $itemId,
            ])->firstOrFail();

            // Check if assignment can be removed
            if ($assignment->status === 'received') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot remove received assignments',
                ], 400);
            }

            $assignment->delete();

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
            DB::beginTransaction();

            $demand = ServiceDemendPurchcing::findOrFail($demandId);

            // Verify all assignments belong to the specified fournisseur and demand
            $assignments = ServiceDemandItemFournisseur::with(['item.product'])
                ->whereIn('id', $request->assignment_ids)
                ->where('fournisseur_id', $request->fournisseur_id)
                ->whereHas('item', function ($query) use ($demandId) {
                    $query->where('service_demand_purchasing_id', $demandId);
                })
                ->get();

            if ($assignments->count() !== count($request->assignment_ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some assignments not found or do not belong to the specified supplier',
                ], 400);
            }

            // Create facture proforma
            $facture = FactureProforma::create([
                'fournisseur_id' => $request->fournisseur_id,
                'service_demand_purchasing_id' => $demandId,
                'created_by' => Auth::id(),
                'status' => 'draft',
            ]);

            // Create facture proforma products from assignments
            foreach ($assignments as $assignment) {
                FactureProformaProduct::create([
                    'factureproforma_id' => $facture->id,
                    'product_id' => $assignment->item->product_id,
                    'quantity' => $assignment->assigned_quantity,
                    'price' => $assignment->unit_price ?? 0,
                    'unit' => $assignment->unit ?? $assignment->item->product->unit ?? 'unit',
                ]);

                // Update assignment status
                $assignment->update(['status' => 'ordered']);
            }

            DB::commit();

            $facture->load([
                'fournisseur:id,company_name,contact_person,email,phone',
                'serviceDemand.service:id,name',
                'creator:id,name',
                'products.product:id,name,product_code',
            ]);

            return response()->json([
                'success' => true,
                'data' => $facture,
                'message' => 'Facture proforma created successfully',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
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
            $fournisseurs = Fournisseur::select('id', 'company_name', 'contact_person', 'email', 'phone')
                ->where('is_active', true)
                ->orderBy('company_name')
                ->get();

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
            $demand = ServiceDemendPurchcing::with([
                'items.product:id,name,product_code',
                'items.fournisseurAssignments.fournisseur:id,company_name',
            ])->findOrFail($demandId);

            $summary = [
                'demand_id' => $demand->id,
                'demand_code' => $demand->demand_code,
                'total_items' => $demand->items->count(),
                'fully_assigned_items' => 0,
                'partially_assigned_items' => 0,
                'unassigned_items' => 0,
                'assignments_by_supplier' => [],
            ];

            $supplierAssignments = [];

            foreach ($demand->items as $item) {
                $totalAssigned = $item->fournisseurAssignments->sum('assigned_quantity');

                if ($totalAssigned === 0) {
                    $summary['unassigned_items']++;
                } elseif ($totalAssigned >= $item->quantity) {
                    $summary['fully_assigned_items']++;
                } else {
                    $summary['partially_assigned_items']++;
                }

                // Group by supplier
                foreach ($item->fournisseurAssignments as $assignment) {
                    $supplierId = $assignment->fournisseur_id;

                    if (! isset($supplierAssignments[$supplierId])) {
                        $supplierAssignments[$supplierId] = [
                            'supplier_id' => $supplierId,
                            'supplier_name' => $assignment->fournisseur->company_name,
                            'total_items' => 0,
                            'total_quantity' => 0,
                            'total_amount' => 0,
                        ];
                    }

                    $supplierAssignments[$supplierId]['total_items']++;
                    $supplierAssignments[$supplierId]['total_quantity'] += $assignment->assigned_quantity;
                    $supplierAssignments[$supplierId]['total_amount'] += $assignment->total_amount;
                }
            }

            $summary['assignments_by_supplier'] = array_values($supplierAssignments);

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
            $demand = ServiceDemendPurchcing::findOrFail($id);

            // Validate current status allows transition
            if (! in_array($demand->status, ['sent', 'approved'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Demand must be sent or approved to create proforma',
                ], 403);
            }

            $demand->update([
                'status' => 'factureprofram',
                'proforma_confirmed' => false,
                'proforma_confirmed_at' => null,
            ]);

            return response()->json([
                'success' => true,
                'data' => $demand->fresh()->load(['service', 'items.product']),
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
            $demand = ServiceDemendPurchcing::findOrFail($id);

            // Validate current status allows transition
            if (! in_array($demand->status, ['factureprofram', 'approved', 'sent'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Demand must be in proforma, approved, or sent status to create bon commend',
                ], 403);
            }

            $demand->update([
                'status' => 'boncommend',
                'boncommend_confirmed' => false,
                'boncommend_confirmed_at' => null,
            ]);

            return response()->json([
                'success' => true,
                'data' => $demand->fresh()->load(['service', 'items.product']),
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
            $demand = ServiceDemendPurchcing::findOrFail($id);

            $demand->update([
                'proforma_confirmed' => true,
                'proforma_confirmed_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $demand->fresh()->load(['service', 'items.product']),
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
            $demand = ServiceDemendPurchcing::findOrFail($id);

            $demand->update([
                'boncommend_confirmed' => true,
                'boncommend_confirmed_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $demand->fresh()->load(['service', 'items.product']),
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
            // Get pricing history from bon_entree_items (actual purchase prices)
            $pricingHistory = DB::table('bon_entree_items')
                ->join('bon_entrees', 'bon_entree_items.bon_entree_id', '=', 'bon_entrees.id')
                ->join('bon_receptions', 'bon_entrees.bon_reception_id', '=', 'bon_receptions.id')
                ->join('fournisseurs', 'bon_receptions.fournisseur_id', '=', 'fournisseurs.id')
                ->select([
                    'bon_entree_items.id',
                    'bon_entree_items.purchase_price as price',
                    'bon_entree_items.quantity',
                    'bon_entree_items.remarks as notes',
                    'bon_entrees.created_at as order_date',
                    'bon_entrees.bon_entree_code as document_reference',
                    'bon_entree_items.created_at',
                    DB::raw("'entree' as order_type"),
                    'fournisseurs.company_name as supplier_name',
                    'bon_entree_items.batch_number',
                    'bon_entree_items.expiry_date',
                    'bon_entrees.status',
                ])
                ->where('bon_entree_items.product_id', $productId)
                ->where('bon_receptions.fournisseur_id', $supplierId)
                ->where('bon_entree_items.purchase_price', '>', 0)
                ->whereIn('bon_entrees.status', ['Draft', 'Validated', 'Transferred'])
                ->orderBy('bon_entrees.created_at', 'desc')
                ->get();

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
            // Get pricing history from bon_entree_items (actual purchase prices)
            $pricingHistory = DB::table('bon_entree_items as bei')
                ->join('bon_entrees as be', 'bei.bon_entree_id', '=', 'be.id')
                ->join('bon_receptions as br', 'be.bon_reception_id', '=', 'br.id')
                ->join('fournisseurs as f', 'br.fournisseur_id', '=', 'f.id')
                ->join('products as p', 'bei.product_id', '=', 'p.id')
                ->where('bei.product_id', $productId)
                ->where('bei.purchase_price', '>', 0) // Only include records with valid prices
                ->select(
                    'f.id as supplier_id',
                    'f.company_name',
                    'f.contact_person',
                    'bei.purchase_price as price',
                    'bei.quantity as quantity',
                    'be.created_at as order_date',
                    'bei.created_at',
                    'be.status as entree_status'
                )
                ->orderBy('bei.created_at', 'desc')
                ->get();

            // Calculate supplier statistics from bon_entree_items data
            $supplierStats = [];
            foreach ($pricingHistory as $record) {
                $supplierId = $record->supplier_id;

                if (! isset($supplierStats[$supplierId])) {
                    $supplierStats[$supplierId] = [
                        'supplier_id' => $supplierId,
                        'company_name' => $record->company_name,
                        'contact_person' => $record->contact_person,
                        'prices' => [],
                        'quantities' => [],
                        'order_dates' => [],
                        'total_orders' => 0,
                        'last_price' => null,
                        'average_price' => 0,
                        'min_price' => null,
                        'max_price' => null,
                        'price_trend' => 'stable', // increasing, decreasing, stable
                        'reliability_score' => 0,
                    ];
                }

                $supplierStats[$supplierId]['prices'][] = $record->price;
                $supplierStats[$supplierId]['quantities'][] = $record->quantity;
                $supplierStats[$supplierId]['order_dates'][] = $record->order_date;
                $supplierStats[$supplierId]['total_orders']++;
            }

            // Calculate final statistics for each supplier
            foreach ($supplierStats as $supplierId => &$stats) {
                if (count($stats['prices']) > 0) {
                    $stats['last_price'] = $stats['prices'][0]; // First price (most recent)
                    $stats['average_price'] = round(array_sum($stats['prices']) / count($stats['prices']), 2);
                    $stats['min_price'] = min($stats['prices']);
                    $stats['max_price'] = max($stats['prices']);

                    // Calculate price trend
                    if (count($stats['prices']) >= 2) {
                        $recentPrices = array_slice($stats['prices'], 0, 3); // Last 3 orders
                        $oldPrices = array_slice($stats['prices'], -3); // First 3 orders

                        $recentAvg = array_sum($recentPrices) / count($recentPrices);
                        $oldAvg = array_sum($oldPrices) / count($oldPrices);

                        if ($recentAvg > $oldAvg * 1.05) {
                            $stats['price_trend'] = 'increasing';
                        } elseif ($recentAvg < $oldAvg * 0.95) {
                            $stats['price_trend'] = 'decreasing';
                        }
                    }

                    // Calculate reliability score (0-100)
                    $consistencyScore = 0;
                    if (count($stats['prices']) > 1) {
                        $priceVariation = ($stats['max_price'] - $stats['min_price']) / $stats['average_price'];
                        $consistencyScore = max(0, 100 - ($priceVariation * 100));
                    } else {
                        $consistencyScore = 50; // Neutral score for single order
                    }

                    $orderFrequencyScore = min(100, $stats['total_orders'] * 10); // Max 100 for 10+ orders

                    $stats['reliability_score'] = round(($consistencyScore + $orderFrequencyScore) / 2);
                }
            }

            // Convert to array and sort by average price (lowest first) then by reliability
            $finalData = array_values($supplierStats);
            usort($finalData, function ($a, $b) {
                // First sort by having pricing data
                if (empty($a['prices']) && ! empty($b['prices'])) {
                    return 1;
                }
                if (! empty($a['prices']) && empty($b['prices'])) {
                    return -1;
                }

                // Then by average price (lower is better)
                if ($a['average_price'] != $b['average_price']) {
                    return $a['average_price'] <=> $b['average_price'];
                }

                // Finally by reliability score (higher is better)
                return $b['reliability_score'] <=> $a['reliability_score'];
            });

            return response()->json([
                'success' => true,
                'data' => $finalData,
                'summary' => [
                    'total_suppliers' => count($finalData),
                    'suppliers_with_history' => count(array_filter($finalData, fn ($s) => ! empty($s['prices']))),
                    'best_price' => ! empty($finalData) && ! empty($finalData[0]['prices']) ? $finalData[0]['average_price'] : null,
                    'price_range' => [
                        'min' => ! empty($finalData) ? min(array_filter(array_column($finalData, 'min_price'))) : null,
                        'max' => ! empty($finalData) ? max(array_filter(array_column($finalData, 'max_price'))) : null,
                    ],
                ],
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
            $suppliers = Fournisseur::where('is_active', true)->get();
            $ratings = [];

            foreach ($suppliers as $supplier) {
                // Get performance metrics from bon receptions
                $receptionStats = DB::table('bon_receptions as br')
                    ->join('bon_reception_items as bri', 'br.id', '=', 'bri.bon_reception_id')
                    ->where('br.fournisseur_id', $supplier->id)
                    ->whereNotNull('br.date_reception')
                    ->selectRaw('
                        COUNT(DISTINCT br.id) as total_orders,
                        AVG(bri.unit_price) as avg_price,
                        COUNT(CASE WHEN br.status = "completed" THEN 1 END) as completed_orders,
                        COUNT(CASE WHEN bri.quantity_received >= bri.quantity_ordered THEN 1 END) as full_deliveries,
                        COUNT(bri.id) as total_items
                    ')
                    ->first();

                // Calculate ratings
                $totalOrders = $receptionStats->total_orders ?? 0;
                $onTimeDelivery = $totalOrders > 0 ? (($receptionStats->completed_orders ?? 0) / $totalOrders) * 100 : 0;
                $qualityScore = $totalOrders > 0 ? (($receptionStats->full_deliveries ?? 0) / ($receptionStats->total_items ?? 1)) * 100 : 0;

                // Base rating calculation (1-5 stars)
                $baseRating = 3.0; // Start with neutral rating

                if ($totalOrders > 0) {
                    // Adjust rating based on performance
                    if ($onTimeDelivery >= 95) {
                        $baseRating += 1.5;
                    } elseif ($onTimeDelivery >= 85) {
                        $baseRating += 1.0;
                    } elseif ($onTimeDelivery >= 70) {
                        $baseRating += 0.5;
                    } elseif ($onTimeDelivery < 50) {
                        $baseRating -= 1.0;
                    }

                    if ($qualityScore >= 95) {
                        $baseRating += 0.5;
                    } elseif ($qualityScore < 70) {
                        $baseRating -= 0.5;
                    }

                    // Order frequency bonus
                    if ($totalOrders >= 50) {
                        $baseRating += 0.3;
                    } elseif ($totalOrders >= 20) {
                        $baseRating += 0.2;
                    } elseif ($totalOrders >= 10) {
                        $baseRating += 0.1;
                    }
                }

                // Cap rating between 1 and 5
                $finalRating = max(1.0, min(5.0, $baseRating));

                $ratings[$supplier->id] = [
                    'rating' => round($finalRating, 1),
                    'total_orders' => $totalOrders,
                    'on_time_delivery' => round($onTimeDelivery),
                    'quality_score' => round($qualityScore),
                    'avg_price' => $receptionStats->avg_price ?? null,
                    'performance_tier' => $this->getPerformanceTier($finalRating, $totalOrders),
                ];
            }

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
}
