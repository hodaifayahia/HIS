<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceProductSetting extends Model
{
    protected $fillable = [
        'service_id',
        'product_id',
        'product_name',
        'product_forme',
        
        // Alert Settings
        'low_stock_threshold',
        'critical_stock_threshold',
        'max_stock_level',
        'reorder_point',
        'expiry_alert_days',
        'min_shelf_life_days',
        
        // Notification Settings
        'email_alerts',
        'sms_alerts',
        'alert_frequency',
        'preferred_supplier',
        
        // Inventory Settings
        'batch_tracking',
        'location_tracking',
        'auto_reorder',
        
        // Display Settings
        'custom_name',
        'color_code',
        'priority'
    ];

    protected $casts = [
        'low_stock_threshold' => 'decimal:2',
        'critical_stock_threshold' => 'decimal:2',
        'max_stock_level' => 'decimal:2',
        'reorder_point' => 'decimal:2',
        'expiry_alert_days' => 'integer',
        'min_shelf_life_days' => 'integer',
        'email_alerts' => 'boolean',
        'sms_alerts' => 'boolean',
        'batch_tracking' => 'boolean',
        'location_tracking' => 'boolean',
        'auto_reorder' => 'boolean'
    ];

    // Relationships
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Helper methods
    public function getDisplayName()
    {
        return $this->custom_name ?: $this->product_name;
    }

    public function getEffectiveLowStockThreshold()
    {
        return $this->low_stock_threshold ?? 10;
    }

    public function getEffectiveCriticalStockThreshold()
    {
        return $this->critical_stock_threshold ?? ($this->getEffectiveLowStockThreshold() * 0.5);
    }

    public function getEffectiveMaxStockLevel()
    {
        return $this->max_stock_level ?? ($this->getEffectiveLowStockThreshold() * 3);
    }

    public function getEffectiveReorderPoint()
    {
        return $this->reorder_point ?? $this->getEffectiveLowStockThreshold();
    }

    public function getEffectiveExpiryAlertDays()
    {
        return $this->expiry_alert_days ?? 30;
    }

    public function getEffectiveMinShelfLifeDays()
    {
        return $this->min_shelf_life_days ?? 90;
    }
}
