<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Template;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Appointment;
use App\Models\ConsultationPlaceholderAttributes;

class Consultation extends Model
{
    protected $fillable = [
        'template_id',
        'patient_id',
        'status',
        'consultation_end_at',
        'codebash',
        'name',
        'doctor_id',
        'appointment_id'
    ];

     protected $casts = [
        'consultation_end_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        // Add other date/time columns if you want them automatically cast
    ];
    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class , 'appointment_id');
    }

    public function placeholderAttributes()
    {
        return $this->hasMany(ConsultationPlaceholderAttributes::class);
    }
}
