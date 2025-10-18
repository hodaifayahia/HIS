<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrestationRemise extends Model
{
    
    protected $table = 'prestation_remise';

    protected $fillable = [
        'prestation_id',
        'remise_id'
    ];

    public function prestation()
    {
        return $this->belongsTo(Prestation::class, 'prestation_id');
    }

    public function remise()
    {
        return $this->belongsTo(Remise::class, 'remise_id');
    }
}
