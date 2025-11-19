# Quick Reference: VAT Pricing System

## 🎯 Key Concept

**The `prix` field in `prestation_pricing` table ALWAYS contains TTC (price with VAT included).**

---

## 📊 Quick Comparison

| Component | Role | VAT Handling |
|-----------|------|--------------|
| **Prestation** | Base pricing | Has HT prices + VAT rate |
| **PrestationPricing** | Convention pricing | Has TTC in `prix` field |
| **AnnexCreationService** | Initial setup | Calculates TTC from HT |
| **AvenantService** | Amendments | Duplicates TTC as-is |

---

## 🔄 Data Flow

```
Prestation (HT)
    ↓ + VAT
PrestationPricing (TTC) ← Created by AnnexCreationService
    ↓ duplicate
Avenant 1 (TTC) ← Created by AvenantService
    ↓ duplicate  
Avenant 2 (TTC) ← Created by AvenantService
    ↓ ...
```

---

## 💰 Price Calculation Examples

### Example 1: Public Price Strategy
```
Input:
- public_price: 1000 MAD (HT)
- vat_rate: 9%

Calculation:
- TTC = 1000 × 1.09 = 1090 MAD

Stored:
- prix: 1090 MAD (TTC)
```

### Example 2: Convenience Price Strategy
```
Input:
- convenience_prix: 2000 MAD (HT)
- vat_rate: 9%

Calculation:
- TTC = 2000 × 1.09 = 2180 MAD

Stored:
- prix: 2180 MAD (TTC)
```

### Example 3: With Consumables
```
Input:
- public_price: 1000 MAD (HT)
- consumables_cost: 50 MAD
- vat_rate: 9%

Calculation:
- Base = 1000 + 50 = 1050 MAD
- TTC = 1050 × 1.09 = 1144.50 MAD

Stored:
- prix: 1144.50 MAD (TTC)
```

---

## 🏢 Company/Patient Share Calculation

### Step 1: Calculate Shares on TTC
```
TTC = 1090 MAD
Convention: 90% company, 10% patient

Company share = 1090 × 90% = 981 MAD
Patient share = 1090 × 10% = 109 MAD
```

### Step 2: Apply Max Price Cap
```
Max price = 300 MAD

Company: 981 > 300
  → Capped to 300 MAD
  → Excess = 981 - 300 = 681 MAD

Patient absorbs excess:
  → 109 + 681 = 790 MAD

Result:
- company_price: 300 MAD
- patient_price: 790 MAD
- max_price_exceeded: true
```

---

## 📝 Database Fields

| Field | Example | Meaning |
|-------|---------|---------|
| `prix` | 1090 | TTC (total with tax) |
| `company_price` | 300 | Company's share (after cap) |
| `patient_price` | 790 | Patient's share (with excess) |
| `tva` | 9 | VAT percentage |
| `original_company_share` | 981 | Before capping |
| `original_patient_share` | 109 | Before capping |
| `max_price_exceeded` | true | Was price capped? |

---

## 🔌 API Usage

### Get Pricing
```javascript
GET /api/prestation-pricings?annex_id=5

Response:
{
  "pricing": {
    "prix": "1090.00",          // TTC
    "prix_with_vat": 1090,      // Same as prix
    "company_price": "300.00",
    "patient_price": "790.00",
    "tva": "9.00"
  }
}
```

### Create Annex
```javascript
POST /api/annexes
{
  "annex_name": "Services",
  "convention_id": 1,
  "service_id": 3,
  "prestation_prix_status": "public_prix"
}

// Automatically creates PrestationPricing with TTC prices
```

### Create Avenant
```javascript
POST /api/avenants/convention/1

// Automatically duplicates TTC prices from annex or previous avenant
```

---

## 🧪 Testing Commands

```bash
# Test annex pricing calculation
php test_annex_pricing_with_vat.php

# Test before/after comparison
php test_before_after_comparison.php

# Test avenant duplication
php test_avenant_vat_duplication.php
```

---

## 📚 Documentation Files

1. **ANNEX_PRICING_WITH_VAT.md** - Annex creation details
2. **AVENANT_VAT_DUPLICATION.md** - Avenant duplication details
3. **COMPLETE_VAT_SYSTEM_SUMMARY.md** - Full system overview
4. **QUICK_REFERENCE.md** - This file

---

## ⚠️ Important Notes

1. **prix = TTC always**
   - Never store HT (price without tax) in `prix`
   - Always include VAT in calculations

2. **Avenant Duplication**
   - Copies TTC as-is
   - No recalculation needed
   - Preserves all audit fields

3. **Max Price Capping**
   - Applied AFTER TTC calculation
   - Excess goes to patient
   - Original values preserved

4. **API Responses**
   - `prix` = TTC
   - `prix_with_vat` = accessor result
   - Both should be similar

---

## 🚀 Quick Start

### For Developers

```php
// Creating prices with VAT
$service = app(AnnexCreationService::class);
$annex = $service->createAnnexAndInitializePrestations($data);
// All prestations now have TTC in prix field

// Duplicating for avenant
$avenantService = app(AvenantService::class);
$result = $avenantService->duplicateAllPrestationsWithNewAvenant($conventionId);
// All prices duplicated with TTC preserved

// Accessing prices
$pricing = PrestationPricing::find(1);
$ttc = $pricing->prix; // Already TTC
$withVat = $pricing->price_with_vat; // Uses accessor
```

### For Frontend

```javascript
// Display pricing
const pricing = prestationPricing.pricing;
console.log('Price (TTC):', pricing.prix);
console.log('Company pays:', pricing.company_price);
console.log('Patient pays:', pricing.patient_price);
console.log('VAT rate:', pricing.tva + '%');

// Show if capped
if (pricing.details.max_price_exceeded) {
  console.log('⚠️ Price was capped');
  console.log('Original company share:', pricing.details.original_company_share);
}
```

---

## ✅ Checklist for New Features

When adding new features that involve pricing:

- [ ] Use `prix` field for TTC prices
- [ ] Calculate VAT during creation
- [ ] Preserve TTC during duplication
- [ ] Store original shares before capping
- [ ] Set `max_price_exceeded` flag if capped
- [ ] Duplicate `tva` field
- [ ] Test with VAT calculations
- [ ] Update API documentation

---

**Last Updated:** October 8, 2025  
**Status:** ✅ Production Ready
