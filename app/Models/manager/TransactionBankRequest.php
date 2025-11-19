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
        'approval_document',
        'payment_method',
        'amount',
        'status',
        'attachment_path',
        'attachment_original_name',
        'attachment_mime_type',
        'attachment_size'
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

    public function uploads()
    {
        return $this->morphMany(\App\Models\Upload::class, 'uploadable');
    }

    /**
     * Get attachment data as an array
     */
    public function getAttachmentAttribute()
    {
        if (!$this->attachment_path) {
            return null;
        }

        return [
            'original_name' => $this->attachment_original_name,
            'mime_type' => $this->attachment_mime_type,
            'size' => $this->attachment_size,
            'path' => $this->attachment_path,
            'url' => asset('storage/' . $this->attachment_path)
        ];
    }
}
