<?php

namespace App\Models;

use App\Models\CONFIGURATION\Prestation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdmissionProcedure extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'admission_id',
        'prestation_id',
        'name',
        'description',
        'status',
        'is_medication_conversion',
        'performed_by',
        'scheduled_at',
        'started_at',
        'completed_at',
        'cancelled_at',
        'created_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'is_medication_conversion' => 'boolean',
    ];

    /**
     * Get the admission
     */
    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }

    /**
     * Get the prestation
     */
    public function prestation(): BelongsTo
    {
        return $this->belongsTo(Prestation::class);
    }

    /**
     * Get the performer
     */
    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Get the creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
