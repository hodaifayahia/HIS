<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Requests\Caisse\BulkPaymentRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use App\Models\Bank\Bank;

class BulkPaymentRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test bank for validation
        Bank::create([
            'name' => 'Test Bank',
            'code' => 'TB001',
            'swift_code' => 'TESTBK01',
            'is_active' => true
        ]);
    }

    /** @test */
    public function it_validates_bank_transfer_payment_method()
    {
        // Create required test data
        $bank = \App\Models\Bank\Bank::factory()->create();
        $patient = \App\Models\Patient::factory()->create();
        $cashier = \App\Models\User::factory()->create();
        $ficheNavette = \App\Models\Reception\ficheNavette::factory()->create(['patient_id' => $patient->id]);
        $ficheNavetteItem = \App\Models\Reception\ficheNavetteItem::factory()->create([
            'fiche_navette_id' => $ficheNavette->id,
            'remise_id' => null, // Avoid foreign key constraint
            'insured_id' => null, // Avoid foreign key constraint
            'convention_id' => null, // Avoid foreign key constraint
            'modality_id' => null, // Avoid foreign key constraint
            'technician_id' => null, // Avoid foreign key constraint
            'assistant_clinician_id' => null, // Avoid foreign key constraint
        ]);
        
        $request = new BulkPaymentRequest();
        $rules = $request->rules();
        
        $data = [
            'fiche_navette_id' => $ficheNavette->id,
            'cashier_id' => $cashier->id,
            'patient_id' => $patient->id,
            'payment_method' => 'bank_transfer',
            'transaction_type' => 'bulk_payment',
            'total_amount' => 100.00,
            'is_bank_transaction' => true,
            'bank_id' => $bank->id,
            'items' => [
                [
                    'fiche_navette_item_id' => $ficheNavetteItem->id,
                    'amount' => 100.00,
                    'remaining_amount' => 0.00
                ]
            ]
        ];

        $validator = Validator::make($data, $rules);
        
        if ($validator->fails()) {
            dump($validator->errors()->toArray());
        }
        
        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function it_requires_bank_id_when_is_bank_transaction_is_true()
    {
        // Create a bank first
        Bank::factory()->create(['id' => 1, 'is_active' => true]);
        
        $rules = (new BulkPaymentRequest())->rules();
        
        $data = [
            'fiche_navette_id' => 1,
            'patient_id' => 1,
            'payment_method' => 'bank_transfer',
            'transaction_type' => 'bulk_payment',
            'total_amount' => 100.00,
            'is_bank_transaction' => true,
            // Missing bank_id
            'items' => [
                [
                    'fiche_navette_item_id' => 1,
                    'amount' => 100.00,
                    'remaining_amount' => 0.00
                ]
            ]
        ];

        $validator = Validator::make($data, $rules);
        $this->assertFalse($validator->passes());
        
        $errors = $validator->errors()->toArray();
        $this->assertArrayHasKey('bank_id', $errors);
    }

    /** @test */
    public function it_allows_null_bank_id_when_is_bank_transaction_is_false()
    {
        // Create required test data
        $patient = \App\Models\Patient::factory()->create();
        $ficheNavette = \App\Models\Reception\ficheNavette::factory()->create(['patient_id' => $patient->id]);
        $ficheNavetteItem = \App\Models\Reception\ficheNavetteItem::factory()->create([
            'fiche_navette_id' => $ficheNavette->id,
            'remise_id' => null, // Avoid foreign key constraint
            'insured_id' => null, // Avoid foreign key constraint
            'convention_id' => null, // Avoid foreign key constraint
            'modality_id' => null, // Avoid foreign key constraint
            'technician_id' => null, // Avoid foreign key constraint
            'assistant_clinician_id' => null, // Avoid foreign key constraint
        ]);
        
        $request = new BulkPaymentRequest();
        $rules = $request->rules();
        
        $data = [
            'fiche_navette_id' => $ficheNavette->id,
            'patient_id' => $patient->id,
            'payment_method' => 'cash',
            'transaction_type' => 'bulk_payment',
            'total_amount' => 100.00,
            'is_bank_transaction' => false,
            'bank_id' => null,
            'items' => [
                [
                    'fiche_navette_item_id' => $ficheNavetteItem->id,
                    'amount' => 100.00,
                    'remaining_amount' => 0.00
                ]
            ]
        ];

        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function it_validates_bank_id_exists_in_banks_table()
    {
        $request = new BulkPaymentRequest();
        $rules = $request->rules();
        
        $data = [
            'payment_method' => 'bank_transfer',
            'is_bank_transaction' => true,
            'bank_id' => 999, // Non-existent bank ID
            'fiche_navette_id' => 1,
            'caisse_session_id' => 1,
            'cashier_id' => 1,
            'patient_id' => 1,
            'transaction_type' => 'payment',
            'total_amount' => 100.00,
            'items' => [
                [
                    'fiche_navette_item_id' => 1,
                    'amount' => 100.00
                ]
            ]
        ];

        $validator = Validator::make($data, $rules);
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('bank_id', $validator->errors()->toArray());
    }

    /** @test */
    public function it_validates_is_bank_transaction_as_boolean()
    {
        $request = new BulkPaymentRequest();
        $rules = $request->rules();
        
        $data = [
            'payment_method' => 'cash',
            'is_bank_transaction' => 'not_boolean', // Invalid boolean value
            'fiche_navette_id' => 1,
            'caisse_session_id' => 1,
            'cashier_id' => 1,
            'patient_id' => 1,
            'transaction_type' => 'payment',
            'total_amount' => 100.00,
            'items' => [
                [
                    'fiche_navette_item_id' => 1,
                    'amount' => 100.00
                ]
            ]
        ];

        $validator = Validator::make($data, $rules);
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('is_bank_transaction', $validator->errors()->toArray());
    }

    /** @test */
    public function it_validates_custom_error_messages()
    {
        $request = new BulkPaymentRequest();
        $rules = $request->rules();
        $messages = $request->messages();
        
        $data = [
            'fiche_navette_id' => 1,
            'patient_id' => 1,
            'payment_method' => 'bank_transfer',
            'transaction_type' => 'bulk_payment',
            'total_amount' => 100.00,
            'is_bank_transaction' => true,
            // Missing bank_id to trigger custom message
            'items' => [
                [
                    'fiche_navette_item_id' => 1,
                    'amount' => 100.00,
                    'remaining_amount' => 0.00
                ]
            ]
        ];

        $validator = Validator::make($data, $rules, $messages);
        $this->assertFalse($validator->passes());
        
        $errors = $validator->errors()->toArray();
        $this->assertArrayHasKey('bank_id', $errors);
        $this->assertContains('Bank selection is required for bank transactions', $errors['bank_id']);
    }
}