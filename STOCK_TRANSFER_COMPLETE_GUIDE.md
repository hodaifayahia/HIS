# Stock Movement Transfer - Complete Implementation Guide

## Fix Status: ✅ COMPLETE

The error handling for stock transfer initialization is now working correctly with clear, actionable error messages.

## Error Message Examples (Working Correctly)

### Error 1: No Inventory Selected
```json
{
  "success": false,
  "message": "An error occurred while initializing transfer: No inventory selected for approved item: Clopidogrel 20%. Please select inventory before initializing transfer."
}
```
**User Action:** Go to inventory selection step and select inventory batches for this product.

---

### Error 2: Quantity Mismatch (Primary Error - Currently Occurring)
```json
{
  "success": false,
  "message": "An error occurred while initializing transfer: Selected quantity (30) does not match approved quantity (67.00) for product: Pioglitazone 10%. Please correct the inventory selection before initializing transfer."
}
```
**User Action:** Adjust inventory selections so total equals 67.00 units.

---

### Error 3: Insufficient Stock in Selected Inventory
```json
{
  "success": false,
  "message": "An error occurred while initializing transfer: Insufficient stock for product: Clopidogrel 20%. Available: 5, Requested: 10"
}
```
**User Action:** Either select different inventory batches with more stock, or reduce the approved quantity.

---

## User Workflow

### Step 1: Create Draft
```
User creates stock request with items and quantities
```

### Step 2: Submit for Approval
```
Request submitted to providing service for approval
```

### Step 3: Approval
```
Providing service reviews and approves/rejects items with quantities
Status: Items marked as "approved" with approved_quantity set
```

### Step 4: Select Inventory (NEW MANDATORY STEP)
```
For each approved item:
- View available inventory batches
- Select batch(es)
- Enter quantity from each batch
- Total must equal approved_quantity
```

**API Endpoint:**
```
POST /api/stock-movements/{movementId}/select-inventory
{
  "item_id": 123,
  "selected_inventory": [
    {
      "inventory_id": 456,
      "quantity": 30
    },
    {
      "inventory_id": 789,
      "quantity": 37
    }
  ]
}
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Inventory selection saved successfully"
}
```

### Step 5: Initialize Transfer
```
All validations pass:
✓ Item is approved
✓ Inventory is selected
✓ Selected quantity = approved quantity
✓ Inventory stock is sufficient
→ Transfer initialized successfully
```

**API Endpoint:**
```
POST /api/stock-movements/{movementId}/init-transfer
```

---

## Frontend Implementation Checklist

### Approval View Changes
- [ ] After approving items, show message: "Next: Select inventory for approved items"
- [ ] Add button to proceed to inventory selection step
- [ ] Display approved quantity for each item

### Inventory Selection View Updates
- [ ] Display approved quantity prominently
- [ ] Show list of available inventory batches for the product
- [ ] Allow user to select multiple batches
- [ ] Show running total of selected quantity
- [ ] Validate: `totalSelected === approvedQuantity` before allowing save
- [ ] Show error if quantities don't match: 
  ```
  "Total selected (30) must equal approved quantity (67.00)"
  ```

### Transfer Initialization Error Handling
```javascript
// Parse error message to provide context-aware UI
try {
  await axios.post(`/api/stock-movements/${movementId}/init-transfer`);
} catch (error) {
  const message = error.response?.data?.message || '';
  
  if (message.includes('No inventory selected')) {
    // Take user to inventory selection screen
    // Show which products need inventory selection
    showToast('error', 'Please select inventory for all approved items');
    router.push('inventory-selection');
  } 
  else if (message.includes('does not match approved quantity')) {
    // Show quantity mismatch details
    // Extract: Selected quantity (X) vs Approved quantity (Y)
    showToast('error', message);
    router.push('inventory-selection');
  }
  else if (message.includes('Insufficient stock')) {
    // Show stock issue details
    showToast('error', message);
    router.push('inventory-selection');
  }
  else {
    showToast('error', 'Failed to initialize transfer');
  }
}
```

---

## API Endpoints Reference

### Get Product Inventory (for selection UI)
```
GET /api/stock-movements/{movementId}/product-inventory/{productId}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 456,
      "barcode": "BATCH001",
      "batch_number": "B123456",
      "quantity": 50,
      "total_units": 50,
      "expiry_date": "2026-01-15",
      "location": "Shelf A",
      "stockage": {
        "id": 1,
        "name": "Main Stockage"
      }
    }
  ]
}
```

### Select Inventory
```
POST /api/stock-movements/{movementId}/select-inventory
```

**Request:**
```json
{
  "item_id": 123,
  "selected_inventory": [
    {
      "inventory_id": 456,
      "quantity": 30
    },
    {
      "inventory_id": 789,
      "quantity": 37
    }
  ]
}
```

**Validation Errors:**
```json
{
  "success": false,
  "message": "Total selected quantity (30) does not match approved quantity (67.00) for product: Pioglitazone 10%"
}
```

### Initialize Transfer
```
POST /api/stock-movements/{movementId}/init-transfer
```

**Success Response:**
```json
{
  "success": true,
  "message": "Transfer initialized successfully. Stock quantities have been updated.",
  "movement": { ... }
}
```

**Error Responses:** See Error Message Examples section above

---

## Database Field Reference

### stock_movement_items table
- `approved_quantity` - Qty approved by provider
- `provided_quantity` - Qty being sent (sum of selected inventories)
- `sender_quantity` - Qty sent after init-transfer
- `executed_quantity` - Qty received

### stock_movement_inventory_selections table
- `stock_movement_item_id` - Links to item
- `inventory_id` - Which inventory batch
- `selected_quantity` - How much from this batch

---

## Current Status: Error 2 - Quantity Mismatch

**The system is reporting:**
```
Selected quantity (30) does not match approved quantity (67.00) for product: Pioglitazone 10%
```

**What this means:**
- The approver approved 67.00 units for Pioglitazone 10%
- Only 30 units are currently selected from inventory
- Need to select 37 more units to complete the requirement

**Solution:**
1. User must find additional inventory batches for "Pioglitazone 10%"
2. Add to selection until total = 67.00 units
3. Then proceed with transfer initialization

---

## Testing Scenarios

### ✅ Scenario 1: Complete Success
1. Approve 50 units of Product A
2. Select exactly 50 units from inventory
3. Click "Initialize Transfer"
4. ✅ Transfer initializes successfully

### ✅ Scenario 2: Detect Mismatch
1. Approve 50 units of Product A
2. Select only 30 units from inventory
3. Try to initialize transfer
4. ✅ Get error: "Selected quantity (30) does not match approved quantity (50.00)"
5. User selects 20 more units (total 50)
6. ✅ Transfer initializes successfully

### ✅ Scenario 3: No Inventory Selected
1. Approve 50 units of Product A
2. Skip inventory selection (don't select anything)
3. Try to initialize transfer
4. ✅ Get error: "No inventory selected for approved item: Product A"

### ✅ Scenario 4: Insufficient Stock
1. Approve 100 units of Product A
2. Select from inventory: 50 units available
3. Try to initialize transfer
4. ✅ Get error: "Insufficient stock for product: Product A. Available: 50, Requested: 50"
   (This happens per inventory batch when deducting)

---

## Migration Notes

If updating existing UIs:
1. Remove any UI that skipped inventory selection
2. Add mandatory inventory selection step after approval
3. Update approval workflows to include inventory selection prompt
4. Update error handling to distinguish between the three error types
5. Test with real data (especially Pioglitazone 10% case)

---

## Support Information

**All error messages are now:**
- ✅ Clear about what's wrong
- ✅ Actionable (tell user what to do)
- ✅ Specific (include product names and quantities)
- ✅ Logged server-side for debugging

**Check application logs for:**
- Stock deductions logged with inventory_id, product_id, quantities
- Transfer initialization attempts
- All validation failures

