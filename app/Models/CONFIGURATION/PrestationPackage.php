<?php

namespace App\Models\CONFIGURATION;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use App\Models\CONFIGURATION\PrestationPackageitem;

/**
 * Class PrestationPackage
 *
 * Represents a package of prestations with associated items.
 */

class PrestationPackage extends Model
{
    
    protected $table = 'prestation_packages'; // Specify the table name if different
    protected $fillable = [
        'name',
        'description',
        'price',
        'is_active',
        'created_by',
        'updated_by',
    ];

      public function items(): HasMany
    {
        return $this->hasMany(PrestationPackageitem::class, 'prestation_package_id');
    }
  

    /**
     * Get prestations through package items
     */
    public function prestations()
    {
        return $this->hasManyThrough(
            Prestation::class,
            PrestationPackageitem::class,
            'prestation_package_id', // Foreign key on PrestationPackageitem table
            'id', // Foreign key on Prestation table
            'id', // Local key on PrestationPackage table
            'prestation_id' // Local key on PrestationPackageitem table
        );
    }

    /**
     * Get the user that created the prestation package.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user that updated the prestation package.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope a query to only include active prestation packages.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include inactive prestation packages.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Get the formatted price of the prestation package.
     */
    public function getFormattedPriceAttribute()
    {
        return number_format($this->price, 2, '.', '');
    }

    /**
     * Get the description of the prestation package.
     */
    public function getDescriptionAttribute($value)
    {
        return $value ?: 'No description available.';
    }
}
