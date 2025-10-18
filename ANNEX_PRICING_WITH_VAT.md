# Annex/Avenant Pricing with VAT - Implementation Guide

## Overview
This document explains how the system calculates initial prices for Annex prestations using **price with VAT** (TTC - Toutes Taxes Comprises) instead of base prices.

---

## Key Change: Using Price with VAT

### Before:
```php
$initialBasePrice = $prestation->convenience_prix ?? 0.00;
$initialBasePrice = $prestation->public_price ?? 0.00;
```

### After:
```php
// For convenience_prix: Calculate price with VAT
$conventionPrice = is_null($prestation->convenience_prix) ? 0.0 : (float) $prestation->convenience_prix;
$consumables = is_null($prestation->consumables_cost) ? 0.0 : (float) $prestation->consumables_cost;
$vat = is_null($prestation->vat_rate) ? 0.0 : (float) $prestation->vat_rate;
$base = $conventionPrice + $consumables;
$initialBasePrice = round($base * (1 + $vat / 100), 2);

// For public_prix: Use the accessor
$initialBasePrice = $prestation->price_with_vat ?? 0.00;
```

---

## Implementation Details

### AnnexCreationService

**File:** `app/Services/B2B/AnnexCreationService.php`

**Method:** `createAnnexAndInitializePrestations()`

The service now handles three pricing strategies:

#### 1. **convenience_prix** Strategy
```php
case 'convenience_prix':
    $conventionPrice = is_null($prestation->convenience_prix) ? 0.0 : (float) $prestation->convenience_prix;
    $consumables = is_null($prestation->consumables_cost) ? 0.0 : (float) $prestation->consumables_cost;
    $vat = is_null($prestation->vat_rate) ? 0.0 : (float) $prestation->vat_rate;
    $base = $conventionPrice + $consumables;
    $initialBasePrice = round($base * (1 + $vat / 100), 2);
    break;
```

**Calculation:**
- Base = convenience_prix + consumables_cost
- TTC = Base × (1 + VAT/100)
- Example: 2000 + 0 = 2000 × 1.09 = **2180 MAD**

#### 2. **public_prix** Strategy
```php
case 'public_prix':
    $initialBasePrice = $prestation->price_with_vat ?? 0.00;
    break;
```

**Calculation:**
- Uses Prestation model's `getPriceWithVatAttribute()` accessor
- Base = public_price + consumables_cost
- TTC = Base × (1 + VAT/100)
- Example: 1000 + 0 = 1000 × 1.09 = **1090 MAD**

#### 3. **empty** (Default) Strategy
```php
case 'empty':
default:
    $initialBasePrice = $prestation->price_with_vat ?? 0.00;
    break;
```

**Calculation:**
- Falls back to public price with VAT
- Same as public_prix strategy

---

## Flow Diagram

```
┌─────────────────────────────────────┐
│  Create Annex with Service          │
│  prestation_prix_status = ???       │
└──────────────┬──────────────────────┘
               │
               ▼
┌──────────────────────────────────────┐
│ Fetch all Prestations for Service   │
└──────────────┬───────────────────────┘
               │
               ▼
       ┌───────┴───────┐
       │ For Each      │
       │ Prestation    │
       └───────┬───────┘
               │
               ▼
┌──────────────────────────────────────┐
│ Calculate initialBasePrice           │
│ Based on prestation_prix_status      │
└──────────────┬───────────────────────┘
               │
       ┌───────┴────────┐
       │                │
       ▼                ▼
convenience_prix    public_prix/empty
       │                │
       ▼                ▼
Calculate VAT     Use Accessor
manually          price_with_vat
       │                │
       └────────┬───────┘
                │
                ▼
    ┌───────────────────────┐
    │ initialBasePrice      │
    │ (Price with VAT/TTC)  │
    └──────────┬────────────┘
               │
               ▼
┌──────────────────────────────────────┐
│ Calculate Company & Patient Shares   │
│ Based on discount_percentage         │
│ and max_price                        │
└──────────────┬───────────────────────┘
               │
               ▼
┌──────────────────────────────────────┐
│ Create PrestationPricing Record      │
│ - prix = initialBasePrice (TTC)      │
│ - company_price = calculated share   │
│ - patient_price = calculated share   │
└──────────────────────────────────────┘
```

---

## Example Calculation

### Scenario:
- **Prestation:** ECG
- **Public Price:** 1000 MAD
- **Convenience Price:** 2000 MAD
- **Consumables:** 0 MAD
- **VAT Rate:** 9%
- **Convention Discount:** 90% (company pays 90%)
- **Max Price:** 300 MAD

### Case 1: prestation_prix_status = 'public_prix'

```
Step 1: Calculate initialBasePrice (with VAT)
- Base = 1000 + 0 = 1000 MAD
- TTC = 1000 × (1 + 9/100) = 1000 × 1.09 = 1090 MAD
- initialBasePrice = 1090 MAD

Step 2: Calculate shares
- Company share = 1090 × 0.90 = 981 MAD
- Patient share = 1090 - 981 = 109 MAD

Step 3: Apply max_price cap (300 MAD)
- Company share exceeds max (981 > 300)
- Excess = 981 - 300 = 681 MAD
- Final company_price = 300 MAD
- Final patient_price = 109 + 681 = 790 MAD
- max_price_exceeded = true

Step 4: Store in PrestationPricing
- prix = 1090 MAD (TTC price)
- company_price = 300 MAD
- patient_price = 790 MAD
- original_company_share = 981 MAD
- original_patient_share = 109 MAD
```

### Case 2: prestation_prix_status = 'convenience_prix'

```
Step 1: Calculate initialBasePrice (with VAT)
- Base = 2000 + 0 = 2000 MAD
- TTC = 2000 × (1 + 9/100) = 2000 × 1.09 = 2180 MAD
- initialBasePrice = 2180 MAD

Step 2: Calculate shares
- Company share = 2180 × 0.90 = 1962 MAD
- Patient share = 2180 - 1962 = 218 MAD

Step 3: Apply max_price cap (300 MAD)
- Company share exceeds max (1962 > 300)
- Excess = 1962 - 300 = 1662 MAD
- Final company_price = 300 MAD
- Final patient_price = 218 + 1662 = 1880 MAD
- max_price_exceeded = true

Step 4: Store in PrestationPricing
- prix = 2180 MAD (TTC price)
- company_price = 300 MAD
- patient_price = 1880 MAD
- original_company_share = 1962 MAD
- original_patient_share = 218 MAD
```

---

## Benefits

1. **VAT Included from Start**
   - All prices stored in `prix` field include VAT
   - No need to calculate VAT separately later

2. **Consistent with PrestationPricing Model**
   - `getPriceWithVatAttribute()` in PrestationPricing uses `prix` field
   - This `prix` already contains the TTC price

3. **Accurate Financial Calculations**
   - Company and patient shares calculated on final price (TTC)
   - Matches real-world billing scenarios

4. **Transparency**
   - Clear separation: base calculation vs stored value
   - `original_company_share` and `original_patient_share` preserve pre-cap values

---

## Testing

### Test Script
Run: `php test_annex_pricing_with_vat.php`

### Expected Output
```
Case 1: prestation_prix_status = 'public_prix'
Price with VAT: 1090 MAD ✅

Case 2: prestation_prix_status = 'convenience_prix'
Calculated Price with VAT: 2180 MAD ✅

Case 3: prestation_prix_status = 'empty'
Falls back to public price with VAT: 1090 MAD ✅
```

---

## Database Schema

**prestation_pricing table:**
- `prix` (decimal:2) - **Stores TTC price** (price with VAT included)
- `company_price` (decimal:2) - Company's share (may be capped by max_price)
- `patient_price` (decimal:2) - Patient's share (absorbs excess if capped)
- `tva` (decimal:2) - VAT percentage (may be null, inherited from prestation)
- `original_company_share` (decimal:2) - Pre-cap company share
- `original_patient_share` (decimal:2) - Pre-cap patient share
- `max_price_exceeded` (boolean) - Flag if company share was capped

**prestations table:**
- `public_price` (decimal:2) - Base public price (HT)
- `convenience_prix` (decimal:2) - Base convenience price (HT)
- `vat_rate` (decimal:2) - VAT percentage
- `consumables_cost` (decimal:2) - Additional consumables cost

---

## Important Notes

1. **`prix` field in PrestationPricing = TTC Price**
   - This is the convention price WITH VAT included
   - NOT the base price without VAT

2. **Accessor Consistency**
   - `Prestation->price_with_vat` calculates: (public_price + consumables) × (1 + VAT/100)
   - `PrestationPricing->price_with_vat` calculates: (prix + consumables) × (1 + tva/100)
   - Since `prix` already includes VAT, ensure `tva` is set appropriately or is 0

3. **VAT Application**
   - VAT is applied ONCE during annex creation
   - Stored in `prix` field as TTC
   - Further calculations use this TTC price

---

## Migration Path

### If you have existing data without VAT:

```sql
-- Update existing PrestationPricing records to include VAT
UPDATE prestation_pricing pp
JOIN prestations p ON pp.prestation_id = p.id
SET pp.prix = ROUND(pp.prix * (1 + COALESCE(p.vat_rate, 0) / 100), 2)
WHERE pp.prix IS NOT NULL 
  AND pp.prix > 0
  AND p.vat_rate IS NOT NULL;
```

**⚠️ WARNING:** Test this migration on a backup first!

---

## Summary

✅ **AnnexCreationService now uses TTC prices**
✅ **All three pricing strategies include VAT**
✅ **Company/patient shares calculated on final price**
✅ **Consistent with PrestationPricing accessor**
✅ **Accurate financial reporting**

**Status:** IMPLEMENTED AND TESTED
