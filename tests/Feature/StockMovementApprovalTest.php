<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\StockMovement;
use App\Models\StockMovementItem;
use App\Models\Product;
use App\Models\CONFIGURATION\Service;
use App\Models\UserSpecialization;
use App\Models\Specialization;
use App\Events\StockMovementItemApproved;
use App\Events\StockMovementItemRejected;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class StockMovementApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $service;
    protected $movement;
    protected $items;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->user = User::factory()->create();
        $this->service = Service::factory()->create();
        
        // Create specialization and link user
        $specialization = Specialization::factory()->create([
            'service_id' => $this->service->id
        ]);
        
        UserSpecialization::create([
            'user_id' => $this->user->id,
            'specialization_id' => $specialization->id,
            'status' => 'active'
        ]);
        
        // Create stock movement
        $this->movement = StockMovement::factory()->create([
            'providing_service_id' => $this->service->id,
            'status' => 'pending'
        ]);
        
        // Create stock movement items
        $this->items = StockMovementItem::factory()->count(3)->create([
            'stock_movement_id' => $this->movement->id,
            'requested_quantity' => 10,
            'approved_quantity' => null
        ]);
    }

    public function test_can_approve_items_successfully()
    {
        Event::fake();
        
        $this->actingAs($this->user)
             ->postJson("/api/stock-movements/{$this->movement->id}/approve", [
                 'item_ids' => [$this->items[0]->id, $this->items[1]->id]
             ])
             ->assertStatus(200)
             ->assertJson([
                 'success' => true,
                 'message' => '2 items approved successfully'
             ]);
        
        // Check items are approved
        $this->items[0]->refresh();
        $this->items[1]->refresh();
        $this->items[2]->refresh();
        
        $this->assertEquals(10, $this->items[0]->approved_quantity);
        $this->assertEquals(10, $this->items[1]->approved_quantity);
        $this->assertNull($this->items[2]->approved_quantity);
        
        // Check movement status
        $this->movement->refresh();
        $this->assertEquals('partially_approved', $this->movement->status);
        
        // Check events were dispatched
        Event::assertDispatched(StockMovementItemApproved::class, 2);
    }

    public function test_can_reject_items_with_reason()
    {
        Event::fake();
        
        $rejectionReason = 'Insufficient stock available';
        
        $this->actingAs($this->user)
             ->postJson("/api/stock-movements/{$this->movement->id}/reject", [
                 'item_ids' => [$this->items[0]->id],
                 'rejection_reason' => $rejectionReason
             ])
             ->assertStatus(200)
             ->assertJson([
                 'success' => true,
                 'message' => '1 items rejected successfully'
             ]);
        
        // Check item is rejected
        $this->items[0]->refresh();
        $this->assertEquals(0, $this->items[0]->approved_quantity);
        $this->assertStringContainsString($rejectionReason, $this->items[0]->notes);
        
        // Check event was dispatched
        Event::assertDispatched(StockMovementItemRejected::class, function ($event) use ($rejectionReason) {
            return $event->item->id === $this->items[0]->id && 
                   $event->rejectionReason === $rejectionReason;
        });
    }

    public function test_cannot_modify_already_processed_items()
    {
        // Pre-approve an item
        $this->items[0]->update(['approved_quantity' => 5]);
        
        $this->actingAs($this->user)
             ->postJson("/api/stock-movements/{$this->movement->id}/approve", [
                 'item_ids' => [$this->items[0]->id]
             ])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['item_ids']);
    }

    public function test_validation_prevents_invalid_item_ids()
    {
        $this->actingAs($this->user)
             ->postJson("/api/stock-movements/{$this->movement->id}/approve", [
                 'item_ids' => [999, 1000] // Non-existent IDs
             ])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['item_ids']);
    }

    public function test_item_status_methods_work_correctly()
    {
        $item = $this->items[0];
        
        // Initially pending
        $this->assertTrue($item->isPending());
        $this->assertFalse($item->isApproved());
        $this->assertFalse($item->isRejected());
        $this->assertTrue($item->isEditable());
        $this->assertEquals('pending', $item->getStatus());
        
        // After approval
        $item->update(['approved_quantity' => 10]);
        $this->assertFalse($item->isPending());
        $this->assertTrue($item->isApproved());
        $this->assertFalse($item->isRejected());
        $this->assertFalse($item->isEditable());
        $this->assertEquals('approved', $item->getStatus());
        
        // After rejection
        $item->update(['approved_quantity' => 0]);
        $this->assertFalse($item->isPending());
        $this->assertFalse($item->isApproved());
        $this->assertTrue($item->isRejected());
        $this->assertFalse($item->isEditable());
        $this->assertEquals('rejected', $item->getStatus());
    }

    public function test_movement_statistics_are_accurate()
    {
        // Approve one item, reject one, leave one pending
        $this->items[0]->update(['approved_quantity' => 10]);
        $this->items[1]->update(['approved_quantity' => 0]);
        // items[2] remains pending
        
        $service = app(\App\Services\StockMovementApprovalService::class);
        $stats = $service->getMovementStatistics($this->movement);
        
        $this->assertEquals(3, $stats['total_items']);
        $this->assertEquals(1, $stats['pending_items']);
        $this->assertEquals(1, $stats['approved_items']);
        $this->assertEquals(1, $stats['rejected_items']);
        $this->assertEquals(1, $stats['editable_items']);
    }

    public function test_unauthorized_user_cannot_access_movement()
    {
        $otherUser = User::factory()->create();
        
        $this->actingAs($otherUser)
             ->postJson("/api/stock-movements/{$this->movement->id}/approve", [
                 'item_ids' => [$this->items[0]->id]
             ])
             ->assertStatus(403);
    }
}