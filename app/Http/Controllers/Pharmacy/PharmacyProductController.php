<?php

namespace App\Http\Controllers\Pharmacy;

use App\Models\PharmacyInventory;
use App\Models\PharmacyProduct;
use App\Models\PharmacyProductGlobalSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PharmacyProductController extends \App\Http\Controllers\Controller
{
    /**
     * Display a listing of the resource.
     * HIGHLY OPTIMIZED: Database-level pagination, minimal eager loading, cached aggregations
     */
    public function index(Request $request)
    {
        $quantityByBox = $request->boolean('quantity_by_box', false);
        $user = auth()->user();
        
        // OPTIMIZED: Limit per_page to prevent performance issues
        $requestedPerPage = $request->get('per_page', 10);
        $perPage = min($requestedPerPage, 100); // Maximum 100 items per page
        $currentPage = $request->get('page', 1);

        // OPTIMIZED: Select only necessary columns
        $query = PharmacyProduct::select([
            'id', 'name', 'code', 'category', 'medication_type', 'boite_de',
            'is_controlled_substance', 'requires_prescription', 'minimum_stock_level',
            'critical_stock_level', 'units_per_package', 'unit_of_measure',
            'dosage_form', 'is_active', 'created_at'
        ]);

        // Filter by user's assigned services (only admin/SuperAdmin see all)
        if ($user && ! $user->hasRole(['admin', 'SuperAdmin'])) {
            $userServices = $user->services ?? [];
            if (! empty($userServices)) {
                $query->whereHas('inventories.stockage', function ($q) use ($userServices) {
                    $q->whereIn('service_id', $userServices);
                });
            }
        }

        // OPTIMIZED: Simplified search - use indexes
        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->has('category') && ! empty($request->category)) {
            $query->where('category', $request->category);
        }

        // Filter by medication type
        if ($request->has('medication_type') && ! empty($request->medication_type)) {
            $query->where('medication_type', $request->medication_type);
        }

        // Filter by controlled substance
        if ($request->has('is_controlled')) {
            $query->where('is_controlled_substance', $request->boolean('is_controlled'));
        }

        // Filter by prescription requirement
        if ($request->has('requires_prescription')) {
            $query->where('requires_prescription', $request->boolean('requires_prescription'));
        }

        // CRITICAL: Paginate at DATABASE level BEFORE processing
        $paginatedProducts = $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $currentPage);

        // OPTIMIZED: Only load inventories for paginated products
        $paginatedProducts->load(['inventories' => function ($q) {
            $q->select('id', 'pharmacy_product_id', 'pharmacy_stockage_id', 'quantity', 'unit', 'expiry_date')
                ->where('quantity', '>', 0);
        }]);

        // Process only the paginated products
        $processedProducts = $paginatedProducts->map(function ($product) use ($quantityByBox) {
            return $this->processProductData($product, $quantityByBox);
        });

        // Calculate alert counts efficiently (only for current page)
        $alertCounts = $this->calculateAlertCounts($processedProducts);

        // Filter by alert types if provided (after pagination)
        if ($request->has('alert_filters') && ! empty($request->alert_filters)) {
            $alertFilters = is_string($request->alert_filters) 
                ? json_decode($request->alert_filters, true) 
                : $request->alert_filters;

            if (is_array($alertFilters) && ! empty($alertFilters)) {
                $processedProducts = $processedProducts->filter(function ($product) use ($alertFilters) {
                    if (empty($product->alerts)) return false;
                    return collect($alertFilters)->contains(function ($filter) use ($product) {
                        return collect($product->alerts)->contains('type', $filter);
                    });
                });
            }
        }

        return response()->json([
            'success' => true,
            'data' => $processedProducts->values(),
            'meta' => [
                'current_page' => $paginatedProducts->currentPage(),
                'last_page' => $paginatedProducts->lastPage(),
                'per_page' => $paginatedProducts->perPage(),
                'total' => $paginatedProducts->total(),
                'from' => $paginatedProducts->firstItem(),
                'to' => $paginatedProducts->lastItem(),
            ],
            'alert_counts' => $alertCounts,
            'quantity_by_box' => $quantityByBox,
        ]);
    }

    /**
     * Process individual product data (extracted for reusability and clarity)
     */
    private function processProductData($product, $quantityByBox)
    {
        $inventories = $product->inventories;
        $unitsPerPackage = $product->units_per_package ?? 1;

        // Calculate total quantity
        if ($quantityByBox && $unitsPerPackage > 1) {
            $totalQuantity = $inventories->sum('quantity');
            $totalUnits = $totalQuantity * $unitsPerPackage;
            $displayUnit = 'packages';
            $inventoryDisplay = "{$totalQuantity} packages ({$totalUnits} units)";
        } else {
            $totalQuantity = $inventories->sum(function ($inventory) use ($unitsPerPackage) {
                return $inventory->quantity * $unitsPerPackage;
            });
            $totalPackages = $unitsPerPackage > 1 ? floor($totalQuantity / $unitsPerPackage) : 0;
            $displayUnit = $product->dosage_form ?? 'units';
            $inventoryDisplay = $unitsPerPackage > 1 
                ? "{$totalPackages} packages × {$unitsPerPackage} = {$totalQuantity} units"
                : "{$totalQuantity} units";
        }

        // Simplified inventory by location (lazy load stockage only if needed)
        $inventoryByLocation = [];
        if ($inventories->isNotEmpty() && $inventories->count() <= 10) {
            // Only process location details for products with reasonable inventory count
            $inventories->load('pharmacyStockage.service');
            $inventoryByLocation = $inventories->groupBy(function ($inventory) {
                return $inventory->pharmacyStockage->service->name ?? 
                       $inventory->pharmacyStockage->name ?? 
                       'Unknown';
            })->map(function ($locationInventories) use ($unitsPerPackage, $quantityByBox) {
                $locationQuantity = $locationInventories->sum('quantity');
                $locationUnits = $locationQuantity * $unitsPerPackage;
                
                return [
                    'location' => $locationInventories->first()->pharmacyStockage->service->name ?? 'Unknown',
                    'packages' => $locationQuantity,
                    'units' => $locationUnits,
                    'display' => $quantityByBox && $unitsPerPackage > 1
                        ? "{$locationQuantity} packages ({$locationUnits} units)"
                        : "{$locationUnits} units",
                ];
            })->values();
        }

        // Calculate alerts efficiently
        $alerts = $this->calculateProductAlerts($product, $inventories, $totalQuantity, $displayUnit, $quantityByBox);

        // Get primary unit
        $primaryUnit = $inventories->first()->unit ?? 'units';

        // Add computed properties
        $product->alerts = $alerts;
        $product->total_quantity = $totalQuantity;
        $product->display_unit = $displayUnit;
        $product->quantity_by_box = $quantityByBox;
        $product->unit = $primaryUnit;
        $product->inventory_display = $inventoryDisplay;
        $product->inventory_by_location = $inventoryByLocation;
        $product->units_per_package = $unitsPerPackage;
        $product->has_package_units = $unitsPerPackage > 1;

        return $product;
    }

    /**
     * Calculate alerts for a single product
     */
    private function calculateProductAlerts($product, $inventories, $totalQuantity, $displayUnit, $quantityByBox)
    {
        $alerts = [];
        $lowStockThreshold = $product->minimum_stock_level ?? 20;
        $criticalStockThreshold = $product->critical_stock_level ?? 10;
        $expiryAlertDays = 60;

        // Adjust thresholds based on quantity mode
        if ($quantityByBox && $product->units_per_package) {
            $lowStockThreshold = ceil($lowStockThreshold / $product->units_per_package);
            $criticalStockThreshold = ceil($criticalStockThreshold / $product->units_per_package);
        }

        // Stock alerts
        if ($totalQuantity <= $criticalStockThreshold) {
            $alerts[] = [
                'type' => 'critical_stock',
                'severity' => 'danger',
                'message' => "Critical Stock: {$totalQuantity} {$displayUnit} remaining",
                'icon' => 'pi pi-times-circle',
            ];
        } elseif ($totalQuantity <= $lowStockThreshold) {
            $alerts[] = [
                'type' => 'low_stock',
                'severity' => 'warning',
                'message' => "Low Stock: {$totalQuantity} {$displayUnit} remaining",
                'icon' => 'pi pi-exclamation-triangle',
            ];
        }

        // Expiry alerts (optimized with single pass)
        $now = now();
        $expiringCount = 0;
        $expiredCount = 0;

        foreach ($inventories as $inventory) {
            if ($inventory->expiry_date) {
                if ($inventory->expiry_date->isPast()) {
                    $expiredCount++;
                } else {
                    $daysUntilExpiry = $now->diffInDays($inventory->expiry_date, false);
                    if ($daysUntilExpiry <= $expiryAlertDays) {
                        $expiringCount++;
                    }
                }
            }
        }

        if ($expiredCount > 0) {
            $alerts[] = [
                'type' => 'expired',
                'severity' => 'danger',
                'message' => "Expired: {$expiredCount} batches",
                'icon' => 'pi pi-ban',
            ];
        }

        if ($expiringCount > 0) {
            $alerts[] = [
                'type' => 'expiring',
                'severity' => 'warning',
                'message' => "Expiring Soon: {$expiringCount} batches",
                'icon' => 'pi pi-clock',
            ];
        }

        // Controlled substance alert
        if ($product->is_controlled_substance) {
            $alerts[] = [
                'type' => 'controlled_substance',
                'severity' => 'info',
                'message' => 'Controlled Substance - Special Handling Required',
                'icon' => 'pi pi-shield',
            ];
        }

        return $alerts;
    }

    /**
     * Calculate alert counts efficiently
     */
    private function calculateAlertCounts($products)
    {
        $counts = [
            'low_stock' => 0,
            'critical_stock' => 0,
            'expiring' => 0,
            'expired' => 0,
            'controlled_substance' => 0,
        ];

        foreach ($products as $product) {
            if (empty($product->alerts)) continue;
            
            foreach ($product->alerts as $alert) {
                $type = $alert['type'];
                if (isset($counts[$type])) {
                    $counts[$type]++;
                }
            }
        }

        return $counts;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => ['required', Rule::in(['Medication', 'Medical Supplies', 'Equipment', 'Controlled Substances', 'Others'])],
            'is_clinical' => 'boolean',
            'is_controlled_substance' => 'boolean',
            'controlled_substance_schedule' => 'nullable|string|max:10',
            'code_interne' => 'nullable|integer',
            'code_pch' => 'nullable|string|max:255',
            'designation' => 'nullable|string|max:255',
            'type_medicament' => 'nullable|string|max:255',
            'forme' => 'nullable|string|max:255',
            'boite_de' => 'nullable|integer',
            'nom_commercial' => 'nullable|string|max:255',
            'dosage_strength' => 'nullable|string|max:100',
            'active_ingredient' => 'nullable|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'requires_prescription' => 'boolean',
            'storage_requirements' => 'nullable|string',
            'status' => ['nullable', Rule::in(['In Stock', 'Low Stock', 'Out of Stock', 'Discontinued'])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->all();

        // Set pharmacy-specific defaults
        $data['is_clinical'] = $request->boolean('is_clinical', false);
        $data['is_controlled_substance'] = $request->boolean('is_controlled_substance', false);
        $data['requires_prescription'] = $request->boolean('requires_prescription', false);

        // Set default status if not provided
        if (! isset($data['status'])) {
            $data['status'] = 'In Stock';
        }

        $product = PharmacyProduct::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Pharmacy product created successfully',
            'data' => $product,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(PharmacyProduct $product)
    {
        $product->load(['inventories.stockage.storage']);

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PharmacyProduct $product)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => ['required', Rule::in(['Medication', 'Medical Supplies', 'Equipment', 'Controlled Substances', 'Others'])],
            'is_clinical' => 'boolean',
            'is_controlled_substance' => 'boolean',
            'controlled_substance_schedule' => 'nullable|string|max:10',
            'code_interne' => 'nullable|integer',
            'code_pch' => 'nullable|string|max:255',
            'designation' => 'nullable|string|max:255',
            'type_medicament' => 'nullable|string|max:255',
            'forme' => 'nullable|string|max:255',
            'boite_de' => 'nullable|integer',
            'nom_commercial' => 'nullable|string|max:255',
            'dosage_strength' => 'nullable|string|max:100',
            'active_ingredient' => 'nullable|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'requires_prescription' => 'boolean',
            'storage_requirements' => 'nullable|string',
            'status' => ['nullable', Rule::in(['In Stock', 'Low Stock', 'Out of Stock', 'Discontinued'])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->all();

        // Set pharmacy-specific defaults
        $data['is_clinical'] = $request->boolean('is_clinical', false);
        $data['is_controlled_substance'] = $request->boolean('is_controlled_substance', false);
        $data['requires_prescription'] = $request->boolean('requires_prescription', false);

        $product->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Pharmacy product updated successfully',
            'data' => $product,
        ]);
    }

    /**
     * Bulk delete products.
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:pharmacy_products,id',
        ]);

        $ids = $request->ids;

        try {
            // Start a database transaction
            \DB::beginTransaction();

            // Get all inventory IDs for the products to be deleted
            $inventoryIds = PharmacyInventory::whereIn('product_id', $ids)->pluck('id')->toArray();

            // First, delete pharmacy movement inventory selections that reference these inventories
            if (! empty($inventoryIds)) {
                \DB::table('pharmacy_movement_inventory_selections')
                    ->whereIn('inventory_id', $inventoryIds)
                    ->delete();
            }

            // Then, delete all related inventory records
            PharmacyInventory::whereIn('product_id', $ids)->delete();

            // Finally, delete the products
            $deletedCount = PharmacyProduct::whereIn('id', $ids)->delete();

            // Commit the transaction
            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$deletedCount} pharmacy products deleted successfully",
                'deleted_count' => $deletedCount,
            ]);

        } catch (\Exception $e) {
            // Rollback the transaction on error
            \DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete pharmacy products: '.$e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get detailed information about a product including stock stats and locations.
     */
    public function getDetails($id, Request $request)
    {
        $quantityByBox = $request->boolean('quantity_by_box', false);

        $product = PharmacyProduct::with(['inventories.stockage.storage'])->find($id);

        if (! $product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        // Calculate stats based on quantity mode
        if ($quantityByBox && $product->units_per_package) {
            // Calculate by packages
            $totalQuantity = $product->inventories->sum('quantity');
            $displayUnit = 'packages';
            $conversionFactor = $product->units_per_package;
        } else {
            // Calculate by units
            $totalQuantity = $product->inventories->sum(function ($inventory) use ($product) {
                return $inventory->quantity * ($product->units_per_package ?? 1);
            });
            $displayUnit = $product->dosage_form ?? 'units';
            $conversionFactor = 1;
        }

        $locations = $product->inventories->map(function ($inventory) use ($product, $quantityByBox) {
            if ($quantityByBox && $product->units_per_package) {
                $displayQuantity = $inventory->quantity;
                $actualQuantity = $inventory->quantity * $product->units_per_package;
            } else {
                $displayQuantity = $inventory->quantity * ($product->units_per_package ?? 1);
                $actualQuantity = $inventory->quantity * ($product->units_per_package ?? 1);
            }

            return [
                'location' => $inventory->stockage->storage->name ?? 'Unknown',
                'quantity' => $displayQuantity,
                'actual_quantity' => $actualQuantity,
                'package_quantity' => $inventory->quantity,
                'batch_number' => $inventory->batch_number,
                'expiry_date' => $inventory->expiry_date,
                'unit' => $inventory->unit,
                'storage_requirements' => $inventory->storage_requirements,
                'temperature_controlled' => $inventory->stockage->storage->temperature_controlled ?? false,
            ];
        });

        // Use product-specific thresholds
        $lowStockThreshold = $product->minimum_stock_level ?? 20;
        $criticalStockThreshold = $product->critical_stock_level ?? 10;
        $expiryAlertDays = 60; // Pharmacy standard

        // Adjust thresholds based on quantity mode
        if ($quantityByBox && $product->units_per_package) {
            $lowStockThreshold = ceil($lowStockThreshold / $product->units_per_package);
            $criticalStockThreshold = ceil($criticalStockThreshold / $product->units_per_package);
        }

        $stats = [
            'total_quantity' => $totalQuantity,
            'display_unit' => $displayUnit,
            'quantity_by_box' => $quantityByBox,
            'conversion_factor' => $conversionFactor,
            'locations_count' => $locations->count(),
            'low_stock_threshold' => $lowStockThreshold,
            'critical_stock_threshold' => $criticalStockThreshold,
            'expiry_alert_days' => $expiryAlertDays,
            'is_low_stock' => $totalQuantity <= $lowStockThreshold,
            'is_critical_stock' => $totalQuantity <= $criticalStockThreshold,
            'is_controlled_substance' => $product->is_controlled_substance,
            'requires_prescription' => $product->requires_prescription,
            'locations' => $locations,
        ];

        return response()->json([
            'success' => true,
            'product' => $product,
            'stats' => $stats,
        ]);
    }

    /**
     * Get product settings.
     */
    public function getSettings($productId)
    {
        $product = PharmacyProduct::findOrFail($productId);

        // Pharmacy-specific settings with enhanced defaults
        $settings = [
            'min_quantity_all_services' => [
                'threshold' => $product->minimum_stock_level ?? 20,
                'apply_to_services' => true,
            ],
            'critical_stock_threshold' => [
                'threshold' => $product->critical_stock_level ?? 10,
                'apply_to_services' => true,
            ],
            'expiry_alert_days' => [
                'days' => 60,
            ],
            'auto_reorder_settings' => [
                'enabled' => false,
                'reorder_point' => $product->minimum_stock_level ?? 30,
                'reorder_quantity' => 100,
            ],
            'notification_settings' => [
                'email_alerts' => true,
                'sms_alerts' => false,
                'alert_frequency' => 'daily',
            ],
            'pharmacy_specific' => [
                'controlled_substance_monitoring' => $product->is_controlled_substance,
                'prescription_required' => $product->requires_prescription,
                'temperature_monitoring' => true,
                'batch_tracking' => true,
            ],
        ];

        return response()->json([
            'success' => true,
            'settings' => $settings,
        ]);
    }

    /**
     * Save product settings.
     */
    public function saveSettings(Request $request, $productId)
    {
        $product = PharmacyProduct::findOrFail($productId);

        $request->validate([
            'settings' => 'required|array',
            'settings.min_quantity_all_services' => 'sometimes|array',
            'settings.critical_stock_threshold' => 'sometimes|array',
            'settings.expiry_alert_days' => 'sometimes|array',
            'settings.auto_reorder_settings' => 'sometimes|array',
            'settings.notification_settings' => 'sometimes|array',
            'settings.pharmacy_specific' => 'sometimes|array',
        ]);

        $settings = $request->input('settings');

        // Here you would typically save the settings to a database table
        // For now, we'll just return success since this is a demo implementation

        return response()->json([
            'success' => true,
            'message' => 'Pharmacy product settings saved successfully',
            'settings' => $settings,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PharmacyProduct $product)
    {
        try {
            // Start a database transaction
            \DB::beginTransaction();

            // Get all inventory IDs for this product
            $inventoryIds = $product->inventories()->pluck('id')->toArray();

            // First, delete pharmacy movement inventory selections that reference these inventories
            if (! empty($inventoryIds)) {
                \DB::table('pharmacy_movement_inventory_selections')
                    ->whereIn('inventory_id', $inventoryIds)
                    ->delete();
            }

            // Then, delete all related inventory records
            $product->inventories()->delete();

            // Finally, delete the product
            $product->delete();

            // Commit the transaction
            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pharmacy product deleted successfully',
            ]);

        } catch (\Exception $e) {
            // Rollback the transaction on error
            \DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete pharmacy product: '.$e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getStockDetails($productId)
    {
        try {
            $product = PharmacyProduct::with(['pharmacyInventories.pharmacyStockage.service'])->findOrFail($productId);
            
            // Get global settings
            $globalSettings = PharmacyProductGlobalSetting::getAllSettingsForProduct($productId);
            
            // Set defaults if no settings exist
            if (empty($globalSettings)) {
                $globalSettings = [
                    'min_quantity_all_services' => ['threshold' => 10, 'enabled' => true],
                    'critical_stock_threshold' => ['threshold' => 5, 'enabled' => true],
                    'expiry_alert_days' => ['days' => 30, 'enabled' => true],
                ];
            }
            
            // Calculate stock summary
            $unitsPerPackage = $product->units_per_package ?? 1;
            
            $totalQuantity = $product->pharmacyInventories->sum(function ($inventory) use ($unitsPerPackage) {
                return $inventory->quantity * $unitsPerPackage;
            });
            
            $totalValue = $product->pharmacyInventories->sum(function ($inventory) {
                return $inventory->quantity * ($inventory->unit_price ?? 0);
            });
            
            $lowStockThreshold = is_array($globalSettings['min_quantity_all_services']) ? 
                ($globalSettings['min_quantity_all_services']['threshold'] ?? 10) : 10;
            $criticalStockThreshold = is_array($globalSettings['critical_stock_threshold']) ? 
                ($globalSettings['critical_stock_threshold']['threshold'] ?? 5) : 5;
            
            // Get stock details by location
            $locations = $product->pharmacyInventories->map(function ($inventory) use ($product, $lowStockThreshold, $unitsPerPackage) {
                $quantity = $inventory->quantity * $unitsPerPackage;
                
                return [
                    'id' => $inventory->id,
                    'stockage_id' => $inventory->pharmacy_stockage_id,
                    'stockage' => [
                        'id' => $inventory->pharmacyStockage->id ?? null,
                        'name' => $inventory->pharmacyStockage->name ?? 'Unknown',
                    ],
                    'service_name' => $inventory->pharmacyStockage->service->name ?? 'N/A',
                    'quantity' => $quantity,
                    'unit_price' => $inventory->unit_price ?? 0,
                    'total_value' => $quantity * ($inventory->unit_price ?? 0),
                    'batch_number' => $inventory->batch_number ?? 'N/A',
                    'expiry_date' => $inventory->expiry_date,
                    'is_low_stock' => $quantity <= $lowStockThreshold,
                    'is_expired' => $inventory->expiry_date && now()->gt($inventory->expiry_date),
                    'unit' => $product->dosage_form ?? 'units',
                ];
            });
            
            // Calculate alerts
            $alerts = [
                'low_stock' => $totalQuantity <= $lowStockThreshold && $totalQuantity > $criticalStockThreshold,
                'critical_stock' => $totalQuantity <= $criticalStockThreshold,
                'expiring_soon' => $product->pharmacyInventories->filter(function ($inv) {
                    return $inv->expiry_date && now()->diffInDays($inv->expiry_date) <= 30 && now()->lt($inv->expiry_date);
                })->count(),
                'expired' => $product->pharmacyInventories->filter(function ($inv) {
                    return $inv->expiry_date && now()->gt($inv->expiry_date);
                })->count(),
            ];
            
            return response()->json([
                'success' => true,
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'generic_name' => $product->generic_name,
                    'brand_name' => $product->brand_name,
                    'category' => $product->category,
                    'dosage_form' => $product->dosage_form,
                    'strength' => $product->strength,
                    'units_per_package' => $product->units_per_package,
                    'low_stock_threshold' => $lowStockThreshold,
                    'requires_prescription' => $product->requires_prescription,
                    'is_controlled_substance' => $product->is_controlled_substance,
                ],
                'summary' => [
                    'total_quantity' => $totalQuantity,
                    'total_value' => $totalValue,
                    'location_count' => $locations->count(),
                    'average_price' => $totalQuantity > 0 ? $totalValue / $totalQuantity : 0,
                ],
                'stock_details' => $locations->values(),
                'alerts' => $alerts,
                'global_settings' => $globalSettings,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load stock details: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getCategories()
    {
        $categories = PharmacyProduct::select('category')->distinct()->pluck('category');

        return response()->json(['success' => true, 'data' => $categories]);
    }

    /**
     * Get alert counts separately (optimized for dashboard/widgets)
     * This endpoint is cached and should be called independently
     */
    public function getAlertCounts(Request $request)
    {
        // Cache for 5 minutes
        $cacheKey = 'pharmacy_product_alert_counts_' . auth()->id();
        
        $alertCounts = \Cache::remember($cacheKey, 300, function () use ($request) {
            $user = auth()->user();
            
            // Build base query
            $query = PharmacyProduct::query();
            
            // Filter by user's assigned services
            if ($user && ! $user->hasRole(['admin', 'SuperAdmin'])) {
                $userServices = $user->services ?? [];
                if (! empty($userServices)) {
                    $query->whereHas('inventories.stockage', function ($q) use ($userServices) {
                        $q->whereIn('service_id', $userServices);
                    });
                }
            }
            
            // Load only necessary data
            $products = $query->with(['inventories' => function ($q) {
                $q->select('id', 'pharmacy_product_id', 'quantity', 'expiry_date')
                    ->where('quantity', '>', 0);
            }])
            ->select(['id', 'minimum_stock_level', 'critical_stock_level', 'units_per_package', 'is_controlled_substance'])
            ->get();
            
            $counts = [
                'low_stock' => 0,
                'critical_stock' => 0,
                'expiring' => 0,
                'expired' => 0,
                'controlled_substance' => 0,
            ];
            
            $now = now();
            $expiryAlertDays = 60;
            
            foreach ($products as $product) {
                $totalQuantity = $product->inventories->sum(function ($inv) use ($product) {
                    return $inv->quantity * ($product->units_per_package ?? 1);
                });
                
                // Stock alerts
                $lowThreshold = $product->minimum_stock_level ?? 20;
                $criticalThreshold = $product->critical_stock_level ?? 10;
                
                if ($totalQuantity <= $criticalThreshold) {
                    $counts['critical_stock']++;
                } elseif ($totalQuantity <= $lowThreshold) {
                    $counts['low_stock']++;
                }
                
                // Expiry alerts
                foreach ($product->inventories as $inventory) {
                    if ($inventory->expiry_date) {
                        if ($inventory->expiry_date->isPast()) {
                            $counts['expired']++;
                            break; // Count product once
                        } elseif ($now->diffInDays($inventory->expiry_date, false) <= $expiryAlertDays) {
                            $counts['expiring']++;
                            break; // Count product once
                        }
                    }
                }
                
                // Controlled substance
                if ($product->is_controlled_substance) {
                    $counts['controlled_substance']++;
                }
            }
            
            return $counts;
        });
        
        return response()->json([
            'success' => true,
            'alert_counts' => $alertCounts,
        ]);
    }

    /**
     * Clear alert counts cache (call after inventory changes)
     */
    public function clearAlertCache()
    {
        $cacheKey = 'pharmacy_product_alert_counts_' . auth()->id();
        \Cache::forget($cacheKey);
        
        return response()->json([
            'success' => true,
            'message' => 'Alert cache cleared',
        ]);
    }

    /**
     * Lightweight endpoint for autocomplete/dropdowns
     * Returns minimal data for fast loading in select components
     * OPTIMIZED: Maximum 100 results, minimal columns
     */
    public function autocomplete(Request $request)
    {
        $search = $request->get('search', '');
        $limit = min($request->get('limit', 50), 100); // Cap at 100
        
        $query = PharmacyProduct::select([
            'id', 'name', 'code', 'category', 'unit_of_measure', 'boite_de'
        ])
        ->where('is_active', true);
        
        // Search filter
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }
        
        // Filter by service if provided
        if ($request->has('service_id')) {
            $query->whereHas('inventories.stockage', function ($q) use ($request) {
                $q->where('service_id', $request->service_id)
                  ->where('quantity', '>', 0);
            });
        }
        
        $products = $query->limit($limit)
            ->orderBy('name', 'asc')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $products,
            'count' => $products->count(),
        ]);
    }

    /**
     * Get product settings for a specific service-product combination.
     * Returns custom settings if exist, otherwise returns defaults.
     */
    public function getProductSettings($serviceId, $productName, $productForme)
    {
        try {
            // Decode URL-encoded parameters
            $productName = urldecode($productName);
            $productForme = urldecode($productForme);
            
            // Find product by name or ID
            $product = PharmacyProduct::where('name', $productName)
                ->orWhere('id', (int)$productName)
                ->first();
            
            if (!$product) {
                // Product not found, return defaults
                return response()->json([
                    'success' => true,
                    'data' => $this->getDefaultProductSettings($productName, $productForme),
                    'is_default' => true,
                ]);
            }
            
            // Try to find service-specific settings
            $setting = \DB::table('pharmacy_service_product_settings')
                ->where('service_id', $serviceId)
                ->where('pharmacy_product_id', $product->id)
                ->first();
            
            if ($setting) {
                return response()->json([
                    'success' => true,
                    'data' => (array)$setting,
                    'is_default' => false,
                ]);
            }
            
            // No custom settings found, return defaults
            return response()->json([
                'success' => true,
                'data' => $this->getDefaultProductSettings($productName, $productForme),
                'is_default' => true,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving product settings: ' . $e->getMessage(),
                'data' => $this->getDefaultProductSettings($productName, $productForme),
            ], 500);
        }
    }
    
    /**
     * Store new product settings.
     * Expected request body: { service_id, product_name, ...settings }
     * If pharmacy_product_id not provided, will look up by product_name
     */
    public function storeProductSettings(Request $request)
    {
        try {
            // First validate required fields
            $request->validate([
                'service_id' => 'required|integer|exists:services,id',
                'product_name' => 'required|string',
            ]);
            
            $productName = $request->input('product_name');
            
            // Find or get product ID
            $productId = $request->input('pharmacy_product_id');
            if (!$productId) {
                $product = PharmacyProduct::where('name', $productName)->first();
                if (!$product) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Product not found: ' . $productName,
                    ], 404);
                }
                $productId = $product->id;
            }
            
            // Validate all fields
            $data = $request->validate([
                'service_id' => 'required|integer|exists:services,id',
                'product_name' => 'required|string',
                'low_stock_threshold' => 'nullable|integer|min:0',
                'critical_stock_threshold' => 'nullable|integer|min:0',
                'max_stock_level' => 'nullable|integer|min:0',
                'reorder_point' => 'nullable|integer|min:0',
                'expiry_alert_days' => 'nullable|integer|min:0',
                'min_shelf_life_days' => 'nullable|integer|min:0',
                'email_alerts' => 'nullable|boolean',
                'sms_alerts' => 'nullable|boolean',
                'alert_frequency' => 'nullable|string',
                'preferred_supplier' => 'nullable|string',
                'batch_tracking' => 'nullable|boolean',
                'location_tracking' => 'nullable|boolean',
                'auto_reorder' => 'nullable|boolean',
                'custom_name' => 'nullable|string',
                'color_code' => 'nullable|string',
                'priority' => 'nullable|string|in:low,normal,high,critical',
            ]);
            
            // Add pharmacy_product_id
            $data['pharmacy_product_id'] = $productId;
            
            // Map priority to priority_level
            if (isset($data['priority'])) {
                $priorityMap = ['low' => 1, 'normal' => 2, 'high' => 3, 'critical' => 4];
                $data['priority_level'] = $priorityMap[$data['priority']] ?? 2;
                unset($data['priority']);
            }
            
            // Check if settings already exist (unique on service_id, pharmacy_product_id)
            $existing = \DB::table('pharmacy_service_product_settings')
                ->where('service_id', $data['service_id'])
                ->where('pharmacy_product_id', $productId)
                ->first();
            
            if ($existing) {
                // Update existing record instead
                $data['updated_at'] = now();
                \DB::table('pharmacy_service_product_settings')
                    ->where('id', $existing->id)
                    ->update($data);
                
                $updated = \DB::table('pharmacy_service_product_settings')
                    ->where('id', $existing->id)
                    ->first();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Product settings updated successfully',
                    'data' => (array)$updated,
                ]);
            }
            
            // Create new record
            $data['created_at'] = now();
            $data['updated_at'] = now();
            
            $id = \DB::table('pharmacy_service_product_settings')
                ->insertGetId($data);
            
            $newSetting = \DB::table('pharmacy_service_product_settings')
                ->where('id', $id)
                ->first();
            
            return response()->json([
                'success' => true,
                'message' => 'Product settings created successfully',
                'data' => (array)$newSetting,
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating product settings: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get all product settings for a specific service.
     */
    public function getProductSettingsByService($serviceId)
    {
        try {
            $settings = \DB::table('pharmacy_service_product_settings')
                ->where('service_id', $serviceId)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $settings->toArray(),
                'count' => $settings->count(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving product settings: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get default product settings.
     */
    private function getDefaultProductSettings($productName, $productForme)
    {
        return [
            'low_stock_threshold' => 10,
            'critical_stock_threshold' => 5,
            'max_stock_level' => 100,
            'reorder_point' => 15,
            'expiry_alert_days' => 30,
            'min_shelf_life_days' => 90,
            'email_alerts' => false,
            'sms_alerts' => false,
            'alert_frequency' => null,
            'preferred_supplier' => null,
            'batch_tracking' => true,
            'location_tracking' => true,
            'auto_reorder' => false,
            'custom_name' => null,
            'color_code' => 'default',
            'priority' => 'normal',
        ];
    }
    
    /**
     * Update product settings for a specific service-product combination.
     */
    public function updateProductSettings(Request $request, $serviceId, $productName, $productForme)
    {
        try {
            // Decode URL-encoded parameters
            $productName = urldecode($productName);
            
            // Find product
            $product = PharmacyProduct::where('name', $productName)
                ->orWhere('id', (int)$productName)
                ->first();
            
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found',
                ], 404);
            }
            
            // Validate data
            $data = $request->validate([
                'low_stock_threshold' => 'nullable|integer|min:0',
                'critical_stock_threshold' => 'nullable|integer|min:0',
                'max_stock_level' => 'nullable|integer|min:0',
                'reorder_point' => 'nullable|integer|min:0',
                'expiry_alert_days' => 'nullable|integer|min:0',
                'min_shelf_life_days' => 'nullable|integer|min:0',
                'email_alerts' => 'nullable|boolean',
                'sms_alerts' => 'nullable|boolean',
                'alert_frequency' => 'nullable|string',
                'preferred_supplier' => 'nullable|string',
                'batch_tracking' => 'nullable|boolean',
                'location_tracking' => 'nullable|boolean',
                'auto_reorder' => 'nullable|boolean',
                'custom_name' => 'nullable|string',
                'color_code' => 'nullable|string',
                'priority' => 'nullable|string|in:low,normal,high,critical',
            ]);
            
            // Map priority to priority_level
            if (isset($data['priority'])) {
                $priorityMap = ['low' => 1, 'normal' => 2, 'high' => 3, 'critical' => 4];
                $data['priority_level'] = $priorityMap[$data['priority']] ?? 2;
                unset($data['priority']);
            }
            
            $data['updated_at'] = now();
            
            // Find and update the setting record
            $updated = \DB::table('pharmacy_service_product_settings')
                ->where('service_id', $serviceId)
                ->where('pharmacy_product_id', $product->id)
                ->update($data);
            
            if ($updated > 0) {
                $setting = \DB::table('pharmacy_service_product_settings')
                    ->where('service_id', $serviceId)
                    ->where('pharmacy_product_id', $product->id)
                    ->first();
                    
                return response()->json([
                    'success' => true,
                    'message' => 'Product settings updated successfully',
                    'data' => (array)$setting,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Settings not found',
                ], 404);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating product settings: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Delete product settings for a specific service-product combination.
     */
    public function destroyProductSettings($serviceId, $productName, $productForme)
    {
        try {
            // Decode URL-encoded parameters
            $productName = urldecode($productName);
            
            // Find product
            $product = PharmacyProduct::where('name', $productName)
                ->orWhere('id', (int)$productName)
                ->first();
            
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found',
                ], 404);
            }
            
            // Delete the setting record
            $deleted = \DB::table('pharmacy_service_product_settings')
                ->where('service_id', $serviceId)
                ->where('pharmacy_product_id', $product->id)
                ->delete();
            
            if ($deleted > 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'Product settings deleted successfully',
                ]);
            } else {
                return response()->json([
                    'success' => true,
                    'message' => 'No settings to delete',
                ]);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting product settings: ' . $e->getMessage(),
            ], 500);
        }
    }
}

/**
 * Get low stock products.
 */

// Pharmacy Inventory
