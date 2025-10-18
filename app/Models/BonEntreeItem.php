<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BonEntreeItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'bon_entree_id',
        'product_id',
        'in_stock_id',
        'storage_name',
        'batch_number',
        'serial_number',
        'expiry_date',
        'boite_de',
        'quantity',
        'purchase_price',
        'sell_price',
        'tva',
        'by_box',
        'qte_by_box',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'purchase_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
        'tva' => 'decimal:2',
        'by_box' => 'boolean',
        'boite_de' => 'integer',
        'quantity' => 'integer',
        'qte_by_box' => 'integer',
    ];

    // Relationships
    public function bonEntree(): BelongsTo
    {
        return $this->belongsTo(BonEntree::class, 'bon_entree_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function inStock(): BelongsTo
    {
        return $this->belongsTo(Stock::class, 'in_stock_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Accessors
    public function getTotalAmountAttribute()
    {
        $priceWithTVA = $this->purchase_price * (1 + ($this->tva / 100));

        return $this->quantity * $priceWithTVA;
    }

    public function getUnitPriceWithTvaAttribute()
    {
        return $this->purchase_price * (1 + ($this->tva / 100));
    }

    public function getFormattedExpiryDateAttribute()
    {
        return $this->expiry_date ? $this->expiry_date->format('Y-m-d') : null;
    }

    // Methods
    public function isExpired()
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function isExpiringSoon($days = 30)
    {
        return $this->expiry_date &&
               $this->expiry_date->isAfter(now()) &&
               $this->expiry_date->isBefore(now()->addDays($days));
    }

    public function updateStock()
    {
        // Get the bon entree and resiliently resolve the service
        $bonEntree = $this->bonEntree;
        if (! $bonEntree) {
            throw new \Exception('BonEntree not found for this item');
        }

        $service = null;

        // Prefer direct service_id lookup (canonical)
        $svcId = $bonEntree->getAttribute('service_id') ?? null;
        if ($svcId) {
            $service = \App\Models\CONFIGURATION\Service::find($svcId);
        }

        // Fallback: if service_id not set, try service_abv/name
        if (! $service && ! empty($bonEntree->service_abv)) {
            $service = \App\Models\CONFIGURATION\Service::where('service_abv', $bonEntree->service_abv)
                ->orWhere('name', $bonEntree->service_abv)
                ->first();
        }

        if (! $service) {
            $identifier = $bonEntree->getAttribute('service_id') ?? ($bonEntree->service_abv ?: 'unknown');
            throw new \Exception("Service not found for stock update (tried service_id/service_abv/name: {$identifier})");
        }

        // Get or create the service's default stockage
        $stockage = $service->stockages()->where('type', 'warehouse')->first();
        if (! $stockage) {
            // Create a default stockage for this service
            $stockage = \App\Models\Stockage::create([
                'name' => $service->name.' - Main Stock',
                'description' => 'Default stock location for '.$service->name,
                'type' => 'warehouse',
                'location' => 'Main Warehouse',
                'service_id' => $service->id,
            ]);
        }

        // Create or update inventory record
        $inventory = \App\Models\Inventory::where([
            'product_id' => $this->product_id,
            'stockage_id' => $stockage->id,
            'batch_number' => $this->batch_number,
        ])->first();

        if ($inventory) {
            // Update existing inventory
            $inventory->increment('quantity', $this->quantity);
            $inventory->increment('total_units', $this->quantity);
            $inventory->update([
                'purchase_price' => $this->purchase_price,
                'expiry_date' => $this->expiry_date,
                'serial_number' => $this->serial_number,
                'location' => $this->storage_name ?: $stockage->location,
            ]);
        } else {
            // Create new inventory record
            $inventory = \App\Models\Inventory::create([
                'product_id' => $this->product_id,
                'stockage_id' => $stockage->id,
                'quantity' => $this->quantity,
                'total_units' => $this->quantity,
                'unit' => $this->product->unit ?? 'units',
                'batch_number' => $this->batch_number,
                'serial_number' => $this->serial_number,
                'purchase_price' => $this->purchase_price,
                'expiry_date' => $this->expiry_date,
                'location' => $this->storage_name ?: $stockage->location,
            ]);
        }

        // Update the bon entree item with the inventory reference
        $this->update(['in_stock_id' => $inventory->id]);

        return $inventory;
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::created(function ($item) {
            // Update bon entree total when item is created
            $item->bonEntree->calculateTotal();
        });

        static::updated(function ($item) {
            // Update bon entree total when item is updated
            $item->bonEntree->calculateTotal();
        });

        static::deleted(function ($item) {
            // Update bon entree total when item is deleted
            $item->bonEntree->calculateTotal();
        });
    }
}
