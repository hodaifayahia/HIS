<?php

require_once 'vendor/autoload.php';

use Illuminate\Http\Request;
use App\Http\Controllers\AppointmentController;
use Illuminate\Support\Facades\DB;

$data = [
    'patient_id' => 1,
    'doctor_id' => 1,
    'appointment_date' => '2024-12-20',
    'appointment_time' => '10:00',
    'description' => 'Test appointment with prestations',
    'prestations' => [12, 13]
];

$controller = new AppointmentController();
$request = new Request();
$request->merge($data);

try {
    $response = $controller->store($request);
    echo 'Appointment created successfully: ' . $response->resource->id . PHP_EOL;

    // Check if prestations were stored
    $prestations = DB::table('appointment_prestations')->where('appointment_id', $response->resource->id)->get();
    echo 'Prestations stored: ' . $prestations->count() . PHP_EOL;
    if ($prestations->count() > 0) {
        foreach ($prestations as $prestation) {
            echo 'Prestation ID: ' . $prestation->prestation_id . PHP_EOL;
        }
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
