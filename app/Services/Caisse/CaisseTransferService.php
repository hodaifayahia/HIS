<?php

// app/Services/Caisse/CaisseTransferService.php

namespace App\Services\Caisse;

use App\Models\Caisse\CaisseSession;
use App\Models\Caisse\CaisseTransfer;
use App\Models\Coffre\Caisse;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CaisseTransferService
{
    public function getAllPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = CaisseTransfer::with(['caisse', 'fromUser', 'toUser'])
            ->latest('created_at');

        // Apply filters
        if (! empty($filters['caisse_id'])) {
            $query->byCaisse($filters['caisse_id']);
        }

        if (! empty($filters['caisse_session_id'])) {
            $query->where('caisse_session_id', $filters['caisse_session_id']);
        }

        if (! empty($filters['from_user_id'])) {
            $query->where('from_user_id', $filters['from_user_id']);
        }

        if (! empty($filters['to_user_id'])) {
            $query->where('to_user_id', $filters['to_user_id']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['user_id'])) {
            $query->forUser($filters['user_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }

    public function findById(int $id): CaisseTransfer
    {
        return CaisseTransfer::with(['caisse', 'fromUser', 'toUser'])->findOrFail($id);
    }

    public function create(array $data): CaisseTransfer
    {

        return DB::transaction(function () use ($data) {

            $data['from_user_id'] = auth()->id();
            // Mark previous transfers (from same user and caisse) as done
            if ((! empty($data['caisse_id']) && ! empty($data['caisse_session_id']))) {
                CaisseTransfer::where('caisse_id', $data['caisse_id'])
                    ->where('caisse_session_id', $data['caisse_session_id'])
                    ->where('status', '<>', 'done')
                    ->update(['status' => 'done']);
            }

            CaisseSession::where('id', $data['caisse_session_id'])
                ->update(['is_transfer' => 1]);
            // Create new transfer
            $transfer = CaisseTransfer::create($data);

            // Generate token for pending transfers
            try {
                if ($transfer->status === 'pending') {
                    $transfer->generateToken();
                }
            } catch (\Exception $ex) {
                // log and continue
                \Log::error('Failed to generate transfer token', ['error' => $ex->getMessage(), 'transfer_id' => $transfer->id ?? null]);
            }

            // Dispatch event after commit so observers can update sessions
            DB::afterCommit(function () use ($transfer) {
                event(new \App\Events\CaisseTransferCreated($transfer));
            });

            return $transfer->load(['caisse', 'fromUser', 'toUser']);
        });
    }

    public function acceptTransfer(CaisseTransfer $transfer, string $token, ?float $amountReceived = null): CaisseTransfer
    {
        return DB::transaction(function () use ($transfer, $amountReceived) {

            // store received amount (use sent amount as fallback)
            if ($amountReceived !== null) {
                $transfer->amount_received = $amountReceived;
            } elseif ($transfer->amount_received === null) {
                $transfer->amount_received = $transfer->amount_sended;
            }

            // persist amount before accepting to keep audit trail
            $transfer->save();

            if (! $transfer->accept()) {
                throw new \Exception('Cannot accept this transfer.');
            }

            return $transfer->refresh()->load(['caisse', 'fromUser', 'toUser']);
        });
    }

    public function rejectTransfer(CaisseTransfer $transfer): CaisseTransfer
    {
        return DB::transaction(function () use ($transfer) {
            if (! $transfer->reject()) {
                throw new \Exception('Cannot reject this transfer.');
            }

            return $transfer->refresh();
        });
    }

    public function expireOldTransfers(): int
    {
        $expiredCount = 0;

        CaisseTransfer::pending()
            ->where('token_expires_at', '<', Carbon::now())
            ->chunk(100, function ($transfers) use (&$expiredCount) {
                foreach ($transfers as $transfer) {
                    if ($transfer->expire()) {
                        $expiredCount++;
                    }
                }
            });

        return $expiredCount;
    }

    public function getTransferStats(?int $caisseId = null, ?int $userId = null): array
    {
        $query = CaisseTransfer::query();

        if ($caisseId) {
            $query->byCaisse($caisseId);
        }

        if ($userId) {
            $query->forUser($userId);
        }

        $stats = [
            'total_transfers' => $query->count(),
            'pending_transfers' => $query->pending()->count(),
            'accepted_transfers' => $query->accepted()->count(),
            'rejected_transfers' => $query->rejected()->count(),
            'expired_transfers' => $query->expired()->count(),
            'total_amount_transferred' => $query->accepted()->sum('amount'),
            'pending_amount' => $query->pending()->sum('amount'),
        ];

        return $stats;
    }

    public function getUserTransfers(int $userId, string $type = 'all'): Collection
    {
        $query = CaisseTransfer::with(['caisse', 'fromUser', 'toUser']);

        switch ($type) {
            case 'sent':
                $query->where('from_user_id', $userId);
                break;
            case 'received':
                $query->where('to_user_id', $userId);
                break;
            default:
                $query->forUser($userId);
        }

        return $query->latest('created_at')->get();
    }

    public function getTransferByToken(string $token): ?CaisseTransfer
    {
        return CaisseTransfer::with(['caisse', 'fromUser', 'toUser'])
            ->where('transfer_token', $token)
            ->first();
    }

    public function delete(CaisseTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            if ($transfer->status === 'accepted') {
                throw new \Exception('Cannot delete accepted transfer.');
            }

            $transfer->delete();
        });
    }
}
