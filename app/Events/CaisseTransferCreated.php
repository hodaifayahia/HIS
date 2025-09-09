<?php
namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use App\Models\Caisse\CaisseTransfer;

class CaisseTransferCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public CaisseTransfer $transfer;

    public function __construct(CaisseTransfer $transfer)
    {
        $this->transfer = $transfer;
    }
}
