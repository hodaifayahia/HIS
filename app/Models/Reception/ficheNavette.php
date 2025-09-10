<?php

namespace App\Models\Reception;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Patient;
use App\Models\Reception\ficheNavetteItem;

class ficheNavette extends Model
{
    protected $table = 'fiche_navette';
    
    protected $fillable = [
        'id',
        'patient_id',
        'creator_id',
        'fiche_date',
        'status',
        'reference',
        'total_amount',
        'notes',
        'created_by'
    ];

    protected $casts = [
        'fiche_date' => 'datetime',
        'arrival_time' => 'datetime',
        'departure_time' => 'datetime',
        'total_amount' => 'decimal:2'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
    
    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function items()
    {
        return $this->hasMany(ficheNavetteItem::class, 'fiche_navette_id');
    }

    // Helper method to get patient name
    public function getPatientNameAttribute()
    {
        return $this->patient ? $this->patient->first_name . ' ' . $this->patient->last_name : 'N/A';
    }
}
