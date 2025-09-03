<?php
// app/Http/Controllers/Coffre/CaisseController.php

namespace App\Http\Controllers\Coffre;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coffre\StoreCaisseRequest;
use App\Http\Requests\Coffre\UpdateCaisseRequest;
use App\Http\Resources\Coffre\CaisseCollection;
use App\Http\Resources\Coffre\CaisseResource;
use App\Models\Coffre\Caisse;

use App\Services\Coffre\CaisseService;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CaisseController extends Controller
{
    protected CaisseService $service;

    public function __construct(CaisseService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): CaisseCollection
    {
        $perPage = $request->integer('per_page', 15);
        
        $filters = $request->only([
            'service_id', 'is_active', 'search'
        ]);

        $caisses = $this->service->getAllPaginated($filters, $perPage);

        return new CaisseCollection($caisses);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCaisseRequest $request): JsonResponse
    {
        $caisse = $this->service->create($request->validated());

        return (new CaisseResource($caisse))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Caisse $caisse): CaisseResource
    {
        return new CaisseResource($caisse->load('service'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCaisseRequest $request, Caisse $caisse): CaisseResource
    {
        $updatedCaisse = $this->service->update($caisse, $request->validated());

        return new CaisseResource($updatedCaisse);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Caisse $caisse): JsonResponse
    {
        $this->service->delete($caisse);

        return response()->json([
            'message' => 'Cash register deleted successfully'
        ], 204);
    }

    /**
     * Toggle caisse status
     */
    public function toggleStatus(Caisse $caisse): CaisseResource
    {
        // $this->authorize('update', $caisse);
        
        $updatedCaisse = $this->service->toggleStatus($caisse);

        return new CaisseResource($updatedCaisse);
    }

    /**
     * Get services for form dropdown
     */
    public function services(): JsonResponse
    {
        $services = $this->service->getServicesForSelect();

        return response()->json([
            'data' => $services
        ]);
    }

    /**
     * Get caisse statistics
     */
    public function stats(): JsonResponse
    {
        $stats = $this->service->getCaisseStats();

        return response()->json([
            'data' => $stats
        ]);
    }
}
