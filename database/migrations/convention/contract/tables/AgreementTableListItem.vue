<script setup>
import { defineProps, defineEmits, computed } from 'vue';

const props = defineProps({
  agreement: {
    type: Object,
    required: true,
  },
  contractState: {
    type: String,
    required: true,
  },
});

const emit = defineEmits(['edit', 'delete', 'showInfo']); // Removed 'viewDocument' as it's handled internally

// Computed property to check if file is PDF
const isPDF = computed(() => {
  if (!props.agreement.file_path) return false;
  const extension = getFileExtension(props.agreement.file_path).toLowerCase();
  return extension === 'pdf';
});

// Helper function to get file extension
const getFileExtension = (filePath) => {
  if (!filePath) return '';
  const parts = filePath.split('.');
  return parts[parts.length - 1].toUpperCase();
};

// Helper function to get filename from path
const getFileName = (filePath) => {
  if (!filePath) return '';
  const parts = filePath.split('/');
  return parts[parts.length - 1];
};

// Format date function
const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-GB');
};

// Download document function
const downloadDocument = () => {
  // Use the full URL from the backend or construct it
  const fileUrl = props.agreement.file_url || `/storage/${props.agreement.file_path}`;
  if (!fileUrl) return;

  const link = document.createElement('a');
  link.href = fileUrl;
  link.download = getFileName(props.agreement.file_path) || 'document';
  link.target = '_blank';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

// View document function (for PDFs)
const viewDocument = () => {
  // Use the full URL from the backend or construct it
  const fileUrl = props.agreement.file_url || `/storage/${props.agreement.file_path}`;
  if (!fileUrl) return;

  window.open(fileUrl, '_blank');
};
</script>

<template>
  
  <tr>
    <td>{{ agreement.id }}</td>
    <td>{{ agreement.name || agreement.title }}</td>
    <td>{{ formatDate(agreement.created_at) }}</td>
    <td>
      <div class="description-cell">
        <span v-if="agreement.description && agreement.description.length > 50">
          {{ agreement.description.substring(0, 50) + '...' }}
        </span>
        <span v-else>
          {{ agreement.description || 'No description' }}
        </span>
      </div>
    </td>

    <td>
      <div class="document-actions d-flex align-items-center gap-2">
        <button
          v-if="agreement.file_path"
          class="btn btn-sm btn-success"
          @click="downloadDocument"
          title="Download Document"
        >
          <i class="fas fa-download"></i>
        </button>

        <button
          v-if="agreement.file_path && isPDF"
          class="btn btn-sm btn-primary"
          @click="viewDocument"
          title="View Document"
        >
          <i class="fas fa-eye"></i>
        </button>

        <span v-if="!agreement.file_path" class="text-muted small">
          <i class="fas fa-file-slash"></i> No file
        </span>

        <span v-if="agreement.file_path" class="badge bg-secondary ms-1">
          {{ getFileExtension(agreement.file_path) }}
        </span>
      </div>
    </td>

    <td v-if="contractState === 'pending'">
      <div class="action-buttons d-flex gap-2">
        <button
          class="btn btn-sm btn-warning"
          @click="$emit('edit', agreement)"
          title="Edit Agreement"
        >
          <i class="fas fa-pencil-alt"></i>
        </button>
        <button
          class="btn btn-sm btn-danger"
          @click="$emit('delete', agreement)"
          title="Delete Agreement"
        >
          <i class="fas fa-trash-alt"></i>
        </button>
      </div>
    </td>

    <td>
      <button
        class="btn btn-sm btn-info"
        @click="$emit('showInfo', agreement)"
        title="View Details"
      >
        <i class="fas fa-info-circle me-1"></i> Details
      </button>
    </td>
  </tr>
</template>


<style scoped>
.description-cell {
  max-width: 200px;
  word-wrap: break-word;
}

.document-actions {
  min-width: 120px;
}

.action-buttons {
  min-width: 80px;
}

.badge {
  font-size: 0.7rem;
}

.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
}

.gap-2 {
  gap: 0.5rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .description-cell {
    max-width: 150px;
  }

  .document-actions,
  .action-buttons {
    flex-direction: column;
    align-items: stretch;
  }

  .btn-sm {
    font-size: 0.75rem;
    padding: 0.2rem 0.4rem;
  }
}
</style>