<template>
  <div class="tw-min-h-screen tw-bg-gray-50 tw-p-6">
    <!-- Loading State -->
    <div v-if="loading" class="tw-flex tw-justify-center tw-items-center tw-py-12">
      <ProgressSpinner />
    </div>

    <div v-else-if="admission">
      <!-- Header Section -->
      <div class="tw-bg-white tw-rounded-lg tw-shadow-sm tw-p-6 tw-mb-6">
        <div class="tw-flex tw-justify-between tw-items-start">
          <div>
            <h1 class="tw-text-2xl tw-font-bold tw-text-gray-900 tw-mb-2">Admission Details</h1>
            <p class="tw-text-gray-600 tw-text-sm">Patient: <span class="tw-font-semibold">{{ admission.patient.name }}</span></p>
          </div>
          <div class="tw-flex tw-gap-3">
            <Button
              as-child
              severity="warning"
              size="small"
              icon="pi pi-pencil"
            >
              <router-link :to="`/admissions/${admission.id}/edit`">
                Edit
              </router-link>
            </Button>
            <Button
              v-if="admission.can_discharge"
              @click="dischargeAdmission"
              severity="success"
              size="small"
              icon="pi pi-door-open"
            >
              Discharge
            </Button>
            <Button
              v-if="admission && !admission.file_number_verified"
              @click="verifyFileNumber"
              severity="success"
              size="small"
              icon="pi pi-check"
            >
              Verify File Number
            </Button>
            <Button
              as-child
              severity="secondary"
              size="small"
              icon="pi pi-arrow-left"
            >
              <router-link to="/admissions">
                Back
              </router-link>
            </Button>
          </div>
        </div>
      </div>

      <!-- Main Info Cards -->
      <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-3 tw-gap-6 tw-mb-6">
        <!-- Admission Info Card -->
        <div class="lg:tw-col-span-2">
          <Card class="tw-shadow-sm">
            <template #content>
              <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
                <div>
                  <p class="tw-font-semibold tw-text-gray-700">Patient:</p>
                  <p class="tw-text-lg tw-font-medium tw-text-gray-900">{{ admission.patient.name }}</p>
                  <p class="tw-text-sm tw-text-gray-600">{{ admission.patient.phone }}</p>
                </div>
                <div>
                  <p class="tw-font-semibold tw-text-gray-700">Doctor:</p>
                  <p class="tw-text-lg tw-font-medium tw-text-gray-900">{{ admission.doctor?.name || 'Not Assigned' }}</p>
                </div>
              </div>
              <Divider />
              <div class="tw-grid tw-grid-cols-2 md:tw-grid-cols-4 tw-gap-4">
                <div>
                  <p class="tw-font-semibold tw-text-gray-700">Type:</p>
                  <Tag :severity="admission.type === 'surgery' ? 'warning' : 'success'" class="tw-font-medium">
                    {{ admission.type_label }}
                  </Tag>
                </div>
                <div>
                  <p class="tw-font-semibold tw-text-gray-700">Status:</p>
                  <Tag :severity="getStatusSeverity(admission.status)" class="tw-font-medium">
                    {{ admission.status_label }}
                  </Tag>
                </div>
                <div>
                  <p class="tw-font-semibold tw-text-gray-700">Admitted:</p>
                  <p class="tw-text-sm tw-text-gray-900">{{ formatDate(admission.admitted_at) }}</p>
                </div>
                <div>
                  <p class="tw-font-semibold tw-text-gray-700">Duration:</p>
                  <p class="tw-text-sm tw-text-gray-900">{{ admission.duration_days }} days</p>
                </div>
              </div>
              <Divider />
              <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
                <div>
                  <p class="tw-font-semibold tw-text-gray-700">Documents Verified:</p>
                  <Tag :severity="admission.documents_verified ? 'success' : 'warning'" class="tw-font-medium">
                    {{ admission.documents_verified ? 'Yes' : 'No' }}
                  </Tag>
                </div>
                <div>
                  <p class="tw-font-semibold tw-text-gray-700">Created By:</p>
                  <p class="tw-text-sm tw-text-gray-900">{{ admission.creator?.name || 'N/A' }}</p>
                </div>
              </div>
            </template>
          </Card>
        </div>

        <!-- Statistics Card -->
        <div class="tw-space-y-4">
          <Card class="tw-shadow-sm">
            <template #content>
              <div class="tw-flex tw-items-center tw-gap-4">
                <div class="tw-w-12 tw-h-12 tw-bg-cyan-100 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                  <i class="pi pi-file-pdf tw-text-cyan-600 tw-text-xl"></i>
                </div>
                <div>
                  <h6 class="tw-text-gray-600 tw-text-sm tw-mb-1">Documents</h6>
                  <h3 class="tw-text-2xl tw-font-bold tw-text-gray-900 tw-m-0">{{ admission.documents_count || 0 }}</h3>
                </div>
              </div>
            </template>
          </Card>
        </div>
      </div>

      <!-- Tabs Section -->
      <Card class="tw-shadow-sm">
        <template #content>
          <TabView class="tw-border-0">
            <!-- Documents Tab -->
            <TabPanel header="Documents" :header-icon="'pi pi-file-pdf'">
              <div class="tw-space-y-6">
                <div class="tw-flex tw-justify-between tw-items-center">
                  <h6 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-m-0">Document Management</h6>
                  <Button
                    @click="showUploadModal = true"
                    severity="primary"
                    size="small"
                    icon="pi pi-cloud-upload"
                  >
                    Upload Document
                  </Button>
                </div>

                <!-- Documents Grid -->
                <div v-if="admission.documents && admission.documents.length > 0" class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
                  <Card
                    v-for="doc in admission.documents"
                    :key="doc.id"
                    class="tw-shadow-sm tw-transition-all tw-duration-200 hover:tw-shadow-md"
                  >
                    <template #content>
                      <div class="tw-flex tw-justify-between tw-items-start tw-mb-3">
                        <div class="tw-flex tw-items-center tw-gap-3">
                          <div class="tw-w-10 tw-h-10 tw-bg-red-100 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                            <i class="pi pi-file-pdf tw-text-red-600"></i>
                          </div>
                          <div>
                            <h6 class="tw-font-semibold tw-text-gray-900 tw-m-0">{{ getDocumentTypeLabel(doc.type) }}</h6>
                            <p class="tw-text-xs tw-text-gray-500 tw-m-0">{{ formatDate(doc.created_at) }}</p>
                          </div>
                        </div>
                        <div class="tw-flex tw-gap-2">
                          <Button
                            @click="downloadDocument(doc.id, doc.file_name)"
                            severity="info"
                            size="small"
                            outlined
                            icon="pi pi-download"
                            v-tooltip="'Download'"
                          />
                          <Button
                            @click="deleteDocument(doc.id)"
                            severity="danger"
                            size="small"
                            outlined
                            icon="pi pi-trash"
                            v-tooltip="'Delete'"
                          />
                        </div>
                      </div>
                      <div class="tw-space-y-2">
                        <p class="tw-text-sm tw-text-gray-600 tw-m-0">{{ doc.description || 'No description' }}</p>
                        <div class="tw-flex tw-gap-2 tw-flex-wrap">
                          <Tag :severity="doc.verified ? 'success' : 'warning'" class="tw-text-xs">
                            {{ doc.verified ? 'Verified' : 'Pending' }}
                          </Tag>
                          <Tag severity="info" class="tw-text-xs">
                            {{ formatFileSize(doc.file_size) }}
                          </Tag>
                        </div>
                      </div>
                    </template>
                  </Card>
                </div>
                <div v-else class="tw-text-center tw-py-12 tw-text-gray-500">
                  <i class="pi pi-file-pdf tw-text-4xl tw-mb-4 tw-block"></i>
                  <p class="tw-text-lg tw-mb-2">No documents uploaded yet</p>
                  <p class="tw-text-sm">Upload documents to manage patient records</p>
                </div>
              </div>
            </TabPanel>

            <!-- Treatments Tab -->
            <TabPanel>
              <template #header>
                <i class="pi pi-calendar tw-mr-2"></i>
                <span>Treatments</span>
              </template>
              <div class="tw-space-y-4">
                <div class="tw-flex tw-justify-between tw-items-center">
                  <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-m-0">Patient Treatments</h3>
                  <Button
                    icon="pi pi-plus"
                    label="Add Treatment"
                    @click="openAddTreatmentModal"
                    severity="success"
                    size="small"
                  />
                </div>

                <DataTable
                  :value="treatments"
                  :loading="loadingTreatments"
                  striped-rows
                  paginator
                  :rows="10"
                  data-key="id"
                  class="tw-text-sm"
                  empty-message="No treatments recorded yet"
                >
                  <Column header="Entered At" style="width: 15%">
                    <template #body="slotProps">
                      <div class="tw-flex tw-items-center">
                        <i class="pi pi-sign-in tw-mr-2 tw-text-green-600"></i>
                        {{ formatDate(slotProps.data.entered_at) }}
                      </div>
                    </template>
                  </Column>

                  <Column header="Exited At" style="width: 15%">
                    <template #body="slotProps">
                      <div v-if="slotProps.data.exited_at" class="tw-flex tw-items-center">
                        <i class="pi pi-sign-out tw-mr-2 tw-text-red-600"></i>
                        {{ formatDate(slotProps.data.exited_at) }}
                      </div>
                      <Tag v-else severity="info" class="tw-text-xs">In Progress</Tag>
                    </template>
                  </Column>

                  <Column header="Duration" style="width: 12%">
                    <template #body="slotProps">
                      <span v-if="slotProps.data.duration_minutes">
                        {{ Math.floor(slotProps.data.duration_minutes / 60) }}h {{ slotProps.data.duration_minutes % 60 }}m
                      </span>
                      <span v-else class="tw-text-gray-400">-</span>
                    </template>
                  </Column>

                  <Column field="doctor.name" header="Doctor" style="width: 20%">
                    <template #body="slotProps">
                      <div v-if="slotProps.data.doctor" class="tw-flex tw-items-center">
                        <i class="pi pi-user tw-mr-2 tw-text-blue-600"></i>
                        {{ slotProps.data.doctor.name }}
                      </div>
                      <span v-else class="tw-text-gray-400">-</span>
                    </template>
                  </Column>

                  <Column field="prestation.name" header="Prestation" style="width: 20%">
                    <template #body="slotProps">
                      <div v-if="slotProps.data.prestation">
                        {{ slotProps.data.prestation.name }}
                        <span v-if="slotProps.data.prestation.code" class="tw-text-gray-500 tw-text-xs">
                          ({{ slotProps.data.prestation.code }})
                        </span>
                      </div>
                      <span v-else class="tw-text-gray-400">-</span>
                    </template>
                  </Column>

                  <Column field="notes" header="Notes" style="width: 23%">
                    <template #body="slotProps">
                      <span :title="slotProps.data.notes" class="tw-truncate tw-block">
                        {{ slotProps.data.notes || '-' }}
                      </span>
                    </template>
                  </Column>

                  <Column header="Actions" style="width: 10%" frozen alignFrozen="right">
                    <template #body="slotProps">
                      <div class="tw-flex tw-gap-2">
                        <Button
                          icon="pi pi-pencil"
                          @click="editTreatment(slotProps.data)"
                          severity="warning"
                          size="small"
                          text
                          rounded
                          v-tooltip="'Edit'"
                        />
                        <Button
                          icon="pi pi-trash"
                          @click="deleteTreatment(slotProps.data)"
                          severity="danger"
                          size="small"
                          text
                          rounded
                          v-tooltip="'Delete'"
                        />
                      </div>
                    </template>
                  </Column>
                </DataTable>
              </div>
            </TabPanel>
          </TabView>
        </template>
      </Card>
    </div>

    <div v-else class="tw-bg-red-50 tw-border tw-border-red-200 tw-rounded-lg tw-p-4 tw-text-red-800">
      <i class="pi pi-exclamation-triangle tw-mr-2"></i>
      Admission not found
    </div>

    <!-- Upload Document Dialog -->
    <Dialog
      :visible="showUploadModal"
      @update:visible="showUploadModal = $event"
      modal
      header="Upload Document"
      :style="{ width: '600px' }"
      :closable="true"
    >
      <div class="tw-space-y-4">
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Document Type</label>
          <Dropdown
            v-model="newDocument.type"
            :options="documentTypes"
            option-label="label"
            option-value="value"
            placeholder="Select document type"
            class="tw-w-full"
          />
        </div>

        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Upload File</label>
          <div
            class="tw-border-2 tw-border-dashed tw-border-gray-300 tw-rounded-lg tw-p-8 tw-text-center tw-cursor-pointer tw-transition-colors hover:tw-border-blue-400 hover:tw-bg-blue-50"
            :class="{ 'tw-border-blue-400 tw-bg-blue-50': dragOver }"
            @click="fileInput?.click()"
            @dragover.prevent="dragOver = true"
            @dragleave.prevent="dragOver = false"
            @drop.prevent="handleFileDrop"
          >
            <input
              ref="fileInput"
              type="file"
              @change="handleFileUpload"
              class="tw-hidden"
              accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
            />
            <i class="pi pi-cloud-upload tw-text-3xl tw-text-blue-500 tw-mb-2 tw-block"></i>
            <p v-if="!newDocument.file" class="tw-text-gray-600 tw-mb-0">
              Drop file here or click to browse
            </p>
            <p v-else class="tw-text-green-600 tw-mb-0 tw-font-medium">
              <i class="pi pi-check-circle tw-mr-2"></i>{{ newDocument.file.name }}
            </p>
          </div>
        </div>

        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Description</label>
          <Textarea
            v-model="newDocument.description"
            placeholder="Add any notes about this document..."
            rows="3"
            class="tw-w-full"
          />
        </div>
      </div>

      <template #footer>
        <div class="tw-flex tw-justify-end tw-gap-3">
          <Button
            @click="showUploadModal = false"
            severity="secondary"
            label="Cancel"
          />
          <Button
            @click="uploadDocument"
            :loading="uploading"
            :disabled="!newDocument.file || !newDocument.type || uploading"
            severity="primary"
            label="Upload"
          />
        </div>
      </template>
    </Dialog>

    <!-- Add Treatment Dialog -->
    <Dialog
      :visible="showAddTreatmentModal"
      @update:visible="showAddTreatmentModal = $event"
      modal
      :header="editingTreatment ? 'Edit Treatment' : 'Add Treatment'"
      :style="{ width: '700px' }"
      :closable="true"
    >
      <div class="tw-space-y-4">
        <div class="tw-grid tw-grid-cols-2 tw-gap-4">
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              Doctor <span class="tw-text-red-500">*</span>
            </label>
            <Dropdown
              v-model="treatmentForm.doctor_id"
              :options="doctors"
              option-label="name"
              option-value="id"
              placeholder="Select doctor"
              class="tw-w-full"
              filter
              show-clear
            />
          </div>

          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              Prestation
            </label>
            <Dropdown
              v-model="treatmentForm.prestation_id"
              :options="prestations"
              option-label="name"
              option-value="id"
              placeholder="Select prestation"
              class="tw-w-full"
              filter
              show-clear
            />
          </div>
        </div>

        <div class="tw-grid tw-grid-cols-2 tw-gap-4">
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              Entered At <span class="tw-text-red-500">*</span>
            </label>
            <Calendar
              v-model="treatmentForm.entered_at"
              show-time
              hour-format="24"
              date-format="dd/mm/yy"
              placeholder="Select entry time"
              class="tw-w-full"
            />
          </div>

          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              Exited At
            </label>
            <Calendar
              v-model="treatmentForm.exited_at"
              show-time
              hour-format="24"
              date-format="dd/mm/yy"
              placeholder="Select exit time"
              class="tw-w-full"
              show-clear
            />
          </div>
        </div>

        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">Notes</label>
          <Textarea
            v-model="treatmentForm.notes"
            rows="3"
            placeholder="Enter notes about the treatment..."
            class="tw-w-full"
          />
        </div>

        <div v-if="!editingTreatment" class="tw-bg-blue-50 tw-border tw-border-blue-200 tw-rounded-lg tw-p-4">
          <div class="tw-flex tw-items-start">
            <i class="pi pi-info-circle tw-text-blue-600 tw-mr-3 tw-mt-1"></i>
            <div class="tw-flex-1">
              <p class="tw-text-sm tw-font-medium tw-text-blue-900 tw-mb-2">Use Convention Management</p>
              <p class="tw-text-xs tw-text-blue-700 tw-mb-3">
                Select doctor and prestation through convention management system for billing purposes
              </p>
              <Button
                @click="showConventionModal = true"
                severity="info"
                size="small"
                icon="pi pi-link"
                label="Open Convention Management"
              />
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="tw-flex tw-justify-end tw-gap-3">
          <Button
            @click="showAddTreatmentModal = false"
            severity="secondary"
            label="Cancel"
          />
          <Button
            @click="saveTreatment"
            :disabled="!treatmentForm.doctor_id || !treatmentForm.entered_at"
            severity="primary"
            :label="editingTreatment ? 'Update' : 'Save'"
          />
        </div>
      </template>
    </Dialog>

    <!-- ConventionManagement Modal -->
    <ConventionManagement
      v-if="admission"
      v-model:visible="showConventionModal"
      :ficheNavetteId="admission.fiche_navette_id || null"
      @convention-items-added="onConventionItemsAdded"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { AdmissionService } from '@/services/admissionService'
import { useNotification } from '@/composables/useNotification'
import axios from 'axios'

// PrimeVue Components
import Button from 'primevue/button'
import Card from 'primevue/card'
import Dialog from 'primevue/dialog'
import Dropdown from 'primevue/dropdown'
import Textarea from 'primevue/textarea'
import Tag from 'primevue/tag'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import ProgressSpinner from 'primevue/progressspinner'
import Divider from 'primevue/divider'
import Calendar from 'primevue/calendar'
import InputText from 'primevue/inputtext'
import ConventionManagement from '@/Components/Apps/Emergency/FicheNavatteItem/ConventionManagement.vue'

const route = useRoute()
const router = useRouter()
const { notify } = useNotification()

const admission = ref(null)
const loading = ref(true)
const uploading = ref(false)
const dragOver = ref(false)
const showUploadModal = ref(false)
const fileInput = ref(null)

const documentTypes = ref([
  { label: 'Medical Record', value: 'medical_record' },
  { label: 'Lab Result', value: 'lab_result' },
  { label: 'Imaging Report', value: 'imaging' },
  { label: 'Prescription', value: 'prescription' },
  { label: 'Discharge Summary', value: 'discharge_summary' },
  { label: 'Other', value: 'other' },
])

const newDocument = ref({
  type: '',
  file: null,
  description: '',
})

// Treatments state
const treatments = ref([])
const loadingTreatments = ref(false)
const showAddTreatmentModal = ref(false)
const showConventionModal = ref(false)
const editingTreatment = ref(false)
const selectedTreatment = ref(null)
const doctors = ref([])
const prestations = ref([])
const treatmentForm = ref({
  doctor_id: null,
  prestation_id: null,
  entered_at: null,
  exited_at: null,
  notes: '',
})

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString()
}

const formatFileSize = (bytes) => {
  if (!bytes) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i]
}

const getStatusSeverity = (status) => {
  const severities = {
    admitted: 'info',
    in_service: 'primary',
    document_pending: 'warning',
    ready_for_discharge: 'success',
  }
  return severities[status] || 'secondary'
}

const getDocumentTypeLabel = (type) => {
  const labels = {
    medical_record: 'Medical Record',
    lab_result: 'Lab Result',
    imaging: 'Imaging Report',
    prescription: 'Prescription',
    discharge_summary: 'Discharge Summary',
    other: 'Other',
  }
  return labels[type] || type
}

const handleFileUpload = (e) => {
  const file = e.target.files?.[0]
  if (file) {
    newDocument.value.file = file
  }
}

const handleFileDrop = (e) => {
  dragOver.value = false
  const file = e.dataTransfer.files?.[0]
  if (file) {
    newDocument.value.file = file
  }
}

const uploadDocument = async () => {
  if (!newDocument.value.file || !newDocument.value.type) {
    notify('error', 'Please select both document type and file')
    return
  }

  uploading.value = true
  const formData = new FormData()
  formData.append('type', newDocument.value.type)
  formData.append('file', newDocument.value.file)
  formData.append('description', newDocument.value.description)

  try {
    console.log('Uploading document:', {
      admissionId: admission.value.id,
      type: newDocument.value.type,
      fileName: newDocument.value.file.name,
      fileSize: newDocument.value.file.size
    })

    const response = await axios.post(
      `/api/admissions/${admission.value.id}/documents`,
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      }
    )

    console.log('Upload response:', response.data)
    notify('success', 'Document uploaded successfully')
    showUploadModal.value = false
    newDocument.value = { type: '', file: null, description: '' }
    if (fileInput.value) fileInput.value.value = ''
    await fetchAdmission()
  } catch (error) {
    console.error('Upload error:', error.response?.data || error.message)
    notify('error', error.response?.data?.message || error.message || 'Failed to upload document')
  } finally {
    uploading.value = false
  }
}

const downloadDocument = async (docId, fileName) => {
  try {
    console.log('Downloading document:', { admissionId: admission.value.id, docId, fileName })

    const response = await axios.get(
      `/api/admissions/${admission.value.id}/documents/${docId}/download`,
      {
        responseType: 'blob',
      }
    )

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', fileName || 'document')
    document.body.appendChild(link)
    link.click()
    link.parentElement.removeChild(link)
    window.URL.revokeObjectURL(url)
    notify('success', 'Document downloaded')
  } catch (error) {
    console.error('Download error:', error.response?.data || error.message)
    notify('error', 'Failed to download document')
  }
}

const deleteDocument = async (docId) => {
  if (!confirm('Are you sure you want to delete this document?')) return

  try {
    console.log('Deleting document:', { admissionId: admission.value.id, docId })

    await axios.delete(`/api/admissions/${admission.value.id}/documents/${docId}`)
    notify('success', 'Document deleted successfully')
    await fetchAdmission()
  } catch (error) {
    console.error('Delete error:', error.response?.data || error.message)
    notify('error', 'Failed to delete document')
  }
}

const loadTreatments = async () => {
  if (!route.params.id) return
  loadingTreatments.value = true
  try {
    const response = await axios.get(`/api/admissions/${route.params.id}/treatments`)
    treatments.value = response.data.data
  } catch (error) {
    notify('error', 'Failed to load treatments')
  } finally {
    loadingTreatments.value = false
  }
}

const loadDoctors = async () => {
  try {
    const response = await axios.get('/api/doctors')
    doctors.value = response.data.data
  } catch (error) {
    console.error('Failed to load doctors:', error)
  }
}

const loadPrestations = async () => {
  try {
    const response = await axios.get('/api/prestations')
    prestations.value = response.data.data
  } catch (error) {
    console.error('Failed to load prestations:', error)
  }
}

const openAddTreatmentModal = () => {
  editingTreatment.value = false
  selectedTreatment.value = null
  treatmentForm.value = {
    doctor_id: null,
    prestation_id: null,
    entered_at: null,
    exited_at: null,
    notes: '',
  }
  showAddTreatmentModal.value = true
  loadDoctors()
  loadPrestations()
}

const editTreatment = (treatment) => {
  editingTreatment.value = true
  selectedTreatment.value = treatment
  treatmentForm.value = {
    doctor_id: treatment.doctor_id,
    prestation_id: treatment.prestation_id,
    entered_at: treatment.entered_at ? new Date(treatment.entered_at) : null,
    exited_at: treatment.exited_at ? new Date(treatment.exited_at) : null,
    notes: treatment.notes || '',
  }
  showAddTreatmentModal.value = true
  loadDoctors()
  loadPrestations()
}

const createTreatment = async () => {
  try {
    await axios.post(`/api/admissions/${route.params.id}/treatments`, treatmentForm.value)
    notify('success', 'Treatment added successfully')
    showAddTreatmentModal.value = false
    loadTreatments()
  } catch (error) {
    notify('error', error.response?.data?.message || 'Failed to add treatment')
  }
}

const updateTreatment = async () => {
  try {
    await axios.patch(
      `/api/admissions/${route.params.id}/treatments/${selectedTreatment.value.id}`,
      treatmentForm.value
    )
    notify('success', 'Treatment updated successfully')
    showAddTreatmentModal.value = false
    loadTreatments()
  } catch (error) {
    notify('error', error.response?.data?.message || 'Failed to update treatment')
  }
}

const deleteTreatment = async (treatment) => {
  if (!confirm('Are you sure you want to delete this treatment?')) return

  try {
    await axios.delete(`/api/admissions/${route.params.id}/treatments/${treatment.id}`)
    notify('success', 'Treatment deleted successfully')
    loadTreatments()
  } catch (error) {
    notify('error', 'Failed to delete treatment')
  }
}

const saveTreatment = () => {
  if (editingTreatment.value) {
    updateTreatment()
  } else {
    createTreatment()
  }
}

const onConventionItemsAdded = (data) => {
  // Auto-populate doctor and prestation from convention selection
  if (data.doctor_id) {
    treatmentForm.value.doctor_id = data.doctor_id
  }
  if (data.prestation_id) {
    treatmentForm.value.prestation_id = data.prestation_id
  }
  showConventionModal.value = false
  showAddTreatmentModal.value = true
}

const verifyFileNumber = async () => {
  try {
    await axios.post(`/api/admissions/${admission.value.id}/verify-file-number`)
    notify('success', 'File number verified successfully')
    fetchAdmission()
  } catch (error) {
    notify('error', error.response?.data?.message || 'Failed to verify file number')
  }
}

const fetchAdmission = async () => {
  loading.value = true
  try {
    const response = await AdmissionService.getAdmission(route.params.id)
    admission.value = response.data.data
  } catch (error) {
    notify('error', 'Failed to load admission')
    router.push('/admissions')
  } finally {
    loading.value = false
  }
}

const dischargeAdmission = async () => {
  if (!confirm('Are you sure you want to discharge this patient?')) return

  try {
    await AdmissionService.dischargeAdmission(admission.value.id)
    notify('success', 'Patient discharged successfully')
    fetchAdmission()
  } catch (error) {
    notify('error', error.response?.data?.message || 'Failed to discharge patient')
  }
}

onMounted(() => {
  fetchAdmission()
  loadTreatments()
})
</script>

<style scoped>
/* Custom styles for enhanced design */
.tw-transition-all {
  transition: all 0.2s ease;
}

.tw-shadow-sm {
  box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
}

.tw-shadow-md {
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
}

/* PrimeVue Dialog customization */
.p-dialog .p-dialog-header {
  border-bottom: 1px solid #e5e7eb;
  padding: 1.5rem;
}

.p-dialog .p-dialog-content {
  padding: 1.5rem;
}

.p-dialog .p-dialog-footer {
  border-top: 1px solid #e5e7eb;
  padding: 1.5rem;
}

/* DataTable customization */
.p-datatable .p-datatable-thead > tr > th {
  background: #f9fafb;
  font-weight: 600;
  color: #374151;
  border-bottom: 1px solid #e5e7eb;
}

.p-datatable .p-datatable-tbody > tr:hover {
  background: #f9fafb;
}

/* TabView customization */
.p-tabview .p-tabview-nav {
  border: none;
  background: transparent;
}

.p-tabview .p-tabview-nav li .p-tabview-nav-link {
  border: none;
  border-bottom: 3px solid transparent;
  color: #6b7280;
  font-weight: 500;
  transition: all 0.2s ease;
}

.p-tabview .p-tabview-nav li .p-tabview-nav-link:hover {
  border-bottom-color: #3b82f6;
  color: #3b82f6;
}

.p-tabview .p-tabview-nav li.p-highlight .p-tabview-nav-link {
  color: #3b82f6;
  border-bottom-color: #3b82f6;
  background: transparent;
}

.p-tabview .p-tabview-panels {
  border: none;
  padding: 0;
}

/* Card hover effects */
.p-card:hover {
  box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
}

/* Button customizations */
.p-button {
  transition: all 0.2s ease;
}

.p-button:hover {
  transform: translateY(-1px);
}

/* Tag customizations */
.p-tag {
  font-weight: 500;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .tw-grid-cols-1.md\\:tw-grid-cols-2 {
    grid-template-columns: 1fr;
  }

  .lg\\:tw-col-span-2 {
    grid-column: span 1;
  }

  .lg\\:tw-grid-cols-3 {
    grid-template-columns: 1fr;
  }
}
</style>
