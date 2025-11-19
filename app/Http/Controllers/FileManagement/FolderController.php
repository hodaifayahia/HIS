<?php

namespace App\Http\Controllers\FileManagement;

use App\Http\Controllers\Controller;
use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FolderController extends Controller
{
    public function index(Request $request)
    {
        $folders = Folder::where('doctor_id', $request->doctorid)->get();

        return response()->json(['data' => $folders->values()->all()]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'doctor_id' => 'nullable|exists:doctors,id',
            'specializations_id' => 'nullable|exists:specializations,id',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $folder = Folder::create([
            'name' => $request->name,
            'description' => $request->description,
            'doctor_id' => $request->doctor_id ?? null,
            'specializations_id' => $request->specializations_id ?? null,
        ]);

        return response()->json(['data' => $folder], 201);
    }

    public function update(Request $request, Folder $folder)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'doctor_id' => 'nullable|exists:doctors,id',
            'specializations_id' => 'nullable|exists:specializations,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $folder->update($validator->validated());

        return response()->json(['data' => $folder]);
    }

    public function destroy(Folder $folder)
    {
        $folder->delete();

        return response()->json(null, 204);
    }

    public function search(Request $request)
    {
        $query = $request->get('query', '');
        $doctorId = $request->get('doctorid');

        $folders = Folder::where('doctor_id', $doctorId)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->get();

        return response()->json(['data' => $folders]);
    }
}
