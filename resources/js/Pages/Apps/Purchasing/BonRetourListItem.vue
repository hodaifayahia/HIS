<template>
  <div class="tw-p-6">
    <!-- Status Alert -->
    <Message v-if="bonRetour.status && bonRetour.status !== 'draft'" :severity="getStatusSeverity(bonRetour.status)" :closable="false" class="tw-mb-4">
      <div class="tw-flex tw-items-center tw-justify-between">
        <span>
          This return is currently <strong>{{ getStatusLabel(bonRetour.status) }}</strong>.
          {{ !isEditable ? 'Editing is restricted.' : '' }}
        </span>
        <div v-if="bonRetour.status === 'draft' && mode === 'edit'" class="tw-flex tw-gap-2">
          <Button 
            label="Submit for Approval" 
            icon="pi pi-send"
            size="small"
            @click="submitForApproval"
          />
        </div>
      </div>
    </Message>

    <!-- Main Content -->
    <TabView>
      <!-- Information Tab -->
      <TabPanel header="Information">
        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
          <div class="tw-space-y-4">
            <div>
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                Return Code
              </label>
              <InputText
                v-model="form.bon_retour_code"
                disabled
                class="tw-w-full"
              />
            </div>

            <div>
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                Return Type <span v-if="isEditing" class="tw-text-red-500">*</span>
              </label>
              <Dropdown
                v-model="form.return_type"
                :options="returnTypeOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Select return type"
                class="tw-w-full"
                :disabled="!isEditing"
                appendTo="self"
              />
            </div>

            <div>
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                Supplier <span v-if="isEditing" class="tw-text-red-500">*</span>
              </label>
              <Dropdown
                v-model="form.fournisseur_id"
                :options="suppliers"
                optionLabel="company_name"
                optionValue="id"
                placeholder="Select supplier"
                class="tw-w-full"
                filter
                :disabled="!isEditing || mode === 'edit'"
                @change="onSupplierChange"
                appendTo="self"
              />
            </div>

            <div>
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                Original Entry (Optional)
              </label>
              <Dropdown
                v-model="form.bon_entree_id"
                :options="bonEntrees"
                optionLabel="bon_entree_code"
                optionValue="id"
                placeholder="Select original entry"
                class="tw-w-full"
                filter
                :disabled="!isEditing || !form.fournisseur_id"
                appendTo="self"
              />
            </div>
          </div>

          <div class="tw-space-y-4">
            <div>
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                Return Date <span v-if="isEditing" class="tw-text-red-500">*</span>
              </label>
              <Calendar
                v-model="form.return_date"
                dateFormat="yy-mm-dd"
                showIcon
                class="tw-w-full"
                :disabled="!isEditing"
              />
            </div>

            <div>
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                Reference Invoice
              </label>
              <InputText
                v-model="form.reference_invoice"
                placeholder="Invoice number"
                class="tw-w-full"
                :disabled="!isEditing"
              />
            </div>

            <div>
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                Service
              </label>
              <Dropdown
                v-model="form.service_id"
                :options="services"
                optionLabel="name"
                optionValue="id"
                placeholder="Select service"
                class="tw-w-full"
                :disabled="!isEditing"
                filter
                showClear
              />
            </div>

            <div>
              <div class="tw-flex tw-items-center tw-gap-4 tw-mb-2">
                <Checkbox
                  v-model="form.credit_note_received"
                  :binary="true"
                  inputId="credit_note"
                  :disabled="!isEditing"
                />
                <label for="credit_note" class="tw-text-sm tw-font-medium tw-text-gray-700">
                  Credit Note Received
                </label>
              </div>
              
              <InputText
                v-if="form.credit_note_received"
                v-model="form.credit_note_number"
                placeholder="Credit note number"
                class="tw-w-full"
                :disabled="!isEditing"
              />
            </div>
          </div>

          <div class="md:tw-col-span-2">
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              Reason for Return
            </label>
            <Textarea
              v-model="form.reason"
              rows="3"
              placeholder="Describe the reason for return..."
              class="tw-w-full"
              :disabled="!isEditing"
            />
          </div>
        </div>
      </TabPanel>

      <!-- Items Tab -->
      <TabPanel header="Items">
        <div class="tw-mb-4 tw-flex tw-justify-between tw-items-center">
          <div class="tw-text-lg tw-font-semibold">Return Items</div>
          <Button 
            v-if="isEditing"
            icon="pi pi-plus" 
            label="Add Item"
            size="small"
            @click="addItem"
            :disabled="!form.fournisseur_id"
          />
        </div>

        <DataTable 
          :value="form.items" 
          responsiveLayout="scroll"
          class="tw-text-sm"
          :scrollable="true"
        >
          <Column header="#" class="tw-w-16">
            <template #body="slotProps">
              {{ slotProps.index + 1 }}
            </template>
          </Column>

          <Column header="Product" class="tw-min-w-48">
            <template #body="slotProps">
              <Dropdown
                v-if="isEditing"
                v-model="slotProps.data.product_id"
                :options="products"
                optionLabel="name"
                optionValue="id"
                placeholder="Select product"
                class="tw-w-full"
                filter
                @change="onProductChange($event, slotProps.index)"
                appendTo="body"
              />
              <span v-else>
                {{ getProductName(slotProps.data.product_id) }}
              </span>
            </template>
          </Column>

          <Column header="Batch/Serial" class="tw-min-w-32">
            <template #body="slotProps">
              <div v-if="isEditing" class="tw-space-y-1">
                <InputText
                  v-model="slotProps.data.batch_number"
                  placeholder="Batch"
                  class="tw-w-full tw-text-xs"
                />
                <InputText
                  v-model="slotProps.data.serial_number"
                  placeholder="Serial"
                  class="tw-w-full tw-text-xs"
                />
              </div>
              <div v-else class="tw-text-xs">
                <div v-if="slotProps.data.batch_number">Batch: {{ slotProps.data.batch_number }}</div>
                <div v-if="slotProps.data.serial_number">Serial: {{ slotProps.data.serial_number }}</div>
              </div>
            </template>
          </Column>

          <Column header="Quantity" class="tw-w-24">
            <template #body="slotProps">
              <InputNumber
                v-if="isEditing"
                v-model="slotProps.data.quantity_returned"
                :min="1"
                class="tw-w-full"
                @input="calculateItemTotal(slotProps.index)"
              />
              <span v-else>{{ slotProps.data.quantity_returned }}</span>
            </template>
          </Column>

          <Column header="Unit Price" class="tw-w-32">
            <template #body="slotProps">
              <InputNumber
                v-if="isEditing"
                v-model="slotProps.data.unit_price"
                mode="currency"
                currency="USD"
                class="tw-w-full"
                @input="calculateItemTotal(slotProps.index)"
              />
              <span v-else>{{ formatCurrency(slotProps.data.unit_price) }}</span>
            </template>
          </Column>

          <Column header="TVA %" class="tw-w-20">
            <template #body="slotProps">
              <InputNumber
                v-if="isEditing"
                v-model="slotProps.data.tva"
                :min="0"
                :max="100"
                suffix="%"
                class="tw-w-full"
                @input="calculateItemTotal(slotProps.index)"
              />
              <span v-else>{{ slotProps.data.tva }}%</span>
            </template>
          </Column>

          <Column header="Return Reason" class="tw-min-w-40">
            <template #body="slotProps">
              <Dropdown
                v-if="isEditing"
                v-model="slotProps.data.return_reason"
                :options="itemReturnReasons"
                optionLabel="label"
                optionValue="value"
                placeholder="Reason"
                class="tw-w-full"
                appendTo="body"
              />
              <Tag v-else :value="getReturnReasonLabel(slotProps.data.return_reason)" />
            </template>
          </Column>

          <Column header="Total" class="tw-w-32">
            <template #body="slotProps">
              <span class="tw-font-semibold">
                {{ formatCurrency(slotProps.data.total_amount || 0) }}
              </span>
            </template>
          </Column>

          <Column v-if="isEditing" header="" class="tw-w-16">
            <template #body="slotProps">
              <Button 
                icon="pi pi-trash" 
                size="small"
                text
                severity="danger"
                @click="removeItem(slotProps.index)"
              />
            </template>
          </Column>
        </DataTable>

        <div v-if="form.items.length === 0" class="tw-text-center tw-py-8 tw-text-gray-500">
          <i class="pi pi-inbox tw-text-4xl tw-mb-2"></i>
          <p>No items added yet</p>
        </div>
      </TabPanel>

      <!-- Summary Tab -->
      <TabPanel header="Summary">
        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
          <Card>
            <template #title>Financial Summary</template>
            <template #content>
              <div class="tw-space-y-3">
                <div class="tw-flex tw-justify-between tw-text-sm">
                  <span class="tw-text-gray-600">Total Items:</span>
                  <span class="tw-font-semibold">{{ form.items.length }}</span>
                </div>
                
                <div class="tw-flex tw-justify-between tw-text-sm">
                  <span class="tw-text-gray-600">Subtotal:</span>
                  <span>{{ formatCurrency(subtotal) }}</span>
                </div>
                
                <div class="tw-flex tw-justify-between tw-text-sm">
                  <span class="tw-text-gray-600">Tax:</span>
                  <span>{{ formatCurrency(totalTax) }}</span>
                </div>
                
                <Divider />
                
                <div class="tw-flex tw-justify-between tw-text-lg tw-font-bold">
                  <span>Total Amount:</span>
                  <span class="tw-text-green-600">{{ formatCurrency(totalAmount) }}</span>
                </div>
              </div>
            </template>
          </Card>

          <Card v-if="bonRetour.id">
            <template #title>Status Information</template>
            <template #content>
              <div class="tw-space-y-3">
                <div class="tw-flex tw-justify-between tw-text-sm">
                  <span class="tw-text-gray-600">Status:</span>
                  <Tag :value="getStatusLabel(bonRetour.status)" :severity="getStatusSeverity(bonRetour.status)" />
                </div>
                
                <div v-if="bonRetour.created_at" class="tw-flex tw-justify-between tw-text-sm">
                  <span class="tw-text-gray-600">Created:</span>
                  <span>{{ formatDate(bonRetour.created_at) }}</span>
                </div>
                
                <div v-if="bonRetour.creator" class="tw-flex tw-justify-between tw-text-sm">
                  <span class="tw-text-gray-600">Created By:</span>
                  <span>{{ bonRetour.creator.name }}</span>
                </div>
                
                <div v-if="bonRetour.approved_at" class="tw-flex tw-justify-between tw-text-sm">
                  <span class="tw-text-gray-600">Approved:</span>
                  <span>{{ formatDate(bonRetour.approved_at) }}</span>
                </div>
                
                <div v-if="bonRetour.approver" class="tw-flex tw-justify-between tw-text-sm">
                  <span class="tw-text-gray-600">Approved By:</span>
                  <span>{{ bonRetour.approver.name }}</span>
                </div>
              </div>
            </template>
          </Card>
        </div>
      </TabPanel>
    </TabView>

    <!-- Action Buttons -->
    <div class="tw-mt-6 tw-flex tw-justify-end tw-gap-2">
      <Button 
        v-if="mode === 'view' && isEditable"
        label="Edit"
        icon="pi pi-pencil"
        @click="enableEdit"
      />
      
      <Button 
        v-if="isEditing"
        label="Save"
        icon="pi pi-save"
        @click="saveReturn"
        :loading="saving"
      />
      
      <Button 
        v-if="isEditing && bonRetour.status === 'draft'"
        label="Submit for Approval"
        icon="pi pi-send"
        severity="success"
        @click="submitForApproval"
        :loading="submitting"
      />
      
      <Button 
        label="Close"
        icon="pi pi-times"
        severity="secondary"
        @click="$emit('cancelled')"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import axios from 'axios'

// PrimeVue Components
import Button from 'primevue/button'
import Card from 'primevue/card'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import Dropdown from 'primevue/dropdown'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Calendar from 'primevue/calendar'
import Checkbox from 'primevue/checkbox'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Divider from 'primevue/divider'
import Message from 'primevue/message'
import Tag from 'primevue/tag'

const props = defineProps({
  bonRetourId: {
    type: Number,
    default: null
  },
  mode: {
    type: String,
    default: 'view', // 'view', 'edit', 'create'
    validator: (value) => ['view', 'edit', 'create'].includes(value)
  }
})

const emit = defineEmits(['saved', 'cancelled'])

const toast = useToast()

// Data
const bonRetour = ref({})
const suppliers = ref([])
const products = ref([])
const bonEntrees = ref([])
const services = ref([])
const isEditing = ref(props.mode !== 'view')

// Form
const form = ref({
  bon_retour_code: '',
  return_type: null,
  fournisseur_id: null,
  bon_entree_id: null,
  return_date: new Date(),
  reference_invoice: '',
  service_id: null,
  reason: '',
  credit_note_received: false,
  credit_note_number: '',
  items: [],
  attachments: []
})

// Loading states
const loading = ref(false)
const saving = ref(false)
const submitting = ref(false)

// Options
const returnTypeOptions = [
  { label: 'Defective', value: 'defective' },
  { label: 'Expired', value: 'expired' },
  { label: 'Wrong Delivery', value: 'wrong_delivery' },
  { label: 'Overstock', value: 'overstock' },
  { label: 'Quality Issue', value: 'quality_issue' },
  { label: 'Other', value: 'other' }
]

const itemReturnReasons = [
  { label: 'Defective', value: 'defective' },
  { label: 'Expired', value: 'expired' },
  { label: 'Damaged', value: 'damaged' },
  { label: 'Wrong Item', value: 'wrong_item' },
  { label: 'Quality Issue', value: 'quality_issue' },
  { label: 'Other', value: 'other' }
]

// Computed
const isEditable = computed(() => !bonRetour.value.id || bonRetour.value.is_editable)

const subtotal = computed(() => {
  return form.value.items.reduce((sum, item) => {
    const price = item.unit_price * item.quantity_returned
    return sum + price
  }, 0)
})

const totalTax = computed(() => {
  return form.value.items.reduce((sum, item) => {
    const price = item.unit_price * item.quantity_returned
    const tax = price * (item.tva / 100)
    return sum + tax
  }, 0)
})

const totalAmount = computed(() => subtotal.value + totalTax.value)

// Methods
const loadBonRetour = async () => {
  if (!props.bonRetourId) return
  
  try {
    loading.value = true
    const response = await axios.get(`/api/bon-retours/${props.bonRetourId}`)
    
    if (response.data.status === 'success') {
      bonRetour.value = response.data.data
      
      // Populate form
      form.value = {
        bon_retour_code: bonRetour.value.bon_retour_code || '',
        return_type: bonRetour.value.return_type,
        fournisseur_id: bonRetour.value.fournisseur_id,
        bon_entree_id: bonRetour.value.bon_entree_id,
        return_date: new Date(bonRetour.value.return_date),
        reference_invoice: bonRetour.value.reference_invoice || '',
        service_id: bonRetour.value.service_id,
        reason: bonRetour.value.reason || '',
        credit_note_received: bonRetour.value.credit_note_received,
        credit_note_number: bonRetour.value.credit_note_number || '',
        items: bonRetour.value.items || [],
        attachments: bonRetour.value.attachments || []
      }
      
      // Load supplier's bon entrees if supplier is set
      if (form.value.fournisseur_id) {
        await loadBonEntrees(form.value.fournisseur_id)
      }
    }
  } catch (err) {
    console.error('Error loading bon retour:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load return note'
    })
  } finally {
    loading.value = false
  }
}

const loadSuppliers = async () => {
  try {
    const response = await axios.get('/api/fournisseurs')
    // if (response.data.status === 'success') {
      suppliers.value = response.data.data
    // }
  } catch (err) {
    console.error('Error loading suppliers:', err)
  }
}

const loadProducts = async () => {
  try {
    const response = await axios.get('/api/products')
    // if (response.data.status === 'success') {
      products.value = response.data.data
    // }
  } catch (err) {
    console.error('Error loading products:', err)
  }
}

const loadServices = async () => {
  try {
    const response = await axios.get('/api/services')
    // if (response.data.status === 'success') {
      services.value = response.data.data
    // }
  } catch (err) {
    console.error('Error loading services:', err)
  }
}

const loadBonEntrees = async (supplierId) => {
  try {
    const response = await axios.get('/api/bon-entrees', {
      params: { fournisseur_id: supplierId, status: 'validated' }
    })
    // if (response.data.status === 'success') {
      bonEntrees.value = response.data.data
    // }
  } catch (err) {
    console.error('Error loading bon entrees:', err)
  }
}

const onSupplierChange = async () => {
  if (form.value.fournisseur_id) {
    await loadBonEntrees(form.value.fournisseur_id)
  } else {
    bonEntrees.value = []
  }
}

const enableEdit = () => {
  isEditing.value = true
}

const addItem = () => {
  form.value.items.push({
    product_id: null,
    batch_number: '',
    serial_number: '',
    quantity_returned: 1,
    unit_price: 0,
    tva: 0,
    total_amount: 0,
    return_reason: null,
    remarks: ''
  })
}

const removeItem = (index) => {
  form.value.items.splice(index, 1)
}

const onProductChange = (productId, index) => {
  const product = products.value.find(p => p.id === productId)
  if (product) {
    form.value.items[index].unit_price = product.purchase_price || 0
    calculateItemTotal(index)
  }
}

const calculateItemTotal = (index) => {
  const item = form.value.items[index]
  const subtotal = item.quantity_returned * item.unit_price
  const tax = subtotal * (item.tva / 100)
  item.total_amount = subtotal + tax
}

const saveReturn = async () => {
  await save('draft')
}

const submitForApproval = async () => {
  await save('pending')
}

const save = async (status = 'draft') => {
  try {
    const isSubmitting = status === 'pending'
    if (isSubmitting) {
      submitting.value = true
    } else {
      saving.value = true
    }

    // Validate
    if (!form.value.return_type || !form.value.fournisseur_id || form.value.items.length === 0) {
      toast.add({
        severity: 'warn',
        summary: 'Validation Error',
        detail: 'Please fill all required fields and add at least one item'
      })
      return
    }

    const data = {
      ...form.value,
      status,
      total_amount: totalAmount.value
    }

    let response
    if (props.bonRetourId) {
      response = await axios.put(`/api/bon-retours/${props.bonRetourId}`, data)
    } else {
      response = await axios.post('/api/bon-retours', data)
    }

    if (response.data.status === 'success') {
      emit('saved')
    }
  } catch (err) {
    console.error('Error saving return:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Failed to save return'
    })
  } finally {
    saving.value = false
    submitting.value = false
  }
}

// Utility functions
const getProductName = (productId) => {
  const product = products.value.find(p => p.id === productId)
  return product ? product.name : 'Unknown Product'
}

const getReturnReasonLabel = (reason) => {
  const found = itemReturnReasons.find(r => r.value === reason)
  return found ? found.label : reason
}

const getStatusSeverity = (status) => {
  const severities = {
    draft: 'secondary',
    pending: 'warning',
    approved: 'info',
    completed: 'success',
    cancelled: 'danger'
  }
  return severities[status] || 'info'
}

const getStatusLabel = (status) => {
  const labels = {
    draft: 'Draft',
    pending: 'Pending',
    approved: 'Approved',
    completed: 'Completed',
    cancelled: 'Cancelled'
  }
  return labels[status] || status
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleString()
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount || 0)
}

// Lifecycle
onMounted(async () => {
  await Promise.all([
    loadSuppliers(),
    loadProducts(),
    loadServices(),
    loadBonRetour()
  ])
})

// Watch for mode changes
watch(() => props.mode, (newMode) => {
  isEditing.value = newMode !== 'view'
})
</script>

<style scoped>
:deep(.p-card) {
  @apply shadow-sm tw-border-0;
}

:deep(.p-card-title) {
  @apply text-base tw-font-semibold;
}

:deep(.p-tabview-nav) {
  @apply bg-gray-50;
}
</style>
