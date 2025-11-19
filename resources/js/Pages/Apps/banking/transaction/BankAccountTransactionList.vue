<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useRoute, useRouter } from 'vue-router';
import { debounce } from 'lodash-es';

// PrimeVue Components
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Tag from 'primevue/tag';
import ProgressSpinner from 'primevue/progressspinner';
import Paginator from 'primevue/paginator';
import Toast from 'primevue/toast';
import Dialog from 'primevue/dialog';
import Calendar from 'primevue/calendar';
import Textarea from 'primevue/textarea';
import FileUpload from 'primevue/fileupload';

// Services
import { bankAccountTransactionService } from '../../../../Components/Apps/services/bank/BankAccountTransactionService';
import { bankAccountService } from '../../../../Components/Apps/services/bank/BankAccountService';
import { coffreService } from '../../../../Components/Apps/services/Coffre/CoffreService';

// Composables
const toast = useToast();
const route = useRoute();
const router = useRouter();

// Reactive state
const transactions = ref([]);
const loading = ref(true);
const searchQuery = ref('');
const perPage = ref(15);
const meta = ref(null);
const summary = ref(null);

// New reactive state for account filtering
const selectedBankAccountId = ref(null);
const accountInfo = ref(null);

// Validation modal state
const showValidationModal = ref(false);
const selectedTransaction = ref(null);
const validationLoading = ref(false);
const validationErrors = ref({});

// Validation form data
const validationForm = ref({
  Payment_date: new Date(),
  reference_validation: '',
  Attachment_validation: null,
  Reason_validation: ''
});

// Transaction detail modal state
const showDetailModal = ref(false);
const selectedTransactionDetail = ref(null);
const attachmentUrl = ref(null);
const isAttachmentImage = ref(false);

// Pack users modal state
const showPackUsersModal = ref(false);
const selectedPack = ref(null);
const packUsers = ref([]);
const loadingPackUsers = ref(false);

// Create transaction modal state
const showCreateTransactionModal = ref(false);
const createTransactionLoading = ref(false);
const createTransactionErrors = ref({});

// Create transaction form data
const createTransactionForm = ref({
  bank_account_id: null,
  transaction_type: 'debit',
  amount: null,
  description: '',
  reference: '',
  transaction_date: new Date(),
  Designation: '',
  Payer: '',
  Attachment: null,
  coffre_id: null
});

// Bulk upload modal state
const showBulkUploadModal = ref(false);
const bulkUploadLoading = ref(false);
const bulkUploadErrors = ref({});
const showTemplatePreview = ref(false);

// Bulk upload form data
const bulkUploadForm = ref({
  bank_account_id: null,
  file: null,
  description: ''
});

// Template data
const templateData = ref(null);

// File upload reference
const fileUpload = ref(null);

// Filter states
const filters = ref({
  bank_account_id: null,
  transaction_type: '',
  status: ''
});

// Options
const bankAccountOptions = ref([]);
const coffreOptions = ref([]);

const typeOptions = [
  { label: 'Credit', value: 'credit' },
  { label: 'Debit', value: 'debit' }
];

const statusOptions = [
  { label: 'Pending', value: 'pending' },
  { label: 'Confirmed', value: 'confirmed' },
  { label: 'Completed', value: 'completed' },
  { label: 'Cancelled', value: 'cancelled' },
  { label: 'Reconciled', value: 'reconciled' }
];

const perPageOptions = [
  { label: '10 per page', value: 10 },
  { label: '15 per page', value: 15 },
  { label: '25 per page', value: 25 },
  { label: '50 per page', value: 50 }
];

// Computed properties for dynamic page title
const pageTitle = computed(() => {
  if (selectedBankAccountId.value && accountInfo.value) {
    return `Transactions - ${accountInfo.value.account_name}`;
  }
  return 'Bank Account Transactions';
});

const pageSubtitle = computed(() => {
  if (selectedBankAccountId.value && accountInfo.value) {
    return `View transactions for ${accountInfo.value.bank_name}`;
  }
  return 'View all bank account transactions';
});

// Methods
const fetchTransactions = async (page = 1) => {
  loading.value = true;
  const params = {
    page,
    per_page: perPage.value,
    search: searchQuery.value,
    ...filters.value
  };

  if (selectedBankAccountId.value) {
    params.bank_account_id = selectedBankAccountId.value;
  }

  try {
    const result = await bankAccountTransactionService.getAll(params);
    if (result.success) {
      transactions.value = result.data;
      meta.value = result.meta;
      summary.value = result.summary;
    } else {
      showToast('error', 'Error', result.message);
    }
  } catch (error) {
    showToast('error', 'Error', 'An error occurred while loading transactions.');
    console.error(error);
  } finally {
    loading.value = false;
  }
};

const loadBankAccounts = async () => {
  try {
    const result = await bankAccountService.getAll();
    console.log(result);
    
    if (result.success) {
      bankAccountOptions.value = result.data.map(account => ({
        label: `${account.bank_name} - ${account.account_name}`,
        value: account.id
      }));
    }
  } catch (error) {
    console.error('Error loading bank accounts:', error);
  }
};

const loadCoffreOptions = async () => {
  try {
    const result = await coffreService.getAll();
    if (result.success) {
      coffreOptions.value = result.data.map(coffre => ({
        label: `${coffre.name} - ${coffre.location}`,
        value: coffre.id
      }));
    }
  } catch (error) {
    console.error('Error loading coffre options:', error);
  }
};

// Load specific bank account info when filtering
const loadAccountInfo = async (accountId) => {
  try {
    const result = await bankAccountService.getById(accountId);
    if (result.success) {
      accountInfo.value = result.data;
    }
  } catch (error) {
    console.error('Error loading account info:', error);
  }
};

// Navigation methods
const navigateBack = () => {
  router.push({ name: 'bank.accounts' });
};

// Initialize from route parameters
const initializeFromRoute = () => {
  if (route.query.bank_account_id) {
    selectedBankAccountId.value = parseInt(route.query.bank_account_id);
    
    if (route.query.account_name && route.query.bank_name) {
      accountInfo.value = {
        account_name: route.query.account_name,
        bank_name: route.query.bank_name,
        masked_account_number: route.query.account_number || 'N/A'
      };
    }
    
    loadAccountInfo(selectedBankAccountId.value);
  } else {
    selectedBankAccountId.value = null;
    accountInfo.value = null;
  }
};

const debouncedSearch = debounce(() => fetchTransactions(), 400);

const onPageChange = (event) => {
  fetchTransactions(event.page + 1);
};

// Utility methods
const showToast = (severity, summary, detail) => {
  toast.add({ severity, summary, detail, life: 3000 });
};

const formatCurrency = (amount, currency = 'DZD') => {
  return new Intl.NumberFormat('fr-DZ', {
    style: 'currency',
    currency: currency,
    minimumFractionDigits: 2
  }).format(amount || 0);
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short',
    day: '2-digit'
  }).format(new Date(dateString));
};

const formatTime = (dateString) => {
  if (!dateString) return '';
  return new Intl.DateTimeFormat('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: true
  }).format(new Date(dateString));
};

const getAmountClass = (type) => {
  return type === 'credit' ? 'tw-text-green-500' : 'tw-text-red-500';
};

const getBalanceClass = (balance) => {
  if (balance > 0) return 'tw-text-green-600';
  if (balance < 0) return 'tw-text-red-600';
  return 'tw-text-gray-600';
};

const getStatusIcon = (status) => {
  const icons = {
    'pending': 'pi pi-clock',
    'confirmed': 'pi pi-check',
    'completed': 'pi pi-check-circle',
    'cancelled': 'pi pi-times-circle',
    'reconciled': 'pi pi-verified'
  };
  return icons[status] || 'pi pi-circle';
};

const truncateText = (text, length) => {
  if (!text) return '';
  return text.length > length ? text.substring(0, length) + '...' : text;
};

// Validation functionality
const showValidationForm = (transaction) => {
  selectedTransaction.value = transaction;
  // Reset form
  validationForm.value = {
    Payment_date: new Date(),
    reference_validation: transaction.reference || `VAL-${Date.now()}`,
    Attachment_validation: null,
    Reason_validation: ''
  };
  validationErrors.value = {};
  showValidationModal.value = true;
};

const cancelValidation = () => {
  showValidationModal.value = false;
  selectedTransaction.value = null;
  validationErrors.value = {};
  if (fileUpload.value) {
    fileUpload.value.clear();
  }
};

const onFileSelect = (event) => {
  const file = event.files[0];
  if (file) {
    validationForm.value.Attachment_validation = file;
  }
};

const onFileRemove = () => {
  validationForm.value.Attachment_validation = null;
};

// New helper function to log FormData
const logFormData = (formData) => {
  console.log('Logging FormData contents:');
  for (let [key, value] of formData.entries()) {
    console.log(`${key}:`, value);
  }
};

const confirmValidation = async () => {
  validationLoading.value = true;
  validationErrors.value = {};
  
  try {
    // Prepare validation data
    const validationData = new FormData();
    
    // Add form fields with null checks
    if (validationForm.value.Payment_date) {
      validationData.append('Payment_date', validationForm.value.Payment_date.toISOString().split('T')[0]);
    }
    if (validationForm.value.reference_validation) {
      validationData.append('reference_validation', validationForm.value.reference_validation);
    }
    if (validationForm.value.Reason_validation) {
      validationData.append('Reason_validation', validationForm.value.Reason_validation);
    }
    
    // Only append the file if it exists
    if (validationForm.value.Attachment_validation) {
        validationData.append('Attachment_validation', validationForm.value.Attachment_validation);
    }
   
    // Log the data for debugging purposes
    logFormData(validationData);

    const result = await bankAccountTransactionService.validate(selectedTransaction.value.id, validationData);
    
    if (result.success) {
      showToast('success', 'Success', 'Transaction validated successfully');
      showValidationModal.value = false;
      selectedTransaction.value = null;
      if (fileUpload.value) {
        fileUpload.value.clear();
      }
      fetchTransactions(); // Refresh the list
    } else {
      if (result.errors) {
        validationErrors.value = result.errors;
      } else {
        showToast('error', 'Error', result.message || 'Failed to validate transaction');
      }
    }
  } catch (error) {
    console.error('Error validating transaction:', error);
    showToast('error', 'Error', 'An error occurred while validating the transaction');
  } finally {
    validationLoading.value = false;
  }
};

const validateTransaction = async (transaction) => {
  showValidationForm(transaction);
};

const showTransactionDetails = (event) => {
  // PrimeVue row-click emits an object with originalEvent and data
  const transaction = event?.data || event;
  
  // Check if this transaction has associated packs (meaning it's a bulk upload transaction)
  if (transaction.pack_id || transaction.has_packs) {
    showPackUsersTable(transaction);
    return;
  }
  
  // Original transaction details logic
  selectedTransactionDetail.value = transaction;

  // compute attachment url and image flag
  const att = transaction?.Attachment_validation || transaction?.Attachment_validation_url || transaction?.attachment_validation;
  if (att) {
    if (/^https?:\/\//i.test(att)) {
      attachmentUrl.value = att;
    } else if (att.startsWith('/')) {
      attachmentUrl.value = att;
    } else {
      // safe default: prepend /storage/
      attachmentUrl.value = `/storage/${att}`;
    }
    const ext = (attachmentUrl.value.split('.').pop() || '').toLowerCase();
    isAttachmentImage.value = ['jpg', 'jpeg', 'png', 'gif'].includes(ext);
  } else {
    attachmentUrl.value = null;
    isAttachmentImage.value = false;
  }

  showDetailModal.value = true;
};

// New method to show pack users
const showPackUsersTable = async (transaction) => {
  selectedPack.value = transaction;
  loadingPackUsers.value = true;
  showPackUsersModal.value = true;
  
  try {
    const result = await bankAccountTransactionService.getPackUsers(transaction.id);
    if (result.success) {
      packUsers.value = result.data;
    } else {
      showToast('error', 'Error', result.message || 'Failed to load pack users');
    }
  } catch (error) {
    console.error('Error loading pack users:', error);
    showToast('error', 'Error', 'An error occurred while loading pack users');
  } finally {
    loadingPackUsers.value = false;
  }
};

const closePackUsersModal = () => {
  showPackUsersModal.value = false;
  selectedPack.value = null;
  packUsers.value = [];
};

// Helper functions for attachments
const downloadAttachment = (url, filename) => {
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  link.target = '_blank';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

const getFileIcon = (url) => {
  if (!url) return 'pi pi-file';
  const extension = url.split('.').pop().toLowerCase();
  const iconMap = {
    'pdf': 'pi pi-file-pdf',
    'doc': 'pi pi-file-word',
    'docx': 'pi pi-file-word',
    'xls': 'pi pi-file-excel',
    'xlsx': 'pi pi-file-excel',
    'jpg': 'pi pi-image',
    'jpeg': 'pi pi-image',
    'png': 'pi pi-image',
    'gif': 'pi pi-image'
  };
  return iconMap[extension] || 'pi pi-file';
};

// Create transaction functionality
const showCreateTransactionForm = () => {
  createTransactionForm.value = {
    bank_account_id: selectedBankAccountId.value,
    transaction_type: 'debit',
    amount: null,
    description: '',
    reference: '',
    transaction_date: new Date(),
    Designation: '',
    Payer: '',
    Attachment: null,
    coffre_id: null
  };
  createTransactionErrors.value = {};
  loadCoffreOptions(); // Load coffre options when opening the modal
  showCreateTransactionModal.value = true;
};

const cancelCreateTransaction = () => {
  showCreateTransactionModal.value = false;
  createTransactionForm.value = {
    bank_account_id: null,
    transaction_type: 'debit',
    amount: null,
    description: '',
    reference: '',
    transaction_date: new Date(),
    Designation: '',
    Payer: '',
    Attachment: null,
    coffre_id: null
  };
  createTransactionErrors.value = {};
};

const onCreateFileSelect = (event) => {
  const file = event.files[0];
  if (file) {
    createTransactionForm.value.Attachment = file;
  }
};

const onCreateFileRemove = () => {
  createTransactionForm.value.Attachment = null;
};

const validateCreateTransaction = () => {
  const errors = {};
  
  if (!createTransactionForm.value.bank_account_id) {
    errors.bank_account_id = 'Please select a bank account';
  }
  
  if (!createTransactionForm.value.amount || createTransactionForm.value.amount <= 0) {
    errors.amount = 'Please enter a valid amount greater than 0';
  }
  
  if (!createTransactionForm.value.description.trim()) {
    errors.description = 'Please enter a description';
  }
  
  if (!createTransactionForm.value.reference.trim()) {
    errors.reference = 'Please enter a reference';
  }
  
  createTransactionErrors.value = errors;
  return Object.keys(errors).length === 0;
};

const createTransaction = async () => {
  if (!validateCreateTransaction()) return;
  
  createTransactionLoading.value = true;
  
  try {
    const formData = new FormData();
    
    // Add form fields
    formData.append('bank_account_id', createTransactionForm.value.bank_account_id);
    formData.append('transaction_type', createTransactionForm.value.transaction_type);
    formData.append('amount', createTransactionForm.value.amount);
    formData.append('description', createTransactionForm.value.description);
    formData.append('reference', createTransactionForm.value.reference);
    formData.append('transaction_date', createTransactionForm.value.transaction_date.toISOString().split('T')[0]);
    
    if (createTransactionForm.value.Designation) {
      formData.append('Designation', createTransactionForm.value.Designation);
    }
    
    if (createTransactionForm.value.Payer) {
      formData.append('Payer', createTransactionForm.value.Payer);
    }
    
    // Only append the file if it exists
    if (createTransactionForm.value.Attachment) {
      formData.append('Attachment', createTransactionForm.value.Attachment);
    }
    
    // Add coffre_id if selected
    if (createTransactionForm.value.coffre_id) {
      formData.append('coffre_id', createTransactionForm.value.coffre_id);
    }
    
    const result = await bankAccountTransactionService.create(formData);
    
    if (result.success) {
      showToast('success', 'Success', 'Transaction created successfully');
      showCreateTransactionModal.value = false;
      fetchTransactions(); // Refresh the list
    } else {
      if (result.errors) {
        createTransactionErrors.value = result.errors;
      } else {
        showToast('error', 'Error', result.message || 'Failed to create transaction');
      }
    }
  } catch (error) {
    console.error('Error creating transaction:', error);
    showToast('error', 'Error', 'An error occurred while creating the transaction');
  } finally {
    createTransactionLoading.value = false;
  }
};

// Bulk upload functionality
const showBulkUploadForm = () => {
  bulkUploadForm.value = {
    bank_account_id: selectedBankAccountId.value,
    file: null,
    description: ''
  };
  bulkUploadErrors.value = {};
  showBulkUploadModal.value = true;
  loadTemplate();
};

const loadTemplate = async () => {
  try {
    const result = await bankAccountTransactionService.downloadTemplate();
    if (result.success) {
      templateData.value = result.data;
    }
  } catch (error) {
    console.error('Error loading template:', error);
  }
};

const onBulkFileSelect = (event) => {
  const file = event.files[0];
  if (file) {
    bulkUploadForm.value.file = file;
    bulkUploadErrors.value.file = null;
  }
};

const onBulkFileRemove = () => {
  bulkUploadForm.value.file = null;
};

const validateBulkUpload = () => {
  const errors = {};
  
  if (!bulkUploadForm.value.bank_account_id) {
    errors.bank_account_id = 'Please select a bank account';
  }
  
  if (!bulkUploadForm.value.file) {
    errors.file = 'Please select a file to upload';
  }
  
  bulkUploadErrors.value = errors;
  return Object.keys(errors).length === 0;
};

const processBulkUpload = async () => {
  if (!validateBulkUpload()) return;
  
  bulkUploadLoading.value = true;
  
  try {
    const formData = new FormData();
    formData.append('bank_account_id', bulkUploadForm.value.bank_account_id);
    formData.append('file', bulkUploadForm.value.file);
    if (bulkUploadForm.value.description) {
      formData.append('description', bulkUploadForm.value.description);
    }
    
    const result = await bankAccountTransactionService.bulkUpload(formData);
    
    if (result.success) {
      showToast('success', 'Success', result.message);
      showBulkUploadModal.value = false;
      fetchTransactions(); // Refresh the list
    } else {
      if (result.errors) {
        bulkUploadErrors.value = result.errors;
      } else {
        showToast('error', 'Error', result.message);
      }
    }
  } catch (error) {
    console.error('Error uploading file:', error);
    showToast('error', 'Error', 'An error occurred during file upload');
  } finally {
    bulkUploadLoading.value = false;
  }
};

const cancelBulkUpload = () => {
  showBulkUploadModal.value = false;
  bulkUploadForm.value = {
    bank_account_id: null,
    file: null,
    description: ''
  };
  bulkUploadErrors.value = {};
};

const downloadTemplateFile = () => {
  if (!templateData.value) return;
  
  // Create CSV content
  const headers = templateData.value.headers.join(',');
  const rows = templateData.value.sample_data.map(row => 
    row.map(cell => `"${cell}"`).join(',')
  ).join('\n');
  
  const csvContent = `${headers}\n${rows}`;
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  link.setAttribute('href', url);
  link.setAttribute('download', 'bulk_upload_template.csv');
  link.style.visibility = 'hidden';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

// Watch for route changes
watch(() => route.query, () => {
  initializeFromRoute();
  fetchTransactions();
}, { immediate: false });

// Lifecycle
onMounted(() => {
  initializeFromRoute();
  fetchTransactions();
  // if (!selectedBankAccountId.value) {
    loadBankAccounts();
  
});
</script>

<template>
  <div class="tw-bg-gray-100 tw-min-h-screen">
    <div class="tw-bg-gradient-to-r tw-from-blue-700 tw-to-blue-400 tw-py-10 tw-rounded-b-2xl">
      <div class="tw-max-w-7xl tw-mx-auto tw-px-4 sm:tw-px-6 lg:tw-px-8">
        <div class="tw-flex tw-flex-col md:tw-flex-row tw-justify-between tw-items-center tw-gap-8 md:tw-gap-0">
          <div class="tw-flex tw-items-center tw-gap-4 tw-text-white">
            <i class="pi pi-list tw-text-5xl tw-opacity-90"></i>
            <div class="tw-flex tw-flex-col">
              <span class="tw-text-4xl tw-font-bold tw-leading-none">{{ pageTitle }}</span>
              <p class="tw-text-white/80 tw-mt-1 tw-text-lg tw-font-normal">
                {{ pageSubtitle }}
              </p>
            </div>
          </div>
          <div class="tw-flex tw-gap-2 tw-items-center tw-flex-wrap md:tw-flex-nowrap">
            <Button
              v-if="selectedBankAccountId"
              icon="pi pi-plus"
              label="Create Transaction"
              class="p-button-primary p-button-lg"
              @click="showCreateTransactionForm"
            />
            <Button
              v-if="selectedBankAccountId"
              icon="pi pi-upload"
              label="Bulk Upload"
              class="p-button-success p-button-lg"
              @click="showBulkUploadForm"
            />
            <Button
              v-if="selectedBankAccountId"
              icon="pi pi-arrow-left"
              label="Back to Accounts"
              class="p-button-secondary p-button-lg"
              @click="navigateBack"
            />
          </div>
        </div>
      </div>
    </div>

    <div v-if="selectedBankAccountId && accountInfo" class="tw-max-w-7xl tw-mx-auto tw-px-4 sm:tw-px-6 lg:tw-px-8 tw-py-4">
      <Card class="tw-rounded-xl tw-shadow-md tw-bg-gradient-to-r tw-from-gray-100 tw-to-gray-200 tw-p-6">
        <template #content>
          <div class="tw-flex tw-flex-col md:tw-flex-row tw-justify-between tw-items-center tw-gap-4">
            <div class="tw-flex tw-items-center tw-gap-4">
              <div class="tw-w-12 tw-h-12 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-text-white tw-text-2xl tw-bg-gradient-to-r tw-from-blue-700 tw-to-blue-400">
                <i class="pi pi-credit-card"></i>
              </div>
              <div class="tw-text-center md:tw-text-left">
                <h5 class="tw-text-xl tw-font-semibold tw-text-gray-900 tw-m-0">{{ accountInfo.account_name }}</h5>
                <p class="tw-text-gray-600 tw-mt-1 tw-m-0">{{ accountInfo.bank_name }} • {{ accountInfo.masked_account_number }}</p>
                <div class="tw-mt-2 tw-text-sm tw-text-gray-700 tw-font-semibold">
                  Current Balance:
                  <span :class="getBalanceClass(accountInfo.current_balance)">
                    {{ formatCurrency(accountInfo.current_balance, accountInfo.currency) }}
                  </span>
                </div>
              </div>
            </div>
            <div class="tw-flex tw-gap-2 tw-items-center">
              <Tag v-if="accountInfo.currency" :value="accountInfo.currency" severity="info" class="tw-text-xs" />
              <Tag 
                v-if="accountInfo.is_active !== undefined"
                :value="accountInfo.is_active ? 'Active' : 'Inactive'"
                :severity="accountInfo.is_active ? 'success' : 'danger'"
                class="tw-text-xs"
              />
            </div>
          </div>
        </template>
      </Card>
    </div>

    <div class="tw-max-w-7xl tw-mx-auto tw-px-4 sm:tw-px-6 lg:tw-px-8 tw-py-4">
      <div v-if="summary" class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-4 tw-gap-6 tw-mb-8">
        <Card class="tw-border-none tw-rounded-xl tw-shadow-md">
          <template #content>
            <div class="tw-flex tw-items-center tw-gap-4 tw-p-2">
              <div class="tw-w-12 tw-h-12 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-text-2xl tw-bg-gradient-to-r tw-from-blue-700 tw-to-blue-400">
                <i class="pi pi-list"></i>
              </div>
              <div>
                <div class="tw-text-gray-600 tw-text-sm">Total Transactions</div>
                <div class="tw-text-2xl tw-font-bold tw-text-gray-900">{{ summary.pending_count + summary.completed_count + summary.reconciled_count }}</div>
              </div>
            </div>
          </template>
        </Card>

        <Card class="tw-border-none tw-rounded-xl tw-shadow-md">
          <template #content>
            <div class="tw-flex tw-items-center tw-gap-4 tw-p-2">
              <div class="tw-w-12 tw-h-12 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-text-2xl tw-bg-gradient-to-r tw-from-green-500 tw-to-emerald-500">
                <i class="pi pi-arrow-up"></i>
              </div>
              <div>
                <div class="tw-text-gray-600 tw-text-sm">Total Credits</div>
                <div class="tw-text-2xl tw-font-bold tw-text-green-500">{{ formatCurrency(summary.credit_amount) }}</div>
              </div>
            </div>
          </template>
        </Card>

        <Card class="tw-border-none tw-rounded-xl tw-shadow-md">
          <template #content>
            <div class="tw-flex tw-items-center tw-gap-4 tw-p-2">
              <div class="tw-w-12 tw-h-12 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-text-2xl tw-bg-gradient-to-r tw-from-red-500 tw-to-rose-500">
                <i class="pi pi-arrow-down"></i>
              </div>
              <div>
                <div class="tw-text-gray-600 tw-text-sm">Total Debits</div>
                <div class="tw-text-2xl tw-font-bold tw-text-red-500">{{ formatCurrency(summary.debit_amount) }}</div>
              </div>
            </div>
          </template>
        </Card>

        <Card class="tw-border-none tw-rounded-xl tw-shadow-md">
          <template #content>
            <div class="tw-flex tw-items-center tw-gap-4 tw-p-2">
              <div class="tw-w-12 tw-h-12 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-text-white tw-text-2xl tw-bg-gradient-to-r tw-from-blue-500 tw-to-indigo-500">
                <i class="pi pi-check-circle"></i>
              </div>
              <div>
                <div class="tw-text-gray-600 tw-text-sm">Completed</div>
                <div class="tw-text-2xl tw-font-bold tw-text-blue-500">{{ summary.completed_count }}</div>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <Card class="tw-mb-8 tw-border-none tw-rounded-xl tw-shadow-md">
        <template #content>
          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-6 tw-items-center">
            <div class="tw-col-span-1 lg:tw-col-span-2">
              <div class="p-inputgroup">
                <span class="p-inputgroup-addon tw-rounded-l-lg">
                  <i class="pi pi-search"></i>
                </span>
                <InputText
                  v-model="searchQuery"
                  placeholder="Search by reference or description..."
                  @input="debouncedSearch"
                  class="tw-w-full"
                />
              </div>
            </div>
            <div class="tw-col-span-1 tw-flex tw-flex-wrap tw-gap-4 tw-items-center tw-justify-end">
              <Dropdown
                v-if="!selectedBankAccountId"
                v-model="filters.bank_account_id"
                :options="bankAccountOptions"
                option-label="label"
                option-value="value"
                placeholder="All Accounts"
                class="tw-min-w-[140px] tw-flex-1"
                @change="fetchTransactions"
                showClear
              />
              <Dropdown
                v-model="filters.transaction_type"
                :options="typeOptions"
                option-label="label"
                option-value="value"
                placeholder="All Types"
                class="tw-min-w-[140px] tw-flex-1"
                @change="fetchTransactions"
                showClear
              />
              <Dropdown
                v-model="filters.status"
                :options="statusOptions"
                option-label="label"
                option-value="value"
                placeholder="All Status"
                class="tw-min-w-[140px] tw-flex-1"
                @change="fetchTransactions"
                showClear
              />
            </div>
          </div>
          <div class="tw-flex tw-justify-end tw-mt-4 tw-gap-2">
            <Dropdown
              v-model="perPage"
              :options="perPageOptions"
              option-label="label"
              option-value="value"
              @change="fetchTransactions"
              class="tw-min-w-[150px]"
            />
            <Button
              icon="pi pi-refresh"
              class="p-button-outlined"
              @click="fetchTransactions"
              :loading="loading"
              v-tooltip.top="'Refresh'"
            />
          </div>
        </template>
      </Card>

      <div v-if="loading" class="tw-flex tw-justify-center tw-items-center tw-py-16">
        <ProgressSpinner animationDuration=".5s" strokeWidth="4" />
      </div>

      <div v-else-if="transactions.length > 0">
        <DataTable
          :value="transactions"
          :rows="perPage"
          responsive-layout="scroll"
          class="tw-rounded-xl tw-overflow-hidden tw-shadow-md"
          :row-hover="true"
            @row-click="showTransactionDetails"
        >
          <template #header>
            <div class="tw-flex tw-justify-between tw-items-center tw-py-4">
              <h3 class="tw-text-gray-900 tw-font-bold tw-text-xl">{{ selectedBankAccountId ? 'Account Transactions' : 'Transaction History' }}</h3>
            </div>
          </template>

          <Column field="reference" header="Reference" :sortable="true" style="width: 12%">
            <template #body="{ data }">
              <div class="tw-font-mono tw-font-semibold tw-text-indigo-600">{{ data.reference }}</div>
            </template>
          </Column>

          <Column v-if="!selectedBankAccountId" field="bank_account" header="Account" :sortable="false" style="width: 20%">
            <template #body="{ data }">
              <div class="tw-flex tw-flex-col tw-gap-1">
                <div class="tw-font-bold tw-text-gray-900 tw-text-sm">{{ data.bank_account?.account_name || 'N/A' }}</div>
                <div class="tw-font-mono tw-text-gray-600 tw-text-xs">{{ data.bank_account?.account_number || 'N/A' }}</div>
                <div class="tw-font-normal tw-text-gray-400 tw-text-xs">{{ data.bank_account?.bank_name || 'Unknown Bank' }}</div>
              </div>
            </template>
          </Column>

          <Column field="transaction_type" header="Type" :sortable="true" style="width: 10%">
            <template #body="{ data }">
              <Tag
                :value="data.transaction_type_text"
                :severity="data.transaction_type === 'credit' ? 'success' : 'danger'"
                :icon="data.transaction_type === 'credit' ? 'pi pi-arrow-up' : 'pi pi-arrow-down'"
              />
            </template>
          </Column>

          <Column field="amount" header="Amount" :sortable="true" style="width: 12%">
            <template #body="{ data }">
              <div class="tw-font-bold tw-text-lg" :class="getAmountClass(data.transaction_type)">
                <span class="tw-font-normal">{{ data.transaction_type === 'credit' ? '+' : '-' }}</span>{{ formatCurrency(data.amount, data.bank_account?.currency) }}
              </div>
            </template>
          </Column>

          <Column field="status" header="Status" :sortable="true" style="width: 10%">
            <template #body="{ data }">
              <Tag
                :value="data.status_text"
                :severity="data.status_color"
                :icon="getStatusIcon(data.status)"
              />
            </template>
          </Column>

          <Column field="transaction_date" header="Date" :sortable="true" style="width: 12%">
            <template #body="{ data }">
              <div class="tw-flex tw-flex-col tw-gap-1">
                <div class="tw-font-semibold tw-text-gray-900 tw-text-sm">{{ formatDate(data.transaction_date) }}</div>
                <div class="tw-text-gray-500 tw-text-xs">{{ formatTime(data.transaction_date) }}</div>
              </div>
            </template>
          </Column>

          <Column field="accepted_by" header="Accepted By" :sortable="false" style="width: 12%">
            <template #body="{ data }">
              <div class="tw-flex tw-flex-col tw-gap-1">
                <div class="tw-font-medium tw-text-gray-900 tw-text-sm">{{ data.accepted_by?.name || 'N/A' }}</div>
                <div v-if="data.accepted_by?.email" class="tw-text-gray-500 tw-text-xs">{{ data.accepted_by.email }}</div>
              </div>
            </template>
          </Column>

          <Column field="description" header="Description" :sortable="false" :style="selectedBankAccountId ? 'width: 20%' : 'width: 12%'">
            <template #body="{ data }">
              <div v-if="data.description" class="tw-text-gray-600 tw-text-sm tw-truncate" v-tooltip.top="data.description">
                {{ truncateText(data.description, 30) }}
              </div>
              <span v-else class="tw-text-gray-400 tw-italic tw-text-sm">No description</span>
            </template>
          </Column>

          <Column header="Actions" :exportable="false" style="width: 10%">
            <template #body="{ data }">
              <div class="tw-flex tw-gap-1">
                <Button
                  v-if="data.status === 'pending'"
                  icon="pi pi-check"
                  class="p-button-rounded p-button-success p-button-sm"
                  v-tooltip.top="'Validate Transaction'"
                  @click.stop="validateTransaction(data)"
                />
                <span v-else class="tw-text-gray-400 tw-text-sm">No actions</span>
              </div>
            </template>
          </Column>

        </DataTable>
      </div>

      <div v-else-if="!loading" class="tw-text-center tw-p-12 tw-rounded-xl tw-bg-gray-50 tw-border-2 tw-border-dashed tw-border-gray-300">
        <i class="pi pi-list tw-text-6xl tw-text-gray-300 tw-mb-4"></i>
        <h4 class="tw-text-2xl tw-text-gray-800 tw-font-semibold tw-mb-2">No transactions found</h4>
        <p class="tw-text-gray-500 tw-mb-8">
          {{ selectedBankAccountId ? 'This account has no transactions yet.' : 'There are no transactions to display.' }}
        </p>
        <Button 
          v-if="selectedBankAccountId" 
          icon="pi pi-arrow-left" 
          label="Back to Accounts" 
          class="p-button-secondary"
          @click="navigateBack" 
        />
      </div>

      <Paginator
        v-if="!loading && meta && meta.total > perPage"
        :rows="perPage"
        :total-records="meta.total"
        :rows-per-page-options="[10, 15, 25, 50]"
        @page="onPageChange"
        class="tw-mt-6"
      />
    </div>

    <Dialog
      v-model:visible="showValidationModal"
      :modal="true"
      header="Validate Transaction"
      :style="{ width: '600px' }"
      class="p-fluid"
      :closable="false"
    >
      <div v-if="selectedTransaction" class="tw-space-y-6">
        <div class="tw-bg-gray-50 tw-p-4 tw-rounded-lg">
          <h4 class="tw-text-lg tw-font-semibold tw-text-gray-800 tw-mb-3">Transaction Details</h4>
          <div class="tw-grid tw-grid-cols-2 tw-gap-4 tw-text-sm">
            <div>
              <span class="tw-font-medium tw-text-gray-600">Reference:</span>
              <span class="tw-ml-2 tw-font-mono">{{ selectedTransaction.reference }}</span>
            </div>
            <div>
              <span class="tw-font-medium tw-text-gray-600">Amount:</span>
              <span class="tw-ml-2 tw-font-semibold" :class="getAmountClass(selectedTransaction.transaction_type)">
                {{ formatCurrency(selectedTransaction.amount, selectedTransaction.bank_account?.currency) }}
              </span>
            </div>
            <div class="tw-col-span-2">
              <span class="tw-font-medium tw-text-gray-600">Description:</span>
              <span class="tw-ml-2">{{ selectedTransaction.description || 'No description' }}</span>
            </div>
          </div>
        </div>

        <div class="tw-space-y-4">
          <h4 class="tw-text-lg tw-font-semibold tw-text-gray-800">Validation Information</h4>
          
          <div class="tw-space-y-2">
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">Payment Date</label>
            <Calendar
              v-model="validationForm.Payment_date"
              date-format="yy-mm-dd"
              show-icon
              :class="{ 'p-invalid': validationErrors.Payment_date }"
              placeholder="Select payment date"
            />
            <small v-if="validationErrors.Payment_date" class="p-error">{{ validationErrors.Payment_date[0] }}</small>
          </div>

          <div class="tw-space-y-2">
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">Reference Validation</label>
            <InputText
              v-model="validationForm.reference_validation"
              :class="{ 'p-invalid': validationErrors.reference_validation }"
              placeholder="Enter validation reference"
            />
            <small v-if="validationErrors.reference_validation" class="p-error">{{ validationErrors.reference_validation[0] }}</small>
          </div>

          <div class="tw-space-y-2">
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">Reason for Validation</label>
            <Textarea
              v-model="validationForm.Reason_validation"
              :class="{ 'p-invalid': validationErrors.Reason_validation }"
              placeholder="Enter reason for validation"
              rows="3"
              auto-resize
            />
            <small v-if="validationErrors.Reason_validation" class="p-error">{{ validationErrors.Reason_validation[0] }}</small>
          </div>

          <div class="tw-space-y-2">
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">Validation Attachment (Optional)</label>
            <FileUpload
              ref="fileUpload"
              mode="basic"
              :multiple="false"
              accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
              :maxFileSize="10485760"
              :auto="false"
              choose-label="Choose File"
              :class="{ 'p-invalid': validationErrors.Attachment_validation }"
              @select="onFileSelect"
              @remove="onFileRemove"
              :show-upload-button="false"
              :show-cancel-button="false"
            />
            <small class="tw-text-xs tw-text-gray-500">Supported formats: PDF, JPG, PNG, DOC, DOCX (Max 10MB)</small>
            <small v-if="validationErrors.Attachment_validation" class="p-error">{{ validationErrors.Attachment_validation[0] }}</small>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="tw-flex tw-justify-end tw-gap-3">
          <Button
            label="Cancel"
            icon="pi pi-times"
            class="p-button-text"
            @click="cancelValidation"
            :disabled="validationLoading"
          />
          <Button
            label="Validate Transaction"
            icon="pi pi-check"
            class="p-button-success"
            @click="confirmValidation"
            :loading="validationLoading"
          />
        </div>
      </template>
    </Dialog>

    <Dialog
      v-model:visible="showDetailModal"
      :modal="true"
      header="Transaction Details"
      :style="{ width: '900px' }"
      class="p-fluid"
      :closable="true"
    >
      <div v-if="selectedTransactionDetail" class="tw-space-y-6">
        <!-- Transaction Overview Card -->
        <Card class="tw-border tw-border-gray-200 tw-rounded-lg">
          <template #header>
            <div class="tw-bg-gradient-to-r tw-from-blue-50 tw-to-blue-100 tw-p-4 tw-rounded-t-lg">
              <h3 class="tw-text-lg tw-font-semibold tw-text-blue-900 tw-m-0 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-info-circle"></i>
                Transaction Overview
              </h3>
            </div>
          </template>
          <template #content>
            <div class="tw-p-4">
              <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-4">
                <div class="tw-bg-gray-50 tw-p-3 tw-rounded-lg">
                  <div class="tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wide tw-mb-1">Reference</div>
                  <div class="tw-font-mono tw-font-semibold tw-text-gray-900">{{ selectedTransactionDetail.reference || 'N/A' }}</div>
                </div>
                <div class="tw-bg-gray-50 tw-p-3 tw-rounded-lg">
                  <div class="tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wide tw-mb-1">Amount</div>
                  <div class="tw-font-bold tw-text-lg" :class="getAmountClass(selectedTransactionDetail.transaction_type)">
                    {{ formatCurrency(selectedTransactionDetail.amount, selectedTransactionDetail.bank_account?.currency || 'DZD') }}
                  </div>
                </div>
                <div class="tw-bg-gray-50 tw-p-3 tw-rounded-lg">
                  <div class="tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wide tw-mb-1">Status</div>
                  <Tag :value="selectedTransactionDetail.status_text || selectedTransactionDetail.status" 
                       :severity="selectedTransactionDetail.status_color || 'info'" />
                </div>
                <div class="tw-bg-gray-50 tw-p-3 tw-rounded-lg">
                  <div class="tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wide tw-mb-1">Type</div>
                  <div class="tw-font-medium tw-text-gray-900">{{ selectedTransactionDetail.transaction_type_text || selectedTransactionDetail.transaction_type }}</div>
                </div>
                <div class="tw-bg-gray-50 tw-p-3 tw-rounded-lg">
                  <div class="tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wide tw-mb-1">Transaction Date</div>
                  <div class="tw-font-medium tw-text-gray-900">
                    {{ selectedTransactionDetail.transaction_date ? formatDate(selectedTransactionDetail.transaction_date) : 'N/A' }}
                    <div class="tw-text-xs tw-text-gray-500">
                      {{ selectedTransactionDetail.transaction_date ? formatTime(selectedTransactionDetail.transaction_date) : '' }}
                    </div>
                  </div>
                </div>
                <div class="tw-bg-gray-50 tw-p-3 tw-rounded-lg">
                  <div class="tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wide tw-mb-1">Current Balance</div>
                  <div class="tw-font-semibold" :class="getBalanceClass(selectedTransactionDetail.current_balance)">
                    {{ formatCurrency(selectedTransactionDetail.current_balance, selectedTransactionDetail.bank_account?.currency || 'DZD') }}
                  </div>
                </div>
              </div>
            </div>
          </template>
        </Card>

        <!-- Transaction Details Card -->
        <Card class="tw-border tw-border-gray-200 tw-rounded-lg">
          <template #header>
            <div class="tw-bg-gradient-to-r tw-from-green-50 tw-to-green-100 tw-p-4 tw-rounded-t-lg">
              <h3 class="tw-text-lg tw-font-semibold tw-text-green-900 tw-m-0 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-list"></i>
                Transaction Information
              </h3>
            </div>
          </template>
          <template #content>
            <div class="tw-p-4">
              <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
                <div>
                  <div class="tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-1">Designation</div>
                  <div class="tw-font-medium tw-text-gray-900">{{ selectedTransactionDetail.Designation || selectedTransactionDetail.designation || 'N/A' }}</div>
                </div>
                <div>
                  <div class="tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-1">Payer</div>
                  <div class="tw-font-medium tw-text-gray-900">{{ selectedTransactionDetail.Payer || selectedTransactionDetail.payer || 'N/A' }}</div>
                </div>
                <div class="tw-col-span-1 md:tw-col-span-2">
                  <div class="tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-1">Description</div>
                  <div class="tw-font-medium tw-text-gray-900 tw-bg-gray-50 tw-p-3 tw-rounded">{{ selectedTransactionDetail.description || 'No description provided' }}</div>
                </div>
                <div>
                  <div class="tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-1">Bank Account</div>
                  <div class="tw-font-medium tw-text-gray-900">
                    {{ selectedTransactionDetail.bank_account?.account_name || 'N/A' }}
                    <div class="tw-text-xs tw-text-gray-500">{{ selectedTransactionDetail.bank_account?.bank_name || '' }}</div>
                  </div>
                </div>
                <div>
                  <div class="tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-1">Account Number</div>
                  <div class="tw-font-mono tw-text-sm tw-text-gray-900">{{ selectedTransactionDetail.bank_account?.masked_account_number || 'N/A' }}</div>
                </div>
              </div>
            </div>
          </template>
        </Card>

        <!-- Validation Information Card -->
        <Card v-if="selectedTransactionDetail.status !== 'pending'" class="tw-border tw-border-gray-200 tw-rounded-lg">
          <template #header>
            <div class="tw-bg-gradient-to-r tw-from-purple-50 tw-to-purple-100 tw-p-4 tw-rounded-t-lg">
              <h3 class="tw-text-lg tw-font-semibold tw-text-purple-900 tw-m-0 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-verified"></i>
                Validation Information
              </h3>
            </div>
          </template>
          <template #content>
            <div class="tw-p-4">
              <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
                <div>
                  <div class="tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-1">Payment Date</div>
                  <div class="tw-font-medium tw-text-gray-900">{{ selectedTransactionDetail.Payment_date ? formatDate(selectedTransactionDetail.Payment_date) : 'N/A' }}</div>
                </div>
                <div>
                  <div class="tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-1">Validation Reference</div>
                  <div class="tw-font-mono tw-font-medium tw-text-gray-900">{{ selectedTransactionDetail.reference_validation || 'N/A' }}</div>
                </div>
                <div class="tw-col-span-1 md:tw-col-span-2">
                  <div class="tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-1">Validation Reason</div>
                  <div class="tw-font-medium tw-text-gray-900 tw-bg-gray-50 tw-p-3 tw-rounded">{{ selectedTransactionDetail.Reason_validation || selectedTransactionDetail.reason_validation || 'No reason provided' }}</div>
                </div>
                <div class="tw-col-span-1 md:tw-col-span-2">
                  <div class="tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-1">Accepted By</div>
                  <div class="tw-flex tw-items-center tw-gap-3">
                    <div class="tw-w-8 tw-h-8 tw-rounded-full tw-bg-blue-100 tw-flex tw-items-center tw-justify-center">
                      <i class="pi pi-user tw-text-blue-600 tw-text-sm"></i>
                    </div>
                    <div>
                      <div class="tw-font-medium tw-text-gray-900">{{ selectedTransactionDetail.accepted_by?.name || 'N/A' }}</div>
                      <div class="tw-text-sm tw-text-gray-500">{{ selectedTransactionDetail.accepted_by?.email || '' }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </template>
        </Card>

        <!-- Attachments Card -->
        <Card class="tw-border tw-border-gray-200 tw-rounded-lg">
          <template #header>
            <div class="tw-bg-gradient-to-r tw-from-orange-50 tw-to-orange-100 tw-p-4 tw-rounded-t-lg">
              <h3 class="tw-text-lg tw-font-semibold tw-text-orange-900 tw-m-0 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-paperclip"></i>
                Attachments
              </h3>
            </div>
          </template>
          <template #content>
            <div class="tw-p-4">
              <!-- Validation Attachment -->
              <div class="tw-mb-6">
                <h4 class="tw-text-md tw-font-semibold tw-text-gray-700 tw-mb-3 tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-check-circle tw-text-green-600"></i>
                  Validation Attachment
                </h4>
                <div v-if="attachmentUrl" class="tw-border tw-border-gray-200 tw-rounded-lg tw-p-4 tw-bg-gray-50">
                  <div v-if="isAttachmentImage" class="tw-text-center">
                    <img :src="attachmentUrl" alt="Validation attachment" class="tw-max-w-full tw-max-h-64 tw-mx-auto tw-rounded tw-shadow-md" />
                    <div class="tw-mt-3 tw-flex tw-justify-center tw-gap-2">
                      <Button
                        icon="pi pi-external-link"
                        label="View Full Size"
                        class="p-button-sm p-button-outlined"
                        @click="window.open(attachmentUrl, '_blank')"
                      />
                      <Button
                        icon="pi pi-download"
                        label="Download"
                        class="p-button-sm p-button-success"
                        @click="downloadAttachment(attachmentUrl, `validation_${selectedTransactionDetail.reference}`)"
                      />
                    </div>
                  </div>
                  <div v-else class="tw-flex tw-items-center tw-justify-between">
                    <div class="tw-flex tw-items-center tw-gap-3">
                      <i :class="getFileIcon(attachmentUrl)" class="tw-text-2xl tw-text-gray-600"></i>
                      <div>
                        <div class="tw-font-medium tw-text-gray-900">Validation Document</div>
                        <div class="tw-text-sm tw-text-gray-500">{{ attachmentUrl.split('/').pop() }}</div>
                      </div>
                    </div>
                    <div class="tw-flex tw-gap-2">
                      <Button
                        icon="pi pi-eye"
                        label="View"
                        class="p-button-sm p-button-outlined"
                        @click="window.open(attachmentUrl, '_blank')"
                      />
                      <Button
                        icon="pi pi-download"
                        label="Download"
                        class="p-button-sm p-button-success"
                        @click="downloadAttachment(attachmentUrl, `validation_${selectedTransactionDetail.reference}`)"
                      />
                    </div>
                  </div>
                </div>
                <div v-else class="tw-text-center tw-py-8 tw-text-gray-500">
                  <i class="pi pi-file-o tw-text-4xl tw-text-gray-300 tw-mb-2"></i>
                  <div>No validation attachment available</div>
                </div>
              </div>

              <!-- Original Attachment -->
              <div>
                <h4 class="tw-text-md tw-font-semibold tw-text-gray-700 tw-mb-3 tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-file tw-text-blue-600"></i>
                  Original Attachment
                </h4>
                <div v-if="selectedTransactionDetail.Attachment || selectedTransactionDetail.attachment" class="tw-border tw-border-gray-200 tw-rounded-lg tw-p-4 tw-bg-gray-50">
                  <div class="tw-flex tw-items-center tw-justify-between">
                    <div class="tw-flex tw-items-center tw-gap-3">
                      <i :class="getFileIcon(selectedTransactionDetail.Attachment || selectedTransactionDetail.attachment)" class="tw-text-2xl tw-text-gray-600"></i>
                      <div>
                        <div class="tw-font-medium tw-text-gray-900">Original Document</div>
                        <div class="tw-text-sm tw-text-gray-500">{{ (selectedTransactionDetail.Attachment || selectedTransactionDetail.attachment)?.split('/').pop() }}</div>
                      </div>
                    </div>
                    <div class="tw-flex tw-gap-2">
                      <Button
                        icon="pi pi-eye"
                        label="View"
                        class="p-button-sm p-button-outlined"
                        @click="window.open((selectedTransactionDetail.Attachment || selectedTransactionDetail.attachment).startsWith('http') ? (selectedTransactionDetail.Attachment || selectedTransactionDetail.attachment) : `/storage/${selectedTransactionDetail.Attachment || selectedTransactionDetail.attachment}`, '_blank')"
                      />
                      <Button
                        icon="pi pi-download"
                        label="Download"
                        class="p-button-sm p-button-success"
                        @click="downloadAttachment((selectedTransactionDetail.Attachment || selectedTransactionDetail.attachment).startsWith('http') ? (selectedTransactionDetail.Attachment || selectedTransactionDetail.attachment) : `/storage/${selectedTransactionDetail.Attachment || selectedTransactionDetail.attachment}`, `original_${selectedTransactionDetail.reference}`)"
                      />
                    </div>
                  </div>
                </div>
                <div v-else class="tw-text-center tw-py-8 tw-text-gray-500">
                  <i class="pi pi-file-o tw-text-4xl tw-text-gray-300 tw-mb-2"></i>
                  <div>No original attachment available</div>
                </div>
              </div>
            </div>
          </template>
        </Card>

        <!-- System Information Card -->
        <Card class="tw-border tw-border-gray-200 tw-rounded-lg">
          <template #header>
            <div class="tw-bg-gradient-to-r tw-from-gray-50 tw-to-gray-100 tw-p-4 tw-rounded-t-lg">
              <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-m-0 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-cog"></i>
                System Information
              </h3>
            </div>
          </template>
          <template #content>
            <div class="tw-p-4">
              <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-4">
                <div>
                  <div class="tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-1">Transaction ID</div>
                  <div class="tw-font-mono tw-text-sm tw-text-gray-900">{{ selectedTransactionDetail.id || 'N/A' }}</div>
                </div>
                <div>
                  <div class="tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-1">Created At</div>
                  <div class="tw-text-sm tw-text-gray-900">
                    {{ selectedTransactionDetail.created_at ? formatDate(selectedTransactionDetail.created_at) : 'N/A' }}
                    <div class="tw-text-xs tw-text-gray-500">
                      {{ selectedTransactionDetail.created_at ? formatTime(selectedTransactionDetail.created_at) : '' }}
                    </div>
                  </div>
                </div>
                <div>
                  <div class="tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-1">Updated At</div>
                  <div class="tw-text-sm tw-text-gray-900">
                    {{ selectedTransactionDetail.updated_at ? formatDate(selectedTransactionDetail.updated_at) : 'N/A' }}
                    <div class="tw-text-xs tw-text-gray-500">
                      {{ selectedTransactionDetail.updated_at ? formatTime(selectedTransactionDetail.updated_at) : '' }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <template #footer>
        <div class="tw-flex tw-justify-between tw-items-center">
          <div class="tw-flex tw-gap-2">
            <Button
              v-if="selectedTransactionDetail.status === 'pending'"
              icon="pi pi-check"
              label="Validate Transaction"
              class="p-button-success"
              @click="validateTransaction(selectedTransactionDetail); showDetailModal = false"
            />
          </div>
          <Button
            label="Close"
            icon="pi pi-times"
            class="p-button-secondary"
            @click="showDetailModal = false"
          />
        </div>
      </template>
    </Dialog>

    <!-- Bulk Upload Modal -->
    <Dialog
      v-model:visible="showBulkUploadModal"
      :modal="true"
      header="Bulk Upload Transactions"
      :style="{ width: '800px' }"
      class="p-fluid"
      :closable="false"
    >
      <div class="tw-space-y-6">
        <!-- Template Information -->
        <Card class="tw-border tw-border-blue-200 tw-rounded-lg tw-bg-blue-50">
          <template #content>
            <div class="tw-p-4">
              <h4 class="tw-text-lg tw-font-semibold tw-text-blue-900 tw-mb-3 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-info-circle"></i>
                File Format Requirements
              </h4>
              <div class="tw-text-sm tw-text-blue-800 tw-space-y-2">
                <p>Your file must contain the following columns in this exact order:</p>
                <div class="tw-bg-white tw-p-3 tw-rounded tw-border">
                  <div class="tw-grid tw-grid-cols-4 tw-gap-2 tw-font-semibold tw-text-gray-700">
                    <div>Name</div>
                    <div>Description</div>
                    <div>Amount</div>
                    <div>Reference</div>
                  </div>
                </div>
                <p class="tw-mt-2">
                  <strong>Supported formats:</strong> CSV, Excel (.xlsx, .xls) • 
                  <strong>Max file size:</strong> 10MB
                </p>
              </div>
              
              <div class="tw-flex tw-gap-3 tw-mt-4">
                <Button
                  icon="pi pi-download"
                  label="Download Template"
                  class="p-button-sm p-button-outlined"
                  @click="downloadTemplateFile"
                />
                <Button
                  icon="pi pi-eye"
                  label="Preview Template"
                  class="p-button-sm p-button-outlined"
                  @click="showTemplatePreview = !showTemplatePreview"
                />
              </div>
              
              <!-- Template Preview -->
              <div v-if="showTemplatePreview && templateData" class="tw-mt-4">
                <h5 class="tw-text-sm tw-font-semibold tw-text-blue-900 tw-mb-2">Sample Data:</h5>
                <div class="tw-bg-white tw-p-3 tw-rounded tw-border tw-overflow-x-auto">
                  <table class="tw-w-full tw-text-xs">
                    <thead>
                      <tr class="tw-bg-gray-100">
                        <th v-for="header in templateData.headers" :key="header" class="tw-p-2 tw-text-left tw-font-semibold">
                          {{ header }}
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(row, index) in templateData.sample_data" :key="index" class="tw-border-t">
                        <td v-for="(cell, cellIndex) in row" :key="cellIndex" class="tw-p-2">
                          {{ cell }}
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </template>
        </Card>

        <!-- Upload Form -->
        <div class="tw-space-y-4">
          <h4 class="tw-text-lg tw-font-semibold tw-text-gray-800">Upload Details</h4>
          
          <div class="tw-space-y-2">
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">Bank Account *</label>
            <Dropdown
              v-model="bulkUploadForm.bank_account_id"
              :options="bankAccountOptions"
              option-label="label"
              option-value="value"
              placeholder="Select bank account"
              :class="{ 'p-invalid': bulkUploadErrors.bank_account_id }"
              class="tw-w-full"
            />
            <small v-if="bulkUploadErrors.bank_account_id" class="p-error">{{ bulkUploadErrors.bank_account_id }}</small>
          </div>

          <div class="tw-space-y-2">
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">Upload File *</label>
            <FileUpload
              mode="basic"
              :multiple="false"
              accept=".csv,.xlsx,.xls"
              :maxFileSize="10485760"
              :auto="false"
              choose-label="Choose File"
              :class="{ 'p-invalid': bulkUploadErrors.file }"
              @select="onBulkFileSelect"
              @remove="onBulkFileRemove"
              :show-upload-button="false"
              :show-cancel-button="false"
            />
            <small class="tw-text-xs tw-text-gray-500">Accepted formats: CSV, Excel (.xlsx, .xls) - Max 10MB</small>
            <small v-if="bulkUploadErrors.file" class="p-error">{{ bulkUploadErrors.file }}</small>
          </div>
          
          <div class="tw-space-y-2">
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">Description (Optional)</label>
            <Textarea
              v-model="bulkUploadForm.description"
              placeholder="Enter a description for this bulk upload"
              rows="3"
              auto-resize
            />
          </div>
        </div>
      </div>

      <template #footer>
        <div class="tw-flex tw-justify-end tw-gap-3">
          <Button
            label="Cancel"
            icon="pi pi-times"
            class="p-button-text"
            @click="cancelBulkUpload"
            :disabled="bulkUploadLoading"
          />
          <Button
            label="Upload & Process"
            icon="pi pi-upload"
            class="p-button-success"
            @click="processBulkUpload"
            :loading="bulkUploadLoading"
          />
        </div>
      </template>
    </Dialog>

    <!-- Pack Users Modal -->
    <Dialog
      v-model:visible="showPackUsersModal"
      :modal="true"
      header="Pack Transaction Details"
      :style="{ width: '1000px' }"
      class="p-fluid"
      :closable="true"
      @hide="closePackUsersModal"
    >
      <div v-if="selectedPack" class="tw-space-y-6">
        <!-- Pack Overview Card -->
        <Card class="tw-border tw-border-gray-200 tw-rounded-lg">
          <template #header>
            <div class="tw-bg-gradient-to-r tw-from-purple-50 tw-to-purple-100 tw-p-4 tw-rounded-t-lg">
              <h3 class="tw-text-lg tw-font-semibold tw-text-purple-900 tw-m-0 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-box"></i>
                Pack Transaction Summary
              </h3>
            </div>
          </template>
          <template #content>
            <div class="tw-p-4">
              <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-4">
                <div class="tw-bg-gray-50 tw-p-3 tw-rounded-lg">
                  <div class="tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wide tw-mb-1">Transaction Reference</div>
                  <div class="tw-font-mono tw-font-semibold tw-text-gray-900">{{ selectedPack.reference || 'N/A' }}</div>
                </div>
                <div class="tw-bg-gray-50 tw-p-3 tw-rounded-lg">
                  <div class="tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wide tw-mb-1">Total Amount</div>
                  <div class="tw-font-bold tw-text-lg tw-text-green-600">
                    {{ formatCurrency(selectedPack.amount, selectedPack.bank_account?.currency || 'DZD') }}
                  </div>
                </div>
                <div class="tw-bg-gray-50 tw-p-3 tw-rounded-lg">
                  <div class="tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wide tw-mb-1">Recipients Count</div>
                  <div class="tw-font-bold tw-text-lg tw-text-blue-600">{{ packUsers.length }}</div>
                </div>
              </div>
              <div v-if="selectedPack.description" class="tw-mt-4">
                <div class="tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-1">Description</div>
                <div class="tw-font-medium tw-text-gray-900 tw-bg-gray-50 tw-p-3 tw-rounded">{{ selectedPack.description }}</div>
              </div>
            </div>
          </template>
        </Card>

        <!-- Users Table Card -->
        <Card class="tw-border tw-border-gray-200 tw-rounded-lg">
          <template #header>
            <div class="tw-bg-gradient-to-r tw-from-blue-50 tw-to-blue-100 tw-p-4 tw-rounded-t-lg">
              <h3 class="tw-text-lg tw-font-semibold tw-text-blue-900 tw-m-0 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-users"></i>
                Transaction Recipients
              </h3>
            </div>
          </template>
          <template #content>
            <div class="tw-p-4">
              <div v-if="loadingPackUsers" class="tw-flex tw-justify-center tw-items-center tw-py-8">
                <ProgressSpinner animationDuration=".5s" strokeWidth="4" />
              </div>
              
              <div v-else-if="packUsers.length > 0">
                <DataTable
                  :value="packUsers"
                  responsive-layout="scroll"
                  class="tw-rounded-lg tw-overflow-hidden tw-shadow-sm"
                  :rows="10"
                  :paginator="packUsers.length > 10"
                  paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                  currentPageReportTemplate="Showing {first} to {last} of {totalRecords} entries"
                >
                  <Column field="employee_id" header="Employee ID" :sortable="true" style="width: 20%">
                    <template #body="{ data }">
                      <div class="tw-flex tw-items-center tw-gap-3">
                        <div class="tw-w-8 tw-h-8 tw-rounded-full tw-bg-blue-100 tw-flex tw-items-center tw-justify-center">
                          <i class="pi pi-user tw-text-blue-600 tw-text-sm"></i>
                        </div>
                        <div>
                          <div class="tw-font-semibold tw-text-gray-900">{{ data.employee_id || 'N/A' }}</div>
                        </div>
                      </div>
                    </template>
                  </Column>

                  <Column field="name" header="Name" :sortable="true" style="width: 25%">
                    <template #body="{ data }">
                      <div class="tw-text-gray-700 tw-font-medium">{{ data.name || 'N/A' }}</div>
                    </template>
                  </Column>

                  <Column field="description" header="Description" :sortable="false" style="width: 25%">
                    <template #body="{ data }">
                      <div class="tw-text-gray-700">{{ data.description || 'No description' }}</div>
                    </template>
                  </Column>

                  <Column field="amount" header="Amount" :sortable="true" style="width: 15%">
                    <template #body="{ data }">
                      <div class="tw-font-semibold tw-text-green-600">
                        {{ formatCurrency(data.amount, selectedPack.bank_account?.currency || 'DZD') }}
                      </div>
                    </template>
                  </Column>

                  <Column field="reference" header="Reference" :sortable="true" style="width: 15%">
                    <template #body="{ data }">
                      <div class="tw-font-mono tw-text-sm tw-text-gray-700">{{ data.reference || 'N/A' }}</div>
                    </template>
                  </Column>

                  <template #empty>
                    <div class="tw-text-center tw-py-8 tw-text-gray-500">
                      <i class="pi pi-users tw-text-4xl tw-text-gray-300 tw-mb-2"></i>
                      <div>No recipients found for this transaction</div>
                    </div>
                  </template>
                </DataTable>
              </div>

              <div v-else class="tw-text-center tw-py-8 tw-text-gray-500">
                <i class="pi pi-users tw-text-4xl tw-text-gray-300 tw-mb-2"></i>
                <div>No recipients found for this transaction</div>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <template #footer>
        <div class="tw-flex tw-justify-end">
          <Button
            label="Close"
            icon="pi pi-times"
            class="p-button-secondary"
            @click="closePackUsersModal"
          />
        </div>
      </template>
    </Dialog>

    <!-- Create Transaction Modal -->
    <Dialog
      v-model:visible="showCreateTransactionModal"
      :modal="true"
      header="Create New Transaction"
      :style="{ width: '700px' }"
      class="p-fluid"
      :closable="false"
    >
      <div class="tw-space-y-6">
        <div class="tw-space-y-4">
          <h4 class="tw-text-lg tw-font-semibold tw-text-gray-800">Transaction Details</h4>
          
          <div class="tw-space-y-2">
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">Bank Account *</label>
            <Dropdown
              v-model="createTransactionForm.bank_account_id"
              :options="bankAccountOptions"
              option-label="label"
              option-value="value"
              placeholder="Select bank account"
              :class="{ 'p-invalid': createTransactionErrors.bank_account_id }"
              class="tw-w-full"
            />
            <small v-if="createTransactionErrors.bank_account_id" class="p-error">{{ createTransactionErrors.bank_account_id }}</small>
          </div>

          <div class="tw-space-y-2">
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">Coffre (Optional)</label>
            <Dropdown
              v-model="createTransactionForm.coffre_id"
              :options="coffreOptions"
              option-label="label"
              option-value="value"
              placeholder="Select coffre for transfer"
              class="tw-w-full"
              showClear
            />
            <small class="tw-text-xs tw-text-gray-500">If selected, transaction will also update the coffre balance</small>
          </div>

          <div class="tw-space-y-2">
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">Transaction Type *</label>
            <Dropdown
              v-model="createTransactionForm.transaction_type"
              :options="typeOptions"
              option-label="label"
              option-value="value"
              placeholder="Select transaction type"
              class="tw-w-full"
            />
          </div>

          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
            <div class="tw-space-y-2">
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">Amount *</label>
              <InputText
                v-model.number="createTransactionForm.amount"
                type="number"
                step="0.01"
                min="0"
                placeholder="Enter amount"
                :class="{ 'p-invalid': createTransactionErrors.amount }"
              />
              <small v-if="createTransactionErrors.amount" class="p-error">{{ createTransactionErrors.amount }}</small>
            </div>

            <div class="tw-space-y-2">
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">Transaction Date *</label>
              <Calendar
                v-model="createTransactionForm.transaction_date"
                date-format="yy-mm-dd"
                show-icon
                placeholder="Select date"
              />
            </div>
          </div>

          <div class="tw-space-y-2">
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">Reference *</label>
            <InputText
              v-model="createTransactionForm.reference"
              placeholder="Enter transaction reference"
              :class="{ 'p-invalid': createTransactionErrors.reference }"
            />
            <small v-if="createTransactionErrors.reference" class="p-error">{{ createTransactionErrors.reference }}</small>
          </div>

          <div class="tw-space-y-2">
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">Description *</label>
            <Textarea
              v-model="createTransactionForm.description"
              placeholder="Enter transaction description"
              :class="{ 'p-invalid': createTransactionErrors.description }"
              rows="3"
              auto-resize
            />
            <small v-if="createTransactionErrors.description" class="p-error">{{ createTransactionErrors.description }}</small>
          </div>

          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
            <div class="tw-space-y-2">
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">Designation</label>
              <InputText
                v-model="createTransactionForm.Designation"
                placeholder="Enter designation"
              />
            </div>

            <div class="tw-space-y-2">
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">Payer</label>
              <InputText
                v-model="createTransactionForm.Payer"
                placeholder="Enter payer"
              />
            </div>
          </div>

          <div class="tw-space-y-2">
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">Attachment (Optional)</label>
            <FileUpload
              mode="basic"
              :multiple="false"
              accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
              :maxFileSize="10485760"
              :auto="false"
              choose-label="Choose File"
              @select="onCreateFileSelect"
              @remove="onCreateFileRemove"
              :show-upload-button="false"
              :show-cancel-button="false"
            />
            <small class="tw-text-xs tw-text-gray-500">Supported formats: PDF, JPG, PNG, DOC, DOCX (Max 10MB)</small>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="tw-flex tw-justify-end tw-gap-3">
          <Button
            label="Cancel"
            icon="pi pi-times"
            class="p-button-text"
            @click="cancelCreateTransaction"
            :disabled="createTransactionLoading"
          />
          <Button
            label="Create Transaction"
            icon="pi pi-plus"
            class="p-button-primary"
            @click="createTransaction"
            :loading="createTransactionLoading"
          />
        </div>
      </template>
    </Dialog>

    <Toast />
  </div>
</template>

<style scoped>
@reference "../../../../../../resources/css/app.css";

/* Scoped CSS overrides for PrimeVue components, using `@apply` for Tailwind classes */
:deep(.p-datatable .p-datatable-thead > tr > th) {
  background-color: #f9fafb;
  color: #374151;
  font-weight: 600;
  border: none;
  font-size: 0.875rem;
}
:deep(.p-datatable .p-datatable-tbody > tr) {
  transition: background-color 0.2s ease;
}
:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background-color: #f3f4f6;
}
:deep(.p-card-header) {
  padding: 0;
}
:deep(.p-card-content) {
  padding: 0;
}
:deep(.p-paginator) {
  background-color: white;
  border-radius: 0.5rem;
  padding: 1rem;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
}

/* Modal styling */
:deep(.p-dialog .p-dialog-header) {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  border-top-left-radius: 0.75rem;
  border-top-right-radius: 0.75rem;
}

:deep(.p-dialog .p-dialog-content) {
  padding: 1.5rem;
}

:deep(.p-dialog .p-dialog-footer) {
  padding: 1rem 1.5rem;
  background-color: #f9fafb;
  border-bottom-left-radius: 0.75rem;
  border-bottom-right-radius: 0.75rem;
}

/* Form styling */
:deep(.p-calendar),
:deep(.p-inputtext),
:deep(.p-inputtextarea) {
  border-radius: 0.5rem;
  border: 1px solid #d1d5db;
  transition: all 0.2s ease;
}

:deep(.p-calendar:not(.p-disabled):hover),
:deep(.p-inputtext:hover),
:deep(.p-inputtextarea:hover) {
  border-color: #10b981;
}

:deep(.p-calendar:not(.p-disabled).p-focus),
:deep(.p-inputtext:focus),
:deep(.p-inputtextarea:focus) {
  border-color: #10b981;
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

:deep(.p-invalid) {
  border-color: #ef4444 !important;
}

:deep(.p-invalid:focus) {
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
}

/* File upload styling */
:deep(.p-fileupload) {
  border-radius: 0.5rem;
  border: 1px solid #d1d5db;
  transition: all 0.2s ease;
}

:deep(.p-fileupload:hover) {
  border-color: #10b981;
}

:deep(.p-fileupload.p-focus) {
  border-color: #10b981;
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

:deep(.p-fileupload .p-button) {
  background-color: #10b981;
  border-color: #10b981;
  border-radius: 0.375rem;
}

:deep(.p-fileupload .p-button:hover) {
  background-color: #059669;
  border-color: #059669;
}

:deep(.p-fileupload-filename) {
  color: #374151;
  font-size: 0.875rem;
}
</style>