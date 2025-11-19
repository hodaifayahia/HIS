<?php

namespace App\Services\Purchsing\order;

use App\Models\PharmacyProduct;
use App\Models\Product;
use App\Models\ServiceDemendPurchcing;
use App\Models\ServiceDemendPurchcingItem;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ServiceDemandItemService
{
    /**
     * Add an item to a service demand
     */
    public function addItem($demandId, array $itemData)
    {
        try {
            $demand = ServiceDemendPurchcing::findOrFail($demandId);

            // Allow adding items if status is draft, sent, approved, or factureprofram
            // Only prevent adding items if it's been completed (boncommend, received, cancelled)
            $blockStatuses = ['boncommend', 'received', 'cancelled'];
            if (in_array($demand->status, $blockStatuses)) {
                throw new \Exception('Cannot add items to demand with status: '.$demand->status);
            }

            // Determine whether the provided product_id is a stock Product or a PharmacyProduct
            $productId = $itemData['product_id'] ?? $itemData['pharmacy_product_id'] ?? null;
            $isStockProduct = isset($itemData['product_id']) ? Product::find($itemData['product_id']) : null;
            $isPharmacyProduct = isset($itemData['pharmacy_product_id']) ? PharmacyProduct::find($itemData['pharmacy_product_id']) : null;

            if (! $isStockProduct && ! $isPharmacyProduct) {
                throw new \Exception('Provided product_id or pharmacy_product_id was not found');
            }

            // Check if product already exists in demand (either stock or pharmacy)
            if ($isStockProduct) {
                $existingItem = $demand->items()->where('product_id', $itemData['product_id'])->first();
            } else {
                $existingItem = $demand->items()->where('pharmacy_product_id', $itemData['pharmacy_product_id'])->first();
            }

            if ($existingItem) {
                throw new \Exception('Product already exists in this demand');
            }

            $itemCreate = [
                'service_demand_purchasing_id' => $demand->id,
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'] ?? null,
                'quantity_by_box' => $itemData['quantity_by_box'] ?? false,
                'notes' => $itemData['notes'] ?? null,
            ];

            if ($isStockProduct) {
                $itemCreate['product_id'] = $itemData['product_id'];
            } else {
                $itemCreate['pharmacy_product_id'] = $itemData['pharmacy_product_id'];
            }

            $item = ServiceDemendPurchcingItem::create($itemCreate);
            $item->load('product', 'pharmacyProduct');

            return $item;
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Demand not found');
        }
    }

    /**
     * Update an item in a service demand
     */
    public function updateItem($demandId, $itemId, array $itemData)
    {
        try {
            $demand = ServiceDemendPurchcing::with('items.product', 'items.pharmacyProduct')->findOrFail($demandId);
            $item = $demand->items()->findOrFail($itemId);

            // Allow updating items if status is draft, sent, approved, or factureprofram
            // Only prevent updating items if it's been completed (boncommend, received, cancelled)
            $blockStatuses = ['boncommend', 'received', 'cancelled'];
            if (in_array($demand->status, $blockStatuses)) {
                throw new \Exception('Cannot update items for demand with status: '.$demand->status);
            }

            $item->update([
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'] ?? null,
                'quantity_by_box' => $itemData['quantity_by_box'] ?? false,
                'notes' => $itemData['notes'] ?? null,
            ]);

            return $item->load('product', 'pharmacyProduct');
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Item or demand not found');
        }
    }

    /**
     * Remove an item from a service demand
     */
    public function removeItem($demandId, $itemId)
    {
        try {
            $demand = ServiceDemendPurchcing::with(['service', 'items.product', 'items.pharmacyProduct'])->findOrFail($demandId);
            $item = $demand->items()->findOrFail($itemId);

            // Allow removing items if status is draft, sent, approved, or factureprofram
            // Only prevent removing items if it's been completed (boncommend, received, cancelled)
            $blockStatuses = ['boncommend', 'received', 'cancelled'];
            if (in_array($demand->status, $blockStatuses)) {
                throw new \Exception('Cannot remove items from demand with status: '.$demand->status);
            }

            $item->delete();

            return true;
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Item or demand not found');
        }
    }
}
