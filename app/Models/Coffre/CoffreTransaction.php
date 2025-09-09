<?php
// app/Models/Coffre/CoffreTransaction.php

namespace App\Models\Coffre;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
//import bank
use App\Models\Bank\Bank;

class CoffreTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'coffre_id',
        'user_id',
        'bank_account_id',
        'transaction_type',
        'amount',
        'status',
        'description',
        'source_caisse_session_id',
        'destination_banque_id',
        'dest_coffre_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function coffre()
    {
        return $this->belongsTo(Coffre::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sourceCaisseSession()
    {
        return $this->belongsTo(\App\Models\CaisseSession::class, 'source_caisse_session_id');
    }

    public function destinationBanque()
    {
        return $this->belongsTo(bank::class, 'destination_banque_id');
    }

    public function destinationCoffre()
    {
        return $this->belongsTo(Coffre::class, 'dest_coffre_id');
    }

    public function approvalRequest()
    {
        return $this->hasOne(\App\Models\RequestTransactionApproval::class, 'request_transaction_id');
    }

    // Scopes
    public function scopeByType($query, string $type)
    {
        return $query->where('transaction_type', $type);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeForCoffre($query, int $coffreId)
    {
        return $query->where('coffre_id', $coffreId);
    }

    // Accessors
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2, '.', ',');
    }

    public function getTransactionTypeDisplayAttribute()
    {
        return match($this->transaction_type) {
            'deposit' => 'Deposit',
            'withdrawal' => 'Withdrawal',
            'transfer_in' => 'Transfer In',
            'transfer_out' => 'Transfer Out',
            'adjustment' => 'Adjustment',
            default => ucfirst($this->transaction_type)
        };
    }
}
