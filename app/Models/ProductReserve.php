<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductReserve extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'reservation_code',
        'product_id',
        'pharmacy_product_id',
        'reserved_by',
        'quantity',
        'status',
        'reserved_at',
        'expires_at',
        'fulfilled_at',
        'cancelled_at',
        'cancel_reason',
        'source',
        'reservation_notes',
        'meta',
        'reserve_id',
        'stockage_id',
        'pharmacy_stockage_id',
        'destination_service_id',
    ];

    protected $casts = [
        'reserved_at' => 'datetime',
        'expires_at' => 'datetime',
        'fulfilled_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'meta' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
    public function pharmacyProduct()
    {
        return $this->belongsTo(PharmacyProduct::class);
    }
    
    public function reserver()
    {
        return $this->belongsTo(User::class, 'reserved_by');
    }
    
    public function stockage()
    {
        return $this->belongsTo(Stockage::class)->withDefault();
    }
    
    public function pharmacyStockage()
    {
        return $this->belongsTo(PharmacyStockage::class)->withDefault();
    }
    
    public function reserve()
    {
        return $this->belongsTo(\App\Models\Stock\Reserve::class);
    }
    
    public function destinationService()
    {
        return $this->belongsTo(\App\Models\CONFIGURATION\Service::class, 'destination_service_id');
    }
    
    /**
     * Get the source stockage (either regular or pharmacy)
     */
    public function getSourceStockageAttribute()
    {
        return $this->stockage ?? $this->pharmacyStockage;
    }
}
