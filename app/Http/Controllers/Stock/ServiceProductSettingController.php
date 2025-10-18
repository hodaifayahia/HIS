<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceProductSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ServiceProductSettingController extends Controller
{
    /**
     * Get product settings for a specific service and product
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

        // Find product - could be by ID or by name
        $product = null;

        // Try to find by ID first (for API routes)
        if (is_numeric($productParam)) {
            $product = Product::find($productParam);
        }

        // If not found by ID, try by name (for web routes)
        if (! $product) {
            $product = Product::where('name', $productParam)->first();
        }

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        $query = ServiceProductSetting::where('service_id', $serviceId)
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
            // Return default settings if not found
            $setting = $this->getDefaultSettings($serviceId, $product->id, $formeValue, $product);
        }

        return response()->json([
            'success' => true,
            'data' => $setting,
        ]);
    }

    /**
     * Store or update product settings
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

            // Find product - could be by ID or by name
            $product = null;

            // Try to find by ID first (if product_id is provided)
            if ($request->has('product_id') && is_numeric($request->product_id)) {
                $product = Product::find($request->product_id);
            }

            // If not found by ID, try by name
            if (! $product && $request->has('product_name')) {
                $product = Product::where('name', $request->product_name)->first();
            }

            if (! $product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found',
                ], 404);
            }

            // Handle product_forme - convert empty strings to null
            $productForme = $request->product_forme;
            if ($productForme === '' || $productForme === 'N/A') {
                $productForme = null;
            }

            // Check if settings already exist
            $existingSetting = ServiceProductSetting::where('service_id', $request->service_id)
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
                $setting = ServiceProductSetting::create(array_merge($request->all(), [
                    'service_id' => $request->service_id,
                    'product_id' => $product->id,
                    'product_name' => $request->product_name,
                    'product_forme' => $productForme,
                ]));
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product settings saved successfully',
                'data' => $setting,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to save product settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update product settings
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

            // Find product - could be by ID or by name
            $product = null;

            // Try to find by ID first (for API routes)
            if (is_numeric($productName)) {
                $product = Product::find($productName);
            }

            // If not found by ID, try by name (for web routes)
            if (! $product) {
                $product = Product::where('name', $productName)->first();
            }

            if (! $product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found',
                ], 404);
            }

            $query = ServiceProductSetting::where('service_id', $serviceId)
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
                    'message' => 'Product settings not found',
                ], 404);
            }

            $setting->update($request->all());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product settings updated successfully',
                'data' => $setting,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update product settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete product settings
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
            // Find product - could be by ID or by name
            $product = null;

            // Try to find by ID first (for API routes)
            if (is_numeric($productName)) {
                $product = Product::find($productName);
            }

            // If not found by ID, try by name (for web routes)
            if (! $product) {
                $product = Product::where('name', $productName)->first();
            }

            if (! $product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found',
                ], 404);
            }

            $query = ServiceProductSetting::where('service_id', $serviceId)
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
                    'message' => 'Product settings not found',
                ], 404);
            }

            $setting->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product settings deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all product settings for a service
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

        $query = ServiceProductSetting::where('service_id', $serviceId)
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

        $settings = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Bulk update product settings
     */
    public function bulkUpdate(Request $request, $serviceId)
    {
        $validator = Validator::make(array_merge($request->all(), ['service_id' => $serviceId]), [
            'service_id' => 'required|exists:services,id',
            'settings' => 'required|array',
            'settings.*.product_id' => 'required|exists:products,id',
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
            foreach ($request->settings as $settingData) {
                $productForme = $settingData['product_forme'] ?? null;

                if ($productForme === 'N/A') {
                    $productForme = null;
                }

                $setting = ServiceProductSetting::updateOrCreate(
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
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bulk product settings updated successfully',
                'data' => $results,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk update product settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get default settings for a product
     */
    private function getDefaultSettings($serviceId, $productId, $forme = null, $product = null)
    {
        if (! $product) {
            $product = Product::find($productId);
        }

        return [
            'service_id' => $serviceId,
            'product_id' => $productId,
            'product_name' => $product ? $product->name : 'Unknown Product',
            'product_forme' => $forme,

            // Default Alert Settings
            'low_stock_threshold' => 10,
            'critical_stock_threshold' => 5,
            'max_stock_level' => 30,
            'reorder_point' => 10,
            'expiry_alert_days' => 30,
            'min_shelf_life_days' => 90,

            // Default Notification Settings
            'email_alerts' => true,
            'sms_alerts' => false,
            'alert_frequency' => 'immediate',
            'preferred_supplier' => null,

            // Default Inventory Settings
            'batch_tracking' => true,
            'location_tracking' => true,
            'auto_reorder' => false,

            // Default Display Settings
            'custom_name' => null,
            'color_code' => 'default',
            'priority' => 'normal',
        ];
    }
}
