<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractPercentage extends Model
{
    protected $fillable = [
        'contract_id',
        'percentage',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
    ];

    public function convention(): BelongsTo
    {
        return $this->belongsTo(Convention::class, 'contract_id');
    }
}
