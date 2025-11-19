<?php

namespace Tests\Unit\Services;

use App\Models\CONFIGURATION\Service;
use App\Models\ServiceDemendPurchcing;
use App\Models\User;
use App\Services\ServiceDemandListService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceDemandListServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ServiceDemandListService $service;

    protected User $adminUser;

    protected User $regularUser;

    protected Service $service1;

    protected Service $service2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ServiceDemandListService;

        // Create test users
        $this->adminUser = User::factory()->create(['role' => 'admin']);
        $this->regularUser = User::factory()->create(['role' => 'user']);

        // Create test services
        $this->service1 = Service::factory()->create(['name' => 'Pharmacy Service', 'is_active' => true]);
        $this->service2 = Service::factory()->create(['name' => 'Stock Service', 'is_active' => true]);
    }

    /** @test */
    public function it_retrieves_all_demands_with_default_sent_and_approved_status()
    {
        // Create demands with different statuses
        ServiceDemendPurchcing::factory()->create(['status' => 'draft', 'created_by' => $this->adminUser->id]);
        ServiceDemendPurchcing::factory()->create(['status' => 'sent', 'created_by' => $this->adminUser->id]);
        ServiceDemendPurchcing::factory()->create(['status' => 'approved', 'created_by' => $this->adminUser->id]);
        ServiceDemendPurchcing::factory()->create(['status' => 'rejected', 'created_by' => $this->adminUser->id]);

        // Act as admin
        $this->actingAs($this->adminUser);

        // Get demands without status filter
        $result = $this->service->getAll(['page' => 1, 'per_page' => 15]);

        // Assert: Should only have 'sent' and 'approved'
        $this->assertEquals(2, $result->total());
        $statuses = $result->pluck('status')->toArray();
        $this->assertContains('sent', $statuses);
        $this->assertContains('approved', $statuses);
        $this->assertNotContains('draft', $statuses);
        $this->assertNotContains('rejected', $statuses);
    }

    /** @test */
    public function it_filters_by_specific_status_when_provided()
    {
        // Create demands
        ServiceDemendPurchcing::factory()->create(['status' => 'draft', 'created_by' => $this->adminUser->id]);
        ServiceDemendPurchcing::factory()->create(['status' => 'sent', 'created_by' => $this->adminUser->id]);
        ServiceDemendPurchcing::factory()->create(['status' => 'approved', 'created_by' => $this->adminUser->id]);

        // Act as admin
        $this->actingAs($this->adminUser);

        // Get demands with specific status
        $result = $this->service->getAll(['status' => 'draft', 'page' => 1, 'per_page' => 15]);

        // Assert: Should only have 'draft'
        $this->assertEquals(1, $result->total());
        $this->assertEquals('draft', $result->first()->status);
    }

    /** @test */
    public function it_respects_authorization_for_non_admin_users()
    {
        // Create demands from different users
        ServiceDemendPurchcing::factory()->count(3)->create(['status' => 'sent', 'created_by' => $this->adminUser->id]);
        ServiceDemendPurchcing::factory()->count(2)->create(['status' => 'sent', 'created_by' => $this->regularUser->id]);

        // Act as regular user
        $this->actingAs($this->regularUser);

        // Get demands
        $result = $this->service->getAll(['page' => 1, 'per_page' => 15]);

        // Assert: Regular user should only see their own demands
        $this->assertEquals(2, $result->total());
        foreach ($result as $demand) {
            $this->assertEquals($this->regularUser->id, $demand->created_by);
        }
    }

    /** @test */
    public function it_filters_by_service()
    {
        // Create demands for different services
        ServiceDemendPurchcing::factory()->count(3)->create([
            'status' => 'sent',
            'service_id' => $this->service1->id,
            'created_by' => $this->adminUser->id,
        ]);
        ServiceDemendPurchcing::factory()->count(2)->create([
            'status' => 'sent',
            'service_id' => $this->service2->id,
            'created_by' => $this->adminUser->id,
        ]);

        // Act as admin
        $this->actingAs($this->adminUser);

        // Get demands for specific service
        $result = $this->service->getAll(['service_id' => $this->service1->id, 'page' => 1, 'per_page' => 15]);

        // Assert: Should only have demands for service1
        $this->assertEquals(3, $result->total());
        foreach ($result as $demand) {
            $this->assertEquals($this->service1->id, $demand->service_id);
        }
    }

    /** @test */
    public function it_searches_by_demand_code()
    {
        // Create demands
        ServiceDemendPurchcing::factory()->create([
            'status' => 'sent',
            'demand_code' => 'SD-2025-001',
            'created_by' => $this->adminUser->id,
        ]);
        ServiceDemendPurchcing::factory()->create([
            'status' => 'sent',
            'demand_code' => 'SD-2025-002',
            'created_by' => $this->adminUser->id,
        ]);

        // Act as admin
        $this->actingAs($this->adminUser);

        // Search for specific code
        $result = $this->service->getAll(['search' => 'SD-2025-001', 'page' => 1, 'per_page' => 15]);

        // Assert: Should find the demand
        $this->assertEquals(1, $result->total());
        $this->assertStringContainsString('SD-2025-001', $result->first()->demand_code);
    }

    /** @test */
    public function it_searches_by_notes()
    {
        // Create demands
        ServiceDemendPurchcing::factory()->create([
            'status' => 'sent',
            'notes' => 'Urgent pharmacy order',
            'created_by' => $this->adminUser->id,
        ]);
        ServiceDemendPurchcing::factory()->create([
            'status' => 'sent',
            'notes' => 'Regular stock order',
            'created_by' => $this->adminUser->id,
        ]);

        // Act as admin
        $this->actingAs($this->adminUser);

        // Search for specific note
        $result = $this->service->getAll(['search' => 'pharmacy', 'page' => 1, 'per_page' => 15]);

        // Assert: Should find the demand with matching notes
        $this->assertEquals(1, $result->total());
        $this->assertStringContainsString('pharmacy', $result->first()->notes);
    }

    /** @test */
    public function it_returns_available_statuses()
    {
        $statuses = $this->service->getAvailableStatuses();

        // Assert: Should have all expected statuses
        $this->assertArrayHasKey('draft', $statuses);
        $this->assertArrayHasKey('sent', $statuses);
        $this->assertArrayHasKey('approved', $statuses);
        $this->assertArrayHasKey('rejected', $statuses);
        $this->assertArrayHasKey('factureprofram', $statuses);
        $this->assertArrayHasKey('boncommend', $statuses);
        $this->assertArrayHasKey('received', $statuses);
        $this->assertArrayHasKey('cancelled', $statuses);
    }

    /** @test */
    public function it_paginates_results_correctly()
    {
        // Create 30 demands
        ServiceDemendPurchcing::factory()->count(30)->create([
            'status' => 'sent',
            'created_by' => $this->adminUser->id,
        ]);

        // Act as admin
        $this->actingAs($this->adminUser);

        // Get first page with 10 per page
        $result1 = $this->service->getAll(['page' => 1, 'per_page' => 10]);
        $result2 = $this->service->getAll(['page' => 2, 'per_page' => 10]);

        // Assert
        $this->assertEquals(10, $result1->count());
        $this->assertEquals(10, $result2->count());
        $this->assertEquals(30, $result1->total());
        $this->assertEquals(1, $result1->currentPage());
        $this->assertEquals(2, $result2->currentPage());
        $this->assertEquals(3, $result1->lastPage());
    }

    /** @test */
    public function it_combines_multiple_filters()
    {
        // Create demands
        ServiceDemendPurchcing::factory()->count(5)->create([
            'status' => 'sent',
            'service_id' => $this->service1->id,
            'notes' => 'Pharmacy related',
            'created_by' => $this->adminUser->id,
        ]);
        ServiceDemendPurchcing::factory()->count(3)->create([
            'status' => 'approved',
            'service_id' => $this->service1->id,
            'notes' => 'Stock related',
            'created_by' => $this->adminUser->id,
        ]);

        // Act as admin
        $this->actingAs($this->adminUser);

        // Get demands with combined filters
        $result = $this->service->getAll([
            'search' => 'pharmacy',
            'service_id' => $this->service1->id,
            'status' => 'sent',
            'page' => 1,
            'per_page' => 15,
        ]);

        // Assert: Should only have sent demands for service1 with pharmacy notes
        $this->assertEquals(5, $result->total());
        foreach ($result as $demand) {
            $this->assertEquals('sent', $demand->status);
            $this->assertEquals($this->service1->id, $demand->service_id);
            $this->assertStringContainsString('pharmacy', $demand->notes);
        }
    }
}
