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
        <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-3 tw-gap-4 tw-items-center">
          <!-- Product Info -->
          <div class="tw-flex tw-items-center tw-space-x-3">
            <div class="tw-w-10 tw-h-10 tw-bg-blue-500 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
              <i class="pi pi-box tw-text-white"></i>
            </div>
            <div>
              <h3 class="tw-text-base tw-font-bold tw-text-gray-900">{{ selectedItem?.product?.name }}</h3>
              <p class="tw-text-xs tw-text-gray-600">{{ selectedItem?.product?.code }}</p>
            </div>
          </div>

          <!-- Selection Status -->
          <div v-if="Object.keys(selectedItems).length > 0" class="tw-text-center">
            <div class="tw-flex tw-items-center tw-justify-center tw-gap-2 tw-mb-1">
              <span :class="[
                'tw-px-2 tw-py-1 tw-rounded-full tw-text-xs tw-font-medium',
                totalSelectedQuantity >= getCalculatedRequestedQuantity() 
                  ? 'tw-bg-green-100 tw-text-green-700' 
                  : 'tw-bg-orange-100 tw-text-orange-700'
              ]">
                {{ totalSelectedQuantity >= getCalculatedRequestedQuantity() ? '✓ Sufficient' : '⚠ Insufficient' }}
              </span>
              <span class="tw-text-xs tw-text-gray-500">
                {{ Object.keys(selectedItems).length }} items selected
              </span>
            </div>
            <p class="tw-text-sm tw-font-semibold tw-text-gray-900">
              {{ totalSelectedQuantity.toFixed(2) }} / {{ getCalculatedRequestedQuantity() }} {{ getUnitDisplay() }}
            </p>
          </div>

          <!-- Quick Actions -->
          <div class="tw-text-right">
            <p class="tw-text-xs tw-font-medium tw-text-gray-600 tw-mb-2">Quick Select</p>
            <div v-if="hasBoiteDe(selectedItem?.product)" class="tw-flex tw-flex-col tw-gap-1">
              <div class="tw-flex tw-gap-1 tw-justify-end">
                <button 
                  @click="applySuggestion('units')"
                  class="tw-px-2 tw-py-1 tw-bg-blue-50 tw-text-blue-600 tw-rounded tw-text-xs hover:tw-bg-blue-100 tw-transition-colors"
                >
                  {{ getCalculatedRequestedQuantity() }} {{ getUnitDisplay() }}
                </button>
                <button 
                  @click="applySuggestion('boxes')"
                  class="tw-px-2 tw-py-1 tw-bg-blue-50 tw-text-blue-600 tw-rounded tw-text-xs hover:tw-bg-blue-100 tw-transition-colors"
                >
                  {{ getRequestedBoxes() }} boxes
                </button>
              </div>
              <p class="tw-text-xs tw-text-gray-500">
                1 box = {{ selectedItem?.product?.boite_de }} {{ getUnitDisplay() }}
              </p>
            </div>
            <div v-else>
              <button 
                @click="applySuggestion('units')"
                class="tw-px-3 tw-py-1 tw-bg-blue-50 tw-text-blue-600 tw-rounded tw-text-xs hover:tw-bg-blue-100 tw-transition-colors"
              >
                Select {{ getCalculatedRequestedQuantity() }} {{ getUnitDisplay() }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Search and Filter -->
      <div class="tw-bg-white tw-p-3 tw-rounded-lg tw-border tw-border-gray-200">
        <div class="tw-flex tw-flex-wrap tw-gap-3 tw-items-center">
          <!-- Barcode Search -->
          <div class="tw-relative tw-flex-1 tw-min-w-64">
            <InputText
              v-model="barcodeSearch"
              @keyup.enter="searchByBarcode"
              placeholder="Scan or search barcode..."
              class="tw-pl-9 tw-pr-4 tw-py-2 tw-text-sm tw-w-full"
            />
            <i class="pi pi-qrcode tw-absolute tw-left-3 tw-top-1/2 tw-transform -tw-translate-y-1/2 tw-text-gray-400 tw-text-sm"></i>
          </div>

          <!-- Filters -->
          <div class="tw-flex tw-gap-2">
            <Dropdown
              v-model="batchFilter"
              :options="batchOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Batch"
              class="tw-w-32"
              size="small"
              showClear
            />
            <Dropdown
              v-model="expiryFilter"
              :options="expiryOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Expiry"
              class="tw-w-36"
              size="small"
              showClear
            />
            <Button
              @click="clearFilters"
              icon="pi pi-filter-slash"
              severity="secondary"
              outlined
              size="small"
              class="tw-px-2"
            />
          </div>
        </div>
      </div>

      <!-- Inventory Table -->
      <div class="tw-bg-white tw-rounded-lg tw-border tw-border-gray-200 tw-overflow-hidden">
        <div class="tw-overflow-x-auto tw-max-h-80">
          <table class="tw-min-w-full tw-divide-y tw-divide-gray-200">
            <thead class="tw-bg-gray-50 tw-sticky tw-top-0">
              <tr>
                <th class="tw-px-3 tw-py-2 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase">
                  <div class="tw-flex tw-items-center tw-gap-2">
                    <input
                      type="checkbox"
                      :checked="selectAll"
                      @change="toggleSelectAll"
                      class="tw-rounded tw-border-gray-300"
                    />
                    Select
                  </div>
                </th>
                <th class="tw-px-3 tw-py-2 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase">
                  Barcode
                </th>
                <th class="tw-px-3 tw-py-2 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase">
                  Batch / Serial
                </th>
                <th class="tw-px-3 tw-py-2 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase">
                  Expiry
                </th>
                <th class="tw-px-3 tw-py-2 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase">
                  Available
                </th>
                <th class="tw-px-3 tw-py-2 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase">
                  Location
                </th>
                <th class="tw-px-3 tw-py-2 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase">
                  Quantity
                </th>
              </tr>
            </thead>
            <tbody class="tw-divide-y tw-divide-gray-200">
              <tr
                v-for="item in filteredInventory"
                :key="item.id"
                class="tw-hover:tw-bg-gray-50 tw-transition-colors"
              >
                <td class="tw-px-3 tw-py-3 tw-whitespace-nowrap">
                  <input
                    type="checkbox"
                    :checked="isItemSelected(item)"
                    @change="toggleItemSelection(item)"
                    class="tw-rounded tw-border-gray-300"
                  />
                </td>
                <td class="tw-px-3 tw-py-3 tw-whitespace-nowrap tw-text-sm tw-text-gray-900">
                  {{ item.barcode || '-' }}
                </td>
                <td class="tw-px-3 tw-py-3 tw-whitespace-nowrap tw-text-sm tw-text-gray-900">
                  {{ item.batch_number || '-' }}
                </td>
                <td class="tw-px-3 tw-py-3 tw-whitespace-nowrap tw-text-sm">
                  <span
                    :class="{
                      'tw-text-red-600': isExpiringSoon(item.expiry_date),
                      'tw-text-orange-600': isExpired(item.expiry_date),
                      'tw-text-gray-900': !isExpiringSoon(item.expiry_date) && !isExpired(item.expiry_date)
                    }"
                  >
                    {{ formatDate(item.expiry_date) }}
                  </span>
                </td>
                <td class="tw-px-3 tw-py-3 tw-whitespace-nowrap tw-text-sm">
                  <div class="tw-flex tw-flex-col">
                    <span class="tw-font-medium tw-text-gray-900">
                      {{ item.available_quantity }}
                    </span>
                    <span class="tw-text-xs tw-text-gray-500">
                      {{ selectedItem.unit_name }}
                    </span>
                  </div>
                </td>
                <td class="tw-px-3 tw-py-3 tw-whitespace-nowrap tw-text-sm tw-text-gray-500">
                  {{ item.location || '-' }}
                </td>
                <td class="tw-px-3 tw-py-3 tw-whitespace-nowrap">
                  <div class="tw-flex tw-items-center tw-gap-2">
                    <input
                      v-if="isItemSelected(item)"
                      type="number"
                      v-model.number="selectedItems[item.id].quantity"
                      :max="getMaxQuantity(item)"
                      min="0"
                      step="1"
                      @input="updateQuantityInput(item.id)"
                      class="tw-w-16 tw-px-2 tw-py-1 tw-border tw-border-gray-300 tw-rounded tw-text-sm focus:tw-outline-none focus:tw-ring-1 focus:tw-ring-blue-500 focus:tw-border-blue-500"
                    />
                    <span v-else class="tw-text-sm tw-text-gray-400">-</span>
                    <div v-if="hasBoiteDe(item) && isItemSelected(item)" class="tw-flex tw-flex-col tw-items-center">
                      <button
                        @click="selectedItems[item.id].byBox = !selectedItems[item.id].byBox; updateQuantityMode(item.id)"
                        :class="[
                          'tw-px-2 tw-py-1 tw-text-xs tw-rounded tw-transition-colors tw-min-w-12',
                          selectedItems[item.id].byBox
                            ? 'tw-bg-blue-100 tw-text-blue-700 tw-border tw-border-blue-300'
                            : 'tw-bg-gray-100 tw-text-gray-600 tw-border tw-border-gray-300'
                        ]"
                      >
                        {{ selectedItems[item.id].byBox ? 'Box' : 'Unit' }}
                      </button>
                    </div>
                  </div>
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



      <!-- Action Buttons -->
      <div class="tw-flex tw-justify-between tw-items-center tw-pt-4 tw-border-t tw-border-gray-200">
        <div class="tw-text-sm tw-text-gray-600">
          <span v-if="Object.keys(selectedItems).length > 0">
            {{ Object.keys(selectedItems).length }} item(s) selected
          </span>
        </div>
        <div class="tw-flex tw-gap-3">
          <Button
            @click="cancelSelection"
            label="Cancel"
            icon="pi pi-times"
            severity="secondary"
            outlined
            class="tw-px-4 tw-py-2"
          />
          <Button
            @click="saveSelection"
            label="Apply Selection"
            icon="pi pi-check"
            :class="Object.keys(selectedItems).length > 0 ? 'tw-bg-green-600' : 'tw-bg-gray-400'"
            :disabled="Object.keys(selectedItems).length === 0"
            :loading="saving"
            class="tw-px-4 tw-py-2"
          />
        </div>
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
        let quantity = parseFloat(item.quantity) || 0
        
        // If selected by box, convert to units
        if (item.byBox) {
          const boiteDe = props.selectedItem?.product?.boite_de || 1
          quantity = quantity * boiteDe
        }
        
        return total + quantity
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

        // Load existing selections if editing
        if (props.selectedItem?.selected_inventory && Array.isArray(props.selectedItem.selected_inventory)) {
          selectedItems.value = {}
          props.selectedItem.selected_inventory.forEach(selection => {
            if (selection.inventory && selection.inventory.id) {
              selectedItems.value[selection.inventory.id] = {
                inventory_id: selection.inventory.id,
                quantity: parseFloat(selection.quantity || selection.selected_quantity || 0),
                byBox: false // Default to units, can be enhanced later to detect if it was by box
              }
            }
          })
          updateTotalQuantity()
        }
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

    const isItemSelected = (inventoryItem) => {
      return !!selectedItems.value[inventoryItem.id]
    }

    const isExpiringSoon = (expiryDate) => {
      if (!expiryDate) return false
      const days = getDaysUntilExpiry(expiryDate)
      return days > 0 && days <= 90
    }

    const isExpired = (expiryDate) => {
      if (!expiryDate) return false
      const days = getDaysUntilExpiry(expiryDate)
      return days < 0
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
      getRequestedBoxes,
      isItemSelected,
      isExpiringSoon,
      isExpired
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