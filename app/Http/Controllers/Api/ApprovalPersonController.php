<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreApprovalPersonRequest;
use App\Http\Requests\UpdateApprovalPersonRequest;
use App\Http\Resources\ApprovalPersonResource;
use App\Models\ApprovalPerson;
use Illuminate\Http\Request;

class ApprovalPersonController extends Controller
{
    /**
     * Display a listing of approval persons.
     */
    public function index(Request $request)
    {
        $query = ApprovalPerson::with('user');

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search by user name or title
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })->orWhere('title', 'like', "%{$search}%");
        }

        // Order by priority
        $query->orderBy('priority', 'asc')
            ->orderBy('max_amount', 'asc');

        $approvalPersons = $request->has('per_page')
            ? $query->paginate($request->get('per_page', 15))
            : $query->get();

        return ApprovalPersonResource::collection($approvalPersons);
    }

    /**
     * Store a newly created approval person.
     */
    public function store(StoreApprovalPersonRequest $request)
    {
        $approvalPerson = ApprovalPerson::create($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Approval person created successfully',
            'data' => new ApprovalPersonResource($approvalPerson->load('user')),
        ], 201);
    }

    /**
     * Display the specified approval person.
     */
    public function show(ApprovalPerson $approvalPerson)
    {
        return new ApprovalPersonResource($approvalPerson->load('user'));
    }

    /**
     * Update the specified approval person.
     */
    public function update(UpdateApprovalPersonRequest $request, ApprovalPerson $approvalPerson)
    {
        $approvalPerson->update($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Approval person updated successfully',
            'data' => new ApprovalPersonResource($approvalPerson->load('user')),
        ]);
    }

    /**
     * Remove the specified approval person.
     */
    public function destroy(ApprovalPerson $approvalPerson)
    {
        // Check if there are pending approvals
        $pendingCount = $approvalPerson->approvals()->where('status', 'pending')->count();

        if ($pendingCount > 0) {
            return response()->json([
                'status' => 'error',
                'message' => "Cannot delete approval person with {$pendingCount} pending approval(s)",
            ], 422);
        }

        $approvalPerson->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Approval person deleted successfully',
        ]);
    }

    /**
     * Toggle active status of approval person.
     */
    public function toggleActive(ApprovalPerson $approvalPerson)
    {
        $approvalPerson->update([
            'is_active' => ! $approvalPerson->is_active,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Approval person status updated successfully',
            'data' => new ApprovalPersonResource($approvalPerson->load('user')),
        ]);
    }

    /**
     * Get approval persons who can handle a specific amount.
     */
    public function getForAmount(Request $request)
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        $amount = $request->get('amount');

        $approvalPersons = ApprovalPerson::with('user')
            ->canApproveAmount($amount)
            ->get();

        return ApprovalPersonResource::collection($approvalPersons);
    }
}
