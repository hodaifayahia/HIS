<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-blue-50 tw-via-indigo-50 tw-to-purple-50 tw-p-6">
    <Toast position="top-right" />

    <!-- Header -->
    <Card class="tw-mb-8 tw-bg-gradient-to-r tw-from-blue-600 tw-to-indigo-700 tw-text-white tw-shadow-xl">
      <template #content>
        <div class="tw-flex tw-justify-between tw-items-center">
          <div>
            <h1 class="tw-text-4xl tw-font-bold tw-mb-2">Stock Movement Management</h1>
            <p class="tw-text-blue-100">Request and manage stock transfers between services</p>
          </div>
          <div class="tw-flex tw-space-x-3">
            <Button
              :label="activeTab === 'sender' ? 'Switch to Receiver View' : 'Switch to Sender View'"
              icon="pi pi-refresh"
              class="tw-bg-white tw-text-blue-600 hover:tw-bg-blue-50"
              @click="switchView"
            />
            <Button
              v-if="activeTab === 'sender'"
              label="New Request"
              icon="pi pi-plus"
              class="tw-bg-green-500 hover:tw-bg-green-600"
              @click="showNewRequestDialog = true"
            />
          </div>
        </div>
      </template>
    </Card>

    <!-- Tab Navigation -->
    <div class="tw-mb-6">
      <TabView v-model:activeIndex="activeTabIndex" @tab-change="onTabChange">
        <TabPanel header="My Requests" v-if="activeTab === 'sender'">
          <div class="tw-text-sm tw-text-gray-600">
            View and manage your stock transfer requests
          </div>
        </TabPanel>
        <TabPanel header="Pending Approvals" v-if="activeTab === 'receiver'">
          <div class="tw-text-sm tw-text-gray-600">
            Requests from other services waiting for your approval
          </div>
        </TabPanel>
      </TabView>
    </div>

    <!-- Stats Cards -->
    <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-6 tw-mb-8">
      <Card class="tw-shadow-lg" v-if="activeTab === 'sender'">
        <template #content>
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <p class="tw-text-sm tw-text-gray-600">My Drafts</p>
              <p class="tw-text-3xl tw-font-bold tw-text-blue-600">{{ stats.draft || 0 }}</p>
            </div>
            <i class="pi pi-file tw-text-3xl tw-text-blue-600"></i>
          </div>
        </template>
      </Card>

      <Card class="tw-shadow-lg" v-if="activeTab === 'sender'">
        <template #content>
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <p class="tw-text-sm tw-text-gray-600">Pending Approval</p>
              <p class="tw-text-3xl tw-font-bold tw-text-orange-600">{{ stats.requesting_pending || 0 }}</p>
            </div>
            <i class="pi pi-clock tw-text-3xl tw-text-orange-600"></i>
          </div>
        </template>
      </Card>

      <Card class="tw-shadow-lg" v-if="activeTab === 'receiver'">
        <template #content>
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <p class="tw-text-sm tw-text-gray-600">To Approve</p>
              <p class="tw-text-3xl tw-font-bold tw-text-orange-600">{{ stats.providing_pending || 0 }}</p>
            </div>
            <i class="pi pi-exclamation-triangle tw-text-3xl tw-text-orange-600"></i>
          </div>
        </template>
      </Card>

      <Card class="tw-shadow-lg">
        <template #content>
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <p class="tw-text-sm tw-text-gray-600">Completed</p>
              <p class="tw-text-3xl tw-font-bold tw-text-green-600">{{ stats.executed || 0 }}</p>
            </div>
              <i class="pi pi-check-circle tw-text-3xl tw-text-green-600"></i>
          </div>
        </template>
      </Card>
    </div>

    <!-- Main Content -->
    <div v-if="activeTab === 'sender'">
      <!-- Sender's View: My Requests -->
      <Card class="tw-shadow-xl">
        <template #content>
          <DataTable
            :value="filteredMovements"
            :loading="loading"
            paginator
            :rows="10"
            :rowsPerPageOptions="[5, 10, 25, 50]"
            tableStyle="min-width: 50rem"
            filterDisplay="row"
            :globalFilterFields="['movement_number', 'providing_service.name', 'requesting_service.name', 'requesting_user.name', 'status']"
            removableSort
          >
            <template #header>
              <div class="tw-flex tw-justify-between tw-items-center">
                <h3 class="tw-text-xl tw-font-semibold">My Requests</h3>
                <span class="p-input-icon-left">
                  <i class="pi pi-search" />
                  <InputText v-model="filters['global'].value" placeholder="Search requests..." />
                </span>
              </div>
            </template>

            <Column field="movement_number" header="Request #" sortable style="min-width:12rem">
              <template #body="slotProps">
                <span class="tw-font-mono tw-text-sm">{{ slotProps.data.movement_number }}</span>
              </template>
            </Column>

            <Column header="Products">
              <template #body="slotProps">
                <div class="tw-space-y-1">
                  <div v-for="item in slotProps.data.items" :key="item.id" class="tw-flex tw-justify-between tw-text-sm">
                    <span>{{ item.product?.name || 'Product not loaded' }}</span>
                    <span class="tw-font-medium">{{ item.requested_quantity }}</span>
                  </div>
                  <div v-if="slotProps.data.items.length === 0" class="tw-text-gray-500 tw-text-sm">
                    No products added
                  </div>
                </div>
              </template>
            </Column>

            <Column header="Requesting Service" style="min-width:15rem">
              <template #body="slotProps">
                <div class="tw-flex tw-items-center tw-space-x-3">
                  <div class="tw-w-8 tw-h-8 tw-bg-green-100 tw-rounded-full tw-flex tw-items-center tw-justify-center">
                    <i class="pi pi-building tw-text-green-600"></i>
                  </div>
                  <div>
                    <p class="tw-font-medium">{{ slotProps.data.requesting_service?.name }}</p>
                    
                  </div>
                </div>
              </template>
            </Column>

            <Column header="Providing Service" style="min-width:15rem">
              <template #body="slotProps">
                <div class="tw-flex tw-items-center tw-space-x-3">
                  <div class="tw-w-8 tw-h-8 tw-bg-blue-100 tw-rounded-full tw-flex tw-items-center tw-justify-center">
                    <i class="pi pi-building tw-text-blue-600"></i>
                  </div>
                  <div>
                    <p class="tw-font-medium">{{ slotProps.data?.providing_service?.name }}</p>
                   
                  </div>
                </div>
              </template>
            </Column>

            <Column header="Requested By" style="min-width:10rem">
              <template #body="slotProps">
                <div class="tw-flex tw-items-center tw-space-x-3">
                  <div class="tw-w-8 tw-h-8 tw-bg-purple-100 tw-rounded-full tw-flex tw-items-center tw-justify-center">
                    <i class="pi pi-user tw-text-purple-600"></i>
                  </div>
                  <div>
                    <p class="tw-font-medium">{{ slotProps.data?.requesting_user?.name }}</p>
                    <p class="tw-text-sm tw-text-gray-500">{{ slotProps.data?.requesting_user?.email }}</p>
                  </div>
                </div>
              </template>
            </Column>

            <Column field="status" header="Status" sortable >
              <template #body="slotProps">
                <Tag :value="slotProps.data.status" :severity="getStatusSeverity(slotProps.data.status)" />
              </template>
            </Column>

            <Column field="updated_at" header="Last Updated" sortable style="min-width:12rem">
              <template #body="slotProps">
                <div>
                  <p class="tw-font-medium">{{ formatDate(slotProps.data.updated_at) }}</p>
                  <p class="tw-text-sm tw-text-gray-500">{{ formatTime(slotProps.data.updated_at) }}</p>
                </div>
              </template>
            </Column>

            <Column header="Actions" style="min-width:15rem">
              <template #body="slotProps">
                <div class="tw-flex tw-space-x-2">
             

                  <Button
                    v-if="slotProps.data.status === 'draft'"
                    icon="pi pi-pencil"
                    class="p-button-rounded p-button-warning p-button-text"
                    @click="editDraft(slotProps.data)"
                    v-tooltip="'Edit Draft'"
                  />

                  <Button
                    v-if="slotProps.data.status === 'draft' && slotProps.data.items.length > 0"
                    icon="pi pi-send"
                    class="p-button-rounded p-button-success p-button-text"
                    @click="sendDraft(slotProps.data)"
                    v-tooltip="'Send Request'"
                  />

                  <Button
                    v-if="slotProps.data.status === 'draft'"
                    icon="pi pi-trash"
                    class="p-button-rounded p-button-danger p-button-text"
                    @click="deleteDraft(slotProps.data)"
                    v-tooltip="'Delete Draft'"
                  />

                  <Button
                    v-if="slotProps.data.status === 'approved'"
                    icon="pi pi-check-circle"
                    class="p-button-rounded p-button-success p-button-text"
                    v-tooltip="'Approved'"
                    disabled
                  />
                  <Button
                    icon="pi pi-eye"
                    class="p-button-rounded p-button-info p-button-text"
                    @click="viewMovement(slotProps.data)"
                    v-tooltip="'View Details'"
                  />
                </div>
              </template>
            </Column>
          </DataTable>
        </template>
      </Card>
    </div>

    <div v-else-if="activeTab === 'receiver'">
      <Card class="tw-shadow-xl">
        <template #content>
          <DataTable
            :value="pendingApprovals"
            :loading="loading"
            paginator
            :rows="10"
            :rowsPerPageOptions="[5, 10, 25, 50]"
            tableStyle="min-width: 50rem"
            filterDisplay="row"
            :globalFilterFields="['movement_number', 'requestingService.name', 'providingService.name', 'requestingUser.name']"
            removableSort
          >
            <template #header>
              <div class="tw-flex tw-justify-between tw-items-center">
                <h3 class="tw-text-xl tw-font-semibold">Pending Approvals</h3>
                <span class="p-input-icon-left">
                  <i class="pi pi-search" />
                  <InputText v-model="filters['global'].value" placeholder="Search requests..." />
                </span>
              </div>
            </template>

            <Column field="movement_number" header="Request #" sortable style="min-width:12rem">
              <template #body="slotProps">
                <span class="tw-font-mono tw-text-sm">{{ slotProps.data.movement_number }}</span>
              </template>
            </Column>

            <Column header="Products" style="min-width:20rem">
              <template #body="slotProps">
                <div class="tw-space-y-1">
                  <div v-for="item in slotProps.data.items" :key="item.id" class="tw-flex tw-justify-between tw-text-sm">
                    <span>{{ item.product?.name }}</span>
                    <span class="tw-font-medium">{{ item.requested_quantity }}</span>
                  </div>
                </div>
              </template>
            </Column>

            <Column header="Requesting Service" style="min-width:15rem">
              <template #body="slotProps">
                <div class="tw-flex tw-items-center tw-space-x-3">
                  <div class="tw-w-8 tw-h-8 tw-bg-green-100 tw-rounded-full tw-flex tw-items-center tw-justify-center">
                    <i class="pi pi-building tw-text-green-600"></i>
                  </div>
                  <div>
                    <p class="tw-font-medium">{{ slotProps.data.requesting_service?.name }}</p>
                    <p class="tw-text-sm tw-text-gray-500">by {{ slotProps.data.requesting_user?.name }}</p>
                  </div>
                </div>
              </template>
            </Column>

            <Column header="Providing Service" style="min-width:15rem">
              <template #body="slotProps">
                <div class="tw-flex tw-items-center tw-space-x-3">
                  <div class="tw-w-8 tw-h-8 tw-bg-blue-100 tw-rounded-full tw-flex tw-items-center tw-justify-center">
                    <i class="pi pi-building tw-text-blue-600"></i>
                  </div>
                  <div>
                    <p class="tw-font-medium">{{ slotProps.data.providing_service?.name }}</p>
                    <p class="tw-text-sm tw-text-gray-500">{{ slotProps.data.providing_service?.type }}</p>
                  </div>
                </div>
              </template>
            </Column>

            <Column field="request_reason" header="Reason" style="min-width:15rem">
              <template #body="slotProps">
                <p class="tw-text-sm tw-line-clamp-2">{{ slotProps.data.request_reason }}</p>
              </template>
            </Column>

            <Column field="created_at" header="Requested" sortable style="min-width:12rem">
              <template #body="slotProps">
                <div>
                  <p class="tw-font-medium">{{ formatDate(slotProps.data.created_at) }}</p>
                  <p class="tw-text-sm tw-text-gray-500">{{ formatTime(slotProps.data.created_at) }}</p>
                </div>
              </template>
            </Column>

            <Column header="Actions" style="min-width:15rem">
              <template #body="slotProps">
                <div class="tw-flex tw-space-x-2">
                  <Button
                    icon="pi pi-eye"
                    class="p-button-rounded p-button-info p-button-text"
                    @click="viewMovement(slotProps.data)"
                    v-tooltip="'View Details'"
                  />
                </div>
              </template>
            </Column>
          </DataTable>
        </template>
      </Card>
    </div>

    <!-- New Request Dialog -->
    <Dialog v-model:visible="showNewRequestDialog" modal header="Create New Stock Request" :style="{width: '60rem'}">
      <div class="tw-space-y-6">
        <div class="tw-grid tw-grid-cols-2 tw-gap-6">
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-mb-2">Select Service to Request From *</label>
            <Dropdown
              v-model="newRequest.providing_service_id"
              :options="services"
              optionLabel="name"
              optionValue="id"
              placeholder="Choose service"
              class="tw-w-full"
              :loading="loadingServices"
            />
          </div>

          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-mb-2">Expected Delivery Date</label>
            <Calendar
              v-model="newRequest.expected_delivery_date"
              dateFormat="yy-mm-dd"
              class="tw-w-full"
              :minDate="minDate"
              placeholder="Optional"
            />
          </div>
        </div>

        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-mb-2">Request Reason *</label>
          <Textarea
            v-model="newRequest.request_reason"
            rows="3"
            class="tw-w-full"
            placeholder="Please explain why you need these products..."
          />
        </div>

        <div class="tw-flex tw-justify-end tw-space-x-3">
          <Button
            type="button"
            label="Cancel"
            icon="pi pi-times"
            class="p-button-text"
            @click="closeNewRequestDialog"
          />
          <Button
            type="button"
            label="Create Draft"
            icon="pi pi-save"
            class="tw-bg-blue-600"
            :loading="creatingDraft"
            @click="createDraft"
          />
        </div>
      </div>
    </Dialog>

    <!-- Edit Draft Dialog -->
    <Dialog v-model:visible="showEditDialog" modal :header="`Edit Draft: ${selectedMovement?.movement_number}`" :style="{width: '70rem'}">
      <div class="tw-space-y-6">
        <!-- Request Items -->
        <div class="tw-bg-gray-50 tw-p-4 tw-rounded-lg">
          <div class="tw-flex tw-justify-between tw-items-center tw-mb-4">
            <h4 class="tw-font-medium">Requested Products</h4>
            <Button
              label="Add Product"
              icon="pi pi-plus"
              size="small"
              @click="showAddProductDialog = true"
            />
          </div>

          <div v-if="selectedMovement?.items?.length === 0" class="tw-text-center tw-py-8 tw-text-gray-500">
            <i class="pi pi-box tw-text-3xl tw-mb-2"></i>
            <p>No products added yet</p>
          </div>

          <div v-else class="tw-space-y-3">
            <div v-for="item in selectedMovement.items" :key="item.id" class="tw-flex tw-items-center tw-justify-between tw-bg-white tw-p-3 tw-rounded">
              <div class="tw-flex tw-items-center tw-space-x-3">
                <div class="tw-w-10 tw-h-10 tw-bg-blue-100 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                  <i :class="getProductIcon(item.product)" class="tw-text-blue-600"></i>
                </div>
                <div>
                  <p class="tw-font-medium">{{ item.product.name }}</p>
                  <p class="tw-text-sm tw-text-gray-500">{{ item.product.category }}</p>
                </div>
              </div>
              <div class="tw-flex tw-items-center tw-space-x-3">
                <span class="tw-font-medium">Qty: {{ item.requested_quantity }}</span>
                <Button
                  icon="pi pi-trash"
                  class="p-button-rounded p-button-danger p-button-text p-button-sm"
                  @click="removeItem(item)"
                  v-tooltip="'Remove item'"
                />
              </div>
            </div>
          </div>
        </div>

        <div class="tw-flex tw-justify-end tw-space-x-3">
          <Button
            type="button"
            label="Cancel"
            icon="pi pi-times"
            class="p-button-text"
            @click="closeEditDialog"
          />
          <Button
            type="button"
            label="Save Changes"
            icon="pi pi-save"
            class="tw-bg-blue-600"
            :loading="savingDraft"
            @click="saveDraft"
          />
          <Button
            v-if="selectedMovement?.items?.length > 0"
            type="button"
            label="Send Request"
            icon="pi pi-send"
            class="tw-bg-green-600"
            :loading="sendingDraft"
            @click="confirmSendDraft"
          />
        </div>
      </div>
    </Dialog>

    <!-- Add Product Dialog -->
    <Dialog v-model:visible="showAddProductDialog" modal header="Add Product to Request" :style="{width: '50rem'}">
      <div class="tw-space-y-6">
        <div class="tw-grid tw-grid-cols-2 tw-gap-6">
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-mb-2">Select Product *</label>
            <Dropdown
              v-model="newItem.product_id"
              :options="availableProducts"
              optionLabel="name"
              optionValue="id"
              placeholder="Choose product"
              class="tw-w-full"
              :loading="loadingProducts"
              @change="onProductChange"
            />
          </div>

          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-mb-2">Requested Quantity *</label>
            <InputNumber
              v-model="newItem.requested_quantity"
              :min="0.01"
              :max="maxQuantity"
              class="tw-w-full"
              mode="decimal"
              :minFractionDigits="2"
              :maxFractionDigits="2"
            />
            <p v-if="maxQuantity" class="tw-text-xs tw-text-gray-500 tw-mt-1">
              Max available: {{ maxQuantity }}
            </p>
          </div>
        </div>

        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-mb-2">Notes (Optional)</label>
          <Textarea
            v-model="newItem.notes"
            rows="2"
            class="tw-w-full"
            placeholder="Any special notes about this product..."
          />
        </div>

        <div class="tw-flex tw-justify-end tw-space-x-3">
          <Button
            type="button"
            label="Cancel"
            icon="pi pi-times"
            class="p-button-text"
            @click="showAddProductDialog = false"
          />
          <Button
            type="button"
            label="Add Product"
            icon="pi pi-plus"
            class="tw-bg-blue-600"
            :loading="addingItem"
            @click="addProductToDraft"
          />
        </div>
      </div>
    </Dialog>

    <!-- Movement Details Dialog -->
    <Dialog v-model:visible="showDetailsDialog" modal :header="`Request Details: ${selectedMovement?.movement_number}`" :style="{width: '70rem'}">
      <div v-if="selectedMovement" class="tw-space-y-6">
        <!-- Request Info -->
        <div class="tw-grid tw-grid-cols-2 tw-gap-6">
          <div class="tw-bg-blue-50 tw-p-4 tw-rounded-lg">
            <h4 class="tw-font-medium tw-mb-2">Request Information</h4>
            <p><strong>Requesting:</strong> {{ selectedMovement.requesting_service?.name }}</p>
            <p><strong>Providing:</strong> {{ selectedMovement.providing_service?.name }}</p>
            <p><strong>Status:</strong> <Tag :value="selectedMovement.status" :severity="getStatusSeverity(selectedMovement.status)" /></p>
            <p><strong>Requested:</strong> {{ formatDate(selectedMovement.created_at) }}</p>
          </div>

          <div class="tw-bg-green-50 tw-p-4 tw-rounded-lg">
            <h4 class="tw-font-medium tw-mb-2">Request Details</h4>
            <p><strong>Reason:</strong> {{ selectedMovement.request_reason }}</p>
            <p v-if="selectedMovement.expected_delivery_date"><strong>Expected:</strong> {{ formatDate(selectedMovement.expected_delivery_date) }}</p>
    
          </div>
        </div>

        <!-- Products List -->
        <div>
          <h4 class="tw-font-medium tw-mb-3">Requested Products</h4>
          <div class="tw-space-y-3">
            <div v-for="item in selectedMovement.items" :key="item.id" class="tw-flex tw-items-center tw-justify-between tw-bg-gray-50 tw-p-3 tw-rounded">
              <div class="tw-flex tw-items-center tw-space-x-3">
                <div class="tw-w-10 tw-h-10 tw-bg-blue-100 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                  <i :class="getProductIcon(item.product)" class="tw-text-blue-600"></i>
                </div>
                <div>
                  <p class="tw-font-medium">{{ item.product.name }}</p>
                  <p class="tw-text-sm tw-text-gray-500">{{ item.product.category }}</p>
                </div>
              </div>
              <div class="tw-text-right">
                <p class="tw-font-medium">Requested: {{ item.requested_quantity }}</p>
      
                <p v-if="item.executed_quantity" class="tw-text-blue-600">Executed: {{ item.executed_quantity }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="tw-flex tw-justify-end tw-space-x-3">
          <Button
            type="button"
            label="Close"
            icon="pi pi-times"
            class="p-button-text"
            @click="showDetailsDialog = false"
          />


        </div>
      </div>
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
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';
import Calendar from 'primevue/calendar';
import Textarea from 'primevue/textarea';
import Tag from 'primevue/tag';
import Card from 'primevue/card';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import RadioButton from 'primevue/radiobutton';
import Toast from 'primevue/toast';

export default {
  name: 'StockMovement',
  components: {
    DataTable,
    Column,
    Dialog,
    Button,
    InputText,
    InputNumber,
    Dropdown,
    Calendar,
    Textarea,
    Tag,
    Card,
    TabView,
    TabPanel,
    RadioButton,
    Toast
  },
  data() {
    return {
      activeTab: 'sender', // 'sender' or 'receiver'
      activeTabIndex: 0,
      movements: [],
      pendingApprovals: [],
      services: [],
      availableProducts: [],
      stats: {},
      loading: false,
      loadingServices: false,
      loadingProducts: false,
      creatingDraft: false,
      savingDraft: false,
      sendingDraft: false,
      addingItem: false,

      showNewRequestDialog: false,
      showEditDialog: false,
      showAddProductDialog: false,
      showDetailsDialog: false,

      selectedMovement: null,
      maxQuantity: null,
      filters: {
        global: { value: null, matchMode: 'contains' }
      },
      newRequest: {
        providing_service_id: null,
        request_reason: '',
        expected_delivery_date: null
      },
      newItem: {
        product_id: null,
        requested_quantity: null,
        notes: ''
      },

      minDate: new Date()
    };
  },
  mounted() {
    this.toast = useToast();
    this.confirm = useConfirm();
    this.loadInitialData();
  },
  computed: {
    filteredMovements() {
      return this.movements;
    }
  },
  methods: {
    async loadInitialData() {
      this.loading = true;
      try {
        await Promise.all([
          this.loadServices(),
          this.loadStats(),
          this.loadMovements()
        ]);
      } catch (error) {
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to load data'
        });
      } finally {
        this.loading = false;
      }
    },

    async loadMovements() {
      try {
        if (this.activeTab === 'sender') {
          const response = await axios.get('/api/stock-movements/?role=requester');
          // Handle direct array response or nested data structure
          this.movements = Array.isArray(response.data) ? response.data : (response.data.data?.data || response.data.data || []);
          console.log('Loaded movements for sender:', this.movements);
          
        } else {
          const response = await axios.get('/api/stock-movements/?role=provider&status=pending');
          // Handle direct array response or nested data structure
          this.pendingApprovals = Array.isArray(response.data) ? response.data : (response.data.data?.data || response.data.data || []);
          console.log('Loaded pending approvals for receiver:', this.pendingApprovals);
        }
      } catch (error) {
        console.error('Failed to load movements:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to load stock movements'
        });
      }
    },

    async loadServices() {
      this.loadingServices = true;
      try {
        const response = await axios.get('/api/services?per_page=1000');
        this.services = response.data.data || [];
      } catch (error) {
        console.error('Failed to load services:', error);
      } finally {
        this.loadingServices = false;
      }
    },

    async loadStats() {
      try {
        const response = await axios.get('/api/stock-movements/stats');
        this.stats = response.data.data || {};
      } catch (error) {
        console.error('Failed to load stats:', error);
      }
    },

    async loadAvailableProducts() {
      if (!this.selectedMovement?.providing_service_id) return;

      this.loadingProducts = true;
      try {
        // Load products available in the providing service
        const response = await axios.get(`/api/products?service_id=${this.selectedMovement.providing_service_id}`);
        this.availableProducts = response.data.data || [];
      } catch (error) {
        console.error('Failed to load products:', error);
      } finally {
        this.loadingProducts = false;
      }
    },

    switchView() {
      this.activeTab = this.activeTab === 'sender' ? 'receiver' : 'sender';
      this.activeTabIndex = this.activeTab === 'sender' ? 0 : 1;
      this.loadMovements();
    },

    onTabChange(event) {
      this.activeTab = event.index === 0 ? 'sender' : 'receiver';
      this.loadMovements();
    },

    async onProductChange() {
      if (!this.newItem.product_id || !this.selectedMovement?.providing_service_id) return;

      try {
        // Check available quantity for this product
        const response = await axios.get(`/api/stock-movements/${this.selectedMovement.id}/available-stock?product_id=${this.newItem.product_id}`);
        this.maxQuantity = response.data.available || 0;
      } catch (error) {
        this.maxQuantity = null;
      }
    },

    async createDraft() {
      this.creatingDraft = true;
      try {
        const response = await axios.post('/api/stock-movements/create-draft', this.newRequest);
        this.toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Draft created successfully'
        });

        this.closeNewRequestDialog();

        // Redirect to the new management page
        this.$router.push({
          name: 'stock.movements.manage',
          params: { id: response.data.data.id }
        });
      } catch (error) {
        const message = error.response?.data?.error || 'Failed to create draft';
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: message
        });
      } finally {
        this.creatingDraft = false;
      }
    },

    async editDraft(movement) {
      // Redirect to the management page instead of opening edit dialog
      this.$router.push({
        name: 'stock.movements.manage',
        params: { id: movement.id }
      });
    },

    async saveDraft() {
      this.savingDraft = true;
      try {
        // Draft is automatically saved when items are added/removed
        this.toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Draft saved successfully'
        });

        this.closeEditDialog();
        this.loadMovements();
      } catch (error) {
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to save draft'
        });
      } finally {
        this.savingDraft = false;
      }
    },

    async sendDraft(movement) {
      this.confirm.require({
        message: 'Are you sure you want to send this request? It will be submitted for approval.',
        header: 'Send Request',
        icon: 'pi pi-send',
        rejectClass: 'p-button-secondary p-button-outlined',
        acceptClass: 'p-button-success',
        accept: async () => {
          this.sendingDraft = true;
          try {
            await axios.post(`/api/stock-movements/${movement.id}/send`);
            this.toast.add({
              severity: 'success',
              summary: 'Success',
              detail: 'Request sent successfully'
            });

            this.closeEditDialog();
            this.loadMovements();
            this.loadStats();
          } catch (error) {
            const message = error.response?.data?.error || 'Failed to send request';
            this.toast.add({
              severity: 'error',
              summary: 'Error',
              detail: message
            });
          } finally {
            this.sendingDraft = false;
          }
        }
      });
    },

    confirmSendDraft() {
      this.sendDraft(this.selectedMovement);
    },

    async addProductToDraft() {
      this.addingItem = true;
      try {
        await axios.post(`/api/stock-movements/${this.selectedMovement.id}/items`, this.newItem);
        this.toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Product added to request'
        });

        // Refresh the movement data
        const response = await axios.get(`/api/stock-movements/${this.selectedMovement.id}`);
        this.selectedMovement = response.data.data;

        this.showAddProductDialog = false;
        this.resetNewItem();
      } catch (error) {
        const message = error.response?.data?.error || 'Failed to add product';
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: message
        });
      } finally {
        this.addingItem = false;
      }
    },

    async removeItem(item) {
      try {
        await axios.delete(`/api/stock-movements/${this.selectedMovement.id}/items/${item.id}`);
        this.toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Product removed from request'
        });

        // Refresh the movement data
        const response = await axios.get(`/api/stock-movements/${this.selectedMovement.id}`);
        this.selectedMovement = response.data.data;
      } catch (error) {
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to remove product'
        });
      }
    },

    async deleteDraft(movement) {
      this.confirm.require({
        message: 'Are you sure you want to delete this draft? This action cannot be undone.',
        header: 'Delete Draft',
        icon: 'pi pi-exclamation-triangle',
        rejectClass: 'p-button-secondary p-button-outlined',
        acceptClass: 'p-button-danger',
        accept: async () => {
          try {
            await axios.delete(`/api/stock-movements/${movement.id}`);
            this.toast.add({
              severity: 'success',
              summary: 'Success',
              detail: 'Draft deleted successfully'
            });

            this.loadMovements();
            this.loadStats();
          } catch (error) {
            this.toast.add({
              severity: 'error',
              summary: 'Error',
              detail: 'Failed to delete draft'
            });
          }
        }
      });
    },







    viewMovement(movement) {
      this.$router.push({
        name: 'stock.movements.view',
        params: { id: movement.id }
      });
    },

    closeNewRequestDialog() {
      this.showNewRequestDialog = false;
      this.resetNewRequest();
    },

    closeEditDialog() {
      this.showEditDialog = false;
      this.selectedMovement = null;
    },

    resetNewRequest() {
      this.newRequest = {
        providing_service_id: null,
        request_reason: '',
        expected_delivery_date: null
      };
    },

    resetNewItem() {
      this.newItem = {
        product_id: null,
        requested_quantity: null,
        notes: ''
      };
      this.maxQuantity = null;
    },

    getStatusSeverity(status) {
      const severities = {
        'draft': 'secondary',
        'pending': 'warning',
        'approved': 'success',
        'partially_approved': 'info',
        'rejected': 'danger',
        'executed': 'success',
        'partially_executed': 'info',
        'cancelled': 'secondary'
      };
      return severities[status] || 'info';
    },

    getProductIcon(product) {
      if (product?.is_clinical) return 'pi pi-heart';
      return 'pi pi-box';
    },



    formatDate(date) {

      return new Date(date).toLocaleDateString();
    },

    formatTime(date) {
      return new Date(date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
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

.tw-line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  line-clamp: 2;
  overflow: hidden;
}

/* Dialog animations */
.p-dialog {
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Form input enhancements */
.p-inputtext:focus,
.p-textarea:focus,
.p-dropdown:focus {
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
  border-color: rgb(59, 130, 246);
}

/* Responsive design */
@media (max-width: 768px) {
  .tw-grid-cols-4 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .tw-grid-cols-2 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }
}
</style>
