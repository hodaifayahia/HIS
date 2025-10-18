<?php

namespace App\Services;

use App\Models\FactureProforma;
use App\Models\FactureProformaProduct;
use App\Models\ServiceDemendPurchcing;
use App\Models\Fournisseur;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class FactureProformaService
{
    /**
     * Create a new facture proforma.
     */
    public function create(array $data): FactureProforma
    {
        return DB::transaction(function () use ($data) {
            $facture = FactureProforma::create([
                'fournisseur_id' => $data['fournisseur_id'],
                'service_demand_purchasing_id' => $data['service_demand_purchasing_id'] ?? null,
                'created_by' => Auth::id(),
                'status' => 'draft'
            ]);

            if (!empty($data['products'])) {
                $this->addProductsToFacture($facture, $data['products']);
            }

            return $facture->load(['fournisseur', 'serviceDemand.service', 'creator', 'products.product']);
        });
    }

    /**
     * Update an existing facture proforma.
     */
    public function update(FactureProforma $facture, array $data): FactureProforma
    {
        if ($facture->status !== 'draft') {
            throw new \Exception('Cannot update facture proforma that is not in draft status');
        }

        return DB::transaction(function () use ($facture, $data) {
            $facture->update([
                'fournisseur_id' => $data['fournisseur_id'],
                'service_demand_purchasing_id' => $data['service_demand_purchasing_id'] ?? null,
            ]);

            // Remove existing products and add new ones
            $facture->products()->delete();

            if (!empty($data['products'])) {
                $this->addProductsToFacture($facture, $data['products']);
            }

            return $facture->load(['fournisseur', 'serviceDemand.service', 'creator', 'products.product']);
        });
    }

    /**
     * Add products to a facture proforma.
     */
    protected function addProductsToFacture(FactureProforma $facture, array $products): void
    {
        foreach ($products as $productData) {
            FactureProformaProduct::create([
                'factureproforma_id' => $facture->id,
                'product_id' => $productData['product_id'],
                'quantity' => $productData['quantity'],
                'price' => $productData['price'],
                'unit' => $productData['unit'],
            ]);
        }
    }

    /**
     * Create multiple facture proformas from a service demand with supplier assignments.
     */
    public function createFromServiceDemand(int $serviceDemandId, array $productAssignments): array
    {
        return DB::transaction(function () use ($serviceDemandId, $productAssignments) {
            $serviceDemand = ServiceDemendPurchcing::with('items.product')->findOrFail($serviceDemandId);

            // Group products by supplier
            $productsBySupplier = collect($productAssignments)->groupBy('fournisseur_id');

            $factureProformas = [];

            foreach ($productsBySupplier as $fournisseurId => $supplierProducts) {
                $facture = FactureProforma::create([
                    'fournisseur_id' => $fournisseurId,
                    'service_demand_purchasing_id' => $serviceDemandId,
                    'created_by' => Auth::id(),
                    'status' => 'draft'
                ]);

                foreach ($supplierProducts as $productData) {
                    $item = $serviceDemand->items->firstWhere('id', $productData['item_id']);
                    
                    if ($item) {
                        FactureProformaProduct::create([
                            'factureproforma_id' => $facture->id,
                            'product_id' => $item->product_id,
                            'quantity' => $item->quantity,
                            'price' => $productData['price'],
                            'unit' => $item->product->unit ?? $item->product->forme ?? 'unit',
                        ]);
                    }
                }

                $facture->load(['fournisseur', 'serviceDemand.service', 'creator', 'products.product']);
                $factureProformas[] = $facture;
            }

            return $factureProformas;
        });
    }

    /**
     * Send facture proforma to supplier.
     */
    public function send(FactureProforma $facture): void
    {
        if ($facture->status !== 'draft') {
            throw new \Exception('Can only send draft facture proformas');
        }

        if ($facture->products->count() === 0) {
            throw new \Exception('Cannot send facture proforma without products');
        }

        $facture->update(['status' => 'sent']);
    }

    /**
     * Mark facture proforma as paid.
     */
    public function markAsPaid(FactureProforma $facture): void
    {
        if ($facture->status !== 'sent') {
            throw new \Exception('Can only mark sent facture proformas as paid');
        }

        $facture->update(['status' => 'paid']);
    }

    /**
     * Cancel facture proforma.
     */
    public function cancel(FactureProforma $facture): void
    {
        if ($facture->status === 'paid') {
            throw new \Exception('Cannot cancel paid facture proformas');
        }

        $facture->update(['status' => 'cancelled']);
    }

    /**
     * Get facture proforma statistics.
     */
    public function getStatistics(): array
    {
        $totalAmount = FactureProforma::with('products')->get()->sum('total_amount');

        return [
            'total_factures' => FactureProforma::count(),
            'draft_factures' => FactureProforma::draft()->count(),
            'sent_factures' => FactureProforma::sent()->count(),
            'paid_factures' => FactureProforma::paid()->count(),
            'cancelled_factures' => FactureProforma::cancelled()->count(),
            'total_amount' => $totalAmount,
            'this_month_factures' => FactureProforma::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'this_month_amount' => FactureProforma::with('products')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->get()
                ->sum('total_amount'),
        ];
    }

    /**
     * Get available service demands for facture proforma creation.
     */
    public function getAvailableServiceDemands(): \Illuminate\Database\Eloquent\Collection
    {
        return ServiceDemendPurchcing::with(['service:id,name', 'items.product:id,name,code,unit,forme'])
            ->where('status', 'sent')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get active suppliers.
     */
    public function getActiveSuppliers(): \Illuminate\Database\Eloquent\Collection
    {
        return Fournisseur::select('id', 'name', 'company_name', 'email', 'phone')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    /**
     * Delete facture proforma if it's in draft status.
     */
    public function delete(FactureProforma $facture): void
    {
        if ($facture->status !== 'draft') {
            throw new \Exception('Cannot delete facture proforma that is not in draft status');
        }

        $facture->delete();
    }

    /**
     * Get filtered facture proformas with pagination.
     */
    public function getFilteredFactures(array $filters = [], int $perPage = 15)
    {
        $query = FactureProforma::with([
            'fournisseur:id,name,company_name',
            'serviceDemand.service:id,name',
            'creator:id,name',
            'products'
        ]);

        // Apply filters
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['fournisseur_id'])) {
            $query->where('fournisseur_id', $filters['fournisseur_id']);
        }

        if (!empty($filters['service_demand_id'])) {
            $query->where('service_demand_purchasing_id', $filters['service_demand_id']);
        }

        if (!empty($filters['search'])) {
            $query->where('factureProformaCode', 'like', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }
}