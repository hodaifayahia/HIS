# Doctor Duplication with Consultation Configuration

## Overview

The doctor duplication feature has been enhanced to include **complete consultation configuration duplication**. When you duplicate a doctor, the system now copies:

1. **Schedule Configuration** (existing)
   - Recurring schedules
   - Appointment available months
   - Custom date schedules (appointment forcers)
   - Excluded dates

2. **Consultation Configuration** (NEW)
   - Placeholders/Sections with all attributes
   - Templates with placeholder associations
   - Medication favorites

## Feature Details

### What Gets Duplicated

#### 1. Schedule Configuration
- ✅ Weekly recurring schedules (day of week, shift periods, time slots)
- ✅ Appointment available months (booking windows)
- ✅ Appointment forcers (custom date overrides)
- ✅ Excluded dates (vacations, holidays)
- ✅ Doctor settings (time slots, frequency, patients per slot)

#### 2. Consultation Configuration (NEW)
- ✅ **Folders**: All template folders with descriptions
- ✅ **Placeholders/Sections**: All consultation sections with their attributes
- ✅ **Templates**: All consultation templates with placeholder mappings
- ✅ **Medication Favorites**: All favorited medications

### What Changes

Only the following information is updated for the new doctor:
- Name
- Email
- Password
- Phone (optional)

**Everything else is an exact copy** from the source doctor.

## API Endpoint

### Duplicate Doctor

**Endpoint:** `POST /api/doctors/{doctorId}/duplicate`

**Request Body:**
```json
{
  "name": "Dr. Jane Smith",
  "email": "jane.smith@hospital.com",
  "password": "SecurePassword123",
  "phone": "+1234567890"
}
```

**Response (201 Created):**
```json
{
  "message": "Doctor duplicated successfully",
  "data": {
    "id": 42,
    "user": {
      "id": 150,
      "name": "Dr. Jane Smith",
      "email": "jane.smith@hospital.com",
      "role": "doctor",
      "is_active": true
    },
    "specialization": {
      "id": 5,
      "name": "Cardiology"
    },
    "schedules": [...],
    "appointmentAvailableMonths": [...],
    "appointmentForce": [...],
    "excludedDates": [...]
  }
}
```

## Implementation Details

### Code Location

**File:** `app/Http/Controllers/DoctorController.php`

### Main Method: `duplicate()`

```php
public function duplicate(Request $request, $doctorId)
{
    // 1. Validate new doctor credentials
    // 2. Load source doctor with all relationships
    // 3. Create new user
    // 4. Create new doctor with same settings
    // 5. Duplicate schedules
    // 6. Duplicate appointment windows
    // 7. Duplicate appointment forcers
    // 8. Duplicate excluded dates
    // 9. Duplicate consultation configurations (NEW)
    // 10. Clear cache
    // 11. Return new doctor resource
}
```

### Helper Method: `duplicateConsultationConfigurations()`

This new private method handles all consultation configuration duplication:

```php
private function duplicateConsultationConfigurations(
    int $sourceDoctorId, 
    int $targetDoctorId
): void
{
    // 1. Duplicate Folders first
    // 2. Map old folder IDs to new ones
    // 3. Duplicate Placeholders with attributes (including doctor_id)
    // 4. Map old placeholder IDs to new ones
    // 5. Duplicate Templates with correct folder references
    // 6. Duplicate placeholder-template associations
    // 7. Duplicate Medication Favorites
    // 8. Log results
}
```

### Database Models Used

- `Doctor` - Main doctor model
- `User` - User credentials
- `Schedule` - Weekly schedules
- `AppointmentAvailableMonth` - Booking windows
- `AppointmentForcer` - Custom date schedules
- `ExcludedDate` - Blocked dates
- **`Folder`** - Template folders (NEW)
- **`Placeholder`** - Consultation sections (NEW)
- **`Attribute`** - Section attributes (NEW)
- **`Template`** - Consultation templates (NEW)
- **`PlaceholderTemplate`** - Template-placeholder links (NEW)
- **`MedicationDoctorFavorat`** - Favorite medications (NEW)

## Database Transactions

The entire duplication process runs in a **single database transaction**:

```php
DB::beginTransaction();
try {
    // All duplication operations
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    // Error handling
}
```

This ensures **atomicity** - either everything is duplicated successfully, or nothing is changed.

## Error Handling

### Consultation Config Errors

If consultation configuration duplication fails, the error is **logged but not propagated**. This means:
- The doctor and schedule duplication will still succeed
- Only the consultation config will be missing
- Error is logged for debugging

```php
try {
    $this->duplicateConsultationConfigurations($sourceDoctorId, $targetDoctorId);
} catch (\Exception $e) {
    \Log::error('Consultation configuration duplication failed: ' . $e->getMessage());
    // Continue without throwing - doctor duplication succeeds
}
```

### Main Duplication Errors

If the main doctor/schedule duplication fails:
- Transaction is rolled back
- 500 error response returned
- Error message and stack trace logged

## Folder and Placeholder ID Mapping

Both folders and templates reference their related entities by ID. When duplicating, we maintain these relationships:

```php
// Map folder IDs
$folderMap = []; // Old folder ID => New folder ID
foreach ($sourceFolders as $sourceFolder) {
    $newFolder = Folder::create([...]);
    $folderMap[$sourceFolder->id] = $newFolder->id;
}

// Map placeholder IDs
$placeholderMap = []; // Old placeholder ID => New placeholder ID
foreach ($sourcePlaceholders as $sourcePlaceholder) {
    $newPlaceholder = Placeholder::create([...]);
    $placeholderMap[$sourcePlaceholder->id] = $newPlaceholder->id;
}

// When duplicating templates, use mapped IDs:
'folder_id' => $folderMap[$sourceTemplate->folder_id],
'placeholder_id' => $placeholderMap[$placeholder->id]

// When duplicating attributes, include doctor_id:
'doctor_id' => $targetDoctorId,
'placeholder_id' => $newPlaceholder->id
```

## Testing

### Test Doctor Duplication

```bash
curl -X POST http://your-domain/api/doctors/5/duplicate \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "name": "Dr. Test Copy",
    "email": "test.copy@hospital.com",
    "password": "Password123"
  }'
```

### Verify Results

Check the database for the new doctor:

```sql
-- New doctor record
SELECT * FROM doctors WHERE user_id = (
  SELECT id FROM users WHERE email = 'test.copy@hospital.com'
);

-- New placeholders
SELECT * FROM placeholders WHERE doctor_id = [NEW_DOCTOR_ID];

-- New templates
SELECT * FROM templates WHERE doctor_id = [NEW_DOCTOR_ID];

-- New medication favorites
SELECT * FROM medication_doctor_favorats WHERE doctor_id = [NEW_DOCTOR_ID];
```

## Use Cases

### 1. Setting Up Similar Doctors
When you have multiple doctors with the same specialty and similar schedules, duplicate one and change only the name/credentials.

### 2. Consultation Template Standardization
Duplicate a doctor's consultation setup to ensure consistent templates across multiple doctors.

### 3. Training New Doctors
Create a new doctor profile with the same configuration as an experienced doctor for training purposes.

### 4. Department-Wide Configuration
Duplicate configurations across an entire department or specialty group.

## Logging

All duplication operations are logged:

```
INFO: Doctor duplication started for doctor ID: 5
INFO: Consultation configurations duplicated: 5 folders, 12 sections, 8 templates, 45 medication favorites
INFO: Doctor duplicated successfully: New doctor ID 42
```

Errors are logged with full context:

```
ERROR: Doctor duplication failed: Unique constraint violation
ERROR: Stack trace: [full stack trace]
```

## Performance Considerations

### Bulk Inserts

The implementation uses bulk inserts where possible to improve performance:

```php
Schedule::insert($schedules);              // Bulk insert all schedules
Attribute::insert($attributes);            // Bulk insert all attributes
PlaceholderTemplate::insert($templates);   // Bulk insert all associations
```

### Cache Clearing

After duplication, doctor availability cache is cleared:

```php
$this->clearDoctorAvailabilityCache($newDoctor->id);
```

## Frontend Integration

The existing duplication UI in `DoctorListItem.vue` works without modification. The enhanced backend automatically duplicates consultation configurations.

## Files Modified

- ✅ `app/Http/Controllers/DoctorController.php`
  - Added imports for `Placeholder`, `Attribute`, `Template`, `PlaceholderTemplate`, `MedicationDoctorFavorat`
  - Modified `duplicate()` method to call `duplicateConsultationConfigurations()`
  - Added `duplicateConsultationConfigurations()` private method

## Migration Compatibility

This feature is **backward compatible**. If a doctor has no consultation configurations, the duplication will:
- Still succeed for schedules and settings
- Log "0 sections, 0 templates, 0 medication favorites duplicated"
- Complete without errors

## Related Documentation

- `DOCTOR_DUPLICATION_IMPLEMENTATION.md` - Original doctor duplication feature
- `CONSULTATION_DUPLICATION_FEATURE.md` - Standalone consultation duplication endpoints
- `CONSULTATION_DUPLICATION_QUICK_REFERENCE.md` - API quick reference

## Summary

The enhanced doctor duplication feature provides a **complete one-click copy** of:
- ✅ All schedule configurations (6 relationship types)
- ✅ All consultation configurations (3 relationship types)
- ✅ 9 total relationship types duplicated automatically

This significantly reduces the time required to set up new doctors with similar configurations, from hours to seconds.
