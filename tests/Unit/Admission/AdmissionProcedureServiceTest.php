<?php

namespace Tests\Unit\Admission;

use App\Models\Admission;
use App\Models\AdmissionProcedure;
use App\Models\CONFIGURATION\Prestation;
use App\Models\User;
use App\Services\Admission\AdmissionProcedureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdmissionProcedureServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $procedureService;

    protected $user;

    protected $admission;

    protected $prestation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->procedureService = app(AdmissionProcedureService::class);
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->admission = Admission::factory()->create();
        $this->prestation = Prestation::factory()->create(['price' => 1000]);
    }

    /**
     * Test: Create procedure
     */
    public function test_can_create_procedure(): void
    {
        $data = [
            'admission_id' => $this->admission->id,
            'name' => 'Surgery A',
            'description' => 'Test surgery',
            'prestation_id' => $this->prestation->id,
        ];

        $procedure = $this->procedureService->createProcedure($data);

        $this->assertInstanceOf(AdmissionProcedure::class, $procedure);
        $this->assertEquals('Surgery A', $procedure->name);
        $this->assertEquals('scheduled', $procedure->status);
    }

    /**
     * Test: Update procedure
     */
    public function test_can_update_procedure(): void
    {
        $procedure = AdmissionProcedure::factory()->create(['admission_id' => $this->admission->id]);

        $updateData = [
            'name' => 'Updated Surgery',
        ];

        $updated = $this->procedureService->updateProcedure($procedure->id, $updateData);

        $this->assertEquals('Updated Surgery', $updated->name);
    }

    /**
     * Test: Cannot update completed procedure
     */
    public function test_cannot_update_completed_procedure(): void
    {
        $procedure = AdmissionProcedure::factory()->create([
            'admission_id' => $this->admission->id,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot update completed or cancelled procedure');

        $this->procedureService->updateProcedure($procedure->id, ['name' => 'New Name']);
    }

    /**
     * Test: Complete procedure
     */
    public function test_can_complete_procedure(): void
    {
        $procedure = AdmissionProcedure::factory()->create([
            'admission_id' => $this->admission->id,
            'status' => 'scheduled',
            'prestation_id' => $this->prestation->id,
        ]);

        $completed = $this->procedureService->completeProcedure($procedure->id);

        $this->assertEquals('completed', $completed->status);
        $this->assertNotNull($completed->completed_at);
    }

    /**
     * Test: Cannot complete already completed procedure
     */
    public function test_cannot_complete_already_completed(): void
    {
        $procedure = AdmissionProcedure::factory()->create([
            'admission_id' => $this->admission->id,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Procedure already completed');

        $this->procedureService->completeProcedure($procedure->id);
    }

    /**
     * Test: Cannot complete cancelled procedure
     */
    public function test_cannot_complete_cancelled(): void
    {
        $procedure = AdmissionProcedure::factory()->create([
            'admission_id' => $this->admission->id,
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot complete cancelled procedure');

        $this->procedureService->completeProcedure($procedure->id);
    }

    /**
     * Test: Cancel procedure
     */
    public function test_can_cancel_procedure(): void
    {
        $procedure = AdmissionProcedure::factory()->create([
            'admission_id' => $this->admission->id,
            'status' => 'scheduled',
        ]);

        $cancelled = $this->procedureService->cancelProcedure($procedure->id);

        $this->assertEquals('cancelled', $cancelled->status);
        $this->assertNotNull($cancelled->cancelled_at);
    }

    /**
     * Test: Cannot cancel completed procedure
     */
    public function test_cannot_cancel_completed(): void
    {
        $procedure = AdmissionProcedure::factory()->create([
            'admission_id' => $this->admission->id,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot cancel completed procedure');

        $this->procedureService->cancelProcedure($procedure->id);
    }

    /**
     * Test: Cannot cancel already cancelled procedure
     */
    public function test_cannot_cancel_already_cancelled(): void
    {
        $procedure = AdmissionProcedure::factory()->create([
            'admission_id' => $this->admission->id,
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Procedure already cancelled');

        $this->procedureService->cancelProcedure($procedure->id);
    }

    /**
     * Test: Completing procedure creates billing record
     */
    public function test_complete_procedure_creates_billing_record(): void
    {
        $procedure = AdmissionProcedure::factory()->create([
            'admission_id' => $this->admission->id,
            'status' => 'scheduled',
            'prestation_id' => $this->prestation->id,
        ]);

        $this->procedureService->completeProcedure($procedure->id);

        $this->assertDatabaseHas('admission_billing_records', [
            'admission_id' => $this->admission->id,
            'procedure_id' => $procedure->id,
            'amount' => $this->prestation->price,
        ]);
    }

    /**
     * Test: Check medication conversion rule (under 5000)
     */
    public function test_medication_conversion_not_triggered_under_5000(): void
    {
        $result = $this->procedureService->checkMedicationConversion($this->admission->id, 3000);

        $this->assertNull($result);
    }

    /**
     * Test: Check medication conversion rule (over 5000)
     */
    public function test_medication_conversion_triggered_over_5000(): void
    {
        $result = $this->procedureService->checkMedicationConversion($this->admission->id, 5500);

        $this->assertInstanceOf(AdmissionProcedure::class, $result);
        $this->assertTrue($result->is_medication_conversion);
        $this->assertStringContainsString('5500', $result->description);
    }

    /**
     * Test: Medication conversion creates completed procedure
     */
    public function test_medication_conversion_creates_completed_procedure(): void
    {
        $procedure = $this->procedureService->checkMedicationConversion($this->admission->id, 5500);

        $this->assertEquals('Medication Charges', $procedure->name);
        $this->assertEquals('completed', $procedure->status);
    }

    /**
     * Test: Procedure created with creator tracking
     */
    public function test_procedure_tracks_creator(): void
    {
        $procedure = AdmissionProcedure::factory()->create([
            'admission_id' => $this->admission->id,
        ]);

        $this->assertEquals($this->user->id, $procedure->created_by);
    }

    /**
     * Test: Complete procedure without prestation doesn't create billing record
     */
    public function test_complete_procedure_without_prestation_no_billing(): void
    {
        $procedure = AdmissionProcedure::factory()->create([
            'admission_id' => $this->admission->id,
            'status' => 'scheduled',
            'prestation_id' => null,
        ]);

        $this->procedureService->completeProcedure($procedure->id);

        $this->assertDatabaseMissing('admission_billing_records', [
            'procedure_id' => $procedure->id,
        ]);
    }
}
