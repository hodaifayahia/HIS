<?php

namespace App\Models\Reception;

use Illuminate\Database\Eloquent\Model;
use App\Models\CONFIGURATION\Prestation;
use App\Models\CONFIGURATION\PrestationPackage;
use App\Models\CONFIGURATION\PrestationPackageitem;
use App\Models\B2B\Convention;
use App\Models\User;
use App\Models\Patient;

class ficheNavetteItem extends Model
{
    protected $fillable = [
        'fiche_navette_id',
        'prestation_id', // Can be null for packages
        'package_id', // For package items
        'convention_id',
        'insured_id',
        'doctor_id',
        'status',
        'base_price',
        'custom_name',
        'discounted_price',
        'final_price',
        'patient_share',
        'prise_en_charge_date',
        'family_authorization',
        'uploaded_file',
        'notes',
    ];

    protected $casts = [
        'family_authorization' => 'array',
        'uploaded_file' => 'array',
        'prise_en_charge_date' => 'date',
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

    /**
     * Check if this item is a package
     */
    public function isPackage(): bool
    {
        return !is_null($this->package_id) && is_null($this->prestation_id);
    }

    /**
     * Check if this item is an individual prestation
     */
    public function isPrestation(): bool
    {
        return !is_null($this->prestation_id) && is_null($this->package_id);
    }
}
