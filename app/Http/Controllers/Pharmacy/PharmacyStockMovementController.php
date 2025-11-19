<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\PharmacyMovement;
use App\Models\PharmacyMovementItem;
use App\Models\User;
use App\Models\UserSpecialization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PharmacyStockMovementController extends Controller
{
    protected $approvalService;

    // public function __construct()
    // {
    //     $this->middleware('auth');
    //     // $this->approvalService = app(PharmacyApprovalService::class);
    // }

    /**
     * Get user's service
     */
    private function getUserService()
    {
        try {
            $user = Auth::user();

            // Get user's active specialization
            $userSpecialization = UserSpecialization::where('user_id', $user->id)
                ->where('status', 'active')
                ->with('specialization.service')
                ->first();
            if (! $userSpecialization) {
                \Log::warning('StockMovementController: No active specialization found for user', ['user_id' => $user->id]);

                return null;
            }

            if (! $userSpecialization->specialization) {
                \Log::warning('StockMovementController: Specialization not found', ['specialization_id' => $userSpecialization->specialization_id]);

                return null;
            }

            if (! $userSpecialization->specialization->service) {
                \Log::warning('StockMovementController: Service not found for specialization', [
                    'specialization_id' => $userSpecialization->specialization_id,
                    'service_id' => $userSpecialization->specialization->service_id,
                ]);

                return null;
            }

            return $userSpecialization->specialization->service;

        } catch (\Exception $e) {
            \Log::error('StockMovementController: Error getting user service', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Display a listing of pharmacy movements
     */
    public function index(Request $request)
    {
        $query = PharmacyMovement::with([
            'items.product' => function ($query) {
                $query->with(['inventories' => function ($inventoryQuery) {
                    $inventoryQuery->select('product_id', 'unit');
                }]);
            },
            'requestingService',
            'providingService',
            'requestingUser',
        ]);

        // Filter by status
        // if ($request->has('status') && $request->status !== 'all') {
        //     $query->where('status', $request->status);
        // }

        // Filter by controlled substance level
        if ($request->has('controlled_substance_level') && $request->controlled_substance_level !== 'all') {
            $query->whereHas('items.product', function ($q) use ($request) {
                $q->where('controlled_substance_level', $request->controlled_substance_level);
            });
        }

        // Filter by prescription requirement
        if ($request->has('requires_prescription')) {
            $query->whereHas('items.product', function ($q) use ($request) {
                $q->where('requires_prescription', $request->boolean('requires_prescription'));
            });
        }

        // Filter by service (for user's ALL services)
        $user = Auth::user();
        
        // Get ALL service IDs user is assigned to
        $userServiceIds = $user->activeSpecializations()
            ->with('specialization.service')
            ->get()
            ->pluck('specialization.service.id')
            ->filter()
            ->unique()
            ->toArray();

        if (!empty($userServiceIds)) {
            if ($request->has('role')) {
                if ($request->role === 'requester') {
                    // Show movements where user's services are requesting
                    $query->whereIn('requesting_service_id', $userServiceIds);
                } elseif ($request->role === 'provider') {
                    // Show movements where user's services are providing
                    $query->whereIn('providing_service_id', $userServiceIds);
                } else {
                    // default to both - show all movements for user's services
                    $query->where(function ($q) use ($userServiceIds) {
                        $q->whereIn('requesting_service_id', $userServiceIds)
                            ->orWhereIn('providing_service_id', $userServiceIds);
                    });
                }
            } else {
                // Show all movements for user's services (both requesting and providing)
                $query->where(function ($q) use ($userServiceIds) {
                    $q->whereIn('requesting_service_id', $userServiceIds)
                        ->orWhereIn('providing_service_id', $userServiceIds);
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

        // Add unit information and pharmacy-specific data to products
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
            'data' => $movements,
        ]);
    }

    /**
     * Get draft movements for current user
     */
    public function getDrafts()
    {
        $user = Auth::user();
        
        // Get ALL service IDs user is assigned to
        $userServiceIds = $user->activeSpecializations()
            ->with('specialization.service')
            ->get()
            ->pluck('specialization.service.id')
            ->filter()
            ->unique()
            ->toArray();

        if (empty($userServiceIds)) {
            return response()->json(['error' => 'User not assigned to any service'], 403);
        }

        $drafts = PharmacyMovement::with([
            'items.product' => function ($query) {
                $query->with(['inventories' => function ($inventoryQuery) {
                    $inventoryQuery->select('product_id', 'unit');
                }]);
            },
            'providingService',
            'requestingService',
            'requestingUser',
        ])
            ->whereIn('requesting_service_id', $userServiceIds)
            ->where('status', 'draft')
            ->where('requesting_user_id', $user->id)
            ->orderBy('updated_at', 'desc')
            ->get();

        // Add unit information and pharmacy-specific data to products
        $drafts->each(function ($draft) {
            $draft->items->each(function ($item) {
                if ($item->product && $item->product->inventories) {
                    $units = $item->product->inventories->pluck('unit')->filter()->unique();
                    $item->product->unit = $units->first() ?? 'units';
                }

                // Add pharmacy-specific flags
                if ($item->product) {
                    $item->product->is_controlled = ! empty($item->product->controlled_substance_level);
                    $item->product->requires_cold_storage = $item->product->storage_temperature_min < 8;
                }
            });
        });

        return response()->json([
            'success' => true,
            'data' => $drafts,
        ]);
    }

    /**
     * Get pending requests for approval
     */
    public function getPendingApprovals(Request $request)
    {
        $user = Auth::user();
        
        // Get ALL service IDs user is assigned to
        $userServiceIds = $user->activeSpecializations()
            ->with('specialization.service')
            ->get()
            ->pluck('specialization.service.id')
            ->filter()
            ->unique()
            ->toArray();

        if (empty($userServiceIds)) {
            return response()->json(['error' => 'User not assigned to any service'], 403);
        }

        $query = PharmacyMovement::with([
            'items.product' => function ($query) {
                $query->with(['inventories' => function ($inventoryQuery) {
                    $inventoryQuery->select('product_id', 'unit');
                }]);
            },
            'requestingService',
            'requestingUser',
        ])
            ->whereIn('providing_service_id', $userServiceIds)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc');

        $pendingRequests = $query->paginate($request->get('per_page', 15));

        // Add unit information and pharmacy-specific data to products
        $pendingRequests->each(function ($request) {
            $request->items->each(function ($item) {
                if ($item->product && $item->product->inventories) {
                    $units = $item->product->inventories->pluck('unit')->filter()->unique();
                    $item->product->unit = $units->first() ?? 'units';
                }

                // Add pharmacy-specific flags and alerts
                if ($item->product) {
                    $item->product->is_controlled = ! empty($item->product->controlled_substance_level);
                    $item->product->requires_cold_storage = $item->product->storage_temperature_min < 8;
                    $item->product->requires_prescription = $item->product->requires_prescription ?? false;
                }
            });
        });

        return response()->json([
            'success' => true,
            'data' => $pendingRequests,
        ]);
    }

    /**
     * Create a new draft pharmacy movement
     */
    public function createDraft(Request $request)
    {
        $request->validate([
            'requesting_service_id' => 'required|exists:services,id',
            'providing_service_id' => 'required|exists:services,id',
            'request_reason' => 'nullable|string|max:500',
            'expected_delivery_date' => 'nullable|date|after:today',
            'prescription_reference' => 'nullable|string|max:255',
            'patient_id' => 'nullable|exists:patients,id',
        ]);

        $user = Auth::user();
        
        // Verify user has access to the requesting service
        $userServiceIds = $user->activeSpecializations()
            ->with('specialization.service')
            ->get()
            ->pluck('specialization.service.id')
            ->filter()
            ->unique()
            ->toArray();

        if (!in_array($request->requesting_service_id, $userServiceIds)) {
            return response()->json(['error' => 'You do not have access to the selected requesting service'], 403);
        }
        
        // Prevent requesting from same service
        if ($request->requesting_service_id === $request->providing_service_id) {
            return response()->json(['error' => 'Cannot request from your own service'], 422);
        }

        // Check if user has any draft for this service combination
        $existingMovement = PharmacyMovement::where('requesting_service_id', $request->requesting_service_id)
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
                'providingService',
            ]);

            // Add unit information and pharmacy-specific data to products
            $data->items->each(function ($item) {
                if ($item->product && $item->product->inventories) {
                    $units = $item->product->inventories->pluck('unit')->filter()->unique();
                    $item->product->unit = $units->first() ?? 'units';
                }

                if ($item->product) {
                    $item->product->is_controlled = ! empty($item->product->controlled_substance_level);
                    $item->product->requires_cold_storage = $item->product->storage_temperature_min < 8;
                }
            });

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }

        $movement = PharmacyMovement::create([
            'movement_number' => $this->generateMovementNumber(),
            'requesting_service_id' => $request->requesting_service_id,
            'providing_service_id' => $request->providing_service_id,
            'requesting_user_id' => $user->id,
            'status' => 'draft',
            'request_reason' => $request->request_reason,
            'expected_delivery_date' => $request->expected_delivery_date,
            'prescription_reference' => $request->prescription_reference,
            'patient_id' => $request->patient_id,
        ]);

        $data = $movement->load([
            'items.product' => function ($query) {
                $query->with(['inventories' => function ($inventoryQuery) {
                    $inventoryQuery->select('product_id', 'unit');
                }]);
            },
            'providingService',
        ]);

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Draft created successfully',
        ]);
    }

    /**
     * Add item to pharmacy movement
     */
    public function addItem(Request $request, $movementId)
    {
        $request->validate([
            'product_id' => 'required|exists:pharmacy_products,id',
            'requested_quantity' => 'required|numeric|min:0.01',
            'quantity_by_box' => 'boolean',
            'notes' => 'nullable|string|max:255',
            'dosage_instructions' => 'nullable|string|max:500',
            'administration_route' => 'nullable|string|max:100',
        ]);

        $movement = PharmacyMovement::findOrFail($movementId);

        // Check if user owns this draft
        // if ($movement->requesting_user_id !== Auth::id() || $movement->status !== 'draft') {
        //     return response()->json(['error' => 'Unauthorized or invalid movement'], 403);
        // }

        // Check if item already exists
        $existingItem = $movement->items()->where('product_id', $request->product_id)->first();

        if ($existingItem) {
            $existingItem->update([
                'requested_quantity' => $request->requested_quantity,
                'quantity_by_box' => $request->boolean('quantity_by_box', false),
                'notes' => $request->notes,
                'dosage_instructions' => $request->dosage_instructions,
                'administration_route' => $request->administration_route,
            ]);

            $data = $existingItem->load('pharmacyProduct');

            // Add pharmacy-specific data to product
            if ($data->pharmacyProduct) {
                $data->pharmacyProduct->is_controlled = $data->pharmacyProduct->is_controlled_substance ?? false;
                $data->pharmacyProduct->requires_cold_storage = false; // Set based on your logic
                $data->product = $data->pharmacyProduct; // Alias for frontend compatibility
            }

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Item updated successfully',
            ]);
        }

        $item = $movement->items()->create([
            'product_id' => $request->product_id,
            'requested_quantity' => $request->requested_quantity,
            'quantity_by_box' => $request->boolean('quantity_by_box', false),
            'notes' => $request->notes,
            'dosage_instructions' => $request->dosage_instructions,
            'administration_route' => $request->administration_route,
        ]);

        $data = $item->load('pharmacyProduct');

        // Add pharmacy-specific data to product
        if ($data->pharmacyProduct) {
            $data->pharmacyProduct->is_controlled = $data->pharmacyProduct->is_controlled_substance ?? false;
            $data->pharmacyProduct->requires_cold_storage = false; // Set based on your logic
            $data->product = $data->pharmacyProduct; // Alias for frontend compatibility
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Item added successfully',
        ]);
    }

    /**
     * Update item in draft movement
     */
    public function updateItem(Request $request, $movementId, $itemId)
    {
        $request->validate([
            'product_id' => 'required|exists:pharmacy_products,id',
            'requested_quantity' => 'required|numeric|min:0.01',
            'quantity_by_box' => 'boolean',
            'notes' => 'nullable|string|max:255',
            'dosage_instructions' => 'nullable|string|max:500',
            'administration_route' => 'nullable|string|max:100',
        ]);

        $movement = PharmacyMovement::findOrFail($movementId);

        // Check if user owns this draft
        // if ($movement->requesting_user_id !== Auth::id() || $movement->status !== 'draft') {
        //     return response()->json(['error' => 'Unauthorized or invalid movement'], 403);
        // }

        $item = $movement->items()->findOrFail($itemId);

        $item->update([
            'product_id' => $request->product_id,
            'requested_quantity' => $request->requested_quantity,
            'quantity_by_box' => $request->boolean('quantity_by_box', false),
            'notes' => $request->notes,
            'dosage_instructions' => $request->dosage_instructions,
            'administration_route' => $request->administration_route,
        ]);

        $data = $item->load([
            'product' => function ($query) {
                $query->with(['inventories' => function ($inventoryQuery) {
                    $inventoryQuery->select('product_id', 'unit');
                }]);
            },
        ]);

        // Add unit information and pharmacy-specific data to product
        if ($data->product && $data->product->inventories) {
            $units = $data->product->inventories->pluck('unit')->filter()->unique();
            $data->product->unit = $units->first() ?? 'units';
        }

        if ($data->product) {
            $data->product->is_controlled = ! empty($data->product->controlled_substance_level);
            $data->product->requires_cold_storage = $data->product->storage_temperature_min < 8;
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Item updated successfully',
        ]);
    }

    /**
     * Remove item from draft movement
     */
    public function removeItem($movementId, $itemId)
    {
        $movement = PharmacyMovement::findOrFail($movementId);

        // if ($movement->requesting_user_id !== Auth::id() || $movement->status !== 'draft') {
        //     return response()->json(['error' => 'Unauthorized or invalid movement'], 403);
        // }

        $item = $movement->items()->findOrFail($itemId);
        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item removed successfully',
        ]);
    }

    /**
     * Send movement for approval (handles POST /api/pharmacy/stock-movements/{id}/send)
     */
    public function send(Request $request, $movementId)
    {
        $movement = PharmacyMovement::findOrFail($movementId);

        // if ($movement->requesting_user_id !== Auth::id() || $movement->status !== 'pending') {
        //     return response()->json(['error' => 'Unauthorized or invalid movement'], 403);
        // }

        if ($movement->items()->count() === 0) {
            return response()->json(['error' => 'Cannot send empty request'], 422);
        }

        // Validate controlled substances requirements
        $controlledItems = $movement->items()->whereHas('product', function ($q) {
            $q->whereNotNull('controlled_substance_level');
        })->get();

        if ($controlledItems->count() > 0 && empty($movement->prescription_reference)) {
            return response()->json(['error' => 'Prescription reference required for controlled substances'], 422);
        }

        try {
            DB::beginTransaction();

            $movement->update([
                'status' => 'pending',
                'requested_at' => now(),
            ]);

            // Notify providing service (placeholder implementation)
            // $this->notifyService($movement->providing_service_id, 'new_pharmacy_movement_request', $movement);

            DB::commit();

            $data = $movement->load([
                'items.product' => function ($query) {
                    $query->with(['inventories' => function ($inventoryQuery) {
                        $inventoryQuery->select('product_id', 'unit');
                    }]);
                },
            ]);

            // Add unit information to products
            $data->items->each(function ($item) {
                if ($item->product && $item->product->inventories) {
                    $units = $item->product->inventories->pluck('unit')->filter()->unique();
                    $item->product->unit = $units->first() ?? 'units';
                }

                // Add pharmacy-specific flags
                if ($item->product) {
                    $item->product->is_controlled = ! empty($item->product->controlled_substance_level);
                    $item->product->requires_cold_storage = $item->product->storage_temperature_min < 8;
                    $item->product->requires_prescription = $item->product->requires_prescription ?? false;
                }
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Request sent successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => 'Failed to send movement: '.$e->getMessage()], 500);
        }
    }

    /**
     * Send draft movement (change status to pending)
     */
    public function sendDraft($movementId)
    {
        $movement = PharmacyMovement::findOrFail($movementId);

        // if ($movement->requesting_user_id !== Auth::id() || $movement->status !== 'draft') {
        //     return response()->json(['error' => 'Unauthorized or invalid movement'], 403);
        // }

        if ($movement->items()->count() === 0) {
            return response()->json(['error' => 'Cannot send empty request'], 422);
        }

        // Validate controlled substances requirements
        $controlledItems = $movement->items()->whereHas('product', function ($q) {
            $q->whereNotNull('controlled_substance_level');
        })->get();

        if ($controlledItems->count() > 0 && empty($movement->prescription_reference)) {
            return response()->json(['error' => 'Prescription reference required for controlled substances'], 422);
        }

        $movement->update([
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        // Notify providing service
        $this->notifyService($movement->providing_service_id, 'new_pharmacy_movement_request', $movement);

        $data = $movement->load([
            'items.product' => function ($query) {
                $query->with(['inventories' => function ($inventoryQuery) {
                    $inventoryQuery->select('product_id', 'unit');
                }]);
            },
        ]);

        // Add unit information and pharmacy-specific data to products
        $data->items->each(function ($item) {
            if ($item->product && $item->product->inventories) {
                $units = $item->product->inventories->pluck('unit')->filter()->unique();
                $item->product->unit = $units->first() ?? 'units';
            }

            if ($item->product) {
                $item->product->is_controlled = ! empty($item->product->controlled_substance_level);
                $item->product->requires_cold_storage = $item->product->storage_temperature_min < 8;
            }
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Request sent successfully',
        ]);
    }

    /**
     * Get low stock/expiring products for suggestions with pharmacy-specific alerts
     */
    public function getSuggestions(Request $request)
    {
        try {
            // Authentication and authorization checks
            $user = Auth::user();
            if (! $user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $userService = UserSpecialization::where('user_id', $user->id)->first();
            if (! $userService) {
                return response()->json(['error' => 'User service not found'], 403);
            }

            $providing_service_id = $request->input('providing_service_id', $userService->providing_service_id);
            if (! $providing_service_id) {
                return response()->json(['error' => 'Providing service ID is required'], 400);
            }

            // Get products with different stock levels (pharmacy-specific)
            $criticalLowStock = DB::table('pharmacy_inventories')
                ->join('pharmacy_products', 'pharmacy_inventories.pharmacy_product_id', '=', 'pharmacy_products.id')
                ->join('pharmacy_stockages', 'pharmacy_inventories.pharmacy_stockage_id', '=', 'pharmacy_stockages.id')
                ->where('pharmacy_stockages.service_id', $providing_service_id)
                ->whereRaw('pharmacy_inventories.quantity <= 5')
                ->whereRaw('pharmacy_inventories.quantity > 0')
                ->select(
                    'pharmacy_products.*',
                    'pharmacy_inventories.quantity as current_stock',
                    'pharmacy_inventories.expiry_date',
                    'pharmacy_inventories.batch_number',
                    'pharmacy_stockages.name as storage_name',
                    'pharmacy_stockages.temperature_controlled',
                    'pharmacy_stockages.security_level',
                    DB::raw('DATEDIFF(pharmacy_inventories.expiry_date, CURDATE()) as days_until_expiry')
                )
                ->orderBy('pharmacy_inventories.quantity', 'asc')
                ->get();

            $lowStock = DB::table('pharmacy_inventories')
                ->join('pharmacy_products', 'pharmacy_inventories.pharmacy_product_id', '=', 'pharmacy_products.id')
                ->join('pharmacy_stockages', 'pharmacy_inventories.pharmacy_stockage_id', '=', 'pharmacy_stockages.id')
                ->where('pharmacy_stockages.service_id', $providing_service_id)
                ->whereRaw('pharmacy_inventories.quantity <= 10 AND pharmacy_inventories.quantity > 5')
                ->select(
                    'pharmacy_products.*',
                    'pharmacy_inventories.quantity as current_stock',
                    'pharmacy_inventories.expiry_date',
                    'pharmacy_inventories.batch_number',
                    'pharmacy_stockages.name as storage_name',
                    'pharmacy_stockages.temperature_controlled',
                    'pharmacy_stockages.security_level',
                    DB::raw('DATEDIFF(pharmacy_inventories.expiry_date, CURDATE()) as days_until_expiry')
                )
                ->orderBy('pharmacy_inventories.quantity', 'asc')
                ->get();

            // Get products expiring soon (within 30 days) - critical for pharmacy
            $expiringSoon = DB::table('pharmacy_inventories')
                ->join('pharmacy_products', 'pharmacy_inventories.pharmacy_product_id', '=', 'pharmacy_products.id')
                ->join('pharmacy_stockages', 'pharmacy_inventories.pharmacy_stockage_id', '=', 'pharmacy_stockages.id')
                ->where('pharmacy_stockages.service_id', $providing_service_id)
                ->whereNotNull('pharmacy_inventories.expiry_date')
                ->whereRaw('DATEDIFF(pharmacy_inventories.expiry_date, CURDATE()) <= 30')
                ->whereRaw('DATEDIFF(pharmacy_inventories.expiry_date, CURDATE()) > 0')
                ->select(
                    'pharmacy_products.*',
                    'pharmacy_inventories.quantity as current_stock',
                    'pharmacy_inventories.expiry_date',
                    'pharmacy_inventories.batch_number',
                    'pharmacy_stockages.name as storage_name',
                    'pharmacy_stockages.temperature_controlled',
                    'pharmacy_stockages.security_level',
                    DB::raw('DATEDIFF(pharmacy_inventories.expiry_date, CURDATE()) as days_until_expiry')
                )
                ->orderBy('pharmacy_inventories.expiry_date', 'asc')
                ->get();

            // Get expired products
            $expired = DB::table('pharmacy_inventories')
                ->join('pharmacy_products', 'pharmacy_inventories.pharmacy_product_id', '=', 'pharmacy_products.id')
                ->join('pharmacy_stockages', 'pharmacy_inventories.pharmacy_stockage_id', '=', 'pharmacy_stockages.id')
                ->where('pharmacy_stockages.service_id', $providing_service_id)
                ->whereNotNull('pharmacy_inventories.expiry_date')
                ->whereRaw('pharmacy_inventories.expiry_date < CURDATE()')
                ->whereRaw('pharmacy_inventories.quantity > 0')
                ->select(
                    'pharmacy_products.*',
                    'pharmacy_inventories.quantity as current_stock',
                    'pharmacy_inventories.expiry_date',
                    'pharmacy_inventories.batch_number',
                    'pharmacy_stockages.name as storage_name',
                    'pharmacy_stockages.temperature_controlled',
                    'pharmacy_stockages.security_level',
                    DB::raw('DATEDIFF(CURDATE(), pharmacy_inventories.expiry_date) as days_expired')
                )
                ->orderBy('pharmacy_inventories.expiry_date', 'desc')
                ->get();

            // Get controlled substances requiring special attention
            $controlledSubstances = DB::table('pharmacy_inventories')
                ->join('pharmacy_products', 'pharmacy_inventories.pharmacy_product_id', '=', 'pharmacy_products.id')
                ->join('pharmacy_stockages', 'pharmacy_inventories.pharmacy_stockage_id', '=', 'pharmacy_stockages.id')
                ->where('pharmacy_stockages.service_id', $providing_service_id)
                ->where('pharmacy_products.is_controlled_substance', true)
                ->whereRaw('pharmacy_inventories.quantity > 0')
                ->select(
                    'pharmacy_products.*',
                    'pharmacy_inventories.quantity as current_stock',
                    'pharmacy_inventories.expiry_date',
                    'pharmacy_inventories.batch_number',
                    'pharmacy_stockages.name as storage_name',
                    'pharmacy_stockages.security_level',
                    'pharmacy_products.is_controlled_substance',
                    'pharmacy_products.requires_prescription'
                )
                ->orderBy('pharmacy_products.name', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'critical_low' => $criticalLowStock,
                    'low_stock' => $lowStock,
                    'expiring_soon' => $expiringSoon,
                    'expired' => $expired,
                    'controlled_substances' => $controlledSubstances,
                    'counts' => [
                        'critical_low' => $criticalLowStock->count(),
                        'low_stock' => $lowStock->count(),
                        'expiring_soon' => $expiringSoon->count(),
                        'expired' => $expired->count(),
                        'controlled_substances' => $controlledSubstances->count(),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting suggestions', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Failed to get suggestions'], 500);
        }
    }

    /**
     * Get movement statistics with pharmacy-specific metrics
     */
    public function getStats()
    {
        $user = Auth::user();
        $userService = $this->getUserService();

        if (! $userService) {
            return response()->json(['error' => 'User not assigned to any service'], 403);
        }

        $stats = [
            'draft' => PharmacyMovement::where('requesting_service_id', $userService->id)
                ->where('status', 'draft')->count(),
            'requesting_pending' => PharmacyMovement::where('requesting_service_id', $userService->id)
                ->where('status', 'pending')->count(),
            'providing_pending' => PharmacyMovement::where('providing_service_id', $userService->id)
                ->where('status', 'pending')->count(),
            'approved' => PharmacyMovement::where('providing_service_id', $userService->id)
                ->where('status', 'approved')->count(),
            'rejected' => PharmacyMovement::where('requesting_service_id', $userService->id)
                ->where('status', 'rejected')->count(),
            'executed' => PharmacyMovement::where(function ($q) use ($userService) {
                $q->where('requesting_service_id', $userService->id)
                    ->orWhere('providing_service_id', $userService->id);
            })->where('status', 'executed')->count(),

            // Pharmacy-specific stats
            'controlled_substances_pending' => PharmacyMovement::where('providing_service_id', $userService->id)
                ->where('status', 'pending')
                ->whereHas('items.product', function ($q) {
                    $q->whereNotNull('controlled_substance_level');
                })->count(),
            'prescription_required_pending' => PharmacyMovement::where('providing_service_id', $userService->id)
                ->where('status', 'pending')
                ->whereHas('items.product', function ($q) {
                    $q->where('requires_prescription', true);
                })->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Show specific movement
     */
    public function show($movementId)
    {
        $movement = PharmacyMovement::with([
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
        // Temporarily disabled for testing - TODO: Re-enable with proper authentication
        // if (!$userService ||
        //     ($movement->requesting_service_id !== $userService->id &&
        //      $movement->providing_service_id !== $userService->id)) {
        //     return response()->json(['error' => 'Unauthorized'], 403);
        // }

        // Add unit information and pharmacy-specific data to products
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

                // Add pharmacy-specific flags
                $item->product->is_controlled = ! empty($item->product->controlled_substance_level);
                $item->product->requires_cold_storage = $item->product->storage_temperature_min < 8;
                $item->product->requires_prescription = $item->product->requires_prescription ?? false;
            }

            // Add provided_quantity (this might be the approved_quantity or requested_quantity based on context)
            $item->provided_quantity = $item->approved_quantity ?? $item->requested_quantity ?? 0;
        });

        return response()->json([
            'success' => true,
            'data' => $movement,
        ]);
    }

    /**
     * Delete draft movement
     */
    public function destroy($movementId)
    {
        $movement = PharmacyMovement::findOrFail($movementId);

        if ($movement->requesting_user_id !== Auth::id() || $movement->status !== 'draft') {
            return response()->json(['error' => 'Unauthorized or cannot delete'], 403);
        }

        $movement->delete();

        return response()->json([
            'success' => true,
            'message' => 'Draft deleted successfully',
        ]);
    }

    /**
     * Get available stock for a product in a movement context
     */
    public function availableStock($movementId, Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:pharmacy_products,id',
        ]);

        $movement = PharmacyMovement::findOrFail($movementId);
        $productId = $request->product_id;

        $user = Auth::user();
        $userService = $this->getUserService();

        // Check if user has access to this movement
        if (! $userService ||
            ($movement->requesting_service_id !== $userService->id &&
             $movement->providing_service_id !== $userService->id)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Get available stock from the providing service's pharmacy storages
        $providingService = $movement->providingService;
        $availableStock = 0;

        if ($providingService) {
            // Get stock from service's pharmacy stockages using pharmacy_inventories
            $storages = $providingService->pharmacyStockages;
            foreach ($storages as $storage) {
                $inventory = $storage->pharmacyInventories()->where('pharmacy_product_id', $productId)->first();
                if ($inventory) {
                    $availableStock += $inventory->quantity;
                }
            }
        }

        return response()->json([
            'success' => true,
            'available_stock' => $availableStock,
        ]);
    }

    /**
     * Get inventory items for a product in movement context
     */
    public function getProductInventory($movementId, $productId)
    {
        $movement = PharmacyMovement::findOrFail($movementId);
        $user = Auth::user();
        $userService = $this->getUserService();

        // Check if user has access to this movement and is the providing service
        if (! $userService || $movement->providing_service_id !== $userService->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Get inventory items for this product from the providing service's pharmacy storages
        $providingService = $movement->providingService;
        $inventoryItems = [];

        if ($providingService) {
            $storages = $providingService->pharmacyStockages;
            foreach ($storages as $storage) {
                $inventories = $storage->pharmacyInventories()
                    ->with('pharmacyProduct')
                    ->where('pharmacy_product_id', $productId)
                    ->where('quantity', '>', 0)
                    ->get();

                foreach ($inventories as $inventory) {
                    $inventoryItems[] = [
                        'id' => $inventory->id,
                        'batch_number' => $inventory->batch_number,
                        'serial_number' => $inventory->serial_number,
                        'quantity' => $inventory->quantity,
                        'expiry_date' => $inventory->expiry_date,
                        'purchase_price' => $inventory->purchase_price,
                        'storage' => [
                            'id' => $storage->id,
                            'name' => $storage->name,
                            'temperature_controlled' => $storage->temperature_controlled,
                            'security_level' => $storage->security_level,
                        ],
                        'product' => $inventory->pharmacyProduct,
                        // Pharmacy-specific flags
                        'is_controlled' => $inventory->pharmacyProduct->is_controlled_substance ?? false,
                        'requires_prescription' => $inventory->pharmacyProduct->requires_prescription ?? false,
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => $inventoryItems,
        ]);
    }

    /**
     * Save selected inventory items for a movement item
     */
    public function selectInventory(Request $request, $movementId)
    {
        $request->validate([
            'item_id' => 'required|exists:pharmacy_stock_movement_items,id',
            'selected_inventory' => 'required|array',
            'selected_inventory.*.inventory_id' => 'required|exists:inventories,id',
            'selected_inventory.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $movement = PharmacyMovement::findOrFail($movementId);
        $user = Auth::user();
        $userService = $this->getUserService();

        // Check if user has access to this movement and is the providing service
        if (! $userService || $movement->providing_service_id !== $userService->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $item = PharmacyMovementItem::findOrFail($request->item_id);

        // Verify the item belongs to this movement
        if ($item->pharmacy_stock_movement_id !== $movement->id) {
            return response()->json(['error' => 'Invalid item for this movement'], 403);
        }

        DB::transaction(function () use ($item, $request) {
            // Clear existing selections for this item
            DB::table('pharmacy_movement_inventory_selections')
                ->where('pharmacy_stock_movement_item_id', $item->id)
                ->delete();

            // Save new selections
            $totalSelectedQuantity = 0;
            foreach ($request->selected_inventory as $selection) {
                // Verify the inventory item exists and has sufficient quantity
                $inventory = DB::table('pharmacy_inventories')->find($selection['inventory_id']);
                if (! $inventory) {
                    throw new \Exception('Inventory item not found: '.$selection['inventory_id']);
                }

                // Use quantity from pharmacy inventory
                $availableQuantity = $inventory->quantity;
                if ($availableQuantity < $selection['quantity']) {
                    throw new \Exception('Insufficient inventory for item: '.$inventory->batch_number.'. Available: '.$availableQuantity.', Requested: '.$selection['quantity']);
                }

                DB::table('pharmacy_movement_inventory_selections')->insert([
                    'pharmacy_stock_movement_item_id' => $item->id,
                    'pharmacy_inventory_id' => $selection['inventory_id'],
                    'selected_quantity' => $selection['quantity'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $totalSelectedQuantity += $selection['quantity'];
            }

            // Update the item's provided quantity
            $item->update([
                'provided_quantity' => $totalSelectedQuantity,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Inventory selection saved successfully',
        ]);
    }

    // Helper methods
    private function generateMovementNumber()
    {
        $year = date('Y');
        $lastMovement = PharmacyMovement::where('movement_number', 'like', "PM-{$year}-%")
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

        return 'PM-'.$year.'-'.str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    private function notifyService($serviceId, $event, $movement)
    {
        // Implementation depends on your notification system
        // Could use Laravel notifications, database notifications, or email
        // Pharmacy-specific notifications for controlled substances, etc.
    }

    /**
     * Initialize transfer for approved pharmacy movement
     */
    public function initializeTransfer(Request $request, $movementId)
    {
        try {
            // Get the movement without requiring user to own the providing service
            // The user can initialize transfer from the receiver side (StockMovementView)
            $movement = PharmacyMovement::where('id', $movementId)
                ->with(['items.product', 'providingService', 'requestingService'])
                ->first();

            if (! $movement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pharmacy movement not found',
                ], 404);
            }

            // Check if movement is in approved status
            if ($movement->status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pharmacy movement must be in approved status to initialize transfer',
                ], 422);
            }

            // Check if there are any approved items
            $approvedItems = $movement->items()->where('approved_quantity', '>', 0)->get();
            if ($approvedItems->count() === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No approved items to transfer',
                ], 422);
            }

            DB::beginTransaction();

            try {
                // Update status to 'in_transfer' for all approved items
                $movement->items()
                    ->where('approved_quantity', '>', 0)
                    ->update([
                        'status' => 'in_transfer',
                        'provided_quantity' => DB::raw('`approved_quantity`'),
                    ]);

                // Update movement status to 'in_transfer'
                $movement->update([
                    'status' => 'in_transfer',
                    'transfer_initiated_at' => now(),
                    'transfer_initiated_by' => Auth::id(),
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Transfer initialized successfully',
                    'data' => $movement->fresh(['items.product', 'requestingService', 'providingService']),
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Error initializing pharmacy transfer: '.$e->getMessage(), [
                'movement_id' => $movementId,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while initializing transfer: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get movement statistics with pharmacy-specific metrics
     */
    public function getStatistics()
    {
        $user = Auth::user();
        $userService = $this->getUserService();

        if (! $userService) {
            return response()->json(['error' => 'User not assigned to any service'], 403);
        }

        $stats = [
            'draft' => PharmacyMovement::where('requesting_service_id', $userService->id)
                ->where('status', 'draft')->count(),
            'requesting_pending' => PharmacyMovement::where('requesting_service_id', $userService->id)
                ->where('status', 'pending')->count(),
            'providing_pending' => PharmacyMovement::where('providing_service_id', $userService->id)
                ->where('status', 'pending')->count(),
            'approved' => PharmacyMovement::where('providing_service_id', $userService->id)
                ->where('status', 'approved')->count(),
            'rejected' => PharmacyMovement::where('requesting_service_id', $userService->id)
                ->where('status', 'rejected')->count(),
            'executed' => PharmacyMovement::where(function ($q) use ($userService) {
                $q->where('requesting_service_id', $userService->id)
                    ->orWhere('providing_service_id', $userService->id);
            })->where('status', 'executed')->count(),

            // Pharmacy-specific stats
            'controlled_substances_pending' => PharmacyMovement::where('providing_service_id', $userService->id)
                ->where('status', 'pending')
                ->whereHas('items.product', function ($q) {
                    $q->whereNotNull('controlled_substance_level');
                })->count(),
            'prescription_required_pending' => PharmacyMovement::where('providing_service_id', $userService->id)
                ->where('status', 'pending')
                ->whereHas('items.product', function ($q) {
                    $q->where('requires_prescription', true);
                })->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Show specific movement
     */

    /**
     * Delete draft movement
     */

    /**
     * Get available stock for a product in a movement context
     */

    /**
     * Get inventory items for a product in movement context
     */

    /**
     * Save selected inventory items for a movement item
     */

    /**
     * Get movement statistics with pharmacy-specific metrics
     */
    // public function getStats(Request $request)
    // {
    //     try {
    //         // Total number of stock movements
    //         $totalMovements = PharmacyMovement::count();

    //         // Movements by type (e.g., 'in', 'out', 'transfer')
    //         $movementsByType = PharmacyMovement::select('movement_type', DB::raw('count(*) as total'))
    //             ->groupBy('movement_type')
    //             ->get();

    //         // Movements by status (e.g., 'pending', 'completed', 'cancelled')
    //         $movementsByStatus = PharmacyMovement::select('status', DB::raw('count(*) as total'))
    //             ->groupBy('status')
    //             ->get();

    //         // You can add more statistics here, e.g.,
    //         // - Value of moved stock
    //         // - Average movement size
    //         // - Top products by movement volume

    //         return response()->json([
    //             'message' => 'Stock movement statistics fetched successfully',
    //             'data' => [
    //                 'total_movements' => $totalMovements,
    //                 'movements_by_type' => $movementsByType,
    //                 'movements_by_status' => $movementsByStatus,
    //             ],
    //         ]);
    //     } catch (\Exception $e) {
    //         Log::error('Failed to fetch stock movement statistics: ' . $e->getMessage());
    //         return response()->json([
    //             'message' => 'Failed to fetch stock movement statistics',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    /**
     * Get stock movement history with filters.
     */
    public function getHistory(Request $request)
    {
        $query = PharmacyMovement::with([
            'items.product' => function ($query) {
                $query->with(['inventories' => function ($inventoryQuery) {
                    $inventoryQuery->select('product_id', 'unit');
                }]);
            },
            'requestingService',
            'providingService',
            'requestingUser',
        ]);

        // Filter by product
        if ($request->has('product_id')) {
            $query->whereHas('items', function ($q) use ($request) {
                $q->where('product_id', $request->product_id);
            });
        }

        // Filter by movement type (e.g., 'transfer', 'adjustment') - assuming a type field exists or can be inferred
        // For now, we'll filter by status as a proxy for type if a direct type field isn't available
        if ($request->has('movement_type')) {
            $query->where('status', $request->movement_type); // This might need adjustment based on actual 'type' field
        }

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
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

        // Add unit information and pharmacy-specific data to products
        $movements->each(function ($movement) {
            $movement->items->each(function ($item) {
                if ($item->product && $item->product->inventories) {
                    $units = $item->product->inventories->pluck('unit')->filter()->unique();
                    $item->product->unit = $units->first() ?? 'units';
                }
                // Add pharmacy-specific flags
                if ($item->product) {
                    $item->product->is_controlled = ! empty($item->product->controlled_substance_level);
                    $item->product->requires_cold_storage = $item->product->storage_temperature_min < 8;
                }
            });
        });

        return response()->json([
            'success' => true,
            'data' => $movements,
        ]);
    }

    /**
     * Approve a pharmacy movement
     */
    public function approveItems(Request $request, $movementId)
    {
        try {
            $movement = PharmacyMovement::findOrFail($movementId);

            if ($movement->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Movement must be in pending status to approve',
                ], 422);
            }

            $movement->update([
                'status' => 'approved',
                'approving_user_id' => Auth::id(),
                'approved_at' => now(),
                'approval_notes' => $request->input('approval_notes', 'Approved'),
            ]);

            // Auto-approve all items
            $movement->items()->update([
                'approved_quantity' => DB::raw('requested_quantity'),
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Movement approved successfully',
                'data' => $movement->fresh(['items', 'requestingService', 'providingService']),
            ]);
        } catch (\Exception $e) {
            Log::error('Error approving pharmacy movement: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve movement: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject a pharmacy movement
     */
    public function rejectItems(Request $request, $movementId)
    {
        try {
            $movement = PharmacyMovement::findOrFail($movementId);

            if ($movement->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Movement must be in pending status to reject',
                ], 422);
            }

            $movement->update([
                'status' => 'rejected',
                'approving_user_id' => Auth::id(),
                'approved_at' => now(),
                'approval_notes' => $request->input('rejection_reason', 'Rejected'),
            ]);

            // Reject all items
            $movement->items()->update([
                'approved_quantity' => 0,
                'rejected_by' => Auth::id(),
                'rejected_at' => now(),
                'rejection_reason' => $request->input('rejection_reason', 'Not approved'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Movement rejected successfully',
                'data' => $movement->fresh(['items', 'requestingService', 'providingService']),
            ]);
        } catch (\Exception $e) {
            Log::error('Error rejecting pharmacy movement: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject movement: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Confirm individual product delivery with quantity validation
     */
    public function confirmProduct(Request $request, $movementId)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'item_id' => 'required|integer|exists:pharmacy_stock_movement_items,id',
            'status' => 'required|in:good,damaged,manque',
            'notes' => 'nullable|string|max:1000',
            'received_quantity' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $movement = PharmacyMovement::where('id', $movementId)
                ->with(['items.product', 'items.selected_inventory.inventory', 'requestingService'])
                ->first();

            if (!$movement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pharmacy movement not found',
                ], 404);
            }

            // Check if movement is in in_transfer status
            if ($movement->status !== 'in_transfer') {
                return response()->json([
                    'success' => false,
                    'message' => 'Movement must be in transfer status to confirm delivery',
                ], 422);
            }

            // Find the specific item
            $item = $movement->items->where('id', $request->item_id)->first();
            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item not found in this movement',
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

                    // Add stock to requester's inventory (pharmacy inventory)
                    if ($item->selected_inventory && $item->selected_inventory->count() > 0) {
                        foreach ($item->selected_inventory as $selection) {
                            // Create new pharmacy inventory record for the requesting service
                            $newInventory = new \App\Models\PharmacyInventory;
                            $newInventory->pharmacy_product_id = $selection->inventory->pharmacy_product_id ?? $item->product_id;
                            $newInventory->pharmacy_stockage_id = $movement->requestingService->pharmacyStockages()->first()->id ?? 1;
                            $newInventory->quantity = $selection->quantity;
                            $newInventory->batch_number = $selection->inventory->batch_number ?? '';
                            $newInventory->serial_number = $selection->inventory->serial_number ?? '';
                            $newInventory->expiry_date = $selection->inventory->expiry_date;
                            $newInventory->purchase_price = $selection->inventory->purchase_price ?? 0;
                            $newInventory->save();

                            Log::info('Pharmacy stock added to requesting service for individual product', [
                                'new_inventory_id' => $newInventory->id,
                                'product_id' => $newInventory->pharmacy_product_id,
                                'stockage_id' => $newInventory->pharmacy_stockage_id,
                                'quantity' => $selection->quantity,
                                'item_id' => $item->id,
                                'movement_id' => $movement->id,
                            ]);
                        }
                    }

                } elseif ($confirmationStatus === 'damaged') {
                    // Handle damaged products - don't add to inventory, mark as damaged
                    if ($item->selected_inventory && $item->selected_inventory->count() > 0) {
                        foreach ($item->selected_inventory as $selection) {
                            Log::info('Damaged pharmacy stock reported for individual product', [
                                'movement_id' => $movement->id,
                                'item_id' => $item->id,
                                'product_id' => $selection->inventory->pharmacy_product_id ?? $item->product_id,
                                'quantity' => $selection->quantity,
                                'batch_number' => $selection->inventory->batch_number,
                                'service_id' => $movement->requesting_service_id,
                            ]);
                        }
                    }

                    // Set executed quantity to 0 for damaged items
                    $item->executed_quantity = 0;

                } elseif ($confirmationStatus === 'manque') {
                    // For 'manque' status: update received_quantity and set executed_quantity to received quantity
                    if ($receivedQuantity !== null) {
                        $item->received_quantity = $receivedQuantity;
                        $item->executed_quantity = $receivedQuantity;

                        // Add whatever was received to inventory
                        if ($receivedQuantity > 0 && $item->selected_inventory && $item->selected_inventory->count() > 0) {
                            // Proportionally distribute received quantity across selected inventory
                            $totalSelected = $item->selected_inventory->sum('quantity');
                            foreach ($item->selected_inventory as $selection) {
                                if ($totalSelected > 0) {
                                    $proportion = $selection->quantity / $totalSelected;
                                    $quantityToAdd = $receivedQuantity * $proportion;

                                    if ($quantityToAdd > 0) {
                                        $newInventory = new \App\Models\PharmacyInventory;
                                        $newInventory->pharmacy_product_id = $selection->inventory->pharmacy_product_id ?? $item->product_id;
                                        $newInventory->pharmacy_stockage_id = $movement->requestingService->pharmacyStockages()->first()->id ?? 1;
                                        $newInventory->quantity = $quantityToAdd;
                                        $newInventory->batch_number = $selection->inventory->batch_number ?? '';
                                        $newInventory->serial_number = $selection->inventory->serial_number ?? '';
                                        $newInventory->expiry_date = $selection->inventory->expiry_date;
                                        $newInventory->purchase_price = $selection->inventory->purchase_price ?? 0;
                                        $newInventory->save();
                                    }
                                }
                            }
                        }
                    } else {
                        // If no received quantity provided, set executed to 0
                        $item->executed_quantity = 0;
                    }

                    Log::info('Missing pharmacy stock reported for individual product', [
                        'movement_id' => $movement->id,
                        'item_id' => $item->id,
                        'product_id' => $item->product_id,
                        'service_id' => $movement->requesting_service_id,
                        'received_quantity' => $receivedQuantity,
                    ]);
                }

                $item->save();

                DB::commit();

                $statusMessage = $confirmationStatus === 'good'
                    ? 'Product confirmed successfully. Item has been added to your pharmacy inventory.'
                    : ($confirmationStatus === 'damaged'
                        ? 'Product confirmed as damaged. Item has been marked as damaged and not added to inventory.'
                        : 'Product confirmed as missing. Received quantity has been processed.');

                return response()->json([
                    'success' => true,
                    'message' => $statusMessage,
                    'item' => $item->fresh(),
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Error confirming individual pharmacy product: ' . $e->getMessage(), [
                'movement_id' => $movementId,
                'item_id' => $request->item_id,
                'user_id' => Auth::id(),
                'status' => $request->status,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while confirming product: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate received quantities for all items
     */
    public function validateQuantities(Request $request, $movementId)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*.item_id' => 'required|integer|exists:pharmacy_stock_movement_items,id',
            'items.*.received_quantity' => 'required|numeric|min:0',
            'items.*.sender_quantity' => 'nullable|numeric|min:0',
            'items.*.requested_quantity' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $movement = PharmacyMovement::where('id', $movementId)
                ->with(['items.product', 'items.selected_inventory.inventory'])
                ->first();

            if (!$movement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pharmacy movement not found',
                ], 404);
            }

            if ($movement->status !== 'in_transfer') {
                return response()->json([
                    'success' => false,
                    'message' => 'Movement must be in transfer status to validate quantities',
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

                $requestedQuantity = $validation['requested_quantity'] ?? $item->requested_quantity;
                $senderQuantity = $validation['sender_quantity'] ?? $item->approved_quantity ?? $requestedQuantity;
                $receivedQuantity = $validation['received_quantity'];

                // Calculate shortage based on sender quantity (what was actually sent)
                $shortageQuantity = max(0, $senderQuantity - $receivedQuantity);

                // Automatic status determination based on quantity comparison
                $automaticStatus = null;
                $executedQuantity = null;

                if ($receivedQuantity >= $senderQuantity) {
                    // Received quantity meets or exceeds sent quantity - set status to 'good'
                    $automaticStatus = 'good';
                    $executedQuantity = $item->approved_quantity ?? $requestedQuantity;
                } else {
                    // Received less than sent - set status to 'manque'
                    $automaticStatus = 'manque';
                    $executedQuantity = $receivedQuantity;
                    $hasShortages = true;
                }

                // Update the movement item with automatic status and quantities
                $item->update([
                    'received_quantity' => $receivedQuantity,
                    'confirmation_status' => $automaticStatus,
                    'executed_quantity' => $executedQuantity,
                    'updated_at' => now(),
                ]);

                $result = [
                    'item_id' => $item->id,
                    'product_name' => $item->product->name ?? 'Unknown',
                    'requested_quantity' => $requestedQuantity,
                    'sender_quantity' => $senderQuantity,
                    'received_quantity' => $receivedQuantity,
                    'shortage_quantity' => $shortageQuantity,
                    'has_shortage' => $shortageQuantity > 0,
                    'status' => $shortageQuantity > 0 ? 'manque' : 'good',
                    'automatic_status' => $automaticStatus,
                    'executed_quantity' => $executedQuantity,
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
                    'automatic_processing' => true,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error validating quantities: ' . $e->getMessage(), [
                'movement_id' => $movementId,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while validating quantities: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Finalize delivery confirmation and set final movement status
     */
    public function finalizeConfirmation(Request $request, $movementId)
    {
        try {
            $movement = PharmacyMovement::where('id', $movementId)
                ->with(['items'])
                ->first();

            if (!$movement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pharmacy movement not found',
                ], 404);
            }

            // Check if movement is in in_transfer status
            if ($movement->status !== 'in_transfer') {
                return response()->json([
                    'success' => false,
                    'message' => 'Movement must be in transfer status to finalize confirmation',
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
                // Use 'completed' for fulfilled deliveries (valid ENUM value)
                if ($goodItems->count() === $movement->items->count()) {
                    // All items received in good condition
                    $movement->status = 'completed';
                    $movement->delivery_status = 'good';
                } elseif ($goodItems->count() > 0) {
                    // Mix of good and other statuses - use completed but mark delivery as mixed
                    $movement->status = 'completed';
                    $movement->delivery_status = 'mixed';
                } elseif ($damagedItems->count() > 0) {
                    // Damaged items present
                    $movement->status = 'completed';
                    $movement->delivery_status = 'damaged';
                } else {
                    // Mostly manque/missing items
                    $movement->status = 'completed';
                    $movement->delivery_status = 'manque';
                }

                // Update movement finalization details
                $movement->delivery_confirmed_at = now();
                $movement->delivery_confirmed_by = Auth::id();
                $movement->save();

                Log::info('Pharmacy movement confirmation finalized', [
                    'movement_id' => $movement->id,
                    'final_status' => $movement->status,
                    'good_items' => $goodItems->count(),
                    'damaged_items' => $damagedItems->count(),
                    'manque_items' => $manqueItems->count(),
                    'total_items' => $movement->items->count(),
                    'finalized_by' => Auth::id(),
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Confirmation finalized successfully. Movement status updated.',
                    'movement' => $movement->fresh(['items', 'requestingService', 'providingService']),
                    'summary' => [
                        'final_status' => $movement->status,
                        'delivery_status' => $movement->delivery_status,
                        'good_items' => $goodItems->count(),
                        'damaged_items' => $damagedItems->count(),
                        'manque_items' => $manqueItems->count(),
                        'total_items' => $movement->items->count(),
                    ],
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Error finalizing pharmacy confirmation: ' . $e->getMessage(), [
                'movement_id' => $movementId,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while finalizing confirmation: ' . $e->getMessage(),
            ], 500);
        }
    }
}

