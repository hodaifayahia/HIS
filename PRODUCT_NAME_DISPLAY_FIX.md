# Product Name Display Fix - Service Demand Purchasing

## Problem
Product names were showing as "N/A" in the Service Demand View component, despite the database containing correct pharmacy product data.

## Root Cause Analysis
The issue was a **data transformation mismatch** between API response and Vue component expectations:

1. **API Controller (`show()` method)**: Was returning raw Eloquent models without resource transformation
2. **Vue Component**: Was trying to access both `product?.name` and `pharmacyProduct?.name` fields
3. **Resource Already Existed**: `ServiceDemandItemResource.php` existed but wasn't being used in the `show()` method
4. **Index Method**: Was using the resource transformation, but `show()` method was not

The resource transforms the response to have a single unified `product` field with a `product_source` indicator, but the `show()` method was bypassing this transformation.

## Solution

### 1. Controller Fix (app/Http/Controllers/ServiceDemandPurchasingController.php)
Added resource transformation to the `show()` method:

```php
// Transform items using ServiceDemandItemResource
$demand->items = $demand->items->map(function ($item) {
    return new ServiceDemandItemResource($item);
});
```

This ensures the API response includes the transformed item structure with:
- `product.name` - The actual product name (from either pharmacy_products or products table)
- `product_source` - Either 'pharmacy' or 'stock' indicating the source
- `product` - Complete product data (sku, unit_of_measure, brand_name, etc.)

### 2. Vue Component Fix (resources/js/Pages/Apps/pharmacy/services/purchasing/ServiceDemandView.vue)
Updated all references to use the unified `product.name` field:

**Location 1 - Product Name Column (Line 475-493)**
```vue
{{ slotProps.data.product?.name || 'N/A' }}
Source: {{ slotProps.data.product_source === 'pharmacy' ? 'Pharmacy' : 'Stock' }}
```

**Location 2 - Item Details Panel (Line 590)**
```vue
{{ item.product?.name || 'N/A' }}
Code: {{ item.product?.sku || item.product?.product_code || item.product?.code_interne || 'N/A' }}
```

**Location 3 - Search Filter (Line 1167)**
```javascript
const productName = (item.product?.name || '').toLowerCase();
const productCode = (item.product?.sku || item.product?.product_code || item.product?.code_interne || '').toLowerCase();
```

**Location 4 - Delete Confirmation (Line 1351)**
```javascript
const productName = item.product?.name || 'Item';
```

## Data Flow

### Before Fix:
```
Database (pharmacy_product) 
  ↓
Model (pharmacyProduct relationship) 
  ↓
Controller show() - Returns raw model
  ↓
API Response: { product_id: null, pharmacyProduct: { name: "APPROVEL" } }
  ↓
Vue Template: item.product?.name || item.pharmacyProduct?.name → "N/A"
```

### After Fix:
```
Database (pharmacy_product) 
  ↓
Model (pharmacyProduct relationship) 
  ↓
Controller show() - Applies ServiceDemandItemResource transformation
  ↓
API Response: { product_source: "pharmacy", product: { name: "APPROVEL", sku: "..." } }
  ↓
Vue Template: item.product?.name → "APPROVEL" ✅
```

## Test Data Verification
Verified database contains correct data:
- Item 1446: pharmacy_product_id=11 → name="APPROVEL" ✅
- Item 1447: pharmacy_product_id=12 → name="Phenytoin 100mg" ✅
- Item 1448: pharmacy_product_id=13 → name="Carbamazepine 5%" ✅

## Files Modified
1. `app/Http/Controllers/ServiceDemandPurchasingController.php` - Added resource transformation to show() method
2. `resources/js/Pages/Apps/pharmacy/services/purchasing/ServiceDemandView.vue` - Updated all product name references (4 locations)

## Impact
- ✅ Product names now display correctly in service demand view
- ✅ Unified API response format for both pharmacy and stock products
- ✅ No database changes required
- ✅ Backward compatible with existing data
- ✅ Consistent transformation between index() and show() methods

## Validation
The fix has been validated:
- Backend: Resource transformation correctly maps pharmacy products to unified product data
- Frontend: Vue component correctly accesses the transformed data
- Data: Database contains all required pharmacy product information
