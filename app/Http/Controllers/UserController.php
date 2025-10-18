<?php

namespace App\Http\Controllers;

use Storage;
use App\Http\Enum\RoleSystemEnum;
use App\Http\Resources\UserDoctorResource;
use App\Http\Resources\UserResource;
use App\Models\Doctor;
use App\Models\User;
use App\Models\Specialization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Fetch all users that are not soft-deleted with their specializations
        $users = User::with(['activeSpecializations', 'specialization'])
            ->where('deleted_at', null)
            ->paginate();
    
        return UserResource::collection($users);
    }

    public function GetReceptionists(Request $request)
    {
        // Fetch users where the role column is "receptionist"
        $users = User::with(['activeSpecializations', 'specialization'])
            ->where('role', 'receptionist')
            ->paginate();
    
        return UserResource::collection($users);
    }
    
    public function getCurrentUser()
    {
        try {

            $user = Auth::user()->load(['activeSpecializations', 'specialization', 'doctor']);
            return response()->json([
                'success' => true,
                'data' => new UserResource($user),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching user information',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function role()
    {
        try {
            $user = Auth::user();
            
            // Load the doctor relationship to avoid N+1 queries
            $user->load('doctor', 'activeSpecializations');
            
            return response()->json([
                'role' => $user->role,
                'id' => $user->doctor?->id ?? null,
                'specialization_id' => $user->doctor?->specialization_id ?? null,
                'specializations' => $user->activeSpecializations->pluck('id')->toArray(),
                'all_specializations' => $user->getAllSpecializations()->pluck('id')->toArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching user role information',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all active specializations
     */
    public function getSpecializations()
    {
        $specializations = Specialization::active()
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'color', 'code']);

        return response()->json([
            'success' => true,
            'data' => $specializations
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|unique:users,email',
            'phone' => 'required|string',
            'fichenavatte_max' => 'nullable|numeric',
            'salary' => 'nullable|numeric',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'role' => 'required|string',
            'is_active' => 'nullable|boolean',
            'password' => 'required|string|min:8',
            'specializations' => 'nullable|array',
            'specializations.*' => 'exists:specializations,id',
            'main_specialization_id' => 'nullable|exists:specializations,id', // For doctor table
        ]);
    
        // Handle file upload for avatar
        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatar = $request->file('avatar');
            $fileName = $validatedData['name'] . '-' . time() . '.' . $avatar->getClientOriginalExtension();
            $avatarPath = $avatar->storeAs('Users', $fileName, 'public');
        }
    
        // Create the user
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'phone' => $validatedData['phone'],
            'fichenavatte_max' => $validatedData['fichenavatte_max'],
            'salary' => $validatedData['salary'],
            'is_active' => $validatedData['is_active'] ?? 0,
            'password' => Hash::make($validatedData['password']),
            'role' => $validatedData['role'],
            'avatar' => $avatarPath,
            'created_by' => Auth::id(),
        ]);

        // Assign roles using Spatie
        $user->assignRole($validatedData['role']);

        // Create doctor record if role is doctor
        if ($validatedData['role'] === 'doctor') {
            $doctor = Doctor::create([
                'user_id' => $user->id,
                'specialization_id' => $validatedData['main_specialization_id'] ?? null,
                // Add other doctor-specific fields as needed
            ]);
        }

        // Sync multiple specializations if provided
        if (isset($validatedData['specializations']) && !empty($validatedData['specializations'])) {
            $user->syncSpecializations($validatedData['specializations']);
        }
    
        return new UserResource($user->load(['activeSpecializations', 'specialization']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        // Normalize boolean and numeric incoming values
        if ($request->has('is_active')) {
            $request->merge(['is_active' => filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN) ? 1 : 0]);
        } else {
            $request->merge(['is_active' => 0]);
        }

        if ($request->has('fichenavatte_max')) {
            $request->merge(['fichenavatte_max' => is_numeric($request->input('fichenavatte_max')) ? (int) $request->input('fichenavatte_max') : null]);
        }

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|unique:users,email,' . $id,
            'phone' => 'required|string|min:10|max:15',
            'is_active' => 'nullable|boolean',
            'salary' => 'nullable|numeric',
            'fichenavatte_max' => 'nullable|integer',
            'password' => 'nullable|string|min:8',
            'role' => 'required|string',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'specializations' => 'nullable|array',
            'specializations.*' => 'exists:specializations,id',
            'main_specialization_id' => 'nullable|exists:specializations,id',
        ]);

        // Prepare data for updating
        $updateData = [
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'phone' => $validatedData['phone'],
            'salary' => $validatedData['salary'],
            'fichenavatte_max' => $validatedData['fichenavatte_max'],
            'is_active' => $validatedData['is_active'],
            'role' => $validatedData['role'],
        ];

        // Handle avatar upload if present
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar) {
                Storage::delete($user->avatar);
            }
            $updateData['avatar'] = $request->file('avatar')->store('users', 'public');
        }

        // Handle password update if provided
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->input('password'));
        }

        // Update user data
        $user->update($updateData);

        // Update role using Spatie
        $user->syncRoles([$validatedData['role']]);

       

        // Sync multiple specializations if provided
        if (isset($validatedData['specializations'])) {
            if (empty($validatedData['specializations'])) {
                // If empty array, deactivate all specializations
                $user->userSpecializations()->update(['status' => 'inactive']);
            } else {
                $user->syncSpecializations($validatedData['specializations']);
            }
        }

        // Respond with the updated user data
        return response()->json([
            'success' => true,
            'user' => new UserResource($user->load(['activeSpecializations', 'specialization'])),
        ]);
    }

    public function ChangeRole($userId, Request $request)
    {
        $user = User::findOrFail($userId);
        
        $validatedData = $request->validate([
            'role' => 'required|string|in:admin,doctor,receptionist',
        ]);

        // Update role field
        $user->role = $validatedData['role'];
        $user->save();

        // Sync roles using Spatie
        $user->syncRoles([$validatedData['role']]);
        
        return response()->json([
            "success" => true,
        ]);
    }
    
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::with(['activeSpecializations', 'specialization', 'doctor'])
            ->findOrFail($id);
            
        return new UserResource($user);
    }

    public function search(Request $request)
    {
        $searchTerm = $request->query('query');
        $role = $request->query('role');
        
        // If search term is empty, return an empty collection
        if (empty($searchTerm)) {
            return UserResource::collection(
                User::with(['activeSpecializations', 'specialization'])
                    ->orderBy('created_at', 'desc')
                    ->get()
            );
        }
    
        $users = User::with(['activeSpecializations', 'specialization'])
            ->where(function($query) use ($searchTerm) {
                $query->where('name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('email', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('phone', 'LIKE', "%{$searchTerm}%");
            });

        if ($role) {
            $users->where('role', $role);
        }
        
        $users = $users->orderBy('created_at', 'desc')
            ->paginate();
    
        return UserResource::collection($users);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $authUser = Auth::user();

        // Prevent deleting a SuperAdmin if the authenticated user is not a SuperAdmin
        if ($user->role === RoleSystemEnum::SuperAdmin) {
            return response()->json([
                'message' => 'You cannot delete a Super Admin',
            ], 403);
        }

        $user->delete(); // Uses SoftDeletes if enabled

        return response()->noContent(); // Returns HTTP 204 No Content
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids'); // Retrieves 'ids' from the request body

        if (!is_array($ids) || empty($ids)) {
            return response()->json(['message' => 'Invalid input'], 422);
        }

        // Convert all IDs to integers to avoid SQL injection risks
        $ids = array_map('intval', $ids);

        // Exclude Super Admins from being deleted
        $usersToDelete = User::whereIn('id', $ids)->where('role', '!=', RoleSystemEnum::SuperAdmin)->pluck('id')->toArray();

        if (empty($usersToDelete)) {
            return response()->json(['message' => 'No users deleted (Super Admins cannot be deleted)'], 403);
        }

        User::whereIn('id', $usersToDelete)->delete();

        return response()->json(['message' => 'Users deleted successfully!'], 200);
    }

    public function notify(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'message' => 'nullable|string'
        ]);

        $user = User::findOrFail($id);
        $amount = (float) $request->input('amount', 0);
        $current = (float) ($user->balance ?? 0);
        $max = (float) ($user->fichenavatte_max ?? 0);

        // Check if adding amount would exceed max
        if ($max > 0 && ($current + $amount) > $max) {
            return response()->json([
                'success' => false,
                'message' => "Adding {$amount} would exceed maximum allowed balance ({$max})"
            ], 422);
        }

        // Update user balance (if you have a balance field)
        // $user->balance = $current + $amount;
        // $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User notified successfully',
            'data' => [
                'user' => new UserResource($user),
                'amount_added' => $amount
            ]
        ]);
    }
}
