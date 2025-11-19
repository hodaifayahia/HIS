<template>
  <div class="tw-min-h-screen tw-bg-gray-50">
    <!-- Header -->
    <div class="tw-bg-white tw-shadow-sm tw-border-b">
      <div class="tw-max-w-7xl tw-mx-auto tw-px-4 sm:tw-px-6 lg:tw-px-8">
        <div class="tw-flex tw-items-center tw-justify-between tw-h-16">
          <div class="tw-flex tw-items-center tw-gap-4">
            <Button
              icon="pi pi-arrow-left"
              severity="secondary"
              outlined
              @click="goBack"
              class="tw-rounded-full"
            />
            <h1 class="tw-text-2xl tw-font-bold tw-text-gray-900">
              Patient Portal
            </h1>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="tw-flex tw-justify-center tw-items-center tw-h-96">
      <ProgressSpinner style="width: 50px; height: 50px" strokeWidth="6" />
      <span class="tw-ml-3 tw-text-lg">Loading patient data...</span>
    </div>

    <!-- Main Content -->
    <div v-else-if="patient" class="tw-max-w-7xl tw-mx-auto tw-px-4 sm:tw-px-6 lg:tw-px-8 tw-py-8">
      <!-- Patient Header Card -->
      <Card class="tw-mb-6 tw-shadow-lg">
        <template #content>
          <div class="tw-flex tw-items-center tw-justify-between">
            <div class="tw-flex tw-items-center tw-gap-4">
              <Avatar
                :image="patient.avatar || defaultAvatar"
                size="xlarge"
                shape="circle"
                class="tw-border-4 tw-border-blue-200"
              />
              <div>
                <h2 class="tw-text-2xl tw-font-bold tw-text-gray-800 tw-mb-1">
                  {{ patient.first_name }} {{ patient.last_name }}
                </h2>
                <div class="tw-flex tw-gap-4 tw-text-sm tw-text-gray-600">
                  <span><i class="pi pi-id-card tw-mr-1"></i>ID: {{ patient.Idnum }}</span>
                  <span><i class="pi pi-phone tw-mr-1"></i>{{ patient.phone }}</span>
                  <span><i class="pi pi-calendar tw-mr-1"></i>{{ formatDate(patient.dateOfBirth) }}</span>
                  <span><i class="pi pi-user tw-mr-1"></i>{{ patient.gender }}</span>
                </div>
              </div>
            </div>
            <div class="tw-flex tw-gap-2">
              <Button
                label="Edit Patient"
                icon="pi pi-pencil"
                severity="secondary"
                outlined
                @click="openEditModal"
                class="tw-rounded-full"
              />
            
            </div>
          </div>
        </template>
      </Card>

      <!-- Tabs for different sections -->
      <TabView class="tw-min-h-[800px]">
        <!-- Overview Tab -->
        <TabPanel header="Overview" class="tw-p-0">
          <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-2 tw-gap-6 tw-mt-6">
            <!-- Patient Information -->
            <Card class="tw-shadow-md">
              <template #header>
                <div class="tw-p-4 tw-bg-blue-50">
                  <h3 class="tw-text-lg tw-font-semibold tw-text-blue-800 tw-flex tw-items-center tw-gap-2">
                    <i class="pi pi-user"></i>
                    Patient Information
                  </h3>
                </div>
              </template>
              <template #content>
                <div class="tw-space-y-3">
                  <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-100">
                    <span class="tw-font-medium tw-text-gray-600">Full Name:</span>
                    <span class="tw-text-gray-800 tw-font-medium">{{ patient.first_name }} {{ patient.last_name }}</span>
                  </div>
                  <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-100">
                    <span class="tw-font-medium tw-text-gray-600">ID Number:</span>
                    <span class="tw-text-gray-800">{{ patient.Idnum || 'N/A' }}</span>
                  </div>
                  <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-100">
                    <span class="tw-font-medium tw-text-gray-600">Date of Birth:</span>
                    <span class="tw-text-gray-800">{{ formatDate(patient.dateOfBirth) }}</span>
                  </div>
                  <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-100">
                    <span class="tw-font-medium tw-text-gray-600">Age:</span>
                    <span class="tw-text-gray-800">{{ patient.age || calculateAge(patient.dateOfBirth) }} years</span>
                  </div>
                  <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-100">
                    <span class="tw-font-medium tw-text-gray-600">Gender:</span>
                    <span class="tw-text-gray-800">{{ patient.gender || 'N/A' }}</span>
                  </div>
                  <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-100">
                    <span class="tw-font-medium tw-text-gray-600">Phone:</span>
                    <span class="tw-text-gray-800">{{ patient.phone || 'N/A' }}</span>
                  </div>
                  <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-100">
                    <span class="tw-font-medium tw-text-gray-600">Parent/Guardian:</span>
                    <span class="tw-text-gray-800">{{ patient.Parent || 'N/A' }}</span>
                  </div>
                  <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-100">
                    <span class="tw-font-medium tw-text-gray-600">Weight:</span>
                    <span class="tw-text-gray-800">{{ patient.weight ? patient.weight + ' kg' : 'N/A' }}</span>
                  </div>
                  <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-100">
                    <span class="tw-font-medium tw-text-gray-600">Balance:</span>
                    <span class="tw-text-gray-800" :class="getBalanceClass(patient.balance)">
                      {{ patient.balance ? '$' + patient.balance : '$0.00' }}
                    </span>
                  </div>
                  <div class="tw-flex tw-justify-between tw-py-2">
                    <span class="tw-font-medium tw-text-gray-600">Created:</span>
                    <span class="tw-text-gray-800">{{ formatDate(patient.created_at) }}</span>
                  </div>
                </div>
              </template>
            </Card>

            <!-- Medical Information -->
            <Card class="tw-shadow-md">
              <template #header>
                <div class="tw-p-4 tw-bg-red-50">
                  <h3 class="tw-text-lg tw-font-semibold tw-text-red-800 tw-flex tw-items-center tw-gap-2">
                    <i class="pi pi-heart"></i>
                    Medical Information
                  </h3>
                </div>
              </template>
              <template #content>
                <div class="tw-space-y-4">
                  <!-- Allergies -->
                  <div>
                    <h4 class="tw-font-semibold tw-text-gray-700 tw-mb-2">Allergies</h4>
                    <div v-if="patient.allergies && patient.allergies.length > 0" class="tw-flex tw-flex-wrap tw-gap-2">
                      <Chip v-for="allergy in patient.allergies" :key="allergy.id" :label="allergy.name" class="tw-bg-red-100 tw-text-red-800" />
                    </div>
                    <p v-else class="tw-text-gray-500 tw-italic">No known allergies</p>
                  </div>
                  
                  <!-- Chronic Diseases -->
                  <div>
                    <h4 class="tw-font-semibold tw-text-gray-700 tw-mb-2">Chronic Diseases</h4>
                    <div v-if="patient.chronic_diseases && patient.chronic_diseases.length > 0" class="tw-flex tw-flex-wrap tw-gap-2">
                      <Chip v-for="disease in patient.chronic_diseases" :key="disease.id" :label="disease.name" class="tw-bg-orange-100 tw-text-orange-800" />
                    </div>
                    <p v-else class="tw-text-gray-500 tw-italic">No chronic diseases</p>
                  </div>
                  
                  <!-- Family Diseases -->
                  <div>
                    <h4 class="tw-font-semibold tw-text-gray-700 tw-mb-2">Family Medical History</h4>
                    <div v-if="patient.family_diseases && patient.family_diseases.length > 0" class="tw-flex tw-flex-wrap tw-gap-2">
                      <Chip v-for="disease in patient.family_diseases" :key="disease.id" :label="disease.name" class="tw-bg-purple-100 tw-text-purple-800" />
                    </div>
                    <p v-else class="tw-text-gray-500 tw-italic">No family medical history</p>
                  </div>
                  
                  <!-- Surgical History -->
                  <div>
                    <h4 class="tw-font-semibold tw-text-gray-700 tw-mb-2">Surgical History</h4>
                    <div v-if="patient.surgical && patient.surgical.length > 0" class="tw-space-y-2">
                      <div v-for="surgery in patient.surgical" :key="surgery.id" class="tw-p-2 tw-bg-blue-50 tw-rounded">
                        <p class="tw-font-medium">{{ surgery.name }}</p>
                        <p class="tw-text-sm tw-text-gray-600">{{ formatDate(surgery.date) }}</p>
                      </div>
                    </div>
                    <p v-else class="tw-text-gray-500 tw-italic">No surgical history</p>
                  </div>
                </div>
              </template>
            </Card>

            <!-- Recent Appointments -->
            <Card class="tw-shadow-md">
              <template #header>
                <div class="tw-p-4 tw-bg-green-50">
                  <h3 class="tw-text-lg tw-font-semibold tw-text-green-800 tw-flex tw-items-center tw-gap-2">
                    <i class="pi pi-calendar"></i>
                    Recent Appointments
                  </h3>
                </div>
              </template>
              <template #content>
                <div v-if="recentAppointments.length === 0" class="tw-text-center tw-py-8 tw-text-gray-500">
                  <i class="pi pi-calendar-times tw-text-3xl tw-mb-2"></i>
                  <p>No recent appointments</p>
                </div>
                <div v-else class="tw-space-y-3">
                  <div
                    v-for="appointment in recentAppointments.slice(0, 5)"
                    :key="appointment.id"
                    class="tw-p-3 tw-border tw-border-gray-200 tw-rounded-lg hover:tw-bg-gray-50 tw-cursor-pointer"
                    @click="viewAppointment(appointment)"
                  >
                    <div class="tw-flex tw-justify-between tw-items-start">
                      <div>
                        <p class="tw-font-medium tw-text-gray-800">{{ appointment.doctor_name }}</p>
                        <p class="tw-text-sm tw-text-gray-600">{{ formatDateTime(appointment.appointment_date) }}</p>
                      </div>
                      <Tag
                        :value="getStatusDisplayName(appointment.status)"
                        :severity="getStatusSeverity(appointment.status)"
                        class="tw-text-xs"
                      />
                    </div>
                  </div>
                </div>
              </template>
            </Card>
          </div>
        </TabPanel>

        <!-- Appointments Tab -->
        <TabPanel header="Appointments" class="tw-p-0">
          <Card class="tw-shadow-md tw-mt-6">
            <template #header>
              <Toolbar class="tw-border-0 tw-bg-white tw-p-4">
                <template #start>
                  <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-flex tw-items-center tw-gap-2">
                      <h3 class="tw-text-xl tw-font-semibold tw-text-gray-800">All Appointments</h3>
                      <Badge :value="filteredAppointments.length" severity="info" class="tw-rounded-full" />
                    </div>
                    <!-- Status Filter -->
                    <div class="tw-flex tw-items-center tw-gap-2">
                      <label class="tw-text-sm tw-font-medium tw-text-gray-600">Filter by Status:</label>
                      <Dropdown
                        v-model="selectedStatusFilter"
                        :options="statusFilterOptions"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="All Statuses"
                        class="tw-w-48"
                        showClear
                      />
                    </div>
                  </div>
                </template>
               
              </Toolbar>
            </template>
            <template #content>
              <DataTable
                :value="filteredAppointments"
                :loading="appointmentsLoading"
                stripedRows
                showGridlines
                responsiveLayout="scroll"
                :paginator="true"
                :rows="10"
                :rowsPerPageOptions="[5, 10, 20]"
                paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                currentPageReportTemplate="Showing {first} to {last} of {totalRecords} appointments"
                class="tw-w-full"
              >
                <Column field="appointment_date" header="Date" sortable>
                  <template #body="{ data }">
                    {{ formatDate(data.appointment_date) }}
                  </template>
                </Column>

                <Column field="appointment_time" header="Time" sortable>
                  <template #body="{ data }">
                    {{ data.appointment_time }}
                  </template>
                </Column>

                <Column field="doctor_name" header="Doctor" sortable>
                  <template #body="{ data }">
                    <div class="tw-flex tw-items-center tw-gap-2">
                      <Avatar :label="data.doctor_name?.charAt(0)" size="small" shape="circle" />
                      <span>{{ data.doctor_name }}</span>
                    </div>
                  </template>
                </Column>

                <Column field="specialization" header="Specialization">
                  <template #body="{ data }">
                    {{ data }}
                    <Tag :value="data.specialization.name" severity="info" class="tw-rounded-full" />
                  </template>
                </Column>

                <Column field="status" header="Status" sortable>
                  <template #body="{ data }">
                    <Tag
                      :value="getStatusDisplayName(data.status)"
                      :severity="getStatusSeverity(data.status)"
                      class="tw-font-medium"
                    />
                  </template>
                </Column>

              </DataTable>
            </template>
          </Card>
        </TabPanel>

        <!-- Consultations Tab -->
        <TabPanel header="Consultations" class="tw-p-0">
          <Card class="tw-shadow-md tw-mt-6">
            <template #header>
              <Toolbar class="tw-border-0 tw-bg-white tw-p-4">
                <template #start>
                  <div class="tw-flex tw-items-center tw-gap-2">
                    <h3 class="tw-text-xl tw-font-semibold tw-text-gray-800">Medical Consultations</h3>
                    <Badge :value="consultations.length" severity="info" class="tw-rounded-full" />
                  </div>
                </template>
              </Toolbar>
            </template>
            <template #content>
              <DataTable
                :value="consultations"
                :loading="consultationsLoading"
                stripedRows
                showGridlines
                responsiveLayout="scroll"
                :paginator="true"
                :rows="10"
                :rowsPerPageOptions="[5, 10, 20]"
                paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                currentPageReportTemplate="Showing {first} to {last} of {totalRecords} consultations"
                class="tw-w-full tw-cursor-pointer"
                selectionMode="single"
                @rowSelect="onConsultationRowSelect"
                :rowHover="true"
              >
                <Column field="consultation_date" header="Date" sortable>
                  <template #body="{ data }">
                    {{ formatDate(data.consultation_date) }}
                  </template>
                </Column>

                <Column field="doctor_name" header="Doctor" sortable>
                  <template #body="{ data }">
                    <div class="tw-flex tw-items-center tw-gap-2">
                      <Avatar :label="data.doctor_name?.charAt(0)" size="small" shape="circle" />
                      <span>{{ data.doctor_name }}</span>
                    </div>
                  </template>
                </Column>

                <Column field="diagnosis" header="Diagnosis">
                  <template #body="{ data }">
                    <span class="tw-text-gray-700">{{ data.diagnosis || 'N/A' }}</span>
                  </template>
                </Column>

                <Column field="treatment" header="Treatment">
                  <template #body="{ data }">
                    <span class="tw-text-gray-700">{{ data.treatment || 'N/A' }}</span>
                  </template>
                </Column>

                <Column header="Actions">
                  <template #body="{ data }">
                    <div class="tw-flex tw-gap-2">
                      <Button
                        icon="pi pi-eye"
                        severity="info"
                        outlined
                        size="small"
                        @click="viewConsultationDetails(data)"
                        v-tooltip.top="'View Details'"
                      />
                    </div>
                  </template>
                </Column>
              </DataTable>
            </template>
          </Card>
        </TabPanel>

        <!-- Appointment History Tab -->
        <TabPanel header="History" class="tw-p-0">
          <Card class="tw-shadow-md tw-mt-6">
            <template #header>
              <Toolbar class="tw-border-0 tw-bg-white tw-p-4">
                <template #start>
                  <div class="tw-flex tw-items-center tw-gap-2">
                    <h3 class="tw-text-xl tw-font-semibold tw-text-gray-800">Appointment History</h3>
                    <Badge :value="appointmentHistory.length" severity="info" class="tw-rounded-full" />
                  </div>
                </template>
              </Toolbar>
            </template>
            <template #content>
              <DataTable
                :value="appointmentHistory"
                :loading="appointmentsLoading"
                stripedRows
                showGridlines
                responsiveLayout="scroll"
                :paginator="true"
                :rows="10"
                :rowsPerPageOptions="[5, 10, 20]"
                paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                currentPageReportTemplate="Showing {first} to {last} of {totalRecords} appointments"
                class="tw-w-full"
              >
                <Column field="appointment_date" header="Date" sortable>
                  <template #body="{ data }">
                    {{ formatDate(data.appointment_date) }}
                  </template>
                </Column>

                <Column field="appointment_time" header="Time" sortable>
                  <template #body="{ data }">
                    {{ data.appointment_time }}
                  </template>
                </Column>

                <Column field="doctor_name" header="Doctor" sortable>
                  <template #body="{ data }">
                    <div class="tw-flex tw-items-center tw-gap-2">
                      <Avatar :label="data.doctor_name?.charAt(0)" size="small" shape="circle" />
                      <span>{{ data.doctor_name }}</span>
                    </div>
                  </template>
                </Column>

                <Column field="specialization" header="Specialization">
                  <template #body="{ data }">
                    <Tag :value="data.specialization" severity="info" class="tw-rounded-full" />
                  </template>
                </Column>

                <Column field="status" header="Status" sortable>
                  <template #body="{ data }">
                    <Tag
                      :value="getStatusDisplayName(data.status)"
                      :severity="getStatusSeverity(data.status)"
                      class="tw-font-medium"
                    />
                  </template>
                </Column>

                <Column field="reason" header="Reason">
                  <template #body="{ data }">
                    <span class="tw-text-gray-700">{{ data.reason || 'N/A' }}</span>
                  </template>
                </Column>
              </DataTable>
            </template>
          </Card>
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