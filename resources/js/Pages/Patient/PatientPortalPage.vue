<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-slate-50 tw-via-blue-50 tw-to-indigo-50" v-tooltip>
    <!-- Enhanced Medical-themed Header -->
    <div class="tw-bg-white tw-border-b tw-border-slate-200 tw-sticky tw-top-0 tw-z-10 tw-shadow-lg tw-backdrop-blur-sm tw-bg-white/95">
      <div class="tw-px-4 lg:tw-px-8 xl:tw-px-12 tw-py-6">
        <div class="tw-flex tw-items-center tw-justify-between tw-items-start lg:tw-items-center tw-gap-6">
          <div class="tw-flex tw-items-center tw-gap-4">
            <Button
              icon="pi pi-arrow-left"
              severity="secondary"
              outlined
              @click="goBack"
              class="p-button-outlined p-button-secondary tw-rounded-xl tw-px-6 tw-py-3"
              v-tooltip.top="'Go Back'"
            />
            <div class="tw-bg-gradient-to-br tw-from-indigo-500 tw-to-blue-600 tw-p-3 tw-rounded-xl tw-shadow-lg">
              <i class="pi pi-user tw-text-white tw-text-2xl"></i>
            </div>
            <div>
              <h1 class="tw-text-3xl tw-font-bold tw-text-slate-900 tw-mb-1">Patient Portal</h1>
              <p class="tw-text-slate-600 tw-text-base">Comprehensive patient information and management system</p>
            </div>
          </div>

          <!-- Enhanced Quick Stats -->
          <div class="tw-flex tw-gap-6" v-if="patient">
            <div class="tw-bg-gradient-to-r tw-from-blue-50 tw-to-blue-100 tw-px-6 tw-py-4 tw-rounded-xl tw-border tw-border-blue-200 tw-shadow-sm">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-bg-blue-500 tw-p-2 tw-rounded-lg">
                  <i class="pi pi-calendar tw-text-white"></i>
                </div>
                <div>
                  <div class="tw-text-xs tw-font-medium tw-text-blue-700 tw-uppercase tw-tracking-wide">Total Appointments</div>
                  <div class="tw-text-2xl tw-font-bold tw-text-blue-800">{{ appointments.length }}</div>
                </div>
              </div>
            </div>
            <div class="tw-bg-gradient-to-r tw-from-green-50 tw-to-green-100 tw-px-6 tw-py-4 tw-rounded-xl tw-border tw-border-green-200 tw-shadow-sm">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-bg-green-500 tw-p-2 tw-rounded-lg">
                  <i class="pi pi-check-circle tw-text-white"></i>
                </div>
                <div>
                  <div class="tw-text-xs tw-font-medium tw-text-green-700 tw-uppercase tw-tracking-wide">Active</div>
                  <div class="tw-text-2xl tw-font-bold tw-text-green-800">{{ patient.is_active ? 'Yes' : 'No' }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="tw-flex tw-justify-center tw-items-center tw-h-96">
      <div class="tw-bg-gradient-to-br tw-from-indigo-100 tw-to-blue-100 tw-w-20 tw-h-20 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-6">
        <ProgressSpinner
          class="tw-w-8 tw-h-8"
          strokeWidth="4"
          fill="transparent"
          animationDuration="1.5s"
        />
      </div>
      <h3 class="tw-text-lg tw-font-semibold tw-text-slate-900 tw-mb-2">Loading patient data...</h3>
      <p class="tw-text-slate-500">Please wait while we fetch the latest information</p>
    </div>

    <!-- Main Content -->
    <div v-else-if="patient" class="tw-px-4 lg:tw-px-8 xl:tw-px-12 tw-py-8">
      <!-- Enhanced Patient Header Card -->
      <div class="tw-bg-white tw-rounded-2xl tw-shadow-xl tw-border tw-border-slate-200/60 tw-p-6 lg:tw-p-8 tw-mb-8 tw-backdrop-blur-sm">
        <div class="tw-flex tw-flex-col lg:tw-flex-row tw-items-start lg:tw-items-center tw-justify-between tw-gap-6">
          <div class="tw-flex tw-items-center tw-gap-6">
            <Avatar
              :image="patient.avatar || defaultAvatar"
              size="xlarge"
              shape="circle"
              class="tw-border-4 tw-border-indigo-200 tw-shadow-lg"
            />
            <div>
              <h2 class="tw-text-3xl tw-font-bold tw-text-slate-900 tw-mb-2">
                {{ patient.first_name }} {{ patient.last_name }}
              </h2>
              <div class="tw-flex tw-flex-wrap tw-gap-4 tw-text-sm tw-text-slate-600">
                <div class="tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-id-card tw-text-indigo-500"></i>
                  <span>ID: {{ patient.Idnum || 'N/A' }}</span>
                </div>
                <div class="tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-calendar tw-text-blue-500"></i>
                  <span>Age: {{ calculateAge(patient.dateOfBirth) }}</span>
                </div>
                <div class="tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-phone tw-text-green-500"></i>
                  <span>{{ patient.phone || 'N/A' }}</span>
                </div>
                <Badge
                  :value="patient.gender || 'Not Specified'"
                  :severity="patient.gender?.toLowerCase() === 'male' ? 'info' : patient.gender?.toLowerCase() === 'female' ? 'success' : 'warning'"
                  rounded
                />
              </div>
            </div>
          </div>

          <div class="tw-flex tw-flex-wrap tw-gap-3">
            <Button
              label="Edit Patient"
              icon="pi pi-pencil"
              class="p-button-primary tw-rounded-xl tw-px-8 tw-py-3 tw-font-semibold tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-200"
              @click="openEditModal"
              v-tooltip.top="'Edit Patient'"
            />
            <Button
              label="New Appointment"
              icon="pi pi-plus"
              severity="success"
              class="p-button-success tw-rounded-xl tw-px-8 tw-py-3 tw-font-semibold tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-200"
              @click="openNewAppointment"
              v-tooltip.top="'Schedule New Appointment'"
            />
            <Button
              label="New Consultation"
              icon="pi pi-stethoscope"
              severity="info"
              class="p-button-info tw-rounded-xl tw-px-8 tw-py-3 tw-font-semibold tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-200"
              @click="openNewConsultation"
              v-tooltip.top="'Start New Consultation'"
            />
          </div>
        </div>
      </div>

      <!-- Enhanced Tabs for different sections -->
      <TabView class="tw-min-h-[800px]">
        <!-- Overview Tab -->
        <TabPanel header="Overview" class="tw-p-0">
          <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-2 tw-gap-8 tw-mt-6">
            <!-- Enhanced Patient Information Card -->
            <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-border tw-border-slate-200/60 tw-overflow-hidden tw-backdrop-blur-sm">
              <div class="tw-bg-gradient-to-r tw-from-blue-50 tw-to-blue-100 tw-px-6 tw-py-4 tw-border-b tw-border-blue-200">
                <h3 class="tw-text-lg tw-font-semibold tw-text-blue-800 tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-user tw-text-blue-600"></i>
                  Patient Information
                </h3>
              </div>
              <div class="tw-p-6">
                <div class="tw-space-y-4">
                  <div class="tw-flex tw-justify-between tw-items-center tw-py-3 tw-px-4 tw-bg-slate-50 tw-rounded-lg">
                    <span class="tw-font-medium tw-text-slate-600">Full Name:</span>
                    <span class="tw-text-slate-900 tw-font-semibold">{{ patient.first_name }} {{ patient.last_name }}</span>
                  </div>
                  <div class="tw-flex tw-justify-between tw-items-center tw-py-3 tw-px-4 tw-bg-slate-50 tw-rounded-lg">
                    <span class="tw-font-medium tw-text-slate-600">ID Number:</span>
                    <span class="tw-text-slate-900">{{ patient.Idnum || 'N/A' }}</span>
                  </div>
                  <div class="tw-flex tw-justify-between tw-items-center tw-py-3 tw-px-4 tw-bg-slate-50 tw-rounded-lg">
                    <span class="tw-font-medium tw-text-slate-600">Date of Birth:</span>
                    <span class="tw-text-slate-900">{{ formatDate(patient.dateOfBirth) }}</span>
                  </div>
                  <div class="tw-flex tw-justify-between tw-items-center tw-py-3 tw-px-4 tw-bg-slate-50 tw-rounded-lg">
                    <span class="tw-font-medium tw-text-slate-600">Age:</span>
                    <span class="tw-text-slate-900 tw-font-semibold">{{ calculateAge(patient.dateOfBirth) }}</span>
                  </div>
                  <div class="tw-flex tw-justify-between tw-items-center tw-py-3 tw-px-4 tw-bg-slate-50 tw-rounded-lg">
                    <span class="tw-font-medium tw-text-slate-600">Gender:</span>
                    <Badge
                      :value="patient.gender || 'Not Specified'"
                      :severity="patient.gender?.toLowerCase() === 'male' ? 'info' : patient.gender?.toLowerCase() === 'female' ? 'success' : 'warning'"
                      rounded
                    />
                  </div>
                  <div class="tw-flex tw-justify-between tw-items-center tw-py-3 tw-px-4 tw-bg-slate-50 tw-rounded-lg">
                    <span class="tw-font-medium tw-text-slate-600">Phone:</span>
                    <span class="tw-text-slate-900">{{ patient.phone || 'N/A' }}</span>
                  </div>
                  <div class="tw-flex tw-justify-between tw-items-center tw-py-3 tw-px-4 tw-bg-slate-50 tw-rounded-lg">
                    <span class="tw-font-medium tw-text-slate-600">Status:</span>
                    <Badge
                      :value="patient.is_active ? 'Active' : 'Inactive'"
                      :severity="patient.is_active ? 'success' : 'danger'"
                      rounded
                    />
                  </div>
                </div>
              </div>
            </div>
            <!-- Enhanced Medical Information Card -->
            <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-border tw-border-slate-200/60 tw-overflow-hidden tw-backdrop-blur-sm">
              <div class="tw-bg-gradient-to-r tw-from-red-50 tw-to-red-100 tw-px-6 tw-py-4 tw-border-b tw-border-red-200">
                <h3 class="tw-text-lg tw-font-semibold tw-text-red-800 tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-heart tw-text-red-600"></i>
                  Medical Information
                </h3>
              </div>
              <div class="tw-p-6">
                <div class="tw-space-y-6">
                  <!-- Allergies -->
                  <div>
                    <h4 class="tw-font-semibold tw-text-slate-700 tw-mb-3 tw-flex tw-items-center tw-gap-2">
                      <i class="pi pi-exclamation-triangle tw-text-red-500"></i>
                      Allergies
                    </h4>
                    <div v-if="patient.allergies && patient.allergies.length > 0" class="tw-flex tw-flex-wrap tw-gap-2">
                      <Chip v-for="allergy in patient.allergies" :key="allergy.id || allergy" :label="allergy.name || allergy" class="tw-bg-red-100 tw-text-red-800 tw-border-red-200" />
                    </div>
                    <div v-else class="tw-bg-slate-50 tw-rounded-lg tw-p-4 tw-text-center">
                      <i class="pi pi-check-circle tw-text-green-500 tw-text-lg tw-mb-1"></i>
                      <p class="tw-text-slate-600 tw-text-sm">No known allergies</p>
                    </div>
                  </div>

                  <!-- Chronic Diseases -->
                  <div>
                    <h4 class="tw-font-semibold tw-text-slate-700 tw-mb-3 tw-flex tw-items-center tw-gap-2">
                      <i class="pi pi-medkit tw-text-orange-500"></i>
                      Chronic Diseases
                    </h4>
                    <div v-if="patient.chronic_diseases && patient.chronic_diseases.length > 0" class="tw-flex tw-flex-wrap tw-gap-2">
                      <Chip v-for="disease in patient.chronic_diseases" :key="disease.id || disease" :label="disease.name || disease" class="tw-bg-orange-100 tw-text-orange-800 tw-border-orange-200" />
                    </div>
                    <div v-else class="tw-bg-slate-50 tw-rounded-lg tw-p-4 tw-text-center">
                      <i class="pi pi-check-circle tw-text-green-500 tw-text-lg tw-mb-1"></i>
                      <p class="tw-text-slate-600 tw-text-sm">No chronic diseases</p>
                    </div>
                  </div>

                  <!-- Family Diseases -->
                  <div>
                    <h4 class="tw-font-semibold tw-text-slate-700 tw-mb-3 tw-flex tw-items-center tw-gap-2">
                      <i class="pi pi-users tw-text-blue-500"></i>
                      Family Diseases
                    </h4>
                    <div v-if="patient.family_diseases && patient.family_diseases.length > 0" class="tw-flex tw-flex-wrap tw-gap-2">
                      <Chip v-for="disease in patient.family_diseases" :key="disease.id || disease" :label="disease.name || disease" class="tw-bg-blue-100 tw-text-blue-800 tw-border-blue-200" />
                    </div>
                    <div v-else class="tw-bg-slate-50 tw-rounded-lg tw-p-4 tw-text-center">
                      <i class="pi pi-check-circle tw-text-green-500 tw-text-lg tw-mb-1"></i>
                      <p class="tw-text-slate-600 tw-text-sm">No family diseases</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </TabPanel>

        <!-- Appointments Tab -->
        <TabPanel header="Appointments" class="tw-p-0">
          <div class="tw-bg-white tw-rounded-2xl tw-shadow-xl tw-border tw-border-slate-200/60 tw-overflow-hidden tw-backdrop-blur-sm tw-mt-6">
            <div class="tw-bg-gradient-to-r tw-from-green-50 tw-to-green-100 tw-px-6 tw-py-4 tw-border-b tw-border-green-200">
              <div class="tw-flex tw-items-center tw-justify-between">
                <div class="tw-flex tw-items-center tw-gap-4">
                  <h3 class="tw-text-lg tw-font-semibold tw-text-green-800 tw-flex tw-items-center tw-gap-2">
                    <i class="pi pi-calendar tw-text-green-600"></i>
                    Appointments History
                  </h3>
                  <Badge :value="filteredAppointments.length.toString()" severity="info" class="tw-rounded-full" />
                  <!-- Status Filter -->
                  <div class="tw-flex tw-items-center tw-gap-2">
                    <label class="tw-text-sm tw-font-medium tw-text-green-700">Filter by Status:</label>
                    <Dropdown
                      v-model="selectedStatusFilter"
                      :options="statusFilterOptions"
                      optionLabel="label"
                      optionValue="value"
                      placeholder="All Statuses"
                      class="tw-w-48 tw-rounded-xl"
                      showClear
                    />
                  </div>
                </div>
                <Button
                  label="New Appointment"
                  icon="pi pi-plus"
                  severity="success"
                  class="p-button-success tw-rounded-xl tw-px-6 tw-py-2 tw-font-semibold"
                  @click="openNewAppointment"
                  v-tooltip.top="'Schedule New Appointment'"
                />
              </div>
            </div>
            <div class="tw-p-6">
              <DataTable
                :value="filteredAppointments"
                :loading="appointmentsLoading"
                dataKey="id"
                :paginator="true"
                :rows="10"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                currentPageReportTemplate="Showing {first} to {last} of {totalRecords} appointments"
                :rowsPerPageOptions="[10, 25, 50]"
                responsiveLayout="scroll"
                class="appointments-table tw-rounded-xl"
                @row-click="viewAppointment"
              >
                <Column field="appointment_date" header="Date" :sortable="true">
                  <template #body="{ data }">
                    <div class="tw-font-medium tw-text-slate-900">
                      {{ formatDateTime(data.appointment_date) }}
                    </div>
                  </template>
                </Column>

                <Column field="doctor_name" header="Doctor" :sortable="true">
                  <template #body="{ data }">
                    <div class="tw-flex tw-items-center tw-gap-3">
                      <Avatar
                        :label="data.doctor_name?.charAt(0)?.toUpperCase() || 'D'"
                        class="tw-bg-indigo-100 tw-text-indigo-600"
                        size="normal"
                        shape="circle"
                      />
                      <span class="tw-text-slate-700">{{ data.doctor_name }}</span>
                    </div>
                  </template>
                </Column>

                <Column field="status" header="Status" :sortable="true">
                  <template #body="{ data }">
                    <Badge
                      :value="getStatusDisplayName(data.status)"
                      :severity="getStatusSeverity(data.status)"
                      rounded
                    />
                  </template>
                </Column>

                <Column field="actions" header="Actions" headerStyle="width: 12rem">
                  <template #body="{ data }">
                    <div class="tw-flex tw-items-center tw-gap-2">
                      <Button
                        @click.stop="viewAppointment(data)"
                        icon="pi pi-eye"
                        class="p-button-rounded p-button-text p-button-info p-button-sm"
                        v-tooltip.top="'View Appointment'"
                      />
                      <Button
                        @click.stop="editAppointment(data)"
                        icon="pi pi-pencil"
                        class="p-button-rounded p-button-text p-button-primary p-button-sm"
                        v-tooltip.top="'Edit Appointment'"
                      />
                    </div>
                  </template>
                </Column>

                <template #empty>
                  <div class="tw-text-center tw-py-16 tw-px-8">
                    <div class="tw-bg-gradient-to-br tw-from-slate-100 tw-to-slate-200 tw-w-24 tw-h-24 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-6">
                      <i class="pi pi-calendar tw-text-4xl tw-text-slate-400"></i>
                    </div>
                    <h3 class="tw-text-xl tw-font-semibold tw-text-slate-900 tw-mb-2">No appointments found</h3>
                    <p class="tw-text-slate-500 tw-mb-6 tw-max-w-md tw-mx-auto">This patient doesn't have any appointments yet. Schedule their first appointment to get started.</p>
                    <Button
                      label="Schedule First Appointment"
                      icon="pi pi-plus"
                      class="p-button-success tw-rounded-xl tw-px-8 tw-py-3 tw-font-semibold tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-200"
                      @click="openNewAppointment"
                      v-tooltip.top="'Schedule First Appointment'"
                    />
                  </div>
                </template>

                <template #loading>
                  <div class="tw-text-center tw-py-16 tw-px-8">
                    <div class="tw-bg-gradient-to-br tw-from-indigo-100 tw-to-blue-100 tw-w-20 tw-h-20 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-6">
                      <ProgressSpinner
                        class="tw-w-8 tw-h-8"
                        strokeWidth="4"
                        fill="transparent"
                        animationDuration="1.5s"
                      />
                    </div>
                    <h3 class="tw-text-lg tw-font-semibold tw-text-slate-900 tw-mb-2">Loading appointments...</h3>
                    <p class="tw-text-slate-500">Please wait while we fetch the appointment data</p>
                  </div>
                </template>
              </DataTable>
            </div>
          </div>
        </TabPanel>

        <!-- Consultations Tab -->
        <TabPanel header="Consultations" class="tw-p-0">
          <div class="tw-bg-white tw-rounded-2xl tw-shadow-xl tw-border tw-border-slate-200/60 tw-overflow-hidden tw-backdrop-blur-sm tw-mt-6">
            <div class="tw-bg-gradient-to-r tw-from-purple-50 tw-to-purple-100 tw-px-6 tw-py-4 tw-border-b tw-border-purple-200">
              <div class="tw-flex tw-items-center tw-justify-between">
                <h3 class="tw-text-lg tw-font-semibold tw-text-purple-800 tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-stethoscope tw-text-purple-600"></i>
                  Medical Consultations
                </h3>
                <div class="tw-flex tw-items-center tw-gap-4">
                  <Badge :value="consultations.length.toString()" severity="info" class="tw-rounded-full" />
                  <Button
                    label="New Consultation"
                    icon="pi pi-plus"
                    severity="info"
                    class="p-button-info tw-rounded-xl tw-px-6 tw-py-2 tw-font-semibold"
                    @click="openNewConsultation"
                    v-tooltip.top="'Start New Consultation'"
                  />
                </div>
              </div>
            </div>
            <div class="tw-p-6">
              <DataTable
                :value="consultations"
                :loading="consultationsLoading"
                dataKey="id"
                :paginator="true"
                :rows="10"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                currentPageReportTemplate="Showing {first} to {last} of {totalRecords} consultations"
                :rowsPerPageOptions="[10, 25, 50]"
                responsiveLayout="scroll"
                class="consultations-table tw-rounded-xl"
                selectionMode="single"
                @rowSelect="onConsultationRowSelect"
                :rowHover="true"
              >
                <Column field="consultation_date" header="Date" :sortable="true">
                  <template #body="{ data }">
                    <div class="tw-font-medium tw-text-slate-900">
                      {{ formatDate(data.consultation_date) }}
                    </div>
                  </template>
                </Column>

                <Column field="doctor_name" header="Doctor" :sortable="true">
                  <template #body="{ data }">
                    <div class="tw-flex tw-items-center tw-gap-3">
                      <Avatar
                        :label="data.doctor_name?.charAt(0)?.toUpperCase() || 'D'"
                        class="tw-bg-purple-100 tw-text-purple-600"
                        size="normal"
                        shape="circle"
                      />
                      <span class="tw-text-slate-700">{{ data.doctor_name }}</span>
                    </div>
                  </template>
                </Column>

                <Column field="diagnosis" header="Diagnosis">
                  <template #body="{ data }">
                    <div class="tw-max-w-xs tw-truncate tw-text-slate-700">
                      {{ data.diagnosis || 'N/A' }}
                    </div>
                  </template>
                </Column>

                <Column field="treatment" header="Treatment">
                  <template #body="{ data }">
                    <div class="tw-max-w-xs tw-truncate tw-text-slate-700">
                      {{ data.treatment || 'N/A' }}
                    </div>
                  </template>
                </Column>

                <Column field="actions" header="Actions" headerStyle="width: 8rem">
                  <template #body="{ data }">
                    <Button
                      @click.stop="viewConsultationDetails(data)"
                      icon="pi pi-eye"
                      class="p-button-rounded p-button-text p-button-info p-button-sm"
                      v-tooltip.top="'View Details'"
                    />
                  </template>
                </Column>

                <template #empty>
                  <div class="tw-text-center tw-py-16 tw-px-8">
                    <div class="tw-bg-gradient-to-br tw-from-slate-100 tw-to-slate-200 tw-w-24 tw-h-24 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-6">
                      <i class="pi pi-stethoscope tw-text-4xl tw-text-slate-400"></i>
                    </div>
                    <h3 class="tw-text-xl tw-font-semibold tw-text-slate-900 tw-mb-2">No consultations found</h3>
                    <p class="tw-text-slate-500 tw-mb-6 tw-max-w-md tw-mx-auto">This patient doesn't have any medical consultations yet. Start their first consultation to begin their medical record.</p>
                    <Button
                      label="Start First Consultation"
                      icon="pi pi-plus"
                      class="p-button-info tw-rounded-xl tw-px-8 tw-py-3 tw-font-semibold tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-200"
                      @click="openNewConsultation"
                      v-tooltip.top="'Start First Consultation'"
                    />
                  </div>
                </template>

                <template #loading>
                  <div class="tw-text-center tw-py-16 tw-px-8">
                    <div class="tw-bg-gradient-to-br tw-from-purple-100 tw-to-indigo-100 tw-w-20 tw-h-20 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-6">
                      <ProgressSpinner
                        class="tw-w-8 tw-h-8"
                        strokeWidth="4"
                        fill="transparent"
                        animationDuration="1.5s"
                      />
                    </div>
                    <h3 class="tw-text-lg tw-font-semibold tw-text-slate-900 tw-mb-2">Loading consultations...</h3>
                    <p class="tw-text-slate-500">Please wait while we fetch the consultation data</p>
                  </div>
                </template>
              </DataTable>
            </div>
          </div>
        </TabPanel>

        <!-- Appointment History Tab -->
        <TabPanel header="History" class="tw-p-0">
          <div class="tw-bg-white tw-rounded-2xl tw-shadow-xl tw-border tw-border-slate-200/60 tw-overflow-hidden tw-backdrop-blur-sm tw-mt-6">
            <div class="tw-bg-gradient-to-r tw-from-green-50 tw-to-green-100 tw-px-6 tw-py-4 tw-border-b tw-border-green-200">
              <div class="tw-flex tw-items-center tw-justify-between">
                <h3 class="tw-text-lg tw-font-semibold tw-text-green-800 tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-history tw-text-green-600"></i>
                  Appointment History
                </h3>
                <div class="tw-flex tw-items-center tw-gap-4">
                  <Badge :value="appointmentHistory.length.toString()" severity="success" class="tw-rounded-full" />
                  <Button
                    label="Schedule New"
                    icon="pi pi-plus"
                    severity="success"
                    class="p-button-success tw-rounded-xl tw-px-6 tw-py-2 tw-font-semibold"
                    @click="openNewAppointment"
                    v-tooltip.top="'Schedule New Appointment'"
                  />
                </div>
              </div>
            </div>
            <div class="tw-p-6">
              <DataTable
                :value="appointmentHistory"
                :loading="appointmentsLoading"
                dataKey="id"
                :paginator="true"
                :rows="10"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                currentPageReportTemplate="Showing {first} to {last} of {totalRecords} appointments"
                :rowsPerPageOptions="[10, 25, 50]"
                responsiveLayout="scroll"
                class="history-table tw-rounded-xl"
                selectionMode="single"
                @rowSelect="onAppointmentHistoryRowSelect"
                :rowHover="true"
              >
                <Column field="appointment_date" header="Date" :sortable="true">
                  <template #body="{ data }">
                    <div class="tw-font-medium tw-text-slate-900">
                      {{ formatDate(data.appointment_date) }}
                    </div>
                  </template>
                </Column>

                <Column field="appointment_time" header="Time" :sortable="true">
                  <template #body="{ data }">
                    <div class="tw-font-medium tw-text-slate-700">
                      {{ data.appointment_time }}
                    </div>
                  </template>
                </Column>

                <Column field="doctor_name" header="Doctor" :sortable="true">
                  <template #body="{ data }">
                    <div class="tw-flex tw-items-center tw-gap-3">
                      <Avatar
                        :label="data.doctor_name?.charAt(0)?.toUpperCase() || 'D'"
                        class="tw-bg-green-100 tw-text-green-600"
                        size="normal"
                        shape="circle"
                      />
                      <span class="tw-text-slate-700">{{ data.doctor_name }}</span>
                    </div>
                  </template>
                </Column>

                <Column field="specialization" header="Specialization">
                  <template #body="{ data }">
                    <Badge
                      :value="data.specialization"
                      severity="info"
                      class="tw-rounded-full tw-font-medium"
                    />
                  </template>
                </Column>

                <Column field="status" header="Status" :sortable="true">
                  <template #body="{ data }">
                    <Badge
                      :value="getStatusDisplayName(data.status)"
                      :severity="getStatusSeverity(data.status)"
                      class="tw-rounded-full tw-font-medium"
                    />
                  </template>
                </Column>

                <Column field="reason" header="Reason">
                  <template #body="{ data }">
                    <div class="tw-max-w-xs tw-truncate tw-text-slate-700">
                      {{ data.reason || 'N/A' }}
                    </div>
                  </template>
                </Column>

                <Column field="actions" header="Actions" headerStyle="width: 8rem">
                  <template #body="{ data }">
                    <Button
                      @click.stop="viewAppointment(data)"
                      icon="pi pi-eye"
                      class="p-button-rounded p-button-text p-button-success p-button-sm"
                      v-tooltip.top="'View Details'"
                    />
                  </template>
                </Column>

                <template #empty>
                  <div class="tw-text-center tw-py-16 tw-px-8">
                    <div class="tw-bg-gradient-to-br tw-from-slate-100 tw-to-slate-200 tw-w-24 tw-h-24 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-6">
                      <i class="pi pi-history tw-text-4xl tw-text-slate-400"></i>
                    </div>
                    <h3 class="tw-text-xl tw-font-semibold tw-text-slate-900 tw-mb-2">No appointment history found</h3>
                    <p class="tw-text-slate-500 tw-mb-6 tw-max-w-md tw-mx-auto">This patient doesn't have any appointment history yet. Schedule their first appointment to get started.</p>
                    <Button
                      label="Schedule First Appointment"
                      icon="pi pi-plus"
                      class="p-button-success tw-rounded-xl tw-px-8 tw-py-3 tw-font-semibold tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-200"
                      @click="openNewAppointment"
                      v-tooltip.top="'Schedule First Appointment'"
                    />
                  </div>
                </template>

                <template #loading>
                  <div class="tw-text-center tw-py-16 tw-px-8">
                    <div class="tw-bg-gradient-to-br tw-from-green-100 tw-to-emerald-100 tw-w-20 tw-h-20 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-6">
                      <ProgressSpinner
                        class="tw-w-8 tw-h-8"
                        strokeWidth="4"
                        fill="transparent"
                        animationDuration="1.5s"
                      />
                    </div>
                    <h3 class="tw-text-lg tw-font-semibold tw-text-slate-900 tw-mb-2">Loading appointment history...</h3>
                    <p class="tw-text-slate-500">Please wait while we fetch the appointment data</p>
                  </div>
                </template>
              </DataTable>
            </div>
          </div>
        </TabPanel>   
      </TabView>
    </div>

    <!-- Patient Edit Modal -->
    <PatientModel
      :show-modal="showEditModal"
      :spec-data="patient"
      @close="closeEditModal"
      @patientsUpdate="handlePatientUpdate"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { useToastr } from '../../Components/toster';
import PatientModel from '../../Components/PatientModel.vue';

// PrimeVue Components
import Card from 'primevue/card';
import Button from 'primevue/button';
import Avatar from 'primevue/avatar';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Badge from 'primevue/badge';
import Toolbar from 'primevue/toolbar';
import ProgressSpinner from 'primevue/progressspinner';
import Textarea from 'primevue/textarea';
import InputText from 'primevue/inputtext';
import Chip from 'primevue/chip';
import Dropdown from 'primevue/dropdown';
import Tooltip from 'primevue/tooltip';

// Register directives
const { default: TooltipDirective } = Tooltip;

const route = useRoute();
const router = useRouter();
const toaster = useToastr();

// State
const loading = ref(false);
const patient = ref(null);
const appointments = ref([]);
const consultations = ref([]);
const appointmentsLoading = ref(false);
const consultationsLoading = ref(false);
const showEditModal = ref(false);
const patientNotes = ref('');
const newAllergy = ref('');
const newMedication = ref('');
const selectedStatusFilter = ref(null);

// Status filter options
const statusFilterOptions = ref([
  { label: 'All Statuses', value: null },
  { label: 'Scheduled', value: 0 },
  { label: 'Confirmed', value: 1 },
  { label: 'Cancelled', value: 2 },
  { label: 'Pending', value: 3 },
  { label: 'Done', value: 4 },
  { label: 'On Working', value: 5 }
]);

const defaultAvatar = 'https://ui-avatars.com/api/?name=Patient&background=2563eb&color=fff&size=128';

// Get patient ID from route params
const patientId = computed(() => route.params.id);

// Computed
const recentAppointments = computed(() => {
  return appointments.value
    .sort((a, b) => new Date(b.appointment_date) - new Date(a.appointment_date))
    .slice(0, 5);
});

const filteredAppointments = computed(() => {
  if (selectedStatusFilter.value === null) {
    return appointments.value;
  }
  return appointments.value.filter(appointment => {
    const status = typeof appointment.status === 'object' ? appointment.status.value : appointment.status;
    return status === selectedStatusFilter.value;
  });
});

const appointmentHistory = computed(() => {
  return appointments.value
    .sort((a, b) => new Date(b.appointment_date) - new Date(a.appointment_date));
});

// Methods
const fetchPatientData = async () => {
  if (!patientId.value) return;
  
  try {
    loading.value = true;
    const response = await axios.get(`/api/patients/${patientId.value}`);
    patient.value = response.data.data || response.data;
    patientNotes.value = patient.value.notes || '';
  } catch (error) {
    console.error('Error fetching patient:', error);
    toaster.error('Failed to load patient data');
  } finally {
    loading.value = false;
  }
};

const fetchAppointments = async () => {
  if (!patientId.value) return;
  
  try {
    appointmentsLoading.value = true;
    const response = await axios.get(`/api/appointments/patient/${patientId.value}`);
    appointments.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error fetching appointments:', error);
    toaster.error('Failed to load appointments');
  } finally {
    appointmentsLoading.value = false;
  }
};

const fetchConsultations = async () => {
  if (!patientId.value) return;
  
  try {
    consultationsLoading.value = true;
    const response = await axios.get(`/api/consulations/${patientId.value}`);
    consultations.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error fetching consultations:', error);
    toaster.error('Failed to load consultations');
  } finally {
    consultationsLoading.value = false;
  }
};

const formatDate = (date) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

const formatDateTime = (date) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const getStatusSeverity = (status) => {
  // Handle object status (from AppointmentResource)
  if (typeof status === 'object' && status.color) {
    const colorMap = {
      'primary': 'info',
      'success': 'success', 
      'danger': 'danger',
      'warning': 'warning',
      'info': 'info',
      'secondary': 'secondary'
    };
    return colorMap[status.color] || 'info';
  }
  
  // Handle numeric status values
  const statusMap = {
    0: 'info',     // SCHEDULED
    1: 'success',  // CONFIRMED
    2: 'danger',   // CANCELLED
    3: 'warning',  // PENDING
    4: 'success',  // DONE
    5: 'warning'   // ON WORKING
  };
  
  // Handle string status values
  const stringStatusMap = {
    'scheduled': 'info',
    'confirmed': 'success',
    'completed': 'success',
    'cancelled': 'danger',
    'no-show': 'warning',
    'pending': 'warning',
    'done': 'success',
    'on working': 'warning'
  };
  
  if (typeof status === 'number') {
    return statusMap[status] || 'info';
  }
  
  return stringStatusMap[status?.toLowerCase()] || 'info';
};

const goBack = () => {
  router.go(-1);
};

const openEditModal = () => {
  showEditModal.value = true;
};

const closeEditModal = () => {
  showEditModal.value = false;
};

const handlePatientUpdate = () => {
  closeEditModal();
  fetchPatientData();
  toaster.success('Patient updated successfully');
};

const saveNotes = async () => {
  try {
    await axios.put(`/api/patients/${patientId.value}/notes`, {
      notes: patientNotes.value
    });
    toaster.success('Notes saved successfully');
  } catch (error) {
    console.error('Error saving notes:', error);
    toaster.error('Failed to save notes');
  }
};

const addAllergy = () => {
  if (newAllergy.value.trim()) {
    if (!patient.value.allergies) patient.value.allergies = [];
    patient.value.allergies.push(newAllergy.value.trim());
    newAllergy.value = '';
    updatePatientMedicalInfo();
  }
};

const removeAllergy = (allergy) => {
  if (patient.value.allergies) {
    patient.value.allergies = patient.value.allergies.filter(a => a !== allergy);
    updatePatientMedicalInfo();
  }
};

const addMedication = () => {
  if (newMedication.value.trim()) {
    if (!patient.value.medications) patient.value.medications = [];
    patient.value.medications.push(newMedication.value.trim());
    newMedication.value = '';
    updatePatientMedicalInfo();
  }
};

const removeMedication = (medication) => {
  if (patient.value.medications) {
    patient.value.medications = patient.value.medications.filter(m => m !== medication);
    updatePatientMedicalInfo();
  }
};

const updatePatientMedicalInfo = async () => {
  try {
    await axios.put(`/api/patients/${patientId.value}/medical-info`, {
      allergies: patient.value.allergies || [],
      medications: patient.value.medications || []
    });
    toaster.success('Medical information updated');
  } catch (error) {
    console.error('Error updating medical info:', error);
    toaster.error('Failed to update medical information');
  }
};

const openNewAppointment = () => {
  router.push({ 
    name: 'admin.appointments.create', 
    query: { 
      patientId: patientId.value,

      preselected: 'true'
    } 
  });
};

const openNewConsultation = () => {
  router.push({ 
    name: 'admin.consultations.create', 
    query: { patientId: patientId.value } 
  });
};

const viewAppointment = (appointment) => {
  router.push({ 
    name: 'admin.appointments.show', 
    params: { id: appointment.id } 
  });
};

const editAppointment = (appointment) => {
  router.push({ 
    name: 'admin.appointments.edit', 
    params: { id: appointment.id } 
  });
};

const viewConsultation = (consultation) => {
  router.push({ 
    name: 'admin.consultations.show', 
    params: { id: consultation.id } 
  });
};

const editConsultation = (consultation) => {
  router.push({ 
    name: 'admin.consultations.edit', 
    params: { id: consultation.id } 
  });
};

const viewConsultationDetails = (consultation) => {
  router.push({
    name: 'admin.consultations.old-consultation',
    params: { 
      patientId: patientId.value,
      consultationId: consultation.id 
    },
    query: {
      patient_name: `${patient.value.first_name} ${patient.value.last_name}`
    }
  });
};

const onConsultationRowSelect = (event) => {
  viewConsultationDetails(event.data);
};

const onAppointmentHistoryRowSelect = (event) => {
  viewAppointment(event.data);
};

const getStatusDisplayName = (status) => {
  if (typeof status === 'object' && status.name) {
    return status.name;
  }
  
  const statusMap = {
    0: 'SCHEDULED',
    1: 'CONFIRMED', 
    2: 'CANCELLED',
    3: 'PENDING',
    4: 'DONE',
    5: 'ON WORKING'
  };
  
  return statusMap[status] || status || 'Unknown';
};

const calculateAge = (dateOfBirth) => {
  if (!dateOfBirth) return 'N/A';
  const today = new Date();
  const birthDate = new Date(dateOfBirth);
  let age = today.getFullYear() - birthDate.getFullYear();
  const monthDiff = today.getMonth() - birthDate.getMonth();
  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
    age--;
  }
  return age;
};

const getBalanceClass = (balance) => {
  const amount = parseFloat(balance) || 0;
  if (amount > 0) return 'tw-text-green-600 tw-font-semibold';
  if (amount < 0) return 'tw-text-red-600 tw-font-semibold';
  return 'tw-text-gray-600';
};

// Lifecycle
onMounted(() => {
  fetchPatientData();
  fetchAppointments();
  fetchConsultations();
});
</script>

<style scoped>
/* Custom styles for the patient portal page */
.p-tabview .p-tabview-panels {
  padding: 0;
}

/* Custom card styling */
:deep(.p-card .p-card-body) {
  padding: 1rem;
}

:deep(.p-card .p-card-content) {
  padding: 0;
}

/* DataTable styling */
:deep(.p-datatable .p-datatable-tbody > tr > td) {
  padding: 0.75rem;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
  padding: 0.75rem;
  background-color: #f8fafc;
  font-weight: 600;
}

/* Clickable consultation rows */
:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background-color: #f1f5f9;
  cursor: pointer;
}

/* Status filter dropdown */
:deep(.p-dropdown) {
  min-width: 180px;
}
</style>