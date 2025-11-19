<?php

require_once __DIR__.'/vendor/autoload.php';

// Simple test for the Nursing Controller
echo "=== Simple Nursing Controller Test ===\n";

try {
    // Initialize Laravel
    $app = require_once __DIR__.'/bootstrap/app.php';
    $kernel = $app->make('Illuminate\Contracts\Console\Kernel');
    $kernel->bootstrap();

    // Import the controller
    require_once __DIR__.'/app/Http/Controllers/Nursing/NursingProductController.php';

    echo "✓ Laravel bootstrapped successfully\n";
    echo "✓ NursingProductController class loaded\n";

    // Check if the controller class exists
    if (class_exists('App\Http\Controllers\Nursing\NursingProductController')) {
        echo "✓ NursingProductController class found\n";

        // Create controller instance
        $controller = new App\Http\Controllers\Nursing\NursingProductController;
        echo "✓ Controller instance created\n";

        // Test reflection to see methods
        $reflection = new ReflectionClass($controller);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        echo "\nPublic methods in controller:\n";
        foreach ($methods as $method) {
            if ($method->class === 'App\Http\Controllers\Nursing\NursingProductController') {
                echo '  - '.$method->name."\n";
            }
        }
    } else {
        echo "✗ NursingProductController class not found\n";
    }

} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n";
    echo 'Trace: '.$e->getTraceAsString()."\n";
}

echo "\n=== Test Complete ===\n";
