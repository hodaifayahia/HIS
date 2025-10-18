<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BonCommendApproval extends Model
{
    protected $fillable = [
        'bon_commend_id',
        'approval_person_id',
        'requested_by',
        'amount',
        'status',
        'notes',
        'approval_notes',
        'approved_at',
        'rejected_at',
        'requested_at',  // Added this field
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'requested_at' => 'datetime',  // Added this cast
    ];

    /**
     * Get the bon commend that needs approval.
     */
    public function bonCommend(): BelongsTo
    {
        return $this->belongsTo(BonCommend::class);
    }

    /**
     * Get the approval person who will approve.
     */
    public function approvalPerson(): BelongsTo
    {
        return $this->belongsTo(ApprovalPerson::class);
    }

    /**
     * Get the user who requested approval.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Scope to get pending approvals.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get approved approvals.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to get rejected approvals.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope to filter by approval person.
     */
    public function scopeForApprover($query, $approvalPersonId)
    {
        return $query->where('approval_person_id', $approvalPersonId);
    }

    /**
     * Mark approval as approved.
     */
    public function approve($notes = null)
    {
        $this->update([
            'status' => 'approved',
            'approval_notes' => $notes,
            'approved_at' => now(),
        ]);
    }

    /**
     * Mark approval as rejected.
     */
    public function reject($notes = null)
    {
        $this->update([
            'status' => 'rejected',
            'approval_notes' => $notes,
            'rejected_at' => now(),
        ]);
    }

    /**
     * Check if approval is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if approval is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if approval is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Get status label with color.
     */
    public function getStatusLabelAttribute()
    {
        return [
            'pending' => ['label' => 'Pending', 'color' => 'warning'],
            'approved' => ['label' => 'Approved', 'color' => 'success'],
            'rejected' => ['label' => 'Rejected', 'color' => 'danger'],
            'sent_back' => ['label' => 'Sent Back', 'color' => 'info'],
        ][$this->status] ?? ['label' => 'Unknown', 'color' => 'secondary'];
    }
}
