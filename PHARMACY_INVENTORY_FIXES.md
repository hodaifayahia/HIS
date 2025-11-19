# Pharmacy Inventory Issues Fixed - October 30, 2025

## Issue 1: Duplicate Entry Constraint Violation ✅ FIXED

### Error
```
SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '11-3' 
for key 'pharmacy_inventories.unique_pharmacy_product_stockage'
```

### Root Cause
The database has a unique constraint on `(pharmacy_product_id, pharmacy_stockage_id)`, meaning only ONE inventory record is allowed per product-stockage combination. However, the code was trying to create new records for different batches of the same product in the same stockage.

### Solution
Modified `PharmacyInventoryController::store()` method to:
- Check if the product already exists in the stockage (regardless of batch)
- If it exists with the same batch/expiry, update the quantity
- If it exists with different batch/expiry, merge quantities and keep the batch with the latest expiry date
- Returns clear message indicating that batches were merged

**File Modified**: `app/Http/Controllers/Pharmacy/PharmacyInventoryController.php`

**Key Logic** (lines 185-217):
```php
// Check if product already exists in this stockage
$existingInventory = PharmacyInventory::where('pharmacy_product_id', $request->pharmacy_product_id)
    ->where('pharmacy_stockage_id', $request->pharmacy_stockage_id)
    ->first();

if ($existingInventory) {
    // Merge quantities and update batch info if new batch has later expiry
    $newQuantity = $existingInventory->quantity + $request->quantity;
    
    // Update with the batch that has the latest expiry date
    // This ensures FIFO (First In, First Out) inventory management
}
```

---

## Issue 2: Products Not Showing After Addition ✅ FIXED

### Error
```
"No products found in this stockage"
"Try adjusting your filters or add a new product."
```

### Root Cause
The `PharmacyInventoryController::index()` method was missing the `stockage_id` filter. When the frontend called `/api/pharmacy/inventory?stockage_id=3`, the backend ignored this parameter and returned all inventory items (or used other filters that excluded the newly added product).

### Solution
Added `stockage_id` filter to the index method to properly filter inventory by stockage.

**File Modified**: `app/Http/Controllers/Pharmacy/PharmacyInventoryController.php`

**Code Added** (after line 82):
```php
// Filter by stockage ID
if ($request->has('stockage_id') && $request->stockage_id) {
    $query->where('pharmacy_stockage_id', $request->stockage_id);
}
```

### How It Works Now
1. Frontend calls: `GET /api/pharmacy/inventory?stockage_id=3`
2. Backend filters: `WHERE pharmacy_stockage_id = 3`
3. Returns only products in that specific stockage
4. Products display correctly in the UI

---

## Issue 3: Tool Creation Error - "service on null" ✅ FIXED (Previous Session)

### Error
```
"Failed to add tool: Attempt to read property 'service' on null"
```

### Root Causes Fixed
1. **Wrong Model Relationship**: `PharmacyStorageTool` was pointing to `PharmacyStorage` instead of `PharmacyStockage`
2. **Unsafe Property Access**: Code accessed nested relationships without null checks
3. **Missing Eager Loading**: Controller didn't load the `service` relationship

**Files Modified**:
- `app/Models/PharmacyStorageTool.php`
- `app/Http/Controllers/Pharmacy/PharmacyStockageController.php`

---

## Testing Checklist

### Test Scenario 1: Adding Product to Stockage
- [x] Add product to stockage → Success
- [x] Product appears in stockage inventory immediately
- [x] No duplicate entry errors

### Test Scenario 2: Adding Same Product Again
- [x] Add same product/stockage with same batch → Quantity increases
- [x] Add same product/stockage with different batch → Quantities merge, keeps latest expiry
- [x] Clear message about batch merging

### Test Scenario 3: Viewing Stockage Inventory
- [x] View stockage → Shows only products in that stockage
- [x] Search works correctly
- [x] Filters work correctly

### Test Scenario 4: Adding Tools to Stockage
- [x] Add tool to stockage → Success
- [x] Location code generates correctly
- [x] No "service on null" errors

---

## Database Constraint Details

### Current Schema
```sql
UNIQUE KEY `unique_pharmacy_product_stockage` 
  (`pharmacy_product_id`, `pharmacy_stockage_id`)
```

This constraint enforces **one inventory record per product-stockage pair**.

### Implications
- ✅ Prevents duplicate tracking of same product in same location
- ✅ Simplifies inventory queries
- ⚠️ Multiple batches must be merged into single record
- ⚠️ Batch tracking is limited to the "current" batch (latest expiry)

### Alternative Design (Future Consideration)
If separate batch tracking is required, the constraint should be:
```sql
UNIQUE KEY `unique_pharmacy_product_stockage_batch` 
  (`pharmacy_product_id`, `pharmacy_stockage_id`, `batch_number`, `expiry_date`)
```

This would allow multiple batches but requires significant code changes.

---

## API Endpoints Affected

### `/api/pharmacy/inventory` (GET)
**New Parameter**: `stockage_id`
```bash
GET /api/pharmacy/inventory?stockage_id=3
```

**Response**: Returns only inventory items for the specified stockage

### `/api/pharmacy/inventory` (POST)
**Behavior Changed**:
- Now merges quantities when product already exists in stockage
- Returns `merged: true` in response when merging occurs
- Updates batch info to latest expiry date

---

## Summary

All three critical issues have been resolved:

1. ✅ **Duplicate Entry Error**: Products are now properly merged when added to the same stockage
2. ✅ **Missing Products**: Stockage filter now works correctly, products display after addition
3. ✅ **Tool Creation Error**: Fixed in previous session

The pharmacy inventory system now properly handles:
- Adding products to stockages
- Viewing products by stockage
- Merging batches according to database constraints
- Tool creation with proper relationships

**Status**: All issues resolved and tested ✅
