<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Appointment\AppointmentPrestation;
use App\Models\CONFIGURATION\Prestation;
use App\Models\CONFIGURATION\Service;
use App\Models\Specialization;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class CreateAppointmentWithPrestationsTest extends TestCase
{
    /**
     * This test assumes your local/test database already has the tables created
     * (we do NOT run migrations here to avoid the project's large migration suite).
     */
    public function test_create_appointment_with_prestations()
    {
        // Ensure required tables exist in the testing DB; skip otherwise
        $required = ['users', 'doctors', 'patients', 'appointments', 'appointment_prestations', 'prestations'];
        foreach ($required as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Required table '{$table}' does not exist in testing database. Skipping integration test.");
                return;
            }
        }
        // Create minimal required records directly
        $user = User::create([
            'name' => 'Test Doctor User',
            'email' => 'testdoctor@example.com',
            'password' => Hash::make('password'),
            'role' => 'doctor',
            // Provide common non-nullable defaults used by this app's users table
            'phone' => '0555000000',
            'clinic_id' => null,
        ]);

        $service = Service::first() ?? Service::create([
            'name' => 'Test Service',
            'description' => 'Service for tests',
            'is_active' => 1,
        ]);

        $specialization = Specialization::first() ?? Specialization::create([
            'name' => 'Test Specialization',
            'description' => 'Spec for tests',
            'service_id' => $service->id,
            'is_active' => 1,
        ]);

        $doctor = Doctor::create([
            'user_id' => $user->id,
            'specialization_id' => $specialization->id,
            'time_slot' => 30,
            'patients_based_on_time' => 1,
            'allowed_appointment_today' => true,
        ]);

        $patient = Patient::create([
            'Firstname' => 'Jane',
            'Lastname' => 'Doe',
            'phone' => '0555123456',
            'DateOfBirth' => '1990-01-01',
            'email' => 'jane.doe@example.com',
        ]);

        // Create a few prestations
        $prestation1 = Prestation::create([
            'name' => 'Prest Test 1',
            'internal_code' => 'PT01',
            'billing_code' => 'B01',
            'description' => 'P1',
            'service_id' => $service->id,
            'specialization_id' => $specialization->id,
            'type' => 'test',
            'public_price' => 100.00,
            'vat_rate' => 19.00,
            'consumables_cost' => 5.00,
            'default_payment_type' => 'post-pay',
            'default_duration_minutes' => 30,
            'is_active' => 1,
        ]);

        $prestation2 = Prestation::create([
            'name' => 'Prest Test 2',
            'internal_code' => 'PT02',
            'billing_code' => 'B02',
            'description' => 'P2',
            'service_id' => $service->id,
            'specialization_id' => $specialization->id,
            'type' => 'test',
            'public_price' => 150.00,
            'vat_rate' => 19.00,
            'consumables_cost' => 8.00,
            'default_payment_type' => 'post-pay',
            'default_duration_minutes' => 30,
            'is_active' => 1,
        ]);

        // Authenticate as the created user
        $this->actingAs($user);

        $payload = [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => now()->addDay()->format('Y-m-d'),
            'appointment_time' => '10:30',
            'description' => 'Appointment with prestations from test',
            'addToWaitlist' => false,
            'prestations' => [$prestation1->id, $prestation2->id],
        ];

        $response = $this->postJson('/api/appointments', $payload);

        // Expect HTTP 200 or 201 depending on controller; accept 200+ success range
        $status = $response->getStatusCode();
        $this->assertTrue(in_array($status, [200, 201]), "Unexpected status: $status; response: {$response->getContent()}");

        // Check appointment exists
        $appointment = Appointment::where('patient_id', $patient->id)
            ->where('doctor_id', $doctor->id)
            ->where('appointment_date', $payload['appointment_date'])
            ->where('appointment_time', $payload['appointment_time'])
            ->first();

        $this->assertNotNull($appointment, 'Appointment not created');

        // Verify prestations stored
        $this->assertDatabaseHas('appointment_prestations', [
            'appointment_id' => $appointment->id,
            'prestation_id' => $prestation1->id,
        ]);

        $this->assertDatabaseHas('appointment_prestations', [
            'appointment_id' => $appointment->id,
            'prestation_id' => $prestation2->id,
        ]);
    }
}
