<?php

namespace App\Models\CONFIGURATION;

use App\Models\B2B\Annex;
use App\Models\Specialization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'image_url',
        'start_time',
        'end_time',
        'agmentation',
        'is_active',
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

    public function stockages()
    {
        return $this->hasMany(\App\Models\Stockage::class, 'service_id');
    }
}
