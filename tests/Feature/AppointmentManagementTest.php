<?php

namespace Tests\Feature;

use App\AppointmentSatatusEnum;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Schedule;
use App\Models\Specialization;
use App\Models\User;
use App\Models\WaitList;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AppointmentManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Doctor $doctor;
    protected Patient $patient;
    protected Specialization $specialization;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user for authentication
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        
        // Create specialization, doctor, and patient for testing
        $this->specialization = Specialization::factory()->create();
        $this->doctor = Doctor::factory()->create([
            'specialization_id' => $this->specialization->id,
            'user_id' => $this->user->id,
            'frequency' => 'Daily',
            'allowed_appointment_today' => true,
            'patients_based_on_time' => false,
            'time_slot' => 30,
        ]);
        $this->patient = Patient::factory()->create();
        
        // Create a schedule for the doctor
        Schedule::factory()->create([
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 'monday',
            'shift_period' => 'morning',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'number_of_patients_per_day' => 10,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_can_create_appointment_with_valid_data()
    {
        $appointmentData = [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => Carbon::now()->addDays(1)->format('Y-m-d'),
            'appointment_time' => '09:00',
            'description' => 'Regular checkup',
        ];

        $response = $this->postJson('/api/appointments', $appointmentData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('appointments', [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => $appointmentData['appointment_date'],
            'appointment_time' => $appointmentData['appointment_time'],
            'notes' => $appointmentData['description'],
            'status' => AppointmentSatatusEnum::SCHEDULED->value,
            'created_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_validates_required_fields_for_appointment_creation()
    {
        $response = $this->postJson('/api/appointments', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'patient_id',
            'doctor_id',
            'appointment_date',
            'appointment_time'
        ]);
    }

    /** @test */
    public function it_validates_patient_exists()
    {
        $appointmentData = [
            'patient_id' => 99999, // Non-existent patient
            'doctor_id' => $this->doctor->id,
            'appointment_date' => Carbon::now()->addDays(1)->format('Y-m-d'),
            'appointment_time' => '09:00',
        ];

        $response = $this->postJson('/api/appointments', $appointmentData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['patient_id']);
    }

    /** @test */
    public function it_validates_doctor_exists()
    {
        $appointmentData = [
            'patient_id' => $this->patient->id,
            'doctor_id' => 99999, // Non-existent doctor
            'appointment_date' => Carbon::now()->addDays(1)->format('Y-m-d'),
            'appointment_time' => '09:00',
        ];

        $response = $this->postJson('/api/appointments', $appointmentData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['doctor_id']);
    }

    /** @test */
    public function it_validates_appointment_date_format()
    {
        $invalidDates = ['invalid-date', '2024/12/31', '31-12-2024', ''];
        
        foreach ($invalidDates as $invalidDate) {
            $response = $this->postJson('/api/appointments', [
                'patient_id' => $this->patient->id,
                'doctor_id' => $this->doctor->id,
                'appointment_date' => $invalidDate,
                'appointment_time' => '09:00',
            ]);

            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['appointment_date']);
        }
    }

    /** @test */
    public function it_validates_appointment_time_format()
    {
        $invalidTimes = ['25:00', '12:60', 'invalid', '9am', ''];
        
        foreach ($invalidTimes as $invalidTime) {
            $response = $this->postJson('/api/appointments', [
                'patient_id' => $this->patient->id,
                'doctor_id' => $this->doctor->id,
                'appointment_date' => Carbon::now()->addDays(1)->format('Y-m-d'),
                'appointment_time' => $invalidTime,
            ]);

            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['appointment_time']);
        }
    }

    /** @test */
    public function it_prevents_booking_past_dates()
    {
        $pastDate = Carbon::now()->subDays(1)->format('Y-m-d');
        
        $response = $this->postJson('/api/appointments', [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => $pastDate,
            'appointment_time' => '09:00',
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('past', $response->json('message'));
    }

    /** @test */
    public function it_prevents_double_booking_same_slot()
    {
        $appointmentDate = Carbon::now()->addDays(1)->format('Y-m-d');
        $appointmentTime = '09:00';

        // Create first appointment
        Appointment::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => $appointmentDate,
            'appointment_time' => $appointmentTime,
            'status' => AppointmentSatatusEnum::SCHEDULED->value,
            'created_by' => $this->user->id,
        ]);

        // Try to create second appointment for same slot
        $response = $this->postJson('/api/appointments', [
            'patient_id' => Patient::factory()->create()->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => $appointmentDate,
            'appointment_time' => $appointmentTime,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['appointment_time']);
        $this->assertStringContainsString('already booked', $response->json('message'));
    }

    /** @test */
    public function it_allows_booking_canceled_appointment_slot()
    {
        $appointmentDate = Carbon::now()->addDays(1)->format('Y-m-d');
        $appointmentTime = '09:00';

        // Create canceled appointment
        Appointment::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => $appointmentDate,
            'appointment_time' => $appointmentTime,
            'status' => AppointmentSatatusEnum::CANCELED->value,
            'created_by' => $this->user->id,
        ]);

        // Try to book the same slot (should be allowed)
        $response = $this->postJson('/api/appointments', [
            'patient_id' => Patient::factory()->create()->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => $appointmentDate,
            'appointment_time' => $appointmentTime,
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function it_can_book_same_day_appointment_when_allowed()
    {
        $today = Carbon::now()->format('Y-m-d');
        $futureTime = Carbon::now()->addHours(2)->format('H:i');

        $response = $this->postJson('/api/appointments/book-same-day', [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_time' => $today . ' ' . $futureTime,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('appointments', [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => $today,
            'appointment_time' => $futureTime,
        ]);
    }

    /** @test */
    public function it_prevents_same_day_appointment_when_not_allowed()
    {
        // Update doctor to not allow same-day appointments
        $this->doctor->update(['allowed_appointment_today' => false]);

        $today = Carbon::now()->format('Y-m-d');
        $futureTime = Carbon::now()->addHours(2)->format('H:i');

        $response = $this->postJson('/api/appointments/book-same-day', [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_time' => $today . ' ' . $futureTime,
        ]);

        $response->assertStatus(400);
        $this->assertStringContainsString('does not allow same-day', $response->json('message'));
    }

    /** @test */
    public function it_can_update_appointment()
    {
        $appointment = Appointment::factory()->create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => Carbon::now()->addDays(1)->format('Y-m-d'),
            'appointment_time' => '09:00',
            'status' => AppointmentSatatusEnum::SCHEDULED->value,
        ]);

        $updateData = [
            'appointment_date' => Carbon::now()->addDays(2)->format('Y-m-d'),
            'appointment_time' => '10:00',
            'description' => 'Updated appointment',
        ];

        $response = $this->putJson("/api/appointments/{$appointment->id}", $updateData);

        $response->assertStatus(200);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'appointment_date' => $updateData['appointment_date'],
            'appointment_time' => $updateData['appointment_time'],
            'notes' => $updateData['description'],
            'updated_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_prevents_updating_to_conflicting_slot()
    {
        $conflictDate = Carbon::now()->addDays(1)->format('Y-m-d');
        $conflictTime = '10:00';

        // Create existing appointment
        Appointment::factory()->create([
            'doctor_id' => $this->doctor->id,
            'appointment_date' => $conflictDate,
            'appointment_time' => $conflictTime,
            'status' => AppointmentSatatusEnum::SCHEDULED->value,
        ]);

        // Create appointment to update
        $appointment = Appointment::factory()->create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => Carbon::now()->addDays(2)->format('Y-m-d'),
            'appointment_time' => '09:00',
        ]);

        // Try to update to conflicting slot
        $response = $this->putJson("/api/appointments/{$appointment->id}", [
            'appointment_date' => $conflictDate,
            'appointment_time' => $conflictTime,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['appointment_time']);
    }

    /** @test */
    public function it_can_cancel_appointment()
    {
        $appointment = Appointment::factory()->create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'status' => AppointmentSatatusEnum::SCHEDULED->value,
        ]);

        $response = $this->patchJson("/api/appointments/{$appointment->id}/cancel");

        $response->assertStatus(200);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => AppointmentSatatusEnum::CANCELED->value,
            'canceled_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_can_mark_appointment_as_completed()
    {
        $appointment = Appointment::factory()->create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'status' => AppointmentSatatusEnum::SCHEDULED->value,
        ]);

        $response = $this->patchJson("/api/appointments/{$appointment->id}/complete");

        $response->assertStatus(200);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => AppointmentSatatusEnum::DONE->value,
        ]);
    }

    /** @test */
    public function it_can_retrieve_appointments_by_doctor()
    {
        // Create appointments for the doctor
        Appointment::factory()->count(3)->create([
            'doctor_id' => $this->doctor->id,
            'status' => AppointmentSatatusEnum::SCHEDULED->value,
        ]);

        // Create appointments for another doctor
        $anotherDoctor = Doctor::factory()->create([
            'specialization_id' => $this->specialization->id,
        ]);
        Appointment::factory()->count(2)->create([
            'doctor_id' => $anotherDoctor->id,
            'status' => AppointmentSatatusEnum::SCHEDULED->value,
        ]);

        $response = $this->getJson("/api/appointments?doctor_id={$this->doctor->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'appointments');
        
        // Verify all returned appointments belong to the correct doctor
        $appointments = $response->json('appointments');
        foreach ($appointments as $appointment) {
            $this->assertEquals($this->doctor->id, $appointment['doctor_id']);
        }
    }

    /** @test */
    public function it_can_retrieve_appointments_by_patient()
    {
        // Create appointments for the patient
        Appointment::factory()->count(2)->create([
            'patient_id' => $this->patient->id,
            'status' => AppointmentSatatusEnum::SCHEDULED->value,
        ]);

        // Create appointments for another patient
        $anotherPatient = Patient::factory()->create();
        Appointment::factory()->count(3)->create([
            'patient_id' => $anotherPatient->id,
            'status' => AppointmentSatatusEnum::SCHEDULED->value,
        ]);

        $response = $this->getJson("/api/appointments?patient_id={$this->patient->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'appointments');
        
        // Verify all returned appointments belong to the correct patient
        $appointments = $response->json('appointments');
        foreach ($appointments as $appointment) {
            $this->assertEquals($this->patient->id, $appointment['patient_id']);
        }
    }

    /** @test */
    public function it_can_filter_appointments_by_date()
    {
        $targetDate = Carbon::now()->addDays(1)->format('Y-m-d');
        
        // Create appointments for target date
        Appointment::factory()->count(2)->create([
            'doctor_id' => $this->doctor->id,
            'appointment_date' => $targetDate,
            'status' => AppointmentSatatusEnum::SCHEDULED->value,
        ]);

        // Create appointments for different date
        Appointment::factory()->count(3)->create([
            'doctor_id' => $this->doctor->id,
            'appointment_date' => Carbon::now()->addDays(2)->format('Y-m-d'),
            'status' => AppointmentSatatusEnum::SCHEDULED->value,
        ]);

        $response = $this->getJson("/api/appointments?date={$targetDate}");

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'appointments');
        
        // Verify all returned appointments are for the target date
        $appointments = $response->json('appointments');
        foreach ($appointments as $appointment) {
            $this->assertEquals($targetDate, $appointment['appointment_date']);
        }
    }

    /** @test */
    public function it_can_filter_appointments_by_status()
    {
        // Create scheduled appointments
        Appointment::factory()->count(2)->create([
            'doctor_id' => $this->doctor->id,
            'status' => AppointmentSatatusEnum::SCHEDULED->value,
        ]);

        // Create completed appointments
        Appointment::factory()->count(3)->create([
            'doctor_id' => $this->doctor->id,
            'status' => AppointmentSatatusEnum::DONE->value,
        ]);

        $response = $this->getJson("/api/appointments?status=" . AppointmentSatatusEnum::SCHEDULED->value);

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'appointments');
        
        // Verify all returned appointments have the correct status
        $appointments = $response->json('appointments');
        foreach ($appointments as $appointment) {
            $this->assertEquals(AppointmentSatatusEnum::SCHEDULED->value, $appointment['status']);
        }
    }

    /** @test */
    public function it_can_check_slot_availability()
    {
        $doctorId = $this->doctor->id;
        $date = Carbon::now()->addDays(1)->format('Y-m-d');
        $time = '09:00';

        // Check availability for empty slot
        $isAvailable = Appointment::isSlotAvailable($doctorId, $date, $time, [AppointmentSatatusEnum::CANCELED->value]);
        $this->assertTrue($isAvailable);

        // Create appointment
        Appointment::create([
            'doctor_id' => $doctorId,
            'appointment_date' => $date,
            'appointment_time' => $time,
            'patient_id' => $this->patient->id,
            'status' => AppointmentSatatusEnum::SCHEDULED->value,
            'created_by' => $this->user->id,
        ]);

        // Check availability for booked slot
        $isAvailable = Appointment::isSlotAvailable($doctorId, $date, $time, [AppointmentSatatusEnum::CANCELED->value]);
        $this->assertFalse($isAvailable);
    }

    /** @test */
    public function it_can_check_slot_availability_for_update()
    {
        $doctorId = $this->doctor->id;
        $date = Carbon::now()->addDays(1)->format('Y-m-d');
        $time = '09:00';

        // Create appointment
        $appointment = Appointment::create([
            'doctor_id' => $doctorId,
            'appointment_date' => $date,
            'appointment_time' => $time,
            'patient_id' => $this->patient->id,
            'status' => AppointmentSatatusEnum::SCHEDULED->value,
            'created_by' => $this->user->id,
        ]);

        // Check availability for update (should be available for same appointment)
        $isAvailable = Appointment::isSlotAvailableForUpdate(
            $doctorId, 
            $date, 
            $time, 
            [AppointmentSatatusEnum::CANCELED->value], 
            $appointment->id
        );
        $this->assertTrue($isAvailable);

        // Create another appointment
        $anotherAppointment = Appointment::create([
            'doctor_id' => $doctorId,
            'appointment_date' => $date,
            'appointment_time' => '10:00',
            'patient_id' => Patient::factory()->create()->id,
            'status' => AppointmentSatatusEnum::SCHEDULED->value,
            'created_by' => $this->user->id,
        ]);

        // Check availability for update to conflicting slot
        $isAvailable = Appointment::isSlotAvailableForUpdate(
            $doctorId, 
            $date, 
            '10:00', 
            [AppointmentSatatusEnum::CANCELED->value], 
            $appointment->id
        );
        $this->assertFalse($isAvailable);
    }

    /** @test */
    public function it_can_add_patient_to_waitlist()
    {
        $appointmentData = [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => Carbon::now()->addDays(1)->format('Y-m-d'),
            'appointment_time' => '09:00',
            'addToWaitlist' => true,
            'importance' => 5,
            'description' => 'Urgent consultation needed',
        ];

        $response = $this->postJson('/api/appointments', $appointmentData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('waitlists', [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'importance' => 5,
        ]);
    }

    /** @test */
    public function it_validates_importance_when_adding_to_waitlist()
    {
        $response = $this->postJson('/api/appointments', [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => Carbon::now()->addDays(1)->format('Y-m-d'),
            'appointment_time' => '09:00',
            'addToWaitlist' => true,
            // Missing importance field
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['importance']);
    }

    /** @test */
    public function it_can_create_next_appointment_after_completing_current()
    {
        // Create current appointment
        $currentAppointment = Appointment::factory()->create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'status' => AppointmentSatatusEnum::SCHEDULED->value,
        ]);

        $nextAppointmentData = [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'appointment_time' => '10:00',
            'description' => 'Follow-up appointment',
        ];

        $response = $this->postJson("/api/appointments/{$currentAppointment->id}/next", $nextAppointmentData);

        $response->assertStatus(201);
        
        // Verify current appointment is marked as done
        $this->assertDatabaseHas('appointments', [
            'id' => $currentAppointment->id,
            'status' => AppointmentSatatusEnum::DONE->value,
        ]);
        
        // Verify new appointment is created
        $this->assertDatabaseHas('appointments', [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => $nextAppointmentData['appointment_date'],
            'appointment_time' => $nextAppointmentData['appointment_time'],
            'status' => AppointmentSatatusEnum::SCHEDULED->value,
        ]);
    }

    /** @test */
    public function it_can_get_canceled_appointments_for_rebooking()
    {
        // Create canceled appointments
        Appointment::factory()->count(2)->create([
            'doctor_id' => $this->doctor->id,
            'appointment_date' => Carbon::now()->addDays(1)->format('Y-m-d'),
            'status' => AppointmentSatatusEnum::CANCELED->value,
        ]);

        // Create scheduled appointments (should not be included)
        Appointment::factory()->count(3)->create([
            'doctor_id' => $this->doctor->id,
            'appointment_date' => Carbon::now()->addDays(1)->format('Y-m-d'),
            'status' => AppointmentSatatusEnum::SCHEDULED->value,
        ]);

        $response = $this->getJson("/api/appointments/canceled?doctor_id={$this->doctor->id}");

        $response->assertStatus(200);
        $this->assertArrayHasKey('available_canceled_appointments', $response->json());
        
        $canceledSlots = $response->json('available_canceled_appointments');
        $this->assertNotEmpty($canceledSlots);
    }

    /** @test */
    public function it_handles_appointment_relationships_correctly()
    {
        $appointment = Appointment::factory()->create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'created_by' => $this->user->id,
        ]);

        // Test doctor relationship
        $this->assertInstanceOf(Doctor::class, $appointment->doctor);
        $this->assertEquals($this->doctor->id, $appointment->doctor->id);

        // Test patient relationship
        $this->assertInstanceOf(Patient::class, $appointment->patient);
        $this->assertEquals($this->patient->id, $appointment->patient->id);

        // Test created by user relationship
        $this->assertInstanceOf(User::class, $appointment->createdByUser);
        $this->assertEquals($this->user->id, $appointment->createdByUser->id);
    }

    /** @test */
    public function it_handles_database_transaction_rollback_on_error()
    {
        $this->expectException(\Exception::class);

        DB::transaction(function () {
            Appointment::create([
                'patient_id' => $this->patient->id,
                'doctor_id' => $this->doctor->id,
                'appointment_date' => Carbon::now()->addDays(1)->format('Y-m-d'),
                'appointment_time' => '09:00',
                'status' => AppointmentSatatusEnum::SCHEDULED->value,
                'created_by' => $this->user->id,
            ]);

            // Simulate an error
            throw new \Exception('Simulated error');
        });

        // Verify no appointment was created due to rollback
        $this->assertDatabaseCount('appointments', 0);
    }

    /** @test */
    public function it_can_search_appointments_by_patient_name()
    {
        // Create appointments for the patient
        Appointment::factory()->count(2)->create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
        ]);

        $response = $this->getJson("/api/appointments/search?patient_name={$this->patient->Firstname}");

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'appointments');
    }

    /** @test */
    public function it_can_filter_future_appointments()
    {
        // Create past appointment
        Appointment::factory()->create([
            'doctor_id' => $this->doctor->id,
            'appointment_date' => Carbon::now()->subDays(1)->format('Y-m-d'),
        ]);

        // Create future appointments
        Appointment::factory()->count(2)->create([
            'doctor_id' => $this->doctor->id,
            'appointment_date' => Carbon::now()->addDays(1)->format('Y-m-d'),
        ]);

        $response = $this->getJson("/api/appointments?filter=future");

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'appointments');
    }

    /** @test */
    public function it_can_filter_todays_appointments()
    {
        $today = Carbon::now()->format('Y-m-d');
        
        // Create today's appointments
        Appointment::factory()->count(2)->create([
            'doctor_id' => $this->doctor->id,
            'appointment_date' => $today,
            'status' => AppointmentSatatusEnum::SCHEDULED->value,
        ]);

        // Create future appointments
        Appointment::factory()->count(3)->create([
            'doctor_id' => $this->doctor->id,
            'appointment_date' => Carbon::now()->addDays(1)->format('Y-m-d'),
        ]);

        $response = $this->getJson("/api/appointments?filter=today");

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'appointments');
    }

    /** @test */
    public function it_validates_description_length()
    {
        $longDescription = str_repeat('a', 1001); // Exceeds 1000 character limit
        
        $response = $this->postJson('/api/appointments', [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => Carbon::now()->addDays(1)->format('Y-m-d'),
            'appointment_time' => '09:00',
            'description' => $longDescription,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['description']);
    }

    /** @test */
    public function it_can_bulk_cancel_appointments()
    {
        // Create multiple appointments
        $appointments = Appointment::factory()->count(3)->create([
            'doctor_id' => $this->doctor->id,
            'status' => AppointmentSatatusEnum::SCHEDULED->value,
        ]);

        $appointmentIds = $appointments->pluck('id')->toArray();

        $response = $this->patchJson('/api/appointments/bulk-cancel', [
            'appointment_ids' => $appointmentIds,
            'reason' => 'Doctor unavailable',
        ]);

        $response->assertStatus(200);
        
        foreach ($appointmentIds as $appointmentId) {
            $this->assertDatabaseHas('appointments', [
                'id' => $appointmentId,
                'status' => AppointmentSatatusEnum::CANCELED->value,
                'canceled_by' => $this->user->id,
            ]);
        }
    }

    /** @test */
    public function it_tracks_appointment_audit_trail()
    {
        $appointment = Appointment::factory()->create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'created_by' => $this->user->id,
        ]);

        // Update appointment
        $appointment->update([
            'appointment_time' => '10:00',
            'updated_by' => $this->user->id,
        ]);

        // Cancel appointment
        $appointment->update([
            'status' => AppointmentSatatusEnum::CANCELED->value,
            'canceled_by' => $this->user->id,
        ]);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
            'canceled_by' => $this->user->id,
        ]);
    }
}