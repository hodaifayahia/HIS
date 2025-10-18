<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Patient;

use App\Models\User;

class Allergy extends Model
{

    protected $fillable = [
        'name',
        'severity',
        'date',
        'note',
        'patient_id',
    ];
    protected $casts = [
        'date' => 'date',
    ];
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}