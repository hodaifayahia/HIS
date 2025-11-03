<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Stockage;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    /**
     * Seed the inventory table with comprehensive, realistic stock data.
     * Distributes inventory across ALL stockages and services.
     */
    public function run(): void
    {
        // Get all products and stockages
        $products = Product::select('id', 'code_interne', 'boite_de', 'forme')->get();
        $stockages = Stockage::select('id', 'capacity', 'type')->get();
        
        if ($products->isEmpty() || $stockages->isEmpty()) {
            echo "Error: No products or stockages found. Please seed products and stockages first.\n";
            return;
        }

        $productIds = $products->pluck('id')->toArray();
        $productCount = count($productIds);
        $stockageCount = count($stockages);

        echo "Starting comprehensive inventory seeding...\n";
        echo "Total Products: {$productCount}\n";
        echo "Total Stockages: {$stockageCount}\n";
        echo "Target: ~30-50 items per stockage for realistic coverage\n\n";

        // Storage units
        $units = ['tablet', 'capsule', 'ml', 'mg', 'g', 'kg', 'piece', 'box', 'vial', 'ampoule', 'syringe', 'bottle', 'tube', 'patch'];

        // Batch number patterns
        $batchPrefixes = ['BATCH', 'LOT', 'BN', 'LN'];

        $batchSize = 100;
        $inventories = [];
        $recordCount = 0;

        // Seed strategy: Give each stockage 30-50 random products for realistic coverage
        foreach ($stockages as $stockage) {
            // Randomize number of products per stockage (30-50)
            $productsPerStockage = rand(30, 50);
            
            // Get random products for this stockage
            $randomProductIndices = array_rand($productIds, min($productsPerStockage, count($productIds)));
            if (!is_array($randomProductIndices)) {
                $randomProductIndices = [$randomProductIndices];
            }

            foreach ($randomProductIndices as $productIndex) {
                $product = $products[$productIndex];
                $productId = $productIds[$productIndex];
                $recordCount++;

                // Vary quantity based on stockage type and capacity
                $quantity = $this->calculateQuantity($stockage, $product);
                $totalUnits = $this->calculateTotalUnits($quantity, $product, $stockage);

                // Generate batch number
                $batchNumber = $batchPrefixes[array_rand($batchPrefixes)] . '-' . 
                              str_pad($product->code_interne ?? $productId, 6, '0', STR_PAD_LEFT) . '-' . 
                              rand(1000, 9999);

                // Generate serial number (40% of items for pharmacies, 20% for others)
                $serialChance = $stockage->type === 'pharmacy' ? 40 : 20;
                $serialNumber = rand(0, 100) < $serialChance ? 'SN' . str_pad($recordCount, 10, '0', STR_PAD_LEFT) : null;

                // Purchase price (random between $0.50 and $500)
                $purchasePrice = rand(50, 50000) / 100;

                // Expiry date distribution (more realistic for different stockage types)
                $expiryDate = $this->generateExpiryDate($stockage->type);

                // Location information
                $location = $this->generateLocation($stockage->type);

                $inventories[] = [
                    'product_id' => $productId,
                    'stockage_id' => $stockage->id,
                    'quantity' => $quantity,
                    'total_units' => $totalUnits,
                    'unit' => $units[array_rand($units)],
                    'batch_number' => $batchNumber,
                    'serial_number' => $serialNumber,
                    'purchase_price' => $purchasePrice,
                    'barcode' => $this->generateBarcode($product->code_interne ?? $productId, $batchNumber, $serialNumber, $expiryDate),
                    'expiry_date' => $expiryDate,
                    'location' => $location,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Insert in batches
                if (count($inventories) === $batchSize) {
                    try {
                        Inventory::insert($inventories);
                        $progressPercent = round(($recordCount / ($stockageCount * 40)) * 100);
                        echo "Progress: {$progressPercent}% ({$recordCount} inventory records seeded)\n";
                    } catch (\Exception $e) {
                        echo "Error at record {$recordCount}: " . $e->getMessage() . "\n";
                        throw $e;
                    }
                    $inventories = [];
                }
            }
        }

        // Insert remaining records
        if (!empty($inventories)) {
            try {
                Inventory::insert($inventories);
                echo "Progress: 100% ({$recordCount} inventory records seeded)\n";
            } catch (\Exception $e) {
                echo "Error inserting final batch: " . $e->getMessage() . "\n";
                throw $e;
            }
        }

        // Get final statistics
        $totalInventory = Inventory::count();
        $stockagesWithInventory = Inventory::distinct('stockage_id')->count();
        $productsInInventory = Inventory::distinct('product_id')->count();

        echo "\n✓ Successfully completed comprehensive inventory seeding!\n";
        echo "════════════════════════════════════════════════════════\n";
        echo "Total Inventory Records: {$totalInventory}\n";
        echo "Stockages with Inventory: {$stockagesWithInventory} / {$stockageCount}\n";
        echo "Products in Inventory: {$productsInInventory} / {$productCount}\n";
        echo "════════════════════════════════════════════════════════\n";
    }

    /**
     * Calculate quantity based on stockage type and capacity
     */
    private function calculateQuantity($stockage, $product)
    {
        switch ($stockage->type) {
            case 'pharmacy':
            case 'cold_room':
                // Smaller quantities for pharmacies and cold rooms
                return rand(5, 50);
            case 'laboratory':
                // Laboratory: small quantities
                return rand(2, 20);
            case 'warehouse':
            case 'storage_room':
                // Large quantities for warehouses
                return rand(50, 200);
            case 'emergency':
                // Medium quantities for emergency
                return rand(10, 100);
            default:
                return rand(10, 100);
        }
    }

    /**
     * Calculate total units based on quantity and product
     */
    private function calculateTotalUnits($quantity, $product, $stockage)
    {
        // 60% stored by box, 40% by individual units
        if (rand(0, 100) < 60 && $product->boite_de) {
            return $quantity * $product->boite_de;
        }
        return $quantity;
    }

    /**
     * Generate realistic expiry dates based on stockage type
     */
    private function generateExpiryDate($stockageType)
    {
        // Different expiry patterns for different storage types
        $expiryChance = rand(0, 100);
        
        if ($stockageType === 'pharmacy' || $stockageType === 'cold_room') {
            // Pharmacies: higher percentage of items expiring/expired
            if ($expiryChance < 75) {
                // 75% - Valid stock (3-24 months)
                return now()->addMonths(rand(3, 24))->toDateString();
            } elseif ($expiryChance < 90) {
                // 15% - Expiring soon (7-45 days)
                return now()->addDays(rand(7, 45))->toDateString();
            } else {
                // 10% - Already expired (1-180 days)
                return now()->subDays(rand(1, 180))->toDateString();
            }
        } else {
            // Warehouses/equipment: longer shelf life
            if ($expiryChance < 80) {
                // 80% - Valid stock (1-5 years)
                return now()->addMonths(rand(12, 60))->toDateString();
            } elseif ($expiryChance < 95) {
                // 15% - Expiring soon (30-90 days)
                return now()->addDays(rand(30, 90))->toDateString();
            } else {
                // 5% - Already expired
                return now()->subDays(rand(1, 360))->toDateString();
            }
        }
    }

    /**
     * Generate realistic location codes based on stockage type
     */
    private function generateLocation($storageType)
    {
        switch ($storageType) {
            case 'pharmacy':
                return 'Shelf P' . chr(65 + rand(0, 5)) . '-' . rand(1, 20);
            case 'warehouse':
                return 'Rack W' . chr(65 + rand(0, 3)) . '-' . rand(1, 30) . '-' . rand(1, 5);
            case 'cold_room':
                return 'Freezer F' . chr(65 + rand(0, 2)) . '-' . rand(1, 10);
            case 'laboratory':
                return 'Cabinet L' . chr(65 + rand(0, 3)) . '-' . rand(1, 15);
            case 'emergency':
                return 'Emergency E' . chr(65 + rand(0, 1)) . '-' . rand(1, 10);
            default:
                return 'Storage S' . chr(65 + rand(0, 2)) . '-' . rand(1, 20);
        }
    }

    /**
     * Generate realistic barcode from inventory components
     */
    private function generateBarcode($productCode, $batchNumber, $serialNumber, $expiryDate)
    {
        $code = 'INV-' . str_pad($productCode, 6, '0', STR_PAD_LEFT) . '-' . 
                substr($batchNumber, -4) . '-' . 
                date('dmY', strtotime($expiryDate));
        
        if ($serialNumber) {
            $code .= '-' . substr($serialNumber, -4);
        }
        
        return $code;
    }
}