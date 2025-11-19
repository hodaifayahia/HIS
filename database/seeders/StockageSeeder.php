<?php

namespace Database\Seeders;

use App\Models\Stockage;
use App\Models\CONFIGURATION\Service;
use Illuminate\Database\Seeder;

class StockageSeeder extends Seeder
{
    /**
     * Seed the stockage table with comprehensive storage management data.
     * Creates various types of storage locations with realistic details.
     */
    public function run(): void
    {
        // Storage types
        $types = ['warehouse', 'pharmacy', 'laboratory', 'emergency', 'storage_room', 'cold_room'];
        
        // Warehouse types
        $warehouseTypes = ['Central Pharmacy (PC)', 'Service Pharmacy (PS)'];
        
        // Security levels
        $securityLevels = ['low', 'medium', 'high', 'restricted'];
        
        // Status options
        $statuses = ['active', 'inactive', 'maintenance', 'under_construction'];
        
        // Storage locations/areas
        $locations = [
            'Ground Floor - Main Warehouse',
            'Ground Floor - Secondary Storage',
            'First Floor - Central Pharmacy',
            'First Floor - Service Pharmacy A',
            'First Floor - Service Pharmacy B',
            'Second Floor - Laboratory Storage',
            'Basement Level 1 - Cold Storage',
            'Basement Level 1 - Controlled Substances',
            'Basement Level 2 - Archive Storage',
            'Emergency Department - Quick Access',
            'Emergency Department - Backup Stock',
            'ICU - Critical Supplies',
            'Surgery Block - Sterile Room',
            'Surgery Block - Instrument Storage',
            'Maternity - Delivery Supplies',
            'Pediatrics - Medication Storage',
            'Orthopedic - Equipment Storage',
            'Cardiology - Specialized Equipment',
            'Radiology - Film Storage',
            'Pathology - Sample Storage',
        ];
        
        // Storage descriptions
        $descriptions = [
            'Main pharmaceutical warehouse with climate control',
            'Secondary storage for overflow inventory',
            'Central pharmacy serving all departments',
            'Service pharmacy for ward A medications',
            'Service pharmacy for ward B medications',
            'Laboratory specimen and reagent storage',
            'Temperature controlled storage (-20°C)',
            'Secure storage for controlled substances with access log',
            'Archive storage for historical records and documents',
            'Quick access emergency medical supplies',
            'Emergency backup stock for critical situations',
            'Critical care unit medication and equipment storage',
            'Sterile surgical instruments and equipment',
            'Non-sterile surgical instruments storage',
            'Obstetric delivery supplies and emergency medications',
            'Pediatric medications and specialized equipment',
            'Orthopedic implants and surgical equipment',
            'Cardiology medications and monitoring equipment',
            'X-ray film and imaging media storage',
            'Pathology samples and reagent storage',
            'General pharmaceutical storage',
            'Vaccine and immunobiological storage',
            'Controlled temperature medication storage',
            'Hazardous materials and chemical storage',
            'Medical supplies and consumables',
            'Diagnostic equipment and reagents',
            'Sterile dressings and wound care supplies',
            'IV fluids and injectable medications',
            'Oral medications and tablets',
            'Topical preparations and creams',
        ];
        
        // Location codes
        $locationCodes = [
            'WH-001', 'WH-002', 'WH-003', 'WH-004', 'WH-005',
            'PC-001', 'PC-002', 'PC-003', 'PC-004', 'PC-005',
            'SP-A01', 'SP-A02', 'SP-A03', 'SP-A04', 'SP-A05',
            'SP-B01', 'SP-B02', 'SP-B03', 'SP-B04', 'SP-B05',
            'LAB-001', 'LAB-002', 'LAB-003', 'LAB-004', 'LAB-005',
            'COLD-B1-01', 'COLD-B1-02', 'COLD-B1-03',
            'CS-001', 'CS-002', 'CS-003', 'CS-004', 'CS-005',
            'ARCHIVE-B2-001', 'ARCHIVE-B2-002', 'ARCHIVE-B2-003',
            'ED-001', 'ED-002', 'ED-003', 'ED-004', 'ED-005',
            'ICU-001', 'ICU-002', 'ICU-003',
            'OR-001', 'OR-002', 'OR-003', 'OR-004', 'OR-005',
            'MAT-001', 'MAT-002', 'MAT-003',
            'PED-001', 'PED-002', 'PED-003',
            'ORTHO-001', 'ORTHO-002', 'ORTHO-003',
            'CARDIO-001', 'CARDIO-002',
            'RAD-001', 'RAD-002',
            'PATH-001', 'PATH-002',
        ];
        
        // Get all services
        $services = Service::select('id')->get();
        $serviceIds = $services->pluck('id')->toArray();
        
        if (empty($serviceIds)) {
            echo "No services found. Please seed services first.\n";
            return;
        }

        $batchSize = 50;
        $stockages = [];
        $totalStockages = 100;
        $locationCodeIndex = 0;

        for ($i = 1; $i <= $totalStockages; $i++) {
            $type = $types[array_rand($types)];
            $isPharmacy = $type === 'pharmacy';
            $isControlledStorage = $type === 'cold_room' || ($type === 'storage_room' && rand(0, 100) < 30);
            
            $stockages[] = [
                'name' => $this->generateStorageName($type, $i),
                'description' => $descriptions[array_rand($descriptions)],
                'location' => $locations[array_rand($locations)],
                'capacity' => rand(100, 10000), // Storage capacity in units
                'type' => $type,
                'status' => $statuses[array_rand($statuses)],
                'service_id' => $serviceIds[array_rand($serviceIds)],
                'temperature_controlled' => $type === 'cold_room' || ($type === 'pharmacy' && rand(0, 100) < 40),
                'security_level' => $isControlledStorage ? 'high' : ($type === 'emergency' ? 'medium' : $securityLevels[array_rand($securityLevels)]),
                'location_code' => $locationCodes[$locationCodeIndex % count($locationCodes)],
                'warehouse_type' => $isPharmacy ? $warehouseTypes[array_rand($warehouseTypes)] : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            
            $locationCodeIndex++;

            // Insert in batches
            if (count($stockages) === $batchSize || $i === $totalStockages) {
                try {
                    Stockage::insert($stockages);
                    $progressPercent = round(($i / $totalStockages) * 100);
                    echo "Progress: {$progressPercent}% ({$i}/{$totalStockages} stockages seeded)\n";
                } catch (\Exception $e) {
                    echo "Error at stockage {$i}: " . $e->getMessage() . "\n";
                    throw $e;
                }
                $stockages = [];
            }
        }

        echo "✓ Successfully seeded {$totalStockages} stockages!\n";
    }

    /**
     * Generate meaningful storage names based on type
     */
    private function generateStorageName($type, $index)
    {
        $names = [];

        switch ($type) {
            case 'warehouse':
                $names = [
                    'Central Warehouse - Block ' . chr(65 + (($index - 1) % 26)),
                    'Main Storage Facility ' . $index,
                    'Pharmaceutical Warehouse ' . $index,
                    'Distribution Center ' . $index,
                    'Bulk Storage Unit ' . $index,
                ];
                break;

            case 'pharmacy':
                $names = [
                    'Central Pharmacy - Counter ' . $index,
                    'Service Pharmacy Ward A',
                    'Service Pharmacy Ward B',
                    'Inpatient Pharmacy - ' . $index,
                    'Outpatient Pharmacy - ' . $index,
                    'Emergency Pharmacy - ' . $index,
                    'Dispensary - ' . $index,
                ];
                break;

            case 'laboratory':
                $names = [
                    'Lab Storage Room - ' . $index,
                    'Specimen Storage Lab - ' . $index,
                    'Reagent Storage - ' . $index,
                    'Chemistry Lab Storage - ' . $index,
                    'Hematology Storage - ' . $index,
                ];
                break;

            case 'emergency':
                $names = [
                    'Emergency Department Cache ' . $index,
                    'ED Quick Access - ' . $index,
                    'Trauma Room Storage - ' . $index,
                    'Emergency Supply Station - ' . $index,
                    'Resuscitation Cache - ' . $index,
                ];
                break;

            case 'storage_room':
                $names = [
                    'General Storage Room ' . $index,
                    'Department Storage - ' . $index,
                    'Utility Storage ' . $index,
                    'Supply Storage ' . $index,
                    'Equipment Storage Room ' . $index,
                    'Consumables Storage - ' . $index,
                ];
                break;

            case 'cold_room':
                $names = [
                    'Refrigerated Storage - ' . $index . ' (2-8°C)',
                    'Freezer Storage - ' . $index . ' (-20°C)',
                    'Vaccine Cold Room - ' . $index,
                    'Controlled Temperature Room ' . $index,
                    'Ultra-Low Freezer ' . $index . ' (-80°C)',
                    'Temperature Controlled Storage - ' . $index,
                ];
                break;

            default:
                $names = ['Storage Location ' . $index];
        }

        return $names[array_rand($names)];
    }
}