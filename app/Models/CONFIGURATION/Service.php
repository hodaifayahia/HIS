<?php

namespace App\Models\CONFIGURATION;

use Illuminate\Database\Eloquent\Model;
use  App\Models\Specialization;
use App\Models\B2B\Annex;
use App\Models\CONFIGURATION\Prestation;

class Service extends Model
{
    protected $fillable = [
        'name',
        'description',
        'image_url',
        'start_date',
        'end_date',
        'agmentation',
        'is_active'
    ];
     // Add this relationship
  // In App\Models\CONFIGURATION\Service.php
public function specializations()
{
    return $this->hasMany(Specialization::class);
}

    public function annexes()
    {
        return $this->hasMany(Annex::class, 'service_id'); // Assuming 'service_id' is the foreign key in Annex model
    }

    public function prestations()
    {
        return $this->hasMany(Prestation::class, 'service_id'); // Assuming 'service_id' is the foreign key in Prestation model
    }

}
