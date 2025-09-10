<?php


namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\OpinionRequest;

class OpinionRequestCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $opinionRequest;

    public function __construct(OpinionRequest $opinionRequest)
    {
        $this->opinionRequest = $opinionRequest;
    }

    public function broadcastOn(): array
    {
        // Check both possible column names - use the one that matches your database
        $receiverId = $this->opinionRequest->reciver_doctor_id;
        
        return [
            new PrivateChannel('doctor.'.$receiverId),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->opinionRequest->id,
            'sender_doctor_name' => $this->opinionRequest->senderDoctor->user->name ?? 'Unknown Doctor',
            'receiver_doctor_name' => $this->opinionRequest->receiverDoctor->user->name ?? 'Unknown Doctor',
            'request' => $this->opinionRequest->request,
            'reply' => $this->opinionRequest->Reply,
            'status' => $this->opinionRequest->status,
            'appointment_id' => $this->opinionRequest->appointment_id,
            'created_at' => $this->opinionRequest->created_at->toDateTimeString(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'OpinionRequestCreated';
    }
}