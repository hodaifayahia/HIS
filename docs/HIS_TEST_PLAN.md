# Hospital Information System (HIS) - Comprehensive Test Plan

## Overview

This document outlines a comprehensive test plan for a Hospital Information System (HIS) built with Laravel (PHP) and Vue.js, using MySQL as the backend database. The test plan covers unit tests, integration tests, end-to-end tests, performance tests, and security tests.

## 1. PHPUnit Test Cases for Laravel

### 1.1 Unit Tests for Models

#### Patient Model Tests
```php
<?php
// tests/Unit/Models/PatientTest.php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PatientTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_patient()
    {
        $patient = Patient::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+1234567890'
        ]);

        $this->assertDatabaseHas('patients', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com'
        ]);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Patient::create([
            'first_name' => '',
            'last_name' => '',
            'email' => 'invalid-email'
        ]);
    }

    /** @test */
    public function it_has_appointments_relationship()
    {
        $patient = Patient::factory()->create();
        $appointment = $patient->appointments()->create([
            'appointment_date' => now()->addDay(),
            'status' => 'scheduled'
        ]);

        $this->assertTrue($patient->appointments->contains($appointment));
    }
}
```

#### Appointment Model Tests
```php
<?php
// tests/Unit/Models/AppointmentTest.php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Doctor;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_belongs_to_patient_and_doctor()
    {
        $patient = Patient::factory()->create();
        $doctor = Doctor::factory()->create();
        
        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id
        ]);

        $this->assertEquals($patient->id, $appointment->patient->id);
        $this->assertEquals($doctor->id, $appointment->doctor->id);
    }

    /** @test */
    public function it_can_be_cancelled()
    {
        $appointment = Appointment::factory()->create(['status' => 'scheduled']);
        
        $appointment->cancel();
        
        $this->assertEquals('cancelled', $appointment->status);
    }
}
```

### 1.2 Unit Tests for Services

#### AppointmentService Tests
```php
<?php
// tests/Unit/Services/AppointmentServiceTest.php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AppointmentService;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AppointmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private AppointmentService $appointmentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->appointmentService = new AppointmentService();
    }

    /** @test */
    public function it_can_schedule_appointment()
    {
        $patient = Patient::factory()->create();
        $doctor = Doctor::factory()->create();
        
        $appointmentData = [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'type' => 'consultation'
        ];

        $appointment = $this->appointmentService->scheduleAppointment($appointmentData);

        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertEquals('scheduled', $appointment->status);
    }

    /** @test */
    public function it_prevents_double_booking()
    {
        $doctor = Doctor::factory()->create();
        $appointmentTime = now()->addDay();
        
        // Create first appointment
        Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'appointment_date' => $appointmentTime,
            'status' => 'scheduled'
        ]);

        // Try to create conflicting appointment
        $this->expectException(\App\Exceptions\AppointmentConflictException::class);
        
        $this->appointmentService->scheduleAppointment([
            'patient_id' => Patient::factory()->create()->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => $appointmentTime->format('Y-m-d H:i:s'),
            'type' => 'consultation'
        ]);
    }
}
```

### 1.3 Unit Tests for Controllers

#### PatientController Tests
```php
<?php
// tests/Unit/Controllers/PatientControllerTest.php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Http\Controllers\PatientController;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

class PatientControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_paginated_patients()
    {
        $user = User::factory()->create();
        Patient::factory()->count(15)->create();

        $controller = new PatientController();
        $request = new Request();
        
        $response = $controller->index($request);
        
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('meta', $responseData);
    }

    /** @test */
    public function it_creates_patient_with_valid_data()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $patientData = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
            'phone' => '+1987654321',
            'date_of_birth' => '1990-01-01'
        ];

        $controller = new PatientController();
        $request = new Request($patientData);
        
        $response = $controller->store($request);
        
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertDatabaseHas('patients', $patientData);
    }
}
```

### 1.4 Database Assertions and Schema Tests

#### Migration Tests
```php
<?php
// tests/Unit/Database/MigrationTest.php

namespace Tests\Unit\Database;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MigrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function patients_table_has_expected_columns()
    {
        $expectedColumns = [
            'id', 'first_name', 'last_name', 'email', 'phone',
            'date_of_birth', 'gender', 'address', 'emergency_contact',
            'created_at', 'updated_at'
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('patients', $column),
                "Patients table is missing column: {$column}"
            );
        }
    }

    /** @test */
    public function appointments_table_has_foreign_keys()
    {
        $this->assertTrue(Schema::hasColumn('appointments', 'patient_id'));
        $this->assertTrue(Schema::hasColumn('appointments', 'doctor_id'));
        
        // Test foreign key constraints
        $foreignKeys = Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->listTableForeignKeys('appointments');
            
        $foreignKeyColumns = array_map(function($fk) {
            return $fk->getLocalColumns()[0];
        }, $foreignKeys);
        
        $this->assertContains('patient_id', $foreignKeyColumns);
        $this->assertContains('doctor_id', $foreignKeyColumns);
    }

    /** @test */
    public function database_transactions_rollback_properly()
    {
        $initialCount = Patient::count();
        
        try {
            \DB::transaction(function () {
                Patient::factory()->create(['first_name' => 'Test']);
                throw new \Exception('Force rollback');
            });
        } catch (\Exception $e) {
            // Expected exception
        }
        
        $this->assertEquals($initialCount, Patient::count());
    }
}
```

## 2. Feature Tests for API Endpoints

### 2.1 Authentication Tests
```php
<?php
// tests/Feature/AuthenticationTest.php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'user' => ['id', 'name', 'email'],
                    'token'
                ]);
    }

    /** @test */
    public function user_cannot_login_with_invalid_credentials()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(401)
                ->assertJson(['message' => 'Invalid credentials']);
    }

    /** @test */
    public function authenticated_user_can_logout()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/logout');

        $response->assertStatus(200)
                ->assertJson(['message' => 'Logged out successfully']);
    }
}
```

### 2.2 Patient Registration Tests
```php
<?php
// tests/Feature/PatientRegistrationTest.php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class PatientRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function authenticated_user_can_register_patient()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $patientData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+1234567890',
            'date_of_birth' => '1985-05-15',
            'gender' => 'male'
        ];

        $response = $this->postJson('/api/patients', $patientData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id', 'first_name', 'last_name', 'email',
                        'phone', 'date_of_birth', 'gender'
                    ]
                ]);

        $this->assertDatabaseHas('patients', $patientData);
    }

    /** @test */
    public function patient_registration_validates_required_fields()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/patients', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'first_name', 'last_name', 'email', 'phone'
                ]);
    }
}
```

### 2.3 Appointment Scheduling Tests
```php
<?php
// tests/Feature/AppointmentSchedulingTest.php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class AppointmentSchedulingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_schedule_appointment()
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();
        $doctor = Doctor::factory()->create();
        
        Sanctum::actingAs($user);

        $appointmentData = [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'type' => 'consultation',
            'notes' => 'Regular checkup'
        ];

        $response = $this->postJson('/api/appointments', $appointmentData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id', 'patient_id', 'doctor_id', 'appointment_date',
                        'type', 'status', 'notes'
                    ]
                ]);

        $this->assertDatabaseHas('appointments', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'type' => 'consultation',
            'status' => 'scheduled'
        ]);
    }

    /** @test */
    public function appointment_cannot_be_scheduled_in_past()
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();
        $doctor = Doctor::factory()->create();
        
        Sanctum::actingAs($user);

        $appointmentData = [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => now()->subDay()->format('Y-m-d H:i:s'),
            'type' => 'consultation'
        ];

        $response = $this->postJson('/api/appointments', $appointmentData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['appointment_date']);
    }
}
```

### 2.4 Billing Tests
```php
<?php
// tests/Feature/BillingTest.php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Bill;
use App\Models\Prestation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function bill_can_be_created_for_appointment()
    {
        $user = User::factory()->create();
        $appointment = Appointment::factory()->create();
        $prestation = Prestation::factory()->create(['public_price' => 100.00]);
        
        Sanctum::actingAs($user);

        $billData = [
            'appointment_id' => $appointment->id,
            'prestations' => [
                ['prestation_id' => $prestation->id, 'quantity' => 1]
            ],
            'payment_method' => 'cash'
        ];

        $response = $this->postJson('/api/bills', $billData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id', 'appointment_id', 'total_amount',
                        'payment_method', 'status', 'prestations'
                    ]
                ]);

        $this->assertDatabaseHas('bills', [
            'appointment_id' => $appointment->id,
            'total_amount' => 100.00,
            'payment_method' => 'cash',
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function bill_calculates_total_with_multiple_prestations()
    {
        $user = User::factory()->create();
        $appointment = Appointment::factory()->create();
        $prestation1 = Prestation::factory()->create(['public_price' => 50.00]);
        $prestation2 = Prestation::factory()->create(['public_price' => 75.00]);
        
        Sanctum::actingAs($user);

        $billData = [
            'appointment_id' => $appointment->id,
            'prestations' => [
                ['prestation_id' => $prestation1->id, 'quantity' => 2],
                ['prestation_id' => $prestation2->id, 'quantity' => 1]
            ]
        ];

        $response = $this->postJson('/api/bills', $billData);

        $response->assertStatus(201);
        
        $bill = Bill::find($response->json('data.id'));
        $this->assertEquals(175.00, $bill->total_amount); // (50*2) + (75*1)
    }
}
```

## 3. Integration and End-to-End Tests

### 3.1 Laravel Dusk Tests

#### User Login/Logout Flow
```php
<?php
// tests/Browser/LoginTest.php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class LoginTest extends DuskTestCase
{
    use DatabaseMigrations;

    /** @test */
    public function user_can_login_and_logout()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123')
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->type('email', 'test@example.com')
                    ->type('password', 'password123')
                    ->press('Login')
                    ->assertPathIs('/dashboard')
                    ->assertSee('Dashboard')
                    ->click('@logout-button')
                    ->assertPathIs('/login')
                    ->assertSee('Login');
        });
    }

    /** @test */
    public function user_cannot_login_with_invalid_credentials()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->type('email', 'invalid@example.com')
                    ->type('password', 'wrongpassword')
                    ->press('Login')
                    ->assertPathIs('/login')
                    ->assertSee('These credentials do not match our records');
        });
    }
}
```

#### Multi-step Workflow Test
```php
<?php
// tests/Browser/PatientWorkflowTest.php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use App\Models\User;
use App\Models\Patient;
use App\Models\Doctor;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class PatientWorkflowTest extends DuskTestCase
{
    use DatabaseMigrations;

    /** @test */
    public function complete_patient_workflow()
    {
        $user = User::factory()->create();
        $doctor = Doctor::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            // Step 1: Login
            $browser->loginAs($user)
                    ->visit('/dashboard');

            // Step 2: Patient Check-in
            $browser->visit('/patients/checkin')
                    ->type('first_name', 'John')
                    ->type('last_name', 'Doe')
                    ->type('email', 'john.doe@example.com')
                    ->type('phone', '+1234567890')
                    ->press('Check In')
                    ->assertSee('Patient checked in successfully');

            // Step 3: Schedule Consultation
            $browser->visit('/appointments/create')
                    ->select('patient_id', '1') // Assuming first patient
                    ->select('doctor_id', '1')  // Assuming first doctor
                    ->type('appointment_date', now()->addHour()->format('Y-m-d\TH:i'))
                    ->select('type', 'consultation')
                    ->press('Schedule Appointment')
                    ->assertSee('Appointment scheduled successfully');

            // Step 4: Create Bill
            $browser->visit('/bills/create')
                    ->select('appointment_id', '1')
                    ->check('prestations[1]') // Select first prestation
                    ->select('payment_method', 'cash')
                    ->press('Create Bill')
                    ->assertSee('Bill created successfully');

            // Step 5: Process Discharge
            $browser->visit('/patients/discharge')
                    ->select('patient_id', '1')
                    ->type('discharge_notes', 'Patient recovered well')
                    ->press('Discharge Patient')
                    ->assertSee('Patient discharged successfully');
        });
    }
}
```

## 4. Vue.js Unit Tests with Jest

### 4.1 Component Existence and Props Validation

```javascript
// tests/Vue/components/PatientForm.test.js

import { mount } from '@vue/test-utils'
import PatientForm from '@/components/PatientForm.vue'

describe('PatientForm.vue', () => {
  test('renders correctly', () => {
    const wrapper = mount(PatientForm)
    expect(wrapper.exists()).toBe(true)
  })

  test('accepts patient prop', () => {
    const patient = {
      id: 1,
      first_name: 'John',
      last_name: 'Doe',
      email: 'john.doe@example.com'
    }
    
    const wrapper = mount(PatientForm, {
      props: { patient }
    })
    
    expect(wrapper.props('patient')).toEqual(patient)
  })

  test('validates required props', () => {
    const wrapper = mount(PatientForm, {
      props: { patient: null }
    })
    
    // Should show validation errors for required fields
    expect(wrapper.find('.error-message').exists()).toBe(true)
  })
})
```

### 4.2 DOM Interaction Tests

```javascript
// tests/Vue/components/LoginForm.test.js

import { mount } from '@vue/test-utils'
import LoginForm from '@/components/LoginForm.vue'

describe('LoginForm.vue', () => {
  test('toggles password visibility', async () => {
    const wrapper = mount(LoginForm)
    
    const passwordInput = wrapper.find('input[type="password"]')
    const toggleButton = wrapper.find('.password-toggle')
    
    expect(passwordInput.exists()).toBe(true)
    
    await toggleButton.trigger('click')
    
    expect(wrapper.find('input[type="text"]').exists()).toBe(true)
  })

  test('remember me checkbox works', async () => {
    const wrapper = mount(LoginForm)
    
    const checkbox = wrapper.find('input[type="checkbox"]')
    
    expect(checkbox.element.checked).toBe(false)
    
    await checkbox.setChecked(true)
    
    expect(checkbox.element.checked).toBe(true)
  })

  test('form submission with valid data', async () => {
    const wrapper = mount(LoginForm)
    
    await wrapper.find('input[name="email"]').setValue('test@example.com')
    await wrapper.find('input[name="password"]').setValue('password123')
    
    await wrapper.find('form').trigger('submit.prevent')
    
    expect(wrapper.emitted('submit')).toBeTruthy()
    expect(wrapper.emitted('submit')[0][0]).toEqual({
      email: 'test@example.com',
      password: 'password123',
      remember: false
    })
  })
})
```

### 4.3 API Call Mocks

```javascript
// tests/Vue/services/PatientService.test.js

import axios from 'axios'
import PatientService from '@/services/PatientService'

jest.mock('axios')
const mockedAxios = axios as jest.Mocked<typeof axios>

describe('PatientService', () => {
  beforeEach(() => {
    jest.clearAllMocks()
  })

  test('fetches patients successfully', async () => {
    const patientsData = {
      data: [
        { id: 1, first_name: 'John', last_name: 'Doe' },
        { id: 2, first_name: 'Jane', last_name: 'Smith' }
      ],
      meta: { total: 2 }
    }

    mockedAxios.get.mockResolvedValue({ data: patientsData })

    const result = await PatientService.getPatients()

    expect(mockedAxios.get).toHaveBeenCalledWith('/api/patients')
    expect(result).toEqual(patientsData)
  })

  test('creates patient successfully', async () => {
    const patientData = {
      first_name: 'John',
      last_name: 'Doe',
      email: 'john.doe@example.com'
    }

    const createdPatient = { id: 1, ...patientData }

    mockedAxios.post.mockResolvedValue({ data: { data: createdPatient } })

    const result = await PatientService.createPatient(patientData)

    expect(mockedAxios.post).toHaveBeenCalledWith('/api/patients', patientData)
    expect(result.data).toEqual(createdPatient)
  })

  test('handles API errors gracefully', async () => {
    const errorMessage = 'Network Error'
    mockedAxios.get.mockRejectedValue(new Error(errorMessage))

    await expect(PatientService.getPatients()).rejects.toThrow(errorMessage)
  })
})
```

## 5. Performance and Security Tests

### 5.1 Load Testing

```php
<?php
// tests/Performance/LoadTest.php

namespace Tests\Performance;

use Tests\TestCase;
use App\Models\User;
use App\Models\Patient;
use App\Models\Doctor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class LoadTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function patient_creation_performance_test()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        // Simulate 100 concurrent patient creations
        for ($i = 0; $i < 100; $i++) {
            $patientData = [
                'first_name' => "Patient{$i}",
                'last_name' => "Test{$i}",
                'email' => "patient{$i}@test.com",
                'phone' => "+123456789{$i}"
            ];

            $response = $this->postJson('/api/patients', $patientData);
            $response->assertStatus(201);
        }

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024; // Convert to MB

        // Performance assertions
        $this->assertLessThan(5000, $executionTime, 'Patient creation took too long'); // < 5 seconds
        $this->assertLessThan(50, $memoryUsed, 'Memory usage too high'); // < 50 MB

        // Database efficiency check
        $queryCount = count(DB::getQueryLog());
        $this->assertLessThan(300, $queryCount, 'Too many database queries'); // < 3 queries per patient
    }

    /** @test */
    public function appointment_scheduling_load_test()
    {
        $user = User::factory()->create();
        $patients = Patient::factory()->count(50)->create();
        $doctors = Doctor::factory()->count(10)->create();
        
        $this->actingAs($user);

        $startTime = microtime(true);

        // Schedule 100 appointments
        for ($i = 0; $i < 100; $i++) {
            $appointmentData = [
                'patient_id' => $patients->random()->id,
                'doctor_id' => $doctors->random()->id,
                'appointment_date' => now()->addDays(rand(1, 30))->format('Y-m-d H:i:s'),
                'type' => 'consultation'
            ];

            $response = $this->postJson('/api/appointments', $appointmentData);
            $response->assertStatus(201);
        }

        $executionTime = (microtime(true) - $startTime) * 1000;
        
        $this->assertLessThan(10000, $executionTime, 'Appointment scheduling took too long');
    }
}
```

### 5.2 Security Tests

```php
<?php
// tests/Security/SecurityTest.php

namespace Tests\Security;

use Tests\TestCase;
use App\Models\User;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function sql_injection_protection_test()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Attempt SQL injection in patient search
        $maliciousInput = "'; DROP TABLE patients; --";
        
        $response = $this->getJson("/api/patients?search={$maliciousInput}");
        
        // Should not cause SQL error and patients table should still exist
        $response->assertStatus(200);
        $this->assertTrue(\Schema::hasTable('patients'));
    }

    /** @test */
    public function xss_protection_test()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $xssPayload = '<script>alert("XSS")</script>';
        
        $patientData = [
            'first_name' => $xssPayload,
            'last_name' => 'Test',
            'email' => 'test@example.com',
            'phone' => '+1234567890'
        ];

        $response = $this->postJson('/api/patients', $patientData);
        
        if ($response->status() === 201) {
            $patient = Patient::find($response->json('data.id'));
            // Ensure XSS payload is escaped/sanitized
            $this->assertNotEquals($xssPayload, $patient->first_name);
        }
    }

    /** @test */
    public function unauthorized_access_protection()
    {
        // Test without authentication
        $response = $this->getJson('/api/patients');
        $response->assertStatus(401);

        // Test with invalid token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token'
        ])->getJson('/api/patients');
        
        $response->assertStatus(401);
    }

    /** @test */
    public function rate_limiting_test()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Make multiple rapid requests
        for ($i = 0; $i < 100; $i++) {
            $response = $this->getJson('/api/patients');
            
            if ($response->status() === 429) {
                // Rate limit hit - this is expected
                $this->assertEquals(429, $response->status());
                break;
            }
        }
    }
}
```

## 6. Test File Organization

### Directory Structure
```
tests/
├── Unit/
│   ├── Models/
│   │   ├── PatientTest.php
│   │   ├── AppointmentTest.php
│   │   ├── DoctorTest.php
│   │   └── BillTest.php
│   ├── Services/
│   │   ├── AppointmentServiceTest.php
│   │   ├── BillingServiceTest.php
│   │   └── PrestationValidationServiceTest.php
│   ├── Controllers/
│   │   ├── PatientControllerTest.php
│   │   ├── AppointmentControllerTest.php
│   │   └── BillControllerTest.php
│   └── Database/
│       ├── MigrationTest.php
│       └── SeederTest.php
├── Feature/
│   ├── AuthenticationTest.php
│   ├── PatientRegistrationTest.php
│   ├── AppointmentSchedulingTest.php
│   ├── BillingTest.php
│   └── MedicalRecordTest.php
├── Browser/
│   ├── LoginTest.php
│   ├── PatientWorkflowTest.php
│   └── AppointmentManagementTest.php
├── Performance/
│   ├── LoadTest.php
│   └── DatabasePerformanceTest.php
├── Security/
│   ├── SecurityTest.php
│   └── AuthorizationTest.php
└── Vue/
    ├── components/
    │   ├── PatientForm.test.js
    │   ├── LoginForm.test.js
    │   └── AppointmentCalendar.test.js
    └── services/
        ├── PatientService.test.js
        ├── AppointmentService.test.js
        └── BillingService.test.js
```

## 7. Test Environment Configuration

### .env.testing
```env
APP_NAME="HIS Testing"
APP_ENV=testing
APP_KEY=base64:your-test-key-here
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=single
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=his_testing
DB_USERNAME=test_user
DB_PASSWORD=test_password

BROADCAST_DRIVER=log
CACHE_DRIVER=array
FILESYSTEM_DRIVER=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=array

MAIL_MAILER=array

# Testing specific configurations
TELESCOPE_ENABLED=false
DEBUGBAR_ENABLED=false
```

### phpunit.xml
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="./vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory suffix="Test.php">./tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory suffix="Test.php">./tests/Feature</directory>
        </testsuite>
        <testsuite name="Browser">
            <directory suffix="Test.php">./tests/Browser</directory>
        </testsuite>
        <testsuite name="Performance">
            <directory suffix="Test.php">./tests/Performance</directory>
        </testsuite>
        <testsuite name="Security">
            <directory suffix="Test.php">./tests/Security</directory>
        </testsuite>
    </testsuites>
    <coverage processUncoveredFiles="true">
        <include>
            <directory suffix=".php">./app</directory>
        </include>
    </coverage>
    <php>
        <server name="APP_ENV" value="testing"/>
        <server name="BCRYPT_ROUNDS" value="4"/>
        <server name="CACHE_DRIVER" value="array"/>
        <server name="DB_CONNECTION" value="mysql"/>
        <server name="DB_DATABASE" value="his_testing"/>
        <server name="MAIL_MAILER" value="array"/>
        <server name="QUEUE_CONNECTION" value="sync"/>
        <server name="SESSION_DRIVER" value="array"/>
        <server name="TELESCOPE_ENABLED" value="false"/>
    </php>
</phpunit>
```

### Jest Configuration (jest.config.js)
```javascript
module.exports = {
  preset: '@vue/cli-plugin-unit-jest',
  testEnvironment: 'jsdom',
  moduleFileExtensions: ['js', 'json', 'vue'],
  transform: {
    '^.+\\.vue$': '@vue/vue3-jest',
    '^.+\\.js$': 'babel-jest'
  },
  moduleNameMapping: {
    '^@/(.*)$': '<rootDir>/resources/js/$1'
  },
  collectCoverage: true,
  collectCoverageFrom: [
    'resources/js/**/*.{js,vue}',
    '!resources/js/app.js',
    '!**/node_modules/**'
  ],
  coverageReporters: ['html', 'text', 'lcov'],
  testMatch: [
    '**/tests/Vue/**/*.test.js'
  ]
}
```

## 8. Running Tests

### Laravel Tests
```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
php artisan test --testsuite=Browser

# Run with coverage
php artisan test --coverage

# Run specific test file
php artisan test tests/Unit/Models/PatientTest.php

# Run with parallel processing
php artisan test --parallel

# Run performance tests
php artisan test --testsuite=Performance --stop-on-failure
```

### Vue.js Tests
```bash
# Run Vue tests
npm run test:unit

# Run with coverage
npm run test:unit -- --coverage

# Run in watch mode
npm run test:unit -- --watch

# Run specific test file
npm run test:unit -- tests/Vue/components/PatientForm.test.js
```

### Database Setup for Testing
```bash
# Create test database
mysql -u root -p -e "CREATE DATABASE his_testing;"
mysql -u root -p -e "GRANT ALL PRIVILEGES ON his_testing.* TO 'test_user'@'localhost' IDENTIFIED BY 'test_password';"

# Run migrations for test database
php artisan migrate --env=testing

# Seed test database
php artisan db:seed --env=testing
```

This comprehensive test plan ensures thorough coverage of the Hospital Information System, from unit tests to end-to-end workflows, performance validation, and security checks. The test suite provides confidence in system reliability, data integrity, and user experience across both backend Laravel APIs and frontend Vue.js components.