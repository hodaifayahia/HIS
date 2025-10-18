<?php

namespace App\Http\Controllers\INFRASTRUCTURE;

use App\Http\Controllers\Controller;
use App\Models\INFRASTRUCTURE\RoomType;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Http\Resources\INFRASTRUCTURE\RoomTypeResource;
use App\Models\Service; // Import the Service model

class RoomTypeController extends Controller
{
    /**
     * Display a listing of the room types.
     */
 public function index(Request $request)
{
    $query = RoomType::with('service')->orderBy('name');
    
    // Filter by service_id if provided
    if ($request->has('service_id')) {
        $query->where('service_id', $request->input('service_id'));
    }
    
    // Filter by room_type if provided
    if ($request->has('type')) {
        $query->where('room_type', $request->input('type'));
    }
    
    $roomTypes = $query->get();
    
    return RoomTypeResource::collection($roomTypes);
}

    /**
     * Store a newly created room type in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:room_types,name',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
            'room_type' => 'required|string', // Added validation for is_waiting_room
            'service_id' => 'nullable|exists:services,id', // Added validation for single service_id
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('room-types', 'public');
            $validatedData['image_url'] = Storage::url($path);
        }

        // Create the room type with all validated data, including new fields
        $roomType = RoomType::create($validatedData);

        return new RoomTypeResource($roomType->load('service')); // Load service for the response
    }

    /**
     * Display the specified room type.
     */
    public function show(RoomType $roomType)
    {
        // Eager load the 'service' relationship
        return new RoomTypeResource($roomType->load('service'));
    }

    /**
     * Update the specified room type in storage.
     */
    public function update(Request $request, RoomType $roomType)
    {
        // Ensure that 'is_waiting_room' and 'service_id' are included in the validation
        $validatedData = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
            'remove_image' => 'nullable|boolean', // Flag to remove existing image
            'is_waiting_room' => 'required|boolean', // Added validation for is_waiting_room
            'service_id' => 'nullable|exists:services,id', // Added validation for single service_id
        ]);

        // Handle image removal
        if ($request->input('remove_image')) {
            $this->deleteImage($roomType->image_url);
            $validatedData['image_url'] = null;
        }

        // Handle new image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            $this->deleteImage($roomType->image_url);

            $path = $request->file('image')->store('room-types', 'public');
            $validatedData['image_url'] = Storage::url($path);
        }

        // Update the room type with all validated data
        $roomType->update($validatedData);

        return new RoomTypeResource($roomType->load('service')); // Load service for the response
    }

    /**
     * Remove the specified room type from storage.
     */
    public function destroy(RoomType $roomType)
    {
        if (Room::where('room_type_id', $roomType->id)->exists()) {
            return response()->json([
                'message' => 'Cannot delete room type because it is associated with existing rooms. Please reassign or delete rooms first.',
            ], 409);
        }

        // Delete associated image
        $this->deleteImage($roomType->image_url);

        $roomType->delete();
        return response()->json([
            'message' => 'Room type deleted successfully!'
        ], 200);
    }

    /**
     * Helper method to delete image file
     */
    protected function deleteImage($imageUrl)
    {
        if ($imageUrl) {
            // Extract the path from the URL to delete the file from storage
            $pathInStorage = str_replace('/storage', '', parse_url($imageUrl, PHP_URL_PATH));
            // Ensure we are not trying to delete a remote URL or a malformed path
            if (starts_with($pathInStorage, '/room-types/')) { // Adjust prefix if your room type images are in a different folder
                Storage::disk('public')->delete($pathInStorage);
            }
        }
    }
}