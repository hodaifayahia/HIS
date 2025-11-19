<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CONFIGURATION\ModalityType;
use App\Models\CONFIGURATION\Modality;
use App\Models\CONFIGURATION\ModalitySchedule;
use App\Models\CONFIGURATION\ModalityAvailableMonth;
use App\Models\CONFIGURATION\AppointmentModalityForce;
use App\Models\Specialization;
use App\Models\INFRASTRUCTURE\Room;

class ModalitySeeder extends Seeder
{
    public function run()
    {
        // Ensure a default service exists for rooms (idempotent)
        $defaultServiceAttrs = \App\Models\CONFIGURATION\Service::factory()->make()->toArray();
        $defaultService = \App\Models\CONFIGURATION\Service::firstOrCreate([
            'name' => 'Seeder Default Service'
        ], array_merge($defaultServiceAttrs, ['name' => 'Seeder Default Service']));

        // Create a few modality types (idempotent)
        $types = collect();
        for ($i = 0; $i < 3; $i++) {
            $attrs = ModalityType::factory()->make()->toArray();
            $type = ModalityType::firstOrCreate([
                'name' => $attrs['name']
            ], $attrs);
            $types->push($type);
        }

        // Create a few modalities for each type and related records
        $types->each(function ($type) use ($defaultService) {
            // Ensure there is a specialization associated with this modality type
            $specAttrs = Specialization::factory()->make(['service_id' => $defaultService->id])->toArray();
            $typeSpecialization = Specialization::firstOrCreate(
                ['name' => $type->name . ' Specialization'],
                array_merge($specAttrs, ['name' => $type->name . ' Specialization'])
            );

            // Create (or find) 4 modalities for each type and related records when newly created
            for ($i = 0; $i < 4; $i++) {
                $modAttrs = \App\Models\CONFIGURATION\Modality::factory()->make(['modality_type_id' => $type->id])->toArray();

                // Ensure uniqueness by internal_code
                $modality = \App\Models\CONFIGURATION\Modality::firstOrCreate(
                    ['internal_code' => $modAttrs['internal_code']],
                    $modAttrs
                );

                // If the modality was recently created (i.e., has no schedules yet), add related records
                if ($modality->modalitySchedule()->count() == 0 && $modality->appointmentModalityForce()->count() == 0) {
                    $room = Room::factory()->create(['service_id' => $defaultService->id]);
                    $modality->physical_location_id = $room->id;
                    $modality->specialization_id = $typeSpecialization->id;
                    $modality->save();

                    ModalitySchedule::factory()->count(2)->create([
                        'modality_id' => $modality->id,
                    ]);

                    ModalityAvailableMonth::factory()->count(3)->create([
                        'modality_id' => $modality->id,
                    ]);

                    AppointmentModalityForce::factory()->create([
                        'modality_id' => $modality->id,
                    ]);
                }
            }

            // Also ensure existing modalities for this type (created in prior runs) are assigned
            \App\Models\CONFIGURATION\Modality::where('modality_type_id', $type->id)
                ->whereNull('specialization_id')
                ->update(['specialization_id' => $typeSpecialization->id]);
        });
    }
}
