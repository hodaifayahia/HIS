# Patient Model Documentation

## Overview
The Patient model represents patients in the Hospital Information System (HIS). It manages patient demographics, contact information, and basic medical data.

## Database Table
**Table Name:** `patients`

## Fillable Attributes

| Attribute | Type | Description |
|-----------|------|-------------|
| `Firstname` | string | Patient's first name (automatically title-cased) |
| `phone` | string | Patient's phone number |
| `Lastname` | string | Patient's last name |
| `Parent` | string | Parent or guardian name |
| `Idnum` | string | Patient identification number |
| `age` | integer | Patient's age |
| `gender` | string | Patient's gender |
| `weight` | decimal(2) | Patient's weight |
| `created_by` | integer | ID of user who created the record |
| `dateOfBirth` | date | Patient's date of birth |
| `balance` | decimal(2) | Patient's account balance |

## Model Relationships

### Appointments
- **Type:** One-to-Many (hasMany)
- **Description:** A patient can have multiple appointments
- **Related Model:** `App\Models\Appointment`

### Consultations
- **Type:** One-to-Many (hasMany)
- **Description:** A patient can have multiple consultations
- **Related Model:** `App\Models\Consultation`

### Medical Records
- **Type:** One-to-Many (hasMany)
- **Description:** A patient can have multiple medical records
- **Related Model:** Various medical record models

## Model Features

### Soft Deletes
The Patient model uses Laravel's SoftDeletes trait, allowing for safe deletion and recovery of patient records.

### Computed Attributes
- **fullname:** Automatically computed attribute that combines `Firstname` and `Lastname`

### Mutators
- **Firstname:** Automatically converts to title case when set

## Factory Usage Example

```php
// Create a single patient
$patient = Patient::factory()->create();

// Create multiple patients
$patients = Patient::factory()->count(50)->create();

// Create patient with specific attributes
$patient = Patient::factory()->create([
    'Firstname' => 'John',
    'Lastname' => 'Doe',
    'gender' => 'male'
]);
```

## Seeder Usage Example

```php
// In PatientSeeder.php
public function run(): void
{
    // Create 100 patients with realistic data
    Patient::factory()->count(100)->create();
}
```

## Usage in Tests

```php
public function test_patient_creation()
{
    $patient = Patient::factory()->create([
        'Firstname' => 'Jane',
        'Lastname' => 'Smith'
    ]);
    
    $this->assertEquals('Jane Smith', $patient->fullname);
    $this->assertDatabaseHas('patients', [
        'Firstname' => 'Jane',
        'Lastname' => 'Smith'
    ]);
}
```

## API Endpoints
- `GET /api/patients` - List all patients
- `POST /api/patients` - Create new patient
- `GET /api/patients/{id}` - Get specific patient
- `PUT /api/patients/{id}` - Update patient
- `DELETE /api/patients/{id}` - Soft delete patient

## Validation Rules
```php
'Firstname' => 'required|string|max:255',
'Lastname' => 'required|string|max:255',
'phone' => 'required|string|max:20',
'gender' => 'required|in:male,female',
'age' => 'nullable|integer|min:0|max:150',
'weight' => 'nullable|numeric|min:0',
'dateOfBirth' => 'nullable|date|before:today'
```