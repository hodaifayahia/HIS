<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\User;
use App\Models\Bank\Bank;
use App\Models\Patient;
use App\Models\Caisse\CaisseSession;
use App\Models\Reception\ficheNavette;
use App\Models\Reception\ficheNavetteItem;
use App\Models\Caisse\FinancialTransaction;
use Laravel\Sanctum\Sanctum;

class BulkPaymentBankTransactionTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $bank;
    protected $patient;
    protected $caisseSession;
    protected $ficheNavette;
    protected $ficheNavetteItem;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::factory()->create([
            'role' => 'cashier'
        ]);
        
        // Create test bank
        $this->bank = Bank::create([
            'name' => 'Test Bank',
            'code' => 'TB001',
            'swift_code' => 'TESTBK01',
            'is_active' => true
        ]);
        
        // Create test patient
        $this->patient = Patient::factory()->create();
        
        // Create test caisse session
        $this->caisseSession = CaisseSession::factory()->create([
            'cashier_id' => $this->user->id,
            'is_active' => true
        ]);
        
        // Create test fiche navette
        $this->ficheNavette = ficheNavette::factory()->create([
            'patient_id' => $this->patient->id
        ]);
        
        // Create test fiche navette item
        $this->ficheNavetteItem = ficheNavetteItem::factory()->create([
            'fiche_navette_id' => $this->ficheNavette->id,
            'amount' => 100.00,
            'paid_amount' => 0.00,
            'payment_status' => 'unpaid'
        ]);
        
        // Authenticate user
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_process_bulk_payment_with_bank_transfer()
    {
        $payload = [
            'fiche_navette_id' => $this->ficheNavette->id,
            'caisse_session_id' => $this->caisseSession->id,
            'cashier_id' => $this->user->id,
            'patient_id' => $this->patient->id,
            'payment_method' => 'bank_transfer',
            'transaction_type' => 'payment',
            'total_amount' => 100.00,
            'is_bank_transaction' => true,
            'bank_id' => $this->bank->id,
            'notes' => 'Test bank transfer payment',
            'items' => [
                [
                    'fiche_navette_item_id' => $this->ficheNavetteItem->id,
                    'amount' => 100.00
                ]
            ]
        ];

        $response = $this->postJson('/api/financial-transactions-bulk-payment', $payload);

        $response->assertStatus(200);
        
        // Verify financial transaction was created with bank details
        $this->assertDatabaseHas('financial_transactions', [
            'patient_id' => $this->patient->id,
            'payment_method' => 'bank_transfer',
            'is_bank_transaction' => true,
            'bank_id' => $this->bank->id,
            'amount' => 100.00,
            'transaction_type' => 'payment'
        ]);
        
        // Verify fiche navette item was updated
        $this->assertDatabaseHas('fiche_navette_items', [
            'id' => $this->ficheNavetteItem->id,
            'paid_amount' => 100.00,
            'payment_status' => 'paid'
        ]);
    }

    /** @test */
    public function it_fails_when_bank_id_is_missing_for_bank_transaction()
    {
        $payload = [
            'fiche_navette_id' => $this->ficheNavette->id,
            'caisse_session_id' => $this->caisseSession->id,
            'cashier_id' => $this->user->id,
            'patient_id' => $this->patient->id,
            'payment_method' => 'bank_transfer',
            'transaction_type' => 'payment',
            'total_amount' => 100.00,
            'is_bank_transaction' => true,
            // Missing bank_id
            'items' => [
                [
                    'fiche_navette_item_id' => $this->ficheNavetteItem->id,
                    'amount' => 100.00
                ]
            ]
        ];

        $response = $this->postJson('/api/financial-transactions-bulk-payment', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['bank_id']);
    }

    /** @test */
    public function it_fails_when_bank_id_does_not_exist()
    {
        $payload = [
            'fiche_navette_id' => $this->ficheNavette->id,
            'caisse_session_id' => $this->caisseSession->id,
            'cashier_id' => $this->user->id,
            'patient_id' => $this->patient->id,
            'payment_method' => 'bank_transfer',
            'transaction_type' => 'payment',
            'total_amount' => 100.00,
            'is_bank_transaction' => true,
            'bank_id' => 999, // Non-existent bank
            'items' => [
                [
                    'fiche_navette_item_id' => $this->ficheNavetteItem->id,
                    'amount' => 100.00
                ]
            ]
        ];

        $response = $this->postJson('/api/financial-transactions-bulk-payment', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['bank_id']);
    }

    /** @test */
    public function it_can_process_cash_payment_without_bank_details()
    {
        $payload = [
            'fiche_navette_id' => $this->ficheNavette->id,
            'caisse_session_id' => $this->caisseSession->id,
            'cashier_id' => $this->user->id,
            'patient_id' => $this->patient->id,
            'payment_method' => 'cash',
            'transaction_type' => 'payment',
            'total_amount' => 100.00,
            'is_bank_transaction' => false,
            'items' => [
                [
                    'fiche_navette_item_id' => $this->ficheNavetteItem->id,
                    'amount' => 100.00
                ]
            ]
        ];

        $response = $this->postJson('/api/financial-transactions-bulk-payment', $payload);

        $response->assertStatus(200);
        
        // Verify financial transaction was created without bank details
        $this->assertDatabaseHas('financial_transactions', [
            'patient_id' => $this->patient->id,
            'payment_method' => 'cash',
            'is_bank_transaction' => false,
            'bank_id' => null,
            'amount' => 100.00,
            'transaction_type' => 'payment'
        ]);
    }

    /** @test */
    public function it_creates_donation_transaction_with_bank_details_for_excess_amount()
    {
        // Create a scenario where payment exceeds the required amount
        $payload = [
            'fiche_navette_id' => $this->ficheNavette->id,
            'caisse_session_id' => $this->caisseSession->id,
            'cashier_id' => $this->user->id,
            'patient_id' => $this->patient->id,
            'payment_method' => 'bank_transfer',
            'transaction_type' => 'payment',
            'total_amount' => 150.00, // More than the item amount (100.00)
            'is_bank_transaction' => true,
            'bank_id' => $this->bank->id,
            'items' => [
                [
                    'fiche_navette_item_id' => $this->ficheNavetteItem->id,
                    'amount' => 100.00
                ]
            ]
        ];

        $response = $this->postJson('/api/financial-transactions-bulk-payment', $payload);

        $response->assertStatus(200);
        
        // Verify payment transaction
        $this->assertDatabaseHas('financial_transactions', [
            'patient_id' => $this->patient->id,
            'payment_method' => 'bank_transfer',
            'is_bank_transaction' => true,
            'bank_id' => $this->bank->id,
            'amount' => 100.00,
            'transaction_type' => 'payment'
        ]);
        
        // Verify donation transaction for excess amount
        $this->assertDatabaseHas('financial_transactions', [
            'patient_id' => $this->patient->id,
            'payment_method' => 'bank_transfer',
            'is_bank_transaction' => true,
            'bank_id' => $this->bank->id,
            'amount' => 50.00,
            'transaction_type' => 'donation'
        ]);
    }

    /** @test */
    public function it_validates_payment_method_includes_bank_transfer()
    {
        $payload = [
            'fiche_navette_id' => $this->ficheNavette->id,
            'caisse_session_id' => $this->caisseSession->id,
            'cashier_id' => $this->user->id,
            'patient_id' => $this->patient->id,
            'payment_method' => 'invalid_method',
            'transaction_type' => 'payment',
            'total_amount' => 100.00,
            'items' => [
                [
                    'fiche_navette_item_id' => $this->ficheNavetteItem->id,
                    'amount' => 100.00
                ]
            ]
        ];

        $response = $this->postJson('/api/financial-transactions-bulk-payment', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['payment_method']);
    }

    /** @test */
    public function it_requires_authentication_for_bulk_payment()
    {
        // Remove authentication
        $this->app['auth']->forgetGuards();
        
        $payload = [
            'fiche_navette_id' => $this->ficheNavette->id,
            'caisse_session_id' => $this->caisseSession->id,
            'cashier_id' => $this->user->id,
            'patient_id' => $this->patient->id,
            'payment_method' => 'bank_transfer',
            'transaction_type' => 'payment',
            'total_amount' => 100.00,
            'is_bank_transaction' => true,
            'bank_id' => $this->bank->id,
            'items' => [
                [
                    'fiche_navette_item_id' => $this->ficheNavetteItem->id,
                    'amount' => 100.00
                ]
            ]
        ];

        $response = $this->postJson('/api/financial-transactions-bulk-payment', $payload);

        $response->assertStatus(401);
    }
}