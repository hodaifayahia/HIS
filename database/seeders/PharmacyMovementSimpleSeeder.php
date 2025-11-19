<?php

namespace Database\Seeders;

use App\Models\PharmacyMovement;
use App\Models\PharmacyMovementItem;
use App\Models\PharmacyProduct;
use App\Models\User;
use App\Models\CONFIGURATION\Service;
use Illuminate\Database\Seeder;

class PharmacyMovementSimpleSeeder extends Seeder
{
    private $count = 0;

    public function run(): void
    {
        $products = PharmacyProduct::where('is_active', true)->limit(100)->get();
        $users = User::all();
        $services = Service::all();

        if ($products->isEmpty() || $users->isEmpty() || $services->isEmpty()) {
            echo "⚠️ Missing data\n";
            return;
        }

        echo "\n🚀 Creating pharmacy movements...\n";

        // Draft: 50
        $this->createMovements($products, $users, $services, 'draft', 50);

        // Pending: 63
        $this->createMovements($products, $users, $services, 'pending', 63);

        // Approved: 63
        $this->createMovements($products, $users, $services, 'approved', 63);

        // In Transfer: 38
        $this->createMovements($products, $users, $services, 'in_transfer', 38);

        // Completed: 30
        $this->createMovements($products, $users, $services, 'completed', 30);

        // Rejected: 8
        $this->createMovements($products, $users, $services, 'rejected', 8);

        // Cancelled: 5
        $this->createMovements($products, $users, $services, 'cancelled', 5);

        echo "\n" . str_repeat('=', 70) . "\n";
        echo "✅ Created " . $this->count . " pharmacy movements!\n";
        $this->showStats();
    }

    private function createMovements($products, $users, $services, $status, $quantity)
    {
        echo "\n📝 Creating $quantity $status movements...\n";

        for ($i = 0; $i < $quantity; $i++) {
            $reqService = $services->random();
            $provService = $services->where('id', '!=', $reqService->id)->random();
            $product = $products->random();
            $user1 = $users->random();
            $user2 = $users->random();
            $user3 = $users->random();

            $data = [
                'movement_number' => 'PM-' . date('Y') . '-' . str_pad(PharmacyMovement::count() + 1, 6, '0', STR_PAD_LEFT),
                'pharmacy_product_id' => $product->id,
                'requesting_service_id' => $reqService->id,
                'providing_service_id' => $provService->id,
                'requesting_user_id' => $user1->id,
                'requested_quantity' => rand(5, 150),
                'status' => $status,
                'request_reason' => $this->reasons()[array_rand($this->reasons())],
            ];

            if ($status !== 'draft') {
                $data['approving_user_id'] = $user2->id;
                $data['approved_quantity'] = $status === 'rejected' ? 0 : rand(5, $data['requested_quantity']);
                $data['requested_at'] = now()->subDays(rand(5, 60));
                $data['approved_at'] = $data['requested_at']->addDays(rand(2, 10));
            }

            if ($status === 'executed') {
                $data['executing_user_id'] = $user3->id;
                $data['executed_quantity'] = $data['approved_quantity'];
                $data['executed_at'] = $data['approved_at']->addDays(rand(1, 5));
            }

            $movement = PharmacyMovement::create($data);

            // Add 1-3 items
            for ($j = 0; $j < rand(1, 3); $j++) {
                $prod = $products->random();
                $rQty = rand(5, 100);
                $itemData = [
                    'pharmacy_movement_id' => $movement->id,
                    'product_id' => $prod->id,
                    'requested_quantity' => $rQty,
                    'administration_route' => $this->routes()[array_rand($this->routes())],
                ];

                if ($status !== 'draft') {
                    $itemData['approved_quantity'] = $status === 'rejected' ? 0 : rand(max(1, $rQty - 20), $rQty);
                }

                if ($status === 'executed') {
                    $itemData['executed_quantity'] = $itemData['approved_quantity'];
                    $itemData['provided_quantity'] = $itemData['approved_quantity'];
                }

                PharmacyMovementItem::create($itemData);
            }

            $this->count++;
            if ($this->count % 25 == 0) {
                echo "   ✅ Created $this->count total\n";
            }
        }
    }

    private function reasons()
    {
        return [
            'Routine supply',
            'Emergency needed',
            'Low stock alert',
            'Surgical prep',
            'Clinic needs',
            'Ward supply',
        ];
    }

    private function routes()
    {
        return ['oral', 'iv', 'im', 'sc', 'topical', 'inhalation'];
    }

    private function showStats()
    {
        echo "📊 PHARMACY MOVEMENT STATISTICS:\n";
        echo str_repeat('-', 70) . "\n";

        $total = PharmacyMovement::count();
        $byStatus = PharmacyMovement::groupBy('status')->selectRaw('status, count(*) as total')->pluck('total', 'status');

        foreach (['draft', 'pending', 'approved', 'in_transfer', 'completed', 'rejected', 'cancelled'] as $s) {
            $cnt = $byStatus[$s] ?? 0;
            $pct = $total > 0 ? round(100 * $cnt / $total, 1) : 0;
            echo "  ✓ " . str_pad(ucfirst(str_replace('_', ' ', $s)), 20) . ": $cnt ({$pct}%)\n";
        }

        echo "\n🏆 TOP SERVICES:\n";
        $topSvc = PharmacyMovement::groupBy('requesting_service_id')->selectRaw('requesting_service_id, count(*) as total')->orderByDesc('total')->limit(10)->pluck('total', 'requesting_service_id');
        $r = 1;
        foreach ($topSvc as $id => $cnt) {
            $svc = \App\Models\CONFIGURATION\Service::find($id);
            echo "$r. {$svc->name} - $cnt\n";
            $r++;
        }

        echo str_repeat('=', 70) . "\n";
    }
}
