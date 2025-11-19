<?php

namespace Tests\Feature\Purchasing;

use App\Models\Fournisseur;
use App\Models\Product;
use App\Models\Purchasing\ConsignmentReception;
use App\Models\Purchasing\ConsignmentReceptionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsignmentControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Fournisseur $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->supplier = Fournisseur::factory()->create();
    }

    public function test_can_list_consignments(): void
    {
        $this->withoutMiddleware();
        $this->actingAs($this->user);

        ConsignmentReception::factory()->count(3)->create([
            'fournisseur_id' => $this->supplier->id,
        ]);

        $response = $this->getJson('/api/consignments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'consignment_code', 'fournisseur', 'reception_date'],
                ],
                'meta',
            ]);
    }

    public function test_can_create_consignment(): void
    {
        $this->withoutMiddleware();
        $this->actingAs($this->user);
        $product = Product::factory()->create();

        $data = [
            'fournisseur_id' => $this->supplier->id,
            'reception_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity_received' => 100,
                    'unit_price' => 50.00,
                ],
            ],
        ];

        $response = $this->postJson('/api/consignments', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'consignment_code', 'items'],
                'message',
            ]);

        $this->assertDatabaseHas('consignment_receptions', [
            'fournisseur_id' => $this->supplier->id,
        ]);
    }

    public function test_can_view_consignment_details(): void
    {
        $this->withoutMiddleware();
        $this->actingAs($this->user);

        $consignment = ConsignmentReception::create([
            'fournisseur_id' => $this->supplier->id,
            'reception_date' => now(),
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson("/api/consignments/{$consignment->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['id', 'consignment_code', 'fournisseur', 'items'],
            ]);
    }

    public function test_can_get_uninvoiced_items(): void
    {
        $this->withoutMiddleware();
        $this->actingAs($this->user);
        $product = Product::factory()->create();

        $consignment = ConsignmentReception::create([
            'fournisseur_id' => $this->supplier->id,
            'reception_date' => now(),
            'created_by' => $this->user->id,
        ]);

        ConsignmentReceptionItem::create([
            'consignment_reception_id' => $consignment->id,
            'product_id' => $product->id,
            'quantity_received' => 100,
            'quantity_consumed' => 50,
            'quantity_invoiced' => 20,
            'unit_price' => 50.00,
        ]);

        $response = $this->getJson("/api/consignments/{$consignment->id}/uninvoiced-items");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'product_id', 'quantity_uninvoiced', 'uninvoiced_value'],
                ],
            ]);
    }

    public function test_validation_fails_for_invalid_consignment_data(): void
    {
        $this->withoutMiddleware();
        $this->actingAs($this->user);

        $data = [
            'fournisseur_id' => null, // Required
            'reception_date' => 'invalid-date',
            'items' => [], // Must have at least one item
        ];

        $response = $this->postJson('/api/consignments', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['fournisseur_id', 'reception_date', 'items']);
    }
}
