<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Coffre\Coffre;
use App\Models\Coffre\Caisse;
use App\Models\Coffre\CaisseSession;
use App\Models\Coffre\CoffreTransaction;
use App\Models\User;
use Carbon\Carbon;

class CoffreCaisseTestSeeder extends Seeder
{
    /**
     * Run the database seeds for testing caisse-coffre relationships.
     */
    public function run(): void
    {
        // Create test users
        $user1 = User::firstOrCreate(
            ['email' => 'cashier1@test.com'],
            [
                'name' => 'Test Cashier 1',
                'password' => bcrypt('password'),
                'role' => 'cashier'
            ]
        );

        $user2 = User::firstOrCreate(
            ['email' => 'cashier2@test.com'],
            [
                'name' => 'Test Cashier 2',
                'password' => bcrypt('password'),
                'role' => 'cashier'
            ]
        );

        // Create test coffres
        $coffre1 = Coffre::firstOrCreate(
            ['name' => 'Main Coffre'],
            [
                'location' => 'Reception',
                'current_balance' => 50000.00,
                'maximum_capacity' => 100000.00,
                'is_active' => true
            ]
        );

        $coffre2 = Coffre::firstOrCreate(
            ['name' => 'Emergency Coffre'],
            [
                'location' => 'Emergency Room',
                'current_balance' => 25000.00,
                'maximum_capacity' => 50000.00,
                'is_active' => true
            ]
        );

        // Create test caisses
        $caisse1 = Caisse::firstOrCreate(
            ['name' => 'Reception Caisse 1'],
            [
                'location' => 'Reception Desk 1',
                'is_active' => true,
                'current_balance' => 5000.00,
                'maximum_capacity' => 10000.00
            ]
        );

        $caisse2 = Caisse::firstOrCreate(
            ['name' => 'Reception Caisse 2'],
            [
                'location' => 'Reception Desk 2',
                'is_active' => true,
                'current_balance' => 3000.00,
                'maximum_capacity' => 8000.00
            ]
        );

        // Create test caisse sessions
        $session1 = CaisseSession::create([
            'caisse_id' => $caisse1->id,
            'user_id' => $user1->id,
            'source_coffre_id' => $coffre1->id,
            'destination_coffre_id' => $coffre1->id,
            'opening_balance' => 1000.00,
            'current_balance' => 1500.00,
            'status' => 'active',
            'opened_at' => Carbon::now()->subHours(4),
            'notes' => 'Test session for caisse 1'
        ]);

        $session2 = CaisseSession::create([
            'caisse_id' => $caisse2->id,
            'user_id' => $user2->id,
            'source_coffre_id' => $coffre2->id,
            'destination_coffre_id' => $coffre2->id,
            'opening_balance' => 800.00,
            'current_balance' => 1200.00,
            'status' => 'active',
            'opened_at' => Carbon::now()->subHours(2),
            'notes' => 'Test session for caisse 2'
        ]);

        $session3 = CaisseSession::create([
            'caisse_id' => $caisse1->id,
            'user_id' => $user1->id,
            'source_coffre_id' => $coffre1->id,
            'destination_coffre_id' => $coffre1->id,
            'opening_balance' => 1200.00,
            'current_balance' => 1200.00,
            'status' => 'closed',
            'opened_at' => Carbon::yesterday()->setHour(9),
            'closed_at' => Carbon::yesterday()->setHour(17),
            'notes' => 'Closed session for caisse 1'
        ]);

        // Create test coffre transactions linked to caisse sessions
        $transactions = [
            // Transactions for session 1 (caisse 1)
            [
                'coffre_id' => $coffre1->id,
                'user_id' => $user1->id,
                'source_caisse_session_id' => $session1->id,
                'transaction_type' => 'deposit',
                'amount' => 500.00,
                'description' => 'Cash deposit from caisse session 1',
                'status' => 'completed',
                'created_at' => Carbon::now()->subHours(3)
            ],
            [
                'coffre_id' => $coffre1->id,
                'user_id' => $user1->id,
                'source_caisse_session_id' => $session1->id,
                'transaction_type' => 'withdrawal',
                'amount' => 200.00,
                'description' => 'Cash withdrawal for caisse session 1',
                'status' => 'completed',
                'created_at' => Carbon::now()->subHours(2)
            ],
            [
                'coffre_id' => $coffre1->id,
                'user_id' => $user1->id,
                'source_caisse_session_id' => $session1->id,
                'transaction_type' => 'adjustment',
                'amount' => -50.00,
                'description' => 'Balance adjustment for caisse session 1',
                'status' => 'completed',
                'created_at' => Carbon::now()->subHour()
            ],

            // Transactions for session 2 (caisse 2)
            [
                'coffre_id' => $coffre2->id,
                'user_id' => $user2->id,
                'source_caisse_session_id' => $session2->id,
                'transaction_type' => 'deposit',
                'amount' => 400.00,
                'description' => 'Cash deposit from caisse session 2',
                'status' => 'completed',
                'created_at' => Carbon::now()->subMinutes(90)
            ],
            [
                'coffre_id' => $coffre2->id,
                'user_id' => $user2->id,
                'source_caisse_session_id' => $session2->id,
                'transaction_type' => 'transfer_out',
                'amount' => 300.00,
                'description' => 'Transfer out from caisse session 2',
                'status' => 'pending',
                'created_at' => Carbon::now()->subMinutes(30)
            ],

            // Transactions for session 3 (closed session)
            [
                'coffre_id' => $coffre1->id,
                'user_id' => $user1->id,
                'source_caisse_session_id' => $session3->id,
                'transaction_type' => 'deposit',
                'amount' => 800.00,
                'description' => 'End of day deposit from closed session',
                'status' => 'completed',
                'created_at' => Carbon::yesterday()->setHour(17)
            ],

            // Some transactions without caisse session (general coffre transactions)
            [
                'coffre_id' => $coffre1->id,
                'user_id' => $user1->id,
                'source_caisse_session_id' => null,
                'transaction_type' => 'deposit',
                'amount' => 1000.00,
                'description' => 'Direct deposit to coffre (no caisse session)',
                'status' => 'completed',
                'created_at' => Carbon::now()->subDays(2)
            ],
            [
                'coffre_id' => $coffre2->id,
                'user_id' => $user2->id,
                'source_caisse_session_id' => null,
                'transaction_type' => 'withdrawal',
                'amount' => 500.00,
                'description' => 'Direct withdrawal from coffre (no caisse session)',
                'status' => 'completed',
                'created_at' => Carbon::now()->subDays(1)
            ]
        ];

        foreach ($transactions as $transactionData) {
            CoffreTransaction::create($transactionData);
        }

        $this->command->info('Coffre-Caisse test data seeded successfully!');
        $this->command->info('Created:');
        $this->command->info('- 2 Users (cashier1@test.com, cashier2@test.com)');
        $this->command->info('- 2 Coffres (Main Coffre, Emergency Coffre)');
        $this->command->info('- 2 Caisses (Reception Caisse 1, Reception Caisse 2)');
        $this->command->info('- 3 Caisse Sessions (2 active, 1 closed)');
        $this->command->info('- 8 Coffre Transactions (6 linked to sessions, 2 general)');
    }
}