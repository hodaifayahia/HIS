<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { useToastr } from '../../../../../Components/toster';
import { Bootstrap5Pagination } from 'laravel-vue-pagination';

const users = ref([]);
const modalitiesList = ref([]);
const pagination = ref({});
const searchQuery = ref('');
const isLoading = ref(false);
const toaster = useToastr();

// Initialize users with modalityPermissions
const initializeUserPermissions = (user) => ({
    ...user,
    modalityPermissions: modalitiesList.value.reduce((acc, modality) => {
        acc[modality.id] = {
            ...modality,
            is_able_to_force: false
        };
        return acc;
    }, {})
});

onMounted(async () => {
    isLoading.value = true;
    try {
        // First get modalities list
        await getModalitiesList();
        // Then get users with permissions
        await getUsers();
    } catch (error) {
        console.error('Initialization error:', error);
        toaster.error('Failed to initialize data.');
    } finally {
        isLoading.value = false;
    }
});

const getModalitiesList = async () => {
    try {
        const response = await axios.get('/api/modalities');
        if (response.data?.data) {
            modalitiesList.value = response.data.data;
        }
    } catch (error) {
        toaster.error('Failed to fetch modalities list');
        console.error('Error fetching modalities:', error);
        modalitiesList.value = [];
    }
};

const getUsers = async (page = 1) => {
    isLoading.value = true;
    try {
        const response = await axios.get(`/api/users/receptionist?page=${page}`);
        if (response.data?.data) {
            users.value = response.data.data.map(initializeUserPermissions);
            pagination.value = response.data.meta;
            await fetchPermissions();
        }
    } catch (error) {
        toaster.error('Failed to fetch users');
        console.error('Error fetching users:', error);
        users.value = [];
        pagination.value = {};
    } finally {
        isLoading.value = false;
    }
};

const fetchPermissions = async () => {
    if (!users.value.length || !modalitiesList.value.length) return;

    try {
        const response = await axios.get('/api/modality-user-permissions/ability');

        if (response.data?.success && response.data?.data) {
            const permissionsMap = {};
            response.data.data.forEach(permission => {
                if (!permissionsMap[permission.user_id]) {
                    permissionsMap[permission.user_id] = {};
                }
                permissionsMap[permission.user_id][permission.modality_id] = {
                    is_able_to_force: Boolean(permission.is_able_to_force)
                };
            });

            users.value = users.value.map(user => ({
                ...user,
                modalityPermissions: modalitiesList.value.reduce((acc, modality) => {
                    acc[modality.id] = {
                        ...modality,
                        is_able_to_force: Boolean(permissionsMap[user.id]?.[modality.id]?.is_able_to_force)
                    };
                    return acc;
                }, {})
            }));
        }
    } catch (error) {
        toaster.error('Failed to fetch permissions');
        console.error('Error fetching permissions:', error);
    }
};

const handlePermissionChange = async (userId, modalityId, checked) => {
    if (!userId || !modalityId) return;

    // Optimistically update the UI
    const userIndex = users.value.findIndex(u => u.id === userId);
    let originalState;
    if (userIndex !== -1 && users.value[userIndex].modalityPermissions?.[modalityId]) {
        originalState = users.value[userIndex].modalityPermissions[modalityId].is_able_to_force;
        users.value[userIndex].modalityPermissions[modalityId].is_able_to_force = checked;
    }

    try {
        const loadingIndex = users.value.findIndex(u => u.id === userId);
        if (loadingIndex !== -1) {
            users.value[loadingIndex].loading = true;
        }

        await axios.post('/api/modality-user-permissions', {
            modality_id: modalityId,
            user_id: userId,
            is_able_to_force: checked
        });
        
        toaster.success(`Permission ${checked ? 'granted' : 'removed'} successfully`);
    } catch (error) {
        toaster.error('Failed to update permission');
        console.error('Error updating permission:', error);
        
        // Revert to original state if API call fails
        if (userIndex !== -1 && users.value[userIndex].modalityPermissions?.[modalityId]) {
            users.value[userIndex].modalityPermissions[modalityId].is_able_to_force = originalState;
        }
    } finally {
        const loadingIndex = users.value.findIndex(u => u.id === userId);
        if (loadingIndex !== -1) {
            users.value[loadingIndex].loading = false;
        }
    }
};

const debouncedSearch = (() => {
    let timeout;
    return () => {
        clearTimeout(timeout);
        timeout = setTimeout(async () => {
            try {
                isLoading.value = true;
                if (!searchQuery.value?.trim()) {
                    await getUsers();
                    return;
                }
                
                const response = await axios.get('/api/users/search', {
                    params: {
                        query: searchQuery.value,
                        role: 'receptionist'
                    },
                });
                
                if (response.data?.data) {
                    users.value = response.data.data.map(initializeUserPermissions);
                    pagination.value = {
                        current_page: 1,
                        last_page: 1,
                        from: 1,
                        to: response.data.data.length,
                        total: response.data.data.length,
                        links: []
                    };
                    await fetchPermissions();
                } else {
                    users.value = [];
                    pagination.value = {};
                }
            } catch (error) {
                toaster.error('Failed to search users');
                console.error('Error searching users:', error);
            } finally {
                isLoading.value = false;
            }
        }, 300);
    };
})();

watch(searchQuery, debouncedSearch);
</script>

<template>
    <div class="container mt-5">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0">
                    <i class="fas fa-users-cog me-2"></i>
                    Modality Force Permissions
                </h2>
                <div class="input-group w-auto search-input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fas fa-search"></i>
                    </span>
                    <input
                        type="text"
                        class="form-control border-start-0"
                        v-model="searchQuery"
                        placeholder="Search users..."
                        :disabled="isLoading"
                        @keyup.enter="debouncedSearch"
                    />
                </div>
            </div>
            
            <div class="card-body">
                <!-- Loading State -->
                <div v-if="isLoading" class="d-flex justify-content-center align-items-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <span class="ms-3">Loading users and permissions...</span>
                </div>
                
                <!-- Main Content -->
                <div v-else class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="bg-light">
                            <tr>
                                <th scope="col" class="text-center" style="width: 80px;">Avatar</th>
                                <th scope="col">Name</th>
                                <th scope="col">Email</th>
                                <th scope="col" class="text-center">Modality Force Permissions</th>
                            </tr>
                        </thead>
                        <tbody v-if="users.length > 0">
                            <tr v-for="user in users" :key="user.id">
                                <!-- Avatar Column -->
                                <td class="text-center">
                                    <img
                                        v-if="user.avatar"
                                        :src="user.avatar"
                                        alt="User Avatar"
                                        class="rounded-circle"
                                        style="width: 50px; height: 50px; object-fit: cover;"
                                    />
                                    <div 
                                        v-else 
                                        class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center mx-auto"
                                        style="width: 50px; height: 50px; font-size: 20px; font-weight: bold;"
                                    >
                                        {{ user.name.charAt(0).toUpperCase() }}
                                    </div>
                                </td>
                                
                                <!-- Name Column -->
                                <td class="align-middle">
                                    <div class="fw-bold">{{ user.name }}</div>
                                    <small class="text-muted">{{ user.role || 'Receptionist' }}</small>
                                </td>
                                
                                <!-- Email Column -->
                                <td class="align-middle">{{ user.email }}</td>
                                
                                <!-- Permissions Column -->
                                <td class="align-middle">
                                    <div v-if="modalitiesList.length" class="d-flex flex-wrap justify-content-center gap-2 p-2">
                                        <div
                                            v-for="modality in modalitiesList"
                                            :key="modality.id"
                                            class="permission-card card border shadow-sm"
                                            style="min-width: 120px;"
                                        >
                                            <div class="card-body d-flex flex-column align-items-center p-2">
                                                <small class="card-title text-muted text-nowrap mb-2 text-center" style="font-size: 0.75rem;">
                                                    {{ modality.name }}
                                                </small>
                                                <div class="form-check form-switch">
                                                    <input
                                                        class="form-check-input"
                                                        type="checkbox"
                                                        :id="`user-${user.id}-modality-${modality.id}`"
                                                        :checked="user.modalityPermissions?.[modality.id]?.is_able_to_force"
                                                        @change="handlePermissionChange(user.id, modality.id, $event.target.checked)"
                                                        :disabled="user.loading"
                                                    >
                                                    <label class="form-check-label visually-hidden" :for="`user-${user.id}-modality-${modality.id}`">
                                                        Grant Force Access for {{ modality.name }}
                                                    </label>
                                                </div>
                                                <small 
                                                    class="text-muted mt-1" 
                                                    style="font-size: 0.65rem;"
                                                    :class="{
                                                        'text-success': user.modalityPermissions?.[modality.id]?.is_able_to_force,
                                                        'text-secondary': !user.modalityPermissions?.[modality.id]?.is_able_to_force
                                                    }"
                                                >
                                                    {{ user.modalityPermissions?.[modality.id]?.is_able_to_force ? 'Can Force' : 'No Access' }}
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="text-center text-muted p-3">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        No modalities available to assign permissions.
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        <tbody v-else>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-5">
                                    <i class="fas fa-info-circle me-2 fs-4"></i>
                                    <div class="mt-2">No users found matching your criteria.</div>
                                    <small class="text-muted">Try adjusting your search terms or check if users exist.</small>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div v-if="users.length > 0 && pagination.last_page > 1" class="d-flex justify-content-center mt-4">
                    <Bootstrap5Pagination
                        :data="pagination"
                        @pagination-change-page="getUsers"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.search-input-group {
    max-width: 300px;
}

.permission-card {
    transition: all 0.2s ease;
}

.permission-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15) !important;
}

.form-check-input:checked {
    background-color: #198754;
    border-color: #198754;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
}

.card-header {
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

@media (max-width: 768px) {
    .permission-card {
        min-width: 100px;
    }
    
    .search-input-group {
        max-width: 200px;
    }
}
</style>