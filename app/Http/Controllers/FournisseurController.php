<?php

namespace App\Http\Controllers;

use App\Models\Fournisseur;
use App\Services\FournisseurService;
use App\Http\Resources\FournisseurResource;
use App\Http\Resources\FournisseurContactResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FournisseurController extends Controller
{
    public function __construct(
        private FournisseurService $fournisseurService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $perPage = $request->get('per_page', 15);
            $fournisseurs = $this->fournisseurService->getAll($perPage);

            // If paginator, return resource collection to preserve meta
            if (method_exists($fournisseurs, 'withPath')) {
                return FournisseurResource::collection($fournisseurs);
            }

            return FournisseurResource::collection($fournisseurs);
        }

        $fournisseurs = $this->fournisseurService->getAll();

        return view('fournisseurs.index', compact('fournisseurs'));
    }

    /**
     * Show the form for creating a new resource.
     */

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
            'contacts' => 'nullable|array',
            'contacts.*.name' => 'required|string|max:255',
            'contacts.*.position' => 'nullable|string|max:255',
            'contacts.*.email' => 'nullable|email|max:255',
            'contacts.*.phone' => 'nullable|string|max:20',
            'contacts.*.mobile' => 'nullable|string|max:20',
            'contacts.*.is_primary' => 'boolean',
        ]);

        $fournisseur = $this->fournisseurService->create($validated);

        return (new FournisseurResource($fournisseur))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */

    /**
     * Show the form for editing the specified resource.
     */

    /**
     * Display the specified resource.
     */
    public function show(Fournisseur $fournisseur): JsonResponse
    {
        $fournisseurData = $this->fournisseurService->findById($fournisseur->id);

        return new FournisseurResource($fournisseurData);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Fournisseur $fournisseur): JsonResponse
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
            'contacts' => 'nullable|array',
            'contacts.*.id' => 'nullable|integer|exists:fournisseur_contacts,id',
            'contacts.*.name' => 'required|string|max:255',
            'contacts.*.position' => 'nullable|string|max:255',
            'contacts.*.email' => 'nullable|email|max:255',
            'contacts.*.phone' => 'nullable|string|max:20',
            'contacts.*.mobile' => 'nullable|string|max:20',
            'contacts.*.is_primary' => 'boolean',
        ]);

        $fournisseur = $this->fournisseurService->update($fournisseur, $validated);

        return (new FournisseurResource($fournisseur))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Fournisseur $fournisseur): JsonResponse
    {
        $this->fournisseurService->delete($fournisseur);
        return response()->json([
            'message' => 'Fournisseur deleted successfully',
        ]);
    }

    /**
     * Search fournisseurs.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->validate([
            'q' => 'required|string|min:2',
        ])['q'];

        $fournisseurs = $this->fournisseurService->search($query);

        return FournisseurResource::collection($fournisseurs);
    }

    /**
     * Get active fournisseurs.
     */
    public function active(): JsonResponse
    {
        $fournisseurs = $this->fournisseurService->getActive();

        return FournisseurResource::collection($fournisseurs);
    }
}
