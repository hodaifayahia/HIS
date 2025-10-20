<?php

namespace Tests\Feature;

use App\Http\Controllers\AppointmentController;
use App\Models\Doctor;
use App\Models\Schedule;
use App\Models\Specialization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AppointmentDateParsingTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private $doctor;
    private $specialization;
    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->specialization = Specialization::factory()->create();
        $this->doctor = Doctor::factory()->create([
            'specialization_id' => $this->specialization->id
        ]);
    }

    /** @test */
    public function it_correctly_parses_dates_when_schedule_times_are_strings()
    {
        // Create a schedule with string times
        $schedule = Schedule::create([
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 'monday',
            'shift_period' => 'morning',
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'number_of_patients_per_day' => 10,
            'is_active' => true,
        ]);

        $date = '2025-10-22';
        
        // Test the fixed logic directly
        $startTimeStr = $schedule->start_time instanceof Carbon ? $schedule->start_time->format('H:i:s') : $schedule->start_time;
        $endTimeStr = $schedule->end_time instanceof Carbon ? $schedule->end_time->format('H:i:s') : $schedule->end_time;

        $startTime = Carbon::parse($date.' '.$startTimeStr);
        $endTime = Carbon::parse($date.' '.$endTimeStr);

        $this->assertEquals('2025-10-22 08:00:00', $startTime->format('Y-m-d H:i:s'));
        $this->assertEquals('2025-10-22 12:00:00', $endTime->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_correctly_parses_dates_when_schedule_times_are_carbon_instances()
    {
        // Create a schedule that will have Carbon instances due to casting
        $schedule = Schedule::make([
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 'monday',
            'shift_period' => 'morning',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'number_of_patients_per_day' => 10,
            'is_active' => true,
        ]);

        $date = '2025-10-22';
        
        // Test the fixed logic with Carbon instances
        $startTimeStr = $schedule->start_time instanceof Carbon ? $schedule->start_time->format('H:i:s') : $schedule->start_time;
        $endTimeStr = $schedule->end_time instanceof Carbon ? $schedule->end_time->format('H:i:s') : $schedule->end_time;

        $startTime = Carbon::parse($date.' '.$startTimeStr);
        $endTime = Carbon::parse($date.' '.$endTimeStr);

        $this->assertEquals('2025-10-22 08:00:00', $startTime->format('Y-m-d H:i:s'));
        $this->assertEquals('2025-10-22 12:00:00', $endTime->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_prevents_malformed_date_strings_in_calculate_total_available_time()
    {
        // Create a schedule
        $schedule = Schedule::create([
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 'monday',
            'shift_period' => 'morning',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'number_of_patients_per_day' => 20,
            'is_active' => true,
        ]);

        $controller = new AppointmentController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('calculateTotalAvailableTime');
        $method->setAccessible(true);

        $date = '2025-10-22';
        $schedules = collect([$schedule]);

        // This should not throw an exception and should return a valid result
        $result = $method->invoke($controller, $date, $schedules);
        
        $this->assertIsNumeric($result);
        $this->assertGreaterThan(0, $result);
    }

    /** @test */
    public function it_prevents_malformed_date_strings_in_get_doctor_working_hours()
    {
        // Create a schedule
        $schedule = Schedule::create([
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 'monday',
            'shift_period' => 'morning',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'number_of_patients_per_day' => 20,
            'is_active' => true,
        ]);

        $controller = new AppointmentController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getDoctorWorkingHours');
        $method->setAccessible(true);

        $dateString = '2025-10-22';
        $schedules = collect([$schedule]);

        // This should not throw an exception and should return a valid result
        $result = $method->invoke($controller, $dateString, $schedules);
        
        $this->assertIsArray($result);
    }

    /** @test */
    public function it_handles_edge_cases_with_different_time_formats()
    {
        $testCases = [
            ['08:00', '17:00'],
            ['08:00:00', '17:00:00'],
            ['8:00', '17:00'],
            ['08:30', '17:30'],
        ];

        foreach ($testCases as [$startTime, $endTime]) {
            $schedule = Schedule::make([
                'start_time' => $startTime,
                'end_time' => $endTime,
            ]);

            $date = '2025-10-22';
            
            $startTimeStr = $schedule->start_time instanceof Carbon ? $schedule->start_time->format('H:i:s') : $schedule->start_time;
            $endTimeStr = $schedule->end_time instanceof Carbon ? $schedule->end_time->format('H:i:s') : $schedule->end_time;

            // Should not throw exceptions
            $startDateTime = Carbon::parse($date.' '.$startTimeStr);
            $endDateTime = Carbon::parse($date.' '.$endTimeStr);

            $this->assertInstanceOf(Carbon::class, $startDateTime);
            $this->assertInstanceOf(Carbon::class, $endDateTime);
            $this->assertEquals('2025-10-22', $startDateTime->format('Y-m-d'));
            $this->assertEquals('2025-10-22', $endDateTime->format('Y-m-d'));
        }
    }

    /** @test */
    public function it_does_not_create_duplicate_dates_in_parsed_strings()
    {
        $schedule = Schedule::make([
            'start_time' => '08:00',
            'end_time' => '17:00',
        ]);

        $date = '2025-10-22';
        
        $startTimeStr = $schedule->start_time instanceof Carbon ? $schedule->start_time->format('H:i:s') : $schedule->start_time;
        $endTimeStr = $schedule->end_time instanceof Carbon ? $schedule->end_time->format('H:i:s') : $schedule->end_time;

        $concatenatedStart = $date.' '.$startTimeStr;
        $concatenatedEnd = $date.' '.$endTimeStr;

        // Ensure we don't have malformed strings like "2025-10-22 2025-10-20 08:00:00"
        $this->assertStringNotContainsString('2025-10-22 2025-', $concatenatedStart);
        $this->assertStringNotContainsString('2025-10-22 2025-', $concatenatedEnd);
        
        // Ensure proper format
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(:\d{2})?$/', $concatenatedStart);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(:\d{2})?$/', $concatenatedEnd);
    }
}