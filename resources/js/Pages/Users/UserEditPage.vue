<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { useToastr } from '../../Components/toster';
import Button from 'primevue/button';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';
import ProgressSpinner from 'primevue/progressspinner';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

const route = useRoute();
const router = useRouter();
const toaster = useToastr();
const primeToast = useToast();

const userId = route.params.id;
const user = ref(null);
const loading = ref(true);
const saving = ref(false);

const roles = [
  { label: 'Admin', value: 'admin' },
  { label: 'Doctor', value: 'doctor' },
  { label: 'Receptionist', value: 'receptionist' },
  { label: 'Nurse', value: 'nurse' },
  { label: 'Patient', value: 'patient' },
];

const form = ref({
  name: '',
  email: '',
  phone: '',
  role: '',
  salary: 0,
  fichenavatte_max: 0,
});

const fetchUser = async () => {
  try {
    loading.value = true;
    const response = await axios.get(`/api/users/${userId}`);
    const userData = response.data.data || response.data;
    user.value = userData;
    
    // Populate form with user data
    form.value = {
      name: userData.name || '',
      email: userData.email || '',
      phone: userData.phone || '',
      role: userData.role || '',
      salary: userData.salary || 0,
      fichenavatte_max: userData.fichenavatte_max || 0,
    };
  } catch (error) {
    toaster.error('Failed to load user details');
    console.error('Error fetching user:', error);
  } finally {
    loading.value = false;
  }
};

const saveUser = async () => {
  try {
    saving.value = true;
    
    // Validate required fields
    if (!form.value.name || !form.value.email || !form.value.role) {
      toaster.error('Please fill in all required fields');
      return;
    }

    const response = await axios.put(`/api/users/${userId}`, {
      name: form.value.name,
      email: form.value.email,
      phone: form.value.phone,
      role: form.value.role,
      salary: form.value.salary,
      fichenavatte_max: form.value.fichenavatte_max,
    });

    toaster.success('User updated successfully!');
    
    // Update local user data
    user.value = response.data.data || response.data;
    
    // Redirect back to view page after a brief delay
    setTimeout(() => {
      router.push({ 
        name: 'admin.users.show', 
        params: { id: userId } 
      });
    }, 500);
  } catch (error) {
    const message = error.response?.data?.message || 'Failed to update user';
    toaster.error(message);
    console.error('Error updating user:', error);
  } finally {
    saving.value = false;
  }
};

const goBack = () => {
  router.push({ 
    name: 'admin.users.show', 
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
        <p class="tw-text-slate-600 tw-mt-4">Loading user data...</p>
      </div>
    </div>

    <!-- Edit Form -->
    <div v-else-if="user" class="tw-max-w-3xl tw-mx-auto">
      <!-- Header -->
      <div class="tw-mb-6 tw-flex tw-items-center tw-justify-between">
        <div class="tw-flex tw-items-center tw-gap-4">
          <Button 
            icon="pi pi-arrow-left" 
            class="p-button-rounded p-button-text p-button-secondary"
            @click="goBack"
            v-tooltip.right="'Back'"
          />
          <div>
            <h1 class="tw-text-3xl tw-font-bold tw-bg-gradient-to-r tw-from-slate-900 tw-to-slate-700 tw-bg-clip-text tw-text-transparent">
              Edit User
            </h1>
            <p class="tw-text-slate-600 tw-text-sm tw-mt-1">Update user information</p>
          </div>
        </div>
      </div>

      <!-- Form Card -->
      <Card class="tw-shadow-lg tw-border-0">
        <template #content>
          <form @submit.prevent="saveUser" class="tw-space-y-6">
            <!-- Name Field -->
            <div>
              <label class="tw-block tw-text-sm tw-font-semibold tw-text-slate-700 tw-mb-2">
                Full Name *
              </label>
              <InputText
                v-model="form.name"
                placeholder="Enter full name"
                class="tw-w-full tw-px-4 tw-py-2.5 tw-border tw-border-slate-200 tw-rounded-lg focus:tw-border-blue-500 focus:tw-ring-2 focus:tw-ring-blue-500/20 focus:tw-outline-none tw-transition-all"
              />
            </div>

            <!-- Email Field -->
            <div>
              <label class="tw-block tw-text-sm tw-font-semibold tw-text-slate-700 tw-mb-2">
                Email Address *
              </label>
              <InputText
                v-model="form.email"
                type="email"
                placeholder="Enter email address"
                class="tw-w-full tw-px-4 tw-py-2.5 tw-border tw-border-slate-200 tw-rounded-lg focus:tw-border-blue-500 focus:tw-ring-2 focus:tw-ring-blue-500/20 focus:tw-outline-none tw-transition-all"
              />
            </div>

            <!-- Phone Field -->
            <div>
              <label class="tw-block tw-text-sm tw-font-semibold tw-text-slate-700 tw-mb-2">
                Phone Number
              </label>
              <InputText
                v-model="form.phone"
                placeholder="Enter phone number"
                class="tw-w-full tw-px-4 tw-py-2.5 tw-border tw-border-slate-200 tw-rounded-lg focus:tw-border-blue-500 focus:tw-ring-2 focus:tw-ring-blue-500/20 focus:tw-outline-none tw-transition-all"
              />
            </div>

            <!-- Role Field -->
            <div>
              <label class="tw-block tw-text-sm tw-font-semibold tw-text-slate-700 tw-mb-2">
                Role *
              </label>
              <Dropdown
                v-model="form.role"
                :options="roles"
                optionLabel="label"
                optionValue="value"
                placeholder="Select a role"
                class="tw-w-full"
              />
            </div>

            <!-- Salary Field -->
            <div>
              <label class="tw-block tw-text-sm tw-font-semibold tw-text-slate-700 tw-mb-2">
                Salary
              </label>
              <InputNumber
                v-model="form.salary"
                :min="0"
                :max="999999.99"
                mode="currency"
                currency="USD"
                placeholder="Enter salary amount"
                class="tw-w-full"
              />
            </div>

            <!-- Fiche Navette Max Field -->
            <div>
              <label class="tw-block tw-text-sm tw-font-semibold tw-text-slate-700 tw-mb-2">
                Max Fiche Navette
              </label>
              <InputNumber
                v-model="form.fichenavatte_max"
                :min="0"
                :max="9999"
                placeholder="Maximum fiche navette files allowed"
                class="tw-w-full"
              />
            </div>

            <!-- Action Buttons -->
            <div class="tw-flex tw-gap-4 tw-justify-between tw-pt-6 tw-border-t tw-border-slate-200">
              <Button 
                icon="pi pi-times" 
                label="Cancel"
                class="p-button-outlined p-button-secondary tw-rounded-lg"
                @click="goBack"
                :loading="saving"
              />
              <Button 
                icon="pi pi-check" 
                label="Save Changes"
                class="p-button-primary tw-rounded-lg"
                type="submit"
                :loading="saving"
              />
            </div>
          </form>
        </template>
      </Card>
    </div>

    <!-- Toast -->
    <Toast />
  </div>
</template>
