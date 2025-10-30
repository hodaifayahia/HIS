<?php

namespace App\Models\CONFIGURATION;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Doctor;
use App\Models\User;

/**
 * Class PrestationPackageReception
 * 
 * Junction table to store doctor assignments for prestations within a package
 * during reception/appointment processing
 */
class PrestationPackageReception extends Model
{
    use HasFactory;

    protected $table = 'prestation_package_reception';

    protected $fillable = [
        'package_id',
        'prestation_id',
        'doctor_id',
    ];

    /**
     * Get the package associated with this record
     */
    public function package()
    {
        return $this->belongsTo(PrestationPackage::class, 'package_id');
    }

    /**
     * Get the prestation associated with this record
     */
    public function prestation()
    {
        return $this->belongsTo(Prestation::class, 'prestation_id');
    }

    /**
     * Get the doctor assigned to this prestation in the package
     */
    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    /**
     * Scope to get all prestations for a specific package
     */
    public function scopeForPackage($query, $packageId)
    {
        return $query->where('package_id', $packageId);
    }

    /**
     * Scope to get all prestations for a specific package with a specific doctor
     */
    public function scopeForPackageAndDoctor($query, $packageId, $doctorId)
    {
        return $query->where('package_id', $packageId)
                     ->where('doctor_id', $doctorId);
    }

    /**
     * Check if a prestation already exists in a package with a doctor
     */
    public static function exists($packageId, $prestationId, $doctorId = null)
    {
        $query = static::where('package_id', $packageId)
                      ->where('prestation_id', $prestationId);

        if ($doctorId !== null) {
            $query->where('doctor_id', $doctorId);
        }

        return $query->exists();
    }
}
