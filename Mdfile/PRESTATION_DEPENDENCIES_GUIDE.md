# Prestation Dependencies & Package Detection Implementation Guide

## Overview
This implementation adds intelligent dependency handling and automatic package detection when creating appointments with prestations.

## Features Implemented

### 1. **Frontend: Dependency Selection UI**
Location: `resources/js/Pages/Appointments/AppointmentForm.vue`

**What happens when user selects prestations:**
- ✅ Automatically fetches dependencies from `required_prestations_info`
- ✅ Displays dependencies as checkboxes for user to select
- ✅ Shows patient instructions from selected prestations
- ✅ Auto-fills description field with patient instructions
- ✅ Combines selected prestations + selected dependencies for submission

**UI Components Added:**
- Dependencies section (with checkboxes)
- Patient instructions display box
- Styled with clear visual separation

### 2. **Backend: API Enhancement**
Location: `app/Http/Controllers/Reception/ficheNavetteController.php`

**Endpoint:** `GET /api/reception/prestations/by-specialization/{specializationId}`

**Returns:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "MRI Scan",
      "required_prestations_info": [2, 3],  // Dependency IDs
      "patient_instructions": "Fast for 6 hours before scan",
      ...
    }
  ]
}
```

### 3. **Backend: Package Detection Logic**
Location: `app/Http/Controllers/AppointmentController.php`

**New Method:** `findMatchingPackage(array $prestationIds): ?PrestationPackage`

**How it works:**
1. Receives array of selected prestation IDs
2. Sorts them for consistent comparison
3. Queries all active packages (`is_active = true`)
4. Compares selected prestations with each package's items
5. Returns package if exact match found, null otherwise

**Store Logic Flow:**
```
User submits appointment with prestations
  ↓
Collect all prestation IDs (main + dependencies)
  ↓
Check: Do they match an active package?
  ↓
YES → Store ONE record with package_id
  ↓
NO → Store INDIVIDUAL records with patient_instructions as description
```

### 4. **Database Changes**

**Migration:** `2025_10_07_184906_add_package_id_to_appointment_prestations_table.php`

**Added Column:**
- `package_id` (nullable, foreign key to `prestation_packages`)

**Updated Model:** `app/Models/Appointment/AppointmentPrestation.php`
- Added `package_id` to fillable
- Added `package()` relationship

## Usage Example

### Scenario 1: Individual Prestations
**User selects:**
- Prestation A (has dependencies: B, C)
- User also selects dependency B

**Backend stores:**
```sql
INSERT INTO appointment_prestations (prestation_id, description, package_id)
VALUES 
  (A, 'Instructions for A', NULL),
  (B, 'Instructions for B', NULL);
```

### Scenario 2: Package Match
**User selects:**
- Prestation A, B, C
- Active package "Lab Package" contains exactly: A, B, C

**Backend stores:**
```sql
INSERT INTO appointment_prestations (prestation_id, package_id, description)
VALUES (A, 123, 'Package: Lab Package');
-- Only ONE record, references the package
```

## Data Flow Diagram

```
[User Interface]
    ↓
Select Prestation(s)
    ↓
fetchDependenciesAndInstructions()
    ├── Extract required_prestations_info
    ├── Show as checkboxes
    └── Display patient_instructions
    ↓
User optionally selects dependencies
    ↓
Submit (prestations + dependencies combined)
    ↓
[Backend]
    ↓
findMatchingPackage()
    ├── Match found? → Store package_id
    └── No match? → Store individual with instructions
    ↓
Database: appointment_prestations table
```

## Testing Checklist

### Manual Testing
1. ✅ Create appointment
2. ✅ Select time slot
3. ✅ Choose a prestation with dependencies
4. ✅ Verify dependencies appear as checkboxes
5. ✅ Verify patient instructions appear
6. ✅ Select some dependencies
7. ✅ Submit appointment
8. ✅ Check DB: verify records in `appointment_prestations`

### Package Detection Test
1. Create a package with prestations [1, 2, 3]
2. Set package `is_active = true`
3. Create appointment with prestations [1, 2, 3]
4. Verify: `package_id` is set, only ONE record created

### Individual Prestations Test
1. Create appointment with prestations [1, 2]
2. No matching package exists
3. Verify: TWO records created, `package_id = NULL`
4. Verify: Each has `patient_instructions` in description

## API Endpoints

### Get Prestations by Specialization
```bash
GET /api/reception/prestations/by-specialization/{specializationId}
```

**Response includes:**
- `required_prestations_info` (array of dependency IDs)
- `patient_instructions` (text)

## Database Schema

### appointment_prestations
```sql
CREATE TABLE appointment_prestations (
  id BIGINT PRIMARY KEY,
  appointment_id BIGINT FK -> appointments,
  prestation_id BIGINT FK -> prestations,
  package_id BIGINT FK -> prestation_packages (NULLABLE),
  patient_id BIGINT FK -> patients,
  description TEXT,
  appointment_date TIMESTAMP,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

## Key Files Modified

### Frontend
- `resources/js/Pages/Appointments/AppointmentForm.vue`
  - Added dependency UI
  - Added patient instructions display
  - Added fetchDependenciesAndInstructions()
  - Updated handleSubmit to combine prestations + dependencies

### Backend
- `app/Http/Controllers/AppointmentController.php`
  - Added findMatchingPackage() method
  - Updated store() with package detection logic
  - Added patient_instructions as description
  
- `app/Http/Controllers/Reception/ficheNavetteController.php`
  - Enhanced getPrestationsBySpecialization() to return patient_instructions

- `app/Models/Appointment/AppointmentPrestation.php`
  - Added package_id fillable
  - Added package() relationship

### Database
- `database/migrations/2025_10_07_184906_add_package_id_to_appointment_prestations_table.php`
  - Added package_id column

## Troubleshooting

### Dependencies not showing?
- Check that prestation has `required_prestations_info` populated
- Verify JSON format: `[1, 2, 3]` (array of integers)

### Package not detected?
- Verify package `is_active = true`
- Check prestation IDs match EXACTLY (order doesn't matter, but set must be identical)
- Use logs: check `storage/logs/laravel.log` for "Package matched" or "no package match"

### Patient instructions not appearing?
- Verify prestation has `patient_instructions` field populated
- Check browser console for fetch errors

## Future Enhancements
- [ ] Add package preview before submission
- [ ] Show package price vs individual prices
- [ ] Allow partial package matching with suggestions
- [ ] Add dependency tree visualization
- [ ] Cache package lookups for performance
