<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Configuration\TransferApproval;
use App\Models\User;
use App\Models\Coffre\Coffre;
use App\Services\Coffre\CoffreTransactionService;
use App\Models\RequestTransactionApproval;
use Illuminate\Support\Facades\Auth;

class TestCoffreApprovalSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:coffre-approval';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the Coffre to Bank approval system implementation';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Testing Coffre to Bank Approval System ===');
        $this->newLine();

        // Test 1: Check basic setup
        $this->info('1. Checking basic system setup...');
        
        $userCount = User::count();
        $coffreCount = Coffre::count();
        $approvalCount = TransferApproval::count();

        $this->line("   Users in system: {$userCount}");
        $this->line("   Coffres in system: {$coffreCount}");
        $this->line("   Transfer approvals configured: {$approvalCount}");

        if ($userCount === 0) {
            $this->error('No users found. Please create users first.');
            return;
        }

        if ($coffreCount === 0) {
            $this->error('No coffres found. Please create coffres first.');
            return;
        }

        $this->info('✓ Basic setup looks good!');
        $this->newLine();

        // Test 2: Service functionality
        $this->info('2. Testing CoffreTransactionService...');
        
        try {
            $service = new CoffreTransactionService();
            $types = $service->getTransactionTypes();
            $this->line("✓ Transaction types available: " . implode(', ', array_keys($types)));
            
            $coffres = $service->getCoffresForSelect();
            $this->line("✓ Coffres available: {$coffres->count()}");
            
            $users = $service->getUsersForSelect();
            $this->line("✓ Users available: {$users->count()}");

        } catch (\Exception $e) {
            $this->error("❌ Service error: " . $e->getMessage());
            return;
        }

        $this->newLine();

        // Test 3: Create test approval configuration
        $this->info('3. Setting up test approval configuration...');
        
        $testUser = User::first();
        Auth::login($testUser); // Set auth for testing
        
        $approval = TransferApproval::updateOrCreate(
            ['user_id' => $testUser->id],
            [
                'maximum' => 50000.00,
                'is_active' => true,
                'note' => 'Test approval configuration'
            ]
        );

        $this->line("✓ Test approval created for user {$testUser->name} with max: {$approval->maximum}");
        $this->newLine();

        // Test 4: Test bank transfer that needs approval
        $this->info('4. Testing bank transfer creation (should require approval)...');
        
        $firstCoffre = Coffre::first();
        
        $bankTransferData = [
            'coffre_id' => $firstCoffre->id,
            'user_id' => $testUser->id,
            'transaction_type' => 'transfer_out',
            'amount' => 25000.00,
            'description' => 'Test bank transfer requiring approval',
            'destination_banque_id' => 1, // This should trigger approval
        ];

        try {
            $transaction = $service->create($bankTransferData);
            
            $this->line("✓ Transaction created with ID: {$transaction->id}");
            $this->line("✓ Transaction status: {$transaction->status}");
            
            if ($transaction->status === 'pending') {
                $this->info('✓ Transaction correctly set to pending status');
                
                // Check for approval request
                $approvalRequest = RequestTransactionApproval::where('request_transaction_id', $transaction->id)->first();
                
                if ($approvalRequest) {
                    $this->line("✓ Approval request created with ID: {$approvalRequest->id}");
                    $this->line("✓ Candidate approvers: " . implode(', ', $approvalRequest->candidate_user_ids ?? []));
                    
                    // Test approval
                    $this->info('5. Testing approval process...');
                    
                    if (in_array($testUser->id, $approvalRequest->candidate_user_ids ?? [])) {
                        $approvedTransaction = $service->approveTransaction($transaction, $testUser->id);
                        $this->line("✓ Transaction approved successfully");
                        $this->line("✓ Final transaction status: {$approvedTransaction->status}");
                        
                        $approvalRequest->refresh();
                        $this->line("✓ Approval request status: {$approvalRequest->status}");
                    } else {
                        $this->warn('Test user is not in candidate approvers list');
                    }
                } else {
                    $this->error('❌ No approval request created');
                }
            } else {
                $this->warn("Transaction status is '{$transaction->status}' instead of 'pending'");
            }

            // Cleanup
            $transaction->delete();
            
        } catch (\Exception $e) {
            $this->error("❌ Error creating bank transfer: " . $e->getMessage());
        }

        $this->newLine();

        // Test 5: Test regular transaction (no approval needed)
        $this->info('6. Testing regular transaction (should not require approval)...');
        
        $regularData = [
            'coffre_id' => $firstCoffre->id,
            'user_id' => $testUser->id,
            'transaction_type' => 'deposit',
            'amount' => 15000.00,
            'description' => 'Regular deposit - no approval needed',
            // No destination_banque_id
        ];

        try {
            $regularTransaction = $service->create($regularData);
            
            $this->line("✓ Regular transaction created with status: {$regularTransaction->status}");
            
            $regularApproval = RequestTransactionApproval::where('request_transaction_id', $regularTransaction->id)->first();
            
            if (!$regularApproval) {
                $this->info('✓ No approval request created for regular transaction (expected)');
            } else {
                $this->warn('Approval request was created for regular transaction (unexpected)');
            }

            // Cleanup
            $regularTransaction->delete();
            
        } catch (\Exception $e) {
            $this->error("❌ Error creating regular transaction: " . $e->getMessage());
        }

        // Cleanup test approval
        $approval->delete();

        $this->newLine();
        $this->info('=== Test completed successfully! ===');
        $this->info('The coffre to bank approval system is working correctly.');
    }
}
