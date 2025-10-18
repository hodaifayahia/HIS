<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\PharmacyStorage;
use App\Models\PharmacyStorageTool;
use App\Models\PharmacyStockage;
use App\Models\PharmacyInventory;
use App\Models\CONFIGURATION\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PharmacyStorageController extends Controller
{
    /**
     * Display a listing of pharmacy storage facilities.
     */
    public function index(Request $request)
    {
        $query = PharmacyStorage::with(['service', 'stockages.inventories']);

        // Filter by service
        if ($request->has('service_id') && $request->service_id) {
            $query->where('service_id', $request->service_id);
        }

        // Filter by storage type
        if ($request->has('storage_type') && $request->storage_type) {
            $query->where('storage_type', $request->storage_type);
        }

        // Filter by status
        if ($request->has('is_active') && $request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $storages = $query->orderBy('name', 'asc')
                         ->paginate($request->get('per_page', 15));

        return response()->json($storages);
    }

    /**
     * Store a newly created pharmacy storage facility.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'required|exists:services,id',
            'name' => 'required|string|max:100',
            'location' => 'required|string|max:200',
            'storage_type' => 'required|in:refrigerator,freezer,room_temperature,controlled_substance,hazardous,general',
            'description' => 'nullable|string|max:500',
            'capacity' => 'nullable|numeric|min:0',
            'temperature_min' => 'nullable|numeric',
            'temperature_max' => 'nullable|numeric',
            'humidity_min' => 'nullable|numeric|min:0|max:100',
            'humidity_max' => 'nullable|numeric|min:0|max:100',
            'security_level' => 'required|in:low,medium,high,maximum',
            'access_restrictions' => 'nullable|string|max:1000',
            'maintenance_schedule' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $storage = PharmacyStorage::create($request->all());

            return response()->json([
                'message' => 'Pharmacy storage created successfully',
                'storage' => $storage->load('service')
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create pharmacy storage: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified pharmacy storage facility.
     */
    public function show($id)
    {
        $storage = PharmacyStorage::with([
            'service',
            'stockages.inventories.product',
            'tools'
        ])->findOrFail($id);

        return response()->json($storage);
    }

    /**
     * Update the specified pharmacy storage facility.
     */
    public function update(Request $request, $id)
    {
        $storage = PharmacyStorage::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:100',
            'location' => 'sometimes|string|max:200',
            'storage_type' => 'sometimes|in:refrigerator,freezer,room_temperature,controlled_substance,hazardous,general',
            'description' => 'nullable|string|max:500',
            'capacity' => 'nullable|numeric|min:0',
            'temperature_min' => 'nullable|numeric',
            'temperature_max' => 'nullable|numeric',
            'humidity_min' => 'nullable|numeric|min:0|max:100',
            'humidity_max' => 'nullable|numeric|min:0|max:100',
            'security_level' => 'sometimes|in:low,medium,high,maximum',
            'access_restrictions' => 'nullable|string|max:1000',
            'maintenance_schedule' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $storage->update($request->all());

            return response()->json([
                'message' => 'Pharmacy storage updated successfully',
                'storage' => $storage->load('service')
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update pharmacy storage: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified pharmacy storage facility.
     */
    public function destroy($id)
    {
        $storage = PharmacyStorage::findOrFail($id);

        // Check if storage has active stockages
        if ($storage->stockages()->exists()) {
            return response()->json(['error' => 'Cannot delete storage with existing stockages'], 422);
        }

        try {
            $storage->delete();

            return response()->json(['message' => 'Pharmacy storage deleted successfully']);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete pharmacy storage: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get storage capacity utilization.
     */
    public function getCapacityUtilization($id)
    {
        $storage = PharmacyStorage::with(['stockages.inventories'])->findOrFail($id);

        $totalItems = $storage->stockages->sum(function($stockage) {
            return $stockage->inventories->sum('quantity');
        });

        $utilization = [
            'storage_id' => $storage->id,
            'storage_name' => $storage->name,
            'capacity' => $storage->capacity,
            'current_items' => $totalItems,
            'utilization_percentage' => $storage->capacity ? round(($totalItems / $storage->capacity) * 100, 2) : null,
            'available_space' => $storage->capacity ? max(0, $storage->capacity - $totalItems) : null,
        ];

        return response()->json($utilization);
    }

    /**
     * Get storage environmental conditions.
     */
    public function getEnvironmentalConditions($id)
    {
        $storage = PharmacyStorage::findOrFail($id);

        $conditions = [
            'storage_id' => $storage->id,
            'storage_name' => $storage->name,
            'storage_type' => $storage->storage_type,
            'temperature_range' => [
                'min' => $storage->temperature_min,
                'max' => $storage->temperature_max,
            ],
            'humidity_range' => [
                'min' => $storage->humidity_min,
                'max' => $storage->humidity_max,
            ],
            'security_level' => $storage->security_level,
            'access_restrictions' => $storage->access_restrictions,
        ];

        return response()->json($conditions);
    }

    /**
     * Get storage tools.
     */
    public function getTools($id)
    {
        $storage = PharmacyStorage::findOrFail($id);
        $tools = PharmacyStorageTool::where('pharmacy_storage_id', $id)
                                   ->orderBy('name', 'asc')
                                   ->get();

        return response()->json([
            'storage' => $storage->only(['id', 'name']),
            'tools' => $tools
        ]);
    }

    /**
     * Add tool to storage.
     */
    public function addTool(Request $request, $id)
    {
        $storage = PharmacyStorage::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'tool_type' => 'required|in:thermometer,hygrometer,scale,barcode_scanner,refrigeration_unit,security_camera,access_control,other',
            'description' => 'nullable|string|max:500',
            'serial_number' => 'nullable|string|max:100',
            'manufacturer' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'installation_date' => 'nullable|date',
            'last_maintenance_date' => 'nullable|date',
            'next_maintenance_date' => 'nullable|date|after:last_maintenance_date',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $tool = PharmacyStorageTool::create(array_merge(
                $request->all(),
                ['pharmacy_storage_id' => $id]
            ));

            return response()->json([
                'message' => 'Storage tool added successfully',
                'tool' => $tool
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to add storage tool: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update storage tool.
     */
    public function updateTool(Request $request, $storageId, $toolId)
    {
        $tool = PharmacyStorageTool::where('pharmacy_storage_id', $storageId)
                                  ->where('id', $toolId)
                                  ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:100',
            'tool_type' => 'sometimes|in:thermometer,hygrometer,scale,barcode_scanner,refrigeration_unit,security_camera,access_control,other',
            'description' => 'nullable|string|max:500',
            'serial_number' => 'nullable|string|max:100',
            'manufacturer' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'installation_date' => 'nullable|date',
            'last_maintenance_date' => 'nullable|date',
            'next_maintenance_date' => 'nullable|date|after:last_maintenance_date',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $tool->update($request->all());

            return response()->json([
                'message' => 'Storage tool updated successfully',
                'tool' => $tool
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update storage tool: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove tool from storage.
     */
    public function removeTool($storageId, $toolId)
    {
        $tool = PharmacyStorageTool::where('pharmacy_storage_id', $storageId)
                                  ->where('id', $toolId)
                                  ->firstOrFail();

        try {
            $tool->delete();

            return response()->json(['message' => 'Storage tool removed successfully']);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to remove storage tool: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get storage statistics.
     */
    public function getStatistics(Request $request)
    {
        $serviceId = $request->get('service_id');
        
        $query = PharmacyStorage::query();
        
        if ($serviceId) {
            $query->where('service_id', $serviceId);
        }

        $stats = [
            'total_storages' => $query->count(),
            'active_storages' => $query->where('is_active', true)->count(),
            'storage_types' => $query->select('storage_type', DB::raw('count(*) as count'))
                                   ->groupBy('storage_type')
                                   ->get(),
            'total_capacity' => $query->sum('capacity'),
            'average_utilization' => $this->calculateAverageUtilization($query->get()),
        ];

        return response()->json($stats);
    }

    /**
     * Calculate average utilization across storages.
     */
    private function calculateAverageUtilization($storages)
    {
        $totalUtilization = 0;
        $storagesWithCapacity = 0;

        foreach ($storages as $storage) {
            if ($storage->capacity) {
                $totalItems = $storage->stockages->sum(function($stockage) {
                    return $stockage->inventories->sum('quantity');
                });
                $utilization = ($totalItems / $storage->capacity) * 100;
                $totalUtilization += $utilization;
                $storagesWithCapacity++;
            }
        }

        return $storagesWithCapacity > 0 ? round($totalUtilization / $storagesWithCapacity, 2) : 0;
    }
}