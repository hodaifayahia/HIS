<?php

namespace App\Models\Inventory;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryAuditsParticipante extends Model
{
    protected $table = 'inventory_audits_participantes';
    
    protected $fillable = [
        'inventory_audit_id',
        'user_id',
        'is_participant',
        'is_able_to_see',
        'status',
    ];

    protected $casts = [
        'is_participant' => 'boolean',
        'is_able_to_see' => 'boolean',
    ];

    /**
     * Get the inventory audit this participant belongs to
     */
    public function inventoryAudit(): BelongsTo
    {
        return $this->belongsTo(InventoryAudit::class, 'inventory_audit_id');
    }

    /**
     * Get the user who is the participant
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
