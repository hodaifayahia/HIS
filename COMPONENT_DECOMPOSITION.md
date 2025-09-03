# CaissePatientPayment Component Decomposition

This document describes the decomposition of the monolithic `CaissePatientPayment.vue` component into smaller, focused components.

## Component Structure

### Main Component
- **`CaissePatientPayment.vue`** - Main orchestrator component that manages state and coordinates between child components

### Child Components

#### 1. `PaymentHeader.vue`
**Purpose**: Displays fiche information and total outstanding amount
**Props**:
- `ficheId` - The fiche ID
- `patientName` - Patient name 
- `totalOutstanding` - Total amount due

#### 2. `PrestationsSummary.vue`
**Purpose**: Summary table showing all prestations with pricing info
**Props**:
- `summaryItems` - Array of prestation summary data
**Events**:
- `scroll-to-item` - Scroll to specific prestation card

#### 3. `TransactionsOverview.vue`
**Purpose**: Displays transaction history with filtering capabilities
**Props**:
- `filteredTransactions` - Filtered transaction list
- `transactionTypeOptions` - Filter dropdown options
- `searchQuery` - Search input value
- `selectedType` - Selected transaction type filter
- `selectedDateRange` - Selected date range filter
**Events**:
- `update:search-query`, `update:selected-type`, `update:selected-date-range` - Filter updates

#### 4. `GlobalPayment.vue`
**Purpose**: Global payment form section
**Props**:
- `amount` - Payment amount
- `method` - Payment method
- `maxAmount` - Maximum allowed amount
**Events**:
- `update:amount`, `update:method` - Input updates
- `pay-global` - Submit global payment

#### 5. `PrestationCard.vue`
**Purpose**: Individual prestation payment card with transaction history
**Props**:
- `item` - Prestation item data
- `payAmount`, `payMethod` - Payment form values
- `transactionsVisible` - Whether transactions are expanded
- `userCanRefund` - User permission flag
- `finalPrice`, `paidAmount`, `remaining` - Calculated amounts
**Events**:
- `update:pay-amount`, `update:pay-method` - Payment form updates
- `pay-item` - Submit item payment
- `toggle-transactions` - Toggle transaction visibility
- `open-update`, `open-refund` - Modal triggers

#### 6. `PaymentModals.vue`
**Purpose**: All modal dialogs (overpayment, refund, update)
**Props**:
- Modal visibility flags
- Data objects for each modal
- Processing state flags
**Events**:
- Modal action events and data updates

## Composables

### `useCurrencyFormatter.js`
- `formatCurrency(amount)` - Formats numbers as currency

### `useTransactionHelpers.js`
- `getTransactionTypeText(type)` - Human-readable transaction type
- `getTransactionTypeClass(type)` - CSS classes for transaction types

### `usePaymentHelpers.js`
- `getItemFinalPrice(item)` - Get effective final price
- `getItemPaidAmount(item)` - Get effective paid amount  
- `getItemRemainingAmount(item)` - Calculate remaining amount
- `mapPaymentMethod(method)` - Map payment method strings

## Benefits

1. **Separation of Concerns**: Each component has a single responsibility
2. **Reusability**: Components can be reused in other parts of the application
3. **Maintainability**: Easier to locate and fix issues
4. **Testability**: Smaller components are easier to unit test
5. **Code Organization**: Logic is organized by feature/domain
6. **Performance**: Smaller components can be optimized individually

## Communication

Components communicate via:
- **Props**: Parent to child data flow
- **Events**: Child to parent communication
- **Composables**: Shared logic and utilities

## File Structure

```
resources/js/
├── Pages/Apps/caisse/
│   └── CaissePatientPayment.vue (main component)
├── Components/Caisse/
│   ├── PaymentHeader.vue
│   ├── PrestationsSummary.vue
│   ├── TransactionsOverview.vue
│   ├── GlobalPayment.vue
│   ├── PrestationCard.vue
│   └── PaymentModals.vue
└── composables/
    ├── useCurrencyFormatter.js
    ├── useTransactionHelpers.js
    └── usePaymentHelpers.js
```

## Migration Notes

- All existing functionality is preserved
- Props/events maintain the same data flow
- State management remains in the main component
- No breaking changes to external APIs
- Package prestations display is maintained in `PrestationCard.vue`
