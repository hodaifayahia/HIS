# Doctor Model Documentation

## Overview
The Doctor model represents medical doctors in the Hospital Information System (HIS). It manages doctor profiles, specializations, scheduling preferences, and appointment configurations.

## Database Table
**Table Name:** `doctors`

## Fillable Attributes

| Attribute | Type | Description |
|-----------|------|-------------|
| `specialization_id` | integer | Foreign key to specializations table |
| `frequency` | integer | Working frequency (days per week) |
| `specific_date` | date | Specific working date (optional) |
| `patients_based_on_time` | integer | Number of patients per time slot |
| `add_to_waitlist` | boolean | Whether to add patients to waitlist when full |
| `notes` | text | Additional notes about the doctor |
| `avatar` | string | Doctor's profile picture URL |
| `include_time` | boolean | Whether to include time in appointments |
| `appointment_booking_window` | integer | Days in advance for booking |
| `time_slot` | integer | Duration of each appointment slot (minutes) |
| `doctor` | string | Doctor's name |
| `is_active` | boolean | Whether the doctor is currently active |
| `created_by` | integer | ID of user who created the record |
| `user_id` | integer | Associated user account ID |
| `allowed_appointment_today` | boolean | Whether same-day appointments are allowed |

## Model Relationships

### Specialization
- **Type:** Many-to-One (belongsTo)
- **Description:** Each doctor belongs to one specialization
- **Related Model:** `App\Models\Specialization`

### User Account
- **Type:** Many-to-One (belongsTo)
- **Description:** Each doctor is linked to a user account
- **Related Model:** `App\Models\User`

### Appointments
- **Type:** One-to-Many (hasMany)
- **Description:** A doctor can have multiple appointments
- **Related Model:** `App\Models\Appointment`

### Schedules
- **Type:** One-to-Many (hasMany)
- **Description:** A doctor can have multiple schedule entries
- **Related Model:** `App\Models\Schedule`

### Opinion Requests
- **Type:** One-to-Many (hasMany)
- **Description:** A doctor can receive multiple opinion requests
- **Related Model:** `App\Models\OpinionRequest`

## Model Features

### Soft Deletes
The Doctor model uses Laravel's SoftDeletes trait for safe deletion and recovery.

### Appointment Management
- Configurable time slots
- Waitlist management
- Booking window restrictions
- Same-day appointment controls

## Factory Usage Example

```php
// Create a single doctor
$doctor = Doctor::factory()->create();

// Create active doctors
$activeDoctors = Doctor::factory()->active()->count(10)->create();

// Create inactive doctors
$inactiveDoctors = Doctor::factory()->inactive()->count(5)->create();

// Create doctor with specific specialization
$doctor = Doctor::factory()->create([
    'specialization_id' => 1,
    'time_slot' => 30,
    'is_active' => true
]);
```

## Seeder Usage Example

```php
// In DoctorSeeder.php
public function run(): void
{
    // Create 50 active doctors
    Doctor::factory()->active()->count(50)->create();
    
    // Create 15 inactive doctors
    Doctor::factory()->inactive()->count(15)->create();
    
    // Create 10 doctors with random states
    Doctor::factory()->count(10)->create();
}
```

## Usage in Tests

```php
public function test_doctor_can_have_appointments()
{
    $doctor = Doctor::factory()->active()->create();
    $patient = Patient::factory()->create();
    
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'patient_id' => $patient->id
    ]);
    
    $this->assertTrue($doctor->appointments->contains($appointment));
}

public function test_inactive_doctor_cannot_take_appointments()
{
    $doctor = Doctor::factory()->inactive()->create();
    
    $this->assertFalse($doctor->is_active);
}
```

## API Endpoints
- `GET /api/doctors` - List all doctors
- `POST /api/doctors` - Create new doctor
- `GET /api/doctors/{id}` - Get specific doctor
- `PUT /api/doctors/{id}` - Update doctor
- `DELETE /api/doctors/{id}` - Soft delete doctor
- `GET /api/doctors/{id}/appointments` - Get doctor's appointments
- `GET /api/doctors/{id}/schedule` - Get doctor's schedule

## Validation Rules
```php
'specialization_id' => 'required|exists:specializations,id',
'frequency' => 'required|integer|min:1|max:7',
'patients_based_on_time' => 'required|integer|min:1|max:20',
'appointment_booking_window' => 'required|integer|min:1|max:365',
'time_slot' => 'required|integer|min:15|max:120',
'doctor' => 'required|string|max:255',
'user_id' => 'required|exists:users,id',
'is_active' => 'boolean'
```

## Business Logic
- Doctors must be active to accept new appointments
- Time slots determine appointment duration
- Booking window controls how far in advance appointments can be made
- Waitlist functionality helps manage overflow patients