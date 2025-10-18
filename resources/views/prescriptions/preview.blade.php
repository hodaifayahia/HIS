<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<style>
     body {
        font-family: Arial, sans-serif;
        width: 170mm;
        margin: 0 auto;
        position: relative;
        min-height: 220mm;
        line-height: 1.4;
    }
    
    .clinic-header {
        height: 30mm;
        text-align: center;
        margin-bottom: 5mm;
    }
    
    .clinic-name {
        font-size: 24pt;
        font-weight: bold;
        margin: 0;
    }
    
    .clinic-subtitle {
        font-size: 14pt;
        margin-top: 2mm;
        border-bottom: 1px dashed #000;
        padding-bottom: 3mm;
    }
    
    .doctor-date-container {
        display: table;
        width: calc(100% - 25mm);
        margin: 5mm 0 0 25mm;
    }
    
    .doctor-section, .date-section {
        display: table-cell;
    }
    
    .date-section {
        text-align: right;
    }
    
    .patient-info {
        display: table;
        width: calc(100% - 25mm);
        margin: 7mm 0 0 25mm;
    }
    
    .patient-field {
        display: table-cell;
        width: auto;
        padding-right: 10mm;
    }
    
    .patient-field.firstname {
        padding-left: 20mm;
    }
    
    .prescription-title {
        text-align: center;
        font-weight: bold;
        font-size: 16pt;
        margin: 10mm 0;
        text-decoration: underline;
    }
    
    .medications {
        list-style: none;
        padding: 0;
        margin: 0 0 0 0;
    }
    
    .medication-item {
        margin-bottom: 8px;
        display: flex;
        flex-direction: column;
    }
    
    /* FIXED: Proper flexbox layout for medication line */
    .medication-line {
        width: 100%;
        margin-bottom: 5px;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 15px; /* Increased gap for better separation */
    }

    .medication-name {
        font-weight: bold;
        flex: 1; /* Take available space */
        text-align: left;
        margin: 0; /* Remove negative margin */
        min-width: 0; /* Allow shrinking if needed */
    }

    .medication-qsp {
        flex-shrink: 0; /* Don't shrink */
        text-align: right;
        white-space: nowrap; /* Prevent line breaks */
        margin-left: 200px; /* Push to right */
    }
    
    .dosage-item {
        flex: 1;
        min-width: 80px;
        text-align: left;
        font-size: 0.95em;
    }
    
    .period-info {
        flex: 1;
        min-width: 100px;
        font-style: italic;
        color: #555;
        font-size: 0.7em;
    }
    
    .instructions {
        flex: 3;
        min-width: 150px;
        color: #666;
        font-size: 0.9em;
    }
    
    .medication-instructions {
        margin-left: 25mm;
        font-size: 0.85em;
        color: #444;
        margin-top: 2px;
    }
      
    .barcode-container {
        position: absolute;
        top: -5mm;
        right: -5mm;
        width: 40mm;
        height: 15mm;
    }
    
    .barcode-container img {
        max-width: 100%;
        max-height: 100%;
    }
    
    .clinic-header {
        height: 30mm;
        text-align: center;
        margin-bottom: 5mm;
    }
    
    
    .signature-area {
        margin-top: 15mm;
        text-align: right;
        margin-right: 25mm;
    }
    
        .clinic-header {
            height: auto;
            text-align: center;
            margin-bottom: 8mm;
            padding: 5mm 0;
            border-bottom: 2px solid #3498db;
        }

        .header-image {
            max-width: 100%;
            max-height: 40mm;
            width: auto;
            height: auto;
            object-fit: contain;
            margin-bottom: 3mm;
        }
    
    .signature-line {
        border-bottom: 1px solid #000;
        width: 80mm;
        margin-left: auto;
        margin-top: 10mm;
    }
    
    .footer {
        position: absolute;
        bottom: 0;
        width: 100%;
        text-align: center;
        font-size: 10pt;
        padding-bottom: 5mm;
    }
    
    .additional-notes {
        margin: 10mm 25mm 0 25mm;
        padding-top: 5mm;
        border-top: 1px dashed #ccc;
    }

    @media print {
        body {
            width: 100%;
            min-height: 100%;
        }
        .medication-line {
            page-break-inside: avoid;
        }
    }
</style>
</head>
<body>
    @if(isset($codebar))
    <div class="barcode-container">
        <img src="data:image/png;base64,{{ $codebar }}" alt="Prescription Barcode">
    </div>
    @endif
   
    <!-- Médecin & Date -->
    <div class="doctor-date-container">
     <div class="clinic-header">
        <div>
            <img src="{{ storage_path('app/public/ENTETE.png') }}" class="header-image" alt="En-tête de la clinique">
        </div>
    </div>
        <div class="doctor-section">
            <strong>Doctor: </strong> {{ $doctor_name }}
        </div>
        <div class="date-section">
            <strong>date </strong> {{ $current_date ?? " " }}
        </div>
    </div>
    
    <!-- Informations patient -->
    <div class="patient-info">
        <div class="patient-field">
            <strong>lastname : </strong> {{ $patient_last_name }}
        </div>
        <div class="patient-field firstname">
            <strong> first name :</strong> {{ $patient_first_name }}
        </div>
        <div class="patient-field">
            <strong> </strong> 
            @if(isset($patient_age))
                {{ $patient_age }} {{ $age_unit ?? 'an(s)' }}
            @endif
        </div>
    </div>
    
    <!-- Titre de l'ordonnance -->
    <div class="prescription-title">Ordonnance Médicale</div>
    
    <ul class="medications">
    @forelse($medications as $index => $medication)
        <li class="medication-item">
            <div class="medication-line">
                <span class="medication-name">
                    {{ $medication->medication->designation }}
                </span>
                <span class="medication-qsp">
                    QSP {{ $medication->period_intakes }} {{ $medication->frequency_period }}(s)
                </span>
            </div>
            @if($medication->frequency_period || $medication->period_intakes)
                <span class="period-info">
                    <span class="dosage-item">{{ $medication->num_times }}×/{{ $medication->frequency }}</span>
                    {{ $medication->timing_preference }}
                </span>
            @endif
            @if($medication->description)
                <span class="instructions">{{ $medication->description }}</span>
            @endif
        </li>
    @empty
        <p style="text-align: center; color: #777;">Aucun médicament n'a été prescrit pour cette ordonnance.</p>
    @endforelse
</ul>
</body>
</html>