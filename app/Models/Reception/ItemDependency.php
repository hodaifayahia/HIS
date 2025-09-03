<?php

namespace App\Models\Reception;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemDependency extends Model
{
    protected $table = 'item_dependencies';
    
    protected $fillable = [
        'parent_item_id',
        'dependent_prestation_id',
        'dependency_type',
        'doctor_id',
        'base_price',
        'final_price',
        'status',
        'payment_status',
        'remaining_amount',
        'paid_amount',
        'patient_share',
        'prise_en_charge_date',
        'discounted_price',
        'organisme_share',
        'notes',
        'custom_name' ,// Add this field
        'is_package'
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'final_price' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'patient_share' => 'decimal:2',
        'discounted_price' => 'decimal:2',
        'organisme_share' => 'decimal:2',
        'prise_en_charge_date' => 'date'
    ];

    /**
     * Get the parent item
     */
    public function parentItem(): BelongsTo
    {
        return $this->belongsTo(ficheNavetteItem::class, 'parent_item_id');
    }

    /**
     * Get the dependent prestation
     */
    public function dependencyPrestation(): BelongsTo
    {
        // dependent_prestation_id is the correct foreign key linking to prestation id
        return $this->belongsTo(\App\Models\CONFIGURATION\Prestation::class, 'dependent_prestation_id', 'id');
    }

    /**
     * Get the doctor assigned to this dependency
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'doctor_id');
    }

    /**
     * Get the display name (custom name takes priority, then prestation name)
     */
    public function getDisplayNameAttribute(): string
    {
        // Priority: custom_name first, then prestation name, then fallback
        if (!empty($this->custom_name)) {
            return $this->custom_name;
        }
        
        if ($this->dependencyPrestation) {
            return $this->dependencyPrestation->name;
        }
        
        return 'Unknown Dependency';
    }
}
