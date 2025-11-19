# Custom Permission System Documentation

## Overview
The application now supports a fully flexible permission system that allows you to create, manage, and assign any custom permissions dynamically. This replaces the hardcoded permission approach and gives you complete control over user permissions.

## Key Features
- ✅ Create any custom permission dynamically
- ✅ Assign/revoke permissions to/from users
- ✅ Check user permissions programmatically
- ✅ List all permissions and their assignments
- ✅ Backward compatibility with existing caisse.approve permission

## API Endpoints

### Permission Management
```
GET    /api/configuration/permissions              # List all permissions
POST   /api/configuration/permissions              # Create new permission
GET    /api/configuration/permissions/{id}         # Get permission details
PUT    /api/configuration/permissions/{id}         # Update permission
DELETE /api/configuration/permissions/{id}         # Delete permission
```

### Permission Assignment
```
POST   /api/configuration/permissions/assign        # Assign permission to user
POST   /api/configuration/permissions/revoke        # Revoke permission from user
```

### User Permission Queries
```
GET    /api/configuration/permissions/user/{userId} # Get user's permissions
POST   /api/configuration/permissions/check         # Check if user has permission
GET    /api/configuration/permissions/users-with-permission # Get users with specific permission
```

### Caisse Approval (Backward Compatibility)
```
GET    /api/configuration/caisse-approval/users      # List users with caisse approval status
POST   /api/configuration/caisse-approval/grant      # Grant caisse approval to user
DELETE /api/configuration/caisse-approval/revoke/{userId} # Revoke caisse approval
GET    /api/configuration/caisse-approval/check-auth # Check current user's approval status
GET    /api/configuration/caisse-approval/approvers  # Get all caisse approvers
POST   /api/configuration/caisse-approval/set-permission-name # Change permission name
GET    /api/configuration/caisse-approval/permission-name # Get current permission name
```

## Usage Examples

### 1. Create a Custom Permission
```javascript
// Create a new permission
const response = await fetch('/api/configuration/permissions', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        name: 'reports.advanced',
        description: 'Can access advanced reports'
    })
});
```

### 2. Assign Permission to User
```javascript
// Assign permission to user
const response = await fetch('/api/configuration/permissions/assign', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        user_id: 123,
        permission_name: 'reports.advanced'
    })
});
```

### 3. Check User Permission
```javascript
// Check if user has permission
const response = await fetch('/api/configuration/permissions/check', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        user_id: 123,
        permission_name: 'reports.advanced'
    })
});

const result = await response.json();
console.log('Has permission:', result.has_permission);
```

### 4. Get User's Permissions
```javascript
// Get all permissions for a user
const response = await fetch('/api/configuration/permissions/user/123');
const data = await response.json();
console.log('User permissions:', data.permissions);
```

## Predefined Permissions
The system comes with these predefined permissions:
- `caisse.approve` - Can approve caisse transactions
- `caisse.view` - Can view caisse transactions
- `caisse.create` - Can create caisse transactions
- `caisse.edit` - Can edit caisse transactions
- `caisse.delete` - Can delete caisse transactions
- `users.manage` - Can manage users
- `roles.manage` - Can manage roles
- `permissions.manage` - Can manage permissions
- `appointments.manage` - Can manage appointments
- `patients.manage` - Can manage patients
- `reports.view` - Can view reports
- `settings.manage` - Can manage system settings

## Dynamic Permission Creation
You can create any permission you need:
- `inventory.manage`
- `billing.admin`
- `notifications.send`
- `exports.create`
- `imports.process`
- Any custom permission name you want

## Migration from Hardcoded Permissions
The old hardcoded `caisse.approve` permission is now configurable. You can:
1. Change the permission name using `/api/configuration/caisse-approval/set-permission-name`
2. Use any permission name for caisse approval
3. Maintain backward compatibility

## Error Handling
All endpoints return proper JSON responses with:
- Success status and messages
- Error details when operations fail
- Validation errors for invalid requests

## Security Notes
- All permission operations require proper authentication
- Users can only manage permissions they have access to
- Permission names should follow a consistent naming convention (e.g., `module.action`)
