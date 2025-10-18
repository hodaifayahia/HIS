<template>
  <div class="tw-space-y-6">
    <!-- Header Information -->
    <div v-if="demand" class="tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50 tw-p-6 tw-rounded-xl tw-border tw-border-blue-200">
      <h4 class="tw-text-2xl tw-font-bold tw-text-blue-800 tw-mb-3">
        Create Price Inquiry Proforma
      </h4>
      <div class="tw-space-y-2 tw-text-sm tw-text-blue-700">
        <div><strong>Service Demand:</strong> {{ demand.demand_code || 'N/A' }}</div>
        <div><strong>Service:</strong> {{ demand.service?.name || 'N/A' }}</div>
        <div><strong>Expected Date:</strong> {{ formatDate(demand.expected_date) }}</div>
        <div class="tw-bg-blue-100 tw-px-3 tw-py-2 tw-rounded-md tw-mt-3">
          <i class="pi pi-info-circle tw-mr-2"></i>
          <span class="tw-font-medium">Price inquiry - No prices will be shown in the proforma</span>
        </div>
      </div>
    </div>

    <!-- Products Table with Supplier Assignment -->
    <div v-if="demand?.items?.length" class="tw-bg-white tw-rounded-xl tw-shadow-lg tw-overflow-hidden">
      <div class="tw-bg-gradient-to-r tw-from-green-500 tw-to-emerald-600 tw-text-white tw-p-4">
        <h5 class="tw-text-lg tw-font-bold tw-flex tw-items-center tw-gap-2">
          <i class="pi pi-shopping-cart"></i>
          Products & Supplier Assignment
        </h5>
        <p class="tw-text-green-100 tw-text-sm tw-mt-1">Assign suppliers to products for price inquiries</p>
      </div>

      <div class="tw-p-4">
        <DataTable 
          :value="demand.items" 
          stripedRows 
          responsiveLayout="scroll"
          class="tw-w-full"
        >
          <!-- Product Info Column -->
          <Column header="Product" class="tw-min-w-0">
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-bg-gradient-to-r tw-from-blue-500 tw-to-indigo-600 tw-rounded-lg tw-p-2 tw-flex-shrink-0">
                  <i class="pi pi-box tw-text-white tw-text-sm"></i>
                </div>
                <div class="tw-min-w-0">
                  <h6 class="tw-font-bold tw-text-gray-900 tw-text-sm tw-truncate">
                    {{ slotProps.data.product?.name || 'Unknown Product' }}
                  </h6>
                  <p class="tw-text-xs tw-text-gray-600 tw-truncate">
                    Code: {{ slotProps.data.product?.product_code || 'N/A' }}
                  </p>
                </div>
              </div>
            </template>
          </Column>

          <!-- Quantity Column -->
          <Column header="Quantity" class="tw-w-32">
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-sort-numeric-up tw-text-green-500"></i>
                <span class="tw-font-semibold tw-text-gray-800">{{ slotProps.data.quantity }}</span>
                <Tag v-if="slotProps.data.quantity_by_box" value="Box" severity="success" class="tw-text-xs" />
              </div>
            </template>
          </Column>

          <!-- Assigned Suppliers Column -->
          <Column header="Assigned Suppliers" class="tw-min-w-0">
            <template #body="slotProps">
              <div class="tw-space-y-2">
                <!-- Show existing assignments -->
                <div 
                  v-for="supplier in getAssignedSuppliers(slotProps.data)" 
                  :key="supplier.id"
                  class="tw-bg-gradient-to-r tw-from-indigo-50 tw-to-purple-50 tw-border tw-border-indigo-200 tw-rounded-lg tw-p-2 tw-flex tw-items-center tw-justify-between"
                >
                  <div class="tw-flex tw-items-center tw-gap-2 tw-min-w-0 tw-flex-1">
                    <div class="tw-bg-gradient-to-r tw-from-indigo-500 tw-to-purple-600 tw-rounded-full tw-p-1 tw-flex-shrink-0">
                      <i class="pi pi-building tw-text-white tw-text-xs"></i>
                    </div>
                    <span class="tw-font-medium tw-text-sm tw-text-gray-900 tw-truncate">
                      {{ supplier.company_name }}
                    </span>
                  </div>
                  <Button 
                    icon="pi pi-times" 
                    class="tw-bg-red-100 hover:tw-bg-red-200 tw-text-red-700 tw-border-red-300 tw-rounded-full tw-w-6 tw-h-6 tw-p-0"
                    @click="removeSupplierFromProduct(slotProps.data, supplier)"
                    size="small"
                    v-tooltip="'Remove supplier'"
                  />
                </div>

                <!-- Add supplier button -->
                <Button 
                  @click="showSupplierSelectionForProduct(slotProps.data)"
                  icon="pi pi-plus"
                  label="Add Supplier"
                  class="tw-bg-gradient-to-r tw-from-green-500 tw-to-emerald-600 hover:tw-from-green-600 hover:tw-to-emerald-700 tw-border-0 tw-shadow-md tw-w-full"
                  size="small"
                />
              </div>
            </template>
          </Column>

          <!-- Actions Column -->
          <Column header="Actions" class="tw-w-40">
            <template #body="slotProps">
              <div class="tw-flex tw-gap-2 tw-justify-center">
                <Button 
                  v-if="getAssignedSuppliers(slotProps.data).length > 0"
                  @click="generateProductProformas(slotProps.data)"
                  icon="pi pi-file-pdf"
                  label="Generate PDFs"
                  class="tw-bg-gradient-to-r tw-from-red-500 tw-to-rose-600 hover:tw-from-red-600 hover:tw-to-rose-700 tw-border-0 tw-shadow-md"
                  size="small"
                  :loading="generatingPdf"
                />
                <span v-else class="tw-text-gray-400 tw-text-sm tw-italic">No suppliers assigned</span>
              </div>
            </template>
          </Column>
        </DataTable>
      </div>
    </div>

    <!-- Supplier Selection Dialog -->
    <Dialog 
      :visible="showSupplierDialog"
      @update:visible="showSupplierDialog = $event"
      header="Select Supplier"
      :modal="true"
      :style="{ width: '500px' }"
    >
      <div class="tw-space-y-4">
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
            Available Suppliers
          </label>
          <Dropdown 
            v-model="selectedSupplierId"
            :options="availableSuppliersForProduct"
            option-label="company_name"
            option-value="id"
            placeholder="Choose a supplier to assign"
            class="tw-w-full"
            filter
          >
            <template #option="slotProps">
              <div class="tw-flex tw-flex-col tw-gap-1">
                <span class="tw-font-medium">{{ slotProps.option.company_name }}</span>
                <small class="tw-text-gray-500">
                  Contact: {{ slotProps.option.contact_person }} | 
                  {{ slotProps.option.email }}
                </small>
              </div>
            </template>
          </Dropdown>
        </div>
      </div>

      <template #footer>
        <div class="tw-flex tw-justify-end tw-gap-3">
          <Button 
            label="Cancel" 
            outlined 
            @click="closeSupplierDialog"
          />
          <Button 
            label="Assign Supplier" 
            @click="assignSupplierToProduct"
            :disabled="!selectedSupplierId"
            class="tw-bg-blue-600 hover:tw-bg-blue-700"
          />
        </div>
      </template>
    </Dialog>

    <!-- Global Actions -->
    <div v-if="demand?.items?.length" class="tw-bg-gray-50 tw-p-4 tw-rounded-xl tw-border">
      <h5 class="tw-text-lg tw-font-bold tw-text-gray-800 tw-mb-3">Generate All Proformas</h5>
      <div class="tw-flex tw-gap-3 tw-flex-wrap">
        <Button 
          @click="generateAllProformasBySupplier"
          icon="pi pi-file-export"
          label="Generate by Supplier"
          class="tw-bg-gradient-to-r tw-from-amber-500 tw-to-orange-600 hover:tw-from-amber-600 hover:tw-to-orange-700 tw-border-0 tw-shadow-lg"
          :loading="generatingPdf"
          :disabled="!hasAnyAssignments"
        />
        <Button 
          @click="generateAllProformasAtOnce"
          icon="pi pi-file-pdf"
          label="Download All PDFs"
          class="tw-bg-gradient-to-r tw-from-red-500 tw-to-rose-600 hover:tw-from-red-600 hover:tw-to-rose-700 tw-border-0 tw-shadow-lg"
          :loading="generatingPdf"
          :disabled="!hasAnyAssignments"
        />
      </div>
      <p class="tw-text-sm tw-text-gray-600 tw-mt-2">
        <i class="pi pi-info-circle tw-mr-1"></i>
        Generate separate proforma PDFs for each supplier with assigned products
      </p>
    </div>

    <!-- No Products Message -->
        <!-- No Products Message -->
    <div v-else class="tw-text-center tw-py-12">
      <div class="tw-bg-gradient-to-br tw-from-gray-100 tw-to-gray-200 tw-rounded-full tw-w-24 tw-h-24 tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-6">
        <i class="pi pi-shopping-bag tw-text-4xl tw-text-gray-500"></i>
      </div>
      <h3 class="tw-text-xl tw-font-bold tw-text-gray-900 tw-mb-3">No Products Available</h3>
      <p class="tw-text-gray-600 tw-max-w-md tw-mx-auto">
        This service demand doesn't have any products to create proformas for.
      </p>
    </div>

    <!-- Form Actions -->
    <div class="tw-flex tw-justify-end tw-gap-3 tw-pt-6 tw-border-t tw-border-gray-200">
      <Button 
        type="button"
        @click="$emit('cancel')"
        label="Cancel"
        class="p-button-secondary"
      />
    </div>
    <div v-if="selectedAssignmentIds.length" class="tw-bg-blue-50 tw-p-4 tw-rounded-md tw-border tw-border-blue-200">
      <div class="tw-flex tw-justify-between tw-items-center tw-mb-2">
        <span class="tw-text-sm tw-font-medium tw-text-blue-700">
          Selected: {{ selectedAssignmentIds.length }} assignment(s)
        </span>
        <span class="tw-text-lg tw-font-bold tw-text-blue-900">
          Total: {{ formatCurrency(calculateTotal()) }}
        </span>
      </div>
      <div class="tw-text-xs tw-text-blue-600">
        Total Items: {{ calculateTotalItems() }}
      </div>
    </div>

    <!-- Form Actions -->
    <div class="tw-flex tw-justify-end tw-gap-3 tw-pt-4 tw-border-t tw-border-gray-200">
      <Button 
        type="button"
        @click="$emit('cancel')"
        label="Cancel"
        class="p-button-secondary"
        :disabled="loading"
      />
      <Button 
        type="button"
        @click="handleCreateProforma"
        label="Create Facture Proforma"
        :loading="loading"
        :disabled="!selectedSupplierId || !selectedAssignmentIds.length"
        class="p-button-success"
        icon="pi pi-file-plus"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, defineProps, defineEmits } from 'vue'
import { useToast } from 'primevue/usetoast'

// PrimeVue Components
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dropdown from 'primevue/dropdown'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import Toast from 'primevue/toast'

// Props
const props = defineProps({
  demand: {
    type: Object,
    default: null
  },
  suppliers: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits(['save', 'cancel'])

// Composables
const toast = useToast()

// State
const productSupplierAssignments = ref({}) // productId -> [supplierIds]
const showSupplierDialog = ref(false)
const selectedSupplierId = ref(null)
const currentProduct = ref(null)
const generatingPdf = ref(false)

// Computed
const availableSuppliersForProduct = computed(() => {
  if (!currentProduct.value) return props.suppliers
  
  const assignedSupplierIds = getAssignedSuppliers(currentProduct.value).map(s => s.id)
  return props.suppliers.filter(supplier => !assignedSupplierIds.includes(supplier.id))
})

const hasAnyAssignments = computed(() => {
  return Object.values(productSupplierAssignments.value).some(suppliers => suppliers.length > 0)
})

// Methods
const getAssignedSuppliers = (product) => {
  const assignedSupplierIds = productSupplierAssignments.value[product.id] || []
  return props.suppliers.filter(supplier => assignedSupplierIds.includes(supplier.id))
}

const showSupplierSelectionForProduct = (product) => {
  currentProduct.value = product
  selectedSupplierId.value = null
  showSupplierDialog.value = true
}

const closeSupplierDialog = () => {
  showSupplierDialog.value = false
  currentProduct.value = null
  selectedSupplierId.value = null
}

const assignSupplierToProduct = () => {
  if (!currentProduct.value || !selectedSupplierId.value) return
  
  if (!productSupplierAssignments.value[currentProduct.value.id]) {
    productSupplierAssignments.value[currentProduct.value.id] = []
  }
  
  if (!productSupplierAssignments.value[currentProduct.value.id].includes(selectedSupplierId.value)) {
    productSupplierAssignments.value[currentProduct.value.id].push(selectedSupplierId.value)
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Supplier assigned successfully',
      life: 3000
    })
  }
  
  closeSupplierDialog()
}

const removeSupplierFromProduct = (product, supplier) => {
  if (!productSupplierAssignments.value[product.id]) return
  
  const index = productSupplierAssignments.value[product.id].indexOf(supplier.id)
  if (index > -1) {
    productSupplierAssignments.value[product.id].splice(index, 1)
    
    toast.add({
      severity: 'info',
      summary: 'Removed',
      detail: 'Supplier removed from product',
      life: 3000
    })
  }
}

// PDF Generation Methods
const generateProductProformas = async (product) => {
  const assignedSuppliers = getAssignedSuppliers(product)
  if (!assignedSuppliers.length) return
  
  generatingPdf.value = true
  
  try {
    for (const supplier of assignedSuppliers) {
      await generatePdfForSupplier(supplier, [product])
    }
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: `Generated ${assignedSuppliers.length} PDF(s) for ${product.product?.name}`,
      life: 3000
    })
  } catch (error) {
    console.error('Error generating PDFs:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to generate PDF proformas',
      life: 3000
    })
  } finally {
    generatingPdf.value = false
  }
}

const generateAllProformasBySupplier = async () => {
  generatingPdf.value = true
  
  try {
    const supplierProductMap = {}
    
    // Group products by supplier
    Object.keys(productSupplierAssignments.value).forEach(productId => {
      const product = props.demand.items.find(item => item.id == productId)
      if (!product) return
      
      const assignedSupplierIds = productSupplierAssignments.value[productId] || []
      
      assignedSupplierIds.forEach(supplierId => {
        if (!supplierProductMap[supplierId]) {
          supplierProductMap[supplierId] = []
        }
        supplierProductMap[supplierId].push(product)
      })
    })
    
    let totalPdfs = 0
    
    // Generate PDF for each supplier
    for (const [supplierId, products] of Object.entries(supplierProductMap)) {
      const supplier = props.suppliers.find(s => s.id == supplierId)
      if (supplier && products.length > 0) {
        await generatePdfForSupplier(supplier, products)
        totalPdfs++
      }
    }
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: `Generated ${totalPdfs} PDF proforma(s) by supplier`,
      life: 3000
    })
  } catch (error) {
    console.error('Error generating PDFs:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to generate PDF proformas',
      life: 3000
    })
  } finally {
    generatingPdf.value = false
  }
}

const generateAllProformasAtOnce = async () => {
  generatingPdf.value = true
  
  try {
    const supplierProductMap = {}
    
    // Group products by supplier
    Object.keys(productSupplierAssignments.value).forEach(productId => {
      const product = props.demand.items.find(item => item.id == productId)
      if (!product) return
      
      const assignedSupplierIds = productSupplierAssignments.value[productId] || []
      
      assignedSupplierIds.forEach(supplierId => {
        if (!supplierProductMap[supplierId]) {
          supplierProductMap[supplierId] = []
        }
        supplierProductMap[supplierId].push(product)
      })
    })
    
    // Generate all PDFs concurrently
    const pdfPromises = Object.entries(supplierProductMap).map(([supplierId, products]) => {
      const supplier = props.suppliers.find(s => s.id == supplierId)
      if (supplier && products.length > 0) {
        return generatePdfForSupplier(supplier, products)
      }
      return Promise.resolve()
    })
    
    await Promise.all(pdfPromises)
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: `All PDF proformas downloaded successfully`,
      life: 3000
    })
  } catch (error) {
    console.error('Error generating PDFs:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to generate PDF proformas',
      life: 3000
    })
  } finally {
    generatingPdf.value = false
  }
}

const generatePdfForSupplier = async (supplier, products) => {
  try {
    // Import jsPDF dynamically
    const jsPDF = (await import('jspdf')).default
    
    // Create PDF document
    const pdf = new jsPDF('p', 'mm', 'a4')
    const pageWidth = pdf.internal.pageSize.getWidth()
    const pageHeight = pdf.internal.pageSize.getHeight()
    
    // Add clinic logo (if available)
    try {
      const logoImg = new Image()
      logoImg.crossOrigin = 'anonymous'
      
      await new Promise((resolve, reject) => {
        logoImg.onload = () => resolve()
        logoImg.onerror = () => resolve() // Continue even if logo fails
        logoImg.src = '/images/clinic-logo.png'
      })
      
      if (logoImg.complete && logoImg.width > 0) {
        const logoMaxWidth = 40
        const logoMaxHeight = 20
        const aspectRatio = logoImg.width / logoImg.height
        
        let logoWidth = logoMaxWidth
        let logoHeight = logoMaxWidth / aspectRatio
        
        if (logoHeight > logoMaxHeight) {
          logoHeight = logoMaxHeight
          logoWidth = logoMaxHeight * aspectRatio
        }
        
        pdf.addImage(logoImg, 'PNG', 15, 15, logoWidth, logoHeight)
      }
    } catch (error) {
      console.log('Logo not found, continuing without logo')
    }

    // Add header
    pdf.setFontSize(20)
    pdf.setFont('helvetica', 'bold')
    pdf.text('PROFORMA - PRICE INQUIRY', pageWidth / 2, 25, { align: 'center' })
    
    pdf.setFontSize(12)
    pdf.setFont('helvetica', 'normal')
    pdf.text(`Date: ${new Date().toLocaleDateString('fr-FR')}`, pageWidth - 15, 35, { align: 'right' })
    
    // Add demand and supplier details
    let yPosition = 50
    
    pdf.setFontSize(14)
    pdf.setFont('helvetica', 'bold')
    pdf.text('DEMAND INFORMATION', 15, yPosition)
    
    yPosition += 10
    pdf.setFontSize(11)
    pdf.setFont('helvetica', 'normal')
    
    const demandInfo = [
      `Demand Code: ${props.demand.demand_code || 'N/A'}`,
      `Service: ${props.demand.service?.name || 'N/A'}`,
      `Expected Date: ${formatDate(props.demand.expected_date)}`,
      `Status: ${props.demand.status || 'N/A'}`
    ]
    
    demandInfo.forEach(info => {
      pdf.text(info, 15, yPosition)
      yPosition += 7
    })
    
    // Add supplier information
    yPosition += 10
    pdf.setFontSize(14)
    pdf.setFont('helvetica', 'bold')
    pdf.text('SUPPLIER INFORMATION', 15, yPosition)
    
    yPosition += 10
    pdf.setFontSize(11)
    pdf.setFont('helvetica', 'normal')
    
    const supplierInfo = [
      `Company: ${supplier.company_name || 'N/A'}`,
      `Contact: ${supplier.contact_person || 'N/A'}`,
      `Email: ${supplier.email || 'N/A'}`,
      `Phone: ${supplier.phone || 'N/A'}`
    ]
    
    supplierInfo.forEach(info => {
      pdf.text(info, 15, yPosition)
      yPosition += 7
    })
    
    // Add products table
    yPosition += 15
    pdf.setFontSize(14)
    pdf.setFont('helvetica', 'bold')
    pdf.text('PRODUCTS FOR PRICE INQUIRY', 15, yPosition)
    
    yPosition += 10
    
    // Table headers
    const headers = ['Product Name', 'Product Code', 'Quantity', 'Unit', 'Notes']
    const colWidths = [50, 30, 25, 20, 60]
    let xPosition = 15
    
    pdf.setFontSize(10)
    pdf.setFont('helvetica', 'bold')
    
    // Draw header background
    pdf.setFillColor(230, 230, 230)
    pdf.rect(15, yPosition - 5, pageWidth - 30, 10, 'F')
    
    headers.forEach((header, index) => {
      pdf.text(header, xPosition + 2, yPosition)
      xPosition += colWidths[index]
    })
    
    yPosition += 8
    
    // Table rows
    pdf.setFont('helvetica', 'normal')
    products.forEach((product, index) => {
      // Check if we need a new page
      if (yPosition > pageHeight - 30) {
        pdf.addPage()
        yPosition = 20
      }
      
      xPosition = 15
      
      // Alternate row colors
      if (index % 2 === 0) {
        pdf.setFillColor(248, 248, 248)
        pdf.rect(15, yPosition - 5, pageWidth - 30, 10, 'F')
      }
      
      const rowData = [
        product.product?.name || 'N/A',
        product.product?.product_code || 'N/A',
        product.quantity?.toString() || '0',
        product.quantity_by_box ? 'Box' : 'Unit',
        product.notes || 'Price inquiry request'
      ]
      
      rowData.forEach((data, colIndex) => {
        const maxWidth = colWidths[colIndex] - 4
        const lines = pdf.splitTextToSize(data, maxWidth)
        pdf.text(lines, xPosition + 2, yPosition)
        xPosition += colWidths[colIndex]
      })
      
      yPosition += 10
    })
    
    // Add price inquiry note
    yPosition += 15
    pdf.setFillColor(255, 255, 200)
    pdf.rect(15, yPosition - 5, pageWidth - 30, 20, 'F')
    
    pdf.setFontSize(12)
    pdf.setFont('helvetica', 'bold')
    pdf.text('PRICE INQUIRY REQUEST', 15, yPosition + 2)
    
    pdf.setFontSize(10)
    pdf.setFont('helvetica', 'normal')
    pdf.text('Please provide your best prices for the above products.', 15, yPosition + 10)
    pdf.text('This is a request for quotation - no prices are shown.', 15, yPosition + 17)
    
    // Add footer
    const footerY = pageHeight - 20
    pdf.setFontSize(8)
    pdf.setFont('helvetica', 'italic')
    pdf.text('Price Inquiry Document - Generated Automatically', pageWidth / 2, footerY, { align: 'center' })
    pdf.text(`Generated on ${new Date().toLocaleString('fr-FR')}`, pageWidth / 2, footerY + 5, { align: 'center' })
    
    // Save PDF
    const fileName = `price_inquiry_${supplier.company_name?.replace(/[^a-zA-Z0-9]/g, '_')}_${props.demand.demand_code}_${new Date().toISOString().slice(0, 10)}.pdf`
    pdf.save(fileName)
    
  } catch (error) {
    throw error
  }
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  }).format(new Date(dateString))
}
</script>

<style scoped>
/* Form styling */
:deep(.p-dropdown) {
  width: 100%;
}

:deep(.p-checkbox) {
  margin-right: 0.5rem;
}

:deep(.p-invalid) {
  border-color: #dc2626;
}

.p-error {
  color: #dc2626;
  font-size: 0.875rem;
  margin-top: 0.25rem;
  display: block;
}

/* Scrollbar styling */
.tw-overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.tw-overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.tw-overflow-y-auto::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.tw-overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>