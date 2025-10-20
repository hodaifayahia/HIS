# Bon Commend Approval System

## Overview
This approval system allows organizations to control purchase order approvals based on amount thresholds. When a bon commend's total amount exceeds a defined threshold (default: 10,000 DZD), it must be approved by an authorized person before it can be confirmed.

## Database Tables

### `approval_persons`
Stores users who can approve bon commends with their maximum approval amounts.

**Columns:**
- `id`: Primary key
- `user_id`: Foreign key to users table
- `max_amount`: Maximum amount this person can approve (DZD)
- `title`: Job title or position (optional)
- `description`: Additional notes about approval authority
- `is_active`: Whether this approver is currently active
- `priority`: Lower number = higher authority (0 is highest)
- `timestamps`: created_at, updated_at

### `bon_commend_approvals`
Tracks approval requests and their status.

**Columns:**
- `id`: Primary key
- `bon_commend_id`: Foreign key to bon_commends table
- `approval_person_id`: Foreign key to approval_persons table
- `requested_by`: User who requested the approval
- `amount`: Amount being approved
- `status`: pending|approved|rejected
- `notes`: Notes from the requester
- `approval_notes`: Notes from the approver
- `approved_at`: When approved (if applicable)
- `rejected_at`: When rejected (if applicable)
- `timestamps`: created_at, updated_at

## Workflow

### 1. Create Bon Commend
User creates a bon commend with items. The system calculates the total amount.

### 2. Save Bon Commend
When saving:
- If total amount ≤ threshold (e.g., 10,000 DZD): Can be confirmed directly
- If total amount > threshold: Requires approval

### 3. Request Approval
When user clicks "Send for Approval":
- System finds appropriate approver based on amount
- Creates approval request with status "pending"
- Bon commend status changes to "pending_approval"
- Approver receives notification

### 4. Approval Process
Approver can:
- **Approve**: Bon commend status changes to "sent", can now be confirmed
- **Reject**: Bon commend status changes to "rejected", cannot be confirmed

### 5. After Approval
Once approved, the original user can:
- Confirm the bon commend
- Generate PDF
- Create bon reception

## API Endpoints

### Approval Persons Management
```
GET    /api/approval-persons                    - List all approval persons
POST   /api/approval-persons                    - Create approval person
GET    /api/approval-persons/{id}               - Get approval person
PUT    /api/approval-persons/{id}               - Update approval person
DELETE /api/approval-persons/{id}               - Delete approval person
POST   /api/approval-persons/{id}/toggle-active - Toggle active status
GET    /api/approval-persons/for-amount         - Find approvers for amount
```

### Approval Requests
```
GET    /api/bon-commend-approvals               - List all approval requests
GET    /api/bon-commend-approvals/my-pending    - My pending approvals
GET    /api/bon-commend-approvals/{id}          - Get approval details
POST   /api/bon-commends/{id}/request-approval  - Request approval
POST   /api/bon-commend-approvals/{id}/approve  - Approve request
POST   /api/bon-commend-approvals/{id}/reject   - Reject request
POST   /api/bon-commend-approvals/{id}/cancel   - Cancel request
GET    /api/bon-commend-approvals/statistics    - Get statistics
```

## Frontend Components

### ApprovalPersons.vue
Management interface for approval persons:
- List all approval persons with filters
- Add/Edit approval persons
- Set max amounts and priorities
- Toggle active/inactive status
- Delete approval persons

### PendingApprovals.vue
Approval workflow interface for approvers:
- View pending approval requests
- See bon commend details
- Approve or reject requests
- Add approval notes
- View statistics dashboard

### BonCommendList.vue (Updated)
Integrated approval workflow:
- "Send for Approval" button when amount > threshold
- Show approval status badges
- Disable confirm button when pending approval
- Show approval history

## Usage Examples

### Setting Up Approval Persons

1. **Finance Manager** - Can approve up to 50,000 DZD
   - user_id: 5
   - max_amount: 50000
   - title: "Finance Manager"
   - priority: 1

2. **Department Head** - Can approve up to 100,000 DZD
   - user_id: 3
   - max_amount: 100000
   - title: "Department Head"
   - priority: 0 (highest)

3. **Supervisor** - Can approve up to 20,000 DZD
   - user_id: 7
   - max_amount: 20000
   - title: "Purchasing Supervisor"
   - priority: 2

### Approval Flow Example

**Scenario**: Bon commend with 35,000 DZD total amount

1. User creates bon commend worth 35,000 DZD
2. User clicks "Send for Approval"
3. System finds Finance Manager (max: 50,000 DZD)
4. Approval request created with status "pending"
5. Finance Manager reviews and approves
6. Bon commend status changes to "sent"
7. User can now confirm the bon commend

## Models and Relationships

### ApprovalPerson
- `belongsTo` User
- `hasMany` BonCommendApproval
- Scopes: `active()`, `canApproveAmount()`
- Methods: `canApprove($amount)`

### BonCommendApproval
- `belongsTo` BonCommend
- `belongsTo` ApprovalPerson
- `belongsTo` User (requester)
- Scopes: `pending()`, `approved()`, `rejected()`
- Methods: `approve()`, `reject()`, `isPending()`, `isApproved()`, `isRejected()`

### BonCommend (Updated)
- `hasMany` BonCommendApproval (approvals)
- `hasOne` BonCommendApproval (currentApproval)
- Methods: `requiresApproval()`, `hasPendingApproval()`, `isApproved()`, `isRejected()`
- Attribute: `approval_status`

## Service Layer

### BonCommendApprovalService

**Methods:**
- `checkAndRequestApproval()`: Check if approval needed and create request
- `findApproverForAmount()`: Find best approver for amount
- `approveBonCommend()`: Approve a request
- `rejectBonCommend()`: Reject a request
- `getPendingApprovalsForUser()`: Get pending approvals for a user
- `canUserApproveBonCommend()`: Check if user can approve
- `getApprovalStatistics()`: Get approval metrics
- `cancelApprovalRequest()`: Cancel pending request

## Configuration

### Default Threshold
The default approval threshold is 10,000 DZD. This can be configured when requesting approval:

```php
$approvalService->checkAndRequestApproval($bonCommend, 15000); // Custom threshold
```

### Priority System
- Priority 0 = Highest authority
- Lower priority numbers are checked first
- If multiple approvers have same max_amount, priority determines selection

## Security Considerations

1. Only authorized users can be set as approval persons
2. Approvers can only approve within their max_amount
3. Once approved/rejected, approval cannot be changed
4. Only the requester can cancel pending approvals
5. Bon commend status prevents unauthorized confirmations

## Status Flow

```
draft → (amount > threshold) → pending_approval
pending_approval → (approved) → sent → confirmed
pending_approval → (rejected) → rejected
```

## Testing

### Create Approval Person
```bash
POST /api/approval-persons
{
  "user_id": 5,
  "max_amount": 50000,
  "title": "Finance Manager",
  "priority": 1,
  "is_active": true
}
```

### Request Approval
```bash
POST /api/bon-commends/123/request-approval
{
  "threshold_amount": 10000,
  "notes": "Urgent purchase for new equipment"
}
```

### Approve Request
```bash
POST /api/bon-commend-approvals/456/approve
{
  "approval_notes": "Approved - budget available"
}
```

## Future Enhancements

1. Multi-level approvals (require multiple approvals for very high amounts)
2. Email notifications for approval requests
3. Approval delegation (temporary approval authority transfer)
4. Approval workflows based on department or category
5. Approval history reports and analytics
