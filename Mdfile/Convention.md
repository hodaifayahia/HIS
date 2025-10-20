# Convention Model Documentation

## Overview
The Convention model represents business-to-business contracts in the Hospital Information System (HIS). It manages agreements between the hospital and external organizations for medical services.

## Database Table
**Table Name:** `conventions`

## Namespace
`App\Models\B2B\Convention`

## Fillable Attributes

| Attribute | Type | Description |
|-----------|------|-------------|
| `organisme_id` | integer | Foreign key to organismes table |
| `name` | string | Convention name |
| `status` | string | Convention status (draft, active, inactive, expired, terminated) |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Last update timestamp |
| `activation_at` | timestamp | Convention activation date |

## Model Relationships

### Organisme
- **Type:** Many-to-One (belongsTo)
- **Description:** Each convention belongs to one organisme
- **Related Model:** `App\Models\CRM\Organisme`
- **Foreign Key:** `organisme_id`

### Convention Detail
- **Type:** One-to-One (hasOne)
- **Description:** Each convention has detailed information
- **Related Model:** `App\Models\B2B\ConventionDetail`

### Avenants
- **Type:** One-to-Many (hasMany)
- **Description:** A convention can have multiple amendments (avenants)
- **Related Model:** `App\Models\B2B\Avenant`
- **Foreign Key:** `convention_id`

### Contract Percentages
- **Type:** One-to-Many (hasMany)
- **Description:** A convention can have multiple percentage configurations
- **Related Model:** `App\Models\ContractPercentage`

## Convention Statuses

| Status | Description |
|--------|-------------|
| `draft` | Convention is being prepared |
| `active` | Convention is currently active |
| `inactive` | Convention is temporarily inactive |
| `expired` | Convention has expired |
| `terminated` | Convention has been terminated |

## Factory Usage Example

```php
// Create a single convention
$convention = Convention::factory()->create();

// Create active conventions
$activeConventions = Convention::factory()->active()->count(30)->create();

// Create draft conventions
$draftConventions = Convention::factory()->draft()->count(15)->create();

// Create expired conventions
$expiredConventions = Convention::factory()->expired()->count(15)->create();

// Create convention with specific organisme
$convention = Convention::factory()->create([
    'organisme_id' => 1,
    'name' => 'Convention Hospitalière 2024'
]);
```

## Seeder Usage Example

```php
// In ConventionSeeder.php
public function run(): void
{
    // Create 30 active conventions
    Convention::factory()->active()->count(30)->create();
    
    // Create 15 draft conventions
    Convention::factory()->draft()->count(15)->create();
    
    // Create 15 expired conventions
    Convention::factory()->expired()->count(15)->create();
    
    // Create 10 conventions with random states
    Convention::factory()->count(10)->create();
}
```

## Usage in Tests

```php
public function test_convention_belongs_to_organisme()
{
    $organisme = Organisme::factory()->create();
    $convention = Convention::factory()->create(['organisme_id' => $organisme->id]);
    
    $this->assertEquals($organisme->id, $convention->organisme->id);
}

public function test_active_convention_has_activation_date()
{
    $convention = Convention::factory()->active()->create();
    
    $this->assertEquals('active', $convention->status);
    $this->assertNotNull($convention->activation_at);
}

public function test_convention_can_have_avenants()
{
    $convention = Convention::factory()->create();
    $avenant = Avenant::factory()->create(['convention_id' => $convention->id]);
    
    $this->assertTrue($convention->avenants->contains($avenant));
}
```

## API Endpoints
- `GET /api/conventions` - List all conventions
- `POST /api/conventions` - Create new convention
- `GET /api/conventions/{id}` - Get specific convention
- `PUT /api/conventions/{id}` - Update convention
- `DELETE /api/conventions/{id}` - Delete convention
- `POST /api/conventions/{id}/activate` - Activate convention
- `GET /api/conventions/{id}/avenants` - Get convention avenants

## Validation Rules
```php
'organisme_id' => 'required|exists:organismes,id',
'name' => 'required|string|max:255',
'status' => 'required|in:draft,active,inactive,expired,terminated',
'activation_at' => 'nullable|date'
```

## Business Logic
- Conventions must be linked to a valid organisme
- Only active conventions can process medical services
- Activation date is required for active conventions
- Draft conventions can be edited freely
- Expired/terminated conventions are read-only
- Avenants (amendments) track changes to active conventions

## Workflow States
```
draft → active → inactive/expired/terminated
  ↑        ↓
  └────────┘ (reactivation possible)
```

## Related Features
- **Pricing Management:** Conventions define pricing rules for services
- **Service Coverage:** Determines which services are covered
- **Billing Integration:** Used for automated billing processes
- **Reporting:** Convention performance and usage analytics