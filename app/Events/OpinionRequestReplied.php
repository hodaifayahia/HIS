<?php
// Create a new event for replies
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\OpinionRequest;

class OpinionRequestReplied implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $opinionRequest;
    public $reply;

    public function __construct(OpinionRequest $opinionRequest)
    {
        $this->opinionRequest = $opinionRequest;
        $this->reply =  $this->opinionRequest->Reply;
    }

public function broadcastOn(): array
{
    return [
        new PrivateChannel('doctor.'.$this->opinionRequest->sender_doctor_id), // Changed from reciver_doctor_id to sender_doctor_id
    ];
}
 public function broadcastWith(): array
{
    return [
        'id' => $this->opinionRequest->id,
        'reply' => $this->reply,
        'status' => 'replied',
        'replied_at' => $this->opinionRequest->updated_at,
        'receiver_doctor_name' => $this->opinionRequest->receiverDoctor->user->name ?? 'Unknown Doctor', // This is correct - it's the doctor who replied
    ];
}

    public function broadcastAs(): string
    {
        return 'OpinionRequestReplied';
    }
}