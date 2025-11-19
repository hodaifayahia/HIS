<?php

namespace App\Services\Purchsing\order;

use App\Models\ServiceDemendPurchcing;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class ServiceDemandListService
{
    /**
     * Get all service demands with filters and pagination
     * OPTIMIZED: Minimal eager loading, count aggregation, lazy item loading
     */
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        // Use minimal eager loading for list view - don't load items yet
        $query = ServiceDemendPurchcing::query()
            ->select([
                'id',
                'service_id',
                'created_by',
                'status',
                'demand_code',
                'notes',
                'is_pharmacy',
                'is_pharmacy_order',
                'created_at',
                'updated_at',
            ])
            // Only eager load relationships needed for list display
            ->with([
                'service:id,name',
                'creator:id,name',
            ])
            // Use withCount for item count (database-level aggregation - FAST)
            ->withCount('items');

        // Apply authorization filter
        $this->applyAuthorizationFilter($query);

        // Apply status filter
        $this->applyStatusFilter($query, $filters['status'] ?? null);

        // Apply service filter
        $this->applyServiceFilter($query, $filters['service_id'] ?? null);

        // Apply search filter
        $this->applySearchFilter($query, $filters['search'] ?? null);

        // Apply sorting
        $sortField = $filters['sort_field'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortField, $sortOrder);

        // Get paginated results
        $page = $filters['page'] ?? 1;
        $perPage = $filters['per_page'] ?? 15;

        $demands = $query->paginate($perPage, ['*'], 'page', $page);

        return $demands;
    }

    /**
     * Get demand with full details including items (use for detail/show pages)
     * Separate from list to avoid loading unnecessary items for list views
     */
    public function getWithItems(int $id): ?\App\Models\ServiceDemendPurchcing
    {
        return ServiceDemendPurchcing::query()
            ->where('id', $id)
            ->with([
                'service:id,name',
                'items' => function ($query) {
                    $query->select([
                        'id',
                        'service_demand_purchasing_id',
                        'product_id',
                        'pharmacy_product_id',
                        'quantity',
                        'unit_price',
                        'notes',
                    ]);
                },
                'items.product:id,name,code',
                'items.pharmacyProduct:id,name,sku',
                'creator:id,name',
            ])
            ->first();
    }

    /**
     * Apply authorization filter based on user role
     */
    private function applyAuthorizationFilter($query): void
    {
        $currentUser = Auth::user();

        if ($currentUser && ! in_array($currentUser->role, ['admin', 'SuperAdmin'])) {
            $query->where('created_by', $currentUser->id);
        }
    }

    /**
     * Apply status filter
     * If no specific status is provided, retrieve 'sent' and 'approved' statuses by default
     */
    private function applyStatusFilter($query, ?string $status): void
    {
        if ($status) {
            // If specific status is provided, filter by that status
            $query->where('status', $status);
        } else {
            // Default: retrieve 'sent' and 'approved' statuses
            $query->where(function ($q) {
                $q->where('status', 'sent')
                    ->orWhere('status', 'approved');
            });
        }
    }

    /**
     * Apply service filter
     */
    private function applyServiceFilter($query, ?int $serviceId): void
    {
        if ($serviceId) {
            $query->where('service_id', $serviceId);
        }
    }

    /**
     * Apply search filter
     */
    private function applySearchFilter($query, ?string $search): void
    {
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('demand_code', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('service', function ($serviceQuery) use ($search) {
                        $serviceQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }
    }

    /**
     * Get all available statuses
     */
    public function getAvailableStatuses(): array
    {
        return [
            'draft' => 'Draft',
            'sent' => 'Sent',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'factureprofram' => 'Facture Proforma',
            'boncommend' => 'Bon Commend',
            'received' => 'Received',
            'cancelled' => 'Cancelled',
        ];
    }
}
