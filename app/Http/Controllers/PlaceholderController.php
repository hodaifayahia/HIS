<?php

namespace App\Http\Controllers;

use App\Http\Resources\PlaceholderResource;
use App\Models\Placeholder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Attribute;
use App\Models\ConsultationPlaceholderAttributes;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class PlaceholderController extends Controller
{
  // app\Http\Controllers\PlaceholderController.php

 public function index(Request $request)
{
    $query = Placeholder::with(['doctor', 'doctor.user','specializations'])->where('doctor_id', $request->doctor_id);

    if ($request->has('search') && !empty($request->input('search'))) {
        $searchTerm = $request->input('search'); // No leading '%' for better index usage initially

        $query->where(function ($q) use ($searchTerm) {
            // Option 1: Prioritize leading matches for performance
            $q->where('name', 'like', $searchTerm . '%')
              ->orWhere('description', 'like', $searchTerm . '%') // Still not ideal for description if very long
              ->orWhereHas('doctor', function($dq) use ($searchTerm) {
                  $dq->where('name', 'like', $searchTerm . '%');
              })
              ->orWhereHas('specializations', function($sq) use ($searchTerm) {
                  $sq->where('name', 'like', $searchTerm . '%');
              });

            // Option 2 (Less performant for large data, but matches anywhere):
            // Fallback to full wildcard search if leading match doesn't make sense for your use case
            // $fullSearchTerm = '%' . $searchTerm . '%';
            // $q->orWhere('name', 'like', $fullSearchTerm)
            //   ->orWhere('description', 'like', $fullSearchTerm)
            //   ->orWhereHas('doctor', function($dq) use ($fullSearchTerm) {
            //       $dq->where('name', 'like', $fullSearchTerm);
            //   })
            //   ->orWhereHas('specializations', function($sq) use ($fullSearchTerm) {
            //       $sq->where('name', 'like', $fullSearchTerm);
            //   });
        });
    }

    $placeholders = $query->paginate(30);

    return PlaceholderResource::collection($placeholders);
}



    //store
    public function store(Request $request) {
        // Validate the request data
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'doctor_id' => 'required|exists:doctors,id',
            'specializations_id' => 'nullable',
        ]);

        $placeholder = Placeholder::create([
            'name' => $request->name,
            'description' => $request->description,
            'doctor_id' => $request->doctor_id,
            'specializations_id' => $request->specializations_id?? null,
        ]);

        return response()->json($placeholder, 201);
    }
//make the updaet funcaiton 
    public function update(Request $request) {
        // Validate the request data
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'doctor_id' => 'required|exists:doctors,id',
            'specializations_id' => 'nullable',
        ]);

        $placeholder = Placeholder::find($request->id);
        if (!$placeholder) {
            return response()->json(['message' => 'Placeholder not found'], 404);
        }

        $placeholder->update([
            'name' => $request->name,
            'description' => $request->description,
            'doctor_id' => $request->doctor_id,
            'specializations_id' => $request->specializations_id,
        ]);

        return response()->json($placeholder, 200);
    }
    //search 
    public function search(Request $request) {
        $query = Placeholder::query(); // Start with query builder instead of getting all records
    
        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        if ($request->has('description')) {
            $query->where('description', 'like', '%' . $request->description . '%');
        }
        if ($request->has('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }
        if ($request->has('specializations_id')) {
            $query->where('specializations_id', $request->specializations_id);
        }
    
        return $query->paginate(15); // Add pagination to search results
    }
        
    // deleate 
    public function destroy($id) {
        $placeholder = Placeholder::find($id);
        if (!$placeholder) {
            return response()->json(['message' => 'Placeholder not found'], 404);
        }
        
        $placeholder->delete();
        
        return response()->json(['message' => 'Placeholder deleted successfully'], 200);
    }

    public function saveConsultationAttributes(Request $request)
    {
        try {
            $validated = $request->validate([
                'appointment_id' => 'required|integer',
                'attributes' => 'required|array',
                'doctor_id' => 'required|integer',
            ]);

            // Remove the dd() statement
            Log::info('Received data:', $request->all());

            $attributes = $request->input('attributes');
            
            DB::beginTransaction();

            foreach ($attributes as $key => $value) {
                Log::info('Processing:', ['key' => $key, 'value' => $value]);

                // Handle both dot notation and underscore notation
                $parts = str_contains($key, '.') ? explode('.', $key) : explode('_', $key);
                
                if (count($parts) < 2) {
                    Log::warning('Invalid key format:', ['key' => $key]);
                    continue;
                }

                $placeholderName = $parts[0];
                $attributeName = $parts[1];

                $placeholder = Placeholder::where('name', $placeholderName)
                ->where('doctor_id', $validated['doctor_id'])
                ->first();
                if (!$placeholder) {
                    Log::warning('Placeholder not found:', ['name' => $placeholderName]);
                    continue;
                }

                $attribute = Attribute::where('placeholder_id', $placeholder->id)
                ->where('name', $attributeName)
                    ->first();

                if (!$attribute) {
                    Log::warning('Attribute not found:', [
                        'name' => $attributeName,
                        'placeholder_id' => $placeholder->id
                    ]);
                    continue;
                }

                ConsultationPlaceholderAttributes::updateOrCreate(
                    [
                        'appointment_id' => $validated['appointment_id'],
                        'placeholder_id' => $placeholder->id,
                        'attribute_id' => $attribute->id,
                    ],
                    ['attribute_value' => $value]
                );

                Log::info('Saved:', [
                    'placeholder' => $placeholderName,
                    'attribute' => $attributeName,
                    'value' => $value
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Consultation attributes saved successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to save: ' . $e->getMessage()
            ], 500);
        }
    }
 
public function getConsultationPlaceholderAttributes($appointmentid)
{
    try {
        // Validate appointment ID
        if (!$appointmentid || !is_numeric($appointmentid)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid appointment ID'
            ], 400);
        }

        // Check if appointment exists (optional but recommended)
        $appointmentExists = \App\Models\Appointment::where('id', $appointmentid)->exists();
        if (!$appointmentExists) {
            return response()->json([
                'success' => false,
                'message' => 'Appointment not found'
            ], 404);
        }

        // Fetch consultation attributes with proper relationships
        $attributes = ConsultationPlaceholderAttributes::where('appointment_id', $appointmentid)
            ->with(['placeholder', 'attribute'])
            ->get();

        // Check if any attributes exist
        if ($attributes->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'No saved consultation attributes found'
            ]);
        }

        // Group by placeholder name and format the data
        $groupedAttributes = $attributes->groupBy('placeholder.name')
            ->map(function ($group) {
                return $group->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'attribute_name' => $item->attribute->name,
                        'attribute_value' => $item->attribute_value,
                        'input_type' => $item->attribute->input_type,
                        'attribute_id' => $item->attribute->id,
                        'placeholder_id' => $item->placeholder->id,
                        'created_at' => $item->created_at,
                        'updated_at' => $item->updated_at
                    ];
                })->values(); // Remove keys to get a clean array
            });

        return response()->json([
            'success' => true,
            'data' => $groupedAttributes,
            'count' => $attributes->count(),
            'appointment_id' => $appointmentid
        ]);

    } catch (\Exception $e) {
        \Log::error('Error fetching consultation attributes: ' . $e->getMessage(), [
            'appointment_id' => $appointmentid,
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch consultation attributes',
            'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
        ], 500);
    }
}

// Additional helper method to check if consultation data exists
public function hasConsultationData($appointmentid)
{
    try {
        $count = ConsultationPlaceholderAttributes::where('appointment_id', $appointmentid)->count();
        
        return response()->json([
            'success' => true,
            'has_data' => $count > 0,
            'count' => $count
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to check consultation data'
        ], 500);
    }
}

// Method to clear consultation data (useful for testing)
public function clearConsultationData($appointmentid)
{
    try {
        $deleted = ConsultationPlaceholderAttributes::where('appointment_id', $appointmentid)->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Consultation data cleared successfully',
            'deleted_count' => $deleted
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to clear consultation data'
        ], 500);
    }
}
}