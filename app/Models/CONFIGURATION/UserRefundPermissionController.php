<?php

namespace App\Http\Controllers\CONFIGURATION;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class UserRefundPermissionController extends Controller
{
    /**
     * Get all users with their refund permission status
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->integer('per_page', 15);
            $searchQuery = $request->get('search', '');

            $query = User::select('id', 'name', 'email', 'avatar', 'role')
                ->when($searchQuery, function ($q) use ($searchQuery) {
                    return $q->where(function ($query) use ($searchQuery) {
                        $query->where('name', 'LIKE', "%{$searchQuery}%")
                              ->orWhere('email', 'LIKE', "%{$searchQuery}%");
                    });
                })
                ->orderBy('name');

            $users = $query->paginate($perPage);
            
            // Add permission status to each user
            $users->getCollection()->transform(function ($user) {
                $user->can_refund = $user->hasPermissionTo('refund.approve');
                return $user;
            });

            return response()->json($users);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to load users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Grant refund permission to a user
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        try {
            $user = User::findOrFail($request->user_id);
            
            // Ensure the permission exists
            $permission = Permission::firstOrCreate(['name' => 'refund.approve']);
            
            if (!$user->hasPermissionTo('refund.approve')) {
                $user->givePermissionTo($permission);
                
                return response()->json([
                    'success' => true,
                    'message' => "Refund permission granted to {$user->name}",
                    'data' => [
                        'user_id' => $user->id,
                        'can_refund' => true
                    ]
                ], 201);
            }

            return response()->json([
                'success' => true,
                'message' => "{$user->name} already has refund permission",
                'data' => [
                    'user_id' => $user->id,
                    'can_refund' => true
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to grant permission',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Revoke refund permission from a user
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        try {
            if ($user->hasPermissionTo('refund.approve')) {
                $user->revokePermissionTo('refund.approve');
                
                return response()->json([
                    'success' => true,
                    'message' => "Refund permission revoked from {$user->name}",
                    'data' => [
                        'user_id' => $user->id,
                        'can_refund' => false
                    ]
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => "{$user->name} doesn't have refund permission",
                'data' => [
                    'user_id' => $user->id,
                    'can_refund' => false
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to revoke permission',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Check if the authenticated user can approve refunds
     */
    public function checkauth(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $canRefund = $user ? $user->hasPermissionTo('refund.approve') : false;

            return response()->json([
                'success' => true,
                'data' => [
                    'can_refund' => $canRefund,
                    'user_id' => $user ? $user->id : null,
                    'user_name' => $user ? $user->name : null
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => [
                    'can_refund' => false,
                    'error' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Get all users who can approve refunds
     */
    public function getApprovers(): JsonResponse
    {
        try {
            $approvers = User::permission('refund.approve')
                ->select('id', 'name', 'email', 'avatar')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $approvers,
                'count' => $approvers->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load approvers',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}