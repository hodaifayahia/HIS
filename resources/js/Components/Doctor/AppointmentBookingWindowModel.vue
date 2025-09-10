<script setup>
import { ref, computed, watch, onMounted, nextTick, onBeforeUnmount } from 'vue';
import 'font-awesome/css/font-awesome.min.css';

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => [],
  },
  isEditMode: {
    type: Boolean,
    default: false,
  },
  appointmentBookingWindow: {
    type: Array,
    default: () => [],
  },
  minYear: {
    type: Number,
    default: () => new Date().getFullYear(),
  },
  maxYears: {
    type: Number,
    default: 15, // Number of years from minYear to show (e.g., 2025, 2026, 2027)
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  required: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:modelValue', 'change', 'validation-change']);

// Generate years based on props
const currentYear = new Date().getFullYear();
const years = computed(() =>
  Array.from({ length: props.maxYears }, (_, i) => Math.max(props.minYear, currentYear) + i)
);

const selectedYear = ref(Math.max(props.minYear, currentYear));

const allMonths = [
  { name: 'January', value: 1, short: 'Jan' },
  { name: 'February', value: 2, short: 'Feb' },
  { name: 'March', value: 3, short: 'Mar' },
  { name: 'April', value: 4, short: 'Apr' },
  { name: 'May', value: 5, short: 'May' },
  { name: 'June', value: 6, short: 'Jun' },
  { name: 'July', value: 7, short: 'Jul' },
  { name: 'August', value: 8, short: 'Aug' },
  { name: 'September', value: 9, short: 'Sep' },
  { name: 'October', value: 10, short: 'Oct' },
  { name: 'November', value: 11, short: 'Nov' },
  { name: 'December', value: 12, short: 'Dec' },
];

const monthsForSelectedYear = computed(() => {
  const selectedSet = new Set(
    selectedMonths.value
      .filter(m => m.year === selectedYear.value && m.is_available)
      .map(m => m.value)
  );

  return allMonths.map(month => ({
    ...month,
    is_available: selectedSet.has(month.value),
    isPastMonth: isMonthInPast(month.value, selectedYear.value),
  }));
});

const isDropdownOpen = ref(false);
const selectedMonths = ref([]);
const dropdownRef = ref(null);

const validationErrors = ref({
  selectedMonths: '',
});

const isMonthInPast = (month, year) => {
  const today = new Date();
  const currentMonth = today.getMonth() + 1;
  const currentYear = today.getFullYear();

  return year < currentYear || (year === currentYear && month < currentMonth);
};

const toggleDropdown = () => {
  if (props.disabled) return;
  isDropdownOpen.value = !isDropdownOpen.value;

  if (isDropdownOpen.value) {
    nextTick(() => {
      const firstAvailableMonth = dropdownRef.value?.querySelector('.month-item:not(.disabled)');
      firstAvailableMonth?.focus();
    });
  }
};

const closeDropdown = (event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    isDropdownOpen.value = false;
  }
};

const toggleMonthSelection = (month) => {
  if (props.disabled || month.isPastMonth) return;

  const existingIndex = selectedMonths.value.findIndex(
    (m) => m.value === month.value && m.year === selectedYear.value
  );

  if (existingIndex !== -1) {
    selectedMonths.value.splice(existingIndex, 1);
  } else {
    const newMonth = {
      name: month.name,
      short: month.short,
      value: month.value,
      year: selectedYear.value,
      is_available: true,
    };

    const insertIndex = selectedMonths.value.findIndex(m =>
      m.year > selectedYear.value ||
      (m.year === selectedYear.value && m.value > month.value)
    );

    if (insertIndex === -1) {
      selectedMonths.value.push(newMonth);
    } else {
      selectedMonths.value.splice(insertIndex, 0, newMonth);
    }
  }

  emitChanges();
  clearValidationError();
};

const removeMonth = (index) => {
  if (props.disabled) return;
  selectedMonths.value.splice(index, 1);
  emitChanges();
};

const selectAllMonthsForYear = () => {
  if (props.disabled) return;

  const availableMonthsToAdd = monthsForSelectedYear.value.filter(
    m => !m.isPastMonth && !m.is_available
  );

  availableMonthsToAdd.forEach(month => {
    selectedMonths.value.push({
      name: month.name,
      short: month.short,
      value: month.value,
      year: selectedYear.value,
      is_available: true,
    });
  });

  sortSelectedMonths();
  emitChanges();
  clearValidationError();
};

const clearAllMonthsForYear = () => {
  if (props.disabled) return;
  selectedMonths.value = selectedMonths.value.filter(m => m.year !== selectedYear.value);
  emitChanges();
};

const sortSelectedMonths = () => {
  selectedMonths.value.sort((a, b) => {
    if (a.year !== b.year) return a.year - b.year;
    return a.value - b.value;
  });
};

const validateMonths = () => {
  validationErrors.value.selectedMonths = '';
  if (props.required && selectedMonths.value.length === 0) {
    validationErrors.value.selectedMonths = 'Please select at least one month.';
    return false;
  }
  return true;
};

const clearValidationError = () => {
  validationErrors.value.selectedMonths = '';
};

const emitChanges = () => {
  const isValid = validateMonths();
  emit('update:modelValue', [...selectedMonths.value]);
  emit('change', [...selectedMonths.value]);
  emit('validation-change', isValid);
};

const hasSelectedMonths = computed(() => selectedMonths.value.length > 0);

const selectedMonthsDisplay = computed(() => {
  if (!hasSelectedMonths.value) return 'Select Months';

  const count = selectedMonths.value.length;
  const uniqueYears = [...new Set(selectedMonths.value.map(m => m.year))];

  if (uniqueYears.length === 1) {
    return `${count} month${count > 1 ? 's' : ''} selected (${uniqueYears[0]})`;
  }

  return `${count} month${count > 1 ? 's' : ''} selected across ${uniqueYears.length} years`;
});

const selectedMonthsForCurrentYear = computed(() =>
  monthsForSelectedYear.value.filter(m => m.is_available).length
);

const availableMonthsForCurrentYear = computed(() =>
  monthsForSelectedYear.value.filter(m => !m.isPastMonth && !m.is_available).length
);

const handleKeydown = (event) => {
  if (!isDropdownOpen.value) return;

  const items = Array.from(dropdownRef.value.querySelectorAll('.month-item:not(.disabled)'));
  if (items.length === 0) return;

  const focusedItem = document.activeElement;
  let nextIndex = -1;

  if (event.key === 'ArrowDown') {
    event.preventDefault();
    const currentIndex = items.indexOf(focusedItem);
    nextIndex = (currentIndex + 1) % items.length;
  } else if (event.key === 'ArrowUp') {
    event.preventDefault();
    const currentIndex = items.indexOf(focusedItem);
    nextIndex = (currentIndex - 1 + items.length) % items.length;
  } else if (event.key === 'Escape') {
    event.preventDefault();
    isDropdownOpen.value = false;
    dropdownRef.value.querySelector('button').focus();
  } else if (event.key === 'Enter' || event.key === ' ') {
    if (focusedItem && focusedItem.classList.contains('month-item')) {
      event.preventDefault();
      focusedItem.click();
    }
  }

  if (nextIndex !== -1) {
    items[nextIndex].focus();
  }
};

onMounted(() => {
  if (props.isEditMode && props.appointmentBookingWindow.length > 0) {
    selectedMonths.value = props.appointmentBookingWindow
      .map((booking) => {
        const monthObj = allMonths.find((m) => m.value === booking.month);
        return {
          name: monthObj?.name || '',
          short: monthObj?.short || '',
          value: booking.month,
          year: booking.year,
          is_available: booking.is_available,
        };
      })
      .filter(m => m.name);

    sortSelectedMonths();
  }

  document.addEventListener('click', closeDropdown);
  document.addEventListener('keydown', handleKeydown);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', closeDropdown);
  document.removeEventListener('keydown', handleKeydown);
});

watch(
  () => props.modelValue,
  (newValue) => {
    if (!newValue) return;
    selectedMonths.value = newValue.map(month => {
      const monthObj = allMonths.find(m => m.value === month.value);
      return {
        ...month,
        name: monthObj?.name || '',
        short: monthObj?.short || '',
      };
    }).filter(m => m.name);
    sortSelectedMonths();
  },
  { deep: true, immediate: true }
);

defineExpose({
  validate: validateMonths,
  clearValidation: clearValidationError,
  selectAll: selectAllMonthsForYear,
  clearAll: clearAllMonthsForYear,
});
</script>

<template>
  <div class="appointment-booking-window mb-4">
    <label for="monthDropdown" class="form-label d-flex align-items-center gap-2 mb-2">
      <span v-if="required" class="text-danger">*</span>
      <span v-if="hasSelectedMonths" class="text-muted-small text-secondary">
        ({{ selectedMonths.length }} selected)
      </span>
    </label>

    <div class="row align-items-center mb-3">
      <div class="col-auto">
        <label for="yearSelect" class="col-form-label fw-semibold text-muted">Year:</label>
      </div>
      <div class="col-md-4 col-6">
        <select
          id="yearSelect"
          class="form-select custom-select-year"
          v-model="selectedYear"
          :disabled="disabled"
        >
          <option v-for="year in years" :key="year" :value="year">
            {{ year }}
          </option>
        </select>
      </div>
      <div class="col-md-7 col-auto d-flex align-items-center gap-2 text-muted-small">
        <small class="text-info-subtle">
          <i class="fa fa-info-circle"></i>
          {{ selectedMonthsForCurrentYear }}/{{ selectedMonthsForCurrentYear + availableMonthsForCurrentYear }} months available for {{ selectedYear }}
        </small>
      </div>
    </div>

    <div class="dropdown" ref="dropdownRef">
      <button
        @click="toggleDropdown"
        class="btn btn-outline-primary dropdown-toggle w-100 d-flex justify-content-between align-items-center"
        type="button"
        id="monthDropdown"
        :aria-expanded="isDropdownOpen"
        :disabled="disabled"
        :class="{ 'is-invalid': validationErrors.selectedMonths, 'btn-disabled': disabled }"
      >
        <span class="text-truncate me-2">{{ selectedMonthsDisplay }}</span>
      </button>

      <div v-show="isDropdownOpen" class="dropdown-menu w-100 show shadow-lg border mt-2">
        <div class="dropdown-header d-flex justify-content-between align-items-center py-2 px-3 bg-light-subtle">
          <span class="fw-bold text-dark">Months for {{ selectedYear }}</span>
          <div class="btn-group btn-group-sm" role="group" aria-label="Bulk month actions">
            <button
              @click.stop="selectAllMonthsForYear"
              class="btn btn-sm btn-outline-success"
              type="button"
              :disabled="availableMonthsForCurrentYear === 0 || disabled"
              title="Select all available months for this year"
            >
              <i class="fa fa-check-double me-1"></i> All
            </button>
            <button
              @click.stop="clearAllMonthsForYear"
              class="btn btn-sm btn-outline-danger"
              type="button"
              :disabled="selectedMonthsForCurrentYear === 0 || disabled"
              title="Clear all selected months for this year"
            >
              <i class="fa fa-ban me-1"></i> Clear
            </button>
          </div>
        </div>
        <div class="dropdown-divider my-0"></div>

        <div class="month-grid">
          <a
            v-for="month in monthsForSelectedYear"
            :key="month.value"
            class="dropdown-item month-item d-flex justify-content-between align-items-center py-2 px-3"
            href="#"
            @click.prevent="toggleMonthSelection(month)"
            :class="{
              'active': month.is_available,
              'disabled': month.isPastMonth || disabled,
              'text-muted': month.isPastMonth,
            }"
            :title="month.isPastMonth ? 'Month is in the past' : ''"
            :aria-disabled="month.isPastMonth || disabled"
            role="option"
            :aria-selected="month.is_available"
          >
            <div class="d-flex align-items-center gap-2">
              <i
                class="fa month-checkbox-icon"
                :class="month.is_available ? 'fa-check-square text-primary' : 'fa-square-o text-secondary'"
              ></i>
              <span>{{ month.name }}</span>
              <small v-if="month.isPastMonth" class="text-danger-emphasis fw-bold">(Past)</small>
            </div>
            <span v-if="month.is_available" class="badge bg-primary-subtle text-primary rounded-pill checkmark-badge">
              <i class="fa fa-check"></i>
            </span>
          </a>
          <div v-if="monthsForSelectedYear.every(m => m.isPastMonth)" class="dropdown-item text-center text-muted py-3">
            <i class="fa fa-calendar-times-o me-1"></i> No future months available for this year.
          </div>
        </div>
      </div>
    </div>

    <div v-if="hasSelectedMonths" class="selected-months mt-3 p-3 border rounded-lg bg-light">
      <label class="form-label fw-semibold mb-2 text-dark">Currently Selected:</label>
      <div class="d-flex flex-wrap gap-2">
        <div
          v-for="(month, index) in selectedMonths"
          :key="`${month.year}-${month.value}`"
          class="badge ml-2 mb-2 d-flex align-items-center gap-2 p-2 shadow-sm month-pill"
          :class="{
            'bg-success-subtle text-success border border-success-subtle': !isMonthInPast(month.value, month.year),
            'bg-warning-subtle text-warning-emphasis border border-warning-subtle': isMonthInPast(month.value, month.year)
          }"
        >
          <span>{{ month.short || month.name }} {{ month.year }}</span>
          <button
            @click="removeMonth(index)"
            class="btn-close"
            aria-label="Remove"
            :disabled="disabled"
          > 
        <!--can u add a incon for deleate here -->
        x
           </button>
        </div>
      </div>

      <div class="mt-3 text-sm text-muted">
        <i class="fa fa-info-circle me-1"></i>
        These months will be available for appointment bookings.
      </div>
    </div>

    <div v-if="validationErrors.selectedMonths" class="invalid-feedback d-block mt-2">
      <i class="fa fa-exclamation-triangle me-1"></i>
      {{ validationErrors.selectedMonths }}
    </div>

    <div v-else class="form-text mt-2 text-info">
      <i class="fa fa-lightbulb-o me-1"></i>
      Select the months when appointments can be booked. You can select months across multiple years.
    </div>
  </div>
</template>

<style scoped>
:root {
  --bs-primary: #007bff;
  --bs-secondary: #6c757d;
  --bs-success: #28a745;
  --bs-danger: #dc3545;
  --bs-warning: #ffc107;
  --bs-info: #17a2b8;
  --bs-light: #f8f9fa;
  --bs-dark: #343a40;
  --bs-white: #fff;
  --bs-gray-100: #f8f9fa;
  --bs-gray-200: #e9ecef;
  --bs-gray-500: #adb5bd;
  --bs-gray-700: #495057;

  /* New Bootstrap 5-like color palette */
  --bs-primary-rgb: 13, 110, 253;
  --bs-secondary-rgb: 108, 117, 125;
  --bs-success-rgb: 25, 135, 84;
  --bs-danger-rgb: 220, 53, 69;
  --bs-warning-rgb: 255, 193, 7;
  --bs-info-rgb: 13, 202, 240;

  /* Subtler background colors for badges and dropdowns */
  --bs-primary-subtle: #cfe2ff;
  --bs-secondary-subtle: #e2e3e5;
  --bs-success-subtle: #d1e7dd;
  --bs-warning-subtle: #fff3cd;
  --bs-info-subtle: #cff4fc;
  --bs-light-subtle: #fcfcfd;
}

.appointment-booking-window {
  position: relative;
  font-family: 'Inter', sans-serif; /* Modern font */
  color: var(--bs-dark);
}

/* Typography */
.text-lg {
  font-size: 1.25rem; /* Larger label for importance */
}

.font-semibold {
  font-weight: 600;
}

.text-muted-small {
  font-size: 0.875em;
  color: var(--bs-secondary);
}

.text-info-subtle {
  color: rgba(var(--bs-info-rgb), 0.8);
}

.text-danger-emphasis {
  color: #dc3545; /* Stronger red for "Past" */
}

/* Year Select */
.custom-select-year {
  border-radius: 0.5rem;
  padding: 0.5rem 1rem;
  border-color: var(--bs-gray-300);
}

/* Dropdown button */
.btn-outline-primary {
  border-color: var(--bs-primary);
  color: var(--bs-primary);
  border-radius: 0.5rem;
  padding: 0.75rem 1rem; /* More comfortable padding */
  font-size: 1rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); /* Subtle shadow */
}

.btn-outline-primary:hover {
  background-color: var(--bs-primary);
  color: var(--bs-white);
}

.btn-disabled {
  opacity: 0.6;
  cursor: not-allowed;
  background-color: var(--bs-light) !important;
  border-color: var(--bs-gray-300) !important;
  color: var(--bs-secondary) !important;
}

.btn-outline-primary.is-invalid {
  border-color: var(--bs-danger);
  box-shadow: 0 0 0 0.25rem rgba(var(--bs-danger-rgb), 0.25);
}

/* Dropdown menu */
.dropdown-menu.show {
  border-radius: 0.75rem; /* More rounded corners */
  box-shadow: 0 1rem 2rem rgba(0, 0, 0, 0.1); /* Stronger, softer shadow */
  padding: 0; /* Remove default padding */
  overflow: hidden; /* Ensures rounded corners apply to content */
}

.dropdown-header {
  background-color: var(--bs-light-subtle); /* Lighter header background */
  border-bottom: 1px solid var(--bs-gray-200);
  border-top-left-radius: 0.75rem; /* Match parent radius */
  border-top-right-radius: 0.75rem; /* Match parent radius */
  position: sticky;
  top: 0;
  z-index: 100;
  padding: 0.75rem 1.25rem; /* More padding */
}

.dropdown-header .btn-group .btn {
  border-radius: 0.5rem; /* Rounded buttons */
  font-weight: 500;
  padding: 0.375rem 0.75rem;
  transition: all 0.2s ease;
}

.btn-outline-success {
  border-color: var(--bs-success);
  color: var(--bs-success);
}
.btn-outline-success:hover {
  background-color: var(--bs-success);
  color: var(--bs-white);
}

.btn-outline-danger {
  border-color: var(--bs-danger);
  color: var(--bs-danger);
}
.btn-outline-danger:hover {
  background-color: var(--bs-danger);
  color: var(--bs-white);
}

.dropdown-divider {
  border-top: 1px solid var(--bs-gray-200);
  margin: 0; /* Remove default margin */
}

.month-grid {
  max-height: 280px; /* Slightly reduced height for better fit */
  overflow-y: auto;
  padding: 0.5rem 0; /* Internal padding for grid */
}

.month-item {
  transition: all 0.2s ease-in-out;
  cursor: pointer;
  font-weight: 500;
  padding: 0.75rem 1.25rem; /* More padding for month items */
  color: var(--bs-dark);
}

.month-item:hover:not(.disabled) {
  background-color: var(--bs-primary-subtle); /* Lighter hover background */
  color: var(--bs-dark);
}

.month-item.active {
  background-color: var(--bs-primary);
  color: var(--bs-dark);
}

.month-item.disabled {
  cursor: not-allowed;
  opacity: 0.6;
  background-color: var(--bs-gray-100);
  color: var(--bs-secondary);
  pointer-events: none;
}

.month-checkbox-icon {
  width: 1em; /* Ensure consistent icon width */
  text-align: center;
}

.checkmark-badge {
  font-size: 0.75rem;
  padding: 0.2em 0.5em;
  min-width: 20px; /* Ensure it's not too small */
  text-align: center;
  line-height: 1;
}

/* Selected months badges */
.selected-months {
  background-color: var(--bs-light-subtle);
  border-radius: 0.75rem;
  padding: 1.25rem;
  border: 1px solid var(--bs-gray-200);
}

.month-pill {
  font-weight: 500;
  padding: 0.75em 1em;
  border-radius: 0.6rem; /* Slightly more rounded */
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08); /* More pronounced shadow */
  transition: all 0.2s ease;
}

.month-pill:hover:not([disabled]) {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.12);
}

.bg-success-subtle {
  background-color: var(--bs-success-subtle) !important;
}
.text-success {
  color: var(--bs-success) !important;
}
.border-success-subtle {
  border-color: rgba(var(--bs-success-rgb), 0.3) !important;
}

.bg-warning-subtle {
  background-color: var(--bs-warning-subtle) !important;
}
.text-warning-emphasis {
  color: #664d03 !important; /* Darker yellow text for contrast */
}
.border-warning-subtle {
  border-color: rgba(var(--bs-warning-rgb), 0.3) !important;
}

.btn-close {
    width: 1.5rem;
    height: 1.5rem;
    padding: 0;
    border: none;
    background: transparent;
    opacity: 0.7; /* Slightly transparent */
    transition: opacity 0.2s ease, transform 0.2s ease;


}

.btn-close:hover {
  opacity: 1;
}

.btn-close-white {
  filter: brightness(0) invert(1); /* Makes the icon white */
}

/* Dropdown button icon rotation */
.transition-transform {
  transition: transform 0.2s ease-in-out;
}

.rotate-180 {
  transform: rotate(180deg);
}

/* Custom scrollbar styles */
.month-grid::-webkit-scrollbar {
  width: 6px; /* Slimmer scrollbar */
}

.month-grid::-webkit-scrollbar-track {
  background: var(--bs-gray-100);
  border-radius: 3px;
}

.month-grid::-webkit-scrollbar-thumb {
  background: var(--bs-gray-400);
  border-radius: 3px;
}

.month-grid::-webkit-scrollbar-thumb:hover {
  background: var(--bs-gray-600);
}

/* Focus styles for accessibility */
.dropdown-item:focus {
  outline: 2px solid rgba(var(--bs-primary-rgb), 0.5); /* Primary focus ring */
  outline-offset: -2px;
  background-color: var(--bs-primary-subtle);
  color: var(--bs-primary);
}

.btn:focus {
  box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
}

/* Form feedback messages */
.invalid-feedback {
  color: var(--bs-danger);
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.form-text {
  color: var(--bs-secondary);
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.text-info {
  color: rgba(var(--bs-info-rgb), 0.9) !important;
}

/* Responsive adjustments */
@media (max-width: 767.98px) {
  .dropdown-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
  }
  .dropdown-header .btn-group {
    width: 100%;
    justify-content: stretch;
  }
  .dropdown-header .btn-group .btn {
    flex: 1;
    font-size: 0.9rem;
    padding: 0.4rem 0.6rem;
  }
  .month-item {
    padding: 0.6rem 1rem;
  }
  .selected-months .badge {
    font-size: 0.9rem;
    padding: 0.6rem 0.8rem;
  }
  .col-md-7.col-auto.text-muted-small {
    margin-top: 0.5rem;
    text-align: left;
  }
}
</style>