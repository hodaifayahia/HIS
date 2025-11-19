<template>
  <div class="tw-bg-gray-50 tw-min-h-screen tw-p-6">
    <div class="tw-max-w-7xl tw-mx-auto">
      <!-- Header Section -->
      <div class="tw-bg-gradient-to-r tw-from-blue-600 tw-to-indigo-600 tw-rounded-2xl tw-p-8 tw-mb-8 tw-text-white">
        <div class="tw-flex tw-items-center tw-justify-between">
          <div>
            <h1 class="tw-text-3xl tw-font-bold tw-mb-2">Permission Management</h1>
            <p class="tw-text-blue-100">Create, manage, and assign custom permissions to users</p>
          </div>
          <div class="tw-text-right">
            <div class="tw-text-blue-100 tw-text-sm">Total Permissions</div>
            <div class="tw-text-4xl tw-font-bold">{{ summary.total_permissions }}</div>
          </div>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-6 tw-mb-8">
        <Card class="tw-bg-white tw-shadow-md tw-rounded-xl">
          <template #content>
            <div class="tw-text-center tw-p-4">
              <div class="tw-text-2xl tw-font-bold tw-text-blue-600">{{ summary.total_permissions }}</div>
              <div class="tw-text-sm tw-text-gray-500">Total Permissions</div>
            </div>
          </template>
        </Card>

        <Card class="tw-bg-white tw-shadow-md tw-rounded-xl">
          <template #content>
            <div class="tw-text-center tw-p-4">
              <div class="tw-text-2xl tw-font-bold tw-text-green-600">{{ summary.assigned_permissions }}</div>
              <div class="tw-text-sm tw-text-gray-500">Assigned</div>
            </div>
          </template>
        </Card>

        <Card class="tw-bg-white tw-shadow-md tw-rounded-xl">
          <template #content>
            <div class="tw-text-center tw-p-4">
              <div class="tw-text-2xl tw-font-bold tw-text-purple-600">{{ summary.total_users_with_permissions }}</div>
              <div class="tw-text-sm tw-text-gray-500">Users with Permissions</div>
            </div>
          </template>
        </Card>

        <Card class="tw-bg-white tw-shadow-md tw-rounded-xl">
          <template #content>
            <div class="tw-text-center tw-p-4">
              <div class="tw-text-2xl tw-font-bold tw-text-orange-600">{{ summary.most_assigned_permission }}</div>
              <div class="tw-text-sm tw-text-gray-500">Most Assigned</div>
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
                    placeholder="Search permissions..."
                    class="tw-w-full"
                    @keyup.enter="loadPermissions"
                  />
                </div>
              </div>

              <div class="tw-flex tw-items-center tw-gap-3">
                <Button
                  icon="pi pi-plus"
                  label="Create Permission"
                  class="p-button-primary"
                  @click="openCreateDialog"
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
            :value="permissions"
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
            <!-- Permission Name Column -->
            <Column field="name" header="Permission Name" sortable class="tw-min-w-[200px]">
              <template #body="{ data }">
                <div class="tw-flex tw-items-center tw-gap-3">
                  <div class="tw-w-8 tw-h-8 tw-bg-blue-100 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                    <i class="pi pi-shield tw-text-blue-600"></i>
                  </div>
                  <div>
                    <div class="tw-font-semibold tw-text-gray-900">{{ data.name }}</div>
                    <div class="tw-text-xs tw-text-gray-500">Permission</div>
                  </div>
                </div>
              </template>
            </Column>

            <!-- User Count Column -->
            <Column field="user_count" header="Assigned Users" sortable class="tw-text-center">
              <template #body="{ data }">
                <div class="tw-text-center">
                  <div class="tw-text-lg tw-font-bold tw-text-purple-600">{{ data.user_count || 0 }}</div>
                  <div class="tw-text-xs tw-text-gray-500">users</div>
                </div>
              </template>
            </Column>

            <!-- Sample Users Column -->
            <Column header="Sample Users" class="tw-min-w-[250px]">
              <template #body="{ data }">
                <div v-if="data.sample_users && data.sample_users.length > 0" class="tw-flex tw-flex-wrap tw-gap-1">
                  <Tag
                    v-for="user in data.sample_users.slice(0, 3)"
                    :key="user.id"
                    :value="user.name"
                    severity="info"
                    class="tw-text-xs"
                  />
                  <Tag
                    v-if="data.sample_users.length > 3"
                    :value="`+${data.sample_users.length - 3} more`"
                    severity="secondary"
                    class="tw-text-xs"
                  />
                </div>
                <div v-else class="tw-text-gray-400 tw-text-sm tw-italic">
                  No users assigned
                </div>
              </template>
            </Column>

            <!-- Created Date Column -->
            <Column field="created_at" header="Created" sortable>
              <template #body="{ data }">
                <div class="tw-text-sm">
                  <div>{{ formatDate(data.created_at) }}</div>
                  <div class="tw-text-gray-500">{{ data.created_at_human || 'Recently' }}</div>
                </div>
              </template>
            </Column>

            <!-- Actions Column -->
            <Column header="Actions" class="tw-text-center tw-min-w-[200px]">
              <template #body="{ data }">
                <div class="tw-flex tw-justify-center tw-gap-2">
                  <Button
                    icon="pi pi-eye"
                    class="p-button-info p-button-sm"
                    @click="viewPermissionDetails(data)"
                    v-tooltip="'View Details'"
                  />
                  <Button
                    icon="pi pi-pencil"
                    class="p-button-warning p-button-sm"
                    @click="openEditDialog(data)"
                    v-tooltip="'Edit'"
                  />
                  <Button
                    icon="pi pi-users"
                    class="p-button-success p-button-sm"
                    @click="openAssignDialog(data)"
                    v-tooltip="'Assign Users'"
                  />
                  <Button
                    icon="pi pi-trash"
                    class="p-button-danger p-button-sm"
                    @click="confirmDelete(data)"
                    :disabled="data.user_count > 0"
                    v-tooltip="data.user_count > 0 ? 'Cannot delete - users assigned' : 'Delete'"
                  />
                </div>
              </template>
            </Column>

            <!-- Empty State -->
            <template #empty>
              <div class="tw-text-center tw-py-16">
                <i class="pi pi-shield tw-text-6xl tw-text-gray-300 tw-mb-4"></i>
                <h3 class="tw-text-xl tw-font-semibold tw-text-gray-700">No Permissions Found</h3>
                <p class="tw-text-gray-500 tw-mt-2">
                  {{ searchQuery ? 'No permissions match your search criteria.' : 'Start by creating your first permission.' }}
                </p>
                <Button
                  v-if="!searchQuery"
                  icon="pi pi-plus"
                  label="Create First Permission"
                  class="p-button-primary tw-mt-4"
                  @click="openCreateDialog"
                />
              </div>
            </template>

            <!-- Loading Template -->
            <template #loading>
              <div class="tw-text-center tw-py-16">
                <ProgressSpinner strokeWidth="4" class="tw-w-12 tw-h-12" />
                <p class="tw-mt-4 tw-text-gray-600">Loading permissions...</p>
              </div>
            </template>
          </DataTable>
        </template>
      </Card>
    </div>

    <!-- Create/Edit Permission Dialog -->
    <Dialog
      v-model:visible="showPermissionDialog"
      :header="dialogMode === 'create' ? 'Create Permission' : 'Edit Permission'"
      :style="{ width: '500px' }"
      :modal="true"
      :closable="!submitting"
    >
      <div class="tw-space-y-6">
        <!-- Permission Name -->
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
            Permission Name <span class="tw-text-red-500">*</span>
          </label>
          <InputText
            v-model="permissionForm.name"
            placeholder="e.g., reports.view, users.manage"
            class="tw-w-full"
            :class="{ 'p-invalid': permissionFormErrors.name }"
            :disabled="submitting"
          />
          <small v-if="permissionFormErrors.name" class="p-error tw-block tw-mt-1">
            {{ permissionFormErrors.name[0] }}
          </small>
          <small class="tw-text-gray-500 tw-block tw-mt-1">
            Use dot notation for grouping (e.g., module.action)
          </small>
        </div>

        <!-- Description -->
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
            Description <span class="tw-text-gray-500">(Optional)</span>
          </label>
          <Textarea
            v-model="permissionForm.description"
            rows="3"
            placeholder="Describe what this permission allows..."
            class="tw-w-full"
            :class="{ 'p-invalid': permissionFormErrors.description }"
            :disabled="submitting"
            maxlength="500"
          />
          <small v-if="permissionFormErrors.description" class="p-error tw-block tw-mt-1">
            {{ permissionFormErrors.description[0] }}
          </small>
          <small class="tw-text-gray-500 tw-block tw-mt-1">
            {{ permissionForm.description?.length || 0 }}/500 characters
          </small>
        </div>
      </div>

      <template #footer>
        <div class="tw-flex tw-justify-end tw-gap-3">
          <Button
            label="Cancel"
            icon="pi pi-times"
            class="p-button-secondary"
            @click="closePermissionDialog"
            :disabled="submitting"
          />
          <Button
            :label="dialogMode === 'create' ? 'Create Permission' : 'Update Permission'"
            :icon="dialogMode === 'create' ? 'pi pi-plus' : 'pi pi-check'"
            class="p-button-primary"
            @click="submitPermissionForm"
            :loading="submitting"
            :disabled="!isPermissionFormValid"
          />
        </div>
      </template>
    </Dialog>

    <!-- Assign Users Dialog -->
    <Dialog
      v-model:visible="showAssignDialog"
      header="Assign Permission to Users"
      :style="{ width: '700px' }"
      :modal="true"
      :closable="!submitting"
    >
      <div class="tw-space-y-6">
        <!-- Permission Info -->
        <div v-if="selectedPermission" class="tw-p-4 tw-bg-blue-50 tw-rounded-lg">
          <div class="tw-flex tw-items-center tw-gap-3">
            <div class="tw-w-10 tw-h-10 tw-bg-blue-100 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
              <i class="pi pi-shield tw-text-blue-600"></i>
            </div>
            <div>
              <div class="tw-font-semibold tw-text-blue-900">{{ selectedPermission.name }}</div>
              <div class="tw-text-sm tw-text-blue-700">{{ selectedPermission.description || 'No description' }}</div>
            </div>
          </div>
        </div>

        <!-- User Search -->
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
            Search Users
          </label>
          <InputText
            v-model="userSearchQuery"
            placeholder="Search users by name or email..."
            class="tw-w-full"
            @keyup.enter="searchUsers"
          />
        </div>

        <!-- Available Users -->
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
            Available Users
          </label>
          <div class="tw-border tw-border-gray-200 tw-rounded-lg tw-max-h-64 tw-overflow-y-auto">
            <div v-if="availableUsers.length === 0" class="tw-text-center tw-py-8 tw-text-gray-500">
              <i class="pi pi-users tw-text-2xl tw-mb-2"></i>
              <div>No users found</div>
            </div>
            <div
              v-for="user in availableUsers"
              :key="user.id"
              class="tw-flex tw-items-center tw-justify-between tw-p-3 tw-border-b tw-border-gray-100 hover:tw-bg-gray-50"
            >
              <div class="tw-flex tw-items-center tw-gap-3">
                <Avatar
                  :image="user.avatar ? user.avatar : null"
                  :label="user.avatar ? null : getInitials(user.name)"
                  size="normal"
                  shape="circle"
                  class="tw-bg-blue-500 tw-text-white"
                />
                <div>
                  <div class="tw-font-semibold">{{ user.name }}</div>
                  <div class="tw-text-sm tw-text-gray-500">{{ user.email }}</div>
                </div>
              </div>
              <Button
                icon="pi pi-plus"
                class="p-button-success p-button-sm"
                @click="assignPermissionToUser(user)"
                :loading="assigningUsers.includes(user.id)"
                v-tooltip="'Assign permission'"
              />
            </div>
          </div>
        </div>

        <!-- Currently Assigned Users -->
        <div v-if="assignedUsers.length > 0">
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
            Currently Assigned Users ({{ assignedUsers.length }})
          </label>
          <div class="tw-border tw-border-gray-200 tw-rounded-lg tw-max-h-64 tw-overflow-y-auto">
            <div
              v-for="user in assignedUsers"
              :key="user.id"
              class="tw-flex tw-items-center tw-justify-between tw-p-3 tw-border-b tw-border-gray-100 hover:tw-bg-red-50"
            >
              <div class="tw-flex tw-items-center tw-gap-3">
                <Avatar
                  :image="user.avatar ? user.avatar : null"
                  :label="user.avatar ? null : getInitials(user.name)"
                  size="normal"
                  shape="circle"
                  class="tw-bg-green-500 tw-text-white"
                />
                <div>
                  <div class="tw-font-semibold">{{ user.name }}</div>
                  <div class="tw-text-sm tw-text-gray-500">{{ user.email }}</div>
                </div>
              </div>
              <Button
                icon="pi pi-minus"
                class="p-button-danger p-button-sm"
                @click="revokePermissionFromUser(user)"
                :loading="assigningUsers.includes(user.id)"
                v-tooltip="'Revoke permission'"
              />
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="tw-flex tw-justify-end">
          <Button
            label="Close"
            icon="pi pi-times"
            class="p-button-secondary"
            @click="closeAssignDialog"
            :disabled="submitting"
          />
        </div>
      </template>
    </Dialog>

    <!-- Permission Details Dialog -->
    <Dialog
      v-model:visible="showDetailsDialog"
      header="Permission Details"
      :style="{ width: '600px' }"
      :modal="true"
    >
      <div v-if="selectedPermission" class="tw-space-y-6">
        <!-- Permission Info -->
        <div class="tw-p-4 tw-bg-blue-50 tw-rounded-lg">
          <div class="tw-flex tw-items-center tw-gap-3">
            <div class="tw-w-12 tw-h-12 tw-bg-blue-100 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
              <i class="pi pi-shield tw-text-blue-600"></i>
            </div>
            <div>
              <div class="tw-text-lg tw-font-semibold tw-text-blue-900">{{ selectedPermission.name }}</div>
              <div class="tw-text-sm tw-text-blue-700">{{ selectedPermission.description || 'No description provided' }}</div>
            </div>
          </div>
        </div>

        <!-- Statistics -->
        <div class="tw-grid tw-grid-cols-2 tw-gap-4">
          <Card class="tw-bg-green-50 tw-border-green-200">
            <template #content>
              <div class="tw-text-center">
                <div class="tw-text-2xl tw-font-bold tw-text-green-600">{{ selectedPermission.user_count || 0 }}</div>
                <div class="tw-text-sm tw-text-green-700">Assigned Users</div>
              </div>
            </template>
          </Card>
          <Card class="tw-bg-blue-50 tw-border-blue-200">
            <template #content>
              <div class="tw-text-center">
                <div class="tw-text-2xl tw-font-bold tw-text-blue-600">{{ formatDate(selectedPermission.created_at) }}</div>
                <div class="tw-text-sm tw-text-blue-700">Created Date</div>
              </div>
            </template>
          </Card>
        </div>

        <!-- Assigned Users List -->
        <div>
          <h3 class="tw-text-lg tw-font-semibold tw-mb-3">Assigned Users</h3>
          <div v-if="assignedUsers.length === 0" class="tw-text-center tw-py-8 tw-text-gray-500">
            <i class="pi pi-users tw-text-3xl tw-mb-2"></i>
            <div>No users assigned to this permission</div>
          </div>
          <div v-else class="tw-space-y-2">
            <div
              v-for="user in assignedUsers"
              :key="user.id"
              class="tw-flex tw-items-center tw-gap-3 tw-p-3 tw-bg-gray-50 tw-rounded-lg"
            >
              <Avatar
                :image="user.avatar ? user.avatar : null"
                :label="user.avatar ? null : getInitials(user.name)"
                size="normal"
                shape="circle"
                class="tw-bg-green-500 tw-text-white"
              />
              <div>
                <div class="tw-font-semibold">{{ user.name }}</div>
                <div class="tw-text-sm tw-text-gray-500">{{ user.email }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
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
import Textarea from 'primevue/textarea'
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
const submitting = ref(false)
const permissions = ref([])
const totalRecords = ref(0)
const searchQuery = ref('')
const rowsPerPage = ref(15)
const assigningUsers = ref([])
const showPermissionDialog = ref(false)
const showAssignDialog = ref(false)
const showDetailsDialog = ref(false)
const dialogMode = ref('create') // 'create' or 'edit'
const selectedPermission = ref(null)
const availableUsers = ref([])
const assignedUsers = ref([])
const userSearchQuery = ref('')
const permissionFormErrors = ref({})

// Summary data
const summary = ref({
  total_permissions: 0,
  assigned_permissions: 0,
  total_users_with_permissions: 0,
  most_assigned_permission: 'None'
})

// Pagination and Sorting
const lazyParams = ref({
  first: 0,
  rows: 15,
  page: 1,
  sortField: null,
  sortOrder: null
})

// Permission Form Data
const permissionForm = reactive({
  name: '',
  description: ''
})

// Computed Properties
const isPermissionFormValid = computed(() => {
  return permissionForm.name && permissionForm.name.trim().length > 0
})

// Methods
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

const loadPermissions = async () => {
  loading.value = true

  try {
    const params = {
      page: lazyParams.value.page,
      per_page: lazyParams.value.rows,
      search: searchQuery.value
    }

    if (lazyParams.value.sortField) {
      params.sortField = lazyParams.value.sortField
      params.sortOrder = lazyParams.value.sortOrder
    }

    const response = await axios.get('/api/configuration/permissions', { params })

    if (response.data.success !== false) {
      permissions.value = response.data.data || []
      totalRecords.value = response.data.meta?.total || response.data.total || 0
      summary.value = response.data.summary || {
        total_permissions: permissions.value.length,
        assigned_permissions: permissions.value.filter(p => p.user_count > 0).length,
        total_users_with_permissions: permissions.value.reduce((sum, p) => sum + (p.user_count || 0), 0),
        most_assigned_permission: 'N/A'
      }
    }

  } catch (error) {
    console.error('Error loading permissions:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load permissions',
      life: 4000
    })
  } finally {
    loading.value = false
  }
}

const openCreateDialog = () => {
  dialogMode.value = 'create'
  selectedPermission.value = null

  // Reset form
  Object.assign(permissionForm, {
    name: '',
    description: ''
  })

  permissionFormErrors.value = {}
  showPermissionDialog.value = true
}

const openEditDialog = (permission) => {
  dialogMode.value = 'edit'
  selectedPermission.value = permission

  // Populate form
  Object.assign(permissionForm, {
    name: permission.name,
    description: permission.description || ''
  })

  permissionFormErrors.value = {}
  showPermissionDialog.value = true
}

const closePermissionDialog = () => {
  showPermissionDialog.value = false
  selectedPermission.value = null
  permissionFormErrors.value = {}

  // Reset form
  Object.assign(permissionForm, {
    name: '',
    description: ''
  })
}

const submitPermissionForm = async () => {
  submitting.value = true

  try {
    let response

    if (dialogMode.value === 'create') {
      response = await axios.post('/api/configuration/permissions', permissionForm)
    } else {
      response = await axios.put(`/api/configuration/permissions/${selectedPermission.value.id}`, permissionForm)
    }

    if (response.data.success !== false) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: response.data.message || 'Permission saved successfully',
        life: 4000
      })

      closePermissionDialog()
      await loadPermissions()
    }
  } catch (error) {
    console.error('Error submitting form:', error)

    if (error.response?.data?.errors) {
      permissionFormErrors.value = error.response.data.errors
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: error.response?.data?.message || 'Failed to save permission',
        life: 4000
      })
    }
  } finally {
    submitting.value = false
  }
}

const openAssignDialog = async (permission) => {
  selectedPermission.value = permission
  userSearchQuery.value = ''
  availableUsers.value = []
  assignedUsers.value = []

  await loadUsersForPermission()
  showAssignDialog.value = true
}

const closeAssignDialog = () => {
  showAssignDialog.value = false
  selectedPermission.value = null
  availableUsers.value = []
  assignedUsers.value = []
  userSearchQuery.value = ''
}

const loadUsersForPermission = async () => {
  try {
    // Load all users (you might want to add pagination for large user bases)
    const usersResponse = await axios.get('/api/configuration/caisse-approval/users', {
      params: { per_page: 1000, search: userSearchQuery.value }
    })

    if (usersResponse.data.data) {
      const allUsers = usersResponse.data.data

      // Load users who already have this permission
      const assignedResponse = await axios.get('/api/configuration/permissions/users-with-permission', {
        params: { permission_name: selectedPermission.value.name }
      })

      const assignedUserIds = assignedResponse.data.users?.map(u => u.id) || []

      availableUsers.value = allUsers.filter(user => !assignedUserIds.includes(user.id))
      assignedUsers.value = assignedResponse.data.users || []
    }
  } catch (error) {
    console.error('Error loading users:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load users',
      life: 4000
    })
  }
}

const searchUsers = async () => {
  await loadUsersForPermission()
}

const assignPermissionToUser = async (user) => {
  assigningUsers.value.push(user.id)

  try {
    const response = await axios.post('/api/configuration/permissions/assign', {
      user_id: user.id,
      permission_name: selectedPermission.value.name
    })

    if (response.data.success !== false) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: response.data.message || 'Permission assigned successfully',
        life: 3000
      })

      // Move user from available to assigned
      availableUsers.value = availableUsers.value.filter(u => u.id !== user.id)
      assignedUsers.value.push(user)

      // Update permission count
      selectedPermission.value.user_count = (selectedPermission.value.user_count || 0) + 1
    }
  } catch (error) {
    console.error('Error assigning permission:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to assign permission',
      life: 4000
    })
  } finally {
    assigningUsers.value = assigningUsers.value.filter(id => id !== user.id)
  }
}

const revokePermissionFromUser = async (user) => {
  assigningUsers.value.push(user.id)

  try {
    const response = await axios.post('/api/configuration/permissions/revoke', {
      user_id: user.id,
      permission_name: selectedPermission.value.name
    })

    if (response.data.success !== false) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: response.data.message || 'Permission revoked successfully',
        life: 3000
      })

      // Move user from assigned to available
      assignedUsers.value = assignedUsers.value.filter(u => u.id !== user.id)
      availableUsers.value.push(user)

      // Update permission count
      selectedPermission.value.user_count = Math.max((selectedPermission.value.user_count || 0) - 1, 0)
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
    assigningUsers.value = assigningUsers.value.filter(id => id !== user.id)
  }
}

const viewPermissionDetails = async (permission) => {
  selectedPermission.value = permission

  try {
    // Load assigned users for details
    const response = await axios.get('/api/configuration/permissions/users-with-permission', {
      params: { permission_name: permission.name }
    })

    assignedUsers.value = response.data.users || []
    showDetailsDialog.value = true
  } catch (error) {
    console.error('Error loading permission details:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load permission details',
      life: 4000
    })
  }
}

const confirmDelete = (permission) => {
  confirm.require({
    message: `Are you sure you want to delete the permission "${permission.name}"?`,
    header: 'Delete Confirmation',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    acceptLabel: 'Delete',
    rejectLabel: 'Cancel',
    accept: () => deletePermission(permission)
  })
}

const deletePermission = async (permission) => {
  try {
    const response = await axios.delete(`/api/configuration/permissions/${permission.id}`)

    if (response.data.success !== false) {
      toast.add({
        severity: 'success',
        summary: 'Deleted',
        detail: response.data.message || 'Permission deleted successfully',
        life: 4000
      })

      await loadPermissions()
    }
  } catch (error) {
    console.error('Error deleting permission:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to delete permission',
      life: 4000
    })
  }
}

const onPage = (event) => {
  lazyParams.value.first = event.first
  lazyParams.value.rows = event.rows
  lazyParams.value.page = event.page + 1
  loadPermissions()
}

const onSort = (event) => {
  lazyParams.value.sortField = event.sortField
  lazyParams.value.sortOrder = event.sortOrder
  loadPermissions()
}

const refreshData = async () => {
  await loadPermissions()
}

// Watch for search changes
watch(searchQuery, () => {
  // Reset pagination when searching
  lazyParams.value.first = 0
  lazyParams.value.page = 1
})

// Lifecycle
onMounted(() => {
  loadPermissions()
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
</style>
