<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use App\Models\StockMovementItem;
use App\Models\UserSpecialization;
use App\Models\Specialization;
use App\Models\Product;
use App\Models\CONFIGURATION\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\stock\StockMovementResource;

class StockMovementController extends Controller
{
    public function __construct()
    {
        // Middleware is already applied at route level
    }

    /**
     * Get user's service through their specialization
     */
    private function getUserService()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                \Log::warning('StockMovementController: No authenticated user found');
                return null;
            }
            
            // Get user's active specialization
            $userSpecialization = UserSpecialization::where('user_id', $user->id)
                                                   ->where('status', 'active')
                                                   ->with('specialization.service')
                                                   ->first();
            
            if (!$userSpecialization) {
                \Log::warning('StockMovementController: No active specialization found for user', ['user_id' => $user->id]);
                return null;
            }
            
            if (!$userSpecialization->specialization) {
                \Log::warning('StockMovementController: Specialization not found', ['specialization_id' => $userSpecialization->specialization_id]);
                return null;
            }
            
            if (!$userSpecialization->specialization->service) {
                \Log::warning('StockMovementController: Service not found for specialization', [
                    'specialization_id' => $userSpecialization->specialization_id,
                    'service_id' => $userSpecialization->specialization->service_id
                ]);
                return null;
            }
            
            return $userSpecialization->specialization->service;
            
        } catch (\Exception $e) {
            \Log::error('StockMovementController: Error getting user service', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get user's active specialization
     */
    private function getUserSpecialization()
    {
        $user = Auth::user();
        
        if (!$user) {
            return null;
        }
        
        return UserSpecialization::where('user_id', $user->id)
                                ->where('status', 'active')
                                ->with('specialization.service')
                                ->first();
    }

    /**
     * Display a listing of stock movements
     */
    public function index(Request $request)
    {
        $query = StockMovement::with([
            'items.product' => function ($query) {
                $query->with(['inventories' => function ($inventoryQuery) {
                    $inventoryQuery->select('product_id', 'unit');
                }]);
            },
            'requestingService',
            'providingService',
            'requestingUser'
        ]);

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by service (for user's service)
        $user = Auth::user();
        $userService = $this->getUserService();

        if ($userService) {
            if ($request->has('role')) {
                if ($request->role === 'requester') {
                    $query->where('requesting_service_id', $userService->id);
                } elseif ($request->role === 'provider') {
                    $query->where('providing_service_id', $userService->id);
                } else {
                    // default to both
                    $query->where(function ($q) use ($userService) {
                        $q->where('requesting_service_id', $userService->id)
                          ->orWhere('providing_service_id', $userService->id);
                    });
                }
            } else {
                $query->where(function ($q) use ($userService) {
                    $q->where('requesting_service_id', $userService->id)
                      ->orWhere('providing_service_id', $userService->id);
                });
            }
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $movements = $query->orderBy('created_at', 'desc')
                           ->paginate($request->get('per_page', 15));

        // Add unit information to products
        $movements->each(function ($movement) {
            $movement->items->each(function ($item) {
                if ($item->product && $item->product->inventories) {
                    $units = $item->product->inventories->pluck('unit')->filter()->unique();
                    $item->product->unit = $units->first() ?? 'units';
                }
            });
        });

        return response()->json([
            'success' => true,
            'data' => $movements
        ]);
    }

    /**
     * Get draft movements for current user
     */
    public function getDrafts()
    {
        $user = Auth::user();
        $userService = $this->getUserService();

        if (!$userService) {
            return response()->json(['error' => 'User not assigned to any service'], 403);
        }

    $drafts = StockMovement::with([
        'items.product' => function ($query) {
            $query->with(['inventories' => function ($inventoryQuery) {
                $inventoryQuery->select('product_id', 'unit');
            }]);
        },
        'providingService',
        'requestingService',
        'requestingUser'
    ])
                  ->where('requesting_service_id', $userService->id)
                  ->where('status', 'draft')
                  ->where('requesting_user_id', $user->id)
                  ->orderBy('updated_at', 'desc')
                  ->get();

        // Add unit information to products
        $drafts->each(function ($draft) {
            $draft->items->each(function ($item) {
                if ($item->product && $item->product->inventories) {
                    $units = $item->product->inventories->pluck('unit')->filter()->unique();
                    $item->product->unit = $units->first() ?? 'units';
                }
            });
        });

        return response()->json([
            'success' => true,
            'data' => StockMovementResource::collection($drafts)
        ]);
    }

    /**
     * Get pending requests for approval
     */
    public function getPendingApprovals(Request $request)
    {
        $user = Auth::user();
        $userService = $this->getUserService();

        if (!$userService) {
            return response()->json(['error' => 'User not assigned to any service'], 403);
        }

        $query = StockMovement::with([
            'items.product' => function ($query) {
                $query->with(['inventories' => function ($inventoryQuery) {
                    $inventoryQuery->select('product_id', 'unit');
                }]);
            },
            'requestingService',
            'requestingUser'
        ])
                                       ->where('providing_service_id', $userService->id)
                                       ->where('status', 'pending')
                                       ->orderBy('created_at', 'desc');

        $pendingRequests = $query->paginate($request->get('per_page', 15));

        // Add unit information to products
        $pendingRequests->each(function ($request) {
            $request->items->each(function ($item) {
                if ($item->product && $item->product->inventories) {
                    $units = $item->product->inventories->pluck('unit')->filter()->unique();
                    $item->product->unit = $units->first() ?? 'units';
                }
            });
        });

        return response()->json([
            'success' => true,
            'data' => $pendingRequests
        ]);
    }

    /**
     * Create a new draft stock movement
     */
    public function createDraft(Request $request)
    {
        $request->validate([
            'providing_service_id' => 'required|exists:services,id',
            'request_reason' => 'nullable|string|max:500',
            'expected_delivery_date' => 'nullable|date|after:today'
        ]);

        $user = Auth::user();
        $userService = $this->getUserService();

        if (!$userService) {
            return response()->json(['error' => 'User not assigned to any service'], 403);
        }

        // Check if user has any draft for this service
        $existingMovement = StockMovement::where('requesting_service_id', $userService->id)
                                     ->where('providing_service_id', $request->providing_service_id)
                                     ->where('requesting_user_id', $user->id)
                                     ->latest()
                                     ->first();

        // If there's an existing movement and it's in draft status, return it
        if ($existingMovement && $existingMovement->status === 'draft') {
            $data = $existingMovement->load([
                'items.product' => function ($query) {
                    $query->with(['inventories' => function ($inventoryQuery) {
                        $inventoryQuery->select('product_id', 'unit');
                    }]);
                },
                'providingService'
            ]);
            
            // Add unit information to products
            $data->items->each(function ($item) {
                if ($item->product && $item->product->inventories) {
                    $units = $item->product->inventories->pluck('unit')->filter()->unique();
                    $item->product->unit = $units->first() ?? 'units';
                }
            });
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        }

        $movement = StockMovement::create([
            'movement_number' => $this->generateMovementNumber(),
            'requesting_service_id' => $userService->id,
            'providing_service_id' => $request->providing_service_id,
            'requesting_user_id' => $user->id,
            'status' => 'draft',
            'request_reason' => $request->request_reason,
            'expected_delivery_date' => $request->expected_delivery_date,
        ]);

        $data = $movement->load([
            'items.product' => function ($query) {
                $query->with(['inventories' => function ($inventoryQuery) {
                    $inventoryQuery->select('product_id', 'unit');
                }]);
            },
            'providingService'
        ]);

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Draft created successfully'
        ]);
    }

    public function addItem(Request $request, $movementId)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'requested_quantity' => 'required|numeric|min:0.01',
            'quantity_by_box' => 'boolean',
            'notes' => 'nullable|string|max:255'
        ]);

        $movement = StockMovement::findOrFail($movementId);

        // Check if user owns this draft
        if ($movement->requesting_user_id !== Auth::id() || $movement->status !== 'draft') {
            return response()->json(['error' => 'Unauthorized or invalid movement'], 403);
        }

        // Check if item already exists
        $existingItem = $movement->items()->where('product_id', $request->product_id)->first();

        if ($existingItem) {
            $existingItem->update([
                'requested_quantity' => $request->requested_quantity,
                'quantity_by_box' => $request->boolean('quantity_by_box', false),
                'notes' => $request->notes
            ]);

            $data = $existingItem->load([
                'product' => function ($query) {
                    $query->with(['inventories' => function ($inventoryQuery) {
                        $inventoryQuery->select('product_id', 'unit');
                    }]);
                }
            ]);
            
            // Add unit information to product
            if ($data->product && $data->product->inventories) {
                $units = $data->product->inventories->pluck('unit')->filter()->unique();
                $data->product->unit = $units->first() ?? 'units';
            }

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Item updated successfully'
            ]);
        }

        $item = $movement->items()->create([
            'product_id' => $request->product_id,
            'requested_quantity' => $request->requested_quantity,
            'quantity_by_box' => $request->boolean('quantity_by_box', false),
            'notes' => $request->notes
        ]);

        $data = $item->load([
            'product' => function ($query) {
                $query->with(['inventories' => function ($inventoryQuery) {
                    $inventoryQuery->select('product_id', 'unit');
                }]);
            }
        ]);
        
        // Add unit information to product
        if ($data->product && $data->product->inventories) {
            $units = $data->product->inventories->pluck('unit')->filter()->unique();
            $data->product->unit = $units->first() ?? 'units';
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Item added successfully'
        ]);
    }

    /**
     * Update item in draft movement
     */
    public function updateItem(Request $request, $movementId, $itemId)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'requested_quantity' => 'required|numeric|min:0.01',
            'quantity_by_box' => 'boolean',
            'notes' => 'nullable|string|max:255'
        ]);

        $movement = StockMovement::findOrFail($movementId);

        // Check if user owns this draft
        if ($movement->requesting_user_id !== Auth::id() || $movement->status !== 'draft') {
            return response()->json(['error' => 'Unauthorized or invalid movement'], 403);
        }

        $item = $movement->items()->findOrFail($itemId);

        $item->update([
            'product_id' => $request->product_id,
            'requested_quantity' => $request->requested_quantity,
            'quantity_by_box' => $request->boolean('quantity_by_box', false),
            'notes' => $request->notes
        ]);

        $data = $item->load([
            'product' => function ($query) {
                $query->with(['inventories' => function ($inventoryQuery) {
                    $inventoryQuery->select('product_id', 'unit');
                }]);
            }
        ]);
        
        // Add unit information to product
        if ($data->product && $data->product->inventories) {
            $units = $data->product->inventories->pluck('unit')->filter()->unique();
            $data->product->unit = $units->first() ?? 'units';
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Item updated successfully'
        ]);
    }

    /**
     * Remove item from draft movement
     */
    public function removeItem($movementId, $itemId)
    {
        $movement = StockMovement::findOrFail($movementId);

        if ($movement->requesting_user_id !== Auth::id() || $movement->status !== 'draft') {
            return response()->json(['error' => 'Unauthorized or invalid movement'], 403);
        }

        $item = $movement->items()->findOrFail($itemId);
        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item removed successfully'
        ]);
    }

    /**
     * Send draft movement (change status to pending)
     */
    public function sendDraft($movementId)
    {
        $movement = StockMovement::findOrFail($movementId);

        if ($movement->requesting_user_id !== Auth::id() || $movement->status !== 'draft') {
            return response()->json(['error' => 'Unauthorized or invalid movement'], 403);
        }

        if ($movement->items()->count() === 0) {
            return response()->json(['error' => 'Cannot send empty request'], 422);
        }

        $movement->update([
            'status' => 'pending',
            'requested_at' => now()
        ]);

        // Notify providing service
        $this->notifyService($movement->providing_service_id, 'new_movement_request', $movement);

        $data = $movement->load([
            'items.product' => function ($query) {
                $query->with(['inventories' => function ($inventoryQuery) {
                    $inventoryQuery->select('product_id', 'unit');
                }]);
            }
        ]);
        
        // Add unit information to products
        $data->items->each(function ($item) {
            if ($item->product && $item->product->inventories) {
                $units = $item->product->inventories->pluck('unit')->filter()->unique();
                $item->product->unit = $units->first() ?? 'units';
            }
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Request sent successfully'
        ]);
    }

    /**
     * Get low stock/expiring products for suggestions
     */
    public function getSuggestions(Request $request)
    {
        $user = Auth::user();
        $userService = $this->getUserService();

        if (!$userService) {
            return response()->json(['error' => 'User not assigned to any service'], 403);
        }

        $providingServiceId = $request->get('providing_service_id');
        if (!$providingServiceId) {
            return response()->json(['error' => 'Providing service ID required'], 422);
        }

        // Get products with different stock levels
        $criticalLowStock = DB::table('inventories')
            ->join('products', 'inventories.product_id', '=', 'products.id')
            ->join('stockages', 'inventories.stockage_id', '=', 'stockages.id')
            ->where('stockages.service_id', $providingServiceId)
            ->whereRaw('CASE 
                WHEN products.quantity_by_box = true AND products.boite_de > 0 
                THEN inventories.total_units / products.boite_de <= 5 
                ELSE inventories.total_units <= 5 
                END')
            ->whereRaw('inventories.total_units > 0')
            ->select(
                'products.*',
                'inventories.quantity as current_stock',
                'inventories.total_units',
                'inventories.expiry_date',
                'inventories.batch_number',
                'stockages.name as stockage_name',
                'products.category as category_name',
                DB::raw('DATEDIFF(inventories.expiry_date, CURDATE()) as days_until_expiry')
            )
            ->orderBy('inventories.total_units', 'asc')
            ->get();

        $lowStock = DB::table('inventories')
            ->join('products', 'inventories.product_id', '=', 'products.id')
            ->join('stockages', 'inventories.stockage_id', '=', 'stockages.id')
            ->where('stockages.service_id', $providingServiceId)
            ->whereRaw('CASE 
                WHEN products.quantity_by_box = true AND products.boite_de > 0 
                THEN inventories.total_units / products.boite_de <= 10 AND inventories.total_units / products.boite_de > 5
                ELSE inventories.total_units <= 10 AND inventories.total_units > 5
                END')
            ->select(
                'products.*',
                'inventories.quantity as current_stock',
                'inventories.total_units',
                'inventories.expiry_date',
                'inventories.batch_number',
                'stockages.name as stockage_name',
                'products.category as category_name',
                DB::raw('DATEDIFF(inventories.expiry_date, CURDATE()) as days_until_expiry')
            )
            ->orderBy('inventories.total_units', 'asc')
            ->get();

        // Get products expiring soon (within 30 days)
        $expiringSoon = DB::table('inventories')
            ->join('products', 'inventories.product_id', '=', 'products.id')
            ->join('stockages', 'inventories.stockage_id', '=', 'stockages.id')
            ->where('stockages.service_id', $providingServiceId)
            ->whereNotNull('inventories.expiry_date')
            ->whereRaw('DATEDIFF(inventories.expiry_date, CURDATE()) <= 30')
            ->whereRaw('DATEDIFF(inventories.expiry_date, CURDATE()) > 0')
            ->select(
                'products.*',
                'inventories.quantity as current_stock',
                'inventories.total_units',
                'inventories.expiry_date',
                'inventories.batch_number',
                'stockages.name as stockage_name',
                'products.category as category_name',
                DB::raw('DATEDIFF(inventories.expiry_date, CURDATE()) as days_until_expiry')
            )
            ->orderBy('inventories.expiry_date', 'asc')
            ->get();

        // Get expired products
        $expired = DB::table('inventories')
            ->join('products', 'inventories.product_id', '=', 'products.id')
            ->join('stockages', 'inventories.stockage_id', '=', 'stockages.id')
            ->where('stockages.service_id', $providingServiceId)
            ->whereNotNull('inventories.expiry_date')
            ->whereRaw('inventories.expiry_date < CURDATE()')
            ->whereRaw('inventories.total_units > 0')
            ->select(
                'products.*',
                'inventories.quantity as current_stock',
                'inventories.total_units',
                'inventories.expiry_date',
                'inventories.batch_number',
                'stockages.name as stockage_name',
                'products.category as category_name',
                DB::raw('DATEDIFF(CURDATE(), inventories.expiry_date) as days_expired')
            )
            ->orderBy('inventories.expiry_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'critical_low' => $criticalLowStock,
                'low_stock' => $lowStock,
                'expiring_soon' => $expiringSoon,
                'expired' => $expired,
                'counts' => [
                    'critical_low' => $criticalLowStock->count(),
                    'low_stock' => $lowStock->count(),
                    'expiring_soon' => $expiringSoon->count(),
                    'expired' => $expired->count()
                ]
            ]
        ]);
    }

    /**
     * Approve or reject movement request
     */
    public function updateStatus(Request $request, $movementId)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected,partially_approved',
            'approval_notes' => 'nullable|string|max:500'
        ]);

        $movement = StockMovement::findOrFail($movementId);
        $user = Auth::user();
        $userService = $this->getUserService();

        // Check if user can approve for this service
        if (!$userService || $movement->providing_service_id !== $userService->id) {
            return response()->json(['error' => 'Unauthorized to approve this request'], 403);
        }

        DB::transaction(function () use ($movement, $request, $user) {
            $movement->update([
                'status' => $request->status,
                'approving_user_id' => $user->id,
                'approval_notes' => $request->approval_notes,
                'approved_at' => now(),
            ]);

            // Update item approvals if partially approved
            if ($request->status === 'partially_approved' && $request->has('item_approvals')) {
                foreach ($request->item_approvals as $itemId => $approvedQty) {
                    $movement->items()
                            ->where('id', $itemId)
                            ->update(['approved_quantity' => $approvedQty]);
                }
            } elseif ($request->status === 'approved') {
                // Approve all items with requested quantities
                $movement->items()->update([
                    'approved_quantity' => DB::raw('requested_quantity')
                ]);
            }
        });

        // Notify requesting service
        $statusMessage = $request->status === 'approved' ? 'approved' : 
                        ($request->status === 'partially_approved' ? 'partially approved' : 'rejected');
        $this->notifyService($movement->requesting_service_id, 'movement_' . $statusMessage, $movement);

        $data = $movement->load([
            'items.product' => function ($query) {
                $query->with(['inventories' => function ($inventoryQuery) {
                    $inventoryQuery->select('product_id', 'unit');
                }]);
            }
        ]);
        
        // Add unit information to products
        $data->items->each(function ($item) {
            if ($item->product && $item->product->inventories) {
                $units = $item->product->inventories->pluck('unit')->filter()->unique();
                $item->product->unit = $units->first() ?? 'units';
            }
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Request ' . $statusMessage . ' successfully'
        ]);
    }

    /**
     * Get movement statistics
     */
    public function getStats()
    {
        $user = Auth::user();
        $userService = $this->getUserService();

        if (!$userService) {
            return response()->json(['error' => 'User not assigned to any service'], 403);
        }

        $stats = [
            'draft' => StockMovement::where('requesting_service_id', $userService->id)
                                   ->where('status', 'draft')->count(),
            'requesting_pending' => StockMovement::where('requesting_service_id', $userService->id)
                                                ->where('status', 'pending')->count(),
            'providing_pending' => StockMovement::where('providing_service_id', $userService->id)
                                               ->where('status', 'pending')->count(),
            'approved' => StockMovement::where('providing_service_id', $userService->id)
                                      ->where('status', 'approved')->count(),
            'rejected' => StockMovement::where('requesting_service_id', $userService->id)
                                      ->where('status', 'rejected')->count(),
            'executed' => StockMovement::where(function ($q) use ($userService) {
                $q->where('requesting_service_id', $userService->id)
                  ->orWhere('providing_service_id', $userService->id);
            })->where('status', 'executed')->count()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Show specific movement
     */
    public function show($movementId)
    {
        $movement = StockMovement::with([
            'items.product' => function ($query) {
                $query->with(['inventories' => function ($inventoryQuery) {
                    $inventoryQuery->select('product_id', 'unit');
                }]);
            },
            'requestingService',
            'providingService',
            'requestingUser',
            'approvingUser',
            'executingUser'
        ])->findOrFail($movementId);

        $user = Auth::user();
        $userService = $this->getUserService();

        // Check if user has access to this movement
        if (!$userService || 
            ($movement->requesting_service_id !== $userService->id && 
             $movement->providing_service_id !== $userService->id)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Add unit information to products
        $movement->items->each(function ($item) {
            if ($item->product && $item->product->inventories) {
                $units = $item->product->inventories->pluck('unit')->filter()->unique();
                $item->product->unit = $units->first() ?? 'units';
            }
        });

        return response()->json([
            'success' => true,
            'data' => $movement
        ]);
    }

    /**
     * Delete draft movement
     */
    public function destroy($movementId)
    {
        $movement = StockMovement::findOrFail($movementId);

        if ($movement->requesting_user_id !== Auth::id() || $movement->status !== 'draft') {
            return response()->json(['error' => 'Unauthorized or cannot delete'], 403);
        }

        $movement->delete();

        return response()->json([
            'success' => true,
            'message' => 'Draft deleted successfully'
        ]);
    }

    /**
     * Get available stock for a product in a movement context
     */
    public function availableStock($movementId, Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id'
        ]);

        $movement = StockMovement::findOrFail($movementId);
        $productId = $request->product_id;

        $user = Auth::user();
        $userService = $this->getUserService();

        // Check if user has access to this movement
        if (!$userService || 
            ($movement->requesting_service_id !== $userService->id && 
             $movement->providing_service_id !== $userService->id)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Get available stock from the providing service
        $providingService = $movement->providingService;
        $availableStock = 0;

        if ($providingService) {
            // Get stock from service's stockages
            $stockages = $providingService->stockages;
            foreach ($stockages as $stockage) {
                $inventory = $stockage->inventories()->where('product_id', $productId)->first();
                if ($inventory) {
                    $availableStock += $inventory->quantity;
                }
            }
        }

        return response()->json([
            'success' => true,
            'available_stock' => $availableStock
        ]);
    }

    /**
     * Get inventory items for a product in movement context
     */
    public function getProductInventory($movementId, $productId)
    {
        $movement = StockMovement::findOrFail($movementId);
        $user = Auth::user();
        $userService = $this->getUserService();

        // Check if user has access to this movement and is the providing service
        if (!$userService || $movement->providing_service_id !== $userService->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Get inventory items for this product from the providing service's stockages
        $providingService = $movement->providingService;
        $inventoryItems = [];

        if ($providingService) {
            $stockages = $providingService->stockages;
            foreach ($stockages as $stockage) {
                $inventories = $stockage->inventories()
                    ->with('product')
                    ->where('product_id', $productId)
                    ->where('quantity', '>', 0)
                    ->get();

                foreach ($inventories as $inventory) {
                    $inventoryItems[] = [
                        'id' => $inventory->id,
                        'barcode' => $inventory->barcode,
                        'batch_number' => $inventory->batch_number,
                        'serial_number' => $inventory->serial_number,
                        'quantity' => $inventory->quantity,
                        'total_units' => $inventory->total_units,
                        'unit' => $inventory->unit,
                        'expiry_date' => $inventory->expiry_date,
                        'location' => $inventory->location,
                        'stockage' => [
                            'id' => $stockage->id,
                            'name' => $stockage->name
                        ],
                        'product' => $inventory->product
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => $inventoryItems
        ]);
    }

    /**
     * Save selected inventory items for a movement item
     */
    public function selectInventory(Request $request, $movementId)
    {
        $request->validate([
            'item_id' => 'required|exists:stock_movement_items,id',
            'selected_inventory' => 'required|array',
            'selected_inventory.*.inventory_id' => 'required|exists:inventories,id',
            'selected_inventory.*.quantity' => 'required|numeric|min:0.01'
        ]);

        $movement = StockMovement::findOrFail($movementId);
        $user = Auth::user();
        $userService = $this->getUserService();

        // Check if user has access to this movement and is the providing service
        if (!$userService || $movement->providing_service_id !== $userService->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $item = StockMovementItem::findOrFail($request->item_id);

        // Verify the item belongs to this movement
        if ($item->stock_movement_id !== $movement->id) {
            return response()->json(['error' => 'Invalid item for this movement'], 403);
        }

        DB::transaction(function () use ($item, $request) {
            // Clear existing selections for this item
            DB::table('stock_movement_inventory_selections')
                ->where('stock_movement_item_id', $item->id)
                ->delete();

            // Save new selections
            $totalSelectedQuantity = 0;
            foreach ($request->selected_inventory as $selection) {
                // Verify the inventory item exists and has sufficient quantity
                $inventory = DB::table('inventories')->find($selection['inventory_id']);
                if (!$inventory || $inventory->quantity < $selection['quantity']) {
                    throw new \Exception('Insufficient inventory for item: ' . $inventory->barcode);
                }

                DB::table('stock_movement_inventory_selections')->insert([
                    'stock_movement_item_id' => $item->id,
                    'inventory_id' => $selection['inventory_id'],
                    'selected_quantity' => $selection['quantity'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                $totalSelectedQuantity += $selection['quantity'];
            }

            // Update the item's provided quantity
            $item->update([
                'provided_quantity' => $totalSelectedQuantity
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Inventory selection saved successfully'
        ]);
    }

    // Helper methods
    private function generateMovementNumber()
    {
        $year = date('Y');
        $lastMovement = StockMovement::where('movement_number', 'like', "SM-{$year}-%")
                                    ->orderBy('id', 'desc')
                                    ->first();
        
        if ($lastMovement) {
            // Extract the sequential number from the last movement number
            $parts = explode('-', $lastMovement->movement_number);
            $lastNumber = (int) end($parts);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        
        return 'SM-' . $year . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    private function notifyService($serviceId, $event, $movement)
    {
        // Implementation depends on your notification system
        // Could use Laravel notifications, database notifications, or email
    }
}
