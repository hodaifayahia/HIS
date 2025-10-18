<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyStorageTool extends Model
{
    protected $table = 'pharmacy_stockage_tools';

    protected $fillable = [
        'pharmacy_storage_id',
        'tool_type',
        'tool_number',
        'block',
        'shelf_level',
        'location_code',
        'temperature_zone',
        'humidity_zone',
        'light_protection_level',
        'access_restriction',
        'controlled_substance_compatible',
        'refrigeration_capable',
        'security_features',
        'capacity_units',
        'current_occupancy',
        'last_calibration_date',
        'next_calibration_due',
        'maintenance_schedule',
        'compliance_status',
        'dea_approved',
    ];

    protected $casts = [
        'tool_number' => 'integer',
        'shelf_level' => 'integer',
        'capacity_units' => 'integer',
        'current_occupancy' => 'integer',
        'controlled_substance_compatible' => 'boolean',
        'refrigeration_capable' => 'boolean',
        'dea_approved' => 'boolean',
        'last_calibration_date' => 'date',
        'next_calibration_due' => 'date',
        'security_features' => 'array',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tool) {
            $tool->location_code = $tool->generateLocationCode();
        });

        static::updating(function ($tool) {
            // Only regenerate if relevant fields changed
            if ($tool->isDirty(['tool_type', 'tool_number', 'block', 'shelf_level']) ||
                $tool->pharmacyStorage->isDirty(['location_code']) ||
                $tool->pharmacyStorage->service->isDirty(['service_abv'])) {
                $tool->location_code = $tool->generateLocationCode();
            }
        });
    }

    /**
     * Get the pharmacy storage that owns this tool.
     */
    public function pharmacyStorage(): BelongsTo
    {
        return $this->belongsTo(PharmacyStorage::class);
    }

    /**
     * Get the tool type label.
     */
    public function getToolTypeLabelAttribute(): string
    {
        return match ($this->tool_type) {
            'RY' => 'Rayonnage (Shelving)',
            'AR' => 'Armoire (Cabinet)',
            'CF' => 'Coffre (Safe)',
            'FR' => 'Frigo (Refrigerator)',
            'CS' => 'Caisson (Drawer Unit)',
            'CH' => 'Chariot (Cart)',
            'PL' => 'Palette (Pallet)',
            'CV' => 'Controlled Vault',
            'NF' => 'Narcotic Safe',
            'FC' => 'Freezer Cabinet',
            'CC' => 'Climate Chamber',
            'UV' => 'UV Protection Cabinet',
            'BS' => 'Biosafety Cabinet',
            'AC' => 'Automated Cabinet',
            default => $this->tool_type
        };
    }

    /**
     * Get the block label (only for Rayonnage).
     */
    public function getBlockLabelAttribute(): ?string
    {
        if ($this->tool_type !== 'RY' || ! $this->block) {
            return null;
        }

        return $this->block;
    }

    /**
     * Check if this tool type requires block and shelf level.
     */
    public function requiresBlockAndShelf(): bool
    {
        return in_array($this->tool_type, ['RY', 'AR', 'CS']);
    }

    /**
     * Generate location code based on the format rules.
     */
    public function generateLocationCode(): string
    {
        $serviceAbv = $this->pharmacyStorage->service->service_abv;
        $storageLocationCode = $this->pharmacyStorage->location_code;

        $base = $serviceAbv.$storageLocationCode.'-'.$this->tool_type.$this->tool_number;

        if ($this->requiresBlockAndShelf() && $this->block && $this->shelf_level) {
            $base .= '-'.$this->block.$this->shelf_level;
        }

        // Add temperature zone if applicable
        if ($this->temperature_zone) {
            $base .= '-T'.$this->temperature_zone;
        }

        return $base;
    }

    // Pharmacy-specific methods
    public function getTemperatureZoneLabelAttribute(): string
    {
        return match ($this->temperature_zone) {
            'RT' => 'Room Temperature (15-25°C)',
            'CRT' => 'Controlled Room Temperature (20-25°C)',
            'REF' => 'Refrigerated (2-8°C)',
            'FRZ' => 'Frozen (-20°C)',
            'ULF' => 'Ultra-Low Freezer (-80°C)',
            default => $this->temperature_zone ?? 'Ambient'
        };
    }

    public function getHumidityZoneLabelAttribute(): string
    {
        return match ($this->humidity_zone) {
            'LOW' => 'Low Humidity (<30%)',
            'STD' => 'Standard Humidity (30-60%)',
            'HIGH' => 'High Humidity (>60%)',
            'CTL' => 'Controlled Humidity',
            default => $this->humidity_zone ?? 'Standard'
        };
    }

    public function getLightProtectionLevelLabelAttribute(): string
    {
        return match ($this->light_protection_level) {
            'none' => 'No Light Protection',
            'minimal' => 'Minimal Light Protection',
            'standard' => 'Standard Light Protection',
            'high' => 'High Light Protection',
            'complete' => 'Complete Light Protection',
            'uv_filtered' => 'UV Filtered',
            default => $this->light_protection_level ?? 'Standard'
        };
    }

    public function getAccessRestrictionLabelAttribute(): string
    {
        return match ($this->access_restriction) {
            'open' => 'Open Access',
            'staff' => 'Staff Access Only',
            'pharmacist' => 'Pharmacist Access Only',
            'authorized' => 'Authorized Personnel Only',
            'dual_control' => 'Dual Control Required',
            'time_locked' => 'Time-Locked Access',
            'biometric' => 'Biometric Access Required',
            default => $this->access_restriction ?? 'Standard Access'
        };
    }

    public function isControlledSubstanceCompatible(): bool
    {
        return $this->controlled_substance_compatible === true;
    }

    public function isRefrigerationCapable(): bool
    {
        return $this->refrigeration_capable === true;
    }

    public function isDEAApproved(): bool
    {
        return $this->dea_approved === true;
    }

    public function getOccupancyPercentageAttribute(): float
    {
        if (! $this->capacity_units || $this->capacity_units == 0) {
            return 0;
        }

        return ($this->current_occupancy / $this->capacity_units) * 100;
    }

    public function isNearCapacity($threshold = 90): bool
    {
        return $this->occupancy_percentage >= $threshold;
    }

    public function isCalibrationDue(): bool
    {
        if (! $this->next_calibration_due) {
            return false;
        }

        return $this->next_calibration_due->isPast();
    }

    public function isCalibrationOverdue($days = 30): bool
    {
        if (! $this->next_calibration_due) {
            return false;
        }

        return $this->next_calibration_due->addDays($days)->isPast();
    }

    public function getComplianceStatusLabelAttribute(): string
    {
        return match ($this->compliance_status) {
            'compliant' => 'Compliant',
            'calibration_due' => 'Calibration Due',
            'maintenance_required' => 'Maintenance Required',
            'non_compliant' => 'Non-Compliant',
            'out_of_service' => 'Out of Service',
            default => $this->compliance_status ?? 'Unknown'
        };
    }

    public function hasSecurityFeature($feature): bool
    {
        return in_array($feature, $this->security_features ?? []);
    }

    public function getSecurityFeaturesListAttribute(): string
    {
        if (! $this->security_features) {
            return 'No special security features';
        }

        $features = [
            'electronic_lock' => 'Electronic Lock',
            'biometric_access' => 'Biometric Access',
            'dual_key' => 'Dual Key System',
            'time_delay' => 'Time Delay Lock',
            'audit_trail' => 'Access Audit Trail',
            'tamper_detection' => 'Tamper Detection',
            'alarm_integration' => 'Alarm System Integration',
            'video_monitoring' => 'Video Monitoring',
        ];

        $labels = [];
        foreach ($this->security_features as $feature) {
            $labels[] = $features[$feature] ?? $feature;
        }

        return implode(', ', $labels);
    }

    public function requiresSpecialHandling(): bool
    {
        return $this->isControlledSubstanceCompatible() ||
               $this->isRefrigerationCapable() ||
               in_array($this->tool_type, ['CV', 'NF', 'FC', 'CC', 'UV', 'BS']);
    }

    // Scopes
    public function scopeControlledSubstanceCompatible($query)
    {
        return $query->where('controlled_substance_compatible', true);
    }

    public function scopeRefrigerationCapable($query)
    {
        return $query->where('refrigeration_capable', true);
    }

    public function scopeDEAApproved($query)
    {
        return $query->where('dea_approved', true);
    }

    public function scopeCalibrationDue($query)
    {
        return $query->where('next_calibration_due', '<=', now());
    }

    public function scopeCalibrationOverdue($query, $days = 30)
    {
        return $query->where('next_calibration_due', '<=', now()->subDays($days));
    }

    public function scopeNearCapacity($query, $threshold = 90)
    {
        return $query->whereRaw('(current_occupancy / capacity_units * 100) >= ?', [$threshold]);
    }

    public function scopeByToolType($query, $type)
    {
        return $query->where('tool_type', $type);
    }

    public function scopeByTemperatureZone($query, $zone)
    {
        return $query->where('temperature_zone', $zone);
    }

    public function scopeByAccessRestriction($query, $restriction)
    {
        return $query->where('access_restriction', $restriction);
    }
}
