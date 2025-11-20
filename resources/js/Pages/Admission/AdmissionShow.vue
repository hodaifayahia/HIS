<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-slate-50 tw-via-blue-50/30 tw-to-indigo-50/20 tw-pb-12">
    <!-- Loading State -->
    <div v-if="loading" class="tw-flex tw-justify-center tw-items-center tw-py-12">
      <ProgressSpinner />
    </div>

    <div v-else-if="admission">
      <!-- Enhanced Header with Gradient Background -->
      <div class="tw-bg-gradient-to-r tw-from-white tw-via-blue-50/50 tw-to-indigo-50/30 tw-border-b tw-border-slate-200/60 tw-sticky tw-top-0 tw-z-10 tw-shadow-lg tw-backdrop-blur-sm">
        <div class="tw-px-6 tw-py-6">
          <div class="tw-flex tw-flex-col lg:tw-flex-row tw-justify-between tw-items-start lg:tw-items-center tw-gap-6">
            <div class="tw-flex tw-items-center tw-gap-4">
              <div class="tw-w-12 tw-h-12 tw-bg-gradient-to-br tw-from-blue-500 tw-to-indigo-600 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-shadow-lg">
                <i class="bi bi-file-earmark-medical tw-text-white tw-text-xl"></i>
              </div>
              <div>
                <h1 class="tw-text-3xl tw-font-bold tw-text-gray-900 tw-mb-1">
                  Admission Details
                </h1>
                <p class="tw-text-slate-600">
                  <i class="bi bi-person-fill tw-mr-2"></i>{{ admission.patient.name }}
                </p>
              </div>
            </div>

            <!-- Enhanced Action Buttons -->
            <div class="tw-flex tw-flex-wrap tw-gap-3">
              <Button
                as-child
                icon="pi pi-pencil"
                label="Edit"
                class="p-button-warning"
                size="small"
              >
                <router-link :to="`/admissions/${admission.id}/edit`">
                  Edit
                </router-link>
              </Button>
              <Button
                v-if="admission.can_discharge"
                @click="dischargeAdmission"
                icon="pi pi-door-open"
                label="Discharge"
                severity="success"
                size="small"
              />
              <Button
                v-if="admission && !admission.file_number_verified"
                @click="verifyFileNumber"
                icon="pi pi-check"
                label="Verify"
                severity="primary"
                size="small"
              />
              <Button
                as-child
                icon="pi pi-arrow-left"
                label="Back"
                class="p-button-outlined p-button-secondary"
                size="small"
              >
                <router-link to="/admissions">
                  Back
                </router-link>
              </Button>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div class="tw-px-6 tw-py-6">

        <!-- Overview Cards with Key Information - Single Row, Compact -->
        <div class="tw-grid tw-grid-cols-2 md:tw-grid-cols-4 lg:tw-grid-cols-4 tw-gap-3 tw-mb-8">
          <!-- Patient Info -->
          <div class="tw-bg-white/80 tw-backdrop-blur-sm tw-border tw-border-white/20 tw-rounded-lg tw-p-3 tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-200">
            <div class="tw-flex tw-items-center tw-gap-2 tw-mb-2">
              <div class="tw-w-8 tw-h-8 tw-bg-blue-100 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-flex-shrink-0">
                <i class="bi bi-person-fill tw-text-blue-600 tw-text-sm"></i>
              </div>
              <p class="tw-text-xs tw-font-semibold tw-text-slate-600 tw-uppercase tw-truncate">Patient</p>
            </div>
            <p class="tw-text-sm tw-font-bold tw-text-gray-900 tw-truncate">{{ admission.patient.name }}</p>
          </div>

          <!-- Doctor Info -->
          <div class="tw-bg-white/80 tw-backdrop-blur-sm tw-border tw-border-white/20 tw-rounded-lg tw-p-3 tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-200">
            <div class="tw-flex tw-items-center tw-gap-2 tw-mb-2">
              <div class="tw-w-8 tw-h-8 tw-bg-green-100 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-flex-shrink-0">
                <i class="bi bi-person-check tw-text-green-600 tw-text-sm"></i>
              </div>
              <p class="tw-text-xs tw-font-semibold tw-text-slate-600 tw-uppercase tw-truncate">Doctor</p>
            </div>
            <p class="tw-text-sm tw-font-bold tw-text-gray-900 tw-truncate">{{ admission.doctor?.name || 'N/A' }}</p>
          </div>

          <!-- Status Info -->
          <div class="tw-bg-white/80 tw-backdrop-blur-sm tw-border tw-border-white/20 tw-rounded-lg tw-p-3 tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-200">
            <div class="tw-flex tw-items-center tw-gap-2 tw-mb-2">
              <div class="tw-w-8 tw-h-8 tw-bg-amber-100 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-flex-shrink-0">
                <i class="bi bi-info-circle tw-text-amber-600 tw-text-sm"></i>
              </div>
              <p class="tw-text-xs tw-font-semibold tw-text-slate-600 tw-uppercase tw-truncate">Status</p>
            </div>
            <Tag :severity="getStatusSeverity(admission.status)" :value="admission.status_label" class="tw-font-medium tw-text-xs" />
          </div>

          <!-- Type Info -->
          <div class="tw-bg-white/80 tw-backdrop-blur-sm tw-border tw-border-white/20 tw-rounded-lg tw-p-3 tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-200">
            <div class="tw-flex tw-items-center tw-gap-2 tw-mb-2">
              <div class="tw-w-8 tw-h-8 tw-bg-purple-100 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-flex-shrink-0">
                <i class="bi bi-tag tw-text-purple-600 tw-text-sm"></i>
              </div>
              <p class="tw-text-xs tw-font-semibold tw-text-slate-600 tw-uppercase tw-truncate">Type</p>
            </div>
            <Tag :severity="admission.type === 'surgery' ? 'warning' : 'success'" :value="admission.type_label" class="tw-font-medium tw-text-xs" />
          </div>
        </div>
      </div>

      <!-- Tabs Section -->
      <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-border tw-border-slate-200/60 tw-overflow-hidden tw-backdrop-blur-sm">
        <TabView class="tw-border-0">
            <!-- Edit Info Tab -->
            <TabPanel header="Edit Information" :header-icon="'pi pi-pencil'">
              <div class="tw-space-y-6 tw-p-6">
                <div class="tw-flex tw-items-center tw-gap-3 tw-mb-6">
                  <div class="tw-w-10 tw-h-10 tw-bg-blue-100 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                    <i class="bi bi-pencil-square tw-text-blue-600"></i>
                  </div>
                  <div>
                    <h5 class="tw-text-lg tw-font-bold tw-text-gray-900 tw-m-0">Edit Admission Details</h5>
                    <p class="tw-text-sm tw-text-slate-600">Update admission information and medical details</p>
                  </div>
                </div>

                <form @submit.prevent="saveAdmissionChanges" class="tw-space-y-6">
                  <!-- Row 1: File Number & Admission Type -->
                  <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
                    <!-- File Number -->
                    <div class="tw-space-y-2">
                      <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
                        <i class="pi pi-file tw-text-indigo-600"></i>File Number
                        <Tag v-if="admission.file_number_verified" severity="success" class="tw-text-xs">
                          <i class="pi pi-lock tw-mr-1"></i>Verified
                        </Tag>
                      </label>
                      <InputText 
                        id="file_number"
                        name="file_number"
                        v-model="editForm.file_number" 
                        class="tw-w-full"
                        :readonly="admission.file_number_verified"
                        placeholder="e.g., 2024/001"
                      />
                      <small v-if="admission.file_number_verified" class="tw-text-green-600">
                        <i class="pi pi-lock tw-mr-1"></i>Verified file numbers cannot be edited
                      </small>
                    </div>

                    <!-- Admission Type -->
                    <div class="tw-space-y-2">
                      <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
                        <i class="pi pi-building tw-text-amber-600"></i>Admission Type
                      </label>
                      <div class="tw-flex tw-gap-3">
                        <label class="tw-flex tw-items-center tw-gap-2 tw-flex-1 tw-p-3 tw-border tw-border-gray-200 tw-rounded-lg tw-cursor-pointer hover:tw-bg-gray-50" :class="{ 'tw-bg-amber-50 tw-border-amber-500': editForm.type === 'surgery' }">
                          <input id="type_surgery" name="type" type="radio" v-model="editForm.type" value="surgery" class="tw-w-4 tw-h-4" />
                          <span class="tw-text-sm tw-font-medium">Surgery</span>
                        </label>
                        <label class="tw-flex tw-items-center tw-gap-2 tw-flex-1 tw-p-3 tw-border tw-border-gray-200 tw-rounded-lg tw-cursor-pointer hover:tw-bg-gray-50" :class="{ 'tw-bg-green-50 tw-border-green-500': editForm.type === 'nursing' }">
                          <input id="type_nursing" name="type" type="radio" v-model="editForm.type" value="nursing" class="tw-w-4 tw-h-4" />
                          <span class="tw-text-sm tw-font-medium">Nursing</span>
                        </label>
                      </div>
                    </div>
                  </div>

                  <!-- Row 2: Doctor & Companion -->
                  <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
                    <!-- Doctor -->
                    <div class="tw-space-y-2">
                      <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
                        <i class="pi pi-user-md tw-text-indigo-600"></i>Doctor
                      </label>
                      <Dropdown
                        id="doctor_id"
                        name="doctor_id"
                        v-model="editForm.doctor_id"
                        :options="editDoctors"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="-- Select Doctor --"
                        class="tw-w-full"
                      />
                    </div>

                    <!-- Companion -->
                    <div class="tw-space-y-2">
                      <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
                        <i class="pi pi-people tw-text-pink-600"></i>Companion
                      </label>
                      <InputText
                        id="companion_name"
                        name="companion_name"
                        v-model="editForm.companion_name" 
                        class="tw-w-full"
                        placeholder="Companion name"
                      />
                    </div>
                  </div>

                  <!-- Row 3: Company & Social Security -->
                  <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
                    <!-- Company/Insurance -->
                    <div class="tw-space-y-2">
                      <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
                        <i class="pi pi-building tw-text-cyan-600"></i>Company/Insurance
                      </label>
                      <Dropdown
                        id="company_id"
                        name="company_id"
                        v-model="editForm.company_id"
                        :options="editCompanies"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Select company or insurance"
                        class="tw-w-full"
                        filter
                        showClear
                      />
                    </div>

                    <!-- Social Security Number -->
                    <div class="tw-space-y-2">
                      <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
                        <i class="pi pi-shield tw-text-green-600"></i>Social Security Number
                      </label>
                      <InputText
                        id="social_security_num"
                        name="social_security_num"
                        v-model="editForm.social_security_num" 
                        class="tw-w-full"
                        placeholder="XXX-XX-XXXX"
                      />
                    </div>
                  </div>

                  <!-- Row 4: Relation Type & Initial Prestation -->
                  <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
                    <!-- Relation Type -->
                    <div class="tw-space-y-2">
                      <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
                        <i class="pi pi-link tw-text-orange-600"></i>Relation Type
                      </label>
                      <Dropdown
                        id="relation_type"
                        name="relation_type"
                        v-model="editForm.relation_type"
                        :options="editRelationTypes"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Select relation type"
                        class="tw-w-full"
                        filter
                        showClear
                      />
                    </div>

                    <!-- Initial Prestation -->
                    <div class="tw-space-y-2">
                      <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
                        <i class="pi pi-plus-circle tw-text-emerald-600"></i>Initial Prestation
                      </label>
                      <InputText
                        id="initial_prestation_name"
                        name="initial_prestation_name"
                        v-model="editForm.initial_prestation_name" 
                        class="tw-w-full"
                        placeholder="Prestation name"
                        :readonly="true"
                      />
                    </div>
                  </div>

                  <!-- Row 5: Observation/Notes -->
                  <div class="tw-space-y-2">
                    <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
                      <i class="pi pi-comment tw-text-orange-600"></i>Observation/Notes
                    </label>
                    <Dropdown
                      id="observation"
                      name="observation"
                      v-model="editForm.observation"
                      :options="observationTypesList"
                      optionLabel="label"
                      optionValue="value"
                      placeholder="Select observation type"
                      class="tw-w-full"
                      filter
                      showClear
                    />
                    <Textarea
                      id="custom_observation"
                      name="custom_observation"
                      v-if="editForm.observation === 'other'"
                      v-model="editForm.custom_observation" 
                      class="tw-w-full"
                      placeholder="Enter custom observation..."
                      rows="3"
                    />
                  </div>

                  <!-- Reason for Admission -->
                  <div class="tw-space-y-2">
                    <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
                      <i class="pi pi-info-circle tw-text-red-600"></i>Reason for Admission
                    </label>
                    <Textarea
                      id="reason_for_admission"
                      name="reason_for_admission"
                      v-model="editForm.reason_for_admission" 
                      class="tw-w-full"
                      placeholder="Enter reason for admission..."
                      rows="3"
                    />
                  </div>

                  <!-- Save Button Section -->
                  <div class="tw-flex tw-justify-end tw-gap-3 tw-pt-6 tw-border-t tw-border-slate-200/60">
                    <Button
                      @click="resetEditForm"
                      type="button"
                      label="Cancel"
                      severity="secondary"
                      outlined
                      icon="pi pi-times"
                    />
                    <Button
                      @click="saveAdmissionChanges"
                      type="submit"
                      label="Save Changes"
                      severity="success"
                      icon="pi pi-check"
                      :loading="savingChanges"
                    />
                  </div>
                </form>
              </div>
            </TabPanel>

            <!-- Documents Tab -->
            <TabPanel header="Documents" :header-icon="'pi pi-file-pdf'">
              <div class="tw-space-y-6 tw-p-6">
                <div class="tw-flex tw-flex-col sm:tw-flex-row tw-justify-between tw-items-start sm:tw-items-center tw-gap-4">
                  <div>
                    <h6 class="tw-text-lg tw-font-bold tw-text-gray-900 tw-mb-1">
                      <i class="bi bi-file-earmark-pdf tw-mr-2"></i>Document Management
                    </h6>
                    <p class="tw-text-sm tw-text-slate-600">{{ admission.documents_count || 0 }} document(s) uploaded</p>
                  </div>
                  <Button
                    @click="showUploadModal = true"
                    severity="primary"
                    icon="pi pi-cloud-upload"
                    label="Upload Document"
                    size="small"
                  />
                </div>

                <!-- Documents Grid -->
                <div v-if="admission.documents && admission.documents.length > 0" class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
                  <div
                    v-for="doc in admission.documents"
                    :key="doc.id"
                    class="tw-bg-gradient-to-br tw-from-slate-50 tw-to-slate-100/50 tw-rounded-xl tw-border tw-border-slate-200/60 tw-p-4 tw-shadow-sm tw-hover:tw-shadow-md tw-transition-all tw-duration-200 group"
                  >
                    <div class="tw-flex tw-justify-between tw-items-start tw-mb-3">
                      <div class="tw-flex tw-items-center tw-gap-3">
                        <div class="tw-w-10 tw-h-10 tw-bg-red-100 tw-rounded-lg tw-flex tw-items-center tw-justify-center group-hover:tw-scale-110 tw-transition-transform">
                          <i class="bi bi-file-earmark-pdf tw-text-red-600"></i>
                        </div>
                        <div>
                          <p class="tw-font-semibold tw-text-gray-900 tw-text-sm">{{ doc.name || 'Document' }}</p>
                          <p class="tw-text-xs tw-text-slate-500">{{ formatFileSize(doc.file_size) }}</p>
                        </div>
                      </div>
                      <Tag :value="getDocumentTypeLabel(doc.type)" severity="info" class="tw-text-xs" />
                    </div>
                    <div v-if="doc.description" class="tw-mb-3 tw-pb-3 tw-border-b tw-border-slate-200/60">
                      <p class="tw-text-sm tw-text-gray-700">{{ doc.description }}</p>
                    </div>
                    <div class="tw-flex tw-gap-2 tw-flex-wrap">
                      <Button
                        @click.stop="downloadDocument(doc.id, doc.name)"
                        icon="pi pi-download"
                        size="small"
                        rounded
                        text
                        v-tooltip.top="'Download'"
                        class="p-button-info"
                      />
                      <Button
                        @click.stop="deleteDocument(doc.id)"
                        icon="pi pi-trash"
                        size="small"
                        rounded
                        text
                        v-tooltip.top="'Delete'"
                        class="p-button-danger"
                      />
                    </div>
                  </div>
                </div>

                <!-- Empty State -->
                <div v-else class="tw-text-center tw-py-12">
                  <i class="bi bi-file-earmark-pdf tw-text-6xl tw-text-slate-300 tw-mb-4 tw-block"></i>
                  <h5 class="tw-text-lg tw-font-semibold tw-text-slate-600 tw-mb-2">No Documents Uploaded</h5>
                  <p class="tw-text-slate-500 tw-mb-6">Start by uploading patient documents to manage records</p>
                  <Button
                    @click="showUploadModal = true"
                    icon="pi pi-plus"
                    label="Upload Your First Document"
                    severity="primary"
                    size="small"
                  />
                </div>
              </div>
            </TabPanel>

            <!-- Treatments Tab -->
            <TabPanel>
              <template #header>
                <i class="pi pi-calendar tw-mr-2"></i>
                <span>Treatments</span>
              </template>
              <div class="tw-space-y-4 tw-p-6">
                <div class="tw-flex tw-flex-col sm:tw-flex-row tw-justify-between tw-items-start sm:tw-items-center tw-gap-4">
                  <div>
                    <h3 class="tw-text-lg tw-font-bold tw-text-gray-900 tw-mb-1">
                      <i class="bi bi-heart-pulse tw-mr-2"></i>Patient Treatments
                    </h3>
                    <p class="tw-text-sm tw-text-slate-600">{{ treatments.length }} treatment(s) recorded</p>
                  </div>
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
                  :rows-per-page-options="[5, 10, 20]"
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

                  <Column field="notes" header="Notes" style="width: 40%">
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
      </div>
    </div>

    <div v-else class="tw-px-6 tw-py-6">
      <div class="tw-bg-red-50 tw-border tw-border-red-200 tw-rounded-2xl tw-p-8 tw-text-center">
        <i class="bi bi-exclamation-triangle tw-text-5xl tw-text-red-500 tw-mb-4 tw-block"></i>
        <h3 class="tw-text-xl tw-font-bold tw-text-red-900 tw-mb-2">Admission Not Found</h3>
        <p class="tw-text-red-700 tw-mb-4">The admission record you're looking for doesn't exist or has been deleted.</p>
        <router-link to="/admissions" class="tw-inline-block tw-px-6 tw-py-2 tw-bg-red-500 tw-text-white tw-rounded-lg tw-hover:tw-bg-red-600 tw-transition-colors">
          <i class="pi pi-arrow-left tw-mr-2"></i>Back to Admissions
        </router-link>
      </div>
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

// Edit Admission Form State
const savingChanges = ref(false)
const editDoctors = ref([])
const editCompanies = ref([])
const editRelationTypes = ref([])
const editForm = ref({
  file_number: '',
  type: 'nursing',
  doctor_id: null,
  companion_id: null,
  companion_name: '',
  company_id: null,
  social_security_num: '',
  relation_type: '',
  initial_prestation_id: null,
  initial_prestation_name: '',
  observation: '',
  custom_observation: '',
  reason_for_admission: '',
})

const observationTypesList = ref([
  { value: 'Hotellerie', label: 'Hotellerie' },
  { value: 'Hotellerie pour MAPA (Holter Tensionnel)', label: 'Hotellerie pour MAPA (Holter Tensionnel)' },
  { value: 'Hotellerie Holter ECG', label: 'Hotellerie Holter ECG' },
  { value: 'Non Hospit. - Admis(e) & Sortie par Pavillon des Urgences', label: 'Non Hospit. - Admis(e) & Sortie par Pavillon des Urgences' },
  { value: 'Non Hospit. - Admis(e), Opéré(e), Réglé(e) & Sortie par Pavillon des Urgences', label: 'Non Hospit. - Admis(e), Opéré(e), Réglé(e) & Sortie par Pavillon des Urgences' },
  { value: 'Non Hospit. - Evacuation - *vers autre(s) structure(s)*', label: 'Non Hospit. - Evacuation - *vers autre(s) structure(s)*' },
  { value: 'Sortie Contre Avis Médical', label: 'Sortie Contre Avis Médical' },
  { value: 'Non Hospit. - Sortie Contre Avis Médical', label: 'Non Hospit. - Sortie Contre Avis Médical' },
  { value: 'P. E. C. (Voir Direction)', label: 'P. E. C. (Voir Direction)' },
  { value: 'other', label: 'Other (Custom)' },
])

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
    console.log('Fetched admission data:', admission.value)
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

// Initialize edit form with admission data
const initializeEditForm = async () => {
  if (!admission.value) return

  console.log('Initializing edit form with admission:', admission.value)

  // If admission has fiche_navette_id but missing info, fetch from fiche navette
  let ficheData = null
  if (admission.value.fiche_navette_id && 
      (!admission.value.company_id || !admission.value.initial_prestation_id)) {
    try {
      const ficheResponse = await axios.get(`/api/fiche-navette/${admission.value.fiche_navette_id}`)
      ficheData = ficheResponse.data.data
      console.log('Fetched fiche navette data:', ficheData)
    } catch (error) {
      console.error('Failed to fetch fiche navette:', error)
    }
  }

  editForm.value = {
    file_number: admission.value.file_number || '',
    type: admission.value.type || 'nursing',
    doctor_id: admission.value.doctor_id || ficheData?.doctor_id || null,
    companion_id: admission.value.companion_id || null,
    companion_name: admission.value.companion?.name || '',
    company_id: admission.value.company_id || ficheData?.organisme_id || null,
    social_security_num: admission.value.social_security_num || '',
    relation_type: admission.value.relation_type || '',
    initial_prestation_id: admission.value.initial_prestation_id || null,
    initial_prestation_name: admission.value.initial_prestation?.name || 
                             (ficheData?.items?.[0]?.prestation?.name) || '',
    observation: admission.value.observation || '',
    custom_observation: '',
    reason_for_admission: admission.value.reason_for_admission || '',
  }

  console.log('Initialized editForm:', editForm.value)
}

// Load dropdown options for edit form
const loadEditDropdownOptions = async () => {
  try {
    const [doctorsRes, companiesRes, relationTypesRes] = await Promise.all([
      axios.get('/api/doctors'),
      axios.get('/api/organismes'),
      axios.get('/api/config/relation-types'),
    ])

    editDoctors.value = doctorsRes.data.data || []
    editCompanies.value = companiesRes.data.data || []
    
    // Use the relation types from backend API
    editRelationTypes.value = relationTypesRes.data.data || []
    
    console.log('Loaded relation types:', editRelationTypes.value)
  } catch (error) {
    console.error('Failed to load dropdown options:', error)
  }
}

// Reset edit form
const resetEditForm = () => {
  initializeEditForm()
}

// Save admission changes
const saveAdmissionChanges = async () => {
  if (!admission.value) return

  savingChanges.value = true
  try {
    const dataToSave = {
      file_number: editForm.value.file_number,
      type: editForm.value.type,
      doctor_id: editForm.value.doctor_id,
      companion_id: editForm.value.companion_id,
      company_id: editForm.value.company_id,
      social_security_num: editForm.value.social_security_num,
      relation_type: editForm.value.relation_type,
      initial_prestation_id: editForm.value.initial_prestation_id,
      observation: editForm.value.observation === 'other' ? editForm.value.custom_observation : editForm.value.observation,
      reason_for_admission: editForm.value.reason_for_admission,
    }

    const response = await axios.put(
      `/api/admissions/${admission.value.id}`,
      dataToSave
    )

    if (response.data.success) {
      notify('success', 'Admission details updated successfully')
      await fetchAdmission()
      initializeEditForm()
    }
  } catch (error) {
    console.error('Error saving admission changes:', error)
    notify('error', error.response?.data?.message || 'Failed to save admission changes')
  } finally {
    savingChanges.value = false
  }
}

onMounted(async () => {
  await fetchAdmission()
  loadTreatments()
  await loadEditDropdownOptions()
  initializeEditForm()
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

.tw-shadow-lg {
  box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
}

/* Enhanced medical table styles */
:deep(.p-datatable .p-datatable-header) {
  background-color: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
  background-color: #f8fafc;
  color: #374151;
  font-weight: 600;
  font-size: 0.875rem;
  border-bottom: 1px solid #e2e8f0;
  padding: 1rem 0.75rem;
}

:deep(.p-datatable .p-datatable-tbody > tr) {
  border-bottom: 1px solid #f1f5f9;
  transition: background-color 0.2s ease;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background-color: rgba(59, 130, 246, 0.05);
}

:deep(.p-datatable .p-datatable-tbody > tr.p-highlight) {
  background-color: #eff6ff;
}

:deep(.p-button-sm) {
  font-size: 0.875rem;
  padding: 0.375rem 0.625rem;
}

:deep(.p-tag) {
  font-weight: 600;
}

/* PrimeVue Dialog customization */
:deep(.p-dialog .p-dialog-header) {
  border-bottom: 1px solid #e5e7eb;
  padding: 1.5rem;
  background: linear-gradient(to right, #f0f9ff, #f0fdf4);
}

:deep(.p-dialog .p-dialog-content) {
  padding: 1.5rem;
}

:deep(.p-dialog .p-dialog-footer) {
  border-top: 1px solid #e5e7eb;
  padding: 1.5rem;
}

/* TabView customization */
:deep(.p-tabview .p-tabview-nav) {
  border: none;
  background: transparent;
  border-bottom: 2px solid #e5e7eb;
}

:deep(.p-tabview .p-tabview-nav li .p-tabview-nav-link) {
  border: none;
  border-bottom: 3px solid transparent;
  color: #6b7280;
  font-weight: 500;
  padding: 1rem 1.5rem;
  transition: all 0.2s ease;
  margin-bottom: -2px;
}

:deep(.p-tabview .p-tabview-nav li .p-tabview-nav-link:hover) {
  border-bottom-color: #3b82f6;
  color: #3b82f6;
}

:deep(.p-tabview .p-tabview-nav li.p-highlight .p-tabview-nav-link) {
  color: #3b82f6;
  border-bottom-color: #3b82f6;
  background: transparent;
}

:deep(.p-tabview .p-tabview-panels) {
  border: none;
  padding: 0;
  background: transparent;
}

/* Card hover effects */
:deep(.p-card) {
  border-radius: 0.75rem;
}

:deep(.p-card:hover) {
  box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);
}

/* Button customizations */
:deep(.p-button) {
  border-radius: 0.5rem;
  transition: all 0.2s ease;
}

:deep(.p-button:hover) {
  transform: translateY(-1px);
}

:deep(.p-button.p-button-outlined) {
  border-width: 1px;
}

/* Dropdown customizations */
:deep(.p-dropdown) {
  border-radius: 0.5rem;
}

:deep(.p-inputtext) {
  border-radius: 0.5rem;
}

/* Custom scrollbar */
:deep(.p-datatable-wrapper::-webkit-scrollbar) {
  width: 6px;
  height: 6px;
}

:deep(.p-datatable-wrapper::-webkit-scrollbar-track) {
  background: #f1f5f9;
  border-radius: 3px;
}

:deep(.p-datatable-wrapper::-webkit-scrollbar-thumb) {
  background: #cbd5e1;
  border-radius: 3px;
}

:deep(.p-datatable-wrapper::-webkit-scrollbar-thumb:hover) {
  background: #94a3b8;
}

/* Animations */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes slideIn {
  from { opacity: 0; transform: translateX(-10px); }
  to { opacity: 1; transform: translateX(0); }
}

.tw-animate-fade-in {
  animation: fadeIn 0.3s ease-out;
}

.tw-animate-slide-in {
  animation: slideIn 0.2s ease-out;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  :deep(.p-tabview .p-tabview-nav li .p-tabview-nav-link) {
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
  }
}
</style>
