<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class ServiceDemendPurchcingItem extends Model
{
    protected $table = 'service_demand_purchasing_items';
    
    protected $fillable = [
        'service_demand_purchasing_id',
        'product_id',
        'quantity',
        'unit_price',
        'quantity_by_box',
        'status',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'quantity_by_box' => 'boolean',
    ];

    public function demand(): BelongsTo
    {
        return $this->belongsTo(ServiceDemendPurchcing::class, 'service_demand_purchasing_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function fournisseurAssignments(): HasMany
    {
        return $this->hasMany(ServiceDemandItemFournisseur::class, 'service_demand_purchasing_item_id');
    }

    /**
     * Get bon commends for this item through the service demand relationship
     */
    public function bonCommends()
    {
        return BonCommend::where('service_demand_purchasing_id', $this->service_demand_purchasing_id)
            ->whereHas('items', function($query) {
                $query->where('product_id', $this->product_id);
            });
    }

    public function getTotalPriceAttribute()
    {
        return $this->quantity * ($this->unit_price ?? 0);
    }

    public function getTotalAssignedQuantityAttribute()
    {
        return $this->fournisseurAssignments->sum('assigned_quantity');
    }

    public function getRemainingQuantityAttribute()
    {
        return $this->quantity - $this->getTotalAssignedQuantityAttribute();
    }

    public function getIsFullyAssignedAttribute()
    {
        return $this->getTotalAssignedQuantityAttribute() >= $this->quantity;
    }
}
