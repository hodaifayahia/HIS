<?php

namespace App\Events\Reception;

use App\Models\Reception\ficheNavette;
use App\Models\CONFIGURATION\PrestationPackage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PrestationsConvertedToPackage
 * 
 * Fired AFTER prestations are successfully converted to a package.
 * Signals that the operation is complete and resources have been allocated.
 * 
 * Use cases:
 * - Audit logging: "Package replaced"
 * - Notifications: Send toast/email to user
 * - Analytics: Track conversion frequency
 * - Cascading workflows: Trigger downstream processes (invoicing, etc.)
 */
class PrestationsConvertedToPackage
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ficheNavette $fiche,
        public PrestationPackage $newPackage,
        public array $removedItemIds, // IDs of items that were removed
        public bool $isCascading = false, // Whether this replaced another package
        public ?array $oldPackageIds = null, // Packages being replaced (for cascading)
    ) {}
}
