<?php

namespace App\Models\Inventory;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryAudit extends Model
{
    protected $table = 'inventory_audits';
    
    protected $fillable = [
        'name',
        'description',
        'created_by',
        'started_at',
        'completed_at',
        'status',
        'is_global',
        'is_pharmacy_wide',
        'service_id',
        'stockage_id',
        'scheduled_at'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'is_global' => 'boolean',
        'is_pharmacy_wide' => 'boolean'
    ];

    /**
     * Get the user who created the audit
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the service for this audit
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(\App\Models\CONFIGURATION\Service::class, 'service_id');
    }

    /**
     * Get the pharmacy stockage for this audit
     */
    public function pharmacyStockage(): BelongsTo
    {
        return $this->belongsTo(\App\Models\PharmacyStockage::class, 'stockage_id');
    }

    /**
     * Get the general stockage for this audit
     */
    public function generalStockage(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Stockage::class, 'stockage_id');
    }

    /**
     * Get the stockage for this audit (dynamic based on is_pharmacy_wide)
     */
    public function getStockageAttribute()
    {
        if ($this->is_pharmacy_wide) {
            return $this->pharmacyStockage;
        }
        return $this->generalStockage;
    }

    /**
     * Get all participants for this audit
     */
    public function participants(): HasMany
    {
        return $this->hasMany(InventoryAuditsParticipante::class, 'inventory_audit_id');
    }

    /**
     * Get only active participants (is_participant = true)
     */
    public function activeParticipants(): HasMany
    {
        return $this->participants()->where('is_participant', true);
    }

    /**
     * Get only viewers (is_able_to_see = true)
     */
    public function viewers(): HasMany
    {
        return $this->participants()->where('is_able_to_see', true);
    }
}
