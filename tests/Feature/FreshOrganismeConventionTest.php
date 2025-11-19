<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\FreshOrganismeAndConventionSeeder;
use App\Models\CRM\Organisme;

class FreshOrganismeConventionTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_organismes_and_conventions_seeded_with_relations_and_statuses()
    {
        // Run the fresh seeder which recreates organismes and seeds conventions
        $this->seed(FreshOrganismeAndConventionSeeder::class);

        $organismes = Organisme::all();
        $this->assertCount(3, $organismes);

        $statusesSeen = [];

        foreach ($organismes as $organisme) {
            $this->assertGreaterThanOrEqual(1, $organisme->conventions()->count(), 'Organisme has no conventions: ' . $organisme->id);

            foreach ($organisme->conventions as $convention) {
                // Ensure related annexes and contract percentages exist (avenants may be zero)
                $this->assertGreaterThanOrEqual(1, $convention->annexes()->count(), 'Convention has no annexes: ' . $convention->id);
                $this->assertGreaterThanOrEqual(1, $convention->contractPercentages()->count(), 'Convention has no contract percentages: ' . $convention->id);

                $statusesSeen[$convention->status] = true;
            }
        }

        // Ensure we have at least one of the statuses present in the seeded data
        $this->assertTrue(array_key_exists('draft', $statusesSeen) || array_key_exists('active', $statusesSeen) || array_key_exists('terminated', $statusesSeen));
    }
}
