<script setup>
import { defineProps, defineEmits } from 'vue';

// PrimeVue Components
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';

const props = defineProps({
  users: {
    type: Array,
    required: true,
  },
  filters: {
    type: Object,
    required: true,
  },
  paymentOptionsFilter: {
    type: Array,
    required: true,
  },
  userStatusOptions: {
    type: Array,
    required: true,
  },
  selectedPaymentMethodFilter: {
    type: String, // Can be null as well
    default: null,
  },
  getStatusBadgeClass: {
    type: Function,
    required: true,
  },
  getPaymentMethodTagClass: {
    type: Function,
    required: true,
  },
  getPaymentMethodName: {
    type: Function,
    required: true,
  },
  getEmptyMessageForUsers: {
    type: Function,
    required: true,
  },
});

const emit = defineEmits([
  'show-assign-payment-dialog',
  'clear-all-filters',
  'edit-user', // This event is emitted when the edit button is clicked
  'confirm-delete-user',
  'update:global-filter',
  'update:status-filter',
  'update:payment-method-filter',
]);

// This function is defined in the parent component and passed as a prop if needed,
// but for DataTable's @filter event, it's typically handled internally by PrimeVue
// or you can emit a custom event to the parent. For simplicity and consistency with
// existing filter updates, we're not explicitly defining handleDataTableFilter here.
// The @filter prop on DataTable primarily works with the 'filters' object directly.
// If you need specific logic on filter, you could emit a generic 'filter-updated' event.
// For now, the existing `update:global-filter`, `update:status-filter`, etc. handle it.
// const handleDataTableFilter = (event) => {
//   // You can perform additional logic here if needed when filters change
//   console.log("DataTable filter event:", event);
// };
</script>
<template>
  <div class="card">
    <div class="card-header">
      <div class="header-content">
        <div class="title-section">
          <h2 class="card-title">User Payment Access List</h2>
          <span class="specialization-count">{{ users.length }} users</span>
        </div>
        <button @click="$emit('show-assign-payment-dialog')" class="add-specialization-button">
          <i class="fas fa-money-check-alt button-icon"></i>
          <span>Assign Payment Methods</span>
        </button>
      </div>

      <div class="filters-section">
        <div class="search-container">
          <div class="search-input-wrapper">
            <i class="fas fa-search search-icon"></i>
            <InputText
              :modelValue="filters['global'].value"
              @update:modelValue="(value) => $emit('update:global-filter', value)"
              placeholder="Search Users..."
              class="search-input"
            />
          </div>
        </div>

        <div class="filter-container">
          <Dropdown
            :modelValue="selectedPaymentMethodFilter"
            @update:modelValue="(value) => $emit('update:payment-method-filter', value)"
            :options="paymentOptionsFilter"
            optionLabel="name"
            optionValue="key"
            placeholder="Filter by Method"
            class="status-filter custom-dropdown"
            showClear
          />

          <Dropdown
            :modelValue="filters['status'].value"
            @update:modelValue="(value) => $emit('update:status-filter', value)"
            :options="userStatusOptions"
            placeholder="Filter by Status"
            class="status-filter custom-dropdown"
            showClear
          />
          <button
            @click="$emit('clear-all-filters')"
            class="clear-filters-btn"
            v-if="filters['global'].value || filters['status'].value || selectedPaymentMethodFilter"
          >
            <i class="fas fa-times"></i>
            Clear Filters
          </button>
        </div>
      </div>
    </div>

    <div class="card-content-area">
      <DataTable
        :value="users"
        :filters="filters"
        paginator
        :rows="10"
        :rowsPerPageOptions="[5, 10, 20, 50]"
        class="p-datatable-gridlines custom-datatable"
        responsiveLayout="scroll"
        :globalFilterFields="['name', 'email', 'status']"
        :emptyMessage="getEmptyMessageForUsers()"
      >
        <Column field="name" header="User Name" :sortable="true" style="min-width: 12rem"></Column>
        <Column field="email" header="username" style="min-width: 15rem "></Column>
        <Column field="status" header="Status" style="width: 8rem">
          <template #body="slotProps">
            <span :class="['user-status-badge', getStatusBadgeClass(slotProps.data.status)]">{{ slotProps.data.status }}</span>
          </template>
        </Column>
        <Column header="Allowed Methods" style="min-width: 18rem">
          <template #body="slotProps">
            <div class="flex flex-wrap gap-1">
              <template v-if="slotProps.data.allowedMethods && slotProps.data.allowedMethods.length > 0">
                <span
                  v-for="methodKey in (typeof slotProps.data.allowedMethods === 'string' ? slotProps.data.allowedMethods.split(',').map(m => m.trim()) : slotProps.data.allowedMethods)"
                  :key="methodKey"
                  :class="['payment-method-tag ml-1 mr-1', getPaymentMethodTagClass(methodKey)]"
                >
                  {{ getPaymentMethodName(methodKey) }}
                </span>
              </template>
              <span v-else class="text-gray-500">None</span>
            </div>
          </template>
        </Column>
        <Column header="actions" style="width: 10rem">
          <template #body="slotProps">
            <button class="action-button edit-button mr-2" @click="$emit('edit-user', slotProps.data)" title="Edit user">
              <i class="fas fa-edit"></i>
            </button>
            <button class="action-button delete-button" @click="$emit('confirm-delete-user', slotProps.data)" title="Delete user">
              <i class="fas fa-trash-alt"></i>
            </button>
          </template>
        </Column>
      </DataTable>
    </div>
  </div>
</template>

<style scoped>
/* Add your scoped styles here */
</style>