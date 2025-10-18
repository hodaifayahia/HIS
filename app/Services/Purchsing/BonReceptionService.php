<?php

namespace App\Services\Purchsing;

use App\Models\BonCommend;
use App\Models\BonReception;
use App\Models\BonReceptionItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BonReceptionService
{
    /**
     * Create bon reception with items + attachments
     */
    public function create(array $data): BonReception
    {
        return DB::transaction(function () use ($data) {

            $bonCommend = ! empty($data['bon_commend_id'])
                ? BonCommend::with('items')->findOrFail($data['bon_commend_id'])
                : null;

            $attachmentsData = $this->processAttachments($data);

            $bonReception = BonReception::create([
                'bon_commend_id' => $bonCommend?->id,
                'bon_commend_date' => $bonCommend?->created_at?->toDateString(),
                'fournisseur_id' => $bonCommend?->fournisseur_id ?? $data['fournisseur_id'],
                'received_by' => $data['received_by'],
                'created_by' => Auth::id(),
                'date_reception' => $data['date_reception'],
                'bon_livraison_numero' => $data['bon_livraison_numero'] ?? null,
                'bon_livraison_date' => $data['bon_livraison_date'] ?? null,
                'facture_numero' => $data['facture_numero'] ?? null,
                'facture_date' => $data['facture_date'] ?? null,
                'nombre_colis' => $data['nombre_colis'] ?? 0,
                'observation' => $data['observation'] ?? null,
                'status' => 'pending',
                'attachments' => $attachmentsData,
            ]);

            $this->addItems($bonReception, $data['items'], $bonCommend);

            return $bonReception->load(['fournisseur', 'bonCommend', 'items.product']);
        });
    }

    /**
     * Update bon reception and its items + attachments
     */
    public function update(int $id, array $data): BonReception
    {
        return DB::transaction(function () use ($id, $data) {
            $bonReception = BonReception::findOrFail($id);

            if ($bonReception->status !== 'pending') {
                throw new \Exception('Only pending receptions can be updated');
            }

            $attachmentsData = $this->processAttachments($data, $bonReception);

            $bonReception->update([
                'bon_commend_id' => $data['bon_commend_id'] ?? $bonReception->bon_commend_id,
                'received_by' => $data['received_by'],
                'date_reception' => $data['date_reception'],
                'bon_livraison_numero' => $data['bon_livraison_numero'] ?? null,
                'bon_livraison_date' => $data['bon_livraison_date'] ?? null,
                'facture_numero' => $data['facture_numero'] ?? null,
                'facture_date' => $data['facture_date'] ?? null,
                'nombre_colis' => $data['nombre_colis'] ?? 0,
                'observation' => $data['observation'] ?? null,
                'attachments' => $attachmentsData ?? $bonReception->attachments,
            ]);

            // reset + recreate items
            $bonReception->items()->delete();
            $bonCommend = $bonReception->bonCommend;
            $this->addItems($bonReception, $data['items'], $bonCommend);

            return $bonReception->fresh()->load(['fournisseur', 'bonCommend', 'items.product']);
        });
    }

    /**
     * Insert items
     */
    protected function addItems(BonReception $bonReception, array $items, ?BonCommend $bonCommend = null): void
    {
        foreach ($items as $itemData) {
            $bonCommendItem = $bonCommend
                ? $bonCommend->items()->where('product_id', $itemData['product_id'])->first()
                : null;

            $ordered = $bonCommendItem->quantity_desired ?? 0;
            $received = $itemData['quantity_received'];
            $surplus = max(0, $received - $ordered);
            $shortage = max(0, $ordered - $received);

            $status = 'pending';
            if ($received == $ordered && $ordered > 0) {
                $status = 'received';
            } elseif ($received > 0 && $received < $ordered) {
                $status = 'partial';
            } elseif ($surplus > 0) {
                $status = 'excess';
            } elseif ($shortage > 0) {
                $status = 'missing';
            }

            BonReceptionItem::create([
                'bon_reception_id' => $bonReception->id,
                'bon_commend_item_id' => $bonCommendItem?->id,
                'product_id' => $itemData['product_id'],
                'quantity_ordered' => $ordered,
                'quantity_received' => $received,
                'quantity_surplus' => $surplus,
                'quantity_shortage' => $shortage,
                'unit' => $bonCommendItem?->unit ?? 'piece',
                'unit_price' => $itemData['unit_price'] ?? 0,
                'status' => $status,
                'notes' => $itemData['notes'] ?? null,
                'is_unexpected' => ! $bonCommendItem,
                'received_at' => now(),
            ]);
        }
    }

    /**
     * Process attachments into JSON array
     */
    protected function processAttachments(array $data, ?BonReception $existing = null): array
    {
        $attachmentsToKeep = $existing?->attachments ?? [];

        if (! empty($data['attachments'])) {
            foreach ($data['attachments'] as $att) {
                if (isset($att['file']) && $att['file'] instanceof \Illuminate\Http\UploadedFile) {
                    $file = $att['file'];
                    $path = $file->store('bon-reception-attachments', 'public');
                    $attachmentsToKeep[] = [
                        'path' => $path,
                        'name' => $att['name'] ?? $file->getClientOriginalName(),
                        'original_filename' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                        'description' => $att['description'] ?? null,
                        'uploaded_at' => now()->toDateTimeString(),
                    ];
                }
            }
        }

        return $attachmentsToKeep;
    }
}
