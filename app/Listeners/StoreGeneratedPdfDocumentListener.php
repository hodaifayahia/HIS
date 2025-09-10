<?php

namespace App\Listeners;

use App\Events\PdfGeneratedEvent;
use App\Models\PatientDocement;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class StoreGeneratedPdfDocumentListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PdfGeneratedEvent $event): void
    {
        try {
            // Just verify the file exists and log the information
            $exists = Storage::disk('public')->exists($event->pdfPath);
            Log::info("PDF generation verification:", [
                'path' => $event->pdfPath,
                'exists' => $exists,
                'full_path' => Storage::disk('public')->path($event->pdfPath)
            ]);

            if (!$exists) {
                Log::error("PDF file not found at path: {$event->pdfPath}");
            }

            // You can add any additional processing here that doesn't involve
            // creating another document record

        } catch (\Exception $e) {
            Log::error("Error in PDF generation verification: " . $e->getMessage(), [
                'pdf_path' => $event->pdfPath ?? 'not set',
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}