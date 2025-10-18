<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FamilyDiseases extends Model
{
   
    protected $fillable = [
        'disease_name',
        'relation',
        'notes',
        'patient_id',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
    
}
