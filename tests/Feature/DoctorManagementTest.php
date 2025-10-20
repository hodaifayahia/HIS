<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\User;
use App\Models\Specialization;
use App\Models\Schedule;
use App\Models\AppointmentForcer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DoctorManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected $specialization;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user for authentication
        $this->user = User::factory()->create();
        
        // Create a test specialization
        $this->specialization = Specialization::factory()->create([
            'name' => 'Cardiology',
            'description' => 'Heart specialist'
        ]);
        
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_create_doctor_with_valid_data()
    {
        Storage::fake('public');
        $photo = UploadedFile::fake()->image('doctor.jpg');

        $doctorData = [
            'name' => 'Dr. John Smith',
            'email' => 'john.smith@hospital.com',
            'phone' => '+1234567890',
            'password' => 'SecurePassword123!',
            'is_active' => true,
            'allowed_appointment_today' => true,
            'start_time_force' => '08:00',
            'end_time_force' => '17:00',
            'number_of_patients' => 20,
            'include_time' => 30,
            'specialization' => $this->specialization->id,
            'frequency' => 'Weekly',
            'patients_based_on_time' => true,
            'time_slot' => 15,
            'photo' => $photo,
            'schedules' => [
                [
                    'day_of_week' => 1, // Monday
                    'start_time' => '08:00',
                    'end_time' => '12:00',
                    'number_of_patients_per_day' => 10
                ],
                [
                    'day_of_week' => 2, // Tuesday
                    'start_time' => '14:00',
                    'end_time' => '18:00',
                    'number_of_patients_per_day' => 10
                ]
            ]
        ];

        $response = $this->postJson('/api/doctors', $doctorData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'name',
                        'email',
                        'phone',
                        'is_active',
                        'specialization',
                        'schedules'
                    ]
                ]);

        // Verify doctor was created in database
        $this->assertDatabaseHas('doctors', [
            'specialization_id' => $this->specialization->id,
            'frequency' => 'Weekly',
            'patients_based_on_time' => true,
            'time_slot' => 15
        ]);

        // Verify user was created
        $this->assertDatabaseHas('users', [
            'name' => 'Dr. John Smith',
            'email' => 'john.smith@hospital.com',
            'phone' => '+1234567890'
        ]);

        // Verify schedules were created
        $this->assertDatabaseHas('schedules', [
            'day_of_week' => 1,
            'start_time' => '08:00:00',
            'end_time' => '12:00:00'
        ]);

        // Verify appointment forcer was created
        $this->assertDatabaseHas('appointment_forcers', [
            'start_time_force' => '08:00:00',
            'end_time_force' => '17:00:00',
            'number_of_patients' => 20
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_doctor()
    {
        $response = $this->postJson('/api/doctors', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'name',
                    'email',
                    'phone',
                    'password',
                    'specialization'
                ]);
    }

    /** @test */
    public function it_validates_email_format_and_uniqueness()
    {
        // Create existing user with email
        User::factory()->create(['email' => 'existing@hospital.com']);

        $doctorData = [
            'name' => 'Dr. Jane Doe',
            'email' => 'invalid-email',
            'phone' => '+1234567890',
            'password' => 'SecurePassword123!',
            'specialization' => $this->specialization->id
        ];

        $response = $this->postJson('/api/doctors', $doctorData);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);

        // Test email uniqueness
        $doctorData['email'] = 'existing@hospital.com';
        $response = $this->postJson('/api/doctors', $doctorData);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_validates_phone_format()
    {
        $doctorData = [
            'name' => 'Dr. Jane Doe',
            'email' => 'jane.doe@hospital.com',
            'phone' => 'invalid-phone',
            'password' => 'SecurePassword123!',
            'specialization' => $this->specialization->id
        ];

        $response = $this->postJson('/api/doctors', $doctorData);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['phone']);
    }

    /** @test */
    public function it_validates_password_strength()
    {
        $doctorData = [
            'name' => 'Dr. Jane Doe',
            'email' => 'jane.doe@hospital.com',
            'phone' => '+1234567890',
            'password' => 'weak',
            'specialization' => $this->specialization->id
        ];

        $response = $this->postJson('/api/doctors', $doctorData);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function it_validates_specialization_exists()
    {
        $doctorData = [
            'name' => 'Dr. Jane Doe',
            'email' => 'jane.doe@hospital.com',
            'phone' => '+1234567890',
            'password' => 'SecurePassword123!',
            'specialization' => 999999 // Non-existent specialization
        ];

        $response = $this->postJson('/api/doctors', $doctorData);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['specialization']);
    }

    /** @test */
    public function it_validates_frequency_enum_values()
    {
        $doctorData = [
            'name' => 'Dr. Jane Doe',
            'email' => 'jane.doe@hospital.com',
            'phone' => '+1234567890',
            'password' => 'SecurePassword123!',
            'specialization' => $this->specialization->id,
            'frequency' => 'InvalidFrequency'
        ];

        $response = $this->postJson('/api/doctors', $doctorData);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['frequency']);
    }

    /** @test */
    public function it_validates_time_format_in_schedules()
    {
        $doctorData = [
            'name' => 'Dr. Jane Doe',
            'email' => 'jane.doe@hospital.com',
            'phone' => '+1234567890',
            'password' => 'SecurePassword123!',
            'specialization' => $this->specialization->id,
            'schedules' => [
                [
                    'day_of_week' => 1,
                    'start_time' => 'invalid-time',
                    'end_time' => '12:00',
                    'number_of_patients_per_day' => 10
                ]
            ]
        ];

        $response = $this->postJson('/api/doctors', $doctorData);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['schedules.0.start_time']);
    }

    /** @test */
    public function it_validates_day_of_week_range_in_schedules()
    {
        $doctorData = [
            'name' => 'Dr. Jane Doe',
            'email' => 'jane.doe@hospital.com',
            'phone' => '+1234567890',
            'password' => 'SecurePassword123!',
            'specialization' => $this->specialization->id,
            'schedules' => [
                [
                    'day_of_week' => 8, // Invalid day (should be 0-6)
                    'start_time' => '08:00',
                    'end_time' => '12:00',
                    'number_of_patients_per_day' => 10
                ]
            ]
        ];

        $response = $this->postJson('/api/doctors', $doctorData);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['schedules.0.day_of_week']);
    }

    /** @test */
    public function it_validates_logical_time_order_in_schedules()
    {
        $doctorData = [
            'name' => 'Dr. Jane Doe',
            'email' => 'jane.doe@hospital.com',
            'phone' => '+1234567890',
            'password' => 'SecurePassword123!',
            'specialization' => $this->specialization->id,
            'schedules' => [
                [
                    'day_of_week' => 1,
                    'start_time' => '18:00',
                    'end_time' => '08:00', // End time before start time
                    'number_of_patients_per_day' => 10
                ]
            ]
        ];

        $response = $this->postJson('/api/doctors', $doctorData);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['schedules.0.end_time']);
    }

    /** @test */
    public function it_validates_positive_number_of_patients()
    {
        $doctorData = [
            'name' => 'Dr. Jane Doe',
            'email' => 'jane.doe@hospital.com',
            'phone' => '+1234567890',
            'password' => 'SecurePassword123!',
            'specialization' => $this->specialization->id,
            'number_of_patients' => -5 // Negative number
        ];

        $response = $this->postJson('/api/doctors', $doctorData);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['number_of_patients']);
    }

    /** @test */
    public function it_validates_time_slot_range()
    {
        $doctorData = [
            'name' => 'Dr. Jane Doe',
            'email' => 'jane.doe@hospital.com',
            'phone' => '+1234567890',
            'password' => 'SecurePassword123!',
            'specialization' => $this->specialization->id,
            'time_slot' => 0 // Invalid time slot (should be positive)
        ];

        $response = $this->postJson('/api/doctors', $doctorData);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['time_slot']);
    }

    /** @test */
    public function it_can_create_doctor_with_custom_frequency_and_dates()
    {
        $doctorData = [
            'name' => 'Dr. Custom Schedule',
            'email' => 'custom@hospital.com',
            'phone' => '+1234567890',
            'password' => 'SecurePassword123!',
            'specialization' => $this->specialization->id,
            'frequency' => 'Custom',
            'customDates' => [
                [
                    'date' => '2024-02-15',
                    'start_time' => '09:00',
                    'end_time' => '17:00',
                    'number_of_patients_per_day' => 15
                ],
                [
                    'date' => '2024-02-16',
                    'start_time' => '10:00',
                    'end_time' => '16:00',
                    'number_of_patients_per_day' => 12
                ]
            ]
        ];

        $response = $this->postJson('/api/doctors', $doctorData);

        $response->assertStatus(201);

        // Verify custom schedules were created
        $this->assertDatabaseHas('schedules', [
            'date' => '2024-02-15',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'number_of_patients_per_day' => 15
        ]);
    }

    /** @test */
    public function it_handles_photo_upload_validation()
    {
        Storage::fake('public');

        // Test with invalid file type
        $invalidFile = UploadedFile::fake()->create('document.pdf', 1000);

        $doctorData = [
            'name' => 'Dr. Photo Test',
            'email' => 'photo@hospital.com',
            'phone' => '+1234567890',
            'password' => 'SecurePassword123!',
            'specialization' => $this->specialization->id,
            'photo' => $invalidFile
        ];

        $response = $this->postJson('/api/doctors', $doctorData);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['photo']);

        // Test with oversized file
        $oversizedFile = UploadedFile::fake()->image('large.jpg')->size(10000); // 10MB

        $doctorData['photo'] = $oversizedFile;
        $response = $this->postJson('/api/doctors', $doctorData);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['photo']);
    }

    /** @test */
    public function it_can_retrieve_doctor_list()
    {
        // Create multiple doctors
        $doctors = Doctor::factory()->count(3)->create([
            'specialization_id' => $this->specialization->id
        ]);

        $response = $this->getJson('/api/doctors');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'email',
                            'specialization',
                            'is_active'
                        ]
                    ]
                ]);
    }

    /** @test */
    public function it_can_retrieve_specific_doctor()
    {
        $doctor = Doctor::factory()->create([
            'specialization_id' => $this->specialization->id
        ]);

        $response = $this->getJson("/api/doctors/{$doctor->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'name',
                        'email',
                        'phone',
                        'specialization',
                        'schedules'
                    ]
                ]);
    }

    /** @test */
    public function it_returns_404_for_non_existent_doctor()
    {
        $response = $this->getJson('/api/doctors/999999');
        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_update_doctor_information()
    {
        $doctor = Doctor::factory()->create([
            'specialization_id' => $this->specialization->id
        ]);

        $updateData = [
            'name' => 'Dr. Updated Name',
            'allowed_appointment_today' => false,
            'number_of_patients' => 25
        ];

        $response = $this->putJson("/api/doctors/{$doctor->id}", $updateData);

        $response->assertStatus(200);

        // Verify updates in database
        $this->assertDatabaseHas('users', [
            'id' => $doctor->user_id,
            'name' => 'Dr. Updated Name'
        ]);

        $this->assertDatabaseHas('doctors', [
            'id' => $doctor->id,
            'allowed_appointment_today' => false
        ]);
    }

    /** @test */
    public function it_can_soft_delete_doctor()
    {
        $doctor = Doctor::factory()->create([
            'specialization_id' => $this->specialization->id
        ]);

        $response = $this->deleteJson("/api/doctors/{$doctor->id}");

        $response->assertStatus(200);

        // Verify soft deletion
        $this->assertSoftDeleted('doctors', [
            'id' => $doctor->id
        ]);
    }

    /** @test */
    public function it_can_search_doctors_by_name()
    {
        $doctor1 = Doctor::factory()->create([
            'specialization_id' => $this->specialization->id
        ]);
        $doctor1->user->update(['name' => 'Dr. John Cardiology']);

        $doctor2 = Doctor::factory()->create([
            'specialization_id' => $this->specialization->id
        ]);
        $doctor2->user->update(['name' => 'Dr. Jane Neurology']);

        $response = $this->getJson('/api/doctors/search?query=John');

        $response->assertStatus(200)
                ->assertJsonCount(1, 'data')
                ->assertJsonFragment(['name' => 'Dr. John Cardiology']);
    }

    /** @test */
    public function it_validates_schedule_conflicts_for_same_doctor()
    {
        $doctorData = [
            'name' => 'Dr. Conflict Test',
            'email' => 'conflict@hospital.com',
            'phone' => '+1234567890',
            'password' => 'SecurePassword123!',
            'specialization' => $this->specialization->id,
            'schedules' => [
                [
                    'day_of_week' => 1,
                    'start_time' => '08:00',
                    'end_time' => '12:00',
                    'number_of_patients_per_day' => 10
                ],
                [
                    'day_of_week' => 1, // Same day
                    'start_time' => '10:00', // Overlapping time
                    'end_time' => '14:00',
                    'number_of_patients_per_day' => 8
                ]
            ]
        ];

        $response = $this->postJson('/api/doctors', $doctorData);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['schedules']);
    }

    /** @test */
    public function it_handles_database_transaction_rollback_on_error()
    {
        // Mock a scenario where user creation succeeds but doctor creation fails
        $doctorData = [
            'name' => 'Dr. Transaction Test',
            'email' => 'transaction@hospital.com',
            'phone' => '+1234567890',
            'password' => 'SecurePassword123!',
            'specialization' => 999999, // Invalid specialization to cause failure
        ];

        $response = $this->postJson('/api/doctors', $doctorData);
        $response->assertStatus(422);

        // Verify no user was created due to transaction rollback
        $this->assertDatabaseMissing('users', [
            'email' => 'transaction@hospital.com'
        ]);
    }

    /** @test */
    public function it_can_bulk_delete_doctors()
    {
        $doctors = Doctor::factory()->count(3)->create([
            'specialization_id' => $this->specialization->id
        ]);

        $doctorIds = $doctors->pluck('id')->toArray();

        $response = $this->deleteJson('/api/doctors/bulk-delete', [
            'ids' => $doctorIds
        ]);

        $response->assertStatus(200);

        // Verify all doctors were soft deleted
        foreach ($doctorIds as $id) {
            $this->assertSoftDeleted('doctors', ['id' => $id]);
        }
    }

    /** @test */
    public function it_validates_appointment_booking_window()
    {
        $doctorData = [
            'name' => 'Dr. Booking Window Test',
            'email' => 'booking@hospital.com',
            'phone' => '+1234567890',
            'password' => 'SecurePassword123!',
            'specialization' => $this->specialization->id,
            'appointment_booking_window' => -1 // Invalid negative value
        ];

        $response = $this->postJson('/api/doctors', $doctorData);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['appointment_booking_window']);
    }
}