<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Http\Requests\BonRetour\StoreBonRetourRequest;
use App\Http\Requests\BonRetour\UpdateBonRetourRequest;
use App\Http\Resources\BonRetourCollection;
use App\Http\Resources\BonRetourResource;
use App\Models\BonRetour;
use App\Services\BonRetourService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BonRetourController extends Controller
{
    protected $bonRetourService;

    public function __construct(BonRetourService $bonRetourService)
    {
        $this->bonRetourService = $bonRetourService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = BonRetour::with(['fournisseur', 'items.product', 'creator', 'service']);

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('return_type')) {
                $query->where('return_type', $request->return_type);
            }

            if ($request->has('fournisseur_id')) {
                $query->where('fournisseur_id', $request->fournisseur_id);
            }

            if ($request->has('service_id')) {
                $query->where('service_id', $request->service_id);
            }

            if ($request->has('date_from')) {
                $query->whereDate('return_date', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('return_date', '<=', $request->date_to);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('bon_retour_code', 'like', "%{$search}%")
                        ->orWhere('reference_invoice', 'like', "%{$search}%")
                        ->orWhere('credit_note_number', 'like', "%{$search}%")
                        ->orWhereHas('fournisseur', function ($q) use ($search) {
                            $q->where('company_name', 'like', "%{$search}%");
                        });
                });
            }

            $bonRetours = $query->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 15);

            return response()->json([
                'status' => 'success',
                'data' => new BonRetourCollection($bonRetours),
                'message' => 'Return notes fetched successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching bon retours: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch return notes',
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBonRetourRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $bonRetour = $this->bonRetourService->create($request->validated());

            DB::commit();

            return response()->json([
                'status' => 'success',
                'data' => new BonRetourResource($bonRetour->load(['items.product', 'fournisseur'])),
                'message' => 'Return note created successfully',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating bon retour: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(BonRetour $bonRetour): JsonResponse
    {
        try {
            $bonRetour->load([
                'fournisseur',
                'items.product',
                'items.originalItem',
                'bonEntree.items',
                'creator',
                'approver',
                'service',
            ]);

            return response()->json([
                'status' => 'success',
                'data' => new BonRetourResource($bonRetour),
                'message' => 'Return note fetched successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching bon retour: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch return note',
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBonRetourRequest $request, BonRetour $bonRetour): JsonResponse
    {
        if (! $bonRetour->is_editable) {
            return response()->json([
                'status' => 'error',
                'message' => 'This return note cannot be edited',
            ], 403);
        }

        DB::beginTransaction();
        try {
            $bonRetour = $this->bonRetourService->update($bonRetour, $request->validated());

            DB::commit();

            return response()->json([
                'status' => 'success',
                'data' => new BonRetourResource($bonRetour->load(['items.product', 'fournisseur'])),
                'message' => 'Return note updated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating bon retour: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BonRetour $bonRetour): JsonResponse
    {
        if (! $bonRetour->is_editable) {
            return response()->json([
                'status' => 'error',
                'message' => 'This return note cannot be deleted',
            ], 403);
        }

        DB::beginTransaction();
        try {
            $this->bonRetourService->delete($bonRetour);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Return note deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting bon retour: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete return note',
            ], 500);
        }
    }

    /**
     * Submit for approval
     */
    public function submitForApproval(BonRetour $bonRetour): JsonResponse
    {
        if ($bonRetour->status !== 'draft') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only draft returns can be submitted for approval',
            ], 400);
        }

        try {
            $bonRetour->update(['status' => 'pending']);

            return response()->json([
                'status' => 'success',
                'data' => new BonRetourResource($bonRetour),
                'message' => 'Return note submitted for approval',
            ]);
        } catch (\Exception $e) {
            Log::error('Error submitting bon retour for approval: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to submit for approval',
            ], 500);
        }
    }

    /**
     * Approve return note
     */
    public function approve(BonRetour $bonRetour): JsonResponse
    {
        if ($bonRetour->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only pending returns can be approved',
            ], 400);
        }

        DB::beginTransaction();
        try {
            $bonRetour = $this->bonRetourService->approve($bonRetour, auth()->id());

            DB::commit();

            return response()->json([
                'status' => 'success',
                'data' => new BonRetourResource($bonRetour),
                'message' => 'Return note approved successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error approving bon retour: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Complete return note
     */
    public function complete(BonRetour $bonRetour): JsonResponse
    {
        try {
            $bonRetour->complete();

            return response()->json([
                'status' => 'success',
                'data' => new BonRetourResource($bonRetour),
                'message' => 'Return note completed successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error completing bon retour: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel return note
     */
    public function cancel(Request $request, BonRetour $bonRetour): JsonResponse
    {
        try {
            $bonRetour->cancel($request->reason);

            return response()->json([
                'status' => 'success',
                'data' => new BonRetourResource($bonRetour),
                'message' => 'Return note cancelled successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error cancelling bon retour: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate PDF
     */
    public function generatePdf(BonRetour $bonRetour, Request $request)
    {
        try {
            // Load all relationships needed for PDF
            $bonRetour->load([
                'fournisseur',
                'items.product',
                'creator',
                'approver',
                'service',
                'bonEntree',
            ]);

            // Calculate totals
            $subtotal = $bonRetour->items->sum(function ($item) {
                return $item->quantity_returned * $item->unit_price;
            });

            $totalTax = $bonRetour->items->sum(function ($item) {
                $price = $item->quantity_returned * $item->unit_price;

                return $price * ($item->tva / 100);
            });

            $totalAmount = $subtotal + $totalTax;

            // Prepare data for PDF
            $data = [
                'bonRetour' => $bonRetour,
                'subtotal' => $subtotal,
                'totalTax' => $totalTax,
                'totalAmount' => $totalAmount,
                'company' => [
                    'name' => config('app.company_name', 'Hospital Management System'),
                    'address' => config('app.company_address', '123 Medical Center Drive'),
                    'city' => config('app.company_city', 'Healthcare City, HC 12345'),
                    'phone' => config('app.company_phone', '+1 (555) 123-4567'),
                    'email' => config('app.company_email', 'info@hospital.com'),
                    'website' => config('app.company_website', 'www.hospital.com'),
                    'tax_id' => config('app.company_tax_id', 'TAX-123456789'),
                ],
                'generated_at' => now(),
            ];

            // Generate PDF
            $pdf = Pdf::loadView('pdf.bon-retour', $data);

            // Set paper size and orientation
            $pdf->setPaper('a4', 'portrait');

            // Return as download or stream based on request
            if ($request->download === 'true') {
                return $pdf->download("return-note-{$bonRetour->bon_retour_code}.pdf");
            }

            return $pdf->stream("return-note-{$bonRetour->bon_retour_code}.pdf");

        } catch (\Exception $e) {
            Log::error('Error generating BonRetour PDF: '.$e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate PDF: '.$e->getMessage(),
            ], 500);
        }
    }

    public function downloadPdf($id)
    {
        try {
            $bonRetour = BonRetour::with([
                'fournisseur',
                'items.product',
                'creator',
                'approver',
            ])->findOrFail($id);

            // If the Blade template fails, use HTML string
            $html = $this->generatePdfHtml($bonRetour);

            $pdf = Pdf::loadHTML($html);
            $pdf->setPaper('a4', 'portrait');

            return $pdf->download("return-note-{$bonRetour->bon_retour_code}.pdf");

        } catch (\Exception $e) {
            Log::error('Error downloading BonRetour PDF: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate PDF',
            ], 500);
        }
    }

    /**
     * Generate PDF HTML as string (fallback method)
     */
    protected function generatePdfHtml($bonRetour)
    {
        $subtotal = $bonRetour->items->sum(function ($item) {
            return $item->quantity_returned * $item->unit_price;
        });

        $totalTax = $bonRetour->items->sum(function ($item) {
            $price = $item->quantity_returned * $item->unit_price;

            return $price * ($item->tva / 100);
        });

        $totalAmount = $subtotal + $totalTax;

        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Return Note - '.$bonRetour->bon_retour_code.'</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.5; color: #333; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; margin-bottom: 20px; }
        .header h1 { font-size: 24px; margin-bottom: 5px; }
        .header p { opacity: 0.9; }
        .container { padding: 0 20px; }
        .info-grid { display: table; width: 100%; margin-bottom: 20px; }
        .info-box { display: table-cell; width: 48%; vertical-align: top; padding: 15px; background: #f8f9fa; border-radius: 5px; }
        .info-box:first-child { margin-right: 4%; }
        .info-box h3 { color: #667eea; margin-bottom: 10px; font-size: 14px; }
        .info-row { margin-bottom: 8px; }
        .info-label { font-weight: bold; color: #666; display: inline-block; width: 120px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; }
        .badge-warning { background: #ffc107; color: #000; }
        .badge-danger { background: #dc3545; color: white; }
        .badge-info { background: #17a2b8; color: white; }
        .badge-success { background: #28a745; color: white; }
        .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .table th { background: #667eea; color: white; padding: 10px; text-align: left; font-size: 11px; }
        .table td { padding: 10px; border-bottom: 1px solid #dee2e6; font-size: 11px; }
        .table tr:nth-child(even) { background: #f8f9fa; }
        .totals { margin-top: 20px; text-align: right; }
        .total-row { margin-bottom: 5px; }
        .total-label { display: inline-block; width: 150px; font-weight: bold; }
        .grand-total { font-size: 16px; color: #667eea; border-top: 2px solid #667eea; padding-top: 10px; margin-top: 10px; }
        .footer { margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 5px; }
        .footer p { color: #666; font-size: 10px; }
        .reason-box { background: #fff3cd; border: 1px solid #ffc107; padding: 10px; border-radius: 5px; margin: 20px 0; }
        .reason-box h4 { color: #856404; margin-bottom: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>RETURN NOTE</h1>
        <p>Document No: '.$bonRetour->bon_retour_code.'</p>
    </div>

    <div class="container">
        <div class="info-grid">
            <div class="info-box">
                <h3>SUPPLIER INFORMATION</h3>
                <div class="info-row">
                    <span class="info-label">Company:</span>
                    <strong>'.($bonRetour->fournisseur->company_name ?? 'N/A').'</strong>
                </div>
                <div class="info-row">
                    <span class="info-label">Contact:</span>
                    '.($bonRetour->fournisseur->contact_person ?? 'N/A').'
                </div>
                <div class="info-row">
                    <span class="info-label">Phone:</span>
                    '.($bonRetour->fournisseur->phone ?? 'N/A').'
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    '.($bonRetour->fournisseur->email ?? 'N/A').'
                </div>
            </div>

            <div class="info-box">
                <h3>RETURN DETAILS</h3>
                <div class="info-row">
                    <span class="info-label">Return Date:</span>
                    <strong>'.$bonRetour->return_date->format('d/m/Y').'</strong>
                </div>
                <div class="info-row">
                    <span class="info-label">Return Type:</span>
                    <span class="badge badge-'.$this->getReturnTypeBadgeClass($bonRetour->return_type).'">
                        '.strtoupper(str_replace('_', ' ', $bonRetour->return_type)).'
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="badge badge-'.$this->getStatusBadgeClass($bonRetour->status).'">
                        '.strtoupper($bonRetour->status).'
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Invoice Ref:</span>
                    '.($bonRetour->reference_invoice ?? 'N/A').'
                </div>
            </div>
        </div>';

        if ($bonRetour->reason) {
            $html .= '
        <div class="reason-box">
            <h4>Return Reason:</h4>
            <p>'.nl2br(htmlspecialchars($bonRetour->reason)).'</p>
        </div>';
        }

        $html .= '
        <table class="table">
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="35%">Product</th>
                    <th width="10%">Batch/Serial</th>
                    <th width="10%">Quantity</th>
                    <th width="10%">Unit Price</th>
                    <th width="8%">TVA %</th>
                    <th width="12%">Total</th>
                    <th width="10%">Reason</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($bonRetour->items as $index => $item) {
            $itemTotal = $item->quantity_returned * $item->unit_price * (1 + $item->tva / 100);
            $html .= '
                <tr>
                    <td>'.($index + 1).'</td>
                    <td>
                        <strong>'.($item->product->name ?? 'Unknown').'</strong><br>
                        <small>Code: '.($item->product->code ?? 'N/A').'</small>
                    </td>
                    <td>
                        '.($item->batch_number ? 'Batch: '.$item->batch_number.'<br>' : '').'
                        '.($item->serial_number ? 'SN: '.$item->serial_number : '').'
                    </td>
                    <td><strong>'.$item->quantity_returned.'</strong></td>
                    <td>$'.number_format($item->unit_price, 2).'</td>
                    <td>'.$item->tva.'%</td>
                    <td><strong>$'.number_format($itemTotal, 2).'</strong></td>
                    <td><small>'.ucfirst($item->return_reason).'</small></td>
                </tr>';
        }

        $html .= '
            </tbody>
        </table>

        <div class="totals">
            <div class="total-row">
                <span class="total-label">Subtotal:</span>
                $'.number_format($subtotal, 2).'
            </div>
            <div class="total-row">
                <span class="total-label">Tax (TVA):</span>
                $'.number_format($totalTax, 2).'
            </div>
            <div class="total-row grand-total">
                <span class="total-label">TOTAL AMOUNT:</span>
                <strong>$'.number_format($totalAmount, 2).'</strong>
            </div>
        </div>

        <div class="footer">
            <p><strong>Created by:</strong> '.($bonRetour->creator->name ?? 'System').' on '.$bonRetour->created_at->format('d/m/Y H:i').'</p>
            '.($bonRetour->approved_at ? '<p><strong>Approved by:</strong> '.($bonRetour->approver->name ?? 'N/A').' on '.$bonRetour->approved_at->format('d/m/Y H:i').'</p>' : '').'
            <p style="margin-top: 10px;">This is a computer-generated document. No signature is required.</p>
        </div>
    </div>
</body>
</html>';

        return $html;
    }

    protected function getReturnTypeBadgeClass($type)
    {
        return match ($type) {
            'defective' => 'danger',
            'expired' => 'warning',
            'overstock' => 'info',
            default => 'secondary'
        };
    }

    protected function getStatusBadgeClass($status)
    {
        return match ($status) {
            'completed' => 'success',
            'approved' => 'info',
            'pending' => 'warning',
            'draft' => 'secondary',
            default => 'secondary'
        };
    }

    /**
     * Get statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $stats = $this->bonRetourService->getStatistics($request->all());

            return response()->json([
                'status' => 'success',
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting statistics: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get statistics',
            ], 500);
        }
    }
}
