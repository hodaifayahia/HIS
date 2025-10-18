<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { useToastr } from '../../../../../Components/toster'; // Re-import original toaster
import { Bootstrap5Pagination } from 'laravel-vue-pagination'; // Re-import Bootstrap pagination

const users = ref([]);
const modalitiesList = ref([]); // Replace doctorsList with modalitiesList
const pagination = ref({}); // For Bootstrap5Pagination
const searchQuery = ref('');
const isLoading = ref(false);
const toaster = useToastr(); // Re-initialize original toaster

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
        toaster.error('Failed to initialize data.'); // Use original toaster
    } finally {
        isLoading.value = false;
    }
});

const getModalitiesList = async () => {
    try {
        const response = await axios.get('/api/modalities'); // Fetch modalities
        if (response.data?.data) {
            modalitiesList.value = response.data.data;
        }
    } catch (error) {
        toaster.error('Failed to fetch modalities list'); // Use original toaster
        console.error('Error fetching modalities:', error);
        modalitiesList.value = [];
    }
};

const getUsers = async (page = 1) => { // Bootstrap5Pagination directly provides page number
    isLoading.value = true;
    try {
        const response = await axios.get(`/api/users/receptionist?page=${page}`);
        if (response.data?.data) {
            users.value = response.data.data.map(initializeUserPermissions);
            pagination.value = response.data.meta; // Set pagination object
            await fetchPermissions();
        }
    } catch (error) {
        toaster.error('Failed to fetch users'); // Use original toaster
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
        const response = await axios.get('/api/modality-user-permissions/ability', {
            params: { modality_id: null }
        });

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
        toaster.error('Failed to fetch permissions'); // Use original toaster
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
        isLoading.value = true;
        await axios.post('/api/modality-user-permissions', {
            modality_id: modalityId,
            user_id: userId,
            is_able_to_force: checked ? 1 : 0,
        });
        toaster.success(`Permission ${checked ? 'granted' : 'removed'} successfully`); // Use original toaster
    } catch (error) {
        toaster.error('Failed to update permission'); // Use original toaster
        console.error('Error updating permission:', error);
        // Revert to original state if API call fails
        if (userIndex !== -1 && users.value[userIndex].modalityPermissions?.[modalityId]) {
            users.value[userIndex].modalityPermissions[modalityId].is_able_to_force = originalState;
        }
    } finally {
        isLoading.value = false;
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
                    await getUsers(); // If search query is empty, get all users
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
                    // When searching, Laravel's search endpoint might not return full pagination meta.
                    // If it does, great. If not, we'll simulate a single page for the search results.
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
                toaster.error('Failed to search users'); // Use original toaster
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
                <h2 class="h5 mb-0">Receptionist Permissions</h2>
                <div class="input-group w-auto search-input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fas fa-search"></i> </span>
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
                <div v-if="isLoading" class="d-flex justify-content-center align-items-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                <div v-else class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="bg-light">
                            <tr>
                                <th scope="col">Image</th>
                                <th scope="col">Name</th>
                                <th scope="col">Username</th>
                                <th scope="col" class="text-center">Modality Permissions</th>
                            </tr>
                        </thead>
                        <tbody v-if="users.length > 0">
                            <tr v-for="user in users" :key="user.id">
                                <td>
                                    <img
                                        style="width: 50px; height: 50px; border-radius: 50%;"
                                        v-if="user.avatar"
                                        :src="user.avatar"
                                        alt="User Avatar"
                                        class="user-avatar"
                                    />
                                    <div v-else class="user-initials-avatar bg-secondary text-white">
                                        {{ user.name.charAt(0).toUpperCase() }}
                                    </div>
                                </td>
                                <td>{{ user.name }}</td>
                                <td>{{ user.email }}</td>
                                <td>
                                    <div v-if="modalitiesList.length" class="d-flex flex-wrap justify-content-center gap-2 p-2">
                                        <div
                                            v-for="modality in modalitiesList"
                                            :key="modality.id"
                                            class="modality-permission-item card border shadow-sm"
                                        >
                                            <div class="card-body d-flex flex-column align-items-center p-2">
                                                <small class="card-title text-muted text-nowrap mb-1">{{ modality.name }}</small>
                                                <div class="form-check form-switch">
                                                    <input
                                                        class="form-check-input"
                                                        type="checkbox"
                                                        :id="`user-${user.id}-modality-${modality.id}`"
                                                        :checked="user.modalityPermissions?.[modality.id]?.is_able_to_force"
                                                        @change="handlePermissionChange(user.id, modality.id, $event.target.checked)"
                                                        :disabled="isLoading"
                                                    >
                                                    <label class="form-check-label visually-hidden" :for="`user-${user.id}-modality-${modality.id}`">
                                                        Grant Access for {{ modality.name }}
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="text-center text-muted p-3">
                                        No modalities available to assign permissions.
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        <tbody v-else>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    <i class="fas fa-info-circle me-2"></i> No users found matching your criteria.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
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
/* Update styles as needed */
</style>