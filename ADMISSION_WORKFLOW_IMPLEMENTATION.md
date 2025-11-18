# Admission Workflow - Automatic Fiche Navette Creation & Initial Prestation Setup

## Overview

This implementation adds intelligent patient search functionality to the admission creation workflow with automatic fiche navette handling based on admission type.

## Features Implemented

### 1. **Patient Search with Today's Fiche Navette Detection**
   - **Location**: `PatientController::search()`
   - **Functionality**: 
     - Search patients by name, phone, ID number
     - Simultaneously checks if patient has today's fiche navette
     - Enriches search results with fiche navette information
     - Returns `today_fiche_navette` object with reference, status, and ID

### 2. **Smart Fiche Navette Management**
   - **Location**: `FicheNavetteSearchService::getOrCreateFicheNavetteForToday()`
   - **Logic**:
     ```
     IF patient has fiche navette for today
        → Return existing fiche navette
     ELSE
        → Create new fiche navette for today
        → Return fresh fiche navette with relationships
     ```
   - **Key Features**:
     - Checks only for today's date using `whereDate('fiche_date', today())`
     - Automatically creates if doesn't exist
     - Returns eager-loaded relationships for performance
     - Transaction-safe operations

### 3. **Automatic Initial Prestation Setup**
   - **Location**: `AdmissionService::createAdmission()` & `addInitialPrestationToFiche()`
   - **Logic by Admission Type**:

   **Surgery (Upfront)**:
   - ✅ Creates/links today's fiche navette automatically
   - ✅ Adds selected initial prestation as item to fiche navette
   - ✅ Sets quantity = 1, unit_price from prestation
   - ✅ Updates fiche total amount
   - 📝 Required: User must select initial prestation

   **Nursing (Pay After)**:
   - ✅ Creates/links today's fiche navette automatically
   - ❌ Does NOT add any default prestation
   - 🔄 Services can be added later as needed
   - 📝 No initial prestation required

### 4. **Enhanced Admission Creation Form**
   - **Component**: `AdmissionCreate.vue`
   - **UX Improvements**:
     - Live patient search with autocomplete
     - Shows today's fiche navette status directly in search results
     - Selected patient card with fiche navette info
     - Clear button to reset selection
     - Type-specific help text explaining payment models
     - Surgery: Shows warning that prestation will be auto-added
     - Nursing: Shows info that no prestation will be added
     - Submit button disabled until patient is selected

## Data Flow

### Step 1: Patient Search
```
User types patient search query
    ↓
Frontend calls: GET /api/patients/search?query=...
    ↓
PatientController::search() executes
    ↓
Query patients by name, phone, ID
    ↓
For each patient, find today's fiche navette
    ↓
Enrich with: { today_fiche_navette: { id, reference, status } }
    ↓
Return results to frontend
    ↓
Frontend displays with fiche status indicator
```

### Step 2: Admission Creation (Surgery Example)
```
User selects patient (has today's fiche OR needs creation)
    ↓
User selects "Surgery (Upfront)" type
    ↓
User selects initial prestation (required)
    ↓
User submits form
    ↓
Frontend POST: /api/admissions with data
    ↓
AdmissionService::createAdmission() executes:
    ├─ FicheNavetteSearchService::getOrCreateFicheNavetteForToday()
    │  ├─ Query: Does patient have fiche for today?
    │  ├─ If YES: Return existing fiche
    │  └─ If NO: Create new fiche, return it
    ├─ If type === 'surgery':
    │  └─ addInitialPrestationToFiche()
    │     ├─ Check if prestation already in fiche
    │     ├─ Get prestation pricing
    │     ├─ Create fiche item with quantity=1
    │     └─ Update fiche total_amount
    └─ Create Admission record linked to fiche
    ↓
Return Admission with fiche_navette_id populated
    ↓
Frontend redirects to admission detail page
```

### Step 3: Admission Creation (Nursing Example)
```
Similar to Surgery BUT:
    - No initial prestation selection required
    - addInitialPrestationToFiche() is NOT called
    - Fiche is created/linked with 0 items
    - Services can be added from admission detail page
```

## Database Changes

### ficheNavettes Table
- No schema changes needed
- Existing columns used:
  - `patient_id` - links to patient
  - `fiche_date` - used for today() check
  - `reference` - auto-generated
  - `status` - defaults to 'pending'
  - `total_amount` - calculated sum of items

### ficheNavetteItem Table
- No schema changes needed
- Used when Surgery admission adds initial prestation:
  - `fiche_navette_id`
  - `prestation_id`
  - `type` = 'prestation'
  - `quantity` = 1
  - `unit_price` - from prestation
  - `total_price` = unit_price * 1

### Admissions Table
- No schema changes needed
- Now guaranteed to have `fiche_navette_id` populated on creation

## Code Files Modified/Created

### Created
1. **`app/Services/Reception/FicheNavetteSearchService.php`**
   - `getOrCreateFicheNavetteForToday(patientId, admissionType)` - Core logic
   - `findTodaysFicheNavette(patientId)` - Read-only query
   - `hasTodaysFicheNavette(patientId)` - Existence check

### Modified
1. **`app/Http/Controllers/Patient/PatientController.php`**
   - Updated `search()` method to enrich results with fiche navette info

2. **`app/Services/Admission/AdmissionService.php`**
   - Added dependency injection for `FicheNavetteSearchService`
   - Refactored `createAdmission()` to handle automatic fiche creation
   - Added `addInitialPrestationToFiche()` protected method

3. **`app/Http/Controllers/Admission/AdmissionController.php`**
   - Updated `getOrCreateFicheNavette()` to reflect new workflow
   - Now mainly for backwards compatibility

4. **`resources/js/Pages/Admission/AdmissionCreate.vue`**
   - Replaced dropdown patient selector with live search
   - Added search result display with fiche navette indicators
   - Added selected patient card
   - Added conditional initial prestation field
   - Added info alerts explaining admission types

5. **`app/Models/Patient.php`**
   - Added `ficheNavettes()` relationship method

## API Endpoints

### Patient Search (Enhanced)
```
GET /api/patients/search?query=john
```

**Response**:
```json
[
  {
    "id": 1,
    "Firstname": "John",
    "Lastname": "Doe",
    "phone": "1234567890",
    "Idnum": "ID123",
    "today_fiche_navette": {
      "id": 42,
      "reference": "FN-1-20251115143022",
      "status": "pending"
    }
  },
  {
    "id": 2,
    "Firstname": "John",
    "Lastname": "Smith",
    "phone": "0987654321",
    "Idnum": "ID456",
    "today_fiche_navette": null
  }
]
```

### Create Admission (Enhanced)
```
POST /api/admissions
```

**Payload (Surgery)**:
```json
{
  "patient_id": 1,
  "doctor_id": 5,
  "type": "surgery",
  "initial_prestation_id": 10
}
```

**Payload (Nursing)**:
```json
{
  "patient_id": 2,
  "doctor_id": 5,
  "type": "nursing"
}
```

**Response**:
```json
{
  "success": true,
  "message": "Admission created successfully",
  "data": {
    "id": 100,
    "patient_id": 1,
    "doctor_id": 5,
    "type": "surgery",
    "fiche_navette_id": 42,
    "status": "admitted",
    "initial_prestation_id": 10,
    ...
  }
}
```

## Error Handling

### Scenario 1: Surgery admission without initial prestation
**Error**:
```json
{
  "success": false,
  "message": "Initial prestation is required for surgery admission"
}
```
**Status**: 422

### Scenario 2: Patient not found
**Response**: Database constraint error, returns 422

### Scenario 3: Invalid prestation
**Error**:
```json
{
  "success": false,
  "message": "Failed to create admission",
  "error": "No query results found for model..."
}
```
**Status**: 500

## Testing Examples

### Test 1: Search shows existing fiche navette
```php
$patient = Patient::factory()->create();
ficheNavette::factory()->create([
    'patient_id' => $patient->id,
    'fiche_date' => today()
]);

$response = $this->getJson("/api/patients/search?query={$patient->Firstname}");

$this->assertNotNull($response[0]['today_fiche_navette']);
$this->assertEquals($patient->id, $response[0]['id']);
```

### Test 2: Surgery admission creates and links fiche
```php
$patient = Patient::factory()->create();
$prestation = Prestation::factory()->create();

$response = $this->postJson('/api/admissions', [
    'patient_id' => $patient->id,
    'type' => 'surgery',
    'initial_prestation_id' => $prestation->id,
    'doctor_id' => 1
]);

$this->assertTrue($response['success']);
$this->assertNotNull($response['data']['fiche_navette_id']);

$fiche = ficheNavette::find($response['data']['fiche_navette_id']);
$this->assertEquals(1, $fiche->items()->count());
$this->assertEquals($prestation->id, $fiche->items()->first()->prestation_id);
```

### Test 3: Nursing admission doesn't add prestation
```php
$patient = Patient::factory()->create();

$response = $this->postJson('/api/admissions', [
    'patient_id' => $patient->id,
    'type' => 'nursing',
    'doctor_id' => 1
]);

$this->assertTrue($response['success']);

$fiche = ficheNavette::find($response['data']['fiche_navette_id']);
$this->assertEquals(0, $fiche->items()->count());
```

## Performance Considerations

1. **Search Caching**: Patient search results are cached for 5 minutes
2. **Eager Loading**: All relationships loaded with `fresh()` to prevent N+1
3. **Transaction Safety**: Database transaction wraps entire admission creation
4. **Date Query Optimization**: Uses `whereDate()` which leverages database indexes

## Future Enhancements

1. **Bulk Prestation Addition**: Add multiple prestations for surgery admission
2. **Fiche Navette Templates**: Pre-populated prestation sets per surgery type
3. **Patient History**: Show last 5 fiche navettes in patient search results
4. **Auto-Select Doctor**: Pre-select doctor based on prestation requirements
5. **Pricing Preview**: Show total cost during surgery admission creation

## Backwards Compatibility

- ✅ Existing `fiche_navette_id` parameter in request is ignored
- ✅ `getOrCreateFicheNavette()` endpoint still works but returns different data structure
- ✅ Old admission records without `fiche_navette_id` will need data migration (if needed)
- ✅ Search endpoint adds new `today_fiche_navette` field (non-breaking)

## Debugging

### Check today's fiche for patient:
```php
php artisan tinker
$patient = Patient::find(1);
$fiche = $patient->ficheNavettes()->whereDate('fiche_date', today())->first();
dd($fiche);
```

### View admission with fiche:
```php
php artisan tinker
$admission = Admission::with('ficheNavette.items.prestation')->find(1);
dd($admission);
```

### Check fiche items and pricing:
```php
php artisan tinker
$fiche = ficheNavette::find(42);
$fiche->items()->with('prestation')->get()->each(fn($item) => 
    dd([
        'prestation' => $item->prestation->name,
        'qty' => $item->quantity,
        'unit_price' => $item->unit_price,
        'total' => $item->total_price
    ])
);
```
