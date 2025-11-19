<?php

use App\Models\CONFIGURATION\TransferApproval;
use App\Models\User;
use App\Models\Coffre\Coffre;
use App\Services\Coffre\CoffreTransactionService;

echo "=== Testing Coffre to Bank Approval System ===\n\n";

// Test 1: Check if models exist and work
echo "1. Testing basic model functionality...\n";

$userCount = User::count();
$coffreCount = Coffre::count();
$approvalCount = TransferApproval::count();

echo "   Users in system: {$userCount}\n";
echo "   Coffres in system: {$coffreCount}\n";
echo "   Transfer approvals configured: {$approvalCount}\n";

if ($userCount > 0 && $coffreCount > 0) {
    echo "✓ Basic setup looks good!\n\n";
    
    // Test 2: Check service instantiation
    echo "2. Testing service instantiation...\n";
    try {
        $service = new CoffreTransactionService();
        echo "✓ CoffreTransactionService created successfully\n\n";
        
        // Test 3: Check helper methods
        echo "3. Testing helper methods...\n";
        $types = $service->getTransactionTypes();
        echo "✓ Transaction types: " . implode(', ', array_keys($types)) . "\n";
        
        $coffres = $service->getCoffresForSelect();
        echo "✓ Available coffres: " . $coffres->count() . "\n";
        
        $users = $service->getUsersForSelect();
        echo "✓ Available users: " . $users->count() . "\n\n";
        
        // Test 4: Test approval logic with mock data
        echo "4. Testing approval logic (needsApproval method)...\n";
        
        // Mock data that should trigger approval
        $bankTransferData = [
            'destination_banque_id' => 1,
            'amount' => 25000
        ];
        
        // Use reflection to test private method
        $reflection = new ReflectionClass($service);
        $needsApprovalMethod = $reflection->getMethod('needsApproval');
        $needsApprovalMethod->setAccessible(true);
        
        $needsApproval = $needsApprovalMethod->invoke($service, $bankTransferData);
        echo "✓ Bank transfer needs approval: " . ($needsApproval ? 'Yes' : 'No') . "\n";
        
        // Mock data that should NOT trigger approval
        $regularData = [
            'transaction_type' => 'deposit',
            'amount' => 25000
        ];
        
        $needsApproval2 = $needsApprovalMethod->invoke($service, $regularData);
        echo "✓ Regular deposit needs approval: " . ($needsApproval2 ? 'Yes' : 'No') . "\n\n";
        
        echo "5. Checking existing approval configurations...\n";
        $approvals = TransferApproval::active()->get();
        foreach ($approvals as $approval) {
            echo "   User {$approval->user_id} can approve up to {$approval->maximum}\n";
        }
        
        if ($approvals->count() === 0) {
            echo "   No active approval configurations found.\n";
            echo "   To test approval flow, create some TransferApproval records.\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
    
} else {
    echo "❌ Missing basic data. Please ensure you have users and coffres in the system.\n";
}

echo "\n=== Test completed ===\n";
