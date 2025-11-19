<?php

namespace App\Services\Purchsing\order;

use App\Models\CONFIGURATION\Service;
use App\Models\Fournisseur;
use App\Models\PharmacyProduct;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ServiceDemandDataService
{
    /**
     * Get all active services
     */
    public function getServices()
    {
        try {
            $services = Service::where('is_active', true)
                ->select('id', 'name', 'service_abv as service_code', 'description')
                ->orderBy('name')
                ->get();

            return $services;
        } catch (\Exception $e) {
            Log::error('Error fetching services: '.$e->getMessage());
            throw new \Exception('Failed to fetch services');
        }
    }

    /**
     * Get products with optional search and pagination
     */
    public function getProducts($type = null, $search = '', $page = 1, $perPage = 50)
    {
        try {
            $page = max(1, (int) $page);
            $perPage = min(100, max(10, (int) $perPage));

            $allProducts = collect();
            $totalCount = 0;
            $lastPage = 1;
            $currentPage = $page;

            // Fetch stock products
            if ($type === 'stock' || $type === null) {
                $query = Product::query();

                if (! empty($search)) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('code_interne', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%")
                            ->orWhere('designation', 'like', "%{$search}%")
                            ->orWhere('nom_commercial', 'like', "%{$search}%")
                            ->orWhere('code_pch', 'like', "%{$search}%");
                    });
                }

                $stockProducts = $query
                    ->select('id', 'name', 'code_interne', 'code', 'designation', 'forme', 'nom_commercial', 'boite_de', 'category')
                    ->orderBy('name', 'asc')
                    ->paginate($perPage, ['*'], 'page', $page);

                $stockMapped = $stockProducts->getCollection()->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name ?? $product->designation ?? 'Unnamed Product',
                        'code_interne' => $product->code_interne,
                        'code' => $product->code,
                        'product_code' => $product->code_interne ?? $product->code,
                        'designation' => $product->designation,
                        'unit' => $product->forme ?? 'units',
                        'forme' => $product->forme,
                        'nom_commercial' => $product->nom_commercial,
                        'boite_de' => $product->boite_de ?? 1,
                        'category' => $product->category,
                        'type' => 'stock',
                        'display_name' => ($product->name ?? $product->designation).' (Stock)',
                    ];
                });

                $allProducts = $stockMapped;
                $totalCount = $stockProducts->total();
                $lastPage = $stockProducts->lastPage();
                $currentPage = $stockProducts->currentPage();
            }

            // Fetch pharmacy products
            elseif ($type === 'pharmacy') {
                $ppQuery = PharmacyProduct::query();

                if (! empty($search)) {
                    $ppQuery->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('generic_name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%")
                            ->orWhere('brand_name', 'like', "%{$search}%")
                            ->orWhere('barcode', 'like', "%{$search}%");
                    });
                }

                $pharmacyProducts = $ppQuery
                    ->select('id', 'name', 'generic_name', 'sku', 'brand_name', 'unit_of_measure', 'strength', 'strength_unit', 'dosage_form', 'units_per_package', 'category')
                    ->where('is_active', 1)
                    ->orderBy('name', 'asc')
                    ->paginate($perPage, ['*'], 'page', $page);

                $pharmacyMapped = $pharmacyProducts->getCollection()->map(function ($product) {
                    $displayName = $product->name ?? $product->generic_name ?? 'Unnamed Product';
                    if ($product->strength && $product->strength_unit) {
                        $displayName .= ' '.$product->strength.$product->strength_unit;
                    }

                    return [
                        'id' => $product->id,
                        'pharmacy_product_id' => $product->id,
                        'name' => $product->name ?? $product->generic_name,
                        'generic_name' => $product->generic_name,
                        'sku' => $product->sku,
                        'product_code' => $product->sku,
                        'brand_name' => $product->brand_name,
                        'unit' => $product->unit_of_measure ?? 'units',
                        'unit_of_measure' => $product->unit_of_measure,
                        'strength' => $product->strength,
                        'strength_unit' => $product->strength_unit,
                        'dosage_form' => $product->dosage_form,
                        'units_per_package' => $product->units_per_package ?? 1,
                        'category' => $product->category,
                        'type' => 'pharmacy',
                        'display_name' => $displayName.' (Pharmacy)',
                    ];
                });

                $allProducts = $pharmacyMapped;
                $totalCount = $pharmacyProducts->total();
                $lastPage = $pharmacyProducts->lastPage();
                $currentPage = $pharmacyProducts->currentPage();
            }

            return [
                'data' => $allProducts->values()->all(),
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $totalCount,
                'meta' => [
                    'total' => $totalCount,
                    'count' => $allProducts->count(),
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching products: '.$e->getMessage());
            throw new \Exception('Failed to fetch products');
        }
    }

    /**
     * Get service demand statistics
     * OPTIMIZED: Single query instead of 6 separate COUNT queries
     */
    public function getStats()
    {
        try {
            // Single query to get all counts at once
            $stats = DB::table('service_demand_purchasings')
                ->selectRaw('
                    COUNT(*) as total_demands,
                    SUM(CASE WHEN status = "draft" THEN 1 ELSE 0 END) as draft_demands,
                    SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent_demands,
                    SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved_demands,
                    SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected_demands
                ')
                ->first();

            // Get total items count separately (still fast)
            $totalItems = DB::table('service_demand_purchasing_items')->count();

            return [
                'total_demands' => (int) $stats->total_demands,
                'draft_demands' => (int) $stats->draft_demands,
                'sent_demands' => (int) $stats->sent_demands,
                'approved_demands' => (int) $stats->approved_demands,
                'rejected_demands' => (int) $stats->rejected_demands,
                'total_items' => $totalItems,
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching stats: '.$e->getMessage());
            throw new \Exception('Failed to fetch statistics');
        }
    }

    /**
     * Get purchasing suggestions based on inventory
     */
    public function getSuggestions()
    {
        try {
            $criticalLowStock = collect();
            $lowStock = collect();
            $expiringSoon = collect();
            $expired = collect();
            $controlledSubstances = collect();

            // Fetch stock data from inventory
            try {
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
                        'suggested_quantity' => max(50, $quantity * 2),
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
                    } elseif ($quantity <= 20) {
                        // Low stock
                        $productData['reason'] = 'Low stock';
                        $productData['suggested_quantity'] = max(50, $quantity * 3);
                        $lowStock->push($productData);
                    }

                    // Check expiry dates
                    if ($item->expiry_date) {
                        $expiryDate = \Carbon\Carbon::parse($item->expiry_date);
                        $now = \Carbon\Carbon::now();
                        $thirtyDaysFromNow = $now->copy()->addDays(30);

                        if ($expiryDate->lte($now)) {
                            $productData['reason'] = 'Expired stock needs replacement';
                            $productData['suggested_quantity'] = max(50, $quantity);
                            $expired->push($productData);
                        } elseif ($expiryDate->lte($thirtyDaysFromNow)) {
                            $productData['reason'] = 'Expiring soon, replacement needed';
                            $productData['suggested_quantity'] = max(30, $quantity);
                            $expiringSoon->push($productData);
                        }
                    }

                    // Controlled substances
                    if (stripos($item->product_name, 'morphine') !== false ||
                        stripos($item->product_name, 'opioid') !== false ||
                        stripos($item->product_name, 'narcotic') !== false) {
                        $productData['reason'] = 'Controlled substance monitoring';
                        $controlledSubstances->push($productData);
                    }
                }

                $criticalLowStock = $criticalLowStock->unique('product_id')->values();
                $lowStock = $lowStock->unique('product_id')->values();
                $expiringSoon = $expiringSoon->unique('product_id')->values();
                $expired = $expired->unique('product_id')->values();
                $controlledSubstances = $controlledSubstances->unique('product_id')->values();
            } catch (\Exception $e) {
                Log::warning('Error fetching stock data for suggestions: '.$e->getMessage());
            }

            return [
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
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching suggestions: '.$e->getMessage());
            throw new \Exception('Failed to fetch suggestions');
        }
    }

    /**
     * Get all active fournisseurs
     */
    public function getFournisseurs()
    {
        try {
            $fournisseurs = Fournisseur::select('id', 'company_name', 'contact_person', 'email', 'phone')
                ->where('is_active', true)
                ->orderBy('company_name')
                ->get();

            return $fournisseurs;
        } catch (\Exception $e) {
            Log::error('Error fetching fournisseurs: '.$e->getMessage());
            throw new \Exception('Failed to fetch suppliers');
        }
    }
}
