<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\ServiceGroupProductPricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ServiceGroupProductPricingController extends Controller
{
    protected ServiceGroupProductPricingService $pricingService;

    public function __construct(ServiceGroupProductPricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    /**
     * Get pricing for a specific product in a service group.
     */
    public function show(Request $request, $serviceGroupId, $productId)
    {
        try {
            $isPharmacy = $request->boolean('is_pharmacy', false);

            $pricing = \App\Models\Inventory\ServiceGroupProductPricing::getPricing(
                $serviceGroupId,
                $productId,
                $isPharmacy
            );

            if (! $pricing) {
                return response()->json([
                    'success' => false,
                    'message' => 'No pricing found for this product in this service group',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $pricing->id,
                    'service_group_id' => $pricing->service_group_id,
                    'service_group_name' => $pricing->serviceGroup->name,
                    'product_id' => $pricing->product_id,
                    'pharmacy_product_id' => $pricing->pharmacy_product_id,
                    'is_pharmacy' => $pricing->is_pharmacy,
                    'selling_price' => $pricing->selling_price,
                    'purchase_price' => $pricing->purchase_price,
                    'vat_rate' => $pricing->vat_rate,
                    'selling_price_with_vat' => $pricing->selling_price_with_vat,
                    'profit_margin' => $pricing->profit_margin,
                    'effective_from' => $pricing->effective_from->format('Y-m-d H:i:s'),
                    'effective_to' => $pricing->effective_to?->format('Y-m-d H:i:s'),
                    'is_active' => $pricing->is_active,
                    'notes' => $pricing->notes,
                    'updated_by_name' => $pricing->updatedBy?->name,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get pricing: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create or update pricing for a product.
     */
    public function store(Request $request, $serviceGroupId, $productId)
    {
        $validator = Validator::make($request->all(), [
            'is_pharmacy' => 'required|boolean',
            'selling_price' => 'required|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $isPharmacy = $request->boolean('is_pharmacy');

            $pricing = $this->pricingService->updatePrice(
                $serviceGroupId,
                $productId,
                $isPharmacy,
                [
                    'selling_price' => $request->selling_price,
                    'purchase_price' => $request->purchase_price,
                    'vat_rate' => $request->vat_rate ?? 0.00,
                    'notes' => $request->notes,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Pricing updated successfully',
                'data' => [
                    'id' => $pricing->id,
                    'selling_price' => $pricing->selling_price,
                    'selling_price_with_vat' => $pricing->selling_price_with_vat,
                    'profit_margin' => $pricing->profit_margin,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update pricing: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get price history for a product.
     */
    public function history(Request $request, $serviceGroupId, $productId)
    {
        try {
            $isPharmacy = $request->boolean('is_pharmacy', false);
            $limit = $request->integer('limit', 10);

            $history = $this->pricingService->getPriceHistory(
                $serviceGroupId,
                $productId,
                $isPharmacy,
                $limit
            );

            return response()->json([
                'success' => true,
                'data' => $history,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get price history: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk update prices for multiple products.
     */
    public function bulkUpdate(Request $request, $serviceGroupId)
    {
        $validator = Validator::make($request->all(), [
            'is_pharmacy' => 'required|boolean',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|integer',
            'products.*.selling_price' => 'required_without:percentage_increase|numeric|min:0',
            'percentage_increase' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->pricingService->bulkUpdatePrices(
                $serviceGroupId,
                $request->boolean('is_pharmacy'),
                $request->products,
                $request->percentage_increase
            );

            return response()->json([
                'success' => true,
                'message' => 'Bulk update completed',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk update prices: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete pricing record.
     */
    public function destroy(int $pricingId): JsonResponse
    {
        try {
            $this->pricingService->deletePricing($pricingId);

            return response()->json([
                'success' => true,
                'message' => 'Pricing deleted successfully',
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to delete pricing:', [
                'pricing_id' => $pricingId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete pricing: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export pricing data to CSV
     */
    public function export(int $serviceGroupId, Request $request)
    {
        try {
            $isPharmacy = $request->boolean('is_pharmacy', false);
            $csv = $this->pricingService->exportPricingToCsv($serviceGroupId, $isPharmacy);

            $filename = sprintf(
                'pricing_export_%d_%s_%s.csv',
                $serviceGroupId,
                $isPharmacy ? 'pharmacy' : 'stock',
                now()->format('Y-m-d_His')
            );

            return response($csv, 200)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
        } catch (\Exception $e) {
            \Log::error('Failed to export pricing:', [
                'service_group_id' => $serviceGroupId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to export pricing: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Import pricing data from CSV
     */
    public function import(int $serviceGroupId, Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
            'is_pharmacy' => 'boolean',
        ]);

        try {
            $isPharmacy = $request->boolean('is_pharmacy', false);
            $file = $request->file('file');

            $result = $this->pricingService->importPricingFromCsv(
                $serviceGroupId,
                $isPharmacy,
                $file->getRealPath()
            );

            return response()->json([
                'success' => true,
                'message' => 'Pricing imported successfully',
                'imported_count' => $result['imported'],
                'failed_count' => $result['failed'],
                'errors' => $result['errors'],
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to import pricing:', [
                'service_group_id' => $serviceGroupId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to import pricing: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate pricing report PDF
     */
    public function report(int $serviceGroupId, Request $request)
    {
        try {
            $isPharmacy = $request->boolean('is_pharmacy', false);
            $pdf = $this->pricingService->generatePricingReport($serviceGroupId, $isPharmacy);

            $filename = sprintf(
                'pricing_report_%d_%s_%s.pdf',
                $serviceGroupId,
                $isPharmacy ? 'pharmacy' : 'stock',
                now()->format('Y-m-d')
            );

            return response($pdf, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
        } catch (\Exception $e) {
            \Log::error('Failed to generate pricing report:', [
                'service_group_id' => $serviceGroupId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all pricing for a service group.
     */
    public function indexByServiceGroup(Request $request, $serviceGroupId)
    {
        try {
            $isPharmacy = $request->boolean('is_pharmacy', false);

            $query = \App\Models\Inventory\ServiceGroupProductPricing::with(['product', 'pharmacyProduct', 'updatedBy'])
                ->forServiceGroup($serviceGroupId)
                ->active()
                ->current();

            if ($isPharmacy) {
                $query->pharmacy();
            } else {
                $query->stock();
            }

            $pricing = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $pricing->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'product_id' => $p->product_id,
                        'pharmacy_product_id' => $p->pharmacy_product_id,
                        'product_name' => $p->product?->product_name ?? $p->pharmacyProduct?->product_name,
                        'selling_price' => $p->selling_price,
                        'purchase_price' => $p->purchase_price,
                        'vat_rate' => $p->vat_rate,
                        'selling_price_with_vat' => $p->selling_price_with_vat,
                        'profit_margin' => $p->profit_margin,
                        'effective_from' => $p->effective_from->format('Y-m-d'),
                        'updated_by_name' => $p->updatedBy?->name,
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get pricing list: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get pricing for a product across all service groups.
     */
    public function getProductPricingByGroups(Request $request, $productId)
    {
        try {
            $isPharmacy = $request->boolean('is_pharmacy', false);

            $query = \App\Models\Inventory\ServiceGroupProductPricing::with(['serviceGroup'])
                ->active()
                ->current();

            if ($isPharmacy) {
                $query->where('pharmacy_product_id', $productId)->where('is_pharmacy', true);
            } else {
                $query->where('product_id', $productId)->where('is_pharmacy', false);
            }

            $pricing = $query->orderBy('service_group_id')->get();

            return response()->json([
                'status' => 'success',
                'data' => $pricing->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'service_group_id' => $p->service_group_id,
                        'service_group_name' => $p->serviceGroup->name,
                        'price' => $p->selling_price,
                        'selling_price' => $p->selling_price,
                        'purchase_price' => $p->purchase_price,
                        'vat_rate' => $p->vat_rate,
                        'profit_margin' => $p->profit_margin,
                        'effective_from' => $p->effective_from->format('Y-m-d'),
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get pricing by groups: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Batch update pricing for multiple products/service groups.
     */
    public function batchUpdatePricing(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pricing_data' => 'required|array',
            'pricing_data.*.service_group_id' => 'required|integer|exists:service_groups,id',
            'pricing_data.*.product_id' => 'nullable|integer',
            'pricing_data.*.pharmacy_product_id' => 'nullable|integer',
            'pricing_data.*.price' => 'required|numeric|min:0',
            'pricing_data.*.is_pharmacy' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $results = [];
            $successCount = 0;
            $errorCount = 0;

            foreach ($request->pricing_data as $data) {
                try {
                    $productId = $data['is_pharmacy'] ? $data['pharmacy_product_id'] : $data['product_id'];

                    $pricing = $this->pricingService->updatePrice(
                        $data['service_group_id'],
                        $productId,
                        $data['is_pharmacy'],
                        [
                            'selling_price' => $data['price'],
                            'purchase_price' => $data['purchase_price'] ?? null,
                            'vat_rate' => $data['vat_rate'] ?? 0.00,
                            'notes' => 'Batch updated from Bon Entrée validation',
                        ]
                    );

                    $results[] = [
                        'success' => true,
                        'service_group_id' => $data['service_group_id'],
                        'product_id' => $productId,
                    ];
                    $successCount++;
                } catch (\Exception $e) {
                    $results[] = [
                        'success' => false,
                        'service_group_id' => $data['service_group_id'],
                        'product_id' => $productId ?? null,
                        'error' => $e->getMessage(),
                    ];
                    $errorCount++;
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => "Batch update complete: {$successCount} success, {$errorCount} errors",
                'data' => [
                    'success_count' => $successCount,
                    'error_count' => $errorCount,
                    'results' => $results,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Batch update failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update single pricing entry.
     */
    public function updateSinglePricing(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_group_id' => 'required|integer|exists:service_groups,id',
            'product_id' => 'nullable|integer',
            'pharmacy_product_id' => 'nullable|integer',
            'price' => 'required|numeric|min:0',
            'is_pharmacy' => 'required|boolean',
            'purchase_price' => 'nullable|numeric|min:0',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $productId = $request->is_pharmacy ? $request->pharmacy_product_id : $request->product_id;

            $pricing = $this->pricingService->updatePrice(
                $request->service_group_id,
                $productId,
                $request->is_pharmacy,
                [
                    'selling_price' => $request->price,
                    'purchase_price' => $request->purchase_price,
                    'vat_rate' => $request->vat_rate ?? 0.00,
                    'notes' => $request->notes ?? 'Updated via service group pricing',
                ]
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Pricing updated successfully',
                'data' => [
                    'id' => $pricing->id,
                    'service_group_id' => $pricing->service_group_id,
                    'price' => $pricing->selling_price,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update pricing: '.$e->getMessage(),
            ], 500);
        }
    }
}
