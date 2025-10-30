<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-purple-50 tw-via-white tw-to-pink-50 tw-p-6">
    <!-- Success Toast -->
    <Toast position="top-right" />

    <!-- Confirm Dialog -->
    <ConfirmDialog />

    <!-- Header Section -->
    <div class="tw-mb-8">
      <div class="tw-bg-gradient-to-r tw-from-purple-600 tw-to-pink-700 tw-rounded-2xl tw-shadow-xl tw-p-8 tw-text-white">
        <div class="tw-flex tw-justify-between tw-items-center">
          <div>
            <h1 class="tw-text-4xl tw-font-bold tw-mb-2 tw-flex tw-items-center tw-gap-3">
              <i class="pi pi-bookmark tw-text-5xl"></i>
              Stock Reserves Management
            </h1>
            <p class="tw-text-purple-100 tw-text-lg">Manage and track your stock reservations</p>
          </div>
          <div class="tw-flex tw-items-center tw-gap-4">
            <div class="tw-bg-white/20 tw-backdrop-blur-sm tw-rounded-xl tw-p-4 tw-text-center">
              <div class="tw-text-3xl tw-font-bold">{{ total }}</div>
              <div class="tw-text-sm tw-text-purple-100">Total Reserves</div>
            </div>
            <Button
              label="New Reserve"
              icon="pi pi-plus"
              class="tw-bg-white tw-text-purple-600 tw-border-white tw-rounded-xl tw-px-6 tw-py-3 tw-font-semibold tw-shadow-lg hover:tw-bg-purple-50 tw-transition-all tw-duration-300"
              @click="openAddReserveModal"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Filters and Search Section -->
    <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-p-6 tw-mb-6">
      <div class="tw-flex tw-flex-col lg:tw-flex-row tw-gap-6 tw-items-start lg:tw-items-center tw-justify-between">
        <!-- Search -->
        <div class="tw-flex-1 tw-max-w-md">
          <div class="tw-relative">
            <i class="pi pi-search tw-absolute tw-left-4 tw-top-1/2 tw-transform -tw-translate-y-1/2 tw-text-gray-400"></i>
            <InputText
              v-model="searchQuery"
              placeholder="Search reserves by name..."
              class="tw-w-full tw-pl-12 tw-pr-4 tw-py-3 tw-rounded-xl tw-border-2 tw-border-gray-200 focus:tw-border-purple-500 tw-transition-all"
              @input="onSearchInput"
            />
          </div>
        </div>

        <!-- Status Filter -->
        <div class="tw-flex tw-gap-3">
          <Button
            :label="`All (${total})`"
            :class="[
              'tw-rounded-xl tw-px-6 tw-py-3 tw-font-medium tw-transition-all',
              activeStatusFilter === 'all' 
                ? 'tw-bg-purple-600 tw-text-white tw-shadow-lg' 
                : 'tw-bg-gray-100 tw-text-gray-700 hover:tw-bg-gray-200'
            ]"
            @click="setStatusFilter('all')"
          />
          <Button
            :label="`Pending (${statusCounts.pending || 0})`"
            :class="[
              'tw-rounded-xl tw-px-6 tw-py-3 tw-font-medium tw-transition-all',
              activeStatusFilter === 'pending' 
                ? 'tw-bg-yellow-500 tw-text-white tw-shadow-lg' 
                : 'tw-bg-gray-100 tw-text-gray-700 hover:tw-bg-gray-200'
            ]"
            @click="setStatusFilter('pending')"
          />
          <Button
            :label="`Confirmed (${statusCounts.confirmed || 0})`"
            :class="[
              'tw-rounded-xl tw-px-6 tw-py-3 tw-font-medium tw-transition-all',
              activeStatusFilter === 'confirmed' 
                ? 'tw-bg-blue-500 tw-text-white tw-shadow-lg' 
                : 'tw-bg-gray-100 tw-text-gray-700 hover:tw-bg-gray-200'
            ]"
            @click="setStatusFilter('confirmed')"
          />
          <Button
            :label="`Completed (${statusCounts.completed || 0})`"
            :class="[
              'tw-rounded-xl tw-px-6 tw-py-3 tw-font-medium tw-transition-all',
              activeStatusFilter === 'completed' 
                ? 'tw-bg-green-500 tw-text-white tw-shadow-lg' 
                : 'tw-bg-gray-100 tw-text-gray-700 hover:tw-bg-gray-200'
            ]"
            @click="setStatusFilter('completed')"
          />
          <Button
            :label="`Cancelled (${statusCounts.cancelled || 0})`"
            :class="[
              'tw-rounded-xl tw-px-6 tw-py-3 tw-font-medium tw-transition-all',
              activeStatusFilter === 'cancelled' 
                ? 'tw-bg-red-500 tw-text-white tw-shadow-lg' 
                : 'tw-bg-gray-100 tw-text-gray-700 hover:tw-bg-gray-200'
            ]"
            @click="setStatusFilter('cancelled')"
          />
        </div>

        <!-- Actions -->
        <div class="tw-flex tw-gap-3">
          <Button
            icon="pi pi-refresh"
            class="tw-bg-gray-100 tw-text-gray-700 tw-rounded-xl tw-px-4 tw-py-3 hover:tw-bg-gray-200 tw-transition-all"
            @click="refreshReserves"
            :loading="loading"
          />
        </div>
      </div>
    </div>

    <!-- Reserves Table -->
    <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-overflow-hidden">
      <DataTable
        :value="reserves"
        :loading="loading"
        :paginator="true"
        :rows="perPage"
        :totalRecords="total"
        :lazy="true"
        @page="onPage"
        @row-click="onRowClick"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        :rowsPerPageOptions="[10, 25, 50, 100]"
        currentPageReportTemplate="Showing {first} to {last} of {totalRecords} reserves"
        class="p-datatable-sm tw-rounded-2xl tw-cursor-pointer"
        stripedRows
        showGridlines
        responsiveLayout="scroll"
      >
        <template #loading>
          <div class="tw-flex tw-flex-col tw-items-center tw-justify-center tw-py-12">
            <i class="pi pi-spin pi-spinner tw-text-6xl tw-text-purple-500 tw-mb-4"></i>
            <p class="tw-text-gray-500 tw-text-lg">Loading reserves...</p>
          </div>
        </template>

        <template #empty>
          <div class="tw-flex tw-flex-col tw-items-center tw-justify-center tw-py-16">
            <i class="pi pi-bookmark tw-text-8xl tw-text-gray-300 tw-mb-4"></i>
            <p class="tw-text-gray-500 tw-text-xl tw-font-semibold tw-mb-2">No reserves found</p>
            <p class="tw-text-gray-400">Create your first reserve to get started</p>
          </div>
        </template>
        

        <Column field="id" header="ID" style="width: 80px" class="tw-font-medium" :sortable="true">
          <template #body="slotProps">
            <div class="tw-flex tw-items-center tw-gap-2">
              <div class="tw-bg-purple-100 tw-text-purple-700 tw-px-3 tw-py-1 tw-rounded-lg tw-font-bold">
                #{{ slotProps.data.id }}
              </div>
            </div>
          </template>
        </Column>

        <Column field="name" header="Reserve Name" style="min-width: 250px" :sortable="true">
          <template #body="slotProps">
            <div class="tw-flex tw-items-center tw-gap-3">
              <div class="tw-bg-gradient-to-br tw-from-purple-400 tw-to-pink-500 tw-w-10 tw-h-10 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-text-white tw-font-bold tw-shadow-md">
                {{ slotProps.data.name.charAt(0).toUpperCase() }}
              </div>
              <div>
                <div class="tw-font-semibold tw-text-gray-800">{{ slotProps.data.name }}</div>
                <div class="tw-text-sm tw-text-gray-500">Created {{ formatRelativeDate(slotProps.data.created_at) }}</div>
              </div>
            </div>
          </template>
        </Column>

        <Column field="description" header="Description" style="min-width: 300px">
          <template #body="slotProps">
            <div class="tw-text-gray-600 tw-line-clamp-2">
              {{ slotProps.data.description || 'No description provided' }}
            </div>
          </template>
        </Column>

       
        <Column header="Actions" style="min-width: 200px">
          <template #body="slotProps">
            <div class="tw-flex tw-gap-2">
              <Button
                icon="pi pi-eye"
                class="tw-bg-blue-500 tw-border-blue-500 tw-text-white tw-rounded-lg tw-p-2 hover:tw-bg-blue-600 tw-transition-all"
                v-tooltip.top="'View Details'"
                @click="viewReserve(slotProps.data)"
              />
              
              <Button
                icon="pi pi-pencil"
                class="tw-bg-green-500 tw-border-green-500 tw-text-white tw-rounded-lg tw-p-2 hover:tw-bg-green-600 tw-transition-all"
                v-tooltip.top="'Edit'"
                @click="editReserve(slotProps.data)"
              />
              <Button
                icon="pi pi-trash"
                class="tw-bg-red-500 tw-border-red-500 tw-text-white tw-rounded-lg tw-p-2 hover:tw-bg-red-600 tw-transition-all"
                v-tooltip.top="'Delete'"
                @click="confirmDelete(slotProps.data)"
              />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- View Reserve Dialog -->
    <Dialog
      v-model:visible="showViewDialog"
      modal
      :header="selectedReserve ? selectedReserve.name : 'Reserve Details'"
      :style="{ width: '70rem' }"
      :closable="true"
      class="tw-rounded-2xl"
    >
      <div v-if="selectedReserve" class="tw-p-6">
        <!-- Header with Status -->
        <div class="tw-bg-gradient-to-r tw-from-purple-600 tw-to-pink-700 tw-rounded-2xl tw-p-6 tw-mb-6 tw-text-white">
          <div class="tw-flex tw-justify-between tw-items-start">
            <div>
              <h2 class="tw-text-3xl tw-font-bold tw-mb-2">{{ selectedReserve.name }}</h2>
              <p class="tw-text-purple-100">Reserve ID: #{{ selectedReserve.id }}</p>
            </div>
            <Tag
              :value="selectedReserve.status || 'pending'"
              :severity="getStatusSeverity(selectedReserve.status)"
              class="tw-px-6 tw-py-3 tw-font-bold tw-text-lg tw-rounded-xl"
            />
          </div>
        </div>

        <!-- Details Grid -->
        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
          <!-- Description Card -->
          <div class="tw-col-span-full tw-bg-gradient-to-br tw-from-blue-50 tw-to-indigo-50 tw-rounded-xl tw-p-6 tw-border tw-border-blue-100">
            <h3 class="tw-text-xl tw-font-bold tw-text-blue-700 tw-mb-3 tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-align-left"></i>
              Description
            </h3>
            <p class="tw-text-gray-700 tw-leading-relaxed">
              {{ selectedReserve.description || 'No description provided' }}
            </p>
          </div>

          <!-- Reserved Date Card -->
          <div class="tw-bg-gradient-to-br tw-from-purple-50 tw-to-pink-50 tw-rounded-xl tw-p-6 tw-border tw-border-purple-100">
            <h3 class="tw-text-lg tw-font-bold tw-text-purple-700 tw-mb-3 tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-calendar"></i>
              Reserved Date
            </h3>
            <p class="tw-text-2xl tw-font-bold tw-text-gray-800">{{ formatDate(selectedReserve.reserved_at) }}</p>
            <p class="tw-text-sm tw-text-gray-500 tw-mt-2">{{ formatRelativeDate(selectedReserve.reserved_at) }}</p>
          </div>

          <!-- Status Card -->
          <div class="tw-bg-gradient-to-br tw-from-green-50 tw-to-emerald-50 tw-rounded-xl tw-p-6 tw-border tw-border-green-100">
            <h3 class="tw-text-lg tw-font-bold tw-text-green-700 tw-mb-3 tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-info-circle"></i>
              Status
            </h3>
            <Tag
              :value="selectedReserve.status || 'pending'"
              :severity="getStatusSeverity(selectedReserve.status)"
              :icon="getStatusIcon(selectedReserve.status)"
              class="tw-px-6 tw-py-3 tw-font-bold tw-text-lg tw-rounded-xl"
            />
          </div>

          <!-- Created Date Card -->
          <div class="tw-bg-gradient-to-br tw-from-orange-50 tw-to-yellow-50 tw-rounded-xl tw-p-6 tw-border tw-border-orange-100">
            <h3 class="tw-text-lg tw-font-bold tw-text-orange-700 tw-mb-3 tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-clock"></i>
              Created At
            </h3>
            <p class="tw-text-lg tw-font-semibold tw-text-gray-800">{{ formatDateTime(selectedReserve.created_at) }}</p>
          </div>

          <!-- Updated Date Card -->
          <div class="tw-bg-gradient-to-br tw-from-teal-50 tw-to-cyan-50 tw-rounded-xl tw-p-6 tw-border tw-border-teal-100">
            <h3 class="tw-text-lg tw-font-bold tw-text-teal-700 tw-mb-3 tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-refresh"></i>
              Last Updated
            </h3>
            <p class="tw-text-lg tw-font-semibold tw-text-gray-800">{{ formatDateTime(selectedReserve.updated_at) }}</p>
          </div>
        </div>

        <!-- Footer Actions -->
        <div class="tw-flex tw-justify-end tw-gap-4 tw-mt-8 tw-pt-6 tw-border-t tw-border-gray-200">
          <Button
            label="Close"
            severity="secondary"
            class="tw-rounded-lg tw-px-6 tw-py-3 tw-font-medium"
            @click="closeViewDialog"
          />
          <Button
            label="Edit Reserve"
            icon="pi pi-pencil"
            class="tw-bg-purple-600 tw-border-purple-600 tw-rounded-lg tw-px-6 tw-py-3 tw-font-medium tw-shadow-lg hover:tw-bg-purple-700 tw-transition-all tw-duration-300"
            @click="editReserve(selectedReserve)"
          />
        </div>
      </div>
    </Dialog>

    <!-- Add Reserve Dialog -->
    <Dialog
      v-model:visible="showAddDialog"
      modal
      header="Create New Reserve"
      :style="{ width: '60rem' }"
      :closable="true"
      class="tw-rounded-2xl"
    >
      <form @submit.prevent="addReserve" class="tw-p-6">
        <div class="tw-space-y-6">
          <!-- Reserve Name -->
          <div class="tw-bg-gradient-to-r tw-from-purple-50 tw-to-pink-50 tw-rounded-xl tw-p-6">
            <label class="tw-block tw-text-sm tw-font-bold tw-text-gray-700 tw-mb-2">
              <i class="pi pi-bookmark tw-mr-2"></i>Reserve Name *
            </label>
            <InputText
              v-model="newReserve.name"
              placeholder="Enter reserve name"
              class="tw-w-full tw-rounded-lg tw-border-2 tw-px-4 tw-py-3 focus:tw-border-purple-500 focus:tw-ring-4 focus:tw-ring-purple-100"
              required
            />
          </div>

          <!-- Description -->
          <div class="tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50 tw-rounded-xl tw-p-6">
            <label class="tw-block tw-text-sm tw-font-bold tw-text-gray-700 tw-mb-2">
              <i class="pi pi-align-left tw-mr-2"></i>Description
            </label>
            <Textarea
              v-model="newReserve.description"
              rows="4"
              placeholder="Enter reserve description (optional)"
              class="tw-w-full tw-rounded-lg tw-border-2 tw-px-4 tw-py-3 focus:tw-border-blue-500 focus:tw-ring-4 focus:tw-ring-blue-100"
            />
          </div>

          <!-- Reserved Date and Status -->
          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
            <!-- Reserved Date -->
            <div class="tw-bg-gradient-to-r tw-from-green-50 tw-to-emerald-50 tw-rounded-xl tw-p-6">
              <label class="tw-block tw-text-sm tw-font-bold tw-text-gray-700 tw-mb-2">
                <i class="pi pi-calendar tw-mr-2"></i>Reserved Date *
              </label>
              <Calendar
                v-model="newReserve.reserved_at"
                dateFormat="yy-mm-dd"
                :showIcon="true"
                placeholder="Select date"
                class="tw-w-full"
                required
              />
            </div>

            <!-- Status -->
            <div class="tw-bg-gradient-to-r tw-from-orange-50 tw-to-yellow-50 tw-rounded-xl tw-p-6">
              <label class="tw-block tw-text-sm tw-font-bold tw-text-gray-700 tw-mb-2">
                <i class="pi pi-info-circle tw-mr-2"></i>Status
              </label>
              <Dropdown
                v-model="newReserve.status"
                :options="statusOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Select status"
                class="tw-w-full"
              />
            </div>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="tw-flex tw-justify-end tw-gap-4 tw-mt-8 tw-pt-6 tw-border-t tw-border-gray-200">
          <Button
            type="button"
            label="Cancel"
            severity="secondary"
            class="tw-rounded-lg tw-px-6 tw-py-3 tw-font-medium"
            @click="closeAddDialog"
            :disabled="isSubmitting"
          />
          <Button
            type="submit"
            label="Create Reserve"
            icon="pi pi-check"
            class="tw-bg-purple-600 tw-border-purple-600 tw-rounded-lg tw-px-6 tw-py-3 tw-font-medium tw-shadow-lg hover:tw-bg-purple-700 tw-transition-all tw-duration-300"
            :loading="isSubmitting"
            :disabled="isSubmitting"
          />
        </div>
      </form>
    </Dialog>

    <!-- Edit Reserve Dialog -->
    <Dialog
      v-model:visible="showEditDialog"
      modal
      header="Edit Reserve"
      :style="{ width: '60rem' }"
      :closable="true"
      class="tw-rounded-2xl"
    >
      <form @submit.prevent="updateReserve" class="tw-p-6">
        <div class="tw-space-y-6">
          <!-- Reserve Name -->
          <div class="tw-bg-gradient-to-r tw-from-purple-50 tw-to-pink-50 tw-rounded-xl tw-p-6">
            <label class="tw-block tw-text-sm tw-font-bold tw-text-gray-700 tw-mb-2">
              <i class="pi pi-bookmark tw-mr-2"></i>Reserve Name *
            </label>
            <InputText
              v-model="editingReserve.name"
              placeholder="Enter reserve name"
              class="tw-w-full tw-rounded-lg tw-border-2 tw-px-4 tw-py-3 focus:tw-border-purple-500 focus:tw-ring-4 focus:tw-ring-purple-100"
              required
            />
          </div>

          <!-- Description -->
          <div class="tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50 tw-rounded-xl tw-p-6">
            <label class="tw-block tw-text-sm tw-font-bold tw-text-gray-700 tw-mb-2">
              <i class="pi pi-align-left tw-mr-2"></i>Description
            </label>
            <Textarea
              v-model="editingReserve.description"
              rows="4"
              placeholder="Enter reserve description (optional)"
              class="tw-w-full tw-rounded-lg tw-border-2 tw-px-4 tw-py-3 focus:tw-border-blue-500 focus:tw-ring-4 focus:tw-ring-blue-100"
            />
          </div>

          <!-- Reserved Date and Status -->
          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
            <!-- Reserved Date -->
            <div class="tw-bg-gradient-to-r tw-from-green-50 tw-to-emerald-50 tw-rounded-xl tw-p-6">
              <label class="tw-block tw-text-sm tw-font-bold tw-text-gray-700 tw-mb-2">
                <i class="pi pi-calendar tw-mr-2"></i>Reserved Date *
              </label>
              <Calendar
                v-model="editingReserve.reserved_at"
                dateFormat="yy-mm-dd"
                :showIcon="true"
                placeholder="Select date"
                class="tw-w-full"
                required
              />
            </div>

            <!-- Status -->
            <div class="tw-bg-gradient-to-r tw-from-orange-50 tw-to-yellow-50 tw-rounded-xl tw-p-6">
              <label class="tw-block tw-text-sm tw-font-bold tw-text-gray-700 tw-mb-2">
                <i class="pi pi-info-circle tw-mr-2"></i>Status
              </label>
              <Dropdown
                v-model="editingReserve.status"
                :options="statusOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Select status"
                class="tw-w-full"
              />
            </div>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="tw-flex tw-justify-end tw-gap-4 tw-mt-8 tw-pt-6 tw-border-t tw-border-gray-200">
          <Button
            type="button"
            label="Cancel"
            severity="secondary"
            class="tw-rounded-lg tw-px-6 tw-py-3 tw-font-medium"
            @click="closeEditDialog"
            :disabled="isSubmitting"
          />
          <Button
            type="submit"
            label="Update Reserve"
            icon="pi pi-check"
            class="tw-bg-purple-600 tw-border-purple-600 tw-rounded-lg tw-px-6 tw-py-3 tw-font-medium tw-shadow-lg hover:tw-bg-purple-700 tw-transition-all tw-duration-300"
            :loading="isSubmitting"
            :disabled="isSubmitting"
          />
        </div>
      </form>
    </Dialog>
  </div>
</template>

<script>
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

// PrimeVue Components
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import Calendar from 'primevue/calendar';
import Tag from 'primevue/tag';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';

export default {
  name: 'ReserveList',
  components: {
    DataTable,
    Column,
    Dialog,
    Button,
    InputText,
    Textarea,
    Dropdown,
    Calendar,
    Tag,
    Toast,
    ConfirmDialog
  },
  data() {
    return {
      reserves: [],
      selectedReserve: null,
      showViewDialog: false,
      showAddDialog: false,
      showEditDialog: false,
      editingReserve: null,
      isSubmitting: false,
      loading: false,
      searchQuery: '',
      searchTimeout: null,
      activeStatusFilter: 'all',
      // Pagination
      currentPage: 1,
      perPage: 10,
      total: 0,
      // Status counts
      statusCounts: {
        pending: 0,
        confirmed: 0,
        completed: 0,
        cancelled: 0
      },
      // New reserve form
      newReserve: {
        name: '',
        description: '',
        reserved_at: null,
        status: 'pending'
      },
      // Status options
      statusOptions: [
        { label: 'Pending', value: 'pending' },
        { label: 'Confirmed', value: 'confirmed' },
        { label: 'Completed', value: 'completed' },
        { label: 'Cancelled', value: 'cancelled' }
      ]
    };
  },
  mounted() {
    this.toast = useToast();
    this.confirm = useConfirm();
    this.fetchReserves();
  },
  methods: {
    onRowClick(event) {
      // Navigate to the reserve products page
      this.$router.push({
        name: 'stock.product-reserves.products',
        params: { reserveId: event.data.id }
      });
    },

    async fetchReserves(page = 1) {
      this.loading = true;
      try {
        const params = {
          page: page,
          per_page: this.perPage
        };

        // Add search if present
        if (this.searchQuery.trim()) {
          params.search = this.searchQuery.trim();
        }

        // Add status filter if not 'all'
        if (this.activeStatusFilter !== 'all') {
          params.status = this.activeStatusFilter;
        }

        const response = await axios.get('/api/stock/reserves', { params });
        
        if (response.data) {
          this.reserves = response.data.data || response.data;
          this.total = response.data.total || this.reserves.length;
          this.currentPage = response.data.current_page || page;
          
          // Calculate status counts
          this.calculateStatusCounts();
        }
      } catch (error) {
        console.error('Error fetching reserves:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to load reserves',
          life: 3000
        });
      } finally {
        this.loading = false;
      }
    },

    calculateStatusCounts() {
      // Reset counts
      this.statusCounts = {
        pending: 0,
        confirmed: 0,
        completed: 0,
        cancelled: 0
      };

      // Count from current data
      this.reserves.forEach(reserve => {
        const status = reserve.status || 'pending';
        if (this.statusCounts.hasOwnProperty(status)) {
          this.statusCounts[status]++;
        }
      });
    },

    onSearchInput() {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.currentPage = 1;
        this.fetchReserves(1);
      }, 300);
    },

    onPage(event) {
      this.currentPage = event.page + 1;
      this.perPage = event.rows;
      this.fetchReserves(this.currentPage);
    },

    setStatusFilter(status) {
      this.activeStatusFilter = status;
      this.currentPage = 1;
      this.fetchReserves(1);
    },

    async refreshReserves() {
      await this.fetchReserves(this.currentPage);
      this.toast.add({
        severity: 'success',
        summary: 'Refreshed',
        detail: 'Reserve list has been refreshed',
        life: 2000
      });
    },

    viewReserve(reserve) {
      this.selectedReserve = { ...reserve };
      this.showViewDialog = true;
    },

    closeViewDialog() {
      this.showViewDialog = false;
      this.selectedReserve = null;
    },

    openAddReserveModal() {
      this.resetNewReserve();
      this.showAddDialog = true;
    },

    closeAddDialog() {
      this.showAddDialog = false;
      this.resetNewReserve();
    },

    resetNewReserve() {
      this.newReserve = {
        name: '',
        description: '',
        reserved_at: null,
        status: 'pending'
      };
    },

    async addReserve() {
      this.isSubmitting = true;
      try {
        // Format the date
        const formattedData = {
          ...this.newReserve,
          reserved_at: this.formatDateForAPI(this.newReserve.reserved_at)
        };

        const response = await axios.post('/api/stock/reserves', formattedData);
        
        if (response.data) {
          this.toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Reserve created successfully',
            life: 3000
          });
          this.closeAddDialog();
          this.fetchReserves(this.currentPage);
        }
      } catch (error) {
        console.error('Error creating reserve:', error);
        if (error.response && error.response.data.errors) {
          const errors = Object.values(error.response.data.errors).flat();
          this.toast.add({
            severity: 'error',
            summary: 'Validation Error',
            detail: errors.join(', '),
            life: 5000
          });
        } else {
          this.toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to create reserve',
            life: 3000
          });
        }
      } finally {
        this.isSubmitting = false;
      }
    },

    editReserve(reserve) {
      this.editingReserve = { ...reserve };
      // Convert date string to Date object for Calendar component
      if (this.editingReserve.reserved_at) {
        this.editingReserve.reserved_at = new Date(this.editingReserve.reserved_at);
      }
      this.showViewDialog = false;
      this.showEditDialog = true;
    },

    closeEditDialog() {
      this.showEditDialog = false;
      this.editingReserve = null;
    },

    async updateReserve() {
      this.isSubmitting = true;
      try {
        // Format the date
        const formattedData = {
          ...this.editingReserve,
          reserved_at: this.formatDateForAPI(this.editingReserve.reserved_at)
        };

        const response = await axios.put(`/api/stock/reserves/${this.editingReserve.id}`, formattedData);
        
        if (response.data) {
          this.toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Reserve updated successfully',
            life: 3000
          });
          this.closeEditDialog();
          this.fetchReserves(this.currentPage);
        }
      } catch (error) {
        console.error('Error updating reserve:', error);
        if (error.response && error.response.data.errors) {
          const errors = Object.values(error.response.data.errors).flat();
          this.toast.add({
            severity: 'error',
            summary: 'Validation Error',
            detail: errors.join(', '),
            life: 5000
          });
        } else {
          this.toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to update reserve',
            life: 3000
          });
        }
      } finally {
        this.isSubmitting = false;
      }
    },

    confirmDelete(reserve) {
      this.confirm.require({
        message: `Are you sure you want to delete "${reserve.name}"? This action cannot be undone.`,
        header: 'Delete Reserve',
        icon: 'pi pi-exclamation-triangle',
        rejectClass: 'p-button-secondary p-button-outlined',
        acceptClass: 'p-button-danger',
        accept: () => {
          this.deleteReserve(reserve);
        }
      });
    },

    async deleteReserve(reserve) {
      try {
        await axios.delete(`/api/stock/reserves/${reserve.id}`);
        this.toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Reserve deleted successfully',
          life: 3000
        });
        this.fetchReserves(this.currentPage);
      } catch (error) {
        console.error('Error deleting reserve:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to delete reserve',
          life: 3000
        });
      }
    },

    getStatusSeverity(status) {
      const severities = {
        pending: 'warning',
        confirmed: 'info',
        completed: 'success',
        cancelled: 'danger'
      };
      return severities[status] || 'secondary';
    },

    getStatusIcon(status) {
      const icons = {
        pending: 'pi pi-clock',
        confirmed: 'pi pi-check-circle',
        completed: 'pi pi-check',
        cancelled: 'pi pi-times-circle'
      };
      return icons[status] || 'pi pi-info-circle';
    },

    formatDate(dateString) {
      if (!dateString) return 'N/A';
      const date = new Date(dateString);
      return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    },

    formatDateTime(dateString) {
      if (!dateString) return 'N/A';
      const date = new Date(dateString);
      return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    },

    formatRelativeDate(dateString) {
      if (!dateString) return 'N/A';
      const date = new Date(dateString);
      const now = new Date();
      const diffTime = Math.abs(now - date);
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

      if (diffDays === 0) return 'Today';
      if (diffDays === 1) return 'Yesterday';
      if (diffDays < 7) return `${diffDays} days ago`;
      if (diffDays < 30) return `${Math.floor(diffDays / 7)} weeks ago`;
      if (diffDays < 365) return `${Math.floor(diffDays / 30)} months ago`;
      return `${Math.floor(diffDays / 365)} years ago`;
    },

    formatDateForAPI(date) {
      if (!date) return null;
      
      // If it's already a string in the correct format
      if (typeof date === 'string') {
        return date;
      }
      
      // If it's a Date object
      const d = new Date(date);
      const year = d.getFullYear();
      const month = String(d.getMonth() + 1).padStart(2, '0');
      const day = String(d.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    }
  }
};
</script>

<style scoped>
/* Custom styles for PrimeVue components */
.p-datatable .p-datatable-tbody > tr {
  cursor: pointer !important;
  transition: all 0.2s ease;
}

.p-datatable .p-datatable-tbody > tr:hover {
  background-color: #faf5ff !important;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(147, 51, 234, 0.1);
  transition: all 0.2s ease;
}

/* Enhanced button styles */
.p-button {
  transition: all 0.3s ease !important;
}

.p-button:hover {
  transform: translateY(-2px) !important;
  box-shadow: 0 8px 25px rgba(147, 51, 234, 0.15) !important;
}

/* Dialog animations */
.p-dialog {
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* Enhanced form inputs */
.p-inputtext:focus,
.p-textarea:focus,
.p-dropdown:focus,
.p-calendar:focus {
  box-shadow: 0 0 0 3px rgba(147, 51, 234, 0.1) !important;
  border-color: #9333ea !important;
}

/* Loading animation */
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

/* Tag enhancements */
.p-tag {
  border-radius: 20px !important;
  font-weight: 600 !important;
  letter-spacing: 0.025em !important;
}

/* DataTable header styling */
.p-datatable .p-datatable-thead > tr > th {
  background: linear-gradient(135deg, #9333ea 0%, #ec4899 100%) !important;
  color: white !important;
  font-weight: 600 !important;
  border: none !important;
  padding: 1rem !important;
}

/* Enhanced card shadows */
.tw-shadow-lg {
  box-shadow: 0 20px 25px -5px rgba(147, 51, 234, 0.1), 0 10px 10px -5px rgba(147, 51, 234, 0.04) !important;
}

.tw-shadow-xl {
  box-shadow: 0 25px 50px -12px rgba(147, 51, 234, 0.25) !important;
}

/* Line clamp utility */
.tw-line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Responsive design */
@media (max-width: 768px) {
  .tw-flex {
    flex-direction: column;
    gap: 1rem;
  }
}
</style>
