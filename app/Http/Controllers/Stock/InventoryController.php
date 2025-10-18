<?php

namespace App\Http\Controllers\Stock;

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
        $query = Inventory::with(['product', 'stockage']);

        // Filter by stockage
        if ($request->has('stockage_id') && $request->stockage_id) {
            $query->where('stockage_id', $request->stockage_id);
        }

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Pagination
        $perPage = $request->get('per_page', 10);
        $inventory = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $inventory->items(),
            'meta' => [
                'current_page' => $inventory->currentPage(),
                'last_page' => $inventory->lastPage(),
                'per_page' => $inventory->perPage(),
                'total' => $inventory->total(),
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
        $existingInventory = Inventory::where('product_id', $request->product_id)
            ->where('stockage_id', $request->stockage_id)
            ->where('batch_number', $request->batch_number)
            ->where('serial_number', $request->serial_number)
            ->whereRaw('DATE(expiry_date) = ?', [date('Y-m-d', strtotime($request->expiry_date))])
            ->first();

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

            $newTotalUnits = $existingInventory->total_units + $quantityToAdd;

            $existingInventory->update([
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
            // Keep the quantity field as the number of boxes entered
            $data['total_units'] = $request->quantity * $product->boite_de;
        } else {
            // If quantity is individual units, total_units equals quantity
            $data['total_units'] = $request->quantity;
        }

        // Set unit based on product's forme if not provided
        if (empty($data['unit']) && $product) {
            $data['unit'] = $product->forme;
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
            'type' => 'required|in:add,remove',
            'reason' => 'required_if:type,remove|string|max:255',
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

        $query = Inventory::with(['product', 'stockage'])
            ->whereHas('stockage', function ($q) use ($request) {
                $q->where('service_id', $request->get('service_id'));
            });

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Pagination
        $perPage = $request->get('per_page', 10);
        $inventory = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $inventory->items(),
            'meta' => [
                'current_page' => $inventory->currentPage(),
                'last_page' => $inventory->lastPage(),
                'per_page' => $inventory->perPage(),
                'total' => $inventory->total(),
            ],
        ]);
    }
}
