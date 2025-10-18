<?php

namespace App\Models\Reception;

use App\Models\B2B\Convention;
use App\Models\CONFIGURATION\Prestation;
use App\Models\CONFIGURATION\PrestationPackageitem;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ficheNavetteItem extends Model
{
    protected $table = 'fiche_navette_items';

    protected $fillable = [
        'fiche_navette_id',
        'prestation_id', // Can be null for packages
        'status',
        'base_price',
        'final_price',
        'patient_share',
        'organisme_share',
        'primary_clinician_id',
        'assistant_clinician_id',
        'technician_id',
        'modality_id',
        'convention_id',
        'doctor_id',
        'patient_id',
        'uploaded_file',
        'family_authorization',
        'prise_en_charge_date',
        'package_id',
        'remise_id',
        'insured_id',
        'remaining_amount',
        'paid_amount',
        'payment_status',
        'payment_method',
        'default_payment_type',
        'is_nursing_consumption',
    ];

    protected $casts = [
        'family_authorization' => 'array',
        // Ensure uploaded files are stored/returned as JSON arrays
        'uploaded_file' => 'array',
        'prise_en_charge_date' => 'date',
        'is_nursing_consumption' => 'boolean',
    ];

    /**
     * Get the prestation associated with this item (null for packages)
     */
    public function prestation()
    {
        return $this->belongsTo(Prestation::class);
    }

    /**
     * Get the package associated with this item (null for individual prestations)
     */
    public function package()
    {
        return $this->belongsTo(\App\Models\CONFIGURATION\PrestationPackage::class, 'package_id');
    }

    /**
     * Get prestations if this is a package item
     */
    public function packagePrestations()
    {
        return $this->hasMany(PrestationPackageitem::class, 'prestation_package_id', 'package_id')
            ->with('prestation');
    }

    /**
     * Get the convention associated with this item
     */
    public function convention()
    {
        return $this->belongsTo(Convention::class);
    }

    /**
     * Get the doctor assigned to this item
     */
    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Get the insured patient
     */
    public function insuredPatient()
    {
        return $this->belongsTo(Patient::class, 'insured_id');
    }

    /**
     * Get the fiche navette this item belongs to
     */
    public function ficheNavette()
    {
        return $this->belongsTo(ficheNavette::class);
    }

    /**
     * Get dependencies for this item
     */
    public function dependencies()
    {
        return $this->hasMany(ItemDependency::class, 'parent_item_id');
    }

    public function nursingConsumptions()
    {
        return $this->hasMany(\App\Models\Nursing\PatientConsumption::class, 'fiche_navette_item_id');
    }

    /**
     * Check if this item is a package
     */
    public function isPackage(): bool
    {
        return ! is_null($this->package_id) && is_null($this->prestation_id);
    }

    /**
     * Check if this item is an individual prestation
     */
    public function isPrestation(): bool
    {
        return ! is_null($this->prestation_id) && is_null($this->package_id);
    }

    public function appointment()
    {
        return $this->hasMany(\App\Models\Appointment::class);
    }
}
