<script setup>
import { ref } from 'vue';
import { useToastr } from '../../Components/toster';
import { readFileAsArrayBuffer } from '../utils/fileReaders'; // Adjust the import path as needed

const toaster = useToastr();

const emit = defineEmits(['fileUploaded']);

const handleFileUpload = async (event) => {
  const file = event.target.files[0];
  if (!file) return;

  if (file.type !== 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
    toaster.error('Please upload a .docx file.');
    return;
  }

  // Emit initial progress
  emit('fileUploaded', { file: file, progress: 0 });

  try {
    // Simulate progress for reading/processing
    for (let i = 1; i <= 9; i++) {
      await new Promise(resolve => setTimeout(resolve, 50)); // Simulate work
      emit('fileUploaded', { file: file, progress: i * 10 });
    }

    // Actual file processing will happen in the parent (DocumentEditor)
    // Here we just emit the file, and the parent will handle its type (template/regular docx)
    emit('fileUploaded', { file: file, progress: 100 });

  } catch (error) {
    console.error('File upload/processing error:', error);
    toaster.error('Failed to process file.');
    emit('fileUploaded', { file: null, progress: 0 }); // Reset on error
  } finally {
    event.target.value = ''; // Clear the input
  }
};
</script>

<template>
  <input
    type="file"
    @change="handleFileUpload"
    accept=".docx"
    class="hidden-file-input"
    id="documentUpload"
  />
  <label for="documentUpload" class="premium-btn premium-upload-btn">
    ⬆️ Upload .docx
  </label>
</template>

<style scoped>
.hidden-file-input {
  display: none;
}

.premium-upload-btn {
  padding: 8px 15px;
  border: 1px solid #ccc;
  border-radius: 5px;
  background-color: #f0f0f0;
  cursor: pointer;
  font-size: 0.9em;
  transition: background-color 0.2s, border-color 0.2s;
  display: flex;
  align-items: center;
  gap: 5px;
}

.premium-upload-btn:hover {
  background-color: #e0e0e0;
  border-color: #bbb;
}
</style>