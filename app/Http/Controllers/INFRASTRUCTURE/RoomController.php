<?php

namespace App\Http\Controllers\INFRASTRUCTURE;

use App\Http\Controllers\Controller;
use App\Models\INFRASTRUCTURE\Room; // Import the Room model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; // For file storage operations
use Illuminate\Validation\Rule; // For validation rules
use App\Http\Resources\INFRASTRUCTURE\RoomResource; // Import the RoomResource
use App\Http\Requests\INFRASTRUCTURE\StoreRoomRequest; // Import the StoreRoomRequest
use App\Http\Requests\INFRASTRUCTURE\UpdateRoomRequest; // Import the UpdateRoomRequest
use DB;
use Log;
use Validator;

class RoomController extends Controller
{
    /**
     * Display a listing of the rooms.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Fetch all rooms, eager load related models for efficiency
        $rooms = Room::with(['roomType', 'pavilion', 'service'])->paginate(10); // Paginate results

        // Return a collection of RoomResource
        return RoomResource::collection($rooms);
    }

    /**
     * Store a newly created room in storage.
     *
     * @param  \App\Http\Requests\StoreRoomRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreRoomRequest $request) // Use StoreRoomRequest for validation
    {
      
        // Validated data is automatically available via $request->validated()
        $validatedData = $request->validated();

        $imageUrl = null;

        // Handle image upload if a file is provided
        if ($request->hasFile('room_image')) {
            $imagePath = $request->file('room_image')->store('public/rooms'); // Store in storage/app/public/rooms
            $imageUrl = Storage::url($imagePath); // Get the public URL for the stored image
        } elseif (isset($validatedData['image_url'])) {
            // If no new file is uploaded but an image_url is provided (e.g., from an external source)
            $imageUrl = $validatedData['image_url'];
        }

        // Create the new Room instance
        $room = Room::create(array_merge($validatedData, [
            'image_url' => $imageUrl, // Assign the generated/provided image URL
        ]));

        // Return the created room using RoomResource with a 201 status code
        return (new RoomResource($room->load(['roomType', 'pavilion', 'service'])))->response()->setStatusCode(201);
    }

    /**
     * Display the specified room.
     *
     * @param  \App\Models\Room  $room
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Room $room)
    {
        // Eager load related models and return using RoomResource
        return new RoomResource($room->load(['roomType', 'pavilion', 'service']));
    }

    /**
     * Update the specified room in storage.
     *
     * @param  \App\Http\Requests\UpdateRoomRequest  $request
     * @param  \App\Models\Room  $room
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateRoomRequest $request, Room $room) // Use UpdateRoomRequest for validation
    {
        // Validated data is automatically available via $request->validated()
        $validatedData = $request->validated();

        $imageUrl = $room->image_url; // Start with the existing image URL

        // Handle new image upload if a file is provided
        if ($request->hasFile('room_image')) {
            // Delete old image if it exists and is stored publicly
            if ($room->image_url && str_starts_with($room->image_url, '/storage/')) {
                Storage::delete(str_replace('/storage/', 'public/', $room->image_url));
            }

            $imagePath = $request->file('room_image')->store('public/rooms');
            $imageUrl = Storage::url($imagePath);
        } elseif ($request->has('image_url')) {
            // If image_url is explicitly provided in the request (e.g., changed to an external URL or cleared)
            $imageUrl = $validatedData['image_url'];
        } else {
            // If neither a new file nor an image_url is provided, and the old image_url was present,
            // it means the user might have cleared the image.
            // In this case, we should clear the image_url in the database.
            // This assumes if 'image_url' is not in the request, it means it should be removed.
            // Adjust this logic based on your frontend's specific behavior for clearing images.
            if (!$request->hasFile('room_image') && !$request->has('image_url')) {
                if ($room->image_url && str_starts_with($room->image_url, '/storage/')) {
                    Storage::delete(str_replace('/storage/', 'public/', $room->image_url));
                }
                $imageUrl = null;
            }
        }

        // Update the Room instance
        $room->update(array_merge($validatedData, [
            'image_url' => $imageUrl, // Assign the new/updated image URL
        ]));

        // Return the updated room using RoomResource
        return new RoomResource($room->load(['roomType', 'pavilion', 'service']));
    }

    /**
     * Remove the specified room from storage.
     *
     * @param  \App\Models\Room  $room
     * @return \Illuminate\Http\JsonResponse
     */
 public function destroy(Room $room)
{
    try {
        // The database constraint should handle the foreign key automatically
        $room->delete();
        
        return response()->json([
            'message' => 'Room deleted successfully'
        ], 200);
        
    } catch (\Exception $e) {
        Log::error('Room deletion failed: ' . $e->getMessage());
        
        return response()->json([
            'error' => 'Cannot delete room: ' . $e->getMessage()
        ], 400);
    }
}




}
