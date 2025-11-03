<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Str;

$services = \App\Models\CONFIGURATION\Service::whereNull('service_abv')->get();
$updated = [];
foreach ($services as $s) {
    $slug = Str::upper(preg_replace('/[^A-Z0-9]+/i', '_', trim($s->name)));
    // Ensure uniqueness by appending id if needed
    if (\App\Models\CONFIGURATION\Service::where('service_abv', $slug)->exists()) {
        $slug = $slug . '_' . $s->id;
    }
    $s->service_abv = $slug;
    $s->save();
    $updated[] = ['id' => $s->id, 'name' => $s->name, 'service_abv' => $s->service_abv];
}

echo json_encode(['updated' => $updated], JSON_PRETTY_PRINT);
