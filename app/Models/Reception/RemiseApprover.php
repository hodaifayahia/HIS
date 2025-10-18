<?php

namespace App\Models\Reception;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class RemiseApprover extends Model
{
    protected $table = 'remise_approvers';

    protected $fillable = [
        'user_id',
        'approver_id',
        'is_approved',
        'comments',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class , 'user_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }
}
