<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BonRetourItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'bon_retour_id',
        'product_id',
        'bon_entree_item_id',
        'batch_number',
        'serial_number',
        'bon_reception_item_id',
        'expiry_date',
        'quantity_returned',
        'unit_price',
        'tva',
        'total_amount',
        'return_reason',
        'remarks',
        'storage_location',
        'stock_updated',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'quantity_returned' => 'integer',
        'unit_price' => 'decimal:2',
        'tva' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'stock_updated' => 'boolean',
    ];

    // Return reasons
    const RETURN_REASONS = [
        'defective' => 'Defective',
        'expired' => 'Expired',
        'damaged' => 'Damaged',
        'wrong_item' => 'Wrong Item',
        'quality_issue' => 'Quality Issue',
        'other' => 'Other',
        'overstock' => 'Overstock',
    ];

    // Relationships
    public function bonRetour(): BelongsTo
    {
        return $this->belongsTo(BonRetour::class, 'bon_retour_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function originalItem(): BelongsTo
    {
        return $this->belongsTo(BonEntreeItem::class, 'bon_entree_item_id');
    }

    // Accessors
    public function getReturnReasonLabelAttribute()
    {
        return self::RETURN_REASONS[$this->return_reason] ?? ucfirst($this->return_reason);
    }

    public function getUnitPriceWithTvaAttribute()
    {
        return $this->unit_price * (1 + ($this->tva / 100));
    }

    public function getFormattedExpiryDateAttribute()
    {
        return $this->expiry_date ? $this->expiry_date->format('Y-m-d') : null;
    }

    // Methods
    public function calculateTotal()
    {
        $priceWithTva = $this->unit_price * (1 + ($this->tva / 100));
        $this->total_amount = $this->quantity_returned * $priceWithTva;
        $this->save();
        return $this->total_amount;
    }

    public function updateStockForReturn()
    {
        if ($this->stock_updated) {
            return; // Already updated
        }

        // Get the service from the bon retour
        $bonRetour = $this->bonRetour;
        $service = $bonRetour->service;

        if (!$service) {
            throw new \Exception('Service not found for stock update');
        }

        // Find the stockage for this service
        $stockage = $service->stockages()->where('type', 'warehouse')->first();
        
        if (!$stockage) {
            throw new \Exception('Stockage not found for service');
        }

        // Find inventory record
        $inventory = \App\Models\Inventory::where([
            'product_id' => $this->product_id,
            'stockage_id' => $stockage->id,
            'batch_number' => $this->batch_number,
        ])->first();

        if ($inventory) {
            // Decrease inventory for returned items
            $inventory->decrement('quantity', $this->quantity_returned);
            $inventory->decrement('total_units', $this->quantity_returned);
            
            // Log the stock movement
            \App\Models\StockMovement::create([
                'product_id' => $this->product_id,
                'stockage_id' => $stockage->id,
                'type' => 'return',
                'quantity' => -$this->quantity_returned,
                'reference_type' => 'BonRetour',
                'reference_id' => $this->bon_retour_id,
                'batch_number' => $this->batch_number,
                'reason' => 'Return to supplier - ' . $this->return_reason_label,
                'created_by' => auth()->id(),
            ]);
        }

        // Mark as updated
        $this->update(['stock_updated' => true]);

        return true;
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            // Calculate total before creating
            if ($item->unit_price && $item->quantity_returned) {
                $priceWithTva = $item->unit_price * (1 + ($item->tva / 100));
                $item->total_amount = $item->quantity_returned * $priceWithTva;
            }
        });

        static::created(function ($item) {
            // Update bon retour total when item is created
            $item->bonRetour->calculateTotal();
        });

        static::updated(function ($item) {
            // Recalculate total
            $item->calculateTotal();
            // Update bon retour total when item is updated
            $item->bonRetour->calculateTotal();
        });

        static::deleted(function ($item) {
            // Update bon retour total when item is deleted
            $item->bonRetour->calculateTotal();
        });
    }
}
