<script setup>
import { ref, onMounted, reactive } from 'vue';
import { requestTransactionApprovalService } from '/resources/js/Components/Apps/services/Coffre/RequestTransactionApprovalService.js';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

// PrimeVue Components
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Card from 'primevue/card';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Calendar from 'primevue/calendar';
import Textarea from 'primevue/textarea';
import FileUpload from 'primevue/fileupload';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';

// Composables
const toast = useToast();
const confirm = useConfirm();

// Reactive state
const pendingApprovals = ref([]);
const loading = ref(false);
const showApprovalModal = ref(false);
const selectedApproval = ref(null);
const submitting = ref(false);
const errors = ref({});

// Validation form data
const validationForm = reactive({
  Payment_date: new Date(),
  reference_validation: '',
  Attachment_validation: null,
  Reason_validation: ''
});

// File upload reference
const fileUpload = ref(null);

// Methods
const onFileSelect = (event) => {
  const file = event.files[0];
  if (file) {
    validationForm.Attachment_validation = file;
  }
};

const onFileRemove = () => {
  validationForm.Attachment_validation = null;
  if (fileUpload.value) {
    fileUpload.value.clear();
  }
};

const onFileClear = () => {
  validationForm.Attachment_validation = null;
};

const loadPendingApprovals = async () => {
  loading.value = true;
  try {
    const result = await requestTransactionApprovalService.getPendingApprovals();
    if (result.success) {
      pendingApprovals.value = result.data || [];
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: result.message || 'Failed to load pending approvals',
        life: 4000
      });
    }
  } catch (error) {
    console.error('Error loading pending approvals:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'An error occurred while loading pending approvals',
      life: 4000
    });
  } finally {
    loading.value = false;
  }
};

const approveRequest = async (approval) => {
  selectedApproval.value = approval;
  // Reset form
  validationForm.Payment_date = new Date();
  validationForm.reference_validation = `REF-${Date.now()}`;
  validationForm.Attachment_validation = null;
  validationForm.Reason_validation = '';
  errors.value = {};
  
  // Clear file upload component
  if (fileUpload.value) {
    fileUpload.value.clear();
  }
  
  showApprovalModal.value = true;
};

const confirmApproval = async () => {
  submitting.value = true;
  errors.value = {};
  
  try {
    // Prepare validation data as FormData
    const formData = new FormData();
    
    // Add form fields
    if (validationForm.Payment_date) {
      const formattedDate = validationForm.Payment_date instanceof Date 
        ? validationForm.Payment_date.toISOString().split('T')[0]
        : validationForm.Payment_date;
      formData.append('Payment_date', formattedDate);
    }
    
    if (validationForm.reference_validation) {
      formData.append('reference_validation', validationForm.reference_validation);
    }
    
    if (validationForm.Reason_validation) {
      formData.append('Reason_validation', validationForm.Reason_validation);
    }
    
    // Add file if selected
    if (validationForm.Attachment_validation instanceof File) {
      formData.append('Attachment_validation', validationForm.Attachment_validation);
    }

    const result = await requestTransactionApprovalService.approve(selectedApproval.value.id, formData);
    
    if (result.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: result.message || 'Transaction approved successfully',
        life: 4000
      });
      
      // Close modal and reset
      cancelApproval();
      
      // Reload the list
      await loadPendingApprovals();
    } else {
      if (result.errors && Object.keys(result.errors).length > 0) {
        errors.value = result.errors;
        
        // Show first error in toast
        const firstError = Object.values(result.errors)[0];
        toast.add({
          severity: 'error',
          summary: 'Validation Error',
          detail: Array.isArray(firstError) ? firstError[0] : firstError,
          life: 4000
        });
      } else {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: result.message || 'Failed to approve transaction',
          life: 4000
        });
      }
    }
  } catch (error) {
    console.error('Error approving transaction:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'An error occurred while approving the transaction',
      life: 4000
    });
  } finally {
    submitting.value = false;
  }
};

const cancelApproval = () => {
  showApprovalModal.value = false;
  selectedApproval.value = null;
  errors.value = {};
  
  // Reset form
  validationForm.Payment_date = new Date();
  validationForm.reference_validation = '';
  validationForm.Attachment_validation = null;
  validationForm.Reason_validation = '';
  
  // Clear file upload component
  if (fileUpload.value) {
    fileUpload.value.clear();
  }
};

const confirmRejectRequest = (approval) => {
  confirm.require({
    message: 'Are you sure you want to reject this transaction? This action cannot be undone.',
    header: 'Confirm Rejection',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    acceptLabel: 'Reject',
    rejectLabel: 'Cancel',
    accept: () => rejectRequest(approval)
  });
};

const rejectRequest = async (approval) => {
  try {
    const result = await requestTransactionApprovalService.reject(approval.id);
    if (result.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: result.message || 'Transaction rejected successfully',
        life: 4000
      });
      
      // Reload the list
      await loadPendingApprovals();
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: result.message || 'Failed to reject transaction',
        life: 4000
      });
    }
  } catch (error) {
    console.error('Error rejecting transaction:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'An error occurred while rejecting the transaction',
      life: 4000
    });
  }
};

const formatAmount = (amount) => {
  return new Intl.NumberFormat('fr-DZ', {
    style: 'currency',
    currency: 'DZD',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount);
};

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('fr-DZ', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

// Form validation
const isFormValid = computed(() => {
  return validationForm.Payment_date && validationForm.reference_validation;
});

// Lifecycle
onMounted(() => {
  loadPendingApprovals();
});
</script>

<template>
  <Card class="tw-shadow-lg">
    <template #title>
      <div class="tw-flex tw-items-center tw-gap-3">
        <i class="pi pi-clock tw-text-orange-500"></i>
        <span>Pending Approvals</span>
        <Tag v-if="pendingApprovals.length > 0" :value="pendingApprovals.length" severity="warning" />
      </div>
    </template>
    
    <template #content>
      <DataTable
        :value="pendingApprovals"
        :loading="loading"
        paginator
        :rows="10"
        dataKey="id"
        :globalFilterFields="['transaction.description', 'requested.name']"
        emptyMessage="No pending approvals found."
        class="p-datatable-sm"
      >
        <Column field="transaction.amount" header="Amount" sortable>
          <template #body="{ data }">
            <span class="tw-font-semibold tw-text-green-600">
              {{ formatAmount(data.transaction.amount) }}
            </span>
          </template>
        </Column>

        <Column field="transaction.description" header="Description">
          <template #body="{ data }">
            <span class="tw-truncate tw-max-w-xs tw-block" :title="data.transaction.description">
              {{ data.transaction.description || 'No description' }}
            </span>
          </template>
        </Column>

        <Column field="transaction.coffre.name" header="From Coffre">
          <template #body="{ data }">
            <div class="tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-lock tw-text-gray-500"></i>
              <span>{{ data.transaction.coffre?.name || 'Unknown' }}</span>
            </div>
          </template>
        </Column>

        <Column field="requested.name" header="Requested By">
          <template #body="{ data }">
            <div class="tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-user tw-text-blue-500"></i>
              <span>{{ data.requested?.name || 'Unknown' }}</span>
            </div>
          </template>
        </Column>

        <Column field="created_at" header="Requested At" sortable>
          <template #body="{ data }">
            <span class="tw-text-sm tw-text-gray-600">
              {{ formatDate(data.created_at) }}
            </span>
          </template>
        </Column>

        <Column field="transaction.bank_account_id" header="Bank Transfer" sortable>
          <template #body="{ data }">
            <Tag 
              v-if="data.transaction.bank_account_id" 
              value="Yes" 
              severity="info" 
              icon="pi pi-building"
            />
            <Tag 
              v-else 
              value="No" 
              severity="secondary"
            />
          </template>
        </Column>

        <Column header="Actions" :exportable="false">
          <template #body="{ data }">
            <div class="tw-flex tw-gap-2">
              <Button
                icon="pi pi-check"
                label="Approve"
                size="small"
                class="p-button-success p-button-sm"
                @click="approveRequest(data)"
              />
              <Button
                icon="pi pi-times"
                label="Reject"
                size="small"
                class="p-button-danger p-button-outlined p-button-sm"
                @click="confirmRejectRequest(data)"
              />
            </div>
          </template>
        </Column>
      </DataTable>

      <div v-if="!loading && pendingApprovals.length === 0" class="tw-text-center tw-py-8">
        <i class="pi pi-check-circle tw-text-4xl tw-text-green-500 tw-mb-4"></i>
        <h3 class="tw-text-gray-600 tw-mb-2">No Pending Approvals</h3>
        <p class="tw-text-gray-500">All transactions have been processed.</p>
      </div>
    </template>
  </Card>

  <!-- Approval Modal -->
  <Dialog
    v-model:visible="showApprovalModal"
    :modal="true"
    header="Approve Transaction"
    :style="{ width: '700px' }"
    class="p-fluid"
    :closable="!submitting"
  >
    <div v-if="selectedApproval" class="tw-space-y-6">
      <!-- Transaction Summary -->
      <div class="tw-bg-gray-50 tw-p-4 tw-rounded-lg">
        <h4 class="tw-text-lg tw-font-semibold tw-text-gray-800 tw-mb-3">Transaction Details</h4>
        <div class="tw-grid tw-grid-cols-2 tw-gap-4 tw-text-sm">
          <div>
            <span class="tw-font-medium tw-text-gray-600">Amount:</span>
            <span class="tw-ml-2 tw-font-semibold tw-text-green-600">
              {{ formatAmount(selectedApproval.transaction.amount) }}
            </span>
          </div>
          <div>
            <span class="tw-font-medium tw-text-gray-600">From Coffre:</span>
            <span class="tw-ml-2">{{ selectedApproval.transaction.coffre?.name || 'Unknown' }}</span>
          </div>
          <div class="tw-col-span-2">
            <span class="tw-font-medium tw-text-gray-600">Description:</span>
            <span class="tw-ml-2">{{ selectedApproval.transaction.description || 'No description' }}</span>
          </div>
        </div>
      </div>

      <!-- Validation Fields Form -->
      <div class="tw-space-y-4">
        <h4 class="tw-text-lg tw-font-semibold tw-text-gray-800">Validation Information</h4>
        
        <!-- Payment Date -->
        <div class="tw-space-y-2">
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">
            Payment Date <span class="tw-text-red-500">*</span>
          </label>
          <Calendar
            v-model="validationForm.Payment_date"
            dateFormat="yy-mm-dd"
            showIcon
            :class="{ 'p-invalid': errors.Payment_date }"
            placeholder="Select payment date"
            :disabled="submitting"
          />
          <small v-if="errors.Payment_date" class="p-error">
            {{ Array.isArray(errors.Payment_date) ? errors.Payment_date[0] : errors.Payment_date }}
          </small>
        </div>

        <!-- Reference Validation -->
        <div class="tw-space-y-2">
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">
            Reference Validation <span class="tw-text-red-500">*</span>
          </label>
          <InputText
            v-model="validationForm.reference_validation"
            :class="{ 'p-invalid': errors.reference_validation }"
            placeholder="Enter validation reference"
            :disabled="submitting"
          />
          <small v-if="errors.reference_validation" class="p-error">
            {{ Array.isArray(errors.reference_validation) ? errors.reference_validation[0] : errors.reference_validation }}
          </small>
        </div>

        <!-- Reason Validation -->
        <div class="tw-space-y-2">
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">Reason for Validation</label>
          <Textarea
            v-model="validationForm.Reason_validation"
            :class="{ 'p-invalid': errors.Reason_validation }"
            placeholder="Enter reason for validation"
            rows="3"
            autoResize
            :disabled="submitting"
          />
          <small v-if="errors.Reason_validation" class="p-error">
            {{ Array.isArray(errors.Reason_validation) ? errors.Reason_validation[0] : errors.Reason_validation }}
          </small>
        </div>

        <!-- Attachment Validation -->
        <div class="tw-space-y-2">
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">Validation Attachment (Optional)</label>
          <FileUpload
            ref="fileUpload"
            mode="basic"
            :multiple="false"
            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
            :maxFileSize="10485760"
            :auto="false"
            chooseLabel="Choose File"
            :class="{ 'p-invalid': errors.Attachment_validation }"
            @select="onFileSelect"
            @remove="onFileRemove"
            @clear="onFileClear"
            :showUploadButton="false"
            :showCancelButton="false"
            :disabled="submitting"
          />
          
          <!-- Display selected file -->
          <div v-if="validationForm.Attachment_validation" class="tw-mt-2 tw-p-2 tw-bg-blue-50 tw-rounded tw-flex tw-items-center tw-justify-between">
            <div class="tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-file tw-text-blue-600"></i>
              <span class="tw-text-sm tw-text-blue-800">{{ validationForm.Attachment_validation.name }}</span>
              <span class="tw-text-xs tw-text-gray-500">({{ Math.round(validationForm.Attachment_validation.size / 1024) }} KB)</span>
            </div>
            <Button
              icon="pi pi-times"
              class="p-button-text p-button-sm tw-text-red-500"
              @click="onFileRemove"
              :disabled="submitting"
            />
          </div>
          
          <small class="tw-text-xs tw-text-gray-500">Supported formats: PDF, JPG, PNG, DOC, DOCX (Max 10MB)</small>
          <small v-if="errors.Attachment_validation" class="p-error">
            {{ Array.isArray(errors.Attachment_validation) ? errors.Attachment_validation[0] : errors.Attachment_validation }}
          </small>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="tw-flex tw-justify-end tw-gap-3">
        <Button
          label="Cancel"
          icon="pi pi-times"
          class="p-button-text"
          @click="cancelApproval"
          :disabled="submitting"
        />
        <Button
          label="Approve Transaction"
          icon="pi pi-check"
          class="p-button-success"
          @click="confirmApproval"
          :loading="submitting"
          :disabled="!isFormValid"
        />
      </div>
    </template>
  </Dialog>

  <Toast />
  <ConfirmDialog />
</template>



<style scoped>
/* Additional styling for the approval component */
:deep(.p-datatable-thead > tr > th) {
  background-color: #f8fafc;
  font-weight: 600;
}

:deep(.p-button-sm) {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
}

/* Modal styling */
:deep(.p-dialog .p-dialog-header) {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  border-top-left-radius: 0.75rem;
  border-top-right-radius: 0.75rem;
}

:deep(.p-dialog .p-dialog-content) {
  padding: 1.5rem;
}

:deep(.p-dialog .p-dialog-footer) {
  padding: 1rem 1.5rem;
  background-color: #f9fafb;
  border-bottom-left-radius: 0.75rem;
  border-bottom-right-radius: 0.75rem;
}

/* Form styling */
:deep(.p-calendar),
:deep(.p-inputtext),
:deep(.p-inputtextarea) {
  border-radius: 0.5rem;
  border: 1px solid #d1d5db;
  transition: all 0.2s ease;
}

:deep(.p-calendar:not(.p-disabled):hover),
:deep(.p-inputtext:hover),
:deep(.p-inputtextarea:hover) {
  border-color: #10b981;
}

:deep(.p-calendar:not(.p-disabled).p-focus),
:deep(.p-inputtext:focus),
:deep(.p-inputtextarea:focus) {
  border-color: #10b981;
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

:deep(.p-invalid) {
  border-color: #ef4444 !important;
}

:deep(.p-invalid:focus) {
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
}

/* File upload styling */
:deep(.p-fileupload) {
  border-radius: 0.5rem;
  border: 1px solid #d1d5db;
  transition: all 0.2s ease;
}

:deep(.p-fileupload:hover) {
  border-color: #10b981;
}

:deep(.p-fileupload.p-focus) {
  border-color: #10b981;
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

:deep(.p-fileupload .p-button) {
  background-color: #10b981;
  border-color: #10b981;
  border-radius: 0.375rem;
}

:deep(.p-fileupload .p-button:hover) {
  background-color: #059669;
  border-color: #059669;
}

:deep(.p-fileupload-filename) {
  color: #374151;
  font-size: 0.875rem;
}
</style>
