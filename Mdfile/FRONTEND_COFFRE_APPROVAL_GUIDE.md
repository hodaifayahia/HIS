# Frontend Updates for Coffre Bank Approval System

## New Components Added

### 1. Updated CoffreTransactionModal.vue
**Location**: `resources/js/Components/Apps/coffre/Transactions/CoffreTransactionModal.vue`

**New Features**:
- **Bank Transfer Toggle**: Checkbox to enable bank transfer mode
- **Bank Selection**: Dropdown to select destination bank account
- **Approval Warning**: Shows notification when transaction will require approval
- **Dynamic Form**: Form fields change based on transfer type

**Usage**:
```vue
<CoffreTransactionModal
  :transaction="editingTransaction"
  :is-editing="false"
  :coffre-id="selectedCoffreId"
  @close="closeModal"
  @saved="onTransactionSaved"
/>
```

### 2. RequestTransactionApprovalService.js
**Location**: `resources/js/Components/Apps/services/Coffre/RequestTransactionApprovalService.js`

**Methods**:
- `getPendingApprovals()`: Fetch pending approval requests for current user
- `approve(id)`: Approve a transaction approval request
- `reject(id)`: Reject a transaction approval request

### 3. PendingApprovalsList.vue
**Location**: `resources/js/Components/Apps/coffre/Approvals/PendingApprovalsList.vue`

**Features**:
- Shows list of pending approval requests for current user
- Approve/Reject buttons with confirmation
- Real-time updates after actions
- Shows transaction details and requester info

### 4. TransactionStatusBadge.vue
**Location**: `resources/js/Components/Apps/coffre/Transactions/TransactionStatusBadge.vue`

**Features**:
- Color-coded status badges (Pending, Completed, Rejected)
- Shows approval information for pending transactions
- Bank transfer indicator

### 5. CoffreTransactionsDashboard.vue
**Location**: `resources/js/Pages/Apps/coffre/CoffreTransactionsDashboard.vue`

**Features**:
- Tabbed interface with Transactions and Approvals
- Complete transaction management interface
- Status filtering and search
- Integrated approval workflow

## Updated Services

### CoffreTransactionService.js
**Updated Endpoints**:
- `getBanks()`: Fetch available bank accounts
- `getTransactionTypes()`: Updated to use correct API endpoint
- `getCoffres()`: Updated to use correct API endpoint
- `getUsers()`: Updated to use correct API endpoint

## Navigation & Routing

### New Route Added
**Route**: `/coffre/transactions-dashboard`
**Component**: `CoffreTransactionsDashboard.vue`
**Access**: Admin, SuperAdmin, Manager, Reception roles

### Route Location
File: `resources/js/Routes/Coffre.js`

## How to Use the New Features

### 1. Creating a Bank Transfer
1. Navigate to `/coffre/transactions-dashboard`
2. Click "New Transaction"
3. Check "Transfer to Bank Account"
4. Select destination bank account
5. Enter amount and description
6. Submit - transaction will be created as "Pending"

### 2. Approving Transactions
1. Go to "Pending Approvals" tab
2. Review transaction details
3. Click "Approve" or "Reject"
4. Transaction status updates automatically

### 3. Viewing Transaction Status
- **Pending**: Orange badge, shows "Pending Approval"
- **Completed**: Green badge, shows "Completed"
- **Rejected**: Red badge, shows "Rejected"
- Bank transfers show blue "Bank Transfer" indicator

## User Permissions

### Who Can Approve?
Users must have a `TransferApproval` record with:
- `is_active = true`
- `maximum >= transaction.amount`

### Setup Approvers
```php
use App\Models\Configuration\TransferApproval;

TransferApproval::create([
    'user_id' => 1,
    'maximum' => 50000.00,
    'is_active' => true,
    'note' => 'Manager level approval'
]);
```

## API Integration

### New Endpoints Used
- `GET /api/request-transaction-approvals` - Get pending approvals
- `PATCH /api/request-transaction-approvals/{id}/approve` - Approve request
- `PATCH /api/request-transaction-approvals/{id}/reject` - Reject request
- `GET /api/banques` - Get available banks

### Enhanced Endpoints
- `POST /api/coffre-transactions` - Now supports bank transfers
- `GET /api/coffre-transactions/types/all` - Transaction types
- `GET /api/coffre-transactions/coffres/all` - Available coffres
- `GET /api/coffre-transactions/users/all` - Available users

## Testing the Frontend

### 1. Test Bank Transfer Creation
```javascript
// Example transaction data for bank transfer
const bankTransferData = {
  transaction_type: 'transfer_out',
  coffre_id: 1,
  destination_banque_id: 1,
  amount: 25000.00,
  description: 'Transfer to bank account'
};
```

### 2. Test Approval Flow
1. Create bank transfer (will be pending)
2. Check "Pending Approvals" tab
3. Approve/reject the transaction
4. Verify status updates

### 3. Test Status Display
- Transactions show correct status badges
- Bank transfers display bank indicator
- Pending transactions show approval count

## Error Handling

### Common Scenarios
- **Insufficient approval limit**: User not in candidate list
- **Already processed**: Request status changed
- **Missing bank**: Bank account doesn't exist
- **Network errors**: Graceful fallback with user messages

### User Feedback
- Success messages after successful operations
- Error alerts for failed operations
- Loading states during API calls
- Confirmation dialogs for destructive actions

## Styling Notes

The components use:
- **Tailwind CSS**: For utility-first styling
- **PrimeVue**: For UI components
- **Consistent color scheme**: Emerald for primary, Orange for warnings
- **Responsive design**: Works on mobile and desktop

## Next Steps

1. **Add notifications**: Real-time notifications for approval requests
2. **Bulk operations**: Approve/reject multiple transactions
3. **History tracking**: View approval history and logs
4. **Advanced filters**: Filter by amount range, date, approver
5. **Export functionality**: Export transaction reports

## File Structure Summary

```
resources/js/
├── Components/Apps/
│   ├── services/Coffre/
│   │   ├── CoffreTransactionService.js (updated)
│   │   └── RequestTransactionApprovalService.js (new)
│   └── coffre/
│       ├── Transactions/
│       │   ├── CoffreTransactionModal.vue (updated)
│       │   └── TransactionStatusBadge.vue (new)
│       └── Approvals/
│           └── PendingApprovalsList.vue (new)
├── Pages/Apps/coffre/
│   └── CoffreTransactionsDashboard.vue (new)
└── Routes/
    └── Coffre.js (updated)
```

This frontend implementation provides a complete user interface for the coffre to bank approval system, matching the backend functionality we implemented earlier.
