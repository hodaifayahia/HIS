<template>
  <Dialog
    :visible="showModal"
    :modal="true"
    :header="modalTitle"
    @update:visible="closeModal"
    :style="{ width: '50vw' }"
    :breakpoints="{ '960px': '75vw', '641px': '100vw' }"
  >
    <div class="p-fluid">
      <div v-if="modalType === 'delete'">
        <p>Are you sure you want to delete agreement "<strong>{{ selectedItem.title || selectedItem.name }}</strong>"?</p>
      </div>
      <div v-else-if="modalType === 'info'">
        <p><strong>ID:</strong> {{ selectedItem.id }}</p>
        <p><strong>Title:</strong> {{ selectedItem.title || selectedItem.name }}</p>
        <p><strong>Description:</strong> {{ selectedItem.description || 'No description' }}</p>
        <p><strong>Created At:</strong> {{ formatDate(selectedItem.created_at) }}</p>
        <p v-if="selectedItem.file_path">
          <strong>Document:</strong>
          <a :href="selectedItem.file_url" target="_blank" class="p-button p-button-sm p-button-info p-ml-2">
            <i class="pi pi-file"></i> View File ({{ getFileExtension(selectedItem.file_path) }})
          </a>
        </p>
        <p v-else><strong>Document:</strong> No file attached</p>
      </div>
      <form v-else @submit.prevent="handleSave">
        <div class="p-field mb-3">
          <label for="agreementTitle">Title</label>
          <InputText id="agreementTitle" v-model="localForm.title" required />
        </div>
        <div class="p-field mb-3">
          <label for="agreementDescription">Description</label>
          <Textarea id="agreementDescription" v-model="localForm.description" rows="3" />
        </div>
        <div class="p-field mb-3">
          <label for="agreementFile">Document File</label>
          <FileUpload
            mode="basic"
            name="agreementFile"
            url="./upload"
            :auto="false"
            :chooseLabel="localForm.file ? 'Change File' : 'Choose File'"
            @select="handleFileChange"
            :customUpload="true"
          />
          <div v-if="localForm.file_path" class="p-mt-2 p-d-flex p-align-center">
            <span class="p-mr-2">Current file: {{ getFileName(localForm.file_path) }}</span>
            <Button
              type="button"
              icon="pi pi-times"
              class="p-button-sm p-button-outlined p-button-danger"
              @click="removeCurrentFile"
              label="Remove"
            />
          </div>
          <div v-if="localForm.remove_file" class="p-mt-1 p-text-danger p-text-sm">
            File will be removed on save. Upload a new file to replace.
          </div>
        </div>
      </form>
    </div>

    <template #footer>
      <Button label="Close" icon="pi pi-times" class="p-button-text" @click="closeModal" />
      <Button
        v-if="modalType === 'delete'"
        label="Delete"
        icon="pi pi-trash"
        class="p-button-danger"
        @click="$emit('delete')"
        :loading="isLoading"
      />
      <Button
        v-else-if="modalType !== 'info'"
        label="Save Changes"
        icon="pi pi-check"
        @click="handleSave"
        :loading="isLoading"
      />
    </template>
  </Dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import FileUpload from 'primevue/fileupload';

const props = defineProps({
  showModal: {
    type: Boolean,
    required: true,
  },
  modalType: {
    type: String,
    required: true, // 'add', 'edit', 'info', 'delete'
  },
  form: {
    type: Object,
    required: true, // This is the `currentForm` from the parent
  },
  selectedItem: {
    type: Object,
    default: () => ({}), // For info/delete display
  },
  isLoading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['close-modal', 'save', 'delete']);

const localForm = ref({ ...props.form });

watch(
  () => props.form,
  (newVal) => {
    localForm.value = { ...newVal };
  },
  { deep: true }
);

const modalTitle = computed(() => {
  switch (props.modalType) {
    case 'add':
      return 'Add New Agreement';
    case 'edit':
      return 'Edit Agreement';
    case 'info':
      return 'Agreement Details';
    case 'delete':
      return 'Confirm Delete';
    default:
      return 'Agreement';
  }
});

const closeModal = () => {
  emit('close-modal');
};

const handleSave = () => {
  emit('save', localForm.value);
};

const handleFileChange = (event) => {
  // PrimeVue FileUpload provides an array of files in event.files
  localForm.value.file = event.files[0];
  localForm.value.remove_file = false;
};

const removeCurrentFile = () => {
  localForm.value.file_path = null;
  localForm.value.file = null;
  localForm.value.remove_file = true;
};

const getFileExtension = (filePath) => {
  if (!filePath) return '';
  const parts = filePath.split('.');
  return parts[parts.length - 1].toUpperCase();
};

const getFileName = (filePath) => {
  if (!filePath) return '';
  const parts = filePath.split('/');
  return parts[parts.length - 1];
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-GB');
};
</script>

<style scoped>
/* No specific styles needed here for the modal itself, PrimeVue handles styling */
/* You might want to add margin-bottom for form fields if p-fluid is not enough */
.p-fluid .p-field {
  margin-bottom: 1rem;
}
</style>