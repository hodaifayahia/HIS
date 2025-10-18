<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PharmacyProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pharmacy_products';

    protected $fillable = [
        'name',
        'generic_name',
        'brand_name',
        'barcode',
        'sku',
        'category',
        'description',
        'manufacturer',
        'supplier',
        'unit_of_measure',
        'strength',
        'strength_unit',
        'dosage_form',
        'route_of_administration',
        'active_ingredients',
        'inactive_ingredients',
        'requires_prescription',
        'is_controlled_substance',
        'controlled_substance_schedule',
        'storage_temperature_min',
        'storage_temperature_max',
        'storage_humidity_min',
        'storage_humidity_max',
        'storage_conditions',
        'requires_cold_chain',
        'light_sensitive',
        'shelf_life_days',
        'contraindications',
        'side_effects',
        'drug_interactions',
        'warnings',
        'precautions',
        'unit_cost',
        'selling_price',
        'markup_percentage',
        'therapeutic_class',
        'pharmacological_class',
        'atc_code',
        'ndc_number',
        'lot_number',
        'expiry_date',
        'is_active',
        'is_discontinued',
        'discontinued_date',
        'discontinuation_reason',
        'regulatory_info',
        'quality_control_info',
        'packaging_info',
        'labeling_info',
        'notes',
    ];

    protected $casts = [
        'strength' => 'decimal:3',
        'requires_prescription' => 'boolean',
        'is_controlled_substance' => 'boolean',
        'storage_temperature_min' => 'decimal:2',
        'storage_temperature_max' => 'decimal:2',
        'storage_humidity_min' => 'decimal:2',
        'storage_humidity_max' => 'decimal:2',
        'requires_cold_chain' => 'boolean',
        'light_sensitive' => 'boolean',
        'shelf_life_days' => 'integer',
        'unit_cost' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'markup_percentage' => 'decimal:2',
        'expiry_date' => 'date',
        'is_active' => 'boolean',
        'is_discontinued' => 'boolean',
        'discontinued_date' => 'date',
        'regulatory_info' => 'json',
        'quality_control_info' => 'json',
        'packaging_info' => 'json',
        'labeling_info' => 'json',
    ];

    /**
     * Get the pharmacy inventories for this product
     */
    public function pharmacyInventories()
    {
        return $this->hasMany(PharmacyInventory::class, 'pharmacy_product_id');
    }

    /**
     * Alias for pharmacyInventories() for backward compatibility
     */
    public function inventories()
    {
        return $this->pharmacyInventories();
    }

    /**
     * Get the pharmacy stock movement items for this product
     */
    public function pharmacyStockMovementItems()
    {
        return $this->hasMany(PharmacyMovementItem::class, 'pharmacy_product_id');
    }

    /**
     * Get the pharmacy stock movements for this product
     */
    public function pharmacyStockMovements()
    {
        return $this->hasMany(PharmacyMovement::class, 'pharmacy_product_id');
    }

    /**
     * Get the pharmacy stockages that have this product
     */
    public function pharmacyStockages()
    {
        return $this->belongsToMany(PharmacyStockage::class, 'pharmacy_inventories', 'pharmacy_product_id', 'pharmacy_stockage_id');
    }

    /**
     * Scope to get active products
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get discontinued products
     */
    public function scopeDiscontinued($query)
    {
        return $query->where('is_discontinued', true);
    }

    /**
     * Scope to get products by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to get controlled substances
     */
    public function scopeControlledSubstances($query)
    {
        return $query->where('is_controlled_substance', true);
    }

    /**
     * Scope to get prescription required products
     */
    public function scopeRequiresPrescription($query)
    {
        return $query->where('requires_prescription', true);
    }

    /**
     * Get the full product name with strength
     */
    public function getFullNameAttribute()
    {
        $name = $this->name;
        if ($this->strength && $this->strength_unit) {
            $name .= ' '.$this->strength.$this->strength_unit;
        }

        return $name;
    }

    /**
     * Check if product is expired
     */
    public function getIsExpiredAttribute()
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Check if product is near expiry (within 30 days)
     */
    public function getIsNearExpiryAttribute()
    {
        return $this->expiry_date && $this->expiry_date->diffInDays(now()) <= 30;
    }

    /**
     * Generate barcode if not exists
     */
    public function generateBarcode()
    {
        if (! $this->barcode) {
            $this->barcode = 'PH'.str_pad($this->id, 8, '0', STR_PAD_LEFT);
            $this->save();
        }

        return $this->barcode;
    }

    /**
     * Generate SKU if not exists
     */
    public function generateSku()
    {
        if (! $this->sku) {
            $this->sku = 'SKU-PH-'.str_pad($this->id, 6, '0', STR_PAD_LEFT);
            $this->save();
        }

        return $this->sku;
    }
}
