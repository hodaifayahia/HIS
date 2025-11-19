<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SellingSettings extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'service_id',
        'service_group_id',
        'percentage',
        'type',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'percentage' => 0,
        'is_active' => false,
        'type' => 'pharmacy',
    ];

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        // Before saving, ensure only one active setting per service/type
        static::saving(function ($setting) {
            if ($setting->is_active) {
                // Deactivate other settings for the same service and type
                static::where('service_id', $setting->service_id)
                    ->where('type', $setting->type)
                    ->where('id', '!=', $setting->id)
                    ->update(['is_active' => false]);
            }
        });
    }

    /**
     * Relationships
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function serviceGroup(): BelongsTo
    {
        return $this->belongsTo(ServiceGroup::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePharmacy($query)
    {
        return $query->where('type', 'pharmacy');
    }

    public function scopeStock($query)
    {
        return $query->where('type', 'stock');
    }

    public function scopeForService($query, $serviceId)
    {
        return $query->where('service_id', $serviceId);
    }

    /**
     * Get active setting for a service and type
     */
    public static function getActivePercentage($serviceId, $type = 'pharmacy')
    {
        $setting = static::where('service_id', $serviceId)
            ->where('type', $type)
            ->where('is_active', true)
            ->first();

        return $setting ? $setting->percentage : 0;
    }

    /**
     * Get active percentage for a service group
     */
    public static function getActivePercentageForGroup($serviceGroupId, $type = 'pharmacy')
    {
        $setting = static::where('service_group_id', $serviceGroupId)
            ->where('type', $type)
            ->where('is_active', true)
            ->first();

        return $setting ? $setting->percentage : 0;
    }

    /**
     * Determine the target type (service or group)
     */
    public function getTargetTypeAttribute()
    {
        if ($this->service_group_id) {
            return 'group';
        }

        return 'service';
    }

    /**
     * Get the target name (service name or group name)
     */
    public function getTargetNameAttribute()
    {
        if ($this->service_group_id && $this->serviceGroup) {
            return $this->serviceGroup->name;
        }
        if ($this->service_id && $this->service) {
            return $this->service->name;
        }

        return 'N/A';
    }
}
