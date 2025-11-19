<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>External Prescription - {{ $prescription->prescription_code }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #4A5568;
            padding-bottom: 15px;
        }
        .header h1 {
            color: #2D3748;
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0;
            color: #4A5568;
        }
        .info-section {
            margin-bottom: 25px;
            background: #F7FAFC;
            padding: 15px;
            border-radius: 5px;
        }
        .info-row {
            margin: 8px 0;
        }
        .info-label {
            font-weight: bold;
            color: #2D3748;
            display: inline-block;
            width: 150px;
        }
        .info-value {
            color: #4A5568;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .items-table th {
            background-color: #4299E1;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #3182CE;
        }
        .items-table td {
            padding: 10px 12px;
            border: 1px solid #E2E8F0;
        }
        .items-table tr:nth-child(even) {
            background-color: #F7FAFC;
        }
        .items-table tr:hover {
            background-color: #EDF2F7;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #718096;
            border-top: 1px solid #E2E8F0;
            padding-top: 15px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        .status-draft {
            background-color: #FED7D7;
            color: #742A2A;
        }
        .status-confirmed {
            background-color: #C6F6D5;
            color: #22543D;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>EXTERNAL PRESCRIPTION</h1>
        <p><strong>Prescription Code:</strong> {{ $prescription->prescription_code }}</p>
        <p><strong>Date:</strong> {{ $prescription->created_at->format('d/m/Y H:i') }}</p>
    </div>

    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Created By:</span>
            <span class="info-value">{{ $prescription->creator->name }}</span>
        </div>
        @if($prescription->doctor)
        <div class="info-row">
            <span class="info-label">Doctor:</span>
            <span class="info-value">Dr. {{ $prescription->doctor->user->name }}</span>
        </div>
        @endif
        <div class="info-row">
            <span class="info-label">Status:</span>
            <span class="status-badge status-{{ $prescription->status }}">{{ $prescription->status_label }}</span>
        </div>
        @if($prescription->description)
        <div class="info-row">
            <span class="info-label">Description:</span>
            <span class="info-value">{{ $prescription->description }}</span>
        </div>
        @endif
    </div>

    <h3 style="color: #2D3748; margin-top: 30px; margin-bottom: 15px;">Prescription Items</h3>

    <table class="items-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 40px;">No.</th>
                <th>Product Name</th>
                <th class="text-center" style="width: 100px;">Quantity</th>
                <th class="text-center" style="width: 80px;">Unit</th>
            </tr>
        </thead>
        <tbody>
            @forelse($prescription->items as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    <strong>{{ $item->pharmacyProduct->name }}</strong>
                    @if($item->pharmacyProduct->code)
                    <br><small style="color: #718096;">Code: {{ $item->pharmacyProduct->code }}</small>
                    @endif
                </td>
                <td class="text-center">
                    <strong>{{ $item->quantity_sended ?? $item->quantity }}</strong>
                </td>
                <td class="text-center">
                    {{ $item->quantity_by_box ? 'Box' : $item->unit }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="text-center" style="padding: 30px; color: #A0AEC0;">
                    No items in this prescription
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($prescription->items->count() > 0)
    <div style="margin-top: 20px; padding: 10px; background: #EDF2F7; border-radius: 5px;">
        <strong>Total Items:</strong> {{ $prescription->items->count() }}
        @if($prescription->status === 'confirmed')
        | <strong>Dispensed Items:</strong> {{ $prescription->dispensed_items }}
        @endif
    </div>
    @endif

    <div class="footer">
        <p>Generated on {{ now()->format('d/m/Y H:i:s') }}</p>
        <p>This is an automatically generated document from the Hospital Information System</p>
    </div>
</body>
</html>
