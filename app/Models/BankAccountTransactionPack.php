<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Bank\BankAccountTransaction;

class BankAccountTransactionPack extends Model
{
    protected $fillable = [
        'bank_account_transaction_id',
        'user_id',
        'name',
        'description',
        'amount',
        'reference',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    // Relationships
    public function bankAccountTransaction()
    {
        return $this->belongsTo(BankAccountTransaction::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
