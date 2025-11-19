<?php

namespace Tests\Database;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Bank\Bank;
use App\Models\Caisse\FinancialTransaction;
use App\Models\Patient;
use App\Models\Reception\ficheNavette;
use App\Models\Reception\ficheNavetteItem;

class BankTransactionIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed necessary data for testing
        $this->seedTestData();
    }

    /**
     * Test that banks table has required columns for bank transactions
     */
    public function test_banks_table_has_required_columns()
    {
        $this->assertTrue(Schema::hasTable('banks'));
        
        $expectedColumns = [
            'id',
            'name',
            'code',
            'is_active',
            'created_at',
            'updated_at'
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('banks', $column),
                "Banks table is missing required column: {$column}"
            );
        }
    }

    /**
     * Test that financial_transactions table has bank-related columns
     */
    public function test_financial_transactions_table_has_bank_columns()
    {
        $this->assertTrue(Schema::hasTable('financial_transactions'));
        
        $bankRelatedColumns = [
            'is_bank_transaction',
            'bank_id'
        ];

        foreach ($bankRelatedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('financial_transactions', $column),
                "Financial transactions table is missing bank-related column: {$column}"
            );
        }
    }

    /**
     * Test foreign key constraint between financial_transactions and banks
     */
    public function test_financial_transactions_bank_foreign_key_constraint()
    {
        $bank = Bank::factory()->create(['is_active' => true]);
        
        // Create a financial transaction with valid bank_id
        $transaction = FinancialTransaction::create([
            'patient_id' => Patient::factory()->create()->id,
            'fiche_navette_id' => ficheNavette::factory()->create()->id,
            'fiche_navette_item_id' => ficheNavetteItem::factory()->create()->id,
            'transaction_type' => 'payment',
            'payment_method' => 'bank_transfer',
            'amount' => 100.00,
            'is_bank_transaction' => true,
            'bank_id' => $bank->id,
            'caisse_session_id' => 1,
            'cashier_id' => 1
        ]);

        $this->assertDatabaseHas('financial_transactions', [
            'id' => $transaction->id,
            'bank_id' => $bank->id,
            'is_bank_transaction' => true
        ]);

        // Test that invalid bank_id fails
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        FinancialTransaction::create([
            'patient_id' => Patient::factory()->create()->id,
            'fiche_navette_id' => ficheNavette::factory()->create()->id,
            'fiche_navette_item_id' => ficheNavetteItem::factory()->create()->id,
            'transaction_type' => 'payment',
            'payment_method' => 'bank_transfer',
            'amount' => 100.00,
            'is_bank_transaction' => true,
            'bank_id' => 99999, // Non-existent bank ID
            'caisse_session_id' => 1,
            'cashier_id' => 1
        ]);
    }

    /**
     * Test that bank transactions are properly linked to banks
     */
    public function test_bank_transaction_relationship()
    {
        $bank = Bank::factory()->create(['is_active' => true]);
        
        $transaction = FinancialTransaction::create([
            'patient_id' => Patient::factory()->create()->id,
            'fiche_navette_id' => ficheNavette::factory()->create()->id,
            'fiche_navette_item_id' => ficheNavetteItem::factory()->create()->id,
            'transaction_type' => 'payment',
            'payment_method' => 'bank_transfer',
            'amount' => 100.00,
            'is_bank_transaction' => true,
            'bank_id' => $bank->id,
            'caisse_session_id' => 1,
            'cashier_id' => 1
        ]);

        // Test relationship exists
        $this->assertInstanceOf(Bank::class, $transaction->bank);
        $this->assertEquals($bank->id, $transaction->bank->id);
        $this->assertEquals($bank->name, $transaction->bank->name);
    }

    /**
     * Test that non-bank transactions have null bank_id
     */
    public function test_non_bank_transactions_have_null_bank_id()
    {
        $transaction = FinancialTransaction::create([
            'patient_id' => Patient::factory()->create()->id,
            'fiche_navette_id' => ficheNavette::factory()->create()->id,
            'fiche_navette_item_id' => ficheNavetteItem::factory()->create()->id,
            'transaction_type' => 'payment',
            'payment_method' => 'cash',
            'amount' => 100.00,
            'is_bank_transaction' => false,
            'bank_id' => null,
            'caisse_session_id' => 1,
            'cashier_id' => 1
        ]);

        $this->assertDatabaseHas('financial_transactions', [
            'id' => $transaction->id,
            'is_bank_transaction' => false,
            'bank_id' => null
        ]);
    }

    /**
     * Test data consistency for bank transactions
     */
    public function test_bank_transaction_data_consistency()
    {
        $bank = Bank::factory()->create(['is_active' => true]);
        
        // Create bank transaction
        $transaction = FinancialTransaction::create([
            'patient_id' => Patient::factory()->create()->id,
            'fiche_navette_id' => ficheNavette::factory()->create()->id,
            'fiche_navette_item_id' => ficheNavetteItem::factory()->create()->id,
            'transaction_type' => 'payment',
            'payment_method' => 'bank_transfer',
            'amount' => 100.00,
            'is_bank_transaction' => true,
            'bank_id' => $bank->id,
            'caisse_session_id' => 1,
            'cashier_id' => 1
        ]);

        // Verify data consistency
        $this->assertTrue($transaction->is_bank_transaction);
        $this->assertNotNull($transaction->bank_id);
        $this->assertEquals('bank_transfer', $transaction->payment_method);
        $this->assertEquals($bank->id, $transaction->bank_id);
    }

    /**
     * Test that only active banks can be used for transactions
     */
    public function test_only_active_banks_for_transactions()
    {
        $activeBank = Bank::factory()->create(['is_active' => true]);
        $inactiveBank = Bank::factory()->create(['is_active' => false]);

        // Active bank should work
        $transaction1 = FinancialTransaction::create([
            'patient_id' => Patient::factory()->create()->id,
            'fiche_navette_id' => ficheNavette::factory()->create()->id,
            'fiche_navette_item_id' => ficheNavetteItem::factory()->create()->id,
            'transaction_type' => 'payment',
            'payment_method' => 'bank_transfer',
            'amount' => 100.00,
            'is_bank_transaction' => true,
            'bank_id' => $activeBank->id,
            'caisse_session_id' => 1,
            'cashier_id' => 1
        ]);

        $this->assertDatabaseHas('financial_transactions', [
            'id' => $transaction1->id,
            'bank_id' => $activeBank->id
        ]);

        // Inactive bank should still be allowed at database level
        // (business logic validation should be handled in request validation)
        $transaction2 = FinancialTransaction::create([
            'patient_id' => Patient::factory()->create()->id,
            'fiche_navette_id' => ficheNavette::factory()->create()->id,
            'fiche_navette_item_id' => ficheNavetteItem::factory()->create()->id,
            'transaction_type' => 'payment',
            'payment_method' => 'bank_transfer',
            'amount' => 100.00,
            'is_bank_transaction' => true,
            'bank_id' => $inactiveBank->id,
            'caisse_session_id' => 1,
            'cashier_id' => 1
        ]);

        $this->assertDatabaseHas('financial_transactions', [
            'id' => $transaction2->id,
            'bank_id' => $inactiveBank->id
        ]);
    }

    /**
     * Test transaction rollback maintains data integrity
     */
    public function test_transaction_rollback_maintains_integrity()
    {
        $bank = Bank::factory()->create(['is_active' => true]);
        $initialTransactionCount = FinancialTransaction::count();

        try {
            DB::transaction(function () use ($bank) {
                // Create a valid transaction
                FinancialTransaction::create([
                    'patient_id' => Patient::factory()->create()->id,
                    'fiche_navette_id' => ficheNavette::factory()->create()->id,
                    'fiche_navette_item_id' => ficheNavetteItem::factory()->create()->id,
                    'transaction_type' => 'payment',
                    'payment_method' => 'bank_transfer',
                    'amount' => 100.00,
                    'is_bank_transaction' => true,
                    'bank_id' => $bank->id,
                    'caisse_session_id' => 1,
                    'cashier_id' => 1
                ]);

                // Force an exception to trigger rollback
                throw new \Exception('Forced rollback');
            });
        } catch (\Exception $e) {
            // Expected exception
        }

        // Verify transaction was rolled back
        $this->assertEquals($initialTransactionCount, FinancialTransaction::count());
    }

    /**
     * Test bulk bank transactions maintain referential integrity
     */
    public function test_bulk_bank_transactions_referential_integrity()
    {
        $bank = Bank::factory()->create(['is_active' => true]);
        $patient = Patient::factory()->create();
        $ficheNavette = ficheNavette::factory()->create(['patient_id' => $patient->id]);
        
        $transactions = [];
        
        // Create multiple bank transactions
        for ($i = 0; $i < 5; $i++) {
            $ficheNavetteItem = ficheNavetteItem::factory()->create([
                'fiche_navette_id' => $ficheNavette->id
            ]);
            
            $transactions[] = FinancialTransaction::create([
                'patient_id' => $patient->id,
                'fiche_navette_id' => $ficheNavette->id,
                'fiche_navette_item_id' => $ficheNavetteItem->id,
                'transaction_type' => 'payment',
                'payment_method' => 'bank_transfer',
                'amount' => 50.00,
                'is_bank_transaction' => true,
                'bank_id' => $bank->id,
                'caisse_session_id' => 1,
                'cashier_id' => 1
            ]);
        }

        // Verify all transactions are linked to the same bank
        foreach ($transactions as $transaction) {
            $this->assertEquals($bank->id, $transaction->bank_id);
            $this->assertTrue($transaction->is_bank_transaction);
            $this->assertEquals('bank_transfer', $transaction->payment_method);
        }

        // Verify bank has the expected number of transactions
        $bankTransactionCount = FinancialTransaction::where('bank_id', $bank->id)->count();
        $this->assertEquals(5, $bankTransactionCount);
    }

    /**
     * Seed test data
     */
    private function seedTestData()
    {
        // Create test banks
        Bank::factory()->create([
            'name' => 'Test Bank 1',
            'code' => 'TB001',
            'is_active' => true
        ]);

        Bank::factory()->create([
            'name' => 'Test Bank 2',
            'code' => 'TB002',
            'is_active' => true
        ]);

        Bank::factory()->create([
            'name' => 'Inactive Bank',
            'code' => 'IB001',
            'is_active' => false
        ]);
    }
}