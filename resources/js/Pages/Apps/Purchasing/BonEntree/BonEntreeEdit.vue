<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-slate-50 tw-via-blue-50/30 tw-to-indigo-50 tw-p-4 lg:tw-p-6 tw-relative tw-overflow-hidden">
    <!-- Animated Background Elements -->
    <div class="tw-absolute tw-inset-0 tw-overflow-hidden tw-pointer-events-none">
      <div class="tw-absolute tw-top-10 tw-left-10 tw-w-72 tw-h-72 tw-bg-gradient-to-r tw-from-indigo-400/20 tw-to-purple-400/20 tw-rounded-full tw-blur-3xl tw-animate-float-slow tw-shadow-2xl"></div>
      <div class="tw-absolute tw-bottom-10 tw-right-10 tw-w-96 tw-h-96 tw-bg-gradient-to-r tw-from-blue-400/15 tw-to-cyan-400/15 tw-rounded-full tw-blur-3xl tw-animate-float-reverse tw-shadow-2xl"></div>
      <div class="tw-absolute tw-top-1/2 tw-left-1/2 tw--translate-x-1/2 tw--translate-y-1/2 tw-w-64 tw-h-64 tw-bg-gradient-to-r tw-from-emerald-400/10 tw-to-teal-400/10 tw-rounded-full tw-blur-3xl tw-animate-pulse-glow tw-shadow-2xl"></div>
      <!-- Additional floating elements for depth -->
      <div class="tw-absolute tw-top-20 tw-right-20 tw-w-32 tw-h-32 tw-bg-gradient-to-r tw-from-pink-400/15 tw-to-rose-400/15 tw-rounded-full tw-blur-2xl tw-animate-float-delayed tw-shadow-xl"></div>
      <div class="tw-absolute tw-bottom-20 tw-left-20 tw-w-48 tw-h-48 tw-bg-gradient-to-r tw-from-violet-400/12 tw-to-purple-400/12 tw-rounded-full tw-blur-2xl tw-animate-float-reverse-delayed tw-shadow-xl"></div>
    </div>

    <!-- Enhanced Header with Breadcrumb -->
    <div class="tw-mb-6 tw-relative tw-z-10">
      <Breadcrumb :model="breadcrumbItems" class="tw-mb-4">
        <template #item="{ item }">
          <span class="tw-text-gray-600 hover:tw-text-indigo-600 tw-transition-all tw-duration-300 hover:tw-scale-105 tw-cursor-pointer">{{ item.label }}</span>
        </template>
      </Breadcrumb>

      <Card class="tw-border-0 tw-shadow-2xl tw-overflow-hidden tw-bg-white/95 tw-backdrop-blur-sm tw-animate-slide-in-up tw-relative tw-group">
        <template #content>
          <div class="tw-bg-gradient-to-r tw-from-indigo-600 tw-via-purple-600 tw-to-pink-600 tw-p-6 tw--m-6 tw-relative tw-overflow-hidden">
            <!-- Enhanced animated gradient overlay -->
            <div class="tw-absolute tw-inset-0 tw-bg-gradient-to-r tw-from-transparent tw-via-white/10 tw-to-transparent tw-animate-shimmer"></div>
            <div class="tw-absolute tw-inset-0 tw-bg-gradient-to-r tw-from-indigo-600/80 tw-via-purple-600/60 tw-to-pink-600/80 tw-animate-gradient-shift"></div>

            <!-- Floating particles effect -->
            <div class="tw-absolute tw-inset-0 tw-overflow-hidden">
              <div class="tw-absolute tw-top-4 tw-left-4 tw-w-2 tw-h-2 tw-bg-white/30 tw-rounded-full tw-animate-particle-1"></div>
              <div class="tw-absolute tw-top-8 tw-right-8 tw-w-1 tw-h-1 tw-bg-white/40 tw-rounded-full tw-animate-particle-2"></div>
              <div class="tw-absolute tw-bottom-6 tw-left-1/4 tw-w-1.5 tw-h-1.5 tw-bg-white/25 tw-rounded-full tw-animate-particle-3"></div>
              <div class="tw-absolute tw-bottom-4 tw-right-1/3 tw-w-1 tw-h-1 tw-bg-white/35 tw-rounded-full tw-animate-particle-4"></div>
            </div>

            <div class="tw-flex tw-flex-col lg:tw-flex-row tw-justify-between tw-items-start lg:tw-items-center tw-gap-4 tw-relative tw-z-10">
              <div class="tw-animate-fade-in-left">
                <h1 class="tw-text-4xl tw-font-bold tw-text-white tw-flex tw-items-center tw-gap-4 tw-mb-2">
                  <div class="tw-bg-white/20 tw-p-4 tw-rounded-2xl tw-backdrop-blur-sm tw-animate-pulse-slow tw-shadow-2xl tw-group-hover:tw-scale-110 tw-transition-transform tw-duration-500">
                    <i class="pi pi-file-edit tw-text-3xl"></i>
                  </div>
                  <span class="tw-bg-gradient-to-r tw-from-white tw-to-indigo-100 tw-bg-clip-text tw-text-transparent tw-group-hover:tw-from-indigo-100 tw-group-hover:tw-to-white tw-transition-all tw-duration-500">
                    Edit Stock Entry
                  </span>
                </h1>
                <div class="tw-flex tw-flex-wrap tw-items-center tw-gap-4 tw-mt-4">
                  <div class="tw-flex tw-items-center tw-gap-3 tw-animate-fade-in-up tw-animation-delay-200">
                    <span class="tw-text-indigo-100 tw-text-sm tw-font-medium">Code:</span>
                    <span class="tw-bg-white/20 tw-px-5 tw-py-3 tw-rounded-2xl tw-text-white tw-font-bold tw-backdrop-blur-sm tw-shadow-lg tw-border tw-border-white/30 tw-animate-bounce-gentle">
                      <i class="pi pi-tag tw-mr-2"></i>
                      {{ bonEntreeData?.bon_entree_code || 'Loading...' }}
                    </span>
                  </div>
                  <Tag
                    v-if="bonEntreeData"
                    :value="getStatusLabel(bonEntreeData.status)"
                    :severity="getStatusSeverity(bonEntreeData.status)"
                    class="tw-font-semibold tw-px-5 tw-py-3 tw-text-lg tw-shadow-lg tw-animate-fade-in-up tw-animation-delay-400"
                    icon="pi pi-circle-fill"
                  />
                  <Tag
                    v-if="bonEntreeData?.bon_reception"
                    :value="`Receipt: ${bonEntreeData.bon_reception.bon_reception_code}`"
                    severity="info"
                    icon="pi pi-link"
                    class="tw-animate-fade-in-up tw-animation-delay-600"
                  />
                </div>
              </div>
              <div class="tw-flex tw-gap-3 tw-animate-fade-in-right">
                <Button
                  @click="printBonEntree"
                  icon="pi pi-print"
                  label="Print"
                  class="p-button-help tw-shadow-xl tw-border-0 tw-bg-white/10 tw-backdrop-blur-sm hover:tw-bg-white/20 tw-transition-all tw-duration-300 hover:tw-scale-110 hover:tw-shadow-2xl hover:tw-rotate-1 tw-group"
                  size="large"
                >
                  <template #icon>
                    <i class="pi pi-print tw-group-hover:tw-animate-bounce"></i>
                  </template>
                </Button>
                <Button
                  @click="router.back()"
                  icon="pi pi-arrow-left"
                  label="Back"
                  class="p-button-secondary tw-shadow-xl tw-border-0 tw-bg-white/10 tw-backdrop-blur-sm hover:tw-bg-white/20 tw-transition-all tw-duration-300 hover:tw-scale-110 hover:tw-shadow-2xl hover:tw--translate-x-1 tw-group"
                  size="large"
                >
                  <template #icon>
                    <i class="pi pi-arrow-left tw-group-hover:tw-animate-pulse"></i>
                  </template>
                </Button>
              </div>
            </div>

            <!-- Enhanced Quick Stats Bar -->
            <div class="tw-grid tw-grid-cols-2 lg:tw-grid-cols-5 tw-gap-4 tw-mt-8 tw-animate-fade-in-up tw-animation-delay-800">
              <div class="tw-bg-white/15 tw-backdrop-blur-md tw-rounded-2xl tw-p-4 tw-border tw-border-white/30 tw-shadow-xl hover:tw-bg-white/25 tw-transition-all tw-duration-500 hover:tw-scale-105 hover:tw-shadow-2xl tw-group tw-cursor-pointer tw-relative tw-overflow-hidden">
                <!-- Card background animation -->
                <div class="tw-absolute tw-inset-0 tw-bg-gradient-to-r tw-from-indigo-500/20 tw-to-purple-500/20 tw-opacity-0 tw-group-hover:tw-opacity-100 tw-transition-opacity tw-duration-500"></div>
                <div class="tw-absolute tw-top-0 tw-left-0 tw-w-full tw-h-1 tw-bg-gradient-to-r tw-from-indigo-400 tw-to-purple-400 tw-transform tw-scale-x-0 tw-group-hover:tw-scale-x-100 tw-transition-transform tw-duration-500"></div>

                <div class="tw-flex tw-items-center tw-justify-between tw-relative tw-z-10">
                  <div>
                    <p class="tw-text-xs tw-text-indigo-200 tw-font-medium tw-uppercase tw-tracking-wider tw-group-hover:tw-text-indigo-100 tw-transition-colors tw-duration-300">Total Items</p>
                    <p class="tw-text-2xl tw-font-bold tw-text-white tw-mt-1 tw-group-hover:tw-scale-110 tw-transition-transform tw-duration-300 tw-group-hover:tw-text-indigo-100">{{ form.items.length }}</p>
                  </div>
                  <div class="tw-bg-white/20 tw-p-3 tw-rounded-xl tw-group-hover:tw-bg-white/30 tw-transition-all tw-duration-300 tw-group-hover:tw-scale-110 tw-group-hover:tw-rotate-12">
                    <i class="pi pi-box tw-text-indigo-300 tw-text-2xl"></i>
                  </div>
                </div>
              </div>

              <div class="tw-bg-white/15 tw-backdrop-blur-md tw-rounded-2xl tw-p-4 tw-border tw-border-white/30 tw-shadow-xl hover:tw-bg-white/25 tw-transition-all tw-duration-500 hover:tw-scale-105 hover:tw-shadow-2xl tw-group tw-cursor-pointer tw-relative tw-overflow-hidden">
                <div class="tw-absolute tw-inset-0 tw-bg-gradient-to-r tw-from-blue-500/20 tw-to-cyan-500/20 tw-opacity-0 tw-group-hover:tw-opacity-100 tw-transition-opacity tw-duration-500"></div>
                <div class="tw-absolute tw-top-0 tw-left-0 tw-w-full tw-h-1 tw-bg-gradient-to-r tw-from-blue-400 tw-to-cyan-400 tw-transform tw-scale-x-0 tw-group-hover:tw-scale-x-100 tw-transition-transform tw-duration-500"></div>

                <div class="tw-flex tw-items-center tw-justify-between tw-relative tw-z-10">
                  <div>
                    <p class="tw-text-xs tw-text-indigo-200 tw-font-medium tw-uppercase tw-tracking-wider tw-group-hover:tw-text-indigo-100 tw-transition-colors tw-duration-300">Total Quantity</p>
                    <p class="tw-text-2xl tw-font-bold tw-text-white tw-mt-1 tw-group-hover:tw-scale-110 tw-transition-transform tw-duration-300 tw-group-hover:tw-text-blue-100">{{ calculateTotalQuantity() }}</p>
                  </div>
                  <div class="tw-bg-white/20 tw-p-3 tw-rounded-xl tw-group-hover:tw-bg-white/30 tw-transition-all tw-duration-300 tw-group-hover:tw-scale-110 tw-group-hover:tw-rotate-12">
                    <i class="pi pi-hashtag tw-text-indigo-300 tw-text-2xl"></i>
                  </div>
                </div>
              </div>

              <div class="tw-bg-white/15 tw-backdrop-blur-md tw-rounded-2xl tw-p-4 tw-border tw-border-white/30 tw-shadow-xl hover:tw-bg-white/25 tw-transition-all tw-duration-500 hover:tw-scale-105 hover:tw-shadow-2xl tw-group tw-cursor-pointer tw-relative tw-overflow-hidden">
                <div class="tw-absolute tw-inset-0 tw-bg-gradient-to-r tw-from-green-500/20 tw-to-emerald-500/20 tw-opacity-0 tw-group-hover:tw-opacity-100 tw-transition-opacity tw-duration-500"></div>
                <div class="tw-absolute tw-top-0 tw-left-0 tw-w-full tw-h-1 tw-bg-gradient-to-r tw-from-green-400 tw-to-emerald-400 tw-transform tw-scale-x-0 tw-group-hover:tw-scale-x-100 tw-transition-transform tw-duration-500"></div>

                <div class="tw-flex tw-items-center tw-justify-between tw-relative tw-z-10">
                  <div>
                    <p class="tw-text-xs tw-text-indigo-200 tw-font-medium tw-uppercase tw-tracking-wider tw-group-hover:tw-text-indigo-100 tw-transition-colors tw-duration-300">Total Amount</p>
                    <p class="tw-text-xl tw-font-bold tw-text-white tw-mt-1 tw-group-hover:tw-scale-110 tw-transition-transform tw-duration-300 tw-group-hover:tw-text-green-100">{{ formatCurrency(calculateTotalAmount()) }}</p>
                  </div>
                  <div class="tw-bg-white/20 tw-p-3 tw-rounded-xl tw-group-hover:tw-bg-white/30 tw-transition-all tw-duration-300 tw-group-hover:tw-scale-110 tw-group-hover:tw-rotate-12">
                    <i class="pi pi-dollar tw-text-indigo-300 tw-text-2xl"></i>
                  </div>
                </div>
              </div>

              <div class="tw-bg-white/15 tw-backdrop-blur-md tw-rounded-2xl tw-p-4 tw-border tw-border-white/30 tw-shadow-xl hover:tw-bg-white/25 tw-transition-all tw-duration-500 hover:tw-scale-105 hover:tw-shadow-2xl tw-group tw-cursor-pointer tw-relative tw-overflow-hidden">
                <div class="tw-absolute tw-inset-0 tw-bg-gradient-to-r tw-from-purple-500/20 tw-to-pink-500/20 tw-opacity-0 tw-group-hover:tw-opacity-100 tw-transition-opacity tw-duration-500"></div>
                <div class="tw-absolute tw-top-0 tw-left-0 tw-w-full tw-h-1 tw-bg-gradient-to-r tw-from-purple-400 tw-to-pink-400 tw-transform tw-scale-x-0 tw-group-hover:tw-scale-x-100 tw-transition-transform tw-duration-500"></div>

                <div class="tw-flex tw-items-center tw-justify-between tw-relative tw-z-10">
                  <div>
                    <p class="tw-text-xs tw-text-indigo-200 tw-font-medium tw-uppercase tw-tracking-wider tw-group-hover:tw-text-indigo-100 tw-transition-colors tw-duration-300">Service</p>
                    <p class="tw-text-sm tw-font-bold tw-text-white tw-truncate tw-mt-1 tw-group-hover:tw-scale-105 tw-transition-transform tw-duration-300 tw-group-hover:tw-text-purple-100">{{ getServiceName() || 'Not Set' }}</p>
                  </div>
                  <div class="tw-bg-white/20 tw-p-3 tw-rounded-xl tw-group-hover:tw-bg-white/30 tw-transition-all tw-duration-300 tw-group-hover:tw-scale-110 tw-group-hover:tw-rotate-12">
                    <i class="pi pi-building tw-text-indigo-300 tw-text-2xl"></i>
                  </div>
                </div>
              </div>

              <div class="tw-bg-white/15 tw-backdrop-blur-md tw-rounded-2xl tw-p-4 tw-border tw-border-white/30 tw-shadow-xl hover:tw-bg-white/25 tw-transition-all tw-duration-500 hover:tw-scale-105 hover:tw-shadow-2xl tw-group tw-cursor-pointer tw-relative tw-overflow-hidden">
                <div class="tw-absolute tw-inset-0 tw-bg-gradient-to-r tw-from-orange-500/20 tw-to-red-500/20 tw-opacity-0 tw-group-hover:tw-opacity-100 tw-transition-opacity tw-duration-500"></div>
                <div class="tw-absolute tw-top-0 tw-left-0 tw-w-full tw-h-1 tw-bg-gradient-to-r tw-from-orange-400 tw-to-red-400 tw-transform tw-scale-x-0 tw-group-hover:tw-scale-x-100 tw-transition-transform tw-duration-500"></div>

                <div class="tw-flex tw-items-center tw-justify-between tw-relative tw-z-10">
                  <div>
                    <p class="tw-text-xs tw-text-indigo-200 tw-font-medium tw-uppercase tw-tracking-wider tw-group-hover:tw-text-indigo-100 tw-transition-colors tw-duration-300">Attachments</p>
                    <p class="tw-text-2xl tw-font-bold tw-text-white tw-mt-1 tw-group-hover:tw-scale-110 tw-transition-transform tw-duration-300 tw-group-hover:tw-text-orange-100">{{ bonEntreeData?.attachments?.length || 0 }}</p>
                  </div>
                  <div class="tw-bg-white/20 tw-p-3 tw-rounded-xl tw-group-hover:tw-bg-white/30 tw-transition-all tw-duration-300 tw-group-hover:tw-scale-110 tw-group-hover:tw-rotate-12">
                    <i class="pi pi-paperclip tw-text-indigo-300 tw-text-2xl"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </template>
      </Card>
    </div>    <!-- Progress Indicator -->
    <div class="tw-mb-6">
      <Steps :model="stepsItems" :readonly="true" :activeIndex="activeStep" />
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
            <p class="tw-mt-4 tw-text-gray-600 tw-text-lg">Loading stock entry data...</p>
            <ProgressBar :value="loadingProgress" :showValue="false" class="tw-mt-4" />
          </div>
        </template>
      </Card>
    </div>

    <!-- Main Form -->
    <form v-else-if="bonEntreeData" @submit.prevent="submitForm" class="tw-space-y-6">

      <!-- Tabbed Content -->
      <TabView class="tw-shadow-xl tw-rounded-xl">
        <!-- Basic Information Tab -->
        <TabPanel>
          <template #header>
            <i class="pi pi-info-circle tw-mr-2"></i>
            Basic Information
          </template>

          <div class="tw-space-y-6">
            <!-- Status and Service -->
            <Card class="tw-border tw-shadow-sm">
              <template #content>
                <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-3 tw-gap-6">
                  <!-- Bon Reception Code -->
                  <div class="tw-space-y-2">
                    <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
                      <i class="pi pi-receipt tw-text-gray-500"></i>
                      Bon Reception Code
                    </label>
                    <div class="tw-flex tw-items-center tw-gap-2">
                      <InputText
                        :value="bonEntreeData.bon_reception?.bon_reception_code || 'N/A'"
                        disabled
                        class="tw-flex-1 tw-bg-gray-50 enhanced-input"
                      />
                      <Button
                        v-if="bonEntreeData.bon_reception"
                        @click="viewBonReception"
                        icon="pi pi-external-link"
                        class="p-button-text p-button-sm"
                        v-tooltip="'View Bon Reception'"
                      />
                    </div>
                  </div>

                  <!-- Service Selection -->
                  <div class="tw-space-y-2">
                    <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
                      <i class="pi pi-building tw-text-gray-500"></i>
                      Service <span class="tw-text-red-500">*</span>
                    </label>
                    <Dropdown
                      v-model="form.service_id"
                      :options="services"
                      optionLabel="name"
                      optionValue="id"
                      placeholder="Select a Service"
                      class="tw-w-full enhanced-dropdown"
                      :loading="loadingServices"
                      :disabled="bonEntreeData.status === 'transferred'"
                      filter
                      showClear
                      :virtualScrollerOptions="{ itemSize: 40 }"
                    >
                      <template #option="slotProps">
                        <div class="tw-flex tw-items-center tw-gap-3 hover:tw-bg-indigo-50 tw-p-2 tw-rounded">
                          <Avatar
                            icon="pi pi-building"
                            class="tw-bg-indigo-100 tw-text-indigo-700"
                            shape="circle"
                            size="small"
                          />
                          <div>
                            <div class="tw-font-medium">{{ slotProps.option.name }}</div>
                            <div class="tw-text-xs tw-text-gray-500">ID: {{ slotProps.option.service_id }}</div>
                          </div>
                        </div>
                      </template>
                    </Dropdown>
                    <small v-if="errors.service_id" class="tw-text-red-500 tw-text-xs">
                      <i class="pi pi-exclamation-circle tw-mr-1"></i>{{ errors.service_id[0] }}
                    </small>
                  </div>

                  <!-- Status Management -->
                  <div class="tw-space-y-2">
                    <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
                      <i class="pi pi-flag tw-text-gray-500"></i>
                      Status Management
                    </label>
                    <div class="tw-space-y-2">
                      <div class="tw-flex tw-items-center tw-gap-2">
                        <Tag
                          :value="getStatusLabel(bonEntreeData.status)"
                          :severity="getStatusSeverity(bonEntreeData.status)"
                          class="tw-text-lg tw-px-4 tw-py-2 tw-flex-1 tw-justify-center"
                        />
                      </div>
                      <div class="tw-flex tw-gap-2">
                        <Button
                          v-if="bonEntreeData.status === 'draft'"
                          @click="validateBonEntree"
                          icon="pi pi-check-circle"
                          label="Validate"
                          class="p-button-success tw-flex-1 enhanced-success-btn"
                          size="small"
                        />
                        <Button
                          v-if="bonEntreeData.status === 'validated'"
                          @click="transferToStock"
                          icon="pi pi-send"
                          label="Transfer to Stock"
                          class="p-button-warning tw-flex-1 enhanced-warning-btn"
                          size="small"
                        />
                        <Button
                          v-if="bonEntreeData.status === 'validated' || bonEntreeData.status === 'transferred'"
                          @click="generateTickets"
                          icon="pi pi-print"
                          label="Generate Tickets"
                          class="p-button-help tw-flex-1 enhanced-info-btn"
                          size="small"
                        />
                        <Button
                          v-if="bonEntreeData.status === 'draft'"
                          @click="cancelBonEntree"
                          icon="pi pi-times"
                          label="Cancel"
                          severity="danger"
                          class="tw-flex-1 enhanced-danger-btn"
                          size="small"
                        />
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Notes Section -->
                <Divider />
                <div class="tw-space-y-2">
                  <label class="tw-flex tw-items-center tw-justify-between">
                    <span class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
                      <i class="pi pi-comment tw-text-gray-500"></i>
                      Notes
                    </span>
                    <span class="tw-text-xs tw-text-gray-500">
                      {{ form.notes.length }}/500 characters
                    </span>
                  </label>
                  <Textarea
                    v-model="form.notes"
                    rows="3"
                    :maxlength="500"
                    placeholder="Add any additional notes..."
                    class="tw-w-full enhanced-textarea"
                    :disabled="bonEntreeData.status === 'transferred'"
                    autoResize
                  />
                  <small v-if="errors.notes" class="tw-text-red-500 tw-text-xs">
                    <i class="pi pi-exclamation-circle tw-mr-1"></i>{{ errors.notes[0] }}
                  </small>
                </div>
              </template>
            </Card>

            <!-- Supplier Information Card -->
            <Card v-if="bonEntreeData.fournisseur" class="tw-border tw-shadow-sm tw-bg-gradient-to-br tw-from-indigo-50 tw-to-purple-50">
              <template #title>
                <div class="tw-flex tw-items-center tw-gap-2 tw-text-indigo-800">
                  <i class="pi pi-users"></i>
                  <span>Supplier Information</span>
                </div>
              </template>
              <template #content>
                <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-4">
                  <div class="tw-bg-white tw-rounded-lg tw-p-4 tw-shadow-sm">
                    <label class="tw-text-xs tw-text-gray-600 tw-font-medium tw-uppercase tw-tracking-wider">Company</label>
                    <div class="tw-flex tw-items-center tw-gap-2 tw-mt-2">
                      <Avatar
                        :label="bonEntreeData.fournisseur.company_name?.charAt(0)"
                        class="tw-bg-indigo-100 tw-text-indigo-700"
                        shape="circle"
                      />
                      <div class="tw-text-lg tw-font-semibold tw-text-gray-800">
                        {{ bonEntreeData.fournisseur.company_name || 'N/A' }}
                      </div>
                    </div>
                  </div>

                  <div class="tw-bg-white tw-rounded-lg tw-p-4 tw-shadow-sm">
                    <label class="tw-text-xs tw-text-gray-600 tw-font-medium tw-uppercase tw-tracking-wider">Contact</label>
                    <div class="tw-text-lg tw-font-semibold tw-text-gray-800 tw-mt-2">
                      <i class="pi pi-user tw-mr-2 tw-text-gray-400"></i>
                      {{ bonEntreeData.fournisseur.contact_person || 'N/A' }}
                    </div>
                  </div>

                  <div class="tw-bg-white tw-rounded-lg tw-p-4 tw-shadow-sm">
                    <label class="tw-text-xs tw-text-gray-600 tw-font-medium tw-uppercase tw-tracking-wider">Phone</label>
                    <div class="tw-text-lg tw-font-semibold tw-text-gray-800 tw-mt-2">
                      <i class="pi pi-phone tw-mr-2 tw-text-gray-400"></i>
                      {{ bonEntreeData.fournisseur.phone || 'N/A' }}
                    </div>
                  </div>

                  <div class="tw-bg-white tw-rounded-lg tw-p-4 tw-shadow-sm">
                    <label class="tw-text-xs tw-text-gray-600 tw-font-medium tw-uppercase tw-tracking-wider">Email</label>
                    <div class="tw-text-lg tw-font-semibold tw-text-gray-800 tw-mt-2 tw-truncate">
                      <i class="pi pi-envelope tw-mr-2 tw-text-gray-400"></i>
                      {{ bonEntreeData.fournisseur.email || 'N/A' }}
                    </div>
                  </div>
                </div>
              </template>
            </Card>
          </div>
        </TabPanel>

        <!-- Products Tab -->
        <TabPanel>
          <template #header>
            <i class="pi pi-box tw-mr-2"></i>
            Products
            <Badge :value="form.items.length" class="tw-ml-2" severity="info" />
          </template>

          <div class="tw-space-y-4">
            <!-- Products Toolbar -->
            <div class="tw-flex tw-flex-col sm:tw-flex-row tw-justify-between tw-items-start sm:tw-items-center tw-gap-4">
              <div class="tw-flex tw-items-center tw-gap-4">
                <!-- Search for existing items -->
                <div v-if="form.items.length > 5" class="tw-relative">
                  <InputText
                    v-model="itemSearchQuery"
                    placeholder="Search products..."
                    class="tw-pr-8 enhanced-input"
                    @input="debouncedSearch"
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

              <div class="tw-flex tw-gap-2">
                <Button
                  v-if="bonEntreeData.status !== 'transferred'"
                  @click="importFromExcel"
                  icon="pi pi-file-excel"
                  label="Import Excel"
                  class="p-button-help"
                  size="small"
                />
                <Button
                  v-if="bonEntreeData.status !== 'transferred'"
                  @click="showProductModal = true"
                  icon="pi pi-plus"
                  label="Add Products"
                  class="p-button-success enhanced-success-btn"
                />
              </div>
            </div>

            <!-- Empty State -->
            <div v-if="form.items.length === 0" class="tw-bg-gray-50 tw-rounded-xl tw-border-2 tw-border-dashed tw-border-gray-300 tw-p-12">
              <div class="tw-text-center">
                <i class="pi pi-inbox tw-text-6xl tw-text-gray-300 tw-animate-bounce"></i>
                <p class="tw-mt-4 tw-text-gray-500 tw-text-lg">No products added yet</p>
                <p class="tw-text-gray-400 tw-mt-2">Start by adding products to this stock entry</p>
                <Button
                  v-if="bonEntreeData.status !== 'transferred'"
                  @click="showProductModal = true"
                  icon="pi pi-plus"
                  label="Add First Product"
                  class="p-button-outlined tw-mt-4 enhanced-primary-btn"
                />
              </div>
            </div>

            <!-- Table View -->
            <DataTable
              v-else-if="viewMode === 'table'"
              :value="filteredItems"
              :paginator="form.items.length > 10"
              :rows="10"
              :rowsPerPageOptions="[10, 25, 50, 100]"
              paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
              currentPageReportTemplate="Showing {first} to {last} of {totalRecords} products"
              responsiveLayout="scroll"
              class="tw-mt-4 large-datatable tw-shadow-2xl tw-rounded-2xl tw-overflow-hidden tw-border-0"
              :editMode="bonEntreeData.status !== 'transferred' ? 'cell' : null"
              @cell-edit-complete="onCellEditComplete"
              :rowClass="getRowClass"
              showGridlines
              :resizableColumns="true"
              columnResizeMode="expand"
            >
              <!-- Selection Column -->
              <Column v-if="bonEntreeData.status !== 'transferred'" selectionMode="multiple" style="width: 3rem"></Column>

              <!-- Index Column -->
              <Column header="#" style="width: 50px" :exportable="false">
                <template #body="slotProps">
                  <div class="tw-w-8 tw-h-8 tw-bg-gradient-to-br tw-from-indigo-500 tw-to-purple-600 tw-text-white tw-rounded-full tw-flex tw-items-center tw-justify-center tw-font-bold tw-text-sm tw-shadow">
                    {{ slotProps.index + 1 }}
                  </div>
                </template>
              </Column>

              <!-- Product Column -->
              <Column field="product_id" header="Product" :sortable="true" style="min-width: 350px">
                <template #body="slotProps">
                  <div class="tw-flex tw-items-center tw-gap-3">
                    <Avatar
                      :label="getProductDisplayName(slotProps.data)?.charAt(0)"
                      class="tw-bg-purple-100 tw-text-purple-700"
                      shape="square"
                    />
                    <div class="tw-flex-1">
                      <div class="tw-font-semibold tw-break-words">{{ getProductDisplayName(slotProps.data) || 'Select Product' }}</div>
                      <div class="tw-text-xs tw-text-gray-500 tw-space-y-1">
                        <div>Code: {{ getProductDisplayCode(slotProps.data) }}</div>
                        <div v-if="getProductDisplayCategory(slotProps.data)">
                          Category: <Tag :value="getProductDisplayCategory(slotProps.data)" severity="info" class="tw-ml-1" />
                        </div>
                        <div v-if="getProductInventoryQty(slotProps.data) !== null" class="tw-font-medium tw-text-green-600">
                          In Stock: {{ getProductInventoryQty(slotProps.data) }} units
                        </div>
                      </div>
                    </div>
                  </div>
                </template>
                <template #editor="slotProps" v-if="bonEntreeData.status !== 'transferred'">
                  <Dropdown
                    v-model="slotProps.data.product_id"
                    :options="products"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Select Product"
                    class="tw-w-full enhanced-dropdown"
                    filter
                    :virtualScrollerOptions="{ itemSize: 50 }"
                  >
                    <template #option="optionProps">
                      <div class="tw-py-2 hover:tw-bg-indigo-50 tw-px-2 tw-rounded">
                        <div class="tw-font-medium">{{ optionProps.option.name }}</div>
                        <div class="tw-text-xs tw-text-gray-500">Code: {{ optionProps.option.product_code }}</div>
                      </div>
                    </template>
                  </Dropdown>
                </template>
              </Column>

              <!-- Quantity Column -->
              <Column field="quantity" header="Quantity" :sortable="true" style="width: 120px">
                <template #body="slotProps">
                  <div class="tw-flex tw-flex-col tw-gap-1">
                    <Tag :value="`Remaining: ${slotProps.data.quantity}`" severity="info" class="tw-font-bold" />
                    <Tag
                      v-if="getSubQuantityTotal(slotProps.data) > 0"
                      :value="`Allocated: ${getSubQuantityTotal(slotProps.data)}`"
                      severity="warning"
                      class="tw-font-semibold"
                    />
                    <small v-if="getSubQuantityTotal(slotProps.data) > 0" class="tw-text-xs tw-text-gray-500">
                      Total: {{ getTotalQuantityForItem(slotProps.data) }}
                    </small>
                  </div>
                </template>
                <template #editor="slotProps" v-if="bonEntreeData.status !== 'transferred'">
                  <InputNumber
                    v-model="slotProps.data.quantity"
                    :min="0"
                    :max="9999"
                    showButtons
                    buttonLayout="vertical"
                    class="tw-w-full high-density-input"
                  />
                </template>
              </Column>

              <!-- Purchase Price Column -->
              <Column field="purchase_price" header="Purchase Price" :sortable="true" style="width: 150px">
                <template #body="slotProps">
                  <span class="tw-font-medium">{{ formatCurrency(slotProps.data.purchase_price) }}</span>
                </template>
                <template #editor="slotProps" v-if="bonEntreeData.status !== 'transferred'">
                  <InputNumber
                    v-model="slotProps.data.purchase_price"
                    :min="0"
                    mode="currency"
                    currency="DZD"
                    locale="fr-FR"
                    class="tw-w-full high-density-input"
                  />
                </template>
              </Column>

              <!-- Unit Column -->
              <Column field="unit" header="Unit" style="width: 100px">
                <template #body="slotProps">
                  <Tag
                    :value="slotProps.data.unit || 'unit'"
                    :severity="slotProps.data.unit === 'box' ? 'info' : 'success'"
                    class="tw-font-medium"
                  />
                </template>
                <template #editor="slotProps" v-if="bonEntreeData.status !== 'transferred'">
                  <Dropdown
                    v-model="slotProps.data.unit"
                    :options="[
                      { label: 'Unit', value: 'unit' },
                      { label: 'Box', value: 'box' }
                    ]"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select Unit"
                    class="tw-w-full high-density-input"
                  />
                </template>
              </Column>

              <!-- Batch Number Column -->
              <Column field="batch_number" header="Batch #" style="width: 150px">
                <template #body="slotProps">
                  <span class="tw-text-sm tw-font-mono">{{ slotProps.data.batch_number || '-' }}</span>
                </template>
                <template #editor="slotProps" v-if="bonEntreeData.status !== 'transferred'">
                  <InputText
                    v-model="slotProps.data.batch_number"
                    placeholder="Batch number"
                    class="tw-w-full high-density-input"
                  />
                </template>
              </Column>

              <!-- Expiry Date Column -->
              <Column field="expiry_date" header="Expiry" :sortable="true" style="width: 150px">
                <template #body="slotProps">
                  <Tag
                    v-if="slotProps.data.expiry_date"
                    :value="formatDate(slotProps.data.expiry_date)"
                    :severity="getExpirySeverity(slotProps.data.expiry_date)"
                    :icon="getExpiryIcon(slotProps.data.expiry_date)"
                  />
                  <span v-else class="tw-text-gray-400">-</span>
                </template>
                <template #editor="slotProps" v-if="bonEntreeData.status !== 'transferred'">
                  <Calendar
                    v-model="slotProps.data.expiry_date"
                    dateFormat="yy-mm-dd"
                    placeholder="Select date"
                    class="tw-w-full high-density-input"
                    showIcon
                  />
                </template>
              </Column>

              <!-- TVA Column -->
              <Column field="tva" header="TVA %" :sortable="true" style="width: 100px">
                <template #body="slotProps">
                  <span>{{ slotProps.data.tva || 0 }}%</span>
                </template>
                <template #editor="slotProps" v-if="bonEntreeData.status !== 'transferred'">
                  <InputNumber
                    v-model="slotProps.data.tva"
                    :min="0"
                    :max="100"
                    suffix="%"
                    class="tw-w-full high-density-input"
                  />
                </template>
              </Column>

              <!-- Total Column -->
              <Column header="Total" :sortable="true" style="width: 150px">
                <template #body="slotProps">
                  <span class="tw-font-bold tw-text-green-600 tw-text-lg">
                    {{ formatCurrency(calculateItemTotal(slotProps.data)) }}
                  </span>
                </template>
              </Column>

              <!-- Actions Column -->
              <Column v-if="bonEntreeData.status !== 'transferred'" header="Actions" style="width: 160px" :exportable="false">
                <template #body="slotProps">
                  <div class="tw-flex tw-gap-1">
                    <Button
                      @click="openSubItemManager(slotProps.index)"
                      icon="pi pi-sitemap"
                      class="p-button-text p-button-sm p-button-warning"
                      v-tooltip="'Manage Batches'"
                    />
                    <Button
                      @click="editItemDetails(slotProps.data)"
                      icon="pi pi-pencil"
                      class="p-button-text p-button-sm p-button-info"
                      v-tooltip="'Edit Details'"
                    />
                    <Button
                      @click="duplicateItem(slotProps.data)"
                      icon="pi pi-copy"
                      class="p-button-text p-button-sm p-button-success"
                      v-tooltip="'Duplicate'"
                    />
                    <Button
                      @click="removeItem(slotProps.index)"
                      icon="pi pi-trash"
                      class="p-button-text p-button-sm p-button-danger"
                      v-tooltip="'Remove'"
                    />
                  </div>
                </template>
              </Column>

              <!-- Footer Template -->
              <template #footer>
                <div class="tw-bg-gradient-to-r tw-from-indigo-50 tw-to-purple-50 tw-p-4 tw-rounded-lg">
                  <div class="tw-flex tw-justify-between tw-items-center">
                    <div class="tw-text-gray-600">
                      <span class="tw-font-medium">Total Items:</span> {{ form.items.length }}
                      <span class="tw-mx-4">|</span>
                      <span class="tw-font-medium">Total Quantity:</span> {{ calculateTotalQuantity() }}
                    </div>
                    <div class="tw-text-2xl tw-font-bold tw-text-indigo-600">
                      Grand Total: {{ formatCurrency(calculateTotalAmount()) }}
                    </div>
                  </div>
                </div>
              </template>
            </DataTable>

            <!-- Grid View -->
            <div v-else-if="viewMode === 'grid'" class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-4">
              <div
                v-for="(item, index) in filteredItems"
                :key="index"
                class="tw-bg-white tw-rounded-xl tw-shadow-md hover:tw-shadow-xl tw-transition-shadow tw-overflow-hidden"
              >
                <div class="tw-bg-gradient-to-r tw-from-indigo-500 tw-to-purple-600 tw-p-3">
                  <div class="tw-flex tw-justify-between tw-items-center">
                    <span class="tw-text-white tw-font-bold">Item #{{ index + 1 }}</span>
                    <div class="tw-flex tw-gap-1">
                      <Button
                        @click="openSubItemManager(index)"
                        icon="pi pi-sitemap"
                        class="p-button-text p-button-sm tw-text-white"
                        v-tooltip="'Manage Batches'"
                      />
                      <Button
                        @click="editItemDetails(item)"
                        icon="pi pi-pencil"
                        class="p-button-text p-button-sm tw-text-white"
                        v-tooltip="'Edit'"
                      />
                      <Button
                        @click="removeItem(index)"
                        icon="pi pi-trash"
                        class="p-button-text p-button-sm tw-text-white"
                        v-tooltip="'Remove'"
                      />
                    </div>
                  </div>
                </div>
                <div class="tw-p-4 tw-space-y-3">
                  <div>
                    <label class="tw-text-xs tw-text-gray-500 tw-uppercase tw-tracking-wider">Product</label>
                    <div class="tw-font-semibold tw-text-gray-800">
                      {{ getProductById(item.product_id)?.name || 'Unknown' }}
                    </div>
                  </div>
                  <div class="tw-grid tw-grid-cols-2 tw-gap-3">
                    <div>
                      <label class="tw-text-xs tw-text-gray-500">Quantity</label>
                      <div class="tw-flex tw-flex-col tw-gap-1 tw-mt-1">
                        <Tag :value="`Remaining: ${item.quantity}`" severity="info" />
                        <Tag
                          v-if="getSubQuantityTotal(item) > 0"
                          :value="`Allocated: ${getSubQuantityTotal(item)}`"
                          severity="warning"
                        />
                        <small v-if="getSubQuantityTotal(item) > 0" class="tw-text-xs tw-text-gray-500">
                          Total: {{ getTotalQuantityForItem(item) }}
                        </small>
                      </div>
                    </div>
                    <div>
                      <label class="tw-text-xs tw-text-gray-500">Price</label>
                      <div class="tw-font-medium tw-mt-1">{{ formatCurrency(item.purchase_price) }}</div>
                    </div>
                    <div>
                      <label class="tw-text-xs tw-text-gray-500">Batch</label>
                      <div class="tw-font-mono tw-text-sm tw-mt-1">{{ item.batch_number || '-' }}</div>
                    </div>
                    <div>
                      <label class="tw-text-xs tw-text-gray-500">Expiry</label>
                      <Tag
                        v-if="item.expiry_date"
                        :value="formatDate(item.expiry_date)"
                        :severity="getExpirySeverity(item.expiry_date)"
                        class="tw-mt-1"
                      />
                      <span v-else class="tw-text-gray-400">-</span>
                    </div>
                  </div>
                  <div v-if="getSubItems(item).length" class="tw-bg-indigo-50 tw-rounded-lg tw-p-3">
                    <label class="tw-text-xs tw-text-indigo-700 tw-font-semibold tw-uppercase">Sub Batches</label>
                    <div class="tw-mt-2 tw-space-y-2">
                      <div
                        v-for="(sub, subIndex) in getSubItems(item)"
                        :key="sub.id || subIndex"
                        class="tw-flex tw-justify-between tw-items-start tw-bg-white tw-border tw-border-indigo-100 tw-rounded-lg tw-p-2"
                      >
                        <div>
                          <div class="tw-font-semibold tw-text-gray-700">
                            {{ sub.batch_number || ('Batch ' + (subIndex + 1)) }}
                          </div>
                          <div class="tw-text-xs tw-text-gray-500">
                            {{ sub.quantity }} {{ sub.unit || item.unit || 'unit' }} · {{ formatCurrency(sub.purchase_price) }}
                          </div>
                          <div v-if="sub.expiry_date" class="tw-text-xs tw-text-gray-400">
                            Expires: {{ formatDate(sub.expiry_date) }}
                          </div>
                        </div>
                        <Tag :value="`#${subIndex + 1}`" severity="info" />
                      </div>
                    </div>
                  </div>
                  <Divider />
                  <div class="tw-flex tw-justify-between tw-items-center">
                    <span class="tw-text-sm tw-text-gray-500">Total</span>
                    <span class="tw-text-lg tw-font-bold tw-text-green-600">
                      {{ formatCurrency(calculateItemTotal(item)) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </TabPanel>

        <!-- Attachments Tab -->
        <TabPanel>
          <template #header>
            <i class="pi pi-paperclip tw-mr-2"></i>
            Attachments
            <Badge :value="bonEntreeData?.attachments?.length || 0" class="tw-ml-2" severity="warning" />
          </template>

          <Card class="tw-border-0">
            <template #content>
              <AttachmentUploader
                v-model="bonEntreeData.attachments"
                model-type="bon_entree"
                :model-id="bonEntreeData.id"
                :disabled="bonEntreeData.status === 'transferred'"
                @uploaded="onAttachmentsUpdated"
                @deleted="onAttachmentsUpdated"
              />
            </template>
          </Card>
        </TabPanel>

        <!-- History Tab -->
        <TabPanel>
          <template #header>
            <i class="pi pi-history tw-mr-2"></i>
            History
          </template>

          <Timeline :value="historyEvents" align="alternate" class="tw-mt-6">
            <template #marker="slotProps">
              <span class="tw-flex tw-w-10 tw-h-10 tw-items-center tw-justify-center tw-text-white tw-rounded-full tw-z-10"
                    :style="{ backgroundColor: getEventColor(slotProps.item.type) }">
                <i :class="slotProps.item.icon"></i>
              </span>
            </template>
            <template #content="slotProps">
              <Card class="tw-shadow-md">
                <template #content>
                  <div class="tw-text-sm tw-text-gray-500 tw-mb-1">
                    {{ formatDateTime(slotProps.item.date) }}
                  </div>
                  <div class="tw-font-semibold tw-text-gray-800">{{ slotProps.item.title }}</div>
                  <div class="tw-text-sm tw-text-gray-600 tw-mt-1">{{ slotProps.item.description }}</div>
                  <div v-if="slotProps.item.user" class="tw-text-xs tw-text-gray-500 tw-mt-2">
                    <i class="pi pi-user tw-mr-1"></i>{{ slotProps.item.user }}
                  </div>
                </template>
              </Card>
            </template>
          </Timeline>
        </TabPanel>
      </TabView>

      <!-- Summary Card -->
      <Card class="tw-border-0 tw-shadow-xl tw-bg-gradient-to-r tw-from-green-50 tw-to-emerald-50">
        <template #content>
          <div class="tw-grid tw-grid-cols-2 md:tw-grid-cols-5 tw-gap-4">
            <div class="tw-bg-white tw-rounded-lg tw-p-4 tw-shadow-sm">
              <div class="tw-text-sm tw-text-gray-600 tw-mb-1">Subtotal</div>
              <div class="tw-text-xl tw-font-bold tw-text-gray-800">{{ formatCurrency(calculateSubtotal()) }}</div>
            </div>
            <div class="tw-bg-white tw-rounded-lg tw-p-4 tw-shadow-sm">
              <div class="tw-text-sm tw-text-gray-600 tw-mb-1">Total TVA</div>
              <div class="tw-text-xl tw-font-bold tw-text-blue-600">{{ formatCurrency(calculateTotalTVA()) }}</div>
            </div>
            <div class="tw-bg-white tw-rounded-lg tw-p-4 tw-shadow-sm">
              <div class="tw-text-sm tw-text-gray-600 tw-mb-1">Items Count</div>
              <div class="tw-text-xl tw-font-bold tw-text-purple-600">{{ form.items.length }}</div>
            </div>
            <div class="tw-bg-white tw-rounded-lg tw-p-4 tw-shadow-sm">
              <div class="tw-text-sm tw-text-gray-600 tw-mb-1">Total Quantity</div>
              <div class="tw-text-xl tw-font-bold tw-text-indigo-600">{{ calculateTotalQuantity() }}</div>
            </div>
            <div class="tw-bg-gradient-to-r tw-from-green-500 tw-to-emerald-600 tw-rounded-lg tw-p-4 tw-shadow-sm tw-text-white">
              <div class="tw-text-sm tw-text-green-100 tw-mb-1">Total Amount</div>
              <div class="tw-text-2xl tw-font-bold">{{ formatCurrency(calculateTotalAmount()) }}</div>
            </div>
          </div>
        </template>
      </Card>

      <!-- Form Actions -->
      <Card class="tw-border-0 tw-shadow-xl">
        <template #content>
          <div class="tw-flex tw-flex-col sm:tw-flex-row tw-justify-between tw-items-center tw-gap-4">
            <div class="tw-flex tw-items-center tw-gap-4 tw-text-sm tw-text-gray-500">
              <div>
                <i class="pi pi-calendar tw-mr-1"></i>
                Created: {{ formatDateTime(bonEntreeData.created_at) }}
              </div>
              <div>
                <i class="pi pi-refresh tw-mr-1"></i>
                Updated: {{ formatDateTime(bonEntreeData.updated_at) }}
              </div>
            </div>
            <div class="tw-flex tw-gap-3">
              <Button
                type="button"
                @click="router.back()"
                label="Cancel"
                icon="pi pi-times"
                class="p-button-secondary enhanced-cancel-btn"
                size="large"
              />
              <Button
                v-if="bonEntreeData.status !== 'transferred'"
                type="button"
                @click="saveAsDraft"
                :loading="savingDraft"
                label="Save as Draft"
                icon="pi pi-save"
                class="p-button-info enhanced-info-btn"
                size="large"
              />
              <Button
                v-if="bonEntreeData.status !== 'transferred'"
                type="submit"
                :loading="saving"
                label="Update Stock Entry"
                icon="pi pi-check"
                class="tw-bg-gradient-to-r tw-from-indigo-600 tw-to-purple-700 enhanced-primary-btn"
                size="large"
              />
            </div>
          </div>
        </template>
      </Card>
    </form>

    <!-- Product Selection Modal -->
    <Dialog 
      :visible="showProductModal" 
      header="Add Products" 
      :modal="true"
      :style="{ width: '95vw', maxWidth: '1600px', height: '90vh' }"
      :maximizable="true"
      class="product-selector-dialog"
      @update:visible="val => (showProductModal = val)"
      @hide="showProductModal = false"
    >
      <!-- Pricing Information Panel -->
      <div v-if="loadingPricingInfo || currentProductPricingInfo" class="tw-mb-4">
        <div v-if="loadingPricingInfo" class="tw-flex tw-justify-center tw-py-8">
          <i class="pi pi-spin pi-spinner tw-text-3xl tw-text-teal-600"></i>
        </div>
        <ProductPricingInfoPanel 
          v-else
          :pricing-info="currentProductPricingInfo" 
        />
      </div>
      
      <ProductSelectorWithInfiniteScroll
        v-model="selectedProducts"
        :scroll-height="'60vh'"
        :per-page="20"
        :show-source-filter="true"
        :show-stock="true"
        :show-select-all="true"
        :selectable="true"
        :default-tab="isPharmacyOrder ? 'pharmacy' : 'stock'"
        :default-quantity="defaultItemSettings.quantity"
        :default-price="defaultItemSettings.purchasingPrice"
        :default-unit="defaultItemSettings.unit"
        @selection-change="handleProductSelectionChange"
        @defaults-change="handleDefaultsChange"
        ref="productSelectorRef"
      />

      <template #footer>
        <div class="tw-flex tw-justify-between tw-items-center tw-w-full">
          <div class="tw-text-sm tw-text-gray-600">
            <i class="pi pi-info-circle tw-mr-1"></i>
            {{ selectedProducts.length }} product(s) selected
          </div>
          <div class="tw-flex tw-gap-3">
            <Button
              label="Cancel"
              icon="pi pi-times"
              @click="showProductModal = false"
              class="p-button-text"
            />
            <Button
              label="Add Selected Products"
              icon="pi pi-plus"
              @click="addSelectedProducts"
              :disabled="selectedProducts.length === 0"
              class="p-button-success enhanced-success-btn"
            />
          </div>
        </div>
      </template>
    </Dialog>

    <!-- Item Details Edit Modal -->
    <Dialog 
      v-model="showItemDetailsModal" 
      :header="`Edit Product: ${editingItem?.product_name || ''}`"
      :modal="true"
      :style="{ width: '700px' }"
    >
      <div v-if="editingItem" class="tw-space-y-4">
        <div class="tw-grid tw-grid-cols-2 tw-gap-4">
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">Quantity</label>
            <InputNumber
              v-model="editingItem.quantity"
              :min="0"
              showButtons
              class="tw-w-full"
            />
          </div>
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">Purchase Price</label>
            <InputNumber
              v-model="editingItem.purchase_price"
              :min="0"
              mode="currency"
              currency="DZD"
              locale="fr-FR"
              class="tw-w-full"
            />
          </div>
        </div>

        <div class="tw-grid tw-grid-cols-2 tw-gap-4">
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">Batch Number</label>
            <InputText
              v-model="editingItem.batch_number"
              placeholder="Batch number"
              class="tw-w-full"
            />
          </div>
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">Serial Number</label>
            <InputText
              v-model="editingItem.serial_number"
              placeholder="Serial number"
              class="tw-w-full"
            />
          </div>
        </div>

        <div class="tw-grid tw-grid-cols-2 tw-gap-4">
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">TVA (%)</label>
            <InputNumber
              v-model="editingItem.tva"
              :min="0"
              :max="100"
              suffix="%"
              class="tw-w-full"
            />
          </div>
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">Unit</label>
            <Dropdown
              v-model="editingItem.unit"
              :options="[
                { label: 'Unit', value: 'unit' },
                { label: 'Box', value: 'box' }
              ]"
              optionLabel="label"
              optionValue="value"
              placeholder="Select Unit"
              class="tw-w-full"
            />
          </div>
        </div>

        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">Storage Location</label>
          <InputText
            v-model="editingItem.storage_name"
            placeholder="Storage location"
            class="tw-w-full"
          />
        </div>

        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1">Remarks</label>
          <Textarea
            v-model="editingItem.remarks"
            placeholder="Additional remarks..."
            class="tw-w-full"
            rows="3"
            autoResize
          />
        </div>

        <!-- Live Total Display -->
        <div class="tw-bg-gradient-to-r tw-from-green-50 tw-to-emerald-50 tw-p-4 tw-rounded-lg tw-border tw-border-green-200">
          <div class="tw-flex tw-justify-between tw-items-center">
            <span class="tw-text-gray-700 tw-font-medium">Item Total (with TVA):</span>
            <span class="tw-text-2xl tw-font-bold tw-text-green-600">
              {{ formatCurrency(calculateItemTotal(editingItem)) }}
            </span>
          </div>
        </div>
      </div>

      <template #footer>
        <Button 
          label="Cancel" 
          icon="pi pi-times" 
          @click="showItemDetailsModal = false" 
          class="p-button-text"
        />
        <Button 
          label="Save Changes" 
          icon="pi pi-check" 
          @click="saveItemDetails" 
          class="p-button-primary"
        />
      </template>
    </Dialog>

    <!-- Sub Item Management Modal -->
    <Dialog
      :visible="showSubItemModal"
      header="Manage Product Batches"
      :modal="true"
      :style="{ width: '900px', maxWidth: '95vw' }"
      @update:visible="val => (showSubItemModal = val)"
      @hide="closeSubItemModal"
      class="sub-item-modal"
    >
      <div v-if="currentSubItemParent" class="tw-space-y-6">
        <!-- Enhanced Product Header Card -->
        <div class="tw-bg-gradient-to-br tw-from-indigo-500 tw-via-purple-500 tw-to-pink-500 tw-rounded-2xl tw-p-6 tw-shadow-2xl tw-relative tw-overflow-hidden">
          <!-- Animated background elements -->
          <div class="tw-absolute tw-inset-0 tw-bg-gradient-to-r tw-from-white/10 tw-via-transparent tw-to-white/10 tw-animate-shimmer"></div>
          <div class="tw-absolute tw-top-0 tw-left-0 tw-w-full tw-h-1 tw-bg-gradient-to-r tw-from-indigo-400 tw-to-purple-400"></div>

          <div class="tw-flex tw-flex-col md:tw-flex-row tw-justify-between tw-gap-6 tw-relative tw-z-10">
            <div class="tw-flex tw-items-center tw-gap-4">
              <div class="tw-bg-white/20 tw-p-4 tw-rounded-2xl tw-backdrop-blur-sm">
                <i class="pi pi-box tw-text-2xl tw-text-white"></i>
              </div>
              <div>
                <div class="tw-text-white tw-font-bold tw-text-xl tw-mb-1">
                  {{ currentSubItemParent.product_name || getProductById(currentSubItemParent.product_id)?.name || 'Selected Product' }}
                </div>
                <div class="tw-text-indigo-100 tw-text-sm">
                  Code: {{ getProductById(currentSubItemParent.product_id)?.product_code || 'N/A' }}
                </div>
              </div>
            </div>

            <div class="tw-grid tw-grid-cols-2 tw-gap-4 tw-min-w-[200px]">
              <div class="tw-bg-white/15 tw-backdrop-blur-md tw-rounded-xl tw-p-4 tw-border tw-border-white/20">
                <div class="tw-text-xs tw-text-indigo-200 tw-font-medium tw-uppercase tw-tracking-wider">Remaining</div>
                <div class="tw-text-2xl tw-font-bold tw-text-white tw-mt-1">{{ currentSubRemainingQuantity }}</div>
              </div>
              <div class="tw-bg-white/15 tw-backdrop-blur-md tw-rounded-xl tw-p-4 tw-border tw-border-white/20">
                <div class="tw-text-xs tw-text-purple-200 tw-font-medium tw-uppercase tw-tracking-wider">Allocated</div>
                <div class="tw-text-2xl tw-font-bold tw-text-white tw-mt-1">{{ getSubQuantityTotal(currentSubItemParent) }}</div>
              </div>
              <div class="tw-col-span-2 tw-text-center tw-text-xs tw-text-indigo-200 tw-mt-2">
                Total quantity: <span class="tw-font-semibold">{{ getTotalQuantityForItem(currentSubItemParent) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Existing Sub Batches Section -->
        <div v-if="getSubItems(currentSubItemParent).length" class="tw-bg-white tw-border tw-border-gray-200 tw-rounded-2xl tw-shadow-lg tw-overflow-hidden">
          <div class="tw-bg-gradient-to-r tw-from-slate-50 tw-to-gray-50 tw-px-6 tw-py-4 tw-border-b tw-border-gray-200">
            <div class="tw-flex tw-items-center tw-gap-3">
              <div class="tw-bg-indigo-100 tw-p-2 tw-rounded-lg">
                <i class="pi pi-list tw-text-indigo-600"></i>
              </div>
              <div>
                <div class="tw-text-sm tw-font-semibold tw-text-gray-800">Existing Sub Batches</div>
                <div class="tw-text-xs tw-text-gray-500">{{ getSubItems(currentSubItemParent).length }} batch(es) allocated</div>
              </div>
            </div>
          </div>

          <DataTable
            :value="getSubItems(currentSubItemParent)"
            dataKey="id"
            size="small"
            class="tw-rounded-2xl"
            responsiveLayout="scroll"
            :paginator="getSubItems(currentSubItemParent).length > 5"
            :rows="5"
          >
            <Column field="batch_number" header="Batch" style="min-width: 150px">
              <template #body="slotProps">
                <div class="tw-flex tw-items-center tw-gap-2">
                  <div class="tw-bg-purple-100 tw-p-1 tw-rounded">
                    <i class="pi pi-tag tw-text-purple-600 tw-text-xs"></i>
                  </div>
                  <span class="tw-font-medium">{{ slotProps.data.batch_number || ('Batch ' + (slotProps.index + 1)) }}</span>
                </div>
              </template>
            </Column>
            <Column field="quantity" header="Quantity" style="width: 120px">
              <template #body="slotProps">
                <Tag :value="slotProps.data.quantity" severity="warning" class="tw-font-bold tw-px-3" />
              </template>
            </Column>
            <Column field="unit" header="Unit" style="width: 100px">
              <template #body="slotProps">
                <Tag :value="slotProps.data.unit || currentSubItemParent.unit || 'unit'" severity="info" class="tw-font-medium" />
              </template>
            </Column>
            <Column field="purchase_price" header="Price" style="width: 140px">
              <template #body="slotProps">
                <span class="tw-font-semibold tw-text-green-600">{{ formatCurrency(slotProps.data.purchase_price) }}</span>
              </template>
            </Column>
            <Column field="expiry_date" header="Expiry" style="width: 150px">
              <template #body="slotProps">
                <Tag
                  v-if="slotProps.data.expiry_date"
                  :value="formatDate(slotProps.data.expiry_date)"
                  :severity="getExpirySeverity(slotProps.data.expiry_date)"
                  class="tw-px-3"
                />
                <span v-else class="tw-text-gray-400 tw-italic">No expiry</span>
              </template>
            </Column>
            <Column header="Actions" style="width: 110px" :exportable="false">
              <template #body="slotProps">
                <div class="tw-flex tw-gap-1">
                  <Button
                    icon="pi pi-pencil"
                    class="p-button-text p-button-sm p-button-info tw-rounded-lg"
                    v-tooltip="'Edit batch'"
                    @click="editSubItem(slotProps.data, slotProps.index)"
                  />
                  <Button
                    icon="pi pi-trash"
                    class="p-button-text p-button-sm p-button-danger tw-rounded-lg"
                    v-tooltip="'Remove batch'"
                    @click="removeSubItem(slotProps.index)"
                  />
                </div>
              </template>
            </Column>
          </DataTable>
        </div>
        <div v-else class="tw-bg-gradient-to-br tw-from-gray-50 tw-to-slate-50 tw-border tw-border-dashed tw-border-gray-300 tw-rounded-2xl tw-p-8 tw-text-center">
          <div class="tw-bg-white tw-p-4 tw-rounded-full tw-inline-block tw-mb-4 tw-shadow-md">
            <i class="pi pi-inbox tw-text-3xl tw-text-gray-400"></i>
          </div>
          <p class="tw-text-gray-600 tw-text-lg tw-font-medium tw-mb-2">No sub batches defined yet</p>
          <p class="tw-text-gray-500 tw-text-sm">Allocate a portion of this product below to create sub batches.</p>
        </div>

        <div class="tw-bg-gradient-to-br tw-from-white tw-via-indigo-50 tw-to-white tw-border tw-border-indigo-100 tw-rounded-xl tw-shadow-sm tw-p-4 tw-space-y-4">
          <div class="tw-flex tw-flex-col md:tw-flex-row tw-justify-between tw-gap-3">
            <div class="tw-flex tw-items-center tw-gap-3">
              <span class="tw-inline-flex tw-items-center tw-justify-center tw-w-12 tw-h-12 tw-rounded-2xl tw-bg-gradient-to-br tw-from-indigo-500 tw-to-purple-600 tw-text-white tw-shadow-lg">
                <i class="pi pi-layer-plus tw-text-xl"></i>
              </span>
              <div>
                <div class="tw-text-lg tw-font-bold tw-text-indigo-700">Quick Allocation Builder</div>
                <div class="tw-text-sm tw-text-gray-500">Create multiple batch splits before saving.</div>
              </div>
            </div>
            <div class="tw-flex tw-flex-wrap tw-gap-2 tw-items-center">
              <Button
                label="Reset All"
                icon="pi pi-refresh"
                class="p-button-text p-button-sm tw-rounded-lg tw-border-2 tw-border-gray-200 hover:tw-border-indigo-300"
                @click="resetPendingSubItems"
                :disabled="pendingSubItems.length === 0 || isPendingSectionDisabled"
              />
              <Button
                label="Add Batch"
                icon="pi pi-plus"
                class="p-button-sm p-button-rounded p-button-primary tw-font-semibold tw-px-4 tw-py-2 tw-rounded-xl tw-shadow-md hover:tw-shadow-lg tw-transition-all tw-duration-300"
                @click="addPendingSubItemRow"
                :disabled="isPendingSectionDisabled"
              />
            </div>
          </div>

          <div v-if="pendingSubItems.length" class="tw-space-y-4">
            <div
              v-for="(pending, index) in pendingSubItems"
              :key="pending.tempId"
              class="tw-bg-gradient-to-br tw-from-white tw-via-indigo-50/30 tw-to-white tw-border tw-border-indigo-200 tw-rounded-2xl tw-p-6 tw-shadow-sm tw-transition-all tw-duration-300 hover:tw-shadow-xl hover:tw-border-indigo-300 tw-relative tw-overflow-hidden"
            >
              <!-- Background decoration -->
              <div class="tw-absolute tw-top-0 tw-right-0 tw-w-32 tw-h-32 tw-bg-gradient-to-bl tw-from-indigo-100/20 tw-to-transparent tw-rounded-full tw--translate-y-16 tw-translate-x-16"></div>
              <div class="tw-absolute tw-bottom-0 tw-left-0 tw-w-24 tw-h-24 tw-bg-gradient-to-tr tw-from-purple-100/20 tw-to-transparent tw-rounded-full tw-translate-y-12 tw--translate-x-12"></div>

              <div class="tw-flex tw-justify-between tw-items-center tw-mb-6 tw-relative tw-z-10">
                <div class="tw-flex tw-items-center tw-gap-4">
                  <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-2xl tw-bg-gradient-to-br tw-from-indigo-500 tw-to-purple-600 tw-text-white tw-font-bold tw-text-lg tw-shadow-lg">
                    {{ index + 1 }}
                  </div>
                  <div>
                    <div class="tw-text-lg tw-font-bold tw-text-gray-800">Batch Allocation</div>
                    <div class="tw-text-sm tw-text-gray-500">Configure batch details</div>
                  </div>
                </div>
                <Button
                  icon="pi pi-times"
                  class="p-button-rounded p-button-text p-button-danger p-button-sm tw-rounded-xl tw-w-8 tw-h-8 tw-border-2 tw-border-red-200 hover:tw-border-red-400 hover:tw-bg-red-50"
                  @click="removePendingSubItemRow(index)"
                  :disabled="pendingSubItems.length <= 1"
                  v-tooltip="'Remove this batch allocation'"
                />
              </div>

              <div class="tw-grid md:tw-grid-cols-5 tw-gap-6 tw-relative tw-z-10">
                <div class="tw-space-y-3">
                  <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-bold tw-text-gray-700 tw-uppercase tw-tracking-wider">
                    <i class="pi pi-hashtag tw-text-indigo-500 tw-text-base"></i>
                    Quantity
                  </label>
                  <div class="tw-relative">
                    <input
                      v-model.number="pending.quantity"
                      type="number"
                      min="0"
                      :max="currentSubRemainingQuantity"
                      class="tw-w-full high-density-input tw-rounded-xl tw-border-2 tw-border-gray-200 hover:tw-border-indigo-300 focus:tw-border-indigo-500 focus:tw-ring-4 focus:tw-ring-indigo-100 tw-transition-all tw-duration-200 tw-shadow-sm hover:tw-shadow-md tw-text-center tw-font-bold tw-text-gray-800 tw-text-lg"
                      :class="{ 'tw-border-red-300 focus:tw-border-red-500 focus:tw-ring-red-100': pending.quantity > currentSubRemainingQuantity }"
                    />
                    <div v-if="pending.quantity > currentSubRemainingQuantity" class="tw-absolute tw-right-2 tw-top-1/2 tw--translate-y-1/2">
                      <i class="pi pi-exclamation-triangle tw-text-red-500 tw-text-sm"></i>
                    </div>
                  </div>
                </div>
                <div class="tw-space-y-3">
                  <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-bold tw-text-gray-700 tw-uppercase tw-tracking-wider">
                    <i class="pi pi-dollar tw-text-green-500 tw-text-base"></i>
                    Price
                  </label>
                  <div class="tw-relative">
                    <input
                      v-model.number="pending.purchase_price"
                      type="number"
                      min="0"
                      step="0.01"
                      class="tw-w-full high-density-input tw-rounded-xl tw-border-2 tw-border-gray-200 hover:tw-border-green-300 focus:tw-border-green-500 focus:tw-ring-4 focus:tw-ring-green-100 tw-transition-all tw-duration-200 tw-shadow-sm hover:tw-shadow-md tw-font-bold tw-text-green-700 tw-text-lg"
                      :class="{ 'tw-border-red-300 focus:tw-border-red-500 focus:tw-ring-red-100': pending.purchase_price < 0 }"
                    />
                    <div v-if="pending.purchase_price < 0" class="tw-absolute tw-right-2 tw-top-1/2 tw--translate-y-1/2">
                      <i class="pi pi-exclamation-triangle tw-text-red-500 tw-text-sm"></i>
                    </div>
                  </div>
                </div>
                <div class="tw-space-y-3">
                  <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-bold tw-text-gray-700 tw-uppercase tw-tracking-wider">
                    <i class="pi pi-box tw-text-blue-500 tw-text-base"></i>
                    Unit
                  </label>
                  <div class="tw-relative">
                    <select
                      v-model="pending.unit"
                      class="tw-w-full high-density-input tw-rounded-xl tw-border-2 tw-border-gray-200 hover:tw-border-blue-300 focus:tw-border-blue-500 focus:tw-ring-4 focus:tw-ring-blue-100 tw-transition-all tw-duration-200 tw-shadow-sm hover:tw-shadow-md tw-bg-white tw-pl-4 tw-pr-10 tw-py-3 tw-font-semibold tw-text-gray-800"
                    >
                      <option value="" disabled>Select unit</option>
                      <option value="unit">Unit</option>
                      <option value="box">Box</option>
                    </select>
                    <div class="tw-absolute tw-right-3 tw-top-1/2 tw--translate-y-1/2 tw-pointer-events-none">
                      <i class="pi pi-chevron-down tw-text-gray-500 tw-text-sm"></i>
                    </div>
                  </div>
                </div>
                <div class="tw-space-y-3">
                  <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-bold tw-text-gray-700 tw-uppercase tw-tracking-wider">
                    <i class="pi pi-tag tw-text-purple-500 tw-text-base"></i>
                    Batch
                  </label>
                  <div class="tw-relative">
                    <input
                      v-model="pending.batch_number"
                      type="text"
                      placeholder="Enter batch number"
                      class="tw-w-full high-density-input tw-rounded-xl tw-border-2 tw-border-gray-200 hover:tw-border-purple-300 focus:tw-border-purple-500 focus:tw-ring-4 focus:tw-ring-purple-100 tw-transition-all tw-duration-200 tw-shadow-sm hover:tw-shadow-md tw-pl-12 tw-pr-4 tw-font-semibold tw-text-gray-800"
                    />
                    <div class="tw-absolute tw-left-3 tw-top-1/2 tw--translate-y-1/2">
                      <i class="pi pi-tag tw-text-purple-400 tw-text-sm"></i>
                    </div>
                  </div>
                </div>
                <div class="tw-space-y-3">
                  <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-bold tw-text-gray-700 tw-uppercase tw-tracking-wider">
                    <i class="pi pi-calendar tw-text-orange-500 tw-text-base"></i>
                    Expiry
                  </label>
                  <div class="tw-relative">
                    <input
                      v-model="pending.expiry_date"
                      type="date"
                      class="tw-w-full high-density-input tw-rounded-xl tw-border-2 tw-border-gray-200 hover:tw-border-orange-300 focus:tw-border-orange-500 focus:tw-ring-4 focus:tw-ring-orange-100 tw-transition-all tw-duration-200 tw-shadow-sm hover:tw-shadow-md tw-pr-12 tw-pl-4 tw-font-semibold tw-text-gray-800"
                      :class="{ 'tw-border-red-300 focus:tw-border-red-500 focus:tw-ring-red-100': pending.expiry_date && new Date(pending.expiry_date) < new Date() }"
                    />
                    <div class="tw-absolute tw-right-3 tw-top-1/2 tw--translate-y-1/2">
                      <i class="pi pi-calendar tw-text-orange-500 tw-text-sm"></i>
                    </div>
                    <div v-if="pending.expiry_date && new Date(pending.expiry_date) < new Date()" class="tw-absolute tw-right-12 tw-top-1/2 tw--translate-y-1/2">
                      <i class="pi pi-exclamation-triangle tw-text-red-500 tw-text-sm"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="tw-bg-gradient-to-br tw-from-gray-50 tw-via-indigo-50/20 tw-to-gray-50 tw-border tw-border-dashed tw-border-indigo-200 tw-rounded-2xl tw-p-8 tw-text-center tw-shadow-inner tw-relative tw-overflow-hidden">
            <!-- Background decoration -->
            <div class="tw-absolute tw-top-0 tw-right-0 tw-w-24 tw-h-24 tw-bg-gradient-to-bl tw-from-indigo-100/30 tw-to-transparent tw-rounded-full tw--translate-y-12 tw-translate-x-12"></div>
            <div class="tw-absolute tw-bottom-0 tw-left-0 tw-w-16 tw-h-16 tw-bg-gradient-to-tr tw-from-purple-100/30 tw-to-transparent tw-rounded-full tw-translate-y-8 tw--translate-x-8"></div>

            <div class="tw-relative tw-z-10">
              <div class="tw-bg-gradient-to-br tw-from-indigo-100 tw-to-purple-100 tw-p-6 tw-rounded-3xl tw-inline-block tw-mb-6 tw-shadow-xl">
                <i class="pi pi-layer-plus tw-text-4xl tw-text-indigo-600"></i>
              </div>
              <p class="tw-text-gray-700 tw-text-xl tw-font-bold tw-mb-3">Ready to Create Batches</p>
              <p class="tw-text-gray-500 tw-text-base tw-mb-6 tw-max-w-md tw-mx-auto">Add allocation rows to split this product into multiple batches with different pricing and expiry dates.</p>
              <Button
                label="Add First Batch"
                icon="pi pi-plus"
                class="p-button-primary p-button-lg tw-rounded-2xl tw-px-8 tw-py-4 tw-font-bold tw-text-lg tw-shadow-xl hover:tw-shadow-2xl tw-transition-all tw-duration-300"
                @click="addPendingSubItemRow"
                :disabled="isPendingSectionDisabled"
              />
            </div>
          </div>

          <div class="tw-flex tw-flex-col md:tw-flex-row tw-items-start md:tw-items-center tw-justify-between tw-gap-6 tw-pt-6 tw-border-t tw-border-indigo-100">
            <div class="tw-bg-gradient-to-r tw-from-indigo-50 tw-to-blue-50 tw-rounded-2xl tw-p-6 tw-flex-1 tw-shadow-lg">
              <div class="tw-grid tw-grid-cols-2 tw-gap-6 tw-text-center">
                <div class="tw-bg-white tw-rounded-xl tw-p-4 tw-shadow-sm tw-border tw-border-indigo-100">
                  <div class="tw-text-sm tw-font-bold tw-text-gray-600 tw-uppercase tw-tracking-wider tw-mb-2">Pending Qty</div>
                  <div class="tw-text-2xl tw-font-bold tw-text-indigo-700">{{ pendingSubQuantityTotal }}</div>
                  <div class="tw-text-xs tw-text-gray-500 tw-mt-1">units to allocate</div>
                </div>
                <div class="tw-bg-white tw-rounded-xl tw-p-4 tw-shadow-sm tw-border tw-border-emerald-100">
                  <div class="tw-text-sm tw-font-bold tw-text-gray-600 tw-uppercase tw-tracking-wider tw-mb-2">Remaining</div>
                  <div :class="remainingAfterPendingAllocation > 0 ? 'tw-text-2xl tw-font-bold tw-text-emerald-600' : 'tw-text-2xl tw-font-bold tw-text-red-500'">
                    {{ remainingAfterPendingAllocation }}
                  </div>
                  <div class="tw-text-xs tw-text-gray-500 tw-mt-1">units left</div>
                </div>
              </div>
            </div>
            <div class="tw-flex tw-flex-col tw-gap-3">
              <Button
                label="Apply All Batches"
                icon="pi pi-check"
                class="p-button-success tw-font-bold tw-px-8 tw-py-4 tw-rounded-2xl tw-shadow-xl hover:tw-shadow-2xl tw-transition-all tw-duration-300 tw-text-lg"
                @click="applyPendingSubItems"
                :disabled="isPendingSectionDisabled || !isPendingAllocationValid"
              />
              <div v-if="isPendingSectionDisabled" class="tw-text-sm tw-text-orange-500 tw-font-medium tw-text-center tw-bg-orange-50 tw-px-4 tw-py-2 tw-rounded-lg">
                <i class="pi pi-info-circle tw-mr-2"></i>
                {{ editingSubItemIndex !== null ? 'Finish editing the selected sub batch before adding new allocations.' : 'All available quantity has already been assigned to batches.' }}
              </div>
              <div v-else-if="pendingSubQuantityTotal > currentSubRemainingQuantity" class="tw-text-sm tw-text-red-500 tw-font-medium tw-text-center tw-bg-red-50 tw-px-4 tw-py-2 tw-rounded-lg">
                <i class="pi pi-exclamation-triangle tw-mr-2"></i>
                Pending quantity exceeds the available remaining amount.
              </div>
            </div>
          </div>
        </div>

        <Transition name="fade">
          <div v-if="editingSubItemIndex !== null" class="tw-bg-purple-50 tw-border tw-border-purple-200 tw-rounded-xl tw-shadow-sm tw-p-5 tw-space-y-4">
            <div class="tw-flex tw-items-center tw-justify-between">
              <div>
                <div class="tw-text-sm tw-font-semibold tw-text-purple-700">Editing Sub Batch</div>
                <div class="tw-text-xs tw-text-gray-500">Adjust quantity, pricing, or batch metadata, then save changes.</div>
              </div>
              <Tag value="Editing" severity="warning" />
            </div>

            <div class="tw-grid md:tw-grid-cols-2 tw-gap-4">
              <div class="tw-space-y-3">
                <label class="tw-flex tw-items-center tw-gap-2 tw-text-xs tw-font-bold tw-text-gray-700 tw-uppercase tw-tracking-wider">
                  <i class="pi pi-hashtag tw-text-indigo-500 tw-text-sm"></i>
                  Quantity
                </label>
                <div class="tw-relative">
                  <InputNumber
                    v-model="subItemDraft.quantity"
                    :min="1"
                    :max="currentSubRemainingQuantity"
                    showButtons
                    class="tw-w-full high-density-input tw-rounded-xl tw-border-2 tw-border-gray-200 hover:tw-border-indigo-300 focus:tw-border-indigo-500 focus:tw-ring-4 focus:tw-ring-indigo-100 tw-transition-all tw-duration-200 tw-shadow-sm hover:tw-shadow-md"
                    inputClass="tw-text-center tw-font-semibold tw-text-gray-800"
                    :class="{ 'tw-border-red-300 focus:tw-border-red-500 focus:tw-ring-red-100': subItemDraft.quantity > currentSubRemainingQuantity }"
                  />
                  <div v-if="subItemDraft.quantity > currentSubRemainingQuantity" class="tw-absolute tw-right-2 tw-top-1/2 tw--translate-y-1/2">
                    <i class="pi pi-exclamation-triangle tw-text-red-500 tw-text-sm"></i>
                  </div>
                </div>
              </div>
              <div class="tw-space-y-3">
                <label class="tw-flex tw-items-center tw-gap-2 tw-text-xs tw-font-bold tw-text-gray-700 tw-uppercase tw-tracking-wider">
                  <i class="pi pi-dollar tw-text-green-500 tw-text-sm"></i>
                  Purchase Price
                </label>
                <div class="tw-relative">
                  <InputNumber
                    v-model="subItemDraft.purchase_price"
                    :min="0"
                    mode="currency"
                    currency="DZD"
                    locale="fr-FR"
                    class="tw-w-full high-density-input tw-rounded-xl tw-border-2 tw-border-gray-200 hover:tw-border-green-300 focus:tw-border-green-500 focus:tw-ring-4 focus:tw-ring-green-100 tw-transition-all tw-duration-200 tw-shadow-sm hover:tw-shadow-md"
                    inputClass="tw-font-semibold tw-text-green-700"
                    :class="{ 'tw-border-red-300 focus:tw-border-red-500 focus:tw-ring-red-100': subItemDraft.purchase_price < 0 }"
                  />
                  <div v-if="subItemDraft.purchase_price < 0" class="tw-absolute tw-right-2 tw-top-1/2 tw--translate-y-1/2">
                    <i class="pi pi-exclamation-triangle tw-text-red-500 tw-text-sm"></i>
                  </div>
                </div>
              </div>
              <div class="tw-space-y-3">
                <label class="tw-flex tw-items-center tw-gap-2 tw-text-xs tw-font-bold tw-text-gray-700 tw-uppercase tw-tracking-wider">
                  <i class="pi pi-box tw-text-blue-500 tw-text-sm"></i>
                  Unit
                </label>
                <Dropdown
                  v-model="subItemDraft.unit"
                  :options="[
                    { label: 'Unit', value: 'unit', icon: 'pi pi-minus' },
                    { label: 'Box', value: 'box', icon: 'pi pi-box' }
                  ]"
                  optionLabel="label"
                  optionValue="value"
                  class="tw-w-full high-density-input tw-rounded-xl tw-border-2 tw-border-gray-200 hover:tw-border-blue-300 focus:tw-border-blue-500 focus:tw-ring-4 focus:tw-ring-blue-100 tw-transition-all tw-duration-200 tw-shadow-sm hover:tw-shadow-md"
                  panelClass="tw-rounded-xl tw-border-2 tw-border-blue-200 tw-shadow-xl"
                >
                  <template #option="slotProps">
                    <div class="tw-flex tw-items-center tw-gap-2 tw-p-2">
                      <i :class="slotProps.option.icon" class="tw-text-gray-600"></i>
                      <span>{{ slotProps.option.label }}</span>
                    </div>
                  </template>
                  <template #value="slotProps">
                    <div v-if="slotProps.value" class="tw-flex tw-items-center tw-gap-2">
                      <i :class="slotProps.value === 'unit' ? 'pi pi-minus' : 'pi pi-box'" class="tw-text-gray-600"></i>
                      <span>{{ slotProps.value === 'unit' ? 'Unit' : 'Box' }}</span>
                    </div>
                    <span v-else class="tw-text-gray-400">Select unit</span>
                  </template>
                </Dropdown>
              </div>
              <div class="tw-space-y-3">
                <label class="tw-flex tw-items-center tw-gap-2 tw-text-xs tw-font-bold tw-text-gray-700 tw-uppercase tw-tracking-wider">
                  <i class="pi pi-tag tw-text-purple-500 tw-text-sm"></i>
                  Batch Number
                </label>
                <div class="tw-relative">
                  <InputText
                    v-model="subItemDraft.batch_number"
                    placeholder="Enter batch number"
                    class="tw-w-full high-density-input tw-rounded-xl tw-border-2 tw-border-gray-200 hover:tw-border-purple-300 focus:tw-border-purple-500 focus:tw-ring-4 focus:tw-ring-purple-100 tw-transition-all tw-duration-200 tw-shadow-sm hover:tw-shadow-md tw-pl-4"
                  />
                  <div class="tw-absolute tw-left-3 tw-top-1/2 tw--translate-y-1/2">
                    <i class="pi pi-tag tw-text-purple-400 tw-text-sm"></i>
                  </div>
                </div>
              </div>
              <div class="md:tw-col-span-2 tw-space-y-3">
                <label class="tw-flex tw-items-center tw-gap-2 tw-text-xs tw-font-bold tw-text-gray-700 tw-uppercase tw-tracking-wider">
                  <i class="pi pi-calendar tw-text-orange-500 tw-text-sm"></i>
                  Expiry Date
                </label>
                <div class="tw-relative">
                  <Calendar
                    v-model="subItemDraft.expiry_date"
                    dateFormat="yy-mm-dd"
                    placeholder="Select expiry date"
                    class="tw-w-full high-density-input tw-rounded-xl tw-border-2 tw-border-gray-200 hover:tw-border-orange-300 focus:tw-border-orange-500 focus:tw-ring-4 focus:tw-ring-orange-100 tw-transition-all tw-duration-200 tw-shadow-sm hover:tw-shadow-md"
                    showIcon
                    iconClass="tw-text-orange-500"
                    :class="{ 'tw-border-red-300 focus:tw-border-red-500 focus:tw-ring-red-100': subItemDraft.expiry_date && new Date(subItemDraft.expiry_date) < new Date() }"
                  />
                  <div v-if="subItemDraft.expiry_date && new Date(subItemDraft.expiry_date) < new Date()" class="tw-absolute tw-right-10 tw-top-1/2 tw--translate-y-1/2">
                    <i class="pi pi-exclamation-triangle tw-text-red-500 tw-text-sm"></i>
                  </div>
                </div>
              </div>
            </div>

            <div class="tw-flex tw-justify-end tw-gap-2">
              <Button label="Cancel" icon="pi pi-times" class="p-button-text" @click="cancelSubItemEdit" />
              <Button label="Save Changes" icon="pi pi-check" class="p-button-primary" @click="saveEditedSubItem" />
            </div>
          </div>
        </Transition>
      </div>

      <template #footer>
        <Button label="Close" icon="pi pi-times" class="p-button-text" @click="closeSubItemModal" />
      </template>
    </Dialog>

    <!-- Confirm Dialog -->
    <ConfirmDialog></ConfirmDialog>

    <!-- Validation Modal with Storage Selection -->
    <Dialog
      :visible="showValidationModal"
      :header="`Validate Bon Entree - ${bonEntreeData?.bon_entree_code || ''}`"
      :modal="true"
      :closable="true"
      :style="{ width: '90vw', maxWidth: '600px' }"
      class="validation-modal"
      @update:visible="val => (showValidationModal = val)"
      @hide="closeValidationModal"
    >
      <div class="tw-space-y-6">
        <!-- Service Information -->
        <div v-if="form.service_id" class="tw-bg-blue-50 tw-border tw-border-blue-200 tw-rounded-lg tw-p-4">
          <div class="tw-flex tw-items-center tw-gap-3 tw-mb-2">
            <i class="pi pi-building tw-text-blue-600 tw-text-xl"></i>
            <h3 class="tw-font-semibold tw-text-blue-900">Service Information</h3>
          </div>
          <p class="tw-text-sm tw-text-gray-700">
            <span class="tw-font-medium">Service:</span> {{ validationModalData.serviceInfo?.name || form.service_id }}
          </p>
        </div>

        <!-- Storage Selection -->
        <div v-if="validationModalData.storageOptions.length > 0" class="tw-space-y-3">
          <div class="tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-home tw-text-indigo-600 tw-text-lg"></i>
            <label class="tw-font-semibold tw-text-gray-800">Select Storage Location</label>
          </div>
          <div class="tw-grid tw-grid-cols-1 tw-gap-3">
            <div
              v-for="storage in validationModalData.storageOptions"
              :key="storage.id"
              class="tw-p-4 tw-border-2 tw-rounded-lg tw-cursor-pointer tw-transition-all tw-duration-200"
              :class="validationModalData.selectedStorage?.id === storage.id 
                ? 'tw-border-indigo-600 tw-bg-indigo-50' 
                : 'tw-border-gray-200 tw-bg-white hover:tw-border-indigo-300 hover:tw-bg-indigo-50/30'"
              @click="validationModalData.selectedStorage = storage"
            >
              <div class="tw-flex tw-items-start tw-gap-3">
                <div
                  class="tw-w-6 tw-h-6 tw-border-2 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mt-0.5 tw-flex-shrink-0"
                  :class="validationModalData.selectedStorage?.id === storage.id 
                    ? 'tw-border-indigo-600 tw-bg-indigo-600' 
                    : 'tw-border-gray-300'"
                >
                  <i v-if="validationModalData.selectedStorage?.id === storage.id" class="pi pi-check tw-text-white tw-text-xs"></i>
                </div>
                <div class="tw-flex-1 tw-min-w-0">
                  <p class="tw-font-semibold tw-text-gray-800">{{ storage.name }}</p>
                  <p class="tw-text-sm tw-text-gray-600 tw-mt-1">{{ storage.description || storage.location || 'No description' }}</p>
                  <div v-if="storage.capacity" class="tw-text-xs tw-text-gray-500 tw-mt-2">
                    Capacity: <span class="tw-font-medium">{{ storage.capacity }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- No Storage Message -->
        <div v-else class="tw-bg-amber-50 tw-border tw-border-amber-200 tw-rounded-lg tw-p-4">
          <div class="tw-flex tw-items-start tw-gap-3">
            <i class="pi pi-info-circle tw-text-amber-600 tw-text-xl tw-flex-shrink-0 tw-mt-0.5"></i>
            <div>
              <p class="tw-font-medium tw-text-amber-900">No storages configured</p>
              <p class="tw-text-sm tw-text-amber-800 tw-mt-1">This service has no storage locations configured. The bon entree will be validated without storage assignment.</p>
            </div>
          </div>
        </div>

        <!-- Summary Section -->
        <div class="tw-bg-gradient-to-r tw-from-slate-50 tw-to-gray-50 tw-border tw-border-gray-200 tw-rounded-lg tw-p-4 tw-space-y-2">
          <div class="tw-text-sm tw-font-semibold tw-text-gray-700 tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-list tw-text-indigo-600"></i>
            Validation Summary
          </div>
          <div class="tw-grid tw-grid-cols-2 tw-gap-3 tw-text-sm tw-mt-3">
            <div class="tw-bg-white tw-p-2 tw-rounded">
              <p class="tw-text-gray-600">Items</p>
              <p class="tw-text-lg tw-font-bold tw-text-indigo-600">{{ form.items.length }}</p>
            </div>
            <div class="tw-bg-white tw-p-2 tw-rounded">
              <p class="tw-text-gray-600">Total Qty</p>
              <p class="tw-text-lg tw-font-bold tw-text-green-600">{{ calculateTotalQuantity() }}</p>
            </div>
            <div class="tw-bg-white tw-p-2 tw-rounded tw-col-span-2">
              <p class="tw-text-gray-600">Selected Storage</p>
              <p class="tw-font-medium tw-text-gray-800">{{ validationModalData.selectedStorage?.name || 'No storage selected' }}</p>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="tw-flex tw-gap-3 tw-justify-end">
          <Button 
            label="Cancel" 
            icon="pi pi-times" 
            class="p-button-text" 
            @click="closeValidationModal" 
          />
          <Button
            label="Validate"
            icon="pi pi-check-circle"
            class="p-button-success"
            :loading="validationModalData.loadingStorages"
            @click="confirmValidation"
          />
        </div>
      </template>
    </Dialog>

    <!-- Toast -->
    <Toast position="top-right" />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import axios from 'axios'

// PrimeVue Components
import Button from 'primevue/button'
import Dropdown from 'primevue/dropdown'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Calendar from 'primevue/calendar'
import Tag from 'primevue/tag'
import ProgressSpinner from 'primevue/progressspinner'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dialog from 'primevue/dialog'
import Avatar from 'primevue/avatar'
import Breadcrumb from 'primevue/breadcrumb'
import Divider from 'primevue/divider'
import Steps from 'primevue/steps'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import Badge from 'primevue/badge'
import ProgressBar from 'primevue/progressbar'
import Timeline from 'primevue/timeline'
import ConfirmDialog from 'primevue/confirmdialog'
import Toast from 'primevue/toast'

// Custom Components
import AttachmentUploader from '@/Components/AttachmentUploader.vue'
import ProductSelectorWithInfiniteScroll from '@/Components/Apps/Purchasing/Shared/ProductSelectorWithInfiniteScroll.vue'
import ProductPricingInfoPanel from '@/Components/Inventory/ProductPricingInfoPanel.vue'

// Services
import productPricingService from '@/services/Inventory/productPricingService'

const router = useRouter()
const route = useRoute()
const toast = useToast()
const confirm = useConfirm()

// Props from route
const bonEntreeId = route.params.id

// State
const loading = ref(true)
const saving = ref(false)
const savingDraft = ref(false)
const loadingServices = ref(false)
const loadingProgress = ref(0)
const activeStep = ref(0)

const bonEntreeData = ref(null)
const services = ref([])
const errors = ref({})
const isPharmacyOrder = ref(false) // Track if this is a pharmacy order

const form = reactive({
  service_id: null,
  fournisseur_id: null,
  notes: '',
  items: []
})

// View and search
const viewMode = ref('table')
const itemSearchQuery = ref('')
const selectedProducts = ref([])
const currentProductPricingInfo = ref(null)
const loadingPricingInfo = ref(false)

// Modals
const showProductModal = ref(false)
const showItemDetailsModal = ref(false)
const showSubItemModal = ref(false)
const showValidationModal = ref(false)
const editingItem = ref(null)
const editingSubItemIndex = ref(null)
const productSelectorRef = ref(null)
const subItemContextIndex = ref(null)
const subItemDraft = reactive({
  id: null,
  quantity: 1,
  purchase_price: 0,
  unit: 'unit',
  batch_number: '',
  expiry_date: null
})

// Validation modal data
const validationModalData = reactive({
  selectedStorage: null,
  storageOptions: [],
  serviceInfo: null,
  loadingStorages: false
})

// Default settings
const defaultItemSettings = reactive({
  quantity: 1,
  tva: 0,
  storage_name: '',
  batch_number: '',
  purchasingPrice: 0,
  unit: 'unit' // Add unit to default settings
})

// Steps
const stepsItems = [
  { label: 'Basic Info', icon: 'pi pi-info-circle' },
  { label: 'Products', icon: 'pi pi-box' },
  { label: 'Attachments', icon: 'pi pi-paperclip' },
  { label: 'Review', icon: 'pi pi-check' }
]

// History events (mock data - replace with API call)
const historyEvents = computed(() => [
  {
    type: 'created',
    title: 'Bon Entree Created',
    description: 'Initial creation from Bon Reception',
    date: bonEntreeData.value?.created_at,
    user: 'System',
    icon: 'pi pi-plus'
  },
  {
    type: 'updated',
    title: 'Last Updated',
    description: 'Form data updated',
    date: bonEntreeData.value?.updated_at,
    user: 'Current User',
    icon: 'pi pi-pencil'
  }
])

// Breadcrumb
const breadcrumbItems = computed(() => [
  { label: 'Dashboard', to: '/' },
  { label: 'Bon Entrees', to: '/bon-entrees' },
  { label: bonEntreeData.value?.bon_entree_code || 'Loading...', disabled: true }
])

// Computed
const filteredItems = computed(() => {
  if (!itemSearchQuery.value) return form.items

  const query = itemSearchQuery.value.toLowerCase()
  return form.items.filter(item => {
    const product = getProductById(item.product_id)
    return product?.name?.toLowerCase().includes(query) || 
           item.batch_number?.toLowerCase().includes(query)
  })
})

const getSubItems = (item) => Array.isArray(item?.sub_items) ? item.sub_items : []

const normalizeSubItem = (sub) => ({
  ...sub,
  expiry_date: sub?.expiry_date ? new Date(sub.expiry_date) : null
})

const getSubQuantityTotal = (item) => getSubItems(item).reduce((total, sub) => total + (Number(sub.quantity) || 0), 0)

const calculateSubBaseTotal = (item) => getSubItems(item).reduce((total, sub) => {
  const qty = Number(sub.quantity) || 0
  const price = Number(sub.purchase_price) || 0
  return total + (qty * price)
}, 0)

const calculateItemBaseTotal = (item) => {
  const mainBase = (Number(item.quantity) || 0) * (Number(item.purchase_price) || 0)
  return mainBase + calculateSubBaseTotal(item)
}

const getTotalQuantityForItem = (item) => (Number(item.quantity) || 0) + getSubQuantityTotal(item)

const currentSubItemParent = computed(() => {
  if (subItemContextIndex.value === null) return null
  return form.items[subItemContextIndex.value]
})

const currentSubRemainingQuantity = computed(() => {
  const parent = currentSubItemParent.value
  if (!parent) return 0
  const parentQty = Number(parent.quantity) || 0
  if (editingSubItemIndex.value === null) return parentQty
  const editingSub = getSubItems(parent)[editingSubItemIndex.value]
  return parentQty + (Number(editingSub?.quantity) || 0)
})

const pendingSubItems = ref([])

const resolveSubItemDate = (value) => (value instanceof Date ? value : value ? new Date(value) : null)

const buildSubItemDefaults = (overrides = {}) => {
  const parent = currentSubItemParent.value
  const available = currentSubRemainingQuantity.value
  const defaultQuantity = available > 0 ? 0 : 0

  return {
    quantity: overrides.quantity ?? defaultQuantity,
  purchase_price: overrides.purchase_price ?? 23,
  unit: overrides.unit ?? (parent?.unit || 'unit'),
    batch_number: overrides.batch_number ?? '',
    expiry_date: resolveSubItemDate(overrides.expiry_date)
  }
}

const createPendingSubItem = (overrides = {}) => reactive({
  tempId: `${Date.now()}-${Math.random()}`,
  ...buildSubItemDefaults(overrides)
})

const resetSubItemDraft = () => {
  const defaults = buildSubItemDefaults()
  subItemDraft.id = null
  subItemDraft.quantity = defaults.quantity
  subItemDraft.purchase_price = defaults.purchase_price
  subItemDraft.unit = defaults.unit
  subItemDraft.batch_number = defaults.batch_number
  subItemDraft.expiry_date = defaults.expiry_date
}

const resetPendingSubItems = () => {
  if (!currentSubItemParent.value) {
    pendingSubItems.value = []
    return
  }
  pendingSubItems.value = [createPendingSubItem()]
}

const addPendingSubItemRow = () => {
  if (isPendingSectionDisabled.value) {
    toast.add({
      severity: 'warn',
      summary: 'No quantity available',
      detail: 'All available quantity is already allocated',
      life: 2500
    })
    return
  }
  pendingSubItems.value.push(createPendingSubItem())
}

const removePendingSubItemRow = (index) => {
  if (pendingSubItems.value.length <= 1) {
    pendingSubItems.value.splice(0, 1, createPendingSubItem())
    return
  }
  pendingSubItems.value.splice(index, 1)
}

const pendingSubQuantityTotal = computed(() =>
  pendingSubItems.value.reduce((total, sub) => total + (Number(sub.quantity) || 0), 0)
)

const remainingAfterPendingAllocation = computed(() => {
  const remaining = currentSubRemainingQuantity.value - pendingSubQuantityTotal.value
  return remaining > 0 ? remaining : 0
})

const isPendingAllocationValid = computed(() => {
  if (pendingSubItems.value.length === 0) return false
  if (pendingSubQuantityTotal.value <= 0) return false
  if (pendingSubQuantityTotal.value > currentSubRemainingQuantity.value) return false

  return pendingSubItems.value.every(sub => {
    const quantity = Number(sub.quantity) || 0
    const price = Number(sub.purchase_price)
    return quantity > 0 && !Number.isNaN(price) && price >= 0
  })
})

const isPendingSectionDisabled = computed(() => {
  if (!currentSubItemParent.value) return true
  if (editingSubItemIndex.value !== null) return true
  return currentSubRemainingQuantity.value <= 0
})

const applyPendingSubItems = () => {
  const parent = currentSubItemParent.value
  if (!parent) return

  if (isPendingSectionDisabled.value) {
    toast.add({
      severity: 'warn',
      summary: 'No quantity available',
      detail: 'All available quantity is already allocated',
      life: 2500
    })
    return
  }

  if (!isPendingAllocationValid.value) {
    toast.add({
      severity: 'warn',
      summary: 'Invalid allocation',
      detail: 'Please review the batch quantities and prices before saving',
      life: 3000
    })
    return
  }

  if (!Array.isArray(parent.sub_items)) parent.sub_items = []

  let available = currentSubRemainingQuantity.value
  let appliedCount = 0
  let allocatedQuantity = 0

  pendingSubItems.value.forEach(sub => {
    const quantity = Number(sub.quantity) || 0
    if (quantity <= 0 || quantity > available) return

    const payload = {
      id: Date.now() + Math.random(),
      quantity,
      purchase_price: Number(sub.purchase_price) || 0,
      unit: sub.unit || parent.unit || 'unit',
      batch_number: sub.batch_number || '',
      expiry_date: resolveSubItemDate(sub.expiry_date)
    }

    parent.sub_items.push(payload)
    available -= quantity
    appliedCount += 1
    allocatedQuantity += quantity
  })

  parent.quantity = available

  if (appliedCount === 0) {
    toast.add({
      severity: 'warn',
      summary: 'Nothing saved',
      detail: 'No batches were added. Verify quantities and try again.',
      life: 2500
    })
    return
  }

  toast.add({
    severity: 'success',
    summary: 'Batches allocated',
    detail: `Allocated ${allocatedQuantity} unit(s) across ${appliedCount} batch(es)`,
    life: 3000
  })

  resetPendingSubItems()
}

const openSubItemManager = (itemIndex) => {
  subItemContextIndex.value = itemIndex
  editingSubItemIndex.value = null
  const parent = currentSubItemParent.value
  if (parent && !Array.isArray(parent.sub_items)) {
    parent.sub_items = []
  }
  resetSubItemDraft()
  resetPendingSubItems()
  showSubItemModal.value = true
}

const closeSubItemModal = () => {
  showSubItemModal.value = false
  subItemContextIndex.value = null
  cancelSubItemEdit()
  pendingSubItems.value = []
}

const saveEditedSubItem = () => {
  const parent = currentSubItemParent.value
  if (!parent || editingSubItemIndex.value === null) return

  const quantity = Number(subItemDraft.quantity) || 0
  if (quantity <= 0) {
    toast.add({
      severity: 'warn',
      summary: 'Invalid quantity',
      detail: 'Quantity must be greater than zero',
      life: 2500
    })
    return
  }

  const available = currentSubRemainingQuantity.value
  if (quantity > available) {
    toast.add({
      severity: 'warn',
      summary: 'Not enough quantity',
      detail: `Only ${available} unit(s) available for allocation`,
      life: 3000
    })
    return
  }

  const payload = {
    id: subItemDraft.id ?? Date.now(),
    quantity,
    purchase_price: Number(subItemDraft.purchase_price) || 0,
    unit: subItemDraft.unit || parent.unit || 'unit',
    batch_number: subItemDraft.batch_number || '',
    expiry_date: resolveSubItemDate(subItemDraft.expiry_date)
  }

  parent.sub_items.splice(editingSubItemIndex.value, 1, payload)
  parent.quantity = available - quantity

  toast.add({
    severity: 'success',
    summary: 'Batch updated',
    detail: `Updated allocation to ${quantity} unit(s)`,
    life: 2500
  })

  cancelSubItemEdit()
}

const editSubItem = (sub, subIndex) => {
  const parent = currentSubItemParent.value
  if (!parent) return
  editingSubItemIndex.value = subIndex
  subItemDraft.id = sub.id ?? Date.now()
  subItemDraft.quantity = Number(sub.quantity) || 1
  subItemDraft.purchase_price = Number(sub.purchase_price) || Number(parent.purchase_price) || 0
  subItemDraft.unit = sub.unit || parent.unit || 'unit'
  subItemDraft.batch_number = sub.batch_number || ''
  subItemDraft.expiry_date = resolveSubItemDate(sub.expiry_date)
}

const removeSubItem = (subIndex) => {
  const parent = currentSubItemParent.value
  if (!parent) return
  const subItems = getSubItems(parent)
  const removed = subItems.splice(subIndex, 1)[0]
  parent.quantity = (Number(parent.quantity) || 0) + (Number(removed?.quantity) || 0)

  toast.add({
    severity: 'info',
    summary: 'Batch removed',
    detail: 'Sub batch has been removed and quantity returned to main item',
    life: 2000
  })

  if (editingSubItemIndex.value === subIndex) {
    cancelSubItemEdit()
  } else if (editingSubItemIndex.value !== null && subIndex < editingSubItemIndex.value) {
    editingSubItemIndex.value -= 1
  } else if (subItems.length === 0) {
    cancelSubItemEdit()
  }
}

const cancelSubItemEdit = () => {
  editingSubItemIndex.value = null
  resetSubItemDraft()
}

// Methods continue in next part...
// ... continuing methods
const fetchBonEntree = async () => {
  try {
    loading.value = true
    loadingProgress.value = 30

    const response = await axios.get(`/api/purchasing/bon-entrees/${bonEntreeId}`)
    loadingProgress.value = 60

    if (response.data.status === 'success') {
      bonEntreeData.value = response.data.data
      
      // Set pharmacy order flag from backend
      isPharmacyOrder.value = response.data.data.is_pharmacy_order || false

      // Populate form data
      form.service_id = bonEntreeData.value.service_id
      form.fournisseur_id = bonEntreeData.value.fournisseur_id
      form.notes = bonEntreeData.value.notes || ''
      form.items = bonEntreeData.value.items.map(item => ({
        id: item.id,
        product_id: item.product_id,
        pharmacy_product_id: item.pharmacy_product_id, // Preserve pharmacy_product_id
        product: item.product || item.pharmacy_product || {
          id: item.product_id || item.pharmacy_product_id,
          name: item.product_name || item.product?.name || item.pharmacy_product?.name || 'Unknown Product',
          product_code: item.product_code || item.product?.product_code || item.pharmacy_product?.product_code || '',
          category: item.category || item.product?.category || item.pharmacy_product?.category || '',
          unit: item.unit || item.product?.unit || item.pharmacy_product?.unit || 'unit'
        },
        product_name: item.product_name || item.product?.name || item.pharmacy_product?.name || 'Unknown Product',
        product_code: item.product_code || item.product?.product_code || item.pharmacy_product?.product_code || '',
        quantity: item.quantity,
        purchase_price: item.purchase_price || 0,
        sell_price: item.sell_price || 0,
        tva: item.tva || 0,
        unit: item.unit || item.product?.unit || item.pharmacy_product?.unit || 'unit',
        batch_number: item.batch_number || '',
        serial_number: item.serial_number || '',
        expiry_date: item.expiry_date ? new Date(item.expiry_date) : null,
        storage_name: item.storage_name || '',
        remarks: item.remarks || '',
        sub_items: Array.isArray(item.sub_items) ? item.sub_items.map(normalizeSubItem) : []
      }))

      loadingProgress.value = 100
      activeStep.value = form.items.length > 0 ? 1 : 0
    }
  } catch (err) {
    console.error('Error fetching bon entree:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load bon entree data',
      life: 3000
    })
    router.back()
  } finally {
    loading.value = false
  }
}

const fetchServices = async () => {
  try {
    loadingServices.value = true
    const response = await axios.get('/api/services')
    services.value = response.data.data || response.data
  } catch (err) {
    console.error('Error fetching services:', err)
  } finally {
    loadingServices.value = false
  }
}

const addSelectedProducts = () => {
  if (selectedProducts.value.length === 0) return

  selectedProducts.value.forEach(product => {
    // Apply default settings from the ProductSelectorWithInfiniteScroll component
    // These are set by the user in the Default Settings section of the modal
    const quantity = product.quantity !== undefined ? product.quantity : defaultItemSettings.quantity
    const purchasingPrice = product.price !== undefined ? product.price : defaultItemSettings.purchasingPrice
    const unit = product.purchaseUnit || defaultItemSettings.unit || 'unit'
    const tva = product.tva || defaultItemSettings.tva || 0

    // Debug logging
    console.log('➕ Adding product:', {
      name: product.name,
      productQuantity: product.quantity,
      productPrice: product.price,
      productUnit: product.purchaseUnit,
      finalQuantity: quantity,
      finalPrice: purchasingPrice,
      finalUnit: unit
    })

    const newItem = {
      // Use pharmacy_product_id for pharmacy orders, product_id for regular orders
      ...(isPharmacyOrder.value 
        ? { pharmacy_product_id: product.id, product_id: null }
        : { product_id: product.id, pharmacy_product_id: null }
      ),
      product_name: product.name,
      quantity: quantity,
      purchase_price: purchasingPrice,
      sell_price: product.sell_price || 0,
      tva: tva,
      unit: unit, // Store the unit type (unit/box)
      batch_number: product.batch_number || defaultItemSettings.batch_number || '',
      serial_number: '',
      expiry_date: null,
      storage_name: product.storage_name || defaultItemSettings.storage_name || '',
      remarks: '',
      sub_items: [],
      // Store complete product data for display purposes
      product: {
        id: product.id,
        name: product.name,
        product_code: product.product_code || product.code || '',
        category: product.category || product.category_name || ''
      }
    }

    form.items.push(newItem)
  })

  toast.add({
    severity: 'success',
    summary: 'Success',
    detail: `${selectedProducts.value.length} product(s) added`,
    life: 3000
  })

  showProductModal.value = false
  // Clear selection after adding
  selectedProducts.value = []
  productSelectorRef.value?.clearSelection?.()
}

// Handle product selection changes from the ProductSelectorWithInfiniteScroll
const handleProductSelectionChange = async (selected) => {
  // Always seed incoming selections with the current defaults
  selectedProducts.value = selected.map(product => ({
    ...product,
    quantity: defaultItemSettings.quantity,
    price: defaultItemSettings.purchasingPrice,
    purchaseUnit: defaultItemSettings.unit,
    product: {
      id: product.id,
      name: product.name,
      product_code: product.product_code || product.code || '',
      category: product.category || product.category_name || '',
      unit: product.unit || 'unit'
    },
    sub_items: []
  }))
  
  // Load pricing info for first selected product
  if (selected.length > 0) {
    await loadProductPricingInfo(selected[0])
  } else {
    currentProductPricingInfo.value = null
  }
}

// Load product pricing information
const loadProductPricingInfo = async (product) => {
  if (!product || !product.id) {
    currentProductPricingInfo.value = null
    return
  }
  
  loadingPricingInfo.value = true
  try {
    const isPharmacy = product.sku || product.is_clinical !== undefined
    const response = await productPricingService.getProductPricingInfo(product.id, isPharmacy)
    
    if (response.success) {
      currentProductPricingInfo.value = response.data
    } else {
      currentProductPricingInfo.value = null
    }
  } catch (error) {
    console.error('Failed to load pricing info:', error)
    currentProductPricingInfo.value = null
  } finally {
    loadingPricingInfo.value = false
  }
}

// Handle defaults changes from the ProductSelectorWithInfiniteScroll
const handleDefaultsChange = (defaults) => {
  defaultItemSettings.quantity = defaults.quantity || 1
  defaultItemSettings.purchasingPrice = defaults.price || 0
  defaultItemSettings.unit = defaults.unit || 'unit'
}

// Re-seed any already-selected products whenever defaults change
watch(
  () => [
    defaultItemSettings.quantity,
    defaultItemSettings.purchasingPrice,
    defaultItemSettings.unit
  ],
  ([quantity, price, unit]) => {
    if (selectedProducts.value.length === 0) return

    selectedProducts.value = selectedProducts.value.map(product => ({
      ...product,
      quantity,
      price,
      purchaseUnit: unit
    }))
  }
)

const editItemDetails = (item) => {
  editingItem.value = {
    ...item,
    sub_items: getSubItems(item).map(sub => ({ ...sub }))
  }
  showItemDetailsModal.value = true
}

const saveItemDetails = () => {
  const index = form.items.findIndex(item => 
    (item.id === editingItem.value.id) || 
    (item.product_id === editingItem.value.product_id && !item.id && !editingItem.value.id)
  )

  if (index !== -1) {
    form.items[index] = {
      ...form.items[index],
      ...editingItem.value,
      sub_items: getSubItems(editingItem.value).map(normalizeSubItem)
    }
  }

  showItemDetailsModal.value = false
  editingItem.value = null
}

const duplicateItem = (item) => {
  const newItem = {
    ...item,
    expiry_date: item.expiry_date ? (item.expiry_date instanceof Date ? new Date(item.expiry_date) : new Date(item.expiry_date)) : null,
    sub_items: getSubItems(item).map(sub => ({
      ...sub,
      id: Date.now() + Math.random(),
      expiry_date: sub.expiry_date ? (sub.expiry_date instanceof Date ? new Date(sub.expiry_date) : new Date(sub.expiry_date)) : null
    }))
  }
  if (newItem.product) {
    newItem.product = { ...newItem.product }
  }
  delete newItem.id
  form.items.push(newItem)

  toast.add({
    severity: 'success',
    summary: 'Duplicated',
    detail: 'Product duplicated successfully',
    life: 2000
  })
}

const removeItem = (index) => {
  confirm.require({
    message: 'Are you sure you want to remove this product?',
    header: 'Confirm Removal',
    icon: 'pi pi-exclamation-triangle',
    accept: () => {
      form.items.splice(index, 1)
      toast.add({
        severity: 'success',
        summary: 'Removed',
        detail: 'Product removed successfully',
        life: 2000
      })
    }
  })
}

const onCellEditComplete = (event) => {
  let { data, newValue, field } = event
  data[field] = newValue
}

const importFromExcel = () => {
  toast.add({
    severity: 'info',
    summary: 'Coming Soon',
    detail: 'Excel import feature will be available soon',
    life: 3000
  })
}

// Status management
const validateBonEntree = () => {
  // Open validation modal instead of direct confirmation
  showValidationModal.value = true
  loadStoragesForService()
}

const loadStoragesForService = async () => {
  if (!form.service_id) {
    toast.add({
      severity: 'warn',
      summary: 'Service Required',
      detail: 'Please select a service before validation',
      life: 3000
    })
    return
  }

  try {
    validationModalData.loadingStorages = true
    
    // Fetch storage options for the selected service
    const response = await axios.get(`/api/services/${form.service_id}/storages`)
    
    if (response.data.status === 'success') {
      validationModalData.storageOptions = response.data.data || []
      validationModalData.serviceInfo = response.data.service || null
      
      // Auto-select optical storage if available and only one option
      if (validationModalData.storageOptions.length === 1) {
        validationModalData.selectedStorage = validationModalData.storageOptions[0]
      }
    }
  } catch (err) {
    console.error('Error loading storages:', err)
    // If endpoint doesn't exist, just continue with validation
    validationModalData.storageOptions = []
  } finally {
    validationModalData.loadingStorages = false
  }
}

const confirmValidation = async () => {
  try {
    const response = await axios.post(`/api/purchasing/bon-entrees/${bonEntreeId}/validate`, {
      storage_id: validationModalData.selectedStorage?.id || null
    })

    if (response.data.status === 'success') {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Bon entree validated successfully',
        life: 3000
      })
      showValidationModal.value = false
      fetchBonEntree()
    }
  } catch (err) {
    console.error('Error validating bon entree:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Failed to validate bon entree',
      life: 3000
    })
  }
}

const closeValidationModal = () => {
  showValidationModal.value = false
  validationModalData.selectedStorage = null
  validationModalData.storageOptions = []
  validationModalData.serviceInfo = null
}

const transferToStock = () => {
  confirm.require({
    message: 'Are you sure you want to transfer this bon entree to stock? This action cannot be undone.',
    header: 'Transfer to Stock',
    icon: 'pi pi-send',
    acceptClass: 'p-button-warning',
    accept: async () => {
      try {
        const response = await axios.post(`/api/purchasing/bon-entrees/${bonEntreeId}/transfer-to-stock`)

        if (response.data.status === 'success') {
          toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Bon entree transferred to stock successfully',
            life: 3000
          })
          fetchBonEntree()
        }
      } catch (err) {
        console.error('Error transferring bon entree:', err)
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: err.response?.data?.message || 'Failed to transfer bon entree to stock',
          life: 3000
        })
      }
    }
  })
}

const generateTickets = async () => {
  try {
    const response = await axios.get(`/api/purchasing/bon-entrees/${bonEntreeId}/generate-tickets`)
    
    const newWindow = window.open('', '_blank')
    if (newWindow) {
      newWindow.document.write(response.data)
      newWindow.document.close()
      setTimeout(() => {
        newWindow.print()
      }, 250)
    } else {
      toast.add({
        severity: 'warn',
        summary: 'Popup Blocked',
        detail: 'Please allow popups to print tickets',
        life: 5000
      })
    }
  } catch (err) {
    console.error('Error generating tickets:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Failed to generate tickets',
      life: 3000
    })
  }
}

const cancelBonEntree = () => {
  confirm.require({
    message: 'Are you sure you want to cancel this bon entree?',
    header: 'Cancel Bon Entree',
    icon: 'pi pi-times-circle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        const response = await axios.post(`/api/purchasing/bon-entrees/${bonEntreeId}/cancel`)

        if (response.data.status === 'success') {
          toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Bon entree cancelled successfully',
            life: 3000
          })
          fetchBonEntree()
        }
      } catch (err) {
        console.error('Error cancelling bon entree:', err)
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: err.response?.data?.message || 'Failed to cancel bon entree',
          life: 3000
        })
      }
    }
  })
}

const saveAsDraft = async () => {
  try {
    savingDraft.value = true
    await submitForm(true)
  } finally {
    savingDraft.value = false
  }
}

const submitForm = async (asDraft = false) => {
  try {
    saving.value = true
    errors.value = {}

    const payload = {
      service_id: form.service_id,
      fournisseur_id: form.fournisseur_id,
      notes: form.notes,
      status: asDraft ? 'draft' : bonEntreeData.value.status,
      items: form.items.map(item => {
        // Calculate remaining quantity not allocated to sub-items
        const subItems = getSubItems(item)
        const totalSubQuantity = subItems.reduce((total, sub) => total + (Number(sub.quantity) || 0), 0)
        const remainingQuantity = (Number(item.quantity) || 0) - totalSubQuantity
        
        // Prepare sub_items array with formatted dates
        let finalSubItems = subItems.map(sub => ({
          ...sub,
          expiry_date: sub.expiry_date ? formatDateForAPI(sub.expiry_date) : null
        }))
        
        // CRITICAL: Auto-add remaining quantity as a sub-item if there's any left
        if (remainingQuantity > 0) {
          finalSubItems.push({
            id: Date.now(), // Temporary ID for new sub-item
            quantity: remainingQuantity,
            purchase_price: item.purchase_price || 0,
            unit: item.unit || 'unit',
            batch_number: item.batch_number || '',
            serial_number: item.serial_number || '',
            expiry_date: item.expiry_date ? formatDateForAPI(item.expiry_date) : null
          })
        }
        
        // Prepare the item - keep either product_id OR pharmacy_product_id
        const itemData = {
          id: item.id,
          quantity: item.quantity,
          purchase_price: item.purchase_price,
          sell_price: item.sell_price,
          tva: item.tva,
          unit: item.unit,
          batch_number: item.batch_number,
          serial_number: item.serial_number,
          storage_name: item.storage_name,
          remarks: item.remarks,
          expiry_date: item.expiry_date ? formatDateForAPI(item.expiry_date) : null,
          sub_items: finalSubItems
        }
        
        // Include either product_id OR pharmacy_product_id (not both)
        if (item.pharmacy_product_id) {
          itemData.pharmacy_product_id = item.pharmacy_product_id
        } else if (item.product_id) {
          itemData.product_id = item.product_id
        }
        
        return itemData
      })
    }

    const response = await axios.put(`/api/purchasing/bon-entrees/${bonEntreeId}`, payload)

    if (response.data.status === 'success') {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: asDraft ? 'Bon entree saved as draft' : 'Bon entree updated successfully',
        life: 3000
      })

      fetchBonEntree()
    }
  } catch (err) {
    console.error('Error updating bon entree:', err)

    if (err.response?.status === 422) {
      errors.value = err.response.data.errors || {}
      toast.add({
        severity: 'error',
        summary: 'Validation Error',
        detail: 'Please check the form for errors',
        life: 3000
      })
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: err.response?.data?.message || 'Failed to update bon entree',
        life: 3000
      })
    }
  } finally {
    saving.value = false
  }
}

// Calculation methods
const calculateItemTotal = (item) => {
  const subtotal = calculateItemBaseTotal(item)
  const tvaAmount = subtotal * ((item.tva || 0) / 100)
  return subtotal + tvaAmount
}

const calculateSubtotal = () => {
  return form.items.reduce((total, item) => total + calculateItemBaseTotal(item), 0)
}

const calculateTotalTVA = () => {
  return form.items.reduce((total, item) => {
    const itemSubtotal = calculateItemBaseTotal(item)
    return total + (itemSubtotal * ((item.tva || 0) / 100))
  }, 0)
}

const calculateTotalQuantity = () => {
  return form.items.reduce((total, item) => total + getTotalQuantityForItem(item), 0)
}

const calculateTotalAmount = () => {
  return calculateSubtotal() + calculateTotalTVA()
}

// Utility functions
const getProductById = (id) => {
  // First try to find in form.items (which now contains product data)
  const item = form.items.find(item => item.product_id === id)
  if (item) {
    // Return the stored product object or construct one from item data
    return item.product || {
      id: item.product_id,
      name: item.product_name || 'Unknown Product',
      product_code: item.product_code || '',
      category: item.category || '',
      unit: item.unit || 'unit'
    }
  }
  // Fallback: return a basic product object if not found
  return { id, name: 'Unknown Product', product_code: '', category: '', unit: 'unit' }
}

/**
 * Get product display name from either pharmacy_product or product
 */
const getProductDisplayName = (item) => {
  if (!item) return 'Unknown Product'
  
  // Check if it has pharmacy_product relationship
  if (item.pharmacy_product?.name) {
    return item.pharmacy_product.name
  }
  
  // Check if it has product relationship
  if (item.product?.name) {
    return item.product.name
  }
  
  // Fallback to stored product_name
  if (item.product_name) {
    return item.product_name
  }
  
  return 'Unknown Product'
}

/**
 * Get product code from either pharmacy_product or product
 */
const getProductDisplayCode = (item) => {
  if (!item) return 'N/A'
  
  if (item.pharmacy_product?.code) {
    return item.pharmacy_product.code
  }
  
  if (item.product?.product_code) {
    return item.product.product_code
  }
  
  if (item.product_code) {
    return item.product_code
  }
  
  return 'N/A'
}

/**
 * Get product category from either pharmacy_product or product
 */
const getProductDisplayCategory = (item) => {
  if (!item) return null
  
  if (item.pharmacy_product?.category) {
    return item.pharmacy_product.category
  }
  
  if (item.product?.category) {
    return item.product.category
  }
  
  if (item.category) {
    return item.category
  }
  
  return null
}

/**
 * Get inventory quantity from either pharmacy_inventory or inventory
 */
const getProductInventoryQty = (item) => {
  if (!item) return null
  
  // For pharmacy products
  if (item.pharmacy_product_id && item.pharmacy_product?.inventory) {
    const invs = Array.isArray(item.pharmacy_product.inventory)
      ? item.pharmacy_product.inventory
      : (item.pharmacy_product.inventory ? [item.pharmacy_product.inventory] : [])
    const totalQty = invs.reduce((sum, inv) => sum + (inv?.quantity || 0), 0)
    return totalQty > 0 ? totalQty : 0
  }
  
  // For regular products
  if (item.product_id && item.product?.inventory) {
    const invs = Array.isArray(item.product.inventory)
      ? item.product.inventory
      : (item.product.inventory ? [item.product.inventory] : [])
    const totalQty = invs.reduce((sum, inv) => sum + (inv?.quantity || 0), 0)
    return totalQty > 0 ? totalQty : 0
  }
  
  return null
}

const getServiceName = () => {
  const service = services.value.find(s => s.id === form.service_id)
  return service?.name
}

const getStatusLabel = (status) => {
  const statusMap = {
    draft: 'Draft',
    validated: 'Validated',
    transferred: 'Transferred',
    cancelled: 'Cancelled'
  }
  return statusMap[status] || status
}

const getStatusSeverity = (status) => {
  const severityMap = {
    draft: 'info',
    validated: 'success',
    transferred: 'warning',
    cancelled: 'danger'
  }
  return severityMap[status] || 'info'
}

const getStockSeverity = (quantity) => {
  if (quantity <= 0) return 'danger'
  if (quantity <= 10) return 'warning'
  return 'success'
}

const getExpirySeverity = (date) => {
  if (!date) return 'info'
  const expiryDate = new Date(date)
  const today = new Date()
  const daysUntilExpiry = Math.floor((expiryDate - today) / (1000 * 60 * 60 * 24))

  if (daysUntilExpiry < 0) return 'danger'
  if (daysUntilExpiry < 30) return 'warning'
  return 'success'
}

const getExpiryIcon = (date) => {
  const severity = getExpirySeverity(date)
  if (severity === 'danger') return 'pi pi-times-circle'
  if (severity === 'warning') return 'pi pi-exclamation-triangle'
  return 'pi pi-check-circle'
}

const getRowClass = (data) => {
  const expirySeverity = getExpirySeverity(data.expiry_date)
  if (expirySeverity === 'danger') return 'tw-bg-red-50'
  if (expirySeverity === 'warning') return 'tw-bg-yellow-50'
  return ''
}

const getEventColor = (type) => {
  const colors = {
    created: '#10b981',
    updated: '#3b82f6',
    validated: '#8b5cf6',
    transferred: '#f59e0b',
    cancelled: '#ef4444'
  }
  return colors[type] || '#6b7280'
}

const formatCurrency = (amount) => {
  if (!amount && amount !== 0) return 'DZD 0.00'
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD',
    minimumFractionDigits: 2
  }).format(amount)
}

const formatDate = (date) => {
  if (!date) return ''
  const d = new Date(date)
  return d.toLocaleDateString('fr-FR')
}

const formatDateTime = (date) => {
  if (!date) return ''
  const d = new Date(date)
  return d.toLocaleString('fr-FR')
}

const formatDateForAPI = (date) => {
  if (!date) return null
  return date instanceof Date ? date.toISOString().split('T')[0] : date
}

const onAttachmentsUpdated = () => {
  toast.add({
    severity: 'info',
    summary: 'Attachments Updated',
    detail: 'Document attachments have been updated',
    life: 2000
  })
}

const viewBonReception = () => {
  if (bonEntreeData.value?.bon_reception?.id) {
    router.push(`/bon-receptions/${bonEntreeData.value.bon_reception.id}`)
  }
}

const printBonEntree = () => {
  toast.add({
    severity: 'info',
    summary: 'Print',
    detail: 'Print feature will be available soon',
    life: 3000
  })
}

// Lifecycle
onMounted(async () => {
  await Promise.all([
    fetchBonEntree(),
    fetchServices()
  ])
})

// Watchers
watch(() => form.items.length, () => {
  if (form.items.length > 0) activeStep.value = 1
})

// Auto-populate service_id from bon_reception's service
watch(() => bonEntreeData.value?.bon_reception, (bonReception) => {
  if (bonReception && !form.service_id) {
    // Try to get service_id from the bon_reception
    if (bonReception.service_id) {
      form.service_id = bonReception.service_id
    } else if (bonReception.service_name) {
      // If we have service_name but not service_id, find it from services list
      const service = services.value.find(s => s.name === bonReception.service_name)
      if (service) {
        form.service_id = service.id
      }
    }
  }
}, { immediate: true })

// Auto-populate fournisseur from bon_reception
watch(() => bonEntreeData.value?.bon_reception, (bonReception) => {
  if (bonReception && !form.fournisseur_id) {
    if (bonReception.fournisseur_id) {
      form.fournisseur_id = bonReception.fournisseur_id
    }
  }
}, { immediate: true })
</script>

<style scoped>
/* Advanced Animations */
@keyframes float-slow {
  0%, 100% { transform: translateY(0px) rotate(0deg); }
  50% { transform: translateY(-20px) rotate(5deg); }
}

@keyframes float-reverse {
  0%, 100% { transform: translateY(0px) rotate(0deg); }
  50% { transform: translateY(20px) rotate(-5deg); }
}

@keyframes float-delayed {
  0%, 100% { transform: translateY(0px) scale(1); }
  50% { transform: translateY(-15px) scale(1.1); }
}

@keyframes float-reverse-delayed {
  0%, 100% { transform: translateY(0px) scale(1); }
  50% { transform: translateY(15px) scale(0.9); }
}

@keyframes pulse-glow {
  0%, 100% { opacity: 0.1; transform: scale(1); }
  50% { opacity: 0.3; transform: scale(1.2); }
}

@keyframes shimmer {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}

@keyframes gradient-shift {
  0%, 100% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
}

@keyframes particle-1 {
  0%, 100% { transform: translate(0, 0) rotate(0deg); opacity: 0.3; }
  25% { transform: translate(10px, -10px) rotate(90deg); opacity: 0.6; }
  50% { transform: translate(-5px, -20px) rotate(180deg); opacity: 0.9; }
  75% { transform: translate(-15px, -5px) rotate(270deg); opacity: 0.6; }
}

@keyframes particle-2 {
  0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.4; }
  50% { transform: translate(-20px, 15px) scale(1.5); opacity: 0.8; }
}

@keyframes particle-3 {
  0%, 100% { transform: translate(0, 0); opacity: 0.25; }
  33% { transform: translate(25px, -10px); opacity: 0.5; }
  66% { transform: translate(-10px, 20px); opacity: 0.75; }
}

@keyframes particle-4 {
  0%, 100% { transform: translate(0, 0) rotate(0deg); opacity: 0.35; }
  50% { transform: translate(15px, 25px) rotate(180deg); opacity: 0.7; }
}

@keyframes slide-in-up {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes fade-in-left {
  from {
    opacity: 0;
    transform: translateX(-30px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes fade-in-right {
  from {
    opacity: 0;
    transform: translateX(30px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes fade-in-up {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes bounce-gentle {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-5px); }
}

/* Animation Classes */
.tw-animate-float-slow { animation: float-slow 6s ease-in-out infinite; }
.tw-animate-float-reverse { animation: float-reverse 8s ease-in-out infinite; }
.tw-animate-float-delayed { animation: float-delayed 7s ease-in-out infinite; }
.tw-animate-float-reverse-delayed { animation: float-reverse-delayed 9s ease-in-out infinite; }
.tw-animate-pulse-glow { animation: pulse-glow 4s ease-in-out infinite; }
.tw-animate-shimmer { animation: shimmer 3s linear infinite; }
.tw-animate-gradient-shift { animation: gradient-shift 8s ease infinite; background-size: 200% 200%; }
.tw-animate-particle-1 { animation: particle-1 8s ease-in-out infinite; }
.tw-animate-particle-2 { animation: particle-2 6s ease-in-out infinite; }
.tw-animate-particle-3 { animation: particle-3 10s ease-in-out infinite; }
.tw-animate-particle-4 { animation: particle-4 7s ease-in-out infinite; }
.tw-animate-slide-in-up { animation: slide-in-up 0.8s ease-out; }
.tw-animate-fade-in-left { animation: fade-in-left 0.8s ease-out; }
.tw-animate-fade-in-right { animation: fade-in-right 0.8s ease-out; }
.tw-animate-fade-in-up { animation: fade-in-up 0.6s ease-out; }
.tw-animate-bounce-gentle { animation: bounce-gentle 2s ease-in-out infinite; }
.tw-animate-pulse-slow { animation: pulse 3s ease-in-out infinite; }

/* Animation Delays */
.tw-animation-delay-200 { animation-delay: 0.2s; }
.tw-animation-delay-400 { animation-delay: 0.4s; }
.tw-animation-delay-600 { animation-delay: 0.6s; }
.tw-animation-delay-800 { animation-delay: 0.8s; }

/* DataTable enhancements */
:deep(.p-datatable) {
  border: 0;
  border-radius: 1rem;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  color: #334155;
  font-weight: 700;
  border-color: #e2e8f0;
  padding: 1rem 0.75rem;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  position: relative;
  overflow: hidden;
}

:deep(.p-datatable .p-datatable-thead > tr > th::before) {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
  transition: left 0.5s;
}

:deep(.p-datatable .p-datatable-thead > tr > th:hover::before) {
  left: 100%;
}

:deep(.p-datatable .p-datatable-tbody > tr) {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border-bottom: 1px solid #f1f5f9;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background: linear-gradient(135deg, #fefbff 0%, #f0f9ff 100%);
  transform: translateY(-2px);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
  padding: 1rem 0.75rem;
  border-color: #f1f5f9;
  vertical-align: middle;
}

/* Enhanced Card styling */
:deep(.p-card) {
  border-radius: 1rem;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.2);
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  transition: all 0.3s ease;
}

:deep(.p-card:hover) {
  transform: translateY(-4px);
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

:deep(.p-card-title) {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1e293b;
}

/* Enhanced TabView */
:deep(.p-tabview-nav) {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-radius: 0.75rem 0.75rem 0 0;
  padding: 0.5rem;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
}

:deep(.p-tabview-nav-link) {
  border-radius: 0.5rem;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  font-weight: 600;
  color: #64748b;
}

:deep(.p-tabview-nav-link:hover) {
  background: rgba(99, 102, 241, 0.1);
  color: #4f46e5;
  transform: translateY(-2px);
}

:deep(.p-tabview-nav-link.p-highlight) {
  background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
  color: white;
  box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3);
}

/* Enhanced Dialog */
:deep(.p-dialog) {
  border-radius: 1rem;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  border: 1px solid rgba(255, 255, 255, 0.2);
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
}

:deep(.p-dialog-header) {
  background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
  color: white;
  border-radius: 1rem 1rem 0 0;
  padding: 1.5rem;
}

:deep(.p-dialog-header .p-dialog-title) {
  color: white;
  font-weight: 700;
  font-size: 1.25rem;
}

:deep(.p-dialog-content) {
  padding: 2rem;
}

/* Enhanced Button styles */
:deep(.p-button) {
  border-radius: 0.75rem;
  font-weight: 600;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
}

:deep(.p-button:not(:disabled):hover) {
  transform: translateY(-2px) scale(1.02);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

:deep(.p-button:not(:disabled):active) {
  transform: translateY(0) scale(0.98);
}

/* Enhanced Input styles */
:deep(.p-inputtext) {
  border-radius: 0.5rem;
  border: 2px solid #e2e8f0;
  transition: all 0.3s ease;
  background: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(5px);
}

:deep(.p-inputtext:focus) {
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
  background: white;
}

:deep(.p-inputtext:hover) {
  border-color: #cbd5e1;
}

/* Enhanced Dropdown */
:deep(.p-dropdown) {
  border-radius: 0.5rem;
  border: 2px solid #e2e8f0;
  transition: all 0.3s ease;
  background: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(5px);
}

:deep(.p-dropdown:not(.p-disabled):hover) {
  border-color: #cbd5e1;
}

:deep(.p-dropdown.p-focus) {
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

/* Enhanced Tag styles */
:deep(.p-tag) {
  border-radius: 0.5rem;
  font-weight: 600;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
}

/* Loading state enhancements */
:deep(.p-progress-spinner) {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Grid view enhancements */
.grid-item {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.grid-item:hover {
  transform: translateY(-8px) scale(1.02);
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

/* Timeline enhancements */
:deep(.p-timeline-event-marker) {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

:deep(.p-timeline-event-content) {
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(10px);
  border-radius: 0.75rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

/* Toast enhancements */
:deep(.p-toast) {
  border-radius: 0.75rem;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

/* Custom utility classes */
.enhanced-primary-btn {
  background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%) !important;
  border: none !important;
  box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3) !important;
}

.enhanced-success-btn {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
  border: none !important;
  box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3) !important;
}

.enhanced-info-btn {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
  border: none !important;
  box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3) !important;
}

.enhanced-cancel-btn {
  background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%) !important;
  border: none !important;
  box-shadow: 0 4px 6px -1px rgba(107, 114, 128, 0.3) !important;
}

.enhanced-dropdown {
  border-radius: 0.5rem !important;
  border: 2px solid #e2e8f0 !important;
  transition: all 0.3s ease !important;
}

.enhanced-dropdown:hover {
  border-color: #cbd5e1 !important;
}

.enhanced-dropdown.p-focus {
  border-color: #4f46e5 !important;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1) !important;
}

.high-density-input {
  padding: 0.5rem 0.75rem !important;
  font-size: 0.875rem !important;
  line-height: 1.25rem !important;
}

.large-datatable {
  font-size: 0.875rem !important;
}

/* Responsive enhancements */
@media (max-width: 768px) {
  :deep(.p-datatable .p-datatable-tbody > tr:hover) {
    transform: none;
  }

  .tw-animate-float-slow,
  .tw-animate-float-reverse,
  .tw-animate-float-delayed,
  .tw-animate-float-reverse-delayed {
    animation-duration: 12s;
  }
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
  .tw-animate-float-slow,
  .tw-animate-float-reverse,
  .tw-animate-float-delayed,
  .tw-animate-float-reverse-delayed,
  .tw-animate-pulse-glow,
  .tw-animate-shimmer,
  .tw-animate-gradient-shift,
  .tw-animate-particle-1,
  .tw-animate-particle-2,
  .tw-animate-particle-3,
  .tw-animate-particle-4,
  .tw-animate-slide-in-up,
  .tw-animate-fade-in-left,
  .tw-animate-fade-in-right,
  .tw-animate-fade-in-up,
  .tw-animate-bounce-gentle,
  .tw-animate-pulse-slow {
    animation: none;
  }

  :deep(.p-button:not(:disabled):hover) {
    transform: none;
  }

  :deep(.p-card:hover) {
    transform: none;
  }

  :deep(.p-datatable .p-datatable-tbody > tr:hover) {
    transform: none;
  }
}
</style>