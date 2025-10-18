<?php

namespace App\Http\Controllers;

use App\Http\Requests\Purchsing\StoreBonReceptionRequest;
use App\Http\Requests\Purchsing\UpdateBonReceptionRequest;
use App\Models\BonCommend;
use App\Models\BonReception;
use App\Models\BonReceptionItem;
use App\Models\BonRetour;
use App\Models\BonEntree;
use App\Models\BonEntreeItem;
use App\Models\BonRetourItem;
use App\Services\Purchsing\BonReceptionService;
use App\Services\BonRetourService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDF;

class BonReceptionController extends Controller
{
    protected BonReceptionService $service;
    protected BonRetourService $bonRetourService;

    public function __construct(BonReceptionService $service, BonRetourService $bonRetourService = null)
    {
        $this->service = $service;
        // Initialize BonRetourService if not injected
        $this->bonRetourService = $bonRetourService ?: new BonRetourService();
    }

    /**
     * Display a listing of bon receptions with enhanced filters
     */
    public function index(Request $request)
    {
        // try {
            $query = BonReception::with([
                'fournisseur:id,company_name,contact_person',
                'bonCommend:id,bonCommendCode,status',
                'receivedByUser:id,name',
                'createdByUser:id,name',
                'bonRetour:id,bon_retour_code,status',
                'items.product:id,name,code',
            ]);

            // Apply filters
            if ($request->status && $request->status !== 'all') {
                $query->where('status', $request->status);
            }
            if ($request->fournisseur_id) {
                $query->where('fournisseur_id', $request->fournisseur_id);
            }
            if ($request->bon_commend_id) {
                $query->where('bon_commend_id', $request->bon_commend_id);
            }
            if ($request->search) {
                $query->where('bonReceptionCode', 'like', '%'.$request->search.'%');
            }
            if ($request->date_from) {
                $query->whereDate('date_reception', '>=', $request->date_from);
            }
            if ($request->date_to) {
                $query->whereDate('date_reception', '<=', $request->date_to);
            }
            if ($request->has_surplus) {
                $query->whereHas('items', function ($q) {
                    $q->where('quantity_surplus', '>', 0);
                });
            }
            if ($request->has_return) {
                $query->whereNotNull('bon_retour_id');
            }

            // Add counts
            $query->withCount([
                'items as total_items',
                'items as surplus_items_count' => function ($q) {
                    $q->where('quantity_surplus', '>', 0);
                }
            ]);

            $receptions = $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 15);

            // Add calculated fields to each reception
            $receptions->through(function ($reception) {
                $reception->has_surplus = $reception->surplus_items_count > 0;
                $reception->total_surplus_quantity = $reception->items->sum('quantity_surplus');
                $reception->total_shortage_quantity = $reception->items->sum('quantity_shortage');
                return $reception;
            });

            return response()->json(['status' => 'success', 'data' => $receptions]);

        // } catch (\Exception $e) {
        //     Log::error('Error fetching bon receptions: '.$e->getMessage());
        //     return response()->json(['status' => 'error', 'message' => 'Failed to fetch bon receptions'], 500);
        // }
    }

    /**
     * Store a newly created bon reception
     */
    public function store(StoreBonReceptionRequest $request)
    {
        DB::beginTransaction();
        try {
            $bonReception = $this->service->create($request->validated());

            // Check for surplus items
            $surplusItems = $bonReception->items->filter(function ($item) {
                return $item->quantity_surplus > 0;
            });

            $responseData = [
                'bonReception' => $bonReception,
                'has_surplus' => $surplusItems->isNotEmpty(),
                'surplus_count' => $surplusItems->count()
            ];

            DB::commit();

            return response()->json([
                'status' => 'success',
                'data' => $responseData,
                'message' => $surplusItems->isNotEmpty() 
                    ? 'Bon reception created with surplus items detected' 
                    : 'Bon reception created successfully',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating bon reception: '.$e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to create bon reception: '.$e->getMessage()], 500);
        }
    }

    /**
     * Display the specified bon reception with all related data
     */
    public function show($id)
    {
        try {
            $bonReception = BonReception::with([
                'fournisseur',
                'bonCommend.items.product',
                'receivedByUser',
                'createdByUser',
                'confirmedByUser',
                'bonRetour.items.product',
                'items.product',
                'items.bonCommendItem',
            ])->findOrFail($id);

            // Add calculated fields
            $bonReception->total_ordered = $bonReception->items->sum('quantity_ordered');
            $bonReception->total_received = $bonReception->items->sum('quantity_received');
            $bonReception->total_surplus = $bonReception->items->sum('quantity_surplus');
            $bonReception->total_shortage = $bonReception->items->sum('quantity_shortage');
            $bonReception->surplus_items = $bonReception->items->filter(function ($item) {
                return $item->quantity_surplus > 0;
            })->values();
            $bonReception->shortage_items = $bonReception->items->filter(function ($item) {
                return $item->quantity_shortage > 0;
            })->values();

            return response()->json(['status' => 'success', 'data' => $bonReception]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['status' => 'error', 'message' => 'Bon reception not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching bon reception: '.$e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch bon reception'], 500);
        }
    }

    /**
     * Update the specified bon reception
     */
    public function update(UpdateBonReceptionRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $bonReception = $this->service->update($id, $request->validated());

            // Check for surplus after update
            $surplusItems = $bonReception->items->filter(function ($item) {
                return $item->quantity_surplus > 0;
            });

            DB::commit();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'bonReception' => $bonReception,
                    'has_surplus' => $surplusItems->isNotEmpty(),
                    'surplus_count' => $surplusItems->count()
                ],
                'message' => 'Bon reception updated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating bon reception: '.$e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to update bon reception: '.$e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified bon reception
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $bonReception = BonReception::findOrFail($id);

            if ($bonReception->status !== 'pending') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete bon reception with status: '.$bonReception->status,
                ], 422);
            }

            if ($bonReception->bon_retour_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete bon reception with associated return note',
                ], 422);
            }

            $bonReception->items()->delete();
            $bonReception->delete();

            DB::commit();

            return response()->json(['status' => 'success', 'message' => 'Bon reception deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting bon reception: '.$e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to delete bon reception'], 500);
        }
    }

    /**
     * Update status and create BonRetour for surplus items if confirming
     */

    // ... existing methods ...

    /**
     * Confirm reception with surplus handling and BonEntree creation
     */
    public function confirmReception(Request $request, $id)
    {
        $request->validate([
            'surplus_action' => 'required|in:return,accept,partial',
            'items_to_return' => 'array',
            'items_to_return.*.item_id' => 'required|exists:bon_reception_items,id',
            'items_to_return.*.quantity_to_return' => 'required|integer|min:1',
            'items_to_return.*.reason' => 'required|string',
            'return_notes' => 'nullable|string',
            'service_id' => 'nullable|exists:services,id',
            'service_abv' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            $bonReception = BonReception::with(['items.product', 'fournisseur', 'bonCommend'])->findOrFail($id);
            
            if ($bonReception->status !== 'pending') {
                throw new \Exception('Only pending receptions can be confirmed');
            }

            // Update reception status to completed
            $bonReception->update([
                'status' => 'completed',
                'is_confirmed' => true,
                'confirmed_at' => now(),
                'confirmed_by' => auth()->id(),
            ]);

            $responseData = ['bonReception' => $bonReception];

            // Handle surplus based on action
            if ($request->surplus_action === 'return') {
                // Scenario 1: Return all surplus items (no stock entry for surplus)
                $bonRetour = $this->createReturnForSurplusItems($bonReception, $request->return_notes);
                if ($bonRetour) {
                    $responseData['bonRetour'] = $bonRetour;
                }
                
                // Create BonEntree only for non-surplus items
                $bonEntree = $this->createBonEntreeForAcceptedItems($bonReception, false, $request);
                if ($bonEntree) {
                    $responseData['bonEntree'] = $bonEntree;
                }
                
            } elseif ($request->surplus_action === 'accept') {
                // Scenario 2: Accept all items including surplus (full stock entry)
                $bonEntree = $this->createBonEntreeForAcceptedItems($bonReception, true, $request);
                if ($bonEntree) {
                    $responseData['bonEntree'] = $bonEntree;
                }
                
            } elseif ($request->surplus_action === 'partial' && $request->items_to_return) {
                // Scenario 3: Partial return (some surplus returned, rest accepted)
                $bonRetour = $this->createPartialReturn($bonReception, $request->items_to_return, $request->return_notes);
                if ($bonRetour) {
                    $responseData['bonRetour'] = $bonRetour;
                }
                
                // Create BonEntree for accepted items (including partial surplus)
                $bonEntree = $this->createBonEntreeForPartialAcceptance($bonReception, $request->items_to_return, $request);
                if ($bonEntree) {
                    $responseData['bonEntree'] = $bonEntree;
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'data' => $responseData,
                'message' => $this->getConfirmationMessage($responseData),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error confirming reception: '.$e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }


    /**
     * Create BonEntree for accepted items (with or without surplus)
     */
    protected function createBonEntreeForAcceptedItems(BonReception $bonReception, bool $includeSurplus = false, Request $request = null)
    {
        try {
            // Create BonEntree
            $bonEntree = BonEntree::create([
                'bon_reception_id' => $bonReception->id,
                'fournisseur_id' => $bonReception->fournisseur_id,
                'status' => 'pending',
                'service_id' => $request->service_id ?? $bonReception->bonCommend?->service_id,
                'created_by' => auth()->id(),
                'notes' => $includeSurplus 
                    ? "Stock entry from reception {$bonReception->bonReceptionCode} (including surplus)" 
                    : "Stock entry from reception {$bonReception->bonReceptionCode}",
                'attachments' => $bonReception->attachments,
            ]);

            // Add items to BonEntree
            foreach ($bonReception->items as $item) {
                // Determine quantity to add to stock
                $quantityToStock = $includeSurplus 
                    ? $item->quantity_received // All received items
                    : min($item->quantity_received, $item->quantity_ordered); // Only ordered quantity

                if ($quantityToStock > 0) {
                    $bonEntreeItem = BonEntreeItem::create([
                        'bon_entree_id' => $bonEntree->id,
                        'product_id' => $item->product_id,
                        'batch_number' => $item->batch_number,
                        'serial_number' => $item->serial_number,
                        'expiry_date' => $item->expiry_date,
                        'quantity' => $quantityToStock,
                        'purchase_price' => $item->unit_price,
                        'sell_price' => $item->product?->sell_price ?? $item->unit_price * 1.3,
                        'tva' => $item->tva ?? 0,
                        'by_box' => $item->by_box ?? false,
                        'qte_by_box' => $item->qte_by_box ?? 1,
                        'remarks' => $item->notes,
                        'storage_name' => $item->storage_location,
                        'created_by' => auth()->id(),
                    ]);

                    // Update stock for this item
                    try {
                        $bonEntreeItem->updateStock();
                    } catch (\Exception $e) {
                        Log::warning('Stock update failed for item: ' . $e->getMessage());
                    }
                }
            }

            // Calculate total
            $bonEntree->calculateTotal();

            // Link BonEntree to BonReception
            $bonReception->update(['bon_entree_id' => $bonEntree->id]);

            return $bonEntree->load(['items.product', 'fournisseur']);

        } catch (\Exception $e) {
            Log::error('Error creating BonEntree: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create BonEntree for partial acceptance (some items returned, rest accepted)
     */
    protected function createBonEntreeForPartialAcceptance(BonReception $bonReception, array $itemsToReturn, Request $request = null)
    {
        try {
            // Build a map of returned quantities
            $returnedQuantities = [];
            foreach ($itemsToReturn as $returnItem) {
                $returnedQuantities[$returnItem['item_id']] = $returnItem['quantity_to_return'];
            }

            // Create BonEntree
            $bonEntree = BonEntree::create([
                'bon_reception_id' => $bonReception->id,
                'fournisseur_id' => $bonReception->fournisseur_id,
                'status' => 'validated',
                'service_id' => $request->service_id ?? $bonReception->bonCommend?->service_id,
                'created_by' => auth()->id(),
                'notes' => "Stock entry from reception {$bonReception->bonReceptionCode} (partial surplus acceptance)",
                'attachments' => $bonReception->attachments,
            ]);

            // Add items to BonEntree
            foreach ($bonReception->items as $item) {
                // Calculate quantity to stock (received minus returned)
                $returnedQty = $returnedQuantities[$item->id] ?? 0;
                $quantityToStock = $item->quantity_received - $returnedQty;

                if ($quantityToStock > 0) {
                    $bonEntreeItem = BonEntreeItem::create([
                        'bon_entree_id' => $bonEntree->id,
                        'product_id' => $item->product_id,
                        'batch_number' => $item->batch_number,
                        'serial_number' => $item->serial_number,
                        'expiry_date' => $item->expiry_date,
                        'quantity' => $quantityToStock,
                        'purchase_price' => $item->unit_price,
                        'sell_price' => $item->product?->sell_price ?? $item->unit_price * 1.3,
                        'tva' => $item->tva ?? 0,
                        'by_box' => $item->by_box ?? false,
                        'qte_by_box' => $item->qte_by_box ?? 1,
                        'remarks' => $returnedQty > 0 
                            ? "Partial: {$returnedQty} units returned. " . ($item->notes ?? '')
                            : $item->notes,
                        'storage_name' => $item->storage_location,
                        'created_by' => auth()->id(),
                    ]);

                    // Update stock for this item
                    try {
                        $bonEntreeItem->updateStock();
                    } catch (\Exception $e) {
                        Log::warning('Stock update failed for item: ' . $e->getMessage());
                    }
                }
            }

            // Calculate total
            $bonEntree->calculateTotal();

            // Link BonEntree to BonReception
            $bonReception->update(['bon_entree_id' => $bonEntree->id]);

            return $bonEntree->load(['items.product', 'fournisseur']);

        } catch (\Exception $e) {
            Log::error('Error creating partial BonEntree: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create BonRetour for surplus items
     */
    protected function createReturnForSurplusItems(BonReception $bonReception, ?string $notes = null)
    {
        // Find items with surplus
        $surplusItems = $bonReception->items->filter(function ($item) {
            return $item->quantity_surplus > 0;
        });

        if ($surplusItems->isEmpty()) {
            return null;
        }

        try {
            // Create BonRetour
            $bonRetour = BonRetour::create([
                'fournisseur_id' => $bonReception->fournisseur_id,
                'return_type' => 'overstock',
                'status' => 'draft',
                'service_abv' => $bonReception->bonCommend?->service_abv,
                'reason' => $notes ?? "Automatic return for surplus items from reception {$bonReception->bonReceptionCode}",
                'return_date' => now(),
                'reference_invoice' => $bonReception->facture_numero,
                'created_by' => auth()->id(),
            ]);

            // Add only surplus quantities to return
            foreach ($surplusItems as $item) {
                BonRetourItem::create([
                    'bon_retour_id' => $bonRetour->id,
                    'product_id' => $item->product_id,
                    'bon_reception_item_id' => $item->id,
                    'batch_number' => $item->batch_number,
                    'serial_number' => $item->serial_number,
                    'expiry_date' => $item->expiry_date,
                    'quantity_returned' => $item->quantity_surplus, // Only surplus quantity
                    'unit_price' => $item->unit_price,
                    'tva' => $item->tva ?? 0,
                    'total_amount' => $item->quantity_surplus * $item->unit_price,
                    'return_reason' => 'overstock',
                    'remarks' => "Surplus from reception {$bonReception->bonReceptionCode}",
                    'storage_location' => $item->storage_location,
                ]);
            }

            // Calculate total
            $bonRetour->calculateTotal();

            // Link return to reception
            $bonReception->update(['bon_retour_id' => $bonRetour->id]);

            return $bonRetour->load(['items.product', 'fournisseur']);

        } catch (\Exception $e) {
            Log::error('Error creating return for surplus: ' . $e->getMessage());
            throw $e;
        }
    }


    /**
     * Create partial return for specific items
     */
    protected function createPartialReturn(BonReception $bonReception, array $itemsToReturn, ?string $notes = null)
    {
        try {
            // Create BonRetour
            $bonRetour = BonRetour::create([
                'fournisseur_id' => $bonReception->fournisseur_id,
                'return_type' => 'overstock',
                'status' => 'draft',
                'service_abv' => $bonReception->bonCommend?->service_abv,
                'reason' => $notes ?? "Partial return for selected items from reception {$bonReception->bonReceptionCode}",
                'return_date' => now(),
                'reference_invoice' => $bonReception->facture_numero,
                'created_by' => auth()->id(),
            ]);

            foreach ($itemsToReturn as $returnItem) {
                $receptionItem = BonReceptionItem::find($returnItem['item_id']);
                if (!$receptionItem) continue;

                BonRetourItem::create([
                    'bon_retour_id' => $bonRetour->id,
                    'product_id' => $receptionItem->product_id,
                    'bon_reception_item_id' => $receptionItem->id,
                    'batch_number' => $receptionItem->batch_number,
                    'serial_number' => $receptionItem->serial_number,
                    'expiry_date' => $receptionItem->expiry_date,
                    'quantity_returned' => $returnItem['quantity_to_return'],
                    'unit_price' => $receptionItem->unit_price,
                    'tva' => $receptionItem->tva ?? 0,
                    'total_amount' => $returnItem['quantity_to_return'] * $receptionItem->unit_price,
                    'return_reason' => $this->mapReasonToReturnReason($returnItem['reason']),
                    'remarks' => $returnItem['reason'],
                    'storage_location' => $receptionItem->storage_location,
                ]);
            }

            // Calculate total
            $bonRetour->calculateTotal();

            // Link return to reception
            $bonReception->update(['bon_retour_id' => $bonRetour->id]);

            return $bonRetour->load(['items.product', 'fournisseur']);

        } catch (\Exception $e) {
            Log::error('Error creating partial return: ' . $e->getMessage());
            throw $e;
        }
    }
    /**
     * Get confirmation message based on actions taken
     */
  protected function getConfirmationMessage($responseData)
    {
        $messages = [];
        
        if (isset($responseData['bonEntree'])) {
            $messages[] = "Stock entry {$responseData['bonEntree']->bon_entree_code} created";
        }
        
        if (isset($responseData['bonRetour'])) {
            $messages[] = "Return note {$responseData['bonRetour']->bon_retour_code} created for surplus";
        }
        
        if (empty($messages)) {
            return 'Reception confirmed successfully';
        }
        
        return 'Reception confirmed. ' . implode(' and ', $messages);
    }

    /**
     * Update status and optionally create BonEntree
     */
       public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,completed,canceled,rejected',
            'create_return_for_surplus' => 'boolean',
            'create_stock_entry' => 'boolean',
            'notes' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            $bonReception = BonReception::with(['items.product', 'fournisseur'])->findOrFail($id);
            
            // Update reception status
            $bonReception->update([
                'status' => $request->status,
                'is_confirmed' => $request->status === 'completed',
                'confirmed_at' => $request->status === 'completed' ? now() : null,
                'confirmed_by' => $request->status === 'completed' ? auth()->id() : null,
            ]);

            $responseData = ['bonReception' => $bonReception];

            // If confirming, handle stock and returns
            if ($request->status === 'completed') {
                // Create stock entry if requested
                if ($request->create_stock_entry ?? true) {
                    $bonEntree = $this->createBonEntreeForAcceptedItems(
                        $bonReception, 
                        !($request->create_return_for_surplus ?? false),
                        $request
                    );
                    if ($bonEntree) {
                        $responseData['bonEntree'] = $bonEntree;
                    }
                }

                // Create return for surplus if requested
                if ($request->create_return_for_surplus ?? false) {
                    $bonRetour = $this->createReturnForSurplusItems($bonReception, $request->notes);
                    if ($bonRetour) {
                        $responseData['bonRetour'] = $bonRetour;
                    }
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'data' => $responseData,
                'message' => $this->getConfirmationMessage($responseData),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating bon reception status: '.$e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to update status: '.$e->getMessage()], 500);
        }
    }

    /**
     * Confirm reception with advanced surplus handling options
     */
    
   

      


    /**
     * Create BonRetour for surplus items automatically
     */
 

    /**
     * Create partial return for specific items
     */
  

    /**
     * Map reason text to return reason enum
     */
    protected function mapReasonToReturnReason(string $reason): string
    {
        $reason = strtolower($reason);
        
        if (str_contains($reason, 'defect') || str_contains($reason, 'broken')) {
            return 'defective';
        }
        if (str_contains($reason, 'expired') || str_contains($reason, 'expiry')) {
            return 'expired';
        }
        if (str_contains($reason, 'wrong') || str_contains($reason, 'incorrect')) {
            return 'wrong_item';
        }
        if (str_contains($reason, 'quality') || str_contains($reason, 'damage')) {
            return 'quality_issue';
        }
        if (str_contains($reason, 'surplus') || str_contains($reason, 'excess') || str_contains($reason, 'overstock')) {
            return 'overstock';
        }
        
        return 'other';
    }

    /**
     * Get surplus items preview before confirming
     */
    public function getSurplusPreview($id)
    {
        try {
            $bonReception = BonReception::with(['items.product', 'fournisseur'])->findOrFail($id);
            
            $surplusItems = $bonReception->items->filter(function ($item) {
                return $item->quantity_surplus > 0;
            })->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product' => [
                        'id' => $item->product_id,
                        'name' => $item->product?->name ?? 'Unknown Product',
                        'code' => $item->product?->code
                    ],
                    'quantity_ordered' => $item->quantity_ordered,
                    'quantity_received' => $item->quantity_received,
                    'quantity_surplus' => $item->quantity_surplus,
                    'unit_price' => $item->unit_price,
                    'total_surplus_value' => $item->quantity_surplus * $item->unit_price
                ];
            });

            $shortageItems = $bonReception->items->filter(function ($item) {
                return $item->quantity_shortage > 0;
            })->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product' => [
                        'id' => $item->product_id,
                        'name' => $item->product?->name ?? 'Unknown Product',
                        'code' => $item->product?->code
                    ],
                    'quantity_ordered' => $item->quantity_ordered,
                    'quantity_received' => $item->quantity_received,
                    'quantity_shortage' => $item->quantity_shortage,
                    'unit_price' => $item->unit_price,
                    'total_shortage_value' => $item->quantity_shortage * $item->unit_price
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'has_surplus' => $surplusItems->isNotEmpty(),
                    'has_shortage' => $shortageItems->isNotEmpty(),
                    'total_surplus_items' => $surplusItems->count(),
                    'total_surplus_quantity' => $surplusItems->sum('quantity_surplus'),
                    'total_surplus_value' => $surplusItems->sum('total_surplus_value'),
                    'total_shortage_items' => $shortageItems->count(),
                    'total_shortage_quantity' => $shortageItems->sum('quantity_shortage'),
                    'total_shortage_value' => $shortageItems->sum('total_shortage_value'),
                    'surplus_items' => $surplusItems->values(),
                    'shortage_items' => $shortageItems->values()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting surplus preview: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to get surplus preview'], 500);
        }
    }

    /**
     * Create reception from a confirmed BonCommend
     */
    public function createFromBonCommend(Request $request)
    {
        $request->validate([
            'bon_commend_id' => 'required|exists:bon_commends,id',
            'received_by' => 'required|exists:users,id',
            'date_reception' => 'required|date',
            'auto_fill_quantities' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            $bonReception = $this->service->createFromBonCommend($request->all());

            DB::commit();

            return response()->json([
                'status' => 'success',
                'data' => $bonReception,
                'message' => 'Bon reception created from bon commend successfully',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating bon reception from bon commend: '.$e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to create from bon commend: '.$e->getMessage()], 500);
        }
    }

    /**
     * Get dashboard statistics with return information
     */
    public function getStats()
    {
        try {
            $stats = [
                'total' => BonReception::count(),
                'pending' => BonReception::where('status', 'pending')->count(),
                'completed' => BonReception::where('status', 'completed')->count(),
                'canceled' => BonReception::where('status', 'canceled')->count(),
                'rejected' => BonReception::where('status', 'rejected')->count(),
                'this_month' => BonReception::whereMonth('created_at', now()->month)->count(),
                'with_returns' => BonReception::whereNotNull('bon_retour_id')->count(),
                'surplus_items' => BonReceptionItem::where('quantity_surplus', '>', 0)->count(),
                'shortage_items' => BonReceptionItem::where('quantity_shortage', '>', 0)->count(),
                'total_surplus_value' => BonReceptionItem::where('quantity_surplus', '>', 0)
                    ->selectRaw('SUM(quantity_surplus * unit_price) as total')
                    ->first()
                    ->total ?? 0,
                'total_shortage_value' => BonReceptionItem::where('quantity_shortage', '>', 0)
                    ->selectRaw('SUM(quantity_shortage * unit_price) as total')
                    ->first()
                    ->total ?? 0,
            ];

            return response()->json(['status' => 'success', 'data' => $stats]);
        } catch (\Exception $e) {
            Log::error('Error getting stats: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to get statistics'], 500);
        }
    }

    /**
     * Get dropdown of confirmed bon commends not yet received
     */
    public function getBonCommends()
    {
        try {
            $bonCommends = BonCommend::with('fournisseur:id,company_name')
                ->where('status', 'confirmed')
                ->whereNotExists(function ($q) {
                    $q->select('id')->from('bon_receptions')
                        ->whereColumn('bon_receptions.bon_commend_id', 'bon_commends.id');
                })
                ->select('id', 'bonCommendCode', 'fournisseur_id', 'status', 'created_at')
                ->get();

            return response()->json(['status' => 'success', 'data' => $bonCommends]);
        } catch (\Exception $e) {
            Log::error('Error getting bon commends: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to get purchase orders'], 500);
        }
    }

    /**
     * Download bon reception as PDF with enhanced template
     */
    public function download($id)
    {
        try {
            $bonReception = BonReception::with([
                'fournisseur',
                'bonCommend.items.product',
                'bonRetour',
                'receivedByUser',
                'createdByUser',
                'confirmedByUser',
                'items.product',
            ])->findOrFail($id);

            $data = [
                'bonReception' => $bonReception,
                'company' => config('app.company_name', 'Hospital Management System'),
                'has_surplus' => $bonReception->items->where('quantity_surplus', '>', 0)->isNotEmpty(),
                'has_shortage' => $bonReception->items->where('quantity_shortage', '>', 0)->isNotEmpty(),
                'surplus_items' => $bonReception->items->where('quantity_surplus', '>', 0),
                'shortage_items' => $bonReception->items->where('quantity_shortage', '>', 0),
            ];

            $pdf = PDF::loadView('pdf.bon-reception', $data);
            $filename = 'bon-reception-'.($bonReception->bonReceptionCode ?: 'BR-'.$bonReception->id).'.pdf';

            return $pdf->download($filename);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['status' => 'error', 'message' => 'Bon reception not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error downloading bon reception: '.$e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to download bon reception'], 500);
        }
    }

    /**
     * Export receptions data to Excel
     */
    public function export(Request $request)
    {
        try {
            $query = BonReception::with(['fournisseur', 'items.product', 'bonRetour']);

            // Apply same filters as index
            if ($request->status && $request->status !== 'all') {
                $query->where('status', $request->status);
            }
            if ($request->fournisseur_id) {
                $query->where('fournisseur_id', $request->fournisseur_id);
            }
            if ($request->date_from) {
                $query->whereDate('date_reception', '>=', $request->date_from);
            }
            if ($request->date_to) {
                $query->whereDate('date_reception', '<=', $request->date_to);
            }

            $receptions = $query->get();

            // Format data for export
            $exportData = $receptions->map(function ($reception) {
                return [
                    'Code' => $reception->bonReceptionCode,
                    'Purchase Order' => $reception->bonCommend?->bonCommendCode,
                    'Supplier' => $reception->fournisseur?->company_name,
                    'Date' => $reception->date_reception->format('Y-m-d'),
                    'Status' => $reception->status,
                    'Items' => $reception->items->count(),
                    'Total Ordered' => $reception->items->sum('quantity_ordered'),
                    'Total Received' => $reception->items->sum('quantity_received'),
                    'Total Surplus' => $reception->items->sum('quantity_surplus'),
                    'Total Shortage' => $reception->items->sum('quantity_shortage'),
                    'Has Return' => $reception->bon_retour_id ? 'Yes' : 'No',
                    'Return Code' => $reception->bonRetour?->bon_retour_code,
                ];
            });

            // Here you would typically use a package like Laravel Excel to export
            // For now, returning as JSON
            return response()->json([
                'status' => 'success',
                'data' => $exportData,
                'message' => 'Export data prepared successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error exporting receptions: '.$e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to export data'], 500);
        }
    }
}
