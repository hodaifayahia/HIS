<?php

namespace App\Http\Controllers\Bank;

use App\Http\Controllers\Controller;
use App\Models\BankAccountTransactionPack;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class BankAccountTransactionPackController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = BankAccountTransactionPack::with(['bankAccountTransaction', 'user']);

            // Apply filters
            if ($request->has('search') && ! empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('reference', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('bankAccountTransaction', function ($subQ) use ($search) {
                            $subQ->where('reference', 'like', "%{$search}%");
                        });
                });
            }

            if ($request->has('status') && ! empty($request->status)) {
                $query->where('status', $request->status);
            }

            if ($request->has('date_from') && ! empty($request->date_from)) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to') && ! empty($request->date_to)) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $perPage = $request->get('per_page', 15);
            $packs = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $packs,
                'message' => 'Bank account transaction packs retrieved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve bank account transaction packs: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // This method is typically used for web forms, not API
        return response()->json([
            'success' => false,
            'message' => 'Method not implemented for API',
        ], 405);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'bank_account_transaction_id' => 'required|exists:bank_account_transactions,id',
                'reference' => 'required|string|max:255|unique:bank_account_transaction_packs,reference',
                'description' => 'nullable|string|max:1000',
                'status' => ['required', Rule::in(['pending', 'processed', 'cancelled'])],
                'total_amount' => 'required|numeric|min:0.01',
                'transaction_count' => 'required|integer|min:1',
                'attachment_path' => 'nullable|string|max:500',
                'processed_at' => 'nullable|date',
                'notes' => 'nullable|string|max:1000',
            ]);

            $validatedData['user_id'] = auth()->id();

            $pack = BankAccountTransactionPack::create($validatedData);

            return response()->json([
                'success' => true,
                'data' => $pack->load(['bankAccountTransaction', 'user']),
                'message' => 'Bank account transaction pack created successfully',
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create bank account transaction pack: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(BankAccountTransactionPack $bankAccountTransactionPack): JsonResponse
    {
        try {
            $pack = $bankAccountTransactionPack->load(['bankAccountTransaction', 'user']);

            return response()->json([
                'success' => true,
                'data' => $pack,
                'message' => 'Bank account transaction pack retrieved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve bank account transaction pack: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BankAccountTransactionPack $bankAccountTransactionPack)
    {
        // This method is typically used for web forms, not API
        return response()->json([
            'success' => false,
            'message' => 'Method not implemented for API',
        ], 405);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BankAccountTransactionPack $bankAccountTransactionPack): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'bank_account_transaction_id' => 'required|exists:bank_account_transactions,id',
                'reference' => ['required', 'string', 'max:255', Rule::unique('bank_account_transaction_packs')->ignore($bankAccountTransactionPack->id)],
                'description' => 'nullable|string|max:1000',
                'status' => ['required', Rule::in(['pending', 'processed', 'cancelled'])],
                'total_amount' => 'required|numeric|min:0.01',
                'transaction_count' => 'required|integer|min:1',
                'attachment_path' => 'nullable|string|max:500',
                'processed_at' => 'nullable|date',
                'notes' => 'nullable|string|max:1000',
            ]);

            $bankAccountTransactionPack->update($validatedData);

            return response()->json([
                'success' => true,
                'data' => $bankAccountTransactionPack->load(['bankAccountTransaction', 'user']),
                'message' => 'Bank account transaction pack updated successfully',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update bank account transaction pack: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BankAccountTransactionPack $bankAccountTransactionPack): JsonResponse
    {
        try {
            // Check if pack can be deleted (only if status is not processed)
            if ($bankAccountTransactionPack->status === 'processed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete a processed bank account transaction pack',
                ], 422);
            }

            // Delete associated attachment if exists
            if ($bankAccountTransactionPack->attachment_path && Storage::disk('public')->exists($bankAccountTransactionPack->attachment_path)) {
                Storage::disk('public')->delete($bankAccountTransactionPack->attachment_path);
            }

            $bankAccountTransactionPack->delete();

            return response()->json([
                'success' => true,
                'message' => 'Bank account transaction pack deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete bank account transaction pack: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get statistics for bank account transaction packs
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $stats = [
                'total_packs' => BankAccountTransactionPack::count(),
                'pending_packs' => BankAccountTransactionPack::where('status', 'pending')->count(),
                'processed_packs' => BankAccountTransactionPack::where('status', 'processed')->count(),
                'cancelled_packs' => BankAccountTransactionPack::where('status', 'cancelled')->count(),
                'total_amount' => BankAccountTransactionPack::sum('total_amount'),
                'processed_amount' => BankAccountTransactionPack::where('status', 'processed')->sum('total_amount'),
                'average_transaction_count' => BankAccountTransactionPack::avg('transaction_count'),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Bank account transaction pack statistics retrieved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process a bank account transaction pack
     */
    public function process(BankAccountTransactionPack $bankAccountTransactionPack): JsonResponse
    {
        try {
            DB::beginTransaction();

            if ($bankAccountTransactionPack->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending packs can be processed',
                ], 422);
            }

            $bankAccountTransactionPack->update([
                'status' => 'processed',
                'processed_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $bankAccountTransactionPack->load(['bankAccountTransaction', 'user']),
                'message' => 'Bank account transaction pack processed successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to process bank account transaction pack: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel a bank account transaction pack
     */
    public function cancel(BankAccountTransactionPack $bankAccountTransactionPack): JsonResponse
    {
        try {
            if ($bankAccountTransactionPack->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending packs can be cancelled',
                ], 422);
            }

            $bankAccountTransactionPack->update(['status' => 'cancelled']);

            return response()->json([
                'success' => true,
                'data' => $bankAccountTransactionPack->load(['bankAccountTransaction', 'user']),
                'message' => 'Bank account transaction pack cancelled successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel bank account transaction pack: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all transaction packs for a specific transaction
     */
    public function getPackUsers(int $transactionId): JsonResponse
    {
        try {
            // Get all packs for this transaction
            $packs = BankAccountTransactionPack::with(['user'])
                ->where('bank_account_transaction_id', $transactionId)
                ->orderBy('created_at', 'desc')
                ->get();

            // Format the data for the frontend
            $packUsers = $packs->map(function ($pack) {
                return [
                    'id' => $pack->id,
                    'employee_id' => $pack->user ? $pack->user->employee_id ?? $pack->user->id : 'N/A',
                    'name' => $pack->name,
                    'description' => $pack->description,
                    'amount' => $pack->amount,
                    'reference' => $pack->reference,
                    'status' => 'processed',
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $packUsers,
                'message' => 'Pack users retrieved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve pack users: '.$e->getMessage(),
            ], 500);
        }
    }
}
