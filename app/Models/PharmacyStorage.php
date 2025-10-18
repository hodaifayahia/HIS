<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PharmacyStorage extends Model
{
    protected $fillable = [
        'name',
        'description',
        'location',
        'capacity',
        'type',
        'status',
        'service_id',
        'temperature_controlled',
        'security_level',
        'location_code',
        'warehouse_type',
        'controlled_substance_vault',
        'refrigeration_unit',
        'humidity_controlled',
        'light_protection',
        'access_control_level',
        'pharmacist_access_only',
        'dea_registration_required',
        'temperature_min',
        'temperature_max',
        'humidity_min',
        'humidity_max',
        'monitoring_system',
        'backup_power',
        'alarm_system',
        'compliance_certification',
        'last_inspection_date',
        'next_inspection_due',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'temperature_controlled' => 'boolean',
        'service_id' => 'integer',
        'controlled_substance_vault' => 'boolean',
        'refrigeration_unit' => 'boolean',
        'humidity_controlled' => 'boolean',
        'light_protection' => 'boolean',
        'pharmacist_access_only' => 'boolean',
        'dea_registration_required' => 'boolean',
        'temperature_min' => 'decimal:1',
        'temperature_max' => 'decimal:1',
        'humidity_min' => 'decimal:1',
        'humidity_max' => 'decimal:1',
        'backup_power' => 'boolean',
        'alarm_system' => 'boolean',
        'last_inspection_date' => 'date',
        'next_inspection_due' => 'date',
    ];

    /**
     * Get the service associated with this pharmacy storage
     */
    public function service()
    {
        return $this->belongsTo(\App\Models\CONFIGURATION\Service::class, 'service_id');
    }

    /**
     * Get the pharmacy storage tools for this storage
     */
    public function pharmacyStorageTools()
    {
        return $this->hasMany(PharmacyStorageTool::class);
    }

    /**
     * Get the pharmacy stockages for this storage
     */
    public function stockages()
    {
        return $this->hasMany(PharmacyStockage::class, 'pharmacy_storage_id');
    }

    /**
     * Get the inventories for this pharmacy storage
     */
    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'stockage_id');
    }

    /**
     * Get the products in this pharmacy storage
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'inventories', 'stockage_id', 'product_id');
    }

    // Pharmacy-specific methods
    public function getStorageTypeLabelAttribute(): string
    {
        return match($this->type) {
            'general_pharmacy' => 'General Pharmacy Storage',
            'controlled_substances' => 'Controlled Substances Vault',
            'refrigerated' => 'Refrigerated Storage',
            'frozen' => 'Frozen Storage',
            'hazardous' => 'Hazardous Materials Storage',
            'compounding' => 'Compounding Storage',
            'bulk_storage' => 'Bulk Storage',
            'quarantine' => 'Quarantine Storage',
            'returns' => 'Returns Storage',
            default => $this->type ?? 'Standard Storage'
        };
    }

    public function getSecurityLevelLabelAttribute(): string
    {
        return match($this->security_level) {
            'level_1' => 'Level 1 - Basic Security',
            'level_2' => 'Level 2 - Enhanced Security',
            'level_3' => 'Level 3 - High Security',
            'level_4' => 'Level 4 - Maximum Security (DEA)',
            'level_5' => 'Level 5 - Ultra High Security',
            default => $this->security_level ?? 'Standard Security'
        };
    }

    public function getAccessControlLevelLabelAttribute(): string
    {
        return match($this->access_control_level) {
            'open' => 'Open Access',
            'staff_only' => 'Staff Only',
            'pharmacist_only' => 'Pharmacist Only',
            'authorized_personnel' => 'Authorized Personnel Only',
            'dual_control' => 'Dual Control Required',
            'biometric' => 'Biometric Access Required',
            default => $this->access_control_level ?? 'Standard Access'
        };
    }

    public function isControlledSubstanceStorage(): bool
    {
        return $this->controlled_substance_vault === true || 
               $this->type === 'controlled_substances' ||
               $this->dea_registration_required === true;
    }

    public function requiresSpecialHandling(): bool
    {
        return $this->temperature_controlled || 
               $this->humidity_controlled || 
               $this->light_protection ||
               $this->isControlledSubstanceStorage();
    }

    public function isTemperatureCompliant($currentTemp = null): bool
    {
        if (!$this->temperature_controlled || !$currentTemp) {
            return true;
        }

        return $currentTemp >= $this->temperature_min && $currentTemp <= $this->temperature_max;
    }

    public function isHumidityCompliant($currentHumidity = null): bool
    {
        if (!$this->humidity_controlled || !$currentHumidity) {
            return true;
        }

        return $currentHumidity >= $this->humidity_min && $currentHumidity <= $this->humidity_max;
    }

    public function getEnvironmentalStatusAttribute(): string
    {
        // This would typically check against real-time monitoring data
        // For now, we'll return a placeholder status
        if ($this->requiresSpecialHandling()) {
            return 'monitoring_required';
        }
        return 'standard';
    }

    public function isInspectionDue(): bool
    {
        if (!$this->next_inspection_due) {
            return false;
        }
        
        return $this->next_inspection_due->isPast();
    }

    public function isInspectionOverdue($days = 30): bool
    {
        if (!$this->next_inspection_due) {
            return false;
        }
        
        return $this->next_inspection_due->addDays($days)->isPast();
    }

    public function getComplianceStatusAttribute(): string
    {
        if ($this->isInspectionOverdue()) {
            return 'non_compliant';
        } elseif ($this->isInspectionDue()) {
            return 'inspection_due';
        } elseif (!$this->compliance_certification) {
            return 'certification_required';
        }
        return 'compliant';
    }

    public function getComplianceStatusLabelAttribute(): string
    {
        return match($this->compliance_status) {
            'compliant' => 'Compliant',
            'inspection_due' => 'Inspection Due',
            'certification_required' => 'Certification Required',
            'non_compliant' => 'Non-Compliant',
            default => 'Unknown'
        };
    }

    public function hasBackupSystems(): bool
    {
        return $this->backup_power && $this->alarm_system;
    }

    public function getMonitoringSystemLabelAttribute(): string
    {
        return match($this->monitoring_system) {
            'basic' => 'Basic Monitoring',
            'advanced' => 'Advanced Environmental Monitoring',
            'real_time' => 'Real-Time Monitoring with Alerts',
            'iot_enabled' => 'IoT-Enabled Smart Monitoring',
            'none' => 'No Monitoring System',
            default => $this->monitoring_system ?? 'Not Specified'
        };
    }

    // Scopes
    public function scopeControlledSubstances($query)
    {
        return $query->where('controlled_substance_vault', true)
                    ->orWhere('type', 'controlled_substances')
                    ->orWhere('dea_registration_required', true);
    }

    public function scopeTemperatureControlled($query)
    {
        return $query->where('temperature_controlled', true);
    }

    public function scopeRefrigerated($query)
    {
        return $query->where('refrigeration_unit', true)
                    ->orWhere('type', 'refrigerated');
    }

    public function scopeHighSecurity($query)
    {
        return $query->whereIn('security_level', ['level_3', 'level_4', 'level_5']);
    }

    public function scopeInspectionDue($query)
    {
        return $query->where('next_inspection_due', '<=', now());
    }

    public function scopeInspectionOverdue($query, $days = 30)
    {
        return $query->where('next_inspection_due', '<=', now()->subDays($days));
    }

    public function scopeCompliant($query)
    {
        return $query->where('next_inspection_due', '>', now())
                    ->whereNotNull('compliance_certification');
    }

    public function scopeByStorageType($query, $type)
    {
        return $query->where('type', $type);
    }
}