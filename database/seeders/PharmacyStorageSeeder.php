<?php

namespace Database\Seeders;

use App\Models\PharmacyStorage;
use App\Models\CONFIGURATION\Service;
use Illuminate\Database\Seeder;

class PharmacyStorageSeeder extends Seeder
{
    /**
     * Seed the pharmacy_storages table with various storage types and configurations.
     */
    public function run(): void
    {
        $storageTypes = [
            'general_pharmacy' => 'General Pharmacy Storage',
            'controlled_substances' => 'Controlled Substances Vault',
            'refrigerated' => 'Refrigerated Storage',
            'frozen' => 'Frozen Storage',
            'hazardous' => 'Hazardous Materials Storage',
            'compounding' => 'Compounding Storage',
            'bulk_storage' => 'Bulk Storage',
            'quarantine' => 'Quarantine Storage',
            'returns' => 'Returns Storage',
        ];

        $securityLevels = ['level_1', 'level_2', 'level_3', 'level_4', 'level_5'];
        $accessControlLevels = ['open', 'staff_only', 'pharmacist_only', 'authorized_personnel', 'dual_control', 'biometric'];
        $monitoringSystems = ['none', 'basic', 'advanced', 'real_time', 'iot_enabled'];
        $complianceCertifications = ['ISO 9001:2015', 'GMP Certified', 'USP Verified', 'ISO 13485', 'FDA Registered'];

        $storages = [];
        $storageId = 1;

        // Create different storage configurations
        foreach ($storageTypes as $typeKey => $typeName) {
            // Determine characteristics based on storage type
            $isControlledSubstances = $typeKey === 'controlled_substances';
            $needsTemperatureControl = in_array($typeKey, ['refrigerated', 'frozen', 'compounding']);
            $needsHumidityControl = $typeKey !== 'frozen';
            $needsLightProtection = $typeKey !== 'quarantine';

            // Determine security level based on type
            $securityLevel = match ($typeKey) {
                'controlled_substances' => 'level_4',
                'refrigerated', 'frozen' => 'level_3',
                'compounding' => 'level_2',
                default => 'level_1',
            };

            // Determine access control based on type
            $accessControl = match ($typeKey) {
                'controlled_substances' => 'dual_control',
                'compounding', 'refrigerated' => 'pharmacist_only',
                'quarantine' => 'authorized_personnel',
                default => 'staff_only',
            };

            // Create 2-3 storage units of each type for realistic scenario
            $numStorages = match ($typeKey) {
                'general_pharmacy' => 3,
                'controlled_substances' => 2,
                'refrigerated' => 2,
                default => 2,
            };

            for ($i = 0; $i < $numStorages; $i++) {
                $storages[] = [
                    'name' => $typeName . ' Unit ' . ($i + 1),
                    'description' => 'Pharmacy storage for ' . strtolower($typeName) . ' - Unit ' . ($i + 1) . ' with full compliance',
                    'location' => 'Pharmacy Wing - ' . $typeName . ' Area - Position ' . ($i + 1),
                    'capacity' => rand(100, 10000), // Units vary by storage type
                    'type' => $typeKey,
                    'status' => rand(0, 1) < 90 ? 'active' : 'maintenance',
                    'service_id' => null, // Will be assigned to pharmacy services
                    'temperature_controlled' => $needsTemperatureControl ? true : false,
                    'security_level' => $securityLevel,
                    'location_code' => 'PS-' . str_pad($storageId, 3, '0', STR_PAD_LEFT),
                    'warehouse_type' => rand(0, 1) ? 'Central Pharmacy (PC)' : 'Service Pharmacy (PS)',
                    'controlled_substance_vault' => $isControlledSubstances,
                    'refrigeration_unit' => in_array($typeKey, ['refrigerated', 'frozen']),
                    'humidity_controlled' => $needsHumidityControl,
                    'light_protection' => $needsLightProtection,
                    'access_control_level' => $accessControl,
                    'pharmacist_access_only' => $typeKey === 'compounding',
                    'dea_registration_required' => $isControlledSubstances,
                    'temperature_min' => $typeKey === 'frozen' ? -25.0 : ($typeKey === 'refrigerated' ? 2.0 : 15.0),
                    'temperature_max' => $typeKey === 'frozen' ? -20.0 : ($typeKey === 'refrigerated' ? 8.0 : 25.0),
                    'humidity_min' => 30.0,
                    'humidity_max' => 70.0,
                    'monitoring_system' => $isControlledSubstances ? 'iot_enabled' : ($typeKey === 'refrigerated' ? 'real_time' : 'basic'),
                    'backup_power' => $isControlledSubstances || $needsTemperatureControl ? true : false,
                    'alarm_system' => $isControlledSubstances || $typeKey === 'hazardous' ? true : false,
                    'compliance_certification' => $isControlledSubstances ? $complianceCertifications[array_rand($complianceCertifications)] : null,
                    'last_inspection_date' => now()->subMonths(rand(1, 6)),
                    'next_inspection_due' => now()->addMonths(rand(1, 6)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $storageId++;
            }
        }

        // Batch insert
        foreach (array_chunk($storages, 10) as $batch) {
            PharmacyStorage::insert($batch);
        }

        $this->command->info('✓ Successfully seeded ' . count($storages) . ' pharmacy storage units!');
    }
}
