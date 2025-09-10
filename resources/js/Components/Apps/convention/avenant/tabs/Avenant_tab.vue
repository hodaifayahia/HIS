<script setup>
import TabView from "primevue/tabview";
import TabPanel from "primevue/tabpanel";
import Agreement_para_table  from '../../contract/tables/Agreement_para_table.vue';
import Avenant_prestation_table  from '../tables/Avenant_prestation_table.vue';



import { ref, computed , defineProps } from "vue";
const props = defineProps({
    contractState: String,
    avenantpage: String,
    avenantState: String,
    avenantid:String,
    convention_data: Object,
});
</script>
<template>
  <section>        
        <TabView>
            <TabPanel header="Configurable Agreement">
                
                <Agreement_para_table :contractState="props.contractState" :avenantState="avenantState" :avenantid="avenantid" :avenantpage="'yes'" />
            </TabPanel>
            <TabPanel header="Prestation">
                <Avenant_prestation_table :contractState="props.contractState" :contractdata="props.convention_data" :avenantState="avenantState"  :avenantid="avenantid"  :avenantpage="'yes'"/>
            </TabPanel>
        </TabView>

    </section>
</template>