<template>
  <div class="tw-min-h-screen tw-bg-slate-50">
    <!-- Medical-themed Header -->
    <div class="tw-bg-white tw-border-b tw-border-slate-200 tw-sticky tw-top-0 tw-z-10 tw-shadow-sm">
      <div class="tw-px-6 tw-py-4">
        <div class="tw-flex tw-flex-col md:tw-flex-row tw-justify-between tw-items-start md:tw-items-center tw-gap-4">
          <div>
            <div class="tw-flex tw-items-center tw-gap-3">
              <div class="tw-bg-blue-100 tw-p-2 tw-rounded-lg">
                <i class="pi pi-shopping-cart tw-text-blue-600 tw-text-xl"></i>
              </div>
              <div>
                <h1 class="tw-text-2xl tw-font-semibold tw-text-slate-900">Purchase Orders</h1>
                <p class="tw-text-sm tw-text-slate-500">Manage medical supplies and equipment orders</p>
              </div>
            </div>
          </div>
          
          <!-- Quick Stats -->
          <div class="tw-flex tw-gap-4">
            <div class="tw-bg-amber-50 tw-px-3 tw-py-2 tw-rounded-lg tw-border tw-border-amber-200">
              <div class="tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-clock tw-text-amber-600"></i>
                <div>
                  <div class="tw-text-xs tw-text-amber-600">Pending</div>
                  <div class="tw-text-lg tw-font-semibold tw-text-amber-700">
                    {{ bonCommends.filter(b => b.status === 'draft').length }}
                  </div>
                </div>
              </div>
            </div>
            <div class="tw-bg-green-50 tw-px-3 tw-py-2 tw-rounded-lg tw-border tw-border-green-200">
              <div class="tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-check-circle tw-text-green-600"></i>
                <div>
                  <div class="tw-text-xs tw-text-green-600">Confirmed</div>
                  <div class="tw-text-lg tw-font-semibold tw-text-green-700">
                    {{ bonCommends.filter(b => b.status === 'confirmed').length }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="tw-px-6 tw-py-6">
      <!-- Action Toolbar -->
      <div class="tw-bg-white tw-rounded-xl tw-shadow-sm tw-border tw-border-slate-200 tw-p-4 tw-mb-6">
        <div class="tw-flex tw-flex-col lg:tw-flex-row tw-justify-between tw-items-start lg:tw-items-center tw-gap-4">
          <!-- Filters Section -->
          <div class="tw-flex tw-flex-wrap tw-gap-3 tw-flex-1">
            <div class="tw-relative tw-flex-1 tw-min-w-[200px] tw-max-w-[300px]">
              <i class="pi pi-search tw-absolute tw-left-3 tw-top-1/2 tw--translate-y-1/2 tw-text-slate-400"></i>
              <InputText
                v-model="filters.search"
                placeholder="Search order code..."
                class="tw-w-full tw-pl-10 tw-pr-3 tw-py-2.5 tw-border tw-border-slate-200 tw-rounded-lg focus:tw-border-blue-500 focus:tw-outline-none"
                @input="applyFilters"
              />
            </div>
            
            <Dropdown
              v-model="filters.status"
              :options="statusOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="All Status"
              class="tw-min-w-[150px]"
              @change="applyFilters"
            >
              <template #value="slotProps">
                <div v-if="slotProps.value" class="tw-flex tw-items-center tw-gap-2">
                  <span class="tw-w-2 tw-h-2 tw-rounded-full" 
                        :class="getStatusColor(slotProps.value)"></span>
                  {{ getStatusLabel({ status: slotProps.value }) }}
                </div>
                <span v-else>All Status</span>
              </template>
            </Dropdown>

            <Dropdown
              v-model="filters.fournisseur_id"
              :options="suppliers"
              optionLabel="company_name"
              optionValue="id"
              placeholder="All Suppliers"
              class="tw-min-w-[200px]"
              filter
              filterPlaceholder="Search supplier..."
              @change="applyFilters"
            >
              <template #option="slotProps">
                <div class="tw-py-1">
                  <div class="tw-font-medium">{{ slotProps.option.company_name }}</div>
                  <div class="tw-text-xs tw-text-slate-500">{{ slotProps.option.contact_person }}</div>
                </div>
              </template>
            </Dropdown>
          </div>

          <!-- Action Buttons -->
          <div class="tw-flex tw-flex-wrap tw-gap-2">
            <Button 
              @click="refreshData"
              icon="pi pi-refresh"
              class="p-button-outlined p-button-secondary"
              v-tooltip.top="'Refresh data'"
            />
            <Button 
              @click="openBatchPdfDialog"
              :disabled="!selectedBonCommends.length || generatingPdf"
              icon="pi pi-file-pdf"
              :label="selectedBonCommends.length ? `Generate ${selectedBonCommends.length} PDF(s)` : 'Generate PDFs'"
              class="p-button-outlined p-button-info"
            />
            <Button 
              @click="createBonCommend"
              icon="pi pi-plus"
              label="New Order"
              class="p-button-primary"
            />
          </div>
        </div>
      </div>

      <!-- Main Data Table -->
      <div class="tw-bg-white tw-rounded-xl tw-shadow-sm tw-border tw-border-slate-200 tw-overflow-hidden">
        <DataTable 
          v-model:selection="selectedBonCommends"
          :value="bonCommends"
          :paginator="true"
          :rows="10"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords} orders"
          :rowsPerPageOptions="[10, 25, 50]"
          dataKey="id"
          :loading="loading"
          selectionMode="multiple"
          responsiveLayout="scroll"
          class="p-datatable-sm medical-table"
        >
          <!-- Selection Column -->
          <Column selectionMode="multiple" headerStyle="width: 3rem" />

          <!-- Order Code -->
          <Column field="bonCommendCode" header="Order Code" :sortable="true">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-2">
                <div class="tw-bg-blue-100 tw-text-blue-700 tw-px-2 tw-py-1 tw-rounded tw-font-mono tw-text-sm tw-font-medium">
                  {{ data.bonCommendCode || `BC-${data.id}` }}
                </div>
              </div>
            </template>
          </Column>

          <!-- Supplier -->
          <Column field="fournisseur.company_name" header="Supplier" :sortable="true">
            <template #body="{ data }">
              <div>
                <div class="tw-font-medium tw-text-slate-900">
                  {{ data.fournisseur?.company_name || 'Not specified' }}
                </div>
                <div class="tw-text-xs tw-text-slate-500 tw-mt-0.5">
                  <i class="pi pi-user tw-text-[10px]"></i>
                  {{ data.fournisseur?.contact_person || 'No contact' }}
                </div>
              </div>
            </template>
          </Column>

          <!-- Items Count -->
          <Column header="Items" :sortable="true">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-2">
                <span class="tw-bg-slate-100 tw-text-slate-700 tw-px-2 tw-py-1 tw-rounded-full tw-text-sm tw-font-medium">
                  {{ data.items?.length || 0 }} items
                </span>
              </div>
            </template>
          </Column>

          <!-- Total Amount -->
          <Column header="Total Amount" :sortable="true">
            <template #body="{ data }">
              <div class="tw-text-right">
                <div class="tw-font-semibold tw-text-slate-900">
                  {{ formatCurrency(calculateTotal(data)) }}
                </div>
                <div v-if="requiresApproval(calculateTotal(data))" 
                     class="tw-text-xs tw-text-amber-600 tw-mt-0.5">
                  <i class="pi pi-exclamation-triangle tw-text-[10px]"></i>
                  Requires approval
                </div>
              </div>
            </template>
          </Column>

          <!-- Status -->
          <Column field="status" header="Status" :sortable="true">
            <template #body="{ data }">
              <div class="tw-flex tw-flex-col tw-gap-1">
                <Tag 
                  :value="getStatusLabel(data)" 
                  :severity="getStatusSeverity(data.status)"
                  class="tw-text-xs"
                />
               
              </div>
            </template>
          </Column>

          <!-- Date -->
          <Column field="created_at" header="Created" :sortable="true">
            <template #body="{ data }">
              <div class="tw-text-sm tw-text-slate-600">
                <div class="tw-font-medium">{{ formatDateOnly(data.created_at) }}</div>
                <div class="tw-text-xs tw-text-slate-400">{{ formatTimeOnly(data.created_at) }}</div>
              </div>
            </template>
          </Column>

          <!-- Actions -->
          <Column header="Actions" :exportable="false">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-1">
                <Button
                  @click="editBonCommendItems(data)"
                  icon="pi pi-pencil"
                  class="p-button-rounded p-button-text p-button-sm tw-text-blue-600 hover:tw-bg-blue-50"
                  v-tooltip.top="'Edit Order'"
                />
                
                <Button
                  v-if="requiresApproval(calculateTotal(data)) && data.status === 'draft' && !data.approval_status"
                  @click="requestApproval(data)"
                  icon="pi pi-send"
                  class="p-button-rounded p-button-text p-button-sm tw-text-amber-600 hover:tw-bg-amber-50"
                  v-tooltip.top="'Request Approval'"
                />
                
                <Button
                  @click="createReception(data)"
                  :disabled="data.status !== 'confirmed'"
                  icon="pi pi-box"
                  class="p-button-rounded p-button-text p-button-sm tw-text-green-600 hover:tw-bg-green-50 disabled:tw-opacity-40"
                  v-tooltip.top="'Create Reception'"
                />
                
                <SplitButton
                  icon="pi pi-download"
                  :model="getPdfMenuItems(data)"
                  class="p-button-sm p-button-outlined tw-text-slate-600"
                  v-tooltip.top="'PDF Options'"
                />
                
                <Button
                  @click="deleteBonCommend(data)"
                  :disabled="data.status !== 'draft'"
                  icon="pi pi-trash"
                  class="p-button-rounded p-button-text p-button-sm tw-text-red-600 hover:tw-bg-red-50 disabled:tw-opacity-40"
                  v-tooltip.top="'Delete'"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </div>
    </div>

    <!-- PDF Template Selection Dialog -->
    <Dialog 
      v-model:visible="pdfTemplateDialog"
      :modal="true"
      class="tw-w-full tw-max-w-lg"
    >
      <template #header>
        <div class="tw-flex tw-items-center tw-gap-3">
          <div class="tw-bg-slate-100 tw-p-2 tw-rounded-lg">
            <i class="pi pi-file-pdf tw-text-slate-600 tw-text-lg"></i>
          </div>
          <div>
            <h3 class="tw-text-lg tw-font-semibold tw-text-slate-900">Generate PDF Documents</h3>
            <p class="tw-text-sm tw-text-slate-500">
              {{ pdfMode === 'single' ? 'Select template for order' : `Generate ${selectedBonCommends.length} PDFs` }}
            </p>
          </div>
        </div>
      </template>

      <div class="tw-space-y-4">
        <!-- Template Selection Cards -->
        <div class="tw-space-y-3">
          <!-- Standard Template -->
          <div 
            @click="selectedTemplate = 'default'"
            class="tw-border tw-rounded-lg tw-p-4 tw-cursor-pointer tw-transition-all"
            :class="selectedTemplate === 'default' 
              ? 'tw-border-blue-500 tw-bg-blue-50 tw-ring-2 tw-ring-blue-200' 
              : 'tw-border-slate-200 hover:tw-border-slate-300 hover:tw-bg-slate-50'"
          >
            <div class="tw-flex tw-items-start tw-gap-3">
              <div class="tw-mt-1">
                <RadioButton 
                  v-model="selectedTemplate" 
                  inputId="default-template" 
                  value="default" 
                />
              </div>
              <div class="tw-flex-1">
                <label for="default-template" class="tw-cursor-pointer">
                  <div class="tw-flex tw-items-center tw-gap-2 tw-mb-1">
                    <div class="tw-p-1.5 tw-bg-blue-100 tw-rounded">
                      <i class="pi pi-file tw-text-blue-600"></i>
                    </div>
                    <span class="tw-font-semibold tw-text-slate-900">Standard Template</span>
                    <Tag value="Default" severity="info" class="tw-text-xs" />
                  </div>
                  <p class="tw-text-sm tw-text-slate-600">
                    Hospital's standard purchase order format with complete details and official branding
                  </p>
                  <div class="tw-mt-2 tw-flex tw-gap-2">
                    <span class="tw-text-xs tw-bg-white tw-px-2 tw-py-1 tw-rounded tw-border tw-border-slate-200">
                      <i class="pi pi-check tw-text-green-500 tw-text-xs"></i> Header/Footer
                    </span>
                    <span class="tw-text-xs tw-bg-white tw-px-2 tw-py-1 tw-rounded tw-border tw-border-slate-200">
                      <i class="pi pi-check tw-text-green-500 tw-text-xs"></i> Logo
                    </span>
                    <span class="tw-text-xs tw-bg-white tw-px-2 tw-py-1 tw-rounded tw-border tw-border-slate-200">
                      <i class="pi pi-check tw-text-green-500 tw-text-xs"></i> Signatures
                    </span>
                  </div>
                </label>
              </div>
            </div>
          </div>

          <!-- PCH Template -->
          <div 
            @click="selectedTemplate = 'pch'"
            class="tw-border tw-rounded-lg tw-p-4 tw-cursor-pointer tw-transition-all"
            :class="selectedTemplate === 'pch' 
              ? 'tw-border-green-500 tw-bg-green-50 tw-ring-2 tw-ring-green-200' 
              : 'tw-border-slate-200 hover:tw-border-slate-300 hover:tw-bg-slate-50'"
          >
            <div class="tw-flex tw-items-start tw-gap-3">
              <div class="tw-mt-1">
                <RadioButton 
                  v-model="selectedTemplate" 
                  inputId="pch-template" 
                  value="pch" 
                />
              </div>
              <div class="tw-flex-1">
                <label for="pch-template" class="tw-cursor-pointer">
                  <div class="tw-flex tw-items-center tw-gap-2 tw-mb-1">
                    <div class="tw-p-1.5 tw-bg-green-100 tw-rounded">
                      <i class="pi pi-building tw-text-green-600"></i>
                    </div>
                    <span class="tw-font-semibold tw-text-slate-900">PCH Template</span>
                    <Tag value="Pharmacy" severity="success" class="tw-text-xs" />
                  </div>
                  <p class="tw-text-sm tw-text-slate-600">
                    Central Pharmacy (PCH) specific format for pharmaceutical and medical supply orders
                  </p>
                  <div class="tw-mt-2 tw-flex tw-gap-2">
                    <span class="tw-text-xs tw-bg-white tw-px-2 tw-py-1 tw-rounded tw-border tw-border-slate-200">
                      <i class="pi pi-check tw-text-green-500 tw-text-xs"></i> PCH Header
                    </span>
                    <span class="tw-text-xs tw-bg-white tw-px-2 tw-py-1 tw-rounded tw-border tw-border-slate-200">
                      <i class="pi pi-check tw-text-green-500 tw-text-xs"></i> Drug Codes
                    </span>
                    <span class="tw-text-xs tw-bg-white tw-px-2 tw-py-1 tw-rounded tw-border tw-border-slate-200">
                      <i class="pi pi-check tw-text-green-500 tw-text-xs"></i> Batch Info
                    </span>
                  </div>
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- Order Details (for single PDF) -->
        <div v-if="selectedPdfBonCommend && pdfMode === 'single'" 
             class="tw-bg-slate-50 tw-rounded-lg tw-p-4 tw-border tw-border-slate-200">
          <h4 class="tw-text-sm tw-font-medium tw-text-slate-700 tw-mb-3">Order Details</h4>
          <div class="tw-grid tw-grid-cols-2 tw-gap-3 tw-text-sm">
            <div>
              <span class="tw-text-slate-500">Order Code:</span>
              <span class="tw-font-mono tw-font-medium tw-text-slate-900 tw-ml-2">
                {{ selectedPdfBonCommend.bonCommendCode }}
              </span>
            </div>
            <div>
              <span class="tw-text-slate-500">Supplier:</span>
              <span class="tw-font-medium tw-text-slate-900 tw-ml-2">
                {{ selectedPdfBonCommend.fournisseur?.company_name || 'N/A' }}
              </span>
            </div>
            <div>
              <span class="tw-text-slate-500">Items:</span>
              <span class="tw-font-medium tw-text-slate-900 tw-ml-2">
                {{ selectedPdfBonCommend.items?.length || 0 }}
              </span>
            </div>
            <div>
              <span class="tw-text-slate-500">Total:</span>
              <span class="tw-font-medium tw-text-blue-600 tw-ml-2">
                {{ formatCurrency(calculateTotal(selectedPdfBonCommend)) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Batch Summary (for multiple PDFs) -->
        <div v-if="pdfMode === 'batch'" 
             class="tw-bg-blue-50 tw-rounded-lg tw-p-4 tw-border tw-border-blue-200">
          <div class="tw-flex tw-items-start tw-gap-2">
            <i class="pi pi-info-circle tw-text-blue-600 tw-mt-0.5"></i>
            <div class="tw-text-sm">
              <p class="tw-font-medium tw-text-blue-900 tw-mb-1">
                Generating {{ selectedBonCommends.length }} PDF documents
              </p>
              <p class="tw-text-blue-700">
                All selected orders will be generated using the {{ selectedTemplate === 'pch' ? 'PCH' : 'Standard' }} template.
              </p>
            </div>
          </div>
        </div>

        <!-- PDF Options -->
        <div class="tw-space-y-3 tw-pt-3 tw-border-t tw-border-slate-200">
          <div class="tw-flex tw-items-center tw-justify-between">
            <label for="open-pdf" class="tw-text-sm tw-font-medium tw-text-slate-700 tw-cursor-pointer">
              PDF Action
            </label>
            <Dropdown 
              v-model="pdfAction" 
              :options="pdfActionOptions"
              optionLabel="label"
              optionValue="value"
              class="tw-w-48"
            />
          </div>
          
          <div v-if="pdfAction === 'save'" class="tw-flex tw-items-center tw-justify-between">
            <label class="tw-text-sm tw-font-medium tw-text-slate-700">
              Save Location
            </label>
            <InputText 
              v-model="savePath" 
              placeholder="/downloads/orders/"
              class="tw-w-48"
              disabled
            />
          </div>
        </div>
      </div>

      <template #footer>
        <div class="tw-flex tw-gap-2 tw-justify-end">
          <Button 
            @click="pdfTemplateDialog = false" 
            label="Cancel" 
            icon="pi pi-times" 
            class="p-button-text p-button-secondary"
          />
          <Button 
            @click="previewPdf" 
            label="Preview" 
            icon="pi pi-eye" 
            class="p-button-outlined p-button-info"
            v-if="pdfMode === 'single'"
          />
          <Button 
            @click="generatePdf" 
            :label="pdfMode === 'batch' ? `Generate ${selectedBonCommends.length} PDFs` : 'Generate PDF'" 
            icon="pi pi-download" 
            :loading="generatingPdf"
            class="p-button-primary"
          />
        </div>
      </template>
    </Dialog>

    <!-- Approval Dialog -->
    <Dialog 
      v-model:visible="approvalDialog"
      :modal="true"
      class="tw-w-full tw-max-w-lg"
    >
      <!-- [Previous approval dialog content remains the same] -->
    </Dialog>

    <!-- ConfirmDialog component -->
    <ConfirmDialog />
    
    <!-- Toast component -->
    <Toast />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import SplitButton from 'primevue/splitbutton'
import Dialog from 'primevue/dialog'
import Dropdown from 'primevue/dropdown'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Tag from 'primevue/tag'
import Message from 'primevue/message'
import Textarea from 'primevue/textarea'
import ConfirmDialog from 'primevue/confirmdialog'
import RadioButton from 'primevue/radiobutton'
import Checkbox from 'primevue/checkbox'
import Toast from 'primevue/toast'
import axios from 'axios'

// Composables
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()

// State Management
const loading = ref(false)
const generatingPdf = ref(false)
const bonCommends = ref([])
const suppliers = ref([])
const selectedBonCommends = ref([])
const selectedBonCommend = ref(null)

// PDF State
const pdfTemplateDialog = ref(false)
const selectedPdfBonCommend = ref(null)
const selectedTemplate = ref('default')
const pdfMode = ref('single') // 'single' or 'batch'
const pdfAction = ref('download') // 'download', 'preview', 'save'
const savePath = ref('/downloads/orders/')

// Approval State
const approvalDialog = ref(false)
const approvalNotes = ref('')
const requestingApproval = ref(false)
const approvalThreshold = ref(10000)

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
  { label: 'Confirmed', value: 'confirmed' },
  { label: 'Cancelled', value: 'cancelled' }
]

const pdfActionOptions = [
  { label: 'Download', value: 'download', icon: 'pi pi-download' },
  { label: 'Open in new tab', value: 'preview', icon: 'pi pi-external-link' },
  { label: 'Save to server', value: 'save', icon: 'pi pi-server' }
]

// Get PDF menu items for split button
const getPdfMenuItems = (bonCommend) => {
  return [
    {
      label: 'Standard PDF',
      icon: 'pi pi-file',
      command: () => {
        selectedPdfBonCommend.value = bonCommend
        selectedTemplate.value = 'default'
        pdfMode.value = 'single'
        generatePdf()
      }
    },
    {
      label: 'PCH PDF',
      icon: 'pi pi-building',
      command: () => {
        selectedPdfBonCommend.value = bonCommend
        selectedTemplate.value = 'pch'
        pdfMode.value = 'single'
        generatePdf()
      }
    },
    {
      separator: true
    },
    {
      label: 'Choose Template...',
      icon: 'pi pi-cog',
      command: () => {
        selectedPdfBonCommend.value = bonCommend
        pdfMode.value = 'single'
        pdfTemplateDialog.value = true
      }
    }
  ]
}

// API Methods
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
    }
  } catch (err) {
    console.error('Error fetching bon commends:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load purchase orders',
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

// Navigate to edit page
const editBonCommendItems = (bonCommend) => {
  router.push({ 
    name: 'purchasing.bon-commend.edit', 
    params: { id: bonCommend.id } 
  })
}

// Open batch PDF dialog
const openBatchPdfDialog = () => {
  if (!selectedBonCommends.value.length) return
  
  pdfMode.value = 'batch'
  selectedTemplate.value = 'default'
  pdfTemplateDialog.value = true
}

// Generate PDF with selected template
const generatePdf = async () => {
  generatingPdf.value = true
  
  try {
    if (pdfMode.value === 'batch') {
      // Generate multiple PDFs
      let successCount = 0
      let errorCount = 0
      
      for (const bonCommend of selectedBonCommends.value) {
        try {
          await downloadSinglePdf(bonCommend, selectedTemplate.value)
          successCount++
        } catch (err) {
          errorCount++
          console.error(`Error generating PDF for order ${bonCommend.bonCommendCode}:`, err)
        }
      }
      
      if (successCount > 0) {
        toast.add({
          severity: 'success',
          summary: 'Success',
          detail: `Generated ${successCount} PDF(s) successfully${errorCount > 0 ? ` (${errorCount} failed)` : ''}`,
          life: 3000
        })
      }
      
      selectedBonCommends.value = []
    } else {
      // Generate single PDF
      await downloadSinglePdf(selectedPdfBonCommend.value, selectedTemplate.value)
      
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: `PDF generated with ${selectedTemplate.value === 'pch' ? 'PCH' : 'standard'} template`,
        life: 3000
      })
    }
    
    pdfTemplateDialog.value = false
  } catch (err) {
    console.error('Error generating PDF:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to generate PDF',
      life: 3000
    })
  } finally {
    generatingPdf.value = false
  }
}

// Download single PDF
const downloadSinglePdf = async (bonCommend, template) => {
  const response = await axios.get(
    `/api/bon-commends/${bonCommend.id}/pdf?template=${template}`,
    {
      responseType: 'blob'
    }
  )
  
  const blob = new Blob([response.data], { type: 'application/pdf' })
  const url = window.URL.createObjectURL(blob)
  
  if (pdfAction.value === 'preview') {
    // Open in new tab
    window.open(url, '_blank')
  } else if (pdfAction.value === 'save') {
    // Save to server (call different endpoint)
    await savePdfToServer(bonCommend, template)
  } else {
    // Download
    const link = document.createElement('a')
    link.href = url
    link.download = `order-${bonCommend.bonCommendCode}-${template}.pdf`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  }
  
  setTimeout(() => {
    window.URL.revokeObjectURL(url)
  }, 100)
}

// Save PDF to server
const savePdfToServer = async (bonCommend, template) => {
  const response = await axios.post(
    `/api/bon-commends/${bonCommend.id}/pdf/save?template=${template}`
  )
  
  if (response.data.status === 'success') {
    toast.add({
      severity: 'info',
      summary: 'Saved',
      detail: `PDF saved to: ${response.data.path}`,
      life: 4000
    })
  }
}

// Preview PDF
const previewPdf = async () => {
  if (!selectedPdfBonCommend.value) return
  
  try {
    generatingPdf.value = true
    
    const response = await axios.get(
      `/api/bon-commends/${selectedPdfBonCommend.value.id}/pdf/preview?template=${selectedTemplate.value}`,
      {
        responseType: 'blob'
      }
    )
    
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    window.open(url, '_blank')
    
    setTimeout(() => {
      window.URL.revokeObjectURL(url)
    }, 100)
  } catch (err) {
    console.error('Error previewing PDF:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to preview PDF',
      life: 3000
    })
  } finally {
    generatingPdf.value = false
  }
}

const createReception = (bonCommend) => {
  router.push({
    name: 'purchasing.bon-receptions.create',
    query: { bon_commend_id: bonCommend.id }
  })
}

const deleteBonCommend = (bonCommend) => {
  confirm.require({
    message: `Delete order ${bonCommend.bonCommendCode}? This action cannot be undone.`,
    header: 'Confirm Deletion',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        const response = await axios.delete(`/api/bon-commends/${bonCommend.id}`)
        
        if (response.data.status === 'success') {
          toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Order deleted successfully',
            life: 3000
          })
          await fetchBonCommends()
        }
      } catch (err) {
        console.error('Error deleting bon commend:', err)
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to delete order',
          life: 3000
        })
      }
    }
  })
}

const createBonCommend = () => {
  router.push({ name: 'purchasing.bon-commend.create' })
}

// Approval Methods
const requiresApproval = (totalAmount) => {
  return totalAmount > approvalThreshold.value
}

const requestApproval = (bonCommend) => {
  selectedBonCommend.value = bonCommend
  approvalNotes.value = ''
  approvalDialog.value = true
}

// Utility Functions
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

const formatDateOnly = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatTimeOnly = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getStatusLabel = (data) => {
  const status = data.status || 'draft'
  return status.charAt(0).toUpperCase() + status.slice(1)
}

const getStatusSeverity = (status) => {
  switch (status) {
    case 'draft': return 'warning'
    case 'sent': return 'info'
    case 'confirmed': return 'success'
    case 'cancelled': return 'danger'
    default: return 'secondary'
  }
}

const getStatusColor = (status) => {
  switch (status) {
    case 'draft': return 'tw-bg-amber-400'
    case 'sent': return 'tw-bg-blue-400'
    case 'confirmed': return 'tw-bg-green-400'
    case 'cancelled': return 'tw-bg-red-400'
    default: return 'tw-bg-slate-400'
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
/* Previous styles remain the same */
:deep(.medical-table .p-datatable-header) {
  @apply bg-slate-50 tw-border-b tw-border-slate-200;
}

:deep(.medical-table .p-datatable-thead > tr > th) {
  @apply bg-slate-50 tw-text-slate-700 tw-font-medium tw-text-sm tw-border-b tw-border-slate-200;
}

:deep(.medical-table .p-datatable-tbody > tr) {
  @apply border-b tw-border-slate-100 hover:tw-bg-blue-50/30 tw-transition-colors;
}

:deep(.medical-table .p-datatable-tbody > tr.p-highlight) {
  @apply bg-blue-50;
}

:deep(.p-button-sm) {
  @apply text-sm tw-py-1.5 tw-px-2.5;
}

:deep(.p-tag) {
  @apply font-medium;
}

:deep(.p-radiobutton .p-radiobutton-box) {
  @apply border-slate-300;
}

:deep(.p-radiobutton.p-radiobutton-checked .p-radiobutton-box) {
  @apply border-blue-500 tw-bg-blue-500;
}

:deep(.p-splitbutton .p-splitbutton-defaultbutton) {
  @apply rounded-r-none;
}

:deep(.p-splitbutton .p-splitbutton-menubutton) {
  @apply rounded-l-none tw-px-2;
}
</style>
