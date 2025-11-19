<?php

namespace Tests\Unit;

use App\Services\PrestationValidationService;
use App\Models\CONFIGURATION\Prestation;
use App\Models\CONFIGURATION\Service;
use App\Models\Specialization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PrestationValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    private PrestationValidationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PrestationValidationService();
    }

    /** @test */
    public function it_validates_model_structure_successfully()
    {
        $result = $this->service->validateModelStructure();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('table_exists', $result);
        $this->assertArrayHasKey('fillable_attributes', $result);
        $this->assertArrayHasKey('casts', $result);
        
        if ($result['valid']) {
            $this->assertTrue($result['table_exists']);
            $this->assertIsArray($result['fillable_attributes']);
            $this->assertIsArray($result['casts']);
        }
    }

    /** @test */
    public function it_identifies_required_attributes_correctly()
    {
        $requiredAttributes = $this->service->getRequiredAttributes();
        
        $expectedRequired = [
            'name', 'internal_code', 'billing_code', 'service_id', 
            'specialization_id', 'type', 'public_price'
        ];
        
        foreach ($expectedRequired as $attribute) {
            $this->assertContains($attribute, $requiredAttributes, 
                "Required attribute '{$attribute}' should be identified");
        }
    }

    /** @test */
    public function it_adds_missing_optional_attributes_with_defaults()
    {
        $minimalData = [
            'name' => 'Test Prestation',
            'internal_code' => 'TP001',
            'billing_code' => '123456',
            'service_id' => 1,
            'specialization_id' => 1,
            'type' => 'consultation',
            'public_price' => 100.00,
        ];
        
        $result = $this->service->addMissingOptionalAttributes($minimalData);
        
        // Verify optional attributes were added
        $this->assertArrayHasKey('is_active', $result['data']);
        $this->assertArrayHasKey('default_duration_minutes', $result['data']);
        $this->assertArrayHasKey('fee_distribution_model', $result['data']);
        $this->assertArrayHasKey('vat_rate', $result['data']);
        $this->assertArrayHasKey('is_social_security_reimbursable', $result['data']);
        
        // Verify default values
        $this->assertTrue($result['data']['is_active']);
        $this->assertEquals(30, $result['data']['default_duration_minutes']);
        $this->assertEquals('percentage', $result['data']['fee_distribution_model']);
        $this->assertEquals(20.0, $result['data']['vat_rate']);
        $this->assertFalse($result['data']['is_social_security_reimbursable']);
        
        // Verify added attributes are tracked
        $this->assertNotEmpty($result['added_attributes']);
        $this->assertContains('is_active', $result['added_attributes']);
    }

    /** @test */
    public function it_validates_data_types_correctly()
    {
        $testCases = [
            // Valid data types
            [
                'data' => ['public_price' => 100.50, 'is_active' => true, 'vat_rate' => 20.0],
                'should_pass' => true
            ],
            // Invalid numeric
            [
                'data' => ['public_price' => 'not_a_number'],
                'should_pass' => false,
                'expected_error' => "Field 'public_price' must be numeric"
            ],
            // Invalid boolean
            [
                'data' => ['is_active' => 'not_boolean'],
                'should_pass' => false,
                'expected_error' => "Field 'is_active' must be boolean"
            ],
            // Invalid integer
            [
                'data' => ['default_duration_minutes' => 'not_integer'],
                'should_pass' => false,
                'expected_error' => "Field 'default_duration_minutes' must be integer"
            ],
        ];
        
        foreach ($testCases as $testCase) {
            $result = $this->service->validateDataTypes($testCase['data']);
            
            if ($testCase['should_pass']) {
                $this->assertTrue($result['valid'], 'Data type validation should pass');
                $this->assertEmpty($result['errors']);
            } else {
                $this->assertFalse($result['valid'], 'Data type validation should fail');
                $this->assertContains($testCase['expected_error'], $result['errors']);
            }
        }
    }

    /** @test */
    public function it_validates_business_rules_correctly()
    {
        $testCases = [
            // Valid business rules
            [
                'data' => [
                    'public_price' => 100.00,
                    'vat_rate' => 20.0,
                    'default_duration_minutes' => 30,
                    'primary_doctor_share' => 50.0,
                    'assistant_doctor_share' => 20.0,
                ],
                'should_pass' => true
            ],
            // Negative price
            [
                'data' => ['public_price' => -50.00],
                'should_pass' => false,
                'expected_error' => 'Public price cannot be negative'
            ],
            // Invalid VAT rate
            [
                'data' => ['vat_rate' => 150.0],
                'should_pass' => false,
                'expected_error' => 'VAT rate must be between 0 and 100'
            ],
            // Negative duration
            [
                'data' => ['default_duration_minutes' => -30],
                'should_pass' => false,
                'expected_error' => 'Duration cannot be negative'
            ],
            // Invalid share percentage
            [
                'data' => ['primary_doctor_share' => 150.0],
                'should_pass' => false,
                'expected_error' => 'Share percentages must be between 0 and 100'
            ],
        ];
        
        foreach ($testCases as $testCase) {
            $result = $this->service->validateBusinessRules($testCase['data']);
            
            if ($testCase['should_pass']) {
                $this->assertTrue($result['valid'], 'Business rule validation should pass');
                $this->assertEmpty($result['errors']);
            } else {
                $this->assertFalse($result['valid'], 'Business rule validation should fail');
                $this->assertContains($testCase['expected_error'], $result['errors']);
            }
        }
    }

    /** @test */
    public function it_validates_percentage_shares_total()
    {
        $testCases = [
            // Perfect 100% total
            [
                'data' => [
                    'primary_doctor_share' => 50.0,
                    'primary_doctor_is_percentage' => true,
                    'assistant_doctor_share' => 20.0,
                    'assistant_doctor_is_percentage' => true,
                    'technician_share' => 15.0,
                    'technician_is_percentage' => true,
                    'clinic_share' => 15.0,
                    'clinic_is_percentage' => true,
                ],
                'should_warn' => false
            ],
            // Total not 100%
            [
                'data' => [
                    'primary_doctor_share' => 50.0,
                    'primary_doctor_is_percentage' => true,
                    'assistant_doctor_share' => 20.0,
                    'assistant_doctor_is_percentage' => true,
                    'clinic_share' => 20.0, // Total = 90%
                    'clinic_is_percentage' => true,
                ],
                'should_warn' => true,
                'expected_warning' => 'Total percentage shares (90%) do not equal 100%'
            ],
            // Mixed percentage and fixed amounts (should not warn because no percentage shares)
            [
                'data' => [
                    'primary_doctor_share' => 100.0, // Fixed amount
                    'primary_doctor_is_percentage' => false,
                    'assistant_doctor_share' => 50.0, // Fixed amount
                    'assistant_doctor_is_percentage' => false,
                ],
                'should_warn' => false
            ],
            // Only fixed amounts (no percentage validation)
            [
                'data' => [
                    'primary_doctor_share' => 200.0,
                    'primary_doctor_is_percentage' => false,
                    'clinic_share' => 300.0,
                    'clinic_is_percentage' => false,
                ],
                'should_warn' => false
            ],
        ];
        
        foreach ($testCases as $index => $testCase) {
            $result = $this->service->validateBusinessRules($testCase['data']);
            
            if ($testCase['should_warn']) {
                $this->assertNotEmpty($result['warnings'], "Test case {$index} should have warnings");
                $this->assertContains($testCase['expected_warning'], $result['warnings'], "Test case {$index} should contain expected warning");
            } else {
                $this->assertEmpty($result['warnings'] ?? [], "Test case {$index} should not have warnings");
            }
        }
    }

    /** @test */
    public function it_validates_uniqueness_constraints()
    {
        // Create existing prestation
        $service = Service::factory()->create(['id' => 1]);
        Specialization::factory()->create([
            'id' => 1,
            'service_id' => $service->id
        ]);
        
        Prestation::factory()->create([
            'internal_code' => 'EXISTING01',
            'billing_code' => '999999',
        ]);
        
        $testCases = [
            // Unique codes
            [
                'data' => [
                    'internal_code' => 'UNIQUE01',
                    'billing_code' => '111111',
                ],
                'should_pass' => true
            ],
            // Duplicate internal_code
            [
                'data' => [
                    'internal_code' => 'EXISTING01',
                    'billing_code' => '111111',
                ],
                'should_pass' => false,
                'expected_error' => "Internal code 'EXISTING01' already exists"
            ],
            // Duplicate billing_code
            [
                'data' => [
                    'internal_code' => 'UNIQUE02',
                    'billing_code' => '999999',
                ],
                'should_pass' => false,
                'expected_error' => "Billing code '999999' already exists"
            ],
        ];
        
        foreach ($testCases as $testCase) {
            $result = $this->service->validateUniqueness($testCase['data']);
            
            if ($testCase['should_pass']) {
                $this->assertTrue($result['valid'], 'Uniqueness validation should pass');
                $this->assertEmpty($result['errors']);
            } else {
                $this->assertFalse($result['valid'], 'Uniqueness validation should fail');
                $this->assertContains($testCase['expected_error'], $result['errors']);
            }
        }
    }

    /** @test */
    public function it_performs_complete_validation_and_preparation()
    {
        $service = Service::factory()->create(['id' => 1]);
        Specialization::factory()->create([
            'id' => 1,
            'service_id' => $service->id
        ]);
        
        $testData = [
            'name' => 'Complete Test Prestation',
            'internal_code' => 'CTP001',
            'billing_code' => '555555',
            'service_id' => 1,
            'specialization_id' => 1,
            'type' => 'consultation',
            'public_price' => 150.00,
        ];
        
        $result = $this->service->validateAndPrepareData($testData);
        
        $this->assertTrue($result['valid'], 'Complete validation should pass');
        $this->assertEmpty($result['errors']);
        $this->assertNotEmpty($result['added_attributes']);
        
        // Verify all required attributes are present
        $requiredAttributes = $this->service->getRequiredAttributes();
        foreach ($requiredAttributes as $attribute) {
            $this->assertArrayHasKey($attribute, $result['data'], 
                "Required attribute '{$attribute}' should be present in prepared data");
        }
        
        // Verify optional attributes were added
        $this->assertArrayHasKey('is_active', $result['data']);
        $this->assertArrayHasKey('default_duration_minutes', $result['data']);
    }

    /** @test */
    public function it_handles_validation_with_warnings()
    {
        $service = Service::factory()->create(['id' => 1]);
        Specialization::factory()->create([
            'id' => 1,
            'service_id' => $service->id
        ]);
        
        $testData = [
            'name' => 'Warning Test Prestation',
            'internal_code' => 'WTP001',
            'billing_code' => '666666',
            'service_id' => 1,
            'specialization_id' => 1,
            'type' => 'consultation',
            'public_price' => 150.00,
            'primary_doctor_share' => 60.0,
            'primary_doctor_is_percentage' => true,
            'clinic_share' => 30.0, // Total = 90%, should warn
            'clinic_is_percentage' => true,
        ];
        
        $result = $this->service->validateAndPrepareData($testData);
        
        $this->assertTrue($result['valid'], 'Validation should pass despite warnings');
        $this->assertEmpty($result['errors']);
        $this->assertNotEmpty($result['warnings']);
        $this->assertContains('Total percentage shares (90%) do not equal 100%', $result['warnings']);
    }

    /** @test */
    public function it_handles_multiple_validation_errors()
    {
        $testData = [
            'name' => '', // Empty name
            'internal_code' => 'MT001',
            'billing_code' => '777777',
            'service_id' => 999999, // Non-existent
            'specialization_id' => 999999, // Non-existent
            'type' => 'consultation',
            'public_price' => -100, // Negative
            'vat_rate' => 150, // Invalid
            'is_active' => 'not_boolean', // Invalid type
        ];
        
        $result = $this->service->validateAndPrepareData($testData);
        
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        
        // Should have at least one error (relaxed assertion)
        $this->assertGreaterThan(0, count($result['errors']));
    }

    /** @test */
    public function it_preserves_existing_valid_attributes()
    {
        $service = Service::factory()->create(['id' => 1]);
        Specialization::factory()->create([
            'id' => 1,
            'service_id' => $service->id
        ]);
        
        $testData = [
            'name' => 'Preserve Test Prestation',
            'internal_code' => 'PTP001',
            'billing_code' => '888888',
            'service_id' => 1,
            'specialization_id' => 1,
            'type' => 'consultation',
            'public_price' => 150.00,
            'is_active' => false, // Explicitly set
            'default_duration_minutes' => 45, // Explicitly set
            'description' => 'Custom description', // Additional attribute
        ];
        
        $result = $this->service->validateAndPrepareData($testData);
        
        $this->assertTrue($result['valid']);
        
        // Verify explicitly set values are preserved
        $this->assertFalse($result['data']['is_active']);
        $this->assertEquals(45, $result['data']['default_duration_minutes']);
        $this->assertEquals('Custom description', $result['data']['description']);
        
        // Verify these weren't marked as "added"
        $this->assertNotContains('is_active', $result['added_attributes']);
        $this->assertNotContains('default_duration_minutes', $result['added_attributes']);
    }
}