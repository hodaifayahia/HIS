<?php

require_once __DIR__ . '/vendor/autoload.php';

// Initialize Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Requests\Reception\StoreConventionPrescriptionRequest;
use Illuminate\Http\Request;

echo "=== Testing StoreConventionPrescriptionRequest Validation ===\n\n";

try {
    // Test data that should pass validation
    $testData = [
        'conventions' => [
            [
                'convention_id' => 1,
                'doctor_id' => 1,
                'prestations' => [
                    [
                        'prestation_id' => 1,
                        'convention_price' => 100.00,
                        'doctor_id' => 1
                    ]
                ]
            ]
        ],
        'prise_en_charge_date' => '2024-12-01',
        'familyAuth' => 'Test authorization',
        'adherentPatient_id' => 1
    ];

    // Create a mock request
    $request = new Request($testData);

    // Create the form request and set the request
    $formRequest = new StoreConventionPrescriptionRequest();
    $formRequest->setContainer(app());
    $formRequest->setValidator(app('validator')->make($testData, $formRequest->rules(), $formRequest->messages()));

    // Test validation
    $validator = app('validator')->make($testData, $formRequest->rules(), $formRequest->messages());

    if ($validator->fails()) {
        echo "❌ Validation failed:\n";
        foreach ($validator->errors()->all() as $error) {
            echo "  - $error\n";
        }
    } else {
        echo "✅ Validation passed!\n";
        echo "Validated data structure:\n";
        print_r($validator->validated());
    }

    echo "\n=== Testing with invalid data (string conventions) ===\n";

    // Test with the old format (string) that was causing the error
    $invalidData = [
        'conventions' => 'invalid string data',
        'prise_en_charge_date' => '2024-12-01',
        'familyAuth' => 'Test authorization'
    ];

    $invalidValidator = app('validator')->make($invalidData, $formRequest->rules(), $formRequest->messages());

    if ($invalidValidator->fails()) {
        echo "✅ Invalid data correctly rejected:\n";
        foreach ($invalidValidator->errors()->all() as $error) {
            echo "  - $error\n";
        }
    } else {
        echo "❌ Invalid data was incorrectly accepted!\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
