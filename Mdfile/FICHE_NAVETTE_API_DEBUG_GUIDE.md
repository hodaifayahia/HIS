# Fiche Navette API Not Sending - Debug Guide

## Issue
The fiche navette modal form is not sending the API request when creating a new fiche.

## Root Causes Identified

### 1. Missing `closeModal` Function (FIXED ✅)
**Problem:** The modal component referenced `closeModal()` but it wasn't defined.

**Solution Applied:**
```javascript
const closeModal = () => {
  emit('update:visible', false)
  resetForm()
}
```

### 2. Service Import Issue
**Check:** Make sure the service is imported correctly in the modal:
```javascript
import ficheNavetteService from '../../../../Components/Apps/services/Reception/ficheNavetteService';
```

**Note:** Should be **default import**, not destructured import.

### 3. Patient Selection Data Structure
**Problem:** The patient object structure might not match what's expected.

**Expected Structure:**
```javascript
formData.patient = {
  id: 123,
  first_name: "John",
  last_name: "Doe"
}
```

## Debug Steps Added

### Enhanced Logging in Modal
The `handleSubmit` function now has comprehensive logging:

```javascript
console.log('=== FORM SUBMIT STARTED ===');
console.log('Form data:', formData);
console.log('Payload:', payload);
console.log('=== API RESPONSE ===');
console.log('Result:', result);
```

### Check Browser Console
1. **Open browser DevTools** (F12)
2. **Go to Console tab**
3. **Click "New Form" button**
4. **Select a patient**
5. **Click "Create" button**
6. **Look for these logs:**
   - `=== FORM SUBMIT STARTED ===`
   - `Payload:` - Should show patient_id, fiche_date, status
   - `=== SENDING API REQUEST ===`
   - `=== API RESPONSE ===`

### Check Network Tab
1. **Open DevTools > Network tab**
2. **Filter by "fiche-navette"**
3. **Click Create button**
4. **Look for POST request to:** `/api/reception/fiche-navette`
5. **Check:**
   - Request Payload
   - Response Status (should be 201)
   - Response Body

## Common Issues & Solutions

### Issue 1: No API Call at All
**Symptoms:** No network request in Network tab, no logs in console

**Possible Causes:**
- Form validation failing silently
- Patient not selected properly
- Button not triggering submit

**Solution:**
```javascript
// Check validation
console.log('isFormValid:', isFormValid.value);
console.log('formData.patient:', formData.patient);
```

### Issue 2: 422 Validation Error
**Symptoms:** Network request shows, but returns 422 status

**Check Laravel Logs:**
```bash
tail -f /home/administrator/www/HIS/storage/logs/laravel.log
```

**Common Validation Errors:**
- `patient_id` is required
- `patient_id` must exist in patients table

### Issue 3: 500 Server Error
**Symptoms:** Network request returns 500 status

**Check:**
```bash
# Check Laravel logs
tail -f storage/logs/laravel.log | grep "Failed to create Fiche Navette"

# Check for PHP errors
tail -f storage/logs/laravel.log | grep "ERROR"
```

### Issue 4: CORS Error
**Symptoms:** Console shows CORS policy error

**Solution:** Ensure you're accessing the app on the correct domain/port

### Issue 5: Authentication Error (401)
**Symptoms:** Network request returns 401 Unauthorized

**Solution:** Check if user is logged in and token is valid

## Testing the API Directly

### Test with cURL
```bash
# Get auth token first (login)
TOKEN="your_auth_token_here"

# Test create endpoint
curl -X POST http://10.47.0.26:8080/api/reception/fiche-navette \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "patient_id": 1,
    "fiche_date": "2025-10-06",
    "status": "pending"
  }'
```

### Test with Browser Console
```javascript
// In browser console (when logged in)
const payload = {
  patient_id: 1,
  fiche_date: '2025-10-06',
  status: 'pending'
};

axios.post('/api/reception/fiche-navette', payload)
  .then(response => console.log('Success:', response.data))
  .catch(error => console.error('Error:', error.response?.data || error));
```

## Verification Checklist

### Backend ✅
- [x] Route exists: `POST /api/reception/fiche-navette`
- [x] Controller method: `ficheNavetteController@store`
- [x] Database table: `fiche_navettes` exists
- [x] Validation rules defined
- [x] Error logging added

### Frontend
- [x] Service method: `ficheNavetteService.createFicheNavette()`
- [x] Modal component: `ficheNavetteModal.vue`
- [x] Import statement correct
- [x] Form validation working
- [ ] Patient selection working (TO VERIFY)
- [ ] API call triggering (TO VERIFY)

## Step-by-Step User Test

1. **Open the Application**
   - Navigate to: Reception → Fiche Navette

2. **Click "New Form" Button**
   - Modal should open
   - Console should be clear of errors

3. **Select a Patient**
   - Type in patient search
   - Select a patient from dropdown
   - Check console: Should see selected patient object

4. **Click "Create" Button**
   - Check Console for:
     ```
     === FORM SUBMIT STARTED ===
     Form data: {...}
     === SENDING API REQUEST ===
     Payload: {patient_id: X, fiche_date: "...", status: "pending"}
     ```
   - Check Network tab for POST request

5. **Verify Success**
   - Success toast should appear
   - Modal should close
   - List should refresh with new fiche
   - Should redirect to items page

## What Was Changed

### Files Modified:

1. **ficheNavetteController.php** (`/app/Http/Controllers/Reception/`)
   - Fixed store() method
   - Added proper query using whereHas
   - Added error logging
   - Added database transaction

2. **ficheNavetteModal.vue** (`/resources/js/Components/Apps/reception/FicheNavatte/`)
   - Added missing `closeModal()` function
   - Enhanced logging in `handleSubmit()`
   - Better error messages

3. **ficheNavetteService.js** (`/resources/js/Components/Apps/services/Reception/`)
   - Service method exists and correct

## Next Steps After Build Completes

1. **Clear browser cache** (Ctrl+Shift+Delete)
2. **Hard reload** the page (Ctrl+Shift+R)
3. **Open DevTools Console**
4. **Try creating a new fiche**
5. **Check console output**
6. **Check Network tab**
7. **Report findings**

## Expected Console Output (Success)

```
=== FORM SUBMIT STARTED ===
Form data: {patient: {id: 1, first_name: "John", last_name: "Doe"}, fiche_date: Mon Oct 06 2025..., status: "pending"}
Mode: create
=== SENDING API REQUEST ===
Payload: {patient_id: 1, fiche_date: "2025-10-06", status: "pending"}
Mode: create
Service method: createFicheNavette
=== API RESPONSE ===
Result: {success: true, data: {...}, message: "Fiche Navette created successfully"}
Success! Fiche created: {...}
=== FORM SUBMIT ENDED ===
```

## Expected Network Request

**Request URL:** `http://10.47.0.26:8080/api/reception/fiche-navette`  
**Method:** `POST`  
**Status:** `201 Created`  

**Request Payload:**
```json
{
  "patient_id": 1,
  "fiche_date": "2025-10-06",
  "status": "pending"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Fiche Navette created successfully",
  "data": {
    "id": 7,
    "patient_id": 1,
    "creator_id": 1,
    "status": "pending",
    "fiche_date": "2025-10-06",
    "total_amount": "0.00",
    "patient_name": "John Doe",
    "creator_name": "Dr. Smith"
  }
}
```

---

## Support

If the issue persists after following this guide:

1. **Capture console logs** (screenshot or copy/paste)
2. **Capture network request** (screenshot)
3. **Check Laravel logs:** `tail -f storage/logs/laravel.log`
4. **Check patient selection** - ensure patient object has `id` property
5. **Try with different patient** - could be data-specific issue

**Most Likely Issue:** Patient object structure from PatientSearch component doesn't match expected format.

**Quick Fix to Test:** Temporarily hardcode patient_id in handleSubmit to isolate the issue.

