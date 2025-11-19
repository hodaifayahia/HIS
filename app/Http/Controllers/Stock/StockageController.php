<?php

namespace App\Http\Controllers\Stock;

use App\Models\Stockage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class StockageController extends \App\Http\Controllers\Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Stockage::with('service:id,name');

        // Search functionality
        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        // Filter by type
        if ($request->has('type') && ! empty($request->type)) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->has('status') && ! empty($request->status)) {
            $query->where('status', $request->status);
        }

        // Filter by service
        if ($request->has('service_id') && ! empty($request->service_id)) {
            $query->where('service_id', $request->service_id);
        }

        // Pagination
        $perPage = $request->get('per_page', 10);
        $stockages = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $stockages->items(),
            'meta' => [
                'current_page' => $stockages->currentPage(),
                'last_page' => $stockages->lastPage(),
                'per_page' => $stockages->perPage(),
                'total' => $stockages->total(),
                'from' => $stockages->firstItem(),
                'to' => $stockages->lastItem(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'required|string|max:255',
            'capacity' => 'nullable|integer|min:1',
            'type' => ['required', Rule::in(['warehouse', 'pharmacy', 'laboratory', 'emergency', 'storage_room', 'cold_room'])],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'maintenance', 'under_construction'])],
            'service_id' => 'nullable|exists:services,id',
            'temperature_controlled' => 'boolean',
            'security_level' => ['nullable', Rule::in(['low', 'medium', 'high', 'restricted'])],
            'location_code' => 'nullable|string|max:255',
            'warehouse_type' => ['nullable', Rule::in(['Central Pharmacy (PC)', 'Service Pharmacy (PS)'])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->all();

        // Set default status if not provided
        if (! isset($data['status'])) {
            $data['status'] = 'active';
        }

        // Set default security level if not provided
        if (! isset($data['security_level'])) {
            $data['security_level'] = 'medium';
        }

        $stockage = Stockage::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Stockage created successfully',
            'data' => $stockage->load('service:id,name'),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Stockage $stockage)
    {
        return response()->json([
            'success' => true,
            'data' => $stockage->load('service:id,name'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Stockage $stockage)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'required|string|max:255',
            'capacity' => 'nullable|integer|min:1',
            'type' => ['required', Rule::in(['warehouse', 'pharmacy', 'laboratory', 'emergency', 'storage_room', 'cold_room'])],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'maintenance', 'under_construction'])],
            'service_id' => 'nullable|exists:services,id',
            'temperature_controlled' => 'boolean',
            'security_level' => ['nullable', Rule::in(['low', 'medium', 'high', 'restricted'])],
            'location_code' => 'nullable|string|max:255',
            'warehouse_type' => ['nullable', Rule::in(['Central Pharmacy (PC)', 'Service Pharmacy (PS)'])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $stockage->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Stockage updated successfully',
            'data' => $stockage->load('service:id,name'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Stockage $stockage)
    {
        $stockage->delete();

        return response()->json([
            'success' => true,
            'message' => 'Stockage deleted successfully',
        ]);
    }

    /**
     * Get available managers for dropdown
     */
    public function getManagers()
    {
        // Return users whose role is 'manager'. Adjust roles if you need admins/superadmins here.
        $managers = User::where('role', 'manager')
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $managers,
        ]);
    }
}
