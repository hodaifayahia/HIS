<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovementAuditLog extends Model
{
    protected $fillable = [
        'stock_movement_id',
        'stock_movement_item_id',
        'user_id',
        'action',
        'old_values',
        'new_values',
        'notes',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    // Relationships
    public function stockMovement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class);
    }

    public function stockMovementItem(): BelongsTo
    {
        return $this->belongsTo(StockMovementItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Helper methods
    public function getFormattedChangesAttribute(): string
    {
        if (! $this->old_values || ! $this->new_values) {
            return 'No changes recorded';
        }

        $changes = [];
        foreach ($this->new_values as $key => $newValue) {
            $oldValue = $this->old_values[$key] ?? 'null';
            if ($oldValue != $newValue) {
                $changes[] = "{$key}: {$oldValue} → {$newValue}";
            }
        }

        return implode(', ', $changes);
    }

    public function getActionDescriptionAttribute(): string
    {
        $descriptions = [
            'approved' => 'Item approved',
            'rejected' => 'Item rejected',
            'created' => 'Movement created',
            'updated' => 'Movement updated',
            'status_changed' => 'Status changed',
        ];

        return $descriptions[$this->action] ?? ucfirst($this->action);
    }

    // Scopes
    public function scopeForMovement($query, $movementId)
    {
        return $query->where('stock_movement_id', $movementId);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
