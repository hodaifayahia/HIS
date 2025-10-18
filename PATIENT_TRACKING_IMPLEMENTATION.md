# Patient Tracking System - Implementation Summary

## Overview
This implementation adds a complete patient tracking system to manage patient check-ins to salles (rooms) based on their prestation's specialization.

## Backend Components Created

### 1. Database Migration
**File**: `database/migrations/2025_10_05_152641_create_patient_trackings_table.php`
- Creates `patient_trackings` table with fields:
  - `fiche_navette_item_id`: Links to the fiche navette item
  - `patient_id`: Links to the patient
  - `salle_id`: Links to the assigned salle (room)
  - `specialization_id`: Links to the specialization
  - `prestation_id`: Links to the prestation
  - `check_in_time`, `check_out_time`: Timestamps for tracking
  - `status`: waiting, in_progress, completed, cancelled
  - `notes`: Optional notes
  - `created_by`: User who created the tracking

### 2. Model
**File**: `app/Models/MANAGER/PatientTracking.php`
- Relationships: patient, salle, specialization, ficheNavetteItem, prestation, creator
- Scopes: `active()` (checked in but not out), `byStatus()`

### 3. Service Layer
**File**: `app/Services/MANAGER/PatientTrackingService.php`
- Methods:
  - `checkIn()`: Check patient into a salle
  - `checkOut()`: Check patient out of a salle
  - `getCurrentPositions()`: Get all currently checked-in patients
  - `getHistory()`: Get patient tracking history with filters
  - `getAvailableSalles()`: Get salles for a specific specialization with occupancy count
  - `getSalleOccupancy()`: Get occupancy statistics for all salles

### 4. Request Classes
**Folder**: `app/Http/Requests/MANAGER/`
- `PatientTrackingCheckInRequest.php`: Validates check-in data
- `PatientTrackingCheckOutRequest.php`: Validates check-out data

### 5. Controller
**File**: `app/Http/Controllers/MANAGER/PatientTrackingController.php`
- Endpoints:
  - `POST /api/patient-tracking/check-in`: Check in a patient
  - `POST /api/patient-tracking/{trackingId}/check-out`: Check out a patient
  - `GET /api/patient-tracking/current-positions`: Get all current positions
  - `GET /api/patient-tracking/history`: Get tracking history
  - `GET /api/patient-tracking/available-salles/{specializationId}`: Get available salles
  - `GET /api/patient-tracking/salle-occupancy`: Get occupancy stats
  - `GET /api/patient-tracking/{id}`: Get single tracking record

### 6. Resource
**File**: `app/Http/Resources/MANAGER/PatientTrackingResource.php`
- Transforms patient tracking data for API responses
- Includes patient, salle, specialization, prestation details
- Calculates duration for completed trackings

### 7. Routes
**File**: `routes/web.php`
- Added patient tracking routes group under `/api/patient-tracking`

## Frontend Components Created

### 1. Check-In Modal
**File**: `resources/js/Components/Apps/manager/CheckInModal.vue`
- Features:
  - Displays prestation and patient information
  - Fetches available salles based on prestation's specialization
  - Shows salle occupancy (current patient count)
  - Allows selecting a salle and adding notes
  - Handles check-in process

### 2. Updated PrestationPatient Page
**File**: `resources/js/Pages/Apps/manager/PrestationPatient.vue`
- Added:
  - "Check-In" action button in the Actions column
  - Integration with CheckInModal component
  - Disabled check-in for prestations without specialization or those already done/canceled
  - Real-time status update after check-in

### 3. Patient Positions Page
**File**: `resources/js/Pages/Apps/manager/PatientPositions.vue`
- Features:
  - Dashboard showing occupancy stats for all salles
  - DataTable displaying all currently checked-in patients
  - Shows: patient name, prestation, salle, specialization, check-in time, duration
  - Check-out functionality with notes
  - Real-time updates after check-out
  - Badge indicators for salle occupancy

### 4. Route Configuration
**File**: `resources/js/Routes/manager.js`
- Added route: `/manger/patient-positions` → PatientPositions.vue

## Features Implemented

### Check-In Workflow
1. User clicks "Check-In" button on a prestation
2. Modal opens showing:
   - Prestation details (patient, prestation name, specialization)
   - Available salles for that specialization
   - Current occupancy count for each salle
3. User selects a salle and optionally adds notes
4. System creates patient tracking record
5. Updates fiche navette item status to "working"

### Patient Position Tracking
1. Dashboard shows all salles with:
   - Active patient count
   - Total patients processed today
   - Specialization assignment
2. Table displays all checked-in patients with:
   - Patient name
   - Prestation being performed
   - Assigned salle
   - Check-in time and duration
   - Current status
3. Check-out functionality:
   - User clicks "Check-Out" button
   - Modal allows adding notes
   - System records check-out time
   - Updates fiche item status to "done"

### Salle Selection Logic
- Only salles configured for the prestation's specialization are shown
- Salles can be configured via:
  - Default specialization assignment
  - Multiple specialization assignments (via pivot table)
- Real-time occupancy count helps with load balancing

## Database Structure

### patient_trackings Table
```sql
- id (primary key)
- fiche_navette_item_id (foreign key → fiche_navette_items)
- patient_id (foreign key → patients)
- salle_id (foreign key → salls)
- specialization_id (foreign key → specializations)
- prestation_id (foreign key → prestations)
- check_in_time (timestamp, nullable)
- check_out_time (timestamp, nullable)
- status (enum: waiting, in_progress, completed, cancelled)
- notes (text, nullable)
- created_by (foreign key → users)
- created_at, updated_at (timestamps)
- deleted_at (soft delete)
```

## API Endpoints

### POST /api/patient-tracking/check-in
Request:
```json
{
  "fiche_navette_item_id": 123,
  "salle_id": 5,
  "notes": "Patient requires special attention",
  "update_item_status": true
}
```

### POST /api/patient-tracking/{trackingId}/check-out
Request:
```json
{
  "status": "completed",
  "notes": "Procedure completed successfully",
  "update_item_status": true
}
```

### GET /api/patient-tracking/current-positions
Query params:
- `salle_id` (optional)
- `specialization_id` (optional)
- `status` (optional)

### GET /api/patient-tracking/available-salles/{specializationId}
Returns salles configured for the specialization with current occupancy

### GET /api/patient-tracking/salle-occupancy
Returns statistics for all salles

## Usage Instructions

### For Manager Users:
1. Navigate to `/manger/prestation` to see today's prestations
2. Click "Check-In" on any prestation to assign patient to a salle
3. Navigate to `/manger/patient-positions` to view current patient locations
4. Use "Check-Out" button when patient completes their prestation

### Configuration Required:
1. Salles must be configured with specializations (via Salles configuration page)
2. Prestations must have specializations assigned
3. Users need appropriate manager role permissions

## Notes
- The system automatically updates fiche navette item status during check-in/check-out
- Soft deletes are enabled for tracking records (audit trail)
- All times are recorded with full timestamps
- Duration calculations are done on the frontend for real-time display
- The User model now has a `specializations()` relationship added to support the existing codebase

## Files Modified/Created Summary
- **Created**: 10 backend files (migration, model, service, requests, controller, resource)
- **Created**: 3 frontend files (CheckInModal, PatientPositions, route)
- **Modified**: 2 files (PrestationPatient.vue, manager.js routes, web.php routes, User.php model)
