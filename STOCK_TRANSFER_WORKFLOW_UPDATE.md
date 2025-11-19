# Stock Movement Transfer - User Workflow

## Quick Fix Summary

**Error:** "An error occurred while initializing transfer: Insufficient stock for product: Clopidogrel 20%"

**Solution:** This error now has more specific messages. You need to:

1. ✓ **Approve the items** - Done in approval step
2. ✓ **Select inventory for each approved item** - NEW REQUIREMENT
3. ✓ **Ensure selected quantity matches approved quantity** - NEW VALIDATION
4. ✓ **Initialize transfer** - Should now work if all above are done

## What Changed

### Before
- Items could be approved without selecting inventory
- Init-transfer would fail with generic "insufficient stock" error
- Users didn't know what went wrong

### After
- System now requires inventory selection for each approved item
- Clear error messages tell you exactly what's wrong:
  - "No inventory selected for approved item: Clopidogrel 20%. Please select inventory before initializing transfer."
  - "Total selected quantity (10) does not match approved quantity (15) for product: Clopidogrel 20%"
  - "Insufficient stock for product: Clopidogrel 20%. Available: 5, Requested: 10"

## For Frontend/UI Developers

### Update Approval Interface
After approving items, users must:
1. **Go to "Select Inventory" step** (or similar)
2. **For each approved item:**
   - Choose inventory batches/lots
   - Select quantity from each batch
   - Ensure total selected = approved quantity
3. **Then click "Initialize Transfer"**

### Error Handling
Catch these specific error scenarios:

```javascript
// Scenario 1: Missing inventory selection
if (error.message.includes("No inventory selected")) {
  // Show: "Please select inventory for all approved items"
  // Action: Take user back to inventory selection
}

// Scenario 2: Quantity mismatch
if (error.message.includes("does not match approved quantity")) {
  // Show: "Selected quantity must match approved quantity"
  // Action: Show current selected vs approved quantities
}

// Scenario 3: Insufficient stock
if (error.message.includes("Insufficient stock")) {
  // Show: Display the available quantity and requested
  // Action: Allow user to adjust approved quantity
}
```

## Database/Backend Notes

### Model Relationships
- `StockMovement` → `items` → `StockMovementItem`
- `StockMovementItem` → `selectedInventory` (relationship to `StockMovementInventorySelection`)
- `StockMovementInventorySelection` → `inventory` (actual inventory records)

### Key Fields
- `StockMovementItem.approved_quantity` - What was approved
- `StockMovementItem.provided_quantity` - What's being sent (sum of selected inventories)
- `StockMovementInventorySelection.selected_quantity` - Individual inventory selection

### Validation Logic
```php
// For each approved item:
- approved_quantity MUST be > 0
- selectedInventory MUST NOT be empty
- Sum of selected_quantity MUST equal approved_quantity
- Each inventory MUST have sufficient stock
```

## Testing Checklist

- [ ] Approve items successfully
- [ ] Try init-transfer without selecting inventory → Get clear error
- [ ] Select inventory but wrong quantity → Get quantity mismatch error
- [ ] Select inventory with correct quantity but insufficient stock → Get stock error
- [ ] Select inventory correctly → Transfer initializes successfully

## Files Modified
- `app/Http/Controllers/Stock/StockMovementController.php`
  - `initializeTransfer()` - Added inventory selection validation
  - `selectInventory()` - Added quantity matching validation

## Related API Endpoints

### POST `/api/stock-movements/{movementId}/init-transfer`
Initializes transfer after approval.
**Now requires:** All approved items must have inventory selected.

### POST `/api/stock-movements/{movementId}/select-inventory`
Selects inventory for an item.
**New validation:** Total selected must match approved quantity.

### GET `/api/stock-movements/{movementId}/product-inventory/{productId}`
Gets available inventory for a product in the movement context.
Use this to populate inventory selection dropdowns in UI.
