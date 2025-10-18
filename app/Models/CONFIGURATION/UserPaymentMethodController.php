<?php
// filepath: d:\Projects\AppointmentSystem\AppointmentSystem-main\app\Http\Controllers\CONFIGURATION\UserPaymentMethodController.php

namespace App\Http\Controllers\CONFIGURATION;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Services\CONFIGURATION\UserPaymentMethodService;
use App\Http\Resources\CONFIGURATION\UserResource;
use App\Http\Resources\CONFIGURATION\UserPaymentMethodResource;
use App\Http\Requests\CONFIGURATION\UserPaymentMethodRequest;
use App\Enums\Payment\PaymentMethodEnum;

class UserPaymentMethodController extends Controller
{
    protected $userPaymentMethodService;

    public function __construct(UserPaymentMethodService $userPaymentMethodService)
    {
        $this->userPaymentMethodService = $userPaymentMethodService;
    }

    /**
     * Display a listing of the users with their payment access.
     */
    public function index()
    {
        try {
            $users = $this->userPaymentMethodService->getAllUsersWithPaymentAccess();
            return response()->json([
                'success' => true,
                'data' => UserPaymentMethodResource::collection($users)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load users with payment access.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store: Handle both bulk assignment AND new user creation
     */
    public function store(Request $request)
    {
        try {
            // Manual validation to avoid the enum issue temporarily
            $request->validate([
                'userIds' => 'nullable|array',
                'userIds.*' => 'integer|exists:users,id',
                'paymentMethodKeys' => 'nullable|array',
                'paymentMethodKeys.*' => 'string|in:prepayment,postpayment,versement',
                'allowedMethods' => 'nullable|array',
                'allowedMethods.*' => 'string|in:prepayment,postpayment,versement',
                'status' => 'required|string|in:active,inactive,suspended',
                'name' => 'nullable|string|max:255',
                'email' => 'nullable|email',
                'password' => 'nullable|string|min:8',
            ]);

            // Check if this is a bulk assignment (has userIds) or new user creation
            if ($request->has('userIds') && !empty($request->input('userIds'))) {
                // Bulk assignment
                $paymentMethodKeys = $request->input('paymentMethodKeys', []);
                $userIds = $request->input('userIds', []);
                $status = $request->input('status', 'active');

                $assignedCount = $this->userPaymentMethodService->assignPaymentMethodsToUsersBulk(
                    $paymentMethodKeys,
                    $userIds,
                    $status
                );

                return response()->json([
                    'success' => true,
                    'message' => "Successfully assigned payment methods to {$assignedCount} user(s).",
                    'data' => [
                        'assigned_count' => $assignedCount,
                    ]
                ], 200);
            } else {
                // New user creation
                $userData = [
                    'name' => $request->input('name'),
                    'email' => $request->input('email'),
                    'password' => $request->input('password'),
                    'allowedMethods' => $request->input('allowedMethods', []),
                    'status' => $request->input('status', 'active')
                ];

                $user = $this->userPaymentMethodService->createUserWithPaymentMethods($userData);

                return response()->json([
                    'success' => true,
                    'message' => 'User created successfully with payment methods.',
                    'data' => new UserResource($user)
                ], 201);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process request.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified user with their payment access.
     */
    public function show(User $user)
    {
        try {
            $userPaymentMethods = $this->userPaymentMethodService->getUserWithPaymentAccess($user);
            return response()->json([
                'success' => true,
                'data' => UserPaymentMethodResource::collection($userPaymentMethods)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load user payment access.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified user's payment methods.
     */
    public function update(Request $request, $user)
    {
       $user =  User::findOrFail($user);
        try {
            // Manual validation
            $request->validate([
                'allowedMethods' => 'nullable|array',
                'allowedMethods.*' => 'string|in:prepayment,postpayment,versement',
                'status' => 'required|string|in:active,inactive,suspended',
            ]);

            $userPaymentMethod = $this->userPaymentMethodService->updateUserPaymentMethods(
                $user,
                $request->all()
            );
            
            return response()->json([
                'success' => true,
                'message' => 'User payment methods updated successfully.',
                'data' => new UserPaymentMethodResource($userPaymentMethod)
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user payment methods.', 
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        try {
            $this->userPaymentMethodService->deleteUser($user);
            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user.', 
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the list of available payment methods from the Enum.
     */
    public function getPaymentMethods()
    {
        try {
            return response()->json([
                'success' => true,
                'data' => PaymentMethodEnum::toArrayForDropdown()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load payment methods.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}