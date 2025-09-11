<?php

namespace App\Models\Caisse;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Coffre\Caisse;

class CaisseSession extends Model
{
    protected $fillable = [
        'user_id',
        'caisse_id',
        'status',
        'opening_amount',
        'closing_amount',
        'theoretical_amount',
        'opened_at',
        'closed_at',
        'opening_notes',
        'closing_notes',
        'is_transfer'
    ];

    protected $appends = [
        'is_transfer'
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_amount' => 'decimal:2',
        'closing_amount' => 'decimal:2',
        'theoretical_amount' => 'decimal:2'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function caisse()
    {
        return $this->belongsTo(Caisse::class);
    }

    public function isActive()
    {
        return $this->status === 'active';
    }
}
