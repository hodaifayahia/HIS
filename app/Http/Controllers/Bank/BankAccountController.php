<?php
// app/Http/Controllers/Bank/BankAccountController.php

namespace App\Http\Controllers\Bank;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bank\StoreBankAccountRequest;
use App\Http\Requests\Bank\UpdateBankAccountRequest;
use App\Http\Resources\Bank\BankAccountCollection;
use App\Http\Resources\Bank\BankAccountResource;
use App\Models\Bank\BankAccount;
use App\Services\Bank\BankAccountService;
use App\Services\Bank\BankService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    protected BankAccountService $service;
    protected BankService $bankService;

    public function __construct(BankAccountService $service, BankService $bankService)
    {
        $this->service = $service;
        $this->bankService = $bankService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): BankAccountCollection
    {
        $perPage = $request->integer('per_page', 15);
        
        $filters = $request->only([
            'currency', 'is_active', 'bank_id', 'bank_name', 'search'
        ]);

        $bankAccounts = $this->service->getAllPaginated($filters, $perPage);

        return new BankAccountCollection($bankAccounts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBankAccountRequest $request): JsonResponse
    {
        try {
            $bankAccount = $this->service->create($request->validated());

            return (new BankAccountResource($bankAccount))
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
    public function show(BankAccount $bankAccount): BankAccountResource
    {
        return new BankAccountResource($bankAccount->load(['bank']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBankAccountRequest $request, BankAccount $bankAccount): BankAccountResource
    {
        try {
            $updatedBankAccount = $this->service->update($bankAccount, $request->validated());

            return new BankAccountResource($updatedBankAccount);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BankAccount $bankAccount): JsonResponse
    {
        $this->service->delete($bankAccount);

        return response()->json([
            'message' => 'Bank account deleted successfully'
        ], 204);
    }

    /**
     * Toggle bank account status
     */
    public function toggleStatus(BankAccount $bankAccount): BankAccountResource
    {
        $updatedBankAccount = $this->service->toggleStatus($bankAccount);

        return new BankAccountResource($updatedBankAccount);
    }

    /**
     * Update account balance
     */
    public function updateBalance(Request $request, BankAccount $bankAccount): BankAccountResource
    {
        $request->validate([
            'amount' => 'required|numeric',
            'type' => 'required|in:credit,debit',
            'description' => 'nullable|string|max:255'
        ]);

        $updatedBankAccount = $this->service->updateBalance(
            $bankAccount, 
            $request->amount, 
            $request->type
        );

        return new BankAccountResource($updatedBankAccount);
    }

    /**
     * Get active bank accounts
     */
    public function active(): JsonResponse
    {
        $accounts = $this->service->getActiveAccounts();

        return response()->json([
            'data' => BankAccountResource::collection($accounts)
        ]);
    }

    /**
     * Get accounts by currency
     */
    public function byCurrency(string $currency): JsonResponse
    {
        $accounts = $this->service->getAccountsByCurrency($currency);

        return response()->json([
            'data' => BankAccountResource::collection($accounts)
        ]);
    }

    /**
     * Get accounts by bank
     */
    public function byBank(int $bankId): JsonResponse
    {
        $accounts = $this->service->getAccountsByBank($bankId);

        return response()->json([
            'data' => BankAccountResource::collection($accounts)
        ]);
    }

    /**
     * Get bank account statistics
     */
    public function stats(): JsonResponse
    {
        $stats = $this->service->getBankAccountStats();

        return response()->json([
            'data' => $stats
        ]);
    }

    /**
     * Get available currencies
     */
    public function currencies(): JsonResponse
    {
        $currencies = $this->service->getCurrencies();

        return response()->json([
            'data' => $currencies
        ]);
    }

    /**
     * Get banks for dropdowns
     */
    public function banks(): JsonResponse
    {
        $banks = $this->bankService->getBankOptions();

        return response()->json([
            'data' => $banks
        ]);
    }

    /**
     * Sync account balances
     */
    public function syncBalances(): JsonResponse
    {
        $updated = $this->service->syncAccountBalances();

        return response()->json([
            'message' => "Synchronized {$updated} bank account balances"
        ]);
    }
}
