<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Specialization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class ComprehensiveDoctorTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $specialization;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user for authentication
        $this->user = User::factory()->create([
            'role' => 'admin'
        ]);
        $this->actingAs($this->user);

        // Create a test specialization
        $this->specialization = Specialization::factory()->create([
            'name' => 'Cardiology'
        ]);
    }

    // ========== POSITIVE SCENARIOS ==========

    /** @test */
    public function it_creates_doctor_with_all_required_fields()
    {
        $doctorData = [
            'name' => 'Dr. John Smith',
            'email' => 'john.smith@hospital.com',
            'phone' => '+1234567890',
            'password' => 'SecurePassword123!',
            'is_active' => true,
            'allowed_appointment_today' => true,
            'specialization' => $this->specialization->id,
            'frequency' => 'Weekly',
            'patients_based_on_time' => true,
            'time_slot' => 15,
            'appointmentBookingWindow' => [
                ['month' => 1, 'is_available' => true],
                ['month' => 2, 'is_available' => true]
            ],
            'schedules' => [
                [
                    'day_of_week' => 'monday',
                    'shift_period' => 'morning',
                    'start_time' => '08:00',
                    'end_time' => '12:00'
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
                        'specialization'
                    ]
                ]);

        // Verify doctor was created in database
        $this->assertDatabaseHas('doctors', [
            'specialization_id' => $this->specialization->id,
            'frequency' => 'Weekly',
            'patients_based_on_time' => true
        ]);

        // Verify user was created
        $this->assertDatabaseHas('users', [
            'name' => 'Dr. John Smith',
            'email' => 'john.smith@hospital.com',
            'phone' => '+1234567890',
            'role' => 'doctor'
        ]);
    }

    /** @test */
    public function it_creates_doctor_with_minimal_required_fields()
    {
        $doctorData = [
            'name' => 'Dr. Jane Doe',
            'email' => 'jane.doe@hospital.com',
            'password' => 'MinimalPass123!',
            'is_active' => true,
            'allowed_appointment_today' => false,
            'specialization' => $this->specialization->id,
            'frequency' => 'Daily',
            'patients_based_on_time' => false,
            'appointmentBookingWindow' => [
                ['month' => 1, 'is_available' => true]
            ],
            'schedules' => [
                [
                    'day_of_week' => 'tuesday',
                    'shift_period' => 'afternoon',
                    'start_time' => '14:00',
                    'end_time' => '18:00',
                    'number_of_patients_per_day' => 10
                ]
            ]
        ];

        $response = $this->postJson('/api/doctors', $doctorData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'name' => 'Dr. Jane Doe',
            'email' => 'jane.doe@hospital.com',
            'role' => 'doctor'
        ]);
    }

    /** @test */
    public function it_creates_doctor_with_multiple_schedules()
    {
        $doctorData = [
            'name' => 'Dr. Multi Schedule',
            'email' => 'multi@hospital.com',
            'password' => 'MultiPass123!',
            'is_active' => true,
            'allowed_appointment_today' => true,
            'specialization' => $this->specialization->id,
            'frequency' => 'Weekly',
            'patients_based_on_time' => true,
            'time_slot' => 30,
            'appointmentBookingWindow' => [
                ['month' => 1, 'is_available' => true],
                ['month' => 2, 'is_available' => false]
            ],
            'schedules' => [
                [
                    'day_of_week' => 'monday',
                    'shift_period' => 'morning',
                    'start_time' => '08:00',
                    'end_time' => '12:00'
                ],
                [
                    'day_of_week' => 'wednesday',
                    'shift_period' => 'afternoon',
                    'start_time' => '14:00',
                    'end_time' => '18:00'
                ]
            ]
        ];

        $response = $this->postJson('/api/doctors', $doctorData);

        $response->assertStatus(201);

        // Verify multiple schedules were created
        $this->assertDatabaseCount('doctor_schedules', 2);
    }

    // ========== NEGATIVE SCENARIOS ==========

    /** @test */
    public function it_validates_required_fields()
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
                    'patients_based_on_time',
                    'appointmentBookingWindow'
                ]);
    }

    /** @test */
    public function it_validates_email_format()
    {
        $doctorData = [
            'name' => 'Dr. Invalid Email',
            'email' => 'invalid-email-format',
            'password' => 'ValidPassword123!',
            'is_active' => true,
            'allowed_appointment_today' => true,
            'specialization' => $this->specialization->id,
            'frequency' => 'Weekly',
            'patients_based_on_time' => false,
            'schedules' => []
        ];

        $response = $this->postJson('/api/doctors', $doctorData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_validates_duplicate_email()
    {
        // Create first doctor
        $firstDoctor = [
            'name' => 'Dr. First',
            'email' => 'duplicate@hospital.com',
            'password' => 'ValidPassword123!',
            'is_active' => true,
            'allowed_appointment_today' => true,
            'specialization' => $this->specialization->id,
            'frequency' => 'Weekly',
            'patients_based_on_time' => false,
            'schedules' => [
                [
                    'day_of_week' => 'monday',
                    'shift_period' => 'morning',
                    'start_time' => '08:00',
                    'end_time' => '12:00'
                ]
            ]
        ];

        $this->postJson('/api/doctors', $firstDoctor);

        // Try to create second doctor with same email
        $secondDoctor = [
            'name' => 'Dr. Second',
            'email' => 'duplicate@hospital.com',
            'password' => 'ValidPassword123!',
            'is_active' => true,
            'allowed_appointment_today' => true,
            'specialization' => $this->specialization->id,
            'frequency' => 'Daily',
            'patients_based_on_time' => false,
            'schedules' => [
                [
                    'day_of_week' => 'tuesday',
                    'shift_period' => 'afternoon',
                    'start_time' => '14:00',
                    'end_time' => '18:00'
                ]
            ]
        ];

        $response = $this->postJson('/api/doctors', $secondDoctor);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_validates_weak_password()
    {
        $doctorData = [
            'name' => 'Dr. Weak Password',
            'email' => 'weak@hospital.com',
            'password' => '123', // Too short
            'is_active' => true,
            'allowed_appointment_today' => true,
            'specialization' => $this->specialization->id,
            'frequency' => 'Weekly',
            'patients_based_on_time' => false,
            'schedules' => []
        ];

        $response = $this->postJson('/api/doctors', $doctorData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function it_validates_invalid_specialization()
    {
        $doctorData = [
            'name' => 'Dr. Invalid Spec',
            'email' => 'invalidspec@hospital.com',
            'password' => 'ValidPassword123!',
            'is_active' => true,
            'allowed_appointment_today' => true,
            'specialization' => 99999, // Non-existent specialization
            'frequency' => 'Weekly',
            'patients_based_on_time' => false,
            'schedules' => []
        ];

        $response = $this->postJson('/api/doctors', $doctorData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['specialization']);
    }

    // ========== EDGE CASES ==========

    /** @test */
    public function it_handles_maximum_name_length()
    {
        $longName = str_repeat('A', 256); // Exceeds max length

        $doctorData = [
            'name' => $longName,
            'email' => 'longname@hospital.com',
            'password' => 'ValidPassword123!',
            'is_active' => true,
            'allowed_appointment_today' => true,
            'specialization' => $this->specialization->id,
            'frequency' => 'Weekly',
            'patients_based_on_time' => false,
            'schedules' => []
        ];

        $response = $this->postJson('/api/doctors', $doctorData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function it_validates_schedule_day_of_week()
    {
        $doctorData = [
            'name' => 'Dr. Invalid Schedule',
            'email' => 'invalid.schedule@hospital.com',
            'password' => 'ValidPassword123!',
            'is_active' => true,
            'allowed_appointment_today' => true,
            'specialization' => $this->specialization->id,
            'frequency' => 'Weekly',
            'patients_based_on_time' => true,
            'schedules' => [
                [
                    'day_of_week' => 'invalid_day',
                    'shift_period' => 'morning',
                    'start_time' => '08:00',
                    'end_time' => '12:00'
                ]
            ]
        ];

        $response = $this->postJson('/api/doctors', $doctorData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['schedules.0.day_of_week']);
    }

    /** @test */
    public function it_validates_schedule_shift_period()
    {
        $doctorData = [
            'name' => 'Dr. Invalid Shift',
            'email' => 'invalid.shift@hospital.com',
            'password' => 'ValidPassword123!',
            'is_active' => true,
            'allowed_appointment_today' => true,
            'specialization' => $this->specialization->id,
            'frequency' => 'Weekly',
            'patients_based_on_time' => true,
            'schedules' => [
                [
                    'day_of_week' => 'monday',
                    'shift_period' => 'invalid_shift',
                    'start_time' => '08:00',
                    'end_time' => '12:00'
                ]
            ]
        ];

        $response = $this->postJson('/api/doctors', $doctorData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['schedules.0.shift_period']);
    }

    /** @test */
    public function it_validates_schedule_time_order()
    {
        $doctorData = [
            'name' => 'Dr. Time Order',
            'email' => 'timeorder@hospital.com',
            'password' => 'ValidPassword123!',
            'is_active' => true,
            'allowed_appointment_today' => true,
            'specialization' => $this->specialization->id,
            'frequency' => 'Weekly',
            'patients_based_on_time' => true,
            'schedules' => [
                [
                    'day_of_week' => 'monday',
                    'shift_period' => 'morning',
                    'start_time' => '18:00',
                    'end_time' => '08:00' // End before start
                ]
            ]
        ];

        $response = $this->postJson('/api/doctors', $doctorData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['schedules.0.end_time']);
    }

    /** @test */
    public function it_validates_time_slot_when_patients_based_on_time()
    {
        $doctorData = [
            'name' => 'Dr. Missing Time Slot',
            'email' => 'missing.timeslot@hospital.com',
            'password' => 'ValidPassword123!',
            'is_active' => true,
            'allowed_appointment_today' => true,
            'specialization' => $this->specialization->id,
            'frequency' => 'Weekly',
            'patients_based_on_time' => true,
            // Missing time_slot when patients_based_on_time is true
            'schedules' => [
                [
                    'day_of_week' => 'monday',
                    'shift_period' => 'morning',
                    'start_time' => '08:00',
                    'end_time' => '12:00'
                ]
            ]
        ];

        $response = $this->postJson('/api/doctors', $doctorData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['time_slot']);
    }

    // ========== RELATIONSHIP TESTS ==========

    /** @test */
    public function it_creates_proper_doctor_user_relationship()
    {
        $doctorData = [
            'name' => 'Dr. Relationship Test',
            'email' => 'relationship@hospital.com',
            'password' => 'ValidPassword123!',
            'is_active' => true,
            'allowed_appointment_today' => true,
            'specialization' => $this->specialization->id,
            'frequency' => 'Weekly',
            'patients_based_on_time' => false,
            'appointmentBookingWindow' => [
                ['month' => 1, 'is_available' => true]
            ],
            'schedules' => [
                [
                    'day_of_week' => 'monday',
                    'shift_period' => 'morning',
                    'start_time' => '08:00',
                    'end_time' => '12:00',
                    'number_of_patients_per_day' => 15
                ]
            ]
        ];

        $response = $this->postJson('/api/doctors', $doctorData);

        $response->assertStatus(201);

        $doctor = Doctor::where('specialization_id', $this->specialization->id)->first();
        $this->assertNotNull($doctor->user);
        $this->assertEquals('doctor', $doctor->user->role);
        $this->assertEquals($doctorData['name'], $doctor->user->name);
    }

    /** @test */
    public function it_creates_proper_doctor_specialization_relationship()
    {
        $doctorData = [
            'name' => 'Dr. Specialization Test',
            'email' => 'specialization@hospital.com',
            'password' => 'ValidPassword123!',
            'is_active' => true,
            'allowed_appointment_today' => true,
            'specialization' => $this->specialization->id,
            'frequency' => 'Weekly',
            'patients_based_on_time' => false,
            'appointmentBookingWindow' => [
                ['month' => 1, 'is_available' => true]
            ],
            'schedules' => [
                [
                    'day_of_week' => 'monday',
                    'shift_period' => 'morning',
                    'start_time' => '08:00',
                    'end_time' => '12:00',
                    'number_of_patients_per_day' => 20
                ]
            ]
        ];

        $response = $this->postJson('/api/doctors', $doctorData);

        $response->assertStatus(201);

        $doctor = Doctor::where('specialization_id', $this->specialization->id)->first();
        $this->assertNotNull($doctor->specialization);
        $this->assertEquals($this->specialization->id, $doctor->specialization->id);
    }
}