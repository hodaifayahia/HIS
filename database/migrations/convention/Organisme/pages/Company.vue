<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import Company_card from '../cards/Company_card.vue';
import Contract_contact_tab from '../tabs/Contract_contact_tab.vue';
import axios from 'axios';

const route = useRoute();
const id = route.params.id;

// Enhance company ref with a more explicit id field
const company = ref({
  id: '',
  organisme_id: '', // Add this explicit field
  name: '',
  address: '',
  phone: '',
  email: ''
});

const fetchCompanyData = async () => {
  try {
    const response = await axios.get(`/api/organismes/${id}`);
    const data = response.data.data;
    
    // Update mapping to ensure ID is properly set
    company.value = {
      id: data.id,
      organisme_id: data.id, // Explicitly set organisme_id
      name: data.name,
      address: data.address,
      phone: data.phone || data.mobile,
      email: data.email
    };
  } catch (error) {
    console.error('Error fetching company data:', error);
  }
};

onMounted(async() => {
 await fetchCompanyData();
});
</script>


<template>
  <div class="content">
    <div class="title">
      <h1>Company</h1>
    </div>
    <!-- Pass the fetched data to Company_card -->
    <Company_card 
     :company="company"
    />
    <div class="title">
      <h1 id="contracts">Company Content</h1>
    </div>
    <Contract_contact_tab :companyId="company.id" />
  </div>
</template>

<style scoped>
.container {
  display: flex;
  flex-direction: row;
}
.content {
  display: flex;
  flex-direction: column;
  flex: 1;
  padding-top: 10px;
  padding-right: 20px;
  padding-bottom: 20px;
}
.title h1 {
  margin-top: 10px;
  margin-bottom: 20px;
  font-weight: bold;
  font-size: 2rem;
}
#contracts {
  margin-top: 1rem;
}
</style>