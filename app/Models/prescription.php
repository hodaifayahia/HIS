<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Consultation;
use App\Models\User;
use App\Models\Patient;
use App\Models\prescriptionMedication;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class prescription extends Model
{
    protected $fillable = [
        'patient_id',
        'consultation_id',
        'doctor_id',
        'appointment_id',
        'prescription_date',
        'pdf_path',
        'signature_status',
    ];

    /**
     * Get the medications for the prescription.
     */
    public function medications()
    {
        return $this->hasMany(prescriptionMedication::class);
    }

    /**
     * Get the patient associated with the prescription.
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the doctor who created the prescription.
     */
    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}