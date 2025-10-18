<?php

namespace App\Http\Controllers;

use App\Models\BonCommend;
use App\Models\BonEntree;
use App\Models\BonReception;
use App\Models\FactureProforma;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentController extends Controller
{
    /**
     * Upload attachment files for purchasing documents
     */
    public function upload(Request $request)
    {
        $request->validate([
            'files.*' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:10240', // 10MB max
            'model_type' => 'required|in:bon_commend,bon_reception,bon_entree,facture_proforma',
            'model_id' => 'required|integer',
        ]);

        $modelClass = $this->getModelClass($request->model_type);
        $model = $modelClass::findOrFail($request->model_id);

        $attachments = $model->attachments ?? [];
        $uploadedFiles = [];

        foreach ($request->file('files') as $file) {
            $filename = time().'_'.Str::random(10).'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('attachments/'.$request->model_type, $filename, 'public');

            $attachmentData = [
                'id' => Str::uuid(),
                'original_name' => $file->getClientOriginalName(),
                'filename' => $filename,
                'path' => $path,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'uploaded_at' => now()->toISOString(),
                'uploaded_by' => Auth::id(),
            ];

            $attachments[] = $attachmentData;
            $uploadedFiles[] = $attachmentData;
        }

        $model->update(['attachments' => $attachments]);

        return response()->json([
            'success' => true,
            'message' => 'Files uploaded successfully',
            'files' => $uploadedFiles,
        ]);
    }

    /**
     * Download an attachment file
     */
    public function download(Request $request)
    {
        $request->validate([
            'model_type' => 'required|in:bon_commend,bon_reception,bon_entree,facture_proforma',
            'model_id' => 'required|integer',
            'attachment_id' => 'required|string',
        ]);

        $modelClass = $this->getModelClass($request->model_type);
        $model = $modelClass::findOrFail($request->model_id);

        $attachments = $model->attachments ?? [];
        $attachment = collect($attachments)->firstWhere('id', $request->attachment_id);

        if (! $attachment) {
            return response()->json(['error' => 'Attachment not found'], 404);
        }

        if (! Storage::disk('public')->exists($attachment['path'])) {
            return response()->json(['error' => 'File not found'], 404);
        }

        return Storage::disk('public')->download($attachment['path'], $attachment['original_name']);
    }

    /**
     * View/preview an attachment file
     */
    public function view(Request $request)
    {
        $request->validate([
            'model_type' => 'required|in:bon_commend,bon_reception,bon_entree,facture_proforma',
            'model_id' => 'required|integer',
            'attachment_id' => 'required|string',
        ]);

        $modelClass = $this->getModelClass($request->model_type);
        $model = $modelClass::findOrFail($request->model_id);

        $attachments = $model->attachments ?? [];
        $attachment = collect($attachments)->firstWhere('id', $request->attachment_id);

        if (! $attachment) {
            return response()->json(['error' => 'Attachment not found'], 404);
        }

        if (! Storage::disk('public')->exists($attachment['path'])) {
            return response()->json(['error' => 'File not found'], 404);
        }

        $filePath = Storage::disk('public')->path($attachment['path']);

        return response()->file($filePath, [
            'Content-Type' => $attachment['mime_type'],
            'Content-Disposition' => 'inline; filename="'.$attachment['original_name'].'"',
        ]);
    }

    /**
     * Delete an attachment file
     */
    public function delete(Request $request)
    {
        $request->validate([
            'model_type' => 'required|in:bon_commend,bon_reception,bon_entree,facture_proforma',
            'model_id' => 'required|integer',
            'attachment_id' => 'required|string',
        ]);

        $modelClass = $this->getModelClass($request->model_type);
        $model = $modelClass::findOrFail($request->model_id);

        $attachments = $model->attachments ?? [];
        $attachmentIndex = collect($attachments)->search(function ($attachment) use ($request) {
            return $attachment['id'] === $request->attachment_id;
        });

        if ($attachmentIndex === false) {
            return response()->json(['error' => 'Attachment not found'], 404);
        }

        $attachment = $attachments[$attachmentIndex];

        // Delete the physical file
        if (Storage::disk('public')->exists($attachment['path'])) {
            Storage::disk('public')->delete($attachment['path']);
        }

        // Remove from attachments array
        unset($attachments[$attachmentIndex]);
        $attachments = array_values($attachments); // Reindex array

        $model->update(['attachments' => $attachments]);

        return response()->json([
            'success' => true,
            'message' => 'Attachment deleted successfully',
        ]);
    }

    /**
     * List all attachments for a model
     */
    public function list(Request $request)
    {
        $request->validate([
            'model_type' => 'required|in:bon_commend,bon_reception,bon_entree,facture_proforma',
            'model_id' => 'required|integer',
        ]);

        $modelClass = $this->getModelClass($request->model_type);
        $model = $modelClass::findOrFail($request->model_id);

        $attachments = $model->attachments ?? [];

        return response()->json([
            'success' => true,
            'attachments' => $attachments,
        ]);
    }

    /**
     * Get the model class based on model type
     */
    private function getModelClass($modelType)
    {
        return match ($modelType) {
            'bon_commend' => BonCommend::class,
            'bon_reception' => BonReception::class,
            'bon_entree' => BonEntree::class,
            'facture_proforma' => FactureProforma::class,
            default => throw new \InvalidArgumentException('Invalid model type')
        };
    }
}
