<?php

namespace Database\Seeders;

use App\Models\CONFIGURATION\Prestation;
use App\Models\CONFIGURATION\Service;
use App\Models\Specialization;
use App\Models\CONFIGURATION\ModalityType;
use App\Services\PrestationValidationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class AdvancedPrestationSeeder extends Seeder
{
    private PrestationValidationService $validationService;
    private array $performanceMetrics = [];
    private array $validationResults = [];
    private int $successCount = 0;
    private int $failureCount = 0;

    public function __construct()
    {
        $this->validationService = new PrestationValidationService();
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $startTime = microtime(true);
        $this->performanceMetrics['start_time'] = $startTime;
        
        if ($this->command) {
            $this->command->info('🚀 Starting Advanced Prestation Seeder...');
        }
        
        // Step 1: Validate model structure
        $this->validateModelStructure();
        
        // Step 2: Prepare dependencies
        $this->prepareDependencies();
        
        // Step 3: Generate and seed prestations
        $this->generatePrestations();
        
        // Step 4: Generate performance report
        $this->generatePerformanceReport();
        
        $endTime = microtime(true);
        $this->performanceMetrics['end_time'] = $endTime;
        $this->performanceMetrics['total_duration'] = $endTime - $startTime;
        
        if ($this->command) {
            $this->command->info('✅ Advanced Prestation Seeder completed successfully!');
        }
        $this->displaySummary();
    }

    /**
     * Validate model structure before seeding
     */
    private function validateModelStructure(): void
    {
        if ($this->command) {
            $this->command->info('🔍 Validating model structure...');
        }
        
        $validation = $this->validationService->validateModelStructure();
        $this->validationResults['model_structure'] = $validation;
        
        if (!$validation['valid']) {
            if ($this->command) {
                $this->command->error('❌ Model structure validation failed:');
                foreach ($validation['errors'] as $error) {
                    $this->command->error("  - {$error}");
                }
            }
            throw new \Exception('Model structure validation failed. Cannot proceed with seeding.');
        }
        
        if ($this->command) {
            $this->command->info('✅ Model structure validation passed');
            
            if (!empty($validation['missing_columns'])) {
                $this->command->warn('⚠️  Missing columns detected: ' . implode(', ', $validation['missing_columns']));
            }
        }
    }

    /**
     * Prepare required dependencies (Services, Specializations, ModalityTypes)
     */
    private function prepareDependencies(): void
    {
        if ($this->command) {
            $this->command->info('🔧 Preparing dependencies...');
        }
        
        $dependencyStartTime = microtime(true);
        
        // Ensure we have enough Services
        $serviceCount = Service::count();
        if ($serviceCount < 10) {
            if ($this->command) {
                $this->command->info("Creating additional services (current: {$serviceCount})...");
            }
            Service::factory()->count(10 - $serviceCount)->create();
        }
        
        // Ensure we have enough Specializations
        $specializationCount = Specialization::count();
        if ($specializationCount < 15) {
            if ($this->command) {
                $this->command->info("Creating additional specializations (current: {$specializationCount})...");
            }
            Specialization::factory()->count(15 - $specializationCount)->create();
        }
        
        // Ensure we have some ModalityTypes (optional)
        $modalityCount = ModalityType::count();
        if ($modalityCount < 5) {
            if ($this->command) {
                $this->command->info("Creating modality types (current: {$modalityCount})...");
            }
            try {
                ModalityType::factory()->count(5 - $modalityCount)->create();
            } catch (\Exception $e) {
                if ($this->command) {
                    $this->command->warn("Could not create ModalityTypes: " . $e->getMessage());
                }
            }
        }
        
        $dependencyEndTime = microtime(true);
        $this->performanceMetrics['dependency_preparation_time'] = $dependencyEndTime - $dependencyStartTime;
        
        if ($this->command) {
            $this->command->info('✅ Dependencies prepared');
        }
    }

    /**
     * Generate 100 unique prestation records
     */
    private function generatePrestations(): void
    {
        if ($this->command) {
            $this->command->info('📝 Generating 100 unique prestation records...');
        }
        
        $generationStartTime = microtime(true);
        
        // Get available dependencies
        $services = Service::all();
        $specializations = Specialization::all();
        $modalityTypes = ModalityType::all();
        
        $prestationData = $this->generateUniqueDataSet($services, $specializations, $modalityTypes);
        
        $generationEndTime = microtime(true);
        $this->performanceMetrics['data_generation_time'] = $generationEndTime - $generationStartTime;
        
        // Seed in batches with transaction handling
        $this->seedInBatches($prestationData);
    }

    /**
     * Generate unique dataset for 100 prestations
     */
    private function generateUniqueDataSet(Collection $services, Collection $specializations, Collection $modalityTypes): array
    {
        $prestationData = [];
        $usedInternalCodes = [];
        $usedBillingCodes = [];
        $usedPrices = [];
        
        // Realistic prestation categories with varied pricing
        $prestationCategories = [
            'consultation' => [
                'names' => [
                    'Consultation générale', 'Consultation spécialisée', 'Consultation de suivi',
                    'Consultation d\'urgence', 'Téléconsultation', 'Consultation préventive',
                    'Consultation post-opératoire', 'Consultation pédiatrique', 'Consultation gériatrique'
                ],
                'price_range' => [80, 300],
                'duration_range' => [15, 60]
            ],
            'examen' => [
                'names' => [
                    'Échographie abdominale', 'Radiographie thoracique', 'IRM cérébrale',
                    'Scanner abdominal', 'Mammographie', 'Électrocardiogramme',
                    'Échographie cardiaque', 'Endoscopie digestive', 'Coloscopie',
                    'Bronchoscopie', 'Arthroscopie', 'Biopsie'
                ],
                'price_range' => [150, 800],
                'duration_range' => [20, 120]
            ],
            'intervention' => [
                'names' => [
                    'Chirurgie ambulatoire', 'Intervention sous anesthésie locale',
                    'Chirurgie laparoscopique', 'Ablation de kyste', 'Suture de plaie',
                    'Extraction dentaire', 'Pose d\'implant', 'Chirurgie esthétique mineure',
                    'Infiltration articulaire', 'Ponction lombaire'
                ],
                'price_range' => [300, 2500],
                'duration_range' => [30, 180]
            ],
            'traitement' => [
                'names' => [
                    'Séance de kinésithérapie', 'Séance d\'orthophonie',
                    'Chimiothérapie', 'Radiothérapie', 'Dialyse',
                    'Perfusion intraveineuse', 'Pansement complexe',
                    'Rééducation fonctionnelle', 'Thérapie respiratoire'
                ],
                'price_range' => [50, 1200],
                'duration_range' => [30, 240]
            ],
            'urgence' => [
                'names' => [
                    'Consultation aux urgences', 'Prise en charge trauma',
                    'Réanimation cardio-pulmonaire', 'Stabilisation patient critique',
                    'Intervention d\'urgence', 'Défibrillation'
                ],
                'price_range' => [200, 1500],
                'duration_range' => [15, 300]
            ]
        ];

        for ($i = 0; $i < 100; $i++) {
            // Select random category
            $categoryKey = array_rand($prestationCategories);
            $category = $prestationCategories[$categoryKey];
            
            // Generate unique codes
            do {
                $internalCode = strtoupper(substr($categoryKey, 0, 2)) . sprintf('%04d', rand(1000, 9999));
            } while (in_array($internalCode, $usedInternalCodes));
            $usedInternalCodes[] = $internalCode;
            
            do {
                $billingCode = sprintf('%06d', rand(100000, 999999));
            } while (in_array($billingCode, $usedBillingCodes));
            $usedBillingCodes[] = $billingCode;
            
            // Generate unique price
            do {
                $basePrice = rand($category['price_range'][0], $category['price_range'][1]);
                $price = $basePrice + (rand(0, 99) / 100); // Add cents for uniqueness
            } while (in_array($price, $usedPrices));
            $usedPrices[] = $price;
            
            // Select random name from category
            $name = $category['names'][array_rand($category['names'])] . ' ' . ($i + 1);
            
            $prestationData[] = [
                'name' => $name,
                'internal_code' => $internalCode,
                'billing_code' => $billingCode,
                'description' => "Description détaillée pour {$name}",
                'service_id' => $services->random()->id,
                'specialization_id' => $specializations->random()->id,
                'type' => $categoryKey,
                'public_price' => $price,
                'convenience_prix' => $price * rand(120, 180) / 100,
                'tva_const_prestation' => rand(0, 20),
                'vat_rate' => rand(0, 20),
                'night_tariff' => rand(0, 50),
                'consumables_cost' => rand(0, 200),
                'is_social_security_reimbursable' => rand(0, 1) === 1,
                'reimbursement_conditions' => rand(0, 1) ? 'Remboursable selon conditions' : null,
                'non_applicable_discount_rules' => rand(0, 1) ? 'Pas de remise applicable' : null,
                'fee_distribution_model' => ['fixed', 'percentage', 'hybrid'][rand(0, 2)],
                'primary_doctor_share' => rand(40, 70),
                'primary_doctor_is_percentage' => true,
                'assistant_doctor_share' => rand(10, 25),
                'assistant_doctor_is_percentage' => true,
                'technician_share' => rand(5, 15),
                'technician_is_percentage' => true,
                'clinic_share' => rand(10, 25),
                'clinic_is_percentage' => true,
                'default_payment_type' => ['pre-pay', 'post-pay', 'versement'][rand(0, 2)],
                'min_versement_amount' => rand(0, 100),
                'need_an_appointment' => rand(0, 1) === 1,
                'requires_hospitalization' => $categoryKey === 'intervention' ? rand(0, 1) === 1 : false,
                'default_hosp_nights' => $categoryKey === 'intervention' ? rand(0, 3) : 0,
                'required_modality_type_id' => $modalityTypes->isNotEmpty() && rand(0, 1) ? $modalityTypes->random()->id : null,
                'default_duration_minutes' => rand($category['duration_range'][0], $category['duration_range'][1]),
                'required_prestations_info' => rand(0, 1) ? json_encode([rand(1, 10), rand(11, 20)]) : null,
                'patient_instructions' => rand(0, 1) ? 'Instructions spécifiques pour le patient' : null,
                'required_consents' => rand(0, 1) ? 'Consentement éclairé requis' : null,
                'is_active' => rand(0, 10) > 1, // 90% active
            ];
        }
        
        return $prestationData;
    }

    /**
     * Seed prestations in batches with transaction handling
     */
    private function seedInBatches(array $prestationData): void
    {
        $batchSize = 20;
        $batches = array_chunk($prestationData, $batchSize);
        $batchCount = count($batches);
        
        if ($this->command) {
            $this->command->info("🔄 Processing {$batchCount} batches of {$batchSize} records each...");
        }
        
        $seedingStartTime = microtime(true);
        
        foreach ($batches as $batchIndex => $batch) {
            $batchStartTime = microtime(true);
            
            DB::beginTransaction();
            
            try {
                foreach ($batch as $recordIndex => $data) {
                    $globalIndex = ($batchIndex * $batchSize) + $recordIndex + 1;
                    
                    // Validate data before creation
                    $validation = $this->validationService->validateAndPrepareData($data);
                    
                    if (!$validation['valid']) {
                        $this->failureCount++;
                        if ($this->command) {
                            $this->command->error("❌ Record {$globalIndex} validation failed:");
                            foreach ($validation['errors'] as $error) {
                                $this->command->error("  - {$error}");
                            }
                        }
                        continue;
                    }
                    
                    // Check uniqueness
                    $uniquenessCheck = $this->validationService->validateUniqueness($validation['data']);
                    if (!$uniquenessCheck['valid']) {
                        $this->failureCount++;
                        if ($this->command) {
                            $this->command->error("❌ Record {$globalIndex} uniqueness check failed:");
                            foreach ($uniquenessCheck['errors'] as $error) {
                                $this->command->error("  - {$error}");
                            }
                        }
                        continue;
                    }
                    
                    // Create prestation
                    Prestation::create($validation['data']);
                    $this->successCount++;
                    
                    if (!empty($validation['warnings']) && $this->command) {
                        $this->command->warn("⚠️  Record {$globalIndex} warnings:");
                        foreach ($validation['warnings'] as $warning) {
                            $this->command->warn("  - {$warning}");
                        }
                    }
                }
                
                DB::commit();
                
                $batchEndTime = microtime(true);
                $batchDuration = $batchEndTime - $batchStartTime;
                
                if ($this->command) {
                    $this->command->info("✅ Batch " . ($batchIndex + 1) . "/{$batchCount} completed in " . 
                                       number_format($batchDuration, 3) . "s");
                }
                
            } catch (\Exception $e) {
                DB::rollBack();
                if ($this->command) {
                    $this->command->error("❌ Batch " . ($batchIndex + 1) . " failed: " . $e->getMessage());
                }
                Log::error('Prestation seeding batch failed', [
                    'batch_index' => $batchIndex,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        
        $seedingEndTime = microtime(true);
        $this->performanceMetrics['seeding_time'] = $seedingEndTime - $seedingStartTime;
    }

    /**
     * Generate performance report
     */
    private function generatePerformanceReport(): void
    {
        $this->performanceMetrics['records_processed'] = 100;
        $this->performanceMetrics['successful_records'] = $this->successCount;
        $this->performanceMetrics['failed_records'] = $this->failureCount;
        $this->performanceMetrics['success_rate'] = ($this->successCount / 100) * 100;
        
        if ($this->performanceMetrics['seeding_time'] > 0) {
            $this->performanceMetrics['records_per_second'] = $this->successCount / $this->performanceMetrics['seeding_time'];
        }
        
        $this->performanceMetrics['memory_usage'] = [
            'peak' => memory_get_peak_usage(true),
            'current' => memory_get_usage(true)
        ];
        
        $this->performanceMetrics['database_stats'] = [
            'total_prestations' => Prestation::count(),
            'active_prestations' => Prestation::where('is_active', true)->count(),
            'services_count' => Service::count(),
            'specializations_count' => Specialization::count()
        ];
    }

    /**
     * Display seeding summary
     */
    private function displaySummary(): void
    {
        if ($this->command) {
            $this->command->info('');
            $this->command->info('📊 SEEDING SUMMARY');
            $this->command->info('==================');
            $this->command->info("✅ Successful records: {$this->successCount}");
            $this->command->info("❌ Failed records: {$this->failureCount}");
            $this->command->info("📈 Success rate: " . number_format($this->performanceMetrics['success_rate'], 2) . "%");
            $this->command->info("⏱️  Total duration: " . number_format($this->performanceMetrics['total_duration'], 3) . "s");
            $this->command->info("🚀 Records per second: " . number_format($this->performanceMetrics['records_per_second'] ?? 0, 2));
            $this->command->info("💾 Peak memory usage: " . $this->formatBytes($this->performanceMetrics['memory_usage']['peak']));
            $this->command->info("🏥 Total prestations in DB: " . $this->performanceMetrics['database_stats']['total_prestations']);
        }
        
        // Log detailed metrics
        Log::info('Advanced Prestation Seeder completed', $this->performanceMetrics);
    }

    /**
     * Get performance metrics for testing
     */
    public function getPerformanceMetrics(): array
    {
        return $this->performanceMetrics;
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}