# Inventory Audit System with Participants - Complete Implementation

## Overview
A complete system for creating inventory audits with multiple participants. Each participant can have different permissions:
- **is_participant**: Can actively participate in the audit
- **is_able_to_see**: Can view the audit (read-only access)

## Files Created

### 1. Service Layer
**`app/Services/Inventory/InventoryAuditService.php`**
- `paginate()` - Get paginated audits with filters
- `create()` - Create audit with participants in a transaction
- `update()` - Update audit and sync participants
- `syncParticipants()` - Sync all participants (delete old, create new)
- `addParticipant()` - Add or update a single participant
- `removeParticipant()` - Remove a participant
- `start()` - Start an audit
- `complete()` - Complete an audit
- `delete()` - Delete audit and all participants
- `getUserAudits()` - Get audits for a specific user
- `isParticipant()` - Check if user is participant
- `canView()` - Check if user can view

### 2. Request Validation
**`app/Http/Requests/Inventory/StoreInventoryAuditRequest.php`**
- Validates audit creation with participants array
- Each participant must have valid user_id

**`app/Http/Requests/Inventory/UpdateInventoryAuditRequest.php`**
- Validates audit updates
- Optional participants array for syncing

### 3. API Resources
**`app/Http/Resources/Inventory/InventoryAuditResource.php`**
- Formats audit data for API responses
- Includes creator, participants, counts

**`app/Http/Resources/Inventory/InventoryAuditParticipantResource.php`**
- Formats participant data with user info

### 4. Controller
**`app/Http/Controllers/Inventory/InventoryAuditController.php`**
Complete REST API with additional methods:
- `index()` - List all audits with filters
- `store()` - Create audit with participants
- `show()` - Show single audit
- `update()` - Update audit
- `destroy()` - Delete audit
- `addParticipant()` - Add participant to audit
- `removeParticipant()` - Remove participant
- `start()` - Start audit
- `complete()` - Complete audit
- `myAudits()` - Get current user's audits

### 5. Models Updated
**`app/Models/Inventory/InventoryAudit.php`**
- Added `participants()` relationship
- Added `activeParticipants()` - only is_participant=true
- Added `viewers()` - only is_able_to_see=true

**`app/Models/Inventory/InventoryAuditsParticipante.php`**
- Added proper type hints
- Cleaned up relationships

## API Endpoints

### Basic CRUD
```http
GET    /api/inventory-audits                    # List all audits
POST   /api/inventory-audits                    # Create new audit
GET    /api/inventory-audits/{id}               # Show audit
PUT    /api/inventory-audits/{id}               # Update audit
DELETE /api/inventory-audits/{id}               # Delete audit
```

### Participant Management
```http
POST   /api/inventory-audits/{id}/participants  # Add participant
DELETE /api/inventory-audits/{id}/participants  # Remove participant
```

### Audit Actions
```http
POST   /api/inventory-audits/{id}/start         # Start audit
POST   /api/inventory-audits/{id}/complete      # Complete audit
GET    /api/inventory-audits/my-audits          # Get my audits
```

## Usage Examples

### 1. Create Audit with Multiple Participants

```http
POST /api/inventory-audits
Content-Type: application/json

{
  "name": "Monthly Stock Audit - January 2025",
  "description": "Complete inventory check for all warehouses",
  "scheduled_at": "2025-01-15 09:00:00",
  "participants": [
    {
      "user_id": 5,
      "is_participant": true,
      "is_able_to_see": true
    },
    {
      "user_id": 8,
      "is_participant": true,
      "is_able_to_see": true
    },
    {
      "user_id": 12,
      "is_participant": false,
      "is_able_to_see": true
    }
  ]
}
```

**Response:**
```json
{
  "message": "Inventory audit created successfully",
  "data": {
    "id": 1,
    "name": "Monthly Stock Audit - January 2025",
    "description": "Complete inventory check for all warehouses",
    "status": "draft",
    "creator": {
      "id": 1,
      "name": "Admin User",
      "email": "admin@example.com"
    },
    "participants": [
      {
        "id": 1,
        "user_id": 5,
        "user": {
          "id": 5,
          "name": "John Doe",
          "email": "john@example.com"
        },
        "is_participant": true,
        "is_able_to_see": true
      },
      {
        "id": 2,
        "user_id": 8,
        "user": {
          "id": 8,
          "name": "Jane Smith",
          "email": "jane@example.com"
        },
        "is_participant": true,
        "is_able_to_see": true
      },
      {
        "id": 3,
        "user_id": 12,
        "user": {
          "id": 12,
          "name": "Manager",
          "email": "manager@example.com"
        },
        "is_participant": false,
        "is_able_to_see": true
      }
    ],
    "participants_count": 3,
    "scheduled_at": "2025-01-15T09:00:00.000000Z",
    "created_at": "2025-01-29T10:00:00.000000Z"
  }
}
```

### 2. Update Audit and Change Participants

```http
PUT /api/inventory-audits/1
Content-Type: application/json

{
  "name": "Monthly Stock Audit - January 2025 (Updated)",
  "participants": [
    {
      "user_id": 5,
      "is_participant": true,
      "is_able_to_see": true
    },
    {
      "user_id": 10,
      "is_participant": true,
      "is_able_to_see": true
    }
  ]
}
```

This will:
1. Update the audit name
2. Remove old participants
3. Add new participants

### 3. Add a Single Participant

```http
POST /api/inventory-audits/1/participants
Content-Type: application/json

{
  "user_id": 15,
  "is_participant": false,
  "is_able_to_see": true
}
```

### 4. Remove a Participant

```http
DELETE /api/inventory-audits/1/participants
Content-Type: application/json

{
  "user_id": 12
}
```

### 5. Start an Audit

```http
POST /api/inventory-audits/1/start
```

**Response:**
```json
{
  "message": "Audit started successfully",
  "data": {
    "id": 1,
    "status": "in_progress",
    "started_at": "2025-01-29T10:30:00.000000Z"
  }
}
```

### 6. Complete an Audit

```http
POST /api/inventory-audits/1/complete
```

### 7. Get My Audits (Current User)

```http
GET /api/inventory-audits/my-audits?per_page=20
```

Returns all audits where the current user is either:
- A participant (`is_participant = true`)
- A viewer (`is_able_to_see = true`)

### 8. Filter Audits

```http
GET /api/inventory-audits?status=in_progress&search=january&per_page=15
```

**Query Parameters:**
- `status` - Filter by: draft, in_progress, completed, cancelled
- `created_by` - Filter by creator user ID
- `search` - Search in audit name
- `per_page` - Items per page (default: 15)

## Participant Permissions

### is_participant = true, is_able_to_see = true
- Can actively participate in the audit
- Can view all audit details
- Can submit audit data
- Full access

### is_participant = false, is_able_to_see = true
- Cannot participate
- Can view all audit details
- Read-only access (like a supervisor/manager)

### is_participant = true, is_able_to_see = false
- Can participate but shouldn't be able to see (edge case - usually both should be true if participating)

## Database Tables

### inventory_audits
```sql
- id
- name
- description
- created_by (user_id)
- status (draft, in_progress, completed, cancelled)
- scheduled_at
- started_at
- completed_at
- created_at
- updated_at
```

### inventory_audits_participantes
```sql
- id
- inventory_audit_id
- user_id
- is_participant (boolean)
- is_able_to_see (boolean)
- created_at
- updated_at
```

## Frontend Integration

### Get All Audits
```javascript
const response = await axios.get('/api/inventory-audits', {
  params: {
    status: 'in_progress',
    per_page: 20
  }
});
const audits = response.data.data;
```

### Create Audit with Participants
```javascript
const response = await axios.post('/api/inventory-audits', {
  name: 'Quarterly Audit Q1',
  description: 'Full inventory audit',
  scheduled_at: '2025-02-01 08:00:00',
  participants: [
    { user_id: 5, is_participant: true, is_able_to_see: true },
    { user_id: 8, is_participant: true, is_able_to_see: true },
    { user_id: 12, is_participant: false, is_able_to_see: true }
  ]
});
```

### Add Participant
```javascript
await axios.post(`/api/inventory-audits/${auditId}/participants`, {
  user_id: 15,
  is_participant: true,
  is_able_to_see: true
});
```

### Start Audit
```javascript
await axios.post(`/api/inventory-audits/${auditId}/start`);
```

## Benefits

✅ **Transaction Safety** - All operations use database transactions
✅ **Flexible Permissions** - Each participant can have different access levels
✅ **Batch Operations** - Create audit with all participants at once
✅ **Individual Management** - Add/remove participants individually
✅ **User-Centric** - Get audits relevant to specific user
✅ **Full REST API** - Complete CRUD operations
✅ **Validation** - Request validation for all inputs
✅ **Resources** - Clean API responses with relationships
✅ **Service Layer** - Business logic separated from controller

## Testing Checklist

- [ ] Create audit without participants
- [ ] Create audit with multiple participants
- [ ] Update audit and sync participants
- [ ] Add individual participant
- [ ] Remove participant
- [ ] Start audit
- [ ] Complete audit
- [ ] Get user's audits
- [ ] Filter audits by status
- [ ] Search audits by name
- [ ] Delete audit (should delete participants too)
- [ ] Verify is_participant flag works
- [ ] Verify is_able_to_see flag works

## Next Steps

1. Create frontend UI for managing audits
2. Add audit items (products to audit)
3. Add audit results recording
4. Add audit reports generation
5. Add notifications for participants
6. Add audit templates

**Status:** ✅ Complete and Ready to Use
