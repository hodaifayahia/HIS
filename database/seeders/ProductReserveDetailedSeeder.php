<?php

namespace Database\Seeders;

use App\Models\PharmacyProduct;
use App\Models\PharmacyStockage;
use App\Models\ProductReserve;
use App\Models\Stock\Reserve;
use App\Models\User;
use App\Models\CONFIGURATION\Service;
use Illuminate\Database\Seeder;

class ProductReserveDetailedSeeder extends Seeder
{
    /**
     * Run the database seeds for pharmacy product reserves with comprehensive test cases
     */
    public function run(): void
    {
        $pharmacyProducts = PharmacyProduct::where('is_active', true)->limit(50)->get();
        $pharmacyStockages = PharmacyStockage::all();
        $reserves = Reserve::all();
        $users = User::all();
        $services = Service::all();

        if ($pharmacyProducts->isEmpty() || $pharmacyStockages->isEmpty() || $users->isEmpty()) {
            echo "⚠️  Missing required data (Products: {$pharmacyProducts->count()}, Stockages: {$pharmacyStockages->count()}, Users: {$users->count()})\n";
            return;
        }

        echo "\n🚀 Creating comprehensive pharmacy product reserves with test cases...\n";

        $statuses = ['pending', 'fulfilled', 'cancelled', 'expired'];
        $reservationCode = 1001;
        $created = 0;

        // Test Case 1: Standard Active Reserves (40%)
        echo "\n📝 Test Case 1: Standard Active Reserves (40%)\n";
        for ($i = 0; $i < 100; $i++) {
            $product = $pharmacyProducts->random();
            $stockage = $pharmacyStockages->random();
            $user = $users->random();
            $service = $services->random();
            $reserve = $reserves->random();

            ProductReserve::create([
                'reservation_code' => 'RES-PH-' . str_pad($reservationCode++, 6, '0', STR_PAD_LEFT),
                'pharmacy_product_id' => $product->id,
                'pharmacy_stockage_id' => $stockage->id,
                'reserved_by' => $user->id,
                'quantity' => rand(5, 100),
                'status' => 'pending',
                'source' => 'pharmacy',
                'reserved_at' => now()->subDays(rand(1, 15)),
                'expires_at' => now()->addDays(rand(5, 30)),
                'destination_service_id' => $service->id,
                'reserve_id' => $reserve->id,
                'reservation_notes' => "Standard reserve for {$product->name}",
                'meta' => [
                    'priority' => rand(1, 5),
                    'batch_number' => 'BATCH-' . rand(1000, 9999),
                    'requested_date' => now()->subDays(rand(1, 15))->toDateString(),
                    'requested_by_service' => $service->id,
                ],
                'created_at' => now()->subDays(rand(1, 15)),
                'updated_at' => now()->subDays(rand(0, 10)),
            ]);

            $created++;
            if ($created % 25 == 0) {
                echo "  ✅ Created $created reserves\n";
            }
        }

        // Test Case 2: Fulfilled Reserves with Historical Data (30%)
        echo "\n📝 Test Case 2: Fulfilled Reserves (30%)\n";
        for ($i = 0; $i < 75; $i++) {
            $product = $pharmacyProducts->random();
            $stockage = $pharmacyStockages->random();
            $user = $users->random();
            $approver = $users->random();
            $executor = $users->random();
            $service = $services->random();
            $reserve = $reserves->random();

            $reservedAt = now()->subDays(rand(30, 60));
            $fulfilledAt = $reservedAt->copy()->addDays(rand(2, 10));

            ProductReserve::create([
                'reservation_code' => 'RES-PH-' . str_pad($reservationCode++, 6, '0', STR_PAD_LEFT),
                'pharmacy_product_id' => $product->id,
                'pharmacy_stockage_id' => $stockage->id,
                'reserved_by' => $user->id,
                'quantity' => rand(10, 200),
                'status' => 'fulfilled',
                'source' => 'pharmacy',
                'reserved_at' => $reservedAt,
                'expires_at' => now()->addDays(rand(5, 30)),
                'fulfilled_at' => $fulfilledAt,
                'destination_service_id' => $service->id,
                'reserve_id' => $reserve->id,
                'reservation_notes' => "Fulfilled reserve for {$product->name} - Regular supply",
                'meta' => [
                    'priority' => rand(1, 3),
                    'batch_number' => 'BATCH-' . rand(1000, 9999),
                    'requested_date' => $reservedAt->toDateString(),
                    'fulfilled_quantity' => rand(10, 200),
                    'fulfillment_location' => $stockage->location,
                ],
                'created_at' => $reservedAt,
                'updated_at' => $fulfilledAt,
            ]);

            $created++;
            if ($created % 25 == 0) {
                echo "  ✅ Created $created reserves\n";
            }
        }

        // Test Case 3: Cancelled Reserves with Reasons (15%)
        echo "\n📝 Test Case 3: Cancelled Reserves with Reasons (15%)\n";
        $cancelReasons = [
            'Product out of stock',
            'Budget constraints',
            'Product discontinued',
            'Service no longer needs item',
            'Duplicate order',
            'Better alternative found',
            'Supplier unavailable',
            'Quality issues',
        ];

        for ($i = 0; $i < 38; $i++) {
            $product = $pharmacyProducts->random();
            $stockage = $pharmacyStockages->random();
            $user = $users->random();
            $canceller = $users->random();
            $service = $services->random();
            $reserve = $reserves->random();

            $reservedAt = now()->subDays(rand(30, 90));
            $cancelledAt = $reservedAt->copy()->addDays(rand(1, 5));

            ProductReserve::create([
                'reservation_code' => 'RES-PH-' . str_pad($reservationCode++, 6, '0', STR_PAD_LEFT),
                'pharmacy_product_id' => $product->id,
                'pharmacy_stockage_id' => $stockage->id,
                'reserved_by' => $user->id,
                'quantity' => rand(5, 100),
                'status' => 'cancelled',
                'source' => 'pharmacy',
                'reserved_at' => $reservedAt,
                'expires_at' => now()->addDays(rand(5, 30)),
                'cancelled_at' => $cancelledAt,
                'cancel_reason' => $cancelReasons[array_rand($cancelReasons)],
                'destination_service_id' => $service->id,
                'reserve_id' => $reserve->id,
                'reservation_notes' => "Cancelled reserve - {$cancelReasons[array_rand($cancelReasons)]}",
                'meta' => [
                    'priority' => rand(1, 5),
                    'batch_number' => 'BATCH-' . rand(1000, 9999),
                    'requested_date' => $reservedAt->toDateString(),
                    'cancelled_by' => $canceller->id,
                    'cancellation_reason_code' => strtoupper(substr(md5(rand()), 0, 4)),
                ],
                'created_at' => $reservedAt,
                'updated_at' => $cancelledAt,
            ]);

            $created++;
            if ($created % 25 == 0) {
                echo "  ✅ Created $created reserves\n";
            }
        }

        // Test Case 4: Expired Reserves (15%)
        echo "\n📝 Test Case 4: Expired Reserves (15%)\n";
        for ($i = 0; $i < 38; $i++) {
            $product = $pharmacyProducts->random();
            $stockage = $pharmacyStockages->random();
            $user = $users->random();
            $service = $services->random();
            $reserve = $reserves->random();

            $reservedAt = now()->subDays(rand(60, 120));
            $expiresAt = $reservedAt->copy()->addDays(rand(5, 15))->subDays(rand(1, 10)); // Already past expiry

            ProductReserve::create([
                'reservation_code' => 'RES-PH-' . str_pad($reservationCode++, 6, '0', STR_PAD_LEFT),
                'pharmacy_product_id' => $product->id,
                'pharmacy_stockage_id' => $stockage->id,
                'reserved_by' => $user->id,
                'quantity' => rand(5, 50),
                'status' => 'expired',
                'source' => 'pharmacy',
                'reserved_at' => $reservedAt,
                'expires_at' => $expiresAt,
                'destination_service_id' => $service->id,
                'reserve_id' => $reserve->id,
                'reservation_notes' => "Expired reserve - not fulfilled within timeframe",
                'meta' => [
                    'priority' => rand(1, 5),
                    'batch_number' => 'BATCH-' . rand(1000, 9999),
                    'requested_date' => $reservedAt->toDateString(),
                    'expiry_days_overdue' => now()->diffInDays($expiresAt),
                ],
                'created_at' => $reservedAt,
                'updated_at' => now()->subDays(rand(1, 30)),
            ]);

            $created++;
            if ($created % 25 == 0) {
                echo "  ✅ Created $created reserves\n";
            }
        }

        echo "\n" . str_repeat('=', 60) . "\n";
        echo "✅ Successfully created $created pharmacy product reserve records!\n\n";

        // Display Statistics
        echo "📊 PRODUCT RESERVE STATISTICS:\n";
        echo str_repeat('-', 60) . "\n";

        $totalReserves = ProductReserve::where('source', 'pharmacy')->count();
        $statsByStatus = ProductReserve::where('source', 'pharmacy')
            ->groupBy('status')
            ->selectRaw('status, count(*) as total')
            ->pluck('total', 'status');

        echo "Total Pharmacy Reserves: $totalReserves\n";
        foreach ($statuses as $status) {
            $count = $statsByStatus[$status] ?? 0;
            $percentage = ($totalReserves > 0) ? round(($count / $totalReserves) * 100, 1) : 0;
            echo "  ✓ " . ucfirst($status) . ": $count ({$percentage}%)\n";
        }

        // Top Products
        echo "\n🏆 TOP 10 MOST RESERVED PRODUCTS:\n";
        $topProducts = ProductReserve::where('source', 'pharmacy')
            ->groupBy('pharmacy_product_id')
            ->selectRaw('pharmacy_product_id, count(*) as total')
            ->orderByDesc('total')
            ->limit(10)
            ->pluck('total', 'pharmacy_product_id');

        $rank = 1;
        foreach ($topProducts as $productId => $count) {
            $product = PharmacyProduct::find($productId);
            echo "$rank. {$product->name} - $count reservations\n";
            $rank++;
        }

        // Top Services
        echo "\n🏢 TOP 10 SERVICES BY RESERVE COUNT:\n";
        $topServices = ProductReserve::where('source', 'pharmacy')
            ->groupBy('destination_service_id')
            ->selectRaw('destination_service_id, count(*) as total')
            ->orderByDesc('total')
            ->limit(10)
            ->pluck('total', 'destination_service_id');

        $rank = 1;
        foreach ($topServices as $serviceId => $count) {
            $service = Service::find($serviceId);
            echo "$rank. {$service->name} - $count reserves\n";
            $rank++;
        }

        echo "\n" . str_repeat('=', 60) . "\n";
    }
}
