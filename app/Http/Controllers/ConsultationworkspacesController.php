<?php

namespace App\Http\Controllers;

use App\Models\Consultationworkspace;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\ConsulationResource;
use App\Models\Consultation;
use App\Models\ConsultationworkspaceList;


class ConsultationworkspacesController extends Controller
{
 public function index(Request $request)
{
    $query = Consultationworkspace::where('doctor_id', $request->doctorid)
        ->orderBy('created_at', 'desc');

    // If explicitly requesting archived workspaces
    if ($request->has('is_archived')) {
        $query->where('is_archived', $request->is_archived);
    }

    $workspaces = $query->get();

    return response()->json(['data' => $workspaces]);
}


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'doctor_id' => 'nullable|exists:doctors,id',
            'description' => 'nullable|string',
            'is_archived' => 'nullable|boolean',
            'last_accessed' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $workspace = Consultationworkspace::create([
            'name' => $request->name,
            'description' => $request->description,
            'doctor_id' => $request->doctor_id ?? null,
            'is_archived' => $request->is_archived ?? false,
            'last_accessed' => $request->last_accessed ?? null,
        ]);

        return response()->json(['data' => $workspace], 201);
    }

    public function show(Consultationworkspace $workspace)
    {
        return response()->json(['data' => $workspace]);
    }
public function update(Request $request, $id)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'doctor_id' => 'nullable|exists:doctors,id',
        'is_archived' => 'nullable|boolean',
        'last_accessed' => 'nullable|date'
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $workspace = Consultationworkspace::find($id);

    if (!$workspace) {
        return response()->json(['message' => 'Resource not found'], 404);
    }

    $workspace->update([
        'name' => $request->name,
        'description' => $request->description,
        'doctor_id' => $request->doctor_id ?? $workspace->doctor_id,
        'is_archived' => $request->has('is_archived') ? $request->is_archived : $workspace->is_archived,
        'last_accessed' => $request->last_accessed ?? $workspace->last_accessed,
    ]);

    return response()->json(['data' => $workspace]);
}
   public function destroy($id)
{
    $workspace = Consultationworkspace::find($id);

    if (!$workspace) {
        return response()->json(['message' => 'Resource not found'], 404);
    }

    $workspace->delete();

    return response()->json(null, 204);
}


    public function search(Request $request)
    {
        $query = $request->get('query', '');

        $workspaces = Consultationworkspace::where('name', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->get();

        return response()->json(['data' => $workspaces]);
    }

  
    public function getworkspaceDetails(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'consultation_workspace_id' => 'required|exists:consultationworkspaces,id',
            'consultationId' => 'nullable|exists:consultations,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $consultationWorkspaceId = $request->consultation_workspace_id;

        $consultationsQuery = Consultation::with([
            'patient',
            'doctor',
            'template',
            'placeholderAttributes.placeholder',
            'placeholderAttributes.attribute'
        ])
        ->join('consultationworkspace_lists', 'consultations.id', '=', 'consultationworkspace_lists.consultation_id')
        ->where('consultationworkspace_lists.consultation_workspace_id', $consultationWorkspaceId)
        ->whereHas('appointment', function ($q) {
            $q->where('status', 'DONE')
              ->orWhere('status', 4);
        })
        ->orderBy('consultations.created_at', 'desc')
        ->select('consultations.*', 'consultationworkspace_lists.consultation_workspace_id');

        $consultations = $consultationsQuery->get();

        // Return both the resource and the workspace id for each consultation
        $data = $consultations->map(function ($consultation) {
            $resource = (new \App\Http\Resources\ConsulationResource($consultation))->toArray(request());
            $resource['consultation_workspace_id'] = $consultation->consultation_workspace_id;
            return $resource;
        });

        return response()->json(['data' => $data]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve workspace details: ' . $e->getMessage()
        ], 500);
    }
}
public function DeleteworkspaceDetails(Request $request)
{
    $validator = Validator::make($request->all(), [
        'consultation_id' => 'required|exists:consultationworkspace_lists,consultation_id',
        'consultation_workspace_id' => 'required|exists:consultationworkspace_lists,consultation_workspace_id',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    try {
        $deleted = ConsultationworkspaceList::where('consultation_id', $request->consultation_id)
            ->where('consultation_workspace_id', $request->consultation_workspace_id)
            ->delete();

        if ($deleted) {
            return response()->json(null, 204);
        } else {
            return response()->json(['message' => 'Consultation workspace list entry not found.'], 404);
        }
    } catch (\Exception $e) {
        return response()->json(['message' => 'Failed to delete workspace details: ' . $e->getMessage()], 500);
    }
}
       public function storeworkDetails(Request $request)
    {
        // Validate incoming request data for creating new list entries
        $validator = Validator::make($request->all(), [
            'consultation_id' => 'required|exists:consultations,id', // Must exist in the 'consultations' table
            // 'consultation_workspace_id' was singular, now 'consultation_workspace_ids' is an array
            'consultation_workspace_ids' => 'required|array', // Must be an array
            'consultation_workspace_ids.*' => 'required|integer|exists:consultationworkspaces,id', // Each ID in the array must be an integer and exist in 'consultation_workspaces'
            'notes' => 'nullable|string|max:1000', // Optional notes, max length 1000 characters
        ]);
      

        // If validation fails, return errors with a 422 status code
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $createdEntries = [];
            $consultationId = $request->consultation_id;
            $notes = $request->notes;

            // Iterate over each selected workspace ID and create a new entry
            foreach ($request->consultation_workspace_ids as $workspaceId) {
                // Check if this consultation is already associated with this workspace
                $existingEntry = ConsultationworkspaceList::where('consultation_id', $consultationId)
                                                          ->where('consultation_workspace_id', $workspaceId)
                                                          ->first();

                if (!$existingEntry) {
                    $workspaceListEntry = ConsultationworkspaceList::create([
                        'consultation_id' => $consultationId,
                        'consultation_workspace_id' => $workspaceId,
                    ]);
                    $createdEntries[] = $workspaceListEntry;
                } else {
                    // Optionally, you could update the existing entry or skip it
                    // For now, we'll just skip creating duplicates.
                    // You might want to log this or return a specific message for duplicates.
                    $createdEntries[] = $existingEntry; // Add existing to the response, or handle differently
                }
            }

            // Return all created (or existing unique) entries with a 201 status code
            return response()->json(['data' => $createdEntries], 201);
        } catch (\Exception $e) {
            // Catch any exceptions and return a 500 server error response
            return response()->json(['message' => 'Failed to store workspace details: ' . $e->getMessage()], 500);
        }
    }

}