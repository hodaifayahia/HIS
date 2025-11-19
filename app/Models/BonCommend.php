<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BonCommend extends Model
{
    use HasFactory;
    protected $table = 'bon_commends';

    protected $fillable = [
        'bonCommendCode',
        'fournisseur_id',
        'service_demand_purchasing_id',
        'order_date',                    // ADDED
        'expected_delivery_date',        // ADDED
        'department',                    // ADDED
        'priority',                      // ADDED
        'notes',                         // ADDED
        'created_by',
        'status',
        'approval_status',               // ADDED for approval workflow
        'has_approver_modifications',    // ADDED for approval workflow
        'price',                         // Optional total price field
        'pdf_content',
        'pdf_generated_at',
        'is_confirmed',
        'confirmed_at',
        'confirmed_by',
        'boncommend_confirmed_at',      // ADDED (from your updateStatus method)
        'attachments',                   // JSON column for attachments
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'order_date' => 'date',              // ADDED
        'expected_delivery_date' => 'date',  // ADDED
        'pdf_generated_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'boncommend_confirmed_at' => 'datetime',  // ADDED
        'price' => 'decimal:2',              // Cast price as decimal
        'attachments' => 'array',            // Cast JSON to array
        'is_confirmed' => 'boolean',         // ADDED for clarity
        'has_approver_modifications' => 'boolean', // ADDED for approval workflow
    ];

    /**
     * Attributes to append to model's array/JSON form
     */
    protected $appends = [
        'can_be_confirmed_now',
    ];

    /**
     * Get the supplier that owns the bon commend.
     */
    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Fournisseur::class, 'fournisseur_id');
    }

    /**
     * Get the service demand that this bon commend is for.
     */
    public function serviceDemand(): BelongsTo
    {
        return $this->belongsTo(ServiceDemendPurchcing::class, 'service_demand_purchasing_id');
    }

    /**
     * Get the user who created this bon commend.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the user who confirmed this bon commend.
     */
    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'confirmed_by');
    }

    /**
     * Get all items for this bon commend.
     */
    public function items(): HasMany
    {
        return $this->hasMany(BonCommendItem::class, 'bon_commend_id');
    }

    /**
     * Alias for items relationship (for consistency)
     */
    public function products(): HasMany
    {
        return $this->hasMany(\App\Models\BonCommendItem::class, 'bon_commend_id');
    }

    /**
     * Get approval requests for this bon commend.
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(BonCommendApproval::class);
    }

    /**
     * Get the current pending approval request.
     */
    public function currentApproval()
    {
        return $this->hasOne(BonCommendApproval::class)->latestOfMany()->where('status', 'pending');
    }

    // NOTE: Remove this relationship if using JSON storage for attachments
    // Keep only if you're still using BonCommendAttachment table
    // /**
    //  * Get the attachments for this bon commend.
    //  */
    // public function attachments(): HasMany
    // {
    //     return $this->hasMany(BonCommendAttachment::class);
    // }

    /**
     * Scope a query to only include draft bon commends.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope a query to only include sent bon commends.
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope a query to only include confirmed bon commends.
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Scope a query to only include completed bon commends.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include cancelled bon commends.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope a query to filter by supplier.
     */
    public function scopeBySupplier($query, $supplierId)
    {
        return $query->where('fournisseur_id', $supplierId);
    }

    /**
     * Scope a query to filter by priority.
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('order_date', [$startDate, $endDate]);
    }

    /**
     * Get the total amount for this bon commend.
     * Prioritizes the direct price field, falls back to calculating from items.
     */
    public function getTotalAmountAttribute()
    {
        // If direct price is set, use it
        if (! is_null($this->price)) {
            return (float) $this->price;
        }

        // Otherwise calculate from items
        return $this->items->sum(function ($item) {
            return ($item->quantity_desired ?? $item->quantity) * ($item->price ?? 0);
        });
    }

    /**
     * Get the service name through the service demand relationship.
     */
    public function getServiceNameAttribute()
    {
        return $this->serviceDemand?->service?->name;
    }

    /**
     * Get the supplier name.
     */
    public function getSupplierNameAttribute()
    {
        return $this->fournisseur?->company_name;
    }

    /**
     * Get formatted order date.
     */
    public function getFormattedOrderDateAttribute()
    {
        return $this->order_date?->format('d/m/Y');
    }

    /**
     * Get formatted expected delivery date.
     */
    public function getFormattedExpectedDeliveryDateAttribute()
    {
        return $this->expected_delivery_date?->format('d/m/Y');
    }

    /**
     * Check if bon commend is editable.
     */
    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'sent']);
    }

    /**
     * Check if bon commend is deletable.
     */
    public function isDeletable(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if bon commend can be confirmed.
     */
    public function canBeConfirmed(): bool
    {
        // Must be in draft status
        if ($this->status !== 'draft') {
            return false;
        }

        // Must have items
        if (! $this->items()->exists()) {
            return false;
        }

        // Check if approval is required
        if ($this->requiresApprovalCheck()) {
            // Must have an approved approval
            return $this->approvals()->where('status', 'approved')->exists();
        }

        // If no approval required, can be confirmed
        return true;
    }

    /**
     * Calculate the actual total amount from all items.
     */
    public function calculateTotalAmount(): float
    {
        return $this->items->sum(function ($item) {
            return ($item->quantity_desired ?? $item->quantity ?? 0) * ($item->price ?? 0);
        });
    }

    /**
     * Check if this bon commend requires approval based on amount thresholds or product flags.
     */
    public function requiresApprovalCheck(): bool
    {
        // Check if any product has is_request_approval OR is_required_approval flag set to true
        $hasProductRequiringApproval = $this->items()->whereHas('product', function ($query) {
            $query->where('is_required_approval', true)
                ->orWhere('is_request_approval', true);
        })->exists();

        if ($hasProductRequiringApproval) {
            return true;
        }

        // Check if total amount exceeds any approval threshold
        $totalAmount = $this->calculateTotalAmount();

        // Get the minimum approval threshold to see if approval is needed
        $minThreshold = \App\Models\ApprovalPerson::where('is_active', true)
            ->min('max_amount');

        return $minThreshold && $totalAmount > $minThreshold;
    }

    /**
     * Find the appropriate approver based on the total amount.
     */
    public function findApprover()
    {
        $totalAmount = $this->calculateTotalAmount();

        // Get active approval persons ordered by max_amount ascending
        $approvalPersons = \App\Models\ApprovalPerson::where('is_active', true)
            ->orderBy('max_amount', 'asc')
            ->get();

        // Find the first approver whose max_amount is >= total amount
        $appropriateApprover = $approvalPersons->first(function ($approver) use ($totalAmount) {
            return $approver->max_amount >= $totalAmount;
        });

        // If no approver can handle the amount, return the highest threshold approver
        if (! $appropriateApprover && $approvalPersons->isNotEmpty()) {
            $appropriateApprover = $approvalPersons->sortByDesc('max_amount')->first();
        }

        return $appropriateApprover;
    }

    /**
     * Get the highest approval person (for amounts exceeding all thresholds).
     */
    public function getHighestApprover()
    {
        return \App\Models\ApprovalPerson::where('is_active', true)
            ->orderBy('max_amount', 'desc')
            ->first();
    }

    /**
     * Check if this bon commend requires approval based on amount.
     */
    public function requiresApproval($threshold = 10000): bool
    {
        return $this->total_amount > $threshold;
    }

    /**
     * Check if bon commend has pending approval.
     */
    public function hasPendingApproval(): bool
    {
        return $this->approvals()->where('status', 'pending')->exists();
    }

    /**
     * Check if bon commend is approved.
     */
    public function isApproved(): bool
    {
        return $this->approvals()->where('status', 'approved')->exists();
    }

    /**
     * Check if bon commend is rejected.
     */
    public function isRejected(): bool
    {
        return $this->approvals()->where('status', 'rejected')->exists();
    }

    /**
     * Get whether this bon commend can be confirmed right now.
     * This is an accessor attribute that will be available in JSON responses.
     */
    public function getCanBeConfirmedNowAttribute(): bool
    {
        // Must be in draft status
        if ($this->status !== 'draft') {
            return false;
        }

        // Must have items
        if (! $this->items()->exists()) {
            return false;
        }

        // Check if approval is required
        if ($this->requiresApprovalCheck()) {
            // Must have an approved approval to confirm
            return $this->isApproved();
        }

        // If no approval required, can be confirmed directly
        return true;
    }

    /**
     * Get the approval status.
     */
    public function getApprovalStatusAttribute()
    {
        if ($this->isRejected()) {
            return 'rejected';
        }

        if ($this->hasPendingApproval()) {
            return 'pending_approval';
        }

        if ($this->isApproved()) {
            return 'approved';
        }

        return 'no_approval_required';
    }

    /**
     * Get priority label with styling info.
     */
    public function getPriorityLabelAttribute()
    {
        $priorities = [
            'low' => ['label' => 'Low', 'color' => 'secondary'],
            'normal' => ['label' => 'Normal', 'color' => 'info'],
            'high' => ['label' => 'High', 'color' => 'warning'],
            'urgent' => ['label' => 'Urgent', 'color' => 'danger'],
        ];

        return $priorities[$this->priority] ?? ['label' => 'Normal', 'color' => 'info'];
    }

    /**
     * Get status label with styling info.
     */
    public function getStatusLabelAttribute()
    {
        $statuses = [
            'draft' => ['label' => 'Draft', 'color' => 'secondary'],
            'sent' => ['label' => 'Sent', 'color' => 'info'],
            'confirmed' => ['label' => 'Confirmed', 'color' => 'success'],
            'completed' => ['label' => 'Completed', 'color' => 'success'],
            'cancelled' => ['label' => 'Cancelled', 'color' => 'danger'],
        ];

        return $statuses[$this->status] ?? ['label' => 'Unknown', 'color' => 'secondary'];
    }

    /**
     * Get total items count.
     */
    public function getTotalItemsAttribute()
    {
        return $this->items()->count();
    }

    /**
     * Get attachment count from JSON.
     */
    public function getAttachmentCountAttribute()
    {
        return is_array($this->attachments) ? count($this->attachments) : 0;
    }

    /**
     * Boot the model and add event listeners.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->bonCommendCode)) {
                $year = now()->year;
                $count = static::whereYear('created_at', $year)->count() + 1;
                $model->bonCommendCode = 'BC-'.$year.'-'.str_pad($count, 6, '0', STR_PAD_LEFT);
            }

            // Set default order date if not provided
            if (empty($model->order_date)) {
                $model->order_date = now();
            }

            // Set default priority if not provided
            if (empty($model->priority)) {
                $model->priority = 'normal';
            }
        });
    }
}
