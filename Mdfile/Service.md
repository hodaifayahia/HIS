# Service Model Documentation

## Overview
The Service model represents hospital departments and services in the Hospital Information System (HIS). It manages service configurations, operating hours, and organizational structure.

## Database Table
**Table Name:** `services`

## Namespace
`App\Models\CONFIGURATION\Service`

## Fillable Attributes

| Attribute | Type | Description |
|-----------|------|-------------|
| `name` | string | Service name |
| `description` | text | Service description |
| `image_url` | string | Service image URL |
| `start_time` | time | Service opening time |
| `end_time` | time | Service closing time |
| `agmentation` | text | Additional service information |
| `is_active` | boolean | Whether the service is currently active |

## Model Relationships

### Specializations
- **Type:** One-to-Many (hasMany)
- **Description:** A service can have multiple specializations
- **Related Model:** `App\Models\Specialization`
- **Foreign Key:** `service_id`

### Annexes
- **Type:** One-to-Many (hasMany)
- **Description:** A service can have multiple annexes
- **Related Model:** `App\Models\B2B\Annex`
- **Foreign Key:** `service_id`

### Prestations
- **Type:** One-to-Many (hasMany)
- **Description:** A service can offer multiple prestations
- **Related Model:** `App\Models\CONFIGURATION\Prestation`
- **Foreign Key:** `service_id`

### Stockages
- **Type:** One-to-Many (hasMany)
- **Description:** A service can have multiple storage locations
- **Related Model:** `App\Models\Stockage`
- **Foreign Key:** `service_id`

## Common Service Types

| Service Name | Description | Typical Hours |
|--------------|-------------|---------------|
| Urgences | Emergency services | 24/7 |
| Consultation externe | Outpatient consultations | 08:00-18:00 |
| Hospitalisation | Inpatient services | 24/7 |
| Chirurgie | Surgical services | 07:00-20:00 |
| Radiologie | Imaging services | 08:00-17:00 |
| Laboratoire | Laboratory services | 06:00-22:00 |
| Pharmacie | Pharmacy services | 08:00-20:00 |
| Kinésithérapie | Physiotherapy | 08:00-17:00 |

## Factory Usage Example

```php
// Create a single service
$service = Service::factory()->create();

// Create active services
$activeServices = Service::factory()->active()->count(10)->create();

// Create inactive services
$inactiveServices = Service::factory()->inactive()->count(3)->create();

// Create emergency service
$emergencyService = Service::factory()->emergencyService()->create();

// Create service with specific attributes
$service = Service::factory()->create([
    'name' => 'Cardiologie',
    'start_time' => '08:00',
    'end_time' => '17:00'
]);
```

## Seeder Usage Example

```php
// In ServiceSeeder.php
public function run(): void
{
    // Create 45 active services
    Service::factory()->active()->count(45)->create();
    
    // Create 5 inactive services
    Service::factory()->inactive()->count(5)->create();
    
    // Create 5 emergency services
    Service::factory()->emergencyService()->count(5)->create();
}
```

## Usage in Tests

```php
public function test_service_has_specializations()
{
    $service = Service::factory()->create();
    $specialization = Specialization::factory()->create(['service_id' => $service->id]);
    
    $this->assertTrue($service->specializations->contains($specialization));
}

public function test_emergency_service_is_24_7()
{
    $service = Service::factory()->emergencyService()->create();
    
    $this->assertEquals('Urgences', $service->name);
    $this->assertEquals('00:00', $service->start_time);
    $this->assertEquals('23:59', $service->end_time);
    $this->assertTrue($service->is_active);
}

public function test_service_can_have_prestations()
{
    $service = Service::factory()->create();
    $prestation = Prestation::factory()->create(['service_id' => $service->id]);
    
    $this->assertTrue($service->prestations->contains($prestation));
}
```

## API Endpoints
- `GET /api/services` - List all services
- `POST /api/services` - Create new service
- `GET /api/services/{id}` - Get specific service
- `PUT /api/services/{id}` - Update service
- `DELETE /api/services/{id}` - Delete service
- `GET /api/services/{id}/specializations` - Get service specializations
- `GET /api/services/{id}/prestations` - Get service prestations
- `GET /api/services/active` - Get active services only

## Validation Rules
```php
'name' => 'required|string|max:255|unique:services,name',
'description' => 'nullable|string',
'image_url' => 'nullable|url',
'start_time' => 'required|date_format:H:i',
'end_time' => 'required|date_format:H:i|after:start_time',
'agmentation' => 'nullable|string',
'is_active' => 'boolean'
```

## Business Logic
- Services define the organizational structure of the hospital
- Operating hours control when services are available
- Active status determines service availability
- Services group related specializations and prestations
- Emergency services typically operate 24/7
- Service hierarchy affects appointment scheduling and resource allocation

## Service Categories

### Clinical Services
- Emergency Medicine
- Internal Medicine
- Surgery
- Pediatrics
- Obstetrics & Gynecology

### Diagnostic Services
- Radiology
- Laboratory
- Pathology
- Cardiology (diagnostic)

### Support Services
- Pharmacy
- Physiotherapy
- Social Services
- Administration

## Integration Points
- **Appointment System:** Services determine available appointment slots
- **Staff Management:** Doctors and nurses are assigned to services
- **Resource Allocation:** Equipment and supplies are managed per service
- **Billing:** Services are used for cost center allocation
- **Reporting:** Performance metrics are tracked by service