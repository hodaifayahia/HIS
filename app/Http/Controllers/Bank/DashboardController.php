<?php
// app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers\Bank;


 
use App\Models\Bank\Bank;
use App\Http\Controllers\Controller;

use App\Models\Bank\BankAccount;
use App\Models\Bank\BankAccountTransaction;
use App\Services\Bank\BankService;
use App\Services\Bank\BankAccountService;
use App\Services\Bank\BankAccountTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected BankService $bankService;
    protected BankAccountService $bankAccountService;
    protected BankAccountTransactionService $transactionService;

    public function __construct(
        BankService $bankService,
        BankAccountService $bankAccountService,
        BankAccountTransactionService $transactionService
    ) {
        $this->bankService = $bankService;
        $this->bankAccountService = $bankAccountService;
        $this->transactionService = $transactionService;
    }

    /**
     * Get comprehensive dashboard data
     */
    public function index(Request $request): JsonResponse
    {
        $dateFrom = $request->get('date_from', Carbon::now()->subDays(30)->toDateString());
        $dateTo = $request->get('date_to', Carbon::now()->toDateString());

        // Get KPIs
        $totalBalance = BankAccount::active()->sum('current_balance');
        $activeAccounts = BankAccount::active()->count();
        $totalAccounts = BankAccount::count();
        $activeBanks = Bank::active()->count();
        $totalBanks = Bank::count();

        // Monthly transactions
        $monthlyTransactions = BankAccountTransaction::whereBetween('transaction_date', [
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth()
        ])->count();

        // Transaction trend
        $lastMonthTransactions = BankAccountTransaction::whereBetween('transaction_date', [
            Carbon::now()->subMonth()->startOfMonth(),
            Carbon::now()->subMonth()->endOfMonth()
        ])->count();

        $transactionTrend = $lastMonthTransactions > 0 
            ? round((($monthlyTransactions - $lastMonthTransactions) / $lastMonthTransactions) * 100, 1)
            : 0;

        // Balance by currency
        $balanceByCurrency = BankAccount::active()
            ->selectRaw('currency, SUM(current_balance) as total_balance, COUNT(*) as account_count')
            ->groupBy('currency')
            ->get()
            ->map(function ($item) {
                return [
                    'code' => $item->currency,
                    'amount' => $item->total_balance,
                    'accounts' => $item->account_count
                ];
            });

        // Accounts by bank
        $accountsByBank = DB::table('bank_accounts')
            ->join('banks', 'bank_accounts.bank_id', '=', 'banks.id')
            ->selectRaw('banks.name, COUNT(*) as account_count')
            ->where('bank_accounts.is_active', true)
            ->groupBy('banks.id', 'banks.name')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->name,
                    'accounts' => $item->account_count
                ];
            });

        // Recent transactions
        $recentTransactions = BankAccountTransaction::with(['bankAccount.bank', 'acceptedBy'])
            ->latest('transaction_date')
            ->take(10)
            ->get();

        // Account summaries
        $accountSummaries = BankAccount::with('bank')
            ->active()
            ->orderBy('current_balance', 'desc')
            ->take(8)
            ->get();

        // Transactions by status
        $transactionsByStatus = BankAccountTransaction::selectRaw('status, COUNT(*) as count, SUM(amount) as total_amount')
            ->whereBetween('transaction_date', [$dateFrom, $dateTo])
            ->groupBy('status')
            ->get()
            ->map(function ($item) {
                return [
                    'status' => $item->status,
                    'count' => $item->count,
                    'total_amount' => $item->total_amount
                ];
            });

        // Balance history (last 30 days)
        $balanceHistory = $this->getBalanceHistory($dateFrom, $dateTo);

        // Transaction trends
        $transactionTrends = $this->getTransactionTrends($dateFrom, $dateTo);

        // Calculate balance change
        $balanceChange = $this->calculateBalanceChange();

        return response()->json([
            'data' => [
                'totalBalance' => $totalBalance,
                'balanceChange' => $balanceChange,
                'activeAccounts' => $activeAccounts,
                'totalAccounts' => $totalAccounts,
                'monthlyTransactions' => $monthlyTransactions,
                'transactionTrend' => $transactionTrend,
                'activeBanks' => $activeBanks,
                'totalBanks' => $totalBanks,
                'totalTransactions' => BankAccountTransaction::count(),
                'balanceByCurrency' => $balanceByCurrency,
                'accountsByBank' => $accountsByBank,
                'recentTransactions' => $recentTransactions,
                'accountSummaries' => $accountSummaries,
                'transactionsByStatus' => $transactionsByStatus,
                'balanceHistory' => $balanceHistory,
                'transactionTrends' => $transactionTrends,
            ]
        ]);
    }

    /**
     * Get system health information
     */
    public function systemHealth(): JsonResponse
    {
        // Calculate system health metrics
        $activeConnections = BankAccount::active()->count();
        $lastSync = BankAccountTransaction::latest('created_at')->first()?->created_at ?? now();
        
        // Calculate error rate (failed transactions in last 24h)
        $totalTransactions = BankAccountTransaction::where('created_at', '>=', Carbon::now()->subDay())->count();
        $failedTransactions = BankAccountTransaction::where('status', 'cancelled')
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->count();
        
        $errorRate = $totalTransactions > 0 ? round(($failedTransactions / $totalTransactions) * 100, 1) : 0;
        
        $status = 'Healthy';
        if ($errorRate > 10) {
            $status = 'Error';
        } elseif ($errorRate > 5) {
            $status = 'Warning';
        }

        return response()->json([
            'data' => [
                'status' => $status,
                'activeConnections' => $activeConnections,
                'lastSync' => $lastSync,
                'errorRate' => $errorRate,
            ]
        ]);
    }

    /**
     * Export dashboard data
     */
    public function exportDashboard(Request $request): JsonResponse
    {
        // Implementation for PDF/Excel export
        // This would typically generate a report file
        
        return response()->json([
            'success' => true,
            'message' => 'Dashboard exported successfully'
        ]);
    }

    /**
     * Get balance history for chart
     */
    private function getBalanceHistory(string $dateFrom, string $dateTo): array
    {
        $startDate = Carbon::parse($dateFrom);
        $endDate = Carbon::parse($dateTo);
        $dates = [];
        
        // Generate date range
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $balance = BankAccount::active()
                ->where('created_at', '<=', $currentDate->endOfDay())
                ->sum('current_balance');
                
            $dates[] = [
                'date' => $currentDate->format('Y-m-d'),
                'balance' => $balance
            ];
            
            $currentDate->addDay();
        }

        return $dates;
    }

    /**
     * Get transaction trends for chart
     */
    private function getTransactionTrends(string $dateFrom, string $dateTo): array
    {
        return DB::table('bank_account_transactions')
            ->selectRaw('DATE(transaction_date) as date')
            ->selectRaw('SUM(CASE WHEN transaction_type = "credit" THEN amount ELSE 0 END) as credits')
            ->selectRaw('SUM(CASE WHEN transaction_type = "debit" THEN amount ELSE 0 END) as debits')
            ->selectRaw('COUNT(CASE WHEN transaction_type = "credit" THEN 1 END) as credit_count')
            ->selectRaw('COUNT(CASE WHEN transaction_type = "debit" THEN 1 END) as debit_count')
            ->whereBetween('transaction_date', [$dateFrom, $dateTo])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * Calculate balance change from last month
     */
    private function calculateBalanceChange(): float
    {
        $currentBalance = BankAccount::active()->sum('current_balance');
        
        // Get last month's transactions to calculate previous balance
        $lastMonthTransactions = BankAccountTransaction::where('status', 'completed')
            ->where('transaction_date', '>=', Carbon::now()->subMonth())
            ->sum(DB::raw('CASE WHEN transaction_type = "credit" THEN amount ELSE -amount END'));

        return $lastMonthTransactions;
    }
}
