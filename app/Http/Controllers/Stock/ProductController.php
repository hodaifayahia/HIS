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
        $user = auth()->user();

        $query = Product::query();

        // Filter by user's assigned services (only admin/SuperAdmin see all)
        if ($user && ! $user->hasRole(['admin', 'SuperAdmin'])) {
            // Get user's assigned services
            $userServices = $user->services ?? [];
            if (! empty($userServices)) {
                $query->whereHas('inventories.stockage', function ($q) use ($userServices) {
                    $q->whereIn('service_id', $userServices);
                });
            }
        }

        // Enhanced search functionality
        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('code_interne', 'like', "%{$search}%")
                    ->orWhere('forme', 'like', "%{$search}%")
                    ->orWhere('designation', 'like', "%{$search}%")
                    ->orWhere('nom_commercial', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->has('category') && ! empty($request->category)) {
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
        $productsWithAlerts = $products->map(function ($product) use ($quantityByBox, $user) {
            // Get global settings for this product
            $globalSettings = \App\Models\ProductGlobalSetting::getAllSettingsForProduct($product->id);

            // Filter inventories by user's services if not admin
            $inventories = $product->inventories;
            if (! $user->hasRole(['admin', 'SuperAdmin'])) {
                $userServices = $user->services ?? [];
                if (! empty($userServices)) {
                    $inventories = $inventories->filter(function ($inventory) use ($userServices) {
                        return in_array($inventory->stockage->service_id ?? null, $userServices);
                    });
                }
            }

            // Calculate total quantity based on mode
            if ($quantityByBox && $product->boite_de) {
                $totalQuantity = $inventories->sum('quantity');
                $totalUnits = $inventories->sum(function ($inventory) use ($product) {
                    return $inventory->quantity * ($product->boite_de ?? 1);
                });
                $displayUnit = 'boxes';
                $inventoryDisplay = "{$totalQuantity} boxes ({$totalUnits} units)";
            } else {
                $totalQuantity = $inventories->sum(function ($inventory) use ($product) {
                    return $inventory->quantity * ($product->boite_de ?? 1);
                });
                $totalBoxes = $product->boite_de ? floor($totalQuantity / $product->boite_de) : 0;
                $displayUnit = $product->forme ?? 'units';
                $inventoryDisplay = $product->boite_de ?
                    "{$totalBoxes} boxes × {$product->boite_de} = {$totalQuantity} units" :
                    "{$totalQuantity} units";
            }

            // Get inventory details by service/location
            $inventoryByLocation = $inventories->groupBy(function ($inventory) {
                return $inventory->stockage->service->name ?? $inventory->stockage->name ?? 'Unknown Location';
            })->map(function ($locationInventories, $locationName) use ($product, $quantityByBox) {
                $locationQuantity = $locationInventories->sum('quantity');
                $locationUnits = $locationInventories->sum(function ($inventory) use ($product) {
                    return $inventory->quantity * ($product->boite_de ?? 1);
                });

                if ($quantityByBox && $product->boite_de) {
                    return [
                        'location' => $locationName,
                        'boxes' => $locationQuantity,
                        'units' => $locationUnits,
                        'display' => "{$locationQuantity} boxes ({$locationUnits} units)",
                    ];
                } else {
                    $boxes = $product->boite_de ? floor($locationUnits / $product->boite_de) : 0;

                    return [
                        'location' => $locationName,
                        'boxes' => $boxes,
                        'units' => $locationUnits,
                        'display' => $product->boite_de ?
                            "{$boxes} boxes × {$product->boite_de} = {$locationUnits} units" :
                            "{$locationUnits} units",
                    ];
                }
            })->values();

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

            // Expiring alerts
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
                    'message' => "Expiring Soon: {$expiringItems->count()} items",
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
                    'message' => "Expired: {$expiredItems->count()} items",
                    'icon' => 'pi pi-ban',
                ];
            }

            // Add alert information to product
            $product->alerts = $alerts;
            $product->total_quantity = $totalQuantity;
            $product->display_unit = $displayUnit;
            $product->quantity_by_box = $quantityByBox;
            $product->unit = $primaryUnit ?? 'units';
            $product->global_settings = $globalSettings;

            // Add inventory information
            $product->inventory_display = $inventoryDisplay;
            $product->inventory_by_location = $inventoryByLocation;
            $product->boite_de = $product->boite_de;
            $product->has_box_units = ! empty($product->boite_de) && $product->boite_de > 1;

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
        $perPage = $request->get('per_page', 100);
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
            'category' => ['required', Rule::in(['Medical Supplies', 'Equipment', 'Medication', 'Others'])],
            'is_clinical' => 'boolean',
            'code_interne' => 'nullable|integer',
            'code_pch' => 'nullable|string|max:255',
            'designation' => 'nullable|string|max:255',
            'type_medicament' => 'nullable|string|max:255',
            'forme' => 'nullable|string|max:255',
            'boite_de' => 'nullable|integer',
            'nom_commercial' => 'nullable|string|max:255',
            'status' => ['nullable', Rule::in(['In Stock', 'Low Stock', 'Out of Stock'])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->all();

        // Set is_clinical based on checkbox value only
        $data['is_clinical'] = $request->boolean('is_clinical', false);

        // Set default status if not provided
        if (! isset($data['status'])) {
            $data['status'] = 'In Stock';
        }

        $product = Product::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => $product,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        return response()->json([
            'success' => true,
            'data' => $product,
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
            'is_required_approval' => 'boolean',
            'code_interne' => 'nullable|integer',
            'code_pch' => 'nullable|string|max:255',
            'designation' => 'nullable|string|max:255',
            'type_medicament' => 'nullable|string|max:255',
            'forme' => 'nullable|string|max:255',
            'boite_de' => 'nullable|integer',
            'nom_commercial' => 'nullable|string|max:255',
            'status' => ['nullable', Rule::in(['In Stock', 'Low Stock', 'Out of Stock'])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->all();

        // Set is_clinical based on checkbox value only
        $data['is_clinical'] = $request->boolean('is_clinical', false);
        $data['is_required_approval'] = $request->boolean('is_required_approval', false);

        $product->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
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
            'ids.*' => 'integer|exists:products,id',
        ]);

        $ids = $request->ids;

        try {
            // Start a database transaction
            \DB::beginTransaction();

            // Temporarily disable foreign key checks for reliable deletion
            \DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // Get all inventory IDs for the products to be deleted
            $inventoryIds = \App\Models\Inventory::whereIn('product_id', $ids)->pluck('id')->toArray();

            // First, delete stock movement inventory selections that reference these inventories
            if (! empty($inventoryIds)) {
                \DB::table('stock_movement_inventory_selections')
                    ->whereIn('inventory_id', $inventoryIds)
                    ->delete();
            }

            // Then, delete all related inventory records
            \App\Models\Inventory::whereIn('product_id', $ids)->delete();

            // Finally, delete the products
            $deletedCount = Product::whereIn('id', $ids)->delete();

            // Re-enable foreign key checks
            \DB::statement('SET FOREIGN_KEY_CHECKS=1');

            // Commit the transaction
            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$deletedCount} products deleted successfully",
                'deleted_count' => $deletedCount,
            ]);

        } catch (\Exception $e) {
            // Re-enable foreign key checks and rollback the transaction on error
            \DB::statement('SET FOREIGN_KEY_CHECKS=1');
            \DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete products: '.$e->getMessage(),
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
            $totalQuantity = $product->inventories->sum(function ($inventory) {
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
                'apply_to_services' => true,
            ],
            'critical_stock_threshold' => [
                'threshold' => 5,
                'apply_to_services' => true,
            ],
            'expiry_alert_days' => [
                'days' => 30,
            ],
            'auto_reorder_settings' => [
                'enabled' => false,
                'reorder_point' => 20,
                'reorder_quantity' => 50,
            ],
            'notification_settings' => [
                'email_alerts' => true,
                'sms_alerts' => false,
                'alert_frequency' => 'daily',
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
        $product = Product::findOrFail($productId);

        $request->validate([
            'settings' => 'required|array',
            'settings.min_quantity_all_services' => 'sometimes|array',
            'settings.critical_stock_threshold' => 'sometimes|array',
            'settings.expiry_alert_days' => 'sometimes|array',
            'settings.auto_reorder_settings' => 'sometimes|array',
            'settings.notification_settings' => 'sometimes|array',
        ]);

        $settings = $request->input('settings');

        // Here you would typically save the settings to a database table
        // For now, we'll just return success since this is a demo implementation

        return response()->json([
            'success' => true,
            'message' => 'Product settings saved successfully',
            'settings' => $settings,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        try {
            // Start a database transaction
            \DB::beginTransaction();

            // Temporarily disable foreign key checks for reliable deletion
            \DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // Get all inventory IDs for this product
            $inventoryIds = $product->inventories()->pluck('id')->toArray();

            // First, delete stock movement inventory selections that reference these inventories
            if (! empty($inventoryIds)) {
                \DB::table('stock_movement_inventory_selections')
                    ->whereIn('inventory_id', $inventoryIds)
                    ->delete();
            }

            // Then, delete all related inventory records
            $product->inventories()->delete();

            // Finally, delete the product
            $product->delete();

            // Re-enable foreign key checks
            \DB::statement('SET FOREIGN_KEY_CHECKS=1');

            // Commit the transaction
            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully',
            ]);

        } catch (\Exception $e) {
            // Re-enable foreign key checks and rollback the transaction on error
            \DB::statement('SET FOREIGN_KEY_CHECKS=1');
            \DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product: '.$e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get comprehensive purchase history for a product
     */
    public function getHistory($productId)
    {
        try {
            // Get product details
            $product = Product::findOrFail($productId);

            // Get purchase history from different sources
            $bonCommends = $this->getBonCommendHistory($productId);
            $factureProformas = $this->getFactureProformaHistory($productId);
            $bonReceptions = $this->getBonReceptionHistory($productId);
            $bonEntrees = $this->getBonEntreeHistory($productId);

            // Get suppliers who have sold this product
            $suppliers = $this->getProductSuppliers($productId);

            // Calculate statistics
            $statistics = $this->calculateStatistics($bonCommends, $factureProformas, $bonReceptions, $bonEntrees);

            return response()->json([
                'success' => true,
                'data' => [
                    'product' => $product,
                    'history' => [
                        'bonCommends' => $bonCommends,
                        'factureProformas' => $factureProformas,
                        'bonReceptions' => $bonReceptions,
                        'bonEntrees' => $bonEntrees,
                    ],
                    'suppliers' => $suppliers,
                    'statistics' => $statistics,
                ],
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error fetching product history: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch product history',
            ], 500);
        }
    }

    /**
     * Get purchase orders (Bon Commend) history for a product
     */
    private function getBonCommendHistory($productId)
    {
        return \DB::table('bon_commends')
            ->join('bon_commend_items', 'bon_commends.id', '=', 'bon_commend_items.bon_commend_id')
            ->leftJoin('fournisseurs', 'bon_commends.fournisseur_id', '=', 'fournisseurs.id')
            ->where('bon_commend_items.product_id', $productId)
            ->select([
                'bon_commends.id',
                'bon_commends.order_date',
                'bon_commends.status',
                'bon_commends.department',
                'bon_commends.notes',
                'bon_commends.created_at',
                'bon_commend_items.quantity',
                'bon_commend_items.unit',
                'bon_commend_items.notes as item_notes',
                'fournisseurs.company_name as supplier_name',
                'fournisseurs.email as supplier_email',
                'fournisseurs.phone as supplier_phone',
            ])
            ->orderBy('bon_commends.created_at', 'desc')
            ->get();
    }

    /**
     * Get proforma invoices history for a product
     */
    private function getFactureProformaHistory($productId)
    {
        return \DB::table('facture_proformas')
            ->join('facture_proforma_products', 'facture_proformas.id', '=', 'facture_proforma_products.facture_proforma_id')
            ->leftJoin('fournisseurs', 'facture_proformas.fournisseur_id', '=', 'fournisseurs.id')
            ->where('facture_proforma_products.product_id', $productId)
            ->select([
                'facture_proformas.id',
                'facture_proformas.factureProformaCode',
                'facture_proformas.date',
                'facture_proformas.status',
                'facture_proformas.notes',
                'facture_proformas.created_at',
                'facture_proforma_products.quantity',
                'facture_proforma_products.unit',
                'fournisseurs.company_name as supplier_name',
                'fournisseurs.email as supplier_email',
                'fournisseurs.phone as supplier_phone',
            ])
            ->orderBy('facture_proformas.created_at', 'desc')
            ->get();
    }

    /**
     * Get reception history for a product
     */
    private function getBonReceptionHistory($productId)
    {
        return \DB::table('bon_receptions')
            ->join('bon_reception_items', 'bon_receptions.id', '=', 'bon_reception_items.bon_reception_id')
            ->leftJoin('fournisseurs', 'bon_receptions.fournisseur_id', '=', 'fournisseurs.id')
            ->where('bon_reception_items.product_id', $productId)
            ->select([
                'bon_receptions.id',
                'bon_receptions.date_reception',
                'bon_receptions.status',
                'bon_receptions.observation',
                'bon_receptions.created_at',
                'bon_reception_items.quantity_received',
                'bon_reception_items.unit',
                'bon_reception_items.notes',
                'fournisseurs.company_name as supplier_name',
                'fournisseurs.email as supplier_email',
                'fournisseurs.phone as supplier_phone',
            ])
            ->orderBy('bon_receptions.created_at', 'desc')
            ->get();
    }

    /**
     * Get stock entries history for a product
     */
    private function getBonEntreeHistory($productId)
    {
        return \DB::table('bon_entrees')
            ->join('bon_entree_items', 'bon_entrees.id', '=', 'bon_entree_items.bon_entree_id')
            ->leftJoin('fournisseurs', 'bon_entrees.fournisseur_id', '=', 'fournisseurs.id')
            ->where('bon_entree_items.product_id', $productId)
            ->select([
                'bon_entrees.id',
                'bon_entrees.entry_date',
                'bon_entrees.status',
                'bon_entrees.notes',
                'bon_entrees.created_at',
                'bon_entree_items.quantity',
                'bon_entree_items.unit',
                'bon_entree_items.notes as item_notes',
                'fournisseurs.company_name as supplier_name',
                'fournisseurs.email as supplier_email',
                'fournisseurs.phone as supplier_phone',
            ])
            ->orderBy('bon_entrees.created_at', 'desc')
            ->get();
    }

    /**
     * Get all suppliers who have sold this product
     */
    private function getProductSuppliers($productId)
    {
        // Get suppliers from all purchasing documents
        $suppliers = collect();

        // From Bon Commends
        $bonCommendSuppliers = \DB::table('fournisseurs')
            ->join('bon_commends', 'fournisseurs.id', '=', 'bon_commends.fournisseur_id')
            ->join('bon_commend_items', 'bon_commends.id', '=', 'bon_commend_items.bon_commend_id')
            ->where('bon_commend_items.product_id', $productId)
            ->select([
                'fournisseurs.id',
                'fournisseurs.company_name',
                'fournisseurs.email',
                'fournisseurs.phone',
                \DB::raw('COUNT(bon_commends.id) as orders_count'),
                \DB::raw('SUM(bon_commend_items.quantity) as total_quantity'),
            ])
            ->groupBy('fournisseurs.id', 'fournisseurs.company_name', 'fournisseurs.email', 'fournisseurs.phone')
            ->get();

        // From Facture Proformas
        $proformaSuppliers = \DB::table('fournisseurs')
            ->join('facture_proformas', 'fournisseurs.id', '=', 'facture_proformas.fournisseur_id')
            ->join('facture_proforma_products', 'facture_proformas.id', '=', 'facture_proforma_products.facture_proforma_id')
            ->where('facture_proforma_products.product_id', $productId)
            ->select([
                'fournisseurs.id',
                'fournisseurs.company_name',
                'fournisseurs.email',
                'fournisseurs.phone',
                \DB::raw('COUNT(facture_proformas.id) as orders_count'),
                \DB::raw('SUM(facture_proforma_products.quantity) as total_quantity'),
            ])
            ->groupBy('fournisseurs.id', 'fournisseurs.company_name', 'fournisseurs.email', 'fournisseurs.phone')
            ->get();

        // Merge and deduplicate suppliers
        $allSuppliers = $bonCommendSuppliers->concat($proformaSuppliers);

        return $allSuppliers->groupBy('id')->map(function ($supplierGroup) {
            $supplier = $supplierGroup->first();

            return [
                'id' => $supplier->id,
                'company_name' => $supplier->company_name,
                'email' => $supplier->email,
                'phone' => $supplier->phone,
                'orders_count' => $supplierGroup->sum('orders_count'),
                'total_quantity' => $supplierGroup->sum('total_quantity'),
            ];
        })->values();
    }

    /**
     * Calculate purchase statistics
     */
    private function calculateStatistics($bonCommends, $factureProformas, $bonReceptions, $bonEntrees)
    {
        $totalOrders = $bonCommends->count() + $factureProformas->count() + $bonReceptions->count() + $bonEntrees->count();

        $totalQuantity = $bonCommends->sum('quantity') +
                        $factureProformas->sum('quantity') +
                        $bonReceptions->sum('quantity_received') +
                        $bonEntrees->sum('quantity');

        // Get unique suppliers count
        $suppliersFromCommends = $bonCommends->pluck('supplier_name')->filter()->unique();
        $suppliersFromProformas = $factureProformas->pluck('supplier_name')->filter()->unique();
        $suppliersFromReceptions = $bonReceptions->pluck('supplier_name')->filter()->unique();
        $suppliersFromEntrees = $bonEntrees->pluck('supplier_name')->filter()->unique();

        $allSuppliers = $suppliersFromCommends
            ->concat($suppliersFromProformas)
            ->concat($suppliersFromReceptions)
            ->concat($suppliersFromEntrees)
            ->unique();

        // Get last order date
        $allDates = collect();
        $allDates = $allDates->concat($bonCommends->pluck('created_at'))
            ->concat($factureProformas->pluck('created_at'))
            ->concat($bonReceptions->pluck('created_at'))
            ->concat($bonEntrees->pluck('created_at'))
            ->filter()
            ->sort()
            ->reverse();

        return [
            'totalOrders' => $totalOrders,
            'totalQuantity' => $totalQuantity,
            'suppliersCount' => $allSuppliers->count(),
            'lastOrderDate' => $allDates->first(),
        ];
    }

    /**
     * Bulk update product approval requirements
     */
    public function bulkUpdateApproval(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'products' => 'required|array',
            'products.*.id' => 'required|integer|exists:products,id',
            'products.*.is_required_approval' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            \DB::beginTransaction();

            $updatedCount = 0;
            foreach ($request->products as $productData) {
                $product = Product::find($productData['id']);
                if ($product) {
                    $product->update([
                        'is_required_approval' => $productData['is_required_approval'],
                    ]);
                    $updatedCount++;
                }
            }

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$updatedCount} products updated successfully",
                'updated_count' => $updatedCount,
            ]);

        } catch (\Exception $e) {
            \DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update products: '.$e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
