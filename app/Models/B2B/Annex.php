<?php

namespace App\Models\B2B;

use Illuminate\Database\Eloquent\Factories\HasFactory; // Often useful for testing
use Illuminate\Database\Eloquent\Model;
use App\Models\B2B\Convention; // Assuming your Convention model is in App\Models
use App\Models\CONFIGURATION\Service;  // Assuming your Specialty model is in App\Models
use App\Models\User;      // Assuming your User model is in App\Models
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\B2B\PrestationPricing; // Assuming your PrestationPricing model

class Annex extends Model
{
    // If your table name is 'annexes', this line is optional.
    // protected $table = 'annexes';

    protected $fillable = [
        'annex_name',
        'convention_id',
        'service_id',
        'description',
        'is_active',
        'min_price',
        'prestation_prix_status', // Added this field
        'created_by',
        'updated_by',
    ];

    public function convention()
    {
        return $this->belongsTo(Convention::class, 'convention_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
 public function prestationPrices(): HasMany
{
    return $this->hasMany(PrestationPricing::class, 'annex_id');
}
    // You might also want to add accessors for displaying names directly
    // public function getCreatedByNameAttribute()
    // {
    //      return $this->creator ? $this->creator->name : 'N/A';
    // }

    // public function getSpecialtyNameAttribute()
    // {
    //      return $this->specialty ? $this->specialty->specialty_name : 'N/A';
    // }
}