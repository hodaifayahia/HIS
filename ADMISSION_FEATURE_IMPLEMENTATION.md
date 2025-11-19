# Admission Feature Implementation Summary

## Overview
The admission system has been enhanced with intelligent patient search and automatic fiche navette management. When creating a new admission, the system now:

1. **Searches for today's fiche navette** for the selected patient
2. **Creates one if it doesn't exist** (automatically linked to the admission)
3. **Adds initial prestation** for Surgery admissions only with correct pricing
4. **No default prestation** for Nursing admissions (pay after services)

---

## Key Features Implemented

### 1. Patient Search with Fiche Navette Status
**File**: `app/Http/Controllers/Patient/PatientController.php`

When searching for a patient, the API now returns:
- Patient basic information (name, phone, ID)
- **Today's fiche navette status** (if it exists)
  - Fiche reference number
  - Fiche status
  - Visual indicator showing fiche already exists

```php
// Search result enriched with fiche navette data
{
  "id": 1,
  "Firstname": "John",
  "Lastname": "Doe",
  "phone": "555-1234",
  "Idnum": "ID123",
  "today_fiche_navette": {
    "id": 5,
    "reference": "FN-1-20251115143022",
    "status": "pending"
  }
}
```

### 2. Automatic Fiche Navette Management
**File**: `app/Services/Reception/FicheNavetteSearchService.php`

New service class with three methods:

#### `getOrCreateFicheNavetteForToday(int $patientId, string $admissionType)`
- Searches for existing fiche navette created today for the patient
- If found: returns the existing fiche navette
- If not found: creates a new one with:
  - Today's date
  - Auto-generated reference: `FN-{patientId}-{timestamp}`
  - Status: `pending`
  - Admission type in notes

#### `findTodaysFicheNavette(int $patientId)`
- Simple finder for today's fiche navette

#### `hasTodaysFicheNavette(int $patientId)`
- Boolean check for today's fiche navette existence

### 3. Enhanced Admission Creation
**File**: `app/Services/Admission/AdmissionService.php`

Updated `createAdmission()` method now:

1. **Auto-links fiche navette**
   - Gets or creates today's fiche navette for the patient
   - Links it to the admission automatically

2. **Handles initial prestation for Surgery**
   - Only for Surgery admissions (`type = 'surgery'`)
   - Uses `price_with_vat_and_consumables_variant` for accurate pricing
   - Handles both array and scalar price returns
   - Adds prestation item with:
     - Correct unit price (with VAT and consumables)
     - Total price calculation
     - Prestation name in notes

3. **No default prestation for Nursing**
   - Nursing admissions get the fiche navette only
   - Services added manually as needed

#### Price Calculation
```php
// Gets price_with_vat_and_consumables_variant from prestation
$priceData = $prestation->price_with_vat_and_consumables_variant;

// Handles array format: ['ttc' => 100, 'ttc_with_consumables_vat' => 110]
$unitPrice = $priceData['ttc_with_consumables_vat'] ?? $priceData['ttc'] ?? 0;

// Creates fiche item with correct pricing
ficheNavetteItem::create([
    'unit_price' => $unitPrice,
    'total_price' => $unitPrice * 1,
    'notes' => 'Initial prestation for surgery admission - ' . $prestation->name,
]);
```

### 4. Enhanced Vue Component
**File**: `resources/js/Pages/Admission/AdmissionCreate.vue`

Improved admission creation form with:

#### Patient Search Field
- Real-time search with debouncing
- Shows matching patients in dropdown
- Displays patient details:
  - Full name
  - Phone number
  - ID number
  - **Green indicator**: "Today's Fiche: {reference}" if exists

#### Selected Patient Display
- Alert box showing selected patient info
- **Blue info box** if today's fiche navette already exists
- Shows fiche reference and status
- "Clear" button to reset selection

#### Admission Type Labels
- "Surgery (Upfront)" - with warning about automatic prestation
- "Nursing (Pay After)" - with info about manual service addition

#### Info Messages
- Surgery: "The selected initial prestation will be automatically added to the fiche navette"
- Nursing: "No default prestation will be added. Services will be added as needed"

#### Submit Logic
- Disabled until patient is selected
- Shows loading state during submission
- Displays success message: "Admission created successfully. Fiche Navette has been automatically created/linked."

---

## Database Flow

```
Patient Search
    ↓
[Check today's fiche navette]
    ↓
┌─ Fiche exists? ───→ Show in search results with green indicator
├─ Fiche not exist? → Show patient without indicator
    ↓
Select Patient & Create Admission
    ↓
AdmissionService::createAdmission()
    ├─ FicheNavetteSearchService::getOrCreateFicheNavetteForToday()
    │   ├─ Query: WHERE patient_id = X AND DATE(fiche_date) = TODAY
    │   ├─ Found? → Return existing fiche
    │   └─ Not found? → Create new fiche with reference FN-{X}-{timestamp}
    │
    ├─ If Surgery admission:
    │   ├─ Get prestation by ID
    │   ├─ Extract price_with_vat_and_consumables_variant
    │   ├─ Create ficheNavetteItem with:
    │   │   ├─ prestation_id
    │   │   ├─ unit_price (from variant)
    │   │   ├─ total_price (unit_price * quantity)
    │   │   └─ notes with prestation name
    │   └─ Update fiche total_amount
    │
    └─ Create Admission record with:
        ├─ fiche_navette_id (linked)
        ├─ initial_prestation_id (for Surgery)
        ├─ type (surgery|nursing)
        └─ status: 'admitted'
```

---

## API Endpoints

### Search Patients (Enhanced)
```
GET /api/patients/search?query={term}

Response:
{
  "data": [
    {
      "id": 1,
      "Firstname": "John",
      "Lastname": "Doe",
      "phone": "555-1234",
      "Idnum": "ID123",
      "today_fiche_navette": {
        "id": 5,
        "reference": "FN-1-20251115143022",
        "status": "pending"
      }
    }
  ]
}
```

### Create Admission (Updated)
```
POST /api/admissions

Request:
{
  "patient_id": 1,
  "doctor_id": 2,
  "type": "surgery",              // or "nursing"
  "initial_prestation_id": 10     // Required for surgery, ignored for nursing
}

Response:
{
  "success": true,
  "message": "Admission created successfully. Fiche Navette has been automatically created/linked.",
  "data": {
    "id": 1,
    "patient_id": 1,
    "type": "surgery",
    "status": "admitted",
    "fiche_navette_id": 5,
    "initial_prestation_id": 10,
    "admitted_at": "2025-11-15T14:30:22Z"
  }
}
```

---

## Architecture

### Service Layer
```
FicheNavetteSearchService
├── getOrCreateFicheNavetteForToday()
├── findTodaysFicheNavette()
└── hasTodaysFicheNavette()

AdmissionService
├── createAdmission()           [Uses FicheNavetteSearchService]
├── updateAdmission()
├── dischargePatient()
├── generateDischargeTicket()
├── getStatistics()
└── addInitialPrestationToFiche()  [Private helper]
```

### Controller Layer
```
PatientController
└── search()  [Enriched with fiche navette data]

AdmissionController
├── store()   [Uses AdmissionService::createAdmission()]
├── index()
├── show()
├── update()
└── destroy()
```

### Vue Components
```
AdmissionCreate.vue
├── Patient search with real-time filtering
├── Patient selection with fiche navette indicator
├── Admission type selection (Surgery/Nursing)
├── Initial prestation selection (Surgery only)
└── Form submission
```

---

## Technical Details

### Price Handling
The implementation correctly handles the `price_with_vat_and_consumables_variant` accessor:

```php
// Prestation model returns:
$priceData = [
    'ttc' => 100.00,                    // Simple VAT calculation
    'ttc_with_consumables_vat' => 110.00  // Consumables with separate VAT
]

// AdmissionService extracts the correct value:
$unitPrice = $priceData['ttc_with_consumables_vat'] ?? $priceData['ttc'] ?? 0;
```

### Transaction Safety
All operations in `createAdmission()` are wrapped in a database transaction:
- If fiche creation fails, admission is rolled back
- If prestation item creation fails, entire process is rolled back
- Ensures data consistency

### Caching Considerations
Patient search results are cached for 5 minutes, but fiche navette status is fresh (not cached) to always show current state.

---

## Testing Checklist

- [ ] Search for patient without today's fiche → Shows patient only
- [ ] Search for patient with today's fiche → Shows green "Today's Fiche" indicator
- [ ] Create Surgery admission → Fiche created/linked + Prestation item added
- [ ] Create Nursing admission → Fiche created/linked + No prestation item
- [ ] Verify prestation price uses `price_with_vat_and_consumables_variant`
- [ ] Check fiche total_amount updated correctly
- [ ] Verify admission linked to correct fiche
- [ ] Test with prestation that has consumables VAT
- [ ] Test with prestation without consumables VAT

---

## Dependencies

- `FicheNavetteSearchService` - New service for fiche management
- `AdmissionService` - Updated with enhanced logic
- `PatientController` - Enhanced search method
- Vue 3 Composition API - For component reactivity
- Axios - For API calls

---

## Notes

- ✅ Fiche navette creation is automatic (no manual selection needed)
- ✅ Price calculation includes VAT and consumables
- ✅ Surgery admissions have initial prestation (upfront payment)
- ✅ Nursing admissions have no default prestation (pay after)
- ✅ Search results show today's fiche status instantly
- ✅ All operations are transactional for data safety
