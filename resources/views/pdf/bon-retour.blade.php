<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Return Note - {{ $bonRetour->bon_retour_code }}</title>
    <style>
        @page {
            margin: 0cm 0cm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            line-height: 1.6;
            color: #333;
            background: white;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px 30px;
            position: relative;
        }

        .header-content {
            display: table;
            width: 100%;
        }

        .header-left {
            display: table-cell;
            vertical-align: middle;
        }

        .header-right {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
        }

        .document-title {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
            letter-spacing: 1px;
        }

        .document-number {
            font-size: 14px;
            opacity: 0.95;
        }

        .company-info {
            font-size: 10px;
            opacity: 0.9;
            line-height: 1.4;
        }

        /* Content */
        .content {
            padding: 30px;
        }

        /* Info Section */
        .info-section {
            margin-bottom: 25px;
        }

        .info-grid {
            display: table;
            width: 100%;
            table-layout: fixed;
        }

        .info-box {
            display: table-cell;
            width: 48%;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .info-box:first-child {
            margin-right: 4%;
        }

        .info-box-title {
            font-size: 12px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-row {
            margin-bottom: 8px;
            display: table;
            width: 100%;
        }

        .info-label {
            display: table-cell;
            width: 40%;
            font-weight: 600;
            color: #666;
            font-size: 10px;
        }

        .info-value {
            display: table-cell;
            width: 60%;
            color: #333;
            font-size: 10px;
        }

        /* Status Badges */
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-draft { background: #e2e3e5; color: #383d41; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-approved { background: #d1ecf1; color: #0c5460; }
        .badge-completed { background: #d4edda; color: #155724; }
        .badge-cancelled { background: #f8d7da; color: #721c24; }
        
        .badge-defective { background: #f8d7da; color: #721c24; }
        .badge-expired { background: #fff3cd; color: #856404; }
        .badge-overstock { background: #d1ecf1; color: #0c5460; }
        .badge-quality { background: #f8d7da; color: #721c24; }

        /* Reason Box */
        .reason-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 12px 15px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .reason-box-title {
            font-weight: bold;
            color: #856404;
            margin-bottom: 5px;
            font-size: 11px;
        }

        .reason-text {
            color: #856404;
            font-size: 10px;
            line-height: 1.5;
        }

        /* Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 10px;
        }

        .items-table thead {
            background: #667eea;
            color: white;
        }

        .items-table th {
            padding: 10px 8px;
            text-align: left;
            font-weight: 600;
            font-size: 10px;
            border-bottom: 2px solid #5a67d8;
        }

        .items-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #e2e8f0;
        }

        .items-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        .items-table tbody tr:hover {
            background: #e9ecef;
        }

        .product-name {
            font-weight: bold;
            color: #333;
            font-size: 11px;
        }

        .product-code {
            font-size: 9px;
            color: #666;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* Totals */
        .totals-section {
            margin-top: 30px;
            display: table;
            width: 100%;
        }

        .totals-box {
            display: table-cell;
            width: 40%;
            margin-left: auto;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            float: right;
        }

        .total-row {
            display: table;
            width: 100%;
            margin-bottom: 8px;
            font-size: 11px;
        }

        .total-label {
            display: table-cell;
            width: 60%;
            padding-right: 10px;
            text-align: right;
            font-weight: 600;
            color: #666;
        }

        .total-value {
            display: table-cell;
            width: 40%;
            text-align: right;
            color: #333;
        }

        .grand-total {
            border-top: 2px solid #667eea;
            padding-top: 10px;
            margin-top: 10px;
        }

        .grand-total .total-label {
            font-size: 13px;
            color: #667eea;
        }

        .grand-total .total-value {
            font-size: 16px;
            font-weight: bold;
            color: #667eea;
        }

        /* Footer */
        .footer {
            margin-top: 40px;
            padding: 20px 30px;
            background: #f8f9fa;
            border-top: 2px solid #dee2e6;
        }

        .footer-content {
            display: table;
            width: 100%;
        }

        .footer-left {
            display: table-cell;
            width: 50%;
        }

        .footer-right {
            display: table-cell;
            width: 50%;
            text-align: right;
        }

        .footer-text {
            font-size: 9px;
            color: #666;
            line-height: 1.4;
        }

        .signature-box {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
        }

        .signature-line {
            display: inline-block;
            width: 200px;
            border-bottom: 1px solid #666;
            margin: 0 20px;
        }

        /* Utility Classes */
        .mt-10 { margin-top: 10px; }
        .mt-20 { margin-top: 20px; }
        .mb-10 { margin-bottom: 10px; }
        .mb-20 { margin-bottom: 20px; }
        .text-bold { font-weight: bold; }
        .text-muted { color: #6c757d; }
    </style>
</head>
<body>
   

    <!-- Content -->
    <div class="content">
        <!-- Info Section -->
        <div class="info-section">
            <div class="info-grid">
                <div class="info-box">
                    <div class="info-box-title">Supplier Information</div>
                    <div class="info-row">
                        <div class="info-label">Company:</div>
                        <div class="info-value"><strong>{{ $bonRetour->fournisseur->company_name ?? 'N/A' }}</strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Contact:</div>
                        <div class="info-value">{{ $bonRetour->fournisseur->contact_person ?? 'N/A' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Phone:</div>
                        <div class="info-value">{{ $bonRetour->fournisseur->phone ?? 'N/A' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Email:</div>
                        <div class="info-value">{{ $bonRetour->fournisseur->email ?? 'N/A' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Address:</div>
                        <div class="info-value">{{ $bonRetour->fournisseur->address ?? 'N/A' }}</div>
                    </div>
                </div>

                <div class="info-box" style="margin-left: 4%;">
                    <div class="info-box-title">Return Details</div>
                    <div class="info-row">
                        <div class="info-label">Return Date:</div>
                        <div class="info-value"><strong>{{ $bonRetour->return_date->format('d/m/Y') }}</strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Type:</div>
                        <div class="info-value">
                            <span class="badge badge-{{ str_replace('_', '', $bonRetour->return_type) }}">
                                {{ strtoupper(str_replace('_', ' ', $bonRetour->return_type)) }}
                            </span>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Status:</div>
                        <div class="info-value">
                            <span class="badge badge-{{ $bonRetour->status }}">
                                {{ strtoupper($bonRetour->status) }}
                            </span>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Invoice Ref:</div>
                        <div class="info-value">{{ $bonRetour->reference_invoice ?? 'N/A' }}</div>
                    </div>
                    @if($bonRetour->credit_note_received)
                    <div class="info-row">
                        <div class="info-label">Credit Note:</div>
                        <div class="info-value">{{ $bonRetour->credit_note_number ?? 'Pending' }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Reason Box -->
        @if($bonRetour->reason)
        <div class="reason-box">
            <div class="reason-box-title">Return Reason:</div>
            <div class="reason-text">{{ $bonRetour->reason }}</div>
        </div>
        @endif

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%">#</th>
                    <th style="width: 30%">Product</th>
                    <th style="width: 12%">Batch/Serial</th>
                    <th style="width: 8%" class="text-center">Qty</th>
                    <th style="width: 10%" class="text-right">Unit Price</th>
                    <th style="width: 8%" class="text-center">TVA</th>
                    <th style="width: 12%" class="text-right">Total</th>
                    <th style="width: 15%">Reason</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bonRetour->items as $index => $item)
                @php
                    $itemSubtotal = $item->quantity_returned * $item->unit_price;
                    $itemTax = $itemSubtotal * ($item->tva / 100);
                    $itemTotal = $itemSubtotal + $itemTax;
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <div class="product-name">{{ $item->product->name ?? 'Unknown Product' }}</div>
                        <div class="product-code">Code: {{ $item->product->code ?? 'N/A' }}</div>
                    </td>
                    <td>
                        @if($item->batch_number)
                            <div>Batch: {{ $item->batch_number }}</div>
                        @endif
                        @if($item->serial_number)
                            <div>SN: {{ $item->serial_number }}</div>
                        @endif
                    </td>
                    <td class="text-center"><strong>{{ $item->quantity_returned }}</strong></td>
                    <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-center">{{ $item->tva }}%</td>
                    <td class="text-right"><strong>${{ number_format($itemTotal, 2) }}</strong></td>
                    <td>
                        <small>{{ ucfirst(str_replace('_', ' ', $item->return_reason)) }}</small>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals-section">
            <div class="totals-box">
                <div class="total-row">
                    <div class="total-label">Subtotal:</div>
                    <div class="total-value">${{ number_format($subtotal, 2) }}</div>
                </div>
                <div class="total-row">
                    <div class="total-label">Tax (TVA):</div>
                    <div class="total-value">${{ number_format($totalTax, 2) }}</div>
                </div>
                <div class="total-row grand-total">
                    <div class="total-label">TOTAL AMOUNT:</div>
                    <div class="total-value">${{ number_format($totalAmount, 2) }}</div>
                </div>
            </div>
        </div>

        <!-- Clear float -->
        <div style="clear: both;"></div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-content">
            <div class="footer-left">
                <div class="footer-text">
                    <strong>Document Information:</strong><br>
                    Created by: {{ $bonRetour->creator->name ?? 'System' }}<br>
                    Date: {{ $bonRetour->created_at->format('d/m/Y H:i') }}<br>
                    @if($bonRetour->approved_at)
                        Approved by: {{ $bonRetour->approver->name ?? 'N/A' }}<br>
                        Approved on: {{ $bonRetour->approved_at->format('d/m/Y H:i') }}
                    @endif
                </div>
            </div>
            <div class="footer-right">
                <div class="footer-text">
                    Generated on: {{ $generated_at->format('d/m/Y H:i:s') }}<br>
                    Page 1 of 1<br>
                    <em>This is a computer-generated document.</em>
                </div>
            </div>
        </div>

        <!-- Signature Section -->
        <div class="signature-box">
            <table style="width: 100%">
                <tr>
                    <td style="width: 50%; text-align: center;">
                        <div class="signature-line"></div>
                        <div class="footer-text mt-10">Prepared By</div>
                    </td>
                    <td style="width: 50%; text-align: center;">
                        <div class="signature-line"></div>
                        <div class="footer-text mt-10">Authorized Signature</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
