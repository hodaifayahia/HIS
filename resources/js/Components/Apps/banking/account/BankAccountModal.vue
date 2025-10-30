<script setup>
import { ref, reactive, onMounted, watch, computed } from 'vue';
import { bankAccountService } from '../../services/bank/BankAccountService';
import { bankService } from '../../services/bank/bankService';

// PrimeVue Components
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';
import Textarea from 'primevue/textarea';
import InputSwitch from 'primevue/inputswitch';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

// Props
const props = defineProps({
  bankAccount: {
    type: Object,
    default: null
  },
  isEditing: {
    type: Boolean,
    default: false
  }
});

// Emits
const emit = defineEmits(['close', 'saved']);

// Reactive state
const banks = ref([]);
const currencyOptions = ref([]);
const saving = ref(false);
const errors = ref({});

const form = reactive({
  bank_id: null,
  account_name: '',
  account_number: '',

  currency: 'DZD',
  current_balance: 0,

  description: '',
  is_active: true
});

// Computed
const isFormValid = computed(() => {
  return form.bank_id && 
           form.account_name.trim() !== '' && 
           form.account_number.trim() !== '' && 
           form.currency !== '';
});

// new computed: filter currency options by selected bank
const filteredCurrencyOptions = computed(() => {
  if (!form.bank_id) return currencyOptions.value;

  const bank = banks.value.find(b => String(b.id) === String(form.bank_id) || b.id === form.bank_id);
  if (!bank) return currencyOptions.value;

  // supported_currencies may be array or comma-separated string
  let supported = bank.supported_currencies ?? bank.supported_currencies_text ?? null;
  if (!supported) return currencyOptions.value;

  if (Array.isArray(supported)) {
    supported = supported.map(s => String(s).trim().toUpperCase());
  } else {
    supported = String(supported).split(',').map(s => s.trim().toUpperCase());
  }

  return currencyOptions.value.filter(opt => supported.includes(String(opt.value).toUpperCase()));
});

// Methods
const loadData = async () => {
  try {
    // Load banks
    const banksResult = await bankService.getAll();
    if (banksResult.success) {
      banks.value = banksResult.data;
    }

    // Load currencies
    const currenciesResult = await bankAccountService.getCurrencies();
    if (currenciesResult.success) {
      currencyOptions.value = currenciesResult.data.map(currency => ({
        label: `${currency} - ${getCurrencyName(currency)}`,
        value: currency
      }));
    }
  } catch (error) {
    console.error('Error loading form data:', error);
  }
};

const resetForm = () => {
  form.bank_id = null;
  form.account_name = '';
  form.account_number = '';
  
  form.currency = 'DZD';
  form.current_balance = 0;
  form.available_balance = 0;
  form.description = '';
  form.is_active = true;
  errors.value = {};
};

const populateForm = () => {
  if (props.bankAccount && props.isEditing) {
    form.bank_id = props.bankAccount.bank_id || props.bankAccount.bank?.id;
    form.account_name = props.bankAccount.account_name || '';
    form.account_number = props.bankAccount.account_number || '';

    form.currency = props.bankAccount.currency || 'DZD';
    form.current_balance = props.bankAccount.current_balance || 0;
    form.available_balance = props.bankAccount.available_balance || 0;
    form.description = props.bankAccount.description || '';
    form.is_active = props.bankAccount.is_active ?? true;
  }
};

const saveBankAccount = async () => {
  if (!isFormValid.value) return;
  
  saving.value = true;
  errors.value = {};

  const data = {
    bank_id: form.bank_id,
    account_name: form.account_name.trim(),
    account_number: form.account_number.trim(),

    currency: form.currency,
    current_balance: form.current_balance || 0,
    available_balance: form.available_balance || form.current_balance || 0,
    description: form.description.trim() || null,
    is_active: form.is_active
  };

  try {
    const result = props.isEditing
      ? await bankAccountService.update(props.bankAccount.id, data)
      : await bankAccountService.create(data);

    if (result.success) {
      emit('saved', result.message);
    } else {
      if (result.errors) {
        errors.value = result.errors;
      } else {
        alert(result.message);
      }
    }
  } catch (error) {
    console.error('Error saving bank account:', error);
    alert('An unexpected error occurred. Please try again.');
  } finally {
    saving.value = false;
  }
};

// Helper methods
const getSelectedBankName = (bankId) => {
  const bank = banks.value.find(b => b.id == bankId || b.value == bankId);
  return bank ? bank.name : '';
};

const getSelectedBankCode = (bankId) => {
  const bank = banks.value.find(b => b.id == bankId || b.value == bankId);
  return bank ? bank.code : '';
};

const formatCurrency = (amount, currency = 'DZD') => {
  if (!amount) return '0.00';
  return new Intl.NumberFormat('fr-DZ', {
    style: 'currency',
    currency: currency,
    minimumFractionDigits: 2
  }).format(amount);
};

const getCurrencyName = (currency) => {
  const currencyNames = {
    'DZD': 'Algerian Dinar',
    'EUR': 'Euro',
    'USD': 'US Dollar',
    'GBP': 'British Pound',
    'CHF': 'Swiss Franc',
    'JPY': 'Japanese Yen'
  };
  return currencyNames[currency] || currency;
};




// Watchers
watch(() => props.bankAccount, () => {
  if (props.isEditing) {
    populateForm();
  } else {
    resetForm();
  }
}, { immediate: true });

// Auto-sync available balance with current balance if not manually set
watch(() => form.current_balance, (newValue) => {
  if (!props.isEditing || form.available_balance === 0) {
    form.available_balance = newValue;
  }
});

// when bank changes, ensure selected currency is allowed
watch(() => form.bank_id, (newBankId) => {
  if (!newBankId) return;
  const allowed = filteredCurrencyOptions.value;
  if (!allowed || allowed.length === 0) {
    // if bank has no supported currencies defined, keep current currency
    return;
  }
  const current = form.currency ? String(form.currency).toUpperCase() : null;
  if (!current || !allowed.some(a => String(a.value).toUpperCase() === current)) {
    form.currency = allowed[0].value;
  }
});

// Lifecycle
onMounted(() => {
  loadData();
  if (props.isEditing) {
    populateForm();
  }
});
</script>

<template>
  <div class="tw-fixed tw-inset-0 tw-bg-black/50 tw-flex tw-items-center tw-justify-center tw-z-[1050] tw-backdrop-blur-sm" >
    <div class="tw-bg-white tw-rounded-xl tw-w-[90%] md:tw-max-w-3xl tw-max-h-[95vh] tw-overflow-y-auto tw-shadow-2xl tw-animate-slideIn">
      <div class="tw-p-6 tw-border-b tw-border-gray-200 tw-flex tw-justify-between tw-items-center tw-bg-gradient-to-br tw-from-blue-700 tw-to-blue-400 tw-text-white tw-rounded-t-xl">
        <h4 class="tw-m-0 tw-font-semibold tw-text-xl md:tw-text-2xl tw-flex tw-items-center tw-gap-2">
          <i class="pi pi-credit-card"></i>
          {{ isEditing ? 'Edit Bank Account' : 'New Bank Account' }}
        </h4>
        <button type="button" class="tw-bg-transparent tw-border-none tw-text-white tw-text-xl tw-p-2 tw-rounded-full tw-w-[35px] tw-h-[35px] tw-flex tw-items-center tw-justify-center tw-transition-all tw-duration-200 tw-cursor-pointer hover:tw-bg-white/20 hover:tw-rotate-90" @click="$emit('close')">
          <i class="pi pi-times"></i>
        </button>
      </div>

      <div class="tw-p-8 md:tw-p-12">
        <form @submit.prevent="saveBankAccount">
          <div class="tw-mb-4">
            <label for="bank_id" class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-flex tw-items-center tw-gap-2 tw-text-sm md:tw-text-base">
              <i class="pi pi-building"></i>
              Bank *
            </label>
            <Dropdown
              v-model="form.bank_id"
              :options="banks"
              append-to="self"
              option-label="name"
              option-value="id"
              placeholder="Select bank"
              class="tw-w-full"
              :class="{ 'p-invalid': errors.bank_id }"
              :disabled="saving"
            >
              <template #value="slotProps">
                <div v-if="slotProps.value" class="tw-flex tw-items-center tw-gap-3 tw-p-2">
                  <div class="tw-w-10 tw-h-10 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-text-white tw-text-base tw-flex-shrink-0 tw-bg-gradient-to-br tw-from-indigo-500 tw-to-violet-500">
                    <i class="pi pi-building"></i>
                  </div>
                  <div class="tw-flex-1">
                    <div class="tw-font-semibold tw-text-gray-900 tw-text-sm">{{ getSelectedBankName(slotProps.value) }}</div>
                    <div class="tw-text-gray-500 tw-text-xs">{{ getSelectedBankCode(slotProps.value) }}</div>
                  </div>
                </div>
                <span v-else>{{ slotProps.placeholder }}</span>
              </template>
              <template #option="slotProps">
                <div class="tw-flex tw-items-center tw-gap-3 tw-p-2 tw-rounded-lg tw-transition-colors tw-duration-200 hover:tw-bg-gray-100">
                  <div class="tw-w-10 tw-h-10 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-text-white tw-text-base tw-flex-shrink-0 tw-bg-gradient-to-br tw-from-indigo-500 tw-to-violet-500">
                    <i class="pi pi-building"></i>
                  </div>
                  <div class="tw-flex-1">
                    <div class="tw-font-semibold tw-text-gray-900 tw-text-sm">{{ slotProps.option.name }}</div>
                    <div class="tw-text-gray-500 tw-text-xs">{{ slotProps.option.code || 'No code' }}</div>
                  </div>
                </div>
              </template>
            </Dropdown>
            <small v-if="errors.bank_id" class="p-error tw-text-red-500 tw-mt-1 tw-block">
              {{ errors.bank_id }}
            </small>
          </div>

          <div class="tw-mb-4">
            <label for="account_name" class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-flex tw-items-center tw-gap-2 tw-text-sm md:tw-text-base">
              <i class="pi pi-user"></i>
              Account Name *
            </label>
            <InputText
              v-model="form.account_name"
              id="account_name"
              placeholder="Enter account name"
              class="tw-w-full"
              :class="{ 'p-invalid': errors.account_name }"
              :disabled="saving"
            />
            <small v-if="errors.account_name" class="p-error tw-text-red-500 tw-mt-1 tw-block">
              {{ errors.account_name }}
            </small>
          </div>

          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
            <div class="tw-mb-4">
              <label for="account_number" class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-flex tw-items-center tw-gap-2 tw-text-sm md:tw-text-base">
                <i class="pi pi-credit-card"></i>
                Account Number *
              </label>
              <InputText
                v-model="form.account_number"
                id="account_number"
                placeholder="Enter account number"
                class="tw-w-full"
                :class="{ 'p-invalid': errors.account_number }"
                :disabled="saving"
              />
              <small v-if="errors.account_number" class="p-error tw-text-red-500 tw-mt-1 tw-block">
                {{ errors.account_number }}
              </small>
            </div>
              <div class="tw-mb-4">
              <label for="currency" class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-flex tw-items-center tw-gap-2 tw-text-sm md:tw-text-base">
                <i class="pi pi-dollar"></i>
                Currency *
              </label>
              <Dropdown
                v-model="form.currency"
                :options="filteredCurrencyOptions"
                option-label="label"
                option-value="value"
                append-to="self"
                placeholder="Select currency"
                class="tw-w-full"
                :class="{ 'p-invalid': errors.currency }"
                :disabled="saving"
              />
              <small v-if="errors.currency" class="p-error tw-text-red-500 tw-mt-1 tw-block">
                {{ errors.currency }}
              </small>
            </div>
       
          </div>

          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
          
     <div class="tw-mb-4">
              <label for="current_balance" class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-flex tw-items-center tw-gap-2 tw-text-sm md:tw-text-base">
                <i class="pi pi-wallet"></i>
                Current Balance
              </label>
              <div class="p-inputgroup">
                <InputNumber
                  v-model="form.current_balance"
                  :min-fraction-digits="2"
                  :max-fraction-digits="2"
                  :min="0"
                  placeholder="0.00"
                  class="tw-w-full"
                  :class="{ 'p-invalid': errors.current_balance }"
                  :disabled="saving"
                />
                <span class="p-inputgroup-addon">{{ form.currency || 'DZD' }}</span>
              </div>
              <small v-if="errors.current_balance" class="p-error tw-text-red-500 tw-mt-1 tw-block">
                {{ errors.current_balance }}
              </small>
            </div>
           
          </div>

          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
       
          </div>

          <div class="tw-mb-4">
            <label for="description" class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-flex tw-items-center tw-gap-2 tw-text-sm md:tw-text-base">
              <i class="pi pi-file-edit"></i>
              Description
            </label>
            <Textarea
              v-model="form.description"
              rows="3"
              placeholder="Enter account description (optional)"
              class="tw-w-full"
              :class="{ 'p-invalid': errors.description }"
              :disabled="saving"
            />
            <small v-if="errors.description" class="p-error tw-text-red-500 tw-mt-1 tw-block">
              {{ errors.description }}
            </small>
          </div>

          <div class="tw-mb-6">
            <div class="tw-p-4 tw-border tw-border-gray-300 tw-rounded-lg tw-bg-gray-50 tw-flex tw-justify-between tw-items-start md:tw-items-center tw-gap-4">
              <div class="tw-flex-1">
                <label class="tw-font-semibold tw-text-gray-800 tw-mb-1 tw-flex tw-items-center tw-gap-2 tw-text-sm md:tw-text-base">
                  <i class="pi pi-power-off"></i>
                  Status
                </label>
                <small class="tw-text-gray-600 tw-text-xs tw-block tw-leading-snug">
                  {{ form.is_active ? 'Account is active and ready to use' : 'Account is inactive' }}
                </small>
              </div>
              <div class="tw-flex tw-items-center tw-gap-2 tw-flex-shrink-0">
                <InputSwitch
                  v-model="form.is_active"
                  :disabled="saving"
                />
                <span class="tw-font-semibold tw-text-sm" :class="form.is_active ? 'tw-text-green-500' : 'tw-text-gray-500'">
                  {{ form.is_active ? 'Active' : 'Inactive' }}
                </span>
              </div>
            </div>
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
              :label="saving ? 'Saving...' : (isEditing ? 'Update Account' : 'Create Account')"
              :icon="saving ? 'pi pi-spin pi-spinner' : (isEditing ? 'pi pi-check' : 'pi pi-plus')"
              class="p-button-primary tw-flex-1"
              :disabled="saving || !isFormValid"
            />
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
/*
 * Minimal CSS is needed when using Tailwind.
 * All custom classes have been removed from the template.
 * The following section contains overrides for PrimeVue components
 * that are styled using the `@apply` directive.
 */
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

:deep(.p-inputtext),
:deep(.p-dropdown),
:deep(.p-inputnumber-input),
:deep(.p-inputtextarea) {
  @apply rounded-lg tw-border tw-border-gray-300 tw-transition-all tw-duration-200;
}

:deep(.p-inputtext:hover),
:deep(.p-dropdown:not(.p-disabled):hover),
:deep(.p-inputnumber-input:hover),
:deep(.p-inputtextarea:hover) {
  @apply border-blue-500;
}

:deep(.p-inputtext:focus),
:deep(.p-dropdown:not(.p-disabled).p-focus),
:deep(.p-inputnumber-input:focus),
:deep(.p-inputtextarea:focus) {
  @apply border-blue-500 tw-shadow-md tw-shadow-blue-500/25;
}

:deep(.p-invalid) {
  @apply border-red-500;
}

:deep(.p-invalid:focus) {
  @apply shadow-md tw-shadow-red-500/25;
}

:deep(.p-button) {
  @apply rounded-lg tw-font-medium tw-transition-all tw-duration-200;
}

:deep(.p-button:hover) {
  @apply scale-105;
}

:deep(.p-button:disabled) {
  @apply scale-100 tw-cursor-not-allowed tw-opacity-50;
}

:deep(.p-inputswitch.p-inputswitch-checked .p-inputswitch-slider) {
  @apply bg-green-500;
}

:deep(.p-inputgroup-addon) {
  @apply bg-gray-100 tw-border tw-border-gray-300 tw-text-blue-700 tw-font-semibold tw-rounded-r-lg;
}

:deep(.p-dropdown-panel) {
  @apply rounded-xl tw-border-none tw-shadow-2xl;
}

:deep(.p-dropdown-item) {
  @apply rounded-md tw-mx-2 tw-my-0.5 tw-transition-all tw-duration-200;
}

:deep(.p-dropdown-item:hover) {
  @apply bg-gray-100 tw-translate-x-0.5;
}
</style>