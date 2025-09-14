<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stockage extends Model
{
    protected $fillable = [
        'name',
        'description',
        'location',
        'capacity',
        'type',
        'status',
        'service_id',
        'temperature_controlled',
        'security_level',
        'location_code',
        'warehouse_type'
    ];

    protected $casts = [
        'capacity' => 'integer',
        'temperature_controlled' => 'boolean',
        'service_id' => 'integer'
    ];

    /**
     * Get the manager (user) associated with this stockage
     */
    // manager relationship removed — stockage no longer tracks a manager on the model

    /**
     * Get the service associated with this stockage
     */
    public function service()
    {
        return $this->belongsTo(\App\Models\CONFIGURATION\Service::class, 'service_id');
    }

    /**
     * Get the stockage tools for this stockage
     */
    public function stockageTools()
    {
        return $this->hasMany(StockageTool::class);
    }

    /**
     * Get the inventories for this stockage
     */
    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Get the products in this stockage
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'inventories');
    }
}
