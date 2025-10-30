<script setup>
import { computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Breadcrumb from 'primevue/breadcrumb'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'

const props = defineProps({
  ficheId: {
    type: String,
    required: true
  },
  fiche: {
    type: Object,
    default: null
  },
  showCreateForm: {
    type: Boolean,
    default: false
  },
  totalAmount: {
    type: Number,
    required: true
  },
  itemsCount: {
    type: Number,
    required: true
  },
  conventionCompanies: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['go-back', 'toggle-create-form', 'show-convention-details', 'show-all-conventions', 'print-ticket'])

const toast = useToast()

// Header Information
const breadcrumbItems = computed(() => [
  { label: 'Reception', route: '/reception' },
  { label: 'Fiche Navette', route: '/reception/fiche-navette' },
  { label: `Fiche #${props.ficheId}` }
])

const breadcrumbHome = { icon: 'pi pi-home', route: '/' }

// Information section logic
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

const getCompanyColor = (company, index) => {
  if (company.organism_color) {
    return {
      bg: company.organism_color,
      light: company.organism_color + '22',
      border: company.organism_color + '66',
      name: 'Custom'
    }
  }
  const colorIndex = company.id ? (company.id % companyColors.length) : (index % companyColors.length)
  return companyColors[colorIndex]
}

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
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
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
  return labels[props.fiche?.status] || props.fiche?.status
})

const hasConventions = computed(() => {
  return props.conventionCompanies && props.conventionCompanies.length > 0
})

const showOrganismeDetails = (organisme) => {
  emit('show-convention-details', organisme)
}

const handleShowAllConventions = () => {
  emit('show-all-conventions')
}

</script>

<template>
  <div class="tw-mb-8">
    <!-- Breadcrumb -->
    <div class="tw-mb-4">
      <Breadcrumb :model="breadcrumbItems" :home="breadcrumbHome" />
    </div>

    <!-- Enhanced Single Row Header Card -->
    <Card class="tw-bg-white tw-shadow-lg tw-rounded-2xl tw-border tw-border-gray-200 tw-overflow-hidden">
      <template #content>
        <div class="tw-p-6">
          <!-- Single Row Layout -->
          <div class="tw-flex tw-items-center tw-justify-between tw-gap-6 tw-min-h-[80px]">
            
            <!-- Left Section: Back Button + Fiche Info -->
            <div class="tw-flex tw-items-center tw-gap-4 tw-flex-shrink-0">
              <Button
                icon="pi pi-arrow-left"
                class="p-button-text p-button-rounded tw-w-12 tw-h-12 tw-text-gray-600 hover:tw-bg-gray-200 tw-transition-all"
                @click="$emit('go-back')"
                v-tooltip.bottom="'Retour à la liste'"
              />
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-flex tw-items-center tw-justify-center tw-w-14 tw-h-14 tw-bg-gradient-to-br tw-from-blue-500 tw-to-blue-600 tw-text-white tw-rounded-xl tw-shadow-lg">
                  <i class="pi pi-file-edit tw-text-xl"></i>
                </div>
                <div>
                  <h1 class="tw-text-2xl tw-font-bold tw-text-gray-900 tw-leading-tight">
                    Fiche #{{ ficheId }}
                  </h1>
                  <div class="tw-flex tw-items-center tw-gap-3 tw-text-sm tw-text-gray-600 tw-mt-1" v-if="fiche">
                    <span class="tw-flex tw-items-center tw-gap-1">
                      <i class="pi pi-user tw-text-xs"></i>
                      {{ fiche.patient_name }}
                    </span>
                    <span class="tw-text-gray-400">•</span>
                    <span class="tw-flex tw-items-center tw-gap-1">
                      <i class="pi pi-calendar tw-text-xs"></i>
                      {{ formatDate(fiche.fiche_date) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Center Section: Stats and Info -->
            <div class="tw-flex tw-items-center tw-gap-6 tw-flex-1 tw-justify-center tw-px-4" v-if="fiche">
              
              <!-- Status -->
              <div class="tw-flex tw-flex-col tw-items-center tw-gap-1">
                <span class="tw-text-xs tw-text-gray-500 tw-font-medium tw-uppercase tw-tracking-wide">Status</span>
                <Tag
                  :value="statusLabel"
                  :severity="getStatusSeverity(fiche.status)"
                  class="tw-font-semibold tw-px-3 tw-py-1"
                />
              </div>

              <!-- Services Count -->
              <!-- <div class="tw-flex tw-flex-col tw-items-center tw-gap-1 tw-bg-blue-50 tw-rounded-lg tw-px-4 tw-py-2">
                <span class="tw-text-xs tw-text-blue-600 tw-font-medium tw-uppercase tw-tracking-wide">Services</span>
                <div class="tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-list tw-text-blue-600"></i>
                  <span class="tw-text-lg tw-font-bold tw-text-blue-800">{{ itemsCount }}</span>
                </div>
              </div> -->

              <!-- Total Amount -->
              <div class="tw-flex tw-flex-col tw-items-center tw-gap-1 tw-bg-green-50 tw-rounded-lg tw-px-4 tw-py-2">
                <span class="tw-text-xs tw-text-green-600 tw-font-medium tw-uppercase tw-tracking-wide">Total</span>
                <div class="tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-dollar tw-text-green-600"></i>
                  <span class="tw-text-lg tw-font-bold tw-text-green-800">{{ formatCurrency(totalAmount) }}</span>
                </div>
              </div>

              <!-- Patient Balance -->
              <div class="tw-flex tw-flex-col tw-items-center tw-gap-1 tw-bg-purple-50 tw-rounded-lg tw-px-4 tw-py-2">
                <span class="tw-text-xs tw-text-purple-600 tw-font-medium tw-uppercase tw-tracking-wide">Balance</span>
                <div class="tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-balance-scale tw-text-purple-600"></i>
                  <span class="tw-text-lg tw-font-bold tw-text-purple-800">{{ formatCurrency(fiche.patient_balance) }}</span>
                </div>
              </div>

              <!-- Convention Companies (Compact) -->
              <div v-if="hasConventions" class="tw-flex tw-flex-col tw-items-center tw-gap-1">
                <span class="tw-text-xs tw-text-gray-500 tw-font-medium tw-uppercase tw-tracking-wide">Companies</span>
                <div class="tw-flex tw-items-center tw-gap-1">
                  <div 
                    v-for="(company, index) in conventionCompaniesWithColors.slice(0, 2)"
                    :key="company.id"
                    class="tw-flex tw-items-center tw-justify-center tw-w-8 tw-h-8 tw-rounded-full tw-text-white tw-text-xs tw-font-bold tw-border-2 tw-border-white tw-shadow-sm tw-cursor-pointer hover:tw-scale-110 tw-transition-transform"
                    :style="{ backgroundColor: company.color.bg }"
                    @click="showOrganismeDetails(company)"
                    :title="company.organisme_name || company.company_name"
                  >
                    {{ (company.organisme_name || company.company_name).charAt(0).toUpperCase() }}
                  </div>
                  <div
                    v-if="conventionCompaniesWithColors.length > 2"
                    class="tw-flex tw-items-center tw-justify-center tw-w-8 tw-h-8 tw-rounded-full tw-bg-gray-400 tw-text-white tw-text-xs tw-font-bold tw-border-2 tw-border-white tw-shadow-sm tw-cursor-pointer hover:tw-scale-110 tw-transition-transform"
                    @click="handleShowAllConventions"
                    :title="`+${conventionCompaniesWithColors.length - 2} more companies`"
                  >
                    +{{ conventionCompaniesWithColors.length - 2 }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Right Section: Action Buttons -->
            <div class="tw-flex tw-gap-3 tw-flex-shrink-0">
              <Button
                icon="pi pi-print"
                label="Imprimer"
                class="p-button-outlined p-button-info tw-px-4 tw-py-3 tw-font-semibold tw-shadow-md tw-transition-all hover:tw-shadow-lg"
                @click="$emit('print-ticket')"
                size="large"
                v-tooltip.bottom="'Imprimer le ticket'"
              />
              <Button
                :icon="showCreateForm ? 'pi pi-times' : 'pi pi-plus'"
                :label="showCreateForm ? 'Annuler' : 'Ajouter'"
                :class="[
                  showCreateForm
                    ? 'p-button-outlined p-button-danger'
                    : 'p-button-primary',
                  'tw-px-6 tw-py-3 tw-font-semibold tw-shadow-lg tw-transition-all hover:tw-shadow-xl'
                ]"
                @click="$emit('toggle-create-form')"
                size="large"
              />
            </div>
          </div>
        </div>
      </template>
    </Card>
  </div>
</template>

<style scoped>
/* PrimeVue overrides */
:deep(.p-card-content) {
  @apply p-0;
}

:deep(.p-tag) {
  @apply rounded-full tw-shadow-sm;
}

:deep(.p-button) {
  @apply transition-all tw-duration-200;
}

:deep(.p-button-text:hover) {
  @apply transform tw-scale-105;
}

/* Responsive adjustments */
@media (max-width: 1024px) {
  .tw-flex-1 {
    @apply gap-4;
  }
  
  .tw-px-4 {
    @apply px-2;
  }
}

@media (max-width: 768px) {
  /* Stack vertically on mobile */
  .tw-justify-between {
    @apply flex-col tw-items-start tw-gap-4;
  }
  
  .tw-justify-center {
    @apply justify-start tw-flex-wrap;
  }
  
  .tw-flex-shrink-0:last-child {
    @apply w-full;
  }
  
  .tw-flex-shrink-0:last-child .p-button {
    @apply w-full tw-justify-center;
  }
}
</style>