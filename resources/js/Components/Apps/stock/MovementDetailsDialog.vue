<template>
  <Dialog :visible="visible" @update:visible="$emit('update:visible', $event)" modal header="Movement Details" :style="{width: '70rem'}">
    <div class="tw-space-y-6" v-if="movement">
      <!-- Movement Header -->
      <div class="tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50 tw-p-6 tw-rounded-lg">
        <div class="tw-grid tw-grid-cols-2 tw-gap-6">
          <div>
            <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-4">Request Information</h3>
            <div class="tw-space-y-2">
              <div class="tw-flex tw-justify-between">
                <span class="tw-text-gray-600">Request Number:</span>
                <span class="tw-font-medium">#{{ movement.request_number }}</span>
              </div>
              <div class="tw-flex tw-justify-between">
                <span class="tw-text-gray-600">Status:</span>
                <Tag
                  :value="movement.status"
                  :severity="getStatusSeverity(movement.status)"
                  class="tw-text-xs"
                />
              </div>
              <div class="tw-flex tw-justify-between">
                <span class="tw-text-gray-600">Requester:</span>
                <span class="tw-font-medium">{{ movement.requesting_service?.name || 'N/A' }}</span>
              </div>
              <div class="tw-flex tw-justify-between">
                <span class="tw-text-gray-600">Provider:</span>
                <span class="tw-font-medium">{{ movement.providing_service?.name || 'N/A' }}</span>
              </div>
            </div>
          </div>

          <div>
            <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-4">Timeline</h3>
            <div class="tw-space-y-2">
              <div class="tw-flex tw-justify-between">
                <span class="tw-text-gray-600">Created:</span>
                <span class="tw-font-medium">{{ formatDate(movement.created_at) }}</span>
              </div>
              <div class="tw-flex tw-justify-between" v-if="movement.approved_at">
                <span class="tw-text-gray-600">Approved:</span>
                <span class="tw-font-medium">{{ formatDate(movement.approved_at) }}</span>
              </div>
              <div class="tw-flex tw-justify-between" v-if="movement.expected_delivery_date">
                <span class="tw-text-gray-600">Expected Delivery:</span>
                <span class="tw-font-medium">{{ formatDate(movement.expected_delivery_date) }}</span>
              </div>
              <div class="tw-flex tw-justify-between">
                <span class="tw-text-gray-600">Last Updated:</span>
                <span class="tw-font-medium">{{ formatDate(movement.updated_at) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Request Reason -->
        <div class="tw-mt-4" v-if="movement.request_reason">
          <h4 class="tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Request Reason</h4>
          <p class="tw-text-gray-800 tw-bg-white tw-p-3 tw-rounded tw-border">{{ movement.request_reason }}</p>
        </div>
      </div>

      <!-- Products List -->
      <div>
        <h3 class="tw-text-lg tw-font-semibold tw-mb-4">Requested Products</h3>
        <DataTable
          :value="movement.products || []"
          class="tw-w-full"
          :paginator="false"
          emptyMessage="No products in this request"
        >
          <Column field="name" header="Product Name">
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-space-x-2">
                <i :class="getProductIcon(slotProps.data.type)" class="tw-text-blue-600"></i>
                <div>
                  <div class="tw-font-medium">{{ slotProps.data.name }}</div>
                  <div class="tw-text-sm tw-text-gray-500">{{ slotProps.data.type }}</div>
                </div>
              </div>
            </template>
          </Column>
          <Column field="pivot.quantity" header="Quantity">
            <template #body="slotProps">
              <div class="tw-text-center">
                <span class="tw-font-bold tw-text-lg">{{ slotProps.data.pivot?.quantity || 0 }}</span>
                <div class="tw-text-xs tw-text-gray-500">{{ slotProps.data.unit }}</div>
              </div>
            </template>
          </Column>
          <Column field="pivot.urgency_level" header="Urgency">
            <template #body="slotProps">
              <Tag
                :value="slotProps.data.pivot?.urgency_level || 'normal'"
                :severity="getUrgencySeverity(slotProps.data.pivot?.urgency_level)"
              />
            </template>
          </Column>
          <Column field="pivot.notes" header="Notes">
            <template #body="slotProps">
              <span class="tw-text-sm tw-text-gray-600">
                {{ slotProps.data.pivot?.notes || 'No notes' }}
              </span>
            </template>
          </Column>
        </DataTable>
      </div>

      <!-- Approval Information -->
      <div v-if="movement.status !== 'draft' && movement.status !== 'pending'" class="tw-bg-gray-50 tw-p-4 tw-rounded-lg">
        <h3 class="tw-text-lg tw-font-semibold tw-mb-3">Approval Information</h3>
        <div class="tw-grid tw-grid-cols-2 tw-gap-4">
          <div v-if="movement.approved_by">
            <span class="tw-text-gray-600">Approved By:</span>
            <span class="tw-ml-2 tw-font-medium">{{ movement.approved_by }}</span>
          </div>
          <div v-if="movement.approved_at">
            <span class="tw-text-gray-600">Approval Date:</span>
            <span class="tw-ml-2 tw-font-medium">{{ formatDate(movement.approved_at) }}</span>
          </div>
          <div v-if="movement.approval_notes" class="tw-col-span-2">
            <span class="tw-text-gray-600">Approval Notes:</span>
            <p class="tw-mt-1 tw-text-gray-800">{{ movement.approval_notes }}</p>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="tw-flex tw-justify-end tw-space-x-3">
        <Button
          type="button"
          label="Close"
          icon="pi pi-times"
          class="p-button-text"
          @click="$emit('close')"
        />
        <Button
          v-if="movement.status === 'draft'"
          type="button"
          label="Edit Draft"
          icon="pi pi-pencil"
          class="tw-bg-blue-600"
          @click="$emit('edit-draft', movement)"
        />
        <Button
          v-if="canPrint"
          type="button"
          label="Print"
          icon="pi pi-print"
          class="tw-bg-gray-600"
          @click="handlePrint"
        />
      </div>
    </div>
  </Dialog>
</template>

<script>
import { computed } from 'vue';
import Dialog from 'primevue/dialog';
import Tag from 'primevue/tag';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';

export default {
  name: 'MovementDetailsDialog',
  components: {
    Dialog,
    Tag,
    DataTable,
    Column,
    Button
  },
  props: {
    visible: {
      type: Boolean,
      default: false
    },
    movement: {
      type: Object,
      default: null
    }
  },
  emits: ['update:visible', 'close', 'edit-draft'],
  setup(props, { emit }) {
    const canPrint = computed(() => {
      return props.movement && ['approved', 'in_transfer', 'completed'].includes(props.movement.status);
    });

    const getStatusSeverity = (status) => {
      const severities = {
        'draft': 'secondary',
        'pending': 'warning',
        'approved': 'info',
        'rejected': 'danger',
        'in_transfer': 'success',
        'completed': 'success'
      };
      return severities[status] || 'secondary';
    };

    const getProductIcon = (type) => {
      const icons = {
        'medical': 'pi pi-heart',
        'equipment': 'pi pi-cog',
        'consumable': 'pi pi-box',
        'pharmaceutical': 'pi pi-plus-circle'
      };
      return icons[type] || 'pi pi-box';
    };

    const getUrgencySeverity = (urgency) => {
      const severities = {
        'low': 'success',
        'normal': 'info',
        'high': 'warning',
        'urgent': 'danger'
      };
      return severities[urgency] || 'info';
    };

    const formatDate = (dateString) => {
      if (!dateString) return 'N/A';
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    };

    const handlePrint = () => {
      // Implement print functionality
      window.print();
    };

    return {
      canPrint,
      getStatusSeverity,
      getProductIcon,
      getUrgencySeverity,
      formatDate,
      handlePrint
    };
  }
};
</script>

<style scoped>
/* Dialog animations */
.p-dialog {
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Table styling */
.p-datatable .p-datatable-tbody > tr > td {
  padding: 0.75rem;
}

/* Gradient background */
.tw-bg-gradient-to-r {
  background: linear-gradient(to right, #eff6ff, #eef2ff);
}

/* Print styles */
@media print {
  .p-dialog-header,
  .tw-flex.tw-justify-end {
    display: none !important;
  }
  
  .p-dialog-content {
    padding: 0 !important;
  }
}

/* Responsive design */
@media (max-width: 768px) {
  .tw-grid-cols-2 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }
}
</style>