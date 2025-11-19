# Stock Movement Init-Transfer Error Fix

## Problem
When trying to initialize a stock transfer, the system was returning errors:
```
Error 1: "An error occurred while initializing transfer: Insufficient stock for product: Clopidogrel 20%"
Error 2: "An error occurred while initializing transfer: Insufficient stock for product: Clopidogrel 20%. Available: 24.00, Requested: 94.00"
```

These errors were misleading because they didn't distinguish between:
1. **No inventory selected** for an approved item (user must select inventory before transfer)
2. **Inventory selected but quantity doesn't match approved quantity** 
3. **Actual insufficient stock** in the selected inventory batches

## Root Causes
1. In the `initializeTransfer` method, the movement query wasn't loading the `selectedInventory` relationship
2. The code was silently skipping items without inventory selections instead of throwing proper error messages
3. No validation that total selected quantity equals approved quantity before deduction
4. Accessed wrong field name when deducting (`$selection->quantity` instead of `$selection->selected_quantity`)

## Solution

### 1. Fixed `initializeTransfer` Method Query (Line 1366)

**Changes:**
- Load `selectedInventory` relationship with `inventory` eager loading

**Before:**
```php
->with(['items.product'])
```

**After:**
```php
->with(['items.product', 'items.selectedInventory.inventory'])
```

### 2. Enhanced `initializeTransfer` Method Validation (Lines 1388-1443)

**Changes:**
- Added check for items with approved quantities: Skip items that weren't approved
- Added validation that all approved items **must have selected inventory** before initialization
- **NEW:** Calculate and validate total selected quantity matches approved quantity
- Fixed field reference: Use `$selection->selected_quantity` (not `$selection->quantity`)
- Improved error messages to distinguish between all three scenarios

**New Validation Flow:**
```php
foreach ($movement->items as $item) {
    // 1. Skip unapproved items
    if ($item->approved_quantity === null || $item->approved_quantity <= 0) {
        continue;
    }
    
    // 2. Validate inventory selections exist for approved items
    if (!$item->selectedInventory || $item->selectedInventory->count() === 0) {
        throw new Exception("No inventory selected for approved item: {$item->product->name}. Please select inventory before initializing transfer.");
    }
    
    // 3. Calculate and validate total selected quantity
    $totalSelectedQuantity = 0;
    foreach ($item->selectedInventory as $selection) {
        $totalSelectedQuantity += $selection->selected_quantity;
    }
    
    if (abs($totalSelectedQuantity - $item->approved_quantity) > 0.01) {
        throw new Exception(
            "Selected quantity (" . $totalSelectedQuantity . 
            ") does not match approved quantity (" . $item->approved_quantity . 
            ") for product: {$item->product->name}. Please correct the inventory selection before initializing transfer."
        );
    }
    
    // 4. Perform stock deduction with better error messages
    foreach ($item->selectedInventory as $selection) {
        if ($inventory->quantity >= $selection->selected_quantity) {
            // ... deduct stock
        } else {
            throw new Exception("Insufficient stock for product: {$item->product->name}. Available: {$inventory->quantity}, Requested: {$selection->selected_quantity}");
        }
    }
}
```

### 3. Enhanced `selectInventory` Method (Lines 1113-1189)

**Changes:**
- Added validation to ensure total selected quantity matches the approved quantity for the item
- This prevents users from selecting less or more inventory than was approved
- Provides clear error message if there's a mismatch

**New Validation:**
```php
// Validate that total selected quantity matches approved quantity
if ($item->approved_quantity && abs($totalSelectedQuantity - $item->approved_quantity) > 0.01) {
    throw new Exception(
        'Total selected quantity (' . $totalSelectedQuantity . 
        ') does not match approved quantity (' . $item->approved_quantity . 
        ') for product: ' . $item->product->name
    );
}
```

## Error Messages Now

### Scenario 1: No Inventory Selected
```
Error: An error occurred while initializing transfer: No inventory selected for approved item: Clopidogrel 20%. Please select inventory before initializing transfer.
```

### Scenario 2: Inventory Selected but Quantity Mismatch
```
Error: An error occurred while initializing transfer: Selected quantity (24.00) does not match approved quantity (94.00) for product: Clopidogrel 20%. Please correct the inventory selection before initializing transfer.
```

### Scenario 3: Selected Quantity Doesn't Match Approved (at selectInventory)
```
Error: Total selected quantity (10) does not match approved quantity (15) for product: Clopidogrel 20%
```

### Scenario 4: Actual Insufficient Stock
```
Error: An error occurred while initializing transfer: Insufficient stock for product: Clopidogrel 20%. Available: 5, Requested: 10
```

## Workflow Impact

### Before Approval
1. User creates draft with items
2. User submits draft for approval

### After Approval (Providing Service Approval)
1. Approver approves/rejects items
2. **[NEW STEP]** Approver must select inventory for each approved item
   - Cannot proceed with transfer until inventory is selected
   - Total selected must equal approved quantity

### Transfer Initialization
1. Approver clicks "Initialize Transfer"
2. System now validates:
   - Item is approved ✓
   - Inventory is selected ✓
   - Selected quantity matches approved ✓
   - Inventory stock is sufficient ✓
3. Transfer proceeds with clear error messages if validation fails

## Testing Recommendations

1. **Test Missing Inventory Selection**
   - Approve an item but don't select inventory
   - Try to initialize transfer
   - Expected: Clear error about missing inventory selection

2. **Test Quantity Mismatch**
   - Approve 15 units but select only 10 units
   - Try to initialize transfer
   - Expected: Error about quantity mismatch

3. **Test Insufficient Stock**
   - Approve and select 20 units but only 15 available
   - Try to initialize transfer
   - Expected: Error showing available vs requested

4. **Test Successful Transfer**
   - Approve item with exact quantity
   - Select all inventory needed
   - Initialize transfer should succeed

## Files Modified
- `/home/administrator/www/HIS/app/Http/Controllers/Stock/StockMovementController.php`
  - `initializeTransfer` method (lines ~1368-1410)
  - `selectInventory` method (lines ~1113-1189)

## Related Components
- Stock movement UI should guide users to select inventory after approval
- Frontend error handling should display the specific error messages
- Approval workflow should enforce inventory selection before transfer
