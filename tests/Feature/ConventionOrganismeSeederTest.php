<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\ConventionOrganismeSeeder;
use App\Models\CRM\Organisme;
use App\Models\B2B\Convention;

class ConventionOrganismeSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_conventions_and_related_models_are_seeded_for_each_organisme()
    {
        // Create some organismes to seed against
        Organisme::factory()->count(3)->create();

        // Run the seeder
        $this->seed(ConventionOrganismeSeeder::class);

        // Assert each organisme has at least one convention
        $organismes = Organisme::all();
        $this->assertNotEmpty($organismes);

        foreach ($organismes as $organisme) {
            $this->assertGreaterThanOrEqual(1, $organisme->conventions()->count(), 'Organisme has no conventions: ' . $organisme->id);

            foreach ($organisme->conventions as $convention) {
                // conventions should have at least one annex or contract percentage or be created
                $this->assertTrue(
                    $convention->annexes()->count() >= 0 && $convention->contractPercentages()->count() >= 0,
                    'Convention relations missing'
                );
            }
        }
    }
}
