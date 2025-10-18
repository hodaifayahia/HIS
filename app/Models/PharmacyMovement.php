<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\CONFIGURATION\Service;
use App\Models\User;
use App\Models\Product;
use App\Models\PharmacyMovementItem;

class PharmacyMovement extends Model
{
    protected $table = 'pharmacy_stock_movements';
    
    protected $fillable = [
        'movement_number',
        'product_id',
        'requesting_service_id',
        'providing_service_id',
        'requesting_user_id',
        'approving_user_id',
        'executing_user_id',
        'requested_quantity',
        'approved_quantity',
        'executed_quantity',
        'status',
        'request_reason',
        'approval_notes',
        'execution_notes',
        'requested_at',
        'approved_at',
        'executed_at',
        'expected_delivery_date',
        'prescription_reference',
        'patient_id',
        'urgency_level',
        'pharmacy_notes',
    ];

    protected $casts = [
        'requested_quantity' => 'decimal:2',
        'approved_quantity' => 'decimal:2',
        'executed_quantity' => 'decimal:2',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'executed_at' => 'datetime',
        'expected_delivery_date' => 'datetime',
    ];

    // Relationships
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function requestingService(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'requesting_service_id');
    }

    public function providingService(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'providing_service_id');
    }

    public function requestingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requesting_user_id');
    }

    public function approvingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approving_user_id');
    }

    public function executingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executing_user_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PharmacyMovementItem::class);
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeUrgent($query)
    {
        return $query->where('urgency_level', 'urgent');
    }

    public function scopeForService($query, $serviceId)
    {
        return $query->where(function ($q) use ($serviceId) {
            $q->where('requesting_service_id', $serviceId)
              ->orWhere('providing_service_id', $serviceId);
        });
    }

    public function scopeForPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    // Helper methods
    public function isEditable(): bool
    {
        return in_array($this->status, ['draft']);
    }

    public function canBeSent(): bool
    {
        return $this->status === 'draft' && $this->items()->count() > 0;
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'pending';
    }

    public function canBeExecuted(): bool
    {
        return in_array($this->status, ['approved', 'partially_approved']);
    }

    public function isUrgent(): bool
    {
        return $this->urgency_level === 'urgent';
    }

    public function getUrgencyLevelLabelAttribute(): string
    {
        return match($this->urgency_level) {
            'urgent' => 'Urgent',
            'high' => 'High Priority',
            'normal' => 'Normal',
            'low' => 'Low Priority',
            default => 'Normal'
        };
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($movement) {
            if (!$movement->movement_number) {
                $movement->movement_number = 'PM-' . date('Y') . '-' . 
                    str_pad(static::count() + 1, 6, '0', STR_PAD_LEFT);
            }
        });
    }
}