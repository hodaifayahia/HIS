<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdmissionBillingRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'admission_id',
        'procedure_id',
        'item_type',
        'description',
        'amount',
        'is_paid',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'float',
        'is_paid' => 'boolean',
    ];

    /**
     * Get the admission
     */
    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }

    /**
     * Get the procedure
     */
    public function procedure(): BelongsTo
    {
        return $this->belongsTo(AdmissionProcedure::class);
    }

    /**
     * Get the creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
