<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\BonEntree;
use App\Models\BonEntreeItem;
use App\Models\BonReception;
use App\Models\CONFIGURATION\Service;
use App\Models\Product;
use App\Services\Inventory\ServiceGroupProductPricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BonEntreeController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = BonEntree::with(['fournisseur', 'bonReception', 'creator', 'items.product', 'items.pharmacyProduct', 'service'])
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

            // Ensure all items have complete product information
            $bonEntrees->getCollection()->transform(function ($bonEntree) {
                $bonEntree->items->each(function ($item) {
                    // Prioritize pharmacy_product if it exists
                    if ($item->pharmacyProduct) {
                        $item->product_name = $item->pharmacyProduct->name;
                        $item->product_code = $item->pharmacyProduct->product_code ?? '';
                        $item->category = $item->pharmacyProduct->category ?? '';
                        $item->unit = $item->unit ?? $item->pharmacyProduct->unit ?? 'unit';
                    } elseif ($item->product) {
                        $item->product_name = $item->product->name;
                        $item->product_code = $item->product->product_code;
                        $item->category = $item->product->category;
                        $item->unit = $item->unit ?? $item->product->unit ?? 'unit';
                    }
                });

                return $bonEntree;
            });

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
            // Each item must have either a product_id or a pharmacy_product_id (not both)
            'items.*.product_id' => 'nullable|exists:products,id|required_without:items.*.pharmacy_product_id',
            'items.*.pharmacy_product_id' => 'nullable|exists:pharmacy_products,id|required_without:items.*.product_id',
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
            'items.*.sub_items' => 'nullable|array',
            'items.*.sub_items.*.quantity' => 'required|integer|min:1',
            'items.*.sub_items.*.purchase_price' => 'nullable|numeric|min:0',
            'items.*.sub_items.*.unit' => 'nullable|string|max:50',
            'items.*.sub_items.*.batch_number' => 'nullable|string|max:255',
            'items.*.sub_items.*.serial_number' => 'nullable|string|max:255',
            'items.*.sub_items.*.expiry_date' => 'nullable|date|after:today',
        ]);

        // Validate that each item has either product_id OR pharmacy_product_id (not both, not neither)
        foreach ($request->items as $index => $itemData) {
            $hasProduct = ! empty($itemData['product_id']);
            $hasPharmacyProduct = ! empty($itemData['pharmacy_product_id']);

            if (! $hasProduct && ! $hasPharmacyProduct) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Item #{$index} must have either product_id or pharmacy_product_id",
                ], 422);
            }

            if ($hasProduct && $hasPharmacyProduct) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Item #{$index} cannot have both product_id and pharmacy_product_id",
                ], 422);
            }
        }

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
                    'product_id' => $itemData['product_id'] ?? null,
                    'pharmacy_product_id' => $itemData['pharmacy_product_id'] ?? null,
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
                    'sub_items' => $itemData['sub_items'] ?? null, // Save sub_items array
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
                'items.product.inventory',  // Load regular product inventory
                'items.pharmacyProduct.inventory',  // Load pharmacy product inventory
                'fournisseur',
                'bonReception.bonCommend.serviceDemand', // Load to get is_pharmacy_order flag
                'creator',
                'service',
            ])->findOrFail($id);

            // Determine if this is a pharmacy order
            $isPharmacyOrder = $bonEntree->bonReception
                ?->bonCommend
                ?->serviceDemand
                ?->is_pharmacy_order ?? false;

            // Ensure all items have complete product information
            $bonEntree->items->each(function ($item) {
                // For pharmacy orders, prioritize pharmacy_product
                if ($item->pharmacyProduct) {
                    $item->product_name = $item->pharmacyProduct->name;
                    $item->product_code = $item->pharmacyProduct->product_code ?? '';
                    $item->category = $item->pharmacyProduct->category ?? '';
                    $item->unit = $item->unit ?? $item->pharmacyProduct->unit ?? 'unit';
                } elseif ($item->product) {
                    // For regular products
                    $item->product_name = $item->product->name;
                    $item->product_code = $item->product->product_code;
                    $item->category = $item->product->category;
                    $item->unit = $item->unit ?? $item->product->unit ?? 'unit';
                }
            });

            // Add is_pharmacy_order flag to response
            $bonEntree->is_pharmacy_order = $isPharmacyOrder;

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
            // Each item may be a product OR a pharmacy product - at least one must be present
            'items.*.product_id' => 'nullable|exists:products,id|required_without:items.*.pharmacy_product_id',
            'items.*.pharmacy_product_id' => 'nullable|exists:pharmacy_products,id|required_without:items.*.product_id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.purchase_price' => 'nullable|numeric|min:0',
            'items.*.sell_price' => 'nullable|numeric|min:0',
            'items.*.tva' => 'nullable|numeric|min:0|max:100',
            'items.*.batch_number' => 'nullable|string|max:255',
            'items.*.serial_number' => 'nullable|string|max:255',
            'items.*.expiry_date' => 'nullable|date|after:today',
            'items.*.sub_items' => 'nullable|array',
            'items.*.sub_items.*.quantity' => 'required|integer|min:1',
            'items.*.sub_items.*.purchase_price' => 'nullable|numeric|min:0',
            'items.*.sub_items.*.unit' => 'nullable|string|max:50',
            'items.*.sub_items.*.batch_number' => 'nullable|string|max:255',
            'items.*.sub_items.*.serial_number' => 'nullable|string|max:255',
            'items.*.sub_items.*.expiry_date' => 'nullable|date|after:today',
        ]);

        // Validate that each item has either product_id OR pharmacy_product_id (not both, not neither)
        if ($request->has('items')) {
            foreach ($request->items as $index => $itemData) {
                $hasProduct = ! empty($itemData['product_id']);
                $hasPharmacyProduct = ! empty($itemData['pharmacy_product_id']);

                if (! $hasProduct && ! $hasPharmacyProduct) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Item #{$index} must have either product_id or pharmacy_product_id",
                    ], 422);
                }

                if ($hasProduct && $hasPharmacyProduct) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Item #{$index} cannot have both product_id and pharmacy_product_id",
                    ], 422);
                }
            }
        }

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
    public function validate(Request $request, $id)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'storage_id' => 'required|integer',
            'service_group_pricing' => 'nullable|array',
            'service_group_pricing.*.product_id' => 'nullable|integer',
            'service_group_pricing.*.pharmacy_product_id' => 'nullable|integer',
            'service_group_pricing.*.service_group_id' => 'required|integer|exists:service_groups,id',
            'service_group_pricing.*.price' => 'required|numeric|min:0',
            'service_group_pricing.*.is_pharmacy' => 'required|boolean',
        ]);

        DB::beginTransaction();

        try {
            $bonEntree = BonEntree::with([
                'bonReception.bonCommend.serviceDemand',
                'items.product',
            ])->findOrFail($id);

            if (strtolower($bonEntree->status) !== 'draft') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only draft bon entrees can be validated',
                ], 422);
            }

            // Determine if this is a pharmacy order
            $isPharmacyOrder = $bonEntree->bonReception
                ?->bonCommend
                ?->serviceDemand
                ?->is_pharmacy_order ?? false;

            $serviceId = $request->service_id;
            $storageId = $request->storage_id;

            // Verify the storage exists and belongs to the correct type and service
            if ($isPharmacyOrder) {
                $storage = \App\Models\PharmacyStockage::where('id', $storageId)
                    ->where('service_id', $serviceId)
                    ->where('status', 'active')
                    ->first();

                if (! $storage) {
                    DB::rollBack();

                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid pharmacy storage selected for this service',
                    ], 422);
                }
            } else {
                $storage = \App\Models\Stockage::where('id', $storageId)
                    ->where('service_id', $serviceId)
                    ->where('status', 'active')
                    ->first();

                if (! $storage) {
                    DB::rollBack();

                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid storage selected for this service',
                    ], 422);
                }
            }

            // Process service group pricing if provided
            if ($request->has('service_group_pricing') && ! empty($request->service_group_pricing)) {
                $pricingService = app(\App\Services\Inventory\ServiceGroupProductPricingService::class);

                foreach ($request->service_group_pricing as $pricingData) {
                    try {
                        $productId = $pricingData['is_pharmacy']
                            ? $pricingData['pharmacy_product_id']
                            : $pricingData['product_id'];

                        // Validate that we have a product ID
                        if (empty($productId)) {
                            \Log::warning('Skipping pricing update: no valid product ID', [
                                'pricing_data' => $pricingData,
                            ]);

                            continue;
                        }

                        // Find the corresponding item to get purchase price
                        $bonEntreeItem = $bonEntree->items->first(function ($item) use ($pricingData) {
                            if ($pricingData['is_pharmacy']) {
                                return $item->pharmacy_product_id == $pricingData['pharmacy_product_id'];
                            } else {
                                return $item->product_id == $pricingData['product_id'];
                            }
                        });

                        $purchasePrice = $bonEntreeItem ? $bonEntreeItem->purchase_price : null;

                        $pricingService->updatePrice(
                            $pricingData['service_group_id'],
                            $productId,
                            $pricingData['is_pharmacy'],
                            [
                                'selling_price' => $pricingData['price'],
                                'purchase_price' => $purchasePrice,
                                'vat_rate' => 0.00,
                                'notes' => 'Updated from Bon Entrée #'.$bonEntree->bon_entree_code,
                            ]
                        );
                    } catch (\Exception $e) {
                        \Log::warning('Failed to update service group pricing', [
                            'pricing_data' => $pricingData,
                            'error' => $e->getMessage(),
                        ]);
                        // Continue with other pricing updates even if one fails
                    }
                }
            }

            // Update bon entree with service_id and mark as validated
            $bonEntree->update([
                'status' => 'validated',
                'service_id' => $serviceId,
            ]);

            // Create inventory records in the appropriate table
            foreach ($bonEntree->items as $item) {
                // CRITICAL: Determine if this is a pharmacy product or regular product
                // pharmacy_product_id takes precedence
                $isPharmacyProduct = ! empty($item->pharmacy_product_id);

                if ($isPharmacyProduct && $isPharmacyOrder) {
                    // Pharmacy product → pharmacy_inventories table
                    $this->createOrUpdatePharmacyInventory($item, $storageId);
                } elseif (! $isPharmacyProduct && ! $isPharmacyOrder) {
                    // Regular product → inventories table
                    $this->createOrUpdateInventory($item, $storageId);
                } else {
                    // Mismatch: Pharmacy product in non-pharmacy order or vice versa
                    \Log::warning('Product type mismatch in bon entree', [
                        'item_id' => $item->id,
                        'has_pharmacy_product_id' => $isPharmacyProduct,
                        'is_pharmacy_order' => $isPharmacyOrder,
                    ]);

                    // Handle based on product type, not order type
                    if ($isPharmacyProduct) {
                        $this->createOrUpdatePharmacyInventory($item, $storageId);
                    } else {
                        $this->createOrUpdateInventory($item, $storageId);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Bon entree validated successfully and inventory updated',
                'data' => $bonEntree->fresh()->load(['items.product', 'service']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to validate bon entree',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create or update pharmacy inventory for a bon entree item
     * CRITICAL: Creates ONE ROW PER BATCH - each sub_item becomes a separate inventory row
     */
    private function createOrUpdatePharmacyInventory($item, $pharmacyStockageId)
    {
        // Get the pharmacy product ID
        $pharmacyProductId = $item->pharmacy_product_id;

        if (empty($pharmacyProductId)) {
            \Log::error('Missing pharmacy_product_id for pharmacy inventory', [
                'item_id' => $item->id,
                'product_id' => $item->product_id,
            ]);
            throw new \Exception('Item must have pharmacy_product_id for pharmacy inventory');
        }

        // CRITICAL: Handle sub_items (batches) - each sub_item becomes ONE SEPARATE ROW
        if (! empty($item->sub_items) && is_array($item->sub_items)) {
            \Log::info('Processing sub_items for pharmacy inventory', [
                'item_id' => $item->id,
                'pharmacy_product_id' => $pharmacyProductId,
                'sub_items_count' => count($item->sub_items),
            ]);

            // Create ONE ROW for EACH sub-item
            foreach ($item->sub_items as $index => $subItem) {
                $subQuantity = $subItem['quantity'] ?? 0;

                if ($subQuantity <= 0) {
                    \Log::warning('Skipping sub_item with zero or negative quantity', [
                        'sub_item_index' => $index,
                        'quantity' => $subQuantity,
                    ]);

                    continue;
                }

                // Create ONE ROW for this batch
                $this->createSinglePharmacyInventoryRow(
                    $pharmacyProductId,
                    $pharmacyStockageId,
                    $subQuantity,
                    $subItem['purchase_price'] ?? $item->purchase_price,
                    $subItem['batch_number'] ?? null,
                    $subItem['expiry_date'] ?? null,
                    $subItem['serial_number'] ?? null,
                    $subItem['unit'] ?? $item->unit ?? 'unit'
                );
            }

            // CRITICAL: Handle remaining quantity if some quantity is not allocated to sub-items
            $totalSubQuantity = array_sum(array_column($item->sub_items, 'quantity'));
            $remainingQuantity = $item->quantity - $totalSubQuantity;

            if ($remainingQuantity > 0) {
                \Log::info('Creating pharmacy inventory row for remaining unallocated quantity', [
                    'pharmacy_product_id' => $pharmacyProductId,
                    'total_quantity' => $item->quantity,
                    'allocated_to_subs' => $totalSubQuantity,
                    'remaining' => $remainingQuantity,
                ]);

                // Save the remaining quantity as ONE SEPARATE ROW with main item details
                $this->createSinglePharmacyInventoryRow(
                    $pharmacyProductId,
                    $pharmacyStockageId,
                    $remainingQuantity,
                    $item->purchase_price,
                    $item->batch_number, // Use main item's batch number
                    $item->expiry_date,   // Use main item's expiry date
                    $item->serial_number, // Use main item's serial number
                    $item->unit ?? 'unit'
                );
            }
        } else {
            // No sub_items: Process main item as ONE SINGLE ROW
            $this->createSinglePharmacyInventoryRow(
                $pharmacyProductId,
                $pharmacyStockageId,
                $item->quantity,
                $item->purchase_price,
                $item->batch_number,
                $item->expiry_date,
                $item->serial_number,
                $item->unit ?? 'unit'
            );
        }
    }

    /**
     * Create ONE SINGLE ROW in pharmacy_inventories table
     * CRITICAL: Always creates a new row - NEVER merges with existing inventory
     * Each batch (unique combination of batch_number, serial_number, expiry_date) = ONE ROW
     */
    private function createSinglePharmacyInventoryRow(
        $pharmacyProductId,
        $pharmacyStockageId,
        $quantity,
        $purchasePrice,
        $batchNumber = null,
        $expiryDate = null,
        $serialNumber = null,
        $unit = 'unit'
    ) {
        // Debug logging
        \Log::info('Creating NEW pharmacy inventory ROW (no merge)', [
            'pharmacy_product_id' => $pharmacyProductId,
            'stockage_id' => $pharmacyStockageId,
            'quantity' => $quantity,
            'batch_number' => $batchNumber,
            'serial_number' => $serialNumber,
            'expiry_date' => $expiryDate,
            'purchase_price' => $purchasePrice,
        ]);

        // ALWAYS CREATE NEW ROW - Generate unique barcode for this specific batch
        $barcode = $this->generateInventoryBarcode($pharmacyProductId, $batchNumber, $expiryDate);

        // Create new inventory record - ONE ROW PER BATCH
        $inventory = \App\Models\PharmacyInventory::create([
            'pharmacy_product_id' => $pharmacyProductId,
            'pharmacy_stockage_id' => $pharmacyStockageId,
            'quantity' => $quantity,
            'unit' => $unit,
            'batch_number' => $batchNumber,
            'serial_number' => $serialNumber,
            'expiry_date' => $expiryDate ? date('Y-m-d', strtotime($expiryDate)) : null,
            'purchase_price' => $purchasePrice,
            'barcode' => $barcode,
            'pharmacist_verified' => false,
            'quality_check_passed' => true,
        ]);

        \Log::info('✅ NEW pharmacy inventory ROW created', [
            'inventory_id' => $inventory->id,
            'pharmacy_product_id' => $pharmacyProductId,
            'stockage_id' => $pharmacyStockageId,
            'batch_number' => $batchNumber,
            'serial_number' => $serialNumber,
            'expiry_date' => $expiryDate,
            'purchase_price' => $purchasePrice,
            'quantity' => $quantity,
            'barcode' => $barcode,
        ]);

        return $inventory;
    }

    /**
     * Generate unique barcode for inventory item
     */
    private function generateInventoryBarcode($productId, $batchNumber, $expiryDate)
    {
        $batchPart = $batchNumber ?: 'NOBATCH';
        $expiryPart = $expiryDate ? date('dmY', strtotime($expiryDate)) : 'NOEXP';

        return $productId.'-'.$batchPart.'-'.$expiryPart;
    }

    /**
     * Create or update regular inventory for a bon entree item
     * CRITICAL: Creates ONE ROW PER BATCH - each sub_item becomes a separate inventory row
     */
    private function createOrUpdateInventory($item, $stockageId)
    {
        // Get the product ID
        $productId = $item->product_id;

        if (empty($productId)) {
            \Log::error('Missing product_id for regular inventory', [
                'item_id' => $item->id,
                'pharmacy_product_id' => $item->pharmacy_product_id,
            ]);
            throw new \Exception('Item must have product_id for regular inventory');
        }

        // CRITICAL: Handle sub_items (batches) - each sub_item becomes ONE SEPARATE ROW
        if (! empty($item->sub_items) && is_array($item->sub_items)) {
            \Log::info('Processing sub_items for regular inventory', [
                'item_id' => $item->id,
                'product_id' => $productId,
                'sub_items_count' => count($item->sub_items),
            ]);

            // Create ONE ROW for EACH sub-item
            foreach ($item->sub_items as $index => $subItem) {
                $subQuantity = $subItem['quantity'] ?? 0;

                if ($subQuantity <= 0) {
                    \Log::warning('Skipping sub_item with zero or negative quantity', [
                        'sub_item_index' => $index,
                        'quantity' => $subQuantity,
                    ]);

                    continue;
                }

                // Create ONE ROW for this batch
                $this->createSingleInventoryRow(
                    $productId,
                    $stockageId,
                    $subQuantity,
                    $subItem['purchase_price'] ?? $item->purchase_price,
                    $subItem['batch_number'] ?? null,
                    $subItem['expiry_date'] ?? null,
                    $subItem['serial_number'] ?? null,
                    $subItem['unit'] ?? $item->unit ?? 'unit'
                );
            }

            // CRITICAL: Handle remaining quantity if some quantity is not allocated to sub-items
            $totalSubQuantity = array_sum(array_column($item->sub_items, 'quantity'));
            $remainingQuantity = $item->quantity - $totalSubQuantity;

            if ($remainingQuantity > 0) {
                \Log::info('Creating inventory row for remaining unallocated quantity', [
                    'product_id' => $productId,
                    'total_quantity' => $item->quantity,
                    'allocated_to_subs' => $totalSubQuantity,
                    'remaining' => $remainingQuantity,
                ]);

                // Save the remaining quantity as ONE SEPARATE ROW with main item details
                $this->createSingleInventoryRow(
                    $productId,
                    $stockageId,
                    $remainingQuantity,
                    $item->purchase_price,
                    $item->batch_number, // Use main item's batch number
                    $item->expiry_date,   // Use main item's expiry date
                    $item->serial_number, // Use main item's serial number
                    $item->unit ?? 'unit'
                );
            }
        } else {
            // No sub_items: Process main item as ONE SINGLE ROW
            $this->createSingleInventoryRow(
                $productId,
                $stockageId,
                $item->quantity,
                $item->purchase_price,
                $item->batch_number,
                $item->expiry_date,
                $item->serial_number,
                $item->unit ?? 'unit'
            );
        }
    }

    /**
     * Create ONE SINGLE ROW in inventories table
     * CRITICAL: Always creates a new row - NEVER merges with existing inventory
     * Each batch (unique combination of batch_number, serial_number, expiry_date) = ONE ROW
     */
    private function createSingleInventoryRow(
        $productId,
        $stockageId,
        $quantity,
        $purchasePrice,
        $batchNumber = null,
        $expiryDate = null,
        $serialNumber = null,
        $unit = 'unit'
    ) {
        // Debug logging
        \Log::info('Creating NEW inventory ROW (no merge)', [
            'product_id' => $productId,
            'stockage_id' => $stockageId,
            'quantity' => $quantity,
            'batch_number' => $batchNumber,
            'serial_number' => $serialNumber,
            'expiry_date' => $expiryDate,
            'purchase_price' => $purchasePrice,
        ]);

        // ALWAYS CREATE NEW ROW
        $inventory = \App\Models\Inventory::create([
            'product_id' => $productId,
            'stockage_id' => $stockageId,
            'quantity' => $quantity,
            'total_units' => $quantity,
            'unit' => $unit,
            'batch_number' => $batchNumber,
            'serial_number' => $serialNumber,
            'expiry_date' => $expiryDate ? date('Y-m-d', strtotime($expiryDate)) : null,
            'purchase_price' => $purchasePrice,
            'quality_check_passed' => true,
        ]);

        \Log::info('✅ NEW inventory ROW created', [
            'inventory_id' => $inventory->id,
            'product_id' => $productId,
            'stockage_id' => $stockageId,
            'batch_number' => $batchNumber,
            'serial_number' => $serialNumber,
            'expiry_date' => $expiryDate,
            'purchase_price' => $purchasePrice,
            'quantity' => $quantity,
        ]);

        return $inventory;
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
            'service_id' => 'nullable|exists:services,id',
        ]);

        DB::beginTransaction();

        try {
            $bonReception = BonReception::with(['items.product', 'fournisseur', 'bonCommend.serviceDemand.service'])->findOrFail($request->bon_reception_id);

            // Check if bon entree already exists for this reception
            $existingBonEntree = BonEntree::where('bon_reception_id', $bonReception->id)->first();
            if ($existingBonEntree) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Bon entree already exists for this reception',
                ], 422);
            }

            // Auto-populate service_id from BonReception's BonCommend's ServiceDemand
            $serviceId = $request->service_id;
            if (! $serviceId && $bonReception->bonCommend) {
                $serviceId = $bonReception->bonCommend->serviceDemand?->service?->id;
            }

            // Create bon entree and store service_id directly (if provided or auto-populated)
            $bonEntree = BonEntree::create([
                'bon_reception_id' => $bonReception->id,
                'fournisseur_id' => $bonReception->fournisseur_id,
                'service_id' => $serviceId,
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
            $bonReceptions = BonReception::with(['fournisseur', 'bonCommend.serviceDemand.service'])
                ->where('status', 'validated')
                ->whereDoesntHave('bonEntrees')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($br) {
                    // Add service information to each reception for display
                    $br->service_id = $br->bonCommend?->serviceDemand?->service?->id;
                    $br->service_name = $br->bonCommend?->serviceDemand?->service?->name;

                    return $br;
                });

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

    /**
     * Get services with their storages (pharmacy or stock) based on bon_entree's order type
     * This traces: bon_entree -> bon_reception -> bon_commend -> service_demand -> is_pharmacy_order
     */
    public function getServicesWithStorages($bonEntreeId)
    {
        try {
            $bonEntree = BonEntree::with([
                'bonReception.bonCommend.serviceDemand',
            ])->findOrFail($bonEntreeId);

            // Determine if this is a pharmacy order
            $isPharmacyOrder = $bonEntree->bonReception
                ?->bonCommend
                ?->serviceDemand
                ?->is_pharmacy_order ?? false;

            // Get all active services with their appropriate storages
            $services = \App\Models\CONFIGURATION\Service::where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(function ($service) use ($isPharmacyOrder) {
                    if ($isPharmacyOrder) {
                        // Load pharmacy stockages for this service
                        $storages = \App\Models\PharmacyStockage::where('service_id', $service->id)
                            ->where('status', 'active')
                            ->select('id', 'name', 'type', 'location', 'capacity', 'temperature_controlled', 'security_level')
                            ->get();
                    } else {
                        // Load regular stockages for this service
                        $storages = \App\Models\Stockage::where('service_id', $service->id)
                            ->where('status', 'active')
                            ->select('id', 'name', 'type', 'location', 'capacity', 'temperature_controlled', 'security_level')
                            ->get();
                    }

                    return [
                        'id' => $service->id,
                        'name' => $service->name,
                        'service_abv' => $service->service_abv ?? '',
                        'is_pharmacy_order' => $isPharmacyOrder,
                        'storages' => $storages,
                    ];
                })
                ->filter(function ($service) {
                    // Only return services that have at least one storage
                    return $service['storages']->isNotEmpty();
                })
                ->values();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'services' => $services,
                    'is_pharmacy_order' => $isPharmacyOrder,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch services with storages',
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

    /**
     * Generate tickets/labels for all items in a bon entree
     * Each ticket is 40cm x 20cm
     * Creates one ticket per sub-item, or one ticket for main item if no sub-items
     */
    public function generateTickets($id)
    {
        try {
            $bonEntree = BonEntree::with([
                'items.product',
                'items.pharmacyProduct',
                'fournisseur',
                'service',
            ])->findOrFail($id);

            // Check if validated
            if ($bonEntree->status !== 'validated' && $bonEntree->status !== 'transferred') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only validated or transferred bon entrees can generate tickets',
                ], 422);
            }

            // Prepare tickets data
            $tickets = [];

            foreach ($bonEntree->items as $item) {
                // Get product info (pharmacy or regular)
                $product = $item->pharmacyProduct ?? $item->product;

                if (! $product) {
                    continue; // Skip items without product
                }

                $productName = $product->name;
                $productCode = $product->product_code ?? '';

                // Check if item has sub-items
                $subItems = is_array($item->sub_items) ? $item->sub_items : [];

                if (count($subItems) > 0) {
                    // CREATE ONE TICKET PER SUB-ITEM
                    foreach ($subItems as $index => $subItem) {
                        $tickets[] = [
                            'product_name' => $productName,
                            'product_code' => $productCode,
                            'quantity' => $subItem['quantity'] ?? 0,
                            'unit' => $subItem['unit'] ?? $item->unit ?? 'unit',
                            'batch_number' => $subItem['batch_number'] ?? '',
                            'serial_number' => $subItem['serial_number'] ?? '',
                            'expiry_date' => $subItem['expiry_date'] ?? null,
                            'purchase_price' => $subItem['purchase_price'] ?? $item->purchase_price,
                            'service' => $bonEntree->service->name ?? '',
                            'fournisseur' => $bonEntree->fournisseur->company_name ?? '',
                            'bon_entree_code' => $bonEntree->bon_entree_code,
                            'item_type' => 'sub_item',
                            'sub_item_index' => $index + 1,
                        ];
                    }
                } else {
                    // NO SUB-ITEMS: CREATE ONE TICKET FOR MAIN ITEM
                    $tickets[] = [
                        'product_name' => $productName,
                        'product_code' => $productCode,
                        'quantity' => $item->quantity,
                        'unit' => $item->unit ?? 'unit',
                        'batch_number' => $item->batch_number ?? '',
                        'serial_number' => $item->serial_number ?? '',
                        'expiry_date' => $item->expiry_date,
                        'purchase_price' => $item->purchase_price,
                        'service' => $bonEntree->service->name ?? '',
                        'fournisseur' => $bonEntree->fournisseur->company_name ?? '',
                        'bon_entree_code' => $bonEntree->bon_entree_code,
                        'item_type' => 'main_item',
                    ];
                }
            }

            // Generate HTML for tickets
            $html = $this->generateTicketsHtml($tickets);

            return response($html, 200)
                ->header('Content-Type', 'text/html');

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate tickets',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate HTML for printing tickets
     * Ticket size: 40cm x 20cm (400mm x 200mm)
     */
    private function generateTicketsHtml($tickets)
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Inventory Tickets</title>
            <style>
                @page {
                    size: 400mm 200mm;
                    margin: 0;
                }
                
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    font-family: Arial, sans-serif;
                    background: white;
                }
                
                .ticket {
                    width: 400mm;
                    height: 200mm;
                    padding: 15mm;
                    page-break-after: always;
                    border: 2px solid #333;
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                }
                
                .ticket:last-child {
                    page-break-after: auto;
                }
                
                .ticket-header {
                    border-bottom: 3px solid #2563eb;
                    padding-bottom: 8mm;
                    margin-bottom: 8mm;
                }
                
                .ticket-title {
                    font-size: 28pt;
                    font-weight: bold;
                    color: #1e40af;
                    margin-bottom: 5mm;
                }
                
                .ticket-code {
                    font-size: 16pt;
                    color: #64748b;
                }
                
                .ticket-body {
                    flex: 1;
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 10mm;
                }
                
                .ticket-section {
                    display: flex;
                    flex-direction: column;
                    gap: 5mm;
                }
                
                .field {
                    background: #f8fafc;
                    padding: 5mm;
                    border-radius: 3mm;
                    border-left: 4px solid #2563eb;
                }
                
                .field-label {
                    font-size: 10pt;
                    color: #64748b;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    margin-bottom: 2mm;
                }
                
                .field-value {
                    font-size: 18pt;
                    font-weight: bold;
                    color: #1e293b;
                    word-wrap: break-word;
                }
                
                .field-value.large {
                    font-size: 32pt;
                }
                
                .field-value.highlight {
                    color: #dc2626;
                }
                
                .ticket-footer {
                    border-top: 2px solid #e2e8f0;
                    padding-top: 8mm;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                
                .footer-info {
                    font-size: 12pt;
                    color: #64748b;
                }
                
                .barcode-placeholder {
                    background: #f1f5f9;
                    padding: 5mm;
                    border-radius: 3mm;
                    text-align: center;
                    font-family: monospace;
                    font-size: 14pt;
                }
                
                @media print {
                    body {
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }
                }
            </style>
        </head>
        <body>';

        foreach ($tickets as $index => $ticket) {
            $expiryDate = $ticket['expiry_date'] ? date('d/m/Y', strtotime($ticket['expiry_date'])) : 'N/A';
            $itemLabel = $ticket['item_type'] === 'sub_item'
                ? "Batch #{$ticket['sub_item_index']}"
                : 'Main Item';

            $html .= "
            <div class=\"ticket\">
                <div class=\"ticket-header\">
                    <div class=\"ticket-title\">{$ticket['product_name']}</div>
                    <div class=\"ticket-code\">Code: {$ticket['product_code']} | BE: {$ticket['bon_entree_code']}</div>
                </div>
                
                <div class=\"ticket-body\">
                    <div class=\"ticket-section\">
                        <div class=\"field\">
                            <div class=\"field-label\">Quantity</div>
                            <div class=\"field-value large highlight\">{$ticket['quantity']} {$ticket['unit']}</div>
                        </div>
                        
                        <div class=\"field\">
                            <div class=\"field-label\">Batch Number</div>
                            <div class=\"field-value\">".($ticket['batch_number'] ?: 'N/A').'</div>
                        </div>
                        
                        <div class="field">
                            <div class="field-label">Serial Number</div>
                            <div class="field-value">'.($ticket['serial_number'] ?: 'N/A')."</div>
                        </div>
                    </div>
                    
                    <div class=\"ticket-section\">
                        <div class=\"field\">
                            <div class=\"field-label\">Expiry Date</div>
                            <div class=\"field-value\">{$expiryDate}</div>
                        </div>
                        
                        <div class=\"field\">
                            <div class=\"field-label\">Purchase Price</div>
                            <div class=\"field-value\">".number_format($ticket['purchase_price'], 2)." DZD</div>
                        </div>
                        
                        <div class=\"field\">
                            <div class=\"field-label\">Type</div>
                            <div class=\"field-value\">{$itemLabel}</div>
                        </div>
                    </div>
                </div>
                
                <div class=\"ticket-footer\">
                    <div class=\"footer-info\">
                        <strong>Service:</strong> {$ticket['service']}<br>
                        <strong>Supplier:</strong> {$ticket['fournisseur']}
                    </div>
                    <div class=\"barcode-placeholder\">
                        ".($ticket['batch_number'] ?: $ticket['product_code']).'
                    </div>
                </div>
            </div>';
        }

        $html .= '
        </body>
        </html>';

        return $html;
    }

    /**
     * Get product pricing information from Bon d'Entrée history.
     */
    public function getProductPricingInfo(Request $request, $productId)
    {
        try {
            $isPharmacy = $request->boolean('is_pharmacy', false);

            $pricingService = new ServiceGroupProductPricingService;
            $pricingInfo = $pricingService->getProductPricingInfoFromBonEntree($productId, $isPharmacy);

            return response()->json([
                'success' => true,
                'data' => $pricingInfo,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get product pricing info: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get statistics for Bon d'Entrée list.
     */
    public function getStats()
    {
        try {
            $totalDraft = BonEntree::where('status', 'draft')->count();
            $totalPending = BonEntree::where('status', 'pending')->count();
            $totalValidated = BonEntree::where('status', 'validated')->count();
            $totalCancelled = BonEntree::where('status', 'cancelled')->count();
            $totalTransferred = BonEntree::where('status', 'transferred')->count();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'draft' => $totalDraft,
                    'pending' => $totalPending,
                    'validated' => $totalValidated,
                    'cancelled' => $totalCancelled,
                    'transferred' => $totalTransferred,
                    'total' => $totalDraft + $totalPending + $totalValidated + $totalCancelled + $totalTransferred,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get statistics: '.$e->getMessage(),
            ], 500);
        }
    }
}
