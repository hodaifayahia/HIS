<?php
// app/Models/Bank/BankAccount.php

namespace App\Models\Bank;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory;

    protected $table = 'bank_accounts';

    protected $fillable = [
        'bank_id',
        'account_name',
        'account_number',
        'current_balance',
        'description',
        'is_active',
        'is_defult'
    ];

    protected $casts = [
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
        'is_defult' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCurrency($query, string $currency)
    {
        return $query->where('currency', $currency);
    }

    public function scopeByBank($query, int $bankId)
    {
        return $query->where('bank_id', $bankId);
    }

    public function scopeByBankName($query, string $bankName)
    {
        return $query->whereHas('bank', function ($q) use ($bankName) {
            $q->where('name', 'like', "%{$bankName}%");
        });
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('account_name', 'like', "%{$search}%")
              ->orWhere('account_number', 'like', "%{$search}%")
              ->orWhere('iban', 'like', "%{$search}%")
              ->orWhereHas('bank', function ($bankQuery) use ($search) {
                  $bankQuery->where('name', 'like', "%{$search}%")
                           ->orWhere('code', 'like', "%{$search}%");
              });
        });
    }

    // Accessors
    public function getFormattedBalanceAttribute(): string
    {
        return number_format($this->current_balance, 2) . ' ' . $this->currency;
    }

    public function getMaskedAccountNumberAttribute(): string
    {
        if (strlen($this->account_number) <= 4) {
            return $this->account_number;
        }
        return '****' . substr($this->account_number, -4);
    }

    public function getStatusTextAttribute(): string
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }

    public function getStatusColorAttribute(): string
    {
        return $this->is_active ? 'success' : 'danger';
    }

    public function getBalanceStatusAttribute(): string
    {
        if ($this->current_balance > 0) return 'positive';
        if ($this->current_balance < 0) return 'negative';
        return 'neutral';
    }

    public function getBankNameAttribute(): ?string
    {
        return $this->bank?->name;
    }

    public function getBankCodeAttribute(): ?string
    {
        return $this->bank?->code;
    }

    public function getBankSwiftCodeAttribute(): ?string
    {
        return $this->bank?->swift_code;
    }

    public function getBankLogoUrlAttribute(): ?string
    {
        return $this->bank?->logo_url;
    }

    public function getFullAccountNameAttribute(): string
    {
        return $this->bank_name ? "{$this->bank_name} - {$this->account_name}" : $this->account_name;
    }

    public function getFormattedIbanAttribute(): ?string
    {
        if (!$this->iban) return null;
        return chunk_split($this->iban, 4, ' ');
    }

    // Methods
    public function updateBalance(float $amount, string $type = 'credit'): void
    {
        if ($type === 'credit') {
            $this->increment('current_balance', $amount);
            $this->increment('available_balance', $amount);
        } else {
            $this->decrement('current_balance', $amount);
            $this->decrement('available_balance', $amount);
        }
    }

    public function canDebit(float $amount): bool
    {
        return $this->available_balance >= $amount;
    }

    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function validateBankCurrency(): bool
    {
        return $this->bank?->supportsCurrency($this->currency) ?? true;
    }

    public function syncAvailableBalance(): void
    {
        if ($this->available_balance === null || $this->available_balance === 0) {
            $this->update(['available_balance' => $this->current_balance]);
        }
    }
}
