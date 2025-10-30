<?php

namespace App\Http\Controllers\Purchasing;

use App\Models\Product;
use App\Models\PharmacyProduct;
use App\Services\Purchasing\PurchasingProductService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PurchasingProductController extends \App\Http\Controllers\Controller
{
    private PurchasingProductService $service;

    public function __construct(PurchasingProductService $service)
    {
        $this->service = $service;
    }

    /**
     * Display combined list from both stock and pharmacy tables
     * GET /api/products (for stock)
     * GET /api/pharmacy-products (for pharmacy)
     * GET /api/purchasing/products (for combined)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Determine which endpoint was called
            $path = $request->path();
            
            if (str_contains($path, 'pharmacy-products')) {
                // Pharmacy products only
                $products = $this->service->getPharmacyProducts($request->all());
            } elseif (str_contains($path, 'purchasing/products')) {
                // Combined from both tables
                $products = $this->service->getProductsFromBothTables($request->all());
            } else {
                // Stock products only (default)
                $products = $this->service->getStockProducts($request->all());
            }

            return response()->json([
                'success' => true,
                'data' => $products,
                'count' => count($products),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch products',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created product
     * Routes to correct table based on is_clinical flag
     * POST /api/purchasing/products with { is_clinical: true/false }
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'category' => 'nullable|string',
                'quantity' => 'nullable|integer|min:0',
                'unit_price' => 'nullable|numeric|min:0',
                'is_clinical' => 'required|boolean',
                'code_interne' => 'nullable|string',
                'forme' => 'nullable|string',
                'generic_name' => 'nullable|string',
                'brand_name' => 'nullable|string',
            ]);

            // Ensure is_clinical is boolean
            $validated['is_clinical'] = (bool) $validated['is_clinical'];

            // Normalize is_clinical to is_clinic for service layer
            $validated['is_clinic'] = $validated['is_clinical'];

            $product = $this->service->createProduct($validated);

            // Add is_clinical and source to response
            $productData = $product->toArray();
            $productData['is_clinical'] = $validated['is_clinical'];
            $productData['source'] = $validated['is_clinical'] ? 'pharmacy' : 'stock';

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'data' => $productData,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified product
     * GET /api/products/{id} (stock)
     * GET /api/pharmacy-products/{id} (pharmacy)
     */
    public function show($id): JsonResponse
    {
        try {
            // Try to find in stock table first
            $product = Product::find($id);
            
            if (!$product) {
                // Try pharmacy table
                $product = PharmacyProduct::find($id);
            }

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $product,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified product
     * Routes to correct table based on source parameter
     * PUT /api/purchasing/products/{id}?source=pharmacy|stock
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'category' => 'nullable|string',
                'quantity' => 'nullable|integer|min:0',
                'unit_price' => 'nullable|numeric|min:0',
                'code_interne' => 'nullable|string',
                'forme' => 'nullable|string',
                'generic_name' => 'nullable|string',
                'brand_name' => 'nullable|string',
                'source' => 'nullable|in:stock,pharmacy',
            ]);

            // Get source from validated data or query parameter
            $source = $validated['source'] ?? $request->query('source', 'stock');
            
            // Validate source
            if (!in_array($source, ['stock', 'pharmacy'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid source parameter. Must be "stock" or "pharmacy"',
                ], 400);
            }

            $product = $this->service->updateProduct($id, $source, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $product,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete the specified product
     * DELETE /api/purchasing/products/{id}?source=pharmacy|stock
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            // Get source from query parameter
            $source = $request->query('source', 'stock');
            
            // Validate source
            if (!in_array($source, ['stock', 'pharmacy'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid source parameter. Must be "stock" or "pharmacy"',
                ], 400);
            }

            $this->service->deleteProduct($id, $source);

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
