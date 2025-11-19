<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-indigo-50 tw-via-purple-50/30 tw-to-white tw-p-4 md:tw-p-8">
    <div class="tw-max-w-[1600px] tw-mx-auto">
      <!-- Enhanced Header Section -->
      <div class="tw-bg-white tw-rounded-3xl tw-shadow-2xl tw-overflow-hidden tw-mb-6 tw-border tw-border-indigo-100">
        <div class="tw-bg-gradient-to-r tw-from-indigo-600 tw-via-purple-600 tw-to-indigo-700 tw-p-8">
          <div class="tw-flex tw-flex-col md:tw-flex-row md:tw-justify-between md:tw-items-center tw-gap-4">
            <div class="tw-flex tw-items-center tw-gap-4">
              <div class="tw-bg-white/20 tw-backdrop-blur tw-p-4 tw-rounded-2xl tw-shadow-lg">
                <i class="pi pi-calendar-plus tw-text-white tw-text-3xl"></i>
              </div>
              <div>
                <h1 class="tw-text-4xl tw-font-bold tw-text-white tw-tracking-tight">Emergency Nurse Planning</h1>
                <p class="tw-text-indigo-100 tw-mt-2 tw-text-sm">Manage 24/7 nurse schedules and emergency shifts</p>
              </div>
            </div>
            <Button 
              @click="openCreateModal" 
              icon="pi pi-plus" 
              class="tw-bg-white tw-text-indigo-600 tw-font-semibold tw-shadow-xl hover:tw-shadow-2xl tw-transition-all tw-duration-300"
              label="New Planning"
            />
          </div>
        </div>

        <!-- Quick Stats Bar -->
        <div class="tw-bg-gradient-to-r tw-from-indigo-50 tw-to-purple-50 tw-px-8 tw-py-4 tw-border-t tw-border-indigo-100">
          <div class="tw-flex tw-items-center tw-justify-between tw-flex-wrap tw-gap-4">
            <div class="tw-flex tw-gap-6">
              <div class="tw-flex tw-items-center tw-gap-2">
                <div class="tw-w-3 tw-h-3 tw-bg-green-500 tw-rounded-full tw-animate-pulse"></div>
                <span class="tw-text-sm tw-text-gray-600">Active: <span class="tw-font-bold tw-text-green-700">{{ plannings.filter(p => p.status === 'active').length }}</span></span>
              </div>
              <div class="tw-flex tw-items-center tw-gap-2">
                <div class="tw-w-3 tw-h-3 tw-bg-blue-500 tw-rounded-full"></div>
                <span class="tw-text-sm tw-text-gray-600">Today: <span class="tw-font-bold tw-text-blue-700">{{ plannings.filter(p => formatDate(p.planning_date) === formatDate(new Date())).length }}</span></span>
              </div>
              <div class="tw-flex tw-items-center tw-gap-2">
                <div class="tw-w-3 tw-h-3 tw-bg-purple-500 tw-rounded-full"></div>
                <span class="tw-text-sm tw-text-gray-600">This Week: <span class="tw-font-bold tw-text-purple-700">{{ getThisWeekCount() }}</span></span>
              </div>
            </div>
            <div class="tw-flex tw-gap-2">
              <Button 
                @click="printMonthlyReport" 
                icon="pi pi-print" 
                class="p-button-text tw-text-indigo-600"
                v-tooltip="'Print Report'"
              />
              <Button 
                @click="refreshData" 
                icon="pi pi-refresh" 
                class="p-button-text tw-text-purple-600"
                v-tooltip="'Refresh'"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Enhanced Filters Section -->
      <div class="tw-bg-white tw-rounded-3xl tw-shadow-xl tw-p-6 tw-mb-6 tw-border tw-border-indigo-100">
        <div class="tw-flex tw-items-center tw-gap-3 tw-mb-6">
          <div class="tw-p-2 tw-bg-gradient-to-br tw-from-indigo-500 tw-to-purple-500 tw-rounded-xl">
            <i class="pi pi-filter tw-text-white"></i>
          </div>
          <h3 class="tw-text-xl tw-font-bold tw-text-gray-800">Filters & Actions</h3>
        </div>

        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-5 tw-gap-4">
          <div class="tw-space-y-2">
            <label class="tw-block tw-text-sm tw-font-semibold tw-text-indigo-700">
              <i class="pi pi-calendar tw-text-indigo-500 tw-mr-2"></i>Month
            </label>
            <Dropdown 
              v-model="filters.month" 
              :options="monthOptions" 
              option-label="label" 
              option-value="value"
              placeholder="Select Month"
              class="tw-w-full tw-border-indigo-200"
              @change="loadPlannings"
            />
          </div>

          <div class="tw-space-y-2">
            <label class="tw-block tw-text-sm tw-font-semibold tw-text-purple-700">
              <i class="pi pi-calendar tw-text-purple-500 tw-mr-2"></i>Year
            </label>
            <Dropdown 
              v-model="filters.year" 
              :options="yearOptions" 
              option-label="label" 
              option-value="value"
              placeholder="Select Year"
              class="tw-w-full tw-border-purple-200"
              @change="loadPlannings"
            />
          </div>

          <div class="tw-space-y-2">
            <label class="tw-block tw-text-sm tw-font-semibold tw-text-green-700">
              <i class="pi pi-building tw-text-green-500 tw-mr-2"></i>Service
            </label>
            <Dropdown 
              v-model="filters.serviceId" 
              :options="services" 
              option-label="name" 
              option-value="id"
              placeholder="All Services"
              class="tw-w-full tw-border-green-200"
              @change="loadPlannings"
              show-clear
            />
          </div>

          <div class="tw-space-y-2">
            <label class="tw-block tw-text-sm tw-font-semibold tw-text-orange-700">
              <i class="pi pi-eye tw-text-orange-500 tw-mr-2"></i>Daily View
            </label>
            <Calendar 
              v-model="selectedDate" 
              placeholder="Select Date"
              class="tw-w-full tw-border-orange-200"
              date-format="yy-mm-dd"
              @date-select="loadDayOverview"
              show-button-bar
            />
          </div>

          <div class="tw-space-y-2">
            <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
              <i class="pi pi-bolt tw-text-yellow-500 tw-mr-2"></i>Quick Actions
            </label>
            <div class="tw-flex tw-flex-wrap tw-gap-2">
              <Button 
                @click="getMonthlyOverview" 
                icon="pi pi-chart-line" 
                class="tw-bg-gradient-to-r tw-from-blue-500 tw-to-indigo-500 tw-text-white tw-border-0 tw-flex-1"
                label="Overview"
              />
              <Button 
                @click="openCopyPlanningModal" 
                icon="pi pi-copy" 
                class="tw-bg-gradient-to-r tw-from-purple-500 tw-to-pink-500 tw-text-white tw-border-0 tw-flex-1"
                label="Copy"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Enhanced View Toggle -->
      <div class="tw-bg-white tw-rounded-3xl tw-shadow-xl tw-p-4 tw-mb-6 tw-border tw-border-indigo-100">
        <div class="tw-flex tw-flex-col sm:tw-flex-row tw-justify-between tw-items-center tw-gap-4">
          <div class="tw-flex tw-gap-2">
            <Button 
              @click="viewMode = 'calendar'" 
              :class="viewMode === 'calendar' ? 'tw-bg-gradient-to-r tw-from-indigo-600 tw-to-purple-600 tw-text-white tw-border-0' : 'tw-border-indigo-300 tw-text-indigo-600'"
              icon="pi pi-calendar"
              label="Calendar View"
              class="tw-transition-all tw-duration-300"
            />
            <Button 
              @click="viewMode = 'table'" 
              :class="viewMode === 'table' ? 'tw-bg-gradient-to-r tw-from-indigo-600 tw-to-purple-600 tw-text-white tw-border-0' : 'tw-border-indigo-300 tw-text-indigo-600'"
              icon="pi pi-table"
              label="Table View"
              class="tw-transition-all tw-duration-300"
            />
          </div>
          <div class="tw-flex tw-items-center tw-gap-3 tw-bg-gradient-to-r tw-from-indigo-50 tw-to-purple-50 tw-px-5 tw-py-3 tw-rounded-xl tw-border tw-border-indigo-200">
            <i class="pi pi-list tw-text-indigo-600 tw-text-lg"></i>
            <span class="tw-text-sm tw-font-semibold tw-text-gray-700">Total Shifts:</span>
            <span class="tw-text-2xl tw-font-bold tw-text-transparent tw-bg-clip-text tw-bg-gradient-to-r tw-from-indigo-600 tw-to-purple-600">
              {{ plannings.length }}
            </span>
          </div>
        </div>
      </div>

      <!-- Enhanced Daily Schedule Overview -->
      <div v-if="dayOverview && selectedDate" class="tw-bg-white tw-rounded-3xl tw-shadow-xl tw-border tw-border-indigo-100 tw-p-6 tw-mb-6">
        <div class="tw-flex tw-flex-col sm:tw-flex-row tw-justify-between tw-items-start sm:tw-items-center tw-gap-4 tw-mb-6">
          <div class="tw-flex tw-items-center tw-gap-4">
            <div class="tw-bg-gradient-to-br tw-from-orange-400 tw-to-pink-500 tw-p-3 tw-rounded-2xl tw-shadow-lg">
              <i class="pi pi-calendar-times tw-text-white tw-text-2xl"></i>
            </div>
            <div>
              <h3 class="tw-text-2xl tw-font-bold tw-text-gray-900">Daily Schedule</h3>
              <p class="tw-text-sm tw-text-gray-600">{{ formatDate(selectedDate) }}</p>
            </div>
          </div>
          <div class="tw-flex tw-gap-4">
            <div class="tw-bg-gradient-to-br tw-from-blue-50 tw-to-indigo-100 tw-px-5 tw-py-3 tw-rounded-xl tw-border tw-border-blue-200">
              <div class="tw-text-xs tw-text-blue-600 tw-font-medium">Total Shifts</div>
              <div class="tw-text-2xl tw-font-bold tw-text-blue-700">{{ dayOverview.total_shifts }}</div>
            </div>
            <div class="tw-bg-gradient-to-br tw-from-green-50 tw-to-emerald-100 tw-px-5 tw-py-3 tw-rounded-xl tw-border tw-border-green-200">
              <div class="tw-text-xs tw-text-green-600 tw-font-medium">Coverage Hours</div>
              <div class="tw-text-2xl tw-font-bold tw-text-green-700">{{ dayOverview.coverage_hours }}h</div>
            </div>
          </div>
        </div>

        <!-- Available Time Slots with Enhanced Design -->
        <div v-if="dayOverview.next_available_slots && dayOverview.next_available_slots.length > 0" class="tw-mb-6">
          <div class="tw-flex tw-items-center tw-gap-3 tw-mb-4">
            <div class="tw-p-2 tw-bg-gradient-to-br tw-from-green-500 tw-to-emerald-500 tw-rounded-xl">
              <i class="pi pi-clock tw-text-white"></i>
            </div>
            <h4 class="tw-font-bold tw-text-lg tw-text-gray-800">Available Time Slots</h4>
            <span class="tw-bg-gradient-to-r tw-from-green-100 tw-to-emerald-100 tw-text-green-700 tw-text-xs tw-font-bold tw-px-3 tw-py-1 tw-rounded-full tw-border tw-border-green-200">
              {{ dayOverview.next_available_slots.length }} slots
            </span>
          </div>

          <div class="tw-grid tw-grid-cols-1 sm:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-4">
            <div 
              v-for="slot in dayOverview.next_available_slots" 
              :key="`${slot.start_time}-${slot.end_time}`"
              class="tw-bg-gradient-to-br tw-from-green-50 tw-to-emerald-50 tw-border-2 tw-border-green-200 tw-rounded-2xl tw-p-5 tw-cursor-pointer hover:tw-from-green-100 hover:tw-to-emerald-100 hover:tw-border-green-300 hover:tw-shadow-xl hover:tw-scale-105 tw-transition-all tw-duration-300 tw-group"
              @click="() => createPlanningFromSlot(slot)"
            >
              <div class="tw-flex tw-items-center tw-justify-between tw-mb-3">
                <div class="tw-font-bold tw-text-green-800 tw-text-xl">
                  {{ formatTime(slot.start_time) }} - {{ formatTime(slot.end_time) }}
                </div>
                <i class="pi pi-plus-circle tw-text-green-600 tw-text-2xl group-hover:tw-scale-125 group-hover:tw-rotate-90 tw-transition-all tw-duration-300"></i>
              </div>
              <Tag 
                :value="slot.shift_type" 
                :severity="getShiftTypeSeverity(slot.shift_type)"
                class="tw-capitalize tw-font-semibold"
              />
              <div class="tw-text-sm tw-text-green-700 tw-mt-3 tw-font-medium">
                <i class="pi pi-hand-pointer tw-mr-1"></i>
                Click to schedule
              </div>
            </div>
          </div>
        </div>

        <!-- No Shifts State -->
        <div v-if="dayOverview.shifts.length === 0" class="tw-text-center tw-py-16">
          <div class="tw-bg-gradient-to-br tw-from-gray-100 tw-to-gray-200 tw-rounded-full tw-w-24 tw-h-24 tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-4">
            <i class="pi pi-calendar tw-text-gray-400 tw-text-5xl"></i>
          </div>
          <p class="tw-text-gray-600 tw-font-medium tw-text-lg tw-mb-4">No shifts scheduled for this date</p>
          <Button 
            @click="openCreateModal" 
            class="tw-bg-gradient-to-r tw-from-indigo-600 tw-to-purple-600 tw-text-white tw-border-0"
            label="Add First Shift"
            icon="pi pi-plus"
          />
        </div>

        <!-- Scheduled Shifts with Enhanced Cards -->
        <div v-else class="tw-space-y-4">
          <div class="tw-flex tw-items-center tw-gap-3 tw-mb-4">
            <div class="tw-p-2 tw-bg-gradient-to-br tw-from-blue-500 tw-to-indigo-500 tw-rounded-xl">
              <i class="pi pi-list tw-text-white"></i>
            </div>
            <h4 class="tw-font-bold tw-text-lg tw-text-gray-800">Scheduled Shifts</h4>
          </div>

          <div 
            v-for="shift in dayOverview.shifts" 
            :key="shift.id"
            class="tw-flex tw-flex-col sm:tw-flex-row tw-items-start sm:tw-items-center tw-justify-between tw-p-5 tw-bg-gradient-to-r tw-from-white tw-via-indigo-50/30 tw-to-purple-50/30 tw-rounded-2xl tw-border tw-border-indigo-200 hover:tw-border-indigo-400 hover:tw-shadow-xl tw-transition-all tw-duration-300"
          >
            <div class="tw-flex tw-flex-wrap tw-items-center tw-gap-4 tw-mb-3 sm:tw-mb-0">
              <div class="tw-flex tw-items-center tw-gap-2 tw-bg-white tw-px-4 tw-py-2 tw-rounded-xl tw-border tw-border-indigo-200 tw-shadow-sm">
                <i class="pi pi-clock tw-text-indigo-500"></i>
                <span class="tw-font-bold tw-text-gray-900">
                  {{ formatTime(shift.start_time) }} - {{ formatTime(shift.end_time) }}
                </span>
              </div>
              <div class="tw-flex tw-items-center tw-gap-2">
                <Avatar 
                  :label="getNurseDisplayName(shift.nurse).charAt(0)"
                  class="tw-bg-gradient-to-br tw-from-purple-500 tw-to-indigo-500 tw-text-white"
                  size="small"
                />
                <span class="tw-font-medium tw-text-gray-800">
                  {{ getNurseDisplayName(shift.nurse) }} 
                </span>
              </div>
              <Tag 
                :value="shift.shift_type" 
                :severity="getShiftTypeSeverity(shift.shift_type)"
                class="tw-capitalize tw-font-semibold"
              />
            </div>
            <div class="tw-flex tw-items-center tw-gap-3">
              <div class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-text-gray-600 tw-bg-gradient-to-r tw-from-gray-100 tw-to-gray-50 tw-px-3 tw-py-2 tw-rounded-lg">
                <i class="pi pi-building tw-text-xs"></i>
                <span>{{ getServiceDisplayName(shift.service) }}</span>
              </div>
              <Button 
                @click="() => editPlanning(plannings.find(p => p.id === shift.id))" 
                class="p-button-rounded tw-bg-gradient-to-r tw-from-indigo-500 tw-to-purple-500 tw-text-white tw-border-0"
                icon="pi pi-pencil"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Calendar/Table View Container -->
      <div 
        v-if="viewMode === 'calendar'" 
        class="tw-bg-white tw-rounded-3xl tw-shadow-xl tw-border tw-border-indigo-100 tw-relative tw-min-h-[600px] tw-p-6"
      >
        <FullCalendar :options="calendarOptions" />
        <div 
          v-if="loading"
          class="tw-absolute tw-inset-0 tw-flex tw-flex-col tw-items-center tw-justify-center tw-bg-white/90 tw-backdrop-blur-sm tw-z-10 tw-rounded-3xl"
        >
          <div class="tw-relative">
            <div class="tw-w-16 tw-h-16 tw-border-4 tw-border-indigo-200 tw-rounded-full"></div>
            <div class="tw-w-16 tw-h-16 tw-border-4 tw-border-indigo-600 tw-border-t-transparent tw-rounded-full tw-animate-spin tw-absolute tw-top-0"></div>
          </div>
          <p class="tw-mt-4 tw-text-indigo-600 tw-font-medium">Loading plannings...</p>
        </div>
      </div>

      <!-- Enhanced Table View -->
      <div v-else class="tw-bg-white tw-rounded-3xl tw-shadow-xl tw-border tw-border-indigo-100 tw-overflow-hidden">
        <DataTable 
          :value="plannings" 
          :loading="loading"
          paginator 
          :rows="20"
          :rowsPerPageOptions="[10, 20, 50]"
          responsive-layout="scroll"
          class="enhanced-planning-table"
          :global-filter-fields="['nurse.name', 'nurse.nom', 'nurse.prenom', 'service.name', 'service.nom', 'shift_type']"
        >
          <template #header>
            <div class="tw-bg-gradient-to-r tw-from-indigo-50 tw-to-purple-50 tw-p-6 tw-border-b tw-border-indigo-100">
              <div class="tw-flex tw-flex-col sm:tw-flex-row tw-justify-between tw-items-center tw-gap-4">
                <h3 class="tw-text-xl tw-font-bold tw-text-indigo-900">Planning List</h3>
                <span class="p-input-icon-left tw-w-full sm:tw-w-96">
                  <i class="pi pi-search tw-text-indigo-500" />
                  <InputText 
                    v-model="globalFilter" 
                    placeholder="Search plannings..." 
                    class="tw-w-full tw-pl-10 tw-border-indigo-200 focus:tw-border-indigo-500" 
                  />
                </span>
              </div>
            </div>
          </template>

          <!-- Table columns with enhanced styling -->
          <Column field="planning_date" header="Date" sortable>
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-calendar tw-text-indigo-400"></i>
                <span class="tw-font-medium tw-text-gray-900">{{ formatDate(slotProps.data.planning_date) }}</span>
              </div>
            </template>
          </Column>

          <Column field="nurse" header="Nurse" sortable>
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-gap-3">
                <Avatar 
                  :label="getPlanningNurseName(slotProps.data).charAt(0)"
                  class="tw-bg-gradient-to-br tw-from-purple-500 tw-to-indigo-500 tw-text-white"
                  size="small"
                />
                <span class="tw-font-medium tw-text-gray-800">{{ getPlanningNurseName(slotProps.data) }}</span>
              </div>
            </template>
          </Column>

          <Column field="service.name" header="Service" sortable>
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-building tw-text-green-500"></i>
                <span>{{ getServiceDisplayName(slotProps.data.service) }}</span>
              </div>
            </template>
          </Column>

          <Column field="shift_type" header="Shift Type" sortable>
            <template #body="slotProps">
              <Tag 
                :value="slotProps.data.shift_type" 
                :severity="getShiftTypeSeverity(slotProps.data.shift_type)"
                class="tw-capitalize tw-font-semibold"
              />
            </template>
          </Column>

          <Column field="shift_start_time" header="Time" sortable>
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-gap-2 tw-text-sm">
                <i class="pi pi-clock tw-text-blue-500"></i>
                <span class="tw-font-medium">{{ formatTime(slotProps.data.shift_start_time) }} - {{ formatTime(slotProps.data.shift_end_time) }}</span>
              </div>
            </template>
          </Column>

          <Column field="notes" header="Notes">
            <template #body="slotProps">
              <span class="tw-truncate tw-max-w-xs tw-block tw-text-sm tw-text-gray-600" :title="slotProps.data.notes">
                {{ slotProps.data.notes || '—' }}
              </span>
            </template>
          </Column>

          <Column header="Actions">
            <template #body="slotProps">
              <div class="tw-flex tw-gap-1">
                <Button 
                  @click="editPlanning(slotProps.data)" 
                  icon="pi pi-pencil" 
                  class="p-button-rounded p-button-text tw-text-indigo-600 hover:tw-bg-indigo-50"
                  v-tooltip="'Edit'"
                />
                <Button 
                  @click="deletePlanning(slotProps.data)" 
                  icon="pi pi-trash" 
                  class="p-button-rounded p-button-text tw-text-red-600 hover:tw-bg-red-50"
                  v-tooltip="'Delete'"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </div>

      <!-- Planning Modal -->
      <NursingEmergencyPlanningModal
        :visible="modalVisible"
        @update:visible="modalVisible = $event"
        :planning="selectedPlanning"
        :nurses="nurses"
        :services="services"
        @saved="onPlanningSaved"
      />

      <!-- Overview Dialog -->
      <Dialog 
        :visible="overviewVisible" 
        @update:visible="overviewVisible = $event"
        header="Monthly Overview" 
        :style="{ width: '90vw', maxWidth: '1200px' }"
        :modal="true"
        class="tw-rounded-2xl"
      >
        <div v-if="overview" class="tw-space-y-6">
          <!-- Statistics Cards -->
          <div class="tw-grid tw-grid-cols-2 md:tw-grid-cols-4 tw-gap-4">
            <div class="tw-bg-gradient-to-br tw-from-blue-50 tw-to-blue-100 tw-p-6 tw-rounded-xl tw-border-2 tw-border-blue-200 tw-shadow-sm hover:tw-shadow-md tw-transition-shadow">
              <i class="pi pi-list tw-text-blue-600 tw-text-2xl tw-mb-2"></i>
              <div class="tw-text-3xl tw-font-bold tw-text-blue-700">{{ overview.statistics.total_shifts }}</div>
              <div class="tw-text-sm tw-text-blue-800 tw-font-medium tw-mt-1">Total Shifts</div>
            </div>
            <div class="tw-bg-gradient-to-br tw-from-green-50 tw-to-green-100 tw-p-6 tw-rounded-xl tw-border-2 tw-border-green-200 tw-shadow-sm hover:tw-shadow-md tw-transition-shadow">
              <i class="pi pi-sun tw-text-green-600 tw-text-2xl tw-mb-2"></i>
              <div class="tw-text-3xl tw-font-bold tw-text-green-700">{{ overview.statistics.day_shifts }}</div>
              <div class="tw-text-sm tw-text-green-800 tw-font-medium tw-mt-1">Day Shifts</div>
            </div>
            <div class="tw-bg-gradient-to-br tw-from-purple-50 tw-to-purple-100 tw-p-6 tw-rounded-xl tw-border-2 tw-border-purple-200 tw-shadow-sm hover:tw-shadow-md tw-transition-shadow">
              <i class="pi pi-moon tw-text-purple-600 tw-text-2xl tw-mb-2"></i>
              <div class="tw-text-3xl tw-font-bold tw-text-purple-700">{{ overview.statistics.night_shifts }}</div>
              <div class="tw-text-sm tw-text-purple-800 tw-font-medium tw-mt-1">Night Shifts</div>
            </div>
            <div class="tw-bg-gradient-to-br tw-from-orange-50 tw-to-orange-100 tw-p-6 tw-rounded-xl tw-border-2 tw-border-orange-200 tw-shadow-sm hover:tw-shadow-md tw-transition-shadow">
              <i class="pi pi-exclamation-triangle tw-text-orange-600 tw-text-2xl tw-mb-2"></i>
              <div class="tw-text-3xl tw-font-bold tw-text-orange-700">{{ overview.statistics.emergency_shifts }}</div>
              <div class="tw-text-sm tw-text-orange-800 tw-font-medium tw-mt-1">Emergency Shifts</div>
            </div>
          </div>

          <!-- Coverage Information -->
          <div class="tw-bg-gradient-to-r tw-from-gray-50 tw-to-white tw-p-6 tw-rounded-xl tw-border tw-border-gray-200">
            <h3 class="tw-text-lg tw-font-bold tw-mb-4 tw-text-gray-900 tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-chart-bar tw-text-green-600"></i>
              Coverage Analysis
            </h3>
            <div class="tw-flex tw-flex-col sm:tw-flex-row tw-items-center tw-gap-4">
              <div class="tw-bg-white tw-px-4 tw-py-2 tw-rounded-lg tw-border tw-border-gray-200">
                <span class="tw-font-bold tw-text-2xl tw-text-green-600">{{ overview.coverage.covered_days }}</span> 
                <span class="tw-text-gray-600 tw-mx-1">/</span> 
                <span class="tw-text-gray-700 tw-font-medium">{{ overview.coverage.total_days }}</span>
                <span class="tw-text-sm tw-text-gray-600 tw-ml-2">days covered</span>
              </div>
              <div class="tw-flex-1 tw-bg-gray-200 tw-rounded-full tw-h-4 tw-overflow-hidden tw-shadow-inner">
                <div 
                  class="tw-bg-gradient-to-r tw-from-green-500 tw-to-emerald-500 tw-h-4 tw-rounded-full tw-transition-all tw-duration-500 tw-shadow-sm" 
                  :style="{ width: overview.coverage.coverage_percentage + '%' }"
                ></div>
              </div>
              <div class="tw-bg-green-100 tw-px-4 tw-py-2 tw-rounded-lg tw-border tw-border-green-300">
                <span class="tw-font-bold tw-text-2xl tw-text-green-700">{{ overview.coverage.coverage_percentage }}%</span>
              </div>
            </div>
          </div>
        </div>
      </Dialog>

      <!-- Copy Planning Dialog -->
      <Dialog 
        :visible="copyPlanningVisible" 
        @update:visible="copyPlanningVisible = $event"
        header="Copy Monthly Planning" 
        :style="{ width: '90vw', maxWidth: '700px' }"
        :modal="true"
        class="p-fluid"
      >
        <div class="tw-space-y-6">
          <div class="tw-bg-gradient-to-r tw-from-blue-50 tw-to-cyan-50 tw-p-5 tw-rounded-xl tw-border-2 tw-border-blue-200">
            <div class="tw-flex tw-items-start tw-gap-3 tw-text-blue-800 tw-mb-2">
              <i class="pi pi-info-circle tw-text-xl tw-mt-1"></i>
              <div>
                <span class="tw-font-bold tw-text-lg">Copy Planning</span>
                <p class="tw-text-sm tw-text-blue-700 tw-mt-2 tw-leading-relaxed">
                  This will copy all planning entries from the source month to the target month, 
                  adjusting dates accordingly while preserving shift times and assignments.
                </p>
              </div>
            </div>
          </div>

          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
            <div class="tw-bg-white tw-p-5 tw-rounded-xl tw-border-2 tw-border-green-200">
              <h4 class="tw-font-bold tw-text-gray-800 tw-mb-4 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-arrow-up tw-text-green-600"></i>
                From (Source)
              </h4>
              <div class="tw-space-y-4">
                <div>
                  <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">Month</label>
                  <Dropdown 
                    v-model="copyPlanningForm.fromMonth" 
                    :options="monthOptions" 
                    option-label="label" 
                    option-value="value"
                    placeholder="Select Month"
                    class="tw-w-full"
                  />
                </div>
                <div>
                  <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">Year</label>
                  <Dropdown 
                    v-model="copyPlanningForm.fromYear" 
                    :options="yearOptions" 
                    option-label="label" 
                    option-value="value"
                    placeholder="Select Year"
                    class="tw-w-full"
                  />
                </div>
              </div>
            </div>

            <div class="tw-bg-white tw-p-5 tw-rounded-xl tw-border-2 tw-border-blue-200">
              <h4 class="tw-font-bold tw-text-gray-800 tw-mb-4 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-arrow-down tw-text-blue-600"></i>
                To (Target)
              </h4>
              <div class="tw-space-y-4">
                <div>
                  <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">Month</label>
                  <Dropdown 
                    v-model="copyPlanningForm.toMonth" 
                    :options="monthOptions" 
                    option-label="label" 
                    option-value="value"
                    placeholder="Select Month"
                    class="tw-w-full"
                  />
                </div>
                <div>
                  <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">Year</label>
                  <Dropdown 
                    v-model="copyPlanningForm.toYear" 
                    :options="yearOptions" 
                    option-label="label" 
                    option-value="value"
                    placeholder="Select Year"
                    class="tw-w-full"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>

        <template #footer>
          <div class="tw-flex tw-justify-end tw-gap-3 tw-pt-4">
            <Button 
              @click="copyPlanningVisible = false" 
              label="Cancel" 
              class="p-button-text p-button-secondary" 
              icon="pi pi-times"
            />
            <Button 
              @click="copyPlanning" 
              label="Copy Planning" 
              :loading="loading"
              class="p-button-success tw-shadow-lg"
              icon="pi pi-copy"
              :disabled="!copyPlanningForm.fromMonth || !copyPlanningForm.fromYear || !copyPlanningForm.toMonth || !copyPlanningForm.toYear"
            />
          </div>
        </template>
      </Dialog>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dropdown from 'primevue/dropdown'
import InputText from 'primevue/inputtext'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import Calendar from 'primevue/calendar'
import ProgressSpinner from 'primevue/progressspinner'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'
import NursingEmergencyPlanningModal from './NursingEmergencyPlanningModal.vue'
import axios from 'axios'

const toast = useToast()
const confirm = useConfirm()

// Data
const plannings = ref([])
const nurses = ref([])
const services = ref([])
const loading = ref(false)
const modalVisible = ref(false)
const overviewVisible = ref(false)
const selectedPlanning = ref(null)
const overview = ref(null)
const globalFilter = ref('')
const viewMode = ref('table')
const selectedDate = ref(new Date())
const dayOverview = ref(null)
const copyPlanningVisible = ref(false)
const copyPlanningForm = ref({
  fromMonth: null,
  fromYear: null,
  toMonth: null,
  toYear: null
})


let loadPlanningsRequestId = 0


// Filters
const filters = ref({
  month: new Date().getMonth() + 1,
  year: new Date().getFullYear(),
  serviceId: null
})

// Options
const monthOptions = [
  { label: 'January', value: 1 },
  { label: 'February', value: 2 },
  { label: 'March', value: 3 },
  { label: 'April', value: 4 },
  { label: 'May', value: 5 },
  { label: 'June', value: 6 },
  { label: 'July', value: 7 },
  { label: 'August', value: 8 },
  { label: 'September', value: 9 },
  { label: 'October', value: 10 },
  { label: 'November', value: 11 },
  { label: 'December', value: 12 }
]

const yearOptions = computed(() => {
  const currentYear = new Date().getFullYear()
  const years = []
  for (let i = currentYear; i <= currentYear + 5; i++) {
    years.push({ label: i.toString(), value: i })
  }
  return years
})

// Helper utilities
const getNurseDisplayName = (nurse) => {
  if (!nurse) {
    return 'Unassigned'
  }

  if (typeof nurse === 'string') {
    return nurse
  }

  const {
    display_name: displayName,
    full_name: fullName,
    name,
    nom,
    prenom,
    email,
    id
  } = nurse

  if (displayName) {
    return displayName
  }

  if (fullName) {
    return fullName
  }

  const parts = [name, nom, prenom].filter(Boolean)
  if (parts.length) {
    return parts.join(' ').replace(/\s+/g, ' ').trim()
  }

  if (email) {
    return email
  }

  if (id) {
    return `Nurse #${id}`
  }

  return 'Unknown Nurse'
}

const getPlanningNurseName = (planning) => {
  if (!planning) {
    return 'Unassigned'
  }
  
  // Use resource fields first (more reliable)
  if (planning.nurse_display_name) {
    return planning.nurse_display_name
  }

  // Fallback to nested nurse object from resource
  if (planning.nurse?.name) {
    return planning.nurse.name
  }

  if (planning.nurse?.display_name) {
    return planning.nurse.display_name
  }

  if (planning.nurse?.full_name) {
    return planning.nurse.full_name
  }

  // Legacy fallbacks
  if (planning.nurse_name) {
    return planning.nurse_name
  }

  if (planning.nurse_full_name) {
    return planning.nurse_full_name
  }

  if (planning.nurse_nom || planning.nurse_prenom) {
    const parts = [planning.nurse_nom, planning.nurse_prenom].filter(Boolean)
    if (parts.length) {
      return parts.join(' ').replace(/\s+/g, ' ').trim()
    }
  }

  return getNurseDisplayName(planning.nurse)
}

const getServiceDisplayName = (service) => {
  if (!service) {
    return 'Unassigned'
  }

  if (typeof service === 'string') {
    return service
  }

  // Use resource fields
  return service.name ?? service.display_name ?? service.nom ?? 'Unassigned'
}

const handleDatesSet = (info) => {
  const referenceDate = info?.view?.currentStart || info?.start || info?.startStr

  if (!referenceDate) {
    return
  }

  const dateObj = referenceDate instanceof Date ? referenceDate : new Date(referenceDate)
  const newMonth = dateObj.getMonth() + 1
  const newYear = dateObj.getFullYear()

  const monthChanged = newMonth !== filters.value.month
  const yearChanged = newYear !== filters.value.year

  if (monthChanged || yearChanged) {
    filters.value.month = newMonth
    filters.value.year = newYear
    loadPlannings()
  }
}

// Calendar options
const calendarOptions = computed(() => ({
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  headerToolbar: {
    left: 'prev,next today',
    center: 'title',
    right: 'dayGridMonth,timeGridWeek,timeGridDay'
  },
  datesSet: handleDatesSet,
  events: plannings.value.flatMap(planning => {
    const events = []
    const startTime = planning.shift_start_time
    const endTime = planning.shift_end_time
    const isOvernight = endTime <= startTime
    
    if (isOvernight) {
      // Create two events for overnight shift
      // Part 1: Start date from shift start to end of day
      events.push({
        id: `${planning.id}-part1`,
        title: `${getPlanningNurseName(planning)} (${planning.shift_type}) - Night Part 1`,
        start: planning.planning_date + 'T' + startTime,
        end: planning.planning_date + 'T23:59:59',
        backgroundColor: getShiftTypeColor(planning.shift_type),
        borderColor: getShiftTypeColor(planning.shift_type),
        extendedProps: {
          planning: planning,
          isOvernightPart: 1
        }
      })
      
      // Part 2: Next day from start of day to shift end
      const nextDay = new Date(planning.planning_date)
      nextDay.setDate(nextDay.getDate() + 1)
      const nextDayStr = nextDay.toISOString().split('T')[0]
      
      events.push({
        id: `${planning.id}-part2`,
        title: `${getPlanningNurseName(planning)} (${planning.shift_type}) - Night Part 2`,
        start: nextDayStr + 'T00:00:00',
        end: nextDayStr + 'T' + endTime,
        backgroundColor: getShiftTypeColor(planning.shift_type),
        borderColor: getShiftTypeColor(planning.shift_type),
        extendedProps: {
          planning: planning,
          isOvernightPart: 2
        }
      })
    } else {
      // Regular shift - single event
      events.push({
        id: planning.id,
        title: `${getPlanningNurseName(planning)} (${planning.shift_type})`,
        start: planning.planning_date + 'T' + startTime,
        end: planning.planning_date + 'T' + endTime,
        backgroundColor: getShiftTypeColor(planning.shift_type),
        borderColor: getShiftTypeColor(planning.shift_type),
        extendedProps: {
          planning: planning
        },
         eventDidMount: (info) => {
    // Make event text white
    info.el.style.color = 'white'
    info.el.style.fontWeight = 'bold'
    info.el.style.textShadow = '0 1px 2px rgba(0,0,0,0.2)'

    // Ensure the background is blue gradient
    info.el.style.background = 'linear-gradient(135deg, #2563eb, #0284c7)'
    info.el.style.border = 'none'
    info.el.style.borderRadius = '0.5rem'
    info.el.style.padding = '2px 6px'
  },
        
      })
    }
    
    return events
  }),
  eventClick: handleEventClick,
  selectable: true,
  select: handleDateSelect
}))

// Methods
const loadPlannings = async () => {
  const requestId = ++loadPlanningsRequestId
  loading.value = true
  const params = {
    month: filters.value.month,
    year: filters.value.year
  }
  if (filters.value.serviceId) {
    params.service_id = filters.value.serviceId
  }

  const requestedMonth = params.month
  const requestedYear = params.year
  try {
    const response = await axios.get('/api/nursing-emergency-planning', { params })

    if (
      requestId === loadPlanningsRequestId &&
      requestedMonth === filters.value.month &&
      requestedYear === filters.value.year
    ) {
      plannings.value = response.data.data
    }
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load plannings',
      life: 3000
    })
  } finally {
    if (requestId === loadPlanningsRequestId) {
      loading.value = false
    }
  }
}

const loadNurses = async () => {
  try {
    const response = await axios.get('/api/nursing-emergency-planning/data/nurses')
    nurses.value = response.data.data
  } catch (error) {
    console.error('Failed to load nurses:', error)
  }
}

const loadServices = async () => {
  try {
    const response = await axios.get('/api/nursing-emergency-planning/data/services')
    services.value = response.data.data
  } catch (error) {
    console.error('Failed to load services:', error)
  }
}

const getMonthlyOverview = async () => {
  try {
    const params = {
      month: filters.value.month,
      year: filters.value.year
    }
    const response = await axios.get('/api/nursing-emergency-planning/overview/monthly', { params })
    overview.value = response.data.data
    overviewVisible.value = true
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load overview',
      life: 3000
    })
  }
}

const loadDayOverview = async () => {
  if (!selectedDate.value) return
  
  try {
    const params = {
      planning_date: formatDate(selectedDate.value)
    }
    const response = await axios.get('/api/nursing-emergency-planning/day-overview', { params })
    dayOverview.value = response.data.data
  } catch (error) {
    console.error('Failed to load day overview:', error)
  }
}

const openCreateModal = () => {
  selectedPlanning.value = null
  modalVisible.value = true
}

const createPlanningFromSlot = (slot) => {
  selectedPlanning.value = {
    planning_date: selectedDate.value,
    shift_start_time: slot.start_time,
    shift_end_time: slot.end_time,
    shift_type: slot.shift_type,
    month: filters.value.month,
    year: filters.value.year
  }
  modalVisible.value = true
}

const editPlanning = (planning) => {
  selectedPlanning.value = { ...planning }
  modalVisible.value = true
}

const deletePlanning = (planning) => {
  confirm.require({
    message: 'Are you sure you want to delete this planning?',
    header: 'Confirmation',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      try {
        await axios.delete(`/api/nursing-emergency-planning/${planning.id}`)
        toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Planning deleted successfully',
          life: 3000
        })
        loadPlannings()
      } catch (error) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to delete planning',
          life: 3000
        })
      }
    }
  })
}

const onPlanningSaved = () => {
  modalVisible.value = false
  loadPlannings()
}

const openCopyPlanningModal = () => {
  copyPlanningForm.value = {
    fromMonth: filters.value.month,
    fromYear: filters.value.year,
    toMonth: filters.value.month === 12 ? 1 : filters.value.month + 1,
    toYear: filters.value.month === 12 ? filters.value.year + 1 : filters.value.year
  }
  copyPlanningVisible.value = true
}

const copyPlanning = async () => {
  try {
    loading.value = true
    const response = await axios.post('/api/nursing-emergency-planning/copy-planning', copyPlanningForm.value)
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: response.data.message.replace('{count}', response.data.count),
      life: 4000
    })
    
    copyPlanningVisible.value = false
    
    // If copying to current view, reload plannings
    if (copyPlanningForm.value.toMonth === filters.value.month && 
        copyPlanningForm.value.toYear === filters.value.year) {
      loadPlannings()
    }
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to copy planning',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const printMonthlyReport = async () => {
  try {
    const params = {
      month: filters.value.month,
      year: filters.value.year
    }
    if (filters.value.serviceId) {
      params.service_id = filters.value.serviceId
    }
    
    const response = await axios.get('/api/nursing-emergency-planning/print-report', { params })
    
    // Create a new window for printing
    const printWindow = window.open('', '_blank')
    
    // Generate the print HTML
    const printHTML = generatePrintHTML(response.data.data)
    
    printWindow.document.write(printHTML)
    printWindow.document.close()
    
    // Focus and print
    printWindow.focus()
    setTimeout(() => {
      printWindow.print()
    }, 250)
    
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to generate print report',
      life: 3000
    })
  }
}

const generatePrintHTML = (reportData) => {
  const { month_name, plannings_by_date, statistics } = reportData
  
  let html = `
    <!DOCTYPE html>
    <html>
    <head>
      <title>Emergency Planning Report - ${month_name}</title>
      <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { border: 1px solid #ddd; padding: 15px; text-align: center; }
        .day-section { margin-bottom: 20px; page-break-inside: avoid; }
        .day-header { background: #f5f5f5; padding: 10px; font-weight: bold; }
        .shift { padding: 8px; border-bottom: 1px solid #eee; display: grid; grid-template-columns: 1fr 2fr 2fr 1fr; gap: 10px; }
        .shift-header { font-weight: bold; background: #f9f9f9; }
        @media print { 
          .day-section { page-break-inside: avoid; }
          body { margin: 0; }
        }
      </style>
    </head>
    <body>
      <div class="header">
        <h1>Emergency Nurse Planning Report</h1>
        <h2>${month_name}</h2>
        <p>Generated on: ${new Date().toLocaleString()}</p>
      </div>
      
      <div class="stats">
        <div class="stat-card">
          <h3>${statistics.total_shifts}</h3>
          <p>Total Shifts</p>
        </div>
        <div class="stat-card">
          <h3>${statistics.total_nurses}</h3>
          <p>Total Nurses</p>
        </div>
        <div class="stat-card">
          <h3>${statistics.day_shifts}</h3>
          <p>Day Shifts</p>
        </div>
        <div class="stat-card">
          <h3>${statistics.night_shifts}</h3>
          <p>Night Shifts</p>
        </div>
        <div class="stat-card">
          <h3>${statistics.emergency_shifts}</h3>
          <p>Emergency Shifts</p>
        </div>
        <div class="stat-card">
          <h3>${statistics.total_services}</h3>
          <p>Services Involved</p>
        </div>
      </div>
  `
  
  // Add daily schedules
  Object.keys(plannings_by_date).sort().forEach(date => {
    const shifts = plannings_by_date[date]
    const formattedDate = new Date(date).toLocaleDateString('en-US', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    })
    
    html += `
      <div class="day-section">
        <div class="day-header">${formattedDate}</div>
        <div class="shift shift-header">
          <div>Time</div>
          <div>Nurse</div>
          <div>Service</div>
          <div>Type</div>
        </div>
    `
    
    shifts.forEach(shift => {
      html += `
        <div class="shift">
          <div>${formatTime(shift.shift_start_time)} - ${formatTime(shift.shift_end_time)}</div>
          <div>${shift.nurse_display_name || 'Unknown Nurse'}</div>
          <div>${shift.service?.name || 'Unknown Service'}</div>
          <div style="text-transform: capitalize;">${shift.shift_type}</div>
        </div>
      `
    })
    
    html += '</div>'
  })
  
  html += '</body></html>'
  return html
}

const handleEventClick = (info) => {
  editPlanning(info.event.extendedProps.planning)
}

const handleDateSelect = (selectionInfo) => {
  selectedPlanning.value = {
    planning_date: selectionInfo.startStr,
    month: filters.value.month,
    year: filters.value.year
  }
  modalVisible.value = true
}
const getThisWeekCount = () => {
  const today = new Date()
  const startOfWeek = new Date(today.setDate(today.getDate() - today.getDay()))
  const endOfWeek = new Date(today.setDate(today.getDate() - today.getDay() + 6))

  return plannings.value.filter(p => {
    const pDate = new Date(p.planning_date)
    return pDate >= startOfWeek && pDate <= endOfWeek
  }).length
}

const refreshData = () => {
  loadPlannings()
  loadNurses()
  loadServices()
}


// Utility functions
const formatDate = (date) => {
  if (!date) return ''
  const d = new Date(date)
  return d.getFullYear() + '-' + 
         String(d.getMonth() + 1).padStart(2, '0') + '-' + 
         String(d.getDate()).padStart(2, '0')
}

const formatTime = (time) => {
  if (!time) return ''
  return new Date('2000-01-01 ' + time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

const getShiftTypeSeverity = (type) => {
  switch (type) {
    case 'day': return 'success'
    case 'night': return 'info'
    case 'emergency': return 'danger'
    default: return 'secondary'
  }
}

const getShiftTypeColor = (type) => {
  switch (type) {
    case 'day': return '#10b981'
    case 'night': return '#3b82f6'
    case 'emergency': return '#ef4444'
    default: return '#6b7280'
  }
}

// Lifecycle
onMounted(() => {
  loadPlannings()
  loadNurses()
  loadServices()
})
</script>

<style scoped>
/* Updated table styles with blue theme */
.enhanced-planning-table :deep(.p-datatable) {
  border: none;
  font-size: 0.875rem;
}

.enhanced-planning-table :deep(.p-datatable-thead > tr > th) {
  background: linear-gradient(to right, #dbeafe, #e0f2fe);
  border: none;
  color: #1e40af;
  font-weight: 600;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 1rem;
  border-bottom: 2px solid #93c5fd;
}

.enhanced-planning-table :deep(.p-datatable-tbody > tr) {
  border-bottom: 1px solid #dbeafe;
  transition: all 0.2s ease;
}

.enhanced-planning-table :deep(.p-datatable-tbody > tr:hover) {
  background: linear-gradient(to right, rgba(219, 234, 254, 0.5), rgba(224, 242, 254, 0.5));
}

/* Enhanced calendar styles with white text */
:deep(.fc) {
  font-family: inherit;
}

:deep(.fc-event) {
  font-size: 0.85rem;
  border-radius: 0.5rem;
  padding: 2px 6px;
  background: linear-gradient(135deg, #2563eb, #0284c7) !important;
  border: none !important;
  color: white !important;
  font-weight: 600 !important;
  text-shadow: 0 1px 2px rgba(0,0,0,0.2);
}

:deep(.fc-event-title) {
  color: white !important;
  font-weight: 600 !important;
}

:deep(.fc-event-time) {
  color: white !important;
  opacity: 0.95;
}

:deep(.fc .fc-button-primary) {
  background: linear-gradient(to right, #2563eb, #0284c7);
  border: none;
}

:deep(.fc .fc-button-primary:hover) {
  background: linear-gradient(to right, #1d4ed8, #0369a1);
}

:deep(.fc-toolbar-title) {
  font-size: 1.5rem !important;
  font-weight: 700;
  background: linear-gradient(to right, #2563eb, #0284c7);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

/* Make sure calendar event text is white */
:deep(.fc-daygrid-event) {
  color: white !important;
}

:deep(.fc-list-event-title) {
  color: white !important;
}

:deep(.fc-list-event-time) {
  color: white !important;
}

/* Enhanced input styles with blue */
:deep(.p-inputtext:focus) {
  border-color: #2563eb;
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
}

:deep(.p-dropdown:not(.p-disabled).p-focus) {
  border-color: #2563eb;
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
}

/* Animations */
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.tw-animate-spin {
  animation: spin 1s linear infinite;
}

.tw-animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}
</style>