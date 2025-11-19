<?php

namespace App\Models\Inventory;

use App\Models\PharmacyProduct;
use App\Models\Product;
use App\Models\ServiceGroup;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceGroupProductPricing extends Model
{
    use HasFactory;

    protected $table = 'service_group_product_pricing';

    protected $fillable = [
        'service_group_id',
        'product_id',
        'pharmacy_product_id',
        'is_pharmacy',
        'selling_price',
        'purchase_price',
        'vat_rate',
        'effective_from',
        'effective_to',
        'is_active',
        'notes',
        'updated_by',
    ];

    protected $casts = [
        'is_pharmacy' => 'boolean',
        'is_active' => 'boolean',
        'selling_price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'effective_from' => 'datetime',
        'effective_to' => 'datetime',
    ];

    /**
     * Get the service group that owns this pricing.
     */
    public function serviceGroup(): BelongsTo
    {
        return $this->belongsTo(ServiceGroup::class);
    }

    /**
     * Get the product that this pricing applies to (for stock products).
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the pharmacy product that this pricing applies to.
     */
    public function pharmacyProduct(): BelongsTo
    {
        return $this->belongsTo(PharmacyProduct::class);
    }

    /**
     * Get the user who last updated this pricing.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope: Get only active pricing records.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Get only pharmacy product pricing.
     */
    public function scopePharmacy(Builder $query): Builder
    {
        return $query->where('is_pharmacy', true);
    }

    /**
     * Scope: Get only stock product pricing.
     */
    public function scopeStock(Builder $query): Builder
    {
        return $query->where('is_pharmacy', false);
    }

    /**
     * Scope: Get current pricing (effective_to is NULL).
     */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->whereNull('effective_to');
    }

    /**
     * Scope: Get pricing for a specific service group.
     */
    public function scopeForServiceGroup(Builder $query, int $serviceGroupId): Builder
    {
        return $query->where('service_group_id', $serviceGroupId);
    }

    /**
     * Scope: Get pricing for a specific product.
     */
    public function scopeForProduct(Builder $query, int $productId, bool $isPharmacy = false): Builder
    {
        if ($isPharmacy) {
            return $query->where('pharmacy_product_id', $productId)->where('is_pharmacy', true);
        }

        return $query->where('product_id', $productId)->where('is_pharmacy', false);
    }

    /**
     * Scope: Get pricing effective at a specific date.
     */
    public function scopeEffectiveAt(Builder $query, Carbon $date): Builder
    {
        return $query->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>', $date);
            });
    }

    /**
     * Get the current pricing for a service group and product.
     */
    public static function getPricing(
        int $serviceGroupId,
        int $productId,
        bool $isPharmacy = false
    ): ?self {
        return self::active()
            ->current()
            ->forServiceGroup($serviceGroupId)
            ->forProduct($productId, $isPharmacy)
            ->first();
    }

    /**
     * Archive current pricing and create a new version.
     */
    public function archiveAndCreateNew(array $newPricingData): self
    {
        // Archive current pricing by setting effective_to
        $this->update([
            'effective_to' => now(),
            'is_active' => false,
        ]);

        // Create new pricing version
        return self::create(array_merge($newPricingData, [
            'service_group_id' => $this->service_group_id,
            'product_id' => $this->product_id,
            'pharmacy_product_id' => $this->pharmacy_product_id,
            'is_pharmacy' => $this->is_pharmacy,
            'effective_from' => now(),
            'effective_to' => null,
            'is_active' => true,
        ]));
    }

    /**
     * Get price history for a product in a service group.
     */
    public static function getPriceHistory(
        int $serviceGroupId,
        int $productId,
        bool $isPharmacy = false,
        int $limit = 10
    ): \Illuminate\Database\Eloquent\Collection {
        return self::forServiceGroup($serviceGroupId)
            ->forProduct($productId, $isPharmacy)
            ->orderBy('effective_from', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Calculate selling price with VAT.
     */
    public function getSellingPriceWithVatAttribute(): float
    {
        return round($this->selling_price * (1 + ($this->vat_rate / 100)), 2);
    }

    /**
     * Calculate profit margin.
     */
    public function getProfitMarginAttribute(): ?float
    {
        if (! $this->purchase_price || $this->purchase_price == 0) {
            return null;
        }

        return round((($this->selling_price - $this->purchase_price) / $this->purchase_price) * 100, 2);
    }

    /**
     * Check if this pricing is currently effective.
     */
    public function isCurrentlyEffective(): bool
    {
        $now = now();

        return $this->is_active
            && $this->effective_from <= $now
            && ($this->effective_to === null || $this->effective_to > $now);
    }
}
