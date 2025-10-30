<?php
namespace App\Http\Controllers\Stock;
use App\Http\Controllers\Controller;

use App\Models\Stock\Reserve;
use App\Http\Requests\Stock\StoreReserveRequest;
use App\Http\Requests\Stock\UpdateReserveRequest;
use App\Services\Stock\ReserveService;
use App\Http\Resources\Stock\ReserveResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReserveController extends Controller
{
    public function __construct(private ReserveService $service) {}

    // GET /api/reserves
    public function index(): JsonResponse
    {
        $reserves = $this->service->paginate();
        return response()->json(ReserveResource::collection($reserves));
    }

    // POST /api/reserves
    public function store(StoreReserveRequest $request): JsonResponse
    {
        $reserve = $this->service->store($request->validated());
        return response()->json(new ReserveResource($reserve), 201);
    }

    // GET /api/reserves/{reserve}
    public function show(Reserve $reserve): JsonResponse
    {
        return response()->json(new ReserveResource($reserve));
    }

    // PUT/PATCH /api/reserves/{reserve}
    public function update(UpdateReserveRequest $request, Reserve $reserve): JsonResponse
    {
        $updated = $this->service->update($reserve, $request->validated());
        return response()->json(new ReserveResource($updated));
    }

    // DELETE /api/reserves/{reserve}
    public function destroy(Reserve $reserve): JsonResponse
    {
        $this->service->delete($reserve);
        return response()->json([], 204);
    }

    // Optional: form methods for web routes (index/create/edit) can delegate to API or return views
}
