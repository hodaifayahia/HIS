<?php

namespace App\Http\Controllers;

use App\Models\Medication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

use App\Models\MedicationDoctorFavorat;
class MedicationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 20);
            $search = $request->input('search');
            
            $query = Medication::query();
            
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('designation', 'like', "%{$search}%")
                      ->orWhere('nom_commercial', 'like', "%{$search}%")
                      ->orWhere('type_medicament', 'like', "%{$search}%");
                });
            }
            
            $medications = $query->orderBy('created_at', 'desc')->paginate($perPage);
            
            // Transform dates to proper format
            $items = $medications->items();
            foreach ($items as &$item) {
                $item->created_at = $item->created_at ? $item->created_at->format('Y-m-d H:i:s') : null;
                $item->updated_at = $item->updated_at ? $item->updated_at->format('Y-m-d H:i:s') : null;
            }

            return response()->json([
                'data' => $items,
                'meta' => [
                    'current_page' => $medications->currentPage(),
                    'last_page' => $medications->lastPage(),
                    'per_page' => $medications->perPage(),
                    'total' => $medications->total()
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Medication index error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch medications',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'designation' => 'required|string|max:255',
                'type_medicament' => 'required|string|max:255',
                'forme' => 'required|string|max:255',
                'boite_de' => 'nullable|string|max:255',
                'code_pch' => 'nullable|string|max:255',
                'nom_commercial' => 'required|string|max:255',
            ]);

            $medication = Medication::create($validatedData);
            MedicationDoctorFavorat::create([
                'medication_id' => $medication->id,
                'doctor_id' =>  $request->doctor_id,
                'favorited_at' => now(),
            ]);
            // add this medcaiton to favrrite medications
            return response()->json([
                'message' => 'Medication created successfully',
                'data' => $medication
            ], Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation Error',
                'errors' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create medication',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        // Find the medication by its ID
        $medication = Medication::find($id);

        // If the medication is not found, return a 404 Not Found response
        if (!$medication) {
            return response()->json(['message' => 'Medication not found'], Response::HTTP_NOT_FOUND);
        }

        // Return a JSON response with the medication and a 200 OK status
        return response()->json($medication, Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
  
public function update(Request $request, $id)
{
    try {
        $medication = Medication::findOrFail($id);
        
        $validatedData = $request->validate([
            'designation' => 'required|string|max:255',
            'type_medicament' => 'required|string|max:255',
            'forme' => 'required|string|max:255',
            'boite_de' => 'nullable|string|max:255',
            'code_pch' => 'nullable|string|max:255',
            'nom_commercial' => 'required|string|max:255',
        ]);

        $medication->update($validatedData);

        return response()->json([
            'message' => 'Medication updated successfully',
            'data' => $medication
        ]);

    } catch (ModelNotFoundException $e) {
        return response()->json([
            'message' => 'Medication not found'
        ], 404);
    } catch (ValidationException $e) {
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Failed to update medication',
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        // Find the medication by its ID
        $medication = Medication::find($id);

        // If the medication is not found, return a 404 Not Found response
        if (!$medication) {
            return response()->json(['message' => 'Medication not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            // Delete the medication
            $medication->delete();

            // Return a 204 No Content response for successful deletion
            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            // Catch any exceptions during deletion and return a 500 Internal Server Error
            return response()->json([
                'message' => 'Failed to delete medication',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
