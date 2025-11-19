<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BonReceptionItem extends Model
{
    protected $fillable = [
        'bon_reception_id',
        'bon_commend_item_id',
        'product_id',
        'quantity_ordered',
        'quantity_received',
        'quantity_surplus',
        'quantity_shortage',
        'unit',
        'unit_price',
        'status',
        'notes',
        'is_unexpected',
        'received_at',
    ];

    protected $casts = [
        'quantity_ordered' => 'integer',
        'quantity_received' => 'integer',
        'quantity_surplus' => 'integer',
        'quantity_shortage' => 'integer',
        'unit_price' => 'decimal:2',
        'is_unexpected' => 'boolean',
        'received_at' => 'datetime',
    ];

    // Relationships
    public function bonReception(): BelongsTo
    {
        return $this->belongsTo(BonReception::class);
    }

    public function bonCommendItem(): BelongsTo
    {
        return $this->belongsTo(BonCommendItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Computed attributes
    public function getTotalValueAttribute()
    {
        return $this->quantity_received * $this->unit_price;
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'En attente',
            'received' => 'Reçu',
            'partial' => 'Partiel',
            'excess' => 'Excédent',
            'missing' => 'Manquant',
        ];

        return $labels[$this->status] ?? $this->status;
    }
}
