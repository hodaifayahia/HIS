<template>
  <div class="admission-edit">
    <div class="row">
      <div class="col-md-8 offset-md-2">
        <div class="card">
          <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">Edit Admission</h5>
          </div>

          <form @submit.prevent="submitForm" class="card-body" v-if="admission">
            <!-- Status -->
            <div class="mb-3">
              <label for="status" class="form-label">Status</label>
              <select v-model="form.status" id="status" class="form-select">
                <option value="admitted">Admitted</option>
                <option value="in_service">In Service</option>
                <option value="document_pending">Document Pending</option>
                <option value="ready_for_discharge">Ready for Discharge</option>
              </select>
            </div>

            <!-- Documents Verified -->
            <div class="mb-3">
              <div class="form-check form-switch">
                <input
                  v-model="form.documents_verified"
                  type="checkbox"
                  class="form-check-input"
                  id="documents_verified"
                />
                <label class="form-check-label" for="documents_verified">
                  Documents Verified
                </label>
              </div>
            </div>

            <!-- Initial Prestation (Read-only) -->
            <div class="mb-3">
              <label class="form-label">Initial Prestation</label>
              <input
                type="text"
                class="form-control"
                :value="`${admission.initial_prestation?.name || 'N/A'} (${admission.initial_prestation?.code || 'N/A'})`"
                disabled
              />
            </div>

            <!-- Notes Section -->
            <div class="alert alert-info" role="alert">
              <i class="bi bi-info-circle"></i>
              This admission was created on {{ formatDate(admission.created_at) }} by {{ admission.creator?.name }}.
            </div>

            <!-- Submit Buttons -->
            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-warning" :disabled="loading">
                <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                {{ loading ? 'Saving...' : 'Save Changes' }}
              </button>
              <router-link :to="`/admissions/${admission.id}`" class="btn btn-secondary">Cancel</router-link>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { AdmissionService } from '@/services/admissionService'
import { useNotification } from '@/composables/useNotification'

const route = useRoute()
const router = useRouter()
const { notify } = useNotification()

const admission = ref(null)
const loading = ref(false)
const form = ref({
  status: '',
  documents_verified: false,
})

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString()
}

const fetchAdmission = async () => {
  try {
    const response = await AdmissionService.getAdmission(route.params.id)
    admission.value = response.data.data
    form.value = {
      status: admission.value.status,
      documents_verified: admission.value.documents_verified,
    }
  } catch (error) {
    notify('error', 'Failed to load admission')
    router.push('/admissions')
  }
}

const submitForm = async () => {
  loading.value = true
  try {
    await AdmissionService.updateAdmission(route.params.id, form.value)
    notify('success', 'Admission updated successfully')
    router.push(`/admissions/${route.params.id}`)
  } catch (error) {
    notify('error', error.response?.data?.message || 'Failed to update admission')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchAdmission()
})
</script>

<style scoped>
.admission-edit {
  padding: 20px;
}

.form-check-label {
  cursor: pointer;
}

.alert {
  margin-top: 20px;
}
</style>
