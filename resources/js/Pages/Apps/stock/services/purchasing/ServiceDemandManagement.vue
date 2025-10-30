<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-blue-50 tw-via-indigo-50 tw-to-purple-50 tw-p-6">
    <Toast position="top-right" />
    <ConfirmDialog />

    <!-- Header -->
    <Card class="tw-mb-8 tw-bg-gradient-to-r tw-from-blue-600 tw-to-indigo-700 tw-text-white tw-shadow-xl tw-border-0">
      <template #content>
        <div class="tw-flex tw-flex-col md:tw-flex-row tw-justify-between tw-items-start md:tw-items-center tw-gap-4">
          <div class="tw-flex tw-items-start tw-gap-3 tw-flex-1">
            <Button
              v-if="serviceId"
              @click="backToServices"
              icon="pi pi-arrow-left"
              class="p-button-text p-button-rounded tw-text-white hover:tw-bg-white/20 tw-mt-1"
              v-tooltip.bottom="'Back to Services'"
              aria-label="Back to Services"
            />
            <div>
              <h1 class="tw-text-2xl md:tw-text-3xl tw-font-bold tw-mb-2 tw-flex tw-items-center tw-flex-wrap tw-gap-2">
                <i class="pi pi-shopping-cart"></i>
                <span>{{ selectedServiceName ? `${selectedServiceName} - Demands` : 'Service Demand Management' }}</span>
              </h1>
              <p class="tw-text-blue-100 tw-text-sm md:tw-text-base">
                {{ selectedServiceName ? `Manage demands for ${selectedServiceName}` : 'Manage purchasing demands for services' }}
              </p>
            </div>
          </div>
          <div class="tw-text-left md:tw-text-right tw-bg-white/10 tw-rounded-lg tw-px-6 tw-py-3 tw-backdrop-blur-sm">
            <div class="tw-text-3xl tw-font-bold tw-mb-1">{{ stats.total_demands || 0 }}</div>
            <div class="tw-text-blue-100 tw-text-sm tw-font-medium tw-uppercase tw-tracking-wide">Total Demands</div>
          </div>
        </div>
      </template>
    </Card>

    <!-- Tab Navigation -->
    <div class="tw-mb-6">
      <TabView :activeIndex="activeTabIndex" @tab-change="onTabChange">
        <TabPanel header="My Demands">
          <template #header>
            <i class="pi pi-user tw-mr-2"></i>
            <span>My Demands</span>
          </template>
        </TabPanel>
        <TabPanel header="All Demands">
          <template #header>
            <i class="pi pi-list tw-mr-2"></i>
            <span>All Demands</span>
          </template>
        </TabPanel>
      </TabView>
    </div>

    <!-- Stats Cards -->
    <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-4 tw-gap-4 md:tw-gap-6 tw-mb-8">
      <Card class="tw-shadow-lg tw-border-0 tw-overflow-hidden hover:tw-shadow-xl tw-transition-shadow tw-duration-300">
        <template #content>
          <div class="tw-flex tw-items-center tw-justify-between tw-p-2">
            <div class="tw-flex-1">
              <div class="tw-text-3xl tw-font-extrabold tw-text-gray-900 tw-mb-1">{{ stats.draft_demands || 0 }}</div>
              <div class="tw-text-xs tw-font-medium tw-text-gray-600 tw-uppercase tw-tracking-wide">Draft Demands</div>
            </div>
            <div class="tw-w-14 tw-h-14 tw-bg-gradient-to-br tw-from-yellow-400 tw-to-yellow-600 tw-rounded-2xl tw-flex tw-items-center tw-justify-center tw-shadow-lg">
              <i class="pi pi-file-edit tw-text-white tw-text-2xl"></i>
            </div>
          </div>
        </template>
      </Card>

      <Card class="tw-shadow-lg tw-border-0 tw-overflow-hidden hover:tw-shadow-xl tw-transition-shadow tw-duration-300">
        <template #content>
          <div class="tw-flex tw-items-center tw-justify-between tw-p-2">
            <div class="tw-flex-1">
              <div class="tw-text-3xl tw-font-extrabold tw-text-gray-900 tw-mb-1">{{ stats.sent_demands || 0 }}</div>
              <div class="tw-text-xs tw-font-medium tw-text-gray-600 tw-uppercase tw-tracking-wide">Sent Demands</div>
            </div>
            <div class="tw-w-14 tw-h-14 tw-bg-gradient-to-br tw-from-blue-400 tw-to-blue-600 tw-rounded-2xl tw-flex tw-items-center tw-justify-center tw-shadow-lg">
              <i class="pi pi-send tw-text-white tw-text-2xl"></i>
            </div>
          </div>
        </template>
      </Card>

      <Card class="tw-shadow-lg tw-border-0 tw-overflow-hidden hover:tw-shadow-xl tw-transition-shadow tw-duration-300">
        <template #content>
          <div class="tw-flex tw-items-center tw-justify-between tw-p-2">
            <div class="tw-flex-1">
              <div class="tw-text-3xl tw-font-extrabold tw-text-gray-900 tw-mb-1">{{ stats.approved_demands || 0 }}</div>
              <div class="tw-text-xs tw-font-medium tw-text-gray-600 tw-uppercase tw-tracking-wide">Approved Demands</div>
            </div>
            <div class="tw-w-14 tw-h-14 tw-bg-gradient-to-br tw-from-green-400 tw-to-green-600 tw-rounded-2xl tw-flex tw-items-center tw-justify-center tw-shadow-lg">
              <i class="pi pi-check-circle tw-text-white tw-text-2xl"></i>
            </div>
          </div>
        </template>
      </Card>

      <Card class="tw-shadow-lg tw-border-0 tw-overflow-hidden hover:tw-shadow-xl tw-transition-shadow tw-duration-300">
        <template #content>
          <div class="tw-flex tw-items-center tw-justify-between tw-p-2">
            <div class="tw-flex-1">
              <div class="tw-text-3xl tw-font-extrabold tw-text-gray-900 tw-mb-1">{{ stats.total_items || 0 }}</div>
              <div class="tw-text-xs tw-font-medium tw-text-gray-600 tw-uppercase tw-tracking-wide">Total Items</div>
            </div>
            <div class="tw-w-14 tw-h-14 tw-bg-gradient-to-br tw-from-purple-400 tw-to-purple-600 tw-rounded-2xl tw-flex tw-items-center tw-justify-center tw-shadow-lg">
              <i class="pi pi-box tw-text-white tw-text-2xl"></i>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Main Content -->
    <Card class="tw-shadow-xl">
      <template #title>
        <div class="tw-flex tw-justify-between tw-items-center">
          <div class="tw-flex tw-items-center">
            <i class="pi pi-list tw-mr-3 tw-text-blue-600"></i>
            <span class="tw-text-xl tw-font-semibold">Demands List</span>
          </div>
          <Button 
            label="Create New Demand" 
            icon="pi pi-plus" 
            class="tw-bg-gradient-to-r tw-from-blue-500 tw-to-indigo-600 tw-border-0"
            @click="goToCreatePage"
          />
        </div>
      </template>

      <template #content>
        <!-- Filters -->
        <div class="tw-mb-6 tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-4">
          <div class="tw-flex tw-flex-col">
            <label class="tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Search</label>
            <span class="p-input-icon-left">
              <i class="pi pi-search"></i>
              <InputText 
                v-model="filters.global.value" 
                placeholder="Search demands..." 
                class="tw-w-full"
              />
            </span>
          </div>

          <div class="tw-flex tw-flex-col">
            <label class="tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Status</label>
            <Dropdown
              v-model="statusFilter"
              :options="statusOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="All Status"
              class="tw-w-full"
              @change="loadDemands"
            />
          </div>

          <div class="tw-flex tw-flex-col">
            <label class="tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Service</label>
            <Dropdown
              v-model="serviceFilter"
              :options="services"
              optionLabel="name"
              optionValue="id"
              placeholder="All Services"
              class="tw-w-full"
              :disabled="!!serviceId"
              @change="loadDemands"
            />
            <small v-if="serviceId" class="tw-text-gray-500 tw-mt-1">
              <i class="pi pi-info-circle tw-mr-1"></i>
              Filtering by specific service
            </small>
          </div>

          <div class="tw-flex tw-flex-col tw-justify-end">
            <Button 
              label="Clear Filters" 
              icon="pi pi-times" 
              class="p-button-outlined"
              @click="clearFilters"
            />
          </div>
        </div>

        <!-- Data Table -->
        <DataTable 
          :value="demands" 
          :loading="loading"
          :filters="filters"
          :globalFilterFields="['demand_code', 'notes', 'service.name']"
          paginator 
          :rows="10" 
          :rowsPerPageOptions="[5, 10, 20, 50]"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords} demands"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          responsiveLayout="scroll"
          stripedRows
          class="tw-mt-4"
        >
          <template #empty>
            <div class="tw-text-center tw-py-12">
              <i class="pi pi-inbox tw-text-6xl tw-text-gray-300 tw-mb-4"></i>
              <h3 class="tw-text-xl tw-font-medium tw-text-gray-900 tw-mb-2">No demands found</h3>
              <p class="tw-text-gray-500 tw-mb-6">Get started by creating your first demand.</p>
              <Button 
                label="Create Demand" 
                icon="pi pi-plus" 
                @click="goToCreatePage"
              />
            </div>
          </template>

          <Column field="demand_code" header="Demand Code" sortable>
            <template #body="slotProps">
              <div class="tw-flex tw-items-center">
                <i class="pi pi-hashtag tw-mr-2 tw-text-gray-400"></i>
                <span class="tw-font-mono tw-font-medium">{{ slotProps.data.demand_code }}</span>
              </div>
            </template>
          </Column>

          <Column field="service.name" header="Service" sortable>
            <template #body="slotProps">
              <div class="tw-flex tw-items-center">
                <i class="pi pi-cog tw-mr-2 tw-text-blue-500"></i>
                <span>{{ slotProps.data.service?.name || 'N/A' }}</span>
              </div>
            </template>
          </Column>

          <Column field="expected_date" header="Expected Date" sortable>
            <template #body="slotProps">
              <div class="tw-flex tw-items-center">
                <i class="pi pi-calendar tw-mr-2 tw-text-green-500"></i>
                <span>{{ formatDate(slotProps.data.expected_date) }}</span>
              </div>
            </template>
          </Column>

          <Column field="status" header="Status" sortable>
            <template #body="slotProps">
              <Tag 
                :value="slotProps.data.status" 
                :severity="getStatusSeverity(slotProps.data.status)"
                class="tw-font-medium"
              />
            </template>
          </Column>

          <Column field="items" header="Items" sortable>
            <template #body="slotProps">
              <div class="tw-flex tw-items-center">
                <i class="pi pi-box tw-mr-2 tw-text-purple-500"></i>
                <span class="tw-font-medium">{{ slotProps.data.items?.length || 0 }} items</span>
              </div>
            </template>
          </Column>

          <Column field="created_at" header="Created" sortable>
            <template #body="slotProps">
              <div class="tw-text-sm tw-text-gray-600">
                {{ formatDateTime(slotProps.data.created_at) }}
              </div>
            </template>
          </Column>

          <Column header="Actions" :exportable="false">
            <template #body="slotProps">
              <div class="tw-flex tw-gap-2">
                <Button 
                  icon="pi pi-eye" 
                  class="p-button-rounded p-button-text p-button-sm tw-text-blue-600"
                  v-tooltip.top="'View Details'"
                  @click="viewDemand(slotProps.data)"
                />
                <Button 
                  v-if="canEditDemand(slotProps.data)"
                  icon="pi pi-pencil" 
                  class="p-button-rounded p-button-text p-button-sm tw-text-orange-600"
                  v-tooltip.top="'Edit Draft'"
                  @click="editDemand(slotProps.data)"
                />
                <Button 
                  icon="pi pi-send" 
                  class="p-button-rounded p-button-text p-button-sm tw-text-green-600"
                  v-tooltip.top="'Send Demand'"
                  @click="sendDemand(slotProps.data)"
                />
                <Button 
                  v-if="canDeleteDemand(slotProps.data)"
                  icon="pi pi-trash" 
                  class="p-button-rounded p-button-text p-button-sm tw-text-red-600"
                  v-tooltip.top="'Delete Draft'"
                  @click="deleteDemand(slotProps.data)"
                />
                <!-- Show status info for non-draft items -->
                <span 
                  v-if="!canEditDemand(slotProps.data)" 
                  class="tw-text-sm tw-text-gray-500 tw-italic tw-flex tw-items-center"
                  v-tooltip.top="'Cannot edit - Status: ' + slotProps.data.status"
                >
                  <i class="pi pi-lock tw-mr-1"></i>
                  {{ slotProps.data.status }}
                </span>
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Edit Demand Dialog -->
    <Dialog :visible="showEditDialog" @update:visible="showEditDialog = $event" modal :header="`Edit Demand: ${selectedDemand?.demand_code}`" :style="{width: '70rem'}">
      <div v-if="selectedDemand" class="tw-space-y-6">
        <!-- Basic Information -->
        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
          <div class="tw-flex tw-flex-col">
            <label class="tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Service *</label>
            <Dropdown
              v-model="selectedDemand.service_id"
              :options="services"
              optionLabel="name"
              optionValue="id"
              placeholder="Select a service"
              class="tw-w-full"
              :loading="loadingServices"
            />
          </div>

          <div class="tw-flex tw-flex-col">
            <label class="tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Expected Date</label>
            <Calendar
              v-model="selectedDemand.expected_date"
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
            v-model="selectedDemand.notes"
            rows="3"
            placeholder="Additional notes or requirements..."
            class="tw-w-full"
          />
        </div>

        <!-- Items Section -->
        <div class="tw-border-t tw-pt-6">
          <div class="tw-flex tw-justify-between tw-items-center tw-mb-4">
            <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900">Items</h3>
            <Button 
              label="Add Product" 
              icon="pi pi-plus" 
              class="p-button-sm"
              @click="showAddProductDialog = true"
            />
          </div>

          <DataTable 
            :value="selectedDemand.items || []" 
            class="tw-mt-4"
            responsiveLayout="scroll"
          >
            <template #empty>
              <div class="tw-text-center tw-py-8">
                <i class="pi pi-box tw-text-4xl tw-text-gray-300 tw-mb-2"></i>
                <p class="tw-text-gray-500">No items added yet. Add some products to this demand.</p>
              </div>
            </template>

            <Column field="product.name" header="Product">
              <template #body="slotProps">
                <div class="tw-flex tw-items-center">
                  <i class="pi pi-box tw-mr-2 tw-text-purple-500"></i>
                  <span>{{ slotProps.data.product?.name }}</span>
                </div>
              </template>
            </Column>

            <Column field="quantity" header="Quantity">
              <template #body="slotProps">
                <span class="tw-font-medium">{{ slotProps.data.quantity }} {{ slotProps.data.product?.unit }}</span>
              </template>
            </Column>

            <Column field="unit_price" header="Unit Price">
              <template #body="slotProps">
                <span v-if="slotProps.data.unit_price" class="tw-font-medium">${{ slotProps.data.unit_price }}</span>
                <span v-else class="tw-text-gray-400">Not set</span>
              </template>
            </Column>

            <Column field="total_price" header="Total">
              <template #body="slotProps">
                <span v-if="slotProps.data.unit_price" class="tw-font-bold tw-text-green-600">
                  ${{ (slotProps.data.quantity * slotProps.data.unit_price).toFixed(2) }}
                </span>
                <span v-else class="tw-text-gray-400">-</span>
              </template>
            </Column>

            <Column header="Actions" :exportable="false">
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
        </div>
      </div>

      <template #footer>
        <div class="tw-flex tw-justify-end tw-gap-3">
          <Button 
            label="Cancel" 
            icon="pi pi-times" 
            class="p-button-text" 
            @click="closeEditDialog"
          />
          <Button 
            v-if="canEditDemand(selectedDemand)"
            label="Save Draft" 
            icon="pi pi-save" 
            :loading="savingDraft"
            @click="saveDraft"
          />
          <Button 
            v-if="canSendDemand(selectedDemand)"
            label="Send Demand" 
            icon="pi pi-send" 
            :loading="sendingDraft"
            @click="confirmSendDraft"
          />
        </div>
      </template>
    </Dialog>

    <!-- Add Product Dialog -->
    <Dialog :visible="showAddProductDialog" @update:visible="showAddProductDialog = $event" modal header="Add Product to Demand" :style="{width: '50rem'}">
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
                :filter="true"
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
            @click="closeAddProductDialog"
          />
          <Button 
            label="Add Item" 
            icon="pi pi-plus" 
            :loading="addingItem"
            :disabled="!newItem.product_id || !newItem.quantity || newItem.quantity <= 0"
            @click="addProductToDemand"
          />
        </div>
      </template>
    </Dialog>

    <!-- Create Demand Dialog -->
    <Dialog :visible="showCreateDialog" @update:visible="showCreateDialog = $event" modal header="Create New Service Demand" :style="{width: '50rem'}">
      <div class="tw-space-y-6">
        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
          <div class="tw-flex tw-flex-col">
            <label class="tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Service *</label>
            <Dropdown
              v-model="newDemand.service_id"
              :options="services"
              optionLabel="name"
              optionValue="id"
              placeholder="Select a service"
              class="tw-w-full"
              :class="{ 'p-invalid': !newDemand.service_id }"
              :loading="loadingServices"
            />
          </div>

          <div class="tw-flex tw-flex-col">
            <label class="tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Expected Date *</label>
            <Calendar
              v-model="newDemand.expected_date"
              placeholder="Select expected delivery date"
              class="tw-w-full"
              :class="{ 'p-invalid': !newDemand.expected_date }"
              :minDate="minDate"
              showIcon
              dateFormat="yy-mm-dd"
            />
          </div>
        </div>

        <div class="tw-flex tw-flex-col">
          <label class="tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Notes</label>
          <Textarea
            v-model="newDemand.notes"
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
            @click="closeCreateDialog"
          />
          <Button 
            label="Create & Add Products" 
            icon="pi pi-plus" 
            :loading="creatingDraft"
            :disabled="!newDemand.service_id || !newDemand.expected_date"
            @click="createDemandAndRedirect"
          />
        </div>
      </template>
    </Dialog>

    <!-- Demand Details Dialog -->
    <Dialog :visible="showDetailsDialog" @update:visible="showDetailsDialog = $event" modal :header="`Demand Details: ${selectedDemand?.demand_code}`" :style="{width: '70rem'}">
      <div v-if="selectedDemand" class="tw-space-y-6">
        <!-- Basic Information -->
        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
          <div class="tw-space-y-4">
            <div>
              <label class="tw-text-sm tw-font-medium tw-text-gray-700">Demand Code</label>
              <div class="tw-text-lg tw-font-mono tw-font-bold tw-text-gray-900">{{ selectedDemand.demand_code }}</div>
            </div>
            <div>
              <label class="tw-text-sm tw-font-medium tw-text-gray-700">Service</label>
              <div class="tw-text-lg tw-text-gray-900">{{ selectedDemand.service?.name }}</div>
            </div>
            <div>
              <label class="tw-text-sm tw-font-medium tw-text-gray-700">Status</label>
              <div>
                <Tag 
                  :value="selectedDemand.status" 
                  :severity="getStatusSeverity(selectedDemand.status)"
                  class="tw-font-medium"
                />
              </div>
            </div>
          </div>
          <div class="tw-space-y-4">
            <div>
              <label class="tw-text-sm tw-font-medium tw-text-gray-700">Expected Date</label>
              <div class="tw-text-lg tw-text-gray-900">{{ formatDate(selectedDemand.expected_date) }}</div>
            </div>
            <div>
              <label class="tw-text-sm tw-font-medium tw-text-gray-700">Created</label>
              <div class="tw-text-lg tw-text-gray-900">{{ formatDateTime(selectedDemand.created_at) }}</div>
            </div>
            <div v-if="selectedDemand.notes">
              <label class="tw-text-sm tw-font-medium tw-text-gray-700">Notes</label>
              <div class="tw-text-gray-900">{{ selectedDemand.notes }}</div>
            </div>
          </div>
        </div>

        <!-- Items -->
        <div class="tw-border-t tw-pt-6">
          <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-4">Items ({{ selectedDemand.items?.length || 0 }})</h3>
          
          <DataTable 
            :value="selectedDemand.items || []" 
            responsiveLayout="scroll"
          >
            <template #empty>
              <div class="tw-text-center tw-py-8">
                <i class="pi pi-box tw-text-4xl tw-text-gray-300 tw-mb-2"></i>
                <p class="tw-text-gray-500">No items in this demand.</p>
              </div>
            </template>

            <Column field="product.name" header="Product">
              <template #body="slotProps">
                <div class="tw-flex tw-items-center">
                  <i class="pi pi-box tw-mr-2 tw-text-purple-500"></i>
                  <div>
                    <div class="tw-font-medium">{{ slotProps.data.product?.name }}</div>
                    <div class="tw-text-sm tw-text-gray-500">{{ slotProps.data.product?.product_code }}</div>
                  </div>
                </div>
              </template>
            </Column>

            <Column field="quantity" header="Quantity">
              <template #body="slotProps">
                <span class="tw-font-medium">{{ slotProps.data.quantity }} {{ slotProps.data.product?.unit }}</span>
              </template>
            </Column>

            <Column field="unit_price" header="Unit Price">
              <template #body="slotProps">
                <span v-if="slotProps.data.unit_price" class="tw-font-medium">${{ slotProps.data.unit_price }}</span>
                <span v-else class="tw-text-gray-400">Not set</span>
              </template>
            </Column>

            <Column field="total_price" header="Total">
              <template #body="slotProps">
                <span v-if="slotProps.data.unit_price" class="tw-font-bold tw-text-green-600">
                  ${{ (slotProps.data.quantity * slotProps.data.unit_price).toFixed(2) }}
                </span>
                <span v-else class="tw-text-gray-400">-</span>
              </template>
            </Column>

            <Column field="status" header="Status">
              <template #body="slotProps">
                <Tag 
                  :value="slotProps.data.status" 
                  :severity="getStatusSeverity(slotProps.data.status)"
                  class="tw-font-medium"
                />
              </template>
            </Column>
          </DataTable>
        </div>
      </div>

      <template #footer>
        <div class="tw-flex tw-justify-end tw-gap-3">
          <Button 
            label="Close" 
            icon="pi pi-times" 
            class="p-button-text" 
            @click="showDetailsDialog = false"
          />
          <Button 
            v-if="canEditDemand(selectedDemand)"
            label="Edit Demand" 
            icon="pi pi-pencil" 
            @click="editDemandFromDetails"
          />
          <Button 
            v-if="canSendDemand(selectedDemand)"
            label="Send Demand" 
            icon="pi pi-send" 
            @click="sendDemand(selectedDemand)"
          />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
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
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';

// Props
const props = defineProps({
  serviceId: {
    type: [String, Number],
    default: null
  }
});

// Composables
const router = useRouter();
const toast = useToast();
const confirm = useConfirm();

// State
const activeTab = ref('my_demands');
const activeTabIndex = ref(0);
const demands = ref([]);
const services = ref([]);
const allServices = ref([]);
const userSpecializationServices = ref([]);
const availableProducts = ref([]);
const stats = ref({});
const currentUser = ref(null);
const loading = ref(false);
const loadingServices = ref(false);
const loadingProducts = ref(false);
const creatingDraft = ref(false);
const savingDraft = ref(false);
const sendingDraft = ref(false);
const addingItem = ref(false);

const showEditDialog = ref(false);
const showAddProductDialog = ref(false);
const showDetailsDialog = ref(false);
const showCreateDialog = ref(false);

const selectedDemand = ref(null);
const selectedServiceName = ref(null);
const newDemand = ref({
  service_id: null,
  expected_date: null,
  notes: ''
});
const filters = ref({
  global: { value: null, matchMode: 'contains' }
});
const statusFilter = ref(null);
const serviceFilter = ref(null);
const statusOptions = ref([
  { label: 'All Status', value: null },
  { label: 'Draft', value: 'draft' },
  { label: 'Sent', value: 'sent' },
  { label: 'Approved', value: 'approved' },
  { label: 'Rejected', value: 'rejected' },
  { label: 'Completed', value: 'completed' }
]);
const newItem = ref({
  product_id: null,
  quantity: 1,
  unit_price: null,
  notes: ''
});

const minDate = ref(new Date());

// Computed
const filteredDemands = computed(() => demands.value);

// Lifecycle
onMounted(() => {
  // If serviceId is provided, set it as the filter
  if (props.serviceId) {
    serviceFilter.value = parseInt(props.serviceId);
  }
  loadInitialData();
});

// Methods
const loadInitialData = async () => {
  loading.value = true;
  try {
    await Promise.all([
      loadCurrentUser(),
      loadDemands(),
      loadServices(),
      loadStats(),
      loadProducts()
    ]);
  } catch (error) {
    console.error('Failed to load initial data:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load initial data',
      life: 3000
    });
  } finally {
    loading.value = false;
  }
};

const loadCurrentUser = async () => {
  try {
    const response = await axios.get('/api/loginuser');
    currentUser.value = response.data.data;
  } catch (error) {
    console.error('Failed to load current user:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load user information',
      life: 3000
    });
  }
};

const loadDemands = async () => {
  try {
    const params = {};
    if (statusFilter.value) params.status = statusFilter.value;
    if (serviceFilter.value) params.service_id = serviceFilter.value;
    if (filters.value.global.value) params.search = filters.value.global.value;

    const response = await axios.get('/api/service-demands', { params });
    demands.value = response.data.data.data || [];
  } catch (error) {
    console.error('Failed to load demands:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load demands',
      life: 3000
    });
  }
};

const loadServices = async () => {
  loadingServices.value = true;
  try {
    const response = await axios.get('/api/service-demands/meta/services');
    allServices.value = response.data.data || [];
    
    // If serviceId prop is provided, find and set the service name
    if (props.serviceId) {
      const selectedService = allServices.value.find(s => s.id == props.serviceId);
      if (selectedService) {
        selectedServiceName.value = selectedService.name;
        // Also set it in newDemand for pre-selection when creating new demands
        newDemand.value.service_id = parseInt(props.serviceId);
      }
    }
    
    // Filter services based on user's specialization
    if (currentUser.value && currentUser.value.specialization_id) {
      // Get services that belong to user's specialization
      const specializationResponse = await axios.get(`/api/specializations`);
      const specializations = specializationResponse.data.data || [];
      
      // Find user's specialization
      const userSpecialization = specializations.find(spec => spec.id === currentUser.value.specialization_id);
      
      if (userSpecialization && userSpecialization.service_id) {
        // Filter services to show only the user's specialization service and its related services
        userSpecializationServices.value = allServices.value.filter(service => 
          service.id === userSpecialization.service_id
        );
        
        // Set services to user's specialization services for dropdown
        services.value = userSpecializationServices.value;
      } else {
        // If no specialization service found, show all services
        services.value = allServices.value;
      }
    } else {
      // If no user specialization, show all services
      services.value = allServices.value;
    }
  } catch (error) {
    console.error('Failed to load services:', error);
    // Fallback to all services
    services.value = allServices.value;
  } finally {
    loadingServices.value = false;
  }
};

const loadStats = async () => {
  try {
    const response = await axios.get('/api/service-demands/meta/stats');
    stats.value = response.data.data || {};
  } catch (error) {
    console.error('Failed to load stats:', error);
  }
};

const loadProducts = async () => {
  loadingProducts.value = true;
  try {
    const response = await axios.get('/api/service-demands/meta/products');
    availableProducts.value = response.data.data || [];
  } catch (error) {
    console.error('Failed to load products:', error);
  } finally {
    loadingProducts.value = false;
  }
};

const onTabChange = (event) => {
  activeTab.value = event.index === 0 ? 'my_demands' : 'all_demands';
  loadDemands();
};

const goToCreatePage = () => {
  // Open create dialog instead of navigating
  resetNewDemand();
  showCreateDialog.value = true;
};

const createDemandAndRedirect = async () => {
  creatingDraft.value = true;
  try {
    const response = await axios.post('/api/service-demands', {
      service_id: newDemand.value.service_id,
      expected_date: newDemand.value.expected_date,
      notes: newDemand.value.notes
    });
    
    const newDemandData = response.data.data;
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Demand created successfully! Redirecting to add products...',
      life: 3000
    });
    
    closeCreateDialog();
    
    // Navigate to edit mode with the new demand
    router.push({
      path: `/stock/service-demands/edit/${newDemandData.id}`,
      query: { mode: 'edit', skipBasicInfo: 'true' }
    });
    
  } catch (error) {
    console.error('Error creating demand:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to create demand',
      life: 3000
    });
  } finally {
    creatingDraft.value = false;
  }
};

const closeCreateDialog = () => {
  showCreateDialog.value = false;
  resetNewDemand();
};

const resetNewDemand = () => {
  newDemand.value = {
    service_id: props.serviceId ? parseInt(props.serviceId) : null,
    expected_date: null,
    notes: ''
  };
};

const editDemand = (demand) => {
  // Navigate to ServiceDemandCreate page in edit mode
  router.push({
    path: `/stock/service-demands/edit/${demand.id}`,
    query: { mode: 'edit' }
  });
};

const saveDraft = async () => {
  savingDraft.value = true;
  try {
    await axios.put(`/api/service-demands/${selectedDemand.value.id}`, {
      service_id: selectedDemand.value.service_id,
      expected_date: selectedDemand.value.expected_date,
      notes: selectedDemand.value.notes
    });
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Demand updated successfully',
      life: 3000
    });
    closeEditDialog();
    loadDemands();
  } catch (error) {
    console.error('Error saving demand:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to save demand',
      life: 3000
    });
  } finally {
    savingDraft.value = false;
  }
};

const sendDemand = async (demand) => {
  confirm.require({
    message: `Are you sure you want to send demand "${demand.demand_code}"? Once sent, you cannot edit it anymore.`,
    header: 'Send Demand',
    icon: 'pi pi-exclamation-triangle',
    rejectClass: 'p-button-text',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        const response = await axios.post(`/api/service-demands/${demand.id}/send`);
        
        // Update the status in the local data immediately
        const demandIndex = demands.value.findIndex(d => d.id === demand.id);
        if (demandIndex !== -1) {
          demands.value[demandIndex].status = 'sent';
          demands.value[demandIndex] = { ...demands.value[demandIndex], status: 'sent' };
        }
        
        // If this demand is currently being viewed/edited, update it too
        if (selectedDemand.value && selectedDemand.value.id === demand.id) {
          selectedDemand.value.status = 'sent';
        }
        
        toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Demand sent successfully. Status changed to "Sent".',
          life: 3000
        });
        
        // Close edit dialog if it's open for this demand
        if (showEditDialog.value && selectedDemand.value && selectedDemand.value.id === demand.id) {
          showEditDialog.value = false;
        }
        
        // Reload data to ensure consistency
        loadDemands();
        loadStats();
        
      } catch (error) {
        console.error('Error sending demand:', error);
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error.response?.data?.message || 'Failed to send demand',
          life: 3000
        });
      }
    }
  });
};

const confirmSendDraft = () => {
  sendDemand(selectedDemand.value);
};

const addProductToDemand = async () => {
  addingItem.value = true;
  try {
    const response = await axios.post(`/api/service-demands/${selectedDemand.value.id}/items`, newItem.value);
    selectedDemand.value.items.push(response.data.data);
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Item added successfully',
      life: 3000
    });
    closeAddProductDialog();
    resetNewItem();
  } catch (error) {
    console.error('Error adding item:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to add item',
      life: 3000
    });
  } finally {
    addingItem.value = false;
  }
};

const removeItem = async (item) => {
  confirm.require({
    message: `Are you sure you want to remove "${item.product?.name}" from this demand?`,
    header: 'Remove Item',
    icon: 'pi pi-exclamation-triangle',
    rejectClass: 'p-button-text',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await axios.delete(`/api/service-demands/${selectedDemand.value.id}/items/${item.id}`);
        const index = selectedDemand.value.items.findIndex(i => i.id === item.id);
        if (index !== -1) {
          selectedDemand.value.items.splice(index, 1);
        }
        toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Item removed successfully',
          life: 3000
        });
      } catch (error) {
        console.error('Error removing item:', error);
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to remove item',
          life: 3000
        });
      }
    }
  });
};

const deleteDemand = async (demand) => {
  confirm.require({
    message: `Are you sure you want to delete demand "${demand.demand_code}"? This action cannot be undone.`,
    header: 'Delete Demand',
    icon: 'pi pi-exclamation-triangle',
    rejectClass: 'p-button-text',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await axios.delete(`/api/service-demands/${demand.id}`);
        toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Demand deleted successfully',
          life: 3000
        });
        loadDemands();
        loadStats();
      } catch (error) {
        console.error('Error deleting demand:', error);
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to delete demand',
          life: 3000
        });
      }
    }
  });
};

const viewDemand = (demand) => {
  // Navigate to ServiceDemandCreate page in view mode
  router.push({
    path: `/stock/service-demands/view/${demand.id}`,
    query: { mode: 'view' }
  });
};

const editDemandFromDetails = () => {
  // This method is no longer needed since we use navigation
  // but keeping for backward compatibility
  showDetailsDialog.value = false;
  showEditDialog.value = true;
};

const backToServices = () => {
  router.push({ name: 'pharmacy.services' });
};

const clearFilters = () => {
  filters.value.global.value = null;
  statusFilter.value = null;
  // Don't clear serviceFilter if serviceId prop is set
  if (!props.serviceId) {
    serviceFilter.value = null;
  }
  loadDemands();
};

const closeEditDialog = () => {
  showEditDialog.value = false;
  selectedDemand.value = null;
};

const closeAddProductDialog = () => {
  showAddProductDialog.value = false;
  resetNewItem();
};

const editItem = (item) => {
  // TODO: Implement edit item functionality
  console.log('Edit item:', item);
};

const resetNewItem = () => {
  newItem.value = {
    product_id: null,
    quantity: 1,
    unit_price: null,
    notes: ''
  };
};

const getStatusSeverity = (status) => {
  const severities = {
    draft: 'warning',
    sent: 'info',
    approved: 'success',
    rejected: 'danger',
    completed: 'success',
    pending: 'warning'
  };
  return severities[status] || 'info';
};

// Helper method to check if a demand can be edited
const canEditDemand = (demand) => {
  return demand && demand.status === 'draft';
};

// Helper method to check if a demand can be sent
const canSendDemand = (demand) => {
  return demand && demand.status === 'draft';
};

// Helper method to check if a demand can be deleted
const canDeleteDemand = (demand) => {
  return demand && demand.status === 'draft';
};

const formatDate = (date) => {
  if (!date) return 'Not set';
  return new Date(date).toLocaleDateString();
};

const formatDateTime = (date) => {
  if (!date) return 'Not set';
  return new Date(date).toLocaleString();
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