<?php

namespace App\Http\Controllers\Reception;
use App\Http\Controllers\Controller;

use App\Models\Reception\ficheNavetteCustomPackage;
use Illuminate\Http\Request;

class FicheNavetteCustomPackageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $packages = ficheNavetteCustomPackage::all();
        return response()->json(['success' => true, 'data' => $packages]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Not needed for API, usually handled by frontend
        return response()->json(['success' => false, 'message' => 'Not implemented'], 405);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:fiche_navette_custom_packages,name',
            'description' => 'nullable|string',
        ]);

        $package = ficheNavetteCustomPackage::create($validated);

        return response()->json(['success' => true, 'data' => $package], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ficheNavetteCustomPackage $ficheNavetteCustomPackage)
    {
        return response()->json(['success' => true, 'data' => $ficheNavetteCustomPackage]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ficheNavetteCustomPackage $ficheNavetteCustomPackage)
    {
        // Not needed for API, usually handled by frontend
        return response()->json(['success' => false, 'message' => 'Not implemented'], 405);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ficheNavetteCustomPackage $ficheNavetteCustomPackage)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:fiche_navette_custom_packages,name,' . $ficheNavetteCustomPackage->id,
            'description' => 'nullable|string',
        ]);

        $ficheNavetteCustomPackage->update($validated);

        return response()->json(['success' => true, 'data' => $ficheNavetteCustomPackage]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ficheNavetteCustomPackage $ficheNavetteCustomPackage)
    {
        $ficheNavetteCustomPackage->delete();
        return response()->json(['success' => true, 'message' => 'Package deleted']);
    }
}
