<!-- filepath: d:\Projects\AppointmentSystem\AppointmentSystem-main\resources\js\Pages\Apps\Configuration\RemiseMangement\PaymentMethod\PaymentMethodList.vue -->

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import { FilterMatchMode } from 'primevue/api';

// UPDATED: Import service and enum instead of axios
import paymentMethodService from '../../../../../Components/Apps/services/Reception/paymentMethodService.js';
import {
  PaymentMethodEnum,
  getPaymentMethodsForDropdown,
  getPaymentMethodLabel,
  getPaymentMethodIcon
} from '../../../../../Components/Apps/enums/PaymentMethodEnum.js';

// PrimeVue Components
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';

// Custom Components
import PageHeader from '../../../../../Components/Apps/Configuration/RemiseMangement/PaymentMethod/PageHeader.vue';
import UserPaymentAccessTable from '../../../../../Components/Apps/Configuration/RemiseMangement/PaymentMethod/UserPaymentAccessTable.vue';
import UserFormDialog from '../../../../../Components/Apps/Configuration/RemiseMangement/PaymentMethod/UserFormDialog.vue';
import AssignPaymentMethodDialog from '../../../../../Components/Apps/Configuration/RemiseMangement/PaymentMethod/AssignPaymentMethodDialog.vue';

const toast = useToast();
const confirm = useConfirm();

// --- Payment Method Data (UPDATED to use enum) ---
const paymentOptions = ref(getPaymentMethodsForDropdown()); // Use enum data

const paymentOptionsFilter = computed(() => {
  return [
    { name: 'All Methods', key: 'all' },
    ...paymentOptions.value.map((option) => ({
      name: option.label,
      key: option.value,
    })),
  ];
});

const paymentOptionsDropdown = computed(() => {
  return paymentOptions.value.map((option) => ({
    name: option.label,
    key: option.value,
  }));
});

// UPDATED: Use enum helper function
const getPaymentMethodName = (key) => {
  return getPaymentMethodLabel(key);
};

// --- User Management State ---
const users = ref([]);
const userDialogVisible = ref(false);
const editMode = ref(false);
const userForm = reactive({
  id: null,
  name: '',
  email: '',
  password: '',
  status: null,
  allowedMethods: [], // Array of payment method keys (strings)
});
const submitted = ref(false);
const userStatusOptions = ref(['active', 'inactive', 'suspended']); // FIXED: lowercase to match backend

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
        user.name?.toLowerCase().includes(searchTerm) ||
        user.email?.toLowerCase().includes(searchTerm)
    );
  }

  if (filters.value.status.value && filters.value.status.value !== 'all') {
    filtered = filtered.filter((user) => user.status === filters.value.status.value);
  }

  if (selectedPaymentMethodFilter.value && selectedPaymentMethodFilter.value !== 'all') {
    const methodKey = selectedPaymentMethodFilter.value;
    filtered = filtered.filter((user) =>
      Array.isArray(user.allowedMethods) && user.allowedMethods.includes(methodKey)
    );
  }

  return filtered;
});

// --- Bulk Assignment Dialog State ---
const assignPaymentDialogVisible = ref(false);
const paymentMethodsToAssign = ref([]);
const selectedUsersForAssignment = ref([]);
const UsersPaymentMethod = ref([]);
const assignSubmitted = ref(false);

// --- API Calls (UPDATED to use service) ---
const fetchPaymentMethods = async () => {
  // UPDATED: Use enum instead of API call for payment methods
  paymentOptions.value = getPaymentMethodsForDropdown();
  console.log('Using enum payment options:', paymentOptions.value);
};

const fetchPaymentMethodUsers = async () => {
  try {
    const result = await paymentMethodService.getAllUsersWithPaymentMethods();
    if (result.success) {
      // UPDATED: Normalize the data structure
      UsersPaymentMethod.value = result.data.data.map(userPaymentMethod => ({
        id: userPaymentMethod.user?.id || userPaymentMethod.user_id,
        name: userPaymentMethod.user?.name || 'Unknown',
        email: userPaymentMethod.user?.email || 'No email',
        status: userPaymentMethod.status || 'inactive',
        allowedMethods: Array.isArray(userPaymentMethod.payment_method_key) 
          ? userPaymentMethod.payment_method_key 
          : []
      }));
      console.log('Fetched UsersPaymentMethod:', UsersPaymentMethod.value);
    } else {
      throw new Error(result.message);
    }
  } catch (error) {
    console.error('Error fetching payment method users:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.message || 'Could not load payment method users.',
      life: 3000,
    });
  }
};

const fetchUsers = async () => {
  try {
    // UPDATED: Use a different endpoint for all users (not payment-specific)
    const result = await paymentMethodService.getAllUsersWithPaymentMethods();
    if (result.success) {
      users.value = result.data.data.map(item => ({
        id: item.user?.id || item.user_id,
        name: item.user?.name || 'Unknown',
        email: item.user?.email || 'No email',
        status: item.status || 'inactive',
        allowedMethods: Array.isArray(item.payment_method_key) 
          ? item.payment_method_key 
          : []
      }));
    } else {
      throw new Error(result.message);
    }
  } catch (error) {
    console.error('Error fetching users:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.message || 'Could not load users.',
      life: 3000,
    });
  }
};

onMounted(async () => {
  await fetchPaymentMethods();
  await fetchUsers();
  await fetchPaymentMethodUsers();
});

// --- Helper Functions (UPDATED to use enum) ---
const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'active':
      return 'status-active';
    case 'suspended':
      return 'status-info';
    case 'inactive':
      return 'status-inactive';
    default:
      return '';
  }
};

const getPaymentMethodTagClass = (key) => {
  switch (key) {
    case PaymentMethodEnum.PREPAYMENT:
      return 'tag-green';
    case PaymentMethodEnum.POSTPAYMENT:
      return 'tag-blue';
    case PaymentMethodEnum.VERSEMENT:
      return 'tag-orange';
    default:
      return '';
  }
};

const isValidEmail = (email) => {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return re.test(email);
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
  resetUserForm();
};

const resetUserForm = () => {
  userForm.id = null;
  userForm.name = '';
  userForm.email = '';
  userForm.password = '';
  userForm.status = null;
  userForm.allowedMethods = [];
};

const editUser = (user) => {
  editMode.value = true;
  userDialogVisible.value = true;
  userForm.id = user.id;
  userForm.name = user.name;
  userForm.email = user.email;
  userForm.password = '';
  userForm.status = user.status;
  // UPDATED: Ensure allowedMethods is always an array of strings
  userForm.allowedMethods = Array.isArray(user.allowedMethods) 
    ? [...user.allowedMethods] 
    : [];
  console.log('Editing user:', userForm);
};

// UPDATED: Use service for save operations
const saveUserPaymentMethod = async () => {
  submitted.value = true;

  // Validation
  if (!userForm.status) {
    toast.add({
      severity: 'error',
      summary: 'Validation Error',
      detail: 'Please select a status.',
      life: 3000,
    });
    return;
  }

  if (!editMode.value) {
    if (!userForm.name.trim() || !userForm.email.trim() || !isValidEmail(userForm.email)) {
      toast.add({
        severity: 'error',
        summary: 'Validation Error',
        detail: 'Please fill in all required fields correctly (Name, Email).',
        life: 3000,
      });
      return;
    }

    if (!userForm.password) {
      toast.add({
        severity: 'error',
        summary: 'Validation Error',
        detail: 'Password is required for new users.',
        life: 3000,
      });
      return;
    }
  }

  // UPDATED: Construct payload correctly
  const payload = {
    status: userForm.status,
    allowedMethods: userForm.allowedMethods, // Array of payment method keys
  };

  // For new users, add user creation fields
  if (!editMode.value) {
    payload.name = userForm.name;
    payload.email = userForm.email;
    payload.password = userForm.password;
  }

  try {
    let result;
    if (editMode.value) {
      // UPDATED: Use service
      result = await paymentMethodService.updateUserPaymentMethods(userForm.id, payload);
    } else {
      // UPDATED: Use service for bulk assignment (which creates users)
      result = await paymentMethodService.bulkAssignPaymentMethods({
        userIds: [userForm.id], // If creating, this might need adjustment
        paymentMethodKeys: payload.allowedMethods,
        status: payload.status,
        // Add user creation fields
        ...payload
      });
    }

    if (result.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: `User payment methods ${editMode.value ? 'updated' : 'created'} successfully`,
        life: 3000,
      });

      hideUserDialog();
      await fetchUsers();
      await fetchPaymentMethodUsers();
    } else {
      throw new Error(result.message);
    }
  } catch (error) {
    console.error('Error saving user payment methods:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.message || 'An error occurred while saving.',
      life: 3000,
    });
  }
};

// UPDATED: Use service for delete
const confirmDeleteUser = (user) => {
  confirm.require({
    message: `Are you sure you want to delete ${user.name}? This action cannot be undone.`,
    header: 'Delete Confirmation',
    icon: 'fas fa-exclamation-triangle',
    acceptClass: 'p-button-danger',
    rejectClass: 'p-button-secondary p-button-outlined',
    acceptLabel: 'Yes, Delete',
    rejectLabel: 'Cancel',
    accept: async () => {
      try {
        const result = await paymentMethodService.deleteUserPaymentMethods(user.id);
        if (result.success) {
          toast.add({ 
            severity: 'success', 
            summary: 'Confirmed', 
            detail: 'User deleted', 
            life: 3000 
          });
          await fetchUsers();
          await fetchPaymentMethodUsers();
        } else {
          throw new Error(result.message);
        }
      } catch (error) {
        console.error('Error deleting user:', error);
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error.message || 'An error occurred while deleting the user.',
          life: 3000,
        });
      }
    },
    reject: () => {
      toast.add({ 
        severity: 'info', 
        summary: 'Rejected', 
        detail: 'Delete operation cancelled', 
        life: 3000 
      });
    },
  });
};

// --- Bulk Assignment Dialog Logic ---
const showAssignPaymentDialog = () => {
  resetAssignPaymentForm();
  assignPaymentDialogVisible.value = true;
  assignSubmitted.value = false;
};

const hideAssignPaymentDialog = () => {
  assignPaymentDialogVisible.value = false;
  assignSubmitted.value = false;
  resetAssignPaymentForm();
};

const resetAssignPaymentForm = () => {
  paymentMethodsToAssign.value = [];
  selectedUsersForAssignment.value = [];
};

// UPDATED: Use service for bulk assignment
const performBulkAssignment = async () => {
  assignSubmitted.value = true;

  if (paymentMethodsToAssign.value.length === 0 || selectedUsersForAssignment.value.length === 0) {
    toast.add({
      severity: 'error',
      summary: 'Validation Error',
      detail: 'Please select at least one payment method and at least one user.',
      life: 3000,
    });
    return;
  }

  const userIdsToAssign = selectedUsersForAssignment.value.map((user) => user.id);

  // UPDATED: Use correct payload structure
  const bulkPayload = {
    paymentMethodKeys: paymentMethodsToAssign.value,
    userIds: userIdsToAssign,
    status: 'active',
  };

  console.log('Bulk Assignment Payload:', bulkPayload);

  try {
    // UPDATED: Use service
    const result = await paymentMethodService.bulkAssignPaymentMethods(bulkPayload);
    
    if (result.success) {
      const message = result.data.message || 
        `${userIdsToAssign.length} user(s) updated with ${paymentMethodsToAssign.value.length} payment method(s).`;

      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: message,
        life: 3000,
      });

      hideAssignPaymentDialog();
      await fetchUsers();
      await fetchPaymentMethodUsers();
    } else {
      throw new Error(result.message);
    }
  } catch (error) {
    console.error('Error performing bulk assignment:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.message || 'An error occurred during bulk assignment.',
      life: 3000,
    });
  }
};

const selectAllActiveUsers = () => {
  selectedUsersForAssignment.value = users.value.filter((user) => user.status === 'active');
  toast.add({ 
    severity: 'info', 
    summary: 'Users Selected', 
    detail: `All active users added to selection.`, 
    life: 3000 
  });
};

const removeUserChip = (userToRemove) => {
  selectedUsersForAssignment.value = selectedUsersForAssignment.value.filter(
    (user) => user.id !== userToRemove.id
  );
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
          :users="UsersPaymentMethod"
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
          @update:filters="(newFilters) => (filters = newFilters)"
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
      @save-user="saveUserPaymentMethod"
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
/* Add your scoped styles here */
</style>