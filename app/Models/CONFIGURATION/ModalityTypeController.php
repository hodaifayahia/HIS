<?php

namespace App\Http\Controllers\CONFIGURATION;

use App\Http\Controllers\Controller;
use App\Models\CONFIGURATION\ModalityType; // Adjust namespace if different
use App\Http\Resources\ModalityTypeResource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ModalityTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $modalityTypes = ModalityType::all();
            return ModalityTypeResource::collection($modalityTypes);
        } catch (\Exception $e) {
            Log::error('Error fetching modality types: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch modality types.'], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            Log::info('ModalityType Store Request received:', $request->all());

            $validatedData = $request->validate([
                'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
                'name' => ['required', 'string', 'max:255', 'unique:modality_types,name'],
                'description' => ['nullable', 'string', 'max:1000'],
            ]);

            Log::info('Validation successful. Validated data:', $validatedData);

            $validatedData['image_url'] = null; // Initialize to null
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('modality_type_images', 'public');
                $validatedData['image_url'] = Storage::url($path);
                Log::info('Image uploaded. Full URL: ' . $validatedData['image_url']);
            } else {
                // If no file is uploaded, and the frontend sent an image_url (e.g., for existing image)
                // or if it sent null/empty string to remove, we handle it.
                // For a store function, this usually means no image was chosen.
                $validatedData['image_url'] = $request->input('image_url');
                Log::info('No new image uploaded. Existing URL (if any): ' . $validatedData['image_url']);
            }

            // Ensure image_url is explicitly null if no file and no existing URL
            if (!isset($validatedData['image_url']) || $validatedData['image_url'] === '') {
                $validatedData['image_url'] = null;
            }

            Log::info('Attempting to create modality type with data:', $validatedData);
            $modalityType = ModalityType::create($validatedData);
            Log::info('Modality Type created successfully:', $modalityType->toArray());

            return response()->json([
                'status' => 'success',
                'message' => 'Modality Type created successfully.',
                'data' => new ModalityTypeResource($modalityType)
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('ModalityType Validation Error:', $e->errors());
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed for modality type creation.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Generic Error creating modality type: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create modality type. Please try again later.'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CONFIGURATION\ModalityType  $modalityType
     * @return \Illuminate\Http\Response
     */
    public function show(ModalityType $modalityType)
    {
        try {
            return new ModalityTypeResource($modalityType);
        } catch (\Exception $e) {
            Log::error('Error fetching modality type ' . $modalityType->id . ': ' . $e->getMessage());
            return response()->json(['message' => 'Modality Type not found.'], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CONFIGURATION\ModalityType  $modalityType
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ModalityType $modalityType)
    {
        try {
            Log::info('ModalityType Update Request received for ID: ' . $modalityType->id, $request->all());

            $validatedData = $request->validate([
                'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'], // Allow 'image' file input
                'name' => ['required', 'string', 'max:255', Rule::unique('modality_types', 'name')->ignore($modalityType->id)],
                'description' => ['nullable', 'string', 'max:1000'],
            ]);

            Log::info('Validation successful for update. Validated data:', $validatedData);

            // Handle image update logic
            if ($request->hasFile('image')) {
                // 1. Delete old image if it exists
                if ($modalityType->image_url) {
                    $oldImagePath = str_replace('/storage/', '', $modalityType->image_url);
                    if (Storage::disk('public')->exists($oldImagePath)) {
                        Storage::disk('public')->delete($oldImagePath);
                        Log::info('Old image deleted: ' . $oldImagePath);
                    }
                }

                // 2. Store the new image on the 'public' disk
                $path = $request->file('image')->store('modality_type_images', 'public');
                $validatedData['image_url'] = Storage::url($path);
                Log::info('New image uploaded for update. URL: ' . $validatedData['image_url']);
            } else if ($request->has('image_url') && $request->input('image_url') === null) {
                // Case: Frontend explicitly sends image_url as null, meaning "remove image"
                if ($modalityType->image_url) {
                    $oldImagePath = str_replace('/storage/', '', $modalityType->image_url);
                    if (Storage::disk('public')->exists($oldImagePath)) {
                        Storage::disk('public')->delete($oldImagePath);
                        Log::info('Image removed: ' . $oldImagePath);
                    }
                }
                $validatedData['image_url'] = null; // Set image_url to null in DB
            } else {
                // No new file, and not explicitly requesting to remove.
                // Keep the existing image_url from the model.
                // Or if the frontend sends the current image_url, it will be used.
                 if (!isset($validatedData['image_url'])) {
                     $validatedData['image_url'] = $modalityType->image_url;
                }
            }

            // Remove 'image' from validatedData as it's not a direct column
            unset($validatedData['image']);


            Log::info('Attempting to update modality type with data:', $validatedData);
            $modalityType->update($validatedData);
            Log::info('Modality Type updated successfully:', $modalityType->toArray());

            return response()->json([
                'status' => 'success',
                'message' => 'Modality Type updated successfully.',
                'data' => new ModalityTypeResource($modalityType)
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('ModalityType Validation Error on update:', $e->errors());
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed for modality type update.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Generic Error updating modality type ' . $modalityType->id . ': ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update modality type. Please try again later.'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CONFIGURATION\ModalityType  $modalityType
     * @return \Illuminate\Http\Response
     */
    public function destroy(ModalityType $modalityType)
    {
        try {
            // Delete associated image
            if ($modalityType->image_url) {
                $imagePath = str_replace('/storage/', '', $modalityType->image_url);
                if (Storage::disk('public')->exists($imagePath)) {
                    Storage::disk('public')->delete($imagePath);
                    Log::info('Modality Type image deleted during destroy: ' . $imagePath);
                }
            }

            $modalityType->delete();
            Log::info('Modality Type deleted successfully: ' . $modalityType->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Modality Type deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting modality type ' . $modalityType->id . ': ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete modality type. Please try again later.'
            ], 500);
        }
    }
}