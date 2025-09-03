<?php

namespace App\Models\manager;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Caisse\FinancialTransaction;
use App\Models\User;

class TransactionBankRequest extends Model
{
    protected $table = "transaction_bank_requests";

    protected $fillable = [
        'transaction_id',
        'requested_by',
        'approved_by',
        'is_approved',
        'approved_at',
        'requested_at',
        'notes',
        'payment_method',
        'amount',
        'status'
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'requested_at' => 'datetime',
        'is_approved' => 'boolean',
        'amount' => 'decimal:2'
    ];

    public function transaction()
    {
        return $this->belongsTo(FinancialTransaction::class, 'transaction_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function ficheNavetteItem()
    {
        return $this->hasOneThrough(
            \App\Models\Reception\FicheNavetteItem::class,
            FinancialTransaction::class,
            'id', // Financial transaction id
            'id', // FicheNavetteItem id
            'transaction_id', // TransactionBankRequest transaction_id
            'fiche_navette_item_id' // FinancialTransaction fiche_navette_item_id
        );
    }

    public function itemDependency()
    {
        return $this->hasOneThrough(
            \App\Models\Reception\ItemDependency::class,
            FinancialTransaction::class,
            'id', // Financial transaction id
            'id', // ItemDependency id
            'transaction_id', // TransactionBankRequest transaction_id
            'item_dependency_id' // FinancialTransaction item_dependency_id
        );
    }
}
