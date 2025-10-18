<template>
  <div class="tw-p-6 tw-bg-gray-50 tw-min-h-screen">
    <!-- Header -->
    <div class="tw-flex tw-justify-between tw-items-center tw-mb-6">
      <div>
        <h1 class="tw-text-3xl tw-font-bold tw-text-gray-900">Proforma Invoices</h1>
        <p class="tw-text-gray-600 tw-mt-1">Managing proforma invoices for purchase requests</p>
      </div>
      <div class="tw-flex tw-gap-3">
        <Button 
          @click="switchToBonCommend"
          :disabled="buttonClicked"
          icon="pi pi-arrow-right"
          :label="buttonClicked ? 'Already Used' : 'Switch to Bon Commande'"
          outlined
        />
        <Button 
          @click="createFactureProforma"
          icon="pi pi-plus"
          label="New Proforma"
          class="tw-bg-blue-600 hover:tw-bg-blue-700"
        />
        <Button 
          @click="generateAllPdfs"
          :disabled="!selectedProformas.length || generatingPdf"
          :loading="generatingPdf"
          icon="pi pi-file-pdf"
          label="Export PDFs"
          class="tw-bg-green-600 hover:tw-bg-green-700"
          :badge="selectedProformas.length ? String(selectedProformas.length) : null"
        />
        <Button 
          @click="refreshData"
          icon="pi pi-refresh"
          label="Refresh"
          outlined
        />
      </div>
    </div>

    <!-- Filters -->
    <div class="tw-bg-white tw-rounded-lg tw-shadow-sm tw-p-4 tw-mb-6">
      <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-4">
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Status</label>
          <Dropdown
            v-model="filters.status"
            :options="statusOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="All Statuses"
            showClear
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
            showClear
            filter
            class="tw-w-full"
          />
        </div>
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Search</label>
          <InputText
            v-model="filters.search"
            placeholder="Search by code..."
            @keyup.enter="applyFilters"
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
    <div class="tw-bg-white tw-rounded-lg tw-shadow-sm">
      <DataTable 
        v-model="selectedProformas"
        :value="proformas"
        :loading="loading"
        dataKey="id"
        :paginator="true"
        :rows="10"
        :rowsPerPageOptions="[10, 20, 50]"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        currentPageReportTemplate="Showing {first} to {last} of {totalRecords}"
        responsiveLayout="scroll"
        selectionMode="multiple"
        class=""
        :rowClass="rowClass"
      >
        <!-- Loading Template -->
        <template #loading>
          <div class="tw-flex tw-flex-col tw-items-center tw-justify-center tw-py-16">
            <div class="tw-relative">
              <div class="tw-w-16 tw-h-16 tw-border-4 tw-border-indigo-200 tw-rounded-full"></div>
              <div class="tw-w-16 tw-h-16 tw-border-4 tw-border-indigo-600 tw-border-t-transparent tw-rounded-full tw-animate-spin tw-absolute tw-top-0"></div>
            </div>
            <p class="tw-mt-4 tw-text-indigo-600 tw-font-medium">Loading proforma invoices...</p>
          </div>
        </template>

        <Column selectionMode="multiple" headerStyle="width: 3rem" frozen>
          <template #header>
            <div class="tw-flex tw-items-center tw-justify-center">
              <div class="tw-w-5 tw-h-5 tw-rounded tw-border-2 tw-border-indigo-500"></div>
            </div>
          </template>
        </Column>

        <Column field="factureProformaCode" header="Code" :sortable="true" frozen>
          <template #body="{ data }">
            <div class="tw-flex tw-items-center tw-gap-2">
              <div class="tw-w-1 tw-h-8 tw-bg-gradient-to-b tw-from-indigo-500 tw-to-purple-500 tw-rounded-full"></div>
              <span class="tw-font-bold tw-text-indigo-700 tw-font-mono">
                {{ data.factureProformaCode || `FP-${data.id}` }}
              </span>
            </div>
          </template>
        </Column>

        <Column field="fournisseur.company_name" header="Supplier" :sortable="true">
          <template #body="{ data }">
            <div class="tw-flex tw-items-center tw-gap-3">
              <Avatar 
                :label="data.fournisseur?.company_name?.charAt(0)" 
                class="tw-bg-gradient-to-br tw-from-purple-500 tw-to-indigo-500 tw-text-white"
                size="small"
              />
              <div>
                <div class="tw-font-medium tw-text-gray-900">
                  {{ data.fournisseur?.company_name || 'N/A' }}
                </div>
                <div class="tw-text-xs tw-text-purple-600" v-if="data.fournisseur?.contact_person">
                  <i class="pi pi-user tw-mr-1"></i>
                  {{ data.fournisseur.contact_person }}
                </div>
              </div>
            </div>
          </template>
        </Column>

        <Column header="Products">
          <template #body="{ data }">
            <div v-if="data.products?.length" class="tw-flex tw-items-center tw-gap-2">
              <Tag 
                :value="`${data.products.length} items`" 
                class="tw-bg-gradient-to-r tw-from-indigo-500 tw-to-purple-500 tw-text-white"
              />
              <span class="tw-text-sm tw-font-medium tw-text-indigo-600">
                Qty: {{ data.products.reduce((sum, p) => sum + (p.quantity || 0), 0) }}
              </span>
            </div>
            <span v-else class="tw-text-sm tw-text-gray-400 tw-italic">No products</span>
          </template>
        </Column>

        <Column field="created_at" header="Created" :sortable="true">
          <template #body="{ data }">
            <div class="tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-calendar tw-text-indigo-400 tw-text-sm"></i>
              <span class="tw-text-sm tw-text-gray-700 tw-font-medium">{{ formatDate(data.created_at) }}</span>
            </div>
          </template>
        </Column>

        <!-- Actions Column with Colored Icons -->
        <Column header="Actions" frozen alignFrozen="right">
          <template #body="{ data }">
            <div class="tw-flex tw-gap-1">
              <Button
                @click="editProformaItems(data)"
                icon="pi pi-pencil"
                size="small"
                class="p-button-text tw-text-indigo-600 hover:tw-bg-indigo-50"
                v-tooltip.top="'Edit'"
              />
              <Button
                @click="cancelProforma(data)"
                :disabled="data.status === 'cancelled' || data.status === 'factureprofram'"
                icon="pi pi-times"
                size="small"
                class="p-button-text tw-text-orange-600 hover:tw-bg-orange-50"
                v-tooltip.top="'Cancel'"
              />
              <Button
                @click="showConfirmationDialog(data)"
                :disabled="data.status === 'cancelled' || data.status === 'factureprofram'"
                icon="pi pi-ban"
                size="small"
                class="p-button-text tw-text-red-600 hover:tw-bg-red-50"
                v-tooltip.top="'Cancel Workflow'"
              />
              <Button
                @click="generatePdf(data)"
                :loading="generatingPdf"
                icon="pi pi-file-pdf"
                size="small"
                class="p-button-text tw-text-purple-600 hover:tw-bg-purple-50"
                v-tooltip.top="'Export PDF'"
              />
              <Button
                @click="deleteProforma(data)"
                :disabled="data.status !== 'draft'"
                icon="pi pi-trash"
                size="small"
                class="p-button-text tw-text-red-600 hover:tw-bg-red-50"
                v-tooltip.top="'Delete'"
              />
            </div>
          </template>
        </Column>

        <template #empty>
          <div class="tw-text-center tw-py-8">
            <i class="pi pi-inbox tw-text-gray-400 tw-text-4xl tw-mb-4"></i>
            <p class="tw-text-gray-500">No proforma invoices found</p>
          </div>
        </template>
      </DataTable>
    </div>

    <!-- Cancellation Dialog -->
    <Dialog 
      v-model="confirmationDialog"
      modal 
      header="Cancel Proforma Workflow"
      :style="{ width: '30rem' }"
    >
      <div class="tw-space-y-4">
        <div class="tw-text-center">
          <i class="pi pi-question-circle tw-text-6xl tw-text-orange-500 tw-mb-4"></i>
          <h3 class="tw-text-xl tw-font-semibold tw-text-gray-800 tw-mb-2">
            Cancel Proforma Workflow
          </h3>
          <p class="tw-text-gray-600">
            Are you sure you want to cancel this proforma and mark the workflow as cancelled?
            This action cannot be undone.
          </p>
        </div>

        <div v-if="selectedProforma" class="tw-bg-gray-50 tw-p-4 tw-rounded-lg">
          <div class="tw-text-sm tw-text-gray-700 tw-space-y-2">
            <div><strong>Code:</strong> {{ selectedProforma.factureProformaCode || `FP-${selectedProforma.id}` }}</div>
            <div><strong>Supplier:</strong> {{ selectedProforma.fournisseur?.company_name }}</div>
            <div><strong>Status:</strong> Will be marked as "Cancelled"</div>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="tw-flex tw-gap-2 tw-justify-end">
          <Button 
            @click="confirmationDialog = false"
            label="Cancel"
            outlined
          />
          <Button 
            @click="cancelProformaWorkflow"
            :loading="confirming"
            label="Cancel Proforma"
            severity="danger"
          />
        </div>
      </template>
    </Dialog>

    <ConfirmDialog />
  </div>
</template>

<script setup>
// ALL IMPORTS AND SCRIPT CONTENT REMAIN EXACTLY THE SAME
import { ref, reactive, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useRouter } from 'vue-router'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Dropdown from 'primevue/dropdown'
import InputText from 'primevue/inputtext'
import ConfirmDialog from 'primevue/confirmdialog'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'
import Avatar from 'primevue/avatar'
import axios from 'axios'

const toast = useToast()
const confirm = useConfirm()
const router = useRouter()

const loading = ref(true)
const generatingPdf = ref(false)
const proformas = ref([])
const suppliers = ref([])
const selectedProformas = ref([])
const selectedProforma = ref(null)
const confirmationDialog = ref(false)
const confirming = ref(false)
const totalRecords = ref(0)
const buttonClicked = ref(localStorage.getItem('facture_proforma_used') === 'true')

const filters = reactive({
  status: null,
  fournisseur_id: null,
  search: ''
})

const statusOptions = [
  { label: 'Draft', value: 'draft' },
  { label: 'Sent', value: 'sent' },
  { label: 'Approved', value: 'approved' },
  { label: 'Rejected', value: 'rejected' },
  { label: 'Completed', value: 'completed' }
]

// ALL METHODS REMAIN EXACTLY THE SAME
const normalizePaginator = (payload) => {
  if (!payload) return { data: [], total: 0 }
  if (payload.status && payload.data) payload = payload.data
  if (Array.isArray(payload.data)) {
    const items = payload.data
    const total = payload.total || payload.totalRecords || (payload.meta?.total) || items.length
    return { data: items, total }
  }
  if (Array.isArray(payload)) return { data: payload, total: payload.length }
  if (payload.data?.data && Array.isArray(payload.data.data)) {
    const items = payload.data.data
    const total = payload.data.total || payload.total || items.length
    return { data: items, total }
  }
  const items = payload.data || payload.items || []
  const total = payload.total || items.length
  return { data: Array.isArray(items) ? items : [], total }
}

const fetchProformas = async () => {
  try {
    loading.value = true
    const params = new URLSearchParams()
    if (filters.status) params.append('status', filters.status)
    if (filters.fournisseur_id) params.append('fournisseur_id', filters.fournisseur_id)
    if (filters.search) params.append('search', filters.search)

    const response = await axios.get(`/api/facture-proformas?${params.toString()}`)
    const normalized = normalizePaginator(response.data)
    proformas.value = normalized.data
    totalRecords.value = normalized.total
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
    suppliers.value = response.data.status === 'success' 
      ? response.data.data 
      : (Array.isArray(response.data) ? response.data : [])
  } catch (err) {
    console.error('Error fetching suppliers:', err)
  }
}

const applyFilters = () => fetchProformas()

const refreshData = async () => {
  await Promise.all([fetchProformas(), fetchSuppliers()])
  toast.add({
    severity: 'success',
    summary: 'Refreshed',
    detail: 'Data refreshed successfully',
    life: 2000
  })
}

const editProformaItems = (proforma) => {
  router.push({ 
    name: 'purchasing.facture-proforma.edit', 
    params: { id: proforma.id } 
  })
}

const showConfirmationDialog = (proforma) => {
  selectedProforma.value = proforma
  confirmationDialog.value = true
}

const cancelProformaWorkflow = async () => {
  try {
    confirming.value = true
    const response = await axios.post(
      `/api/facture-proformas/${selectedProforma.value.id}/cancel`
    )

    if (response.data.success || response.data.status === 'success') {
      toast.add({ 
        severity: 'success', 
        summary: 'Cancelled', 
        detail: 'Proforma workflow cancelled successfully', 
        life: 3000 
      })
      await fetchProformas()
      confirmationDialog.value = false
      selectedProforma.value = null
    }
  } catch (error) {
    console.error('Error cancelling proforma workflow:', error)
    toast.add({ 
      severity: 'error', 
      summary: 'Error', 
      detail: error.response?.data?.message || 'Failed to cancel proforma workflow', 
      life: 3000 
    })
  } finally {
    confirming.value = false
  }
}

const generatePdf = async (proforma) => {
  try {
    generatingPdf.value = true
    const response = await axios.get(`/api/facture-proformas/${proforma.id}/download`, {
      responseType: 'blob'
    })

    const blob = new Blob([response.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `facture-proforma-${proforma.factureProformaCode || `FP-${proforma.id}`}.pdf`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'PDF downloaded successfully',
      life: 3000
    })
  } catch (err) {
    console.error('Error generating PDF:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to download PDF',
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
      detail: `Generated ${selectedProformas.value.length} PDF(s)`,
      life: 3000
    })
  } catch (err) {
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
    message: `Delete ${proforma.factureProformaCode}?`,
    header: 'Confirm Deletion',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        const response = await axios.delete(`/api/facture-proformas/${proforma.id}`)
        if (response.data.status === 'success') {
          toast.add({
            severity: 'success',
            summary: 'Deleted',
            detail: 'Proforma deleted successfully',
            life: 3000
          })
          await fetchProformas()
        }
      } catch (err) {
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

const cancelProforma = (proforma) => {
  confirm.require({
    message: `Cancel ${proforma.factureProformaCode || `FP-${proforma.id}`}? This will mark it as cancelled.`,
    header: 'Confirm Cancellation',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        const response = await axios.post(`/api/facture-proformas/${proforma.id}/cancel`)
        if (response.data.status === 'success' || response.data.success) {
          toast.add({ severity: 'success', summary: 'Cancelled', detail: 'Proforma cancelled successfully', life: 3000 })
          await fetchProformas()
        }
      } catch (err) {
        console.error('Error cancelling proforma:', err)
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to cancel proforma', life: 3000 })
      }
    }
  })
}

const switchToBonCommend = async () => {
  try {
    const serviceDemandId = localStorage.getItem('current_service_demand_id')
    if (serviceDemandId) {
      const response = await axios.post(`/api/service-demands/${serviceDemandId}/update-to-bon-commend`)
      if (response.data.success) {
        localStorage.setItem('serviceDemandStatus_' + serviceDemandId, 'boncommend')
        toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Updated to Bon Commend status',
          life: 3000
        })
      }
    }
    localStorage.setItem('facture_proforma_used', 'true')
    buttonClicked.value = true
    window.location.href = '/apps/purchasing/bon-commends'
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to update status',
      life: 3000
    })
  }
}

const createFactureProforma = () => {
  router.push({ name: 'purchasing.facture-proforma.create' })
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric' 
  })
}

// Row class for styling
const rowClass = (data) => {
  if (data.status === 'cancelled') return 'tw-bg-red-50/30'
  if (data.status === 'approved') return 'tw-bg-green-50/30'
  return ''
}

onMounted(() => {
  Promise.all([fetchProformas(), fetchSuppliers()])
})
</script>

<style scoped>
:deep(.p-datatable) {
  border: 0;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
  background-color: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

:deep(.p-datatable .p-datatable-tbody > tr) {
  border-bottom: 1px solid #f3f4f6;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background-color: #f9fafb;
}
</style>