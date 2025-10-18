<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\ConsultationPlaceholderAttributes;
use App\Models\AttributesPlaceholderDoctor;
use App\Models\Placeholder;
use App\Models\Doctor;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AttributeController extends Controller
{
   public function index($placeholder_id)
{
    \Log::debug("Fetching attributes for placeholder: {$placeholder_id}");
    try {
        $attributes = Attribute::with(['placeholder', 'doctor'])
            ->where('placeholder_id', $placeholder_id)
            ->orderByDesc('created_at')
            ->get();
        
        \Log::debug("Found attributes:", $attributes->toArray());
        
        return response()->json([
            'success' => true,
            'data' => $attributes,
        ]);
    } catch (\Exception $e) {
        \Log::error("Attribute error: " . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve attributes',
            'error' => $e->getMessage()
        ], 500);
    }
}
     public function getMetadata(Request $request)
    {
        try {
            $request->validate([
                'attribute_name' => 'required|string',
                'doctor_id' => 'required|integer'
            ]);

            $attributeName = $request->input('attribute_name');
            $doctorId = $request->input('doctor_id');

            // Skip PatientInfo attributes as they don't need database lookup
            if (str_starts_with($attributeName, 'PatientInfo.')) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'attribute_name' => $attributeName,
                        'input_type' => 1, // Default to text input for patient info
                        'is_required' => false
                    ]
                ]);
            }

            // Query the attributes table to get metadata
            $attribute = DB::table('attributes')
                ->where('name', $attributeName)
                ->where('doctor_id', $doctorId)
                ->first();

            if (!$attribute) {
                // If attribute doesn't exist, create it with default values
                $attributeId = DB::table('attributes')->insertGetId([
                    'name' => $attributeName,
                    'doctor_id' => $doctorId,
                    'input_type' => 1, // Default to text input
                    'is_required' => false,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'id' => $attributeId,
                        'attribute_name' => $attributeName,
                        'input_type' => 1,
                        'is_required' => false
                    ]
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $attribute->id,
                    'attribute_name' => $attribute->name,
                    'input_type' => (int) $attribute->input_type, // Ensure it's an integer
                    'is_required' => (bool) $attribute->is_required
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching attribute metadata: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch attribute metadata',
                'error' => $e->getMessage()
            ], 500);
        }
    }
public function searchAttributeValues(Request $request)
{
    $attributeId = $request->input('attribute_id');
    $query = $request->input('query', '');
    // dd($attributeId);
    $values = ConsultationPlaceholderAttributes::where('attribute_id', $attributeId)
        ->distinct()
        ->pluck('attribute_value');

    return response()->json([
        'success' => true,
        'data' => $values,
    ]);
}

    /**
     * Store a newly created attribute
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'value' => 'nullable|string|max:255',
            'input_type' => 'required|boolean', // Ensure input_type is boolean
            'placeholder_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
                'message' => 'Validation failed'
            ], 422);
        }

        $attribute = Attribute::create([
            'name' => $request->name,
            'value' => $request->value ?? null,
            'input_type' => filter_var($request->input_type, FILTER_VALIDATE_BOOLEAN), // Convert to boolean
            'placeholder_id' => $request->placeholder_id
        ]);

        return response()->json([
            'data' => $attribute,
            'message' => 'Attribute created successfully'
        ], 201);
    }

    /**
     * Update the specified attribute
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'value' => 'nullable|string|max:255',
            'input_type' => 'nullable|boolean', // Ensure input_type is boolean
            'placeholder_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
                'message' => 'Validation failed'
            ], 422);
        }

        try {
            $attribute = Attribute::findOrFail($id);
            $attribute->update([
                'name' => $request->get('name', $attribute->name),
                'value' => $request->get('value', $attribute->value),
                'input_type' => filter_var($request->input_type, FILTER_VALIDATE_BOOLEAN), // Convert to boolean

                'placeholder_id' => $request->get('placeholder_id', $attribute->placeholder_id)
            ]);
    
            return response()->json([
                'data' => $attribute,
                'message' => 'Attribute updated successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Attribute not found'
            ], 404);
        }
    }
    //search
    /**
     * Search for attributes by name
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $query = $request->input('query');
        $attributes = Attribute::where('name', 'LIKE', "%{$query}%")->get();

        return response()->json([
            'data' => $attributes,
            'message' => 'Attributes retrieved successfully'
        ]);
    }

    /**
     * Remove the specified attribute
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $attribute = Attribute::findOrFail($id);
            $attribute->delete();
    
            return response()->json([
                'message' => 'Attribute deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Attribute not found'
            ], 404);
        }
    }
}