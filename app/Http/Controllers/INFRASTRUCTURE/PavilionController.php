<?php

namespace App\Http\Controllers\INFRASTRUCTURE;

use App\Http\Controllers\Controller;
use App\Models\INFRASTRUCTURE\Pavilion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Resources\PavilionResource;
use App\Models\CONFIGURATION\Service; // Import the Service model
use Illuminate\Support\Facades\Storage; // Import Storage facade

class PavilionController extends Controller
{
    /**
     * Display a listing of the pavilions.
     */
    public function index()
    {
        $pavilions = Pavilion::with('services')->orderBy('name')->get();
        return PavilionResource::collection($pavilions);
    }

    /**
     * Store a newly created pavilion in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:191|unique:pavilions,name',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Validation for file upload
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'exists:services,id',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            // Store the image in the 'public' disk under the 'pavilions' directory
            $imagePath = $request->file('image')->store('pavilions', 'public');
            // Generate the URL for the stored image
            $validatedData['image_url'] = Storage::url($imagePath);
        } else {
            // If no new image is uploaded, ensure image_url is not set unless explicitly needed
            // If you want to allow clearing an image, you might need a separate field or logic
            if (isset($validatedData['image_url'])) {
                 unset($validatedData['image_url']); // Remove it if not an upload field
            }
        }

        // Remove 'image' key as it's not a database column
        unset($validatedData['image']);


        $pavilion = Pavilion::create($validatedData);

        if (isset($validatedData['service_ids'])) {
            $pavilion->services()->sync($validatedData['service_ids']);
        }

        return new PavilionResource($pavilion->load('services'));
    }

    /**
     * Display the specified pavilion.
     */
    public function show(Pavilion $pavilion)
    {
        return new PavilionResource($pavilion->load('services'));
    }

    /**
     * Update the specified pavilion in storage.
     */
    public function update(Request $request, Pavilion $pavilion)
    {
        $validatedData = $request->validate([
            'name' => [
                'required',
                'string',
                'max:191',
                Rule::unique('pavilions')->ignore($pavilion->id),
            ],
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Validation for file upload
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'exists:services,id',
        ]);

        // Handle image upload for update
        if ($request->hasFile('image')) {
            // Delete old image if it exists
            if ($pavilion->image_url) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $pavilion->image_url));
            }
            $imagePath = $request->file('image')->store('pavilions', 'public');
            $validatedData['image_url'] = Storage::url($imagePath);
        } else if ($request->input('clear_image')) { // Optional: Add a hidden field to explicitly clear image
            if ($pavilion->image_url) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $pavilion->image_url));
            }
            $validatedData['image_url'] = null;
        }
        // If no new image is uploaded and clear_image is not set, keep the existing image_url
        // No else needed here because image_url is not directly in $validatedData unless uploaded

        // Remove 'image' key as it's not a database column
        unset($validatedData['image']);


        $pavilion->update($validatedData);

        if (isset($validatedData['service_ids'])) {
            $pavilion->services()->sync($validatedData['service_ids']);
        } else {
            $pavilion->services()->detach();
        }

        return new PavilionResource($pavilion->load('services'));
    }

    /**
     * Remove the specified pavilion from storage.
     */
    public function destroy(Pavilion $pavilion)
    {
        // Delete image file before deleting the pavilion record
        if ($pavilion->image_url) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $pavilion->image_url));
        }

        $pavilion->services()->detach();
        $pavilion->delete();

        return response()->json([
            'message' => 'Pavilion deleted successfully!'
        ], 200);
    }
      public function PavilionServices(Pavilion $pavilionId) // Use Route Model Binding for Pavilion
    {
        try {
            // Eager load services for the given pavilion
            // The `services` method on the Pavilion model defines the many-to-many relationship
            $services = $pavilionId->services()->get();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Services for pavilion ' . $pavilionId->name . ' retrieved successfully.',
                'data' => $services
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve services for this pavilion. Please try again later.'
            ], 500);
        }
    }
}