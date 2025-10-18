<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyMovementItem extends Model
{
    protected $table = 'pharmacy_stock_movement_items';
    
    protected $fillable = [
        'pharmacy_stock_movement_id',
        'pharmacy_product_id',
        'requested_quantity',
        'approved_quantity',
        'executed_quantity',
        'provided_quantity',
        'notes',
        'quantity_by_box',
        'dosage_instructions',
        'administration_route',
        'frequency',
        'duration_days',
        'contraindications',
        'expiry_date_required',
        'batch_number',
        'pharmacist_notes',
    ];

    protected $casts = [
        'requested_quantity' => 'decimal:2',
        'approved_quantity' => 'decimal:2',
        'executed_quantity' => 'decimal:2',
        'provided_quantity' => 'decimal:2',
        'duration_days' => 'integer',
        'expiry_date_required' => 'boolean',
    ];

    // Relationships
    public function pharmacyMovement(): BelongsTo
    {
        return $this->belongsTo(PharmacyMovement::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventorySelections()
    {
        return $this->hasMany(PharmacyMovementInventorySelection::class);
    }

    public function selectedInventory()
    {
        return $this->hasMany(PharmacyMovementInventorySelection::class);
    }

    // Helper methods
    public function getAvailableStock()
    {
        // Get available stock for this product in the providing service
        $providingServiceId = $this->pharmacyMovement->providing_service_id;
        
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

    // Pharmacy-specific methods
    public function getDosageInstructionsAttribute(): ?string
    {
        return $this->attributes['dosage_instructions'];
    }

    public function getAdministrationRouteLabel(): string
    {
        return match($this->administration_route) {
            'oral' => 'Oral',
            'iv' => 'Intravenous',
            'im' => 'Intramuscular',
            'sc' => 'Subcutaneous',
            'topical' => 'Topical',
            'inhalation' => 'Inhalation',
            'rectal' => 'Rectal',
            'sublingual' => 'Sublingual',
            default => $this->administration_route ?? 'Not specified'
        };
    }

    public function getFrequencyLabel(): string
    {
        return match($this->frequency) {
            'once_daily' => 'Once daily',
            'twice_daily' => 'Twice daily',
            'three_times_daily' => 'Three times daily',
            'four_times_daily' => 'Four times daily',
            'every_6_hours' => 'Every 6 hours',
            'every_8_hours' => 'Every 8 hours',
            'every_12_hours' => 'Every 12 hours',
            'as_needed' => 'As needed',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            default => $this->frequency ?? 'Not specified'
        };
    }

    public function hasContraindications(): bool
    {
        return !empty($this->contraindications);
    }

    public function requiresExpiryDateCheck(): bool
    {
        return $this->expiry_date_required === true;
    }

    public function getTotalDailyDose(): ?float
    {
        // Calculate total daily dose based on frequency and requested quantity
        if (!$this->requested_quantity || !$this->frequency) {
            return null;
        }

        $dailyMultiplier = match($this->frequency) {
            'once_daily' => 1,
            'twice_daily' => 2,
            'three_times_daily' => 3,
            'four_times_daily' => 4,
            'every_6_hours' => 4,
            'every_8_hours' => 3,
            'every_12_hours' => 2,
            default => null
        };

        return $dailyMultiplier ? $this->requested_quantity * $dailyMultiplier : null;
    }
}