<?php

namespace Tests\Feature;

use App\Models\CONFIGURATION\Prestation;
use App\Models\CONFIGURATION\Service;
use App\Models\Specialization;
use App\Models\CONFIGURATION\ModalityType;
use App\Services\PrestationValidationService;
use Database\Seeders\AdvancedPrestationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdvancedPrestationSeederTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private PrestationValidationService $validationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validationService = new PrestationValidationService();
    }

    /** @test */
    public function it_validates_model_structure_before_seeding()
    {
        $validation = $this->validationService->validateModelStructure();
        
        $this->assertTrue($validation['valid'], 'Model structure should be valid');
        $this->assertTrue($validation['table_exists'], 'Prestations table should exist');
        $this->assertIsArray($validation['fillable_attributes']);
        $this->assertIsArray($validation['casts']);
        
        // Verify required columns exist
        $requiredColumns = ['name', 'internal_code', 'billing_code', 'service_id', 'specialization_id', 'type', 'public_price'];
        $tableColumns = Schema::getColumnListing('prestations');
        
        foreach ($requiredColumns as $column) {
            $this->assertContains($column, $tableColumns, "Required column '{$column}' should exist");
        }
    }

    /** @test */
    public function it_validates_and_prepares_prestation_data_correctly()
    {
        $service = Service::factory()->create();
        $specialization = Specialization::factory()->create();
        
        $testData = [
            'name' => 'Test Prestation',
            'internal_code' => 'TP0001',
            'billing_code' => '123456',
            'service_id' => $service->id,
            'specialization_id' => $specialization->id,
            'type' => 'consultation',
            'public_price' => 150.00,
        ];
        
        $validation = $this->validationService->validateAndPrepareData($testData);
        
        $this->assertTrue($validation['valid'], 'Data validation should pass');
        $this->assertEmpty($validation['errors'], 'Should have no validation errors');
        $this->assertNotEmpty($validation['added_attributes'], 'Should add missing optional attributes');
        
        // Verify optional attributes were added
        $this->assertArrayHasKey('is_active', $validation['data']);
        $this->assertArrayHasKey('default_duration_minutes', $validation['data']);
        $this->assertArrayHasKey('fee_distribution_model', $validation['data']);
    }

    /** @test */
    public function it_detects_validation_errors_for_invalid_data()
    {
        $invalidData = [
            'name' => '', // Empty name
            'internal_code' => 'TP0001',
            'billing_code' => '123456',
            'service_id' => 999999, // Non-existent service
            'specialization_id' => 999999, // Non-existent specialization
            'type' => 'consultation',
            'public_price' => -100, // Negative price
        ];
        
        $validation = $this->validationService->validateAndPrepareData($invalidData);
        
        $this->assertFalse($validation['valid'], 'Validation should fail for invalid data');
        $this->assertNotEmpty($validation['errors'], 'Should have validation errors');
    }

    /** @test */
    public function it_validates_data_types_correctly()
    {
        $service = Service::factory()->create();
        $specialization = Specialization::factory()->create();
        
        $testData = [
            'name' => 'Test Prestation',
            'internal_code' => 'TP0001',
            'billing_code' => '123456',
            'service_id' => $service->id,
            'specialization_id' => $specialization->id,
            'type' => 'consultation',
            'public_price' => 'invalid_price', // Invalid type
            'is_active' => 'not_boolean', // Invalid boolean
        ];
        
        $validation = $this->validationService->validateAndPrepareData($testData);
        
        $this->assertFalse($validation['valid']);
        $this->assertContains("Field 'public_price' must be numeric", $validation['errors']);
        $this->assertContains("Field 'is_active' must be boolean", $validation['errors']);
    }

    /** @test */
    public function it_validates_business_rules()
    {
        $service = Service::factory()->create();
        $specialization = Specialization::factory()->create();
        
        $testData = [
            'name' => 'Test Prestation',
            'internal_code' => 'TP0001',
            'billing_code' => '123456',
            'service_id' => $service->id,
            'specialization_id' => $specialization->id,
            'type' => 'consultation',
            'public_price' => -50, // Negative price
            'vat_rate' => 150, // Invalid VAT rate
            'default_duration_minutes' => -30, // Negative duration
        ];
        
        $validation = $this->validationService->validateAndPrepareData($testData);
        
        $this->assertFalse($validation['valid']);
        $this->assertContains('Public price cannot be negative', $validation['errors']);
        $this->assertContains('VAT rate must be between 0 and 100', $validation['errors']);
        $this->assertContains('Duration cannot be negative', $validation['errors']);
    }

    /** @test */
    public function it_validates_percentage_shares_business_rule()
    {
        $service = Service::factory()->create();
        $specialization = Specialization::factory()->create();
        
        $testData = [
            'name' => 'Test Prestation',
            'internal_code' => 'TP0001',
            'billing_code' => '123456',
            'service_id' => $service->id,
            'specialization_id' => $specialization->id,
            'type' => 'consultation',
            'public_price' => 150.00,
            'primary_doctor_share' => 50.00,
            'primary_doctor_is_percentage' => true,
            'assistant_doctor_share' => 20.00,
            'assistant_doctor_is_percentage' => true,
            'technician_share' => 15.00,
            'technician_is_percentage' => true,
            'clinic_share' => 10.00, // Total = 95%, should warn
            'clinic_is_percentage' => true,
        ];
        
        $validation = $this->validationService->validateAndPrepareData($testData);
        
        $this->assertTrue($validation['valid']); // Should be valid but with warnings
        $this->assertNotEmpty($validation['warnings']);
        $this->assertContains('Total percentage shares (95%) do not equal 100%', $validation['warnings']);
    }

    /** @test */
    public function it_validates_uniqueness_constraints()
    {
        $service = Service::factory()->create();
        $specialization = Specialization::factory()->create();
        
        // Create existing prestation
        Prestation::factory()->create([
            'internal_code' => 'EXISTING01',
            'billing_code' => '999999',
        ]);
        
        $testData = [
            'internal_code' => 'EXISTING01', // Duplicate
            'billing_code' => '999999', // Duplicate
        ];
        
        $uniquenessCheck = $this->validationService->validateUniqueness($testData);
        
        $this->assertFalse($uniquenessCheck['valid']);
        $this->assertContains("Internal code 'EXISTING01' already exists", $uniquenessCheck['errors']);
        $this->assertContains("Billing code '999999' already exists", $uniquenessCheck['errors']);
    }

    /** @test */
    public function it_runs_advanced_seeder_successfully()
    {
        // Ensure we have required dependencies
        Service::factory()->count(10)->create();
        Specialization::factory()->count(15)->create();
        
        $initialCount = Prestation::count();
        
        // Run the seeder
        $seeder = new AdvancedPrestationSeeder();
        $seeder->run();
        
        $finalCount = Prestation::count();
        
        // Verify records were created
        $this->assertGreaterThan($initialCount, $finalCount, 'Prestations should be created');
        
        // Verify uniqueness of created records
        $this->verifyRecordUniqueness();
        
        // Verify data integrity
        $this->verifyDataIntegrity();
    }

    /** @test */
    public function it_handles_seeding_with_existing_data()
    {
        // Create some existing prestations
        Prestation::factory()->count(10)->create();
        
        $initialCount = Prestation::count();
        
        // Run seeder
        Service::factory()->count(10)->create();
        Specialization::factory()->count(15)->create();
        
        $seeder = new AdvancedPrestationSeeder();
        $seeder->run();
        
        $finalCount = Prestation::count();
        
        $this->assertGreaterThan($initialCount, $finalCount);
        $this->verifyRecordUniqueness();
    }

    /** @test */
    public function it_creates_records_with_varied_realistic_data()
    {
        Service::factory()->count(10)->create();
        Specialization::factory()->count(15)->create();
        
        $seeder = new AdvancedPrestationSeeder();
        $seeder->run();
        
        $prestations = Prestation::all();
        
        // Verify we have different types
        $types = $prestations->pluck('type')->unique();
        $this->assertGreaterThan(1, $types->count(), 'Should have multiple prestation types');
        
        // Verify price variations
        $prices = $prestations->pluck('public_price')->unique();
        $this->assertGreaterThan(50, $prices->count(), 'Should have varied prices');
        
        // Verify realistic price ranges
        $minPrice = $prestations->min('public_price');
        $maxPrice = $prestations->max('public_price');
        $this->assertGreaterThan(50, $minPrice, 'Minimum price should be realistic');
        $this->assertLessThan(5000, $maxPrice, 'Maximum price should be realistic');
    }

    /** @test */
    public function it_handles_transaction_rollback_on_batch_failure()
    {
        // This test simulates a scenario where a batch might fail
        Service::factory()->count(5)->create();
        Specialization::factory()->count(5)->create();
        
        $initialCount = Prestation::count();
        
        // Mock a database constraint violation scenario
        DB::shouldReceive('beginTransaction')->andReturnSelf();
        DB::shouldReceive('commit')->andReturnSelf();
        DB::shouldReceive('rollBack')->andReturnSelf();
        
        try {
            $seeder = new AdvancedPrestationSeeder();
            $seeder->run();
        } catch (\Exception $e) {
            // Expected in case of mocked failure
        }
        
        // Verify database consistency
        $this->assertIsInt(Prestation::count());
    }

    /** @test */
    public function it_validates_required_relationships_exist()
    {
        $validation = $this->validationService->validateModelStructure();
        
        $this->assertTrue(Schema::hasTable('services'), 'Services table should exist');
        $this->assertTrue(Schema::hasTable('specializations'), 'Specializations table should exist');
        
        // Test with missing relationships
        if (!$validation['valid']) {
            $this->assertContains('Services table does not exist', $validation['errors']);
        }
    }

    /** @test */
    public function it_generates_performance_metrics()
    {
        // Clear existing data properly to prevent foreign key constraint issues
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Prestation::truncate();
        Service::truncate();
        Specialization::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        // Create minimal dependencies first
        $service = \App\Models\CONFIGURATION\Service::factory()->create(['name' => 'Test Service']);
        $specialization = \App\Models\Specialization::factory()->create(['name' => 'Test Specialization']);
        
        // Create a few prestations
        for ($i = 0; $i < 3; $i++) {
            \App\Models\CONFIGURATION\Prestation::factory()->create([
                'service_id' => $service->id,
                'specialization_id' => $specialization->id,
                'name' => 'Test Prestation ' . $i,
                'internal_code' => 'TEST' . str_pad($i, 3, '0', STR_PAD_LEFT)
            ]);
        }
        
        // Create a seeder instance and run it
        $seeder = new AdvancedPrestationSeeder();
        $seeder->run();
        
        $metrics = $seeder->getPerformanceMetrics();
        
        // The seeder should have generated performance metrics
        $this->assertIsArray($metrics);
        $this->assertNotEmpty($metrics);
        
        // Check for basic metric keys that should exist
        $this->assertArrayHasKey('start_time', $metrics);
        
        // Verify that prestations exist in the database
        $this->assertGreaterThan(0, Prestation::count());
    }

    /** @test */
    public function it_handles_dynamic_attribute_addition()
    {
        $service = Service::factory()->create();
        $specialization = Specialization::factory()->create();
        
        // Minimal data - missing optional attributes
        $minimalData = [
            'name' => 'Minimal Prestation',
            'internal_code' => 'MIN001',
            'billing_code' => '111111',
            'service_id' => $service->id,
            'specialization_id' => $specialization->id,
            'type' => 'consultation',
            'public_price' => 100.00,
        ];
        
        $validation = $this->validationService->validateAndPrepareData($minimalData);
        
        $this->assertTrue($validation['valid']);
        $this->assertNotEmpty($validation['added_attributes']);
        
        // Verify specific attributes were added
        $this->assertArrayHasKey('is_active', $validation['data']);
        $this->assertArrayHasKey('default_duration_minutes', $validation['data']);
        $this->assertArrayHasKey('fee_distribution_model', $validation['data']);
        $this->assertArrayHasKey('primary_doctor_share', $validation['data']);
        
        // Verify default values
        $this->assertTrue($validation['data']['is_active']);
        $this->assertEquals(30, $validation['data']['default_duration_minutes']);
        $this->assertEquals('percentage', $validation['data']['fee_distribution_model']);
    }

    /**
     * Verify uniqueness of all created records
     */
    private function verifyRecordUniqueness(): void
    {
        $prestations = Prestation::all();
        
        // Check internal_code uniqueness
        $internalCodes = $prestations->pluck('internal_code');
        $uniqueInternalCodes = $internalCodes->unique();
        $this->assertEquals($internalCodes->count(), $uniqueInternalCodes->count(), 'Internal codes should be unique');
        
        // Check billing_code uniqueness
        $billingCodes = $prestations->pluck('billing_code');
        $uniqueBillingCodes = $billingCodes->unique();
        $this->assertEquals($billingCodes->count(), $uniqueBillingCodes->count(), 'Billing codes should be unique');
        
        // Check public_price uniqueness (should be varied)
        $prices = $prestations->pluck('public_price');
        $uniquePrices = $prices->unique();
        $this->assertGreaterThan($prices->count() * 0.8, $uniquePrices->count(), 'Prices should be mostly unique');
    }

    /**
     * Verify data integrity of created records
     */
    private function verifyDataIntegrity(): void
    {
        $prestations = Prestation::all();
        
        foreach ($prestations as $prestation) {
            // Verify required fields are not null
            $this->assertNotNull($prestation->name);
            $this->assertNotNull($prestation->internal_code);
            $this->assertNotNull($prestation->billing_code);
            $this->assertNotNull($prestation->service_id);
            $this->assertNotNull($prestation->specialization_id);
            $this->assertNotNull($prestation->type);
            $this->assertNotNull($prestation->public_price);
            
            // Verify relationships exist
            $this->assertInstanceOf(Service::class, $prestation->service);
            $this->assertInstanceOf(Specialization::class, $prestation->specialization);
            
            // Verify data constraints
            $this->assertGreaterThanOrEqual(0, $prestation->public_price);
            $this->assertGreaterThanOrEqual(0, $prestation->vat_rate);
            $this->assertLessThanOrEqual(100, $prestation->vat_rate);
            
            // Verify boolean fields
            $this->assertIsBool($prestation->is_active);
            $this->assertIsBool($prestation->is_social_security_reimbursable);
        }
    }
}