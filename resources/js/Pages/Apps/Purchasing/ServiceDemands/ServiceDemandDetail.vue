<template>
  <div class="service-demand-detail">
    <!-- Loading State -->
    <div v-if="loading" class="tw-flex tw-justify-center tw-items-center tw-py-12">
      <ProgressSpinner />
    </div>

    <!-- Main Content -->
    <div v-else-if="serviceDemand">
      <!-- Header Section -->
      <div class="tw-bg-white tw-p-6 tw-rounded-lg tw-shadow-md tw-mb-6">
        <div class="tw-flex tw-justify-between tw-items-start tw-mb-4">
          <div>
            <h1 class="tw-text-2xl tw-font-bold tw-text-gray-800">{{ serviceDemand.demand_code }}</h1>
            <p class="tw-text-gray-600 tw-mt-1">Service Demand Details</p>
          </div>
          <div class="tw-flex tw-gap-3">
            <Button 
              @click="saveProforma"
              :disabled="!hasAssignments || savingData"
              icon="pi pi-save"
              label="Save Proforma"
              class="p-button-primary"
            />
            <Button 
              @click="saveBonCommend"
              :disabled="!hasAssignments || savingData"
              icon="pi pi-save"
              label="Save Boncommend"
              class="p-button-warning"
            />
            <Button 
              @click="refreshData"
              icon="pi pi-refresh"
              label="Refresh"
              class="p-button-secondary"
            />
          </div>
        </div>

        <!-- Service Demand Info Cards -->
        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-4">
          <div class="tw-bg-blue-50 tw-p-4 tw-rounded-lg tw-border-l-4 tw-border-blue-500">
            <div class="tw-text-sm tw-text-gray-600">Service</div>
            <div class="tw-font-semibold tw-text-blue-900">
              {{ serviceDemand.service?.name || 'No Service Assigned' }}
            </div>
          </div>
          <div class="tw-bg-green-50 tw-p-4 tw-rounded-lg tw-border-l-4 tw-border-green-500">
            <div class="tw-text-sm tw-text-gray-600">Status</div>
            <Tag 
              :value="serviceDemand.status" 
              :severity="getStatusSeverity(serviceDemand.status)"
              class="tw-mt-1"
            />
          </div>
          <div class="tw-bg-purple-50 tw-p-4 tw-rounded-lg tw-border-l-4 tw-border-purple-500">
            <div class="tw-text-sm tw-text-gray-600">Expected Date</div>
            <div class="tw-font-semibold tw-text-purple-900">
              {{ formatDate(serviceDemand.expected_date) }}
            </div>
          </div>
          <div class="tw-bg-orange-50 tw-p-4 tw-rounded-lg tw-border-l-4 tw-border-orange-500">
            <div class="tw-text-sm tw-text-gray-600">Total Items</div>
            <div class="tw-font-semibold tw-text-orange-900">
              {{ serviceDemand.items?.length || 0 }} Products
            </div>
          </div>
        </div>

        <!-- Notes Section -->
        <div v-if="serviceDemand.notes" class="tw-mt-4 tw-p-4 tw-bg-gray-50 tw-rounded-lg">
          <div class="tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Notes:</div>
          <div class="tw-text-gray-600">{{ serviceDemand.notes }}</div>
        </div>
      </div>

      <!-- Products Table -->
      <div class="tw-bg-white tw-rounded-lg tw-shadow-md tw-overflow-hidden">
        <div class="tw-p-6 tw-border-b tw-border-gray-200">
          <h2 class="tw-text-xl tw-font-semibold tw-text-gray-800">Products & Supplier Assignments</h2>
          <p class="tw-text-gray-600 tw-mt-1">Manage supplier assignments for each product</p>
        </div>

        <DataTable 
          :value="serviceDemand.items"
          class="p-datatable-gridlines"
          responsiveLayout="scroll"
          :paginator="true"
          :rows="10"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords} products"
          :rowsPerPageOptions="[5, 10, 20, 50]"
          dataKey="id"
        >
          <!-- Product Information -->
          <Column field="product.name" header="Product" :sortable="true" class="tw-min-w-[200px]">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center">
                <div>
                  <div class="tw-font-medium tw-text-gray-900">{{ data.product?.name }}</div>
                  <div class="tw-text-sm tw-text-gray-500">Code: {{ data.product?.code || 'N/A' }}</div>
                </div>
              </div>
            </template>
          </Column>

          <!-- Quantity & Unit -->
          <Column header="Quantity" class="tw-min-w-[120px]">
            <template #body="{ data }">
              <div class="tw-text-center">
                <div class="tw-font-semibold tw-text-gray-900">{{ data.quantity }}</div>
                <div class="tw-text-xs tw-text-gray-500">
                  {{ data.quantity_by_box ? 'Box' : 'Unit' }}
                </div>
              </div>
            </template>
          </Column>

          <!-- Assigned Suppliers -->
          <Column header="Assigned Suppliers" class="tw-min-w-[250px]">
            <template #body="{ data }">
              <div class="tw-space-y-2">
                <div 
                  v-for="assignment in data.fournisseur_assignments" 
                  :key="assignment.id"
                  class="tw-flex tw-items-center tw-justify-between tw-p-2 tw-bg-gray-50 tw-rounded-lg"
                >
                  <div class="tw-flex-1">
                    <div class="tw-font-medium tw-text-sm">{{ assignment.fournisseur?.company_name }}</div>
                    <div class="tw-text-xs tw-text-gray-500">
                      Qty: {{ assignment.assigned_quantity }}
                      <span v-if="assignment.unit">
                        | {{ assignment.unit }}
                      </span>
                    </div>
                  </div>
                  <div class="tw-flex tw-gap-1">
                    <Button
                      @click="editAssignment(data, assignment)"
                      icon="pi pi-pencil"
                      size="small"
                      class="p-button-text p-button-sm"
                      severity="secondary"
                    />
                    <Button
                      @click="removeAssignment(data.id, assignment.id)"
                      icon="pi pi-trash"
                      size="small"
                      class="p-button-text p-button-sm"
                      severity="danger"
                    />
                  </div>
                </div>
                <div v-if="!data.fournisseur_assignments?.length" class="tw-text-sm tw-text-gray-400 tw-italic">
                  No suppliers assigned
                </div>
              </div>
            </template>
          </Column>

          <!-- Assignment Status -->
          <Column header="Status" class="tw-min-w-[120px]">
            <template #body="{ data }">
              <div class="tw-text-center">
                <div class="tw-mb-2">
                  <span class="tw-text-sm tw-font-medium">
                    {{ data.fournisseur_assignments?.length || 0 }} supplier(s)
                  </span>
                </div>
                <Tag 
                  :value="getAssignmentStatus(data)"
                  :severity="getAssignmentStatusSeverity(data)"
                  class="tw-w-full"
                />
              </div>
            </template>
          </Column>

          <!-- Actions -->
          <Column header="Actions" class="tw-min-w-[200px]">
            <template #body="{ data }">
              <div class="tw-flex tw-gap-2">
                <Button
                  @click="showSupplierDialog(data)"
                  icon="pi pi-plus"
                  label="Add Suppliers"
                  size="small"
                  class="p-button-primary p-button-sm"
                />

              </div>
            </template>
          </Column>
        </DataTable>
      </div>

      <!-- Supplier Assignment Dialog -->
      <Dialog 
        :visible="supplierDialog" 
        @update:visible="supplierDialog = $event"
        modal 
        :header="`Assign Suppliers - ${selectedProduct?.product?.name || 'Product'}`"
        class="tw-w-full tw-max-w-md"
      >
        <div class="tw-space-y-4">
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              Select Suppliers *
            </label>
            <MultiSelect
              v-model="assignmentForm.fournisseur_ids"
              :options="suppliers"
              optionLabel="company_name"
              optionValue="id"
              placeholder="Choose suppliers"
              class="tw-w-full"
              filter
              :class="{ 'p-invalid': formErrors.fournisseur_ids }"
              :maxSelectedLabels="3"
            >
              <template #option="{ option }">
                <div class="tw-flex tw-flex-col">
                  <div class="tw-font-medium">{{ option.company_name }}</div>
                  <div class="tw-text-sm tw-text-gray-500">{{ option.contact_person }}</div>
                </div>
              </template>
            </MultiSelect>
            <small v-if="formErrors.fournisseur_ids" class="p-error">{{ formErrors.fournisseur_ids }}</small>
          </div>

          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              Quantity to Request (for all selected suppliers)
            </label>
            <InputNumber
              v-model="assignmentForm.quantity"
              :min="1"
              :max="selectedProduct?.quantity || 999999"
              placeholder="Enter quantity"
              class="tw-w-full"
              showButtons
              :suffix="` ${selectedProduct?.quantity_by_box ? 'Boxes' : 'Units'}`"
            />
            <small v-if="formErrors.quantity" class="p-error">{{ formErrors.quantity }}</small>
            <small v-else class="tw-block tw-text-gray-600 tw-mt-1">
              Each selected supplier will receive a request for this quantity (Max: {{ selectedProduct?.quantity || 0 }})
            </small>
          </div>

          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              Unit (for all selected suppliers)
            </label>
            <InputText
              v-model="assignmentForm.unit"
              placeholder="e.g., piece, box, kg"
              class="tw-w-full"
            />
          </div>

          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              Notes
            </label>
            <Textarea
              v-model="assignmentForm.notes"
              rows="3"
              placeholder="Additional notes..."
              class="tw-w-full"
            />
          </div>
        </div>

        <template #footer>
          <div class="tw-flex tw-gap-2">
            <Button 
              @click="supplierDialog = false"
              label="Cancel"
              class="p-button-secondary"
            />
            <Button 
              @click="saveAssignment"
              :loading="submittingAssignment"
              label="Assign Suppliers"
              class="p-button-primary"
            />
          </div>
        </template>
      </Dialog>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="tw-text-center tw-py-12">
      <div class="tw-text-red-600 tw-mb-4">{{ error }}</div>
      <Button @click="fetchServiceDemand" label="Try Again" class="p-button-primary" />
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Dropdown from 'primevue/dropdown'
import MultiSelect from 'primevue/multiselect'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import ProgressSpinner from 'primevue/progressspinner'
import ProgressBar from 'primevue/progressbar'
import Tag from 'primevue/tag'
import axios from 'axios'

// Composables
const route = useRoute()
const toast = useToast()

// Reactive state
const loading = ref(true)
const error = ref(null)
const serviceDemand = ref(null)
const suppliers = ref([])
const supplierDialog = ref(false)
const selectedProduct = ref(null)
const submittingAssignment = ref(false)
const savingData = ref(false)

// Assignment form
const assignmentForm = reactive({
  fournisseur_ids: [],
  quantity: 0,
  unit: '',
  notes: ''
})

const formErrors = ref({})

// Computed properties
const hasAssignments = computed(() => {
  return serviceDemand.value?.items?.some(item => item.fournisseur_assignments?.length > 0)
})

// Methods
const fetchServiceDemand = async () => {
  try {
    loading.value = true
    error.value = null
    
    const response = await axios.get(`/api/service-demands/${route.params.id}`)
    
    if (response.data.success) {
      serviceDemand.value = response.data.data
    } else {
      throw new Error(response.data.message || 'Failed to fetch service demand')
    }
  } catch (err) {
    console.error('Error fetching service demand:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to load service demand'
  } finally {
    loading.value = false
  }
}

const fetchSuppliers = async () => {
  try {
    const response = await axios.get('/api/service-demands/meta/fournisseurs')
    if (response.data.success) {
      suppliers.value = response.data.data
    }
  } catch (err) {
    console.error('Error fetching suppliers:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load suppliers',
      life: 3000
    })
  }
}

const showSupplierDialog = (product) => {
  selectedProduct.value = product
  assignmentForm.fournisseur_ids = []
  assignmentForm.quantity = product.quantity || 1 // Initialize with product quantity
  assignmentForm.unit = product.quantity_by_box ? 'Box' : 'Unit' // Get unit from product
  assignmentForm.notes = ''
  formErrors.value = {}
  supplierDialog.value = true
}

const saveAssignment = async () => {
  try {
    formErrors.value = {}
    
    // Validation
    if (!assignmentForm.fournisseur_ids || assignmentForm.fournisseur_ids.length === 0) {
      formErrors.value.fournisseur_ids = 'At least one supplier is required'
      return
    }
    
    if (!assignmentForm.quantity || assignmentForm.quantity <= 0) {
      formErrors.value.quantity = 'Quantity must be greater than 0'
      return
    }
    
    submittingAssignment.value = true
    
    // Create assignments for each selected supplier
    const assignments = assignmentForm.fournisseur_ids.map(fournisseurId => ({
      item_id: selectedProduct.value.id, // Include item_id in each assignment
      fournisseur_id: fournisseurId,
      assigned_quantity: assignmentForm.quantity, // User-specified quantity
      unit_price: null, // No price for proforma demand
      unit: assignmentForm.unit,
      notes: assignmentForm.notes
    }))
    
    // Send bulk assignment request
    const response = await axios.post(
      `/api/service-demands/${route.params.id}/bulk-assign-fournisseurs`,
      {
        assignments: assignments
      }
    )
    
    if (response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: `${assignmentForm.fournisseur_ids.length} supplier(s) assigned successfully`,
        life: 3000
      })
      
      supplierDialog.value = false
      await fetchServiceDemand()
    } else {
      throw new Error(response.data.message || 'Assignment failed')
    }
  } catch (err) {
    console.error('Error assigning suppliers:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Failed to assign suppliers',
      life: 3000
    })
  } finally {
    submittingAssignment.value = false
  }
}

const removeAssignment = async (itemId, assignmentId) => {
  try {
    const response = await axios.delete(
      `/api/service-demands/${route.params.id}/items/${itemId}/assignments/${assignmentId}`
    )
    
    if (response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Assignment removed successfully',
        life: 3000
      })
      
      await fetchServiceDemand()
    } else {
      throw new Error(response.data.message || 'Failed to remove assignment')
    }
  } catch (err) {
    console.error('Error removing assignment:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Failed to remove assignment',
      life: 3000
    })
  }
}

const editAssignment = (product, assignment) => {
  selectedProduct.value = product
  assignmentForm.fournisseur_ids = [assignment.fournisseur_id]
  assignmentForm.quantity = assignment.assigned_quantity || product.quantity || 1
  assignmentForm.unit = assignment.unit || (product.quantity_by_box ? 'Box' : 'Unit')
  assignmentForm.notes = assignment.notes || ''
  formErrors.value = {}
  supplierDialog.value = true
}

const saveProforma = async () => {
  try {
    savingData.value = true
    
    // Check if there are any supplier assignments
    const hasAnyAssignments = serviceDemand.value?.items?.some(item => item.fournisseur_assignments?.length > 0)
    if (!hasAnyAssignments) {
      toast.add({
        severity: 'warn',
        summary: 'Warning',
        detail: 'No supplier assignments found to save',
        life: 3000
      })
      return
    }

    let totalFactureProformas = 0

    // Process each product with assignments
    for (const product of serviceDemand.value.items) {
      const assignments = product.fournisseur_assignments || []
      if (assignments.length === 0) continue

      // Group assignments by supplier
      const supplierGroups = {}
      assignments.forEach(assignment => {
        const supplierId = assignment.fournisseur.id
        if (!supplierGroups[supplierId]) {
          supplierGroups[supplierId] = {
            supplier: assignment.fournisseur,
            products: []
          }
        }
        
        supplierGroups[supplierId].products.push({
          product_id: product.product.id,
          quantity: assignment.assigned_quantity,
          price: 0,
          unit: assignment.unit || 'Unit'
        })
      })

      // Create facture proforma for each supplier
      for (const [supplierId, group] of Object.entries(supplierGroups)) {
        try {
          const factureProformaData = {
            fournisseur_id: parseInt(supplierId),
            service_demand_purchasing_id: serviceDemand.value.id,
            products: group.products
          }

          const proformaResponse = await axios.post('/api/facture-proformas', factureProformaData)
          
          if (proformaResponse.data.status !== 'success') {
            throw new Error('Failed to create facture proforma')
          }

          totalFactureProformas++

          // Add workflow note
          await addWorkflowNote('Generate Facture Proforma')

        } catch (supplierError) {
          console.error(`Error saving proforma for supplier ${group.supplier.company_name}:`, supplierError)
          toast.add({
            severity: 'error',
            summary: 'Error',
            detail: `Failed to save proforma for ${group.supplier.company_name}`,
            life: 3000
          })
        }
      }
    }

    if (totalFactureProformas > 0) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: `Saved ${totalFactureProformas} facture proforma(s) successfully`,
        life: 3000
      })
      
      // Navigate to facture proforma list
      await navigateToFactureProformaList()
    }

  } catch (err) {
    console.error('Error saving facture proforma:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to save facture proforma data',
      life: 3000
    })
  } finally {
    savingData.value = false
  }
}

const saveBonCommend = async () => {
  try {
    savingData.value = true
    
    // Check if there are any supplier assignments
    const hasAnyAssignments = serviceDemand.value?.items?.some(item => item.fournisseur_assignments?.length > 0)
    if (!hasAnyAssignments) {
      toast.add({
        severity: 'warn',
        summary: 'Warning',
        detail: 'No supplier assignments found to save',
        life: 3000
      })
      return
    }

    let totalBonCommends = 0

    // Process each product with assignments
    for (const product of serviceDemand.value.items) {
      const assignments = product.fournisseur_assignments || []
      if (assignments.length === 0) continue

      // Group assignments by supplier
      const supplierGroups = {}
      assignments.forEach(assignment => {
        const supplierId = assignment.fournisseur.id
        if (!supplierGroups[supplierId]) {
          supplierGroups[supplierId] = {
            supplier: assignment.fournisseur,
            products: []
          }
        }
        
        supplierGroups[supplierId].products.push({
          product_id: product.product.id,
          quantity: assignment.assigned_quantity,
          price: 0,
          unit: assignment.unit || 'Unit'
        })
      })

      // For each supplier, create facture proforma first, then bon commend
      for (const [supplierId, group] of Object.entries(supplierGroups)) {
        try {
          // Step 1: Create facture proforma for this supplier
          const factureProformaData = {
            fournisseur_id: parseInt(supplierId),
            service_demand_purchasing_id: serviceDemand.value.id,
            products: group.products
          }

          const proformaResponse = await axios.post('/api/facture-proformas', factureProformaData)
          
          if (proformaResponse.data.status !== 'success') {
            throw new Error('Failed to create facture proforma')
          }

          const factureProforma = proformaResponse.data.data

          // Step 2: Create bon commend items linked to the facture proforma
          const bonCommendData = {
            fournisseur_id: parseInt(supplierId),
            service_demand_purchasing_id: serviceDemand.value.id,
            items: group.products.map(prod => ({
              factureproforma_id: factureProforma.id,
              product_id: prod.product_id,
              quantity: prod.quantity,
              quantity_desired: prod.quantity,
              price: prod.price,
              unit: prod.unit,
              status: 'pending'
            }))
          }

          const bonCommendResponse = await axios.post('/api/bon-commends', bonCommendData)
          
          if (bonCommendResponse.data.status === 'success') {
            totalBonCommends++
          }

          // Add workflow note
          await addWorkflowNote('Generate Facture Proforma->Generate Boncommend')

        } catch (supplierError) {
          console.error(`Error saving boncommend for supplier ${group.supplier.company_name}:`, supplierError)
          toast.add({
            severity: 'error',
            summary: 'Error',
            detail: `Failed to save boncommend for ${group.supplier.company_name}`,
            life: 3000
          })
        }
      }
    }

    if (totalBonCommends > 0) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: `Saved ${totalBonCommends} bon commend(s) successfully`,
        life: 3000
      })
      
      // Navigate to bon commend list
      await navigateToBonCommendList()
    }

  } catch (err) {
    console.error('Error saving bon commend:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to save bon commend data',
      life: 3000
    })
  } finally {
    savingData.value = false
  }
}

// Navigation functions
const navigateToFactureProformaList = async () => {
  // Navigate to facture proforma list page
  window.location.href = '/purchasing/facture-proforma-list'
}

const navigateToBonCommendList = async () => {
  // Navigate to bon commend list page
  window.location.href = '/purchasing/bon-commend-list'
}

// Workflow tracking function
const addWorkflowNote = async (noteText) => {
  try {
    // Add workflow tracking note to service demand
    await axios.post(`/api/service-demands/${route.params.id}/add-note`, {
      note: noteText,
      created_by: 'system'
    })
  } catch (error) {
    console.error('Error adding workflow note:', error)
  }
}



const refreshData = async () => {
  await fetchServiceDemand()
  toast.add({
    severity: 'success',
    summary: 'Refreshed',
    detail: 'Data refreshed successfully',
    life: 2000
  })
}

// Utility functions
const getAssignmentStatus = (product) => {
  const assignmentCount = product.fournisseur_assignments?.length || 0
  if (assignmentCount === 0) return 'Not Assigned'
  if (assignmentCount === 1) return '1 Supplier'
  return `${assignmentCount} Suppliers`
}

const getAssignmentStatusSeverity = (product) => {
  const assignmentCount = product.fournisseur_assignments?.length || 0
  if (assignmentCount === 0) return 'secondary'
  if (assignmentCount === 1) return 'info'
  return 'success'
}

const getStatusSeverity = (status) => {
  const severityMap = {
    'draft': 'secondary',
    'sent': 'info',
    'approved': 'success',
    'rejected': 'danger',
    'completed': 'success'
  }
  return severityMap[status] || 'secondary'
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD'
  }).format(amount || 0)
}

// Lifecycle
onMounted(async () => {
  await Promise.all([
    fetchServiceDemand(),
    fetchSuppliers()
  ])
})
</script>

<style scoped>
.service-demand-detail {
  padding: 1.5rem;
  background-color: #f9fafb;
  min-height: 100vh;
}

.progress-complete :deep(.p-progressbar-value) {
  background-color: #10b981;
}

.progress-partial :deep(.p-progressbar-value) {
  background-color: #f59e0b;
}

.progress-empty :deep(.p-progressbar-value) {
  background-color: #d1d5db;
}

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
