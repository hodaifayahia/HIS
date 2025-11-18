<?php

namespace App\Listeners\Inventory;

use App\Events\Inventory\PriceChanged;
use App\Models\User;
use App\Notifications\Inventory\PriceChangedNotification;
use Illuminate\Support\Facades\Log;

class NotifyPriceChange
{
    /**
     * Handle the event.
     */
    public function handle(PriceChanged $event): void
    {
        try {
            // Get users who should be notified (users with manage-pricing permission)
            // For now, notify all admin users - you can customize this based on roles/permissions
            $users = User::whereHas('roles', function ($query) {
                $query->where('name', 'admin');
            })->get();

            foreach ($users as $user) {
                // Don't notify the person who made the change
                if ($user->id === $event->changedBy->id) {
                    continue;
                }

                $user->notify(new PriceChangedNotification(
                    $event->oldPricing,
                    $event->newPricing,
                    $event->changedBy
                ));
            }

            Log::info('Price change notifications sent', [
                'product_id' => $event->newPricing->product_id ?? $event->newPricing->pharmacy_product_id,
                'service_group_id' => $event->newPricing->service_group_id,
                'notified_users' => $users->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send price change notifications', [
                'error' => $e->getMessage(),
                'product_id' => $event->newPricing->product_id ?? $event->newPricing->pharmacy_product_id,
            ]);
        }
    }
}
