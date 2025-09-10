<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\CRM\OrganismeContact;
use Illuminate\Http\Request;
use App\Http\Resources\CRM\OrganismeContactResource;

class OrganismeContactController extends Controller
{
    // Display a listing of contacts for all or for a given organisme_id
    public function index(Request $request)
    {
        $orgId = $request->query('organisme_id');
        $contacts = $orgId
            ? OrganismeContact::where('organisme_id', $orgId)->get()
            : OrganismeContact::all();

        // Wrap in resource collection
        return OrganismeContactResource::collection($contacts);
    }

    // Store a new contact
    public function store(Request $request)
    {
        $data = $request->validate([
            'organisme_id' => 'required|exists:organismes,id',
            'name' => 'required|string|max:191',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:191',
            'role' => 'nullable|string|max:191',
        ]);
        $contact = OrganismeContact::create($data);

        // Wrap in resource
        return new OrganismeContactResource($contact);
    }

    // Show a specific contact
    public function show(OrganismeContact $organismeContact)
    {
        return new OrganismeContactResource($organismeContact);
    }

    // Update a contact
    public function update(Request $request, OrganismeContact $organismeContact)
    {
        $data = $request->validate([
            'organisme_id' => 'sometimes|exists:organismes,id',
            'name' => 'sometimes|required|string|max:191',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:191',
            'role' => 'nullable|string|max:191',
        ]);
        $organismeContact->update($data);

        return new OrganismeContactResource($organismeContact);
    }

    // Delete a contact
    public function destroy(OrganismeContact $organismeContact)
    {
        $organismeContact->delete();
        return response()->json(['message' => 'Contact deleted successfully']);
    }
}
