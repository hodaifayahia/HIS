<?php
// app/Models/Caisse/FinancialTransaction.php

namespace App\Models\Caisse;

use App\Models\Patient;
use App\Models\User;
use App\Models\Reception\ficheNavetteItem;
use App\Models\Reception\ItemDependency;
use App\Models\Bank\BankAccount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FinancialTransaction extends Model
{
    use HasFactory;

    protected $table = 'financial_transactions';
    
    protected $fillable = [
        'fiche_navette_item_id',
        'item_dependency_id',
        'patient_id', // This is actually prestation_id based on user's note
        'cashier_id',
        'approved_by',
        'amount',
        'transaction_type',
        'payment_method',
        'b2b_invoice_id',
        'notes',
        'status',
        'is_bank_transaction',
        'bank_id_account',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'item_dependency_id' => 'integer',
        'is_bank_transaction' => 'boolean',
        'bank_id_account' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'formatted_amount',
        'transaction_type_text',
        'transaction_type_class',
        'payment_method_text',
        'reference'
    ];

    // Relationships
    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function ficheNavetteItem()
    {
        return $this->belongsTo(ficheNavetteItem::class, 'fiche_navette_item_id');
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function itemDependency()
    {
        return $this->belongsTo(ItemDependency::class, 'item_dependency_id');
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class, 'bank_id_account');
    }

    public function b2bInvoice()
    {
        // B2B\Invoice model doesn't exist, return a dummy relationship
        return $this->belongsTo(self::class, 'id')->whereRaw('1 = 0');
    }

    // Scopes
    public function scopeByFicheNavetteItem($query, int $ficheNavetteItemId)
    {
        return $query->where('fiche_navette_item_id', $ficheNavetteItemId);
    }

    public function scopeByPrestation($query, int $prestationId)
    {
        return $query->where('patient_id', $prestationId); // patient_id is prestation_id
    }

    public function scopeByTransactionType($query, string $type)
    {
        return $query->where('transaction_type', $type);
    }

    public function scopePayments($query)
    {
        return $query->where('transaction_type', 'payment');
    }

    public function scopeRefunds($query)
    {
        return $query->where('transaction_type', 'refund');
    }

    public function scopeAdjustments($query)
    {
        return $query->where('transaction_type', 'adjustment');
    }

    public function scopeDonations($query)
    {
        return $query->where('transaction_type', 'donation');
    }

    public function scopeCredits($query)
    {
        return $query->where('transaction_type', 'credit');
    }

    public function scopeByCashier($query, int $cashierId)
    {
        return $query->where('cashier_id', $cashierId);
    }

    public function scopeByPaymentMethod($query, string $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', now()->toDateString());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereBetween('created_at', [
            now()->startOfMonth(),
            now()->endOfMonth()
        ]);
    }

    public function scopeDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // Accessors
    public function getFormattedAmountAttribute(): string
    {
        return number_format((float) $this->amount, 2);
    }

    public function getTransactionTypeTextAttribute(): string
    {
        return match($this->transaction_type) {
            'payment' => 'Payment',
            'refund' => 'Refund',
            'adjustment' => 'Adjustment',
            default => ucfirst($this->transaction_type)
        };
    }

    public function getPaymentMethodTextAttribute(): string
    {
        return match($this->payment_method) {
            'cash' => 'Cash',
            'card' => 'Credit Card',
            'check' => 'Check',
            'transfer' => 'Bank Transfer',
            'insurance' => 'Insurance',
            default => ucfirst($this->payment_method)
        };
    }

    public function getTransactionTypeClassAttribute(): string
    {
        return match($this->transaction_type) {
            'payment' => 'success',
            'refund' => 'warning',
            'adjustment' => 'info',
            'donation' => 'secondary',
            'credit' => 'primary',
            default => 'secondary'
        };
    }

    public function getReferenceAttribute(): string
    {
        return 'TXN-' . str_pad($this->id, 8, '0', STR_PAD_LEFT);
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->transaction_type) {
            'payment' => 'success',
            'refund' => 'warning',
            'adjustment' => 'info',
            default => 'secondary'
        };
    }

    // Methods
    public function isPayment(): bool
    {
        return $this->transaction_type === 'payment';
    }

    public function isRefund(): bool
    {
        return $this->transaction_type === 'refund';
    }

    public function isAdjustment(): bool
    {
        return $this->transaction_type === 'adjustment';
    }

    public function isDonation(): bool
    {
        return $this->transaction_type === 'donation';
    }

    public function isCredit(): bool
    {
        return $this->transaction_type === 'credit';
    }

    public function canBeRefunded(): bool
    {
        return $this->isPayment() && $this->amount > 0;
    }

    public function canBeAdjusted(): bool
    {
        return $this->created_at->isToday();
    }
}
