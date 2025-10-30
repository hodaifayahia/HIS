# Auto-Package Conversion Test - Complete Scenario

## Goal
Test the exact scenario you described:
- Start Fiche with: Endoscopie digestive 87 + Endoscopie digestive 87
- Add: Pose d'implant 88
- Expected: Auto-convert to PACK CARDIOLOGIE 05

## Test Steps

### Step 1: Verify Test Data
```bash
php artisan tinker
```

```php
// Check prestations
$p87 = \DB::table('prestations')->find(87);
$p88 = \DB::table('prestations')->find(88);
echo "87: " . $p87->name . "\n";
echo "88: " . $p88->name . "\n";

// Check package
$pkg = \DB::table('prestation_packages')->find(11);
echo "\nPackage 11: " . $pkg->name . "\n";
echo "Active: " . ($pkg->is_active ? 'Yes' : 'No') . "\n";

// Check what prestations are in package 11
$pkgItems = \DB::table('prestation_package_items')
    ->where('prestation_package_id', 11)
    ->pluck('prestation_id')
    ->toArray();
echo "Package contains: " . json_encode($pkgItems) . "\n";
```

Expected Output:
```
87: Endoscopie digestive 87
88: Pose d'implant 88

Package 11: PACK CARDIOLOGIE 05
Active: Yes
Package contains: [87,87,88]
```

### Step 2: Create Test Fiche Navette

Using POST `/api/ficheNavette` with existing patient ID 1:

```json
{
  "patient_id": 1,
  "date_reception": "2025-10-23 14:00:00"
}
```

Note response with `fiche_id`.

### Step 3: Add First Item (Endoscopie 87)

POST `/api/ficheNavette/{fiche_id}/items`

```json
{
  "prestations": [
    {
      "id": 87,
      "prestation_id": 87,
      "quantity": 1
    }
  ]
}
```

Response should show:
- 1 item with prestation_id: 87

### Step 4: Add Second Item (Endoscopie 87)

POST `/api/ficheNavette/{fiche_id}/items`

```json
{
  "prestations": [
    {
      "id": 87,
      "prestation_id": 87,
      "quantity": 1
    }
  ]
}
```

Response should show:
- 2 items with prestation_id: 87

### Step 5: THIS IS WHERE AUTO-CONVERSION HAPPENS!

POST `/api/ficheNavette/{fiche_id}/items`

```json
{
  "prestations": [
    {
      "id": 88,
      "prestation_id": 88,
      "quantity": 1
    }
  ]
}
```

**EXPECTED BEHAVIOR:**
```json
{
  "success": true,
  "message": "Items added and auto-converted to package",
  "data": {
    "id": {fiche_id},
    "items": [
      {
        "id": 3,
        "fiche_navette_id": {fiche_id},
        "prestation_id": null,
        "package_id": 11,
        "payment_status": "unpaid",
        "package": {
          "id": 11,
          "name": "PACK CARDIOLOGIE 05",
          "price": "..."
        }
      }
    ]
  },
  "conversion": {
    "should_convert": true,
    "converted": true,
    "package_id": 11,
    "package_name": "PACK CARDIOLOGIE 05",
    "message": "Auto-converted to package: PACK CARDIOLOGIE 05"
  }
}
```

### Step 6: Verify in Database

```php
// Check items in fiche
$items = \DB::table('fiche_navette_items')
    ->where('fiche_navette_id', {fiche_id})
    ->get();

echo "Items in fiche:\n";
foreach($items as $item) {
    echo "- Item " . $item->id . ": ";
    if($item->package_id) {
        echo "Package " . $item->package_id;
    } else {
        echo "Prestation " . $item->prestation_id;
    }
    echo " (Status: " . $item->payment_status . ")\n";
}

// Expected: Only 1 item with package_id = 11
```

## Debugging: Check Service Logs

If auto-conversion doesn't happen, check:

```bash
tail -f storage/logs/laravel.log | grep -i "conversion\|package\|auto"
```

Look for messages like:
- "Checking for package conversion"
- "Found matching package"
- "Auto-converting to package on add item"
- "✅ EXACT MATCH FOUND"

If you see "❌ No matching package found", then:
1. Package prestations don't match
2. Items are paid (conversion blocked)
3. Package is inactive

## Debugging: Check checkAndPreparePackageConversion Method

The method in ReceptionService.php should:
1. Get existing prestation IDs from fiche
2. Combine with new prestation IDs
3. Call detectMatchingPackage() with combined IDs
4. Check all items are unpaid
5. Check package is active
6. Return conversion status

If returning `should_convert: false`, check:
- Are the prestation IDs being combined correctly?
- Are there exactly 3 prestations (87, 87, 88)?
- Is the package marked as active?
- Are any items marked as paid?

## Debugging: Check autoConvertToPackageOnAddItem Method

The method should:
1. Delete old items from database
2. Create new package item
3. Store doctor assignments
4. Update fiche total
5. Return refreshed fiche

If this fails, you'll see error in response with details.

## URL Pattern for Testing

If running locally:
```
http://localhost:8000/api/ficheNavette/{fiche_id}/items
```

If running on server:
```
https://your-domain.com/api/ficheNavette/{fiche_id}/items
```

## Test with cURL

```bash
# Get fiche ID from Step 2 response
FICHE_ID=123

# Step 3: Add first Endoscopie
curl -X POST http://localhost:8000/api/ficheNavette/$FICHE_ID/items \
  -H "Content-Type: application/json" \
  -d '{"prestations":[{"id":87,"prestation_id":87,"quantity":1}]}'

# Step 4: Add second Endoscopie
curl -X POST http://localhost:8000/api/ficheNavette/$FICHE_ID/items \
  -H "Content-Type: application/json" \
  -d '{"prestations":[{"id":87,"prestation_id":87,"quantity":1}]}'

# Step 5: Add Pose d'implant (SHOULD TRIGGER AUTO-CONVERSION!)
curl -X POST http://localhost:8000/api/ficheNavette/$FICHE_ID/items \
  -H "Content-Type: application/json" \
  -d '{"prestations":[{"id":88,"prestation_id":88,"quantity":1}]}'
```

## What to Check If It Doesn't Work

1. **Check PHP Error Logs**
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Check Browser Console** (if using frontend)
   - Network tab to see API response
   - Console for any JavaScript errors

3. **Verify Database**
   ```php
   // Are the items actually being added?
   \DB::table('fiche_navette_items')->where('fiche_navette_id', {fiche_id})->count()
   
   // Did the old items get deleted?
   // Should only see 1 item with package_id = 11
   ```

4. **Check Method is Being Called**
   Add temporary console logs to verify:
   - `checkAndPreparePackageConversion()` called
   - `autoConvertToPackageOnAddItem()` called
   - Items deleted
   - New package item created

5. **Common Issues**
   - Package prestation IDs don't match exactly
   - Package is marked as inactive
   - Items were already paid
   - Syntax error in controller (check with `php -l`)
   - Service method not imported correctly

