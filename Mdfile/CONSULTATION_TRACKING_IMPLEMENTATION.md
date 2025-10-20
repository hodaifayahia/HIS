# Consultation Tracking & Concurrent Consultation Prevention

## Overview
This implementation provides a comprehensive consultation tracking system that:
- Prevents multiple doctors from consulting the same patient simultaneously
- Updates `patient_tracking` status through the consultation lifecycle
- Sends real-time WebSocket notifications to doctors via Laravel Reverb
- Provides clear user feedback for consultation blocking scenarios

## Status Flow

### PatientTracking Status Lifecycle
```
pending → in_progress → completed
```

1. **pending**: Patient has checked in and is waiting in a salle
2. **in_progress**: Doctor has started a consultation with the patient
3. **completed**: Consultation is done (appointment status = DONE/4)

## Backend Implementation

### 1. Event Classes Created

#### `/app/Events/ConsultationBlocked.php`
- **Purpose**: Notify requesting doctor when patient is already in consultation
- **Trigger**: When doctor attempts to start consultation for patient already in consultation
- **Channel**: `PrivateChannel('doctor.{requestingDoctorId}')`
- **Event Name**: `consultation.blocked`
- **Payload**:
  ```php
  [
      'patient_id' => $patient->id,
      'patient_name' => $patient->first_name . ' ' . $patient->last_name,
      'active_doctor' => $activeDoctor->first_name . ' ' . $activeDoctor->last_name,
      'requesting_doctor_id' => $requestingDoctorId,
      'prestation' => $tracking->prestation->name ?? null,
      'message' => 'Patient is currently in consultation with...',
      'timestamp' => now()
  ]
  ```

#### `/app/Events/ConsultationCompleted.php`
- **Purpose**: Notify all doctors when a consultation completes
- **Trigger**: When appointment status changes to DONE (4)
- **Channel**: `Channel('consultations')` (public)
- **Event Name**: `consultation.completed`
- **Payload**:
  ```php
  [
      'patient_id' => $patient->id,
      'patient_name' => $patient->first_name . ' ' . $patient->last_name,
      'doctor_name' => $doctor->first_name . ' ' . $doctor->last_name,
      'appointment_id' => $appointmentId,
      'message' => 'Consultation completed. Patient is now available.',
      'timestamp' => now()
  ]
  ```

### 2. Controller Updates

#### `/app/Http/Controllers/ConsulationController.php`
**Modified**: `store()` method

**Key Logic**:
```php
// 1. Check for concurrent consultations
$activeTracking = PatientTracking::where('patient_id', $request->patient_id)
    ->where('status', 'in_progress')
    ->first();

if ($activeTracking) {
    // Fire blocking event
    event(new ConsultationBlocked($patient, $activeDoctor, $doctor->id, $activeTracking));
    
    // Return 409 Conflict
    return response()->json([
        'success' => false,
        'message' => 'Patient is currently in another consultation...',
        'in_consultation' => true,
        'active_doctor' => $activeDoctor->first_name . ' ' . $activeDoctor->last_name,
        'prestation' => $activeTracking->prestation->name ?? null
    ], 409);
}

// 2. Create consultation (existing logic)

// 3. Update tracking status to in_progress
PatientTracking::where('patient_id', $request->patient_id)
    ->where('status', 'pending')
    ->whereNull('check_out_time')
    ->update(['status' => 'in_progress']);
```

#### `/app/Http/Controllers/AppointmentController.php`
**Modified**: Two methods updated

##### Method 1: `nextAppointment()` (line ~1798)
```php
$existingAppointment->update(['status' => 4]); // Mark as DONE

// Update patient tracking to completed
PatientTracking::where('patient_id', $existingAppointment->patient_id)
    ->where('status', 'in_progress')
    ->update([
        'status' => 'completed',
        'check_out_time' => now()
    ]);

// Fire completion event
$patient = Patient::find($existingAppointment->patient_id);
$doctor = Doctor::find($existingAppointment->doctor_id);
if ($patient && $doctor) {
    event(new ConsultationCompleted($patient, $doctor, $existingAppointment->id));
}
```

##### Method 2: `changeAppointmentStatus()` (line ~1976)
```php
$appointment->save();

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
```

**New Imports Added**:
```php
use App\Models\MANAGER\PatientTracking;
use App\Events\ConsultationCompleted;
```

## Frontend Implementation

### `/resources/js/Pages/Consultation/consultations/Consulationpatient.vue`

#### 1. Enhanced Error Handling in `createConsultation()`
```javascript
catch (err) {
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
    
    // ... existing error handling
}
```

#### 2. WebSocket Listeners in `onMounted()`
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

#### 3. Cleanup in `onBeforeUnmount()`
```javascript
onBeforeUnmount(() => {
  if (window.Echo && currentDoctorId.value) {
    window.Echo.leave(`doctor.${currentDoctorId.value}`);
    window.Echo.leave('consultations');
  }
});
```

**New Imports Added**:
```javascript
import { ref, onMounted, onBeforeUnmount, computed, watch } from 'vue';
```

## User Experience Flow

### Scenario: Doctor A is consulting Patient X

1. **Doctor A starts consultation** (`createConsultation()`)
   - Backend creates consultation
   - PatientTracking status: `pending` → `in_progress`
   - Returns success

2. **Doctor B attempts to start consultation with same patient**
   - Backend detects `in_progress` status
   - Fires `ConsultationBlocked` event to Doctor B's private channel
   - Returns 409 Conflict with details
   - Frontend shows warning toast: "Patient is currently in consultation with Dr. A for Cardiology. Please wait until the consultation is completed."

3. **Doctor A completes consultation**
   - Appointment status changes to DONE (4)
   - Backend updates PatientTracking: `in_progress` → `completed`, sets `check_out_time`
   - Fires `ConsultationCompleted` event on public channel
   - All doctors (including Doctor B) receive notification
   - Frontend automatically refreshes appointments list
   - Doctor B can now start consultation

## WebSocket Channels

### Private Channel: `doctor.{id}`
- **Purpose**: Doctor-specific notifications
- **Events**: `consultation.blocked`
- **Authentication**: Required (Laravel Sanctum/Passport)

### Public Channel: `consultations`
- **Purpose**: General consultation events
- **Events**: `consultation.completed`
- **Authentication**: None required

## Testing Checklist

- [ ] **Concurrent Prevention**: Try creating two consultations for same patient simultaneously
  - Verify second doctor receives 409 response
  - Verify warning toast appears with correct doctor name
  
- [ ] **WebSocket Blocking**: Second doctor should receive real-time notification
  - Check browser console for `Consultation blocked:` log
  - Verify toast warning appears
  
- [ ] **Status Updates**: 
  - Verify tracking goes from `pending` → `in_progress` on consultation start
  - Verify tracking goes to `completed` when appointment becomes DONE
  - Verify `check_out_time` is set on completion
  
- [ ] **Completion Broadcasting**: When consultation completes
  - Verify all doctors receive notification on `consultations` channel
  - Verify appointments list refreshes automatically
  - Verify second doctor can now create consultation
  
- [ ] **Multiple Completion Triggers**:
  - Test via `nextAppointment()` endpoint
  - Test via `changeAppointmentStatus()` endpoint
  - Both should trigger tracking completion and event

## Database Schema Reference

### `patient_trackings` Table
```sql
- id
- fiche_navette_item_id
- patient_id (FK to patients)
- salle_id (FK to salles)
- specialization_id (FK to specializations)
- prestation_id (FK to prestations)
- check_in_time (datetime)
- check_out_time (datetime, nullable)
- status (enum: 'pending', 'in_progress', 'completed')
- notes (text, nullable)
- created_by (FK to users)
- timestamps
```

## Configuration Requirements

### Laravel Reverb
Ensure Laravel Reverb is running:
```bash
php artisan reverb:start
```

### Broadcasting Configuration
In `.env`:
```env
BROADCAST_DRIVER=reverb
```

In `config/broadcasting.php`:
```php
'reverb' => [
    'driver' => 'reverb',
    'key' => env('REVERB_APP_KEY'),
    'secret' => env('REVERB_APP_SECRET'),
    'app_id' => env('REVERB_APP_ID'),
    'options' => [
        'host' => env('REVERB_HOST', '0.0.0.0'),
        'port' => env('REVERB_PORT', 8080),
        'scheme' => env('REVERB_SCHEME', 'http'),
    ],
],
```

### Frontend Echo Configuration
In `bootstrap.js`:
```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    wssPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
```

## Files Modified

### Backend
1. `/app/Events/ConsultationBlocked.php` (NEW)
2. `/app/Events/ConsultationCompleted.php` (NEW)
3. `/app/Http/Controllers/ConsulationController.php` (MODIFIED)
4. `/app/Http/Controllers/AppointmentController.php` (MODIFIED)

### Frontend
1. `/resources/js/Pages/Consultation/consultations/Consulationpatient.vue` (MODIFIED)

## Notes

- The system handles multiple appointment completion paths (nextAppointment, changeAppointmentStatus)
- Events are only fired when patient and doctor models are successfully loaded
- WebSocket listeners are properly cleaned up on component unmount to prevent memory leaks
- The 409 Conflict response provides detailed information for user-friendly error messages
- Automatic appointment refresh ensures doctors see up-to-date patient availability

## Related Documentation

- See `PATIENT_TRACKING_IMPLEMENTATION.md` for the initial patient tracking setup
- See Laravel Broadcasting documentation: https://laravel.com/docs/broadcasting
- See Laravel Reverb documentation: https://reverb.laravel.com
