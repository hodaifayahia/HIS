<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reception\StoreRemiseApproverRequest;
use App\Http\Requests\Reception\UpdateRemiseApproverRequest;
use App\Http\Resources\Reception\RemiseApproverResource;
use App\Models\Reception\RemiseApprover;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RemiseApproverController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = RemiseApprover::with(['user', 'approver']);

        // Filter by current user only
        $query->where('user_id', auth()->id());

        // Apply filters
        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->whereHas('approver', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('is_approved')) {
            $query->where('is_approved', filter_var($request->input('is_approved'), FILTER_VALIDATE_BOOLEAN));
        }

        $query->orderBy('created_at', 'desc');

        if ($request->filled('per_page') && $request->input('per_page') > 0) {
            $paginator = $query->paginate($request->input('per_page', 10));
            return RemiseApproverResource::collection($paginator);
        }

        $items = $query->get();
        return response()->json([
            'success' => true,
            'data' => RemiseApproverResource::collection($items)
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        ds($request->all());
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            // accept either single approver_id or array approver_ids
            'approver_ids' => ['sometimes', 'array'],
            'approver_ids.*' => ['integer', 'exists:users,id', 'different:user_id'],
            'approver_id' => ['sometimes', 'integer', 'exists:users,id', 'different:user_id'],
            'is_approved' => ['sometimes', 'boolean'],
            'comments' => ['nullable', 'string', 'max:1000'],
        ]);

        // ensure acting user creates only for themselves
        if ($validated['user_id'] !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You can only create approvers for yourself'
            ], 403);
        }

        // build array of approver ids
        $approverIds = [];
        if (!empty($validated['approver_ids'])) {
            $approverIds = $validated['approver_ids'];
        } elseif (!empty($validated['approver_id'])) {
            $approverIds = [$validated['approver_id']];
        }

        if (empty($approverIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No approver_id(s) provided'
            ], 422);
        }

        $created = [];
        $skipped = [];

        DB::beginTransaction();
        try {
            foreach ($approverIds as $approverId) {
                // skip if relationship already exists
                $existing = RemiseApprover::where('user_id', $validated['user_id'])
                    ->where('approver_id', $approverId)
                    ->first();

                if ($existing) {
                    $skipped[] = [
                        'approver_id' => $approverId,
                        'message' => 'relationship already exists'
                    ];
                    continue;
                }

                $remiseApprover = RemiseApprover::create([
                    'user_id' => $validated['user_id'],
                    'approver_id' => $approverId,
                    'is_approved' => $validated['is_approved'] ?? false,
                    'comments' => $validated['comments'] ?? null,
                ]);

                $remiseApprover->load(['user', 'approver']);
                $created[] = $remiseApprover;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create approver(s)',
                'error' => $e->getMessage()
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => count($created) . ' approver(s) created',
            'data' => RemiseApproverResource::collection(collect($created)),
            'skipped' => $skipped,
            'created_count' => count($created),
            'skipped_count' => count($skipped)
        ], count($created) ? 201 : 422);
    }

    /**
     * Display the specified resource.
     */
    public function show(RemiseApprover $remiseApprover)
    {
        // Ensure user can only view their own approvers
        if ($remiseApprover->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $remiseApprover->load(['user', 'approver']);
        
        return (new RemiseApproverResource($remiseApprover))
            ->additional(['success' => true]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RemiseApprover $remiseApprover)
    {
        // Ensure user can only update their own approvers
        if ($remiseApprover->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validated = $request->validate([
            'is_approved' => ['required', 'boolean'],
            'comments' => ['nullable', 'string', 'max:1000'],
        ]);

        $remiseApprover->update($validated);
        $remiseApprover->load(['user', 'approver']);

        return (new RemiseApproverResource($remiseApprover))
            ->additional([
                'success' => true,
                'message' => 'Approver updated successfully'
            ]);
    }

    /**
     * Toggle approval status.
     */
    public function toggleApproval(Request $request, RemiseApprover $remiseApprover)
    {
        // Ensure user can only toggle their own approvers
        if ($remiseApprover->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $remiseApprover->is_approved = !$remiseApprover->is_approved;

        if ($request->filled('comments')) {
            $remiseApprover->comments = $request->input('comments');
        }

        $remiseApprover->save();
        $remiseApprover->load(['user', 'approver']);

        return (new RemiseApproverResource($remiseApprover))
            ->additional([
                'success' => true,
                'message' => 'Approval status toggled successfully'
            ]);
    }

    /**
     * Bulk update approvers.
     */
    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'approver_ids' => ['required', 'array'],
            'approver_ids.*' => ['integer', 'exists:remise_approvers,id'],
            'is_approved' => ['required', 'boolean'],
            'comments' => ['nullable', 'string', 'max:1000'],
        ]);

        // Ensure user can only update their own approvers
        $approvers = RemiseApprover::whereIn('id', $validated['approver_ids'])
            ->where('user_id', auth()->id())
            ->get();

        if ($approvers->count() !== count($validated['approver_ids'])) {
            return response()->json([
                'success' => false,
                'message' => 'Some approvers were not found or you do not have permission to update them'
            ], 403);
        }

        $updateData = ['is_approved' => $validated['is_approved']];
        if (isset($validated['comments'])) {
            $updateData['comments'] = $validated['comments'];
        }

        $updated = RemiseApprover::whereIn('id', $validated['approver_ids'])
            ->where('user_id', auth()->id())
            ->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Approvers updated successfully',
            'updated_count' => $updated
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RemiseApprover $remiseApprover)
    {
        // Ensure user can only delete their own approvers
        if ($remiseApprover->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $remiseApprover->delete();

        return response()->json([
            'success' => true,
            'message' => 'Approver removed successfully'
        ]);
    }
}
