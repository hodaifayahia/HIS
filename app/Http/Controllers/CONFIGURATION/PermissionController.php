<?php

namespace App\Http\Controllers\CONFIGURATION;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    /**
     * Get all permissions with their assigned users
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->integer('per_page', 15);
            $searchQuery = $request->get('search', '');

            $query = Permission::query()
                ->when($searchQuery, function ($q) use ($searchQuery) {
                    return $q->where('name', 'LIKE', "%{$searchQuery}%");
                })
                ->orderBy('name');

            $permissions = $query->paginate($perPage);

            // Add user count and sample users for each permission
            $permissions->getCollection()->transform(function ($permission) {
                $users = $permission->users()->select('id', 'name', 'email')->limit(5)->get();
                $permission->user_count = $permission->users()->count();
                $permission->sample_users = $users;
                return $permission;
            });

            return response()->json($permissions);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to load permissions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new permission
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|unique:permissions,name|max:255',
            'description' => 'nullable|string|max:500'
        ]);

        try {
            $permission = Permission::create([
                'name' => $request->name,
                'guard_name' => 'web'
            ]);

            // Store description as a custom attribute if needed
            if ($request->description) {
                $permission->description = $request->description;
                $permission->save();
            }

            return response()->json([
                'success' => true,
                'message' => "Permission '{$request->name}' created successfully",
                'data' => $permission
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create permission',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get a specific permission with its users
     */
    public function show(Permission $permission): JsonResponse
    {
        try {
            $users = $permission->users()
                ->select('id', 'name', 'email', 'avatar')
                ->orderBy('name')
                ->get();

            return response()->json([
                'permission' => $permission,
                'users' => $users,
                'user_count' => $users->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to load permission details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a permission
     */
    public function update(Request $request, Permission $permission): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|unique:permissions,name,' . $permission->id . '|max:255',
            'description' => 'nullable|string|max:500'
        ]);

        try {
            $permission->update([
                'name' => $request->name
            ]);

            if ($request->description) {
                $permission->description = $request->description;
                $permission->save();
            }

            return response()->json([
                'success' => true,
                'message' => "Permission updated successfully",
                'data' => $permission
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update permission',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Delete a permission
     */
    public function destroy(Permission $permission): JsonResponse
    {
        try {
            // Check if permission is assigned to any users
            if ($permission->users()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete permission that is assigned to users. Remove all assignments first.'
                ], 422);
            }

            $permission->delete();

            return response()->json([
                'success' => true,
                'message' => "Permission '{$permission->name}' deleted successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete permission',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Assign permission to a user
     */
    public function assignToUser(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'permission_name' => 'required|string|exists:permissions,name'
        ]);

        try {
            $user = User::findOrFail($request->user_id);
            $permission = Permission::where('name', $request->permission_name)->first();

            if (!$user->hasPermissionTo($permission)) {
                $user->givePermissionTo($permission);

                return response()->json([
                    'success' => true,
                    'message' => "Permission '{$permission->name}' granted to {$user->name}",
                    'data' => [
                        'user_id' => $user->id,
                        'permission' => $permission->name
                    ]
                ], 201);
            }

            return response()->json([
                'success' => true,
                'message' => "{$user->name} already has '{$permission->name}' permission",
                'data' => [
                    'user_id' => $user->id,
                    'permission' => $permission->name
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign permission',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Revoke permission from a user
     */
    public function revokeFromUser(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'permission_name' => 'required|string|exists:permissions,name'
        ]);

        try {
            $user = User::findOrFail($request->user_id);
            $permission = Permission::where('name', $request->permission_name)->first();

            if ($user->hasPermissionTo($permission)) {
                $user->revokePermissionTo($permission);

                return response()->json([
                    'success' => true,
                    'message' => "Permission '{$permission->name}' revoked from {$user->name}",
                    'data' => [
                        'user_id' => $user->id,
                        'permission' => $permission->name
                    ]
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => "{$user->name} doesn't have '{$permission->name}' permission",
                'data' => [
                    'user_id' => $user->id,
                    'permission' => $permission->name
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
     * Get all permissions for a specific user
     */
    public function getUserPermissions(User $user): JsonResponse
    {
        try {
            $permissions = $user->permissions()
                ->select('id', 'name')
                ->orderBy('name')
                ->get();

            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ],
                'permissions' => $permissions,
                'count' => $permissions->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to load user permissions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if user has specific permission
     */
    public function checkUserPermission(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'permission_name' => 'required|string'
        ]);

        try {
            $user = User::findOrFail($request->user_id);
            $hasPermission = $user->hasPermissionTo($request->permission_name);

            return response()->json([
                'user_id' => $user->id,
                'permission' => $request->permission_name,
                'has_permission' => $hasPermission
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'has_permission' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get users who have a specific permission
     */
    public function getUsersWithPermission(Request $request): JsonResponse
    {
        $request->validate([
            'permission_name' => 'required|string|exists:permissions,name'
        ]);

        try {
            $permission = Permission::where('name', $request->permission_name)->first();
            $users = $permission->users()
                ->select('id', 'name', 'email', 'avatar')
                ->orderBy('name')
                ->get();

            return response()->json([
                'permission' => $request->permission_name,
                'users' => $users,
                'count' => $users->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to load users with permission',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
