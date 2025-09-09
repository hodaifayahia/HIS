<?php

require 'vendor/autoload.php';

use App\Models\Coffre\CoffreTransaction;
use App\Models\Configuration\TransferApproval;
use App\Models\RequestTransactionApproval;
use App\Services\Coffre\CoffreTransactionService;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

// Mock authentication
Auth::shouldReceive('id')->andReturn(1);

echo "=== Coffre to Bank Transaction Approval System Test ===\n\n";

// Create some test data
echo "1. Creating test transfer approval configurations...\n";

// Create users with different approval limits
$user1 = User::find(1); // Assuming user 1 exists
$user2 = User::find(2); // Assuming user 2 exists

if (!$user1) {
    echo "Error: User 1 not found. Please ensure users exist in the database.\n";
    exit(1);
}

// Create transfer approval configurations
TransferApproval::updateOrCreate(
    ['user_id' => 1],
    ['maximum' => 50000.00, 'is_active' => true, 'notes' => 'Can approve up to 50,000']
);

if ($user2) {
    TransferApproval::updateOrCreate(
        ['user_id' => 2],
        ['maximum' => 100000.00, 'is_active' => true, 'notes' => 'Can approve up to 100,000']
    );
}

echo "✓ Transfer approval configurations created\n\n";

// Test the service
$service = new CoffreTransactionService();

echo "2. Testing coffre to bank transaction that needs approval...\n";

// Create a transaction that needs approval (has destination_banque_id)
$transactionData = [
    'coffre_id' => 1, // Assuming coffre 1 exists
    'user_id' => 1,
    'transaction_type' => 'transfer_out',
    'amount' => 25000.00, // Amount that should trigger approval
    'description' => 'Transfer to bank account - Test',
    'destination_banque_id' => 1, // This triggers approval flow
];

try {
    $transaction = $service->create($transactionData);
    
    echo "✓ Transaction created with ID: {$transaction->id}\n";
    echo "✓ Transaction status: {$transaction->status}\n";
    
    // Check if approval request was created
    $approvalRequest = RequestTransactionApproval::where('request_transaction_id', $transaction->id)->first();
    
    if ($approvalRequest) {
        echo "✓ Approval request created with ID: {$approvalRequest->id}\n";
        echo "✓ Approval status: {$approvalRequest->status}\n";
        echo "✓ Candidate approvers: " . implode(', ', $approvalRequest->candidate_user_ids ?? []) . "\n";
    } else {
        echo "❌ No approval request found\n";
    }
    
    echo "\n3. Testing approval process...\n";
    
    if ($approvalRequest && $user1) {
        // Approve the transaction
        $approvedTransaction = $service->approveTransaction($transaction, 1);
        
        echo "✓ Transaction approved by user 1\n";
        echo "✓ Updated transaction status: {$approvedTransaction->status}\n";
        
        // Check updated approval request
        $approvalRequest->refresh();
        echo "✓ Approval request status: {$approvalRequest->status}\n";
        echo "✓ Approved by user: {$approvalRequest->approved_by}\n";
    }
    
    echo "\n4. Testing transaction without approval (no destination_banque_id)...\n";
    
    $nonApprovalData = [
        'coffre_id' => 1,
        'user_id' => 1,
        'transaction_type' => 'deposit',
        'amount' => 15000.00,
        'description' => 'Regular deposit - No approval needed',
    ];
    
    $regularTransaction = $service->create($nonApprovalData);
    echo "✓ Regular transaction created with status: {$regularTransaction->status}\n";
    
    $regularApproval = RequestTransactionApproval::where('request_transaction_id', $regularTransaction->id)->first();
    if (!$regularApproval) {
        echo "✓ No approval request created for regular transaction (expected)\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test completed ===\n";

// Cleanup test data
echo "\nCleaning up test data...\n";
try {
    // Remove test transactions
    if (isset($transaction)) {
        $transaction->delete();
    }
    if (isset($regularTransaction)) {
        $regularTransaction->delete();
    }
    
    // Remove test approval configurations
    TransferApproval::where('user_id', 1)->delete();
    if ($user2) {
        TransferApproval::where('user_id', 2)->delete();
    }
    
    echo "✓ Test data cleaned up\n";
} catch (Exception $e) {
    echo "❌ Cleanup error: " . $e->getMessage() . "\n";
}
