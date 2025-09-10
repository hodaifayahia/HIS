<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrescriptionMedication extends Model
{
    // Ensure consistent casing for class name: PrescriptionMedication
    // The table name will automatically be 'prescription_medications'

    protected $fillable = [
        'prescription_id',
        'medication_id',
        'form',
        'num_times',
        'frequency',
        'start_date',
        'frequency_period',
        'period_intakes',
        'timing_preference',
        'end_date',
        'description',
        'pills_matin',
        'pills_apres_midi',
        'pills_midi',
        'pills_soir'
    ];

    /**
     * Get the prescription that owns the medication.
     */
    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }

    /**
     * Get the medication details.
     */
    public function medication()
    {
        // This correctly links to the base Medication model
        return $this->belongsTo(Medication::class, 'medication_id');
    }
}