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

class ConsultationCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $patient;
    public $doctor;
    public $appointmentId;

    /**
     * Create a new event instance.
     */
    public function __construct(Patient $patient, Doctor $doctor, int $appointmentId)
    {
        $this->patient = $patient;
        $this->doctor = $doctor;
        $this->appointmentId = $appointmentId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('consultations'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'consultation.completed';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'patient_id' => $this->patient->id,
            'patient_name' => $this->patient->Firstname . ' ' . $this->patient->Lastname,
            'doctor_id' => $this->doctor->id,
            'doctor_name' => $this->doctor->user->name ?? 'Unknown',
            'appointment_id' => $this->appointmentId,
            'message' => "Patient {$this->patient->Firstname} {$this->patient->Lastname} consultation completed",
            'timestamp' => now()->toISOString(),
        ];
    }
}
