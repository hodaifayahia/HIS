<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BonCommendItem extends Model
{
    protected $table = 'bon_commend_items';
    
    protected $fillable = [
        'bon_commend_id',
        'factureproforma_id', // Note: Based on migration, this links to factureproforma
        'product_id',
        'quantity',
        'quntity_by_box', // Note: Typo in migration - should be quantity_by_box
        'quantity_desired',
        'price',
        'unit',
        'status',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'quantity_desired' => 'integer',
        'price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the bon commend that owns this item.
     */
    public function bonCommend(): BelongsTo
    {
        return $this->belongsTo(BonCommend::class, 'bon_commend_id');
    }

    /**
     * Get the facture proforma that owns this bon commend item.
     * Note: Based on migration structure, bon commend items are linked to factureproforma
     */
    public function factureProforma(): BelongsTo
    {
        return $this->belongsTo(FactureProforma::class, 'factureproforma_id');
    }

    /**
     * Get the product details.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id');
    }

    /**
     * Get the total price for this item line.
     */
    public function getTotalPriceAttribute()
    {
        return $this->quantity * $this->price;
    }

    /**
     * Get the product name.
     */
    public function getProductNameAttribute()
    {
        return $this->product?->name;
    }

    /**
     * Get the product code.
     */
    public function getProductCodeAttribute()
    {
        return $this->product?->code ?? $this->product?->product_code;
    }

    /**
     * Scope a query to only include pending items.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include approved items.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query to only include rejected items.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Get whether this item is by box.
     */
    public function getQuantityByBoxAttribute()
    {
        return !empty($this->quntity_by_box);
    }
}
