<?php

namespace App\Http\Controllers\CONFIGURATION;

use App\Http\Controllers\Controller;
use App\Services\CONFIGURATION\PrestationService;
use App\Services\CONFIGURATION\ImportService;
use App\Models\CONFIGURATION\Prestation;
use App\Http\Requests\CONFIGURATION\PrestationRequest;
use App\Http\Resources\PrestationResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Validators\ValidationException;

class PrestationController extends Controller
{
    protected $prestationService;
    protected $importService;

    public function __construct(PrestationService $prestationService, ImportService $importService)
    {
        $this->prestationService = $prestationService;
        $this->importService = $importService;
    }

    /**
     * Display a listing of prestations
     */
    public function index(Request $request)
    {
       
        try {

            $prestations = $this->prestationService->getPrestations($request);
            //  dd($prestations);
            return PrestationResource::collection($prestations);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching prestations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created prestation
     */
    public function store(PrestationRequest $request)
    {
        try {
            $prestation = $this->prestationService->createPrestation($request);

            return response()->json([
                'message' => 'Prestation created successfully',
                'data' => new PrestationResource($prestation)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating prestation',
                'error' => $e->getMessage()
            ], 500);
        }
    }
  

    /**
     * Display the specified prestation
     */
    public function show($id)
    {
        try {
            $prestation = Prestation::with(['service', 'specialization', 'modalityType'])->findOrFail($id);
            return new PrestationResource($prestation);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Prestation not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified prestation
     */
    public function update(PrestationRequest $request, $id)
    {
        try {
            $prestation = $this->prestationService->updatePrestation($request, $id);

            return response()->json([
                'message' => 'Prestation updated successfully',
                'data' => new PrestationResource($prestation)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating prestation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import prescriptions
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file',
        ]);

        try {
            $result = $this->importService->importPrescriptions($request);

            if (!$result['success']) {
                return response()->json([
                    'message' => $result['message'],
                    'failures' => $result['failures']
                ], 422);
            }

            return response()->json(['message' => $result['message']]);
        } catch (ValidationException $e) {
            $failures = $e->failures();
            $errorMessages = [];
            foreach ($failures as $failure) {
                $row = $failure->row();
                $attribute = $failure->attribute();
                $errors = implode(', ', $failure->errors());
                $errorMessages[] = "Row {$row}, Column '{$attribute}': {$errors}";
            }
            Log::error('Excel Import Validation Error:', ['errors' => $errorMessages]);
            return response()->json([
                'message' => 'Validation errors occurred during import.',
                'errors' => $errorMessages
            ], 422);
        } catch (\Exception $e) {
            Log::error('Excel Import General Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'An unexpected error occurred during import: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified prestation
     */
    public function destroy($id)
    {
        try {
            $this->prestationService->deletePrestation($id);
            return response()->json(['message' => 'Prestation deleted successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error deleting prestation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle prestation active status
     */
    public function toggleStatus($id)
    {
        try {
            $prestation = $this->prestationService->togglePrestationStatus($id);
            return response()->json([
                'message' => 'Prestation status updated successfully',
                'data' => new PrestationResource($prestation)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating prestation status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get filter options
     */
    public function getFilterOptions()
    {
        try {
            $options = $this->prestationService->getFilterOptions();
            return response()->json($options);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching filter options',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get prestation statistics
     */
    public function getStatistics()
    {
        try {
            $stats = $this->prestationService->getStatistics();
            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Keep the simple getter methods in the controller for convenience
    public function getServices()
    {
        try {
            $services = Service::where('is_active', true)
                              ->select('id', 'name')
                              ->orderBy('name')
                              ->get();
            return response()->json($services);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching services',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getSpecializations()
    {
        try {
            $specializations = Specialization::select('id', 'name')
                                           ->orderBy('name')
                                           ->get();
            return response()->json(['data' => $specializations]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching specializations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getModalityTypes()
    {
        try {
            $modalityTypes = ModalityType::where('is_active', true)
                                       ->select('id', 'name')
                                       ->orderBy('name')
                                       ->get();
            return response()->json(['data' => $modalityTypes]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching modality types',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
