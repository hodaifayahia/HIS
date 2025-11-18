<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\SellingSettings;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SellingSettingsController extends Controller
{
    /**
     * Display a listing of selling settings
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = SellingSettings::with(['service', 'serviceGroup', 'creator', 'updater']);

            // Filter by type (pharmacy or stock)
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            // Filter by service
            if ($request->has('service_id')) {
                $query->where('service_id', $request->service_id);
            }

            // Filter by service group
            if ($request->has('service_group_id')) {
                $query->where('service_group_id', $request->service_group_id);
            }

            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            // Search
            if ($request->has('search') && ! empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->whereHas('service', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                        ->orWhereHas('serviceGroup', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                });
            }

            // Sort
            $sortField = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortField, $sortOrder);

            $settings = $query->get();

            return response()->json([
                'status' => 'success',
                'data' => $settings,
                'total' => $settings->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch selling settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created selling setting
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'nullable|exists:services,id',
            'service_group_id' => 'nullable|exists:service_groups,id',
            'percentage' => 'required|numeric|min:0|max:100',
            'type' => 'required|in:pharmacy,stock',
            'is_active' => 'boolean',
        ]);

        // Custom validation: Must have either service_id or service_group_id, but not both
        $validator->after(function ($validator) use ($request) {
            if (! $request->service_id && ! $request->service_group_id) {
                $validator->errors()->add('service', 'Either service_id or service_group_id is required');
            }
            if ($request->service_id && $request->service_group_id) {
                $validator->errors()->add('service', 'Cannot have both service_id and service_group_id');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            // If is_active is true, deactivate other settings for this service/group and type
            if ($request->is_active) {
                if ($request->service_id) {
                    SellingSettings::where('service_id', $request->service_id)
                        ->where('type', $request->type)
                        ->update(['is_active' => false]);
                }
                if ($request->service_group_id) {
                    SellingSettings::where('service_group_id', $request->service_group_id)
                        ->where('type', $request->type)
                        ->update(['is_active' => false]);
                }
            }

            $setting = SellingSettings::create([
                'service_id' => $request->service_id,
                'service_group_id' => $request->service_group_id,
                'percentage' => $request->percentage,
                'type' => $request->type,
                'is_active' => $request->is_active ?? false,
                'created_by' => Auth::id(),
            ]);

            $setting->load(['service', 'serviceGroup', 'creator']);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Selling setting created successfully',
                'data' => $setting,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create selling setting',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified selling setting
     */
    public function show($id): JsonResponse
    {
        try {
            $setting = SellingSettings::with(['service', 'creator', 'updater'])
                ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $setting,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Selling setting not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Update the specified selling setting
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'sometimes|exists:services,id',
            'percentage' => 'sometimes|numeric|min:0|max:100',
            'type' => 'sometimes|in:pharmacy,stock',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $setting = SellingSettings::findOrFail($id);

            // If activating this setting, deactivate others
            if ($request->has('is_active') && $request->is_active) {
                $serviceId = $request->service_id ?? $setting->service_id;
                $type = $request->type ?? $setting->type;

                SellingSettings::where('service_id', $serviceId)
                    ->where('type', $type)
                    ->where('id', '!=', $id)
                    ->update(['is_active' => false]);
            }

            $setting->update(array_merge(
                $request->only(['service_id', 'percentage', 'type', 'is_active']),
                ['updated_by' => Auth::id()]
            ));

            $setting->load(['service', 'creator', 'updater']);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Selling setting updated successfully',
                'data' => $setting,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update selling setting',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified selling setting
     */
    public function destroy($id): JsonResponse
    {
        try {
            $setting = SellingSettings::findOrFail($id);
            $setting->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Selling setting deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete selling setting',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle active status
     */
    public function toggleActive($id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $setting = SellingSettings::findOrFail($id);
            $newStatus = ! $setting->is_active;

            if ($newStatus) {
                // Deactivate other settings for this service/type
                SellingSettings::where('service_id', $setting->service_id)
                    ->where('type', $setting->type)
                    ->where('id', '!=', $id)
                    ->update(['is_active' => false]);
            }

            $setting->update([
                'is_active' => $newStatus,
                'updated_by' => Auth::id(),
            ]);

            $setting->load(['service', 'creator', 'updater']);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Status updated successfully',
                'data' => $setting,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get active percentage for a service
     */
    public function getActivePercentage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'required|exists:services,id',
            'type' => 'required|in:pharmacy,stock',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $percentage = SellingSettings::getActivePercentage(
                $request->service_id,
                $request->type
            );

            return response()->json([
                'status' => 'success',
                'data' => [
                    'percentage' => $percentage,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get active percentage',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all services for dropdown
     */
    public function getServices(): JsonResponse
    {
        try {
            $services = Service::select('id', 'name', 'service_abv')
                ->orderBy('name')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $services,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch services',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
