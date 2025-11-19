<?php

namespace App\Providers;

use App\Models\Doctor;
use App\Models\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class ScheduleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Listen for Schedule model events
        Schedule::created(function ($schedule) {
            $this->clearCaches($schedule->doctor_id);
        });

        Schedule::updated(function ($schedule) {
            $this->clearCaches($schedule->doctor_id);
        });

        Schedule::deleted(function ($schedule) {
            $this->clearCaches($schedule->doctor_id);
        });

        // Listen for Doctor model events that might affect schedules
        Doctor::updated(function ($doctor) {
            $this->clearCaches($doctor->id);
        });
    }

    /**
     * Clear all relevant caches when a schedule changes
     */
    private function clearCaches($doctorId): void
    {
        // Clear the optimization cache
        Artisan::call('optimize:clear');

        // Clear specific doctor caches
        Cache::forget("doctor_availability_data_{$doctorId}");

        // Clear any date-specific caches for the next 30 days
        $startDate = now();
        $endDate = now()->addDays(30);

        while ($startDate <= $endDate) {
            $dateStr = $startDate->format('Y-m-d');
            Cache::forget("doctor_{$doctorId}_hours_{$dateStr}");
            Cache::forget("booked_slots_{$doctorId}_{$dateStr}");
            Cache::forget("request_working_hours_{$doctorId}_{$dateStr}");
            $startDate->addDay();
        }
    }
}
