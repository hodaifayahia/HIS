<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class FileServeController extends Controller
{
    /**
     * Serve a file from storage
     */
    public function serve($path)
    {
        $filePath = storage_path('app/public/' . $path);
        
        if (!file_exists($filePath)) {
            abort(404, 'File not found');
        }
        
        return Response::file($filePath);
    }
}
