<template>
  <div class="tw-p-6 tw-bg-gray-50 tw-min-h-screen">
    <!-- Header -->
    <div class="tw-bg-white tw-p-6 tw-rounded-lg tw-shadow-md tw-mb-6">
      <div class="tw-flex tw-justify-between tw-items-center">
        <div>
          <h1 class="tw-text-2xl tw-font-bold tw-text-gray-900">Approval Persons Management</h1>
          <p class="tw-text-gray-600 tw-mt-1">Manage users who can approve purchase orders based on amount thresholds</p>
        </div>
        <Button 
          @click="openCreateDialog" 
          label="Add Approval Person" 
          icon="pi pi-plus" 
          class="p-button-success"
        />
      </div>
    </div>

    <!-- Filters -->
    <div class="tw-bg-white tw-p-4 tw-rounded-lg tw-shadow-md tw-mb-6">
      <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-4">
        <div class="tw-flex tw-items-center tw-gap-2">
          <label class="tw-text-sm tw-font-medium tw-text-gray-700">Status:</label>
          <Dropdown 
            v-model="filters.is_active" 
            :options="statusOptions" 
            optionLabel="label" 
            optionValue="value"
            placeholder="All Status"
            class="tw-flex-1"
          />
        </div>
      
        <div class="tw-flex tw-gap-2">
          <Button 
            @click="applyFilters" 
            label="Apply" 
            icon="pi pi-filter" 
            class="p-button-primary"
          />
          <Button 
            @click="resetFilters" 
            label="Reset" 
            icon="pi pi-filter-slash" 
            class="p-button-secondary"
          />
        </div>
      </div>
    </div>

    <!-- Data Table -->
    <div class="tw-bg-white tw-rounded-lg tw-shadow-md tw-overflow-hidden">
      <DataTable 
        :value="approvalPersons" 
        :loading="loading"
        stripedRows
        class="tw-w-full"
      >
        <Column field="id" header="ID" sortable style="width: 80px" />
        
        <Column header="User" sortable>
          <template #body="slotProps">
            <div>
              <div class="tw-font-semibold tw-text-gray-900">{{ slotProps.data.user.name }}</div>
              <div class="tw-text-sm tw-text-gray-500">{{ slotProps.data.user.email }}</div>
            </div>
          </template>
        </Column>

      

        <Column field="max_amount" header="Max Amount" sortable>
          <template #body="slotProps">
            <div class="tw-font-semibold tw-text-blue-600">
              {{ formatCurrency(slotProps.data.max_amount) }}
            </div>
          </template>
        </Column>

     

        <Column field="pending_approvals_count" header="Pending" sortable style="width: 100px">
          <template #body="slotProps">
            <Tag 
              :value="slotProps.data.pending_approvals_count || 0" 
              :severity="slotProps.data.pending_approvals_count > 0 ? 'warning' : 'secondary'"
            />
          </template>
        </Column>

        <Column field="is_active" header="Status" sortable style="width: 100px">
          <template #body="slotProps">
            <Tag 
              :value="slotProps.data.is_active ? 'Active' : 'Inactive'" 
              :severity="slotProps.data.is_active ? 'success' : 'danger'"
            />
          </template>
        </Column>

        <Column header="Actions" style="width: 200px">
          <template #body="slotProps">
            <div class="tw-flex tw-gap-2">
              <Button 
                @click="editApprovalPerson(slotProps.data)" 
                icon="pi pi-pencil" 
                class="p-button-rounded p-button-text p-button-info"
                v-tooltip.top="'Edit'"
              />
              <Button 
                @click="toggleActive(slotProps.data)" 
                :icon="slotProps.data.is_active ? 'pi pi-eye-slash' : 'pi pi-eye'" 
                :class="slotProps.data.is_active ? 'p-button-rounded p-button-text p-button-warning' : 'p-button-rounded p-button-text p-button-success'"
                v-tooltip.top="slotProps.data.is_active ? 'Deactivate' : 'Activate'"
              />
              <Button 
                @click="deleteApprovalPerson(slotProps.data)" 
                icon="pi pi-trash" 
                class="p-button-rounded p-button-text p-button-danger"
                v-tooltip.top="'Delete'"
              />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Create/Edit Dialog -->
    <Dialog 
      v-model:visible="dialogVisible" 
      :header="isEditing ? 'Edit Approval Person' : 'Create Approval Person'"
      :modal="true"
      class="tw-w-full tw-max-w-2xl"
    >
      <div class="tw-space-y-4 tw-py-4">
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">User *</label>
          <Dropdown 
            v-model="form.user_id" 
            :options="users" 
            optionLabel="name" 
            optionValue="id"
            placeholder="Select User"
            filter
            :disabled="isEditing"
            class="tw-w-full"
            :class="{'p-invalid': errors.user_id}"
          />
          <small v-if="errors.user_id" class="p-error">{{ errors.user_id }}</small>
        </div>

        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Maximum Amount (DZD) *</label>
          <InputNumber 
            v-model="form.max_amount" 
            mode="currency" 
            currency="DZD" 
            locale="fr-FR"
            :min="0"
            class="tw-w-full"
            :class="{'p-invalid': errors.max_amount}"
          />
          <small v-if="errors.max_amount" class="p-error">{{ errors.max_amount }}</small>
        </div>

      

        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Description</label>
          <Textarea 
            v-model="form.description" 
            rows="3"
            placeholder="Additional notes about approval authority..."
            class="tw-w-full"
          />
        </div>


        <div class="tw-flex tw-items-center">
          <Checkbox 
            v-model="form.is_active" 
            :binary="true" 
            inputId="is_active"
          />
          <label for="is_active" class="tw-ml-2 tw-text-sm tw-font-medium tw-text-gray-700">Active</label>
        </div>
      </div>

      <template #footer>
        <Button 
          @click="dialogVisible = false" 
          label="Cancel" 
          icon="pi pi-times" 
          class="p-button-text"
        />
        <Button 
          @click="saveApprovalPerson" 
          :label="isEditing ? 'Update' : 'Create'" 
          icon="pi pi-check" 
          :loading="saving"
          class="p-button-primary"
        />
      </template>
    </Dialog>

    <!-- Confirm Dialog -->
    <ConfirmDialog />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import axios from 'axios'

import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Dropdown from 'primevue/dropdown'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Checkbox from 'primevue/checkbox'
import Tag from 'primevue/tag'
import ConfirmDialog from 'primevue/confirmdialog'

const toast = useToast()
const confirm = useConfirm()

// Reactive state
const loading = ref(true)
const saving = ref(false)
const approvalPersons = ref([])
const users = ref([])
const dialogVisible = ref(false)
const isEditing = ref(false)
const editingId = ref(null)

// Filters
const filters = reactive({
  is_active: null,
  search: ''
})

const statusOptions = [
  { label: 'All Status', value: null },
  { label: 'Active', value: true },
  { label: 'Inactive', value: false }
]

// Form
const form = reactive({
  user_id: null,
  max_amount: 0,
  description: '',
  is_active: true,
  priority: 0
})

const errors = reactive({})

// Methods
const fetchApprovalPersons = async () => {
  try {
    loading.value = true
    const params = new URLSearchParams()
    
    if (filters.is_active !== null) params.append('is_active', filters.is_active)
    if (filters.search) params.append('search', filters.search)

    const response = await axios.get(`/api/approval-persons?${params.toString()}`)
    approvalPersons.value = response.data.data || response.data
  } catch (err) {
    console.error('Error fetching approval persons:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to fetch approval persons',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const fetchUsers = async () => {
  try {
    const response = await axios.get('/api/users')
    users.value = response.data.data || response.data
  } catch (err) {
    console.error('Error fetching users:', err)
  }
}

const openCreateDialog = () => {
  resetForm()
  isEditing.value = false
  dialogVisible.value = true
}

const editApprovalPerson = (approvalPerson) => {
  resetForm()
  isEditing.value = true
  editingId.value = approvalPerson.id
  
  form.user_id = approvalPerson.user_id
  form.max_amount = approvalPerson.max_amount
  form.description = approvalPerson.description || ''
  form.is_active = approvalPerson.is_active
  form.priority = approvalPerson.priority
  
  dialogVisible.value = true
}

const saveApprovalPerson = async () => {
  try {
    saving.value = true
    clearErrors()

    const endpoint = isEditing.value 
      ? `/api/approval-persons/${editingId.value}`
      : '/api/approval-persons'
    
    const method = isEditing.value ? 'put' : 'post'
    
    const response = await axios[method](endpoint, form)

    if (response.data.status === 'success') {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: response.data.message,
        life: 3000
      })
      
      dialogVisible.value = false
      await fetchApprovalPersons()
    }
  } catch (err) {
    console.error('Error saving approval person:', err)
    
    if (err.response?.data?.errors) {
      Object.assign(errors, err.response.data.errors)
    }
    
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Failed to save approval person',
      life: 3000
    })
  } finally {
    saving.value = false
  }
}

const toggleActive = async (approvalPerson) => {
  try {
    const response = await axios.post(`/api/approval-persons/${approvalPerson.id}/toggle-active`)
    
    if (response.data.status === 'success') {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: response.data.message,
        life: 3000
      })
      
      await fetchApprovalPersons()
    }
  } catch (err) {
    console.error('Error toggling active status:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to update status',
      life: 3000
    })
  }
}

const deleteApprovalPerson = (approvalPerson) => {
  confirm.require({
    message: `Are you sure you want to delete ${approvalPerson.user.name} as an approval person?`,
    header: 'Confirm Deletion',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      try {
        const response = await axios.delete(`/api/approval-persons/${approvalPerson.id}`)
        
        if (response.data.status === 'success') {
          toast.add({
            severity: 'success',
            summary: 'Success',
            detail: response.data.message,
            life: 3000
          })
          
          await fetchApprovalPersons()
        }
      } catch (err) {
        console.error('Error deleting approval person:', err)
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: err.response?.data?.message || 'Failed to delete approval person',
          life: 3000
        })
      }
    }
  })
}

const applyFilters = async () => {
  await fetchApprovalPersons()
}

const resetFilters = () => {
  filters.is_active = null
  filters.search = ''
  fetchApprovalPersons()
}

const resetForm = () => {
  form.user_id = null
  form.max_amount = 0
  form.description = ''
  form.is_active = true
  form.priority = 0
  clearErrors()
}

const clearErrors = () => {
  Object.keys(errors).forEach(key => delete errors[key])
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD'
  }).format(amount || 0)
}

const getPrioritySeverity = (priority) => {
  if (priority === 0) return 'danger'
  if (priority <= 5) return 'warning'
  return 'info'
}

// Lifecycle
onMounted(async () => {
  await Promise.all([
    fetchApprovalPersons(),
    fetchUsers()
  ])
})
</script>
