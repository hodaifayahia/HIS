<?php

namespace App\Services\Inventory;

use App\Events\Inventory\PriceChanged;
use App\Models\BonEntreeItem;
use App\Models\Inventory\ServiceGroupProductPricing;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ServiceGroupProductPricingService
{
    /**
     * Get or create pricing for a product in a service group.
     */
    public function getOrCreatePricing(
        int $serviceGroupId,
        int $productId,
        bool $isPharmacy = false,
        ?array $defaultPricing = null
    ): ServiceGroupProductPricing {
        $pricing = ServiceGroupProductPricing::getPricing($serviceGroupId, $productId, $isPharmacy);

        if (! $pricing && $defaultPricing) {
            $pricing = $this->createPricing($serviceGroupId, $productId, $isPharmacy, $defaultPricing);
        }

        return $pricing;
    }

    /**
     * Create new pricing record.
     */
    public function createPricing(
        int $serviceGroupId,
        int $productId,
        bool $isPharmacy,
        array $pricingData
    ): ServiceGroupProductPricing {
        DB::beginTransaction();
        try {
            $data = [
                'service_group_id' => $serviceGroupId,
                'is_pharmacy' => $isPharmacy,
                'selling_price' => $pricingData['selling_price'],
                'purchase_price' => $pricingData['purchase_price'] ?? null,
                'vat_rate' => $pricingData['vat_rate'] ?? 0.00,
                'effective_from' => $pricingData['effective_from'] ?? now(),
                'effective_to' => null,
                'is_active' => true,
                'notes' => $pricingData['notes'] ?? null,
                'updated_by' => auth()->id(),
            ];

            if ($isPharmacy) {
                $data['pharmacy_product_id'] = $productId;
                $data['product_id'] = null;
            } else {
                $data['product_id'] = $productId;
                $data['pharmacy_product_id'] = null;
            }

            $pricing = ServiceGroupProductPricing::create($data);

            DB::commit();

            Log::info('Service group product pricing created', [
                'pricing_id' => $pricing->id,
                'service_group_id' => $serviceGroupId,
                'product_id' => $productId,
                'is_pharmacy' => $isPharmacy,
                'selling_price' => $pricingData['selling_price'],
            ]);

            return $pricing->fresh(['serviceGroup', 'product', 'pharmacyProduct', 'updatedBy']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create service group product pricing', [
                'error' => $e->getMessage(),
                'service_group_id' => $serviceGroupId,
                'product_id' => $productId,
            ]);
            throw $e;
        }
    }

    /**
     * Update pricing (archives old and creates new version).
     */
    public function updatePrice(
        int $serviceGroupId,
        int $productId,
        bool $isPharmacy,
        array $newPricingData
    ): ServiceGroupProductPricing {
        DB::beginTransaction();
        try {
            $currentPricing = ServiceGroupProductPricing::getPricing($serviceGroupId, $productId, $isPharmacy);

            if (! $currentPricing) {
                // No existing pricing, create new
                $newPricing = $this->createPricing($serviceGroupId, $productId, $isPharmacy, $newPricingData);
            } else {
                // Archive current and create new version
                $newPricing = $currentPricing->archiveAndCreateNew([
                    'selling_price' => $newPricingData['selling_price'],
                    'purchase_price' => $newPricingData['purchase_price'] ?? $currentPricing->purchase_price,
                    'vat_rate' => $newPricingData['vat_rate'] ?? $currentPricing->vat_rate,
                    'notes' => $newPricingData['notes'] ?? null,
                    'updated_by' => auth()->id(),
                ]);

                // Dispatch price change event
                event(new PriceChanged(
                    $currentPricing,
                    $newPricing,
                    Auth::user()
                ));
            }

            DB::commit();

            Log::info('Service group product pricing updated', [
                'old_pricing_id' => $currentPricing?->id,
                'new_pricing_id' => $newPricing->id,
                'service_group_id' => $serviceGroupId,
                'product_id' => $productId,
                'old_price' => $currentPricing?->selling_price,
                'new_price' => $newPricingData['selling_price'],
            ]);

            return $newPricing->fresh(['serviceGroup', 'product', 'pharmacyProduct', 'updatedBy']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update service group product pricing', [
                'error' => $e->getMessage(),
                'service_group_id' => $serviceGroupId,
                'product_id' => $productId,
            ]);
            throw $e;
        }
    }

    /**
     * Bulk update prices for multiple products.
     */
    public function bulkUpdatePrices(
        int $serviceGroupId,
        bool $isPharmacy,
        array $products,
        ?float $percentageIncrease = null
    ): array {
        DB::beginTransaction();
        try {
            $updated = [];
            $failed = [];

            foreach ($products as $productData) {
                try {
                    $productId = $productData['product_id'];

                    if ($percentageIncrease !== null) {
                        // Apply percentage increase to current price
                        $currentPricing = ServiceGroupProductPricing::getPricing($serviceGroupId, $productId, $isPharmacy);
                        if ($currentPricing) {
                            $newPrice = $currentPricing->selling_price * (1 + ($percentageIncrease / 100));
                            $productData['selling_price'] = round($newPrice, 2);
                        }
                    }

                    $newPricing = $this->updatePrice(
                        $serviceGroupId,
                        $productId,
                        $isPharmacy,
                        $productData
                    );

                    $updated[] = [
                        'product_id' => $productId,
                        'pricing_id' => $newPricing->id,
                        'old_price' => $currentPricing->selling_price ?? null,
                        'new_price' => $newPricing->selling_price,
                    ];
                } catch (\Exception $e) {
                    $failed[] = [
                        'product_id' => $productData['product_id'],
                        'error' => $e->getMessage(),
                    ];
                }
            }

            DB::commit();

            Log::info('Bulk price update completed', [
                'service_group_id' => $serviceGroupId,
                'is_pharmacy' => $isPharmacy,
                'updated_count' => count($updated),
                'failed_count' => count($failed),
            ]);

            return [
                'success' => true,
                'updated' => $updated,
                'failed' => $failed,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk price update failed', [
                'error' => $e->getMessage(),
                'service_group_id' => $serviceGroupId,
            ]);
            throw $e;
        }
    }

    /**
     * Get price history for a product.
     */
    public function getPriceHistory(
        int $serviceGroupId,
        int $productId,
        bool $isPharmacy = false,
        int $limit = 10
    ): array {
        $history = ServiceGroupProductPricing::getPriceHistory($serviceGroupId, $productId, $isPharmacy, $limit);

        return $history->map(function ($pricing) {
            return [
                'id' => $pricing->id,
                'selling_price' => $pricing->selling_price,
                'purchase_price' => $pricing->purchase_price,
                'vat_rate' => $pricing->vat_rate,
                'selling_price_with_vat' => $pricing->selling_price_with_vat,
                'profit_margin' => $pricing->profit_margin,
                'effective_from' => $pricing->effective_from->format('Y-m-d H:i:s'),
                'effective_to' => $pricing->effective_to?->format('Y-m-d H:i:s'),
                'is_active' => $pricing->is_active,
                'notes' => $pricing->notes,
                'updated_by_name' => $pricing->updatedBy?->name,
                'created_at' => $pricing->created_at->format('Y-m-d H:i:s'),
            ];
        })->toArray();
    }

    /**
     * Get product pricing info from Bon d'Entrée history.
     */
    public function getProductPricingInfoFromBonEntree(
        int $productId,
        bool $isPharmacy = false
    ): array {
        $column = $isPharmacy ? 'pharmacy_product_id' : 'product_id';

        $items = BonEntreeItem::where($column, $productId)
            ->whereHas('bonEntree', function ($query) {
                $query->where('status', 'validated');
            })
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        if ($items->isEmpty()) {
            return [
                'average_price' => null,
                'last_price' => null,
                'current_price' => null,
                'min_price' => null,
                'max_price' => null,
                'price_history' => [],
            ];
        }

        $prices = $items->pluck('purchase_price')->filter()->values();

        return [
            'average_price' => $prices->isNotEmpty() ? round($prices->average(), 2) : null,
            'last_price' => $items->first()->purchase_price,
            'current_price' => $items->first()->purchase_price,
            'min_price' => $prices->isNotEmpty() ? $prices->min() : null,
            'max_price' => $prices->isNotEmpty() ? $prices->max() : null,
            'price_history' => $items->take(5)->map(function ($item) {
                return [
                    'date' => $item->created_at->format('Y-m-d'),
                    'purchase_price' => $item->purchase_price,
                    'sell_price' => $item->sell_price,
                    'tva' => $item->tva,
                    'bon_entree_code' => $item->bonEntree->bon_entree_code ?? null,
                ];
            })->toArray(),
        ];
    }

    /**
     * Delete pricing (soft delete by setting effective_to).
     */
    public function deletePricing(int $pricingId): bool
    {
        DB::beginTransaction();
        try {
            $pricing = ServiceGroupProductPricing::findOrFail($pricingId);

            $pricing->update([
                'effective_to' => now(),
                'is_active' => false,
            ]);

            DB::commit();

            Log::info('Service group product pricing deleted', [
                'pricing_id' => $pricingId,
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete service group product pricing', [
                'error' => $e->getMessage(),
                'pricing_id' => $pricingId,
            ]);
            throw $e;
        }
    }

    /**
     * Export pricing data to CSV format
     */
    public function exportPricingToCsv(int $serviceGroupId, bool $isPharmacy): string
    {
        $query = ServiceGroupProductPricing::with(['product', 'pharmacyProduct', 'updatedBy', 'serviceGroup'])
            ->forServiceGroup($serviceGroupId)
            ->active()
            ->current();

        if ($isPharmacy) {
            $query->pharmacy();
        } else {
            $query->stock();
        }

        $pricing = $query->orderBy('created_at', 'desc')->get();

        // CSV header
        $csv = "Service Group,Product Name,Product Code,Selling Price,Purchase Price,VAT Rate,Price with VAT,Profit Margin,Effective From,Updated By,Notes\n";

        foreach ($pricing as $p) {
            $productName = $isPharmacy
                ? ($p->pharmacyProduct->name ?? 'N/A')
                : ($p->product->product_name ?? 'N/A');

            $productCode = $isPharmacy
                ? ($p->pharmacyProduct->code ?? 'N/A')
                : ($p->product->product_code ?? 'N/A');

            $csv .= sprintf(
                "%s,%s,%s,%.2f,%.2f,%.2f,%.2f,%s,%s,%s,\"%s\"\n",
                $p->serviceGroup->name ?? 'N/A',
                $this->escapeCsv($productName),
                $this->escapeCsv($productCode),
                $p->selling_price,
                $p->purchase_price ?? 0,
                $p->vat_rate,
                $p->selling_price_with_vat,
                $p->profit_margin ? number_format($p->profit_margin, 2).'%' : 'N/A',
                $p->effective_from ? $p->effective_from->format('Y-m-d') : 'N/A',
                $p->updatedBy->name ?? 'System',
                $this->escapeCsv($p->notes ?? '')
            );
        }

        Log::info('Pricing exported to CSV:', [
            'service_group_id' => $serviceGroupId,
            'is_pharmacy' => $isPharmacy,
            'rows' => $pricing->count(),
        ]);

        return $csv;
    }

    /**
     * Import pricing data from CSV file
     */
    public function importPricingFromCsv(int $serviceGroupId, bool $isPharmacy, string $filePath): array
    {
        $imported = 0;
        $failed = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            $file = fopen($filePath, 'r');
            $headers = fgetcsv($file); // Skip header row

            while (($row = fgetcsv($file)) !== false) {
                try {
                    // Expected columns: product_id, selling_price, purchase_price, vat_rate, notes
                    if (count($row) < 2) {
                        throw new \Exception('Invalid row format');
                    }

                    $productId = (int) $row[0];
                    $sellingPrice = (float) $row[1];
                    $purchasePrice = isset($row[2]) ? (float) $row[2] : null;
                    $vatRate = isset($row[3]) ? (float) $row[3] : 0;
                    $notes = $row[4] ?? '';

                    // Validate product exists
                    if ($isPharmacy) {
                        if (! \App\Models\Pharmacy\PharmacyProduct::find($productId)) {
                            throw new \Exception("Pharmacy product {$productId} not found");
                        }
                    } else {
                        if (! \App\Models\Purchasing\Product::find($productId)) {
                            throw new \Exception("Product {$productId} not found");
                        }
                    }

                    // Create/update pricing
                    $this->createPricing($serviceGroupId, $productId, $isPharmacy, [
                        'selling_price' => $sellingPrice,
                        'purchase_price' => $purchasePrice,
                        'vat_rate' => $vatRate,
                        'notes' => $notes,
                    ]);

                    $imported++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = [
                        'row' => $imported + $failed + 1,
                        'data' => $row,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            fclose($file);
            DB::commit();

            Log::info('Pricing imported from CSV:', [
                'service_group_id' => $serviceGroupId,
                'is_pharmacy' => $isPharmacy,
                'imported' => $imported,
                'failed' => $failed,
            ]);

            return [
                'imported' => $imported,
                'failed' => $failed,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CSV import failed:', ['error' => $e->getMessage()]);

            throw $e;
        }
    }

    /**
     * Generate pricing report as HTML (PDF conversion can be added later)
     */
    public function generatePricingReport(int $serviceGroupId, bool $isPharmacy): string
    {
        $query = ServiceGroupProductPricing::with(['product', 'pharmacyProduct', 'updatedBy', 'serviceGroup'])
            ->forServiceGroup($serviceGroupId)
            ->active()
            ->current();

        if ($isPharmacy) {
            $query->pharmacy();
        } else {
            $query->stock();
        }

        $pricing = $query->orderBy('created_at', 'desc')->get();
        $serviceGroup = \App\Models\CONFIGURATION\ServiceGroup::find($serviceGroupId);

        // Calculate statistics
        $totalProducts = $pricing->count();
        $avgMargin = $pricing->avg('profit_margin');
        $avgSellingPrice = $pricing->avg('selling_price');
        $totalValue = $pricing->sum('selling_price');

        // Generate HTML report
        $html = $this->generatePricingReportHtml($serviceGroup, $pricing, [
            'total_products' => $totalProducts,
            'avg_margin' => $avgMargin,
            'avg_selling_price' => $avgSellingPrice,
            'total_value' => $totalValue,
            'is_pharmacy' => $isPharmacy,
        ]);

        Log::info('Pricing report generated:', [
            'service_group_id' => $serviceGroupId,
            'is_pharmacy' => $isPharmacy,
            'products' => $totalProducts,
        ]);

        return $html;
    }

    /**
     * Helper: Escape CSV values
     */
    private function escapeCsv(string $value): string
    {
        return str_replace('"', '""', $value);
    }

    /**
     * Helper: Generate HTML for pricing report
     */
    private function generatePricingReportHtml($serviceGroup, $pricing, array $stats): string
    {
        $productType = $stats['is_pharmacy'] ? 'Pharmacy' : 'Stock';
        $generatedDate = now()->format('Y-m-d H:i:s');
        $totalProducts = $stats['total_products'];
        $avgMargin = number_format($stats['avg_margin'], 2);
        $avgSellingPrice = number_format($stats['avg_selling_price'], 2);
        $totalValue = number_format($stats['total_value'], 2);
        $groupName = $serviceGroup->name;

        $html = "<!DOCTYPE html>
<html>
<head>
    <meta charset=\"UTF-8\">
    <title>Pricing Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #4f46e5; text-align: center; }
        .header { background: #f3f4f6; padding: 15px; margin-bottom: 20px; border-radius: 8px; }
        .stats { display: flex; justify-content: space-around; margin-bottom: 20px; }
        .stat-box { background: #e0e7ff; padding: 10px; text-align: center; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #4f46e5; color: white; padding: 10px; text-align: left; }
        td { padding: 8px; border-bottom: 1px solid #e5e7eb; }
        tr:nth-child(even) { background: #f9fafb; }
        .footer { text-align: center; margin-top: 30px; color: #6b7280; font-size: 12px; }
    </style>
</head>
<body>
    <h1>Service Group Pricing Report</h1>
    
    <div class=\"header\">
        <h2>{$groupName} - {$productType} Products</h2>
        <p><strong>Generated:</strong> {$generatedDate}</p>
    </div>
    
    <div class=\"stats\">
        <div class=\"stat-box\">
            <h3>{$totalProducts}</h3>
            <p>Total Products</p>
        </div>
        <div class=\"stat-box\">
            <h3>{$avgMargin}%</h3>
            <p>Average Margin</p>
        </div>
        <div class=\"stat-box\">
            <h3>{$avgSellingPrice} DZD</h3>
            <p>Avg Selling Price</p>
        </div>
        <div class=\"stat-box\">
            <h3>{$totalValue} DZD</h3>
            <p>Total Portfolio Value</p>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Product Name</th>
                <th>Selling Price</th>
                <th>Purchase Price</th>
                <th>VAT Rate</th>
                <th>Price + VAT</th>
                <th>Margin %</th>
                <th>Effective From</th>
            </tr>
        </thead>
        <tbody>";

        foreach ($pricing as $p) {
            $productName = $stats['is_pharmacy']
                ? ($p->pharmacyProduct->name ?? 'N/A')
                : ($p->product->product_name ?? 'N/A');

            $html .= sprintf(
                '<tr><td>%s</td><td>%.2f DZD</td><td>%.2f DZD</td><td>%.2f%%</td><td>%.2f DZD</td><td>%s%%</td><td>%s</td></tr>',
                htmlspecialchars($productName),
                $p->selling_price,
                $p->purchase_price ?? 0,
                $p->vat_rate,
                $p->selling_price_with_vat,
                $p->profit_margin ? number_format($p->profit_margin, 2) : 'N/A',
                $p->effective_from ? $p->effective_from->format('Y-m-d') : 'N/A'
            );
        }

        $html .= '
        </tbody>
    </table>
    
    <div class="footer">
        <p>Hospital Information System - Pricing Report</p>
        <p>This report is confidential and for internal use only.</p>
    </div>
</body>
</html>';

        return $html;
    }
}
