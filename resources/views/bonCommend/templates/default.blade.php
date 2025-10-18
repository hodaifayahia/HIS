<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Bon de Commande – {{ $order->bonCommendCode }}</title>
  <style>
    /* Reset and Base Styles */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: #2d3748;
      line-height: 1.6;
      background: #ffffff;
      padding: 20px;
    }
    
    /* Container */
    .container {
      max-width: 800px;
      margin: 0 auto;
      background: white;
      padding: 30px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    /* Header Styles */
    .header {
      border-bottom: 3px solid #3182ce;
      padding-bottom: 20px;
      margin-bottom: 30px;
    }
    
    .header-top {
      display: table;
      width: 100%;
      margin-bottom: 20px;
    }
    
    .logo-section {
      display: table-cell;
      vertical-align: top;
      width: 60%;
    }
    
    .order-info {
      display: table-cell;
      vertical-align: top;
      width: 40%;
      text-align: right;
    }
    
    .clinic-name {
      font-size: 24px;
      font-weight: bold;
      color: #1a365d;
      margin-bottom: 5px;
      letter-spacing: -0.5px;
    }
    
    .clinic-subtitle {
      font-size: 14px;
      color: #4a5568;
      font-weight: 500;
    }
    
    .order-badge {
      display: inline-block;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 8px 16px;
      border-radius: 6px;
      font-weight: bold;
      font-size: 16px;
      margin-bottom: 10px;
    }
    
    .order-date {
      font-size: 14px;
      color: #4a5568;
      margin-top: 5px;
    }
    
    .order-status {
      display: inline-block;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      margin-top: 8px;
    }
    
    .status-draft { background: #fef3c7; color: #92400e; }
    .status-sent { background: #dbeafe; color: #1e40af; }
    .status-confirmed { background: #d1fae5; color: #065f46; }
    .status-cancelled { background: #fee2e2; color: #991b1b; }
    
    /* Contact Info Bar */
    .contact-bar {
      background: #f7fafc;
      border-left: 4px solid #3182ce;
      padding: 12px 20px;
      margin-bottom: 25px;
      border-radius: 0 4px 4px 0;
    }
    
    .contact-bar table {
      width: 100%;
    }
    
    .contact-bar td {
      padding: 2px 10px;
      font-size: 13px;
    }
    
    .contact-icon {
      color: #3182ce;
      font-weight: bold;
      margin-right: 5px;
    }
    
    /* Info Sections */
    .info-grid {
      display: table;
      width: 100%;
      margin-bottom: 30px;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      overflow: hidden;
    }
    
    .info-row {
      display: table-row;
    }
    
    .info-col {
      display: table-cell;
      width: 50%;
      padding: 20px;
      vertical-align: top;
      background: #f8fafc;
    }
    
    .info-col:first-child {
      border-right: 1px solid #e2e8f0;
    }
    
    .info-title {
      font-size: 14px;
      font-weight: 600;
      color: #1a365d;
      margin-bottom: 12px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      display: flex;
      align-items: center;
    }
    
    .info-title::before {
      content: '';
      display: inline-block;
      width: 4px;
      height: 16px;
      background: #3182ce;
      margin-right: 8px;
    }
    
    .info-item {
      margin-bottom: 10px;
    }
    
    .info-label {
      font-size: 11px;
      color: #718096;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 2px;
    }
    
    .info-value {
      font-size: 14px;
      color: #2d3748;
      font-weight: 500;
    }
    
    /* Items Table */
    .items-section {
      margin-bottom: 30px;
    }
    
    .section-title {
      font-size: 16px;
      font-weight: 600;
      color: #1a365d;
      margin-bottom: 15px;
      padding-bottom: 8px;
      border-bottom: 2px solid #e2e8f0;
    }
    
    .items-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      overflow: hidden;
    }
    
    .items-table thead {
      background: linear-gradient(to bottom, #f7fafc, #edf2f7);
    }
    
    .items-table th {
      padding: 12px;
      text-align: left;
      font-size: 12px;
      font-weight: 600;
      color: #4a5568;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      border-bottom: 2px solid #e2e8f0;
    }
    
    .items-table tbody tr {
      transition: background-color 0.2s;
    }
    
    .items-table tbody tr:nth-child(even) {
      background: #f8fafc;
    }
    
    .items-table tbody tr:hover {
      background: #edf2f7;
    }
    
    .items-table td {
      padding: 14px 12px;
      font-size: 13px;
      border-bottom: 1px solid #e2e8f0;
    }
    
    .items-table tbody tr:last-child td {
      border-bottom: none;
    }
    
    .item-number {
      font-weight: 600;
      color: #718096;
    }
    
    .product-code {
      font-family: 'Courier New', monospace;
      font-size: 11px;
      color: #718096;
      background: #f7fafc;
      padding: 2px 6px;
      border-radius: 3px;
      display: inline-block;
      margin-bottom: 4px;
    }
    
    .product-name {
      font-weight: 600;
      color: #2d3748;
      margin-bottom: 2px;
    }
    
    .product-desc {
      font-size: 11px;
      color: #718096;
      line-height: 1.4;
    }
    
    .quantity-badge {
      background: #edf2f7;
      color: #2d3748;
      padding: 4px 10px;
      border-radius: 4px;
      font-weight: 600;
      display: inline-block;
    }
    
    .price {
      font-weight: 600;
      color: #2d3748;
      white-space: nowrap;
    }
    
    .total-price {
      font-weight: 700;
      color: #1a365d;
      font-size: 14px;
      white-space: nowrap;
    }
    
    .text-center {
      text-align: center;
    }
    
    .text-right {
      text-align: right;
    }
    
    /* Totals Section */
    .totals-section {
      margin-top: 20px;
      border: 2px solid #e2e8f0;
      border-radius: 8px;
      padding: 20px;
      background: linear-gradient(to bottom, #ffffff, #f8fafc);
    }
    
    .totals-table {
      width: 100%;
      max-width: 350px;
      margin-left: auto;
    }
    
    .totals-table tr {
      line-height: 1.8;
    }
    
    .totals-table td {
      padding: 8px;
      font-size: 14px;
    }
    
    .total-label {
      text-align: right;
      color: #4a5568;
      font-weight: 500;
    }
    
    .total-value {
      text-align: right;
      color: #2d3748;
      font-weight: 600;
      min-width: 120px;
    }
    
    .grand-total td {
      padding-top: 12px;
      border-top: 2px solid #3182ce;
      font-size: 16px;
    }
    
    .grand-total .total-label {
      color: #1a365d;
      font-weight: 700;
    }
    
    .grand-total .total-value {
      color: #3182ce;
      font-weight: 700;
      font-size: 18px;
    }
    
    /* Signatures Section */
    .signatures-section {
      margin-top: 40px;
      padding-top: 30px;
      display: table;
      width: 100%;
    }
    
    .signature-box {
      display: table-cell;
      width: 33.33%;
      text-align: center;
      padding: 0 20px;
    }
    
    .signature-line {
      border-bottom: 2px solid #e2e8f0;
      height: 60px;
      margin-bottom: 8px;
    }
    
    .signature-label {
      font-size: 11px;
      color: #718096;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      font-weight: 600;
    }
    
    .signature-name {
      font-size: 12px;
      color: #4a5568;
      margin-top: 4px;
    }
    
    /* Footer */
    .footer {
      margin-top: 40px;
      padding-top: 20px;
      border-top: 2px solid #e2e8f0;
      text-align: center;
    }
    
    .footer-text {
      font-size: 12px;
      color: #718096;
      line-height: 1.6;
    }
    
    .footer-important {
      background: #fef3c7;
      border-left: 4px solid #f59e0b;
      padding: 10px 15px;
      margin: 20px 0;
      text-align: left;
      border-radius: 0 4px 4px 0;
    }
    
    .footer-important p {
      font-size: 11px;
      color: #92400e;
      margin: 2px 0;
    }
    
    .thank-you {
      font-size: 14px;
      color: #3182ce;
      font-weight: 600;
      margin-top: 15px;
    }
    
    /* Print Styles */
    @media print {
      body {
        padding: 0;
      }
      
      .container {
        box-shadow: none;
        border: none;
        padding: 10px;
      }
      
      .items-table tbody tr:nth-child(even) {
        background: #f8fafc !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <!-- Header Section -->
    <div class="header">
      <div class="header-top">
        <div class="logo-section">
          <div class="clinic-name">CLINIQUE DES OASIS</div>
          <div class="clinic-subtitle">Excellence Médicale & Soins de Qualité</div>
        </div>
        <div class="order-info">
          <div class="order-badge">{{ $order->bonCommendCode }}</div>
          <div class="order-date">Date: {{ $order->created_at->format('d/m/Y H:i') }}</div>
      
        </div>
      </div>
    </div>
    
    <!-- Contact Bar -->
    <div class="contact-bar">
      <table>
        <tr>
          <td><span class="contact-icon">📧</span> contact@clinique-oasis.dz</td>
          <td><span class="contact-icon">📱</span> +213 550 123 456</td>
          <td><span class="contact-icon">📍</span> 123 Avenue de la Santé, Alger 16000</td>
        </tr>
      </table>
    </div>
    
    <!-- Info Grid -->
    <div class="info-grid">
      <div class="info-row">
        <div class="info-col">
          <div class="info-title">Informations Fournisseur</div>
          
          <div class="info-item">
            <div class="info-label">Raison Sociale</div>
            <div class="info-value">{{ $order->fournisseur->company_name ?? 'Non spécifié' }}</div>
          </div>
          
          <div class="info-item">
            <div class="info-label">Personne de Contact</div>
            <div class="info-value">{{ $order->fournisseur->contact_person ?? 'Non spécifié' }}</div>
          </div>
          
          <div class="info-item">
            <div class="info-label">Téléphone</div>
            <div class="info-value">{{ $order->fournisseur->phone ?? 'Non spécifié' }}</div>
          </div>
          
          <div class="info-item">
            <div class="info-label">Email</div>
            <div class="info-value">{{ $order->fournisseur->email ?? 'Non spécifié' }}</div>
          </div>
          
          <div class="info-item">
            <div class="info-label">Adresse</div>
            <div class="info-value">{{ $order->fournisseur->address ?? 'Non spécifiée' }}</div>
          </div>
        </div>
        
        <div class="info-col">
          <div class="info-title">Détails de la Commande</div>
          
          <div class="info-item">
            <div class="info-label">Référence Interne</div>
            <div class="info-value">{{ $order->reference ?? $order->bonCommendCode }}</div>
          </div>
          
          <div class="info-item">
            <div class="info-label">Service Demandeur</div>
            <div class="info-value">{{ $order->serviceDemand->service->name ?? 'Service Général' }}</div>
          </div>
          
          <div class="info-item">
            <div class="info-label">Créé par</div>
            <div class="info-value">{{ $order->creator->name ?? 'Système' }}</div>
          </div>
          
          <div class="info-item">
            <div class="info-label">Date de Livraison Souhaitée</div>
            <div class="info-value">{{ $order->expected_delivery ? \Carbon\Carbon::parse($order->expected_delivery)->format('d/m/Y') : 'À confirmer' }}</div>
          </div>
          
          <div class="info-item">
            <div class="info-label">Conditions de Paiement</div>
            <div class="info-value">{{ $order->payment_terms ?? '30 jours nets' }}</div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Items Section -->
    <div class="items-section">
      <h2 class="section-title">📦 Articles Commandés</h2>
      
      <table class="items-table">
        <thead>
          <tr>
            <th style="width: 5%;">#</th>
            <th style="width: 35%;">Produit</th>
            <th style="width: 12%;" class="text-center">Quantité</th>
            <th style="width: 12%;" class="text-center">Qté Souhaitée</th>
          </tr>
        </thead>
        <tbody>
          @php
            $totalGeneral = 0;
          @endphp
          
          @foreach($order->items as $index => $item)
            @php
              $subtotal = ($item->quantity_desired ?? $item->quantity) * $item->price;
              $totalGeneral += $subtotal;
            @endphp
            <tr>
              <td class="text-center">
                <span class="item-number">{{ $index + 1 }}</span>
              </td>
              <td>
                @if($item->product)
                  <div class="product-code">{{ $item->product->product_code ?? 'REF-' . $item->product_id }}</div>
                  <div class="product-name">{{ $item->product->name }}</div>
                  @if($item->product->description)
                    <div class="product-desc">{{ Str::limit($item->product->description, 100) }}</div>
                  @endif
                @else
                  <div class="product-name">{{ $item->product_name ?? 'Produit #' . $item->product_id }}</div>
                @endif
              </td>
              <td class="text-center">
                <span class="quantity-badge">{{ $item->quantity }}</span>
              </td>
              <td class="text-center">
                <span class="quantity-badge" style="background: #dbeafe; color: #1e40af;">
                  {{ $item->quantity_desired ?? $item->quantity }}
                </span>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    
    <!-- Totals Section -->
    <div class="totals-section">
      <table class="totals-table">
        <tr>
          <td class="total-label">Sous-total HT :</td>
          <td class="total-value">{{ number_format($totalGeneral, 2, ',', ' ') }} DZD</td>
        </tr>
        <tr>
          <td class="total-label">TVA (19%) :</td>
          <td class="total-value">{{ number_format($totalGeneral * 0.19, 2, ',', ' ') }} DZD</td>
        </tr>
        <tr>
          <td class="total-label">Remise :</td>
          <td class="total-value">{{ number_format($order->discount ?? 0, 2, ',', ' ') }} DZD</td>
        </tr>
        <tr class="grand-total">
          <td class="total-label">TOTAL TTC :</td>
          <td class="total-value">
            {{ number_format($totalGeneral * 1.19 - ($order->discount ?? 0), 2, ',', ' ') }} DZD
          </td>
        </tr>
      </table>
    </div>
    
    <!-- Signatures Section -->
    <div class="signatures-section">
      <div class="signature-box">
        <div class="signature-line"></div>
        <div class="signature-label">Préparé par</div>
        <div class="signature-name">{{ $order->creator->name ?? '' }}</div>
      </div>
      <div class="signature-box">
        <div class="signature-line"></div>
        <div class="signature-label">Approuvé par</div>
        <div class="signature-name">Direction des Achats</div>
      </div>
      <div class="signature-box">
        <div class="signature-line"></div>
        <div class="signature-label">Cachet Fournisseur</div>
        <div class="signature-name">&nbsp;</div>
      </div>
    </div>
    
    <!-- Footer -->
    <div class="footer">
      <div class="footer-important">
        <p><strong>⚠️ Conditions Importantes :</strong></p>
        <p>• Livraison sous 48-72h pour les produits en stock</p>
        <p>• Paiement à 30 jours après réception de la facture</p>
        <p>• Toute réclamation doit être faite dans les 48h suivant la réception</p>
      </div>
      
      <div class="footer-text">
        <p>Clinique des Oasis - Établissement de Santé Agréé</p>
        <p>RC: 16/00-0123456B00 | NIF: 000016012345678 | NIS: 000016012345679</p>
        <p>📧 contact@clinique-oasis.dz | 📱 +213 550 123 456 | 📠 +213 21 123 456</p>
      </div>
      
      <div class="thank-you">
        ✨ Merci de votre confiance et de votre partenariat ✨
      </div>
    </div>
  </div>
</body>
</html>
