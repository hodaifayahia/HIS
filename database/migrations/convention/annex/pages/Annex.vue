<script setup>
import Annex_prestation_table from '../tables/Annex_prestation_table.vue';
import Annex_card from '../cards/Annex_card.vue';
import { defineProps, onMounted, ref } from "vue";
import axios from 'axios';

// Only get the ID from route params
const props = defineProps({
    id: String
});

// Create ref to store the fetched data
const annexData = ref(null);
const error = ref(null);

// Fetch annex data when component mounts
onMounted(async () => {
    try {
        if (props.id) {
            const response = await axios.get(`/api/annex/${props.id}`);
            annexData.value = response.data.data;
        }
    } catch (err) {
        error.value = "Failed to load annex data";
        console.error(err);
    }
});
</script>

<template>
    <div class="content">
        <div class="title">
            <h1 id="maintitle">Annex</h1>
        </div>
        
        <div v-if="error" class="alert alert-danger">{{ error }}</div>
        <div v-if="annexData">
           <Annex_card
                :name="annexData.annex_name"
                :description="annexData.description"
                :serviceName="annexData.service_name"
                :isActive="annexData.is_active"
                :minPrice="annexData.min_price"
                :maxPrice="annexData.max_price"
                :conventionMinPrice="annexData.convention_min_price"
                :conventionMaxPrice="annexData.convention_max_price"
                :discountPercentage="annexData.discount_percentage"
                :createdAt="annexData.created_at"
                :createdBy="annexData.created_by"
             />

        </div>

        <div class="title">
            <h1 id="contracts">Prestation</h1>
        </div>
        <Annex_prestation_table 
            v-if="annexData"
            :contractState="annexData.convention_status" 
            :avenantpage="'no'" 
            :serviceId="annexData.service_id"
            :serviceName="annexData.service_name"
            :contractdata="annexData"
            :annexId="annexData.id" 
        />
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
    margin-top: 40px;
    margin-bottom: 30px;
    font-weight: bold;
    font-size: 2rem;
}
.title #maintitle {
    margin-top: 20px;
    margin-bottom: 10px;
    font-weight: bold;
    font-size: 2rem;
}
#contracts {
    margin-top: 1rem;
}

/* Error styling */
.alert {
    padding: 0.75rem 1.25rem;
    margin-bottom: 1rem;
    border: 1px solid transparent;
    border-radius: 0.25rem;
}

.alert-danger {
    color: #721c24;
    background-color: #f8d7da;
    border-color: #f5c6cb;
}
</style>
