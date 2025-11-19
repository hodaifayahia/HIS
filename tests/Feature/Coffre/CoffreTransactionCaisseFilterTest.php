<?php

namespace Tests\Feature\Coffre;

use Tests\TestCase;
use App\Models\User;
use App\Models\Coffre\Coffre;
use App\Models\Coffre\Caisse;
use App\Models\Coffre\CaisseSession;
use App\Models\Coffre\CoffreTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Carbon\Carbon;

class CoffreTransactionCaisseFilterTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private Coffre $coffre1;
    private Coffre $coffre2;
    private Caisse $caisse1;
    private Caisse $caisse2;
    private CaisseSession $session1;
    private CaisseSession $session2;
    private CaisseSession $closedSession;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'admin'
        ]);

        // Create test coffres
        $this->coffre1 = Coffre::create([
            'name' => 'Main Coffre',
            'location' => 'Reception',
            'current_balance' => 50000.00,
            'responsible_user_id' => $this->user->id,
            'is_active' => true
        ]);

        $this->coffre2 = Coffre::create([
            'name' => 'Emergency Coffre',
            'location' => 'Emergency Room',
            'current_balance' => 25000.00,
            'responsible_user_id' => $this->user->id,
            'is_active' => true
        ]);

        // Create test caisses
        $this->caisse1 = Caisse::create([
            'name' => 'Reception Caisse 1',
            'location' => 'Reception Desk 1',
            'is_active' => true
        ]);

        $this->caisse2 = Caisse::create([
            'name' => 'Reception Caisse 2',
            'location' => 'Reception Desk 2',
            'is_active' => true
        ]);

        // Create test caisse sessions
        $this->session1 = CaisseSession::create([
            'caisse_id' => $this->caisse1->id,
            'user_id' => $this->user->id,
            'open_by' => $this->user->id,
            'coffre_id_source' => $this->coffre1->id,
            'coffre_id_destination' => $this->coffre1->id,
            'opening_amount' => 1000.00,
            'closing_amount' => null,
            'status' => 'active',
            'opened_at' => Carbon::now()->subHours(4),
            'opening_notes' => 'Test session 1'
        ]);

        $this->session2 = CaisseSession::create([
            'caisse_id' => $this->caisse2->id,
            'user_id' => $this->user->id,
            'open_by' => $this->user->id,
            'coffre_id_source' => $this->coffre2->id,
            'coffre_id_destination' => $this->coffre2->id,
            'opening_amount' => 800.00,
            'closing_amount' => null,
            'status' => 'active',
            'opened_at' => Carbon::now()->subHours(2),
            'opening_notes' => 'Test session 2'
        ]);

        $this->closedSession = CaisseSession::create([
            'caisse_id' => $this->caisse1->id,
            'user_id' => $this->user->id,
            'open_by' => $this->user->id,
            'closed_by' => $this->user->id,
            'coffre_id_source' => $this->coffre1->id,
            'coffre_id_destination' => $this->coffre1->id,
            'opening_amount' => 1200.00,
            'closing_amount' => 1200.00,
            'status' => 'closed',
            'opened_at' => Carbon::yesterday()->setHour(9),
            'closed_at' => Carbon::yesterday()->setHour(17),
            'opening_notes' => 'Closed session'
        ]);

        // Create test transactions
        $this->createTestTransactions();
    }

    private function createTestTransactions(): void
    {
        // Transactions for session 1
        CoffreTransaction::create([
            'coffre_id' => $this->coffre1->id,
            'user_id' => $this->user->id,
            'source_caisse_session_id' => $this->session1->id,
            'transaction_type' => 'deposit',
            'amount' => 500.00,
            'description' => 'Cash deposit from session 1',
            'status' => 'completed',
            'created_at' => Carbon::now()->subHours(3)
        ]);

        CoffreTransaction::create([
            'coffre_id' => $this->coffre1->id,
            'user_id' => $this->user->id,
            'source_caisse_session_id' => $this->session1->id,
            'transaction_type' => 'withdrawal',
            'amount' => 200.00,
            'description' => 'Cash withdrawal for session 1',
            'status' => 'completed',
            'created_at' => Carbon::now()->subHours(2)
        ]);

        // Transactions for session 2
        CoffreTransaction::create([
            'coffre_id' => $this->coffre2->id,
            'user_id' => $this->user->id,
            'source_caisse_session_id' => $this->session2->id,
            'transaction_type' => 'deposit',
            'amount' => 400.00,
            'description' => 'Cash deposit from session 2',
            'status' => 'completed',
            'created_at' => Carbon::now()->subMinutes(90)
        ]);

        CoffreTransaction::create([
            'coffre_id' => $this->coffre2->id,
            'user_id' => $this->user->id,
            'source_caisse_session_id' => $this->session2->id,
            'transaction_type' => 'transfer_out',
            'amount' => 300.00,
            'description' => 'Transfer out from session 2',
            'status' => 'pending',
            'created_at' => Carbon::now()->subMinutes(30)
        ]);

        // Transactions for closed session
        CoffreTransaction::create([
            'coffre_id' => $this->coffre1->id,
            'user_id' => $this->user->id,
            'source_caisse_session_id' => $this->closedSession->id,
            'transaction_type' => 'deposit',
            'amount' => 800.00,
            'description' => 'End of day deposit from closed session',
            'status' => 'completed',
            'created_at' => Carbon::yesterday()->setHour(17)
        ]);

        // General transactions (no caisse session)
        CoffreTransaction::create([
            'coffre_id' => $this->coffre1->id,
            'user_id' => $this->user->id,
            'source_caisse_session_id' => null,
            'transaction_type' => 'deposit',
            'amount' => 1000.00,
            'description' => 'Direct deposit to coffre',
            'status' => 'completed',
            'created_at' => Carbon::now()->subDays(2)
        ]);

        CoffreTransaction::create([
            'coffre_id' => $this->coffre2->id,
            'user_id' => $this->user->id,
            'source_caisse_session_id' => null,
            'transaction_type' => 'withdrawal',
            'amount' => 500.00,
            'description' => 'Direct withdrawal from coffre',
            'status' => 'completed',
            'created_at' => Carbon::now()->subDays(1)
        ]);
    }

    /** @test */
    public function it_can_fetch_all_coffre_transactions_without_filter()
    {
        $this->actingAs($this->user);

        $response = $this->getJson('/api/coffre-transactions');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'coffre_id',
                            'user_id',
                            'source_caisse_session_id',
                            'transaction_type',
                            'amount',
                            'description',
                            'status',
                            'created_at'
                        ]
                    ],
                    'meta' => [
                        'current_page',
                        'total',
                        'per_page'
                    ]
                ]);

        // Should return all 7 transactions
        $this->assertEquals(7, $response->json('meta.total'));
    }

    /** @test */
    public function it_can_filter_transactions_by_caisse_session_id()
    {
        $this->actingAs($this->user);

        $response = $this->getJson("/api/coffre-transactions?caisse_session_id={$this->session1->id}");

        $response->assertStatus(200);
        
        $transactions = $response->json('data');
        
        // Should return only 2 transactions for session 1
        $this->assertCount(2, $transactions);
        
        // All transactions should belong to session 1
        foreach ($transactions as $transaction) {
            $this->assertEquals($this->session1->id, $transaction['source_caisse_session_id']);
        }
    }

    /** @test */
    public function it_can_filter_transactions_by_different_caisse_session()
    {
        $this->actingAs($this->user);

        $response = $this->getJson("/api/coffre-transactions?caisse_session_id={$this->session2->id}");

        $response->assertStatus(200);
        
        $transactions = $response->json('data');
        
        // Should return only 2 transactions for session 2
        $this->assertCount(2, $transactions);
        
        // All transactions should belong to session 2
        foreach ($transactions as $transaction) {
            $this->assertEquals($this->session2->id, $transaction['source_caisse_session_id']);
        }
    }

    /** @test */
    public function it_can_filter_transactions_by_closed_session()
    {
        $this->actingAs($this->user);

        $response = $this->getJson("/api/coffre-transactions?caisse_session_id={$this->closedSession->id}");

        $response->assertStatus(200);
        
        $transactions = $response->json('data');
        
        // Should return only 1 transaction for closed session
        $this->assertCount(1, $transactions);
        
        $this->assertEquals($this->closedSession->id, $transactions[0]['source_caisse_session_id']);
        $this->assertEquals('deposit', $transactions[0]['transaction_type']);
        $this->assertEquals(800.00, $transactions[0]['amount']);
    }

    /** @test */
    public function it_returns_empty_result_for_nonexistent_caisse_session()
    {
        $this->actingAs($this->user);

        $response = $this->getJson('/api/coffre-transactions?caisse_session_id=99999');

        $response->assertStatus(200);
        
        $transactions = $response->json('data');
        
        // Should return no transactions
        $this->assertCount(0, $transactions);
        $this->assertEquals(0, $response->json('meta.total'));
    }

    /** @test */
    public function it_can_combine_caisse_session_filter_with_coffre_filter()
    {
        $this->actingAs($this->user);

        $response = $this->getJson("/api/coffre-transactions?caisse_session_id={$this->session1->id}&coffre_id={$this->coffre1->id}");

        $response->assertStatus(200);
        
        $transactions = $response->json('data');
        
        // Should return 2 transactions (session 1 is linked to coffre 1)
        $this->assertCount(2, $transactions);
        
        foreach ($transactions as $transaction) {
            $this->assertEquals($this->session1->id, $transaction['source_caisse_session_id']);
            $this->assertEquals($this->coffre1->id, $transaction['coffre_id']);
        }
    }

    /** @test */
    public function it_returns_empty_when_caisse_session_and_coffre_dont_match()
    {
        $this->actingAs($this->user);

        // Session 1 is linked to coffre 1, but we're filtering by coffre 2
        $response = $this->getJson("/api/coffre-transactions?caisse_session_id={$this->session1->id}&coffre_id={$this->coffre2->id}");

        $response->assertStatus(200);
        
        $transactions = $response->json('data');
        
        // Should return no transactions
        $this->assertCount(0, $transactions);
    }

    /** @test */
    public function it_can_paginate_filtered_results()
    {
        $this->actingAs($this->user);

        $response = $this->getJson("/api/coffre-transactions?caisse_session_id={$this->session1->id}&per_page=1");

        $response->assertStatus(200);
        
        $this->assertEquals(1, count($response->json('data')));
        $this->assertEquals(2, $response->json('meta.total'));
        $this->assertEquals(1, $response->json('meta.per_page'));
        $this->assertEquals(2, $response->json('meta.last_page'));
    }

    /** @test */
    public function it_includes_caisse_session_relationship_when_loaded()
    {
        $this->actingAs($this->user);

        $response = $this->getJson("/api/coffre-transactions?caisse_session_id={$this->session1->id}");

        $response->assertStatus(200);
        
        $transactions = $response->json('data');
        
        // Check if source_caisse_session relationship is included
        foreach ($transactions as $transaction) {
            $this->assertArrayHasKey('source_caisse_session_id', $transaction);
            $this->assertEquals($this->session1->id, $transaction['source_caisse_session_id']);
        }
    }

    /** @test */
    public function it_handles_invalid_caisse_session_id_parameter()
    {
        $this->actingAs($this->user);

        $response = $this->getJson('/api/coffre-transactions?caisse_session_id=invalid');

        $response->assertStatus(200);
        
        // Should return all transactions when invalid parameter is provided
        $this->assertEquals(7, $response->json('meta.total'));
    }

    /** @test */
    public function it_requires_authentication_for_coffre_transactions()
    {
        $response = $this->getJson('/api/coffre-transactions');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_can_search_within_filtered_transactions()
    {
        $this->actingAs($this->user);

        $response = $this->getJson("/api/coffre-transactions?caisse_session_id={$this->session1->id}&search=deposit");

        $response->assertStatus(200);
        
        $transactions = $response->json('data');
        
        // Should return only the deposit transaction from session 1
        $this->assertCount(1, $transactions);
        $this->assertEquals('deposit', $transactions[0]['transaction_type']);
        $this->assertEquals($this->session1->id, $transactions[0]['source_caisse_session_id']);
    }
}