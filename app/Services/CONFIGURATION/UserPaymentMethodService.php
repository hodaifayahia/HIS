<?php
// filepath: d:\Projects\AppointmentSystem\AppointmentSystem-main\app\Services\CONFIGURATION\UserPaymentMethodService.php

namespace App\Services\CONFIGURATION;

use App\Models\User;
use App\Models\CONFIGURATION\UserPaymentMethod;
use App\Enums\Payment\PaymentMethodEnum;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class UserPaymentMethodService
{
    /**
     * Get all users with their payment accesses, transformed for frontend.
     */
    public function getAllUsersWithPaymentAccess(): \Illuminate\Database\Eloquent\Collection
    {
        return UserPaymentMethod::with('user')->get();
    }

    /**
     * Get a single user with their payment accesses.
     */
    public function getUserWithPaymentAccess(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return UserPaymentMethod::with('user')->where('user_id', $user->id)->get();
    }

    /**
     * Create a new user with payment methods (for non-edit mode)
     */
    public function createUserWithPaymentMethods(array $data): User
    {
        DB::beginTransaction();
        try {
            // Create the user first
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'email_verified_at' => now(),
            ]);

            // Create the user payment method record
            UserPaymentMethod::create([
                'user_id' => $user->id,
                'payment_method_key' => $data['allowedMethods'] ?? [],
                'status' => $data['status'] ?? 'active'
            ]);

            DB::commit();
            return $user;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Failed to create user with payment methods: " . $e->getMessage(), [
                'data' => $data,
                'exception' => $e
            ]);
            throw $e;
        }
    }

    /**
     * Bulk assigns payment methods to multiple users.
     */
    public function assignPaymentMethodsToUsersBulk(array $paymentMethodKeys, array $userIds, string $status): int
    {
        $assignedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($userIds as $userId) {
                // Find or create the UserPaymentMethod record for this user
                $userPaymentRecord = UserPaymentMethod::firstOrNew(['user_id' => $userId]);

                // Get existing methods, or an empty array if none exist
                $existingMethods = $userPaymentRecord->payment_method_key ?? [];

                // Merge the new keys with the existing unique keys
                $allMethods = array_unique(array_merge($existingMethods, $paymentMethodKeys));

                // Update the model instance's attributes
                $userPaymentRecord->payment_method_key = $allMethods;
                $userPaymentRecord->status = $status;
                $userPaymentRecord->save();
                
                $assignedCount++;
            }

            DB::commit();
            return $assignedCount;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Failed to assign multiple payment methods to users in bulk: " . $e->getMessage(), [
                'payment_method_keys' => $paymentMethodKeys,
                'userIds' => $userIds,
                'status' => $status,
                'exception' => $e
            ]);
            throw $e;
        }
    }

    /**
     * Updates an existing user's payment methods.
     */
    public function updateUserPaymentMethods(User $user, array $formRequest): UserPaymentMethod
    {
        DB::beginTransaction();
        try {
            // Find the existing record or create a new one
            $userPaymentRecord = UserPaymentMethod::firstOrNew(['user_id' => $user->id]);
            
            // Set the new payment methods and status
            $userPaymentRecord->payment_method_key = $formRequest['allowedMethods'] ?? [];
            $userPaymentRecord->status = $formRequest['status'] ?? 'active';

            $userPaymentRecord->save();
            
            DB::commit();

            // Load the user relationship for the response
            $userPaymentRecord->load('user');
            return $userPaymentRecord;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete a user and their payment method records.
     */
    public function deleteUser(User $user): ?bool
    {
        DB::beginTransaction();
        try {
            // Delete the user payment method records first
            UserPaymentMethod::where('user_id', $user->id)->delete();
            
            // Then delete the user
            $result = $user->delete();
            
            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}