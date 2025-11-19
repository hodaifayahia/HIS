<?php

namespace App\Models\Purchasing;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsignmentReceptionItem extends Model
{
    protected $table = 'consignment_reception_items';

    protected $fillable = [
        'consignment_reception_id',
        'product_id',
        'product_type',
        'quantity_received',
        'quantity_consumed',
        'quantity_invoiced',
        'unit_price',
    ];

    protected $casts = [
        'quantity_received' => 'integer',
        'quantity_consumed' => 'integer',
        'quantity_invoiced' => 'integer',
        'unit_price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'quantity_uninvoiced',
        'total_value',
        'consumed_value',
        'uninvoiced_value',
    ];

    /**
     * Relationship: Belongs to consignment reception
     */
    public function consignmentReception(): BelongsTo
    {
        return $this->belongsTo(ConsignmentReception::class, 'consignment_reception_id');
    }

    /**
     * Relationship: Belongs to product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Computed attribute: Quantity uninvoiced (consumed - invoiced)
     */
    public function getQuantityUninvoicedAttribute(): int
    {
        return max(0, $this->quantity_consumed - $this->quantity_invoiced);
    }

    /**
     * Computed attribute: Total value of received items
     */
    public function getTotalValueAttribute(): float
    {
        return $this->quantity_received * $this->unit_price;
    }

    /**
     * Computed attribute: Value of consumed items
     */
    public function getConsumedValueAttribute(): float
    {
        return $this->quantity_consumed * $this->unit_price;
    }

    /**
     * Computed attribute: Value of uninvoiced items
     */
    public function getUninvoicedValueAttribute(): float
    {
        return $this->quantity_uninvoiced * $this->unit_price;
    }

    /**
     * Increment consumed quantity
     */
    public function incrementConsumed(int $quantity = 1): void
    {
        $this->increment('quantity_consumed', $quantity);
    }

    /**
     * Increment invoiced quantity
     */
    public function incrementInvoiced(int $quantity): void
    {
        $this->increment('quantity_invoiced', $quantity);
    }

    /**
     * Check if item can be invoiced
     */
    public function canBeInvoiced(): bool
    {
        return $this->quantity_uninvoiced > 0;
    }

    /**
     * Check if item is fully invoiced
     */
    public function isFullyInvoiced(): bool
    {
        return $this->quantity_invoiced >= $this->quantity_consumed;
    }

    /**
     * Check if item is fully consumed
     */
    public function isFullyConsumed(): bool
    {
        return $this->quantity_consumed >= $this->quantity_received;
    }

    /**
     * Get remaining stock (not yet consumed)
     */
    public function getRemainingStockAttribute(): int
    {
        return max(0, $this->quantity_received - $this->quantity_consumed);
    }

    /**
     * Scope: Has uninvoiced quantity
     */
    public function scopeHasUninvoiced($query)
    {
        return $query->whereRaw('quantity_consumed > quantity_invoiced');
    }

    /**
     * Scope: Filter by product
     */
    public function scopeByProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }
}
