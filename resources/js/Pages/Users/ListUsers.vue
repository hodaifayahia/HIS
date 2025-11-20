<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import axios from 'axios';
import { useToastr } from '../../Components/toster';
import UserListItem from './UserListItem.vue';
import AddUserComponent from '@/Components/AddUserComponent.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Avatar from 'primevue/avatar';
import Badge from 'primevue/badge';
import Tag from 'primevue/tag';
import ProgressSpinner from 'primevue/progressspinner';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';
import TooltipDirective from 'primevue/tooltip';

// Register directive for template
const vTooltip = TooltipDirective;

const users = ref([]);
const pagination = ref({});
const selectedUser = ref({ name: '', email: '', phone: '', password: '', fichenavatte_max: 0, salary: 0 });
const isModalOpen = ref(false);
const toaster = useToastr();
const searchQuery = ref('');
const isLoading = ref(false);
const selectedUserBox = ref([]);
const loading = ref(false);
const file = ref(null);
const errorMessage = ref('');
const successMessage = ref('');
const fileInput = ref(null);
const confirm = useConfirm();

// Fetch users from the server
const getUsers = async (page = 1) => {
  try {
    const response = await axios.get(`/api/users?page=${page}`);
    users.value = response.data.data;
    pagination.value = response.data.meta;
    selectAll.value = false;
    selectAllUsers.value = [];
  } catch (error) {
    toaster.error('Failed to fetch users');
    console.error('Error fetching users:', error);
  }
};

const exportUsers = async () => {
  try {
    const response = await axios.get('/api/export/users', {
      responseType: 'blob',
    });
    const blob = new Blob([response.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
    const downloadUrl = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.download = 'users.xlsx';
    document.body.appendChild(link);
    link.click();
    link.remove();
  } catch (error) {
    console.error('Failed to export users:', error);
    toaster.error('Failed to export users');
  }
};

const handleFileChange = (event) => {
  file.value = event.target.files[0];
  errorMessage.value = '';
  successMessage.value = '';
};

const uploadFile = async () => {
  if (!file.value) {
    errorMessage.value = 'Please select a file.';
    return;
  }
  const allowedTypes = ['text/csv', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
  if (!allowedTypes.includes(file.value.type)) {
    errorMessage.value = 'Please upload a CSV or XLSX file.';
    return;
  }

  const formData = new FormData();
  formData.append('file', file.value);

  try {
    loading.value = true;
    const response = await axios.post('/api/import/users', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
    });

    if (response.data.success) {
      successMessage.value = response.data.message;
      refreshUsers();
      toaster.success('Users imported successfully!');
      errorMessage.value = '';
    } else {
      errorMessage.value = response.data.message;
      successMessage.value = '';
    }
  } catch (error) {
    console.error('Import error:', error);
    errorMessage.value = error.response?.data?.message || 'An error occurred during the file import.';
    successMessage.value = '';
  } finally {
    loading.value = false;
    if (fileInput.value) {
      fileInput.value.value = '';
    }
  }
};

const addNewUser = () => {
  selectedUser.value = { name: '', email: '', phone: '', fichenavatte_max: 0, salary: 0 };
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
};

const refreshUsers = () => {
  getUsers();
};

const debouncedSearch = (() => {
  let timeout;
  return () => {
    clearTimeout(timeout);
    timeout = setTimeout(async () => {
      try {
        isLoading.value = true;
        const response = await axios.get('/api/users/search', {
          params: { query: searchQuery.value },
        });
        users.value = response.data.data;
        pagination.value = response.data.meta;
      } catch (error) {
        toaster.error('Failed to search users');
        console.error('Error searching users:', error);
      } finally {
        isLoading.value = false;
      }
    }, 300);
  };
})();

watch(searchQuery, debouncedSearch);

const deleteUser = (user) => {
  confirm.require({
    message: `Are you sure you want to delete ${user.name}?`,
    header: 'Confirm',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      try {
        await axios.delete(`/api/users/${user.id}`);
        toaster.success('User deleted successfully!');
        refreshUsers();
      } catch (error) {
        toaster.error('Failed to delete user');
        console.error('Error deleting user:', error);
      }
    },
  });
};

const bulkDelete = async () => {
  confirm.require({
    message: `Delete ${selectedUserBox.value.length} selected users?`,
    header: 'Confirm Bulk Delete',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      try {
        await axios.delete('/api/users', {
          params: { ids: selectedUserBox.value },
        });
        toaster.success('Users deleted successfully!');
        selectedUserBox.value = [];
        selectAll.value = false;
        getUsers();
      } catch (error) {
        toaster.error('Failed to delete users');
        console.error('Error deleting users:', error);
      }
    },
  });
};

const selectAll = ref(false);

const selectAllUsers = () => {
  if (selectAll.value) {
    selectedUserBox.value = users.value.map(user => user.id);
  } else {
    selectedUserBox.value = [];
  }
};

const getRoleColor = (role) => {
  const roleColors = {
    admin: 'info',
    doctor: 'success',
    receptionist: 'warning',
    patient: 'primary',
    nurse: 'danger',
  };
  return roleColors[role?.toLowerCase()] || 'secondary';
};

const getInitials = (name) => {
  return name
    ?.split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase() || 'U';
};

const selectedRole = ref(null);

const getRelativeTime = (date) => {
  if (!date) return '';
  const now = new Date();
  const past = new Date(date);
  const diffInSeconds = Math.floor((now - past) / 1000);

  if (diffInSeconds < 60) return 'Just now';
  if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
  if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
  if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)}d ago`;
  return formatDateOnly(date);
};

const formatDateOnly = (date) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

const filteredUsers = computed(() => {
  return users.value.filter(user => {
    const matchesSearch = !searchQuery.value || 
      user.name?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      user.email?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      user.phone?.includes(searchQuery.value);
    
    const matchesRole = !selectedRole.value || user.role === selectedRole.value;
    
    return matchesSearch && matchesRole;
  });
});

const setQuickFilter = (role) => {
  selectedRole.value = selectedRole.value === role ? null : role;
};

const clearSearch = () => {
  searchQuery.value = '';
};

const clearAllFilters = () => {
  searchQuery.value = '';
  selectedRole.value = null;
};

const editUser = (user) => {
  selectedUser.value = user;
  isModalOpen.value = true;
};

const viewUser = (user) => {
  selectedUser.value = user;
  isModalOpen.value = true;
};

onMounted(() => {
  getUsers();
});
</script>

<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-slate-50 tw-via-blue-50/30 tw-to-indigo-50/20">
    <!-- Enhanced Header with Gradient Background -->
    <div class="tw-bg-gradient-to-r tw-from-white tw-via-blue-50/50 tw-to-indigo-50/30 tw-border-b tw-border-slate-200/60 tw-sticky tw-top-0 tw-z-10 tw-shadow-lg tw-backdrop-blur-sm">
      <div class="tw-px-6 tw-py-6">
        <div class="tw-flex tw-flex-col lg:tw-flex-row tw-justify-between tw-items-start lg:tw-items-center tw-gap-6">
          <div class="tw-flex tw-items-center tw-gap-4">
            <div class="tw-relative">
              <div class="tw-bg-gradient-to-br tw-from-indigo-500 tw-to-purple-600 tw-p-3 tw-rounded-xl tw-shadow-lg">
                <i class="pi pi-users tw-text-white tw-text-2xl"></i>
              </div>
              <div class="tw-absolute -tw-top-1 -tw-right-1 tw-w-4 tw-h-4 tw-bg-green-400 tw-rounded-full tw-border-2 tw-border-white tw-animate-pulse"></div>
            </div>
            <div>
              <h1 class="tw-text-3xl tw-font-bold tw-bg-gradient-to-r tw-from-slate-900 tw-to-slate-700 tw-bg-clip-text tw-text-transparent">
                User Management
              </h1>
              <p class="tw-text-slate-600 tw-text-sm tw-mt-1 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-info-circle tw-text-blue-500"></i>
                Manage system users and administrators
              </p>
            </div>
          </div>

          <!-- Enhanced Stats Cards -->
          <div class="tw-flex tw-flex-wrap tw-gap-4">
            <div class="tw-bg-gradient-to-br tw-from-blue-50 tw-to-cyan-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-blue-200/60 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-relative">
                  <div class="tw-bg-blue-100 tw-p-2 tw-rounded-lg">
                    <i class="pi pi-users tw-text-blue-600 tw-text-lg"></i>
                  </div>
                  <div class="tw-absolute -tw-top-1 -tw-right-1 tw-w-3 tw-h-3 tw-bg-blue-400 tw-rounded-full tw-border tw-border-white"></div>
                </div>
                <div>
                  <div class="tw-text-xs tw-text-blue-700 tw-font-medium tw-uppercase tw-tracking-wide">Total Users</div>
                  <div class="tw-text-2xl tw-font-bold tw-text-blue-800">
                    {{ users.length }}
                  </div>
                </div>
              </div>
            </div>

            <div class="tw-bg-gradient-to-br tw-from-purple-50 tw-to-pink-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-purple-200/60 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-relative">
                  <div class="tw-bg-purple-100 tw-p-2 tw-rounded-lg">
                    <i class="pi pi-shield tw-text-purple-600 tw-text-lg"></i>
                  </div>
                  <div class="tw-absolute -tw-top-1 -tw-right-1 tw-w-3 tw-h-3 tw-bg-purple-400 tw-rounded-full tw-border tw-border-white"></div>
                </div>
                <div>
                  <div class="tw-text-xs tw-text-purple-700 tw-font-medium tw-uppercase tw-tracking-wide">Admins</div>
                  <div class="tw-text-2xl tw-font-bold tw-text-purple-800">
                    {{ users.filter(u => u.role === 'admin').length }}
                  </div>
                </div>
              </div>
            </div>

            <div class="tw-bg-gradient-to-br tw-from-green-50 tw-to-emerald-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-green-200/60 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-relative">
                  <div class="tw-bg-green-100 tw-p-2 tw-rounded-lg">
                    <i class="pi pi-check-circle tw-text-green-600 tw-text-lg"></i>
                  </div>
                  <div class="tw-absolute -tw-top-1 -tw-right-1 tw-w-3 tw-h-3 tw-bg-green-400 tw-rounded-full tw-border tw-border-white"></div>
                </div>
                <div>
                  <div class="tw-text-xs tw-text-green-700 tw-font-medium tw-uppercase tw-tracking-wide">Doctors</div>
                  <div class="tw-text-2xl tw-font-bold tw-text-green-800">
                    {{ users.filter(u => u.role === 'doctor').length }}
                  </div>
                </div>
              </div>
            </div>

            <div class="tw-bg-gradient-to-br tw-from-amber-50 tw-to-orange-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-amber-200/60 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-bg-amber-100 tw-p-2 tw-rounded-lg">
                  <i class="pi pi-chart-line tw-text-amber-600 tw-text-lg"></i>
                </div>
                <div>
                  <div class="tw-text-xs tw-text-amber-700 tw-font-medium tw-uppercase tw-tracking-wide">Staff</div>
                  <div class="tw-text-2xl tw-font-bold tw-text-amber-800">
                    {{ users.filter(u => ['receptionist', 'nurse'].includes(u.role)).length }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="tw-px-6 tw-py-6">
      <!-- Enhanced Action Toolbar -->
      <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-border tw-border-slate-200/60 tw-p-6 tw-mb-8 tw-backdrop-blur-sm">
        <div class="tw-flex tw-flex-col xl:tw-flex-row tw-justify-between tw-items-start xl:tw-items-center tw-gap-6">
          <!-- Enhanced Filters Section -->
          <div class="tw-flex tw-flex-col sm:tw-flex-row tw-gap-4 tw-flex-1">
            <!-- Search with Enhanced Design -->
            <div class="tw-relative tw-flex-1 tw-min-w-[250px] tw-max-w-[400px]">
              <div class="tw-absolute tw-inset-y-0 tw-left-0 tw-pl-4 tw-flex tw-items-center tw-pointer-events-none">
                <i class="pi pi-search tw-text-slate-400 tw-text-lg"></i>
              </div>
              <InputText
                v-model="searchQuery"
                placeholder="Search by name, email, phone..."
                class="tw-w-full tw-pl-12 tw-pr-4 tw-py-3 tw-border tw-border-slate-200 tw-rounded-xl focus:tw-border-blue-500 focus:tw-ring-2 focus:tw-ring-blue-500/20 focus:tw-outline-none tw-transition-all tw-duration-200 tw-bg-slate-50/50 hover:tw-bg-white"
              />
              <div v-if="searchQuery" class="tw-absolute tw-inset-y-0 tw-right-0 tw-pr-4 tw-flex tw-items-center">
                <Button
                  @click="clearSearch"
                  icon="pi pi-times"
                  class="p-button-text p-button-sm p-button-rounded tw-text-slate-400 hover:tw-text-slate-600"
                  v-tooltip.top="'Clear search'"
                />
              </div>
            </div>

            <!-- Quick Filter Buttons -->
            <div class="tw-flex tw-gap-2">
              <Button
                @click="setQuickFilter('admin')"
                :class="selectedRole === 'admin' ? 'p-button-primary' : 'p-button-outlined p-button-secondary'"
                class="p-button-sm tw-rounded-xl"
                label="Admin"
              />
              <Button
                @click="setQuickFilter('doctor')"
                :class="selectedRole === 'doctor' ? 'p-button-primary' : 'p-button-outlined p-button-secondary'"
                class="p-button-sm tw-rounded-xl"
                label="Doctor"
              />
              <Button
                @click="setQuickFilter('receptionist')"
                :class="selectedRole === 'receptionist' ? 'p-button-primary' : 'p-button-outlined p-button-secondary'"
                class="p-button-sm tw-rounded-xl"
                label="Reception"
              />
            </div>
          </div>

          <!-- Enhanced Action Buttons -->
          <div class="tw-flex tw-flex-wrap tw-gap-3">
            <Button 
              @click="refreshUsers"
              icon="pi pi-refresh"
              class="p-button-outlined p-button-secondary p-button-lg tw-rounded-xl hover:tw-shadow-md tw-transition-all tw-duration-200"
              v-tooltip.top="'Refresh data'"
              :loading="isLoading"
            />
            <Button 
              @click="exportUsers"
              icon="pi pi-download"
              label="Export"
              class="p-button-outlined p-button-info p-button-lg tw-rounded-xl hover:tw-shadow-md tw-transition-all tw-duration-200"
            />
            <Button 
              @click="addNewUser"
              icon="pi pi-plus"
              label="New User"
              class="p-button-primary p-button-lg tw-rounded-xl tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-200 tw-transform hover:tw-scale-105"
            />
          </div>
        </div>

        <!-- Import Section -->
        <div class="tw-mt-6 tw-pt-6 tw-border-t tw-border-slate-200">
          <div class="tw-flex tw-flex-col md:tw-flex-row tw-gap-4 tw-items-center">
            <div class="tw-relative tw-flex-1 tw-max-w-xs">
              <label for="fileUpload" class="tw-block tw-w-full tw-px-4 tw-py-2.5 tw-text-center tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50 tw-border-2 tw-border-dashed tw-border-indigo-300 tw-rounded-lg tw-cursor-pointer hover:tw-bg-indigo-50 tw-transition-colors tw-duration-200">
                <i class="pi pi-upload tw-mr-2"></i>
                <span class="tw-text-sm tw-font-medium tw-text-slate-700">{{ file ? file.name : 'Choose CSV or XLSX' }}</span>
              </label>
              <input ref="fileInput" type="file" accept=".csv,.xlsx" @change="handleFileChange" id="fileUpload" class="tw-hidden" />
            </div>

            <Button
              label="Import Users"
              icon="pi pi-check"
              @click="uploadFile"
              :loading="loading"
              :disabled="loading || !file"
              class="p-button-success tw-rounded-lg tw-font-semibold"
              v-tooltip.top="'Upload users from file'"
            />

            <div v-if="successMessage" class="tw-flex-1 tw-bg-green-50 tw-text-green-800 tw-p-3 tw-rounded-lg tw-text-sm tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-check-circle"></i>{{ successMessage }}
            </div>
            <div v-if="errorMessage" class="tw-flex-1 tw-bg-red-50 tw-text-red-800 tw-p-3 tw-rounded-lg tw-text-sm tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-exclamation-circle"></i>{{ errorMessage }}
            </div>
          </div>
        </div>

        <!-- Active Filters Display -->
        <div v-if="searchQuery || selectedRole" class="tw-mt-4 tw-flex tw-flex-wrap tw-items-center tw-gap-2">
          <span class="tw-text-sm tw-text-slate-600 tw-font-medium">Active filters:</span>
          <Tag v-if="searchQuery" 
               :value="`Search: ${searchQuery}`" 
               severity="info" 
               class="tw-rounded-lg"
               @click="clearSearch" />
          <Tag v-if="selectedRole" 
               :value="`Role: ${selectedRole}`" 
               severity="info" 
               class="tw-rounded-lg"
               @click="selectedRole = null" />
          <Button @click="clearAllFilters" 
                  label="Clear all" 
                  class="p-button-text p-button-sm tw-text-slate-500 hover:tw-text-slate-700" />
        </div>
      </div>

      <!-- Enhanced Data Table -->
      <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-border tw-border-slate-200/60 tw-overflow-hidden tw-backdrop-blur-sm">
        <DataTable 
          v-model:selection="selectedUserBox"
          :value="filteredUsers"
          :paginator="true"
          :rows="10"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords} users"
          :rowsPerPageOptions="[10, 25, 50]"
          dataKey="id"
          :loading="isLoading"
          selectionMode="multiple"
          responsiveLayout="scroll"
          class="p-datatable-sm medical-table"
        >
          <!-- Selection Column -->
          <Column selectionMode="multiple" headerStyle="width: 3rem" />

          <!-- Name Column with Avatar -->
          <Column field="name" header="User" :sortable="true">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-3">
                <Avatar
                  :label="getInitials(data.name)"
                  class="tw-bg-gradient-to-br tw-from-indigo-500 tw-to-purple-600 tw-text-white tw-font-semibold tw-shadow-md"
                  size="normal"
                  shape="circle"
                />
                <div>
                  <div class="tw-font-semibold tw-text-slate-900">{{ data.name }}</div>
                  <div class="tw-text-xs tw-text-slate-500">ID: {{ data.id }}</div>
                </div>
              </div>
            </template>
          </Column>

          <!-- Email Column -->
          <Column field="email" header="Email" :sortable="true">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-envelope tw-text-slate-400 tw-text-sm"></i>
                <span class="tw-text-slate-700">{{ data.email || 'N/A' }}</span>
              </div>
            </template>
          </Column>

          <!-- Phone Column -->
          <Column field="phone" header="Phone" :sortable="true">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-phone tw-text-slate-400 tw-text-sm"></i>
                <span class="tw-text-slate-700">{{ data.phone || 'N/A' }}</span>
              </div>
            </template>
          </Column>

          <!-- Role Column -->
          <Column field="role" header="Role" :sortable="true">
            <template #body="{ data }">
              <Badge
                :value="data.role?.toUpperCase() || 'USER'"
                :severity="getRoleColor(data.role)"
                class="tw-font-semibold tw-rounded-lg"
              />
            </template>
          </Column>

          <!-- Salary Column -->
          <Column field="salary" header="Salary" :sortable="true">
            <template #body="{ data }">
              <div class="tw-text-slate-900 tw-font-medium">
                {{ data.salary ? `$${parseFloat(data.salary).toFixed(2)}` : 'N/A' }}
              </div>
            </template>
          </Column>

          <!-- Max FicheNavette Column -->
          <Column field="fichenavatte_max" header="Max Consultations" :sortable="true">
            <template #body="{ data }">
              <div class="tw-text-slate-900 tw-font-medium">
                {{ data.fichenavatte_max || 'Unlimited' }}
              </div>
            </template>
          </Column>

          <!-- Created At Column -->
          <Column field="created_at" header="Created" :sortable="true">
            <template #body="{ data }">
              <div class="tw-text-sm">
                <div class="tw-font-semibold tw-text-slate-900">{{ formatDateOnly(data.created_at) }}</div>
                <div class="tw-text-xs tw-text-slate-500">{{ getRelativeTime(data.created_at) }}</div>
              </div>
            </template>
          </Column>

          <!-- Actions Column -->
          <Column header="Actions" headerStyle="width: 12rem">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-2">
                <Button
                  icon="pi pi-pencil"
                  class="p-button-rounded p-button-text p-button-primary p-button-sm"
                  @click="editUser(data)"
                  v-tooltip.top="'Edit user'"
                />
                <Button
                  icon="pi pi-eye"
                  class="p-button-rounded p-button-text p-button-info p-button-sm"
                  @click="viewUser(data)"
                  v-tooltip.top="'View details'"
                />
                <Button
                  icon="pi pi-trash"
                  class="p-button-rounded p-button-text p-button-danger p-button-sm"
                  @click="deleteUser(data)"
                  v-tooltip.top="'Delete user'"
                />
              </div>
            </template>
          </Column>

          <!-- Empty State -->
          <template #empty>
            <div class="tw-text-center tw-py-16 tw-px-8">
              <div class="tw-bg-gradient-to-br tw-from-slate-100 tw-to-slate-200 tw-w-24 tw-h-24 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-6">
                <i class="pi pi-users tw-text-4xl tw-text-slate-400"></i>
              </div>
              <h3 class="tw-text-xl tw-font-semibold tw-text-slate-900 tw-mb-2">No users found</h3>
              <p class="tw-text-slate-500 tw-mb-6 tw-max-w-md tw-mx-auto">No users match your search criteria. Create a new user to get started.</p>
              <Button
                label="Add First User"
                icon="pi pi-plus"
                @click="openModal"
                class="p-button-success tw-rounded-xl tw-px-8 tw-py-3 tw-font-semibold tw-shadow-lg hover:tw-shadow-xl"
              />
            </div>
          </template>

          <!-- Loading State -->
          <template #loading>
            <div class="tw-text-center tw-py-16 tw-px-8">
              <div class="tw-bg-gradient-to-br tw-from-indigo-100 tw-to-purple-100 tw-w-20 tw-h-20 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-6">
                <ProgressSpinner
                  class="tw-w-8 tw-h-8"
                  strokeWidth="4"
                  fill="transparent"
                  animationDuration="1.5s"
                />
              </div>
              <h3 class="tw-text-lg tw-font-semibold tw-text-slate-900 tw-mb-2">Loading users...</h3>
              <p class="tw-text-slate-500">Please wait while we fetch the user data</p>
            </div>
          </template>
        </DataTable>

        <!-- Bulk Actions -->
        <div v-if="selectedUserBox.length > 0" class="tw-bg-blue-50 tw-border-t tw-border-blue-200 tw-p-4 tw-flex tw-items-center tw-justify-between">
          <div class="tw-flex tw-items-center tw-gap-3">
            <i class="pi pi-info-circle tw-text-blue-600 tw-text-xl"></i>
            <span class="tw-text-blue-900 tw-font-medium">{{ selectedUserBox.length }} user(s) selected</span>
          </div>
          <Button
            label="Delete Selected"
            icon="pi pi-trash"
            severity="danger"
            @click="bulkDelete"
            class="tw-rounded-lg tw-font-semibold"
            v-tooltip.top="'Delete selected users'"
          />
        </div>
      </div>
    </div>

    <!-- Add/Edit User Modal -->
    <AddUserComponent :show-modal="isModalOpen" :user-data="selectedUser" @close="closeModal" @user-updated="refreshUsers" />

    <!-- Confirmation Dialog -->
    <ConfirmDialog />

    <!-- Toast -->
    <Toast />

    <!-- Floating Action Button -->
    <button 
      @click="addNewUser"
      class="fab tw-shadow-xl"
      v-tooltip.top="'Create New User'"
    >
      <i class="pi pi-plus tw-text-xl"></i>
    </button>
  </div>
</template>

<style scoped>
.users-table :deep(.p-datatable) {
  background: transparent;
  border: none;
}

.users-table :deep(.p-datatable .p-datatable-thead > tr > th) {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-weight: 600;
  border: none;
  padding: 1rem;
  font-size: 0.875rem;
}

.users-table :deep(.p-datatable .p-datatable-tbody > tr) {
  border-bottom: 1px solid rgba(15, 23, 42, 0.1);
  transition: all 0.3s ease;
}

.users-table :deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background-color: rgba(99, 102, 241, 0.05);
  box-shadow: inset 0 0 12px rgba(99, 102, 241, 0.1);
}

.users-table :deep(.p-datatable .p-datatable-tbody > tr > td) {
  padding: 1rem;
  border: none;
}

.users-table :deep(.p-paginator) {
  background: transparent;
  border: none;
  padding: 1.5rem 0;
}

.users-table :deep(.p-paginator .p-paginator-current) {
  color: rgb(71, 85, 105);
  font-weight: 500;
}

.users-table :deep(.p-paginator .p-paginator-page) {
  color: rgb(71, 85, 105);
  border-radius: 6px;
  margin: 0 2px;
}

.users-table :deep(.p-paginator .p-paginator-page.p-highlight) {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.users-table :deep(.p-paginator .p-paginator-page:hover) {
  background-color: rgba(99, 102, 241, 0.1);
  border-radius: 6px;
}

.users-table :deep(.p-paginator-bottom) {
  background-color: rgba(15, 23, 42, 0.02);
  border-top: 1px solid rgba(15, 23, 42, 0.1);
  margin: 0 -1.5rem -1.5rem -1.5rem;
  padding: 1.5rem;
}
</style>
