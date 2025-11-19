<?php
// app/Http/Controllers/Bank/BankController.php

namespace App\Http\Controllers\Bank;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bank\StoreBankRequest;
use App\Http\Requests\Bank\UpdateBankRequest;
use App\Http\Resources\Bank\BankCollection;
use App\Http\Resources\Bank\BankResource;
use App\Models\Bank\Bank;
use App\Services\Bank\BankService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankController extends Controller
{
    protected BankService $service;

    public function __construct(BankService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): BankCollection
    {
        $perPage = $request->integer('per_page', 15);
        
        $filters = $request->only([
            'is_active', 'currency', 'search'
        ]);

        $banks = $this->service->getAllPaginated($filters, $perPage);

        return new BankCollection($banks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBankRequest $request): JsonResponse
    {
        $bank = $this->service->create($request->validated());

        return (new BankResource($bank))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Bank $bank): BankResource
    {
        return new BankResource($bank);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBankRequest $request, Bank $bank): BankResource
    {
        $updatedBank = $this->service->update($bank, $request->validated());

        return new BankResource($updatedBank);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Bank $bank): JsonResponse
    {
        try {
            $this->service->delete($bank);
            
            return response()->json([
                'message' => 'Bank deleted successfully'
            ], 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Toggle bank status
     */
    public function toggleStatus(Bank $bank): BankResource
    {
        $updatedBank = $this->service->toggleStatus($bank);

        return new BankResource($updatedBank);
    }

    /**
     * Get active banks
     */
    public function active(): JsonResponse
    {
        $banks = $this->service->getActiveBanks();

        return response()->json([
            'data' => BankResource::collection($banks)
        ]);
    }

    /**
     * Get banks by currency
     */
    public function byCurrency(string $currency): JsonResponse
    {
        $banks = $this->service->getBanksByCurrency($currency);

        return response()->json([
            'data' => BankResource::collection($banks)
        ]);
    }

    /**
     * Get bank options for dropdowns
     */
    public function options(): JsonResponse
    {
        $options = $this->service->getBankOptions();

        return response()->json([
            'data' => $options
        ]);
    }

    /**
     * Get bank statistics
     */
    public function stats(): JsonResponse
    {
        $stats = $this->service->getBankStats();

        return response()->json([
            'data' => $stats
        ]);
    }

    /**
     * Reorder banks
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'bank_ids' => 'required|array|min:1',
            'bank_ids.*' => 'exists:banks,id'
        ]);

        $this->service->reorderBanks($request->bank_ids);

        return response()->json([
            'message' => 'Banks reordered successfully'
        ]);
    }

    /**
     * Seed default banks
     */
    public function seed(): JsonResponse
    {
        $this->service->seedDefaultBanks();

        return response()->json([
            'message' => 'Default banks seeded successfully'
        ]);
    }
}
