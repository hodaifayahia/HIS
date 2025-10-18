<?php

namespace App\Http\Controllers\B2B;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\B2B\Agreement; // Don't forget to import your model
use Illuminate\Support\Facades\Storage; // Import Storage facade
use Illuminate\Validation\ValidationException; // Import ValidationException

class AgreementsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Agreement::query();

        // Filter by convention_id if provided
        if ($request->has('convention_id')) {
            $query->where('convention_id', $request->input('convention_id'));
        }

        $agreements = $query->get();

        // Add file_url to each agreement for easy access in frontend
        $agreements->each(function ($agreement) {
            if ($agreement->file_path) {
                $agreement->file_url = Storage::url($agreement->file_path);
            } else {
                $agreement->file_url = null;
            }
        });

        return response()->json($agreements);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255', // Use 'name' as per your database column and frontend consistency
                'description' => 'nullable|string',
                'file' => 'nullable|file|mimes:pdf,doc,docx|max:2048', // Allow PDF, Word docs, max 2MB
                'convention_id' => 'required|exists:conventions,id',
            ]);

            $filePath = null;
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('agreements', $fileName, 'public');
            }

            $agreement = Agreement::create([
                'name' => $validatedData['name'],
                'description' => $validatedData['description'],
                'file_path' => $filePath,
                'convention_id' => $validatedData['convention_id'],
            ]);

            // Add file_url before returning
            if ($agreement->file_path) {
                $agreement->file_url = Storage::url($agreement->file_path);
            }

            return response()->json($agreement, 201); // 201 Created
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation Error',
                'errors' => $e->errors()
            ], 422); // 422 Unprocessable Entity
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error storing agreement',
                'error' => $e->getMessage()
            ], 500); // 500 Internal Server Error
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $agreement = Agreement::find($id);

        if (!$agreement) {
            return response()->json(['message' => 'Agreement not found'], 404);
        }

        // You might want to return the full URL to the file
        if ($agreement->file_path) {
            $agreement->file_url = Storage::url($agreement->file_path);
        } else {
            $agreement->file_url = null;
        }

        return response()->json($agreement);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $agreement = Agreement::find($id);

            if (!$agreement) {
                return response()->json(['message' => 'Agreement not found'], 404);
            }

            $validatedData = $request->validate([
                'name' => 'required|string|max:255', // Use 'name'
                'description' => 'nullable|string',
                'file' => 'nullable|file|mimes:pdf,doc,docx|max:2048', // Allow PDF, Word docs, max 2MB
                'remove_file' => 'nullable|boolean', // New field to signal file removal
                'convention_id' => 'required|exists:conventions,id',
            ]);

            $filePath = $agreement->file_path; // Keep existing path by default

            // Handle file removal
            if (isset($validatedData['remove_file']) && $validatedData['remove_file']) {
                if ($filePath && Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->delete($filePath);
                }
                $filePath = null; // Clear the file path in the database
            }

            // Handle new file upload
            if ($request->hasFile('file')) {
                // Delete old file if it exists and wasn't marked for removal
                if ($filePath && Storage::disk('public')->exists($filePath) && !$request->boolean('remove_file')) {
                    Storage::disk('public')->delete($filePath);
                }
                $file = $request->file('file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('agreements', $fileName, 'public');
            }

            $agreement->update([
                'name' => $validatedData['name'], // Update 'name'
                'description' => $validatedData['description'],
                'file_path' => $filePath,
                'convention_id' => $validatedData['convention_id'],
            ]);

            // Add file_url before returning
            if ($agreement->file_path) {
                $agreement->file_url = Storage::url($agreement->file_path);
            } else {
                $agreement->file_url = null;
            }

            return response()->json($agreement);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation Error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating agreement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $agreement = Agreement::find($id);

        if (!$agreement) {
            return response()->json(['message' => 'Agreement not found'], 404);
        }

        // Delete the associated file if it exists
        if ($agreement->file_path && Storage::disk('public')->exists($agreement->file_path)) {
            Storage::disk('public')->delete($agreement->file_path);
        }

        $agreement->delete();

        return response()->json(['message' => 'Agreement deleted successfully'], 204); // 204 No Content
    }
}