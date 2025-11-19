```vue
<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import axios from 'axios'
import Dropdown from 'primevue/dropdown'
import Button from 'primevue/button'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import InputSwitch from 'primevue/inputswitch'
import Tag from 'primevue/tag'
import Password from 'primevue/password'
import { useRouter } from 'vue-router'

const emit = defineEmits(['close','created'])

// props (optional) allow passing session/caisse context
const props = defineProps({
  caisseSessionId: { type: [Number, String], default: null },
  caisseId: { type: [Number, String], default: null },
  id: { type: [Number, String], default: null }
})

const users = ref([])
const form = ref({
  amount: null,
  to_user_id: null,
  description: '',
  have_problems: false,
  current_user_password: '',
  next_cashier_password: ''
})

// Step management
const currentStep = ref(1)
const submitting = ref(false)
const error = ref(null)
const router = useRouter()

// Session info and theoretical amount
const sessionInfo = ref(null)
const theoreticalAmount = ref(0)

// Computed properties
const isFormValid = computed(() => {
  return form.value.amount && form.value.to_user_id
})

const canProceedToStep2 = computed(() => {
  return isFormValid.value && form.value.current_user_password.trim() !== ''
})

const canSubmit = computed(() => {
  return canProceedToStep2.value && form.value.next_cashier_password.trim() !== ''
})

const selectedUser = computed(() => {
  return users.value.find(u => u.id === form.value.to_user_id) || null
})

// Load session information to calculate theoretical amount
const loadSessionInfo = async () => {
  if (!props.caisseSessionId) return
  
  try {
    const res = await axios.get(`/api/caisse-sessions/${props.caisseSessionId}`, { 
      headers: { Accept: 'application/json' }, 
      withCredentials: true 
    })
    sessionInfo.value = res?.data?.data ?? res?.data ?? null
    
    // Calculate theoretical amount (opening + payments + donations)
    if (sessionInfo.value) {
      await calculateTheoreticalAmount()
    }
  } catch (e) {
    console.error('Failed to load session info', e)
  }
}

// Calculate theoretical amount from session transactions
const calculateTheoreticalAmount = async () => {
  try {
    const res = await axios.get(`/api/financial-transactions`, {
      params: { caisse_session_id: props.caisseSessionId },
      headers: { Accept: 'application/json' },
      withCredentials: true
    })
    
    const transactions = res?.data?.data ?? []
    let totalPayments = 0
    let totalDonations = 0
    
    transactions.forEach(tx => {
      if (tx.transaction_type === 'payment') {
        totalPayments += Number(tx.amount || 0)
      } else if (tx.transaction_type === 'donation') {
        totalDonations += Number(tx.amount || 0)
      }
    })
    
    const openingAmount = Number(sessionInfo.value?.opening_amount || 0)
    theoreticalAmount.value = openingAmount + totalPayments + totalDonations
    
    // Set the amount to theoretical amount by default
    if (!form.value.amount) {
      form.value.amount = theoreticalAmount.value
    }
  } catch (e) {
    console.error('Failed to calculate theoretical amount', e)
    theoreticalAmount.value = Number(sessionInfo.value?.opening_amount || 0)
  }
}

const loadUsers = async () => {
  try {
    const res = await axios.get('/api/users')
    const payload = res?.data?.data ?? res?.data ?? []
    users.value = Array.isArray(payload) ? payload : []
  } catch (e) {
    console.error('load users', e)
    users.value = []
  }
}

// Validate current user password
const validateCurrentUserPassword = async () => {
  if (!form.value.current_user_password) {
    error.value = 'Current user password is required'
    return false
  }
  
  try {
    const res = await axios.post('/api/auth/validate-password', {
      password: form.value.current_user_password
    }, {
      headers: { Accept: 'application/json' },
      withCredentials: true
    })
    
    if (res.data?.valid) {
      error.value = null
      return true
    } else {
      error.value = 'Invalid current user password'
      return false
    }
  } catch (e) {
    error.value = 'Failed to validate current user password'
    return false
  }
}

// Validate next cashier password
const validateNextCashierPassword = async () => {
  if (!form.value.next_cashier_password || !form.value.to_user_id) {
    error.value = 'Next cashier password and user selection are required'
    return false
  }
  
  try {
    const res = await axios.post('/api/auth/validate-user-password', {
      user_id: form.value.to_user_id,
      password: form.value.next_cashier_password
    }, {
      headers: { Accept: 'application/json' },
      withCredentials: true
    })
    
    if (res.data?.valid) {
      error.value = null
      return true
    } else {
      error.value = 'Invalid password for selected cashier'
      return false
    }
  } catch (e) {
    error.value = 'Failed to validate next cashier password'
    return false
  }
}

const goToStep2 = async () => {
  if (!canProceedToStep2.value) {
    error.value = 'Please fill all required fields and provide your password'
    return
  }
  
  const isValid = await validateCurrentUserPassword()
  if (isValid) {
    currentStep.value = 2
    error.value = null
  }
}

const goBackToStep1 = () => {
  currentStep.value = 1
  error.value = null
}

onMounted(async () => {
  await Promise.all([
    loadUsers(),
    loadSessionInfo()
  ])
})

const close = () => emit('close')

const submit = async () => {
  if (!canSubmit.value) {
    error.value = 'Please complete all required fields and password validations'
    return
  }
  
  submitting.value = true
  error.value = null
  
  // Validate both passwords before proceeding
  const currentPasswordValid = await validateCurrentUserPassword()
  const nextPasswordValid = await validateNextCashierPassword()
  
  if (!currentPasswordValid || !nextPasswordValid) {
    error.value = 'Invalid password validation'
    submitting.value = false
    return
  }
  
  try {
    // Validate next cashier password before submitting
    const isNextPasswordValid = await validateNextCashierPassword()
    if (!isNextPasswordValid) {
      submitting.value = false
      return
    }
    
    const payload = {
      id: props.id,
      caisse_id: props.caisseId,
      to_user_id: form.value.to_user_id,
      amount_sended: form.value.amount,
      caisse_session_id: props.caisseSessionId || null,
      description: form.value.description,
      have_problems: form.value.have_problems || false,
      status: 'transferred',
      theoretical_amount: theoreticalAmount.value
    }

    const res = await axios.post('/api/caisse-transfers', payload, {
      headers: { Accept: 'application/json' },
      withCredentials: true
    })

    const transfer = res?.data?.data ?? res?.data ?? null

    // persist pending transfer id so it survives reloads
    // if (transfer && transfer.id) {
    //   localStorage.setItem('pending_caisse_transfer_id', String(transfer.id))
    // }

    // emit server-saved transfer (not the local payload)
    emit('created', transfer)
    close()
  } catch (e) {
    console.error('create passing error', e)
    error.value = e.response?.data?.message ?? 'Failed to create passing.'
  } finally {
    submitting.value = false
  }
}

// Watch for changes in theoretical amount to update form amount
watch(theoreticalAmount, (newVal) => {
  if (newVal && !form.value.amount) {
    form.value.amount = newVal
  }
})

// Format currency helper
const formatCurrency = (amount) => {
  const value = Number(amount ?? 0)
  return new Intl.NumberFormat('fr-DZ', {
    style: 'currency',
    currency: 'DZD',
    minimumFractionDigits: 2
  }).format(value)
}
</script>

<template>
  <!-- Enhanced Modal Backdrop -->
  <div class="tw-fixed tw-inset-0 tw-bg-gradient-to-br tw-from-slate-900/80 tw-via-slate-800/60 tw-to-slate-900/80 tw-backdrop-blur-sm tw-flex tw-items-center tw-justify-center tw-z-[1050] tw-p-4">
    <!-- Enhanced Modal Container (smaller size) -->
    <div class="tw-bg-white tw-w-full tw-max-w-md tw-rounded-xl tw-overflow-hidden tw-shadow-xl tw-border tw-border-gray-100 tw-transform tw-transition-all tw-duration-300">
      
      <!-- Enhanced Header with Gradient and Progress -->
      <div class="tw-relative tw-bg-gradient-to-r tw-from-indigo-600 tw-via-blue-600 tw-to-cyan-600 tw-text-white tw-overflow-hidden">
        <div class="tw-absolute tw-inset-0 tw-bg-black/10"></div>
        <div class="tw-relative tw-px-6 tw-py-4">
          <div class="tw-flex tw-items-center tw-justify-between tw-mb-3">
            <div class="tw-flex tw-items-center tw-space-x-2">
              <div class="tw-bg-white/20 tw-p-1.5 tw-rounded-lg tw-backdrop-blur-sm">
                <i class="pi pi-exchange tw-text-lg"></i>
              </div>
              <div>
                <h3 class="tw-text-xl tw-font-bold tw-mb-0.5">Cashier Transfer</h3>
                <p class="tw-text-blue-100 tw-text-xs tw-font-medium">Step {{ currentStep }} of 2</p>
              </div>
            </div>
            <button 
              class="tw-text-white/80 hover:tw-text-white hover:tw-bg-white/10 tw-transition-all tw-duration-200 tw-p-1.5 tw-rounded-md" 
              @click="close"
            >
              <i class="pi pi-times tw-text-base"></i>
            </button>
          </div>
          
          <!-- Enhanced Progress Bar -->
          <div class="tw-relative tw-bg-white/20 tw-rounded-full tw-h-1.5 tw-overflow-hidden">
            <div 
              class="tw-bg-gradient-to-r tw-from-white tw-to-blue-200 tw-h-full tw-transition-all tw-duration-500 tw-ease-out tw-rounded-full"
              :style="{ width: `${(currentStep / 2) * 100}%` }"
            ></div>
          </div>
        </div>
      </div>

      <!-- Step 1: Enhanced Transfer Details -->
      <div v-if="currentStep === 1" class="tw-p-4">
        <div class="tw-space-y-6">
          
          <!-- Enhanced Theoretical Amount Card -->
          <div v-if="theoreticalAmount > 0" class="tw-relative tw-bg-gradient-to-r tw-from-emerald-50 tw-to-green-50 tw-border tw-border-emerald-200 tw-rounded-lg tw-p-4 tw-shadow-sm">
            <div class="tw-absolute tw-top-2 tw-right-2">
              <Tag value="Calculated" severity="success" class="tw-bg-emerald-500 tw-text-white tw-text-xs" />
            </div>
            <div class="tw-flex tw-items-start tw-space-x-3">
              <div class="tw-bg-emerald-500 tw-p-2 tw-rounded-lg tw-text-white">
                <i class="pi pi-calculator tw-text-lg"></i>
              </div>
              <div class="tw-flex-1">
                <h4 class="tw-text-sm tw-font-bold tw-text-emerald-800 tw-mb-0.5">Theoretical Amount</h4>
                <p class="tw-text-xl tw-font-black tw-text-emerald-900 tw-mb-1">{{ formatCurrency(theoreticalAmount) }}</p>
                <p class="tw-text-xs tw-text-emerald-600 tw-bg-emerald-100 tw-px-2 tw-py-0.5 tw-rounded-full tw-inline-block">
                  Opening + Payments + Donations
                </p>
              </div>
            </div>
          </div>

          <!-- Enhanced Form Grid -->
          <div class="tw-grid tw-grid-cols-1 tw-gap-4">
            
            <!-- Enhanced Amount Input -->
            <div>
              <label class="tw-flex tw-items-center tw-text-xs tw-font-bold tw-text-gray-800 tw-mb-2">
                <div class="tw-bg-blue-500 tw-p-1 tw-rounded-md tw-mr-2">
                  <i class="pi pi-money-bill tw-text-white tw-text-xs"></i>
                </div>
                Actual Amount to Transfer
              </label>
              <div class="tw-relative">
                <InputNumber
                  v-model="form.amount"
                  inputClass="tw-w-full tw-h-10 tw-text-base tw-font-semibold tw-pl-3 tw-pr-3 tw-bg-gray-50 tw-border-2 focus:tw-bg-white"
                  :min="0"
                  :step="0.01"
                  placeholder="0.00"
                  :invalid="!!error && !form.amount"
                  :disabled="submitting"
                />
              </div>
              <p class="tw-text-xs tw-text-gray-500 tw-mt-1 tw-flex tw-items-center">
                <i class="pi pi-info-circle tw-mr-1"></i>
                Should match cash count
              </p>
            </div>

            <!-- Enhanced User Selection -->
            <div>
              <label class="tw-flex tw-items-center tw-text-xs tw-font-bold tw-text-gray-800 tw-mb-2">
                <div class="tw-bg-purple-500 tw-p-1 tw-rounded-md tw-mr-2">
                  <i class="pi pi-user tw-text-white tw-text-xs"></i>
                </div>
                Transfer To Cashier
              </label>
              <Dropdown
                appendTo="self"
                v-model="form.to_user_id"
                :options="users"
                optionLabel="name"
                optionValue="id"
                placeholder="Select cashier"
                class="tw-w-full tw-text-sm"
                :invalid="!!error && !form.to_user_id"
                :disabled="submitting"
              />
            </div>

            <!-- Enhanced Description -->
            <div>
              <label class="tw-flex tw-items-center tw-text-xs tw-font-bold tw-text-gray-800 tw-mb-2">
                <div class="tw-bg-orange-500 tw-p-1 tw-rounded-md tw-mr-2">
                  <i class="pi pi-file-edit tw-text-white tw-text-xs"></i>
                </div>
                Description (Optional)
              </label>
              <Textarea 
                v-model="form.description" 
                rows="2" 
                class="tw-w-full tw-resize-none tw-bg-gray-50 focus:tw-bg-white tw-border-2 tw-text-sm" 
                placeholder="Notes about transfer..."
                :disabled="submitting"
              />
            </div>

          </div>

          <!-- Enhanced Problem Toggle Card -->
          <div class="tw-bg-gradient-to-r tw-from-orange-50 tw-to-red-50 tw-border tw-border-orange-200 tw-rounded-lg tw-p-4">
            <div class="tw-flex tw-items-center tw-justify-between">
              <div class="tw-flex tw-items-center tw-space-x-3">
                <div class="tw-bg-orange-500 tw-p-2 tw-rounded-md">
                  <i class="pi pi-exclamation-triangle tw-text-white tw-text-base"></i>
                </div>
                <div>
                  <h4 class="tw-font-bold tw-text-gray-800 tw-text-sm tw-mb-0.5">Issue Report</h4>
                  <p class="tw-text-xs tw-text-gray-600">Discrepancy or problem</p>
                </div>
              </div>
              <InputSwitch 
                v-model="form.have_problems" 
                class="tw-transform tw-scale-90"
                :disabled="submitting"
              />
            </div>
          </div>

          <!-- Enhanced Password Input -->
          <div>
            <label class="tw-flex tw-items-center tw-text-xs tw-font-bold tw-text-gray-800 tw-mb-2">
              <div class="tw-bg-red-500 tw-p-1 tw-rounded-md tw-mr-2">
                <i class="pi pi-lock tw-text-white tw-text-xs"></i>
              </div>
              Your Password (Required)
            </label>
            <Password
              v-model="form.current_user_password"
              placeholder="Enter your password"
              :feedback="false"
              toggleMask
              class="tw-w-full"
              :invalid="!!error && !form.current_user_password"
              :disabled="submitting"
              inputClass="tw-h-10 tw-bg-gray-50 focus:tw-bg-white tw-border-2 tw-text-sm"
            />
            <p class="tw-text-xs tw-text-gray-500 tw-mt-1 tw-flex tw-items-center">
              <i class="pi pi-shield tw-mr-1 tw-text-green-500"></i>
              Security authorization
            </p>
          </div>

        </div>

        <!-- Enhanced Error Display -->
        <div v-if="error" class="tw-mt-4 tw-bg-gradient-to-r tw-from-red-500 tw-to-pink-500 tw-text-white tw-p-3 tw-rounded-lg tw-shadow">
          <div class="tw-flex tw-items-center tw-space-x-2">
            <i class="pi pi-exclamation-circle tw-text-base"></i>
            <span class="tw-font-medium tw-text-sm">{{ error }}</span>
          </div>
        </div>

        <!-- Enhanced Actions -->
        <div class="tw-flex tw-justify-between tw-items-center tw-mt-4 tw-pt-4 tw-border-t tw-border-gray-100">
          <Button 
            label="Cancel" 
            class="tw-bg-gray-100 hover:tw-bg-gray-200 tw-text-gray-700 tw-font-semibold tw-px-4 tw-py-2 tw-rounded-lg tw-transition-all tw-duration-200 tw-text-sm" 
            @click="close" 
            :disabled="submitting" 
            icon="pi pi-times"
            iconPos="left"
          />
          <Button 
            label="Next" 
            class="tw-bg-gradient-to-r tw-from-blue-600 tw-to-indigo-600 hover:tw-from-blue-700 hover:tw-to-indigo-700 tw-text-white tw-font-semibold tw-px-6 tw-py-2 tw-rounded-lg tw-shadow hover:tw-shadow-md tw-transition-all tw-duration-200 tw-transform hover:tw-scale-105 tw-text-sm" 
            :disabled="!canProceedToStep2 || submitting"
            @click="goToStep2"
            icon="pi pi-arrow-right"
            iconPos="right"
          />
        </div>
      </div>

      <!-- Step 2: Enhanced Verification -->
      <div v-if="currentStep === 2" class="tw-p-4">
        <div class="tw-space-y-6">
          
          <!-- Enhanced Transfer Summary Card -->
          <div class="tw-bg-gradient-to-br tw-from-slate-50 tw-to-gray-100 tw-border tw-border-gray-200 tw-rounded-lg tw-p-4 tw-shadow-sm">
            <h4 class="tw-flex tw-items-center tw-font-bold tw-text-gray-800 tw-text-base tw-mb-4">
              <div class="tw-bg-blue-500 tw-p-1.5 tw-rounded-md tw-mr-2">
                <i class="pi pi-check-circle tw-text-white"></i>
              </div>
              Transfer Summary
            </h4>
            
            <div class="tw-grid tw-grid-cols-1 tw-gap-3">
              <div class="tw-bg-white tw-p-3 tw-rounded-lg tw-border tw-border-gray-100">
                <p class="tw-text-xs tw-text-gray-600 tw-mb-0.5">Transfer Amount</p>
                <p class="tw-text-lg tw-font-black tw-text-gray-900">{{ formatCurrency(form.amount) }}</p>
              </div>
              <div class="tw-bg-white tw-p-3 tw-rounded-lg tw-border tw-border-gray-100">
                <p class="tw-text-xs tw-text-gray-600 tw-mb-0.5">Receiving Cashier</p>
                <p class="tw-text-base tw-font-bold tw-text-gray-900 tw-flex tw-items-center">
                  <i class="pi pi-user tw-mr-1.5 tw-text-blue-500"></i>
                  {{ selectedUser?.name || 'Unknown' }}
                </p>
              </div>
            </div>
            
            <div v-if="form.have_problems" class="tw-mt-3 tw-bg-orange-100 tw-border tw-border-orange-200 tw-p-3 tw-rounded-lg">
              <div class="tw-flex tw-items-center tw-text-orange-700 tw-text-sm">
                <i class="pi pi-exclamation-triangle tw-mr-2 tw-text-base"></i>
                <span class="tw-font-semibold">Issue Reported</span>
              </div>
              <p class="tw-text-xs tw-text-orange-600 tw-mt-0.5">Discrepancy has been noted</p>
            </div>
          </div>

          <!-- Enhanced Next Cashier Password -->
          <div>
            <label class="tw-flex tw-items-center tw-text-xs tw-font-bold tw-text-gray-800 tw-mb-3">
              <div class="tw-bg-green-500 tw-p-1.5 tw-rounded-md tw-mr-2">
                <i class="pi pi-shield tw-text-white"></i>
              </div>
              {{ selectedUser?.name || 'Next Cashier' }}'s Password
            </label>
            
            <div class="tw-bg-green-50 tw-border tw-border-green-200 tw-rounded-lg tw-p-3 tw-mb-3">
              <div class="tw-flex tw-items-start tw-space-x-2">
                <i class="pi pi-info-circle tw-text-green-600 tw-mt-0.5 tw-text-sm"></i>
                <div class="tw-text-xs tw-text-green-700">
                  <p class="tw-font-medium">Verification Required</p>
                  <p>Enter receiving cashier's password to confirm transfer</p>
                </div>
              </div>
            </div>
            
            <Password
              v-model="form.next_cashier_password"
              :placeholder="`Enter ${selectedUser?.name || 'cashier'}'s password`"
              :feedback="false"
              toggleMask
              class="tw-w-full"
              :invalid="!!error && !form.next_cashier_password"
              :disabled="submitting"
              inputClass="tw-h-10 tw-bg-gray-50 focus:tw-bg-white tw-border-2 tw-text-center tw-font-mono tw-text-base"
            />
          </div>

        </div>

        <!-- Enhanced Error Display -->
        <div v-if="error" class="tw-mt-4 tw-bg-gradient-to-r tw-from-red-500 tw-to-pink-500 tw-text-white tw-p-3 tw-rounded-lg tw-shadow">
          <div class="tw-flex tw-items-center tw-space-x-2">
            <i class="pi pi-exclamation-circle tw-text-base"></i>
            <span class="tw-font-medium tw-text-sm">{{ error }}</span>
          </div>
        </div>

        <!-- Enhanced Step 2 Actions -->
        <div class="tw-flex tw-justify-between tw-items-center tw-mt-4 tw-pt-4 tw-border-t tw-border-gray-100">
          <Button 
            label="Back" 
            class="tw-bg-gray-100 hover:tw-bg-gray-200 tw-text-gray-700 tw-font-semibold tw-px-4 tw-py-2 tw-rounded-lg tw-transition-all tw-duration-200 tw-text-sm" 
            @click="goBackToStep1" 
            :disabled="submitting"
            icon="pi pi-arrow-left"
            iconPos="left"
          />
          <Button 
            label="Complete" 
            class="tw-bg-gradient-to-r tw-from-green-600 tw-to-emerald-600 hover:tw-from-green-700 hover:tw-to-emerald-700 tw-text-white tw-font-semibold tw-px-6 tw-py-2 tw-rounded-lg tw-shadow hover:tw-shadow-md tw-transition-all tw-duration-200 tw-transform hover:tw-scale-105 tw-text-sm" 
            :loading="submitting" 
            :disabled="!canSubmit || submitting"
            @click="submit"
            icon="pi pi-check"
            iconPos="right"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@reference "../../../../../../resources/css/app.css";

/* Enhanced PrimeVue Overrides */
:deep(.p-inputtext),
:deep(.p-inputtextarea),
:deep(.p-dropdown),
:deep(.p-inputnumber-input) {
  @apply tw-rounded-lg tw-border-2 tw-border-gray-200 tw-shadow-sm tw-transition-all tw-duration-200 focus:tw-ring-4 focus:tw-ring-blue-500/20 focus:tw-border-blue-500 focus:tw-shadow-md;
}

:deep(.p-inputtext:hover),
:deep(.p-inputtextarea:hover),
:deep(.p-dropdown:not(.p-disabled):hover),
:deep(.p-inputnumber-input:hover) {
  @apply tw-border-gray-300 tw-shadow-md;
}

:deep(.p-dropdown-panel) {
  @apply tw-rounded-lg tw-shadow-lg tw-border tw-border-gray-100 tw-overflow-hidden;
}

:deep(.p-dropdown-panel .p-dropdown-item) {
  @apply tw-p-3 tw-transition-all tw-duration-150 tw-text-sm;
}

:deep(.p-dropdown-panel .p-dropdown-item:hover) {
  @apply tw-bg-blue-50;
}

:deep(.p-dropdown-panel .p-dropdown-items-wrapper) {
  @apply tw-max-h-40 tw-overflow-y-auto;
}

:deep(.p-button) {
  @apply tw-font-semibold tw-border-0 tw-shadow-sm tw-transition-all tw-duration-200 tw-text-sm;
}

:deep(.p-inputswitch.p-inputswitch-checked .p-inputswitch-slider) {
  @apply tw-bg-gradient-to-r tw-from-blue-500 tw-to-indigo-500;
}

:deep(.p-inputswitch .p-inputswitch-slider) {
  @apply tw-transition-all tw-duration-300 tw-shadow-sm;
}

:deep(.p-password-input) {
  @apply tw-w-full;
}

/* Custom scrollbar for dropdown */
:deep(.p-dropdown-items-wrapper::-webkit-scrollbar) {
  @apply tw-w-1.5;
}

:deep(.p-dropdown-items-wrapper::-webkit-scrollbar-track) {
  @apply tw-bg-gray-100;
}

:deep(.p-dropdown-items-wrapper::-webkit-scrollbar-thumb) {
  @apply tw-bg-gray-300 tw-rounded-full;
}

:deep(.p-dropdown-items-wrapper::-webkit-scrollbar-thumb:hover) {
  @apply tw-bg-gray-400;
}
</style>