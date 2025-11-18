<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pharmacy\AddExternalPrescriptionItemRequest;
use App\Http\Requests\Pharmacy\StoreExternalPrescriptionRequest;
use App\Http\Requests\Pharmacy\UpdateExternalPrescriptionItemRequest;
use App\Http\Resources\Pharmacy\ExternalPrescriptionItemResource;
use App\Http\Resources\Pharmacy\ExternalPrescriptionResource;
use App\Models\ExternalPrescription;
use App\Models\ExternalPrescriptionItem;
use App\Models\PharmacyInventory;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExternalPrescriptionController extends Controller
{
    /**
     * Display a listing of external prescriptions
     */
    public function index(Request $request)
    {
        try {
            $query = ExternalPrescription::with(['creator', 'doctor.user', 'items']);

            // Apply filters
            if ($request->has('status') && $request->status) {
                $query->status($request->status);
            }

            if ($request->has('search') && $request->search) {
                $query->search($request->search);
            }

            if ($request->has('created_by') && $request->created_by) {
                $query->createdBy($request->created_by);
            }

            // Date range filter
            if ($request->has('date_from') && $request->date_from) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $perPage = $request->get('per_page', 15);
            $prescriptions = $query->latest()->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'data' => ExternalPrescriptionResource::collection($prescriptions->items()),
                    'current_page' => $prescriptions->currentPage(),
                    'last_page' => $prescriptions->lastPage(),
                    'per_page' => $prescriptions->perPage(),
                    'total' => $prescriptions->total(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching external prescriptions: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch external prescriptions',
            ], 500);
        }
    }

    /**
     * Store a newly created external prescription
     */
    public function store(StoreExternalPrescriptionRequest $request)
    {
        // try {
        DB::beginTransaction();

        $data = $request->validated();
        $data['created_by'] = Auth::id();

        // Auto-assign doctor if user is a doctor
        if (auth()->user() && auth()->user()->doctor) {
            $data['doctor_id'] = auth()->user()->doctor->id;
        }

        $prescription = ExternalPrescription::create($data);

        DB::commit();

        return response()->json([
            'status' => 'success',
            'message' => 'External prescription created successfully',
            'data' => new ExternalPrescriptionResource($prescription->load(['creator', 'doctor.user', 'items'])),
        ], 201);
        // } catch (\Exception $e) {
        //     DB::rollBack();
        //     Log::error('Error creating external prescription: '.$e->getMessage());

        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'Failed to create external prescription',
        //         'error' => $e->getMessage(),
        //     ], 500);
        // }
    }

    /**
     * Display the specified external prescription
     */
    public function show($id)
    {
        try {
            $prescription = ExternalPrescription::with([
                'creator',
                'doctor.user',
                'items.pharmacyProduct',
                'items.service',
                'items.modifier',
            ])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => new ExternalPrescriptionResource($prescription),
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching external prescription: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'External prescription not found',
            ], 404);
        }
    }

    /**
     * Add items to an external prescription
     */
    public function addItems(AddExternalPrescriptionItemRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $prescription = ExternalPrescription::findOrFail($id);

            // Check if prescription is still in draft
            if ($prescription->status !== 'draft') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot add items to a confirmed or cancelled prescription',
                ], 422);
            }

            $items = $request->validated()['items'];
            $createdItems = [];

            foreach ($items as $itemData) {
                $item = $prescription->items()->create([
                    'pharmacy_product_id' => $itemData['pharmacy_product_id'],
                    'quantity' => $itemData['quantity'],
                    'quantity_by_box' => $itemData['quantity_by_box'] ?? false,
                    'unit' => $itemData['unit'] ?? 'unit',
                    'status' => 'draft',
                ]);

                $createdItems[] = $item;
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => count($createdItems).' item(s) added successfully',
                'data' => ExternalPrescriptionItemResource::collection($createdItems),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error adding items to external prescription: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add items',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update item quantity
     */
    public function updateItem(UpdateExternalPrescriptionItemRequest $request, $id, $itemId)
    {
        try {
            DB::beginTransaction();

            $item = ExternalPrescriptionItem::where('external_prescription_id', $id)
                ->findOrFail($itemId);

            if ($item->status !== 'draft') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot update a dispensed or cancelled item',
                ], 422);
            }

            $item->update($request->validated());

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Item updated successfully',
                'data' => new ExternalPrescriptionItemResource($item->load(['pharmacyProduct', 'service'])),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating external prescription item: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Dispense an item and add to pharmacy inventory
     */
    public function dispenseItem(Request $request, $id, $itemId)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'quantity_sended' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $item = ExternalPrescriptionItem::where('external_prescription_id', $id)
                ->findOrFail($itemId);

            if ($item->status !== 'draft') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Item is already dispensed or cancelled',
                ], 422);
            }

            $quantitySended = $request->quantity_sended ?? $item->quantity;

            // Update item status
            $item->update([
                'status' => 'dispensed',
                'quantity_sended' => $quantitySended,
                'service_id' => $request->service_id,
                'modified_by' => auth()->id(),
            ]);

            // Add to pharmacy inventory
            $this->addToPharmacyInventory($item, $request->service_id, $quantitySended);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Item dispensed and added to inventory successfully',
                'data' => new ExternalPrescriptionItemResource($item->load(['pharmacyProduct', 'service'])),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error dispensing external prescription item: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to dispense item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel an item
     */
    public function cancelItem(Request $request, $id, $itemId)
    {
        $request->validate([
            'cancel_reason' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            $item = ExternalPrescriptionItem::where('external_prescription_id', $id)
                ->findOrFail($itemId);

            if ($item->status !== 'draft') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Item is already dispensed or cancelled',
                ], 422);
            }

            $item->update([
                'status' => 'cancelled',
                'cancel_reason' => $request->cancel_reason,
                'quantity_sended' => 0,
                'modified_by' => auth()->id(),
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Item cancelled successfully',
                'data' => new ExternalPrescriptionItemResource($item->load(['pharmacyProduct'])),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error cancelling external prescription item: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to cancel item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete an item (only if draft)
     */
    public function deleteItem($id, $itemId)
    {
        try {
            DB::beginTransaction();

            $item = ExternalPrescriptionItem::where('external_prescription_id', $id)
                ->findOrFail($itemId);

            if ($item->status !== 'draft') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete a dispensed or cancelled item',
                ], 422);
            }

            $item->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Item deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting external prescription item: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate and download PDF
     */
    public function generatePDF($id)
    {
        try {
            $prescription = ExternalPrescription::with([
                'creator',
                'doctor.user',
                'items.pharmacyProduct',
            ])->findOrFail($id);

            $pdf = PDF::loadView('prescriptions.external_prescription_pdf', [
                'prescription' => $prescription,
            ]);

            return $pdf->download("external-prescription-{$prescription->prescription_code}.pdf");
        } catch (\Exception $e) {
            Log::error('Error generating PDF: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate PDF',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete an external prescription
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $prescription = ExternalPrescription::findOrFail($id);

            // Only allow deletion of draft prescriptions
            if ($prescription->status !== 'draft') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete a confirmed or cancelled prescription',
                ], 422);
            }

            $prescription->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'External prescription deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting external prescription: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete prescription',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add item to pharmacy inventory
     */
    private function addToPharmacyInventory(ExternalPrescriptionItem $item, $serviceId, $quantity)
    {
        // Find the pharmacy stockage for the service
        $stockage = \DB::table('pharmacy_stockages')
            ->where('service_id', $serviceId)
            ->first();

        if (! $stockage) {
            throw new \Exception('No pharmacy stockage found for the selected service');
        }

        // Check if inventory already exists
        $inventory = PharmacyInventory::where('pharmacy_product_id', $item->pharmacy_product_id)
            ->where('pharmacy_stockage_id', $stockage->id)
            ->first();

        if ($inventory) {
            // Update existing inventory
            $inventory->increment('quantity', $quantity);
        } else {
            // Create new inventory record
            PharmacyInventory::create([
                'pharmacy_product_id' => $item->pharmacy_product_id,
                'pharmacy_stockage_id' => $stockage->id,
                'quantity' => $quantity,
                'unit' => $item->unit,
            ]);
        }
    }
}
