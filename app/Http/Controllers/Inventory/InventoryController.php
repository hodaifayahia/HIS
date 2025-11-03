<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Stockage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min($request->get('per_page', 10), 100);

        // Build query with selective column loading
        $query = Inventory::select(
            'id',
            'product_id',
            'stockage_id',
            'quantity',
            'total_units',
            'unit',
            'batch_number',
            'serial_number',
            'purchase_price',
            'barcode',
            'expiry_date',
            'location',
            'created_at',
            'updated_at'
        );

        // Filter by stockage
        if ($request->has('stockage_id') && $request->stockage_id) {
            $query->where('stockage_id', $request->stockage_id);
        }

        // Search functionality - use indexed columns only
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code_interne', 'like', "%{$search}%");
            });
        }

        // Apply database-level pagination BEFORE loading relationships
        $paginatedInventories = $query->paginate($perPage);

        // Load relationships only for paginated results
        $paginatedInventories->load([
            'product:id,name,code_interne,forme,boite_de',
            'stockage:id,name,type,location'
        ]);

        // Process inventory data and calculate alerts
        $data = $paginatedInventories->items();
        $processedData = array_map(fn($inv) => $this->processInventoryData($inv), $data);

        return response()->json([
            'success' => true,
            'data' => $processedData,
            'meta' => [
                'current_page' => $paginatedInventories->currentPage(),
                'last_page' => $paginatedInventories->lastPage(),
                'per_page' => $paginatedInventories->perPage(),
                'total' => $paginatedInventories->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'stockage_id' => 'required|exists:stockages,id',
            'quantity' => 'required|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'batch_number' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'purchase_price' => 'nullable|numeric|min:0',
            'expiry_date' => 'nullable|date|after:today',
            'location' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check if product already exists with same batch, serial, and expiry details
        $existingQuery = Inventory::where('product_id', $request->product_id)
            ->where('stockage_id', $request->stockage_id);

        // Handle batch_number comparison (including null/empty)
        if (empty($request->batch_number)) {
            $existingQuery->where(function($q) {
                $q->whereNull('batch_number')->orWhere('batch_number', '');
            });
        } else {
            $existingQuery->where('batch_number', $request->batch_number);
        }

        // Handle serial_number comparison (including null/empty)
        if (empty($request->serial_number)) {
            $existingQuery->where(function($q) {
                $q->whereNull('serial_number')->orWhere('serial_number', '');
            });
        } else {
            $existingQuery->where('serial_number', $request->serial_number);
        }

        // Handle expiry_date comparison (including null)
        if (empty($request->expiry_date)) {
            $existingQuery->whereNull('expiry_date');
        } else {
            $existingQuery->whereRaw('DATE(expiry_date) = ?', [date('Y-m-d', strtotime($request->expiry_date))]);
        }

        $existingInventory = $existingQuery->first();

        if ($existingInventory) {
            // Same product with identical details - adjust quantity
            $quantityByBox = $request->boolean('quantity_by_box', false);
            $product = Product::find($request->product_id);

            if ($quantityByBox && $product && $product->boite_de) {
                // If quantity is by box, add the box quantity converted to total units
                $quantityToAdd = $request->quantity * $product->boite_de;
            } else {
                // If quantity is individual units, add directly
                $quantityToAdd = $request->quantity;
            }

            $newQuantity = $existingInventory->quantity + $quantityToAdd;
            $newTotalUnits = $existingInventory->total_units + $quantityToAdd;

            $existingInventory->update([
                'quantity' => $newQuantity,
                'total_units' => $newTotalUnits,
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $existingInventory->load(['product', 'stockage']),
                'message' => 'Product quantity adjusted successfully',
            ]);
        }

        // Product doesn't exist with these exact details - create new entry
        $data = $request->all();

        // Ensure expiry_date is stored as date only (without time)
        if (isset($data['expiry_date'])) {
            $data['expiry_date'] = date('Y-m-d', strtotime($data['expiry_date']));
        }

        // Handle quantity calculation based on whether it's by box or individual units
        $quantityByBox = $request->boolean('quantity_by_box', false);
        $product = Product::find($request->product_id);

        if ($quantityByBox && $product && $product->boite_de) {
            // If quantity is by box, calculate total_units as quantity * boite_de
            // Keep the quantity field as the number entered
            $data['quantity'] = $request->quantity * $product->boite_de;
            $data['total_units'] = $request->quantity * $product->boite_de;
        } else {
            // If quantity is individual units, both fields equal the entered quantity
            $data['quantity'] = $request->quantity;
            $data['total_units'] = $request->quantity;
        }

        // Set unit based on product's forme if not provided
        if (empty($data['unit']) && $product) {
            $data['unit'] = $product->forme ?? 'pieces';
        }

        $inventory = Inventory::create($data);

        return response()->json([
            'success' => true,
            'data' => $inventory->load(['product', 'stockage']),
            'message' => 'Product added to inventory successfully',
        ], 201);
    }

    public function show($inventory)
    {
        if ($inventory === 'service-stock') {
            return $this->getServiceStock(request());
        }
        $inventory = Inventory::findOrFail($inventory);

        return response()->json([
            'success' => true,
            'data' => $inventory->load(['product', 'stockage']),
        ]);
    }

    public function update(Request $request, Inventory $inventory)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'sometimes|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'batch_number' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'purchase_price' => 'nullable|numeric|min:0',
            'expiry_date' => 'nullable|date',
            'location' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $inventory->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $inventory->load(['product', 'stockage']),
            'message' => 'Inventory updated successfully',
        ]);
    }

    public function destroy(Inventory $inventory)
    {
        $inventory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Inventory item removed successfully',
        ]);
    }

    public function adjustStock(Request $request, Inventory $inventory)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|numeric',
            'adjustment_type' => 'required|in:increase,decrease',
            'reason' => 'required_if:adjustment_type,decrease|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $newQuantity = $inventory->quantity + $request->quantity;

            if ($newQuantity < 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock',
                ], 422);
            }

            $inventory->update([
                'quantity' => $newQuantity,
                'updated_at' => now(),
            ]);

            // Log the adjustment (you might want to create an inventory_log table)
            // InventoryLog::create([...]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $inventory->load(['product', 'stockage']),
                'message' => 'Stock adjusted successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Failed to adjust stock',
            ], 500);
        }
    }

    public function transferStock(Request $request, Inventory $inventory)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|numeric|min:1|max:'.$inventory->quantity,
            'destination_stockage_id' => 'required|exists:stockages,id|different:'.$inventory->stockage_id,
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Check if product exists in destination stockage
            $destinationInventory = Inventory::where('product_id', $inventory->product_id)
                ->where('stockage_id', $request->destination_stockage_id)
                ->first();

            if ($destinationInventory) {
                // Add to existing inventory
                $destinationInventory->update([
                    'quantity' => $destinationInventory->quantity + $request->quantity,
                    'updated_at' => now(),
                ]);
            } else {
                // Create new inventory entry
                Inventory::create([
                    'product_id' => $inventory->product_id,
                    'stockage_id' => $request->destination_stockage_id,
                    'quantity' => $request->quantity,
                    'unit' => $inventory->unit,
                    'batch_number' => $inventory->batch_number,
                    'serial_number' => $inventory->serial_number,
                    'purchase_price' => $inventory->purchase_price,
                    'expiry_date' => $inventory->expiry_date,
                    'location' => $inventory->location,
                ]);
            }

            // Reduce quantity from source
            $inventory->update([
                'quantity' => $inventory->quantity - $request->quantity,
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock transferred successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Failed to transfer stock',
            ], 500);
        }
    }

    public function getServiceStock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'required|exists:services,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $perPage = min($request->get('per_page', 10), 100);

        // Build optimized query with database-level pagination
        $query = Inventory::select(
            'id',
            'product_id',
            'stockage_id',
            'quantity',
            'total_units',
            'unit',
            'batch_number',
            'serial_number',
            'purchase_price',
            'barcode',
            'expiry_date',
            'location',
            'created_at',
            'updated_at'
        )
        ->whereHas('stockage', function ($q) use ($request) {
            $q->where('service_id', $request->get('service_id'));
        });

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code_interne', 'like', "%{$search}%");
            });
        }

        // Apply database-level pagination BEFORE loading relationships
        $paginatedInventories = $query->paginate($perPage);

        // Load relationships only for paginated results
        $paginatedInventories->load([
            'product:id,name,code_interne,forme,boite_de',
            'stockage:id,name,type,location'
        ]);

        // Process inventory data
        $data = $paginatedInventories->items();
        $processedData = array_map(fn($inv) => $this->processInventoryData($inv), $data);

        return response()->json([
            'success' => true,
            'data' => $processedData,
            'meta' => [
                'current_page' => $paginatedInventories->currentPage(),
                'last_page' => $paginatedInventories->lastPage(),
                'per_page' => $paginatedInventories->perPage(),
                'total' => $paginatedInventories->total(),
            ],
        ]);
    }

    /**
     * Process individual inventory item with calculated properties
     */
    private function processInventoryData($inventory)
    {
        $expiryDate = $inventory->expiry_date ? \Carbon\Carbon::parse($inventory->expiry_date) : null;
        $now = now();

        // Calculate expiry status
        if (!$expiryDate) {
            $expiryStatus = 'no_expiry';
            $daysUntilExpiry = null;
        } elseif ($expiryDate->isPast()) {
            $expiryStatus = 'expired';
            $daysUntilExpiry = $expiryDate->diffInDays($now) * -1;
        } elseif ($expiryDate->diffInDays($now) <= 30) {
            $expiryStatus = 'expiring_soon';
            $daysUntilExpiry = $expiryDate->diffInDays($now);
        } else {
            $expiryStatus = 'valid';
            $daysUntilExpiry = $expiryDate->diffInDays($now);
        }

        // Calculate low stock alert
        $isLowStock = $inventory->quantity <= 10;

        return [
            'id' => $inventory->id,
            'product_id' => $inventory->product_id,
            'stockage_id' => $inventory->stockage_id,
            'quantity' => $inventory->quantity,
            'total_units' => $inventory->total_units,
            'unit' => $inventory->unit,
            'batch_number' => $inventory->batch_number,
            'serial_number' => $inventory->serial_number,
            'purchase_price' => $inventory->purchase_price,
            'barcode' => $inventory->barcode,
            'expiry_date' => $inventory->expiry_date,
            'location' => $inventory->location,
            'expiry_status' => $expiryStatus,
            'days_until_expiry' => $daysUntilExpiry,
            'is_low_stock' => $isLowStock,
            'product' => $inventory->product,
            'stockage' => $inventory->stockage,
            'created_at' => $inventory->created_at,
            'updated_at' => $inventory->updated_at,
        ];
    }

    /**
     * Calculate inventory alerts for a collection
     */
    private function calculateAlerts($inventories)
    {
        return collect($inventories)->map(function ($inventory) {
            $alerts = [];

            // Expiry alert
            if ($inventory->expiry_date) {
                $expiryDate = \Carbon\Carbon::parse($inventory->expiry_date);
                if ($expiryDate->isPast()) {
                    $alerts[] = [
                        'type' => 'expired',
                        'severity' => 'critical',
                        'message' => 'Product has expired',
                    ];
                } elseif ($expiryDate->diffInDays(now()) <= 30) {
                    $alerts[] = [
                        'type' => 'expiring_soon',
                        'severity' => 'warning',
                        'message' => 'Product expiring within 30 days',
                    ];
                }
            }

            // Low stock alert
            if ($inventory->quantity <= 10) {
                $alerts[] = [
                    'type' => 'low_stock',
                    'severity' => 'warning',
                    'message' => 'Stock level is low',
                ];
            }

            return [
                'inventory_id' => $inventory->id,
                'alerts' => $alerts,
                'has_alerts' => count($alerts) > 0,
            ];
        });
    }

    /**
     * Calculate alert counts for a collection
     */
    private function calculateAlertCounts($inventories)
    {
        $alerts = $this->calculateAlerts($inventories);

        return [
            'expired' => $alerts->filter(fn($a) => collect($a['alerts'])->pluck('type')->contains('expired'))->count(),
            'expiring_soon' => $alerts->filter(fn($a) => collect($a['alerts'])->pluck('type')->contains('expiring_soon'))->count(),
            'low_stock' => $alerts->filter(fn($a) => collect($a['alerts'])->pluck('type')->contains('low_stock'))->count(),
            'total_alerts' => $alerts->filter(fn($a) => $a['has_alerts'])->count(),
        ];
    }
}
