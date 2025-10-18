<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\PharmacyMovement;
use App\Models\PharmacyMovementAuditLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PharmacyAuditController extends Controller
{
    /**
     * Display a listing of audit logs.
     */
    public function index(Request $request)
    {
        $query = PharmacyMovementAuditLog::with([
            'movement:id,movement_type,reference_number,status',
            'user:id,name,email',
        ]);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('movement', function ($movementQuery) use ($search) {
                        $movementQuery->where('reference_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by action type
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filter by movement type
        if ($request->filled('movement_type')) {
            $query->whereHas('movement', function ($movementQuery) use ($request) {
                $movementQuery->where('movement_type', $request->movement_type);
            });
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by severity level
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        // Order by latest first
        $query->orderBy('created_at', 'desc');

        $auditLogs = $query->paginate(20);

        // Get filter options
        $actions = PharmacyMovementAuditLog::distinct()->pluck('action')->filter()->sort()->values();
        $severities = PharmacyMovementAuditLog::distinct()->pluck('severity')->filter()->sort()->values();
        $movementTypes = PharmacyMovement::distinct()->pluck('movement_type')->filter()->sort()->values();

        return response()->json([
            'success' => true,
            'audit_logs' => $auditLogs,
            'filters' => [
                'actions' => $actions,
                'severities' => $severities,
                'movement_types' => $movementTypes,
            ],
        ]);
    }

    /**
     * Display the specified audit log.
     */
    public function show($id)
    {
        $auditLog = PharmacyMovementAuditLog::with([
            'movement:id,movement_type,reference_number,status,created_at,updated_at',
            'movement.items.product:id,name,sku',
            'movement.items.inventory:id,batch_number,serial_number,expiry_date',
            'user:id,name,email,created_at',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'audit_log' => $auditLog,
        ]);
    }

    /**
     * Get audit statistics and analytics.
     */
    public function getStatistics(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));

        // Basic statistics
        $totalLogs = PharmacyMovementAuditLog::whereBetween('created_at', [$dateFrom, $dateTo])->count();

        $logsByAction = PharmacyMovementAuditLog::whereBetween('created_at', [$dateFrom, $dateTo])
            ->select('action', DB::raw('count(*) as count'))
            ->groupBy('action')
            ->get();

        $logsBySeverity = PharmacyMovementAuditLog::whereBetween('created_at', [$dateFrom, $dateTo])
            ->select('severity', DB::raw('count(*) as count'))
            ->groupBy('severity')
            ->get();

        $logsByUser = PharmacyMovementAuditLog::with('user:id,name')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->select('user_id', DB::raw('count(*) as count'))
            ->groupBy('user_id')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        // Daily activity chart data
        $dailyActivity = PharmacyMovementAuditLog::whereBetween('created_at', [$dateFrom, $dateTo])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        // Movement type analysis
        $movementTypeActivity = PharmacyMovementAuditLog::with('movement:id,movement_type')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereHas('movement')
            ->get()
            ->groupBy('movement.movement_type')
            ->map(function ($logs, $type) {
                return [
                    'movement_type' => $type,
                    'count' => $logs->count(),
                    'actions' => $logs->groupBy('action')->map->count(),
                ];
            })
            ->values();

        // Recent critical activities
        $criticalActivities = PharmacyMovementAuditLog::with([
            'movement:id,movement_type,reference_number',
            'user:id,name',
        ])
            ->where('severity', 'critical')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'statistics' => [
                'total_logs' => $totalLogs,
                'logs_by_action' => $logsByAction,
                'logs_by_severity' => $logsBySeverity,
                'logs_by_user' => $logsByUser,
                'daily_activity' => $dailyActivity,
                'movement_type_activity' => $movementTypeActivity,
                'critical_activities' => $criticalActivities,
                'date_range' => [
                    'from' => $dateFrom,
                    'to' => $dateTo,
                ],
            ],
        ]);
    }

    /**
     * Get compliance report for regulatory purposes.
     */
    public function getComplianceReport(Request $request)
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'report_type' => 'in:controlled_substances,inventory_movements,user_activities,all',
        ]);

        $dateFrom = $request->date_from;
        $dateTo = $request->date_to;
        $reportType = $request->get('report_type', 'all');

        $report = [];

        if ($reportType === 'controlled_substances' || $reportType === 'all') {
            // Controlled substances tracking
            $controlledSubstanceMovements = PharmacyMovementAuditLog::with([
                'movement.items.product:id,name,sku,is_controlled_substance',
                'movement:id,movement_type,reference_number,created_at',
                'user:id,name',
            ])
                ->whereHas('movement.items.product', function ($query) {
                    $query->where('is_controlled_substance', true);
                })
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->orderBy('created_at')
                ->get();

            $report['controlled_substances'] = [
                'total_movements' => $controlledSubstanceMovements->count(),
                'movements' => $controlledSubstanceMovements,
                'summary_by_product' => $controlledSubstanceMovements
                    ->groupBy('movement.items.0.product.name')
                    ->map(function ($logs, $productName) {
                        return [
                            'product_name' => $productName,
                            'total_movements' => $logs->count(),
                            'movement_types' => $logs->groupBy('movement.movement_type')->map->count(),
                        ];
                    })
                    ->values(),
            ];
        }

        if ($reportType === 'inventory_movements' || $reportType === 'all') {
            // All inventory movements
            $inventoryMovements = PharmacyMovementAuditLog::with([
                'movement:id,movement_type,reference_number,status,created_at',
                'user:id,name',
            ])
                ->whereIn('action', ['movement_created', 'movement_approved', 'movement_rejected', 'movement_completed'])
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->orderBy('created_at')
                ->get();

            $report['inventory_movements'] = [
                'total_movements' => $inventoryMovements->count(),
                'movements_by_type' => $inventoryMovements->groupBy('movement.movement_type')->map->count(),
                'movements_by_status' => $inventoryMovements->groupBy('action')->map->count(),
                'movements' => $inventoryMovements,
            ];
        }

        if ($reportType === 'user_activities' || $reportType === 'all') {
            // User activity analysis
            $userActivities = PharmacyMovementAuditLog::with('user:id,name,email')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->get()
                ->groupBy('user_id')
                ->map(function ($logs, $userId) {
                    $user = $logs->first()->user;

                    return [
                        'user' => $user,
                        'total_activities' => $logs->count(),
                        'activities_by_action' => $logs->groupBy('action')->map->count(),
                        'activities_by_severity' => $logs->groupBy('severity')->map->count(),
                        'first_activity' => $logs->min('created_at'),
                        'last_activity' => $logs->max('created_at'),
                    ];
                })
                ->values();

            $report['user_activities'] = $userActivities;
        }

        // Generate report metadata
        $report['metadata'] = [
            'generated_at' => Carbon::now(),
            'generated_by' => Auth::user(),
            'date_range' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
            'report_type' => $reportType,
            'total_audit_logs' => PharmacyMovementAuditLog::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
        ];

        return response()->json([
            'success' => true,
            'compliance_report' => $report,
        ]);
    }

    /**
     * Export audit logs to CSV format.
     */
    public function exportLogs(Request $request)
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'format' => 'in:csv,json',
        ]);

        $dateFrom = $request->date_from;
        $dateTo = $request->date_to;
        $format = $request->get('format', 'csv');

        $logs = PharmacyMovementAuditLog::with([
            'movement:id,movement_type,reference_number',
            'user:id,name,email',
        ])
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->orderBy('created_at')
            ->get();

        if ($format === 'json') {
            return response()->json([
                'success' => true,
                'data' => $logs,
                'metadata' => [
                    'exported_at' => Carbon::now(),
                    'date_range' => ['from' => $dateFrom, 'to' => $dateTo],
                    'total_records' => $logs->count(),
                ],
            ]);
        }

        // CSV format
        $filename = "pharmacy_audit_logs_{$dateFrom}_to_{$dateTo}.csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'ID', 'Action', 'Description', 'Severity', 'Movement Type',
                'Reference Number', 'User Name', 'User Email', 'IP Address',
                'User Agent', 'Created At',
            ]);

            // CSV data
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->action,
                    $log->description,
                    $log->severity,
                    $log->movement->movement_type ?? 'N/A',
                    $log->movement->reference_number ?? 'N/A',
                    $log->user->name ?? 'N/A',
                    $log->user->email ?? 'N/A',
                    $log->ip_address,
                    $log->user_agent,
                    $log->created_at,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get audit trail for a specific movement.
     */
    public function getMovementAuditTrail($movementId)
    {
        $movement = PharmacyMovement::findOrFail($movementId);

        $auditTrail = PharmacyMovementAuditLog::with('user:id,name,email')
            ->where('movement_id', $movementId)
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'movement' => $movement,
            'audit_trail' => $auditTrail,
        ]);
    }

    /**
     * Get system integrity check results.
     */
    public function getIntegrityCheck()
    {
        $checks = [];

        // Check for orphaned audit logs
        $orphanedLogs = PharmacyMovementAuditLog::whereNotNull('movement_id')
            ->whereDoesntHave('movement')
            ->count();

        $checks['orphaned_audit_logs'] = [
            'status' => $orphanedLogs === 0 ? 'pass' : 'fail',
            'count' => $orphanedLogs,
            'description' => 'Audit logs without corresponding movements',
        ];

        // Check for missing audit logs for critical movements
        $criticalMovementsWithoutLogs = PharmacyMovement::whereIn('movement_type', ['transfer', 'adjustment', 'disposal'])
            ->whereDoesntHave('auditLogs')
            ->count();

        $checks['missing_critical_logs'] = [
            'status' => $criticalMovementsWithoutLogs === 0 ? 'pass' : 'warning',
            'count' => $criticalMovementsWithoutLogs,
            'description' => 'Critical movements without audit logs',
        ];

        // Check for recent activity
        $recentActivity = PharmacyMovementAuditLog::where('created_at', '>=', Carbon::now()->subHours(24))->count();

        $checks['recent_activity'] = [
            'status' => $recentActivity > 0 ? 'pass' : 'info',
            'count' => $recentActivity,
            'description' => 'Audit logs in the last 24 hours',
        ];

        $overallStatus = collect($checks)->every(function ($check) {
            return in_array($check['status'], ['pass', 'info']);
        }) ? 'healthy' : 'issues_detected';

        return response()->json([
            'success' => true,
            'integrity_check' => [
                'overall_status' => $overallStatus,
                'checked_at' => Carbon::now(),
                'checks' => $checks,
            ],
        ]);
    }
}
