<?php

namespace App\Providers;

use App\Events\CaisseSessionOpened;
use App\Events\PdfGeneratedEvent;
use App\Listeners\CreateCaisseTransferForSession;
use App\Listeners\StoreConsultationRecordListener;
use App\Listeners\StoreGeneratedPdfDocumentListener;
use App\Models\Nursing\PatientConsumption;
use App\Observers\Nursing\PatientConsumptionObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

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
        CaisseSessionOpened::class => [
            CreateCaisseTransferForSession::class,
        ],
        \App\Events\CaisseTransferCreated::class => [
            \App\Listeners\MarkSessionAsTransferred::class,
        ],

    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {

        //  parent::boot();

        // // Register observers here, not in the $listen array
        // PatientConsumption::observe(PatientConsumptionObserver::class);
    }
}
