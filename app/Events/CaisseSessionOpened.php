<?php
namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use App\Models\Coffre\CaisseSession;

class CaisseSessionOpened
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public CaisseSession $session;
    public array $payload;

    public function __construct(CaisseSession $session, array $payload = [])
    {
        $this->session = $session;
        $this->payload = $payload;
    }
}
