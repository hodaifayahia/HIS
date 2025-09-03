<?php
// app/Http/Controllers/BankAccountTransactionController.php

namespace App\Http\Controllers\Bank;



use App\Http\Controllers\Controller;
use App\Http\Requests\Bank\StoreBankAccountTransactionRequest;
use App\Http\Requests\Bank\UpdateBankAccountTransactionRequest;
use App\Http\Resources\Bank\BankAccountTransactionCollection;
use App\Http\Resources\Bank\BankAccountTransactionResource;
use App\Models\Bank\BankAccountTransaction;
use App\Services\Bank\BankAccountTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankAccountTransactionController extends Controller
{
    protected BankAccountTransactionService $service;

    public function __construct(BankAccountTransactionService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): BankAccountTransactionCollection
    {
        $perPage = $request->integer('per_page', 15);
        
        $filters = $request->only([
            'bank_account_id', 'transaction_type', 'status', 
            'date_from', 'date_to', 'search'
        ]);

        $transactions = $this->service->getAllPaginated($filters, $perPage);

        return new BankAccountTransactionCollection($transactions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBankAccountTransactionRequest $request): JsonResponse
    {
        try {
            $transaction = $this->service->create($request->validated());

            return (new BankAccountTransactionResource($transaction))
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(BankAccountTransaction $bankAccountTransaction): BankAccountTransactionResource
    {
        return new BankAccountTransactionResource(
            $this->service->findById($bankAccountTransaction->id)
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBankAccountTransactionRequest $request, BankAccountTransaction $bankAccountTransaction): BankAccountTransactionResource
    {
        try {
            $updatedTransaction = $this->service->update($bankAccountTransaction, $request->validated());

            return new BankAccountTransactionResource($updatedTransaction);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BankAccountTransaction $bankAccountTransaction): JsonResponse
    {
        try {
            $this->service->delete($bankAccountTransaction);

            return response()->json([
                'message' => 'Transaction deleted successfully'
            ], 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Complete a transaction
     */
    public function complete(BankAccountTransaction $bankAccountTransaction): BankAccountTransactionResource
    {
        $completedTransaction = $this->service->complete($bankAccountTransaction);

        return new BankAccountTransactionResource($completedTransaction);
    }

    /**
     * Cancel a transaction
     */
    public function cancel(BankAccountTransaction $bankAccountTransaction): BankAccountTransactionResource
    {
        $cancelledTransaction = $this->service->cancel($bankAccountTransaction);

        return new BankAccountTransactionResource($cancelledTransaction);
    }

    /**
     * Reconcile a transaction
     */
    public function reconcile(Request $request, BankAccountTransaction $bankAccountTransaction): BankAccountTransactionResource
    {
        $request->validate([
            'reconciled_by_user_id' => 'required|exists:users,id'
        ]);

        $reconciledTransaction = $this->service->reconcile(
            $bankAccountTransaction, 
            $request->reconciled_by_user_id
        );

        return new BankAccountTransactionResource($reconciledTransaction);
    }

    /**
     * Get transaction statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $bankAccountId = $request->integer('bank_account_id');
        $stats = $this->service->getTransactionStats($bankAccountId);

        return response()->json([
            'data' => $stats
        ]);
    }
}
