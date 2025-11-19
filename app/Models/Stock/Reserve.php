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
        'created_by',
    ];
    
    public function products()
    {
        return $this->hasMany(\App\Models\ProductReserve::class);
    }
    
    /**
     * The user who created the reserve
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
    
}
