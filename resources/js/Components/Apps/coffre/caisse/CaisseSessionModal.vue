<script setup>
import { ref, reactive, onMounted, computed } from 'vue';

// Services
import { caisseSessionService } from '../../../../Components/Apps/services/Coffre/caisseSessionService';
// Corrected import: Assumes a service file for coffres exists with a clear name.
import { coffreService } from '../../../../Components/Apps/services/Coffre/CoffreService';

// PrimeVue Components
import Dropdown from 'primevue/dropdown';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Avatar from 'primevue/avatar';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

// Props
const props = defineProps({
  session: {
    type: Object,
    default: null
  },
  caisseId: {
    type: [String, Number],
    default: null
  },
  isEditing: {
    type: Boolean,
    default: false
  }
});

// Emits
const emit = defineEmits(['close', 'saved']);

// Composables
const toast = useToast();

// Reactive state
const caisses = ref([]);
const coffres = ref([]);
const users = ref([]);
const saving = ref(false);
const errors = ref({});

const form = reactive({
  caisse_id: props.caisseId || '',
  coffre_id: '',
  user_id: '',
  opening_amount: null,
  opening_notes: ''
});

// Computed
const isPreselectedCaisse = computed(() => {
  return props.caisseId !== null && props.caisseId !== undefined;
});

const selectedCoffreBalance = computed(() => {
  if (!form.coffre_id) return null;
  const c = coffres.value.find(x => String(x.id) === String(form.coffre_id) || x.id === form.coffre_id);
  return c ? Number(c.current_balance ?? 0) : null;
});

const amountExceedsCoffre = computed(() => {
  if (selectedCoffreBalance.value === null) return false;
  if (form.opening_amount === null || form.opening_amount === undefined) return false;
  return Number(form.opening_amount) > Number(selectedCoffreBalance.value);
});

const isFormValid = computed(() => {
  const base = form.caisse_id && form.user_id && form.opening_amount >= 0;
  return base && !amountExceedsCoffre.value;
});

const loadData = async () => {
  try {
    const caissesResult = await caisseSessionService.getCaisses();
    if (caissesResult.success) {
      caisses.value = caissesResult.data.map(caisse => ({
        ...caisse,
        display_name: `${caisse.name} - ${caisse.location || 'No location'}`
      }));
      if (props.caisseId) form.caisse_id = props.caisseId;
    } else {
      showToast('error', 'Error', 'Failed to load cash registers.');
    }

    const coffresResult = await coffreService.getAll();
    if (coffresResult.success) {
      coffres.value = coffresResult.data.map(coffre => ({
        ...coffre,
        display_name: `${coffre.name} - ${coffre.location || 'No location'}`
      }));
    } else {
      showToast('error', 'Error', 'Failed to load safes.');
    }

    const usersResult = await caisseSessionService.getUsers();
    if (usersResult.success) {
      users.value = usersResult.data.map(user => ({
        ...user,
        display_name: `${user.name} - ${user.email}`
      }));
    } else {
      showToast('error', 'Error', 'Failed to load users.');
    }
  } catch (error) {
    console.error('Error loading form data:', error);
    showToast('error', 'Error', 'An unexpected error occurred while loading data.');
  }
};

const openSession = async () => {
  if (!isFormValid.value) return;

  saving.value = true;
  errors.value = {};
  const data = {
    caisse_id: Number(form.caisse_id),
    coffre_id: form.coffre_id || null,
    user_id: Number(form.user_id),
    opening_amount: Number(form.opening_amount),
    coffre_id_source: form.coffre_id || null,
    opening_notes: form.opening_notes || null
  };

  try {
    const result = await caisseSessionService.openSession(data);
    if (result.success) {
      emit('saved', result.data ?? result.message);
      emit('close');
    } else {
      if (result.errors) {
        errors.value = result.errors;
      } else {
        showToast('error', 'Error', result.message);
      }
    }
  } catch (error) {
    showToast('error', 'Error', 'An unexpected error occurred. Please try again.');
  } finally {
    saving.value = false;
  }
};

const getSelectedCaisseName = (caisseId) => {
  const caisse = caisses.value.find(c => c.id === caisseId);
  return caisse ? caisse.name : '';
};

const getSelectedCaisseLocation = (caisseId) => {
  const caisse = caisses.value.find(c => c.id === caisseId);
  return caisse ? (caisse.location || 'No location') : '';
};

const getSelectedCoffreName = (coffreId) => {
  const coffre = coffres.value.find(c => c.id === coffreId);
  return coffre ? coffre.name : '';
};

const getSelectedCoffreLocation = (coffreId) => {
  const coffre = coffres.value.find(c => c.id === coffreId);
  return coffre ? (coffre.location || 'No location') : '';
};

const getSelectedUserName = (userId) => {
  const user = users.value.find(u => u.id === userId);
  return user ? user.name : '';
};

const getSelectedUserEmail = (userId) => {
  const user = users.value.find(u => u.id === userId);
  return user ? user.email : '';
};

const formatCurrency = (amount) => {
  if (!amount) return '0.00 DA';
  return new Intl.NumberFormat('fr-DZ', {
    style: 'currency',
    currency: 'DZD',
    minimumFractionDigits: 2
  }).format(amount);
};

const showToast = (severity, summary, detail) => {
  toast.add({ severity, summary, detail, life: 3000 });
};

onMounted(() => {
  loadData();
});
</script>

<template>
  <div class="tw-fixed tw-inset-0 tw-bg-black/50 tw-flex tw-items-center tw-justify-center tw-z-[1050] tw-backdrop-blur-sm"
    @click.self="$emit('close')">
    <div
      class="tw-bg-white tw-rounded-xl tw-w-[90%] md:tw-max-w-xl lg:tw-max-w-3xl tw-max-h-[95vh] tw-overflow-y-auto tw-shadow-2xl tw-animate-slideIn">
      <div class="tw-p-6 tw-border-b tw-border-gray-200 tw-flex tw-justify-between tw-items-center tw-bg-gradient-to-br tw-from-emerald-500 tw-to-emerald-700 tw-text-white tw-rounded-t-xl">
        <h4 class="tw-m-0 tw-font-semibold tw-text-xl md:tw-text-2xl tw-flex tw-items-center tw-gap-2">
          <i class="pi pi-play"></i>
          Open New Cash Register Session
        </h4>
        <button type="button" class="tw-bg-transparent tw-border-none tw-text-white tw-text-xl tw-p-2 tw-rounded-full tw-w-[35px] tw-h-[35px] tw-flex tw-items-center tw-justify-center tw-transition-all tw-duration-200 tw-cursor-pointer hover:tw-bg-white/20 hover:tw-rotate-90" @click="$emit('close')">
          <i class="pi pi-times"></i>
        </button>
      </div>

      <div class="tw-p-8 md:tw-p-12">
        <form @submit.prevent="openSession">
          <div class="tw-mb-6">
            <label for="caisse_id" class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-flex tw-items-center tw-gap-2 tw-text-sm md:tw-text-base">
              <i class="pi pi-desktop"></i>
              Cash Register *
            </label>
            <Dropdown
              v-model="form.caisse_id"
              :options="caisses"
              option-label="display_name"
              option-value="id"
              placeholder="Select cash register"
              class="tw-w-full"
              appendTo="self"
              :class="{ 'p-invalid': errors.caisse_id }"
              :disabled="saving || isPreselectedCaisse"
            >
              <template #value="slotProps">
                <div v-if="slotProps.value" class="tw-flex tw-items-center tw-gap-3 tw-p-2">
                  <div class="tw-w-10 tw-h-10 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-text-white tw-text-base tw-flex-shrink-0 tw-bg-gradient-to-br tw-from-amber-500 tw-to-amber-700">
                    <i class="pi pi-desktop"></i>
                  </div>
                  <div class="tw-flex-1">
                    <div class="tw-font-semibold tw-text-gray-900 tw-text-sm">{{ getSelectedCaisseName(slotProps.value) }}</div>
                    <div class="tw-text-gray-500 tw-text-xs">{{ getSelectedCaisseLocation(slotProps.value) }}</div>
                  </div>
                </div>
                <span v-else>{{ slotProps.placeholder }}</span>
              </template>
              <template #option="slotProps">
                <div class="tw-flex tw-items-center tw-gap-3 tw-p-2 tw-rounded-lg tw-transition-colors tw-duration-200 hover:tw-bg-gray-100">
                  <div class="tw-w-10 tw-h-10 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-text-white tw-text-base tw-flex-shrink-0 tw-bg-gradient-to-br tw-from-amber-500 tw-to-amber-700">
                    <i class="pi pi-desktop"></i>
                  </div>
                  <div class="tw-flex-1">
                    <div class="tw-font-semibold tw-text-gray-900 tw-text-sm">{{ slotProps.option.name }}</div>
                    <div class="tw-text-gray-500 tw-text-xs">{{ slotProps.option.location || 'No location' }}</div>
                    <div v-if="slotProps.option.service?.name" class="tw-text-gray-500 tw-text-xs">{{ slotProps.option.service?.name }}</div>
                  </div>
                  <div class="tw-flex-shrink-0">
                    <Tag 
                      :value="slotProps.option.is_active ? 'Active' : 'Inactive'"
                      :severity="slotProps.option.is_active ? 'success' : 'danger'"
                      class="tw-text-xs tw-py-1 tw-px-2"
                    />
                  </div>
                </div>
              </template>
            </Dropdown>
            <small v-if="errors.caisse_id" class="p-error tw-text-red-500 tw-mt-1 tw-block">
              {{ errors.caisse_id }}
            </small>
            <small v-if="isPreselectedCaisse" class="tw-text-xs tw-text-gray-500 tw-mt-1 tw-block tw-italic">
              This session will be created for the pre-selected cash register
            </small>
          </div>

          <div class="tw-mb-6">
            <label for="coffre_id" class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-flex tw-items-center tw-gap-2 tw-text-sm md:tw-text-base">
              <i class="pi pi-lock"></i>
              Safe 
            </label>
            <Dropdown
              v-model="form.coffre_id"
              :options="coffres"
              option-label="display_name"
              option-value="id"
              placeholder="Select safe "
              appendTo="self"
              class="tw-w-full"
              :class="{ 'p-invalid': errors.coffre_id }"
              :disabled="saving"
              showClear
            >
              <template #value="slotProps">
                <div v-if="slotProps.value" class="tw-flex tw-items-center tw-gap-3 tw-p-2">
                  <div class="tw-w-10 tw-h-10 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-text-white tw-text-base tw-flex-shrink-0 tw-bg-gradient-to-br tw-from-indigo-500 tw-to-violet-700">
                    <i class="pi pi-lock"></i>
                  </div>
                  <div class="tw-flex-1">
                    <div class="tw-font-semibold tw-text-gray-900 tw-text-sm">{{ getSelectedCoffreName(slotProps.value) }}</div>
                    <div class="tw-text-gray-500 tw-text-xs">{{ getSelectedCoffreLocation(slotProps.value) }}</div>
                  </div>
                </div>
                <span v-else>{{ slotProps.placeholder }}</span>
              </template>
              <template #option="slotProps">
                <div class="tw-flex tw-items-center tw-gap-3 tw-p-2 tw-rounded-lg tw-transition-colors tw-duration-200 hover:tw-bg-gray-100">
                  <div class="tw-w-10 tw-h-10 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-text-white tw-text-base tw-flex-shrink-0 tw-bg-gradient-to-br tw-from-indigo-500 tw-to-violet-700">
                    <i class="pi pi-lock"></i>
                  </div>
                  <div class="tw-flex-1">
                    <div class="tw-font-semibold tw-text-gray-900 tw-text-sm">{{ slotProps.option.name }}</div>
                    <div class="tw-text-gray-500 tw-text-xs">{{ slotProps.option.location || 'No location' }}</div>
                    <div class="tw-text-gray-500 tw-text-xs">Balance: {{ formatCurrency(slotProps.option.current_balance) }}</div>
                  </div>
                </div>
              </template>
            </Dropdown>
            <small v-if="errors.coffre_id" class="p-error tw-text-red-500 tw-mt-1 tw-block">
              {{ errors.coffre_id }}
            </small>
            <small class="tw-text-xs tw-text-gray-500 tw-mt-1 tw-block tw-italic">
              Select a safe to link this session for better tracking
            </small>
          </div>

          <div class="tw-mb-6">
            <label for="user_id" class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-flex tw-items-center tw-gap-2 tw-text-sm md:tw-text-base">
              <i class="pi pi-user"></i>
              User *
            </label>
            <Dropdown
              v-model="form.user_id"
              :options="users"
              option-label="display_name"
              option-value="id"
              placeholder="Select user"
              class="tw-w-full"
              appendTo="self"
              :class="{ 'p-invalid': errors.user_id }"
              :disabled="saving"
            >
              <template #value="slotProps">
                <div v-if="slotProps.value" class="tw-flex tw-items-center tw-gap-3 tw-p-2">
                  <div class="tw-w-10 tw-h-10 tw-flex tw-items-center tw-justify-center tw-flex-shrink-0">
                    <Avatar
                      :label="getSelectedUserName(slotProps.value)?.charAt(0).toUpperCase()"
                      size="normal"
                      shape="circle"
                    />
                  </div>
                  <div class="tw-flex-1">
                    <div class="tw-font-semibold tw-text-gray-900 tw-text-sm">{{ getSelectedUserName(slotProps.value) }}</div>
                    <div class="tw-text-gray-500 tw-text-xs">{{ getSelectedUserEmail(slotProps.value) }}</div>
                  </div>
                </div>
                <span v-else>{{ slotProps.placeholder }}</span>
              </template>
              <template #option="slotProps">
                <div class="tw-flex tw-items-center tw-gap-3 tw-p-2 tw-rounded-lg tw-transition-colors tw-duration-200 hover:tw-bg-gray-100">
                  <div class="tw-w-10 tw-h-10 tw-flex tw-items-center tw-justify-center tw-flex-shrink-0">
                    <Avatar
                      :label="slotProps.option.name.charAt(0).toUpperCase()"
                      size="normal"
                      shape="circle"
                    />
                  </div>
                  <div class="tw-flex-1">
                    <div class="tw-font-semibold tw-text-gray-900 tw-text-sm">{{ slotProps.option.name }}</div>
                    <div class="tw-text-gray-500 tw-text-xs">{{ slotProps.option.email }}</div>
                  </div>
                </div>
              </template>
            </Dropdown>
            <small v-if="errors.user_id" class="p-error tw-text-red-500 tw-mt-1 tw-block">
              {{ errors.user_id }}
            </small>
          </div>

          <div class="tw-mb-6">
            <label for="opening_amount" class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-flex tw-items-center tw-gap-2 tw-text-sm md:tw-text-base">
              <i class="pi pi-dollar"></i>
              Opening Amount *
            </label>
            <div class="p-inputgroup">
              <InputNumber
                v-model="form.opening_amount"
                :min-fraction-digits="2"
                :max-fraction-digits="2"
                :min="0"
                placeholder="0.00"
                class="tw-w-full"
                :class="{ 'p-invalid': errors.opening_amount || amountExceedsCoffre }"
                :disabled="saving"
              />
              <span class="p-inputgroup-addon">DA</span>
            </div>
            <small v-if="errors.opening_amount" class="p-error tw-text-red-500 tw-mt-1 tw-block">
              {{ errors.opening_amount }}
            </small>
            <small v-else-if="amountExceedsCoffre" class="p-error tw-text-red-500 tw-mt-1 tw-block">
              Selected safe balance is {{ formatCurrency(selectedCoffreBalance) }} — opening amount cannot exceed it.
            </small>
          </div>

          <div class="tw-mb-6">
            <label for="opening_notes" class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-flex tw-items-center tw-gap-2 tw-text-sm md:tw-text-base">
              <i class="pi pi-file-edit"></i>
              Description / Opening Notes
            </label>
            <Textarea
              v-model="form.opening_notes"
              rows="3"
              placeholder="Enter session description or opening notes (optional)"
              class="tw-w-full"
              :class="{ 'p-invalid': errors.opening_notes }"
              :disabled="saving"
            />
            <small v-if="errors.opening_notes" class="p-error tw-text-red-500 tw-mt-1 tw-block">
              {{ errors.opening_notes }}
            </small>
            <small class="tw-text-xs tw-text-gray-500 tw-mt-1 tw-block tw-italic">
              Add any relevant notes about this session opening
            </small>
          </div>
          
          <div class="tw-flex tw-flex-col sm:tw-flex-row tw-gap-4 tw-mt-8">
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
              :label="saving ? 'Opening Session...' : 'Open Session'"
              :icon="saving ? 'pi pi-spin pi-spinner' : 'pi pi-play'"
              class="p-button-success tw-flex-1"
              :disabled="saving || !isFormValid"
            />
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
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

/* All other styles are now Tailwind utility classes. Only PrimeVue overrides remain here. */
:deep(.p-dropdown),
:deep(.p-inputnumber-input),
:deep(.p-inputtextarea) {
  @apply rounded-xl tw-border tw-border-gray-300 tw-transition-all tw-duration-200;
}

:deep(.p-dropdown:not(.p-disabled):hover),
:deep(.p-inputnumber-input:hover),
:deep(.p-inputtextarea:hover) {
  @apply border-emerald-500;
}

:deep(.p-dropdown:not(.p-disabled).p-focus),
:deep(.p-inputnumber-input:focus),
:deep(.p-inputtextarea:focus) {
  @apply border-emerald-500 tw-shadow-md tw-shadow-emerald-500/25;
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

:deep(.p-inputgroup-addon) {
  @apply bg-gray-100 tw-border-gray-300 tw-text-emerald-500 tw-font-semibold tw-rounded-r-lg;
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