<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Models\StockageTool;
use App\Models\Stockage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class StockageToolController extends Controller
{
    /**
     * Display a listing of stockage tools for a specific stockage.
     */
    public function index(Request $request, $stockage): JsonResponse
    {
        $stockage = Stockage::findOrFail($stockage);

        $query = $stockage->stockageTools();

        // Apply filters
        if ($request->has('tool_type') && $request->tool_type) {
            $query->where('tool_type', $request->tool_type);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('tool_number', 'like', "%{$search}%")
                  ->orWhere('block', 'like', "%{$search}%")
                  ->orWhere('shelf_level', 'like', "%{$search}%");
            });
        }

        $tools = $query->orderBy('tool_type')
                      ->orderBy('tool_number')
                      ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $tools,
            'stockage' => $stockage
        ]);
    }

    /**
     * Store a newly created stockage tool.
     */
    public function store(Request $request, $stockage): JsonResponse
    {
        $stockage = Stockage::findOrFail($stockage);

        $validated = $request->validate([
            'tool_type' => ['required', Rule::in(['RY', 'AR', 'CF', 'FR', 'CS', 'CH', 'PL'])],
            'tool_number' => ['required', 'integer', 'min:1'],
            'block' => ['nullable', 'string', 'size:1', 'regex:/^[A-M]$/', Rule::requiredIf($request->tool_type === 'RY')],
            'shelf_level' => ['nullable', 'integer', 'min:1', Rule::requiredIf($request->tool_type === 'RY')]
        ]);

        // Check for unique constraint
        $exists = StockageTool::where('stockage_id', $stockage->id)
                             ->where('tool_type', $validated['tool_type'])
                             ->where('tool_number', $validated['tool_number'])
                             ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'A tool with this type and number already exists in this stockage.'
            ], 422);
        }

        $tool = StockageTool::create([
            'stockage_id' => $stockage->id,
            ...$validated
        ]);

        return response()->json([
            'success' => true,
            'data' => $tool->load('stockage.service'),
            'message' => 'Stockage tool created successfully.'
        ], 201);
    }

    /**
     * Display the specified stockage tool.
     */
    public function show($stockage, $toolId): JsonResponse
    {
        $tool = StockageTool::where('stockage_id', $stockage)
                           ->with('stockage')
                           ->findOrFail($toolId);

        return response()->json([
            'success' => true,
            'data' => $tool
        ]);
    }

    /**
     * Update the specified stockage tool.
     */
    public function update(Request $request, $stockage, $toolId): JsonResponse
    {
        $tool = StockageTool::where('stockage_id', $stockage)->findOrFail($toolId);

        $validated = $request->validate([
            'tool_type' => ['required', Rule::in(['RY', 'AR', 'CF', 'FR', 'CS', 'CH', 'PL'])],
            'tool_number' => ['required', 'integer', 'min:1'],
            'block' => ['nullable', 'string', 'size:1', 'regex:/^[A-M]$/', Rule::requiredIf($request->tool_type === 'RY')],
            'shelf_level' => ['nullable', 'integer', 'min:1', Rule::requiredIf($request->tool_type === 'RY')]
        ]);

        // Check for unique constraint (excluding current record)
        $exists = StockageTool::where('stockage_id', $stockage)
                             ->where('tool_type', $validated['tool_type'])
                             ->where('tool_number', $validated['tool_number'])
                             ->where('id', '!=', $toolId)
                             ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'A tool with this type and number already exists in this stockage.'
            ], 422);
        }

        $tool->update($validated);

        return response()->json([
            'success' => true,
            'data' => $tool->fresh('stockage.service'),
            'message' => 'Stockage tool updated successfully.'
        ]);
    }

    /**
     * Remove the specified stockage tool.
     */
    public function destroy($stockage, $toolId): JsonResponse
    {
        $tool = StockageTool::where('stockage_id', $stockage)->findOrFail($toolId);

        $tool->delete();

        return response()->json([
            'success' => true,
            'message' => 'Stockage tool deleted successfully.'
        ]);
    }

    /**
     * Get tool type options.
     */
    public function getToolTypes(): JsonResponse
    {
        $types = [
            ['value' => 'RY', 'label' => 'Rayonnage'],
            ['value' => 'AR', 'label' => 'Armoire'],
            ['value' => 'CF', 'label' => 'Coffre'],
            ['value' => 'FR', 'label' => 'Frigo'],
            ['value' => 'CS', 'label' => 'Caisson'],
            ['value' => 'CH', 'label' => 'Chariot'],
            ['value' => 'PL', 'label' => 'Palette']
        ];

        return response()->json([
            'success' => true,
            'data' => $types
        ]);
    }

    /**
     * Get block options for Rayonnage.
     */
    public function getBlocks(): JsonResponse
    {
        $blocks = range('A', 'M');

        return response()->json([
            'success' => true,
            'data' => array_map(function($block) {
                return ['value' => $block, 'label' => $block];
            }, $blocks)
        ]);
    }
}
