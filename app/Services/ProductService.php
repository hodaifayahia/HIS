<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PharmacyProduct;
use Illuminate\Database\Eloquent\Collection;

class ProductService
{
    /**
     * Get all products from both Product and PharmacyProduct tables
     * @param string $search
     * @param string $category
     * @param array $userServices
     * @param boolean $isAdmin
     * @return Collection
     */
    public function getAllProducts($search = null, $category = null, $userServices = [], $isAdmin = false)
    {
        // Get regular products
        $regularProducts = $this->getRegularProducts($search, $category, $userServices, $isAdmin);

        // Get pharmacy products
        $pharmacyProducts = $this->getPharmacyProducts($search, $category, $userServices, $isAdmin);

        // Combine and sort by created_at (newest first)
        $allProducts = $regularProducts->merge($pharmacyProducts)
            ->sortByDesc('created_at')
            ->values();

        return $allProducts;
    }

    /**
     * Get regular products from products table
     */
    private function getRegularProducts($search = null, $category = null, $userServices = [], $isAdmin = false)
    {
        $query = Product::query();

        // Filter by user's assigned services (only admin/SuperAdmin see all)
        if (!$isAdmin && !empty($userServices)) {
            $query->whereHas('inventories.stockage', function ($q) use ($userServices) {
                $q->whereIn('service_id', $userServices);
            });
        }

        // Search
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('code_interne', 'like', "%{$search}%")
                    ->orWhere('designation', 'like', "%{$search}%")
                    ->orWhere('nom_commercial', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if (!empty($category)) {
            $query->where('category', $category);
        }

        // Filter to non-clinical products
        $query->where('is_clinical', false);

        return $query->get();
    }

    /**
     * Get pharmacy products from pharmacy_products table
     */
    private function getPharmacyProducts($search = null, $category = null, $userServices = [], $isAdmin = false)
    {
        $query = PharmacyProduct::query();

        // Filter by user's assigned services (only admin/SuperAdmin see all)
        if (!$isAdmin && !empty($userServices)) {
            $query->whereHas('pharmacyInventories.pharmacyStockage', function ($q) use ($userServices) {
                $q->whereIn('service_id', $userServices);
            });
        }

        // Search
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('generic_name', 'like', "%{$search}%")
                    ->orWhere('brand_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if (!empty($category)) {
            $query->where('category', $category);
        }

        // Get only clinical products
        // Note: PharmacyProduct is inherently clinical, but we keep this for consistency
        return $query->get();
    }

    /**
     * Find a product by ID from either table
     * Returns an array with 'product' and 'type' (regular or pharmacy)
     */
    public function findProduct($id)
    {
        // Try to find in regular products first
        $product = Product::find($id);
        if ($product) {
            return [
                'product' => $product,
                'type' => 'regular',
                'model' => Product::class
            ];
        }

        // Try pharmacy products
        $product = PharmacyProduct::find($id);
        if ($product) {
            return [
                'product' => $product,
                'type' => 'pharmacy',
                'model' => PharmacyProduct::class
            ];
        }

        return null;
    }

    /**
     * Create a product in the appropriate table based on is_clinical flag
     */
    public function createProduct($data)
    {
        $isClinical = $data['is_clinical'] ?? false;

        if ($isClinical) {
            // Create in pharmacy_products table
            return PharmacyProduct::create($data);
        } else {
            // Create in products table
            return Product::create($data);
        }
    }

    /**
     * Update a product in the appropriate table
     */
    public function updateProduct($id, $data)
    {
        $productData = $this->findProduct($id);

        if (!$productData) {
            return null;
        }

        $productData['product']->update($data);
        return $productData['product'];
    }

    /**
     * Delete a product from the appropriate table
     */
    public function deleteProduct($id)
    {
        $productData = $this->findProduct($id);

        if (!$productData) {
            return false;
        }

        return $productData['product']->delete();
    }
}
