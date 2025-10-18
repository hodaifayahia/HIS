<?php

namespace App\Models\CONFIGURATION;

use Illuminate\Database\Eloquent\Model;
use App\Models\CONFIGURATION\Prestation;
use App\Models\User;

class Remise extends Model
{
    protected $table = 'remises';
    protected $fillable = [
        'name',
        'description', 
        'code',
        'prestation_id', 
        'amount', 
        'percentage',
        'type',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'amount' => 'decimal:2',
        'percentage' => 'decimal:2'
    ];

    // Fix: This should be belongsToMany, not using prestation_id as foreign key
    public function prestations()
    {
        return $this->belongsToMany(Prestation::class, 'prestation_remises', 'remise_id', 'prestation_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'remise_users', 'remise_id', 'user_id');
    }
    
}
