<template>
  <div class="tw-p-6 tw-bg-gray-50 tw-min-h-screen">
    <!-- Service Demand Status Banner -->
    <div class="tw-bg-green-600 tw-text-white tw-p-4 tw-rounded-lg tw-shadow-md tw-mb-6">
      <div class="tw-flex tw-justify-between tw-items-center">
        <div>
          <h2 class="tw-text-lg tw-font-semibold">Current Status: Bon Commande</h2>
          <p class="tw-text-green-100 tw-mt-1">Managing purchase orders and approvals</p>
        </div>
        <div class="tw-flex tw-gap-3">
          <Button 
            @click="switchToFactureProforma"
            icon="pi pi-arrow-left"
            label="Switch to Facture Proforma"
            class="p-button-outlined p-button-light"
          />
        </div>
      </div>
    </div>

    <!-- Header -->
    <div class="tw-bg-white tw-p-6 tw-rounded-lg tw-shadow-md tw-mb-6">
      <div class="tw-flex tw-justify-between tw-items-center tw-mb-4">
        <div>
          <h1 class="tw-text-2xl tw-font-bold tw-text-gray-800">Bon Commend Management</h1>
          <p class="tw-text-gray-600 tw-mt-1">Manage and edit purchase order records</p>
        </div>
        <div class="tw-flex tw-gap-3">
          <Button 
            @click="generateAllPdfs"
            :disabled="!selectedBonCommends.length || generatingPdf"
            icon="pi pi-file-pdf"
            label="Generate PDFs"
            class="p-button-success"
          />
          <Button 
            @click="refreshData"
            icon="pi pi-refresh"
            label="Refresh"
            class="p-button-secondary"
          />
        </div>
      </div>

      <!-- Filters -->
      <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-4">
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Status Filter</label>
          <Dropdown
            v-model="filters.status"
            :options="statusOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="All Status"
            class="tw-w-full"
          />
        </div>
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Supplier</label>
          <Dropdown
            v-model="filters.fournisseur_id"
            :options="suppliers"
            optionLabel="company_name"
            optionValue="id"
            placeholder="All Suppliers"
            class="tw-w-full"
            filter
          />
        </div>
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Search</label>
          <InputText
            v-model="filters.search"
            placeholder="Search by code..."
            class="tw-w-full"
          />
        </div>
        <div class="tw-flex tw-items-end">
          <Button 
            @click="applyFilters"
            icon="pi pi-search"
            label="Filter"
            class="p-button-primary tw-w-full"
          />
        </div>
      </div>
    </div>

    <!-- Data Table -->
    <div class="tw-bg-white tw-rounded-lg tw-shadow-md tw-overflow-hidden">
      <DataTable 
        :selection="selectedBonCommends"
        @update:selection="selectedBonCommends = $event"
        :value="bonCommends"
        class="p-datatable-gridlines"
        responsiveLayout="scroll"
        :paginator="true"
        :rows="10"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        currentPageReportTemplate="Showing {first} to {last} of {totalRecords} records"
        :rowsPerPageOptions="[5, 10, 20, 50]"
        dataKey="id"
        :loading="loading"
        selectionMode="multiple"
      >
        <!-- Selection Column -->
        <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>

        <!-- Code Column -->
        <Column field="bonCommendCode" header="Code" :sortable="true" class="tw-min-w-[150px]">
          <template #body="{ data }">
            <div class="tw-font-medium tw-text-blue-600">{{ data.bonCommendCode || `BC-${data.id}` }}</div>
          </template>
        </Column>

        <!-- Supplier Column -->
        <Column field="fournisseur.company_name" header="Supplier" :sortable="true" class="tw-min-w-[200px]">
          <template #body="{ data }">
            <div class="tw-font-medium">{{ data.fournisseur?.company_name || 'N/A' }}</div>
            <div class="tw-text-sm tw-text-gray-500">{{ data.fournisseur?.contact_person || '' }}</div>
          </template>
        </Column>

        <!-- Status Column (Editable) -->
        <Column field="status" header="Status" class="tw-min-w-[150px]">
          <template #body="{ data }">
            <Dropdown
              v-model="data.status"
              :options="statusOptions"
              optionLabel="label"
              optionValue="value"
              @change="updateStatus(data)"
              class="tw-w-full"
            />
          </template>
        </Column>

        <!-- Items Column -->
        <Column header="Items" class="tw-min-w-[250px]">
          <template #body="{ data }">
            <div class="tw-space-y-2">
              <div 
                v-for="item in data.items" 
                :key="item.id"
                class="tw-flex tw-items-center tw-justify-between tw-p-2 tw-bg-gray-50 tw-rounded-lg"
              >
                <div class="tw-flex-1">
                  <div class="tw-font-medium tw-text-sm">{{ item.product?.name || 'Unknown Product' }}</div>
                  <div class="tw-text-xs tw-text-gray-500">
                    Qty: {{ item.quantity_desired || 0 }} | Unit: {{ item.unit || 'N/A' }}
                  </div>
                  <div class="tw-text-xs tw-text-gray-500">
                    Status: 
                    <Dropdown
                      v-model="item.status"
                      :options="itemStatusOptions"
                      optionLabel="label"
                      optionValue="value"
                      @change="updateItemStatus(data, item)"
                      class="tw-w-20 tw-text-xs"
                    />
                  </div>
                </div>
                <div class="tw-text-sm tw-font-medium tw-text-green-600">
                  {{ formatCurrency(item.price || 0) }}
                </div>
              </div>
            </div>
          </template>
        </Column>


        <!-- Created Date Column -->
        <Column field="created_at" header="Created Date" :sortable="true" class="tw-min-w-[120px]">
          <template #body="{ data }">
            <div class="tw-text-sm">{{ formatDate(data.created_at) }}</div>
          </template>
        </Column>

        <!-- Actions Column -->
        <Column header="Actions" class="tw-min-w-[200px]">
          <template #body="{ data }">
            <div class="tw-flex tw-gap-2">
            
              <Button
                @click="viewItems(data)"
                icon="pi pi-list"
                size="small"
                class="p-button-secondary p-button-sm"
                v-tooltip="'View Items'"
              />
              <Button
                @click="editBonCommend(data)"
                icon="pi pi-pencil"
                size="small"
                class="p-button-warning p-button-sm"
                v-tooltip="'Edit'"
              />
              <Button
                @click="generatePdf(data)"
                :disabled="generatingPdf"
                icon="pi pi-file-pdf"
                size="small"
                class="p-button-success p-button-sm"
                v-tooltip="'Generate PDF'"
              />
              <Button
                @click="deleteBonCommend(data)"
                :disabled="data.status !== 'draft'"
                icon="pi pi-trash"
                size="small"
                class="p-button-danger p-button-sm"
                v-tooltip="'Delete (Draft Only)'"
              />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Edit Dialog -->
    <Dialog 
      :visible="editDialog" 
      @update:visible="editDialog = $event"
      modal 
      :header="`Edit Bon Commend - ${selectedBonCommend?.bonCommendCode || ''}`"
      class="tw-w-full tw-max-w-4xl"
    >
      <div v-if="selectedBonCommend" class="tw-space-y-4">
        <!-- Basic Info -->
        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Status</label>
            <Dropdown
              v-model="selectedBonCommend.status"
              :options="statusOptions"
              optionLabel="label"
              optionValue="value"
              class="tw-w-full"
            />
          </div>
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Supplier</label>
            <Dropdown
              v-model="selectedBonCommend.fournisseur_id"
              :options="suppliers"
              optionLabel="company_name"
              optionValue="id"
              class="tw-w-full"
              filter
            />
          </div>
        </div>

        <!-- Items -->
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Items</label>
          <div class="tw-space-y-3">
            <div 
              v-for="(item, index) in selectedBonCommend.items" 
              :key="item.id || index"
              class="tw-border tw-border-gray-200 tw-rounded-lg tw-p-4"
            >
              <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-5 tw-gap-4">
                <div>
                  <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">Product</label>
                  <div class="tw-font-medium">{{ item.product?.name || 'Unknown Product' }}</div>
                </div>
                <div>
                  <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">Quantity</label>
                  <InputNumber
                    v-model="item.quantity_desired"
                    :min="1"
                    showButtons
                    class="tw-w-full"
                  />
                </div>
                
                <div>
                  <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">Unit</label>
                  <InputText
                    v-model="item.unit"
                    class="tw-w-full"
                  />
                </div>
                <div>
                  <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">Status</label>
                  <Dropdown
                    v-model="item.status"
                    :options="itemStatusOptions"
                    optionLabel="label"
                    optionValue="value"
                    class="tw-w-full"
                  />
                </div>
              </div>
              <div class="tw-flex tw-justify-between tw-items-center tw-mt-3">
                <div class="tw-text-sm tw-text-gray-600">
                  Total: {{ formatCurrency((item.quantity_desired || 0) * (item.price || 0)) }}
                </div>
                <Button
                  @click="removeItem(index)"
                  icon="pi pi-trash"
                  size="small"
                  class="p-button-danger p-button-outlined p-button-sm"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Attachments -->
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Attachments</label>
          <div class="tw-space-y-3">
            <!-- Existing attachments -->
            <div v-if="selectedBonCommend.attachments && selectedBonCommend.attachments.length > 0" class="tw-space-y-2">
              <div v-for="attachment in selectedBonCommend.attachments" :key="attachment.id" class="tw-flex tw-items-center tw-justify-between tw-p-2 tw-bg-gray-50 tw-rounded">
                <div class="tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-file"></i>
                  <span class="tw-text-sm">{{ attachment.filename }}</span>
                </div>
                <div class="tw-flex tw-gap-2">
                  <Button 
                    @click="downloadAttachment(attachment)"
                    icon="pi pi-download"
                    size="small"
                    class="p-button-text p-button-sm"
                    v-tooltip="'Download'"
                  />
                  <Button 
                    @click="removeAttachment(attachment.id)"
                    icon="pi pi-trash"
                    size="small"
                    class="p-button-danger p-button-text p-button-sm"
                    v-tooltip="'Remove'"
                  />
                </div>
              </div>
            </div>
            
            <!-- Upload new attachment -->
            <div class="tw-border-2 tw-border-dashed tw-border-gray-300 tw-rounded-lg tw-p-4 tw-text-center">
              <input
                ref="attachmentInput"
                type="file"
                @change="handleAttachmentUpload"
                class="tw-hidden"
                multiple
                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
              />
              <div @click="$refs.attachmentInput.click()" class="tw-cursor-pointer">
                <i class="pi pi-upload tw-text-2xl tw-text-gray-400"></i>
                <p class="tw-text-sm tw-text-gray-600 tw-mt-2">Click to upload attachments</p>
                <p class="tw-text-xs tw-text-gray-400">PDF, DOC, DOCX, JPG, PNG files</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="tw-flex tw-gap-2">
          <Button 
            @click="editDialog = false"
            label="Cancel"
            class="p-button-secondary"
          />
          <Button 
            @click="saveBonCommend"
            :loading="saving"
            label="Save Changes"
            class="p-button-primary"
          />
        </div>
      </template>
    </Dialog>

    <!-- Item Detail Dialog -->
    <Dialog 
      :visible="itemDetailDialog" 
      @update:visible="itemDetailDialog = $event"
      modal 
      :header="`Item Details - ${selectedBonCommend?.bonCommendCode || ''}`"
      class="tw-w-full tw-max-w-4xl"
    >
      <div class="tw-space-y-4" v-if="selectedItems.length">
        <div 
          v-for="item in selectedItems" 
          :key="item.id"
          class="tw-border tw-rounded-lg tw-p-4 tw-bg-gray-50"
        >
          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-5 tw-gap-4 tw-items-end">
            <div>
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">Product</label>
              <div class="tw-font-medium">{{ item.product_name || item.product?.name || `Product ID: ${item.product_id}` }}</div>
              <div class="tw-text-sm tw-text-gray-500">{{ item.product_code || item.product?.product_code || '' }}</div>
            </div>
            <div>
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">Quantity</label>
              <InputNumber
                v-model="item.quantity"
                :min="1"
                showButtons
                buttonLayout="horizontal"
                incrementButtonClass="p-button-secondary"
                decrementButtonClass="p-button-secondary"
                class="tw-w-full"
              />
            </div>
            <div>
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">Desired Qty</label>
              <InputNumber
                v-model="item.quantity_desired"
                :min="1"
                showButtons
                buttonLayout="horizontal"
                incrementButtonClass="p-button-secondary"
                decrementButtonClass="p-button-secondary"
                class="tw-w-full"
              />
            </div>
          </div>
          <div class="tw-mt-4 tw-flex tw-justify-end">
            <Button 
              @click="updateItemQuantity(item)"
              :loading="updatingQuantity"
              label="Update"
              icon="pi pi-save"
              class="p-button-primary p-button-sm"
            />
          </div>
        </div>
      </div>
      <div v-else class="tw-text-center tw-py-8 tw-text-gray-500">
        No items found for this bon commend.
      </div>
    </Dialog>

    <!-- Confirmation Dialog -->
    <ConfirmDialog />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Dropdown from 'primevue/dropdown'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import ConfirmDialog from 'primevue/confirmdialog'
import axios from 'axios'

// Composables
const toast = useToast()
const confirm = useConfirm()

// Reactive state
const loading = ref(true)
const saving = ref(false)
const generatingPdf = ref(false)
const bonCommends = ref([])
const suppliers = ref([])
const selectedBonCommends = ref([])
const selectedBonCommend = ref(null)
const editDialog = ref(false)
const itemDetailDialog = ref(false)
const selectedItems = ref([])
const updatingQuantity = ref(false)
const attachmentInput = ref(null)

// Filters
const filters = reactive({
  status: null,
  fournisseur_id: null,
  search: ''
})

// Options
const statusOptions = [
  { label: 'All Status', value: null },
  { label: 'Draft', value: 'draft' },
  { label: 'Sent', value: 'sent' },
  { label: 'Paid', value: 'paid' },
  { label: 'Cancelled', value: 'cancelled' }
]

const itemStatusOptions = [
  { label: 'Pending', value: 'pending' },
  { label: 'Approved', value: 'approved' },
  { label: 'Rejected', value: 'rejected' }
]

// Methods
const fetchBonCommends = async () => {
  try {
    loading.value = true
    const params = new URLSearchParams()
    
    if (filters.status) params.append('status', filters.status)
    if (filters.fournisseur_id) params.append('fournisseur_id', filters.fournisseur_id)
    if (filters.search) params.append('search', filters.search)

    const response = await axios.get(`/api/bon-commends?${params.toString()}`)
    
    if (response.data.status === 'success') {
      bonCommends.value = response.data.data.data || response.data.data
    } else {
      throw new Error(response.data.message || 'Failed to fetch bon commends')
    }
  } catch (err) {
    console.error('Error fetching bon commends:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load bon commends',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const fetchSuppliers = async () => {
  try {
    const response = await axios.get('/api/fournisseurs')
    if (response.data.status === 'success') {
      suppliers.value = response.data.data
    } else if (response.data && Array.isArray(response.data)) {
      suppliers.value = response.data
    }
  } catch (err) {
    console.error('Error fetching suppliers:', err)
  }
}

const applyFilters = async () => {
  await fetchBonCommends()
}

const refreshData = async () => {
  await Promise.all([fetchBonCommends(), fetchSuppliers()])
  toast.add({
    severity: 'success',
    summary: 'Refreshed',
    detail: 'Data refreshed successfully',
    life: 2000
  })
}

const updateStatus = async (bonCommend) => {
  try {
    const response = await axios.put(`/api/bon-commends/${bonCommend.id}/status`, {
      status: bonCommend.status
    })
    
    if (response.data.status === 'success') {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Status updated successfully',
        life: 3000
      })
    }
  } catch (err) {
    console.error('Error updating status:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to update status',
      life: 3000
    })
  }
}

const updateItemQuantity = async (item) => {
  if (!selectedBonCommend.value) return
  
  updatingQuantity.value = true
  try {
    const response = await axios.put(
      `/api/bon-commends/${selectedBonCommend.value.id}/items/${item.id}`,
      {
        quantity: item.quantity,
        quantity_desired: item.quantity_desired,
        price: item.price
      }
    )
    
    if (response.data.status === 'success') {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Item quantity updated successfully',
        life: 3000
      })
      // Refresh the main list
      await fetchBonCommends()
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.data.message || 'Failed to update item',
        life: 3000
      })
    }
  } catch (err) {
    console.error('Error updating quantity:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to update item quantity',
      life: 3000
    })
  } finally {
    updatingQuantity.value = false
  }
}

const updateItemUnit = async (bonCommend, item) => {
  try {
    await updateBonCommendItem(bonCommend, item)
  } catch (err) {
    console.error('Error updating unit:', err)
  }
}

const updateItemStatus = async (bonCommend, item) => {
  try {
    await updateBonCommendItem(bonCommend, item)
  } catch (err) {
    console.error('Error updating item status:', err)
  }
}

const updateBonCommendItem = async (bonCommend, item) => {
  try {
    const response = await axios.put(`/api/bon-commends/${bonCommend.id}`, {
      ...bonCommend,
      items: bonCommend.items.map(i => ({
        factureproforma_id: i.factureproforma_id,
        product_id: i.product_id || i.product?.id,
        quantity: i.quantity,
        quantity_desired: i.quantity_desired,
        price: i.price,
        unit: i.unit,
        status: i.status
      }))
    })
    
    if (response.data.status === 'success') {
      // Update calculated total
      bonCommend.total_amount = calculateTotal(bonCommend)
    }
  } catch (err) {
    console.error('Error updating bon commend:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to update item',
      life: 3000
    })
  }
}

const viewDetails = (bonCommend) => {
  selectedBonCommend.value = { ...bonCommend }
  editDialog.value = true
}

// New function for viewing and editing items
const viewItems = async (bonCommend) => {
  try {
    selectedBonCommend.value = bonCommend
    
    // If we have items in the current data, use them directly
    if (bonCommend.items && bonCommend.items.length > 0) {
      selectedItems.value = bonCommend.items
      itemDetailDialog.value = true
      return
    }
    
    // Otherwise fetch from API
    const response = await axios.get(`/api/bon-commends/${bonCommend.id}/items`)
    
    if (response.data.status === 'success') {
      selectedItems.value = response.data.data.items || []
      itemDetailDialog.value = true
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: 'Failed to fetch item details',
        life: 3000
      })
    }
  } catch (err) {
    console.error('Error fetching items:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to fetch item details',
      life: 3000
    })
  }
}

const editBonCommend = (bonCommend) => {
  selectedBonCommend.value = JSON.parse(JSON.stringify(bonCommend))
  editDialog.value = true
}

const saveBonCommend = async () => {
  try {
    saving.value = true
    
    const response = await axios.put(`/api/bon-commends/${selectedBonCommend.value.id}`, {
      ...selectedBonCommend.value,
      items: selectedBonCommend.value.items.map(i => ({
        factureproforma_id: i.factureproforma_id,
        product_id: i.product_id || i.product?.id,
        quantity: i.quantity,
        quantity_desired: i.quantity_desired,
        price: i.price,
        unit: i.unit,
        status: i.status
      }))
    })
    
    if (response.data.status === 'success') {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Bon commend updated successfully',
        life: 3000
      })
      
      editDialog.value = false
      await fetchBonCommends()
    }
  } catch (err) {
    console.error('Error saving bon commend:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to save bon commend',
      life: 3000
    })
  } finally {
    saving.value = false
  }
}

const removeItem = (index) => {
  selectedBonCommend.value.items.splice(index, 1)
}

const generatePdf = async (bonCommend) => {
  try {
    generatingPdf.value = true
    
    const response = await axios.get(`/api/bon-commends/${bonCommend.id}/download`, {
      responseType: 'blob' // Important: responseType blob for file download
    })
    
    if (response.data) {
      // Create a blob from the response data
      const blob = new Blob([response.data], { type: 'text/html' })
      const url = window.URL.createObjectURL(blob)
      
      // Create a temporary link element and trigger download
      const link = document.createElement('a')
      link.href = url
      link.download = `bon-commend-${bonCommend.bonCommendCode || `BC-${bonCommend.id}`}.html`
      document.body.appendChild(link)
      link.click()
      
      // Clean up
      document.body.removeChild(link)
      window.URL.revokeObjectURL(url)
      
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Document downloaded successfully',
        life: 3000
      })
    } else {
      throw new Error('Failed to generate document')
    }
  } catch (err) {
    console.error('Error generating document:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.message || 'Failed to download document',
      life: 3000
    })
  } finally {
    generatingPdf.value = false
  }
}

const generateAllPdfs = async () => {
  try {
    generatingPdf.value = true
    
    for (const bonCommend of selectedBonCommends.value) {
      await generatePdf(bonCommend)
    }
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: `Generated ${selectedBonCommends.value.length} PDF(s) successfully`,
      life: 3000
    })
  } catch (err) {
    console.error('Error generating PDFs:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to generate PDFs',
      life: 3000
    })
  } finally {
    generatingPdf.value = false
  }
}

const deleteBonCommend = (bonCommend) => {
  confirm.require({
    message: `Are you sure you want to delete ${bonCommend.bonCommendCode}?`,
    header: 'Confirm Deletion',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      try {
        const response = await axios.delete(`/api/bon-commends/${bonCommend.id}`)
        
        if (response.data.status === 'success') {
          toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Bon commend deleted successfully',
            life: 3000
          })
          
          await fetchBonCommends()
        }
      } catch (err) {
        console.error('Error deleting bon commend:', err)
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to delete bon commend',
          life: 3000
        })
      }
    }
  })
}

// Utility functions
const calculateTotal = (bonCommend) => {
  if (!bonCommend.items || !Array.isArray(bonCommend.items)) {
    return 0
  }
  
  return bonCommend.items.reduce((total, item) => {
    const quantity = parseFloat(item.quantity_desired) || 0
    const price = parseFloat(item.price) || 0
    return total + (quantity * price)
  }, 0)
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD'
  }).format(amount || 0)
}

const switchToFactureProforma = () => {
  // Navigate to facture proforma page
  window.location.href = '/apps/purchasing/facture-proformas'
}

const handleAttachmentUpload = async (event) => {
  const files = event.target.files
  if (!files.length || !selectedBonCommend.value) return

  for (const file of files) {
    try {
      const formData = new FormData()
      formData.append('attachment', file)
      formData.append('filename', file.name)

      const response = await axios.post(`/api/bon-commends/${selectedBonCommend.value.id}/attachments`, formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })

      if (response.data.status === 'success') {
        // Refresh the bon commend data to show new attachment
        await fetchBonCommends()
        toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Attachment uploaded successfully',
          life: 3000
        })
      }
    } catch (err) {
      console.error('Error uploading attachment:', err)
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: 'Failed to upload attachment',
        life: 3000
      })
    }
  }

  // Clear the input
  event.target.value = ''
}

const downloadAttachment = async (attachment) => {
  try {
    const response = await axios.get(`/api/bon-commends/${selectedBonCommend.value.id}/attachments/${attachment.id}`, {
      responseType: 'blob'
    })

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.download = attachment.filename
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (err) {
    console.error('Error downloading attachment:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to download attachment',
      life: 3000
    })
  }
}

const removeAttachment = async (attachmentId) => {
  if (!selectedBonCommend.value) return

  try {
    const response = await axios.delete(`/api/bon-commends/${selectedBonCommend.value.id}/attachments/${attachmentId}`)

    if (response.data.status === 'success') {
      // Refresh the bon commend data
      await fetchBonCommends()
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Attachment removed successfully',
        life: 3000
      })
    }
  } catch (err) {
    console.error('Error removing attachment:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to remove attachment',
      life: 3000
    })
  }
}

// Lifecycle
onMounted(async () => {
  await Promise.all([
    fetchBonCommends(),
    fetchSuppliers()
  ])
})
</script>

<style scoped>
:deep(.p-datatable) {
  border: 0;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
  background-color: #f3f4f6;
  border-color: #e5e7eb;
  color: #374151;
  font-weight: 600;
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
  border-color: #e5e7eb;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background-color: #f9fafb;
}

:deep(.p-dialog .p-dialog-header) {
  background-color: #2563eb;
  color: white;
}

:deep(.p-dialog .p-dialog-content) {
  background-color: white;
}
</style>