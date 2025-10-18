<template>
  <Dialog :visible="visible" @update:visible="$emit('update:visible', $event)" modal header="Edit Draft Request" :style="{width: '80rem'}">
    <div class="tw-space-y-6" v-if="draft">
      <!-- Request Details -->
      <div class="tw-grid tw-grid-cols-2 tw-gap-6">
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-mb-2">Service Provider</label>
          <InputText
            :value="draft.providing_service?.name || 'N/A'"
            readonly
            class="tw-w-full tw-bg-gray-50"
          />
        </div>

        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-mb-2">Expected Delivery Date</label>
          <Calendar
            v-model="formData.expected_delivery_date"
            dateFormat="yy-mm-dd"
            class="tw-w-full"
            :minDate="minDate"
            placeholder="Optional"
          />
        </div>
      </div>

      <div>
        <label class="tw-block tw-text-sm tw-font-medium tw-mb-2">Request Reason *</label>
        <Textarea
          v-model="formData.request_reason"
          rows="3"
          class="tw-w-full"
          placeholder="Please explain why you need these products..."
        />
      </div>

      <!-- Products Section -->
      <div>
        <div class="tw-flex tw-justify-between tw-items-center tw-mb-4">
          <h3 class="tw-text-lg tw-font-semibold">Products</h3>
          <Button
            type="button"
            label="Add Product"
            icon="pi pi-plus"
            class="tw-bg-green-600"
            @click="$emit('add-product')"
          />
        </div>

        <DataTable
          :value="draft.products || []"
          class="tw-w-full"
          :paginator="false"
          emptyMessage="No products added yet"
        >
          <Column field="name" header="Product Name">
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-space-x-2">
                <i :class="getProductIcon(slotProps.data.type)" class="tw-text-blue-600"></i>
                <span>{{ slotProps.data.name }}</span>
              </div>
            </template>
          </Column>
          <Column field="pivot.quantity" header="Quantity">
            <template #body="slotProps">
              <span class="tw-font-medium">{{ slotProps.data.pivot?.quantity || 0 }}</span>
            </template>
          </Column>
          <Column field="unit" header="Unit">
            <template #body="slotProps">
              <Tag :value="slotProps.data.unit" class="tw-bg-gray-100 tw-text-gray-800" />
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
          <Column header="Actions" style="width: 100px">
            <template #body="slotProps">
              <Button
                icon="pi pi-trash"
                class="p-button-rounded p-button-text p-button-danger"
                @click="$emit('remove-product', slotProps.data.id)"
                v-tooltip.top="'Remove Product'"
              />
            </template>
          </Column>
        </DataTable>
      </div>

      <!-- Action Buttons -->
      <div class="tw-flex tw-justify-end tw-space-x-3">
        <Button
          type="button"
          label="Cancel"
          icon="pi pi-times"
          class="p-button-text"
          @click="handleClose"
        />
        <Button
          type="button"
          label="Save Draft"
          icon="pi pi-save"
          class="tw-bg-blue-600"
          :loading="savingDraft"
          @click="handleSaveDraft"
          :disabled="!isFormValid"
        />
        <Button
          type="button"
          label="Send Request"
          icon="pi pi-send"
          class="tw-bg-green-600"
          :loading="sendingRequest"
          @click="handleSendRequest"
          :disabled="!canSendRequest"
        />
      </div>
    </div>
  </Dialog>
</template>

<script>
import { computed, reactive, watch } from 'vue';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Calendar from 'primevue/calendar';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';

export default {
  name: 'EditDraftDialog',
  components: {
    Dialog,
    InputText,
    Calendar,
    Textarea,
    Button,
    DataTable,
    Column,
    Tag
  },
  props: {
    visible: {
      type: Boolean,
      default: false
    },
    draft: {
      type: Object,
      default: null
    },
    savingDraft: {
      type: Boolean,
      default: false
    },
    sendingRequest: {
      type: Boolean,
      default: false
    }
  },
  emits: ['update:visible', 'save-draft', 'send-request', 'add-product', 'remove-product', 'close'],
  setup(props, { emit }) {
    const formData = reactive({
      request_reason: '',
      expected_delivery_date: null
    });

    const minDate = computed(() => new Date());
    
    const isFormValid = computed(() => {
      return formData.request_reason.trim();
    });

    const canSendRequest = computed(() => {
      return isFormValid.value && props.draft?.products?.length > 0;
    });

    const handleSaveDraft = () => {
      if (isFormValid.value) {
        emit('save-draft', { ...formData });
      }
    };

    const handleSendRequest = () => {
      if (canSendRequest.value) {
        emit('send-request', { ...formData });
      }
    };

    const handleClose = () => {
      emit('close');
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

    // Watch for draft changes to update form data
    watch(() => props.draft, (newDraft) => {
      if (newDraft) {
        formData.request_reason = newDraft.request_reason || '';
        formData.expected_delivery_date = newDraft.expected_delivery_date ? new Date(newDraft.expected_delivery_date) : null;
      }
    }, { immediate: true });

    // Watch for dialog close to reset form
    watch(() => props.visible, (newVal) => {
      if (!newVal) {
        Object.assign(formData, {
          request_reason: '',
          expected_delivery_date: null
        });
      }
    });

    return {
      formData,
      minDate,
      isFormValid,
      canSendRequest,
      handleSaveDraft,
      handleSendRequest,
      handleClose,
      getProductIcon,
      getUrgencySeverity
    };
  }
};
</script>

<style scoped>
/* Form input enhancements */
.p-inputtext:focus,
.p-textarea:focus,
.p-dropdown:focus {
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
  border-color: rgb(59, 130, 246);
}

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

/* Responsive design */
@media (max-width: 768px) {
  .tw-grid-cols-2 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }
}
</style>