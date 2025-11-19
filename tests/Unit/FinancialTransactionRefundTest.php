<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\Caisse\FinancialTransactionController;
use App\Models\Reception\ficheNavetteItem;
use App\Models\Reception\ItemDependency;
use App\Models\Caisse\FinancialTransaction;
use Illuminate\Support\Collection;

class FinancialTransactionRefundTest extends TestCase
{
    /** @test */
    public function it_calculates_total_paid_with_positive_and_negative_refunds()
    {
        // Create a fake item with transactions
        $item = new class {
            public $transactions;
            public function __construct() {
                $this->transactions = collect();
            }
            public function update($data) { $this->updated = $data; }
        };

        // payments: 100, 50
        $item->transactions->push((object)['transaction_type' => 'payment', 'amount' => 100]);
        $item->transactions->push((object)['transaction_type' => 'payment', 'amount' => 50]);
        // refunds: represented as positive 30 and negative -20 in different cases
        $item->transactions->push((object)['transaction_type' => 'refund', 'amount' => 30]);
        $item->transactions->push((object)['transaction_type' => 'refund', 'amount' => -20]);

        $controller = new FinancialTransactionController(app()->make('App\Services\Caisse\FinancialTransactionService'));

        // Use reflection to call private method
        $ref = new \ReflectionClass($controller);
        $method = $ref->getMethod('updateItemAmounts');
        $method->setAccessible(true);

        $method->invoke($controller, $item);

        // After calculation: totalPaid = 100 + 50 - 30 - 20 = 100
        $this->assertEquals(100, $item->updated['paid_amount']);
        $this->assertEquals(max(0, ($item->updated['remaining_amount'] ?? 0)), $item->updated['remaining_amount']);
    }

    /** @test */
    public function it_updates_item_amounts_correctly_for_dependency()
    {
        // Simulate a dependency item with final_price 200 and payments/refunds
        $dependency = new class {
            public $transactions;
            public $final_price = 200;
            public function __construct() { $this->transactions = collect(); }
            public function update($data) { $this->updated = $data; }
        };

        $dependency->transactions->push((object)['transaction_type' => 'payment', 'amount' => 150]);
        $dependency->transactions->push((object)['transaction_type' => 'refund', 'amount' => 50]);

        $controller = new FinancialTransactionController(app()->make('App\Services\Caisse\FinancialTransactionService'));

        $ref = new \ReflectionClass($controller);
        $method = $ref->getMethod('updateItemAmounts');
        $method->setAccessible(true);

        $method->invoke($controller, $dependency);

        // payments 150 - refund 50 = 100 paid, remaining = 100
        $this->assertEquals(100, $dependency->updated['paid_amount']);
        $this->assertEquals(100, $dependency->updated['remaining_amount']);
    }
}
