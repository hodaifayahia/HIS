<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\B2B\Convention;
use Carbon\Carbon;

class ActivateScheduledConventions extends Command
{
    protected $signature = 'conventions:activate-scheduled';
    protected $description = 'Activate conventions that have reached their activation date';

    public function handle()
    {
        $today = Carbon::now()->startOfDay();

        $conventions = Convention::where('status', 'scheduled')
            ->whereDate('activation_at', '<=', $today)
            ->get();

        foreach ($conventions as $convention) {
            $convention->status = 'active';
            $convention->activation_at = $today; // optional, just to record
            $convention->save();

            $this->info("Activated convention: {$convention->id}");
        }

        $this->info("Activated {$conventions->count()} convention(s)");
        return 0;
    }
}
