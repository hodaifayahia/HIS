<?php

namespace App\Services\Purchsing\order;

use App\Models\ServiceDemendPurchcing;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ServiceDemandStatusService
{
    /**
     * Update service demand status to 'factureprofram'
     */
    public function updateToFactureProforma($id)
    {
        try {
            $demand = ServiceDemendPurchcing::findOrFail($id);

            // Validate current status allows transition
            if (! in_array($demand->status, ['sent', 'approved'])) {
                throw new \Exception('Demand must be sent or approved to create proforma');
            }

            $demand->update([
                'status' => 'factureprofram',
                'proforma_confirmed' => false,
                'proforma_confirmed_at' => null,
            ]);

            return $demand->fresh()->load(['service', 'items.product', 'items.pharmacyProduct']);
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Demand not found');
        }
    }

    /**
     * Update service demand status to 'boncommend'
     */
    public function updateToBonCommend($id)
    {
        try {
            $demand = ServiceDemendPurchcing::findOrFail($id);

            // Validate current status allows transition
            if (! in_array($demand->status, ['factureprofram', 'approved', 'sent'])) {
                throw new \Exception('Demand must be in proforma, approved, or sent status to create bon commend');
            }

            $demand->update([
                'status' => 'boncommend',
                'boncommend_confirmed' => false,
                'boncommend_confirmed_at' => null,
            ]);

            return $demand->fresh()->load(['service', 'items.product', 'items.pharmacyProduct']);
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Demand not found');
        }
    }

    /**
     * Confirm proforma for service demand
     */
    public function confirmProforma($id)
    {
        try {
            $demand = ServiceDemendPurchcing::findOrFail($id);

            $demand->update([
                'proforma_confirmed' => true,
                'proforma_confirmed_at' => now(),
            ]);

            return $demand->fresh()->load(['service', 'items.product', 'items.pharmacyProduct']);
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Demand not found');
        }
    }

    /**
     * Confirm bon commend for service demand
     */
    public function confirmBonCommend($id)
    {
        try {
            $demand = ServiceDemendPurchcing::findOrFail($id);

            $demand->update([
                'boncommend_confirmed' => true,
                'boncommend_confirmed_at' => now(),
            ]);

            return $demand->fresh()->load(['service', 'items.product', 'items.pharmacyProduct']);
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Demand not found');
        }
    }
}
