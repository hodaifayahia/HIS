add<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-blue-50 tw-via-indigo-50 tw-to-purple-50 tw-p-6">
    <Toast position="top-right" />

    <!-- Loading State -->
    <div v-if="loading" class="tw-flex tw-justify-center tw-items-center tw-min-h-screen">
      <div class="tw-text-center">
        <i class="pi pi-spin pi-spinner tw-text-4xl tw-text-blue-600 tw-mb-4"></i>
        <p class="tw-text-lg tw-text-gray-600">Loading demand details...</p>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="tw-text-center tw-py-12">
      <i class="pi pi-exclamation-triangle tw-text-6xl tw-text-red-400 tw-mb-4"></i>
      <h3 class="tw-text-xl tw-font-medium tw-text-gray-900 tw-mb-2">Error Loading Demand</h3>
      <p class="tw-text-gray-500 tw-mb-6">{{ error }}</p>
      <Button 
        label="Try Again" 
        icon="pi pi-refresh" 
        @click="loadDemandDetails"
      />
    </div>

    <!-- Main Content -->
    <div v-else-if="demand">
      <!-- Header -->
      <Card class="tw-mb-8 tw-bg-gradient-to-r tw-from-blue-600 tw-to-indigo-700 tw-text-white tw-shadow-xl">
        <template #content>
          <div class="tw-flex tw-justify-between tw-items-center">
            <div>
              <h1 class="tw-text-3xl tw-font-bold tw-mb-2">
                <i class="pi pi-shopping-cart tw-mr-3"></i>
                Demand Details
              </h1>
              <p class="tw-text-blue-100 tw-text-lg">
                {{ demand.demand_code }}
              </p>
            </div>
            <div class="tw-text-right">
              <Tag 
                :value="demand.status" 
                :severity="getStatusSeverity(demand.status)"
                class="tw-text-lg tw-px-4 tw-py-2"
              />
            </div>
          </div>
        </template>
      </Card>

      <!-- Action Buttons -->
      <div class="tw-mb-6 tw-flex tw-gap-3 tw-flex-wrap">
        <Button 
          label="Back to List" 
          icon="pi pi-arrow-left" 
          class="p-button-outlined"
          @click="$router.back()"
        />
        <Button 
          v-if="demand.status === 'draft'"
          label="Edit Demand" 
          icon="pi pi-pencil" 
          @click="showEditDialog = true"
        />
        <Button 
          v-if="demand.status === 'draft' && demand.items?.length > 0"
          label="Send Demand" 
          icon="pi pi-send" 
          class="p-button-success"
          :loading="sendingDemand"
          @click="sendDemand"
        />
        <Button 
          v-if="demand.status === 'draft'"
          label="Delete Demand" 
          icon="pi pi-trash" 
          class="p-button-danger"
          @click="deleteDemand"
        />
        <Button 
          label="Print" 
          icon="pi pi-print" 
          class="p-button-help"
          @click="printDemand"
        />
        <Button 
          label="Export PDF" 
          icon="pi pi-file-pdf" 
          class="p-button-warning"
          @click="exportPDF"
        />
      </div>

      <!-- Demand Information -->
      <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-2 tw-gap-6 tw-mb-8">
        <!-- Basic Information -->
        <Card class="tw-shadow-lg">
          <template #title>
            <div class="tw-flex tw-items-center">
              <i class="pi pi-info-circle tw-mr-3 tw-text-blue-600"></i>
              <span>Basic Information</span>
            </div>
          </template>
          <template #content>
            <div class="tw-space-y-4">
              <div class="tw-flex tw-justify-between tw-items-center tw-border-b tw-pb-2">
                <span class="tw-font-medium tw-text-gray-700">Demand Code:</span>
                <span class="tw-font-mono tw-font-bold tw-text-gray-900">{{ demand.demand_code }}</span>
              </div>
              <div class="tw-flex tw-justify-between tw-items-center tw-border-b tw-pb-2">
                <span class="tw-font-medium tw-text-gray-700">Service:</span>
                <span class="tw-text-gray-900">{{ demand.service?.service_name || 'N/A' }}</span>
              </div>
              <div class="tw-flex tw-justify-between tw-items-center tw-border-b tw-pb-2">
                <span class="tw-font-medium tw-text-gray-700">Status:</span>
                <Tag 
                  :value="demand.status" 
                  :severity="getStatusSeverity(demand.status)"
                  class="tw-font-medium"
                />
              </div>
              <div class="tw-flex tw-justify-between tw-items-center tw-border-b tw-pb-2">
                <span class="tw-font-medium tw-text-gray-700">Expected Date:</span>
                <span class="tw-text-gray-900">{{ formatDate(demand.expected_date) }}</span>
              </div>
              <div class="tw-flex tw-justify-between tw-items-center tw-border-b tw-pb-2">
                <span class="tw-font-medium tw-text-gray-700">Created Date:</span>
                <span class="tw-text-gray-900">{{ formatDateTime(demand.created_at) }}</span>
              </div>
              <div class="tw-flex tw-justify-between tw-items-center tw-border-b tw-pb-2">
                <span class="tw-font-medium tw-text-gray-700">Updated Date:</span>
                <span class="tw-text-gray-900">{{ formatDateTime(demand.updated_at) }}</span>
              </div>
              <div v-if="demand.notes" class="tw-border-b tw-pb-2">
                <span class="tw-font-medium tw-text-gray-700 tw-block tw-mb-2">Notes:</span>
                <p class="tw-text-gray-900 tw-bg-gray-50 tw-p-3 tw-rounded-lg">{{ demand.notes }}</p>
              </div>
            </div>
          </template>
        </Card>

        <!-- Summary Statistics -->
        <Card class="tw-shadow-lg">
          <template #title>
            <div class="tw-flex tw-items-center">
              <i class="pi pi-chart-bar tw-mr-3 tw-text-green-600"></i>
              <span>Summary</span>
            </div>
          </template>
          <template #content>
            <div class="tw-space-y-4">
              <div class="tw-bg-blue-50 tw-p-4 tw-rounded-lg">
                <div class="tw-flex tw-justify-between tw-items-center">
                  <div>
                    <div class="tw-text-2xl tw-font-bold tw-text-blue-900">{{ demand.items?.length || 0 }}</div>
                    <div class="tw-text-sm tw-text-blue-700">Total Items</div>
                  </div>
                  <div class="tw-w-12 tw-h-12 tw-bg-blue-100 tw-rounded-full tw-flex tw-items-center tw-justify-center">
                    <i class="pi pi-box tw-text-blue-600 tw-text-xl"></i>
                  </div>
                </div>
              </div>

              <div class="tw-bg-green-50 tw-p-4 tw-rounded-lg">
                <div class="tw-flex tw-justify-between tw-items-center">
                  <div>
                    <div class="tw-text-2xl tw-font-bold tw-text-green-900">{{ totalQuantity }}</div>
                    <div class="tw-text-sm tw-text-green-700">Total Quantity</div>
                  </div>
                  <div class="tw-w-12 tw-h-12 tw-bg-green-100 tw-rounded-full tw-flex tw-items-center tw-justify-center">
                    <i class="pi pi-calculator tw-text-green-600 tw-text-xl"></i>
                  </div>
                </div>
              </div>

              <div class="tw-bg-purple-50 tw-p-4 tw-rounded-lg">
                <div class="tw-flex tw-justify-between tw-items-center">
                  <div>
                    <div class="tw-text-2xl tw-font-bold tw-text-purple-900">${{ totalValue.toFixed(2) }}</div>
                    <div class="tw-text-sm tw-text-purple-700">Estimated Total</div>
                  </div>
                  <div class="tw-w-12 tw-h-12 tw-bg-purple-100 tw-rounded-full tw-flex tw-items-center tw-justify-center">
                    <i class="pi pi-dollar tw-text-purple-600 tw-text-xl"></i>
                  </div>
                </div>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <!-- Items Section -->
      <Card class="tw-shadow-xl">
        <template #title>
          <div class="tw-flex tw-justify-between tw-items-center">
            <div class="tw-flex tw-items-center">
              <i class="pi pi-box tw-mr-3 tw-text-purple-600"></i>
              <span class="tw-text-xl tw-font-semibold">Demand Items</span>
            </div>
            <Button 
              v-if="demand.status === 'draft'"
              label="Add Item" 
              icon="pi pi-plus" 
              class="p-button-sm"
              @click="showAddItemDialog = true"
            />
          </div>
        </template>

        <template #content>
          <DataTable 
            :value="demand.items || []" 
            responsiveLayout="scroll"
            stripedRows
            class="tw-mt-4"
          >
            <template #empty>
              <div class="tw-text-center tw-py-12">
                <i class="pi pi-box tw-text-6xl tw-text-gray-300 tw-mb-4"></i>
                <h3 class="tw-text-xl tw-font-medium tw-text-gray-900 tw-mb-2">No items in this demand</h3>
                <p class="tw-text-gray-500 tw-mb-6">Add some products to this demand to get started.</p>
                <Button 
                  v-if="demand.status === 'draft'"
                  label="Add First Item" 
                  icon="pi pi-plus" 
                  @click="showAddItemDialog = true"
                />
              </div>
            </template>

            <Column field="product.product_code" header="Product Code" sortable>
              <template #body="slotProps">
                <span class="tw-font-mono tw-text-sm">{{ slotProps.data.product?.product_code }}</span>
              </template>
            </Column>

            <Column field="product.name" header="Product Name" sortable>
              <template #body="slotProps">
                <div class="tw-flex tw-items-center">
                  <i class="pi pi-box tw-mr-2 tw-text-purple-500"></i>
                  <div>
                    <div class="tw-font-medium">{{ slotProps.data.product?.name }}</div>
                    <div v-if="slotProps.data.product?.description" class="tw-text-sm tw-text-gray-500">
                      {{ slotProps.data.product.description }}
                    </div>
                  </div>
                </div>
              </template>
            </Column>

            <Column field="quantity" header="Quantity" sortable>
              <template #body="slotProps">
                <div class="tw-flex tw-items-center">
                  <span class="tw-font-medium tw-text-lg">{{ slotProps.data.quantity }}</span>
                  <span class="tw-text-sm tw-text-gray-500 tw-ml-1">{{ slotProps.data.product?.unit || 'units' }}</span>
                </div>
              </template>
            </Column>

            <Column field="unit_price" header="Unit Price" sortable>
              <template #body="slotProps">
                <span v-if="slotProps.data.unit_price" class="tw-font-medium tw-text-green-600">
                  ${{ parseFloat(slotProps.data.unit_price).toFixed(2) }}
                </span>
                <span v-else class="tw-text-gray-400 tw-italic">Not set</span>
              </template>
            </Column>

            <Column field="total_price" header="Total Price" sortable>
              <template #body="slotProps">
                <span v-if="slotProps.data.unit_price" class="tw-font-bold tw-text-lg tw-text-green-600">
                  ${{ (slotProps.data.quantity * parseFloat(slotProps.data.unit_price)).toFixed(2) }}
                </span>
                <span v-else class="tw-text-gray-400 tw-italic">-</span>
              </template>
            </Column>

            <Column field="status" header="Status" sortable>
              <template #body="slotProps">
                <Tag 
                  :value="slotProps.data.status || 'pending'" 
                  :severity="getStatusSeverity(slotProps.data.status || 'pending')"
                  class="tw-font-medium"
                />
              </template>
            </Column>

            <Column v-if="demand.status === 'draft'" header="Actions" :exportable="false">
              <template #body="slotProps">
                <div class="tw-flex tw-gap-2">
                  <Button 
                    icon="pi pi-pencil" 
                    class="p-button-rounded p-button-text p-button-sm tw-text-orange-600"
                    v-tooltip.top="'Edit Item'"
                    @click="editItem(slotProps.data)"
                  />
                  <Button 
                    icon="pi pi-trash" 
                    class="p-button-rounded p-button-text p-button-sm tw-text-red-600"
                    v-tooltip.top="'Remove Item'"
                    @click="removeItem(slotProps.data)"
                  />
                </div>
              </template>
            </Column>
          </DataTable>
        </template>
      </Card>

      <!-- Activity Timeline (if available) -->
      <Card v-if="demand.activity_logs?.length > 0" class="tw-mt-6 tw-shadow-lg">
        <template #title>
          <div class="tw-flex tw-items-center">
            <i class="pi pi-clock tw-mr-3 tw-text-indigo-600"></i>
            <span>Activity Timeline</span>
          </div>
        </template>
        <template #content>
          <Timeline :value="demand.activity_logs" align="left" class="tw-mt-4">
            <template #content="slotProps">
              <div class="tw-bg-white tw-p-4 tw-rounded-lg tw-shadow-sm tw-border-l-4 tw-border-blue-500">
                <h4 class="tw-font-semibold tw-text-gray-900">{{ slotProps.item.action }}</h4>
                <p class="tw-text-gray-600 tw-text-sm tw-mt-1">{{ slotProps.item.description }}</p>
                <small class="tw-text-gray-500">{{ formatDateTime(slotProps.item.created_at) }}</small>
              </div>
            </template>
            <template #marker="slotProps">
              <span class="tw-flex tw-w-6 tw-h-6 tw-bg-blue-500 tw-text-white tw-rounded-full tw-text-xs tw-items-center tw-justify-center">
                <i :class="getActivityIcon(slotProps.item.action)"></i>
              </span>
            </template>
          </Timeline>
        </template>
      </Card>
    </div>

    <!-- Edit Demand Dialog -->
    <Dialog :visible="showEditDialog" @update:visible="showEditDialog = $event" modal header="Edit Demand" :style="{width: '50rem'}">
      <div v-if="demand" class="tw-space-y-6">
        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
          <div class="tw-flex tw-flex-col">
            <label class="tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Service *</label>
            <Dropdown
              v-model="demand.service_id"
              :options="services"
              optionLabel="service_name"
              optionValue="id"
              placeholder="Select a service"
              class="tw-w-full"
              :loading="loadingServices"
            />
          </div>

          <div class="tw-flex tw-flex-col">
            <label class="tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Expected Date</label>
            <Calendar
              v-model="demand.expected_date"
              placeholder="Select expected delivery date"
              class="tw-w-full"
              :minDate="minDate"
              showIcon
              dateFormat="yy-mm-dd"
            />
          </div>
        </div>

        <div class="tw-flex tw-flex-col">
          <label class="tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Notes</label>
          <Textarea
            v-model="demand.notes"
            rows="3"
            placeholder="Additional notes or requirements..."
            class="tw-w-full"
          />
        </div>
      </div>

      <template #footer>
        <div class="tw-flex tw-justify-end tw-gap-3">
          <Button 
            label="Cancel" 
            icon="pi pi-times" 
            class="p-button-text" 
            @click="showEditDialog = false"
          />
          <Button 
            label="Save Changes" 
            icon="pi pi-save" 
            :loading="savingDemand"
            @click="saveDemand"
          />
        </div>
      </template>
    </Dialog>

    <!-- Add Item Dialog -->
    <Dialog :visible="showAddItemDialog" @update:visible="showAddItemDialog = $event" modal header="Add Item to Demand" :style="{width: '50rem'}">
      <div class="tw-space-y-6">
        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
          <div class="tw-flex tw-flex-col">
            <label class="tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Product *</label>
            <Dropdown
              v-model="newItem.product_id"
              :options="availableProducts"
              optionLabel="name"
              optionValue="id"
              placeholder="Select a product"
              class="tw-w-full"
              :class="{ 'p-invalid': !newItem.product_id }"
              :loading="loadingProducts"
              filter
              filterPlaceholder="Search products..."
            >
              <template #option="slotProps">
                <div class="tw-flex tw-items-center">
                  <i class="pi pi-box tw-mr-2 tw-text-purple-500"></i>
                  <div>
                    <div class="tw-font-medium">{{ slotProps.option.name }}</div>
                    <div class="tw-text-sm tw-text-gray-500">{{ slotProps.option.product_code }}</div>
                  </div>
                </div>
              </template>
            </Dropdown>
          </div>

          <div class="tw-flex tw-flex-col">
            <label class="tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Quantity *</label>
            <InputNumber
              v-model="newItem.quantity"
              placeholder="Enter quantity"
              class="tw-w-full"
              :class="{ 'p-invalid': !newItem.quantity || newItem.quantity <= 0 }"
              :min="1"
              showButtons
            />
          </div>
        </div>

        <div class="tw-flex tw-flex-col">
          <label class="tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Unit Price</label>
          <InputNumber
            v-model="newItem.unit_price"
            placeholder="Enter unit price"
            class="tw-w-full"
            mode="currency"
            currency="USD"
            :min="0"
          />
        </div>

        <div class="tw-flex tw-flex-col">
          <label class="tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Notes</label>
          <Textarea
            v-model="newItem.notes"
            rows="2"
            placeholder="Additional notes for this item..."
            class="tw-w-full"
          />
        </div>
      </div>

      <template #footer>
        <div class="tw-flex tw-justify-end tw-gap-3">
          <Button 
            label="Cancel" 
            icon="pi pi-times" 
            class="p-button-text" 
            @click="closeAddItemDialog"
          />
          <Button 
            label="Add Item" 
            icon="pi pi-plus" 
            :loading="addingItem"
            :disabled="!newItem.product_id || !newItem.quantity || newItem.quantity <= 0"
            @click="addItem"
          />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<script>
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';
import Calendar from 'primevue/calendar';
import Textarea from 'primevue/textarea';
import Tag from 'primevue/tag';
import Card from 'primevue/card';
import Timeline from 'primevue/timeline';
import Toast from 'primevue/toast';

export default {
  name: 'ServiceDemandView',
  components: {
    DataTable,
    Column,
    Dialog,
    Button,
    InputNumber,
    Dropdown,
    Calendar,
    Textarea,
    Tag,
    Card,
    Timeline,
    Toast
  },
  props: {
    demandId: {
      type: [String, Number],
      required: true
    }
  },
  data() {
    return {
      demand: null,
      services: [],
      availableProducts: [],
      loading: false,
      loadingServices: false,
      loadingProducts: false,
      savingDemand: false,
      sendingDemand: false,
      addingItem: false,
      error: null,

      showEditDialog: false,
      showAddItemDialog: false,

      newItem: {
        product_id: null,
        quantity: 1,
        unit_price: null,
        notes: ''
      },

      minDate: new Date()
    };
  },
  mounted() {
    this.toast = useToast();
    this.confirm = useConfirm();
    this.loadDemandDetails();
    this.loadServices();
    this.loadProducts();
  },
  computed: {
    totalQuantity() {
      if (!this.demand?.items) return 0;
      return this.demand.items.reduce((sum, item) => sum + (item.quantity || 0), 0);
    },

    totalValue() {
      if (!this.demand?.items) return 0;
      return this.demand.items.reduce((sum, item) => {
        const price = parseFloat(item.unit_price) || 0;
        const quantity = item.quantity || 0;
        return sum + (price * quantity);
      }, 0);
    }
  },
  methods: {
    async loadDemandDetails() {
      this.loading = true;
      this.error = null;
      try {
        const response = await axios.get(`/api/service-demands/${this.demandId}`);
        this.demand = response.data.data;
      } catch (error) {
        console.error('Failed to load demand details:', error);
        this.error = error.response?.data?.message || 'Failed to load demand details';
      } finally {
        this.loading = false;
      }
    },

    async loadServices() {
      this.loadingServices = true;
      try {
        const response = await axios.get('/api/service-demands/meta/services');
        this.services = response.data.data || [];
      } catch (error) {
        console.error('Failed to load services:', error);
      } finally {
        this.loadingServices = false;
      }
    },

    async loadProducts() {
      this.loadingProducts = true;
      try {
        const response = await axios.get('/api/service-demands/meta/products');
        this.availableProducts = response.data.data || [];
      } catch (error) {
        console.error('Failed to load products:', error);
      } finally {
        this.loadingProducts = false;
      }
    },

    async saveDemand() {
      this.savingDemand = true;
      try {
        await axios.put(`/api/service-demands/${this.demand.id}`, {
          service_id: this.demand.service_id,
          expected_date: this.demand.expected_date,
          notes: this.demand.notes
        });
        this.toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Demand updated successfully',
          life: 3000
        });
        this.showEditDialog = false;
        this.loadDemandDetails();
      } catch (error) {
        console.error('Error saving demand:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to save demand',
          life: 3000
        });
      } finally {
        this.savingDemand = false;
      }
    },

    async sendDemand() {
      this.confirm.require({
        message: `Are you sure you want to send demand "${this.demand.demand_code}"? Once sent, you cannot edit it anymore.`,
        header: 'Send Demand',
        icon: 'pi pi-exclamation-triangle',
        rejectClass: 'p-button-text',
        acceptClass: 'p-button-danger',
        accept: async () => {
          this.sendingDemand = true;
          try {
            await axios.post(`/api/service-demands/${this.demand.id}/send`);
            this.toast.add({
              severity: 'success',
              summary: 'Success',
              detail: 'Demand sent successfully',
              life: 3000
            });
            this.loadDemandDetails();
          } catch (error) {
            console.error('Error sending demand:', error);
            this.toast.add({
              severity: 'error',
              summary: 'Error',
              detail: error.response?.data?.message || 'Failed to send demand',
              life: 3000
            });
          } finally {
            this.sendingDemand = false;
          }
        }
      });
    },

    async deleteDemand() {
      this.confirm.require({
        message: `Are you sure you want to delete demand "${this.demand.demand_code}"? This action cannot be undone.`,
        header: 'Delete Demand',
        icon: 'pi pi-exclamation-triangle',
        rejectClass: 'p-button-text',
        acceptClass: 'p-button-danger',
        accept: async () => {
          try {
            await axios.delete(`/api/service-demands/${this.demand.id}`);
            this.toast.add({
              severity: 'success',
              summary: 'Success',
              detail: 'Demand deleted successfully',
              life: 3000
            });
            this.$router.push({ name: 'service-demand-management' });
          } catch (error) {
            console.error('Error deleting demand:', error);
            this.toast.add({
              severity: 'error',
              summary: 'Error',
              detail: 'Failed to delete demand',
              life: 3000
            });
          }
        }
      });
    },

    async addItem() {
      this.addingItem = true;
      try {
        const response = await axios.post(`/api/service-demands/${this.demand.id}/items`, this.newItem);
        this.demand.items.push(response.data.data);
        this.toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Item added successfully',
          life: 3000
        });
        this.closeAddItemDialog();
      } catch (error) {
        console.error('Error adding item:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error.response?.data?.message || 'Failed to add item',
          life: 3000
        });
      } finally {
        this.addingItem = false;
      }
    },

    async removeItem(item) {
      this.confirm.require({
        message: `Are you sure you want to remove "${item.product?.name}" from this demand?`,
        header: 'Remove Item',
        icon: 'pi pi-exclamation-triangle',
        rejectClass: 'p-button-text',
        acceptClass: 'p-button-danger',
        accept: async () => {
          try {
            await axios.delete(`/api/service-demands/${this.demand.id}/items/${item.id}`);
            const index = this.demand.items.findIndex(i => i.id === item.id);
            if (index !== -1) {
              this.demand.items.splice(index, 1);
            }
            this.toast.add({
              severity: 'success',
              summary: 'Success',
              detail: 'Item removed successfully',
              life: 3000
            });
          } catch (error) {
            console.error('Error removing item:', error);
            this.toast.add({
              severity: 'error',
              summary: 'Error',
              detail: 'Failed to remove item',
              life: 3000
            });
          }
        }
      });
    },

    editItem(item) {
      // Implement edit item functionality
      this.toast.add({
        severity: 'info',
        summary: 'Info',
        detail: 'Edit item functionality coming soon',
        life: 3000
      });
    },

    closeAddItemDialog() {
      this.showAddItemDialog = false;
      this.resetNewItem();
    },

    resetNewItem() {
      this.newItem = {
        product_id: null,
        quantity: 1,
        unit_price: null,
        notes: ''
      };
    },

    printDemand() {
      window.print();
    },

    exportPDF() {
      this.toast.add({
        severity: 'info',
        summary: 'Info',
        detail: 'PDF export functionality coming soon',
        life: 3000
      });
    },

    getStatusSeverity(status) {
      const severities = {
        draft: 'warning',
        sent: 'info',
        approved: 'success',
        rejected: 'danger',
        completed: 'success',
        pending: 'warning'
      };
      return severities[status] || 'info';
    },

    getActivityIcon(action) {
      const icons = {
        created: 'pi pi-plus',
        updated: 'pi pi-pencil',
        sent: 'pi pi-send',
        approved: 'pi pi-check',
        rejected: 'pi pi-times',
        deleted: 'pi pi-trash'
      };
      return icons[action] || 'pi pi-info-circle';
    },

    formatDate(date) {
      if (!date) return 'Not set';
      return new Date(date).toLocaleDateString();
    },

    formatDateTime(date) {
      if (!date) return 'Not set';
      return new Date(date).toLocaleString();
    }
  }
};
</script>

<style scoped>
.tw-shadow-xl {
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.p-datatable .p-datatable-tbody > tr:hover {
  background-color: rgba(59, 130, 246, 0.05);
}

.p-button {
  transition: all 0.2s ease-in-out;
}

.p-button:hover {
  transform: translateY(-1px);
}

.p-tag {
  font-weight: 500;
  text-transform: capitalize;
}

/* Print styles */
@media print {
  .p-button,
  .tw-gap-3,
  .tw-mb-6 {
    display: none !important;
  }
  
  .tw-bg-gradient-to-br {
    background: white !important;
  }
  
  .tw-text-white {
    color: black !important;
  }
}

/* Timeline customization */
.p-timeline .p-timeline-event-content {
  background: transparent;
}

.p-timeline .p-timeline-event-marker {
  border: 2px solid #3b82f6;
  background: #3b82f6;
}

/* Responsive design */
@media (max-width: 768px) {
  .tw-grid-cols-2 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }
}
</style>