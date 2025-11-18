<?php

namespace App\Models\Purchasing;

use App\Models\BonCommend;
use App\Models\Fournisseur;
use App\Models\User;
use Database\Factories\ConsignmentReceptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsignmentReception extends Model
{
    use HasFactory;

    protected $table = 'consignment_receptions';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ConsignmentReceptionFactory
    {
        return ConsignmentReceptionFactory::new();
    }

    protected $fillable = [
        'consignment_code',
        'fournisseur_id',
        'reception_date',
        'unit_of_measure',
        'origin_note',
        'reception_type',
        'operation_type',
        'created_by',
        'confirmed_at',
        'confirmed_by',
        'bon_reception_id',
        'bon_entree_id',
    ];

    protected $casts = [
        'reception_date' => 'date',
        'confirmed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'total_uninvoiced',
        'total_consumed',
        'total_received',
    ];

    /**
     * Boot method - auto-generate consignment code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($consignmentReception) {
            if (empty($consignmentReception->consignment_code)) {
                $year = now()->year;
                $count = static::whereYear('created_at', $year)->count() + 1;
                $consignmentReception->consignment_code = 'CS-'.$year.'-'.str_pad($count, 6, '0', STR_PAD_LEFT);
            }

            if (empty($consignmentReception->reception_date)) {
                $consignmentReception->reception_date = now();
            }

            if (empty($consignmentReception->reception_type)) {
                $consignmentReception->reception_type = 'consignment';
            }

            if (empty($consignmentReception->operation_type)) {
                $consignmentReception->operation_type = 'manual';
            }
        });
    }

    /**
     * Relationship: Belongs to supplier
     */
    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class, 'fournisseur_id');
    }

    /**
     * Relationship: Has many items
     */
    public function items(): HasMany
    {
        return $this->hasMany(ConsignmentReceptionItem::class, 'consignment_reception_id');
    }

    /**
     * Relationship: Has many invoices (BonCommends) generated from this consignment
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(BonCommend::class, 'consignment_source_id')
            ->where('is_from_consignment', true);
    }

    /**
     * Relationship: Created by user
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship: Confirmed by user
     */
    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * Relationship: Linked BonReception (purchasing document)
     */
    public function bonReception(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BonReception::class, 'bon_reception_id');
    }

    /**
     * Relationship: Linked BonEntree (warehouse entry document)
     */
    public function bonEntree(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BonEntree::class, 'bon_entree_id');
    }

    /**
     * Computed attribute: Total uninvoiced quantity across all items
     */
    public function getTotalUninvoicedAttribute(): int
    {
        return $this->items->sum(function ($item) {
            return max(0, $item->quantity_consumed - $item->quantity_invoiced);
        });
    }

    /**
     * Computed attribute: Total consumed quantity across all items
     */
    public function getTotalConsumedAttribute(): int
    {
        return $this->items->sum('quantity_consumed');
    }

    /**
     * Computed attribute: Total received quantity across all items
     */
    public function getTotalReceivedAttribute(): int
    {
        return $this->items->sum('quantity_received');
    }

    /**
     * Computed attribute: Total value of consignment
     */
    public function getTotalValueAttribute(): float
    {
        return $this->items->sum(function ($item) {
            return $item->quantity_received * $item->unit_price;
        });
    }

    /**
     * Scope: Filter by supplier
     */
    public function scopeBySupplier($query, int $supplierId)
    {
        return $query->where('fournisseur_id', $supplierId);
    }

    /**
     * Scope: Filter by reception date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('reception_date', [$startDate, $endDate]);
    }

    /**
     * Scope: Has uninvoiced items
     */
    public function scopeHasUninvoiced($query)
    {
        return $query->whereHas('items', function ($q) {
            $q->whereRaw('quantity_consumed > quantity_invoiced');
        });
    }

    /**
     * Check if consignment has uninvoiced items
     */
    public function hasUninvoicedItems(): bool
    {
        return $this->total_uninvoiced > 0;
    }

    /**
     * Get uninvoiced items only
     */
    public function getUninvoicedItemsAttribute()
    {
        return $this->items->filter(function ($item) {
            return ($item->quantity_consumed - $item->quantity_invoiced) > 0;
        });
    }
}
