# External Prescription Comprehensive Test Suite

## File Location
`tests/Feature/Pharmacy/ExternalPrescriptionComprehensiveTest.php`

## Test Overview

This comprehensive test suite validates the entire External Prescription workflow with the following scenarios:

---

## Test Methods

### 1. **test_create_external_prescription_with_mixed_products**
Creates a prescription with both existing and new pharmacy products.

**Steps:**
1. ✅ Create stockage location (pharmacy shelf/section)
2. ✅ Create **existing** pharmacy product (Aspirin 500mg)
3. ✅ Create inventory for existing product (quantity: 100)
4. ✅ Create **NEW** pharmacy product (Ibuprofen 400mg) - does NOT exist yet
5. ✅ Add new product to database with inventory (quantity: 50)
6. ✅ Create external prescription in **DRAFT** status
7. ✅ Add items to prescription (existing + new product)

**Assertions:**
- Prescription code auto-generated with format `EXT-PRESC-XXXXX`
- Status is `draft`
- Both items added to prescription
- Database records created correctly

**Returns:** Prescription, items, products, stockage data for next test

---

### 2. **test_edit_prescription_item_quantity_and_confirm**
Edits item quantities and confirms the prescription.

**Steps (continues from previous):**
8. ✅ Edit item1 quantity: 10 → 15 boxes
9. ✅ Update prescription status: **draft** → **confirmed**

**Assertions:**
- Item quantity updated correctly
- Prescription status changed to `confirmed`
- Database reflects all changes

**Returns:** Updated prescription and item data

---

### 3. **test_dispense_item_and_cancel_another**
Dispenses one item with service selection and cancels another.

**Steps (continues from previous):**
10. ✅ **DISPENSE item1** (Aspirin):
    - Status: draft → **dispensed**
    - Quantity sent: 15 boxes
    - **SERVICE SELECTION**: Pharmacy Service (required)
    - Tracked in external_prescription_items table with service_id

11. ✅ **CANCEL item2** (Ibuprofen):
    - Status: draft → **cancelled**
    - Cancel reason: "Product out of stock"
    - Tracked in external_prescription_items table

12. ✅ Verify prescription shows mixed statuses
    - 1 dispensed item
    - 1 cancelled item

**Assertions:**
- Dispense item has service_id (proof of service selection)
- Quantity_sended recorded correctly
- Cancel reason recorded
- Mixed statuses in prescription items

**Returns:** Prescription with service data

---

### 4. **test_generate_prescription_pdf**
Generates PDF document from prescription data.

**Steps (continues from previous):**
13. ✅ Call PDF generation endpoint: `GET /api/external-prescriptions/{id}/pdf`

**Assertions:**
- Response status: 200
- Content-Type: application/pdf
- PDF contains prescription code
- PDF is not empty (has actual content)

**Returns:** Prescription and PDF data

---

### 5. **test_verify_dispensed_item_in_service**
Verifies that dispensed item exists in the chosen service.

**Steps (continues from previous):**
14. ✅ Verify dispensed item relationships:
    - Item has service_id (not NULL)
    - Service_id matches selected service
    - Item queryable by service + status filter
    - Item belongs to correct prescription
    - Item status is "dispensed"
    - Quantity_sended is 15

**Assertions:**
- Service ID matches (service tracking works)
- Query by service returns correct item
- Prescription relationship intact
- All item data correct

---

### 6. **test_complete_external_prescription_workflow**
Runs entire workflow end-to-end.

**Steps:**
- Executes all previous tests in sequence
- Validates complete prescription lifecycle

**Result:** If all tests pass, workflow is complete ✅

---

### 7. **test_prescription_summary_after_operations**
Final verification of prescription state after all operations.

**Steps:**
- Count items by status
- Verify total items: 2
- Verify dispensed items: 1
- Verify cancelled items: 1
- Verify prescription status: confirmed
- Verify dispensed item has service_id
- Verify cancelled item has cancel_reason

**Assertions:**
- Total: 2 items
- Dispensed: 1 (with service tracking)
- Cancelled: 1 (with cancel reason)
- Prescription: confirmed

---

## Data Flow

```
CREATE
├─ Stockage (pharmacy shelf)
├─ Existing Product (Aspirin)
│  └─ Inventory: 100 boxes
└─ New Product (Ibuprofen)
   └─ Inventory: 50 boxes

CREATE PRESCRIPTION (DRAFT)
├─ Doctor: Dr. Ahmed Mohamed
├─ Items: [Aspirin (qty: 10), Ibuprofen (qty: 5)]
└─ Code: Auto-generated (EXT-PRESC-XXXXX)

EDIT ITEMS & CONFIRM
├─ Item 1: quantity 10 → 15
├─ Item 2: quantity 5 (unchanged)
└─ Prescription: draft → confirmed

DISPENSE & CANCEL
├─ Item 1: dispensed with Service (Pharmacy)
│  └─ Quantity sent: 15, Service ID: tracked
└─ Item 2: cancelled with reason

PDF GENERATION
├─ Endpoint: /api/external-prescriptions/{id}/pdf
└─ Output: PDF document

VERIFICATION
├─ Dispensed item in service: YES ✅
├─ Service tracking: YES ✅
├─ Cancel reason: YES ✅
└─ PDF generated: YES ✅
```

---

## Database Tables Used

| Table | Action | Purpose |
|-------|--------|---------|
| `external_prescriptions` | CREATE, UPDATE | Store prescription headers |
| `external_prescription_items` | CREATE, UPDATE | Store prescription line items |
| `pharmacy_products` | CREATE | Product definitions |
| `pharmacy_inventories` | CREATE | Stock levels |
| `pharmacy_stockages` | CREATE | Storage locations |
| `services` | CREATE | Service definitions for tracking |
| `doctors` | CREATE (factory) | Doctor information |
| `users` | CREATE (factory) | User who created prescription |

---

## Key Features Tested

✅ **Existing Product Handling** - Products already in pharmacy database  
✅ **New Product Addition** - Adding new products to database during workflow  
✅ **Draft Status** - Initial prescription creation in draft  
✅ **Quantity Editing** - Modifying item quantities before confirmation  
✅ **Confirmation** - Transitioning from draft to confirmed  
✅ **Dispensing** - Marking items as dispensed with service selection  
✅ **Service Selection** - Service tracking (REQUIRED on dispense)  
✅ **Item Cancellation** - Cancelling items with reasons  
✅ **Cancel Reasons** - Recording why items were cancelled  
✅ **PDF Generation** - Creating downloadable PDF reports  
✅ **Service Verification** - Confirming dispensed items exist in selected service  
✅ **Data Integrity** - All relationships and foreign keys correct  

---

## How to Run

```bash
# Run complete test suite
php artisan test tests/Feature/Pharmacy/ExternalPrescriptionComprehensiveTest.php

# Run specific test method
php artisan test tests/Feature/Pharmacy/ExternalPrescriptionComprehensiveTest.php --filter=test_create_external_prescription_with_mixed_products

# Run with verbose output
php artisan test tests/Feature/Pharmacy/ExternalPrescriptionComprehensiveTest.php --verbose

# Run with coverage
php artisan test tests/Feature/Pharmacy/ExternalPrescriptionComprehensiveTest.php --coverage
```

---

## Expected Output

```
PASS  Tests\Feature\Pharmacy\ExternalPrescriptionComprehensiveTest
  ✓ test_create_external_prescription_with_mixed_products
  ✓ test_edit_prescription_item_quantity_and_confirm
  ✓ test_dispense_item_and_cancel_another
  ✓ test_generate_prescription_pdf
  ✓ test_verify_dispensed_item_in_service
  ✓ test_complete_external_prescription_workflow
  ✓ test_prescription_summary_after_operations

Tests: 7 passed
```

---

## Test Notes

- Uses `RefreshDatabase` trait for clean database per test
- All tests are independent but return data for chaining
- Tests follow AAA pattern (Arrange, Act, Assert)
- Comprehensive error checking and validation
- Real factory models for realistic data
- Service tracking is verified at item level
- PDF content validated, not just headers

---

## Dependencies

- `PharmacyProduct` factory
- `PharmacyInventory` model
- `PharmacyStockage` factory (via `StockageFactory`)
- `Service` factory
- `Doctor` factory
- `User` factory
- External Prescription controller with PDF endpoint

All dependencies should exist in the HIS application.
