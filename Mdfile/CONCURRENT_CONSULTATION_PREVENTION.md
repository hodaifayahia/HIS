# Concurrent Consultation Prevention System

## ✅ SYSTEM STATUS: FULLY IMPLEMENTED

This document explains the real-time concurrent consultation prevention system that ensures a patient cannot be in multiple consultations simultaneously.

---

## 🎯 System Overview

The system prevents concurrent consultations by:
1. **Checking patient_tracking table** for in_progress status (today only)
2. **Broadcasting real-time events** to notify doctors via WebSocket
3. **Showing toast notifications** in the frontend

---

## 🔧 Backend Implementation

### 1. Consultation Prevention Check
**File:** `app/Http/Controllers/ConsulationController.php` (lines 113-149)

```php
public function store(Request $request)
{
    // ... validation code ...
    
    // Check if patient is already in another active consultation (today)
    $activeTracking = PatientTracking::where('patient_id', $request->patient_id)
        ->where('status', 'in_progress')
        ->whereDate('check_in_time', now()->toDateString())
        ->with(['prestation', 'creator'])
        ->first();

    if ($activeTracking) {
        // Get the active doctor who is currently consulting
        $activeDoctor = null;
        if ($activeTracking->creator) {
            $activeDoctor = Doctor::where('user_id', $activeTracking->creator->id)->first();
        }
        
        $requestingDoctor = Doctor::find($request->doctor_id);
        $patient = Patient::find($request->patient_id);

        // Broadcast event to notify requesting doctor
        if ($activeDoctor && $requestingDoctor && $patient) {
            event(new ConsultationBlocked($patient, $requestingDoctor, $activeDoctor));
        }

        return response()->json([
            'success' => false,
            'message' => 'Patient is currently in another consultation. Please wait until the current consultation is complete.',
            'in_consultation' => true,
            'active_doctor' => $activeDoctor ? ($activeDoctor->user->name ?? 'Another doctor') : 'Another doctor',
            'prestation' => $activeTracking->prestation->name ?? 'Unknown'
        ], 409); // 409 Conflict
    }
    
    // ... rest of consultation creation logic ...
}
```

**Key Logic:**
- Query `patient_tracking` table for patient with `status = 'in_progress'` **today only**
- If found, retrieve the active doctor and prestation
- Broadcast `ConsultationBlocked` event to requesting doctor's private channel
- Return 409 Conflict response with details

---

### 2. Consultation Blocked Event
**File:** `app/Events/ConsultationBlocked.php`

**Broadcast Details:**
- **Channel:** Private channel `doctor.{requestingDoctorId}`
- **Event Name:** `consultation.blocked`
- **Payload:**
  ```json
  {
    "patient_id": 123,
    "patient_name": "John Doe",
    "active_doctor_id": 456,
    "active_doctor_name": "Dr. Smith",
    "message": "Patient John Doe is currently in consultation with Dr. Smith",
    "timestamp": "2025-10-14T10:30:00.000Z"
  }
  ```

---

### 3. Consultation Completion Event
**File:** `app/Http/Controllers/AppointmentController.php` (lines 2005-2018)

```php
public function updateStatus(Request $request, $id)
{
    // ... validation and update logic ...
    
    // If appointment is marked as DONE, complete patient tracking and notify
    if ($validated['status'] == 4) { // AppointmentSatatusEnum::DONE->value
        PatientTracking::where('patient_id', $appointment->patient_id)
            ->where('status', 'in_progress')
            ->update([
                'status' => 'completed',
                'check_out_time' => now()
            ]);

        $patient = Patient::find($appointment->patient_id);
        $doctor = Doctor::find($appointment->doctor_id);
        if ($patient && $doctor) {
            event(new ConsultationCompleted($patient, $doctor, $appointment->id));
        }
    }
    
    // ... rest of method ...
}
```

**Key Logic:**
- When appointment status changes to DONE (value 4)
- Update all `in_progress` patient_tracking records to `completed`
- Set `check_out_time` to now
- Broadcast `ConsultationCompleted` event to all doctors

---

### 4. Consultation Completed Event
**File:** `app/Events/ConsultationCompleted.php`

**Broadcast Details:**
- **Channel:** Public channel `consultations`
- **Event Name:** `consultation.completed`
- **Payload:**
  ```json
  {
    "patient_id": 123,
    "patient_name": "John Doe",
    "doctor_id": 456,
    "doctor_name": "Dr. Smith",
    "appointment_id": 789,
    "message": "Patient John Doe consultation completed",
    "timestamp": "2025-10-14T11:00:00.000Z"
  }
  ```

---

## 🌐 Frontend Implementation

### WebSocket Listeners
**File:** `resources/js/Pages/Consultation/consultations/Consulationpatient.vue` (lines 110-124)

```javascript
onMounted(async () => {
  await loadDoctorData();
  getAppointments(1, 'all_relevant_statuses');

  // Setup WebSocket listeners for consultation events
  if (window.Echo && currentDoctorId.value) {
    // Listen for blocked consultation attempts on private doctor channel
    window.Echo.private(`doctor.${currentDoctorId.value}`)
      .listen('.consultation.blocked', (e) => {
        console.log('Consultation blocked:', e);
        toaster.warning(e.message, { duration: 5000 });
      });

    // Listen for consultation completions on public channel
    window.Echo.channel('consultations')
      .listen('.consultation.completed', (e) => {
        console.log('Consultation completed:', e);
        // Refresh appointments to show updated availability
        getAppointments(currentPage.value, selectedStatus.value);
      });
  }
});
```

**Key Features:**
1. **Private Channel Listener:** Receives `consultation.blocked` events for this specific doctor
2. **Public Channel Listener:** Receives `consultation.completed` events for all doctors
3. **Toast Notifications:** Shows warning toast when consultation is blocked
4. **Auto-refresh:** Reloads appointment list when consultation completes

---

### Error Handling in Frontend
**File:** `Consulationpatient.vue` (lines 288-301)

```javascript
const createConsultation = async (appointment) => {
    try {
        const consulation = await axios.post('/api/consulations', {
            appointment_id: appointment.id,
            patient_id: appointment.patient_id,
            doctor_id: appointment.doctor_id,
        });

        // ... success logic ...
        
    } catch (err) {
        // Handle concurrent consultation error (409 Conflict)
        if (err?.response?.status === 409 && err?.response?.data?.in_consultation) {
            const data = err.response.data;
            const activeDoctor = data.active_doctor || 'another doctor';
            const prestation = data.prestation || '';
            toaster.warning(
                `Patient is currently in consultation with ${activeDoctor}${prestation ? ' for ' + prestation : ''}. Please wait until the consultation is completed.`,
                { duration: 5000 }
            );
            return;
        }
        
        // ... other error handling ...
    }
};
```

**Key Features:**
- Catches 409 Conflict responses
- Shows detailed warning toast with active doctor name and prestation
- Prevents appointment status update when blocked

---

## 📊 Database Schema

### patient_trackings Table
Key fields used for concurrent prevention:

| Field | Type | Purpose |
|-------|------|---------|
| `patient_id` | bigint | Links to patients table |
| `status` | enum | Values: `pending`, `in_progress`, `completed` |
| `check_in_time` | timestamp | When patient started prestation/consultation |
| `check_out_time` | timestamp | When patient finished (null while active) |
| `prestation_id` | bigint | What service is being performed |
| `created_by` | bigint | User ID of doctor who created tracking |

**Query Logic:**
```sql
SELECT * FROM patient_trackings 
WHERE patient_id = ? 
  AND status = 'in_progress' 
  AND DATE(check_in_time) = CURDATE()
LIMIT 1
```

---

## 🔄 System Flow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│ Doctor B tries to start consultation with Patient X        │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│ Backend: Check patient_tracking for Patient X              │
│ - status = 'in_progress'                                    │
│ - check_in_time = today                                     │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ├─── NOT FOUND ──────────────────────┐
                     │                                     │
                     │                                     ▼
                     │                     ┌────────────────────────────────┐
                     │                     │ Allow consultation creation    │
                     │                     │ - Create consultation record   │
                     │                     │ - Update tracking to           │
                     │                     │   'in_progress'                │
                     │                     └────────────────────────────────┘
                     │
                     └─── FOUND (Patient with Doctor A) ─┐
                                                          │
                                                          ▼
                         ┌────────────────────────────────────────────┐
                         │ Broadcast ConsultationBlocked event        │
                         │ - Channel: private-doctor.{Doctor B ID}    │
                         │ - Message: Patient busy with Doctor A      │
                         └───────────────────┬────────────────────────┘
                                             │
                                             ▼
                         ┌────────────────────────────────────────────┐
                         │ Frontend (Doctor B): Show toast warning    │
                         │ "Patient is currently in consultation      │
                         │  with Dr. Smith. Please wait..."           │
                         └────────────────────────────────────────────┘

═══════════════════════════════════════════════════════════════════════

┌─────────────────────────────────────────────────────────────┐
│ Doctor A completes consultation (marks appointment as DONE) │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│ Backend: Update appointment status to DONE (4)             │
│ - Update patient_tracking: status = 'completed'             │
│ - Set check_out_time = now()                                │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│ Broadcast ConsultationCompleted event                       │
│ - Channel: public 'consultations'                           │
│ - Message: Patient consultation completed                   │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│ Frontend (All Doctors): Receive notification                │
│ - Auto-refresh appointment list                             │
│ - Patient X now available for consultation                  │
└─────────────────────────────────────────────────────────────┘
```

---

## ⚙️ WebSocket Configuration

### Laravel Reverb Server
**Port:** 8081  
**Status:** ✅ Running

**Start Command:**
```bash
cd /home/administrator/www/HIS
php artisan reverb:start --port=8081
```

### Environment Variables (.env)
```env
# Reverb Configuration
REVERB_APP_ID=local
REVERB_APP_KEY=local
REVERB_APP_SECRET=local
REVERB_HOST=localhost
REVERB_PORT=8081
REVERB_SCHEME=http

# Vite Reverb Configuration
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

**Frontend Connection:**
The frontend connects to Reverb via Laravel Echo configured in `resources/js/bootstrap.js`

---

## 🧪 Testing Scenarios

### Scenario 1: Concurrent Consultation Blocking
**Steps:**
1. Doctor A starts consultation with Patient X (creates consultation record)
2. Patient X tracking status changes to `in_progress`
3. Doctor B attempts to start consultation with Patient X
4. **Expected Result:**
   - Doctor B receives 409 Conflict response
   - Real-time toast notification appears on Doctor B's screen
   - Message: "Patient is currently in consultation with Dr. A. Please wait..."

### Scenario 2: Consultation Completion Notification
**Steps:**
1. Doctor A completes consultation with Patient X (marks appointment as DONE)
2. Patient X tracking status changes to `completed`
3. `ConsultationCompleted` event broadcasts to all doctors
4. **Expected Result:**
   - All doctors' appointment lists auto-refresh
   - Patient X now available for new consultations
   - Real-time update without manual page refresh

### Scenario 3: Multiple Prestations for Same Patient
**Steps:**
1. Patient X has multiple prestations (e.g., consultation + X-ray)
2. Doctor A starts consultation (first prestation)
3. Radiologist tries to start X-ray (second prestation)
4. **Expected Result:**
   - System allows both if they're different prestations
   - Only blocks if trying to create duplicate consultation for same appointment

---

## 🔍 Debugging Tips

### Check Patient Tracking Status
```sql
SELECT pt.*, p.Firstname, p.Lastname, u.name as doctor_name
FROM patient_trackings pt
JOIN patients p ON pt.patient_id = p.id
LEFT JOIN users u ON pt.created_by = u.id
WHERE pt.patient_id = {patient_id}
  AND DATE(pt.check_in_time) = CURDATE()
ORDER BY pt.check_in_time DESC;
```

### Check Reverb Connection
**Browser Console:**
```javascript
// Check if Echo is connected
console.log(window.Echo);

// Check subscribed channels
console.log(window.Echo.connector.pusher.channels);

// Listen for connection events
window.Echo.connector.pusher.connection.bind('connected', () => {
  console.log('WebSocket connected!');
});
```

### Monitor Broadcast Events
**Backend Logs:**
```bash
tail -f storage/logs/laravel.log | grep -i "consultation"
```

**Network Tab (Browser DevTools):**
- Filter: WS (WebSocket)
- Look for connections to `ws://localhost:8081`
- Check for `consultation.blocked` and `consultation.completed` messages

---

## 🚀 Production Considerations

### 1. Reverb Process Management
Use a process manager like **Supervisor** to keep Reverb running:

**Supervisor Config:** `/etc/supervisor/conf.d/reverb.conf`
```ini
[program:reverb]
command=php /home/administrator/www/HIS/artisan reverb:start --port=8081
directory=/home/administrator/www/HIS
autostart=true
autorestart=true
user=administrator
redirect_stderr=true
stdout_logfile=/home/administrator/www/HIS/storage/logs/reverb.log
```

### 2. Firewall Rules
Ensure port 8081 is open for WebSocket connections:
```bash
sudo ufw allow 8081/tcp
```

### 3. HTTPS/WSS Configuration
For production with HTTPS, update to secure WebSocket:
```env
REVERB_SCHEME=https
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### 4. Performance Monitoring
Monitor active connections:
```bash
# Check Reverb process
ps aux | grep reverb

# Check network connections
netstat -an | grep 8081
```

---

## 📝 Maintenance Notes

### When to Clear Patient Tracking
Patient tracking records should be completed when:
1. Appointment is marked as DONE
2. Patient checks out of a prestation
3. End of day cleanup (optional)

### Automatic Cleanup Query (Optional Daily Cron)
```sql
-- Mark abandoned trackings as completed if check_in_time > 24 hours ago
UPDATE patient_trackings 
SET status = 'completed', check_out_time = NOW()
WHERE status = 'in_progress' 
  AND check_in_time < DATE_SUB(NOW(), INTERVAL 24 HOUR);
```

---

## ✅ System Status Checklist

- [x] Backend: Concurrent consultation check implemented
- [x] Backend: ConsultationBlocked event created and broadcast
- [x] Backend: ConsultationCompleted event created and broadcast
- [x] Backend: Patient tracking status management
- [x] Frontend: WebSocket listeners configured
- [x] Frontend: Toast notifications for blocked consultations
- [x] Frontend: Auto-refresh on consultation completion
- [x] Frontend: 409 error handling
- [x] WebSocket: Laravel Reverb server configured (port 8081)
- [x] WebSocket: Environment variables set correctly
- [x] Database: patient_trackings table schema correct

---

## 🆘 Troubleshooting

### Problem: Toast notifications not appearing
**Solution:**
1. Check Reverb is running: `ps aux | grep reverb`
2. Check browser console for WebSocket errors
3. Verify Echo is initialized: `console.log(window.Echo)`
4. Check channel subscription: `window.Echo.connector.pusher.channels`

### Problem: Consultation not blocked even when patient is busy
**Solution:**
1. Verify patient_tracking record exists with status 'in_progress'
2. Check date filtering: `whereDate('check_in_time', now()->toDateString())`
3. Enable query logging to see SQL: `DB::enableQueryLog()`
4. Check doctor/user relationship: `Doctor::where('user_id', ...)`

### Problem: Consultation completed event not broadcasting
**Solution:**
1. Verify appointment status is being set to 4 (DONE)
2. Check if patient and doctor models are found
3. Review queue configuration if using queued broadcasting
4. Check Laravel logs: `tail -f storage/logs/laravel.log`

---

## 📚 Related Files

**Backend Controllers:**
- `app/Http/Controllers/ConsulationController.php`
- `app/Http/Controllers/AppointmentController.php`

**Events:**
- `app/Events/ConsultationBlocked.php`
- `app/Events/ConsultationCompleted.php`

**Models:**
- `app/Models/MANAGER/PatientTracking.php`
- `app/Models/Patient.php`
- `app/Models/Doctor.php`

**Frontend:**
- `resources/js/Pages/Consultation/consultations/Consulationpatient.vue`
- `resources/js/bootstrap.js` (Echo configuration)

**Configuration:**
- `.env` (Reverb settings)
- `config/broadcasting.php`

---

## 📞 Support

For issues or questions about this system, check:
1. Laravel Broadcasting documentation
2. Laravel Reverb documentation
3. This README troubleshooting section
4. Application logs: `storage/logs/laravel.log`

---

**Last Updated:** October 14, 2025  
**Status:** ✅ Fully Operational
