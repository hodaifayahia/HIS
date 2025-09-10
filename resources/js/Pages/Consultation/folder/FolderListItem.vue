<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const props = defineProps({
  folder: {
    type: Object,
    required: true,
    default: () => ({
      id: null,
      name: '',
      description: '',
      templates_count: 0
    })
  },
  folderid:{
    type:Number,
    required:true
  }
});

const GoToTemplatePage = (event) => {
  event.stopPropagation();
  router.push({ name: 'admin.consultation.template', params: {
    folderid : props.folderid
  } });
};

const emit = defineEmits(['edit', 'delete']);

const handleEdit = (event) => {
  event.stopPropagation();
  emit('edit', props.folder);
};

const handleDelete = (event) => {
  event.stopPropagation();
  emit('delete', props.folder.id, props.folder.name);
};
</script>

<template>
  <div class="col-12 col-sm-6 col-lg-4 col-xl-3 mb-4">
    <div class="card h-100 folder-card border-0 shadow-sm" @click="GoToTemplatePage">
      <div class="card-body p-3">
        <div class="d-flex align-items-center gap-3 mb-3">
          <div class="folder-icon">
            <i class="fas fa-folder fa-lg text-warning"></i>
          </div>
          <div class="flex-grow-1 ml-2">
            <h6 class="mb-1 text-truncate">{{ folder.name }}</h6>
            <p class="text-muted small mb-0">{{ folder.description || 'No description' }}</p>
          </div>
        </div>
        <div class="folder-stats small text-muted d-flex gap-3 ml-2">
          <h6>
            <i class="fas fa-file-alt me-1"></i> 
            
            {{ folder.templates_count }} {{ folder.templates_count === 1 ? 'template' : 'templates' }}
          </h6>
        </div>
      </div>
      <div class="card-footer bg-white py-2 px-3 border-top">
        <div class="d-flex justify-content-end gap-2">
          <button 
            class="btn btn-sm btn-light"
            @click="handleEdit"
            title="Edit folder"
          >
            <i class="fas fa-edit"></i>
          </button>
          <button 
            class="btn btn-sm btn-light text-danger"
            @click="handleDelete"
            title="Delete folder"
          >
            <i class="fas fa-trash"></i>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.folder-card {
  transition: all 0.3s ease;
  cursor: pointer;
}

.folder-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 0.5rem 1rem rgba(34, 33, 33, 0.08) !important;
}

.folder-icon {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 193, 7, 0.1);
  border-radius: 8px;
}

.folder-card:hover .folder-icon {
  background: rgba(255, 193, 7, 0.2);
}

.folder-stats {
  opacity: 0.7;
}

.folder-card:hover .folder-stats {
  opacity: 1;
}

.btn-light {
  background: #f8f9fa;
  border-color: #f0f0f0;
}

.btn-light:hover {
  background: #e9ecef;
  border-color: #e9ecef;
}
</style>