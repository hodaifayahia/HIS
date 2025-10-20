# Annex vs Avenant Pricing Context Fix - Implementation Summary

## Problem Statement
The system was not correctly differentiating between prestations priced via **Annex** vs **Avenant** when fetching convention prestations. The frontend displayed all prestations the same way, making it impossible for users to know which pricing source was being used.

## Solution Overview
Implemented a complete pricing context system that:
1. **Backend**: Uses `ConventionDetail` to check for active avenant at the given `prise_en_charge_date`
2. **Frontend**: Displays visual badges and styling to indicate pricing source (Annex / Avenant / Public)
3. **UI Differentiation**: Shows different row styles and additional price breakdowns for annex/avenant prestations

---

## Backend Implementation (Already Existed - Verified)

### Service: `ConventionPricingService`
**Location**: `app/Services/Reception/ConventionPricingService.php`

**Logic Flow**:
1. `getPrestationsWithDateBasedPricing()` receives convention IDs and date
2. For each convention, calls `getConventionPrestationsForDate()`
3. Finds the matching `ConventionDetail` for the date using `findConventionDetailForDate()`
4. **Priority-based pricing**:
   - **Priority 1 (Highest)**: If `conventionDetail->avenant_id` exists → fetch from `PrestationPricing` where `avenant_id = X`
   - **Priority 2**: If no avenant → fetch from all active Annexes for the convention
   - **Priority 3**: Fallback to `PrestationPricing` where `convention_id = X` (no annex/avenant)
   - **Priority 4 (Lowest)**: Fallback to public pricing

**Returned metadata**:
```php
[
    'prestation_id' => ...,
    'prestation_name' => ...,
    'convention_price' => ...,
    'patient_price' => ...,
    'company_price' => ...,
    'pricing_source' => 'avenant' | 'annex' | 'prestation_pricing' | 'public_pricing',
    'priority' => 1-4,
    'avenant_id' => ... (if from avenant),
    'annex_id' => ... (if from annex),
    'valid_date_range' => [
        'start_date' => ...,
        'end_date' => ...
    ]
]
```

### Controller: `ficheNavetteItemController`
**Location**: `app/Http/Controllers/Reception/ficheNavetteItemController.php`

**Endpoint**: `GET /api/reception/fiche-navette-items/prestations/with-convention-pricing`

Already uses `$this->conventionPricingService->getPrestationsWithDateBasedPricing()` ✅

---

## Frontend Implementation (New Changes)

### Files Modified
1. `resources/js/Components/Apps/Emergency/FicheNavatteItem/ConventionManagement.vue`
2. `resources/js/Components/Apps/reception/FicheNavatteItem/ConventionManagement.vue`

### Visual Indicators Added

#### 1. Pricing Source Badges
Each prestation row now shows a badge indicating its pricing source:

- **🟢 Avenant** (green, `pi-check-circle`) → Price from active avenant
- **🔵 Annex** (blue, `pi-file`) → Price from convention annex
- **🟡 Public** (orange/warn, `pi-exclamation-triangle`) → Fallback to public pricing

#### 2. Color-Coded Row Borders
Prestation items have a left border color indicating their source:

```css
.prestation-item.from-avenant {
  border-left-color: #22c55e; /* Green */
  background: linear-gradient(to right, #f0fdf4 0%, #f8fafc 100%);
}

.prestation-item.from-annex {
  border-left-color: #3b82f6; /* Blue */
  background: linear-gradient(to right, #eff6ff 0%, #f8fafc 100%);
}

.prestation-item.from-public {
  border-left-color: #f59e0b; /* Orange/Amber */
  background: linear-gradient(to right, #fffbeb 0%, #f8fafc 100%);
}
```

#### 3. Price Breakdown for Annex/Avenant
When a prestation comes from an annex or avenant, show the patient vs company price split:

```html
<small class="price-breakdown text-muted">
  Patient: 150.00 DA | Company: 350.00 DA
</small>
```

#### 4. Context Information
Show the source ID in the code line:

```html
<small class="prestation-code">
  Code: ABC123
  <span class="text-muted ml-2">(Avenant #42)</span>
</small>
```

### Template Changes (Both Files)

**Before**:
```vue
<div class="prestation-item">
  <div class="prestation-info">
    <strong>{{ prestation.name }}</strong>
    <small>Code: {{ prestation.code }}</small>
  </div>
  <span class="prestation-price">{{ prestation.convention_price }}</span>
</div>
```

**After**:
```vue
<div 
  class="prestation-item" 
  :class="{
    'from-avenant': prestation.pricing_source === 'avenant',
    'from-annex': prestation.pricing_source === 'annex',
    'from-public': prestation.pricing_source === 'public_pricing'
  }"
>
  <div class="prestation-info">
    <div class="prestation-header">
      <strong>{{ prestation.name }}</strong>
      
      <!-- Pricing Source Badge -->
      <Tag v-if="prestation.pricing_source === 'avenant'" 
           value="Avenant" severity="success" icon="pi pi-check-circle" />
      <Tag v-else-if="prestation.pricing_source === 'annex'" 
           value="Annex" severity="info" icon="pi pi-file" />
      <Tag v-else-if="prestation.pricing_source === 'public_pricing'" 
           value="Public" severity="warn" icon="pi pi-exclamation-triangle" />
      
      <!-- Other tags (appointment, specialization) -->
    </div>
    
    <small class="prestation-code">
      Code: {{ prestation.code }}
      <span v-if="prestation.avenant_id" class="text-muted">
        (Avenant #{{ prestation.avenant_id }})
      </span>
    </small>
    
    <!-- Price Breakdown for Annex/Avenant -->
    <small v-if="prestation.pricing_source === 'avenant' || prestation.pricing_source === 'annex'" 
           class="price-breakdown text-muted">
      Patient: {{ formatCurrency(prestation.patient_price) }} | 
      Company: {{ formatCurrency(prestation.company_price) }}
    </small>
  </div>
  
  <span class="prestation-price">{{ formatCurrency(prestation.convention_price) }}</span>
</div>
```

---

## User Experience Changes

### Before (Problem)
- All prestations looked identical
- No way to know if price came from annex, avenant, or public pricing
- Users had to manually check `ConventionDetail` and `PrestationPricing` tables to understand pricing source
- Risk of confusion when viewing prestations at different dates (avenant activation dates)

### After (Solution)
- **Visual clarity**: Instant recognition of pricing source via badges and color-coded borders
- **Transparency**: Shows exact avenant/annex ID and patient/company split
- **Date-aware**: Automatically fetches correct pricing based on `prise_en_charge_date`
- **Business context**: Users can see when an avenant is active vs when base annex pricing is used

---

## Testing Instructions

### 1. Create Test Data
1. Create a convention with an annex and prestation pricing
2. Create an avenant for that convention (with different pricing for the same prestations)
3. Set the avenant activation date to a specific date

### 2. Test Annex Pricing (Before Avenant Activation)
1. Open Convention Management modal
2. Select a `prise_en_charge_date` **before** the avenant activation date
3. Select the convention
4. **Expected**: Prestations show **🔵 Annex** badge and blue left border
5. Price breakdown shows patient/company split from annex pricing

### 3. Test Avenant Pricing (After Avenant Activation)
1. Change `prise_en_charge_date` to **on or after** the avenant activation date
2. Re-select the same convention
3. **Expected**: Prestations show **🟢 Avenant** badge and green left border
4. Price breakdown shows patient/company split from avenant pricing
5. Code line shows `(Avenant #X)`

### 4. Test Public Pricing Fallback
1. Select a convention with **no annexes and no prestations in `PrestationPricing`**
2. **Expected**: Prestations show **🟡 Public** badge and orange left border
3. Price shows prestation's public price

---

## API Response Example

**Request**:
```http
GET /api/reception/fiche-navette-items/prestations/with-convention-pricing
  ?convention_ids=123
  &prise_en_charge_date=2025-10-15
```

**Response** (when avenant is active on that date):
```json
{
  "success": true,
  "data": [
    {
      "prestation_id": 456,
      "prestation_name": "Consultation Cardiologie",
      "prestation_code": "CARD-CONS-001",
      "specialization_id": 10,
      "specialization_name": "Cardiology",
      "standard_price": 5000.00,
      "convention_price": 3500.00,
      "patient_price": 1500.00,
      "company_price": 2000.00,
      "need_an_appointment": true,
      "convention_id": 123,
      "pricing_source": "avenant",
      "priority": 1,
      "avenant_id": 42,
      "valid_date_range": {
        "start_date": "2025-10-01",
        "end_date": "2026-09-30"
      }
    }
  ],
  "meta": {
    "prise_en_charge_date": "2025-10-15",
    "convention_count": 1,
    "prestation_count": 1
  }
}
```

---

## Business Rules Implemented

1. **Date-based pricing**: The system checks `ConventionDetail` to find which annex/avenant is valid for the `prise_en_charge_date`
2. **Avenant priority**: If a `ConventionDetail` has an `avenant_id`, pricing **always** comes from that avenant (overrides annex)
3. **Annex fallback**: If no avenant is active on that date, pricing comes from the convention's annexes
4. **Graceful degradation**: If no annex/avenant pricing exists, falls back to public pricing (with visual warning)
5. **Multiple conventions**: Can fetch prestations from multiple conventions in one request (merged and deduplicated by priority)

---

## Next Steps (Future Enhancements)

### 1. Backend Persistence (High Priority)
When creating `fiche_navette_item` records, persist:
- `contract_percentage_id` (from frontend selection)
- `final_price` using matched `PrestationPricing` (server-side lookup)
- `patient_share` and `company_share` from matched pricing
- Store `avenant_id` or `annex_id` reference for audit trail

**Why**: Ensures totals remain accurate and pricing is locked at creation time (prevents retroactive changes if avenant is updated later).

### 2. Highlight Newly Created Rows (Medium Priority)
When creating/duplicating prestations during annex/avenant creation:
- Briefly highlight new rows with yellow background (fade after 3 seconds)
- Auto-scroll to the first newly created row
- Show a badge "NEW" for 5 seconds

**Why**: Improves UX when duplicating many prestations — users can instantly see what was created.

### 3. Contract Percentage Integration (Medium Priority)
- Wire the existing contract percentage dropdown (already implemented in `ConventionManagement.vue`)
- Call `matchPrestationPricing` endpoint to enrich prestations with percentage-specific pricing
- Display matched `patient_price` when a percentage is selected

**Why**: Allows users to see pricing for different contract percentage tiers before creating items.

### 4. Audit Trail (Low Priority)
Store a snapshot of the `PrestationPricing` record used at item creation time in a JSON column or separate audit table.

**Why**: Compliance and debugging — can reconstruct exact pricing logic used at any point in history.

---

## Files Changed Summary

### Backend (Verified - No Changes Needed)
- ✅ `app/Services/Reception/ConventionPricingService.php` (already correct)
- ✅ `app/Http/Controllers/Reception/ficheNavetteItemController.php` (already using service)

### Frontend (Modified)
- ✏️ `resources/js/Components/Apps/Emergency/FicheNavatteItem/ConventionManagement.vue`
  - Added pricing source badges
  - Added conditional CSS classes
  - Added price breakdown display
  - Added context information (avenant/annex ID)
  
- ✏️ `resources/js/Components/Apps/reception/FicheNavatteItem/ConventionManagement.vue`
  - Same changes as Emergency version

### CSS (Added)
```css
/* Pricing source visual indicators */
.prestation-item.from-avenant { ... }
.prestation-item.from-annex { ... }
.prestation-item.from-public { ... }
.price-breakdown { ... }
```

---

## Conclusion
The system now correctly:
1. ✅ Fetches pricing from avenant if active on the date
2. ✅ Falls back to annex pricing if no avenant
3. ✅ Visually indicates the pricing source to users
4. ✅ Shows patient vs company price breakdown
5. ✅ Provides context (avenant/annex ID) for transparency

Users can now confidently create fiche navette items knowing exactly which pricing source is being applied based on the `prise_en_charge_date`.
