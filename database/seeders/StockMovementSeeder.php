<?php

namespace Database\Seeders;

use App\Models\CONFIGURATION\Service;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockMovementItem;
use App\Models\User;
use App\Models\Inventory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StockMovementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Delete all existing stock movements and items
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('stock_movement_items')->truncate();
        DB::table('stock_movements')->truncate();
        DB::statement('ALTER TABLE stock_movements AUTO_INCREMENT = 1;');
        DB::statement('ALTER TABLE stock_movement_items AUTO_INCREMENT = 1;');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Get services
        $services = Service::all();
        if ($services->count() < 2) {
            $this->command->error('Need at least 2 services to create stock movements');
            return;
        }

        // Get users
        $users = User::limit(3)->get();
        if ($users->count() < 1) {
            $this->command->error('Need at least 1 user to create stock movements');
            return;
        }

        $requestingUser = $users->first();
        $approvingUser = $users->count() > 1 ? $users->skip(1)->first() : $requestingUser;
        $executingUser = $users->count() > 2 ? $users->skip(2)->first() : $requestingUser;

        // Get products with inventory (global, for reference)
        $allProducts = Product::whereHas('inventories')->limit(50)->get();
        if ($allProducts->count() < 3) {
            $this->command->error('Need at least 3 products with inventory');
            return;
        }

        $this->command->info('Creating stock movements between ALL services...');
        $this->command->info("Total services: {$services->count()}");
        $this->command->newLine();

        $movementCounter = 0;

        // Create movements for each service requesting from other services
        foreach ($services as $requestingService) {
            $this->command->info("📦 Creating movements for: {$requestingService->name}");
            
            // Get other services to request from
            $otherServices = $services->where('id', '!=', $requestingService->id);
            
            if ($otherServices->isEmpty()) {
                continue;
            }

            // For each requesting service, create movements from 2-3 providing services
            $providingServices = $otherServices->take(min(3, $otherServices->count()));
            
            foreach ($providingServices as $providingService) {
                // Create 2 movements per service pair (one draft, one with random status)
                $statuses = ['draft', 'pending', 'approved', 'partially_approved', 'rejected', 'in_transfer', 'completed'];
                $randomStatus = $statuses[array_rand($statuses)];
                
                // Movement 1: Draft
                $this->createMovementWithStatus(
                    $requestingService, 
                    $providingService, 
                    $requestingUser, 
                    $approvingUser, 
                    $executingUser, 
                    $allProducts, 
                    'draft'
                );
                $movementCounter++;
                
                // Movement 2: Random status
                $this->createMovementWithStatus(
                    $requestingService, 
                    $providingService, 
                    $requestingUser, 
                    $approvingUser, 
                    $executingUser, 
                    $allProducts, 
                    $randomStatus
                );
                $movementCounter++;
            }
        }

        $this->command->newLine();
        $this->command->info("✅ Created {$movementCounter} stock movements across all services!");
    }

    private function createMovementWithStatus($requestingService, $providingService, $requestingUser, $approvingUser, $executingUser, $products, $status)
    {
        switch ($status) {
            case 'draft':
                $this->createDraftMovement($requestingService, $providingService, $requestingUser, $products);
                break;
            case 'pending':
                $this->createPendingMovement($requestingService, $providingService, $requestingUser, $products);
                break;
            case 'approved':
                $this->createApprovedMovement($requestingService, $providingService, $requestingUser, $approvingUser, $products);
                break;
            case 'partially_approved':
                $this->createPartiallyApprovedMovement($requestingService, $providingService, $requestingUser, $approvingUser, $products);
                break;
            case 'rejected':
                $this->createRejectedMovement($requestingService, $providingService, $requestingUser, $approvingUser, $products);
                break;
            case 'in_transfer':
                $this->createInTransferMovement($requestingService, $providingService, $requestingUser, $approvingUser, $executingUser, $products);
                break;
            case 'completed':
                // Randomly choose completion type
                $completionTypes = ['good', 'damage', 'manque', 'mixed'];
                $type = $completionTypes[array_rand($completionTypes)];
                
                switch ($type) {
                    case 'good':
                        $this->createCompletedMovement($requestingService, $providingService, $requestingUser, $approvingUser, $executingUser, $products);
                        break;
                    case 'damage':
                        $this->createCompletedWithDamageMovement($requestingService, $providingService, $requestingUser, $approvingUser, $executingUser, $products);
                        break;
                    case 'manque':
                        $this->createCompletedWithManqueMovement($requestingService, $providingService, $requestingUser, $approvingUser, $executingUser, $products);
                        break;
                    case 'mixed':
                        $this->createMixedConfirmationMovement($requestingService, $providingService, $requestingUser, $approvingUser, $executingUser, $products);
                        break;
                }
                break;
        }
    }

    private function generateMovementNumber()
    {
        $count = StockMovement::count() + 1;
        return 'SM-' . date('Y') . '-' . str_pad($count, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get products available in a service's inventory
     */
    private function getProductsForService($providingService, $limit = 20, $fallbackProducts = null)
    {
        $products = Product::whereHas('inventories', function($q) use ($providingService) {
            $q->whereHas('stockage', function($sq) use ($providingService) {
                $sq->where('service_id', $providingService->id);
            });
        })->limit($limit)->get();

        // If no products found in service, use fallback
        if ($products->count() === 0 && $fallbackProducts) {
            $products = $fallbackProducts->take($limit);
        }

        return $products;
    }

    private function createDraftMovement($requestingService, $providingService, $user, $products)
    {
        $movement = StockMovement::create([
            'movement_number' => $this->generateMovementNumber(),
            'requesting_service_id' => $requestingService->id,
            'providing_service_id' => $providingService->id,
            'requesting_user_id' => $user->id,
            'status' => 'draft',
            'request_reason' => 'Draft: Still adding items to the request',
            'expected_delivery_date' => now()->addDays(7),
            'created_at' => now()->subDays(1),
        ]);

        // Get products that exist in the providing service's inventory
        $availableProducts = Product::whereHas('inventories', function($q) use ($providingService) {
            $q->whereHas('stockage', function($sq) use ($providingService) {
                $sq->where('service_id', $providingService->id);
            });
        })->limit(20)->get();

        if ($availableProducts->count() === 0) {
            $availableProducts = $products; // Fallback to any products
        }

        // Add 3 items to draft
        foreach ($availableProducts->take(3) as $product) {
            StockMovementItem::create([
                'stock_movement_id' => $movement->id,
                'product_id' => $product->id,
                'requested_quantity' => rand(10, 50),
                'quantity_by_box' => false,
                'notes' => 'Draft item',
            ]);
        }

        $this->command->info("✓ Created DRAFT movement (#{$movement->id})");
    }

    private function createPendingMovement($requestingService, $providingService, $user, $products)
    {
        $movement = StockMovement::create([
            'movement_number' => $this->generateMovementNumber(),
            'requesting_service_id' => $requestingService->id,
            'providing_service_id' => $providingService->id,
            'requesting_user_id' => $user->id,
            'status' => 'pending',
            'request_reason' => 'Urgent: Need supplies for patient care',
            'requested_at' => now()->subHours(2),
            'expected_delivery_date' => now()->addDays(3),
            'created_at' => now()->subHours(3),
        ]);

        $availableProducts = $this->getProductsForService($providingService, 20, $products);

        foreach ($availableProducts->take(4) as $product) {
            StockMovementItem::create([
                'stock_movement_id' => $movement->id,
                'product_id' => $product->id,
                'requested_quantity' => rand(20, 100),
                'quantity_by_box' => rand(0, 1),
                'notes' => 'Awaiting approval',
            ]);
        }

        $this->command->info("✓ Created PENDING movement (#{$movement->id})");
    }

    private function createApprovedMovement($requestingService, $providingService, $requestingUser, $approvingUser, $products)
    {
        $movement = StockMovement::create([
            'movement_number' => $this->generateMovementNumber(),
            'requesting_service_id' => $requestingService->id,
            'providing_service_id' => $providingService->id,
            'requesting_user_id' => $requestingUser->id,
            'approving_user_id' => $approvingUser->id,
            'status' => 'approved',
            'request_reason' => 'Regular restocking',
            'approval_notes' => 'All items approved - stock available',
            'requested_at' => now()->subDays(1),
            'approved_at' => now()->subHours(1),
            'expected_delivery_date' => now()->addDays(2),
            'created_at' => now()->subDays(2),
        ]);

        $availableProducts = $this->getProductsForService($providingService, 20, $products);

        foreach ($availableProducts->take(3) as $product) {
            $requestedQty = rand(30, 80);
            StockMovementItem::create([
                'stock_movement_id' => $movement->id,
                'product_id' => $product->id,
                'requested_quantity' => $requestedQty,
                'approved_quantity' => $requestedQty,
                'quantity_by_box' => false,
                'notes' => 'Approved in full',
            ]);
        }

        $this->command->info("✓ Created APPROVED movement (#{$movement->id})");
    }

    private function createPartiallyApprovedMovement($requestingService, $providingService, $requestingUser, $approvingUser, $products)
    {
        $movement = StockMovement::create([
            'movement_number' => $this->generateMovementNumber(),
            'requesting_service_id' => $requestingService->id,
            'providing_service_id' => $providingService->id,
            'requesting_user_id' => $requestingUser->id,
            'approving_user_id' => $approvingUser->id,
            'status' => 'partially_approved',
            'request_reason' => 'Emergency supplies needed',
            'approval_notes' => 'Partially approved - limited stock available',
            'requested_at' => now()->subHours(5),
            'approved_at' => now()->subHours(2),
            'expected_delivery_date' => now()->addDays(1),
            'created_at' => now()->subHours(6),
        ]);

        $availableProducts = $this->getProductsForService($providingService, 20, $products);
        $productList = $availableProducts->take(5)->values();
        $itemIndex = 0;
        foreach ($productList as $product) {
            $requestedQty = rand(50, 100);
            
            if ($itemIndex < 3) {
                // Approve first 3 with reduced quantity
                StockMovementItem::create([
                    'stock_movement_id' => $movement->id,
                    'product_id' => $product->id,
                    'requested_quantity' => $requestedQty,
                    'approved_quantity' => round($requestedQty * 0.6),
                    'quantity_by_box' => false,
                    'notes' => 'Approved with reduced quantity due to limited stock',
                ]);
            } else {
                // Reject last 2
                StockMovementItem::create([
                    'stock_movement_id' => $movement->id,
                    'product_id' => $product->id,
                    'requested_quantity' => $requestedQty,
                    'approved_quantity' => 0,
                    'quantity_by_box' => false,
                    'notes' => 'Rejected - out of stock',
                ]);
            }
            $itemIndex++;
        }

        $this->command->info("✓ Created PARTIALLY APPROVED movement (#{$movement->id})");
    }

    private function createRejectedMovement($requestingService, $providingService, $requestingUser, $approvingUser, $products)
    {
        $movement = StockMovement::create([
            'movement_number' => $this->generateMovementNumber(),
            'requesting_service_id' => $requestingService->id,
            'providing_service_id' => $providingService->id,
            'requesting_user_id' => $requestingUser->id,
            'approving_user_id' => $approvingUser->id,
            'status' => 'rejected',
            'request_reason' => 'Requested supplies',
            'approval_notes' => 'Request rejected - insufficient stock for all items',
            'requested_at' => now()->subDays(2),
            'approved_at' => now()->subDays(1),
            'expected_delivery_date' => now()->addDays(5),
            'created_at' => now()->subDays(3),
        ]);

        $availableProducts = $this->getProductsForService($providingService, 20, $products);

        foreach ($availableProducts->take(3) as $product) {
            StockMovementItem::create([
                'stock_movement_id' => $movement->id,
                'product_id' => $product->id,
                'requested_quantity' => rand(100, 200),
                'approved_quantity' => 0,
                'quantity_by_box' => false,
                'notes' => 'Rejected - Out of stock',
            ]);
        }

        $this->command->info("✓ Created REJECTED movement (#{$movement->id})");
    }

    private function createInTransferMovement($requestingService, $providingService, $requestingUser, $approvingUser, $executingUser, $products)
    {
        $movement = StockMovement::create([
            'movement_number' => $this->generateMovementNumber(),
            'requesting_service_id' => $requestingService->id,
            'providing_service_id' => $providingService->id,
            'requesting_user_id' => $requestingUser->id,
            'approving_user_id' => $approvingUser->id,
            'executing_user_id' => $executingUser->id,
            'status' => 'in_transfer',
            'request_reason' => 'Transfer in progress',
            'approval_notes' => 'Approved and inventory selected',
            'execution_notes' => 'Items packed and ready for delivery',
            'requested_at' => now()->subDays(1),
            'approved_at' => now()->subHours(12),
            'executed_at' => now()->subHours(2),
            'expected_delivery_date' => now()->addHours(6),
            'created_at' => now()->subDays(2),
        ]);

        $availableProducts = $this->getProductsForService($providingService, 20, $products);

        foreach ($availableProducts->take(3) as $product) {
            $requestedQty = rand(20, 50);
            $item = StockMovementItem::create([
                'stock_movement_id' => $movement->id,
                'product_id' => $product->id,
                'requested_quantity' => $requestedQty,
                'approved_quantity' => $requestedQty,
                'executed_quantity' => $requestedQty,
                'provided_quantity' => $requestedQty,
                'quantity_by_box' => false,
                'notes' => 'In transit to requesting service',
            ]);
        }

        $this->command->info("✓ Created IN TRANSFER movement (#{$movement->id})");
    }

    private function createCompletedMovement($requestingService, $providingService, $requestingUser, $approvingUser, $executingUser, $products)
    {
        $movement = StockMovement::create([
            'movement_number' => $this->generateMovementNumber(),
            'requesting_service_id' => $requestingService->id,
            'providing_service_id' => $providingService->id,
            'requesting_user_id' => $requestingUser->id,
            'approving_user_id' => $approvingUser->id,
            'executing_user_id' => $executingUser->id,
            'status' => 'completed',
            'request_reason' => 'Completed delivery',
            'approval_notes' => 'All approved',
            'execution_notes' => 'Delivered successfully',
            'requested_at' => now()->subDays(3),
            'approved_at' => now()->subDays(2),
            'executed_at' => now()->subDays(1),
            'expected_delivery_date' => now()->subDays(1),
            'created_at' => now()->subDays(4),
        ]);

        $availableProducts = $this->getProductsForService($providingService, 20, $products);

        foreach ($availableProducts->take(4) as $product) {
            $requestedQty = rand(30, 60);
            StockMovementItem::create([
                'stock_movement_id' => $movement->id,
                'product_id' => $product->id,
                'requested_quantity' => $requestedQty,
                'approved_quantity' => $requestedQty,
                'executed_quantity' => $requestedQty,
                'provided_quantity' => $requestedQty,
                'quantity_by_box' => false,
                'notes' => 'Completed successfully - Received in good condition',
            ]);
        }

        $this->command->info("✓ Created COMPLETED movement (#{$movement->id})");
    }

    private function createCompletedWithDamageMovement($requestingService, $providingService, $requestingUser, $approvingUser, $executingUser, $products)
    {
        $movement = StockMovement::create([
            'movement_number' => $this->generateMovementNumber(),
            'requesting_service_id' => $requestingService->id,
            'providing_service_id' => $providingService->id,
            'requesting_user_id' => $requestingUser->id,
            'approving_user_id' => $approvingUser->id,
            'executing_user_id' => $executingUser->id,
            'status' => 'completed',
            'request_reason' => 'Completed with some damage',
            'approval_notes' => 'Approved',
            'execution_notes' => 'Some items damaged during transport',
            'requested_at' => now()->subDays(5),
            'approved_at' => now()->subDays(4),
            'executed_at' => now()->subDays(3),
            'expected_delivery_date' => now()->subDays(3),
            'created_at' => now()->subDays(6),
        ]);

        $availableProducts = $this->getProductsForService($providingService, 20, $products);

        foreach ($availableProducts->take(3) as $product) {
            $requestedQty = rand(40, 80);
            StockMovementItem::create([
                'stock_movement_id' => $movement->id,
                'product_id' => $product->id,
                'requested_quantity' => $requestedQty,
                'approved_quantity' => $requestedQty,
                'executed_quantity' => $requestedQty,
                'provided_quantity' => $requestedQty,
                'quantity_by_box' => false,
                'notes' => 'Completed with damage - Package damaged during delivery',
            ]);
        }

        $this->command->info("✓ Created COMPLETED WITH DAMAGE movement (#{$movement->id})");
    }

    private function createCompletedWithManqueMovement($requestingService, $providingService, $requestingUser, $approvingUser, $executingUser, $products)
    {
        $movement = StockMovement::create([
            'movement_number' => $this->generateMovementNumber(),
            'requesting_service_id' => $requestingService->id,
            'providing_service_id' => $providingService->id,
            'requesting_user_id' => $requestingUser->id,
            'approving_user_id' => $approvingUser->id,
            'executing_user_id' => $executingUser->id,
            'status' => 'completed',
            'request_reason' => 'Completed with shortage',
            'approval_notes' => 'Approved full quantity',
            'execution_notes' => 'Partial delivery - some items short',
            'requested_at' => now()->subDays(4),
            'approved_at' => now()->subDays(3),
            'executed_at' => now()->subDays(2),
            'expected_delivery_date' => now()->subDays(2),
            'created_at' => now()->subDays(5),
        ]);

        $availableProducts = $this->getProductsForService($providingService, 20, $products);

        foreach ($availableProducts->take(3) as $product) {
            $requestedQty = rand(50, 100);
            $receivedQty = round($requestedQty * 0.7); // 70% received
            StockMovementItem::create([
                'stock_movement_id' => $movement->id,
                'product_id' => $product->id,
                'requested_quantity' => $requestedQty,
                'approved_quantity' => $requestedQty,
                'executed_quantity' => $requestedQty,
                'provided_quantity' => $receivedQty,
                'quantity_by_box' => false,
                'notes' => "Completed with shortage - Only received {$receivedQty} out of {$requestedQty}",
            ]);
        }

        $this->command->info("✓ Created COMPLETED WITH MANQUE movement (#{$movement->id})");
    }

    private function createMixedConfirmationMovement($requestingService, $providingService, $requestingUser, $approvingUser, $executingUser, $products)
    {
        $movement = StockMovement::create([
            'movement_number' => $this->generateMovementNumber(),
            'requesting_service_id' => $requestingService->id,
            'providing_service_id' => $providingService->id,
            'requesting_user_id' => $requestingUser->id,
            'approving_user_id' => $approvingUser->id,
            'executing_user_id' => $executingUser->id,
            'status' => 'completed',
            'request_reason' => 'Mixed delivery results',
            'approval_notes' => 'All items approved',
            'execution_notes' => 'Mixed results - some good, some issues',
            'requested_at' => now()->subDays(6),
            'approved_at' => now()->subDays(5),
            'executed_at' => now()->subDays(4),
            'expected_delivery_date' => now()->subDays(4),
            'created_at' => now()->subDays(7),
        ]);

        $availableProducts = $this->getProductsForService($providingService, 20, $products);
        $productList = $availableProducts->take(6);
        $statuses = ['good', 'good', 'damaged', 'damaged', 'manque', 'good'];
        
        $statusIndex = 0;
        foreach ($productList as $product) {
            $requestedQty = rand(40, 80);
            $status = $statuses[$statusIndex];
            
            $data = [
                'stock_movement_id' => $movement->id,
                'product_id' => $product->id,
                'requested_quantity' => $requestedQty,
                'approved_quantity' => $requestedQty,
                'executed_quantity' => $requestedQty,
                'quantity_by_box' => false,
            ];

            if ($status === 'manque') {
                $receivedQty = round($requestedQty * 0.6);
                $data['provided_quantity'] = $receivedQty;
                $data['notes'] = "Shortage: received {$receivedQty} out of {$requestedQty}";
            } elseif ($status === 'damaged') {
                $data['provided_quantity'] = $requestedQty;
                $data['notes'] = 'Items damaged during transport';
            } else {
                $data['provided_quantity'] = $requestedQty;
                $data['notes'] = 'Received in perfect condition';
            }

            StockMovementItem::create($data);
            $statusIndex++;
        }

        $this->command->info("✓ Created MIXED CONFIRMATION movement (#{$movement->id})");
    }
}
