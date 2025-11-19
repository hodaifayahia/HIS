<?php


namespace App\Models\CONFIGURATION;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRefundPermission extends Model
{
    protected $fillable = [
        'granter_id',
        'grantee_id',

        'is_able_to_force',
        'expires_at',
        'notes',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function granter(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'granter_id');
    }

    public function grantee(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'grantee_id');
    }
}