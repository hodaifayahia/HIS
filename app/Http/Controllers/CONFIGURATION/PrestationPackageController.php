<?php

namespace App\Http\Controllers\CONFIGURATION;

use App\Http\Controllers\Controller;
use App\Models\CONFIGURATION\PrestationPackage;
use App\Models\CONFIGURATION\PrestationPackageitem;
use App\Http\Requests\CONFIGURATION\StorePrestationPackageRequest;
use App\Http\Requests\CONFIGURATION\UpdatePrestationPackageRequest;
use App\Services\CONFIGURATION\PrestationPackageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrestationPackageController extends Controller
{
    /**
     * The service instance for handling business logic.
     *
     * @var PrestationPackageService
     */
    protected PrestationPackageService $service;

    /**
     * Create a new controller instance.
     *
     * @param PrestationPackageService $service
     * @return void
     */
    public function __construct(PrestationPackageService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        // Load all packages with their items and associated prestation details.
        $packages = PrestationPackage::with('items.prestation')->get();
        return response()->json($packages);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StorePrestationPackageRequest $request
     * @return JsonResponse
     */
    public function store(StorePrestationPackageRequest $request): JsonResponse
    {
        // Use the service to create the package and its items in a single transaction.
        $package = $this->service->createPackageWithItems($request->validated());
        return response()->json($package, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param PrestationPackage $prestationPackage
     * @return JsonResponse
     */
    public function show(PrestationPackage $prestationPackage): JsonResponse
    {
        // Load the associated items and prestations for the specific package.
        $prestationPackage->load('items.prestation');
        return response()->json($prestationPackage);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdatePrestationPackageRequest $request
     * @param PrestationPackage $prestationPackage
     * @return JsonResponse
     */
    public function update(UpdatePrestationPackageRequest $request, PrestationPackage $prestationPackage): JsonResponse
    {
        // Use the service to update the package details and sync its items.
        $package = $this->service->updatePackageWithItems($prestationPackage, $request->validated());
        return response()->json($package);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param PrestationPackage $prestationPackage
     * @return JsonResponse
     */
    public function destroy(PrestationPackage $prestationPackage): JsonResponse
    {
        // Manually delete the associated items first to prevent orphan records.
        $prestationPackage->items()->delete();
        // Then delete the package itself.
        $prestationPackage->delete();

        return response()->json(null, 204); // Return a 204 No Content status.
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Not implemented for API.
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PrestationPackage $prestationPackage)
    {
        // Not implemented for API.
    }

    /**
     * Clone an existing prestation package.
     *
     * @param Request $request
     * @param PrestationPackage $prestationPackage
     * @return JsonResponse
     */
    public function clone(Request $request, PrestationPackage $prestationPackage): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ]);

        // Use the service to clone the package
        $clonedPackage = $this->service->clonePackage($prestationPackage, $request->only(['name', 'price']));
        
        return response()->json($clonedPackage, 201);
    }
}