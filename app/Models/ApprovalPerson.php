<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalPerson extends Model
{
    protected $table = 'approval_persons';

    protected $fillable = [
        'user_id',
        'max_amount',
        'title',
        'description',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'max_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Get the user associated with this approval person.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all approvals handled by this person.
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(BonCommendApproval::class);
    }

    /**
     * Scope to get only active approval persons.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to find approval persons who can handle a specific amount.
     */
    public function scopeCanApproveAmount($query, $amount)
    {
        return $query->where('is_active', true)
            ->where('max_amount', '>=', $amount)
            ->orderBy('priority', 'asc')
            ->orderBy('max_amount', 'asc');
    }

    /**
     * Check if this person can approve the given amount.
     */
    public function canApprove($amount): bool
    {
        return $this->is_active && $this->max_amount >= $amount;
    }

    /**
     * Get pending approvals count.
     */
    public function getPendingApprovalsCountAttribute()
    {
        return $this->approvals()->where('status', 'pending')->count();
    }
}
