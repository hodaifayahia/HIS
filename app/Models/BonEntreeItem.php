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
        'pharmacy_product_id', // For pharmacy products
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
        'sub_items', // JSON array of batch-level details
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
        'sub_items' => 'array', // Cast JSON to array
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

    public function pharmacyProduct(): BelongsTo
    {
        return $this->belongsTo(PharmacyProduct::class, 'pharmacy_product_id');
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

        // Check if this is a pharmacy product or regular product
        if ($this->pharmacy_product_id) {
            // Handle pharmacy products - transfer to pharmacy inventory
            $this->transferPharmacyProductToStock($stockage);
        } else if ($this->product_id) {
            // Handle regular products - transfer to regular inventory
            // CRITICAL: Process sub_items if they exist
            // Each sub_item represents a different batch with unique batch_number, expiry_date, serial_number
            if (!empty($this->sub_items) && is_array($this->sub_items)) {
                foreach ($this->sub_items as $subItem) {
                    $this->createOrUpdateInventoryRecord(
                        $stockage->id,
                        $subItem['quantity'] ?? 0,
                        $subItem['purchase_price'] ?? $this->purchase_price,
                        $subItem['batch_number'] ?? null,
                        $subItem['expiry_date'] ?? null,
                        $subItem['serial_number'] ?? null,
                        $subItem['unit'] ?? $this->product->unit ?? 'units'
                    );
                }
            } else {
                // No sub_items: Process the main item as a single inventory record
                $this->createOrUpdateInventoryRecord(
                    $stockage->id,
                    $this->quantity,
                    $this->purchase_price,
                    $this->batch_number,
                    $this->expiry_date,
                    $this->serial_number,
                    $this->product->unit ?? 'units'
                );
            }
        } else {
            throw new \Exception('BonEntreeItem must have either product_id or pharmacy_product_id');
        }

        return true;
    }

    /**
     * Transfer pharmacy product to pharmacy stock
     */
    private function transferPharmacyProductToStock($stockage)
    {
        // Get or create pharmacy stockage for this service
        $pharmacyStockage = \App\Models\PharmacyStockage::where('service_id', $stockage->service_id)
            ->where('type', 'warehouse')
            ->first();

        if (!$pharmacyStockage) {
            // Create a default pharmacy stockage for this service
            $pharmacyStockage = \App\Models\PharmacyStockage::create([
                'name' => $stockage->service->name . ' - Pharmacy Stock',
                'description' => 'Default pharmacy stock location for ' . $stockage->service->name,
                'type' => 'warehouse',
                'location' => 'Main Warehouse',
                'service_id' => $stockage->service_id,
            ]);
        }

        // Process pharmacy inventory similar to regular inventory
        $pharmacyInventory = \App\Models\PharmacyInventory::where('pharmacy_product_id', $this->pharmacy_product_id)
            ->where('pharmacy_stockage_id', $pharmacyStockage->id)
            ->where('batch_number', $this->batch_number ?? '')
            ->whereRaw('DATE(expiry_date) <=> DATE(?) IS TRUE', [$this->expiry_date])
            ->first();

        if ($pharmacyInventory) {
            // Merge quantities
            $oldQuantity = $pharmacyInventory->quantity;
            $newTotalQuantity = $oldQuantity + $this->quantity;

            $pharmacyInventory->update([
                'quantity' => $newTotalQuantity,
                'total_units' => ($pharmacyInventory->total_units ?? 0) + $this->quantity,
                'expiry_date' => $this->expiry_date ?? $pharmacyInventory->expiry_date,
                'location' => $this->storage_name ?: $pharmacyInventory->location,
            ]);

            \Log::info('Pharmacy inventory merged', [
                'pharmacy_product_id' => $this->pharmacy_product_id,
                'batch_number' => $this->batch_number,
                'old_quantity' => $oldQuantity,
                'added_quantity' => $this->quantity,
                'new_total_quantity' => $newTotalQuantity,
            ]);
        } else {
            // Create new pharmacy inventory record
            $pharmacyInventory = \App\Models\PharmacyInventory::create([
                'pharmacy_product_id' => $this->pharmacy_product_id,
                'pharmacy_stockage_id' => $pharmacyStockage->id,
                'quantity' => $this->quantity,
                'total_units' => $this->quantity,
                'unit' => 'box',
                'batch_number' => $this->batch_number,
                'serial_number' => $this->serial_number,
                'purchase_price' => $this->purchase_price,
                'expiry_date' => $this->expiry_date,
                'location' => $this->storage_name,
            ]);

            \Log::info('New pharmacy inventory record created', [
                'pharmacy_product_id' => $this->pharmacy_product_id,
                'batch_number' => $this->batch_number,
                'quantity' => $this->quantity,
            ]);
        }

        return $pharmacyInventory;
    }

    /**
     * Create or update inventory record with batch-level details
     * This ensures each unique batch (batch_number + expiry_date) gets its own inventory record
     */
    private function createOrUpdateInventoryRecord(
        $stockageId,
        $quantity,
        $purchasePrice,
        $batchNumber = null,
        $expiryDate = null,
        $serialNumber = null,
        $unit = 'units'
    ) {
        // Find matching inventory based on CRITICAL criteria:
        // 1. Same product
        // 2. Same stockage
        // 3. Same batch number (if provided)
        // 4. Same expiry date (if provided)
        // 5. Same purchase price (for consistency)
        // This ensures we only merge quantities for identical stock items
        $inventory = \App\Models\Inventory::where('product_id', $this->product_id)
            ->where('stockage_id', $stockageId)
            ->where('purchase_price', $purchasePrice)
            ->where('batch_number', $batchNumber ?? '')
            ->whereRaw('DATE(expiry_date) <=> DATE(?) IS TRUE', [$expiryDate])
            ->first();

        if ($inventory) {
            // ✅ FOUND MATCHING INVENTORY: Merge quantities instead of creating duplicate
            $oldQuantity = $inventory->quantity;
            $newTotalQuantity = $oldQuantity + $quantity;

            $inventory->update([
                'quantity' => $newTotalQuantity,
                'total_units' => ($inventory->total_units ?? 0) + $quantity,
                'expiry_date' => $expiryDate ?? $inventory->expiry_date,
                'location' => $this->storage_name ?: $inventory->location,
            ]);

            \Log::info('Inventory merged for batch', [
                'product_id' => $this->product_id,
                'batch_number' => $batchNumber,
                'expiry_date' => $expiryDate,
                'old_quantity' => $oldQuantity,
                'added_quantity' => $quantity,
                'new_total_quantity' => $newTotalQuantity,
                'inventory_id' => $inventory->id,
            ]);
        } else {
            // ❌ NO MATCHING INVENTORY: Create new inventory record
            $inventory = \App\Models\Inventory::create([
                'product_id' => $this->product_id,
                'stockage_id' => $stockageId,
                'quantity' => $quantity,
                'total_units' => $quantity,
                'unit' => $unit,
                'batch_number' => $batchNumber,
                'serial_number' => $serialNumber,
                'purchase_price' => $purchasePrice,
                'expiry_date' => $expiryDate,
                'location' => $this->storage_name,
            ]);

            \Log::info('New inventory record created for batch', [
                'product_id' => $this->product_id,
                'batch_number' => $batchNumber,
                'expiry_date' => $expiryDate,
                'quantity' => $quantity,
                'inventory_id' => $inventory->id,
            ]);
        }

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
