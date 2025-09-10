<script setup>
import { useToastr } from '../../../Components/toster';
import { useSweetAlert } from '../../../Components/useSweetAlert';
import { useRouter } from 'vue-router';

const props = defineProps({
    placeholder: {
        type: Object,
        required: true
    },
});
const router = useRouter();

const emit = defineEmits(['edit', 'delete']);

const swal = useSweetAlert();
const toaster = useToastr();

const handleEdit = () => {
    emit('edit', props.placeholder);
};

const goToAttributes = (placeholderId) => {
    router.push({ name: 'admin.section.attributes', params: { id: placeholderId } });
};

const handleDelete = async (placeholder) => {
    try {
        const result = await swal.fire({
            title: 'Are you sure?',
            text: `Delete section "${placeholder.name}"? This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d'
        });

        if (result.isConfirmed) {
            emit('delete', placeholder.id, placeholder.name);
        }
    } catch (err) {
        toaster.error(err.response?.data?.message || 'Failed to delete section');
    }
};
</script>

<template>
    <div class="col-sm-6 col-md-4 col-lg-3">
        <div class="placeholder-card card shadow-sm h-100" @click="goToAttributes(placeholder.id)">
            <div class="card-body d-flex flex-column p-4">
                <div class="d-flex align-items-start mb-3">
                    <div class="placeholder-icon d-flex align-items-center justify-content-center flex-shrink-0 me-3">
                        <i class="fas fa-file-alt fa-2x text-primary"></i>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <h5 class="card-title fw-bold mb-1">{{ placeholder.name }}</h5>
                        <p class="text-muted small mb-0">
                            <span v-if="placeholder.specialization">{{ placeholder.specialization.name }}</span>
                            <span v-else class="fst-italic">No Specialization</span>
                        </p>
                    </div>
                </div>
                <div class="description-text mb-3 text-secondary small">
                    {{ placeholder.description || 'No description provided.' }}
                </div>
                <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center">
                    <span class="text-muted small fst-italic text-truncate">
                        Doctor: 
                        <span v-if="placeholder.doctor">{{ placeholder.doctor.name }}</span>
                        <span v-else>N/A</span>
                    </span>
                    <div class="btn-group">
                        <button 
                            class="btn btn-sm btn-light" 
                            @click.stop="handleEdit"
                            title="Edit Section"
                        >
                            <i class="fas fa-edit"></i>
                        </button>
                        <button 
                            class="btn btn-sm btn-light text-danger" 
                            @click.stop="handleDelete(placeholder)"
                            title="Delete Section"
                        >
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.placeholder-card {
    transition: all 0.3s ease;
    cursor: pointer;
    border-radius: 12px;
    border: 1px solid #e9ecef;
    background-color: #ffffff;
}

.placeholder-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 1rem 2rem rgba(0, 0, 0, 0.08) !important;
}

.placeholder-icon {
    width: 45px;
    height: 45px;
    margin-right: 5px;
    border-radius: 10px;
    background: rgba(0, 123, 255, 0.1);
    color: #007bff;
}

.card-title {
    font-size: 1rem;
    line-height: 1.4;
    /* Remove 'white-space: nowrap' and 'text-overflow: ellipsis' to allow wrapping */
    overflow-wrap: break-word; /* Ensures long words wrap */
    word-wrap: break-word; /* Legacy property for word-wrap */
    white-space: normal; /* Override nowrap */
}

.description-text {
    display: -webkit-box;
    -webkit-line-clamp: 4;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    min-height: 3rem; /* Consistent height */
}

.btn-group .btn {
    border-radius: 8px;
}

.btn-light {
    background-color: #f8f9fa;
    border-color: #e9ecef;
}

.btn-light:hover {
    background-color: #e2e6ea;
}

/* Retain the text-truncate helper class for other elements that need it */
.text-truncate {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>