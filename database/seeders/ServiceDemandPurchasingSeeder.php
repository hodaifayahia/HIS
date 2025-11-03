<?php

namespace Database\Seeders;

use App\Models\ServiceDemendPurchcing;
use App\Models\ServiceDemendPurchcingItem;
use App\Models\CONFIGURATION\Service;
use App\Models\PharmacyProduct;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ServiceDemandPurchasingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get ALL services from the database
        $allServices = Service::all();
        
        if ($allServices->count() === 0) {
            $this->command->warn('No services found. Skipping ServiceDemandPurchasing seeding.');
            return;
        }

        $users = User::limit(5)->get();
        if ($users->count() < 2) {
            $this->command->warn('Need at least 2 users to seed demands');
            return;
        }

        // Get pharmacy products - these are the products being demanded
        $pharmacyProducts = PharmacyProduct::limit(15)->get();
        if ($pharmacyProducts->count() === 0) {
            $this->command->warn('No pharmacy products found. Skipping seeding.');
            return;
        }

        $this->command->info("Creating Service Demand Purchasing for {$allServices->count()} services with all scenarios...\n");

        // Create 8 scenarios for EACH service
        foreach ($allServices as $service) {
            $this->command->info("→ Creating demands for service: {$service->name}");
            
            $createdBy = $users->random();

            // Scenario 1: Draft Demand
            $this->createDraftDemand($service, $pharmacyProducts, $createdBy);

            // Scenario 2: Sent Demand
            $this->createSentDemand($service, $pharmacyProducts, $createdBy);

            // Scenario 3: Approved Demand
            $this->createApprovedDemand($service, $pharmacyProducts, $createdBy);

            // Scenario 4: Approved with Proforma Confirmed
            $this->createApprovedWithProformaConfirmed($service, $pharmacyProducts, $createdBy);

            // Scenario 5: Approved with Both Proforma and Bon Commend Confirmed
            $this->createFullyConfirmedDemand($service, $pharmacyProducts, $createdBy);

            // Scenario 6: Demand with Multiple Items
            $this->createMultiItemDemand($service, $pharmacyProducts, $createdBy);

            // Scenario 7: Demand with Expected Date
            $this->createDemandWithExpectedDate($service, $pharmacyProducts, $createdBy);

            // Scenario 8: Demand with Notes
            $this->createDemandWithNotes($service, $pharmacyProducts, $createdBy);
        }

        $totalScenarios = $allServices->count() * 8;
        $this->command->info("\n✅ Service Demand Purchasing seeded successfully!");
        $this->command->line("   📊 Total scenarios created: {$totalScenarios} (8 per service × {$allServices->count()} services)");
    }

    /**
     * Scenario 1: Draft Demand - Initial state
     */
    private function createDraftDemand($service, $pharmacyProducts, $createdBy)
    {
        DB::transaction(function () use ($service, $pharmacyProducts, $createdBy) {
            $demand = ServiceDemendPurchcing::create([
                'service_id' => $service->id,
                'status' => 'draft',
                'created_by' => $createdBy->id,
                'notes' => 'Draft demand - Awaiting items to be added and sent for approval',
            ]);

            // Add some items
            for ($i = 0; $i < 2; $i++) {
                ServiceDemendPurchcingItem::create([
                    'service_demand_purchasing_id' => $demand->id,
                    'pharmacy_product_id' => $pharmacyProducts[$i]->id,
                    'quantity' => rand(10, 50),
                    'unit_price' => $pharmacyProducts[$i]->price ?? rand(10, 100),
                    'notes' => 'Item added for draft demand',
                ]);
            }
        });

        $this->command->line('✓ Scenario 1: Draft Demand - CREATED');
    }

    /**
     * Scenario 2: Sent Demand - Ready for approval
     */
    private function createSentDemand($service, $pharmacyProducts, $createdBy)
    {
        DB::transaction(function () use ($service, $pharmacyProducts, $createdBy) {
            $demand = ServiceDemendPurchcing::create([
                'service_id' => $service->id,
                'status' => 'sent',
                'created_by' => $createdBy->id,
                'notes' => 'Sent demand - Awaiting approval from management',
            ]);

            // Add items
            for ($i = 1; $i < 4; $i++) {
                ServiceDemendPurchcingItem::create([
                    'service_demand_purchasing_id' => $demand->id,
                    'pharmacy_product_id' => $pharmacyProducts[$i]->id,
                    'quantity' => rand(20, 100),
                    'unit_price' => $pharmacyProducts[$i]->price ?? rand(10, 100),
                    'notes' => 'Sent with demand for approval',
                ]);
            }
        });

        $this->command->line('✓ Scenario 2: Sent Demand - CREATED');
    }

    /**
     * Scenario 3: Approved Demand
     */
    private function createApprovedDemand($service, $pharmacyProducts, $createdBy)
    {
        DB::transaction(function () use ($service, $pharmacyProducts, $createdBy) {
            $demand = ServiceDemendPurchcing::create([
                'service_id' => $service->id,
                'status' => 'approved',
                'created_by' => $createdBy->id,
                'notes' => 'Approved demand - Awaiting proforma confirmation',
            ]);

            // Add items
            for ($i = 2; $i < 5; $i++) {
                ServiceDemendPurchcingItem::create([
                    'service_demand_purchasing_id' => $demand->id,
                    'pharmacy_product_id' => $pharmacyProducts[$i]->id,
                    'quantity' => rand(15, 75),
                    'unit_price' => $pharmacyProducts[$i]->price ?? rand(10, 100),
                    'notes' => 'Item in approved demand',
                ]);
            }
        });

        $this->command->line('✓ Scenario 3: Approved Demand - CREATED');
    }

    /**
     * Scenario 4: Approved with Proforma Confirmed
     */
    private function createApprovedWithProformaConfirmed($service, $pharmacyProducts, $createdBy)
    {
        DB::transaction(function () use ($service, $pharmacyProducts, $createdBy) {
            $demand = ServiceDemendPurchcing::create([
                'service_id' => $service->id,
                'status' => 'approved',
                'created_by' => $createdBy->id,
                'proforma_confirmed' => true,
                'proforma_confirmed_at' => Carbon::now()->subDays(2),
                'notes' => 'Proforma confirmed - Awaiting Bon Commend confirmation',
            ]);

            // Add items
            for ($i = 3; $i < 6; $i++) {
                ServiceDemendPurchcingItem::create([
                    'service_demand_purchasing_id' => $demand->id,
                    'pharmacy_product_id' => $pharmacyProducts[$i]->id,
                    'quantity' => rand(25, 100),
                    'unit_price' => $pharmacyProducts[$i]->price ?? rand(10, 100),
                    'notes' => 'Item with proforma confirmed',
                ]);
            }
        });

        $this->command->line('✓ Scenario 4: Approved with Proforma Confirmed - CREATED');
    }

    /**
     * Scenario 5: Fully Confirmed Demand (Both Proforma and Bon Commend)
     */
    private function createFullyConfirmedDemand($service, $pharmacyProducts, $createdBy)
    {
        DB::transaction(function () use ($service, $pharmacyProducts, $createdBy) {
            $demand = ServiceDemendPurchcing::create([
                'service_id' => $service->id,
                'status' => 'approved',
                'created_by' => $createdBy->id,
                'proforma_confirmed' => true,
                'proforma_confirmed_at' => Carbon::now()->subDays(4),
                'boncommend_confirmed' => true,
                'boncommend_confirmed_at' => Carbon::now()->subDays(2),
                'notes' => 'Fully confirmed demand - Both proforma and bon commend approved',
            ]);

            // Add items
            for ($i = 4; $i < 7; $i++) {
                ServiceDemendPurchcingItem::create([
                    'service_demand_purchasing_id' => $demand->id,
                    'pharmacy_product_id' => $pharmacyProducts[$i]->id,
                    'quantity' => rand(30, 150),
                    'unit_price' => $pharmacyProducts[$i]->price ?? rand(10, 100),
                    'notes' => 'Item in fully confirmed demand',
                ]);
            }
        });

        $this->command->line('✓ Scenario 5: Fully Confirmed Demand - CREATED');
    }

    /**
     * Scenario 6: Demand with Multiple Items
     */
    private function createMultiItemDemand($service, $pharmacyProducts, $createdBy)
    {
        DB::transaction(function () use ($service, $pharmacyProducts, $createdBy) {
            $demand = ServiceDemendPurchcing::create([
                'service_id' => $service->id,
                'status' => 'approved',
                'created_by' => $createdBy->id,
                'proforma_confirmed' => true,
                'proforma_confirmed_at' => Carbon::now()->subDays(3),
                'notes' => 'Large demand with multiple items from different categories',
            ]);

            // Add many items
            $itemCount = min(8, $pharmacyProducts->count());
            for ($i = 0; $i < $itemCount; $i++) {
                ServiceDemendPurchcingItem::create([
                    'service_demand_purchasing_id' => $demand->id,
                    'pharmacy_product_id' => $pharmacyProducts[$i]->id,
                    'quantity' => rand(5, 200),
                    'unit_price' => $pharmacyProducts[$i]->price ?? rand(10, 100),
                    'notes' => 'Item ' . ($i + 1) . ' of multi-item demand',
                ]);
            }
        });

        $this->command->line('✓ Scenario 6: Multi-Item Demand - CREATED');
    }

    /**
     * Scenario 7: Demand with Expected Date
     */
    private function createDemandWithExpectedDate($service, $pharmacyProducts, $createdBy)
    {
        DB::transaction(function () use ($service, $pharmacyProducts, $createdBy) {
            $demand = ServiceDemendPurchcing::create([
                'service_id' => $service->id,
                'status' => 'sent',
                'created_by' => $createdBy->id,
                'expected_date' => Carbon::now()->addDays(15),
                'notes' => 'Demand with expected delivery date set to 15 days from now',
            ]);

            // Add items
            for ($i = 5; $i < 8; $i++) {
                ServiceDemendPurchcingItem::create([
                    'service_demand_purchasing_id' => $demand->id,
                    'pharmacy_product_id' => $pharmacyProducts[$i]->id,
                    'quantity' => rand(20, 80),
                    'unit_price' => $pharmacyProducts[$i]->price ?? rand(10, 100),
                    'notes' => 'Item with expected delivery date',
                ]);
            }
        });

        $this->command->line('✓ Scenario 7: Demand with Expected Date - CREATED');
    }

    /**
     * Scenario 8: Demand with Detailed Notes
     */
    private function createDemandWithNotes($service, $pharmacyProducts, $createdBy)
    {
        DB::transaction(function () use ($service, $pharmacyProducts, $createdBy) {
            $demand = ServiceDemendPurchcing::create([
                'service_id' => $service->id,
                'status' => 'draft',
                'created_by' => $createdBy->id,
                'expected_date' => Carbon::now()->addDays(20),
                'notes' => 'Important: High priority demand. These items are critical for ongoing operations. Please ensure quick processing and delivery. Contact department manager for any clarifications needed.',
            ]);

            // Add items
            for ($i = 6; $i < 9; $i++) {
                if ($i < $pharmacyProducts->count()) {
                    ServiceDemendPurchcingItem::create([
                        'service_demand_purchasing_id' => $demand->id,
                        'pharmacy_product_id' => $pharmacyProducts[$i]->id,
                        'quantity' => rand(40, 200),
                        'unit_price' => $pharmacyProducts[$i]->price ?? rand(10, 100),
                        'notes' => 'High priority item - Urgent delivery needed',
                    ]);
                }
            }
        });

        $this->command->line('✓ Scenario 8: Demand with Detailed Notes - CREATED');
    }
}
