# Stock Transfer Initialization Error Fix

## Problem Summary

When attempting to initialize a transfer with stock movement ID 14, the system returned a 500 error:

```
Selected quantity (30) does not match approved quantity (67.00) for product: Pioglitazone 10%. 
Please correct the inventory selection before initializing transfer.
```

**Root Cause**: The backend validation was rejecting the transfer because:
1. Product "Pioglitazone 10%" was approved for 67.00 units
2. Only 30 units were selected in the inventory selection
3. The backend expected an exact match before initializing the transfer

## Solution Implemented

### 1. **Backend Enhancement** (`StockMovementController.php`)

**Improved Validation with Better Error Messages:**
- Changed from throwing exceptions (which result in 500 errors) to returning detailed 422 responses
- Validates all approved items BEFORE attempting the transfer
- Provides itemized feedback showing which items have issues and why
- Returns structured data about each problematic item

**Key Changes:**
```php
// First validate all approved items have correct inventory selections
$itemsWithIssues = [];
foreach ($movement->items as $item) {
    if ($item->approved_quantity === null || $item->approved_quantity <= 0) {
        continue;
    }
    
    // Check selection exists
    if (!$item->selectedInventory || $item->selectedInventory->count() === 0) {
        $itemsWithIssues[] = [
            'product' => $item->product->name,
            'issue' => 'No inventory selected',
            'approved_qty' => $item->approved_quantity,
            'selected_qty' => 0
        ];
        continue;
    }
    
    // Validate quantities match
    $totalSelectedQuantity = // ... calculate
    if (abs($totalSelectedQuantity - $item->approved_quantity) > 0.01) {
        $itemsWithIssues[] = [...];
    }
}

// Return early with detailed errors if issues exist
if (!empty($itemsWithIssues)) {
    return response()->json([
        'success' => false,
        'message' => 'Cannot initialize transfer due to inventory selection mismatches',
        'details' => $errorDetails,
        'items_with_issues' => $itemsWithIssues,
    ], 422);
}
```

### 2. **Frontend Enhancement** (`StockMovementView.vue`)

**Client-Side Validation Before Submission:**
- Added comprehensive pre-submission validation
- Shows users exactly which items have problems before sending to backend
- Prevents unnecessary API calls with invalid data
- Provides clear, actionable error messages

**Key Changes:**
```javascript
const initializeTransfer = async () => {
  // Validate all approved items have matching selections
  const approvedItems = movement.value.items.filter(item => 
    item.approved_quantity && item.approved_quantity > 0
  )
  
  const itemsWithIssues = []
  const validItemsForTransfer = []
  
  approvedItems.forEach(item => {
    // Check if inventory selected
    if (!item.selected_inventory || item.selected_inventory.length === 0) {
      itemsWithIssues.push({
        product: item.product?.name,
        issue: 'No inventory selected',
        approved_qty: item.approved_quantity,
        selected_qty: 0
      })
      return
    }
    
    // Calculate total selected
    const totalSelectedQuantity = item.selected_inventory.reduce((total, selection) => {
      return total + parseFloat(selection.quantity || 0)
    }, 0)
    
    // Check quantities match
    if (Math.abs(totalSelectedQuantity - item.approved_quantity) > 0.01) {
      itemsWithIssues.push({
        product: item.product?.name,
        issue: 'Quantity mismatch',
        approved_qty: item.approved_quantity,
        selected_qty: totalSelectedQuantity
      })
    } else {
      validItemsForTransfer.push(item)
    }
  })
  
  // Show issues with toast notification
  if (itemsWithIssues.length > 0) {
    const issueDetails = itemsWithIssues.map(issue => 
      `• ${issue.product}: ${issue.issue} (Approved: ${issue.approved_qty}, Selected: ${issue.selected_qty})`
    ).join('\n')
    
    toast.add({
      severity: 'error',
      summary: 'Inventory Selection Issues',
      detail: `Cannot initialize transfer. The following items have problems:\n${issueDetails}`,
      life: 5000,
      sticky: true
    })
    return
  }
  
  // Proceed with transfer only when valid
  const response = await axios.post(`/api/stock-movements/${props.movementId}/init-transfer`, {
    item_ids: validItemsForTransfer.map(item => item.id)
  })
}
```

## User Impact

### Before Fix:
- ❌ User attempts to initialize transfer
- ❌ Generic 500 error with cryptic message
- ❌ No clear indication of what's wrong or what to fix
- ❌ Must contact support or debug manually

### After Fix:
- ✅ Frontend validates before sending request
- ✅ Clear message shows which products need inventory selection
- ✅ Shows approved quantity vs selected quantity for each problem
- ✅ User can see exactly which items to fix
- ✅ Backend also validates and provides detailed response codes (422 instead of 500)
- ✅ Better error messages help users self-serve

## Example Error Response (After Fix)

**Frontend Toast Notification:**
```
Inventory Selection Issues

Cannot initialize transfer. The following items have problems:
• Pioglitazone 10%: Quantity mismatch (Approved: 67.00, Selected: 30.00)
```

**Backend Response (if bypassed):**
```json
{
  "success": false,
  "message": "Cannot initialize transfer due to inventory selection mismatches",
  "details": "The following items have issues:\n• Pioglitazone 10%: Mismatch between selected and approved quantities (Approved: 67.00, Selected: 30.00)",
  "items_with_issues": [
    {
      "product": "Pioglitazone 10%",
      "issue": "Mismatch between selected and approved quantities",
      "approved_qty": 67.00,
      "selected_qty": 30.00
    }
  ]
}
```

## What the User Should Do

When they see this error, the user needs to:

1. **Go back to the transfer initialization screen**
2. **For each product showing a mismatch:**
   - Click "Select Products" or "Edit Selection" button
   - Adjust inventory selections to match the approved quantity
   - Ensure total selected = approved quantity (67.00 in this case)
3. **Click "Init Transfer" again**

## Files Modified

1. `/home/administrator/www/HIS/app/Http/Controllers/Stock/StockMovementController.php`
   - Lines 1357-1435: Enhanced `initializeTransfer()` method validation

2. `/home/administrator/www/HIS/resources/js/Pages/Apps/stock/StockMovementView.vue`
   - Added `useToast` import
   - Initialized `toast` in setup function
   - Completely rewrote `initializeTransfer()` method with comprehensive validation

## Testing Recommendations

1. **Test with mismatched quantities:**
   - Create a movement with approved quantity = 100
   - Select inventory totaling 50
   - Attempt transfer initialization
   - Verify detailed error message shows

2. **Test with missing inventory selection:**
   - Create a movement with 3 approved items
   - Select inventory for only 2 items
   - Attempt transfer initialization
   - Verify error shows which item is missing selection

3. **Test with correct selections:**
   - Create a movement
   - Select inventory matching approved quantities exactly
   - Verify transfer initializes successfully

4. **Test quantity precision:**
   - Use decimal quantities (67.50, etc.)
   - Verify tolerance of 0.01 works correctly

## Tolerance for Decimal Precision

The system allows a tolerance of 0.01 for floating-point comparison:
```javascript
Math.abs(totalSelectedQuantity - item.approved_quantity) > 0.01
```

This prevents issues with floating-point arithmetic precision (e.g., 67.0000001 vs 67.00).

## Future Enhancements

Consider implementing:
1. **Bulk adjustment** - Quick way to auto-select full approved quantities
2. **Suggestion system** - Auto-suggest available inventory based on quantities needed
3. **Inventory warnings** - Show available stock vs needed during selection
4. **Draft validation** - Warn users during approval stage if quantities might be problematic
