<script setup>
import { ref, defineProps } from "vue";
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
// Assuming these child components are already adapted to PrimeVue or don't rely heavily on Bootstrap UI for their root element
import Contract_table from '../tables/Contract_table.vue';
import Contacts_table from '../tables/Contacts_table.vue';

const props = defineProps({
  company: {
    type: Object,
    required: true,
    validator: (obj) => obj.id !== undefined
  },
  companyId: {
    type: String,
    required: true
  }
});

// PrimeVue TabView uses an 'activeIndex' (0-based index)
const activeIndex = ref(0); // 0 for Contracts, 1 for Contacts

// You could still use activeTab string for clarity if you prefer,
// but for TabView, activeIndex is direct.
// const activeTab = ref('contracts');
// const setActiveTab = (tabName) => {
//   if (tabName === 'contracts') activeIndex.value = 0;
//   else if (tabName === 'contacts') activeIndex.value = 1;
// };

// Using computed property to map activeIndex to tab names (optional, for debugging/clarity)
// const currentTabName = computed(() => {
//   if (activeIndex.value === 0) return 'contracts';
//   else if (activeIndex.value === 1) return 'contacts';
//   return '';
// });
</script>

<template>
  <section class="p-py-4 company-details-section">
    <div class="p-card p-shadow-24 custom-tab-card">
      <TabView v-model:activeIndex="activeIndex" class="custom-tabview">
        <TabPanel header="Contracts">
          <!-- Explicitly convert id to string if needed -->
          <Contract_table :companyId="String(props.companyId)" />
        </TabPanel>

        <TabPanel header="Contacts">
          <Contacts_table :companyId="String(props.companyId)" />
        </TabPanel>
      </TabView>
    </div>
  </section>
</template>

<style scoped>
/* Base Section and Card Styles - Adapted to PrimeVue/PrimeFlex */
.company-details-section {
  padding: 1.5rem;
  background: linear-gradient(135deg, var(--surface-0) 0%, var(--surface-50) 100%);
  min-height: calc(100vh - 60px); /* Adjust based on your header/footer height */
}

.p-card {
  border-radius: var(--border-border-radius);
  box-shadow: var(--surface-shadow);
  border: 1px solid var(--surface-border);
  overflow: hidden; /* Ensures child elements respect border-radius */
}

/* Customizing PrimeVue TabView to match desired aesthetics */
.custom-tab-card .p-card-content {
  padding: 0; /* Remove default padding inside card if TabView fills it */
}

.custom-tabview .p-tabview-nav {
  /* Mimic custom-nav-tabs background and border */
  background: linear-gradient(135deg, var(--surface-0) 0%, var(--surface-50) 100%);
  border-bottom: 1px solid var(--surface-border);
  padding: 0 1.5rem; /* Match original padding */
  border-top-left-radius: var(--border-radius); /* Apply to top corners of nav */
  border-top-right-radius: var(--border-radius);
}

.custom-tabview .p-tabview-nav-link {
  /* Mimic custom-nav-tabs nav-link styles */
  border: 1px solid transparent; /* No direct border, underline effect comes from ::after */
  border-top-left-radius: 0.75rem; /* Apply radius to individual tab buttons */
  border-top-right-radius: 0.75rem;
  color: var(--text-color-secondary); /* Match #64748b */
  font-weight: 600;
  padding: 1rem 1.5rem;
  transition: all 0.3s ease;
  background-color: transparent;
  position: relative;
  overflow: hidden;
  margin-bottom: -1px; /* Align with Bootstrap's negative margin trick */
}

/* Underline effect for active tab */
.custom-tabview .p-tabview-nav-link::before {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 3px;
  background: linear-gradient(90deg, var(--blue-500), var(--purple-500), var(--cyan-500)); /* Re-map gradient colors to PrimeVue palette */
  transform: scaleX(0);
  transform-origin: bottom right;
  transition: transform 0.3s ease-out;
}

.custom-tabview .p-highlight .p-tabview-nav-link {
  /* Active tab styles */
  color: var(--text-color); /* Match #1e293b */
  background-color: var(--surface-card); /* White background for active tab */
  border-color: var(--surface-border) var(--surface-border) var(--surface-card); /* Border colors */
  box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.05); /* Shadow for active tab */
  font-weight: 700;
}

.custom-tabview .p-highlight .p-tabview-nav-link::before {
  transform: scaleX(1);
  transform-origin: bottom left;
}

.custom-tabview .p-tabview-nav-link:not(.p-highlight):hover {
  /* Hover effect for inactive tabs */
  color: var(--primary-color); /* Match #3b82f6 */
  background-color: var(--surface-0); /* Match #f8fafc */
}

/* Ensure tab content fills available width */
.p-tabview-panels {
  padding: 1rem; /* Adjust padding for the content area as needed */
  background-color: var(--surface-card); /* Matches active tab background */
  border-bottom-left-radius: var(--border-radius);
  border-bottom-right-radius: var(--border-radius);
}

/* Responsive Design (PrimeFlex handles many responsive aspects, but specific overrides might be needed) */
@media (max-width: 768px) {
  .company-details-section {
    padding: 1rem;
  }
}
</style>