<?php

namespace App\Http\Controllers\Appointment;

use App\Http\Controllers\Controller;
use App\Models\AppointmentAvailableMonth;

class AppointmentAvailableMonthController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($doctorid)
    {
        return AppointmentAvailableMonth::where('doctor_id', $doctorid)->get();
    }
}
