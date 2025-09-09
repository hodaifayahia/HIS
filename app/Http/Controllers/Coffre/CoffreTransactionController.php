<?php

namespace App\Http\Controllers\Coffre;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coffre\StoreCoffreTransactionRequest;
use App\Http\Requests\Coffre\UpdateCoffreTransactionRequest;
use App\Http\Resources\Coffre\CoffreTransactionCollection;
use App\Http\Resources\Coffre\CoffreTransactionResource;
use App\Models\Coffre\CoffreTransaction;
use App\Services\Coffre\CoffreTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CoffreTransactionController extends Controller
{
    protected CoffreTransactionService $service;
    public $coffreTransactionService; // keep for backward compatibility
    
    public function __construct(CoffreTransactionService $service)
    {
        $this->service = $service;
        $this->coffreTransactionService = $service;
    }
    

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $coffreId = $request->get('coffre_id'); // Get coffre_id filter
            
            // Pass coffre_id filter to service
            $result = $this->service->getAllPaginated($perPage, $coffreId);
            
            return response()->json([
                'success' => true,
                'data' => CoffreTransactionResource::collection($result->items()),
                'meta' => [
                    'current_page' => $result->currentPage(),
                    'last_page' => $result->lastPage(),
                    'per_page' => $result->perPage(),
                    'total' => $result->total(),
                ],
                'links' => [
                    'first' => $result->url(1),
                    'last' => $result->url($result->lastPage()),
                    'prev' => $result->previousPageUrl(),
                    'next' => $result->nextPageUrl(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch transactions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCoffreTransactionRequest $request): JsonResponse
    {

        try {
            $data = $request->validated();
            // Handle attachment upload
            if ($request->hasFile('attachment')) {
                $path = $request->file('attachment')->store('coffre_attachments', 'public');
                $data['Attachment'] = $path;
            }

            // Set user_id to authenticated user automatically
            $data['user_id'] = Auth::id();
            
            $result = $this->service->create($data);
            
            return response()->json([
                'success' => true,
                'data' => new CoffreTransactionResource($result),
                'message' => 'Transaction created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create transaction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(CoffreTransaction $coffreTransaction): CoffreTransactionResource
    {
        return new CoffreTransactionResource($coffreTransaction->load([
            'coffre', 'user', 'destinationCoffre', 'sourceCaisseSession', 'destinationBanque'
        ]));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCoffreTransactionRequest $request, CoffreTransaction $coffreTransaction): JsonResponse
    {
        try {
            $data = $request->validated();
            // Handle attachment update: remove old file then store new
            if ($request->hasFile('attachment')) {
                if (!empty($coffreTransaction->Attachment)) {
                    Storage::disk('public')->delete($coffreTransaction->Attachment);
                }

                $path = $request->file('attachment')->store('coffre_attachments', 'public');
                $data['Attachment'] = $path;
            }

            // Keep original user or set to current auth user if not provided
            if (!isset($data['user_id'])) {
                $data['user_id'] = $coffreTransaction->user_id ?? Auth::id();
            }
            
            $result = $this->service->update($coffreTransaction, $data);
            
            return response()->json([
                'success' => true,
                'data' => new CoffreTransactionResource($result),
                'message' => 'Transaction updated successfully'
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getStatusCode());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update transaction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CoffreTransaction $coffreTransaction): JsonResponse
    {
        $this->service->delete($coffreTransaction);

        return response()->json([
            'message' => 'Transaction deleted successfully'
        ], 204);
    }

    /**
     * Get transaction types for form dropdown
     */
    public function transactionTypes(): JsonResponse
    {
        $types = $this->service->getTransactionTypes();

        return response()->json([
            'data' => collect($types)->map(fn($label, $value) => [
                'value' => $value,
                'label' => $label
            ])->values()
        ]);
    }

    /**
     * Get coffres for form dropdown
     */
    public function coffres(): JsonResponse
    {
        $coffres = $this->service->getCoffresForSelect();

        return response()->json([
            'data' => $coffres
        ]);
    }

    /**
     * Get users for form dropdown
     */
    public function users(): JsonResponse
    {
        $users = $this->service->getUsersForSelect();

        return response()->json([
            'data' => $users
        ]);
    }
}