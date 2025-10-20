<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CONFIGURATION\ModalityType;
use App\Models\CONFIGURATION\Modality;
use App\Models\Specialization;
use App\Models\CONFIGURATION\Service;

class AssignModalitySpecializationsSeeder extends Seeder
{
    public function run()
    {
        // Ensure a default service exists for specializations
        $defaultService = Service::firstOrCreate([
            'name' => 'Seeder Default Service'
        ], [
            'description' => 'Auto-created by seeder',
            'is_active' => 1
        ]);

        // For each modality type create or reuse a specialization and assign to modalities
        foreach (ModalityType::all() as $type) {
            $spec = Specialization::firstOrCreate(
                ['name' => $type->name . ' Specialization'],
                ['description' => 'Specialization for ' . $type->name, 'service_id' => $defaultService->id, 'is_active' => 1]
            );

            Modality::where('modality_type_id', $type->id)
                ->whereNull('specialization_id')
                ->update(['specialization_id' => $spec->id]);
        }
    }
}
