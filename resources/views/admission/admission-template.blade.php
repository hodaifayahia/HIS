<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admission Record - {{ $admission->file_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }

        .page {
            width: 210mm;
            height: 297mm;
            background-color: white;
            margin: 0 auto;
            padding: 20mm;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            page-break-after: always;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            border-bottom: 2px solid #1abc9c;
            padding-bottom: 15px;
        }

        .clinic-logo {
            max-width: 150px;
        }

        .clinic-info {
            text-align: center;
            flex: 1;
        }

        .clinic-info h1 {
            font-size: 20px;
            color: #16a085;
            margin-bottom: 5px;
        }

        .clinic-info p {
            font-size: 11px;
            color: #555;
            line-height: 1.5;
        }

        .file-number {
            text-align: right;
            font-weight: bold;
        }

        .file-number label {
            font-size: 11px;
            color: #666;
        }

        .file-number .value {
            font-size: 14px;
            color: #1abc9c;
            margin-top: 5px;
        }

        /* Title */
        .document-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 20px;
            text-transform: uppercase;
            border: 2px solid #1abc9c;
            padding: 10px;
        }

        /* Sections */
        .section {
            margin-bottom: 20px;
        }

        .section-title {
            background-color: #1abc9c;
            color: white;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 12px;
            border-radius: 3px;
        }

        /* Grid Layout */
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        .grid.full {
            grid-template-columns: 1fr;
        }

        .grid.three {
            grid-template-columns: 1fr 1fr 1fr;
        }

        .field {
            display: flex;
            flex-direction: column;
        }

        .field-label {
            font-size: 10px;
            font-weight: bold;
            color: #555;
            text-transform: uppercase;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }

        .field-value {
            font-size: 12px;
            color: #2c3e50;
            border-bottom: 1px solid #bdc3c7;
            padding-bottom: 5px;
            min-height: 20px;
        }

        /* Patient Information */
        .patient-info {
            background-color: #ecf0f1;
            padding: 12px;
            border-radius: 3px;
            margin-bottom: 15px;
        }

        .patient-info .grid {
            margin-bottom: 0;
        }

        /* Status Badge */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge.success {
            background-color: #27ae60;
            color: white;
        }

        .badge.info {
            background-color: #3498db;
            color: white;
        }

        .badge.warning {
            background-color: #f39c12;
            color: white;
        }

        .badge.danger {
            background-color: #e74c3c;
            color: white;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th {
            background-color: #34495e;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }

        td {
            padding: 8px;
            border-bottom: 1px solid #bdc3c7;
            font-size: 11px;
        }

        tr:nth-child(even) {
            background-color: #f5f5f5;
        }

        /* Footer */
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #bdc3c7;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            font-size: 10px;
        }

        .signature-box {
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #2c3e50;
            margin-top: 40px;
            padding-top: 5px;
        }

        /* Print Styles */
        @media print {
            body {
                padding: 0;
                background-color: white;
            }

            .page {
                box-shadow: none;
                margin: 0;
                width: 100%;
                height: 100%;
                padding: 20mm;
                page-break-after: always;
            }
        }

        /* Timeline */
        .timeline {
            position: relative;
            padding: 20px 0;
        }

        .timeline-item {
            padding-left: 30px;
            margin-bottom: 15px;
            position: relative;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 12px;
            height: 12px;
            background-color: #1abc9c;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 0 0 2px #1abc9c;
        }

        .timeline-item-date {
            font-size: 10px;
            color: #7f8c8d;
            font-weight: bold;
        }

        .timeline-item-content {
            font-size: 11px;
            color: #2c3e50;
            margin-top: 3px;
        }

        /* Remarks */
        .remarks {
            background-color: #fef5e7;
            border-left: 4px solid #f39c12;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 11px;
            line-height: 1.6;
        }

        .empty-message {
            color: #95a5a6;
            font-style: italic;
            padding: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="page">
        <!-- Header -->
        <div class="header">
            <div class="clinic-logo">
                <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100'%3E%3C/svg%3E" alt="Clinic Logo" style="width: 100%;">
            </div>
            <div class="clinic-info">
                <h1>Clinique des Oasis</h1>
                <p>Diagnostic & Healthcare Services</p>
                <p>📞 Contact: +XXX XXXXXXX</p>
                <p>📧 Email: info@cliniqueoasis.com</p>
            </div>
            <div class="file-number">
                <label>File Number</label>
                <div class="value">{{ $admission->file_number }}</div>
                @if($admission->file_number_verified)
                    <span class="badge success" style="margin-top: 5px;">✓ Verified</span>
                @endif
            </div>
        </div>

        <!-- Document Title -->
        <div class="document-title">Admission Record</div>

        <!-- Patient Information Section -->
        <div class="section">
            <div class="section-title">Patient Information</div>
            <div class="patient-info">
                <div class="grid">
                    <div class="field">
                        <span class="field-label">Full Name</span>
                        <span class="field-value">{{ $admission->patient->Firstname ?? $admission->patient->first_name ?? '' }} {{ $admission->patient->Lastname ?? $admission->patient->last_name ?? '' }}</span>
                    </div>
                    <div class="field">
                        <span class="field-label">Patient ID</span>
                        <span class="field-value">{{ $admission->patient_id }}</span>
                    </div>
                </div>
                <div class="grid">
                    <div class="field">
                        <span class="field-label">Phone</span>
                        <span class="field-value">{{ $admission->patient->phone ?? 'N/A' }}</span>
                    </div>
                    <div class="field">
                        <span class="field-label">Social Security Number</span>
                        <span class="field-value">{{ $admission->social_security_num ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admission Details Section -->
        <div class="section">
            <div class="section-title">Admission Details</div>
            <div class="grid">
                <div class="field">
                    <span class="field-label">Admission Type</span>
                    <span class="field-value">
                        <span class="badge {{ $admission->type === 'surgery' ? 'warning' : 'info' }}">
                            {{ ucfirst($admission->type) }}
                        </span>
                    </span>
                </div>
                <div class="field">
                    <span class="field-label">Status</span>
                    <span class="field-value">
                        <span class="badge {{ $admission->status === 'admitted' ? 'info' : ($admission->status === 'ready_for_discharge' ? 'success' : 'warning') }}">
                            {{ ucfirst(str_replace('_', ' ', $admission->status)) }}
                        </span>
                    </span>
                </div>
            </div>
            <div class="grid">
                <div class="field">
                    <span class="field-label">Admission Date</span>
                    <span class="field-value">{{ $admission->admitted_at?->format('d/m/Y H:i') ?? 'N/A' }}</span>
                </div>
                <div class="field">
                    <span class="field-label">Discharge Date</span>
                    <span class="field-value">{{ $admission->discharged_at?->format('d/m/Y H:i') ?? 'Pending' }}</span>
                </div>
            </div>
            <div class="grid">
                <div class="field">
                    <span class="field-label">Duration</span>
                    <span class="field-value">
                        @if($admission->admitted_at)
                            {{ $admission->admitted_at->diffInDays($admission->discharged_at ?? now()) }} day(s)
                        @else
                            N/A
                        @endif
                    </span>
                </div>
                <div class="field">
                    <span class="field-label">Initial Prestation</span>
                    <span class="field-value">{{ $admission->initialPrestation->name ?? 'N/A' }}</span>
                </div>
            </div>
        </div>

        <!-- Medical Details Section -->
        <div class="section">
            <div class="section-title">Medical Details</div>
            <div class="grid">
                <div class="field">
                    <span class="field-label">Doctor</span>
                    <span class="field-value">{{ $admission->doctor->user->name ?? 'N/A' }}</span>
                </div>
                <div class="field">
                    <span class="field-label">Company/Insurance</span>
                    <span class="field-value">{{ $admission->company->name ?? 'N/A' }}</span>
                </div>
            </div>
            <div class="grid">
                <div class="field">
                    <span class="field-label">Companion</span>
                    <span class="field-value">{{ $admission->companion->Firstname ?? $admission->companion->first_name ?? '' }} {{ $admission->companion->Lastname ?? $admission->companion->last_name ?? '' }}</span>
                </div>
                <div class="field">
                    <span class="field-label">Relation Type</span>
                    <span class="field-value">{{ ucfirst(str_replace('_', ' ', $admission->relation_type)) ?? 'N/A' }}</span>
                </div>
            </div>
        </div>

        <!-- Reason for Admission -->
        @if($admission->reason_for_admission)
            <div class="section">
                <div class="section-title">Reason for Admission</div>
                <div class="remarks">
                    {{ $admission->reason_for_admission }}
                </div>
            </div>
        @endif

        <!-- Observation -->
        @if($admission->observation)
            <div class="section">
                <div class="section-title">Observations</div>
                <div class="remarks">
                    {{ $admission->observation }}
                </div>
            </div>
        @endif

        <!-- Treatments Section -->
        @if($admission->treatments && $admission->treatments->count() > 0)
            <div class="section">
                <div class="section-title">Treatment Records</div>
                <table>
                    <thead>
                        <tr>
                            <th>Entered At</th>
                            <th>Exited At</th>
                            <th>Duration</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($admission->treatments as $treatment)
                            <tr>
                                <td>{{ $treatment->entered_at?->format('d/m/Y H:i') }}</td>
                                <td>{{ $treatment->exited_at?->format('d/m/Y H:i') ?? 'In Progress' }}</td>
                                <td>
                                    @if($treatment->duration_minutes)
                                        {{ floor($treatment->duration_minutes / 60) }}h {{ $treatment->duration_minutes % 60 }}m
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $treatment->notes ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <div class="signature-box">
                <p><strong>Doctor's Signature</strong></p>
                <div class="signature-line"></div>
            </div>
            <div class="signature-box">
                <p><strong>Patient's Signature</strong></p>
                <div class="signature-line"></div>
            </div>
            <div class="signature-box">
                <p style="margin-bottom: 30px;"><strong>Clinic's Stamp</strong></p>
            </div>
        </div>

        <!-- Print Info -->
        <div style="text-align: center; font-size: 9px; color: #95a5a6; margin-top: 20px;">
            Printed on: {{ now()->format('d/m/Y H:i') }} | Page 1
        </div>
    </div>
</body>
</html>
