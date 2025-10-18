<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApprovalRuleResource;
use App\Models\ApprovalRule;
use App\Models\ApprovalRuleRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ApprovalRuleController extends Controller
{
    /**
     * Display a listing of approval rules.
     */
    public function index(Request $request)
    {
        $query = ApprovalRule::with(['product', 'ruleRoles']);

        // Filter by rule type
        if ($request->has('rule_type')) {
            $query->where('rule_type', $request->rule_type);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $rules = $query->byPriority()->paginate($request->input('per_page', 15));

        return ApprovalRuleResource::collection($rules);
    }

    /**
     * Store a newly created approval rule.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'rule_type' => 'required|in:product,amount',
            'product_id' => 'required_if:rule_type,product|nullable|exists:products,id',
            'min_amount' => 'required_if:rule_type,amount|nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0|gt:min_amount',
            'is_active' => 'boolean',
            'priority' => 'integer|min:0',
            'description' => 'nullable|string',
            'roles' => 'required|array|min:1',
            'roles.*.role_name' => 'required|string',
            'roles.*.sequence' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $rule = ApprovalRule::create($request->only([
                'name',
                'rule_type',
                'product_id',
                'min_amount',
                'max_amount',
                'is_active',
                'priority',
                'description',
            ]));

            // Create role assignments
            foreach ($request->roles as $roleData) {
                ApprovalRuleRole::create([
                    'approval_rule_id' => $rule->id,
                    'role_name' => $roleData['role_name'],
                    'sequence' => $roleData['sequence'] ?? 0,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Approval rule created successfully',
                'data' => new ApprovalRuleResource($rule->load(['product', 'ruleRoles'])),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create approval rule: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified approval rule.
     */
    public function show($id)
    {
        $rule = ApprovalRule::with(['product', 'ruleRoles'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new ApprovalRuleResource($rule),
        ]);
    }

    /**
     * Update the specified approval rule.
     */
    public function update(Request $request, $id)
    {
        $rule = ApprovalRule::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'rule_type' => 'in:product,amount',
            'product_id' => 'required_if:rule_type,product|nullable|exists:products,id',
            'min_amount' => 'required_if:rule_type,amount|nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0|gt:min_amount',
            'is_active' => 'boolean',
            'priority' => 'integer|min:0',
            'description' => 'nullable|string',
            'roles' => 'array',
            'roles.*.role_name' => 'required|string',
            'roles.*.sequence' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $rule->update($request->only([
                'name',
                'rule_type',
                'product_id',
                'min_amount',
                'max_amount',
                'is_active',
                'priority',
                'description',
            ]));

            // Update roles if provided
            if ($request->has('roles')) {
                // Delete existing roles
                $rule->ruleRoles()->delete();

                // Create new roles
                foreach ($request->roles as $roleData) {
                    ApprovalRuleRole::create([
                        'approval_rule_id' => $rule->id,
                        'role_name' => $roleData['role_name'],
                        'sequence' => $roleData['sequence'] ?? 0,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Approval rule updated successfully',
                'data' => new ApprovalRuleResource($rule->load(['product', 'ruleRoles'])),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update approval rule: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified approval rule.
     */
    public function destroy($id)
    {
        try {
            $rule = ApprovalRule::findOrFail($id);
            $rule->delete();

            return response()->json([
                'success' => true,
                'message' => 'Approval rule deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete approval rule: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle the active status of an approval rule.
     */
    public function toggleActive($id)
    {
        try {
            $rule = ApprovalRule::findOrFail($id);
            $rule->update(['is_active' => ! $rule->is_active]);

            return response()->json([
                'success' => true,
                'message' => 'Approval rule status updated successfully',
                'data' => new ApprovalRuleResource($rule->load(['product', 'ruleRoles'])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle approval rule status: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get approval rules statistics.
     */
    public function getStatistics()
    {
        try {
            $stats = [
                'total' => ApprovalRule::count(),
                'active' => ApprovalRule::where('is_active', true)->count(),
                'inactive' => ApprovalRule::where('is_active', false)->count(),
                'product_rules' => ApprovalRule::where('rule_type', 'product')->count(),
                'amount_rules' => ApprovalRule::where('rule_type', 'amount')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Assign roles to an approval rule.
     */
    public function assignRoles(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'role_ids' => 'required|array|min:1',
            'role_ids.*' => 'integer|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $rule = ApprovalRule::findOrFail($id);

            // Delete existing role assignments
            $rule->ruleRoles()->delete();

            // Create new role assignments
            foreach ($request->role_ids as $index => $roleId) {
                $role = \Spatie\Permission\Models\Role::findOrFail($roleId);

                ApprovalRuleRole::create([
                    'approval_rule_id' => $rule->id,
                    'role_name' => $role->name,
                    'sequence' => $index,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Roles assigned successfully',
                'data' => new ApprovalRuleResource($rule->load(['product', 'ruleRoles'])),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to assign roles: '.$e->getMessage(),
            ], 500);
        }
    }
}
