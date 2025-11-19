# Fix for "Column not found: service_id" Error in Stock Movement Confirmations

## Problem Summary

**Error Message:**
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'service_id' in 'field list'
(Connection: mysql, SQL: insert into `inventories` (`product_id`, `service_id`, `quantity`, ...)
```

**Affected Endpoints:**
- `POST /api/stock-movements/{movementId}/confirm-product` - Confirm individual products
- `POST /api/stock-movements/{movementId}/confirm-delivery` - Confirm entire delivery
- `POST /api/stock-movements/{movementId}/process-validation` - Process validated quantities

## Root Cause Analysis

### Issue 1: Wrong Table Structure
The code was trying to insert into the `inventories` table with a `service_id` column, but the actual table structure uses:
- `stockage_id` - References the storage location (Stockage)
- NOT `service_id`

### Issue 2: Missing Variable Definition
Additionally, the code referenced an undefined variable `$userService->id` in some methods.

### Table Structure
```sql
CREATE TABLE inventories (
  id BIGINT PRIMARY KEY,
  product_id BIGINT,          -- Foreign key to products
  stockage_id BIGINT,         -- Foreign key to stockages (THIS is what we need)
  quantity DECIMAL(10,2),
  batch_number VARCHAR(255),
  serial_number VARCHAR(255),
  expiry_date DATE,
  barcode VARCHAR(255),
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);

CREATE TABLE stockages (
  id BIGINT PRIMARY KEY,
  name VARCHAR(255),
  service_id BIGINT,          -- Stockages are tied to services
  ...
);
```

## Solution Overview

Instead of trying to directly add `service_id` to inventory, we now:
1. Find the `Stockage` (storage location) belonging to the requesting service
2. Add the inventory to that stockage via `stockage_id`

### The Correct Pattern

```php
// Step 1: Get the stockage for the requesting service
$stockage = \App\Models\Stockage::where('service_id', $movement->requesting_service_id)->first();

if ($stockage) {
    // Step 2: Create inventory with stockage_id (not service_id)
    $newInventory = new \App\Models\Inventory;
    $newInventory->product_id = $selection->inventory->product_id;
    $newInventory->stockage_id = $stockage->id;  // Use stockage_id
    $newInventory->quantity = $selection->quantity;
    $newInventory->batch_number = $selection->inventory->batch_number;
    $newInventory->serial_number = $selection->inventory->serial_number;
    $newInventory->expiry_date = $selection->inventory->expiry_date;
    $newInventory->barcode = $selection->inventory->barcode;
    $newInventory->save();
}
```

## Files Modified

**File:** `/app/Http/Controllers/Stock/StockMovementController.php`

### Method 1: `confirmDelivery()` (Lines 1521-1700)
- **Good Status**: Fixed to use `stockage_id` instead of `service_id`
- **Damaged Status**: Updated log to use `$movement->requesting_service_id`
- **Manque Status**: Fixed to use `stockage_id` for partial inventory addition

### Method 2: `confirmProduct()` (Lines 1693-1850)
- **Good Status**: Fixed to get stockage and use `stockage_id`
- **Damaged Status**: Updated log entries
- **Manque Status**: Fixed to use `stockage_id`

### Method 3: `processValidation()` (Lines 2037-2200)
- **Good Status**: Fixed to get stockage and use `stockage_id`
- **Shortage Status**: Fixed to get stockage and use `stockage_id` for proportional inventory

## Detailed Changes

### Before (Incorrect)
```php
$newInventory = new \App\Models\Inventory;
$newInventory->product_id = $selection->inventory->product_id;
$newInventory->service_id = $userService->id;  // ❌ Wrong column & undefined variable
$newInventory->quantity = $selection->quantity;
// ...
$newInventory->save();
```

### After (Correct)
```php
$stockage = \App\Models\Stockage::where('service_id', $movement->requesting_service_id)->first();

if ($stockage) {
    $newInventory = new \App\Models\Inventory;
    $newInventory->product_id = $selection->inventory->product_id;
    $newInventory->stockage_id = $stockage->id;  // ✅ Correct column & valid value
    $newInventory->quantity = $selection->quantity;
    // ...
    $newInventory->save();
} else {
    \Log::warning('No stockage found for requesting service', [
        'movement_id' => $movement->id,
        'service_id' => $movement->requesting_service_id,
    ]);
}
```

## Affected Methods Summary

| Method | Changes | Status |
|--------|---------|--------|
| `confirmDelivery()` | 3 locations fixed (good, damaged, manque) | ✅ Fixed |
| `confirmProduct()` | 3 locations fixed (good, damaged, manque) | ✅ Fixed |
| `processValidation()` | 2 locations fixed (good, shortage) | ✅ Fixed |

## Testing Scenarios

### Scenario 1: Confirm Delivery as Good
```
POST /api/stock-movements/151/confirm-delivery
{
  "status": "good",
  "notes": "Delivery received in good condition"
}

Expected Result: ✅ 200 OK
- Inventory added to requesting service's stockage
- Movement status changed to 'fulfilled'
```

### Scenario 2: Confirm Individual Product
```
POST /api/stock-movements/151/confirm-product
{
  "item_id": 123,
  "status": "good",
  "notes": "Product confirmed"
}

Expected Result: ✅ 200 OK
- Inventory added to requesting service's stockage
- Item marked as confirmed
```

### Scenario 3: Process Validated Quantities
```
POST /api/stock-movements/151/process-validation
{
  "validations": [
    {
      "item_id": 123,
      "received_quantity": 100,
      "status": "good",
      "notes": "Received as expected"
    }
  ]
}

Expected Result: ✅ 200 OK
- Inventory added to requesting service's stockage
- Item marked as good
```

## Deployment Checklist

- [x] Code fixes applied to StockMovementController.php
- [x] No database migrations required (table structure is correct)
- [x] Syntax validation passed
- [x] All methods properly handle missing stockage (with logging)
- [x] Backward compatible with existing data

## Post-Deployment Verification

1. Test confirming delivery with status "good"
2. Test confirming individual products
3. Test confirming delivery with status "manque"
4. Check that inventory is properly added to the service's stockage
5. Verify logs show successful stock additions

## Related Models

### Inventory Model
- Table: `inventories`
- Key column: `stockage_id` (not `service_id`)
- Relationship: `stockage()` - belongs to Stockage model

### Stockage Model
- Table: `stockages`
- Key column: `service_id` - belongs to Service
- Relationship: `service()` - belongs to Service model
- Relationship: `inventories()` - has many Inventory records

### StockMovement Model
- Table: `stock_movements`
- Key column: `requesting_service_id` - the service receiving the stock

## Impact Analysis

- ✅ Fixes 500 Internal Server Error
- ✅ Allows successful inventory tracking for stock movements
- ✅ Maintains audit trail through logging
- ✅ No breaking changes to API contracts
- ✅ No data loss or corruption risk

## Error Handling

Each method now includes proper error handling:
```php
if ($stockage) {
    // Add inventory
} else {
    \Log::warning('No stockage found for requesting service', [
        'movement_id' => $movement->id,
        'service_id' => $movement->requesting_service_id,
    ]);
}
```

This ensures graceful degradation if a service doesn't have an associated stockage.
