# Calendar Components

This directory contains the decomposed components from the main Calendar.vue component, making the codebase more modular and maintainable.

## Components Overview

### 1. CalendarHeader.vue
**Purpose**: Displays the calendar header with month navigation

**Props**:
- `currentDate` (Date, required): The current date being displayed

**Events**:
- `@previous-month`: Emitted when user clicks previous month button
- `@next-month`: Emitted when user clicks next month button

**Features**:
- Month/year display
- Previous/next month navigation buttons
- Responsive design

### 2. DisplayModeToggle.vue
**Purpose**: Toggle buttons for switching between working dates and holidays view

**Props**:
- `displayMode` (String, required): Current display mode ('working' or 'holidays')

**Events**:
- `@update:displayMode`: Emitted when display mode changes (supports v-model)

**Features**:
- Toggle between working dates and holidays
- Active state styling
- Responsive button layout

### 3. CalendarGrid.vue
**Purpose**: Main calendar grid displaying days and doctor schedules

**Props**:
- `currentDate` (Date, required): Current month being displayed
- `selectedDate` (Date, required): Currently selected date
- `doctors` (Array): List of doctors with their working dates
- `displayMode` (String): Display mode for filtering doctors

**Events**:
- `@select-date`: Emitted when a date is clicked
- `@filter-by-doctor`: Emitted when a doctor badge is clicked

**Features**:
- 7-day week grid layout
- Doctor working dates display
- Holiday/exclusion dates
- Date selection
- Responsive design
- Doctor filtering by click

### 4. AppointmentsTable.vue
**Purpose**: DataTable for displaying and managing appointments

**Props**:
- `appointments` (Array): All appointments
- `filteredAppointments` (Array): Filtered appointments to display
- `selectedAppointments` (Array): Currently selected appointments (supports v-model)
- `loading` (Boolean): Loading state
- `role` (String): User role for conditional column display

**Events**:
- `@update:selectedAppointments`: Emitted when selection changes
- `@transfer-appointments`: Emitted when transfer button is clicked
- `@export-pdf`: Emitted when export PDF button is clicked

**Features**:
- Multi-select appointments
- Sortable columns
- Pagination
- Status badges with colors
- Role-based column visibility
- Action buttons (Transfer, Export PDF)

### 5. TransferModal.vue
**Purpose**: Modal dialog for transferring appointments to different doctors/dates

**Props**:
- `visible` (Boolean): Modal visibility (supports v-model)
- `selectedAppointments` (Array): Appointments to transfer
- `doctors` (Array): Available doctors for transfer
- `transferDate` (Date): Selected transfer date (supports v-model)
- `transferDoctor` (Object): Selected transfer doctor (supports v-model)
- `loading` (Boolean): Transfer operation loading state

**Events**:
- `@update:visible`: Emitted when modal visibility changes
- `@update:transferDate`: Emitted when transfer date changes
- `@update:transferDoctor`: Emitted when transfer doctor changes
- `@confirm-transfer`: Emitted when transfer is confirmed
- `@cancel-transfer`: Emitted when transfer is cancelled

**Features**:
- Doctor selection dropdown with color indicators
- Date picker for new appointment date
- Selected appointments preview table
- Form validation
- Loading states

## Usage Example

```vue
<template>
  <div class="calendar-container p-4">
    <Toast />
    
    <!-- Calendar Header -->
    <CalendarHeader 
      :currentDate="currentDate"
      @previous-month="previousMonth"
      @next-month="nextMonth" />

    <!-- Display Mode Toggle -->
    <DisplayModeToggle 
      v-model:displayMode="displayMode" />

    <!-- Calendar Grid -->
    <CalendarGrid 
      :currentDate="currentDate"
      :selectedDate="selectedDate"
      :doctors="doctors"
      :displayMode="displayMode"
      @select-date="selectDate"
      @filter-by-doctor="filterByDoctor" />

    <!-- Appointments Table -->
    <AppointmentsTable 
      :appointments="appointments"
      :filteredAppointments="filteredAppointments"
      v-model:selectedAppointments="selectedAppointments"
      :loading="loading"
      :role="role"
      @transfer-appointments="openTransferModal"
      @export-pdf="exportPDF" />

    <!-- Transfer Modal -->
    <TransferModal 
      v-model:visible="showTransferModal"
      :selectedAppointments="selectedAppointments"
      :doctors="doctors"
      v-model:transferDate="transferDate"
      v-model:transferDoctor="transferDoctor"
      :loading="transferLoading"
      @confirm-transfer="confirmTransfer"
      @cancel-transfer="closeTransferModal" />
  </div>
</template>
```

## Benefits of Decomposition

1. **Modularity**: Each component has a single responsibility
2. **Reusability**: Components can be reused in other parts of the application
3. **Maintainability**: Easier to maintain and debug individual components
4. **Testing**: Each component can be tested independently
5. **Performance**: Better tree-shaking and code splitting opportunities
6. **Collaboration**: Multiple developers can work on different components simultaneously

## Styling

Each component includes its own scoped styles using Tailwind CSS with the `tw-` prefix for consistency. The main Calendar component now only contains minimal styling for the overall layout.

## Dependencies

- Vue 3 Composition API
- PrimeVue components (DataTable, Dialog, Dropdown, Calendar, Button, etc.)
- Tailwind CSS for styling
- Axios for API calls (in main component)