<?php

namespace App\Http\Controllers;

use App\Models\BonReception;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ProductHistoryController extends Controller
{
    /**
     * Get complete purchase history for a product (only from BonReception - actual purchases)
     */
    public function getProductHistory($productId)
    {
        try {
            // Get product details
            $product = Product::findOrFail($productId);

            // Get purchase history only from BonReception (actual received products with prices)
            $purchaseHistory = $this->getBonReceptionHistory($productId);

            // Get suppliers who have sold this product
            $suppliers = $this->getProductSuppliers($productId);

            // Calculate statistics
            $statistics = $this->calculateStatistics($purchaseHistory);

            return response()->json([
                'success' => true,
                'data' => [
                    'product' => $product,
                    'purchaseHistory' => $purchaseHistory,
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
            Log::error('Error fetching product history: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch product history',
            ], 500);
        }
    }

    /**
     * Get reception history for a product with pricing and location
     */
    private function getBonReceptionHistory($productId)
    {
        // build a select array and avoid referencing columns that might not exist on all deployments
        $selects = [
            'bon_receptions.id',
            'bon_receptions.date_reception',
            'bon_receptions.status',
            'bon_receptions.observation',
            'bon_receptions.created_at',
            'bon_reception_items.quantity_received',
            'bon_reception_items.unit',
            'bon_reception_items.notes',
            'bon_entree_items.purchase_price',
            'bon_entree_items.tva',
            DB::raw('(bon_entree_items.purchase_price * (1 + (bon_entree_items.tva / 100))) as total_price'),
            'fournisseurs.company_name as supplier_name',
            'fournisseurs.email as supplier_email',
            'fournisseurs.phone as supplier_phone',
        ];

        // some environments/migrations may not yet have bon_entrees.service_abv; guard against that
        if (Schema::hasColumn('bon_entrees', 'service_abv')) {
            $selects[] = 'bon_entrees.service_abv as current_location';
        } else {
            // fallback to a null column alias so the JSON shape stays consistent
            $selects[] = DB::raw('NULL as current_location');
        }

        return DB::table('bon_receptions')
            ->join('bon_reception_items', 'bon_receptions.id', '=', 'bon_reception_items.bon_reception_id')
            ->leftJoin('fournisseurs', 'bon_receptions.fournisseur_id', '=', 'fournisseurs.id')
            ->leftJoin('bon_entrees', 'bon_receptions.id', '=', 'bon_entrees.bon_reception_id')
            ->leftJoin('bon_entree_items', function ($join) use ($productId) {
                $join->on('bon_entrees.id', '=', 'bon_entree_items.bon_entree_id')
                    ->where('bon_entree_items.product_id', '=', $productId);
            })
            ->where('bon_reception_items.product_id', $productId)
            ->select($selects)
            ->orderBy('bon_receptions.created_at', 'desc')
            ->get();
    }

    /**
     * Get suppliers who have sold this product (from BonReception only)
     */
    private function getProductSuppliers($productId)
    {
        return DB::table('fournisseurs')
            ->join('bon_receptions', 'fournisseurs.id', '=', 'bon_receptions.fournisseur_id')
            ->join('bon_reception_items', 'bon_receptions.id', '=', 'bon_reception_items.bon_reception_id')
            ->where('bon_reception_items.product_id', $productId)
            ->select([
                'fournisseurs.id',
                'fournisseurs.company_name',
                'fournisseurs.email',
                'fournisseurs.phone',
                DB::raw('COUNT(bon_receptions.id) as purchases_count'),
                DB::raw('SUM(bon_reception_items.quantity_received) as total_quantity'),
            ])
            ->groupBy('fournisseurs.id', 'fournisseurs.company_name', 'fournisseurs.email', 'fournisseurs.phone')
            ->get();
    }

    /**
     * Calculate purchase statistics (only from BonReception)
     */
    private function calculateStatistics($purchaseHistory)
    {
        $totalPurchases = $purchaseHistory->count();
        $totalQuantity = $purchaseHistory->sum('quantity_received');
        $totalValue = $purchaseHistory->sum(function ($item) {
            return ($item->total_price ?? 0) * $item->quantity_received;
        });

        // Get unique suppliers count
        $uniqueSuppliers = $purchaseHistory->pluck('supplier_name')->filter()->unique();

        // Get last purchase date
        $lastPurchaseDate = $purchaseHistory->max('date_reception');

        return [
            'totalPurchases' => $totalPurchases,
            'totalQuantity' => $totalQuantity,
            'totalValue' => round($totalValue, 2),
            'suppliersCount' => $uniqueSuppliers->count(),
            'lastPurchaseDate' => $lastPurchaseDate,
        ];
    }
}
