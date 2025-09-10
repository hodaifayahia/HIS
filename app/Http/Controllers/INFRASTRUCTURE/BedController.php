<?php

namespace App\Http\Controllers\INFRASTRUCTURE;

use App\Http\Controllers\Controller;

use App\Models\INFRASTRUCTURE\Bed;
use App\Models\INFRASTRUCTURE\Room;
use App\Http\Requests\INFRASTRUCTURE\StoreBedRequest;
use App\Http\Requests\INFRASTRUCTURE\UpdateBedRequest;
use App\Http\Resources\INFRASTRUCTURE\BedResource;
use Illuminate\Http\Request;

class BedController extends Controller
{
    public function index(Request $request)
    {
        $query = Bed::with(['room.service', 'currentPatient']);

        // Apply filters
        if ($request->filled('service_id')) {
            $query->byService($request->service_id);
        }

        if ($request->filled('room_type')) {
            $query->byRoomType($request->room_type);
        }

        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        $beds = $query->paginate(15);

        return BedResource::collection($beds);
    }

    public function store(StoreBedRequest $request)
    {
        $bed = Bed::create($request->validated());
        $bed->load(['room.service', 'currentPatient']);

        return new BedResource($bed);
    }

    public function show(Bed $bed)
    {
        $bed->load(['room.service', 'currentPatient']);
        return new BedResource($bed);
    }

    public function update(UpdateBedRequest $request, Bed $bed)
    {
        $bed->update($request->validated());
        $bed->load(['room.service', 'currentPatient']);

        return new BedResource($bed);
    }

    public function destroy(Bed $bed)
    {
        $bed->delete();
        return response()->json(['message' => 'Bed deleted successfully']);
    }

    public function getAvailableRooms()
    {
        $rooms = Room::with('service')->get();
        return response()->json($rooms);
    }
}
