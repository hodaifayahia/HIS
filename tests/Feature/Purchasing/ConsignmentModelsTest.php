<?php

namespace Tests\Feature\Purchasing;

use App\Models\Fournisseur;
use App\Models\Product;
use App\Models\Purchasing\ConsignmentReception;
use App\Models\Purchasing\ConsignmentReceptionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsignmentModelsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Fournisseur $supplier;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->supplier = Fournisseur::factory()->create();
        $this->product = Product::factory()->create();
    }

    /**
     * Test ConsignmentReception auto-generates code
     */
    public function test_consignment_reception_auto_generates_code(): void
    {
        $reception = ConsignmentReception::create([
            'fournisseur_id' => $this->supplier->id,
            'reception_date' => now(),
            'created_by' => $this->user->id,
        ]);

        $this->assertNotNull($reception->consignment_code);
        $this->assertStringStartsWith('CS-'.now()->year.'-', $reception->consignment_code);
    }

    /**
     * Test ConsignmentReception relationships
     */
    public function test_consignment_reception_has_relationships(): void
    {
        $reception = ConsignmentReception::create([
            'fournisseur_id' => $this->supplier->id,
            'reception_date' => now(),
            'created_by' => $this->user->id,
        ]);

        // Test fournisseur relationship
        $this->assertInstanceOf(Fournisseur::class, $reception->fournisseur);
        $this->assertEquals($this->supplier->id, $reception->fournisseur->id);

        // Test createdBy relationship
        $this->assertInstanceOf(User::class, $reception->createdBy);
        $this->assertEquals($this->user->id, $reception->createdBy->id);
    }

    /**
     * Test ConsignmentReception computed attributes
     */
    public function test_consignment_reception_computed_attributes(): void
    {
        $reception = ConsignmentReception::create([
            'fournisseur_id' => $this->supplier->id,
            'reception_date' => now(),
            'created_by' => $this->user->id,
        ]);

        $item = ConsignmentReceptionItem::create([
            'consignment_reception_id' => $reception->id,
            'product_id' => $this->product->id,
            'quantity_received' => 100,
            'quantity_consumed' => 20,
            'quantity_invoiced' => 10,
            'unit_price' => 50.00,
        ]);

        $reception->refresh();

        // Test total_received
        $this->assertEquals(100, $reception->total_received);

        // Test total_consumed
        $this->assertEquals(20, $reception->total_consumed);

        // Test total_uninvoiced
        $this->assertEquals(10, $reception->total_uninvoiced); // consumed(20) - invoiced(10)
    }

    /**
     * Test ConsignmentReceptionItem quantity tracking
     */
    public function test_consignment_item_quantity_tracking(): void
    {
        $reception = ConsignmentReception::create([
            'fournisseur_id' => $this->supplier->id,
            'reception_date' => now(),
            'created_by' => $this->user->id,
        ]);

        $item = ConsignmentReceptionItem::create([
            'consignment_reception_id' => $reception->id,
            'product_id' => $this->product->id,
            'quantity_received' => 100,
            'quantity_consumed' => 30,
            'quantity_invoiced' => 20,
            'unit_price' => 10.50,
        ]);

        // Test quantity_uninvoiced computed attribute
        $this->assertEquals(10, $item->quantity_uninvoiced);

        // Test total_value
        $this->assertEquals(1050.00, $item->total_value);

        // Test consumed_value
        $this->assertEquals(315.00, $item->consumed_value);

        // Test uninvoiced_value
        $this->assertEquals(105.00, $item->uninvoiced_value);
    }

    /**
     * Test ConsignmentReceptionItem helper methods
     */
    public function test_consignment_item_helper_methods(): void
    {
        $reception = ConsignmentReception::create([
            'fournisseur_id' => $this->supplier->id,
            'reception_date' => now(),
            'created_by' => $this->user->id,
        ]);

        $item = ConsignmentReceptionItem::create([
            'consignment_reception_id' => $reception->id,
            'product_id' => $this->product->id,
            'quantity_received' => 100,
            'quantity_consumed' => 30,
            'quantity_invoiced' => 20,
            'unit_price' => 10.00,
        ]);

        // Test canBeInvoiced
        $this->assertTrue($item->canBeInvoiced());

        // Test isFullyInvoiced
        $this->assertFalse($item->isFullyInvoiced());

        // Test isFullyConsumed
        $this->assertFalse($item->isFullyConsumed());

        // Update to full consumption
        $item->update(['quantity_consumed' => 100]);
        $this->assertTrue($item->isFullyConsumed());

        // Update to full invoice
        $item->update(['quantity_invoiced' => 100]);
        $this->assertTrue($item->isFullyInvoiced());
        $this->assertFalse($item->canBeInvoiced());
    }

    /**
     * Test ConsignmentReception has uninvoiced items
     */
    public function test_consignment_has_uninvoiced_items(): void
    {
        $reception = ConsignmentReception::create([
            'fournisseur_id' => $this->supplier->id,
            'reception_date' => now(),
            'created_by' => $this->user->id,
        ]);

        // No items yet
        $this->assertFalse($reception->hasUninvoicedItems());

        // Add item with uninvoiced quantity
        ConsignmentReceptionItem::create([
            'consignment_reception_id' => $reception->id,
            'product_id' => $this->product->id,
            'quantity_received' => 100,
            'quantity_consumed' => 50,
            'quantity_invoiced' => 30,
            'unit_price' => 10.00,
        ]);

        $reception->refresh();
        $this->assertTrue($reception->hasUninvoicedItems());
        $this->assertEquals(20, $reception->total_uninvoiced);
    }

    /**
     * Test ConsignmentReception scopes
     */
    public function test_consignment_reception_scopes(): void
    {
        $supplier2 = Fournisseur::factory()->create();

        $reception1 = ConsignmentReception::create([
            'fournisseur_id' => $this->supplier->id,
            'reception_date' => now(),
            'created_by' => $this->user->id,
        ]);

        $reception2 = ConsignmentReception::create([
            'fournisseur_id' => $supplier2->id,
            'reception_date' => now(),
            'created_by' => $this->user->id,
        ]);

        // Test bySupplier scope
        $results = ConsignmentReception::bySupplier($this->supplier->id)->get();
        $this->assertCount(1, $results);
        $this->assertEquals($reception1->id, $results->first()->id);
    }
}
