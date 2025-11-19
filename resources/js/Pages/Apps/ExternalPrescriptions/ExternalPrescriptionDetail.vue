<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-slate-50 tw-via-purple-50/30 tw-to-indigo-50/20 tw-p-6">
    <!-- Loading State -->
    <div v-if="loading" class="tw-flex tw-justify-center tw-items-center tw-h-96">
      <Card class="tw-w-full tw-max-w-md tw-border-0 tw-shadow-lg">
        <template #content>
          <div class="tw-text-center tw-py-8">
            <ProgressSpinner strokeWidth="4" animationDuration="1s" class="tw-w-16 tw-h-16" />
            <p class="tw-mt-4 tw-text-slate-600">Loading prescription...</p>
          </div>
        </template>
      </Card>
    </div>

    <!-- Main Content -->
    <div v-else-if="prescription" class="tw-space-y-6">
      <!-- Header -->
      <div class="tw-flex tw-flex-col lg:tw-flex-row tw-items-start lg:tw-items-center tw-justify-between tw-mb-6 tw-gap-4">
        <div>
          <div class="tw-flex tw-items-center tw-gap-3 tw-mb-3">
            <Button icon="pi pi-arrow-left" class="p-button-text" @click="goBack" />
            <h1 class="tw-text-4xl tw-font-bold tw-bg-gradient-to-r tw-from-slate-900 tw-to-slate-700 tw-bg-clip-text tw-text-transparent">
              {{ prescription.prescription_code }}
            </h1>
          </div>
          <p class="tw-text-slate-600 tw-ml-12">
            Status: 
            <Tag :value="getStatusLabel(prescription.status)" :severity="getStatusSeverity(prescription.status)" class="tw-ml-2" />
          </p>
        </div>
        <div class="tw-flex tw-gap-2">
          <Button icon="pi pi-print" label="Print PDF" @click="printPDF" class="p-button-info tw-rounded-xl" :loading="printingPDF" />
          <Button icon="pi pi-refresh" @click="reloadPrescription" class="p-button-secondary tw-rounded-xl" />
        </div>
      </div>

      <!-- Prescription Info Card -->
      <Card class="tw-border-0 tw-shadow-lg">
        <template #title>
          <div class="tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-info-circle tw-text-purple-600"></i>
            <span>Prescription Information</span>
          </div>
        </template>
        <template #content>
          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-6">
            <div class="tw-bg-gradient-to-br tw-from-blue-50 tw-to-cyan-50 tw-p-4 tw-rounded-lg tw-border tw-border-blue-200/50">
              <p class="tw-text-sm tw-text-slate-600 tw-mb-1">
                <i class="pi pi-user-md tw-mr-2 tw-text-blue-600"></i>Doctor
              </p>
              <p class="tw-text-lg tw-font-semibold tw-text-slate-900">{{ prescription.doctor?.name || 'N/A' }}</p>
            </div>

            <div class="tw-bg-gradient-to-br tw-from-purple-50 tw-to-indigo-50 tw-p-4 tw-rounded-lg tw-border tw-border-purple-200/50">
              <p class="tw-text-sm tw-text-slate-600 tw-mb-1">
                <i class="pi pi-calendar tw-mr-2 tw-text-purple-600"></i>Created
              </p>
              <p class="tw-text-lg tw-font-semibold tw-text-slate-900">{{ formatDate(prescription.created_at) }}</p>
            </div>

            <div class="tw-bg-gradient-to-br tw-from-green-50 tw-to-emerald-50 tw-p-4 tw-rounded-lg tw-border tw-border-green-200/50">
              <p class="tw-text-sm tw-text-slate-600 tw-mb-1">
                <i class="pi pi-boxes tw-mr-2 tw-text-green-600"></i>Total Items
              </p>
              <p class="tw-text-lg tw-font-semibold tw-text-slate-900">{{ prescription.total_items }}</p>
            </div>

            <div class="tw-bg-gradient-to-br tw-from-amber-50 tw-to-orange-50 tw-p-4 tw-rounded-lg tw-border tw-border-amber-200/50">
              <p class="tw-text-sm tw-text-slate-600 tw-mb-1">
                <i class="pi pi-check-circle tw-mr-2 tw-text-amber-600"></i>Dispensed
              </p>
              <p class="tw-text-lg tw-font-semibold tw-text-slate-900">{{ prescription.dispensed_items }} / {{ prescription.total_items }}</p>
            </div>
          </div>
        </template>
      </Card>

      <!-- Items Table -->
      <Card class="tw-border-0 tw-shadow-lg">
        <template #title>
          <div class="tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-boxes tw-text-purple-600"></i>
            <span>Prescription Items ({{ prescription.items?.length || 0 }})</span>
          </div>
        </template>
        <template #content>
          <DataTable :value="prescription.items || []" stripedRows class="p-datatable-sm">
            <template #empty>
              <div class="tw-text-center tw-py-8">
                <i class="pi pi-inbox tw-text-4xl tw-text-slate-300"></i>
                <p class="tw-text-slate-500 tw-mt-2">No items in this prescription</p>
              </div>
            </template>

            <Column field="product.name" header="Product Name">
              <template #body="slotProps">
                <div class="tw-font-semibold tw-text-slate-900">{{ slotProps.data.product?.name || slotProps.data.product_name }}</div>
              </template>
            </Column>

            <Column field="quantity" header="Quantity">
              <template #body="slotProps">
                <div class="tw-flex tw-items-center tw-gap-2">
                  <Tag :value="slotProps.data.quantity" severity="info" />
                  <span class="tw-text-sm tw-text-slate-500">{{ slotProps.data.unit }}</span>
                </div>
              </template>
            </Column>

            <Column field="quantity_sended" header="Dispensed">
              <template #body="slotProps">
                <div class="tw-flex tw-items-center tw-gap-2">
                  <Tag :value="slotProps.data.quantity_sended || 0" :severity="slotProps.data.quantity_sended === slotProps.data.quantity ? 'success' : 'warning'" />
                  <span class="tw-text-sm tw-text-slate-500">of {{ slotProps.data.quantity }}</span>
                </div>
              </template>
            </Column>

            <Column field="status" header="Status">
              <template #body="slotProps">
                <Tag :value="slotProps.data.status" :severity="getStatusSeverity(slotProps.data.status)" class="tw-rounded-lg" />
              </template>
            </Column>

            <Column header="Actions" v-if="prescription.status === 'draft'">
              <template #body="slotProps">
                <div class="tw-flex tw-gap-2">
                  <Button 
                    v-if="slotProps.data.status === 'draft'"
                    icon="pi pi-check" 
                    class="p-button-sm p-button-success p-button-rounded"
                    @click="dispenseItem(slotProps.data)"
                    v-tooltip.top="'Dispense this item'"
                  />
                  <Button 
                    v-if="slotProps.data.status === 'draft'"
                    icon="pi pi-times" 
                    class="p-button-sm p-button-danger p-button-rounded"
                    @click="cancelItem(slotProps.data)"
                    v-tooltip.top="'Cancel this item'"
                  />
                </div>
              </template>
            </Column>
          </DataTable>
        </template>
      </Card>

      <!-- Action Buttons -->
      <div class="tw-flex tw-gap-3 tw-justify-end">
        <Button 
          label="Back to List" 
          icon="pi pi-arrow-left" 
          @click="goBack" 
          class="p-button-secondary tw-rounded-xl"
        />
        <Button 
          v-if="prescription.status === 'draft' && allItemsProcessed"
          label="Confirm Prescription" 
          icon="pi pi-check" 
          @click="confirmPrescription" 
          class="p-button-success tw-rounded-xl"
          :loading="confirming"
        />
      </div>
    </div>

    <!-- Not Found -->
    <div v-else class="tw-flex tw-justify-center tw-items-center tw-h-96">
      <Card class="tw-w-full tw-max-w-md tw-border-0 tw-shadow-lg">
        <template #content>
          <div class="tw-text-center tw-py-8">
            <i class="pi pi-exclamation-circle tw-text-6xl tw-text-red-500 tw-mb-4"></i>
            <p class="tw-text-slate-600 tw-text-lg">Prescription not found</p>
            <Button label="Go Back" icon="pi pi-arrow-left" @click="goBack" class="p-button-secondary tw-mt-4" />
          </div>
        </template>
      </Card>
    </div>

    <!-- Dispense Dialog -->
    <Dialog v-model:visible="showDispenseDialog" header="Dispense Item" :modal="true" class="tw-w-[500px]">
      <div class="tw-space-y-4">
        <div>
          <p class="tw-text-sm tw-text-slate-600 tw-mb-1">Product</p>
          <p class="tw-text-lg tw-font-semibold">{{ selectedItem?.product?.name }}</p>
        </div>

        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-slate-700 tw-mb-2">
            Select Service <span class="tw-text-red-500">*</span>
          </label>
          <Dropdown
            v-model="dispenseForm.service_id"
            :options="services"
            optionLabel="name"
            optionValue="id"
            placeholder="Select service"
            class="tw-w-full"
          />
          <small class="tw-text-slate-500">Service is required for inventory tracking</small>
        </div>

        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-slate-700 tw-mb-2">Quantity to Dispense</label>
          <InputNumber
            v-model="dispenseForm.quantity"
            :min="1"
            :max="selectedItem?.quantity || 1"
            class="tw-w-full"
          />
        </div>
      </div>

      <template #footer>
        <Button label="Cancel" icon="pi pi-times" class="p-button-text" @click="showDispenseDialog = false" />
        <Button label="Dispense" icon="pi pi-check" class="p-button-success" @click="confirmDispense" :loading="dispensing" />
      </template>
    </Dialog>

    <!-- Cancel Dialog -->
    <Dialog v-model:visible="showCancelDialog" header="Cancel Item" :modal="true" class="tw-w-[500px]">
      <div class="tw-space-y-4">
        <div>
          <p class="tw-text-sm tw-text-slate-600 tw-mb-1">Product</p>
          <p class="tw-text-lg tw-font-semibold">{{ selectedItem?.product?.name }}</p>
        </div>

        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-slate-700 tw-mb-2">
            Reason for Cancellation <span class="tw-text-red-500">*</span>
          </label>
          <Dropdown
            v-model="cancelForm.reason"
            :options="cancelReasons"
            optionLabel="label"
            optionValue="value"
            placeholder="Select reason"
            class="tw-w-full"
          />
        </div>

        <div v-if="cancelForm.reason === 'custom'">
          <label class="tw-block tw-text-sm tw-font-medium tw-text-slate-700 tw-mb-2">Additional Details</label>
          <Textarea v-model="cancelForm.customReason" rows="3" placeholder="Please provide details..." class="tw-w-full" />
        </div>
      </div>

      <template #footer>
        <Button label="Cancel" icon="pi pi-times" class="p-button-text" @click="showCancelDialog = false" />
        <Button label="Confirm Cancel" icon="pi pi-check" class="p-button-danger" @click="confirmCancel" :loading="cancelling" />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import Card from 'primevue/card';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import ProgressSpinner from 'primevue/progressspinner';
import externalPrescriptionService from '@/services/Pharmacy/externalPrescriptionService';

const router = useRouter();
const route = useRoute();
const toast = useToast();

// State
const prescription = ref(null);
const loading = ref(false);
const printingPDF = ref(false);
const confirming = ref(false);
const dispensing = ref(false);
const cancelling = ref(false);

const showDispenseDialog = ref(false);
const showCancelDialog = ref(false);
const selectedItem = ref(null);

const dispenseForm = ref({
  service_id: null,
  quantity: 1,
});

const cancelForm = ref({
  reason: null,
  customReason: '',
});

const services = ref([
  { id: 1, name: 'General Practice' },
  { id: 2, name: 'Laboratory' },
  { id: 3, name: 'Pharmacy' },
]);

const cancelReasons = ref([
  { label: 'Did not find product', value: 'did_not_find' },
  { label: 'Out of stock', value: 'out_of_stock' },
  { label: 'Defective product', value: 'defective' },
  { label: 'Other (please specify)', value: 'custom' },
]);

// Computed
const allItemsProcessed = computed(() => {
  if (!prescription.value?.items) return false;
  return prescription.value.items.every(item => 
    item.status === 'dispensed' || item.status === 'cancelled'
  );
});

// Methods
const loadPrescription = async () => {
  loading.value = true;
  try {
    const response = await externalPrescriptionService.getExternalPrescription(route.params.id);
    prescription.value = response.data;
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load prescription',
      life: 3000,
    });
  } finally {
    loading.value = false;
  }
};

const reloadPrescription = () => {
  loadPrescription();
};

const dispenseItem = (item) => {
  selectedItem.value = item;
  dispenseForm.value = {
    service_id: null,
    quantity: item.quantity,
  };
  showDispenseDialog.value = true;
};

const confirmDispense = async () => {
  if (!dispenseForm.value.service_id) {
    toast.add({
      severity: 'warn',
      summary: 'Validation Error',
      detail: 'Please select a service',
      life: 3000,
    });
    return;
  }

  dispensing.value = true;
  try {
    await externalPrescriptionService.dispenseItem(prescription.value.id, selectedItem.value.id, {
      quantity_sended: dispenseForm.value.quantity,
      service_id: dispenseForm.value.service_id,
    });

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Item dispensed successfully',
      life: 3000,
    });

    showDispenseDialog.value = false;
    reloadPrescription();
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to dispense item',
      life: 3000,
    });
  } finally {
    dispensing.value = false;
  }
};

const cancelItem = (item) => {
  selectedItem.value = item;
  cancelForm.value = {
    reason: null,
    customReason: '',
  };
  showCancelDialog.value = true;
};

const confirmCancel = async () => {
  if (!cancelForm.value.reason) {
    toast.add({
      severity: 'warn',
      summary: 'Validation Error',
      detail: 'Please select a reason',
      life: 3000,
    });
    return;
  }

  cancelling.value = true;
  try {
    const reason = cancelForm.value.reason === 'custom' 
      ? cancelForm.value.customReason 
      : cancelForm.value.reason;

    await externalPrescriptionService.cancelItem(prescription.value.id, selectedItem.value.id, {
      cancel_reason: reason,
    });

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Item cancelled successfully',
      life: 3000,
    });

    showCancelDialog.value = false;
    reloadPrescription();
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to cancel item',
      life: 3000,
    });
  } finally {
    cancelling.value = false;
  }
};

const confirmPrescription = async () => {
  confirming.value = true;
  try {
    await externalPrescriptionService.updateExternalPrescription(prescription.value.id, {
      status: 'confirmed',
    });

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Prescription confirmed successfully',
      life: 3000,
    });

    reloadPrescription();
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to confirm prescription',
      life: 3000,
    });
  } finally {
    confirming.value = false;
  }
};

const printPDF = async () => {
  printingPDF.value = true;
  try {
    const pdfBlob = await externalPrescriptionService.generatePDF(route.params.id);
    const url = window.URL.createObjectURL(pdfBlob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `${prescription.value.prescription_code}.pdf`;
    link.click();
    window.URL.revokeObjectURL(url);

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'PDF downloaded successfully',
      life: 3000,
    });
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to generate PDF',
      life: 3000,
    });
  } finally {
    printingPDF.value = false;
  }
};

const goBack = () => {
  router.push('/external-prescriptions/list');
};

const getStatusLabel = (status) => {
  const labels = {
    draft: 'Draft',
    confirmed: 'Confirmed',
    cancelled: 'Cancelled',
  };
  return labels[status] || status;
};

const getStatusSeverity = (status) => {
  const severities = {
    draft: 'warning',
    dispensed: 'success',
    cancelled: 'danger',
    confirmed: 'info',
  };
  return severities[status] || 'info';
};

const formatDate = (date) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

// Lifecycle
onMounted(() => {
  loadPrescription();
});
</script>
