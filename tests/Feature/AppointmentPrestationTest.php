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
use App\Models\Specialization;
use Illuminate\Support\Facades\Auth;

class AppointmentPrestationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $doctor;
    protected $patient;
    protected $service;
    protected $specialization;
    protected $prestations;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user for authentication
        $this->user = User::factory()->create([
            'role' => 'doctor',
        ]);

        // Create a service
        $this->service = Service::create([
            'name' => 'Test Service',
            'description' => 'Test service description',
            'is_active' => true,
        ]);

        // Create a specialization
        $this->specialization = Specialization::create([
            'name' => 'Test Specialization',
            'description' => 'Test specialization description',
            'service_id' => $this->service->id,
            'is_active' => true,
        ]);

        // Create a doctor
        $this->doctor = Doctor::create([
            'user_id' => $this->user->id,
            'specialization_id' => $this->specialization->id,
            'time_slot' => 30,
            'patients_based_on_time' => 1,
            'allowed_appointment_today' => true,
        ]);

        // Create a patient
        $this->patient = Patient::create([
            'Firstname' => 'John',
            'Lastname' => 'Doe',
            'phone' => '1234567890',
            'DateOfBirth' => '1990-01-01',
            'email' => 'john.doe@example.com',
        ]);

        // Create test prestations
        $this->prestations = collect();
        for ($i = 1; $i <= 3; $i++) {
            $this->prestations->push(Prestation::create([
                'name' => "Test Prestation {$i}",
                'internal_code' => "TEST00{$i}",
                'billing_code' => "BILL00{$i}",
                'description' => "Test prestation {$i} description",
                'service_id' => $this->service->id,
                'specialization_id' => $this->specialization->id,
                'type' => 'consultation',
                'public_price' => 100.00 * $i,
                'vat_rate' => 19.00,
                'consumables_cost' => 10.00,
                'default_payment_type' => 'post-pay',
                'default_duration_minutes' => 30,
                'is_active' => true,
            ]));
        }

        Auth::login($this->user);
    }

    /** @test */
    public function it_stores_appointment_with_single_prestation()
    {
        $prestationId = $this->prestations->first()->id;
        
        $appointmentData = [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->addDay()->format('Y-m-d'),
            'appointment_time' => '10:00',
            'description' => 'Test appointment with single prestation',
            'addToWaitlist' => false,
            'prestation_id' => $prestationId,
        ];

        $response = $this->postJson('/api/appointments', $appointmentData);

        $response->assertStatus(200);
        
        // Check that appointment was created
        $this->assertDatabaseHas('appointments', [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => $appointmentData['appointment_date'],
            'appointment_time' => $appointmentData['appointment_time'],
        ]);

        // Check that prestation was stored
        $appointment = Appointment::latest()->first();
        $this->assertDatabaseHas('appointment_prestations', [
            'appointment_id' => $appointment->id,
            'prestation_id' => $prestationId,
        ]);

        // Verify the relationship works
        $this->assertCount(1, $appointment->appointmentPrestations);
        $this->assertEquals($prestationId, $appointment->appointmentPrestations->first()->prestation_id);
    }

    /** @test */
    public function it_stores_appointment_with_multiple_prestations()
    {
        $prestationIds = $this->prestations->pluck('id')->toArray();
        
        $appointmentData = [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->addDay()->format('Y-m-d'),
            'appointment_time' => '11:00',
            'description' => 'Test appointment with multiple prestations',
            'addToWaitlist' => false,
            'prestations' => $prestationIds,
        ];

        $response = $this->postJson('/api/appointments', $appointmentData);

        $response->assertStatus(200);
        
        // Check that appointment was created
        $appointment = Appointment::latest()->first();
        $this->assertNotNull($appointment);

        // Check that all prestations were stored
        foreach ($prestationIds as $prestationId) {
            $this->assertDatabaseHas('appointment_prestations', [
                'appointment_id' => $appointment->id,
                'prestation_id' => $prestationId,
            ]);
        }

        // Verify the relationship works
        $this->assertCount(count($prestationIds), $appointment->appointmentPrestations);
        
        $storedPrestationIds = $appointment->appointmentPrestations->pluck('prestation_id')->toArray();
        $this->assertEquals(sort($prestationIds), sort($storedPrestationIds));
    }

    /** @test */
    public function it_stores_appointment_with_both_prestation_id_and_prestations_array()
    {
        $singlePrestationId = $this->prestations->first()->id;
        $arrayPrestationIds = $this->prestations->slice(1, 2)->pluck('id')->toArray();
        
        $appointmentData = [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->addDay()->format('Y-m-d'),
            'appointment_time' => '12:00',
            'description' => 'Test appointment with both single and array prestations',
            'addToWaitlist' => false,
            'prestation_id' => $singlePrestationId,
            'prestations' => $arrayPrestationIds,
        ];

        $response = $this->postJson('/api/appointments', $appointmentData);

        $response->assertStatus(200);
        
        // Check that appointment was created
        $appointment = Appointment::latest()->first();
        $this->assertNotNull($appointment);

        // All prestations should be stored (no duplicates)
        $expectedPrestationIds = array_unique(array_merge([$singlePrestationId], $arrayPrestationIds));
        
        foreach ($expectedPrestationIds as $prestationId) {
            $this->assertDatabaseHas('appointment_prestations', [
                'appointment_id' => $appointment->id,
                'prestation_id' => $prestationId,
            ]);
        }

        // Verify no duplicates
        $this->assertCount(count($expectedPrestationIds), $appointment->appointmentPrestations);
    }

    /** @test */
    public function it_stores_appointment_without_prestations()
    {
        $appointmentData = [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->addDay()->format('Y-m-d'),
            'appointment_time' => '13:00',
            'description' => 'Test appointment without prestations',
            'addToWaitlist' => false,
        ];

        $response = $this->postJson('/api/appointments', $appointmentData);

        $response->assertStatus(200);
        
        // Check that appointment was created
        $appointment = Appointment::latest()->first();
        $this->assertNotNull($appointment);

        // Check that no prestations were stored
        $this->assertCount(0, $appointment->appointmentPrestations);
        $this->assertDatabaseMissing('appointment_prestations', [
            'appointment_id' => $appointment->id,
        ]);
    }

    /** @test */
    public function it_filters_invalid_prestation_ids()
    {
        $validPrestationId = $this->prestations->first()->id;
        $invalidPrestationIds = [0, -1, 999999, null, ''];
        
        $appointmentData = [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->addDay()->format('Y-m-d'),
            'appointment_time' => '14:00',
            'description' => 'Test appointment with invalid prestation IDs',
            'addToWaitlist' => false,
            'prestations' => array_merge([$validPrestationId], $invalidPrestationIds),
        ];

        $response = $this->postJson('/api/appointments', $appointmentData);

        $response->assertStatus(200);
        
        // Check that appointment was created
        $appointment = Appointment::latest()->first();
        $this->assertNotNull($appointment);

        // Only valid prestation should be stored
        $this->assertCount(1, $appointment->appointmentPrestations);
        $this->assertEquals($validPrestationId, $appointment->appointmentPrestations->first()->prestation_id);
    }

    /** @test */
    public function appointment_prestation_relationships_work_correctly()
    {
        $prestationId = $this->prestations->first()->id;
        
        // Create appointment with prestation
        $appointmentData = [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->addDay()->format('Y-m-d'),
            'appointment_time' => '15:00',
            'prestations' => [$prestationId],
        ];

        $this->postJson('/api/appointments', $appointmentData);
        
        $appointment = Appointment::latest()->first();
        $appointmentPrestation = AppointmentPrestation::where('appointment_id', $appointment->id)->first();

        // Test relationships
        $this->assertNotNull($appointmentPrestation->appointment);
        $this->assertEquals($appointment->id, $appointmentPrestation->appointment->id);

        $this->assertNotNull($appointmentPrestation->prestation);
        $this->assertEquals($prestationId, $appointmentPrestation->prestation->id);
        $this->assertEquals($this->prestations->first()->name, $appointmentPrestation->prestation->name);
    }
}