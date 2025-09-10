<?php

namespace App\Services\Reception;

use App\Events\Reception\RemiseRequestApproved;
use App\Events\Reception\RemiseRequestCreated;
use App\Events\Reception\RemiseRequestRejected;
use App\Models\Reception\RemiseRequest;
use App\Models\Reception\RemiseRequestApproval;
use App\Models\Reception\RemiseRequestNotification;
use App\Models\Reception\RemiseRequestPrestation;
use App\Models\Reception\RemiseRequestPrestationContribution;
use App\Models\Reception\RemiseApprover; // Add this import
use App\Models\User;
use App\Http\Enum\Reception\RemiseRequestStatusEnum;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RemiseRequestNotificationService
{
    /**
     * Create remise requests for contribution users and their approved approvers
     */
    public function createRequest(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $created = [];

            // Get contribution user IDs from prestations
            $contribUserIds = collect($data['prestations'] ?? [])
                ->flatMap(fn($p) => collect($p['contributions'] ?? [])->pluck('user_id'))
                ->filter()
                ->map(fn($id) => (int)$id)
                ->unique()
                ->values()
                ->toArray();

            // Allow explicit receiver_ids fallback/merge
            $explicitReceivers = collect($data['receiver_ids'] ?? [])->map(fn($id) => (int)$id)->toArray();
            $receiverIds = array_values(array_unique(array_merge($contribUserIds, $explicitReceivers)));

            // Include legacy single receiver_id if provided
            if (empty($receiverIds) && !empty($data['receiver_id'])) {
                $receiverIds[] = (int)$data['receiver_id'];
            }

            // For each receiver, create requests for them and their approved approvers
            foreach ($receiverIds as $receiverId) {
                // Get approved approvers for this receiver
                $approvers = RemiseApprover::where('user_id', $receiverId)
                    ->where('is_approved', true)
                    ->with('approver')
                    ->get();

                // Build prestations containing only contributions for this receiver
                $prestationsForReceiver = [];
                foreach ($data['prestations'] as $p) {
                    $ownContribs = array_values(array_filter(
                        $p['contributions'] ?? [], 
                        fn($c) => (int)$c['user_id'] === (int)$receiverId
                    ));
                    
                    if (!empty($ownContribs)) {
                        $prestationsForReceiver[] = [
                            'prestation_id' => $p['prestation_id'],
                            'proposed_amount' => $p['proposed_amount'],
                            'contributions' => $ownContribs
                        ];
                    }
                }

                if (empty($prestationsForReceiver)) {
                    // Nothing to create for this receiver
                    continue;
                }

                // 1. Create request for the receiver themselves
                $remiseRequest = RemiseRequest::create([
                    'sender_id' => $data['sender_id'],
                    'receiver_id' => $receiverId,
                    'approver_id' => null, // No specific approver for receiver
                    'patient_id' => $data['patient_id'],
                    'message' => $data['message'] ?? null,
                    'status' => RemiseRequestStatusEnum::PENDING->value
                ]);

                $this->createPrestationsAndContributions($remiseRequest, $prestationsForReceiver);

                // Create notification for receiver
                $notification = RemiseRequestNotification::create([
                    'remise_request_id' => $remiseRequest->id,
                    'receiver_id' => $receiverId,
                    'type' => RemiseRequestNotification::TYPE_REQUEST,
                    'message' => $this->generateRequestMessage($remiseRequest, 'receiver'),
                    'is_read' => false
                ]);

                broadcast(new RemiseRequestCreated($remiseRequest, $notification))->toOthers();

                $created[] = [
                    'request' => $remiseRequest->load([
                        'sender', 'receiver', 'patient',
                        'prestations.contributions.user'
                    ]),
                    'notification' => $notification
                ];

                // 2. Create requests for each approved approver
                foreach ($approvers as $approverRelation) {
                    $approverId = $approverRelation->approver_id;
                    
                    // Skip if approver is the same as receiver (avoid duplicates)
                    if ($approverId === $receiverId) {
                        continue;
                    }

                    $approverRequest = RemiseRequest::create([
                        'sender_id' => $data['sender_id'],
                        'receiver_id' => $approverId,
                        'approver_id' => $approverId,
                        'patient_id' => $data['patient_id'],
                        'message' => $data['message'] ?? null,
                        'status' => RemiseRequestStatusEnum::PENDING->value
                    ]);

                    // Approvers get the same prestations as the receiver they're approving for
                    $this->createPrestationsAndContributions($approverRequest, $prestationsForReceiver);

                    $approverNotification = RemiseRequestNotification::create([
                        'remise_request_id' => $approverRequest->id,
                        'receiver_id' => $approverId,
                        'type' => RemiseRequestNotification::TYPE_REQUEST,
                        'message' => $this->generateRequestMessage($approverRequest, 'approver', $receiverId),
                        'is_read' => false
                    ]);

                    broadcast(new RemiseRequestCreated($approverRequest, $approverNotification))->toOthers();

                    $created[] = [
                        'request' => $approverRequest->load([
                            'sender', 'receiver', 'approver', 'patient',
                            'prestations.contributions.user'
                        ]),
                        'notification' => $approverNotification
                    ];
                }
            }

            return ['created' => $created];
        });
    }

    /**
     * Generate request notification message
     */
    private function generateRequestMessage(RemiseRequest $request, string $recipientType, ?int $originalReceiverId = null): string
    {
        $request->loadMissing(['patient', 'sender']);
        
        $patientName = $request->patient->name ?? 'Unknown Patient';
        $senderName = $request->sender->name ?? 'Unknown Sender';
        
        // Calculate total from prestations contributions
        $totalAmount = $request->prestations->sum(function($prestation) {
            return $prestation->contributions->sum('proposed_amount');
        });
        
        $baseMessage = "Remise request from {$senderName} for patient {$patientName}. Total contribution: " . number_format($totalAmount, 2) . " DZD.";
        
        if ($recipientType === 'approver') {
            if ($originalReceiverId) {
                $originalReceiver = User::find($originalReceiverId);
                $originalReceiverName = $originalReceiver->name ?? 'Unknown User';
                $baseMessage .= " You are designated as approver for {$originalReceiverName}'s contributions.";
            } else {
                $baseMessage .= " You are designated as approver for this request.";
            }
        }
        
        if ($request->message) {
            $baseMessage .= " Message: {$request->message}";
        }
        
        return $baseMessage;
    }

    /**
     * Check if user can approve a remise request
     */
    public function canUserApproveRequest(RemiseRequest $remiseRequest, ?int $userId = null): bool
    {
        $userId = $userId ?? Auth::id();
        
        // User can approve if they are:
        // 1. The receiver themselves
        if ($userId === $remiseRequest->receiver_id) {
            return true;
        }
        
        // 2. An approved approver for the receiver
        $isApprovedApprover = RemiseApprover::where('user_id', $remiseRequest->receiver_id)
            ->where('approver_id', $userId)
            ->where('is_approved', true)
            ->exists();
            
        return $isApprovedApprover;
    }

    /**
     * Return pending requests related to a user (as receiver or approved approver)
     */
    public function getPendingRequests(array $params = [], ?int $userId = null)
    {
        $userId = $userId ?? Auth::id();
        $status = $params['status'] ?? 'pending';
        $q = isset($params['q']) ? trim($params['q']) : null;
        $perPage = isset($params['per_page']) ? (int)$params['per_page'] : 20;
        $page = max(1, (int)($params['page'] ?? 1));

        $query = RemiseRequest::with([
            'patient',
            'sender',
            'receiver',
            'prestations.contributions.user'
        ])->where(function ($qBuilder) use ($userId) {
            $qBuilder->where('receiver_id', $userId)
                     ->orWhereHas('notifications', function ($n) use ($userId) {
                         $n->where('receiver_id', $userId);
                     });
        });

        if (!empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($q)) {
            $query->where(function ($w) use ($q) {
                $w->where('message', 'like', "%{$q}%")
                  ->orWhereHas('patient', function ($p) use ($q) {
                      $p->where('name', 'like', "%{$q}%");
                  })->orWhereHas('sender', function ($s) use ($q) {
                      $s->where('name', 'like', "%{$q}%");
                  })->orWhereHas('receiver', function ($r) use ($q) {
                      $r->where('name', 'like', "%{$q}%");
                  });
            });
        }

        $paginator = $query->orderByDesc('created_at')->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $paginator->items(),
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
        ];
    }

    /**
     * Approve remise request with authorization check
     */
    public function approveRequest(RemiseRequest $remiseRequest, array $data): array
    {
        // Check if user can approve this request
        if (!$this->canUserApproveRequest($remiseRequest)) {
            throw new \Exception('You are not authorized to approve this request.');
        }

        return DB::transaction(function () use ($remiseRequest, $data) {
            $userRole = $this->getUserRole($remiseRequest);

            // Update contribution amounts
            $this->updateContributionAmounts($remiseRequest, $data['contributions'] ?? null);

            // Update request status
            $remiseRequest->update(['status' => 'accepted']);

            // Create approval record
            $approval = RemiseRequestApproval::create([
                'remise_request_id' => $remiseRequest->id,
                'user_id' => Auth::id(),
                'role' => $userRole,
                'status' => 'accepted',
                'comment' => $data['comment'] ?? null,
                'acted_at' => now()
            ]);

            // Create notification for sender
            $notification = $this->createApprovalNotification($remiseRequest, true, $userRole);

            // Mark original notifications as read
            $this->markOriginalNotificationsAsRead($remiseRequest);

            // Broadcast approval notification
            event(new RemiseRequestApproved($remiseRequest));

            return [
                'request' => $remiseRequest->fresh([
                    'sender', 'receiver', 'patient',
                    'prestations.contributions.user'
                ]),
                'approval' => $approval,
                'notification' => $notification
            ];
        });
    }

    /**
     * Reject remise request with authorization check
     */
    public function rejectRequest(RemiseRequest $remiseRequest, array $data): array
    {
        // Check if user can reject this request
        if (!$this->canUserApproveRequest($remiseRequest)) {
            throw new \Exception('You are not authorized to reject this request.');
        }

        return DB::transaction(function () use ($remiseRequest, $data) {
            $userRole = $this->getUserRole($remiseRequest);

            // Update request status
            $remiseRequest->update(['status' => 'rejected']);

            // Create approval record
            $approval = RemiseRequestApproval::create([
                'remise_request_id' => $remiseRequest->id,
                'user_id' => Auth::id(),
                'role' => $userRole,
                'status' => 'rejected',
                'comment' => $data['comment'] ?? '',
                'acted_at' => now()
            ]);

            // Create notification for sender
            $notification = $this->createApprovalNotification($remiseRequest, false, $userRole);

            // Mark original notifications as read
            $this->markOriginalNotificationsAsRead($remiseRequest);

            // Broadcast rejection notification
            event(new RemiseRequestRejected($remiseRequest));

            return [
                'request' => $remiseRequest,
                'approval' => $approval,
                'notification' => $notification
            ];
        });
    }

    private function createPrestationsAndContributions(RemiseRequest $remiseRequest, array $prestations): void
    {
        foreach ($prestations as $prestationData) {
            $remisePrestation = RemiseRequestPrestation::create([
                'remise_request_id' => $remiseRequest->id,
                'prestation_id' => $prestationData['prestation_id'],
                'proposed_amount' => $prestationData['proposed_amount']
            ]);

            foreach ($prestationData['contributions'] as $contributionData) {
                RemiseRequestPrestationContribution::create([
                    'remise_request_prestation_id' => $remisePrestation->id,
                    'user_id' => $contributionData['user_id'],
                    'role' => $contributionData['role'],
                    'proposed_amount' => $contributionData['proposed_amount']
                ]);
            }
        }
    }
    public function getRequestSummary(RemiseRequest $remiseRequest): array
    {
        // ensure relationships are loaded
        $remiseRequest->loadMissing([
            'patient',
            'sender',
            'prestations.prestation',
            'prestations.contributions.user'
        ]);

        $patientName = $remiseRequest->patient->name ?? 'Unknown Patient';
        $senderName  = $remiseRequest->sender->name ?? 'Unknown Sender';

        $prestations = [];
        $prestationsTotal = 0;
        $contributionsTotal = 0;

        foreach ($remiseRequest->prestations as $p) {
            $prestationModel = $p->prestation;
            $prestationName = $prestationModel->name ?? 'Unknown';
            // prefer explicit prestation price, fall back to prestation record field or proposed_amount
            $prestationPrice = $prestationModel->price ?? $p->proposed_amount ?? 0;
            $prestationsTotal += (float) $prestationPrice;

            $contribs = [];
            foreach ($p->contributions as $c) {
                $proposed = (float) ($c->proposed_amount ?? $c->amount ?? 0);
                $approved = (float) ($c->approved_amount ?? 0);
                $contributionsTotal += $proposed;

                $contribs[] = [
                    'id' => $c->id,
                    'user_id' => $c->user_id,
                    'user_name' => $c->user->name ?? null,
                    'role' => $c->role,
                    'proposed_amount' => $proposed,
                    'approved_amount' => $approved,
                ];
            }

            $prestations[] = [
                'id' => $p->id,
                'prestation_id' => $p->prestation_id,
                'name' => $prestationName,
                'price' => (float) $prestationPrice,
                'proposed_amount' => (float) ($p->proposed_amount ?? 0),
                'contributions' => $contribs,
            ];
        }

        return [
            'request_id' => $remiseRequest->id,
            'patient_name' => $patientName,
            'sender_name' => $senderName,
            'message' => $remiseRequest->message,
            'prestations' => $prestations,
            'prestations_total' => round($prestationsTotal, 2),
            'contributions_total' => round($contributionsTotal, 2),
        ];
    }

    // Stub methods for controller compatibility
    public function getNotifications(array $params = [], ?int $userId = null)
    {
        $userId = $userId ?? Auth::id();
        $items = RemiseRequestNotification::with('remiseRequest')->where('receiver_id', $userId)->orderByDesc('created_at')->get();
        return ['items' => $items];
    }

    public function getRequestHistory(array $params = [], ?int $userId = null)
    {
        $userId = $userId ?? Auth::id();
        $items = RemiseRequest::with(['patient','sender','prestations.prestation','prestations.contributions.user'])
            ->where(function($q) use ($userId){
                $q->where('receiver_id', $userId)->orWhere('sender_id', $userId);
            })->whereIn('status', ['accepted','rejected','applied'])->orderByDesc('created_at')->get();
        return ['items' => $items];
    }

    public function markNotificationsRead(array $ids = [], ?int $userId = null)
    {
        $userId = $userId ?? Auth::id();
        RemiseRequestNotification::whereIn('id', $ids)->where('receiver_id', $userId)->update(['is_read' => true]);
        return ['marked' => count($ids)];
    }

    /**
     * Update contribution amounts with approved values
     */
    private function updateContributionAmounts(RemiseRequest $remiseRequest, ?array $contributions): void
    {
        if ($contributions) {
            foreach ($contributions as $contributionUpdate) {
                $contribution = RemiseRequestPrestationContribution::find($contributionUpdate['id']);
                if ($contribution) {
                    $contribution->update([
                        'final_amount' => $contributionUpdate['final_amount'] ?? $contribution->proposed_amount,
                        'approved_by' => Auth::id()
                    ]);
                }
            }
        }
    }

    /**
     * Create approval/rejection notification
     */
    private function createApprovalNotification(RemiseRequest $remiseRequest, bool $approved, string $userRole): RemiseRequestNotification
    {
        return RemiseRequestNotification::create([
            'remise_request_id' => $remiseRequest->id,
            'receiver_id' => $remiseRequest->sender_id,
            'type' => 'response',
            'message' => $this->generateApprovalMessage($remiseRequest, $approved, $userRole),
            'is_read' => false
        ]);
    }

    /**
     * Mark original notifications as read for current user
     */
    private function markOriginalNotificationsAsRead(RemiseRequest $remiseRequest): void
    {
        RemiseRequestNotification::where('remise_request_id', $remiseRequest->id)
            ->where('receiver_id', Auth::id())
            ->where('type', 'request')
            ->update(['is_read' => true]);
    }

    /**
     * Generate approval/rejection notification message
     */
    private function generateApprovalMessage(RemiseRequest $request, bool $approved, string $userRole): string
    {
        $request->loadMissing('patient');
        
        $status = $approved ? 'approved' : 'rejected';
        $patientName = $request->patient->name ?? 'Unknown Patient';
        $actorName = Auth::user()->name ?? 'Unknown User';
        $roleText = $userRole === 'receiver' ? 'receiver' : 'approver (backup)';
        
        return "Your remise request for patient {$patientName} has been {$status} by {$actorName} ({$roleText}).";
    }
}
