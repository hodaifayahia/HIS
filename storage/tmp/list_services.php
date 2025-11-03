<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$svcs = \App\Models\CONFIGURATION\Service::select('id','name','service_abv')->orderBy('id')->get()->toArray();
echo json_encode($svcs, JSON_PRETTY_PRINT);
