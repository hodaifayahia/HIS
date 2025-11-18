<?php

// Test file to verify convention prescription JSON decoding fix
require_once __DIR__.'/vendor/autoload.php';

// Simulate the JSON string that frontend sends
$jsonString = '[{"convention_id":20,"doctor_id":1,"prestations":[{"prestation_id":15,"doctor_id":1,"convention_price":"2000.00"}]}]';

echo 'Original JSON string: '.$jsonString."\n";

// Test JSON decoding
$decoded = json_decode($jsonString, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "JSON decoding successful!\n";
    echo 'Decoded array: '.print_r($decoded, true)."\n";

    // Test that it's an array and has the expected structure
    if (is_array($decoded) && ! empty($decoded)) {
        echo "✓ Conventions is an array\n";
        echo '✓ First convention ID: '.($decoded[0]['convention_id'] ?? 'not found')."\n";
        echo '✓ Prestations count: '.count($decoded[0]['prestations'] ?? [])."\n";
    } else {
        echo "✗ Conventions is not a proper array\n";
    }
} else {
    echo '✗ JSON decoding failed: '.json_last_error_msg()."\n";
}

echo "\nTest completed!\n";
