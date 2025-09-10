<?php

namespace App\Events\Reception;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Reception\RemiseRequest;
use App\Models\Reception\RemiseRequestNotification;

class RemiseRequestCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $remiseRequest;
    public $notification;

    public function __construct(RemiseRequest $remiseRequest, RemiseRequestNotification $notification)
    {
        $this->remiseRequest = $remiseRequest;
        $this->notification = $notification;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('user.' . $this->remiseRequest->receiver_id);
    }

    public function broadcastAs()
    {
        return 'remise.request.created';
    }

    public function broadcastWith()
    {
        return [
            'request' => $this->remiseRequest->toArray(),
            'notification' => $this->notification->toArray()
        ];
    }
}