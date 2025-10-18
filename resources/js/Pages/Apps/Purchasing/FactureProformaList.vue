<template>
  <div class="tw-p-6 tw-bg-gray-50 tw-min-h-screen">
    <!-- Service Demand Status Banner -->
    <div class="tw-bg-blue-600 tw-text-white tw-p-4 tw-rounded-lg tw-shadow-md tw-mb-6">
      <div class="tw-flex tw-justify-between tw-items-center">
        <div>
          <h2 class="tw-text-lg tw-font-semibold">Current Status: Facture Proforma</h2>
          <p class="tw-text-blue-100 tw-mt-1">Managing proforma invoices for purchase requests</p>
        </div>
        <div class="tw-flex tw-gap-3">
          <Button 
            @click="switchToBonCommend"
            icon="pi pi-arrow-right"
            label="Switch to Bon Commande"
            class="p-button-outlined p-button-light"
          />
        </div>
      </div>
    </div>

    <!-- Header -->
    <div class="tw-bg-white tw-p-6 tw-rounded-lg tw-shadow-md tw-mb-6">
      <div class="tw-flex tw-justify-between tw-items-center tw-mb-4">
        <div>
          <h1 class="tw-text-2xl tw-font-bold tw-text-gray-800">Facture Proforma Management</h1>
          <p class="tw-text-gray-600 tw-mt-1">Manage and edit facture proforma records</p>
        </div>
        <div class="tw-flex tw-gap-3">
          <Button 
            @click="generateAllPdfs"
            :disabled="!selectedProformas.length || generatingPdf"
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
        :selection="selectedProformas"
        @update:selection="selectedProformas = $event"
        :value="proformas"
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
        <Column field="factureProformaCode" header="Code" :sortable="true" class="tw-min-w-[150px]">
          <template #body="{ data }">
            <div class="tw-font-medium tw-text-blue-600">{{ data.factureProformaCode || `FP-${data.id}` }}</div>
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

        <!-- Products Column -->
        <Column header="Products" class="tw-min-w-[200px]">
          <template #body="{ data }">
            <div class="tw-space-y-1">
              <div v-if="data.products && data.products.length > 0">
                <div class="tw-text-sm tw-font-medium tw-text-gray-700">
                  {{ data.products.length }} product(s)
                </div>
                <div class="tw-text-xs tw-text-gray-500">
                  Total Qty: {{ data.products.reduce((sum, p) => sum + (p.quantity || 0), 0) }}
                </div>
              </div>
              <div v-else class="tw-text-sm tw-text-gray-400">
                No products
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
                @click="editProforma(data)"
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
                @click="deleteProforma(data)"
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
      :header="`Edit Facture Proforma - ${selectedProforma?.factureProformaCode || ''}`"
      class="tw-w-full tw-max-w-4xl"
    >
      <div v-if="selectedProforma" class="tw-space-y-4">
        <!-- Basic Info -->
        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
          <!-- <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Status</label>
            <Dropdown
              v-model="selectedProforma.status"
              :options="statusOptions"
              optionLabel="label"
              optionValue="value"
              class="tw-w-full"
            />
          </div> -->
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Supplier</label>
            <Dropdown
              v-model="selectedProforma.fournisseur_id"
              :options="suppliers"
              optionLabel="company_name"
              optionValue="id"
              class="tw-w-full"
              filter
            />
          </div>
        </div>

        <!-- Attachments -->
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Attachments</label>
          <div class="tw-space-y-3">
            <!-- Existing attachments -->
            <div v-if="selectedProforma.attachments && selectedProforma.attachments.length > 0" class="tw-space-y-2">
              <div v-for="attachment in selectedProforma.attachments" :key="attachment.id" class="tw-flex tw-items-center tw-justify-between tw-p-2 tw-bg-gray-50 tw-rounded">
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
            @click="saveProforma"
            :loading="saving"
            label="Save Changes"
            class="p-button-primary"
          />
        </div>
      </template>
    </Dialog>

    <!-- Product Detail Dialog -->
    <!-- <Dialog 
      :visible="productDetailDialog" 
      @update:visible="productDetailDialog = $event"
      modal 
      :header="`Product Details - ${selectedProforma?.factureProformaCode || ''}`"
      class="tw-w-full tw-max-w-4xl"
    >
      <div class="tw-space-y-4" v-if="selectedProducts.length">
        <div 
          v-for="product in selectedProducts" 
          :key="product.id"
          class="tw-border tw-rounded-lg tw-p-4 tw-bg-gray-50"
        >
          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-4 tw-items-end">
            <div>
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">Product</label>
              <div class="tw-font-medium">{{ product.product_name || product.product?.name || `Product ID: ${product.product_id}` }}</div>
              <div class="tw-text-sm tw-text-gray-500">{{ product.product?.product_code || '' }}</div>
            </div>
            <div>
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">Quantity</label>
              <InputNumber
                v-model="product.quantity"
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
              @click="updateProductQuantity(product)"
              :loading="updatingQuantity"
              label="Update"
              icon="pi pi-save"
              class="p-button-primary p-button-sm"
            />
          </div>
        </div>
      </div>
      <div v-else class="tw-text-center tw-py-8 tw-text-gray-500">
        No products found for this proforma.
      </div>
    </Dialog> -->

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
const proformas = ref([])
const suppliers = ref([])
const selectedProformas = ref([])
const selectedProforma = ref(null)
const editDialog = ref(false)
const productDetailDialog = ref(false)
const selectedProducts = ref([])
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
  { label: 'Approved', value: 'approved' },
  { label: 'Rejected', value: 'rejected' },
  { label: 'Completed', value: 'completed' }
]

// Methods
const fetchProformas = async () => {
  try {
    loading.value = true
    const params = new URLSearchParams()
    
    if (filters.status) params.append('status', filters.status)
    if (filters.fournisseur_id) params.append('fournisseur_id', filters.fournisseur_id)
    if (filters.search) params.append('search', filters.search)

    const response = await axios.get(`/api/facture-proformas?${params.toString()}`)
    
    if (response.data.status === 'success') {
      proformas.value = response.data.data.data || response.data.data
    } else {
      throw new Error(response.data.message || 'Failed to fetch proformas')
    }
  } catch (err) {
    console.error('Error fetching proformas:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load facture proformas',
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
  await fetchProformas()
}

const refreshData = async () => {
  await Promise.all([fetchProformas(), fetchSuppliers()])
  toast.add({
    severity: 'success',
    summary: 'Refreshed',
    detail: 'Data refreshed successfully',
    life: 2000
  })
}

const updateStatus = async (proforma) => {
  try {
    const response = await axios.put(`/api/facture-proformas/${proforma.id}`, {
      ...proforma,
      products: proforma.products.map(p => ({
        product_id: p.product_id || p.product?.id,
        quantity: p.quantity,
        price: p.price,
        unit: p.unit
      }))
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

const updateProductQuantity = async (product) => {
  if (!selectedProforma.value) return
  
  updatingQuantity.value = true
  try {
    const response = await axios.put(
      `/api/facture-proformas/${selectedProforma.value.id}/products/${product.id}`,
      {
        quantity: product.quantity,
        price: product.price
      }
    )
    
    if (response.data.status === 'success') {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Product quantity updated successfully',
        life: 3000
      })
      // Refresh the main list
      await fetchProformas()
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.data.message || 'Failed to update product',
        life: 3000
      })
    }
  } catch (err) {
    console.error('Error updating quantity:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to update product quantity',
      life: 3000
    })
  } finally {
    updatingQuantity.value = false
  }
}

const updateProductUnit = async (proforma, product) => {
  try {
    // Update the product unit in the backend
    await updateProformaProduct(proforma, product)
  } catch (err) {
    console.error('Error updating unit:', err)
  }
}

const updateProformaProduct = async (proforma, product) => {
  try {
    const response = await axios.put(`/api/facture-proformas/${proforma.id}`, {
      ...proforma,
      products: proforma.products.map(p => ({
        product_id: p.product_id || p.product?.id,
        quantity: p.quantity,
        price: p.price,
        unit: p.unit
      }))
    })
    
    if (response.data.status === 'success') {
      // Update total amount
      proforma.total_amount = calculateTotal(proforma)
    }
  } catch (err) {
    console.error('Error updating proforma:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to update product',
      life: 3000
    })
  }
}

const viewDetails = (proforma) => {
  selectedProforma.value = { ...proforma }
}

// New function for viewing and editing products
const viewProducts = async (proforma) => {
  try {
    selectedProforma.value = proforma
    
    // If we have products in the current data, use them directly
    if (proforma.products && proforma.products.length > 0) {
      selectedProducts.value = proforma.products
      productDetailDialog.value = true
      return
    }
    
    // Otherwise fetch from API
    const response = await axios.get(`/api/facture-proformas/${proforma.id}/products`)
    
    if (response.data.status === 'success') {
      selectedProducts.value = response.data.data.products || []
      productDetailDialog.value = true
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: 'Failed to fetch product details',
        life: 3000
      })
    }
  } catch (err) {
    console.error('Error fetching products:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to fetch product details',
      life: 3000
    })
  }
}

const editProforma = (proforma) => {
  selectedProforma.value = JSON.parse(JSON.stringify(proforma))
  editDialog.value = true
}

const saveProforma = async () => {
  try {
    saving.value = true
    
    const response = await axios.put(`/api/facture-proformas/${selectedProforma.value.id}`, {
      ...selectedProforma.value,
      products: selectedProforma.value.products.map(p => ({
        product_id: p.product_id || p.product?.id,
        quantity: p.quantity,
        price: p.price,
        unit: p.unit
      }))
    })
    
    if (response.data.status === 'success') {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Proforma updated successfully',
        life: 3000
      })
      
      editDialog.value = false
      await fetchProformas()
    }
  } catch (err) {
    console.error('Error saving proforma:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to save proforma',
      life: 3000
    })
  } finally {
    saving.value = false
  }
}

const removeProduct = (index) => {
  selectedProforma.value.products.splice(index, 1)
}

const generatePdf = async (proforma) => {
  try {
    generatingPdf.value = true
    
    const response = await axios.get(`/api/facture-proformas/${proforma.id}/download`, {
      responseType: 'blob' // Important: responseType blob for file download
    })
    
    if (response.data) {
      // Create a blob from the response data
      const blob = new Blob([response.data], { type: 'application/pdf' })
      const url = window.URL.createObjectURL(blob)
      
      // Create a temporary link element and trigger download
      const link = document.createElement('a')
      link.href = url
      link.download = `facture-proforma-${proforma.factureProformaCode || `FP-${proforma.id}`}.pdf`
      document.body.appendChild(link)
      link.click()
      
      // Clean up
      document.body.removeChild(link)
      window.URL.revokeObjectURL(url)
      
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'PDF downloaded successfully',
        life: 3000
      })
    } else {
      throw new Error('Failed to generate PDF')
    }
  } catch (err) {
    console.error('Error generating PDF:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.message || 'Failed to download PDF',
      life: 3000
    })
  } finally {
    generatingPdf.value = false
  }
}

const generateAllPdfs = async () => {
  try {
    generatingPdf.value = true
    
    for (const proforma of selectedProformas.value) {
      await generatePdf(proforma)
    }
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: `Generated ${selectedProformas.value.length} PDF(s) successfully`,
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

const deleteProforma = (proforma) => {
  confirm.require({
    message: `Are you sure you want to delete ${proforma.factureProformaCode}?`,
    header: 'Confirm Deletion',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      try {
        const response = await axios.delete(`/api/facture-proformas/${proforma.id}`)
        
        if (response.data.status === 'success') {
          toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Proforma deleted successfully',
            life: 3000
          })
          
          await fetchProformas()
        }
      } catch (err) {
        console.error('Error deleting proforma:', err)
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to delete proforma',
          life: 3000
        })
      }
    }
  })
}

// Utility functions
const calculateTotal = (proforma) => {
  if (!proforma.products || !Array.isArray(proforma.products)) {
    return 0
  }
  
  return proforma.products.reduce((total, product) => {
    const quantity = parseFloat(product.quantity) || 0
    const price = parseFloat(product.price) || 0
    return total + (quantity * price)
  }, 0)
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD'
  }).format(amount || 0)
}

const switchToBonCommend = () => {
  // Navigate to bon commend page
  window.location.href = '/apps/purchasing/bon-commends'
}

const handleAttachmentUpload = async (event) => {
  const files = event.target.files
  if (!files.length || !selectedProforma.value) return

  for (const file of files) {
    try {
      const formData = new FormData()
      formData.append('attachment', file)
      formData.append('filename', file.name)

      const response = await axios.post(`/api/facture-proformas/${selectedProforma.value.id}/attachments`, formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })

      if (response.data.status === 'success') {
        // Refresh the proforma data to show new attachment
        await fetchProformas()
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
    const response = await axios.get(`/api/facture-proformas/${selectedProforma.value.id}/attachments/${attachment.id}`, {
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
  if (!selectedProforma.value) return

  try {
    const response = await axios.delete(`/api/facture-proformas/${selectedProforma.value.id}/attachments/${attachmentId}`)

    if (response.data.status === 'success') {
      // Refresh the proforma data
      await fetchProformas()
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
    fetchProformas(),
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