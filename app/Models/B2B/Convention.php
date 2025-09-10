<?php

namespace App\Models\B2B;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\B2B\ConventionDetail;
use App\Models\CONFIGURATION\Prestation;

use App\Models\CRM\Organisme;




class Convention extends Model
{
    protected $fillable = [
        'organisme_id',
        'name',
        'status',
        'created_at',
        'updated_at',
        'activation_at'
    ];

    public function conventionDetail(): HasOne
    {
        return $this->hasOne(ConventionDetail::class);
    }
    public function organisme(): BelongsTo
    {
        return $this->belongsTo(Organisme::class, 'organisme_id'); // Assuming Organisme is the model and 'organisme_id' is the foreign key
    }
      public function avenants(): HasMany
    {
        return $this->hasMany(Avenant::class, 'convention_id'); // Changed to convention_id
    }


    public function annexes(): HasMany
    {
        return $this->hasMany(Annex::class, 'convention_id'); // Changed to convention_id
    }

    // public function agreementDetails(): HasMany
    // {
    //     return $this->hasMany(ConventionDetail::class, 'convention_id'); // Changed to convention_id
    // }

}
