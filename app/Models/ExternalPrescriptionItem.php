<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalPrescriptionItem extends Model
{
    protected $fillable = [
        'external_prescription_id',
        'pharmacy_product_id',
        'quantity',
        'quantity_by_box',
        'unit',
        'quantity_sended',
        'service_id',
        'status',
        'cancel_reason',
        'modified_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'quantity_sended' => 'decimal:2',
        'quantity_by_box' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['status_label', 'quantity_display'];

    /**
     * Boot method to update parent prescription on changes
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($item) {
            $item->prescription->updateItemCounts();
            $item->prescription->autoConfirmIfComplete();
        });

        static::deleted(function ($item) {
            $item->prescription->updateItemCounts();
            $item->prescription->autoConfirmIfComplete();
        });
    }

    /**
     * Get the prescription this item belongs to
     */
    public function prescription(): BelongsTo
    {
        return $this->belongsTo(ExternalPrescription::class, 'external_prescription_id');
    }

    /**
     * Get the pharmacy product
     */
    public function pharmacyProduct(): BelongsTo
    {
        return $this->belongsTo(PharmacyProduct::class, 'pharmacy_product_id');
    }

    /**
     * Get the service where inventory was added
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the user who modified this item
     */
    public function modifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'modified_by');
    }

    /**
     * Get status label for display
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'dispensed' => 'Dispensed',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get formatted quantity display
     */
    public function getQuantityDisplayAttribute(): string
    {
        $qty = $this->quantity_sended ?? $this->quantity;
        $unit = $this->quantity_by_box ? 'Box' : $this->unit;

        return "{$qty} {$unit}";
    }

    /**
     * Scope to filter by status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter draft items
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope to filter dispensed items
     */
    public function scopeDispensed($query)
    {
        return $query->where('status', 'dispensed');
    }

    /**
     * Scope to filter cancelled items
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }
}
