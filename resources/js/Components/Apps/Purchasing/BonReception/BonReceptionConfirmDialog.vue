<template>
  <Dialog 
    v-model:visible="visible"
    :modal="true"
    :closable="false"
    header="Confirm Reception"
    class="tw-w-full tw-max-w-4xl"
  >
    <!-- Loading State -->
    <div v-if="loading" class="tw-flex tw-justify-center tw-items-center tw-py-12">
      <ProgressSpinner />
    </div>

    <!-- Content -->
    <div v-else>
      <!-- Surplus Items Preview -->
      <div v-if="surplusPreview?.has_surplus" class="tw-mb-6">
        <Message severity="warn" :closable="false">
          <div class="tw-space-y-2">
            <div class="tw-font-semibold tw-text-lg">
              <i class="pi pi-exclamation-triangle tw-mr-2"></i>
              Surplus Items Detected
            </div>
            <div class="tw-text-sm">
              This reception contains <strong>{{ surplusPreview.total_surplus_items }}</strong> item(s) with surplus quantities.
              <br>
              Total surplus: <strong>{{ surplusPreview.total_surplus_quantity }}</strong> units worth 
              <strong>{{ formatCurrency(surplusPreview.total_surplus_value) }}</strong>
            </div>
          </div>
        </Message>

        <!-- Surplus Items Table -->
        <div class="tw-mt-4">
          <div class="tw-font-medium tw-text-gray-700 tw-mb-2">Surplus Item Details:</div>
          <DataTable :value="surplusPreview.surplus_items" class="tw-text-sm">
            <Column field="product.name" header="Product">
              <template #body="slotProps">
                <div>
                  <div class="tw-font-medium">{{ slotProps.data.product.name }}</div>
                  <div class="tw-text-xs tw-text-gray-500">{{ slotProps.data.product.code }}</div>
                </div>
              </template>
            </Column>
            <Column field="quantity_ordered" header="Ordered">
              <template #body="slotProps">
                <span>{{ slotProps.data.quantity_ordered }}</span>
              </template>
            </Column>
            <Column field="quantity_received" header="Received">
              <template #body="slotProps">
                <span class="tw-font-semibold">{{ slotProps.data.quantity_received }}</span>
              </template>
            </Column>
            <Column field="quantity_surplus" header="Surplus">
              <template #body="slotProps">
                <Tag :value="`+${slotProps.data.quantity_surplus}`" severity="warning" />
              </template>
            </Column>
            <Column field="total_surplus_value" header="Value">
              <template #body="slotProps">
                {{ formatCurrency(slotProps.data.total_surplus_value) }}
              </template>
            </Column>
          </DataTable>
        </div>

        <!-- Action Selection -->
        <div class="tw-mt-6 tw-p-4 tw-bg-gray-50 tw-rounded-lg">
          <div class="tw-font-medium tw-text-gray-700 tw-mb-4">How would you like to handle the surplus items?</div>
          
          <div class="tw-space-y-3">
            <div class="tw-flex tw-items-start tw-gap-3 tw-p-3 tw-bg-white tw-rounded tw-border" 
                 :class="surplusAction === 'return' ? 'tw-border-orange-500 tw-bg-orange-50' : 'tw-border-gray-300'">
              <RadioButton 
                v-model="surplusAction" 
                inputId="return-all" 
                value="return" 
              />
              <label for="return-all" class="tw-cursor-pointer tw-flex-1">
                <div class="tw-font-medium tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-reply tw-text-orange-500"></i>
                  Create Return Note for All Surplus
                </div>
                <div class="tw-text-sm tw-text-gray-600 tw-mt-1">
                  Automatically generate a return note for all surplus quantities to send back to supplier. 
                  The return will be created in draft status for review.
                </div>
              </label>
            </div>

            <div class="tw-flex tw-items-start tw-gap-3 tw-p-3 tw-bg-white tw-rounded tw-border"
                 :class="surplusAction === 'accept' ? 'tw-border-green-500 tw-bg-green-50' : 'tw-border-gray-300'">
              <RadioButton 
                v-model="surplusAction" 
                inputId="accept-all" 
                value="accept" 
              />
              <label for="accept-all" class="tw-cursor-pointer tw-flex-1">
                <div class="tw-font-medium tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-check-circle tw-text-green-500"></i>
                  Accept All Surplus
                </div>
                <div class="tw-text-sm tw-text-gray-600 tw-mt-1">
                  Keep all surplus items in inventory without creating a return. 
                  This will add the extra quantities to your stock.
                </div>
              </label>
            </div>

            <div class="tw-flex tw-items-start tw-gap-3 tw-p-3 tw-bg-white tw-rounded tw-border"
                 :class="surplusAction === 'partial' ? 'tw-border-blue-500 tw-bg-blue-50' : 'tw-border-gray-300'">
              <RadioButton 
                v-model="surplusAction" 
                inputId="partial-return" 
                value="partial" 
              />
              <label for="partial-return" class="tw-cursor-pointer tw-flex-1">
                <div class="tw-font-medium tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-filter tw-text-blue-500"></i>
                  Selective Return
                </div>
                <div class="tw-text-sm tw-text-gray-600 tw-mt-1">
                  Choose specific items and quantities to return. 
                  You can keep some surplus and return others.
                </div>
              </label>
            </div>
          </div>
        </div>

        <!-- Partial Return Details -->
        <div v-if="surplusAction === 'partial'" class="tw-mt-4 tw-p-4 tw-bg-blue-50 tw-rounded-lg tw-border tw-border-blue-200">
          <div class="tw-font-medium tw-mb-3 tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-list-check tw-text-blue-600"></i>
            Select Items to Return
          </div>
          <div class="tw-space-y-3">
            <div 
              v-for="item in surplusPreview.surplus_items" 
              :key="item.id"
              class="tw-flex tw-items-center tw-gap-4 tw-p-3 tw-bg-white tw-rounded tw-border"
              :class="selectedReturnItems[item.id] ? 'tw-border-blue-400' : 'tw-border-gray-300'"
            >
              <Checkbox 
                v-model="selectedReturnItems[item.id]" 
                :binary="true"
              />
              <div class="tw-flex-1">
                <div class="tw-font-medium">{{ item.product.name }}</div>
                <div class="tw-text-sm tw-text-gray-600">
                  <span class="tw-text-orange-600 tw-font-medium">+{{ item.quantity_surplus }}</span> surplus units
                  ({{ formatCurrency(item.total_surplus_value) }})
                </div>
              </div>
              <div v-if="selectedReturnItems[item.id]" class="tw-flex tw-items-center tw-gap-2">
                <div>
                  <label class="tw-text-xs tw-text-gray-600">Qty to Return:</label>
                  <InputNumber 
                    v-model="returnQuantities[item.id]"
                    :min="1"
                    :max="item.quantity_surplus"
                    class="tw-w-24"
                    :inputClass="'tw-text-sm'"
                  />
                </div>
                <div class="tw-flex-1">
                  <label class="tw-text-xs tw-text-gray-600">Reason:</label>
                  <Dropdown
                    v-model="returnReasons[item.id]"
                    :options="returnReasonOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select reason"
                    class="tw-w-full tw-text-sm"
                  />
                </div>
              </div>
            </div>
          </div>

          <div v-if="partialReturnSummary.count > 0" class="tw-mt-3 tw-p-3 tw-bg-blue-100 tw-rounded tw-text-sm">
            <strong>Summary:</strong> Returning {{ partialReturnSummary.count }} item(s) with 
            {{ partialReturnSummary.quantity }} total units worth {{ formatCurrency(partialReturnSummary.value) }}
          </div>
        </div>

        <!-- Notes -->
        <div class="tw-mt-4">
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
            Additional Notes (Optional)
          </label>
          <Textarea 
            v-model="returnNotes"
            rows="3"
            class="tw-w-full"
            placeholder="Any additional notes about the surplus items or return..."
          />
        </div>

      <!-- Service selection for BonEntree -->
      <div class="tw-mt-4">
        <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
          Assign to Service (optional)
        </label>
        <Dropdown
          v-model="selectedServiceId"
          :options="services"
          optionLabel="name"
          optionValue="id"
          placeholder="Select service (optional)"
          class="tw-w-full"
        />
      </div>
      </div>

      <!-- Shortage Items Preview -->
      <div v-if="surplusPreview?.has_shortage" class="tw-mb-6">
        <Message severity="error" :closable="false">
          <div class="tw-space-y-2">
            <div class="tw-font-semibold tw-text-lg">
              <i class="pi pi-exclamation-circle tw-mr-2"></i>
              Shortage Items Detected
            </div>
            <div class="tw-text-sm">
              This reception has <strong>{{ surplusPreview.total_shortage_items }}</strong> item(s) with shortage.
              <br>
              Total shortage: <strong>{{ surplusPreview.total_shortage_quantity }}</strong> units worth 
              <strong>{{ formatCurrency(surplusPreview.total_shortage_value) }}</strong>
            </div>
          </div>
        </Message>

        <!-- Shortage Items Table -->
        <div class="tw-mt-4">
          <DataTable :value="surplusPreview.shortage_items" class="tw-text-sm">
            <Column field="product.name" header="Product">
              <template #body="slotProps">
                <div>
                  <div class="tw-font-medium">{{ slotProps.data.product.name }}</div>
                  <div class="tw-text-xs tw-text-gray-500">{{ slotProps.data.product.code }}</div>
                </div>
              </template>
            </Column>
            <Column field="quantity_ordered" header="Ordered" />
            <Column field="quantity_received" header="Received" />
            <Column field="quantity_shortage" header="Shortage">
              <template #body="slotProps">
                <Tag :value="`-${slotProps.data.quantity_shortage}`" severity="danger" />
              </template>
            </Column>
          </DataTable>
        </div>
      </div>

      <!-- No Variance Message -->
      <div v-if="!surplusPreview?.has_surplus && !surplusPreview?.has_shortage" class="tw-text-center tw-py-8">
        <i class="pi pi-check-circle tw-text-green-500 tw-text-5xl tw-mb-3"></i>
        <div class="tw-text-lg tw-font-medium tw-mb-2">Perfect Reception!</div>
        <div class="tw-text-gray-600">
          All received quantities match the ordered quantities. Click confirm to complete the reception.
        </div>
      </div>

      <!-- Warning for incomplete selection -->
      <Message v-if="showSelectionWarning" severity="warn" :closable="false" class="tw-mt-4">
        Please select at least one item to return or choose a different action.
      </Message>
    </div>

    <template #footer>
      <div class="tw-flex tw-justify-between tw-items-center">
        <div class="tw-text-sm tw-text-gray-600">
          <span v-if="surplusPreview?.has_surplus">
            <i class="pi pi-info-circle tw-mr-1"></i>
            {{ getActionDescription }}
          </span>
        </div>
        <div class="tw-flex tw-gap-2">
          <Button 
            label="Cancel" 
            icon="pi pi-times" 
            @click="cancel"
            severity="secondary"
            :disabled="confirming"
          />
          <Button 
            label="Confirm Reception" 
            icon="pi pi-check" 
            @click="confirmReception"
            :loading="confirming"
          />
        </div>
      </div>
    </template>
  </Dialog>
</template>

<script setup>
import { ref, watch, reactive, computed, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import axios from 'axios'

// PrimeVue Components
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Message from 'primevue/message'
import RadioButton from 'primevue/radiobutton'
import Checkbox from 'primevue/checkbox'
import InputNumber from 'primevue/inputnumber'
import Dropdown from 'primevue/dropdown'
import Textarea from 'primevue/textarea'
import Tag from 'primevue/tag'
import ProgressSpinner from 'primevue/progressspinner'

const props = defineProps({
  modelValue: Boolean,
  bonReceptionId: {
    type: Number,
    required: true
  }
})

const emit = defineEmits(['update:modelValue', 'confirmed'])

const toast = useToast()

// Data
const visible = ref(props.modelValue)
const loading = ref(false)
const confirming = ref(false)
const surplusPreview = ref(null)
const surplusAction = ref('return') // 'return', 'accept', 'partial'
const selectedReturnItems = reactive({})
const returnQuantities = reactive({})
const returnReasons = reactive({})
const returnNotes = ref('')
const showSelectionWarning = ref(false)

// Options
const returnReasonOptions = [
  { label: 'Surplus/Overstock', value: 'Surplus quantity' },
  { label: 'Wrong Item', value: 'Wrong item delivered' },
  { label: 'Quality Issue', value: 'Quality issue' },
  { label: 'Not Ordered', value: 'Item not ordered' },
  { label: 'Other', value: 'Other reason' }
]

// Services for assigning BonEntree
const services = ref([])
const selectedServiceId = ref(null)

const fetchServices = async () => {
  try {
    const res = await axios.get('/api/services?per_page=1000')
    if (res.data && res.data.data) {
      services.value = res.data.data
    } else if (res.data) {
      services.value = res.data
    }
  } catch (err) {
    console.error('Error fetching services:', err)
  }
}

// Computed
const getActionDescription = computed(() => {
  switch (surplusAction.value) {
    case 'return':
      return 'A return note will be created for all surplus items'
    case 'accept':
      return 'All surplus items will be added to inventory'
    case 'partial':
      return partialReturnSummary.value.count > 0 
        ? `${partialReturnSummary.value.count} item(s) will be returned`
        : 'Select items to return'
    default:
      return ''
  }
})

const partialReturnSummary = computed(() => {
  const selectedItems = Object.keys(selectedReturnItems)
    .filter(id => selectedReturnItems[id])
    .map(id => {
      const item = surplusPreview.value?.surplus_items?.find(i => i.id == id)
      if (!item) return null
      const qty = returnQuantities[id] || 0
      return {
        quantity: qty,
        value: qty * item.unit_price
      }
    })
    .filter(Boolean)

  return {
    count: selectedItems.length,
    quantity: selectedItems.reduce((sum, item) => sum + item.quantity, 0),
    value: selectedItems.reduce((sum, item) => sum + item.value, 0)
  }
})

// Watch for visibility changes
watch(() => props.modelValue, (newVal) => {
  visible.value = newVal
  if (newVal) {
    loadSurplusPreview()
  }
})

watch(visible, (newVal) => {
  emit('update:modelValue', newVal)
  if (!newVal) {
    // Reset state when closing
    resetState()
  }
})

// Methods
const loadSurplusPreview = async () => {
  if (!props.bonReceptionId) return

  try {
    loading.value = true
    const response = await axios.get(`/api/bon-receptions/${props.bonReceptionId}/surplus-preview`)
    
    if (response.data.status === 'success') {
      surplusPreview.value = response.data.data
      
      // Initialize selections for partial return
      if (surplusPreview.value.surplus_items) {
        surplusPreview.value.surplus_items.forEach(item => {
          selectedReturnItems[item.id] = false
          returnQuantities[item.id] = item.quantity_surplus
          returnReasons[item.id] = 'Surplus quantity'
        })
      }

      // Default action based on surplus presence
      if (!surplusPreview.value.has_surplus) {
        surplusAction.value = 'accept' // No action needed if no surplus
      }
      // fetch services for assignment
      fetchServices()
    }
  } catch (err) {
    console.error('Error loading surplus preview:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load surplus information'
    })
  } finally {
    loading.value = false
  }
}

const confirmReception = async () => {
  // Validate partial return selection
  if (surplusAction.value === 'partial') {
    const hasSelection = Object.values(selectedReturnItems).some(v => v)
    if (!hasSelection) {
      showSelectionWarning.value = true
      return
    }
  }

  try {
    confirming.value = true
    showSelectionWarning.value = false
    
    const data = {
      surplus_action: surplusAction.value,
      return_notes: returnNotes.value,
      service_id: selectedServiceId.value
    }
    
    // Add partial return items if applicable
    if (surplusAction.value === 'partial') {
      data.items_to_return = Object.keys(selectedReturnItems)
        .filter(itemId => selectedReturnItems[itemId])
        .map(itemId => ({
          item_id: parseInt(itemId),
          quantity_to_return: returnQuantities[itemId],
          reason: returnReasons[itemId]
        }))
    }
    
    const response = await axios.post(`/api/bon-receptions/${props.bonReceptionId}/confirm`, data)
    
    if (response.data.status === 'success') {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: response.data.message || 'Reception confirmed successfully',
        life: 5000
      })
      
      emit('confirmed', response.data.data)
      visible.value = false
    }
  } catch (err) {
    console.error('Error confirming reception:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Failed to confirm reception'
    })
  } finally {
    confirming.value = false
  }
}

const cancel = () => {
  visible.value = false
}

const resetState = () => {
  surplusAction.value = 'return'
  Object.keys(selectedReturnItems).forEach(key => {
    selectedReturnItems[key] = false
  })
  returnNotes.value = ''
  showSelectionWarning.value = false
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount || 0)
}

// Lifecycle
onMounted(() => {
  if (visible.value) {
    loadSurplusPreview()
  }
})
</script>

<style scoped>
@reference "../../../../../css/app.css";

:deep(.p-dialog) {
  max-height: 90vh;
}

:deep(.p-dialog-content) {
  overflow-y: auto;
  max-height: calc(90vh - 200px);
}

:deep(.p-inputnumber-input) {
}

:deep(.p-dropdown-label) {
}
</style>
