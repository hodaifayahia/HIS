<template>
  <div class="tw-p-6">
    <!-- Status Alert -->
    <Message v-if="bonReception.status && bonReception.status !== 'pending'" :severity="getStatusSeverity(bonReception.status)" :closable="false" class="tw-mb-4">
      <div class="tw-flex tw-items-center tw-justify-between">
        <span>
          This reception is currently <strong>{{ getStatusLabel(bonReception.status) }}</strong>.
          {{ bonReception.status !== 'pending' ? 'Editing is restricted.' : '' }}
        </span>
        <div v-if="bonReception.bon_retour_id" class="tw-flex tw-items-center tw-gap-2">
          <i class="pi pi-reply tw-text-orange-500"></i>
          <span>Return note created for surplus items</span>
          <Button 
            label="View Return" 
            icon="pi pi-external-link"
            size="small"
            text
            @click="viewReturn"
          />
        </div>
      </div>
    </Message>

    <!-- Surplus Alert -->
    <Message v-if="hasSurplusItems" severity="warn" :closable="false" class="tw-mb-4">
      <div class="tw-flex tw-items-center tw-justify-between">
        <div>
          <i class="pi pi-exclamation-triangle tw-mr-2"></i>
          <strong>Surplus Detected:</strong> {{ surplusItems.length }} items have surplus quantities totaling {{ totalSurplusQuantity }} units
        </div>
        <Button 
          v-if="bonReception.status === 'pending' && !bonReception.bon_retour_id"
          label="Handle Surplus" 
          icon="pi pi-cog"
          size="small"
          severity="warning"
          @click="openSurplusDialog"
        />
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
                Reception Code
              </label>
              <InputText
                v-model="form.bonReceptionCode"
                disabled
                class="tw-w-full"
              />
            </div>

            <div>
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                Purchase Order
              </label>
              <Dropdown
                v-model="form.bon_commend_id"
                :options="bonCommends"
                optionLabel="bonCommendCode"
                optionValue="id"
                placeholder="Select purchase order"
                class="tw-w-full"
                :disabled="!isEditing || mode === 'edit'"
                @change="onBonCommendChange"
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
                :disabled="!isEditing || form.bon_commend_id"
              />
            </div>

            <div>
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                Receipt Date <span v-if="isEditing" class="tw-text-red-500">*</span>
              </label>
              <Calendar
                v-model="form.date_reception"
                dateFormat="yy-mm-dd"
                showIcon
                class="tw-w-full"
                :disabled="!isEditing"
              />
            </div>
          </div>

          <div class="tw-space-y-4">
            <div>
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                Received By <span v-if="isEditing" class="tw-text-red-500">*</span>
              </label>
              <Dropdown
                v-model="form.received_by"
                :options="users"
                optionLabel="name"
                optionValue="id"
                placeholder="Select user"
                class="tw-w-full"
                filter
                :disabled="!isEditing"
              />
            </div>

            <div>
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                Delivery Note Number
              </label>
              <InputText
                v-model="form.bon_livraison_numero"
                placeholder="Delivery note number"
                class="tw-w-full"
                :disabled="!isEditing"
              />
            </div>

            <div>
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                Invoice Number
              </label>
              <InputText
                v-model="form.facture_numero"
                placeholder="Invoice number"
                class="tw-w-full"
                :disabled="!isEditing"
              />
            </div>

            <div>
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                Number of Packages
              </label>
              <InputNumber
                v-model="form.nombre_colis"
                :min="0"
                class="tw-w-full"
                :disabled="!isEditing"
              />
            </div>
          </div>

          <div class="md:tw-col-span-2">
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              Observations
            </label>
            <Textarea
              v-model="form.observation"
              rows="3"
              placeholder="Any observations..."
              class="tw-w-full"
              :disabled="!isEditing"
            />
          </div>
        </div>
      </TabPanel>

      <!-- Items Tab -->
      <TabPanel header="Items">
        <div class="tw-mb-4 tw-flex tw-justify-between tw-items-center">
          <div class="tw-text-lg tw-font-semibold">Reception Items</div>
          <div class="tw-flex tw-gap-2">
            <Button 
              v-if="isEditing && !form.bon_commend_id"
              icon="pi pi-plus" 
              label="Add Item"
              size="small"
              @click="addItem"
              :disabled="!form.fournisseur_id"
            />
            <Button 
              v-if="form.bon_commend_id && isEditing"
              icon="pi pi-sync" 
              label="Sync with Order"
              size="small"
              severity="info"
              @click="syncWithBonCommend"
            />
          </div>
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
                v-if="isEditing && !form.bon_commend_id"
                v-model="slotProps.data.product_id"
                :options="products"
                optionLabel="name"
                optionValue="id"
                placeholder="Select product"
                class="tw-w-full"
                filter
                @change="onProductChange($event, slotProps.index)"
              />
              <div v-else>
                <div class="tw-font-medium">{{ getProductName(slotProps.data.product_id) }}</div>
                <div class="tw-text-xs tw-text-gray-500">{{ getProductCode(slotProps.data.product_id) }}</div>
              </div>
            </template>
          </Column>

          <Column header="Ordered" class="tw-w-24">
            <template #body="slotProps">
              <span>{{ slotProps.data.quantity_ordered || '-' }}</span>
            </template>
          </Column>

          <Column header="Received" class="tw-w-24">
            <template #body="slotProps">
              <InputNumber
                v-if="isEditing"
                v-model="slotProps.data.quantity_received"
                :min="0"
                class="tw-w-full"
                @input="calculateVariances(slotProps.index)"
              />
              <span v-else>{{ slotProps.data.quantity_received }}</span>
            </template>
          </Column>

          <Column header="Variance" class="tw-w-32">
            <template #body="slotProps">
              <div v-if="slotProps.data.quantity_surplus > 0">
                <Tag :value="`+${slotProps.data.quantity_surplus}`" severity="warning" />
              </div>
              <div v-else-if="slotProps.data.quantity_shortage > 0">
                <Tag :value="`-${slotProps.data.quantity_shortage}`" severity="danger" />
              </div>
              <div v-else>
                <Tag value="OK" severity="success" />
              </div>
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
              />
              <span v-else>{{ formatCurrency(slotProps.data.unit_price) }}</span>
            </template>
          </Column>

          <Column header="Status" class="tw-w-32">
            <template #body="slotProps">
              <Tag :value="getItemStatusLabel(slotProps.data.status)" :severity="getItemStatusSeverity(slotProps.data.status)" />
            </template>
          </Column>

          <Column header="Notes" class="tw-min-w-40">
            <template #body="slotProps">
              <InputText
                v-if="isEditing"
                v-model="slotProps.data.notes"
                placeholder="Notes"
                class="tw-w-full"
              />
              <span v-else>{{ slotProps.data.notes || '-' }}</span>
            </template>
          </Column>

          <Column v-if="isEditing && !form.bon_commend_id" header="" class="tw-w-16">
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
        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-6">
          <Card>
            <template #title>Reception Summary</template>
            <template #content>
              <div class="tw-space-y-3">
                <div class="tw-flex tw-justify-between tw-text-sm">
                  <span class="tw-text-gray-600">Total Items:</span>
                  <span class="tw-font-semibold">{{ form.items.length }}</span>
                </div>
                
                <div class="tw-flex tw-justify-between tw-text-sm">
                  <span class="tw-text-gray-600">Total Ordered:</span>
                  <span>{{ totalOrdered }}</span>
                </div>
                
                <div class="tw-flex tw-justify-between tw-text-sm">
                  <span class="tw-text-gray-600">Total Received:</span>
                  <span class="tw-font-semibold">{{ totalReceived }}</span>
                </div>
                
                <Divider />
                
                <div class="tw-flex tw-justify-between tw-text-sm">
                  <span class="tw-text-gray-600">Total Surplus:</span>
                  <span class="tw-text-orange-600 tw-font-semibold">
                    {{ totalSurplus > 0 ? `+${totalSurplus}` : '0' }}
                  </span>
                </div>
                
                <div class="tw-flex tw-justify-between tw-text-sm">
                  <span class="tw-text-gray-600">Total Shortage:</span>
                  <span class="tw-text-red-600 tw-font-semibold">
                    {{ totalShortage > 0 ? `-${totalShortage}` : '0' }}
                  </span>
                </div>
              </div>
            </template>
          </Card>

          <Card>
            <template #title>Financial Summary</template>
            <template #content>
              <div class="tw-space-y-3">
                <div class="tw-flex tw-justify-between tw-text-sm">
                  <span class="tw-text-gray-600">Subtotal:</span>
                  <span>{{ formatCurrency(subtotal) }}</span>
                </div>
                
                <div class="tw-flex tw-justify-between tw-text-sm">
                  <span class="tw-text-gray-600">Tax:</span>
                  <span>{{ formatCurrency(0) }}</span>
                </div>
                
                <Divider />
                
                <div class="tw-flex tw-justify-between tw-text-lg tw-font-bold">
                  <span>Total:</span>
                  <span class="tw-text-green-600">{{ formatCurrency(subtotal) }}</span>
                </div>
              </div>
            </template>
          </Card>

          <Card v-if="bonReception.id">
            <template #title>Status Information</template>
            <template #content>
              <div class="tw-space-y-3">
                <div class="tw-flex tw-justify-between tw-text-sm">
                  <span class="tw-text-gray-600">Status:</span>
                  <Tag :value="getStatusLabel(bonReception.status)" :severity="getStatusSeverity(bonReception.status)" />
                </div>
                
                <div v-if="bonReception.created_at" class="tw-flex tw-justify-between tw-text-sm">
                  <span class="tw-text-gray-600">Created:</span>
                  <span>{{ formatDate(bonReception.created_at) }}</span>
                </div>
                
                <div v-if="bonReception.confirmed_at" class="tw-flex tw-justify-between tw-text-sm">
                  <span class="tw-text-gray-600">Confirmed:</span>
                  <span>{{ formatDate(bonReception.confirmed_at) }}</span>
                </div>
                
                <div v-if="bonReception.bon_retour_id" class="tw-flex tw-justify-between tw-text-sm">
                  <span class="tw-text-gray-600">Return Note:</span>
                  <Button 
                    label="View" 
                    size="small"
                    text
                    @click="viewReturn"
                  />
                </div>
              </div>
            </template>
          </Card>
        </div>

        <!-- Surplus Items Detail -->
        <div v-if="surplusItems.length > 0" class="tw-mt-6">
          <Card>
            <template #title>
              <div class="tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-exclamation-triangle tw-text-orange-500"></i>
                Surplus Items Detail
              </div>
            </template>
            <template #content>
              <DataTable :value="surplusItems" class="tw-text-sm">
                <Column field="product.name" header="Product" />
                <Column field="quantity_ordered" header="Ordered" />
                <Column field="quantity_received" header="Received" />
                <Column field="quantity_surplus" header="Surplus">
                  <template #body="slotProps">
                    <Tag :value="`+${slotProps.data.quantity_surplus}`" severity="warning" />
                  </template>
                </Column>
                <Column header="Value">
                  <template #body="slotProps">
                    {{ formatCurrency(slotProps.data.quantity_surplus * slotProps.data.unit_price) }}
                  </template>
                </Column>
              </DataTable>
            </template>
          </Card>
        </div>
      </TabPanel>
    </TabView>

    <!-- Action Buttons -->
    <div class="tw-mt-6 tw-flex tw-justify-between">
      <div>
        <Button 
          v-if="bonReception.status === 'pending' && hasSurplusItems && !bonReception.bon_retour_id"
          label="Create Return for Surplus"
          icon="pi pi-reply"
          severity="warning"
          @click="createReturnForSurplus"
          :loading="creatingReturn"
        />
      </div>

      <div class="tw-flex tw-gap-2">
        <Button 
          v-if="mode === 'view' && bonReception.status === 'pending'"
          label="Edit"
          icon="pi pi-pencil"
          @click="enableEdit"
        />
        
        <Button 
          v-if="isEditing"
          label="Save"
          icon="pi pi-save"
          @click="saveReception"
          :loading="saving"
        />
        
        <Button 
          v-if="bonReception.status === 'pending'"
          label="Confirm Reception"
          icon="pi pi-check-circle"
          severity="success"
          @click="confirmReception"
          :loading="confirming"
        />
        
        <Button 
          label="Close"
          icon="pi pi-times"
          severity="secondary"
          @click="$emit('cancelled')"
        />
      </div>
    </div>

    <!-- Handle Surplus Dialog -->
    <BonReceptionConfirmDialog
      v-model="showSurplusDialog"
      :bon-reception-id="bonReception.id"
      @confirmed="onSurplusHandled"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
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
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Divider from 'primevue/divider'
import Message from 'primevue/message'
import Tag from 'primevue/tag'

// Custom Components
import BonReceptionConfirmDialog from './BonReceptionConfirmDialog.vue'

const props = defineProps({
  bonReceptionId: {
    type: Number,
    required: true
  },
  mode: {
    type: String,
    default: 'view',
    validator: (value) => ['view', 'edit', 'create'].includes(value)
  }
})

const emit = defineEmits(['saved', 'cancelled'])

const router = useRouter()
const toast = useToast()

// Data
const bonReception = ref({})
const suppliers = ref([])
const products = ref([])
const bonCommends = ref([])
const users = ref([])
const isEditing = ref(props.mode !== 'view')
const showSurplusDialog = ref(false)

// Form
const form = ref({
  bonReceptionCode: '',
  bon_commend_id: null,
  fournisseur_id: null,
  date_reception: new Date(),
  received_by: null,
  bon_livraison_numero: '',
  facture_numero: '',
  nombre_colis: 0,
  observation: '',
  items: []
})

// Loading states
const loading = ref(false)
const saving = ref(false)
const confirming = ref(false)
const creatingReturn = ref(false)

// Computed
const hasSurplusItems = computed(() => surplusItems.value.length > 0)

const surplusItems = computed(() => {
  return form.value.items.filter(item => item.quantity_surplus > 0)
})

const totalSurplusQuantity = computed(() => {
  return surplusItems.value.reduce((sum, item) => sum + item.quantity_surplus, 0)
})

const totalOrdered = computed(() => {
  return form.value.items.reduce((sum, item) => sum + (item.quantity_ordered || 0), 0)
})

const totalReceived = computed(() => {
  return form.value.items.reduce((sum, item) => sum + (item.quantity_received || 0), 0)
})

const totalSurplus = computed(() => {
  return form.value.items.reduce((sum, item) => sum + (item.quantity_surplus || 0), 0)
})

const totalShortage = computed(() => {
  return form.value.items.reduce((sum, item) => sum + (item.quantity_shortage || 0), 0)
})

const subtotal = computed(() => {
  return form.value.items.reduce((sum, item) => {
    return sum + ((item.quantity_received || 0) * (item.unit_price || 0))
  }, 0)
})

// Methods
const loadBonReception = async () => {
  try {
    loading.value = true
    const response = await axios.get(`/api/bon-receptions/${props.bonReceptionId}`)
    
    if (response.data.status === 'success') {
      bonReception.value = response.data.data
      
      // Populate form
      form.value = {
        bonReceptionCode: bonReception.value.bonReceptionCode || '',
        bon_commend_id: bonReception.value.bon_commend_id,
        fournisseur_id: bonReception.value.fournisseur_id,
        date_reception: new Date(bonReception.value.date_reception),
        received_by: bonReception.value.received_by,
        bon_livraison_numero: bonReception.value.bon_livraison_numero || '',
        facture_numero: bonReception.value.facture_numero || '',
        nombre_colis: bonReception.value.nombre_colis || 0,
        observation: bonReception.value.observation || '',
        items: bonReception.value.items || []
      }
    }
  } catch (err) {
    console.error('Error loading bon reception:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load reception'
    })
  } finally {
    loading.value = false
  }
}

// Load reference data
const loadSuppliers = async () => {
  try {
    const response = await axios.get('/api/fournisseurs')
    if (response.data.status === 'success') {
      suppliers.value = response.data.data
    }
  } catch (err) {
    console.error('Error loading suppliers:', err)
  }
}

const loadProducts = async () => {
  try {
    const response = await axios.get('/api/products')
    if (response.data.status === 'success') {
      products.value = response.data.data
    }
  } catch (err) {
    console.error('Error loading products:', err)
  }
}

const loadBonCommends = async () => {
  try {
    const response = await axios.get('/api/bon-receptions/meta/bon-commends')
    if (response.data.status === 'success') {
      bonCommends.value = response.data.data
    }
  } catch (err) {
    console.error('Error loading bon commends:', err)
  }
}

const loadUsers = async () => {
  try {
    const response = await axios.get('/api/users')
    if (response.data.status === 'success') {
      users.value = response.data.data
    }
  } catch (err) {
    console.error('Error loading users:', err)
  }
}

const enableEdit = () => {
  isEditing.value = true
}

const addItem = () => {
  form.value.items.push({
    product_id: null,
    quantity_ordered: 0,
    quantity_received: 0,
    quantity_surplus: 0,
    quantity_shortage: 0,
    unit_price: 0,
    status: 'pending',
    notes: ''
  })
}

const removeItem = (index) => {
  form.value.items.splice(index, 1)
}

const onProductChange = (productId, index) => {
  const product = products.value.find(p => p.id === productId)
  if (product) {
    form.value.items[index].unit_price = product.purchase_price || 0
  }
}

const onBonCommendChange = async () => {
  if (!form.value.bon_commend_id) return
  
  const bonCommend = bonCommends.value.find(bc => bc.id === form.value.bon_commend_id)
  if (bonCommend) {
    form.value.fournisseur_id = bonCommend.fournisseur_id
    await syncWithBonCommend()
  }
}

const syncWithBonCommend = async () => {
  if (!form.value.bon_commend_id) return
  
  try {
    const response = await axios.get(`/api/bon-commends/${form.value.bon_commend_id}`)
    
    if (response.data.status === 'success') {
      const bonCommend = response.data.data
      
      // Map bon commend items to reception items
      form.value.items = bonCommend.items.map(item => ({
        bon_commend_item_id: item.id,
        product_id: item.product_id,
        quantity_ordered: item.quantity_desired,
        quantity_received: item.quantity_desired, // Default to ordered
        quantity_surplus: 0,
        quantity_shortage: 0,
        unit: item.unit,
        unit_price: item.price,
        status: 'pending',
        notes: item.notes || ''
      }))
    }
  } catch (err) {
    console.error('Error syncing with bon commend:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to sync with purchase order'
    })
  }
}

const calculateVariances = (index) => {
  const item = form.value.items[index]
  const ordered = item.quantity_ordered || 0
  const received = item.quantity_received || 0
  
  item.quantity_surplus = Math.max(0, received - ordered)
  item.quantity_shortage = Math.max(0, ordered - received)
  
  // Update status
  if (received === ordered && ordered > 0) {
    item.status = 'received'
  } else if (received > 0 && received < ordered) {
    item.status = 'partial'
  } else if (item.quantity_surplus > 0) {
    item.status = 'excess'
  } else if (item.quantity_shortage > 0) {
    item.status = 'missing'
  } else {
    item.status = 'pending'
  }
}

const saveReception = async () => {
  try {
    saving.value = true
    
    const data = { ...form.value }
    
    const response = await axios.put(`/api/bon-receptions/${props.bonReceptionId}`, data)
    
    if (response.data.status === 'success') {
      emit('saved')
    }
  } catch (err) {
    console.error('Error saving reception:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Failed to save reception'
    })
  } finally {
    saving.value = false
  }
}

const confirmReception = () => {
  showSurplusDialog.value = true
}

const onSurplusHandled = (data) => {
  showSurplusDialog.value = false
  
  if (data.bonRetour) {
    bonReception.value.bon_retour_id = data.bonRetour.id
    toast.add({
      severity: 'success',
      summary: 'Reception Confirmed',
      detail: `Return note ${data.bonRetour.bon_retour_code} created for surplus items`,
      life: 5000
    })
  } else {
    toast.add({
      severity: 'success',
      summary: 'Reception Confirmed',
      detail: 'Reception confirmed successfully'
    })
  }
  
  emit('saved')
}

const createReturnForSurplus = async () => {
  try {
    creatingReturn.value = true
    
    const response = await axios.post(`/api/bon-receptions/${bonReception.value.id}/confirm`, {
      surplus_action: 'return',
      return_notes: 'Manual return creation for surplus items'
    })
    
    if (response.data.status === 'success' && response.data.data.bonRetour) {
      bonReception.value.bon_retour_id = response.data.data.bonRetour.id
      
      toast.add({
        severity: 'success',
        summary: 'Return Created',
        detail: `Return note ${response.data.data.bonRetour.bon_retour_code} created successfully`,
        life: 5000
      })
      
      emit('saved')
    }
  } catch (err) {
    console.error('Error creating return:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Failed to create return'
    })
  } finally {
    creatingReturn.value = false
  }
}

const viewReturn = () => {
  if (bonReception.value.bon_retour_id) {
    router.push(`/purchasing/bon-retours/${bonReception.value.bon_retour_id}`)
  }
}

const openSurplusDialog = () => {
  showSurplusDialog.value = true
}

// Utility functions
const getProductName = (productId) => {
  const product = products.value.find(p => p.id === productId)
  return product ? product.name : 'Unknown Product'
}

const getProductCode = (productId) => {
  const product = products.value.find(p => p.id === productId)
  return product ? product.code : ''
}

const getItemStatusLabel = (status) => {
  const labels = {
    pending: 'Pending',
    received: 'Received',
    partial: 'Partial',
    excess: 'Excess',
    missing: 'Missing'
  }
  return labels[status] || status
}

const getItemStatusSeverity = (status) => {
  const severities = {
    pending: 'secondary',
    received: 'success',
    partial: 'warning',
    excess: 'warning',
    missing: 'danger'
  }
  return severities[status] || 'info'
}

const getStatusSeverity = (status) => {
  const severities = {
    pending: 'warning',
    completed: 'success',
    canceled: 'secondary',
    rejected: 'danger'
  }
  return severities[status] || 'info'
}

const getStatusLabel = (status) => {
  const labels = {
    pending: 'Pending',
    completed: 'Completed',
    canceled: 'Canceled',
    rejected: 'Rejected'
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
    loadBonCommends(),
    loadUsers(),
    loadBonReception()
  ])
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
