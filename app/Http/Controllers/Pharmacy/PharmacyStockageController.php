<?php

namespace App\Http\Controllers\Pharmacy;

use App\Models\PharmacyStockage;
use App\Models\PharmacyStorageTool;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PharmacyStockageController extends \App\Http\Controllers\Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = PharmacyStockage::with('service:id,name');

        // Search functionality
        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('location_code', 'like', "%{$search}%");
            });
        }

        // Filter by type (pharmacy-specific types)
        if ($request->has('type') && ! empty($request->type)) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->has('status') && ! empty($request->status)) {
            $query->where('status', $request->status);
        }

        // Filter by service
        if ($request->has('service_id') && ! empty($request->service_id)) {
            $query->where('service_id', $request->service_id);
        }

        // Filter by temperature controlled (important for pharmacy)
        if ($request->has('temperature_controlled')) {
            $query->where('temperature_controlled', $request->boolean('temperature_controlled'));
        }

        // Filter by security level (critical for controlled substances)
        if ($request->has('security_level') && ! empty($request->security_level)) {
            $query->where('security_level', $request->security_level);
        }

        // Filter by warehouse type (pharmacy-specific)
        if ($request->has('warehouse_type') && ! empty($request->warehouse_type)) {
            $query->where('warehouse_type', $request->warehouse_type);
        }

        // Filter by compliance status
        if ($request->has('compliance_status') && ! empty($request->compliance_status)) {
            $query->where('compliance_status', $request->compliance_status);
        }

        // Sort by name by default, but allow other sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 10);
        $stockages = $query->paginate($perPage);

        // Add pharmacy-specific calculations to each stockage
        $stockages->getCollection()->transform(function ($stockage) {
            // Count inventory items in this storage
            $stockage->inventory_count = $stockage->inventories()->count();

            // Count controlled substances
            $stockage->controlled_substances_count = $stockage->inventories()
                ->whereHas('product', function ($q) {
                    $q->where('is_controlled_substance', true);
                })->count();

            // Count expiring items (within 60 days)
            $stockage->expiring_items_count = $stockage->inventories()
                ->whereBetween('expiry_date', [now(), now()->addDays(60)])
                ->count();

            // Count expired items
            $stockage->expired_items_count = $stockage->inventories()
                ->where('expiry_date', '<', now())
                ->count();

            // Calculate capacity utilization if capacity is set
            if ($stockage->capacity) {
                $totalItems = $stockage->inventories()->sum('quantity');
                $stockage->capacity_utilization = round(($totalItems / $stockage->capacity) * 100, 2);
            } else {
                $stockage->capacity_utilization = null;
            }

            // Add compliance indicators
            $stockage->requires_temperature_monitoring = $stockage->temperature_controlled;
            $stockage->high_security_required = in_array($stockage->security_level, ['high', 'restricted']);

            return $stockage;
        });

        return response()->json([
            'success' => true,
            'data' => $stockages->items(),
            'meta' => [
                'current_page' => $stockages->currentPage(),
                'last_page' => $stockages->lastPage(),
                'per_page' => $stockages->perPage(),
                'total' => $stockages->total(),
                'from' => $stockages->firstItem(),
                'to' => $stockages->lastItem(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'required|string|max:255',
            'capacity' => 'nullable|integer|min:1',
            'type' => ['required', Rule::in(['warehouse', 'pharmacy', 'laboratory', 'emergency', 'storage_room', 'cold_room', 'controlled_substances_vault', 'refrigerated_storage', 'hazardous_materials'])],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'maintenance', 'under_construction', 'compliance_review'])],
            'service_id' => 'nullable|exists:services,id',
            'temperature_controlled' => 'boolean',
            'security_level' => ['required', Rule::in(['low', 'medium', 'high', 'restricted'])],
            'location_code' => 'nullable|string|max:255',
            'warehouse_type' => ['nullable', Rule::in(['Central Pharmacy (PC)', 'Service Pharmacy (PS)', 'Emergency Pharmacy', 'Outpatient Pharmacy'])],
            'temperature_range_min' => 'nullable|numeric',
            'temperature_range_max' => 'nullable|numeric',
            'humidity_controlled' => 'boolean',
            'access_restrictions' => 'nullable|string',
            'compliance_certifications' => 'nullable|string',
            'last_inspection_date' => 'nullable|date',
            'next_inspection_due' => 'nullable|date|after:today',
            'environmental_monitoring' => 'boolean',
            'backup_power' => 'boolean',
            'fire_suppression_system' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->all();

        // Set pharmacy-specific defaults
        $data['temperature_controlled'] = $request->boolean('temperature_controlled', false);
        $data['humidity_controlled'] = $request->boolean('humidity_controlled', false);
        $data['environmental_monitoring'] = $request->boolean('environmental_monitoring', false);
        $data['backup_power'] = $request->boolean('backup_power', false);
        $data['fire_suppression_system'] = $request->boolean('fire_suppression_system', false);

        // Set default status if not provided
        if (! isset($data['status'])) {
            $data['status'] = 'active';
        }

        // Validate temperature range if temperature controlled
        if ($data['temperature_controlled']) {
            if (isset($data['temperature_range_min']) && isset($data['temperature_range_max'])) {
                if ($data['temperature_range_min'] >= $data['temperature_range_max']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Minimum temperature must be less than maximum temperature',
                    ], 422);
                }
            }
        }

        // Validate security level for controlled substances storage
        if (in_array($data['type'], ['controlled_substances_vault']) && ! in_array($data['security_level'], ['high', 'restricted'])) {
            return response()->json([
                'success' => false,
                'message' => 'Controlled substances storage requires high or restricted security level',
            ], 422);
        }

        $stockage = PharmacyStockage::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Pharmacy storage created successfully',
            'data' => $stockage->load('service:id,name'),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(PharmacyStockage $stockage)
    {
        $stockage->load('service:id,name');

        // Add detailed pharmacy-specific information
        $stockage->inventory_summary = [
            'total_items' => $stockage->inventories()->count(),
            'total_quantity' => $stockage->inventories()->sum('quantity'),
            'controlled_substances' => $stockage->inventories()
                ->whereHas('product', function ($q) {
                    $q->where('is_controlled_substance', true);
                })->count(),
            'expiring_soon' => $stockage->inventories()
                ->whereBetween('expiry_date', [now(), now()->addDays(60)])
                ->count(),
            'expired' => $stockage->inventories()
                ->where('expiry_date', '<', now())
                ->count(),
            'low_stock_items' => $stockage->inventories()
                ->where('quantity', '<=', 20)
                ->count(),
        ];

        // Add compliance information
        $stockage->compliance_info = [
            'requires_inspection' => $stockage->next_inspection_due ? $stockage->next_inspection_due->isPast() : false,
            'days_until_inspection' => $stockage->next_inspection_due ? now()->diffInDays($stockage->next_inspection_due, false) : null,
            'certifications_current' => ! empty($stockage->compliance_certifications),
            'environmental_monitoring_active' => $stockage->environmental_monitoring,
            'security_compliant' => $stockage->security_level !== 'low',
        ];

        return response()->json([
            'success' => true,
            'data' => $stockage,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PharmacyStockage $stockage)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'required|string|max:255',
            'capacity' => 'nullable|integer|min:1',
            'type' => ['required', Rule::in(['warehouse', 'pharmacy', 'laboratory', 'emergency', 'storage_room', 'cold_room', 'controlled_substances_vault', 'refrigerated_storage', 'hazardous_materials'])],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'maintenance', 'under_construction', 'compliance_review'])],
            'service_id' => 'nullable|exists:services,id',
            'temperature_controlled' => 'boolean',
            'security_level' => ['required', Rule::in(['low', 'medium', 'high', 'restricted'])],
            'location_code' => 'nullable|string|max:255',
            'warehouse_type' => ['nullable', Rule::in(['Central Pharmacy (PC)', 'Service Pharmacy (PS)', 'Emergency Pharmacy', 'Outpatient Pharmacy'])],
            'temperature_range_min' => 'nullable|numeric',
            'temperature_range_max' => 'nullable|numeric',
            'humidity_controlled' => 'boolean',
            'access_restrictions' => 'nullable|string',
            'compliance_certifications' => 'nullable|string',
            'last_inspection_date' => 'nullable|date',
            'next_inspection_due' => 'nullable|date|after:today',
            'environmental_monitoring' => 'boolean',
            'backup_power' => 'boolean',
            'fire_suppression_system' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->all();

        // Validate temperature range if temperature controlled
        if ($request->boolean('temperature_controlled')) {
            if (isset($data['temperature_range_min']) && isset($data['temperature_range_max'])) {
                if ($data['temperature_range_min'] >= $data['temperature_range_max']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Minimum temperature must be less than maximum temperature',
                    ], 422);
                }
            }
        }

        // Validate security level for controlled substances storage
        if (in_array($data['type'], ['controlled_substances_vault']) && ! in_array($data['security_level'], ['high', 'restricted'])) {
            return response()->json([
                'success' => false,
                'message' => 'Controlled substances storage requires high or restricted security level',
            ], 422);
        }

        // Check if there are controlled substances in storage when downgrading security
        if ($stockage->security_level !== $data['security_level'] &&
            in_array($stockage->security_level, ['high', 'restricted']) &&
            ! in_array($data['security_level'], ['high', 'restricted'])) {

            $controlledSubstancesCount = $stockage->inventories()
                ->whereHas('product', function ($q) {
                    $q->where('is_controlled_substance', true);
                })->count();

            if ($controlledSubstancesCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot downgrade security level while controlled substances are stored here',
                ], 422);
            }
        }

        $stockage->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Pharmacy storage updated successfully',
            'data' => $stockage->load('service:id,name'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PharmacyStockage $stockage)
    {
        // Check if storage has any inventory
        $inventoryCount = $stockage->inventories()->count();
        if ($inventoryCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete storage that contains inventory items',
            ], 422);
        }

        // Additional check for controlled substances
        $controlledSubstancesCount = $stockage->inventories()
            ->whereHas('product', function ($q) {
                $q->where('is_controlled_substance', true);
            })->count();

        if ($controlledSubstancesCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete storage that contains controlled substances',
            ], 422);
        }

        $stockage->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pharmacy storage deleted successfully',
        ]);
    }

    /**
     * Get available managers for dropdown
     */
    public function getManagers()
    {
        // Return users whose role includes pharmacy management
        $managers = User::whereIn('role', ['manager', 'pharmacist', 'pharmacy_manager'])
            ->select('id', 'name', 'email', 'role')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $managers,
        ]);
    }

    /**
     * Get storage compliance report
     */
    public function getComplianceReport(Request $request)
    {
        $query = PharmacyStockage::query();

        // Filter by service if provided
        if ($request->has('service_id') && ! empty($request->service_id)) {
            $query->where('service_id', $request->service_id);
        }

        $storages = $query->get();

        $complianceReport = $storages->map(function ($storage) {
            $inventoryCount = $storage->inventories()->count();
            $controlledSubstancesCount = $storage->inventories()
                ->whereHas('product', function ($q) {
                    $q->where('is_controlled_substance', true);
                })->count();

            return [
                'id' => $storage->id,
                'name' => $storage->name,
                'type' => $storage->type,
                'security_level' => $storage->security_level,
                'temperature_controlled' => $storage->temperature_controlled,
                'environmental_monitoring' => $storage->environmental_monitoring,
                'compliance_status' => $storage->compliance_status ?? 'pending',
                'last_inspection_date' => $storage->last_inspection_date,
                'next_inspection_due' => $storage->next_inspection_due,
                'inspection_overdue' => $storage->next_inspection_due ? $storage->next_inspection_due->isPast() : false,
                'inventory_count' => $inventoryCount,
                'controlled_substances_count' => $controlledSubstancesCount,
                'compliance_score' => $this->calculateComplianceScore($storage),
                'compliance_issues' => $this->getComplianceIssues($storage),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $complianceReport,
            'summary' => [
                'total_storages' => $storages->count(),
                'compliant_storages' => $complianceReport->where('compliance_score', '>=', 80)->count(),
                'non_compliant_storages' => $complianceReport->where('compliance_score', '<', 80)->count(),
                'overdue_inspections' => $complianceReport->where('inspection_overdue', true)->count(),
            ],
        ]);
    }

    /**
     * Calculate compliance score for a storage
     */
    private function calculateComplianceScore($storage)
    {
        $score = 100;

        // Deduct points for missing inspections
        if ($storage->next_inspection_due && $storage->next_inspection_due->isPast()) {
            $score -= 30;
        }

        // Deduct points for inadequate security for controlled substances
        $controlledSubstancesCount = $storage->inventories()
            ->whereHas('product', function ($q) {
                $q->where('is_controlled_substance', true);
            })->count();

        if ($controlledSubstancesCount > 0 && ! in_array($storage->security_level, ['high', 'restricted'])) {
            $score -= 40;
        }

        // Deduct points for missing environmental monitoring
        if ($storage->temperature_controlled && ! $storage->environmental_monitoring) {
            $score -= 20;
        }

        // Deduct points for missing certifications
        if (empty($storage->compliance_certifications)) {
            $score -= 10;
        }

        return max(0, $score);
    }

    /**
     * Get compliance issues for a storage
     */
    private function getComplianceIssues($storage)
    {
        $issues = [];

        if ($storage->next_inspection_due && $storage->next_inspection_due->isPast()) {
            $issues[] = 'Inspection overdue';
        }

        $controlledSubstancesCount = $storage->inventories()
            ->whereHas('product', function ($q) {
                $q->where('is_controlled_substance', true);
            })->count();

        if ($controlledSubstancesCount > 0 && ! in_array($storage->security_level, ['high', 'restricted'])) {
            $issues[] = 'Inadequate security for controlled substances';
        }

        if ($storage->temperature_controlled && ! $storage->environmental_monitoring) {
            $issues[] = 'Missing environmental monitoring';
        }

        if (empty($storage->compliance_certifications)) {
            $issues[] = 'Missing compliance certifications';
        }

        return $issues;
    }

    /**
     * Update inspection status
     */
    public function updateInspectionStatus(Request $request, PharmacyStockage $stockage)
    {
        $validator = Validator::make($request->all(), [
            'last_inspection_date' => 'required|date|before_or_equal:today',
            'next_inspection_due' => 'required|date|after:today',
            'compliance_status' => ['required', Rule::in(['compliant', 'non_compliant', 'pending_review'])],
            'inspection_notes' => 'nullable|string|max:1000',
            'compliance_certifications' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $stockage->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Inspection status updated successfully',
            'data' => $stockage,
        ]);
    }

    /**
     * Get tool type options for pharmacy stockage tools.
     */
    public function getToolTypes()
    {
        $types = [
            ['value' => 'RY', 'label' => 'Rayonnage'],
            ['value' => 'AR', 'label' => 'Armoire'],
            ['value' => 'CF', 'label' => 'Coffre'],
            ['value' => 'FR', 'label' => 'Frigo'],
            ['value' => 'CS', 'label' => 'Caisson'],
            ['value' => 'CH', 'label' => 'Chariot'],
            ['value' => 'PL', 'label' => 'Palette'],
        ];

        return response()->json([
            'success' => true,
            'data' => $types,
        ]);
    }

    /**
     * Get block options for Rayonnage tools.
     */
    public function getBlocks()
    {
        $blocks = range('A', 'M');

        return response()->json([
            'success' => true,
            'data' => array_map(function ($block) {
                return ['value' => $block, 'label' => $block];
            }, $blocks),
        ]);
    }

    /**
     * Get tools for a specific stockage.
     */
    public function getTools(Request $request, $stockageId)
    {
        $stockage = PharmacyStockage::findOrFail($stockageId);

        $query = $stockage->pharmacyStockageTools();

        // Apply filters if provided
        if ($request->has('tool_type') && ! empty($request->tool_type)) {
            $query->where('tool_type', $request->tool_type);
        }

        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tool_type', 'like', "%{$search}%")
                    ->orWhere('tool_number', 'like', "%{$search}%")
                    ->orWhere('block', 'like', "%{$search}%")
                    ->orWhere('location_code', 'like', "%{$search}%");
            });
        }

        // Sort by tool_type and tool_number by default
        $sortBy = $request->get('sort_by', 'tool_type');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Add secondary sort by tool_number
        if ($sortBy !== 'tool_number') {
            $query->orderBy('tool_number', 'asc');
        }

        // Pagination
        $perPage = $request->get('per_page', 10);
        $tools = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $tools,
        ]);
    }

    /**
     * Add a tool to the stockage.
     */
    public function addTool(Request $request, $stockageId)
    {
        $stockage = PharmacyStockage::with('service')->findOrFail($stockageId);

        $validated = $request->validate([
            'tool_type' => ['required', Rule::in(['RY', 'AR', 'CF', 'FR', 'CS', 'CH', 'PL', 'SC', 'VT'])],
            'tool_number' => ['required', 'integer', 'min:1'],
            'block' => ['nullable', 'string', 'size:1', 'regex:/^[A-M]$/', Rule::requiredIf($request->tool_type === 'RY')],
            'shelf_level' => ['nullable', 'integer', 'min:1', Rule::requiredIf($request->tool_type === 'RY')],
        ]);

        // Check for unique constraint within the stockage
        $exists = PharmacyStorageTool::where('pharmacy_storage_id', $stockage->id)
            ->where('tool_type', $validated['tool_type'])
            ->where('tool_number', $validated['tool_number'])
            ->when($validated['block'] ?? null, function ($query, $block) {
                return $query->where('block', $block);
            })
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'A tool with this type and number already exists in this stockage.',
            ], 422);
        }

        try {
            $tool = PharmacyStorageTool::create([
                'pharmacy_storage_id' => $stockage->id,
                'tool_type' => $validated['tool_type'],
                'tool_number' => $validated['tool_number'],
                'block' => $validated['block'] ?? null,
                'shelf_level' => $validated['shelf_level'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tool added successfully',
                'data' => $tool,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add tool: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display a single tool for the given stockage.
     */
    public function showTool($stockageId, $toolId)
    {
        $tool = PharmacyStorageTool::with(['pharmacyStorage:id,name,location_code,service_id', 'pharmacyStorage.service:id,name,service_abv'])
            ->where('pharmacy_storage_id', $stockageId)
            ->where('id', $toolId)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $tool,
        ]);
    }

    /**
     * Update an existing tool that belongs to the stockage.
     */
    public function updateTool(Request $request, $stockageId, $toolId)
    {
        $stockage = PharmacyStockage::findOrFail($stockageId);

        $tool = $stockage->pharmacyStockageTools()
            ->where('id', $toolId)
            ->firstOrFail();

        $incomingToolType = $request->input('tool_type', $tool->tool_type);

        $validated = $request->validate([
            'tool_type' => ['sometimes', Rule::in(['RY', 'AR', 'CF', 'FR', 'CS', 'CH', 'PL', 'SC', 'VT'])],
            'tool_number' => ['sometimes', 'integer', 'min:1'],
            'block' => ['nullable', 'string', 'size:1', 'regex:/^[A-M]$/', Rule::requiredIf($incomingToolType === 'RY')],
            'shelf_level' => ['nullable', 'integer', 'min:1', Rule::requiredIf($incomingToolType === 'RY')],
        ]);

        $newToolType = $validated['tool_type'] ?? $tool->tool_type;
        $newToolNumber = $validated['tool_number'] ?? $tool->tool_number;
        $newBlock = array_key_exists('block', $validated) ? $validated['block'] : $tool->block;
        $newShelfLevel = array_key_exists('shelf_level', $validated) ? $validated['shelf_level'] : $tool->shelf_level;

        if ($newToolType !== 'RY') {
            $newBlock = null;
            $newShelfLevel = null;
        }

        $duplicateExists = PharmacyStorageTool::where('pharmacy_storage_id', $stockage->id)
            ->where('tool_type', $newToolType)
            ->where('tool_number', $newToolNumber)
            ->when($newToolType === 'RY', function ($query) use ($newBlock) {
                return $query->where('block', $newBlock);
            })
            ->where('id', '!=', $tool->id)
            ->exists();

        if ($duplicateExists) {
            return response()->json([
                'success' => false,
                'message' => 'Another tool with this type and number already exists in this stockage.',
            ], 422);
        }

        $updateData = $validated;
        $updateData['tool_type'] = $newToolType;
        $updateData['tool_number'] = $newToolNumber;
        $updateData['block'] = $newBlock;
        $updateData['shelf_level'] = $newShelfLevel;

        $tool->fill($updateData);
        $tool->save();

        return response()->json([
            'success' => true,
            'message' => 'Tool updated successfully',
            'data' => $tool->fresh(),
        ]);
    }

    /**
     * Remove a tool from the stockage.
     */
    public function removeTool($stockageId, $toolId)
    {
        $tool = PharmacyStorageTool::where('pharmacy_storage_id', $stockageId)
            ->where('id', $toolId)
            ->firstOrFail();

        $tool->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tool removed successfully',
        ]);
    }

    /**
     * Get inventory summary for a specific stockage.
     */
    public function getInventorySummary()
    {
        $stockages = PharmacyStockage::all();
        $summaries = [];

        foreach ($stockages as $stockage) {
            $totalProducts = $stockage->pharmacyInventories()->count();
            $totalQuantity = $stockage->pharmacyInventories()->sum('quantity');
            $uniqueProducts = $stockage->pharmacyInventories()->distinct('pharmacy_product_id')->count();

            $summaries[] = [
                'stockage_id' => $stockage->id,
                'stockage_name' => $stockage->name,
                'total_products' => $totalProducts,
                'total_quantity' => $totalQuantity,
                'unique_products' => $uniqueProducts,
            ];
        }

        return response()->json($summaries);
    }
}
