
<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import { FilterMatchMode } from 'primevue/api';

// PrimeVue Components (only Toast and ConfirmDialog remain here as they are global or page-level)
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';

// Custom Components
import PageHeader from './components/PageHeader.vue';
import UserPaymentAccessTable from './components/UserPaymentAccessTable.vue';
import UserFormDialog from './components/UserFormDialog.vue';
import AssignPaymentMethodDialog from './components/AssignPaymentMethodDialog.vue';

const toast = useToast();
const confirm = useConfirm();

// --- Payment Method Data ---
const paymentOptions = ref([
  {
    name: 'Pre-payment',
    key: 'prepayment',
    icon: 'fas fa-wallet',
  },
  {
    name: 'Post-payment',
    key: 'postpayment',
    icon: 'fas fa-file-invoice-dollar',
  },
  {
    name: 'Versement',
    key: 'versement',
    icon: 'fas fa-university',
  },
]);

const paymentOptionsFilter = computed(() => {
  return [
    { name: 'All Methods', key: 'all' },
    ...paymentOptions.value.map((option) => ({
      name: option.name,
      key: option.key,
    })),
  ];
});

const paymentOptionsDropdown = computed(() => {
  return paymentOptions.value.map((option) => ({
    name: option.name,
    key: option.key,
  }));
});

const getPaymentMethodName = (key) => {
  const method = paymentOptions.value.find((p) => p.key === key);
  return method ? method.name : key;
};

// --- User Management State ---
const users = ref([]);
const userDialogVisible = ref(false);
const editMode = ref(false);
const userForm = reactive({
  id: null,
  name: '',
  email: '',
  status: null,
  allowedMethods: [],
});
const submitted = ref(false);
const userStatusOptions = ref(['Active', 'Inactive']); // Removed 'Allowed' as it's not in the API response

// --- DataTable Filtering State ---
const selectedPaymentMethodFilter = ref(null);
const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS },
  status: { value: null, matchMode: FilterMatchMode.EQUALS },
});

const filteredUsers = computed(() => {
  let filtered = users.value;

  if (filters.value.global.value) {
    const searchTerm = filters.value.global.value.toLowerCase();
    filtered = filtered.filter(
      (user) =>
        user.name.toLowerCase().includes(searchTerm) ||
        user.email.toLowerCase().includes(searchTerm) ||
        user.status.toLowerCase().includes(searchTerm)
    );
  }

  if (filters.value.status.value && filters.value.status.value !== 'all') {
    filtered = filtered.filter((user) => user.status === filters.value.status.value);
  }

  if (selectedPaymentMethodFilter.value && selectedPaymentMethodFilter.value !== 'all') {
    const methodKey = selectedPaymentMethodFilter.value;
    filtered = filtered.filter((user) => user.allowedMethods.includes(methodKey));
  }

  return filtered;
});

// --- Bulk Assignment Dialog State ---
const assignPaymentDialogVisible = ref(false);
const paymentMethodsToAssign = ref([]);
const selectedUsersForAssignment = ref([]);
const assignSubmitted = ref(false);

// --- Dummy Data ---
onMounted(() => {
  loadUsers();
});

const loadUsers = async () => {
  try {
    console.log('Starting loadUsers API call...');
    const response = await axios.get('/api/users');
    console.log('API Response status:', response.status);
    console.log('API Response headers:', response.headers);
    console.log('API Response data type:', typeof response.data);
    console.log('API Response data:', response.data);
    
    // Handle both paginated and non-paginated responses
    const userData = response.data.data || response.data || [];
    console.log('Extracted userData:', userData);
    console.log('userData type:', typeof userData);
    console.log('userData is array:', Array.isArray(userData));
    
    if (Array.isArray(userData)) {
      users.value = userData.map(user => {
        console.log('Mapping user:', user);
        return {
          id: user.id,
          name: user.name,
          email: user.email,
          status: user.is_active ? 'Active' : 'Inactive',
          allowedMethods: [] // You may need to fetch this separately or adjust based on your API
        };
      });
      
      console.log('Successfully mapped users:', users.value);
      console.log('Final users.value length:', users.value.length);
      
      // If no users loaded, show a message
      if (users.value.length === 0) {
        console.warn('No users loaded from API - empty array returned');
        toast.add({ severity: 'warn', summary: 'Warning', detail: 'No users found in the system', life: 3000 });
      } else {
        console.log('Successfully loaded', users.value.length, 'users');
        toast.add({ severity: 'success', summary: 'Success', detail: `Loaded ${users.value.length} users`, life: 3000 });
      }
    } else {
      console.error('userData is not an array:', userData);
      throw new Error('Invalid response format - expected array');
    }
  } catch (error) {
    console.error('Error loading users:', error);
    if (error.response) {
      console.error('Error response status:', error.response.status);
      console.error('Error response data:', error.response.data);
    }
    
    // Fallback: load dummy data for testing
    console.log('Loading fallback dummy data');
    users.value = [
      { id: 1, name: 'Admin User', email: 'admin@admin.com', status: 'Active', allowedMethods: ['prepayment'] },
      { id: 2, name: 'Test Doctor', email: 'doctor@test.com', status: 'Active', allowedMethods: [] },
      { id: 3, name: 'Receptionist', email: 'reception@test.com', status: 'Inactive', allowedMethods: ['versement'] },
    ];
    
    console.log('Fallback users loaded:', users.value);
    toast.add({ severity: 'info', summary: 'Info', detail: 'Loaded test data due to API error', life: 3000 });
  }
};

// --- Helper Functions for Styling and Logic (will be passed as props) ---
const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'Active':
      return 'status-active';
    case 'Inactive':
      return 'status-inactive';
    default:
      return '';
  }
};

const getPaymentMethodTagClass = (key) => {
  switch (key) {
    case 'prepayment':
      return 'tag-green';
    case 'postpayment':
      return 'tag-blue';
    case 'versement':
      return 'tag-orange';
    default:
      return '';
  }
};

const isValidEmail = (email) => {
  const re =
    /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
  return re.test(String(email).toLowerCase());
};

const getEmptyMessageForUsers = () => {
  if (filters.value.global.value || filters.value.status.value || selectedPaymentMethodFilter.value) {
    return 'No users match your current filters.';
  }
  return 'No users found. Click "Assign Payment Methods" or "Edit" a user to get started!';
};

const clearAllFilters = () => {
  filters.value.global.value = null;
  filters.value.status.value = null;
  selectedPaymentMethodFilter.value = null;
};

// --- User Dialog Logic ---
const hideUserDialog = () => {
  userDialogVisible.value = false;
  submitted.value = false;
};

const resetUserForm = () => {
  userForm.id = null;
  userForm.name = '';
  userForm.email = '';
  userForm.status = null;
  userForm.allowedMethods = [];
};

const editUser = (user) => {
  userForm.id = user.id;
  userForm.name = user.name;
  userForm.email = user.email;
  userForm.status = user.status;
  userForm.allowedMethods = [...user.allowedMethods];
  editMode.value = true;
  userDialogVisible.value = true;
  submitted.value = false;
};

const saveUser = () => {
  submitted.value = true;

  if (userForm.name.trim() && userForm.email.trim() && isValidEmail(userForm.email) && userForm.status) {
    if (editMode.value) {
      const index = users.value.findIndex((u) => u.id === userForm.id);
      if (index !== -1) {
        users.value[index] = { ...userForm };
        toast.add({ severity: 'success', summary: 'Success', detail: 'User Updated', life: 3000 });
      }
    } else {
      const newUser = {
        id: users.value.length > 0 ? Math.max(...users.value.map((u) => u.id)) + 1 : 1,
        ...userForm,
      };
      users.value.push(newUser);
      toast.add({ severity: 'success', summary: 'Success', detail: 'User Added', life: 3000 });
    }
    hideUserDialog();
  } else {
    toast.add({ severity: 'error', summary: 'Validation Error', detail: 'Please fill in all required fields correctly.', life: 3000 });
  }
};

const confirmDeleteUser = (user) => {
  confirm.require({
    message: `Are you sure you want to delete ${user.name}? This action cannot be undone.`,
    header: 'Delete Confirmation',
    icon: 'fas fa-exclamation-triangle',
    acceptClass: 'p-button-danger',
    rejectClass: 'p-button-secondary p-button-outlined',
    acceptLabel: 'Yes, Delete',
    rejectLabel: 'Cancel',
    accept: () => {
      deleteUser(user.id);
      toast.add({ severity: 'success', summary: 'Confirmed', detail: 'User deleted', life: 3000 });
    },
    reject: () => {
      toast.add({ severity: 'info', summary: 'Rejected', detail: 'Delete operation cancelled', life: 3000 });
    },
  });
};

const deleteUser = (userId) => {
  users.value = users.value.filter((u) => u.id !== userId);
};

// --- Bulk Assignment Dialog Logic ---
const showAssignPaymentDialog = () => {
  console.log('Opening assign payment dialog');
  console.log('Available users for assignment:', users.value);
  console.log('Users count:', users.value.length);
  console.log('Users value type:', typeof users.value);
  console.log('Is users.value an array?', Array.isArray(users.value));
  
  if (users.value.length > 0) {
    console.log('Sample user:', users.value[0]);
    console.log('Sample user keys:', Object.keys(users.value[0]));
  } else {
    console.log('No users available - triggering loadUsers and loading fallback data');
    // Load fallback data immediately for testing
    users.value = [
      { id: 1, name: 'Admin User', email: 'admin@admin.com', status: 'Active', allowedMethods: ['prepayment'] },
      { id: 2, name: 'Test Doctor', email: 'doctor@test.com', status: 'Active', allowedMethods: [] },
      { id: 3, name: 'Receptionist', email: 'reception@test.com', status: 'Inactive', allowedMethods: ['versement'] },
    ];
    console.log('Loaded immediate fallback users:', users.value);
    loadUsers();
  }
  
  resetAssignPaymentForm();
  assignPaymentDialogVisible.value = true;
  assignSubmitted.value = false;
};

const hideAssignPaymentDialog = () => {
  assignPaymentDialogVisible.value = false;
  assignSubmitted.value = false;
};

const resetAssignPaymentForm = () => {
  paymentMethodsToAssign.value = [];
  selectedUsersForAssignment.value = [];
};

const performBulkAssignment = () => {
  assignSubmitted.value = true;

  if (!paymentMethodsToAssign.value || paymentMethodsToAssign.value.length === 0 || selectedUsersForAssignment.value.length === 0) {
    toast.add({
      severity: 'error',
      summary: 'Validation Error',
      detail: 'Please select payment methods and users.',
      life: 3000,
    });
    return;
  }

  let assignmentsMade = 0;
  selectedUsersForAssignment.value.forEach((selectedUser) => {
    const userIndex = users.value.findIndex((u) => u.id === selectedUser.id);
    if (userIndex !== -1) {
      const currentUser = users.value[userIndex];
      paymentMethodsToAssign.value.forEach((method) => {
        if (!currentUser.allowedMethods.includes(method)) {
          currentUser.allowedMethods.push(method);
          assignmentsMade++;
        }
      });
    }
  });

  if (assignmentsMade > 0) {
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: `${assignmentsMade} payment method assignments made.`,
      life: 3000,
    });
  } else {
    toast.add({
      severity: 'info',
      summary: 'No Changes',
      detail: 'Selected users already have the chosen payment methods assigned.',
      life: 3000,
    });
  }

  hideAssignPaymentDialog();
};

const selectAllActiveUsers = () => {
  selectedUsersForAssignment.value = users.value.filter((user) => user.status === 'Active');
  toast.add({ severity: 'info', summary: 'Users Selected', detail: `All active users added to selection.`, life: 3000 });
};

const removeUserChip = (userToRemove) => {
  selectedUsersForAssignment.value = selectedUsersForAssignment.value.filter((user) => user.id !== userToRemove.id);
};
</script>
<template>
  <div class="specialization-page">
    <Toast />
    <ConfirmDialog></ConfirmDialog>

    <PageHeader
      title="Payment Methods & User Access"
      subtitle="Manage payment options and user permissions for each method."
      current-breadcrumb="Payment Access"
    />

    <div class="content">
      <div class="container">
        <UserPaymentAccessTable
          :users="filteredUsers"
          :filters="filters"
          :payment-options-filter="paymentOptionsFilter"
          :user-status-options="userStatusOptions"
          :selected-payment-method-filter="selectedPaymentMethodFilter"
          @show-assign-payment-dialog="showAssignPaymentDialog"
          @clear-all-filters="clearAllFilters"
          @edit-user="editUser"
          @confirm-delete-user="confirmDeleteUser"
          @update:global-filter="(value) => (filters.global.value = value)"
          @update:status-filter="(value) => (filters.status.value = value)"
          @update:payment-method-filter="(value) => (selectedPaymentMethodFilter = value)"
          :get-status-badge-class="getStatusBadgeClass"
          :get-payment-method-tag-class="getPaymentMethodTagClass"
          :get-payment-method-name="getPaymentMethodName"
          :get-empty-message-for-users="getEmptyMessageForUsers"
        />
      </div>
    </div>

    <UserFormDialog
      :visible="userDialogVisible"
      :edit-mode="editMode"
      :user-form="userForm"
      :submitted="submitted"
      :user-status-options="userStatusOptions"
      :payment-options-dropdown="paymentOptionsDropdown"
      @hide-dialog="hideUserDialog"
      @save-user="saveUser"
      :is-valid-email="isValidEmail"
    />

    <AssignPaymentMethodDialog
      :visible="assignPaymentDialogVisible"
      :payment-methods-to-assign="paymentMethodsToAssign"
      :selected-users-for-assignment="selectedUsersForAssignment"
      :assign-submitted="assignSubmitted"
      :payment-options-dropdown="paymentOptionsDropdown"
      :users="users"
      @hide-dialog="hideAssignPaymentDialog"
      @perform-bulk-assignment="performBulkAssignment"
      @select-all-active-users="selectAllActiveUsers"
      @remove-user-chip="removeUserChip"
      @update:payment-methods="(value) => (paymentMethodsToAssign = value)"
      @update:selected-users="(value) => (selectedUsersForAssignment = value)"
    />
  </div>
</template>


<style scoped>
@import './styles/base-styles.css'; /* Import base styles */
</style>