# Stock Movement System Enhancements

This document outlines the comprehensive enhancements made to the stock movement approval system to improve code quality, maintainability, and user experience.

## Overview

The stock movement system has been significantly enhanced with the following improvements:

1. **Prevented editing after approval/rejection**
2. **Enhanced code architecture with service layer pattern**
3. **Improved validation with form request classes**
4. **Added comprehensive audit logging**
5. **Implemented event-driven architecture**
6. **Enhanced API responses with resources**
7. **Added comprehensive testing**

## Key Features

### 1. Item Status Management

**New Model Methods in `StockMovementItem`:**
- `isApproved()`: Check if item is approved
- `isRejected()`: Check if item is rejected
- `isPending()`: Check if item is pending approval
- `isEditable()`: Check if item can be edited (only pending items)
- `getStatus()`: Get current status as string

**Usage:**
```php
$item = StockMovementItem::find(1);
if ($item->isEditable()) {
    // Allow editing
} else {
    // Show read-only view
}
```

### 2. Service Layer Architecture

**New Service: `StockMovementApprovalService`**

Centralizes business logic for stock movement approvals:

```php
$service = new StockMovementApprovalService();

// Approve items
$result = $service->approveItems($movement, [1, 2, 3]);

// Reject items with reason
$result = $service->rejectItems($movement, [4, 5], 'Insufficient stock');

// Get movement statistics
$stats = $service->getMovementStatistics($movement);
```

### 3. Enhanced Validation

**Form Request Classes:**
- `ApproveItemsRequest`: Validates approval requests
- `RejectItemsRequest`: Validates rejection requests

Both include:
- Basic validation rules
- Custom validation to ensure items belong to movement
- Checks that items are still editable
- Proper error messages

### 4. API Resources

**New Resource: `StockMovementItemResource`**

Provides consistent API responses including:
- All item data
- Status information (`is_approved`, `is_rejected`, `is_pending`, `is_editable`)
- Calculated fields (actual quantities, display units)
- Related data (product, inventory selections)

### 5. Event-Driven Architecture

**Events:**
- `StockMovementItemApproved`: Fired when item is approved
- `StockMovementItemRejected`: Fired when item is rejected

**Listeners:**
- `LogStockMovementAudit`: Automatically logs all approval/rejection actions

### 6. Comprehensive Audit Logging

**New Model: `StockMovementAuditLog`**

Tracks all actions with:
- User who performed action
- Timestamp
- Old and new values
- IP address and user agent
- Detailed notes

**Migration: `create_stock_movement_audit_logs_table`**

### 7. Enhanced Controller Methods

Updated `StockMovementController` methods:
- Use form request validation
- Leverage service layer
- Return enhanced API responses
- Include movement statistics
- Better error handling and logging

## API Changes

### Approval Endpoint

**POST** `/api/stock-movements/{id}/approve`

**Request:**
```json
{
    "item_ids": [1, 2, 3]
}
```

**Response:**
```json
{
    "success": true,
    "message": "3 items approved successfully",
    "processed_items": [...],
    "statistics": {
        "total_items": 5,
        "pending_items": 2,
        "approved_items": 3,
        "rejected_items": 0,
        "editable_items": 2
    }
}
```

### Rejection Endpoint

**POST** `/api/stock-movements/{id}/reject`

**Request:**
```json
{
    "item_ids": [4, 5],
    "rejection_reason": "Insufficient stock available"
}
}
```

**Response:**
```json
{
    "success": true,
    "message": "2 items rejected successfully",
    "processed_items": [...],
    "statistics": {...}
}
```

## Security Enhancements

1. **Validation at multiple levels:**
   - Form request validation
   - Service layer validation
   - Database constraints

2. **Audit logging:**
   - All actions tracked with user, IP, and timestamp
   - Immutable audit trail

3. **Authorization checks:**
   - Users can only access movements for their service
   - Items must belong to the specified movement
   - Only pending items can be modified

## Testing

**New Test File: `StockMovementApprovalTest`**

Comprehensive test coverage including:
- Successful approval/rejection scenarios
- Validation error handling
- Event dispatching verification
- Status method testing
- Statistics accuracy
- Authorization checks

**Run tests:**
```bash
php artisan test tests/Feature/StockMovementApprovalTest.php
```

## Database Changes

### New Migration
- `2025_01_20_000000_create_stock_movement_audit_logs_table.php`

**Run migration:**
```bash
php artisan migrate
```

## Frontend Integration

The enhanced API responses now include `is_editable` flags that can be used in the frontend to:

1. **Disable editing controls** for processed items
2. **Show status badges** (pending, approved, rejected)
3. **Display audit information**
4. **Show movement statistics**

**Example Vue.js usage:**
```javascript
// In your Vue component
computed: {
    canEditItem() {
        return this.item.is_editable;
    },
    statusClass() {
        return {
            'status-pending': this.item.is_pending,
            'status-approved': this.item.is_approved,
            'status-rejected': this.item.is_rejected
        };
    }
}
```

## Performance Optimizations

1. **Eager loading** relationships in controller methods
2. **Database transactions** for data consistency
3. **Queued event listeners** for audit logging
4. **Indexed audit log table** for fast queries

## Error Handling

1. **Structured error responses** with proper HTTP status codes
2. **Detailed logging** with context information
3. **Graceful degradation** when services are unavailable
4. **User-friendly error messages**

## Future Enhancements

1. **Real-time notifications** using WebSockets
2. **Bulk operations** for multiple movements
3. **Advanced reporting** and analytics
4. **Integration with inventory management**
5. **Mobile app support**

## Maintenance

### Regular Tasks
1. **Monitor audit logs** for unusual activity
2. **Clean up old audit records** (consider archiving after 1 year)
3. **Review and update validation rules** as business requirements change
4. **Performance monitoring** of approval operations

### Troubleshooting

**Common Issues:**

1. **Items not editable:**
   - Check if item status is pending
   - Verify movement status allows editing

2. **Validation errors:**
   - Ensure item IDs belong to the movement
   - Check user has access to the providing service

3. **Event not firing:**
   - Verify event service provider is registered
   - Check queue worker is running for audit logs

**Debug Commands:**
```bash
# Check movement statistics
php artisan tinker
>>> $service = app(\App\Services\StockMovementApprovalService::class);
>>> $movement = \App\Models\StockMovement::find(1);
>>> $service->getMovementStatistics($movement);

# View audit logs
>>> \App\Models\StockMovementAuditLog::forMovement(1)->recent()->get();
```

## Next Steps

To complete the implementation of the stock movement enhancements, follow these steps:

### 1. Database Migration
```bash
# Run the new migration to create audit logs table
php artisan migrate
```

### 2. Register Event Service Provider
Add the new service provider to `config/app.php`:
```php
'providers' => [
    // ... other providers
    App\Providers\StockMovementEventServiceProvider::class,
],
```

### 3. Update StockMovementItem Model
Add the new status methods to the existing `StockMovementItem` model:
```php
// Add these methods to app/Models/StockMovementItem.php
public function isApproved(): bool
{
    return !is_null($this->approved_quantity) && $this->approved_quantity > 0;
}

public function isRejected(): bool
{
    return !is_null($this->approved_quantity) && $this->approved_quantity == 0 && !empty($this->notes);
}

public function isPending(): bool
{
    return is_null($this->approved_quantity);
}

public function isEditable(): bool
{
    return $this->isPending();
}

public function getStatus(): string
{
    if ($this->isApproved()) return 'approved';
    if ($this->isRejected()) return 'rejected';
    return 'pending';
}
```

### 4. Queue Configuration
Ensure queue workers are running for audit logging:
```bash
# Start queue worker
php artisan queue:work --queue=audit
```

### 5. Frontend Integration
Update the frontend components to use the new API response structure:
- Use `is_editable` flag to disable/enable editing controls
- Display status badges using `is_approved`, `is_rejected`, `is_pending` flags
- Show movement statistics in the UI
- Implement real-time updates using the new events

### 6. Testing
Run the comprehensive test suite:
```bash
# Run all stock movement tests
php artisan test tests/Feature/StockMovementApprovalTest.php

# Run specific test methods
php artisan test --filter="test_can_approve_items"
php artisan test --filter="test_cannot_edit_approved_items"
```

### 7. Configuration Updates
Update any configuration files that reference the old approval logic:
- Review API routes for any changes needed
- Update middleware if authorization logic changed
- Check notification configurations

### 8. Documentation Updates
Update any existing API documentation to reflect:
- New request/response formats
- Updated validation rules
- New status fields
- Event system integration

### 9. Deployment Checklist
- [ ] Run database migrations
- [ ] Register service providers
- [ ] Update model methods
- [ ] Configure queue workers
- [ ] Deploy frontend changes
- [ ] Run tests in production environment
- [ ] Monitor audit logs
- [ ] Verify event dispatching

### 10. Monitoring Setup
Set up monitoring for:
- Audit log growth and cleanup
- Queue processing performance
- Event dispatching success rates
- API response times for approval operations

## Conclusion

These enhancements significantly improve the stock movement system by:
- **Preventing data corruption** through proper validation
- **Improving user experience** with clear status indicators
- **Enhancing security** through comprehensive audit logging
- **Increasing maintainability** with clean architecture patterns
- **Enabling future growth** through extensible design

The system now provides a robust foundation for managing stock movements with proper approval workflows and comprehensive tracking.