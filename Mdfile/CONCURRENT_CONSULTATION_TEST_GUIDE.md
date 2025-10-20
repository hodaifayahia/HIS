# 🧪 Concurrent Consultation Prevention - Test Guide

## ✅ System is Ready to Test!

All code is implemented and Laravel Reverb WebSocket server is running on port 8081.

---

## 🎯 Test Scenario 1: Concurrent Consultation Blocking

### Goal
Verify that a patient cannot be in two consultations at the same time.

### Prerequisites
- Two doctor accounts (Doctor A and Doctor B)
- One patient account (Patient X)
- One scheduled appointment for Patient X with Doctor A

### Test Steps

#### Step 1: Doctor A Opens Browser
1. Open browser (e.g., Chrome)
2. Login as **Doctor A**
3. Navigate to Consultation page (`/consultations`)
4. **Keep this browser tab open!**

#### Step 2: Doctor B Opens Different Browser
1. Open a **different browser** or **incognito window** (e.g., Firefox or Chrome Incognito)
2. Login as **Doctor B**
3. Navigate to Consultation page (`/consultations`)
4. **Keep this browser tab open!**

#### Step 3: Doctor A Starts Consultation
1. In **Doctor A's browser**, find Patient X in the appointment list
2. Click "Start Consultation" button
3. System should:
   - ✅ Create consultation record
   - ✅ Update `patient_tracking` status to `in_progress`
   - ✅ Navigate to consultation form

#### Step 4: Doctor B Attempts to Start Consultation
1. In **Doctor B's browser**, find Patient X in the appointment list
2. Click "Start Consultation" button
3. **Expected Result:**
   - ❌ Consultation should **NOT** be created
   - ⚠️ **Toast notification appears** on Doctor B's screen
   - Message should say: *"Patient is currently in consultation with Dr. [Doctor A Name]. Please wait until the consultation is completed."*
   - Duration: 5 seconds

#### Step 5: Verify Database
```sql
-- Check patient_tracking table
SELECT 
    pt.id,
    pt.patient_id,
    pt.status,
    pt.check_in_time,
    pt.check_out_time,
    u.name as doctor_name
FROM patient_trackings pt
LEFT JOIN users u ON pt.created_by = u.id
WHERE pt.patient_id = {PATIENT_X_ID}
  AND DATE(pt.check_in_time) = CURDATE()
ORDER BY pt.check_in_time DESC;
```

**Expected:** Only ONE record with `status = 'in_progress'` for Patient X today.

---

## 🎯 Test Scenario 2: Consultation Completion Notification

### Goal
Verify that when a consultation completes, all doctors are notified in real-time.

### Prerequisites
- Same setup from Scenario 1 (Doctor A with active consultation)
- Doctor B's browser still open on consultation page

### Test Steps

#### Step 1: Doctor A Completes Consultation
1. In **Doctor A's browser**, complete the consultation
2. Mark the appointment as **DONE**
3. System should:
   - ✅ Update appointment status to DONE (4)
   - ✅ Update `patient_tracking` status to `completed`
   - ✅ Set `check_out_time` to current time
   - ✅ Broadcast `ConsultationCompleted` event

#### Step 2: Doctor B Receives Notification
1. In **Doctor B's browser**, watch the screen
2. **Expected Result:**
   - 🔔 Notification appears (optional, can be added)
   - 🔄 **Appointment list automatically refreshes**
   - Patient X now shows as available for new consultations
   - **NO manual page refresh required!**

#### Step 3: Doctor B Can Now Start Consultation
1. In **Doctor B's browser**, click "Start Consultation" for Patient X
2. **Expected Result:**
   - ✅ Consultation should **NOW be created successfully**
   - ✅ No blocking error
   - ✅ Navigate to consultation form

#### Step 4: Verify Database
```sql
-- Check patient_tracking table
SELECT 
    pt.id,
    pt.patient_id,
    pt.status,
    pt.check_in_time,
    pt.check_out_time,
    u.name as doctor_name
FROM patient_trackings pt
LEFT JOIN users u ON pt.created_by = u.id
WHERE pt.patient_id = {PATIENT_X_ID}
  AND DATE(pt.check_in_time) = CURDATE()
ORDER BY pt.check_in_time DESC;
```

**Expected:** 
- First record: `status = 'completed'`, `check_out_time` is set (Doctor A)
- Second record: `status = 'in_progress'`, `check_out_time` is null (Doctor B)

---

## 🎯 Test Scenario 3: WebSocket Connection Test

### Goal
Verify that WebSocket connection is working properly.

### Test Steps

#### Step 1: Check Browser Console
1. Open browser and login as any doctor
2. Press **F12** to open Developer Tools
3. Go to **Console** tab
4. Check for WebSocket connection messages
5. **Expected Output:**
   ```
   WebSocket connected!
   Echo initialized
   Subscribed to channel: private-doctor.{doctor_id}
   Subscribed to channel: consultations
   ```

#### Step 2: Check Network Tab
1. In Developer Tools, go to **Network** tab
2. Filter by **WS** (WebSocket)
3. Look for connection to `ws://localhost:8081`
4. **Expected:** Green status (101 Switching Protocols)

#### Step 3: Check Reverb Server
```bash
# Check if Reverb is running
ps aux | grep reverb | grep -v grep

# Check active connections
netstat -an | grep 8081 | grep ESTABLISHED
```

**Expected:** 
- Reverb process is running
- Active connections on port 8081

---

## 🔍 Debugging Checklist

If tests fail, check these items:

### ✅ Backend Checks

#### 1. Patient Tracking Records
```sql
-- Check if tracking records are being created
SELECT * FROM patient_trackings 
WHERE patient_id = {PATIENT_ID} 
ORDER BY created_at DESC 
LIMIT 5;
```

#### 2. Event Broadcasting
```php
// Add temporary logging in ConsulationController.php store() method
\Log::info('Consultation blocked', [
    'patient_id' => $request->patient_id,
    'requesting_doctor' => $request->doctor_id,
    'active_doctor' => $activeDoctor->id ?? null
]);
```

#### 3. Check Laravel Logs
```bash
tail -f /home/administrator/www/HIS/storage/logs/laravel.log | grep -i consultation
```

### ✅ Frontend Checks

#### 1. Echo Initialization
**Browser Console:**
```javascript
// Check if Echo is available
console.log(window.Echo);

// Should output: Echo object with connector, channels, etc.
```

#### 2. Channel Subscriptions
**Browser Console:**
```javascript
// Check subscribed channels
console.log(window.Echo.connector.pusher.channels);

// Should show:
// {
//   "private-doctor.{id}": Channel {...},
//   "consultations": Channel {...}
// }
```

#### 3. Manual Event Test
**Browser Console:**
```javascript
// Listen for events manually
window.Echo.private('doctor.' + {DOCTOR_ID})
  .listen('.consultation.blocked', (e) => {
    console.log('Blocked event received:', e);
  });

window.Echo.channel('consultations')
  .listen('.consultation.completed', (e) => {
    console.log('Completed event received:', e);
  });
```

### ✅ WebSocket Checks

#### 1. Reverb Server Status
```bash
# Check if running
ps aux | grep reverb

# Check logs
tail -f /home/administrator/www/HIS/storage/logs/reverb.log
```

#### 2. Port Availability
```bash
# Check if port 8081 is listening
netstat -tulpn | grep 8081

# Check firewall
sudo ufw status | grep 8081
```

#### 3. Connection Test
**Browser Console:**
```javascript
// Check connection state
window.Echo.connector.pusher.connection.state

// Should be: "connected"
```

---

## 📊 Expected Results Summary

### Scenario 1: Concurrent Blocking
| Action | Expected Result |
|--------|----------------|
| Doctor A starts consultation | ✅ Success, tracking = in_progress |
| Doctor B tries to start | ❌ 409 Error, toast notification |
| Database check | ✅ Only 1 in_progress record for patient |

### Scenario 2: Completion Notification
| Action | Expected Result |
|--------|----------------|
| Doctor A marks DONE | ✅ Tracking = completed, event broadcast |
| Doctor B's screen | ✅ Auto-refresh, patient available |
| Doctor B starts consultation | ✅ Success, new in_progress record |

### Scenario 3: WebSocket Connection
| Check | Expected Result |
|-------|----------------|
| Browser console | ✅ "WebSocket connected!" message |
| Network tab | ✅ 101 status on ws://localhost:8081 |
| Server process | ✅ Reverb running on port 8081 |

---

## 🐛 Common Issues & Solutions

### Issue 1: Toast Notification Not Appearing

**Symptoms:**
- 409 error in network tab
- No toast notification visible

**Solutions:**
1. Check if `toaster` is imported in Vue component
2. Verify `useToastr()` is called in setup
3. Check browser console for JavaScript errors
4. Verify toast CSS is loaded

**Quick Test:**
```javascript
// In browser console on consultation page
toaster.warning('Test notification');
```

### Issue 2: WebSocket Not Connecting

**Symptoms:**
- Browser console shows connection errors
- Events not received

**Solutions:**
1. Restart Reverb server:
   ```bash
   ps aux | grep reverb | grep -v grep | awk '{print $2}' | xargs kill
   cd /home/administrator/www/HIS
   php artisan reverb:start --port=8081
   ```

2. Check `.env` configuration:
   ```env
   REVERB_PORT=8081
   VITE_REVERB_PORT="${REVERB_PORT}"
   ```

3. Rebuild frontend assets:
   ```bash
   npm run build
   ```

4. Clear browser cache (Ctrl+Shift+R)

### Issue 3: Auto-Refresh Not Working

**Symptoms:**
- Completion event received
- Appointment list doesn't update

**Solutions:**
1. Check if `getAppointments()` function is called in event listener
2. Verify current page and status values are passed correctly
3. Check network tab for API call to `/api/consulations/consulationappointment/{id}`

**Debug Code:**
```javascript
// Add to .consultation.completed listener
window.Echo.channel('consultations')
  .listen('.consultation.completed', (e) => {
    console.log('Consultation completed:', e);
    console.log('Calling getAppointments with:', currentPage.value, selectedStatus.value);
    getAppointments(currentPage.value, selectedStatus.value);
  });
```

### Issue 4: Patient Still Blocked After Completion

**Symptoms:**
- Doctor A completes consultation
- Doctor B still gets blocked error

**Solutions:**
1. Check patient_tracking update query in AppointmentController
2. Verify status is being set to 'completed'
3. Check check_out_time is being set

**Database Query:**
```sql
-- Manually complete tracking if stuck
UPDATE patient_trackings 
SET status = 'completed', 
    check_out_time = NOW()
WHERE patient_id = {PATIENT_ID} 
  AND status = 'in_progress';
```

---

## 📝 Test Report Template

Use this template to document your test results:

```markdown
# Concurrent Consultation Prevention Test Report

**Test Date:** [Date]
**Tester:** [Your Name]
**System Version:** Laravel 11.45.2
**WebSocket Server:** Reverb 8081

## Test Results

### Scenario 1: Concurrent Blocking
- [ ] Doctor A successfully starts consultation
- [ ] Doctor B receives 409 error
- [ ] Toast notification appears with correct message
- [ ] Database shows only 1 in_progress record
- [ ] **Result:** PASS / FAIL
- **Notes:** [Any observations]

### Scenario 2: Completion Notification
- [ ] Doctor A successfully completes consultation
- [ ] Patient tracking updated to completed
- [ ] Doctor B's screen auto-refreshes
- [ ] Doctor B can now start consultation
- [ ] **Result:** PASS / FAIL
- **Notes:** [Any observations]

### Scenario 3: WebSocket Connection
- [ ] Browser console shows connection
- [ ] Network tab shows WS connection
- [ ] Reverb server is running
- [ ] Channels are subscribed correctly
- [ ] **Result:** PASS / FAIL
- **Notes:** [Any observations]

## Issues Found
[Describe any issues or unexpected behavior]

## Screenshots
[Attach screenshots of toast notifications, browser console, etc.]

## Recommendations
[Any suggestions for improvements]
```

---

## 🎉 Success Criteria

The system is working correctly when:

✅ **Blocking Works:**
- Patient cannot be in 2+ consultations simultaneously
- Toast notification appears within 1 second of attempt
- Error message includes active doctor's name

✅ **Completion Works:**
- Appointment list auto-refreshes within 2 seconds
- No manual page reload required
- Patient immediately available for new consultations

✅ **WebSocket Works:**
- Connection established on page load
- Events received in real-time
- No connection drops or errors

---

## 📞 Need Help?

If tests fail, check:
1. **This guide's Debugging section**
2. **CONCURRENT_CONSULTATION_PREVENTION.md** for system details
3. **Laravel logs:** `storage/logs/laravel.log`
4. **Browser console** for JavaScript errors
5. **Network tab** for API/WebSocket issues

---

**Good Luck Testing! 🚀**
