# Emergency Doctor Auto-Assignment Feature

## Overview
This feature automatically assigns an emergency doctor to a fiche navette when it's marked as an emergency case. The system checks the `DoctorEmergencyPlanning` table to find which doctor is currently on emergency duty based on the current date and time.

## Implementation Details

### 1. Database Migration
- **File**: `database/migrations/2025_10_02_123207_add_new_fieled_table_fichenavette.php`
- **Changes**: Added `emergency_doctor_id` column to `fiche_navettes` table
  - Type: `integer`
  - Nullable: `true`
  - Comment: "ID of the emergency doctor if applicable"

### 2. Model Updates

#### FicheNavette Model
- **File**: `app/Models/Reception/ficheNavette.php`
- **Changes**:
  - Added `is_emergency` and `emergency_doctor_id` to `$fillable` array
  - Added `is_emergency` boolean cast to `$casts` array
  - Added `emergencyDoctor()` relationship method linking to `Doctor` model
  - Imported `Doctor` model class

### 3. Service Layer

#### FicheNavetteService
- **File**: `app/Services/Reception/FicheNavetteService.php`
- **Changes**:
  - Imported `DoctorEmergencyPlanning` model
  - Updated `create()` method to:
    - Check if `is_emergency` is true
    - Automatically call `findAvailableEmergencyDoctor()` when emergency
    - Assign the doctor ID to the fiche navette
    - Load `emergencyDoctor.user` relationship on return
  - Added new private method `findAvailableEmergencyDoctor()`:
    - Queries `DoctorEmergencyPlanning` table
    - Filters by current date
    - Checks if current time falls within `shift_start_time` and `shift_end_time`
    - Returns the first available doctor ID
    - Fallback: Returns any doctor scheduled for today if no current shift match
    - Returns `null` if no emergency doctor found
  - Updated `list()` method to:
    - Include `emergencyDoctor.user` and `emergencyDoctor.specialization` relationships
    - Select `is_emergency` and `emergency_doctor_id` columns
  - Updated `find()` method to:
    - Include `emergencyDoctor.user` and `emergencyDoctor.specialization` relationships

### 4. API Resource

#### FicheNavetteResource
- **File**: `app/Http/Resources/Reception/FicheNavetteResource.php`
- **Changes**:
  - Added `emergency_doctor_id` field to response array
  - Added `emergency_doctor` object to response with conditional loading:
    - `id`: Emergency doctor's ID
    - `name`: Emergency doctor's user name
    - `specialization`: Emergency doctor's specialization name
  - Only includes emergency doctor info when relationship is loaded

## How It Works

### Assignment Logic Flow

1. **When creating a fiche navette**:
   ```
   User creates fiche navette → is_emergency = true?
   ↓ YES
   Query DoctorEmergencyPlanning table
   ↓
   Find doctor where:
   - planning_date = TODAY
   - is_active = true
   - shift_start_time <= CURRENT_TIME
   - shift_end_time >= CURRENT_TIME
   ↓
   Doctor found? → Assign doctor_id to emergency_doctor_id
   ↓ NO
   Fallback: Find any doctor scheduled for today
   ↓
   Still no doctor? → emergency_doctor_id = null
   ```

2. **Querying criteria**:
   - Primary: Matches exact current time within shift window
   - Fallback: Any active planning for current date
   - Result: Doctor ID or null

### API Response Example

```json
{
  "id": 123,
  "reference": "FN20251002001",
  "patient_id": 456,
  "is_emergency": true,
  "emergency_doctor_id": 789,
  "emergency_doctor": {
    "id": 789,
    "name": "Dr. Ahmed Hassan",
    "specialization": "Emergency Medicine"
  },
  "status": "pending",
  ...
}
```

## Database Requirements

### DoctorEmergencyPlanning Table Structure
The system expects the following columns:
- `doctor_id`: Foreign key to doctors table
- `planning_date`: Date of the shift
- `shift_start_time`: Time when shift starts (HH:MM:SS)
- `shift_end_time`: Time when shift ends (HH:MM:SS)
- `is_active`: Boolean flag for active planning

## Usage

### Creating Emergency Fiche Navette

```php
// From API request
$ficheNavetteService->create([
    'patient_id' => 123,
    'companion_id' => 456,
    'is_emergency' => true, // This triggers auto-assignment
]);
```

The system will automatically:
1. Find the available emergency doctor
2. Assign them to `emergency_doctor_id`
3. Return the fiche navette with doctor information loaded

### Retrieving Emergency Doctor Info

```php
// Get single fiche
$fiche = $ficheNavetteService->find($id);
echo $fiche->emergencyDoctor->user->name;

// List all fiches (includes emergency doctor if loaded)
$fiches = $ficheNavetteService->list(['is_emergency' => true]);
```

## Benefits

1. **Automatic Assignment**: No manual selection required
2. **Real-time**: Uses current date/time for accurate assignment
3. **Fallback Logic**: Graceful degradation if no exact match
4. **API Ready**: Full support in API responses
5. **Relationship Eager Loading**: Efficient database queries

## Testing Considerations

1. **Test Case 1**: Emergency fiche during active shift
   - Expected: Doctor assigned correctly
   
2. **Test Case 2**: Emergency fiche outside shift hours
   - Expected: Falls back to any scheduled doctor for the day
   
3. **Test Case 3**: Emergency fiche with no planning
   - Expected: `emergency_doctor_id` remains null
   
4. **Test Case 4**: Non-emergency fiche
   - Expected: `emergency_doctor_id` remains null

## Future Enhancements

Potential improvements:
1. Notification system to alert assigned doctor
2. Load balancing for multiple emergency doctors
3. Priority-based assignment
4. Automatic reassignment if doctor becomes unavailable
5. Emergency doctor dashboard showing assigned cases
