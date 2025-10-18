<?php

/**
 * Simple test script to verify Service Demand API endpoints
 */

require_once __DIR__.'/vendor/autoload.php';

$baseUrl = 'http://localhost:8000';

echo "=== Service Demand Management System Test ===\n\n";

// Test 1: Get all demands (should be empty initially)
echo "1. Testing GET /api/service-demands\n";
$response = file_get_contents($baseUrl.'/api/service-demands', false, stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "Accept: application/json\r\n",
    ],
]));

if ($response !== false) {
    $data = json_decode($response, true);
    echo '✓ Response received: '.count($data['data']['data'])." demands found\n";
} else {
    echo "✗ Failed to get demands\n";
}

// Test 2: Get services
echo "\n2. Testing GET /api/service-demands/meta/services\n";
$response = file_get_contents($baseUrl.'/api/service-demands/meta/services', false, stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "Accept: application/json\r\n",
    ],
]));

if ($response !== false) {
    $data = json_decode($response, true);
    echo '✓ Response received: '.count($data['data'])." services found\n";

    // Test 3: Create a new demand if we have services
    if (count($data['data']) > 0) {
        $serviceId = $data['data'][0]['id'];
        echo "\n3. Testing POST /api/service-demands (Creating new demand)\n";

        $postData = json_encode([
            'service_id' => $serviceId,
            'expected_date' => date('Y-m-d', strtotime('+7 days')),
            'notes' => 'Test demand created via API test script',
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
                'content' => $postData,
            ],
        ]);

        $response = file_get_contents($baseUrl.'/api/service-demands', false, $context);

        if ($response !== false) {
            $data = json_decode($response, true);
            echo '✓ Demand created successfully with code: '.$data['data']['demand_code']."\n";
            $demandId = $data['data']['id'];

            // Test 4: Get products
            echo "\n4. Testing GET /api/service-demands/meta/products\n";
            $response = file_get_contents($baseUrl.'/api/service-demands/meta/products', false, stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => "Accept: application/json\r\n",
                ],
            ]));

            if ($response !== false) {
                $productsData = json_decode($response, true);
                echo '✓ Response received: '.count($productsData['data'])." products found\n";

                // Test 5: Add item to demand if we have products
                if (count($productsData['data']) > 0) {
                    $productId = $productsData['data'][0]['id'];
                    echo "\n5. Testing POST /api/service-demands/{$demandId}/items (Adding item)\n";

                    $itemData = json_encode([
                        'product_id' => $productId,
                        'quantity' => 5,
                        'unit_price' => 10.50,
                        'notes' => 'Test item added via API',
                    ]);

                    $context = stream_context_create([
                        'http' => [
                            'method' => 'POST',
                            'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
                            'content' => $itemData,
                        ],
                    ]);

                    $response = file_get_contents($baseUrl."/api/service-demands/{$demandId}/items", false, $context);

                    if ($response !== false) {
                        $itemResponse = json_decode($response, true);
                        echo "✓ Item added successfully to demand\n";

                        // Test 6: Get demand details
                        echo "\n6. Testing GET /api/service-demands/{$demandId} (Get demand details)\n";
                        $response = file_get_contents($baseUrl."/api/service-demands/{$demandId}", false, stream_context_create([
                            'http' => [
                                'method' => 'GET',
                                'header' => "Accept: application/json\r\n",
                            ],
                        ]));

                        if ($response !== false) {
                            $demandData = json_decode($response, true);
                            echo "✓ Demand details retrieved successfully\n";
                            echo '  - Code: '.$demandData['data']['demand_code']."\n";
                            echo '  - Service: '.$demandData['data']['service']['name']."\n";
                            echo '  - Items: '.count($demandData['data']['items'])."\n";
                            echo '  - Status: '.$demandData['data']['status']."\n";
                        } else {
                            echo "✗ Failed to get demand details\n";
                        }

                    } else {
                        echo "✗ Failed to add item to demand\n";
                    }
                }
            } else {
                echo "✗ Failed to get products\n";
            }

        } else {
            echo "✗ Failed to create demand\n";
        }
    }
} else {
    echo "✗ Failed to get services\n";
}

// Test 7: Get stats
echo "\n7. Testing GET /api/service-demands/meta/stats\n";
$response = file_get_contents($baseUrl.'/api/service-demands/meta/stats', false, stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "Accept: application/json\r\n",
    ],
]));

if ($response !== false) {
    $data = json_decode($response, true);
    echo "✓ Stats retrieved successfully\n";
    echo '  - Total demands: '.($data['data']['total_demands'] ?? 0)."\n";
    echo '  - Draft demands: '.($data['data']['draft_demands'] ?? 0)."\n";
    echo '  - Total items: '.($data['data']['total_items'] ?? 0)."\n";
} else {
    echo "✗ Failed to get stats\n";
}

echo "\n=== Test completed ===\n";
