<?php

namespace Tests\Feature;

use App\Models\Fournisseur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FournisseurTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_fournisseur_with_contacts()
    {
        $fournisseurData = [
            'company_name' => 'Test Supplier Inc.',
            'contact_person' => 'John Doe',
            'email' => 'john@testsupplier.com',
            'phone' => '+1234567890',
            'address' => '123 Test Street',
            'city' => 'Test City',
            'country' => 'Test Country',
            'tax_id' => 'TAX123456',
            'website' => 'https://testsupplier.com',
            'notes' => 'Test supplier notes',
            'is_active' => true,
            'contacts' => [
                [
                    'name' => 'Jane Smith',
                    'position' => 'Sales Manager',
                    'email' => 'jane@testsupplier.com',
                    'phone' => '+1234567891',
                    'mobile' => '+1234567892',
                    'is_primary' => true,
                ],
                [
                    'name' => 'Bob Johnson',
                    'position' => 'Technical Support',
                    'email' => 'bob@testsupplier.com',
                    'phone' => '+1234567893',
                    'mobile' => '+1234567894',
                    'is_primary' => false,
                ],
            ],
        ];

        $response = $this->postJson('/api/fournisseurs', $fournisseurData);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Fournisseur created successfully',
                'data' => [
                    'company_name' => 'Test Supplier Inc.',
                    'contact_person' => 'John Doe',
                    'email' => 'john@testsupplier.com',
                    'is_active' => true,
                ],
            ]);

        $this->assertDatabaseHas('fournisseurs', [
            'company_name' => 'Test Supplier Inc.',
            'contact_person' => 'John Doe',
            'email' => 'john@testsupplier.com',
        ]);

        $this->assertDatabaseHas('fournisseur_contacts', [
            'name' => 'Jane Smith',
            'position' => 'Sales Manager',
            'email' => 'jane@testsupplier.com',
            'is_primary' => true,
        ]);

        $this->assertDatabaseHas('fournisseur_contacts', [
            'name' => 'Bob Johnson',
            'position' => 'Technical Support',
            'email' => 'bob@testsupplier.com',
            'is_primary' => false,
        ]);
    }

    public function test_can_list_fournisseurs()
    {
        Fournisseur::factory()->count(3)->create();

        $response = $this->getJson('/api/fournisseurs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'company_name',
                        'contact_person',
                        'email',
                        'is_active',
                        'contacts',
                    ],
                ],
            ]);
    }

    public function test_can_search_fournisseurs()
    {
        Fournisseur::factory()->create(['company_name' => 'ABC Corp']);
        Fournisseur::factory()->create(['company_name' => 'XYZ Ltd']);

        $response = $this->getJson('/api/fournisseurs/search?q=ABC');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    [
                        'company_name' => 'ABC Corp',
                    ],
                ],
            ]);
    }

    public function test_can_get_active_fournisseurs()
    {
        Fournisseur::factory()->create(['is_active' => true]);
        Fournisseur::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/fournisseurs-active');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }
}
