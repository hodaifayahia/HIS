<script setup>
import { defineProps, defineEmits, computed } from 'vue';
import { useRouter } from 'vue-router';


const props = defineProps({
  workspaceid: {
    type: [Number, String],
    required: true
  },
  workspace: {
    type: Object,
    required: true
  }
});
const router = useRouter();


const emit = defineEmits(['edit', 'delete', 'view']);

const openWorkpace = async () => {
  try {
    // Emitting 'view' event for consistency with FolderListItem,
    // though the router push is also fine.
    // If you want to use the router directly, you can remove the emit('view')
    // and potentially rename this function to clarify its direct navigation.

    router.push({
      name: 'admin.consultations.ConsultationWorkspaceDetails',
      params: {
        id: props.workspace.id,
      }
    });
  } catch (err) {
    console.error('Error navigating to ConsultationWorkspace Details:', err);
    // Removed toaster here as it's not present in the original FolderListItem's equivalent function
  }

}

// Computed properties
const formattedDate = computed(() => {
  if (!props.workspace.last_accessed) return 'Never accessed';

  const date = new Date(props.workspace.last_accessed);
  // Using toLocaleDateString for a standard date format, similar to how a 'last modified' might be displayed
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
});

const truncatedDescription = computed(() => {
  if (!props.workspace.description) return '';
  return props.workspace.description.length > 80 // Adjusted length for potentially less space
    ? props.workspace.description.substring(0, 80) + '...'
    : props.workspace.description;
});

const statusColor = computed(() => {
  return props.workspace.is_archived ? 'warning' : 'success';
});

const statusIcon = computed(() => {
  return props.workspace.is_archived ? 'fas fa-archive' : 'fas fa-folder-open';
});

// Event handlers
const handleEdit = (event) => {
  event.stopPropagation();
  emit('edit', props.workspace);
};

const handleDelete = (event) => {
  event.stopPropagation();
  emit('delete', props.workspace.id, props.workspace.name);

};

</script>

<template>
  <div class="col-12 col-sm-6 col-lg-4 col-xl-3 mb-4">
    <div
      class="card h-100 workspace-card border-0 shadow-sm"
      @click="openWorkpace()"
      :class="{ 'archived-card': workspace.is_archived }"
    >
      <div class="card-body p-3 d-flex flex-column">
        <div class="d-flex align-items-center gap-3 mb-3 ">
          <div class="workspace-icon mr-2" :class="`bg-${statusColor}`">
            <i :class="statusIcon" class="text-white"></i>
          </div>
          <div class="flex-grow-1 min-w-0">
            <h6 class="mb-1 text-truncate" :title="workspace.name">
              {{ workspace.name }} 
            </h6>
            <p
              class="text-muted small mb-0"
              :title="workspace.description"
            >
              {{ truncatedDescription || 'No description provided' }}
            </p>
          </div>
        </div>
        <div>{{ workspace.workspaces_count }} Consulation</div>

        <div class="workspace-stats small text-muted d-flex gap-3 mt-auto">
         
          </div>
      </div>
      <div class="card-footer bg-white py-2 px-3 border-top">
        <div class="d-flex justify-content-end gap-2">
          <button
            class="btn btn-sm btn-light"
            @click="handleEdit"
            title="Edit workspace"
            type="button"
          >
            <i class="fas fa-edit"></i>
          </button>
          <button
            class="btn btn-sm btn-light text-danger"
            @click="handleDelete"
            title="Delete workspace"
            type="button"
          >
            <i class="fas fa-trash"></i>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.workspace-card {
  transition: all 0.3s ease;
  cursor: pointer;
  border-radius: 12px !important; /* Consistent with parent component's card */
  /* Remove position: relative, overflow, background, backdrop-filter, and ::before pseudo-element */
  /* These were specific to the previous design and not present in FolderListItem */
}

.workspace-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 0.5rem 1rem rgba(34, 33, 33, 0.08) !important; /* Consistent with FolderListItem hover */
}

.archived-card {
  opacity: 0.8;
  /* Remove specific ::before background for archived, let the primary card style handle it if needed */
}

.workspace-icon {
  width: 40px; /* Consistent size with FolderListItem */
  height: 40px; /* Consistent size with FolderListItem */
  border-radius: 8px; /* Consistent with FolderListItem */
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(16, 205, 98, 0.1); /* Default matching folder-icon */
  /* Removed transition, position: relative, and hover transform/shadow to match FolderListItem's simplicity */
}

/* Specific background for status icons, applied via class binding */
.workspace-icon.bg-success {
  background: rgba(5, 228, 57, 0.8) !important; /* Lighter tint for icons */
}
.workspace-icon.bg-warning {
  background: rgba(255, 193, 7, 0.8) !important; /* Lighter tint for icons */
}

/* Icon color inside the workspace-icon */
.workspace-icon .fa-folder-open {
  color: #28a745; /* Success color for active */
}
.workspace-icon .fa-archive {
  color: #ffc107; /* Warning color for archived */
}


.workspace-card:hover .workspace-icon {
  background: rgba(255, 193, 7, 0.9); /* Hover effect for icon, similar to FolderListItem */
}
.workspace-card:hover .workspace-icon.bg-success {
  background: rgba(40, 167, 69, 0.9) !important;
}
.workspace-card:hover .workspace-icon.bg-warning {
  background: rgba(255, 193, 7, 0.9) !important;
}


.card-title {
  color: #2c3e50; /* Keep consistent with previous title color */
  font-weight: 600; /* Adjusted to be consistent with FolderListItem's h6 */
  font-size: 1rem; /* Adjusted to be consistent with FolderListItem's h6 */
  line-height: 1.3;
}

/* Removed specific badge styles and badge container, as FolderListItem uses simpler text */
/* If you still want a badge, consider a simpler `badge-pill text-bg-success` or similar Bootstrap 5 class */

.description-text {
  line-height: 1.5;
  color: #6c757d;
}

/* Action buttons moved to footer and styled similar to FolderListItem */
.action-buttons {
  /* Removed opacity and transition as buttons are always visible in footer */
}

.btn-light {
  background: #f8f9fa; /* Consistent with FolderListItem */
  border-color: #f0f0f0; /* Consistent with FolderListItem */
  border-radius: 8px !important; /* Smaller radius for action buttons */
  width: 32px; /* Fixed size for aesthetic consistency */
  height: 32px; /* Fixed size for aesthetic consistency */
  display: flex; /* To center icon */
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.btn-light:hover {
  background: #e9ecef; /* Consistent with FolderListItem */
  border-color: #e9ecef; /* Consistent with FolderListItem */
  transform: none; /* Remove transform on hover */
  box-shadow: none; /* Remove box-shadow on hover */
}

.workspace-stats {
  opacity: 0.7; /* Consistent with FolderListItem's folder-stats */
}

.workspace-card:hover .workspace-stats {
  opacity: 1; /* Consistent with FolderListItem's folder-stats */
}


/* Removed hover-overlay, hover-content, and associated styles not present in FolderListItem */
/* Removed card-header-section, description-section, card-footer-section specific z-index/positioning */

/* Animation for new cards (keeping this as it's a nice UX touch) */
@keyframes slideInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.workspace-card {
  animation: slideInUp 0.6s ease-out;
}

/* Stagger animation for multiple cards */
.workspace-card:nth-child(1) { animation-delay: 0.1s; }
.workspace-card:nth-child(2) { animation-delay: 0.2s; }
.workspace-card:nth-child(3) { animation-delay: 0.3s; }
.workspace-card:nth-child(4) { animation-delay: 0.4s; }
.workspace-card:nth-child(5) { animation-delay: 0.5s; }
.workspace-card:nth-child(6) { animation-delay: 0.6s; }

/* Mobile Responsive */
@media (max-width: 768px) {
  .workspace-card {
    margin-bottom: 1rem;
  }
}
</style>