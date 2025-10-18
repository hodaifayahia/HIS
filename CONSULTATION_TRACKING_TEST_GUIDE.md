# Testing Guide: Consultation Tracking & Concurrent Prevention

## Prerequisites

1. **Laravel Reverb Running**
   ```bash
   php artisan reverb:start
   ```

2. **Two Browser Sessions**
   - Session 1: Doctor A logged in
   - Session 2: Doctor B logged in

3. **Test Patient Setup**
   - Patient X with a valid fiche_navette
   - At least one prestation in the fiche
   - Patient must be checked in (patient_tracking record with status='pending')

## Test Scenarios

### Test 1: Basic Concurrent Prevention

**Steps:**
1. **Doctor A's Browser:**
   - Navigate to consultation patients page
   - Find appointment for Patient X
   - Click "Create Consultation" button
   - ✅ Should successfully create consultation
   - ✅ PatientTracking status should change to 'in_progress'

2. **Doctor B's Browser:**
   - Navigate to consultation patients page
   - Find appointment for Patient X
   - Click "Create Consultation" button
   - ✅ Should receive warning toast: "Patient is currently in consultation with Dr. A..."
   - ✅ Should NOT navigate to consultation page
   - ✅ Should see 409 error in browser console

3. **Verify Database:**
   ```sql
   SELECT * FROM patient_trackings WHERE patient_id = [Patient X ID];
   -- status should be 'in_progress'
   -- check_out_time should be NULL
   ```

### Test 2: WebSocket Real-Time Notification

**Steps:**
1. **Doctor A's Browser:**
   - Navigate to consultation patients page
   - Click "Create Consultation" for Patient X
   - ✅ Should successfully start consultation

2. **Doctor B's Browser** (leave open):
   - Open browser console (F12)
   - Navigate to consultation patients page
   - Attempt to create consultation for Patient X
   - ✅ Should see console log: "Consultation blocked: ..."
   - ✅ Should receive warning toast immediately

3. **Verify WebSocket Connection:**
   - Check browser Network tab → WS (WebSocket)
   - Should see connection to Laravel Reverb
   - Should see incoming message with event 'consultation.blocked'

### Test 3: Consultation Completion Flow

**Steps:**
1. **Doctor A's Browser:**
   - Start consultation with Patient X (status becomes 'in_progress')
   - Complete the consultation
   - Mark appointment as DONE (status = 4)
   - ✅ Should see success message

2. **Verify Database:**
   ```sql
   SELECT * FROM patient_trackings WHERE patient_id = [Patient X ID];
   -- status should be 'completed'
   -- check_out_time should be populated with timestamp
   
   SELECT * FROM appointments WHERE patient_id = [Patient X ID];
   -- status should be 4 (DONE)
   ```

3. **Doctor B's Browser** (leave open):
   - Should see console log: "Consultation completed: ..."
   - ✅ Appointments list should automatically refresh
   - ✅ Should now be able to create consultation for Patient X

### Test 4: Multiple Completion Triggers

**Test 4a: Via nextAppointment() endpoint**
1. Doctor A starts consultation with Patient X
2. Doctor A calls nextAppointment API (creates next appointment and marks current as DONE)
3. ✅ PatientTracking should be marked 'completed'
4. ✅ ConsultationCompleted event should fire

**Test 4b: Via changeAppointmentStatus() endpoint**
1. Doctor A starts consultation with Patient X
2. Doctor A manually changes appointment status to DONE (4) via UI
3. ✅ PatientTracking should be marked 'completed'
4. ✅ ConsultationCompleted event should fire

### Test 5: Edge Cases

**Test 5a: Multiple Pending Trackings**
1. Create multiple patient_tracking records for Patient X with status='pending'
2. Doctor A creates consultation
3. ✅ ALL pending tracking records should change to 'in_progress'

**Test 5b: No Active Tracking**
1. Ensure no 'in_progress' tracking exists for Patient X
2. Doctor A creates consultation
3. ✅ Should succeed without blocking
4. ✅ Pending trackings should change to 'in_progress'

**Test 5c: Checked Out Patient**
1. Create patient_tracking with status='pending' and check_out_time populated
2. Doctor A creates consultation
3. ✅ Should NOT update this tracking (due to whereNull('check_out_time'))

## Manual Testing Checklist

### Backend Verification
- [ ] ConsultationBlocked event is fired when concurrent attempt detected
- [ ] ConsultationCompleted event is fired when appointment becomes DONE
- [ ] PatientTracking status updates correctly: pending → in_progress → completed
- [ ] check_out_time is set when status becomes 'completed'
- [ ] 409 Conflict response includes correct doctor name and prestation
- [ ] Multiple pending trackings are all updated to 'in_progress'

### Frontend Verification
- [ ] Warning toast appears on 409 response
- [ ] Toast message includes active doctor's name
- [ ] Toast message includes prestation name (if available)
- [ ] WebSocket listener receives ConsultationBlocked event
- [ ] WebSocket listener receives ConsultationCompleted event
- [ ] Appointments list refreshes on completion event
- [ ] Console logs show WebSocket events
- [ ] No memory leaks (WebSocket cleanup on unmount)

### WebSocket Verification
- [ ] Laravel Reverb server is running
- [ ] Private channel authentication works (doctor.{id})
- [ ] Public channel connection works (consultations)
- [ ] Events broadcast with correct event names
- [ ] Payload data is complete and correct
- [ ] Multiple doctors receive public channel events

## Debugging Commands

### Check PatientTracking Status
```sql
SELECT 
    pt.id,
    pt.patient_id,
    CONCAT(p.first_name, ' ', p.last_name) as patient_name,
    pt.status,
    pt.check_in_time,
    pt.check_out_time,
    pr.name as prestation_name
FROM patient_trackings pt
JOIN patients p ON pt.patient_id = p.id
LEFT JOIN prestations pr ON pt.prestation_id = pr.id
WHERE pt.status = 'in_progress'
ORDER BY pt.check_in_time DESC;
```

### Check Active Consultations
```sql
SELECT 
    c.id,
    c.patient_id,
    c.doctor_id,
    c.appointment_id,
    a.status as appointment_status,
    CONCAT(d.first_name, ' ', d.last_name) as doctor_name
FROM consulations c
JOIN appointments a ON c.appointment_id = a.id
JOIN doctors d ON c.doctor_id = d.id
WHERE a.status != 4
ORDER BY c.created_at DESC;
```

### Monitor Laravel Reverb Logs
```bash
# In terminal running reverb:start
# Look for:
# - Connection established messages
# - Broadcasting messages
# - Channel subscription messages
```

### Monitor Laravel Logs
```bash
tail -f storage/logs/laravel.log
# Look for:
# - Event broadcasting logs
# - Query logs for PatientTracking updates
# - Any error messages
```

### Browser Console Checks
```javascript
// Check Echo connection
console.log(window.Echo);

// Check subscribed channels
console.log(window.Echo.connector.channels);

// Manual test private channel
window.Echo.private('doctor.123')
    .listen('.consultation.blocked', (e) => {
        console.log('Test blocked:', e);
    });

// Manual test public channel
window.Echo.channel('consultations')
    .listen('.consultation.completed', (e) => {
        console.log('Test completed:', e);
    });
```

## Common Issues & Solutions

### Issue 1: WebSocket not connecting
**Solution:**
- Check if Reverb is running: `php artisan reverb:start`
- Verify `.env` has correct REVERB_* settings
- Check browser console for connection errors
- Verify firewall allows WebSocket connections

### Issue 2: Events not broadcasting
**Solution:**
- Check `BROADCAST_DRIVER=reverb` in `.env`
- Verify events implement `ShouldBroadcast`
- Check Laravel logs for broadcasting errors
- Run `php artisan config:clear` and restart Reverb

### Issue 3: 409 error but no toast
**Solution:**
- Check browser console for JavaScript errors
- Verify toaster is properly imported
- Check if error response has `in_consultation: true`
- Verify catch block checks `status === 409`

### Issue 4: Tracking not updating
**Solution:**
- Verify PatientTracking model import
- Check database query filters (patient_id, status, check_out_time)
- Run query manually to test conditions
- Check if tracking record exists with correct status

### Issue 5: Multiple doctors not receiving completion event
**Solution:**
- Verify using public `Channel('consultations')` not `PrivateChannel`
- Check all doctors are subscribed to consultations channel
- Verify event broadcasts on correct channel
- Check Reverb logs for broadcast confirmation

## Performance Testing

### Load Test: Multiple Concurrent Attempts
1. Simulate 5+ doctors attempting consultation simultaneously
2. ✅ Only first doctor should succeed
3. ✅ All others should receive 409 response
4. ✅ No race conditions in database updates
5. ✅ All doctors receive blocking notifications

### Stress Test: Rapid Completion Events
1. Complete 10+ consultations in quick succession
2. ✅ All completion events should broadcast
3. ✅ All doctors should receive all events
4. ✅ Frontend should refresh appropriately
5. ✅ No memory leaks or performance degradation

## Success Criteria

✅ **Concurrent prevention works 100% of the time**
✅ **Real-time notifications delivered within 1-2 seconds**
✅ **PatientTracking status accurate throughout lifecycle**
✅ **No race conditions or data inconsistencies**
✅ **Clear, user-friendly error messages**
✅ **Automatic UI refresh on completion**
✅ **No memory leaks or performance issues**
✅ **WebSocket connections stable and reliable**

## Automated Test Ideas (Future)

```php
// Feature Test Example
public function test_prevents_concurrent_consultations()
{
    $patient = Patient::factory()->create();
    $doctorA = Doctor::factory()->create();
    $doctorB = Doctor::factory()->create();
    
    // Create pending tracking
    PatientTracking::factory()->create([
        'patient_id' => $patient->id,
        'status' => 'pending',
    ]);
    
    // Doctor A starts consultation
    $this->actingAs($doctorA->user)
         ->post('/api/consulations', [
             'patient_id' => $patient->id,
             'doctor_id' => $doctorA->id,
         ])
         ->assertStatus(200);
    
    // Doctor B attempts to start consultation
    $this->actingAs($doctorB->user)
         ->post('/api/consulations', [
             'patient_id' => $patient->id,
             'doctor_id' => $doctorB->id,
         ])
         ->assertStatus(409)
         ->assertJson(['in_consultation' => true]);
}
```
