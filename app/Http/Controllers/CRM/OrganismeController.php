<?php

namespace App\Http\Controllers\CRM;

use App\Models\CRM\Organisme;
use App\Http\Controllers\Controller;

use App\Http\Requests\CRM\OrganismeStoreRequest;
use App\Http\Requests\CRM\OrganismeUpdateRequest;
use App\Http\Resources\CRM\OrganisemResource;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrganismeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Use pagination to avoid loading too many records at once
        // You can specify the number of items per page, e.g., paginate(15)
        $organismes = Organisme::paginate(15); 
        return OrganisemResource::collection($organismes); 
    }

    public function store(OrganismeStoreRequest $request)
    {
        $validatedData = $request->validated();
        $organisme = Organisme::create($validatedData);
        // Ensure the resource is loaded with any necessary relationships if your OrganisemResource uses them
        // $organisme->load('relationship_name'); 
        return new OrganisemResource($organisme); 
    }

    public function show(Organisme $organisme)
    {
        // Eager load any relationships defined in your OrganisemResource to prevent N+1 query problems
        // For example, if your OrganisemResource includes data from a 'contacts' relationship:
        // $organisme->load('contacts'); 
        return new OrganisemResource($organisme); 
    }

    public function update(OrganismeUpdateRequest $request, Organisme $organisme)
    {
        $validatedData = $request->validated();
        $organisme->update($validatedData);
        // Eager load if needed, similar to show
        // $organisme->load('relationship_name'); 
        return new OrganisemResource($organisme); 
    }
    
    public function OrganismesSettings()
    {
        // If 'first()' is consistently used, consider caching this result
        // or ensure this table typically has only one record.
        $organisme = Organisme::first(); 
        
        if (!$organisme) {
            return response()->json([
                'message' => 'No settings found',
                'data' => null
            ], 404);
        }
        
        return response()->json([
            'message' => 'Settings retrieved successfully',
            'data' => new OrganisemResource($organisme)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Organisme $organisme)
    {
        $organisme->delete();
        return response()->json(null, 204); 
    }
}