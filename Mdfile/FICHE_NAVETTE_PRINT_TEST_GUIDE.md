# Quick Test Guide - Fiche Navette Print Ticket

## ✅ Pre-Flight Checklist

All required packages are installed:
- ✅ DomPDF (barryvdh/laravel-dompdf v3.1.1)
- ✅ QR Code (endroid/qr-code v6.0)
- ✅ Routes registered

## 🧪 Testing Steps

### 1. Manual UI Test (Recommended)

1. **Login to the application**
   ```
   http://10.47.0.26:8080
   ```

2. **Navigate to Reception → Fiche Navette**
   
3. **Open any existing fiche navette** or create a new one

4. **Click the "Imprimer" button** in the header (next to "Ajouter" button)

5. **Expected Result:**
   - Loading toast appears
   - PDF downloads automatically
   - Success toast: "Ticket imprimé avec succès. Le statut est maintenant 'Arrivé'."
   - Fiche status badge changes to "ARRIVÉ"
   - Arrival time is recorded

### 2. API Test (cURL)

```bash
# Get auth token first (replace with your credentials)
TOKEN="your_auth_token_here"

# Test print ticket endpoint
curl -X POST http://10.47.0.26:8080/api/reception/fiche-navette/1/print-ticket \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/pdf" \
  --output fiche-ticket-test.pdf

# Check the downloaded file
ls -lh fiche-ticket-test.pdf
```

### 3. Database Verification

```sql
-- Check if status and arrival_time updated
SELECT id, status, arrival_time, updated_at 
FROM fiche_navettes 
WHERE id = 1;

-- Before print: status = 'pending', arrival_time = NULL
-- After print:  status = 'arrived', arrival_time = '2025-10-06 14:30:00'
```

## 🔍 What to Verify in the PDF

### Header Section
- [ ] Shows "FICHE NAVETTE" title
- [ ] Displays correct Fiche ID (#123)
- [ ] Shows current date and time

### Patient Information
- [ ] Patient name is correct
- [ ] Phone number displays (or shows "N/A")
- [ ] Patient ID is correct

### Arrival Information
- [ ] Fiche date is correct
- [ ] Arrival time shows current time
- [ ] Status shows green "ARRIVÉ" badge

### Prestations Section
- [ ] All prestations are listed
- [ ] Each has name and internal code
- [ ] QR codes are visible (one per prestation)
- [ ] Appointment dates show when available
- [ ] Shows "Non programmé" when no appointment

### Footer Section
- [ ] Shows "Imprimé par: {Your Name}"
- [ ] Shows current date/time
- [ ] Shows "Merci de votre confiance" message

### QR Code Test
- [ ] QR codes are scannable
- [ ] Scan returns format: `123-456-20251006`
- [ ] Use any QR scanner app on phone

## 🐛 Troubleshooting

### PDF Not Downloading
**Check:**
```bash
# View Laravel logs
tail -f storage/logs/laravel.log

# Clear caches
php artisan route:clear
php artisan config:clear
php artisan cache:clear
```

### QR Codes Not Showing in PDF
**Check:**
```bash
# Verify QR package
composer show endroid/qr-code

# Should show: endroid/qr-code 6.0.9
```

### Status Not Updating
**Check database table:**
```sql
DESC fiche_navettes;
-- Verify 'arrival_time' column exists
-- Verify 'status' column exists
```

### Button Not Appearing
**Check:**
```bash
# Rebuild frontend
npm run build

# Check browser console for errors
# Clear browser cache
```

### Permission Errors
**Check:**
```bash
# Verify file permissions
ls -la resources/views/pdf/
ls -la storage/logs/

# Fix if needed
chmod -R 755 resources/views/pdf/
chmod -R 775 storage/
```

## 📊 Success Criteria

✅ **Feature is working correctly if:**
1. Print button appears in fiche navette items page
2. Clicking button downloads PDF immediately
3. PDF contains all patient and prestation information
4. QR codes are visible and scannable
5. Status changes to "arrived" after printing
6. Arrival time is recorded in database
7. Success toast message appears
8. No errors in browser console or Laravel logs

## 🎯 Test Scenarios

### Scenario 1: Fiche with Appointments
- Create fiche for patient
- Link prestations to appointments
- Print ticket
- Verify appointment dates show in PDF

### Scenario 2: Fiche without Appointments
- Create fiche for patient
- Add prestations (not linked to appointments)
- Print ticket
- Verify "Non programmé" shows for appointment dates

### Scenario 3: Multiple Prestations
- Create fiche with 5+ prestations
- Print ticket
- Verify all prestations listed
- Verify each has unique QR code

### Scenario 4: Already Arrived Fiche
- Print ticket for fiche
- Print ticket again
- Verify arrival_time doesn't change on second print

### Scenario 5: Different User Printing
- Login as User A, print ticket
- Check PDF shows "Imprimé par: User A"
- Login as User B, print same ticket
- Check PDF shows "Imprimé par: User B"

## 📱 Mobile/Responsive Test

Test on different devices:
- [ ] Desktop browser (Chrome, Firefox, Safari)
- [ ] Tablet
- [ ] Mobile phone
- [ ] Print preview looks correct

## 🖨️ Printer Test (Optional)

If you have a thermal printer:
1. Print the PDF to thermal printer
2. Verify 80mm width fits correctly
3. Check QR codes print clearly
4. Verify text is readable

## ✨ Final Checklist

Before marking as complete:
- [ ] Manual UI test passed
- [ ] PDF downloads correctly
- [ ] All sections display properly
- [ ] QR codes are scannable
- [ ] Status updates to "arrived"
- [ ] Arrival time is recorded
- [ ] No errors in logs
- [ ] Tested on main browser
- [ ] Documentation reviewed
- [ ] Ready for production use

---

## 🚀 Ready to Test!

Everything is installed and configured.
Just navigate to the fiche navette page and click "Imprimer"!

**Current Status:** ✅ READY FOR TESTING
