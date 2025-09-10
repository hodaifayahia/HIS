<script setup>
import { ref, onMounted, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import axios from 'axios';
import ApproverRemiseService from '../../../Components/Apps/services/Remise/ApproverRemiseService.js';
import { userService } from '../../../Components/Apps/services/User/userService.js';
import { useAuthStore } from '../../../stores/auth.js';
// PrimeVue Components
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import MultiSelect from 'primevue/multiselect';
import Avatar from 'primevue/avatar';
import Badge from 'primevue/badge';
import Card from 'primevue/card';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import ToggleButton from 'primevue/togglebutton';
import Textarea from 'primevue/textarea';
import Skeleton from 'primevue/skeleton';
import Tag from 'primevue/tag';
import Toolbar from 'primevue/toolbar';
import Divider from 'primevue/divider';
import ProgressSpinner from 'primevue/progressspinner';

// Composables
const toast = useToast();
const confirm = useConfirm();
const authStore = useAuthStore(); // Initialize auth store

// Reactive data
const approvers = ref([]);
const selectedApprovers = ref([]);
const loading = ref(false);
const savingApprovers = ref(false);
const totalRecords = ref(0);
const first = ref(0);
const rows = ref(10);
const searchQuery = ref('');
const approvalFilter = ref(null);

// Modals
const showAddModal = ref(false);
const showEditModal = ref(false);
const showCommentsModal = ref(false);

// Form data
const newApprover = ref({
  user_id: null,
  approver_ids: [],
  is_approved: false,
  comments: ''
});

const editingApprover = ref({});
const editingComments = ref('');

// Dropdown options
const allUsers = ref([]);
const approverOptions = ref([]);
const approvalOptions = ref([
  { label: 'All Statuses', value: null },
  { label: 'Approved', value: true },
  { label: 'Pending', value: false }
]);

// Computed
const currentUser = computed(() => {
  if (!authStore.user) return null;
  // if store.payload contains nested user.data
  if (authStore.user.user?.data) return authStore.user.user.data;
  if (authStore.user.data) return authStore.user.data;
  // fallback if store already stores flat user
  return authStore.user;
});

const currentUserId = computed(() => {
  return currentUser.value?.id ?? null;
});

const isFormValid = computed(() => {
  return newApprover.value.user_id && 
         newApprover.value.approver_ids && 
         newApprover.value.approver_ids.length > 0;
});

const filteredApproverOptions = computed(() => {
  // Filter out the current user from approver options
  return approverOptions.value.filter(option => option.value !== currentUserId.value);
});

// Methods
const loadApprovers = async (event = null) => {
  loading.value = true;
  try {
    const page = event ? Math.floor(event.first / event.rows) + 1 : 1;
    const params = {
      page,
      per_page: event?.rows || rows.value,
      q: searchQuery.value || undefined,
      is_approved: approvalFilter.value
    };

    console.log('Loading approvers with params:', params);
    const response = await ApproverRemiseService.index(params);
    console.log('Approvers response:', response);
    
    if (response.success) {
      // Handle both paginated and non-paginated responses
      if (response.data && response.data.data) {
        // Paginated response
        approvers.value = response.data.data;
        totalRecords.value = response.data.total || 0;
      } else if (Array.isArray(response.data)) {
        // Direct array response
        approvers.value = response.data;
        totalRecords.value = response.data.length;
      } else {
        approvers.value = [];
        totalRecords.value = 0;
      }
      
      if (event) {
        first.value = event.first;
        rows.value = event.rows;
      }
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to load approvers',
        life: 3000
      });
    }
  } catch (error) {
    console.error('Load approvers error:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load approvers',
      life: 3000
    });
  } finally {
    loading.value = false;
  }
};

const initializeUser = async () => {
  try {
    // load user if not present
    if (!authStore.user || !(authStore.user.user?.data || authStore.user.data || authStore.user.id)) {
      await authStore.getUser();
    }

    // set user_id for new approaches
    if (currentUserId.value) {
      newApprover.value.user_id = currentUserId.value;
    }

    console.log('Current user from auth store:', authStore.user);
  } catch (error) {
    console.error('Initialize user error:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load current user information',
      life: 3000
    });
  }
};

const loadUsers = async () => {
  try {
    console.log('Loading users...');
    const response = await userService.getAll();
    console.log('Users response:', response);
    
    if (response.success) {
      allUsers.value = response.data;
      approverOptions.value = response.data.map(user => ({
        label: `${user.name} (${user.email})`,
        value: user.id,
        user: user
      }));
      console.log('Approver options:', approverOptions.value);
    } else {
      toast.add({
        severity: 'warn',
        summary: 'Warning',
        detail: response.message || 'Could not load users',
        life: 3000
      });
    }
  } catch (error) {
    console.error('Load users error:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load users',
      life: 3000
    });
  }
};

const toggleApproval = async (approver) => {
  try {
    const response = await ApproverRemiseService.toggle(approver.id);
    
    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: `Approval ${approver.is_approved ? 'revoked' : 'granted'} successfully`,
        life: 3000
      });
      
      // Update the local data
      const index = approvers.value.findIndex(a => a.id === approver.id);
      if (index !== -1) {
        approvers.value[index] = response.data;
      }
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to toggle approval',
        life: 3000
      });
    }
  } catch (error) {
    console.error('Toggle approval error:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to toggle approval',
      life: 3000
    });
  }
};

const openAddModal = () => {
  newApprover.value = {
    user_id: currentUserId.value,
    approver_ids: [],
    is_approved: false,
    comments: ''
  };
  showAddModal.value = true;
};

const saveApprover = async () => {
  if (!isFormValid.value) {
    toast.add({
      severity: 'warn',
      summary: 'Validation Error',
      detail: 'Please select at least one approver',
      life: 3000
    });
    return;
  }

  savingApprovers.value = true;
  try {
    console.log('Saving approvers:', newApprover.value);

    // Use single API that accepts approver_ids if available, otherwise fall back to multiple calls
    if (newApprover.value.approver_ids && newApprover.value.approver_ids.length > 1) {
      // Preferred: call backend endpoint that accepts approver_ids array
      const payload = {
        user_id: newApprover.value.user_id,
        approver_ids: newApprover.value.approver_ids,
        is_approved: newApprover.value.is_approved,
        comments: newApprover.value.comments
      };

      const response = await ApproverRemiseService.store(payload);
      if (response && response.success) {
        // response.data may be collection or array
        const created = Array.isArray(response.data) ? response.data : (response.data?.data ? response.data.data : (response.data ? (Array.isArray(response.data) ? response.data : [response.data]) : []));
        // prepend created items to local array
        if (created.length) {
          approvers.value = [...created, ...approvers.value];
          totalRecords.value += created.length;
        }

        toast.add({ severity: 'success', summary: 'Success', detail: `${created.length} approver(s) created`, life: 3000 });
      } else {
        toast.add({ severity: 'error', summary: 'Error', detail: response?.message || 'Failed to create approvers', life: 3000 });
      }
    } else {
      // fallback: create one-by-one and update local array on success
      const promises = newApprover.value.approver_ids.map(approverId => {
        const payload = {
          user_id: newApprover.value.user_id,
          approver_id: approverId,
          is_approved: newApprover.value.is_approved,
          comments: newApprover.value.comments
        };
        return ApproverRemiseService.store(payload);
      });

      const results = await Promise.allSettled(promises);
      const createdItems = [];
      const failed = [];

      results.forEach(r => {
        if (r.status === 'fulfilled' && r.value && r.value.success) {
          const res = r.value;
          const item = res.data?.data ?? res.data ?? res;
          createdItems.push(item);
        } else {
          failed.push(r);
        }
      });

      if (createdItems.length) {
        // flatten createdItems in case nested
        const flat = createdItems.flat();
        approvers.value = [...flat, ...approvers.value];
        totalRecords.value += flat.length;
        toast.add({ severity: 'success', summary: 'Success', detail: `${flat.length} approver(s) created`, life: 3000 });
      }
      if (failed.length) {
        toast.add({ severity: 'warn', summary: 'Partial', detail: `${failed.length} approver(s) failed`, life: 4000 });
      }
    }

    showAddModal.value = false;
    // do NOT reload from API, local array already updated
  } catch (error) {
    console.error('Save approver error:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to create approver(s)',
      life: 3000
    });
  } finally {
    savingApprovers.value = false;
  }
};

const openEditModal = (approver) => {
  editingApprover.value = { ...approver };
  showEditModal.value = true;
};

const updateApprover = async () => {
  loading.value = true;
  try {
    const response = await ApproverRemiseService.update(editingApprover.value.id, {
      is_approved: editingApprover.value.is_approved,
      comments: editingApprover.value.comments
    });
    
    if (response.success) {
      // pick returned item
      const updated = response.data?.data ?? response.data ?? response;
      // find and replace in local array
      const idx = approvers.value.findIndex(a => a.id === editingApprover.value.id);
      if (idx !== -1) {
        approvers.value.splice(idx, 1, updated);
      } else {
        // if not found, prepend
        approvers.value.unshift(updated);
        totalRecords.value += 1;
      }

      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Approver updated successfully',
        life: 3000
      });

      showEditModal.value = false;
      // do NOT reload API
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to update approver',
        life: 3000
      });
    }
  } catch (error) {
    console.error('Update approver error:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to update approver',
      life: 3000
    });
  } finally {
    loading.value = false;
  }
};

const openCommentsModal = (approver) => {
  editingApprover.value = { ...approver };
  editingComments.value = approver.comments || '';
  showCommentsModal.value = true;
};

const saveComments = async () => {
  loading.value = true;
  try {
    const response = await ApproverRemiseService.update(editingApprover.value.id, {
      is_approved: editingApprover.value.is_approved,
      comments: editingComments.value
    });

    if (response.success) {
      const updated = response.data?.data ?? response.data ?? response;
      const idx = approvers.value.findIndex(a => a.id === editingApprover.value.id);
      if (idx !== -1) {
        approvers.value.splice(idx, 1, updated);
      }

      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Comments updated successfully',
        life: 3000
      });

      showCommentsModal.value = false;
      // do NOT reload API
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to update comments',
        life: 3000
      });
    }
  } catch (error) {
    console.error('Save comments error:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to update comments',
      life: 3000
    });
  } finally {
    loading.value = false;
  }
};

const deleteApprover = (approver) => {
  confirm.require({
    message: `Are you sure you want to remove ${approver.approver?.name} as an approver?`,
    header: 'Confirm Deletion',
    icon: 'pi pi-exclamation-triangle',
    rejectClass: 'p-button-secondary p-button-outlined',
    rejectLabel: 'Cancel',
    acceptLabel: 'Delete',
    accept: async () => {
      try {
        const response = await ApproverRemiseService.destroy(approver.id);

        if (response.success) {
          // remove from local array
          const idx = approvers.value.findIndex(a => a.id === approver.id);
          if (idx !== -1) {
            approvers.value.splice(idx, 1);
            totalRecords.value = Math.max(0, totalRecords.value - 1);
          }

          toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Approver removed successfully',
            life: 3000
          });

          // do NOT reload API
        } else {
          toast.add({
            severity: 'error',
            summary: 'Error',
            detail: response.message || 'Failed to remove approver',
            life: 3000
          });
        }
      } catch (error) {
        console.error('Delete approver error:', error);
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to remove approver',
          life: 3000
        });
      }
    }
  });
};

const bulkApprove = () => {
  if (selectedApprovers.value.length === 0) {
    toast.add({
      severity: 'warn',
      summary: 'Warning',
      detail: 'Please select approvers first',
      life: 3000
    });
    return;
  }

  confirm.require({
    message: `Are you sure you want to approve ${selectedApprovers.value.length} approver(s)?`,
    header: 'Confirm Bulk Approval',
    icon: 'pi pi-check-circle',
    accept: async () => {
      try {
        const response = await ApproverRemiseService.bulkUpdate({
          approver_ids: selectedApprovers.value.map(a => a.id),
          is_approved: true
        });

        if (response.success) {
          // update local array: set is_approved true for matching ids
          const ids = new Set(selectedApprovers.value.map(a => a.id));
          approvers.value = approvers.value.map(a => ids.has(a.id) ? ({ ...a, is_approved: true }) : a);

          toast.add({
            severity: 'success',
            summary: 'Success',
            detail: `${response.updated_count || selectedApprovers.value.length} approver(s) approved successfully`,
            life: 3000
          });

          selectedApprovers.value = [];
          // do NOT reload API
        }
      } catch (error) {
        console.error('Bulk approve error:', error);
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to bulk approve',
          life: 3000
        });
      }
    }
  });
};

const getSeverity = (approved) => {
  return approved ? 'success' : 'warning';
};

const getStatusLabel = (approved) => {
  return approved ? 'Approved' : 'Pending';
};

// small debounce util (lightweight, avoids external deps)
const debounce = (fn, wait = 350) => {
  let t;
  return (...args) => {
    clearTimeout(t);
    t = setTimeout(() => fn(...args), wait);
  };
};

// replace immediate search calls with debounced handler
const onSearchInput = debounce(() => {
  first.value = 0;
  loadApprovers();
}, 350);

// use keyboard enter as before, but also trigger debounced on input
const handleSearch = () => {
  first.value = 0;
  loadApprovers();
};

const handleApprovalFilter = () => {
  first.value = 0;
  loadApprovers();
};

// Lifecycle
onMounted(async () => {
  await Promise.all([
    initializeUser(),
    loadUsers()
  ]);
  await loadApprovers();
});
</script>

<template>
  <div class="approver-remise-list">
    <Toast />
    <ConfirmDialog />
    
    <!-- Header Card -->
    <Card class="mb-4 header-card">
      <template #title>
        <div class="flex align-items-center gap-3">
          <i class="pi pi-users text-primary text-xl" aria-hidden="true"></i>
          <div>
            <div class="text-lg font-semibold">My Remise Approvers</div>
            <small class="text-500">Manage who can approve your remise requests</small>
          </div>
        </div>
      </template>
      
      <template #content>
        <p class="text-600 mb-0">
          Manage who can approve your remise requests. Add multiple approvers to handle your financial remise approvals.
        </p>
        <div v-if="currentUser" class="mt-3 p-3 bg-blue-50 border-round">
          <div class="flex align-items-center gap-3">
            <Avatar 
              :image="currentUser.avatar ? `/storage/${currentUser.avatar}` : null"
              :label="currentUser.name?.charAt(0).toUpperCase() || 'U'"
              shape="circle" 
              size="normal"
              class="bg-primary"
            />
            <div>
              <div class="font-semibold text-900">{{ currentUser.name || 'Loading...' }}</div>
              <div class="text-600 text-sm">{{ currentUser.email || '' }}</div>
              <Tag :value="currentUser.role || 'User'" severity="info" class="text-xs mt-1"></Tag>
            </div>
          </div>
        </div>
      </template>
    </Card>

    <!-- Toolbar -->
    <Toolbar class="mb-4 toolbar">
      <template #start>
        <div class="flex align-items-center gap-2">
          <Button 
            label="Add Approvers" 
            icon="pi pi-plus" 
            @click="openAddModal"
            class="p-button-primary"
            :disabled="!currentUserId"
          />
          
          <Button 
            label="Bulk Approve" 
            icon="pi pi-check" 
            @click="bulkApprove"
            :disabled="selectedApprovers.length === 0"
            class="p-button-success"
          />
          <Tag class="ml-2" :value="totalRecords" severity="info" />
        </div>
      </template>

      <template #end>
        <div class="flex align-items-center gap-3">
          <!-- Search -->
          <span class="p-input-icon-left search-wrap">
            <i class="pi pi-search" />
            <InputText 
              v-model="searchQuery"
              placeholder="Search approvers..."
              @input="onSearchInput"
              @keyup.enter="handleSearch"
              class="w-20rem"
              clearable
            />
            <Button icon="pi pi-times" class="p-button-text p-button-plain clear-btn" @click="() => { searchQuery=''; onSearchInput(); }" />
          </span>
          
          <!-- Filter -->
          <Dropdown
            v-model="approvalFilter"
            :options="approvalOptions"
            option-label="label"
            option-value="value"
            placeholder="Filter by status"
            @change="handleApprovalFilter"
            class="w-12rem"
            show-clear
          />
          
          <!-- Refresh -->
          <Button 
            icon="pi pi-refresh" 
            @click="loadApprovers"
            :loading="loading"
            class="p-button-outlined"
            aria-label="Refresh"
          />
        </div>
      </template>
    </Toolbar>

    <!-- Data Table -->
    <Card>
      <template #content>
        <DataTable 
          v-model:selection="selectedApprovers"
          :value="approvers"
          :loading="loading"
          :totalRecords="totalRecords"
          :rows="rows"
          :first="first"
          lazy
          paginator
          @page="loadApprovers"
          selection-mode="multiple"
          :meta-key-selection="false"
          class="p-datatable-gridlines enhanced-table"
          :rows-per-page-options="[10, 25, 50]"
          current-page-report-template="Showing {first} to {last} of {totalRecords} entries"
          responsive-layout="scroll"
        >
          <template #empty>
            <div class="text-center p-6 no-data-cta">
              <i class="pi pi-users text-5xl text-400 mb-3" />
              <h3 class="text-700 mb-2">No approvers yet</h3>
              <p class="text-500 mb-4">Add approvers who can approve your remise requests.</p>
              <Button label="Add Approvers" icon="pi pi-plus" class="p-button-primary" @click="openAddModal" />
            </div>
          </template>

          <template #loading>
            <div class="flex align-items-center justify-content-center p-4">
              <ProgressSpinner style="width: 50px; height: 50px" />
            </div>
          </template>

          <Column selection-mode="multiple" header-style="width: 3rem"></Column>
          
          <Column field="approver" header="Approver" style="min-width: 200px">
            <template #body="{ data }">
              <div class="flex align-items-center gap-3" v-if="data.approver">
                <Avatar 
                  :image="data.approver.avatar ? `/storage/${data.approver.avatar}` : null"
                  :label="data.approver.name?.charAt(0).toUpperCase() || 'U'"
                  shape="circle" 
                  size="normal"
                  class="bg-orange-500"
                />
                <div>
                  <div class="font-semibold text-900">{{ data.approver.name || 'Unknown' }}</div>
                  <div class="text-600 text-sm">{{ data.approver.email || '' }}</div>
                  <Tag :value="data.approver.role || 'User'" severity="warning" class="text-xs mt-1"></Tag>
                </div>
              </div>
              <Skeleton v-else height="3rem" />
            </template>
          </Column>

          

          <Column field="is_approved" header="Approval Toggle" style="width: 150px">
            <template #body="{ data }">
              <ToggleButton 
                v-model="data.is_approved" 
                @change="toggleApproval(data)"
                on-label="Approved" 
                off-label="Pending"
                on-icon="pi pi-check" 
                off-icon="pi pi-times"
                :disabled="loading"
                class="w-full"
              />
            </template>
          </Column>

          <Column field="comments" header="Comments" style="min-width: 200px">
            <template #body="{ data }">
              <div v-if="data.comments" class="max-w-15rem">
                <p class="text-600 m-0 line-height-3" style="max-height: 3rem; overflow: hidden;">
                  {{ data.comments }}
                </p>
                <Button 
                  label="View More" 
                  @click="openCommentsModal(data)"
                  class="p-button-link p-0 mt-1"
                  style="height: auto;"
                />
              </div>
              <Button 
                v-else
                label="Add Comments" 
                @click="openCommentsModal(data)"
                class="p-button-link p-0"
                icon="pi pi-plus"
              />
            </template>
          </Column>

          <Column field="created_at" header="Created" style="width: 120px">
            <template #body="{ data }">
              <div class="text-600 text-sm">
                {{ new Date(data.created_at).toLocaleDateString() }}
              </div>
            </template>
          </Column>

          <Column header="Actions" style="width: 120px">
            <template #body="{ data }">
              <div class="flex gap-2 action-btns">
                <Button 
                  icon="pi pi-pencil" 
                  @click="openEditModal(data)"
                  class="p-button-outlined p-button-sm"
                  v-tooltip="'Edit'"
                  aria-label="Edit"
                />
                <Button 
                  icon="pi pi-trash" 
                  @click="deleteApprover(data)"
                  class="p-button-outlined p-button-danger p-button-sm"
                  v-tooltip="'Remove'"
                  aria-label="Remove"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Add Approver Modal -->
    <Dialog 
      v-model:visible="showAddModal" 
      :modal="true" 
      :closable="true"
      class="w-35rem dialog-fade"
    >
      <template #header>
        <div class="flex align-items-center gap-3">
          <i class="pi pi-plus text-lg text-primary"></i>
          <div>
            <div class="font-semibold">Add Approvers</div>
            <small class="text-500">Select one or more approvers for your account</small>
          </div>
        </div>
      </template>

      <div class="flex flex-column gap-4 pt-4">
        <!-- Current User Display -->
        <div class="field">
          <label class="font-semibold block mb-2">Your Account</label>
          <div v-if="currentUser" class="p-3 bg-gray-50 border-round flex align-items-center gap-3">
            <Avatar 
              :image="currentUser.avatar ? `/storage/${currentUser.avatar}` : null"
              :label="currentUser.name?.charAt(0).toUpperCase() || 'U'"
              shape="circle" 
              size="normal"
              class="bg-primary"
            />
            <div>
              <div class="font-semibold text-900">{{ currentUser.name || 'Loading...' }}</div>
              <div class="text-600 text-sm">{{ currentUser.email || '' }}</div>
              <Tag :value="currentUser.role || 'User'" severity="info" class="text-xs mt-1"></Tag>
            </div>
          </div>
        </div>

        <!-- Approvers Multi-Select -->
        <div class="field">
          <label for="approvers" class="font-semibold block mb-2">Select Approvers *</label>
          <MultiSelect
            id="approvers"
            v-model="newApprover.approver_ids"
            :options="filteredApproverOptions"
            option-label="label"
            option-value="value"
            placeholder="Select approvers"
            class="w-full"
            filter
            :max-selected-labels="3"
            :selection-limit="10"
            display="chip"
          >
            <template #option="{ option }">
              <div class="flex align-items-center gap-2">
                <Avatar 
                  :image="option.user?.avatar ? `/storage/${option.user.avatar}` : null"
                  :label="option.user?.name?.charAt(0).toUpperCase() || 'U'"
                  shape="circle" 
                  size="small"
                  class="bg-orange-500"
                />
                <div>
                  <div class="font-semibold">{{ option.user?.name || 'Unknown' }}</div>
                  <small class="text-500">{{ option.user?.email || '' }} - {{ option.user?.role || 'User' }}</small>
                </div>
              </div>
            </template>
            <template #chip="{ value }">
              <div class="p-multiselect-token">
                <span class="p-multiselect-token-label">
                  {{ approverOptions.find(opt => opt.value === value)?.user?.name || value }}
                </span>
              </div>
            </template>
          </MultiSelect>
          <small class="text-500 mt-1">
            Selected: {{ newApprover.approver_ids.length }} approver(s)
          </small>
        </div>

        <!-- Initial Status -->
        <div class="field">
          <label for="approved" class="font-semibold block mb-2">Initial Status</label>
          <ToggleButton 
            v-model="newApprover.is_approved"
            on-label="Approved" 
            off-label="Pending"
            class="w-full"
          />
          <small class="text-500 mt-1">Set to "Approved" if you want immediate approval permissions</small>
        </div>

        <!-- Comments -->
        <div class="field">
          <label for="comments" class="font-semibold block mb-2">Comments</label>
          <Textarea
            id="comments"
            v-model="newApprover.comments"
            rows="3"
            class="w-full"
            placeholder="Enter comments about these approvers..."
          />
        </div>

        <!-- Debug Info -->
        <div class="field">
          <small class="text-500">
            Current user ID: {{ currentUserId }}, 
            Selected approvers: {{ newApprover.approver_ids }},
            Form valid: {{ isFormValid }}
          </small>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-content-end gap-2">
          <Button 
            label="Cancel" 
            @click="showAddModal = false"
            class="p-button-outlined"
            :disabled="savingApprovers"
          />
          <Button 
            label="Add Approvers" 
            @click="saveApprover"
            :loading="savingApprovers"
            :disabled="!isFormValid"
          />
        </div>
      </template>
    </Dialog>

    <!-- Edit Approver Modal -->
    <Dialog 
      v-model:visible="showEditModal" 
      :modal="true" 
      :closable="true"
      class="w-25rem dialog-fade"
    >
      <template #header>
        <div class="flex align-items-center gap-3">
          <i class="pi pi-pencil text-lg text-primary"></i>
          <div class="font-semibold">Edit Approver</div>
        </div>
      </template>

      <div class="flex flex-column gap-4 pt-4">
        <div class="field">
          <label for="edit-approved" class="font-semibold block mb-2">Approval Status</label>
          <ToggleButton 
            id="edit-approved"
            v-model="editingApprover.is_approved"
            on-label="Approved" 
            off-label="Pending"
            class="w-full"
          />
        </div>

        <div class="field">
          <label for="edit-comments" class="font-semibold block mb-2">Comments</label>
          <Textarea
            id="edit-comments"
            v-model="editingApprover.comments"
            rows="4"
            class="w-full"
            placeholder="Enter comments..."
          />
        </div>
      </div>

      <template #footer>
        <div class="flex justify-content-end gap-2">
          <Button 
            label="Cancel" 
            @click="showEditModal = false"
            class="p-button-outlined"
          />
          <Button 
            label="Update" 
            @click="updateApprover"
            :loading="loading"
          />
        </div>
      </template>
    </Dialog>

    <!-- Comments Modal -->
    <Dialog 
      v-model:visible="showCommentsModal" 
      :modal="true" 
      :closable="true"
      class="w-30rem dialog-fade"
    >
      <template #header>
        <div class="flex align-items-center gap-3">
          <i class="pi pi-comment text-lg text-primary"></i>
          <div class="font-semibold">Manage Comments</div>
        </div>
      </template>

      <div class="pt-4">
        <div class="field">
          <label for="modal-comments" class="font-semibold block mb-2">Comments</label>
          <Textarea
            id="modal-comments"
            v-model="editingComments"
            rows="6"
            class="w-full"
            placeholder="Enter detailed comments..."
          />
        </div>
      </div>

      <template #footer>
        <div class="flex justify-content-end gap-2">
          <Button 
            label="Cancel" 
            @click="showCommentsModal = false"
            class="p-button-outlined"
          />
          <Button 
            label="Save Comments" 
            @click="saveComments"
            :loading="loading"
          />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<style scoped>
.approver-remise-list {
  padding: 1.5rem;
}

/* header improvements */
.header-card .p-card-title {
  padding-bottom: 0.25rem;
}

/* toolbar adjustments */
.toolbar .p-toolbar {
  padding: 0.75rem 1rem;
  border-radius: 8px;
  align-items: center;
}
.search-wrap { display:flex; align-items:center; gap:0.5rem; }
.search-wrap .clear-btn { margin-left: -0.5rem; }

/* table enhancements */
.enhanced-table .p-datatable-tbody > tr:hover { background: rgba(0,0,0,0.02); }
.action-btns { transition: opacity .15s ease; opacity: 0.85; }
.enhanced-table .p-datatable-tbody > tr .action-btns { opacity: 0.5; }
.enhanced-table .p-datatable-tbody > tr:hover .action-btns { opacity: 1; }

/* no-data CTA */
.no-data-cta .pi { color: var(--primary-color); }
.no-data-cta h3 { margin: .5rem 0; }

/* dialog animations */
.dialog-fade .p-dialog {
  transition: transform .18s ease, opacity .18s ease;
}

/* multiselect chips tidy */
.p-multiselect-token { background: var(--primary-color); color: var(--primary-color-text); }

/* responsive */
@media (max-width: 768px) {
  .w-35rem { width: 95vw !important; }
  .w-30rem { width: 95vw !important; }
  .w-25rem { width: 90vw !important; }
  .w-20rem { width: 15rem; }
  .w-12rem { width: 10rem; }
}
</style>