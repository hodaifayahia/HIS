<?php

namespace App\Services\Reception;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
    /**
     * Upload multiple files and return their metadata
     */
    public function uploadConventionFiles(array $files): array
    {
        $uploadedFiles = [];
        
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $uploadedFiles[] = $this->uploadSingleFile($file);
            }
        }
        
        return $uploadedFiles;
    }

    /**
     * Upload a single file and return its metadata
     */
public function uploadSingleFile(UploadedFile $file): array
{
    // Generate unique filename
    $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

    // Store file in convention-documents directory
    $folder = 'convention-documents';
    $path = $file->storeAs($folder, $filename, 'public');

    return [
        'id' => Str::uuid(),
        'original_name' => $file->getClientOriginalName(),
        'filename' => $filename,
        'path' => $path, // This includes folder/filename.ext
        'folder' => $folder,
        'size' => $file->getSize(),
        'mime_type' => $file->getMimeType(),
        'uploaded_at' => now()->toISOString()
    ];
}

    /**
     * Delete files from storage
     */
    public function deleteFiles(array $files): void
    {
        foreach ($files as $file) {
            if (isset($file['path']) && Storage::disk('public')->exists($file['path'])) {
                Storage::disk('public')->delete($file['path']);
            }
        }
    }

    /**
     * Validate file types for convention documents
     */
    public function validateConventionFile(UploadedFile $file): bool
    {
        $allowedMimeTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        
        $allowedExtensions = ['pdf', 'doc', 'docx'];
        
        return in_array($file->getMimeType(), $allowedMimeTypes) && 
               in_array(strtolower($file->getClientOriginalExtension()), $allowedExtensions);
    }
}
