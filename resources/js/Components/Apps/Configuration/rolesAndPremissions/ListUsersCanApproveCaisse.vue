<template>
  <div class="tw-bg-gray-50 tw-min-h-screen tw-p-6">
    <div class="tw-max-w-7xl tw-mx-auto">
      <!-- Header Section -->
      <div class="tw-bg-gradient-to-r tw-from-blue-600 tw-to-indigo-600 tw-rounded-2xl tw-p-8 tw-mb-8 tw-text-white">
        <div class="tw-flex tw-items-center tw-justify-between">
          <div>
            <h1 class="tw-text-3xl tw-font-bold tw-mb-2">Caisse Approval Management</h1>
            <p class="tw-text-blue-100">Manage user permissions for caisse transaction approvals</p>
          </div>
          <div class="tw-text-right">
            <div class="tw-text-blue-100 tw-text-sm">Total Approvers</div>
            <div class="tw-text-4xl tw-font-bold">{{ totalApprovers }}</div>
          </div>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-6 tw-mb-8">
        <Card class="tw-bg-white tw-shadow-md tw-rounded-xl">
          <template #content>
            <div class="tw-text-center tw-p-4">
              <div class="tw-text-2xl tw-font-bold tw-text-green-600">{{ stats.totalUsers }}</div>
              <div class="tw-text-sm tw-text-gray-500">Total Users</div>
            </div>
          </template>
        </Card>
        
        <Card class="tw-bg-white tw-shadow-md tw-rounded-xl">
          <template #content>
            <div class="tw-text-center tw-p-4">
              <div class="tw-text-2xl tw-font-bold tw-text-blue-600">{{ stats.totalApprovers }}</div>
              <div class="tw-text-sm tw-text-gray-500">Can Approve</div>
            </div>
          </template>
        </Card>
        
        <Card class="tw-bg-white tw-shadow-md tw-rounded-xl">
          <template #content>
            <div class="tw-text-center tw-p-4">
              <div class="tw-text-2xl tw-font-bold tw-text-orange-600">{{ stats.totalNonApprovers }}</div>
              <div class="tw-text-sm tw-text-gray-500">Cannot Approve</div>
            </div>
          </template>
        </Card>
        
        <Card class="tw-bg-white tw-shadow-md tw-rounded-xl">
          <template #content>
            <div class="tw-text-center tw-p-4">
              <div class="tw-text-2xl tw-font-bold tw-text-purple-600">{{ Math.round(approvalPercentage) }}%</div>
              <div class="tw-text-sm tw-text-gray-500">Approval Rate</div>
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
                    placeholder="Search users by name or email..."
                    class="tw-w-full"
                    @keyup.enter="loadUsers"
                  />
                </div>
                <Button
                  icon="pi pi-search"
                  class="p-button-primary"
                  @click="loadUsers"
                  :loading="loading"
                />
              </div>
              
              <div class="tw-flex tw-items-center tw-gap-3">
                <Dropdown
                  v-model="rowsPerPage"
                  :options="rowsPerPageOptions"
                  @change="onRowsPerPageChange"
                  placeholder="Rows per page"
                  class="tw-min-w-[140px]"
                />
                <Button
                  icon="pi pi-refresh"
                  class="p-button-outlined"
                  @click="refreshData"
                  :loading="loading"
                  v-tooltip="'Refresh'"
                />
                <Button
                  icon="pi pi-users"
                  label="View Approvers"
                  class="p-button-info"
                  @click="showApproversDialog = true"
                />
              </div>
            </div>
          </div>

          <!-- Data Table -->
          <DataTable
            :value="users"
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
            <Column field="name" header="User" sortable class="tw-min-w-[250px]">
              <template #body="{ data }">
                <div class="tw-flex tw-items-center tw-gap-3">
                  <Avatar
                    :image="data.avatar ? data.avatar : null"
                    :label="data.avatar ? null : getInitials(data.name)"
                    size="large"
                    shape="circle"
                    class="tw-bg-blue-500 tw-text-white"
                  />
                  <div>
                    <div class="tw-font-semibold tw-text-gray-900">{{ data.name }}</div>
                    <div class="tw-text-sm tw-text-gray-500">{{ data.email }}</div>
                  </div>
                </div>
              </template>
            </Column>

            <!-- Role Column -->
            <Column field="role" header="Role" sortable>
              <template #body="{ data }">
                <Tag 
                  :value="data.role" 
                  :severity="getRoleSeverity(data.role)"
                  class="tw-font-semibold"
                />
              </template>
            </Column>

            <!-- Permission Status Column -->
            <Column field="can_approve_caisse" header="Caisse Approval" class="tw-text-center">
              <template #body="{ data }">
                <div class="tw-flex tw-items-center tw-justify-center tw-gap-2">
                  <Tag 
                    :value="data.can_approve_caisse ? 'Can Approve' : 'Cannot Approve'"
                    :severity="data.can_approve_caisse ? 'success' : 'danger'"
                    :icon="data.can_approve_caisse ? 'pi pi-check' : 'pi pi-times'"
                  />
                </div>
              </template>
            </Column>

            <!-- Actions Column -->
            <Column header="Actions" class="tw-text-center tw-min-w-[200px]">
              <template #body="{ data }">
                <div class="tw-flex tw-justify-center tw-gap-2">
                  <Button
                    v-if="!data.can_approve_caisse"
                    icon="pi pi-check"
                    label="Grant"
                    class="p-button-success p-button-sm"
                    @click="grantPermission(data)"
                    :loading="processingUsers.includes(data.id)"
                    :disabled="processingUsers.length > 0"
                    v-tooltip="'Grant caisse approval permission'"
                  />
                  <Button
                    v-else
                    icon="pi pi-times"
                    label="Revoke"
                    class="p-button-danger p-button-sm"
                    @click="confirmRevokePermission(data)"
                    :loading="processingUsers.includes(data.id)"
                    :disabled="processingUsers.length > 0"
                    v-tooltip="'Revoke caisse approval permission'"
                  />
                </div>
              </template>
            </Column>

            <!-- Empty State -->
            <template #empty>
              <div class="tw-text-center tw-py-16">
                <i class="pi pi-users tw-text-6xl tw-text-gray-300 tw-mb-4"></i>
                <h3 class="tw-text-xl tw-font-semibold tw-text-gray-700">No Users Found</h3>
                <p class="tw-text-gray-500 tw-mt-2">
                  {{ searchQuery ? 'No users match your search criteria.' : 'No users available.' }}
                </p>
              </div>
            </template>

            <!-- Loading Template -->
            <template #loading>
              <div class="tw-text-center tw-py-16">
                <ProgressSpinner strokeWidth="4" class="tw-w-12 tw-h-12" />
                <p class="tw-mt-4 tw-text-gray-600">Loading users...</p>
              </div>
            </template>
          </DataTable>
        </template>
      </Card>
    </div>

    <!-- Approvers Dialog -->
    <Dialog 
      v-model:visible="showApproversDialog" 
      header="Current Caisse Approvers" 
      :style="{ width: '600px' }" 
      :modal="true"
    >
      <div v-if="loadingApprovers" class="tw-text-center tw-py-8">
        <ProgressSpinner strokeWidth="4" class="tw-w-10 tw-h-10" />
        <p class="tw-mt-2">Loading approvers...</p>
      </div>
      
      <div v-else-if="approvers.length === 0" class="tw-text-center tw-py-8">
        <i class="pi pi-users tw-text-4xl tw-text-gray-300"></i>
        <p class="tw-mt-2 tw-text-gray-500">No users have caisse approval permissions yet.</p>
      </div>
      
      <div v-else class="tw-space-y-3">
        <div 
          v-for="approver in approvers" 
          :key="approver.id"
          class="tw-flex tw-items-center tw-gap-3 tw-p-3 tw-bg-gray-50 tw-rounded-lg"
        >
          <Avatar
            :image="approver.avatar ? approver.avatar : null"
            :label="approver.avatar ? null : getInitials(approver.name)"
            size="large"
            shape="circle"
            class="tw-bg-green-500 tw-text-white"
          />
          <div class="tw-flex-1">
            <div class="tw-font-semibold">{{ approver.name }}</div>
            <div class="tw-text-sm tw-text-gray-500">{{ approver.email }}</div>
          </div>
          <Tag value="Approver" severity="success" icon="pi pi-check" />
        </div>
      </div>

      <template #footer>
        <Button 
          label="Close" 
          icon="pi pi-times" 
          class="p-button-secondary" 
          @click="showApproversDialog = false" 
        />
        <Button 
          label="Refresh" 
          icon="pi pi-refresh" 
          class="p-button-primary tw-ml-2" 
          @click="loadApprovers"
          :loading="loadingApprovers"
        />
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
import ProgressSpinner from 'primevue/progressspinner'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'

// Composables
const toast = useToast()
const confirm = useConfirm()

// Reactive Data
const loading = ref(false)
const loadingApprovers = ref(false)
const users = ref([])
const approvers = ref([])
const totalRecords = ref(0)
const totalApprovers = ref(0)
const searchQuery = ref('')
const rowsPerPage = ref(15)
const processingUsers = ref([])
const showApproversDialog = ref(false)

// Pagination and Sorting
const lazyParams = ref({
  first: 0,
  rows: 15,
  page: 1,
  sortField: null,
  sortOrder: null
})

// Options
const rowsPerPageOptions = [
  { label: '10 per page', value: 10 },
  { label: '15 per page', value: 15 },
  { label: '25 per page', value: 25 },
  { label: '50 per page', value: 50 }
]

// Computed Properties
const stats = computed(() => ({
  totalUsers: users.value.length,
  totalApprovers: users.value.filter(u => u.can_approve_caisse).length,
  totalNonApprovers: users.value.filter(u => !u.can_approve_caisse).length
}))

const approvalPercentage = computed(() => {
  if (users.value.length === 0) return 0
  return (stats.value.totalApprovers / stats.value.totalUsers) * 100
})

// Methods
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

const loadUsers = async () => {
  loading.value = true
  
  try {
    const params = {
      page: lazyParams.value.page,
      per_page: lazyParams.value.rows,
      search: searchQuery.value
    }

    const response = await axios.get('/api/user-caisse-approval', { params })
    
    users.value = response.data.data || []
    totalRecords.value = response.data.total || 0
    totalApprovers.value = users.value.filter(u => u.can_approve_caisse).length

  } catch (error) {
    console.error('Error loading users:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load users',
      life: 4000
    })
  } finally {
    loading.value = false
  }
}

const loadApprovers = async () => {
  loadingApprovers.value = true
  
  try {
    const response = await axios.get('/api/user-caisse-approval/approvers')
    approvers.value = response.data.data || []
  } catch (error) {
    console.error('Error loading approvers:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load approvers',
      life: 3000
    })
  } finally {
    loadingApprovers.value = false
  }
}

const grantPermission = async (user) => {
  processingUsers.value.push(user.id)
  
  try {
    const response = await axios.post('/api/user-caisse-approval', {
      user_id: user.id
    })

    if (response.data.success) {
      user.can_approve_caisse = true
      totalApprovers.value++
      
      toast.add({
        severity: 'success',
        summary: 'Permission Granted',
        detail: response.data.message,
        life: 4000
      })
    }
  } catch (error) {
    console.error('Error granting permission:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to grant permission',
      life: 4000
    })
  } finally {
    processingUsers.value = processingUsers.value.filter(id => id !== user.id)
  }
}

const confirmRevokePermission = (user) => {
  confirm.require({
    message: `Are you sure you want to revoke caisse approval permission from ${user.name}?`,
    header: 'Revoke Permission',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    acceptLabel: 'Revoke',
    rejectLabel: 'Cancel',
    accept: () => revokePermission(user)
  })
}

const revokePermission = async (user) => {
  processingUsers.value.push(user.id)
  
  try {
    const response = await axios.delete(`/api/user-caisse-approval/${user.id}`)

    if (response.data.success) {
      user.can_approve_caisse = false
      totalApprovers.value--
      
      toast.add({
        severity: 'success',
        summary: 'Permission Revoked',
        detail: response.data.message,
        life: 4000
      })
    }
  } catch (error) {
    console.error('Error revoking permission:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to revoke permission',
      life: 4000
    })
  } finally {
    processingUsers.value = processingUsers.value.filter(id => id !== user.id)
  }
}

const onPage = (event) => {
  lazyParams.value.first = event.first
  lazyParams.value.rows = event.rows
  lazyParams.value.page = event.page + 1
  loadUsers()
}

const onSort = (event) => {
  lazyParams.value.sortField = event.sortField
  lazyParams.value.sortOrder = event.sortOrder
  loadUsers()
}

const onRowsPerPageChange = () => {
  lazyParams.value.rows = rowsPerPage.value
  lazyParams.value.first = 0
  lazyParams.value.page = 1
  loadUsers()
}

const refreshData = async () => {
  await Promise.all([
    loadUsers(),
    loadApprovers()
  ])
}

// Watch for search changes
watch(searchQuery, () => {
  // Reset pagination when searching
  lazyParams.value.first = 0
  lazyParams.value.page = 1
})

// Watch for approvers dialog visibility
watch(showApproversDialog, (newValue) => {
  if (newValue) {
    loadApprovers()
  }
})

// Lifecycle
onMounted(() => {
  loadUsers()
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
</style>
