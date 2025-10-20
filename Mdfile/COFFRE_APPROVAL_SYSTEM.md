# Coffre to Bank Transaction Approval System

## Overview

This system implements an approval workflow for bank transfers from coffre (cash box) accounts. When a transaction involves transferring money to a bank account, it requires approval from authorized users based on their configured maximum approval limits.

## Features

### 1. Automatic Approval Detection
- Transactions with `destination_banque_id` (bank transfers) automatically enter pending status
- Regular transactions (deposits, withdrawals, internal transfers) process immediately
- No code changes needed in frontend - the logic is handled in the service layer

### 2. Flexible Approval Limits
- Configure per-user maximum approval amounts via `TransferApproval` model
- System finds all users whose maximum is >= transaction amount
- Multiple users can be eligible for the same transaction

### 3. Status Management
- Transactions start as `'pending'` for bank transfers requiring approval
- Balance changes are deferred until approval is granted
- Status cannot be changed from `'pending'` until approval request is approved
- Completed transactions have status `'completed'`

## Database Tables

### coffre_transactions
- Added `status` column (default: 'pending')
- `destination_banque_id` triggers approval flow when present

### request_transaction_approvals
- `status`: 'pending', 'approved', 'rejected'
- `requested_by`: User who created the transaction
- `approved_by`: User who approved/rejected (nullable)
- `request_transaction_id`: Links to coffre_transaction
- `candidate_user_ids`: JSON array of eligible approver user IDs

### transfer_approvals
- `user_id`: User who can approve
- `maximum`: Maximum amount this user can approve
- `is_active`: Whether this approval configuration is active
- `note`: Optional notes about the approval limit

## API Endpoints

### Coffre Transactions
```
GET    /api/coffre-transactions              # List transactions
POST   /api/coffre-transactions              # Create transaction
GET    /api/coffre-transactions/{id}         # Show transaction
PUT    /api/coffre-transactions/{id}         # Update transaction
DELETE /api/coffre-transactions/{id}         # Delete transaction

GET    /api/coffre-transactions/types/all    # Get transaction types
GET    /api/coffre-transactions/coffres/all  # Get available coffres
GET    /api/coffre-transactions/users/all    # Get available users
```

### Approval Requests
```
GET    /api/request-transaction-approvals                    # Get pending approvals for current user
PATCH  /api/request-transaction-approvals/{id}/approve      # Approve a request
PATCH  /api/request-transaction-approvals/{id}/reject       # Reject a request
```

## Usage Examples

### 1. Creating a Bank Transfer (Requires Approval)
```json
POST /api/coffre-transactions
{
    "coffre_id": 1,
    "transaction_type": "transfer_out",
    "amount": 25000.00,
    "description": "Transfer to bank account",
    "destination_banque_id": 1
}
```

Response:
```json
{
    "success": true,
    "data": {
        "id": 123,
        "status": "pending",
        "amount": 25000.00,
        "approval_request": {
            "id": 45,
            "status": "pending",
            "candidate_user_ids": [2, 5, 8]
        }
    }
}
```

### 2. Creating a Regular Transaction (No Approval)
```json
POST /api/coffre-transactions
{
    "coffre_id": 1,
    "transaction_type": "deposit",
    "amount": 15000.00,
    "description": "Cash deposit"
}
```

Response:
```json
{
    "success": true,
    "data": {
        "id": 124,
        "status": "completed",
        "amount": 15000.00
    }
}
```

### 3. Getting Pending Approvals
```json
GET /api/request-transaction-approvals

Response:
{
    "success": true,
    "data": [
        {
            "id": 45,
            "status": "pending",
            "transaction": {
                "id": 123,
                "amount": 25000.00,
                "description": "Transfer to bank account",
                "coffre": { "name": "Main Coffre" }
            },
            "requested": { "name": "John Doe" }
        }
    ]
}
```

### 4. Approving a Request
```json
PATCH /api/request-transaction-approvals/45/approve

Response:
{
    "success": true,
    "message": "Transaction approved successfully.",
    "data": {
        "id": 123,
        "status": "completed",
        "amount": 25000.00
    }
}
```

## Setup Instructions

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Configure Approval Limits
Create `TransferApproval` records for users who can approve transactions:

```php
use App\Models\Configuration\TransferApproval;

// User 1 can approve up to 50,000
TransferApproval::create([
    'user_id' => 1,
    'maximum' => 50000.00,
    'is_active' => true,
    'note' => 'Manager level approval'
]);

// User 2 can approve up to 100,000
TransferApproval::create([
    'user_id' => 2,
    'maximum' => 100000.00,
    'is_active' => true,
    'note' => 'Director level approval'
]);
```

### 3. Ensure Bank Records Exist
Make sure you have `banques` records in your database for the foreign key constraint:

```php
use App\Models\Banque;

Banque::create([
    'name' => 'Main Bank Account',
    'account_number' => '123456789',
    // other fields...
]);
```

### 4. Test the System
```bash
php artisan test:coffre-approval
```

## Important Notes

### Balance Handling
- **Pending transactions**: Balance is NOT modified when transaction is created
- **Approved transactions**: Balance is modified when approval is granted
- **Regular transactions**: Balance is modified immediately

### Security
- Only users listed in `candidate_user_ids` can approve/reject a request
- Users can only see approval requests where they are candidates
- Status changes from 'pending' are blocked until approval

### Error Handling
- Attempting to change pending transaction status without approval throws exception
- Invalid approver attempts return 403 Forbidden
- Already processed requests return 400 Bad Request

This system provides a complete approval workflow for financial transactions while maintaining data integrity and security.
