# Fiche Navette Creation - Troubleshooting Guide

## Issue Fixed: Store Method Not Working

### Problem
The `store` method in `ficheNavetteController` was trying to:
1. Query `AppointmentPrestation` with a non-existent field `appointment_date`
2. Map fields that don't exist in the `AppointmentPrestation` model
3. Use fields in the fiche navette that may not exist in the database table

### Solution Applied

**Fixed the `store` method to:**

1. **Use proper database transaction with rollback**
   ```php
   DB::beginTransaction();
   try {
       // Create fiche
       DB::commit();
   } catch (\Exception $e) {
       DB::rollBack();
       // Log error
   }
   ```

2. **Query appointments correctly through relationship**
   ```php
   AppointmentPrestation::with(['appointment', 'prestation'])
       ->whereHas('appointment', function ($query) use ($validatedData) {
           $query->where('patient_id', $validatedData['patient_id'])
                 ->whereDate('appointment_date', Carbon::today());
       })
       ->get();
   ```

3. **Create items with proper fields**
   ```php
   $ficheNavette->items()->create([
       'prestation_id' => $appPrestation->prestation_id,
       'patient_id' => $validatedData['patient_id'],
       'base_price' => $appPrestation->prestation->public_price ?? 0,
       'final_price' => $appPrestation->prestation->public_price ?? 0,
       'status' => 'pending',
       'payment_status' => 'unpaid'
   ]);
   ```

4. **Added comprehensive error logging**
   ```php
   \Log::error('Failed to create Fiche Navette', [
       'error' => $e->getMessage(),
       'trace' => $e->getTraceAsString(),
       'request' => $request->all()
   ]);
   ```

## Testing the Fix

### 1. Test via API (cURL)

```bash
# Get authentication token first
TOKEN="your_auth_token_here"

# Create a new fiche navette
curl -X POST http://10.47.0.26:8080/api/reception/fiche-navette \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "patient_id": 1,
    "notes": "Test fiche creation"
  }'
```

**Expected Success Response:**
```json
{
  "success": true,
  "message": "Fiche Navette created successfully",
  "data": {
    "id": 7,
    "patient_id": 1,
    "creator_id": 1,
    "status": "pending",
    "fiche_date": "2025-10-06T14:30:00.000000Z",
    "total_amount": "0.00",
    "patient_name": "John Doe",
    "creator_name": "Dr. Smith"
  }
}
```

### 2. Test via UI

1. **Navigate to:** Reception → Fiche Navette
2. **Click:** "Nouvelle Fiche" button
3. **Select:** A patient from the dropdown
4. **Add notes** (optional)
5. **Click:** "Créer" or "Save"
6. **Expected:** Success message and new fiche appears in list

### 3. Check Database

```sql
-- Verify the fiche was created
SELECT * FROM fiche_navettes ORDER BY id DESC LIMIT 1;

-- Check if items were created (if patient had appointments today)
SELECT fi.*, p.name as prestation_name
FROM fiche_navette_items fi
JOIN prestations p ON p.id = fi.prestation_id
WHERE fi.fiche_navette_id = (SELECT MAX(id) FROM fiche_navettes);
```

### 4. Check Laravel Logs

```bash
# View latest errors
tail -f /home/administrator/www/HIS/storage/logs/laravel.log

# Search for fiche navette errors
grep -i "Failed to create Fiche Navette" storage/logs/laravel.log
```

## Common Issues and Solutions

### Issue 1: "patient_id is required"
**Solution:** Ensure patient_id is sent in the request
```javascript
{
  "patient_id": 1  // Must be a valid patient ID
}
```

### Issue 2: "Column 'notes' doesn't exist"
**Solution:** Already fixed - removed notes from create array

### Issue 3: Items not created automatically
**Reason:** This is expected behavior if:
- Patient has no appointments today
- Appointments don't have prestations linked

**To verify:**
```sql
SELECT ap.*, a.appointment_date, a.patient_id
FROM appointment_prestations ap
JOIN appointments a ON a.id = ap.appointment_id
WHERE a.patient_id = 1
  AND DATE(a.appointment_date) = CURDATE();
```

### Issue 4: Database connection error
**Check:**
```bash
# Test database connection
php artisan tinker --execute="DB::connection()->getPdo();"

# Check .env file
cat .env | grep DB_
```

### Issue 5: Permission errors
**Fix permissions:**
```bash
cd /home/administrator/www/HIS
chmod -R 775 storage
chmod -R 775 bootstrap/cache
chown -R www-data:www-data storage
chown -R www-data:www-data bootstrap/cache
```

## Debugging Steps

### Step 1: Enable Query Logging
Add to your test code:
```php
DB::enableQueryLog();
// ... your code ...
dd(DB::getQueryLog());
```

### Step 2: Check Route
```bash
php artisan route:list | grep fiche-navette
```

Should show:
```
POST  api/reception/fiche-navette  Reception\ficheNavetteController@store
```

### Step 3: Test in Tinker
```bash
php artisan tinker
```

```php
// Test creating a fiche
$fiche = \App\Models\Reception\ficheNavette::create([
    'patient_id' => 1,
    'creator_id' => 1,
    'status' => 'pending',
    'fiche_date' => now(),
    'total_amount' => 0
]);

echo "Fiche created with ID: " . $fiche->id;
```

### Step 4: Check Fillable Fields
```php
// In tinker
$model = new \App\Models\Reception\ficheNavette();
print_r($model->getFillable());
```

## What Changed

### Before (Broken):
```php
// Wrong query - appointment_date is on appointments table, not appointment_prestations
$prestations = AppointmentPrestation::with('appointment')
    ->where('patient_id', $validatedData['patient_id'])
    ->whereDate('appointment_date', Carbon::now()->startOfDay())  // ❌ Field doesn't exist
    ->get();

// Wrong mapping - these fields don't exist
$ficheNavette->items()->createMany($prestations->map(function ($item) {
    return [
        'prestation_id' => $item->id,  // ❌ Should be $item->prestation_id
        'quantity' => $item->quantity,  // ❌ Field doesn't exist
        'price' => $item->price,        // ❌ Field doesn't exist
    ];
})->toArray());
```

### After (Fixed):
```php
// Correct query using whereHas
$appointmentPrestations = AppointmentPrestation::with(['appointment', 'prestation'])
    ->whereHas('appointment', function ($query) use ($validatedData) {
        $query->where('patient_id', $validatedData['patient_id'])
              ->whereDate('appointment_date', Carbon::today());
    })
    ->get();

// Correct item creation
foreach ($appointmentPrestations as $appPrestation) {
    if ($appPrestation->prestation) {
        $ficheNavette->items()->create([
            'prestation_id' => $appPrestation->prestation_id,
            'patient_id' => $validatedData['patient_id'],
            'base_price' => $appPrestation->prestation->public_price ?? 0,
            'final_price' => $appPrestation->prestation->public_price ?? 0,
            'status' => 'pending',
            'payment_status' => 'unpaid'
        ]);
    }
}
```

## Verification Checklist

- [x] Fixed incorrect query on AppointmentPrestation
- [x] Added proper whereHas for appointment relationship
- [x] Corrected field mapping for items
- [x] Added DB transaction with rollback
- [x] Added comprehensive error logging
- [x] Removed non-existent fields from create
- [x] PHP syntax check passed
- [x] Database connection verified
- [x] Table structure verified

## Next Steps

1. **Test the creation** via UI or API
2. **Check logs** if any errors occur
3. **Verify data** in database after creation
4. **Test with different scenarios:**
   - Patient with appointments
   - Patient without appointments
   - Multiple prestations
   - Single prestation

## Support

If issues persist:
1. Check `/storage/logs/laravel.log`
2. Enable debug mode in `.env`: `APP_DEBUG=true`
3. Test database connection
4. Verify patient exists in database
5. Check user authentication

---

**Status:** ✅ FIXED AND READY TO TEST
