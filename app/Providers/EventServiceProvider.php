<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Events\PdfGeneratedEvent;
use App\Listeners\StoreConsultationRecordListener;
use App\Listeners\StoreGeneratedPdfDocumentListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     */
 protected $listen = [
    PdfGeneratedEvent::class => [
        StoreConsultationRecordListener::class,
        StoreGeneratedPdfDocumentListener::class,
    ],
];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }
}
