<?php

namespace App\Commands;

use Illuminate\Console\Command;
use App\Models\Inventory;

class VerifyInventorySeeding extends Command
{
    protected $signature = 'verify:inventory-seeding';
    protected $description = 'Verify inventory seeding results';

    public function handle()
    {
        $totalInventories = Inventory::count();
        $this->line("=== INVENTORY SEEDING VERIFICATION ===\n");
        $this->line("Total Inventory Records: {$totalInventories}\n");

        // Expiry status breakdown
        $validStock = Inventory::where('expiry_date', '>', now())->count();
        $expiringSoon = Inventory::whereBetween('expiry_date', [now()->subDays(60), now()])->count();
        $expired = Inventory::where('expiry_date', '<', now())->count();

        $this->line("EXPIRY STATUS:");
        $this->line("  Valid Stock (future expiry): {$validStock} (" . round(($validStock/$totalInventories)*100, 1) . "%)");
        $this->line("  Expiring Soon (< 60 days): {$expiringSoon} (" . round(($expiringSoon/$totalInventories)*100, 1) . "%)");
        $this->line("  Expired: {$expired} (" . round(($expired/$totalInventories)*100, 1) . "%)\n");

        // Unit distribution
        $unitCounts = Inventory::select('unit')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('unit')
            ->orderByRaw('COUNT(*) DESC')
            ->get();

        $this->line("UNIT DISTRIBUTION:");
        foreach ($unitCounts as $uc) {
            $this->line("  {$uc->unit}: {$uc->count}");
        }
        $this->line("");

        // Quantity statistics
        $quantityStats = Inventory::selectRaw('MIN(quantity) as min, MAX(quantity) as max, AVG(quantity) as avg, SUM(total_units) as total_units')
            ->first();

        $this->line("QUANTITY STATISTICS:");
        $this->line("  Min Quantity: {$quantityStats->min}");
        $this->line("  Max Quantity: {$quantityStats->max}");
        $this->line("  Avg Quantity: " . round($quantityStats->avg, 2));
        $this->line("  Total Units in Stock: {$quantityStats->total_units}\n");

        // Price statistics
        $priceStats = Inventory::selectRaw('MIN(purchase_price) as min, MAX(purchase_price) as max, AVG(purchase_price) as avg')
            ->first();

        $this->line("PURCHASE PRICE STATISTICS:");
        $this->line("  Min Price: $" . round($priceStats->min, 2));
        $this->line("  Max Price: $" . round($priceStats->max, 2));
        $this->line("  Avg Price: $" . round($priceStats->avg, 2) . "\n");

        // Product coverage
        $productsInInventory = Inventory::distinct('product_id')->count();
        $totalProducts = \App\Models\Product::count();

        $this->line("PRODUCT COVERAGE:");
        $this->line("  Products in Inventory: {$productsInInventory}");
        $this->line("  Total Products: {$totalProducts}");
        $this->line("  Coverage: " . round(($productsInInventory/$totalProducts)*100, 1) . "%\n");

        // Stockage coverage
        $stockagesInInventory = Inventory::distinct('stockage_id')->count();
        $totalStockages = \App\Models\Stockage::count();

        $this->line("STOCKAGE COVERAGE:");
        $this->line("  Stockages with Inventory: {$stockagesInInventory}");
        $this->line("  Total Stockages: {$totalStockages}");
        $this->line("  Coverage: " . round(($stockagesInInventory/$totalStockages)*100, 1) . "%\n");

        // Serial number stats
        $withSerial = Inventory::whereNotNull('serial_number')->count();
        $withoutSerial = Inventory::whereNull('serial_number')->count();

        $this->line("SERIAL NUMBER DISTRIBUTION:");
        $this->line("  With Serial Number: {$withSerial} (" . round(($withSerial/$totalInventories)*100, 1) . "%)");
        $this->line("  Without Serial Number: {$withoutSerial} (" . round(($withoutSerial/$totalInventories)*100, 1) . "%)\n");

        // Batch number sample
        $batchSamples = Inventory::select('batch_number')
            ->distinct()
            ->limit(5)
            ->get();

        $this->line("SAMPLE BATCH NUMBERS:");
        foreach ($batchSamples as $batch) {
            $this->line("  {$batch->batch_number}");
        }
        $this->line("");

        // Location distribution
        $locations = Inventory::select('location')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('location')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(10)
            ->get();

        $this->line("TOP 10 STORAGE LOCATIONS:");
        foreach ($locations as $loc) {
            $this->line("  {$loc->location}: {$loc->count}");
        }
        $this->line("");

        $this->info("✓ Inventory seeding verification complete!");
    }
}
