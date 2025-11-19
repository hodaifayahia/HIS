<?php

namespace App\Models;

use App\Models\CONFIGURATION\Prestation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionTreatment extends Model
{
    use HasFactory;

    protected $fillable = [
        'admission_id',
        'doctor_id',
        'prestation_id',
        'entered_at',
        'exited_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'entered_at' => 'datetime',
        'exited_at' => 'datetime',
    ];

    /**
     * Get the admission
     */
    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }

    /**
     * Get the doctor
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Get the prestation
     */
    public function prestation(): BelongsTo
    {
        return $this->belongsTo(Prestation::class);
    }

    /**
     * Get the creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get duration in minutes
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->entered_at || !$this->exited_at) {
            return null;
        }
        
        return $this->entered_at->diffInMinutes($this->exited_at);
    }
}
