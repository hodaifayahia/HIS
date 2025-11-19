<?php

namespace Database\Seeders;

use App\Models\PharmacyStockage;
use App\Models\PharmacyStorage;
use App\Models\CONFIGURATION\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

class PharmacyStockageSeeder extends Seeder
{
    /**
     * Seed the pharmacy_stockages table with various stockage locations and types.
     */
    public function run(): void
    {
        // Get or create pharmacy-related services
        $pharmacyServiceNames = ['Pharmacy', 'Pharmacie', 'Service Pharmacy', 'Central Pharmacy', 'Pharmaceutical Services'];
        $pharmacyServices = [];

        foreach ($pharmacyServiceNames as $name) {
            $service = Service::firstOrCreate(
                ['name' => $name],
                [
                    'description' => 'Pharmacy Service',
                    'is_active' => true,
                    'image_url' => '/images/services/pharmacy.png',
                    'start_time' => '08:00',
                    'end_time' => '20:00',
                    'agmentation' => 0
                ]
            );
            $pharmacyServices[] = $service;
        }

        // Get all services for diversification
        $allServices = Service::all();
        if ($allServices->isEmpty()) {
            $this->command->info('No services found. Creating default services...');
            return;
        }

        $pharmacyStorages = PharmacyStorage::all();
        if ($pharmacyStorages->isEmpty()) {
            $this->command->info('No pharmacy storages found. Run PharmacyStorageSeeder first.');
            return;
        }

        $users = User::limit(20)->get();

        $stockageTypes = [
            'shelf' => 'Wall Shelf Storage',
            'cabinet' => 'Storage Cabinet',
            'drawer' => 'Drawer Unit',
            'bin' => 'Bin Storage',
            'rack' => 'Rack System',
            'pallet' => 'Pallet Storage',
            'refrigerator' => 'Refrigerator Unit',
            'freezer' => 'Freezer Unit',
            'safe' => 'Secure Safe',
            'display' => 'Display Case',
            'vault' => 'Secure Vault',
            'pod' => 'Storage Pod',
        ];

        $warehouseTypes = [
            'Central Pharmacy (PC)',
            'Service Pharmacy (PS)',
        ];

        $securityLevels = [
            'basic',
            'enhanced',
            'high',
            'maximum',
            'dea_compliant'
        ];

        $stockages = [];
        $stockageId = 1;

        // Create multiple stockage units
        // 3-5 stockage units per pharmacy storage
        foreach ($pharmacyStorages as $storage) {
            $numStockages = rand(3, 5);

            for ($i = 0; $i < $numStockages; $i++) {
                $stockageType = array_rand($stockageTypes);
                $isTemperatureControlled = in_array($stockageType, ['refrigerator', 'freezer']);
                $isSecure = in_array($stockageType, ['safe', 'vault']) || $storage->controlled_substance_vault;

                // Select appropriate service
                $service = $storage->type === 'controlled_substances' 
                    ? $pharmacyServices[array_rand($pharmacyServices)] 
                    : $allServices->random();

                // Select manager if available
                $manager = $users->count() > 0 ? $users->random() : null;

                $stockages[] = [
                    'name' => $storage->type . ' - ' . $stockageTypes[$stockageType] . ' #' . ($i + 1),
                    'location' => $storage->location . ' - Position ' . ($i + 1),
                    'capacity' => rand(50, 5000), // Units
                    'type' => $stockageType,
                    'description' => 'Pharmacy stockage: ' . $stockageTypes[$stockageType] . ' for ' . $storage->type . ' - Unit ' . ($i + 1),
                    'service_id' => $service->id,
                    'status' => rand(0, 1) < 85 ? 'active' : 'maintenance',
                    'manager_id' => $manager ? $manager->id : null,
                    'temperature_controlled' => $isTemperatureControlled,
                    'security_level' => $isSecure ? $securityLevels[array_rand($securityLevels)] : 'basic',
                    'location_code' => 'ST-' . str_pad($stockageId, 4, '0', STR_PAD_LEFT),
                    'warehouse_type' => $warehouseTypes[array_rand($warehouseTypes)],
                    'pharmacy_storage_id' => $storage->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $stockageId++;
            }
        }

        // Batch insert
        foreach (array_chunk($stockages, 25) as $batch) {
            PharmacyStockage::insert($batch);
        }

        $this->command->info('✓ Successfully seeded ' . count($stockages) . ' pharmacy stockage units!');
    }
}
