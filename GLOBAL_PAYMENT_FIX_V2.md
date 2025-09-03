# Global Payment Fix V2

## Issues Fixed
1. **Backend validation error**: `"fiche_navette_item_id is required when patient_id is provided"`
2. **Backend validation error**: `"Either fiche_navette_item_id (in items or top-level), item_dependency_id, or patient_id must be provided"`

## Root Cause
The `payGlobal` function was sending conflicting data in the payload:
- Both `fiche_navette_item_id` AND `item_dependency_id` at the top level for all requests
- Backend validation expects EITHER one OR the other, not both
- For dependency payments: should only send `item_dependency_id`
- For regular item payments: should only send `fiche_navette_item_id`

## Solution Applied

### Before (Problematic Code):
```javascript
const payload = {
  fiche_navette_id: ficheId.value,
  fiche_navette_item_id: a.fiche_navette_item_id,      // ❌ Always sent
  item_dependency_id: a.item_dependency_id,            // ❌ Always sent (even when undefined)
  caisse_session_id: caisseSessionId.value,
  cashier_id: cashierId,
  amount: a.amount,
  transaction_type: 'payment',
  payment_method: mapPaymentMethod(a.method || globalPayment.method || 'cash'),
  notes: a.item_dependency_id
    ? `Global payment allocation for dependency ${a.item_dependency_id}`
    : `Global payment allocation for item ${a.fiche_navette_item_id}`,
  // Duplicate fields (confusing)
  item_dependency_id: a.item_dependency_id ?? undefined,
  fiche_navette_item_id: a.fiche_navette_item_id
}
```

### After (Fixed Code):
```javascript
const payload = {
  fiche_navette_id: ficheId.value,
  caisse_session_id: caisseSessionId.value,
  cashier_id: cashierId,
  amount: a.amount,
  transaction_type: 'payment',
  payment_method: mapPaymentMethod(a.method || globalPayment.method || 'cash'),
  notes: a.item_dependency_id
    ? `Global payment allocation for dependency ${a.item_dependency_id}`
    : `Global payment allocation for item ${a.fiche_navette_item_id}`
}

// ✅ Add either fiche_navette_item_id or item_dependency_id, but not both
if (a.item_dependency_id) {
  // For dependency payments, only send item_dependency_id
  payload.item_dependency_id = a.item_dependency_id
} else {
  // For regular item payments, only send fiche_navette_item_id
  payload.fiche_navette_item_id = a.fiche_navette_item_id
}
```

## Backend Validation Logic
The backend `StoreFinancialTransactionRequest` and `FinancialTransactionService` expect:

1. **For dependency payments**: Only `item_dependency_id` 
   - Backend resolves the parent `fiche_navette_item_id` automatically
   - Patient is resolved from the fiche navette relationship

2. **For regular item payments**: Only `fiche_navette_item_id`
   - Backend resolves patient from the fiche navette relationship
   - No dependency processing needed

3. **Invalid combinations**:
   - ❌ Both `fiche_navette_item_id` AND `item_dependency_id` 
   - ❌ `patient_id` without `fiche_navette_item_id` at top level
   - ❌ Neither `fiche_navette_item_id` nor `item_dependency_id`

## Testing
1. ✅ Build completes successfully (`npm run build`)
2. ✅ Development server starts (`npm run dev`) - Running on port 5173
3. 🔄 Ready for runtime testing of global payment functionality

## Expected Behavior
- Global payments should now process without validation errors
- Dependencies will be paid using `item_dependency_id` only
- Regular items will be paid using `fiche_navette_item_id` only
- Backend will properly resolve patient and item relationships
- Each allocation request will be processed independently

## Files Modified
- `resources/js/Pages/Apps/caisse/CaissePatientPayment.vue` - Fixed `payGlobal()` function payload construction

## Next Steps
Test the global payment functionality in the browser at `http://localhost:5173` to ensure:
1. Global payments process without errors
2. Both regular items and dependencies are paid correctly
3. Amounts are properly allocated across multiple items
