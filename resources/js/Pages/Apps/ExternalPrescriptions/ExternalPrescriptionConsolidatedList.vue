<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-slate-50 tw-via-purple-50/30 tw-to-indigo-50/20">
    <!-- Header Section -->
    <div class="tw-bg-gradient-to-r tw-from-white tw-via-purple-50/50 tw-to-indigo-50/30 tw-border-b tw-border-slate-200/60 tw-sticky tw-top-0 tw-z-10 tw-shadow-lg tw-backdrop-blur-sm">
      <div class="tw-px-6 tw-py-6">
        <div class="tw-flex tw-flex-col lg:tw-flex-row tw-justify-between tw-items-start lg:tw-items-center tw-gap-6">
          <div class="tw-flex tw-items-center tw-gap-4">
            <div class="tw-bg-gradient-to-br tw-from-purple-500 tw-to-indigo-600 tw-p-3 tw-rounded-xl tw-shadow-lg">
              <i class="pi pi-prescription tw-text-white tw-text-2xl"></i>
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
            <div class="tw-bg-gradient-to-br tw-from-red-50 tw-to-rose-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-red-200/60">
              <div class="tw-text-xs tw-text-red-700 tw-font-medium">Cancelled</div>
              <div class="tw-text-2xl tw-font-bold tw-text-red-800">{{ prescriptions.filter(p => p.status === 'cancelled').length }}</div>
            </div>
            <div class="tw-bg-gradient-to-br tw-from-slate-50 tw-to-gray-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-slate-200/60">
              <div class="tw-text-xs tw-text-slate-700 tw-font-medium">Total</div>
              <div class="tw-text-2xl tw-font-bold tw-text-slate-800">{{ prescriptions.length }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content Area -->
    <div class="tw-px-6 tw-py-6 tw-space-y-6">
      <!-- Create Form Section -->
      <Card class="tw-border-0 tw-shadow-lg" v-show="showCreateForm">
        <template #content>
          <div class="tw-mb-6 tw-pb-4 tw-border-b tw-border-slate-200">
            <h3 class="tw-text-xl tw-font-semibold tw-text-slate-900 tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-plus-circle tw-text-purple-600"></i>
              Create New Prescription
            </h3>
          </div>

          <form class="tw-space-y-6">
            <!-- Basic Info -->
            <div class="tw-bg-gradient-to-r tw-from-purple-50/50 tw-to-indigo-50/50 tw-p-4 tw-rounded-xl tw-border tw-border-purple-200/50">
              <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
                <div class="tw-space-y-2">
                  <label class="tw-block tw-text-sm tw-font-medium tw-text-slate-700">
                    Doctor <span class="tw-text-red-500">*</span>
                  </label>
                  <Dropdown
                    v-model="form.doctor_id"
                    :options="doctors"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Select doctor"
                    filter
                    :showClear="true"
                    class="tw-w-full"
                  />
                </div>

                <div class="tw-space-y-2">
                  <label class="tw-block tw-text-sm tw-font-medium tw-text-slate-700">
                    Prescription Code
                  </label>
                  <InputText
                    v-model="form.prescription_code"
                    disabled
                    placeholder="Auto-generated"
                    class="tw-w-full tw-bg-slate-100"
                  />
                </div>
              </div>
            </div>

            <!-- Products Section -->
            <div class="tw-bg-gradient-to-r tw-from-blue-50/50 tw-to-cyan-50/50 tw-p-4 tw-rounded-xl tw-border tw-border-blue-200/50">
              <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
                <h3 class="tw-text-lg tw-font-semibold tw-text-slate-900 tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-boxes tw-text-blue-600"></i>
                  Products
                </h3>
                <Button
                  @click="showProductSelector = true"
                  icon="pi pi-plus"
                  label="Add Products"
                  class="p-button-success p-button-sm"
                />
              </div>

              <!-- Items Table -->
              <DataTable v-if="form.items.length > 0" :value="form.items" stripedRows class="p-datatable-sm tw-mb-4">
                <Column field="product.name" header="Product Name" />
                <Column header="Quantity">
                  <template #body="slotProps">
                    <InputNumber v-model="slotProps.data.quantity" :min="1" placeholder="Qty" class="tw-w-full" />
                  </template>
                </Column>
                <Column field="unit" header="Unit" />
                <Column header="Actions" style="width: 80px">
                  <template #body="slotProps">
                    <Button
                      icon="pi pi-trash"
                      class="p-button-sm p-button-text p-button-danger"
                      @click="removeItem(slotProps.index)"
                    />
                  </template>
                </Column>
              </DataTable>

              <div v-else class="tw-text-center tw-py-8 tw-bg-white tw-rounded-lg tw-border tw-border-dashed tw-border-slate-300">
                <i class="pi pi-inbox tw-text-4xl tw-text-slate-300 tw-mb-3"></i>
                <p class="tw-text-slate-500">No products added yet</p>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="tw-flex tw-gap-3 tw-justify-end tw-pt-4 tw-border-t tw-border-slate-200">
              <Button
                @click="toggleCreateForm"
                label="Cancel"
                icon="pi pi-times"
                class="p-button-outlined p-button-secondary"
              />
              <Button
                @click="createPrescription"
                label="Create Prescription"
                icon="pi pi-check"
                class="p-button-success"
                :loading="creating"
                :disabled="!form.doctor_id || form.items.length === 0"
              />
            </div>
          </form>
        </template>
      </Card>

      <!-- Filters & Actions Toolbar -->
      <Card class="tw-border-0 tw-shadow-lg">
        <template #content>
          <div class="tw-flex tw-flex-col xl:tw-flex-row tw-justify-between tw-items-start xl:tw-items-center tw-gap-6">
            <!-- Filters -->
            <div class="tw-flex tw-flex-col sm:tw-flex-row tw-gap-4 tw-flex-1">
              <div class="tw-flex-1">
                <InputText
                  v-model="filters.search"
                  placeholder="Search by code or doctor..."
                  class="tw-w-full"
                  @input="debouncedSearch"
                />
              </div>

              <Dropdown
                v-model="filters.status"
                :options="statusOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Filter by status"
                @change="applyFilters"
              />

              <Button
                icon="pi pi-refresh"
                class="p-button-outlined p-button-secondary"
                @click="refreshData"
              />

              <Button
                v-if="filters.search || filters.status"
                icon="pi pi-times"
                label="Clear"
                class="p-button-outlined p-button-secondary"
                @click="clearFilters"
              />
            </div>

            <!-- Action Buttons -->
            <div class="tw-flex tw-flex-wrap tw-gap-3">
              <Button
                v-if="!showCreateForm"
                @click="toggleCreateForm"
                icon="pi pi-plus"
                label="Create"
                class="p-button-primary"
              />
              <Button
                icon="pi pi-download"
                label="Export"
                class="p-button-outlined"
                @click="exportData"
              />
            </div>
          </div>
        </template>
      </Card>

      <!-- Prescriptions Table -->
      <Card class="tw-border-0 tw-shadow-lg">
        <template #content>
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
                <i class="pi pi-inbox tw-text-5xl tw-text-slate-300 tw-mb-3"></i>
                <p class="tw-text-slate-500">No prescriptions found</p>
              </div>
            </template>

            <Column field="prescription_code" header="Code" :sortable="true">
              <template #body="slotProps">
                <span class="tw-font-semibold tw-text-slate-900">{{ slotProps.data.prescription_code }}</span>
              </template>
            </Column>

            <Column field="doctor.user.name" header="Doctor" :sortable="true">
              <template #body="slotProps">
                <div class="tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-user-md tw-text-purple-600"></i>
                  <span>{{ slotProps.data.doctor?.user?.name || 'N/A' }}</span>
                </div>
              </template>
            </Column>

            <Column field="items_count" header="Items" :sortable="true">
              <template #body="slotProps">
                <Badge :value="slotProps.data.items?.length || 0" severity="info" />
              </template>
            </Column>

            <Column header="Dispensed" :sortable="true">
              <template #body="slotProps">
                <Badge 
                  :value="slotProps.data.items?.filter(i => i.status === 'dispensed').length || 0" 
                  severity="success" 
                />
              </template>
            </Column>

            <Column field="status" header="Status" :sortable="true">
              <template #body="slotProps">
                <Tag 
                  :value="getStatusLabel(slotProps.data.status)" 
                  :severity="getStatusSeverity(slotProps.data.status)"
                />
              </template>
            </Column>

            <Column field="created_at" header="Date" :sortable="true">
              <template #body="slotProps">
                <span class="tw-text-sm tw-text-slate-600">{{ formatDate(slotProps.data.created_at) }}</span>
              </template>
            </Column>

            <Column header="Actions" :exportable="false" style="width: 120px">
              <template #body="slotProps">
                <div class="tw-flex tw-gap-2 tw-justify-center">
                  <Button
                    icon="pi pi-eye"
                    class="p-button-sm p-button-info"
                    @click="viewPrescription(slotProps.data)"
                  />
                  <Button
                    icon="pi pi-download"
                    class="p-button-sm p-button-success"
                    @click="printPrescription(slotProps.data)"
                  />
                  <Button
                    icon="pi pi-trash"
                    class="p-button-sm p-button-danger"
                    @click="deletePrescription(slotProps.data)"
                  />
                </div>
              </template>
            </Column>
          </DataTable>
        </template>
      </Card>
    </div>

    <!-- Product Selector Modal -->
    <ProductSelectionDialogSimple 
      :visible="showProductSelector"
      @update:visible="showProductSelector = $event"
      @products-selected="onProductsSelected"
    />

    <!-- Delete Confirmation Dialog -->
    <Dialog v-model:visible="deleteDialog" header="Confirm Delete" :modal="true" class="tw-w-[450px]">
      <div class="tw-flex tw-items-center tw-gap-4">
        <i class="pi pi-exclamation-triangle tw-text-4xl tw-text-red-500"></i>
        <div>
          <p class="tw-text-slate-700">Are you sure you want to delete this prescription?</p>
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
import axios from 'axios';
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';
import Dialog from 'primevue/dialog';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Badge from 'primevue/badge';
import ProductSelectionDialogSimple from '@/Components/Pharmacy/ProductSelectionDialogSimple.vue';

const router = useRouter();
const toast = useToast();

// State
const prescriptions = ref([]);
const doctors = ref([]);
const products = ref([]);
const loading = ref(false);
const creating = ref(false);
const deleting = ref(false);

const showCreateForm = ref(false);
const showProductSelector = ref(false);
const productSearch = ref('');
const deleteDialog = ref(false);
const selectedPrescription = ref(null);

const filters = ref({
  search: '',
  status: null,
});

const form = ref({
  doctor_id: null,
  prescription_code: '',
  items: [],
});

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
      p.doctor?.user?.name?.toLowerCase().includes(search)
    );
  }

  if (filters.value.status) {
    result = result.filter(p => p.status === filters.value.status);
  }

  return result;
});

const filteredProducts = computed(() => {
  if (!productSearch.value) return products.value;
  
  const search = productSearch.value.toLowerCase();
  return products.value.filter(p =>
    p.name?.toLowerCase().includes(search) ||
    p.code?.toLowerCase().includes(search)
  );
});

// Methods
const loadDoctorsAndProducts = async () => {
  try {
    const doctorsResponse = await axios.get('/api/doctors');
    doctors.value = doctorsResponse.data.data 

    const productsResponse = await axios.get('/api/pharmacy/products');
    products.value = (productsResponse.data.data || []).map(product => ({
      id: product.id,
      name: product.name || product.designation,
      code: product.code || product.code_interne,
      unit: product.unit_of_measure || 'box',
    }));
  } catch (error) {
    console.error('Error loading data:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load data',
      life: 3000,
    });
  }
};

const loadPrescriptions = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/external-prescriptions');
    prescriptions.value = response.data.data.data || [];
  } catch (error) {
    console.error('Error loading prescriptions:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load prescriptions',
      life: 3000,
    });
  } finally {
    loading.value = false;
  }
};

const toggleCreateForm = () => {
  showCreateForm.value = !showCreateForm.value;
  if (!showCreateForm.value) {
    resetForm();
  }
};

const resetForm = () => {
  form.value = {
    doctor_id: null,
    prescription_code: '',
    items: [],
  };
};

const createPrescription = async () => {
  if (!form.value.doctor_id || form.value.items.length === 0) {
    toast.add({
      severity: 'warn',
      summary: 'Validation Error',
      detail: 'Please select a doctor and add products',
      life: 3000,
    });
    return;
  }

  creating.value = true;
  try {
    const prescriptionResponse = await axios.post('/api/external-prescriptions', {
      doctor_id: form.value.doctor_id,
    });

    const prescription = prescriptionResponse.data.data;

    const itemsPayload = {
      items: form.value.items.map(item => ({
        pharmacy_product_id: item.product_id,
        quantity: item.quantity,
        quantity_by_box: false,
        unit: item.unit,
      })),
    };

    await axios.post(`/api/external-prescriptions/${prescription.id}/items`, itemsPayload);

    // Show prominent success message
    toast.add({
      severity: 'success',
      summary: '✅ Prescription Created Successfully!',
      detail: `Prescription ${prescription.prescription_code} has been created with ${form.value.items.length} items`,
      life: 5000,
    });

    // Reset form and reload list
    resetForm();
    showCreateForm.value = false;
    await loadPrescriptions();

    // Scroll to top to show the new prescription
    window.scrollTo({ top: 0, behavior: 'smooth' });
  } catch (error) {
    console.error('Error creating prescription:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to create prescription',
      life: 3000,
    });
  } finally {
    creating.value = false;
  }
};

const addProductToForm = (product) => {
  const existingItem = form.value.items.find(item => item.product_id === product.id);
  
  if (existingItem) {
    existingItem.quantity += 1;
  } else {
    form.value.items.push({
      product_id: product.id,
      product,
      quantity: 1,
      unit: product.unit || 'box',
    });
  }

  toast.add({
    severity: 'success',
    summary: 'Success',
    detail: `${product.name} added`,
    life: 2000,
  });
};

const onProductsSelected = (selectedProducts) => {
  selectedProducts.forEach(product => {
    const existingItem = form.value.items.find(item => item.product_id === product.id);
    
    if (!existingItem) {
      form.value.items.push({
        product_id: product.id,
        product,
        quantity: product.default_quantity || product.quantity || 1,
        unit: product.default_unit || product.unit || 'box',
      });
    }
  });

  toast.add({
    severity: 'success',
    summary: 'Success',
    detail: `${selectedProducts.length} product(s) added to prescription`,
    life: 2000,
  });
};

const removeItem = (index) => {
  form.value.items.splice(index, 1);
};

const debouncedSearch = (() => {
  let timeout;
  return () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => applyFilters(), 500);
  };
})();

const applyFilters = () => {
  // Filters are reactive, so this just triggers a re-render
};

const clearFilters = () => {
  filters.value.search = '';
  filters.value.status = null;
};

const refreshData = () => {
  loadPrescriptions();
};

const viewPrescription = (prescription) => {
  router.push(`/external-prescriptions/${prescription.id}`);
};

const printPrescription = async (prescription) => {
  try {
    const response = await axios.get(`/api/external-prescriptions/${prescription.id}/pdf`, {
      responseType: 'blob',
    });
    
    const url = window.URL.createObjectURL(response.data);
    const link = document.createElement('a');
    link.href = url;
    link.download = `${prescription.prescription_code}.pdf`;
    link.click();
    window.URL.revokeObjectURL(url);
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'PDF downloaded',
      life: 3000,
    });
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to generate PDF',
      life: 3000,
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
    await axios.delete(`/api/external-prescriptions/${selectedPrescription.value.id}`);
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Prescription deleted',
      life: 3000,
    });
    
    deleteDialog.value = false;
    loadPrescriptions();
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to delete prescription',
      life: 3000,
    });
  } finally {
    deleting.value = false;
  }
};

const exportData = () => {
  const csv = [
    ['Code', 'Doctor', 'Items', 'Status', 'Created Date'],
    ...filteredPrescriptions.value.map(p => [
      p.prescription_code,
      p.doctor?.user?.name || 'N/A',
      p.items?.length || 0,
      p.status,
      formatDate(p.created_at),
    ]),
  ].map(row => row.join(',')).join('\n');

  const blob = new Blob([csv], { type: 'text/csv' });
  const url = window.URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = 'prescriptions.csv';
  link.click();
  window.URL.revokeObjectURL(url);
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
    confirmed: 'success',
    cancelled: 'danger',
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
  loadDoctorsAndProducts();
  loadPrescriptions();
});
</script>

<style scoped>
/* Component-specific styles can go here */
</style>
