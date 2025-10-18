<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role; // Import the Spatie Role model
use Illuminate\Validation\ValidationException; // For custom validation error handling

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            // Start with all roles
            $query = Role::query();

            // Implement search functionality by role name
            if ($request->has('search') && $request->search != '') {
                $searchTerm = $request->search;
                $query->where('name', 'LIKE', "%{$searchTerm}%");
            }

            // You might want to add pagination here for large datasets
            $roles = $query->get(); // Using get() for simplicity, consider paginate() for production

            return response()->json(['data' => $roles], 200);
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Error fetching roles: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to retrieve roles.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Validate incoming request data
            $request->validate([
                'name' => 'required|string|unique:roles,name|max:255',
                // 'guard_name' => 'nullable|string', // Uncomment if you want to allow setting guard_name from client
            ]);

            // Create the new role
            // Default guard_name to 'web' if not provided or specified
            $role = Role::create([
                'name' => $request->name,
                'guard_name' => $request->guard_name ?? 'web', // Use request guard_name or default to 'web'
            ]);

            return response()->json($role, 201); // 201 Created
        } catch (ValidationException $e) {
            // Handle validation errors specifically
            return response()->json(['errors' => $e->errors()], 422); // 422 Unprocessable Entity
        } catch (\Exception $e) {
            // Catch any other unexpected errors
            \Log::error('Error creating role: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create role.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \Spatie\Permission\Models\Role  $role // Laravel's Route Model Binding
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Role $role)
    {
        // Route Model Binding automatically fetches the role or throws 404 if not found
        return response()->json($role, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Spatie\Permission\Models\Role  $role // Laravel's Route Model Binding
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Role $role)
    {
        try {
            // Validate incoming request data
            $request->validate([
                // 'name' must be unique, but ignore the current role's ID during the unique check
                'name' => 'required|string|unique:roles,name,' . $role->id . '|max:255',
                // 'guard_name' => 'nullable|string', // Uncomment if you want to allow setting guard_name from client
            ]);

            // Update the role
            $role->update([
                'name' => $request->name,
                'guard_name' => $request->guard_name ?? $role->guard_name, // Update guard_name or keep existing
            ]);

            return response()->json($role, 200); // 200 OK
        } catch (ValidationException $e) {
            // Handle validation errors specifically
            return response()->json(['errors' => $e->errors()], 422); // 422 Unprocessable Entity
        } catch (\Exception $e) {
            // Catch any other unexpected errors
            \Log::error('Error updating role ' . $role->id . ': ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update role.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Spatie\Permission\Models\Role  $role // Laravel's Route Model Binding
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Role $role)
    {
        try {
            // Delete the role
            $role->delete();

            return response()->json(null, 204); // 204 No Content
        } catch (\Exception $e) {
            // Catch any unexpected errors
            \Log::error('Error deleting role ' . $role->id . ': ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete role.', 'error' => $e->getMessage()], 500);
        }
    }

    // The `create()` and `edit()` methods are typically used for rendering HTML forms.
    // Since you are building an API with Vue.js, these methods are not usually needed
    // as the frontend handles the form presentation. I've left them empty as per your
    // original code, but they can be removed if strictly API-only.
    public function create()
    {
        // Not typically used in API-only controllers
    }

    public function edit(string $id)
    {
        // Not typically used in API-only controllers
    }
}