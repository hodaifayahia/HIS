<?php

namespace Tests\Unit\Admission;

use App\Models\Admission;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use App\Services\Admission\AdmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdmissionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $admissionService;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admissionService = app(AdmissionService::class);
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /**
     * Test: Create nursing admission
     */
    public function test_can_create_nursing_admission(): void
    {
        $patient = Patient::factory()->create();
        $doctor = Doctor::factory()->create();

        $data = [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'type' => 'nursing',
        ];

        $admission = $this->admissionService->createAdmission($data);

        $this->assertInstanceOf(Admission::class, $admission);
        $this->assertEquals('nursing', $admission->type);
        $this->assertEquals('admitted', $admission->status);
        $this->assertNotNull($admission->admitted_at);
    }

    /**
     * Test: Create surgery admission requires prestation
     */
    public function test_create_surgery_admission_requires_prestation(): void
    {
        $patient = Patient::factory()->create();

        $data = [
            'patient_id' => $patient->id,
            'type' => 'surgery',
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Initial prestation is required for surgery admission');

        $this->admissionService->createAdmission($data);
    }

    /**
     * Test: Create surgery admission with prestation
     */
    public function test_can_create_surgery_admission_with_prestation(): void
    {
        $patient = Patient::factory()->create();
        $prestation = \App\Models\CONFIGURATION\Prestation::factory()->create();

        $data = [
            'patient_id' => $patient->id,
            'type' => 'surgery',
            'initial_prestation_id' => $prestation->id,
        ];

        $admission = $this->admissionService->createAdmission($data);

        $this->assertInstanceOf(Admission::class, $admission);
        $this->assertEquals('surgery', $admission->type);
        $this->assertEquals($prestation->id, $admission->initial_prestation_id);
    }

    /**
     * Test: Update admission
     */
    public function test_can_update_admission(): void
    {
        $admission = Admission::factory()->create();

        $updateData = [
            'status' => 'in_service',
        ];

        $updated = $this->admissionService->updateAdmission($admission->id, $updateData);

        $this->assertEquals('in_service', $updated->status);
    }

    /**
     * Test: Cannot update discharged admission
     */
    public function test_cannot_update_discharged_admission(): void
    {
        $admission = Admission::factory()->create([
            'status' => 'ready_for_discharge',
            'discharged_at' => now(),
        ]);

        $updateData = ['status' => 'in_service'];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot update discharged admission');

        $this->admissionService->updateAdmission($admission->id, $updateData);
    }

    /**
     * Test: Get statistics
     */
    public function test_can_get_statistics(): void
    {
        Admission::factory(3)->create(['type' => 'surgery']);
        Admission::factory(2)->create(['type' => 'nursing']);
        Admission::factory(1)->create(['status' => 'ready_for_discharge']);

        $stats = $this->admissionService->getStatistics();

        $this->assertEquals(5, $stats['total_admissions']);
        $this->assertEquals(3, $stats['surgery_admissions']);
        $this->assertEquals(2, $stats['nursing_admissions']);
        $this->assertEquals(1, $stats['ready_for_discharge']);
    }

    /**
     * Test: Discharge patient
     */
    public function test_can_discharge_patient(): void
    {
        $admission = Admission::factory()->create([
            'status' => 'document_pending',
            'documents_verified' => true,
        ]);

        $discharged = $this->admissionService->dischargePatient($admission->id);

        $this->assertEquals('ready_for_discharge', $discharged->status);
        $this->assertNotNull($discharged->discharged_at);
    }

    /**
     * Test: Cannot discharge without documents verified
     */
    public function test_cannot_discharge_without_documents(): void
    {
        $admission = Admission::factory()->create([
            'documents_verified' => false,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot discharge: Documents not verified');

        $this->admissionService->dischargePatient($admission->id);
    }

    /**
     * Test: Discharge generates discharge ticket
     */
    public function test_discharge_generates_ticket(): void
    {
        $admission = Admission::factory()->create([
            'status' => 'document_pending',
            'documents_verified' => true,
        ]);

        $this->admissionService->dischargePatient($admission->id);

        $this->assertDatabaseHas('admission_discharge_tickets', [
            'admission_id' => $admission->id,
        ]);
    }

    /**
     * Test: Today's admissions statistic
     */
    public function test_today_admissions_statistic(): void
    {
        Admission::factory(2)->create(['admitted_at' => now()]);
        Admission::factory(1)->create(['admitted_at' => now()->subDays(1)]);

        $stats = $this->admissionService->getStatistics();

        $this->assertEquals(2, $stats['today_admissions']);
    }

    /**
     * Test: Today's discharges statistic
     */
    public function test_today_discharges_statistic(): void
    {
        Admission::factory(1)->create(['discharged_at' => now()]);
        Admission::factory(1)->create(['discharged_at' => now()->subDays(1)]);

        $stats = $this->admissionService->getStatistics();

        $this->assertEquals(1, $stats['today_discharges']);
    }

    /**
     * Test: Active admissions count
     */
    public function test_active_admissions_count(): void
    {
        Admission::factory(1)->create(['status' => 'admitted']);
        Admission::factory(1)->create(['status' => 'in_service']);
        Admission::factory(1)->create(['status' => 'document_pending']);
        Admission::factory(1)->create(['status' => 'ready_for_discharge']);

        $stats = $this->admissionService->getStatistics();

        $this->assertEquals(3, $stats['active_admissions']);
    }
}
