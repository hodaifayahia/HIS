<?php

namespace App\Models;

use App\Models\Appointment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use App\Models\Allergy;
use App\Models\ChronicDiseases;
use App\Models\FamilyDiseases;
use App\Models\Surgical;
use App\Models\Consultation;


use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Patient extends Model
{
    use HasFactory, Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'Firstname',
        'phone',
        'Lastname',
        'Parent',
        'Idnum',
         'age',
         'gender',
        'weight',
        'created_by',
        'dateOfBirth',
    ];
    protected $casts = [
        'age' => 'integer',
        'weight' => 'decimal:2',
    ];
     // Add 'fullname' to the $appends array
    protected $appends = ['fullname'];

    /**
     * Get the patient's full name.
     *
     * @return string
     */
    public function getFullnameAttribute(): string
    {
        return "{$this->Firstname} {$this->Lastname}";
    }

    public function appointments()
{
    return $this->hasMany(Appointment::class);
}
  public function consultations()
    {
        return $this->hasMany(Consultation::class);
    }
  // In App\Models\Patient.php
public function allergies()
{
    return $this->hasMany(Allergy::class);
}

public function chronicDiseases()
{
    return $this->hasMany(ChronicDiseases::class);
}

public function familyDiseases()
{
    return $this->hasMany(FamilyDiseases::class);
}

public function surgical()
{
    return $this->hasMany(Surgical::class);
}

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function scopeSearch($query, $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('Firstname', 'like', "%{$searchTerm}%")
                ->orWhere('Lastname', 'like', "%{$searchTerm}%")
                ->orWhere('Idnum', 'like', "%{$searchTerm}%");
        });
    }
    public function scopeFilterByDate($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
