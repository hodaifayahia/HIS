<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Bon de Commande PCH – {{ $order->bonCommendCode ?? 'DF_22-PH_00027/25' }}</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Times New Roman', Times, serif;
      font-size: 14px;
      line-height: 1.4;
      color: #000;
      background: white;
      padding: 20px;
    }
    
    .page-container {
      max-width: 800px;
      margin: 0 auto;
      background: white;
    }
    
    /* Header Section - Republic Header */
    .republic-header {
      text-align: center;
      margin-bottom: 20px;
      font-size: 13px;
      line-height: 1.3;
    }
    
    .republic-header .arabic {
      font-size: 25px;
      font-weight: bold;
      margin-bottom: 3px;
      font-family: 'Traditional Arabic', 'Arial', sans-serif;
      direction: rtl;
    }
    
    .republic-header .french {
      font-size: 25px;
      font-style: italic;
      margin-bottom: 8px;
    }
    
    .ministry {
      font-size: 25px;
      margin-top: 5px;
    }
    
    /* Clinic Section - No border */
    .clinic-section {
      margin-bottom: 25px;
      position: relative;
      text-align: center;
    }
    
    .clinic-header {
      text-align: center;
      margin: 0 auto;
      max-width: 400px;
    }
    
    .clinic-name {
      font-size: 20px;
      font-weight: bold;
      letter-spacing: 2px;
      margin-bottom: 5px;
    }
    
    .clinic-subtitle {
      font-size: 18px;
      margin-bottom: 5px;
    }
    
    .pch-text {
      font-size: 16px;
      font-weight: bold;
      margin-bottom: 5px;
    }
    
    .reference-number {
      position: absolute;
      right: 0;
      top: 0;
      font-size: 13px;
      font-weight: bold;
      padding: 5px;
      border: 1px solid #000;
    }
    
    /* Service Info */
    .service-info {
      margin-bottom: 15px;
      font-size: 13px;
    }
    
    .service-info div {
      margin-bottom: 3px;
    }
    
    /* Title with Border */
    .document-title-container {
      text-align: center;
      margin: 25px 0;
    }
    
    .document-title {
      display: inline-block;
      font-size: 18px;
      font-weight: bold;
      letter-spacing: 3px;
      padding: 10px 20px;
      border: 3px double #000;
      box-shadow: 2px 2px 0px rgba(0,0,0,0.1);
    }
    
    /* Alternative bordered title styles - Choose one */
    
    /* Style Option 2: Simple Box */
    .document-title-simple {
      display: inline-block;
      font-size: 18px;
      font-weight: bold;
      letter-spacing: 3px;
      padding: 12px 25px;
      border: 2px solid #000;
      background-color: #f9f9f9;
    }
    
    /* Style Option 3: Rounded Border */
    .document-title-rounded {
      display: inline-block;
      font-size: 18px;
      font-weight: bold;
      letter-spacing: 3px;
      padding: 10px 25px;
      border: 2px solid #000;
      border-radius: 5px;
    }
    
    /* Style Option 4: Full Width Box */
    .document-title-full {
      font-size: 18px;
      font-weight: bold;
      letter-spacing: 3px;
      padding: 12px 0;
      border-top: 2px solid #000;
      border-bottom: 2px solid #000;
      background-color: #f5f5f5;
      text-align: center;
      margin: 25px 0;
    }
    
    /* Main Table */
    .main-table {
      width: 100%;
      border-collapse: collapse;
      margin: 20px 0;
      font-size: 12px;
    }
    
    .main-table thead {
      background-color: #f0f0f0;
    }
    
    .main-table th {
      border: 1px solid #000;
      padding: 8px 5px;
      text-align: center;
      font-weight: bold;
      font-size: 11px;
      vertical-align: middle;
    }
    
    .main-table td {
      border: 1px solid #000;
      padding: 6px 5px;
      vertical-align: middle;
    }
    
    .main-table .code-cell {
      text-align: center;
      font-weight: bold;
      width: 8%;
    }
    
    .main-table .designation-cell {
      text-align: left;
      padding-left: 8px;
      width: 42%;
    }
    
    .main-table .unit-cell {
      text-align: center;
      width: 15%;
    }
    
    .main-table .stock-cell {
      text-align: center;
      width: 15%;
    }
    
    .main-table .quantity-cell {
      text-align: center;
      font-weight: bold;
      width: 20%;
    }
    
    .main-table tbody tr:hover {
      background-color: #f9f9f9;
    }
    
    /* Signatures Section */
    .signatures-section {
      margin-top: 50px;
      display: table;
      width: 100%;
    }
    
    .signature-box {
      display: table-cell;
      width: 50%;
      text-align: center;
      vertical-align: top;
      padding: 0 20px;
    }
    
    .signature-title {
      font-size: 13px;
      font-weight: bold;
      text-transform: uppercase;
      margin-bottom: 50px;
      text-decoration: underline;
    }
    
    /* Footer */
    .footer-section {
      margin-top: 30px;
      padding-top: 20px;
      border-top: 1px solid #ccc;
      text-align: center;
      font-size: 11px;
      color: #666;
    }
    
    /* Print Styles */
    @media print {
      body {
        padding: 10px;
      }
      
      .main-table {
        page-break-inside: avoid;
      }
      
      .signatures-section {
        page-break-inside: avoid;
      }
    }
  </style>
</head>
<body>
  <div class="page-container">
    <!-- Republic Header -->
    <div class="republic-header">
      <div class="arabic">الجمهورية الجزائرية الديمقراطية الشعبية</div>
      <div class="french">République Algérienne Démocratique et Populaire</div>
      <div class="ministry">Ministère de la Santé, de la Population et de la Réforme Hospitalière</div>
    </div>
    
    <!-- Clinic Section - Without Border -->
    <div class="clinic-section">
      @if($order->reference)
        <div class="reference-number">{{ $order->reference }}</div>
      @else
        <div class="reference-number">DF_22-PH_00027/25</div>
      @endif
      
      <div class="clinic-header">
        <div class="clinic-name">CLINIQUE DES OASIS</div>
        <div class="pch-text">P.C.H BISKRA</div>
      </div>
    </div>
    
    <!-- Service Info -->
    <div class="service-info">
      <div><strong>Service:</strong> {{ $order->serviceDemand->service->name ?? 'Service pharmacie' }}</div>
      <div><strong>Date:</strong> {{ $order->created_at ? $order->created_at->format('d/m/Y') : '14/10/2025' }}</div>
    </div>
    
    <!-- Document Title with Border (Option 1: Double Border) -->
    <div class="document-title-container">
      <h1 class="document-title">BON DE COMMANDE</h1>
    </div>
    
    <!-- Alternative: Use one of these instead -->
    <!-- 
    Option 2: Simple Box
    <div class="document-title-container">
      <h1 class="document-title-simple">BON DE COMMANDE</h1>
    </div>
    
    Option 3: Rounded Border
    <div class="document-title-container">
      <h1 class="document-title-rounded">BON DE COMMANDE</h1>
    </div>
    
    Option 4: Full Width
    <h1 class="document-title-full">BON DE COMMANDE</h1>
    -->
    
    <!-- Main Table -->
    <table class="main-table">
      <thead>
          <tr>
          <th class="code-cell">Code</th>
          <th class="designation-cell">Désignation</th>
          <th class="unit-cell">Unité de<br>compte</th>
          <th class="stock-cell">Quantité<br>en stock</th>
          <th class="stock-cell">Inventaire<br>pharmacie</th>
          <th class="quantity-cell">Quantité<br>demandée</th>
        </tr>
      </thead>
      <tbody>
        @if(isset($order->items) && count($order->items) > 0)
          @foreach($order->items as $item)
            <tr>
              <td class="code-cell">
                {{ $item->product->pch_code ?? $item->product->product_code ?? '' }}
              </td>
              <td class="designation-cell">
                {{ $item->product->name ?? $item->product_name ?? '' }}
                @if(isset($item->product->commercial_name))
                  ({{ $item->product->commercial_name }})
                @endif
              </td>
              <td class="unit-cell">
                {{ $item->unit ?? 'Unité(s)' }}
              </td>
              @php
                // Prefer pharmacy inventory totals passed from controller, then item-level stock, then product inventories
                $keyCandidates = [];
                if (isset($item->product)) {
                    $keyCandidates[] = $item->product->barcode ?? null;
                    $keyCandidates[] = $item->product->sku ?? null;
                    $keyCandidates[] = $item->product->code_pch ?? null;
                    $keyCandidates[] = $item->product->pch_code ?? null;
                    $keyCandidates[] = $item->product->product_code ?? null;
                }

                $phStock = 0;
                if (!empty($pharmacyStockMap) && !empty($keyCandidates)) {
                    foreach ($keyCandidates as $kc) {
                        if ($kc && array_key_exists($kc, $pharmacyStockMap)) {
                            $phStock = $pharmacyStockMap[$kc];
                            break;
                        }
                    }
                }

                $stockQty = $phStock ?: (isset($item->stock_quantity) ? $item->stock_quantity : (
                    isset($item->product) && isset($item->product->inventories) ? $item->product->inventories->sum('quantity') : 0
                ));
              @endphp
              <td class="stock-cell">
                {{ $stockQty ?? 0 }}
              </td>
              <td class="stock-cell">
                @php
                  $invDetails = [];
                  if (isset($pharmacyInventoryDetailsMap) && !empty($keyCandidates)) {
                      foreach ($keyCandidates as $kc) {
                          if ($kc && array_key_exists($kc, $pharmacyInventoryDetailsMap)) {
                              $invDetails = $pharmacyInventoryDetailsMap[$kc];
                              break;
                          }
                      }
                  }
                @endphp

                @if(!empty($invDetails))
                  {{-- Show total and list batches (batch_number: qty, expiry) --}}
                  <div><strong>{{ array_sum(array_column($invDetails, 'quantity')) }}</strong></div>
                  <small style="display:block; margin-top:4px; text-align:left;">
                    @foreach($invDetails as $d)
                      @if(isset($d['batch_number']) && $d['batch_number'])
                        {{ $d['batch_number'] }}: {{ $d['quantity'] }} @if($d['expiry_date']) ({{ $d['expiry_date'] }})@endif
                      @else
                        {{ $d['id'] }}: {{ $d['quantity'] }} @if($d['expiry_date']) ({{ $d['expiry_date'] }})@endif
                      @endif
                      @if(!($loop->last)) / @endif
                    @endforeach
                  </small>
                @else
                  <span>—</span>
                @endif
              </td>
              <td class="quantity-cell">
                {{ $item->quantity_desired ?? $item->quantity }}
              </td>
            </tr>
          @endforeach
        @else
          <!-- Default items from PDF if no order items -->
          <tr>
            <td class="code-cell">5774</td>
            <td class="designation-cell">TROPICAMIDE COLLYRE 50MG (Mydriaticum)</td>
            <td class="unit-cell">Unité(s)</td>
            <td class="stock-cell">0</td>
            <td class="quantity-cell">100</td>
          </tr>
          <tr>
            <td class="code-cell">5718</td>
            <td class="designation-cell">TRIAMCINOLONE PDE OPHT 0,1% T/3A5GR</td>
            <td class="unit-cell">Unité(s)</td>
            <td class="stock-cell">0</td>
            <td class="quantity-cell">100</td>
          </tr>
          <tr>
            <td class="code-cell"></td>
            <td class="designation-cell">FRAKIDEX Pde Opht 5Mg</td>
            <td class="unit-cell">Unité(s)</td>
            <td class="stock-cell">0</td>
            <td class="quantity-cell">100</td>
          </tr>
          <tr>
            <td class="code-cell"></td>
            <td class="designation-cell">STERDEX Tube</td>
            <td class="unit-cell">Unité(s)</td>
            <td class="stock-cell">0</td>
            <td class="quantity-cell">100</td>
          </tr>
          <tr>
            <td class="code-cell">5054</td>
            <td class="designation-cell">Mitomycine 10MG</td>
            <td class="unit-cell">Unité(s)</td>
            <td class="stock-cell">0</td>
            <td class="quantity-cell">10</td>
          </tr>
          <tr>
            <td class="code-cell">5117</td>
            <td class="designation-cell">Cefazoline 1G</td>
            <td class="unit-cell">Unité(s)</td>
            <td class="stock-cell">0</td>
            <td class="quantity-cell">100</td>
          </tr>
          <tr>
            <td class="code-cell">5181</td>
            <td class="designation-cell">Éphédrine chlorhydrate 30MG</td>
            <td class="unit-cell">Unité(s)</td>
            <td class="stock-cell">0</td>
            <td class="quantity-cell">10</td>
          </tr>
          <tr>
            <td class="code-cell">6162</td>
            <td class="designation-cell">ETAMSYLATE SOL.INJ 250 MG</td>
            <td class="unit-cell">Unité(s)</td>
            <td class="stock-cell">0</td>
            <td class="quantity-cell">50</td>
          </tr>
          <tr>
            <td class="code-cell">6194</td>
            <td class="designation-cell">Atropine sulfate 0,25MG/ML</td>
            <td class="unit-cell">Unité(s)</td>
            <td class="stock-cell">0</td>
            <td class="quantity-cell">10</td>
          </tr>
          <tr>
            <td class="code-cell">6394</td>
            <td class="designation-cell">FLUORESCEINE INJ 10% amp 5ml</td>
            <td class="unit-cell">Unité(s)</td>
            <td class="stock-cell">0</td>
            <td class="quantity-cell">10</td>
          </tr>
          <tr>
            <td class="code-cell">6701</td>
            <td class="designation-cell">ADRENALINE SOL.INJ IV 1MG/ML</td>
            <td class="unit-cell">Unité(s)</td>
            <td class="stock-cell">0</td>
            <td class="quantity-cell">150</td>
          </tr>
        @endif
      </tbody>
    </table>
    
    <!-- Signatures Section -->
    <div class="signatures-section">
      <div class="signature-box">
        <div class="signature-title">SERVICE DE LA PHARMACIE</div>
      </div>
      <div class="signature-box">
        <div class="signature-title">LE DIRECTEUR</div>
      </div>
    </div>
  </div>
</body>
</html>
