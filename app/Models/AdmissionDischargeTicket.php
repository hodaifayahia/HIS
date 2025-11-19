<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdmissionDischargeTicket extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'admission_id',
        'ticket_number',
        'authorized_by',
        'generated_at',
        'created_by',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
    ];

    /**
     * Get the admission
     */
    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }

    /**
     * Get the authorizer
     */
    public function authorizer(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'authorized_by');
    }

    /**
     * Get the creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
