<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\B2B\Avenant;
use Carbon\Carbon;

class ActivateScheduledAvenants extends Command
{
    protected $signature = 'avenants:activate-scheduled';
    protected $description = 'Activate avenants that have reached their activation date';

    public function handle()
    {
        $today = Carbon::now()->startOfDay();

        $avenants = Avenant::where('status', 'scheduled')
            ->whereDate('activation_at', '<=', $today)
            ->get();

        foreach ($avenants as $avenant) {
            $avenant->status = 'active';
            $avenant->activation_at = $today; // optional: set when actual activation happened
            $avenant->save();

            $this->info("Activated avenant: {$avenant->id}");
        }

        $this->info("Activated {$avenants->count()} avenant(s).");
        return 0;
    }
}
