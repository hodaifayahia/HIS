<?php

namespace App\Http\Controllers\Pharmacy;

use App\Models\PharmacyInventory;
use App\Models\PharmacyProduct;
use App\Models\PharmacyStockage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PharmacyInventoryController extends \App\Http\Controllers\Controller
{
    /**
     * Display a listing of the resource.
     * HIGHLY OPTIMIZED: Database-level pagination, minimal eager loading, indexed queries
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $currentPage = $request->get('page', 1);

        // OPTIMIZED: Build query with only necessary columns
        $query = PharmacyInventory::select([
            'id', 'pharmacy_product_id', 'pharmacy_stockage_id', 'quantity', 'unit',
            'batch_number', 'serial_number', 'purchase_price', 'selling_price',
            'expiry_date', 'location', 'barcode', 'created_at', 'updated_at'
        ]);

        // Search functionality - OPTIMIZED with indexed columns first
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                // Search direct indexed columns first (faster)
                $q->where('batch_number', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    // Then search related tables (slower but necessary)
                    ->orWhereHas('pharmacyProduct', function ($productQuery) use ($search) {
                        $productQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by product category
        if ($request->has('category') && !empty($request->category)) {
            $query->whereHas('pharmacyProduct', function ($q) use ($request) {
                $q->where('category', $request->category);
            });
        }

        // Filter by medication type
        if ($request->has('type_medicament') && !empty($request->type_medicament)) {
            $query->whereHas('pharmacyProduct', function ($q) use ($request) {
                $q->where('medication_type', $request->type_medicament);
            });
        }

        // Filter by controlled substances
        if ($request->has('is_controlled')) {
            $query->whereHas('pharmacyProduct', function ($q) use ($request) {
                $q->where('is_controlled_substance', $request->boolean('is_controlled'));
            });
        }

        // Filter by expiry status - OPTIMIZED with indexed column
        if ($request->has('expiry_status') && !empty($request->expiry_status)) {
            $expiryStatus = $request->expiry_status;
            $now = now();
            
            if ($expiryStatus === 'expired') {
                $query->where('expiry_date', '<', $now);
            } elseif ($expiryStatus === 'expiring_soon') {
                $query->whereBetween('expiry_date', [$now, $now->copy()->addDays(60)]);
            } elseif ($expiryStatus === 'valid') {
                $query->where(function ($q) use ($now) {
                    $q->where('expiry_date', '>', $now->copy()->addDays(60))
                        ->orWhereNull('expiry_date');
                });
            }
        }

        // Filter by storage type
        if ($request->has('storage_type') && !empty($request->storage_type)) {
            $query->whereHas('pharmacyStockage', function ($q) use ($request) {
                $q->where('type', $request->storage_type);
            });
        }

        // Filter by temperature controlled
        if ($request->has('temperature_controlled')) {
            $query->whereHas('pharmacyStockage', function ($q) use ($request) {
                $q->where('temperature_controlled', $request->boolean('temperature_controlled'));
            });
        }

        // Filter by stockage ID - OPTIMIZED with indexed column
        if ($request->has('stockage_id') && !empty($request->stockage_id)) {
            $query->where('pharmacy_stockage_id', $request->stockage_id);
        }

        // Filter by low stock - OPTIMIZED with indexed column
        if ($request->has('low_stock') && $request->boolean('low_stock')) {
            $query->where('quantity', '<=', 20);
        }

        // Sort by expiry date for pharmacy priority
        $sortBy = $request->get('sort_by', 'expiry_date');
        $sortOrder = $request->get('sort_order', 'asc');

        if ($sortBy === 'expiry_date') {
            $query->orderByRaw('expiry_date IS NULL, expiry_date '.$sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        // CRITICAL: Paginate at DATABASE level BEFORE eager loading
        $inventory = $query->paginate($perPage, ['*'], 'page', $currentPage);

        // OPTIMIZED: Only load relationships for paginated results
        $inventory->load([
            'pharmacyProduct:id,name,code,category,boite_de,is_controlled_substance,requires_prescription,controlled_substance_schedule,unit_of_measure',
            'pharmacyStockage:id,name,type,service_id,temperature_controlled,security_level'
        ]);

        // OPTIMIZED: Minimal calculations in single pass
        $now = now();
        $inventory->getCollection()->transform(function ($item) use ($now) {
            if ($item->expiry_date) {
                $daysUntil = $now->diffInDays($item->expiry_date, false);
                $item->days_until_expiry = $daysUntil;
                $item->is_expired = $daysUntil < 0;
                $item->is_expiring_soon = $daysUntil <= 60 && $daysUntil > 0;
            }

            // Calculate total units efficiently
            $item->total_units = $item->quantity * ($item->pharmacyProduct->boite_de ?? 1);

            return $item;
        });

        return response()->json([
            'success' => true,
            'data' => $inventory->items(),
            'meta' => [
                'current_page' => $inventory->currentPage(),
                'last_page' => $inventory->lastPage(),
                'per_page' => $inventory->perPage(),
                'total' => $inventory->total(),
                'from' => $inventory->firstItem(),
                'to' => $inventory->lastItem(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pharmacy_product_id' => 'required|exists:pharmacy_products,id',
            'pharmacy_stockage_id' => 'required|exists:pharmacy_stockages,id',
            'quantity' => 'required|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'batch_number' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'purchase_price' => 'nullable|numeric|min:0',
            'expiry_date' => 'nullable|date|after:today',
            'location' => 'nullable|string|max:255',
            'storage_requirements' => 'nullable|string|max:500',
            'manufacturer_lot' => 'nullable|string|max:100',
            'ndc_number' => 'nullable|string|max:50',
            'pharmacist_verified' => 'boolean',
            'quality_check_passed' => 'boolean',
            'temperature_log' => 'nullable|string',
        ], [
            'pharmacy_product_id.exists' => 'The selected pharmacy product does not exist.',
            'pharmacy_stockage_id.exists' => 'The selected pharmacy stockage does not exist. Please select a valid pharmacy stockage.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Validation failed. Please check your input.',
            ], 422);
        }

        // Check if product already exists in this stockage
        // Due to unique constraint on pharmacy_product_id + pharmacy_stockage_id,
        // only one inventory record is allowed per product-stockage combination
        $existingInventory = PharmacyInventory::where('pharmacy_product_id', $request->pharmacy_product_id)
            ->where('pharmacy_stockage_id', $request->pharmacy_stockage_id)
            ->first();

        if ($existingInventory) {
            // Product already exists in this stockage - merge the new quantity
            // Update quantity and keep the most recent batch/expiry information
            $newQuantity = $existingInventory->quantity + $request->quantity;
            
            // If new batch has a later expiry date, update batch information
            $newExpiryDate = $request->expiry_date ? date('Y-m-d', strtotime($request->expiry_date)) : null;
            $currentExpiryDate = $existingInventory->expiry_date ? $existingInventory->expiry_date->format('Y-m-d') : null;
            
            $updateData = ['quantity' => $newQuantity];
            
            // Update batch info if the new batch has a later expiry or if no expiry exists
            if ($newExpiryDate && (!$currentExpiryDate || $newExpiryDate > $currentExpiryDate)) {
                $updateData['batch_number'] = $request->batch_number;
                $updateData['expiry_date'] = $newExpiryDate;
                $updateData['serial_number'] = $request->serial_number;
                
                if ($request->filled('purchase_price')) {
                    $updateData['purchase_price'] = $request->purchase_price;
                }
            }
            
            $existingInventory->update($updateData);

            return response()->json([
                'success' => true,
                'data' => $existingInventory->fresh()->load(['pharmacyProduct', 'pharmacyStockage']),
                'message' => 'Pharmacy product quantity updated successfully. Note: Only one batch per product-stockage combination is allowed.',
                'merged' => true,
            ]);
        }

        // Product doesn't exist with these exact details - create new entry
        $data = $request->all();

        // Ensure expiry_date is stored as date only (without time)
        if (isset($data['expiry_date'])) {
            $data['expiry_date'] = date('Y-m-d', strtotime($data['expiry_date']));
        }

        // Handle quantity calculation based on unit of measure
        $product = PharmacyProduct::find($request->pharmacy_product_id);

        // Set unit based on product's unit_of_measure if not provided
        if (empty($data['unit']) && $product) {
            $data['unit'] = $product->unit_of_measure;
        }

        // Set pharmacy-specific defaults
        $data['pharmacist_verified'] = $request->boolean('pharmacist_verified', false);
        $data['quality_check_passed'] = $request->boolean('quality_check_passed', true);

        // Validate controlled substance requirements
        if ($product && $product->is_controlled_substance) {
            if (empty($data['batch_number'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Batch number is required for controlled substances',
                ], 422);
            }
        }

        $inventory = PharmacyInventory::create($data);

        return response()->json([
            'success' => true,
            'data' => $inventory->load(['pharmacyProduct', 'pharmacyStockage']),
            'message' => 'Pharmacy product added to inventory successfully',
        ], 201);
    }

    public function show(PharmacyInventory $inventory)
    {
        $inventory->load(['pharmacyProduct', 'pharmacyStockage']);

        // Add pharmacy-specific calculations
        if ($inventory->expiry_date) {
            $inventory->days_until_expiry = now()->diffInDays($inventory->expiry_date, false);
            $inventory->is_expired = $inventory->expiry_date->isPast();
            $inventory->is_expiring_soon = $inventory->days_until_expiry <= 60 && $inventory->days_until_expiry > 0;
        }

        return response()->json([
            'success' => true,
            'data' => $inventory,
        ]);
    }

    public function update(Request $request, PharmacyInventory $inventory)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'sometimes|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'batch_number' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'purchase_price' => 'nullable|numeric|min:0',
            'expiry_date' => 'nullable|date',
            'location' => 'nullable|string|max:255',
            'storage_requirements' => 'nullable|string|max:500',
            'manufacturer_lot' => 'nullable|string|max:100',
            'ndc_number' => 'nullable|string|max:50',
            'pharmacist_verified' => 'boolean',
            'quality_check_passed' => 'boolean',
            'temperature_log' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->all();

        // Handle pharmacy-specific validations
        if ($inventory->pharmacyProduct && $inventory->pharmacyProduct->is_controlled_substance) {
            if (isset($data['batch_number']) && empty($data['batch_number'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Batch number cannot be empty for controlled substances',
                ], 422);
            }
        }

        $inventory->update($data);

        return response()->json([
            'success' => true,
            'data' => $inventory->load(['pharmacyProduct', 'pharmacyStockage']),
            'message' => 'Pharmacy inventory updated successfully',
        ]);
    }

    public function destroy(PharmacyInventory $inventory)
    {
        // Additional validation for controlled substances
        if ($inventory->pharmacyProduct && $inventory->pharmacyProduct->is_controlled_substance) {
            return response()->json([
                'success' => false,
                'message' => 'Controlled substances cannot be deleted without proper authorization',
            ], 403);
        }

        $inventory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pharmacy inventory item removed successfully',
        ]);
    }

    public function adjustStock(Request $request, PharmacyInventory $inventory)
    {
        $validator = Validator::make($request->all(), [
            'adjustment_type' => 'required|in:increase,decrease',
            'quantity' => 'required|numeric',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $adjustmentType = $request->adjustment_type;
        $quantity = $request->quantity;
        $reason = $request->reason;
        $notes = $request->notes;

        // Calculate new quantity
        $newQuantity = $adjustmentType === 'increase'
            ? $inventory->quantity + $quantity
            : $inventory->quantity - $quantity;

        if ($newQuantity < 0) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock for this adjustment',
            ], 422);
        }

        // Additional validation for controlled substances
        if ($inventory->pharmacyProduct && $inventory->pharmacyProduct->is_controlled_substance) {
            if (empty($reason)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reason is required for controlled substance adjustments',
                ], 422);
            }
        }

        $oldQuantity = $inventory->quantity;
        $inventory->update(['quantity' => $newQuantity]);

        // Log the adjustment (you might want to create a separate model for this)
        Log::info('Pharmacy stock adjustment', [
            'inventory_id' => $inventory->id,
            'product_name' => $inventory->pharmacyProduct->name ?? 'Unknown',
            'old_quantity' => $oldQuantity,
            'new_quantity' => $newQuantity,
            'adjustment_type' => $adjustmentType,
            'quantity_changed' => $quantity,
            'reason' => $reason,
            'notes' => $notes,
            'user_id' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $inventory->fresh(['pharmacyProduct', 'pharmacyStockage']),
            'message' => 'Stock adjusted successfully',
        ]);
    }

    public function transferStock(Request $request, PharmacyInventory $inventory)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|numeric|min:1|max:'.$inventory->quantity,
            'destination_stockage_id' => 'required|exists:pharmacy_stockages,id|different:'.$inventory->pharmacy_stockage_id,
            'notes' => 'nullable|string|max:500',
            'pharmacist_license' => 'nullable|string|max:50',
            'transfer_reason' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Validate destination storage for controlled substances
        if ($inventory->pharmacyProduct && $inventory->pharmacyProduct->is_controlled_substance) {
            $destinationStorage = PharmacyStockage::find($request->destination_stockage_id);
            if (! $destinationStorage || $destinationStorage->security_level < 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Controlled substances require high-security storage',
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            // Check if product exists in destination stockage with same batch
            $destinationInventory = PharmacyInventory::where('pharmacy_product_id', $inventory->pharmacy_product_id)
                ->where('pharmacy_stockage_id', $request->destination_stockage_id)
                ->where('batch_number', $inventory->batch_number)
                ->where('expiry_date', $inventory->expiry_date)
                ->first();

            if ($destinationInventory) {
                // Add to existing inventory
                $destinationInventory->update([
                    'quantity' => $destinationInventory->quantity + $request->quantity,
                    'updated_at' => now(),
                ]);
            } else {
                // Create new inventory entry
                PharmacyInventory::create([
                    'pharmacy_product_id' => $inventory->pharmacy_product_id,
                    'pharmacy_stockage_id' => $request->destination_stockage_id,
                    'quantity' => $request->quantity,
                    'unit' => $inventory->unit,
                    'batch_number' => $inventory->batch_number,
                    'serial_number' => $inventory->serial_number,
                    'purchase_price' => $inventory->purchase_price,
                    'expiry_date' => $inventory->expiry_date,
                    'location' => $inventory->location,
                    'storage_requirements' => $inventory->storage_requirements,
                    'manufacturer_lot' => $inventory->manufacturer_lot,
                    'ndc_number' => $inventory->ndc_number,
                    'pharmacist_verified' => true, // Verified during transfer
                    'quality_check_passed' => $inventory->quality_check_passed,
                ]);
            }

            // Reduce quantity from source
            $inventory->update([
                'quantity' => $inventory->quantity - $request->quantity,
                'updated_at' => now(),
            ]);

            // Log the transfer for pharmacy compliance
            Log::info('Pharmacy Stock Transfer', [
                'source_inventory_id' => $inventory->id,
                'destination_stockage_id' => $request->destination_stockage_id,
                'product_id' => $inventory->pharmacy_product_id,
                'quantity' => $request->quantity,
                'pharmacist_license' => $request->pharmacist_license,
                'transfer_reason' => $request->transfer_reason,
                'user_id' => Auth::id(),
                'timestamp' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pharmacy stock transferred successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Failed to transfer pharmacy stock: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get service stock - HIGHLY OPTIMIZED
     */
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

        $perPage = $request->get('per_page', 10);
        $serviceId = $request->get('service_id');

        // OPTIMIZED: Select only necessary columns
        $query = PharmacyInventory::select([
            'id', 'pharmacy_product_id', 'pharmacy_stockage_id', 'quantity', 
            'unit', 'batch_number', 'expiry_date', 'purchase_price', 'created_at'
        ])->whereHas('pharmacyStockage', function ($q) use ($serviceId) {
            $q->where('service_id', $serviceId);
        });

        // Search functionality - indexed columns first
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('batch_number', 'like', "%{$search}%")
                    ->orWhereHas('pharmacyProduct', function ($productQuery) use ($search) {
                        $productQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('brand_name', 'like', "%{$search}%")
                            ->orWhere('generic_name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by expiry status - OPTIMIZED
        if ($request->has('expiry_filter') && !empty($request->expiry_filter)) {
            $expiryFilter = $request->expiry_filter;
            $now = now();
            
            if ($expiryFilter === 'expired') {
                $query->where('expiry_date', '<', $now);
            } elseif ($expiryFilter === 'expiring_soon') {
                $query->whereBetween('expiry_date', [$now, $now->copy()->addDays(60)]);
            }
        }

        // CRITICAL: Paginate before loading relationships
        $inventory = $query->paginate($perPage);

        // Load relationships only for paginated results
        $inventory->load([
            'pharmacyProduct:id,name,brand_name,generic_name,requires_prescription,is_controlled_substance',
            'pharmacyStockage:id,name,service_id'
        ]);

        // OPTIMIZED: Single pass transformation
        $now = now();
        $inventory->getCollection()->transform(function ($item) use ($now) {
            if ($item->expiry_date) {
                $daysUntil = $now->diffInDays($item->expiry_date, false);
                $item->days_until_expiry = $daysUntil;
                $item->is_expired = $daysUntil < 0;
                $item->is_expiring_soon = $daysUntil <= 60 && $daysUntil > 0;
            }

            $item->requires_prescription = $item->pharmacyProduct->requires_prescription ?? false;
            $item->is_controlled_substance = $item->pharmacyProduct->is_controlled_substance ?? false;

            // Map to consistent naming for frontend
            $item->product = $item->pharmacyProduct;
            $item->stockage = $item->pharmacyStockage;
            $item->stockage_id = $item->pharmacy_stockage_id;
            
            return $item;
        });

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

    /**
     * Get expiring medications for pharmacy alerts - HIGHLY OPTIMIZED
     */
    public function getExpiringMedications(Request $request)
    {
        $days = $request->get('days', 60);
        $perPage = $request->get('per_page', 50); // Limit results for performance
        $now = now();

        // OPTIMIZED: Select only necessary columns, use indexed expiry_date
        $query = PharmacyInventory::select([
            'id', 'pharmacy_product_id', 'pharmacy_stockage_id', 
            'quantity', 'batch_number', 'expiry_date', 'created_at'
        ])
        ->whereBetween('expiry_date', [$now, $now->copy()->addDays($days)])
        ->orderBy('expiry_date', 'asc');

        if ($request->has('service_id') && !empty($request->service_id)) {
            $query->whereHas('pharmacyStockage', function ($q) use ($request) {
                $q->where('service_id', $request->service_id);
            });
        }

        // OPTIMIZED: Paginate or limit results
        if ($request->boolean('paginate', false)) {
            $expiringMedications = $query->paginate($perPage);
            
            // Load relationships only for paginated results
            $expiringMedications->load([
                'pharmacyProduct:id,name,generic_name,category',
                'pharmacyStockage:id,name,service_id'
            ]);

            $expiringMedications->getCollection()->transform(function ($item) use ($now) {
                $item->days_until_expiry = $now->diffInDays($item->expiry_date, false);
                return $item;
            });

            return response()->json([
                'success' => true,
                'data' => $expiringMedications->items(),
                'meta' => [
                    'current_page' => $expiringMedications->currentPage(),
                    'total' => $expiringMedications->total(),
                ],
            ]);
        } else {
            // Limit to prevent memory issues
            $expiringMedications = $query->limit(100)->get();
            
            $expiringMedications->load([
                'pharmacyProduct:id,name,generic_name,category',
                'pharmacyStockage:id,name,service_id'
            ]);

            $expiringMedications->transform(function ($item) use ($now) {
                $item->days_until_expiry = $now->diffInDays($item->expiry_date, false);
                return $item;
            });

            return response()->json([
                'success' => true,
                'data' => $expiringMedications,
                'total_count' => $expiringMedications->count(),
            ]);
        }
    }

    /**
     * Get controlled substances inventory - HIGHLY OPTIMIZED
     */
    public function getControlledSubstances(Request $request)
    {
        $perPage = $request->get('per_page', 50);

        // OPTIMIZED: Select only necessary columns
        $query = PharmacyInventory::select([
            'id', 'pharmacy_product_id', 'pharmacy_stockage_id',
            'quantity', 'batch_number', 'expiry_date', 'updated_at'
        ])
        ->whereHas('pharmacyProduct', function ($q) {
            $q->where('is_controlled_substance', true);
        })
        ->orderBy('updated_at', 'desc');

        if ($request->has('service_id') && !empty($request->service_id)) {
            $query->whereHas('pharmacyStockage', function ($q) use ($request) {
                $q->where('service_id', $request->service_id);
            });
        }

        // OPTIMIZED: Always paginate for controlled substances
        $controlledSubstances = $query->paginate($perPage);

        // Load relationships only for paginated results
        $controlledSubstances->load([
            'pharmacyProduct:id,name,generic_name,controlled_substance_schedule,requires_prescription',
            'pharmacyStockage:id,name,service_id,security_level'
        ]);

        return response()->json([
            'success' => true,
            'data' => $controlledSubstances->items(),
            'meta' => [
                'current_page' => $controlledSubstances->currentPage(),
                'last_page' => $controlledSubstances->lastPage(),
                'total' => $controlledSubstances->total(),
            ],
        ]);
    }

    /**
     * Bulk update pharmacy inventory items
     */
    public function bulkUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:pharmacy_inventories,id',
            'items.*.quantity' => 'sometimes|numeric|min:0',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.batch_number' => 'nullable|string|max:100',
            'items.*.serial_number' => 'nullable|string|max:100',
            'items.*.purchase_price' => 'nullable|numeric|min:0',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.location' => 'nullable|string|max:255',
            'items.*.storage_requirements' => 'nullable|string|max:500',
            'items.*.manufacturer_lot' => 'nullable|string|max:100',
            'items.*.ndc_number' => 'nullable|string|max:50',
            'items.*.pharmacist_verified' => 'boolean',
            'items.*.quality_check_passed' => 'boolean',
            'items.*.temperature_log' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $updatedItems = [];
            $errors = [];

            foreach ($request->items as $itemData) {
                $inventory = PharmacyInventory::find($itemData['id']);

                if (! $inventory) {
                    $errors[] = "Inventory item with ID {$itemData['id']} not found";

                    continue;
                }

                // Additional validation for controlled substances
                if ($inventory->pharmacyProduct && $inventory->pharmacyProduct->is_controlled_substance) {
                    if (isset($itemData['batch_number']) && empty($itemData['batch_number'])) {
                        $errors[] = "Batch number cannot be empty for controlled substance: {$inventory->pharmacyProduct->name}";

                        continue;
                    }
                }

                // Remove the ID from the data to avoid updating it
                $updateData = collect($itemData)->except(['id'])->toArray();

                $inventory->update($updateData);
                $updatedItems[] = $inventory->load(['pharmacyProduct', 'pharmacyStockage']);

                // Log the bulk update for controlled substances
                if ($inventory->pharmacyProduct && $inventory->pharmacyProduct->is_controlled_substance) {
                    Log::info('Pharmacy bulk update - controlled substance', [
                        'inventory_id' => $inventory->id,
                        'product_name' => $inventory->pharmacyProduct->name,
                        'updated_fields' => array_keys($updateData),
                        'user_id' => Auth::id(),
                    ]);
                }
            }

            if (! empty($errors)) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Bulk update failed',
                    'errors' => $errors,
                ], 422);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $updatedItems,
                'message' => 'Bulk update completed successfully',
                'updated_count' => count($updatedItems),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Pharmacy bulk update failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Bulk update failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get inventory summary - HIGHLY OPTIMIZED with caching
     */
    public function getInventorySummary(Request $request)
    {
        try {
            $cacheKey = 'pharmacy_inventory_summary_' . auth()->id();
            
            // Cache for 10 minutes
            $inventorySummary = \Cache::remember($cacheKey, 600, function () {
                return PharmacyInventory::select(
                    'pharmacy_product_id',
                    DB::raw('SUM(quantity) as total_quantity'),
                    DB::raw('COUNT(*) as location_count'),
                    DB::raw('AVG(purchase_price) as avg_price')
                )
                ->groupBy('pharmacy_product_id')
                ->having('total_quantity', '>', 0) // Only products in stock
                ->orderBy('total_quantity', 'desc')
                ->limit(1000) // Prevent memory issues
                ->get();
            });

            // Load product details only for the summary results
            $productIds = $inventorySummary->pluck('pharmacy_product_id')->toArray();
            $products = PharmacyProduct::select('id', 'name', 'brand_name', 'generic_name', 'category')
                ->whereIn('id', $productIds)
                ->get()
                ->keyBy('id');

            $inventorySummary->transform(function ($item) use ($products) {
                $item->product = $products->get($item->pharmacy_product_id);
                return $item;
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Inventory summary retrieved successfully',
                'data' => $inventorySummary,
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving inventory summary: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve inventory summary',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get expiring items - HIGHLY OPTIMIZED
     */
    public function getExpiringItems(Request $request)
    {
        try {
            $thresholdDays = $request->query('threshold_days', 90);
            $perPage = $request->query('per_page', 50);
            $now = now();

            // OPTIMIZED: Use indexed expiry_date column, limit results
            $query = PharmacyInventory::select([
                'id', 'pharmacy_product_id', 'pharmacy_stockage_id',
                'quantity', 'batch_number', 'expiry_date'
            ])
            ->where('expiry_date', '<=', $now->copy()->addDays($thresholdDays))
            ->where('expiry_date', '>=', $now)
            ->where('quantity', '>', 0) // Only items in stock
            ->orderBy('expiry_date', 'asc');

            // Paginate for better performance
            $expiringItems = $query->paginate($perPage);

            // Load relationships only for paginated results
            $expiringItems->load([
                'pharmacyProduct:id,name,brand_name,generic_name,category',
                'pharmacyStockage:id,name'
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Expiring items retrieved successfully',
                'data' => $expiringItems->items(),
                'meta' => [
                    'current_page' => $expiringItems->currentPage(),
                    'total' => $expiringItems->total(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving expiring items: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve expiring items',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getAvailable(Request $request)
    {
        try {
            $query = PharmacyInventory::where('quantity', '>', 0)
                ->where('status', 'available')
                ->with(['pharmacyProduct', 'pharmacyStockage']);

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('batch_number', 'like', "%{$search}%")
                        ->orWhereHas('pharmacyProduct', function ($productQuery) use ($search) {
                            $productQuery->where('name', 'like', "%{$search}%");
                        });
                });
            }

            $availableItems = $query->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Available items retrieved successfully',
                'data' => $availableItems,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve available items',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getByProduct($productId)
    {
        try {
            $inventory = PharmacyInventory::where('pharmacy_product_id', $productId)
                ->with(['pharmacyProduct', 'pharmacyStockage'])
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Product inventory retrieved successfully',
                'data' => $inventory,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve product inventory',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get inventory by stockage - HIGHLY OPTIMIZED
     * Uses database-level pagination and indexed queries
     */
    public function getByStockage(Request $request, $stockageId)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $currentPage = $request->get('page', 1);

            // OPTIMIZED: Build query with only necessary columns and indexed filter
            $query = PharmacyInventory::select([
                'id', 'pharmacy_product_id', 'pharmacy_stockage_id', 'quantity', 'unit',
                'batch_number', 'serial_number', 'purchase_price', 'selling_price',
                'expiry_date', 'location', 'barcode', 'created_at', 'updated_at'
            ])->where('pharmacy_stockage_id', $stockageId); // INDEXED column

            // OPTIMIZED: Search with indexed columns first
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    // Search indexed columns first (much faster)
                    $q->where('batch_number', 'like', "%{$search}%")
                        ->orWhere('serial_number', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%")
                        // Then search related product
                        ->orWhereHas('pharmacyProduct', function ($productQuery) use ($search) {
                            $productQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%");
                        });
                });
            }

            // Filter by category
            if ($request->has('category') && !empty($request->category)) {
                $query->whereHas('pharmacyProduct', function ($q) use ($request) {
                    $q->where('category', $request->category);
                });
            }

            // Filter by low stock - INDEXED column
            if ($request->has('low_stock') && $request->boolean('low_stock')) {
                $query->where('quantity', '<=', 20);
            }

            // Filter by expiry status - INDEXED column
            if ($request->has('expiry_status') && !empty($request->expiry_status)) {
                $expiryStatus = $request->expiry_status;
                $now = now();
                
                if ($expiryStatus === 'expired') {
                    $query->where('expiry_date', '<', $now);
                } elseif ($expiryStatus === 'expiring_soon') {
                    $query->whereBetween('expiry_date', [$now, $now->copy()->addDays(60)]);
                } elseif ($expiryStatus === 'valid') {
                    $query->where(function ($q) use ($now) {
                        $q->where('expiry_date', '>', $now->copy()->addDays(60))
                            ->orWhereNull('expiry_date');
                    });
                }
            }

            // OPTIMIZED: Sort with indexed columns
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            if ($sortBy === 'expiry_date') {
                $query->orderByRaw('expiry_date IS NULL, expiry_date ' . $sortOrder);
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }

            // CRITICAL: Paginate BEFORE loading relationships
            $inventory = $query->paginate($perPage, ['*'], 'page', $currentPage);

            // OPTIMIZED: Only load relationships for paginated results
            $inventory->load([
                'pharmacyProduct:id,name,code,category,boite_de,is_controlled_substance,requires_prescription,unit_of_measure',
                'pharmacyStockage:id,name,type,service_id,temperature_controlled'
            ]);

            // OPTIMIZED: Calculate metrics in single pass
            $now = now();
            $inventory->getCollection()->transform(function ($item) use ($now) {
                // Expiry calculations
                if ($item->expiry_date) {
                    $daysUntil = $now->diffInDays($item->expiry_date, false);
                    $item->days_until_expiry = $daysUntil;
                    $item->is_expired = $daysUntil < 0;
                    $item->is_expiring_soon = $daysUntil <= 60 && $daysUntil > 0;
                }

                // Calculate total units
                $item->total_units = $item->quantity * ($item->pharmacyProduct->boite_de ?? 1);

                // Stock alert
                $item->is_low_stock = $item->quantity <= 20;

                return $item;
            });

            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => 'Stockage inventory retrieved successfully',
                'data' => $inventory->items(),
                'meta' => [
                    'current_page' => $inventory->currentPage(),
                    'last_page' => $inventory->lastPage(),
                    'per_page' => $inventory->perPage(),
                    'total' => $inventory->total(),
                    'from' => $inventory->firstItem(),
                    'to' => $inventory->lastItem(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve stockage inventory', [
                'stockage_id' => $stockageId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Failed to retrieve stockage inventory',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getCriticalStock(Request $request)
    {
        try {
            $criticalItems = PharmacyInventory::whereRaw('quantity <= min_stock_level')
                ->with(['pharmacyProduct', 'pharmacyStockage'])
                ->orderBy('quantity', 'asc')
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Critical stock items retrieved successfully',
                'data' => $criticalItems,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve critical stock items',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getExpired(Request $request)
    {
        try {
            $expiredItems = PharmacyInventory::where('expiry_date', '<', now())
                ->with(['pharmacyProduct', 'pharmacyStockage'])
                ->orderBy('expiry_date', 'asc')
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Expired items retrieved successfully',
                'data' => $expiredItems,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve expired items',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getLowStock(Request $request)
    {
        try {
            $lowStockItems = PharmacyInventory::whereRaw('quantity <= min_stock_level * 1.2')
                ->whereRaw('quantity > min_stock_level')
                ->with(['pharmacyProduct', 'pharmacyStockage'])
                ->orderBy('quantity', 'asc')
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Low stock items retrieved successfully',
                'data' => $lowStockItems,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve low stock items',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function generateBarcode(Request $request, PharmacyInventory $inventory)
    {
        try {
            // Generate barcode logic here - you may want to use a barcode library
            $barcodeData = [
                'id' => $inventory->id,
                'product_name' => $inventory->pharmacyProduct->name,
                'batch_number' => $inventory->batch_number,
                'expiry_date' => $inventory->expiry_date,
                'barcode' => 'PH'.str_pad($inventory->id, 8, '0', STR_PAD_LEFT),
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Barcode generated successfully',
                'data' => $barcodeData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate barcode',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
