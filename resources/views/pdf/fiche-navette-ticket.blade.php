<!DOCTYPE html>
<html>
<head>
    <title>Fiche Navette Ticket - {{ $fiche->patient->Firstname }} {{ $fiche->patient->Lastname }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
        }
        
        .ticket {
            width: 80mm;
            padding: 10mm;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #2563eb;
        }
        
        .header h1 {
            font-size: 18px;
            color: #1e40af;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .header .ticket-number {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin: 8px 0;
        }
        
        .section {
            margin-bottom: 12px;
            padding: 8px;
            background: #f8fafc;
            border-radius: 4px;
        }
        
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 6px;
            text-transform: uppercase;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 3px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            border-bottom: 1px dashed #e2e8f0;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #64748b;
            font-size: 10px;
        }
        
        .info-value {
            font-weight: 500;
            color: #0f172a;
            text-align: right;
        }
        
        .prestations-list {
            margin-top: 8px;
        }
        
        .prestation-item {
            padding: 8px;
            margin-bottom: 8px;
            background: white;
            border-left: 3px solid #3b82f6;
            border-radius: 3px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .prestation-name {
            font-weight: bold;
            color: #1e40af;
            font-size: 11px;
            margin-bottom: 4px;
        }
        
        .prestation-details {
            font-size: 9px;
            color: #64748b;
            margin-top: 3px;
        }
        
        .prestation-time {
            display: flex;
            justify-content: space-between;
            margin-top: 4px;
            font-size: 9px;
        }
        
        .time-badge {
            background: #dbeafe;
            color: #1e40af;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: 600;
        }
        
        .qr-section {
            text-align: center;
            margin: 15px 0;
            padding: 10px;
            background: white;
            border: 2px dashed #cbd5e1;
            border-radius: 4px;
        }
        
        .qr-code {
            margin: 10px 0;
        }
        
        .qr-code img {
            max-width: 120px;
            height: auto;
        }
        
        .qr-label {
            font-size: 9px;
            color: #64748b;
            margin-top: 5px;
        }
        
        .footer {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 2px solid #e2e8f0;
            text-align: center;
            font-size: 9px;
            color: #64748b;
        }
        
        .timestamp {
            margin-top: 8px;
            font-weight: 600;
        }
        
        .important-notice {
            background: #fef3c7;
            border-left: 3px solid #f59e0b;
            padding: 8px;
            margin: 10px 0;
            font-size: 9px;
            color: #92400e;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            .ticket {
                width: 80mm;
                padding: 5mm;
            }
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: 600;
            background: #10b981;
            color: white;
        }
    </style>
</head>
<body>
    <div class="ticket">
        <!-- Header -->
        <div class="header">
            <h1>🏥 FICHE NAVETTE</h1>
            <div class="ticket-number">#{{ $fiche->id }}</div>
            <div style="font-size: 10px; color: #64748b;">
                {{ \Carbon\Carbon::parse($fiche->fiche_date)->format('d/m/Y H:i') }}
            </div>
        </div>

        <!-- Patient Information -->
        <div class="section">
            <div class="section-title">👤 Informations Patient</div>
            <div class="info-row">
                <span class="info-label">Nom Complet:</span>
                <span class="info-value">{{ $fiche->patient->Firstname }} {{ $fiche->patient->Lastname }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Téléphone:</span>
                <span class="info-value">{{ $fiche->patient->Phone ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Patient ID:</span>
                <span class="info-value">#{{ $fiche->patient->id }}</span>
            </div>
        </div>

        <!-- Arrival Information -->
        <div class="section">
            <div class="section-title">⏰ Informations d'Arrivée</div>
            <div class="info-row">
                <span class="info-label">Date Fiche:</span>
                <span class="info-value">{{ \Carbon\Carbon::parse($fiche->fiche_date)->format('d/m/Y') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Heure Arrivée:</span>
                <span class="info-value">
                    @if($fiche->arrival_time)
                        {{ \Carbon\Carbon::parse($fiche->arrival_time)->format('H:i') }}
                    @else
                        {{ \Carbon\Carbon::now()->format('H:i') }}
                    @endif
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Statut:</span>
                <span class="info-value">
                    <span class="status-badge">ARRIVÉ</span>
                </span>
            </div>
        </div>

        <!-- Prestations List -->
        <div class="section">
            <div class="section-title">📋 Prestations ({{ count($items) }})</div>
            <div class="prestations-list">
                @foreach($items as $item)
                    <div class="prestation-item">
                        <div class="prestation-name">
                            {{ $item->prestation->name ?? 'N/A' }}
                        </div>
                        
                        @if($item->prestation)
                            <div class="prestation-details">
                                Code: {{ $item->prestation->internal_code ?? 'N/A' }}
                            </div>
                        @endif

                        <div class="prestation-time">
                            @if($item->appointment_date)
                                <div>
                                    <span class="info-label">RDV:</span>
                                    <span class="time-badge">
                                        {{ \Carbon\Carbon::parse($item->appointment_date)->format('d/m/Y H:i') }}
                                    </span>
                                </div>
                            @else
                                <div>
                                    <span class="info-label">RDV:</span>
                                    <span style="color: #94a3b8;">Non programmé</span>
                                </div>
                            @endif
                        </div>


                    </div>
                @endforeach
            </div>
        </div>

        <!-- Single QR Code for the entire fiche -->
        <div class="qr-section">
            <div class="section-title">📱 Code QR - Fiche Navette</div>
            <div class="qr-code">
                {!! $ficheQrCode !!}
            </div>
            <div class="qr-label">
                FICHE-{{ $fiche->id }}-{{ $fiche->patient->id }}-{{ \Carbon\Carbon::parse($fiche->fiche_date)->format('Ymd') }}
            </div>
            <div style="font-size: 8px; color: #64748b; margin-top: 5px;">
                Scannez ce code pour toutes les prestations
            </div>
        </div>

        <!-- Important Notice -->
        <div class="important-notice">
            ⚠️ Veuillez présenter ce ticket à chaque service
        </div>

        <!-- Footer -->
        <div class="footer">
            <div>Imprimé par: <strong>{{ $printedBy }}</strong></div>
            <div class="timestamp">
                {{ \Carbon\Carbon::now()->format('d/m/Y à H:i:s') }}
            </div>
            <div style="margin-top: 8px; font-style: italic;">
                Merci de votre confiance
            </div>
        </div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
