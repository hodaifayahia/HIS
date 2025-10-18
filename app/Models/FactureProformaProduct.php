<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FactureProformaProduct extends Model
{
    protected $table = 'factureproforma_products';

    protected $fillable = [
        'factureproforma_id',
        'product_id',
        'quantity',
        'price',
        'unit',
        'quantity_sended',
        'confirmation_status',
        'confirmed_at',
        'confirmed_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'quantity_sended' => 'integer',
        'price' => 'decimal:2',
        'unit' => 'string',
        'confirmed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];


    /**
     * Get the facture proforma that owns this product.
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
     * Get the total price for this product line.
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
}
