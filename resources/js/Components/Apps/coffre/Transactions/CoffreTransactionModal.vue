<script setup>
import { ref, reactive, onMounted, watch, computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { coffreTransactionService } from '../../../../Components/Apps/services/Coffre/CoffreTransactionService';
import { coffreService } from '../../../../Components/Apps/services/Coffre/CoffreService';

// PrimeVue Components
import Dropdown from 'primevue/dropdown';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';

// Props
const props = defineProps({
  transaction: {
    type: Object,
    default: null
  },
  isEditing: {
    type: Boolean,
    default: false
  },
  coffreId: {
    type: String,
    default: null
  }
});

// Emits
const emit = defineEmits(['close', 'saved']);

// Auth store
const authStore = useAuthStore();

// Reactive state
const coffres = ref([]);
const banks = ref([]);
const transactionTypes = ref([
  'transfer_in',
  'transfer_out',

]);
const saving = ref(false);
const errors = ref({});
const loading = ref(false);

const form = reactive({
  transaction_type: '',
  coffre_id: '',
  dest_coffre_id: '',
  destination_banque_id: '',
  amount: null,
  description: '',
  is_bank_transfer: false
  ,
  // additional bank metadata fields
  Reason: '',
  reference: '',
  Designation: '',
  Payer: ''
});

// New state for file attachment
const attachment = ref(null);

// Computed
const isBankTransfer = computed(() => form.is_bank_transfer);
const requiresApproval = computed(() => isBankTransfer.value && form.amount > 0);

// Methods
const loadInitialData = async () => {
  loading.value = true;
  try {
    const [coffresResult, banksResult] = await Promise.all([
      coffreTransactionService.getCoffres(),
      coffreTransactionService.getBanks(),
    ]);

    if (coffresResult && coffresResult.success) {
      // support both shapes: { data: [...] } or { data: { data: [...] } }
      coffres.value = coffresResult.data?.data || coffresResult.data || [];
    } else {
      coffres.value = [];
    }
    if (banksResult && banksResult.success) {
      // support both shapes: { data: [...] } or { data: { data: [...] } }
      banks.value = banksResult.data || [];
    } else {
      
      banks.value = [];
    }

  } catch (error) {
    console.error('Error loading initial data:', error);
  } finally {
    loading.value = false;
  }
};

// File change handler
const onFileChange = (event) => {
  const f = event.target.files && event.target.files[0];
  attachment.value = f || null;
};

const saveTransaction = async () => {
  saving.value = true;
  errors.value = {};

  // base payload (same as before)
  const payload = {
    transaction_type: form.transaction_type,
    coffre_id: props.coffreId || form.coffre_id,
    amount: form.amount,
    description: form.description || null,
  };

  if (isBankTransfer.value) {
    payload.bank_account_id = form.destination_banque_id;
    payload.Reason = form.Reason || null;
    payload.reference = form.reference || null;
    payload.Designation = form.Designation || null;
    payload.Payer = form.Payer || null;
  } else if (form.dest_coffre_id) {
    payload.dest_coffre_id = form.dest_coffre_id;
  }

  try {
    let result;
    // if there is an attachment, send FormData
    if (attachment.value) {
      const fd = new FormData();
      Object.keys(payload).forEach(key => {
        if (payload[key] !== undefined && payload[key] !== null) {
          fd.append(key, payload[key]);
        }
      });
      fd.append('attachment', attachment.value);
      if (props.isEditing) {
        result = await coffreTransactionService.updateFormData(props.transaction.id, fd);
      } else {
        result = await coffreTransactionService.createFormData(fd);
      }
    } else {
      // no file -> regular JSON
      if (props.isEditing) {
        result = await coffreTransactionService.update(props.transaction.id, payload);
      } else {
        result = await coffreTransactionService.create(payload);
      }
    }

    if (result.success) {
      let message = result.message || 'Transaction saved successfully';
      if (result.data?.status === 'pending' && result.data?.approval_request) {
        message += ' - Approval required for bank transfer.';
      }
      emit('saved', message);
    } else {
      if (result.errors) {
        errors.value = result.errors;
      } else {
        alert(result.message || 'Failed to save transaction');
      }
    }
  } catch (error) {
    console.error('Error saving transaction:', error);
    alert('An unexpected error occurred. Please try again.');
  } finally {
    saving.value = false;
  }
};

const resetDestination = () => {
  form.dest_coffre_id = '';
  form.destination_banque_id = '';
};

// Watchers
watch(() => props.transaction, (newTransaction) => {
  if (newTransaction && props.isEditing) {
    form.transaction_type = newTransaction.transaction_type;
    form.coffre_id = newTransaction.coffre_id;
    form.dest_coffre_id = newTransaction.dest_coffre_id || '';
    form.destination_banque_id = newTransaction.destination_banque_id || '';
    form.amount = newTransaction.amount;
    form.description = newTransaction.description || '';
    form.is_bank_transfer = !!newTransaction.destination_banque_id;
  // populate optional bank metadata if present on the transaction
  form.Reason = newTransaction.Reason || '';
  form.reference = newTransaction.reference || '';
  form.Designation = newTransaction.Designation || '';
  form.Payer = newTransaction.Payer || '';
  }
}, { immediate: true });

watch(() => form.is_bank_transfer, (newValue) => {
  if (newValue) {
    form.transaction_type = 'transfer_out';
  }
  resetDestination();
});

// Lifecycle
onMounted(() => {
  loadInitialData();
  if (props.coffreId) {
    form.coffre_id = props.coffreId;
    // set designation to coffre name if coffres already loaded
    const initial = coffres.value.find(c => String(c.id) === String(props.coffreId));
    if (initial && !props.isEditing) {
      form.Designation = initial.name || '';
    }
  }
});

// update: keep Designation synced when user picks a different coffre (only for new transactions)
watch(() => form.coffre_id, (newId) => {
  if (!props.isEditing && newId) {
    const selected = coffres.value.find(c => String(c.id) === String(newId));
    if (selected) {
      form.Designation = selected.name || '';
    }
  }
});
</script>

<template>
  <div class="tw-fixed tw-inset-0 tw-bg-black/50 tw-flex tw-items-center tw-justify-center tw-z-[1050] tw-backdrop-blur-sm"
    @click.self="$emit('close')">
    <div
      class="tw-bg-white tw-rounded-xl tw-w-[90%] md:tw-max-w-xl lg:tw-max-w-3xl tw-max-h-[95vh] tw-overflow-y-auto tw-shadow-2xl tw-animate-slideIn">
      <div class="tw-p-6 tw-border-b tw-border-gray-200 tw-flex tw-justify-between tw-items-center tw-bg-gradient-to-br tw-from-emerald-500 tw-to-emerald-700 tw-text-white tw-rounded-t-xl">
        <h4 class="tw-m-0 tw-font-semibold tw-text-xl md:tw-text-2xl tw-flex tw-items-center tw-gap-2">
          <i class="pi pi-dollar"></i>
          {{ isEditing ? 'Edit Transaction' : 'New Transaction' }}
        </h4>
        <button type="button" class="tw-bg-transparent tw-border-none tw-text-white tw-text-xl tw-p-2 tw-rounded-full tw-w-[35px] tw-h-[35px] tw-flex tw-items-center tw-justify-center tw-transition-all tw-duration-200 tw-cursor-pointer hover:tw-bg-white/20 hover:tw-rotate-90" @click="$emit('close')">
          <i class="pi pi-times"></i>
        </button>
      </div>

      <div class="tw-p-8 md:tw-p-12" v-if="!loading">
        <form @submit.prevent="saveTransaction">
          <!-- Bank Transfer Toggle -->
          <div class="tw-mb-6 tw-p-4 tw-bg-blue-50 tw-rounded-lg tw-border tw-border-blue-200">
            <div class="tw-flex tw-items-center tw-gap-3">
              <Checkbox 
                v-model="form.is_bank_transfer" 
                :binary="true" 
                input-id="bank_transfer_toggle"
              />
              <label for="bank_transfer_toggle" class="tw-font-semibold tw-text-blue-800 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-building"></i>
                Transfer to Bank Account
              </label>
            </div>
            <div v-if="requiresApproval" class="tw-mt-2 tw-text-sm tw-text-orange-600 tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-exclamation-triangle"></i>
              This transaction will require approval before processing.
            </div>
          </div>

          <!-- Transaction Type -->
          <div class="tw-mb-6">
            <label for="transaction_type" class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-flex tw-items-center tw-gap-2 tw-text-sm md:tw-text-base">
              <i class="pi pi-tag"></i>
              Transaction Type *
            </label>
            <Dropdown
              v-model="form.transaction_type"
              :options="transactionTypes.map(type => ({
                label: type.charAt(0).toUpperCase() + type.slice(1).replace('_', ' '),
                value: type
              }))"
              option-label="label"
              option-value="value"
              placeholder="Select transaction type"
              class="tw-w-full"
              :class="{ 'p-invalid': errors.transaction_type }"
              :disabled="isBankTransfer"
              appendTo="self"
            />
            <small v-if="errors.transaction_type" class="p-error tw-text-red-500 tw-mt-1 tw-block">
              {{ errors.transaction_type[0] }}
            </small>
            <small v-if="isBankTransfer" class="tw-text-blue-600 tw-mt-1 tw-block">
              Bank transfers are automatically set to "Transfer Out"
            </small>
          </div>

          <!-- Source Coffre (if not pre-selected) -->
          <div v-if="!coffreId" class="tw-mb-6">
            <label for="coffre_id" class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-flex tw-items-center tw-gap-2 tw-text-sm md:tw-text-base">
              <i class="pi pi-lock"></i>
              Source Safe *
            </label>
            <Dropdown
              v-model="form.coffre_id"
              :options="coffres"
              option-label="name"
              option-value="id"
              placeholder="Select source safe"
              class="tw-w-full"
              :class="{ 'p-invalid': errors.coffre_id }"
              appendTo="self"
            />
            <small v-if="errors.coffre_id" class="p-error tw-text-red-500 tw-mt-1 tw-block">
              {{ errors.coffre_id[0] }}
            </small>
          </div>

          <!-- Destination (Bank or Coffre) -->
          <div v-if="!isBankTransfer" class="tw-mb-6">
            <label for="dest_coffre_id" class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-flex tw-items-center tw-gap-2 tw-text-sm md:tw-text-base">
              <i class="pi pi-arrow-right"></i>
              Destination Safe
            </label>
            <Dropdown
              v-model="form.dest_coffre_id"
              :options="coffres.filter(c => c.id != (coffreId || form.coffre_id))"
              option-label="name"
              option-value="id"
              placeholder="Select destination safe (optional)"
              class="tw-w-full"
              :class="{ 'p-invalid': errors.dest_coffre_id }"
              appendTo="self"
            />
            <small v-if="errors.dest_coffre_id" class="p-error tw-text-red-500 tw-mt-1 tw-block">
              {{ errors.dest_coffre_id[0] }}
            </small>
          </div>

          <div v-if="isBankTransfer" class="tw-mb-6">
            <label for="destination_banque_id" class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-flex tw-items-center tw-gap-2 tw-text-sm md:tw-text-base">
              <i class="pi pi-building"></i>
              Bank Account *
            </label>
            <Dropdown
              v-model="form.destination_banque_id"
              :options="banks"
              option-label="account_name"
              option-value="id"
              placeholder="Select bank account"
              class="tw-w-full"
              :class="{ 'p-invalid': errors.destination_banque_id }"
              appendTo="self"
            />
            <small v-if="errors.destination_banque_id" class="p-error tw-text-red-500 tw-mt-1 tw-block">
              {{ errors.destination_banque_id[0] }}
            </small>
          </div>

          <!-- Bank metadata fields required for bank transfers -->
          <div v-if="isBankTransfer" class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4 tw-mb-6">
            <!-- Reason -->
          

            <!-- Reference -->
            <div>
              <label for="reference" class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-text-sm md:tw-text-base">
                Reference *
              </label>
              <InputText
                id="reference"
                v-model="form.reference"
                placeholder="Enter reference"
                class="tw-w-full"
                :class="{ 'p-invalid': errors.reference }"
              />
              <small v-if="errors.reference" class="p-error tw-text-red-500 tw-mt-1 tw-block">
                {{ errors.reference[0] }}
              </small>
            </div>

            <!-- Designation -->
            <div>
              <label for="Designation" class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-text-sm md:tw-text-base">
                Designation *
              </label>
              <InputText
                id="Designation"
                v-model="form.Designation"
                placeholder="Enter designation"
                class="tw-w-full"
                :class="{ 'p-invalid': errors.Designation }"
              />
              <small v-if="errors.Designation" class="p-error tw-text-red-500 tw-mt-1 tw-block">
                {{ errors.Designation[0] }}
              </small>
            </div>

            <!-- Payer -->
            <div>
              <label for="Payer" class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-text-sm md:tw-text-base">
                Payer *
              </label>
              <InputText
                id="Payer"
                v-model="form.Payer"
                placeholder="Enter payer name"
                class="tw-w-full"
                :class="{ 'p-invalid': errors.Payer }"
              />
              <small v-if="errors.Payer" class="p-error tw-text-red-500 tw-mt-1 tw-block">
                {{ errors.Payer[0] }}
              </small>
            </div>
          </div>
            <div>
              <label for="Reason" class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-text-sm md:tw-text-base">
                Reason *
              </label>
              <Textarea
                id="Reason"
                v-model="form.Reason"
                placeholder="Enter reason"
                class="tw-w-full"
                :class="{ 'p-invalid': errors.Reason }"
              />
              <small v-if="errors.Reason" class="p-error tw-text-red-500 tw-mt-1 tw-block">
                {{ errors.Reason[0] }}
              </small>
            </div>

          <!-- Amount -->
          <div class="tw-mb-6">
            <label for="amount" class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-flex tw-items-center tw-gap-2 tw-text-sm md:tw-text-base">
              <i class="pi pi-dollar"></i>
              Amount *
            </label>
            <div class="p-inputgroup">
              <InputNumber
                v-model="form.amount"
                :min-fraction-digits="2"
                :max-fraction-digits="2"
                :min="0.01"
                placeholder="0.00"
                class="tw-w-full"
                :class="{ 'p-invalid': errors.amount }"
              />
              <span class="p-inputgroup-addon">DZD</span>
            </div>
            <small v-if="errors.amount" class="p-error tw-text-red-500 tw-mt-1 tw-block">
              {{ errors.amount[0] }}
            </small>
          </div>

          <!-- Description -->
          <div class="tw-mb-6">
            <label for="description" class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-flex tw-items-center tw-gap-2 tw-text-sm md:tw-text-base">
              <i class="pi pi-file-edit"></i>
              Description
            </label>
            <Textarea
              v-model="form.description"
              rows="3"
              placeholder="Enter transaction description (optional)"
              class="tw-w-full"
              :class="{ 'p-invalid': errors.description }"
            />
            <small v-if="errors.description" class="p-error tw-text-red-500 tw-mt-1 tw-block">
              {{ errors.description[0] }}
            </small>
          </div>

          <!-- ATTACHMENT -->
          <div v-if="isBankTransfer" class="md:tw-col-span-2 tw-mb-6">
            <label for="attachment" class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-text-sm md:tw-text-base">
              Attachment
            </label>
            <input
              id="attachment"
              type="file"
              accept=".pdf,.jpg,.jpeg,.png"
              @change="onFileChange"
              class="tw-w-full"
            />
            <small v-if="errors.attachment" class="p-error tw-text-red-500 tw-mt-1 tw-block">
              {{ errors.attachment[0] }}
            </small>
          </div>

          <div class="tw-flex tw-gap-4 tw-mt-8">
            <Button
              type="button"
              label="Cancel"
              icon="pi pi-times"
              class="p-button-secondary tw-flex-1"
              @click="$emit('close')"
              :disabled="saving"
            />
            <Button
              type="submit"
              :label="saving ? 'Saving...' : (isEditing ? 'Update' : 'Create')"
              :icon="saving ? 'pi pi-spin pi-spinner' : (isEditing ? 'pi pi-check' : 'pi pi-plus')"
              class="p-button-primary tw-flex-1"
              :disabled="saving"
            />
          </div>
        </form>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="tw-p-8 tw-text-center">
        <i class="pi pi-spinner pi-spin tw-text-2xl tw-text-emerald-500"></i>
        <p class="tw-mt-2 tw-text-gray-600">Loading...</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Scoped CSS overrides for PrimeVue components, now cleaner with Tailwind classes */
@keyframes slideIn {
  from {
    transform: translateY(-20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

:deep(.p-dropdown),
:deep(.p-inputnumber-input),
:deep(.p-inputtextarea) {
  @apply tw-rounded-xl tw-border tw-border-gray-300 tw-transition-all tw-duration-200;
}

:deep(.p-dropdown:not(.p-disabled):hover),
:deep(.p-inputnumber-input:hover),
:deep(.p-inputtextarea:hover) {
  @apply tw-border-emerald-500;
}

:deep(.p-dropdown:not(.p-disabled).p-focus),
:deep(.p-inputnumber-input:focus),
:deep(.p-inputtextarea:focus) {
  @apply tw-border-emerald-500 tw-shadow-md tw-shadow-emerald-500/25;
}

:deep(.p-invalid) {
  @apply tw-border-red-500;
}

:deep(.p-invalid:focus) {
  @apply tw-shadow-md tw-shadow-red-500/25;
}

:deep(.p-button) {
  @apply tw-rounded-lg tw-font-medium tw-transition-all tw-duration-200;
}

:deep(.p-button:hover) {
  @apply tw-scale-105;
}

:deep(.p-button:disabled) {
  @apply tw-scale-100 tw-cursor-not-allowed tw-opacity-50;
}

:deep(.p-inputgroup-addon) {
  @apply tw-bg-gray-100 tw-border-gray-300 tw-text-emerald-500 tw-font-semibold tw-rounded-r-lg;
}

:deep(.p-dropdown-panel) {
  @apply tw-rounded-xl tw-border-none tw-shadow-2xl;
}

:deep(.p-dropdown-item) {
  @apply tw-rounded-md tw-mx-2 tw-my-0.5 tw-transition-all tw-duration-200;
}

:deep(.p-dropdown-item:hover) {
  @apply tw-bg-gray-100 tw-translate-x-0.5;
}
</style>