# Consignment Workflow - Database Testing Guide

**Purpose:** This guide shows you exactly how to inspect the complete consignment workflow progression by running SQL queries directly against your database.

---

## Overview

The consignment workflow has 4 phases that you can observe in the database:

1. **Phase 1: Reception** - ConsignmentReception created (bon_reception_id = NULL)
2. **Phase 2: Consumption** - Products consumed via FicheNavette (is_paid=false)
3. **Phase 3: Payment** - Consultation paid (is_paid=true)
4. **Phase 4: Invoicing** - BonReception + BonCommend auto-created

---

## SQL Queries to Inspect Each Phase

### Phase 1: View ConsignmentReceptions

See when consignments are created and track when bon_reception_id becomes populated.

```sql
SELECT 
    id,
    consignment_code,
    fournisseur_id,
    bon_reception_id,
    bon_entree_id,
    reception_date,
    created_at,
    updated_at
FROM consignment_receptions
ORDER BY created_at DESC;
```

**What to observe:**
- `bon_reception_id` is initially **NULL** ✓ (deferred)
- After invoicing, `bon_reception_id` becomes populated
- `consignment_code` is auto-generated (CS-YYYY-##)

---

### Phase 1 & 2: View ConsignmentReceptionItems

Track quantity progression: received → consumed → invoiced

```sql
SELECT 
    cri.id,
    cr.consignment_code,
    p.name as product_name,
    cri.quantity_received,
    cri.quantity_consumed,
    cri.quantity_invoiced,
    (cri.quantity_consumed - cri.quantity_invoiced) as remaining,
    cri.unit_price,
    (cri.quantity_consumed * cri.unit_price) as consumed_amount,
    cri.created_at
FROM consignment_reception_items cri
JOIN consignment_receptions cr ON cri.consignment_reception_id = cr.id
JOIN products p ON cri.product_id = p.id
ORDER BY cri.created_at DESC;
```

**What to observe:**
- `quantity_received`: Initial amount received from supplier
- `quantity_consumed`: Increases when products added to consultation
- `quantity_invoiced`: Increases when invoice created
- `remaining`: = consumed - invoiced (items not yet invoiced)

**Expected progression:**
```
quantity_received: 50
quantity_consumed: 0 (initially)
  ↓
quantity_consumed: 20 (after consultation)
quantity_invoiced: 0 (still unpaid)
  ↓
quantity_consumed: 20
quantity_invoiced: 20 (after invoice created)
```

---

### Phase 2: View FicheNavettes with Consignment Products

See consultations that use consignment products.

```sql
SELECT 
    fn.id,
    fn.patient_id,
    fn.doctor_id,
    fn.is_paid,
    COUNT(fni.id) as item_count,
    SUM(fni.quantity * fni.unit_price) as total_amount,
    fn.consultation_date,
    fn.created_at
FROM fiche_navettes fn
JOIN fiche_navette_items fni ON fn.id = fni.fiche_navette_id
WHERE fni.is_from_consignment = true
GROUP BY fn.id
ORDER BY fn.created_at DESC;
```

**What to observe:**
- `is_paid`: Initially **false** (Phase 2)
- Changes to **true** when patient pays (Phase 3)
- Only consultations with consignment items shown

---

### Phase 2 & 3: View FicheNavetteItems (Consignment Products)

Track consumption and payment of individual consignment products.

```sql
SELECT 
    fni.id,
    fn.id as fiche_id,
    p.name as product_name,
    fni.quantity,
    fni.unit_price,
    fni.is_from_consignment,
    fni.is_paid,
    (fni.quantity * fni.unit_price) as total,
    fni.created_at
FROM fiche_navette_items fni
JOIN fiche_navettes fn ON fni.fiche_navette_id = fn.id
JOIN products p ON fni.product_id = p.id
WHERE fni.is_from_consignment = true
ORDER BY fni.created_at DESC;
```

**What to observe:**
- `is_from_consignment`: true (linked to consignment)
- `is_paid`: false initially, true after payment
- Shows exact products consumed in each consultation

**Expected progression:**
```
Phase 2 (Consumption):
  is_from_consignment: true
  is_paid: false
  
  ↓
  
Phase 3 (Payment):
  is_from_consignment: true
  is_paid: true
```

---

### Phase 4: View BonReceptions (Auto-created)

See BonReceptions that were auto-created from consignments.

```sql
SELECT 
    br.id,
    br.bon_reception_code,
    br.fournisseur_id,
    br.consignment_source_id,
    br.is_from_consignment,
    cr.consignment_code,
    br.reception_date,
    br.created_at,
    br.updated_at
FROM bon_receptions br
LEFT JOIN consignment_receptions cr ON br.consignment_source_id = cr.id
WHERE br.is_from_consignment = true
ORDER BY br.created_at DESC;
```

**What to observe:**
- `is_from_consignment`: true
- `consignment_source_id`: Points to ConsignmentReception ID
- Created **automatically** when invoice created (Phase 4)
- `bon_reception_code`: Auto-generated (BR-YYYY-##)

---

### Phase 4: View BonCommends (Auto-created)

See BonCommends (supplier invoices) auto-created from consignments.

```sql
SELECT 
    bc.id,
    bc.bon_commend_code,
    bc.fournisseur_id,
    bc.is_from_consignment,
    bc.consignment_source_id,
    cr.consignment_code,
    bc.total_amount,
    bc.created_at
FROM bon_commends bc
LEFT JOIN consignment_receptions cr ON bc.consignment_source_id = cr.id
WHERE bc.is_from_consignment = true
ORDER BY bc.created_at DESC;
```

**What to observe:**
- `is_from_consignment`: true
- `consignment_source_id`: Points to ConsignmentReception
- Created **together with BonReception** in same transaction
- `total_amount`: Sum of consumed items
- `bon_commend_code`: Auto-generated (BC-YYYY-##)

---

## Complete Workflow Audit Trail

See the **entire workflow progression** from reception to invoice in one query:

```sql
SELECT 
    cr.id as consignment_id,
    cr.consignment_code,
    cr.bon_reception_id,
    cr.created_at as phase1_reception_date,
    
    fn.id as fiche_id,
    fn.is_paid,
    fn.created_at as phase2_consumption_date,
    
    (CASE WHEN fn.is_paid = true THEN fn.updated_at ELSE NULL END) as phase3_payment_date,
    
    br.id as bon_reception_id,
    br.bon_reception_code,
    br.created_at as phase4_bon_reception_date,
    
    bc.id as bon_commend_id,
    bc.bon_commend_code,
    bc.total_amount,
    bc.created_at as phase4_bon_commend_date
    
FROM consignment_receptions cr
LEFT JOIN fiche_navette_items fni ON EXISTS (
    SELECT 1 FROM fiche_navette_items fni2
    WHERE fni2.consignment_reception_id = cr.id
)
LEFT JOIN fiche_navettes fn ON fn.id = (
    SELECT fni3.fiche_navette_id 
    FROM fiche_navette_items fni3
    WHERE fni3.is_from_consignment = true
    LIMIT 1
)
LEFT JOIN bon_receptions br ON cr.bon_reception_id = br.id
LEFT JOIN bon_commends bc ON bc.consignment_source_id = cr.id
ORDER BY cr.created_at DESC;
```

**This shows the entire workflow in one row:**
- Phase 1 timestamp (Reception)
- Phase 2 timestamp (Consumption)
- Phase 3 timestamp (Payment)
- Phase 4 timestamps (BonReception + BonCommend creation)

---

## Workflow Summary Query

Quick count of items in each phase:

```sql
SELECT 'Phase 1: Reception' as phase, COUNT(*) as count 
FROM consignment_receptions 
WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
UNION ALL
SELECT 'Phase 2: Consumption', COUNT(*) 
FROM fiche_navettes 
WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR) 
AND id IN (
    SELECT DISTINCT fiche_navette_id 
    FROM fiche_navette_items 
    WHERE is_from_consignment = true
)
UNION ALL
SELECT 'Phase 3: Payment (Paid)', COUNT(*) 
FROM fiche_navettes 
WHERE is_paid = true 
AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
AND id IN (
    SELECT DISTINCT fiche_navette_id 
    FROM fiche_navette_items 
    WHERE is_from_consignment = true
)
UNION ALL
SELECT 'Phase 4: Invoicing', COUNT(*) 
FROM bon_commends 
WHERE is_from_consignment = true 
AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR);
```

---

## Key Points to Verify

### ✓ Phase 1: Reception
```sql
-- Verify bon_reception_id is NULL initially
SELECT id, consignment_code, bon_reception_id
FROM consignment_receptions
WHERE bon_reception_id IS NULL;  -- Should have rows

-- Verify products are on-loan (excluded from audit)
SELECT * FROM consignment_reception_items
WHERE quantity_consumed > 0 AND quantity_invoiced < quantity_consumed;
```

### ✓ Phase 2: Consumption
```sql
-- Verify consumption is tracked
SELECT 
    cr.consignment_code,
    SUM(cri.quantity_consumed) as total_consumed
FROM consignment_receptions cr
JOIN consignment_reception_items cri ON cr.id = cri.consignment_reception_id
GROUP BY cr.id
HAVING SUM(cri.quantity_consumed) > 0;

-- Verify consultation not yet paid
SELECT id, is_paid 
FROM fiche_navettes 
WHERE is_paid = false 
AND id IN (
    SELECT DISTINCT fiche_navette_id 
    FROM fiche_navette_items 
    WHERE is_from_consignment = true
);
```

### ✓ Phase 3: Payment
```sql
-- Verify consultation paid
SELECT id, is_paid 
FROM fiche_navettes 
WHERE is_paid = true 
AND id IN (
    SELECT DISTINCT fiche_navette_id 
    FROM fiche_navette_items 
    WHERE is_from_consignment = true
);

-- Verify all fiche items paid
SELECT * FROM fiche_navette_items
WHERE is_from_consignment = true AND is_paid = true;
```

### ✓ Phase 4: Invoicing
```sql
-- Verify BonReception auto-created
SELECT cr.consignment_code, br.bon_reception_code
FROM consignment_receptions cr
JOIN bon_receptions br ON cr.bon_reception_id = br.id
WHERE br.is_from_consignment = true;

-- Verify BonCommend linked to BonReception
SELECT bc.bon_commend_code, br.bon_reception_code
FROM bon_commends bc
JOIN bon_receptions br ON bc.consignment_source_id = br.consignment_source_id
WHERE bc.is_from_consignment = true;

-- Verify items invoiced
SELECT 
    cr.consignment_code,
    SUM(cri.quantity_invoiced) as total_invoiced
FROM consignment_receptions cr
JOIN consignment_reception_items cri ON cr.id = cri.consignment_reception_id
GROUP BY cr.id;
```

---

## Running the Test

To run the test guide that displays these queries:

```bash
php artisan test tests/Feature/Purchasing/ConsignmentWorkflowTestGuideTest.php
```

This will display all SQL queries in an easy-to-copy format.

---

## Expected Results Timeline

```
Time 1 (T₀):
  ConsignmentReception created
  ✓ bon_reception_id = NULL
  ✓ Items: quantity_received > 0, quantity_consumed = 0

Time 2 (T₀ + few minutes):
  FicheNavette created
  ✓ is_paid = false
  ✓ FicheNavetteItems added
  ✓ quantity_consumed increased in ConsignmentReceptionItems

Time 3 (T₀ + a few more minutes):
  FicheNavette.is_paid changed to true
  ✓ Patient paid for consultation

Time 4 (T₀ + a few more minutes):
  createInvoiceFromConsumption() executed
  ✓ BonReception auto-created (is_from_consignment=true)
  ✓ BonCommend auto-created (is_from_consignment=true)
  ✓ consignment_receptions.bon_reception_id populated
  ✓ quantity_invoiced incremented
```

---

## Notes

- All test data persists in database for inspection
- No automatic cleanup - you can inspect data anytime
- Use `DATE_SUB(NOW(), INTERVAL 1 HOUR)` to filter recent test runs
- Check `is_from_consignment` field to identify consignment-related records
- Track timestamps to see exact workflow progression
- Compare `quantity_consumed` vs `quantity_invoiced` to see invoicing progress

