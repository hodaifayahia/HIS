<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovementItem extends Model
{
    protected $fillable = [
        'stock_movement_id',
        'product_id',
        'requested_quantity',
        'approved_quantity',
        'executed_quantity',
        'provided_quantity',
        'notes',
        'quantity_by_box',
    ];

    protected $casts = [
        'requested_quantity' => 'decimal:2',
        'approved_quantity' => 'decimal:2',
        'executed_quantity' => 'decimal:2',
        'provided_quantity' => 'decimal:2',
    ];

    // Relationships
    public function stockMovement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventorySelections()
    {
        return $this->hasMany(StockMovementInventorySelection::class);
    }

    public function selectedInventory()
    {
        return $this->hasMany(StockMovementInventorySelection::class);
    }

    // Helper methods
    public function getAvailableStock()
    {
        // Get available stock for this product in the providing service
        $providingServiceId = $this->stockMovement->providing_service_id;
        
        return \DB::table('inventories')
            ->join('stockages', 'inventories.stockage_id', '=', 'stockages.id')
            ->where('inventories.product_id', $this->product_id)
            ->where('stockages.service_id', $providingServiceId)
            ->sum('inventories.quantity');
    }

    public function getActualRequestedQuantity()
    {
        if ($this->quantity_by_box && $this->product && $this->product->boite_de) {
            return $this->requested_quantity * $this->product->boite_de;
        }
        return $this->requested_quantity;
    }

    public function getActualApprovedQuantity()
    {
        if ($this->quantity_by_box && $this->product && $this->product->boite_de) {
            return $this->approved_quantity * $this->product->boite_de;
        }
        return $this->approved_quantity;
    }

    public function getActualExecutedQuantity()
    {
        if ($this->quantity_by_box && $this->product && $this->product->boite_de) {
            return $this->executed_quantity * $this->product->boite_de;
        }
        return $this->executed_quantity;
    }

    public function getDisplayUnit()
    {
        if ($this->quantity_by_box) {
            return 'boxes';
        }
        return $this->product ? ($this->product->forme ?? 'units') : 'units';
    }

    public function getSuggestedQuantity()
    {
        // Get suggested quantity based on low stock threshold
        $lowStockThreshold = $this->product->low_stock_threshold ?? 10;
        $availableStock = $this->getAvailableStock();
        
        // Suggest quantity to reach the max stock level
        $maxStockLevel = $this->product->max_stock_level ?? ($lowStockThreshold * 2);
        
        return max(0, $maxStockLevel - $availableStock);
    }

    // Status checking methods
    public function isApproved(): bool
    {
        return $this->approved_quantity !== null && $this->approved_quantity > 0;
    }

    public function isRejected(): bool
    {
        return $this->approved_quantity !== null && $this->approved_quantity == 0;
    }

    public function isPending(): bool
    {
        return $this->approved_quantity === null;
    }

    public function isEditable(): bool
    {
        return $this->isPending();
    }

    public function getStatus(): string
    {
        if ($this->isApproved()) {
            return 'approved';
        } elseif ($this->isRejected()) {
            return 'rejected';
        }
        return 'pending';
    }
}
