<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExternalPrescription extends Model
{
    protected $fillable = [
        'prescription_code',
        'doctor_id',
        'created_by',
        'status',
        'description',
        'total_items',
        'dispensed_items',
    ];

    protected $casts = [
        'total_items' => 'integer',
        'dispensed_items' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['status_label'];

    /**
     * Boot method to auto-generate prescription code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($prescription) {
            if (! $prescription->prescription_code) {
                $prescription->prescription_code = 'EXT-PRESC-'.str_pad(
                    (self::max('id') ?? 0) + 1,
                    6,
                    '0',
                    STR_PAD_LEFT
                );
            }
        });

        static::saved(function ($prescription) {
            $prescription->updateItemCounts();
        });
    }

    /**
     * Get the doctor who created this prescription
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Get the user who created this prescription
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all items in this prescription
     */
    public function items(): HasMany
    {
        return $this->hasMany(ExternalPrescriptionItem::class);
    }

    /**
     * Get status label for display
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'confirmed' => 'Confirmed',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status),
        };
    }

    /**
     * Update total and dispensed item counts
     */
    public function updateItemCounts(): void
    {
        $this->total_items = $this->items()->count();
        $this->dispensed_items = $this->items()->where('status', 'dispensed')->count();
        $this->saveQuietly();
    }

    /**
     * Check if all items are processed (dispensed or cancelled)
     */
    public function allItemsProcessed(): bool
    {
        return $this->items()->where('status', 'draft')->count() === 0;
    }

    /**
     * Auto-confirm if all items are processed
     */
    public function autoConfirmIfComplete(): void
    {
        if ($this->status === 'draft' && $this->allItemsProcessed() && $this->items()->count() > 0) {
            $this->update(['status' => 'confirmed']);
        }
    }

    /**
     * Scope to filter by status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by creator
     */
    public function scopeCreatedBy($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Scope to search by prescription code or description
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('prescription_code', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }
}
