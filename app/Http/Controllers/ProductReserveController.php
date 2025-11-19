<?php

namespace App\Http\Controllers;
use App\Http\Requests\Stock\StoreProductReserveRequest;
use App\Http\Requests\Stock\UpdateProductReserveRequest;
use App\Http\Resources\Stock\ProductReserveResource;
use App\Models\ProductReserve;
use App\Services\Stock\ProductReserveService;
use Illuminate\Http\JsonResponse;

class ProductReserveController extends Controller
{
    public function __construct(private ProductReserveService $service) {}

    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $reserveId = $request->query('reserve_id');
        $reserves = $this->service->paginate($reserveId);
        
        return response()->json([
            'data' => ProductReserveResource::collection($reserves),
            'meta' => [
                'current_page' => $reserves->currentPage(),
                'from' => $reserves->firstItem(),
                'last_page' => $reserves->lastPage(),
                'per_page' => $reserves->perPage(),
                'to' => $reserves->lastItem(),
                'total' => $reserves->total(),
            ],
            'links' => [
                'first' => $reserves->url(1),
                'last' => $reserves->url($reserves->lastPage()),
                'prev' => $reserves->previousPageUrl(),
                'next' => $reserves->nextPageUrl(),
            ]
        ]);
    }

    public function store(StoreProductReserveRequest $request): JsonResponse
    {
        $reserve = $this->service->store(
            $request->validated() + ['reserved_by' => $request->user()->id]
        );

        $reserve->load(['product', 'pharmacyProduct', 'reserver', 'stockage', 'pharmacyStockage', 'reserve']);
        return response()->json(new ProductReserveResource($reserve), 201);
    }

    public function show(ProductReserve $productReserve): JsonResponse
    {
        $productReserve->load(['product','pharmacyProduct','reserver', 'stockage', 'pharmacyStockage', 'reserve']);
        return response()->json(new ProductReserveResource($productReserve));
    }

    public function update(UpdateProductReserveRequest $request, ProductReserve $productReserve): JsonResponse
    {
        $reserve = $this->service->update($productReserve, $request->validated());
        $reserve->load(['product', 'pharmacyProduct', 'reserver', 'stockage', 'pharmacyStockage', 'reserve']);
        return response()->json(new ProductReserveResource($reserve));
    }

    public function destroy(ProductReserve $productReserve): JsonResponse
    {
        $this->service->delete($productReserve);
        return response()->json([], 204);
    }

    public function cancel(\Illuminate\Http\Request $request, ProductReserve $productReserve): JsonResponse
    {
        $reserve = $this->service->cancel(
            $productReserve,
            $request->input('cancel_reason', 'No reason provided')
        );
        $reserve->load(['product', 'pharmacyProduct', 'reserver', 'stockage', 'pharmacyStockage', 'reserve']);
        return response()->json(new ProductReserveResource($reserve));
    }

    public function fulfill(\Illuminate\Http\Request $request, ProductReserve $productReserve): JsonResponse
    {
        $destinationServiceId = $request->input('destination_service_id');
        
        // Update destination_service_id if provided
        if ($destinationServiceId) {
            $productReserve->update(['destination_service_id' => $destinationServiceId]);
        }
        
        $reserve = $this->service->fulfill($productReserve);
        $reserve->load(['product', 'pharmacyProduct', 'reserver', 'stockage', 'pharmacyStockage', 'reserve', 'destinationService']);
        return response()->json(new ProductReserveResource($reserve));
    }

    public function bulkFulfill(\Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:product_reserves,id'],
            'destination_service_id' => ['nullable', 'integer', 'exists:services,id']
        ]);

        $result = $this->service->bulkFulfill($request->input('ids'), $request->input('destination_service_id'));
        return response()->json($result);
    }

    public function bulkCancel(\Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate([
            'reserve_ids' => ['required', 'array', 'min:1'],
            'reserve_ids.*' => ['required', 'integer', 'exists:product_reserves,id'],
            'cancel_reason' => ['required', 'string', 'max:255']
        ]);

        $results = $this->service->bulkCancel(
            $request->input('reserve_ids'),
            $request->input('cancel_reason')
        );
        
        return response()->json([
            'message' => count($results['success']) . ' reservations cancelled successfully',
            'results' => $results
        ]);
    }
}