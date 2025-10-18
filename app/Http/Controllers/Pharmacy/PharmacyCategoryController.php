<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\Pharmacy\PharmacyCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PharmacyCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = PharmacyCategory::query();

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        }

        // Filter by controlled substance category
        if ($request->has('controlled_only') && $request->controlled_only) {
            $query->where('is_controlled_substance', true);
        }

        // Filter by prescription requirement
        if ($request->has('prescription_only') && $request->prescription_only) {
            $query->where('requires_prescription', true);
        }

        // Filter by storage requirements
        if ($request->has('storage_type') && $request->storage_type) {
            $query->where('storage_requirements', 'like', "%{$request->storage_type}%");
        }

        $categories = $query->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
            'meta' => [
                'total' => $categories->count(),
                'controlled_count' => $categories->where('is_controlled_substance', true)->count(),
                'prescription_count' => $categories->where('requires_prescription', true)->count()
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:pharmacy_categories',
            'description' => 'nullable|string|max:500',
            'is_controlled_substance' => 'boolean',
            'controlled_substance_schedule' => 'nullable|string|in:I,II,III,IV,V',
            'requires_prescription' => 'boolean',
            'storage_requirements' => 'nullable|string|max:255',
            'temperature_range_min' => 'nullable|numeric',
            'temperature_range_max' => 'nullable|numeric',
            'requires_refrigeration' => 'boolean',
            'requires_freezing' => 'boolean',
            'light_sensitive' => 'boolean',
            'humidity_sensitive' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Validate controlled substance schedule
        if ($request->is_controlled_substance && !$request->controlled_substance_schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Controlled substance schedule is required for controlled substances'
            ], 422);
        }

        // Validate temperature ranges
        if ($request->temperature_range_min && $request->temperature_range_max) {
            if ($request->temperature_range_min >= $request->temperature_range_max) {
                return response()->json([
                    'success' => false,
                    'message' => 'Minimum temperature must be less than maximum temperature'
                ], 422);
            }
        }

        $category = PharmacyCategory::create($request->all());

        return response()->json([
            'success' => true,
            'data' => $category,
            'message' => 'Pharmacy category created successfully'
        ], 201);
    }

    public function show(PharmacyCategory $category)
    {
        $category->load(['products' => function($query) {
            $query->select('id', 'name', 'category_id', 'is_controlled_substance', 'requires_prescription');
        }]);

        return response()->json([
            'success' => true,
            'data' => $category,
            'meta' => [
                'products_count' => $category->products->count(),
                'controlled_products_count' => $category->products->where('is_controlled_substance', true)->count()
            ]
        ]);
    }

    public function update(Request $request, PharmacyCategory $category)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:pharmacy_categories,name,' . $category->id,
            'description' => 'nullable|string|max:500',
            'is_controlled_substance' => 'boolean',
            'controlled_substance_schedule' => 'nullable|string|in:I,II,III,IV,V',
            'requires_prescription' => 'boolean',
            'storage_requirements' => 'nullable|string|max:255',
            'temperature_range_min' => 'nullable|numeric',
            'temperature_range_max' => 'nullable|numeric',
            'requires_refrigeration' => 'boolean',
            'requires_freezing' => 'boolean',
            'light_sensitive' => 'boolean',
            'humidity_sensitive' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Validate controlled substance schedule
        if ($request->is_controlled_substance && !$request->controlled_substance_schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Controlled substance schedule is required for controlled substances'
            ], 422);
        }

        // Validate temperature ranges
        if ($request->temperature_range_min && $request->temperature_range_max) {
            if ($request->temperature_range_min >= $request->temperature_range_max) {
                return response()->json([
                    'success' => false,
                    'message' => 'Minimum temperature must be less than maximum temperature'
                ], 422);
            }
        }

        $category->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $category,
            'message' => 'Pharmacy category updated successfully'
        ]);
    }

    public function destroy(PharmacyCategory $category)
    {
        // Check if category is being used
        if ($category->products()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category that has associated pharmacy products'
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pharmacy category deleted successfully'
        ]);
    }

    /**
     * Get controlled substance categories
     */
    public function getControlledSubstances()
    {
        $categories = PharmacyCategory::where('is_controlled_substance', true)
            ->orderBy('controlled_substance_schedule')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Get categories requiring prescription
     */
    public function getPrescriptionRequired()
    {
        $categories = PharmacyCategory::where('requires_prescription', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Get categories by storage requirements
     */
    public function getByStorageRequirements(Request $request)
    {
        $query = PharmacyCategory::query();

        if ($request->has('refrigerated') && $request->refrigerated) {
            $query->where('requires_refrigeration', true);
        }

        if ($request->has('frozen') && $request->frozen) {
            $query->where('requires_freezing', true);
        }

        if ($request->has('light_sensitive') && $request->light_sensitive) {
            $query->where('light_sensitive', true);
        }

        if ($request->has('humidity_sensitive') && $request->humidity_sensitive) {
            $query->where('humidity_sensitive', true);
        }

        $categories = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }
}