<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ServiceGroupController extends Controller
{
    /**
     * Display a listing of service groups
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = ServiceGroup::with(['services', 'creator', 'updater']);

            // Filter by type
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            // Search
            if ($request->has('search') && ! empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Sort
            $sortField = $request->get('sort_by', 'sort_order');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortField, $sortOrder);

            $groups = $query->get();

            return response()->json([
                'status' => 'success',
                'data' => $groups,
                'total' => $groups->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch service groups',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created service group
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'code' => 'nullable|string|max:50|unique:service_groups,code',
            'color' => 'nullable|string|max:7',
            'type' => 'required|string|in:stock,pharmacy',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'exists:services,id',
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

            $group = ServiceGroup::create([
                'name' => $request->name,
                'description' => $request->description,
                'code' => $request->code,
                'color' => $request->color ?? '#3B82F6',
                'type' => $request->type,
                'is_active' => $request->is_active ?? true,
                'sort_order' => $request->sort_order ?? 0,
                'created_by' => Auth::id(),
            ]);

            // Attach services if provided
            if ($request->has('service_ids') && ! empty($request->service_ids)) {
                $group->syncServices($request->service_ids);
            }

            $group->load(['services', 'creator']);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Service group created successfully',
                'data' => $group,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create service group',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified service group
     */
    public function show($id): JsonResponse
    {
        try {
            $group = ServiceGroup::with(['services', 'creator', 'updater', 'sellingSettings'])
                ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $group,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Service group not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Update the specified service group
     */
    public function update(Request $request, ServiceGroup $serviceGroup): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:service_groups,name,' . $serviceGroup->id,
            'description' => 'nullable|string',
            'code' => 'nullable|string|max:50|unique:service_groups,code,' . $serviceGroup->id,
            'color' => 'nullable|string|max:7',
            'type' => 'required|string|in:stock,pharmacy',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'exists:services,id',
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

            $serviceGroup->update([
                'name' => $request->name,
                'description' => $request->description,
                'code' => $request->code,
                'color' => $request->color,
                'type' => $request->type,
                'is_active' => $request->is_active ?? true,
                'sort_order' => $request->sort_order ?? 0,
                'updated_by' => Auth::id(),
            ]);

            // Sync services if provided
            if ($request->has('service_ids')) {
                $serviceGroup->syncServices($request->service_ids);
            }

            $serviceGroup->load(['services', 'creator']);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Service group updated successfully',
                'data' => $serviceGroup,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update service group',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified service group
     */
    public function destroy($id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $group = ServiceGroup::findOrFail($id);

            // Check if group has associated selling settings
            if ($group->sellingSettings()->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete group with associated selling settings. Please delete the settings first.',
                ], 422);
            }

            $group->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Service group deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete service group',
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
            $group = ServiceGroup::findOrFail($id);

            $group->update([
                'is_active' => ! $group->is_active,
                'updated_by' => Auth::id(),
            ]);

            $group->load(['services', 'creator', 'updater']);

            return response()->json([
                'status' => 'success',
                'message' => 'Status updated successfully',
                'data' => $group,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add a service to the group
     */
    public function addService(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'required|exists:services,id',
            'sort_order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $group = ServiceGroup::findOrFail($id);

            if ($group->hasService($request->service_id)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Service already exists in this group',
                ], 422);
            }

            $group->addService($request->service_id, $request->sort_order);
            $group->load(['services']);

            return response()->json([
                'status' => 'success',
                'message' => 'Service added successfully',
                'data' => $group,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add service',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove a service from the group
     */
    public function removeService(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'required|exists:services,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $group = ServiceGroup::findOrFail($id);
            $group->removeService($request->service_id);
            $group->load(['services']);

            return response()->json([
                'status' => 'success',
                'message' => 'Service removed successfully',
                'data' => $group,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to remove service',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available services (not in any group)
     */
    public function getAvailableServices(): JsonResponse
    {
        try {
            $services = Service::whereDoesntHave('serviceGroups')
                ->select('id', 'name', 'service_abv')
                ->orderBy('name')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $services,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch available services',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all services (for dropdown)
     */
    public function getAllServices(): JsonResponse
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

    /**
     * Reorder services in a group
     */
    public function reorderServices(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service_ids' => 'required|array',
            'service_ids.*' => 'exists:services,id',
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

            $group = ServiceGroup::findOrFail($id);
            $group->syncServices($request->service_ids);
            $group->load(['services']);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Services reordered successfully',
                'data' => $group,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reorder services',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
