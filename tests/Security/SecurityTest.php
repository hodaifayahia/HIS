<?php

namespace Tests\Security;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\User;
use App\Models\Patient;
use Laravel\Sanctum\Sanctum;

class SecurityTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function it_prevents_sql_injection_in_patient_search()
    {
        $maliciousInput = "'; DROP TABLE patients; --";
        
        $response = $this->getJson("/api/patients?search={$maliciousInput}");
        
        $response->assertStatus(200);
        
        // Verify that patients table still exists by creating a patient
        $patient = Patient::factory()->create();
        $this->assertDatabaseHas('patients', ['id' => $patient->id]);
    }

    /** @test */
    public function it_prevents_sql_injection_in_patient_filter()
    {
        $maliciousInput = "1' OR '1'='1";
        
        $response = $this->getJson("/api/patients?id={$maliciousInput}");
        
        $response->assertStatus(200);
        // Should not return all patients due to SQL injection
        $this->assertLessThanOrEqual(1, count($response->json()));
    }

    /** @test */
    public function it_sanitizes_xss_in_patient_registration()
    {
        $xssPayload = '<script>alert("XSS")</script>';
        
        $response = $this->postJson('/api/patients', [
            'first_name' => $xssPayload,
            'last_name' => 'Test',
            'email' => 'test@example.com',
            'phone' => '+1234567890',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'address' => 'Test Address'
        ]);

        if ($response->status() === 201) {
            $patient = $response->json();
            $this->assertStringNotContainsString('<script>', $patient['first_name']);
            $this->assertStringNotContainsString('alert', $patient['first_name']);
        }
    }

    /** @test */
    public function it_validates_email_format_in_patient_registration()
    {
        $invalidEmails = [
            'invalid-email',
            'test@',
            '@example.com',
            'test..test@example.com',
            'test@example',
            '<script>alert("xss")</script>@example.com'
        ];

        foreach ($invalidEmails as $email) {
            $response = $this->postJson('/api/patients', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => $email,
                'phone' => '+1234567890',
                'date_of_birth' => '1990-01-01',
                'gender' => 'male'
            ]);

            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['email']);
        }
    }

    /** @test */
    public function it_validates_phone_number_format()
    {
        $invalidPhones = [
            'invalid-phone',
            '123',
            '+123456789012345678901234567890', // Too long
            '<script>alert("xss")</script>',
            'DROP TABLE patients;'
        ];

        foreach ($invalidPhones as $phone) {
            $response = $this->postJson('/api/patients', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test@example.com',
                'phone' => $phone,
                'date_of_birth' => '1990-01-01',
                'gender' => 'male'
            ]);

            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['phone']);
        }
    }

    /** @test */
    public function it_prevents_mass_assignment_vulnerabilities()
    {
        $response = $this->postJson('/api/patients', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'phone' => '+1234567890',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'is_admin' => true, // Attempting mass assignment
            'role' => 'admin',  // Attempting mass assignment
            'created_at' => '2020-01-01', // Attempting mass assignment
        ]);

        if ($response->status() === 201) {
            $patient = Patient::find($response->json()['id']);
            $this->assertNull($patient->is_admin ?? null);
            $this->assertNull($patient->role ?? null);
        }
    }

    /** @test */
    public function it_requires_authentication_for_protected_routes()
    {
        $this->withoutMiddleware(\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class);
        
        $protectedRoutes = [
            ['GET', '/api/patients'],
            ['POST', '/api/patients'],
            ['GET', '/api/appointments'],
            ['POST', '/api/appointments'],
            ['GET', '/api/medical-records'],
            ['POST', '/api/medical-records'],
            ['GET', '/api/invoices'],
            ['POST', '/api/invoices'],
        ];

        foreach ($protectedRoutes as [$method, $route]) {
            $response = $this->json($method, $route);
            $this->assertContains($response->status(), [401, 403], 
                "Route {$method} {$route} should require authentication");
        }
    }

    /** @test */
    public function it_prevents_unauthorized_access_to_patient_data()
    {
        $otherUser = User::factory()->create();
        $patient = Patient::factory()->create(['created_by' => $otherUser->id]);

        // Current user should not access other user's patient data
        $response = $this->getJson("/api/patients/{$patient->id}");
        
        // Depending on your authorization logic, this should be 403 or 404
        $this->assertContains($response->status(), [403, 404]);
    }

    /** @test */
    public function it_validates_date_inputs_to_prevent_injection()
    {
        $maliciousDates = [
            "'; DROP TABLE appointments; --",
            '1990-01-01\'; DROP TABLE patients; --',
            '<script>alert("xss")</script>',
            '1990-13-45', // Invalid date
            'invalid-date',
        ];

        foreach ($maliciousDates as $date) {
            $response = $this->postJson('/api/appointments', [
                'patient_id' => Patient::factory()->create()->id,
                'doctor_id' => User::factory()->create()->id,
                'appointment_date' => $date,
                'appointment_time' => '10:00',
                'appointment_type' => 'consultation'
            ]);

            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['appointment_date']);
        }
    }

    /** @test */
    public function it_prevents_path_traversal_in_file_uploads()
    {
        $maliciousPaths = [
            '../../../etc/passwd',
            '..\\..\\..\\windows\\system32\\config\\sam',
            '/etc/passwd',
            'C:\\windows\\system32\\config\\sam',
        ];

        foreach ($maliciousPaths as $path) {
            $response = $this->postJson('/api/medical-records', [
                'patient_id' => Patient::factory()->create()->id,
                'record_type' => 'lab_result',
                'file_path' => $path,
                'description' => 'Test record'
            ]);

            // Should either reject the request or sanitize the path
            if ($response->status() === 201) {
                $record = $response->json();
                $this->assertStringNotContainsString('..', $record['file_path']);
                $this->assertStringNotContainsString('/etc/', $record['file_path']);
                $this->assertStringNotContainsString('\\windows\\', $record['file_path']);
            } else {
                $response->assertStatus(422);
            }
        }
    }

    /** @test */
    public function it_validates_numeric_inputs_to_prevent_overflow()
    {
        $maliciousNumbers = [
            PHP_INT_MAX + 1,
            -PHP_INT_MAX - 1,
            'NaN',
            'Infinity',
            '-Infinity',
            '1e308', // Very large number
        ];

        foreach ($maliciousNumbers as $number) {
            $response = $this->postJson('/api/invoices', [
                'patient_id' => Patient::factory()->create()->id,
                'total_amount' => $number,
                'items' => [
                    [
                        'description' => 'Test item',
                        'quantity' => 1,
                        'unit_price' => 100,
                        'total' => 100
                    ]
                ]
            ]);

            $response->assertStatus(422);
        }
    }

    /** @test */
    public function it_prevents_ldap_injection()
    {
        $ldapPayloads = [
            '*)(uid=*))(|(uid=*',
            '*)(|(password=*))',
            '*)(&(objectClass=user)(uid=admin))',
        ];

        foreach ($ldapPayloads as $payload) {
            $response = $this->postJson('/api/auth/login', [
                'email' => $payload,
                'password' => 'password'
            ]);

            $response->assertStatus(422);
        }
    }

    /** @test */
    public function it_prevents_command_injection_in_search()
    {
        $commandPayloads = [
            '; ls -la',
            '| cat /etc/passwd',
            '&& rm -rf /',
            '`whoami`',
            '$(id)',
        ];

        foreach ($commandPayloads as $payload) {
            $response = $this->getJson("/api/patients?search={$payload}");
            
            $response->assertStatus(200);
            // Response should not contain system command output
            $responseContent = $response->getContent();
            $this->assertStringNotContainsString('root:', $responseContent);
            $this->assertStringNotContainsString('uid=', $responseContent);
            $this->assertStringNotContainsString('gid=', $responseContent);
        }
    }

    /** @test */
    public function it_validates_json_input_structure()
    {
        $maliciousJson = [
            ['deeply' => ['nested' => ['array' => ['with' => ['too' => ['many' => ['levels' => 'value']]]]]]],
            str_repeat('a', 1000000), // Very large string
            array_fill(0, 10000, 'item'), // Very large array
        ];

        foreach ($maliciousJson as $payload) {
            $response = $this->postJson('/api/patients', $payload);
            
            // Should handle malicious JSON gracefully
            $this->assertContains($response->status(), [400, 422, 413]);
        }
    }

    /** @test */
    public function it_implements_rate_limiting()
    {
        // Make multiple rapid requests to test rate limiting
        $responses = [];
        for ($i = 0; $i < 100; $i++) {
            $responses[] = $this->postJson('/api/auth/login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword'
            ]);
        }

        // At least some requests should be rate limited
        $rateLimitedCount = collect($responses)->filter(function ($response) {
            return $response->status() === 429;
        })->count();

        $this->assertGreaterThan(0, $rateLimitedCount, 'Rate limiting should be implemented');
    }

    /** @test */
    public function it_sanitizes_html_content_in_text_fields()
    {
        $htmlPayloads = [
            '<img src=x onerror=alert("XSS")>',
            '<iframe src="javascript:alert(\'XSS\')"></iframe>',
            '<svg onload=alert("XSS")>',
            '<body onload=alert("XSS")>',
        ];

        foreach ($htmlPayloads as $payload) {
            $response = $this->postJson('/api/medical-records', [
                'patient_id' => Patient::factory()->create()->id,
                'record_type' => 'note',
                'description' => $payload,
                'content' => $payload
            ]);

            if ($response->status() === 201) {
                $record = $response->json();
                $this->assertStringNotContainsString('<script', $record['description']);
                $this->assertStringNotContainsString('onerror=', $record['description']);
                $this->assertStringNotContainsString('onload=', $record['description']);
                $this->assertStringNotContainsString('javascript:', $record['description']);
            }
        }
    }

    /** @test */
    public function it_validates_file_upload_types()
    {
        $maliciousFiles = [
            'test.php',
            'test.exe',
            'test.bat',
            'test.sh',
            'test.js',
        ];

        foreach ($maliciousFiles as $filename) {
            $response = $this->postJson('/api/medical-records/upload', [
                'patient_id' => Patient::factory()->create()->id,
                'file' => $filename,
                'record_type' => 'document'
            ]);

            // Should reject executable file types
            $response->assertStatus(422);
        }
    }
}