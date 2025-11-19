<?php

namespace App\Http\Controllers\Admission;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admission\AdmissionDocumentResource;
use App\Models\Admission;
use App\Models\AdmissionDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdmissionDocumentController extends Controller
{
    /**
     * Store a new document
     */
    public function store(Request $request, Admission $admission): JsonResponse
    {
        try {
            $request->validate([
                'type' => 'required|string|in:medical_record,lab_result,imaging,prescription,discharge_summary,other',
                'file' => 'required|file|max:10240', // 10MB max
                'description' => 'nullable|string|max:500',
            ]);

            // Store file
            $file = $request->file('file');
            $path = $file->store("admissions/{$admission->id}/documents", 'local');

            // Create document record
            $document = new AdmissionDocument([
                'type' => $request->type,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'description' => $request->description,
                'verified' => false,
                'uploaded_by' => auth()->id(),
            ]);

            $admission->documents()->save($document);

            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully',
                'data' => new AdmissionDocumentResource($document->load('uploader')),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload document',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download a document
     */
    public function download(Admission $admission, AdmissionDocument $document): \Symfony\Component\HttpFoundation\BinaryFileResponse|JsonResponse
    {
        try {
            // Verify document belongs to admission
            if ($document->admission_id !== $admission->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document not found for this admission',
                ], 404);
            }

            if (! Storage::disk('local')->exists($document->file_path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found',
                ], 404);
            }

            return Storage::disk('local')->download(
                $document->file_path,
                $document->file_name
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to download document',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a document
     */
    public function destroy(Admission $admission, AdmissionDocument $document): JsonResponse
    {
        try {
            // Verify document belongs to admission
            if ($document->admission_id !== $admission->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document not found for this admission',
                ], 404);
            }

            // Delete file from storage
            if (Storage::disk('local')->exists($document->file_path)) {
                Storage::disk('local')->delete($document->file_path);
            }

            $document->delete();

            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete document',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify a document
     */
    public function verify(Admission $admission, AdmissionDocument $document): JsonResponse
    {
        try {
            // Verify document belongs to admission
            if ($document->admission_id !== $admission->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document not found for this admission',
                ], 404);
            }

            $document->update([
                'verified' => true,
                'verified_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document verified successfully',
                'data' => new AdmissionDocumentResource($document->load('uploader')),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify document',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
