<?php

namespace App\Models\Reception;

use Illuminate\Database\Eloquent\Model;

class RemiseRequestPrestation extends Model
{
    protected $table = 'remise_request_prestations';

    protected $fillable = [
        'remise_request_id',
        'prestation_id',
        'proposed_amount',
        'final_amount',
    ];

    public function remiseRequest()
{
    return $this->belongsTo(RemiseRequest::class);
}

public function prestation()
{
    return $this->belongsTo(Prestation::class);
}

public function contributions()
{
    return $this->hasMany(RemiseRequestPrestationContribution::class);
}
}
