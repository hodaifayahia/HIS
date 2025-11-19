<?php

namespace App\Services\Stock;

use App\Models\ProductReserve;
use App\Models\Inventory;
use App\Models\PharmacyInventory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ProductReserveService
{
    public function paginate(?int $reserveId = null, int $perPage = 15)
    {
        $query = ProductReserve::with([
            'product', 
            'pharmacyProduct', 
            'reserver',
            'stockage',
            'pharmacyStockage',
            'reserve'
        ]);

        if ($reserveId) {
            $query->where('reserve_id', $reserveId);
        }

        return $query->latest()->paginate($perPage);
    }

    public function store(array $data): ProductReserve
    {
        return DB::transaction(function () use ($data) {
            $data['reservation_code'] = Str::upper(Str::random(8));
            $data['reserved_at']      = now();
            $data['status']           = 'pending';

            // Create the reservation
            $reserve = ProductReserve::create($data);

            // Deduct stock from inventory
            $this->deductStock($reserve);

            return $reserve->load(['product', 'pharmacyProduct', 'reserver', 'stockage', 'pharmacyStockage']);
        });
    }

    public function update(ProductReserve $reserve, array $data): ProductReserve
    {
        return DB::transaction(function () use ($reserve, $data) {
            $oldQuantity = $reserve->quantity;
            $newQuantity = $data['quantity'] ?? $oldQuantity;
            
            // If quantity changed, adjust stock
            if ($oldQuantity != $newQuantity && $reserve->status === 'pending') {
                $difference = $newQuantity - $oldQuantity;
                
                if ($difference > 0) {
                    // Need more stock - deduct
                    $this->deductStock($reserve, $difference);
                } else {
                    // Return excess stock
                    $this->returnStock($reserve, abs($difference));
                }
            }

            $reserve->update($data);
            return $reserve->refresh();
        });
    }

    public function cancel(ProductReserve $reserve, string $reason): ProductReserve
    {
        return DB::transaction(function () use ($reserve, $reason) {
            // Return stock to inventory if reservation was pending
            if ($reserve->status === 'pending') {
                $this->returnStock($reserve, $reserve->quantity);
            }

            $reserve->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancel_reason' => $reason
            ]);
            
            return $reserve->refresh();
        });
    }

    public function fulfill(ProductReserve $reserve): ProductReserve
    {
        return DB::transaction(function () use ($reserve) {
            if ($reserve->status !== 'pending') {
                throw new \InvalidArgumentException('Only pending reservations can be fulfilled');
            }

            $reserve->update([
                'status' => 'fulfilled',
                'fulfilled_at' => now()
            ]);

            // Stock was already deducted when reservation was created
            // No need to deduct again on fulfill

            return $reserve->fresh();
        });
    }

    public function delete(ProductReserve $reserve): void
    {
        DB::transaction(function () use ($reserve) {
            // Return stock if reservation was pending
            if ($reserve->status === 'pending') {
                $this->returnStock($reserve, $reserve->quantity);
            }
            
            $reserve->delete();
        });
    }

    /**
     * Bulk fulfill reservations
     */
    public function bulkFulfill(array $reserveIds, ?int $destinationServiceId = null): array
    {
        $results = ['success' => [], 'failed' => []];
        
        foreach ($reserveIds as $id) {
            try {
                $reserve = ProductReserve::findOrFail($id);
                
                if ($reserve->status !== 'pending') {
                    $results['failed'][] = [
                        'id' => $id,
                        'message' => 'Only pending reservations can be fulfilled'
                    ];
                    continue;
                }
                
                // Update destination_service_id if provided
                if ($destinationServiceId) {
                    $reserve->update(['destination_service_id' => $destinationServiceId]);
                }
                
                $this->fulfill($reserve);
                $results['success'][] = $id;
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'id' => $id,
                    'message' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }

    /**
     * Bulk cancel reservations
     */
    public function bulkCancel(array $reserveIds, string $reason): array
    {
        $results = ['success' => [], 'failed' => []];
        
        foreach ($reserveIds as $id) {
            try {
                $reserve = ProductReserve::findOrFail($id);
                
                if ($reserve->status !== 'pending') {
                    $results['failed'][] = [
                        'id' => $id,
                        'message' => 'Only pending reservations can be cancelled'
                    ];
                    continue;
                }
                
                $this->cancel($reserve, $reason);
                $results['success'][] = $id;
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'id' => $id,
                    'message' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }

    /**
     * Deduct stock from inventory
     */
    private function deductStock(ProductReserve $reserve, ?int $quantity = null): void
    {
        $qty = $quantity ?? $reserve->quantity;

        if ($reserve->product_id && $reserve->stockage_id) {
            // Deduct from regular product inventory
            $inventory = Inventory::where('product_id', $reserve->product_id)
                ->where('stockage_id', $reserve->stockage_id)
                ->first();
            
            if ($inventory && $inventory->quantity >= $qty) {
                $inventory->decrement('quantity', $qty);
            } else {
                throw new \Exception('Insufficient stock in selected storage');
            }
        } elseif ($reserve->pharmacy_product_id && $reserve->pharmacy_stockage_id) {
            // Deduct from pharmacy product inventory
            $inventory = PharmacyInventory::where('pharmacy_product_id', $reserve->pharmacy_product_id)
                ->where('pharmacy_stockage_id', $reserve->pharmacy_stockage_id)
                ->first();
            
            if ($inventory && $inventory->quantity >= $qty) {
                $inventory->decrement('quantity', $qty);
            } else {
                throw new \Exception('Insufficient pharmacy stock in selected storage');
            }
        }
    }

    /**
     * Return stock to inventory
     */
    private function returnStock(ProductReserve $reserve, int $quantity): void
    {
        if ($reserve->product_id && $reserve->stockage_id) {
            // Return to regular product inventory
            $inventory = Inventory::where('product_id', $reserve->product_id)
                ->where('stockage_id', $reserve->stockage_id)
                ->first();
            
            if ($inventory) {
                $inventory->increment('quantity', $quantity);
            }
        } elseif ($reserve->pharmacy_product_id && $reserve->pharmacy_stockage_id) {
            // Return to pharmacy product inventory
            $inventory = PharmacyInventory::where('pharmacy_product_id', $reserve->pharmacy_product_id)
                ->where('pharmacy_stockage_id', $reserve->pharmacy_stockage_id)
                ->first();
            
            if ($inventory) {
                $inventory->increment('quantity', $quantity);
            }
        }
    }
}