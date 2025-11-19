<?php

namespace App\Services\Purchasing;

use App\Models\Product;
use App\Models\PharmacyProduct;
use Illuminate\Support\Facades\DB;

class PurchasingProductService
{
    /**
     * Get products from both stock and pharmacy tables
     * HIGHLY OPTIMIZED: Database-level pagination, minimal queries
     */
    public function getProductsFromBothTables(array $filters = [], int $page = 1, int $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        
        // Build optimized queries
        $stockQuery = $this->buildStockProductsQuery($filters);
        $pharmacyQuery = $this->buildPharmacyProductsQuery($filters);
        
        // Get counts efficiently
        $stockCount = $stockQuery->count();
        $pharmacyCount = $pharmacyQuery->count();
        $totalCount = $stockCount + $pharmacyCount;
        
        // Determine how to split the page between both tables
        $stockProducts = [];
        $pharmacyProducts = [];
        
        if ($offset < $stockCount) {
            // We need some stock products
            $stockTake = min($perPage, $stockCount - $offset);
            $stockProducts = $stockQuery
                ->offset($offset)
                ->limit($stockTake)
                ->get()
                ->map(fn($p) => $this->formatStockProduct($p))
                ->toArray();
            
            // If we need more to fill the page, get from pharmacy
            $remaining = $perPage - $stockTake;
            if ($remaining > 0 && $pharmacyCount > 0) {
                $pharmacyProducts = $pharmacyQuery
                    ->limit($remaining)
                    ->get()
                    ->map(fn($p) => $this->formatPharmacyProduct($p))
                    ->toArray();
            }
        } else {
            // We're past stock products, only get pharmacy
            $pharmacyOffset = $offset - $stockCount;
            $pharmacyProducts = $pharmacyQuery
                ->offset($pharmacyOffset)
                ->limit($perPage)
                ->get()
                ->map(fn($p) => $this->formatPharmacyProduct($p))
                ->toArray();
        }
        
        $allProducts = array_merge($stockProducts, $pharmacyProducts);
        
        return [
            'data' => $allProducts,
            'total' => $totalCount,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($totalCount / $perPage),
            'has_more' => ($offset + $perPage) < $totalCount,
            'meta' => [
                'from' => $offset + 1,
                'to' => min($offset + count($allProducts), $totalCount),
                'total' => $totalCount,
                'current_page' => $page,
                'last_page' => ceil($totalCount / $perPage),
                'per_page' => $perPage,
            ]
        ];
    }

    private function buildStockProductsQuery(array $filters = [])
    {
        $query = Product::select([
            'id', 'name', 'description', 'category', 'code_interne', 
            'code_pch', 'designation', 'type_medicament', 'forme', 'boite_de',
            'nom_commercial', 'is_clinical', 'created_at', 'updated_at'
        ])->where('is_clinical', false);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        return $query->orderBy('created_at', 'desc');
    }

    private function buildPharmacyProductsQuery(array $filters = [])
    {
        $query = PharmacyProduct::select([
            'id', 'name', 'description', 'category', 'code_interne', 
            'code_pch', 'designation', 'type_medicament', 'forme', 'boite_de',
            'nom_commercial', 'generic_name', 'brand_name', 'unit_cost', 
            'units_per_package', 'is_clinical', 'created_at', 'updated_at'
        ]);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('generic_name', 'like', "%{$search}%")
                  ->orWhere('brand_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        return $query->orderBy('created_at', 'desc');
    }

    private function formatStockProduct($product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'category' => $product->category,
            'quantity' => 0,
            'unit_price' => 0,
            'is_clinical' => false,
            'is_clinic' => false,
            'source' => 'stock',
            'code_interne' => $product->code_interne,
            'code_pch' => $product->code_pch,
            'designation' => $product->designation,
            'type_medicament' => $product->type_medicament,
            'forme' => $product->forme,
            'boite_de' => $product->boite_de,
            'nom_commercial' => $product->nom_commercial,
            'created_at' => $product->created_at,
            'updated_at' => $product->updated_at,
        ];
    }

    private function formatPharmacyProduct($product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'category' => $product->category,
            'quantity' => 0,
            'unit_price' => $product->unit_cost ?? 0,
            'is_clinical' => true,
            'is_clinic' => true,
            'source' => 'pharmacy',
            'code_interne' => $product->code_interne,
            'code_pch' => $product->code_pch,
            'designation' => $product->designation,
            'type_medicament' => $product->type_medicament,
            'forme' => $product->forme,
            'boite_de' => $product->boite_de,
            'nom_commercial' => $product->nom_commercial,
            'generic_name' => $product->generic_name,
            'brand_name' => $product->brand_name,
            'created_at' => $product->created_at,
            'updated_at' => $product->updated_at,
        ];
    }

    public function getStockProductsQuery(array $filters = []): array
    {
        return $this->buildStockProductsQuery($filters)
            ->limit(100)
            ->get()
            ->map(fn($p) => $this->formatStockProduct($p))
            ->toArray();
    }

    public function getStockProducts(array $filters = []): array
    {
        return $this->getStockProductsQuery($filters);
    }

    public function getPharmacyProductsQuery(array $filters = []): array
    {
        return $this->buildPharmacyProductsQuery($filters)
            ->limit(100)
            ->get()
            ->map(fn($p) => $this->formatPharmacyProduct($p))
            ->toArray();
    }

    public function getPharmacyProducts(array $filters = []): array
    {
        return $this->getPharmacyProductsQuery($filters);
    }

    public function createProduct(array $data)
    {
        if ($data['is_clinic'] ?? false) {
            return PharmacyProduct::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'category' => $data['category'] ?? null,
                'unit_cost' => $data['unit_price'] ?? 0,
                'selling_price' => $data['unit_price'] ?? 0,
                'code_interne' => $data['code_interne'] ?? null,
                'code_pch' => $data['code_pch'] ?? null,
                'designation' => $data['designation'] ?? null,
                'type_medicament' => $data['type_medicament'] ?? null,
                'forme' => $data['forme'] ?? null,
                'boite_de' => $data['boite_de'] ?? null,
                'nom_commercial' => $data['nom_commercial'] ?? null,
                'generic_name' => $data['generic_name'] ?? null,
                'brand_name' => $data['brand_name'] ?? null,
                'is_clinical' => true,
            ]);
        }

        return Product::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? null,
            'is_clinical' => false,
            'code_interne' => $data['code_interne'] ?? null,
            'forme' => $data['forme'] ?? null,
        ]);
    }

    public function updateProduct(int $id, string $source, array $data)
    {
        // Remove source from data if present
        unset($data['source']);
        
        // Convert integer fields
        if (isset($data['code_interne'])) {
            $data['code_interne'] = (int) $data['code_interne'];
        }
        if (isset($data['boite_de'])) {
            $data['boite_de'] = (int) $data['boite_de'];
        }
        if (isset($data['is_clinical'])) {
            $data['is_clinical'] = (bool) $data['is_clinical'];
        }
        
        if ($source === 'pharmacy') {
            $product = PharmacyProduct::findOrFail($id);
            $product->update([
                'name' => $data['name'] ?? $product->name,
                'description' => $data['description'] ?? $product->description,
                'category' => $data['category'] ?? $product->category,
                'unit_cost' => $data['unit_price'] ?? $product->unit_cost,
                'selling_price' => $data['unit_price'] ?? $product->selling_price,
                'code_interne' => $data['code_interne'] ?? $product->code_interne,
                'code_pch' => $data['code_pch'] ?? $product->code_pch,
                'designation' => $data['designation'] ?? $product->designation,
                'type_medicament' => $data['type_medicament'] ?? $product->type_medicament,
                'forme' => $data['forme'] ?? $product->forme,
                'boite_de' => $data['boite_de'] ?? $product->boite_de,
                'nom_commercial' => $data['nom_commercial'] ?? $product->nom_commercial,
                'generic_name' => $data['generic_name'] ?? $product->generic_name,
                'brand_name' => $data['brand_name'] ?? $product->brand_name,
            ]);
        } else {
            $product = Product::findOrFail($id);
            $product->update([
                'name' => $data['name'] ?? $product->name,
                'description' => $data['description'] ?? $product->description,
                'category' => $data['category'] ?? $product->category,
                'code_interne' => $data['code_interne'] ?? $product->code_interne,
                'code_pch' => $data['code_pch'] ?? $product->code_pch,
                'designation' => $data['designation'] ?? $product->designation,
                'type_medicament' => $data['type_medicament'] ?? $product->type_medicament,
                'forme' => $data['forme'] ?? $product->forme,
                'boite_de' => $data['boite_de'] ?? $product->boite_de,
                'nom_commercial' => $data['nom_commercial'] ?? $product->nom_commercial,
            ]);
        }
        return $product;
    }

    public function deleteProduct(int $id, string $source): bool
    {
        $product = $source === 'pharmacy' 
            ? PharmacyProduct::findOrFail($id)
            : Product::findOrFail($id);
        return $product->delete();
    }
}
