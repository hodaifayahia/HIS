<?php

namespace App\Models;

use App\Models\CONFIGURATION\Prestation;
use App\Models\Reception\ficheNavette;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Admission extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'type',
        'status',
        'admitted_at',
        'discharged_at',
        'initial_prestation_id',
        'fiche_navette_id',
        'documents_verified',
        'created_by',
    ];

    protected $casts = [
        'admitted_at' => 'datetime',
        'discharged_at' => 'datetime',
        'documents_verified' => 'boolean',
    ];

    /**
     * Get the patient
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the doctor
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Get the initial prestation
     */
    public function initialPrestation(): BelongsTo
    {
        return $this->belongsTo(Prestation::class, 'initial_prestation_id');
    }

    /**
     * Get the creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get admission procedures
     */
    public function procedures(): HasMany
    {
        return $this->hasMany(AdmissionProcedure::class);
    }

    /**
     * Get admission documents
     */
    public function documents(): HasMany
    {
        return $this->hasMany(AdmissionDocument::class);
    }

    /**
     * Get billing records
     */
    public function billingRecords(): HasMany
    {
        return $this->hasMany(AdmissionBillingRecord::class);
    }

    /**
     * Get discharge tickets
     */
    public function dischargeTickets(): HasMany
    {
        return $this->hasMany(AdmissionDischargeTicket::class);
    }

    /**
     * Get the fiche navette
     */
    public function ficheNavette(): BelongsTo
    {
        return $this->belongsTo(ficheNavette::class, 'fiche_navette_id');
    }
}
