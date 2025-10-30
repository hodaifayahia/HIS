<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyMovementInventorySelection extends Model
{
    protected $fillable = [
        'pharmacy_stock_movement_item_id',
        'pharmacy_movement_item_id',
        'pharmacy_inventory_id',
        'inventory_id',
        'selected_quantity',
        'batch_number',
        'expiry_date',
        'lot_number',
        'manufacturer',
        'storage_conditions',
        'quality_check_status',
        'pharmacist_verified',
        'verification_date',
        'notes',
    ];

    protected $casts = [
        'selected_quantity' => 'decimal:2',
        'expiry_date' => 'date',
        'verification_date' => 'datetime',
        'pharmacist_verified' => 'boolean',
    ];

    protected $appends = ['quantity'];

    // Accessors
    public function getQuantityAttribute()
    {
        return $this->selected_quantity;
    }

    // Relationships
    public function pharmacyMovementItem(): BelongsTo
    {
        return $this->belongsTo(PharmacyMovementItem::class, 'pharmacy_stock_movement_item_id');
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function pharmacyInventory(): BelongsTo
    {
        return $this->belongsTo(PharmacyInventory::class, 'pharmacy_inventory_id');
    }

    // Pharmacy-specific methods
    public function isExpiringSoon($days = 30): bool
    {
        if (! $this->expiry_date) {
            return false;
        }

        return $this->expiry_date->diffInDays(now()) <= $days;
    }

    public function isExpired(): bool
    {
        if (! $this->expiry_date) {
            return false;
        }

        return $this->expiry_date->isPast();
    }

    public function getExpiryStatusAttribute(): string
    {
        if ($this->isExpired()) {
            return 'expired';
        } elseif ($this->isExpiringSoon()) {
            return 'expiring_soon';
        }

        return 'valid';
    }

    public function getExpiryStatusLabelAttribute(): string
    {
        return match ($this->expiry_status) {
            'expired' => 'Expired',
            'expiring_soon' => 'Expiring Soon',
            'valid' => 'Valid',
            default => 'Unknown'
        };
    }

    public function getQualityCheckStatusLabelAttribute(): string
    {
        return match ($this->quality_check_status) {
            'passed' => 'Quality Check Passed',
            'failed' => 'Quality Check Failed',
            'pending' => 'Quality Check Pending',
            'not_required' => 'Quality Check Not Required',
            default => $this->quality_check_status ?? 'Not Checked'
        };
    }

    public function getStorageConditionsLabelAttribute(): string
    {
        return match ($this->storage_conditions) {
            'room_temperature' => 'Room Temperature (15-25°C)',
            'refrigerated' => 'Refrigerated (2-8°C)',
            'frozen' => 'Frozen (-20°C)',
            'controlled_room_temperature' => 'Controlled Room Temperature (20-25°C)',
            'cool_dry_place' => 'Cool, Dry Place',
            'protect_from_light' => 'Protect from Light',
            'protect_from_moisture' => 'Protect from Moisture',
            default => $this->storage_conditions ?? 'Standard Storage'
        };
    }

    public function isPharmacistVerified(): bool
    {
        return $this->pharmacist_verified === true;
    }

    public function canBeDispensed(): bool
    {
        return ! $this->isExpired() &&
               $this->quality_check_status !== 'failed' &&
               $this->isPharmacistVerified();
    }

    public function requiresSpecialHandling(): bool
    {
        return in_array($this->storage_conditions, [
            'refrigerated',
            'frozen',
            'protect_from_light',
            'controlled_room_temperature',
        ]);
    }

    // Scopes
    public function scopeVerified($query)
    {
        return $query->where('pharmacist_verified', true);
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expiry_date')
                ->orWhere('expiry_date', '>', now());
        });
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>', now());
    }

    public function scopeQualityCheckPassed($query)
    {
        return $query->where('quality_check_status', 'passed');
    }

    public function scopeByBatch($query, $batchNumber)
    {
        return $query->where('batch_number', $batchNumber);
    }

    public function scopeByManufacturer($query, $manufacturer)
    {
        return $query->where('manufacturer', $manufacturer);
    }
}
