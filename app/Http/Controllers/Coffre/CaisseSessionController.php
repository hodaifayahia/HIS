<?php
// app/Http/Controllers/Coffre/CaisseSessionController.php

namespace App\Http\Controllers\Coffre;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coffre\CloseCaisseSessionRequest;
use App\Http\Requests\Coffre\StoreCaisseSessionRequest;
use App\Http\Resources\Coffre\CaisseSessionCollection;
use App\Http\Resources\Coffre\CaisseSessionResource;
use App\Models\Coffre\CaisseSession;
use App\Services\Coffre\CaisseSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CaisseSessionController extends Controller
{
    protected CaisseSessionService $service;

    public function __construct(CaisseSessionService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): CaisseSessionCollection
    {
        $perPage = $request->integer('per_page', 15);
        
        $filters = $request->only([
            'caisse_id', 'user_id', 'status', 
            'date_from', 'date_to', 'search'
        ]);

        $sessions = $this->service->getAllPaginated($filters, $perPage);

        return new CaisseSessionCollection($sessions);
    }

    /**
     * Store a newly created resource (Open new session).
     */
    public function store(StoreCaisseSessionRequest $request): JsonResponse
    {
        try {
            $session = $this->service->openSession($request->validated());

            return (new CaisseSessionResource($session))
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id): JsonResponse
    {
        try {
            // First try to find it as a regular session
            $session = CaisseSession::with(['caisse', 'user', 'openedBy', 'sourceCoffre'])
                ->where('id', $id)
                ->first();

            if ($session) {
                return response()->json([
                    'data' => [
                        'id' => $session->id,
                        'caisse_id' => $session->caisse_id,
                        'user_id' => $session->user_id,
                        'status' => $session->status,
                        'opening_amount' => $session->opening_amount,
                        'caisse' => $session->caisse,
                        'user' => $session->user,
                        'openedBy' => $session->openedBy,
                        'sourceCoffre' => $session->sourceCoffre,
                        'is_transfer' => false
                    ]
                ]);
            }

            // If not found as regular session, try to find as transfer
            $transfer = \App\Models\caisse\CaisseTransfer::with(['caisse', 'fromUser', 'toUser'])
                ->where('id', $id)
                ->first();
            if ($transfer) {
                return response()->json([
                    'data' => [
                        'id' => $transfer->id,
                        'caisse_id' => $transfer->caisse_id,
                        'caisse_session_id' => $transfer->caisse_session_id,
                        'from_user_id' => $transfer->from_user_id,
                        'to_user_id'=> $transfer->to_user_id,
                        'user_id' => $transfer->to_user_id,
                        'status' => 'transferred',
                        'opening_amount' => $transfer->amount_sended,
                        'caisse' => $transfer->caisse,
                        'user' => $transfer->toUser,
                        'from_user' => $transfer->fromUser,
                        'is_transfer' => true,
                        'transfer_data' => $transfer
                    ]
                ]);
            }

            return response()->json(['message' => 'Session not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    public function getUserSessions(): JsonResponse
    {
        $sessions = $this->service->getUserSessions();

        return response()->json([
            'data' => $sessions
        ]);
    }

    /**
     * Close a session.
     */
    public function close(CloseCaisseSessionRequest $request, CaisseSession $caisseSession): JsonResponse
    {
        try {
            $session = $this->service->closeSessionWithDenominations($caisseSession, $request->validated());

            return (new CaisseSessionResource($session))->response();
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }
    public function coffres(): JsonResponse
{
    $coffres = $this->service->getCoffresForSelect();

    return response()->json([
        'data' => $coffres
    ]);
}

/**
 * Get standard denominations
 */
public function denominations(): JsonResponse
{
    $denominations = $this->service->getStandardDenominations();

    return response()->json([
        'data' => $denominations
    ]);
}
    /**
     * Suspend a session.
     */
    public function suspend(CaisseSession $caisseSession): JsonResponse
    {
        
        try {
            $session = $this->service->suspendSession($caisseSession);

            return (new CaisseSessionResource($session))->response();
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Resume a session.
     */
    public function resume(CaisseSession $caisseSession): JsonResponse
    {
        
        try {
            $session = $this->service->resumeSession($caisseSession);

            return (new CaisseSessionResource($session))->response();
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CaisseSession $caisseSession): JsonResponse
    {
        try {
            $this->service->delete($caisseSession);

            return response()->json([
                'message' => 'Session deleted successfully'
            ], 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get active sessions
     */
    public function active(): JsonResponse
    {
        $sessions = $this->service->getActiveSessions();

        return response()->json([
            'data' => CaisseSessionResource::collection($sessions)
        ]);
    }

    /**
     * Get caisses for form dropdown
     */
    public function caisses(): JsonResponse
    {
        $caisses = $this->service->getCaissesForSelect();

        return response()->json([
            'data' => $caisses
        ]);
    }

    /**
     * Get users for form dropdown
     */
    public function users(): JsonResponse
    {
        $users = $this->service->getUsersForSelect();

        return response()->json([
            'data' => $users
        ]);
    }

    /**
     * Get session statistics
     */
    public function stats(): JsonResponse
    {
        $stats = $this->service->getSessionStats();

        return response()->json([
            'data' => $stats
        ]);
    }
}
