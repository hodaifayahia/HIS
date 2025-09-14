<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovementInventorySelection extends Model
{
    protected $fillable = [
        'stock_movement_item_id',
        'inventory_id',
        'selected_quantity',
    ];

    protected $casts = [
        'selected_quantity' => 'decimal:2',
    ];

    // Relationships
    public function stockMovementItem(): BelongsTo
    {
        return $this->belongsTo(StockMovementItem::class);
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }
}
