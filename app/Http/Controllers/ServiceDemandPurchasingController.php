<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServiceDemendPurchcing;
use App\Models\ServiceDemendPurchcingItem;
use App\Models\ServiceDemandItemFournisseur;
use App\Models\Fournisseur;
use App\Models\FactureProforma;
use App\Models\FactureProformaProduct;
use App\Models\CONFIGURATION\Service;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

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
                $query->where(function($q) use ($search) {
                    $q->where('demand_code', 'like', "%{$search}%")
                      ->orWhere('notes', 'like', "%{$search}%");
                });
            }

            $demands = $query->orderBy('created_at', 'desc')->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $demands
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching demands: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch demands'
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
                'status' => 'draft'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $demand->load(['service', 'items.product']),
                'message' => 'Demand created successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating demand: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create demand'
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
                'items.fournisseurAssignments.assignedBy:id,name'
            ])
                ->findOrFail($id);

            // Load bon commends for each item manually since it's a complex relationship
            foreach ($demand->items as $item) {
                $item->bonCommends = $item->bonCommends()->with(['fournisseur:id,company_name', 'creator:id,name'])->get();
            }

            return response()->json([
                'success' => true,
                'data' => $demand
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("Demand not found with ID: " . $id);
            return response()->json([
                'success' => false,
                'message' => 'Demand not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching demand: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch demand'
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
                    'message' => 'Cannot update demand that has been sent'
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
                'message' => 'Demand updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating demand: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update demand'
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
                    'message' => 'Cannot delete demand that has been sent'
                ], 403);
            }

            $demand->delete();

            return response()->json([
                'success' => true,
                'message' => 'Demand deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting demand: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete demand'
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
                    'message' => 'Cannot add items to demand that has been sent'
                ], 403);
            }

            // Check if product already exists in demand
            $existingItem = $demand->items()->where('product_id', $request->product_id)->first();
            if ($existingItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product already exists in this demand'
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
                'message' => 'Item added successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error adding item to demand: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to add item'
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
                    'message' => 'Cannot update items for demand that has been sent'
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
                'message' => 'Item updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating item: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update item'
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
                    'message' => 'Cannot remove items from demand that has been sent'
                ], 403);
            }

            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Item removed successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error removing item: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove item'
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
                    'message' => 'Demand has already been sent'
                ], 403);
            }

            // Check if demand has items
            if ($demand->items()->count() === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot send demand without items'
                ], 400);
            }

            $demand->update(['status' => 'sent']);

            return response()->json([
                'success' => true,
                'data' => $demand->load(['service', 'items.product']),
                'message' => 'Demand sent successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending demand: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send demand'
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
                'data' => $services
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching services: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch services'
            ], 500);
        }
    }

    public function getProducts(Request $request)
    {
        try {
            $query = Product::query();

            // Filter by service if provided
            if ($request->has('service_id')) {
                // Add logic to filter products by service if there's a relationship
                // This depends on your product-service relationship structure
            }

            // Search by product name or code
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
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
                'data' => $products
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching products: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch products'
            ], 500);
        }
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
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching stats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics'
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
                            'product_code' => $item->product_code
                        ],
                        'current_stock' => $quantity,
                        'suggested_quantity' => max(50, $quantity * 2), // Suggest double current stock or minimum 50
                        'suggested_price' => null,
                        'reason' => '',
                        'stockage_name' => $item->stockage_name,
                        'batch_number' => $item->batch_number,
                        'expiry_date' => $item->expiry_date
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
                Log::warning('Error fetching stock data for suggestions: ' . $e->getMessage());
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
                        'controlled_substances' => $controlledSubstances->count()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching suggestions: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch suggestions'
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
                    'message' => 'Can only assign fournisseurs to pending items'
                ], 400);
            }

            // Check if this fournisseur is already assigned to this item
            $existingAssignment = ServiceDemandItemFournisseur::where([
                'service_demand_purchasing_item_id' => $itemId,
                'fournisseur_id' => $request->fournisseur_id
            ])->first();

            if ($existingAssignment) {
                return response()->json([
                    'success' => false,
                    'message' => 'This supplier is already assigned to this item'
                ], 400);
            }

            // Check if total assigned quantity doesn't exceed item quantity
            $totalAssigned = $item->fournisseurAssignments->sum('assigned_quantity');
            if ($totalAssigned + $request->assigned_quantity > $item->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Assigned quantity exceeds remaining item quantity'
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
                'status' => 'pending'
            ]);

            $assignment->load(['fournisseur:id,company_name,contact_person,email,phone', 'assignedBy:id,name']);

            return response()->json([
                'success' => true,
                'data' => $assignment,
                'message' => 'Supplier assigned to item successfully'
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error assigning fournisseur to item: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign supplier to item'
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
                        'fournisseur_id' => $assignmentData['fournisseur_id']
                    ])->first();

                    if ($existingAssignment) {
                        $errors[] = "Supplier already assigned to item {$item->product->name}";
                        continue;
                    }

                    // Check quantity constraints
                    $totalAssigned = $item->fournisseurAssignments->sum('assigned_quantity');
                    if ($totalAssigned + $assignmentData['assigned_quantity'] > $item->quantity) {
                        $errors[] = "Assigned quantity exceeds remaining quantity for item {$item->product->name}";
                        continue;
                    }

                    $assignment = ServiceDemandItemFournisseur::create([
                        'service_demand_purchasing_item_id' => $assignmentData['item_id'],
                        'fournisseur_id' => $assignmentData['fournisseur_id'],
                        'assigned_quantity' => $assignmentData['assigned_quantity'],
                        'unit_price' => $assignmentData['unit_price'] ?? null,
                        'unit' => $assignmentData['unit'] ?? $item->product->unit ?? 'unit',
                        'notes' => $assignmentData['notes'] ?? null,
                        'assigned_by' => Auth::id(),
                        'status' => 'pending'
                    ]);

                    $assignment->load(['fournisseur:id,company_name,contact_person,email,phone', 'assignedBy:id,name']);
                    $createdAssignments[] = $assignment;

                } catch (\Exception $e) {
                    $errors[] = "Failed to assign item: " . $e->getMessage();
                }
            }

            DB::commit();

            return response()->json([
                'success' => count($createdAssignments) > 0,
                'data' => $createdAssignments,
                'errors' => $errors,
                'message' => count($createdAssignments) . ' assignments created successfully' . 
                            (count($errors) > 0 ? ', with ' . count($errors) . ' errors' : '')
            ], count($createdAssignments) > 0 ? 201 : 400);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error bulk assigning fournisseurs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk assign suppliers'
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
                'service_demand_purchasing_item_id' => $itemId
            ])->firstOrFail();

            $item = $assignment->item;
            
            // Check quantity constraints (excluding current assignment)
            $totalAssigned = $item->fournisseurAssignments()
                ->where('id', '!=', $assignmentId)
                ->sum('assigned_quantity');
            
            if ($totalAssigned + $request->assigned_quantity > $item->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Updated quantity exceeds item quantity'
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
                'message' => 'Assignment updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating fournisseur assignment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update assignment'
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
                'service_demand_purchasing_item_id' => $itemId
            ])->firstOrFail();

            // Check if assignment can be removed
            if ($assignment->status === 'received') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot remove received assignments'
                ], 400);
            }

            $assignment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Assignment removed successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error removing fournisseur assignment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove assignment'
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
                ->whereHas('item', function($query) use ($demandId) {
                    $query->where('service_demand_purchasing_id', $demandId);
                })
                ->get();

            if ($assignments->count() !== count($request->assignment_ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some assignments not found or do not belong to the specified supplier'
                ], 400);
            }

            // Create facture proforma
            $facture = FactureProforma::create([
                'fournisseur_id' => $request->fournisseur_id,
                'service_demand_purchasing_id' => $demandId,
                'created_by' => Auth::id(),
                'status' => 'draft'
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
                'products.product:id,name,product_code'
            ]);

            return response()->json([
                'success' => true,
                'data' => $facture,
                'message' => 'Facture proforma created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating facture proforma from assignments: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create facture proforma'
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
                'data' => $fournisseurs
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching fournisseurs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch suppliers'
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
                'items.fournisseurAssignments.fournisseur:id,company_name'
            ])->findOrFail($demandId);

            $summary = [
                'demand_id' => $demand->id,
                'demand_code' => $demand->demand_code,
                'total_items' => $demand->items->count(),
                'fully_assigned_items' => 0,
                'partially_assigned_items' => 0,
                'unassigned_items' => 0,
                'assignments_by_supplier' => []
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
                    
                    if (!isset($supplierAssignments[$supplierId])) {
                        $supplierAssignments[$supplierId] = [
                            'supplier_id' => $supplierId,
                            'supplier_name' => $assignment->fournisseur->company_name,
                            'total_items' => 0,
                            'total_quantity' => 0,
                            'total_amount' => 0
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
                'data' => $summary
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching assignment summary: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch assignment summary'
            ], 500);
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
                'note' => 'required|string|max:500'
            ]);

            // Add the note to existing notes or create new
            $existingNotes = $serviceDemand->notes ? $serviceDemand->notes . "\n" : '';
            $newNote = now()->format('Y-m-d H:i:s') . ' - ' . $request->note;
            $serviceDemand->notes = $existingNotes . $newNote;
            $serviceDemand->save();

            return response()->json([
                'success' => true,
                'message' => 'Workflow note added successfully',
                'notes' => $serviceDemand->notes
            ]);

        } catch (\Exception $e) {
            Log::error('Error adding workflow note: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to add workflow note'
            ], 500);
        }
    }
}
