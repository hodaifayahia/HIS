<?php
// app/Http/Resources/Caisse/FinancialTransactionResource.php

namespace App\Http\Resources\Caisse;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinancialTransactionResource extends JsonResource
{
    /**
     * Static holder for refund authorizations grouped by fiche_navette_item_id.
     * Controller will set this before returning the resource collection.
     */
    public static array $refundAuthorizations = [];

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'fiche_navette_item_id' => $this->fiche_navette_item_id,
            'patient_id' => $this->patient_id,
            'cashier_id' => $this->cashier_id,
            'amount' => (float) $this->amount,
            'formatted_amount' => $this->formatted_amount,
            'transaction_type' => $this->transaction_type,
            'transaction_type_text' => $this->transaction_type_text,
            'payment_method' => $this->payment_method,
            'payment_method_text' => $this->payment_method_text,
            'status_color' => $this->status_color,
            'b2b_invoice_id' => $this->b2b_invoice_id,
            'notes' => $this->notes,
            'can_be_refunded' => $this->canBeRefunded(),
            'can_be_adjusted' => $this->canBeAdjusted(),

            // Include refund authorization for this transaction's fiche item (if exists)
            'refund_authorization' => $this->getRefundAuthorizationForItem(),

            // Relationships
            'fiche_navette_item' => $this->whenLoaded('ficheNavetteItem'),
            'patient' => $this->whenLoaded('patient'),
            'cashier' => $this->whenLoaded('cashier'),
            'b2b_invoice' => $this->whenLoaded('b2bInvoice'),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Get refund authorization for this transaction's fiche item
     */
    private function getRefundAuthorizationForItem()
    {
        if (!$this->fiche_navette_item_id) return null;
        
        $auths = self::$refundAuthorizations[$this->fiche_navette_item_id] ?? null;
        
        if (!$auths) return null;
        
        // Return the first (most recent) authorization if it's an array
        if (is_array($auths) && count($auths) > 0) {
            return $auths[0];
        }
        
        return $auths;
    }

    /**
     * Attach additional top-level data when returning collections
     */
    public function with($request): array
    {
        return [
            'refund_authorizations' => self::$refundAuthorizations
        ];
    }
}
