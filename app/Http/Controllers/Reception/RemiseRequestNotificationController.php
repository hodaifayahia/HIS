<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reception\ApproveRemiseRequestRequest;
use App\Http\Requests\Reception\CreateRemiseRequestRequest;
use App\Http\Requests\Reception\MarkNotificationsAsReadRequest;
use App\Http\Requests\Reception\RejectRemiseRequestRequest;
use App\Models\Reception\RemiseRequest;
use App\Services\Reception\RemiseRequestNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\RemiseRequestNotificationResource;

class RemiseRequestNotificationController extends Controller
{
    public function __construct(
        private RemiseRequestNotificationService $service
    ) {}
    

    // GET /api/reception/remise-requests/pending
    public function getPendingRequests(Request $request): JsonResponse
    {
        $params = $request->only(['page', 'status', 'q']);
        try {
            $data = $this->service->getPendingRequests($params, Auth::id());
            
            // Transform using resource
            $transformedData = [
                'items' => RemiseRequestNotificationResource::collection(collect($data['items'])),
                'total' => $data['total'],
                'per_page' => $data['per_page'],
                'current_page' => $data['current_page'],
            ];
            
            return response()->json(['success' => true, 'data' => $transformedData], 200);
        } catch (\Throwable $e) {
            Log::error('getPendingRequests error: '.$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Failed to fetch pending requests'], 500);
        }
    }

    // GET /api/reception/remise-requests/notifications
    public function getNotifications(Request $request): JsonResponse
    {
        $params = $request->only(['page', 'q']);
        try {
            $data = $this->service->getNotifications($params, Auth::id());
            return response()->json(['success' => true, 'data' => $data], 200);
        } catch (\Throwable $e) {
            Log::error('getNotifications error: '.$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Failed to fetch notifications'], 500);
        }
    }

    // POST /api/reception/remise-requests
    public function createRequest(CreateRemiseRequestRequest $request): JsonResponse
    {
        try {
            $payload = $request->validated();
            $res = $this->service->createRequest($payload, Auth::id());
            return response()->json(['success' => true, 'data' => $res], 201);
        } catch (\Throwable $e) {
            Log::error('createRequest error: '.$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Failed to create request'], 500);
        }
    }

    // PATCH /api/reception/remise-requests/{remise_request}/approve
    public function approve($remise_request, Request $request): JsonResponse
    {
        try {
            $remise = RemiseRequest::with(['prestations.contributions'])->findOrFail($remise_request);
            $payload = $request->only(['contributions', 'comment']);
            $res = $this->service->approveRequest($remise, $payload);
            return response()->json(['success' => true, 'data' => $res], 200);
        } catch (\Throwable $e) {
            Log::error('approve error: '.$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Failed to approve request'], 500);
        }
    }

    // PATCH /api/reception/remise-requests/{remise_request}/reject
    public function reject($remise_request, Request $request): JsonResponse
    {
        try {
            $remise = RemiseRequest::findOrFail($remise_request);
            $payload = $request->only(['comment']);
            $res = $this->service->rejectRequest($remise, $payload);
            return response()->json(['success' => true, 'data' => $res], 200);
        } catch (\Throwable $e) {
            Log::error('reject error: '.$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Failed to reject request'], 500);
        }
    }

    // GET /api/reception/remise-requests/history
    public function getRequestHistory(Request $request): JsonResponse
    {
        $params = $request->only(['page', 'q']);
        try {
            $data = $this->service->getRequestHistory($params, Auth::id());
            return response()->json(['success' => true, 'data' => $data], 200);
        } catch (\Throwable $e) {
            Log::error('getRequestHistory error: '.$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Failed to fetch history'], 500);
        }
    }

    // PATCH /api/reception/remise-requests/notifications/mark-read
    public function markAsRead(Request $request): JsonResponse
    {
        $ids = $request->input('notification_ids', []);
        try {
            $res = $this->service->markNotificationsRead($ids, Auth::id());
            return response()->json(['success' => true, 'data' => $res], 200);
        } catch (\Throwable $e) {
            Log::error('markAsRead error: '.$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Failed to mark notifications read'], 500);
        }
    }

    // PATCH /api/reception/remise-requests/{remise_request}/apply-salary
    public function applyToSalary($remise_request): JsonResponse
    {
        try {
            $res = $this->service->applyToSalary((int)$remise_request, Auth::id());
            return response()->json(['success' => true, 'data' => $res], 200);
        } catch (\Throwable $e) {
            Log::error('applyToSalary error: '.$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Failed to apply to salary'], 500);
        }
    }
}
