<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\Pharmacy\PharmacyProductGlobalSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PharmacyProductGlobalSettingsController extends Controller
{
    public function __construct()
    {
        // Authentication is handled at the route level
        // Add role-based access control if needed
        // $this->middleware('can:manage-pharmacy-settings');
    }

    /**
     * Get all settings for a specific pharmacy product (cached for performance)
     */
    public function index($productId = null)
    {
        if (!$productId) {
            return response()->json([
                'success' => false,
                'message' => 'Product ID is required'
            ], 400);
        }

        $cacheKey = "pharmacy_product_settings_{$productId}";
        $settings = Cache::remember($cacheKey, 3600, function () use ($productId) {
            return PharmacyProductGlobalSetting::forProduct($productId)->get();
        });

        // Transform settings into key-value pairs for frontend
        $formattedSettings = [];
        foreach ($settings as $setting) {
            $formattedSettings[$setting->setting_key] = $setting->setting_value;
        }

        return response()->json([
            'success' => true,
            'settings' => $formattedSettings,
            'data' => $settings, // Keep original format for backward compatibility
            'meta' => [
                'controlled_substance_settings' => $this->getControlledSubstanceSettings($settings),
                'prescription_settings' => $this->getPrescriptionSettings($settings),
                'storage_settings' => $this->getStorageSettings($settings)
            ]
        ]);
    }

    /**
     * Get a specific setting for a pharmacy product
     */
    public function show($productId, $key)
    {
        $setting = PharmacyProductGlobalSetting::byProductAndKey($productId, $key)->first();

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Setting not found for this pharmacy product'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $setting
        ]);
    }

    /**
     * Update a setting for a specific pharmacy product (with validation and audit)
     */
    public function update(Request $request, $productId, $key)
    {
        $validator = Validator::make($request->all(), [
            'setting_value' => 'required|array',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $setting = PharmacyProductGlobalSetting::byProductAndKey($productId, $key)->first();

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Setting not found for this pharmacy product'
            ], 404);
        }

        // Validate pharmacy-specific settings
        $validationResult = $this->validatePharmacySetting($key, $request->setting_value);
        if (!$validationResult['valid']) {
            return response()->json([
                'success' => false,
                'message' => $validationResult['message']
            ], 422);
        }

        // Use transaction for data integrity
        \DB::transaction(function () use ($setting, $request, $productId, $key) {
            $oldValue = $setting->setting_value;
            $setting->update($request->only(['setting_value', 'description']));

            // Clear cache for this product
            Cache::forget("pharmacy_product_settings_{$productId}");

            // Audit log for pharmacy compliance
            Log::info("Pharmacy product setting updated: {$key} for product {$productId}", [
                'old_value' => $oldValue,
                'new_value' => $request->setting_value,
                'product_id' => $productId,
                'user_id' => auth()->id(),
                'timestamp' => now(),
                'compliance_required' => $this->isComplianceSetting($key)
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Pharmacy setting updated successfully'
        ]);
    }

    /**
     * Create a new setting or update multiple settings for a specific pharmacy product
     */
    public function store(Request $request, $productId = null)
    {
        // If productId is not in the route, check if it's in the request
        if (!$productId) {
            $productId = $request->input('product_id');
        }

        if (!$productId) {
            return response()->json([
                'success' => false,
                'message' => 'Product ID is required'
            ], 400);
        }

        // Check if this is a bulk settings update
        if ($request->has('settings')) {
            return $this->storeBulkSettings($request, $productId);
        }

        // Handle single setting creation
        $validator = Validator::make($request->all(), [
            'setting_key' => 'required|string',
            'setting_value' => 'required|array',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if setting already exists for this product
        $existingSetting = PharmacyProductGlobalSetting::byProductAndKey($productId, $request->setting_key)->first();
        if ($existingSetting) {
            return response()->json([
                'success' => false,
                'message' => 'Setting already exists for this pharmacy product'
            ], 422);
        }

        // Validate pharmacy-specific settings
        $validationResult = $this->validatePharmacySetting($request->setting_key, $request->setting_value);
        if (!$validationResult['valid']) {
            return response()->json([
                'success' => false,
                'message' => $validationResult['message']
            ], 422);
        }

        $setting = PharmacyProductGlobalSetting::create([
            'product_id' => $productId,
            'setting_key' => $request->setting_key,
            'setting_value' => $request->setting_value,
            'description' => $request->description
        ]);

        // Clear cache for this product
        Cache::forget("pharmacy_product_settings_{$productId}");

        return response()->json([
            'success' => true,
            'message' => 'Pharmacy setting created successfully',
            'data' => $setting
        ], 201);
    }

    /**
     * Handle bulk settings update for a specific pharmacy product
     */
    private function storeBulkSettings(Request $request, $productId)
    {
        $settings = $request->input('settings');

        if (!is_array($settings)) {
            return response()->json([
                'success' => false,
                'message' => 'Settings must be an array'
            ], 422);
        }

        $errors = [];
        $updatedSettings = [];

        \DB::transaction(function () use ($settings, &$errors, &$updatedSettings, $productId) {
            foreach ($settings as $key => $value) {
                try {
                    // Validate the setting value
                    if (!is_array($value)) {
                        $errors[$key] = ['Setting value must be an array'];
                        continue;
                    }

                    // Validate pharmacy-specific settings
                    $validationResult = $this->validatePharmacySetting($key, $value);
                    if (!$validationResult['valid']) {
                        $errors[$key] = [$validationResult['message']];
                        continue;
                    }

                    // Find or create the setting for this product
                    $setting = PharmacyProductGlobalSetting::byProductAndKey($productId, $key)->first();

                    if ($setting) {
                        // Update existing setting
                        $setting->update([
                            'setting_value' => $value,
                            'description' => $this->getPharmacySettingDescription($key)
                        ]);
                    } else {
                        // Create new setting
                        $setting = PharmacyProductGlobalSetting::create([
                            'product_id' => $productId,
                            'setting_key' => $key,
                            'setting_value' => $value,
                            'description' => $this->getPharmacySettingDescription($key)
                        ]);
                    }

                    $updatedSettings[] = $setting;

                } catch (\Exception $e) {
                    $errors[$key] = ['Failed to save setting: ' . $e->getMessage()];
                }
            }

            // Clear cache for this product
            Cache::forget("pharmacy_product_settings_{$productId}");
        });

        if (!empty($errors)) {
            return response()->json([
                'success' => false,
                'message' => 'Some pharmacy settings failed to save',
                'errors' => $errors,
                'updated_count' => count($updatedSettings)
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'All pharmacy settings saved successfully',
            'data' => $updatedSettings,
            'updated_count' => count($updatedSettings)
        ]);
    }

    /**
     * Get description for a pharmacy setting key
     */
    private function getPharmacySettingDescription($key)
    {
        $descriptions = [
            'min_quantity_all_services' => 'Global minimum quantity threshold for all pharmacy services',
            'critical_stock_threshold' => 'Critical stock level threshold for pharmacy alerts',
            'expiry_alert_days' => 'Number of days before expiry to trigger pharmacy alerts',
            'auto_reorder_settings' => 'Automatic reorder configuration for pharmacy products',
            'notification_settings' => 'Pharmacy notification preferences and settings',
            'controlled_substance_settings' => 'Controlled substance tracking and compliance settings',
            'prescription_requirements' => 'Prescription validation and requirements settings',
            'storage_conditions' => 'Temperature and storage condition monitoring settings',
            'dea_compliance_settings' => 'DEA compliance and reporting settings',
            'inventory_audit_settings' => 'Pharmacy inventory audit and reconciliation settings',
            'dispensing_limits' => 'Daily and monthly dispensing limits for controlled substances',
            'pharmacist_verification' => 'Pharmacist verification requirements for dispensing',
            'patient_counseling_settings' => 'Patient counseling and consultation requirements',
            'insurance_verification' => 'Insurance verification and prior authorization settings'
        ];

        return $descriptions[$key] ?? 'Global pharmacy product setting';
    }

    /**
     * Validate pharmacy-specific settings
     */
    private function validatePharmacySetting($key, $value)
    {
        switch ($key) {
            case 'controlled_substance_settings':
                if (!isset($value['schedule']) || !in_array($value['schedule'], ['I', 'II', 'III', 'IV', 'V'])) {
                    return ['valid' => false, 'message' => 'Invalid controlled substance schedule'];
                }
                break;
            
            case 'dispensing_limits':
                if (isset($value['daily_limit']) && (!is_numeric($value['daily_limit']) || $value['daily_limit'] < 0)) {
                    return ['valid' => false, 'message' => 'Daily limit must be a positive number'];
                }
                break;
            
            case 'storage_conditions':
                if (isset($value['temperature_min']) && isset($value['temperature_max'])) {
                    if ($value['temperature_min'] >= $value['temperature_max']) {
                        return ['valid' => false, 'message' => 'Minimum temperature must be less than maximum temperature'];
                    }
                }
                break;
        }

        return ['valid' => true];
    }

    /**
     * Check if setting requires compliance tracking
     */
    private function isComplianceSetting($key)
    {
        $complianceSettings = [
            'controlled_substance_settings',
            'dea_compliance_settings',
            'dispensing_limits',
            'pharmacist_verification'
        ];

        return in_array($key, $complianceSettings);
    }

    /**
     * Get controlled substance specific settings
     */
    private function getControlledSubstanceSettings($settings)
    {
        return $settings->filter(function ($setting) {
            return str_contains($setting->setting_key, 'controlled_substance');
        });
    }

    /**
     * Get prescription specific settings
     */
    private function getPrescriptionSettings($settings)
    {
        return $settings->filter(function ($setting) {
            return str_contains($setting->setting_key, 'prescription');
        });
    }

    /**
     * Get storage specific settings
     */
    private function getStorageSettings($settings)
    {
        return $settings->filter(function ($setting) {
            return str_contains($setting->setting_key, 'storage');
        });
    }

    /**
     * Delete a setting for a specific pharmacy product
     */
    public function destroy($productId, $key)
    {
        $setting = PharmacyProductGlobalSetting::byProductAndKey($productId, $key)->first();

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Setting not found for this pharmacy product'
            ], 404);
        }

        $setting->delete();

        // Clear cache for this product
        Cache::forget("pharmacy_product_settings_{$productId}");

        return response()->json([
            'success' => true,
            'message' => 'Pharmacy setting deleted successfully'
        ]);
    }
}