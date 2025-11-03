# Quantity by Box Fix

## Problem Summary

When initializing stock transfers for products with `quantity_by_box = true`, the system was incorrectly comparing:
- **Approved Quantity**: Stored in boxes (e.g., 23 boxes)
- **Selected Inventory Quantity**: Stored in units (e.g., 23 units)

This caused false "Quantity mismatch" errors even when the correct inventory was selected.

### Example Error
```
Cannot initialize transfer. The following items have problems:
• Cetirizine 10%: Quantity mismatch (Approved: 23.00, Selected: 1)
```

The approved quantity was 23 **boxes**, but the system was comparing it directly with selected inventory quantity in **units**, without converting boxes to units.

## Root Cause

Products can be managed in two ways:
1. **By Units** (`quantity_by_box = false`): Quantities are stored directly as units
2. **By Boxes** (`quantity_by_box = true`): Quantities are stored as boxes, but need to be converted to units using `boite_de` (units per box)

The validation logic in both frontend and backend was not converting box quantities to unit quantities before comparison.

## Solution

### Frontend Fix (`StockMovementView.vue`)

Updated `initializeTransfer()` function to convert approved quantity to units when `quantity_by_box = true`:

```javascript
// Get the approved quantity in the correct unit
// If quantity_by_box is true, multiply by boite_de to get actual unit quantity
let approvedQuantityInUnits = item.approved_quantity
if (item.quantity_by_box && item.product?.boite_de) {
  approvedQuantityInUnits = item.approved_quantity * item.product.boite_de
}

// Now compare in the same unit
if (Math.abs(totalSelectedQuantity - approvedQuantityInUnits) > 0.01) {
  // Show mismatch error
}
```

### Backend Fix (`StockMovementController.php`)

Updated `initializeTransfer()` method validation logic:

```php
// Get the approved quantity in the correct unit
// If quantity_by_box is true, multiply by boite_de to get actual unit quantity
$approvedQuantityInUnits = $item->approved_quantity;
if ($item->quantity_by_box && $item->product && $item->product->boite_de) {
    $approvedQuantityInUnits = $item->approved_quantity * $item->product->boite_de;
}

// Validate match using converted quantity
if (abs($totalSelectedQuantity - $approvedQuantityInUnits) > 0.01) {
    // Add to items with issues
}
```

## Files Modified

1. **Frontend**: `/resources/js/Pages/Apps/stock/StockMovementView.vue`
   - Lines ~1493-1530: Updated `initializeTransfer()` function

2. **Backend**: `/app/Http/Controllers/Stock/StockMovementController.php`
   - Lines ~1385-1420: Updated validation logic in `initializeTransfer()` method

## Testing Scenarios

### Scenario 1: Product by Box
- Product: Cetirizine 10%
- `quantity_by_box = true`
- `boite_de = 10` (10 units per box)
- Approved quantity: 23 boxes
- Expected selected inventory: 230 units (23 × 10)
- **Result**: ✅ Transfer should initialize successfully

### Scenario 2: Product by Unit
- Product: Paracetamol
- `quantity_by_box = false`
- Approved quantity: 50 units
- Expected selected inventory: 50 units
- **Result**: ✅ Transfer should initialize successfully (unchanged behavior)

### Scenario 3: Actual Mismatch
- Product: Cetirizine 10%
- `quantity_by_box = true`
- `boite_de = 10`
- Approved quantity: 23 boxes (230 units)
- Selected inventory: 150 units
- **Result**: ❌ Should show error: "Approved: 230.00, Selected: 150"

## Validation Logic

The system uses a tolerance of **0.01** for decimal precision:

```javascript
if (Math.abs(totalSelectedQuantity - approvedQuantityInUnits) > 0.01) {
  // Show mismatch error
}
```

This allows for minor floating-point precision differences while catching real mismatches.

## Related Functions

- `getCalculatedQuantity(item)`: Already handles box-to-unit conversion for display
- `getTotalSelectedQuantity(selectedInventory)`: Sums up selected quantities
- Both frontend and backend validation now use the same conversion logic

## Impact

- ✅ Products with `quantity_by_box = true` can now be transferred correctly
- ✅ Validation errors now show correct quantities in units
- ✅ Prevents false positive mismatch errors
- ✅ Maintains backward compatibility with products by unit

## Deployment Notes

- No database migrations required
- Changes are in application logic only
- Frontend and backend changes should be deployed together
- Clear browser cache recommended after deployment
