<?php

namespace App\Models\CONFIGURATION;

use App\Models\B2B\PrestationPricing;
use App\Models\Specialization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prestation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'internal_code',
        'billing_code',
        'description',
        'service_id',
        'specialization_id',
        'type',
        'public_price',
    'convenience_prix',
    // VAT specifically applied to consumables when different from general vat_rate
    'tva_const_prestation',
        'vat_rate',
        'night_tariff',
        'consumables_cost',
        'is_social_security_reimbursable',
        'reimbursement_conditions',
        'non_applicable_discount_rules',
        'fee_distribution_model',
        'primary_doctor_share',
        'primary_doctor_is_percentage',
        'assistant_doctor_share',
        'assistant_doctor_is_percentage',
        'technician_share',
        'technician_is_percentage',
        'clinic_share',
        'clinic_is_percentage',
        'default_payment_type',
        'min_versement_amount',
        'need_an_appointment',
        'requires_hospitalization',
        'default_hosp_nights',
        'required_modality_type_id',
        'default_duration_minutes',
        'required_prestations_info',
        'patient_instructions',
        'required_consents',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_social_security_reimbursable' => 'boolean',
        'requires_hospitalization' => 'boolean',
        'primary_doctor_is_percentage' => 'boolean',
        'assistant_doctor_is_percentage' => 'boolean',
        'technician_is_percentage' => 'boolean',
        'clinic_is_percentage' => 'boolean',
        'non_applicable_discount_rules' => 'array',
        'required_prestations_info' => 'array',
        'required_consents' => 'array',
        'need_an_appointment' => 'boolean',
        'public_price' => 'decimal:2',
        'vat_rate' => 'decimal:2',
    'tva_const_prestation' => 'decimal:2',
        'night_tariff' => 'decimal:2',
        'consumables_cost' => 'decimal:2',
        'min_versement_amount' => 'decimal:2',
        'primary_doctor_share' => 'string',
        'assistant_doctor_share' => 'string',
        'technician_share' => 'string',
        'clinic_share' => 'string',
    ];
        protected $appends = [
            'price_with_vat',
            'price_with_vat_and_consumables_variant',
            'price_with_vat',
            'formatted_duration',
            // append the computed attribute so it is available as $prestation->min_versement_amount
            'min_versement_amount',
            'formatted_id',
            'calculated_public_price'
        ];

    // Relationships
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
    // min_versement_amount with tva

    public function getMinVersementAmountAttribute()
    {
        // Read raw attributes to avoid accidental recursion into accessors
        $rawAttrs = $this->getAttributes();

        $minVersementRaw = $rawAttrs['min_versement_amount'] ?? null;
        $vatRateRaw = $rawAttrs['vat_rate'] ?? null;

        // Normalize values (null or empty -> null for minVersement, 0.0 for vat)
        $minVersement = ($minVersementRaw === null || $minVersementRaw === '') ? null : (float) $minVersementRaw;
        $vatRate = ($vatRateRaw === null || $vatRateRaw === '') ? 0.0 : (float) $vatRateRaw;

        // If a configured min versement exists, compute with VAT and return
        if (! is_null($minVersement) && $minVersement > 0) {
            return round($minVersement * (1 + $vatRate / 100), 2);
        }

        // Fallback 1: use computed price_with_vat attribute if available
        // Use getAttributes() / getAttribute() carefully to avoid recursion
        try {
            $priceWithVat = $this->getAttribute('price_with_vat');
        } catch (\Throwable $e) {
            $priceWithVat = null;
        }

        if (! is_null($priceWithVat) && $priceWithVat > 0) {
            return (float) round($priceWithVat, 2);
        }

        // Fallback 2: use public_price if set
        $public = $this->getAttribute('public_price') ?? 0.0;
        return (float) round((float) $public, 2);
    }
    public function specialization(): BelongsTo
    {
        return $this->belongsTo(Specialization::class, 'specialization_id');
    }

    public function modalityType(): BelongsTo
    {
        return $this->belongsTo(ModalityType::class, 'required_modality_type_id');
    }

    public function prestationPrices(): HasMany
    {
        return $this->hasMany(PrestationPricing::class, 'prestation_id');
    }

    /**
     * Many-to-Many relationship: Prestation belongs to many packages
     * Assuming there's a pivot table like 'package_prestation' or 'prestation_package'
     */
    public function packages(): BelongsTo
    {
        return $this->belongsTo(PrestationPackage::class, 'package_id');
    }

    // Accessors
    public function getPriceWithVatAttribute()
    {
        // Ensure numeric safety: treat nulls as 0 and cast to float
        $public = is_null($this->public_price) ? 0.0 : (float) $this->public_price;
        $consumables = is_null($this->consumables_cost) ? 0.0 : (float) $this->consumables_cost;
        $vat = is_null($this->vat_rate) ? 0.0 : (float) $this->vat_rate;

        // Apply VAT. If a separate consumables VAT is defined, apply it only to consumables
        $tvaConsumables = is_null($this->tva_const_prestation) ? null : (float) $this->tva_const_prestation;

        if ($tvaConsumables !== null && $tvaConsumables > 0) {
            // General VAT to public price, consumables VAT to consumables
            $ttcPublic = $public * (1 + $vat / 100);
            $ttcConsumables = $consumables * (1 + $tvaConsumables / 100);
            $ttc = $ttcPublic + $ttcConsumables;
        } else {
            // Default: same VAT applied to public + consumables
            $base = $public + $consumables;
            $ttc = $base * (1 + $vat / 100);
        }

        // Round to 2 decimals to keep consistent formatting and return float
        return round($ttc, 2);
    }

    /**
     * Calculate price with VAT but allow a separate VAT rate for consumables.
     * This returns an array with two values: total TTC and TTC calculated when
     * consumables use their own VAT rate (tva_const_prestation) if set.
     *
     * @return array{ttc: float, ttc_with_consumables_vat: float}
     */
  public function getPriceWithVatAndConsumablesVariantAttribute()
{
    $public = is_null($this->public_price) ? 0.0 : (float) $this->public_price;
    $consumables = is_null($this->consumables_cost) ? 0.0 : (float) $this->consumables_cost;
    $vat = is_null($this->vat_rate) ? 0.0 : (float) $this->vat_rate;

    $base = $public + $consumables;
    $ttc = round($base * (1 + $vat / 100), 2);

    $tvaConsumables = is_null($this->tva_const_prestation) ? null : (float) $this->tva_const_prestation;

    // Debug: Log values to check if they're correct
    \Log::debug('Price calculation', [
        'public' => $public,
        'consumables' => $consumables,
        'vat' => $vat,
        'tva_consumables' => $tvaConsumables
    ]);

    if ($tvaConsumables === null || $tvaConsumables === 0.0) {
        return ['ttc' => $ttc, 'ttc_with_consumables_vat' => $ttc];
    }

    $baseExConsumables = $public;
    $ttcBase = $baseExConsumables * (1 + $vat / 100);
    $ttcConsumables = $consumables * (1 + $tvaConsumables / 100);

    $combined = round($ttcBase + $ttcConsumables, 2);

    // Always return an array with both keys to keep the accessor shape consistent
    return ['ttc' => $ttc, 'ttc_with_consumables_vat' => $combined];
}

public function getPriceWithVat()
{
    $public = is_null($this->public_price) ? 0.0 : (float) $this->public_price;
    $consumables = is_null($this->consumables_cost) ? 0.0 : (float) $this->consumables_cost;
    $vat = is_null($this->vat_rate) ? 0.0 : (float) $this->vat_rate;

    $base = $public + $consumables;
    $ttc = round($base * (1 + $vat / 100), 2);

    $tvaConsumables = is_null($this->tva_const_prestation) ? null : (float) $this->tva_const_prestation;

    // Debug: Log values to check if they're correct
    \Log::debug('Price calculation', [
        'public' => $public,
        'consumables' => $consumables,
        'vat' => $vat,
        'tva_consumables' => $tvaConsumables
    ]);

    if ($tvaConsumables === null || $tvaConsumables === 0.0) {
        return ['ttc' => $ttc, 'ttc_with_consumables_vat' => $ttc];
    }

    $baseExConsumables = $public;
    $ttcBase = $baseExConsumables * (1 + $vat / 100);
    $ttcConsumables = $consumables * (1 + $tvaConsumables / 100);

    $combined = round($ttcBase + $ttcConsumables, 2);

    // Always return an array with both keys to keep the accessor shape consistent
    return $combined;
}


    public function getFormattedDurationAttribute()
    {
        if (! $this->default_duration_minutes) {
            return null;
        }

        $hours = floor($this->default_duration_minutes / 60);
        $minutes = $this->default_duration_minutes % 60;

        return $hours > 0 ? "{$hours}h {$minutes}min" : "{$minutes}min";
    }

    public function getFormattedIdAttribute(): string
    {
        return $this->internal_code ?: $this->billing_code;
    }

    /**
     * Retrieve the effective public price for the prestation.
     */
    public function getPublicPrice(): float
    {
        if (! is_null($this->public_price)) {
            return (float) $this->public_price;
        }

        $latestPricing = $this->prestationPrices()
            ->orderByDesc('activate_at')
            ->orderByDesc('created_at')
            ->first();

        if ($latestPricing) {
            return (float) ($latestPricing->prix ?? $latestPricing->patient_price ?? 0);
        }

        return 0.0;
    }

    public function getCalculatedPublicPriceAttribute(): float
    {
        return $this->getPublicPrice();
    }

    /**
     * Accessor for required_prestations_info to ensure it's always an array
     */
    public function getRequiredPrestationsInfoAttribute($value)
    {
        if (is_string($value)) {
            // If it's a string like "[1,3]", try to decode it
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }

            // If JSON decode fails, return empty array
            return [];
        }

        return $value ?? [];
    }

    /**
     * Mutator for required_prestations_info to ensure it's stored as JSON
     */
    public function setRequiredPrestationsInfoAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['required_prestations_info'] = json_encode($value);
        } elseif (is_string($value)) {
            // If it's already a string, store it as-is (assuming it's valid JSON)
            $this->attributes['required_prestations_info'] = $value;
        } else {
            $this->attributes['required_prestations_info'] = json_encode([]);
        }
    }
}
