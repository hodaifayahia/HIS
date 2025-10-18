<?php

namespace App\Models\B2B;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

use App\Models\B2B\Annex;
use App\Models\B2B\Avenant;
use App\Models\CONFIGURATION\Prestation; // Assuming this is your Prestation model
use App\Models\ContractPercentage;

class PrestationPricing extends Model
{
    use HasFactory;

    protected $table = 'prestation_pricing';

    protected $fillable = [
        'prestation_id', // This is the correct one, used to link to the Prestation model
        'annex_id',
        'avenant_id',
        'contract_percentage_id', // New field
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

    /**
     * Get the contract percentage that owns the PrestationPricing.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function contractPercentage(): BelongsTo
    {
        return $this->belongsTo(ContractPercentage::class, 'contract_percentage_id');
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

    /**
     * Calculate the convention price with VAT (TTC - Toutes Taxes Comprises)
     * Uses prix (convention_price) if available, otherwise falls back to public_price
     *
     * @return float
     */
    public function getPriceWithVatAttribute()
    {
        // Ensure numeric safety: treat nulls as 0 and cast to float
        $conventionPrice = is_null($this->prix) ? 0.0 : (float) $this->prix;
        
        // If convention price (prix) is 0 or null, fall back to public_price from the related prestation
        if ($conventionPrice <= 0 && isset($this->prestation)) {
            $conventionPrice = is_null($this->prestation->public_price ?? null) ? 0.0 : (float) $this->prestation->public_price;
        }
        
        $consumables = is_null($this->prestation->consumables_cost ?? null) ? 0.0 : (float) $this->prestation->consumables_cost;

        // General VAT comes from this pricing record's 'tva' field.
        $vat = is_null($this->tva) ? 0.0 : (float) $this->tva;

        // If the prestation defines a separate VAT for consumables, apply that to consumables
        $consumablesVat = is_null($this->prestation->tva_const_prestation ?? null) ? null : (float) $this->prestation->tva_const_prestation;

        if ($consumablesVat !== null && $consumablesVat > 0) {
            // Apply general VAT to convention/public price and separate VAT to consumables
            $ttcBase = $conventionPrice * (1 + $vat / 100);
            $ttcConsumables = $consumables * (1 + $consumablesVat / 100);
            $ttc = $ttcBase + $ttcConsumables;
        } else {
            // Default: same VAT applied to base + consumables
            $base = $conventionPrice + $consumables;
            $ttc = $base * (1 + $vat / 100);
        }

        // Round to 2 decimals to keep consistent formatting and return float
        return round($ttc, 2);
    }

    /**
     * Calculate price with VAT using convention price (prix) or public_price fallback,
     * but allow applying a different VAT rate to consumables when the prestation
     * defines `tva_const_prestation`.
     *
     * Returns the TTC total when consumables VAT is applied separately.
     *
     * @return float
     */
    public function getPriceWithConsumablesVatAttribute()
    {
        // Determine base price (convention prix or prestation public_price)
        $conventionPrice = is_null($this->prix) ? 0.0 : (float) $this->prix;
        if ($conventionPrice <= 0 && isset($this->prestation)) {
            $conventionPrice = is_null($this->prestation->public_price ?? null) ? 0.0 : (float) $this->prestation->public_price;
        }

        // Consumables
        $consumables = is_null($this->prestation->consumables_cost ?? null) ? 0.0 : (float) $this->prestation->consumables_cost;

        // VATs
        $vat = is_null($this->tva) ? 0.0 : (float) $this->tva; // general VAT for base

        // If prestation defines a separate VAT for consumables, use it
        $consumablesVat = is_null($this->prestation->tva_const_prestation ?? null) ? null : (float) $this->prestation->tva_const_prestation;

        if ($consumablesVat === null || $consumablesVat === 0.0) {
            // no separate consumables VAT -> same as normal calculation
            $base = $conventionPrice + $consumables;
            return round($base * (1 + $vat / 100), 2);
        }

        // Apply general VAT to the base price (without consumables), and consumablesVat to consumables
        $ttcBase = $conventionPrice * (1 + $vat / 100);
        $ttcConsumables = $consumables * (1 + $consumablesVat / 100);

        return round($ttcBase + $ttcConsumables, 2);
    }
}