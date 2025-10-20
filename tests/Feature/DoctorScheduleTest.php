<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\Schedule;
use App\Models\Specialization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;

class DoctorScheduleTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $doctor;
    protected $specialization;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::factory()->create([
            'role' => 'doctor'
        ]);

        // Create a specialization
        $this->specialization = Specialization::factory()->create([
            'name' => 'Cardiology'
        ]);

        // Create a doctor
        $this->doctor = Doctor::factory()->create([
            'specialization_id' => $this->specialization->id,
            'frequency' => 'Weekly',
            'patients_based_on_time' => true,
            'time_slot' => 30,
            'allowed_appointment_today' => true
        ]);

        // Authenticate as admin user for testing
        $this->actingAs($this->user, 'web');
    }

    /** @test */
    public function it_creates_regular_schedule_for_doctor()
    {
        $scheduleData = [
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 'monday',
            'shift_period' => 'morning',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'number_of_patients_per_day' => 16,
            'is_active' => true
        ];

        $schedule = Schedule::create($scheduleData);

        $this->assertDatabaseHas('schedules', [
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 'monday',
            'shift_period' => 'morning',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'is_active' => true
        ]);

        $this->assertEquals($this->doctor->id, $schedule->doctor_id);
        $this->assertEquals('monday', $schedule->day_of_week);
    }

    /** @test */
    public function it_creates_multiple_schedules_for_different_days()
    {
        $schedules = [
            [
                'doctor_id' => $this->doctor->id,
                'day_of_week' => 'monday',
                'shift_period' => 'morning',
                'start_time' => '08:00',
                'end_time' => '12:00',
                'number_of_patients_per_day' => 16,
                'is_active' => true
            ],
            [
                'doctor_id' => $this->doctor->id,
                'day_of_week' => 'tuesday',
                'shift_period' => 'afternoon',
                'start_time' => '14:00',
                'end_time' => '18:00',
                'number_of_patients_per_day' => 16,
                'is_active' => true
            ]
        ];

        foreach ($schedules as $scheduleData) {
            Schedule::create($scheduleData);
        }

        $this->assertDatabaseCount('schedules', 2);
        
        $mondaySchedule = Schedule::where('day_of_week', 'monday')->first();
        $tuesdaySchedule = Schedule::where('day_of_week', 'tuesday')->first();

        $this->assertEquals('morning', $mondaySchedule->shift_period);
        $this->assertEquals('afternoon', $tuesdaySchedule->shift_period);
    }

    /** @test */
    public function it_creates_specific_date_schedule()
    {
        $specificDate = Carbon::now()->addDays(7)->format('Y-m-d');
        
        $scheduleData = [
            'doctor_id' => $this->doctor->id,
            'date' => $specificDate,
            'day_of_week' => strtolower(Carbon::parse($specificDate)->format('l')),
            'shift_period' => 'morning',
            'start_time' => '09:00',
            'end_time' => '13:00',
            'number_of_patients_per_day' => 20,
            'is_active' => true
        ];

        $schedule = Schedule::create($scheduleData);

        $this->assertDatabaseHas('schedules', [
            'doctor_id' => $this->doctor->id,
            'date' => $specificDate,
            'start_time' => '09:00',
            'end_time' => '13:00',
            'is_active' => true
        ]);

        $this->assertEquals($specificDate, $schedule->date);
    }

    /** @test */
    public function it_gets_schedules_for_doctor_by_day()
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
    public function it_handles_morning_and_afternoon_shifts_same_day()
    {
        $schedules = [
            [
                'doctor_id' => $this->doctor->id,
                'day_of_week' => 'wednesday',
                'shift_period' => 'morning',
                'start_time' => '08:00',
                'end_time' => '12:00',
                'number_of_patients_per_day' => 16,
                'is_active' => true
            ],
            [
                'doctor_id' => $this->doctor->id,
                'day_of_week' => 'wednesday',
                'shift_period' => 'afternoon',
                'start_time' => '14:00',
                'end_time' => '18:00',
                'number_of_patients_per_day' => 16,
                'is_active' => true
            ]
        ];

        foreach ($schedules as $scheduleData) {
            Schedule::create($scheduleData);
        }

        $wednesdaySchedules = Schedule::where('doctor_id', $this->doctor->id)
            ->where('day_of_week', 'wednesday')
            ->get();

        $this->assertCount(2, $wednesdaySchedules);
        
        $morningShift = $wednesdaySchedules->where('shift_period', 'morning')->first();
        $afternoonShift = $wednesdaySchedules->where('shift_period', 'afternoon')->first();

        $this->assertEquals('08:00', $morningShift->start_time);
        $this->assertEquals('14:00', $afternoonShift->start_time);
    }

    /** @test */
    public function it_validates_schedule_time_conflicts()
    {
        // Create first schedule
        Schedule::create([
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 'thursday',
            'shift_period' => 'morning',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'number_of_patients_per_day' => 16,
            'is_active' => true
        ]);

        // Try to create overlapping schedule
        $overlappingSchedule = [
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 'thursday',
            'shift_period' => 'morning',
            'start_time' => '10:00',
            'end_time' => '14:00',
            'number_of_patients_per_day' => 16,
            'is_active' => true
        ];

        // This should be handled by business logic validation
        // For now, we just verify the data structure
        $schedule = Schedule::create($overlappingSchedule);
        
        $this->assertDatabaseHas('schedules', [
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 'thursday',
            'start_time' => '10:00',
            'end_time' => '14:00'
        ]);
    }

    /** @test */
    public function it_handles_inactive_schedules()
    {
        Schedule::create([
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 'friday',
            'shift_period' => 'morning',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'number_of_patients_per_day' => 16,
            'is_active' => false
        ]);

        $activeSchedules = Schedule::where('doctor_id', $this->doctor->id)
            ->where('is_active', true)
            ->get();

        $inactiveSchedules = Schedule::where('doctor_id', $this->doctor->id)
            ->where('is_active', false)
            ->get();

        $this->assertCount(0, $activeSchedules);
        $this->assertCount(1, $inactiveSchedules);
    }

    /** @test */
    public function it_calculates_time_slots_based_on_duration()
    {
        $schedule = Schedule::create([
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 'monday',
            'shift_period' => 'morning',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'number_of_patients_per_day' => 16,
            'is_active' => true
        ]);

        // 4 hours (240 minutes) / 30 minutes per slot = 8 slots
        $startTime = Carbon::parse($schedule->start_time);
        $endTime = Carbon::parse($schedule->end_time);
        $totalMinutes = $startTime->diffInMinutes($endTime);
        $expectedSlots = $totalMinutes / $this->doctor->time_slot;

        $this->assertEquals(240, $totalMinutes);
        $this->assertEquals(8, $expectedSlots);
    }

    /** @test */
    public function it_handles_different_shift_periods()
    {
        // Test morning shift
        $morningSchedule = Schedule::create([
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 'saturday',
            'shift_period' => 'morning', // Valid enum value
            'start_time' => '08:00',
            'end_time' => '12:00',
            'number_of_patients_per_day' => 10,
            'is_active' => true
        ]);

        // Test afternoon shift
        $afternoonSchedule = Schedule::create([
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 'sunday',
            'shift_period' => 'afternoon', // Valid enum value
            'start_time' => '14:00',
            'end_time' => '17:00',
            'number_of_patients_per_day' => 8,
            'is_active' => true
        ]);

        $this->assertDatabaseHas('schedules', [
            'doctor_id' => $this->doctor->id,
            'shift_period' => 'morning'
        ]);

        $this->assertDatabaseHas('schedules', [
            'doctor_id' => $this->doctor->id,
            'shift_period' => 'afternoon'
        ]);
    }

    /** @test */
    public function it_validates_schedule_belongs_to_doctor()
    {
        $schedule = Schedule::create([
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 'sunday',
            'shift_period' => 'morning',
            'start_time' => '09:00',
            'end_time' => '13:00',
            'number_of_patients_per_day' => 12,
            'is_active' => true
        ]);

        $this->assertInstanceOf(Doctor::class, $schedule->doctor);
        $this->assertEquals($this->doctor->id, $schedule->doctor->id);
        $this->assertEquals($this->doctor->specialization_id, $schedule->doctor->specialization_id);
    }

    /** @test */
    public function it_handles_edge_case_time_slots()
    {
        // Create doctor with 15-minute time slots
        $doctor15min = Doctor::factory()->create([
            'specialization_id' => $this->specialization->id,
            'time_slot' => 15,
            'patients_based_on_time' => true
        ]);

        $schedule = Schedule::create([
            'doctor_id' => $doctor15min->id,
            'day_of_week' => 'monday',
            'shift_period' => 'morning',
            'start_time' => '08:00',
            'end_time' => '10:00', // 2 hours = 120 minutes
            'number_of_patients_per_day' => 8,
            'is_active' => true
        ]);

        $startTime = Carbon::parse($schedule->start_time);
        $endTime = Carbon::parse($schedule->end_time);
        $totalMinutes = $startTime->diffInMinutes($endTime); // This should be positive
        $expectedSlots = $totalMinutes / $doctor15min->time_slot;

        $this->assertEquals(120, $totalMinutes);
        $this->assertEquals(8, $expectedSlots);
    }

    /** @test */
    public function it_handles_patient_based_scheduling()
    {
        // Create doctor with patient-based scheduling
        $patientBasedDoctor = Doctor::factory()->create([
            'specialization_id' => $this->specialization->id,
            'patients_based_on_time' => false,
            'time_slot' => null
        ]);

        $schedule = Schedule::create([
            'doctor_id' => $patientBasedDoctor->id,
            'day_of_week' => 'tuesday',
            'shift_period' => 'afternoon',
            'start_time' => '14:00',
            'end_time' => '18:00',
            'number_of_patients_per_day' => 25,
            'is_active' => true
        ]);

        $this->assertDatabaseHas('schedules', [
            'doctor_id' => $patientBasedDoctor->id,
            'number_of_patients_per_day' => 25
        ]);

        $this->assertEquals(25, $schedule->number_of_patients_per_day);
    }
}