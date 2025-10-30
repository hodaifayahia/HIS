<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyProductGlobalSetting extends Model
{
    use HasFactory;

    protected $fillable = ['pharmacy_product_id', 'setting_key', 'setting_value', 'description'];

    protected $casts = ['setting_value' => 'array']; // Auto-decode JSON

    /**
     * Relationship with PharmacyProduct
     */
    public function pharmacyProduct(): BelongsTo
    {
        return $this->belongsTo(PharmacyProduct::class);
    }

    // Scopes for easy querying
    public function scopeByKey($query, $key)
    {
        return $query->where('setting_key', $key);
    }

    public function scopeForProduct($query, $productId)
    {
        return $query->where('pharmacy_product_id', $productId);
    }

    public function scopeByProductAndKey($query, $productId, $key)
    {
        return $query->where('pharmacy_product_id', $productId)->where('setting_key', $key);
    }

    // Helper methods
    public static function getSetting($productId, $key, $default = null)
    {
        $setting = self::byProductAndKey($productId, $key)->first();

        return $setting ? $setting->setting_value : $default;
    }

    public static function setSetting($productId, $key, $value, $description = null)
    {
        return self::updateOrCreate(
            ['pharmacy_product_id' => $productId, 'setting_key' => $key],
            [
                'setting_value' => $value,
                'description' => $description,
            ]
        );
    }

    public static function getAllSettingsForProduct($productId)
    {
        return self::forProduct($productId)->get()->pluck('setting_value', 'setting_key')->toArray();
    }
}
