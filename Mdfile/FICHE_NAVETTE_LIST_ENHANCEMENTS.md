# Fiche Navette List Enhancements

## Overview
Enhanced the fiche navette list view to display emergency doctor information and remaining payment amounts for better tracking and management.

## Changes Made

### 1. Emergency Doctor Column

**Location**: `resources/js/Pages/Apps/Nursing/FicheNavatte/ficheNavetteList.vue`

**New Column Added**: Emergency Doctor display showing:
- Doctor name from `emergency_doctor.name` (via `doctor->user->name`)
- Doctor specialization from `emergency_doctor.specialization`
- Visual indicators:
  - Red warning icon (⚠️) for emergency cases with assigned doctor
  - Orange warning icon for emergency cases without assigned doctor
  - Dash (-) for non-emergency cases

**Implementation**:
```vue
<Column field="is_emergency" header="Emergency Doctor" sortable>
  <template #body="{ data }">
    <div v-if="data.is_emergency && data.emergency_doctor" class="tw-flex tw-items-center tw-gap-2">
      <i class="pi pi-exclamation-triangle tw-text-red-500"></i>
      <div class="tw-flex tw-flex-col">
        <span class="tw-font-medium tw-text-gray-800 tw-text-sm">{{ data.emergency_doctor.name }}</span>
        <small class="tw-text-gray-500 tw-text-xs">{{ data.emergency_doctor.specialization }}</small>
      </div>
    </div>
    <div v-else-if="data.is_emergency" class="tw-flex tw-items-center tw-gap-2 tw-text-orange-600">
      <i class="pi pi-exclamation-triangle"></i>
      <span class="tw-text-sm">No doctor assigned</span>
    </div>
    <span v-else class="tw-text-gray-400 tw-text-sm">-</span>
  </template>
</Column>
```

### 2. Remaining Amount Column

**New Column Added**: Remaining Amount display showing:
- Remaining payment amount from `remaining_amount` field
- Payment status indicator (Paid/Partial/Unpaid)
- Color-coded amounts:
  - **Red**: Outstanding balance > 0
  - **Green**: Fully paid (balance = 0)
- Payment status badges:
  - **Red**: Pending/Unpaid
  - **Yellow**: Partial payment
  - **Green**: Fully paid

**Implementation**:
```vue
<Column field="remaining_amount" header="Remaining Amount" sortable>
  <template #body="{ data }">
    <div class="tw-text-right">
      <div class="tw-flex tw-flex-col tw-items-end">
        <span 
          class="tw-font-bold tw-text-base"
          :class="{
            'tw-text-red-600': (data.remaining_amount || 0) > 0,
            'tw-text-green-600': (data.remaining_amount || 0) === 0
          }"
        >
          {{ formatCurrency(data.remaining_amount || 0) }}
        </span>
        <small v-if="data.payment_status" 
          class="tw-text-xs tw-mt-1"
          :class="{
            'tw-text-red-500': data.payment_status === 'pending',
            'tw-text-yellow-600': data.payment_status === 'partial',
            'tw-text-green-600': data.payment_status === 'paid'
          }"
        >
          {{ data.payment_status === 'paid' ? 'Paid' : data.payment_status === 'partial' ? 'Partial' : 'Unpaid' }}
        </small>
      </div>
    </div>
  </template>
</Column>
```

## Data Requirements

### Backend Requirements

The API response must include:

1. **Emergency Doctor Information**:
   ```json
   {
     "is_emergency": true,
     "emergency_doctor_id": 123,
     "emergency_doctor": {
       "id": 123,
       "name": "Dr. Ahmed Hassan",
       "specialization": "Emergency Medicine"
     }
   }
   ```

2. **Payment Information**:
   ```json
   {
     "total_amount": 5000,
     "paid_amount": 2000,
     "remaining_amount": 3000,
     "payment_status": "partial"
   }
   ```

### Already Implemented in Backend

✅ **FicheNavetteResource.php** already includes:
- `emergency_doctor_id`
- `emergency_doctor` object with `name` and `specialization`
- `remaining_amount` calculated from items
- `payment_status` (paid/partial/pending)

✅ **FicheNavetteService.php** already loads:
- `emergencyDoctor.user` relationship
- `emergencyDoctor.specialization` relationship
- Items with payment calculations

## Visual Layout

The table now displays columns in this order:

1. **ID** - Badge with fiche ID
2. **Patient** - Avatar + Name + ID
3. **Creator** - Avatar + Name
4. **Creation Date** - Date + Time
5. **Status** - Status tag
6. **Emergency Doctor** ⭐ NEW
   - Doctor name + specialization (if emergency)
   - Warning icon for emergencies
7. **Services** - Count badge
8. **Total Amount** - Currency formatted
9. **Remaining Amount** ⭐ NEW
   - Amount with color coding
   - Payment status badge
10. **Actions** - View/Edit buttons

## Benefits

1. **Emergency Visibility**: Quick identification of emergency cases and assigned doctors
2. **Payment Tracking**: Immediate visibility of outstanding balances
3. **Better Management**: Staff can prioritize based on payment status
4. **Color Coding**: Visual cues for urgent attention items
5. **Complete Information**: All critical data in one view

## Testing

### Test Cases

1. **Emergency with Doctor**:
   - Verify red warning icon appears
   - Check doctor name displays correctly
   - Confirm specialization shows below name

2. **Emergency without Doctor**:
   - Verify orange warning icon appears
   - Check "No doctor assigned" message displays

3. **Non-Emergency**:
   - Verify dash (-) appears in doctor column

4. **Payment Status**:
   - Fully paid: Green amount, "Paid" status
   - Partial: Red amount, Yellow "Partial" status
   - Unpaid: Red amount, Red "Unpaid" status

5. **Sorting**:
   - Test sorting by Emergency Doctor column
   - Test sorting by Remaining Amount column

## Future Enhancements

Potential improvements:
1. Filter by emergency status
2. Filter by payment status
3. Quick assign doctor button for emergencies
4. Payment collection directly from list
5. Export filtered results
6. Bulk actions for emergency assignments
