<?php

namespace App\Services\Printing;

/**
 * Service for generating thermal printer tickets optimized for Xprinter devices
 *
 * Ticket Dimensions: 40mm x 20mm
 * Key Features:
 * - Optimized for continuous printing (all tickets in one batch)
 * - No page breaks between tickets
 * - Optimized font sizes for thermal printer readability
 * - High contrast for clear printing
 * - Barcode support for inventory tracking
 */
class XprinterTicketService
{
    /**
     * Generate optimized HTML for Xprinter thermal printer
     * Ticket size: 40mm x 20mm
     *
     * @param  array  $tickets  Array of ticket data
     * @return string HTML content optimized for thermal printer
     */
    public function generateTicketsHtml(array $tickets): string
    {
        $html = $this->getHtmlHeader();

        foreach ($tickets as $index => $ticket) {
            $html .= $this->generateSingleTicket($ticket, $index + 1);
        }

        $html .= $this->getHtmlFooter();

        return $html;
    }

    /**
     * Get HTML header with printer-optimized styles
     */
    private function getHtmlHeader(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xprinter Thermal Labels</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { width: 100%; font-family: "Courier New", monospace; background: #fff; }
        @page { size: 40mm 20mm; margin: 0; padding: 0; }
        @media print {
            body { width: 40mm; height: auto; margin: 0; padding: 0; 
                -webkit-print-color-adjust: exact !important; 
                print-color-adjust: exact !important; }
            .ticket { page-break-inside: avoid; page-break-after: auto; orphans: 0; widows: 0; }
        }
        .ticket { width: 40mm; height: 20mm; margin: 0; padding: 0.8mm; 
            display: flex; flex-direction: column; justify-content: space-between;
            border: 1px solid #000; page-break-after: always; background: white; }
        .ticket-header { margin-bottom: 0.5mm; padding-bottom: 0.3mm; border-bottom: 1px dotted #000; }
        .product-name { font-size: 7pt; font-weight: bold; line-height: 1; 
            max-height: 3mm; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .product-code { font-size: 4pt; color: #000; line-height: 1; }
        .ticket-content { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5mm; 
            flex: 1; font-size: 3.5pt; line-height: 1.1; }
        .column { display: flex; flex-direction: column; gap: 0.3mm; overflow: hidden; }
        .field { display: flex; flex-direction: column; }
        .field-label { font-size: 3pt; font-weight: bold; text-transform: uppercase; 
            color: #000; line-height: 1; letter-spacing: 0.1px; }
        .field-value { font-size: 4pt; font-weight: bold; color: #000; line-height: 1;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .quantity-highlight { color: #000; font-size: 5pt; font-weight: 900; line-height: 1; }
        .ticket-footer { border-top: 1px dotted #000; padding-top: 0.3mm; font-size: 3pt; 
            line-height: 1; display: flex; justify-content: space-between; align-items: center; }
        .barcode { font-family: "Courier New", monospace; font-size: 3.5pt; font-weight: bold;
            text-align: center; line-height: 1; letter-spacing: -0.5px; }
    </style>
</head>
<body>
HTML;
    }

    /**
     * Generate a single ticket HTML
     */
    private function generateSingleTicket(array $ticket, int $ticketNumber): string
    {
        $expiryDate = $ticket['expiry_date'] ? date('m/y', strtotime($ticket['expiry_date'])) : 'N/A';
        $itemLabel = $ticket['item_type'] === 'sub_item' ? 'B'.$ticket['sub_item_index'] : 'M';

        $productName = substr($ticket['product_name'], 0, 20);
        $productCode = substr($ticket['product_code'], 0, 10);
        $batchNumber = substr($ticket['batch_number'] ?? 'NONE', 0, 6);
        $serialNumber = substr($ticket['serial_number'] ?? '-', 0, 6);

        return <<<HTML
    <div class="ticket">
        <div class="ticket-header">
            <div class="product-name">$productName</div>
            <div class="product-code">$productCode/$batchNumber</div>
        </div>
        <div class="ticket-content">
            <div class="column">
                <div class="field"><div class="field-label">QTY</div><div class="field-value quantity-highlight">{$ticket['quantity']}</div></div>
                <div class="field"><div class="field-label">SN</div><div class="field-value">$serialNumber</div></div>
            </div>
            <div class="column">
                <div class="field"><div class="field-label">EXP</div><div class="field-value">$expiryDate</div></div>
                <div class="field"><div class="field-label">UNIT</div><div class="field-value">{$ticket['unit']}</div></div>
            </div>
        </div>
        <div class="ticket-footer">
            <div class="barcode">$productCode</div>
            <div>$itemLabel</div>
        </div>
    </div>

HTML;
    }

    /**
     * Get HTML footer
     */
    private function getHtmlFooter(): string
    {
        return '</body></html>';
    }

    /**
     * Generate multiple ticket sets for batch printing
     */
    public function generateBatchTicketsHtml(array $tickets, int $copies = 1): string
    {
        $allTickets = [];
        for ($copy = 0; $copy < $copies; $copy++) {
            foreach ($tickets as $ticket) {
                $allTickets[] = $ticket;
            }
        }

        return $this->generateTicketsHtml($allTickets);
    }

    /**
     * Format price for thermal printer
     */
    public function formatPrice(float $price): string
    {
        if ($price >= 1000000) {
            return number_format($price / 1000000, 1).'M';
        } elseif ($price >= 1000) {
            return number_format($price / 1000, 1).'K';
        }

        return number_format($price, 0);
    }

    /**
     * Validate ticket data before printing
     */
    public function validateTickets(array $tickets): array
    {
        $errors = [];
        foreach ($tickets as $index => $ticket) {
            if (empty($ticket['product_name'])) {
                $errors[] = "Ticket $index: Missing product name";
            }
            if (empty($ticket['quantity'])) {
                $errors[] = "Ticket $index: Missing quantity";
            }
        }

        return $errors;
    }
}
