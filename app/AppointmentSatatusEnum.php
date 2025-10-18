<?php

namespace App;

enum AppointmentSatatusEnum:int
{
    case SCHEDULED = 0;
    case CONFIRMED = 1;
    case CANCELED = 2;
    case PENDING = 3;
    case ONWORKING= 5;
    case DONE = 4;

    public function color(): string
    {
        return match($this) {
            self::SCHEDULED => 'primary', // Blue
            self::CONFIRMED => 'success', // Green
            self::DONE => 'info',         // Light Blue
            self::CANCELED => 'danger',  // Red
            self::PENDING => 'warning',  // Yellow
            self::ONWORKING => 'warning',  // Yellow
            default => 'secondary',      // Gray
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::SCHEDULED => 'fa fa-calendar-check',
            self::DONE => 'fa fa-check-circle',
            self::CANCELED => 'fa fa-ban',
            self::PENDING => 'fa fa-hourglass-half',
            self::CONFIRMED => 'fa fa-check',
            self::ONWORKING => 'warning',  // Yellow
            default => 'fa fa-question-circle',
        };
    }
    const STATUS_DETAILS = [
        'scheduled' => ['name' => 'Scheduled', 'color' => 'blue', 'icon' => 'calendar'],
        'confirmed' => ['name' => 'Confirmed', 'color' => 'green', 'icon' => 'check'],
        'canceled' => ['name' => 'Canceled', 'color' => 'red', 'icon' => 'times'],
        'pending' => ['name' => 'Pending', 'color' => 'orange', 'icon' => 'clock'],
        'done' => ['name' => 'Done', 'color' => 'gray', 'icon' => 'check-circle'],
        'on_working' => ['name' => 'On Working', 'color' => 'purple', 'icon' => 'tools'],
    ];

    public static function getStatusDetails($status)
    {
        return self::STATUS_DETAILS[$status] ?? ['name' => 'Unknown', 'color' => 'default', 'icon' => 'question'];
    }
}