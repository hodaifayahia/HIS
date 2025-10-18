<?php

namespace App\Events;

use App\Models\Patient;
use App\Models\Doctor;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConsultationBlocked implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $patient;
    public $requestingDoctor;
    public $activeDoctor;
    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct(Patient $patient, Doctor $requestingDoctor, Doctor $activeDoctor)
    {
        $this->patient = $patient;
        $this->requestingDoctor = $requestingDoctor;
        $this->activeDoctor = $activeDoctor;
        $this->message = "Patient {$patient->Firstname} {$patient->Lastname} is currently in consultation with Dr. {$activeDoctor->user->name}";
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('doctor.' . $this->requestingDoctor->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'consultation.blocked';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'patient_id' => $this->patient->id,
            'patient_name' => $this->patient->Firstname . ' ' . $this->patient->Lastname,
            'active_doctor_id' => $this->activeDoctor->id,
            'active_doctor_name' => $this->activeDoctor->user->name ?? 'Unknown',
            'message' => $this->message,
            'timestamp' => now()->toISOString(),
        ];
    }
}
