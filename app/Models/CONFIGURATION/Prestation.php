<?php

namespace App\Models\CONFIGURATION;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Specialization;

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
        'night_tariff' => 'decimal:2',
        'consumables_cost' => 'decimal:2',
        'min_versement_amount' => 'decimal:2',
        'primary_doctor_share' => 'string',
        'assistant_doctor_share' => 'string',
        'technician_share' => 'string',
        'clinic_share' => 'string',
    ];

    // Relationships
    public function service(): BelongsTo 
    {
        return $this->belongsTo(Service::class);
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
        return $this->public_price * (1 + $this->vat_rate / 100);
    }

    public function getFormattedDurationAttribute()
    {
        if (!$this->default_duration_minutes) return null;

        $hours = floor($this->default_duration_minutes / 60);
        $minutes = $this->default_duration_minutes % 60;

        return $hours > 0 ? "{$hours}h {$minutes}min" : "{$minutes}min";
    }

    public function getFormattedIdAttribute(): string
    {
        return $this->internal_code ?: $this->billing_code;
    }
}
