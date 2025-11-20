<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ConfigController extends Controller
{
    /**
     * Get relation types from config
     */
    public function getRelationTypes(): JsonResponse
    {
        $relationTypes = config('relation_types', []);
        
        // Transform to array of objects with value and label
        $formatted = array_map(function ($label, $value) {
            return [
                'value' => $value,
                'label' => $label,
            ];
        }, $relationTypes, array_keys($relationTypes));
        
        return response()->json(array_values($formatted));
    }
}
