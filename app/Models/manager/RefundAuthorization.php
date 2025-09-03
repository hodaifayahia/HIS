<?php
// app/Models/RefundAuthorization.php

namespace App\Models\manager;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Reception\ficheNavetteItem;
use App\Models\Reception\ItemDependency;

class RefundAuthorization extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'fiche_navette_item_id',
        'item_dependency_id',
        'requested_by_id',
        'authorized_by_id',
        'reason',
        'authorized_amount',
        'requested_amount',
        'status',
        'expires_at',
        'notes',
        'priority',
    ];

    protected $casts = [
        'authorized_amount' => 'decimal:2',
        'requested_amount' => 'decimal:2',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function ficheNavetteItem()
    {
        return $this->belongsTo(ficheNavetteItem::class);
    }

    public function itemDependency()
    {
        return $this->belongsTo(ItemDependency::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    public function authorizedBy()
    {
        return $this->belongsTo(User::class, 'authorized_by_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
                    ->orWhere(function($q) {
                        $q->where('expires_at', '<', now())
                          ->where('status', '!=', 'used');
                    });
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'approved')
                    ->where('expires_at', '>', now());
    }

    // Accessors
    public function getIsExpiredAttribute()
    {
        return $this->expires_at && $this->expires_at->isPast() && $this->status !== 'used';
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'expired' => 'secondary',
            'used' => 'info',
            default => 'secondary'
        };
    }

    public function getStatusTextAttribute()
    {
        return match($this->status) {
            'pending' => 'Pending Approval',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'expired' => 'Expired',
            'used' => 'Used',
            default => ucfirst($this->status)
        };
    }

    public function getPriorityColorAttribute()
    {
        return match($this->priority) {
            'high' => 'danger',
            'medium' => 'warning',
            'low' => 'info',
            default => 'secondary'
        };
    }

    // Methods
    public function canBeApproved()
    {
        return $this->status === 'pending' && !$this->is_expired;
    }

    public function canBeRejected()
    {
        return $this->status === 'pending';
    }

    public function canBeUsed()
    {
        return $this->status === 'approved' && !$this->is_expired;
    }

    public function markAsUsed()
    {
        $this->update(['status' => 'used']);
    }

    public function approve($authorizedById, $authorizedAmount = null, $notes = null)
    {
        $this->update([
            'status' => 'approved',
            'authorized_by_id' => $authorizedById,
            'authorized_amount' => $authorizedAmount ?? $this->requested_amount,
            'notes' => $notes,
            'expires_at' => now()->addDays(7), // Authorization valid for 7 days
        ]);
    }

    public function reject($authorizedById, $reason)
    {
        $this->update([
            'status' => 'rejected',
            'authorized_by_id' => $authorizedById,
            'notes' => $reason,
        ]);
    }
}
