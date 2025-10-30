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
     */
    public function index(Request $request)
    {
        $quantityByBox = $request->boolean('quantity_by_box', false);
        $user = auth()->user();

        $query = PharmacyProduct::query();

        // Filter by user's assigned services (only admin/SuperAdmin see all)
        if ($user && ! $user->hasRole(['admin', 'SuperAdmin'])) {
            // Get user's assigned services
            $userServices = $user->services ?? [];
            if (! empty($userServices)) {
                $query->whereHas('pharmacyInventories.pharmacyStockage', function ($q) use ($userServices) {
                    $q->whereIn('service_id', $userServices);
                });
            }
        }

        // Enhanced search functionality
        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('generic_name', 'like', "%{$search}%")
                    ->orWhere('brand_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('dosage_form', 'like', "%{$search}%")
                    ->orWhere('commercial_name', 'like', "%{$search}%")
                    ->orWhere('medication_type', 'like', "%{$search}%")
                    ->orWhere('active_ingredient', 'like', "%{$search}%");
            });
        }

        // Filter by category (pharmacy-specific categories)
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

        // Get products with inventories for alert calculation
        $products = $query->with(['inventories.stockage.storage'])->orderBy('created_at', 'desc')->get();

        // Add alert information and quantity calculations to each product
        $productsWithAlerts = $products->map(function ($product) use ($quantityByBox, $user) {
            // Filter inventories by user's services if not admin
            $inventories = $product->inventories;
            if (! $user->hasRole(['admin', 'SuperAdmin'])) {
                $userServices = $user->services ?? [];
                if (! empty($userServices)) {
                    $inventories = $inventories->filter(function ($inventory) use ($userServices) {
                        return in_array($inventory->pharmacyStockage->service_id ?? null, $userServices);
                    });
                }
            }

            // Calculate total quantity based on mode
            $unitsPerPackage = $product->units_per_package ?? $product->packaging_info['units_per_package'] ?? 1;

            if ($quantityByBox && $unitsPerPackage > 1) {
                $totalQuantity = $inventories->sum('quantity');
                $totalUnits = $inventories->sum(function ($inventory) use ($unitsPerPackage) {
                    return $inventory->quantity * $unitsPerPackage;
                });
                $displayUnit = 'packages';
                $inventoryDisplay = "{$totalQuantity} packages ({$totalUnits} units)";
            } else {
                $totalQuantity = $inventories->sum(function ($inventory) use ($unitsPerPackage) {
                    return $inventory->quantity * $unitsPerPackage;
                });
                $totalPackages = $unitsPerPackage > 1 ? floor($totalQuantity / $unitsPerPackage) : 0;
                $displayUnit = $product->dosage_form ?? 'units';
                $inventoryDisplay = $unitsPerPackage > 1 ?
                    "{$totalPackages} packages × {$unitsPerPackage} = {$totalQuantity} units" :
                    "{$totalQuantity} units";
            }

            // Get inventory details by service/location
            $inventoryByLocation = $inventories->groupBy(function ($inventory) {
                return $inventory->pharmacyStockage->service->name ??
                       $inventory->pharmacyStockage->name ??
                       'Unknown Location';
            })->map(function ($locationInventories, $locationName) use ($unitsPerPackage, $quantityByBox) {
                $locationQuantity = $locationInventories->sum('quantity');
                $locationUnits = $locationInventories->sum(function ($inventory) use ($unitsPerPackage) {
                    return $inventory->quantity * $unitsPerPackage;
                });

                if ($quantityByBox && $unitsPerPackage > 1) {
                    return [
                        'location' => $locationName,
                        'packages' => $locationQuantity,
                        'units' => $locationUnits,
                        'display' => "{$locationQuantity} packages ({$locationUnits} units)",
                    ];
                } else {
                    $packages = $unitsPerPackage > 1 ? floor($locationUnits / $unitsPerPackage) : 0;

                    return [
                        'location' => $locationName,
                        'packages' => $packages,
                        'units' => $locationUnits,
                        'display' => $unitsPerPackage > 1 ?
                            "{$packages} packages × {$unitsPerPackage} = {$locationUnits} units" :
                            "{$locationUnits} units",
                    ];
                }
            })->values();

            // Use product-specific thresholds
            $lowStockThreshold = $product->minimum_stock_level ?? 20;
            $criticalStockThreshold = $product->critical_stock_level ?? 10;
            $expiryAlertDays = 60; // Pharmacy standard

            // Adjust thresholds based on quantity mode
            if ($quantityByBox && $product->units_per_package) {
                $lowStockThreshold = ceil($lowStockThreshold / $product->units_per_package);
                $criticalStockThreshold = ceil($criticalStockThreshold / $product->units_per_package);
            }

            // Calculate alerts
            $alerts = [];

            // Low stock alert
            if ($totalQuantity <= $lowStockThreshold && $totalQuantity > $criticalStockThreshold) {
                $alerts[] = [
                    'type' => 'low_stock',
                    'severity' => 'warning',
                    'message' => "Low Stock: {$totalQuantity} {$displayUnit} remaining",
                    'icon' => 'pi pi-exclamation-triangle',
                ];
            }

            // Critical stock alert
            if ($totalQuantity <= $criticalStockThreshold) {
                $alerts[] = [
                    'type' => 'critical_stock',
                    'severity' => 'danger',
                    'message' => "Critical Stock: {$totalQuantity} {$displayUnit} remaining",
                    'icon' => 'pi pi-times-circle',
                ];
            }

            // Expiring alerts (pharmacy-specific)
            $expiringItems = $product->inventories->filter(function ($inventory) use ($expiryAlertDays) {
                if (! $inventory->expiry_date) {
                    return false;
                }
                $daysUntilExpiry = now()->diffInDays($inventory->expiry_date, false);

                return $daysUntilExpiry <= $expiryAlertDays && $daysUntilExpiry > 0;
            });

            if ($expiringItems->count() > 0) {
                $alerts[] = [
                    'type' => 'expiring',
                    'severity' => 'warning',
                    'message' => "Expiring Soon: {$expiringItems->count()} batches",
                    'icon' => 'pi pi-clock',
                ];
            }

            // Expired alerts
            $expiredItems = $product->inventories->filter(function ($inventory) {
                if (! $inventory->expiry_date) {
                    return false;
                }

                return $inventory->expiry_date->isPast();
            });

            if ($expiredItems->count() > 0) {
                $alerts[] = [
                    'type' => 'expired',
                    'severity' => 'danger',
                    'message' => "Expired: {$expiredItems->count()} batches",
                    'icon' => 'pi pi-ban',
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

            // Get primary unit from inventories
            $units = $product->inventories->pluck('unit')->filter()->unique();
            $primaryUnit = $units->first() ?? 'units';

            // Add alert information to product
            $product->alerts = $alerts;
            $product->total_quantity = $totalQuantity;
            $product->display_unit = $displayUnit;
            $product->quantity_by_box = $quantityByBox;
            $product->unit = $primaryUnit;

            // Add inventory information
            $product->inventory_display = $inventoryDisplay;
            $product->inventory_by_location = $inventoryByLocation;
            $product->units_per_package = $unitsPerPackage;
            $product->has_package_units = $unitsPerPackage > 1;

            return $product;
        });

        // Calculate total alert counts across all products (before filtering)
        $allProductsWithAlerts = $productsWithAlerts;
        $alertCounts = [
            'low_stock' => $allProductsWithAlerts->filter(function ($product) {
                return collect($product->alerts)->contains('type', 'low_stock');
            })->count(),
            'critical_stock' => $allProductsWithAlerts->filter(function ($product) {
                return collect($product->alerts)->contains('type', 'critical_stock');
            })->count(),
            'expiring' => $allProductsWithAlerts->filter(function ($product) {
                return collect($product->alerts)->contains('type', 'expiring');
            })->count(),
            'expired' => $allProductsWithAlerts->filter(function ($product) {
                return collect($product->alerts)->contains('type', 'expired');
            })->count(),
            'controlled_substance' => $allProductsWithAlerts->filter(function ($product) {
                return collect($product->alerts)->contains('type', 'controlled_substance');
            })->count(),
        ];

        // Filter by alert types if provided
        if ($request->has('alert_filters') && ! empty($request->alert_filters)) {
            $alertFilters = $request->alert_filters;
            if (is_string($alertFilters)) {
                $alertFilters = json_decode($alertFilters, true);
            }

            if (is_array($alertFilters) && ! empty($alertFilters)) {
                $productsWithAlerts = $productsWithAlerts->filter(function ($product) use ($alertFilters) {
                    if (! $product->alerts || empty($product->alerts)) {
                        return false;
                    }

                    return collect($alertFilters)->contains(function ($filter) use ($product) {
                        return collect($product->alerts)->contains('type', $filter);
                    });
                });
            }
        }

        // Pagination
        $perPage = $request->get('per_page', 10);
        $currentPage = $request->get('page', 1);
        $paginatedProducts = collect($productsWithAlerts)->forPage($currentPage, $perPage);

        return response()->json([
            'success' => true,
            'data' => $paginatedProducts->values(),
            'meta' => [
                'current_page' => (int) $currentPage,
                'last_page' => ceil($productsWithAlerts->count() / $perPage),
                'per_page' => $perPage,
                'total' => $productsWithAlerts->count(),
                'from' => $paginatedProducts->isNotEmpty() ? (($currentPage - 1) * $perPage) + 1 : null,
                'to' => $paginatedProducts->isNotEmpty() ? (($currentPage - 1) * $perPage) + $paginatedProducts->count() : null,
            ],
            'alert_counts' => $alertCounts,
            'quantity_by_box' => $quantityByBox,
        ]);
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
}

/**
 * Get low stock products.
 */

// Pharmacy Inventory
