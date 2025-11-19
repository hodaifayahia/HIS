# Selling Settings Feature Implementation

## Overview
Complete implementation of Selling Settings management system for HIS (Hospital Information System) with separate configurations for Pharmacy and Stock modules.

## Features Implemented

### 1. Database Schema
**Table**: `selling_settings`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| service_id | bigint | Foreign key to services table |
| percentage | decimal(8,2) | Percentage value (0-100) |
| type | enum('pharmacy', 'stock') | Module type |
| is_active | boolean | Active status (only one per service/type) |
| created_by | bigint | User who created the record |
| updated_by | bigint | User who last updated |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Last update timestamp |
| deleted_at | timestamp | Soft delete timestamp |

**Unique Constraint**: Only one active setting allowed per service and type combination.

### 2. Backend Implementation

#### Model: `SellingSettings`
**Location**: `app/Models/SellingSettings.php`

**Features**:
- Soft deletes support
- Automatic deactivation of other settings when one is activated
- Scopes for filtering (active, pharmacy, stock, forService)
- Static method `getActivePercentage($serviceId, $type)` for quick retrieval

**Relationships**:
- `service()` - BelongsTo Service
- `creator()` - BelongsTo User (created_by)
- `updater()` - BelongsTo User (updated_by)

#### Controller: `SellingSettingsController`
**Location**: `app/Http/Controllers/Settings/SellingSettingsController.php`

**Endpoints**:

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/selling-settings` | List all settings (with filters) |
| POST | `/api/selling-settings` | Create new setting |
| GET | `/api/selling-settings/{id}` | Get single setting |
| PUT | `/api/selling-settings/{id}` | Update setting |
| DELETE | `/api/selling-settings/{id}` | Delete setting |
| POST | `/api/selling-settings/{id}/toggle-active` | Toggle active status |
| POST | `/api/selling-settings/active-percentage` | Get active percentage for service |
| GET | `/api/selling-settings/services` | Get all services for dropdown |

**Query Parameters** (for index):
- `type` - Filter by pharmacy/stock
- `service_id` - Filter by service
- `is_active` - Filter by active status
- `search` - Search service names
- `sort_by` - Sort field (default: created_at)
- `sort_order` - Sort direction (default: desc)

#### Routes
**Location**: `routes/api/settings.php`

All routes prefixed with `/api/selling-settings`

### 3. Frontend Implementation

#### Vue Component: `SellingSettingslist.vue`
**Location**: `resources/js/Pages/Apps/pharmacy/SellingSettings/SellingSettingslist.vue`

**Features**:
- ✅ Beautiful UI matching BonCommendList design
- ✅ Gradient backgrounds and modern styling
- ✅ Real-time search with debouncing
- ✅ Advanced filtering (service, status)
- ✅ Active filter tags with click-to-remove
- ✅ Statistics cards (Total, Active, Services count)
- ✅ Sortable DataTable with pagination
- ✅ Create/Edit modal with validation
- ✅ Toggle active status with confirmation
- ✅ Delete with confirmation dialog
- ✅ Floating Action Button (FAB) for quick create
- ✅ Toast notifications for all actions
- ✅ Loading states and error handling
- ✅ Context-aware (pharmacy vs stock)

**Props**:
- `type` (String): 'pharmacy' or 'stock' - Determines which settings to show

#### Routes Configuration

**Pharmacy Route**:
```javascript
{
  path: 'selling-settings',
  name: 'pharmacy.selling-settings',
  component: () => import('../Pages/Apps/pharmacy/SellingSettings/SellingSettingslist.vue'),
}
```

**Stock Route**:
```javascript
{
  path: 'selling-settings',
  name: 'stock.selling-settings',
  component: () => import('../Pages/Apps/pharmacy/SellingSettings/SellingSettingslist.vue'),
  props: { type: 'stock' }
}
```

#### Navigation Integration

**PharmacySidebar.vue**:
```vue
<li class="nav-item">
    <router-link to="/pharmacy/selling-settings" active-class="active" class="nav-link">
        <i class="fas fa-percentage nav-icon"></i>
        <p>Selling Settings</p>
    </router-link>
</li>
```

**StockSidebar.vue**:
```vue
<li class="nav-item">
    <router-link to="/stock/selling-settings" active-class="active" class="nav-link">
        <i class="fas fa-percentage nav-icon"></i>
        <p>Selling Settings</p>
    </router-link>
</li>
```

## Business Rules

1. **One Active Setting per Service/Type**: 
   - Only one setting can be active for each combination of service and type
   - Activating a setting automatically deactivates others for that service/type

2. **Context Separation**:
   - Pharmacy module only shows pharmacy-type settings
   - Stock module only shows stock-type settings
   - Type is automatically set based on current context

3. **Percentage Validation**:
   - Must be between 0 and 100
   - Supports 2 decimal places
   - Required field

4. **Service Validation**:
   - Must exist in services table
   - Required field

## Usage Examples

### API Usage

**Get Active Percentage**:
```javascript
const response = await axios.post('/api/selling-settings/active-percentage', {
  service_id: 5,
  type: 'pharmacy'
});
// Returns: { status: 'success', data: { percentage: 15.50 } }
```

**Create Setting**:
```javascript
const response = await axios.post('/api/selling-settings', {
  service_id: 5,
  percentage: 15.50,
  type: 'pharmacy',
  is_active: true
});
```

**Get Settings for Pharmacy**:
```javascript
const response = await axios.get('/api/selling-settings?type=pharmacy&is_active=1');
```

### Model Usage

**Get Active Percentage**:
```php
$percentage = SellingSettings::getActivePercentage($serviceId, 'pharmacy');
```

**Scoped Queries**:
```php
// Get all active pharmacy settings
$settings = SellingSettings::active()->pharmacy()->with('service')->get();

// Get settings for specific service
$settings = SellingSettings::forService($serviceId)->get();
```

## Database Migration

**To run the migration**:
```bash
php artisan migrate
```

**Migration File**: `database/migrations/2025_11_15_131543_create_selling_settings_table.php`

## Design Highlights

### UI/UX Features
- ✨ Modern gradient backgrounds
- ✨ Smooth animations and transitions
- ✨ Responsive design (mobile-friendly)
- ✨ Hover effects and shadows
- ✨ Color-coded status indicators
- ✨ Clean, professional medical-style table
- ✨ Intuitive filter system
- ✨ Clear action buttons with tooltips

### Color Scheme
- **Primary**: Blue (#3b82f6) - Main actions
- **Success**: Green - Active status
- **Warning**: Amber - Statistics
- **Info**: Cyan - Type badges
- **Danger**: Red - Delete actions
- **Secondary**: Slate - Neutral elements

## Testing Checklist

- [ ] Create new selling setting
- [ ] Edit existing setting
- [ ] Toggle active status
- [ ] Delete setting
- [ ] Search functionality
- [ ] Filter by service
- [ ] Filter by status
- [ ] Clear filters
- [ ] Pagination
- [ ] Sorting columns
- [ ] Switch between pharmacy and stock contexts
- [ ] Verify only one active setting per service/type
- [ ] Form validation
- [ ] API error handling
- [ ] Toast notifications

## Future Enhancements

- [ ] Export to Excel/PDF
- [ ] Bulk operations (activate/deactivate multiple)
- [ ] Setting history/audit log
- [ ] Advanced reporting
- [ ] Setting templates
- [ ] Percentage calculation preview
- [ ] Integration with pricing engine

## Files Created/Modified

### Created
1. `database/migrations/2025_11_15_131543_create_selling_settings_table.php`
2. `app/Models/SellingSettings.php`
3. `app/Http/Controllers/Settings/SellingSettingsController.php`
4. `resources/js/Pages/Apps/pharmacy/SellingSettings/SellingSettingslist.vue`

### Modified
1. `routes/api/settings.php` - Added selling settings routes
2. `resources/js/Routes/pharmacy.js` - Added pharmacy route
3. `resources/js/Routes/stock.js` - Added stock route
4. `resources/js/Pages/Dashborad/Sidebars/PharmacySidebar.vue` - Added menu item
5. `resources/js/Pages/Dashborad/Sidebars/StockSidebar.vue` - Added menu item

## Notes

- The component is shared between pharmacy and stock modules, differentiated by the `type` prop
- All database operations are wrapped in transactions for data integrity
- Soft deletes are enabled for data recovery
- The system automatically handles the uniqueness constraint for active settings
- All user actions are tracked (created_by, updated_by)

---

**Implementation Date**: November 15, 2025  
**Status**: ✅ Complete  
**Version**: 1.0.0
