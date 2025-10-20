<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Appointment\AppointmentPrestation;
use App\Models\CONFIGURATION\Prestation;
use App\Models\CONFIGURATION\Service;
use App\Models\CONFIGURATION\ModalityType;
use App\Models\CONFIGURATION\PrestationPackage;
use App\Models\B2B\PrestationPricing;
use App\Models\Specialization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PrestationManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $doctor;
    protected $patient;
    protected $service;
    protected $specialization;
    protected $modalityType;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user and authenticate
        $this->user = User::factory()->create();
        Auth::login($this->user);

        // Create related models
        $this->service = Service::factory()->create([
            'name' => 'Cardiology Service',
            'is_active' => true,
        ]);

        $this->specialization = Specialization::factory()->create([
            'name' => 'Cardiology',
            'is_active' => true,
        ]);

        $this->modalityType = ModalityType::factory()->create([
            'name' => 'ECG',
            'is_active' => true,
        ]);

        $this->doctor = Doctor::factory()->create([
            'specialization_id' => $this->specialization->id,
        ]);

        $this->patient = Patient::factory()->create();
    }

    /** @test */
    public function it_creates_prestation_with_valid_data()
    {
        $prestationData = [
            'name' => 'Electrocardiogram',
            'internal_code' => 'ECG001',
            'billing_code' => 'BILL-ECG-001',
            'description' => 'Standard 12-lead electrocardiogram',
            'service_id' => $this->service->id,
            'specialization_id' => $this->specialization->id,
            'type' => 'diagnostic',
            'public_price' => 150.00,
            'vat_rate' => 20.00,
            'consumables_cost' => 10.00,
            'is_active' => true,
            'need_an_appointment' => true,
            'default_duration_minutes' => 30,
        ];

        $prestation = Prestation::create($prestationData);

        $this->assertInstanceOf(Prestation::class, $prestation);
        $this->assertDatabaseHas('prestations', [
            'name' => 'Electrocardiogram',
            'internal_code' => 'ECG001',
            'public_price' => 150.00,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        // Try to create prestation without required name field
        Prestation::create([
            'internal_code' => 'TEST001',
        ]);
    }

    /** @test */
    public function it_calculates_price_with_vat_correctly()
    {
        $prestation = Prestation::factory()->create([
            'public_price' => 100.00,
            'consumables_cost' => 20.00,
            'vat_rate' => 20.00,
        ]);

        // Expected: (100 + 20) * 1.20 = 144.00
        $this->assertEquals(144.00, $prestation->price_with_vat);
    }

    /** @test */
    public function it_calculates_price_with_separate_consumables_vat()
    {
        $prestation = Prestation::factory()->create([
            'public_price' => 100.00,
            'consumables_cost' => 20.00,
            'vat_rate' => 20.00,
            'tva_const_prestation' => 10.00, // Different VAT for consumables
        ]);

        $priceVariant = $prestation->price_with_vat_and_consumables_variant;
        
        // Expected: 100 * 1.20 + 20 * 1.10 = 120 + 22 = 142.00
        $this->assertEquals(142.00, $priceVariant['ttc_with_consumables_vat']);
    }

    /** @test */
    public function it_formats_duration_correctly()
    {
        $prestation1 = Prestation::factory()->create(['default_duration_minutes' => 90]);
        $prestation2 = Prestation::factory()->create(['default_duration_minutes' => 30]);
        $prestation3 = Prestation::factory()->create(['default_duration_minutes' => null]);

        $this->assertEquals('1h 30min', $prestation1->formatted_duration);
        $this->assertEquals('30min', $prestation2->formatted_duration);
        $this->assertNull($prestation3->formatted_duration);
    }

    /** @test */
    public function it_returns_formatted_id_correctly()
    {
        $prestation1 = Prestation::factory()->create([
            'internal_code' => 'INT001',
            'billing_code' => 'BILL001',
        ]);

        $prestation2 = Prestation::factory()->create([
            'internal_code' => null,
            'billing_code' => 'BILL002',
        ]);

        $this->assertEquals('INT001', $prestation1->formatted_id);
        $this->assertEquals('BILL002', $prestation2->formatted_id);
    }

    /** @test */
    public function it_has_correct_relationships()
    {
        $prestation = Prestation::factory()->create([
            'service_id' => $this->service->id,
            'specialization_id' => $this->specialization->id,
            'required_modality_type_id' => $this->modalityType->id,
        ]);

        // Test service relationship
        $this->assertInstanceOf(Service::class, $prestation->service);
        $this->assertEquals($this->service->id, $prestation->service->id);

        // Test specialization relationship
        $this->assertInstanceOf(Specialization::class, $prestation->specialization);
        $this->assertEquals($this->specialization->id, $prestation->specialization->id);

        // Test modality type relationship
        $this->assertInstanceOf(ModalityType::class, $prestation->modalityType);
        $this->assertEquals($this->modalityType->id, $prestation->modalityType->id);
    }

    /** @test */
    public function it_handles_required_prestations_info_as_array()
    {
        $prestation = Prestation::factory()->create([
            'required_prestations_info' => [1, 2, 3],
        ]);

        $this->assertIsArray($prestation->required_prestations_info);
        $this->assertEquals([1, 2, 3], $prestation->required_prestations_info);
    }

    /** @test */
    public function it_handles_required_prestations_info_as_json_string()
    {
        $prestation = Prestation::factory()->create();
        $prestation->required_prestations_info = '[4,5,6]';
        $prestation->save();

        $prestation->refresh();
        $this->assertIsArray($prestation->required_prestations_info);
        $this->assertEquals([4, 5, 6], $prestation->required_prestations_info);
    }

    /** @test */
    public function it_calculates_public_price_from_pricing_when_null()
    {
        $prestation = Prestation::factory()->create(['public_price' => null]);

        // Create pricing record
        PrestationPricing::factory()->create([
            'prestation_id' => $prestation->id,
            'prix' => 200.00,
            'activate_at' => now()->subDay(),
        ]);

        $this->assertEquals(200.00, $prestation->getPublicPrice());
        $this->assertEquals(200.00, $prestation->calculated_public_price);
    }

    /** @test */
    public function it_returns_zero_when_no_price_available()
    {
        $prestation = Prestation::factory()->create(['public_price' => null]);

        $this->assertEquals(0.0, $prestation->getPublicPrice());
        $this->assertEquals(0.0, $prestation->calculated_public_price);
    }

    /** @test */
    public function it_calculates_min_versement_amount_with_vat()
    {
        $prestation = Prestation::factory()->create([
            'min_versement_amount' => 50.00,
            'vat_rate' => 20.00,
        ]);

        // Expected: 50 * 1.20 = 60.00
        $this->assertEquals(60.00, $prestation->min_versement_amount);
    }

    /** @test */
    public function it_falls_back_to_price_with_vat_for_min_versement()
    {
        $prestation = Prestation::factory()->create([
            'min_versement_amount' => null,
            'public_price' => 100.00,
            'vat_rate' => 20.00,
        ]);

        // Should fall back to price_with_vat: 100 * 1.20 = 120.00
        $this->assertEquals(120.00, $prestation->min_versement_amount);
    }

    /** @test */
    public function it_can_be_linked_to_appointments()
    {
        $prestation = Prestation::factory()->create();
        
        $appointment = Appointment::factory()->create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
        ]);

        $appointmentPrestation = AppointmentPrestation::create([
            'appointment_id' => $appointment->id,
            'prestation_id' => $prestation->id,
            'description' => 'Test prestation for appointment',
        ]);

        $this->assertDatabaseHas('appointment_prestations', [
            'appointment_id' => $appointment->id,
            'prestation_id' => $prestation->id,
        ]);

        // Test relationships
        $this->assertEquals($prestation->id, $appointmentPrestation->prestation->id);
        $this->assertEquals($appointment->id, $appointmentPrestation->appointment->id);
    }

    /** @test */
    public function it_handles_fee_distribution_correctly()
    {
        $prestation = Prestation::factory()->create([
            'primary_doctor_share' => '60.00',
            'primary_doctor_is_percentage' => true,
            'assistant_doctor_share' => '20.00',
            'assistant_doctor_is_percentage' => true,
            'technician_share' => '10.00',
            'technician_is_percentage' => true,
            'clinic_share' => '10.00',
            'clinic_is_percentage' => true,
        ]);

        $this->assertTrue($prestation->primary_doctor_is_percentage);
        $this->assertEquals('60.00', $prestation->primary_doctor_share);
        $this->assertEquals('20.00', $prestation->assistant_doctor_share);
        $this->assertEquals('10.00', $prestation->technician_share);
        $this->assertEquals('10.00', $prestation->clinic_share);
    }

    /** @test */
    public function it_handles_hospitalization_requirements()
    {
        $prestation = Prestation::factory()->create([
            'requires_hospitalization' => true,
            'default_hosp_nights' => 2,
        ]);

        $this->assertTrue($prestation->requires_hospitalization);
        $this->assertEquals(2, $prestation->default_hosp_nights);
    }

    /** @test */
    public function it_handles_social_security_reimbursement()
    {
        $prestation = Prestation::factory()->create([
            'is_social_security_reimbursable' => true,
            'reimbursement_conditions' => 'Requires prior authorization',
        ]);

        $this->assertTrue($prestation->is_social_security_reimbursable);
        $this->assertEquals('Requires prior authorization', $prestation->reimbursement_conditions);
    }

    /** @test */
    public function it_handles_discount_rules()
    {
        $discountRules = ['senior_discount', 'student_discount'];
        
        $prestation = Prestation::factory()->create([
            'non_applicable_discount_rules' => $discountRules,
        ]);

        $this->assertIsArray($prestation->non_applicable_discount_rules);
        $this->assertEquals($discountRules, $prestation->non_applicable_discount_rules);
    }

    /** @test */
    public function it_handles_required_consents()
    {
        $consents = ['informed_consent', 'privacy_consent'];
        
        $prestation = Prestation::factory()->create([
            'required_consents' => $consents,
        ]);

        $this->assertIsArray($prestation->required_consents);
        $this->assertEquals($consents, $prestation->required_consents);
    }

    /** @test */
    public function it_can_filter_active_prestations()
    {
        Prestation::factory()->create(['is_active' => true, 'name' => 'Active Prestation']);
        Prestation::factory()->create(['is_active' => false, 'name' => 'Inactive Prestation']);

        $activePrestations = Prestation::where('is_active', true)->get();
        $inactivePrestations = Prestation::where('is_active', false)->get();

        $this->assertCount(1, $activePrestations);
        $this->assertCount(1, $inactivePrestations);
        $this->assertEquals('Active Prestation', $activePrestations->first()->name);
    }

    /** @test */
    public function it_can_filter_by_service()
    {
        $service2 = Service::factory()->create(['name' => 'Radiology Service']);
        
        Prestation::factory()->create(['service_id' => $this->service->id, 'name' => 'Cardiology Test']);
        Prestation::factory()->create(['service_id' => $service2->id, 'name' => 'Radiology Test']);

        $cardiologyPrestations = Prestation::where('service_id', $this->service->id)->get();
        $radiologyPrestations = Prestation::where('service_id', $service2->id)->get();

        $this->assertCount(1, $cardiologyPrestations);
        $this->assertCount(1, $radiologyPrestations);
        $this->assertEquals('Cardiology Test', $cardiologyPrestations->first()->name);
    }

    /** @test */
    public function it_can_filter_by_specialization()
    {
        $specialization2 = Specialization::factory()->create(['name' => 'Radiology']);
        
        Prestation::factory()->create(['specialization_id' => $this->specialization->id, 'name' => 'Cardiology Prestation']);
        Prestation::factory()->create(['specialization_id' => $specialization2->id, 'name' => 'Radiology Prestation']);

        $cardiologyPrestations = Prestation::where('specialization_id', $this->specialization->id)->get();
        $radiologyPrestations = Prestation::where('specialization_id', $specialization2->id)->get();

        $this->assertCount(1, $cardiologyPrestations);
        $this->assertCount(1, $radiologyPrestations);
        $this->assertEquals('Cardiology Prestation', $cardiologyPrestations->first()->name);
    }

    /** @test */
    public function it_handles_night_tariff()
    {
        $prestation = Prestation::factory()->create([
            'public_price' => 100.00,
            'night_tariff' => 150.00,
        ]);

        $this->assertEquals(100.00, $prestation->public_price);
        $this->assertEquals(150.00, $prestation->night_tariff);
    }

    /** @test */
    public function it_handles_patient_instructions()
    {
        $instructions = 'Please fast for 12 hours before the procedure';
        
        $prestation = Prestation::factory()->create([
            'patient_instructions' => $instructions,
        ]);

        $this->assertEquals($instructions, $prestation->patient_instructions);
    }

    /** @test */
    public function it_handles_database_transaction_rollback_on_error()
    {
        $initialCount = Prestation::count();

        try {
            DB::transaction(function () {
                Prestation::factory()->create(['name' => 'Test Prestation']);
                
                // Force an error
                throw new \Exception('Simulated error');
            });
        } catch (\Exception $e) {
            // Expected exception
        }

        $this->assertEquals($initialCount, Prestation::count());
    }

    /** @test */
    public function it_can_search_by_name()
    {
        Prestation::factory()->create(['name' => 'Electrocardiogram Test']);
        Prestation::factory()->create(['name' => 'Blood Test']);
        Prestation::factory()->create(['name' => 'X-Ray Examination']);

        $results = Prestation::where('name', 'LIKE', '%Test%')->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->pluck('name')->contains('Electrocardiogram Test'));
        $this->assertTrue($results->pluck('name')->contains('Blood Test'));
    }

    /** @test */
    public function it_can_search_by_codes()
    {
        Prestation::factory()->create(['internal_code' => 'ECG001', 'billing_code' => 'BILL-ECG']);
        Prestation::factory()->create(['internal_code' => 'BLOOD001', 'billing_code' => 'BILL-BLOOD']);

        $resultsByInternal = Prestation::where('internal_code', 'ECG001')->first();
        $resultsByBilling = Prestation::where('billing_code', 'BILL-BLOOD')->first();

        $this->assertNotNull($resultsByInternal);
        $this->assertNotNull($resultsByBilling);
        $this->assertEquals('ECG001', $resultsByInternal->internal_code);
        $this->assertEquals('BILL-BLOOD', $resultsByBilling->billing_code);
    }

    /** @test */
    public function it_handles_multiple_prestations_in_appointment()
    {
        $prestation1 = Prestation::factory()->create(['name' => 'ECG']);
        $prestation2 = Prestation::factory()->create(['name' => 'Blood Test']);
        
        $appointment = Appointment::factory()->create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
        ]);

        AppointmentPrestation::create([
            'appointment_id' => $appointment->id,
            'prestation_id' => $prestation1->id,
        ]);

        AppointmentPrestation::create([
            'appointment_id' => $appointment->id,
            'prestation_id' => $prestation2->id,
        ]);

        $appointmentPrestations = AppointmentPrestation::where('appointment_id', $appointment->id)->get();

        $this->assertCount(2, $appointmentPrestations);
        $this->assertTrue($appointmentPrestations->pluck('prestation_id')->contains($prestation1->id));
        $this->assertTrue($appointmentPrestations->pluck('prestation_id')->contains($prestation2->id));
    }

    /** @test */
    public function it_validates_price_fields_are_numeric()
    {
        $prestation = Prestation::factory()->create([
            'public_price' => '150.50',
            'vat_rate' => '20.00',
            'consumables_cost' => '10.25',
        ]);

        $this->assertEquals(150.50, $prestation->public_price);
        $this->assertEquals(20.00, $prestation->vat_rate);
        $this->assertEquals(10.25, $prestation->consumables_cost);
    }

    /** @test */
    public function it_handles_convenience_price()
    {
        $prestation = Prestation::factory()->create([
            'public_price' => 100.00,
            'convenience_prix' => 120.00,
        ]);

        $this->assertEquals(100.00, $prestation->public_price);
        $this->assertEquals(120.00, $prestation->convenience_prix);
    }
}