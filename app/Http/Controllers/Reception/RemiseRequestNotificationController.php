<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reception\ApproveRemiseRequestRequest;
use App\Http\Requests\Reception\CreateRemiseRequestRequest;
use App\Http\Requests\Reception\MarkNotificationsAsReadRequest;
use App\Http\Requests\Reception\RejectRemiseRequestRequest;
use App\Models\Reception\RemiseRequest;
use App\Models\Reception\ficheNavetteItem;
use App\Models\Reception\RemiseRequestPrestation;


use App\Models\Reception\RemiseRequestPrestationContribution;

use App\Services\Reception\RemiseRequestNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
    public function getRequestDetails(Request $request): JsonResponse {
        try {
            $remise = RemiseRequest::with(['prestations.contributions'])->findOrFail($requestId);
            dd($remise);

            return response()->json(['success' => true, 'data' => $remise], 200);
        } catch (\Throwable $e) {
            Log::error('getRequestDetails error: '.$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Failed to fetch request details'], 500);
        }
    }

    // POST /api/reception/remise-requests
    public function createRequest(CreateRemiseRequestRequest $request): JsonResponse
    {
        try {
            $payload = $request->validated();
       
            // Service signature expects a single array payload
            $res = $this->service->createRequest($payload);
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

    // NEW: Get contributions for a specific group
    public function getGroupContributions($groupId): JsonResponse
    {
        try {
            $contributions = RemiseRequestPrestationContribution::with([
                'prestationContribution.prestation',
                'user',
                'doctor'
            ])
            ->whereHas('prestationContribution.remiseRequest', function($query) use ($groupId) {
                $query->where('group_id', $groupId);
            })
            ->orderBy('created_at', 'desc')
            ->get();

            $transformedData = $contributions->map(function($contribution) {
                return [
                    'id' => $contribution->id,
                    'prestation_id' => $contribution->prestationContribution->prestation_id,
                    'prestation_name' => $contribution->prestationContribution->prestation->name,
                    'prestation_code' => $contribution->prestationContribution->prestation->internal_code,
                    'user_id' => $contribution->user_id,
                    'doctor_id' => $contribution->doctor_id,
                    'contributor_name' => $contribution->user ? $contribution->user->name : ($contribution->doctor ? $contribution->doctor->name : 'Unknown'),
                    'contributor_type' => $contribution->user ? 'user' : 'doctor',
                    'amount' => $contribution->amount,
                    'status' => $contribution->status,
                    'request_id' => $contribution->prestationContribution->remise_request_id,
                    'created_at' => $contribution->created_at,
                    'approved_at' => $contribution->approved_at,
                    'rejected_at' => $contribution->rejected_at,
                    'comments' => $contribution->comments
                ];
            });

            return response()->json(['success' => true, 'data' => $transformedData], 200);
        } catch (\Throwable $e) {
            Log::error('getGroupContributions error: '.$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Failed to fetch group contributions'], 500);
        }
    }

    // NEW: Save contribution drafts
    public function saveContributionDrafts(Request $request): JsonResponse
    {
        try {
            $payload = $request->validate([
                'group_id' => 'required|integer',
                'fiche_navette_id' => 'required|string',
                'contributions' => 'required|array',
                'contributions.*.prestation_id' => 'required|integer',
                'contributions.*.user_id' => 'nullable|integer',
                'contributions.*.doctor_id' => 'nullable|integer',
                'contributions.*.user_amount' => 'nullable|numeric|min:0',
                'contributions.*.doctor_amount' => 'nullable|numeric|min:0',
                'contributions.*.status' => 'nullable|string|in:draft,pending,approved,rejected'
            ]);

            DB::beginTransaction();

            foreach ($payload['contributions'] as $contributionData) {
                // Check if contribution already exists
                $existingContribution = RemiseRequestPrestationContribution::where([
                    'prestation_id' => $contributionData['prestation_id'],
                    'user_id' => $contributionData['user_id'],
                    'doctor_id' => $contributionData['doctor_id']
                ])->first();

                if ($existingContribution && $existingContribution->status !== 'draft') {
                    // Skip if already processed
                    continue;
                }

                // Create or update draft contribution
                RemiseRequestPrestationContribution::updateOrCreate([
                    'prestation_id' => $contributionData['prestation_id'],
                    'user_id' => $contributionData['user_id'],
                    'doctor_id' => $contributionData['doctor_id']
                ], [
                    'amount' => $contributionData['user_amount'] ?? $contributionData['doctor_amount'] ?? 0,
                    'status' => $contributionData['status'] ?? 'draft',
                    'group_id' => $payload['group_id'],
                    'fiche_navette_id' => $payload['fiche_navette_id']
                ]);
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Contribution drafts saved successfully'], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('saveContributionDrafts error: '.$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Failed to save contribution drafts'], 500);
        }
    }
     public function getFichePrestations(Request $request): JsonResponse
    {   
        $payload = $request->validate([
            'fiche_navette_id' => 'required',
            'prestation_ids' => 'nullable|array',
            'prestation_ids.*' => 'integer',
            'users' => 'nullable|array',
        ]);

        try {
            $ficheId = $payload['fiche_navette_id'];
            $prestationIds = $payload['prestation_ids'] ?? [];
            $usersParam = $payload['users'] ?? [];

            Log::info('getFichePrestations started', [
                'fiche_id' => $ficheId,
                'prestation_ids' => $prestationIds,
                'users_count' => count($usersParam)
            ]);

            // Step 1: Get prestation ids from fiche_navette_items
            $itemQuery = ficheNavetteItem::where('fiche_navette_id', $ficheId);
            $allItems = $itemQuery->get(['id', 'prestation_id']);
            
            Log::info('Step 1 - All items in fiche', [
                'items_count' => $allItems->count(),
                'items' => $allItems->toArray()
            ]);

            // Get prestation IDs from items (including package items)
            $prestationIdsFromItems = [];
            
            foreach ($allItems as $item) {
                if ($item->prestation_id) {
                    $prestationIdsFromItems[] = $item->prestation_id;
                }
                
                // If it's a package, get prestations from the package
                if ($item->package_id) {
                    // You might need to adjust this based on your package structure
                    $packagePrestations = \App\Models\CONFIGURATION\PrestationPackageitem::where('package_id', $item->package_id)
                        ->pluck('prestation_id')
                        ->toArray();
                    $prestationIdsFromItems = array_merge($prestationIdsFromItems, $packagePrestations);
                }
            }
            
            $prestationIdsFromItems = array_unique($prestationIdsFromItems);
            
            // Filter by requested prestation IDs if provided
            if (!empty($prestationIds)) {
                $prestationIdsFromItems = array_intersect($prestationIdsFromItems, $prestationIds);
            }

            Log::info('Step 2 - Found prestations', [
                'prestations_from_items' => $prestationIdsFromItems
            ]);

            // Return early if no prestations found
            if (empty($prestationIdsFromItems)) {
                return response()->json([
                    'success' => true, 
                    'data' => [
                        'contributions' => [],
                        'debug' => [
                            'step' => 'no_prestations_found',
                            'fiche_id' => $ficheId,
                            'all_items' => $allItems->toArray(),
                            'found_prestations' => $prestationIdsFromItems
                        ]
                    ]
                ], 200);
            }

            // Step 3: Find remise_request_prestation records
            $remiseRequestPrestations = RemiseRequestPrestation::whereIn('prestation_id', $prestationIdsFromItems)->get();
            
            Log::info('Step 3 - Found remise request prestations', [
                'remise_prestations_count' => $remiseRequestPrestations->count(),
                'remise_prestations' => $remiseRequestPrestations->toArray()
            ]);

            if ($remiseRequestPrestations->isEmpty()) {
                return response()->json([
                    'success' => true, 
                    'data' => [
                        'contributions' => [],
                        'debug' => [
                            'step' => 'no_remise_prestations_found',
                            'prestations_searched' => $prestationIdsFromItems,
                            'remise_prestations_table_count' => RemiseRequestPrestation::count()
                        ]
                    ]
                ], 200);
            }

            $remisePrestationIds = $remiseRequestPrestations->pluck('id')->toArray();

            // Step 4: Get user IDs
            $userIds = [];
            foreach ($usersParam as $u) {
                if (is_array($u) && isset($u['id'])) {
                    $userIds[] = (int) $u['id'];
                } elseif (is_numeric($u)) {
                    $userIds[] = (int) $u;
                }
            }
            $userIds = array_values(array_unique($userIds));

            Log::info('Step 4 - User IDs', [
                'user_ids' => $userIds
            ]);

            // Step 5: Query contributions
            $query = RemiseRequestPrestationContribution::whereIn('remise_request_prestation_id', $remisePrestationIds);

            if (!empty($userIds)) {
                $query->whereIn('user_id', $userIds);
            }

            $contributions = $query->get();

            Log::info('Step 5 - Found contributions', [
                'contributions_count' => $contributions->count(),
                'contributions' => $contributions->toArray()
            ]);

            // Step 6: Transform data (simplified to avoid relationship errors)
            $data = $contributions->map(function ($c) {
                return [
                    'id' => $c->id,
                    'remise_request_prestation_id' => $c->remise_request_prestation_id,
                    'user_id' => $c->user_id,
                    'amount' => $c->approved_amount ?? $c->proposed_amount,
                    'status' => $c->status ?? 'pending',
                    'role' => $c->role ?? null,
                    'created_at' => $c->created_at,
                ];
            });

            return response()->json([
                'success' => true, 
                'data' => [
                    'contributions' => $data,
                    'debug' => [
                        'step' => 'success',
                        'prestations_found' => $prestationIdsFromItems,
                        'remise_prestation_ids' => $remisePrestationIds,
                        'user_ids' => $userIds,
                        'contributions_count' => $contributions->count()
                    ]
                ]
            ], 200);

        } catch (\Throwable $e) {
            Log::error('getFichePrestations error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false, 
                'message' => 'Failed to fetch fiche prestations: ' . $e->getMessage(),
                'debug' => [
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine(),
                    'error_message' => $e->getMessage()
                ]
            ], 500);
        }
    }
    // NEW: Update contribution status
    public function updateContributionStatus($contributionId, Request $request): JsonResponse
    {
        try {
            $payload = $request->validate([
                'status' => 'required|string|in:pending,approved,rejected',
                'comments' => 'nullable|string'
            ]);

            $contribution = RemiseRequestPrestationContribution::findOrFail($contributionId);
            
            $contribution->update([
                'status' => $payload['status'],
                'comments' => $payload['comments'] ?? null,
                $payload['status'] . '_at' => now(),
                $payload['status'] . '_by' => Auth::id()
            ]);

            return response()->json(['success' => true, 'data' => $contribution], 200);
        } catch (\Throwable $e) {
            Log::error('updateContributionStatus error: '.$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Failed to update contribution status'], 500);
        }
    }
}
