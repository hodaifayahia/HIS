# Allergy Model Documentation

## Overview
The Allergy model represents patient allergies in the Hospital Information System (HIS). It manages allergy information including severity levels, dates of occurrence, and associated notes.

## Database Table
**Table Name:** `allergies`

## Fillable Attributes

| Attribute | Type | Description |
|-----------|------|-------------|
| `name` | string | Name of the allergen |
| `severity` | string | Severity level (mild, moderate, severe, life-threatening) |
| `date` | date | Date when allergy was identified |
| `note` | text | Additional notes about the allergy |
| `patient_id` | integer | Foreign key to patients table |

## Model Relationships

### Patient
- **Type:** Many-to-One (belongsTo)
- **Description:** Each allergy belongs to one patient
- **Related Model:** `App\Models\Patient`
- **Foreign Key:** `patient_id`

### User
- **Type:** Many-to-One (belongsTo)
- **Description:** Each allergy record is associated with a user (who recorded it)
- **Related Model:** `App\Models\User`

## Type Casting
- `date` → date

## Severity Levels
- `mild` - Minor reactions, manageable symptoms
- `moderate` - Noticeable reactions requiring attention
- `severe` - Serious reactions requiring immediate medical attention
- `life-threatening` - Anaphylactic or other critical reactions

## Factory Usage Example

```php
// Create a single allergy
$allergy = Allergy::factory()->create();

// Create severe allergies
$severeAllergies = Allergy::factory()->severe()->count(10)->create();

// Create mild allergies
$mildAllergies = Allergy::factory()->mild()->count(20)->create();

// Create allergy for specific patient
$allergy = Allergy::factory()->create([
    'patient_id' => 1,
    'name' => 'Peanuts',
    'severity' => 'severe'
]);
```

## Seeder Usage Example

```php
// In AllergySeeder.php
public function run(): void
{
    // Create 40 mild allergies
    Allergy::factory()->mild()->count(40)->create();
    
    // Create 25 random severity allergies
    Allergy::factory()->count(25)->create();
    
    // Create 15 severe allergies
    Allergy::factory()->severe()->count(15)->create();
}
```

## Usage in Tests

```php
public function test_patient_can_have_allergies()
{
    $patient = Patient::factory()->create();
    $allergy = Allergy::factory()->create(['patient_id' => $patient->id]);
    
    $this->assertEquals($patient->id, $allergy->patient->id);
}

public function test_severe_allergy_requires_attention()
{
    $allergy = Allergy::factory()->severe()->create();
    
    $this->assertEquals('severe', $allergy->severity);
}
```

## API Endpoints
- `GET /api/allergies` - List all allergies
- `POST /api/allergies` - Create new allergy
- `GET /api/allergies/{id}` - Get specific allergy
- `PUT /api/allergies/{id}` - Update allergy
- `DELETE /api/allergies/{id}` - Delete allergy
- `GET /api/patients/{id}/allergies` - Get patient's allergies

## Validation Rules
```php
'name' => 'required|string|max:255',
'severity' => 'required|in:mild,moderate,severe,life-threatening',
'date' => 'required|date|before_or_equal:today',
'note' => 'nullable|string',
'patient_id' => 'required|exists:patients,id'
```

## Business Logic
- Allergies are critical for patient safety
- Severe and life-threatening allergies should trigger alerts
- Date tracking helps understand allergy development timeline
- Notes provide context for medical staff
- Patient relationship ensures proper medical history tracking