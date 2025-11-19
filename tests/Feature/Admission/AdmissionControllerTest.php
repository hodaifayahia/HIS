<?php

namespace Tests\Feature\Admission;

use App\Models\Admission;
use App\Models\CONFIGURATION\Prestation;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdmissionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $patient;

    protected $doctor;

    protected $prestation;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($this->user);

        // Create test data
        $this->patient = Patient::factory()->create();
        $this->doctor = Doctor::factory()->create();
        $this->prestation = Prestation::factory()->create();
    }

    /**
     * Test: List all admissions
     */
    public function test_can_list_admissions(): void
    {
        Admission::factory(3)->create(['patient_id' => $this->patient->id]);

        $response = $this->getJson('/api/admissions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'patient_id',
                        'doctor_id',
                        'type',
                        'status',
                        'admitted_at',
                        'documents_verified',
                        'created_at',
                    ],
                ],
                'meta' => ['current_page', 'total', 'per_page'],
            ]);
    }

    /**
     * Test: Filter admissions by type
     */
    public function test_can_filter_admissions_by_type(): void
    {
        Admission::factory(2)->create(['type' => 'surgery', 'patient_id' => $this->patient->id]);
        Admission::factory(1)->create(['type' => 'nursing', 'patient_id' => $this->patient->id]);

        $response = $this->getJson('/api/admissions?type=surgery');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /**
     * Test: Filter admissions by status
     */
    public function test_can_filter_admissions_by_status(): void
    {
        Admission::factory(2)->create(['status' => 'admitted', 'patient_id' => $this->patient->id]);
        Admission::factory(1)->create(['status' => 'in_service', 'patient_id' => $this->patient->id]);

        $response = $this->getJson('/api/admissions?status=admitted');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /**
     * Test: Search admissions by patient name
     */
    public function test_can_search_admissions_by_patient_name(): void
    {
        $patient = Patient::factory()->create(['Firstname' => 'John', 'Lastname' => 'Doe']);
        Admission::factory()->create(['patient_id' => $patient->id]);

        $response = $this->getJson('/api/admissions?search=John');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /**
     * Test: Create nursing admission
     */
    public function test_can_create_nursing_admission(): void
    {
        $data = [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'type' => 'nursing',
        ];

        $response = $this->postJson('/api/admissions', $data);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'nursing')
            ->assertJsonPath('data.status', 'admitted');

        $this->assertDatabaseHas('admissions', [
            'patient_id' => $this->patient->id,
            'type' => 'nursing',
        ]);
    }

    /**
     * Test: Create surgery admission with initial prestation
     */
    public function test_can_create_surgery_admission_with_prestation(): void
    {
        $data = [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'type' => 'surgery',
            'initial_prestation_id' => $this->prestation->id,
        ];

        $response = $this->postJson('/api/admissions', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'surgery')
            ->assertJsonPath('data.initial_prestation_id', $this->prestation->id);
    }

    /**
     * Test: Surgery admission requires initial prestation
     */
    public function test_surgery_admission_requires_initial_prestation(): void
    {
        $data = [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'type' => 'surgery',
        ];

        $response = $this->postJson('/api/admissions', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('initial_prestation_id');
    }

    /**
     * Test: Create admission requires patient
     */
    public function test_admission_requires_patient(): void
    {
        $data = [
            'type' => 'nursing',
        ];

        $response = $this->postJson('/api/admissions', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('patient_id');
    }

    /**
     * Test: Get admission details
     */
    public function test_can_get_admission_details(): void
    {
        $admission = Admission::factory()->create(['patient_id' => $this->patient->id]);

        $response = $this->getJson("/api/admissions/{$admission->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $admission->id)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'patient',
                    'doctor',
                    'type',
                    'status',
                    'admitted_at',
                    'discharged_at',
                    'documents_verified',
                ],
            ]);
    }

    /**
     * Test: Update admission
     */
    public function test_can_update_admission(): void
    {
        $admission = Admission::factory()->create(['patient_id' => $this->patient->id]);

        $data = [
            'status' => 'in_service',
            'documents_verified' => true,
        ];

        $response = $this->patchJson("/api/admissions/{$admission->id}", $data);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'in_service')
            ->assertJsonPath('data.documents_verified', true);

        $this->assertDatabaseHas('admissions', [
            'id' => $admission->id,
            'status' => 'in_service',
            'documents_verified' => true,
        ]);
    }

    /**
     * Test: Cannot update discharged admission
     */
    public function test_cannot_update_discharged_admission(): void
    {
        $admission = Admission::factory()->create([
            'patient_id' => $this->patient->id,
            'status' => 'ready_for_discharge',
            'discharged_at' => now(),
        ]);

        $data = ['status' => 'in_service'];

        $response = $this->patchJson("/api/admissions/{$admission->id}", $data);

        $response->assertStatus(500);
    }

    /**
     * Test: Delete admission
     */
    public function test_can_delete_admission(): void
    {
        $admission = Admission::factory()->create(['patient_id' => $this->patient->id]);

        $response = $this->deleteJson("/api/admissions/{$admission->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('admissions', ['id' => $admission->id]);
    }

    /**
     * Test: Cannot delete discharged admission
     */
    public function test_cannot_delete_discharged_admission(): void
    {
        $admission = Admission::factory()->create([
            'patient_id' => $this->patient->id,
            'status' => 'ready_for_discharge',
            'discharged_at' => now(),
        ]);

        $response = $this->deleteJson("/api/admissions/{$admission->id}");

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    /**
     * Test: Get active admissions
     */
    public function test_can_get_active_admissions(): void
    {
        Admission::factory()->create(['status' => 'admitted', 'patient_id' => $this->patient->id]);
        Admission::factory()->create(['status' => 'in_service', 'patient_id' => $this->patient->id]);
        Admission::factory()->create(['status' => 'ready_for_discharge', 'patient_id' => $this->patient->id]);

        $response = $this->getJson('/api/admissions/active');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /**
     * Test: Get statistics
     */
    public function test_can_get_statistics(): void
    {
        Admission::factory(3)->create(['type' => 'surgery', 'patient_id' => $this->patient->id]);
        Admission::factory(2)->create(['type' => 'nursing', 'patient_id' => $this->patient->id]);

        $response = $this->getJson('/api/admissions/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_admissions',
                    'active_admissions',
                    'surgery_admissions',
                    'nursing_admissions',
                    'ready_for_discharge',
                    'today_admissions',
                    'today_discharges',
                ],
            ]);
    }

    /**
     * Test: Discharge admission (success case)
     */
    public function test_can_discharge_admission(): void
    {
        $admission = Admission::factory()->create([
            'patient_id' => $this->patient->id,
            'status' => 'document_pending',
            'documents_verified' => true,
        ]);

        $response = $this->postJson("/api/admissions/{$admission->id}/discharge");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'ready_for_discharge')
            ->assertJsonPath('data.discharged_at', '!=', null);
    }

    /**
     * Test: Cannot discharge without documents verified
     */
    public function test_cannot_discharge_without_documents_verified(): void
    {
        $admission = Admission::factory()->create([
            'patient_id' => $this->patient->id,
            'documents_verified' => false,
        ]);

        $response = $this->postJson("/api/admissions/{$admission->id}/discharge");

        $response->assertStatus(422);
    }

    /**
     * Test: Discharge generates ticket
     */
    public function test_discharge_generates_discharge_ticket(): void
    {
        $admission = Admission::factory()->create([
            'patient_id' => $this->patient->id,
            'status' => 'document_pending',
            'documents_verified' => true,
        ]);

        $this->postJson("/api/admissions/{$admission->id}/discharge");

        $this->assertDatabaseHas('admission_discharge_tickets', [
            'admission_id' => $admission->id,
        ]);
    }

    /**
     * Test: Pagination works correctly
     */
    public function test_pagination_works_correctly(): void
    {
        Admission::factory(25)->create(['patient_id' => $this->patient->id]);

        $response = $this->getJson('/api/admissions?per_page=10&page=1');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.current_page', 1);

        $response = $this->getJson('/api/admissions?per_page=10&page=2');

        $response->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.current_page', 2);
    }
}
