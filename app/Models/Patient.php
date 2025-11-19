<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class Patient extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'Firstname',
        'Lastname',
        'Parent',
        'phone',
        'fax_number',
        'email',
        'address',
        'city',
        'postal_code',
        'Idnum',
        'identity_document_type',
        'identity_issued_on',
        'identity_issued_by',
        'passport_number',
        'professional_badge_number',
        'foreigner_card_number',
        'nss',
        'dateOfBirth',
        'birth_place',
        'is_birth_place_presumed',
        'additional_ids',
        'gender',
        'age',
        'height',
        'weight',
        'blood_group',
        'marital_status',
        'mother_firstname',
        'mother_lastname',
        'balance',
        'is_faithful',
        'created_by',
        'firstname_ar',
        'lastname_ar',
        'other_clinical_info',
    ];

    protected $casts = [
        'age' => 'integer',
        'height' => 'decimal:2',
        'weight' => 'decimal:2',
        'balance' => 'decimal:2',
        'is_faithful' => 'boolean',
        'is_birth_place_presumed' => 'boolean',
        'additional_ids' => 'json',
        'dateOfBirth' => 'date',
        'identity_issued_on' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Add 'fullname' to the $appends array
    protected $appends = ['fullname'];

    /**
     * Get the patient's full name.
     */
    public function getFullnameAttribute(): string
    {
        return "{$this->Firstname} {$this->Lastname}";
    }

    public function setFirstnameAttribute($value)
    {
        $this->attributes['Firstname'] = Str::title($value);
    }

    public function setLastnameAttribute($value)
    {
        $this->attributes['Lastname'] = Str::title($value);
    }

    public function setParentAttribute($value)
    {
        $this->attributes['Parent'] = $value ? Str::title($value) : null;
    }

    /**
     * Get the formatted gender attribute
     */
    public function getGenderAttribute($value)
    {
        $genderMap = [
            0 => 'Female',
            1 => 'Male',
            'Female' => 'Female',
            'Male' => 'Male',
            'female' => 'Female',
            'male' => 'Male',
        ];

        return $genderMap[$value] ?? 'Not Specified';
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

    public function ficheNavettes()
    {
        return $this->hasMany(\App\Models\Reception\ficheNavette::class, 'patient_id');
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
