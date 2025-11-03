# Fix for "Undefined variable $userService" Error

## Problem

**Error Message:**
```
An error occurred while confirming product: Undefined variable $userService
Status: 500 Internal Server Error
Endpoint: POST /api/stock-movements/{movementId}/confirm-product
```

## Root Cause

In the `confirmProduct` method of `StockMovementController.php`, the variable `$userService` was being used without being defined:

- **Line 1768**: `$newInventory->service_id = $userService->id;`
- **Line 1785**: `'service_id' => $userService->id,`
- **Line 1807**: `'service_id' => $userService->id,`

The variable was never declared or initialized before use.

## Solution

Replace all references to `$userService->id` with `$movement->requesting_service_id`, which is the correct way to access the requesting service ID from the movement object.

### Changes Made

**File:** `/app/Http/Controllers/Stock/StockMovementController.php`

**Lines Changed:**
1. Line 1768: `$userService->id` → `$movement->requesting_service_id`
2. Line 1785: `$userService->id` → `$movement->requesting_service_id`
3. Line 1807: `$userService->id` → `$movement->requesting_service_id`

### Complete Fixed Code Sections

#### Good Status (Lines 1761-1788)
```php
if ($confirmationStatus === 'good') {
    // For 'good' status: set executed_quantity equal to approved quantity
    $item->executed_quantity = $item->approved_quantity;

    // Add stock to requester's inventory
    if ($item->selectedInventory && $item->selectedInventory->count() > 0) {
        foreach ($item->selectedInventory as $selection) {
            // Create new inventory record for the requesting service
            $newInventory = new \App\Models\Inventory;
            $newInventory->product_id = $selection->inventory->product_id;
            $newInventory->service_id = $movement->requesting_service_id;  // FIXED
            $newInventory->quantity = $selection->quantity;
            $newInventory->batch_number = $selection->inventory->batch_number;
            $newInventory->serial_number = $selection->inventory->serial_number;
            $newInventory->expiry_date = $selection->inventory->expiry_date;
            $newInventory->barcode = $selection->inventory->barcode;
            $newInventory->save();

            \Log::info('Stock added to requesting service for individual product', [
                'new_inventory_id' => $newInventory->id,
                'product_id' => $newInventory->product_id,
                'service_id' => $movement->requesting_service_id,  // FIXED
                'quantity' => $selection->quantity,
                'item_id' => $item->id,
            ]);
        }
    }
}
```

#### Damaged Status (Lines 1790-1804)
```php
elseif ($confirmationStatus === 'damaged') {
    // Handle damaged products - don't add to inventory, mark as damaged
    // Log damaged items for tracking
    if ($item->selectedInventory && $item->selectedInventory->count() > 0) {
        foreach ($item->selectedInventory as $selection) {
            \Log::info('Damaged stock reported for individual product', [
                'movement_id' => $movement->id,
                'item_id' => $item->id,
                'product_id' => $selection->inventory->product_id,
                'quantity' => $selection->quantity,
                'batch_number' => $selection->inventory->batch_number,
                'service_id' => $movement->requesting_service_id,  // FIXED
            ]);
        }
    }

    // Set executed quantity to 0 for damaged items
    $item->executed_quantity = 0;
}
```

#### Manque Status (Lines 1806-1821)
```php
elseif ($confirmationStatus === 'manque') {
    // For 'manque' status: update received_quantity and set executed_quantity to received quantity
    if ($receivedQuantity !== null) {
        $item->received_quantity = $receivedQuantity;
        $item->executed_quantity = $receivedQuantity;
    } else {
        // If no received quantity provided, set executed to 0
        $item->executed_quantity = 0;
    }

    // Handle missing quantities - don't add to inventory
    \Log::info('Missing stock reported for individual product', [
        'movement_id' => $movement->id,
        'item_id' => $item->id,
        'product_id' => $item->product_id,
        'service_id' => $movement->requesting_service_id,  // FIXED
        'received_quantity' => $receivedQuantity,
    ]);
}
```

## Impact

- ✅ Fixes the 500 error when confirming individual products
- ✅ Correctly adds stock to the requesting service's inventory
- ✅ Properly logs confirmation status (good/damaged/manque)
- ✅ No database migrations needed
- ✅ Backward compatible

## Testing

After deployment, test by:
1. Creating a stock movement in transfer status
2. Call POST `/api/stock-movements/{movementId}/confirm-product` with:
   - `item_id`: valid item ID
   - `status`: 'good', 'damaged', or 'manque'
   - `notes`: optional
   - `received_quantity`: optional (for manque status)

**Expected Result:** 200 OK with success message instead of 500 error

## Related Endpoints

- `POST /api/stock-movements/{movementId}/confirm-product` - Confirm individual product
- `POST /api/stock-movements/{movementId}/confirm-delivery` - Confirm entire delivery
- `GET /api/stock-movements/{movementId}` - View movement details
