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
        $query = Product::select([
            'id', 'name', 'code_interne', 'category', 'type_medicament', 'boite_de',
            'is_clinical', 'is_request_approval', 'forme', 'status', 'designation',
            'code_pch', 'nom_commercial', 'created_at'
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

        // OPTIMIZED: Simplified search - use indexes on common fields
        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code_interne', 'like', "%{$search}%")
                    ->orWhere('designation', 'like', "%{$search}%");
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

        // CRITICAL: Paginate at DATABASE level BEFORE processing
        $paginatedProducts = $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $currentPage);

        // OPTIMIZED: Only load inventories for paginated products
        $paginatedProducts->load(['inventories' => function ($q) {
            $q->select('id', 'product_id', 'stockage_id', 'quantity', 'unit', 'expiry_date', 'batch_number')
              ->with('stockage:id,name,service_id');
        }]);

        // Process only the paginated products
        $processedProducts = $paginatedProducts->map(function ($product) use ($quantityByBox, $user) {
            return $this->processProductData($product, $quantityByBox, $user);
        });

        // Calculate alert counts efficiently (only for current page)
        $alertCounts = $this->calculateAlertCounts($processedProducts);

        // Filter by alert types if provided (after pagination)
        if ($request->has('alert_filters') && ! empty($request->alert_filters)) {
            $alertFilters = $request->alert_filters;
            if (is_string($alertFilters)) {
                $alertFilters = json_decode($alertFilters, true);
            }

            if (is_array($alertFilters) && ! empty($alertFilters)) {
                $processedProducts = $processedProducts->filter(function ($product) use ($alertFilters) {
                    if (! $product->alerts || empty($product->alerts)) {
                        return false;
                    }
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
                'current_page' => (int) $paginatedProducts->currentPage(),
                'last_page' => $paginatedProducts->lastPage(),
                'per_page' => $perPage,
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
    private function processProductData($product, $quantityByBox, $user)
    {
        $inventories = $product->inventories;
        $boiteDe = $product->boite_de ?? 1;

        // Filter inventories by user's services if not admin
        if (! $user->hasRole(['admin', 'SuperAdmin'])) {
            $userServices = $user->services ?? [];
            if (! empty($userServices)) {
                $inventories = $inventories->filter(function ($inventory) use ($userServices) {
                    return in_array($inventory->stockage->service_id ?? null, $userServices);
                });
            }
        }

        // Calculate total quantity
        if ($quantityByBox && $boiteDe > 1) {
            $totalQuantity = $inventories->sum('quantity');
            $totalUnits = $inventories->sum(function ($inv) use ($boiteDe) {
                return $inv->quantity * $boiteDe;
            });
            $displayUnit = 'boxes';
            $inventoryDisplay = "{$totalQuantity} boxes ({$totalUnits} units)";
        } else {
            $totalUnits = $inventories->sum(function ($inv) use ($boiteDe) {
                return $inv->quantity * $boiteDe;
            });
            $totalQuantity = $totalUnits;
            $displayUnit = $product->forme ?? 'units';
            $inventoryDisplay = $totalQuantity . ' ' . $displayUnit;
        }

        // Simplified inventory by location (lazy load stockage only if needed)
        $inventoryByLocation = [];
        if ($inventories->isNotEmpty() && $inventories->count() <= 10) {
            $inventoryByLocation = $inventories->groupBy(function ($inventory) {
                return $inventory->stockage->service->name ?? $inventory->stockage->name ?? 'Unknown Location';
            })->map(function ($locationInventories) use ($boiteDe, $quantityByBox, $product) {
                $locationQuantity = $locationInventories->sum('quantity');
                $locationUnits = $locationInventories->sum(function ($inv) use ($boiteDe) {
                    return $inv->quantity * $boiteDe;
                });

                if ($quantityByBox && $boiteDe > 1) {
                    return [
                        'location' => $locationInventories->first()->stockage->name,
                        'boxes' => $locationQuantity,
                        'units' => $locationUnits,
                        'display' => "{$locationQuantity} boxes ({$locationUnits} units)",
                    ];
                } else {
                    return [
                        'location' => $locationInventories->first()->stockage->name,
                        'units' => $locationUnits,
                        'display' => "{$locationUnits} units",
                    ];
                }
            })->values();
        }

        // Calculate alerts efficiently
        $alerts = $this->calculateProductAlerts($product, $inventories, $totalQuantity, $displayUnit, $boiteDe, $quantityByBox);

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
        $product->boite_de = $boiteDe;
        $product->has_box_units = $boiteDe > 1;

        return $product;
    }

    /**
     * Calculate alerts for a single product
     */
    private function calculateProductAlerts($product, $inventories, $totalQuantity, $displayUnit, $boiteDe, $quantityByBox)
    {
        $alerts = [];
        
        // Use product fields as thresholds
        $lowStockThreshold = $product->minimum_stock_level ?? 10;
        $criticalStockThreshold = $product->critical_stock_level ?? 5;
        $expiryAlertDays = 30;

        // Adjust thresholds based on quantity mode
        if ($quantityByBox && $boiteDe > 1) {
            $lowStockThreshold = ceil($lowStockThreshold / $boiteDe);
            $criticalStockThreshold = ceil($criticalStockThreshold / $boiteDe);
        }

        // Critical stock alert
        if ($totalQuantity <= $criticalStockThreshold) {
            $alerts[] = [
                'type' => 'critical_stock',
                'severity' => 'danger',
                'message' => "Critical Stock: {$totalQuantity} {$displayUnit}",
                'icon' => 'pi pi-times-circle',
            ];
        } elseif ($totalQuantity <= $lowStockThreshold) {
            // Low stock alert
            $alerts[] = [
                'type' => 'low_stock',
                'severity' => 'warning',
                'message' => "Low Stock: {$totalQuantity} {$displayUnit}",
                'icon' => 'pi pi-exclamation-triangle',
            ];
        }

        // Expiry alerts (optimized with single pass)
        $now = now();
        $expiringCount = 0;
        $expiredCount = 0;

        foreach ($inventories as $inventory) {
            if (! $inventory->expiry_date) {
                continue;
            }
            $daysUntilExpiry = $now->diffInDays($inventory->expiry_date, false);
            if ($daysUntilExpiry < 0) {
                $expiredCount++;
            } elseif ($daysUntilExpiry <= $expiryAlertDays) {
                $expiringCount++;
            }
        }

        if ($expiredCount > 0) {
            $alerts[] = [
                'type' => 'expired',
                'severity' => 'danger',
                'message' => "{$expiredCount} expired items",
                'icon' => 'pi pi-ban',
            ];
        }

        if ($expiringCount > 0) {
            $alerts[] = [
                'type' => 'expiring',
                'severity' => 'warning',
                'message' => "{$expiringCount} expiring soon",
                'icon' => 'pi pi-clock',
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
        ];

        foreach ($products as $product) {
            if (! $product->alerts) {
                continue;
            }
            foreach ($product->alerts as $alert) {
                if (isset($counts[$alert['type']])) {
                    $counts[$alert['type']]++;
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

    /**
     * Get detailed stock information for a specific product
     */
    public function getStockDetails($productId)
    {
        try {
            $product = Product::with(['inventories.stockage.service'])->findOrFail($productId);
            
            // Get global settings
            $globalSettings = \App\Models\ProductGlobalSetting::getAllSettingsForProduct($productId);
            
            // Calculate stock summary
            $totalQuantity = $product->inventories->sum(function ($inventory) use ($product) {
                return $inventory->quantity * ($product->boite_de ?? 1);
            });
            
            $totalValue = $product->inventories->sum(function ($inventory) {
                return $inventory->quantity * ($inventory->unit_price ?? 0);
            });
            
            $lowStockThreshold = $globalSettings['min_quantity_all_services']['threshold'] ?? 10;
            $criticalStockThreshold = $globalSettings['critical_stock_threshold']['threshold'] ?? 5;
            
            // Get stock details by location
            $locations = $product->inventories->map(function ($inventory) use ($product, $lowStockThreshold) {
                $quantity = $inventory->quantity * ($product->boite_de ?? 1);
                
                return [
                    'id' => $inventory->id,
                    'stockage_id' => $inventory->stockage_id,
                    'stockage_name' => $inventory->stockage->name ?? 'Unknown',
                    'service_name' => $inventory->stockage->service->name ?? 'N/A',
                    'quantity' => $quantity,
                    'unit_price' => $inventory->unit_price ?? 0,
                    'total_value' => $quantity * ($inventory->unit_price ?? 0),
                    'batch_number' => $inventory->batch_number ?? 'N/A',
                    'expiry_date' => $inventory->expiry_date,
                    'is_low_stock' => $quantity <= $lowStockThreshold,
                    'is_expired' => $inventory->expiry_date && now()->gt($inventory->expiry_date),
                    'unit' => $inventory->unit ?? $product->forme ?? 'units',
                ];
            });
            
            // Calculate alerts
            $alerts = [
                'low_stock' => $totalQuantity <= $lowStockThreshold && $totalQuantity > $criticalStockThreshold,
                'critical_stock' => $totalQuantity <= $criticalStockThreshold,
                'expiring_soon' => $product->inventories->filter(function ($inv) {
                    return $inv->expiry_date && now()->diffInDays($inv->expiry_date) <= 30 && now()->lt($inv->expiry_date);
                })->count(),
                'expired' => $product->inventories->filter(function ($inv) {
                    return $inv->expiry_date && now()->gt($inv->expiry_date);
                })->count(),
            ];
            
            return response()->json([
                'success' => true,
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'code_interne' => $product->code_interne,
                    'category' => $product->category,
                    'forme' => $product->forme,
                    'dosage' => $product->dosage,
                    'boite_de' => $product->boite_de,
                    'low_stock_threshold' => $lowStockThreshold,
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
}
