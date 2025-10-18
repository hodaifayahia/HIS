<template>
  <!-- Previous template code remains the same -->
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import { ficheNavetteService } from '@/Components/Apps/services/Reception/ficheNavetteService.js'
// ... other imports

const emit = defineEmits(['cancel', 'created'])
const toast = useToast()

// ... existing reactive data

// API functions implementation
const fetchServices = async () => {
  try {
    const result = await ficheNavetteService.getServicesWithDoctors()
    if (result.success) {
      services.value = result.data
    } else {
      throw new Error(result.message)
    }
  } catch (error) {
    console.error('Error fetching services:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load services',
      life: 3000
    })
  }
}

const fetchDoctors = async () => {
  // Doctors are loaded with services
  doctors.value = services.value.flatMap(service => service.doctors || [])
}

const fetchPrestations = async () => {
  try {
    const result = await ficheNavetteService.searchPrestations()
    if (result.success) {
      prestations.value = result.data
    }
  } catch (error) {
    console.error('Error fetching prestations:', error)
  }
}

const fetchPackages = async () => {
  // Packages will be loaded when service is selected
  packages.value = []
}

const fetchSpecializations = async () => {
  try {
    const result = await ficheNavetteService.getAllSpecializations()
    if (result.success) {
      specializations.value = result.data
    }
  } catch (error) {
    console.error('Error fetching specializations:', error)
  }
}

const fetchPrestationsByIds = async (ids) => {
  try {
    const result = await ficheNavetteService.getPrestationsDependencies(ids)
    if (result.success) {
      return result.data
    }
    return []
  } catch (error) {
    console.error('Error fetching prestations by IDs:', error)
    return []
  }
}

const createFicheNavetteAPI = async (data) => {
  try {
    const result = await ficheNavetteService.createFicheNavette(data)
    if (result.success) {
      return result.data
    } else {
      throw new Error(result.message)
    }
  } catch (error) {
    console.error('Error creating fiche navette:', error)
    throw error
  }
}

// Updated methods
const onServiceChange = async () => {
  selectedDoctor.value = null
  selectedPrestations.value = []
  dependencies.value = []
  
  if (selectedService.value) {
    try {
      loading.value = true
      
      if (showPackages.value) {
        const result = await ficheNavetteService.getPackagesByService(selectedService.value)
        if (result.success) {
          packages.value = result.data
        }
      } else {
        const result = await ficheNavetteService.getPrestationsByService(selectedService.value)
        if (result.success) {
          prestations.value = result.data
        }
      }
    } catch (error) {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: 'Failed to load items for selected service',
        life: 3000
      })
    } finally {
      loading.value = false
    }
  }
}

const loadDependencies = async (dependencyIds) => {
  try {
    const deps = await fetchPrestationsByIds(dependencyIds)
    dependencies.value.push(...deps)
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load dependencies',
      life: 3000
    })
  }
}

// Watch for showPackages change
watch(showPackages, async () => {
  if (selectedService.value) {
    await onServiceChange()
  }
})

// ... rest of the component remains the same
</script>

<!-- Styles remain the same -->