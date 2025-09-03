<?php
// app/Models/Coffre/CaisseSession.php

namespace App\Models\Coffre;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class CaisseSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'caisse_id',
        'user_id',
        'open_by',
        'closed_by',
        'coffre_id_source',
        'coffre_id_destination',
        'ouverture_at',
        'cloture_at',
        'opening_amount',
        'closing_amount',
        'expected_closing_amount',
        'total_cash_counted',
        'cash_difference',
        'status',
        'opening_notes',
        'closing_notes',
    ];

    protected $casts = [
        'ouverture_at' => 'datetime',
        'cloture_at' => 'datetime',
        'opening_amount' => 'decimal:2',
        'closing_amount' => 'decimal:2',
        'expected_closing_amount' => 'decimal:2',
        'total_cash_counted' => 'decimal:2',
        'cash_difference' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function caisse()
    {
        return $this->belongsTo(Caisse::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function openedBy()
    {
        return $this->belongsTo(User::class, 'open_by');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function sourceCoffre()
    {
        return $this->belongsTo(Coffre::class, 'coffre_id_source');
    }

    public function destinationCoffre()
    {
        return $this->belongsTo(Coffre::class, 'coffre_id_destination');
    }

    public function denominations()
    {
        return $this->hasMany(CaisseSessionDenomination::class);
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['open', 'suspended']);
    }

    public function scopeForCaisse($query, int $caisseId)
    {
        return $query->where('caisse_id', $caisseId);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOpenedBy($query, int $userId)
    {
        return $query->where('open_by', $userId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('ouverture_at', [$startDate, $endDate]);
    }

    // Accessors
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'open' => 'success',
            'closed' => 'info',
            'suspended' => 'warning',
            default => 'secondary'
        };
    }

    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'open' => 'Open',
            'closed' => 'Closed',
            'suspended' => 'Suspended',
            default => ucfirst($this->status)
        };
    }

    public function getDurationAttribute(): ?string
    {
        if (!$this->ouverture_at) return null;
        
        $end = $this->cloture_at ?? now();
        $duration = $this->ouverture_at->diffForHumans($end, true);
        
        return $duration;
    }

    public function getDurationInMinutesAttribute(): ?int
    {
        if (!$this->ouverture_at) return null;
        
        $end = $this->cloture_at ?? now();
        return $this->ouverture_at->diffInMinutes($end);
    }

    public function getVarianceAttribute(): ?float
    {
        if (!$this->closing_amount || !$this->expected_closing_amount) {
            return null;
        }
        
        return $this->closing_amount - $this->expected_closing_amount;
    }

    public function getCashVarianceAttribute(): ?float
    {
        if (!$this->closing_amount || !$this->total_cash_counted) {
            return null;
        }
        
        return $this->closing_amount - $this->total_cash_counted;
    }

    public function getVariancePercentageAttribute(): ?float
    {
        if (!$this->expected_closing_amount || $this->expected_closing_amount == 0) {
            return null;
        }
        
        $variance = $this->getVarianceAttribute();
        if ($variance === null) return null;
        
        return ($variance / $this->expected_closing_amount) * 100;
    }

    public function getIsActiveAttribute(): bool
    {
        return in_array($this->status, ['open', 'suspended']);
    }

    public function getTotalCoinsAttribute(): float
    {
        return $this->denominations()
                   ->where('denomination_type', 'coin')
                   ->sum('total_amount');
    }

    public function getTotalNotesAttribute(): float
    {
        return $this->denominations()
                   ->where('denomination_type', 'note')
                   ->sum('total_amount');
    }

    // Methods
    public function canBeClosed(): bool
    {
        return $this->status === 'open';
    }

    public function canBeReOpened(): bool
    {
        return $this->status === 'closed' && $this->cloture_at?->isToday();
    }

    public function canBeAccessedByUser(User $user): bool
    {
        return $this->user_id === $user->id || 
               $this->open_by === $user->id || 
               $user->hasRole('admin') ||
               $user->hasPermission('manage_all_sessions');
    }

    public function calculateTotalFromDenominations(): float
    {
        return $this->denominations()->sum('total_amount');
    }
}
