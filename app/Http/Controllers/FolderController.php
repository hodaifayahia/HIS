<?php

namespace App\Http\Controllers;

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
            'doctor_id' => 'nullable',
            'specializations_id' => 'nullable',
            'description' => 'nullable|string'
        ]);
       // iniitlize the doctor_id and specializations_id // This comment is misleading
if ($validator->fails()) {
    return response()->json(['errors' => $validator->errors()], 422);
}
 // Create the folder using the validated data
$folder = Folder::create([
    'name' => $request->name, // $validatedData is not defined here
    'description' => $request->description , // $validatedData is not defined here
    'doctor_id' => $request->doctor_id ?? null, // $validatedData is not defined here
    'specialization_id' =>$request->specialization_id  ?? null, // $validatedData is not defined here
]);
        return response()->json(['data' => $folder], 201);
    }

     public function update(Request $request, Folder $folder)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'doctor_id' => 'nullable|exists:doctors,id', // Add validation for doctor_id
            'specializations_id' => 'nullable|exists:specializations,id', // Add validation for specializations_id
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // It's better to update with specific validated data instead of all request data
        // to prevent mass assignment vulnerabilities if not all fields are fillable.
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

        $folders = Folder::where('name', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->get();

        return response()->json(['data' => $folders]);
    }
}
