<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Goods Receipt - {{ $bonReception->bonReceptionCode }}</title>
    <style>
        @page {
            margin: 1.5cm 1cm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            line-height: 1.5;
            color: #2d3748;
            background: white;
        }

        /* Header */
        .header {
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }

        .header-content {
            display: table;
            width: 100%;
        }

        .header-left {
            display: table-cell;
            vertical-align: middle;
            width: 50%;
        }

        .header-right {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            width: 50%;
        }

        .company-logo {
            font-size: 22px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 8px;
        }

        .document-title {
            font-size: 24px;
            font-weight: bold;
            color: #1e3a8a;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }

        .document-number {
            font-size: 16px;
            font-weight: 600;
            color: #2563eb;
            background: #dbeafe;
            padding: 5px 12px;
            border-radius: 4px;
            display: inline-block;
        }

        .header-info {
            font-size: 9px;
            color: #64748b;
            line-height: 1.6;
        }

        /* Info Section */
        .info-section {
            margin-bottom: 20px;
        }

        .info-grid {
            display: table;
            width: 100%;
            border-collapse: separate;
            border-spacing: 12px 0;
        }

        .info-box {
            display: table-cell;
            width: 48%;
            padding: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            vertical-align: top;
        }

        .info-box-title {
            font-size: 11px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 4px;
        }

        .info-row {
            margin-bottom: 6px;
            display: table;
            width: 100%;
        }

        .info-label {
            display: table-cell;
            width: 35%;
            font-weight: 600;
            color: #475569;
            font-size: 9px;
        }

        .info-value {
            display: table-cell;
            width: 65%;
            color: #1e293b;
            font-size: 9px;
        }

        /* Status Badges */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            border: 1px solid;
        }

        .badge-pending { 
            background: #fef3c7; 
            color: #92400e;
            border-color: #fbbf24;
        }
        .badge-completed { 
            background: #d1fae5; 
            color: #065f46;
            border-color: #10b981;
        }
        .badge-cancelled { 
            background: #fee2e2; 
            color: #991b1b;
            border-color: #ef4444;
        }
        .badge-rejected { 
            background: #fee2e2; 
            color: #991b1b;
            border-color: #ef4444;
        }

        /* Alert Boxes */
        .alert-box {
            padding: 10px 12px;
            border-radius: 4px;
            margin: 15px 0;
            border-left: 4px solid;
        }

        .alert-warning {
            background: #fef3c7;
            border-left-color: #f59e0b;
            color: #92400e;
        }

        .alert-info {
            background: #dbeafe;
            border-left-color: #3b82f6;
            color: #1e40af;
        }

        .alert-title {
            font-weight: bold;
            margin-bottom: 4px;
            font-size: 10px;
        }

        .alert-text {
            font-size: 9px;
            line-height: 1.4;
        }

        /* Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 9px;
            border: 1px solid #cbd5e1;
        }

        .items-table thead {
            background: #1e40af;
            color: white;
        }

        .items-table th {
            padding: 8px 6px;
            text-align: left;
            font-weight: 700;
            font-size: 9px;
            border: 1px solid #1e3a8a;
        }

        .items-table td {
            padding: 7px 6px;
            border: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        .items-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .items-table tbody tr {
            background: white;
        }

        .product-name {
            font-weight: 600;
            color: #1e293b;
            font-size: 9px;
            line-height: 1.3;
        }

        .product-code {
            font-size: 8px;
            color: #64748b;
            margin-top: 2px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-success {
            color: #28a745;
        }

        .text-warning {
            color: #ffc107;
        }

        .text-danger {
            color: #dc3545;
        }

        /* Summary Box */
        .summary-section {
            margin-top: 20px;
            background: #eff6ff;
            padding: 12px;
            border-radius: 6px;
            border: 2px solid #3b82f6;
        }

        .summary-title {
            font-size: 11px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .summary-grid {
            display: table;
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .summary-item {
            display: table-cell;
            width: 25%;
            text-align: center;
            padding: 8px;
            border-right: 1px solid #bfdbfe;
        }

        .summary-item:last-child {
            border-right: none;
        }

        .summary-value {
            font-size: 18px;
            font-weight: bold;
            color: #1e40af;
            display: block;
            margin-bottom: 4px;
        }

        .summary-label {
            font-size: 8px;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        /* Footer */
        .footer {
            margin-top: 30px;
            padding: 15px 0;
            border-top: 2px solid #cbd5e1;
        }

        .footer-content {
            display: table;
            width: 100%;
        }

        .footer-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .footer-right {
            display: table-cell;
            width: 50%;
            text-align: right;
            vertical-align: top;
        }

        .footer-text {
            font-size: 8px;
            color: #64748b;
            line-height: 1.5;
        }

        .signature-box {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .signature-line {
            display: inline-block;
            width: 180px;
            border-bottom: 2px solid #475569;
            margin: 0 10px;
        }

        .signature-label {
            font-size: 8px;
            color: #475569;
            margin-top: 5px;
            font-weight: 600;
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
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div class="header-left">
                <div class="company-logo">{{ $company ?? 'Hospital Management System' }}</div>
                <div class="header-info">
                    Medical Supply Chain Management<br>
                    Document Control System
                </div>
            </div>
            <div class="header-right">
                <div class="document-title">GOODS RECEIPT</div>
                <div style="margin: 8px 0;">
                    <span class="document-number">{{ $bonReception->bonReceptionCode }}</span>
                </div>
                <div class="header-info">
                    Date: <strong>{{ $bonReception->date_reception->format('d/m/Y') }}</strong><br>
                    Status: 
                    @if($bonReception->is_confirmed)
                        <span class="badge badge-completed">CONFIRMED</span>
                    @else
                        <span class="badge badge-pending">PENDING</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
        <!-- Info Section -->
        <div class="info-section">
            <div class="info-grid">
                <div class="info-box">
                    <div class="info-box-title">🏢 Supplier Information</div>
                    <div class="info-row">
                        <div class="info-label">Company:</div>
                        <div class="info-value"><strong>{{ $bonReception->fournisseur->company_name ?? '-' }}</strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Contact Person:</div>
                        <div class="info-value">{{ $bonReception->fournisseur->contact_person ?? '-' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Phone:</div>
                        <div class="info-value">{{ $bonReception->fournisseur->phone ?? '-' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Email:</div>
                        <div class="info-value">{{ $bonReception->fournisseur->email ?? '-' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Address:</div>
                        <div class="info-value">{{ $bonReception->fournisseur->address ?? '-' }}</div>
                    </div>
                </div>

                <div class="info-box">
                    <div class="info-box-title">📋 Reception Details</div>
                    <div class="info-row">
                        <div class="info-label">Receipt Date:</div>
                        <div class="info-value"><strong>{{ $bonReception->date_reception->format('d/m/Y') }}</strong></div>
                    </div>
                    @if($bonReception->bonCommend)
                    <div class="info-row">
                        <div class="info-label">Purchase Order:</div>
                        <div class="info-value"><strong>{{ $bonReception->bonCommend->bonCommendCode }}</strong></div>
                    </div>
                    @endif
                    <div class="info-row">
                        <div class="info-label">Delivery Note:</div>
                        <div class="info-value">{{ $bonReception->delivery_note_number ?? '-' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Invoice Number:</div>
                        <div class="info-value">{{ $bonReception->invoice_number ?? '-' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Received By:</div>
                        <div class="info-value"><strong>{{ $bonReception->receivedByUser->name ?? '-' }}</strong></div>
                    </div>
                    @if($bonReception->is_confirmed && $bonReception->confirmedByUser)
                    <div class="info-row">
                        <div class="info-label">Confirmed By:</div>
                        <div class="info-value"><strong>{{ $bonReception->confirmedByUser->name }}</strong></div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Observation Box -->
        @if($bonReception->observation)
        <div class="alert-box alert-info">
            <div class="alert-title">Observation:</div>
            <div class="alert-text">{{ $bonReception->observation }}</div>
        </div>
        @endif

        <!-- Alert for Surplus/Shortage -->
        @if($has_surplus)
        <div class="alert-box alert-warning">
            <div class="alert-title">⚠ Warning: Surplus Items Detected</div>
            <div class="alert-text">
                This reception contains surplus items (quantities received exceed ordered quantities). 
                @if($bonReception->bonRetour)
                    A return note ({{ $bonReception->bonRetour->bon_retour_code }}) has been created.
                @endif
            </div>
        </div>
        @endif

        @if($has_shortage)
        <div class="alert-box alert-warning">
            <div class="alert-title">⚠ Warning: Shortage Items Detected</div>
            <div class="alert-text">
                Some items were received in quantities less than ordered.
            </div>
        </div>
        @endif

        <!-- Items Table -->
        <div style="margin: 20px 0;">
            <h3 style="font-size: 11px; font-weight: bold; color: #1e40af; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px;">
                📦 Received Items
            </h3>
        </div>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 4%; text-align: center;">#</th>
                    <th style="width: 32%">Product Details</th>
                    <th style="width: 9%; text-align: center;">Ordered</th>
                    <th style="width: 9%; text-align: center;">Received</th>
                    <th style="width: 9%; text-align: center;">Surplus</th>
                    <th style="width: 9%; text-align: center;">Shortage</th>
                    <th style="width: 13%; text-align: right;">Unit Price</th>
                    <th style="width: 15%; text-align: right;">Total Value</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bonReception->items as $index => $item)
                @php
                    $itemTotal = $item->quantity_received * $item->unit_price;
                @endphp
                <tr>
                    <td class="text-center" style="background: #f1f5f9; font-weight: 600;">{{ $index + 1 }}</td>
                    <td>
                        <div class="product-name">{{ $item->product->name ?? 'Unknown Product' }}</div>
                        <div class="product-code">SKU: {{ $item->product->code ?? '-' }}</div>
                        @if($item->notes)
                        <div class="product-code" style="color: #3b82f6;">📝 {{ $item->notes }}</div>
                        @endif
                    </td>
                    <td class="text-center" style="color: #64748b;">{{ $item->quantity_ordered ?? 0 }}</td>
                    <td class="text-center" style="background: #f0fdf4; font-weight: 700; color: #166534;">{{ $item->quantity_received }}</td>
                    <td class="text-center">
                        @if($item->quantity_surplus > 0)
                            <strong style="color: #ea580c; font-weight: 700;">+{{ $item->quantity_surplus }}</strong>
                        @else
                            <span style="color: #cbd5e1;">-</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($item->quantity_shortage > 0)
                            <strong style="color: #dc2626; font-weight: 700;">-{{ $item->quantity_shortage }}</strong>
                        @else
                            <span style="color: #cbd5e1;">-</span>
                        @endif
                    </td>
                    <td class="text-right" style="font-family: monospace;">${{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right" style="background: #eff6ff; font-weight: 700; font-family: monospace;">${{ number_format($itemTotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Summary Section -->
        <div class="summary-section">
            <div class="summary-title">📊 Reception Summary</div>
            <div class="summary-grid">
                <div class="summary-item">
                    <span class="summary-value">{{ $bonReception->items->count() }}</span>
                    <div class="summary-label">Product Lines</div>
                </div>
                <div class="summary-item">
                    <span class="summary-value">{{ $bonReception->items->sum('quantity_ordered') }}</span>
                    <div class="summary-label">Total Ordered</div>
                </div>
                <div class="summary-item">
                    <span class="summary-value">{{ $bonReception->items->sum('quantity_received') }}</span>
                    <div class="summary-label">Total Received</div>
                </div>
                <div class="summary-item">
                    <span class="summary-value" style="font-size: 16px;">${{ number_format($bonReception->items->sum(function($item) { return $item->quantity_received * $item->unit_price; }), 2) }}</span>
                    <div class="summary-label">Total Value</div>
                </div>
            </div>
        </div>

        <!-- Surplus Items Section (if any) -->
        @if($has_surplus && $surplus_items->count() > 0)
        <div style="margin-top: 20px; padding: 12px; background: #fef3c7; border: 2px solid #f59e0b; border-radius: 6px;">
            <div style="font-size: 10px; font-weight: bold; color: #92400e; margin-bottom: 8px;">
                ⚠️ SURPLUS ITEMS DETECTED
            </div>
            <table style="width: 100%; border-collapse: collapse; font-size: 9px;">
                <thead>
                    <tr style="background: #fbbf24; color: white;">
                        <th style="padding: 6px; border: 1px solid #f59e0b; text-align: left;">Product</th>
                        <th style="padding: 6px; border: 1px solid #f59e0b; text-align: center; width: 15%;">Ordered</th>
                        <th style="padding: 6px; border: 1px solid #f59e0b; text-align: center; width: 15%;">Received</th>
                        <th style="padding: 6px; border: 1px solid #f59e0b; text-align: center; width: 15%;">Surplus</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($surplus_items as $item)
                    <tr style="background: white;">
                        <td style="padding: 6px; border: 1px solid #fcd34d;">{{ $item->product->name ?? 'Unknown' }}</td>
                        <td style="padding: 6px; border: 1px solid #fcd34d; text-align: center;">{{ $item->quantity_ordered }}</td>
                        <td style="padding: 6px; border: 1px solid #fcd34d; text-align: center;">{{ $item->quantity_received }}</td>
                        <td style="padding: 6px; border: 1px solid #fcd34d; text-align: center; font-weight: 700; color: #ea580c;">+{{ $item->quantity_surplus }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Shortage Items Section (if any) -->
        @if($has_shortage && $shortage_items->count() > 0)
        <div style="margin-top: 20px; padding: 12px; background: #fee2e2; border: 2px solid #ef4444; border-radius: 6px;">
            <div style="font-size: 10px; font-weight: bold; color: #991b1b; margin-bottom: 8px;">
                ⚠️ SHORTAGE ITEMS DETECTED
            </div>
            <table style="width: 100%; border-collapse: collapse; font-size: 9px;">
                <thead>
                    <tr style="background: #ef4444; color: white;">
                        <th style="padding: 6px; border: 1px solid #dc2626; text-align: left;">Product</th>
                        <th style="padding: 6px; border: 1px solid #dc2626; text-align: center; width: 15%;">Ordered</th>
                        <th style="padding: 6px; border: 1px solid #dc2626; text-align: center; width: 15%;">Received</th>
                        <th style="padding: 6px; border: 1px solid #dc2626; text-align: center; width: 15%;">Shortage</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($shortage_items as $item)
                    <tr style="background: white;">
                        <td style="padding: 6px; border: 1px solid #fca5a5;">{{ $item->product->name ?? 'Unknown' }}</td>
                        <td style="padding: 6px; border: 1px solid #fca5a5; text-align: center;">{{ $item->quantity_ordered }}</td>
                        <td style="padding: 6px; border: 1px solid #fca5a5; text-align: center;">{{ $item->quantity_received }}</td>
                        <td style="padding: 6px; border: 1px solid #fca5a5; text-align: center; font-weight: 700; color: #dc2626;">-{{ $item->quantity_shortage }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-content">
            <div class="footer-left">
                <div class="footer-text">
                    <strong>Document Information:</strong><br>
                    Created by: {{ $bonReception->createdByUser->name ?? 'System' }}<br>
                    Created on: {{ $bonReception->created_at->format('d/m/Y H:i') }}<br>
                    @if($bonReception->is_confirmed && $bonReception->confirmed_at)
                        Confirmed by: {{ $bonReception->confirmedByUser->name ?? 'N/A' }}<br>
                        Confirmed on: {{ $bonReception->confirmed_at->format('d/m/Y H:i') }}
                    @endif
                </div>
            </div>
            <div class="footer-right">
                <div class="footer-text">
                    Generated on: {{ now()->format('d/m/Y H:i:s') }}<br>
                    Page 1 of 1<br>
                    <em>This is a computer-generated document.</em>
                </div>
            </div>
        </div>

        <!-- Signature Section -->
        <div class="signature-box">
            <table style="width: 100%; border-collapse: separate; border-spacing: 10px 0;">
                <tr>
                    <td style="width: 33%; text-align: center; vertical-align: top;">
                        <div style="padding: 50px 0 10px;">
                            <div class="signature-line"></div>
                        </div>
                        <div class="signature-label">PREPARED BY</div>
                        <div class="footer-text" style="margin-top: 3px;">{{ $bonReception->createdByUser->name ?? 'System' }}</div>
                    </td>
                    <td style="width: 33%; text-align: center; vertical-align: top;">
                        <div style="padding: 50px 0 10px;">
                            <div class="signature-line"></div>
                        </div>
                        <div class="signature-label">RECEIVED BY</div>
                        <div class="footer-text" style="margin-top: 3px;">{{ $bonReception->receivedByUser->name ?? '-' }}</div>
                    </td>
                    <td style="width: 33%; text-align: center; vertical-align: top;">
                        <div style="padding: 50px 0 10px;">
                            <div class="signature-line"></div>
                        </div>
                        <div class="signature-label">AUTHORIZED BY</div>
                        <div class="footer-text" style="margin-top: 3px;">
                            @if($bonReception->is_confirmed && $bonReception->confirmedByUser)
                                {{ $bonReception->confirmedByUser->name }}
                            @else
                                Pending
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
