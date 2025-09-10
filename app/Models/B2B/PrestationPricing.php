<?php

namespace App\Models\B2B;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

use App\Models\B2B\Annex;
use App\Models\B2B\Avenant;
use App\Models\CONFIGURATION\Prestation; // Assuming this is your Prestation model

class PrestationPricing extends Model
{
    use HasFactory;

    protected $table = 'prestation_pricing';

    protected $fillable = [
        'prestation_id', // This is the correct one, used to link to the Prestation model
        'annex_id',
        'avenant_id',
        'updated_by_id',
        'service_id', // Assuming this is the service linked to the prestation
        'head',
        'activate_at',
        'prix',          // Global Price
        'company_price', // Company Part
        'patient_price', // Patient Part
        'tva',           // Tax value
        'max_price_exceeded',
        'original_company_share',
        'original_patient_share',
        'subname'
    ];

    protected $casts = [
        'prix' => 'decimal:2',
        'company_price' => 'decimal:2',
        'patient_price' => 'decimal:2',
        'tva' => 'decimal:2',
        'max_price_exceeded' => 'boolean',
        'original_company_share' => 'decimal:2',
        'original_patient_share' => 'decimal:2',
        'activate_at' => 'datetime',
    ];

    /**
     * Get the prestation that owns the PrestationPricing.
     * This links via 'prestation_id' to the 'id' of the Prestation model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function prestation(): BelongsTo
    {
        return $this->belongsTo(Prestation::class, 'prestation_id');
    }

    /**
     * Get the annex that owns the PrestationPricing.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function annex(): BelongsTo
    {
        return $this->belongsTo(Annex::class);
    }

    /**
     * Get the avenant that owns the PrestationPricing.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function avenant(): BelongsTo
    {
        return $this->belongsTo(Avenant::class, 'avenant_id');
    }
        public function prestationList(): BelongsTo
    {
        return $this->belongsTo(Prestation::class, 'prestation_id');
    }

    /**
     * Get the next version of this prestation price.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updatedByPrestationPrice(): BelongsTo
    {
        return $this->belongsTo(PrestationPricing::class, 'updated_by_id');
    }

    /**
     * Get the previous version that this prestation price updated.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function updatesPrestationPrice(): HasOne
    {
        return $this->hasOne(PrestationPricing::class, 'updated_by_id');
    }
}