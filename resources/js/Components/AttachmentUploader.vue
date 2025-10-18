<template>
  <div class="attachment-uploader">
    <!-- Header -->
    <div class="tw-flex tw-justify-between tw-items-center tw-mb-4">
      <h3 class="tw-text-lg tw-font-semibold tw-text-gray-800">
        <i class="pi pi-paperclip tw-mr-2"></i>
        Attachments
      </h3>
      <Badge 
        :value="attachments.length" 
        severity="info" 
        v-if="attachments.length > 0"
      />
    </div>

    <!-- File Upload Area -->
    <div class="tw-mb-4">
      <FileUpload
        ref="fileUpload"
        mode="basic"
        :multiple="true"
        :auto="false"
        :maxFileSize="maxFileSize"
        :accept="acceptedTypes"
  :chooseLabel="chooseLabel"
        :disabled="uploading || disabled"
        @select="onFileSelect"
        class="tw-w-full"
      >
        <template #empty>
          <div class="tw-text-center tw-p-4 tw-border-2 tw-border-dashed tw-border-gray-300 tw-rounded-lg">
            <i class="pi pi-cloud-upload tw-text-4xl tw-text-gray-400 tw-mb-2"></i>
            <p class="tw-text-gray-500">Drop files here or click to browse</p>
            <p class="tw-text-xs tw-text-gray-400 tw-mt-1">
              Max file size: {{ formatFileSize(maxFileSize) }}
            </p>
          </div>
        </template>
      </FileUpload>
      
      <!-- Upload Button -->
      <Button
        v-if="selectedFiles.length > 0 && showUploadButton"
        :label="uploadButtonLabel"
        icon="pi pi-upload"
        :loading="uploading"
        @click="uploadFiles"
        class="tw-mt-2 tw-w-full"
      />
    </div>

    <!-- Existing Attachments List -->
    <div v-if="attachments.length > 0" class="tw-space-y-2">
      <h4 class="tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
        Uploaded Files ({{ attachments.length }})
      </h4>
      
      <div 
        v-for="(attachment, index) in attachments" 
        :key="attachment.id || index"
        class="tw-flex tw-items-center tw-justify-between tw-p-3 tw-bg-gray-50 tw-rounded-lg tw-border"
      >
        <div class="tw-flex tw-items-center tw-flex-1">
          <i 
            :class="getFileIcon(attachment.mime_type || attachment.type)" 
            class="tw-text-2xl tw-mr-3 tw-text-blue-500"
          ></i>
          <div class="tw-flex-1 tw-min-w-0">
            <p class="tw-text-sm tw-font-medium tw-text-gray-900 tw-truncate">
              {{ attachment.original_name || attachment.name }}
            </p>
            <p class="tw-text-xs tw-text-gray-500">
              {{ formatFileSize(attachment.size) }} • 
              {{ formatDate(attachment.uploaded_at || attachment.created_at) }}
            </p>
          </div>
        </div>
        
        <div class="tw-flex tw-items-center tw-space-x-1">
          <!-- View/Download Button -->
          <Button
            icon="pi pi-eye"
            severity="info"
            text
            rounded
            @click="viewFile(attachment)"
            :disabled="disabled || !canPreview(attachment)"
            v-tooltip="'View File'"
          />
          
          <!-- Download Button -->
          <Button
            icon="pi pi-download"
            severity="success" 
            text
            rounded
            @click="downloadFile(attachment)"
            :disabled="disabled || !canPreview(attachment)"
            v-tooltip="'Download'"
          />
          
          <!-- Delete Button -->
          <Button
            icon="pi pi-trash"
            severity="danger"
            text
            rounded
            @click="confirmDelete(attachment, index)"
            :disabled="disabled"
            v-tooltip="'Delete'"
          />
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="tw-text-center tw-py-8 tw-text-gray-500">
      <i class="pi pi-file tw-text-4xl tw-mb-2"></i>
      <p>No attachments uploaded yet</p>
    </div>

    <!-- Delete Confirmation Dialog -->
    <Dialog
      v-model:visible="deleteDialog"
      header="Confirm Delete"
      :modal="true"
      class="tw-w-full tw-max-w-md"
    >
      <div class="tw-flex tw-items-center tw-space-x-3">
        <i class="pi pi-exclamation-triangle tw-text-3xl tw-text-yellow-500"></i>
        <div>
          <p class="tw-font-medium">Delete this attachment?</p>
          <p class="tw-text-sm tw-text-gray-600 tw-mt-1">
            {{ attachmentToDelete?.original_name || attachmentToDelete?.name }}
          </p>
          <p class="tw-text-sm tw-text-gray-500 tw-mt-1">
            This action cannot be undone.
          </p>
        </div>
      </div>
      
      <template #footer>
        <Button 
          label="Cancel" 
          icon="pi pi-times" 
          text 
          @click="deleteDialog = false" 
        />
        <Button 
          label="Delete" 
          icon="pi pi-trash" 
          severity="danger" 
          @click="deleteFile"
          :loading="deleting"
        />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import axios from 'axios'

// PrimeVue Components
import FileUpload from 'primevue/fileupload'
import Button from 'primevue/button'
import Badge from 'primevue/badge'
import Dialog from 'primevue/dialog'

// Props
const props = defineProps({
  modelValue: {
    type: Array,
    default: () => []
  },
  modelType: {
    type: String,
    required: true,
    validator: (value) => ['bon_commend', 'bon_reception', 'bon_entree', 'facture_proforma'].includes(value)
  },
  modelId: {
    type: [String, Number],
    required: true
  },
  maxFileSize: {
    type: Number,
    default: 10485760 // 10MB
  },
  acceptedTypes: {
    type: String,
    default: '.pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx'
  },
  disabled: {
    type: Boolean,
    default: false
  },
  uploadMode: {
    type: String,
    default: 'api',
    validator: (value) => ['api', 'form'].includes(value)
  }
})

// Emits
const emit = defineEmits(['update:modelValue', 'uploaded', 'deleted', 'error'])

// Composables
const toast = useToast()

// Reactive state
const fileUpload = ref()
const selectedFiles = ref([])
const uploading = ref(false)
const deleting = ref(false)
const deleteDialog = ref(false)
const attachmentToDelete = ref(null)
const indexToDelete = ref(null)

// Computed
const attachments = computed({
  get: () => props.modelValue || [],
  set: (value) => emit('update:modelValue', value)
})

const isFormMode = computed(() => props.uploadMode === 'form')
const showUploadButton = computed(() => !isFormMode.value)
const uploadButtonLabel = computed(() => {
  if (isFormMode.value) {
    return 'Add Files'
  }
  return uploading.value ? 'Uploading...' : 'Upload Files'
})

const generateUuid = () => {
  if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
    return crypto.randomUUID()
  }
  return `${Date.now()}-${Math.random()}`
}
const chooseLabel = computed(() => {
  if (isFormMode.value) {
    return 'Add Attachments'
  }
  return uploading.value ? 'Uploading...' : 'Choose Files'
})

// Methods
const onFileSelect = (event) => {
  selectedFiles.value = Array.from(event.files)

  if (isFormMode.value) {
    processSelectedFiles()
  }
}

const uploadFiles = async () => {
  if (!selectedFiles.value.length) return

  if (isFormMode.value) {
    processSelectedFiles()
    return
  }

  uploading.value = true
  
  try {
    const formData = new FormData()
    
    selectedFiles.value.forEach((file) => {
      formData.append('files[]', file)
    })
    
    formData.append('model_type', props.modelType)
    formData.append('model_id', props.modelId)

    const response = await axios.post('/api/attachments/upload', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    if (response.data.success) {
      const updatedAttachments = [...attachments.value, ...response.data.files]
      attachments.value = updatedAttachments
      
      clearSelectedFiles()

      emit('uploaded', response.data.files)
      
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: `${response.data.files.length} file(s) uploaded successfully`,
        life: 3000
      })
    }
  } catch (error) {
    console.error('Upload error:', error)
    const message = error.response?.data?.message || 'Failed to upload files'
    
    emit('error', error)
    
    toast.add({
      severity: 'error',
      summary: 'Upload Error', 
      detail: message,
      life: 5000
    })
  } finally {
    uploading.value = false
  }
}

const processSelectedFiles = () => {
  if (!selectedFiles.value.length) return

  const newAttachments = selectedFiles.value.map((file) => ({
    id: generateUuid(),
    name: file.name,
    original_name: file.name,
    file,
    size: file.size,
    mime_type: file.type,
    uploaded_at: new Date().toISOString(),
    description: '',
  }))

  attachments.value = [...attachments.value, ...newAttachments]

  emit('uploaded', newAttachments)

  toast.add({
    severity: 'success',
    summary: 'Added',
    detail: `${newAttachments.length} file(s) added`,
    life: 3000
  })

  clearSelectedFiles()
}

const clearSelectedFiles = () => {
  selectedFiles.value = []
  if (fileUpload.value) {
    fileUpload.value.clear()
  }
}

const viewFile = async (attachment) => {
  if (!canPreview(attachment)) return

  try {
    const response = await axios.get('/api/attachments/view', {
      params: {
        model_type: props.modelType,
        model_id: props.modelId,
        attachment_id: attachment.id
      },
      responseType: 'blob'
    })

    const blob = new Blob([response.data], { type: attachment.mime_type || 'application/octet-stream' })
    const url = window.URL.createObjectURL(blob)
    
    // Open in new window/tab
    window.open(url, '_blank')
    
    // Clean up the URL after a delay
    setTimeout(() => window.URL.revokeObjectURL(url), 1000)
  } catch (error) {
    console.error('View error:', error)
    toast.add({
      severity: 'error',
      summary: 'View Error',
      detail: 'Failed to view file',
      life: 3000
    })
  }
}

const downloadFile = async (attachment) => {
  if (!canPreview(attachment)) return

  try {
    const response = await axios.get('/api/attachments/download', {
      params: {
        model_type: props.modelType,
        model_id: props.modelId,
        attachment_id: attachment.id
      },
      responseType: 'blob'
    })

    const blob = new Blob([response.data])
    const url = window.URL.createObjectURL(blob)
    
    // Create download link
    const link = document.createElement('a')
    link.href = url
    link.download = attachment.original_name || attachment.filename
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    
    // Clean up
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Download error:', error)
    toast.add({
      severity: 'error',
      summary: 'Download Error',
      detail: 'Failed to download file',
      life: 3000
    })
  }
}

const confirmDelete = (attachment, index) => {
  attachmentToDelete.value = attachment
  indexToDelete.value = index
  deleteDialog.value = true
}

const deleteFile = async () => {
  if (!attachmentToDelete.value) return

  if (isFormMode.value || !canPreview(attachmentToDelete.value)) {
    removeAttachmentLocally()
    return
  }

  deleting.value = true
  
  try {
    await axios.delete('/api/attachments/delete', {
      params: {
        model_type: props.modelType,
        model_id: props.modelId,
        attachment_id: attachmentToDelete.value.id
      }
    })

    removeAttachmentLocally()
  } catch (error) {
    console.error('Delete error:', error)
    toast.add({
      severity: 'error',
      summary: 'Delete Error',
      detail: 'Failed to delete attachment',
      life: 3000
    })
  } finally {
    deleting.value = false
    deleteDialog.value = false
    attachmentToDelete.value = null
    indexToDelete.value = null
  }
}

const removeAttachmentLocally = () => {
  const updatedAttachments = [...attachments.value]
  updatedAttachments.splice(indexToDelete.value, 1)
  attachments.value = updatedAttachments

  emit('deleted', attachmentToDelete.value)

  toast.add({
    severity: 'success',
    summary: 'Removed',
    detail: 'Attachment removed',
    life: 3000
  })
}

// Utility functions
const getFileIcon = (mimeType) => {
  if (!mimeType) return 'pi pi-file'
  
  if (mimeType.includes('pdf')) return 'pi pi-file-pdf'
  if (mimeType.includes('image')) return 'pi pi-image'
  if (mimeType.includes('word') || mimeType.includes('document')) return 'pi pi-file-word'
  if (mimeType.includes('sheet') || mimeType.includes('excel')) return 'pi pi-file-excel'
  if (mimeType.includes('video')) return 'pi pi-video'
  if (mimeType.includes('audio')) return 'pi pi-volume-up'
  
  return 'pi pi-file'
}

const canPreview = (attachment) => {
  return Boolean(attachment?.path)
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>

<style scoped>
:deep(.p-fileupload-choose) {
  width: 100%;
  justify-content: center;
}

:deep(.p-fileupload-choose:not(.p-disabled):hover) {
  background-color: #2563eb;
}

:deep(.p-button-loading) {
  opacity: 0.6;
}

.attachment-uploader {
  background-color: white;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  padding: 1rem;
}
</style>