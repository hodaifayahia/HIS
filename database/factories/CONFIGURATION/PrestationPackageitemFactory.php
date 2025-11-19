<?php

namespace Database\Factories\CONFIGURATION;

use App\Models\CONFIGURATION\PrestationPackage;
use App\Models\CONFIGURATION\Prestation;
use App\Models\CONFIGURATION\PrestationPackageitem;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrestationPackageitemFactory extends Factory
{
    protected $model = PrestationPackageitem::class;

    public function definition()
    {
        return [
            'prestation_package_id' => PrestationPackage::factory(),
            'prestation_id' => Prestation::factory(),
        ];
    }
}
