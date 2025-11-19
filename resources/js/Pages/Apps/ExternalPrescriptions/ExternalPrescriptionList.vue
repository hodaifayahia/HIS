<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-slate-50 tw-via-purple-50/30 tw-to-indigo-50/20">
    <!-- Header -->
    <div class="tw-bg-gradient-to-r tw-from-white tw-via-purple-50/50 tw-to-indigo-50/30 tw-border-b tw-border-slate-200/60 tw-sticky tw-top-0 tw-z-10 tw-shadow-lg tw-backdrop-blur-sm">
      <div class="tw-px-6 tw-py-6">
        <div class="tw-flex tw-flex-col lg:tw-flex-row tw-justify-between tw-items-start lg:tw-items-center tw-gap-6">
          <div class="tw-flex tw-items-center tw-gap-4">
            <div class="tw-relative">
              <div class="tw-bg-gradient-to-br tw-from-purple-500 tw-to-indigo-600 tw-p-3 tw-rounded-xl tw-shadow-lg">
                <i class="pi pi-prescription tw-text-white tw-text-2xl"></i>
              </div>
            </div>
            <div>
              <h1 class="tw-text-3xl tw-font-bold tw-bg-gradient-to-r tw-from-slate-900 tw-to-slate-700 tw-bg-clip-text tw-text-transparent">
                External Prescriptions
              </h1>
              <p class="tw-text-slate-600 tw-text-sm tw-mt-1">Manage doctor prescriptions for external pharmacies</p>
            </div>
          </div>

          <!-- Stats Cards -->
          <div class="tw-flex tw-flex-wrap tw-gap-4">
            <div class="tw-bg-gradient-to-br tw-from-amber-50 tw-to-orange-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-amber-200/60">
              <div class="tw-text-xs tw-text-amber-700 tw-font-medium">Draft</div>
              <div class="tw-text-2xl tw-font-bold tw-text-amber-800">{{ prescriptions.filter(p => p.status === 'draft').length }}</div>
            </div>
            <div class="tw-bg-gradient-to-br tw-from-green-50 tw-to-emerald-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-green-200/60">
              <div class="tw-text-xs tw-text-green-700 tw-font-medium">Confirmed</div>
              <div class="tw-text-2xl tw-font-bold tw-text-green-800">{{ prescriptions.filter(p => p.status === 'confirmed').length }}</div>
            </div>

            <div class="tw-bg-gradient-to-br tw-from-red-50 tw-to-rose-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-red-200/60 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-relative">
                  <div class="tw-bg-red-100 tw-p-2 tw-rounded-lg">
                    <i class="pi pi-times-circle tw-text-red-600 tw-text-lg"></i>
                  </div>
                  <div class="tw-absolute -tw-top-1 -tw-right-1 tw-w-3 tw-h-3 tw-bg-red-400 tw-rounded-full tw-border tw-border-white"></div>
                </div>
                <div>
                  <div class="tw-text-xs tw-text-red-700 tw-font-medium tw-uppercase tw-tracking-wide">Cancelled</div>
                  <div class="tw-text-2xl tw-font-bold tw-text-red-800">
                    {{ prescriptions.filter(p => p.status === 'cancelled').length }}
                  </div>
                </div>
              </div>
            </div>

            <div class="tw-bg-gradient-to-br tw-from-slate-50 tw-to-gray-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-slate-200/60 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-bg-slate-100 tw-p-2 tw-rounded-lg">
                  <i class="pi pi-chart-line tw-text-slate-600 tw-text-lg"></i>
                </div>
                <div>
                  <div class="tw-text-xs tw-text-slate-700 tw-font-medium tw-uppercase tw-tracking-wide">Total</div>
                  <div class="tw-text-2xl tw-font-bold tw-text-slate-800">
                    {{ prescriptions.length }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="tw-px-6 tw-py-6">
      <!-- Enhanced Action Toolbar -->
      <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-border tw-border-slate-200/60 tw-p-6 tw-mb-8 tw-backdrop-blur-sm">
        <div class="tw-flex tw-flex-col xl:tw-flex-row tw-justify-between tw-items-start xl:tw-items-center tw-gap-6">
          <!-- Enhanced Filters Section -->
          <div class="tw-flex tw-flex-col sm:tw-flex-row tw-gap-4 tw-flex-1">
            <!-- Search with Enhanced Design -->
            <div class="tw-relative tw-flex-1 tw-min-w-[250px] tw-max-w-[400px]">
              <div class="tw-absolute tw-inset-y-0 tw-left-0 tw-pl-4 tw-flex tw-items-center tw-pointer-events-none">
                <i class="pi pi-search tw-text-slate-400 tw-text-lg"></i>
              </div>
              <InputText
                v-model="filters.search"
                placeholder="Search by prescription code, doctor..."
                class="tw-w-full tw-pl-12 tw-pr-4 tw-py-3 tw-border tw-border-slate-200 tw-rounded-xl focus:tw-border-purple-500 focus:tw-ring-2 focus:tw-ring-purple-500/20 focus:tw-outline-none tw-transition-all tw-duration-200 tw-bg-slate-50/50 hover:tw-bg-white"
                @input="debouncedSearch"
              />
              <div v-if="filters.search" class="tw-absolute tw-inset-y-0 tw-right-0 tw-pr-4 tw-flex tw-items-center">
                <Button
                  @click="clearSearch"
                  icon="pi pi-times"
                  class="p-button-text p-button-sm p-button-rounded tw-text-slate-400 hover:tw-text-slate-600"
                  v-tooltip.top="'Clear search'"
                />
              </div>
            </div>

            <!-- Filter Dropdowns -->
            <div class="tw-flex tw-flex-wrap tw-gap-3">
              <Dropdown
                v-model="filters.status"
                :options="statusOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="All Status"
                class="tw-min-w-[160px] tw-border-slate-200 tw-rounded-xl focus:tw-border-purple-500 focus:tw-ring-2 focus:tw-ring-purple-500/20"
                @change="applyFilters"
              >
                <template #value="slotProps">
                  <div v-if="slotProps.value" class="tw-flex tw-items-center tw-gap-2">
                    <span class="tw-w-3 tw-h-3 tw-rounded-full" 
                          :class="getStatusColor(slotProps.value)"></span>
                    <span class="tw-font-medium">{{ getStatusLabel({ status: slotProps.value }) }}</span>
                  </div>
                  <span v-else class="tw-text-slate-500">All Status</span>
                </template>
                <template #option="slotProps">
                  <div class="tw-flex tw-items-center tw-gap-3 tw-py-2">
                    <span class="tw-w-3 tw-h-3 tw-rounded-full" 
                          :class="getStatusColor(slotProps.option.value)"></span>
                    <span>{{ slotProps.option.label }}</span>
                  </div>
                </template>
              </Dropdown>

              <!-- Quick Filter Buttons -->
              <div class="tw-flex tw-gap-2">
                <Button
                  @click="setQuickFilter('draft')"
                  :class="filters.status === 'draft' ? 'p-button-primary' : 'p-button-outlined p-button-secondary'"
                  class="p-button-sm tw-rounded-xl"
                  label="Draft"
                />
                <Button
                  @click="setQuickFilter('confirmed')"
                  :class="filters.status === 'confirmed' ? 'p-button-primary' : 'p-button-outlined p-button-secondary'"
                  class="p-button-sm tw-rounded-xl"
                  label="Confirmed"
                />
                <Button
                  @click="setQuickFilter('cancelled')"
                  :class="filters.status === 'cancelled' ? 'p-button-primary' : 'p-button-outlined p-button-secondary'"
                  class="p-button-sm tw-rounded-xl"
                  label="Cancelled"
                />
              </div>
            </div>
          </div>

          <!-- Enhanced Action Buttons -->
          <div class="tw-flex tw-flex-wrap tw-gap-3">
            <Button 
              @click="refreshData"
              icon="pi pi-refresh"
              label="Refresh"
              class="p-button-outlined p-button-secondary tw-rounded-xl"
              v-tooltip.top="'Refresh data'"
              :loading="loading"
            />
            <Button 
              @click="createNewPrescription"
              icon="pi pi-plus"
              label="New Prescription"
              class="p-button-success tw-rounded-xl tw-shadow-lg hover:tw-shadow-xl tw-transition-all"
              v-tooltip.top="'Create new prescription'"
            />
          </div>
        </div>
      </div>

      <!-- Prescriptions DataTable -->
      <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-border tw-border-slate-200/60 tw-overflow-hidden">
        <DataTable 
          :value="filteredPrescriptions" 
          :paginator="true" 
          :rows="10"
          :rowsPerPageOptions="[10, 25, 50]"
          :loading="loading"
          responsiveLayout="scroll"
          stripedRows
          class="p-datatable-sm"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords} prescriptions"
        >
          <template #empty>
            <div class="tw-text-center tw-py-12">
              <i class="pi pi-inbox tw-text-6xl tw-text-slate-300 tw-mb-4"></i>
              <p class="tw-text-slate-500 tw-text-lg">No prescriptions found</p>
            </div>
          </template>
          
          <Column field="prescription_code" header="Code" :sortable="true">
            <template #body="slotProps">
              <div class="tw-font-mono tw-font-semibold tw-text-purple-600">
                {{ slotProps.data.prescription_code }}
              </div>
            </template>
          </Column>

          <Column field="doctor" header="Doctor" :sortable="true">
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-gap-2">
                <div class="tw-bg-purple-100 tw-p-2 tw-rounded-full">
                  <i class="pi pi-user-md tw-text-purple-600"></i>
                </div>
                <div>
                  <div class="tw-font-semibold tw-text-slate-900">
                    {{ slotProps.data.doctor?.name || 'N/A' }}
                  </div>
                  <div class="tw-text-xs tw-text-slate-500">
                    {{ slotProps.data.doctor?.specialization || 'Doctor' }}
                  </div>
                </div>
              </div>
            </template>
          </Column>

          <Column field="total_items" header="Items" :sortable="true">
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-gap-2">
                <Tag :value="slotProps.data.total_items" severity="info" class="tw-rounded-lg"/>
                <span class="tw-text-xs tw-text-slate-500">products</span>
              </div>
            </template>
          </Column>

          <Column field="dispensed_items" header="Dispensed" :sortable="true">
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-gap-2">
                <Tag :value="slotProps.data.dispensed_items" 
                     :severity="slotProps.data.dispensed_items === slotProps.data.total_items ? 'success' : 'warning'" 
                     class="tw-rounded-lg"/>
                <span class="tw-text-xs tw-text-slate-500">of {{ slotProps.data.total_items }}</span>
              </div>
            </template>
          </Column>

          <Column field="status" header="Status" :sortable="true">
            <template #body="slotProps">
              <Tag :value="getStatusLabel(slotProps.data)" 
                   :severity="getStatusSeverity(slotProps.data.status)" 
                   class="tw-rounded-lg tw-px-3 tw-py-1"/>
            </template>
          </Column>

          <Column field="created_at" header="Created Date" :sortable="true">
            <template #body="slotProps">
              <div class="tw-text-sm tw-text-slate-600">
                {{ formatDate(slotProps.data.created_at) }}
              </div>
            </template>
          </Column>

          <Column header="Actions" :exportable="false">
            <template #body="slotProps">
              <div class="tw-flex tw-gap-2">
                <Button 
                  icon="pi pi-eye" 
                  class="p-button-sm p-button-text p-button-rounded tw-text-blue-600"
                  v-tooltip.top="'View details'"
                  @click="viewPrescription(slotProps.data)"
                />
                <Button 
                  icon="pi pi-print" 
                  class="p-button-sm p-button-text p-button-rounded tw-text-purple-600"
                  v-tooltip.top="'Print prescription'"
                  @click="printPrescription(slotProps.data)"
                />
                <Button 
                  v-if="slotProps.data.status === 'draft'"
                  icon="pi pi-trash" 
                  class="p-button-sm p-button-text p-button-rounded tw-text-red-600"
                  v-tooltip.top="'Delete draft'"
                  @click="deletePrescription(slotProps.data)"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </div>
    </div>

    <!-- Delete Confirmation Dialog -->
    <Dialog v-model:visible="deleteDialog" header="Confirm Delete" :modal="true" class="tw-w-[450px]">
      <div class="tw-flex tw-items-center tw-gap-4">
        <i class="pi pi-exclamation-triangle tw-text-4xl tw-text-red-500"></i>
        <div>
          <p class="tw-text-slate-700">
            Are you sure you want to delete prescription <strong>{{ selectedPrescription?.prescription_code }}</strong>?
          </p>
          <p class="tw-text-sm tw-text-slate-500 tw-mt-2">This action cannot be undone.</p>
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" icon="pi pi-times" class="p-button-text" @click="deleteDialog = false" />
        <Button label="Delete" icon="pi pi-check" class="p-button-danger" @click="confirmDelete" :loading="deleting" />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import externalPrescriptionService from '@/services/Pharmacy/externalPrescriptionService';

const router = useRouter();
const toast = useToast();

// State
const prescriptions = ref([]);
const loading = ref(false);
const filters = ref({
  search: '',
  status: null,
});
const deleteDialog = ref(false);
const selectedPrescription = ref(null);
const deleting = ref(false);

// Status Options
const statusOptions = ref([
  { label: 'All Status', value: null },
  { label: 'Draft', value: 'draft' },
  { label: 'Confirmed', value: 'confirmed' },
  { label: 'Cancelled', value: 'cancelled' },
]);

// Computed
const filteredPrescriptions = computed(() => {
  let result = [...prescriptions.value];

  if (filters.value.search) {
    const search = filters.value.search.toLowerCase();
    result = result.filter(p => 
      p.prescription_code?.toLowerCase().includes(search) ||
      p.doctor?.name?.toLowerCase().includes(search)
    );
  }

  if (filters.value.status) {
    result = result.filter(p => p.status === filters.value.status);
  }

  return result;
});

// Methods
const loadPrescriptions = async () => {
  loading.value = true;
  try {
    const response = await externalPrescriptionService.getExternalPrescriptions(filters.value);
    prescriptions.value = response.data;
  } catch (error) {
    toast.add({ 
      severity: 'error', 
      summary: 'Error', 
      detail: 'Failed to load prescriptions', 
      life: 3000 
    });
  } finally {
    loading.value = false;
  }
};

const debouncedSearch = (() => {
  let timeout;
  return () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => applyFilters(), 500);
  };
})();

const clearSearch = () => {
  filters.value.search = '';
  applyFilters();
};

const applyFilters = () => {
  loadPrescriptions();
};

const setQuickFilter = (status) => {
  filters.value.status = filters.value.status === status ? null : status;
  applyFilters();
};

const refreshData = () => {
  loadPrescriptions();
};

const createNewPrescription = () => {
  router.push('/external-prescriptions/create');
};

const viewPrescription = (prescription) => {
  router.push(`/external-prescriptions/${prescription.id}`);
};

const printPrescription = async (prescription) => {
  try {
    const pdfBlob = await externalPrescriptionService.generatePDF(prescription.id);
    const url = window.URL.createObjectURL(pdfBlob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `${prescription.prescription_code}.pdf`;
    link.click();
    window.URL.revokeObjectURL(url);
    
    toast.add({ 
      severity: 'success', 
      summary: 'Success', 
      detail: 'PDF downloaded successfully', 
      life: 3000 
    });
  } catch (error) {
    toast.add({ 
      severity: 'error', 
      summary: 'Error', 
      detail: 'Failed to generate PDF', 
      life: 3000 
    });
  }
};

const deletePrescription = (prescription) => {
  selectedPrescription.value = prescription;
  deleteDialog.value = true;
};

const confirmDelete = async () => {
  deleting.value = true;
  try {
    await externalPrescriptionService.deleteExternalPrescription(selectedPrescription.value.id);
    toast.add({ 
      severity: 'success', 
      summary: 'Success', 
      detail: 'Prescription deleted successfully', 
      life: 3000 
    });
    deleteDialog.value = false;
    loadPrescriptions();
  } catch (error) {
    toast.add({ 
      severity: 'error', 
      summary: 'Error', 
      detail: 'Failed to delete prescription', 
      life: 3000 
    });
  } finally {
    deleting.value = false;
  }
};

const getStatusLabel = (prescription) => {
  const labels = {
    draft: 'Draft',
    confirmed: 'Confirmed',
    cancelled: 'Cancelled',
  };
  return labels[prescription.status] || prescription.status;
};

const getStatusSeverity = (status) => {
  const severities = {
    draft: 'warning',
    confirmed: 'success',
    cancelled: 'danger',
  };
  return severities[status] || 'info';
};

const getStatusColor = (status) => {
  const colors = {
    draft: 'tw-bg-amber-400',
    confirmed: 'tw-bg-green-400',
    cancelled: 'tw-bg-red-400',
  };
  return colors[status] || 'tw-bg-slate-400';
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
  loadPrescriptions();
});
</script>

<style scoped>
/* Add any component-specific styles here */
</style>
