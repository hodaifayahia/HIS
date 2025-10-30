<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-teal-50 tw-via-white tw-to-cyan-50 tw-p-6">
    <!-- Success Toast -->
    <Toast position="top-right" />

    <!-- Confirm Dialog -->
    <ConfirmDialog />

    <!-- Header Section -->
    <div class="tw-mb-6">
      <div class="tw-bg-gradient-to-r tw-from-teal-600 tw-to-cyan-700 tw-rounded-xl tw-shadow-lg tw-p-6 tw-text-white">
        <div class="tw-flex tw-justify-between tw-items-start tw-gap-4">
          <div class="tw-flex-1">
            <div class="tw-flex tw-items-center tw-gap-3 tw-mb-2">
              <Button
                icon="pi pi-arrow-left"
                class="tw-bg-white/20 tw-border-0 tw-rounded-lg tw-p-2 hover:tw-bg-white/30 tw-transition-all"
                @click="goBack"
              />
              <div class="tw-flex-1">
                <h1 class="tw-text-2xl tw-font-bold tw-mb-1">
                  <i class="pi pi-box tw-mr-2"></i>
                  {{ audit?.name || 'Audit Products' }}
                </h1>
                <p class="tw-text-teal-100 tw-text-sm">
                  {{ getScopeDescription() }}
                </p>
              </div>
            </div>
          </div>
          <div class="tw-flex tw-gap-2 tw-flex-wrap tw-justify-end">
            <Tag 
              v-if="audit"
              :value="audit.status" 
              :severity="getStatusSeverity(audit.status)"
              :icon="getStatusIcon(audit.status)"
              class="tw-px-3 tw-py-2"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="tw-flex tw-flex-col tw-items-center tw-justify-center tw-py-16">
      <i class="pi pi-spin pi-spinner tw-text-6xl tw-text-teal-500 tw-mb-4"></i>
      <p class="tw-text-gray-500 tw-text-lg">Loading products...</p>
    </div>

    <!-- Content -->
    <div v-else-if="audit" class="tw-space-y-6">
      <!-- Locked Status Warning -->
    <div v-if="!canEdit && participantStatus" class="tw-bg-amber-50 tw-border tw-border-amber-200 tw-rounded-xl tw-shadow tw-p-4 tw-mb-4">
      <div class="tw-flex tw-items-center tw-gap-3">
        <i class="pi pi-lock tw-text-2xl tw-text-amber-600"></i>
        <div>
          <h3 class="tw-font-semibold tw-text-amber-800">Editing Locked</h3>
          <p class="tw-text-sm tw-text-amber-700">
            This audit has been submitted (Status: <span class="tw-font-semibold tw-uppercase">{{ participantStatus }}</span>). 
            No further changes can be made.
          </p>
        </div>
      </div>
    </div>

    <!-- Actions Bar -->
      <div class="tw-bg-white tw-rounded-xl tw-shadow tw-p-4">
        <div class="tw-flex tw-flex-wrap tw-gap-2 tw-items-center tw-justify-between">
          <div class="tw-flex tw-gap-2">
            <Button
              v-if="audit.status === 'completed'"
              label="Download PDF"
              icon="pi pi-file-pdf"
              class="tw-bg-red-500 tw-text-white tw-border-0 tw-rounded-lg tw-px-4 tw-py-2 tw-text-sm tw-font-semibold hover:tw-shadow-lg tw-transition-all"
              @click="downloadPDF"
            />
            <Button
              v-if="canEdit"
              label="Save Progress"
              icon="pi pi-save"
              class="tw-bg-blue-500 tw-text-white tw-border-0 tw-rounded-lg tw-px-4 tw-py-2 tw-text-sm tw-font-semibold hover:tw-shadow-lg tw-transition-all"
              @click="saveProgress"
              :loading="saving"
            />
            <Button
              v-if="canEdit && audit.status === 'in_progress' && completedCount > 0"
              label="Send"
              icon="pi pi-send"
              class="tw-bg-gradient-to-r tw-from-purple-500 tw-to-indigo-600 tw-text-white tw-border-0 tw-rounded-lg tw-px-4 tw-py-2 tw-text-sm tw-font-semibold hover:tw-shadow-lg tw-transition-all"
              @click="confirmSendAudit"
              :loading="saving"
            />
            <Button
              v-if="audit.status === 'sent' && canComplete"
              label="Complete Audit"
              icon="pi pi-check"
              class="tw-bg-green-500 tw-text-white tw-border-0 tw-rounded-lg tw-px-4 tw-py-2 tw-text-sm tw-font-semibold hover:tw-shadow-lg tw-transition-all"
              @click="confirmCompleteAudit"
            />
          </div>
          <div class="tw-text-sm tw-text-gray-600">
            <span class="tw-font-semibold">Progress:</span> {{ completedCount }} / {{ totalProducts }}
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="tw-bg-white tw-rounded-xl tw-shadow tw-p-4">
        <div class="tw-flex tw-flex-col lg:tw-flex-row tw-gap-4">
          <!-- Search -->
          <div class="tw-flex-1">
            <span class="p-input-icon-left tw-w-full">
              <i class="pi pi-search tw-text-gray-400" />
              <InputText 
                v-model="searchQuery"
                @input="onSearchInput"
                placeholder="Search products..." 
                class="tw-w-full"
                :disabled="!canEdit && audit.status === 'completed'"
              />
            </span>
          </div>

          <!-- Stockage Filter (if applicable) -->
          <div v-if="showStockageFilter" class="tw-w-full lg:tw-w-64">
            <Dropdown
              v-model="selectedStockage"
              :options="stockages"
              optionLabel="name"
              optionValue="id"
              placeholder="All Stockages"
              class="tw-w-full"
              showClear
              @change="fetchProducts"
            />
          </div>

          <!-- Status Filter -->
          <div class="tw-w-full lg:tw-w-48">
            <Dropdown
              v-model="statusFilter"
              :options="statusOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="All Status"
              class="tw-w-full"
              @change="applyFilters"
            />
          </div>
        </div>
      </div>

      <!-- Products Table -->
      <div class="tw-bg-white tw-rounded-xl tw-shadow tw-overflow-hidden">
        <DataTable
          :value="filteredProducts"
          :loading="loadingProducts"
          :paginator="true"
          :rows="25"
          :totalRecords="filteredProducts.length"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          :rowsPerPageOptions="[25, 50, 100]"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords} products"
          dataKey="id"
          responsiveLayout="scroll"
          class="tw-w-full"
          editMode="cell"
          @cell-edit-complete="onCellEditComplete"
          :key="tableKey"
        >
          <template #empty>
            <div class="tw-flex tw-flex-col tw-items-center tw-justify-center tw-py-16">
              <i class="pi pi-box tw-text-8xl tw-text-gray-300 tw-mb-4"></i>
              <p class="tw-text-gray-500 tw-text-xl tw-font-semibold tw-mb-2">No products found</p>
            </div>
          </template>

          <!-- Product Name -->
          <Column field="name" header="Product" style="min-width: 300px" frozen>
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-bg-gradient-to-br tw-from-teal-400 tw-to-cyan-500 tw-w-10 tw-h-10 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-text-white tw-font-bold tw-text-sm tw-shadow-md">
                  <i class="pi pi-box"></i>
                </div>
                <div>
                  <div class="tw-font-semibold tw-text-gray-800 tw-text-sm">
                    {{ slotProps.data.name }}
                  </div>
                  <div class="tw-text-xs tw-text-gray-500">
                    {{ slotProps.data.type === 'pharmacy' ? 'Pharmacy' : 'Stock' }} | 
                    {{ slotProps.data.category || 'N/A' }}
                  </div>
                </div>
              </div>
            </template>
          </Column>

          <!-- Stockage -->
          <Column v-if="audit.is_global || (audit.service_id && !audit.stockage_id)" field="stockage_name" header="Stockage" style="min-width: 200px">
            <template #body="slotProps">
              <div class="tw-text-sm tw-text-gray-700">
                {{ slotProps.data.stockage_name || 'N/A' }}
              </div>
            </template>
          </Column>

          <!-- Theoretical Quantity -->
          <Column field="theoretical_quantity" header="Theoretical Qty" style="width: 150px">
            <template #body="slotProps">
              <div class="tw-text-sm tw-font-semibold tw-text-gray-800">
                {{ slotProps.data.theoretical_quantity || 0 }}
              </div>
            </template>
          </Column>

          <!-- Actual Quantity -->
          <Column field="actual_quantity" header="Actual Qty" style="width: 150px">
            <template #body="slotProps">
              <InputNumber
                v-if="canEdit"
                :modelValue="slotProps.data.actual_quantity"
                @update:modelValue="(value) => updateActualQuantity(slotProps.data, value)"
                :min="0"
                :inputClass="'tw-w-full tw-text-sm'"
                :placeholder="'Enter quantity'"
              />
              <div v-else class="tw-text-sm tw-font-semibold tw-text-gray-800">
                {{ slotProps.data.actual_quantity !== null ? slotProps.data.actual_quantity : '-' }}
              </div>
            </template>
          </Column>

          <!-- Difference -->
          <Column field="difference" header="Difference" style="width: 150px">
            <template #body="slotProps">
              <Tag 
                v-if="slotProps.data.actual_quantity !== null"
                :value="getDifferenceText(slotProps.data)"
                :severity="getDifferenceSeverity(slotProps.data)"
                class="tw-text-xs"
              />
              <span v-else class="tw-text-xs tw-text-gray-400">Not counted</span>
            </template>
          </Column>

          <!-- Status -->
          <Column field="status" header="Status" style="width: 120px">
            <template #body="slotProps">
              <Tag 
                :value="slotProps.data.actual_quantity !== null ? 'Counted' : 'Pending'"
                :severity="slotProps.data.actual_quantity !== null ? 'success' : 'warning'"
                class="tw-text-xs"
              />
            </template>
          </Column>

          <!-- Notes -->
          <Column field="notes" header="Notes" style="min-width: 200px">
            <template #body="slotProps">
              <InputText
                v-if="canEdit"
                :modelValue="slotProps.data.notes"
                @update:modelValue="(value) => updateNotes(slotProps.data, value)"
                placeholder="Add notes..."
                class="tw-w-full tw-text-sm"
              />
              <div v-else class="tw-text-sm tw-text-gray-600">
                {{ slotProps.data.notes || '-' }}
              </div>
            </template>
          </Column>

          <!-- Last Updated -->
          <Column field="updated_at" header="Last Updated" style="width: 180px">
            <template #body="slotProps">
              <div class="tw-text-xs tw-text-gray-500">
                {{ formatDateTime(slotProps.data.updated_at) }}
              </div>
            </template>
          </Column>
        </DataTable>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

// PrimeVue Components
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';
import Tag from 'primevue/tag';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';

// Composables
const route = useRoute();
const router = useRouter();
const toast = useToast();
const confirm = useConfirm();

// Reactive state
const audit = ref(null);
const products = ref([]);
const filteredProducts = ref([]);
const stockages = ref([]);
const loading = ref(false);
const loadingProducts = ref(false);
const saving = ref(false);
const searchQuery = ref('');
const searchTimeout = ref(null);
const selectedStockage = ref(null);
const statusFilter = ref('all');
const statusOptions = [
  { label: 'All Products', value: 'all' },
  { label: 'Counted', value: 'counted' },
  { label: 'Pending', value: 'pending' },
  { label: 'With Difference', value: 'difference' }
];
const autoSaveTimeout = ref(null);
const changedProducts = ref(new Set());
const tableKey = ref(0);
const participantId = ref(null);
const participantStatus = ref(null); // Track participant status

// Computed properties
const showStockageFilter = computed(() => {
  return audit.value && (audit.value.is_global || (audit.value.service_id && !audit.value.stockage_id));
});

const totalProducts = computed(() => {
  return products.value.length;
});

const completedCount = computed(() => {
  return products.value.filter(p => p.actual_quantity !== null).length;
});

const canComplete = computed(() => {
  return completedCount.value === totalProducts.value;
});

// Check if participant can edit (not sent or completed)
const canEdit = computed(() => {
  return participantStatus.value !== 'sent' && 
         participantStatus.value !== 'completed' &&
         audit.value?.status !== 'completed' && 
         audit.value?.status !== 'sent';
});

// Lifecycle hooks
onMounted(() => {
  // Capture participant_id from URL query parameter
  participantId.value = route.query.participant ? parseInt(route.query.participant) : null;
  console.log('👤 Participant ID from URL:', participantId.value);
  
  fetchAuditDetails();
});

onBeforeUnmount(() => {
  if (autoSaveTimeout.value) {
    clearTimeout(autoSaveTimeout.value);
  }
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value);
  }
});

// Methods
async function fetchAuditDetails() {
  loading.value = true;
  try {
    const auditId = route.params.id;
    const response = await axios.get(`/api/inventory-audits/${auditId}`);
    audit.value = response.data.data;
    
    // Fetch stockages if needed
    if (showStockageFilter.value) {
      await fetchStockages();
    }
    
    // Fetch products
    await fetchProducts();
  } catch (error) {
    console.error('Failed to fetch audit details:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load audit details',
      life: 3000
    });
  } finally {
    loading.value = false;
  }
}

async function fetchStockages() {
  try {
    let url = '';
    if (audit.value.is_pharmacy_wide) {
      url = '/api/pharmacy-stockages';
    } else if (audit.value.service_id) {
      url = `/api/services/${audit.value.service_id}/stockages`;
    } else {
      url = '/api/stockages';
    }
    
    const response = await axios.get(url);
    stockages.value = response.data.data || response.data;
  } catch (error) {
    console.error('Failed to fetch stockages:', error);
  }
}

async function fetchProducts() {
  loadingProducts.value = true;
  try {
    const params = {
      audit_id: audit.value.id
    };

    // Add filters based on audit scope
    if (audit.value.is_global) {
      params.type = 'all'; // Both pharmacy and stock products
    } else if (audit.value.is_pharmacy_wide) {
      params.type = 'pharmacy';
    } else {
      params.type = 'stock';
      if (audit.value.service_id) {
        params.service_id = audit.value.service_id;
      }
      if (audit.value.stockage_id) {
        params.stockage_id = audit.value.stockage_id;
      } else if (selectedStockage.value) {
        params.stockage_id = selectedStockage.value;
      }
    }

    // Fetch products from purchasing API
    const response = await axios.get('/api/purchasing/products', { params });
    
    // Fetch existing audit items
    const auditItemsResponse = await axios.get(`/api/inventory-audits/${audit.value.id}/items`);
    const auditItems = auditItemsResponse.data.data || [];

    console.log('📦 Loaded audit items from database:', auditItems);
    console.log('📦 Products from API:', response.data.data);
    console.log('👤 Current participant_id from URL:', participantId.value);

    // Filter audit items for current participant (if participant_id is set)
    const filteredAuditItems = participantId.value 
      ? auditItems.filter(item => {
          const matches = item.participant_id == participantId.value;
          console.log(`Checking item ${item.id}: participant_id=${item.participant_id}, matches=${matches}`);
          return matches;
        })
      : auditItems;

    console.log('📦 Filtered audit items for this participant:', filteredAuditItems);
    console.log('📦 Number of filtered items:', filteredAuditItems.length);

    // Extract participant status from first audit item (all items have same participant status)
    if (filteredAuditItems.length > 0 && filteredAuditItems[0].participant_status) {
      participantStatus.value = filteredAuditItems[0].participant_status;
      console.log('👤 Participant Status:', participantStatus.value);
    }

    // Merge products with audit items
    products.value = response.data.data.map(product => {
      // Determine product type - default to 'stock' if not specified
      const productType = product.type || product.product_type || 'stock';
      
      const auditItem = filteredAuditItems.find(item => {
        const productIdMatch = item.product_id == product.id;
        const productTypeMatch = item.product_type === productType;
        console.log(`Matching product ${product.id} "${product.name}":`, {
          productIdMatch,
          productTypeMatch,
          itemProductId: item.product_id,
          itemProductType: item.product_type,
          productId: product.id,
          productType: productType,
          rawProduct: product
        });
        return productIdMatch && productTypeMatch;
      });

      // Parse numeric values from database (they come as strings)
      let actualQuantity = null;
      if (auditItem?.actual_quantity !== undefined && auditItem?.actual_quantity !== null) {
        actualQuantity = parseFloat(auditItem.actual_quantity);
        console.log(`✅ Found actual_quantity for product ${product.id}: ${actualQuantity} (from DB: ${auditItem.actual_quantity})`);
      }

      let theoreticalQuantity = 0;
      if (auditItem?.theoretical_quantity !== undefined && auditItem?.theoretical_quantity !== null) {
        theoreticalQuantity = parseFloat(auditItem.theoretical_quantity);
      } else if (product.quantity !== undefined && product.quantity !== null) {
        theoreticalQuantity = parseFloat(product.quantity);
      }

      const mergedProduct = {
        ...product,
        type: productType, // Ensure type is set
        product_type: productType, // Also set product_type for consistency
        audit_item_id: auditItem?.id || null,
        theoretical_quantity: theoreticalQuantity,
        actual_quantity: actualQuantity,
        notes: auditItem?.notes || '',
        updated_at: auditItem?.updated_at || null,
        difference: auditItem?.difference !== undefined ? parseFloat(auditItem.difference || 0) : null,
        variance_percent: auditItem?.variance_percent !== undefined ? parseFloat(auditItem.variance_percent || 0) : null
      };

      // Log merged products with actual quantities for debugging
      if (auditItem) {
        console.log(`✅ Product "${product.name}" FINAL merged data:`, {
          productId: product.id,
          auditItemId: auditItem.id,
          theoreticalQty: theoreticalQuantity,
          actualQty: actualQuantity,
          notes: mergedProduct.notes,
          rawAuditItem: auditItem
        });
      } else {
        console.log(`⚠️ Product "${product.name}" has NO audit item matched`);
      }

      return mergedProduct;
    });

    console.log('📊 Total products loaded:', products.value.length);
    console.log('📊 Products with actual quantities:', products.value.filter(p => p.actual_quantity !== null).length);

    applyFilters();
    
    // Force table re-render to display loaded data
    tableKey.value++;
  } catch (error) {
    console.error('Failed to fetch products:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load products',
      life: 3000
    });
  } finally {
    loadingProducts.value = false;
  }
}

function onSearchInput() {
  clearTimeout(searchTimeout.value);
  searchTimeout.value = setTimeout(() => {
    applyFilters();
  }, 300);
}

function applyFilters() {
  let filtered = [...products.value];

  // Search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(p => 
      p.name.toLowerCase().includes(query) ||
      (p.category && p.category.toLowerCase().includes(query)) ||
      (p.stockage_name && p.stockage_name.toLowerCase().includes(query))
    );
  }

  // Status filter
  if (statusFilter.value === 'counted') {
    filtered = filtered.filter(p => p.actual_quantity !== null);
  } else if (statusFilter.value === 'pending') {
    filtered = filtered.filter(p => p.actual_quantity === null);
  } else if (statusFilter.value === 'difference') {
    filtered = filtered.filter(p => 
      p.actual_quantity !== null && p.actual_quantity !== p.theoretical_quantity
    );
  }

  filteredProducts.value = filtered;
}

function updateActualQuantity(product, value) {
  // Update the product's actual quantity
  product.actual_quantity = value;
  
  // Mark as changed for auto-save
  changedProducts.value.add(product.id);
  
  // Schedule auto-save
  scheduleAutoSave();
  
  console.log(`📝 Updated actual quantity for "${product.name}":`, value);
}

function onQuantityChange(product) {
  changedProducts.value.add(product.id);
  scheduleAutoSave();
}

function updateNotes(product, value) {
  // Update the product's notes
  product.notes = value;
  
  // Mark as changed for auto-save
  changedProducts.value.add(product.id);
  
  // Schedule auto-save
  scheduleAutoSave();
  
  console.log(`📝 Updated notes for "${product.name}":`, value);
}

function onNotesChange(product) {
  changedProducts.value.add(product.id);
  scheduleAutoSave();
}

function scheduleAutoSave() {
  if (autoSaveTimeout.value) {
    clearTimeout(autoSaveTimeout.value);
  }
  
  autoSaveTimeout.value = setTimeout(() => {
    saveProgress(true);
  }, 2000); // Auto-save after 2 seconds of inactivity
}

async function saveProgress(isAutoSave = false) {
  if (changedProducts.value.size === 0 && !isAutoSave) {
    toast.add({
      severity: 'info',
      summary: 'No Changes',
      detail: 'No changes to save',
      life: 2000
    });
    return;
  }

  saving.value = true;
  try {
    console.log('💾 Saving with participant_id:', participantId.value);
    const items = products.value
      .filter(p => changedProducts.value.has(p.id) || p.actual_quantity !== null)
      .map(p => ({
        product_id: p.id,
        product_type: p.type || p.product_type || 'stock',
        theoretical_quantity: p.theoretical_quantity || 0,
        actual_quantity: p.actual_quantity,
        notes: p.notes || null,
        stockage_id: p.stockage_id || null,
        participant_id: participantId.value // Include participant_id
      }));

    console.log('💾 Saving items with participant_id:', participantId.value, items);

    await axios.post(`/api/inventory-audits/${audit.value.id}/items/bulk`, {
      items,
      status: 'in_progress'
    });

    // Update audit_item_ids for newly created items
    const updatedItemsResponse = await axios.get(`/api/inventory-audits/${audit.value.id}/items`);
    const updatedItems = updatedItemsResponse.data.data || [];
    
    // Update only the audit_item_id without losing current data
    products.value.forEach(product => {
      const updatedItem = updatedItems.find(item => 
        item.product_id == product.id && item.product_type === product.type
      );
      if (updatedItem && !product.audit_item_id) {
        product.audit_item_id = updatedItem.id;
      }
    });

    changedProducts.value.clear();

    if (!isAutoSave) {
      toast.add({
        severity: 'success',
        summary: 'Saved',
        detail: 'Progress saved successfully',
        life: 3000
      });
    }
  } catch (error) {
    console.error('Failed to save progress:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to save progress',
      life: 3000
    });
  } finally {
    saving.value = false;
  }
}

function confirmSendAudit() {
  confirm.require({
    message: 'Are you sure you want to send this audit? All counted items will be submitted for review.',
    header: 'Send Audit',
    icon: 'pi pi-send',
    acceptClass: 'p-button-primary',
    accept: () => sendAudit()
  });
}

async function sendAudit() {
  saving.value = true;
  try {
    // First save all current progress
    const items = products.value
      .filter(p => p.actual_quantity !== null)
      .map(p => ({
        product_id: p.id,
        product_type: p.type || p.product_type || 'stock',
        theoretical_quantity: p.theoretical_quantity || 0,
        actual_quantity: p.actual_quantity,
        notes: p.notes || null,
        stockage_id: p.stockage_id || null,
        participant_id: participantId.value // Include participant_id
      }));

    console.log('📤 Sending items with participant_id:', participantId.value, items);

    // Update status to "sent"
    await axios.post(`/api/inventory-audits/${audit.value.id}/items/bulk`, {
      items,
      status: 'sent'
    });

    changedProducts.value.clear();

    // Update local participant status to disable editing immediately
    participantStatus.value = 'sent';

    toast.add({
      severity: 'success',
      summary: 'Sent',
      detail: 'Audit has been sent successfully. You can no longer make changes.',
      life: 5000
    });

    // Refresh audit details
    await fetchAuditDetails();
  } catch (error) {
    console.error('Failed to send audit:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to send audit',
      life: 3000
    });
  } finally {
    saving.value = false;
  }
}

function confirmCompleteAudit() {
  confirm.require({
    message: 'Are you sure you want to complete this audit? This action cannot be undone.',
    header: 'Complete Audit',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-success',
    accept: () => completeAudit()
  });
}

async function completeAudit() {
  try {
    await axios.post(`/api/inventory-audits/${audit.value.id}/complete`);
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Audit completed successfully',
      life: 3000
    });

    // Refresh audit details
    await fetchAuditDetails();
  } catch (error) {
    console.error('Failed to complete audit:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to complete audit',
      life: 3000
    });
  }
}

async function downloadPDF() {
  try {
    const response = await axios.get(`/api/inventory-audits/${audit.value.id}/pdf`, {
      responseType: 'blob'
    });

    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `audit-${audit.value.id}-${Date.now()}.pdf`);
    document.body.appendChild(link);
    link.click();
    link.remove();

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'PDF downloaded successfully',
      life: 3000
    });
  } catch (error) {
    console.error('Failed to download PDF:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to download PDF',
      life: 3000
    });
  }
}

function getScopeDescription() {
  if (!audit.value) return '';
  
  if (audit.value.is_global) {
    return 'Global Audit - All Products & Stockages';
  } else if (audit.value.is_pharmacy_wide) {
    return 'Pharmacy Audit - All Pharmacy Products';
  } else if (audit.value.service_id && audit.value.stockage_id) {
    return `Service: ${audit.value.service?.name || 'N/A'} - Stockage: ${audit.value.stockage?.name || 'N/A'}`;
  } else if (audit.value.service_id) {
    return `Service: ${audit.value.service?.name || 'N/A'} - All Stockages`;
  }
  return 'Stock Audit';
}

function getDifferenceText(product) {
  const diff = product.actual_quantity - product.theoretical_quantity;
  return diff > 0 ? `+${diff}` : `${diff}`;
}

function getDifferenceSeverity(product) {
  const diff = product.actual_quantity - product.theoretical_quantity;
  if (diff === 0) return 'success';
  if (diff > 0) return 'warning';
  return 'danger';
}

function getStatusSeverity(status) {
  const severities = {
    draft: 'secondary',
    in_progress: 'info',
    sent: 'warning',
    completed: 'success',
    cancelled: 'danger'
  };
  return severities[status] || 'secondary';
}

function getStatusIcon(status) {
  const icons = {
    draft: 'pi pi-file',
    in_progress: 'pi pi-spin pi-spinner',
    sent: 'pi pi-send',
    completed: 'pi pi-check-circle',
    cancelled: 'pi pi-times-circle'
  };
  return icons[status] || 'pi pi-info-circle';
}

function goBack() {
  router.push({ name: 'Inventory-audit.view', params: { id: audit.value.id } });
}

function formatDateTime(dateString) {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}
</script>

<style scoped>
/* Custom styles */
.p-datatable .p-datatable-tbody > tr {
  transition: all 0.2s ease;
}

.p-datatable .p-datatable-tbody > tr:hover {
  background-color: #f0fdfa !important;
}

.p-datatable .p-datatable-thead > tr > th {
  background: linear-gradient(135deg, #14b8a6 0%, #06b6d4 100%) !important;
  color: white !important;
  font-weight: 600 !important;
  border: none !important;
  padding: 1rem !important;
}

.p-inputnumber-input {
  text-align: center;
}
</style>
