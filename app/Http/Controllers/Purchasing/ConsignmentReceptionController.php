<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Purchasing\CreateConsignmentInvoiceRequest;
use App\Http\Requests\Purchasing\StoreConsignmentReceptionRequest;
use App\Http\Resources\Purchasing\ConsignmentReceptionDetailResource;
use App\Http\Resources\Purchasing\ConsignmentReceptionResource;
use App\Services\Purchasing\ConsignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConsignmentReceptionController extends Controller
{
    public function __construct(
        private ConsignmentService $consignmentService
    ) {}

    /**
     * Display a listing of consignment receptions
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'fournisseur_id',
            'date_from',
            'date_to',
            'consignment_code',
            'has_uninvoiced',
        ]);

        $perPage = $request->input('per_page', 15);
        $consignments = $this->consignmentService->getAllPaginated($filters, $perPage);

        return response()->json([
            'data' => ConsignmentReceptionResource::collection($consignments->items()),
            'meta' => [
                'current_page' => $consignments->currentPage(),
                'last_page' => $consignments->lastPage(),
                'per_page' => $consignments->perPage(),
                'total' => $consignments->total(),
            ],
        ]);
    }

    /**
     * Store a newly created consignment reception
     */
    public function store(StoreConsignmentReceptionRequest $request): JsonResponse
    {
        $consignment = $this->consignmentService->createReception($request->validated());

        return response()->json([
            'data' => new ConsignmentReceptionDetailResource($consignment),
            'message' => 'Consignment reception created successfully',
        ], 201);
    }

    /**
     * Display the specified consignment reception
     */
    public function show(int $id): JsonResponse
    {
        $consignment = $this->consignmentService->getById($id);

        return response()->json([
            'data' => new ConsignmentReceptionDetailResource($consignment),
        ]);
    }

    /**
     * Get uninvoiced items for a consignment
     */
    public function uninvoicedItems(int $id): JsonResponse
    {
        $items = $this->consignmentService->getUninvoicedItems($id);

        return response()->json([
            'data' => $items,
        ]);
    }

    /**
     * Create invoice from consignment consumption
     */
    public function createInvoice(int $id, CreateConsignmentInvoiceRequest $request): JsonResponse
    {
        $invoice = $this->consignmentService->createInvoiceFromConsumption(
            $id,
            $request->validated()
        );

        return response()->json([
            'data' => $invoice,
            'message' => 'Invoice created successfully from consignment consumption',
        ], 201);
    }

    /**
     * Get consumption history for a consignment
     */
    public function consumptionHistory(int $id): JsonResponse
    {
        $history = $this->consignmentService->getConsumptionHistory($id);

        return response()->json([
            'data' => $history,
        ]);
    }

    /**
     * Confirm consignment reception
     */
    public function confirm(int $id): JsonResponse
    {
        $consignment = $this->consignmentService->confirmReception($id);

        return response()->json([
            'data' => new ConsignmentReceptionDetailResource($consignment),
            'message' => 'Consignment reception confirmed',
        ]);
    }

    /**
     * Get supplier statistics
     */
    public function supplierStats(int $supplierId): JsonResponse
    {
        $stats = $this->consignmentService->getSupplierStats($supplierId);

        return response()->json([
            'data' => $stats,
        ]);
    }
}
