<?php

namespace App\Models;

use App\Models\Inventory\ServiceGroupProductPricing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'code',
        'color',
        'is_active',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = ['services_count'];

    /**
     * Get the services that belong to this group
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_group_members', 'service_group_id', 'service_id')
            ->withPivot('sort_order', 'created_by', 'created_at')
            ->orderByPivot('sort_order', 'asc')
            ->withTimestamps();
    }

    /**
     * Get the service group members
     */
    public function members(): HasMany
    {
        return $this->hasMany(ServiceGroupMember::class);
    }

    /**
     * Get the selling settings for this group
     */
    public function sellingSettings(): HasMany
    {
        return $this->hasMany(SellingSettings::class);
    }

    /**
     * Get active selling settings
     */
    public function activeSellingSettings(): HasMany
    {
        return $this->hasMany(SellingSettings::class)->where('is_active', true);
    }

    /**
     * Get product pricing for this service group
     */
    public function productPricing(): HasMany
    {
        return $this->hasMany(ServiceGroupProductPricing::class);
    }

    /**
     * Get active product pricing
     */
    public function activeProductPricing(): HasMany
    {
        return $this->hasMany(ServiceGroupProductPricing::class)
            ->where('is_active', true)
            ->whereNull('effective_to');
    }

    /**
     * Get the user who created this group
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this group
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope for active groups only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordered groups
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc');
    }

    /**
     * Get services count attribute
     */
    public function getServicesCountAttribute(): int
    {
        return $this->services()->count();
    }

    /**
     * Check if a service is in this group
     */
    public function hasService(int $serviceId): bool
    {
        return $this->services()->where('services.id', $serviceId)->exists();
    }

    /**
     * Add a service to this group
     */
    public function addService(int $serviceId, ?int $sortOrder = null): void
    {
        if (! $this->hasService($serviceId)) {
            $this->services()->attach($serviceId, [
                'sort_order' => $sortOrder ?? ($this->services()->max('sort_order') + 1),
                'created_by' => auth()->id(),
                'created_at' => now(),
            ]);
        }
    }

    /**
     * Remove a service from this group
     */
    public function removeService(int $serviceId): void
    {
        $this->services()->detach($serviceId);
    }

    /**
     * Sync services with this group
     */
    public function syncServices(array $serviceIds): void
    {
        $syncData = [];
        foreach ($serviceIds as $index => $serviceId) {
            $syncData[$serviceId] = [
                'sort_order' => $index,
                'created_by' => auth()->id(),
                'created_at' => now(),
            ];
        }
        $this->services()->sync($syncData);
    }
}
