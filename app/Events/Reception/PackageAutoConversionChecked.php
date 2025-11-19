<?php

namespace App\Events\Reception;

use App\Models\Reception\ficheNavette;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PackageAutoConversionChecked
 * 
 * Fired when a fiche navette's items potentially match a package.
 * Allows listeners to perform actions after auto-conversion logic runs.
 * 
 * Use case: Log audit trails, notify users, trigger downstream workflows
 */
class PackageAutoConversionChecked
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ficheNavette $fiche,
        public array $conversionResult, // ['should_convert' => bool, 'package_id' => ?, ...]
        public array $itemIds = [], // Items involved in conversion attempt
    ) {}
}
