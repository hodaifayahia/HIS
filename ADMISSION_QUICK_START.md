# Quick Start Guide - Admission Workflow

## Overview
When creating a new admission, the system now:
1. **Searches for today's fiche navette** for the patient
2. **Uses existing fiche if found**, or **creates new one if not**
3. **Automatically adds initial prestation** (Surgery only)
4. **Creates empty fiche** (Nursing - no default prestation)

---

## User Guide

### Create Surgery Admission (Upfront Payment)

1. **Navigate to**: Admin → Admissions → Create New Admission
2. **Search Patient**: Type patient name/phone/ID in search box
3. **View Results**: 
   - Patient name shown
   - If they have today's fiche → ✅ Green indicator "Today's Fiche: FN-1-..."
   - If no fiche → No indicator (system will create one)
4. **Click Result**: Selects patient and displays card
5. **Select Type**: Click "Surgery (Upfront)" button
6. **Select Doctor**: Choose from dropdown (optional)
7. **Select Prestation**: Required dropdown appears - choose initial prestation
8. **Submit**: Click "Create Admission"
9. **Result**: 
   - ✅ Fiche navette linked (or created if didn't exist)
   - ✅ Initial prestation added to fiche as item
   - ✅ Fiche total_amount calculated
   - ✅ Admission created and linked
   - Redirects to admission detail page

---

### Create Nursing Admission (Pay After)

1. **Navigate to**: Admin → Admissions → Create New Admission
2. **Search Patient**: Type patient name/phone/ID in search box
3. **View & Select Result**: Click patient from dropdown
4. **Select Type**: Click "Nursing (Pay After)" button
5. **Select Doctor**: Choose from dropdown (optional)
6. **Note**: Initial Prestation field NOT shown (Nursing doesn't need default)
7. **Submit**: Click "Create Admission"
8. **Result**:
   - ✅ Fiche navette linked (or created if didn't exist)
   - ❌ NO prestation added (empty fiche)
   - ✅ Admission created and linked
   - Redirects to admission detail page
   - User can add services from detail page later

---

## API Usage (Developer)

### Search Patients (with Fiche Info)
```bash
GET /api/patients/search?query=john

# Response includes today's fiche navette info:
{
  "id": 1,
  "Firstname": "John",
  "today_fiche_navette": {
    "id": 42,
    "reference": "FN-1-20251115143022",
    "status": "pending"
  }
}
```

### Create Surgery Admission
```bash
POST /api/admissions

{
  "patient_id": 1,
  "doctor_id": 5,
  "type": "surgery",
  "initial_prestation_id": 10
}

# Success Response:
{
  "success": true,
  "data": {
    "id": 100,
    "fiche_navette_id": 42
    # Fiche was automatically created/linked
    # Initial prestation automatically added
  }
}
```

### Create Nursing Admission
```bash
POST /api/admissions

{
  "patient_id": 2,
  "doctor_id": 5,
  "type": "nursing"
}

# Success Response:
{
  "success": true,
  "data": {
    "id": 101,
    "fiche_navette_id": 43
    # Fiche was automatically created/linked
    # NO prestation added
  }
}
```

---

## Testing

### Test 1: Surgery Admission Auto-Adds Prestation
```bash
# 1. Create surgery admission
POST /api/admissions
{
  "patient_id": 1,
  "type": "surgery",
  "initial_prestation_id": 10,
  "doctor_id": 5
}

# 2. Get the returned fiche_navette_id (e.g., 42)

# 3. Check fiche items
GET /api/fiche-navettes/42

# Expected: Fiche has 1 item with prestation_id = 10
```

### Test 2: Nursing Admission No Prestation
```bash
# 1. Create nursing admission
POST /api/admissions
{
  "patient_id": 2,
  "type": "nursing",
  "doctor_id": 5
}

# 2. Get the returned fiche_navette_id (e.g., 43)

# 3. Check fiche items
GET /api/fiche-navettes/43

# Expected: Fiche has 0 items
```

### Test 3: Reuse Today's Fiche
```bash
# 1. Search patient with today's fiche
GET /api/patients/search?query=john

# Check if today_fiche_navette is NOT null

# 2. Create admission for same patient
POST /api/admissions
{
  "patient_id": 1,
  "type": "nursing",
  "doctor_id": 5
}

# 3. Returned fiche_navette_id should MATCH the one from search
# (Same fiche is reused, not created new)
```

---

## Database Verification

### Check if Fiche Was Created
```sql
-- Find latest fiche for patient 1
SELECT * FROM fiche_navettes 
WHERE patient_id = 1 
AND DATE(fiche_date) = CURDATE()
ORDER BY created_at DESC 
LIMIT 1;
```

### Check Fiche Items
```sql
-- See items in fiche 42
SELECT * FROM fiche_navette_items 
WHERE fiche_navette_id = 42;

-- For Surgery: Should have 1+ items
-- For Nursing: Should have 0 items
```

### Check Admission
```sql
-- Find admission and verify fiche link
SELECT 
  id, patient_id, type, fiche_navette_id, 
  initial_prestation_id, status 
FROM admissions 
WHERE id = 100;

-- fiche_navette_id should NOT be NULL
-- initial_prestation_id populated only for surgery
```

---

## Common Scenarios

### Scenario 1: Patient Called Twice Same Day
```
Time 09:00 - Surgery Admission Created
  ✓ Fiche created (FN-1-20251115...)
  ✓ ECG added as prestation

Time 14:00 - Nursing Admission Created
  ✓ SAME fiche used (not new one)
  ✓ No additional prestation added
  
Result: 1 fiche with 1 prestation (ECG)
```

### Scenario 2: Patient Called Tomorrow
```
Time 15:00 Today - Surgery Admission Created
  ✓ Fiche created (FN-1-20251115...)
  
Time 09:00 Tomorrow - Another Admission Created
  ✓ NEW fiche created (FN-1-20251116...)
  
Result: 2 separate fiches (different dates)
```

### Scenario 3: Surgery + Additional Services
```
1. Surgery admission created → Prestation added
2. From admission detail, click "Add Service"
3. Add additional prestations to same fiche
4. Fiche total recalculated

Result: Fiche with multiple items
```

---

## Troubleshooting

### Issue: Prestation not added for surgery
**Check**: 
- Is `type` = "surgery"? 
- Is `initial_prestation_id` provided and valid?
- Is prestation price field populated?

**Fix**: Verify prestation exists: `SELECT * FROM prestations WHERE id = 10;`

### Issue: Fiche not created
**Check**: 
- Is patient_id valid?
- Is creator_id (auth user) valid?
- Are there database constraints preventing creation?

**Debug**: Check Laravel logs for errors

### Issue: Same fiche not reused
**Check**:
- Is fiche_date actually today's date?
- Is patient_id same?
- Is timezone correct?

**Debug**: 
```php
php artisan tinker
$patient = Patient::find(1);
$fiche = $patient->ficheNavettes()->whereDate('fiche_date', today())->first();
echo "Today: " . today();
echo "Fiche date: " . ($fiche->fiche_date ?? 'none');
```

---

## Code References

| Feature | File | Line |
|---------|------|------|
| Search with fiche info | `PatientController` | 147-157 |
| Auto create/find fiche | `FicheNavetteSearchService` | - |
| Add prestation to fiche | `AdmissionService::addInitialPrestationToFiche()` | 138-177 |
| Create admission | `AdmissionService::createAdmission()` | 22-60 |
| Vue search component | `AdmissionCreate.vue` | All |
| Patient relationship | `Patient.php` | Added |

---

## Performance Tips

1. **Cache**: Search results cached 5 minutes - first search slower
2. **Indexes**: Ensure `(patient_id, fiche_date)` indexed on fiche_navettes
3. **Queries**: Using eager loading - no N+1 issues
4. **Transactions**: Atomic - either all succeeds or all rolls back

---

## Support

For issues:
1. Check logs: `storage/logs/laravel.log`
2. Verify database state (see SQL section above)
3. Test API directly with Postman
4. Check Vue browser console for client-side errors

---

## Quick Reference

| Action | Result |
|--------|--------|
| Search patient | Returns today's fiche info if exists |
| Create Surgery admission | Auto-creates fiche + adds prestation |
| Create Nursing admission | Auto-creates fiche + no prestation |
| Multiple admissions same day | Reuses same fiche |
| Admissions next day | Creates new fiche (different date) |

---

## Success Indicators

✅ Patient search shows "Today's Fiche" indicator  
✅ Surgery admission prestation auto-added to fiche  
✅ Nursing admission has empty fiche  
✅ Multiple admissions reuse same fiche  
✅ Admission detail shows fiche_navette_id populated  
✅ No errors in logs during creation  

**All features working!** ✨
