<?php

namespace App\Http\Controllers\Stock;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductController extends \App\Http\Controllers\Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $quantityByBox = $request->boolean('quantity_by_box', false);
        
        $query = Product::query();

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->has('category') && !empty($request->category)) {
            $query->where('category', $request->category);
        }

        // Filter by clinical status
        if ($request->has('is_clinical')) {
            $query->where('is_clinical', $request->boolean('is_clinical'));
        }

        // Get products with inventories for alert calculation
        $products = $query->with(['inventories' => function ($query) {
            $query->with('stockage');
        }])->orderBy('created_at', 'desc')->get();

        // Add alert information and quantity calculations to each product
        $productsWithAlerts = $products->map(function ($product) use ($quantityByBox) {
            // Get global settings for this product
            $globalSettings = \App\Models\ProductGlobalSetting::getAllSettingsForProduct($product->id);

            // Calculate total quantity based on mode
            if ($quantityByBox && $product->boite_de) {
                $totalQuantity = $product->inventories->sum('quantity');
                $displayUnit = 'boxes';
            } else {
                $totalQuantity = $product->inventories->sum(function ($inventory) use ($product) {
                    return $inventory->total_units;
                });
                $displayUnit = $product->forme ?? 'units';
            }

            // Get unit information from inventories (use the most common unit or first available)
            $units = $product->inventories->pluck('unit')->filter()->unique();
            $primaryUnit = $units->first() ?? 'units';

            // Use global settings for thresholds, with defaults
            $lowStockThreshold = $globalSettings['min_quantity_all_services']['threshold'] ?? 10;
            $criticalStockThreshold = $globalSettings['critical_stock_threshold']['threshold'] ?? 5;
            $expiryAlertDays = $globalSettings['expiry_alert_days']['days'] ?? 30;

            // Adjust thresholds based on quantity mode
            if ($quantityByBox && $product->boite_de) {
                $lowStockThreshold = ceil($lowStockThreshold / $product->boite_de);
                $criticalStockThreshold = ceil($criticalStockThreshold / $product->boite_de);
            }

            // Calculate alerts
            $alerts = [];

            // Low stock alert
            if ($totalQuantity <= $lowStockThreshold && $totalQuantity > $criticalStockThreshold) {
                $alerts[] = [
                    'type' => 'low_stock',
                    'severity' => 'warning',
                    'message' => "Low Stock: {$totalQuantity} {$displayUnit} remaining",
                    'icon' => 'pi pi-exclamation-triangle'
                ];
            }

            // Critical stock alert
            if ($totalQuantity <= $criticalStockThreshold) {
                $alerts[] = [
                    'type' => 'critical_stock',
                    'severity' => 'danger',
                    'message' => "Critical Stock: {$totalQuantity} {$displayUnit} remaining",
                    'icon' => 'pi pi-times-circle'
                ];
            }

            // Expiring alerts
            $expiringItems = $product->inventories->filter(function ($inventory) use ($expiryAlertDays) {
                if (!$inventory->expiry_date) return false;
                $daysUntilExpiry = now()->diffInDays($inventory->expiry_date, false);
                return $daysUntilExpiry <= $expiryAlertDays && $daysUntilExpiry > 0;
            });

            if ($expiringItems->count() > 0) {
                $alerts[] = [
                    'type' => 'expiring',
                    'severity' => 'warning',
                    'message' => "Expiring Soon: {$expiringItems->count()} items",
                    'icon' => 'pi pi-clock'
                ];
            }

            // Expired alerts
            $expiredItems = $product->inventories->filter(function ($inventory) {
                if (!$inventory->expiry_date) return false;
                return $inventory->expiry_date->isPast();
            });

            if ($expiredItems->count() > 0) {
                $alerts[] = [
                    'type' => 'expired',
                    'severity' => 'danger',
                    'message' => "Expired: {$expiredItems->count()} items",
                    'icon' => 'pi pi-ban'
                ];
            }

            // Add alert information to product
            $product->alerts = $alerts;
            $product->total_quantity = $totalQuantity;
            $product->display_unit = $displayUnit;
            $product->quantity_by_box = $quantityByBox;
            $product->unit = $primaryUnit; // Add unit to product
            $product->global_settings = $globalSettings;

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
        ];

        // Filter by alert types if provided
        if ($request->has('alert_filters') && !empty($request->alert_filters)) {
            $alertFilters = $request->alert_filters;
            if (is_string($alertFilters)) {
                $alertFilters = json_decode($alertFilters, true);
            }

            if (is_array($alertFilters) && !empty($alertFilters)) {
                $productsWithAlerts = $productsWithAlerts->filter(function ($product) use ($alertFilters) {
                    if (!$product->alerts || empty($product->alerts)) {
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
                'to' => $paginatedProducts->isNotEmpty() ? (($currentPage - 1) * $perPage) + $paginatedProducts->count() : null
            ],
            'alert_counts' => $alertCounts,
            'quantity_by_box' => $quantityByBox
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
            'category' => ['required', Rule::in(['Medical Supplies', 'Equipment', 'Medication', 'Others'])],
            'is_clinical' => 'boolean',
            'code_interne' => 'nullable|integer',
            'code_pch' => 'nullable|string|max:255',
            'designation' => 'nullable|string|max:255',
            'type_medicament' => 'nullable|string|max:255',
            'forme' => 'nullable|string|max:255',
            'boite_de' => 'nullable|integer',
            'nom_commercial' => 'nullable|string|max:255',
            'status' => ['nullable', Rule::in(['In Stock', 'Low Stock', 'Out of Stock'])]
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        
        // Set is_clinical based on checkbox value only
        $data['is_clinical'] = $request->boolean('is_clinical', false);
        
        // Set default status if not provided
        if (!isset($data['status'])) {
            $data['status'] = 'In Stock';
        }

        $product = Product::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => $product
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        return response()->json([
            'success' => true,
            'data' => $product
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => ['required', Rule::in(['Medical Supplies', 'Equipment', 'Medication', 'Others'])],
            'is_clinical' => 'boolean',
            'code_interne' => 'nullable|integer',
            'code_pch' => 'nullable|string|max:255',
            'designation' => 'nullable|string|max:255',
            'type_medicament' => 'nullable|string|max:255',
            'forme' => 'nullable|string|max:255',
            'boite_de' => 'nullable|integer',
            'nom_commercial' => 'nullable|string|max:255',
            'status' => ['nullable', Rule::in(['In Stock', 'Low Stock', 'Out of Stock'])]
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        
        // Set is_clinical based on checkbox value only
        $data['is_clinical'] = $request->boolean('is_clinical', false);

        $product->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data' => $product
        ]);
    }

    /**
     * Bulk delete products.
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:products,id'
        ]);

        $ids = $request->ids;
        $deletedCount = Product::whereIn('id', $ids)->delete();

        return response()->json([
            'success' => true,
            'message' => "{$deletedCount} products deleted successfully",
            'deleted_count' => $deletedCount
        ]);
    }

    /**
     * Get detailed information about a product including stock stats and locations.
     */
    public function getDetails($id, Request $request)
    {
        $quantityByBox = $request->boolean('quantity_by_box', false);
        
        $product = Product::with(['inventories' => function ($query) {
            $query->with('stockage');
        }])->findOrFail($id);

        // Get global settings for this product
        $globalSettings = \App\Models\ProductGlobalSetting::getAllSettingsForProduct($id);

        // Calculate stats based on quantity mode
        if ($quantityByBox && $product->boite_de) {
            // Calculate by boxes
            $totalQuantity = $product->inventories->sum('quantity');
            $displayUnit = 'boxes';
            $conversionFactor = $product->boite_de;
        } else {
            // Calculate by units
            $totalQuantity = $product->inventories->sum(function ($inventory) use ($product) {
                return $inventory->total_units;
            });
            $displayUnit = $product->forme ?? 'units';
            $conversionFactor = 1;
        }

        $locations = $product->inventories->map(function ($inventory) use ($product, $quantityByBox) {
            if ($quantityByBox && $product->boite_de) {
                $displayQuantity = $inventory->quantity;
                $actualQuantity = $inventory->quantity * $product->boite_de;
            } else {
                $displayQuantity = $inventory->total_units;
                $actualQuantity = $inventory->total_units;
            }

            return [
                'location' => $inventory->stockage->name ?? 'Unknown',
                'quantity' => $displayQuantity,
                'actual_quantity' => $actualQuantity,
                'box_quantity' => $inventory->quantity,
                'batch_number' => $inventory->batch_number,
                'expiry_date' => $inventory->expiry_date,
                'unit' => $inventory->unit,
            ];
        });

        // Use global settings for thresholds, with defaults
        $lowStockThreshold = $globalSettings['min_quantity_all_services']['threshold'] ?? 10;
        $criticalStockThreshold = $globalSettings['critical_stock_threshold']['threshold'] ?? 5;
        $expiryAlertDays = $globalSettings['expiry_alert_days']['days'] ?? 30;

        // Adjust thresholds based on quantity mode
        if ($quantityByBox && $product->boite_de) {
            $lowStockThreshold = ceil($lowStockThreshold / $product->boite_de);
            $criticalStockThreshold = ceil($criticalStockThreshold / $product->boite_de);
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
            'locations' => $locations,
            'global_settings' => $globalSettings,
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
        $product = Product::findOrFail($productId);

        // For now, return default settings. In a real implementation,
        // you would fetch these from a settings table or configuration
        $settings = [
            'min_quantity_all_services' => [
                'threshold' => 10,
                'apply_to_services' => true
            ],
            'critical_stock_threshold' => [
                'threshold' => 5,
                'apply_to_services' => true
            ],
            'expiry_alert_days' => [
                'days' => 30
            ],
            'auto_reorder_settings' => [
                'enabled' => false,
                'reorder_point' => 20,
                'reorder_quantity' => 50
            ],
            'notification_settings' => [
                'email_alerts' => true,
                'sms_alerts' => false,
                'alert_frequency' => 'daily'
            ]
        ];

        return response()->json([
            'success' => true,
            'settings' => $settings
        ]);
    }

    /**
     * Save product settings.
     */
    public function saveSettings(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);

        $request->validate([
            'settings' => 'required|array',
            'settings.min_quantity_all_services' => 'sometimes|array',
            'settings.critical_stock_threshold' => 'sometimes|array',
            'settings.expiry_alert_days' => 'sometimes|array',
            'settings.auto_reorder_settings' => 'sometimes|array',
            'settings.notification_settings' => 'sometimes|array'
        ]);

        $settings = $request->input('settings');

        // Here you would typically save the settings to a database table
        // For now, we'll just return success since this is a demo implementation

        return response()->json([
            'success' => true,
            'message' => 'Product settings saved successfully',
            'settings' => $settings
        ]);
    }
}
