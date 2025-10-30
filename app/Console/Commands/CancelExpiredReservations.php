<?php

namespace App\Console\Commands;

use App\Models\ProductReserve;
use App\Models\Inventory;
use App\Models\PharmacyInventory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CancelExpiredReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:cancel-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically cancel expired product reservations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for expired reservations...');

        // Find all pending reservations that have expired
        $expiredReservations = ProductReserve::where('status', 'pending')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        if ($expiredReservations->isEmpty()) {
            $this->info('No expired reservations found.');
            return 0;
        }

        $count = 0;
        foreach ($expiredReservations as $reservation) {
            try {
                DB::transaction(function () use ($reservation, &$count) {
                    // Return stock to inventory
                    $this->returnStock($reservation);

                    // Update reservation status
                    $reservation->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                        'cancel_reason' => 'Automatically cancelled - Reservation expired on ' . $reservation->expires_at->format('Y-m-d H:i:s')
                    ]);

                    $count++;
                    $this->info("Cancelled reservation: {$reservation->reservation_code}");
                });
            } catch (\Exception $e) {
                $this->error("Failed to cancel reservation {$reservation->reservation_code}: {$e->getMessage()}");
            }
        }

        $this->info("Successfully cancelled {$count} expired reservation(s).");
        return 0;
    }

    /**
     * Return stock to inventory
     */
    private function returnStock(ProductReserve $reserve): void
    {
        if ($reserve->product_id && $reserve->stockage_id) {
            // Return to regular product inventory
            $inventory = Inventory::where('product_id', $reserve->product_id)
                ->where('stockage_id', $reserve->stockage_id)
                ->first();
            
            if ($inventory) {
                $inventory->increment('quantity', $reserve->quantity);
            }
        } elseif ($reserve->pharmacy_product_id && $reserve->pharmacy_stockage_id) {
            // Return to pharmacy product inventory
            $inventory = PharmacyInventory::where('pharmacy_product_id', $reserve->pharmacy_product_id)
                ->where('pharmacy_stockage_id', $reserve->pharmacy_stockage_id)
                ->first();
            
            if ($inventory) {
                $inventory->increment('quantity', $reserve->quantity);
            }
        }
    }
}
