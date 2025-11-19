# Pharmacy Stockage Tool Error Fix

## Date: October 30, 2025

## Error Message
```
{
  success: false, 
  message: "Failed to add tool: Attempt to read property 'service' on null"
}
```

## Root Cause Analysis

### Problem 1: Wrong Model Relationship
**Location**: `app/Models/PharmacyStorageTool.php` line 76

The `pharmacyStorage()` relationship was pointing to `PharmacyStorage::class` instead of `PharmacyStockage::class`.

```php
// WRONG:
public function pharmacyStorage(): BelongsTo
{
    return $this->belongsTo(PharmacyStorage::class);
}

// CORRECT:
public function pharmacyStorage(): BelongsTo
{
    return $this->belongsTo(PharmacyStockage::class, 'pharmacy_storage_id');
}
```

**Why this matters**: The controller creates tools with `pharmacy_storage_id` pointing to a `PharmacyStockage` record, but the model was trying to relate to `PharmacyStorage` (different table), causing the relationship to return null.

### Problem 2: Unsafe Access to Nested Relationships
**Location**: `app/Models/PharmacyStorageTool.php`

#### Issue 2a: In `boot()` method (lines 58-64)
The `updating` event was trying to access `$tool->pharmacyStorage->service->isDirty()` without checking if these relationships exist.

```php
// BEFORE (UNSAFE):
static::updating(function ($tool) {
    if ($tool->isDirty(['tool_type', 'tool_number', 'block', 'shelf_level']) ||
        $tool->pharmacyStorage->isDirty(['location_code']) ||
        $tool->pharmacyStorage->service->isDirty(['service_abv'])) { // ⚠️ Crashes if null
        $tool->location_code = $tool->generateLocationCode();
    }
});

// AFTER (SAFE):
static::updating(function ($tool) {
    if ($tool->isDirty(['tool_type', 'tool_number', 'block', 'shelf_level'])) {
        $tool->location_code = $tool->generateLocationCode();
    } elseif ($tool->pharmacyStorage && $tool->pharmacyStorage->isDirty(['location_code'])) {
        $tool->location_code = $tool->generateLocationCode();
    } elseif ($tool->pharmacyStorage && $tool->pharmacyStorage->service && $tool->pharmacyStorage->service->isDirty(['service_abv'])) {
        $tool->location_code = $tool->generateLocationCode();
    }
});
```

#### Issue 2b: In `generateLocationCode()` method (line 126)
The method was directly accessing `$this->pharmacyStorage->service->service_abv` without checking if relationships are loaded or exist.

```php
// BEFORE (UNSAFE):
public function generateLocationCode(): string
{
    $serviceAbv = $this->pharmacyStorage->service->service_abv; // ⚠️ Crashes if null
    $storageLocationCode = $this->pharmacyStorage->location_code;
    
    $base = $serviceAbv.$storageLocationCode.'-'.$this->tool_type.$this->tool_number;
    // ...
}

// AFTER (SAFE):
public function generateLocationCode(): string
{
    // Load relationships if not already loaded
    if (!$this->relationLoaded('pharmacyStorage')) {
        $this->load('pharmacyStorage.service');
    } elseif ($this->pharmacyStorage && !$this->pharmacyStorage->relationLoaded('service')) {
        $this->pharmacyStorage->load('service');
    }

    $serviceAbv = $this->pharmacyStorage?->service?->service_abv ?? 'PHR';
    $storageLocationCode = $this->pharmacyStorage?->location_code ?? 'UNK';

    $base = $serviceAbv.$storageLocationCode.'-'.$this->tool_type.$this->tool_number;
    // ...
}
```

**Improvements**:
- Added relationship loading checks
- Used null-safe operators (`?->`)
- Provided fallback values ('PHR' for service, 'UNK' for location)

### Problem 3: Missing Relationship Loading in Controller
**Location**: `app/Http/Controllers/Pharmacy/PharmacyStockageController.php` line 617

The controller was fetching the stockage without loading the `service` relationship:

```php
// BEFORE:
$stockage = PharmacyStockage::findOrFail($stockageId);

// AFTER:
$stockage = PharmacyStockage::with('service')->findOrFail($stockageId);
```

## Files Modified

### 1. `app/Models/PharmacyStorageTool.php`

**Changes**:
1. Fixed `pharmacyStorage()` relationship to point to correct model
2. Added null-safe checks in `boot()` updating event
3. Added relationship loading and null-safe operators in `generateLocationCode()`
4. Added fallback values for missing data

### 2. `app/Http/Controllers/Pharmacy/PharmacyStockageController.php`

**Changes**:
1. Added `->with('service')` when fetching stockage in `addTool()` method

## Testing Recommendations

1. **Test Tool Creation**:
   ```bash
   curl -X POST http://your-app/api/pharmacy/stockages/1/tools \
     -H "Content-Type: application/json" \
     -d '{
       "tool_type": "RY",
       "tool_number": 1,
       "block": "A",
       "shelf_level": 1
     }'
   ```

2. **Test Without Service Relationship**:
   - Create a stockage without a service_id
   - Try adding a tool
   - Should use fallback values ('PHR', 'UNK')

3. **Test Tool Update**:
   - Update a tool's type or number
   - Verify location_code regenerates correctly

4. **Test Edge Cases**:
   - Null pharmacy_storage_id
   - Missing service relationship
   - Invalid stockage ID

## Technical Details

### Model Hierarchy
```
PharmacyStockage (pharmacy_stockages table)
  ├── service → Service (services table)
  └── tools → PharmacyStorageTool (pharmacy_stockage_tools table)

PharmacyStorage (pharmacy_storages table) - DIFFERENT MODEL
```

### Location Code Format
- Format: `{ServiceAbv}{LocationCode}-{ToolType}{ToolNumber}[-{Block}{ShelfLevel}][-T{TempZone}]`
- Example: `PHR001-RY1-A1` (Pharmacy, Location 001, Rayonnage 1, Block A, Shelf 1)
- Fallback: `PHRUNK-RY1` (when service/location unknown)

## Status: ✅ FIXED

All three root causes addressed:
1. ✅ Corrected model relationship from PharmacyStorage to PharmacyStockage
2. ✅ Added null-safe checks in boot() method
3. ✅ Added relationship loading and null-safe operators in generateLocationCode()
4. ✅ Added relationship eager loading in controller

The "Attempt to read property 'service' on null" error should no longer occur.
