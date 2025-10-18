<template>
  <DataTable
    :value="movements"
    :loading="loading"
    paginator
    :rows="10"
    :rowsPerPageOptions="[5, 10, 25, 50]"
    tableStyle="min-width: 50rem"
    filterDisplay="row"
    :globalFilterFields="['movement_number', 'requestingService.name', 'providingService.name', 'requestingUser.name']"
    removableSort
  >
    <template #header>
      <div class="tw-flex tw-justify-between tw-items-center">
        <h3 class="tw-text-xl tw-font-semibold">Pending Approvals</h3>
        <span class="p-input-icon-left">
          <i class="pi pi-search" />
          <InputText v-model="filters['global'].value" placeholder="Search requests..." />
        </span>
      </div>
    </template>

    <Column field="movement_number" header="Request #" sortable style="min-width:12rem">
      <template #body="slotProps">
        <span class="tw-font-mono tw-text-sm">{{ slotProps.data.movement_number }}</span>
      </template>
    </Column>

    <Column header="Products" style="min-width:20rem">
      <template #body="slotProps">
        <div class="tw-space-y-1">
          <div v-for="item in slotProps.data.items" :key="item.id" class="tw-flex tw-justify-between tw-text-sm">
            <span>{{ item.product?.name }}</span>
            <span class="tw-font-medium">{{ item.requested_quantity }}</span>
          </div>
        </div>
      </template>
    </Column>

    <Column header="Requesting Service" style="min-width:15rem">
      <template #body="slotProps">
        <div class="tw-flex tw-items-center tw-space-x-3">
          <div class="tw-w-8 tw-h-8 tw-bg-green-100 tw-rounded-full tw-flex tw-items-center tw-justify-center">
            <i class="pi pi-building tw-text-green-600"></i>
          </div>
          <div>
            <p class="tw-font-medium">{{ slotProps.data.requesting_service?.name }}</p>
            <p class="tw-text-sm tw-text-gray-500">by {{ slotProps.data.requesting_user?.name }}</p>
          </div>
        </div>
      </template>
    </Column>

    <Column header="Providing Service" style="min-width:15rem">
      <template #body="slotProps">
        <div class="tw-flex tw-items-center tw-space-x-3">
          <div class="tw-w-8 tw-h-8 tw-bg-blue-100 tw-rounded-full tw-flex tw-items-center tw-justify-center">
            <i class="pi pi-building tw-text-blue-600"></i>
          </div>
          <div>
            <p class="tw-font-medium">{{ slotProps.data.providing_service?.name }}</p>
            <p class="tw-text-sm tw-text-gray-500">{{ slotProps.data.providing_service?.type }}</p>
          </div>
        </div>
      </template>
    </Column>

    <Column field="request_reason" header="Reason" style="min-width:15rem">
      <template #body="slotProps">
        <p class="tw-text-sm tw-line-clamp-2">{{ slotProps.data.request_reason }}</p>
      </template>
    </Column>

    <Column field="status" header="Status" sortable>
      <template #body="slotProps">
        <Tag :value="slotProps.data.status" :severity="getStatusSeverity(slotProps.data.status)" />
      </template>
    </Column>

    <Column field="created_at" header="Requested" sortable style="min-width:12rem">
      <template #body="slotProps">
        <div>
          <p class="tw-font-medium">{{ formatDate(slotProps.data.created_at) }}</p>
          <p class="tw-text-sm tw-text-gray-500">{{ formatTime(slotProps.data.created_at) }}</p>
        </div>
      </template>
    </Column>

    <Column header="Actions" style="min-width:20rem">
      <template #body="slotProps">
        <div class="tw-flex tw-space-x-2">
          <Button
            v-if="slotProps.data.status === 'pending'"
            icon="pi pi-check"
            class="p-button-rounded p-button-success p-button-text"
            @click="$emit('approve-request', slotProps.data)"
            v-tooltip="'Approve Request'"
          />
          
          <Button
            v-if="slotProps.data.status === 'pending'"
            icon="pi pi-times"
            class="p-button-rounded p-button-danger p-button-text"
            @click="$emit('reject-request', slotProps.data)"
            v-tooltip="'Reject Request'"
          />
          
          <Button
            v-if="slotProps.data.status === 'approved'"
            icon="pi pi-arrow-right"
            class="p-button-rounded p-button-info p-button-text"
            @click="$emit('init-transfer', slotProps.data)"
            v-tooltip="'Initialize Transfer'"
          />
          
          <Button
            icon="pi pi-eye"
            class="p-button-rounded p-button-info p-button-text"
            @click="$emit('view-movement', slotProps.data)"
            v-tooltip="'View Details'"
          />
        </div>
      </template>
    </Column>
  </DataTable>
</template>

<script>
import InputText from 'primevue/inputtext';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Button from 'primevue/button';

export default {
  name: 'StockApprovalsTable',
  components: {
    InputText,
    DataTable,
    Column,
    Tag,
    Button
  },
  props: {
    movements: {
      type: Array,
      default: () => []
    },
    loading: {
      type: Boolean,
      default: false
    },
    filters: {
      type: Object,
      required: true
    }
  },
  emits: ['approve-request', 'reject-request', 'init-transfer', 'view-movement'],
  methods: {
    getStatusSeverity(status) {
      const severities = {
        'draft': 'secondary',
        'pending': 'warning',
        'approved': 'success',
        'partially_approved': 'info',
        'rejected': 'danger',
        'executed': 'success',
        'partially_executed': 'info',
        'cancelled': 'secondary',
        'in_transfer': 'info'
      };
      return severities[status] || 'info';
    },
    formatDate(date) {
      return new Date(date).toLocaleDateString();
    },
    formatTime(date) {
      return new Date(date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
  }
};
</script>

<style scoped>
.p-datatable .p-datatable-tbody > tr:hover {
  background-color: rgba(59, 130, 246, 0.05);
}

.p-button {
  transition: all 0.2s ease-in-out;
}

.p-button:hover {
  transform: translateY(-1px);
}

.p-tag {
  font-weight: 500;
  text-transform: capitalize;
}

.tw-line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  line-clamp: 2;
  overflow: hidden;
}
</style>