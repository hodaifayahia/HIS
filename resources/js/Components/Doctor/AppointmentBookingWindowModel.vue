<script setup>
import { ref, computed, watch, onMounted, nextTick, onBeforeUnmount } from 'vue';
import 'font-awesome/css/font-awesome.min.css';
import Card from 'primevue/card';
import Dropdown from 'primevue/dropdown';
import Button from 'primevue/button';
import Badge from 'primevue/badge';
import Chip from 'primevue/chip';
import Message from 'primevue/message';
import Divider from 'primevue/divider';
import MultiSelect from 'primevue/multiselect';

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
  <Card class="tw-shadow-xl tw-border-0 tw-bg-gradient-to-br tw-from-white tw-to-gray-50">
    <template #header>
      <div class="tw-bg-gradient-to-r tw-from-indigo-600 tw-to-purple-600 tw-text-white tw-p-6 tw-rounded-t-lg">
        <div class="tw-flex tw-items-center tw-justify-between">
          <div class="tw-flex tw-items-center tw-space-x-3">
            <i class="pi pi-calendar-plus tw-text-2xl"></i>
            <h2 class="tw-text-2xl tw-font-bold tw-m-0">Appointment Booking Window</h2>
            <span v-if="required" class="tw-text-red-300 tw-text-xl">*</span>
          </div>
          <Badge 
            v-if="hasSelectedMonths" 
            :value="`${selectedMonths.length} Selected`" 
            severity="info" 
            class="tw-bg-white tw-text-indigo-600 tw-px-3 tw-py-1"
          />
        </div>
      </div>
    </template>
    
    <template #content>
      <div class="tw-p-6 tw-space-y-6">
        <!-- Year Selection -->
        <div class="tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50 tw-p-4 tw-rounded-xl tw-border tw-border-blue-200">
          <div class="tw-flex tw-flex-col md:tw-flex-row tw-items-start md:tw-items-center tw-space-y-4 md:tw-space-y-0 md:tw-space-x-4">
            <div class="tw-flex tw-items-center tw-space-x-3">
              <i class="pi pi-calendar tw-text-blue-500 tw-text-lg"></i>
              <label for="yearSelect" class="tw-text-sm tw-font-semibold tw-text-gray-700">Select Year:</label>
            </div>
            <div class="tw-flex-1 tw-max-w-xs">
              <Dropdown
                id="yearSelect"
                v-model="selectedYear"
                :options="years"
                :disabled="disabled"
                class="tw-w-full"
                panelClass="tw-shadow-lg tw-border tw-border-gray-200 tw-rounded-lg"
                :pt="{
                  root: 'tw-w-full',
                  input: 'tw-px-4 tw-py-2 tw-border tw-border-gray-300 tw-rounded-lg focus:tw-ring-2 focus:tw-ring-blue-500 focus:tw-border-transparent tw-transition-all tw-duration-200',
                  trigger: 'tw-px-3 tw-text-gray-500'
                }"
              />
            </div>
            <div class="tw-flex tw-items-center tw-space-x-2 tw-text-sm tw-text-blue-600">
              <i class="pi pi-info-circle"></i>
              <span>{{ selectedMonthsForCurrentYear }}/{{ selectedMonthsForCurrentYear + availableMonthsForCurrentYear }} months available for {{ selectedYear }}</span>
            </div>
          </div>
        </div>

        <!-- Month Selection -->
        <div class="tw-space-y-4">
          <div class="tw-relative" ref="dropdownRef">
            <Button
              @click="toggleDropdown"
              :disabled="disabled"
              class="tw-w-full tw-justify-between tw-px-4 tw-py-3 tw-text-left tw-bg-white tw-border tw-border-gray-300 tw-rounded-lg tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200"
              :class="{ 
                'tw-border-red-500 tw-ring-2 tw-ring-red-200': validationErrors.selectedMonths,
                'tw-opacity-60 tw-cursor-not-allowed': disabled 
              }"
              :pt="{
                root: 'tw-w-full tw-justify-between',
                label: 'tw-text-left tw-truncate tw-flex-1'
              }"
            >
              <template #default>
                <div class="tw-flex tw-items-center tw-justify-between tw-w-full">
                  <span class="tw-truncate tw-text-gray-700">{{ selectedMonthsDisplay }}</span>
                  <i class="pi pi-chevron-down tw-text-gray-400 tw-transition-transform tw-duration-200" :class="{ 'tw-rotate-180': isDropdownOpen }"></i>
                </div>
              </template>
            </Button>

            <div v-show="isDropdownOpen" class="tw-absolute tw-z-50 tw-w-full tw-mt-2 tw-bg-white tw-border tw-border-gray-200 tw-rounded-xl tw-shadow-xl tw-overflow-hidden">
              <!-- Header with bulk actions -->
              <div class="tw-bg-gradient-to-r tw-from-gray-50 tw-to-gray-100 tw-p-4 tw-border-b tw-border-gray-200">
                <div class="tw-flex tw-flex-col sm:tw-flex-row tw-items-start sm:tw-items-center tw-justify-between tw-space-y-3 sm:tw-space-y-0">
                  <h3 class="tw-text-lg tw-font-semibold tw-text-gray-800 tw-flex tw-items-center">
                    <i class="pi pi-calendar tw-mr-2 tw-text-blue-500"></i>
                    Months for {{ selectedYear }}
                  </h3>
                  <div class="tw-flex tw-space-x-2">
                    <Button
                      @click.stop="selectAllMonthsForYear"
                      :disabled="availableMonthsForCurrentYear === 0 || disabled"
                      severity="success"
                      size="small"
                      outlined
                      class="tw-px-3 tw-py-1"
                    >
                      <i class="pi pi-check tw-mr-1"></i> All
                    </Button>
                    <Button
                      @click.stop="clearAllMonthsForYear"
                      :disabled="selectedMonthsForCurrentYear === 0 || disabled"
                      severity="danger"
                      size="small"
                      outlined
                      class="tw-px-3 tw-py-1"
                    >
                      <i class="pi pi-times tw-mr-1"></i> Clear
                    </Button>
                  </div>
                </div>
              </div>

              <!-- Month grid -->
              <div class="tw-max-h-80 tw-overflow-y-auto tw-p-2">
                <div class="tw-grid tw-grid-cols-1 sm:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-2">
                  <button
                    v-for="month in monthsForSelectedYear"
                    :key="month.value"
                    @click="toggleMonthSelection(month)"
                    :disabled="month.isPastMonth || disabled"
                    class="tw-flex tw-items-center tw-justify-between tw-p-3 tw-rounded-lg tw-border tw-transition-all tw-duration-200 tw-text-left"
                    :class="{
                      'tw-bg-blue-50 tw-border-blue-200 tw-text-blue-700': month.is_available && !month.isPastMonth,
                      'tw-bg-gray-50 tw-border-gray-200 tw-text-gray-400 tw-cursor-not-allowed': month.isPastMonth || disabled,
                      'tw-bg-white tw-border-gray-200 tw-text-gray-700 hover:tw-bg-gray-50': !month.is_available && !month.isPastMonth && !disabled
                    }"
                  >
                    <div class="tw-flex tw-items-center tw-space-x-3">
                      <i class="pi" :class="month.is_available ? 'pi-check-square tw-text-blue-500' : 'pi-square tw-text-gray-400'"></i>
                      <div>
                        <span class="tw-font-medium">{{ month.name }}</span>
                        <span v-if="month.isPastMonth" class="tw-ml-2 tw-text-xs tw-bg-red-100 tw-text-red-600 tw-px-2 tw-py-1 tw-rounded-full">Past</span>
                      </div>
                    </div>
                    <Badge v-if="month.is_available" value="✓" severity="success" class="tw-text-xs" />
                  </button>
                </div>
                <div v-if="monthsForSelectedYear.every(m => m.isPastMonth)" class="tw-text-center tw-py-8 tw-text-gray-500">
                  <i class="pi pi-calendar-times tw-text-2xl tw-mb-2 tw-block"></i>
                  <p>No future months available for this year.</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Selected months display -->
        <div v-if="hasSelectedMonths" class="tw-bg-gradient-to-r tw-from-green-50 tw-to-emerald-50 tw-p-6 tw-rounded-xl tw-border tw-border-green-200">
          <div class="tw-flex tw-items-center tw-mb-4">
            <i class="pi pi-check-circle tw-text-green-500 tw-mr-2 tw-text-lg"></i>
            <h3 class="tw-text-lg tw-font-semibold tw-text-gray-800">Currently Selected Months</h3>
          </div>
          <div class="tw-flex tw-flex-wrap tw-gap-3">
            <Chip
              v-for="(month, index) in selectedMonths"
              :key="`${month.year}-${month.value}`"
              :label="`${month.short || month.name} ${month.year}`"
              :removable="!disabled"
              @remove="removeMonth(index)"
              :class="{
                'tw-bg-green-100 tw-text-green-700': !isMonthInPast(month.value, month.year),
                'tw-bg-yellow-100 tw-text-yellow-700': isMonthInPast(month.value, month.year)
              }"
              class="tw-shadow-sm tw-transition-all tw-duration-200 hover:tw-shadow-md"
            />
          </div>
          <div class="tw-mt-4 tw-flex tw-items-center tw-text-sm tw-text-green-600">
            <i class="pi pi-info-circle tw-mr-2"></i>
            <span>These months will be available for appointment bookings.</span>
          </div>
        </div>

        <!-- Validation and help messages -->
        <Message v-if="validationErrors.selectedMonths" severity="error" class="tw-mb-4">
          <div class="tw-flex tw-items-center">
            <i class="pi pi-exclamation-triangle tw-mr-2"></i>
            {{ validationErrors.selectedMonths }}
          </div>
        </Message>

        <Message v-else severity="info" class="tw-mb-4">
          <div class="tw-flex tw-items-center">
            <i class="pi pi-lightbulb tw-mr-2"></i>
            Select the months when appointments can be booked. You can select months across multiple years.
          </div>
        </Message>
      </div>
    </template>
  </Card>
</template>

<style scoped>
/* Custom PrimeVue component overrides */
:deep(.p-card) {
  @apply tw-transition-all tw-duration-300;
}

:deep(.p-card-header) {
  @apply tw-p-0;
}

:deep(.p-card-content) {
  @apply tw-p-0;
}

:deep(.p-dropdown) {
  @apply tw-w-full;
}

:deep(.p-dropdown .p-dropdown-trigger) {
  @apply tw-text-gray-500;
}

:deep(.p-dropdown .p-dropdown-label) {
  @apply tw-text-gray-700;
}

:deep(.p-dropdown:not(.p-disabled):hover) {
  @apply tw-border-blue-400;
}

:deep(.p-dropdown:not(.p-disabled).p-focus) {
  @apply tw-border-blue-500 tw-ring-2 tw-ring-blue-200;
}

:deep(.p-button) {
  @apply tw-transition-all tw-duration-200;
}

:deep(.p-button.p-button-outlined.p-button-success) {
  @apply tw-border-green-500 tw-text-green-600 hover:tw-bg-green-500 hover:tw-text-white;
}

:deep(.p-button.p-button-outlined.p-button-danger) {
  @apply tw-border-red-500 tw-text-red-600 hover:tw-bg-red-500 hover:tw-text-white;
}

:deep(.p-button.p-button-sm) {
  @apply tw-px-3 tw-py-1 tw-text-sm;
}

:deep(.p-badge) {
  @apply tw-transition-all tw-duration-200;
}

:deep(.p-badge.p-badge-success) {
  @apply tw-bg-green-500 tw-text-white;
}

:deep(.p-badge.p-badge-info) {
  @apply tw-bg-blue-500 tw-text-white;
}

:deep(.p-chip) {
  @apply tw-transition-all tw-duration-200;
}

:deep(.p-chip .p-chip-remove-icon) {
  @apply tw-text-gray-500 hover:tw-text-red-500;
}

:deep(.p-message) {
  @apply tw-rounded-lg tw-border-l-4;
}

:deep(.p-message.p-message-error) {
  @apply tw-bg-red-50 tw-border-red-500 tw-text-red-700;
}

:deep(.p-message.p-message-info) {
  @apply tw-bg-blue-50 tw-border-blue-500 tw-text-blue-700;
}

/* Custom animations */
.tw-rotate-180 {
  transform: rotate(180deg);
}

/* Custom scrollbar */
::-webkit-scrollbar {
  width: 6px;
}

::-webkit-scrollbar-track {
  @apply tw-bg-gray-100 tw-rounded-full;
}

::-webkit-scrollbar-thumb {
  @apply tw-bg-gray-400 tw-rounded-full;
}

::-webkit-scrollbar-thumb:hover {
  @apply tw-bg-gray-500;
}

/* Focus styles for accessibility */
button:focus {
  @apply tw-outline-none tw-ring-2 tw-ring-blue-500 tw-ring-offset-2;
}

/* Responsive adjustments */
@media (max-width: 640px) {
  :deep(.tw-grid-cols-1.sm\:tw-grid-cols-2.lg\:tw-grid-cols-3) {
    display: grid;
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }
  
  :deep(.tw-flex-col.sm\:tw-flex-row) {
    display: flex;
    flex-direction: column;
  }
  
  :deep(.tw-space-y-3.sm\:tw-space-y-0) {
    row-gap: 0.75rem;
  }
}

/* Animation classes */
.fade-enter-active,
.fade-leave-active {
  transition: all 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* Hover effects */
.hover\:tw-scale-\[1\.02\]:hover {
  transform: scale(1.02);
}

.hover\:tw-shadow-md:hover {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}
</style>