<?php

namespace App\Http\Controllers\B2B;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\B2B\Convention; // Assuming Convention is your contract model
use App\Models\CRM\Organisme; // Assuming Organisme is your company model
use App\Models\CONFIGURATION\Service; // You'll need a Service model for medical specialties
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ConvenctionDashborad extends Controller
{
    public function getDashboardData()
    {
        // Total Companies
        $totalCompanies = Organisme::count();

        // Contract Status Counts
        $activeContracts = Convention::where('status', 'active')->count();
        $pendingContracts = Convention::where('status', 'pending')->count();
        $expiredContracts = Convention::where('status', 'expired')->count();

        // Recent Contracts
        $recentContracts = Convention::with('organisme')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($contract) {
                return [
                    'contract_name' => $contract->name,
                    'company_name' => $contract->organisme->name, // Assuming 'name' is the company name field
                    'status' => $contract->status,
                    'created_at' => $contract->created_at,
                ];
            });

        // Top Companies (by contract count)
        $topCompanies = Organisme::withCount('conventions') // Assuming 'conventions' is the relationship name in Organisme model
            ->orderBy('conventions_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($company) {
                return [
                    'id' => $company->id,
                    'company_name' => $company->name, // Assuming 'name' is the company name
                    'is_public' => $company->is_public ?? 'no', // Assuming 'is_public' field exists, default to 'no'
                    'contract_count' => $company->conventions_count,
                ];
            });

        // Monthly Contract Trends
        $monthlyContractData = Convention::select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw("count(*) as count")
            )
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->limit(6) // Get data for the last 6 months, adjust as needed
            ->get();

        // Medical Service Statistics
        // This assumes you have a 'specialties' table and conventions can be linked to them.
        // You might need to adjust this based on how specialties are linked to contracts/companies.
        $ServiceStats = Service::withCount(['annexes', 'prestations']) // Assuming annexes and prestations are related to specialties
            ->get()
            ->map(function ($Service) {
                return [
                    'Service_name' => $Service->name, // Assuming 'name' is the Service name
                    'description' => $Service->description ?? 'N/A', // Assuming a 'description' field
                    'annex_count' => $Service->annexes_count,
                    'prestation_count' => $Service->prestations_count,
                ];
            });


        return response()->json([
            'totalCompanies' => $totalCompanies,
            'activeContracts' => $activeContracts,
            'pendingContracts' => $pendingContracts,
            'expiredContracts' => $expiredContracts,
            'recentContracts' => $recentContracts,
            'topCompanies' => $topCompanies,
            'monthlyContractData' => $monthlyContractData,
            'ServiceStats' => $ServiceStats,
        ]);
    }
}