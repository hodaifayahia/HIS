<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyMovementAuditLog extends Model
{
    protected $fillable = [
        'pharmacy_movement_id',
        'pharmacy_movement_item_id',
        'user_id',
        'action',
        'old_values',
        'new_values',
        'notes',
        'ip_address',
        'user_agent',
        'pharmacist_license_number',
        'verification_method',
        'compliance_notes',
        'regulatory_reference',
        'patient_consent_verified',
        'drug_interaction_checked',
        'allergy_check_performed',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'patient_consent_verified' => 'boolean',
        'drug_interaction_checked' => 'boolean',
        'allergy_check_performed' => 'boolean',
    ];

    // Relationships
    public function pharmacyMovement(): BelongsTo
    {
        return $this->belongsTo(PharmacyMovement::class);
    }

    public function pharmacyMovementItem(): BelongsTo
    {
        return $this->belongsTo(PharmacyMovementItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Helper methods
    public function getFormattedChangesAttribute(): string
    {
        if (! $this->old_values || ! $this->new_values) {
            return 'No changes recorded';
        }

        $changes = [];
        foreach ($this->new_values as $key => $newValue) {
            $oldValue = $this->old_values[$key] ?? 'null';
            if ($oldValue != $newValue) {
                $changes[] = "{$key}: {$oldValue} → {$newValue}";
            }
        }

        return implode(', ', $changes);
    }

    public function getActionDescriptionAttribute(): string
    {
        $descriptions = [
            'approved' => 'Item approved by pharmacist',
            'rejected' => 'Item rejected by pharmacist',
            'created' => 'Pharmacy movement created',
            'updated' => 'Pharmacy movement updated',
            'status_changed' => 'Status changed',
            'dispensed' => 'Medication dispensed',
            'verified' => 'Pharmacist verification completed',
            'quality_check' => 'Quality check performed',
            'drug_interaction_check' => 'Drug interaction check performed',
            'allergy_check' => 'Allergy check performed',
            'dosage_adjusted' => 'Dosage adjusted by pharmacist',
            'substitution_made' => 'Generic substitution made',
            'patient_counseled' => 'Patient counseling provided',
            'prescription_validated' => 'Prescription validated',
        ];

        return $descriptions[$this->action] ?? ucfirst($this->action);
    }

    public function getVerificationMethodLabelAttribute(): string
    {
        return match ($this->verification_method) {
            'manual_review' => 'Manual Review',
            'electronic_signature' => 'Electronic Signature',
            'biometric' => 'Biometric Verification',
            'two_factor' => 'Two-Factor Authentication',
            'supervisor_override' => 'Supervisor Override',
            default => $this->verification_method ?? 'Standard Verification'
        };
    }

    // Pharmacy-specific methods
    public function isPharmacistAction(): bool
    {
        return in_array($this->action, [
            'approved',
            'rejected',
            'verified',
            'dispensed',
            'quality_check',
            'drug_interaction_check',
            'allergy_check',
            'dosage_adjusted',
            'substitution_made',
            'patient_counseled',
            'prescription_validated',
        ]);
    }

    public function isComplianceAction(): bool
    {
        return in_array($this->action, [
            'drug_interaction_check',
            'allergy_check',
            'prescription_validated',
            'patient_counseled',
        ]);
    }

    public function hasRegulatoryImplications(): bool
    {
        return ! empty($this->regulatory_reference) || $this->isComplianceAction();
    }

    public function isPatientSafetyRelated(): bool
    {
        return $this->drug_interaction_checked ||
               $this->allergy_check_performed ||
               in_array($this->action, ['dosage_adjusted', 'substitution_made']);
    }

    public function getComplianceStatusAttribute(): string
    {
        $checks = [
            'patient_consent_verified',
            'drug_interaction_checked',
            'allergy_check_performed',
        ];

        $completedChecks = 0;
        foreach ($checks as $check) {
            if ($this->$check) {
                $completedChecks++;
            }
        }

        if ($completedChecks === count($checks)) {
            return 'fully_compliant';
        } elseif ($completedChecks > 0) {
            return 'partially_compliant';
        }

        return 'non_compliant';
    }

    public function getComplianceStatusLabelAttribute(): string
    {
        return match ($this->compliance_status) {
            'fully_compliant' => 'Fully Compliant',
            'partially_compliant' => 'Partially Compliant',
            'non_compliant' => 'Non-Compliant',
            default => 'Unknown'
        };
    }

    // Scopes
    public function scopeForMovement($query, $movementId)
    {
        return $query->where('pharmacy_movement_id', $movementId);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopePharmacistActions($query)
    {
        return $query->whereIn('action', [
            'approved',
            'rejected',
            'verified',
            'dispensed',
            'quality_check',
            'drug_interaction_check',
            'allergy_check',
            'dosage_adjusted',
            'substitution_made',
            'patient_counseled',
            'prescription_validated',
        ]);
    }

    public function scopeComplianceActions($query)
    {
        return $query->whereIn('action', [
            'drug_interaction_check',
            'allergy_check',
            'prescription_validated',
            'patient_counseled',
        ]);
    }

    public function scopePatientSafetyRelated($query)
    {
        return $query->where(function ($q) {
            $q->where('drug_interaction_checked', true)
                ->orWhere('allergy_check_performed', true)
                ->orWhereIn('action', ['dosage_adjusted', 'substitution_made']);
        });
    }

    public function scopeByPharmacistLicense($query, $licenseNumber)
    {
        return $query->where('pharmacist_license_number', $licenseNumber);
    }
}
