<?php

namespace Database\Seeders;

use App\Models\CONFIGURATION\Service;
use App\Models\PharmacyMovement;
use App\Models\PharmacyMovementItem;
use App\Models\PharmacyProduct;
use App\Models\PharmacyMovementInventorySelection;
use App\Models\Inventory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PharmacyStockMovementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get services - just get any services available
        $services = Service::where('is_active', true)->limit(3)->get();
        if ($services->count() < 2) {
            // If no active services, get any services
            $services = Service::limit(3)->get();
        }
        
        if ($services->count() < 2) {
            $this->command->warn('Need at least 2 services to seed stock movements');
            return;
        }

        $requestingService = $services->first();
        $providingService = $services->last();
        $users = User::limit(5)->get();

        if ($users->count() < 2) {
            $this->command->warn('Need at least 2 users to seed stock movements');
            return;
        }

        $createdBy = $users->first();
        $approver = $users->get(1);
        $receiver = $users->last();

        // Get pharmacy products
        $products = PharmacyProduct::limit(5)->get();
        if ($products->count() === 0) {
            $this->command->warn('No pharmacy products found. Skipping stock movement seeding.');
            return;
        }

        $this->command->info('Creating pharmacy stock movements with all delivery scenarios...');

        // Scenario 1: Complete Good Delivery
        $this->createGoodDeliveryScenario($requestingService, $providingService, $products, $createdBy, $approver, $receiver);

        // Scenario 2: Partial Delivery (Manque)
        $this->createPartialDeliveryScenario($requestingService, $providingService, $products, $createdBy, $approver, $receiver);

        // Scenario 3: Damaged Delivery
        $this->createDamagedDeliveryScenario($requestingService, $providingService, $products, $createdBy, $approver, $receiver);

        // Scenario 4: Mixed Delivery (Good + Manque + Damaged)
        $this->createMixedDeliveryScenario($requestingService, $providingService, $products, $createdBy, $approver, $receiver);

        // Scenario 5: Draft Pending Approval
        $this->createDraftMovement($requestingService, $providingService, $products, $createdBy);

        // Scenario 6: Approved But Not Yet In Transfer
        $this->createApprovedNotTransferredMovement($requestingService, $providingService, $products, $createdBy, $approver);

        // Scenario 7: In Transfer Pending Confirmation
        $this->createInTransferPendingConfirmation($requestingService, $providingService, $products, $createdBy, $approver);

        $this->command->info('✅ Pharmacy stock movements seeded successfully!');
    }

    /**
     * Scenario 1: All items received in good condition
     */
    private function createGoodDeliveryScenario($requestingService, $providingService, $products, $createdBy, $approver, $receiver)
    {
        DB::transaction(function () use ($requestingService, $providingService, $products, $createdBy, $approver, $receiver) {
            $movement = PharmacyMovement::create([
                'requesting_service_id' => $requestingService->id,
                'providing_service_id' => $providingService->id,
                'requesting_user_id' => $createdBy->id,
                'status' => 'completed',
                'delivery_status' => 'good',
                'request_reason' => 'Regular stock replenishment - Good delivery scenario',
                'approving_user_id' => $approver->id,
                'approved_at' => Carbon::now()->subDays(5),
                'delivery_confirmed_by' => $receiver->id,
                'delivery_confirmed_at' => Carbon::now()->subDay(),
            ]);

            // Add items
            for ($i = 0; $i < 3; $i++) {
                $product = $products[$i];
                $requestedQty = rand(50, 200);
                $approvedQty = $requestedQty;

                $item = PharmacyMovementItem::create([
                    'pharmacy_stock_movement_id' => $movement->id,
                    'pharmacy_movement_id' => $movement->id,
                    'product_id' => $product->id,
                    'pharmacy_product_id' => $product->id,
                    'requested_quantity' => $requestedQty,
                    'approved_quantity' => $approvedQty,
                    'executed_quantity' => $approvedQty,
                    'received_quantity' => $approvedQty,
                    'confirmation_status' => 'good',
                    'confirmation_notes' => 'Product received in excellent condition',
                    'confirmed_at' => Carbon::now()->subHours(2),
                    'confirmed_by' => $receiver->id,
                    'quantity_by_box' => false,
                ]);

                $this->addInventorySelections($item, $product, $approvedQty);
            }
        });

        $this->command->line('✓ Scenario 1: Good Delivery - CREATED');
    }

    /**
     * Scenario 2: Partial delivery (missing some items)
     */
    private function createPartialDeliveryScenario($requestingService, $providingService, $products, $createdBy, $approver, $receiver)
    {
        DB::transaction(function () use ($requestingService, $providingService, $products, $createdBy, $approver, $receiver) {
            $movement = PharmacyMovement::create([
                'requesting_service_id' => $requestingService->id,
                'providing_service_id' => $providingService->id,
                'requesting_user_id' => $createdBy->id,
                'status' => 'completed',
                'delivery_status' => 'manque',
                'request_reason' => 'Partial delivery scenario - Manque/Missing items',
                'approving_user_id' => $approver->id,
                'approved_at' => Carbon::now()->subDays(6),
                'delivery_confirmed_by' => $receiver->id,
                'delivery_confirmed_at' => Carbon::now()->subHours(12),
            ]);

            // Add items with shortages
            $product = $products[0];
            $requestedQty = 100;
            $approvedQty = 100;
            $receivedQty = 85;

            $item = PharmacyMovementItem::create([
                'pharmacy_stock_movement_id' => $movement->id,
                'pharmacy_movement_id' => $movement->id,
                'product_id' => $product->id,
                'pharmacy_product_id' => $product->id,
                'requested_quantity' => $requestedQty,
                'approved_quantity' => $approvedQty,
                'executed_quantity' => $receivedQty,
                'received_quantity' => $receivedQty,
                'confirmation_status' => 'manque',
                'confirmation_notes' => '15 units missing from shipment. Shortage documented.',
                'confirmed_at' => Carbon::now()->subHours(12),
                'confirmed_by' => $receiver->id,
                'quantity_by_box' => false,
            ]);

            $this->addInventorySelections($item, $product, $receivedQty);

            // Add a good item to this movement
            $product2 = $products[1];
            $item2 = PharmacyMovementItem::create([
                'pharmacy_stock_movement_id' => $movement->id,
                'pharmacy_movement_id' => $movement->id,
                'product_id' => $product2->id,
                'pharmacy_product_id' => $product2->id,
                'requested_quantity' => 150,
                'approved_quantity' => 150,
                'executed_quantity' => 150,
                'received_quantity' => 150,
                'confirmation_status' => 'good',
                'confirmation_notes' => 'Received in good condition',
                'confirmed_at' => Carbon::now()->subHours(12),
                'confirmed_by' => $receiver->id,
                'quantity_by_box' => false,
            ]);

            $this->addInventorySelections($item2, $product2, 150);
        });

        $this->command->line('✓ Scenario 2: Partial Delivery (Manque) - CREATED');
    }

    /**
     * Scenario 3: Damaged delivery
     */
    private function createDamagedDeliveryScenario($requestingService, $providingService, $products, $createdBy, $approver, $receiver)
    {
        DB::transaction(function () use ($requestingService, $providingService, $products, $createdBy, $approver, $receiver) {
            $movement = PharmacyMovement::create([
                'requesting_service_id' => $requestingService->id,
                'providing_service_id' => $providingService->id,
                'requested_by' => $createdBy->id,
                'status' => 'completed',
                'delivery_status' => 'damaged',
                'request_reason' => 'Damaged delivery scenario - Products damaged in transit',
                'approved_by' => $approver->id,
                'approved_at' => Carbon::now()->subDays(7),
                'delivery_confirmed_by' => $receiver->id,
                'delivery_confirmed_at' => Carbon::now()->subDays(2),
            ]);

            // Damaged item
            $product = $products[2];
            $requestedQty = 200;
            $approvedQty = 200;

            $item = PharmacyMovementItem::create([
                'pharmacy_stock_movement_id' => $movement->id,
                'pharmacy_movement_id' => $movement->id,
                'product_id' => $product->id,
                'pharmacy_product_id' => $product->id,
                'requested_quantity' => $requestedQty,
                'approved_quantity' => $approvedQty,
                'executed_quantity' => 0,
                'received_quantity' => 0,
                'confirmation_status' => 'damaged',
                'confirmation_notes' => 'Packaging severely damaged. All 200 units contaminated and discarded.',
                'confirmed_at' => Carbon::now()->subDays(2),
                'confirmed_by' => $receiver->id,
                'quantity_by_box' => false,
            ]);

            // Good item in same movement
            $product2 = $products[3];
            $item2 = PharmacyMovementItem::create([
                'pharmacy_stock_movement_id' => $movement->id,
                'pharmacy_movement_id' => $movement->id,
                'product_id' => $product2->id,
                'pharmacy_product_id' => $product2->id,
                'requested_quantity' => 100,
                'approved_quantity' => 100,
                'executed_quantity' => 100,
                'received_quantity' => 100,
                'confirmation_status' => 'good',
                'confirmation_notes' => 'This item received in good condition',
                'confirmed_at' => Carbon::now()->subDays(2),
                'confirmed_by' => $receiver->id,
                'quantity_by_box' => false,
            ]);

            $this->addInventorySelections($item2, $product2, 100);
        });

        $this->command->line('✓ Scenario 3: Damaged Delivery - CREATED');
    }

    /**
     * Scenario 4: Mixed delivery (Good + Manque + Damaged)
     */
    private function createMixedDeliveryScenario($requestingService, $providingService, $products, $createdBy, $approver, $receiver)
    {
        DB::transaction(function () use ($requestingService, $providingService, $products, $createdBy, $approver, $receiver) {
            $movement = PharmacyMovement::create([
                'requesting_service_id' => $requestingService->id,
                'providing_service_id' => $providingService->id,
                'requested_by' => $createdBy->id,
                'status' => 'completed',
                'delivery_status' => 'mixed',
                'request_reason' => 'Mixed delivery scenario - Contains good, damaged, and manque items',
                'approved_by' => $approver->id,
                'approved_at' => Carbon::now()->subDays(4),
                'delivery_confirmed_by' => $receiver->id,
                'delivery_confirmed_at' => Carbon::now()->subHours(6),
            ]);

            // Good item
            $product1 = $products[0];
            $item1 = PharmacyMovementItem::create([
                'pharmacy_stock_movement_id' => $movement->id,
                'pharmacy_movement_id' => $movement->id,
                'product_id' => $product1->id,
                'pharmacy_product_id' => $product1->id,
                'requested_quantity' => 100,
                'approved_quantity' => 100,
                'executed_quantity' => 100,
                'received_quantity' => 100,
                'confirmation_status' => 'good',
                'confirmation_notes' => 'Good condition',
                'confirmed_at' => Carbon::now()->subHours(6),
                'confirmed_by' => $receiver->id,
                'quantity_by_box' => false,
            ]);
            $this->addInventorySelections($item1, $product1, 100);

            // Manque item
            $product2 = $products[1];
            $item2 = PharmacyMovementItem::create([
                'pharmacy_stock_movement_id' => $movement->id,
                'pharmacy_movement_id' => $movement->id,
                'product_id' => $product2->id,
                'pharmacy_product_id' => $product2->id,
                'requested_quantity' => 150,
                'approved_quantity' => 150,
                'executed_quantity' => 120,
                'received_quantity' => 120,
                'confirmation_status' => 'manque',
                'confirmation_notes' => '30 units missing',
                'confirmed_at' => Carbon::now()->subHours(6),
                'confirmed_by' => $receiver->id,
                'quantity_by_box' => false,
            ]);
            $this->addInventorySelections($item2, $product2, 120);

            // Damaged item
            $product3 = $products[2];
            $item3 = PharmacyMovementItem::create([
                'pharmacy_stock_movement_id' => $movement->id,
                'pharmacy_movement_id' => $movement->id,
                'product_id' => $product3->id,
                'pharmacy_product_id' => $product3->id,
                'requested_quantity' => 80,
                'approved_quantity' => 80,
                'executed_quantity' => 0,
                'received_quantity' => 0,
                'confirmation_status' => 'damaged',
                'confirmation_notes' => 'Entire shipment damaged',
                'confirmed_at' => Carbon::now()->subHours(6),
                'confirmed_by' => $receiver->id,
                'quantity_by_box' => false,
            ]);
        });

        $this->command->line('✓ Scenario 4: Mixed Delivery (Good + Manque + Damaged) - CREATED');
    }

    /**
     * Scenario 5: Draft movement pending approval
     */
    private function createDraftMovement($requestingService, $providingService, $products, $createdBy)
    {
        DB::transaction(function () use ($requestingService, $providingService, $products, $createdBy) {
            $movement = PharmacyMovement::create([
                'requesting_service_id' => $requestingService->id,
                'providing_service_id' => $providingService->id,
                'requested_by' => $createdBy->id,
                'status' => 'draft',
                'request_reason' => 'Draft movement - Awaiting approval from sender',
            ]);

            // Add items
            for ($i = 0; $i < 2; $i++) {
                PharmacyMovementItem::create([
                    'pharmacy_stock_movement_id' => $movement->id,
                    'pharmacy_movement_id' => $movement->id,
                    'product_id' => $products[$i]->id,
                    'pharmacy_product_id' => $products[$i]->id,
                    'requested_quantity' => rand(50, 100),
                    'quantity_by_box' => false,
                ]);
            }
        });

        $this->command->line('✓ Scenario 5: Draft Movement - CREATED');
    }

    /**
     * Scenario 6: Approved but not yet in transfer
     */
    private function createApprovedNotTransferredMovement($requestingService, $providingService, $products, $createdBy, $approver)
    {
        DB::transaction(function () use ($requestingService, $providingService, $products, $createdBy, $approver) {
            $movement = PharmacyMovement::create([
                'requesting_service_id' => $requestingService->id,
                'providing_service_id' => $providingService->id,
                'requested_by' => $createdBy->id,
                'status' => 'approved',
                'request_reason' => 'Approved movement - Awaiting transfer initialization',
                'approved_by' => $approver->id,
                'approved_at' => Carbon::now()->subHour(),
            ]);

            // Add items with approved quantities
            for ($i = 0; $i < 2; $i++) {
                $requestedQty = rand(50, 100);
                PharmacyMovementItem::create([
                    'pharmacy_stock_movement_id' => $movement->id,
                    'pharmacy_movement_id' => $movement->id,
                    'product_id' => $products[$i]->id,
                    'pharmacy_product_id' => $products[$i]->id,
                    'requested_quantity' => $requestedQty,
                    'approved_quantity' => $requestedQty,
                    'quantity_by_box' => false,
                ]);
            }
        });

        $this->command->line('✓ Scenario 6: Approved But Not Transferred - CREATED');
    }

    /**
     * Scenario 7: In transfer pending confirmation
     */
    private function createInTransferPendingConfirmation($requestingService, $providingService, $products, $createdBy, $approver)
    {
        DB::transaction(function () use ($requestingService, $providingService, $products, $createdBy, $approver) {
            $movement = PharmacyMovement::create([
                'requesting_service_id' => $requestingService->id,
                'providing_service_id' => $providingService->id,
                'requested_by' => $createdBy->id,
                'status' => 'in_transfer',
                'request_reason' => 'In transfer - Awaiting delivery confirmation from requester',
                'approved_by' => $approver->id,
                'approved_at' => Carbon::now()->subDays(2),
            ]);

            // Add items with approved quantities but NO confirmation status
            for ($i = 0; $i < 3; $i++) {
                $requestedQty = rand(50, 150);
                $item = PharmacyMovementItem::create([
                    'pharmacy_stock_movement_id' => $movement->id,
                    'pharmacy_movement_id' => $movement->id,
                    'product_id' => $products[$i]->id,
                    'pharmacy_product_id' => $products[$i]->id,
                    'requested_quantity' => $requestedQty,
                    'approved_quantity' => $requestedQty,
                    // Note: NO confirmation_status - waiting for requester to confirm
                    'quantity_by_box' => false,
                ]);

                // Add inventory selections to make items ready for confirmation
                $this->addInventorySelections($item, $products[$i], $requestedQty);
            }
        });

        $this->command->line('✓ Scenario 7: In Transfer Pending Confirmation - CREATED');
    }

    /**
     * Helper to add inventory selections
     */
    private function addInventorySelections($item, $product, $quantity)
    {
        // Get inventory for this product from providing service
        $inventories = Inventory::where('product_id', $product->id)
            ->limit(2)
            ->get();

        if ($inventories->count() === 0) {
            return; // Skip if no inventory available
        }

        // Distribute quantity across available inventory items
        $qtyPerInventory = floor($quantity / $inventories->count());
        $remainder = $quantity % $inventories->count();

        foreach ($inventories as $index => $inventory) {
            $selectedQty = $qtyPerInventory;
            if ($index === 0) {
                $selectedQty += $remainder;
            }

            PharmacyMovementInventorySelection::create([
                'pharmacy_stock_movement_item_id' => $item->id,
                'pharmacy_movement_item_id' => $item->id,
                'inventory_id' => $inventory->id,
                'selected_quantity' => $selectedQty,
                'batch_number' => $inventory->batch_number ?? 'BATCH-' . str_pad($inventory->id, 6, '0', STR_PAD_LEFT),
                'expiry_date' => $inventory->expiry_date ?? Carbon::now()->addMonths(12),
                'pharmacist_verified' => true,
                'verification_date' => Carbon::now(),
            ]);
        }
    }
}
