<?php

namespace App\Models;

use App\Models\CONFIGURATION\Service;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BonRetour extends Model
{
    use HasFactory;

    protected $fillable = [
        'bon_retour_code',
        'bon_entree_id',
        'fournisseur_id',
        'return_type',
        'status',
        'service_abv',
        'total_amount',
        'bon_entree_id',
        'reason',
        'return_date',
        'reference_invoice',
        'credit_note_received',
        'credit_note_number',
        'attachments',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'return_date' => 'date',
        'credit_note_received' => 'boolean',
        'attachments' => 'array',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Return types
    const RETURN_TYPES = [
        'defective' => 'Defective Product',
        'expired' => 'Expired',
        'damaged' => 'Damaged',
        'wrong_item' => 'Wrong Item',
        'wrong_delivery' => 'Wrong Delivery',
        'overstock' => 'Overstock',
        'quality_issue' => 'Quality Issue',
        'other' => 'Other',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    // Relationships
    public function bonEntree(): BelongsTo
    {
        return $this->belongsTo(BonEntree::class, 'bon_entree_id');
    }

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class, 'fournisseur_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BonRetourItem::class, 'bon_retour_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_abv', 'service_abv');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeByReturnType($query, $type)
    {
        return $query->where('return_type', $type);
    }

    public function scopeByFournisseur($query, $fournisseurId)
    {
        return $query->where('fournisseur_id', $fournisseurId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('return_date', [$startDate, $endDate]);
    }

    // Accessors & Mutators
    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'pending' => 'Pending Approval',
            'approved' => 'Approved',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status),
        };
    }

    public function getReturnTypeLabelAttribute()
    {
        return self::RETURN_TYPES[$this->return_type] ?? ucfirst($this->return_type);
    }

    public function getIsEditableAttribute()
    {
        return in_array($this->status, ['draft', 'pending']);
    }

    // Methods
    public function calculateTotal()
    {
        $total = $this->items->sum('total_amount');
        $this->update(['total_amount' => $total]);
        return $total;
    }

    public function approve($userId)
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

        // Update stock for each item
        foreach ($this->items as $item) {
            $item->updateStockForReturn();
        }

        return $this;
    }

    public function complete()
    {
        if ($this->status !== self::STATUS_APPROVED) {
            throw new \Exception('Only approved returns can be completed');
        }

        $this->update(['status' => self::STATUS_COMPLETED]);
        return $this;
    }

    public function cancel($reason = null)
    {
        if (!$this->is_editable) {
            throw new \Exception('This return cannot be cancelled');
        }

        $this->update([
            'status' => self::STATUS_CANCELLED,
            'reason' => $reason ?? $this->reason,
        ]);

        return $this;
    }

    public function generateCode()
    {
        $year = now()->year;
        $lastBonRetour = static::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();
        
        $nextNumber = $lastBonRetour ? (intval(substr($lastBonRetour->bon_retour_code, -6)) + 1) : 1;
        
        return 'BR-' . $year . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    // Boot method for auto-generating code
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($bonRetour) {
            if (empty($bonRetour->bon_retour_code)) {
                $bonRetour->bon_retour_code = $bonRetour->generateCode();
            }
            
            if (empty($bonRetour->return_date)) {
                $bonRetour->return_date = now();
            }
        });
    }
}
