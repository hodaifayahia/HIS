<?php
// app/Models/Bank/BankAccountTransaction.php

namespace App\Models\Bank;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccountTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_account_id',
        'accepted_by_user_id',
        'transaction_type',
        'amount',
        'transaction_date',
        'description',
        'reference',
        'status',
        'reconciled_by_user_id',
        'reconciled_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'datetime',
        'reconciled_at' => 'datetime',
    ];

    // Relationships
    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function acceptedBy()
    {
        return $this->belongsTo(User::class, 'accepted_by_user_id');
    }

    public function reconciledBy()
    {
        return $this->belongsTo(User::class, 'reconciled_by_user_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeReconciled($query)
    {
        return $query->where('status', 'reconciled');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('transaction_type', $type);
    }

    public function scopeByBankAccount($query, int $bankAccountId)
    {
        return $query->where('bank_account_id', $bankAccountId);
    }

    // Accessors
    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pending',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'reconciled' => 'Reconciled',
            default => ucfirst($this->status)
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'completed' => 'success',
            'cancelled' => 'danger',
            'reconciled' => 'info',
            default => 'secondary'
        };
    }

    public function getTransactionTypeTextAttribute(): string
    {
        return ucfirst($this->transaction_type);
    }

    public function getFormattedAmountAttribute(): string
    {
        $currency = $this->bankAccount?->currency ?? 'DZD';
        return number_format($this->amount, 2) . ' ' . $currency;
    }

    // Methods
    public function complete(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $this->update(['status' => 'completed']);
        return true;
    }

    public function cancel(): bool
    {
        if (!in_array($this->status, ['pending', 'completed'])) {
            return false;
        }

        $this->update(['status' => 'cancelled']);
        return true;
    }

    public function reconcile(int $userId): bool
    {
        if ($this->status !== 'completed') {
            return false;
        }

        $this->update([
            'status' => 'reconciled',
            'reconciled_by_user_id' => $userId,
            'reconciled_at' => now()
        ]);

        return true;
    }

    public static function generateReference(): string
    {
        return 'TXN-' . date('Ymd') . '-' . str_pad(self::whereDate('created_at', today())->count() + 1, 6, '0', STR_PAD_LEFT);
    }
}
