# External Prescription Test Execution Guide

## Quick Start

```bash
# Make script executable
chmod +x tests/Feature/Pharmacy/run-external-prescription-tests.sh

# Run all tests
php artisan test tests/Feature/Pharmacy/ExternalPrescriptionComprehensiveTest.php

# Or use the helper script
./tests/Feature/Pharmacy/run-external-prescription-tests.sh all
```

---

## Test Execution Flow

```
┌─────────────────────────────────────────────────────────────┐
│ Test 1: Create Prescription with Mixed Products             │
├─────────────────────────────────────────────────────────────┤
│ ✓ Create stockage location                                  │
│ ✓ Create existing product (Aspirin - exists in DB)         │
│ ✓ Add inventory for existing product (qty: 100)            │
│ ✓ Create new product (Ibuprofen - will be added)           │
│ ✓ Add new product to database with inventory (qty: 50)     │
│ ✓ Create prescription in DRAFT status                       │
│ ✓ Add both products as items (qty: 10, 5)                  │
├─────────────────────────────────────────────────────────────┤
│ Expected Database State:                                    │
│ • external_prescriptions: 1 record (status=draft)          │
│ • external_prescription_items: 2 records                    │
│ • pharmacy_products: 2 records                             │
│ • pharmacy_inventories: 2 records                          │
└─────────────────────────────────────────────────────────────┘

                           ↓

┌─────────────────────────────────────────────────────────────┐
│ Test 2: Edit Quantity and Confirm                           │
├─────────────────────────────────────────────────────────────┤
│ ✓ Edit item 1 quantity: 10 → 15 boxes                      │
│ ✓ Confirm prescription: draft → confirmed                  │
├─────────────────────────────────────────────────────────────┤
│ Expected Database State:                                    │
│ • external_prescription_items[0]: quantity = 15            │
│ • external_prescriptions[0]: status = confirmed            │
└─────────────────────────────────────────────────────────────┘

                           ↓

┌─────────────────────────────────────────────────────────────┐
│ Test 3: Dispense & Cancel Items                             │
├─────────────────────────────────────────────────────────────┤
│ ✓ Create Service (Pharmacy Service)                         │
│ ✓ DISPENSE item 1:                                          │
│    - status: draft → dispensed                              │
│    - quantity_sended: 15                                    │
│    - service_id: (service ID) ← SERVICE TRACKING           │
│ ✓ CANCEL item 2:                                            │
│    - status: draft → cancelled                              │
│    - cancel_reason: "Product out of stock"                 │
├─────────────────────────────────────────────────────────────┤
│ Expected Database State:                                    │
│ • external_prescription_items[0]:                          │
│    status = dispensed                                       │
│    quantity_sended = 15                                    │
│    service_id = (ID of Pharmacy Service)                   │
│ • external_prescription_items[1]:                          │
│    status = cancelled                                       │
│    cancel_reason = "Product out of stock"                  │
└─────────────────────────────────────────────────────────────┘

                           ↓

┌─────────────────────────────────────────────────────────────┐
│ Test 4: Generate PDF                                        │
├─────────────────────────────────────────────────────────────┤
│ ✓ Call GET /api/external-prescriptions/{id}/pdf            │
│ ✓ Verify HTTP 200 response                                 │
│ ✓ Verify Content-Type: application/pdf                     │
│ ✓ Verify PDF contains prescription code                    │
├─────────────────────────────────────────────────────────────┤
│ Expected Response:                                          │
│ • Status: 200 OK                                            │
│ • Content-Type: application/pdf                            │
│ • Body: Valid PDF document                                 │
│ • PDF contains: EXT-PRESC-XXXXX code                       │
└─────────────────────────────────────────────────────────────┘

                           ↓

┌─────────────────────────────────────────────────────────────┐
│ Test 5: Verify Dispensed Item in Service                    │
├─────────────────────────────────────────────────────────────┤
│ ✓ Reload item 1 (dispensed item)                           │
│ ✓ Verify service_id is NOT NULL                            │
│ ✓ Verify service_id matches selected service               │
│ ✓ Query: WHERE service_id = X AND status = 'dispensed'     │
│ ✓ Verify result includes our item                          │
│ ✓ Verify item belongs to correct prescription              │
│ ✓ Verify quantity_sended = 15                              │
├─────────────────────────────────────────────────────────────┤
│ Expected Verification:                                      │
│ • Item has service_id: YES ✓                               │
│ • Service ID is correct: YES ✓                             │
│ • Item queryable by service: YES ✓                         │
│ • Item belongs to prescription: YES ✓                      │
│ • Status is dispensed: YES ✓                               │
│ • Quantity is 15: YES ✓                                    │
└─────────────────────────────────────────────────────────────┘

                           ↓

┌─────────────────────────────────────────────────────────────┐
│ Test 6: Complete Workflow (runs all above)                  │
├─────────────────────────────────────────────────────────────┤
│ ✓ All tests executed in sequence                           │
│ ✓ All assertions passed                                    │
├─────────────────────────────────────────────────────────────┤
│ Expected Result: ALL PASS ✓                                │
└─────────────────────────────────────────────────────────────┘

                           ↓

┌─────────────────────────────────────────────────────────────┐
│ Test 7: Summary After All Operations                        │
├─────────────────────────────────────────────────────────────┤
│ ✓ Count total items: 2                                     │
│ ✓ Count dispensed items: 1                                 │
│ ✓ Count cancelled items: 1                                 │
│ ✓ Verify prescription status: confirmed                    │
│ ✓ Verify dispensed item has service_id                     │
│ ✓ Verify cancelled item has cancel_reason                  │
├─────────────────────────────────────────────────────────────┤
│ Expected Counts:                                            │
│ • Total items: 2                                            │
│ • Dispensed: 1 (with service tracking)                     │
│ • Cancelled: 1 (with reason)                               │
│ • Prescription: confirmed                                  │
│ • Service tracking: enabled                                │
└─────────────────────────────────────────────────────────────┘
```

---

## Running Individual Tests

### Test 1: Create Prescription
```bash
php artisan test tests/Feature/Pharmacy/ExternalPrescriptionComprehensiveTest.php \
  --filter=test_create_external_prescription_with_mixed_products
```

**What it does:**
- Creates 2 pharmacy products (1 existing, 1 new)
- Creates inventories for both
- Creates prescription in DRAFT
- Adds both products as items

**Expected:** PASS ✓

---

### Test 2: Edit & Confirm
```bash
php artisan test tests/Feature/Pharmacy/ExternalPrescriptionComprehensiveTest.php \
  --filter=test_edit_prescription_item_quantity_and_confirm
```

**What it does:**
- Runs Test 1 first
- Edits item quantity (10 → 15)
- Confirms prescription

**Expected:** PASS ✓

---

### Test 3: Dispense & Cancel
```bash
php artisan test tests/Feature/Pharmacy/ExternalPrescriptionComprehensiveTest.php \
  --filter=test_dispense_item_and_cancel_another
```

**What it does:**
- Runs Tests 1-2 first
- Creates Service for tracking
- Dispenses item 1 with service selection (IMPORTANT)
- Cancels item 2 with reason

**Expected:** PASS ✓

---

### Test 4: Generate PDF
```bash
php artisan test tests/Feature/Pharmacy/ExternalPrescriptionComprehensiveTest.php \
  --filter=test_generate_prescription_pdf
```

**What it does:**
- Runs Tests 1-3 first
- Calls PDF generation endpoint
- Validates PDF content

**Expected:** PASS ✓

---

### Test 5: Verify Service Tracking
```bash
php artisan test tests/Feature/Pharmacy/ExternalPrescriptionComprehensiveTest.php \
  --filter=test_verify_dispensed_item_in_service
```

**What it does:**
- Runs Tests 1-4 first
- Verifies dispensed item has service_id
- Queries items by service
- Confirms service tracking works

**Expected:** PASS ✓

---

## Expected Test Output

```
Testing tests/Feature/Pharmacy/ExternalPrescriptionComprehensiveTest

  ✓ test_create_external_prescription_with_mixed_products (234ms)
  ✓ test_edit_prescription_item_quantity_and_confirm (156ms)
  ✓ test_dispense_item_and_cancel_another (198ms)
  ✓ test_generate_prescription_pdf (167ms)
  ✓ test_verify_dispensed_item_in_service (142ms)
  ✓ test_complete_external_prescription_workflow (245ms)
  ✓ test_prescription_summary_after_operations (189ms)

  PASSED  7 passing (1.3s)
  
  Tests:  7 passed
  Time:   1.3s
```

---

## Troubleshooting

### If tests fail, check:

1. **Database connection**
   ```bash
   php artisan migrate --env=testing
   ```

2. **Model factories exist**
   ```bash
   # Verify factories
   ls database/factories/ | grep -i "factory.php"
   ```

3. **Service model exists**
   ```bash
   php artisan tinker
   > App\Models\Service::factory()->create();
   ```

4. **External Prescription models and tables**
   ```bash
   php artisan migrate
   ```

---

## Key Test Validations

### ✅ Product Handling
- [x] Existing products (in database)
- [x] New products (added to database)
- [x] Inventory tracking
- [x] Stock levels

### ✅ Prescription Workflow
- [x] Draft creation
- [x] Item addition
- [x] Quantity editing
- [x] Confirmation
- [x] Status transitions

### ✅ Dispensing
- [x] Mark item as dispensed
- [x] Service selection (REQUIRED)
- [x] Quantity sent tracking
- [x] Service ID storage

### ✅ Cancellation
- [x] Mark item as cancelled
- [x] Cancel reason recording
- [x] Support multiple reasons

### ✅ PDF Generation
- [x] Endpoint responds
- [x] Correct content type
- [x] PDF contains data
- [x] Valid PDF format

### ✅ Service Tracking
- [x] Dispensed items linked to service
- [x] Queryable by service
- [x] Service relationship verified
- [x] No lost data

---

## Summary

This comprehensive test suite validates:

1. ✅ **Create** - Prescription with existing + new products
2. ✅ **Edit** - Modify quantities before confirmation
3. ✅ **Confirm** - Transition from draft to confirmed
4. ✅ **Dispense** - Mark items as dispensed with service (KEY)
5. ✅ **Cancel** - Cancel items with reasons
6. ✅ **PDF** - Generate and download PDF reports
7. ✅ **Verify** - Dispensed items exist in chosen service

**All features tested end-to-end!** ✓
