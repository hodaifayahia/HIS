<template>
  <Dialog
    :visible="show"
    modal
    :header="`Patient Portal - ${patient?.first_name} ${patient?.last_name}`"
    :style="{ width: '95vw', height: '90vh' }"
    :breakpoints="{ '1199px': '95vw', '575px': '95vw' }"
    @update:visible="closePortal"
    @hide="closePortal"
    class="tw-font-sans"
    :maximizable="true"
  >
    <div v-if="loading" class="tw-flex tw-justify-center tw-items-center tw-h-96">
      <ProgressSpinner style="width: 50px; height: 50px" strokeWidth="6" />
      <span class="tw-ml-3 tw-text-lg">Loading patient data...</span>
    </div>

    <div v-else-if="patient" class="tw-h-full tw-flex tw-flex-col">
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
              <Button
                label="New Appointment"
                icon="pi pi-plus"
                severity="primary"
                @click="openNewAppointment"
                class="tw-rounded-full"
              />
            </div>
          </div>
        </template>
      </Card>

      <!-- Tabs for different sections -->
      <TabView class="tw-flex-1">
        <!-- Overview Tab -->
        <TabPanel header="Overview" class="tw-p-0">
          <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-3 tw-gap-6 tw-h-full">
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
                  <div class="tw-flex tw-justify-between">
                    <span class="tw-font-medium tw-text-gray-600">Parent:</span>
                    <span class="tw-text-gray-800">{{ patient.Parent || 'N/A' }}</span>
                  </div>
                  <div class="tw-flex tw-justify-between">
                    <span class="tw-font-medium tw-text-gray-600">Email:</span>
                    <span class="tw-text-gray-800">{{ patient.email || 'N/A' }}</span>
                  </div>
                  <div class="tw-flex tw-justify-between">
                    <span class="tw-font-medium tw-text-gray-600">Address:</span>
                    <span class="tw-text-gray-800">{{ patient.address || 'N/A' }}</span>
                  </div>
                  <div class="tw-flex tw-justify-between">
                    <span class="tw-font-medium tw-text-gray-600">City:</span>
                    <span class="tw-text-gray-800">{{ patient.city || 'N/A' }}</span>
                  </div>
                  <div class="tw-flex tw-justify-between">
                    <span class="tw-font-medium tw-text-gray-600">Insurance:</span>
                    <span class="tw-text-gray-800">{{ patient.insurance || 'N/A' }}</span>
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
                    class="tw-p-3 tw-border tw-border-gray-200 tw-rounded-lg tw-hover:bg-gray-50 tw-cursor-pointer"
                    @click="viewAppointment(appointment)"
                  >
                    <div class="tw-flex tw-justify-between tw-items-start">
                      <div>
                        <p class="tw-font-medium tw-text-gray-800">{{ appointment.doctor_name }}</p>
                        <p class="tw-text-sm tw-text-gray-600">{{ formatDateTime(appointment.appointment_date) }}</p>
                      </div>
                      <Tag
                        :value="appointment.status"
                        :severity="getStatusSeverity(appointment.status)"
                        class="tw-text-xs"
                      />
                    </div>
                  </div>
                </div>
              </template>
            </Card>

            <!-- Medical Notes -->
            <Card class="tw-shadow-md">
              <template #header>
                <div class="tw-p-4 tw-bg-purple-50">
                  <h3 class="tw-text-lg tw-font-semibold tw-text-purple-800 tw-flex tw-items-center tw-gap-2">
                    <i class="pi pi-file-edit"></i>
                    Medical Notes
                  </h3>
                </div>
              </template>
              <template #content>
                <div class="tw-space-y-3">
                  <Textarea
                    v-model="patientNotes"
                    rows="6"
                    placeholder="Add medical notes..."
                    class="tw-w-full"
                  />
                  <Button
                    label="Save Notes"
                    icon="pi pi-save"
                    size="small"
                    @click="saveNotes"
                    class="tw-w-full"
                  />
                </div>
              </template>
            </Card>
          </div>
        </TabPanel>

        <!-- Appointments Tab -->
        <TabPanel header="Appointments" class="tw-p-0">
          <Card class="tw-shadow-md tw-h-full">
            <template #header>
              <Toolbar class="tw-border-0 tw-bg-white tw-p-4">
                <template #start>
                  <div class="tw-flex tw-items-center tw-gap-2">
                    <h3 class="tw-text-xl tw-font-semibold tw-text-gray-800">All Appointments</h3>
                    <Badge :value="appointments.length" severity="info" class="tw-rounded-full" />
                  </div>
                </template>
                <template #end>
                  <Button
                    label="New Appointment"
                    icon="pi pi-plus"
                    severity="primary"
                    @click="openNewAppointment"
                    class="tw-rounded-full"
                  />
                </template>
              </Toolbar>
            </template>
            <template #content>
              <DataTable
                :value="appointments"
                :loading="appointmentsLoading"
                stripedRows
                showGridlines
                responsiveLayout="scroll"
                :paginator="true"
                :rows="10"
                :rowsPerPageOptions="[5, 10, 20]"
                paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
                currentPageReportTemplate="{first} to {last} of {totalRecords}"
                class="tw-rounded-lg"
              >
                <template #empty>
                  <div class="tw-text-center tw-py-8">
                    <i class="pi pi-calendar-times tw-text-4xl tw-text-gray-400 tw-mb-4"></i>
                    <p class="tw-text-gray-600 tw-text-lg">No appointments found</p>
                  </div>
                </template>

                <Column field="appointment_date" header="Date & Time" sortable>
                  <template #body="{ data }">
                    <div class="tw-flex tw-items-center tw-gap-2">
                      <i class="pi pi-calendar tw-text-blue-500"></i>
                      <span>{{ formatDateTime(data.appointment_date) }}</span>
                    </div>
                  </template>
                </Column>

                <Column field="doctor_name" header="Doctor" sortable>
                  <template #body="{ data }">
                    <div class="tw-flex tw-items-center tw-gap-2">
                      <i class="pi pi-user-md tw-text-green-500"></i>
                      <span class="tw-font-medium">{{ data.doctor_name }}</span>
                    </div>
                  </template>
                </Column>

                <Column field="specialization" header="Specialization" sortable>
                  <template #body="{ data }">
                    <Tag :value="data.specialization" severity="info" class="tw-rounded-full" />
                  </template>
                </Column>

                <Column field="status" header="Status" sortable>
                  <template #body="{ data }">
                    <Tag
                      :value="data.status"
                      :severity="getStatusSeverity(data.status)"
                      class="tw-font-medium"
                    />
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
                        @click="viewAppointment(data)"
                        v-tooltip.top="'View Details'"
                      />
                      <Button
                        icon="pi pi-pencil"
                        severity="secondary"
                        outlined
                        size="small"
                        @click="editAppointment(data)"
                        v-tooltip.top="'Edit Appointment'"
                      />
                    </div>
                  </template>
                </Column>
              </DataTable>
            </template>
          </Card>
        </TabPanel>

        <!-- Consultations Tab -->
        <TabPanel header="Consultations" class="tw-p-0">
          <Card class="tw-shadow-md tw-h-full">
            <template #header>
              <Toolbar class="tw-border-0 tw-bg-white tw-p-4">
                <template #start>
                  <div class="tw-flex tw-items-center tw-gap-2">
                    <h3 class="tw-text-xl tw-font-semibold tw-text-gray-800">Medical Consultations</h3>
                    <Badge :value="consultations.length" severity="info" class="tw-rounded-full" />
                  </div>
                </template>
                <template #end>
                  <Button
                    label="New Consultation"
                    icon="pi pi-plus"
                    severity="primary"
                    @click="openNewConsultation"
                    class="tw-rounded-full"
                  />
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
                currentPageReportTemplate="{first} to {last} of {totalRecords}"
                class="tw-rounded-lg"
              >
                <template #empty>
                  <div class="tw-text-center tw-py-8">
                    <i class="pi pi-stethoscope tw-text-4xl tw-text-gray-400 tw-mb-4"></i>
                    <p class="tw-text-gray-600 tw-text-lg">No consultations found</p>
                  </div>
                </template>

                <Column field="consultation_date" header="Date" sortable>
                  <template #body="{ data }">
                    <div class="tw-flex tw-items-center tw-gap-2">
                      <i class="pi pi-calendar tw-text-blue-500"></i>
                      <span>{{ formatDate(data.consultation_date) }}</span>
                    </div>
                  </template>
                </Column>

                <Column field="doctor_name" header="Doctor" sortable>
                  <template #body="{ data }">
                    <div class="tw-flex tw-items-center tw-gap-2">
                      <i class="pi pi-user-md tw-text-green-500"></i>
                      <span class="tw-font-medium">{{ data.doctor_name }}</span>
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
                        @click="viewConsultation(data)"
                        v-tooltip.top="'View Details'"
                      />
                      <Button
                        icon="pi pi-pencil"
                        severity="secondary"
                        outlined
                        size="small"
                        @click="editConsultation(data)"
                        v-tooltip.top="'Edit Consultation'"
                      />
                    </div>
                  </template>
                </Column>
              </DataTable>
            </template>
          </Card>
        </TabPanel>

        <!-- Medical History Tab -->
        <TabPanel header="Medical History" class="tw-p-0">
          <Card class="tw-shadow-md tw-h-full">
            <template #content>
              <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-2 tw-gap-6">
                <!-- Allergies -->
                <div>
                  <h4 class="tw-text-lg tw-font-semibold tw-text-gray-800 tw-mb-4 tw-flex tw-items-center tw-gap-2">
                    <i class="pi pi-exclamation-triangle tw-text-red-500"></i>
                    Allergies
                  </h4>
                  <div class="tw-space-y-2">
                    <Chip
                      v-for="allergy in patient.allergies || []"
                      :key="allergy"
                      :label="allergy"
                      class="tw-mr-2 tw-mb-2"
                      removable
                      @remove="removeAllergy(allergy)"
                    />
                    <div class="tw-flex tw-gap-2 tw-mt-3">
                      <InputText
                        v-model="newAllergy"
                        placeholder="Add new allergy"
                        class="tw-flex-1"
                        @keyup.enter="addAllergy"
                      />
                      <Button
                        icon="pi pi-plus"
                        severity="secondary"
                        @click="addAllergy"
                      />
                    </div>
                  </div>
                </div>

                <!-- Medications -->
                <div>
                  <h4 class="tw-text-lg tw-font-semibold tw-text-gray-800 tw-mb-4 tw-flex tw-items-center tw-gap-2">
                    <i class="pi pi-heart tw-text-blue-500"></i>
                    Current Medications
                  </h4>
                  <div class="tw-space-y-2">
                    <Chip
                      v-for="medication in patient.medications || []"
                      :key="medication"
                      :label="medication"
                      class="tw-mr-2 tw-mb-2"
                      removable
                      @remove="removeMedication(medication)"
                    />
                    <div class="tw-flex tw-gap-2 tw-mt-3">
                      <InputText
                        v-model="newMedication"
                        placeholder="Add new medication"
                        class="tw-flex-1"
                        @keyup.enter="addMedication"
                      />
                      <Button
                        icon="pi pi-plus"
                        severity="secondary"
                        @click="addMedication"
                      />
                    </div>
                  </div>
                </div>
              </div>
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
  </Dialog>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import axios from 'axios';
import { useToastr } from '../toster';
import PatientModel from '../PatientModel.vue';
import { useRouter } from 'vue-router';

// PrimeVue Components
import Dialog from 'primevue/dialog';
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

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  patientId: {
    type: [String, Number],
    required: true
  }
});

const emit = defineEmits(['close', 'patientUpdated']);

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

const defaultAvatar = 'https://ui-avatars.com/api/?name=Patient&background=2563eb&color=fff&size=128';

// Computed
const recentAppointments = computed(() => {
  return appointments.value
    .sort((a, b) => new Date(b.appointment_date) - new Date(a.appointment_date))
    .slice(0, 5);
});

// Methods
const fetchPatientData = async () => {
  if (!props.patientId) return;
  
  try {
    loading.value = true;
    const response = await axios.get(`/api/patients/${props.patientId}`);
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
  if (!props.patientId) return;
  
  try {
    appointmentsLoading.value = true;
    const response = await axios.get(`/api/patients/${props.patientId}/appointments`);
    appointments.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error fetching appointments:', error);
    toaster.error('Failed to load appointments');
  } finally {
    appointmentsLoading.value = false;
  }
};

const fetchConsultations = async () => {
  if (!props.patientId) return;
  
  try {
    consultationsLoading.value = true;
    const response = await axios.get(`/api/patients/${props.patientId}/consultations`);
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
  const statusMap = {
    'scheduled': 'info',
    'confirmed': 'success',
    'completed': 'success',
    'cancelled': 'danger',
    'no-show': 'warning',
    'pending': 'warning'
  };
  return statusMap[status?.toLowerCase()] || 'info';
};

const closePortal = () => {
  emit('close');
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
  emit('patientUpdated');
  toaster.success('Patient updated successfully');
};

const saveNotes = async () => {
  try {
    await axios.put(`/api/patients/${props.patientId}/notes`, {
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
    await axios.put(`/api/patients/${props.patientId}/medical-info`, {
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
    query: { patientId: props.patientId } 
  });
};

const openNewConsultation = () => {
  router.push({ 
    name: 'admin.consultations.create', 
    query: { patientId: props.patientId } 
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

// Watchers
watch(() => props.show, (newVal) => {
  if (newVal && props.patientId) {
    fetchPatientData();
    fetchAppointments();
    fetchConsultations();
  }
});

watch(() => props.patientId, (newVal) => {
  if (newVal && props.show) {
    fetchPatientData();
    fetchAppointments();
    fetchConsultations();
  }
});

// Lifecycle
onMounted(() => {
  if (props.show && props.patientId) {
    fetchPatientData();
    fetchAppointments();
    fetchConsultations();
  }
});
</script>

<style scoped>
/* Custom styles for the patient portal */
.p-dialog .p-dialog-content {
  padding: 1.5rem;
}

.p-tabview .p-tabview-panels {
  padding: 0;
  height: calc(100% - 3rem);
}

.p-tabview .p-tabview-panel {
  height: 100%;
}

/* Ensure proper scrolling in tab content */
:deep(.p-tabview-panel) {
  height: 100%;
  overflow-y: auto;
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
</style>