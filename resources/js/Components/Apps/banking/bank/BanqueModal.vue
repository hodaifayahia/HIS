<script setup>
import { ref, reactive, onMounted, watch, computed } from 'vue';
import { bankService } from '../../services/bank/bankService';

// PrimeVue Components
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import InputSwitch from 'primevue/inputswitch';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

// Props
const props = defineProps({
  bank: {
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
const saving = ref(false);
const errors = ref({});
const selectedCurrencies = ref([]);

const form = reactive({
  name: '',
  code: '',
  swift_code: '',
  address: '',
  phone: '',
  email: '',
  website: '',
  logo_url: '',
  sort_order: 0,
  is_active: true
});

const availableCurrencies = [
  { code: 'DZD', name: 'Algerian Dinar' },
  { code: 'EUR', name: 'Euro' },
  { code: 'USD', name: 'US Dollar' },
  { code: 'GBP', name: 'British Pound' },
  { code: 'CHF', name: 'Swiss Franc' },
  { code: 'JPY', name: 'Japanese Yen' }
];

// Computed
const isFormValid = computed(() => {
  return form.name.trim() !== '';
});

// Methods
const resetForm = () => {
  form.name = '';
  form.code = '';
  form.swift_code = '';
  form.address = '';
  form.phone = '';
  form.email = '';
  form.website = '';
  form.logo_url = '';
  form.sort_order = 0;
  form.is_active = true;
  selectedCurrencies.value = [];
  errors.value = {};
};

const populateForm = () => {
  if (props.bank && props.isEditing) {
    form.name = props.bank.name || '';
    form.code = props.bank.code || '';
    form.swift_code = props.bank.swift_code || '';
    form.address = props.bank.address || '';
    form.phone = props.bank.phone || '';
    form.email = props.bank.email || '';
    form.website = props.bank.website || '';
    form.logo_url = props.bank.logo_url || '';
    form.sort_order = props.bank.sort_order || 0;
    form.is_active = props.bank.is_active ?? true;
    selectedCurrencies.value = props.bank.supported_currencies || [];
  }
};

const toggleCurrency = (currencyCode) => {
  const index = selectedCurrencies.value.indexOf(currencyCode);
  if (index > -1) {
    selectedCurrencies.value.splice(index, 1);
  } else {
    selectedCurrencies.value.push(currencyCode);
  }
};

const saveBank = async () => {
  if (!isFormValid.value) return;
  
  saving.value = true;
  errors.value = {};

  const data = {
    name: form.name.trim(),
    code: form.code.trim() || null,
    swift_code: form.swift_code.trim() || null,
    address: form.address.trim() || null,
    phone: form.phone.trim() || null,
    email: form.email.trim() || null,
    website: form.website.trim() || null,
    logo_url: form.logo_url.trim() || null,
    supported_currencies: selectedCurrencies.value.length > 0 ? selectedCurrencies.value : null,
    sort_order: form.sort_order || 0,
    is_active: form.is_active
  };

  try {
    const result = props.isEditing
      ? await bankService.update(props.bank.id, data)
      : await bankService.create(data);

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
    console.error('Error saving bank:', error);
    alert('An unexpected error occurred. Please try again.');
  } finally {
    saving.value = false;
  }
};

// Watchers
watch(() => props.bank, () => {
  if (props.isEditing) {
    populateForm();
  } else {
    resetForm();
  }
}, { immediate: true });

// Lifecycle
onMounted(() => {
  if (props.isEditing) {
    populateForm();
  }
});
</script>

<template>
  <div class="tw-fixed tw-inset-0 tw-bg-black/50 tw-flex tw-items-center tw-justify-center tw-z-[1050] tw-backdrop-blur-sm" @click.self="$emit('close')">
    <div class="tw-bg-white tw-rounded-xl tw-w-[90%] md:tw-max-w-3xl tw-max-h-[95vh] tw-overflow-y-auto tw-shadow-2xl tw-animate-slideIn">
      <div class="tw-p-6 tw-border-b tw-border-gray-200 tw-flex tw-justify-between tw-items-center tw-bg-gradient-to-br tw-from-indigo-500 tw-to-violet-500 tw-text-white tw-rounded-t-xl">
        <h4 class="tw-m-0 tw-font-semibold tw-text-xl md:tw-text-2xl tw-flex tw-items-center tw-gap-2">
          <i class="pi pi-building"></i>
          {{ isEditing ? 'Edit Bank' : 'New Bank' }}
        </h4>
        <button type="button" class="tw-bg-transparent tw-border-none tw-text-white tw-text-xl tw-p-2 tw-rounded-full tw-w-[35px] tw-h-[35px] tw-flex tw-items-center tw-justify-center tw-transition-all tw-duration-200 tw-cursor-pointer hover:tw-bg-white/20 hover:tw-rotate-90" @click="$emit('close')">
          <i class="pi pi-times"></i>
        </button>
      </div>

      <div class="tw-p-8 md:tw-p-12">
        <form @submit.prevent="saveBank">
          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
            <div class="tw-mb-4">
              <label for="name" class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-flex tw-items-center tw-gap-2 tw-text-sm md:tw-text-base">
                <i class="pi pi-building"></i>
                Bank Name *
              </label>
              <InputText
                v-model="form.name"
                id="name"
                placeholder="Enter bank name"
                class="tw-w-full"
                :class="{ 'p-invalid': errors.name }"
                :disabled="saving"
              />
              <small v-if="errors.name" class="p-error tw-text-red-500 tw-mt-1 tw-block">
                {{ errors.name }}
              </small>
            </div>
            <div class="tw-mb-4">
              <label for="code" class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-flex tw-items-center tw-gap-2 tw-text-sm md:tw-text-base">
                <i class="pi pi-tag"></i>
                Bank Code
              </label>
              <InputText
                v-model="form.code"
                id="code"
                placeholder="e.g., BNA, CPA"
                class="tw-w-full"
                :class="{ 'p-invalid': errors.code }"
                :disabled="saving"
              />
              <small v-if="errors.code" class="p-error tw-text-red-500 tw-mt-1 tw-block">
                {{ errors.code }}
              </small>
            </div>
          </div>

         

          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
            <div class="tw-mb-4">
              <label for="phone" class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-flex tw-items-center tw-gap-2 tw-text-sm md:tw-text-base">
                <i class="pi pi-phone"></i>
                Phone
              </label>
              <InputText
                v-model="form.phone"
                id="phone"
                placeholder="Enter phone number"
                class="tw-w-full"
                :class="{ 'p-invalid': errors.phone }"
                :disabled="saving"
              />
              <small v-if="errors.phone" class="p-error tw-text-red-500 tw-mt-1 tw-block">
                {{ errors.phone }}
              </small>
            </div>
            <div class="tw-mb-4">
              <label for="email" class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-flex tw-items-center tw-gap-2 tw-text-sm md:tw-text-base">
                <i class="pi pi-envelope"></i>
                Email
              </label>
              <InputText
                v-model="form.email"
                id="email"
                type="email"
                placeholder="Enter email address"
                class="tw-w-full"
                :class="{ 'p-invalid': errors.email }"
                :disabled="saving"
              />
              <small v-if="errors.email" class="p-error tw-text-red-500 tw-mt-1 tw-block">
                {{ errors.email }}
              </small>
            </div>
          </div>

          <div class="tw-mb-4">
            <label for="website" class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-flex tw-items-center tw-gap-2 tw-text-sm md:tw-text-base">
              <i class="pi pi-globe"></i>
              Website
            </label>
            <InputText
              v-model="form.website"
              id="website"
              placeholder="https://www.bankname.com"
              class="tw-w-full"
              :class="{ 'p-invalid': errors.website }"
              :disabled="saving"
            />
            <small v-if="errors.website" class="p-error tw-text-red-500 tw-mt-1 tw-block">
              {{ errors.website }}
            </small>
          </div>

          <div class="tw-mb-4">
            <label for="address" class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-flex tw-items-center tw-gap-2 tw-text-sm md:tw-text-base">
              <i class="pi pi-map-marker"></i>
              Address
            </label>
            <Textarea
              v-model="form.address"
              rows="3"
              placeholder="Enter bank address"
              class="tw-w-full"
              :class="{ 'p-invalid': errors.address }"
              :disabled="saving"
            />
            <small v-if="errors.address" class="p-error tw-text-red-500 tw-mt-1 tw-block">
              {{ errors.address }}
            </small>
          </div>

          <div class="tw-mb-4">
            <label class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-flex tw-items-center tw-gap-2 tw-text-sm md:tw-text-base">
              <i class="pi pi-dollar"></i>
              Supported Currencies
            </label>
            <div class="tw-border tw-border-gray-300 tw-rounded-lg tw-p-4 tw-bg-gray-50">
              <div class="tw-grid tw-grid-cols-2 md:tw-grid-cols-3 lg:tw-grid-cols-4 tw-gap-3">
                <div 
                  v-for="currency in availableCurrencies" 
                  :key="currency.code"
                  class="tw-p-3 tw-border-2 tw-border-gray-200 tw-rounded-lg tw-bg-white tw-cursor-pointer tw-transition-colors tw-duration-200 tw-relative tw-text-center hover:tw-border-indigo-500 hover:tw-bg-indigo-50"
                  :class="{ 'tw-border-indigo-500 tw-bg-indigo-50 tw-text-indigo-600': selectedCurrencies.includes(currency.code) }"
                  @click="toggleCurrency(currency.code)"
                >
                  <div class="tw-font-semibold tw-text-sm tw-mb-1">{{ currency.code }}</div>
                  <div class="tw-text-xs tw-text-gray-500">{{ currency.name }}</div>
                  <i v-if="selectedCurrencies.includes(currency.code)" class="pi pi-check tw-absolute tw-top-1 tw-right-1 tw-text-green-500 tw-text-xs"></i>
                </div>
              </div>
            </div>
            <small class="tw-text-gray-500 tw-text-xs tw-mt-1 tw-block tw-italic">Select the currencies this bank supports</small>
          </div>

          <div class="tw-mb-4">
            <label for="logo_url" class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-flex tw-items-center tw-gap-2 tw-text-sm md:tw-text-base">
              <i class="pi pi-image"></i>
              Logo URL
            </label>
            <InputText
              v-model="form.logo_url"
              id="logo_url"
              placeholder="https://www.bankname.com/logo.png"
              class="tw-w-full"
              :class="{ 'p-invalid': errors.logo_url }"
              :disabled="saving"
            />
            <small v-if="errors.logo_url" class="p-error tw-text-red-500 tw-mt-1 tw-block">
              {{ errors.logo_url }}
            </small>
          </div>

          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
          
            <div class="tw-mb-4 tw-p-4 tw-border tw-border-gray-300 tw-rounded-lg tw-bg-gray-50 tw-flex tw-justify-between tw-items-start md:tw-items-center">
              <div class="tw-flex-1">
                <label class="tw-font-semibold tw-text-gray-800 tw-mb-1 tw-flex tw-items-center tw-gap-2 tw-text-sm md:tw-text-base">
                  <i class="pi pi-power-off"></i>
                  Status
                </label>
                <small class="tw-text-gray-600 tw-text-xs tw-block tw-leading-snug">
                  {{ form.is_active ? 'Bank is active and available' : 'Bank is inactive' }}
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

          <div v-if="form.name" class="tw-pt-6 tw-mt-6 tw-border-t tw-border-gray-200">
            <h6 class="tw-font-semibold tw-text-gray-800 tw-mb-4 tw-text-sm">Preview</h6>
            <div class="tw-border-2 tw-border-gray-200 tw-rounded-xl tw-overflow-hidden tw-transition-opacity tw-duration-300" :class="{ 'tw-opacity-60': !form.is_active }">
              <div class="tw-p-4 tw-bg-gradient-to-br tw-from-indigo-500 tw-to-violet-500 tw-text-white tw-flex tw-items-center tw-gap-4">
                <div class="tw-w-9 tw-h-9 tw-bg-white/20 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-text-base">
                  <i class="pi pi-building"></i>
                </div>
                <div class="tw-flex-1">
                  <div class="tw-font-semibold tw-text-base tw-mb-1">{{ form.name }}</div>
                  <Tag
                    :value="form.is_active ? 'Active' : 'Inactive'"
                    :severity="form.is_active ? 'success' : 'danger'"
                  />
                </div>
              </div>
              <div class="tw-p-4 tw-bg-white">
                <div class="tw-flex tw-flex-col tw-gap-2">
                  <div class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-text-gray-600" v-if="form.code">
                    <i class="pi pi-tag tw-w-4 tw-text-indigo-500"></i>
                    <span>Code: {{ form.code }}</span>
                  </div>
                  <div class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-text-gray-600" v-if="form.swift_code">
                    <i class="pi pi-send tw-w-4 tw-text-indigo-500"></i>
                    <span>SWIFT: {{ form.swift_code }}</span>
                  </div>
                  <div class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-text-gray-600" v-if="selectedCurrencies.length">
                    <i class="pi pi-dollar tw-w-4 tw-text-indigo-500"></i>
                    <span>Currencies: {{ selectedCurrencies.join(', ') }}</span>
                  </div>
                </div>
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
              :label="saving ? 'Saving...' : (isEditing ? 'Update Bank' : 'Create Bank')"
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
 * Scoped CSS to be replaced by Tailwind.
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
:deep(.p-inputnumber-input),
:deep(.p-inputtextarea) {
  @apply rounded-lg tw-border tw-border-gray-300 tw-transition-all tw-duration-200;
}

:deep(.p-inputtext:hover),
:deep(.p-inputnumber-input:hover),
:deep(.p-inputtextarea:hover) {
  @apply border-indigo-500;
}

:deep(.p-inputtext:focus),
:deep(.p-inputnumber-input:focus),
:deep(.p-inputtextarea:focus) {
  @apply border-indigo-500 tw-shadow-md tw-shadow-indigo-500/25;
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
</style>