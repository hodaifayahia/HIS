<?php

namespace Database\Seeders;

use App\Models\ProductReserve;
use App\Models\PharmacyProduct;
use App\Models\PharmacyStockage;
use App\Models\CONFIGURATION\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GeneratePharmacyReservationsSeeder extends Seeder
{
    /**
     * Generate a large number of pharmacy product reservations
     * Use this seeder to populate realistic data for testing and demonstration
     * 
     * Run with: php artisan db:seed --class=GeneratePharmacyReservationsSeeder
     * Or pass count: php artisan db:seed --class=GeneratePharmacyReservationsSeeder --count=500
     */
    public function run(): void
    {
        // Get count from command line or use default of 300
        $count = 300;

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            $products = PharmacyProduct::where('is_active', true)->get();
            $stockages = PharmacyStockage::get();
            $services = Service::get();
            $users = DB::table('users')->get();

            if ($products->isEmpty()) {
                $this->command->error('❌ No active pharmacy products found. Please seed PharmacyProductSeeder first.');
                return;
            }

            if ($stockages->isEmpty()) {
                $this->command->error('❌ No pharmacy stockages found. Please seed PharmacyStockageSeeder first.');
                return;
            }

            $this->command->info("🚀 Generating $count pharmacy product reservations...");

            $reservations = [];
            $now = now();
            $batchSize = 100;

            for ($i = 0; $i < $count; $i++) {
                $product = $products->random();
                $stockage = $stockages->random();
                $service = $services->random() ?? null;
                $user = $users[rand(0, count($users) - 1)];

                // Realistic status distribution
                $statusRandom = rand(1, 100);
                if ($statusRandom <= 45) {
                    $status = 'pending';
                    $fulfilled_at = null;
                } elseif ($statusRandom <= 80) {
                    $status = 'fulfilled';
                    $fulfilled_at = $now->copy()->subDays(rand(1, 45));
                } elseif ($statusRandom <= 90) {
                    $status = 'cancelled';
                    $fulfilled_at = null;
                } else {
                    $status = 'expired';
                    $fulfilled_at = null;
                }

                $reserved_at = $now->copy()->subDays(rand(1, 90));
                $expires_at = $reserved_at->copy()->addDays(rand(7, 45));

                $reservations[] = [
                    'reservation_code' => $this->generateReservationCode($product->id, $i),
                    'product_id' => null,
                    'pharmacy_product_id' => $product->id,
                    'reserved_by' => $user->id,
                    'quantity' => rand(1, 100),
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
                        'requested_by_service' => $service?->id,
                        'batch_number' => 'BATCH-' . rand(10000, 99999),
                        'requested_date' => $reserved_at->format('Y-m-d'),
                        'unit_of_measure' => $product->unit_of_measure ?? 'unit',
                    ]),
                    'reserve_id' => null,
                    'stockage_id' => null,
                    'pharmacy_stockage_id' => $stockage->id,
                    'destination_service_id' => $service?->id,
                    'created_at' => $reserved_at,
                    'updated_at' => $now,
                ];

                // Insert in batches
                if (count($reservations) >= $batchSize) {
                    ProductReserve::insert($reservations);
                    $this->command->info('  ✅ Inserted ' . count($reservations) . ' reservations (' . ($i + 1) . '/' . $count . ')');
                    $reservations = [];
                }
            }

            // Insert remaining
            if (!empty($reservations)) {
                ProductReserve::insert($reservations);
                $this->command->info('  ✅ Inserted ' . count($reservations) . ' reservations (' . $count . '/' . $count . ')');
            }

            $this->command->info('✅ Successfully generated ' . $count . ' pharmacy product reservations!');
            $this->showDetailedStatistics();

        } catch (\Exception $e) {
            $this->command->error('❌ Error: ' . $e->getMessage());
            throw $e;
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * Generate a unique reservation code
     */
    private function generateReservationCode($productId, $index): string
    {
        $timestamp = substr(time(), -4);
        $hash = substr(md5($productId . $index . $timestamp), 0, 4);
        return 'RES-' . strtoupper($timestamp . $hash);
    }

    /**
     * Show detailed statistics about the generated reservations
     */
    private function showDetailedStatistics(): void
    {
        $total = ProductReserve::where('source', 'pharmacy')->count();
        $pending = ProductReserve::where('source', 'pharmacy')->where('status', 'pending')->count();
        $fulfilled = ProductReserve::where('source', 'pharmacy')->where('status', 'fulfilled')->count();
        $cancelled = ProductReserve::where('source', 'pharmacy')->where('status', 'cancelled')->count();
        $expired = ProductReserve::where('source', 'pharmacy')->where('status', 'expired')->count();

        // By product
        $topProducts = DB::table('product_reserves')
            ->join('pharmacy_products', 'product_reserves.pharmacy_product_id', '=', 'pharmacy_products.id')
            ->where('product_reserves.source', 'pharmacy')
            ->select('pharmacy_products.name', DB::raw('count(*) as count'))
            ->groupBy('product_reserves.pharmacy_product_id')
            ->orderByRaw('count DESC')
            ->limit(5)
            ->get();

        // By service
        $topServices = DB::table('product_reserves')
            ->leftJoin('services', 'product_reserves.destination_service_id', '=', 'services.id')
            ->where('product_reserves.source', 'pharmacy')
            ->select('services.name', DB::raw('count(*) as count'))
            ->groupBy('product_reserves.destination_service_id')
            ->orderByRaw('count DESC')
            ->limit(5)
            ->get();

        $this->command->line('');
        $this->command->info('📊 Pharmacy Reservation Statistics:');
        $this->command->line('═══════════════════════════════════════════════');
        $this->command->line('   Total: ' . $total);
        $this->command->line('   ✓ Pending:   ' . str_pad($pending, 4) . ' (' . round(($pending / max($total, 1)) * 100, 1) . '%)');
        $this->command->line('   ✓ Fulfilled: ' . str_pad($fulfilled, 4) . ' (' . round(($fulfilled / max($total, 1)) * 100, 1) . '%)');
        $this->command->line('   ✓ Cancelled: ' . str_pad($cancelled, 4) . ' (' . round(($cancelled / max($total, 1)) * 100, 1) . '%)');
        $this->command->line('   ✓ Expired:   ' . str_pad($expired, 4) . ' (' . round(($expired / max($total, 1)) * 100, 1) . '%)');
        $this->command->line('═══════════════════════════════════════════════');

        if ($topProducts->isNotEmpty()) {
            $this->command->line('');
            $this->command->info('🏆 Top 5 Most Reserved Products:');
            foreach ($topProducts as $i => $product) {
                $this->command->line('   ' . ($i + 1) . '. ' . substr($product->name, 0, 40) . ' - ' . $product->count . ' reservations');
            }
        }

        if ($topServices->isNotEmpty()) {
            $this->command->line('');
            $this->command->info('🏢 Top 5 Services by Reservation Count:');
            foreach ($topServices as $i => $service) {
                $serviceName = $service->name ?? 'Unassigned';
                $this->command->line('   ' . ($i + 1) . '. ' . substr($serviceName, 0, 40) . ' - ' . $service->count . ' reservations');
            }
        }
    }

    /**
     * Get random cancellation reasons
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
            'Change in patient treatment plan',
            'Supplier delay',
            'Incorrect quantity ordered',
            'Inventory overstocked',
        ];

        return $reasons[array_rand($reasons)];
    }

    /**
     * Get random reservation notes
     */
    private function getRandomReservationNote(): ?string
    {
        $notes = [
            'Urgent - Required for critical care unit',
            'Standard reservation - monthly stock',
            'Low priority - For general stock management',
            'Emergency medication reserve - high priority',
            'Regular scheduled order for supply',
            'Substitute product available if needed',
            'High demand medication - may need expedited delivery',
            'Reserved for specific patient case',
            'Training and stock management update',
            'Seasonal medication for winter months',
            'Backup supply in case of shortage',
            'Clinical trial medication',
            'Preventive medicine program',
        ];

        // 70% chance of having a note
        if (rand(1, 100) <= 70) {
            return $notes[array_rand($notes)];
        }

        return null;
    }
}
