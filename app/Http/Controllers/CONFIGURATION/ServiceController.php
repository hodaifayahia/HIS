<?php

namespace App\Http\Controllers\CONFIGURATION;

use App\Models\CONFIGURATION\Service;
use App\Models\INFRASTRUCTURE\Pavilion; // Import the Pavilion model
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class ServiceController extends Controller
{
    /**
     * Display a listing of the service.
     */
    public function index()
    {
        try {
            $services = Service::latest()->get();
            return response()->json([
                'status' => 'success',
                'message' => 'Services retrieved successfully.',
                'data' => $services
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching services: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve services. Please try again later.'
            ], 500);
        }
    }

    /**
     * Store a newly created service in storage.
     */
    public function store(Request $request)
    {
        try {
            Log::info('Service Store Request received:', $request->all());

            $validatedData = $request->validate([
                'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
                'name' => ['required', 'string', 'max:255', 'unique:services,name'],
                'description' => ['nullable', 'string', 'max:1000'],
                'start_time' => ['nullable'], // Use date_format for time validation
                'end_time' => ['nullable'], // Ensure end_time is after start_time
                'agmentation' => ['nullable', 'string', 'max:255'], // Typo: Should be 'augmentation'?
                'is_active' => ['nullable', 'boolean']
            ]);

            Log::info('Validation successful. Validated data:', $validatedData);

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('services_images', 'public');
                $validatedData['image_url'] = Storage::url($path);
                Log::info('Image uploaded. Full URL: ' . $validatedData['image_url']);
            } else if ($request->filled('image_url_keep')) { // Added for explicit keeping of existing URL
                $validatedData['image_url'] = $request->input('image_url_keep');
                Log::info('No new image uploaded, keeping existing URL: ' . $validatedData['image_url']);
            } else {
                 $validatedData['image_url'] = null; // Ensure it's null if no file and not keeping old URL
                 Log::info('No image uploaded and no existing URL kept, image_url set to null.');
            }

            // Remove 'image' from validatedData as it's not a direct column
            unset($validatedData['image']);


            Log::info('Attempting to create service with data:', $validatedData);
            $service = Service::create($validatedData);
            Log::info('Service created successfully:', $service->toArray());

            return response()->json([
                'status' => 'success',
                'message' => 'Service created successfully.',
                'data' => $service
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Service Validation Error:', $e->errors());
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed for service creation.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Generic Error creating service: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create service. Please try again later.'
            ], 500);
        }
    }

    /**
     * Display the specified service.
     */
    public function show(Service $service)
    {
        try {
            return response()->json([
                'status' => 'success',
                'message' => 'Service retrieved successfully.',
                'data' => $service
            ]);
        } catch (\Exception $e) {
            Log::error('Error showing service ' . $service->id . ': ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Service not found or could not be retrieved.'
            ], 404);
        }
    }

    /**
     * Update the specified service in storage.
     */
   public function update(Request $request, Service $service)
    {
        try {
            Log::info('Service Update Request received for ID: ' . $service->id, $request->all());

            $validatedData = $request->validate([
                'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
                'name' => ['required', 'string', 'max:255', Rule::unique('services', 'name')->ignore($service->id)],
                'description' => ['nullable', 'string', 'max:1000'],
                'start_time' => ['nullable'],
                'end_time' => ['nullable'],
                'agmentation' => ['nullable', 'string', 'max:255'],
                'is_active' => ['nullable', 'boolean'],
                'image_url_keep' => ['nullable', 'boolean'], // New field to indicate keeping existing image
            ]);

            Log::info('Validation successful for update. Validated data:', $validatedData);

            // Handle image update logic
            if ($request->hasFile('image')) {
                // Delete old image if it exists
                if ($service->image_url) {
                    $oldImagePath = str_replace('/storage/', '', $service->image_url);
                    if (Storage::disk('public')->exists($oldImagePath)) {
                        Storage::disk('public')->delete($oldImagePath);
                        Log::info('Old image deleted: ' . $oldImagePath);
                    }
                }
                // Store the new image
                $path = $request->file('image')->store('services_images', 'public');
                $validatedData['image_url'] = Storage::url($path);
                Log::info('New image uploaded for update. URL: ' . $validatedData['image_url']);
            } else if ($request->input('image_url_keep') === false && $service->image_url) {
                // Frontend explicitly indicates to remove the image (e.g., checkbox unchecked)
                // And there was an existing image
                $oldImagePath = str_replace('/storage/', '', $service->image_url);
                if (Storage::disk('public')->exists($oldImagePath)) {
                    Storage::disk('public')->delete($oldImagePath);
                    Log::info('Image removed by explicit request: ' . $oldImagePath);
                }
                $validatedData['image_url'] = null; // Set image_url to null in DB
            } else {
                // No new file, and not explicitly requesting to remove.
                // Keep the existing image_url from the service model.
                $validatedData['image_url'] = $service->image_url;
                Log::info('No new image, keeping existing URL: ' . $validatedData['image_url']);
            }

            // Remove 'image' and 'image_url_keep' from validatedData as they are not direct columns
            unset($validatedData['image']);
            unset($validatedData['image_url_keep']);

            Log::info('Attempting to update service with data:', $validatedData);
            $service->update($validatedData);
            Log::info('Service updated successfully:', $service->toArray());

            return response()->json([
                'status' => 'success',
                'message' => 'Service updated successfully.',
                'data' => $service
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Service Validation Error on update:', $e->errors());
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed for service update.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Generic Error updating service ' . $service->id . ': ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update service. Please try again later.'
            ], 500);
        }
    }

    /**
     * Remove the specified service from storage.
     */
    public function destroy(Service $service)
    {
        try {
            // Detach associated pavilions before deleting the service
            // This is important for many-to-many relationships
            $service->pavilions()->detach();

            // Delete the image file if it exists
            if ($service->image_url) {
                $imagePath = str_replace('/storage/', '', $service->image_url);
                if (Storage::disk('public')->exists($imagePath)) {
                    Storage::disk('public')->delete($imagePath);
                    Log::info('Service image deleted on destroy: ' . $imagePath);
                }
            }

            $service->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Service deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting service ' . $service->id . ': ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete service. Please try again later.'
            ], 500);
        }
    }

    /**
     * Get services for a specific pavilion.
     *
     * @param  \App\Models\INFRASTRUCTURE\Pavilion $pavilion
     * @return \Illuminate\Http\JsonResponse
     */
  
}