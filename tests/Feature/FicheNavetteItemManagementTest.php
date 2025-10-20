<?php

namespace Tests\Feature;

use App\Models\Reception\ficheNavette;
use App\Models\Reception\ficheNavetteItem;
use App\Models\CONFIGURATION\Prestation;
use App\Models\CONFIGURATION\PrestationPackage;
use App\Models\B2B\Convention;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\CONFIGURATION\Service;
use App\Models\Specialization;
use App\Models\Reception\ItemDependency;
use App\Models\Nursing\PatientConsumption;
use App\Models\Appointment;
use App\Models\CRM\Organisme;
use App\Models\B2B\ConventionDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FicheNavetteItemManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $patient;
    protected $doctor;
    protected $service;
    protected $specialization;
    protected $prestation;
    protected $ficheNavette;
    protected $convention;
    protected $organisme;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user first
        $this->user = \App\Models\User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);
        
        // Create test data
        $this->patient = Patient::factory()->create([
            'Firstname' => 'John',
            'Lastname' => 'Doe',
            'balance' => 1000.00
        ]);

        $this->service = Service::factory()->create([
            'name' => 'Cardiology Service'
        ]);

        $this->specialization = Specialization::factory()->create([
            'name' => 'Cardiology'
        ]);

        $this->doctor = Doctor::factory()->create([
            'specialization_id' => $this->specialization->id
        ]);

        $this->prestation = Prestation::factory()->create([
            'name' => 'ECG Test',
            'internal_code' => 'ECG001',
            'billing_code' => 'BILL001',
            'service_id' => $this->service->id,
            'specialization_id' => $this->specialization->id,
            'public_price' => 100.00,
            'consumables_cost' => 10.00,
            'vat_rate' => 20.00,
            'is_active' => true,
            'requires_hospitalization' => false,
            'need_an_appointment' => true
        ]);

        $this->organisme = Organisme::factory()->create([
            'name' => 'Test Insurance',
            'organism_color' => '#FF0000'
        ]);

        $this->convention = Convention::factory()->create([
            'name' => 'Test Convention',
            'organisme_id' => $this->organisme->id,
            'status' => 'active'
        ]);

        ConventionDetail::factory()->create([
            'convention_id' => $this->convention->id,
            'discount_percentage' => 20.00,
            'updated_by_id' => $this->user->id
        ]);

        $this->ficheNavette = ficheNavette::factory()->create([
            'patient_id' => $this->patient->id,
            'status' => 'pending',
            'fiche_date' => now(),
            'total_amount' => 0.00
        ]);
    }

    /** @test */
    public function it_can_create_fiche_navette_item_with_valid_data()
    {
        $itemData = [
            'fiche_navette_id' => $this->ficheNavette->id,
            'prestation_id' => $this->prestation->id,
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'status' => 'pending',
            'base_price' => 100.00,
            'final_price' => 120.00,
            'payment_status' => 'unpaid',
            'notes' => 'Test notes'
        ];

        $item = ficheNavetteItem::create($itemData);

        $this->assertInstanceOf(ficheNavetteItem::class, $item);
        $this->assertEquals($this->ficheNavette->id, $item->fiche_navette_id);
        $this->assertEquals($this->prestation->id, $item->prestation_id);
        $this->assertEquals($this->doctor->id, $item->doctor_id);
        $this->assertEquals('pending', $item->status);
        $this->assertEquals(100.00, $item->base_price);
        $this->assertEquals(120.00, $item->final_price);
        $this->assertEquals('Test notes', $item->notes);
    }

    /** @test */
    public function it_validates_required_fields_for_fiche_navette_item()
    {
        $response = $this->postJson('/api/reception/fiche-navette/' . $this->ficheNavette->id . '/items', [
            'items' => [[
                // Missing required fields
                'status' => 'pending'
            ]]
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items.0.prestation_id']);
    }

    /** @test */
    public function it_can_establish_relationship_with_fiche_navette()
    {
        $item = ficheNavetteItem::factory()->create([
            'fiche_navette_id' => $this->ficheNavette->id,
            'prestation_id' => $this->prestation->id
        ]);

        $this->assertInstanceOf(ficheNavette::class, $item->ficheNavette);
        $this->assertEquals($this->ficheNavette->id, $item->ficheNavette->id);
    }

    /** @test */
    public function it_can_establish_relationship_with_prestation()
    {
        $item = ficheNavetteItem::factory()->create([
            'fiche_navette_id' => $this->ficheNavette->id,
            'prestation_id' => $this->prestation->id
        ]);

        $this->assertInstanceOf(Prestation::class, $item->prestation);
        $this->assertEquals($this->prestation->id, $item->prestation->id);
        $this->assertEquals('ECG Test', $item->prestation->name);
    }

    /** @test */
    public function it_can_establish_relationship_with_doctor()
    {
        $item = ficheNavetteItem::factory()->create([
            'fiche_navette_id' => $this->ficheNavette->id,
            'prestation_id' => $this->prestation->id,
            'doctor_id' => $this->doctor->id
        ]);

        $this->assertInstanceOf(Doctor::class, $item->doctor);
        $this->assertEquals($this->doctor->id, $item->doctor->id);
        $this->assertEquals('Dr. Smith', $item->doctor->name);
    }

    /** @test */
    public function it_can_establish_relationship_with_patient()
    {
        $item = ficheNavetteItem::factory()->create([
            'fiche_navette_id' => $this->ficheNavette->id,
            'prestation_id' => $this->prestation->id,
            'patient_id' => $this->patient->id
        ]);

        $this->assertInstanceOf(Patient::class, $item->patient);
        $this->assertEquals($this->patient->id, $item->patient->id);
        $this->assertEquals('John', $item->patient->Firstname);
    }

    /** @test */
    public function it_can_establish_relationship_with_convention()
    {
        $item = ficheNavetteItem::factory()->create([
            'fiche_navette_id' => $this->ficheNavette->id,
            'prestation_id' => $this->prestation->id,
            'convention_id' => $this->convention->id
        ]);

        $this->assertInstanceOf(Convention::class, $item->convention);
        $this->assertEquals($this->convention->id, $item->convention->id);
        $this->assertEquals('Test Convention', $item->convention->name);
    }

    /** @test */
    public function it_can_handle_prestation_package_relationship()
    {
        $package = PrestationPackage::factory()->create([
            'name' => 'Cardiology Package',
            'description' => 'Complete cardiology examination',
            'price' => 300.00
        ]);

        $item = ficheNavetteItem::factory()->create([
            'fiche_navette_id' => $this->ficheNavette->id,
            'prestation_id' => $this->prestation->id,
            'package_id' => $package->id
        ]);

        $this->assertInstanceOf(PrestationPackage::class, $item->package);
        $this->assertEquals($package->id, $item->package->id);
        $this->assertEquals('Cardiology Package', $item->package->name);
    }

    /** @test */
    public function it_can_determine_if_item_is_package()
    {
        $package = PrestationPackage::factory()->create();
        
        $packageItem = ficheNavetteItem::factory()->create([
            'fiche_navette_id' => $this->ficheNavette->id,
            'prestation_id' => $this->prestation->id,
            'package_id' => $package->id
        ]);

        $individualItem = ficheNavetteItem::factory()->create([
            'fiche_navette_id' => $this->ficheNavette->id,
            'prestation_id' => $this->prestation->id,
            'package_id' => null
        ]);

        $this->assertTrue($packageItem->isPackage());
        $this->assertFalse($individualItem->isPackage());
    }

    /** @test */
    public function it_can_determine_if_item_is_individual_prestation()
    {
        $package = PrestationPackage::factory()->create();
        
        $packageItem = ficheNavetteItem::factory()->create([
            'fiche_navette_id' => $this->ficheNavette->id,
            'prestation_id' => $this->prestation->id,
            'package_id' => $package->id
        ]);

        $individualItem = ficheNavetteItem::factory()->create([
            'fiche_navette_id' => $this->ficheNavette->id,
            'prestation_id' => $this->prestation->id,
            'package_id' => null
        ]);

        $this->assertFalse($packageItem->isIndividualPrestation());
        $this->assertTrue($individualItem->isIndividualPrestation());
    }

    /** @test */
    public function it_can_handle_item_dependencies()
    {
        $dependentPrestation = Prestation::factory()->create([
            'name' => 'Blood Test',
            'service_id' => $this->service->id,
            'specialization_id' => $this->specialization->id
        ]);

        $parentItem = ficheNavetteItem::factory()->create([
            'fiche_navette_id' => $this->ficheNavette->id,
            'prestation_id' => $this->prestation->id
        ]);

        $dependency = ItemDependency::factory()->create([
            'parent_item_id' => $parentItem->id,
            'dependent_prestation_id' => $dependentPrestation->id,
            'dependency_type' => 'required',
            'notes' => 'Required for complete diagnosis'
        ]);

        $parentItem->refresh();
        
        $this->assertTrue($parentItem->dependencies->count() > 0);
        $this->assertEquals($dependency->id, $parentItem->dependencies->first()->id);
        $this->assertEquals('required', $parentItem->dependencies->first()->dependency_type);
    }

    /** @test */
    public function it_can_handle_nursing_consumption_flag()
    {
        $nursingItem = ficheNavetteItem::factory()->create([
            'fiche_navette_id' => $this->ficheNavette->id,
            'prestation_id' => $this->prestation->id,
            'is_nursing_consumption' => true
        ]);

        $regularItem = ficheNavetteItem::factory()->create([
            'fiche_navette_id' => $this->ficheNavette->id,
            'prestation_id' => $this->prestation->id,
            'is_nursing_consumption' => false
        ]);

        $this->assertTrue($nursingItem->is_nursing_consumption);
        $this->assertFalse($regularItem->is_nursing_consumption);
    }

    /** @test */
    public function it_can_handle_payment_status_transitions()
    {
        $item = ficheNavetteItem::factory()->create([
            'fiche_navette_id' => $this->ficheNavette->id,
            'prestation_id' => $this->prestation->id,
            'payment_status' => 'unpaid',
            'final_price' => 120.00,
            'paid_amount' => 0.00
        ]);

        // Partial payment
        $item->update([
            'payment_status' => 'partially_paid',
            'paid_amount' => 60.00
        ]);

        $this->assertEquals('partially_paid', $item->payment_status);
        $this->assertEquals(60.00, $item->paid_amount);
        $this->assertEquals(60.00, $item->remaining_amount);

        // Full payment
        $item->update([
            'payment_status' => 'paid',
            'paid_amount' => 120.00
        ]);

        $this->assertEquals('paid', $item->payment_status);
        $this->assertEquals(120.00, $item->paid_amount);
        $this->assertEquals(0.00, $item->remaining_amount);
    }

    /** @test */
    public function it_can_handle_family_authorization_as_array()
    {
        $authorizationData = [
            'authorized_by' => 'John Doe Sr.',
            'relationship' => 'Father',
            'authorization_date' => now()->toDateString(),
            'notes' => 'Emergency authorization'
        ];

        $item = ficheNavetteItem::factory()->create([
            'fiche_navette_id' => $this->ficheNavette->id,
            'prestation_id' => $this->prestation->id,
            'family_authorization' => $authorizationData
        ]);

        $this->assertIsArray($item->family_authorization);
        $this->assertEquals('John Doe Sr.', $item->family_authorization['authorized_by']);
        $this->assertEquals('Father', $item->family_authorization['relationship']);
    }

    /** @test */
    public function it_can_handle_uploaded_files_as_array()
    {
        $fileData = [
            [
                'id' => 'file_1',
                'original_name' => 'prescription.pdf',
                'path' => 'uploads/prescription.pdf',
                'size' => 1024,
                'mime_type' => 'application/pdf'
            ],
            [
                'id' => 'file_2',
                'original_name' => 'report.docx',
                'path' => 'uploads/report.docx',
                'size' => 2048,
                'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ]
        ];

        $item = ficheNavetteItem::factory()->create([
            'fiche_navette_id' => $this->ficheNavette->id,
            'prestation_id' => $this->prestation->id,
            'uploaded_file' => $fileData
        ]);

        $this->assertIsArray($item->uploaded_file);
        $this->assertCount(2, $item->uploaded_file);
        $this->assertEquals('prescription.pdf', $item->uploaded_file[0]['original_name']);
        $this->assertEquals('report.docx', $item->uploaded_file[1]['original_name']);
    }

    /** @test */
    public function it_can_handle_prise_en_charge_date_casting()
    {
        $priseEnChargeDate = now()->subDays(5);

        $item = ficheNavetteItem::factory()->create([
            'fiche_navette_id' => $this->ficheNavette->id,
            'prestation_id' => $this->prestation->id,
            'prise_en_charge_date' => $priseEnChargeDate
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $item->prise_en_charge_date);
        $this->assertEquals($priseEnChargeDate->toDateString(), $item->prise_en_charge_date->toDateString());
    }

    /** @test */
    public function it_can_store_multiple_items_via_api()
    {
        $secondPrestation = Prestation::factory()->create([
            'name' => 'Blood Pressure Check',
            'service_id' => $this->service->id,
            'specialization_id' => $this->specialization->id
        ]);

        $itemsData = [
            'items' => [
                [
                    'prestation_id' => $this->prestation->id,
                    'doctor_id' => $this->doctor->id,
                    'status' => 'pending',
                    'notes' => 'First item'
                ],
                [
                    'prestation_id' => $secondPrestation->id,
                    'doctor_id' => $this->doctor->id,
                    'status' => 'pending',
                    'notes' => 'Second item'
                ]
            ]
        ];

        $response = $this->postJson('/api/reception/fiche-navette/' . $this->ficheNavette->id . '/items', $itemsData);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'items' => [
                    '*' => [
                        'id',
                        'prestation_id',
                        'doctor_id',
                        'status',
                        'notes'
                    ]
                ]
            ]
        ]);

        $this->assertDatabaseCount('fiche_navette_items', 2);
    }

    /** @test */
    public function it_can_update_item_via_api()
    {
        $item = ficheNavetteItem::factory()->create([
            'fiche_navette_id' => $this->ficheNavette->id,
            'prestation_id' => $this->prestation->id,
            'status' => 'pending',
            'notes' => 'Original notes'
        ]);

        $updateData = [
            'status' => 'completed',
            'notes' => 'Updated notes',
            'final_price' => 150.00
        ];

        $response = $this->putJson('/api/reception/fiche-navette/' . $this->ficheNavette->id . '/items/' . $item->id, $updateData);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data'
        ]);

        $item->refresh();
        $this->assertEquals('completed', $item->status);
        $this->assertEquals('Updated notes', $item->notes);
    }

    /** @test */
    public function it_can_delete_item_via_api()
    {
        $item = ficheNavetteItem::factory()->create([
            'fiche_navette_id' => $this->ficheNavette->id,
            'prestation_id' => $this->prestation->id
        ]);

        $response = $this->deleteJson('/api/reception/fiche-navette/' . $this->ficheNavette->id . '/items/' . $item->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data'
        ]);

        $this->assertDatabaseMissing('fiche_navette_items', ['id' => $item->id]);
    }

    /** @test */
    public function it_can_handle_convention_prescription_with_files()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('prescription.pdf', 1024, 'application/pdf');

        $conventionData = [
            'conventions' => [
                [
                    'convention_id' => $this->convention->id,
                    'prestations' => [
                        [
                            'prestation_id' => $this->prestation->id,
                            'doctor_id' => $this->doctor->id,
                            'specialization_id' => $this->specialization->id
                        ]
                    ]
                ]
            ],
            'prise_en_charge_date' => now()->toDateString(),
            'uploadedFiles' => [$file]
        ];

        $response = $this->postJson('/api/reception/fiche-navette/' . $this->ficheNavette->id . '/convention-prescription', $conventionData);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'items_created',
                'total_amount',
                'items',
                'files_uploaded'
            ]
        ]);

        // Check if file was stored (adjust path based on actual storage logic)
        $this->assertTrue(Storage::disk('public')->exists('uploads/convention-files/' . $file->hashName()));
    }

    /** @test */
    public function it_can_group_items_by_insured_patient()
    {
        $insuredPatient = Patient::factory()->create([
            'Firstname' => 'Jane',
            'Lastname' => 'Smith'
        ]);

        // Create items with different insured patients
        ficheNavetteItem::factory()->create([
            'fiche_navette_id' => $this->ficheNavette->id,
            'prestation_id' => $this->prestation->id,
            'insured_id' => $this->patient->id
        ]);

        ficheNavetteItem::factory()->create([
            'fiche_navette_id' => $this->ficheNavette->id,
            'prestation_id' => $this->prestation->id,
            'insured_id' => $insuredPatient->id
        ]);

        $response = $this->getJson('/api/reception/fiche-navette/' . $this->ficheNavette->id . '/grouped-by-insured');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'insured_patient',
                    'prestations_count',
                    'total_amount',
                    'items'
                ]
            ],
            'meta' => [
                'groups_count',
                'total_items'
            ]
        ]);
    }

    /** @test */
    public function it_can_handle_item_with_appointment_relationship()
    {
        $appointment = Appointment::factory()->create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->addDays(1),
            'status' => 'scheduled'
        ]);

        $item = ficheNavetteItem::factory()->create([
            'fiche_navette_id' => $this->ficheNavette->id,
            'prestation_id' => $this->prestation->id,
            'appointment_id' => $appointment->id
        ]);

        $this->assertInstanceOf(Appointment::class, $item->appointment);
        $this->assertEquals($appointment->id, $item->appointment->id);
    }

    /** @test */
    public function it_can_handle_patient_consumption_relationship()
    {
        $item = ficheNavetteItem::factory()->create([
            'fiche_navette_id' => $this->ficheNavette->id,
            'prestation_id' => $this->prestation->id,
            'is_nursing_consumption' => true
        ]);

        $consumption = PatientConsumption::factory()->create([
            'fiche_navette_item_id' => $item->id,
            'patient_id' => $this->patient->id,
            'quantity' => 2,
            'unit_price' => 25.00
        ]);

        $item->refresh();
        
        $this->assertTrue($item->nursingConsumptions->count() > 0);
        $this->assertEquals($consumption->id, $item->nursingConsumptions->first()->id);
    }

    /** @test */
    public function it_validates_numeric_price_fields()
    {
        $response = $this->postJson('/api/reception/fiche-navette/' . $this->ficheNavette->id . '/items', [
            'items' => [[
                'prestation_id' => $this->prestation->id,
                'base_price' => 'invalid_price',
                'final_price' => 'invalid_price'
            ]]
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items.0.base_price', 'items.0.final_price']);
    }

    /** @test */
    public function it_can_handle_discount_application()
    {
        $item = ficheNavetteItem::factory()->create([
            'fiche_navette_id' => $this->ficheNavette->id,
            'prestation_id' => $this->prestation->id,
            'base_price' => 100.00,
            'final_price' => 100.00,
            'convention_id' => $this->convention->id
        ]);

        // Apply 20% discount from convention
        $discountedPrice = $item->base_price * (1 - 0.20);
        $item->update(['final_price' => $discountedPrice]);

        $this->assertEquals(80.00, $item->final_price);
        $this->assertEquals(20.00, $item->base_price - $item->final_price); // Discount amount
    }

    /** @test */
    public function it_can_handle_modality_assignment()
    {
        $modalityType = \App\Models\CONFIGURATION\ModalityType::factory()->create([
            'name' => 'X-Ray Machine',
            'code' => 'XRAY001'
        ]);

        $item = ficheNavetteItem::factory()->create([
            'fiche_navette_id' => $this->ficheNavette->id,
            'prestation_id' => $this->prestation->id,
            'modality_id' => $modalityType->id
        ]);

        $this->assertEquals($modalityType->id, $item->modality_id);
    }

    /** @test */
    public function it_handles_database_transaction_rollback_on_error()
    {
        $initialCount = ficheNavetteItem::count();

        try {
            DB::transaction(function () {
                ficheNavetteItem::create([
                    'fiche_navette_id' => $this->ficheNavette->id,
                    'prestation_id' => $this->prestation->id,
                    'status' => 'pending'
                ]);

                // Force an error
                throw new \Exception('Simulated error');
            });
        } catch (\Exception $e) {
            // Expected exception
        }

        $this->assertEquals($initialCount, ficheNavetteItem::count());
    }

    /** @test */
    public function it_can_search_items_by_prestation_name()
    {
        ficheNavetteItem::factory()->create([
            'fiche_navette_id' => $this->ficheNavette->id,
            'prestation_id' => $this->prestation->id
        ]);

        $searchablePrestation = Prestation::factory()->create([
            'name' => 'Unique Test Name',
            'service_id' => $this->service->id,
            'specialization_id' => $this->specialization->id
        ]);

        ficheNavetteItem::factory()->create([
            'fiche_navette_id' => $this->ficheNavette->id,
            'prestation_id' => $searchablePrestation->id
        ]);

        $items = ficheNavetteItem::whereHas('prestation', function ($query) {
            $query->where('name', 'like', '%Unique Test%');
        })->get();

        $this->assertCount(1, $items);
        $this->assertEquals($searchablePrestation->id, $items->first()->prestation_id);
    }

    /** @test */
    public function it_can_filter_items_by_status()
    {
        ficheNavetteItem::factory()->create([
            'fiche_navette_id' => $this->ficheNavette->id,
            'prestation_id' => $this->prestation->id,
            'status' => 'pending'
        ]);

        ficheNavetteItem::factory()->create([
            'fiche_navette_id' => $this->ficheNavette->id,
            'prestation_id' => $this->prestation->id,
            'status' => 'completed'
        ]);

        $pendingItems = ficheNavetteItem::where('status', 'pending')->get();
        $completedItems = ficheNavetteItem::where('status', 'completed')->get();

        $this->assertCount(1, $pendingItems);
        $this->assertCount(1, $completedItems);
        $this->assertEquals('pending', $pendingItems->first()->status);
        $this->assertEquals('completed', $completedItems->first()->status);
    }

    /** @test */
    public function it_can_calculate_total_amount_for_fiche_navette()
    {
        ficheNavetteItem::factory()->create([
            'fiche_navette_id' => $this->ficheNavette->id,
            'prestation_id' => $this->prestation->id,
            'final_price' => 100.00
        ]);

        ficheNavetteItem::factory()->create([
            'fiche_navette_id' => $this->ficheNavette->id,
            'prestation_id' => $this->prestation->id,
            'final_price' => 150.00
        ]);

        $totalAmount = ficheNavetteItem::where('fiche_navette_id', $this->ficheNavette->id)
            ->sum('final_price');

        $this->assertEquals(250.00, $totalAmount);
    }

    /** @test */
    public function it_can_handle_multiple_conventions_per_item()
    {
        $secondConvention = Convention::factory()->create([
            'name' => 'Secondary Convention',
            'organisme_id' => $this->organisme->id
        ]);

        $item = ficheNavetteItem::factory()->create([
            'fiche_navette_id' => $this->ficheNavette->id,
            'prestation_id' => $this->prestation->id,
            'convention_id' => $this->convention->id
        ]);

        // In a real scenario, you might have a pivot table for multiple conventions
        // For now, we test single convention assignment
        $this->assertEquals($this->convention->id, $item->convention_id);
        
        // Update to second convention
        $item->update(['convention_id' => $secondConvention->id]);
        $this->assertEquals($secondConvention->id, $item->convention_id);
    }

    /** @test */
    public function it_can_handle_item_notes_and_descriptions()
    {
        $item = ficheNavetteItem::factory()->create([
            'fiche_navette_id' => $this->ficheNavette->id,
            'prestation_id' => $this->prestation->id,
            'notes' => 'Patient requires special attention',
            'description' => 'Detailed description of the procedure'
        ]);

        $this->assertEquals('Patient requires special attention', $item->notes);
        $this->assertEquals('Detailed description of the procedure', $item->description);
    }

    /** @test */
    public function it_can_handle_share_distribution_fields()
    {
        $item = ficheNavetteItem::factory()->create([
            'fiche_navette_id' => $this->ficheNavette->id,
            'prestation_id' => $this->prestation->id,
            'patient_share' => 40.00,
            'organisme_share' => 60.00,
            'remaining_amount' => 100.00,
            'paid_amount' => 40.00
        ]);

        $this->assertEquals(40.00, $item->patient_share);
        $this->assertEquals(60.00, $item->organisme_share);
        $this->assertEquals(100.00, $item->remaining_amount);
        $this->assertEquals(40.00, $item->paid_amount);
        
        // Verify patient and organisme shares equal 100%
        $totalShares = $item->patient_share + $item->organisme_share;
        $this->assertEquals(100.00, $totalShares);
    }
}