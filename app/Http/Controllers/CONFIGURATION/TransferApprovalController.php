<?php
// app/Http/Controllers/TransferApprovalController.php

namespace App\Http\Controllers\Configuration;

use App\Http\Controllers\Controller;

use App\Models\Configuration\TransferApproval;
use App\Http\Requests\Configuration\StoreTransferApprovalRequest;
use App\Http\Requests\Configuration\UpdateTransferApprovalRequest;
use App\Http\Resources\Configuration\TransferApprovalResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransferApprovalController extends Controller
{
    /**
     * Display a listing of transfer approvals
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = TransferApproval::with('user:id,name,email,role')
                        ->latest();

            // Apply filters
            if ($request->filled('status')) {
                if ($request->status === 'active') {
                    $query->active();
                } elseif ($request->status === 'inactive') {
                    $query->inactive();
                }
            }

            if ($request->filled('user_id')) {
                $query->byUser($request->user_id);
            }

            if ($request->filled('role')) {
                $query->whereHas('user', function($q) use ($request) {
                    $q->where('role', $request->role);
                });
            }

            if ($request->filled('min_amount')) {
                $query->where('maximum', '>=', $request->min_amount);
            }

            if ($request->filled('max_amount')) {
                $query->where('maximum', '<=', $request->max_amount);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('user', function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%");
                });
            }

            $perPage = $request->integer('per_page', 15);
            $transferApprovals = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => TransferApprovalResource::collection($transferApprovals->items()),
                'meta' => [
                    'total' => $transferApprovals->total(),
                    'count' => $transferApprovals->count(),
                    'per_page' => $transferApprovals->perPage(),
                    'current_page' => $transferApprovals->currentPage(),
                    'total_pages' => $transferApprovals->lastPage(),
                    'has_more_pages' => $transferApprovals->hasMorePages(),
                ],
                'summary' => $this->getTransferApprovalSummary($request),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load transfer approvals',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created transfer approval
     */
    public function store(StoreTransferApprovalRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            
            // Check if user already has an active transfer approval
            $existingApproval = TransferApproval::byUser($data['user_id'])->active()->first();
            if ($existingApproval) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'User already has an active transfer approval limit'
                ], 422);
            }

            // Set default values
            $data['is_active'] = $data['is_active'] ?? true;

            $transferApproval = TransferApproval::create($data);
            $transferApproval->load('user:id,name,email,role');

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => new TransferApprovalResource($transferApproval),
                'message' => 'Transfer approval limit created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create transfer approval limit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified transfer approval
     */
    public function show(TransferApproval $transferApproval): JsonResponse
    {
        try {
            $transferApproval->load('user:id,name,email,role,avatar');

            return response()->json([
                'success' => true,
                'data' => new TransferApprovalResource($transferApproval)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load transfer approval',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified transfer approval
     */
    public function update(UpdateTransferApprovalRequest $request, TransferApproval $transferApproval): JsonResponse
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

            // If changing user, check if new user already has an active approval
            if (isset($data['user_id']) && $data['user_id'] !== $transferApproval->user_id) {
                $existingApproval = TransferApproval::byUser($data['user_id'])
                    ->active()
                    ->where('id', '!=', $transferApproval->id)
                    ->first();
                    
                if ($existingApproval) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Selected user already has an active transfer approval limit'
                    ], 422);
                }
            }

            $transferApproval->update($data);
            $transferApproval->load('user:id,name,email,role');

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => new TransferApprovalResource($transferApproval),
                'message' => 'Transfer approval limit updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update transfer approval limit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activate/Deactivate transfer approval
     */
    public function toggleStatus(TransferApproval $transferApproval): JsonResponse
    {
        try {
            $newStatus = !$transferApproval->is_active;
            $transferApproval->update(['is_active' => $newStatus]);

            return response()->json([
                'success' => true,
                'data' => new TransferApprovalResource($transferApproval->load('user:id,name,email,role')),
                'message' => 'Transfer approval status updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if user can approve a transfer amount
     */
    public function checkApprovalLimit(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01'
        ]);

        try {
            $canApprove = TransferApproval::canUserApprove($request->user_id, $request->amount);
            $userMaximum = TransferApproval::getMaximumForUser($request->user_id);

            return response()->json([
                'success' => true,
                'data' => [
                    'can_approve' => $canApprove,
                    'user_maximum' => $userMaximum,
                    'requested_amount' => $request->amount,
                    'exceeds_limit' => $request->amount > $userMaximum
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check approval limit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get users without transfer approval limits
     */
    public function getUsersWithoutLimits(): JsonResponse
    {
        try {
            $usersWithLimits = TransferApproval::active()->pluck('user_id');
            
            $usersWithoutLimits = User::whereNotIn('id', $usersWithLimits)
                ->whereIn('role', ['admin', 'manager', 'SuperAdmin']) // Only management roles
                ->select('id', 'name', 'email', 'role')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $usersWithoutLimits
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transfer approval for current user
     */
    public function getMyApprovalLimit(): JsonResponse
    {
        try {
            $approval = TransferApproval::byUser(Auth::id())->active()->first();

            if (!$approval) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active transfer approval limit found for current user',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new TransferApprovalResource($approval->load('user:id,name,email,role'))
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load approval limit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified transfer approval
     */
    public function destroy(TransferApproval $transferApproval): JsonResponse
    {
        try {
            $userName = $transferApproval->user->name;
            $transferApproval->delete();

            return response()->json([
                'success' => true,
                'message' => "Transfer approval limit for {$userName} deleted successfully"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete transfer approval limit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get summary statistics
     */
    private function getTransferApprovalSummary(Request $request): array
    {
        $query = TransferApproval::query();

        // Apply same filters as main query
        if ($request->filled('role')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('role', $request->role);
            });
        }

        $baseQuery = clone $query;

        return [
            'total_limits' => $baseQuery->count(),
            'active_limits' => $query->clone()->active()->count(),
            'inactive_limits' => $query->clone()->inactive()->count(),
            'average_maximum' => $query->clone()->active()->avg('maximum') ?? 0,
            'highest_maximum' => $query->clone()->active()->max('maximum') ?? 0,
            'lowest_maximum' => $query->clone()->active()->min('maximum') ?? 0,
            'by_role' => $this->getLimitsByRole($query->clone()),
        ];
    }

    /**
     * Get limits grouped by user role
     */
    private function getLimitsByRole($query): array
    {
        return $query->with('user:id,role')
            ->get()
            ->groupBy('user.role')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'average_maximum' => $group->avg('maximum'),
                    'total_maximum' => $group->sum('maximum'),
                ];
            })
            ->toArray();
    }
}
