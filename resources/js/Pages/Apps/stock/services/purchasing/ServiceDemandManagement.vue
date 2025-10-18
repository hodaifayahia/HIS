<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-blue-50 tw-via-indigo-50 tw-to-purple-50 tw-p-6">
    <Toast position="top-right" />
    <ConfirmDialog />

    <!-- Header -->
    <Card class="tw-mb-8 tw-bg-gradient-to-r tw-from-blue-600 tw-to-indigo-700 tw-text-white tw-shadow-xl">
      <template #content>
        <div class="tw-flex tw-justify-between tw-items-center">
          <div>
            <h1 class="tw-text-3xl tw-font-bold tw-mb-2">
              <i class="pi pi-shopping-cart tw-mr-3"></i>
              Service Demand Management
            </h1>
            <p class="tw-text-blue-100 tw-text-lg">
              Manage purchasing demands for services
            </p>
          </div>
          <div class="tw-text-right">
            <div class="tw-text-2xl tw-font-bold">{{ stats.total_demands || 0 }}</div>
            <div class="tw-text-blue-100">Total Demands</div>
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
    <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-6 tw-mb-8">
      <Card class="tw-shadow-lg">
        <template #content>
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <div class="tw-text-2xl tw-font-bold tw-text-gray-900">{{ stats.draft_demands || 0 }}</div>
              <div class="tw-text-sm tw-text-gray-600">Draft Demands</div>
            </div>
            <div class="tw-w-12 tw-h-12 tw-bg-yellow-100 tw-rounded-full tw-flex tw-items-center tw-justify-center">
              <i class="pi pi-file-edit tw-text-yellow-600 tw-text-xl"></i>
            </div>
          </div>
        </template>
      </Card>

      <Card class="tw-shadow-lg">
        <template #content>
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <div class="tw-text-2xl tw-font-bold tw-text-gray-900">{{ stats.sent_demands || 0 }}</div>
              <div class="tw-text-sm tw-text-gray-600">Sent Demands</div>
            </div>
            <div class="tw-w-12 tw-h-12 tw-bg-blue-100 tw-rounded-full tw-flex tw-items-center tw-justify-center">
              <i class="pi pi-send tw-text-blue-600 tw-text-xl"></i>
            </div>
          </div>
        </template>
      </Card>

      <Card class="tw-shadow-lg">
        <template #content>
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <div class="tw-text-2xl tw-font-bold tw-text-gray-900">{{ stats.approved_demands || 0 }}</div>
              <div class="tw-text-sm tw-text-gray-600">Approved Demands</div>
            </div>
            <div class="tw-w-12 tw-h-12 tw-bg-green-100 tw-rounded-full tw-flex tw-items-center tw-justify-center">
              <i class="pi pi-check-circle tw-text-green-600 tw-text-xl"></i>
            </div>
          </div>
        </template>
      </Card>

      <Card class="tw-shadow-lg">
        <template #content>
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <div class="tw-text-2xl tw-font-bold tw-text-gray-900">{{ stats.total_items || 0 }}</div>
              <div class="tw-text-sm tw-text-gray-600">Total Items</div>
            </div>
            <div class="tw-w-12 tw-h-12 tw-bg-purple-100 tw-rounded-full tw-flex tw-items-center tw-justify-center">
              <i class="pi pi-box tw-text-purple-600 tw-text-xl"></i>
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
              @change="loadDemands"
            />
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
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';

export default {
  name: 'ServiceDemandManagement',
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
    Toast,
    ConfirmDialog
  },
  data() {
    return {
      activeTab: 'my_demands',
      activeTabIndex: 0,
      demands: [],
      services: [],
      allServices: [], // Store all services
      userSpecializationServices: [], // Store services for user's specialization
      availableProducts: [],
      stats: {},
      currentUser: null,
      loading: false,
      loadingServices: false,
      loadingProducts: false,
      creatingDraft: false,
      savingDraft: false,
      sendingDraft: false,
      addingItem: false,

      showEditDialog: false,
      showAddProductDialog: false,
      showDetailsDialog: false,
      showCreateDialog: false,

      selectedDemand: null,
      newDemand: {
        service_id: null,
        expected_date: null,
        notes: ''
      },
      filters: {
        global: { value: null, matchMode: 'contains' }
      },
      statusFilter: null,
      serviceFilter: null,
      statusOptions: [
        { label: 'All Status', value: null },
        { label: 'Draft', value: 'draft' },
        { label: 'Sent', value: 'sent' },
        { label: 'Approved', value: 'approved' },
        { label: 'Rejected', value: 'rejected' },
        { label: 'Completed', value: 'completed' }
      ],
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
    this.loadInitialData();
  },
  computed: {
    filteredDemands() {
      return this.demands;
    }
  },
  methods: {
    async loadInitialData() {
      this.loading = true;
      try {
        await Promise.all([
          this.loadCurrentUser(),
          this.loadDemands(),
          this.loadServices(),
          this.loadStats(),
          this.loadProducts()
        ]);
      } catch (error) {
        console.error('Failed to load initial data:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to load initial data',
          life: 3000
        });
      } finally {
        this.loading = false;
      }
    },

    async loadCurrentUser() {
      try {
        const response = await axios.get('/api/loginuser');
        this.currentUser = response.data.data;
      } catch (error) {
        console.error('Failed to load current user:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to load user information',
          life: 3000
        });
      }
    },

    async loadDemands() {
      try {
        const params = {};
        if (this.statusFilter) params.status = this.statusFilter;
        if (this.serviceFilter) params.service_id = this.serviceFilter;
        if (this.filters.global.value) params.search = this.filters.global.value;

        const response = await axios.get('/api/service-demands', { params });
        this.demands = response.data.data.data || [];
      } catch (error) {
        console.error('Failed to load demands:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to load demands',
          life: 3000
        });
      }
    },

    async loadServices() {
      this.loadingServices = true;
      try {
        const response = await axios.get('/api/service-demands/meta/services');
        this.allServices = response.data.data || [];
        
        // Filter services based on user's specialization
        if (this.currentUser && this.currentUser.specialization_id) {
          // Get services that belong to user's specialization
          const specializationResponse = await axios.get(`/api/specializations`);
          const specializations = specializationResponse.data.data || [];
          
          // Find user's specialization
          const userSpecialization = specializations.find(spec => spec.id === this.currentUser.specialization_id);
          
          if (userSpecialization && userSpecialization.service_id) {
            // Filter services to show only the user's specialization service and its related services
            this.userSpecializationServices = this.allServices.filter(service => 
              service.id === userSpecialization.service_id
            );
            
            // Set services to user's specialization services for dropdown
            this.services = this.userSpecializationServices;
          } else {
            // If no specialization service found, show all services
            this.services = this.allServices;
          }
        } else {
          // If no user specialization, show all services
          this.services = this.allServices;
        }
      } catch (error) {
        console.error('Failed to load services:', error);
        // Fallback to all services
        this.services = this.allServices;
      } finally {
        this.loadingServices = false;
      }
    },

    async loadStats() {
      try {
        const response = await axios.get('/api/service-demands/meta/stats');
        this.stats = response.data.data || {};
      } catch (error) {
        console.error('Failed to load stats:', error);
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

    onTabChange(event) {
      this.activeTab = event.index === 0 ? 'my_demands' : 'all_demands';
      this.loadDemands();
    },

    goToCreatePage() {
      // Open create dialog instead of navigating
      this.resetNewDemand();
      this.showCreateDialog = true;
    },

    async createDemandAndRedirect() {
      this.creatingDraft = true;
      try {
        const response = await axios.post('/api/service-demands', {
          service_id: this.newDemand.service_id,
          expected_date: this.newDemand.expected_date,
          notes: this.newDemand.notes
        });
        
        const newDemand = response.data.data;
        
        this.toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Demand created successfully! Redirecting to add products...',
          life: 3000
        });
        
        this.closeCreateDialog();
        
        // Navigate to edit mode with the new demand
        this.$router.push({
          path: `/stock/service-demands/edit/${newDemand.id}`,
          query: { mode: 'edit', skipBasicInfo: 'true' }
        });
        
      } catch (error) {
        console.error('Error creating demand:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to create demand',
          life: 3000
        });
      } finally {
        this.creatingDraft = false;
      }
    },

    closeCreateDialog() {
      this.showCreateDialog = false;
      this.resetNewDemand();
    },

    resetNewDemand() {
      this.newDemand = {
        service_id: null,
        expected_date: null,
        notes: ''
      };
    },

    editDemand(demand) {
      // Navigate to ServiceDemandCreate page in edit mode
      this.$router.push({
        path: `/stock/service-demands/edit/${demand.id}`,
        query: { mode: 'edit' }
      });
    },

    async saveDraft() {
      this.savingDraft = true;
      try {
        await axios.put(`/api/service-demands/${this.selectedDemand.id}`, {
          service_id: this.selectedDemand.service_id,
          expected_date: this.selectedDemand.expected_date,
          notes: this.selectedDemand.notes
        });
        this.toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Demand updated successfully',
          life: 3000
        });
        this.closeEditDialog();
        this.loadDemands();
      } catch (error) {
        console.error('Error saving demand:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to save demand',
          life: 3000
        });
      } finally {
        this.savingDraft = false;
      }
    },

    async sendDemand(demand) {
      this.confirm.require({
        message: `Are you sure you want to send demand "${demand.demand_code}"? Once sent, you cannot edit it anymore.`,
        header: 'Send Demand',
        icon: 'pi pi-exclamation-triangle',
        rejectClass: 'p-button-text',
        acceptClass: 'p-button-danger',
        accept: async () => {
          try {
            const response = await axios.post(`/api/service-demands/${demand.id}/send`);
            
            // Update the status in the local data immediately
            const demandIndex = this.demands.findIndex(d => d.id === demand.id);
            if (demandIndex !== -1) {
              this.demands[demandIndex].status = 'sent';
              this.demands[demandIndex] = { ...this.demands[demandIndex], status: 'sent' };
            }
            
            // If this demand is currently being viewed/edited, update it too
            if (this.selectedDemand && this.selectedDemand.id === demand.id) {
              this.selectedDemand.status = 'sent';
            }
            
            this.toast.add({
              severity: 'success',
              summary: 'Success',
              detail: 'Demand sent successfully. Status changed to "Sent".',
              life: 3000
            });
            
            // Close edit dialog if it's open for this demand
            if (this.showEditDialog && this.selectedDemand && this.selectedDemand.id === demand.id) {
              this.showEditDialog = false;
            }
            
            // Reload data to ensure consistency
            this.loadDemands();
            this.loadStats();
            
          } catch (error) {
            console.error('Error sending demand:', error);
            this.toast.add({
              severity: 'error',
              summary: 'Error',
              detail: error.response?.data?.message || 'Failed to send demand',
              life: 3000
            });
          }
        }
      });
    },

    confirmSendDraft() {
      this.sendDemand(this.selectedDemand);
    },

    async addProductToDemand() {
      this.addingItem = true;
      try {
        const response = await axios.post(`/api/service-demands/${this.selectedDemand.id}/items`, this.newItem);
        this.selectedDemand.items.push(response.data.data);
        this.toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Item added successfully',
          life: 3000
        });
        this.closeAddProductDialog();
        this.resetNewItem();
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
            await axios.delete(`/api/service-demands/${this.selectedDemand.id}/items/${item.id}`);
            const index = this.selectedDemand.items.findIndex(i => i.id === item.id);
            if (index !== -1) {
              this.selectedDemand.items.splice(index, 1);
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

    async deleteDemand(demand) {
      this.confirm.require({
        message: `Are you sure you want to delete demand "${demand.demand_code}"? This action cannot be undone.`,
        header: 'Delete Demand',
        icon: 'pi pi-exclamation-triangle',
        rejectClass: 'p-button-text',
        acceptClass: 'p-button-danger',
        accept: async () => {
          try {
            await axios.delete(`/api/service-demands/${demand.id}`);
            this.toast.add({
              severity: 'success',
              summary: 'Success',
              detail: 'Demand deleted successfully',
              life: 3000
            });
            this.loadDemands();
            this.loadStats();
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

    viewDemand(demand) {
      // Navigate to ServiceDemandCreate page in view mode
      this.$router.push({
        path: `/stock/service-demands/view/${demand.id}`,
        query: { mode: 'view' }
      });
    },

    editDemandFromDetails() {
      // This method is no longer needed since we use navigation
      // but keeping for backward compatibility
      this.showDetailsDialog = false;
      this.showEditDialog = true;
    },

    clearFilters() {
      this.filters.global.value = null;
      this.statusFilter = null;
      this.serviceFilter = null;
      this.loadDemands();
    },

    closeEditDialog() {
      this.showEditDialog = false;
      this.selectedDemand = null;
    },

    closeAddProductDialog() {
      this.showAddProductDialog = false;
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

    // Helper method to check if a demand can be edited
    canEditDemand(demand) {
      return demand && demand.status === 'draft';
    },

    // Helper method to check if a demand can be sent
    canSendDemand(demand) {
      return demand && demand.status === 'draft';
    },

    // Helper method to check if a demand can be deleted
    canDeleteDemand(demand) {
      return demand && demand.status === 'draft';
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