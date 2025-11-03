<?php

namespace Database\Seeders;

use App\Models\ProductReserve;
use App\Models\PharmacyProduct;
use App\Models\PharmacyStockage;
use App\Models\CONFIGURATION\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PharmacyProductReservationSeeder extends Seeder
{
    /**
     * Run the database seeds for pharmacy product reservations
     * 
     * This seeder creates realistic pharmacy product reservations linked to pharmacy products
     */
    public function run(): void
    {
        // Disable foreign key constraints temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            // Get existing pharmacy products, stockages, and services
            $products = PharmacyProduct::where('is_active', true)->limit(50)->get();
            $stockages = PharmacyStockage::limit(10)->get();
            $services = Service::limit(5)->get();
            $users = DB::table('users')->limit(5)->get();

            if ($products->isEmpty() || $stockages->isEmpty() || $services->isEmpty()) {
                $this->command->warn('⚠️  Not enough data to seed. Please ensure pharmacy products, stockages, and services exist first.');
                return;
            }

            $now = now();
            $reservations = [];

            // Create realistic reservations
            foreach ($products as $index => $product) {
                // Skip some products randomly
                if (rand(0, 1) === 0) {
                    continue;
                }

                $randomStockage = $stockages->random();
                $randomService = $services->random();
                $randomUser = $users[rand(0, count($users) - 1)];

                // Vary the status to have realistic distribution
                $statusRandom = rand(1, 100);
                if ($statusRandom <= 50) {
                    $status = 'pending';
                    $fulfilled_at = null;
                } elseif ($statusRandom <= 85) {
                    $status = 'fulfilled';
                    $fulfilled_at = $now->copy()->subDays(rand(1, 30));
                } elseif ($statusRandom <= 95) {
                    $status = 'cancelled';
                    $fulfilled_at = null;
                } else {
                    $status = 'expired';
                    $fulfilled_at = null;
                }

                $reserved_at = $now->copy()->subDays(rand(0, 60));
                $expires_at = $reserved_at->copy()->addDays(rand(7, 30));

                $reservations[] = [
                    'reservation_code' => 'RES-' . strtoupper(str_pad(
                        $product->id . '-' . substr(md5($product->name . $index), 0, 4),
                        8,
                        '0',
                        STR_PAD_LEFT
                    )),
                    'product_id' => null, // Not a regular stock product
                    'pharmacy_product_id' => $product->id,
                    'reserved_by' => $randomUser->id,
                    'quantity' => rand(1, 50),
                    'status' => $status,
                    'reserved_at' => $reserved_at,
                    'expires_at' => $expires_at,
                    'fulfilled_at' => $fulfilled_at,
                    'cancelled_at' => $status === 'cancelled' ? $now->copy()->subDays(rand(1, 60)) : null,
                    'cancel_reason' => $status === 'cancelled' ? $this->getRandomCancelReason() : null,
                    'source' => 'pharmacy',
                    'reservation_notes' => $this->getRandomReservationNote(),
                    'meta' => json_encode([
                        'priority' => rand(1, 5),
                        'requested_by_service' => $randomService->id,
                        'batch_number' => 'BATCH-' . rand(1000, 9999),
                        'requested_date' => $reserved_at->format('Y-m-d'),
                    ]),
                    'reserve_id' => null,
                    'stockage_id' => null, // Not a regular stockage
                    'pharmacy_stockage_id' => $randomStockage->id,
                    'destination_service_id' => $randomService->id,
                    'created_at' => $reserved_at,
                    'updated_at' => $now,
                ];
            }

            // Insert in batches to avoid memory issues
            collect($reservations)->chunk(100)->each(function ($chunk) {
                ProductReserve::insert($chunk->toArray());
                $this->command->info('✅ Inserted ' . count($chunk) . ' pharmacy product reservations');
            });

            $this->command->info('✅ Successfully seeded ' . count($reservations) . ' pharmacy product reservations!');
            
            // Show statistics
            $this->showStatistics();

        } catch (\Exception $e) {
            $this->command->error('❌ Error seeding pharmacy product reservations: ' . $e->getMessage());
            throw $e;
        } finally {
            // Re-enable foreign key constraints
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * Show seeding statistics
     */
    private function showStatistics(): void
    {
        $total = ProductReserve::where('source', 'pharmacy')->count();
        $pending = ProductReserve::where('source', 'pharmacy')->where('status', 'pending')->count();
        $fulfilled = ProductReserve::where('source', 'pharmacy')->where('status', 'fulfilled')->count();
        $cancelled = ProductReserve::where('source', 'pharmacy')->where('status', 'cancelled')->count();
        $expired = ProductReserve::where('source', 'pharmacy')->where('status', 'expired')->count();

        $this->command->info('📊 Pharmacy Reservation Statistics:');
        $this->command->line('   Total: ' . $total);
        $this->command->line('   ✓ Pending: ' . $pending . ' (' . round(($pending / max($total, 1)) * 100, 1) . '%)');
        $this->command->line('   ✓ Fulfilled: ' . $fulfilled . ' (' . round(($fulfilled / max($total, 1)) * 100, 1) . '%)');
        $this->command->line('   ✓ Cancelled: ' . $cancelled . ' (' . round(($cancelled / max($total, 1)) * 100, 1) . '%)');
        $this->command->line('   ✓ Expired: ' . $expired . ' (' . round(($expired / max($total, 1)) * 100, 1) . '%)');
    }

    /**
     * Get a random cancellation reason
     */
    private function getRandomCancelReason(): string
    {
        $reasons = [
            'Product out of stock',
            'Service no longer needed',
            'Budget constraints',
            'Product discontinued',
            'Alternative product found',
            'Emergency cancellation',
            'Duplicate reservation',
            'Order error',
        ];

        return $reasons[array_rand($reasons)];
    }

    /**
     * Get a random reservation note
     */
    private function getRandomReservationNote(): ?string
    {
        $notes = [
            'Urgent - Required for critical care unit',
            'Standard reservation',
            'Low priority - For stock management',
            'Emergency medication reserve',
            'Regular scheduled order',
            'Substitute product available',
            'High demand product',
            'Reserved for specific patient case',
            'Training and stock update',
        ];

        // 60% chance of having a note
        if (rand(1, 100) <= 60) {
            return $notes[array_rand($notes)];
        }

        return null;
    }
}
