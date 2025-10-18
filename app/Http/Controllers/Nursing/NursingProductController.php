<?php

namespace App\Http\Controllers\Nursing;

use App\Http\Controllers\Controller;
use App\Models\PharmacyProduct;
use App\Models\Product;
use Illuminate\Http\Request;

class NursingProductController extends Controller
{
    /**
     * Get products for nursing staff based on their service assignment
     * Includes both stock products and pharmacy products
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Get user's services through specializations
        $userServices = $this->getUserServices($user);

        // If no services found and not admin, return empty collection
        if (empty($userServices) && ! $user->hasRole(['admin', 'SuperAdmin'])) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'No products found for your assigned services',
            ]);
        }

        $products = collect();

        // Get stock products
        $stockProducts = $this->getStockProducts($request, $userServices, $user);

        // Get pharmacy products
        $pharmacyProducts = $this->getPharmacyProducts($request, $userServices, $user);

        // Combine and format products
        $allProducts = $stockProducts->concat($pharmacyProducts);

        // Apply pagination
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 15);
        $offset = ($page - 1) * $perPage;

        $paginatedProducts = $allProducts->slice($offset, $perPage)->values();

        return response()->json([
            'success' => true,
            'data' => $paginatedProducts,
            'pagination' => [
                'current_page' => (int) $page,
                'per_page' => (int) $perPage,
                'total' => $allProducts->count(),
                'last_page' => ceil($allProducts->count() / $perPage),
            ],
        ]);
    }

    /**
     * Get user's services through specializations
     */
    private function getUserServices($user)
    {
        if ($user->hasRole(['admin', 'SuperAdmin'])) {
            return []; // Will handle differently for admins
        }

        return $user->activeSpecializations()
            ->with('specialization.service')
            ->get()
            ->pluck('specialization.service.id')
            ->filter()
            ->unique()
            ->toArray();
    }

    /**
     * Get stock products based on user services
     */
    private function getStockProducts(Request $request, array $userServices, $user)
    {
        $query = Product::with(['inventories.stockage.service']);

        // Apply service filtering
        if (! $user->hasRole(['admin', 'SuperAdmin']) && ! empty($userServices)) {
            $query->whereHas('inventories.stockage.service', function ($q) use ($userServices) {
                $q->whereIn('id', $userServices);
            });
        }

        // Apply search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('code_interne', 'like', "%{$search}%")
                    ->orWhere('forme', 'like', "%{$search}%");
            });
        }

        // Apply category filter
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $stockProducts = $query->get()->map(function ($product) {
            // Calculate total quantity and inventory by location
            $totalQuantity = $product->inventories->sum('quantity');
            $inventoryByLocation = $product->inventories->groupBy('stockage.name')->map(function ($items) {
                return $items->sum('quantity');
            });

            return [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'category' => $product->category,
                'code_interne' => $product->code_interne,
                'forme' => $product->forme,
                'designation' => $product->designation,
                'nom_commercial' => $product->nom_commercial,
                'type' => 'stock',
                'source' => 'Stock General',
                'total_quantity' => $totalQuantity,
                'unit' => $product->unit ?? 'Unit',
                'has_box_units' => ! empty($product->package_quantity),
                'package_quantity' => $product->package_quantity,
                'inventory_display' => $this->formatInventoryDisplay($totalQuantity, $product->package_quantity, $product->unit),
                'inventory_by_location' => $inventoryByLocation,
                'availability' => $totalQuantity > 0 ? 'Available' : 'Out of Stock',
                'availability_class' => $totalQuantity > 0 ? 'text-green-600' : 'text-red-600',
            ];
        });

        return $stockProducts;
    }

    /**
     * Get pharmacy products based on user services
     */
    private function getPharmacyProducts(Request $request, array $userServices, $user)
    {
        $query = PharmacyProduct::with(['inventories.stockage.service']);

        // Apply service filtering
        if (! $user->hasRole(['admin', 'SuperAdmin']) && ! empty($userServices)) {
            $query->whereHas('inventories.stockage.service', function ($q) use ($userServices) {
                $q->whereIn('id', $userServices);
            });
        }

        // Apply search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('forme', 'like', "%{$search}%")
                    ->orWhere('code_medicament', 'like', "%{$search}%");
            });
        }

        // Apply category filter
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $pharmacyProducts = $query->get()->map(function ($product) {
            // Calculate total quantity and inventory by location
            $totalQuantity = $product->inventories->sum('quantity');
            $inventoryByLocation = $product->inventories->groupBy('stockage.name')->map(function ($items) {
                return $items->sum('quantity');
            });

            return [
                'id' => 'pharmacy_'.$product->id,
                'name' => $product->name,
                'description' => $product->description,
                'category' => $product->category,
                'code_interne' => $product->code_medicament,
                'forme' => $product->forme,
                'designation' => $product->designation ?? $product->name,
                'nom_commercial' => $product->nom_commercial,
                'type' => 'pharmacy',
                'source' => 'Pharmacy',
                'total_quantity' => $totalQuantity,
                'unit' => $product->unit ?? 'Unit',
                'has_box_units' => ! empty($product->package_quantity),
                'package_quantity' => $product->package_quantity,
                'inventory_display' => $this->formatInventoryDisplay($totalQuantity, $product->package_quantity, $product->unit),
                'inventory_by_location' => $inventoryByLocation,
                'availability' => $totalQuantity > 0 ? 'Available' : 'Out of Stock',
                'availability_class' => $totalQuantity > 0 ? 'text-green-600' : 'text-red-600',
                // Pharmacy specific fields
                'dci' => $product->dci,
                'dosage' => $product->dosage,
                'prix_unitaire' => $product->prix_unitaire,
                'is_controlled_substance' => $product->is_controlled_substance ?? false,
                'requires_prescription' => $product->requires_prescription ?? false,
            ];
        });

        return $pharmacyProducts;
    }

    /**
     * Format inventory display with box/unit information
     */
    private function formatInventoryDisplay($totalQuantity, $packageQuantity, $unit)
    {
        if (empty($packageQuantity) || $packageQuantity <= 1) {
            return number_format($totalQuantity).' '.($unit ?? 'units');
        }

        $boxes = intval($totalQuantity / $packageQuantity);
        $remainingUnits = $totalQuantity % $packageQuantity;

        $display = '';
        if ($boxes > 0) {
            $display .= $boxes.' × '.$packageQuantity.' boxes';
        }
        if ($remainingUnits > 0) {
            if ($boxes > 0) {
                $display .= ' + ';
            }
            $display .= $remainingUnits.' '.($unit ?? 'units');
        }

        return $display ?: '0 '.($unit ?? 'units');
    }

    /**
     * Get available categories for filtering
     */
    public function categories(Request $request)
    {
        $user = auth()->user();
        $userServices = $this->getUserServices($user);

        $stockCategories = collect();
        $pharmacyCategories = collect();

        if ($user->hasRole(['admin', 'SuperAdmin']) || ! empty($userServices)) {
            // Get stock categories
            $stockQuery = Product::select('category')->distinct();
            if (! $user->hasRole(['admin', 'SuperAdmin']) && ! empty($userServices)) {
                $stockQuery->whereHas('inventories.stockage.service', function ($q) use ($userServices) {
                    $q->whereIn('id', $userServices);
                });
            }
            $stockCategories = $stockQuery->whereNotNull('category')->pluck('category');

            // Get pharmacy categories
            $pharmacyQuery = PharmacyProduct::select('category')->distinct();
            if (! $user->hasRole(['admin', 'SuperAdmin']) && ! empty($userServices)) {
                $pharmacyQuery->whereHas('inventories.stockage.service', function ($q) use ($userServices) {
                    $q->whereIn('id', $userServices);
                });
            }
            $pharmacyCategories = $pharmacyQuery->whereNotNull('category')->pluck('category');
        }

        $allCategories = $stockCategories->concat($pharmacyCategories)->unique()->sort()->values();

        return response()->json([
            'success' => true,
            'data' => $allCategories,
        ]);
    }

    /**
     * Get user's assigned services info
     */
    public function userServices()
    {
        $user = auth()->user();

        $services = $user->activeSpecializations()
            ->with(['specialization.service'])
            ->get()
            ->map(function ($userSpec) {
                return [
                    'specialization_id' => $userSpec->specialization->id,
                    'specialization_name' => $userSpec->specialization->name,
                    'service_id' => $userSpec->specialization->service->id ?? null,
                    'service_name' => $userSpec->specialization->service->name ?? null,
                ];
            })
            ->filter(function ($item) {
                return ! is_null($item['service_id']);
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $services,
            'is_admin' => $user->hasRole(['admin', 'SuperAdmin']),
        ]);
    }
}
