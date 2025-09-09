<template>
  <div class="tw-bg-gray-50 tw-min-h-screen tw-p-6">
    <div class="tw-max-w-7xl tw-mx-auto">
      <!-- Header Section -->
      <div class="tw-bg-gradient-to-r tw-from-purple-600 tw-to-indigo-600 tw-rounded-2xl tw-p-8 tw-mb-8 tw-text-white">
        <div class="tw-flex tw-items-center tw-justify-between">
          <div>
            <h1 class="tw-text-3xl tw-font-bold tw-mb-2">Transfer Approval Limits</h1>
            <p class="tw-text-purple-100">Manage maximum transfer amounts that users can approve</p>
          </div>
          <div class="tw-text-right">
            <div class="tw-text-purple-100 tw-text-sm">Active Limits</div>
            <div class="tw-text-4xl tw-font-bold">{{ summary.active_limits }}</div>
          </div>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-6 tw-mb-8">
        <Card class="tw-bg-white tw-shadow-md tw-rounded-xl">
          <template #content>
            <div class="tw-text-center tw-p-4">
              <div class="tw-text-2xl tw-font-bold tw-text-blue-600">{{ summary.total_limits }}</div>
              <div class="tw-text-sm tw-text-gray-500">Total Limits</div>
            </div>
          </template>
        </Card>
        
        <Card class="tw-bg-white tw-shadow-md tw-rounded-xl">
          <template #content>
            <div class="tw-text-center tw-p-4">
              <div class="tw-text-2xl tw-font-bold tw-text-green-600">{{ summary.active_limits }}</div>
              <div class="tw-text-sm tw-text-gray-500">Active</div>
            </div>
          </template>
        </Card>
        
        <Card class="tw-bg-white tw-shadow-md tw-rounded-xl">
          <template #content>
            <div class="tw-text-center tw-p-4">
              <div class="tw-text-2xl tw-font-bold tw-text-orange-600">{{ summary.inactive_limits }}</div>
              <div class="tw-text-sm tw-text-gray-500">Inactive</div>
            </div>
          </template>
        </Card>
        
        <Card class="tw-bg-white tw-shadow-md tw-rounded-xl">
          <template #content>
            <div class="tw-text-center tw-p-4">
              <div class="tw-text-2xl tw-font-bold tw-text-purple-600">{{ formatCurrency(summary.highest_maximum) }}</div>
              <div class="tw-text-sm tw-text-gray-500">Highest Limit</div>
            </div>
          </template>
        </Card>
      </div>

      <!-- Main Content -->
      <Card class="tw-bg-white tw-shadow-xl tw-rounded-2xl">
        <template #content>
          <!-- Search and Controls -->
          <div class="tw-p-6 tw-border-b tw-border-gray-200">
            <div class="tw-flex tw-flex-col md:tw-flex-row tw-justify-between tw-items-center tw-gap-4">
              <div class="tw-flex tw-items-center tw-gap-4 tw-w-full md:tw-w-auto">
                <div class="p-inputgroup tw-w-full md:tw-w-96">
                  <span class="p-inputgroup-addon">
                    <i class="pi pi-search"></i>
                  </span>
                  <InputText
                    v-model="searchQuery"
                    placeholder="Search by user name or email..."
                    class="tw-w-full"
                    @keyup.enter="loadTransferApprovals"
                  />
                </div>
                <Dropdown
                  v-model="statusFilter"
                  :options="statusFilterOptions"
                  option-label="label"
                  option-value="value"
                  placeholder="All Statuses"
                  class="tw-min-w-[150px]"
                  @change="loadTransferApprovals"
                  showClear
                />
              </div>
              
              <div class="tw-flex tw-items-center tw-gap-3">
                <Button
                  icon="pi pi-plus"
                  label="Add Limit"
                  class="p-button-primary"
                  @click="openAddDialog"
                />
                <Button
                  icon="pi pi-refresh"
                  class="p-button-outlined"
                  @click="refreshData"
                  :loading="loading"
                  v-tooltip="'Refresh'"
                />
              </div>
            </div>
          </div>

          <!-- Data Table -->
          <DataTable
            :value="transferApprovals"
            :lazy="true"
            :paginator="true"
            :rows="rowsPerPage"
            :totalRecords="totalRecords"
            @page="onPage"
            @sort="onSort"
            :loading="loading"
            paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
            currentPageReportTemplate="{first} to {last} of {totalRecords}"
            responsiveLayout="scroll"
            stripedRows
            rowHover
            class="tw-rounded-lg"
          >
            <!-- User Column -->
            <Column field="user.name" header="User" sortable class="tw-min-w-[250px]">
              <template #body="{ data }">
                <div class="tw-flex tw-items-center tw-gap-3">
                  <Avatar
                    :image="data.user.avatar ? data.user.avatar : null"
                    :label="data.user.avatar ? null : getInitials(data.user.name)"
                    size="large"
                    shape="circle"
                    class="tw-bg-purple-500 tw-text-white"
                  />
                  <div>
                    <div class="tw-font-semibold tw-text-gray-900">{{ data.user.name }}</div>
                    <div class="tw-text-sm tw-text-gray-500">{{ data.user.email }}</div>
                  </div>
                </div>
              </template>
            </Column>

            <!-- Role Column -->
            <Column field="user.role" header="Role" sortable>
              <template #body="{ data }">
                <Tag 
                  :value="data.user.role" 
                  :severity="getRoleSeverity(data.user.role)"
                  class="tw-font-semibold"
                />
              </template>
            </Column>

            <!-- Maximum Amount Column -->
            <Column field="maximum" header="Maximum Amount" sortable class="tw-text-right">
              <template #body="{ data }">
                <div class="tw-text-right">
                  <div class="tw-text-lg tw-font-bold tw-text-green-600">{{ formatCurrency(data.maximum) }}</div>
                  <div class="tw-text-xs tw-text-gray-500">{{ data.formatted_maximum }}</div>
                </div>
              </template>
            </Column>

            <!-- Status Column -->
            <Column field="is_active" header="Status" class="tw-text-center">
              <template #body="{ data }">
                <Tag 
                  :value="data.status_text"
                  :severity="data.status_color"
                  :icon="data.is_active ? 'pi pi-check' : 'pi pi-times'"
                />
              </template>
            </Column>

            <!-- Created Date Column -->
            <Column field="created_at" header="Created" sortable>
              <template #body="{ data }">
                <div class="tw-text-sm">
                  <div>{{ formatDate(data.created_at) }}</div>
                  <div class="tw-text-gray-500">{{ data.created_at_human }}</div>
                </div>
              </template>
            </Column>

            <!-- Actions Column -->
            <Column header="Actions" class="tw-text-center tw-min-w-[200px]">
              <template #body="{ data }">
                <div class="tw-flex tw-justify-center tw-gap-2">
                  <Button
                    icon="pi pi-pencil"
                    class="p-button-info p-button-sm"
                    @click="openEditDialog(data)"
                    v-tooltip="'Edit'"
                  />
                  <Button
                    :icon="data.is_active ? 'pi pi-eye-slash' : 'pi pi-eye'"
                    :class="data.is_active ? 'p-button-warning' : 'p-button-success'"
                    class="p-button-sm"
                    @click="toggleStatus(data)"
                    :loading="processingItems.includes(data.id)"
                    v-tooltip="data.is_active ? 'Deactivate' : 'Activate'"
                  />
                  <Button
                    icon="pi pi-trash"
                    class="p-button-danger p-button-sm"
                    @click="confirmDelete(data)"
                    :disabled="processingItems.length > 0"
                    v-tooltip="'Delete'"
                  />
                </div>
              </template>
            </Column>

            <!-- Empty State -->
            <template #empty>
              <div class="tw-text-center tw-py-16">
                <i class="pi pi-wallet tw-text-6xl tw-text-gray-300 tw-mb-4"></i>
                <h3 class="tw-text-xl tw-font-semibold tw-text-gray-700">No Transfer Approval Limits</h3>
                <p class="tw-text-gray-500 tw-mt-2">
                  {{ searchQuery ? 'No limits match your search criteria.' : 'Start by creating your first transfer approval limit.' }}
                </p>
                <Button 
                  v-if="!searchQuery" 
                  icon="pi pi-plus" 
                  label="Add First Limit" 
                  class="p-button-primary tw-mt-4" 
                  @click="openAddDialog" 
                />
              </div>
            </template>

            <!-- Loading Template -->
            <template #loading>
              <div class="tw-text-center tw-py-16">
                <ProgressSpinner strokeWidth="4" class="tw-w-12 tw-h-12" />
                <p class="tw-mt-4 tw-text-gray-600">Loading transfer approval limits...</p>
              </div>
            </template>
          </DataTable>
        </template>
      </Card>
    </div>

    <!-- Add/Edit Dialog -->
    <Dialog 
      v-model:visible="showFormDialog" 
      :header="dialogMode === 'add' ? 'Add Transfer Approval Limit' : 'Edit Transfer Approval Limit'" 
      :style="{ width: '600px' }" 
      :modal="true"
      :closable="!submitting"
    >
      <div class="tw-space-y-6">
        <!-- User Selection (only for add mode) -->
        <div v-if="dialogMode === 'add'">
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
            User <span class="tw-text-red-500">*</span>
          </label>
          <Dropdown
            v-model="form.user_id"
            :options="availableApprovers"
            option-label="name"
            option-value="id"
            placeholder="Select a user with caisse approval permission"
            class="tw-w-full"
            :class="{ 'p-invalid': formErrors.user_id }"
            :disabled="submitting || loadingApprovers"
            filter
            showClear
          >
            <template #option="slotProps">
              <div class="tw-flex tw-items-center tw-gap-3">
                <Avatar
                  :image="slotProps.option.avatar ? slotProps.option.avatar : null"
                  :label="slotProps.option.avatar ? null : getInitials(slotProps.option.name)"
                  size="normal"
                  shape="circle"
                  class="tw-bg-blue-500 tw-text-white"
                />
                <div>
                  <div class="tw-font-semibold">{{ slotProps.option.name }}</div>
                  <div class="tw-text-sm tw-text-gray-500">{{ slotProps.option.email }}</div>
                </div>
              </div>
            </template>
            <template #value="slotProps">
              <div v-if="slotProps.value" class="tw-flex tw-items-center tw-gap-2">
                <Avatar
                  :image="selectedUser?.avatar ? selectedUser.avatar : null"
                  :label="selectedUser?.avatar ? null : getInitials(selectedUser?.name || 'U')"
                  size="small"
                  shape="circle"
                  class="tw-bg-blue-500 tw-text-white"
                />
                <span>{{ selectedUser?.name }}</span>
              </div>
              <span v-else>Select a user with caisse approval permission</span>
            </template>
          </Dropdown>
          <small v-if="formErrors.user_id" class="p-error tw-block tw-mt-1">
            {{ formErrors.user_id[0] }}
          </small>
          <small v-if="loadingApprovers" class="tw-text-gray-500 tw-block tw-mt-1">
            <i class="pi pi-spin pi-spinner tw-mr-1"></i>
            Loading users with caisse approval permissions...
          </small>
        </div>

        <!-- User Display (for edit mode) -->
        <div v-else-if="selectedApproval">
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">User</label>
          <div class="tw-flex tw-items-center tw-gap-3 tw-p-3 tw-bg-gray-50 tw-rounded-lg">
            <Avatar
              :image="selectedApproval.user.avatar ? selectedApproval.user.avatar : null"
              :label="selectedApproval.user.avatar ? null : getInitials(selectedApproval.user.name)"
              size="large"
              shape="circle"
              class="tw-bg-purple-500 tw-text-white"
            />
            <div>
              <div class="tw-font-semibold">{{ selectedApproval.user.name }}</div>
              <div class="tw-text-sm tw-text-gray-500">{{ selectedApproval.user.email }}</div>
            </div>
          </div>
        </div>

        <!-- Maximum Amount -->
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
            Maximum Amount (DZD) <span class="tw-text-red-500">*</span>
          </label>
          <InputNumber
            v-model="form.maximum"
            :min="0.01"
            :max="999999999.99"
            :minFractionDigits="2"
            :maxFractionDigits="2"
            placeholder="0.00"
            class="tw-w-full"
            :class="{ 'p-invalid': formErrors.maximum }"
            :disabled="submitting"
            mode="currency"
            currency="DZD"
            locale="fr-DZ"
            :showButtons="true"
            buttonLayout="horizontal"
            spinnerMode="horizontal"
            decrementButtonClass="p-button-secondary"
            incrementButtonClass="p-button-secondary"
          />
          <small v-if="formErrors.maximum" class="p-error tw-block tw-mt-1">
            {{ formErrors.maximum[0] }}
          </small>
          <small class="tw-text-gray-500 tw-block tw-mt-1">
            This user will be able to approve transfers up to this amount
          </small>
        </div>

        <!-- Status (for edit mode) -->
        <div v-if="dialogMode === 'edit'">
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Status</label>
          <div class="tw-flex tw-items-center tw-gap-3">
            <InputSwitch
              v-model="form.is_active"
              :disabled="submitting"
            />
            <span class="tw-text-sm tw-font-medium">
              {{ form.is_active ? 'Active' : 'Inactive' }}
            </span>
          </div>
        </div>

        <!-- Notes -->
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
            Notes <span class="tw-text-gray-500">(Optional)</span>
          </label>
          <Textarea
            v-model="form.notes"
            rows="3"
            placeholder="Add any notes about this approval limit..."
            class="tw-w-full"
            :class="{ 'p-invalid': formErrors.notes }"
            :disabled="submitting"
            maxlength="1000"
          />
          <small v-if="formErrors.notes" class="p-error tw-block tw-mt-1">
            {{ formErrors.notes[0] }}
          </small>
          <small class="tw-text-gray-500 tw-block tw-mt-1">
            {{ form.notes?.length || 0 }}/1000 characters
          </small>
        </div>
      </div>

      <template #footer>
        <div class="tw-flex tw-justify-end tw-gap-3">
          <Button 
            label="Cancel" 
            icon="pi pi-times" 
            class="p-button-secondary" 
            @click="closeDialog" 
            :disabled="submitting"
          />
          <Button 
            :label="dialogMode === 'add' ? 'Create Limit' : 'Update Limit'"
            :icon="dialogMode === 'add' ? 'pi pi-plus' : 'pi pi-check'"
            class="p-button-primary" 
            @click="submitForm"
            :loading="submitting"
            :disabled="!isFormValid"
          />
        </div>
      </template>
    </Dialog>

    <!-- Toast Messages -->
    <Toast />
    
    <!-- Confirmation Dialog -->
    <ConfirmDialog />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import axios from 'axios'

// PrimeVue Components
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Dropdown from 'primevue/dropdown'
import Tag from 'primevue/tag'
import Avatar from 'primevue/avatar'
import Dialog from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import InputSwitch from 'primevue/inputswitch'
import ProgressSpinner from 'primevue/progressspinner'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'

// Composables
const toast = useToast()
const confirm = useConfirm()

// Reactive Data
const loading = ref(false)
const submitting = ref(false)
const loadingApprovers = ref(false)
const transferApprovals = ref([])
const availableApprovers = ref([])
const totalRecords = ref(0)
const searchQuery = ref('')
const statusFilter = ref(null)
const rowsPerPage = ref(15)
const processingItems = ref([])
const showFormDialog = ref(false)
const dialogMode = ref('add') // 'add' or 'edit'
const selectedApproval = ref(null)
const formErrors = ref({})

// Summary data
const summary = ref({
  total_limits: 0,
  active_limits: 0,
  inactive_limits: 0,
  average_maximum: 0,
  highest_maximum: 0,
  lowest_maximum: 0
})

// Pagination and Sorting
const lazyParams = ref({
  first: 0,
  rows: 15,
  page: 1,
  sortField: null,
  sortOrder: null
})

// Form Data
const form = reactive({
  user_id: null,
  maximum: null,
  is_active: true,
  notes: ''
})

// Options
const statusFilterOptions = [
  { label: 'All Statuses', value: null },
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' }
]

// Computed Properties
const selectedUser = computed(() => {
  return availableApprovers.value.find(user => user.id === form.user_id)
})

const isFormValid = computed(() => {
  if (dialogMode.value === 'add') {
    return form.user_id && form.maximum && form.maximum > 0
  } else {
    return form.maximum && form.maximum > 0
  }
})

// Methods
const formatCurrency = (amount) => {
  const value = Number(amount || 0)
  return new Intl.NumberFormat('fr-DZ', {
    style: 'currency',
    currency: 'DZD',
    minimumFractionDigits: 2
  }).format(value)
}

const formatDate = (date) => {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('fr-DZ')
}

const getInitials = (name) => {
  if (!name) return 'U'
  const words = name.split(' ')
  if (words.length === 1) return words[0].charAt(0).toUpperCase()
  return (words[0].charAt(0) + words[words.length - 1].charAt(0)).toUpperCase()
}

const getRoleSeverity = (role) => {
  const roleMap = {
    'SuperAdmin': 'danger',
    'admin': 'warning',
    'manager': 'info',
    'doctor': 'success',
    'receptionist': 'secondary'
  }
  return roleMap[role] || 'secondary'
}

const loadTransferApprovals = async () => {
  loading.value = true
  
  try {
    const params = {
      page: lazyParams.value.page,
      per_page: lazyParams.value.rows,
      search: searchQuery.value,
      status: statusFilter.value
    }

    if (lazyParams.value.sortField) {
      params.sortField = lazyParams.value.sortField
      params.sortOrder = lazyParams.value.sortOrder
    }

    const response = await axios.get('/api/transfer-approvals', { params })
    
    if (response.data.success) {
      transferApprovals.value = response.data.data || []
      totalRecords.value = response.data.meta?.total || 0
      summary.value = response.data.summary || {}
    }

  } catch (error) {
    console.error('Error loading transfer approvals:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load transfer approvals',
      life: 4000
    })
  } finally {
    loading.value = false
  }
}

const loadAvailableApprovers = async () => {
  loadingApprovers.value = true
  
  try {
    const response = await axios.get('/api/user-caisse-approval/approvers')
    
    if (response.data.data) {
      // Filter out users who already have transfer approval limits
      const existingUserIds = transferApprovals.value.map(approval => approval.user.id)
      availableApprovers.value = response.data.data.filter(user => 
        !existingUserIds.includes(user.id)
      )
    }
  } catch (error) {
    console.error('Error loading approvers:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load users with caisse approval permissions',
      life: 3000
    })
  } finally {
    loadingApprovers.value = false
  }
}

const openAddDialog = async () => {
  dialogMode.value = 'add'
  selectedApproval.value = null
  
  // Reset form
  Object.assign(form, {
    user_id: null,
    maximum: null,
    is_active: true,
    notes: ''
  })
  
  formErrors.value = {}
  await loadAvailableApprovers()
  showFormDialog.value = true
}

const openEditDialog = (approval) => {
  dialogMode.value = 'edit'
  selectedApproval.value = approval
  
  // Populate form
  Object.assign(form, {
    user_id: approval.user_id,
    maximum: approval.maximum,
    is_active: approval.is_active,
    notes: approval.notes || ''
  })
  
  formErrors.value = {}
  showFormDialog.value = true
}

const closeDialog = () => {
  showFormDialog.value = false
  selectedApproval.value = null
  formErrors.value = {}
  
  // Reset form
  Object.assign(form, {
    user_id: null,
    maximum: null,
    is_active: true,
    notes: ''
  })
}

const submitForm = async () => {
  submitting.value = true
  
  try {
    let response
    
    if (dialogMode.value === 'add') {
      response = await axios.post('/api/transfer-approvals', form)
    } else {
      response = await axios.put(`/api/transfer-approvals/${selectedApproval.value.id}`, form)
    }
    
    if (response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: response.data.message,
        life: 4000
      })
      
      closeDialog()
      await loadTransferApprovals()
    }
  } catch (error) {
    console.error('Error submitting form:', error)
    
    if (error.response?.data?.errors) {
      formErrors.value = error.response.data.errors
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: error.response?.data?.message || 'Failed to save transfer approval limit',
        life: 4000
      })
    }
  } finally {
    submitting.value = false
  }
}

const toggleStatus = async (approval) => {
  processingItems.value.push(approval.id)
  
  try {
    const response = await axios.post(`/api/transfer-approvals/${approval.id}/toggle-status`)
    
    if (response.data.success) {
      // Update local data
      approval.is_active = response.data.data.is_active
      approval.status_text = response.data.data.status_text
      approval.status_color = response.data.data.status_color
      
      toast.add({
        severity: 'success',
        summary: 'Status Updated',
        detail: response.data.message,
        life: 3000
      })
    }
  } catch (error) {
    console.error('Error toggling status:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to update status',
      life: 4000
    })
  } finally {
    processingItems.value = processingItems.value.filter(id => id !== approval.id)
  }
}

const confirmDelete = (approval) => {
  confirm.require({
    message: `Are you sure you want to delete the transfer approval limit for ${approval.user.name}?`,
    header: 'Delete Confirmation',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    acceptLabel: 'Delete',
    rejectLabel: 'Cancel',
    accept: () => deleteApproval(approval)
  })
}

const deleteApproval = async (approval) => {
  try {
    const response = await axios.delete(`/api/transfer-approvals/${approval.id}`)
    
    if (response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Deleted',
        detail: response.data.message,
        life: 4000
      })
      
      await loadTransferApprovals()
    }
  } catch (error) {
    console.error('Error deleting approval:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to delete transfer approval limit',
      life: 4000
    })
  }
}

const onPage = (event) => {
  lazyParams.value.first = event.first
  lazyParams.value.rows = event.rows
  lazyParams.value.page = event.page + 1
  loadTransferApprovals()
}

const onSort = (event) => {
  lazyParams.value.sortField = event.sortField
  lazyParams.value.sortOrder = event.sortOrder
  loadTransferApprovals()
}

const refreshData = async () => {
  await loadTransferApprovals()
}

// Watch for search changes
watch(searchQuery, () => {
  // Reset pagination when searching
  lazyParams.value.first = 0
  lazyParams.value.page = 1
})

// Lifecycle
onMounted(() => {
  loadTransferApprovals()
})
</script>

<style scoped>
/* Custom styles for better UI */
:deep(.p-datatable .p-datatable-thead > tr > th) {
  background-color: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  font-weight: 600;
  color: #374151;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background-color: #f1f5f9;
}

:deep(.p-paginator) {
  border-top: 1px solid #e2e8f0;
  background-color: #f8fafc;
}

:deep(.p-card) {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

:deep(.p-card-content) {
  padding: 0;
}

:deep(.p-tag) {
  font-weight: 500;
}

:deep(.p-button-sm) {
  padding: 0.375rem 0.75rem;
  font-size: 0.875rem;
}

:deep(.p-invalid) {
  border-color: #ef4444 !important;
}

:deep(.p-error) {
  color: #ef4444;
  font-size: 0.875rem;
}

:deep(.p-inputnumber .p-button) {
  border: 1px solid #d1d5db;
}

:deep(.p-inputnumber .p-button:hover) {
  background-color: #f3f4f6;
}
</style>
