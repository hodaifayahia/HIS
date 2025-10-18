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
use App\Http\Resources\Stock\StockMovementItemResource;
use App\Http\Requests\Stock\ApproveItemsRequest;
use App\Http\Requests\Stock\RejectItemsRequest;
use App\Services\StockMovementApprovalService;

class StockMovementController extends Controller
{
    protected $approvalService;

    public function __construct(StockMovementApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
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
                                                   ->where('status', true)
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
            'items.product',
            'items.selectedInventory.inventory',
            'requestingService',
            'providingService',
            'requestingUser',
            'approvingUser',
            'executingUser',
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
                $item->product->name;
                $item->product->description;
            }
            
            // Ensure product name and description are available
            if ($item->product) {
                $item->product_name = $item->product->name;
                $item->product_description = $item->product->description;
            }
            
            // Add provided_quantity (this might be the approved_quantity or requested_quantity based on context)
            $item->provided_quantity = $item->approved_quantity ?? $item->requested_quantity ?? 0;
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
                if (!$inventory) {
                    throw new \Exception('Inventory item not found: ' . $selection['inventory_id']);
                }
                
                // Use total_units if available, otherwise fall back to quantity
                $availableQuantity = $inventory->total_units ?? $inventory->quantity;
                if ($availableQuantity < $selection['quantity']) {
                    throw new \Exception('Insufficient inventory for item: ' . $inventory->barcode . '. Available: ' . $availableQuantity . ', Requested: ' . $selection['quantity']);
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

    /**
     * Approve selected items in a stock movement
     */
    public function approveItems(ApproveItemsRequest $request, $movementId)
    {
        try {
            $userService = $this->getUserService();
            if (!$userService) {
                return response()->json([
                    'success' => false,
                    'message' => 'User service not found'
                ], 403);
            }

            $movement = StockMovement::where('id', $movementId)
                                   ->where('providing_service_id', $userService->id)
                                   ->with('items.product')
                                   ->first();

            if (!$movement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock movement not found or access denied'
                ], 404);
            }

            // Check if movement can be edited
            if (!$this->approvalService->canEditMovement($movement)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This stock movement cannot be modified in its current state'
                ], 422);
            }

            $result = $this->approvalService->approveItems($movement, $request->item_ids);

            if (!empty($result['errors'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some items could not be processed',
                    'errors' => $result['errors']
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'processed_items' => StockMovementItemResource::collection($result['processed_items']),
                'statistics' => $this->approvalService->getMovementStatistics($movement->fresh())
            ]);

        } catch (\Exception $e) {
            \Log::error('Error approving stock movement items: ' . $e->getMessage(), [
                'movement_id' => $movementId,
                'item_ids' => $request->item_ids ?? [],
                'user_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while approving items'
            ], 500);
        }
    }

    /**
     * Reject selected items in a stock movement
     */
    public function rejectItems(RejectItemsRequest $request, $movementId)
    {
        try {
            $userService = $this->getUserService();
            if (!$userService) {
                return response()->json([
                    'success' => false,
                    'message' => 'User service not found'
                ], 403);
            }

            $movement = StockMovement::where('id', $movementId)
                                   ->where('providing_service_id', $userService->id)
                                   ->with('items.product')
                                   ->first();

            if (!$movement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock movement not found or access denied'
                ], 404);
            }

            // Check if movement can be edited
            if (!$this->approvalService->canEditMovement($movement)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This stock movement cannot be modified in its current state'
                ], 422);
            }

            $result = $this->approvalService->rejectItems(
                $movement, 
                $request->item_ids, 
                $request->rejection_reason
            );

            if (!empty($result['errors'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some items could not be processed',
                    'errors' => $result['errors']
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'processed_items' => StockMovementItemResource::collection($result['processed_items']),
                'statistics' => $this->approvalService->getMovementStatistics($movement->fresh())
            ]);

        } catch (\Exception $e) {
            \Log::error('Error rejecting stock movement items: ' . $e->getMessage(), [
                'movement_id' => $movementId,
                'item_ids' => $request->item_ids ?? [],
                'rejection_reason' => $request->rejection_reason ?? null,
                'user_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while rejecting items'
            ], 500);
        }
    }

    /**
     * Initialize transfer for approved stock movement
     */
    public function initializeTransfer(Request $request, $movementId)
    {
        try {
            $userService = $this->getUserService();
            if (!$userService) {
                return response()->json([
                    'success' => false,
                    'message' => 'User service not found'
                ], 403);
            }
            $movement = StockMovement::where('id', $movementId)
                                   ->where('providing_service_id', $userService->id)
                                        ->with(['items.product'])
                                   ->first();

            if (!$movement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock movement not found or access denied'
                ], 404);
            }

            // Check if movement is in approved status
            if ($movement->status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock movement must be in approved status to initialize transfer'
                ], 422);
            }

            DB::beginTransaction();

            try {
                // Update stock quantities for each approved item and track sender quantities
                foreach ($movement->items as $item) {
                    $totalSentQuantity = 0;
                    
                    if ($item->selectedInventory && $item->selectedInventory->count() > 0) {
                        foreach ($item->selectedInventory as $selection) {
                            $inventory = $selection->inventory;
                            
                            // Deduct quantity from provider's stock
                            if ($inventory->quantity >= $selection->quantity) {
                                $inventory->quantity -= $selection->quantity;
                                $inventory->save();
                                
                                // Track the quantity being sent
                                $totalSentQuantity += $selection->quantity;
                                
                                \Log::info('Stock deducted for transfer', [
                                    'inventory_id' => $inventory->id,
                                    'product_id' => $inventory->product_id,
                                    'deducted_quantity' => $selection->quantity,
                                    'remaining_quantity' => $inventory->quantity
                                ]);
                            } else {
                                throw new \Exception("Insufficient stock for product: {$item->product->name}");
                            }
                        }
                    }
                    
                    // Update the item with sender quantity tracking
                    $item->update([
                        'sender_quantity' => $totalSentQuantity,
                        'updated_at' => now()
                    ]);
                }

                // Update movement status to in_transfer
                $movement->status = 'in_transfer';
                $movement->transfer_initiated_at = now();
                $movement->transfer_initiated_by = Auth::id();
                $movement->save();

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Transfer initialized successfully. Stock quantities have been updated.',
                    'movement' => new StockMovementResource($movement->fresh())
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            \Log::error('Error initializing transfer: ' . $e->getMessage(), [
                'movement_id' => $movementId,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while initializing transfer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirm delivery for in-transfer stock movement
     */
    public function confirmDelivery(Request $request, $movementId)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:good,manque,damaged',
            'notes' => 'nullable|string|max:1000',
            'missing_quantity' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userService = $this->getUserService();
            if (!$userService) {
                return response()->json([
                    'success' => false,
                    'message' => 'User service not found'
                ], 403);
            }

            $movement = StockMovement::where('id', $movementId)
                                   ->where('requesting_service_id', $userService->id)
                                   ->with(['items.selectedInventory.inventory', 'items.product'])
                                   ->first();

            if (!$movement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock movement not found or access denied'
                ], 404);
            }

            // Check if movement is in in_transfer status
            if ($movement->status !== 'in_transfer') {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock movement must be in transfer status to confirm delivery'
                ], 422);
            }

            DB::beginTransaction();

            try {
                $deliveryStatus = $request->status;
                $notes = $request->notes;
                $missingQuantity = $request->missing_quantity ?? 0;

                if ($deliveryStatus === 'good') {
                    // Mark as fulfilled - add stock to requester's inventory
                    foreach ($movement->items as $item) {
                        if ($item->selectedInventory && $item->selectedInventory->count() > 0) {
                            foreach ($item->selectedInventory as $selection) {
                                // Create new inventory record for the requesting service
                                $newInventory = new \App\Models\Inventory();
                                $newInventory->product_id = $selection->inventory->product_id;
                                $newInventory->service_id = $userService->id;
                                $newInventory->quantity = $selection->quantity;
                                $newInventory->batch_number = $selection->inventory->batch_number;
                                $newInventory->serial_number = $selection->inventory->serial_number;
                                $newInventory->expiry_date = $selection->inventory->expiry_date;
                                $newInventory->barcode = $selection->inventory->barcode;
                                $newInventory->save();
                                
                                \Log::info('Stock added to requesting service', [
                                    'new_inventory_id' => $newInventory->id,
                                    'product_id' => $newInventory->product_id,
                                    'service_id' => $userService->id,
                                    'quantity' => $selection->quantity
                                ]);
                            }
                        }
                    }
                    
                    $movement->status = 'fulfilled';
                    $movement->delivery_status = 'good';
                    
                } else if ($deliveryStatus === 'damaged') {
                    // Handle damaged products - don't add to inventory, mark as damaged
                    $movement->status = 'damaged';
                    $movement->delivery_status = 'damaged';
                    
                    // Log damaged items for tracking
                    foreach ($movement->items as $item) {
                        if ($item->selectedInventory && $item->selectedInventory->count() > 0) {
                            foreach ($item->selectedInventory as $selection) {
                                \Log::info('Damaged stock reported', [
                                    'movement_id' => $movement->id,
                                    'product_id' => $selection->inventory->product_id,
                                    'quantity' => $selection->quantity,
                                    'batch_number' => $selection->inventory->batch_number,
                                    'service_id' => $userService->id
                                ]);
                            }
                        }
                    }
                    
                } else if ($deliveryStatus === 'manque') {
                    // Handle missing quantities
                    $movement->status = 'partially_fulfilled';
                    $movement->delivery_status = 'manque';
                    $movement->missing_quantity = $missingQuantity;
                    
                    // Add received quantities to requester's inventory (if any)
                    foreach ($movement->items as $item) {
                        if ($item->selectedInventory && $item->selectedInventory->count() > 0) {
                            foreach ($item->selectedInventory as $selection) {
                                $receivedQuantity = max(0, $selection->quantity - $missingQuantity);
                                
                                if ($receivedQuantity > 0) {
                                    $newInventory = new \App\Models\Inventory();
                                    $newInventory->product_id = $selection->inventory->product_id;
                                    $newInventory->service_id = $userService->id;
                                    $newInventory->quantity = $receivedQuantity;
                                    $newInventory->batch_number = $selection->inventory->batch_number;
                                    $newInventory->serial_number = $selection->inventory->serial_number;
                                    $newInventory->expiry_date = $selection->inventory->expiry_date;
                                    $newInventory->barcode = $selection->inventory->barcode;
                                    $newInventory->save();
                                }
                            }
                        }
                    }
                }

                // Update movement with delivery confirmation
                $movement->delivery_confirmed_at = now();
                $movement->delivery_confirmed_by = Auth::id();
                $movement->delivery_notes = $notes;
                $movement->save();

                DB::commit();

                $statusMessage = $deliveryStatus === 'good' 
                    ? 'Delivery confirmed successfully. Items have been added to your inventory.' 
                    : ($deliveryStatus === 'damaged' 
                        ? 'Delivery confirmed as damaged. Items have been marked as damaged and not added to inventory.'
                        : 'Delivery confirmed with missing quantities. Partial stock has been added to your inventory.');

                return response()->json([
                    'success' => true,
                    'message' => $statusMessage,
                    'movement' => new StockMovementResource($movement->fresh())
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            \Log::error('Error confirming delivery: ' . $e->getMessage(), [
                'movement_id' => $movementId,
                'user_id' => Auth::id(),
                'status' => $request->status,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while confirming delivery: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirm individual product delivery status
     */
    public function confirmProduct(Request $request, $movementId)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|integer|exists:stock_movement_items,id',
            'status' => 'required|in:good,damaged,manque',
            'notes' => 'nullable|string|max:1000',
            'received_quantity' => 'nullable|numeric|min:0' // Add received_quantity for manque status
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userService = $this->getUserService();
            if (!$userService) {
                return response()->json([
                    'success' => false,
                    'message' => 'User service not found'
                ], 403);
            }

            $movement = StockMovement::where('id', $movementId)
                                   ->where('requesting_service_id', $userService->id)
                                   ->with(['items.selectedInventory.inventory', 'items.product'])
                                   ->first();

            if (!$movement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock movement not found or access denied'
                ], 404);
            }

            // Check if movement is in in_transfer status
            if ($movement->status !== 'in_transfer') {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock movement must be in transfer status to confirm delivery'
                ], 422);
            }

            // Find the specific item
            $item = $movement->items->where('id', $request->item_id)->first();
            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item not found in this movement'
                ], 404);
            }

            DB::beginTransaction();

            try {
                $confirmationStatus = $request->status;
                $notes = $request->notes;
                $receivedQuantity = $request->received_quantity;

                // Update item confirmation status
                $item->confirmation_status = $confirmationStatus;
                $item->confirmation_notes = $notes;
                $item->confirmed_at = now();
                $item->confirmed_by = Auth::id();

                if ($confirmationStatus === 'good') {
                    // For 'good' status: set executed_quantity equal to approved quantity
                    $item->executed_quantity = $item->approved_quantity;
                    
                    // Add stock to requester's inventory
                    if ($item->selectedInventory && $item->selectedInventory->count() > 0) {
                        foreach ($item->selectedInventory as $selection) {
                            // Create new inventory record for the requesting service
                            $newInventory = new \App\Models\Inventory();
                            $newInventory->product_id = $selection->inventory->product_id;
                            $newInventory->service_id = $userService->id;
                            $newInventory->quantity = $selection->quantity;
                            $newInventory->batch_number = $selection->inventory->batch_number;
                            $newInventory->serial_number = $selection->inventory->serial_number;
                            $newInventory->expiry_date = $selection->inventory->expiry_date;
                            $newInventory->barcode = $selection->inventory->barcode;
                            $newInventory->save();
                            
                            \Log::info('Stock added to requesting service for individual product', [
                                'new_inventory_id' => $newInventory->id,
                                'product_id' => $newInventory->product_id,
                                'service_id' => $userService->id,
                                'quantity' => $selection->quantity,
                                'item_id' => $item->id
                            ]);
                        }
                    }
                    
                } else if ($confirmationStatus === 'damaged') {
                    // Handle damaged products - don't add to inventory, mark as damaged
                    // Log damaged items for tracking
                    if ($item->selectedInventory && $item->selectedInventory->count() > 0) {
                        foreach ($item->selectedInventory as $selection) {
                            \Log::info('Damaged stock reported for individual product', [
                                'movement_id' => $movement->id,
                                'item_id' => $item->id,
                                'product_id' => $selection->inventory->product_id,
                                'quantity' => $selection->quantity,
                                'batch_number' => $selection->inventory->batch_number,
                                'service_id' => $userService->id
                            ]);
                        }
                    }
                    
                    // Set executed quantity to 0 for damaged items
                    $item->executed_quantity = 0;
                    
                } else if ($confirmationStatus === 'manque') {
                    // For 'manque' status: update received_quantity and set executed_quantity to received quantity
                    if ($receivedQuantity !== null) {
                        $item->received_quantity = $receivedQuantity;
                        $item->executed_quantity = $receivedQuantity;
                    } else {
                        // If no received quantity provided, set executed to 0
                        $item->executed_quantity = 0;
                    }
                    
                    // Handle missing quantities - don't add to inventory
                    \Log::info('Missing stock reported for individual product', [
                        'movement_id' => $movement->id,
                        'item_id' => $item->id,
                        'product_id' => $item->product_id,
                        'service_id' => $userService->id,
                        'received_quantity' => $receivedQuantity
                    ]);
                }

                $item->save();

                DB::commit();

                $statusMessage = $confirmationStatus === 'good' 
                    ? 'Product confirmed successfully. Item has been added to your inventory.' 
                    : ($confirmationStatus === 'damaged' 
                        ? 'Product confirmed as damaged. Item has been marked as damaged and not added to inventory.'
                        : 'Product confirmed as missing. Item has been marked as not received.');

                return response()->json([
                    'success' => true,
                    'message' => $statusMessage,
                    'item' => new StockMovementItemResource($item->fresh())
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            \Log::error('Error confirming individual product: ' . $e->getMessage(), [
                'movement_id' => $movementId,
                'item_id' => $request->item_id,
                'user_id' => Auth::id(),
                'status' => $request->status,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while confirming product: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate received quantities and automatically set status based on quantity comparison
     */
    public function validateQuantities(Request $request, $movementId)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*.item_id' => 'required|integer|exists:stock_movement_items,id',
            'items.*.received_quantity' => 'required|numeric|min:0',
            'items.*.sender_quantity' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'submitted_data' => $request->all()
            ], 422);
        }

        try {
            $userService = $this->getUserService();
            if (!$userService) {
                return response()->json([
                    'success' => false,
                    'message' => 'User service not found'
                ], 403);
            }

            $movement = StockMovement::where('id', $movementId)
                                   ->where('requesting_service_id', $userService->id)
                                   ->with(['items.product', 'items.selectedInventory.inventory'])
                                   ->first();

            if (!$movement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock movement not found or access denied'
                ], 404);
            }

            if ($movement->status !== 'in_transfer') {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock movement must be in transfer status to validate quantities'
                ], 422);
            }

            $validationResults = [];
            $hasShortages = false;

            DB::beginTransaction();

            foreach ($request->items as $validation) {
                $item = $movement->items->where('id', $validation['item_id'])->first();
                if (!$item) {
                    continue;
                }

                $requestedQuantity = $item->approved_quantity;
                $senderQuantity = $validation['sender_quantity'] ?? $item->sender_quantity ?? $requestedQuantity;
                $receivedQuantity = $validation['received_quantity'];
                
                // Calculate shortage based on sender quantity (what was actually sent)
                $shortageQuantity = max(0, $senderQuantity - $receivedQuantity);

                // Automatic status determination based on quantity comparison
                $automaticStatus = null;
                $executedQuantity = null;
                
                if ($receivedQuantity >= $senderQuantity) {
                    // Received quantity meets or exceeds sent quantity - set status to 'good'
                    $automaticStatus = 'good';
                    $executedQuantity = $item->approved_quantity; // Set executed to approved quantity
                } else {
                    // Received less than sent - set status to 'insufficient'
                    $automaticStatus = 'insufficient';
                    $executedQuantity = $receivedQuantity; // Set executed to received quantity
                    $hasShortages = true;
                }

                // Update the movement item with automatic status and quantities
                $item->update([
                    'received_quantity' => $receivedQuantity,
                    'confirmation_status' => $automaticStatus,
                    'executed_quantity' => $executedQuantity,
                    'updated_at' => now()
                ]);

                $result = [
                    'item_id' => $item->id,
                    'product_name' => $item->product->name,
                    'requested_quantity' => $requestedQuantity,
                    'sender_quantity' => $senderQuantity,
                    'received_quantity' => $receivedQuantity,
                    'shortage_quantity' => $shortageQuantity,
                    'has_shortage' => $shortageQuantity > 0,
                    'status' => $shortageQuantity > 0 ? 'insufficient' : 'good',
                    'automatic_status' => $automaticStatus,
                    'executed_quantity' => $executedQuantity,
                    'submitted_data' => $validation
                ];

                $validationResults[] = $result;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Quantities validated and statuses updated automatically',
                'validation_results' => $validationResults,
                'has_shortages' => $hasShortages,
                'total_items' => count($validationResults),
                'shortage_items' => collect($validationResults)->where('has_shortage', true)->count(),
                'summary' => [
                    'good_items' => collect($validationResults)->where('automatic_status', 'good')->count(),
                    'manque_items' => collect($validationResults)->where('automatic_status', 'manque')->count(),
                    'automatic_processing' => true
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error validating quantities: ' . $e->getMessage(), [
                'movement_id' => $movementId,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while validating quantities: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process validated quantities and handle shortages
     */
    public function processValidation(Request $request, $movementId)
    {
        $validator = Validator::make($request->all(), [
            'validations' => 'required|array',
            'validations.*.item_id' => 'required|integer|exists:stock_movement_items,id',
            'validations.*.received_quantity' => 'required|numeric|min:0',
            'validations.*.status' => 'required|in:good,insufficient',
            'validations.*.notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userService = $this->getUserService();
            if (!$userService) {
                return response()->json([
                    'success' => false,
                    'message' => 'User service not found'
                ], 403);
            }

            $movement = StockMovement::where('id', $movementId)
                                   ->where('requesting_service_id', $userService->id)
                                   ->with(['items.product', 'items.selectedInventory.inventory'])
                                   ->first();

            if (!$movement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock movement not found or access denied'
                ], 404);
            }

            if ($movement->status !== 'in_transfer') {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock movement must be in transfer status to process validation'
                ], 422);
            }

            DB::beginTransaction();

            try {
                $processedItems = [];
                $shortageRecords = [];

                foreach ($request->validations as $validation) {
                    $item = $movement->items->where('id', $validation['item_id'])->first();
                    if (!$item) {
                        continue;
                    }

                    $receivedQuantity = $validation['received_quantity'];
                    $requestedQuantity = $item->approved_quantity;
                    $shortageQuantity = max(0, $requestedQuantity - $receivedQuantity);

                    if ($validation['status'] === 'good') {
                        // Update item as good
                        $item->confirmation_status = 'good';
                        $item->confirmation_notes = $validation['notes'] ?? null;
                        $item->confirmed_at = now();
                        $item->confirmed_by = Auth::id();
                        $item->executed_quantity = $item->approved_quantity; // Set executed to approved quantity

                        // Add received stock to inventory
                        if ($item->selectedInventory && $item->selectedInventory->count() > 0) {
                            foreach ($item->selectedInventory as $selection) {
                                // Calculate proportional quantity for this selection
                                $proportionalQuantity = ($selection->quantity / $requestedQuantity) * $receivedQuantity;
                                
                                if ($proportionalQuantity > 0) {
                                    $newInventory = new \App\Models\Inventory();
                                    $newInventory->product_id = $selection->inventory->product_id;
                                    $newInventory->service_id = $userService->id;
                                    $newInventory->quantity = $proportionalQuantity;
                                    $newInventory->batch_number = $selection->inventory->batch_number;
                                    $newInventory->serial_number = $selection->inventory->serial_number;
                                    $newInventory->expiry_date = $selection->inventory->expiry_date;
                                    $newInventory->barcode = $selection->inventory->barcode;
                                    $newInventory->save();
                                }
                            }
                        }

                        $item->save();
                        $processedItems[] = $item;

                    } else if ($validation['status'] === 'shortage' && $shortageQuantity > 0) {
                        // Keep original record unchanged for received quantity
                        if ($receivedQuantity > 0) {
                            $item->confirmation_status = 'good';
                            $item->confirmation_notes = $validation['notes'] ?? null;
                            $item->confirmed_at = now();
                            $item->confirmed_by = Auth::id();
                            $item->executed_quantity = $item->approved_quantity; // Set executed to approved quantity
                            
                            // Update approved quantity to received quantity
                            $item->approved_quantity = $receivedQuantity;

                            // Add received stock to inventory (proportional)
                            if ($item->selectedInventory && $item->selectedInventory->count() > 0) {
                                foreach ($item->selectedInventory as $selection) {
                                    $proportionalQuantity = ($selection->quantity / $requestedQuantity) * $receivedQuantity;
                                    
                                    if ($proportionalQuantity > 0) {
                                        $newInventory = new \App\Models\Inventory();
                                        $newInventory->product_id = $selection->inventory->product_id;
                                        $newInventory->service_id = $userService->id;
                                        $newInventory->quantity = $proportionalQuantity;
                                        $newInventory->batch_number = $selection->inventory->batch_number;
                                        $newInventory->serial_number = $selection->inventory->serial_number;
                                        $newInventory->expiry_date = $selection->inventory->expiry_date;
                                        $newInventory->barcode = $selection->inventory->barcode;
                                        $newInventory->save();
                                    }
                                }
                            }

                            $item->save();
                            $processedItems[] = $item;
                        }

                        // Create new record for shortage quantity
                        $shortageItem = new \App\Models\StockMovementItem();
                        $shortageItem->stock_movement_id = $movement->id;
                        $shortageItem->product_id = $item->product_id;
                        $shortageItem->requested_quantity = $shortageQuantity;
                        $shortageItem->approved_quantity = $shortageQuantity;
                        $shortageItem->confirmation_status = 'manque';
                        $shortageItem->confirmation_notes = "Shortage: {$shortageQuantity} units not received. " . ($validation['notes'] ?? '');
                        $shortageItem->confirmed_at = now();
                        $shortageItem->confirmed_by = Auth::id();
                        $shortageItem->executed_quantity = 0;
                        $shortageItem->save();

                        $shortageRecords[] = $shortageItem;

                        \Log::info('Shortage record created', [
                            'movement_id' => $movement->id,
                            'original_item_id' => $item->id,
                            'shortage_item_id' => $shortageItem->id,
                            'product_id' => $item->product_id,
                            'shortage_quantity' => $shortageQuantity,
                            'received_quantity' => $receivedQuantity
                        ]);
                    }
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Validation processed successfully',
                    'processed_items' => count($processedItems),
                    'shortage_records' => count($shortageRecords),
                    'items' => $processedItems,
                    'shortages' => $shortageRecords
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            \Log::error('Error processing validation: ' . $e->getMessage(), [
                'movement_id' => $movementId,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing validation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Finalize confirmation process for the entire movement
     */
    public function finalizeConfirmation(Request $request, $movementId)
    {
        try {
            $userService = $this->getUserService();
            if (!$userService) {
                return response()->json([
                    'success' => false,
                    'message' => 'User service not found'
                ], 403);
            }

            $movement = StockMovement::where('id', $movementId)
                                   ->where('requesting_service_id', $userService->id)
                                   ->with(['items'])
                                   ->first();

            if (!$movement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock movement not found or access denied'
                ], 404);
            }

            // Check if movement is in in_transfer status
            if ($movement->status !== 'in_transfer') {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock movement must be in transfer status to finalize confirmation'
                ], 422);
            }

            DB::beginTransaction();

            try {
                // Check confirmation status of all items
                $confirmedItems = $movement->items->where('confirmation_status', '!=', null);
                $goodItems = $movement->items->where('confirmation_status', 'good');
                $damagedItems = $movement->items->where('confirmation_status', 'damaged');
                $manqueItems = $movement->items->where('confirmation_status', 'manque');

                // Determine overall movement status
                if ($goodItems->count() === $movement->items->count()) {
                    $movement->status = 'fulfilled';
                    $movement->delivery_status = 'good';
                } else if ($goodItems->count() > 0) {
                    $movement->status = 'partially_fulfilled';
                    $movement->delivery_status = 'mixed';
                } else if ($damagedItems->count() > 0) {
                    $movement->status = 'damaged';
                    $movement->delivery_status = 'damaged';
                } else {
                    $movement->status = 'unfulfilled';
                    $movement->delivery_status = 'manque';
                }

                // Update movement finalization details
                $movement->delivery_confirmed_at = now();
                $movement->delivery_confirmed_by = Auth::id();
                $movement->save();

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Confirmation finalized successfully.',
                    'movement' => new StockMovementResource($movement->fresh())
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            \Log::error('Error finalizing confirmation: ' . $e->getMessage(), [
                'movement_id' => $movementId,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while finalizing confirmation: ' . $e->getMessage()
            ], 500);
        }
    }
}
