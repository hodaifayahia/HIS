<?php

namespace App\Http\Controllers;

use App\Models\MedicationDoctorFavorat;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MedicationDoctorFavoratController extends Controller
{

      public function toggleFavorite(Request $request)
    {
        try {
            $validated = $request->validate([
                'medication_id' => 'required|exists:medications,id',
                'doctor_id' => 'required|exists:doctors,id'
            ]);

            $favorite = MedicationDoctorFavorat::where([
                'medication_id' => $validated['medication_id'],
                'doctor_id' => $validated['doctor_id']
            ])->first();

            if ($favorite) {
                // If exists, remove from favorites
                $favorite->delete();
                return response()->json([
                    'message' => 'Removed from favorites',
                    'is_favorite' => false
                ]);
            } else {
                // If doesn't exist, add to favorites
                $favorite = MedicationDoctorFavorat::create([
                    'medication_id' => $validated['medication_id'],
                    'doctor_id' => $validated['doctor_id'],
                    'favorited_at' => Carbon::now()
                ]);

                return response()->json([
                    'message' => 'Added to favorites',
                    'is_favorite' => true,
                    'favorite' => $favorite
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to toggle favorite status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Display a listing of the resource.
     */
   public function index(Request $request)
    {
        try {
            // 1. Validate doctor_id from request
            $validated = $request->validate([
                'doctor_id' => ['required', 'integer', 'exists:doctors,id']
            ]);

            $doctorId = $validated['doctor_id'];
            $searchQuery = $request->input('search'); // Get the search query from the request

            // Start building the query on MedicationDoctorFavorat
            $favoritedMedicationsQuery = MedicationDoctorFavorat::where('doctor_id', $doctorId)
                ->with(['medication' => function ($query) use ($searchQuery) {
                    // Apply search filter ONLY to the eager-loaded medication
                    if ($searchQuery) {
                        $searchTerm = '%' . $searchQuery . '%';
                        $query->where(function ($q) use ($searchTerm) {
                            $q->where('designation', 'like', $searchTerm)
                              ->orWhere('nom_commercial', 'like', $searchTerm)
                              ->orWhere('forme', 'like', $searchTerm); // Ensure these columns exist in your 'medications' table
                        });
                    }
                }]);

            // Get the results
            $favoritedMedications = $favoritedMedicationsQuery->get();

            // Transform the collection to return only the medication details
            // Filter out any favorite entries where the associated medication was not found
            // (e.g., if the search query filtered out the medication during eager loading)
            $medications = $favoritedMedications->map(function($favorat) {
                return $favorat->medication;
            })->filter()->values(); // filter() removes nulls (medications not matching search), values() re-indexes

            return response()->json([
                'success' => true,
                'data' => $medications,
                'message' => 'Favorited medications retrieved successfully.'
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422); // Unprocessable Entity
        } catch (\Exception $e) {
            // Catch any other unexpected errors
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred: ' . $e->getMessage()
            ], 500); // Internal Server Error
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(MedicationDoctorFavorat $medicationDoctorFavorat)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MedicationDoctorFavorat $medicationDoctorFavorat)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MedicationDoctorFavorat $medicationDoctorFavorat)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MedicationDoctorFavorat $medicationDoctorFavorat)
    {
        //
    }
}
