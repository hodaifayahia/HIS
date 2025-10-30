<!-- components/Reception/FicheNavette/FicheNavetteInfo.vue -->
<script setup>
import { computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import Avatar from 'primevue/avatar'
import Chip from 'primevue/chip'
import Badge from 'primevue/badge'
import Button from 'primevue/button'

// Add useToast
const toast = useToast()

const props = defineProps({
  fiche: {
    type: Object,
    required: true
  },
  totalAmount: {
    type: Number,
    required: true
  },
  itemsCount: {
    type: Number,
    required: true
  },
  groupsCount: {
    type: Number,
    required: true
  },
  conventionCompanies: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['show-convention-details', 'show-all-conventions'])

// Add company colors array (same as FicheNavetteItemCreate)
const companyColors = [
  { bg: '#3B82F6', light: '#DBEAFE', border: '#93C5FD', name: 'Blue' },
  { bg: '#10B981', light: '#D1FAE5', border: '#6EE7B7', name: 'Green' },
  { bg: '#8B5CF6', light: '#EDE9FE', border: '#C4B5FD', name: 'Purple' },
  { bg: '#F59E0B', light: '#FEF3C7', border: '#FCD34D', name: 'Yellow' },
  { bg: '#EF4444', light: '#FEE2E2', border: '#FECACA', name: 'Red' },
  { bg: '#06B6D4', light: '#CFFAFE', border: '#67E8F9', name: 'Cyan' },
  { bg: '#84CC16', light: '#ECFCCB', border: '#BEF264', name: 'Lime' },
  { bg: '#EC4899', light: '#FCE7F3', border: '#F9A8D4', name: 'Pink' },
]

// Function to get company color (same as FicheNavetteItemCreate)
const getCompanyColor = (company, index) => {
  // First check if company has organism_color
  if (company.organism_color) {
    console.log('Custom organism color found:', company.organism_color);
    return {
      bg: company.organism_color,
      light: company.organism_color + '22', // Add transparency
      border: company.organism_color + '66',
      name: 'Custom'
    }
  }
  
  // Fallback to palette based on company ID or index
  const colorIndex = company.id ? (company.id % companyColors.length) : (index % companyColors.length)
  return companyColors[colorIndex]
}

// Enhanced computed properties with colors
const conventionCompaniesWithColors = computed(() => {
  return props.conventionCompanies.map((company, index) => ({
    ...company,
    color: getCompanyColor(company, index)
  }))
})

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD'
  }).format(amount || 0)
}

const getStatusSeverity = (status) => {
  const statusMap = {
    'pending': 'warning',
    'in_progress': 'info',
    'completed': 'success',
    'cancelled': 'danger',
    'required': 'secondary'
  }
  return statusMap[status] || 'secondary'
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('fr-FR', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const statusLabel = computed(() => {
  const labels = {
    'pending': 'En attente',
    'in_progress': 'En cours',
    'completed': 'Terminé',
    'cancelled': 'Annulé',
    'required': 'Requis'
  }
  return labels[props.fiche.status] || props.fiche.status
})

const totalConventions = computed(() => {
  return props.conventionCompanies.reduce((total, company) => {
    return total + (company.conventions_count || company.conventions?.length || 0)
  }, 0)
})

const hasConventions = computed(() => {
  return props.conventionCompanies && props.conventionCompanies.length > 0
})

// Enhanced click handler - pass company with color
const showOrganismeDetails = (organisme) => {
  console.log('Showing organisme details:', organisme) // Debug
  emit('show-convention-details', organisme)
}

const showAllConventions = () => {
  emit('show-all-conventions')
}

// Add file handling methods
const getFileIcon = (mimeTypeOrName) => {
  if (!mimeTypeOrName) return 'pi pi-file'
  const type = mimeTypeOrName.toLowerCase()
  if (type.includes('pdf')) return 'pi pi-file-pdf'
  if (type.includes('word') || type.includes('doc')) return 'pi pi-file-word'
  if (type.includes('excel') || type.includes('xls')) return 'pi pi-file-excel'
  if (type.includes('image')) return 'pi pi-image'
  return 'pi pi-file'
}

const formatFileSize = (bytes) => {
  if (!bytes) return 'Unknown size'
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(1024))
  return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i]
}

const viewFile = (file) => {
  if (file && file.id) {
    window.open(`/api/fiche-navette/files/${file.id}/view`, '_blank')
  } else {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'File ID not available',
      life: 3000
    })
  }
}

const downloadFile = (file) => {
  if (file && file.id) {
    const link = document.createElement('a')
    link.href = `/api/fiche-navette/files/${file.id}/download`
    link.download = file.original_name || 'download'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } else {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'File ID not available',
      life: 3000
    })
  }
}
</script>
<template>
  <Card class="tw-bg-white tw-shadow-md tw-rounded-2xl tw-border tw-border-gray-200">
    <template #content>
      <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-2 tw-gap-8 tw-p-4 md:tw-p-6">
        <div class="tw-flex tw-flex-col tw-gap-8">
          <div class="tw-flex tw-flex-col tw-gap-4">
            <div class="tw-flex tw-items-center tw-gap-2 tw-pb-2 tw-border-b-2 tw-border-blue-200">
              <i class="pi pi-user tw-text-blue-500 tw-text-xl"></i>
              <h4 class="tw-text-lg tw-font-semibold tw-text-gray-800">Patient Information</h4>
            </div>
          
          </div>

          <div class="tw-flex tw-flex-col tw-gap-4">
            <div class="tw-flex tw-items-center tw-gap-2 tw-pb-2 tw-border-b-2 tw-border-blue-200">
              <i class="pi pi-info-circle tw-text-blue-500 tw-text-xl"></i>
              <h4 class="tw-text-lg tw-font-semibold tw-text-gray-800">Fiche Details</h4>
            </div>
            <div class="tw-grid tw-grid-cols-1 tw-gap-3">
              <div class="tw-bg-gray-50 tw-p-3 tw-rounded-lg tw-border-l-4 tw-border-blue-500 tw-flex tw-items-center tw-justify-between">
                <span class="tw-text-gray-600 tw-font-medium tw-text-sm tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-calendar"></i> Date
                </span>
                <span class="tw-font-semibold tw-text-gray-800">{{ formatDate(fiche.fiche_date) }}</span>
              </div>
              <div class="tw-bg-gray-50 tw-p-3 tw-rounded-lg tw-border-l-4 tw-border-blue-500 tw-flex tw-items-center tw-justify-between">
                <span class="tw-text-gray-600 tw-font-medium tw-text-sm tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-flag"></i> Status
                </span>
                <Tag 
                  :value="statusLabel"
                  :severity="getStatusSeverity(fiche.status)"
                  class="tw-font-semibold tw-px-2 tw-py-1 tw-text-xs"
                />
              </div>
              <div class="tw-bg-gray-50 tw-p-3 tw-rounded-lg tw-border-l-4 tw-border-blue-500 tw-flex tw-items-center tw-justify-between">
                <span class="tw-text-gray-600 tw-font-medium tw-text-sm tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-balance-scale"></i> Balance
                </span>
                <span class="tw-font-bold tw-text-green-600">{{ fiche.patient_balance }} DZD</span>
              </div>
            </div>
          </div>
        </div>

        <div class="tw-flex tw-flex-col tw-gap-8">
          <div v-if="hasConventions" class="tw-flex tw-flex-col tw-gap-4">
            <div class="tw-flex tw-items-center tw-gap-2 tw-pb-2 tw-border-b-2 tw-border-blue-200">
              <i class="pi pi-building tw-text-blue-500 tw-text-xl"></i>
              <h4 class="tw-text-lg tw-font-semibold tw-text-gray-800">Convention Companies</h4>
            </div>
            <div class="tw-flex tw-flex-col tw-gap-4">
              <div class="tw-flex tw-flex-wrap tw-gap-2">
                <Tag
                  v-for="company in conventionCompaniesWithColors.slice(0, 3)"
                  :key="company.id"
                  :value="company.organisme_name || company.company_name"
                  class="tw-cursor-pointer tw-transition-all tw-duration-200 hover:tw-scale-105"
                  @click="showOrganismeDetails(company)"
                  :style="{ backgroundColor: company.color.bg }"
                >
                  <template #default>
                    <div class="tw-flex tw-items-center tw-gap-2 tw-text-white">
                      <i class="pi pi-building"></i>
                      <span class="tw-font-medium">{{ company.organisme_name || company.company_name }}</span>
                      <Badge 
                        :value="company.conventions_count || company.conventions?.length" 
                        :style="{ backgroundColor: company.color.bg, color: 'white', border: `1px solid rgba(255,255,255,0.2)` }"
                      />
                    </div>
                  </template>
                </Tag>
                <Tag
                  v-if="conventionCompaniesWithColors.length > 3"
                  :value="`+${conventionCompaniesWithColors.length - 3} more`"
                  severity="secondary"
                  class="tw-cursor-pointer tw-transition-all tw-duration-200 hover:tw-scale-105"
                  @click="showAllConventions"
                />
              </div>
              <Button
                v-if="conventionCompaniesWithColors.length > 3"
                label="View All Companies"
                icon="pi pi-external-link"
                class="p-button-text p-button-sm tw-justify-start tw-text-blue-600 tw-font-semibold"
                @click="showAllConventions"
              />
            </div>
          </div>

          <div class="tw-flex tw-flex-col tw-gap-4">
            <div class="tw-flex tw-items-center tw-gap-2 tw-pb-2 tw-border-b-2 tw-border-blue-200">
              <i class="pi pi-chart-line tw-text-blue-500 tw-text-xl"></i>
              <h4 class="tw-text-lg tw-font-semibold tw-text-gray-800">Summary</h4>
            </div>
            <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
              <div class="tw-bg-gray-100 tw-p-4 tw-rounded-lg tw-shadow-sm tw-flex tw-items-center tw-gap-4">
                <div class="tw-flex tw-items-center tw-justify-center tw-w-12 tw-h-12 tw-rounded-full tw-bg-blue-200 tw-text-blue-800 tw-text-xl tw-flex-shrink-0">
                  <i class="pi pi-list"></i>
                </div>
                <div>
                  <div class="tw-text-2xl tw-font-bold tw-text-gray-900">{{ itemsCount }}</div>
                  <div class="tw-text-sm tw-text-gray-600">Total Items</div>
                </div>
              </div>
              <div class="tw-bg-green-100 tw-p-4 tw-rounded-lg tw-shadow-sm tw-flex tw-items-center tw-gap-4">
                <div class="tw-flex tw-items-center tw-justify-center tw-w-12 tw-h-12 tw-rounded-full tw-bg-green-200 tw-text-green-800 tw-text-xl tw-flex-shrink-0">
                  <i class="pi pi-dollar"></i>
                </div>
                <div>
                  <div class="tw-text-2xl tw-font-bold tw-text-green-800">{{ formatCurrency(totalAmount) }}</div>
                  <div class="tw-text-sm tw-text-green-600">Total Amount</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </Card>
</template>

<style scoped>
/* PrimeVue overrides */
:deep(.p-card-content) {
  @apply p-0;
}

:deep(.p-tag) {
  @apply rounded-full;
}

:deep(.p-button-text) {
  @apply text-sm;
}
</style>