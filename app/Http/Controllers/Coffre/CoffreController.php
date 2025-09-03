<?php

namespace App\Http\Controllers\Coffre;



use App\Http\Controllers\Controller;
use App\Http\Requests\Coffre\StoreCoffreRequest;
use App\Http\Requests\Coffre\UpdateCoffreRequest;
use App\Http\Resources\Coffre\CoffreCollection;
use App\Http\Resources\Coffre\CoffreResource;
use App\Models\Coffre\Coffre;
use App\Services\Coffre\CoffreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoffreController extends Controller
{
    // primary service instance used by methods
    protected CoffreService $service;
    // keep legacy property in case other code references it
    protected $coffreservice;

    public function __construct(CoffreService $service)
    {
        $this->service = $service;
        $this->coffreservice = $service;
        // require auth for this controller (if needed)
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): CoffreCollection
    {
        $perPage = $request->integer('per_page', 15);
        $query = $request->string('search');

        $coffres = $query->isNotEmpty() 
            ? $this->service->search($query, $perPage)
            : $this->service->getAllPaginated($perPage);

        return new CoffreCollection($coffres);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCoffreRequest $request): JsonResponse
    {
        $coffre = $this->service->create($request->validated());

        return (new CoffreResource($coffre))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Coffre $coffre): CoffreResource
    {
        return new CoffreResource($coffre->load('user:id,name,email'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCoffreRequest $request, Coffre $coffre): CoffreResource
    {
        $updatedCoffre = $this->service->update($coffre, $request->validated());

        return new CoffreResource($updatedCoffre);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Coffre $coffre): JsonResponse
    {
        $this->service->delete($coffre);

        return response()->json([
            'message' => 'Coffre supprimé avec succès'
        ], 204);
    }

    /**
     * Get users for select dropdown
     */
    public function users(): JsonResponse
    {
        $users = $this->service->getUsersForSelect();

        return response()->json([
            'data' => $users
        ]);
    }
}
