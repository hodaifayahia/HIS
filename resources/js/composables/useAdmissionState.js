import { ref } from 'vue'

export function useAdmissionState() {
  const admission = ref(null)
  const admissions = ref([])
  const procedures = ref([])
  const documents = ref([])
  const billingRecords = ref([])
  const loading = ref(false)
  const errors = ref({})

  const setAdmission = (data) => {
    admission.value = data
  }

  const setAdmissions = (data) => {
    admissions.value = data
  }

  const setProcedures = (data) => {
    procedures.value = data
  }

  const setDocuments = (data) => {
    documents.value = data
  }

  const setBillingRecords = (data) => {
    billingRecords.value = data
  }

  const setLoading = (state) => {
    loading.value = state
  }

  const setErrors = (errorData) => {
    errors.value = errorData
  }

  const clearErrors = () => {
    errors.value = {}
  }

  return {
    admission,
    admissions,
    procedures,
    documents,
    billingRecords,
    loading,
    errors,
    setAdmission,
    setAdmissions,
    setProcedures,
    setDocuments,
    setBillingRecords,
    setLoading,
    setErrors,
    clearErrors,
  }
}
