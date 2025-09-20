<?php

namespace App\Providers;

use App\Events\StockMovementItemApproved;
use App\Events\StockMovementItemRejected;
use App\Listeners\LogStockMovementAudit;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class StockMovementEventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        StockMovementItemApproved::class => [
            LogStockMovementAudit::class . '@handleApproved',
        ],
        StockMovementItemRejected::class => [
            LogStockMovementAudit::class . '@handleRejected',
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}