<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\Schedule;
use App\Models\Specialization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ScheduleManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Doctor $doctor;
    protected Specialization $specialization;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user for authentication
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        
        // Create specialization and doctor for testing
        $this->specialization = Specialization::factory()->create();
        $this->doctor = Doctor::factory()->create([
            'specialization_id' => $this->specialization->id,
            'user_id' => $this->user->id,
            'frequency' => 'Daily',
            'patients_based_on_time' => false,
            'time_slot' => 30,
        ]);
    }

    /** @test */
    public function it_can_create_a_schedule_with_valid_data()
    {
        $scheduleData = [
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 'monday',
            'shift_period' => 'morning',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'number_of_patients_per_day' => 10,
            'is_active' => true,
        ];

        $schedule = Schedule::create($scheduleData);

        $this->assertDatabaseHas('schedules', $scheduleData);
        $this->assertEquals($this->doctor->id, $schedule->doctor_id);
        $this->assertEquals('monday', $schedule->day_of_week);
        $this->assertEquals('morning', $schedule->shift_period);
    }

    /** @test */
    public function it_validates_required_fields_for_schedule_creation()
    {
        $response = $this->postJson('/api/schedules', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'doctor_id',
            'day_of_week',
            'shift_period',
            'start_time',
            'end_time',
            'number_of_patients_per_day'
        ]);
    }

    /** @test */
    public function it_validates_day_of_week_enum_values()
    {
        $invalidDays = ['invalid_day', 'weekday', ''];
        
        foreach ($invalidDays as $invalidDay) {
            $response = $this->postJson('/api/schedules', [
                'doctor_id' => $this->doctor->id,
                'day_of_week' => $invalidDay,
                'shift_period' => 'morning',
                'start_time' => '08:00',
                'end_time' => '12:00',
                'number_of_patients_per_day' => 10,
            ]);

            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['day_of_week']);
        }
    }

    /** @test */
    public function it_validates_shift_period_enum_values()
    {
        $invalidShifts = ['invalid_shift', 'night', ''];
        
        foreach ($invalidShifts as $invalidShift) {
            $response = $this->postJson('/api/schedules', [
                'doctor_id' => $this->doctor->id,
                'day_of_week' => 'monday',
                'shift_period' => $invalidShift,
                'start_time' => '08:00',
                'end_time' => '12:00',
                'number_of_patients_per_day' => 10,
            ]);

            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['shift_period']);
        }
    }

    /** @test */
    public function it_validates_time_format()
    {
        $invalidTimes = ['25:00', '12:60', 'invalid', ''];
        
        foreach ($invalidTimes as $invalidTime) {
            $response = $this->postJson('/api/schedules', [
                'doctor_id' => $this->doctor->id,
                'day_of_week' => 'monday',
                'shift_period' => 'morning',
                'start_time' => $invalidTime,
                'end_time' => '12:00',
                'number_of_patients_per_day' => 10,
            ]);

            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['start_time']);
        }
    }

    /** @test */
    public function it_validates_end_time_is_after_start_time()
    {
        $response = $this->postJson('/api/schedules', [
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 'monday',
            'shift_period' => 'morning',
            'start_time' => '12:00',
            'end_time' => '08:00', // End time before start time
            'number_of_patients_per_day' => 10,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['end_time']);
    }

    /** @test */
    public function it_validates_positive_number_of_patients()
    {
        $invalidPatientCounts = [0, -1, -10];
        
        foreach ($invalidPatientCounts as $invalidCount) {
            $response = $this->postJson('/api/schedules', [
                'doctor_id' => $this->doctor->id,
                'day_of_week' => 'monday',
                'shift_period' => 'morning',
                'start_time' => '08:00',
                'end_time' => '12:00',
                'number_of_patients_per_day' => $invalidCount,
            ]);

            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['number_of_patients_per_day']);
        }
    }

    /** @test */
    public function it_validates_doctor_exists()
    {
        $response = $this->postJson('/api/schedules', [
            'doctor_id' => 99999, // Non-existent doctor
            'day_of_week' => 'monday',
            'shift_period' => 'morning',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'number_of_patients_per_day' => 10,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['doctor_id']);
    }

    /** @test */
    public function it_can_create_multiple_schedules_for_different_days()
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        
        foreach ($days as $day) {
            Schedule::create([
                'doctor_id' => $this->doctor->id,
                'day_of_week' => $day,
                'shift_period' => 'morning',
                'start_time' => '08:00',
                'end_time' => '12:00',
                'number_of_patients_per_day' => 10,
                'is_active' => true,
            ]);
        }

        $this->assertDatabaseCount('schedules', 5);
        
        foreach ($days as $day) {
            $this->assertDatabaseHas('schedules', [
                'doctor_id' => $this->doctor->id,
                'day_of_week' => $day,
            ]);
        }
    }

    /** @test */
    public function it_can_create_multiple_shifts_for_same_day()
    {
        $shifts = ['morning', 'afternoon'];
        
        foreach ($shifts as $shift) {
            Schedule::create([
                'doctor_id' => $this->doctor->id,
                'day_of_week' => 'monday',
                'shift_period' => $shift,
                'start_time' => $shift === 'morning' ? '08:00' : '14:00',
                'end_time' => $shift === 'morning' ? '12:00' : '18:00',
                'number_of_patients_per_day' => 10,
                'is_active' => true,
            ]);
        }

        $this->assertDatabaseCount('schedules', 2);
        $this->assertDatabaseHas('schedules', [
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 'monday',
            'shift_period' => 'morning',
        ]);
        $this->assertDatabaseHas('schedules', [
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 'monday',
            'shift_period' => 'afternoon',
        ]);
    }

    /** @test */
    public function it_can_create_schedule_with_specific_date()
    {
        $specificDate = Carbon::now()->addDays(7)->format('Y-m-d');
        
        $schedule = Schedule::create([
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 'monday',
            'date' => $specificDate,
            'shift_period' => 'morning',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'number_of_patients_per_day' => 15,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('schedules', [
            'doctor_id' => $this->doctor->id,
            'date' => $specificDate,
        ]);
        $this->assertEquals($specificDate, $schedule->date);
    }

    /** @test */
    public function it_can_retrieve_schedules_for_doctor()
    {
        // Create multiple schedules for the doctor
        Schedule::factory()->count(3)->create([
            'doctor_id' => $this->doctor->id,
            'is_active' => true,
        ]);

        // Create schedules for another doctor
        $anotherDoctor = Doctor::factory()->create([
            'specialization_id' => $this->specialization->id,
        ]);
        Schedule::factory()->count(2)->create([
            'doctor_id' => $anotherDoctor->id,
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/schedules?doctor_id={$this->doctor->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'schedules');
        
        // Verify all returned schedules belong to the correct doctor
        $schedules = $response->json('schedules');
        foreach ($schedules as $schedule) {
            $this->assertEquals($this->doctor->id, $schedule['doctor_id']);
        }
    }

    /** @test */
    public function it_can_update_schedule()
    {
        $schedule = Schedule::factory()->create([
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 'monday',
            'shift_period' => 'morning',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'number_of_patients_per_day' => 10,
        ]);

        $updateData = [
            'start_time' => '09:00',
            'end_time' => '13:00',
            'number_of_patients_per_day' => 15,
        ];

        $response = $this->putJson("/api/schedules/{$schedule->id}", $updateData);

        $response->assertStatus(200);
        $this->assertDatabaseHas('schedules', [
            'id' => $schedule->id,
            'start_time' => '09:00',
            'end_time' => '13:00',
            'number_of_patients_per_day' => 15,
        ]);
    }

    /** @test */
    public function it_can_delete_schedule()
    {
        $schedule = Schedule::factory()->create([
            'doctor_id' => $this->doctor->id,
        ]);

        $response = $this->deleteJson("/api/schedules/{$schedule->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('schedules', [
            'id' => $schedule->id,
        ]);
    }

    /** @test */
    public function it_can_deactivate_schedule_instead_of_deleting()
    {
        $schedule = Schedule::factory()->create([
            'doctor_id' => $this->doctor->id,
            'is_active' => true,
        ]);

        $response = $this->patchJson("/api/schedules/{$schedule->id}/deactivate");

        $response->assertStatus(200);
        $this->assertDatabaseHas('schedules', [
            'id' => $schedule->id,
            'is_active' => false,
        ]);
    }

    /** @test */
    public function it_prevents_overlapping_schedules_for_same_doctor_and_day()
    {
        // Create first schedule
        Schedule::create([
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 'monday',
            'shift_period' => 'morning',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'number_of_patients_per_day' => 10,
            'is_active' => true,
        ]);

        // Try to create overlapping schedule
        $response = $this->postJson('/api/schedules', [
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 'monday',
            'shift_period' => 'morning', // Same shift period
            'start_time' => '10:00', // Overlaps with existing schedule
            'end_time' => '14:00',
            'number_of_patients_per_day' => 8,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['shift_period']);
    }

    /** @test */
    public function it_allows_different_shift_periods_on_same_day()
    {
        // Create morning schedule
        Schedule::create([
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 'monday',
            'shift_period' => 'morning',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'number_of_patients_per_day' => 10,
            'is_active' => true,
        ]);

        // Create afternoon schedule (should be allowed)
        $response = $this->postJson('/api/schedules', [
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 'monday',
            'shift_period' => 'afternoon',
            'start_time' => '14:00',
            'end_time' => '18:00',
            'number_of_patients_per_day' => 8,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseCount('schedules', 2);
    }

    /** @test */
    public function it_can_get_schedules_for_doctor_by_day()
    {
        // Create schedules for different days
        Schedule::create([
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 'monday',
            'shift_period' => 'morning',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'number_of_patients_per_day' => 10,
            'is_active' => true,
        ]);

        Schedule::create([
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 'tuesday',
            'shift_period' => 'morning',
            'start_time' => '09:00',
            'end_time' => '13:00',
            'number_of_patients_per_day' => 12,
            'is_active' => true,
        ]);

        $schedules = Schedule::getSchedulesForDoctor($this->doctor->id, 'monday');

        $this->assertCount(1, $schedules);
        $this->assertEquals('08:00', $schedules->first()->start_time);
        $this->assertEquals('12:00', $schedules->first()->end_time);
        $this->assertEquals(10, $schedules->first()->number_of_patients_per_day);
    }

    /** @test */
    public function it_only_returns_active_schedules()
    {
        // Create active schedule
        Schedule::create([
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 'monday',
            'shift_period' => 'morning',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'number_of_patients_per_day' => 10,
            'is_active' => true,
        ]);

        // Create inactive schedule
        Schedule::create([
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 'monday',
            'shift_period' => 'afternoon',
            'start_time' => '14:00',
            'end_time' => '18:00',
            'number_of_patients_per_day' => 8,
            'is_active' => false,
        ]);

        $schedules = Schedule::getSchedulesForDoctor($this->doctor->id, 'monday');

        $this->assertCount(1, $schedules);
        $this->assertEquals('morning', $schedules->first()->shift_period);
    }

    /** @test */
    public function it_handles_database_transaction_rollback_on_error()
    {
        $this->expectException(\Exception::class);

        DB::transaction(function () {
            Schedule::create([
                'doctor_id' => $this->doctor->id,
                'day_of_week' => 'monday',
                'shift_period' => 'morning',
                'start_time' => '08:00',
                'end_time' => '12:00',
                'number_of_patients_per_day' => 10,
                'is_active' => true,
            ]);

            // Simulate an error
            throw new \Exception('Simulated error');
        });

        // Verify no schedule was created due to rollback
        $this->assertDatabaseCount('schedules', 0);
    }

    /** @test */
    public function it_can_bulk_create_schedules()
    {
        $scheduleData = [
            [
                'doctor_id' => $this->doctor->id,
                'day_of_week' => 'monday',
                'shift_period' => 'morning',
                'start_time' => '08:00',
                'end_time' => '12:00',
                'number_of_patients_per_day' => 10,
                'is_active' => true,
            ],
            [
                'doctor_id' => $this->doctor->id,
                'day_of_week' => 'tuesday',
                'shift_period' => 'morning',
                'start_time' => '09:00',
                'end_time' => '13:00',
                'number_of_patients_per_day' => 12,
                'is_active' => true,
            ],
            [
                'doctor_id' => $this->doctor->id,
                'day_of_week' => 'wednesday',
                'shift_period' => 'afternoon',
                'start_time' => '14:00',
                'end_time' => '18:00',
                'number_of_patients_per_day' => 8,
                'is_active' => true,
            ],
        ];

        $response = $this->postJson('/api/schedules/bulk', [
            'schedules' => $scheduleData
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseCount('schedules', 3);
        
        foreach ($scheduleData as $schedule) {
            $this->assertDatabaseHas('schedules', $schedule);
        }
    }

    /** @test */
    public function it_validates_bulk_schedule_creation()
    {
        $invalidScheduleData = [
            [
                'doctor_id' => $this->doctor->id,
                'day_of_week' => 'invalid_day', // Invalid day
                'shift_period' => 'morning',
                'start_time' => '08:00',
                'end_time' => '12:00',
                'number_of_patients_per_day' => 10,
            ],
            [
                'doctor_id' => 99999, // Non-existent doctor
                'day_of_week' => 'tuesday',
                'shift_period' => 'morning',
                'start_time' => '09:00',
                'end_time' => '13:00',
                'number_of_patients_per_day' => 12,
            ],
        ];

        $response = $this->postJson('/api/schedules/bulk', [
            'schedules' => $invalidScheduleData
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'schedules.0.day_of_week',
            'schedules.1.doctor_id'
        ]);
    }

    /** @test */
    public function it_can_search_schedules_by_doctor_name()
    {
        // Create schedules for the doctor
        Schedule::factory()->count(2)->create([
            'doctor_id' => $this->doctor->id,
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/schedules/search?doctor_name={$this->user->name}");

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'schedules');
    }

    /** @test */
    public function it_can_filter_schedules_by_date_range()
    {
        $startDate = Carbon::now()->format('Y-m-d');
        $endDate = Carbon::now()->addDays(7)->format('Y-m-d');
        
        // Create schedule within date range
        Schedule::create([
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 'monday',
            'date' => Carbon::now()->addDays(3)->format('Y-m-d'),
            'shift_period' => 'morning',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'number_of_patients_per_day' => 10,
            'is_active' => true,
        ]);

        // Create schedule outside date range
        Schedule::create([
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 'friday',
            'date' => Carbon::now()->addDays(10)->format('Y-m-d'),
            'shift_period' => 'morning',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'number_of_patients_per_day' => 10,
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/schedules?start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'schedules');
    }

    /** @test */
    public function it_tracks_user_who_created_schedule()
    {
        $schedule = Schedule::create([
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 'monday',
            'shift_period' => 'morning',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'number_of_patients_per_day' => 10,
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        $this->assertDatabaseHas('schedules', [
            'id' => $schedule->id,
            'created_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_tracks_user_who_updated_schedule()
    {
        $schedule = Schedule::factory()->create([
            'doctor_id' => $this->doctor->id,
        ]);

        $schedule->update([
            'start_time' => '09:00',
            'updated_by' => $this->user->id,
        ]);

        $this->assertDatabaseHas('schedules', [
            'id' => $schedule->id,
            'start_time' => '09:00',
            'updated_by' => $this->user->id,
        ]);
    }
}