<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Product;
use App\Models\PharmacyProduct;

class ServiceDemendPurchcingItem extends Model
{
    protected $table = 'service_demand_purchasing_items';

    protected $fillable = [
        'service_demand_purchasing_id',
        'product_id',
        'pharmacy_product_id',
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

    /**
     * Pharmacy product relation (used when item belongs to pharmacy module)
     */
    public function pharmacyProduct(): BelongsTo
    {
        return $this->belongsTo(PharmacyProduct::class, 'pharmacy_product_id');
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
            ->where(function ($q) {
                $q->whereHas('items', function ($query) {
                    $query->where('product_id', $this->product_id);
                })
                ->orWhereHas('items', function ($query) {
                    $query->where('pharmacy_product_id', $this->pharmacy_product_id);
                });
            });
    }

    /**
     * Helper accessor to get whichever product object is present (stock or pharmacy)
     */
    public function getProductObjectAttribute()
    {
        return $this->product ?? $this->pharmacyProduct;
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
