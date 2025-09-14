<template>
  <Dialog
    :visible="visible"
    modal
    :header="`Select Inventory for ${selectedItem?.product?.name || 'Product'}`"
    :style="{width: '90rem'}"
    class="product-selection-dialog"
    @hide="emit('update:visible', false)"
  >
    <div class="tw-space-y-6">
      <!-- Product Info Header -->
      <div class="tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50 tw-p-4 tw-rounded-xl tw-border tw-border-blue-200">
        <div class="tw-flex tw-items-center tw-justify-between">
          <div class="tw-flex tw-items-center tw-space-x-4">
            <div class="tw-w-12 tw-h-12 tw-bg-blue-500 tw-rounded-xl tw-flex tw-items-center tw-justify-center">
              <i class="pi pi-box tw-text-white tw-text-lg"></i>
            </div>
            <div>
              <h3 class="tw-text-lg tw-font-bold tw-text-gray-900">{{ selectedItem?.product?.name }}</h3>
              <p class="tw-text-sm tw-text-gray-600">Code: {{ selectedItem?.product?.code }} • Category: {{ selectedItem?.product?.category }}</p>
            </div>
          </div>
          <div class="tw-text-right">
            <p class="tw-text-sm tw-font-medium tw-text-gray-600">Requested Quantity</p>
            <div class="tw-space-y-1">
              <p class="tw-text-xl tw-font-bold tw-text-blue-600">
                {{ getCalculatedRequestedQuantity() }} {{ getUnitDisplay() }}
              </p>
              <div v-if="hasBoiteDe(selectedItem?.product)" class="tw-flex tw-flex-col tw-gap-1">
                <p class="tw-text-sm tw-text-gray-600">
                  Select by:
                </p>
                <div class="tw-flex tw-gap-2">
                  <button 
                    @click="applySuggestion('units')"
                    class="tw-px-2 tw-py-1 tw-bg-blue-50 tw-text-blue-600 tw-rounded-md tw-text-xs hover:tw-bg-blue-100"
                  >
                    {{ getCalculatedRequestedQuantity() }} {{ getUnitDisplay() }}
                  </button>
                  <button 
                    @click="applySuggestion('boxes')"
                    class="tw-px-2 tw-py-1 tw-bg-blue-50 tw-text-blue-600 tw-rounded-md tw-text-xs hover:tw-bg-blue-100"
                  >
                    {{ getRequestedBoxes() }} boxes ({{ selectedItem?.product?.boite_de }} {{ getUnitDisplay() }}/box)
                  </button>
                </div>
                <p class="tw-text-xs tw-text-gray-500">
                  {{ selectedItem?.quantity_by_box ? 
                    `Original request: ${selectedItem?.requested_quantity} boxes` : 
                    `Equal to ${getRequestedBoxes()} boxes` }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Search and Filter -->
      <div class="tw-flex tw-flex-wrap tw-gap-4 tw-items-center tw-justify-between">
        <div class="tw-flex tw-gap-4 tw-items-center">
          <!-- Barcode Search -->
          <div class="tw-relative">
            <InputText
              v-model="barcodeSearch"
              @keyup.enter="searchByBarcode"
              placeholder="Scan barcode..."
              class="tw-pl-10 tw-pr-4 tw-py-2 tw-border tw-border-gray-300 tw-rounded-lg tw-w-64"
            />
            <i class="pi pi-qrcode tw-absolute tw-left-3 tw-top-1/2 tw-transform -tw-translate-y-1/2 tw-text-gray-400"></i>
          </div>

          <!-- Batch Filter -->
          <div class="tw-w-48">
            <Dropdown
              v-model="batchFilter"
              :options="batchOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Filter by batch"
              class="tw-w-full"
              showClear
            />
          </div>

          <!-- Expiry Filter -->
          <div class="tw-w-48">
            <Dropdown
              v-model="expiryFilter"
              :options="expiryOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Filter by expiry"
              class="tw-w-full"
              showClear
            />
          </div>
        </div>

        <div class="tw-flex tw-items-center tw-gap-2">
          <Button
            @click="clearFilters"
            icon="pi pi-filter-slash"
            severity="secondary"
            text
            size="small"
          >
            Clear Filters
          </Button>
        </div>
      </div>

      <!-- Inventory Table -->
      <div class="tw-bg-white tw-rounded-xl tw-border tw-border-gray-200 tw-overflow-hidden tw-shadow-sm">
        <div class="tw-overflow-x-auto">
          <table class="tw-w-full">
            <thead class="tw-bg-gradient-to-r tw-from-gray-50 tw-to-gray-100">
              <tr>
                <th class="tw-px-6 tw-py-4 tw-text-left tw-text-xs tw-font-semibold tw-text-gray-600 tw-uppercase tw-tracking-wider">
                  <input
                    type="checkbox"
                    v-model="selectAll"
                    @change="toggleSelectAll"
                    class="tw-rounded tw-border-gray-300"
                  />
                </th>
                <th class="tw-px-6 tw-py-4 tw-text-left tw-text-xs tw-font-semibold tw-text-gray-600 tw-uppercase tw-tracking-wider">
                  Barcode
                </th>
                <th class="tw-px-6 tw-py-4 tw-text-left tw-text-xs tw-font-semibold tw-text-gray-600 tw-uppercase tw-tracking-wider">
                  Batch Number
                </th>
                <th class="tw-px-6 tw-py-4 tw-text-left tw-text-xs tw-font-semibold tw-text-gray-600 tw-uppercase tw-tracking-wider">
                  Expiry Date
                </th>
                <th class="tw-px-6 tw-py-4 tw-text-left tw-text-xs tw-font-semibold tw-text-gray-600 tw-uppercase tw-tracking-wider">
                  Available Qty
                </th>
                <th class="tw-px-6 tw-py-4 tw-text-left tw-text-xs tw-font-semibold tw-text-gray-600 tw-uppercase tw-tracking-wider">
                  Location
                </th>
                <th class="tw-px-6 tw-py-4 tw-text-left tw-text-xs tw-font-semibold tw-text-gray-600 tw-uppercase tw-tracking-wider">
                  Select Qty
                </th>
              </tr>
            </thead>
            <tbody class="tw-divide-y tw-divide-gray-200">
              <tr
                v-for="inventory in filteredInventory"
                :key="inventory.id"
                :class="[
                  'tw-hover:bg-blue-50/30 tw-transition-colors tw-duration-200',
                  selectedItems[inventory.id] ? 'tw-bg-blue-50' : ''
                ]"
              >
                <!-- Checkbox -->
                <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap">
                  <input
                    type="checkbox"
                    :checked="!!selectedItems[inventory.id]"
                    @change="toggleItemSelection(inventory)"
                    class="tw-rounded tw-border-gray-300"
                  />
                </td>

                <!-- Barcode -->
                <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap">
                  <div class="tw-flex tw-items-center tw-gap-2">
                    <span class="tw-font-mono tw-text-sm tw-text-gray-900">{{ inventory.barcode }}</span>
                    <Button
                      @click="copyBarcode(inventory.barcode)"
                      icon="pi pi-copy"
                      severity="secondary"
                      text
                      size="small"
                      v-tooltip="'Copy barcode'"
                    />
                  </div>
                </td>

                <!-- Batch Number -->
                <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap">
                  <span class="tw-text-sm tw-font-medium tw-text-gray-900">
                    {{ inventory.batch_number || 'N/A' }}
                  </span>
                </td>

                <!-- Expiry Date -->
                <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap">
                  <div class="tw-flex tw-flex-col">
                    <span class="tw-text-sm tw-font-medium tw-text-gray-900">
                      {{ formatDate(inventory.expiry_date) }}
                    </span>
                    <span
                      :class="getExpiryStatusClass(inventory.expiry_date)"
                      class="tw-text-xs tw-font-medium tw-px-2 tw-py-1 tw-rounded-full tw-inline-block tw-mt-1"
                    >
                      {{ getExpiryStatus(inventory.expiry_date) }}
                    </span>
                  </div>
                </td>

                <!-- Available Quantity -->
                <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap">
                  <div class="tw-flex tw-flex-col">
                    <span class="tw-text-sm tw-font-medium tw-text-gray-900">
                      {{ inventory.total_units || inventory.quantity }} {{ inventory.unit || 'units' }}
                    </span>
                    <span class="tw-text-xs tw-text-gray-500">
                      {{ getBoxesDisplay(inventory.total_units || inventory.quantity, inventory.product?.boite_de || selectedItem?.product?.boite_de) }}
                    </span>
                  </div>
                </td>

                <!-- Location -->
                <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap">
                  <div class="tw-flex tw-items-center tw-gap-2">
                    <i class="pi pi-map-marker tw-text-gray-400"></i>
                    <span class="tw-text-sm tw-text-gray-600">
                      {{ inventory.stockage?.name || 'Unknown' }}
                    </span>
                  </div>
                </td>

                <!-- Select Quantity -->
                <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap">
                  <div v-if="selectedItems[inventory.id]" class="tw-flex tw-flex-col tw-gap-2">
                    <!-- By Box Checkbox -->
                    <div v-if="hasBoiteDe(inventory)" class="tw-flex tw-items-center tw-gap-2">
                      <div class="tw-flex tw-items-center">
                        <input
                          type="checkbox"
                          :id="'byBox-' + inventory.id"
                          v-model="selectedItems[inventory.id].byBox"
                          class="tw-rounded tw-border-gray-300"
                          @change="updateQuantityMode(inventory.id)"
                        />
                        <label :for="'byBox-' + inventory.id" class="tw-ml-2 tw-text-xs tw-text-gray-600">By Box</label>
                      </div>
                    </div>

                    <!-- Quantity Input -->
                    <div class="tw-flex tw-items-center tw-gap-2">
                      <InputNumber
                        v-model="selectedItems[inventory.id].quantity"
                        :min="0.01"
                        :max="getMaxQuantity(inventory)"
                        :step="selectedItems[inventory.id].byBox ? 1 : 0.01"
                        mode="decimal"
                        :minFractionDigits="selectedItems[inventory.id].byBox ? 0 : 2"
                        :maxFractionDigits="selectedItems[inventory.id].byBox ? 0 : 2"
                        class="tw-w-24"
                        size="small"
                        @input="() => updateQuantityInput(inventory.id)"
                      />
                      <span class="tw-text-xs tw-text-gray-500">{{ selectedItems[inventory.id].byBox ? 'boxes' : (inventory.unit || 'units') }}</span>
                    </div>

                    <!-- Quantity Display -->
                    <div class="tw-text-xs tw-text-gray-500">
                      {{ getQuantityDisplay(inventory.id, selectedItems[inventory.id]) }}
                    </div>
                  </div>
                  <span v-else class="tw-text-sm tw-text-gray-400">-</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Empty State -->
        <div v-if="filteredInventory.length === 0" class="tw-text-center tw-py-16">
          <div class="tw-w-16 tw-h-16 tw-bg-gray-100 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-4">
            <i class="pi pi-box tw-text-4xl tw-text-gray-400"></i>
          </div>
          <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-2">No inventory found</h3>
          <p class="tw-text-gray-600">No inventory items match your current filters.</p>
        </div>
      </div>

      <!-- Selection Summary -->
      <div v-if="Object.keys(selectedItems).length > 0" class="tw-bg-gradient-to-r tw-from-green-50 tw-to-emerald-50 tw-p-6 tw-rounded-xl tw-border tw-border-green-200">
        <div class="tw-space-y-4">
          <!-- Header -->
          <div class="tw-flex tw-items-center tw-justify-between tw-border-b tw-border-green-200 tw-pb-3">
            <h4 class="tw-text-lg tw-font-semibold tw-text-green-800 tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-check-circle"></i>
              Selection Summary
            </h4>
            <p :class="[
              'tw-px-3 tw-py-1 tw-rounded-full tw-text-sm tw-font-medium',
              totalSelectedQuantity >= getCalculatedRequestedQuantity() 
                ? 'tw-bg-green-100 tw-text-green-700' 
                : 'tw-bg-orange-100 tw-text-orange-700'
            ]">
              {{ totalSelectedQuantity >= getCalculatedRequestedQuantity() ? '✓ Sufficient' : '⚠ Insufficient' }}
            </p>
          </div>

          <!-- Summary Grid -->
          <div class="tw-grid tw-grid-cols-2 md:tw-grid-cols-3 tw-gap-4">
            <!-- Selected Items Count -->
            <div class="tw-bg-white tw-rounded-lg tw-p-3 tw-border tw-border-green-100">
              <p class="tw-text-sm tw-text-gray-600 tw-mb-1">Selected Items</p>
              <p class="tw-text-lg tw-font-bold tw-text-gray-900">
                {{ Object.keys(selectedItems).length }}
              </p>
            </div>

            <!-- Total Selected -->
            <div class="tw-bg-white tw-rounded-lg tw-p-3 tw-border tw-border-green-100">
              <p class="tw-text-sm tw-text-gray-600 tw-mb-1">Total Selected</p>
              <p class="tw-text-lg tw-font-bold tw-text-gray-900">
                {{ totalSelectedQuantity.toFixed(2) }} {{ getUnitDisplay() }}
              </p>
              <p v-if="hasBoiteDe(selectedItem?.product)" class="tw-text-xs tw-text-gray-500">
                ≈ {{ Math.floor(totalSelectedQuantity / selectedItem?.product?.boite_de) }} boxes
              </p>
            </div>

            <!-- Requested -->
            <div class="tw-bg-white tw-rounded-lg tw-p-3 tw-border tw-border-green-100">
              <p class="tw-text-sm tw-text-gray-600 tw-mb-1">Requested</p>
              <p class="tw-text-lg tw-font-bold tw-text-gray-900">
                {{ getCalculatedRequestedQuantity() }} {{ getUnitDisplay() }}
              </p>
              <p v-if="hasBoiteDe(selectedItem?.product)" class="tw-text-xs tw-text-gray-500">
                ≈ {{ getRequestedBoxes() }} boxes
              </p>
            </div>
          </div>

          <!-- Progress Bar -->
          <div class="tw-space-y-2">
            <div class="tw-h-2 tw-bg-green-100 tw-rounded-full tw-overflow-hidden">
              <div 
                class="tw-h-full tw-transition-all tw-duration-500"
                :class="totalSelectedQuantity >= getCalculatedRequestedQuantity() ? 'tw-bg-green-500' : 'tw-bg-orange-500'"
                :style="{ width: `${Math.min((totalSelectedQuantity / getCalculatedRequestedQuantity()) * 100, 100)}%` }"
              ></div>
            </div>
            <p class="tw-text-xs tw-text-gray-600 tw-text-right">
              {{ Math.round((totalSelectedQuantity / getCalculatedRequestedQuantity()) * 100) }}% of requested quantity
            </p>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="tw-flex tw-justify-end tw-space-x-3 tw-pt-4 tw-border-t tw-border-gray-200">
        <Button
          @click="cancelSelection"
          label="Cancel"
          icon="pi pi-times"
          severity="secondary"
          outlined
        />
        <Button
          @click="saveSelection"
          label="Save Selection"
          icon="pi pi-check"
          :class="Object.keys(selectedItems).length > 0 ? 'tw-bg-green-600' : 'tw-bg-gray-400'"
          :disabled="Object.keys(selectedItems).length === 0"
          :loading="saving"
        />
      </div>
    </div>
  </Dialog>
</template>

<script>
import { ref, computed, watch, onMounted } from 'vue'
import axios from 'axios'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Dropdown from 'primevue/dropdown'

export default {
  name: 'ProductSelectionDialog',
  components: {
    Dialog,
    Button,
    InputText,
    InputNumber,
    Dropdown
  },
  props: {
    visible: {
      type: Boolean,
      default: false
    },
    selectedItem: {
      type: Object,
      default: null
    },
    movementId: {
      type: [String, Number],
      required: true
    }
  },
  emits: ['update:visible', 'selection-saved'],
  setup(props, { emit }) {
    // Reactive data
    const inventory = ref([])
    const selectedItems = ref({})
    const loading = ref(false)
    const saving = ref(false)
    const barcodeSearch = ref('')
    const batchFilter = ref(null)
    const expiryFilter = ref(null)

    // Computed
    const selectAll = computed({
      get: () => {
        return filteredInventory.value.length > 0 &&
               filteredInventory.value.every(item => selectedItems.value[item.id])
      },
      set: (value) => {
        if (value) {
          filteredInventory.value.forEach(item => {
            const availableQuantity = item.total_units || item.quantity
            const boiteDe = item.product?.boite_de || props.selectedItem?.product?.boite_de
            const requestedQuantity = getCalculatedRequestedQuantity()

            // If we have boite_de, select by boxes, otherwise select full quantity
            let quantityToSelect
            if (boiteDe && boiteDe > 0) {
              // Calculate how many boxes we can select
              const maxBoxes = Math.floor(availableQuantity / boiteDe)
              const requestedBoxes = Math.ceil(requestedQuantity / boiteDe)
              const boxesToSelect = Math.min(maxBoxes, requestedBoxes)
              quantityToSelect = boxesToSelect * boiteDe
            } else {
              // No boite_de, select full quantity
              quantityToSelect = Math.min(availableQuantity, requestedQuantity)
            }

            selectedItems.value[item.id] = {
              inventory_id: item.id,
              quantity: quantityToSelect,
              byBox: false
            }
          })
        } else {
          selectedItems.value = {}
        }
        updateTotalQuantity()
      }
    })

    const filteredInventory = computed(() => {
      let filtered = inventory.value

      // Filter by batch
      if (batchFilter.value) {
        filtered = filtered.filter(item => item.batch_number === batchFilter.value)
      }

      // Filter by expiry status
      if (expiryFilter.value) {
        filtered = filtered.filter(item => {
          const daysUntilExpiry = getDaysUntilExpiry(item.expiry_date)
          switch (expiryFilter.value) {
            case 'expired':
              return daysUntilExpiry < 0
            case 'expiring_30':
              return daysUntilExpiry >= 0 && daysUntilExpiry <= 30
            case 'expiring_90':
              return daysUntilExpiry > 30 && daysUntilExpiry <= 90
            case 'valid':
              return daysUntilExpiry > 90
            default:
              return true
          }
        })
      }

      return filtered
    })

    const batchOptions = computed(() => {
      const batches = [...new Set(inventory.value.map(item => item.batch_number).filter(Boolean))]
      return batches.map(batch => ({ label: batch, value: batch }))
    })

    const expiryOptions = computed(() => [
      { label: 'All', value: null },
      { label: 'Expired', value: 'expired' },
      { label: 'Expiring (≤30 days)', value: 'expiring_30' },
      { label: 'Expiring (≤90 days)', value: 'expiring_90' },
      { label: 'Valid (>90 days)', value: 'valid' }
    ])

    const totalSelectedQuantity = computed(() => {
      return Object.values(selectedItems.value).reduce((total, item) => {
        return total + (parseFloat(item.quantity) || 0)
      }, 0)
    })

    // Methods
    const getCalculatedRequestedQuantity = () => {
      if (!props.selectedItem) return 0
      
      const baseQuantity = props.selectedItem.requested_quantity || 0
      
      // If quantity_by_box is true, multiply by boite_de
      if (props.selectedItem.quantity_by_box && props.selectedItem.product?.boite_de) {
        return baseQuantity * props.selectedItem.product.boite_de
      }
      
      return baseQuantity
    }

    const getRequestedBoxes = () => {
      if (!props.selectedItem?.product?.boite_de) return 0
      
      const requestedQuantity = getCalculatedRequestedQuantity()
      return Math.ceil(requestedQuantity / props.selectedItem.product.boite_de)
    }

    const loadInventory = async () => {
      if (!props.selectedItem?.product?.id) return

      try {
        loading.value = true
        const response = await axios.get(`/api/stock-movements/${props.movementId}/inventory/${props.selectedItem.product.id}`)
        inventory.value = response.data.data || []

        // Sort inventory by expiry date (soonest to expire first)
        inventory.value.sort((a, b) => {
          const dateA = a.expiry_date ? new Date(a.expiry_date) : null
          const dateB = b.expiry_date ? new Date(b.expiry_date) : null

          // Items without expiry date go to the end
          if (!dateA && !dateB) return 0
          if (!dateA) return 1
          if (!dateB) return -1

          // Sort by date ascending (soonest first)
          return dateA - dateB
        })
      } catch (error) {
        console.error('Error loading inventory:', error)
        inventory.value = []
      } finally {
        loading.value = false
      }
    }

    const searchByBarcode = () => {
      if (!barcodeSearch.value.trim()) return

      const foundItem = inventory.value.find(item =>
        item.barcode?.toLowerCase().includes(barcodeSearch.value.toLowerCase())
      )

      if (foundItem) {
        toggleItemSelection(foundItem)
        barcodeSearch.value = ''
      }
    }

    const hasBoiteDe = (inventoryItem) => {
      const boiteDe = inventoryItem?.product?.boite_de || props.selectedItem?.product?.boite_de
      return boiteDe && boiteDe > 0
    }

    const getMaxQuantity = (inventoryItem) => {
      const availableQuantity = inventoryItem.total_units || inventoryItem.quantity
      const selectedItem = selectedItems.value[inventoryItem.id]
      
      if (selectedItem?.byBox) {
        const boiteDe = inventoryItem.product?.boite_de || props.selectedItem?.product?.boite_de
        return Math.floor(availableQuantity / boiteDe)
      }
      
      return availableQuantity
    }

    const updateQuantityMode = (inventoryId) => {
      const item = selectedItems.value[inventoryId]
      const inventoryItem = inventory.value.find(i => i.id === inventoryId)
      const boiteDe = inventoryItem.product?.boite_de || props.selectedItem?.product?.boite_de
      
      if (item.byBox) {
        // Convert units to boxes
        item.quantity = Math.floor(item.quantity / boiteDe)
      } else {
        // Convert boxes to units
        item.quantity = item.quantity * boiteDe
      }
      
      updateTotalQuantity()
    }

    const updateQuantityInput = (inventoryId) => {
      const item = selectedItems.value[inventoryId]
      const inventoryItem = inventory.value.find(i => i.id === inventoryId)
      const availableQuantity = inventoryItem.total_units || inventoryItem.quantity
      const boiteDe = inventoryItem.product?.boite_de || props.selectedItem?.product?.boite_de

      if (item.byBox) {
        // Ensure we don't exceed max boxes
        const maxBoxes = Math.floor(availableQuantity / boiteDe)
        item.quantity = Math.min(Math.max(0, Math.floor(item.quantity)), maxBoxes)
      } else {
        // Ensure we don't exceed available units
        item.quantity = Math.min(Math.max(0, item.quantity), availableQuantity)
      }

      updateTotalQuantity()
    }

    const getQuantityDisplay = (inventoryId, selectedItem) => {
      const inventoryItem = inventory.value.find(i => i.id === inventoryId)
      const boiteDe = inventoryItem.product?.boite_de || props.selectedItem?.product?.boite_de
      
      if (selectedItem.byBox) {
        return `(${selectedItem.quantity * boiteDe} ${inventoryItem.unit || 'units'})`
      } else {
        return `(${Math.floor(selectedItem.quantity / boiteDe)} boxes)`
      }
    }

    const toggleItemSelection = (inventoryItem) => {
      if (selectedItems.value[inventoryItem.id]) {
        delete selectedItems.value[inventoryItem.id]
      } else {
        const availableQuantity = inventoryItem.total_units || inventoryItem.quantity
        const requestedQuantity = getCalculatedRequestedQuantity()

        // Initialize with byBox = false and quantity in units
        let quantityToSelect = Math.min(availableQuantity, requestedQuantity)

        selectedItems.value[inventoryItem.id] = {
          inventory_id: inventoryItem.id,
          quantity: quantityToSelect,
          byBox: false // Default to units
        }
      }
      updateTotalQuantity()
    }

    const toggleSelectAll = () => {
      selectAll.value = !selectAll.value
    }

    const updateTotalQuantity = () => {
      // Force reactivity update
      selectedItems.value = { ...selectedItems.value }
    }

    const clearFilters = () => {
      barcodeSearch.value = ''
      batchFilter.value = null
      expiryFilter.value = null
    }

    const saveSelection = async () => {
      try {
        saving.value = true
        
        // Convert all quantities to units for saving
        const processedSelections = Object.entries(selectedItems.value).map(([id, item]) => {
          const inventoryItem = inventory.value.find(i => i.id === parseInt(id))
          const boiteDe = inventoryItem.product?.boite_de || props.selectedItem?.product?.boite_de
          
          return {
            inventory_id: item.inventory_id,
            quantity: item.byBox ? item.quantity * boiteDe : item.quantity
          }
        })

        const selectionData = {
          item_id: props.selectedItem.id,
          selected_inventory: processedSelections
        }

        await axios.post(`/api/stock-movements/${props.movementId}/select-inventory`, selectionData)

        emit('selection-saved', {
          itemId: props.selectedItem.id,
          selectedItems: processedSelections,
          totalQuantity: totalSelectedQuantity.value
        })

        emit('update:visible', false)
        resetDialog()
      } catch (error) {
        console.error('Error saving selection:', error)
      } finally {
        saving.value = false
      }
    }

    const cancelSelection = () => {
      emit('update:visible', false)
      resetDialog()
    }

    const applySuggestion = (type) => {
      // Reset any existing selections
      selectedItems.value = {}
      
      const availableInventory = [...inventory.value]
        .sort((a, b) => {
          // Sort by expiry date (if available)
          const dateA = a.expiry_date ? new Date(a.expiry_date) : new Date('9999-12-31')
          const dateB = b.expiry_date ? new Date(b.expiry_date) : new Date('9999-12-31')
          return dateA - dateB
        })

      const requestedQuantity = getCalculatedRequestedQuantity()
      const boiteDe = props.selectedItem?.product?.boite_de || 1
      let remainingQuantity = requestedQuantity

      for (const item of availableInventory) {
        if (remainingQuantity <= 0) break

        const availableQuantity = item.total_units || item.quantity

        if (type === 'boxes' && boiteDe > 1) {
          // Calculate boxes needed and available
          const boxesAvailable = Math.floor(availableQuantity / boiteDe)
          const boxesToSelect = Math.min(Math.ceil(remainingQuantity / boiteDe), boxesAvailable)

          if (boxesToSelect > 0) {
            selectedItems.value[item.id] = {
              inventory_id: item.id,
              quantity: boxesToSelect,
              byBox: true
            }
            remainingQuantity -= (boxesToSelect * boiteDe)
          }
        } else {
          // Select by units
          const quantityToSelect = Math.min(remainingQuantity, availableQuantity)
          selectedItems.value[item.id] = {
            inventory_id: item.id,
            quantity: quantityToSelect,
            byBox: false
          }
          remainingQuantity -= quantityToSelect
        }
      }

      updateTotalQuantity()
    }

    const resetDialog = () => {
      selectedItems.value = {}
      barcodeSearch.value = ''
      batchFilter.value = null
      expiryFilter.value = null
    }

    const copyBarcode = (barcode) => {
      navigator.clipboard.writeText(barcode)
    }

    const getUnitDisplay = () => {
      if (!props.selectedItem?.product) return 'units'
      return props.selectedItem.product.unit || props.selectedItem.product.forme || 'units'
    }

    const getBoxesDisplay = (totalQuantity, boiteDe) => {
      if (!boiteDe || !totalQuantity) return ''
      const boxes = Math.floor(totalQuantity / boiteDe)
      return `(${boxes} box${boxes !== 1 ? 'es' : ''})`
    }

    const formatDate = (date) => {
      if (!date) return 'N/A'
      return new Date(date).toLocaleDateString()
    }

    const getDaysUntilExpiry = (expiryDate) => {
      if (!expiryDate) return Infinity
      const today = new Date()
      const expiry = new Date(expiryDate)
      const diffTime = expiry - today
      return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
    }

    const getExpiryStatus = (expiryDate) => {
      const days = getDaysUntilExpiry(expiryDate)
      if (days < 0) return 'Expired'
      if (days <= 30) return 'Critical'
      if (days <= 90) return 'Warning'
      return 'Valid'
    }

    const getExpiryStatusClass = (expiryDate) => {
      const days = getDaysUntilExpiry(expiryDate)
      if (days < 0) return 'tw-bg-red-100 tw-text-red-800'
      if (days <= 30) return 'tw-bg-red-100 tw-text-red-800'
      if (days <= 90) return 'tw-bg-orange-100 tw-text-orange-800'
      return 'tw-bg-green-100 tw-text-green-800'
    }

    // Watchers
    watch(() => props.visible, (newVal) => {
      if (newVal && props.selectedItem) {
        loadInventory()
      }
    })

    watch(() => props.selectedItem, (newItem) => {
      if (newItem && props.visible) {
        loadInventory()
      }
    })

    return {
      inventory,
      selectedItems,
      loading,
      saving,
      barcodeSearch,
      batchFilter,
      expiryFilter,
      selectAll,
      filteredInventory,
      batchOptions,
      expiryOptions,
      totalSelectedQuantity,
      loadInventory,
      searchByBarcode,
      toggleItemSelection,
      toggleSelectAll,
      updateTotalQuantity,
      clearFilters,
      saveSelection,
      cancelSelection,
      resetDialog,
      copyBarcode,
      getUnitDisplay,
      getBoxesDisplay,
      formatDate,
      getExpiryStatus,
      getExpiryStatusClass,
      hasBoiteDe,
      getMaxQuantity,
      updateQuantityMode,
      updateQuantityInput,
      getQuantityDisplay,
      applySuggestion,
      getCalculatedRequestedQuantity,
      getRequestedBoxes
    }
  }
}
</script>

<style scoped>
.product-selection-dialog .p-dialog-content {
  padding: 0;
}

.product-selection-dialog .p-dialog-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.product-selection-dialog .p-dialog-footer {
  padding: 1.5rem;
  border-top: 1px solid #e5e7eb;
}
</style>