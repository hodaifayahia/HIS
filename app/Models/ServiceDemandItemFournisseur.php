<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceDemandItemFournisseur extends Model
{
    protected $fillable = [
        'service_demand_purchasing_item_id',
        'fournisseur_id',
        'assigned_quantity',
        'unit_price',
        'unit',
        'notes',
        'status',
        'assigned_by',
    ];

    protected $casts = [
        'assigned_quantity' => 'integer',
        'unit_price' => 'decimal:2',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(ServiceDemendPurchcingItem::class, 'service_demand_purchasing_item_id');
    }

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_by');
    }

    public function getTotalAmountAttribute()
    {
        return $this->assigned_quantity * ($this->unit_price ?? 0);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeOrdered($query)
    {
        return $query->where('status', 'ordered');
    }

    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }
}
