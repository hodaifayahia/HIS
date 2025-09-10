<?php

namespace App\Models\CONFIGURATION;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
//import App\Models\Prestation;
use App\Models\CONFIGURATION\Prestation;
//import App\Models\PrestationPackage;
use App\Models\CONFIGURATION\PrestationPackage;
//import App\Models\PrestationPackageitem;
use App\Models\CONFIGURATION\PrestationPackageitem;



class PrestationPackageitem extends Model
{
    
    protected $fillable = [
        'prestation_package_id',
        'prestation_id',
        'created_by',
        'updated_by',
    ];
    /**
     * Get the prestation package that owns the item.
     */
    public function prestationPackage()
    {
        return $this->belongsTo(PrestationPackage::class);
    }

    /**
     * Get the prestation associated with the item.
     */
    public function prestation()
    {
        return $this->belongsTo(Prestation::class);
    }

    /**
     * Get the user that created the item.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user that updated the item.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope a query to only include items of a specific prestation package.
     */
    public function scopeOfPackage($query, $packageId)
    {
        return $query->where('prestation_package_id', $packageId);
    }

    /**
     * Scope a query to only include items of a specific prestation.
     */
    public function scopeOfPrestation($query, $prestationId)
    {
        return $query->where('prestation_id', $prestationId);
    }
}
