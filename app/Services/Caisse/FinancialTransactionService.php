<?php

// app/Services/Caisse/FinancialTransactionService.php

namespace App\Services\Caisse;

use App\Models\Caisse\FinancialTransaction;
use App\Models\Reception\ficheNavetteItem;
use App\Models\Reception\ItemDependency;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FinancialTransactionService
{
    public function getAllPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = FinancialTransaction::with([
            'ficheNavetteItem.prestation.specialization',
            'ficheNavetteItem.ficheNavette.patient',
            'ficheNavetteItem',
            'ficheNavetteItem.dependencies.dependencyPrestation.specialization',
            'patient',
            'cashier',
            'itemDependency',
        ])
            ->latest('created_at');

        // Apply filters
        if (! empty($filters['fiche_navette_item_id'])) {
            $query->byFicheNavetteItem($filters['fiche_navette_item_id']);
        }

        if (! empty($filters['prestation_id'])) {
            $query->byPrestation($filters['prestation_id']);
        }

        if (! empty($filters['transaction_type'])) {
            $query->byTransactionType($filters['transaction_type']);
        }

        if (! empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (! empty($filters['cashier_id'])) {
            $query->where('cashier_id', $filters['cashier_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['caisse_id'])) {
            $caisseId = $filters['caisse_id'];
            $query->whereHas('ficheNavetteItem.ficheNavette', function ($q) use ($caisseId) {
                $q->where('caisse_id', $caisseId);
            });
        }

        return $query->paginate($perPage);
    }

    public function findById(int $id): FinancialTransaction
    {
        return FinancialTransaction::with([
            'ficheNavetteItem.prestation.specialization',
            'ficheNavetteItem.ficheNavette.patient',
            'ficheNavetteItem',
            'ficheNavetteItem.dependencies.dependencyPrestation.specialization',
            'patient',
            'cashier',
            'itemDependency',
        ])->findOrFail($id);
    }

    public function getAllPrestationsWithDependencies($ficheNavetteId)
    {
        // Get all ficheNavetteItems for this ficheNavette
        $items = ficheNavetteItem::with([
            'prestation',
            'dependencies.dependencyPrestation',
        ])->where('fiche_navette_id', $ficheNavetteId)->get();

        $prestations = [];

        foreach ($items as $item) {
            // Add the main prestation if it exists
            if ($item->prestation) {
                $prestations[] = [
                    'type' => 'prestation',
                    'item_id' => $item->id,
                    'prestation_id' => $item->prestation->id,
                    'name' => $item->prestation->name,
                    'service' => $item->prestation->service->name ?? null,
                    'final_price' => $item->final_price,
                    'paid_amount' => $item->paid_amount,
                    'remaining_amount' => $item->remaining_amount,
                    'status' => $item->status,
                ];
            }

            // Add dependencies as prestations
            foreach ($item->dependencies as $dep) {
                if ($dep->dependencyPrestation) {
                    $prestations[] = [
                        'type' => 'dependency',
                        'item_id' => $item->id,
                        'dependency_id' => $dep->id,
                        'prestation_id' => $dep->dependencyPrestation->id,
                        'name' => $dep->getDisplayNameAttribute(),
                        'service' => $dep->dependencyPrestation->service->name ?? null,
                        'final_price' => $dep->final_price,
                        'paid_amount' => $dep->paid_amount,
                        'remaining_amount' => $dep->remaining_amount,
                        'status' => $dep->status,
                    ];
                }
            }
        }

        return $prestations;
    }

    public function getTodaysOrUnpaidPrestationsForPatient($patientId)
    {
        // Get all ficheNavette for this patient created today or not fully paid
        $ficheNavettes = \App\Models\Reception\ficheNavette::where('patient_id', $patientId)
            ->where(function ($q) {
                $q->whereDate('created_at', now()->toDateString())
                    ->orWhere('status', '!=', 'payed');
            })
            ->pluck('id');

        $allPrestations = [];
        foreach ($ficheNavettes as $ficheNavetteId) {
            $prestations = $this->getAllPrestationsWithDependencies($ficheNavetteId);
            $allPrestations = array_merge($allPrestations, $prestations);
        }

        return $allPrestations;
    }

    /**
     * Create financial transaction with proper fiche navette item resolution
     */
    public function create(array $data): FinancialTransaction
    {
        return DB::transaction(function () use ($data) {
            // Validate and resolve fiche_navette_item_id if needed
            $data = $this->validateAndResolveFicheNavetteItem($data);

            // Create the financial transaction
            // If this is a dependency payment, avoid persisting the parent fiche_navette_item_id
            if (! empty($data['_is_dependency_payment']) || ! empty($data['item_dependency_id'])) {
                // keep flags for later processing but remove parent fiche id to avoid storing it on transaction
                unset($data['fiche_navette_item_id']);
            }

            $transaction = FinancialTransaction::create($data);

            return $transaction->load(['ficheNavetteItem', 'patient', 'cashier']);
        });
    }

    /**
     * Validate and resolve fiche navette item ID
     */
    private function validateAndResolveFicheNavetteItem(array $data): array
    {
        // Extract fiche_navette_item_id from items payload if present
        if (! empty($data['items'])) {
            $itemsPayload = $data['items'];

            // decode JSON string if necessary
            if (is_string($itemsPayload)) {
                $decoded = json_decode($itemsPayload, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $itemsPayload = $decoded;
                }
            }

            // items can be associative ['fiche_navette_item_id' => 165]
            if (is_array($itemsPayload)) {
                if (! empty($itemsPayload['fiche_navette_item_id'])) {
                    $data['fiche_navette_item_id'] = (int) $itemsPayload['fiche_navette_item_id'];
                } elseif (isset($itemsPayload[0]) && is_array($itemsPayload[0]) && ! empty($itemsPayload[0]['fiche_navette_item_id'])) {
                    // or an array of items -> take first element
                    $data['fiche_navette_item_id'] = (int) $itemsPayload[0]['fiche_navette_item_id'];
                }
            }
        }

        // Check if item_dependency_id is provided - this indicates a dependency payment
        if (! empty($data['item_dependency_id'])) {
            $dependency = ItemDependency::find($data['item_dependency_id']);
            if (! $dependency) {
                throw new InvalidArgumentException("Item dependency with ID {$data['item_dependency_id']} not found.");
            }

            // Use parent_item_id as the fiche_navette_item_id and mark as dependency payment
            $data['fiche_navette_item_id'] = $dependency->parent_item_id;
            // also persist explicit dependency id so transaction stores it
            $data['item_dependency_id'] = $dependency->id;
            // Keep the patient_id as sent from frontend (actual patient ID)
            $data['_is_dependency_payment'] = true;
            $data['_dependency_id'] = $dependency->id;
            $data['_dependency_prestation_id'] = $dependency->dependent_prestation_id; // Store for later use

            return $this->castDataTypes($data);
        }

        // If fiche_navette_item_id still missing, try to resolve from patient_id (which might be prestation_id)
        if (empty($data['fiche_navette_item_id'])) {
            if (empty($data['patient_id'])) {
                throw new InvalidArgumentException('Either fiche_navette_item_id (in items or top-level), item_dependency_id, or patient_id must be provided.');
            }

            // First check if patient_id is actually a patient ID
            $patient = \App\Models\Patient::find($data['patient_id']);
            if ($patient) {
                // patient_id is valid, try to find items by looking up prestations for this patient
                // This is a fallback case - normally fiche_navette_item_id should be provided
                throw new InvalidArgumentException('fiche_navette_item_id is required when patient_id is provided.');
            } else {
                // Assume patient_id is actually prestation_id (legacy behavior)
                $prestationId = $data['patient_id'];

                // First try to find ficheNavetteItem by prestation_id or package_id
                $item = ficheNavetteItem::where('prestation_id', $prestationId)
                    ->orWhere('package_id', $prestationId)
                    ->latest('id')
                    ->first();

                if ($item) {
                    $data['fiche_navette_item_id'] = (int) $item->id;
                    // Get actual patient_id from the fiche
                    $data['patient_id'] = $item->ficheNavette->patient_id ?? $data['patient_id'];
                } else {
                    // Look in dependencies -> this is a dependency payment
                    $dependency = ItemDependency::where('dependent_prestation_id', $prestationId)->latest('id')->first();
                    if ($dependency && $dependency->parent_item_id) {
                        // set parent for validation and mark dependency
                        $data['fiche_navette_item_id'] = (int) $dependency->parent_item_id;
                        $data['item_dependency_id'] = $dependency->id;
                        $data['_is_dependency_payment'] = true;
                        $data['_dependency_id'] = $dependency->id;
                        $data['_dependency_prestation_id'] = $dependency->dependent_prestation_id;
                        // Get actual patient_id from the parent fiche
                        $parentItem = ficheNavetteItem::find($dependency->parent_item_id);
                        $data['patient_id'] = $parentItem->ficheNavette->patient_id ?? $data['patient_id'];
                    } else {
                        throw new InvalidArgumentException("Could not resolve fiche_navette_item_id for prestation ID {$prestationId}");
                    }
                }
            }
        } else {
            // fiche_navette_item_id is provided, check if this is actually a dependency payment
            if (! empty($data['patient_id'])) {
                // If patient_id is provided and valid, keep it as is
                $patient = \App\Models\Patient::find($data['patient_id']);
                if (! $patient) {
                    // If not a valid patient ID, treat as prestation_id for dependency lookup
                    $dependency = ItemDependency::where('parent_item_id', $data['fiche_navette_item_id'])
                        ->where('dependent_prestation_id', $data['patient_id'])
                        ->first();

                    if ($dependency) {
                        $data['item_dependency_id'] = $dependency->id;
                        $data['_is_dependency_payment'] = true;
                        $data['_dependency_id'] = $dependency->id;
                        $data['_dependency_prestation_id'] = $dependency->dependent_prestation_id;
                        // Get actual patient_id from the fiche
                        $ficheItem = ficheNavetteItem::find($data['fiche_navette_item_id']);
                        $data['patient_id'] = $ficheItem->ficheNavette->patient_id ?? $data['patient_id'];
                    }
                }
            }
        }

        // Verify fiche_navette_item exists
        $ficheItem = ficheNavetteItem::find($data['fiche_navette_item_id']);
        if (! $ficheItem) {
            throw new InvalidArgumentException("Fiche navette item with ID {$data['fiche_navette_item_id']} not found.");
        }

        // If patient_id still missing or invalid, get it from the fiche
        if (empty($data['patient_id']) || ! \App\Models\Patient::find($data['patient_id'])) {
            $data['patient_id'] = $ficheItem->ficheNavette->patient_id;
        }

        return $this->castDataTypes($data);
    }

    /**
     * Cast data types to proper format
     */
    private function castDataTypes(array $data): array
    {
        // Cast IDs and amount to proper types
        $data['fiche_navette_item_id'] = (int) $data['fiche_navette_item_id'];
        if (! empty($data['patient_id'])) {
            $data['patient_id'] = (int) $data['patient_id'];
        }
        if (! empty($data['cashier_id'])) {
            $data['cashier_id'] = (int) $data['cashier_id'];
        }
        if (isset($data['amount'])) {
            $data['amount'] = (float) $data['amount'];
        }

        return $data;
    }

    public function update(FinancialTransaction $transaction, array $data): FinancialTransaction
    {
        return DB::transaction(function () use ($transaction, $data) {
            // capture old state
            $oldAmount = $transaction->amount ?? 0;
            $oldType = $transaction->transaction_type ?? null;

            // perform update
            $transaction->update($data);
            $transaction->refresh();

            // determine new state
            $newAmount = $transaction->amount ?? 0;
            $newType = $transaction->transaction_type ?? null;

            // Compute net effect on paid_amount for the related prestation
            // Payments increase paid_amount, refunds decrease it. Represent effect as: payment -> +amount, refund -> -amount
            $oldEffect = ($oldType === 'payment') ? $oldAmount : (($oldType === 'refund') ? -$oldAmount : 0);
            $newEffect = ($newType === 'payment') ? $newAmount : (($newType === 'refund') ? -$newAmount : 0);
            $deltaEffect = $newEffect - $oldEffect;

            if (abs($deltaEffect) > 0) {
                // Prefer explicit item_dependency_id if present (dependency payments)
                $dependencyId = $transaction->item_dependency_id ?? null;

                if ($dependencyId) {
                    $itemDependency = ItemDependency::find($dependencyId);
                    if ($itemDependency) {
                        // Use formatted string for decimals to avoid implicit float-to-decimal conversion warnings
                        $newPaid = max(0, ($itemDependency->paid_amount ?? 0) + $deltaEffect);
                        // Ensure paid_amount does not exceed final_price
                        if ($newPaid > (float) $itemDependency->final_price) {
                            $newPaid = (float) $itemDependency->final_price;
                        }
                        $itemDependency->update([
                            'paid_amount' => (float) $newPaid,
                            'remaining_amount' => (float) max(0, (float) $itemDependency->final_price - (float) $newPaid),
                            'payment_status' => ((float) max(0, (float) $itemDependency->final_price - (float) $newPaid) <= 0) ? 'paid' : 'pending',
                        ]);
                    }
                } else {
                    $ficheId = $transaction->fiche_navette_item_id ?? null;
                    if ($ficheId) {
                        $ficheItem = ficheNavetteItem::find($ficheId);
                        if ($ficheItem) {
                            $newPaid = max(0, ($ficheItem->paid_amount ?? 0) + $deltaEffect);
                            // Clamp paid_amount within [0, final_price]
                            if ($newPaid > (float) $ficheItem->final_price) {
                                $newPaid = (float) $ficheItem->final_price;
                            }
                            $ficheItem->update([
                                'paid_amount' => (float) $newPaid,
                                'remaining_amount' => (float) max(0, (float) $ficheItem->final_price - (float) $newPaid),
                                'payment_status' => ((float) max(0, (float) $ficheItem->final_price - (float) $newPaid) <= 0) ? 'paid' : 'pending',
                            ]);
                        }
                    }
                }
            }

            return $transaction->refresh()->load(['ficheNavetteItem', 'patient', 'cashier']);
        });
    }

    /**
     * Process payment and update prestation amounts
     * This function finds the prestation either in ficheNavetteItem or ItemDependency
     * and updates the remaining_amount and paid_amount
     */
    private function processPaymentAndUpdatePrestation(FinancialTransaction $transaction): array
    {
        if ($transaction->transaction_type !== 'payment') {
            throw new InvalidArgumentException('This function only works with payment transactions.');
        }

        $updated = [];
        $ficheNavetteItemId = $transaction->fiche_navette_item_id;
        $paymentAmount = $transaction->amount;

        // Check if this is a dependency payment
        $isDependencyPayment = isset($transaction->_is_dependency_payment) && $transaction->_is_dependency_payment;
        $dependencyId = $transaction->_dependency_id ?? null;
        $dependencyPrestationId = $transaction->_dependency_prestation_id ?? null;

        if ($isDependencyPayment && $dependencyId) {
            // This is a dependency payment - update ItemDependency directly
            $itemDependency = ItemDependency::find($dependencyId);
            if ($itemDependency) {
                $newPaidAmount = ($itemDependency->paid_amount ?? 0) + $paymentAmount;
                $newRemainingAmount = ($itemDependency->final_price ?? 0) - $newPaidAmount;

                $itemDependency->update([
                    'paid_amount' => $newPaidAmount,
                    'remaining_amount' => max(0, $newRemainingAmount),
                    'payment_status' => ($newRemainingAmount <= 0) ? 'paid' : 'pending',
                ]);

                $updated['item_dependency'] = $itemDependency->fresh();
            }
        } elseif ($ficheNavetteItemId) {

            // Regular fiche navette item payment
            $ficheNavetteItem = ficheNavetteItem::find($ficheNavetteItemId);
            if ($ficheNavetteItem) {
                $newPaidAmount = ($ficheNavetteItem->paid_amount ?? 0) + $paymentAmount;
                $newRemainingAmount = ($ficheNavetteItem->final_price ?? 0) - $newPaidAmount;

                $ficheNavetteItem->update([
                    'paid_amount' => $newPaidAmount,
                    'remaining_amount' => max(0, $newRemainingAmount),
                    'payment_status' => ($newRemainingAmount <= 0) ? 'paid' : 'pending',
                ]);

                $updated['fiche_navette_item'] = $ficheNavetteItem->fresh();
            }
        } else {
            // Try to find based on item_dependency_id from the transaction
            if ($transaction->item_dependency_id) {
                $itemDependency = ItemDependency::find($transaction->item_dependency_id);
                if ($itemDependency) {
                    $newPaidAmount = ($itemDependency->paid_amount ?? 0) + $paymentAmount;
                    $newRemainingAmount = ($itemDependency->final_price ?? 0) - $newPaidAmount;

                    $itemDependency->update([
                        'paid_amount' => $newPaidAmount,
                        'remaining_amount' => max(0, $newRemainingAmount),
                        'payment_status' => ($newRemainingAmount <= 0) ? 'paid' : 'pending',
                    ]);

                    $updated['item_dependency'] = $itemDependency->fresh();
                }
            }
        }

        if (empty($updated)) {
            throw new \Exception('Failed to update payment records for transaction. No valid target found.');
        }

        return $updated;
    }

    /**
     * Process refund and update prestation amounts
     */
    private function processRefundAndUpdatePrestation(FinancialTransaction $transaction): array
    {
        if ($transaction->transaction_type !== 'refund') {
            throw new InvalidArgumentException('This function only works with refund transactions.');
        }

        $updated = [];
        $ficheNavetteItemId = $transaction->fiche_navette_item_id;
        $refundAmount = abs((float) $transaction->amount); // Ensure positive amount for calculations

        // Check if this is a dependency refund by looking at item_dependency_id or dependency flags
        $dependencyId = $transaction->item_dependency_id ?? $transaction->_dependency_id ?? null;
        $isDependencyRefund = ! empty($dependencyId) || (isset($transaction->_is_dependency_payment) && $transaction->_is_dependency_payment);

        if ($isDependencyRefund && $dependencyId) {
            // This is a dependency refund - update ItemDependency directly
            $itemDependency = ItemDependency::find($dependencyId);
            if ($itemDependency) {
                $currentPaidAmount = (float) ($itemDependency->paid_amount ?? 0);
                $finalPrice = (float) ($itemDependency->final_price ?? 0);

                // Subtract refund amount from paid amount (ensure it doesn't go below 0)
                $newPaidAmount = max(0, $currentPaidAmount - $refundAmount);
                $newRemainingAmount = max(0, $finalPrice - $newPaidAmount);

                $itemDependency->update([
                    'paid_amount' => $newPaidAmount,
                    'remaining_amount' => $newRemainingAmount,
                    'payment_status' => ($newRemainingAmount <= 0) ? 'paid' : 'pending',
                ]);

                $updated['item_dependency'] = $itemDependency->fresh();
            }
        } elseif ($ficheNavetteItemId) {
            // Regular fiche navette item refund
            $ficheNavetteItem = ficheNavetteItem::find($ficheNavetteItemId);
            if ($ficheNavetteItem) {
                $currentPaidAmount = (float) ($ficheNavetteItem->paid_amount ?? 0);
                $finalPrice = (float) ($ficheNavetteItem->final_price ?? 0);

                // Subtract refund amount from paid amount (ensure it doesn't go below 0)
                $newPaidAmount = max(0, $currentPaidAmount - $refundAmount);
                $newRemainingAmount = max(0, $finalPrice - $newPaidAmount);

                $ficheNavetteItem->update([
                    'paid_amount' => $newPaidAmount,
                    'remaining_amount' => $newRemainingAmount,
                    'payment_status' => ($newRemainingAmount <= 0) ? 'paid' : 'pending',
                ]);

                $updated['fiche_navette_item'] = $ficheNavetteItem->fresh();
            }
        }

        if (empty($updated)) {
            throw new \Exception('Failed to update refund records for transaction. No valid target found.');
        }

        return $updated;
    }

    /**
     * Process payment transaction and update prestation
     */
    public function processPaymentTransaction(array $data): array
    {

        return DB::transaction(function () use ($data) {
            // Validate and resolve fiche_navette_item_id if needed
            $data = $this->validateAndResolveFicheNavetteItem($data);

            // Store dependency flags for later use
            $isDependencyPayment = $data['_is_dependency_payment'] ?? false;
            $dependencyId = $data['_dependency_id'] ?? null;
            $dependencyPrestationId = $data['_dependency_prestation_id'] ?? null;

            // Remove internal flags and raw items payload to avoid mass-assignment errors
            $hasDependencyFlags = $isDependencyPayment || ! empty($data['item_dependency_id']);

            // keep item_dependency_id in payload but remove transient internal flags
            unset($data['_is_dependency_payment'], $data['_dependency_id'], $data['_dependency_prestation_id']);
            if (isset($data['items'])) {
                unset($data['items']);
            }

            // If this is a dependency payment, avoid persisting parent fiche id onto transaction
            if ($hasDependencyFlags && isset($data['fiche_navette_item_id'])) {
                unset($data['fiche_navette_item_id']);
            }

            // Create the financial transaction (item_dependency_id, if present, will be persisted)
            $transaction = FinancialTransaction::create($data);

            // Restore dependency flags for processing
            $transaction->_is_dependency_payment = $isDependencyPayment;
            $transaction->_dependency_id = $dependencyId;
            $transaction->_dependency_prestation_id = $dependencyPrestationId;

            // If item_dependency_id was not set in flags but is present in data, use it
            if (! $dependencyId && ! empty($data['item_dependency_id'])) {
                $transaction->_is_dependency_payment = true;
                $transaction->_dependency_id = $data['item_dependency_id'];
            }

            // If it's a payment, update the prestation amounts
            $updatedItems = [];
            if ($transaction->transaction_type === 'payment') {
                try {
                    $updatedItems = $this->processPaymentAndUpdatePrestation($transaction);
                } catch (\Exception $e) {
                    // If payment processing fails, delete the created transaction and re-throw
                    $transaction->delete();
                    throw $e;
                }
            }

            // If it's a refund, update prestation amounts accordingly
            if ($transaction->transaction_type === 'refund') {
                try {
                    $updatedItems = $this->processRefundAndUpdatePrestation($transaction);
                } catch (\Exception $e) {
                    // If refund processing fails, delete the created transaction and re-throw
                    $transaction->delete();
                    throw $e;
                }
            }

            // Corrected return statement
            return [
                'transaction' => $transaction->load(['ficheNavetteItem', 'patient', 'cashier']),
                'updated_items' => $updatedItems,
            ]; // <-- Semicolon and closing bracket fixed
        }); // <-- Semicolon added
    }

    /**
     * Create multiple payment transactions (for bulk payments)
     * Now supports both legacy format and new global payment format
     */
    public function createBulkPayments(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // Check if this is new global payment format
            if (isset($data['items']) && isset($data['total_amount'])) {
                $globalResult = $this->processGlobalPayment($data);

                // Return in the expected format with 'payments' key
                return [
                    'payments' => $globalResult['transactions'] ?? [],
                    'updated_items' => $globalResult['updated_items'] ?? [],
                    'total_processed' => $globalResult['total_processed'] ?? 0,
                    'amount_processed' => $globalResult['amount_processed'] ?? 0,
                    'payments_amount' => $globalResult['payments_amount'] ?? 0,
                    'remaining_amount' => $globalResult['remaining_amount'] ?? 0,
                    'donation_transaction' => $globalResult['donation_transaction'] ?? null,
                    'excess_amount' => $globalResult['excess_amount'] ?? 0,
                    'action' => $globalResult['action'] ?? null,
                    'success' => true,
                    'message' => isset($globalResult['donation_transaction'])
                        ? "Global payment processed successfully. {$globalResult['excess_amount']} was donated."
                        : "Global payment processed successfully for {$globalResult['total_processed']} items.",
                ];
            }

            // Legacy format - array of individual payments
            $results = [];
            $errors = [];
            $updatedItems = [];

            foreach ($data as $index => $paymentData) {
                try {
                    $result = $this->processPaymentTransaction($paymentData);
                    $results[] = $result['transaction'];
                    $updatedItems = array_merge($updatedItems, $result['updated_items']);
                } catch (\Exception $e) {
                    $errors[$index] = $e->getMessage();
                }
            }

            if (! empty($errors)) {
                throw new \Exception('Some payments failed: '.json_encode($errors));
            }

            // Return consistent structure for legacy format
            return [
                'payments' => $results,
                'updated_items' => $updatedItems,
                'total_processed' => count($results),
                'success' => true,
                'message' => 'Bulk payment processed successfully for '.count($results).' transactions.',
            ];
        });
    }

    /**
     * Process global payment - pays multiple items in priority order
     */
    private function processGlobalPayment(array $data): array
    {
        
        $items = $data['items'];
        $totalAmount = $data['total_amount'];
        $cashierId = $data['cashier_id'];
        $patientId = $data['patient_id'];
        $paymentMethod = $data['payment_method'];
        $notes = $data['notes'] ?? 'Global payment';
        $isBankTransaction = $data['is_bank_transaction'] ?? false;
        $bankIdAccount = $data['bank_id_account'] ?? null;

        $results = [];
        $processedTransactions = [];
        $updatedItems = [];

          if (($data['is_bank_transaction'] ?? false) && empty($data['bank_id_account'])) {
                $defaultBankAccount = \App\Models\Bank\BankAccount::where('is_defult', true)
                    ->where('is_active', true)
                    ->first();
                
                if ($defaultBankAccount) {
                    $data['bank_id_account'] = $defaultBankAccount->id;
                }
            }
        // Calculate total outstanding amount needed
        $totalOutstanding = 0;
        foreach ($items as $item) {
            $totalOutstanding += $item['amount'];
        }

        // Sort items by priority: unpaid first (visa not granted), then partial, then paid
        usort($items, function ($a, $b) {
            // Get current paid amounts from database
            if ($a['is_dependency'] ?? false) {
                $aItem = \App\Models\Reception\ItemDependency::find($a['item_dependency_id']);
                $aPaid = $aItem ? $aItem->paid_amount : 0;
                $aRemaining = $aItem ? ($aItem->final_price - $aItem->paid_amount) : 0;
            } else {
                $aItem = ficheNavetteItem::find($a['fiche_navette_item_id']);
                $aPaid = $aItem ? $aItem->paid_amount : 0;
                $aRemaining = $aItem ? ($aItem->final_price - $aItem->paid_amount) : 0;
            }

            if ($b['is_dependency'] ?? false) {
                $bItem = \App\Models\Reception\ItemDependency::find($b['item_dependency_id']);
                $bPaid = $bItem ? $bItem->paid_amount : 0;
                $bRemaining = $bItem ? ($bItem->final_price - $bItem->paid_amount) : 0;
            } else {
                $bItem = ficheNavetteItem::find($b['fiche_navette_item_id']);
                $bPaid = $bItem ? $bItem->paid_amount : 0;
                $bRemaining = $bItem ? ($bItem->final_price - $bItem->paid_amount) : 0;
            }

            // Priority 1: Completely unpaid items (visa not granted) - paid_amount = 0
            $aIsUnpaid = $aPaid == 0 && $aRemaining > 0;
            $bIsUnpaid = $bPaid == 0 && $bRemaining > 0;

            // Priority 2: Partial payments - paid_amount > 0 but remaining > 0
            $aIsPartial = $aPaid > 0 && $aRemaining > 0;
            $bIsPartial = $bPaid > 0 && $bRemaining > 0;

            // Priority 3: Fully paid items - remaining = 0
            $aIsPaid = $aRemaining <= 0;
            $bIsPaid = $bRemaining <= 0;

            // Sort: unpaid first, then partial, then paid
            if ($aIsUnpaid && ! $bIsUnpaid) {
                return -1;
            }
            if ($bIsUnpaid && ! $aIsUnpaid) {
                return 1;
            }
            if ($aIsPartial && ! $bIsPartial && ! $bIsUnpaid) {
                return -1;
            }
            if ($bIsPartial && ! $aIsPartial && ! $aIsUnpaid) {
                return 1;
            }

            return 0;
        });
        $amountForPayments = min($totalAmount, $totalOutstanding);
        $remainingAmount = $amountForPayments;

        // Process each item payment
        foreach ($items as $item) {
            if ($remainingAmount <= 0) {
                break;
            }

            $amountForThisItem = min($item['amount'], $remainingAmount);

            // Create individual payment transaction
            $paymentData = [
                'fiche_navette_item_id' => $item['fiche_navette_item_id'],
                'patient_id' => $patientId,
                'cashier_id' => $cashierId,
                'amount' => $amountForThisItem,
                'transaction_type' => 'payment',
                'payment_method' => $paymentMethod,
                'notes' => $notes.' - Payment for '.($item['item_name'] ?? 'item'),
                'is_bank_transaction' => $isBankTransaction,
                'bank_id_account' => $bankIdAccount,
            ];

            // Add dependency info if applicable
            if ($item['is_dependency'] ?? false) {
                $paymentData['item_dependency_id'] = $item['item_dependency_id'];
            }

            try {
                $result = $this->processPaymentTransaction($paymentData);
                $processedTransactions[] = $result['transaction'];
                $updatedItems = array_merge($updatedItems, $result['updated_items']);
                $remainingAmount -= $amountForThisItem;
            } catch (\Exception $e) {
                // If any payment fails, throw exception to rollback the entire transaction
                throw new \Exception("Payment failed for item {$item['item_name']}: ".$e->getMessage());
            }
        }

        // Step 2: Handle excess amount as donation if totalAmount > totalOutstanding
        $excessAmount = $totalAmount - $totalOutstanding;
        if ($excessAmount > 0) {
            // Create donation transaction for the excess amount
            $donationTransaction = FinancialTransaction::create([
                'fiche_navette_item_id' => null, // Standalone donation - not attached to specific item
                'patient_id' => $patientId,
                'cashier_id' => $cashierId,
                'amount' => $excessAmount,
                'transaction_type' => 'donation',
                'payment_method' => $paymentMethod,
                'notes' => $notes ? "Global payment donation: {$notes}" : 'Global payment excess donated',
                'is_bank_transaction' => $isBankTransaction,
                'bank_id_account' => $bankIdAccount,
            ]);

            $processedTransactions[] = $donationTransaction;
            $results['donation_transaction'] = $donationTransaction;
            $results['excess_amount'] = $excessAmount;
            $results['action'] = 'donated';
        }

        $results['transactions'] = $processedTransactions;
        $results['updated_items'] = $updatedItems;
        $results['total_processed'] = count($processedTransactions);
        $results['amount_processed'] = $totalAmount;
        $results['payments_amount'] = $amountForPayments;
        $results['remaining_amount'] = 0; // All amount is processed (either payment or donation)

        return $results;
    }

    public function getTransactionStats(?int $ficheNavetteItemId = null): array
    {
        $query = FinancialTransaction::query();

        if ($ficheNavetteItemId) {
            $query->byFicheNavetteItem($ficheNavetteItemId);
        }

        $stats = [
            'total_transactions' => $query->count(),
            'total_payments' => $query->payments()->sum('amount'),
            'total_refunds' => $query->refunds()->sum('amount'),
            'net_amount' => $query->payments()->sum('amount') - $query->refunds()->sum('amount'),
            'by_payment_method' => [],
            'by_transaction_type' => [],
        ];

        // Group by payment method
        $paymentMethods = FinancialTransaction::selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
            ->when($ficheNavetteItemId, function ($q) use ($ficheNavetteItemId) {
                $q->byFicheNavetteItem($ficheNavetteItemId);
            })
            ->groupBy('payment_method')
            ->get();

        foreach ($paymentMethods as $method) {
            $stats['by_payment_method'][$method->payment_method] = [
                'count' => $method->count,
                'total' => $method->total,
            ];
        }

        // Group by transaction type
        $transactionTypes = FinancialTransaction::selectRaw('transaction_type, COUNT(*) as count, SUM(amount) as total')
            ->when($ficheNavetteItemId, function ($q) use ($ficheNavetteItemId) {
                $q->byFicheNavetteItem($ficheNavetteItemId);
            })
            ->groupBy('transaction_type')
            ->get();

        foreach ($transactionTypes as $type) {
            $stats['by_transaction_type'][$type->transaction_type] = [
                'count' => $type->count,
                'total' => $type->total,
            ];
        }

        return $stats;
    }

    /**
     * Process overpayment by handling excess amount through donation or patient balance
     */
    public function processOverpayment(
        int $ficheNavetteItemId,
        int $patientId,
        int $cashierId,
        float $requiredAmount,
        float $paidAmount,
        string $paymentMethod,
        string $overpaymentAction,
        ?string $notes = null,
        ?int $itemDependencyId = null,
        ?int $dependentPrestationId = null
    ): array {
        return DB::transaction(function () use (
            $ficheNavetteItemId,
            $patientId,
            $cashierId,
            $requiredAmount,
            $paidAmount,
            $paymentMethod,
            $overpaymentAction,
            $notes,
            $itemDependencyId,
            $dependentPrestationId
        ) {
            $excessAmount = $paidAmount - $requiredAmount;

            if ($excessAmount <= 0) {
                throw new InvalidArgumentException('No overpayment detected. Paid amount must exceed required amount.');
            }

            $results = [];

            // Always process the full payment first to update paid_amount and remaining_amount
            $fullPaymentData = [
                'fiche_navette_item_id' => $ficheNavetteItemId,
                'patient_id' => $patientId,
                'cashier_id' => $cashierId,
                'amount' => $paidAmount, // Use full paid amount
                'transaction_type' => 'payment',
                'payment_method' => $paymentMethod,
                'notes' => $notes ? "Full payment: {$notes}" : "Full payment for fiche item {$ficheNavetteItemId}",
            ];

            // Add dependency information if provided
            if ($itemDependencyId) {
                $fullPaymentData['item_dependency_id'] = $itemDependencyId;
            }
            if ($dependentPrestationId) {
                $fullPaymentData['dependent_prestation_id'] = $dependentPrestationId;
            }

            $paymentResult = $this->processPaymentTransaction($fullPaymentData);
            $results['payment_transaction'] = $paymentResult['transaction'];
            $results['updated_items'] = $paymentResult['updated_items'];

            // Handle the excess amount based on the action
            if ($overpaymentAction === 'donate') {
                // Create a donation transaction as a standalone donation (not attached to specific item)
                $donationTransaction = FinancialTransaction::create([
                    'fiche_navette_item_id' => null, // Don't attach donations to specific items
                    'patient_id' => $patientId,
                    'cashier_id' => $cashierId,
                    'amount' => $excessAmount,
                    'transaction_type' => 'donation',
                    'payment_method' => $paymentMethod,
                    'notes' => $notes ? "Donation: {$notes}" : 'Donation of excess payment amount',
                ]);

                $results['donation_transaction'] = $donationTransaction;
                $results['action'] = 'donated';
                $results['excess_amount'] = $excessAmount;

            } elseif ($overpaymentAction === 'balance') {
                // Add excess amount to patient balance
                $patient = \App\Models\Patient::findOrFail($patientId);
                $oldBalance = $patient->balance ?? 0;
                $newBalance = $oldBalance + $excessAmount;

                $patient->update(['balance' => $newBalance]);

                // Create a credit transaction to track this balance addition
                $creditTransaction = FinancialTransaction::create([
                    'fiche_navette_item_id' => null, // Don't attach balance credits to specific items
                    'patient_id' => $patientId,
                    'cashier_id' => $cashierId,
                    'amount' => $excessAmount,
                    'transaction_type' => 'credit',
                    'payment_method' => $paymentMethod,
                    'notes' => $notes ? "Credit: {$notes}" : 'Patient balance credit from excess payment',
                ]);

                $results['credit_transaction'] = $creditTransaction;
                $results['patient_balance'] = [
                    'old_balance' => $oldBalance,
                    'new_balance' => $newBalance,
                    'credit_amount' => $excessAmount,
                ];
                $results['action'] = 'credited';
                $results['excess_amount'] = $excessAmount;

            } else {
                throw new InvalidArgumentException("Invalid overpayment action: {$overpaymentAction}. Must be 'donate' or 'balance'.");
            }

            return $results;
        });
    }

    /**
     * Get refundable transactions for a fiche navette
     */
    public function getRefundableTransactions(int $ficheNavetteId): Collection
    {
        return FinancialTransaction::with([
            'ficheNavetteItem',
            'patient',
            'cashier',
        ])
            ->whereHas('ficheNavetteItem', function ($q) use ($ficheNavetteId) {
                $q->where('fiche_navette_id', $ficheNavetteId);
            })
            ->where('transaction_type', 'payment')
            ->where('amount', '>', 0)
            ->latest('created_at')
            ->get();
    }

    /**
     * Delete a financial transaction
     */
    public function delete(FinancialTransaction $transaction): bool
    {
        return DB::transaction(function () use ($transaction) {
            // Reverse the transaction effects before deleting
            if ($transaction->transaction_type === 'payment') {
                $this->reversePaymentTransaction($transaction);
            } elseif ($transaction->transaction_type === 'refund') {
                $this->reverseRefundTransaction($transaction);
            }

            return $transaction->delete();
        });
    }

    /**
     * Reverse payment transaction effects
     */
    private function reversePaymentTransaction(FinancialTransaction $transaction): void
    {
        $ficheNavetteItemId = $transaction->fiche_navette_item_id;
        $dependencyId = $transaction->item_dependency_id;
        $amount = $transaction->amount;

        if ($dependencyId) {
            $dependency = ItemDependency::find($dependencyId);
            if ($dependency) {
                $dependency->update([
                    'paid_amount' => max(0, ($dependency->paid_amount ?? 0) - $amount),
                    'remaining_amount' => ($dependency->final_price ?? 0) - max(0, ($dependency->paid_amount ?? 0) - $amount),
                    'payment_status' => (($dependency->final_price ?? 0) - max(0, ($dependency->paid_amount ?? 0) - $amount) <= 0) ? 'paid' : 'pending',
                ]);
            }
        } elseif ($ficheNavetteItemId) {
            $ficheItem = ficheNavetteItem::find($ficheNavetteItemId);
            if ($ficheItem) {
                $ficheItem->update([
                    'paid_amount' => max(0, ($ficheItem->paid_amount ?? 0) - $amount),
                    'remaining_amount' => ($ficheItem->final_price ?? 0) - max(0, ($ficheItem->paid_amount ?? 0) - $amount),
                    'payment_status' => (($ficheItem->final_price ?? 0) - max(0, ($ficheItem->paid_amount ?? 0) - $amount) <= 0) ? 'paid' : 'pending',
                ]);
            }
        }
    }

    /**
     * Reverse refund transaction effects
     */
    private function reverseRefundTransaction(FinancialTransaction $transaction): void
    {
        $ficheNavetteItemId = $transaction->fiche_navette_item_id;
        $dependencyId = $transaction->item_dependency_id;
        $amount = abs((float) $transaction->amount); // Refunds are stored as negative, but we need positive for reversal

        if ($dependencyId) {
            $dependency = ItemDependency::find($dependencyId);
            if ($dependency) {
                $dependency->update([
                    'paid_amount' => ($dependency->paid_amount ?? 0) + $amount,
                    'remaining_amount' => max(0, ($dependency->final_price ?? 0) - (($dependency->paid_amount ?? 0) + $amount)),
                    'payment_status' => (($dependency->final_price ?? 0) - (($dependency->paid_amount ?? 0) + $amount) <= 0) ? 'paid' : 'pending',
                ]);
            }
        } elseif ($ficheNavetteItemId) {
            $ficheItem = ficheNavetteItem::find($ficheNavetteItemId);
            if ($ficheItem) {
                $ficheItem->update([
                    'paid_amount' => ($ficheItem->paid_amount ?? 0) + $amount,
                    'remaining_amount' => max(0, ($ficheItem->final_price ?? 0) - (($ficheItem->paid_amount ?? 0) + $amount)),
                    'payment_status' => (($ficheItem->final_price ?? 0) - (($ficheItem->paid_amount ?? 0) + $amount) <= 0) ? 'paid' : 'pending',
                ]);
            }
        }
    }

    /**
     * Update prestation price and recalculate amounts
     */
    public function updatePrestationPrice(int $prestationId, int $ficheNavetteItemId, float $newFinalPrice, float $paidAmount): array
    {
        return DB::transaction(function () use ($ficheNavetteItemId, $newFinalPrice, $paidAmount) {
            $ficheItem = ficheNavetteItem::find($ficheNavetteItemId);
            if (! $ficheItem) {
                throw new \Exception('Fiche navette item not found');
            }

            $oldFinalPrice = $ficheItem->final_price ?? 0;
            $oldPaidAmount = $ficheItem->paid_amount ?? 0;

            // Update the final price
            $ficheItem->update([
                'final_price' => $newFinalPrice,
                'paid_amount' => $paidAmount,
                'remaining_amount' => max(0, $newFinalPrice - $paidAmount),
                'payment_status' => ($newFinalPrice - $paidAmount <= 0) ? 'paid' : 'pending',
            ]);

            // Update dependencies if any
            $updatedDependencies = [];
            foreach ($ficheItem->dependencies as $dependency) {
                // Recalculate dependency amounts proportionally
                $oldDependencyPrice = $dependency->final_price ?? 0;
                $priceRatio = $oldFinalPrice > 0 ? $newFinalPrice / $oldFinalPrice : 1;
                $newDependencyPrice = $oldDependencyPrice * $priceRatio;

                $dependency->update([
                    'final_price' => $newDependencyPrice,
                    'remaining_amount' => max(0, $newDependencyPrice - ($dependency->paid_amount ?? 0)),
                    'payment_status' => ($newDependencyPrice - ($dependency->paid_amount ?? 0) <= 0) ? 'paid' : 'pending',
                ]);

                $updatedDependencies[] = $dependency->fresh();
            }

            return [
                'fiche_navette_item' => $ficheItem->fresh(),
                'dependencies' => $updatedDependencies,
                'old_final_price' => $oldFinalPrice,
                'new_final_price' => $newFinalPrice,
            ];
        });
    }
}
