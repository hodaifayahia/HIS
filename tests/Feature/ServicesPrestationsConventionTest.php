<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\FreshOrganismeAndConventionSeeder;
use App\Models\CONFIGURATION\Service;
use App\Models\CONFIGURATION\Prestation;
use App\Models\CRM\Organisme;

class ServicesPrestationsConventionTest extends TestCase
{
    use RefreshDatabase;

    public function test_services_prestations_and_conventions_seeded_and_linked()
    {
        $this->seed(FreshOrganismeAndConventionSeeder::class);

        // Default service created
        $service = Service::where('name', 'Seeder Default Service')->first();
        $this->assertNotNull($service, 'Default service not created');

        // Prestations created
        $consult = Prestation::where('internal_code', 'PREST-CONS-001')->first();
        $this->assertNotNull($consult, 'Consultation prestation not created');
        $this->assertEquals($service->id, $consult->service_id);

        // Organismes and conventions
        $this->assertEquals(3, Organisme::count());

        $statuses = ['pending', 'active', 'terminated'];
        $foundStatus = [];

        foreach (Organisme::all() as $organisme) {
            foreach ($organisme->conventions as $convention) {
                $this->assertGreaterThanOrEqual(1, $convention->annexes()->count());
                $this->assertGreaterThanOrEqual(1, $convention->contractPercentages()->count());
                $foundStatus[$convention->status] = true;
            }
        }

        $this->assertTrue(count(array_intersect($statuses, array_keys($foundStatus))) > 0, 'No expected convention statuses found');
    }
}
