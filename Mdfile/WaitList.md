# WaitList Model Documentation

## Overview
The WaitList model manages patient waiting lists in the Hospital Information System (HIS). It handles appointment queues, priority management, and scheduling for patients awaiting medical services.

## Database Table
**Table Name:** `waitlist`

## Fillable Attributes

| Attribute | Type | Description |
|-----------|------|-------------|
| `id` | integer | Primary key |
| `doctor_id` | integer | Foreign key to doctors table (nullable) |
| `appointmentId` | integer | Related appointment ID (nullable) |
| `patient_id` | integer | Foreign key to patients table |
| `specialization_id` | integer | Foreign key to specializations table |
| `is_Daily` | boolean | Whether this is a daily waitlist entry |
| `created_by` | integer | User who created the entry |
| `importance` | string | Priority level |
| `notes` | text | Additional notes |
| `MoveToEnd` | boolean | Whether to move to end of queue |

## Model Relationships

### Doctor
- **Type:** Many-to-One (belongsTo)
- **Description:** Waitlist entry may be assigned to a specific doctor
- **Related Model:** `App\Models\Doctor`
- **Foreign Key:** `doctor_id` (nullable)

### Patient
- **Type:** Many-to-One (belongsTo)
- **Description:** Each waitlist entry belongs to one patient
- **Related Model:** `App\Models\Patient`
- **Foreign Key:** `patient_id`

### Specialization
- **Type:** Many-to-One (belongsTo)
- **Description:** Each entry is for a specific medical specialization
- **Related Model:** `App\Models\Specialization`
- **Foreign Key:** `specialization_id`

### Creator
- **Type:** Many-to-One (belongsTo)
- **Description:** User who created the waitlist entry
- **Related Model:** `App\Models\User`
- **Foreign Key:** `created_by`

## Model Features

### Soft Deletes
The WaitList model uses Laravel's SoftDeletes trait for safe deletion and recovery.

### HasFactory
Supports factory pattern for testing and seeding.

## Importance Levels
- `low` - Standard priority
- `medium` - Moderate priority
- `high` - High priority
- `urgent` - Emergency or critical cases

## Factory Usage Example

```php
// Create a single waitlist entry
$waitlistEntry = WaitList::factory()->create();

// Create urgent waitlist entries
$urgentEntries = WaitList::factory()->urgent()->count(10)->create();

// Create entries with assigned doctors
$doctorEntries = WaitList::factory()->withDoctor()->count(20)->create();

// Create daily waitlist entries
$dailyEntries = WaitList::factory()->daily()->count(15)->create();

// Create entry for specific patient
$entry = WaitList::factory()->create([
    'patient_id' => 1,
    'importance' => 'urgent',
    'is_Daily' => true
]);
```

## Seeder Usage Example

```php
// In WaitListSeeder.php
public function run(): void
{
    // Create 30 urgent entries
    WaitList::factory()->urgent()->count(30)->create();
    
    // Create 40 entries with doctors
    WaitList::factory()->withDoctor()->count(40)->create();
    
    // Create 30 daily entries
    WaitList::factory()->daily()->count(30)->create();
    
    // Create 20 random entries
    WaitList::factory()->count(20)->create();
}
```

## Usage in Tests

```php
public function test_waitlist_belongs_to_patient()
{
    $patient = Patient::factory()->create();
    $entry = WaitList::factory()->create(['patient_id' => $patient->id]);
    
    $this->assertEquals($patient->id, $entry->patient->id);
}

public function test_urgent_waitlist_entry()
{
    $entry = WaitList::factory()->urgent()->create();
    
    $this->assertEquals('urgent', $entry->importance);
    $this->assertTrue($entry->is_Daily);
}

public function test_waitlist_with_doctor()
{
    $entry = WaitList::factory()->withDoctor()->create();
    
    $this->assertNotNull($entry->doctor_id);
    $this->assertInstanceOf(Doctor::class, $entry->doctor);
}
```

## API Endpoints
- `GET /api/waitlist` - List all waitlist entries
- `POST /api/waitlist` - Create new waitlist entry
- `GET /api/waitlist/{id}` - Get specific entry
- `PUT /api/waitlist/{id}` - Update entry
- `DELETE /api/waitlist/{id}` - Soft delete entry
- `GET /api/waitlist/urgent` - Get urgent entries
- `GET /api/waitlist/daily` - Get daily entries
- `POST /api/waitlist/{id}/move-to-end` - Move entry to end
- `GET /api/doctors/{id}/waitlist` - Get doctor's waitlist

## Validation Rules
```php
'patient_id' => 'required|exists:patients,id',
'specialization_id' => 'required|exists:specializations,id',
'doctor_id' => 'nullable|exists:doctors,id',
'appointmentId' => 'nullable|integer',
'is_Daily' => 'boolean',
'importance' => 'required|in:low,medium,high,urgent',
'notes' => 'nullable|string',
'MoveToEnd' => 'boolean',
'created_by' => 'required|exists:users,id'
```

## Business Logic
- **Priority Management:** Urgent cases get priority scheduling
- **Doctor Assignment:** Entries can be assigned to specific doctors
- **Daily Lists:** Separate daily waitlists for immediate scheduling
- **Queue Management:** Ability to move entries within the queue
- **Specialization Filtering:** Organize by medical specialization
- **Soft Deletion:** Maintain history while removing from active lists

## Waitlist States
- **Active:** Currently in queue awaiting appointment
- **Assigned:** Assigned to specific doctor
- **Daily:** On daily scheduling list
- **Urgent:** High priority requiring immediate attention
- **Moved:** Repositioned in queue

## Integration Points
- **Appointment System:** Convert waitlist entries to appointments
- **Doctor Schedules:** Match with available doctor slots
- **Patient Management:** Track patient service requests
- **Notification System:** Alert staff of urgent cases
- **Reporting:** Generate waitlist analytics and metrics