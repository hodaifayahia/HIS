<?php

namespace App\Http\Controllers\CONFIGURATION;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class UserCaisseApprovalController extends Controller
{
    /**
     * Get all users with their caisse approval status
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
                $user->can_approve_caisse = $user->hasPermissionTo('caisse.approve');
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
     * Grant caisse approval permission to a user
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        try {
            $user = User::findOrFail($request->user_id);
            
            // Ensure the permission exists
            $permission = Permission::firstOrCreate(['name' => 'caisse.approve']);
            
            if (!$user->hasPermissionTo('caisse.approve')) {
                $user->givePermissionTo($permission);
                
                return response()->json([
                    'success' => true,
                    'message' => "Caisse approval permission granted to {$user->name}",
                    'data' => [
                        'user_id' => $user->id,
                        'can_approve_caisse' => true
                    ]
                ], 201);
            }

            return response()->json([
                'success' => true,
                'message' => "{$user->name} already has caisse approval permission",
                'data' => [
                    'user_id' => $user->id,
                    'can_approve_caisse' => true
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
     * Revoke caisse approval permission from a user
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        try {
            if ($user->hasPermissionTo('caisse.approve')) {
                $user->revokePermissionTo('caisse.approve');
                
                return response()->json([
                    'success' => true,
                    'message' => "Caisse approval permission revoked from {$user->name}",
                    'data' => [
                        'user_id' => $user->id,
                        'can_approve_caisse' => false
                    ]
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => "{$user->name} doesn't have caisse approval permission",
                'data' => [
                    'user_id' => $user->id,
                    'can_approve_caisse' => false
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
     * Check if the authenticated user can approve caisse transactions
     */
    public function checkAuth(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $canApprove = $user ? $user->hasPermissionTo('caisse.approve') : false;

            return response()->json([
                'can_approve_caisse' => $canApprove,
                'user_id' => $user ? $user->id : null,
                'user_name' => $user ? $user->name : null
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'can_approve_caisse' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all users who can approve caisse transactions
     */
    public function getApprovers(): JsonResponse
    {
        try {
            $approvers = User::permission('caisse.approve')
                ->select('id', 'name', 'email', 'avatar')
                ->orderBy('name')
                ->get();

            return response()->json([
                'data' => $approvers,
                'count' => $approvers->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to load approvers',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
