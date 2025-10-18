# Fiche Navette Print Ticket Feature Installation Guide

## Overview
This feature allows printing tickets for fiche navette with:
- Patient information (name, phone)
- All prestations with QR codes
- Appointment dates (if linked)
- Arrival time
- Automatic status update to "arrived"

## Installation Steps

### 1. Install Required Dependencies

The QR code package (endroid/qr-code) is already installed in this project.
If you need to install it manually in the future:

```bash
cd /home/administrator/www/HIS
composer require endroid/qr-code
```

### 2. Build Frontend Assets

```bash
npm run build
# OR for development
npm run dev
```

### 3. Clear Cache

```bash
php artisan route:clear
php artisan config:clear
php artisan cache:clear
```

## Files Modified/Created

### Backend Files:
1. **Created:** `resources/views/pdf/fiche-navette-ticket.blade.php` - PDF template
2. **Modified:** `app/Http/Controllers/Reception/ficheNavetteController.php` - Added `printFicheNavetteTicket` method
3. **Modified:** `routes/web.php` - Added print ticket route

### Frontend Files:
1. **Modified:** `resources/js/Components/Apps/services/Reception/ficheNavetteService.js` - Added `printTicket` method
2. **Modified:** `resources/js/Components/Apps/reception/FicheNavatteItem/FicheNavetteHeader.vue` - Added print button
3. **Modified:** `resources/js/Pages/Apps/reception/FicheNavatte/FicheNavetteItemsList.vue` - Added print handler

## API Endpoints

### Print Fiche Navette Ticket
- **URL:** `/api/reception/fiche-navette/{id}/print-ticket`
- **Method:** POST
- **Auth Required:** Yes
- **Response:** PDF file download

### What it Does:
1. Loads fiche with patient, items, and prestations
2. Generates QR codes for each item (format: `patientId-prestationId-date`)
3. Updates fiche status to "arrived" and sets `arrival_time`
4. Returns PDF ticket for printing

## Usage

### From Fiche Navette Items Page:
1. Navigate to any fiche navette details page
2. Click the "Imprimer" (Print) button in the header
3. PDF ticket will download automatically
4. Status changes to "Arrivé" automatically

## Ticket Contents

The printed ticket includes:
- **Header:** Fiche ID and date
- **Patient Info:** Name, phone, patient ID
- **Arrival Info:** Fiche date, arrival time, status
- **Prestations:** 
  - Prestation name and code
  - Appointment date/time (if linked to appointment)
  - QR code for each prestation
- **Footer:** Printed by user name and timestamp

## QR Code Format

Each prestation gets a QR code with this format:
```
{patientId}-{prestationId}-{ficheDate}
```

Example: `123-456-20251006`

## Troubleshooting

### PDF Not Generating
- Ensure DomPDF is installed: `composer require barryvdh/laravel-dompdf`
- Check Laravel logs: `storage/logs/laravel.log`

### QR Codes Not Showing
- QR code package is already installed (endroid/qr-code v6.0)
- Clear config cache: `php artisan config:clear`
- Check PDF rendering supports images

### Status Not Updating
- Check `fiche_navettes` table has `arrival_time` column
- Run migrations if needed: `php artisan migrate`

### Print Button Not Showing
- Build frontend: `npm run build`
- Clear browser cache
- Check console for JS errors

## Testing

### Test the Print Feature:
```bash
# Create a test fiche
curl -X POST http://localhost:8080/api/reception/fiche-navette \
  -H "Content-Type: application/json" \
  -d '{"patient_id": 1, "notes": "Test fiche"}'

# Print the ticket
curl -X POST http://localhost:8080/api/reception/fiche-navette/1/print-ticket \
  -H "Authorization: Bearer YOUR_TOKEN" \
  --output test-ticket.pdf
```

## Features

✅ Patient name and phone display
✅ All prestations listed
✅ QR codes for each prestation  
✅ Appointment times (when available)
✅ Arrival time tracking
✅ Automatic status update to "arrived"
✅ Printed by user tracking
✅ Professional ticket design
✅ Auto-download PDF
✅ Same format as appointment tickets

## Notes

- Ticket automatically updates fiche status to "arrived"
- QR codes can be scanned for patient tracking
- PDF downloads automatically when generated
- Works for both reception and emergency modules
- Supports multiple prestations per fiche
- Shows appointment dates only when prestation is linked to an appointment
