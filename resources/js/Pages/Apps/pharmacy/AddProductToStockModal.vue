<template>
  <div>
    <!-- Add Product Modal -->
    <Dialog
      v-model:visible="showModal"
      modal
      header="Add Product to Stock"
      :style="{ width: '50rem' }"
      class="add-product-modal"
    >
      <form @submit.prevent="addProductToStock" class="tw-space-y-6">
        <!-- Product Selection Section -->
        <div class="tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50 tw-rounded-xl tw-p-5 tw-border tw-border-blue-100">
          <div class="tw-flex tw-items-center tw-gap-3 tw-mb-4">
            <div class="tw-w-8 tw-h-8 tw-bg-blue-500 tw-rounded-full tw-flex tw-items-center tw-justify-center">
              <i class="pi pi-box tw-text-white tw-text-sm"></i>
            </div>
            <h3 class="tw-text-lg tw-font-semibold tw-text-gray-800">Product Selection</h3>
          </div>

          <div class="tw-space-y-4">
            <div>
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                <i class="pi pi-search tw-text-blue-500 tw-mr-2"></i>
                Select Product *
              </label>
              <Dropdown
                v-model="formData.product_id"
                :options="availableProducts"
                option-label="name"
                option-value="id"
                placeholder="Search and select a product..."
                class="tw-w-full"
                :filter="true"
                filter-placeholder="Type to search products..."
                required
                :loading="loadingProducts"
                @change="onProductSelect"
                @filter="onProductSearch"
                :show-clear="true"
              />
              <small v-if="loadingProducts" class="tw-text-blue-500 tw-block tw-mt-1">
                <i class="pi pi-spin pi-spinner"></i> Searching products...
              </small>
              <small v-else-if="availableProducts.length === 0" class="tw-text-orange-500 tw-block tw-mt-1">
                Start typing to search all products in the system...
              </small>
              <small v-else class="tw-text-green-500 tw-block tw-mt-1">
                {{ availableProducts.length }} product(s) found
              </small>
            </div>

            <!-- Compact Product Info -->
            <div v-if="selectedProduct" class="tw-bg-white tw-rounded-lg tw-p-4 tw-border tw-border-gray-200 tw-shadow-sm">
              <div class="tw-flex tw-items-start tw-justify-between">
                <div class="tw-flex-1">
                  <h4 class="tw-font-medium tw-text-gray-900 tw-mb-2">{{ selectedProduct.name }}</h4>
                  <div class="tw-grid tw-grid-cols-2 md:tw-grid-cols-4 tw-gap-3 tw-text-sm">
                    <div class="tw-flex tw-items-center tw-gap-2">
                      <i class="pi pi-tag tw-text-blue-500"></i>
                      <span class="tw-text-gray-600">Form:</span>
                      <Tag :value="selectedProduct.forme || 'N/A'" :severity="selectedProduct.forme ? 'info' : 'secondary'" class="tw-text-xs" />
                    </div>
                    <div v-if="selectedProduct.boite_de" class="tw-flex tw-items-center tw-gap-2">
                      <i class="pi pi-package tw-text-green-500"></i>
                      <span class="tw-text-gray-600">Box:</span>
                      <Tag :value="selectedProduct.boite_de + ' units'" severity="success" class="tw-text-xs" />
                    </div>
                    <div class="tw-flex tw-items-center tw-gap-2">
                      <i class="pi pi-folder tw-text-purple-500"></i>
                      <span class="tw-text-gray-600">Category:</span>
                      <Tag :value="selectedProduct.category" :severity="getCategorySeverity(selectedProduct.category)" class="tw-text-xs" />
                    </div>
                    <div class="tw-flex tw-items-center tw-gap-2">
                      <Button
                        label="Details"
                        icon="pi pi-eye"
                        class="p-button-text p-button-sm"
                        @click="toggleProductDetails"
                        v-tooltip.top="'View full product details'"
                      />
                    </div>
                  </div>
                </div>
              </div>

              <!-- Expanded Product Details -->
              <div v-if="showProductDetails" class="tw-mt-4 tw-pt-4 tw-border-t tw-border-gray-200">
                <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4 tw-text-sm">
                  <div v-if="selectedProduct.description">
                    <strong class="tw-text-gray-700">Description:</strong>
                    <span class="tw-ml-2">{{ selectedProduct.description }}</span>
                  </div>
                  <div v-if="selectedProduct.is_clinical">
                    <strong class="tw-text-gray-700">Type:</strong>
                    <span class="tw-ml-2">{{ selectedProduct.type_medicament || 'N/A' }}</span>
                  </div>
                  <div v-if="selectedProduct.code_pch">
                    <strong class="tw-text-gray-700">Code PCH:</strong>
                    <span class="tw-ml-2">{{ selectedProduct.code_pch }}</span>
                  </div>
                  <div v-if="selectedProduct.nom_commercial">
                    <strong class="tw-text-gray-700">Commercial Name:</strong>
                    <span class="tw-ml-2">{{ selectedProduct.nom_commercial }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Stock Details Section -->
        <div class="tw-bg-gradient-to-r tw-from-green-50 tw-to-emerald-50 tw-rounded-xl tw-p-5 tw-border tw-border-green-100">
          <div class="tw-flex tw-items-center tw-gap-3 tw-mb-4">
            <div class="tw-w-8 tw-h-8 tw-bg-green-500 tw-rounded-full tw-flex tw-items-center tw-justify-center">
              <i class="pi pi-chart-line tw-text-white tw-text-sm"></i>
            </div>
            <h3 class="tw-text-lg tw-font-semibold tw-text-gray-800">Stock Details</h3>
          </div>

          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
            <div v-if="!preSelectedStockageId">
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                <i class="pi pi-building tw-text-green-500 tw-mr-2"></i>
                Stockage Location *
              </label>
              <Dropdown
                v-model="formData.stockage_id"
                :options="availableStockages"
                option-label="name"
                option-value="id"
                placeholder="Select stockage location"
                class="tw-w-full"
                required
                @change="onStockageSelect"
              />
            </div>

            <div>
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                <i class="pi pi-sort-numeric-up tw-text-green-500 tw-mr-2"></i>
                Quantity *
              </label>
              <div class="tw-space-y-2">
                <div class="tw-flex tw-items-center tw-gap-3">
                  <InputNumber
                    v-model="formData.quantity"
                    class="tw-flex-1"
                    min="1"
                    placeholder="Enter quantity"
                    required
                  />
                  <div v-if="selectedProduct?.boite_de" class="tw-flex tw-items-center tw-gap-2">
                    <input
                      type="checkbox"
                      id="quantityByBox"
                      v-model="quantityByBox"
                      class="tw-w-4 tw-h-4 tw-text-green-600 tw-bg-gray-100 tw-border-gray-300 tw-rounded focus:tw-ring-green-500"
                    />
                    <label for="quantityByBox" class="tw-text-sm tw-text-gray-700 tw-whitespace-nowrap">
                      By box
                    </label>
                  </div>
                </div>
                <div v-if="selectedProduct?.boite_de" class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-text-gray-600">
                  <i class="pi pi-info-circle tw-text-blue-500"></i>
                  <span>Total units: <strong>{{ calculateTotalUnits(formData.quantity) }}</strong></span>
                  <span v-if="quantityByBox" class="tw-text-green-600">
                    ({{ Number(formData.quantity || 0) }} boxes × {{ Number(selectedProduct.boite_de) }} units)
                  </span>
                </div>
              </div>
            </div>

            <div class="tw-col-span-full">
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                <i class="pi pi-map-marker tw-text-green-500 tw-mr-2"></i>
                Storage Location
              </label>
              <Dropdown
                v-model="formData.location"
                :options="locationOptions"
                option-label="label"
                option-value="value"
                placeholder="Select storage location"
                class="tw-w-full"
                :loading="loadingLocations"
                @change="onLocationChange"
              >
                <template #empty>
                  <div class="tw-text-center tw-py-4">
                    <i class="pi pi-info-circle tw-text-gray-400 tw-text-2xl tw-mb-2 tw-block"></i>
                    <p class="tw-text-sm tw-text-gray-500 tw-mb-1">No locations available</p>
                    <p class="tw-text-xs tw-text-gray-400">Create locations in the Stockage Details page first</p>
                  </div>
                </template>
              </Dropdown>
              <small v-if="locationOptions.length === 0 && !loadingLocations" class="tw-text-gray-500 tw-block tw-mt-1">
                No locations available for this stockage
              </small>
            </div>
          </div>
        </div>
        <!-- Product Information Section -->
        <div class="tw-bg-gradient-to-r tw-from-purple-50 tw-to-violet-50 tw-rounded-xl tw-p-5 tw-border tw-border-purple-100">
          <div class="tw-flex tw-items-center tw-gap-3 tw-mb-4">
            <div class="tw-w-8 tw-h-8 tw-bg-purple-500 tw-rounded-full tw-flex tw-items-center tw-justify-center">
              <i class="pi pi-info-circle tw-text-white tw-text-sm"></i>
            </div>
            <h3 class="tw-text-lg tw-font-semibold tw-text-gray-800">Product Information</h3>
          </div>

          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
            <div>
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                <i class="pi pi-hashtag tw-text-purple-500 tw-mr-2"></i>
                Batch Number
              </label>
              <InputText
                v-model="formData.batch_number"
                class="tw-w-full"
                placeholder="Enter batch number"
              />
            </div>

            <div>
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                <i class="pi pi-qrcode tw-text-purple-500 tw-mr-2"></i>
                Serial Number
              </label>
              <InputText
                v-model="formData.serial_number"
                class="tw-w-full"
                placeholder="Enter serial number"
              />
            </div>

            <div>
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                <i class="pi pi-dollar tw-text-purple-500 tw-mr-2"></i>
                Purchase Price
              </label>
              <div class="tw-flex tw-items-center tw-gap-4">
                <InputNumber
                  v-model="formData.purchase_price"
                  class="tw-flex-1"
                  min="0"
                  :minFractionDigits="2"
                  :maxFractionDigits="2"
                  placeholder="0.00"
                />
                <div v-if="selectedProduct?.boite_de" class="tw-flex tw-items-center tw-gap-2">
                  <input
                    type="checkbox"
                    id="purchasePriceByBox"
                    v-model="purchasePriceByBox"
                    class="tw-w-4 tw-h-4 tw-text-green-600 tw-bg-gray-100 tw-border-gray-300 tw-rounded focus:tw-ring-green-500"
                  />
                  <label for="purchasePriceByBox" class="tw-text-sm tw-text-gray-700 tw-whitespace-nowrap">
                    By box
                  </label>
                </div>
              </div>
              <div v-if="selectedProduct?.boite_de && purchasePriceByBox && formData.purchase_price" class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-text-gray-600 tw-mt-2">
                <i class="pi pi-info-circle tw-text-blue-500"></i>
                <span>Price per unit: <strong>{{ calculatePricePerUnit(formData.purchase_price) }}</strong></span>
                <span class="tw-text-green-600">
                  ({{ Number(formData.purchase_price || 0) }} / {{ Number(selectedProduct.boite_de) }} units)
                </span>
              </div>
            </div>

            <div>
              <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                <i class="pi pi-calendar tw-text-purple-500 tw-mr-2"></i>
                Expiry Date
              </label>
              <Calendar
                v-model="formData.expiry_date"
                class="tw-w-full"
                dateFormat="dd/mm/yy"
                :showIcon="true"
                placeholder="Select expiry date"
              />
            </div>
          </div>
        </div>

        <!-- Additional Settings Section -->
        <div class="tw-bg-gradient-to-r tw-from-orange-50 tw-to-amber-50 tw-rounded-xl tw-p-5 tw-border tw-border-orange-100">
          <div class="tw-flex tw-items-center tw-gap-3 tw-mb-4">
            <div class="tw-w-8 tw-h-8 tw-bg-orange-500 tw-rounded-full tw-flex tw-items-center tw-justify-center">
              <i class="pi pi-cog tw-text-white tw-text-sm"></i>
            </div>
            <h3 class="tw-text-lg tw-font-semibold tw-text-gray-800">Additional Settings</h3>
          </div>

            <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
            <!-- min_stock_level removed per request -->

            <div class="tw-flex tw-items-end">
              <div class="tw-bg-blue-50 tw-rounded-lg tw-p-4 tw-border tw-border-blue-200 tw-w-full">
                <div class="tw-flex tw-items-center tw-gap-2 tw-mb-2">
                  <i class="pi pi-lightbulb tw-text-blue-500"></i>
                  <span class="tw-text-sm tw-font-medium tw-text-blue-800">Quick Tip</span>
                </div>
                <p class="tw-text-xs tw-text-blue-700">
                  Double-check all information before adding to stock. You can always edit these details later.
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="tw-flex tw-justify-end tw-gap-3 tw-pt-4 tw-border-t tw-border-gray-200">
          <Button
            type="button"
            label="Cancel"
            icon="pi pi-times"
            class="p-button-secondary p-button-outlined"
            @click="closeModal"
            :disabled="isSubmitting"
          />
          <Button
            type="submit"
            label="Add to Stock"
            icon="pi pi-plus"
            class="p-button-primary"
            :disabled="isSubmitting"
            :loading="isSubmitting"
          />
        </div>
      </form>
    </Dialog>

    <!-- Add Location Modal -->
    <Dialog
      v-model:visible="showAddLocationModal"
      modal
      header="Add New Location"
      :style="{ width: '40rem' }"
    >
      <form @submit.prevent="createLocation" class="tw-p-4">
        <div class="tw-grid tw-grid-cols-1 tw-gap-4">
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">Tool Type *</label>
            <Dropdown
              v-model="newLocation.tool_type"
              :options="toolTypes"
              option-label="label"
              option-value="value"
              placeholder="Select tool type"
              class="tw-w-full"
              required
            />
          </div>

          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">Tool Number *</label>
            <InputNumber
              v-model="newLocation.tool_number"
              class="tw-w-full"
              min="1"
              placeholder="Enter tool number"
              required
            />
          </div>

          <div v-if="newLocation.tool_type === 'RY'">
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">Block *</label>
            <Dropdown
              v-model="newLocation.block"
              :options="blocks"
              option-label="label"
              option-value="value"
              placeholder="Select block"
              class="tw-w-full"
              required
            />
          </div>

          <div v-if="newLocation.tool_type === 'RY'">
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">Shelf Level *</label>
            <InputNumber
              v-model="newLocation.shelf_level"
              class="tw-w-full"
              min="1"
              placeholder="Enter shelf level"
              required
            />
          </div>

          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">Location Code</label>
            <InputText
              :value="locationCodePreview"
              placeholder="Auto-generated"
              class="tw-w-full"
              readonly
            />
            <small class="tw-text-gray-500">This code is auto-generated based on the tool details</small>
          </div>
        </div>

        <div class="tw-flex tw-justify-end tw-gap-3 tw-mt-6 tw-pt-4 tw-border-t tw-border-gray-200">
          <Button
            type="button"
            label="Cancel"
            icon="pi pi-times"
            class="p-button-secondary"
            @click="closeAddLocationModal"
            :disabled="isSubmitting"
          />
          <Button
            type="submit"
            label="Create Location"
            icon="pi pi-check"
            class="p-button-primary"
            :disabled="isSubmitting"
            :loading="isSubmitting"
          />
        </div>
      </form>
    </Dialog>
  </div>
</template>

<script>
import axios from 'axios';

// PrimeVue Components
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';
import Calendar from 'primevue/calendar';
import Tag from 'primevue/tag';

export default {
  name: 'AddProductToStockModal',
  components: {
    Dialog,
    Button,
    InputText,
    InputNumber,
    Dropdown,
    Calendar,
    Tag
  },
  props: {
    visible: {
      type: Boolean,
      default: false
    },
    preSelectedStockageId: {
      type: [String, Number],
      default: null
    },
    availableStockages: {
      type: Array,
      default: () => []
    },
    serviceAbv: {
      type: String,
      default: ''
    }
  },
  emits: ['update:visible', 'success', 'error'],
  data() {
    return {
      showModal: this.visible,
      showAddLocationModal: false,
      isSubmitting: false,
      showProductDetails: false,
      quantityByBox: false,
      purchasePriceByBox: false,

      // Data
      availableProducts: [],
      selectedProduct: null,
      availableLocations: [],
      locationOptions: [],
      toolTypes: [],
      blocks: [],

      // Loading states
      loadingProducts: false,
      loadingLocations: false,

      // Forms
      formData: {
        product_id: null,
        stockage_id: this.preSelectedStockageId,
        quantity: null,
        unit: '',
        batch_number: '',
        serial_number: '',
        purchase_price: null,
        expiry_date: null,
        location: '',
        
      },
      newLocation: {
        tool_type: '',
        tool_number: null,
        block: '',
        shelf_level: null,
        stockage_id: null
      }
    }
  },
  mounted() {
    // Initialize data when component is first mounted
    // Don't fetch products initially - user will search when opening modal
    this.fetchToolTypes();
    this.fetchBlocks();
    if (this.preSelectedStockageId) {
      this.formData.stockage_id = this.preSelectedStockageId;
      this.fetchStockageLocations(this.preSelectedStockageId);
    }
  },
  watch: {
    visible(newVal) {
      this.showModal = newVal;
      if (newVal) {
        this.resetForm();
        // Don't pre-fetch products - user will search when needed
        if (this.toolTypes.length === 0) {
          this.fetchToolTypes();
        }
        if (this.blocks.length === 0) {
          this.fetchBlocks();
        }
        if (this.preSelectedStockageId) {
          this.formData.stockage_id = this.preSelectedStockageId;
          this.fetchStockageLocations(this.preSelectedStockageId);
        }
      }
    },
    showModal(newVal) {
      this.$emit('update:visible', newVal);
    }
  },
  computed: {
    locationCodePreview() {
      if (!this.newLocation.tool_type || !this.newLocation.tool_number) {
        return '';
      }

      const serviceAbv = this.serviceAbv || '';
      const stockage = this.availableStockages.find(s => s.id === this.formData.stockage_id);
      const stockageLocationCode = stockage?.location_code || '';

      let code = serviceAbv + stockageLocationCode + '-' + this.newLocation.tool_type + this.newLocation.tool_number;

      if (this.newLocation.tool_type === 'RY' && this.newLocation.block && this.newLocation.shelf_level) {
        code += '-' + this.newLocation.block + this.newLocation.shelf_level;
      }

      return code;
    }
  },
  methods: {
    async fetchAvailableProducts(searchQuery = '') {
      this.loadingProducts = true;
      try {
        // Fetch all products or search if query provided
        const response = await axios.get('/api/pharmacy/products', {
          params: { 
            per_page: 50,
            search: searchQuery
          }
        });
        if (response.data.success) {
          this.availableProducts = response.data.data || [];
        }
      } catch (error) {
        console.error('Failed to load products:', error);
        this.$emit('error', 'Failed to load available products');
        this.availableProducts = [];
      } finally {
        this.loadingProducts = false;
      }
    },

    async onProductSearch(event) {
      // This method is called when user types in the dropdown filter
      const searchQuery = event.filter || '';
      if (searchQuery.length >= 2) {
        // Only search if at least 2 characters typed
        await this.fetchAvailableProducts(searchQuery);
      } else if (searchQuery.length === 0) {
        // Clear search on empty
        this.availableProducts = [];
      }
    },

    async fetchToolTypes() {
      try {
        const response = await axios.get('/api/pharmacy/stockage-tools/types');
        if (response.data.success) {
          this.toolTypes = response.data.data;
        }
      } catch (error) {
        console.error('Failed to load tool types');
      }
    },

    async fetchBlocks() {
      try {
        const response = await axios.get('/api/pharmacy/stockage-tools/blocks');
        if (response.data.success) {
          this.blocks = response.data.data;
        }
      } catch (error) {
        console.error('Failed to load blocks');
      }
    },

    async fetchStockageLocations(stockageId) {

      if (!stockageId) return;

      this.loadingLocations = true;
      try {
        const response = await axios.get(`/api/pharmacy/stockages/${stockageId}/tools`);

          const tools = response.data.data.data || response.data.data || [];

          if (tools.length > 0) {
            this.availableLocations = tools.map(tool => ({
              id: tool.id,
              name: tool.location_code,
              location_code: tool.location_code,
              tool_type: tool.tool_type,
              tool_number: tool.tool_number,
              block: tool.block,
              shelf_level: tool.shelf_level,
              current_items: 'Empty'
            }));

            await this.calculateLocationItemCounts(stockageId);
            this.updateLocationSuggestions();

        } else {
          this.availableLocations = [];
          this.updateLocationSuggestions();
        }
      } catch (error) {
        console.error('Failed to load stockage tools:', error);
        this.availableLocations = [];
        this.updateLocationSuggestions();
        this.$emit('error', 'Failed to load locations for this stockage');
      } finally {
        this.loadingLocations = false;
      }
    },

    async calculateLocationItemCounts(stockageId) {
      try {
        const response = await axios.get('/api/pharmacy/inventory', {
          params: { stockage_id: stockageId, per_page: 1000 }
        });

        if (response.data.success) {
          const inventory = response.data.data;

          const locationCounts = {};
          inventory.forEach(item => {
            if (item.location) {
              locationCounts[item.location] = (locationCounts[item.location] || 0) + 1;
            }
          });

          this.availableLocations.forEach(location => {
            const count = locationCounts[location.location_code] || 0;
            location.current_items = count === 0 ? 'Empty' : `${count} item${count > 1 ? 's' : ''}`;
          });
        }
      } catch (error) {
        console.error('Failed to calculate location item counts:', error);
      }
    },

    onProductSelect() {
      this.selectedProduct = this.availableProducts.find(p => p.id === this.formData.product_id);
      if (this.selectedProduct) {
        this.formData.unit = this.selectedProduct.forme || '';
      }
      this.showProductDetails = false;
      this.quantityByBox = false;
      this.purchasePriceByBox = false;

      this.updateLocationSuggestions();
    },

    async onStockageSelect() {
      await this.fetchStockageLocations(this.formData.stockage_id);
      this.updateLocationSuggestions();
    },

    async updateLocationSuggestions() {
      if (!this.formData.stockage_id) return;

      let locationOptions = [];

      if (this.selectedProduct) {
        const history = await this.fetchProductStockHistory(this.selectedProduct.id, this.formData.stockage_id);

        const currentLocations = history.filter(h => Number(h.quantity || 0) > 0).map(h => ({
          label: `${h.location || 'Unnamed Location'} (Current: ${Number(h.quantity || 0)} ${h.unit || 'units'})`,
          value: h.location,
          type: 'current'
        }));

        if (currentLocations.length > 0) {
          locationOptions = currentLocations;
        } else {
          const lastLocation = history.sort((a, b) => new Date(b.updated_at) - new Date(a.updated_at))[0];
          if (lastLocation) {
            locationOptions = [{
              label: `${lastLocation.location || 'Unnamed Location'} (Last used)`,
              value: lastLocation.location,
              type: 'last'
            }];
          }
        }
      }

      if (locationOptions.length === 0) {
        locationOptions = this.availableLocations.map(loc => ({
          label: `${loc.location_code} (${loc.current_items || 'Empty'})`,
          value: loc.location_code,
          type: 'available'
        }));
      }

      locationOptions.push({
        label: '+ Add New Location',
        value: 'new_location',
        type: 'new'
      });

      this.locationOptions = locationOptions;

      // Only set default location if none is selected and we have valid options
      if (locationOptions.length > 1 && (!this.formData.location || this.formData.location === 'new_location')) {
        const firstValidOption = locationOptions.find(opt => opt.value !== 'new_location');
        if (firstValidOption) {
          this.formData.location = firstValidOption.value;
        }
      }
    },

    async fetchProductStockHistory(productId, stockageId) {
      try {
        const response = await axios.get(`/api/pharmacy/inventory/by-product/${productId}`, {
          params: { stockage_id: stockageId }
        });
        if (response.data.success) {
          return response.data.data || [];
        }
        return [];
      } catch (error) {
        console.error('Failed to fetch product stock history:', error);
        return [];
      }
    },

    onLocationChange(event) {
      if (event.value === 'new_location') {
        this.openAddLocationModal();
      }
    },

    toggleProductDetails() {
      this.showProductDetails = !this.showProductDetails;
    },

    calculateTotalUnits(quantity) {
      if (!this.selectedProduct?.boite_de || !quantity) return Number(quantity || 0);

      if (this.quantityByBox) {
        return Number(quantity) * Number(this.selectedProduct.boite_de);
      } else {
        return Number(quantity);
      }
    },

    calculatePricePerUnit(price) {
      if (!this.selectedProduct?.boite_de || !price || !this.purchasePriceByBox) return Number(price || 0);

      return (Number(price) / Number(this.selectedProduct.boite_de)).toFixed(2);
    },

    getCategorySeverity(category) {
      const severities = {
        'medication': 'danger',
        'equipment': 'info',
        'consumable': 'success',
        'supplies': 'warning'
      };
      return severities[category] || 'secondary';
    },

    openAddLocationModal() {
      if (!this.formData.stockage_id) {
        this.$emit('error', 'Please select a stockage first');
        return;
      }
      this.newLocation.stockage_id = this.formData.stockage_id;
      this.showAddLocationModal = true;
    },

    closeAddLocationModal() {
      this.showAddLocationModal = false;
      this.resetNewLocation();
      // Don't automatically select a location, let the user choose
      // The updateLocationSuggestions method will handle proper default selection
    },

    resetNewLocation() {
      this.newLocation = {
        tool_type: '',
        tool_number: null,
        block: '',
        shelf_level: null,
        stockage_id: null
      };
    },

    resetForm() {
      this.formData = {
        product_id: null,
        stockage_id: this.preSelectedStockageId,
        quantity: null,
        unit: '',
        batch_number: '',
        serial_number: '',
        purchase_price: null,
        expiry_date: null,
        location: '',
        
      };
      this.selectedProduct = null;
      this.showProductDetails = false;
      this.availableLocations = [];
      this.locationOptions = [];
      this.showAddLocationModal = false;
      this.resetNewLocation();
      this.quantityByBox = false;
      this.purchasePriceByBox = false;
    },

    closeModal() {
      this.showModal = false;
      this.resetForm();
    },

    openModal() {
      this.showModal = true;
      this.resetForm();
      // Don't pre-fetch products - user will search when needed
      this.fetchToolTypes();
      this.fetchBlocks();
      if (this.preSelectedStockageId) {
        this.formData.stockage_id = this.preSelectedStockageId;
        this.fetchStockageLocations(this.preSelectedStockageId);
      }
    },

    async createLocation() {
      if (!this.newLocation.tool_type || !this.newLocation.tool_number) {
        this.$emit('error', 'Tool type and tool number are required');
        return;
      }

      if (this.newLocation.tool_type === 'RY' && (!this.newLocation.block || !this.newLocation.shelf_level)) {
        this.$emit('error', 'Block and shelf level are required for rack with shelves');
        return;
      }

      this.isSubmitting = true;

      try {
        const locationData = {
          ...this.newLocation,
          tool_number: Number(this.newLocation.tool_number),
          shelf_level: this.newLocation.shelf_level ? Number(this.newLocation.shelf_level) : null
        };
        const response = await axios.post(`/api/pharmacy/stockages/${this.newLocation.stockage_id}/tools`, locationData);
        if (response.data.success) {
          const newLoc = {
            id: response.data.data.id,
            name: response.data.data.location_code,
            location_code: response.data.data.location_code,
            tool_type: this.newLocation.tool_type,
            tool_number: this.newLocation.tool_number,
            block: this.newLocation.block,
            shelf_level: this.newLocation.shelf_level,
            current_items: 'Empty'
          };
          this.availableLocations.push(newLoc);

          this.formData.location = response.data.data.location_code;

          this.updateLocationSuggestions();

          this.closeAddLocationModal();
          this.$emit('success', 'Location created successfully!');
        }
      } catch (error) {
        console.error('Failed to create location:', error);
        this.$emit('error', 'Failed to create location. Please try again.');
      } finally {
        this.isSubmitting = false;
      }
    },

    async addProductToStock() {
      if (!this.formData.product_id || !this.formData.stockage_id || !this.formData.quantity) {
        this.$emit('error', 'Product, stockage, and quantity are required');
        return;
      }

      this.isSubmitting = true;

        try {
        const stockData = {
          pharmacy_product_id: this.formData.product_id,
          pharmacy_stockage_id: this.formData.stockage_id,
          quantity: Number(this.formData.quantity || 0),
          unit: this.selectedProduct?.forme || this.formData.unit,
          batch_number: this.formData.batch_number,
          serial_number: this.formData.serial_number,
          purchase_price: Number(this.formData.purchase_price || 0),
          expiry_date: this.formData.expiry_date,
          location: this.formData.location,
          quantity_by_box: this.quantityByBox
        };

        const response = await axios.post('/api/pharmacy/inventory', stockData);
        if (response.data.success) {
          this.$emit('success', 'Product added to stock successfully!');
          this.closeModal();
        }
      } catch (error) {
        console.error('Failed to add product to stock:', error);
        if (error.response && error.response.data.errors) {
          this.$emit('error', Object.values(error.response.data.errors).flat().join(', '));
        } else {
          this.$emit('error', 'Failed to add product to stock. Please try again.');
        }
      } finally {
        this.isSubmitting = false;
      }
    }
  }
}
</script>

<style scoped>
@keyframes fade-in {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.tw-animate-fade-in {
  animation: fade-in 0.3s ease-out;
}

/* Custom modal styling */
.add-product-modal .p-dialog-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 12px 12px 0 0;
  padding: 1.5rem;
}

.add-product-modal .p-dialog-header .p-dialog-title {
  font-size: 1.25rem;
  font-weight: 600;
}

.add-product-modal .p-dialog-content {
  padding: 0;
  border-radius: 0 0 12px 12px;
}

.add-product-modal .p-dialog-footer {
  padding: 1.5rem;
  border-top: 1px solid #e5e7eb;
  background: #f9fafb;
  border-radius: 0 0 12px 12px;
}

/* Section styling */
.add-product-modal .section-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.add-product-modal .section-icon {
  width: 2rem;
  height: 2rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 0.875rem;
}

/* Form field styling */
.add-product-modal .p-inputtext,
.add-product-modal .p-inputnumber-input,
.add-product-modal .p-dropdown,
.add-product-modal .p-calendar {
  border-radius: 8px;
  border: 1px solid #d1d5db;
  transition: all 0.2s ease;
}

.add-product-modal .p-inputtext:focus,
.add-product-modal .p-inputnumber-input:focus,
.add-product-modal .p-dropdown:focus,
.add-product-modal .p-calendar:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Button styling */
.add-product-modal .p-button {
  border-radius: 8px;
  font-weight: 500;
  transition: all 0.2s ease;
}

.add-product-modal .p-button:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Tag styling */
.add-product-modal .p-tag {
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 500;
}

/* Checkbox styling */
.add-product-modal input[type="checkbox"] {
  width: 1rem;
  height: 1rem;
  border-radius: 4px;
  border: 2px solid #d1d5db;
  background: white;
  cursor: pointer;
  transition: all 0.2s ease;
}

.add-product-modal input[type="checkbox"]:checked {
  background: #10b981;
  border-color: #10b981;
}

.add-product-modal input[type="checkbox"]:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

/* Responsive design */
@media (max-width: 768px) {
  .add-product-modal {
    width: 95vw !important;
    max-width: none !important;
  }

  .add-product-modal .p-dialog-header {
    padding: 1rem;
  }

  .add-product-modal .p-dialog-content {
    padding: 1rem;
  }

  .add-product-modal .p-dialog-footer {
    padding: 1rem;
  }
}
</style>
