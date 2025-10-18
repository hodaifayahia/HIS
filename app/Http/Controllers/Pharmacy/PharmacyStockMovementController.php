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
        $userService = $this->getUserService();

        if (! $userService) {
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
            ->where('requesting_service_id', $userService->id)
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
        $userService = $this->getUserService();

        if (! $userService) {
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
            ->where('providing_service_id', $userService->id)
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
            'providing_service_id' => 'required|exists:services,id',
            'request_reason' => 'nullable|string|max:500',
            'expected_delivery_date' => 'nullable|date|after:today',
            'prescription_reference' => 'nullable|string|max:255',
            'patient_id' => 'nullable|exists:patients,id',
        ]);

        $user = Auth::user();
        $userService = $this->getUserService();

        if (! $userService) {
            return response()->json(['error' => 'User not assigned to any service'], 403);
        }

        // Check if user has any draft for this service
        $existingMovement = PharmacyMovement::where('requesting_service_id', $userService->id)
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
            'requesting_service_id' => $userService->id,
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
            'product_id' => 'required|exists:products,id',
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

            $data = $existingItem->load([
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

        $item = $movement->items()->create([
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
            'message' => 'Item added successfully',
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
            $criticalLowStock = DB::table('inventories')
                ->join('products', 'inventories.product_id', '=', 'products.id')
                ->join('pharmacy_stockages', 'inventories.stockage_id', '=', 'pharmacy_stockages.id')
                ->where('pharmacy_stockages.service_id', $providing_service_id)
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
                    'pharmacy_stockages.name as storage_name',
                    'pharmacy_stockages.temperature_controlled',
                    'pharmacy_stockages.security_level',
                    'products.category as category_name',
                    'products.controlled_substance_level',
                    'products.requires_prescription',
                    DB::raw('DATEDIFF(inventories.expiry_date, CURDATE()) as days_until_expiry')
                )
                ->orderBy('inventories.total_units', 'asc')
                ->get();

            $lowStock = DB::table('inventories')
                ->join('products', 'inventories.product_id', '=', 'products.id')
                ->join('pharmacy_stockages', 'inventories.stockage_id', '=', 'pharmacy_stockages.id')
                ->where('pharmacy_stockages.service_id', $providing_service_id)
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
                    'pharmacy_stockages.name as storage_name',
                    'pharmacy_stockages.temperature_controlled',
                    'pharmacy_stockages.security_level',
                    'products.category as category_name',
                    'products.controlled_substance_level',
                    'products.requires_prescription',
                    DB::raw('DATEDIFF(inventories.expiry_date, CURDATE()) as days_until_expiry')
                )
                ->orderBy('inventories.total_units', 'asc')
                ->get();

            // Get products expiring soon (within 30 days) - critical for pharmacy
            $expiringSoon = DB::table('inventories')
                ->join('products', 'inventories.product_id', '=', 'products.id')
                ->join('pharmacy_stockages', 'inventories.stockage_id', '=', 'pharmacy_stockages.id')
                ->where('pharmacy_stockages.service_id', $providing_service_id)
                ->whereNotNull('inventories.expiry_date')
                ->whereRaw('DATEDIFF(inventories.expiry_date, CURDATE()) <= 30')
                ->whereRaw('DATEDIFF(inventories.expiry_date, CURDATE()) > 0')
                ->select(
                    'products.*',
                    'inventories.quantity as current_stock',
                    'inventories.total_units',
                    'inventories.expiry_date',
                    'inventories.batch_number',
                    'pharmacy_stockages.name as storage_name',
                    'pharmacy_stockages.temperature_controlled',
                    'pharmacy_stockages.security_level',
                    'products.category as category_name',
                    'products.controlled_substance_level',
                    'products.requires_prescription',
                    DB::raw('DATEDIFF(inventories.expiry_date, CURDATE()) as days_until_expiry')
                )
                ->orderBy('inventories.expiry_date', 'asc')
                ->get();

            // Get expired products
            $expired = DB::table('inventories')
                ->join('products', 'inventories.product_id', '=', 'products.id')
                ->join('pharmacy_stockages', 'inventories.stockage_id', '=', 'pharmacy_stockages.id')
                ->where('pharmacy_stockages.service_id', $providing_service_id)
                ->whereNotNull('inventories.expiry_date')
                ->whereRaw('inventories.expiry_date < CURDATE()')
                ->whereRaw('inventories.total_units > 0')
                ->select(
                    'products.*',
                    'inventories.quantity as current_stock',
                    'inventories.total_units',
                    'inventories.expiry_date',
                    'inventories.batch_number',
                    'pharmacy_stockages.name as storage_name',
                    'pharmacy_stockages.temperature_controlled',
                    'pharmacy_stockages.security_level',
                    'products.category as category_name',
                    'products.controlled_substance_level',
                    'products.requires_prescription',
                    DB::raw('DATEDIFF(CURDATE(), inventories.expiry_date) as days_expired')
                )
                ->orderBy('inventories.expiry_date', 'desc')
                ->get();

            // Get controlled substances requiring special attention
            $controlledSubstances = DB::table('inventories')
                ->join('products', 'inventories.product_id', '=', 'products.id')
                ->join('pharmacy_stockages', 'inventories.stockage_id', '=', 'pharmacy_stockages.id')
                ->where('pharmacy_stockages.service_id', $providing_service_id)
                ->whereNotNull('products.controlled_substance_level')
                ->whereRaw('inventories.total_units > 0')
                ->select(
                    'products.*',
                    'inventories.quantity as current_stock',
                    'inventories.total_units',
                    'inventories.expiry_date',
                    'inventories.batch_number',
                    'pharmacy_stockages.name as storage_name',
                    'pharmacy_stockages.security_level',
                    'products.controlled_substance_level',
                    'products.requires_prescription'
                )
                ->orderBy('products.controlled_substance_level', 'desc')
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
            'product_id' => 'required|exists:products,id',
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
            // Get stock from service's pharmacy storages
            $storages = $providingService->pharmacyStorages;
            foreach ($storages as $storage) {
                $inventory = $storage->inventories()->where('product_id', $productId)->first();
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
            $storages = $providingService->pharmacyStorages;
            foreach ($storages as $storage) {
                $inventories = $storage->inventories()
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
                        'storage' => [
                            'id' => $storage->id,
                            'name' => $storage->name,
                            'temperature_controlled' => $storage->temperature_controlled,
                            'security_level' => $storage->security_level,
                        ],
                        'product' => $inventory->product,
                        // Pharmacy-specific flags
                        'is_controlled' => ! empty($inventory->product->controlled_substance_level),
                        'requires_cold_storage' => $inventory->product->storage_temperature_min < 8,
                        'requires_prescription' => $inventory->product->requires_prescription ?? false,
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
                ->where('pharmacy_movement_item_id', $item->id)
                ->delete();

            // Save new selections
            $totalSelectedQuantity = 0;
            foreach ($request->selected_inventory as $selection) {
                // Verify the inventory item exists and has sufficient quantity
                $inventory = DB::table('inventories')->find($selection['inventory_id']);
                if (! $inventory) {
                    throw new \Exception('Inventory item not found: '.$selection['inventory_id']);
                }

                // Use total_units if available, otherwise fall back to quantity
                $availableQuantity = $inventory->total_units ?? $inventory->quantity;
                if ($availableQuantity < $selection['quantity']) {
                    throw new \Exception('Insufficient inventory for item: '.$inventory->barcode.'. Available: '.$availableQuantity.', Requested: '.$selection['quantity']);
                }

                DB::table('pharmacy_movement_inventory_selections')->insert([
                    'pharmacy_movement_item_id' => $item->id,
                    'inventory_id' => $selection['inventory_id'],
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
            $userService = $this->getUserService();
            if (! $userService) {
                return response()->json([
                    'success' => false,
                    'message' => 'User service not found',
                ], 403);
            }

            $movement = PharmacyMovement::where('id', $movementId)
                ->where('providing_service_id', $userService->id)
                ->with(['items.product'])
                ->first();

            if (! $movement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pharmacy movement not found or access denied',
                ], 404);
            }

            // Check if movement is in approved status
            if ($movement->status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pharmacy movement must be in approved status to initialize transfer',
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

                                // Log pharmacy-specific audit trail
                                DB::table('pharmacy_movement_audit_logs')->insert([
                                    'pharmacy_stock_movement_id' => $movement->id,
                                    'action' => 'inventory_deducted',
                                    'inventory_id' => $inventory->id,
                                    'quantity_changed' => $selection->quantity,
                                    'user_id' => Auth::id(),
                                    'notes' => 'Stock deducted for transfer - Item: '.$item->product->name,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            } else {
                                throw new \Exception('Insufficient stock for item: '.$item->product->name);
                            }
                        }
                    }

                    // Update the item with the actual sent quantity
                    $item->update(['provided_quantity' => $totalSentQuantity]);
                }

                // Update movement status to 'in_transit'
                $movement->update([
                    'status' => 'in_transit',
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
}
