
<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
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
const userStatusOptions = ref(['Active', 'Inactive', 'Allowed']); // Added 'Allowed' as it appears in dummy data

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
const paymentMethodToAssign = ref(null);
const selectedUsersForAssignment = ref([]);
const assignSubmitted = ref(false);

// --- Dummy Data ---
onMounted(() => {
  users.value = [
    { id: 1, name: 'Alice Johnson', email: 'alice.j@example.com', status: 'Active', allowedMethods: ['prepayment', 'postpayment'] },
    { id: 2, name: 'Bob Williams', email: 'bob.w@example.com', status: 'Inactive', allowedMethods: ['prepayment'] },
    { id: 3, name: 'Charlie Davis', email: 'charlie.d@example.com', status: 'Allowed', allowedMethods: ['postpayment', 'versement'] },
    { id: 4, name: 'Diana Miller', email: 'diana.m@example.com', status: 'Active', allowedMethods: ['prepayment'] },
    { id: 5, name: 'Eve Brown', email: 'eve.b@example.com', status: 'Allowed', allowedMethods: ['prepayment', 'postpayment', 'versement'] },
    { id: 6, name: 'Frank White', email: 'frank.w@example.com', status: 'Active', allowedMethods: ['prepayment'] },
    { id: 7, name: 'Grace Green', email: 'grace.g@example.com', status: 'Inactive', allowedMethods: [] },
    { id: 8, name: 'Harry Black', email: 'harry.b@example.com', status: 'Active', allowedMethods: ['prepayment'] },
    { id: 9, name: 'Ivy King', email: 'ivy.k@example.com', status: 'Allowed', allowedMethods: ['postpayment'] },
    { id: 10, name: 'Jack Lee', email: 'jack.l@example.com', status: 'Active', allowedMethods: ['prepayment', 'versement'] },
    { id: 11, name: 'Karen Taylor', email: 'karen.t@example.com', status: 'Active', allowedMethods: ['prepayment'] },
    { id: 12, name: 'Liam Hall', email: 'liam.h@example.com', status: 'Inactive', allowedMethods: ['postpayment'] },
    { id: 13, name: 'Mia Young', email: 'mia.y@example.com', status: 'Allowed', allowedMethods: ['versement'] },
    { id: 14, name: 'Noah Clark', email: 'noah.c@example.com', status: 'Active', allowedMethods: ['prepayment', 'postpayment'] },
    { id: 15, name: 'Olivia Lewis', email: 'olivia.l@example.com', status: 'Inactive', allowedMethods: [] },
    { id: 16, name: 'Peter Scott', email: 'peter.s@example.com', status: 'Active', allowedMethods: ['prepayment'] },
    { id: 17, name: 'Quinn Adams', email: 'quinn.a@example.com', status: 'Allowed', allowedMethods: ['postpayment'] },
    { id: 18, name: 'Rachel Baker', email: 'rachel.b@example.com', status: 'Active', allowedMethods: ['prepayment', 'versement'] },
    { id: 19, name: 'Sam Nelson', email: 'sam.n@example.com', status: 'Inactive', allowedMethods: ['prepayment'] },
    { id: 20, name: 'Tina Carter', email: 'tina.c@example.com', status: 'Allowed', allowedMethods: ['postpayment', 'versement'] },
  ];
});

// --- Helper Functions for Styling and Logic (will be passed as props) ---
const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'Active':
      return 'status-active';
    case 'Allowed':
      return 'status-info';
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
  resetAssignPaymentForm();
  assignPaymentDialogVisible.value = true;
  assignSubmitted.value = false;
};

const hideAssignPaymentDialog = () => {
  assignPaymentDialogVisible.value = false;
  assignSubmitted.value = false;
};

const resetAssignPaymentForm = () => {
  paymentMethodToAssign.value = null;
  selectedUsersForAssignment.value = [];
};

const performBulkAssignment = () => {
  assignSubmitted.value = true;

  if (!paymentMethodToAssign.value || selectedUsersForAssignment.value.length === 0) {
    toast.add({
      severity: 'error',
      summary: 'Validation Error',
      detail: 'Please select a payment method and at least one user.',
      life: 3000,
    });
    return;
  }

  let assignmentsMade = 0;
  selectedUsersForAssignment.value.forEach((selectedUser) => {
    const userIndex = users.value.findIndex((u) => u.id === selectedUser.id);
    if (userIndex !== -1) {
      const currentUser = users.value[userIndex];
      if (!currentUser.allowedMethods.includes(paymentMethodToAssign.value)) {
        currentUser.allowedMethods.push(paymentMethodToAssign.value);
        assignmentsMade++;
      }
    }
  });

  if (assignmentsMade > 0) {
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: `${assignmentsMade} user(s) updated with new payment method.`,
      life: 3000,
    });
  } else {
    toast.add({
      severity: 'info',
      summary: 'No Changes',
      detail: 'Selected users already had the chosen payment method assigned.',
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
      :payment-method-to-assign="paymentMethodToAssign"
      :selected-users-for-assignment="selectedUsersForAssignment"
      :assign-submitted="assignSubmitted"
      :payment-options-dropdown="paymentOptionsDropdown"
      :users="users"
      @hide-dialog="hideAssignPaymentDialog"
      @perform-bulk-assignment="performBulkAssignment"
      @select-all-active-users="selectAllActiveUsers"
      @remove-user-chip="removeUserChip"
      @update:payment-method="(value) => (paymentMethodToAssign = value)"
      @update:selected-users="(value) => (selectedUsersForAssignment = value)"
    />
  </div>
</template>


<style scoped>
@import './styles/base-styles.css'; /* Import base styles */
</style>