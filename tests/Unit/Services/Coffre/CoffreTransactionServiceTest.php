<?php

namespace Tests\Unit\Services\Coffre;

use Tests\TestCase;
use App\Models\User;
use App\Models\Coffre\Coffre;
use App\Models\Coffre\Caisse;
use App\Models\Coffre\CaisseSession;
use App\Models\Coffre\CoffreTransaction;
use App\Services\Coffre\CoffreTransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class CoffreTransactionServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private CoffreTransactionService $service;
    private User $user;
    private Coffre $coffre;
    private Caisse $caisse;
    private CaisseSession $session;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new CoffreTransactionService();
        
        // Create test user
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);

        // Create test coffre
        $this->coffre = Coffre::create([
            'name' => 'Test Coffre',
            'location' => 'Test Location',
            'current_balance' => 10000.00,
            'responsible_user_id' => $this->user->id,
            'is_active' => true
        ]);

        // Create test caisse
        $this->caisse = Caisse::create([
            'name' => 'Test Caisse',
            'location' => 'Test Location',
            'is_active' => true
        ]);

        // Create test caisse session
        $this->session = CaisseSession::create([
            'caisse_id' => $this->caisse->id,
            'user_id' => $this->user->id,
            'open_by' => $this->user->id,
            'status' => 'open',
            'opening_amount' => 1000.00,
            'opened_at' => now(),
        ]);

        $this->createTestTransactions();
    }

    private function createTestTransactions(): void
    {
        // Transactions linked to caisse session
        CoffreTransaction::create([
            'coffre_id' => $this->coffre->id,
            'user_id' => $this->user->id,
            'source_caisse_session_id' => $this->session->id,
            'transaction_type' => 'deposit',
            'amount' => 500.00,
            'description' => 'Session deposit',
            'status' => 'completed'
        ]);

        CoffreTransaction::create([
            'coffre_id' => $this->coffre->id,
            'user_id' => $this->user->id,
            'source_caisse_session_id' => $this->session->id,
            'transaction_type' => 'withdrawal',
            'amount' => 200.00,
            'description' => 'Session withdrawal',
            'status' => 'completed'
        ]);

        // Transactions not linked to caisse session
        CoffreTransaction::create([
            'coffre_id' => $this->coffre->id,
            'user_id' => $this->user->id,
            'source_caisse_session_id' => null,
            'transaction_type' => 'deposit',
            'amount' => 1000.00,
            'description' => 'Direct deposit',
            'status' => 'completed'
        ]);

        CoffreTransaction::create([
            'coffre_id' => $this->coffre->id,
            'user_id' => $this->user->id,
            'source_caisse_session_id' => null,
            'transaction_type' => 'transfer_in',
            'amount' => 300.00,
            'description' => 'Transfer in',
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function it_returns_paginated_results()
    {
        $result = $this->service->getAllPaginated();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(4, $result->total());
        $this->assertEquals(15, $result->perPage()); // Default per page
    }

    /** @test */
    public function it_can_filter_by_caisse_session_id()
    {
        $result = $this->service->getAllPaginated(15, null, $this->session->id);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(2, $result->total());
        
        // All returned transactions should have the correct caisse session ID
        foreach ($result->items() as $transaction) {
            $this->assertEquals($this->session->id, $transaction->source_caisse_session_id);
        }
    }

    /** @test */
    public function it_returns_all_transactions_when_no_caisse_session_filter()
    {
        $result = $this->service->getAllPaginated(15, null, null);

        $this->assertEquals(4, $result->total());
    }

    /** @test */
    public function it_returns_empty_result_for_nonexistent_caisse_session()
    {
        $result = $this->service->getAllPaginated(15, null, 99999);

        $this->assertEquals(0, $result->total());
        $this->assertEmpty($result->items());
    }

    /** @test */
    public function it_can_combine_caisse_session_filter_with_coffre_filter()
    {
        $result = $this->service->getAllPaginated(15, $this->coffre->id, $this->session->id);

        $this->assertEquals(2, $result->total());
        
        foreach ($result->items() as $transaction) {
            $this->assertEquals($this->session->id, $transaction->source_caisse_session_id);
            $this->assertEquals($this->coffre->id, $transaction->coffre_id);
        }
    }

    /** @test */
    public function it_can_search_within_caisse_session_filtered_results()
    {
        // Note: The current service method doesn't support search parameter
        // This test verifies filtering works and we can check description manually
        $result = $this->service->getAllPaginated(15, null, $this->session->id);

        $this->assertEquals(2, $result->total());
        
        // Find the deposit transaction
        $depositTransaction = collect($result->items())->first(function ($transaction) {
            return $transaction->transaction_type === 'deposit';
        });
        
        $this->assertNotNull($depositTransaction);
        $this->assertEquals($this->session->id, $depositTransaction->source_caisse_session_id);
        $this->assertEquals('deposit', $depositTransaction->transaction_type);
        $this->assertStringContainsString('deposit', strtolower($depositTransaction->description));
    }

    /** @test */
    public function it_respects_pagination_parameters_with_caisse_session_filter()
    {
        $result = $this->service->getAllPaginated(1, null, $this->session->id);

        $this->assertEquals(2, $result->total());
        $this->assertEquals(1, $result->perPage());
        $this->assertEquals(1, count($result->items()));
        $this->assertEquals(2, $result->lastPage());
    }

    /** @test */
    public function it_orders_results_by_created_at_desc_with_caisse_session_filter()
    {
        // Create additional transaction with specific timestamp
        CoffreTransaction::create([
            'coffre_id' => $this->coffre->id,
            'user_id' => $this->user->id,
            'source_caisse_session_id' => $this->session->id,
            'transaction_type' => 'adjustment',
            'amount' => 50.00,
            'description' => 'Latest transaction',
            'status' => 'completed',
            'created_at' => Carbon::now()
        ]);

        $result = $this->service->getAllPaginated(15, null, $this->session->id);

        $this->assertEquals(3, $result->total());
        
        $transactions = $result->items();
        
        // First transaction should be the latest one
        $this->assertEquals('adjustment', $transactions[0]->transaction_type);
        $this->assertEquals('Latest transaction', $transactions[0]->description);
        
        // Verify ordering
        for ($i = 0; $i < count($transactions) - 1; $i++) {
            $this->assertGreaterThanOrEqual(
                $transactions[$i + 1]->created_at,
                $transactions[$i]->created_at
            );
        }
    }

    /** @test */
    public function it_handles_empty_caisse_session_id_parameter()
    {
        $result = $this->service->getAllPaginated(15, null, '');

        // Should return all transactions when empty string is provided
        $this->assertEquals(4, $result->total());
    }

    /** @test */
    public function it_handles_zero_caisse_session_id_parameter()
    {
        $result = $this->service->getAllPaginated(15, null, 0);

        // Should return no transactions for ID 0
        $this->assertEquals(0, $result->total());
    }

    /** @test */
    public function it_can_filter_by_coffre_id_and_caisse_session_id_together()
    {
        // Create another coffre and transactions
        $anotherCoffre = Coffre::create([
            'name' => 'Another Coffre',
            'location' => 'Another Location',
            'current_balance' => 5000.00,
            'responsible_user_id' => $this->user->id,
            'is_active' => true
        ]);

        CoffreTransaction::create([
            'coffre_id' => $anotherCoffre->id,
            'user_id' => $this->user->id,
            'source_caisse_session_id' => $this->session->id,
            'transaction_type' => 'deposit',
            'amount' => 100.00,
            'description' => 'Cross-coffre transaction',
            'status' => 'completed'
        ]);

        $result = $this->service->getAllPaginated(15, $this->coffre->id, $this->session->id);

        // Should return only transactions for the specific coffre and caisse session
        $this->assertEquals(2, $result->total());
        
        foreach ($result->items() as $transaction) {
            $this->assertEquals($this->coffre->id, $transaction->coffre_id);
            $this->assertEquals($this->session->id, $transaction->source_caisse_session_id);
        }
    }
}