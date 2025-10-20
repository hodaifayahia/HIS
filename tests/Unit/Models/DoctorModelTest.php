<?php

namespace Tests\Unit\Models;

use App\Models\Doctor;
use App\Models\User;
use App\Models\Specialization;
use App\Models\CONFIGURATION\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DoctorModelTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    /** @test */
    public function it_can_create_a_doctor()
    {
        $user = User::factory()->create();
        $specialization = Specialization::factory()->create();
        $service = Service::factory()->create();

        $doctorData = [
            'user_id' => $user->id,
            'specialization_id' => $specialization->id,
            'service_id' => $service->id,
            'professional_name' => 'Dr. John Smith',
            'phone' => '+1234567890',
            'email' => 'john.smith@hospital.com',
            'notes' => 'Experienced cardiologist with 15 years of practice',
            'is_active' => true,
        ];

        $doctor = Doctor::create($doctorData);

        $this->assertInstanceOf(Doctor::class, $doctor);
        $this->assertEquals($doctorData['professional_name'], $doctor->professional_name);
        $this->assertEquals($doctorData['email'], $doctor->email);
        $this->assertTrue($doctor->is_active);
        $this->assertDatabaseHas('doctors', $doctorData);
    }

    /** @test */
    public function it_belongs_to_a_user()
    {
        $user = User::factory()->create();
        $doctor = Doctor::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $doctor->user);
        $this->assertEquals($user->id, $doctor->user->id);
    }

    /** @test */
    public function it_belongs_to_a_specialization()
    {
        $specialization = Specialization::factory()->create();
        $doctor = Doctor::factory()->create(['specialization_id' => $specialization->id]);

        $this->assertInstanceOf(Specialization::class, $doctor->specialization);
        $this->assertEquals($specialization->id, $doctor->specialization->id);
    }

    /** @test */
    public function it_belongs_to_a_service()
    {
        $service = Service::factory()->create();
        $doctor = Doctor::factory()->create(['service_id' => $service->id]);

        $this->assertInstanceOf(Service::class, $doctor->service);
        $this->assertEquals($service->id, $doctor->service->id);
    }

    /** @test */
    public function it_can_update_doctor_information()
    {
        $doctor = Doctor::factory()->create();
        
        $updatedData = [
            'professional_name' => 'Dr. Jane Doe',
            'phone' => '+9876543210',
            'notes' => 'Updated medical notes',
        ];

        $doctor->update($updatedData);

        $this->assertEquals($updatedData['professional_name'], $doctor->professional_name);
        $this->assertEquals($updatedData['phone'], $doctor->phone);
        $this->assertEquals($updatedData['notes'], $doctor->notes);
        $this->assertDatabaseHas('doctors', $updatedData);
    }

    /** @test */
    public function it_can_delete_a_doctor()
    {
        $doctor = Doctor::factory()->create();
        $doctorId = $doctor->id;

        $doctor->delete();

        $this->assertDatabaseMissing('doctors', ['id' => $doctorId]);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        // Attempt to create doctor without required fields
        Doctor::create([]);
    }

    /** @test */
    public function it_can_scope_active_doctors()
    {
        Doctor::factory()->create(['is_active' => true]);
        Doctor::factory()->create(['is_active' => false]);
        Doctor::factory()->create(['is_active' => true]);

        $activeDoctors = Doctor::where('is_active', true)->get();
        
        $this->assertCount(2, $activeDoctors);
        $activeDoctors->each(function ($doctor) {
            $this->assertTrue($doctor->is_active);
        });
    }

    /** @test */
    public function it_uses_english_terminology_in_factory()
    {
        $doctor = Doctor::factory()->create();

        // Verify English professional names are used
        $englishTitles = ['Dr.', 'Prof.', 'Specialist'];
        $hasEnglishTitle = false;
        
        foreach ($englishTitles as $title) {
            if (str_contains($doctor->professional_name, $title)) {
                $hasEnglishTitle = true;
                break;
            }
        }
        
        $this->assertTrue($hasEnglishTitle, 'Doctor should have English professional title');
        
        // Ensure no French terms are present
        $this->assertStringNotContainsString('Docteur', $doctor->professional_name);
        $this->assertStringNotContainsString('Professeur', $doctor->professional_name);
        $this->assertStringNotContainsString('Spécialiste', $doctor->professional_name);
    }

    /** @test */
    public function it_maintains_data_integrity_with_foreign_keys()
    {
        $user = User::factory()->create();
        $specialization = Specialization::factory()->create();
        $service = Service::factory()->create();
        
        $doctor = Doctor::factory()->create([
            'user_id' => $user->id,
            'specialization_id' => $specialization->id,
            'service_id' => $service->id,
        ]);

        // Test that foreign key relationships are maintained
        $this->assertEquals($user->id, $doctor->user_id);
        $this->assertEquals($specialization->id, $doctor->specialization_id);
        $this->assertEquals($service->id, $doctor->service_id);
        
        // Test cascade behavior (if implemented)
        $user->delete();
        $doctor->refresh();
        
        // Depending on your foreign key constraints, adjust this assertion
        $this->assertNull($doctor->user);
    }
}