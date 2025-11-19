<?php

namespace Database\Seeders;

use App\Models\PharmacyInventory;
use App\Models\PharmacyProduct;
use App\Models\PharmacyStockage;
use Illuminate\Database\Seeder;

class PharmacyInventorySeeder extends Seeder
{
    /**
     * Seed the pharmacy_inventories table by linking pharmacy products with stockages.
     * Creates realistic inventory entries with batch numbers, expiry dates, and pricing.
     */
    public function run(): void
    {
        $products = PharmacyProduct::where('is_active', true)->get();
        $stockages = PharmacyStockage::all();

        if ($products->isEmpty() || $stockages->isEmpty()) {
            $this->command->error('No active pharmacy products or stockages found. Please seed those first.');
            return;
        }

        $this->command->info("Starting to seed pharmacy inventories...");
        $this->command->info("Products available: {$products->count()}");
        $this->command->info("Stockages available: {$stockages->count()}");

        // Suppliers list
        $suppliers = [
            'ABC Pharma Supply', 'MedDirect', 'HealthCare Solutions', 'Pharma Logistics',
            'Global Health Supplies', 'Premium Med Supplies', 'QuickMed Distribution',
            'Reliable Pharma', 'Elite Medical Supply', 'Professional Health Services',
            'Pharmaceutical Distribution Co.', 'Health Logistics Inc.', 'Medical Supply Partners',
            'Quality Pharma Distributors', 'National Health Services'
        ];

        $units = ['Tablet', 'mL', 'Capsule', 'Unit', 'Vial', 'Ampoule', 'Strip', 'Box', 'Bottle', 'Jar'];

        $inventoryData = [];
        $totalCount = 0;
        $batchSize = 100;

        // For each stockage, assign multiple products
        // Note: Due to unique constraint on (pharmacy_product_id, pharmacy_stockage_id),
        // we can only have ONE inventory entry per product per stockage
        foreach ($stockages as $stockage) {
            // Each stockage gets 30-80 different products (varied inventory)
            $productsForThisStockage = $products->random(min(rand(30, 80), $products->count()));

            foreach ($productsForThisStockage as $product) {
                // Only one inventory entry per product per stockage (per unique constraint)
                $batchNumber = 'BATCH-' . strtoupper(uniqid());
                $purchasePrice = (float)rand(50, 5000) / 100;
                $markup = rand(20, 150);
                $sellingPrice = $purchasePrice * (1 + ($markup / 100));

                $inventoryData[] = [
                    'pharmacy_product_id' => $product->id,
                    'pharmacy_stockage_id' => $stockage->id,
                    'quantity' => rand(10, 500),
                    'unit' => $units[array_rand($units)],
                    'batch_number' => $batchNumber,
                    'expiry_date' => now()->addMonths(rand(3, 36)), // 3 months to 3 years
                    'location' => 'Shelf ' . rand(1, 10) . ', Position ' . rand(1, 20),
                    'barcode' => 'PHARMA-' . $product->id . '-' . $batchNumber,
                    'serial_number' => 'SN-' . strtoupper(bin2hex(random_bytes(5))),
                    'purchase_price' => $purchasePrice,
                    'selling_price' => round($sellingPrice, 2),
                    'supplier' => $suppliers[array_rand($suppliers)],
                    'purchase_date' => now()->subDays(rand(1, 180)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $totalCount++;

                // Insert in batches for better performance
                if (count($inventoryData) === $batchSize) {
                    PharmacyInventory::insert($inventoryData);
                    $this->command->info("Inserted {$totalCount} inventory entries...");
                    $inventoryData = [];
                }
            }
        }

        // Insert remaining data
        if (!empty($inventoryData)) {
            PharmacyInventory::insert($inventoryData);
        }

        $this->command->info("✓ Successfully seeded {$totalCount} pharmacy inventory entries!");
        $this->command->info("Inventory is now distributed across all stockages with realistic data.");
    }
}
