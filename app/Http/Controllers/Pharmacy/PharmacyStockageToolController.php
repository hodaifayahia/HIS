<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\PharmacyStockage;
use App\Models\PharmacyStorageTool;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PharmacyStockageToolController extends Controller
{
    /**
     * Display a listing of stockage tools for a specific pharmacy stockage.
     */
    public function index(Request $request, $stockage): JsonResponse
    {
        $stockage = PharmacyStockage::findOrFail($stockage);

        $query = $stockage->stockageTools();

        // Apply filters
        if ($request->has('tool_type') && $request->tool_type) {
            $query->where('tool_type', $request->tool_type);
        }

        if ($request->has('controlled_substance_level') && $request->controlled_substance_level) {
            $query->where('controlled_substance_level', $request->controlled_substance_level);
        }

        if ($request->has('temperature_controlled') && $request->temperature_controlled !== null) {
            $query->where('temperature_controlled', $request->boolean('temperature_controlled'));
        }

        if ($request->has('security_level') && $request->security_level) {
            $query->where('security_level', $request->security_level);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tool_number', 'like', "%{$search}%")
                    ->orWhere('block', 'like', "%{$search}%")
                    ->orWhere('shelf_level', 'like', "%{$search}%")
                    ->orWhere('location_code', 'like', "%{$search}%");
            });
        }

        $tools = $query->orderBy('security_level', 'desc')
            ->orderBy('controlled_substance_level', 'desc')
            ->orderBy('tool_type')
            ->orderBy('tool_number')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $tools,
            'stockage' => $stockage,
            'summary' => $this->getStockageToolsSummary($stockage->id),
        ]);
    }

    /**
     * Store a newly created pharmacy stockage tool.
     */
    public function store(Request $request, $stockage): JsonResponse
    {
        $stockage = PharmacyStockage::findOrFail($stockage);

        $validated = $request->validate([
            'tool_type' => ['required', Rule::in(['RY', 'AR', 'CF', 'FR', 'CS', 'CH', 'PL', 'SC', 'VT'])],
            'tool_number' => ['required', 'integer', 'min:1'],
            'block' => ['nullable', 'string', 'size:1', 'regex:/^[A-M]$/', Rule::requiredIf($request->tool_type === 'RY')],
            'shelf_level' => ['nullable', 'integer', 'min:1', Rule::requiredIf($request->tool_type === 'RY')],
            'controlled_substance_level' => ['nullable', Rule::in(['I', 'II', 'III', 'IV', 'V'])],
            'security_level' => ['required', Rule::in(['low', 'medium', 'high', 'maximum'])],
            'temperature_controlled' => ['boolean'],
            'temperature_min' => ['nullable', 'numeric', 'min:-50', 'max:50'],
            'temperature_max' => ['nullable', 'numeric', 'min:-50', 'max:50', 'gt:temperature_min'],
            'humidity_controlled' => ['boolean'],
            'humidity_min' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'humidity_max' => ['nullable', 'numeric', 'min:0', 'max:100', 'gt:humidity_min'],
            'light_sensitive' => ['boolean'],
            'access_log_required' => ['boolean'],
            'dual_control_required' => ['boolean'],
            'location_code' => ['nullable', 'string', 'max:20'],
            'capacity_limit' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // Validate pharmacy-specific rules
        $this->validatePharmacyToolRules($validated);

        // Check for unique constraint
        $exists = PharmacyStorageTool::where('stockage_id', $stockage->id)
            ->where('tool_type', $validated['tool_type'])
            ->where('tool_number', $validated['tool_number'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'A tool with this type and number already exists in this stockage.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $tool = PharmacyStorageTool::create([
                'stockage_id' => $stockage->id,
                ...$validated,
            ]);

            // Create initial access log entry
            if ($validated['access_log_required'] ?? false) {
                $this->createAccessLogEntry($tool->id, 'created', auth()->id());
            }

            DB::commit();

            // Clear cache
            Cache::forget("pharmacy_stockage_tools_{$stockage->id}");

            return response()->json([
                'success' => true,
                'data' => $tool->load('stockage.service'),
                'message' => 'Pharmacy stockage tool created successfully.',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create stockage tool: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified pharmacy stockage tool.
     */
    public function show($stockage, $toolId): JsonResponse
    {
        $tool = PharmacyStorageTool::where('stockage_id', $stockage)
            ->with(['stockage', 'accessLogs' => function ($query) {
                $query->latest()->limit(10);
            }])
            ->findOrFail($toolId);

        return response()->json([
            'success' => true,
            'data' => $tool,
            'compliance_status' => $this->getComplianceStatus($tool),
            'current_capacity' => $this->getCurrentCapacity($tool),
        ]);
    }

    /**
     * Update the specified pharmacy stockage tool.
     */
    public function update(Request $request, $stockage, $toolId): JsonResponse
    {
        $tool = PharmacyStorageTool::where('stockage_id', $stockage)->findOrFail($toolId);

        $validated = $request->validate([
            'tool_type' => ['required', Rule::in(['RY', 'AR', 'CF', 'FR', 'CS', 'CH', 'PL', 'SC', 'VT'])],
            'tool_number' => ['required', 'integer', 'min:1'],
            'block' => ['nullable', 'string', 'size:1', 'regex:/^[A-M]$/', Rule::requiredIf($request->tool_type === 'RY')],
            'shelf_level' => ['nullable', 'integer', 'min:1', Rule::requiredIf($request->tool_type === 'RY')],
            'controlled_substance_level' => ['nullable', Rule::in(['I', 'II', 'III', 'IV', 'V'])],
            'security_level' => ['required', Rule::in(['low', 'medium', 'high', 'maximum'])],
            'temperature_controlled' => ['boolean'],
            'temperature_min' => ['nullable', 'numeric', 'min:-50', 'max:50'],
            'temperature_max' => ['nullable', 'numeric', 'min:-50', 'max:50', 'gt:temperature_min'],
            'humidity_controlled' => ['boolean'],
            'humidity_min' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'humidity_max' => ['nullable', 'numeric', 'min:0', 'max:100', 'gt:humidity_min'],
            'light_sensitive' => ['boolean'],
            'access_log_required' => ['boolean'],
            'dual_control_required' => ['boolean'],
            'location_code' => ['nullable', 'string', 'max:20'],
            'capacity_limit' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // Validate pharmacy-specific rules
        $this->validatePharmacyToolRules($validated);

        // Check for unique constraint (excluding current record)
        $exists = PharmacyStorageTool::where('stockage_id', $stockage)
            ->where('tool_type', $validated['tool_type'])
            ->where('tool_number', $validated['tool_number'])
            ->where('id', '!=', $toolId)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'A tool with this type and number already exists in this stockage.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $originalData = $tool->toArray();
            $tool->update($validated);

            // Log significant changes
            if ($this->hasSignificantChanges($originalData, $validated)) {
                $this->createAccessLogEntry($tool->id, 'updated', auth()->id(), [
                    'changes' => $this->getChanges($originalData, $validated),
                ]);
            }

            DB::commit();

            // Clear cache
            Cache::forget("pharmacy_stockage_tools_{$stockage}");

            return response()->json([
                'success' => true,
                'data' => $tool->fresh(['stockage.service', 'accessLogs']),
                'message' => 'Pharmacy stockage tool updated successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update stockage tool: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified pharmacy stockage tool.
     */
    public function destroy($stockage, $toolId): JsonResponse
    {
        $tool = PharmacyStorageTool::where('stockage_id', $stockage)->findOrFail($toolId);

        // Check if tool has products
        if ($tool->products()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete stockage tool that contains products.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Log deletion
            if ($tool->access_log_required) {
                $this->createAccessLogEntry($tool->id, 'deleted', auth()->id());
            }

            $tool->delete();

            DB::commit();

            // Clear cache
            Cache::forget("pharmacy_stockage_tools_{$stockage}");

            return response()->json([
                'success' => true,
                'message' => 'Pharmacy stockage tool deleted successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete stockage tool: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get pharmacy tool type options.
     */
    public function getToolTypes(): JsonResponse
    {
        $types = [
            ['value' => 'RY', 'label' => 'Rayonnage', 'security_levels' => ['low', 'medium']],
            ['value' => 'AR', 'label' => 'Armoire', 'security_levels' => ['medium', 'high']],
            ['value' => 'CF', 'label' => 'Coffre-fort', 'security_levels' => ['high', 'maximum']],
            ['value' => 'FR', 'label' => 'Réfrigérateur', 'security_levels' => ['low', 'medium', 'high']],
            ['value' => 'CS', 'label' => 'Caisson sécurisé', 'security_levels' => ['high', 'maximum']],
            ['value' => 'CH', 'label' => 'Chariot', 'security_levels' => ['low', 'medium']],
            ['value' => 'PL', 'label' => 'Palette', 'security_levels' => ['low']],
            ['value' => 'SC', 'label' => 'Coffre stupéfiants', 'security_levels' => ['maximum']],
            ['value' => 'VT', 'label' => 'Vitrine', 'security_levels' => ['low', 'medium']],
        ];

        return response()->json([
            'success' => true,
            'data' => $types,
        ]);
    }

    /**
     * Get controlled substance levels.
     */
    public function getControlledSubstanceLevels(): JsonResponse
    {
        $levels = [
            ['value' => 'I', 'label' => 'Schedule I', 'description' => 'Highest control level'],
            ['value' => 'II', 'label' => 'Schedule II', 'description' => 'High control level'],
            ['value' => 'III', 'label' => 'Schedule III', 'description' => 'Moderate control level'],
            ['value' => 'IV', 'label' => 'Schedule IV', 'description' => 'Low control level'],
            ['value' => 'V', 'label' => 'Schedule V', 'description' => 'Lowest control level'],
        ];

        return response()->json([
            'success' => true,
            'data' => $levels,
        ]);
    }

    /**
     * Get security levels.
     */
    public function getSecurityLevels(): JsonResponse
    {
        $levels = [
            ['value' => 'low', 'label' => 'Low Security', 'description' => 'Basic security measures'],
            ['value' => 'medium', 'label' => 'Medium Security', 'description' => 'Enhanced security measures'],
            ['value' => 'high', 'label' => 'High Security', 'description' => 'Strict security measures'],
            ['value' => 'maximum', 'label' => 'Maximum Security', 'description' => 'Highest security measures'],
        ];

        return response()->json([
            'success' => true,
            'data' => $levels,
        ]);
    }

    /**
     * Get tools by controlled substance level.
     */
    public function getByControlledSubstanceLevel(Request $request, $stockage): JsonResponse
    {
        $request->validate([
            'level' => ['required', Rule::in(['I', 'II', 'III', 'IV', 'V'])],
        ]);

        $tools = PharmacyStorageTool::where('stockage_id', $stockage)
            ->where('controlled_substance_level', $request->level)
            ->with('stockage')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tools,
        ]);
    }

    /**
     * Get temperature controlled tools.
     */
    public function getTemperatureControlled(Request $request, $stockage): JsonResponse
    {
        $tools = PharmacyStorageTool::where('stockage_id', $stockage)
            ->where('temperature_controlled', true)
            ->with('stockage')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tools,
        ]);
    }

    /**
     * Validate pharmacy-specific tool rules.
     */
    private function validatePharmacyToolRules(array $data): void
    {
        // Controlled substances require high security
        if (! empty($data['controlled_substance_level']) &&
            in_array($data['controlled_substance_level'], ['I', 'II']) &&
            ! in_array($data['security_level'], ['high', 'maximum'])) {
            throw new \InvalidArgumentException('Schedule I and II controlled substances require high or maximum security level.');
        }

        // Temperature controlled tools need temperature ranges
        if ($data['temperature_controlled'] &&
            (empty($data['temperature_min']) || empty($data['temperature_max']))) {
            throw new \InvalidArgumentException('Temperature controlled tools must have temperature ranges defined.');
        }

        // Humidity controlled tools need humidity ranges
        if ($data['humidity_controlled'] &&
            (empty($data['humidity_min']) || empty($data['humidity_max']))) {
            throw new \InvalidArgumentException('Humidity controlled tools must have humidity ranges defined.');
        }
    }

    /**
     * Get stockage tools summary.
     */
    private function getStockageToolsSummary(int $stockageId): array
    {
        return Cache::remember("pharmacy_stockage_tools_summary_{$stockageId}", 300, function () use ($stockageId) {
            $tools = PharmacyStorageTool::where('stockage_id', $stockageId)->get();

            return [
                'total_tools' => $tools->count(),
                'by_security_level' => $tools->groupBy('security_level')->map->count(),
                'controlled_substance_tools' => $tools->whereNotNull('controlled_substance_level')->count(),
                'temperature_controlled' => $tools->where('temperature_controlled', true)->count(),
                'access_log_required' => $tools->where('access_log_required', true)->count(),
            ];
        });
    }

    /**
     * Get compliance status for a tool.
     */
    private function getComplianceStatus(PharmacyStorageTool $tool): array
    {
        $status = [
            'compliant' => true,
            'issues' => [],
        ];

        // Check temperature compliance
        if ($tool->temperature_controlled && ! $this->isTemperatureCompliant($tool)) {
            $status['compliant'] = false;
            $status['issues'][] = 'Temperature out of range';
        }

        // Check access log compliance
        if ($tool->access_log_required && ! $this->hasRecentAccessLog($tool)) {
            $status['compliant'] = false;
            $status['issues'][] = 'Missing recent access logs';
        }

        return $status;
    }

    /**
     * Get current capacity for a tool.
     */
    private function getCurrentCapacity(PharmacyStorageTool $tool): array
    {
        $currentCount = $tool->products()->sum('quantity');
        $limit = $tool->capacity_limit ?? 0;

        return [
            'current' => $currentCount,
            'limit' => $limit,
            'percentage' => $limit > 0 ? round(($currentCount / $limit) * 100, 2) : 0,
            'available' => max(0, $limit - $currentCount),
        ];
    }

    /**
     * Create access log entry.
     */
    private function createAccessLogEntry(int $toolId, string $action, int $userId, array $metadata = []): void
    {
        // Implementation would create access log entry
        // This is a placeholder for the actual implementation
    }

    /**
     * Check if changes are significant enough to log.
     */
    private function hasSignificantChanges(array $original, array $new): bool
    {
        $significantFields = [
            'controlled_substance_level', 'security_level', 'temperature_controlled',
            'temperature_min', 'temperature_max', 'access_log_required',
        ];

        foreach ($significantFields as $field) {
            if (($original[$field] ?? null) !== ($new[$field] ?? null)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get changes between original and new data.
     */
    private function getChanges(array $original, array $new): array
    {
        $changes = [];
        foreach ($new as $key => $value) {
            if (($original[$key] ?? null) !== $value) {
                $changes[$key] = [
                    'from' => $original[$key] ?? null,
                    'to' => $value,
                ];
            }
        }

        return $changes;
    }

    /**
     * Check temperature compliance.
     */
    private function isTemperatureCompliant(PharmacyStorageTool $tool): bool
    {
        // Implementation would check current temperature against ranges
        // This is a placeholder for the actual implementation
        return true;
    }

    /**
     * Check if tool has recent access logs.
     */
    private function hasRecentAccessLog(PharmacyStorageTool $tool): bool
    {
        // Implementation would check for recent access logs
        // This is a placeholder for the actual implementation
        return true;
    }
}
