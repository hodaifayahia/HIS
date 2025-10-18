<?php

namespace App\Http\Controllers;

use App\Models\BonEntree;
use App\Models\BonEntreeItem;
use App\Models\BonReception;
use App\Models\Product;
use App\Models\CONFIGURATION\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BonEntreeController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = BonEntree::with(['fournisseur', 'bonReception', 'creator', 'items.product', 'service'])
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('fournisseur_id')) {
                $query->where('fournisseur_id', $request->fournisseur_id);
            }

            if ($request->filled('service_id')) {
                $query->where('service_id', $request->service_id);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('bon_entree_code', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhereHas('fournisseur', function ($fq) use ($search) {
                            $fq->where('company_name', 'like', "%{$search}%");
                        });
                });
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $bonEntrees = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'status' => 'success',
                'data' => $bonEntrees,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch bon entrees',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'bon_reception_id' => 'nullable|exists:bon_receptions,id',
            'fournisseur_id' => 'nullable|exists:fournisseurs,id',
            'service_id' => 'nullable|exists:services,id',
            'notes' => 'nullable|string',
            'attachments' => 'nullable|array',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.purchase_price' => 'nullable|numeric|min:0',
            'items.*.sell_price' => 'nullable|numeric|min:0',
            'items.*.tva' => 'nullable|numeric|min:0|max:100',
            'items.*.batch_number' => 'nullable|string|max:255',
            'items.*.serial_number' => 'nullable|string|max:255',
            'items.*.expiry_date' => 'nullable|date|after:today',
            'items.*.storage_name' => 'nullable|string|max:255',
            'items.*.boite_de' => 'nullable|integer|min:1',
            'items.*.by_box' => 'nullable|boolean',
            'items.*.qte_by_box' => 'nullable|integer|min:1',
            'items.*.remarks' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            // Create bon entree and store service_id directly
            $bonEntree = BonEntree::create([
                'bon_reception_id' => $request->bon_reception_id,
                'fournisseur_id' => $request->fournisseur_id,
                'service_id' => $request->service_id ?? null,
                'notes' => $request->notes,
                'created_by' => Auth::id(),
                'status' => 'draft',
                'attachments' => $request->attachments ?? [],
            ]);

            // Create items
            foreach ($request->items as $itemData) {
                $bonEntree->items()->create([
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'purchase_price' => $itemData['purchase_price'] ?? 0,
                    'sell_price' => $itemData['sell_price'] ?? 0,
                    'tva' => $itemData['tva'] ?? 0,
                    'batch_number' => $itemData['batch_number'] ?? null,
                    'serial_number' => $itemData['serial_number'] ?? null,
                    'expiry_date' => $itemData['expiry_date'] ?? null,
                    'storage_name' => $itemData['storage_name'] ?? null,
                    'boite_de' => $itemData['boite_de'] ?? 1,
                    'by_box' => $itemData['by_box'] ?? false,
                    'qte_by_box' => $itemData['qte_by_box'] ?? 1,
                    'remarks' => $itemData['remarks'] ?? null,
                    'created_by' => Auth::id(),
                ]);
            }

            // Calculate total
            $bonEntree->calculateTotal();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Bon entree created successfully',
                'data' => $bonEntree->load(['items.product', 'fournisseur', 'bonReception']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create bon entree',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $bonEntree = BonEntree::with([
                'items.product',
                'fournisseur',
                'bonReception',
                'creator',
                'service',
            ])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $bonEntree,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bon entree not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'bon_reception_id' => 'nullable|exists:bon_receptions,id',
            'fournisseur_id' => 'nullable|exists:fournisseurs,id',
            'service_id' => 'nullable|exists:services,id',
            'status' => ['nullable', Rule::in(['draft', 'validated', 'transferred', 'cancelled'])],
            'notes' => 'nullable|string',
            'attachments' => 'nullable|array',
            'items' => 'nullable|array',
            'items.*.id' => 'nullable|exists:bon_entree_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.purchase_price' => 'nullable|numeric|min:0',
            'items.*.sell_price' => 'nullable|numeric|min:0',
            'items.*.tva' => 'nullable|numeric|min:0|max:100',
            'items.*.batch_number' => 'nullable|string|max:255',
            'items.*.serial_number' => 'nullable|string|max:255',
            'items.*.expiry_date' => 'nullable|date|after:today',
        ]);

        DB::beginTransaction();

        try {
            $bonEntree = BonEntree::findOrFail($id);

            // Check if bon entree can be updated
            if ($bonEntree->status === 'transferred') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot update transferred bon entree',
                ], 422);
            }

            // Map service_id -> service_abv if provided
            $updateData = $request->only([
                'bon_reception_id',
                'fournisseur_id',
                'service_id',
                'status',
                'notes',
                'attachments',
            ]);
            // Keep service_id on the model; no mapping to service_abv required
            // (BonEntree.model uses service_id as canonical reference)

            // Update bon entree
            $bonEntree->update($updateData);

            // Update items if provided
            if ($request->has('items')) {
                // Get existing item IDs
                $existingItemIds = $bonEntree->items()->pluck('id')->toArray();
                $providedItemIds = collect($request->items)->pluck('id')->filter()->toArray();

                // Delete items not in the request
                $itemsToDelete = array_diff($existingItemIds, $providedItemIds);
                if (! empty($itemsToDelete)) {
                    BonEntreeItem::whereIn('id', $itemsToDelete)->delete();
                }

                // Update or create items
                foreach ($request->items as $itemData) {
                    if (isset($itemData['id'])) {
                        // Update existing item
                        $item = BonEntreeItem::find($itemData['id']);
                        if ($item && $item->bon_entree_id === $bonEntree->id) {
                            $item->update($itemData);
                        }
                    } else {
                        // Create new item
                        $bonEntree->items()->create(array_merge($itemData, [
                            'created_by' => Auth::id(),
                        ]));
                    }
                }
            }

            // Recalculate total
            $bonEntree->calculateTotal();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Bon entree updated successfully',
                'data' => $bonEntree->load(['items.product', 'fournisseur', 'bonReception']),
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update bon entree',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $bonEntree = BonEntree::findOrFail($id);

            // Check if bon entree can be deleted
            if ($bonEntree->status === 'transferred') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete transferred bon entree',
                ], 422);
            }

            $bonEntree->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Bon entree deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete bon entree',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Additional methods
    public function validate($id)
    {
        try {
            $bonEntree = BonEntree::findOrFail($id);

            if (($bonEntree->status !== 'Draft')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only draft bon entrees can be validated',
                ], 422);
            }

            $bonEntree->update(['status' => 'validated']);

            return response()->json([
                'status' => 'success',
                'message' => 'Bon entree validated successfully',
                'data' => $bonEntree,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to validate bon entree',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function validateBonEntree($id)
    {
        return $this->validate($id);
    }

    public function transfer($id)
    {
        DB::beginTransaction();

        try {
            $bonEntree = BonEntree::with('items.product')->findOrFail($id);
            if (strtolower($bonEntree->status) !== 'validated') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only validated bon entrees can be transferred to stock',
                ], 422);
            }

            // Ensure we have a service_id (canonical). Older records may have only
            // a service_abv populated; try to resolve that to an id and persist it.
            $serviceId = $bonEntree->service_id ?? $bonEntree->getAttribute('service_id');

            if (! $serviceId) {
                // Try to resolve from a legacy service_abv field if present
                $serviceAbv = $bonEntree->getAttribute('service_abv') ?? null;
                if ($serviceAbv) {
                    $svc = Service::where('service_abv', $serviceAbv)
                        ->orWhere('name', $serviceAbv)
                        ->first();
                    if ($svc) {
                        $serviceId = $svc->id;
                        // persist resolution so subsequent operations can rely on it
                        $bonEntree->update(['service_id' => $serviceId]);
                    }
                }
            }

            if (! $serviceId) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Service not set on bon entree. Please assign a service before transferring to stock',
                ], 422);
            }

            // Verify service exists
            $service = Service::find($serviceId);
            if (! $service) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Referenced service not found. Please check the assigned service',
                ], 422);
            }

            // Transfer each item to stock. Handle per-item failures explicitly so we
            // return a helpful error instead of bubbling an exception.
            foreach ($bonEntree->items as $item) {
                try {
                    $item->updateStock();
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Failed to transfer one or more items to stock',
                        'error' => $e->getMessage(),
                        'item_id' => $item->id,
                    ], 500);
                }
            }

            $bonEntree->update(['status' => 'transferred']);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Bon entree transferred to stock successfully',
                'data' => $bonEntree,
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to transfer bon entree to stock',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function transferToStock($id)
    {
        return $this->transfer($id);
    }

    public function cancel($id)
    {
        try {
            $bonEntree = BonEntree::findOrFail($id);

            if ($bonEntree->status === 'transferred') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot cancel transferred bon entree',
                ], 422);
            }

            $bonEntree->update(['status' => 'cancelled']);

            return response()->json([
                'status' => 'success',
                'message' => 'Bon entree cancelled successfully',
                'data' => $bonEntree,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to cancel bon entree',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function createFromBonReception(Request $request)
    {
        $request->validate([
            'bon_reception_id' => 'required|exists:bon_receptions,id',
            'service_id' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $bonReception = BonReception::with(['items.product', 'fournisseur'])->findOrFail($request->bon_reception_id);

            // Check if bon entree already exists for this reception
            $existingBonEntree = BonEntree::where('bon_reception_id', $bonReception->id)->first();
            if ($existingBonEntree) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Bon entree already exists for this reception',
                ], 422);
            }

            // Create bon entree and store service_id directly (if provided)
            $bonEntree = BonEntree::create([
                'bon_reception_id' => $bonReception->id,
                'fournisseur_id' => $bonReception->fournisseur_id,
                'service_id' => $request->service_id ?? null,
                'created_by' => Auth::id(),
                'status' => 'draft',
            ]);

            // Create items from reception items
            foreach ($bonReception->items as $receptionItem) {
                $bonEntree->items()->create([
                    'product_id' => $receptionItem->product_id,
                    'quantity' => $receptionItem->quantity_received ?? $receptionItem->quantity,
                    'purchase_price' => $receptionItem->unit_price ?? 0,
                    'sell_price' => 0,
                    'tva' => 0,
                    'created_by' => Auth::id(),
                ]);
            }

            // Calculate total
            $bonEntree->calculateTotal();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Bon entree created from reception successfully',
                'data' => $bonEntree->load(['items.product', 'fournisseur', 'bonReception']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create bon entree from reception',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Meta endpoints
    public function getProducts()
    {
        try {
            $products = Product::select('id', 'name', 'product_code', 'unit')
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $products,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch products',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getBonReceptions()
    {
        try {
            $bonReceptions = BonReception::with('fournisseur')
                ->where('status', 'validated')
                ->whereDoesntHave('bonEntrees')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $bonReceptions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch bon receptions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getServices()
    {
        try {
            $services = \App\Models\CONFIGURATION\Service::select('id', 'name as service_name')
                ->selectRaw('COALESCE(service_id, id) as service_id')
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $services,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch services',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function addItem(Request $request, $id)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'purchase_price' => 'nullable|numeric|min:0',
            'sell_price' => 'nullable|numeric|min:0',
            'tva' => 'nullable|numeric|min:0|max:100',
            'batch_number' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'expiry_date' => 'nullable|date|after:today',
        ]);

        try {
            $bonEntree = BonEntree::findOrFail($id);

            if ($bonEntree->status === 'transferred') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot add items to transferred bon entree',
                ], 422);
            }

            $item = $bonEntree->items()->create(array_merge($request->all(), [
                'created_by' => Auth::id(),
            ]));

            $bonEntree->calculateTotal();

            return response()->json([
                'status' => 'success',
                'message' => 'Item added successfully',
                'data' => $item->load('product'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateItem(Request $request, $id, $itemId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
            'purchase_price' => 'nullable|numeric|min:0',
            'sell_price' => 'nullable|numeric|min:0',
            'tva' => 'nullable|numeric|min:0|max:100',
            'batch_number' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'expiry_date' => 'nullable|date|after:today',
        ]);

        try {
            $bonEntree = BonEntree::findOrFail($id);
            $item = BonEntreeItem::where('bon_entree_id', $id)->findOrFail($itemId);
            dd($bonEntree, $item);
            if ($bonEntree->status === 'transferred') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot update items in transferred bon entree',
                ], 422);
            }

            $item->update($request->all());
            $bonEntree->calculateTotal();

            return response()->json([
                'status' => 'success',
                'message' => 'Item updated successfully',
                'data' => $item->load('product'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function removeItem($id, $itemId)
    {
        try {
            $bonEntree = BonEntree::findOrFail($id);
            $item = BonEntreeItem::where('bon_entree_id', $id)->findOrFail($itemId);

            if ($bonEntree->status === 'transferred') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot remove items from transferred bon entree',
                ], 422);
            }

            $item->delete();
            $bonEntree->calculateTotal();

            return response()->json([
                'status' => 'success',
                'message' => 'Item removed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to remove item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getFromBonReception($bonReceptionId)
    {
        try {
            $bonReception = BonReception::with(['items.product'])->findOrFail($bonReceptionId);

            return response()->json([
                'status' => 'success',
                'data' => $bonReception,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch bon reception',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
