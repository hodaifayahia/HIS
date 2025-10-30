<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PharmacyStockage extends Model
{
    use HasFactory;

    protected $table = 'pharmacy_stockages';

    protected $fillable = [
        'name',
        'location',
        'capacity',
        'type',
        'description',
        'service_id',
        'status',
        'manager_id',
        'temperature_controlled',
        'security_level',
        'location_code',
        'warehouse_type',
        'pharmacy_storage_id',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'service_id' => 'integer',
        'manager_id' => 'integer',
        'pharmacy_storage_id' => 'integer',
        'temperature_controlled' => 'boolean',
    ];

    /**
     * Get the manager of this stockage
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get the service associated with this stockage
     */
    public function service()
    {
        return $this->belongsTo(\App\Models\CONFIGURATION\Service::class, 'service_id');
    }

    /**
     * Get the pharmacy storage that owns this stockage
     */
    public function storage()
    {
        return $this->belongsTo(PharmacyStorage::class, 'pharmacy_storage_id');
    }

    /**
     * Get the pharmacy inventories for this stockage
     */
    public function pharmacyInventories()
    {
        return $this->hasMany(PharmacyInventory::class, 'pharmacy_stockage_id');
    }

    /**
     * Alias for pharmacyInventories() for backward compatibility
     */
    public function inventories()
    {
        return $this->pharmacyInventories();
    }

    /**
     * Get the pharmacy products in this stockage
     */
    public function pharmacyProducts()
    {
        return $this->belongsToMany(PharmacyProduct::class, 'pharmacy_inventories', 'pharmacy_stockage_id', 'pharmacy_product_id');
    }

    /**
     * Get the pharmacy stockage tools for this stockage
     */
    public function pharmacyStockageTools()
    {
    return $this->hasMany(PharmacyStorageTool::class, 'pharmacy_storage_id');
    }

    /**
     * Scope to get active stockages
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get stockages by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get stockages by warehouse type
     */
    public function scopeByWarehouseType($query, $warehouseType)
    {
        return $query->where('warehouse_type', $warehouseType);
    }

    /**
     * Scope to get temperature controlled stockages
     */
    public function scopeTemperatureControlled($query)
    {
        return $query->where('temperature_controlled', true);
    }

    /**
     * Get the total quantity of products in this stockage
     */
    public function getTotalQuantityAttribute()
    {
        return $this->pharmacyInventories()->sum('quantity');
    }

    /**
     * Get the available capacity
     */
    public function getAvailableCapacityAttribute()
    {
        if (! $this->capacity) {
            return null;
        }

        return $this->capacity - $this->total_quantity;
    }

    /**
     * Check if stockage is at capacity
     */
    public function getIsAtCapacityAttribute()
    {
        if (! $this->capacity) {
            return false;
        }

        return $this->total_quantity >= $this->capacity;
    }

    /**
     * Get capacity utilization percentage
     */
    public function getCapacityUtilizationAttribute()
    {
        if (! $this->capacity) {
            return 0;
        }

        return round(($this->total_quantity / $this->capacity) * 100, 2);
    }

    /**
     * Get the warehouse type label
     */
    public function getWarehouseTypeLabelAttribute()
    {
        return match ($this->warehouse_type) {
            'Central Pharmacy (PC)' => 'Central Pharmacy',
            'Service Pharmacy (PS)' => 'Service Pharmacy',
            default => $this->warehouse_type
        };
    }
}
