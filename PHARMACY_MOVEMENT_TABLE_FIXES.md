# Pharmacy Stock Movement Table Migration Fixes

## Issue Summary
The pharmacy stock movement system was incorrectly referencing stock tables (`inventories`, `products`, `stockages`) instead of pharmacy tables (`pharmacy_inventories`, `pharmacy_products`, `pharmacy_stockages`).

## Root Cause
- System initially built to mirror stock management system
- Database uses pharmacy-specific tables but code referenced stock tables
- Validation rules checked wrong tables

## Files Modified

### 1. PharmacyStockMovementController.php

#### Fixed Methods:

**a) addItem() - Lines 346-415**
- ✅ Changed validation: `exists:products,id` → `exists:pharmacy_products,id`
- ✅ Kept column name as `product_id` (actual table column)
- ✅ Added `pharmacyProduct` relationship loading
- ✅ Added alias `$data->product = $data->pharmacyProduct` for frontend compatibility

**b) updateItem() - Lines 420-470**
- ✅ Changed validation: `exists:products,id` → `exists:pharmacy_products,id`
- ✅ Note: Needs complete update similar to addItem() - still references old relationships

**c) getSuggestions() - Lines 673-765**
- ✅ Migrated 5 DB queries from stock tables to pharmacy tables
- ✅ Changed: `DB::table('inventories')` → `DB::table('pharmacy_inventories')`
- ✅ Changed: `join('products',...)` → `join('pharmacy_products',...)`
- ✅ Changed: `join('stockages',...)` → `join('pharmacy_stockages',...)`
- ✅ Updated field references:
  - `total_units` → `quantity`
  - `barcode` → `batch_number`
  - `controlled_substance_level` → `is_controlled_substance`
- ✅ Fixed foreign keys:
  - `product_id` → `pharmacy_product_id`
  - `stockage_id` → `pharmacy_stockage_id`

**d) selectInventory() - Lines 1060-1080**
- ✅ Changed: `DB::table('inventories')` → `DB::table('pharmacy_inventories')`
- ✅ Updated field reference: `barcode` → `batch_number`
- ✅ Removed reference to non-existent `total_units`

**e) availableStock() - Lines 922-960**
- ✅ Changed validation: `exists:products,id` → `exists:pharmacy_products,id`
- ✅ Changed: `pharmacyStorages` → `pharmacyStockages`
- ✅ Changed: `inventories()` → `pharmacyInventories()`
- ✅ Changed: `where('product_id',...)` → `where('pharmacy_product_id',...)`

**f) getProductInventory() - Lines 977-1020**
- ✅ Changed: `pharmacyStorages` → `pharmacyStockages`
- ✅ Changed: `inventories()` → `pharmacyInventories()`
- ✅ Changed: `->with('product')` → `->with('pharmacyProduct')`
- ✅ Changed: `where('product_id', $productId)` → `where('pharmacy_product_id', $productId)`
- ✅ Removed non-existent fields: barcode, total_units, location
- ✅ Added pharmacy fields: purchase_price, is_controlled_substance, requires_prescription

### 2. PharmacyMovementItem.php

**Changes:**
- ✅ Added `product_id` to fillable array (actual column name in DB)
- ✅ Added `pharmacy_movement_id` to fillable array
- ✅ Added `pharmacyProduct()` relationship method:
  ```php
  public function pharmacyProduct(): BelongsTo
  {
      return $this->belongsTo(PharmacyProduct::class, 'product_id', 'id');
  }
  ```
- ✅ Fixed `inventorySelections()` and `selectedInventory()` relationships to use correct foreign key:
  ```php
  public function inventorySelections()
  {
      return $this->hasMany(PharmacyMovementInventorySelection::class, 'pharmacy_stock_movement_item_id');
  }
  
  public function selectedInventory()
  {
      return $this->hasMany(PharmacyMovementInventorySelection::class, 'pharmacy_stock_movement_item_id');
  }
  ```

### 3. PharmacyMovementInventorySelection.php

**Changes:**
- ✅ Added `pharmacy_stock_movement_item_id` to fillable array (actual column name in DB)
- ✅ Added `pharmacy_inventory_id` to fillable array
- ✅ Fixed `pharmacyMovementItem()` relationship to specify foreign key:
  ```php
  public function pharmacyMovementItem(): BelongsTo
  {
      return $this->belongsTo(PharmacyMovementItem::class, 'pharmacy_stock_movement_item_id');
  }
  ```
- ✅ Added `pharmacyInventory()` relationship:
  ```php
  public function pharmacyInventory(): BelongsTo
  {
      return $this->belongsTo(PharmacyInventory::class, 'pharmacy_inventory_id');
  }
  ```

### 4. PharmacyStockMovementController.php - selectInventory() method

**Changes:**
- ✅ Fixed delete query: `where('pharmacy_stock_movement_item_id', $item->id)`
- ✅ Fixed insert: Changed `'pharmacy_movement_item_id'` to `'pharmacy_stock_movement_item_id'`
- ✅ Fixed insert: Changed `'inventory_id'` to `'pharmacy_inventory_id'`

## Database Schema Notes

### pharmacy_stock_movement_items table
- ✅ Uses `product_id` (not `pharmacy_product_id`)
- ✅ Uses `pharmacy_movement_id` 
- ✅ Has columns: requested_quantity, approved_quantity, executed_quantity, provided_quantity
- ✅ Has pharmacy-specific fields: dosage_instructions, administration_route

### pharmacy_inventories table
- ✅ Uses `pharmacy_product_id` (foreign key to pharmacy_products)
- ✅ Uses `pharmacy_stockage_id` (foreign key to pharmacy_stockages)
- ✅ Has `quantity` field (not total_units)
- ✅ Has `batch_number` (not barcode)
- ✅ Has expiry_date, purchase_price fields

### pharmacy_products table
- ✅ Has `is_controlled_substance` boolean
- ✅ Has `requires_prescription` boolean
- ✅ Does NOT have controlled_substance_level, storage_temperature_min

### pharmacy_movement_inventory_selections table
- ✅ Uses `pharmacy_stock_movement_item_id` (foreign key to pharmacy_stock_movement_items)
- ✅ Uses `pharmacy_inventory_id` (foreign key to pharmacy_inventories)
- ✅ Has `selected_quantity` field
- ✅ Has created_at, updated_at timestamps

## Validation Fix Pattern

**Before:**
```php
'product_id' => 'required|exists:products,id'
```

**After:**
```php
'product_id' => 'required|exists:pharmacy_products,id'
```

**Key Point:** Validate against `pharmacy_products` table, but use `product_id` as column name in `pharmacy_stock_movement_items`.

## Query Migration Pattern

**Before:**
```php
DB::table('inventories')
    ->join('products', 'inventories.product_id', '=', 'products.id')
    ->where('product_id', $id)
```

**After:**
```php
DB::table('pharmacy_inventories')
    ->join('pharmacy_products', 'pharmacy_inventories.pharmacy_product_id', '=', 'pharmacy_products.id')
    ->where('pharmacy_product_id', $id)
```

## Relationship Pattern

**In Controller:**
```php
$data = $item->load('pharmacyProduct');
if ($data->pharmacyProduct) {
    $data->product = $data->pharmacyProduct; // Alias for frontend
}
```

**In Model:**
```php
public function pharmacyProduct(): BelongsTo
{
    return $this->belongsTo(PharmacyProduct::class, 'product_id', 'id');
}
```

## Testing Checklist

- [x] Validation accepts pharmacy product IDs
- [x] Suggestions endpoint returns pharmacy data
- [x] Can add items to pharmacy movements
- [ ] Can update items in movements
- [ ] Available stock calculation uses pharmacy tables
- [ ] Inventory selection works correctly
- [ ] Complete movement workflow (draft → pending → approved → executed)

## Remaining Work

1. **updateItem() method** - Needs same treatment as addItem():
   - Still references old `->product` relationship
   - Needs to use `pharmacyProduct` relationship
   - Product field mappings need update

2. **Other Methods to Verify:**
   - index()
   - getDrafts()
   - getPendingApprovals()
   - show()
   - All methods that load product relationships

3. **Service Model:**
   - Verify `pharmacyStockages()` relationship exists
   - May be referenced as `pharmacyStorages` in some places

## Related Files
- `/home/administrator/www/HIS/app/Http/Controllers/Pharmacy/PharmacyStockMovementController.php`
- `/home/administrator/www/HIS/app/Models/PharmacyMovementItem.php`
- `/home/administrator/www/HIS/app/Models/PharmacyProduct.php`

## Errors Resolved

### Error 1: Column 'pharmacy_product_id' not found
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'pharmacy_product_id' in 'where clause'
```
**Solution:** Use `product_id` column name (actual DB column) while validating against `pharmacy_products` table.

### Error 2: Column 'pharmacy_movement_item_id' not found
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'pharmacy_movement_item_id' in 'where clause'
```
**Solution:** Use `pharmacy_stock_movement_item_id` column name in `pharmacy_movement_inventory_selections` table.

## Key Column Name Mappings

| Logical Name | Actual DB Column | Table |
|--------------|------------------|-------|
| Product ID | `product_id` | pharmacy_stock_movement_items |
| Movement ID | `pharmacy_movement_id` | pharmacy_stock_movement_items |
| Movement Item ID | `pharmacy_stock_movement_item_id` | pharmacy_movement_inventory_selections |
| Inventory ID | `pharmacy_inventory_id` | pharmacy_movement_inventory_selections |
| Product ID | `pharmacy_product_id` | pharmacy_inventories |
| Stockage ID | `pharmacy_stockage_id` | pharmacy_inventories |
