# Doctor User Linking Implementation Summary

## Overview
Modified the `ConsignmentWorkflowDataCreationTest.php` to generate doctor records based on authenticated users who are doctors, matching by name.

## Changes Made

### 1. **Updated `seedTestData()` method**
**File**: `tests/Feature/Purchasing/ConsignmentWorkflowDataCreationTest.php`

#### Before:
```php
// Create users
User::factory()->create(['name' => 'Admin User', 'email' => 'admin@test.com']);
User::factory()->create(['name' => 'Doctor User', 'email' => 'doctor@test.com']);
User::factory()->create(['name' => 'Receptionist', 'email' => 'receptionist@test.com']);

// ...

// Create doctor
Doctor::factory()->create([
    'first_name' => 'Dr.',
    'last_name' => 'Smith',
]);
```

#### After:
```php
// Create users
User::factory()->create(['name' => 'Admin User', 'email' => 'admin@test.com']);
$doctorUser = User::factory()->create(['name' => 'Dr. Smith', 'email' => 'doctor@test.com']);
User::factory()->create(['name' => 'Receptionist', 'email' => 'receptionist@test.com']);

// ...

// Create doctor linked to doctor user with special fields
Doctor::factory()->create([
    'user_id' => $doctorUser->id,
    'doctor' => $doctorUser->name,
    'is_active' => true,
]);
```

**Key improvements:**
- Doctor user is now stored in `$doctorUser` variable
- Doctor record is linked to the user via `user_id` foreign key
- Doctor's `doctor` field is populated with the user's name (`Dr. Smith`)
- `is_active` flag is set to `true`

### 2. **Updated `test_create_fiche_navette_and_payment_workflow()` method**

#### Before:
```php
// Get test data
$patient = Patient::first();
$doctor = Doctor::first();
$modality = Modality::first();
$prestations = Prestation::take(2)->get();
$caisse = Caisse::first();
$user = User::first();
```

#### After:
```php
// Get test data
$patient = Patient::first();
$user = User::first();

// Get doctor based on authenticated user who is a doctor with the same name
$doctorUser = User::where('email', 'doctor@test.com')->first();
$doctor = Doctor::where('user_id', $doctorUser->id)->first();

$modality = Modality::first();
$prestations = Prestation::take(2)->get();
$caisse = Caisse::first();
```

**Key improvements:**
- Doctor is now retrieved by finding the user with `doctor@test.com` email
- Doctor record is fetched by matching the `user_id` foreign key
- Creates a direct link between the authenticated doctor user and their doctor profile

## Architecture Pattern

```
┌──────────────────────────────────────────┐
│  User (Doctor)                           │
│  - name: "Dr. Smith"                     │
│  - email: "doctor@test.com"              │
│  - id: 2                                 │
└──────────────────────────────────────────┘
           ↑
           │ user_id FK
           │
┌──────────────────────────────────────────┐
│  Doctor (Profile)                        │
│  - user_id: 2                            │
│  - doctor: "Dr. Smith"                   │
│  - is_active: true                       │
│  - specialization_id: (optional)         │
└──────────────────────────────────────────┘
```

## Benefits

1. **User-Doctor Relationship**: Doctor records are now directly linked to user accounts
2. **Name Matching**: Doctor's name field stores the user's name for consistency
3. **Active Status**: Explicitly marks doctor as active
4. **Query by User**: Can retrieve doctor profile from authenticated user via `user_id`
5. **Data Integrity**: Eliminates separate name fields and maintains single source of truth

## Special Fields Used

- `user_id`: Foreign key linking to User model
- `doctor`: Stores the user's full name
- `is_active`: Boolean flag for doctor status

## Related Models

- **User** (`app/Models/User.php`): Contains user accounts with name and email
- **Doctor** (`app/Models/Doctor.php`): Contains doctor profile with `user_id` relationship

## Testing

The test now:
1. Creates a doctor user with name `"Dr. Smith"`
2. Creates a doctor profile linked to this user
3. Retrieves the doctor by finding the user with the matching email
4. Uses the retrieved doctor in subsequent fiche navette operations
