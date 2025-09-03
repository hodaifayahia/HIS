<!-- components/Reception/FicheNavette/FicheNavetteItemsSection.vue -->
<script setup lang="ts">
import Card from 'primevue/card'
import Button from 'primevue/button'
import Chip from 'primevue/chip'
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from 'primevue/useconfirm'

// Components
import FicheNavetteItemCreate from './FicheNavetteItemCreate.vue'
import PrestationItemCard from '../FicheNavatte/PrestationItemCard.vue'
import EmptyState from '../../../Common/EmptyState.vue'
import { ficheNavetteService } from '../../../Apps/services/Reception/ficheNavetteService'


const props = defineProps({
  fiche: {
    type: Object,
    required: true
  },
  items: {
    type: Array,
    required: true
  },
  groupedItems: {
    type: Array,
    required: true
  },
  prestations: {
    type: Array,
    required: true
  },
  packages: {
    type: Array,
    required: true
  },
  doctors: {
    type: Array,
    required: true
  },
  showCreateForm: {
    type: Boolean,
    required: true
  },
  totalAmount: {
    type: Number,
    required: true
  },
  itemsCount: {
    type: Number,
    required: true
  }
})

const emit = defineEmits([
  'items-added',
  'item-removed',
  'remise-applied',
  'toggle-create-form'
])

const confirm = useConfirm()

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD'
  }).format(amount || 0)
}
const confirmRemoveGroup = (items) => {
  confirm.require({
    message: 'Are you sure you want to remove this group and all its prestations and dependencies? This action cannot be undone.',
    header: 'Remove Group',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: () => handleRemoveGroup(items)
  })
}
const handleRemoveGroup = async (items) => {
  // items is an array of all prestations in the group
  for (const item of items) {
    await ficheNavetteService.removeFicheNavetteItem(props.fiche.id, item.id)
    // Optionally: remove dependencies if not handled by backend
  }
  emit('items-added')
}
const handleRemoveItem = async (item) => {
  await ficheNavetteService.removeFicheNavetteItem(props.fiche.id, item)
  emit('item-removed', item)
}
const confirmRemoveItem = (itemId) => {
  confirm.require({
    message: 'Are you sure you want to remove this item? This action cannot be undone.',
    header: 'Remove Item',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: () => handleRemoveItem(itemId)
  })
}
</script>
<template>
  <div class="tw-flex tw-flex-col tw-gap-6 tw-mb-8">
    <Card v-if="showCreateForm" class="tw-border-2 tw-border-dashed tw-border-blue-400 tw-bg-blue-50 tw-rounded-2xl tw-overflow-hidden tw-shadow-md">
      <template #header>
        <div class="tw-bg-blue-100 tw-p-6">
          <div class="tw-flex tw-items-center tw-justify-between tw-gap-4">
            <div class="tw-flex tw-items-center tw-gap-4">
              <div class="tw-flex tw-items-center tw-justify-center tw-w-12 tw-h-12 tw-bg-blue-600 tw-text-white tw-rounded-lg tw-shadow-md tw-flex-shrink-0">
                <i class="pi pi-plus tw-text-xl"></i>
              </div>
              <div>
                <h4 class="tw-text-xl tw-font-bold tw-text-blue-800 tw-mb-0.5">Add New Items</h4>
                <p class="tw-text-sm tw-text-blue-600">Select prestations or packages to add to this fiche</p>
              </div>
            </div>
            <Button
              icon="pi pi-times"
              class="p-button-text p-button-rounded tw-text-blue-600 hover:tw-bg-blue-200"
              @click="$emit('toggle-create-form')"
              v-tooltip.left="'Close'"
            />
          </div>
        </div>
      </template>
      <template #content>
        <div class="tw-p-6">
          <FicheNavetteItemCreate
            :patient-id="fiche.patient_id"
            :fiche-navette-id="fiche.id"
            @created="$emit('items-added')"
          />
        </div>
      </template>
    </Card>

    <Card class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-border tw-border-gray-200">
      <template #header>
        <div class="tw-flex tw-flex-col sm:tw-flex-row tw-items-start sm:tw-items-center tw-justify-between tw-p-6 tw-bg-gray-50 tw-border-b tw-border-gray-200">
          <div class="tw-flex tw-items-center tw-gap-4">
            <div class="tw-flex tw-items-center tw-justify-center tw-w-12 tw-h-12 tw-bg-blue-100 tw-text-blue-600 tw-rounded-full tw-flex-shrink-0">
              <i class="pi pi-list tw-text-xl"></i>
            </div>
            <div>
              <h3 class="tw-text-xl tw-font-bold tw-text-gray-900 tw-mb-0.5">Items & Services</h3>
              <p class="tw-text-sm tw-text-gray-500">Manage prestations and packages for this fiche</p>
            </div>
          </div>
          <div class="tw-flex tw-gap-2 tw-mt-4 sm:tw-mt-0 tw-flex-wrap">
            <Chip 
              :label="`${groupedItems.length} item${groupedItems.length !== 1 ? 's' : ''}`"
              severity="info"
              class="!tw-bg-blue-100 !tw-text-blue-800 tw-font-medium"
            />
            
            <Chip 
              :label="formatCurrency(totalAmount)"
              severity="success"
              class="!tw-bg-green-100 !tw-text-green-800 tw-font-bold tw-text-base"
            />
          </div>
        </div>
      </template>
      
      <template #content>
        <div class="tw-p-6">
          <EmptyState
            v-if="itemsCount === 0"
            icon="pi pi-inbox"
            title="No Items Added Yet"
            description="Start by adding prestations or packages to this fiche navette"
            :actions="[
              {
                label: 'Add First Item',
                icon: 'pi pi-plus',
                class: 'p-button-primary p-button-lg',
                action: () => $emit('toggle-create-form')
              }
            ]"
          />

          <div v-else class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-6">
            <PrestationItemCard
              v-for="group in groupedItems"
              :key="`${group.type}_${group.id}`"
              :group="group"
              :patient-id="fiche.patient_id"
              :prestations="prestations"
              :packages="packages"
              :doctors="doctors"
              :fiche-navette-id="fiche.id"
              @remove-item="confirmRemoveItem"
              @item-updated="$emit('items-added')"
              @remove-group="confirmRemoveGroup"
              @dependency-removed="$emit('items-added')"
              @apply-remise="$emit('remise-applied')"
            />
          </div>
        </div>

        <div v-if="itemsCount > 0" class="tw-flex tw-flex-col sm:tw-flex-row tw-items-center tw-justify-between tw-p-6 tw-bg-gray-100 tw-border-t tw-border-gray-200">
          <div class="tw-flex-1">
            <Button
              icon="pi pi-plus"
              label="Add More Items"
              class="p-button-outlined tw-w-full sm:tw-w-auto"
              @click="$emit('toggle-create-form')"
            />
          </div>
          <div class="tw-flex tw-items-center tw-justify-center sm:tw-justify-end tw-gap-2 tw-mt-4 sm:tw-mt-0 tw-w-full sm:tw-w-auto">
            <span class="tw-text-lg tw-font-semibold tw-text-gray-600">Total:</span>
            <span class="tw-text-2xl tw-font-bold tw-text-green-600">{{ formatCurrency(totalAmount) }}</span>
          </div>
        </div>
      </template>
    </Card>

    <ConfirmDialog />
  </div>
</template>

<style scoped>
/* All styles are now handled by Tailwind CSS classes. */
</style>