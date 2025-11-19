<template>
  <div class="tw-p-4">
    <h4 class="tw-text-lg tw-font-semibold tw-mb-4">Product Categories</h4>
    <ul class="tw-list-disc tw-pl-5">
      <li v-for="category in categories" :key="category.id" class="tw-mb-2">
        {{ category.name }}
      </li>
    </ul>
  </div>
</template>

<script>
import { defineComponent } from 'vue';

export default defineComponent({
  name: 'ProductCategories',
  props: {
    categories: {
      type: Array,
      default: () => []
    }
  }
});
</script>

<style scoped>
/* Add any specific styles for ProductCategories here */
</style>