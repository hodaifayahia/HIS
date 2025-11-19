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
        'companion_id',
        'type',
        'status',
        'admitted_at',
        'discharged_at',
        'initial_prestation_id',
        'fiche_navette_id',
        'documents_verified',
        'created_by',
        'file_number',
        'file_number_verified',
        'observation',
        'company_id',
        'social_security_num',
        'relation_type',
    ];

    protected $casts = [
        'admitted_at' => 'datetime',
        'discharged_at' => 'datetime',
        'documents_verified' => 'boolean',
        'file_number_verified' => 'boolean',
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
     * Get the companion (patient who is accompanying the admitted patient)
     */
    public function companion(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'companion_id');
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

    /**
     * Get the company/organisme
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\B2B\Organisme::class, 'company_id');
    }

    /**
     * Get admission treatments (patient movements)
     */
    public function treatments(): HasMany
    {
        return $this->hasMany(AdmissionTreatment::class);
    }

    /**
     * Boot method to auto-generate file_number
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($admission) {
            if (empty($admission->file_number)) {
                $admission->file_number = self::generateFileNumber();
            }
        });
    }

    /**
     * Generate unique file_number in format: YYYY/number
     */
    public static function generateFileNumber(): string
    {
        $year = date('Y');
        $prefix = $year . '/';
        
        // Get the last admission file number for this year
        $lastAdmission = self::where('file_number', 'LIKE', $prefix . '%')
            ->orderBy('file_number', 'desc')
            ->first();
        
        if ($lastAdmission && preg_match('/\/(\d+)$/', $lastAdmission->file_number, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }
        
        return $prefix . $nextNumber;
    }
}
