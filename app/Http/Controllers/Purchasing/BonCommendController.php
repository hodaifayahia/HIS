<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Purchsing\StoreBonCommendRequest;
use App\Http\Requests\Purchsing\UpdateBonCommendRequest;
use App\Models\BonCommend;
use App\Models\PharmacyInventory;
use App\Models\PharmacyProduct;
use App\Services\Purchsing\BonCommendService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BonCommendController extends Controller
{
    protected $bonCommendService;

    public function __construct(BonCommendService $bonCommendService)
    {
        $this->bonCommendService = $bonCommendService;
    }

    /**
     * Display a listing of bon commends
     */
    public function index(Request $request)
    {
        // try {
            $filters = $request->only(['status', 'fournisseur_id', 'search', 'date_from', 'date_to']);
            $bonCommends = $this->bonCommendService->getAllBonCommends($filters);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'data' => $bonCommends->items(),
                    'current_page' => $bonCommends->currentPage(),
                    'last_page' => $bonCommends->lastPage(),
                    'per_page' => $bonCommends->perPage(),
                    'total' => $bonCommends->total(),
                ],
            ]);
        // } catch (\Exception $e) {
        //     Log::error('Error fetching bon commends: '.$e->getMessage());

        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'Failed to fetch bon commends',
        //     ], 500);
        // }
    }

    public function confirmWorkflow(Request $request, $id)
    {
        try {
            $bonCommend = BonCommend::with(['approvals', 'items.product.inventories'])->findOrFail($id);

            // Check if the bon commend requires approval
            $requiresApproval = $bonCommend->requiresApprovalCheck();

            if ($requiresApproval) {
                // Check if there's an approved approval
                $hasApprovedApproval = $bonCommend->approvals()
                    ->where('status', 'approved')
                    ->exists();

                if (! $hasApprovedApproval) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot confirm: This bon commend requires approval first. Please submit for approval or wait for approval.',
                        'requires_approval' => true,
                        'approval_status' => $bonCommend->approval_status,
                    ], 422);
                }
            }

            // Update the bon commend status to confirmed
            $bonCommend->update([
                'status' => 'confirmed',
                'is_confirmed' => true,
                'confirmed_at' => now(),
                'confirmed_by' => auth()->id(),
            ]);

            // Also update all items to confirmed status if they exist
            if ($bonCommend->items) {
                $bonCommend->items()->update([
                    'status' => 'delivered',
                    'confirmation_status' => 'confirmed',
                    'confirmed_at' => now(),
                ]);
            }

            // Load fresh data with relationships
            $bonCommend->load(['fournisseur', 'serviceDemand.service', 'creator', 'items.product.inventories', 'items.factureProforma']);

            return response()->json([
                'success' => true,
                'message' => 'Bon commend confirmed successfully',
                'data' => $bonCommend,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bon commend not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error confirming bon commend: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm bon commend',
            ], 500);
        }
    }

    /**
     * Store a newly created bon commend
     */
    public function store(StoreBonCommendRequest $request)
    {
        try {
            $validatedData = $request->validated();
            $bonCommend = $this->bonCommendService->createWithAttachments($validatedData);

            // Load items with products to check approval requirements
            $bonCommend->load(['items.product.inventories']);

            // Skip approval workflow if bon commend is already confirmed/approved (e.g., created from reception)
            $skipApproval = ($bonCommend->status === 'confirmed' && $bonCommend->approval_status === 'approved');
            // Check if approval is required and auto-create approval request
            if ($skipApproval && $bonCommend->requiresApprovalCheck()) {
                $approver = $bonCommend->findApprover();

                if ($approver) {
                    // Create approval request automatically
                    \App\Models\BonCommendApproval::create([
                        'bon_commend_id' => $bonCommend->id,
                        'approval_person_id' => $approver->id,
                        'requested_by' => auth()->id(),
                        'amount' => $bonCommend->calculateTotalAmount(),
                        'status' => 'pending',
                        'requested_at' => now(),
                    ]);

                    // Update bon commend status to pending approval
                    $bonCommend->update([
                        'approval_status' => 'pending_approval',
                    ]);

                    // Reload to get the approval relationship
                    $bonCommend->load(['approvals.approvalPerson.user']);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Bon commend created successfully'.
                    ($skipApproval ? ' (pre-approved)' : ($bonCommend->requiresApprovalCheck() ? ' and submitted for approval' : '')),
                'data' => $bonCommend,
                'requires_approval' => ! $skipApproval && $bonCommend->requiresApprovalCheck(),
                'approval_created' => $bonCommend->approvals()->exists(),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating bon commend: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create bon commend: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download PDF using DomPDF (no external dependencies required)
     */
    public function downloadPdf(Request $request, $id)
    {
        try {
            // Validate template parameter
            $template = $request->query('template', 'default');

            // Sanitize: only allow 'default' or 'pch'
            if (! in_array($template, ['default', 'pch'])) {
                $template = 'default';
            }

            // Fetch the bon commend with relationships
            $bon = BonCommend::with([
                'fournisseur',
                'serviceDemand.service',
                'creator',
                'items.product',
                'items.factureProforma',
            ])->findOrFail($id);

            // Build pharmacy inventory map for items (keyed by product code)
            $pharmacyStockMap = [];
            $pharmacyInventoryDetailsMap = [];
            $codes = [];

            foreach ($bon->items as $item) {
                if (isset($item->product)) {
                    $codes[] = $item->product->code_pch ?? null;
                    $codes[] = $item->product->pch_code ?? null;
                    $codes[] = $item->product->product_code ?? null;
                }
            }

            $codes = array_filter(array_unique($codes));

            if (! empty($codes)) {
                try {
                    $pp = PharmacyProduct::whereIn('barcode', $codes)
                        ->orWhereIn('sku', $codes)
                        ->get();

                    $ppIds = $pp->pluck('id')->all();

                    if (! empty($ppIds)) {
                        $totals = PharmacyInventory::whereIn('pharmacy_product_id', $ppIds)
                            ->select('pharmacy_product_id', \DB::raw('SUM(quantity) as total'))
                            ->groupBy('pharmacy_product_id')
                            ->pluck('total', 'pharmacy_product_id')
                            ->toArray();

                        // Additionally fetch individual pharmacy inventory rows grouped by pharmacy_product_id
                        $inventories = PharmacyInventory::whereIn('pharmacy_product_id', $ppIds)
                            ->get()
                            ->groupBy('pharmacy_product_id')
                            ->map(function ($group) {
                                return $group->map(function ($inv) {
                                    return [
                                        'id' => $inv->id,
                                        'quantity' => $inv->quantity,
                                        'batch_number' => $inv->batch_number,
                                        'expiry_date' => $inv->expiry_date ? $inv->expiry_date->format('d/m/Y') : null,
                                        'location' => $inv->location ?? null,
                                    ];
                                })->toArray();
                            })->toArray();

                        foreach ($pp as $p) {
                            $key = $p->barcode ?: $p->sku ?: $p->id;
                            $pharmacyStockMap[$key] = $totals[$p->id] ?? 0;
                            $pharmacyInventoryDetailsMap[$key] = $inventories[$p->id] ?? [];
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('Error fetching pharmacy inventory for PDF: '.$e->getMessage());
                    // Continue without pharmacy data
                }
            }

            // Check if template view exists
            $templatePath = "bonCommend.templates.{$template}";
            if (! view()->exists($templatePath)) {
                $templatePath = 'bonCommend.templates.default';
            }

            // Generate PDF using DomPDF
            $pdf = Pdf::loadView($templatePath, [
                'order' => $bon,
                'pharmacyStockMap' => $pharmacyStockMap,
                'pharmacyInventoryDetailsMap' => $pharmacyInventoryDetailsMap,
            ]);

            // Configure PDF settings
            $pdf->setPaper('a4', 'portrait');

            // Set options for better rendering
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
                'isFontSubsettingEnabled' => true,
                'dpi' => 150,
                'enable_php' => false,
            ]);

            // Generate filename
            $filename = "bon-commande-{$bon->bonCommendCode}-{$template}.pdf";

            // Set response headers
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="'.$filename.'"');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');

            // Return PDF as download with proper headers
            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
            ]);

        } catch (\Exception $e) {
            Log::error('Error downloading bon commend PDF: '.$e->getMessage(), [
                'bon_commend_id' => $id,
                'exception' => $e,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to download PDF: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save PDF to storage using DomPDF
     */
    public function generateAndSavePdf(Request $request, $id)
    {
        try {
            // Validate template parameter
            $template = $request->query('template', 'default');

            // Sanitize: only allow 'default' or 'pch'
            if (! in_array($template, ['default', 'pch'])) {
                $template = 'default';
            }

            // Fetch the bon commend with relationships
            $bon = BonCommend::with([
                'fournisseur',
                'serviceDemand.service',
                'creator',
                'items.product',
                'items.factureProforma',
            ])->findOrFail($id);

            // Build pharmacy inventory map for items (keyed by product code)
            $pharmacyStockMap = [];
            $pharmacyInventoryDetailsMap = [];
            $codes = [];

            foreach ($bon->items as $item) {
                if (isset($item->product)) {
                    $codes[] = $item->product->code_pch ?? null;
                    $codes[] = $item->product->pch_code ?? null;
                    $codes[] = $item->product->product_code ?? null;
                }
            }

            $codes = array_filter(array_unique($codes));

            if (! empty($codes)) {
                $pp = PharmacyProduct::whereIn('barcode', $codes)
                    ->orWhereIn('sku', $codes)
                    ->get();

                $ppIds = $pp->pluck('id')->all();

                if (! empty($ppIds)) {
                    $totals = PharmacyInventory::whereIn('pharmacy_product_id', $ppIds)
                        ->select('pharmacy_product_id', \DB::raw('SUM(quantity) as total'))
                        ->groupBy('pharmacy_product_id')
                        ->pluck('total', 'pharmacy_product_id')
                        ->toArray();

                    // Additionally fetch individual pharmacy inventory rows grouped by pharmacy_product_id
                    $inventories = PharmacyInventory::whereIn('pharmacy_product_id', $ppIds)
                        ->get()
                        ->groupBy('pharmacy_product_id')
                        ->map(function ($group) {
                            return $group->map(function ($inv) {
                                return [
                                    'id' => $inv->id,
                                    'quantity' => $inv->quantity,
                                    'batch_number' => $inv->batch_number,
                                    'expiry_date' => $inv->expiry_date ? $inv->expiry_date->format('d/m/Y') : null,
                                    'location' => $inv->location ?? null,
                                ];
                            })->toArray();
                        })->toArray();

                    foreach ($pp as $p) {
                        $key = $p->barcode ?: $p->sku ?: $p->id;
                        $pharmacyStockMap[$key] = $totals[$p->id] ?? 0;
                        $pharmacyInventoryDetailsMap[$key] = $inventories[$p->id] ?? [];
                    }
                }
            }

            // Generate PDF using DomPDF
            $pdf = Pdf::loadView("bonCommend.templates.{$template}", [
                'order' => $bon,
                'pharmacyStockMap' => $pharmacyStockMap,
                'pharmacyInventoryDetailsMap' => $pharmacyInventoryDetailsMap,
            ]);

            // Configure PDF settings
            $pdf->setPaper('a4', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
                'isFontSubsettingEnabled' => true,
            ]);

            // Generate the PDF content
            $pdfContent = $pdf->output();

            // Save to storage
            $fileName = "bon-commande-{$bon->bonCommendCode}-{$template}.pdf";
            $pdfPath = "bon-commends/{$id}/{$fileName}";

            // Ensure directory exists
            Storage::disk('public')->makeDirectory("bon-commends/{$id}");

            // Save the generated PDF content to storage
            Storage::disk('public')->put($pdfPath, $pdfContent);

            return response()->json([
                'status' => 'success',
                'path' => Storage::url($pdfPath),
                'message' => 'PDF saved successfully',
                'filename' => $fileName,
                'size' => Storage::disk('public')->size($pdfPath),
            ]);

        } catch (\Exception $e) {
            Log::error('PDF save error: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save PDF: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Stream PDF directly to browser (for preview)
     */
    public function previewPdf(Request $request, $id)
    {
        try {
            $template = $request->query('template', 'default');

            if (! in_array($template, ['default', 'pch'])) {
                $template = 'default';
            }

            $bon = BonCommend::with([
                'fournisseur',
                'serviceDemand.service',
                'creator',
                'items.product',
                'items.factureProforma',
            ])->findOrFail($id);

            // Build pharmacy inventory map for items (keyed by product code)
            $pharmacyStockMap = [];
            $codes = [];
            foreach ($bon->items as $item) {
                if (isset($item->product)) {
                    $codes[] = $item->product->code_pch ?? null;
                    $codes[] = $item->product->pch_code ?? null;
                    $codes[] = $item->product->product_code ?? null;
                }
            }
            $codes = array_filter(array_unique($codes));
            if (! empty($codes)) {
                $pp = PharmacyProduct::whereIn('barcode', $codes)
                    ->orWhereIn('sku', $codes)
                    ->get();

                $ppIds = $pp->pluck('id')->all();
                if (! empty($ppIds)) {
                    $totals = PharmacyInventory::whereIn('pharmacy_product_id', $ppIds)
                        ->select('pharmacy_product_id', \DB::raw('SUM(quantity) as total'))
                        ->groupBy('pharmacy_product_id')
                        ->pluck('total', 'pharmacy_product_id')
                        ->toArray();

                    foreach ($pp as $p) {
                        $key = $p->barcode ?: $p->sku ?: $p->id;
                        $pharmacyStockMap[$key] = $totals[$p->id] ?? 0;
                    }
                }
            }

            // Build pharmacy inventory details map
            $pharmacyInventoryDetailsMap = [];

            // Generate PDF using DomPDF
            $pdf = Pdf::loadView("bonCommend.templates.{$template}", [
                'order' => $bon,
                'pharmacyStockMap' => $pharmacyStockMap,
                'pharmacyInventoryDetailsMap' => $pharmacyInventoryDetailsMap,
            ]);

            // Configure PDF settings
            $pdf->setPaper('a4', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);

            // Return as inline (preview in browser)
            return $pdf->stream('preview.pdf');

        } catch (\Exception $e) {
            Log::error('PDF preview error: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to preview PDF',
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $bonCommend = BonCommend::findOrFail($id);

            // Only allow deletion if status is draft
            if ($bonCommend->status !== 'draft') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete bon commend with status: '.$bonCommend->status,
                ], 422);
            }

            DB::beginTransaction();

            $bonCommend->items()->delete();
            $bonCommend->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Bon commend deleted successfully',
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bon commend not found',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting bon commend: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete bon commend',
            ], 500);
        }
    }

    /**
     * Display the specified bon commend
     */
    public function show($id)
    {
        try {
            $bonCommend = BonCommend::with([
                'fournisseur',
                'serviceDemand.service',
                'serviceDemand.items.product.inventories',
                'creator',
                'items.product.inventories',
                'approvals.approvalPerson.user',
            ])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $bonCommend,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bon commend not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching bon commend: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch bon commend',
            ], 500);
        }
    }

    /**
     * Update the specified bon commend
     */
    public function update(UpdateBonCommendRequest $request, $id)
    {
        try {
            $validatedData = $request->validated();
            $bonCommend = $this->bonCommendService->updateWithAttachments($id, $validatedData);

            // Load items with products to check approval requirements
            $bonCommend->load(['items.product', 'approvals']);

            // Check if approval is required but no pending approval exists
            if ($bonCommend->requiresApprovalCheck() && ! $bonCommend->hasPendingApproval() && $bonCommend->status === 'draft') {
                $approver = $bonCommend->findApprover();

                if ($approver) {
                    // Create approval request automatically
                    \App\Models\BonCommendApproval::create([
                        'bon_commend_id' => $bonCommend->id,
                        'approval_person_id' => $approver->id,
                        'requested_by' => auth()->id(),
                        'amount' => $bonCommend->calculateTotalAmount(),
                        'status' => 'pending',
                        'requested_at' => now(),
                    ]);

                    // Update bon commend status to pending approval
                    $bonCommend->update([
                        'approval_status' => 'pending_approval',
                    ]);

                    // Reload to get the approval relationship
                    $bonCommend->load(['approvals.approvalPerson.user']);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Bon commend updated successfully'.
                    ($bonCommend->hasPendingApproval() ? ' (pending approval)' : ''),
                'data' => $bonCommend,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bon commend not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error updating bon commend: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update bon commend: '.$e->getMessage(),
            ], 500);
        }
    }

    public function download($id)
    {
        try {
            $bonCommend = BonCommend::with([
                'fournisseur',
                'serviceDemand.service',
                'creator',
                'items.product',
                'items.factureProforma',
            ])->findOrFail($id);

            // Generate PDF content
            $html = $this->generatePdfContent($bonCommend);

            // Use dompdf to generate the PDF
            $pdf = \PDF::loadHTML($html);

            $filename = 'bon-commend-'.($bonCommend->bonCommendCode ?: 'BC-'.$bonCommend->id).'.pdf';

            return $pdf->download($filename);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bon commend not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error downloading bon commend: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to download bon commend',
            ], 500);
        }
    }

    private function generatePdfContent(BonCommend $bonCommend)
    {
        $supplierName = $bonCommend->fournisseur ? $bonCommend->fournisseur->company_name : 'Not assigned';
        $serviceName = $bonCommend->serviceDemand?->service?->name ?? 'N/A';
        $createdDate = $bonCommend->created_at->format('d/m/Y');

        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Purchase Order - {$bonCommend->bonCommendCode}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .company-info { float: left; width: 50%; }
                .supplier-info { float: right; width: 45%; text-align: right; }
                .clear { clear: both; }
                .info-section { margin: 20px 0; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                .total-row { font-weight: bold; background-color: #f9f9f9; }
                .footer { margin-top: 30px; text-align: center; color: #666; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>PURCHASE ORDER (BON DE COMMANDE)</h1>
                <h2>{$bonCommend->bonCommendCode}</h2>
            </div>
            
            <div class='company-info'>
                <h3>From:</h3>
                <p><strong>Hospital Information System</strong></p>
                <p>Service: {$serviceName}</p>
                <p>Date: {$createdDate}</p>
                <p>Status: ".ucfirst($bonCommend->status)."</p>
            </div>
            
            <div class='supplier-info'>
                <h3>To:</h3>
                <p><strong>{$supplierName}</strong></p>";

        if ($bonCommend->fournisseur) {
            if ($bonCommend->fournisseur->contact_person) {
                $html .= "<p>Contact: {$bonCommend->fournisseur->contact_person}</p>";
            }
            if ($bonCommend->fournisseur->phone) {
                $html .= "<p>Phone: {$bonCommend->fournisseur->phone}</p>";
            }
            if ($bonCommend->fournisseur->email) {
                $html .= "<p>Email: {$bonCommend->fournisseur->email}</p>";
            }
        }

        $html .= "
            </div>
            
            <div class='clear'></div>
            
            <div class='info-section'>
                <h3>Order Details</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Quantity </th>
                            <th>Unit</th>
                        </tr>
                    </thead>
                    <tbody>";

        foreach ($bonCommend->items as $item) {
            $productName = $item->product ? $item->product->name : 'Unknown Product';

            $html .= "
                        <tr>
                            <td>{$productName}</td>
                            <td>{$item->quantity_desired}</td>
                            <td>{$item->unit}</td>
                        </tr>";
        }

        $html .= "
                    </tbody>
                </table>
            </div>
            
            <div class='info-section'>
                <h3>Instructions</h3>
                <p>This is a purchase order request. Please review the items and provide pricing and availability.</p>
                <p>Delivery should be coordinated with the requesting service.</p>
                <p>Please confirm receipt and provide estimated delivery dates.</p>
            </div>
            
            <div class='footer'>
                <p>Generated on ".now()->format('d/m/Y H:i').'</p>
                <p>This is an automatically generated document.</p>
            </div>
        </body>
        </html>';

        return $html;
    }

    /**
     * Download attachment by index
     */
    public function downloadAttachment($id, $attachmentIndex)
    {
        try {
            $bonCommend = BonCommend::findOrFail($id);

            if (! isset($bonCommend->attachments[$attachmentIndex])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Attachment not found',
                ], 404);
            }

            $attachment = $bonCommend->attachments[$attachmentIndex];

            if (! isset($attachment['path'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Attachment path not found',
                ], 404);
            }

            $filePath = storage_path('app/public/'.$attachment['path']);

            if (! file_exists($filePath)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'File not found on server',
                ], 404);
            }

            $fileName = $attachment['name'] ?? $attachment['original_filename'] ?? basename($attachment['path']);

            return response()->download($filePath, $fileName);

        } catch (\Exception $e) {
            Log::error('Error downloading bon commend attachment: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to download attachment',
            ], 500);
        }
    }

    /**
     * Create a bon commend from a facture proforma
     */
    public function createFromFactureProforma(Request $request)
    {
        // try {
        $request->validate([
            'factureproforma_id' => 'required|exists:factureproformas,id',
            'order_date' => 'nullable|date',
            'expected_delivery_date' => 'nullable|date',
            'priority' => 'nullable|string|in:low,normal,high,urgent',
            'notes' => 'nullable|string',
            'price' => 'nullable|numeric|min:0', // Optional price override
        ]);

        $factureProforma = \App\Models\FactureProforma::with(['fournisseur', 'products', 'serviceDemand'])->findOrFail($request->factureproforma_id);

        // Check if a bon commend already exists for this facture proforma
        $existingBonCommend = \App\Models\BonCommendItem::where('factureproforma_id', $request->factureproforma_id)->exists();

        if ($existingBonCommend) {
            return response()->json([
                'success' => false,
                'message' => 'A bon commend has already been created from this facture proforma. You cannot create multiple bon commends from the same facture proforma.',
            ], 422);
        }

        // Create bon commend with auto-filled price from facture proforma
        $bonCommendData = [
            'fournisseur_id' => $factureProforma->fournisseur_id,
            'service_demand_purchasing_id' => $factureProforma->service_demand_purchasing_id,
            'order_date' => $request->order_date ?? now()->toDateString(),
            'expected_delivery_date' => $request->expected_delivery_date,
            'priority' => $request->priority ?? 'normal',
            'notes' => $request->notes,
            'created_by' => auth()->id(),
            'status' => 'draft',
            // Auto-fill price from facture proforma, or use provided price, or leave null
            'price' => $request->price ?? $factureProforma->price,
        ];

        $bonCommend = BonCommend::create($bonCommendData);

        // Copy items from facture proforma to bon commend
        foreach ($factureProforma->products as $factureProduct) {
            \App\Models\BonCommendItem::create([
                'bon_commend_id' => $bonCommend->id,
                'product_id' => $factureProduct->product_id,
                'factureproforma_id' => $factureProforma->id,
                'quantity_desired' => $factureProduct->quantity,
                'unit' => $factureProduct->unit ?? 'units',
                'price' => $factureProduct->price ?? null,
                'status' => 'pending',
            ]);
        }

        // Load relationships for response
        $bonCommend->load(['fournisseur', 'serviceDemand.service', 'creator', 'items.product.inventories']);

        return response()->json([
            'success' => true,
            'message' => 'Bon commend created successfully from facture proforma',
            'data' => $bonCommend,
            'price_auto_filled' => ! is_null($factureProforma->price) && is_null($request->price),
        ], 201);

        // } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Facture proforma not found',
        //         'error' => $e->getMessage()
        //     ], 404);
        // } catch (\Exception $e) {
        //     Log::error('Error creating bon commend from facture proforma: ' . $e->getMessage());
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Failed to create bon commend: ' . $e->getMessage(),
        //         'error' => $e->getMessage()
        //     ], 500);
        // }
    }

    /**
     * Submit bon commend for approval workflow
     */
    public function submitForApproval(Request $request, $id)
    {
        try {
            $bonCommend = BonCommend::with(['items.product.inventories'])->findOrFail($id);

            // Check if approval is required
            if (! $bonCommend->requiresApprovalCheck()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This purchase order does not require approval. You can directly confirm it.',
                ], 422);
            }

            // Find appropriate approver
            $approver = $bonCommend->findApprover();

            if (! $approver) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No active approval person found to handle this request.',
                ], 422);
            }

            // Create approval request
            $approval = \App\Models\BonCommendApproval::create([
                'bon_commend_id' => $bonCommend->id,
                'approval_person_id' => $approver->id,
                'requested_by' => auth()->id(),
                'amount' => $bonCommend->calculateTotalAmount(),
                'status' => 'pending',
                'requested_at' => now(),
            ]);

            // Update bon commend status
            $bonCommend->update([
                'approval_status' => 'pending_approval',
                'status' => 'pending_approval',
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Purchase order submitted for approval successfully',
                'data' => [
                    'approval_id' => $approval->id,
                    'approver' => $approver->user->name,
                    'total_amount' => $bonCommend->calculateTotalAmount(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error submitting bon commend for approval: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to submit for approval: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get approval thresholds for frontend display
     */
    public function getApprovalThresholds()
    {
        // try {
        $approvers = \App\Models\ApprovalPerson::with('user')
            ->where('is_active', true)
            ->orderBy('max_amount', 'asc')
            ->get()
            ->map(function ($approver) {
                return [
                    'id' => $approver->id,
                    'name' => $approver->user->name,
                    'max_amount' => $approver->max_amount,
                    'title' => $approver->title ?? 'Approver',
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $approvers,
        ]);

        // } catch (\Exception $e) {
        //     Log::error('Error fetching approval thresholds: ' . $e->getMessage());
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'Failed to fetch approval thresholds'
        //     ], 500);
        // }
    }

    /**
     * Get approval thresholds list (alternative endpoint)
     */
    public function getApprovalThresholdsList()
    {
        try {
            $approvers = \App\Models\ApprovalPerson::with('user')
                ->where('is_active', true)
                ->orderBy('max_amount', 'asc')
                ->get()
                ->map(function ($approver) {
                    return [
                        'id' => $approver->id,
                        'name' => $approver->user->name,
                        'max_amount' => $approver->max_amount,
                        'title' => $approver->title ?? 'Approver',
                        'pending_count' => $approver->approvals()->where('status', 'pending')->count(),
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => $approvers,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching approval thresholds list: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch approval thresholds list',
            ], 500);
        }
    }

    /**
     * Check if a bon commend needs approval and which approver
     */
    public function checkApprovalNeeded(Request $request, $id)
    {
        try {
            $bonCommend = BonCommend::with(['items.product'])->findOrFail($id);

            $needsApproval = $bonCommend->requiresApprovalCheck();
            $totalAmount = $bonCommend->calculateTotalAmount();
            $approver = null;

            if ($needsApproval) {
                $approver = $bonCommend->findApprover();
            }

            // Check if any products require approval
            $productsRequiringApproval = $bonCommend->items()
                ->whereHas('product', function ($query) {
                    $query->where('is_request_approval', true);
                })
                ->with('product')
                ->get()
                ->map(function ($item) {
                    return $item->product->name;
                });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'needs_approval' => $needsApproval,
                    'total_amount' => $totalAmount,
                    'approver' => $approver ? [
                        'id' => $approver->id,
                        'name' => $approver->user->name,
                        'title' => $approver->title ?? 'Approver',
                        'max_amount' => $approver->max_amount,
                    ] : null,
                    'products_requiring_approval' => $productsRequiringApproval,
                    'approval_reason' => $productsRequiringApproval->isNotEmpty()
                        ? 'Contains products that require approval'
                        : ($needsApproval ? 'Amount exceeds threshold' : null),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error checking approval needed: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to check approval requirements',
            ], 500);
        }
    }

    /**
     * Update the status of a bon commend
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|string|in:draft,sent,confirmed,completed,cancelled',
            ]);

            $bonCommend = BonCommend::with(['approvals', 'items.product.inventories'])->findOrFail($id);

            $oldStatus = $bonCommend->status;
            $newStatus = $request->status;

            // If trying to set status to 'confirmed', check if approval is required and approved
            if ($newStatus === 'confirmed' && $oldStatus !== 'confirmed') {
                $requiresApproval = $bonCommend->requiresApprovalCheck();

                if ($requiresApproval) {
                    // Check if there's an approved approval
                    $hasApprovedApproval = $bonCommend->approvals()
                        ->where('status', 'approved')
                        ->exists();

                    if (! $hasApprovedApproval) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Cannot confirm: This bon commend requires approval first. Please submit for approval or wait for approval.',
                            'requires_approval' => true,
                            'approval_status' => $bonCommend->approval_status,
                        ], 422);
                    }
                }
            }

            // Update the status
            $bonCommend->update([
                'status' => $newStatus,
            ]);

            // Handle specific status updates
            if ($newStatus === 'confirmed' && $oldStatus !== 'confirmed') {
                $bonCommend->update([
                    'is_confirmed' => true,
                    'confirmed_at' => now(),
                    'confirmed_by' => auth()->id(),
                ]);

                // Update all items to confirmed status if they exist
                if ($bonCommend->items()->exists()) {
                    $bonCommend->items()->update([
                        'status' => 'confirmed',
                    ]);
                }
            }

            // Load fresh data with relationships
            $bonCommend->load([
                'fournisseur',
                'serviceDemand.service',
                'creator',
                'confirmedBy',
                'items.product',
                'items.factureProforma',
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Bon commend status updated successfully',
                'data' => $bonCommend,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bon commend not found',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid status provided',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating bon commend status: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update bon commend status',
            ], 500);
        }
    }

    // Keep your other existing methods (destroy, download PDF, etc.)
}
