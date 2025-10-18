<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\OrganismeStoreRequest;
use App\Http\Requests\CRM\OrganismeUpdateRequest;
use App\Http\Resources\CRM\OrganisemResource;
use App\Models\CRM\Organisme;

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
        // Handle file uploads if present
        if ($request->hasFile('logo_file')) {
            $path = $request->file('logo_file')->store('organismes/logos', 'public');
            $validatedData['logo_url'] = $path; // store path; resource will convert to public URL
        }
        if ($request->hasFile('profile_image_file')) {
            $path = $request->file('profile_image_file')->store('organismes/profiles', 'public');
            $validatedData['profile_image_url'] = $path;
        }

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
        // Handle file uploads and update paths
        if ($request->hasFile('logo_file')) {
            $path = $request->file('logo_file')->store('organismes/logos', 'public');
            $validatedData['logo_url'] = $path;
        }
        if ($request->hasFile('profile_image_file')) {
            $path = $request->file('profile_image_file')->store('organismes/profiles', 'public');
            $validatedData['profile_image_url'] = $path;
        }

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

        if (! $organisme) {
            return response()->json([
                'message' => 'No settings found',
                'data' => null,
            ], 404);
        }

        // Log raw DB values to help debug missing images
        \Log::debug('OrganismesSettings - DB values', [
            'logo_url_raw' => $organisme->logo_url,
            'profile_image_url_raw' => $organisme->profile_image_url,
        ]);

        // Also log the resource array that will be returned
        $resource = new OrganisemResource($organisme);
        \Log::debug('OrganismesSettings - resource array', $resource->toArray(request()));

        return response()->json([
            'message' => 'Settings retrieved successfully',
            'data' => $resource,
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
