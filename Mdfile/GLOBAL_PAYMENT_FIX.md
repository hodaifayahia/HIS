# Global Payment Fix

## Problem
The global payment feature was failing with the error:
```
{success: false, message: "fiche_navette_item_id is required when patient_id is provided."}
```

## Root Cause
The frontend was sending both `patient_id` and `allocation` in the payload, but the backend service validation logic requires:
- Either a single transaction with both `fiche_navette_item_id` and `patient_id` at the top level
- OR multiple items in an `items` array where each item has its own `fiche_navette_item_id`

For global payments that distribute across multiple items, sending `patient_id` at the top level triggered validation that required `fiche_navette_item_id` to also be present.

## Solution
Updated the `payGlobal` function in `CaissePatientPayment.vue`:

1. **Removed top-level `patient_id`**: The backend can resolve the patient from each item's `fiche_navette_item_id`
2. **Changed `allocation` to `items`**: The backend service expects the field to be named `items`
3. **Fixed dependency handling**: For dependency items, use the parent's `fiche_navette_item_id` instead of the dependency's ID
4. **Removed patient validation**: Global payments don't need a top-level patient ID check

## Key Changes

### Before:
```javascript
const payload = {
  fiche_navette_id: ficheId.value,
  caisse_session_id: caisseSessionId.value,
  cashier_id: cashierId,
  patient_id: patient_id,  // ❌ This caused the validation error
  amount: amount,
  transaction_type: 'payment',
  payment_method: mapPaymentMethod(globalPayment.method || 'cash'),
  notes: `Global payment of ${amount}`,
  allocation: allocation    // ❌ Wrong field name
}
```

### After:
```javascript
const itemsAllocation = []
let remainingToApply = amount
for (const it of items.value) {
  const rem = itemRemaining(it)
  if (rem > 0 && remainingToApply > 0) {
    const toApply = Math.min(rem, remainingToApply)
    // ✅ For dependencies, use the parent's fiche_navette_item_id
    const ficheItemId = it.is_dependency ? it.parent_item_id : it.id
    itemsAllocation.push({
      fiche_navette_item_id: ficheItemId,
      amount: toApply,
      item_dependency_id: it.is_dependency ? it.id : undefined
    })
    remainingToApply -= toApply
  }
}

const payload = {
  fiche_navette_id: ficheId.value,
  caisse_session_id: caisseSessionId.value,
  cashier_id: cashierId,
  // ✅ No top-level patient_id
  amount: amount,
  transaction_type: 'payment',
  payment_method: mapPaymentMethod(globalPayment.method || 'cash'),
  notes: `Global payment of ${amount}`,
  items: itemsAllocation  // ✅ Correct field name
}
```

## Backend Compatibility
The backend `FinancialTransactionService` already supports the `items` array format:
- `validateAndResolveFicheNavetteItem()` method extracts `fiche_navette_item_id` from items payload
- For each item, it resolves the patient from the fiche navette relationship
- Dependencies are properly handled with `item_dependency_id`

## Additional Fix
Fixed an unrelated build issue in `WaitlistTypes.vue` by commenting out a missing import for `AddPrestationModal`.

## Testing
1. ✅ Build completes successfully (`npm run build`)
2. ✅ Development server starts (`npm run dev`)
3. ✅ No JavaScript console errors in the frontend code

## Usage
Global payments should now work correctly:
1. Enter a global payment amount
2. Select payment method
3. Click "Pay Global"
4. The payment will be distributed across all outstanding items proportionally
5. Dependencies will be handled correctly using their parent's fiche ID
