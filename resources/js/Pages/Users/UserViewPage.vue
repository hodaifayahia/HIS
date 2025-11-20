<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { useToastr } from '../../Components/toster';
import Button from 'primevue/button';
import Card from 'primevue/card';
import ProgressSpinner from 'primevue/progressspinner';
import Avatar from 'primevue/avatar';
import Tag from 'primevue/tag';
import Divider from 'primevue/divider';

const route = useRoute();
const router = useRouter();
const toaster = useToastr();

const userId = route.params.id;
const user = ref(null);
const loading = ref(true);

const fetchUser = async () => {
  try {
    loading.value = true;
    const response = await axios.get(`/api/users/${userId}`);
    user.value = response.data.data || response.data;
  } catch (error) {
    toaster.error('Failed to load user details');
    console.error('Error fetching user:', error);
  } finally {
    loading.value = false;
  }
};

const getRoleColor = (role) => {
  const roleColors = {
    admin: 'info',
    doctor: 'success',
    receptionist: 'warning',
    patient: 'primary',
    nurse: 'danger',
  };
  return roleColors[role?.toLowerCase()] || 'secondary';
};

const getInitials = (name) => {
  return name
    ?.split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase() || 'U';
};

const goBack = () => {
  router.push({ name: 'admin.users.index' });
};

const goToEdit = () => {
  router.push({ 
    name: 'admin.users.edit', 
    params: { id: userId } 
  });
};

onMounted(() => {
  fetchUser();
});
</script>

<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-slate-50 tw-via-blue-50/30 tw-to-indigo-50/20 tw-p-6">
    <!-- Loading State -->
    <div v-if="loading" class="tw-flex tw-items-center tw-justify-center tw-min-h-screen">
      <div class="tw-text-center">
        <ProgressSpinner style="width: 50px; height: 50px" strokeWidth="4" />
        <p class="tw-text-slate-600 tw-mt-4">Loading user details...</p>
      </div>
    </div>

    <!-- User Details -->
    <div v-else-if="user" class="tw-max-w-4xl tw-mx-auto">
      <!-- Header -->
      <div class="tw-mb-6 tw-flex tw-items-center tw-justify-between">
        <div class="tw-flex tw-items-center tw-gap-4">
          <Button 
            icon="pi pi-arrow-left" 
            class="p-button-rounded p-button-text p-button-secondary"
            @click="goBack"
            v-tooltip.right="'Back to users'"
          />
          <div>
            <h1 class="tw-text-3xl tw-font-bold tw-bg-gradient-to-r tw-from-slate-900 tw-to-slate-700 tw-bg-clip-text tw-text-transparent">
              User Details
            </h1>
            <p class="tw-text-slate-600 tw-text-sm tw-mt-1">View user information</p>
          </div>
        </div>
        <Button 
          icon="pi pi-pencil" 
          label="Edit"
          class="p-button-primary tw-rounded-xl"
          @click="goToEdit"
        />
      </div>

      <!-- Main Profile Card -->
      <Card class="tw-mb-6 tw-shadow-lg tw-border-0">
        <template #content>
          <div class="tw-flex tw-flex-col md:tw-flex-row tw-gap-8">
            <!-- Avatar Section -->
            <div class="tw-flex tw-flex-col tw-items-center tw-gap-4">
              <Avatar
                :label="getInitials(user.name)"
                class="tw-bg-gradient-to-br tw-from-indigo-500 tw-to-purple-600 tw-text-white tw-font-semibold tw-shadow-xl"
                size="xlarge"
                shape="circle"
              />
              <Tag 
                :value="user.role" 
                :severity="getRoleColor(user.role)"
                class="tw-capitalize tw-font-semibold"
              />
            </div>

            <!-- User Info Section -->
            <div class="tw-flex-1">
              <div class="tw-space-y-4">
                <!-- Name -->
                <div>
                  <label class="tw-text-xs tw-font-semibold tw-text-slate-500 tw-uppercase tw-tracking-wide">
                    Full Name
                  </label>
                  <p class="tw-text-lg tw-font-semibold tw-text-slate-900 tw-mt-1">
                    {{ user.name }}
                  </p>
                </div>

                <!-- Email -->
                <div>
                  <label class="tw-text-xs tw-font-semibold tw-text-slate-500 tw-uppercase tw-tracking-wide">
                    Email Address
                  </label>
                  <p class="tw-text-base tw-text-slate-700 tw-mt-1 tw-break-all">
                    <i class="pi pi-envelope tw-mr-2 tw-text-blue-500"></i>
                    {{ user.email }}
                  </p>
                </div>

                <!-- Phone -->
                <div>
                  <label class="tw-text-xs tw-font-semibold tw-text-slate-500 tw-uppercase tw-tracking-wide">
                    Phone Number
                  </label>
                  <p class="tw-text-base tw-text-slate-700 tw-mt-1">
                    <i class="pi pi-phone tw-mr-2 tw-text-green-500"></i>
                    {{ user.phone || 'Not provided' }}
                  </p>
                </div>

                <!-- Role -->
                <div>
                  <label class="tw-text-xs tw-font-semibold tw-text-slate-500 tw-uppercase tw-tracking-wide">
                    Role
                  </label>
                  <p class="tw-text-base tw-text-slate-700 tw-mt-1 tw-capitalize">
                    <i class="pi pi-shield tw-mr-2 tw-text-purple-500"></i>
                    {{ user.role }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </template>
      </Card>

      <!-- Additional Details Cards -->
      <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
        <!-- System Information -->
        <Card class="tw-shadow-lg tw-border-0 tw-bg-blue-50/50">
          <template #header>
            <div class="tw-bg-gradient-to-r tw-from-blue-500 tw-to-indigo-600 tw-text-white tw-px-6 tw-py-4 tw-rounded-t-lg tw-flex tw-items-center tw-gap-3">
              <i class="pi pi-info-circle tw-text-xl"></i>
              <span class="tw-font-semibold">System Information</span>
            </div>
          </template>
          <template #content>
            <div class="tw-space-y-4">
              <div>
                <label class="tw-text-xs tw-font-semibold tw-text-slate-500 tw-uppercase">User ID</label>
                <p class="tw-text-base tw-text-slate-700 tw-font-mono tw-mt-1">{{ user.id }}</p>
              </div>
              <div>
                <label class="tw-text-xs tw-font-semibold tw-text-slate-500 tw-uppercase">Created</label>
                <p class="tw-text-base tw-text-slate-700 tw-mt-1">
                  {{ user.created_at ? new Date(user.created_at).toLocaleDateString() : 'N/A' }}
                </p>
              </div>
              <div>
                <label class="tw-text-xs tw-font-semibold tw-text-slate-500 tw-uppercase">Last Updated</label>
                <p class="tw-text-base tw-text-slate-700 tw-mt-1">
                  {{ user.updated_at ? new Date(user.updated_at).toLocaleDateString() : 'N/A' }}
                </p>
              </div>
            </div>
          </template>
        </Card>

        <!-- Financial Information -->
        <Card class="tw-shadow-lg tw-border-0 tw-bg-green-50/50">
          <template #header>
            <div class="tw-bg-gradient-to-r tw-from-green-500 tw-to-emerald-600 tw-text-white tw-px-6 tw-py-4 tw-rounded-t-lg tw-flex tw-items-center tw-gap-3">
              <i class="pi pi-dollar tw-text-xl"></i>
              <span class="tw-font-semibold">Financial Information</span>
            </div>
          </template>
          <template #content>
            <div class="tw-space-y-4">
              <div>
                <label class="tw-text-xs tw-font-semibold tw-text-slate-500 tw-uppercase">Salary</label>
                <p class="tw-text-lg tw-font-bold tw-text-green-600 tw-mt-1">
                  {{ user.salary ? `$${parseFloat(user.salary).toFixed(2)}` : 'Not set' }}
                </p>
              </div>
              <div>
                <label class="tw-text-xs tw-font-semibold tw-text-slate-500 tw-uppercase">Fiche Navette Max</label>
                <p class="tw-text-base tw-text-slate-700 tw-mt-1">
                  {{ user.fichenavatte_max || 'N/A' }} files
                </p>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <!-- Action Buttons -->
      <div class="tw-mt-8 tw-flex tw-justify-center tw-gap-4">
        <Button 
          icon="pi pi-arrow-left" 
          label="Back"
          class="p-button-outlined p-button-secondary tw-rounded-xl"
          @click="goBack"
        />
        <Button 
          icon="pi pi-pencil" 
          label="Edit User"
          class="p-button-primary tw-rounded-xl"
          @click="goToEdit"
        />
      </div>
    </div>

    <!-- Not Found State -->
    <div v-else class="tw-flex tw-items-center tw-justify-center tw-min-h-screen">
      <Card class="tw-w-full tw-max-w-md">
        <template #content>
          <div class="tw-text-center">
            <div class="tw-bg-red-100 tw-w-16 tw-h-16 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-4">
              <i class="pi pi-times tw-text-3xl tw-text-red-600"></i>
            </div>
            <h3 class="tw-text-xl tw-font-semibold tw-text-slate-900 tw-mb-2">User Not Found</h3>
            <p class="tw-text-slate-600 tw-mb-6">The user you're looking for doesn't exist.</p>
            <Button 
              label="Back to Users"
              class="p-button-primary tw-rounded-xl tw-w-full"
              @click="goBack"
            />
          </div>
        </template>
      </Card>
    </div>
  </div>
</template>
