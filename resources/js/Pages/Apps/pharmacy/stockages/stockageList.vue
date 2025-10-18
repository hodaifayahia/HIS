<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-gray-50 tw-via-blue-50 tw-to-purple-50 tw-p-6">
    <!-- Toast Notifications -->
    <Toast />

    <div class="tw-flex tw-justify-between tw-items-center tw-mb-8">
      <Card class="tw-w-full tw-bg-gradient-to-r tw-from-blue-600 tw-to-purple-600 tw-text-white tw-shadow-lg">
        <template #content>
          <div class="tw-flex tw-justify-between tw-items-center">
            <div class="tw-flex tw-items-center tw-gap-3">
              <i class="pi pi-warehouse tw-text-4xl"></i>
              <h1 class="tw-m-0 tw-text-white tw-text-3xl tw-font-bold">Stockage Management</h1>
            </div>
            <div class="tw-flex tw-gap-4 tw-items-center">
              <InputText
                v-model="searchQuery"
                placeholder="Search stockages..."
                class="tw-w-80 tw-rounded-lg tw-border-white/20 tw-bg-white/10 tw-text-white tw-placeholder-white/70"
                @input="onSearchInput"
              />
              <Button
                label="Add Stockage"
                icon="pi pi-plus"
                class="p-button-outlined p-button-light tw-border-white tw-text-white hover:tw-bg-white hover:tw-text-blue-600"
                @click="openAddStockageModal"
              />
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Filters Section -->
    <Card class="tw-mb-5 tw-shadow-md tw-border-l-4 tw-border-blue-500">
      <template #content>
        <div class="tw-flex tw-gap-4 tw-items-center tw-flex-wrap">
          <div class="tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-filter tw-text-blue-500"></i>
            <label class="tw-text-sm tw-font-medium tw-text-gray-700">Service:</label>
            <Dropdown
              v-model="serviceFilter"
              :options="services"
              option-label="name"
              option-value="id"
              placeholder="All Services"
              class="tw-w-48 tw-rounded-lg"
              @change="applyFilters"
            />
          </div>

          <div class="tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-building tw-text-green-500"></i>
            <label class="tw-text-sm tw-font-medium tw-text-gray-700">Warehouse Type:</label>
            <Dropdown
              v-model="warehouseTypeFilter"
              :options="warehouseTypeOptions"
              option-label="label"
              option-value="value"
              placeholder="All Types"
              class="tw-w-48 tw-rounded-lg"
              @change="applyFilters"
            />
          </div>

          <Button
            label="Clear Filters"
            icon="pi pi-times"
            class="p-button-secondary tw-rounded-lg"
            @click="clearFilters"
          />
        </div>
      </template>
    </Card>

    <!-- Stockage Type Filter Tabs -->
    <div class="tw-flex tw-gap-3 tw-mb-5 tw-bg-white tw-p-6 tw-rounded-xl tw-shadow-lg tw-border tw-border-gray-100">
      <button
        class="tw-px-6 tw-py-4 tw-border-2 tw-border-gray-200 tw-bg-white tw-rounded-lg tw-cursor-pointer tw-text-sm tw-font-medium tw-transition-all tw-duration-300 tw-flex tw-items-center tw-gap-3 tw-text-gray-700 hover:tw-border-blue-500 hover:tw-bg-blue-50 hover:tw-shadow-md tw-transform hover:tw-scale-105"
        :class="{ 'tw-border-blue-500 tw-bg-blue-500 tw-text-white tw-shadow-lg': activeTab === 'all' }"
        @click="setActiveTab('all')"
      >
        <i class="fas fa-th-list tw-text-lg"></i>
        <span>All Stockages</span>
        <span class="tw-bg-gray-200 tw-text-gray-700 tw-px-3 tw-py-1 tw-rounded-full tw-text-xs tw-font-semibold">{{ stockages.length }}</span>
      </button>
      <button
        class="tw-px-6 tw-py-4 tw-border-2 tw-border-green-500 tw-bg-white tw-rounded-lg tw-cursor-pointer tw-text-sm tw-font-medium tw-transition-all tw-duration-300 tw-flex tw-items-center tw-gap-3 tw-text-green-700 hover:tw-border-green-600 hover:tw-bg-green-50 hover:tw-shadow-md tw-transform hover:tw-scale-105"
        :class="{ 'tw-border-green-500 tw-bg-green-500 tw-text-white tw-shadow-lg': activeTab === 'active' }"
        @click="setActiveTab('active')"
      >
        <i class="fas fa-check-circle tw-text-lg"></i>
        <span>Active</span>
        <span class="tw-bg-white/30 tw-text-white tw-px-3 tw-py-1 tw-rounded-full tw-text-xs tw-font-semibold">{{ activeCount }}</span>
      </button>
      <button
        class="tw-px-6 tw-py-4 tw-border-2 tw-border-yellow-500 tw-bg-white tw-rounded-lg tw-cursor-pointer tw-text-sm tw-font-medium tw-transition-all tw-duration-300 tw-flex tw-items-center tw-gap-3 tw-text-yellow-700 hover:tw-border-yellow-600 hover:tw-bg-yellow-50 hover:tw-shadow-md tw-transform hover:tw-scale-105"
        :class="{ 'tw-border-yellow-500 tw-bg-yellow-500 tw-text-white tw-shadow-lg': activeTab === 'maintenance' }"
        @click="setActiveTab('maintenance')"
      >
        <i class="fas fa-tools tw-text-lg"></i>
        <span>Maintenance</span>
        <span class="tw-bg-white/80 tw-text-yellow-700 tw-px-3 tw-py-1 tw-rounded-full tw-text-xs tw-font-semibold">{{ maintenanceCount }}</span>
      </button>
    </div>

    <!-- DataTable -->
    <Card class="tw-shadow-xl tw-rounded-xl tw-overflow-hidden">
      <template #content>
        <DataTable
          :value="filteredStockages"
          :loading="loading"
          :paginator="true"
          :rows="perPage"
          :totalRecords="total"
          :lazy="true"
          @page="onPage"
          class="p-datatable-sm tw-rounded-lg"
          stripedRows
          showGridlines
          responsiveLayout="scroll"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          :rowsPerPageOptions="[5,10,25,50]"
        >
          <template #loading>
            <div class="tw-flex tw-justify-center tw-items-center tw-py-12 tw-bg-gray-50">
              <ProgressSpinner class="tw-mr-3" />
              <span class="tw-text-lg tw-text-gray-600">Loading stockages...</span>
            </div>
          </template>

          <template #empty>
            <div class="tw-text-center tw-py-20 tw-bg-gray-50 tw-rounded-lg">
              <i class="pi pi-warehouse tw-text-6xl tw-mb-4 tw-text-gray-400"></i>
              <p class="tw-text-xl tw-m-0 tw-text-gray-500 tw-font-medium">No stockages found</p>
              <p class="tw-text-sm tw-text-gray-400 tw-mt-2">Try adjusting your filters or add a new stockage.</p>
            </div>
          </template>

          <Column field="id" header="ID" style="width: 80px" sortable class="tw-font-semibold" />

          <Column field="name" header="Name" style="min-width: 200px" sortable>
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-w-10 tw-h-10 tw-rounded-full tw-bg-gradient-to-br tw-from-blue-400 tw-to-purple-500 tw-flex tw-items-center tw-justify-center tw-text-white tw-font-bold">
                  {{ slotProps.data.name.charAt(0).toUpperCase() }}
                </div>
                <div>
                  <div class="tw-font-medium tw-text-gray-800">{{ slotProps.data.name }}</div>
                  <div v-if="slotProps.data.temperature_controlled" class="tw-flex tw-items-center tw-gap-1 tw-text-xs tw-text-blue-600">
                    <i class="pi pi-snowflake"></i>
                    <span>Temperature Controlled</span>
                  </div>
                </div>
              </div>
            </template>
          </Column>

          <Column field="location" header="Location" style="min-width: 150px" sortable />

          <Column field="type" header="Type" style="min-width: 120px" sortable>
            <template #body="slotProps">
              <Tag :value="getTypeLabel(slotProps.data.type)" :severity="getTypeSeverity(slotProps.data.type)" class="tw-font-medium" />
            </template>
          </Column>

          <Column field="capacity" header="Capacity" style="min-width: 100px" sortable>
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-box tw-text-gray-400"></i>
                <span v-if="slotProps.data.capacity" class="tw-font-medium">{{ slotProps.data.capacity }}</span>
                <span v-else class="tw-text-gray-500 tw-italic">N/A</span>
              </div>
            </template>
          </Column>

          <Column field="service.name" header="Service" style="min-width: 150px" sortable>
            <template #body="slotProps">
              <span v-if="slotProps.data.service" class="tw-bg-blue-100 tw-text-blue-800 tw-px-3 tw-py-1 tw-rounded-full tw-text-sm tw-font-medium">{{ slotProps.data.service.name }}</span>
              <span v-else class="tw-text-gray-500 tw-italic">N/A</span>
            </template>
          </Column>

          <Column field="status" header="Status" style="min-width: 120px" sortable>
            <template #body="slotProps">
              <Tag :value="getStatusLabel(slotProps.data.status)" :severity="getStatusSeverity(slotProps.data.status)" class="tw-font-medium" />
            </template>
          </Column>

          <Column header="Actions" style="min-width: 220px">
            <template #body="slotProps">
              <div class="tw-flex tw-gap-2">
                <Button
                  icon="pi pi-eye"
                  class="p-button-info p-button-sm p-button-rounded p-button-outlined"
                  @click="viewStockage(slotProps.data)"
                  v-tooltip.top="'View Details'"
                />
                <Button
                  icon="pi pi-box"
                  class="p-button-success p-button-sm p-button-rounded p-button-outlined"
                  @click="viewStock(slotProps.data)"
                  v-tooltip.top="'View Stock'"
                />
                <Button
                  icon="pi pi-pencil"
                  class="p-button-warning p-button-sm p-button-rounded p-button-outlined"
                  @click="editStockage(slotProps.data)"
                  v-tooltip.top="'Edit'"
                />
                <Button
                  icon="pi pi-trash"
                  class="p-button-danger p-button-sm p-button-rounded p-button-outlined"
                  @click="deleteStockage(slotProps.data)"
                  v-tooltip.top="'Delete'"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Stockage Details Modal -->
    <div v-if="selectedStockage" class="tw-fixed tw-inset-0 tw-bg-black tw-bg-opacity-60 tw-flex tw-justify-center tw-items-center tw-z-50 tw-backdrop-blur-sm" @click="closeModal">
      <div class="tw-bg-white tw-rounded-2xl tw-w-full tw-max-w-lg tw-max-h-5/6 tw-overflow-y-auto tw-shadow-2xl tw-transform tw-scale-100 tw-transition-all tw-duration-300" @click.stop>
        <div class="tw-flex tw-justify-between tw-items-center tw-p-6 tw-border-b tw-border-gray-200 tw-bg-gradient-to-r tw-from-blue-50 tw-to-purple-50">
          <div class="tw-flex tw-items-center tw-gap-3">
            <div class="tw-w-12 tw-h-12 tw-rounded-full tw-bg-gradient-to-br tw-from-blue-400 tw-to-purple-500 tw-flex tw-items-center tw-justify-center tw-text-white tw-font-bold tw-text-xl">
              {{ selectedStockage.name.charAt(0).toUpperCase() }}
            </div>
            <h3 class="tw-m-0 tw-text-gray-800 tw-text-xl tw-font-bold">{{ selectedStockage.name }}</h3>
          </div>
          <button class="tw-bg-none tw-border-none tw-text-2xl tw-cursor-pointer tw-text-gray-500 hover:tw-text-gray-700 tw-transition-colors" @click="closeModal">&times;</button>
        </div>
        <div class="tw-p-6">
          <div class="tw-flex tw-flex-col tw-gap-5">
            <div class="tw-flex tw-justify-between tw-items-center tw-py-3 tw-px-4 tw-border tw-border-gray-100 tw-rounded-lg tw-bg-gray-50">
              <strong class="tw-text-gray-700 tw-min-w-40 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-info-circle tw-text-blue-500"></i>
                Description:
              </strong>
              <span class="tw-text-gray-800 tw-text-right">{{ selectedStockage.description || 'No description available' }}</span>
            </div>
            <div class="tw-flex tw-justify-between tw-items-center tw-py-3 tw-px-4 tw-border tw-border-gray-100 tw-rounded-lg tw-bg-gray-50">
              <strong class="tw-text-gray-700 tw-min-w-40 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-map-marker tw-text-red-500"></i>
                Location:
              </strong>
              <span class="tw-text-gray-800 tw-text-right">{{ selectedStockage.location }}</span>
            </div>
            <div class="tw-flex tw-justify-between tw-items-center tw-py-3 tw-px-4 tw-border tw-border-gray-100 tw-rounded-lg tw-bg-gray-50">
              <strong class="tw-text-gray-700 tw-min-w-40 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-tag tw-text-green-500"></i>
                Location Code:
              </strong>
              <span class="tw-text-gray-800 tw-text-right">{{ selectedStockage.location_code || 'Not specified' }}</span>
            </div>
            <div class="tw-flex tw-justify-between tw-items-center tw-py-3 tw-px-4 tw-border tw-border-gray-100 tw-rounded-lg tw-bg-gray-50">
              <strong class="tw-text-gray-700 tw-min-w-40 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-building tw-text-purple-500"></i>
                Warehouse Type:
              </strong>
              <span class="tw-text-gray-800 tw-text-right">{{ selectedStockage.warehouse_type || 'Not specified' }}</span>
            </div>
            <div class="tw-flex tw-justify-between tw-items-center tw-py-3 tw-px-4 tw-border tw-border-gray-100 tw-rounded-lg tw-bg-gray-50">
              <strong class="tw-text-gray-700 tw-min-w-40 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-cog tw-text-orange-500"></i>
                Type:
              </strong>
              <Tag :value="getTypeLabel(selectedStockage.type)" :severity="getTypeSeverity(selectedStockage.type)" class="tw-font-medium" />
            </div>
            <div class="tw-flex tw-justify-between tw-items-center tw-py-3 tw-px-4 tw-border tw-border-gray-100 tw-rounded-lg tw-bg-gray-50">
              <strong class="tw-text-gray-700 tw-min-w-40 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-box tw-text-indigo-500"></i>
                Capacity:
              </strong>
              <span v-if="selectedStockage.capacity" class="tw-text-gray-800 tw-text-right tw-font-medium">{{ selectedStockage.capacity }}</span>
              <span v-else class="tw-text-gray-500 tw-italic tw-text-right">Not specified</span>
            </div>
            <div class="tw-flex tw-justify-between tw-items-center tw-py-3 tw-px-4 tw-border tw-border-gray-100 tw-rounded-lg tw-bg-gray-50">
              <strong class="tw-text-gray-700 tw-min-w-40 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-check-circle tw-text-teal-500"></i>
                Status:
              </strong>
              <Tag :value="getStatusLabel(selectedStockage.status)" :severity="getStatusSeverity(selectedStockage.status)" class="tw-font-medium" />
            </div>
            <div class="tw-flex tw-justify-between tw-items-center tw-py-3 tw-px-4 tw-border tw-border-gray-100 tw-rounded-lg tw-bg-gray-50">
              <strong class="tw-text-gray-700 tw-min-w-40 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-shield tw-text-pink-500"></i>
                Security Level:
              </strong>
              <span class="tw-text-gray-800 tw-text-right">{{ getSecurityLevelLabel(selectedStockage.security_level) }}</span>
            </div>
            <div class="tw-flex tw-justify-between tw-items-center tw-py-3 tw-px-4 tw-border tw-border-gray-100 tw-rounded-lg tw-bg-gray-50">
              <strong class="tw-text-gray-700 tw-min-w-40 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-snowflake tw-text-blue-500"></i>
                Temperature Controlled:
              </strong>
              <span :class="selectedStockage.temperature_controlled ? 'tw-text-blue-600 tw-font-semibold tw-flex tw-items-center tw-gap-1' : 'tw-text-gray-500 tw-font-semibold tw-flex tw-items-center tw-gap-1'">
                <i :class="selectedStockage.temperature_controlled ? 'fas fa-snowflake' : 'fas fa-times'"></i>
                {{ selectedStockage.temperature_controlled ? 'Yes' : 'No' }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Stockage Modal -->
    <AddStockageModal
      :show-modal="showAddStockageModal"
      @close="closeAddStockageModal"
      @success="onStockageCreated"
    />

    <!-- Edit Stockage Dialog -->
    <Dialog
      v-model:visible="showEditStockageModal"
      modal
      header="Edit Stockage"
      :style="{ width: '55rem' }"
      :maximizable="true"
      :closable="true"
      :dismissableMask="true"
      class="tw-rounded-xl"
    >
      <form @submit.prevent="updateStockage" class="tw-p-6">
        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
          <!-- Basic Information -->
          <div class="tw-col-span-full">
            <div class="tw-flex tw-items-center tw-gap-2 tw-mb-4">
              <i class="pi pi-info-circle tw-text-blue-500"></i>
              <h4 class="tw-text-xl tw-font-semibold tw-text-gray-800 tw-m-0">Basic Information</h4>
            </div>
          </div>

          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              <i class="pi pi-tag tw-text-gray-500 tw-mr-1"></i>
              Stockage Name *
            </label>
            <InputText
              v-model="editingStockage.name"
              class="tw-w-full tw-rounded-lg tw-border-gray-300 focus:tw-border-blue-500 focus:tw-ring-blue-500"
              required
              placeholder="Enter stockage name"
            />
          </div>

          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              <i class="pi pi-map-marker tw-text-red-500 tw-mr-1"></i>
              Location *
            </label>
            <InputText
              v-model="editingStockage.location"
              class="tw-w-full tw-rounded-lg tw-border-gray-300 focus:tw-border-blue-500 focus:tw-ring-blue-500"
              required
              placeholder="Enter location"
            />
          </div>

          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              <i class="pi pi-hashtag tw-text-green-500 tw-mr-1"></i>
              Location Code
            </label>
            <InputText
              v-model="editingStockage.location_code"
              class="tw-w-full tw-rounded-lg tw-border-gray-300 focus:tw-border-blue-500 focus:tw-ring-blue-500"
              placeholder="Enter location code"
            />
          </div>

          <div class="tw-col-span-full">
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              <i class="pi pi-file tw-text-purple-500 tw-mr-1"></i>
              Description
            </label>
            <Textarea
              v-model="editingStockage.description"
              rows="4"
              class="tw-w-full tw-rounded-lg tw-border-gray-300 focus:tw-border-blue-500 focus:tw-ring-blue-500"
              placeholder="Enter description"
            />
          </div>

          <!-- Stockage Details -->
          <div class="tw-col-span-full">
            <div class="tw-flex tw-items-center tw-gap-2 tw-mb-4">
              <i class="pi pi-cog tw-text-orange-500"></i>
              <h4 class="tw-text-xl tw-font-semibold tw-text-gray-800 tw-m-0">Stockage Details</h4>
            </div>
          </div>

          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              <i class="pi pi-list tw-text-indigo-500 tw-mr-1"></i>
              Type *
            </label>
            <Dropdown
              v-model="editingStockage.type"
              :options="typeOptions"
              option-label="label"
              option-value="value"
              placeholder="Select Type"
              class="tw-w-full tw-rounded-lg"
              required
            />
          </div>

          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              <i class="pi pi-building tw-text-teal-500 tw-mr-1"></i>
              Warehouse Type
            </label>
            <Dropdown
              v-model="editingStockage.warehouse_type"
              :options="warehouseTypeOptions"
              option-label="label"
              option-value="value"
              placeholder="Select Warehouse Type"
              class="tw-w-full tw-rounded-lg"
            />
          </div>

          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              <i class="pi pi-box tw-text-pink-500 tw-mr-1"></i>
              Capacity
            </label>
            <InputNumber
              v-model="editingStockage.capacity"
              class="tw-w-full tw-rounded-lg"
              min="1"
              placeholder="Enter capacity"
            />
          </div>

          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              <i class="pi pi-check-circle tw-text-cyan-500 tw-mr-1"></i>
              Status
            </label>
            <Dropdown
              v-model="editingStockage.status"
              :options="statusOptions"
              option-label="label"
              option-value="value"
              class="tw-w-full tw-rounded-lg"
            />
          </div>

          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              <i class="pi pi-shield tw-text-yellow-500 tw-mr-1"></i>
              Security Level
            </label>
            <Dropdown
              v-model="editingStockage.security_level"
              :options="securityLevelOptions"
              option-label="label"
              option-value="value"
              class="tw-w-full tw-rounded-lg"
            />
          </div>

          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              <i class="pi pi-users tw-text-blue-500 tw-mr-1"></i>
              Service
            </label>
            <Dropdown
              v-model="editingStockage.service_id"
              :options="services"
              option-label="name"
              option-value="id"
              placeholder="Select Service (Optional)"
              class="tw-w-full tw-rounded-lg"
            />
          </div>

          <div class="tw-flex tw-items-center tw-gap-3 tw-p-4 tw-bg-gray-50 tw-rounded-lg">
            <Checkbox
              v-model="editingStockage.temperature_controlled"
              inputId="temperature_controlled_edit"
              :binary="true"
            />
            <label for="temperature_controlled_edit" class="tw-text-sm tw-font-medium tw-text-gray-700 tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-snowflake tw-text-blue-500"></i>
              Temperature Controlled
            </label>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="tw-flex tw-justify-end tw-gap-4 tw-mt-8 tw-pt-6 tw-border-t tw-border-gray-200">
          <Button
            type="button"
            label="Cancel"
            icon="pi pi-times"
            class="p-button-secondary tw-rounded-lg"
            @click="closeEditStockageModal"
            :disabled="isSubmitting"
          />
          <Button
            type="submit"
            label="Update Stockage"
            icon="pi pi-check"
            class="p-button-primary tw-rounded-lg tw-px-6"
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
import AddStockageModal from '../../../../Components/Apps/stock/AddStockageModal.vue';

// PrimeVue Components
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import InputNumber from 'primevue/inputnumber';
import Checkbox from 'primevue/checkbox';
import Tag from 'primevue/tag';
import ProgressSpinner from 'primevue/progressspinner';
import Toast from 'primevue/toast';
import Tooltip from 'primevue/tooltip';

export default {
  name: 'StockageList',
  components: {
    AddStockageModal,
    Card,
    Button,
    InputText,
    Dropdown,
    DataTable,
    Column,
    Dialog,
    Textarea,
    InputNumber,
    Checkbox,
    Tag,
    ProgressSpinner,
    Toast
  },
  data() {
    return {
      activeTab: 'all',
      searchQuery: '',
      serviceFilter: '',
      warehouseTypeFilter: '',
      selectedStockage: null,
      showAddStockageModal: false,
      showEditStockageModal: false,
      editingStockage: null,
      isSubmitting: false,
      submitSuccess: false,
      submitError: null,
      newStockage: {
        name: '',
        description: '',
        location: '',
        type: '',
        capacity: null,
        status: 'active',
        service_id: null,
        temperature_controlled: false,
        security_level: 'medium',
        location_code: '',
        warehouse_type: ''
      },
      stockages: [],
      filteredStockages: [],
      services: [],
      loading: false,
      error: null,
      searchTimeout: null,
      // Pagination
      currentPage: 1,
      lastPage: 1,
      perPage: 10,
      total: 0,
      // Dropdown options
      typeOptions: [
        { label: 'Warehouse', value: 'warehouse' },
        { label: 'Pharmacy', value: 'pharmacy' },
        { label: 'Laboratory', value: 'laboratory' },
        { label: 'Emergency', value: 'emergency' },
        { label: 'Storage Room', value: 'storage_room' },
        { label: 'Cold Room', value: 'cold_room' }
      ],
      warehouseTypeOptions: [
        { label: 'Central Pharmacy (PC)', value: 'Central Pharmacy (PC)' },
        { label: 'Service Pharmacy (PS)', value: 'Service Pharmacy (PS)' }
      ],
      statusOptions: [
        { label: 'Active', value: 'active' },
        { label: 'Inactive', value: 'inactive' },
        { label: 'Maintenance', value: 'maintenance' },
        { label: 'Under Construction', value: 'under_construction' }
      ],
      securityLevelOptions: [
        { label: 'Low', value: 'low' },
        { label: 'Medium', value: 'medium' },
        { label: 'High', value: 'high' },
        { label: 'Restricted', value: 'restricted' }
      ]
    }
  },
  mounted() {
    this.fetchStockages();
    this.fetchServices();
  },
  computed: {
    activeCount() {
      return this.stockages.filter(stockage => stockage.status === 'active').length;
    },
    maintenanceCount() {
      return this.stockages.filter(stockage => stockage.status === 'maintenance').length;
    },
    pageNumbers() {
      const pages = [];
      const totalPages = this.lastPage;
      const current = this.currentPage;

      if (totalPages <= 7) {
        for (let i = 1; i <= totalPages; i++) {
          pages.push(i);
        }
      } else {
        if (current <= 4) {
          for (let i = 1; i <= 5; i++) {
            pages.push(i);
          }
          pages.push('...');
          pages.push(totalPages);
        } else if (current >= totalPages - 3) {
          pages.push(1);
          pages.push('...');
          for (let i = totalPages - 4; i <= totalPages; i++) {
            pages.push(i);
          }
        } else {
          pages.push(1);
          pages.push('...');
          for (let i = current - 1; i <= current + 1; i++) {
            pages.push(i);
          }
          pages.push('...');
          pages.push(totalPages);
        }
      }

      return pages;
    }
  },
  methods: {
    async fetchStockages(page = 1) {
      this.loading = true;
      this.error = null;
      try {
        const params = {
          page: page,
          per_page: this.perPage
        };

        // Add search if present
        if (this.searchQuery.trim()) {
          params.search = this.searchQuery;
        }

        const response = await axios.get('/api/pharmacy/stockages', { params });
        if (response.data.success) {
          this.stockages = response.data.data;
          this.currentPage = response.data.meta.current_page;
          this.lastPage = response.data.meta.last_page;
          this.total = response.data.meta.total;
          this.filterStockages();
        }
      } catch (error) {
        this.error = 'Failed to load stockages';
      } finally {
        this.loading = false;
      }
    },

  // fetchManagers removed: managers are no longer used for stockages

    async fetchServices() {
      try {
        const response = await axios.get('/api/pharmacy/stockages', { params });
        // Normalize different possible API shapes:
        // - { status: 'success', data: [...] }
        // - { success: true, data: [...] }
        // - [...] (raw array)
        const resData = response.data;
        if (Array.isArray(resData)) {
          this.services = resData;
        } else if (resData && Array.isArray(resData.data)) {
          this.services = resData.data;
        }
      } catch (error) {
      }
    },

    onSearchInput() {
      // Debounce search to avoid too many API calls
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.currentPage = 1; // Reset to first page when searching
        this.fetchStockages(1);
      }, 300);
    },

    setActiveTab(tab) {
      this.activeTab = tab;
      this.filterStockages();
    },
    onTabChange(event) {
      const tabs = ['all', 'active', 'maintenance'];
      this.activeTab = tabs[event.index];
      this.filterStockages();
    },
    onPage(event) {
      this.currentPage = event.page + 1;
      this.fetchStockages(this.currentPage);
    },
    applyFilters() {
      this.filterStockages();
    },
    clearFilters() {
      this.serviceFilter = '';
      this.warehouseTypeFilter = '';
      this.filterStockages();
    },
    filterStockages() {
      let filtered = [...this.stockages];

      // Filter by tab
      if (this.activeTab === 'active') {
        filtered = filtered.filter(stockage => stockage.status === 'active');
      } else if (this.activeTab === 'maintenance') {
        filtered = filtered.filter(stockage => stockage.status === 'maintenance');
      }

      // Filter by service
      if (this.serviceFilter) {
        filtered = filtered.filter(stockage => stockage.service_id == this.serviceFilter);
      }

      // Filter by warehouse type
      if (this.warehouseTypeFilter) {
        filtered = filtered.filter(stockage => stockage.warehouse_type === this.warehouseTypeFilter);
      }

      // Filter by search query
      if (this.searchQuery.trim()) {
        const query = this.searchQuery.toLowerCase();
        filtered = filtered.filter(stockage =>
          stockage.name.toLowerCase().includes(query) ||
          stockage.description?.toLowerCase().includes(query) ||
          stockage.location.toLowerCase().includes(query)
        );
      }

      this.filteredStockages = filtered;
    },
    viewStockage(stockage) {
      this.$router.push({ name: 'pharmacy.stockages.detail', params: { id: stockage.id } });
    },
    viewStock(stockage) {
      this.$router.push({ name: 'pharmacy.stockages.stock', params: { id: stockage.id } });
    },
    editStockage(stockage) {
      this.editingStockage = { ...stockage };
      this.showEditStockageModal = true;
  // refresh services to populate dropdown
  this.fetchServices();
    },
    async deleteStockage(stockage) {
      if (!confirm(`Are you sure you want to delete "${stockage.name}"? This action cannot be undone.`)) {
        return;
      }

      try {
        const response = await axios.delete(`/api/pharmacy/stockages/${stockage.id}`);
        if (response.data.success) {
          // Refresh the current page to get updated data
          await this.fetchStockages(this.currentPage);
          this.submitSuccess = true;
          setTimeout(() => {
            this.submitSuccess = false;
          }, 3000);
        }
      } catch (error) {
        this.submitError = 'Failed to delete stockage. Please try again.';
        setTimeout(() => {
          this.submitError = null;
        }, 5000);
      }
    },
    closeModal() {
      this.selectedStockage = null;
    },
    openAddStockageModal() {
      this.showAddStockageModal = true;
      this.resetNewStockage();
  // refresh services to populate dropdown
  this.fetchServices();
    },
    closeAddStockageModal() {
      this.showAddStockageModal = false;
      this.resetNewStockage();
    },
    onStockageCreated(createdStockage) {
      // Refresh the current page to get updated data
      this.fetchStockages(this.currentPage);
      this.submitSuccess = true;
      setTimeout(() => {
        this.submitSuccess = false;
      }, 3000);
    },
    closeEditStockageModal() {
      this.showEditStockageModal = false;
      this.editingStockage = null;
    },
    resetNewStockage() {
      this.newStockage = {
        name: '',
        description: '',
        location: '',
        type: '',
        capacity: null,
        status: 'active',
        service_id: null,
        temperature_controlled: false,
        security_level: 'medium',
        location_code: '',
        warehouse_type: ''
      };
    },
    async addStockage() {
      this.isSubmitting = true;
      this.submitError = null;
      this.submitSuccess = false;

      try {
        const response = await axios.post('/api/pharmacy/stockages', this.newStockage);
        if (response.data.success) {
          this.submitSuccess = true;
          // Refresh the current page to get updated data
          await this.fetchStockages(this.currentPage);
          this.closeAddStockageModal();

          // Show success message briefly
          setTimeout(() => {
            this.submitSuccess = false;
          }, 3000);
        }
      } catch (error) {
        if (error.response && error.response.data.errors) {
          this.submitError = 'Validation failed: ' + Object.values(error.response.data.errors).flat().join(', ');
        } else {
          this.submitError = 'Error adding stockage. Please try again.';
        }
      } finally {
        this.isSubmitting = false;
      }
    },
    async updateStockage() {
      this.isSubmitting = true;
      this.submitError = null;
      this.submitSuccess = false;

      try {
        const response = await axios.put(`/api/pharmacy/stockages/${this.editingStockage.id}`, this.editingStockage);
        if (response.data.success) {
          this.submitSuccess = true;
          // Refresh the current page to get updated data
          await this.fetchStockages(this.currentPage);
          this.closeEditStockageModal();

          // Show success message briefly
          setTimeout(() => {
            this.submitSuccess = false;
          }, 3000);
        }
      } catch (error) {
        if (error.response && error.response.data.errors) {
          this.submitError = 'Validation failed: ' + Object.values(error.response.data.errors).flat().join(', ');
        } else {
          this.submitError = 'Error updating stockage. Please try again.';
        }
      } finally {
        this.isSubmitting = false;
      }
    },

    // Pagination methods
    goToPage(page) {
      if (page >= 1 && page <= this.lastPage) {
        this.currentPage = page;
        this.fetchStockages(page);
      }
    },

    nextPage() {
      if (this.currentPage < this.lastPage) {
        this.goToPage(this.currentPage + 1);
      }
    },

    prevPage() {
      if (this.currentPage > 1) {
        this.goToPage(this.currentPage - 1);
      }
    },

    // Helper methods
    getTypeClass(type) {
      const classes = {
        'warehouse': 'tw-bg-blue-100 tw-text-blue-800',
        'pharmacy': 'tw-bg-green-100 tw-text-green-800',
        'laboratory': 'tw-bg-purple-100 tw-text-purple-800',
        'emergency': 'tw-bg-red-100 tw-text-red-800',
        'storage_room': 'tw-bg-yellow-100 tw-text-yellow-800',
        'cold_room': 'tw-bg-cyan-100 tw-text-cyan-800'
      };
      return classes[type] || 'tw-bg-gray-100 tw-text-gray-800';
    },

    getTypeLabel(type) {
      const labels = {
        'warehouse': 'Warehouse',
        'pharmacy': 'Pharmacy',
        'laboratory': 'Laboratory',
        'emergency': 'Emergency',
        'storage_room': 'Storage Room',
        'cold_room': 'Cold Room'
      };
      return labels[type] || type;
    },

    getStatusClass(status) {
      const classes = {
        'active': 'tw-text-green-500',
        'inactive': 'tw-text-gray-500',
        'maintenance': 'tw-text-yellow-500',
        'under_construction': 'tw-text-orange-500'
      };
      return classes[status] || 'tw-text-gray-500';
    },

    getStatusRowClass(status) {
      const classes = {
        'active': '',
        'inactive': 'tw-bg-gray-50',
        'maintenance': 'tw-bg-yellow-50',
        'under_construction': 'tw-bg-orange-50'
      };
      return classes[status] || '';
    },

    getStatusLabel(status) {
      const labels = {
        'active': 'Active',
        'inactive': 'Inactive',
        'maintenance': 'Maintenance',
        'under_construction': 'Under Construction'
      };
      return labels[status] || status;
    },

    getSecurityLevelLabel(level) {
      const labels = {
        'low': 'Low',
        'medium': 'Medium',
        'high': 'High',
        'restricted': 'Restricted'
      };
      return labels[level] || level;
    },

    // PrimeVue helper methods
    getTypeSeverity(type) {
      const severities = {
        'warehouse': 'info',
        'pharmacy': 'success',
        'laboratory': 'warning',
        'emergency': 'danger',
        'storage_room': 'secondary',
        'cold_room': 'info'
      };
      return severities[type] || 'info';
    },

    getStatusSeverity(status) {
      const severities = {
        'active': 'success',
        'inactive': 'secondary',
        'maintenance': 'warning',
        'under_construction': 'info'
      };
      return severities[status] || 'info';
    }
  }
}
</script>

<style scoped>
/* Custom animations and styles */
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

@keyframes slide-in {
  from {
    opacity: 0;
    transform: translateX(-20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.tw-animate-fade-in {
  animation: fade-in 0.3s ease-out;
}

.tw-animate-slide-in {
  animation: slide-in 0.4s ease-out;
}

/* Enhanced button hover effects */
.p-button:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Custom scrollbar for modal */
.tw-overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.tw-overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

.tw-overflow-y-auto::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 10px;
}

.tw-overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

/* Gradient text effect */
.tw-gradient-text {
  background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

/* Card hover effects */
.p-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

/* DataTable row hover */
.p-datatable .p-datatable-tbody > tr:hover {
  background-color: rgba(59, 130, 246, 0.05);
  transform: scale(1.01);
  transition: all 0.2s ease;
}

/* Custom focus styles */
.tw-border-gray-300:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
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

.tw-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
