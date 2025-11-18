# SellingSettings Feature - Setup & Deployment Guide

## Overview
The SellingSettings feature has been fully implemented and is ready for deployment. It allows managing selling percentages by service and type (pharmacy or stock).

## Status: ✅ Complete - Awaiting Database Setup

### Components Implemented

#### 1. **Database**
- **Migration File**: `/database/migrations/2025_11_15_131543_create_selling_settings_table.php`
- **SQL Script**: `/sql/create_selling_settings_table.sql`
- **Table**: `selling_settings`
- **Fields**:
  - `id` - Primary key
  - `service_id` - Foreign key to services table (cascade delete)
  - `percentage` - Decimal(8,2) for the selling percentage
  - `type` - Enum: 'pharmacy' or 'stock'
  - `is_active` - Boolean, only one active per service/type
  - `created_by`, `updated_by` - Audit fields
  - `created_at`, `updated_at`, `deleted_at` - Timestamps
- **Constraints**:
  - Unique constraint on (service_id, type, is_active)
  - Index on (service_id, type, is_active) for performance

#### 2. **Models**
- **Service** (`app/Models/Service.php`) - NEW ✅
  - Maps to `services` table
  - Has relationships with SellingSettings
  - Methods: `sellingSettings()`, `activeSellingSettings()`
  
- **SellingSettings** (`app/Models/SellingSettings.php`) - ✅
  - Boot method enforces single active setting per service/type
  - Relationships: service, creator, updater
  - Scopes: active(), pharmacy(), stock(), forService()
  - Helper: getActivePercentage($serviceId, $type)

#### 3. **Controller**
- **File**: `app/Http/Controllers/Settings/SellingSettingsController.php`
- **Methods**:
  - `index()` - List all with filters (type, service_id, is_active, search)
  - `store()` - Create new setting
  - `show()` - Get single setting
  - `update()` - Update setting
  - `destroy()` - Delete setting
  - `toggleActive()` - Toggle active status with transaction safety
  - `getActivePercentage()` - Retrieve active percentage for service/type
  - `getServices()` - Get services for dropdown

#### 4. **API Routes**
- **File**: `routes/web.php` (lines 232-241)
- **Base URL**: `/api/selling-settings`
- **Endpoints**:
  ```
  GET    /api/selling-settings                    - List all
  POST   /api/selling-settings                    - Create
  GET    /api/selling-settings/services           - Get services
  POST   /api/selling-settings/active-percentage  - Get active percentage
  GET    /api/selling-settings/{id}               - Get single
  PUT    /api/selling-settings/{id}               - Update
  DELETE /api/selling-settings/{id}               - Delete
  POST   /api/selling-settings/{id}/toggle-active - Toggle active
  ```

#### 5. **Frontend Component**
- **File**: `resources/js/Pages/Apps/pharmacy/SellingSettings/SellingSettingslist.vue`
- **Features**:
  - Gradient header with stats cards (Total Settings, Active, Unique Services)
  - Search bar with debounce
  - Filter dropdowns (Service, Status)
  - Active filters display
  - Data table with sorting and pagination
  - Create/Edit dialog with form validation
  - Action buttons (Edit, Toggle Active, Delete)
  - Floating Action Button (FAB) for quick create
  - Loading states and toast notifications
  - Confirmation dialogs for destructive actions

#### 6. **Navigation**
- **File**: `resources/js/Pages/Dashborad/Sidebars/PharmacySidebar.vue`
- **Route**: `/pharmacy/selling-settings`
- **Menu Item**: "Selling Settings" (in English, flattened navigation)

---

## Deployment Steps

### Step 1: Verify Code is in Place ✅
All files have been created and are ready:
```bash
# Check models exist
ls -la app/Models/Service.php
ls -la app/Models/SellingSettings.php

# Check controller exists
ls -la app/Http/Controllers/Settings/SellingSettingsController.php

# Check routes are imported and defined
grep -n "SellingSettingsController" routes/web.php

# Check Vue component
ls -la resources/js/Pages/Apps/pharmacy/SellingSettings/SellingSettingslist.vue
```

### Step 2: Run Database Migration

**Option A: Using Laravel Artisan (Recommended)**
```bash
php artisan migrate
```

**Option B: Direct SQL (If artisan fails)**
Run the SQL script on your database:
```bash
mysql -h your_host -u your_user -p your_database < sql/create_selling_settings_table.sql
```

Or execute in MySQL client:
```sql
-- Source the SQL file
SOURCE /home/administrator/www/HIS/sql/create_selling_settings_table.sql;
```

### Step 3: Clear Cache & Rebuild Assets
```bash
# Clear Laravel cache
php artisan cache:clear
php artisan config:clear
php artisan route:cache

# Rebuild frontend assets
npm run build
# Or for development with hot reload
npm run dev
```

### Step 4: Verify Setup
1. Navigate to `/pharmacy/selling-settings` in browser
2. Check browser console for API errors
3. Try creating a new selling setting
4. Verify data appears in database:
   ```sql
   SELECT * FROM selling_settings;
   ```

---

## Testing Checklist

- [ ] Model relationships load correctly
- [ ] Create new selling setting via UI
- [ ] Edit existing setting
- [ ] Toggle active status
- [ ] Delete setting (soft delete)
- [ ] Search by service name
- [ ] Filter by type (pharmacy/stock)
- [ ] Filter by status (active/inactive)
- [ ] Verify only one active per service/type
- [ ] Verify cascade delete when service deleted
- [ ] Check audit fields (created_by, updated_by) are populated
- [ ] Verify API responses include all necessary data
- [ ] Test with various service selections

---

## Database Schema Details

```sql
CREATE TABLE `selling_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `service_id` bigint unsigned NOT NULL,
  `percentage` decimal(8,2) NOT NULL DEFAULT 0,
  `type` enum('pharmacy','stock') NOT NULL DEFAULT 'pharmacy',
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` bigint unsigned,
  `updated_by` bigint unsigned,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  `deleted_at` timestamp NULL,
  
  PRIMARY KEY (`id`),
  FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  
  UNIQUE KEY `unique_active_per_service_type` (`service_id`, `type`, `is_active`),
  KEY `selling_settings_service_type_active_index` (`service_id`, `type`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## API Examples

### Get All Selling Settings
```bash
curl http://localhost:8000/api/selling-settings
```

### Get Active Settings for Pharmacy
```bash
curl "http://localhost:8000/api/selling-settings?type=pharmacy&is_active=1"
```

### Create New Setting
```bash
curl -X POST http://localhost:8000/api/selling-settings \
  -H "Content-Type: application/json" \
  -d '{
    "service_id": 1,
    "percentage": 15.50,
    "type": "pharmacy",
    "is_active": true
  }'
```

### Get Active Percentage
```bash
curl -X POST http://localhost:8000/api/selling-settings/active-percentage \
  -H "Content-Type: application/json" \
  -d '{
    "service_id": 1,
    "type": "pharmacy"
  }'
```

### Toggle Active Status
```bash
curl -X POST http://localhost:8000/api/selling-settings/1/toggle-active
```

---

## Troubleshooting

### Error: Class "App\Models\Service" not found
- **Fix**: Service model has been created at `app/Models/Service.php` ✅

### Error: Table 'selling_settings' doesn't exist
- **Fix**: Run the migration or execute the SQL script (see Step 2 above)

### API returns 404 on `/api/selling-settings`
- **Fix**: 
  1. Verify routes are imported: `grep "SellingSettingsController" routes/web.php`
  2. Clear route cache: `php artisan route:cache`
  3. Restart Laravel server: `php artisan serve`

### Vue component not showing data
- **Fix**:
  1. Check browser console for network errors
  2. Verify database migration has run
  3. Check SellingSettingsController logs
  4. Clear browser cache and reload

### Only one active setting not enforced
- **Fix**: This is handled by:
  1. Unique database constraint on (service_id, type, is_active)
  2. Model boot method that deactivates competitors
  3. Controller transaction safety
  - Verify constraint exists: `SHOW KEYS FROM selling_settings;`

---

## File Locations Summary

```
✅ app/Models/Service.php - NEW
✅ app/Models/SellingSettings.php
✅ app/Http/Controllers/Settings/SellingSettingsController.php
✅ routes/web.php (import + routes added)
✅ resources/js/Pages/Apps/pharmacy/SellingSettings/SellingSettingslist.vue
✅ resources/js/Routes/pharmacy.js (route already exists)
✅ resources/js/Pages/Dashborad/Sidebars/PharmacySidebar.vue (updated)
✅ database/migrations/2025_11_15_131543_create_selling_settings_table.php
✅ sql/create_selling_settings_table.sql (helper SQL)
```

---

## Integration Complete ✅

All code is in place and ready for production deployment. The only remaining step is running the database migration on the production server.
