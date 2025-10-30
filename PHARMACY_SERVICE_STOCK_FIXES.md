# Pharmacy Service Stock Display Fixes

## Summary
Fixed multiple issues with the pharmacy service stock page where products were not displaying correctly and the route for viewing stockage details was incorrect.

## Issues Fixed

### 1. Products Not Showing Product/Stockage Names
**Problem**: The frontend was expecting `product` and `stockage` properties, but the backend was returning `pharmacyProduct` and `pharmacyStockage`.

**Root Cause**: Inconsistent naming convention between backend and frontend.

**Solution**: Updated the backend API to map the relationships to consistent naming.

**Files Changed**:
- `app/Http/Controllers/Pharmacy/PharmacyInventoryController.php` (Lines 512-591)

**Changes Made**:
```php
// In getServiceStock() method, added mapping after transform:
$item->product = $item->pharmacyProduct;
$item->stockage = $item->pharmacyStockage;
$item->stockage_id = $item->pharmacy_stockage_id;
```

### 2. TypeError: Cannot Read Properties of Undefined (reading 'id')
**Problem**: When clicking "View Stockage Stock" button, the code tried to access `stockage.id` but the stockage object was undefined or null.

**Location**: `serviceStock.vue:688:81` in `viewStockageStock()` method

**Root Cause**: No validation before accessing stockage properties.

**Solution**: Added null/undefined checks before accessing stockage.id and proper error handling.

**Files Changed**:
- `resources/js/Pages/Apps/pharmacy/services/serviceStock.vue` (Lines 687-699)

**Changes Made**:
```javascript
viewStockageStock(stockage) {
  if (!stockage || !stockage.id) {
    console.error('Invalid stockage:', stockage);
    this.submitError = 'Unable to view stockage details';
    setTimeout(() => {
      this.submitError = null;
    }, 3000);
    return;
  }
  this.$router.push({ name: 'pharmacy.stockages.stock', params: { id: stockage.id } });
},
```

### 3. Wrong Route Name for Stockage Navigation
**Problem**: The route was using `stock.stockages.stock` instead of the correct pharmacy route.

**Root Cause**: Copy-paste from stock module without updating the route name.

**Solution**: Changed route name from `stock.stockages.stock` to `pharmacy.stockages.stock`.

**Files Changed**:
- `resources/js/Pages/Apps/pharmacy/services/serviceStock.vue` (Line 697)

### 4. Stockage Filtering Issues
**Problem**: Filtering by stockage wasn't working correctly because the code only checked `item.stockage_id` but the API also returns `item.pharmacy_stockage_id`.

**Solution**: Updated filter methods to check both `stockage_id` and `pharmacy_stockage_id`.

**Files Changed**:
- `resources/js/Pages/Apps/pharmacy/services/serviceStock.vue` (Lines 648-686, 701-737)

**Changes Made**:
```javascript
// In filterProducts():
if (this.stockageFilter) {
  filtered = filtered.filter(item => {
    const stockageId = item.stockage_id || item.pharmacy_stockage_id;
    return stockageId == this.stockageFilter;
  });
}

// In getStockageProductCount():
return this.filteredProducts.filter(item => {
  const itemStockageId = item.stockage_id || item.pharmacy_stockage_id;
  return itemStockageId === stockageId;
}).length;

// In getStockageTotalQuantity():
return this.filteredProducts.filter(item => {
  const itemStockageId = item.stockage_id || item.pharmacy_stockage_id;
  return itemStockageId === stockageId;
}).reduce(...);
```

## API Endpoint
- **Endpoint**: `GET /api/pharmacy/inventory/service-stock`
- **Required Parameter**: `service_id` (required)
- **Optional Parameters**: `search`, `expiry_filter`, `per_page`

## Data Structure Returned
```javascript
{
  success: true,
  data: [
    {
      id: 1,
      pharmacy_product_id: 11,
      pharmacy_stockage_id: 3,
      quantity: 100,
      batch_number: "BATCH123",
      expiry_date: "2025-12-31",
      // Mapped relationships
      product: { id: 11, name: "Product Name", ... },
      stockage: { id: 3, name: "Stockage Name", ... },
      stockage_id: 3,
      // Additional computed properties
      days_until_expiry: 425,
      is_expired: false,
      is_expiring_soon: false,
      requires_prescription: true,
      is_controlled_substance: false
    }
  ],
  meta: {
    current_page: 1,
    last_page: 1,
    per_page: 10000,
    total: 5
  }
}
```

## Testing Checklist
- [x] Products display with correct names
- [x] Stockage names display correctly
- [x] Filter by stockage works
- [x] Clicking "View Stockage Stock" navigates to correct page
- [x] No JavaScript errors in console
- [x] All product counts are accurate
- [x] Total quantities calculate correctly

## Related Files
1. **Backend**:
   - `app/Http/Controllers/Pharmacy/PharmacyInventoryController.php`
   - `routes/web.php` (Line 941: Route definition)

2. **Frontend**:
   - `resources/js/Pages/Apps/pharmacy/services/serviceStock.vue`
   - `resources/js/Routes/pharmacy.js` (Route name: `pharmacy.stockages.stock`)

## Database Tables Used
- `pharmacy_inventories` - Main inventory table
- `pharmacy_products` - Product details
- `pharmacy_stockages` - Stockage/storage locations
- `services` - Service information (for filtering)

## Next Steps
All critical issues have been resolved. The pharmacy service stock page now:
1. ✅ Shows all products with correct names
2. ✅ Displays stockage names correctly
3. ✅ Navigates to correct stockage detail page
4. ✅ Filters by stockage properly
5. ✅ Handles errors gracefully

## Related Documentation
- [PHARMACY_INVENTORY_FIXES.md](./PHARMACY_INVENTORY_FIXES.md) - Previous inventory display fixes
- Product Settings: Located in pharmacy product global settings
- Stock Management: Accessible from product details page
