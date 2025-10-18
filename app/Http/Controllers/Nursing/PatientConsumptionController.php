<?php

namespace App\Http\Controllers\Nursing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nursing\StorePatientConsumptionRequest;
use App\Http\Requests\Nursing\UpdatePatientConsumptionRequest;
use App\Http\Resources\Nursing\PatientConsumptionResource;
use App\Models\Nursing\PatientConsumption;
use App\Services\Nursing\PatientConsumptionService;
use Illuminate\Http\Request;

class PatientConsumptionController extends Controller
{
    public function __construct(private PatientConsumptionService $service) {}

    public function index(Request $request)
    {
        $filters = $request->only(['fiche_id', 'product_id']);
        $consumptions = $this->service->list($filters, $request->get('per_page', 20));

        return PatientConsumptionResource::collection($consumptions);
    }

    public function store(StorePatientConsumptionRequest $request)
    {
        $consumptions = $this->service->createMany($request->validated()['consumptions']);

        return PatientConsumptionResource::collection($consumptions);
    }

    public function show(PatientConsumption $patientConsumption)
    {
        return new PatientConsumptionResource($patientConsumption->load(['product', 'ficheNavette', 'pharmacy', 'ficheNavetteItem']));
    }

    public function update(UpdatePatientConsumptionRequest $request, PatientConsumption $patientConsumption)
    {
        $consumption = $this->service->update($patientConsumption, $request->validated());

        return new PatientConsumptionResource($consumption);
    }

    public function destroy(PatientConsumption $patientConsumption)
    {
        $this->service->delete($patientConsumption);

        return response()->json(['message' => 'Deleted successfully']);
    }
}
