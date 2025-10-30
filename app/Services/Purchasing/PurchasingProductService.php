<?php

namespace App\Services\Purchasing;

use App\Models\Product;
use App\Models\PharmacyProduct;
use Illuminate\Pagination\Paginate;

class PurchasingProductService
{
    /**
     * Get products from both stock and pharmacy tables
     * 
     * @param array $filters
     * @return array
     */
    public function getProductsFromBothTables(array $filters = []): array
    {
        // Get stock products (is_clinic = false)
        $stockProducts = $this->getStockProducts($filters);
        
        // Get pharmacy products (is_clinic = true)
        $pharmacyProducts = $this->getPharmacyProducts($filters);
        
        // Combine and return
        return array_merge($stockProducts, $pharmacyProducts);
    }

    /**
     * Get stock products
     * 
     * @param array $filters
     * @return array
     */
    public function getStockProducts(array $filters = []): array
    {
        $query = Product::where('is_clinical', false);

        // Apply search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        // Apply category filter
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        $products = $query->get()->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'category' => $product->category,
                'quantity' => $this->getTotalStockQuantity($product->id),
                'unit_price' => 0, // Stock products may not have unit_price
                'is_clinical' => false, // Changed from is_clinic to is_clinical for frontend
                'is_clinic' => false, // Keep for backward compatibility
                'source' => 'stock',
                'code_interne' => $product->code_interne ?? null,
                'forme' => $product->forme ?? null,
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at,
            ];
        })->toArray();

        return $products;
    }

    /**
     * Get pharmacy products
     * 
     * @param array $filters
     * @return array
     */
    public function getPharmacyProducts(array $filters = []): array
    {
        $query = PharmacyProduct::query();

        // Apply search filter
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

        // Apply category filter
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        $products = $query->get()->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'category' => $product->category,
                'quantity' => $this->getTotalPharmacyQuantity($product->id),
                'unit_price' => $product->unit_cost ?? 0,
                'is_clinical' => true, // Changed from is_clinic to is_clinical for frontend
                'is_clinic' => true, // Keep for backward compatibility
                'source' => 'pharmacy',
                'code_interne' => $product->code_interne ?? null,
                'forme' => $product->forme ?? null,
                'generic_name' => $product->generic_name ?? null,
                'brand_name' => $product->brand_name ?? null,
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at,
            ];
        })->toArray();

        return $products;
    }

    /**
     * Create product in correct table based on is_clinic flag
     * 
     * @param array $data
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function createProduct(array $data)
    {
        // If is_clinic is true, create in pharmacy table
        if ($data['is_clinic'] ?? false) {
            return PharmacyProduct::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'category' => $data['category'] ?? null,
                'unit_cost' => $data['unit_price'] ?? 0,
                'selling_price' => $data['unit_price'] ?? 0,
                'code_interne' => $data['code_interne'] ?? null,
                'forme' => $data['forme'] ?? null,
                'generic_name' => $data['generic_name'] ?? null,
                'brand_name' => $data['brand_name'] ?? null,
            ]);
        }

        // Otherwise, create in stock table with is_clinical = false
        return Product::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? null,
            'is_clinical' => false,  // Explicitly set to false for stock products
            'code_interne' => $data['code_interne'] ?? null,
            'forme' => $data['forme'] ?? null,
        ]);
    }

    /**
     * Update product in correct table
     * 
     * @param int $id
     * @param string $source
     * @param array $data
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function updateProduct(int $id, string $source, array $data)
    {
        if ($source === 'pharmacy') {
            $product = PharmacyProduct::findOrFail($id);
            $product->update([
                'name' => $data['name'] ?? $product->name,
                'description' => $data['description'] ?? $product->description,
                'category' => $data['category'] ?? $product->category,
                'unit_cost' => $data['unit_price'] ?? $product->unit_cost,
                'selling_price' => $data['unit_price'] ?? $product->selling_price,
                'code_interne' => $data['code_interne'] ?? $product->code_interne,
                'forme' => $data['forme'] ?? $product->forme,
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
                'forme' => $data['forme'] ?? $product->forme,
            ]);
        }

        return $product;
    }

    /**
     * Delete product from correct table
     * 
     * @param int $id
     * @param string $source
     * @return bool
     */
    public function deleteProduct(int $id, string $source): bool
    {
        if ($source === 'pharmacy') {
            $product = PharmacyProduct::findOrFail($id);
        } else {
            $product = Product::findOrFail($id);
        }

        return $product->delete();
    }

    /**
     * Get total quantity for stock product
     * 
     * @param int $productId
     * @return int
     */
    private function getTotalStockQuantity(int $productId): int
    {
        $product = Product::find($productId);
        if (!$product) {
            return 0;
        }

        return $product->inventories()->sum('quantity') * ($product->boite_de ?? 1);
    }

    /**
     * Get total quantity for pharmacy product
     * 
     * @param int $productId
     * @return int
     */
    private function getTotalPharmacyQuantity(int $productId): int
    {
        $product = PharmacyProduct::find($productId);
        if (!$product) {
            return 0;
        }

        $unitsPerPackage = $product->units_per_package ?? 1;
        return $product->pharmacyInventories()->sum('quantity') * $unitsPerPackage;
    }
}
