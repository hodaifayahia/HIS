<?php
// app/Services/Coffre/CaisseSessionService.php

namespace App\Services\Coffre;

use App\Models\Coffre\Caisse;
use App\Models\Coffre\Coffre;
use App\Models\Coffre\CaisseSession;
use App\Models\Coffre\CaisseSessionDenomination;
use App\Models\caisse\CaisseTransfer;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CaisseSessionService
{
    public function getAllPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = CaisseSession::with([
            'caisse', 
            'user', 
            'openedBy', 
            'closedBy', 
            'sourceCoffre', 
            'destinationCoffre'
        ])->latest('ouverture_at');

        // Apply filters
        if (!empty($filters['caisse_id'])) {
            $query->where('caisse_id', $filters['caisse_id']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['open_by'])) {
            $query->where('open_by', $filters['open_by']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('ouverture_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('ouverture_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('opening_notes', 'like', "%{$filters['search']}%")
                  ->orWhere('closing_notes', 'like', "%{$filters['search']}%")
                  ->orWhereHas('caisse', function ($caisseQuery) use ($filters) {
                      $caisseQuery->where('name', 'like', "%{$filters['search']}%");
                  })
                  ->orWhereHas('user', function ($userQuery) use ($filters) {
                      $userQuery->where('name', 'like', "%{$filters['search']}%");
                  })
                  ->orWhereHas('openedBy', function ($userQuery) use ($filters) {
                      $userQuery->where('name', 'like', "%{$filters['search']}%");
                  });
            });
        }

        return $query->paginate($perPage);
    }

    public function findById(int $id): CaisseSession
    {
        return CaisseSession::with([
            'caisse', 
            'user', 
            'openedBy', 
            'closedBy', 
            'sourceCoffre', 
            'destinationCoffre',
            'denominations'
        ])->findOrFail($id);
    }

    public function openSession(array $data): CaisseSession
    {
        return DB::transaction(function () use ($data) {
            $currentUser = Auth::user();

            // use provided user_id if present, otherwise fall back to authenticated user id
            $userId = $data['user_id'] ;

            // Prevent the same user from having more than one open session
            $userHasOpen = CaisseSession::open()
                ->where('user_id', $userId)
                ->exists();

            if ($userHasOpen) {
                throw new \Exception('This user already has an open caisse session.');
            }

            // Check if caisse already has an open session
            $existingSession = CaisseSession::open()
                                          ->where('caisse_id', $data['caisse_id'])
                                          ->first();
            
            if ($existingSession) {
                throw new \Exception('This cash register already has an open session.');
            }

            // Validate caisse is active
            $caisse = Caisse::findOrFail($data['caisse_id']);
            if (!$caisse->is_active) {
                throw new \Exception('Cannot open session on inactive cash register.');
            }

            $session = CaisseSession::create([
                'caisse_id' => $data['caisse_id'],
                'user_id' => $userId,
                'open_by' => $currentUser->id,
                'coffre_id_source' => $data['coffre_id_source'] ?? null,
                'ouverture_at' => now(),
                'opening_amount' => $data['opening_amount'] ?? 0,
                'status' => 'open',
                'opening_notes' => $data['opening_notes'] ?? null,
            ]);

            // If source coffre specified, create withdrawal transaction
            if (!empty($data['coffre_id_source']) && ($session->opening_amount > 0)) {
                $this->createCoffreTransaction(
                    $data['coffre_id_source'],
                    'withdrawal',
                    $session->opening_amount,
                    "Cash withdrawal for session opening - {$caisse->name}",
                    $currentUser->id,
                    $session->id
                );
            }

            $store = Cache::getStore();
            if (method_exists($store, 'tags')) {
                Cache::tags(['caisse_sessions'])->flush();
            } else {
                Cache::forget('caisse_sessions_all');
            }
            
            return $session->load([
                'caisse', 
                'user', 
                'openedBy', 
                'sourceCoffre'
            ]);
        });
    }

    public function closeSessionWithDenominations(CaisseSession $session, array $data): CaisseSession
    {
        return DB::transaction(function () use ($session, $data) {
            $currentUser = Auth::user();
            
            if (!$session->canBeClosed()) {
                throw new \Exception('This session cannot be closed.');
            }

            // Calculate total from denominations
            $totalCashCounted = 0;
            if (!empty($data['denominations'])) {
                foreach ($data['denominations'] as $denomination) {
                    if ($denomination['quantity'] > 0) {
                        $totalCashCounted += $denomination['value'] * $denomination['quantity'];
                    }
                }
            }
            //TODO
            // Validate closing amount
            if ($data['closing_amount'] < 0) {
                throw new \Exception('Closing amount cannot be negative.');
            }
$data['closing_amount'] = $totalCashCounted;
            // Update session
            $session->update([
                'cloture_at' => now(),
                'closed_by' => $currentUser->id,
                'closing_amount' => $data['closing_amount'],
                'expected_closing_amount' => $data['expected_closing_amount'] ?? null,
                'total_cash_counted' => $totalCashCounted,
                'cash_difference' => $data['closing_amount'] - $totalCashCounted,
                'coffre_id_destination' => $data['coffre_id_destination'] ?? null,
                'status' => 'closed',
                'closing_notes' => $data['closing_notes'] ?? null,
             ]);

            // Save denominations
            if (!empty($data['denominations'])) {
                foreach ($data['denominations'] as $denomination) {
                    if ($denomination['quantity'] > 0) {
                        CaisseSessionDenomination::create([
                            'caisse_session_id' => $session->id,
                            'denomination_type' => $denomination['type'],
                            'value' => $denomination['value'],
                            'quantity' => $denomination['quantity'],
                            'total_amount' => $denomination['value'] * $denomination['quantity'],
                        ]);
                    }
                }
            }

            // Transfer cash to destination coffre if specified
            if (!empty($data['coffre_id_destination']) && $totalCashCounted > 0) {
                $this->createCoffreTransaction(
                    $data['coffre_id_destination'],
                    'deposit',
                    $totalCashCounted,
                    "Cash deposit from session closing - {$session->caisse->name}",
                    $currentUser->id,
                    $session->id
                );
            }

            $store = Cache::getStore();
            if (method_exists($store, 'tags')) {
                Cache::tags(['caisse_sessions', 'coffres'])->flush();
            } else {
                Cache::forget('caisse_sessions_all');
                Cache::forget('coffres_all');
            }
            
            return $session->refresh()->load([
                'caisse', 
                'user', 
                'openedBy', 
                'closedBy', 
                'sourceCoffre', 
                'destinationCoffre', 
                'denominations'
            ]);
        });
    }

    public function suspendSession(CaisseSession $session): CaisseSession
    {
        return DB::transaction(function () use ($session) {
            if ($session->status !== 'open') {
                throw new \Exception('Only open sessions can be suspended.');
            }

            $session->update(['status' => 'suspended']);
            
            $store = Cache::getStore();
            if (method_exists($store, 'tags')) {
                Cache::tags(['caisse_sessions'])->flush();
            } else {
                Cache::forget('caisse_sessions_all');
            }
            
            return $session->refresh();
        });
    }

    public function resumeSession(CaisseSession $session): CaisseSession
    {
        return DB::transaction(function () use ($session) {
            if ($session->status !== 'suspended') {
                throw new \Exception('Only suspended sessions can be resumed.');
            }

            $session->update(['status' => 'open']);
            
            $store = Cache::getStore();
            if (method_exists($store, 'tags')) {
                Cache::tags(['caisse_sessions'])->flush();
            } else {
                Cache::forget('caisse_sessions_all');
            }
            
            return $session->refresh();
        });
    }

    public function delete(CaisseSession $session): void
    {
        if ($session->status === 'open') {
            throw new \Exception('Cannot delete an open session. Please close it first.');
        }

        DB::transaction(function () use ($session) {
            // Delete associated denominations
            $session->denominations()->delete();
            
            $session->delete();
            $store = Cache::getStore();
            if (method_exists($store, 'tags')) {
                Cache::tags(['caisse_sessions'])->flush();
            } else {
                Cache::forget('caisse_sessions_all');
            }
        });
    }

    public function getActiveSessions(): Collection
    {
        return CaisseSession::active()
                           ->with([
                               'caisse', 
                               'user', 
                               'openedBy', 
                               'sourceCoffre'
                           ])
                           ->orderBy('ouverture_at', 'desc')
                           ->get();
    }

    public function getCaissesForSelect(): Collection
    {
        return Cache::remember('active_caisses_for_sessions', 300, function () {
            return Caisse::active()
                         ->with('service')
                         ->select('id', 'name', 'location', 'service_id', 'is_active')
                         ->orderBy('name')
                         ->get();
        });
    }

    public function getCoffresForSelect(): Collection
    {
        return Cache::remember('coffres_for_sessions', 300, function () {
            return Coffre::select('id', 'name', 'location', 'current_balance')
                         ->orderBy('name')
                         ->get();
        });
    }

    public function getUsersForSelect(): Collection
    {
        return Cache::remember('users_for_sessions', 300, function () {
            return User::select('id', 'name', 'email')
                       ->orderBy('name')
                       ->get();
        });
    }

    public function getSessionStats(): array
    {
        return Cache::remember('session_stats', 300, function () {
            return [
                'total_sessions' => CaisseSession::count(),
                'open_sessions' => CaisseSession::open()->count(),
                'closed_today' => CaisseSession::closed()
                                               ->whereDate('cloture_at', today())
                                               ->count(),
                'suspended_sessions' => CaisseSession::where('status', 'suspended')->count(),
                'average_session_duration' => $this->getAverageSessionDuration(),
                'total_variance_today' => $this->getTotalVarianceToday(),
                'total_cash_handled_today' => $this->getTotalCashHandledToday(),
            ];
        });
    }
    public function getUserSessions(): array
    {
        $userId = Auth::id();
        
        // Get active sessions
        $activeSessions = CaisseSession::where('user_id', $userId)
                           ->where('status', 'open')
                           ->with(['caisse', 'user', 'openedBy', 'sourceCoffre'])
                           ->orderBy('ouverture_at', 'desc')
                           ->get();

        // Get transferred sessions
        $transferredSessions = CaisseTransfer::where('to_user_id', $userId)
                           ->where('status', 'transferred')
                           ->whereDate('created_at', today())
                           ->with(['caisse', 'fromUser', 'toUser'])
                           ->orderBy('created_at', 'desc')
                           ->get();
        // Return both collections separately
        return [
            'sessions' => $activeSessions,
            'transfers' => $transferredSessions,
            'has_active_session' => $activeSessions->isNotEmpty(),
            'has_transfers' => $transferredSessions->isNotEmpty(),
            'active_count' => $activeSessions->count(),
            'transfer_count' => $transferredSessions->count()
        ];
    }

    public function getStandardDenominations(): array
    {
        return CaisseSessionDenomination::getStandardDenominations();
    }

    private function passSessionToUser(int $sessionId, int $userId): void
    {
        $session = CaisseSession::findOrFail($sessionId);
        $session->user_id = $userId;
        $session->save();
    }

    private function createCoffreTransaction(
        int $coffreId, 
        string $type, 
        float $amount, 
        string $description, 
        int $userId, 
        int $sessionId
    ): void {
        $coffre = Coffre::findOrFail($coffreId);
        
        // Update coffre balance
        if ($type === 'deposit') {
            $coffre->increment('current_balance', $amount);
        } else {
            $coffre->decrement('current_balance', $amount);
        }
        
        // Create transaction record if CoffreTransaction model exists
        if (class_exists(\App\Models\Coffre\CoffreTransaction::class)) {
            \App\Models\Coffre\CoffreTransaction::create([
                'coffre_id' => $coffreId,
                'user_id' => $userId,
                'transaction_type' => $type,
                'amount' => $amount,
                'description' => $description,
                'source_caisse_session_id' => $sessionId,
            ]);
        }
    }

    private function getAverageSessionDuration(): ?float
    {
        $sessions = CaisseSession::closed()
                                 ->whereNotNull('cloture_at')
                                 ->whereDate('cloture_at', '>=', now()->subDays(30))
                                 ->get();

        if ($sessions->isEmpty()) return null;

        $totalMinutes = $sessions->sum(function ($session) {
            return $session->duration_in_minutes;
        });

        return $totalMinutes / $sessions->count();
    }

    private function getTotalVarianceToday(): float
    {
        return CaisseSession::closed()
                           ->whereDate('cloture_at', today())
                           ->whereNotNull('cash_difference')
                           ->sum('cash_difference');
    }

    private function getTotalCashHandledToday(): float
    {
        return CaisseSession::whereDate('ouverture_at', today())
                           ->sum('opening_amount') +
               CaisseSession::closed()
                           ->whereDate('cloture_at', today())
                           ->whereNotNull('closing_amount')
                           ->sum('closing_amount');
    }
}
