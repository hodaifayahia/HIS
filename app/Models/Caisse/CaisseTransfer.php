<?php
// app/Models/Caisse/CaisseTransfer.php

namespace App\Models\Caisse;

use App\Models\User;
use App\Models\Coffre\Caisse;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class CaisseTransfer extends Model
{
    use HasFactory;

    protected $table = 'caisse_transfers';

    protected $fillable = [
        'caisse_id',
        'from_user_id',
        'to_user_id',
        'amount_sended',
        'caisse_session_id',
        'have_problems',
        'amount_received',
        'description',
        'status',
        'transfer_token',
        'token_expires_at',
        'accepted_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'token_expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    protected $hidden = [
        'transfer_token',
    ];

    protected $appends = [
        'formatted_amount',
        'formatted_status',
        'is_expired',
        'can_be_accepted',
    ];

    // Accessors
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2);
    }

    public function getFormattedStatusAttribute()
    {
        return ucfirst($this->status);
    }

    public function getIsExpiredAttribute()
    {
        return $this->token_expires_at && $this->token_expires_at->isPast();
    }

    public function getCanBeAcceptedAttribute()
    {
        return $this->status === 'pending' && !$this->is_expired;
    }

    // Relationships
    public function caisse(): BelongsTo
    {
        return $this->belongsTo(Caisse::class, 'caisse_id');
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('from_user_id', $userId)
              ->orWhere('to_user_id', $userId);
        });
    }

    public function scopeByCaisse($query, int $caisseId)
    {
        return $query->where('caisse_id', $caisseId);
    }

    // Methods
    public function generateToken(): string
    {
        $this->transfer_token = Str::random(32);
        $this->token_expires_at = Carbon::now()->addHours(24);
        $this->save();

        return $this->transfer_token;
    }

    public function accept(): bool
    {
        if (!$this->can_be_accepted) {
            return false;
        }

        $this->status = 'accepted';
        $this->accepted_at = Carbon::now();
        return $this->save();
    }

    public function reject(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $this->status = 'rejected';
        return $this->save();
    }

    public function expire(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $this->status = 'expired';
        return $this->save();
    }

    public function isValidToken(string $token): bool
    {
        return $this->transfer_token === $token && 
               $this->token_expires_at && 
               $this->token_expires_at->isFuture();
    }
}
