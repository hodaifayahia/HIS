<?php
namespace App\Listeners;

use App\Events\CaisseTransferCreated;
use App\Models\Caisse\CaisseSession;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class MarkSessionAsTransferred
{
    public function handle(CaisseTransferCreated $event)
    {
        $transfer = $event->transfer;

        try {
            // Find an active/open session for the from_user and caisse
            $session = CaisseSession::where('user_id', $transfer->from_user_id)
                         ->where('caisse_id', $transfer->caisse_id)
                         ->whereIn('status', ['open', 'active'])
                         ->orderBy('ouverture_at', 'desc')
                         ->first();

            if ($session) {
                // Update a flag column if exists; otherwise set a meta field
                if (Schema::hasColumn((new CaisseSession)->getTable(), 'is_transfer')) {
                    $session->is_transfer = true;
                    $session->save();
                } else {
                    // attempt to set a 'is_transferred' attribute in the DB if available
                    $session->update(['is_transferred' => true]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to mark session as transferred', ['error' => $e->getMessage(), 'transfer_id' => $transfer->id]);
        }
    }
}
