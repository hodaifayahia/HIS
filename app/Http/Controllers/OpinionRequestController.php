<?php

namespace App\Http\Controllers;

use App\Models\OpinionRequest;
use Illuminate\Http\Request;
use App\Http\Resources\OpinionRequestResource; // Import the resource
use App\Models\Doctor; // Import the Doctor model
use Illuminate\Support\Facades\Broadcast; // Import Broadcast facade
use App\Events\OpinionRequestCreated; // Import the event
use Illuminate\Support\Facades\Validator; // Added for validation
use App\Events\OpinionRequestReplied; // Import the reply event
class OpinionRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
public function index(Request $request)
{
    $receiverDoctorId = $request->query('receiver_doctor_id'); // Extracted from query
    $status = $request->query('status');
    $search = $request->query('search');

    // Validate input
    $validator = Validator::make([
        'receiver_doctor_id' => $receiverDoctorId,
        'status' => $status,
        'search' => $search,
    ], [
        'receiver_doctor_id' => 'required|exists:doctors,id',
        'status' => 'nullable|string',
        'search' => 'nullable|string|max:255',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422);
    }

    // Start base query
    $query = OpinionRequest::with(['senderDoctor.user', 'receiverDoctor.user'])
        ->where(function ($q) use ($receiverDoctorId) {
            $q->where('reciver_doctor_id', $receiverDoctorId)
              ->orWhere('sender_doctor_id', $receiverDoctorId);
        });

    // Filter by status
    if (!empty($status) && $status !== 'all') {
        $query->where('status', $status);
    }

    // Filter by search string
    if (!empty($search)) {
        $query->where('request', 'like', '%' . $search . '%');
    }

    $opinionRequests = $query->orderBy('created_at', 'desc')->paginate(30);
    
    return response()->json([
        'message' => 'Opinion requests retrieved successfully',
        'data' => OpinionRequestResource::collection($opinionRequests),
        'meta' => [
            'last_page' => $opinionRequests->lastPage(),
            'per_page' => $opinionRequests->perPage(),
            'current_page' => $opinionRequests->currentPage(),
        ],
    ]);
}

    /**
     * Show the form for creating a new resource.
     *
     * This method is typically used for traditional web applications to show a form.
     * For APIs, you usually don't need this method as the frontend handles form rendering.
     * I'll leave it empty as it's common for API-only controllers.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
      public function store(Request $request)
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'sender_doctor_id' => 'required|exists:doctors,id',
            'reciver_doctor_id' => 'required|exists:doctors,id',
            'request' => 'nullable|string',
            'patient_id' => 'nullable|exists:patients,id',
            'status' => 'nullable|string',
            'appointment_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Create a new OpinionRequest
        $opinionRequest = OpinionRequest::create($validator->validated());
        
        // Load the necessary relationships for the event
        // This is important because your event's broadcastWith method uses these relationships.
        $opinionRequest->load(['senderDoctor.user', 'receiverDoctor.user']);
        
        // Fire the event (This is the correct way!)
        event(new OpinionRequestCreated($opinionRequest)); 
        
        return response()->json([
            'message' => 'Opinion request created successfully',
            'data' => $opinionRequest
        ], 201);
    }

        public function getPendingRequestsCount($doctorId)
    {
        // Assuming 'pending' is the status for a new, unhandled request
        $count = OpinionRequest::where('reciver_doctor_id', $doctorId)
                               ->where('status', 'pending') // Or whatever your pending status is
                               ->count();

        return response()->json(['count' => $count]);
    }
   
public function reply(Request $request, $id)
{
    $validator = Validator::make($request->all(), [
        'reply' => 'required|string',
        'status' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422);
    }
    
    $opinionRequest = OpinionRequest::findOrFail($id);
     $opinionRequest->update([
        'Reply' => $request->reply,
        'status' => 'replied',
    ]);
    $opinionRequest->refresh();

    $opinionRequest->load(['senderDoctor.user', 'receiverDoctor.user']);


    event(new OpinionRequestReplied($opinionRequest)); 


    return response()->json([
        'message' => 'Reply submitted successfully',
    ], 200);
}

    /**
     * Display the specified resource.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

        // Find the opinion request by its ID
        $opinionRequest = OpinionRequest::where('appointment_id',$id)
                        ->get();
        if (!$opinionRequest) {
            return response()->json([
                'message' => 'Opinion request not found'
            ], 404); // 404 Not Found
        }

        // You might want to eager load the Consultation relationship
        // $opinionRequest = OpinionRequest::with('consultation')->find($id);
        // 'data' => OpinionRequestResource::collection($opinionRequests),

        return response()->json([
            'message' => 'Opinion request retrieved successfully',
            'data' => OpinionRequestResource::collection($opinionRequest)
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * Similar to 'create', this is typically for web applications.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, string $id)
    {
        // Find the opinion request by its ID
        $opinionRequest = OpinionRequest::find($id);

        if (!$opinionRequest) {
            return response()->json([
                'message' => 'Opinion request not found'
            ], 404);
        }

        // Validate the incoming request data for update
        $validator = Validator::make($request->all(), [
            'sender_doctor_id' => 'sometimes|required|exists:doctors,id',
            'receiver_doctor_id' => 'sometimes|required|exists:doctors,id',
            'request' => 'sometimes|required|string|max:1000',
            'appointment_id' => 'sometimes|required|exists:consultations,id',
            'reply' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Update the opinion request with the new data
        $opinionRequest->update($request->all());

        return response()->json([
            'message' => 'Opinion request updated successfully',
            'data' => $opinionRequest
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $id)
    {
        // Find the opinion request by its ID
        $opinionRequest = OpinionRequest::find($id);

        if (!$opinionRequest) {
            return response()->json([
                'message' => 'Opinion request not found'
            ], 404);
        }

        // Delete the opinion request
        $opinionRequest->delete();

        return response()->json([
            'message' => 'Opinion request deleted successfully'
        ], 200); // 200 OK for successful deletion
    }
}