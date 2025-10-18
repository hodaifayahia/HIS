<script setup>
import { ref, defineProps, onMounted } from "vue";
import TabView from "primevue/tabview";
import TabPanel from "primevue/tabpanel";

import Agreement_table from '../tables/Agreement_table.vue';
import Agreement_para_table from '../tables/Agreement_para_table.vue';
import Annex_table from '../tables/Annex_table.vue';
import Avenant_table from '../tables/Avenant_table.vue';

const props = defineProps({
  contract: {
    type: Object,
    required: true,
  },
});

// Use a ref to control the active tab index for PrimeVue TabView
const activeTabIndex = ref(0); // 0 for Agreement, 1 for Configurable Agreement, etc.

// No need for selectTab method as PrimeVue handles tab changes internally

// Ensure the correct tab is active on mount if initial state is not 'agreement'
onMounted(() => {
  // If you need to set an initial active tab based on some condition,
  // you can set activeTabIndex here.
  // For example, to start on 'configurableAgreement' based on some logic:
  // if (props.contract.type === 'configurable') {
  //   activeTabIndex.value = 1;
  // }
});
</script>

<template>
  <section class="mt-4">
    <div class="card">
      <div class="card-">
        <TabView v-model:activeIndex="activeTabIndex">
          <TabPanel header="Agreement">
            <Agreement_table :contractState="contract.status" :contractid="contract.id" />
          </TabPanel>

          <TabPanel header="Configurable Agreement">
            <Agreement_para_table :contractState="contract.status" :avenantpage="'no'" :contractid="contract.id" />
          </TabPanel>

          <TabPanel header="Annex">
            <Annex_table :contractState="contract.status" :contractId="contract.id" :isgeneral="false" />
          </TabPanel>

          <TabPanel header="Avenant" v-if="contract.status === 'active' || contract.status === 'Terminated'">
            <Avenant_table :contractState="contract.status" :conventionId="contract.id" />
          </TabPanel>
        </TabView>
      </div>
    </div>
  </section>
</template>

<style scoped>
/* PrimeVue TabView comes with its own styling.
   You might want to adjust or override some of PrimeVue's default styles
   to match your application's theme, but generally, less custom CSS is needed. */

/* Example of overriding a PrimeVue style if necessary (adjust selectors as needed) */
/*
.p-tabview .p-tabview-nav li .p-tabview-nav-link {
    font-weight: bold;
}
*/


/* No need for custom .nav-tabs, .nav-link, .card-header styles as PrimeVue handles the tab structure */
</style>