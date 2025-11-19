<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\Pharmacy\PharmacyProduct;
use App\Models\Pharmacy\PharmacyServiceProductSetting;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PharmacyServiceProductSettingController extends Controller
{
    /**
     * Get product settings for a specific service and pharmacy product
     */
    public function show(Request $request, $serviceId, $productParam, $productForme = null)
    {
        $validator = Validator::make([
            'service_id' => $serviceId,
            'product_param' => $productParam,
            'product_forme' => $productForme,
        ], [
            'service_id' => 'required|exists:services,id',
            'product_param' => 'required',
            'product_forme' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Find pharmacy product - could be by ID or by name
        $product = null;

        // Try to find by ID first (for API routes)
        if (is_numeric($productParam)) {
            $product = PharmacyProduct::find($productParam);
        }

        // If not found by ID, try by name (for web routes)
        if (! $product) {
            $product = PharmacyProduct::where('name', $productParam)->first();
        }

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Pharmacy product not found',
            ], 404);
        }

        $query = PharmacyServiceProductSetting::where('service_id', $serviceId)
            ->where('product_id', $product->id);

        // Handle product_forme parameter - could be passed as URL param or query param
        $formeValue = $productForme ?: $request->get('product_forme');

        if ($formeValue && $formeValue !== 'N/A') {
            $query->where('product_forme', $formeValue);
        } else {
            $query->where(function ($q) use ($formeValue) {
                if ($formeValue === 'N/A' || $formeValue === null) {
                    $q->whereNull('product_forme');
                } else {
                    $q->where('product_forme', $formeValue);
                }
            });
        }

        $setting = $query->first();

        if (! $setting) {
            // Return default pharmacy settings if not found
            $setting = $this->getDefaultPharmacySettings($serviceId, $product->id, $formeValue, $product);
        }

        return response()->json([
            'success' => true,
            'data' => $setting,
            'meta' => [
                'is_controlled_substance' => $product->is_controlled_substance ?? false,
                'requires_prescription' => $product->requires_prescription ?? false,
                'controlled_substance_schedule' => $product->controlled_substance_schedule ?? null,
            ],
        ]);
    }

    /**
     * Store or update pharmacy product settings
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'required|exists:services,id',
            'product_name' => 'required|string',
            'product_forme' => 'nullable|string',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'critical_stock_threshold' => 'nullable|integer|min:0',
            'max_stock_level' => 'nullable|integer|min:0',
            'reorder_point' => 'nullable|integer|min:0',
            'expiry_alert_days' => 'nullable|integer|min:1',
            'min_shelf_life_days' => 'nullable|integer|min:1',
            'email_alerts' => 'nullable|boolean',
            'sms_alerts' => 'nullable|boolean',
            'alert_frequency' => 'nullable|in:immediate,daily,weekly',
            'preferred_supplier' => 'nullable|string|max:255',
            'batch_tracking' => 'nullable|boolean',
            'location_tracking' => 'nullable|boolean',
            'auto_reorder' => 'nullable|boolean',
            'custom_name' => 'nullable|string|max:255',
            'color_code' => 'nullable|in:default,red,orange,yellow,green,blue,purple',
            'priority' => 'nullable|in:low,normal,high,critical',
            // Pharmacy-specific fields
            'controlled_substance_tracking' => 'nullable|boolean',
            'requires_pharmacist_verification' => 'nullable|boolean',
            'prescription_required' => 'nullable|boolean',
            'daily_dispensing_limit' => 'nullable|integer|min:0',
            'monthly_dispensing_limit' => 'nullable|integer|min:0',
            'patient_counseling_required' => 'nullable|boolean',
            'insurance_verification_required' => 'nullable|boolean',
            'temperature_monitoring' => 'nullable|boolean',
            'temperature_min' => 'nullable|numeric',
            'temperature_max' => 'nullable|numeric',
            'dea_reporting_required' => 'nullable|boolean',
            'inventory_audit_frequency' => 'nullable|in:daily,weekly,monthly,quarterly',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Find pharmacy product - could be by ID or by name
            $product = null;

            // Try to find by ID first (if product_id is provided)
            if ($request->has('product_id') && is_numeric($request->product_id)) {
                $product = PharmacyProduct::find($request->product_id);
            }

            // If not found by ID, try by name
            if (! $product && $request->has('product_name')) {
                $product = PharmacyProduct::where('name', $request->product_name)->first();
            }

            if (! $product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pharmacy product not found',
                ], 404);
            }

            // Validate pharmacy-specific settings
            $validationResult = $this->validatePharmacySettings($request, $product);
            if (! $validationResult['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $validationResult['message'],
                ], 422);
            }

            // Handle product_forme - convert empty strings to null
            $productForme = $request->product_forme;
            if ($productForme === '' || $productForme === 'N/A') {
                $productForme = null;
            }

            // Check if settings already exist
            $existingSetting = PharmacyServiceProductSetting::where('service_id', $request->service_id)
                ->where('product_id', $product->id)
                ->where(function ($query) use ($productForme) {
                    if ($productForme === null) {
                        $query->whereNull('product_forme');
                    } else {
                        $query->where('product_forme', $productForme);
                    }
                })
                ->first();

            if ($existingSetting) {
                // Update existing settings
                $existingSetting->update(array_merge($request->all(), [
                    'product_id' => $product->id,
                    'product_name' => $request->product_name,
                    'product_forme' => $productForme,
                ]));
                $setting = $existingSetting;
            } else {
                // Create new settings
                $setting = PharmacyServiceProductSetting::create(array_merge($request->all(), [
                    'service_id' => $request->service_id,
                    'product_id' => $product->id,
                    'product_name' => $request->product_name,
                    'product_forme' => $productForme,
                ]));
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pharmacy product settings saved successfully',
                'data' => $setting,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to save pharmacy product settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update pharmacy product settings
     */
    public function update(Request $request, $serviceId, $productName, $productForme = null)
    {
        $validator = Validator::make(array_merge($request->all(), [
            'service_id' => $serviceId,
            'product_param' => $productName,
            'product_forme' => $productForme,
        ]), [
            'service_id' => 'required|exists:services,id',
            'product_param' => 'required',
            'product_forme' => 'nullable|string',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'critical_stock_threshold' => 'nullable|integer|min:0',
            'max_stock_level' => 'nullable|integer|min:0',
            'reorder_point' => 'nullable|integer|min:0',
            'expiry_alert_days' => 'nullable|integer|min:1',
            'min_shelf_life_days' => 'nullable|integer|min:1',
            'email_alerts' => 'nullable|boolean',
            'sms_alerts' => 'nullable|boolean',
            'alert_frequency' => 'nullable|in:immediate,daily,weekly',
            'preferred_supplier' => 'nullable|string|max:255',
            'batch_tracking' => 'nullable|boolean',
            'location_tracking' => 'nullable|boolean',
            'auto_reorder' => 'nullable|boolean',
            'custom_name' => 'nullable|string|max:255',
            'color_code' => 'nullable|in:default,red,orange,yellow,green,blue,purple',
            'priority' => 'nullable|in:low,normal,high,critical',
            // Pharmacy-specific fields
            'controlled_substance_tracking' => 'nullable|boolean',
            'requires_pharmacist_verification' => 'nullable|boolean',
            'prescription_required' => 'nullable|boolean',
            'daily_dispensing_limit' => 'nullable|integer|min:0',
            'monthly_dispensing_limit' => 'nullable|integer|min:0',
            'patient_counseling_required' => 'nullable|boolean',
            'insurance_verification_required' => 'nullable|boolean',
            'temperature_monitoring' => 'nullable|boolean',
            'temperature_min' => 'nullable|numeric',
            'temperature_max' => 'nullable|numeric',
            'dea_reporting_required' => 'nullable|boolean',
            'inventory_audit_frequency' => 'nullable|in:daily,weekly,monthly,quarterly',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Find pharmacy product - could be by ID or by name
            $product = null;

            // Try to find by ID first (for API routes)
            if (is_numeric($productName)) {
                $product = PharmacyProduct::find($productName);
            }

            // If not found by ID, try by name (for web routes)
            if (! $product) {
                $product = PharmacyProduct::where('name', $productName)->first();
            }

            if (! $product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pharmacy product not found',
                ], 404);
            }

            $query = PharmacyServiceProductSetting::where('service_id', $serviceId)
                ->where('product_id', $product->id);

            if ($productForme && $productForme !== 'N/A') {
                $query->where('product_forme', $productForme);
            } else {
                $query->where(function ($q) use ($productForme) {
                    if ($productForme === 'N/A' || $productForme === null) {
                        $q->whereNull('product_forme');
                    } else {
                        $q->where('product_forme', $productForme);
                    }
                });
            }

            $setting = $query->first();

            if (! $setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pharmacy product settings not found',
                ], 404);
            }

            // Validate pharmacy-specific settings
            $validationResult = $this->validatePharmacySettings($request, $product);
            if (! $validationResult['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $validationResult['message'],
                ], 422);
            }

            $setting->update($request->all());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pharmacy product settings updated successfully',
                'data' => $setting,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update pharmacy product settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete pharmacy product settings
     */
    public function destroy(Request $request, $serviceId, $productName, $productForme = null)
    {
        $validator = Validator::make([
            'service_id' => $serviceId,
            'product_param' => $productName,
            'product_forme' => $productForme,
        ], [
            'service_id' => 'required|exists:services,id',
            'product_param' => 'required',
            'product_forme' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Find pharmacy product - could be by ID or by name
            $product = null;

            // Try to find by ID first (for API routes)
            if (is_numeric($productName)) {
                $product = PharmacyProduct::find($productName);
            }

            // If not found by ID, try by name (for web routes)
            if (! $product) {
                $product = PharmacyProduct::where('name', $productName)->first();
            }

            if (! $product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pharmacy product not found',
                ], 404);
            }

            $query = PharmacyServiceProductSetting::where('service_id', $serviceId)
                ->where('product_id', $product->id);

            if ($productForme && $productForme !== 'N/A') {
                $query->where('product_forme', $productForme);
            } else {
                $query->where(function ($q) use ($productForme) {
                    if ($productForme === 'N/A' || $productForme === null) {
                        $q->whereNull('product_forme');
                    } else {
                        $q->where('product_forme', $productForme);
                    }
                });
            }

            $setting = $query->first();

            if (! $setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pharmacy product settings not found',
                ], 404);
            }

            $setting->delete();

            return response()->json([
                'success' => true,
                'message' => 'Pharmacy product settings deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete pharmacy product settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all pharmacy product settings for a service
     */
    public function getByService(Request $request, $serviceId)
    {
        $validator = Validator::make(['service_id' => $serviceId], [
            'service_id' => 'required|exists:services,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = PharmacyServiceProductSetting::where('service_id', $serviceId)
            ->with(['product', 'service']);

        // Apply filters
        if ($request->has('product_name')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->product_name.'%');
            });
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('alert_frequency')) {
            $query->where('alert_frequency', $request->alert_frequency);
        }

        // Pharmacy-specific filters
        if ($request->has('controlled_only') && $request->controlled_only) {
            $query->where('controlled_substance_tracking', true);
        }

        if ($request->has('prescription_required') && $request->prescription_required) {
            $query->where('prescription_required', true);
        }

        if ($request->has('temperature_monitoring') && $request->temperature_monitoring) {
            $query->where('temperature_monitoring', true);
        }

        $settings = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $settings,
            'meta' => [
                'controlled_count' => $query->where('controlled_substance_tracking', true)->count(),
                'prescription_count' => $query->where('prescription_required', true)->count(),
                'temperature_monitored_count' => $query->where('temperature_monitoring', true)->count(),
            ],
        ]);
    }

    /**
     * Bulk update pharmacy product settings
     */
    public function bulkUpdate(Request $request, $serviceId)
    {
        $validator = Validator::make(array_merge($request->all(), ['service_id' => $serviceId]), [
            'service_id' => 'required|exists:services,id',
            'settings' => 'required|array',
            'settings.*.product_id' => 'required|exists:pharmacy_products,id',
            'settings.*.product_name' => 'required|string|max:255',
            'settings.*.product_forme' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $results = [];
            $errors = [];

            foreach ($request->settings as $index => $settingData) {
                try {
                    $product = PharmacyProduct::find($settingData['product_id']);
                    if (! $product) {
                        $errors[$index] = 'Pharmacy product not found';

                        continue;
                    }

                    // Validate pharmacy-specific settings for this product
                    $validationResult = $this->validatePharmacySettings(new Request($settingData), $product);
                    if (! $validationResult['valid']) {
                        $errors[$index] = $validationResult['message'];

                        continue;
                    }

                    $productForme = $settingData['product_forme'] ?? null;

                    if ($productForme === 'N/A') {
                        $productForme = null;
                    }

                    $setting = PharmacyServiceProductSetting::updateOrCreate(
                        [
                            'service_id' => $serviceId,
                            'product_id' => $settingData['product_id'],
                            'product_forme' => $productForme,
                        ],
                        array_merge($settingData, [
                            'service_id' => $serviceId,
                            'product_id' => $settingData['product_id'],
                            'product_forme' => $productForme,
                        ])
                    );
                    $results[] = $setting;
                } catch (\Exception $e) {
                    $errors[$index] = 'Failed to save: '.$e->getMessage();
                }
            }

            DB::commit();

            $response = [
                'success' => empty($errors),
                'message' => empty($errors) ? 'Bulk pharmacy product settings updated successfully' : 'Some settings failed to update',
                'data' => $results,
                'updated_count' => count($results),
            ];

            if (! empty($errors)) {
                $response['errors'] = $errors;
            }

            return response()->json($response, empty($errors) ? 200 : 422);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk update pharmacy product settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate pharmacy-specific settings
     */
    private function validatePharmacySettings(Request $request, $product)
    {
        // Check temperature range validation
        if ($request->has('temperature_min') && $request->has('temperature_max')) {
            if ($request->temperature_min >= $request->temperature_max) {
                return ['valid' => false, 'message' => 'Minimum temperature must be less than maximum temperature'];
            }
        }

        // Check dispensing limits for controlled substances
        if ($product->is_controlled_substance) {
            if ($request->has('daily_dispensing_limit') && $request->daily_dispensing_limit <= 0) {
                return ['valid' => false, 'message' => 'Daily dispensing limit must be greater than 0 for controlled substances'];
            }
        }

        // Check monthly limit is greater than daily limit
        if ($request->has('daily_dispensing_limit') && $request->has('monthly_dispensing_limit')) {
            if ($request->monthly_dispensing_limit < $request->daily_dispensing_limit) {
                return ['valid' => false, 'message' => 'Monthly dispensing limit must be greater than or equal to daily limit'];
            }
        }

        return ['valid' => true];
    }

    /**
     * Get default pharmacy settings for a product
     */
    private function getDefaultPharmacySettings($serviceId, $productId, $forme = null, $product = null)
    {
        if (! $product) {
            $product = PharmacyProduct::find($productId);
        }

        $isControlled = $product->is_controlled_substance ?? false;
        $requiresPrescription = $product->requires_prescription ?? false;

        return [
            'service_id' => $serviceId,
            'product_id' => $productId,
            'product_name' => $product ? $product->name : 'Unknown Pharmacy Product',
            'product_forme' => $forme,

            // Default Alert Settings
            'low_stock_threshold' => $isControlled ? 5 : 10,
            'critical_stock_threshold' => $isControlled ? 2 : 5,
            'max_stock_level' => $isControlled ? 20 : 30,
            'reorder_point' => $isControlled ? 5 : 10,
            'expiry_alert_days' => 30,
            'min_shelf_life_days' => 90,

            // Default Notification Settings
            'email_alerts' => true,
            'sms_alerts' => $isControlled,
            'alert_frequency' => $isControlled ? 'immediate' : 'daily',
            'preferred_supplier' => null,

            // Default Inventory Settings
            'batch_tracking' => true,
            'location_tracking' => true,
            'auto_reorder' => false,

            // Default Display Settings
            'custom_name' => null,
            'color_code' => $isControlled ? 'red' : 'default',
            'priority' => $isControlled ? 'high' : 'normal',

            // Pharmacy-specific defaults
            'controlled_substance_tracking' => $isControlled,
            'requires_pharmacist_verification' => $isControlled || $requiresPrescription,
            'prescription_required' => $requiresPrescription,
            'daily_dispensing_limit' => $isControlled ? 30 : null,
            'monthly_dispensing_limit' => $isControlled ? 900 : null,
            'patient_counseling_required' => $requiresPrescription,
            'insurance_verification_required' => $requiresPrescription,
            'temperature_monitoring' => $product->requires_refrigeration ?? false,
            'temperature_min' => $product->temperature_range_min ?? null,
            'temperature_max' => $product->temperature_range_max ?? null,
            'dea_reporting_required' => $isControlled,
            'inventory_audit_frequency' => $isControlled ? 'daily' : 'weekly',
        ];
    }
}
