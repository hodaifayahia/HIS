<?php
// app/Services/Coffre/CaisseService.php

namespace App\Services\Coffre;

use App\Models\Coffre\Caisse;
use App\Models\CONFIGURATION\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CaisseService
{
    public function getAllPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Caisse::with('service')->latest('id');

        // Apply filters
        if (!empty($filters['service_id'])) {
            $query->where('service_id', $filters['service_id']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        return $query->paginate($perPage);
    }

    public function findById(int $id): Caisse
    {
        return Caisse::with('service')->findOrFail($id);
    }

    public function create(array $data): Caisse
    {
        return DB::transaction(function () use ($data) {
            $caisse = Caisse::create($data);

            // $store = Cache::getStore();
            // if (method_exists($store, 'tags')) {
            //     Cache::tags(['caisses'])->flush();
            // } else {
            //     Cache::forget('caisses_all');
            //     Cache::forget('caisses_services_for_select');
            // }

            return $caisse->load('service');
        });
    }

    public function update(Caisse $caisse, array $data): Caisse
    {
        return DB::transaction(function () use ($caisse, $data) {
            $caisse->update($data);

            // $store = Cache::getStore();
            // if (method_exists($store, 'tags')) {
            //     Cache::tags(['caisses'])->flush();
            // } else {
            //     Cache::forget('caisses_all');
            //     Cache::forget('caisses_services_for_select');
            // }

            return $caisse->refresh()->load('service');
        });
    }

    public function delete(Caisse $caisse): void
    {
        DB::transaction(function () use ($caisse) {
        
          
        });
    }

    public function toggleStatus(Caisse $caisse): Caisse
    {
        return DB::transaction(function () use ($caisse) {
            $caisse->update(['is_active' => !$caisse->is_active]);
            
            // Cache::tags(['caisses'])->flush();
            
            return $caisse->refresh()->load('service');
        });
    }

    public function getServicesForSelect(): Collection
    {
        return Cache::remember('services_for_select', 300, function () {
            return Service::select('id', 'name')
                         ->orderBy('name')
                         ->get();
        });
    }

    public function getActiveByService(int $serviceId): Collection
    {
        return Cache::remember("caisses_service_{$serviceId}", 300, function () use ($serviceId) {
            return Caisse::active()
                         ->byService($serviceId)
                         ->select('id', 'name', 'location')
                         ->orderBy('name')
                         ->get();
        });
    }

    public function getCaisseStats(): array
    {
        return Cache::remember('caisse_stats', 300, function () {
            return [
                'total' => Caisse::count(),
                'active' => Caisse::active()->count(),
                'inactive' => Caisse::inactive()->count(),
                'by_service' => Caisse::with('service')
                                    ->select('service_id', DB::raw('count(*) as count'))
                                    ->groupBy('service_id')
                                    ->get()
                                    ->map(function ($item) {
                                        return [
                                            'service_name' => $item->service?->name ?? 'Unknown',
                                            'count' => $item->count
                                        ];
                                    })
            ];
        });
    }
}
