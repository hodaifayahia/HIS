<script setup>
import { computed } from 'vue'
import Button from 'primevue/button'
import Breadcrumb from 'primevue/breadcrumb'

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
  }
})

const emit = defineEmits(['go-back', 'toggle-create-form'])

const breadcrumbItems = computed(() => [
  { label: 'Reception', route: '/reception' },
  { label: 'Fiche Navette', route: '/reception/fiche-navette' },
  { label: `Fiche #${props.ficheId}` }
])

const breadcrumbHome = { icon: 'pi pi-home', route: '/' }
</script>

<template>
  <div class="tw-mb-8 md:tw-mb-10 lg:tw-mb-12">
    <div class="tw-mb-4 lg:tw-mb-6">
      <Breadcrumb :model="breadcrumbItems" :home="breadcrumbHome" />
    </div>

    <div class="tw-bg-white tw-shadow-md tw-rounded-2xl tw-p-4 sm:tw-p-6 lg:tw-p-8">
      <div class="tw-flex tw-flex-col md:tw-flex-row md:tw-items-center tw-justify-between tw-gap-4 lg:tw-gap-6">
        <div class="tw-flex tw-items-center tw-gap-4 lg:tw-gap-6 tw-flex-1">
          <Button 
            icon="pi pi-arrow-left"
            class="p-button-text p-button-rounded tw-text-gray-600 hover:tw-bg-gray-200"
            @click="$emit('go-back')"
            v-tooltip.bottom="'Retour à la liste'"
            size="large"
          />
          <div class="tw-flex tw-items-center tw-gap-4">
            <div class="tw-flex tw-items-center tw-justify-center tw-w-12 tw-h-12 tw-bg-blue-600 tw-text-white tw-rounded-xl tw-shadow-lg tw-flex-shrink-0">
              <i class="pi pi-file-edit tw-text-xl"></i>
            </div>
            <div>
              <h1 class="tw-text-xl sm:tw-text-2xl lg:tw-text-3xl tw-font-bold tw-text-gray-900 tw-leading-tight">
                Fiche Navette #{{ ficheId }}
              </h1>
              <p class="tw-text-sm sm:tw-text-base tw-text-gray-600 tw-mt-1" v-if="fiche">
                <i class="pi pi-user tw-mr-1"></i>
                {{ fiche.patient_name }}
                <span class="tw-mx-2 tw-text-gray-400">•</span>
                <i class="pi pi-calendar tw-mr-1"></i>
                {{ new Date(fiche.fiche_date).toLocaleDateString('fr-FR', { 
                  weekday: 'long', 
                  year: 'numeric', 
                  month: 'long', 
                  day: 'numeric' 
                }) }}
              </p>
            </div>
          </div>
        </div>
        
        <div class="tw-flex tw-flex-col sm:tw-flex-row tw-items-center tw-gap-3 tw-w-full md:tw-w-auto">
          <Button 
            :icon="showCreateForm ? 'pi pi-times' : 'pi pi-plus'"
            :label="showCreateForm ? 'Annuler' : 'Ajouter des services'"
            :class="[
              showCreateForm 
                ? 'p-button-outlined p-button-danger tw-w-full' 
                : 'p-button-primary tw-w-full'
            ]"
            @click="$emit('toggle-create-form')"
            size="large"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/*
  The `tw-` prefix is used to prevent conflicts with other styles.
  No custom CSS is needed as Tailwind handles everything.
*/
</style>