<?php

namespace App\Models\Stock;

use Illuminate\Database\Eloquent\Model;

class Reserve extends Model
{
    
    protected $fillable = [
        'name',
        'description',
        'reserved_at',
        'status',
    ];
    
    public function products()
    {
        return $this->hasMany(\App\Models\ProductReserve::class);
    }
    
}
