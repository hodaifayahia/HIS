<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-slate-50 tw-to-slate-100 tw-p-4 lg:tw-p-6">
    <!-- Enhanced Header with Breadcrumb -->
    <div class="tw-mb-6">
      <Breadcrumb :model="breadcrumbItems" class="tw-mb-4">
        <template #item="{ item }">
          <span class="tw-text-gray-600 hover:tw-text-blue-600 tw-transition-colors">{{ item.label }}</span>
        </template>
      </Breadcrumb>

      <Card class="tw-border-0 tw-shadow-2xl tw-overflow-hidden">
        <template #content>
          <div class="tw-bg-gradient-to-r tw-from-blue-600 tw-to-indigo-700 tw-p-6 tw--m-6">
            <div class="tw-flex tw-flex-col lg:tw-flex-row tw-justify-between tw-items-start lg:tw-items-center tw-gap-4">
              <div>
                <h1 class="tw-text-3xl tw-font-bold tw-text-white tw-flex tw-items-center tw-gap-3">
                  <div class="tw-bg-white/20 tw-p-3 tw-rounded-xl tw-backdrop-blur-sm tw-animate-pulse-slow">
                    <i class="pi pi-shopping-cart tw-text-2xl"></i>
                  </div>
                  Demand Details
                </h1>
                <div class="tw-flex tw-flex-wrap tw-items-center tw-gap-3 tw-mt-3">
                  <div class="tw-flex tw-items-center tw-gap-2">
                    <span class="tw-text-blue-100 tw-text-sm">Code:</span>
                    <span class="tw-bg-white/20 tw-px-4 tw-py-2 tw-rounded-full tw-text-white tw-font-bold tw-backdrop-blur-sm">
                      <i class="pi pi-tag tw-mr-2"></i>
                      {{ demand?.demand_code || 'Loading...' }}
                    </span>
                  </div>
                  <Tag 
                    v-if="demand"
                    :value="getStatusLabel(demand.status)" 
                    :severity="getStatusSeverity(demand.status)"
                    class="tw-text-lg tw-px-4 tw-py-2 tw-font-semibold"
                  />
                  <Tag 
                    v-if="demand?.priority"
                    :value="`Priority: ${demand.priority}`" 
                    :severity="getPrioritySeverity(demand.priority)"
                    icon="pi pi-flag"
                  />
                </div>
              </div>

              <!-- Quick Actions -->
              <div class="tw-flex tw-flex-wrap tw-gap-2">
                <Button 
                  @click="$router.back()"
                  icon="pi pi-arrow-left"
                  label="Back"
                  class="p-button-secondary tw-shadow-lg hover:tw-scale-105 tw-transition-transform"
                  size="small"
                />
                <Button 
                  v-if="demand?.status === 'draft'"
                  @click="showEditDialog = true"
                  icon="pi pi-pencil"
                  label="Edit"
                  class="p-button-warning tw-shadow-lg"
                  size="small"
                />
                <Button 
                  v-if="demand?.status === 'draft' && demand?.items?.length > 0"
                  @click="sendDemand"
                  icon="pi pi-send"
                  label="Send"
                  class="p-button-success tw-shadow-lg"
                  :loading="sendingDemand"
                  size="small"
                />
                <Button 
                  @click="printDemand"
                  icon="pi pi-print"
                  label="Print"
                  class="p-button-help tw-shadow-lg"
                  size="small"
                />
                <Button 
                  @click="exportPDF"
                  icon="pi pi-file-pdf"
                  label="Export"
                  class="p-button-danger tw-shadow-lg"
                  size="small"
                />
              </div>
            </div>

            <!-- Quick Stats Bar -->
            <div class="tw-grid tw-grid-cols-2 lg:tw-grid-cols-5 tw-gap-3 tw-mt-6">
              <div class="tw-bg-white/10 tw-backdrop-blur-sm tw-rounded-lg tw-p-3 tw-border tw-border-white/20">
                <div class="tw-flex tw-items-center tw-justify-between">
                  <div>
                    <p class="tw-text-xs tw-text-blue-200">Total Items</p>
                    <p class="tw-text-xl tw-font-bold tw-text-white">{{ demand?.items?.length || 0 }}</p>
                  </div>
                  <i class="pi pi-box tw-text-blue-300 tw-text-xl"></i>
                </div>
              </div>

              <div class="tw-bg-white/10 tw-backdrop-blur-sm tw-rounded-lg tw-p-3 tw-border tw-border-white/20">
                <div class="tw-flex tw-items-center tw-justify-between">
                  <div>
                    <p class="tw-text-xs tw-text-blue-200">Total Quantity</p>
                    <p class="tw-text-xl tw-font-bold tw-text-white">{{ totalQuantity }}</p>
                  </div>
                  <i class="pi pi-hashtag tw-text-blue-300 tw-text-xl"></i>
                </div>
              </div>

              <div class="tw-bg-white/10 tw-backdrop-blur-sm tw-rounded-lg tw-p-3 tw-border tw-border-white/20">
                <div class="tw-flex tw-items-center tw-justify-between">
                  <div>
                    <p class="tw-text-xs tw-text-blue-200">Total Value</p>
                    <p class="tw-text-lg tw-font-bold tw-text-white">{{ formatCurrency(totalValue) }}</p>
                  </div>
                  <i class="pi pi-dollar tw-text-blue-300 tw-text-xl"></i>
                </div>
              </div>

              <div class="tw-bg-white/10 tw-backdrop-blur-sm tw-rounded-lg tw-p-3 tw-border tw-border-white/20">
                <div class="tw-flex tw-items-center tw-justify-between">
                  <div>
                    <p class="tw-text-xs tw-text-blue-200">Approved</p>
                    <p class="tw-text-xl tw-font-bold tw-text-white">{{ approvedItemsCount }}</p>
                  </div>
                  <i class="pi pi-check-circle tw-text-green-300 tw-text-xl"></i>
                </div>
              </div>

              <div class="tw-bg-white/10 tw-backdrop-blur-sm tw-rounded-lg tw-p-3 tw-border tw-border-white/20">
                <div class="tw-flex tw-items-center tw-justify-between">
                  <div>
                    <p class="tw-text-xs tw-text-blue-200">Progress</p>
                    <ProgressBar :value="completionPercentage" :showValue="false" class="tw-w-16" />
                  </div>
                  <span class="tw-text-white tw-font-bold">{{ completionPercentage }}%</span>
                </div>
              </div>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="tw-flex tw-justify-center tw-items-center tw-h-64">
      <Card class="tw-w-full tw-max-w-md tw-border-0 tw-shadow-2xl">
        <template #content>
          <div class="tw-text-center tw-py-8">
            <ProgressSpinner 
              strokeWidth="4"
              animationDuration="1s"
              class="tw-w-16 tw-h-16"
            />
            <p class="tw-mt-4 tw-text-gray-600 tw-text-lg">Loading demand details...</p>
            <ProgressBar :value="loadingProgress" :showValue="false" class="tw-mt-4" />
          </div>
        </template>
      </Card>
    </div>

    <!-- Error State -->
    <Card v-else-if="error" class="tw-border-0 tw-shadow-xl tw-bg-red-50">
      <template #content>
        <div class="tw-text-center tw-py-12">
          <i class="pi pi-exclamation-triangle tw-text-6xl tw-text-red-400 tw-mb-4"></i>
          <h3 class="tw-text-xl tw-font-medium tw-text-gray-900 tw-mb-2">Error Loading Demand</h3>
          <p class="tw-text-gray-500 tw-mb-6">{{ error }}</p>
          <Button 
            label="Try Again" 
            icon="pi pi-refresh" 
            @click="loadDemandDetails"
            class="p-button-danger"
          />
        </div>
      </template>
    </Card>

    <!-- Main Content -->
    <TabView v-else-if="demand" v-model:activeIndex="activeTab" class="tw-shadow-xl tw-rounded-xl">
      <!-- Overview Tab -->
      <TabPanel>
        <template #header>
          <i class="pi pi-info-circle tw-mr-2"></i>
          Overview
          <Badge v-if="demand.items?.length" :value="demand.items.length" class="tw-ml-2" />
        </template>

        <div class="tw-space-y-6">
          <!-- Information Cards -->
          <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-2 tw-gap-6">
            <!-- Basic Information Card -->
            <Card class="tw-border tw-shadow-md hover:tw-shadow-xl tw-transition-shadow">
              <template #title>
                <div class="tw-flex tw-items-center tw-gap-2">
                  <div class="tw-bg-blue-100 tw-p-2 tw-rounded-lg">
                    <i class="pi pi-info-circle tw-text-blue-600"></i>
                  </div>
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
                    <div class="tw-flex tw-items-center tw-gap-2">
                      <Avatar 
                        icon="pi pi-building"
                        class="tw-bg-indigo-100 tw-text-indigo-700"
                        size="small"
                        shape="circle"
                      />
                      <span class="tw-text-gray-900">{{ demand.service?.service_name || 'N/A' }}</span>
                    </div>
                  </div>
                  <div class="tw-flex tw-justify-between tw-items-center tw-border-b tw-pb-2">
                    <span class="tw-font-medium tw-text-gray-700">Status:</span>
                    <Tag 
                      :value="getStatusLabel(demand.status)" 
                      :severity="getStatusSeverity(demand.status)"
                      :icon="getStatusIcon(demand.status)"
                      class="tw-font-medium"
                    />
                  </div>
                  <div class="tw-flex tw-justify-between tw-items-center tw-border-b tw-pb-2">
                    <span class="tw-font-medium tw-text-gray-700">Priority:</span>
                    <Tag 
                      :value="demand.priority || 'Normal'" 
                      :severity="getPrioritySeverity(demand.priority)"
                      icon="pi pi-flag"
                    />
                  </div>
                  <div class="tw-flex tw-justify-between tw-items-center tw-border-b tw-pb-2">
                    <span class="tw-font-medium tw-text-gray-700">Expected Date:</span>
                    <span class="tw-text-gray-900">
                      <i class="pi pi-calendar tw-mr-1 tw-text-gray-500"></i>
                      {{ formatDate(demand.expected_date) }}
                    </span>
                  </div>
                  <div class="tw-flex tw-justify-between tw-items-center tw-border-b tw-pb-2">
                    <span class="tw-font-medium tw-text-gray-700">Created:</span>
                    <span class="tw-text-gray-900">{{ formatDateTime(demand.created_at) }}</span>
                  </div>
                  <div v-if="demand.notes" class="tw-border-b tw-pb-2">
                    <span class="tw-font-medium tw-text-gray-700 tw-block tw-mb-2">Notes:</span>
                    <div class="tw-bg-gray-50 tw-p-3 tw-rounded-lg tw-border tw-border-gray-200">
                      <p class="tw-text-gray-900 tw-whitespace-pre-wrap">{{ demand.notes }}</p>
                    </div>
                  </div>
                </div>
              </template>
            </Card>

            <!-- Summary Statistics Card -->
            <Card class="tw-border tw-shadow-md hover:tw-shadow-xl tw-transition-shadow">
              <template #title>
                <div class="tw-flex tw-items-center tw-gap-2">
                  <div class="tw-bg-green-100 tw-p-2 tw-rounded-lg">
                    <i class="pi pi-chart-bar tw-text-green-600"></i>
                  </div>
                  <span>Summary Statistics</span>
                </div>
              </template>
              <template #content>
                <div class="tw-grid tw-grid-cols-2 tw-gap-4">
                  <div class="tw-bg-gradient-to-br tw-from-blue-50 tw-to-blue-100 tw-p-4 tw-rounded-lg tw-border tw-border-blue-200">
                    <div class="tw-flex tw-justify-between tw-items-center">
                      <div>
                        <div class="tw-text-2xl tw-font-bold tw-text-blue-900">{{ demand.items?.length || 0 }}</div>
                        <div class="tw-text-sm tw-text-blue-700">Total Items</div>
                      </div>
                      <div class="tw-w-12 tw-h-12 tw-bg-blue-200 tw-rounded-full tw-flex tw-items-center tw-justify-center">
                        <i class="pi pi-box tw-text-blue-600 tw-text-xl"></i>
                      </div>
                    </div>
                  </div>

                  <div class="tw-bg-gradient-to-br tw-from-green-50 tw-to-green-100 tw-p-4 tw-rounded-lg tw-border tw-border-green-200">
                    <div class="tw-flex tw-justify-between tw-items-center">
                      <div>
                        <div class="tw-text-2xl tw-font-bold tw-text-green-900">{{ totalQuantity }}</div>
                        <div class="tw-text-sm tw-text-green-700">Total Quantity</div>
                      </div>
                      <div class="tw-w-12 tw-h-12 tw-bg-green-200 tw-rounded-full tw-flex tw-items-center tw-justify-center">
                        <i class="pi pi-calculator tw-text-green-600 tw-text-xl"></i>
                      </div>
                    </div>
                  </div>

                  <div class="tw-bg-gradient-to-br tw-from-purple-50 tw-to-purple-100 tw-p-4 tw-rounded-lg tw-border tw-border-purple-200">
                    <div class="tw-flex tw-justify-between tw-items-center">
                      <div>
                        <div class="tw-text-xl tw-font-bold tw-text-purple-900">{{ formatCurrency(totalValue) }}</div>
                        <div class="tw-text-sm tw-text-purple-700">Est. Total</div>
                      </div>
                      <div class="tw-w-12 tw-h-12 tw-bg-purple-200 tw-rounded-full tw-flex tw-items-center tw-justify-center">
                        <i class="pi pi-dollar tw-text-purple-600 tw-text-xl"></i>
                      </div>
                    </div>
                  </div>

                  <div class="tw-bg-gradient-to-br tw-from-orange-50 tw-to-orange-100 tw-p-4 tw-rounded-lg tw-border tw-border-orange-200">
                    <div class="tw-flex tw-justify-between tw-items-center">
                      <div>
                        <div class="tw-text-2xl tw-font-bold tw-text-orange-900">{{ pendingItemsCount }}</div>
                        <div class="tw-text-sm tw-text-orange-700">Pending</div>
                      </div>
                      <div class="tw-w-12 tw-h-12 tw-bg-orange-200 tw-rounded-full tw-flex tw-items-center tw-justify-center">
                        <i class="pi pi-clock tw-text-orange-600 tw-text-xl"></i>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Progress Chart -->
                <div class="tw-mt-6">
                  <div class="tw-flex tw-justify-between tw-items-center tw-mb-2">
                    <span class="tw-text-sm tw-font-medium tw-text-gray-700">Completion Progress</span>
                    <span class="tw-text-sm tw-font-bold tw-text-gray-900">{{ completionPercentage }}%</span>
                  </div>
                  <ProgressBar :value="completionPercentage" :showValue="false" class="tw-h-6" />
                  <div class="tw-flex tw-justify-between tw-mt-2 tw-text-xs tw-text-gray-500">
                    <span>{{ approvedItemsCount }} approved</span>
                    <span>{{ rejectedItemsCount }} rejected</span>
                  </div>
                </div>
              </template>
            </Card>
          </div>

          <!-- Approval Status (if applicable) -->
          <Card v-if="demand.approval_status" class="tw-border tw-shadow-md tw-bg-gradient-to-br tw-from-yellow-50 tw-to-orange-50">
            <template #title>
              <div class="tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-verified tw-text-orange-600"></i>
                <span>Approval Information</span>
              </div>
            </template>
            <template #content>
              <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-4">
                <div>
                  <label class="tw-text-xs tw-text-gray-600 tw-font-medium tw-uppercase tw-tracking-wider">Status</label>
                  <Tag 
                    :value="demand.approval_status" 
                    :severity="getApprovalSeverity(demand.approval_status)"
                    class="tw-mt-1"
                  />
                </div>
                <div v-if="demand.approved_by">
                  <label class="tw-text-xs tw-text-gray-600 tw-font-medium tw-uppercase tw-tracking-wider">Approved By</label>
                  <div class="tw-flex tw-items-center tw-gap-2 tw-mt-1">
                    <Avatar 
                      :label="demand.approved_by.name?.charAt(0)" 
                      class="tw-bg-orange-100 tw-text-orange-700"
                      size="small"
                    />
                    <span>{{ demand.approved_by.name }}</span>
                  </div>
                </div>
                <div v-if="demand.approved_at">
                  <label class="tw-text-xs tw-text-gray-600 tw-font-medium tw-uppercase tw-tracking-wider">Approved At</label>
                  <div class="tw-mt-1">{{ formatDateTime(demand.approved_at) }}</div>
                </div>
              </div>
              <div v-if="demand.approval_notes" class="tw-mt-4">
                <label class="tw-text-xs tw-text-gray-600 tw-font-medium tw-uppercase tw-tracking-wider">Approval Notes</label>
                <p class="tw-mt-1 tw-text-gray-800">{{ demand.approval_notes }}</p>
              </div>
            </template>
          </Card>
        </div>
      </TabPanel>

      <!-- Items Tab -->
      <TabPanel>
        <template #header>
          <i class="pi pi-list tw-mr-2"></i>
          Items
          <Badge :value="demand.items?.length || 0" class="tw-ml-2" severity="info" />
        </template>

        <div class="tw-space-y-4">
          <!-- Items Toolbar -->
          <div class="tw-flex tw-flex-col sm:tw-flex-row tw-justify-between tw-items-start sm:tw-items-center tw-gap-4">
            <div class="tw-flex tw-items-center tw-gap-4">
              <!-- Search -->
              <div class="tw-relative">
                <InputText 
                  v-model="itemSearchQuery"
                  placeholder="Search items..."
                  class="tw-pr-8 tw-min-w-[250px]"
                />
                <i class="pi pi-search tw-absolute tw-right-2 tw-top-1/2 tw--translate-y-1/2 tw-text-gray-400"></i>
              </div>

              <!-- View Toggle -->
              <div class="tw-flex tw-bg-gray-100 tw-rounded-lg tw-p-1">
                <Button 
                  icon="pi pi-table"
                  :class="viewMode === 'table' ? 'p-button-primary' : 'p-button-text'"
                  @click="viewMode = 'table'"
                  size="small"
                  v-tooltip="'Table View'"
                />
                <Button 
                  icon="pi pi-th-large"
                  :class="viewMode === 'grid' ? 'p-button-primary' : 'p-button-text'"
                  @click="viewMode = 'grid'"
                  size="small"
                  v-tooltip="'Grid View'"
                />
              </div>
            </div>

            <Button 
              v-if="demand.status === 'draft'"
              label="Add Item" 
              icon="pi pi-plus" 
              class="p-button-success"
              @click="showAddItemDialog = true"
            />
          </div>

          <!-- Empty State -->
          <Card v-if="!demand.items || demand.items.length === 0" class="tw-border tw-shadow-sm">
            <template #content>
              <div class="tw-text-center tw-py-12">
                <i class="pi pi-inbox tw-text-6xl tw-text-gray-300 tw-mb-4"></i>
                <h3 class="tw-text-xl tw-font-medium tw-text-gray-900 tw-mb-2">No items in this demand</h3>
                <p class="tw-text-gray-500 tw-mb-6">Add some products to this demand to get started.</p>
                <Button 
                  v-if="demand.status === 'draft'"
                  label="Add First Item" 
                  icon="pi pi-plus" 
                  @click="showAddItemDialog = true"
                  class="p-button-outlined"
                />
              </div>
            </template>
          </Card>

          <!-- Table View -->
          <DataTable 
            v-else-if="viewMode === 'table'"
            :value="filteredItems"
            :paginator="demand.items.length > 10"
            :rows="10"
            :rowsPerPageOptions="[10, 25, 50]"
            responsiveLayout="scroll"
            class="tw-shadow-md"
            :rowClass="getRowClass"
            showGridlines
          >
            <!-- Index Column -->
            <Column header="#" style="width: 50px">
              <template #body="slotProps">
                <div class="tw-w-8 tw-h-8 tw-bg-gradient-to-br tw-from-blue-500 tw-to-indigo-600 tw-text-white tw-rounded-full tw-flex tw-items-center tw-justify-center tw-font-bold tw-text-sm">
                  {{ slotProps.index + 1 }}
                </div>
              </template>
            </Column>

            <!-- Product Code Column -->
            <Column field="product.product_code" header="Product Code" :sortable="true" style="width: 120px">
              <template #body="slotProps">
                <span class="tw-font-mono tw-text-sm tw-font-medium">{{ slotProps.data.product?.product_code }}</span>
              </template>
            </Column>

            <!-- Product Name Column -->
            <Column field="product.name" header="Product Name" :sortable="true">
              <template #body="slotProps">
                <div class="tw-flex tw-items-center tw-gap-3">
                  <Avatar 
                    :label="slotProps.data.product?.name?.charAt(0)" 
                    class="tw-bg-purple-100 tw-text-purple-700"
                    shape="square"
                  />
                  <div>
                    <div class="tw-font-semibold">{{ slotProps.data.product?.name }}</div>
                    <div class="tw-text-xs tw-text-gray-500">
                      Category: {{ slotProps.data.product?.category || 'N/A' }}
                    </div>
                  </div>
                </div>
              </template>
            </Column>

            <!-- Quantity Column -->
            <Column field="quantity" header="Quantity" :sortable="true" style="width: 120px">
              <template #body="slotProps">
                <div class="tw-flex tw-items-center tw-gap-2">
                  <Tag :value="slotProps.data.quantity" severity="info" class="tw-font-bold" />
                  <span class="tw-text-sm tw-text-gray-500">{{ slotProps.data.product?.unit || 'units' }}</span>
                </div>
              </template>
            </Column>

            <!-- Unit Price Column -->
            <Column field="unit_price" header="Unit Price" :sortable="true" style="width: 120px">
              <template #body="slotProps">
                <span v-if="slotProps.data.unit_price" class="tw-font-medium tw-text-green-600">
                  {{ formatCurrency(slotProps.data.unit_price) }}
                </span>
                <span v-else class="tw-text-gray-400 tw-italic">Not set</span>
              </template>
            </Column>

            <!-- Total Price Column -->
            <Column header="Total Price" :sortable="true" style="width: 150px">
              <template #body="slotProps">
                <span v-if="slotProps.data.unit_price" class="tw-font-bold tw-text-lg tw-text-green-600">
                  {{ formatCurrency(slotProps.data.quantity * slotProps.data.unit_price) }}
                </span>
                <span v-else class="tw-text-gray-400">-</span>
              </template>
            </Column>

            <!-- Status Column -->
            <Column field="status" header="Status" :sortable="true" style="width: 120px">
              <template #body="slotProps">
                <Tag 
                  :value="slotProps.data.status || 'pending'" 
                  :severity="getItemStatusSeverity(slotProps.data.status || 'pending')"
                  :icon="getItemStatusIcon(slotProps.data.status || 'pending')"
                />
              </template>
            </Column>

            <!-- Actions Column -->
            <Column v-if="demand.status === 'draft'" header="Actions" style="width: 120px" :exportable="false">
              <template #body="slotProps">
                <div class="tw-flex tw-gap-1">
                  <Button 
                    icon="pi pi-pencil" 
                    class="p-button-text p-button-sm p-button-warning"
                    v-tooltip="'Edit Item'"
                    @click="editItem(slotProps.data)"
                  />
                  <Button 
                    icon="pi pi-trash" 
                    class="p-button-text p-button-sm p-button-danger"
                    v-tooltip="'Remove Item'"
                    @click="removeItem(slotProps.data)"
                  />
                </div>
              </template>
            </Column>

            <!-- Footer -->
            <template #footer>
              <div class="tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50 tw-p-4 tw-rounded-lg">
                <div class="tw-flex tw-justify-between tw-items-center">
                  <div class="tw-text-gray-600">
                    <span class="tw-font-medium">Total Items:</span> {{ demand.items.length }}
                    <span class="tw-mx-4">|</span>
                    <span class="tw-font-medium">Total Quantity:</span> {{ totalQuantity }}
                  </div>
                  <div class="tw-text-2xl tw-font-bold tw-text-indigo-600">
                    Total Value: {{ formatCurrency(totalValue) }}
                  </div>
                </div>
              </div>
            </template>
          </DataTable>

          <!-- Grid View -->
          <div v-else-if="viewMode === 'grid'" class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-4">
            <div 
              v-for="(item, index) in filteredItems" 
              :key="item.id || index"
              class="tw-bg-white tw-rounded-xl tw-shadow-md hover:tw-shadow-xl tw-transition-shadow tw-overflow-hidden"
            >
              <div class="tw-bg-gradient-to-r tw-from-blue-500 tw-to-indigo-600 tw-p-3">
                <div class="tw-flex tw-justify-between tw-items-center">
                  <span class="tw-text-white tw-font-bold">Item #{{ index + 1 }}</span>
                  <Tag 
                    :value="item.status || 'pending'" 
                    :severity="getItemStatusSeverity(item.status || 'pending')"
                  />
                </div>
              </div>
              <div class="tw-p-4 tw-space-y-3">
                <div>
                  <label class="tw-text-xs tw-text-gray-500 tw-uppercase tw-tracking-wider">Product</label>
                  <div class="tw-font-semibold tw-text-gray-800">{{ item.product?.name }}</div>
                  <div class="tw-text-xs tw-text-gray-500">Code: {{ item.product?.product_code }}</div>
                </div>
                <div class="tw-grid tw-grid-cols-2 tw-gap-3">
                  <div>
                    <label class="tw-text-xs tw-text-gray-500">Quantity</label>
                    <Tag :value="item.quantity" severity="info" class="tw-mt-1" />
                  </div>
                  <div>
                    <label class="tw-text-xs tw-text-gray-500">Unit Price</label>
                    <div class="tw-font-medium tw-mt-1">{{ formatCurrency(item.unit_price) || '-' }}</div>
                  </div>
                </div>
                <Divider />
                <div class="tw-flex tw-justify-between tw-items-center">
                  <span class="tw-text-sm tw-text-gray-500">Total</span>
                  <span class="tw-text-lg tw-font-bold tw-text-green-600">
                    {{ formatCurrency((item.quantity || 0) * (item.unit_price || 0)) }}
                  </span>
                </div>
                <div v-if="demand.status === 'draft'" class="tw-flex tw-gap-2 tw-pt-2">
                  <Button 
                    icon="pi pi-pencil" 
                    label="Edit"
                    class="p-button-sm p-button-warning tw-flex-1"
                    @click="editItem(item)"
                  />
                  <Button 
                    icon="pi pi-trash" 
                    label="Remove"
                    class="p-button-sm p-button-danger tw-flex-1"
                    @click="removeItem(item)"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </TabPanel>

      <!-- Timeline Tab -->
      <TabPanel>
        <template #header>
          <i class="pi pi-history tw-mr-2"></i>
          Timeline
          <Badge v-if="demand.activity_logs?.length" :value="demand.activity_logs.length" class="tw-ml-2" severity="warning" />
        </template>

        <div class="tw-space-y-4">
          <!-- Timeline Filter -->
          <Card class="tw-border tw-shadow-sm">
            <template #content>
              <div class="tw-flex tw-flex-col sm:tw-flex-row tw-gap-4">
                <div class="tw-flex-1">
                  <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Filter by Action</label>
                  <MultiSelect
                    v-model="selectedActions"
                    :options="actionOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="All Actions"
                    class="tw-w-full"
                    display="chip"
                  />
                </div>
                <div>
                  <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Sort Order</label>
                  <SelectButton 
                    v-model="timelineSort" 
                    :options="sortOptions" 
                    optionLabel="label"
                    optionValue="value"
                  />
                </div>
              </div>
            </template>
          </Card>

          <!-- Timeline Content -->
          <Timeline 
            v-if="filteredActivityLogs.length > 0"
            :value="filteredActivityLogs" 
            align="alternate" 
            class="tw-mt-6"
          >
            <template #marker="slotProps">
              <span class="tw-flex tw-w-12 tw-h-12 tw-items-center tw-justify-center tw-text-white tw-rounded-full tw-shadow-lg tw-z-10"
                    :style="{ backgroundColor: getActivityColor(slotProps.item.action) }">
                <i :class="getActivityIcon(slotProps.item.action)" class="tw-text-lg"></i>
              </span>
            </template>
            <template #content="slotProps">
              <Card class="tw-shadow-md hover:tw-shadow-lg tw-transition-shadow">
                <template #content>
                  <div class="tw-space-y-2">
                    <div class="tw-flex tw-justify-between tw-items-start">
                      <h4 class="tw-font-semibold tw-text-gray-900 tw-text-lg">
                        {{ slotProps.item.action_label || slotProps.item.action }}
                      </h4>
                      <Tag 
                        :value="slotProps.item.action" 
                        :severity="getActivitySeverity(slotProps.item.action)"
                        class="tw-text-xs"
                      />
                    </div>
                    <p class="tw-text-gray-600 tw-leading-relaxed">
                      {{ slotProps.item.description || getActivityDescription(slotProps.item) }}
                    </p>
                    <div class="tw-flex tw-flex-wrap tw-gap-4 tw-text-sm tw-text-gray-500 tw-pt-2 tw-border-t">
                      <div class="tw-flex tw-items-center tw-gap-1">
                        <i class="pi pi-calendar tw-text-xs"></i>
                        {{ formatDate(slotProps.item.created_at) }}
                      </div>
                      <div class="tw-flex tw-items-center tw-gap-1">
                        <i class="pi pi-clock tw-text-xs"></i>
                        {{ formatTime(slotProps.item.created_at) }}
                      </div>
                      <div v-if="slotProps.item.user" class="tw-flex tw-items-center tw-gap-1">
                        <Avatar 
                          :label="slotProps.item.user.name?.charAt(0)" 
                          class="tw-bg-gray-200 tw-text-gray-700"
                          size="small"
                          shape="circle"
                        />
                        <span>{{ slotProps.item.user.name }}</span>
                      </div>
                    </div>
                    <div v-if="slotProps.item.changes" class="tw-mt-3 tw-bg-gray-50 tw-p-3 tw-rounded-lg">
                      <h5 class="tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Changes:</h5>
                      <div class="tw-space-y-1 tw-text-sm">
                        <div v-for="(value, key) in slotProps.item.changes" :key="key" class="tw-flex tw-gap-2">
                          <span class="tw-font-medium tw-text-gray-600">{{ key }}:</span>
                          <span class="tw-text-gray-900">{{ value }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </template>
              </Card>
            </template>
          </Timeline>

          <!-- Empty Timeline State -->
          <Card v-else class="tw-border tw-shadow-sm">
            <template #content>
              <div class="tw-text-center tw-py-12">
                <i class="pi pi-history tw-text-6xl tw-text-gray-300 tw-mb-4"></i>
                <h3 class="tw-text-xl tw-font-medium tw-text-gray-900 tw-mb-2">No Activity Yet</h3>
                <p class="tw-text-gray-500">Activity logs will appear here once actions are performed.</p>
              </div>
            </template>
          </Card>
        </div>
      </TabPanel>

      <!-- Documents Tab -->
      <TabPanel>
        <template #header>
          <i class="pi pi-file tw-mr-2"></i>
          Documents
          <Badge v-if="demand.documents?.length" :value="demand.documents.length" class="tw-ml-2" severity="info" />
        </template>

        <Card class="tw-border tw-shadow-sm">
          <template #content>
            <div v-if="demand.documents?.length > 0" class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-4">
              <div 
                v-for="doc in demand.documents" 
                :key="doc.id"
                class="tw-bg-gray-50 tw-rounded-lg tw-p-4 tw-border tw-border-gray-200 hover:tw-shadow-md tw-transition-shadow"
              >
                <div class="tw-flex tw-items-start tw-gap-3">
                  <div class="tw-bg-white tw-p-2 tw-rounded-lg tw-shadow-sm">
                    <i :class="getDocumentIcon(doc.type)" class="tw-text-2xl tw-text-gray-600"></i>
                  </div>
                  <div class="tw-flex-1">
                    <h4 class="tw-font-medium tw-text-gray-900">{{ doc.name }}</h4>
                    <p class="tw-text-xs tw-text-gray-500 tw-mt-1">{{ doc.size }}</p>
                    <p class="tw-text-xs tw-text-gray-500">{{ formatDateTime(doc.created_at) }}</p>
                  </div>
                  <Button 
                    icon="pi pi-download"
                    class="p-button-text p-button-sm"
                    @click="downloadDocument(doc)"
                  />
                </div>
              </div>
            </div>
            <div v-else class="tw-text-center tw-py-12">
              <i class="pi pi-file tw-text-6xl tw-text-gray-300 tw-mb-4"></i>
              <h3 class="tw-text-xl tw-font-medium tw-text-gray-900 tw-mb-2">No Documents</h3>
              <p class="tw-text-gray-500">No documents have been attached to this demand.</p>
            </div>
          </template>
        </Card>
      </TabPanel>
    </TabView>

    <!-- Edit Demand Dialog -->
    <Dialog 
      v-model:visible="showEditDialog" 
      modal 
      header="Edit Demand" 
      :style="{width: '50rem'}"
      :breakpoints="{'960px': '75vw', '640px': '90vw'}"
    >
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
              filter
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
          <label class="tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Priority</label>
          <SelectButton 
            v-model="demand.priority" 
            :options="priorityOptions" 
            optionLabel="label"
            optionValue="value"
            class="tw-w-full"
          />
        </div>

        <div class="tw-flex tw-flex-col">
          <label class="tw-flex tw-justify-between tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
            <span>Notes</span>
            <span class="tw-text-xs tw-text-gray-500">{{ demand.notes?.length || 0 }}/500</span>
          </label>
          <Textarea
            v-model="demand.notes"
            rows="3"
            :maxlength="500"
            placeholder="Additional notes or requirements..."
            class="tw-w-full"
            autoResize
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

    <!-- Add/Edit Item Dialog -->
    <Dialog 
      v-model:visible="showAddItemDialog" 
      modal 
      :header="editingItem ? 'Edit Item' : 'Add Item to Demand'" 
      :style="{width: '50rem'}"
    >
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
              :disabled="!!editingItem"
            >
              <template #option="slotProps">
                <div class="tw-flex tw-items-center tw-gap-3 hover:tw-bg-blue-50 tw-p-2 tw-rounded">
                  <Avatar 
                    icon="pi pi-box"
                    class="tw-bg-purple-100 tw-text-purple-700"
                    shape="square"
                  />
                  <div>
                    <div class="tw-font-medium">{{ slotProps.option.name }}</div>
                    <div class="tw-text-xs tw-text-gray-500">Code: {{ slotProps.option.product_code }}</div>
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
              buttonLayout="horizontal"
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
          <label class="tw-flex tw-justify-between tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
            <span>Notes</span>
            <span class="tw-text-xs tw-text-gray-500">{{ newItem.notes?.length || 0 }}/200</span>
          </label>
          <Textarea
            v-model="newItem.notes"
            rows="2"
            :maxlength="200"
            placeholder="Additional notes for this item..."
            class="tw-w-full"
            autoResize
          />
        </div>

        <!-- Live Total Display -->
        <div class="tw-bg-gradient-to-r tw-from-green-50 tw-to-emerald-50 tw-p-4 tw-rounded-lg tw-border tw-border-green-200">
          <div class="tw-flex tw-justify-between tw-items-center">
            <span class="tw-text-gray-700 tw-font-medium">Item Total:</span>
            <span class="tw-text-2xl tw-font-bold tw-text-green-600">
              {{ formatCurrency((newItem.quantity || 0) * (newItem.unit_price || 0)) }}
            </span>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="tw-flex tw-justify-end tw-gap-3">
          <Button 
            label="Cancel" 
            icon="pi pi-times" 
            class="p-button-text" 
            @click="closeItemDialog"
          />
          <Button 
            :label="editingItem ? 'Update Item' : 'Add Item'"
            :icon="editingItem ? 'pi pi-save' : 'pi pi-plus'"
            :loading="addingItem"
            :disabled="!newItem.product_id || !newItem.quantity || newItem.quantity <= 0"
            @click="saveItem"
          />
        </div>
      </template>
    </Dialog>

    <!-- Confirm Dialog -->
    <ConfirmDialog></ConfirmDialog>

    <!-- Toast -->
    <Toast position="top-right" />
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
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Calendar from 'primevue/calendar';
import Textarea from 'primevue/textarea';
import Tag from 'primevue/tag';
import Card from 'primevue/card';
import Timeline from 'primevue/timeline';
import Toast from 'primevue/toast';
import Breadcrumb from 'primevue/breadcrumb';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Badge from 'primevue/badge';
import ProgressBar from 'primevue/progressbar';
import ProgressSpinner from 'primevue/progressspinner';
import Avatar from 'primevue/avatar';
import Divider from 'primevue/divider';
import MultiSelect from 'primevue/multiselect';
import SelectButton from 'primevue/selectbutton';
import ConfirmDialog from 'primevue/confirmdialog';

export default {
  name: 'ServiceDemandView',
  components: {
    DataTable,
    Column,
    Dialog,
    Button,
    InputNumber,
    InputText,
    Dropdown,
    Calendar,
    Textarea,
    Tag,
    Card,
    Timeline,
    Toast,
    Breadcrumb,
    TabView,
    TabPanel,
    Badge,
    ProgressBar,
    ProgressSpinner,
    Avatar,
    Divider,
    MultiSelect,
    SelectButton,
    ConfirmDialog
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
      loadingProgress: 0,

      showEditDialog: false,
      showAddItemDialog: false,
      editingItem: null,

      activeTab: 0,
      viewMode: 'table',
      itemSearchQuery: '',
      timelineSort: 'desc',
      selectedActions: [],

      newItem: {
        product_id: null,
        quantity: 1,
        unit_price: null,
        notes: ''
      },

      minDate: new Date(),

      priorityOptions: [
        { label: 'Low', value: 'low' },
        { label: 'Normal', value: 'normal' },
        { label: 'High', value: 'high' },
        { label: 'Urgent', value: 'urgent' }
      ],

      sortOptions: [
        { label: 'Newest First', value: 'desc' },
        { label: 'Oldest First', value: 'asc' }
      ],

      actionOptions: [
        { label: 'Created', value: 'created' },
        { label: 'Updated', value: 'updated' },
        { label: 'Sent', value: 'sent' },
        { label: 'Approved', value: 'approved' },
        { label: 'Rejected', value: 'rejected' },
        { label: 'Item Added', value: 'item_added' },
        { label: 'Item Removed', value: 'item_removed' }
      ]
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
    breadcrumbItems() {
      return [
        { label: 'Dashboard', to: '/' },
        { label: 'Service Demands', to: '/service-demands' },
        { label: this.demand?.demand_code || 'Loading...', disabled: true }
      ];
    },

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
    },

    approvedItemsCount() {
      if (!this.demand?.items) return 0;
      return this.demand.items.filter(item => item.status === 'approved').length;
    },

    rejectedItemsCount() {
      if (!this.demand?.items) return 0;
      return this.demand.items.filter(item => item.status === 'rejected').length;
    },

    pendingItemsCount() {
      if (!this.demand?.items) return 0;
      return this.demand.items.filter(item => !item.status || item.status === 'pending').length;
    },

    completionPercentage() {
      if (!this.demand?.items?.length) return 0;
      const completed = this.approvedItemsCount + this.rejectedItemsCount;
      return Math.round((completed / this.demand.items.length) * 100);
    },

    filteredItems() {
      if (!this.demand?.items) return [];
      if (!this.itemSearchQuery) return this.demand.items;

      const query = this.itemSearchQuery.toLowerCase();
      return this.demand.items.filter(item => {
        return item.product?.name?.toLowerCase().includes(query) ||
               item.product?.product_code?.toLowerCase().includes(query);
      });
    },

    filteredActivityLogs() {
      let logs = this.demand?.activity_logs || [];

      if (this.selectedActions.length > 0) {
        logs = logs.filter(log => this.selectedActions.includes(log.action));
      }

      return logs.sort((a, b) => {
        const dateA = new Date(a.created_at);
        const dateB = new Date(b.created_at);
        return this.timelineSort === 'desc' ? dateB - dateA : dateA - dateB;
      });
    }
  },
  methods: {
    async loadDemandDetails() {
      this.loading = true;
      this.error = null;
      this.loadingProgress = 30;
      try {
        await new Promise(resolve => setTimeout(resolve, 500)); // Simulate loading
        this.loadingProgress = 60;

        const response = await axios.get(`/api/service-demands/${this.demandId}`);
        this.demand = response.data.data;

        // Add mock activity logs if not present
        if (!this.demand.activity_logs) {
          this.demand.activity_logs = this.generateMockActivityLogs();
        }

        this.loadingProgress = 100;
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
          priority: this.demand.priority,
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
        acceptClass: 'p-button-success',
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

    editItem(item) {
      this.editingItem = item;
      this.newItem = {
        product_id: item.product_id,
        quantity: item.quantity,
        unit_price: item.unit_price,
        notes: item.notes || ''
      };
      this.showAddItemDialog = true;
    },

    async saveItem() {
      this.addingItem = true;
      try {
        if (this.editingItem) {
          await axios.put(`/api/service-demands/${this.demand.id}/items/${this.editingItem.id}`, this.newItem);
          const index = this.demand.items.findIndex(i => i.id === this.editingItem.id);
          if (index !== -1) {
            this.demand.items[index] = { ...this.demand.items[index], ...this.newItem };
          }
          this.toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Item updated successfully',
            life: 3000
          });
        } else {
          const response = await axios.post(`/api/service-demands/${this.demand.id}/items`, this.newItem);
          this.demand.items.push(response.data.data);
          this.toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Item added successfully',
            life: 3000
          });
        }
        this.closeItemDialog();
      } catch (error) {
        console.error('Error saving item:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error.response?.data?.message || 'Failed to save item',
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

    closeItemDialog() {
      this.showAddItemDialog = false;
      this.editingItem = null;
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

    downloadDocument(doc) {
      this.toast.add({
        severity: 'info',
        summary: 'Info',
        detail: 'Document download functionality coming soon',
        life: 3000
      });
    },

    generateMockActivityLogs() {
      return [
        {
          id: 1,
          action: 'created',
          action_label: 'Demand Created',
          description: 'Initial demand creation with basic information',
          created_at: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000),
          user: { name: 'John Doe' }
        },
        {
          id: 2,
          action: 'item_added',
          action_label: 'Item Added',
          description: '5 units of Medical Supplies added to the demand',
          created_at: new Date(Date.now() - 6 * 24 * 60 * 60 * 1000),
          user: { name: 'Jane Smith' },
          changes: {
            product: 'Medical Supplies',
            quantity: 5,
            unit_price: '$25.00'
          }
        },
        {
          id: 3,
          action: 'updated',
          action_label: 'Demand Updated',
          description: 'Priority changed from Normal to High',
          created_at: new Date(Date.now() - 5 * 24 * 60 * 60 * 1000),
          user: { name: 'John Doe' },
          changes: {
            priority_from: 'Normal',
            priority_to: 'High'
          }
        },
        {
          id: 4,
          action: 'sent',
          action_label: 'Demand Sent',
          description: 'Demand sent for approval to management',
          created_at: new Date(Date.now() - 4 * 24 * 60 * 60 * 1000),
          user: { name: 'Jane Smith' }
        }
      ];
    },

    getActivityDescription(item) {
      const descriptions = {
        created: 'Demand was created with initial information',
        updated: 'Demand information was updated',
        sent: 'Demand was sent for approval',
        approved: 'Demand was approved by management',
        rejected: 'Demand was rejected and sent back for revision',
        item_added: 'New item was added to the demand',
        item_removed: 'Item was removed from the demand'
      };
      return descriptions[item.action] || 'Action performed on demand';
    },

    // Utility functions
    getStatusLabel(status) {
      const labels = {
        draft: 'Draft',
        sent: 'Sent',
        approved: 'Approved',
        rejected: 'Rejected',
        completed: 'Completed'
      };
      return labels[status] || status;
    },

    getStatusSeverity(status) {
      const severities = {
        draft: 'warning',
        sent: 'info',
        approved: 'success',
        rejected: 'danger',
        completed: 'success'
      };
      return severities[status] || 'info';
    },

    getStatusIcon(status) {
      const icons = {
        draft: 'pi pi-pencil',
        sent: 'pi pi-send',
        approved: 'pi pi-check-circle',
        rejected: 'pi pi-times-circle',
        completed: 'pi pi-verified'
      };
      return icons[status] || 'pi pi-info-circle';
    },

    getPrioritySeverity(priority) {
      const severities = {
        low: 'secondary',
        normal: 'info',
        high: 'warning',
        urgent: 'danger'
      };
      return severities[priority] || 'info';
    },

    getApprovalSeverity(status) {
      const severities = {
        pending: 'warning',
        approved: 'success',
        rejected: 'danger'
      };
      return severities[status] || 'info';
    },

    getItemStatusSeverity(status) {
      const severities = {
        pending: 'warning',
        approved: 'success',
        rejected: 'danger',
        delivered: 'success'
      };
      return severities[status] || 'info';
    },

    getItemStatusIcon(status) {
      const icons = {
        pending: 'pi pi-clock',
        approved: 'pi pi-check',
        rejected: 'pi pi-times',
        delivered: 'pi pi-truck'
      };
      return icons[status] || 'pi pi-question';
    },

    getActivityIcon(action) {
      const icons = {
        created: 'pi pi-plus',
        updated: 'pi pi-pencil',
        sent: 'pi pi-send',
        approved: 'pi pi-check-circle',
        rejected: 'pi pi-times-circle',
        item_added: 'pi pi-plus-circle',
        item_removed: 'pi pi-minus-circle',
        deleted: 'pi pi-trash'
      };
      return icons[action] || 'pi pi-info-circle';
    },

    getActivityColor(action) {
      const colors = {
        created: '#10b981',
        updated: '#3b82f6',
        sent: '#8b5cf6',
        approved: '#22c55e',
        rejected: '#ef4444',
        item_added: '#06b6d4',
        item_removed: '#f97316',
        deleted: '#dc2626'
      };
      return colors[action] || '#6b7280';
    },

    getActivitySeverity(action) {
      const severities = {
        created: 'success',
        updated: 'info',
        sent: 'info',
        approved: 'success',
        rejected: 'danger',
        item_added: 'success',
        item_removed: 'warning'
      };
      return severities[action] || 'info';
    },

    getDocumentIcon(type) {
      const icons = {
        pdf: 'pi pi-file-pdf',
        excel: 'pi pi-file-excel',
        word: 'pi pi-file-word',
        image: 'pi pi-image'
      };
      return icons[type] || 'pi pi-file';
    },

    getRowClass(data) {
      if (data.status === 'rejected') return 'tw-bg-red-50';
      if (data.status === 'approved') return 'tw-bg-green-50';
      return '';
    },

    formatCurrency(amount) {
      if (!amount && amount !== 0) return '$0.00';
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
      }).format(amount);
    },

    formatDate(date) {
      if (!date) return 'Not set';
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    },

    formatTime(date) {
      if (!date) return '';
      return new Date(date).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
      });
    },

    formatDateTime(date) {
      if (!date) return 'Not set';
      return new Date(date).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    }
  }
};
</script>

<style scoped>
/* Custom animations */
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: .5;
  }
}

.tw-animate-pulse-slow {
  animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* DataTable enhancements */
:deep(.p-datatable) {
  @apply border-0 tw-rounded-lg;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
  @apply bg-gradient-to-r tw-from-gray-50 tw-to-gray-100 tw-text-gray-700 tw-font-semibold tw-border-gray-200;
}

:deep(.p-datatable .p-datatable-tbody > tr) {
  @apply hover:tw-bg-blue-50 tw-transition-colors;
}

/* Card styling */
:deep(.p-card) {
  @apply rounded-xl;
}

:deep(.p-card-title) {
  @apply text-xl tw-font-bold tw-text-gray-800;
}

/* TabView */
:deep(.p-tabview-nav) {
  @apply bg-gradient-to-r tw-from-gray-50 tw-to-gray-100;
}

:deep(.p-tabview-nav-link) {
  @apply hover:tw-bg-blue-50;
}

:deep(.p-tabview-nav-link.p-highlight) {
  @apply bg-white tw-border-blue-600;
}

/* Timeline customization */
:deep(.p-timeline .p-timeline-event-connector) {
  @apply bg-gradient-to-b tw-from-blue-200 tw-to-indigo-200;
}

/* Button hover effects */
.p-button {
  @apply transition-all tw-duration-200;
}

.p-button:hover:not(:disabled) {
  @apply transform tw-scale-105;
}

/* Tag styles */
.p-tag {
  @apply font-medium tw-capitalize;
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
</style>