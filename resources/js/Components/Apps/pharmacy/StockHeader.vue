<template>
  <Card class="tw-mb-8 tw-bg-gradient-to-r tw-from-blue-600 tw-to-indigo-700 tw-text-white tw-shadow-xl">
    <template #content>
      <div class="tw-flex tw-justify-between tw-items-center">
        <div>
          <h1 class="tw-text-4xl tw-font-bold tw-mb-2">{{ title }}</h1>
          <p class="tw-text-blue-100">{{ subtitle }}</p>
        </div>
        <div class="tw-flex tw-space-x-3">
          <Button
            v-if="showNewButton"
            label="New Request"
            icon="pi pi-plus"
            class="tw-bg-green-500 hover:tw-bg-green-600"
            @click="$emit('new-request')"
          />
        </div>
      </div>
    </template>
  </Card>
</template>

<script>
import Card from 'primevue/card';
import Button from 'primevue/button';

export default {
  name: 'StockHeader',
  components: {
    Card,
    Button
  },
  props: {
    title: {
      type: String,
      required: true
    },
    subtitle: {
      type: String,
      required: true
    },
    showNewButton: {
      type: Boolean,
      default: false
    }
  },
  emits: ['new-request']
};
</script>

<style scoped>
.tw-shadow-xl {
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}
</style>