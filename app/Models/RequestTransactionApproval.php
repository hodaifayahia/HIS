<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Coffre\CoffreTransaction;

class RequestTransactionApproval extends Model
{
    protected $fillable = [
        'status',
        'requested_by',
        'approved_by',
        'request_transaction_id',
        'candidate_user_ids',
    ];

    protected $casts = [
        'candidate_user_ids' => 'array',
    ];

    public function requested()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
    public function approved()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function transaction()
    {
        return $this->belongsTo(CoffreTransaction::class, 'request_transaction_id');
    }

}
