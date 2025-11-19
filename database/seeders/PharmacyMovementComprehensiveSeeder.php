<?php

namespace Database\Seeders;

use App\Models\PharmacyMovement;
use App\Models\PharmacyMovementItem;
use App\Models\PharmacyProduct;
use App\Models\User;
use App\Models\CONFIGURATION\Service;
use Illuminate\Database\Seeder;

class PharmacyMovementComprehensiveSeeder extends Seeder
{
    /**
     * Seed pharmacy movements with all workflow states and realistic data
     */
    public function run(): void
    {
        $pharmacyProducts = PharmacyProduct::where('is_active', true)->limit(100)->get();
        $users = User::all();
        $services = Service::all();

        if ($pharmacyProducts->isEmpty() || $users->isEmpty() || $services->isEmpty()) {
            echo "⚠️  Missing required data. Skipping PharmacyMovement seeding.\n";
            return;
        }

        echo "\n🚀 Creating comprehensive pharmacy movements with all workflow states...\n";

        $movementCount = 0;

        // Test Case 1: Draft Movements (20%)
        echo "\n📝 Test Case 1: Draft Movements (20%)\n";
        for ($i = 0; $i < 50; $i++) {
            $requestingService = $services->random();
            $providingService = $services->where('id', '!=', $requestingService->id)->random();
            $requestingUser = $users->random();
            $product = $pharmacyProducts->random();

            $createdDate = now()->subDays(rand(1, 30));

            $movement = PharmacyMovement::create([
                'movement_number' => $this->generateMovementNumber(),
                'pharmacy_product_id' => $product->id,
                'requesting_service_id' => $requestingService->id,
                'providing_service_id' => $providingService->id,
                'requesting_user_id' => $requestingUser->id,
                'requested_quantity' => rand(10, 100),
                'status' => 'draft',
                'request_reason' => $this->getRequestReason(),
                'created_at' => $createdDate,
                'updated_at' => $createdDate->addDays(rand(0, 5)),
            ]);

            // Add movement items
            for ($j = 0; $j < rand(1, 3); $j++) {
                $prod = $pharmacyProducts->random();
                PharmacyMovementItem::create([
                    'pharmacy_movement_id' => $movement->id,
                    'product_id' => $prod->id,
                    'requested_quantity' => rand(10, 100),
                    'administration_route' => $this->getAdministrationRoute(),
                    'frequency' => $this->getFrequency(),
                ]);
            }

            $movementCount++;
            if ($movementCount % 10 == 0) {
                echo "   ✅ Created $movementCount total movements\n";
            }
        }

        // Test Case 2: Pending Approvals (25%)
        echo "\n📝 Test Case 2: Pending Approvals (25%)\n";
        for ($i = 0; $i < 63; $i++) {
            $requestingService = $services->random();
            $providingService = $services->where('id', '!=', $requestingService->id)->random();
            $requestingUser = $users->random();
            $product = $pharmacyProducts->random();

            $createdDate = now()->subDays(rand(5, 30));
            $requestedDate = $createdDate->addDays(rand(1, 5));

            $movement = PharmacyMovement::create([
                'movement_number' => $this->generateMovementNumber(),
                'pharmacy_product_id' => $product->id,
                'requesting_service_id' => $requestingService->id,
                'providing_service_id' => $providingService->id,
                'requesting_user_id' => $requestingUser->id,
                'requested_quantity' => rand(5, 150),
                'status' => 'pending',
                'request_reason' => $this->getRequestReason(),
                'requested_at' => $requestedDate,
                'expected_delivery_date' => $requestedDate->addDays(rand(3, 10)),
                'created_at' => $createdDate,
                'updated_at' => $requestedDate,
            ]);

            // Add items
            for ($j = 0; $j < rand(1, 4); $j++) {
                $prod = $pharmacyProducts->random();
                PharmacyMovementItem::create([
                    'pharmacy_movement_id' => $movement->id,
                    'product_id' => $prod->id,
                    'requested_quantity' => rand(5, 150),
                    'administration_route' => $this->getAdministrationRoute(),
                    'frequency' => $this->getFrequency(),
                ]);
            }

            $movementCount++;
            if ($movementCount % 20 == 0) {
                echo "   ✅ Created $movementCount total movements\n";
            }
        }

        // Test Case 3: Approved Movements (25%)
        echo "\n📝 Test Case 3: Approved Movements (25%)\n";
        for ($i = 0; $i < 63; $i++) {
            $requestingService = $services->random();
            $providingService = $services->where('id', '!=', $requestingService->id)->random();
            $requestingUser = $users->random();
            $approvingUser = $users->random();
            $product = $pharmacyProducts->random();

            $createdDate = now()->subDays(rand(10, 60));
            $requestedDate = $createdDate->addDays(rand(1, 3));
            $approvedDate = $requestedDate->addDays(rand(2, 8));

            $requestedQty = rand(5, 150);
            $approvedQty = rand(max(1, $requestedQty - 30), $requestedQty);

            $movement = PharmacyMovement::create([
                'movement_number' => $this->generateMovementNumber(),
                'pharmacy_product_id' => $product->id,
                'requesting_service_id' => $requestingService->id,
                'providing_service_id' => $providingService->id,
                'requesting_user_id' => $requestingUser->id,
                'approving_user_id' => $approvingUser->id,
                'requested_quantity' => $requestedQty,
                'approved_quantity' => $approvedQty,
                'status' => 'approved',
                'request_reason' => $this->getRequestReason(),
                'approval_notes' => 'Approved by pharmacy manager',
                'requested_at' => $requestedDate,
                'approved_at' => $approvedDate,
                'expected_delivery_date' => $approvedDate->addDays(rand(1, 5)),
                'created_at' => $createdDate,
                'updated_at' => $approvedDate,
            ]);

            // Add items
            for ($j = 0; $j < rand(1, 4); $j++) {
                $prod = $pharmacyProducts->random();
                $rQty = rand(5, 150);
                $aQty = rand(max(1, $rQty - 20), $rQty);
                
                PharmacyMovementItem::create([
                    'pharmacy_stock_movement_id' => $movement->id,
                    'pharmacy_movement_id' => $movement->id,
                    'product_id' => $prod->id,
                    'pharmacy_product_id' => $prod->id,
                    'requested_quantity' => $rQty,
                    'approved_quantity' => $aQty,
                    'administration_route' => $this->getAdministrationRoute(),
                    'frequency' => $this->getFrequency(),
                ]);
            }

            $movementCount++;
            if ($movementCount % 20 == 0) {
                echo "   ✅ Created $movementCount total movements\n";
            }
        }

        // Test Case 4: Partially Approved (15%)
        echo "\n📝 Test Case 4: Partially Approved (15%)\n";
        for ($i = 0; $i < 38; $i++) {
            $requestingService = $services->random();
            $providingService = $services->where('id', '!=', $requestingService->id)->random();
            $requestingUser = $users->random();
            $approvingUser = $users->random();
            $product = $pharmacyProducts->random();

            $createdDate = now()->subDays(rand(10, 60));
            $requestedDate = $createdDate->addDays(rand(1, 3));
            $approvedDate = $requestedDate->addDays(rand(2, 8));

            $requestedQty = rand(5, 150);
            $approvedQty = rand(max(1, $requestedQty - 50), $requestedQty);

            $movement = PharmacyMovement::create([
                'movement_number' => $this->generateMovementNumber(),
                'pharmacy_product_id' => $product->id,
                'requesting_service_id' => $requestingService->id,
                'providing_service_id' => $providingService->id,
                'requesting_user_id' => $requestingUser->id,
                'approving_user_id' => $approvingUser->id,
                'requested_quantity' => $requestedQty,
                'approved_quantity' => $approvedQty,
                'status' => 'partially_approved',
                'request_reason' => $this->getRequestReason(),
                'approval_notes' => 'Some items approved, others out of stock',
                'requested_at' => $requestedDate,
                'approved_at' => $approvedDate,
                'expected_delivery_date' => $approvedDate->addDays(rand(2, 10)),
                'created_at' => $createdDate,
                'updated_at' => $approvedDate,
            ]);

            // Add items - some approved, some rejected
            for ($j = 0; $j < rand(2, 5); $j++) {
                $prod = $pharmacyProducts->random();
                $rQty = rand(5, 150);
                $isApproved = rand(0, 1) === 1;
                
                PharmacyMovementItem::create([
                    'pharmacy_stock_movement_id' => $movement->id,
                    'pharmacy_movement_id' => $movement->id,
                    'product_id' => $prod->id,
                    'pharmacy_product_id' => $prod->id,
                    'requested_quantity' => $rQty,
                    'approved_quantity' => $isApproved ? rand(max(1, $rQty - 30), $rQty) : 0,
                    'administration_route' => $this->getAdministrationRoute(),
                    'frequency' => $this->getFrequency(),
                ]);
            }

            $movementCount++;
            if ($movementCount % 15 == 0) {
                echo "   ✅ Created $movementCount total movements\n";
            }
        }

        // Test Case 5: Executed Movements (12%)
        echo "\n📝 Test Case 5: Executed Movements (12%)\n";
        for ($i = 0; $i < 30; $i++) {
            $requestingService = $services->random();
            $providingService = $services->where('id', '!=', $requestingService->id)->random();
            $requestingUser = $users->random();
            $approvingUser = $users->random();
            $executingUser = $users->random();
            $product = $pharmacyProducts->random();

            $createdDate = now()->subDays(rand(20, 90));
            $requestedDate = $createdDate->addDays(rand(1, 5));
            $approvedDate = $requestedDate->addDays(rand(2, 8));
            $executedDate = $approvedDate->addDays(rand(1, 5));

            $requestedQty = rand(5, 150);
            $approvedQty = rand(max(1, $requestedQty - 20), $requestedQty);

            $movement = PharmacyMovement::create([
                'movement_number' => $this->generateMovementNumber(),
                'pharmacy_product_id' => $product->id,
                'requesting_service_id' => $requestingService->id,
                'providing_service_id' => $providingService->id,
                'requesting_user_id' => $requestingUser->id,
                'approving_user_id' => $approvingUser->id,
                'executing_user_id' => $executingUser->id,
                'requested_quantity' => $requestedQty,
                'approved_quantity' => $approvedQty,
                'executed_quantity' => $approvedQty,
                'status' => 'executed',
                'request_reason' => $this->getRequestReason(),
                'approval_notes' => 'Approved and executed',
                'execution_notes' => 'Successfully delivered to service',
                'requested_at' => $requestedDate,
                'approved_at' => $approvedDate,
                'executed_at' => $executedDate,
                'created_at' => $createdDate,
                'updated_at' => $executedDate,
            ]);

            // Add items
            for ($j = 0; $j < rand(1, 4); $j++) {
                $prod = $pharmacyProducts->random();
                $rQty = rand(5, 150);
                $aQty = rand(max(1, $rQty - 20), $rQty);
                
                PharmacyMovementItem::create([
                    'pharmacy_stock_movement_id' => $movement->id,
                    'pharmacy_movement_id' => $movement->id,
                    'product_id' => $prod->id,
                    'pharmacy_product_id' => $prod->id,
                    'requested_quantity' => $rQty,
                    'approved_quantity' => $aQty,
                    'executed_quantity' => $aQty,
                    'provided_quantity' => $aQty,
                    'administration_route' => $this->getAdministrationRoute(),
                    'frequency' => $this->getFrequency(),
                ]);
            }

            $movementCount++;
            if ($movementCount % 10 == 0) {
                echo "   ✅ Created $movementCount total movements\n";
            }
        }

        // Test Case 6: Rejected Movements (3%)
        echo "\n📝 Test Case 6: Rejected Movements (3%)\n";
        for ($i = 0; $i < 8; $i++) {
            $requestingService = $services->random();
            $providingService = $services->where('id', '!=', $requestingService->id)->random();
            $requestingUser = $users->random();
            $approvingUser = $users->random();
            $product = $pharmacyProducts->random();

            $createdDate = now()->subDays(rand(10, 60));
            $requestedDate = $createdDate->addDays(rand(1, 3));
            $rejectedDate = $requestedDate->addDays(rand(2, 8));

            $movement = PharmacyMovement::create([
                'movement_number' => $this->generateMovementNumber(),
                'pharmacy_product_id' => $product->id,
                'requesting_service_id' => $requestingService->id,
                'providing_service_id' => $providingService->id,
                'requesting_user_id' => $requestingUser->id,
                'approving_user_id' => $approvingUser->id,
                'requested_quantity' => rand(5, 150),
                'approved_quantity' => 0,
                'status' => 'rejected',
                'request_reason' => $this->getRequestReason(),
                'approval_notes' => $this->getRejectionReason(),
                'requested_at' => $requestedDate,
                'approved_at' => $rejectedDate,
                'created_at' => $createdDate,
                'updated_at' => $rejectedDate,
            ]);

            // Add items
            for ($j = 0; $j < rand(1, 3); $j++) {
                $prod = $pharmacyProducts->random();
                PharmacyMovementItem::create([
                    'pharmacy_stock_movement_id' => $movement->id,
                    'pharmacy_movement_id' => $movement->id,
                    'product_id' => $prod->id,
                    'pharmacy_product_id' => $prod->id,
                    'requested_quantity' => rand(5, 150),
                    'approved_quantity' => 0,
                    'administration_route' => $this->getAdministrationRoute(),
                    'frequency' => $this->getFrequency(),
                ]);
            }

            $movementCount++;
        }

        // Test Case 7: Cancelled Movements (2%)
        echo "\n📝 Test Case 7: Cancelled Movements (2%)\n";
        for ($i = 0; $i < 5; $i++) {
            $requestingService = $services->random();
            $providingService = $services->where('id', '!=', $requestingService->id)->random();
            $requestingUser = $users->random();
            $product = $pharmacyProducts->random();

            $createdDate = now()->subDays(rand(20, 90));
            $requestedDate = $createdDate->addDays(rand(1, 5));
            $cancelledDate = $requestedDate->addDays(rand(1, 10));

            $movement = PharmacyMovement::create([
                'movement_number' => $this->generateMovementNumber(),
                'pharmacy_product_id' => $product->id,
                'requesting_service_id' => $requestingService->id,
                'providing_service_id' => $providingService->id,
                'requesting_user_id' => $requestingUser->id,
                'requested_quantity' => rand(5, 150),
                'status' => 'cancelled',
                'request_reason' => $this->getRequestReason(),
                'approval_notes' => $this->getCancellationReason(),
                'requested_at' => $requestedDate,
                'created_at' => $createdDate,
                'updated_at' => $cancelledDate,
            ]);

            // Add items
            for ($j = 0; $j < rand(1, 3); $j++) {
                $prod = $pharmacyProducts->random();
                PharmacyMovementItem::create([
                    'pharmacy_stock_movement_id' => $movement->id,
                    'pharmacy_movement_id' => $movement->id,
                    'product_id' => $prod->id,
                    'pharmacy_product_id' => $prod->id,
                    'requested_quantity' => rand(5, 150),
                    'administration_route' => $this->getAdministrationRoute(),
                    'frequency' => $this->getFrequency(),
                ]);
            }

            $movementCount++;
        }

        echo "\n" . str_repeat('=', 70) . "\n";
        echo "✅ Successfully created $movementCount pharmacy movements!\n\n";

        // Display Statistics
        $this->displayStatistics();
    }

    private function generateMovementNumber()
    {
        $year = date('Y');
        $count = PharmacyMovement::count() + 1;
        return 'PM-' . $year . '-' . str_pad($count, 6, '0', STR_PAD_LEFT);
    }

    private function getRequestReason()
    {
        $reasons = [
            'Routine supply replenishment',
            'Emergency medication needed',
            'Surgical procedure preparation',
            'Patient treatment protocol',
            'Low stock alert',
            'Clinical trial supply',
            'Outpatient clinic needs',
            'Inpatient ward supply',
            'Specialty medication request',
            'Controlled substance replenishment',
        ];
        return $reasons[array_rand($reasons)];
    }

    private function getRejectionReason()
    {
        $reasons = [
            'Out of stock - Product unavailable',
            'Budget constraints',
            'Prescription not verified',
            'Product discontinued',
            'Quantity exceeds limit',
        ];
        return $reasons[array_rand($reasons)];
    }

    private function getCancellationReason()
    {
        $reasons = [
            'Service no longer needs medication',
            'Patient treatment plan changed',
            'Duplicate order detected',
            'Alternative medication selected',
        ];
        return $reasons[array_rand($reasons)];
    }

    private function getAdministrationRoute()
    {
        $routes = ['oral', 'iv', 'im', 'sc', 'topical', 'inhalation'];
        return $routes[array_rand($routes)];
    }

    private function getFrequency()
    {
        $frequencies = ['once_daily', 'twice_daily', 'three_times_daily', 'as_needed', 'every_6_hours'];
        return $frequencies[array_rand($frequencies)];
    }

    private function displayStatistics()
    {
        echo "📊 PHARMACY MOVEMENT STATISTICS:\n";
        echo str_repeat('-', 70) . "\n";

        $totalMovements = PharmacyMovement::count();
        $statusCounts = PharmacyMovement::groupBy('status')->selectRaw('status, count(*) as total')->pluck('total', 'status');

        echo "Total Pharmacy Movements: $totalMovements\n";
        foreach (['draft', 'pending', 'approved', 'partially_approved', 'executed', 'rejected', 'cancelled'] as $status) {
            $count = $statusCounts[$status] ?? 0;
            $percentage = ($totalMovements > 0) ? round(($count / $totalMovements) * 100, 1) : 0;
            echo "  ✓ " . str_pad(ucfirst($status), 20) . ": $count ({$percentage}%)\n";
        }

        echo "\n🏆 TOP 10 REQUESTING SERVICES:\n";
        $topServices = PharmacyMovement::groupBy('requesting_service_id')
            ->selectRaw('requesting_service_id, count(*) as total')
            ->orderByDesc('total')
            ->limit(10)
            ->pluck('total', 'requesting_service_id');

        $rank = 1;
        foreach ($topServices as $serviceId => $count) {
            $service = \App\Models\CONFIGURATION\Service::find($serviceId);
            if ($service) {
                echo "$rank. {$service->name} - $count movements\n";
                $rank++;
            }
        }

        echo "\n" . str_repeat('=', 70) . "\n";
    }
}
