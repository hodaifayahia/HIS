<?php

namespace App\Events\Inventory;

use App\Models\Inventory\ServiceGroupProductPricing;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PriceChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ServiceGroupProductPricing $oldPricing;

    public ServiceGroupProductPricing $newPricing;

    public User $changedBy;

    /**
     * Create a new event instance.
     */
    public function __construct(
        ServiceGroupProductPricing $oldPricing,
        ServiceGroupProductPricing $newPricing,
        User $changedBy
    ) {
        $this->oldPricing = $oldPricing;
        $this->newPricing = $newPricing;
        $this->changedBy = $changedBy;
    }
}
