<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Prescription;
use App\Models\PrescriptionMedication;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PrescriptionTemplate extends Model // Corrected class name to PascalCase
{
    use HasFactory;
    protected $table = 'prescriptiontemplates'; // Explicitly define table name if it deviates from convention
    protected $fillable = [
        'doctor_id',
        'name',
        'prescription_id', // This field is crucial for the hasManyThrough relationship
        'description',
        
    ];

    /**
     * Get the doctor who created the prescription template.
     */
    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Get the prescription associated with the template.
     */
    public function prescription()
    {
        return $this->belongsTo(Prescription::class, 'prescription_id');
    }

    /**
     * Get the medications through the associated prescription.
     * This establishes a "has many through" relationship, meaning:
     * PrescriptionTemplate has many PrescriptionMedication records
     * THROUGH the Prescription model.
     *
     * Relationship flow:
     * PrescriptionTemplate -> (prescription_id) -> Prescription -> (id) -> PrescriptionMedication
     */
    public function medications()
    {
        return $this->hasManyThrough(
            PrescriptionMedication::class, // The final model we want to access
            Prescription::class,          // The intermediate model that acts as a bridge
            'id',                         // Foreign key on the intermediate model (Prescription) that matches the local key of the current model
            'prescription_id',            // Foreign key on the final model (PrescriptionMedication)
            'prescription_id',            // Local key on the current model (PrescriptionTemplate) that points to the intermediate model
            'id'                          // Local key on the intermediate model (Prescription) that is referenced by the current model's local key
        );
    }
}