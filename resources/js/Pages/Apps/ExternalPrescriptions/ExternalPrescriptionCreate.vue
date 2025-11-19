<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-slate-50 tw-via-purple-50/30 tw-to-indigo-50/20">
    <!-- Header -->
    <div class="tw-bg-gradient-to-r tw-from-white tw-via-purple-50/50 tw-to-indigo-50/30 tw-border-b tw-border-slate-200/60 tw-sticky tw-top-0 tw-z-10 tw-shadow-lg tw-backdrop-blur-sm">
      <div class="tw-px-6 tw-py-6">
        <div class="tw-flex tw-items-center tw-gap-4 tw-mb-4">
          <Button 
            icon="pi pi-arrow-left"
            class="p-button-text p-button-secondary"
            @click="goBack"
          />
          <div>
            <h1 class="tw-text-3xl tw-font-bold tw-bg-gradient-to-r tw-from-slate-900 tw-to-slate-700 tw-bg-clip-text tw-text-transparent">
              Create External Prescription
            </h1>
            <p class="tw-text-slate-600 tw-text-sm tw-mt-1">
              <i class="pi pi-info-circle tw-text-purple-500"></i>
              Add products from external pharmacies
            </p>
          </div>
        </div>
      </div>
    </div>

    <div class="tw-px-6 tw-py-6">
      <!-- Form Card -->
      <Card class="tw-border-0 tw-shadow-lg tw-mb-6">
        <template #content>
          <form @submit.prevent="createPrescription" class="tw-space-y-6">
            <!-- Basic Info Section -->
            <div class="tw-bg-gradient-to-r tw-from-purple-50/50 tw-to-indigo-50/50 tw-p-4 tw-rounded-xl tw-border tw-border-purple-200/50">
              <h3 class="tw-text-lg tw-font-semibold tw-text-slate-900 tw-mb-4 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-info-circle tw-text-purple-600"></i>
                Prescription Details
              </h3>

              <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
                <!-- Doctor Selection -->
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
                  >
                    <template #option="slotProps">
                      <div class="tw-flex tw-items-center tw-gap-2">
                        <i class="pi pi-user-md tw-text-purple-600"></i>
                        <span>{{ slotProps.option.name }}</span>
                      </div>
                    </template>
                  </Dropdown>
                  <small class="tw-text-slate-500">{{ form.doctor_id ? `Selected: ${doctorName}` : 'Required' }}</small>
                </div>

                <!-- Prescription Code (Read-only) -->
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
                  <small class="tw-text-slate-500">Auto-generated upon creation</small>
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
                  class="p-button-success p-button-sm tw-rounded-xl"
                  v-tooltip.top="'Select products from pharmacy'"
                />
              </div>

              <!-- Items Table -->
              <DataTable 
                v-if="form.items.length > 0"
                :value="form.items"
                stripedRows
                class="p-datatable-sm tw-mb-4"
              >
                <template #empty>
                  <div class="tw-text-center tw-py-8">
                    <i class="pi pi-inbox tw-text-4xl tw-text-slate-300 tw-mb-2"></i>
                    <p class="tw-text-slate-500">No products added yet</p>
                  </div>
                </template>

                <Column field="product.name" header="Product Name" />
                
                <Column header="Quantity">
                  <template #body="slotProps">
                    <InputNumber
                      v-model="slotProps.data.quantity"
                      :min="1"
                      placeholder="Qty"
                      class="tw-w-full"
                    />
                  </template>
                </Column>

                <Column field="unit" header="Unit">
                  <template #body="slotProps">
                    <span class="tw-text-slate-600">{{ slotProps.data.unit }}</span>
                  </template>
                </Column>

                <Column header="Actions">
                  <template #body="slotProps">
                    <Button
                      icon="pi pi-trash"
                      class="p-button-sm p-button-text p-button-danger"
                      @click="removeItem(slotProps.index)"
                      v-tooltip.top="'Remove item'"
                    />
                  </template>
                </Column>
              </DataTable>

              <div v-else class="tw-text-center tw-py-12 tw-bg-white tw-rounded-lg tw-border tw-border-dashed tw-border-slate-300">
                <i class="pi pi-inbox tw-text-5xl tw-text-slate-300 tw-mb-3"></i>
                <p class="tw-text-slate-500 tw-mb-4">No products added yet</p>
                <Button
                  @click="showProductSelector = true"
                  icon="pi pi-plus"
                  label="Add Products"
                  class="p-button-primary tw-rounded-xl"
                />
              </div>
            </div>

            <!-- Summary Section -->
            <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-4">
              <div class="tw-bg-gradient-to-br tw-from-slate-50 tw-to-gray-50 tw-p-4 tw-rounded-xl tw-border tw-border-slate-200/60">
                <div class="tw-flex tw-items-center tw-gap-2 tw-mb-2">
                  <i class="pi pi-list tw-text-slate-600"></i>
                  <span class="tw-text-sm tw-text-slate-600 tw-font-medium">Total Items</span>
                </div>
                <div class="tw-text-3xl tw-font-bold tw-text-slate-900">{{ form.items.length }}</div>
              </div>

              <div class="tw-bg-gradient-to-br tw-from-green-50 tw-to-emerald-50 tw-p-4 tw-rounded-xl tw-border tw-border-green-200/60">
                <div class="tw-flex tw-items-center tw-gap-2 tw-mb-2">
                  <i class="pi pi-check-circle tw-text-green-600"></i>
                  <span class="tw-text-sm tw-text-green-600 tw-font-medium">Status</span>
                </div>
                <div class="tw-text-2xl tw-font-bold tw-text-green-900">Draft</div>
              </div>

              <div class="tw-bg-gradient-to-br tw-from-purple-50 tw-to-indigo-50 tw-p-4 tw-rounded-xl tw-border tw-border-purple-200/60">
                <div class="tw-flex tw-items-center tw-gap-2 tw-mb-2">
                  <i class="pi pi-calendar tw-text-purple-600"></i>
                  <span class="tw-text-sm tw-text-purple-600 tw-font-medium">Date</span>
                </div>
                <div class="tw-text-lg tw-font-semibold tw-text-purple-900">{{ currentDate }}</div>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="tw-flex tw-gap-3 tw-justify-end tw-pt-4 tw-border-t tw-border-slate-200">
              <Button
                @click="goBack"
                label="Cancel"
                icon="pi pi-times"
                class="p-button-outlined p-button-secondary tw-rounded-xl"
              />
              <Button
                @click="createPrescription"
                label="Create Prescription"
                icon="pi pi-check"
                class="p-button-success tw-rounded-xl"
                :loading="creating"
                :disabled="!form.doctor_id || form.items.length === 0"
              />
            </div>
          </form>
        </template>
      </Card>
    </div>

    <!-- Product Selector Modal -->
    <ProductSelectionDialogSimple 
      :visible="showProductSelector"
      @update:visible="showProductSelector = $event"
      @products-selected="onProductsSelected"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';
import Button from 'primevue/button';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import ProductSelectionDialogSimple from '@/Components/Pharmacy/ProductSelectionDialogSimple.vue';
import externalPrescriptionService from '@/services/Pharmacy/externalPrescriptionService';

const router = useRouter();
const toast = useToast();

// State
const form = ref({
  doctor_id: null,
  prescription_code: '',
  items: [],
});

const doctors = ref([]);
const products = ref([]);
const productSearch = ref('');
const showProductSelector = ref(false);
const creating = ref(false);

// Computed
const filteredProducts = computed(() => {
  if (!productSearch.value) return products.value;
  
  const search = productSearch.value.toLowerCase();
  return products.value.filter(p =>
    p.name?.toLowerCase().includes(search) ||
    p.code?.toLowerCase().includes(search)
  );
});

const doctorName = computed(() => {
  const doctor = doctors.value.find(d => d.id === form.value.doctor_id);
  return doctor?.name || '';
});

const currentDate = computed(() => {
  return new Date().toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
});

// Methods
const loadDoctorsAndProducts = async () => {
  try {
    // Load doctors from API
    const doctorsResponse = await axios.get('/api/doctors');
    // Transform doctors data to include user name
    
    doctors.value = doctorsResponse.data.data 

    // Load products from API
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
      detail: 'Failed to load doctors or products',
      life: 3000,
    });
  }
};

const addProductToForm = (product) => {
  const existingItem = form.value.items.find(item => item.product_id === product.id);
  
  if (existingItem) {
    existingItem.quantity += 1;
  } else {
    form.value.items.push({
      product_id: product.id,
      product: product,
      quantity: 1,
      unit: product.unit || 'box',
    });
  }

  toast.add({
    severity: 'success',
    summary: 'Success',
    detail: `${product.name} added to prescription`,
    life: 2000,
  });
};

const onProductsSelected = (selectedProducts) => {
  selectedProducts.forEach(product => {
    const existingItem = form.value.items.find(item => item.product_id === product.id);
    
    if (!existingItem) {
      form.value.items.push({
        product_id: product.id,
        product: product,
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
    // Create the prescription
    const prescriptionResponse = await axios.post('/api/external-prescriptions', {
      doctor_id: form.value.doctor_id,
    });

    const prescription = prescriptionResponse.data.data;

    // Add items to the prescription
    const itemsPayload = {
      items: form.value.items.map(item => ({
        pharmacy_product_id: item.product_id,
        quantity: item.quantity,
        quantity_by_box: false,
        unit: item.unit,
      })),
    };

    await axios.post(`/api/external-prescriptions/${prescription.id}/items`, itemsPayload);

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: `Prescription ${prescription.prescription_code} created successfully`,
      life: 3000,
    });

    // Redirect to detail page
    router.push(`/external-prescriptions/${prescription.id}`);
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

const goBack = () => {
  router.push('/external-prescriptions/list');
};

// Lifecycle
onMounted(() => {
  loadDoctorsAndProducts();
});
</script>
