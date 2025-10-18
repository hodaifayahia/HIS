<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientDocement extends Model
{
    protected $table = 'patient_docements';
    
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'folder_id',
        'document_type',
        'document_path',
        'document_name',
        'appointment_id',
        'document_size'
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

   
public function doctor()
{
    return $this->belongsTo(Doctor::class, 'doctor_id');
}

    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }
}
