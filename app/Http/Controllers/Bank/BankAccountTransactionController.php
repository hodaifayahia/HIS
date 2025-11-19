<?php
// app/Http/Controllers/BankAccountTransactionController.php

namespace App\Http\Controllers\Bank;



use App\Http\Controllers\Controller;
use App\Http\Requests\Bank\StoreBankAccountTransactionRequest;
use App\Http\Requests\Bank\UpdateBankAccountTransactionRequest;
use App\Http\Requests\Bank\BulkUploadTransactionRequest;
use App\Http\Resources\Bank\BankAccountTransactionCollection;
use App\Http\Resources\Bank\BankAccountTransactionResource;
use App\Models\Bank\BankAccountTransaction;
use App\Services\Bank\BankAccountTransactionService;
use App\Services\Bank\BulkTransactionUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankAccountTransactionController extends Controller
{
    protected BankAccountTransactionService $service;
    protected BulkTransactionUploadService $bulkUploadService;

    public function __construct(
        BankAccountTransactionService $service,
        BulkTransactionUploadService $bulkUploadService
    ) {
        $this->service = $service;
        $this->bulkUploadService = $bulkUploadService;
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
            $data = $request->validated();
            
            // Handle file upload for Attachment if present
            if ($request->hasFile('Attachment')) {
                $data['Attachment'] = $request->file('Attachment');
            }
            
            $transaction = $this->service->create($data);

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
            throw new \Exception($e->getMessage());
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
     * Validate a transaction (change from pending to confirmed with validation data)
     */
    public function validate(Request $request, BankAccountTransaction $bankAccountTransaction): JsonResponse
    {
        try {
            $validationData = $request->validate([
                'Payment_date' => 'nullable|date',
                'reference_validation' => 'nullable|string|max:255',
                'Attachment_validation' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,gif,bmp,tiff,webp,svg|max:10240', // 10MB max
                'Reason_validation' => 'nullable|string|max:255',
            ]);

            // Handle file upload for Attachment_validation
            if ($request->hasFile('Attachment_validation')) {
                $file = $request->file('Attachment_validation');
                $fileName = time() . '_validation_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('validation_attachments', $fileName, 'public');
                $validationData['Attachment_validation'] = $filePath;
            }

            $validatedTransaction = $this->service->validate(
                $bankAccountTransaction, 
                $validationData,
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'data' => new BankAccountTransactionResource($validatedTransaction),
                'message' => 'Transaction validated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
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

    /**
     * Bulk upload transactions from file
     */
    public function bulkUpload(BulkUploadTransactionRequest $request): JsonResponse
    {
        try {
            $result = $this->bulkUploadService->processUpload(
                $request->file('file'),
                $request->integer('bank_account_id'),
                $request->input('description'),
                auth()->id()
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => $result['data']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 422);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download bulk upload template
     */
    public function downloadTemplate(): JsonResponse
    {
        try {
            $template = $this->bulkUploadService->generateTemplate();
            
            return response()->json([
                'success' => true,
                'data' => $template
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate template: ' . $e->getMessage()
            ], 500);
        }
    }
}
