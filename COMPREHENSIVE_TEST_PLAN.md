# Comprehensive Test Plan for Hospital Information System (HIS)

## Overview
This document outlines a comprehensive testing strategy for a Hospital Information System built with Laravel (PHP) and Vue.js, using MySQL as the backend database. The test plan covers unit tests, feature tests, integration tests, performance tests, and security tests.

## 1. PHPUnit Test Cases for Laravel

### 1.1 Unit Tests for Models

#### Patient Model Tests
```php
<?php
// tests/Unit/Models/PatientModelTest.php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PatientModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_patient()
    {
        $patient = Patient::factory()->create([
            'Firstname' => 'John',
            'Lastname' => 'Doe',
            'gender' => 1,
            'phone' => '1234567890'
        ]);

        $this->assertDatabaseHas('patients', [
            'Firstname' => 'John',
            'Lastname' => 'Doe'
        ]);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Patient::create([
            'Lastname' => 'Doe'
            // Missing required Firstname
        ]);
    }

    /** @test */
    public function it_has_appointments_relationship()
    {
        $patient = Patient::factory()->create();
        
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $patient->appointments()
        );
    }
}
```

#### Financial Transaction Model Tests
```php
<?php
// tests/Unit/Models/FinancialTransactionModelTest.php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Caisse\FinancialTransaction;
use App\Models\Bank\Bank;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FinancialTransactionModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_bank_transaction()
    {
        $bank = Bank::factory()->create();
        $patient = Patient::factory()->create();

        $transaction = FinancialTransaction::create([
            'patient_id' => $patient->id,
            'payment_method' => 'bank_transfer',
            'amount' => 100.00,
            'is_bank_transaction' => true,
            'bank_id' => $bank->id,
            'transaction_type' => 'payment'
        ]);

        $this->assertTrue($transaction->is_bank_transaction);
        $this->assertEquals($bank->id, $transaction->bank_id);
        $this->assertEquals('bank_transfer', $transaction->payment_method);
    }

    /** @test */
    public function it_belongs_to_bank()
    {
        $transaction = FinancialTransaction::factory()->create();
        
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $transaction->bank()
        );
    }
}
```

### 1.2 Unit Tests for Services

#### Financial Transaction Service Tests
```php
<?php
// tests/Unit/Services/FinancialTransactionServiceTest.php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Caisse\FinancialTransactionService;
use App\Models\Bank\Bank;
use App\Models\Patient;
use App\Models\Reception\ficheNavette;
use App\Models\Reception\ficheNavetteItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FinancialTransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FinancialTransactionService();
    }

    /** @test */
    public function it_creates_bulk_payments_with_bank_transfer()
    {
        $bank = Bank::factory()->create();
        $patient = Patient::factory()->create();
        $ficheNavette = ficheNavette::factory()->create(['patient_id' => $patient->id]);
        $ficheNavetteItem = ficheNavetteItem::factory()->create([
            'fiche_navette_id' => $ficheNavette->id
        ]);

        $data = [
            'fiche_navette_id' => $ficheNavette->id,
            'patient_id' => $patient->id,
            'payment_method' => 'bank_transfer',
            'total_amount' => 100.00,
            'is_bank_transaction' => true,
            'bank_id' => $bank->id,
            'items' => [
                [
                    'fiche_navette_item_id' => $ficheNavetteItem->id,
                    'amount' => 100.00
                ]
            ]
        ];

        $result = $this->service->createBulkPayments($data);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['payments']);
        $this->assertTrue($result['payments'][0]->is_bank_transaction);
        $this->assertEquals($bank->id, $result['payments'][0]->bank_id);
    }
}
```

### 1.3 Unit Tests for Controllers

#### Financial Transaction Controller Tests
```php
<?php
// tests/Unit/Controllers/FinancialTransactionControllerTest.php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Http\Controllers\Caisse\FinancialTransactionController;
use App\Services\Caisse\FinancialTransactionService;
use App\Http\Requests\Caisse\BulkPaymentRequest;
use Mockery;

class FinancialTransactionControllerTest extends TestCase
{
    /** @test */
    public function it_calls_service_for_bulk_payment()
    {
        $mockService = Mockery::mock(FinancialTransactionService::class);
        $mockService->shouldReceive('createBulkPayments')
                   ->once()
                   ->with(Mockery::type('array'))
                   ->andReturn(['success' => true, 'payments' => []]);

        $controller = new FinancialTransactionController($mockService);
        
        $request = Mockery::mock(BulkPaymentRequest::class);
        $request->shouldReceive('validated')
               ->once()
               ->andReturn([
                   'payment_method' => 'bank_transfer',
                   'is_bank_transaction' => true
               ]);

        $response = $controller->bulkPayment($request);
        
        $this->assertIsArray($response);
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
                    'user',
                    'token'
                ]);
    }

    /** @test */
    public function user_cannot_login_with_invalid_credentials()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'invalid@example.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function authenticated_user_can_logout()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/logout');

        $response->assertStatus(200);
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
            'Firstname' => 'John',
            'Lastname' => 'Doe',
            'gender' => 1,
            'phone' => '1234567890',
            'email' => 'john.doe@example.com',
            'date_of_birth' => '1990-01-01'
        ];

        $response = $this->postJson('/api/patients', $patientData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'id',
                    'Firstname',
                    'Lastname',
                    'phone'
                ]);

        $this->assertDatabaseHas('patients', [
            'Firstname' => 'John',
            'Lastname' => 'Doe'
        ]);
    }

    /** @test */
    public function patient_registration_validates_required_fields()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/patients', [
            'Lastname' => 'Doe'
            // Missing required Firstname
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['Firstname']);
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
    public function authenticated_user_can_schedule_appointment()
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();
        $doctor = Doctor::factory()->create();
        
        Sanctum::actingAs($user);

        $appointmentData = [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => '2024-12-31',
            'appointment_time' => '10:00:00',
            'reason' => 'Regular checkup'
        ];

        $response = $this->postJson('/api/appointments', $appointmentData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'id',
                    'patient_id',
                    'doctor_id',
                    'appointment_date'
                ]);

        $this->assertDatabaseHas('appointments', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id
        ]);
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
use App\Models\Bank\Bank;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function authenticated_user_can_process_bank_transfer_payment()
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();
        $bank = Bank::factory()->create();
        
        Sanctum::actingAs($user);

        $paymentData = [
            'patient_id' => $patient->id,
            'payment_method' => 'bank_transfer',
            'amount' => 150.00,
            'is_bank_transaction' => true,
            'bank_id' => $bank->id,
            'description' => 'Medical consultation fee'
        ];

        $response = $this->postJson('/api/financial-transactions-bulk-payment', $paymentData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ]);

        $this->assertDatabaseHas('financial_transactions', [
            'patient_id' => $patient->id,
            'payment_method' => 'bank_transfer',
            'is_bank_transaction' => true,
            'bank_id' => $bank->id
        ]);
    }
}
```

## 3. Database Assertions and Schema Integrity Tests

### 3.1 Database Schema Tests
```php
<?php
// tests/Database/SchemaIntegrityTest.php

namespace Tests\Database;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

class SchemaIntegrityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function patients_table_has_expected_columns()
    {
        $this->assertTrue(Schema::hasTable('patients'));
        
        $expectedColumns = [
            'id', 'Firstname', 'Lastname', 'gender', 'phone', 
            'email', 'date_of_birth', 'created_at', 'updated_at'
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('patients', $column),
                "Column {$column} does not exist in patients table"
            );
        }
    }

    /** @test */
    public function financial_transactions_table_has_bank_fields()
    {
        $this->assertTrue(Schema::hasTable('financial_transactions'));
        
        $this->assertTrue(Schema::hasColumn('financial_transactions', 'is_bank_transaction'));
        $this->assertTrue(Schema::hasColumn('financial_transactions', 'bank_id'));
        $this->assertTrue(Schema::hasColumn('financial_transactions', 'payment_method'));
    }

    /** @test */
    public function foreign_key_constraints_exist()
    {
        // Test that foreign key relationships are properly defined
        $this->assertTrue(Schema::hasTable('banks'));
        $this->assertTrue(Schema::hasTable('financial_transactions'));
        
        // Verify foreign key constraint by attempting to insert invalid data
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        \DB::table('financial_transactions')->insert([
            'bank_id' => 99999, // Non-existent bank ID
            'amount' => 100.00,
            'payment_method' => 'bank_transfer',
            'is_bank_transaction' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
```

### 3.2 Transaction Rollback Tests
```php
<?php
// tests/Database/TransactionRollbackTest.php

namespace Tests\Database;

use Tests\TestCase;
use App\Models\Patient;
use App\Models\Caisse\FinancialTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class TransactionRollbackTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function database_transaction_rolls_back_on_failure()
    {
        $initialPatientCount = Patient::count();

        try {
            DB::transaction(function () {
                // Create a patient
                Patient::create([
                    'Firstname' => 'John',
                    'Lastname' => 'Doe',
                    'gender' => 1,
                    'phone' => '1234567890'
                ]);

                // Force an exception to trigger rollback
                throw new \Exception('Simulated failure');
            });
        } catch (\Exception $e) {
            // Expected exception
        }

        // Verify that the patient was not created due to rollback
        $this->assertEquals($initialPatientCount, Patient::count());
    }

    /** @test */
    public function financial_transaction_rollback_maintains_data_integrity()
    {
        $patient = Patient::factory()->create();
        $initialTransactionCount = FinancialTransaction::count();

        try {
            DB::transaction(function () use ($patient) {
                // Create a financial transaction
                FinancialTransaction::create([
                    'patient_id' => $patient->id,
                    'amount' => 100.00,
                    'payment_method' => 'cash',
                    'transaction_type' => 'payment'
                ]);

                // Force rollback
                throw new \Exception('Transaction failed');
            });
        } catch (\Exception $e) {
            // Expected
        }

        $this->assertEquals($initialTransactionCount, FinancialTransaction::count());
    }
}
```

## 4. Integration and End-to-End Tests with Laravel Dusk

### 4.1 User Authentication Flow Tests
```php
<?php
// tests/Browser/AuthenticationFlowTest.php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class AuthenticationFlowTest extends DuskTestCase
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
                    ->assertPathIs('/login');
        });
    }

    /** @test */
    public function user_cannot_access_protected_routes_without_authentication()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/dashboard')
                    ->assertPathIs('/login')
                    ->assertSee('Please log in');
        });
    }
}
```

### 4.2 Multi-step Workflow Tests
```php
<?php
// tests/Browser/PatientWorkflowTest.php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Bank\Bank;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class PatientWorkflowTest extends DuskTestCase
{
    use DatabaseMigrations;

    /** @test */
    public function complete_patient_workflow_from_checkin_to_discharge()
    {
        $user = User::factory()->create();
        $doctor = Doctor::factory()->create();
        $bank = Bank::factory()->create();

        $this->browse(function (Browser $browser) use ($user, $doctor, $bank) {
            // Login
            $browser->loginAs($user)
                    ->visit('/dashboard');

            // Step 1: Patient Check-in
            $browser->visit('/patients/register')
                    ->type('Firstname', 'John')
                    ->type('Lastname', 'Doe')
                    ->select('gender', '1')
                    ->type('phone', '1234567890')
                    ->press('Register Patient')
                    ->assertSee('Patient registered successfully');

            // Step 2: Schedule Appointment
            $browser->visit('/appointments/create')
                    ->select('patient_id', '1')
                    ->select('doctor_id', $doctor->id)
                    ->type('appointment_date', '2024-12-31')
                    ->type('appointment_time', '10:00')
                    ->press('Schedule Appointment')
                    ->assertSee('Appointment scheduled');

            // Step 3: Consultation (simulate)
            $browser->visit('/consultations/1')
                    ->type('diagnosis', 'Regular checkup completed')
                    ->type('treatment', 'No treatment required')
                    ->press('Save Consultation')
                    ->assertSee('Consultation saved');

            // Step 4: Billing with Bank Transfer
            $browser->visit('/billing/1')
                    ->select('payment_method', 'bank_transfer')
                    ->select('bank_id', $bank->id)
                    ->type('amount', '150.00')
                    ->press('Process Payment')
                    ->assertSee('Payment processed successfully');

            // Step 5: Discharge
            $browser->visit('/patients/1/discharge')
                    ->type('discharge_notes', 'Patient discharged in good health')
                    ->press('Discharge Patient')
                    ->assertSee('Patient discharged successfully');
        });
    }
}
```

## 5. Vue.js Unit Tests with Jest and Vue Test Utils

### 5.1 Component Existence and Props Validation
```javascript
// tests/Vue/LoginComponent.test.js

import { mount } from '@vue/test-utils'
import LoginComponent from '@/components/auth/LoginComponent.vue'

describe('LoginComponent', () => {
  test('component exists and renders correctly', () => {
    const wrapper = mount(LoginComponent)
    expect(wrapper.exists()).toBe(true)
    expect(wrapper.find('form').exists()).toBe(true)
  })

  test('validates required props', () => {
    const wrapper = mount(LoginComponent, {
      props: {
        redirectUrl: '/dashboard'
      }
    })
    expect(wrapper.props('redirectUrl')).toBe('/dashboard')
  })

  test('displays validation errors for empty fields', async () => {
    const wrapper = mount(LoginComponent)
    
    await wrapper.find('form').trigger('submit')
    
    expect(wrapper.find('.error-message').exists()).toBe(true)
    expect(wrapper.text()).toContain('Email is required')
  })
})
```

### 5.2 DOM Interaction Tests
```javascript
// tests/Vue/BankSelectionComponent.test.js

import { mount } from '@vue/test-utils'
import BankSelectionComponent from '@/components/payment/BankSelectionComponent.vue'

describe('BankSelectionComponent', () => {
  const mockBanks = [
    { id: 1, name: 'Test Bank 1', code: 'TB1' },
    { id: 2, name: 'Test Bank 2', code: 'TB2' }
  ]

  test('renders bank options correctly', () => {
    const wrapper = mount(BankSelectionComponent, {
      props: {
        banks: mockBanks,
        selectedBankId: null
      }
    })

    const options = wrapper.findAll('option')
    expect(options).toHaveLength(mockBanks.length + 1) // +1 for default option
  })

  test('emits bank selection event', async () => {
    const wrapper = mount(BankSelectionComponent, {
      props: {
        banks: mockBanks,
        selectedBankId: null
      }
    })

    const select = wrapper.find('select')
    await select.setValue('1')

    expect(wrapper.emitted('bank-selected')).toBeTruthy()
    expect(wrapper.emitted('bank-selected')[0]).toEqual([1])
  })

  test('password visibility toggle works', async () => {
    const wrapper = mount(LoginComponent)
    
    const passwordInput = wrapper.find('input[type="password"]')
    const toggleButton = wrapper.find('.password-toggle')
    
    expect(passwordInput.attributes('type')).toBe('password')
    
    await toggleButton.trigger('click')
    
    expect(passwordInput.attributes('type')).toBe('text')
  })

  test('remember me checkbox functionality', async () => {
    const wrapper = mount(LoginComponent)
    
    const checkbox = wrapper.find('input[type="checkbox"]')
    
    expect(checkbox.element.checked).toBe(false)
    
    await checkbox.setChecked(true)
    
    expect(checkbox.element.checked).toBe(true)
    expect(wrapper.vm.rememberMe).toBe(true)
  })
})
```

### 5.3 API Call Mocks
```javascript
// tests/Vue/PatientRegistrationComponent.test.js

import { mount } from '@vue/test-utils'
import axios from 'axios'
import PatientRegistrationComponent from '@/components/patients/PatientRegistrationComponent.vue'

jest.mock('axios')
const mockedAxios = axios as jest.Mocked<typeof axios>

describe('PatientRegistrationComponent', () => {
  beforeEach(() => {
    mockedAxios.post.mockClear()
  })

  test('submits patient data to API', async () => {
    const mockResponse = {
      data: {
        id: 1,
        Firstname: 'John',
        Lastname: 'Doe'
      }
    }
    
    mockedAxios.post.mockResolvedValue(mockResponse)

    const wrapper = mount(PatientRegistrationComponent)

    await wrapper.find('input[name="Firstname"]').setValue('John')
    await wrapper.find('input[name="Lastname"]').setValue('Doe')
    await wrapper.find('input[name="phone"]').setValue('1234567890')
    await wrapper.find('select[name="gender"]').setValue('1')

    await wrapper.find('form').trigger('submit')

    expect(mockedAxios.post).toHaveBeenCalledWith('/api/patients', {
      Firstname: 'John',
      Lastname: 'Doe',
      phone: '1234567890',
      gender: '1'
    })
  })

  test('handles API error responses', async () => {
    const mockError = {
      response: {
        status: 422,
        data: {
          errors: {
            Firstname: ['The firstname field is required.']
          }
        }
      }
    }

    mockedAxios.post.mockRejectedValue(mockError)

    const wrapper = mount(PatientRegistrationComponent)

    await wrapper.find('form').trigger('submit')

    await wrapper.vm.$nextTick()

    expect(wrapper.find('.error-message').text()).toContain('The firstname field is required.')
  })
})
```

## 6. Performance and Security Test Scenarios

### 6.1 Load Testing
```javascript
// tests/Performance/LoadTest.js

import http from 'k6/http';
import { check, sleep } from 'k6';

export let options = {
  stages: [
    { duration: '2m', target: 100 }, // Ramp up to 100 users
    { duration: '5m', target: 100 }, // Stay at 100 users
    { duration: '2m', target: 0 },   // Ramp down to 0 users
  ],
};

export default function () {
  // Test login endpoint
  let loginResponse = http.post('http://localhost:8000/api/login', {
    email: 'test@example.com',
    password: 'password123'
  });

  check(loginResponse, {
    'login status is 200': (r) => r.status === 200,
    'login response time < 500ms': (r) => r.timings.duration < 500,
  });

  if (loginResponse.status === 200) {
    let token = loginResponse.json('token');
    
    // Test patient registration
    let patientResponse = http.post('http://localhost:8000/api/patients', {
      Firstname: 'Load Test',
      Lastname: 'Patient',
      gender: 1,
      phone: '1234567890'
    }, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    });

    check(patientResponse, {
      'patient creation status is 201': (r) => r.status === 201,
      'patient creation response time < 1000ms': (r) => r.timings.duration < 1000,
    });
  }

  sleep(1);
}
```

### 6.2 MySQL Query Performance Tests
```php
<?php
// tests/Performance/DatabasePerformanceTest.php

namespace Tests\Performance;

use Tests\TestCase;
use App\Models\Patient;
use App\Models\Caisse\FinancialTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class DatabasePerformanceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function patient_search_query_performs_within_acceptable_time()
    {
        // Create test data
        Patient::factory()->count(1000)->create();

        $startTime = microtime(true);

        // Test search query performance
        $results = Patient::where('Firstname', 'like', '%John%')
                         ->orWhere('Lastname', 'like', '%John%')
                         ->limit(50)
                         ->get();

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $this->assertLessThan(100, $executionTime, 'Patient search query took too long');
    }

    /** @test */
    public function financial_transaction_aggregation_performs_efficiently()
    {
        // Create test data
        $patients = Patient::factory()->count(100)->create();
        
        foreach ($patients as $patient) {
            FinancialTransaction::factory()->count(10)->create([
                'patient_id' => $patient->id
            ]);
        }

        $startTime = microtime(true);

        // Test aggregation query
        $results = DB::table('financial_transactions')
                    ->select('patient_id', DB::raw('SUM(amount) as total_amount'))
                    ->groupBy('patient_id')
                    ->having('total_amount', '>', 500)
                    ->get();

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        $this->assertLessThan(200, $executionTime, 'Aggregation query took too long');
    }
}
```

### 6.3 Security Tests
```php
<?php
// tests/Security/SecurityTest.php

namespace Tests\Security;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function api_endpoints_require_authentication()
    {
        $endpoints = [
            '/api/patients',
            '/api/appointments',
            '/api/financial-transactions-bulk-payment'
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            $this->assertEquals(401, $response->status(), "Endpoint {$endpoint} should require authentication");
        }
    }

    /** @test */
    public function sql_injection_protection_on_patient_search()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Attempt SQL injection
        $maliciousInput = "'; DROP TABLE patients; --";

        $response = $this->getJson("/api/patients?search=" . urlencode($maliciousInput));

        // Should not cause an error and should return safe results
        $response->assertStatus(200);
        
        // Verify patients table still exists
        $this->assertTrue(\Schema::hasTable('patients'));
    }

    /** @test */
    public function xss_protection_on_patient_data()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $xssPayload = '<script>alert("XSS")</script>';

        $response = $this->postJson('/api/patients', [
            'Firstname' => $xssPayload,
            'Lastname' => 'Test',
            'gender' => 1,
            'phone' => '1234567890'
        ]);

        if ($response->status() === 201) {
            $patient = $response->json();
            // Verify that the XSS payload is properly escaped
            $this->assertNotContains('<script>', $patient['Firstname']);
        }
    }

    /** @test */
    public function csrf_protection_is_enabled()
    {
        $response = $this->post('/api/patients', [
            'Firstname' => 'Test',
            'Lastname' => 'User'
        ]);

        // Should fail due to missing CSRF token
        $this->assertEquals(419, $response->status());
    }

    /** @test */
    public function sensitive_data_is_not_exposed_in_api_responses()
    {
        $user = User::factory()->create([
            'password' => bcrypt('secret123')
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/user');

        $userData = $response->json();
        
        // Ensure password is not included in response
        $this->assertArrayNotHasKey('password', $userData);
    }
}
```

## 7. Test File Organization Template

### 7.1 Directory Structure
```
tests/
├── Browser/                    # Laravel Dusk tests
│   ├── AuthenticationFlowTest.php
│   ├── PatientWorkflowTest.php
│   └── AppointmentSchedulingTest.php
├── Database/                   # Database-specific tests
│   ├── SchemaIntegrityTest.php
│   ├── TransactionRollbackTest.php
│   └── BankTransactionIntegrityTest.php
├── Feature/                    # Feature/Integration tests
│   ├── AuthenticationTest.php
│   ├── PatientRegistrationTest.php
│   ├── AppointmentSchedulingTest.php
│   ├── BillingTest.php
│   └── BulkPaymentBankTransactionTest.php
├── Performance/               # Performance tests
│   ├── LoadTest.js           # K6 load testing
│   └── DatabasePerformanceTest.php
├── Security/                  # Security tests
│   └── SecurityTest.php
├── Unit/                      # Unit tests
│   ├── Controllers/
│   │   └── FinancialTransactionControllerTest.php
│   ├── Models/
│   │   ├── PatientModelTest.php
│   │   └── FinancialTransactionModelTest.php
│   ├── Services/
│   │   └── FinancialTransactionServiceTest.php
│   └── BulkPaymentRequestTest.php
├── Vue/                       # Vue.js component tests
│   ├── LoginComponent.test.js
│   ├── PatientRegistrationComponent.test.js
│   ├── BankSelectionComponent.test.js
│   └── AppointmentSchedulingComponent.test.js
└── TestCase.php              # Base test case
```

## 8. Sample .env.testing Configuration

### 8.1 Environment Configuration
```env
# .env.testing

APP_NAME="HIS Testing"
APP_ENV=testing
APP_KEY=base64:your-testing-key-here
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://localhost:8000

# Database Configuration for Testing
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=his_testing
DB_USERNAME=root
DB_PASSWORD=

# Use in-memory database for faster tests (optional)
# DB_CONNECTION=sqlite
# DB_DATABASE=:memory:

# Cache Configuration
CACHE_STORE=array
SESSION_DRIVER=array
QUEUE_CONNECTION=sync

# Mail Configuration (use log driver for testing)
MAIL_MAILER=log

# Disable external services during testing
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local

# Testing-specific configurations
TELESCOPE_ENABLED=false
DEBUGBAR_ENABLED=false

# Bank API Testing (mock endpoints)
BANK_API_URL=http://localhost:8080/mock-bank-api
BANK_API_KEY=test-api-key

# Performance Testing Thresholds
MAX_QUERY_TIME=100
MAX_RESPONSE_TIME=500
```

### 8.2 PHPUnit Configuration
```xml
<!-- phpunit.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
        <testsuite name="Database">
            <directory>tests/Database</directory>
        </testsuite>
        <testsuite name="Security">
            <directory>tests/Security</directory>
        </testsuite>
        <testsuite name="Performance">
            <directory>tests/Performance</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>app</directory>
        </include>
    </source>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="APP_KEY" value="base64:your-testing-key-here"/>
        <env name="DB_CONNECTION" value="mysql"/>
        <env name="DB_DATABASE" value="his_testing"/>
        <env name="CACHE_DRIVER" value="array"/>
        <env name="SESSION_DRIVER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="MAIL_MAILER" value="log"/>
    </php>
</phpunit>
```

### 8.3 Jest Configuration for Vue.js Tests
```javascript
// jest.config.js
module.exports = {
  preset: '@vue/cli-plugin-unit-jest/presets/typescript-and-babel',
  testEnvironment: 'jsdom',
  moduleFileExtensions: ['js', 'json', 'vue', 'ts'],
  transform: {
    '^.+\\.vue$': '@vue/vue3-jest',
    '^.+\\.(js|jsx)$': 'babel-jest',
    '^.+\\.(ts|tsx)$': 'ts-jest'
  },
  moduleNameMapping: {
    '^@/(.*)$': '<rootDir>/resources/js/$1'
  },
  testMatch: [
    '**/tests/Vue/**/*.test.(js|jsx|ts|tsx)'
  ],
  collectCoverageFrom: [
    'resources/js/**/*.{js,vue}',
    '!resources/js/app.js',
    '!**/node_modules/**'
  ],
  coverageReporters: ['html', 'text-summary'],
  setupFilesAfterEnv: ['<rootDir>/tests/Vue/setup.js']
}
```

## 9. Test Execution Commands

### 9.1 Running Different Test Suites
```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
php artisan test --testsuite=Database
php artisan test --testsuite=Security

# Run tests with coverage
php artisan test --coverage

# Run Vue.js tests
npm run test:unit

# Run Vue.js tests with coverage
npm run test:unit -- --coverage

# Run Dusk tests
php artisan dusk

# Run performance tests with K6
k6 run tests/Performance/LoadTest.js

# Run specific test file
php artisan test tests/Feature/BulkPaymentBankTransactionTest.php

# Run tests in parallel (faster execution)
php artisan test --parallel
```

### 9.2 Continuous Integration Setup
```yaml
# .github/workflows/tests.yml
name: Tests

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: his_testing
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
        extensions: mbstring, dom, fileinfo, mysql
        
    - name: Install dependencies
      run: |
        composer install --no-progress --prefer-dist --optimize-autoloader
        npm ci
        
    - name: Setup environment
      run: |
        cp .env.testing .env
        php artisan key:generate
        
    - name: Run migrations
      run: php artisan migrate --force
      
    - name: Run PHPUnit tests
      run: php artisan test --coverage
      
    - name: Run Vue.js tests
      run: npm run test:unit
      
    - name: Run Dusk tests
      run: php artisan dusk
```

This comprehensive test plan provides a robust testing strategy for your Hospital Information System, covering all aspects from unit tests to end-to-end workflows, performance testing, and security validation.