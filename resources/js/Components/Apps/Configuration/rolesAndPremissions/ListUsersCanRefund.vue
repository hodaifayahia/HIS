<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { Bootstrap5Pagination } from 'laravel-vue-pagination';

// ✅ PrimeVue Toast imports
import { useToast } from 'primevue/usetoast';
import Toast from 'primevue/toast';

const users = ref([]);
const pagination = ref({});
const searchQuery = ref('');
const isLoading = ref(false);

// ✅ Use PrimeVue's toast composable
const toast = useToast();

const getUsers = async (page = 1) => {
    isLoading.value = true;
    try {
        const params = {
            page,
            per_page: 15,
            search: searchQuery.value
        };
        
        const res = await axios.get('/api/user-refund-permissions', { params });
        const data = res.data;
        
        users.value = data.data || [];
        pagination.value = {
            current_page: data.current_page,
            last_page: data.last_page,
            per_page: data.per_page,
            total: data.total,
            from: data.from,
            to: data.to
        };
    } catch (err) {
        console.error('Failed to load users', err);
        users.value = [];
        // ✅ Updated toast call
        toast.add({severity: 'error', summary: 'Error', detail: 'Failed to load users', life: 3000});
    } finally {
        isLoading.value = false;
    }
};

const togglePermission = async (user) => {
    try {
        if (user.can_refund) {
            // Remove permission
            await axios.delete(`/api/user-refund-permissions/${user.id}`);
            user.can_refund = false;
            // ✅ Updated toast call
            toast.add({severity: 'success', summary: 'Success', detail: `Refund permission removed from ${user.name}`, life: 3000});
        } else {
            // Grant permission
            const payload = { user_id: user.id };
            await axios.post('/api/user-refund-permissions', payload);
            user.can_refund = true;
            // ✅ Updated toast call
            toast.add({severity: 'success', summary: 'Success', detail: `Refund permission granted to ${user.name}`, life: 3000});
        }
    } catch (err) {
        console.error('Toggle permission error', err);
        // ✅ Updated toast call
        toast.add({severity: 'error', summary: 'Error', detail: err.response?.data?.message || 'Failed to update permission', life: 3000});
    }
};

const searchUsers = () => {
    getUsers(1); // Reset to page 1 when searching
};

onMounted(() => {
    getUsers();
});
</script>

<template>
  <div class="tw-p-6 tw-bg-gray-100 tw-min-h-screen">
    <div class="tw-container tw-mx-auto">
      <div class="tw-mb-6">
        <h1 class="tw-text-3xl tw-font-bold tw-text-gray-800">Users Can Refund</h1>
        <p class="tw-text-gray-600 tw-mt-2">Manage users who can approve refund transactions</p>
      </div>

      <div class="tw-flex tw-items-center tw-mb-6">
        <input 
          type="text" 
          class="tw-form-input tw-w-full tw-p-2 tw-border tw-border-gray-300 tw-rounded-md tw-shadow-sm focus:tw-border-blue-500 focus:tw-ring focus:tw-ring-blue-500 focus:tw-ring-opacity-50" 
          v-model="searchQuery"
          placeholder="Search users by name or email..." 
          :disabled="isLoading"
          @keyup.enter="searchUsers"
        />
        <button 
          @click="searchUsers" 
          :disabled="isLoading"
          class="tw-ml-2 tw-px-4 tw-py-2 tw-bg-blue-500 tw-text-white tw-rounded-md hover:tw-bg-blue-600 disabled:tw-opacity-50"
        >
          Search
        </button>
      </div>

      <div v-if="isLoading" class="tw-flex tw-justify-center tw-items-center tw-py-12">
        <div class="tw-animate-spin tw-rounded-full tw-h-12 tw-w-12 tw-border-4 tw-border-t-4 tw-border-blue-500"></div>
      </div>

      <div v-else class="tw-overflow-x-auto tw-bg-white tw-rounded-lg tw-shadow">
        <table class="tw-min-w-full tw-table-auto">
          <thead>
            <tr class="tw-bg-gray-100 tw-text-gray-600 tw-uppercase tw-text-sm tw-leading-normal">
              <th class="tw-py-3 tw-px-6 tw-text-left">Name</th>
              <th class="tw-py-3 tw-px-6 tw-text-left">Email</th>
              <th class="tw-py-3 tw-px-6 tw-text-left">Role</th>
              <th class="tw-py-3 tw-px-6 tw-text-center">Can Refund</th>
            </tr>
          </thead>
          <tbody class="tw-text-gray-600 tw-text-sm tw-font-light">
            <tr v-for="user in users" :key="user.id" class="tw-border-b tw-border-gray-200 hover:tw-bg-gray-50">
            
              <td class="tw-py-3 tw-px-6 tw-text-left">{{ user.name }}</td>
              <td class="tw-py-3 tw-px-6 tw-text-left">{{ user.email }}</td>
              <td class="tw-py-3 tw-px-6 tw-text-left">
                <span class="tw-px-2 tw-py-1 tw-text-xs tw-rounded-full" 
                      :class="{
                        'tw-bg-purple-100 tw-text-purple-800': user.role === 'admin',
                        'tw-bg-blue-100 tw-text-blue-800': user.role === 'doctor',
                        'tw-bg-green-100 tw-text-green-800': user.role === 'receptionist',
                        'tw-bg-gray-100 tw-text-gray-800': !['admin', 'doctor', 'receptionist'].includes(user.role)
                      }">
                  {{ user.role || 'N/A' }}
                </span>
              </td>
              <td class="tw-py-3 tw-px-6 tw-text-center">
                <div class="tw-relative tw-inline-block tw-w-10 tw-h-6">
                  <label>
                    <input 
                      type="checkbox" 
                      class="tw-form-checkbox tw-absolute tw-top-0 tw-left-0 tw-w-full tw-h-full tw-opacity-0 tw-cursor-pointer"
                      :checked="Boolean(user.can_refund)"
                      @change="togglePermission(user)"
                    />
                    <!-- Toggle switch background -->
                    <span 
                      class="tw-checkmark tw-absolute tw-top-0 tw-left-0 tw-right-0 tw-bottom-0 tw-rounded-full tw-transition-colors tw-duration-300"
                      :class="{ 'tw-bg-red-500': Boolean(user.can_refund), 'tw-bg-gray-200': !Boolean(user.can_refund) }"
                    ></span>
                    <!-- Toggle switch dot -->
                    <span 
                      class="tw-dot tw-absolute tw-top-1 tw-left-1 tw-w-4 tw-h-4 tw-bg-white tw-rounded-full tw-transition-transform tw-duration-300"
                      :class="{ 'tw-translate-x-4': Boolean(user.can_refund) }"
                    ></span>
                  </label>
                </div>
              </td>
            </tr>
            <tr v-if="!users.length && !isLoading">
              <td colspan="5" class="tw-text-center tw-py-10 tw-text-gray-500">
                {{ searchQuery ? 'No users found matching your search' : 'No users found' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="pagination.total > 0" class="tw-flex tw-justify-between tw-items-center tw-mt-6">
        <div class="tw-text-sm tw-text-gray-600">
          Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} users
        </div>
        <Bootstrap5Pagination 
          :data="pagination" 
          @pagination-change-page="getUsers" 
        />
      </div>
    </div>
  </div>
  <Toast />
</template>

<style scoped>
/* Custom toggle switch styles */
.tw-checkmark {
  display: block;
  cursor: pointer;
  background-color: #e5e7eb; /* gray-200 */
  border-radius: 9999px;
  transition: background-color 0.3s ease;
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
}

input[type="checkbox"]:checked ~ .tw-checkmark {
  background-color: #ef4444; /* red-500 */
}

.tw-dot {
  position: absolute;
  top: 0.25rem;
  left: 0.25rem;
  width: 1rem;
  height: 1rem;
  background-color: #ffffff;
  border-radius: 9999px;
  transition: transform 0.3s ease;
  transform: translateX(0);
}

input[type="checkbox"]:checked ~ .tw-dot {
  transform: translateX(1.5rem);
}

.tw-translate-x-4 {
  transform: translateX(1rem);
}
</style>