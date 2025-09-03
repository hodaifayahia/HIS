<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class PasswordValidationController extends Controller
{
    /**
     * Validate current authenticated user's password
     */
    public function validateCurrentUserPassword(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string'
        ]);

        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'valid' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $isValid = Hash::check($request->password, $user->password);

        return response()->json([
            'valid' => $isValid,
            'message' => $isValid ? 'Password is valid' : 'Invalid password'
        ]);
    }

    /**
     * Validate specific user's password by user ID
     */
    public function validateUserPassword(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'password' => 'required|string'
        ]);

        $user = User::find($request->user_id);
        
        if (!$user) {
            return response()->json([
                'valid' => false,
                'message' => 'User not found'
            ], 404);
        }

        $isValid = Hash::check($request->password, $user->password);

        return response()->json([
            'valid' => $isValid,
            'message' => $isValid ? 'Password is valid' : 'Invalid password',
            'user_name' => $user->name
        ]);
    }
}
