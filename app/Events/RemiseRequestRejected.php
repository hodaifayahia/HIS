<?php


namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Reception\RemiseRequest;
use App\Models\Reception\RemiseRequestApproval;
use App\Models\Reception\RemiseRequestNotification;

class RemiseRequestRejected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $remiseRequest;
    public $approval;
    public $notification;

    public function __construct(RemiseRequest $remiseRequest, RemiseRequestApproval $approval, RemiseRequestNotification $notification)
    {
        $this->remiseRequest = $remiseRequest;
        $this->approval = $approval;
        $this->notification = $notification;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('user.' . $this->remiseRequest->sender_id);
    }

    public function broadcastAs()
    {
        return 'remise.request.rejected';
    }

    public function broadcastWith()
    {
        return [
            'request' => $this->remiseRequest->toArray(),
            'approval' => $this->approval->toArray(),
            'notification' => $this->notification->toArray()
        ];
    }
}