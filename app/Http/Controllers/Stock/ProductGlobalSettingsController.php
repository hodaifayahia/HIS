<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Models\ProductGlobalSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ProductGlobalSettingsController extends Controller
{
    public function __construct()
    {
        // Authentication is handled at the route level
        // Add role-based access control if needed
        // $this->middleware('can:manage-stock-settings');
    }

    /**
     * Get all settings for a specific product (cached for performance)
     */
    public function index($productId = null)
    {
        if (! $productId) {
            return response()->json([
                'success' => false,
                'message' => 'Product ID is required',
            ], 400);
        }

        $cacheKey = "product_settings_{$productId}";
        $settings = Cache::remember($cacheKey, 3600, function () use ($productId) {
            return ProductGlobalSetting::forProduct($productId)->get();
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
        ]);
    }

    /**
     * Get a specific setting for a product
     */
    public function show($productId, $key)
    {
        $setting = ProductGlobalSetting::byProductAndKey($productId, $key)->first();

        if (! $setting) {
            return response()->json([
                'success' => false,
                'message' => 'Setting not found for this product',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $setting,
        ]);
    }

    /**
     * Update a setting for a specific product (with validation and audit)
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
                'errors' => $validator->errors(),
            ], 422);
        }

        $setting = ProductGlobalSetting::byProductAndKey($productId, $key)->first();

        if (! $setting) {
            return response()->json([
                'success' => false,
                'message' => 'Setting not found for this product',
            ], 404);
        }

        // Use transaction for data integrity
        \DB::transaction(function () use ($setting, $request, $productId) {
            $oldValue = $setting->setting_value;
            $setting->update($request->only(['setting_value', 'description']));

            // Clear cache for this product
            Cache::forget("product_settings_{$productId}");

            // Audit log
            Log::info("Product setting updated: {$key} for product {$productId}", [
                'old_value' => $oldValue,
                'new_value' => $request->setting_value,
                'product_id' => $productId,
                'user_id' => auth()->id(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Setting updated successfully',
        ]);
    }

    /**
     * Create a new setting or update multiple settings for a specific product
     */
    public function store(Request $request, $productId = null)
    {
        // If productId is not in the route, check if it's in the request
        if (! $productId) {
            $productId = $request->input('product_id');
        }

        if (! $productId) {
            return response()->json([
                'success' => false,
                'message' => 'Product ID is required',
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
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check if setting already exists for this product
        $existingSetting = ProductGlobalSetting::byProductAndKey($productId, $request->setting_key)->first();
        if ($existingSetting) {
            return response()->json([
                'success' => false,
                'message' => 'Setting already exists for this product',
            ], 422);
        }

        $setting = ProductGlobalSetting::create([
            'product_id' => $productId,
            'setting_key' => $request->setting_key,
            'setting_value' => $request->setting_value,
            'description' => $request->description,
        ]);

        // Clear cache for this product
        Cache::forget("product_settings_{$productId}");

        return response()->json([
            'success' => true,
            'message' => 'Setting created successfully',
            'data' => $setting,
        ], 201);
    }

    /**
     * Handle bulk settings update for a specific product
     */
    private function storeBulkSettings(Request $request, $productId)
    {
        $settings = $request->input('settings');

        if (! is_array($settings)) {
            return response()->json([
                'success' => false,
                'message' => 'Settings must be an array',
            ], 422);
        }

        $errors = [];
        $updatedSettings = [];

        \DB::transaction(function () use ($settings, &$errors, &$updatedSettings, $productId) {
            foreach ($settings as $key => $value) {
                try {
                    // Validate the setting value
                    if (! is_array($value)) {
                        $errors[$key] = ['Setting value must be an array'];

                        continue;
                    }

                    // Find or create the setting for this product
                    $setting = ProductGlobalSetting::byProductAndKey($productId, $key)->first();

                    if ($setting) {
                        // Update existing setting
                        $setting->update([
                            'setting_value' => $value,
                            'description' => $this->getSettingDescription($key),
                        ]);
                    } else {
                        // Create new setting
                        $setting = ProductGlobalSetting::create([
                            'product_id' => $productId,
                            'setting_key' => $key,
                            'setting_value' => $value,
                            'description' => $this->getSettingDescription($key),
                        ]);
                    }

                    $updatedSettings[] = $setting;

                } catch (\Exception $e) {
                    $errors[$key] = ['Failed to save setting: '.$e->getMessage()];
                }
            }

            // Clear cache for this product
            Cache::forget("product_settings_{$productId}");
        });

        if (! empty($errors)) {
            return response()->json([
                'success' => false,
                'message' => 'Some settings failed to save',
                'errors' => $errors,
                'updated_count' => count($updatedSettings),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'All settings saved successfully',
            'data' => $updatedSettings,
            'updated_count' => count($updatedSettings),
        ]);
    }

    /**
     * Get description for a setting key
     */
    private function getSettingDescription($key)
    {
        $descriptions = [
            'min_quantity_all_services' => 'Global minimum quantity threshold for all services',
            'critical_stock_threshold' => 'Critical stock level threshold for alerts',
            'expiry_alert_days' => 'Number of days before expiry to trigger alerts',
            'auto_reorder_settings' => 'Automatic reorder configuration settings',
            'notification_settings' => 'Notification preferences and settings',
        ];

        return $descriptions[$key] ?? 'Global product setting';
    }

    /**
     * Delete a setting for a specific product
     */
    public function destroy($productId, $key)
    {
        $setting = ProductGlobalSetting::byProductAndKey($productId, $key)->first();

        if (! $setting) {
            return response()->json([
                'success' => false,
                'message' => 'Setting not found for this product',
            ], 404);
        }

        $setting->delete();

        // Clear cache for this product
        Cache::forget("product_settings_{$productId}");

        return response()->json([
            'success' => true,
            'message' => 'Setting deleted successfully',
        ]);
    }
}
