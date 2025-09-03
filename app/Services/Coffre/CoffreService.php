<?php

namespace App\Services\Coffre;

use App\Models\Coffre\Coffre;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class CoffreService
{
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return Coffre::with('user:id,name,email')
                     ->latest('id')
                     ->paginate($perPage);
    }

    public function findById(int $id): Coffre
    {
        return Coffre::with('user:id,name,email')->findOrFail($id);
    }

    public function create(array $data): Coffre
    {
        return DB::transaction(function () use ($data) {
            $coffre = Coffre::create($data);
            
            // Clear cache: if store supports tags use them, otherwise remove specific keys
            $store = Cache::getStore();
            if (method_exists($store, 'tags')) {
                Cache::tags(['coffres'])->flush();
            } else {
                Cache::forget('coffres_all');
                Cache::forget('coffres_users_for_select');
            }
            
            return $coffre->load('user:id,name,email');
        });
    }

    public function update(Coffre $coffre, array $data): Coffre
    {
        return DB::transaction(function () use ($coffre, $data) {
            $coffre->update($data);
            
            $store = Cache::getStore();
            if (method_exists($store, 'tags')) {
                Cache::tags(['coffres'])->flush();
            } else {
                Cache::forget('coffres_all');
                Cache::forget('coffres_users_for_select');
            }
            
            return $coffre->refresh()->load('user:id,name,email');
        });
    }

    public function delete(Coffre $coffre): void
    {
        DB::transaction(function () use ($coffre) {
            $coffre->delete();
            $store = Cache::getStore();
            if (method_exists($store, 'tags')) {
                Cache::tags(['coffres'])->flush();
            } else {
                Cache::forget('coffres_all');
                Cache::forget('coffres_users_for_select');
            }
        });
    }

    public function getUsersForSelect(): Collection
    {
        return Cache::remember('coffres_users_for_select', 300, function () {
            return User::select('id', 'name', 'email')
                       ->orderBy('name')
                       ->get();
        });
    }

    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return Coffre::with('user:id,name,email')
                     ->where('name', 'LIKE', "%{$query}%")
                     ->orWhere('location', 'LIKE', "%{$query}%")
                     ->orWhereHas('user', function ($q) use ($query) {
                         $q->where('name', 'LIKE', "%{$query}%");
                     })
                     ->latest('id')
                     ->paginate($perPage);
    }
}
