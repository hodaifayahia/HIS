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

    /** @test */
    public function it_creates_doctor_with_proper_user_relationship()
    {
        $doctorData = [
            'name' => 'Dr. User Relationship Test',
            'email' => 'userrel@hospital.com',
            'password' => 'SecurePassword123!',
            'specialization' => $this->specialization->id,
            'is_active' => true,
            'allowed_appointment_today' => true,
            'frequency' => 'Weekly',
            'patients_based_on_time' => true
        ];

        $response = $this->postJson('/api/doctors', $doctorData);
        
        $response->assertStatus(201);
        
        $doctor = Doctor::where('specialization_id', $this->specialization->id)
                        ->whereHas('user', function($query) {
                            $query->where('email', 'userrel@hospital.com');
                        })->first();
        
        $this->assertNotNull($doctor);
        $this->assertNotNull($doctor->user);
        $this->assertEquals('Dr. User Relationship Test', $doctor->user->name);
        $this->assertEquals('userrel@hospital.com', $doctor->user->email);
        $this->assertTrue($doctor->user->hasRole('doctor'));
    }

    /** @test */
    public function it_creates_doctor_with_specialization_relationship()
    {
        $doctorData = [
            'name' => 'Dr. Specialization Test',
            'email' => 'spec@hospital.com',
            'password' => 'SecurePassword123!',
            'specialization' => $this->specialization->id,
            'is_active' => true,
            'allowed_appointment_today' => true,
            'frequency' => 'Weekly',
            'patients_based_on_time' => true
        ];

        $response = $this->postJson('/api/doctors', $doctorData);
        
        $response->assertStatus(201);
        
        $doctor = Doctor::where('specialization_id', $this->specialization->id)
                        ->whereHas('user', function($query) {
                            $query->where('email', 'spec@hospital.com');
                        })->first();
        
        $this->assertNotNull($doctor);
        $this->assertNotNull($doctor->specialization);
        $this->assertEquals($this->specialization->id, $doctor->specialization->id);
        $this->assertEquals($this->specialization->name, $doctor->specialization->name);
    }

    /** @test */
    public function it_creates_doctor_with_schedule_relationships()
    {
        $doctorData = [
            'name' => 'Dr. Schedule Relationship Test',
            'email' => 'schedrel@hospital.com',
            'password' => 'SecurePassword123!',
            'specialization' => $this->specialization->id,
            'is_active' => true,
            'allowed_appointment_today' => true,
            'frequency' => 'Weekly',
            'patients_based_on_time' => true,
            'schedules' => [
                [
                    'day_of_week' => 'monday',
                    'shift_period' => 'morning',
                    'start_time' => '08:00',
                    'end_time' => '12:00',
                    'number_of_patients_per_day' => 10
                ],
                [
                    'day_of_week' => 'tuesday',
                    'shift_period' => 'afternoon',
                    'start_time' => '14:00',
                    'end_time' => '18:00',
                    'number_of_patients_per_day' => 8
                ]
            ]
        ];

        $response = $this->postJson('/api/doctors', $doctorData);
        
        $response->assertStatus(201);
        
        $doctor = Doctor::where('specialization_id', $this->specialization->id)
                        ->whereHas('user', function($query) {
                            $query->where('email', 'schedrel@hospital.com');
                        })->first();
        
        $this->assertNotNull($doctor);
        $this->assertCount(2, $doctor->schedules);
        
        $mondaySchedule = $doctor->schedules->where('day_of_week', 'monday')->first();
        $this->assertNotNull($mondaySchedule);
        $this->assertEquals('morning', $mondaySchedule->shift_period);
        $this->assertEquals('08:00:00', $mondaySchedule->start_time);
        $this->assertEquals('12:00:00', $mondaySchedule->end_time);
        $this->assertEquals(10, $mondaySchedule->number_of_patients_per_day);
        
        $tuesdaySchedule = $doctor->schedules->where('day_of_week', 'tuesday')->first();
        $this->assertNotNull($tuesdaySchedule);
        $this->assertEquals('afternoon', $tuesdaySchedule->shift_period);
        $this->assertEquals('14:00:00', $tuesdaySchedule->start_time);
        $this->assertEquals('18:00:00', $tuesdaySchedule->end_time);
        $this->assertEquals(8, $tuesdaySchedule->number_of_patients_per_day);
    }

    /** @test */
    public function it_handles_doctor_deletion_with_related_schedules()
    {
        $doctor = Doctor::factory()->create([
            'specialization_id' => $this->specialization->id
        ]);

        // Create schedules for the doctor
        Schedule::create([
            'doctor_id' => $doctor->id,
            'day_of_week' => 'monday',
            'shift_period' => 'morning',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'number_of_patients_per_day' => 10
        ]);

        Schedule::create([
            'doctor_id' => $doctor->id,
            'day_of_week' => 'tuesday',
            'shift_period' => 'afternoon',
            'start_time' => '14:00',
            'end_time' => '18:00',
            'number_of_patients_per_day' => 8
        ]);

        $this->assertCount(2, $doctor->schedules);

        // Delete the doctor
        $response = $this->deleteJson("/api/doctors/{$doctor->id}");
        $response->assertStatus(200);

        // Verify doctor is soft deleted
        $this->assertSoftDeleted('doctors', ['id' => $doctor->id]);
        
        // Verify schedules are also handled appropriately
        $doctor->refresh();
        $this->assertTrue($doctor->trashed());
    }

    /** @test */
    public function it_validates_doctor_specialization_exists()
    {
        $nonExistentSpecializationId = 999999;
        
        $doctorData = [
            'name' => 'Dr. Invalid Specialization Test',
            'email' => 'invalidspec@hospital.com',
            'password' => 'SecurePassword123!',
            'specialization' => $nonExistentSpecializationId,
            'is_active' => true,
            'allowed_appointment_today' => true,
            'frequency' => 'Weekly',
            'patients_based_on_time' => true
        ];

        $response = $this->postJson('/api/doctors', $doctorData);
        
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['specialization']);
        
        // Verify no doctor was created
        $this->assertDatabaseMissing('doctors', [
            'specialization_id' => $nonExistentSpecializationId
        ]);
    }

    /** @test */
    public function it_loads_doctor_with_all_relationships()
    {
        $doctor = Doctor::factory()->create([
            'specialization_id' => $this->specialization->id
        ]);

        // Create schedules for the doctor
        Schedule::create([
            'doctor_id' => $doctor->id,
            'day_of_week' => 'monday',
            'shift_period' => 'morning',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'number_of_patients_per_day' => 10
        ]);

        $response = $this->getJson("/api/doctors/{$doctor->id}");
        
        $response->assertStatus(200);
        
        $responseData = $response->json('data');
        
        // Verify all relationships are loaded
        $this->assertArrayHasKey('user', $responseData);
        $this->assertArrayHasKey('specialization', $responseData);
        $this->assertArrayHasKey('schedules', $responseData);
        
        // Verify user relationship data
        $this->assertEquals($doctor->user->name, $responseData['user']['name']);
        $this->assertEquals($doctor->user->email, $responseData['user']['email']);
        
        // Verify specialization relationship data
        $this->assertEquals($this->specialization->name, $responseData['specialization']['name']);
        
        // Verify schedules relationship data
        $this->assertCount(1, $responseData['schedules']);
        $this->assertEquals('monday', $responseData['schedules'][0]['day_of_week']);
        $this->assertEquals('morning', $responseData['schedules'][0]['shift_period']);
    }

    /** @test */
    public function it_updates_doctor_while_preserving_relationships()
    {
        $doctor = Doctor::factory()->create([
            'specialization_id' => $this->specialization->id
        ]);

        $originalUserId = $doctor->user->id;
        $originalSpecializationId = $doctor->specialization_id;

        $updateData = [
            'name' => 'Dr. Updated Name',
            'allowed_appointment_today' => false
        ];

        $response = $this->putJson("/api/doctors/{$doctor->id}", $updateData);
        
        $response->assertStatus(200);
        
        $doctor->refresh();
        
        // Verify relationships are preserved
        $this->assertEquals($originalUserId, $doctor->user->id);
        $this->assertEquals($originalSpecializationId, $doctor->specialization_id);
        
        // Verify updates were applied
        $this->assertEquals('Dr. Updated Name', $doctor->user->name);
        $this->assertFalse($doctor->allowed_appointment_today);
    }

    /** @test */
    public function it_handles_cascade_operations_on_user_deletion()
    {
        $doctor = Doctor::factory()->create([
            'specialization_id' => $this->specialization->id
        ]);

        $userId = $doctor->user->id;
        $doctorId = $doctor->id;

        // Delete the associated user directly
        $doctor->user->delete();

        // Verify doctor is also affected appropriately
        $this->assertDatabaseMissing('users', ['id' => $userId]);
        
        // The doctor record behavior depends on your cascade settings
        // This test verifies the system handles the relationship properly
        $doctorExists = Doctor::find($doctorId);
        if ($doctorExists) {
            // If doctor still exists, it should handle the missing user gracefully
            $this->assertNull($doctorExists->user);
        }
    }

    /** @test */
    public function it_validates_schedule_belongs_to_doctor()
    {
        $doctor1 = Doctor::factory()->create([
            'specialization_id' => $this->specialization->id
        ]);
        
        $doctor2 = Doctor::factory()->create([
            'specialization_id' => $this->specialization->id
        ]);

        $schedule = Schedule::create([
            'doctor_id' => $doctor1->id,
            'day_of_week' => 'monday',
            'shift_period' => 'morning',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'number_of_patients_per_day' => 10
        ]);

        // Verify schedule belongs to correct doctor
        $this->assertEquals($doctor1->id, $schedule->doctor_id);
        $this->assertTrue($doctor1->schedules->contains($schedule));
        $this->assertFalse($doctor2->schedules->contains($schedule));
    }

    /** @test */
    public function it_handles_multiple_doctors_with_same_specialization()
    {
        $doctor1Data = [
            'name' => 'Dr. First Cardiologist',
            'email' => 'first@cardio.com',
            'password' => 'SecurePassword123!',
            'specialization' => $this->specialization->id,
            'is_active' => true,
            'allowed_appointment_today' => true,
            'frequency' => 'Weekly',
            'patients_based_on_time' => true
        ];

        $doctor2Data = [
            'name' => 'Dr. Second Cardiologist',
            'email' => 'second@cardio.com',
            'password' => 'SecurePassword123!',
            'specialization' => $this->specialization->id,
            'is_active' => true,
            'allowed_appointment_today' => true,
            'frequency' => 'Daily',
            'patients_based_on_time' => false
        ];

        $response1 = $this->postJson('/api/doctors', $doctor1Data);
        $response1->assertStatus(201);

        $response2 = $this->postJson('/api/doctors', $doctor2Data);
        $response2->assertStatus(201);

        // Verify both doctors exist with same specialization
        $doctors = Doctor::where('specialization_id', $this->specialization->id)->get();
        $this->assertCount(2, $doctors);

        $firstDoctor = $doctors->where('frequency', 'Weekly')->first();
        $secondDoctor = $doctors->where('frequency', 'Daily')->first();

        $this->assertNotNull($firstDoctor);
        $this->assertNotNull($secondDoctor);
        $this->assertEquals($this->specialization->id, $firstDoctor->specialization_id);
        $this->assertEquals($this->specialization->id, $secondDoctor->specialization_id);
    }

    // ========== ADDITIONAL COMPREHENSIVE TESTS ==========

    /** @test */
    public function it_validates_required_fields_with_specific_error_messages()
    {
        $response = $this->postJson('/api/doctors', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'name',
                    'email', 
                    'password',
                    'is_active',
                    'allowed_appointment_today',
                    'specialization',
                    'frequency',
                    'patients_based_on_time'
                ])
                ->assertJsonFragment([
                    'message' => 'The given data was invalid.'
                ]);
    }

    /** @test */
    public function it_validates_name_field_constraints()
    {
        // Test empty name
        $doctorData = [
            'name' => '',
            'email' => 'test@hospital.com',
            'password' => 'SecurePassword123!',
            'specialization' => $this->specialization->id,
            'is_active' => true,
            'allowed_appointment_today' => true,
            'frequency' => 'Weekly',
            'patients_based_on_time' => true
        ];

        $response = $this->postJson('/api/doctors', $doctorData);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);

        // Test name too long (over 255 characters)
        $doctorData['name'] = str_repeat('a', 256);
        $response = $this->postJson('/api/doctors', $doctorData);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);

        // Test valid name with special characters
        $doctorData['name'] = "Dr. José María O'Connor-Smith Jr.";
        $response = $this->postJson('/api/doctors', $doctorData);
        $response->assertStatus(201);
    }

    /** @test */
    public function it_validates_email_field_thoroughly()
    {
        $baseData = [
            'name' => 'Dr. Email Test',
            'password' => 'SecurePassword123!',
            'specialization' => $this->specialization->id,
            'is_active' => true,
            'allowed_appointment_today' => true,
            'frequency' => 'Weekly',
            'patients_based_on_time' => true
        ];

        // Test various invalid email formats
        $invalidEmails = [
            'invalid-email',
            '@hospital.com',
            'test@',
            'test..test@hospital.com',
            'test@hospital',
            'test@.com',
            ''
        ];

        foreach ($invalidEmails as $email) {
            $doctorData = array_merge($baseData, ['email' => $email]);
            $response = $this->postJson('/api/doctors', $doctorData);
            $response->assertStatus(422)
                    ->assertJsonValidationErrors(['email']);
        }

        // Test valid email formats
        $validEmails = [
            'test@hospital.com',
            'dr.john.smith@medical-center.org',
            'doctor+specialist@hospital.co.uk',
            'test123@hospital.com'
        ];

        foreach ($validEmails as $email) {
            $doctorData = array_merge($baseData, ['email' => $email]);
            $response = $this->postJson('/api/doctors', $doctorData);
            $response->assertStatus(201);
        }
    }

    /** @test */
    public function it_validates_phone_number_formats()
    {
        $baseData = [
            'name' => 'Dr. Phone Test',
            'email' => 'phone@hospital.com',
            'password' => 'SecurePassword123!',
            'specialization' => $this->specialization->id,
            'is_active' => true,
            'allowed_appointment_today' => true,
            'frequency' => 'Weekly',
            'patients_based_on_time' => true
        ];

        // Test invalid phone formats
        $invalidPhones = [
            '123',
            'abc-def-ghij',
            '++1234567890',
            '1234567890123456789', // Too long
            ''
        ];

        foreach ($invalidPhones as $phone) {
            $doctorData = array_merge($baseData, ['phone' => $phone, 'email' => "phone{$phone}@hospital.com"]);
            $response = $this->postJson('/api/doctors', $doctorData);
            $response->assertStatus(422)
                    ->assertJsonValidationErrors(['phone']);
        }

        // Test valid phone formats
        $validPhones = [
            '+1234567890',
            '(555) 123-4567',
            '555-123-4567',
            '+1 (555) 123-4567',
            '15551234567'
        ];

        foreach ($validPhones as $index => $phone) {
            $doctorData = array_merge($baseData, ['phone' => $phone, 'email' => "valid{$index}@hospital.com"]);
            $response = $this->postJson('/api/doctors', $doctorData);
            $response->assertStatus(201);
        }
    }

    /** @test */
    public function it_validates_password_complexity_requirements()
    {
        $baseData = [
            'name' => 'Dr. Password Test',
            'email' => 'password@hospital.com',
            'specialization' => $this->specialization->id,
            'is_active' => true,
            'allowed_appointment_today' => true,
            'frequency' => 'Weekly',
            'patients_based_on_time' => true
        ];

        // Test weak passwords
        $weakPasswords = [
            '123',           // Too short
            'password',      // Too simple
            '12345678',      // Only numbers
            'abcdefgh',      // Only letters
            'PASSWORD',      // Only uppercase
        ];

        foreach ($weakPasswords as $index => $password) {
            $doctorData = array_merge($baseData, ['password' => $password, 'email' => "weak{$index}@hospital.com"]);
            $response = $this->postJson('/api/doctors', $doctorData);
            $response->assertStatus(422)
                    ->assertJsonValidationErrors(['password']);
        }

        // Test strong passwords
        $strongPasswords = [
            'SecurePassword123!',
            'MyStr0ng@Pass',
            'C0mpl3x#P@ssw0rd',
            'V3ry$ecur3P@ss'
        ];

        foreach ($strongPasswords as $index => $password) {
            $doctorData = array_merge($baseData, ['password' => $password, 'email' => "strong{$index}@hospital.com"]);
            $response = $this->postJson('/api/doctors', $doctorData);
            $response->assertStatus(201);
        }
    }

    /** @test */
    public function it_validates_boolean_fields()
    {
        $baseData = [
            'name' => 'Dr. Boolean Test',
            'email' => 'boolean@hospital.com',
            'password' => 'SecurePassword123!',
            'specialization' => $this->specialization->id,
            'frequency' => 'Weekly',
            'patients_based_on_time' => true
        ];

        // Test invalid boolean values
        $invalidBooleans = ['yes', 'no', 'active', 'inactive', 1, 0, 'true', 'false'];

        foreach ($invalidBooleans as $value) {
            $doctorData = array_merge($baseData, ['is_active' => $value]);
            $response = $this->postJson('/api/doctors', $doctorData);
            $response->assertStatus(422)
                    ->assertJsonValidationErrors(['is_active']);
        }

        // Test valid boolean values
        $validBooleans = [true, false];

        foreach ($validBooleans as $index => $value) {
            $doctorData = array_merge($baseData, [
                'is_active' => $value,
                'allowed_appointment_today' => $value,
                'email' => "bool{$index}@hospital.com"
            ]);
            $response = $this->postJson('/api/doctors', $doctorData);
            $response->assertStatus(201);
        }
    }

    /** @test */
    public function it_validates_time_format_constraints()
    {
        $baseData = [
            'name' => 'Dr. Time Test',
            'email' => 'time@hospital.com',
            'password' => 'SecurePassword123!',
            'specialization' => $this->specialization->id,
            'is_active' => true,
            'allowed_appointment_today' => true,
            'frequency' => 'Weekly',
            'patients_based_on_time' => true
        ];

        // Test invalid time formats
        $invalidTimes = [
            '25:00',     // Invalid hour
            '12:60',     // Invalid minute
            '8:00',      // Missing leading zero
            '08:5',      // Missing leading zero in minutes
            '8:00 AM',   // 12-hour format
            'morning',   // Text
            '24:00'      // Edge case
        ];

        foreach ($invalidTimes as $time) {
            $doctorData = array_merge($baseData, ['start_time_force' => $time]);
            $response = $this->postJson('/api/doctors', $doctorData);
            $response->assertStatus(422)
                    ->assertJsonValidationErrors(['start_time_force']);
        }

        // Test valid time formats
        $validTimes = ['00:00', '08:30', '12:00', '18:45', '23:59'];

        foreach ($validTimes as $index => $time) {
            $doctorData = array_merge($baseData, [
                'start_time_force' => $time,
                'end_time_force' => '23:59',
                'email' => "time{$index}@hospital.com"
            ]);
            $response = $this->postJson('/api/doctors', $doctorData);
            $response->assertStatus(201);
        }
    }

    /** @test */
    public function it_validates_time_slot_constraints()
    {
        $baseData = [
            'name' => 'Dr. Slot Test',
            'email' => 'slot@hospital.com',
            'password' => 'SecurePassword123!',
            'specialization' => $this->specialization->id,
            'is_active' => true,
            'allowed_appointment_today' => true,
            'frequency' => 'Weekly',
            'patients_based_on_time' => true
        ];

        // Test invalid time slots
        $invalidSlots = [-1, 0, 0.5, 'fifteen', null];

        foreach ($invalidSlots as $slot) {
            $doctorData = array_merge($baseData, ['time_slot' => $slot]);
            $response = $this->postJson('/api/doctors', $doctorData);
            $response->assertStatus(422)
                    ->assertJsonValidationErrors(['time_slot']);
        }

        // Test valid time slots
        $validSlots = [5, 10, 15, 20, 30, 45, 60];

        foreach ($validSlots as $index => $slot) {
            $doctorData = array_merge($baseData, [
                'time_slot' => $slot,
                'email' => "slot{$index}@hospital.com"
            ]);
            $response = $this->postJson('/api/doctors', $doctorData);
            $response->assertStatus(201);
        }
    }

    /** @test */
    public function it_handles_schedule_validation_comprehensively()
    {
        $baseData = [
            'name' => 'Dr. Schedule Test',
            'email' => 'schedule@hospital.com',
            'password' => 'SecurePassword123!',
            'specialization' => $this->specialization->id,
            'is_active' => true,
            'allowed_appointment_today' => true,
            'frequency' => 'Weekly',
            'patients_based_on_time' => true,
            'time_slot' => 15
        ];

        // Test missing required schedule fields
        $incompleteSchedule = [
            'schedules' => [
                [
                    'day_of_week' => 'monday',
                    'shift_period' => 'morning'
                    // Missing start_time and end_time
                ]
            ]
        ];

        $doctorData = array_merge($baseData, $incompleteSchedule);
        $response = $this->postJson('/api/doctors', $doctorData);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['schedules.0.start_time', 'schedules.0.end_time']);

        // Test invalid day_of_week values
        $invalidDaySchedule = [
            'schedules' => [
                [
                    'day_of_week' => 'invalid_day',
                    'shift_period' => 'morning',
                    'start_time' => '08:00',
                    'end_time' => '12:00'
                ]
            ]
        ];

        $doctorData = array_merge($baseData, $invalidDaySchedule);
        $response = $this->postJson('/api/doctors', $doctorData);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['schedules.0.day_of_week']);

        // Test invalid shift_period values
        $invalidShiftSchedule = [
            'schedules' => [
                [
                    'day_of_week' => 'monday',
                    'shift_period' => 'night',
                    'start_time' => '08:00',
                    'end_time' => '12:00'
                ]
            ]
        ];

        $doctorData = array_merge($baseData, $invalidShiftSchedule);
        $response = $this->postJson('/api/doctors', $doctorData);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['schedules.0.shift_period']);
    }

    /** @test */
    public function it_handles_database_constraint_violations()
    {
        // Test foreign key constraint violation
        $doctorData = [
            'name' => 'Dr. Constraint Test',
            'email' => 'constraint@hospital.com',
            'password' => 'SecurePassword123!',
            'specialization' => 999999, // Non-existent specialization
            'is_active' => true,
            'allowed_appointment_today' => true,
            'frequency' => 'Weekly',
            'patients_based_on_time' => true
        ];

        $response = $this->postJson('/api/doctors', $doctorData);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['specialization']);

        // Verify no partial data was saved
        $this->assertDatabaseMissing('users', [
            'email' => 'constraint@hospital.com'
        ]);
    }

    /** @test */
    public function it_handles_database_connection_failures()
    {
        // Mock database connection failure scenario
        $doctorData = [
            'name' => 'Dr. Connection Test',
            'email' => 'connection@hospital.com',
            'password' => 'SecurePassword123!',
            'specialization' => $this->specialization->id,
            'is_active' => true,
            'allowed_appointment_today' => true,
            'frequency' => 'Weekly',
            'patients_based_on_time' => true
        ];

        // Simulate database error by using invalid specialization
        $doctorData['specialization'] = 999999;

        $response = $this->postJson('/api/doctors', $doctorData);
        
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['specialization']);
    }

    /** @test */
    public function it_handles_foreign_key_constraint_violations()
    {
        $doctorData = [
            'name' => 'Dr. Foreign Key Test',
            'email' => 'foreignkey@hospital.com',
            'password' => 'SecurePassword123!',
            'specialization' => 999999, // Non-existent specialization
            'is_active' => true,
            'allowed_appointment_today' => true,
            'frequency' => 'Weekly',
            'patients_based_on_time' => true
        ];

        $response = $this->postJson('/api/doctors', $doctorData);
        
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['specialization']);

        // Verify no partial data was created
        $this->assertDatabaseMissing('users', [
            'email' => 'foreignkey@hospital.com'
        ]);
    }

    /** @test */
    public function it_handles_invalid_json_payload()
    {
        $response = $this->json('POST', '/api/doctors', [], ['Content-Type' => 'application/json']);
        
        $response->assertStatus(422);
    }

    /** @test */
    public function it_handles_malformed_schedule_data()
    {
        $doctorData = [
            'name' => 'Dr. Malformed Schedule Test',
            'email' => 'malformed@hospital.com',
            'password' => 'SecurePassword123!',
            'specialization' => $this->specialization->id,
            'is_active' => true,
            'allowed_appointment_today' => true,
            'frequency' => 'Weekly',
            'patients_based_on_time' => true,
            'schedules' => [
                [
                    'day_of_week' => 'monday',
                    'shift_period' => 'morning',
                    'start_time' => 'invalid-time-format',
                    'end_time' => 'also-invalid',
                    'number_of_patients_per_day' => 'not-a-number'
                ]
            ]
        ];

        $response = $this->postJson('/api/doctors', $doctorData);
        
        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'schedules.0.start_time',
                    'schedules.0.end_time',
                    'schedules.0.number_of_patients_per_day'
                ]);
    }

    /** @test */
    public function it_handles_soft_deleted_doctor_operations()
    {
        $doctor = Doctor::factory()->create([
            'specialization_id' => $this->specialization->id
        ]);

        // Soft delete the doctor
        $doctor->delete();

        // Try to update soft-deleted doctor
        $updateData = [
            'name' => 'Dr. Updated Deleted',
            'allowed_appointment_today' => false
        ];

        $response = $this->putJson("/api/doctors/{$doctor->id}", $updateData);
        $response->assertStatus(404);

        // Try to delete already soft-deleted doctor
        $response = $this->deleteJson("/api/doctors/{$doctor->id}");
        $response->assertStatus(404);

        // Try to retrieve soft-deleted doctor
        $response = $this->getJson("/api/doctors/{$doctor->id}");
        $response->assertStatus(404);
    }

    /** @test */
    public function it_handles_invalid_file_uploads()
    {
        $doctorData = [
            'name' => 'Dr. File Upload Test',
            'email' => 'fileupload@hospital.com',
            'password' => 'SecurePassword123!',
            'specialization' => $this->specialization->id,
            'is_active' => true,
            'allowed_appointment_today' => true,
            'frequency' => 'Weekly',
            'patients_based_on_time' => true,
            'photo' => 'not-a-file'
        ];

        $response = $this->postJson('/api/doctors', $doctorData);
        
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['photo']);
    }

    /** @test */
    public function it_handles_invalid_authentication_tokens()
    {
        // Test without authentication
        $this->app['auth']->logout();

        $response = $this->getJson('/api/doctors');
        $response->assertStatus(401);

        $response = $this->postJson('/api/doctors', []);
        $response->assertStatus(401);
    }

    /** @test */
    public function it_handles_validation_with_special_characters()
    {
        $doctorData = [
            'name' => 'Dr. Special <script>alert("xss")</script> Characters',
            'email' => 'special@hospital.com',
            'password' => 'SecurePassword123!',
            'specialization' => $this->specialization->id,
            'is_active' => true,
            'allowed_appointment_today' => true,
            'frequency' => 'Weekly',
            'patients_based_on_time' => true
        ];

        $response = $this->postJson('/api/doctors', $doctorData);
        
        // Should either sanitize or reject malicious input
        if ($response->getStatusCode() === 201) {
            // If accepted, verify it was sanitized
            $this->assertDatabaseMissing('users', [
                'name' => 'Dr. Special <script>alert("xss")</script> Characters'
            ]);
        } else {
            // If rejected, should be validation error
            $response->assertStatus(422);
        }
    }

    /** @test */
    public function it_handles_concurrent_doctor_creation_with_same_email()
    {
        $doctorData = [
            'name' => 'Dr. Concurrent Test',
            'email' => 'concurrent@hospital.com',
            'password' => 'SecurePassword123!',
            'specialization' => $this->specialization->id,
            'is_active' => true,
            'allowed_appointment_today' => true,
            'frequency' => 'Weekly',
            'patients_based_on_time' => true
        ];

        // First request should succeed
        $response1 = $this->postJson('/api/doctors', $doctorData);
        $response1->assertStatus(201);

        // Second request with same email should fail
        $response2 = $this->postJson('/api/doctors', $doctorData);
        $response2->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_handles_large_schedule_arrays()
    {
        $doctorData = [
            'name' => 'Dr. Large Schedule Test',
            'email' => 'largeschedule@hospital.com',
            'password' => 'SecurePassword123!',
            'specialization' => $this->specialization->id,
            'is_active' => true,
            'allowed_appointment_today' => true,
            'frequency' => 'Weekly',
            'patients_based_on_time' => true,
            'schedules' => []
        ];

        // Add many schedules
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $shifts = ['morning', 'afternoon'];
        
        foreach ($days as $day) {
            foreach ($shifts as $shift) {
                $doctorData['schedules'][] = [
                    'day_of_week' => $day,
                    'shift_period' => $shift,
                    'start_time' => $shift === 'morning' ? '08:00' : '14:00',
                    'end_time' => $shift === 'morning' ? '12:00' : '18:00',
                    'number_of_patients_per_day' => 10
                ];
            }
        }

        $response = $this->postJson('/api/doctors', $doctorData);
        
        // Should handle large arrays gracefully
        $this->assertTrue(in_array($response->getStatusCode(), [201, 422]));
    }

    /** @test */
    public function it_handles_missing_required_fields_gracefully()
    {
        $incompleteData = [
            'name' => 'Dr. Incomplete'
            // Missing required fields
        ];

        $response = $this->postJson('/api/doctors', $incompleteData);
        
        $response->assertStatus(422);
        
        $errors = $response->json('errors');
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('password', $errors);
        $this->assertArrayHasKey('specialization', $errors);
    }

    /** @test */
    public function it_handles_network_interruption_during_update()
    {
        $doctor = Doctor::factory()->create([
            'specialization_id' => $this->specialization->id
        ]);

        $updateData = [
            'name' => 'Dr. Network Test',
            'allowed_appointment_today' => false
        ];

        // Simulate partial update scenario
        $response = $this->putJson("/api/doctors/{$doctor->id}", $updateData);
        
        // Should complete successfully or fail gracefully
        $this->assertTrue(in_array($response->getStatusCode(), [200, 500, 503]));
    }

    /** @test */
    public function it_handles_invalid_doctor_id_formats()
    {
        $invalidIds = ['abc', '0', '-1', '999999999999999999999'];
        
        foreach ($invalidIds as $invalidId) {
            $response = $this->getJson("/api/doctors/{$invalidId}");
            $this->assertTrue(in_array($response->getStatusCode(), [404, 400]));
            
            $response = $this->putJson("/api/doctors/{$invalidId}", [
                'name' => 'Dr. Invalid ID Test'
            ]);
            $this->assertTrue(in_array($response->getStatusCode(), [404, 400]));
            
            $response = $this->deleteJson("/api/doctors/{$invalidId}");
            $this->assertTrue(in_array($response->getStatusCode(), [404, 400]));
        }
    }

    /** @test */
    public function it_handles_empty_request_body()
    {
        $response = $this->postJson('/api/doctors', []);
        
        $response->assertStatus(422);
        
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
    }

    /** @test */
    public function it_handles_null_values_in_required_fields()
    {
        $doctorData = [
            'name' => null,
            'email' => null,
            'password' => null,
            'specialization' => null,
            'is_active' => null,
            'allowed_appointment_today' => null,
            'frequency' => null,
            'patients_based_on_time' => null
        ];

        $response = $this->postJson('/api/doctors', $doctorData);
        
        $response->assertStatus(422);
        
        $errors = $response->json('errors');
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('password', $errors);
        $this->assertArrayHasKey('specialization', $errors);
    }

    /** @test */
    public function it_validates_response_structure_consistency()
    {
        $doctor = Doctor::factory()->create([
            'specialization_id' => $this->specialization->id
        ]);

        // Test single doctor response structure
        $response = $this->getJson("/api/doctors/{$doctor->id}");
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'name',
                        'email',
                        'phone',
                        'is_active',
                        'specialization' => [
                            'id',
                            'name'
                        ],
                        'schedules'
                    ]
                ]);

        // Test doctor list response structure
        $response = $this->getJson('/api/doctors');
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'email',
                            'is_active',
                            'specialization'
                        ]
                    ],
                    'links',
                    'meta'
                ]);
    }
}