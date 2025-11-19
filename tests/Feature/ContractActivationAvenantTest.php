<?php

namespace Tests\Feature;

use App\Models\B2B\Annex;
use App\Models\B2B\Avenant;
use App\Models\B2B\Convention;
use App\Models\B2B\ConventionDetail;
use App\Models\B2B\PrestationPricing;
use App\Models\ContractPercentage;
use App\Models\CONFIGURATION\Prestation;
use App\Models\CONFIGURATION\Service;
use App\Models\User;
use App\Services\B2B\AvenantService;
use App\Services\B2B\ConventionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ContractActivationAvenantTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private ConventionService $conventionService;
    private AvenantService $avenantService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->conventionService = app(ConventionService::class);
        $this->avenantService = app(AvenantService::class);
        
        // Create a test user
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /**
     * Test the complete flow: create contract, add annexes, activate contract, add avenant, verify prestations
     */
    public function test_contract_activation_creates_avenant_and_copies_annex_prestations()
    {
        // 1. Create a contract (convention)
        $convention = Convention::create([
            'name' => 'Test Convention',
            'description' => 'Test convention for activation',
            'status' => 'draft',
            'organisme_id' => 1,
            'creator_id' => $this->user->id,
        ]);

        // Create convention detail
        $conventionDetail = ConventionDetail::create([
            'convention_id' => $convention->id,
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'family_auth' => true,
            'max_price' => 1000.00,
            'min_price' => 50.00,
            'discount_percentage' => 10.0,
            'head' => true,
        ]);

        // 2. Add annexes with prestations
        $service = Service::create([
            'name' => 'Test Service',
            'description' => 'Test service for prestations',
        ]);

        $prestation1 = Prestation::create([
            'name' => 'Test Prestation 1',
            'description' => 'First test prestation',
            'service_id' => $service->id,
            'prix' => 100.00,
        ]);

        $prestation2 = Prestation::create([
            'name' => 'Test Prestation 2',
            'description' => 'Second test prestation',
            'service_id' => $service->id,
            'prix' => 200.00,
        ]);

        $contractPercentage = ContractPercentage::create([
            'percentage' => 80,
            'name' => '80% Coverage',
        ]);

        // Create first annex
        $annex1 = Annex::create([
            'convention_id' => $convention->id,
            'name' => 'Annex 1',
            'description' => 'First test annex',
            'creator_id' => $this->user->id,
        ]);

        // Add prestations to first annex
        $annexPrestation1 = PrestationPricing::create([
            'annex_id' => $annex1->id,
            'prestation_id' => $prestation1->id,
            'contract_percentage_id' => $contractPercentage->id,
            'discount_percentage' => 10.0,
            'max_price' => 90.00,
            'company_price' => 72.00, // 80% of 90
            'patient_price' => 18.00, // 20% of 90
            'head' => true,
        ]);

        $annexPrestation2 = PrestationPricing::create([
            'annex_id' => $annex1->id,
            'prestation_id' => $prestation2->id,
            'contract_percentage_id' => $contractPercentage->id,
            'discount_percentage' => 5.0,
            'max_price' => 190.00,
            'company_price' => 152.00, // 80% of 190
            'patient_price' => 38.00, // 20% of 190
            'head' => true,
        ]);

        // Create second annex
        $annex2 = Annex::create([
            'convention_id' => $convention->id,
            'name' => 'Annex 2',
            'description' => 'Second test annex',
            'creator_id' => $this->user->id,
        ]);

        // Add prestation to second annex
        $annexPrestation3 = PrestationPricing::create([
            'annex_id' => $annex2->id,
            'prestation_id' => $prestation1->id,
            'contract_percentage_id' => $contractPercentage->id,
            'discount_percentage' => 15.0,
            'max_price' => 85.00,
            'company_price' => 68.00, // 80% of 85
            'patient_price' => 17.00, // 20% of 85
            'head' => true,
        ]);

        // Verify initial state - no avenants exist
        $this->assertEquals(0, Avenant::where('convention_id', $convention->id)->count());
        $this->assertEquals(0, PrestationPricing::whereNotNull('avenant_id')->count());

        // 3. Activate the contract
        $activationResult = $this->conventionService->activateConventionById($convention->id, now()->format('Y-m-d'), false);

        // Verify activation result
        $this->assertEquals($convention->id, $activationResult['conventionId']);
        $this->assertEquals('active', $activationResult['status']);

        // Verify convention status is updated
        $convention->refresh();
        $this->assertEquals('active', $convention->status);
        $this->assertNotNull($convention->activation_at);

        // Verify initial avenant was created during activation
        $initialAvenants = Avenant::where('convention_id', $convention->id)->get();
        $this->assertEquals(1, $initialAvenants->count());

        $initialAvenant = $initialAvenants->first();
        $this->assertEquals('Initial Avenant', $initialAvenant->name);
        $this->assertTrue($initialAvenant->head);

        // Verify all annex prestations were copied to the initial avenant
        $avenantPrestations = PrestationPricing::where('avenant_id', $initialAvenant->id)->get();
        $this->assertEquals(3, $avenantPrestations->count()); // 2 from annex1 + 1 from annex2

        // Verify prestation details are correctly copied
        $copiedPrestation1 = $avenantPrestations->where('prestation_id', $prestation1->id)
            ->where('company_price', 72.00)->first();
        $this->assertNotNull($copiedPrestation1);
        $this->assertEquals($contractPercentage->id, $copiedPrestation1->contract_percentage_id);
        $this->assertEquals(10.0, $copiedPrestation1->discount_percentage);
        $this->assertTrue($copiedPrestation1->head);

        $copiedPrestation2 = $avenantPrestations->where('prestation_id', $prestation2->id)->first();
        $this->assertNotNull($copiedPrestation2);
        $this->assertEquals(152.00, $copiedPrestation2->company_price);
        $this->assertEquals(38.00, $copiedPrestation2->patient_price);
        $this->assertTrue($copiedPrestation2->head);

        // Check the second copy of prestation1 from annex2
        $copiedPrestation3 = $avenantPrestations->where('prestation_id', $prestation1->id)
            ->where('company_price', 68.00)->first();
        $this->assertNotNull($copiedPrestation3);
        $this->assertEquals(85.00, $copiedPrestation3->max_price);
        $this->assertTrue($copiedPrestation3->head);

        // 4. Add an additional avenant (simulating user creating a new avenant)
        $hasExistingAvenants = Avenant::where('convention_id', $convention->id)->exists();
        $this->assertTrue($hasExistingAvenants);

        $avenantResult = $this->avenantService->duplicateAllPrestationsWithExistingAvenant(
            $convention->id,
            $this->user->id
        );

        // Verify new avenant was created
        $this->assertArrayHasKey('avenantId', $avenantResult);
        $this->assertArrayHasKey('prestations', $avenantResult);

        $newAvenantId = $avenantResult['avenantId'];
        $newAvenant = Avenant::find($newAvenantId);
        $this->assertNotNull($newAvenant);
        $this->assertEquals('Additional Avenant', $newAvenant->name);
        $this->assertTrue($newAvenant->head); // New avenant becomes head
        $this->assertEquals('pending', $newAvenant->status);

        // Verify old avenant is no longer head
        $initialAvenant->refresh();
        $this->assertFalse($initialAvenant->head);

        // 5. Verify all prestations from the initial avenant are properly copied to the new avenant
        $newAvenantPrestations = PrestationPricing::where('avenant_id', $newAvenantId)->get();
        $this->assertEquals(3, $newAvenantPrestations->count());

        // Verify prestation details are correctly duplicated
        foreach ($avenantPrestations as $originalPrestation) {
            $duplicatedPrestation = $newAvenantPrestations->where('prestation_id', $originalPrestation->prestation_id)
                ->where('company_price', $originalPrestation->company_price)
                ->first();
            
            $this->assertNotNull($duplicatedPrestation, "Prestation {$originalPrestation->prestation_id} not found in new avenant");
            $this->assertEquals($originalPrestation->contract_percentage_id, $duplicatedPrestation->contract_percentage_id);
            $this->assertEquals($originalPrestation->discount_percentage, $duplicatedPrestation->discount_percentage);
            $this->assertEquals($originalPrestation->max_price, $duplicatedPrestation->max_price);
            $this->assertEquals($originalPrestation->patient_price, $duplicatedPrestation->patient_price);
            $this->assertTrue($duplicatedPrestation->head);
        }

        // Final verification: Total prestations in system
        $totalAvenantPrestations = PrestationPricing::whereNotNull('avenant_id')->count();
        $this->assertEquals(6, $totalAvenantPrestations); // 3 in initial + 3 in new avenant

        // Verify original annex prestations are still intact
        $totalAnnexPrestations = PrestationPricing::whereNotNull('annex_id')->whereNull('avenant_id')->count();
        $this->assertEquals(3, $totalAnnexPrestations); // Original annex prestations remain
    }

    /**
     * Test creating first avenant when no existing avenants exist
     */
    public function test_create_first_avenant_with_new_avenant_method()
    {
        // Create a contract with annexes
        $convention = Convention::create([
            'name' => 'Test Convention for First Avenant',
            'description' => 'Test convention for first avenant creation',
            'status' => 'active',
            'organisme_id' => 1,
            'creator_id' => $this->user->id,
        ]);

        $service = Service::create([
            'name' => 'Test Service',
            'description' => 'Test service for prestations',
        ]);

        $prestation = Prestation::create([
            'name' => 'Test Prestation',
            'description' => 'Test prestation',
            'service_id' => $service->id,
            'prix' => 150.00,
        ]);

        $contractPercentage = ContractPercentage::create([
            'percentage' => 70,
            'name' => '70% Coverage',
        ]);

        $annex = Annex::create([
            'convention_id' => $convention->id,
            'name' => 'Test Annex',
            'description' => 'Test annex',
            'creator_id' => $this->user->id,
        ]);

        PrestationPricing::create([
            'annex_id' => $annex->id,
            'prestation_id' => $prestation->id,
            'contract_percentage_id' => $contractPercentage->id,
            'discount_percentage' => 8.0,
            'max_price' => 138.00,
            'company_price' => 96.60, // 70% of 138
            'patient_price' => 41.40, // 30% of 138
            'head' => true,
        ]);

        // Verify no avenants exist initially
        $this->assertEquals(0, Avenant::where('convention_id', $convention->id)->count());

        // Create first avenant using the new avenant method
        $result = $this->avenantService->duplicateAllPrestationsWithNewAvenant(
            $convention->id,
            $this->user->id
        );

        // Verify avenant was created
        $this->assertArrayHasKey('avenantId', $result);
        $avenant = Avenant::find($result['avenantId']);
        $this->assertNotNull($avenant);
        $this->assertEquals('New Avenant', $avenant->name);
        $this->assertTrue($avenant->head);
        $this->assertEquals('pending', $avenant->status);

        // Verify prestations were copied
        $avenantPrestations = PrestationPricing::where('avenant_id', $avenant->id)->get();
        $this->assertEquals(1, $avenantPrestations->count());

        $copiedPrestation = $avenantPrestations->first();
        $this->assertEquals($prestation->id, $copiedPrestation->prestation_id);
        $this->assertEquals(96.60, $copiedPrestation->company_price);
        $this->assertEquals(41.40, $copiedPrestation->patient_price);
        $this->assertTrue($copiedPrestation->head);
    }
}