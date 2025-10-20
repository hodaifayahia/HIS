# Caisse Approval & Transfer Limits Consolidation

## Overview
Consolidated two separate pages into a single unified interface for managing both **Caisse Approval Permissions** and **Transfer Approval Limits** for users.

## Changes Made

### 1. **Frontend - Unified Component**
**File**: `resources/js/Pages/Apps/Configuration/Approvers/UserCaisseApprovalManagement.vue`

**Updates**:
- **Header**: Changed from "Transfer Approval Limits" to "Caisse Approval & Transfer Limits"
- **New Column**: Added "Approval Permission" column showing whether user has caisse approval permission
- **Enhanced Actions Column**: Now includes 4 action buttons per user:
  1. **Grant/Revoke Permission** - Toggle caisse approval permission (green "Grant" or gray "Revoke" button)
  2. **Edit Limit** - Edit the maximum transfer amount (blue pencil icon)
  3. **Toggle Limit Status** - Activate/Deactivate the transfer limit (yellow/purple eye icon)
  4. **Delete** - Remove the transfer limit (red trash icon)

**New Methods Added**:
- `grantCaissePermission(approval)` - Grants caisse approval permission to a user
- `confirmRevokeCaissePermission(approval)` - Shows confirmation dialog before revoking
- `revokeCaissePermission(approval)` - Revokes caisse approval permission from a user
- `processingApprovalItems` - Reactive ref to track approval operations in progress

### 2. **Navigation - Sidebar**
**File**: `resources/js/Pages/Dashborad/Sidebars/ConfigurationSidebar.vue`

**Updates**:
- **Removed**: "Caisse Permission" menu item
- **Updated**: "Transfer approval" renamed to "**Caisse Approval & Limits**"
- **Result**: Single menu entry under "Roles and Permission" section

### 3. **Routing - Configuration Routes**
**File**: `resources/js/Routes/configuration.js`

**Updates**:
- **Removed**: `Role-caisse-permission` route definition
- **Kept**: `transfer-approvals` route (now serves both purposes)
- **Deleted Component**: `ListUsersCanApproveCaisse.vue` is no longer used

### 4. **Backend - No Changes Required**
The existing backend APIs remain unchanged:
- `/api/user-caisse-approval` - Grant/revoke caisse approval permission
- `/api/transfer-approvals` - CRUD operations for transfer limits
- Both API endpoints work together in the unified interface

## Features

### Unified Management
Users can now manage both aspects of caisse approvals in one place:
1. **Permission Management**: Grant or revoke the ability to approve caisse transactions
2. **Limit Management**: Set maximum amounts users can approve for transfers
3. **User Selection**: Select ANY user from the entire system (not just those with caisse approval permission)

### User Workflow
1. Navigate to: **Configuration → Roles and Permission → Caisse Approval & Limits**
2. View all users with transfer approval limits
3. Click **"Add Limit"** to add a new user:
   - Select from **all users** in the system (dropdown with search)
   - Set maximum transfer amount
   - Add optional notes
4. For each existing user:
   - See if they have caisse approval permission (Tag: "Can Approve" / "Cannot Approve")
   - Grant or revoke caisse approval permission
   - Edit their maximum transfer limit
   - Activate/deactivate their limit
   - Delete their limit entry

### Visual Indicators
- **Green "Grant" Button**: User doesn't have caisse approval permission yet
- **Gray "Revoke" Button**: User already has caisse approval permission
- **Tag Color Coding**:
  - Green Tag (Can Approve) - User has caisse approval permission
  - Red Tag (Cannot Approve) - User lacks caisse approval permission

## Benefits
1. **Single Source of Truth**: One page for all caisse approval management
2. **Reduced Navigation**: No need to switch between two separate pages
3. **Better UX**: See both permission status and limits in one view
4. **Simplified Maintenance**: One component to maintain instead of two
5. **Complete User Access**: Can add transfer limits for ANY user in the system (not restricted to existing approvers)
6. **Flexible Permissions**: Grant caisse approval permission when adding limits or separately after

## Key Features Added (Latest Update)
### Select from All Users
- When adding a new transfer limit, you can now select from **all users** in the system
- Previously limited to only users who already had caisse approval permission
- Dropdown includes user name, email, and role for easy identification
- Users who already have limits are automatically filtered out
- Search/filter functionality for quick user lookup

### Automatic Permission Assignment
When you add a transfer limit for a user:
1. User is added to the transfer approvals table with the specified maximum amount
2. You can then grant them caisse approval permission using the "Grant" button
3. Or they can be given limits first and permissions later - fully flexible workflow

## Testing Checklist
- [ ] Navigate to "Caisse Approval & Limits" page
- [ ] Verify "Approval Permission" column displays correctly
- [ ] Click "Add Limit" button
- [ ] Verify dropdown shows ALL users from the system (not just approvers)
- [ ] Search for a user in the dropdown
- [ ] Select a user and set a maximum transfer amount
- [ ] Create a new transfer limit successfully
- [ ] Grant caisse approval permission to a user
- [ ] Revoke caisse approval permission from a user
- [ ] Edit a user's transfer limit
- [ ] Toggle a limit's active status
- [ ] Delete a transfer limit
- [ ] Verify old "Caisse Permission" menu item is removed
- [ ] Check that all operations complete successfully with proper toast notifications
- [ ] Verify users with existing limits don't appear in the "Add Limit" dropdown

## Files Modified
1. `resources/js/Pages/Apps/Configuration/Approvers/UserCaisseApprovalManagement.vue`
2. `resources/js/Pages/Dashborad/Sidebars/ConfigurationSidebar.vue`
3. `resources/js/Routes/configuration.js`

## Files No Longer Used
1. `resources/js/Components/Apps/Configuration/rolesAndPremissions/ListUsersCanApproveCaisse.vue` (can be safely deleted)

## API Endpoints Used
- `GET /api/users` - **NEW**: Fetch all users from the system for selection
- `GET /api/transfer-approvals` - Fetch transfer approval limits
- `POST /api/transfer-approvals` - Create new limit
- `PUT /api/transfer-approvals/{id}` - Update limit
- `DELETE /api/transfer-approvals/{id}` - Delete limit
- `POST /api/transfer-approvals/{id}/toggle-status` - Toggle limit active status
- `POST /api/user-caisse-approval` - Grant caisse approval permission
- `DELETE /api/user-caisse-approval/{userId}` - Revoke caisse approval permission
