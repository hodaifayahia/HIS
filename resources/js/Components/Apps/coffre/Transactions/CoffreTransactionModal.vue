<script setup>
import { ref, reactive, onMounted, watch } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { coffreTransactionService } from '../../../../Components/Apps/services/Coffre/CoffreTransactionService';
import { coffreService } from '../../../../Components/Apps/services/Coffre/CoffreService';

// PrimeVue Components
import Dropdown from 'primevue/dropdown';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';

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
const saving = ref(false);
const errors = ref({});

// Fixed transaction types
const transactionTypes = [
  { label: 'Deposit', value: 'deposit' },
  { label: 'Withdraw', value: 'withdraw' }
];

const form = reactive({
  transaction_type: '',
  coffre_id: '',
  amount: null,
  description: ''
});

// Methods
const loadCoffres = async () => {
  try {
    const result = await coffreService.getAll();
    if (result.success) {
      coffres.value = result.data || [];
    }
  } catch (error) {
    console.error('Error loading coffres:', error);
  }
};

const saveTransaction = async () => {
  saving.value = true;
  errors.value = {};

  const data = {
    transaction_type: form.transaction_type,
    coffre_id: props.coffreId,
    dest_coffre_id: form.coffre_id,
    amount: form.amount,
    description: form.description || null
    // user_id will be set automatically by backend from auth user
  };

  const result = props.isEditing
    ? await coffreTransactionService.update(props.transaction.id, data)
    : await coffreTransactionService.create(data);

  if (result.success) {
    emit('saved', result.message || 'Transaction saved successfully');
  } else {
    if (result.errors) {
      errors.value = result.errors;
    } else {
      alert(result.message || 'Failed to save transaction');
    }
  }

  saving.value = false;
};

// Watchers
watch(() => props.transaction, (newTransaction) => {
  if (newTransaction && props.isEditing) {
    form.transaction_type = newTransaction.transaction_type;
    form.coffre_id = newTransaction.coffre_id;
    form.amount = newTransaction.amount;
    form.description = newTransaction.description || '';
  }
}, { immediate: true });

// Lifecycle
onMounted(() => {
  loadCoffres();
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

      <div class="tw-p-8 md:tw-p-12">
        <form @submit.prevent="saveTransaction">
          <div class="tw-mb-6">
            <label for="transaction_type" class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-flex tw-items-center tw-gap-2 tw-text-sm md:tw-text-base">
              <i class="pi pi-tag"></i>
              Transaction Type *
            </label>
            <Dropdown
              v-model="form.transaction_type"
              :options="transactionTypes"
              option-label="label"
              option-value="value"
              placeholder="Select transaction type"
              class="tw-w-full"
              :class="{ 'p-invalid': errors.transaction_type }"
              appendTo="self"
            />
            <small v-if="errors.transaction_type" class="p-error tw-text-red-500 tw-mt-1 tw-block">
              {{ errors.transaction_type[0] }}
            </small>
          </div>

          <div class="tw-mb-6">
            <label for="coffre_id" class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-flex tw-items-center tw-gap-2 tw-text-sm md:tw-text-base">
              <i class="pi pi-lock"></i>
              Safe *
            </label>
            <Dropdown
              v-model="form.coffre_id"
              :options="coffres"
              option-label="name"
              option-value="id"
              placeholder="Select safe"
              class="tw-w-full"
              :class="{ 'p-invalid': errors.coffre_id }"
              appendTo="self"
            />
            <small v-if="errors.coffre_id" class="p-error tw-text-red-500 tw-mt-1 tw-block">
              {{ errors.coffre_id[0] }}
            </small>
          </div>

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