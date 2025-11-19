<template>
  <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-6 tw-mb-8">
    <!-- Requester Stats -->
    <template v-if="viewType === 'requester'">
      <Card class="tw-shadow-lg">
        <template #content>
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <p class="tw-text-sm tw-text-gray-600">My Drafts</p>
              <p class="tw-text-3xl tw-font-bold tw-text-blue-600">{{ stats.draft || 0 }}</p>
            </div>
            <i class="pi pi-file tw-text-3xl tw-text-blue-600"></i>
          </div>
        </template>
      </Card>

      <Card class="tw-shadow-lg">
        <template #content>
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <p class="tw-text-sm tw-text-gray-600">Pending Approval</p>
              <p class="tw-text-3xl tw-font-bold tw-text-orange-600">{{ stats.requesting_pending || 0 }}</p>
            </div>
            <i class="pi pi-clock tw-text-3xl tw-text-orange-600"></i>
          </div>
        </template>
      </Card>
    </template>

    <!-- Receiver Stats -->
    <template v-if="viewType === 'receiver'">
      <Card class="tw-shadow-lg">
        <template #content>
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <p class="tw-text-sm tw-text-gray-600">To Approve</p>
              <p class="tw-text-3xl tw-font-bold tw-text-orange-600">{{ stats.providing_pending || 0 }}</p>
            </div>
            <i class="pi pi-exclamation-triangle tw-text-3xl tw-text-orange-600"></i>
          </div>
        </template>
      </Card>

      <Card class="tw-shadow-lg">
        <template #content>
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <p class="tw-text-sm tw-text-gray-600">Approved</p>
              <p class="tw-text-3xl tw-font-bold tw-text-blue-600">{{ stats.approved || 0 }}</p>
            </div>
            <i class="pi pi-check tw-text-3xl tw-text-blue-600"></i>
          </div>
        </template>
      </Card>
    </template>

    <!-- Common Stats -->
    <Card class="tw-shadow-lg">
      <template #content>
        <div class="tw-flex tw-items-center tw-justify-between">
          <div>
            <p class="tw-text-sm tw-text-gray-600">In Transfer</p>
            <p class="tw-text-3xl tw-font-bold tw-text-purple-600">{{ stats.in_transfer || 0 }}</p>
          </div>
          <i class="pi pi-arrow-right tw-text-3xl tw-text-purple-600"></i>
        </div>
      </template>
    </Card>

    <Card class="tw-shadow-lg">
      <template #content>
        <div class="tw-flex tw-items-center tw-justify-between">
          <div>
            <p class="tw-text-sm tw-text-gray-600">Completed</p>
            <p class="tw-text-3xl tw-font-bold tw-text-green-600">{{ stats.executed || 0 }}</p>
          </div>
          <i class="pi pi-check-circle tw-text-3xl tw-text-green-600"></i>
        </div>
      </template>
    </Card>
  </div>
</template>

<script>
import Card from 'primevue/card';

export default {
  name: 'StockStatsCards',
  components: {
    Card
  },
  props: {
    stats: {
      type: Object,
      default: () => ({})
    },
    viewType: {
      type: String,
      required: true,
      validator: (value) => ['requester', 'receiver'].includes(value)
    }
  }
};
</script>

<style scoped>
.tw-shadow-lg {
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

/* Responsive design */
@media (max-width: 768px) {
  .tw-grid-cols-4 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
</style>