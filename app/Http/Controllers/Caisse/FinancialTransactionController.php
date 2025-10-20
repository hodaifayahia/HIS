<?php

// app/Http/Controllers/Caisse/FinancialTransactionController.php

namespace App\Http\Controllers\Caisse;

use App\Http\Controllers\Controller;
use App\Http\Requests\Caisse\BulkPaymentRequest;
use App\Http\Requests\Caisse\OverpaymentHandlingRequest;
use App\Http\Requests\Caisse\RefundTransactionRequest;
use App\Http\Requests\Caisse\StoreFinancialTransactionRequest;
use App\Http\Requests\Caisse\UpdatePrestationPriceRequest;
use App\Http\Resources\Caisse\FinancialTransactionResource;
use App\Models\Caisse\FinancialTransaction;
use App\Models\manager\RefundAuthorization;
use App\Services\Caisse\FinancialTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FinancialTransactionController extends Controller
{
    protected FinancialTransactionService $service;

    public function __construct(FinancialTransactionService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of financial transactions
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page', 15);

        $filters = $request->only([
            'fiche_navette_item_id',
            'prestation_id',
            'transaction_type',
            'payment_method',
            'cashier_id',
            'date_from',
            'date_to',
            'fiche_navette_id',
        ]);

        try {
            $transactions = $this->service->getAllPaginated($filters, $perPage);

            // Relationships should already be loaded by the service
            // Just ensure they're accessible if needed
            $items = $transactions->items();
            $itemIds = collect($items)->pluck('fiche_navette_item_id')->filter()->unique()->values()->toArray();

            // load refund authorizations for those fiche_navette_item_ids
            $refundAuths = [];
            if (! empty($itemIds)) {
                $auths = RefundAuthorization::whereIn('fiche_navette_item_id', $itemIds)
                    ->orderBy('created_at', 'desc')
                    ->get();
                // group by fiche_navette_item_id
                $refundAuths = $auths->groupBy('fiche_navette_item_id')->map(function ($group) {
                    return $group->values();
                })->toArray();
            }

            // Set refund authorizations on the resource class so each transaction can access them
            FinancialTransactionResource::$refundAuthorizations = $refundAuths;

            // Create resource collection
            $resourceCollection = FinancialTransactionResource::collection($transactions->items());

            // Prepare additional meta
            $additional = [
                'success' => true,
                'meta' => [
                    'total' => $transactions->total(),
                    'count' => count($transactions->items()),
                    'per_page' => $transactions->perPage(),
                    'current_page' => $transactions->currentPage(),
                    'total_pages' => $transactions->lastPage(),
                    'has_more_pages' => $transactions->hasMorePages(),
                ],
                'summary' => $this->calculateSummary($transactions->items()),
            ];

            // Get the resource collection as array and return as JsonResponse
            $responseData = $resourceCollection->additional($additional)->response()->getData(true);

            return response()->json($responseData);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load transactions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created financial transaction
     */
    public function store(StoreFinancialTransactionRequest $request): JsonResponse
    {
        try {   // Ensure cashier_id defaults to the authenticated user when not provided by client
            $data = $request->validated();
            if (empty($data['cashier_id'])) {
                $data['cashier_id'] = Auth::id();
            }

            $result = $this->service->processPaymentTransaction($data);

            return response()->json([
                'success' => true,
                'data' => new FinancialTransactionResource($result['transaction']),
                'updated_items' => $result['updated_items'],
                'message' => 'Transaction created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function processRefund(RefundTransactionRequest $request): JsonResponse
    {
        try {
            // Support two refund flows:
            // 1) original_transaction_id provided -> refund that transaction
            // 2) refund_authorization_id provided -> refund using authorization (fixed amount)

            $refundData = [];

            if ($request->filled('original_transaction_id')) {
                $originalTransaction = FinancialTransaction::findOrFail($request->original_transaction_id);

                // Get fiche navette item status to determine if we should bypass restrictions
                $ficheItemStatus = null;
                if ($originalTransaction->ficheNavetteItem) {
                    $ficheItemStatus = strtolower($originalTransaction->ficheNavetteItem->status ?? '');
                }

                $isPriorityStatus = in_array($ficheItemStatus, ['pending', 'confirmed']);

                if (! $isPriorityStatus && ! $originalTransaction->canBeRefunded()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This transaction cannot be refunded',
                    ], 422);
                }

                // Prevent duplicate refunds: check if a refund already exists linked to this transaction
                // Only check for non-priority status items
                if (! $isPriorityStatus) {
                    $existingRefund = FinancialTransaction::where('transaction_type', 'refund')
                        ->where(function ($q) use ($originalTransaction) {
                            $q->where('original_transaction_id', $originalTransaction->id)
                                ->orWhere('fiche_navette_item_id', $originalTransaction->fiche_navette_item_id);
                        })->first();

                    if ($existingRefund) {
                        return response()->json([
                            'success' => false,
                            'message' => 'A refund already exists for this payment or item.',
                        ], 422);
                    }
                }

                // Prefer the patient attached to the original transaction; if missing, try the fiche navette's patient
                // Only validate patient for non-priority status items
                $patientId = $originalTransaction->patient_id;
                if (empty($patientId) && $originalTransaction->ficheNavetteItem) {
                    $patientId = $originalTransaction->ficheNavetteItem->ficheNavette->patient_id ?? null;
                }

                if (! $isPriorityStatus && empty($patientId)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot determine patient for refund. Original transaction lacks associated patient.',
                    ], 422);
                }

                // For priority status items, use a default patient ID if not found
                if ($isPriorityStatus && empty($patientId)) {
                    $patientId = 1; // Default patient ID for priority status refunds
                }

                $refundData = [
                    'fiche_navette_item_id' => $originalTransaction->fiche_navette_item_id,
                    'patient_id' => $patientId,
                    'cashier_id' => $request->cashier_id ?? Auth::id(),
                    'amount' => (float) $request->refund_amount,
                    'transaction_type' => 'refund',
                    'payment_method' => $originalTransaction->payment_method,
                    'notes' => $request->notes ?? "Refund for transaction #{$originalTransaction->reference}",
                    'original_transaction_id' => $originalTransaction->id,
                    // Add item_dependency_id if the original transaction was for a dependency
                    'item_dependency_id' => $originalTransaction->item_dependency_id ?? null,
                ];
            } elseif ($request->filled('refund_authorization_id')) {
                $auth = RefundAuthorization::findOrFail($request->refund_authorization_id);

                // Get fiche navette item status to determine if we should bypass restrictions
                $ficheItemStatus = null;
                $ficheItem = \App\Models\Reception\ficheNavetteItem::find($auth->fiche_navette_item_id);
                if ($ficheItem) {
                    $ficheItemStatus = strtolower($ficheItem->status ?? '');
                }

                $isPriorityStatus = in_array($ficheItemStatus, ['pending', 'confirmed']);

                // Authorization must be approved (frontend may call approve endpoint first)
                // Only check for non-priority status items
                if (! $isPriorityStatus && strtolower($auth->status) !== 'approved') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Refund authorization must be approved before processing a refund.',
                    ], 422);
                }

                // Also disallow if authorization is already used/approved (defensive)
                // Only check for non-priority status items
                if (! $isPriorityStatus) {
                    $st = strtolower($auth->status);
                    if (in_array($st, ['used'])) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Refund authorization has already been used.',
                        ], 422);
                    }
                }

                // Determine amount: prefer authorized_amount, fallback to requested_amount
                $amount = (float) ($auth->authorized_amount ?? $auth->requested_amount ?? 0);
                if (! $isPriorityStatus && $amount <= 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Refund authorization does not contain a valid amount.',
                    ], 422);
                }

                // For priority status items, use the requested amount if authorized amount is not set
                if ($isPriorityStatus && $amount <= 0) {
                    $amount = (float) ($auth->requested_amount ?? $request->refund_amount ?? 0);
                }

                // Resolve fiche item and patient id from the fiche navette (do NOT use prestation_id)
                $ficheItemId = $auth->fiche_navette_item_id;
                $ficheItem = \App\Models\Reception\ficheNavetteItem::with('ficheNavette')->find($ficheItemId);
                $patientId = $ficheItem?->ficheNavette?->patient_id ?? null;

                // Only validate patient for non-priority status items
                if (! $isPriorityStatus && empty($patientId)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot determine patient for refund. Fiche navette or patient missing for the authorized item.',
                    ], 422);
                }

                // For priority status items, use a default patient ID if not found
                if ($isPriorityStatus && empty($patientId)) {
                    $patientId = 1; // Default patient ID for priority status refunds
                }

                $refundData = [
                    'fiche_navette_item_id' => $ficheItemId,
                    'patient_id' => $patientId,
                    'cashier_id' => $request->cashier_id ?? Auth::id(),
                    'amount' => $amount,
                    'transaction_type' => 'refund',
                    'payment_method' => 'refund',
                    'notes' => $request->notes ?? "Authorized refund (auth #{$auth->id})",
                    'refund_authorization_id' => $auth->id,
                ];
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Either original_transaction_id or refund_authorization_id is required.',
                ], 422);
            }

            $result = $this->service->processPaymentTransaction($refundData);

            // Explicitly update remaining and paid amounts for fiche navette item and dependencies
            $this->updateFicheNavetteItemAmounts($refundData['fiche_navette_item_id'], $refundData['item_dependency_id'] ?? null);

            // If we used an authorization, mark it as used
            if (! empty($refundData['refund_authorization_id'])) {
                $auth = RefundAuthorization::find($refundData['refund_authorization_id']);
                if ($auth) {
                    $auth->update(['status' => 'used']);
                }
            }

            return response()->json([
                'success' => true,
                'data' => new FinancialTransactionResource($result['transaction']),
                'updated_items' => $result['updated_items'],
                'message' => 'Refund processed successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Handle overpayment (donate or add to balance)
     */
    public function handleOverpayment(OverpaymentHandlingRequest $request): JsonResponse
    {
        try {
            $cashierId = $request->cashier_id ?? Auth::id();
            $result = $this->service->processOverpayment(
                $request->fiche_navette_item_id,
                $request->patient_id,
                $cashierId,
                $request->required_amount,
                $request->paid_amount,
                $request->payment_method,
                $request->overpayment_action, // 'donate' or 'balance'
                $request->notes,
                $request->item_dependency_id,
                $request->dependent_prestation_id
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => $request->overpayment_action === 'donate'
                    ? 'Payment processed and extra amount donated successfully'
                    : 'Payment processed and extra amount added to patient balance',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get refundable transactions for a fiche navette
     */
    public function getRefundableTransactions(Request $request): JsonResponse
    {
        $ficheNavetteId = $request->integer('fiche_navette_id');

        if (! $ficheNavetteId) {
            return response()->json([
                'success' => false,
                'message' => 'fiche_navette_id is required',
            ], 400);
        }

        try {
            $transactions = $this->service->getRefundableTransactions($ficheNavetteId);

            return response()->json([
                'success' => true,
                'data' => FinancialTransactionResource::collection($transactions),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load refundable transactions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified financial transaction
     */
    public function show(FinancialTransaction $financialTransaction): JsonResponse
    {
        try {
            $transaction = $this->service->findById($financialTransaction->id);

            return response()->json([
                'success' => true,
                'data' => new FinancialTransactionResource($transaction),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Update the specified financial transaction
     */
    public function update(Request $request, FinancialTransaction $financialTransaction): JsonResponse
    {
        $validatedData = $request->validate([
            'amount' => 'sometimes|numeric|min:0.01',
            'transaction_type' => 'sometimes|in:payment,refund,adjustment',
            'payment_method' => 'sometimes|in:cash,card,check,transfer,insurance',
            'notes' => 'sometimes|nullable|string|max:1000',
        ]);

        try {
            $updatedTransaction = $this->service->update($financialTransaction, $validatedData);

            return response()->json([
                'success' => true,
                'data' => new FinancialTransactionResource($updatedTransaction),
                'message' => 'Transaction updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Remove the specified financial transaction
     */
    public function destroy(FinancialTransaction $financialTransaction): JsonResponse
    {
        try {
            $this->service->delete($financialTransaction);

            return response()->json([
                'success' => true,
                'message' => 'Transaction deleted successfully',
            ], 204);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update prestation price and amounts
     */
    public function updatePrestationPrice(UpdatePrestationPriceRequest $request): JsonResponse
    {
        try {
            $updatedItems = $this->service->updatePrestationPrice(
                $request->prestation_id,
                $request->fiche_navette_item_id,
                $request->new_final_price,
                $request->paid_amount
            );

            return response()->json([
                'success' => true,
                'data' => $updatedItems,
                'message' => 'Prestation price updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Process bulk payments
     */
    public function bulkPayment(BulkPaymentRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            // Ensure cashier_id present
            if (empty($data['cashier_id'])) {
                $data['cashier_id'] = Auth::id();
            }
            $result = $this->service->createBulkPayments($data);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => $result['message'] ?? 'Bulk payment processed successfully',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get transaction statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $ficheNavetteItemId = $request->integer('fiche_navette_item_id');

        try {
            $stats = $this->service->getTransactionStats($ficheNavetteItemId);

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get prestations with dependencies for a fiche navette
     */
    public function getPrestationsWithDependencies(Request $request): JsonResponse
    {
        $ficheNavetteId = $request->integer('fiche_navette_id');

        if (! $ficheNavetteId) {
            return response()->json([
                'success' => false,
                'message' => 'fiche_navette_id is required',
            ], 400);
        }

        try {
            $prestations = $this->service->getAllPrestationsWithDependencies($ficheNavetteId);

            return response()->json([
                'success' => true,
                'data' => $prestations,
                'summary' => $this->calculatePrestationsSummary($prestations),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load prestations',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get today's or unpaid prestations for a patient
     */
    public function getPatientPrestations(Request $request): JsonResponse
    {
        $patientId = $request->integer('patient_id');

        if (! $patientId) {
            return response()->json([
                'success' => false,
                'message' => 'patient_id is required',
            ], 400);
        }

        try {
            $prestations = $this->service->getTodaysOrUnpaidPrestationsForPatient($patientId);

            return response()->json([
                'success' => true,
                'data' => $prestations,
                'summary' => $this->calculatePrestationsSummary($prestations),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load patient prestations',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get transactions by fiche navette
     */
    public function getByFicheNavette(Request $request): JsonResponse
    {
        $ficheNavetteId = $request->integer('fiche_navette_id');
        $perPage = $request->integer('per_page', 15);

        if (! $ficheNavetteId) {
            return response()->json([
                'success' => false,
                'message' => 'fiche_navette_id is required',
            ], 400);
        }

        try {
            // collect allowed filters from request
            $filters = $request->only([
                'fiche_navette_id',
                'fiche_navette_item_id',
                'prestation_id',
                'transaction_type',
                'payment_method',
                'cashier_id',
                'date_from',
                'date_to',
            ]);

            // ensure fiche_navette_id is present in filters
            $filters['fiche_navette_id'] = $ficheNavetteId;

            $transactions = $this->service->getAllPaginated($filters, $perPage);

            // Load relationships for the transactions
            try {
                collect($transactions->items())->load([
                    'ficheNavetteItem.prestation.specialization',
                    'ficheNavetteItem.fiche_navette.patient',
                    'ficheNavetteItem',
                    'ficheNavetteItem.dependencies.dependencyPrestation.specialization',
                    'originalTransaction',
                ]);
            } catch (\Throwable $e) {
                Log::warning('Failed to load transaction relationships in getByFicheNavette: '.$e->getMessage());
            }

            // collect fiche_navette_item_ids from returned transactions
            $items = $transactions->items();
            $itemIds = collect($items)->pluck('fiche_navette_item_id')->filter()->unique()->values()->toArray();

            // load refund authorizations for those fiche_navette_item_ids
            $refundAuths = [];
            if (! empty($itemIds)) {
                $auths = RefundAuthorization::whereIn('fiche_navette_item_id', $itemIds)
                    ->orderBy('created_at', 'desc')
                    ->get();
                // group by fiche_navette_item_id
                $refundAuths = $auths->groupBy('fiche_navette_item_id')->map(function ($group) {
                    return $group->values();
                })->toArray();
            }

            return response()->json([
                'success' => true,
                'data' => FinancialTransactionResource::collection($transactions->items()),
                'meta' => [
                    'total' => $transactions->total(),
                    'count' => count($transactions->items()),
                    'per_page' => $transactions->perPage(),
                    'current_page' => $transactions->currentPage(),
                    'total_pages' => $transactions->lastPage(),
                ],
                'summary' => $this->calculateSummary($transactions->items()),
                // add refund authorizations grouped by fiche_navette_item_id
                'refund_authorizations' => $refundAuths,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load transactions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get transactions by caisse session
     */
    public function getBySession(Request $request): JsonResponse
    {
        $sessionId = $request->integer('session_id');

        if (! $sessionId) {
            return response()->json([
                'success' => false,
                'message' => 'session_id is required',
            ], 400);
        }

        try {
            $session = \App\Models\Coffre\CaisseSession::findOrFail($sessionId);

            // Get all payment transactions for this session's cashier within the session date range
            $totalAmount = FinancialTransaction::where('caisse_session_id', $sessionId)
                ->where('transaction_type', 'payment')
                ->sum('amount');

            return response()->json([
                'success' => true,
                'data' => [
                    'total_amount' => $totalAmount,
                    'session_id' => $sessionId,
                    'cashier_id' => $session->user_id,
                    'date_from' => $session->ouverture_at->toDateString(),
                    'date_to' => ($session->cloture_at ?? now())->toDateString(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load session transactions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create refund transaction
     */
    public function refund(Request $request, FinancialTransaction $originalTransaction): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:'.$originalTransaction->amount,
            'notes' => 'nullable|string|max:1000',
        ]);

        if (! $originalTransaction->canBeRefunded()) {
            return response()->json([
                'success' => false,
                'message' => 'This transaction cannot be refunded',
            ], 422);
        }

        try {
            $refundData = [
                'fiche_navette_item_id' => $originalTransaction->fiche_navette_item_id,
                'patient_id' => $originalTransaction->patient_id,
                'cashier_id' => Auth::id(),
                'amount' => $request->amount,
                'transaction_type' => 'refund',
                'payment_method' => $originalTransaction->payment_method,
                'notes' => $request->notes ?? "Refund for transaction #{$originalTransaction->reference}",
            ];

            $result = $this->service->processPaymentTransaction($refundData);

            return response()->json([
                'success' => true,
                'data' => new FinancialTransactionResource($result['transaction']),
                'updated_items' => $result['updated_items'],
                'message' => 'Refund processed successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get daily summary
     */
    public function dailySummary(Request $request): JsonResponse
    {
        $date = $request->get('date', now()->toDateString());

        try {
            $transactions = FinancialTransaction::whereDate('created_at', $date)->get();

            $summary = [
                'date' => $date,
                'total_transactions' => $transactions->count(),
                'total_payments' => $transactions->where('transaction_type', 'payment')->sum('amount'),
                'total_refunds' => $transactions->where('transaction_type', 'refund')->sum('amount'),
                'net_amount' => $transactions->where('transaction_type', 'payment')->sum('amount') -
                               $transactions->where('transaction_type', 'refund')->sum('amount'),
                'by_payment_method' => $transactions->groupBy('payment_method')->map(function ($group) {
                    return [
                        'count' => $group->count(),
                        'total' => $group->sum('amount'),
                    ];
                }),
                'by_cashier' => $transactions->groupBy('cashier_id')->map(function ($group) {
                    return [
                        'count' => $group->count(),
                        'total' => $group->sum('amount'),
                        'cashier_name' => $group->first()->cashier->name ?? 'Unknown',
                    ];
                }),
            ];

            return response()->json([
                'success' => true,
                'data' => $summary,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load daily summary',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate summary for transactions
     */
    private function calculateSummary($transactions): array
    {
        $collection = collect($transactions);

        return [
            'total_transactions' => $collection->count(),
            'total_amount' => $collection->sum('amount'),
            'payment_count' => $collection->where('transaction_type', 'payment')->count(),
            'refund_count' => $collection->where('transaction_type', 'refund')->count(),
            'adjustment_count' => $collection->where('transaction_type', 'adjustment')->count(),
            'total_payments' => $collection->where('transaction_type', 'payment')->sum('amount'),
            'total_refunds' => $collection->where('transaction_type', 'refund')->sum('amount'),
            'net_amount' => $collection->where('transaction_type', 'payment')->sum('amount') -
                           $collection->where('transaction_type', 'refund')->sum('amount'),
        ];
    }

    /**
     * Calculate summary for prestations
     */
    private function calculatePrestationsSummary($prestations): array
    {
        $collection = collect($prestations);

        return [
            'total_prestations' => $collection->count(),
            'total_final_price' => $collection->sum('final_price'),
            'total_paid_amount' => $collection->sum('paid_amount'),
            'total_remaining_amount' => $collection->sum('remaining_amount'),
            'fully_paid' => $collection->where('remaining_amount', '<=', 0)->count(),
            'partially_paid' => $collection->where('paid_amount', '>', 0)->where('remaining_amount', '>', 0)->count(),
            'unpaid' => $collection->where('paid_amount', '<=', 0)->count(),
            'by_type' => $collection->groupBy('type')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total_amount' => $group->sum('final_price'),
                    'total_remaining' => $group->sum('remaining_amount'),
                ];
            }),
        ];
    }

    private function updateFicheNavetteItemAmounts($ficheNavetteItemId, $itemDependencyId = null)
    {
        try {
            // Load the fiche navette item with its dependencies and transactions
            $ficheItem = \App\Models\Reception\ficheNavetteItem::with([
                'dependencies',
                'transactions' => function ($query) {
                    $query->whereIn('transaction_type', ['payment', 'refund']);
                },
                'dependencies.transactions' => function ($query) {
                    $query->whereIn('transaction_type', ['payment', 'refund']);
                },
            ])->find($ficheNavetteItemId);

            if (! $ficheItem) {
                return;
            }

            // If this is a dependency refund, update only the dependency
            if ($itemDependencyId) {
                $dependency = $ficheItem->dependencies->find($itemDependencyId);
                if ($dependency) {
                    $this->updateItemAmounts($dependency);
                }
            } else {
                // Update the main fiche navette item
                $this->updateItemAmounts($ficheItem);

                // Update all dependencies of this item
                foreach ($ficheItem->dependencies as $dependency) {
                    $this->updateItemAmounts($dependency);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to update fiche navette item amounts after refund: '.$e->getMessage());
        }
    }

    /**
     * Update remaining and paid amounts for a single item (main item or dependency)
     */
    private function updateItemAmounts($item)
    {
        try {
            $transactions = $item->transactions ?? collect();

            // Calculate total paid amount (payments - refunds)
            $totalPaid = $transactions->reduce(function ($carry, $transaction) {
                if ($transaction->transaction_type === 'payment') {
                    return $carry + (float) $transaction->amount;
                } elseif ($transaction->transaction_type === 'refund') {
                    // Refund transactions may be stored as negative values in the DB.
                    // Use absolute value to ensure we subtract the refunded amount.
                    return $carry - abs((float) $transaction->amount);
                }

                return $carry;
            }, 0.0);

            // Get the final price (for main items) or amount (for dependencies)
            $finalPrice = $item->final_price ?? $item->amount ?? 0;

            // Calculate remaining amount
            $remainingAmount = max(0, $finalPrice - $totalPaid);

            // Update the item with new amounts
            $item->update([
                'paid_amount' => $totalPaid,
                'remaining_amount' => $remainingAmount,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update item amounts: '.$e->getMessage());
        }
    }
}
