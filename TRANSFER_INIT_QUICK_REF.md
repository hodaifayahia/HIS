# Quick Reference: Transfer Initialization Fix

## Problem
Getting error when initializing stock transfer:
```
Selected quantity (30) does not match approved quantity (67.00)
```

## What Was Wrong
- Approved 67 units of "Pioglitazone 10%"
- Only selected/reserved 30 units
- System requires exact match before transfer

## What's Fixed
1. **Better Error Messages** - Now shows exactly what's wrong
2. **Frontend Validation** - Checks before sending to server
3. **Clear Feedback** - Toast notifications guide users

## How to Fix It (For Users)

When you see the error:

1. **Find the product** in the transfer form (Pioglitazone 10%)
2. **Click "Select Products"** or **"Edit Selection"** button
3. **Adjust quantities** so selected total = approved total (67.00)
4. **Click "Init Transfer"** again

## Error Message Format

### Old Error (Bad)
```
500 Internal Server Error
```

### New Error (Good)
```
Inventory Selection Issues
Cannot initialize transfer. The following items have problems:
• Pioglitazone 10%: Quantity mismatch (Approved: 67.00, Selected: 30.00)
```

## Key Changes

### Backend (`StockMovementController.php`)
- Pre-validates all items before attempting transfer
- Returns detailed error with item-by-item issues
- Proper HTTP status code (422 instead of 500)

### Frontend (`StockMovementView.vue`)
- Validates data before sending to server
- Shows toast notifications with clear instructions
- Lists all problems with quantities shown

## Validation Rules

For each approved item:
- ✅ Must have inventory selected
- ✅ Total selected must equal approved quantity
- ✅ Allows 0.01 tolerance for decimal precision

## Common Causes of Error

| Issue | Fix |
|-------|-----|
| No inventory selected | Click "Select Products" and choose inventory |
| Selected too little | Increase selected quantity |
| Selected too much | Decrease selected quantity |
| Decimal mismatch | Match to 2 decimal places |

## Status Codes

| Code | Meaning | Action |
|------|---------|--------|
| 200 | Success | Transfer initialized |
| 422 | Validation failed | Fix items per error message |
| 403 | Not authorized | Check user permissions |
| 404 | Not found | Try reloading page |
| 500 | Server error | Contact support |

## Database

No data is modified until validation passes - safe to retry.

## Need Help?

1. Read the error message - it tells you exactly what's wrong
2. Click the product name to select inventory
3. Match the approved quantity
4. Try again

---

**Last Updated:** November 3, 2025
**Status:** Production Ready ✅
