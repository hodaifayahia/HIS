# Testing Delivery Confirmation Functionality

## Prerequisites
- Have a pharmacy movement in `in_transfer` status
- Be logged in as a user with proper permissions
- Have browser console open (F12)

## Step-by-Step Testing

### Step 1: Navigate to Stock Movement View
1. Go to Pharmacy > Stock Movements
2. Find a movement with status `in_transfer`
3. Click to open the movement details
4. Scroll to "Delivery Confirmation" section

### Step 2: Test Quantity Validation
1. Click "Validate Quantities" button
2. A modal should appear with all items
3. For each item, enter received quantity
4. Watch browser console for logs
5. Click "Validate Quantities" button in modal
6. Verify response shows:
   - Items with calculated shortages
   - Automatic status assignment
   - Summary counts

**Expected Console Output**:
```
Quantities validated and statuses updated automatically
Total items validated: X
Items with shortages: Y
```

### Step 3: Test Individual Product Confirmation - Good Status
1. In Delivery Confirmation section, find an item with status "pending"
2. Click "Confirm as Good" button (green checkmark)
3. In API call, verify:
   - `status: "good"`
   - `item_id` is correct
4. Wait for response
5. Verify:
   - Item confirmation status changed to "good"
   - Message says "Product confirmed successfully"
   - Inventory was added (check in logs)

**Network Tab Check**:
- Method: POST
- URL: `/api/pharmacy/stock-movements/{id}/confirm-product`
- Status: 200
- Response has `success: true`

### Step 4: Test Individual Product Confirmation - Damaged Status
1. Find another item with pending status
2. Click "Confirm as Damaged" button (red X)
3. In API call, verify:
   - `status: "damaged"`
   - `item_id` is correct
4. Wait for response
5. Verify:
   - Item confirmation status changed to "damaged"
   - Message says "Product confirmed as damaged"
   - Inventory NOT added (check logs)

**Expected Behavior**:
- Status changes to "damaged"
- Executed quantity = 0
- No inventory record created

### Step 5: Test Individual Product Confirmation - Manque Status
1. Find another item with pending status
2. Click "Confirm as Manque" button (warning icon)
3. A dialog should appear
4. Enter received quantity (less than approved)
5. Optionally add notes
6. Click "Confirm Manque"
7. Verify:
   - Item confirmation status changed to "manque"
   - Received quantity recorded
   - Partial inventory added

**Example**:
- Approved: 100
- Received: 85
- Expected shortage in response: 15

### Step 6: Test Validate All Quantities at Once
1. In "Quantity Validation" section, click "Open Validation Modal"
2. Modal appears with all items in table format
3. For each row:
   - Received Quantity column has input field
   - Enter received quantity for each item
4. Watch column update to show:
   - Status indicator (green/orange)
   - Shortage amount
5. Click "Validate Quantities"
6. Verify response shows auto-determination of status

### Step 7: Test Finalize Confirmation
1. After all items confirmed (or at least some), scroll to bottom
2. Click "Finalize Confirmation" button
3. System should:
   - Aggregate all confirmation statuses
   - Set final movement status
   - Show summary

**Verify in Response**:
```json
{
  "success": true,
  "movement": {
    "status": "partially_fulfilled",  // or fulfilled/damaged/unfulfilled
    "delivery_status": "mixed"         // or good/damaged/manque
  },
  "summary": {
    "good_items": 3,
    "damaged_items": 1,
    "manque_items": 1,
    "final_status": "partially_fulfilled"
  }
}
```

## Browser Console Checks

### During Confirm Product (Good)
```
✓ Product confirmed successfully. Item has been added to your pharmacy inventory.
✓ Item updated in confirmation table
```

### During Confirm Product (Damaged)
```
✓ Product confirmed as damaged. Item has been marked as damaged and not added to inventory.
✓ Item status shows "damaged" with red indicator
```

### During Confirm Product (Manque)
```
✓ Product confirmed as missing. Received quantity has been processed.
✓ Item shows received quantity
✓ Shortage amount calculated and displayed
```

### During Validate Quantities
```
✓ Quantities validated and statuses updated automatically
✓ Validation results show each item's calculated status
✓ Summary shows good_items count and manque_items count
```

### During Finalize Confirmation
```
✓ Confirmation finalized successfully. Movement status updated.
✓ Movement status changed from "in_transfer" to final status
✓ Summary shows final counts and status
```

## Network Tab Monitoring

### Successful Confirm Product Request
```
Request:
  Method: POST
  URL: /api/pharmacy/stock-movements/123/confirm-product
  Body: {
    item_id: 1,
    status: "good",
    notes: "...",
    received_quantity: null
  }

Response (Status 200):
  {
    "success": true,
    "message": "...",
    "item": { ... }
  }
```

### Successful Validate Quantities Request
```
Request:
  Method: POST
  URL: /api/pharmacy/stock-movements/123/validate-quantities
  Body: {
    items: [
      { item_id: 1, received_quantity: 50, ... }
    ]
  }

Response (Status 200):
  {
    "success": true,
    "validation_results": [ ... ],
    "summary": { ... }
  }
```

### Successful Finalize Confirmation Request
```
Request:
  Method: POST
  URL: /api/pharmacy/stock-movements/123/finalize-confirmation
  Body: {}

Response (Status 200):
  {
    "success": true,
    "movement": { status: "fulfilled" },
    "summary": { ... }
  }
```

## Error Scenarios to Test

### Test 1: Movement Not in Transfer
**Setup**: Find a movement NOT in `in_transfer` status
**Action**: Try to click "Confirm as Good"
**Expected Error**:
```json
{
  "success": false,
  "message": "Movement must be in transfer status to confirm delivery"
}
```

### Test 2: Invalid Item ID
**Setup**: Manually modify request to use invalid item_id
**Action**: Send confirm-product request with wrong item_id
**Expected Error**:
```json
{
  "success": false,
  "message": "Item not found in this movement"
}
```

### Test 3: Invalid Status
**Setup**: Manually modify request with invalid status
**Action**: Send confirm-product with `status: "invalid"`
**Expected Error**:
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "status": ["The status must be one of: good, damaged, manque"]
  }
}
```

### Test 4: Missing Required Fields
**Setup**: Send request missing required fields
**Action**: Send confirm-product without `item_id` or `status`
**Expected Error**:
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": { ... }
}
```

## Verification Checklist

After completing all tests, verify:

- [ ] Validate Quantities shows all items with calculated status
- [ ] Confirm Good adds inventory to pharmacy
- [ ] Confirm Damaged does NOT add inventory
- [ ] Confirm Manque records shortage and partial quantity
- [ ] All API responses show `success: true`
- [ ] Browser console shows appropriate success messages
- [ ] Network tab shows HTTP 200 status for all requests
- [ ] Finalize Confirmation properly aggregates statuses
- [ ] Movement status changes to final state
- [ ] Delivery confirmation section updates after each action
- [ ] Error scenarios return proper error messages

## Performance Expectations

| Operation | Expected Time | Max Time |
|-----------|---------------|----------|
| Validate Quantities | < 500ms | 2s |
| Confirm Product (Good) | < 500ms | 2s |
| Confirm Product (Damaged) | < 500ms | 2s |
| Confirm Product (Manque) | < 500ms | 2s |
| Finalize Confirmation | < 1s | 3s |

## Log Verification

Check Laravel logs at `/storage/logs/laravel.log`:

```
[2025-11-01 10:30:00] local.INFO: Pharmacy movement confirmation finalized
[2025-11-01 10:30:00] local.INFO: Pharmacy stock added to requesting service for individual product
[2025-11-01 10:30:00] local.INFO: Missing pharmacy stock reported for individual product
```

## Common Issues and Solutions

### Issue: "Confirm as Good" button disabled
**Solution**: Ensure movement is in `in_transfer` status

### Issue: API returns 404
**Solution**: Verify movement ID in URL matches open movement

### Issue: Inventory not created
**Solution**: Check requesting service has pharmacy stockages configured

### Issue: Finalize button shows error
**Solution**: Ensure all items have confirmation status set

### Issue: Shortage not calculated
**Solution**: Verify received_quantity < sender_quantity in validation

## Success Indicators

✓ All items can be confirmed with appropriate status  
✓ Inventory created for good items  
✓ Shortages properly recorded  
✓ Finalization aggregates all statuses correctly  
✓ Movement status updates to final state  
✓ No errors in browser console  
✓ All network requests return 200 status  
✓ Database transactions complete successfully  
