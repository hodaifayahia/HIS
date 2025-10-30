<?php

namespace Tests\Feature\Reception;

use Tests\TestCase;
use App\Services\Reception\PackageConversionFacade;
use App\Actions\Reception\DetectMatchingPackage;
use App\Actions\Reception\PreparePackageConversionData;
use App\Actions\Reception\ExecutePackageConversion;
use App\Models\Reception\ficheNavette;
use App\Models\Reception\ficheNavetteItem;
use App\Models\CONFIGURATION\Prestation;
use App\Models\CONFIGURATION\PrestationPackage;
use App\Models\CONFIGURATION\PrestationPackageItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Example Tests - How to Test the New Architecture
 * 
 * These tests show how to:
 * - Test Actions independently
 * - Test the Facade
 * - Test the complete flow
 */
class PackageConversionFacadeTest extends TestCase
{
    use RefreshDatabase;

    private PackageConversionFacade $facade;

    protected function setUp(): void
    {
        parent::setUp();

        // Create instances
        $detector = new DetectMatchingPackage();
        $preparer = new PreparePackageConversionData($detector);
        $executor = new ExecutePackageConversion();

        $this->facade = new PackageConversionFacade($detector, $preparer, $executor);
    }

    /**
     * Test 1: Detect exact package match
     */
    public function test_detect_package_finds_exact_match(): void
    {
        // Create 2 prestations
        $prestation1 = Prestation::factory()->create(['id' => 5]);
        $prestation2 = Prestation::factory()->create(['id' => 87]);

        // Create package with these prestations
        $package = PrestationPackage::factory()->create(['name' => 'Cardiology']);
        PrestationPackageItem::factory()
            ->for($package)
            ->create(['prestation_id' => $prestation1->id]);
        PrestationPackageItem::factory()
            ->for($package)
            ->create(['prestation_id' => $prestation2->id]);

        // Detect should find the package
        $detected = $this->facade->detectPackage([5, 87]);

        $this->assertNotNull($detected);
        $this->assertEquals($package->id, $detected->id);
        $this->assertEquals('Cardiology', $detected->name);
    }

    /**
     * Test 2: Detect package returns null if no match
     */
    public function test_detect_package_returns_null_if_no_match(): void
    {
        $prestation = Prestation::factory()->create();

        // No package exists
        $detected = $this->facade->detectPackage([$prestation->id]);

        $this->assertNull($detected);
    }

    /**
     * Test 3: Check and prepare returns correct data
     */
    public function test_check_and_prepare_returns_should_convert(): void
    {
        // Create fiche
        $fiche = ficheNavette::factory()->create();

        // Create prestations
        $prest1 = Prestation::factory()->create();
        $prest2 = Prestation::factory()->create();

        // Add first prestation to fiche
        ficheNavetteItem::factory()
            ->for($fiche)
            ->create(['prestation_id' => $prest1->id]);

        // Create package matching both prestations
        $package = PrestationPackage::factory()
            ->has(PrestationPackageItem::factory()->count(2))
            ->create();

        // Update package items to have our prestations
        $package->items[0]->update(['prestation_id' => $prest1->id]);
        $package->items[1]->update(['prestation_id' => $prest2->id]);

        // Check if conversion needed when adding second prestation
        $result = $this->facade->checkAndPrepare(
            $fiche->id,
            [$prest2->id],  // new
            [$prest1->id]   // existing
        );

        $this->assertTrue($result['should_convert']);
        $this->assertEquals($package->id, $result['package_id']);
        $this->assertNotEmpty($result['items_to_remove']);
    }

    /**
     * Test 4: Execute conversion removes items and creates package
     */
    public function test_execute_conversion_removes_items_and_creates_package(): void
    {
        // Create fiche with 2 items
        $fiche = ficheNavette::factory()
            ->state(['total_amount' => 200])
            ->create();

        $prest1 = Prestation::factory()->create();
        $prest2 = Prestation::factory()->create();

        $item1 = ficheNavetteItem::factory()
            ->for($fiche)
            ->state(['prestation_id' => $prest1->id, 'final_price' => 100])
            ->create();

        $item2 = ficheNavetteItem::factory()
            ->for($fiche)
            ->state(['prestation_id' => $prest2->id, 'final_price' => 100])
            ->create();

        // Create package with price 150
        $package = PrestationPackage::factory()
            ->state(['price' => 150])
            ->has(PrestationPackageItem::factory()->count(2))
            ->create();

        // Execute conversion
        $updatedFiche = $this->facade->execute(
            $fiche->id,
            $package->id,
            [$item1->id, $item2->id],  // items to remove
        );

        // Assert old items removed
        $this->assertEquals(0, ficheNavetteItem::where('prestation_id', $prest1->id)->count());
        $this->assertEquals(0, ficheNavetteItem::where('prestation_id', $prest2->id)->count());

        // Assert new package item created
        $packageItem = ficheNavetteItem::where('package_id', $package->id)->first();
        $this->assertNotNull($packageItem);
        $this->assertEquals(150, $packageItem->final_price);

        // Assert total updated
        $updatedFiche->refresh();
        $this->assertEquals(150, $updatedFiche->total_amount);
    }

    /**
     * Test 5: Cascading conversion (replace smaller package with larger)
     */
    public function test_cascading_conversion_replaces_smaller_package_with_larger(): void
    {
        $fiche = ficheNavette::factory()->create();

        // Create 3 prestations
        $p1 = Prestation::factory()->create();
        $p2 = Prestation::factory()->create();
        $p3 = Prestation::factory()->create();

        // Create package with 2 prestations (small)
        $smallPackage = PrestationPackage::factory()
            ->state(['price' => 100, 'name' => 'Small Package'])
            ->create();
        PrestationPackageItem::factory()
            ->for($smallPackage)
            ->create(['prestation_id' => $p1->id]);
        PrestationPackageItem::factory()
            ->for($smallPackage)
            ->create(['prestation_id' => $p2->id]);

        // Create package with 3 prestations (large)
        $largePackage = PrestationPackage::factory()
            ->state(['price' => 200, 'name' => 'Large Package'])
            ->create();
        PrestationPackageItem::factory()
            ->for($largePackage)
            ->create(['prestation_id' => $p1->id]);
        PrestationPackageItem::factory()
            ->for($largePackage)
            ->create(['prestation_id' => $p2->id]);
        PrestationPackageItem::factory()
            ->for($largePackage)
            ->create(['prestation_id' => $p3->id]);

        // Add small package item to fiche
        $smallItem = ficheNavetteItem::factory()
            ->for($fiche)
            ->state(['package_id' => $smallPackage->id, 'final_price' => 100])
            ->create();

        // Check if adding p3 triggers conversion to large package
        $result = $this->facade->checkAndPrepare(
            $fiche->id,
            [$p3->id],  // new prestation
            []
        );

        $this->assertTrue($result['should_convert']);
        $this->assertEquals($largePackage->id, $result['package_id']);
        $this->assertTrue($result['is_cascading']);

        // Execute conversion
        $updated = $this->facade->execute(
            $fiche->id,
            $largePackage->id,
            array_map(fn($i) => $i['id'], $result['items_to_remove']),
        );

        // Assert small package item removed
        $this->assertNull(
            ficheNavetteItem::where('package_id', $smallPackage->id)
                ->where('fiche_navette_id', $fiche->id)
                ->first()
        );

        // Assert large package item created
        $this->assertNotNull(
            ficheNavetteItem::where('package_id', $largePackage->id)
                ->where('fiche_navette_id', $fiche->id)
                ->first()
        );
    }

    /**
     * Test 6: Don't convert if items are paid
     */
    public function test_no_conversion_if_items_already_paid(): void
    {
        $fiche = ficheNavette::factory()->create();

        $prest1 = Prestation::factory()->create();
        $prest2 = Prestation::factory()->create();

        // Create paid item
        ficheNavetteItem::factory()
            ->for($fiche)
            ->state(['prestation_id' => $prest1->id, 'payment_status' => 'paid'])
            ->create();

        // Create package
        $package = PrestationPackage::factory()
            ->has(PrestationPackageItem::factory()->count(2))
            ->create();

        $package->items[0]->update(['prestation_id' => $prest1->id]);
        $package->items[1]->update(['prestation_id' => $prest2->id]);

        // Check conversion
        $result = $this->facade->checkAndPrepare(
            $fiche->id,
            [$prest2->id],
            [$prest1->id]
        );

        // Should NOT convert (item is paid)
        $this->assertFalse($result['should_convert']);
    }
}
