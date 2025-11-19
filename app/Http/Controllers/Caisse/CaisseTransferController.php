<?php
// app/Http/Controllers/CaisseTransferController.php

namespace App\Http\Controllers\Caisse;
use App\Http\Controllers\Controller;



use App\Http\Requests\Caisse\StoreCaisseTransferRequest;
use App\Http\Requests\Caisse\AcceptCaisseTransferRequest;
use App\Http\Resources\Caisse\CaisseTransferCollection;
use App\Http\Resources\Caisse\CaisseTransferResource;
use App\Models\Caisse\CaisseTransfer;
use App\Services\Caisse\CaisseTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CaisseTransferController extends Controller
{
    protected CaisseTransferService $service;

    public function __construct(CaisseTransferService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): CaisseTransferCollection
    {
        $perPage = $request->integer('per_page', 15);
        
        $filters = $request->only([
            'caisse_id', 'from_user_id', 'to_user_id', 'status', 
            'user_id', 'date_from', 'date_to','status'
        ]);

        $transfers = $this->service->getAllPaginated($filters, $perPage);

        return new CaisseTransferCollection($transfers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCaisseTransferRequest $request): JsonResponse
    {
        try {
            $transfer = $this->service->create($request->validated());

            return (new CaisseTransferResource($transfer))
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }
    // create a function that recive a from_id and caisse id and to_user_id and status is pending it should retirve this cloum  
     public function checkIfThereIsRequest(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Get all pending transfer requests where the auth user is the recipient
        $pendingTransfers = CaisseTransfer::query()
            ->where('to_user_id', $user->id)
            ->where('status', 'pending')
            ->with(['fromUser', 'toUser', 'caisse']) // Load related data to know who sent and which caisse
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => CaisseTransferResource::collection($pendingTransfers),
            'has_pending_requests' => $pendingTransfers->count() > 0,
            'pending_count' => $pendingTransfers->count(),
            'message' => $pendingTransfers->count() > 0 
                ? "You have {$pendingTransfers->count()} pending transfer request(s) to approve"
                : "No pending transfer requests"
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(CaisseTransfer $caisseTransfer): CaisseTransferResource
    {
        return new CaisseTransferResource(
            $this->service->findById($caisseTransfer->id)
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CaisseTransfer $caisseTransfer): JsonResponse
    {
        try {
            $this->service->delete($caisseTransfer);

            return response()->json([
                'message' => 'Transfer deleted successfully'
            ], 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function caisseTransfersAuthUserSession(): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $transfers = $this->service->getUserTransfers($user->id);
        return response()->json([
            'data' => CaisseTransferResource::collection($transfers)
        ]);
    }

    

    /**
     * Accept a transfer
     */
      public function accept(AcceptCaisseTransferRequest $request, CaisseTransfer $caisseTransfer): JsonResponse
    {
        try {
           

            $amountReceived = $request->input('amount_received', null);
            $transfer = $this->service->acceptTransfer($caisseTransfer, $amountReceived);

            // return Resource as a JsonResponse
            return (new CaisseTransferResource($transfer))->response()->setStatusCode(200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Reject a transfer
     */
    public function reject(CaisseTransfer $caisseTransfer): CaisseTransferResource
    {
        try {
            $transfer = $this->service->rejectTransfer($caisseTransfer);

            return new CaisseTransferResource($transfer);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get transfers for specific user
     */
    public function userTransfers(Request $request): JsonResponse
    {
        $userId = $request->integer('user_id');
        $type = $request->get('type', 'all'); // all, sent, received

        $transfers = $this->service->getUserTransfers($userId, $type);

        return response()->json([
            'data' => CaisseTransferResource::collection($transfers)
        ]);
    }

    /**
     * Get transfer by token
     */
    public function getByToken(string $token): JsonResponse
    {
        $transfer = $this->service->getTransferByToken($token);

        if (!$transfer) {
            return response()->json([
                'message' => 'Transfer not found'
            ], 404);
        }

        return response()->json([
            'data' => new CaisseTransferResource($transfer)
        ]);
    }

    /**
     * Get transfer statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $caisseId = $request->integer('caisse_id');
        $userId = $request->integer('user_id');

        $stats = $this->service->getTransferStats($caisseId, $userId);

        return response()->json([
            'data' => $stats
        ]);
    }

    /**
     * Expire old transfers
     */
    public function expireOld(): JsonResponse
    {
        $expiredCount = $this->service->expireOldTransfers();

        return response()->json([
            'message' => "Expired {$expiredCount} old transfers"
        ]);
    }
}
