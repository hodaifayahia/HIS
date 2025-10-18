C<script setup>
import { ref } from 'vue';

const props = defineProps({
  form: {
    type: Object,
    required: true
  },
  doctors: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  },
  mimeTypeOptions: {
    type: Array,
    required: true
  }
});

const emit = defineEmits(['update:form', 'file-upload']);

const wordFile = ref(null);
const uploadProgress = ref(0);

const handleFileUpload = async (event) => {
  emit('file-upload', event);
};
</script>

<template>
  <div class="premium-card">
    <div class="premium-card-header">
      <i class="fas fa-pencil-alt me-2"></i>Template Details
    </div>
    <div class="premium-card-body">
      <div class="premium-form-group">
        <label for="templateName" class="premium-label">Template Name *</label>
        <input 
          type="text" 
          class="premium-input" 
          id="templateName" 
          v-model="form.name"
          @input="$emit('update:form', form)"
          placeholder="Enter a descriptive name for your template" 
          required 
        />
      </div>

      <div class="premium-form-group">
        <label for="templateDescription" class="premium-label">Description</label>
        <textarea 
          class="premium-textarea" 
          id="templateDescription" 
          v-model="form.description"
          @input="$emit('update:form', form)"
          placeholder="Add a detailed description of this template's purpose" 
          rows="3"
        ></textarea>
      </div>

      <div class="premium-row">
        <div class="premium-col">
          <label for="doctorSelect" class="premium-label">Associated Doctor</label>
          <div class="premium-select-wrapper">
            <select 
              class="premium-select" 
              id="doctorSelect" 
              v-model="form.doctor_id"
              @change="$emit('update:form', form)"
            >
              <option value="">No specific doctor</option>
              <option v-for="doctor in doctors" :key="doctor.id" :value="doctor.id">
                {{ doctor.name }}
              </option>
            </select>
            <i class="fas fa-chevron-down"></i>
          </div>
          <div v-if="loading && !doctors.length" class="premium-loading">
            <span class="premium-spinner"></span>
            <span class="ms-2">Loading doctors...</span>
          </div>
        </div>

        <div class="premium-col">
          <label for="mimeType" class="premium-label">Document Format</label>
          <div class="premium-select-wrapper">
            <select 
              class="premium-select" 
              id="mimeType" 
              v-model="form.mime_type"
              @change="$emit('update:form', form)"
            >
              <option v-for="option in mimeTypeOptions" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </select>
            <i class="fas fa-chevron-down"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="premium-card mt-4">
      <div class="premium-card-header">
        <i class="fas fa-cloud-upload-alt me-2"></i>Document Upload
      </div>
      <div class="premium-card-body">
        <div class="premium-form-group">
          <label class="premium-label">Upload Word Document</label>
          <div class="premium-upload-container">
            <input 
              type="file" 
              id="wordFileInput" 
              class="premium-file-input" 
              accept=".docx,.doc"
              @change="handleFileUpload" 
            />
            <label for="wordFileInput" class="premium-file-label">
              <i class="fas fa-cloud-upload-alt me-2"></i>
              {{ wordFile ? wordFile.name : 'Drop your Word document here or click to browse' }}
            </label>
          </div>

          <div v-if="uploadProgress > 0" class="premium-progress-container mt-3">
            <div class="premium-progress-text">Uploading: {{ uploadProgress }}%</div>
            <div class="premium-progress">
              <div class="premium-progress-bar" :style="{ width: uploadProgress + '%' }"></div>
            </div>
          </div>

          <div class="premium-help-text">
            <i class="fas fa-info-circle me-1"></i>
            Upload a Word document to create your template. You'll be able to add placeholders in the next step.
          </div>
        </div>
      </div>
    </div>
  </div>
</template>