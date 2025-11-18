<?php

namespace Tests\Feature\Purchasing;

use App\Events\Purchasing\ConsignmentConsumptionEvent;
use App\Events\Purchasing\ConsignmentReceivedEvent;
use App\Models\Fournisseur;
use App\Models\Product;
use App\Models\Purchasing\ConsignmentReception;
use App\Models\Purchasing\ConsignmentReceptionItem;
use App\Models\Reception\ficheNavette;
use App\Models\Reception\ficheNavetteItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsignmentEventsTest extends TestCase
{
    use RefreshDatabase;

    private function createTestFicheItem($ficheId, $consignmentItemId): ficheNavetteItem
    {
        return ficheNavetteItem::create([
            'fiche_navette_id' => $ficheId,
            'consignment_item_id' => $consignmentItemId,
            'status' => 'pending',
            'final_price' => 100,
            'payment_status' => 'pending',
        ]);
    }

    public function test_consignment_received_event_has_correct_structure(): void
    {
        $user = User::factory()->create();
        $supplier = Fournisseur::factory()->create();

        $consignment = ConsignmentReception::create([
            'fournisseur_id' => $supplier->id,
            'reception_date' => now(),
            'created_by' => $user->id,
        ]);

        $event = new ConsignmentReceivedEvent($consignment);
        $broadcastData = $event->broadcastWith();

        $this->assertArrayHasKey('consignment_id', $broadcastData);
        $this->assertArrayHasKey('consignment_code', $broadcastData);
        $this->assertArrayHasKey('supplier_id', $broadcastData);
        $this->assertArrayHasKey('total_received', $broadcastData);
        $this->assertEquals($consignment->id, $broadcastData['consignment_id']);
        $this->assertEquals($supplier->id, $broadcastData['supplier_id']);
    }

    public function test_consignment_received_event_broadcasts_on_supplier_channel(): void
    {
        $user = User::factory()->create();
        $supplier = Fournisseur::factory()->create();

        $consignment = ConsignmentReception::create([
            'fournisseur_id' => $supplier->id,
            'reception_date' => now(),
            'created_by' => $user->id,
        ]);

        $event = new ConsignmentReceivedEvent($consignment);
        $channel = $event->broadcastOn();

        $this->assertEquals('private-consignments.'.$supplier->id, $channel->name);
    }

    public function test_consignment_consumption_event_has_correct_structure(): void
    {
        $user = User::factory()->create();
        $supplier = Fournisseur::factory()->create();
        $product = Product::factory()->create();

        $consignment = ConsignmentReception::create([
            'fournisseur_id' => $supplier->id,
            'reception_date' => now(),
            'created_by' => $user->id,
        ]);

        $consignmentItem = ConsignmentReceptionItem::create([
            'consignment_reception_id' => $consignment->id,
            'product_id' => $product->id,
            'quantity_received' => 100,
            'unit_price' => 50.00,
        ]);

        $fiche = ficheNavette::factory()->create();
        $ficheItem = $this->createTestFicheItem($fiche->id, $consignmentItem->id);

        $event = new ConsignmentConsumptionEvent($consignmentItem, $ficheItem, 1);
        $broadcastData = $event->broadcastWith();

        $this->assertArrayHasKey('consignment_id', $broadcastData);
        $this->assertArrayHasKey('consignment_item_id', $broadcastData);
        $this->assertArrayHasKey('product_id', $broadcastData);
        $this->assertArrayHasKey('quantity_consumed', $broadcastData);
        $this->assertArrayHasKey('quantity_uninvoiced', $broadcastData);
        $this->assertEquals(1, $broadcastData['quantity_consumed']);
    }

    public function test_consignment_consumption_event_broadcasts_on_supplier_channel(): void
    {
        $user = User::factory()->create();
        $supplier = Fournisseur::factory()->create();
        $product = Product::factory()->create();

        $consignment = ConsignmentReception::create([
            'fournisseur_id' => $supplier->id,
            'reception_date' => now(),
            'created_by' => $user->id,
        ]);

        $consignmentItem = ConsignmentReceptionItem::create([
            'consignment_reception_id' => $consignment->id,
            'product_id' => $product->id,
            'quantity_received' => 100,
            'unit_price' => 50.00,
        ]);

        $fiche = ficheNavette::factory()->create();
        $ficheItem = $this->createTestFicheItem($fiche->id, $consignmentItem->id);

        $event = new ConsignmentConsumptionEvent($consignmentItem, $ficheItem, 1);
        $channel = $event->broadcastOn();

        $this->assertEquals('private-consignments.'.$supplier->id, $channel->name);
    }

    public function test_events_use_correct_broadcast_names(): void
    {
        $user = User::factory()->create();
        $supplier = Fournisseur::factory()->create();
        $product = Product::factory()->create();

        $consignment = ConsignmentReception::create([
            'fournisseur_id' => $supplier->id,
            'reception_date' => now(),
            'created_by' => $user->id,
        ]);

        $receivedEvent = new ConsignmentReceivedEvent($consignment);
        $this->assertEquals('consignment.received', $receivedEvent->broadcastAs());

        $consignmentItem = ConsignmentReceptionItem::create([
            'consignment_reception_id' => $consignment->id,
            'product_id' => $product->id,
            'quantity_received' => 100,
            'unit_price' => 50.00,
        ]);

        $fiche = ficheNavette::factory()->create();
        $ficheItem = $this->createTestFicheItem($fiche->id, $consignmentItem->id);

        $consumptionEvent = new ConsignmentConsumptionEvent($consignmentItem, $ficheItem);
        $this->assertEquals('consignment.consumption', $consumptionEvent->broadcastAs());
    }
}
