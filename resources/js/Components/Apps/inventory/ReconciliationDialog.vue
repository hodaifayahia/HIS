<template>
  <Dialog
    :visible="visible"
    modal
    :closable="false"
    :style="{ width: '90vw' }"
    class="tw-max-w-6xl"
  >
    <template #header>
      <div class="tw-flex tw-items-center tw-gap-3">
        <i class="pi pi-chart-bar tw-text-blue-500 tw-text-2xl"></i>
        <div>
          <h2 class="tw-text-2xl tw-font-bold tw-text-gray-800">
            Reconciliation Report
          </h2>
          <p class="tw-text-sm tw-text-gray-500">
            {{ data?.participants_sent || 0 }} participants submitted counts
          </p>
        </div>
      </div>
    </template>

    <!-- Summary Cards -->
    <div class="tw-grid tw-grid-cols-2 tw-gap-4 tw-mb-6">
      <!-- Consensus Card -->
      <div class="tw-bg-green-50 tw-border tw-border-green-200 tw-rounded-lg tw-p-4">
        <div class="tw-flex tw-items-center tw-gap-3">
          <i class="pi pi-check-circle tw-text-green-500 tw-text-3xl"></i>
          <div>
            <div class="tw-text-2xl tw-font-bold tw-text-green-700">
              {{ data?.consensus_products || 0 }}
            </div>
            <div class="tw-text-sm tw-text-green-600">Products in Consensus</div>
            <div class="tw-text-xs tw-text-green-500">All participants agree</div>
          </div>
        </div>
      </div>

      <!-- Disputed Card -->
      <div class="tw-bg-amber-50 tw-border tw-border-amber-200 tw-rounded-lg tw-p-4">
        <div class="tw-flex tw-items-center tw-gap-3">
          <i class="pi pi-exclamation-triangle tw-text-amber-500 tw-text-3xl"></i>
          <div>
            <div class="tw-text-2xl tw-font-bold tw-text-amber-700">
              {{ data?.disputed_products || 0 }}
            </div>
            <div class="tw-text-sm tw-text-amber-600">Disputed Products</div>
            <div class="tw-text-xs tw-text-amber-500">Recount required</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabs for Consensus vs Disputed -->
    <TabView>
      <!-- Consensus Tab -->
      <TabPanel header="✓ Consensus Products">
        <DataTable
          :value="data?.consensus || []"
          :paginator="true"
          :rows="10"
          responsiveLayout="scroll"
        >
          <Column field="product_id" header="Product ID" style="width: 100px" />
          <Column field="agreed_quantity" header="Agreed Quantity" style="width: 150px">
            <template #body="slotProps">
              <Tag :value="slotProps.data.agreed_quantity" severity="success" />
            </template>
          </Column>
          <Column header="Participants" style="min-width: 300px">
            <template #body="slotProps">
              <div class="tw-flex tw-gap-1 tw-flex-wrap">
                <Tag
                  v-for="p in slotProps.data.participant_counts"
                  :key="p.participant_id"
                  :value="`User ${p.participant_id}`"
                  severity="info"
                  icon="pi pi-user"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </TabPanel>

      <!-- Disputed Tab -->
      <TabPanel header="⚠ Disputed Products">
        <DataTable
          :value="data?.discrepancies || []"
          v-model:selection="selectedDisputed"
          :paginator="true"
          :rows="10"
          selectionMode="multiple"
          dataKey="product_id"
          responsiveLayout="scroll"
        >
          <Column selectionMode="multiple" headerStyle="width: 3rem" />
          <Column field="product_id" header="Product ID" style="width: 100px" />
          <Column header="Counts" style="min-width: 400px">
            <template #body="slotProps">
              <div class="tw-space-y-2">
                <div
                  v-for="count in slotProps.data.participant_counts"
                  :key="count.participant_id"
                  class="tw-flex tw-items-center tw-justify-between tw-p-2 tw-bg-gray-50 tw-rounded"
                >
                  <span class="tw-font-semibold tw-text-gray-700">
                    <i class="pi pi-user tw-mr-2 tw-text-gray-400"></i>
                    User {{ count.participant_id }}
                  </span>
                  <Tag :value="count.actual_quantity" severity="warning" />
                </div>
              </div>
            </template>
          </Column>
          <Column field="variance" header="Variance" style="width: 120px">
            <template #body="slotProps">
              <Tag
                :value="`±${slotProps.data.variance}`"
                severity="danger"
                icon="pi pi-exclamation-triangle"
              />
            </template>
          </Column>
        </DataTable>

        <!-- Assign Recount Section -->
        <div v-if="selectedDisputed.length > 0" class="tw-mt-4 tw-p-4 tw-bg-purple-50 tw-rounded-lg tw-border tw-border-purple-200">
          <h3 class="tw-font-bold tw-text-purple-800 tw-mb-3">
            Assign {{ selectedDisputed.length }} products for recount
          </h3>
          
          <!-- Show other counts checkbox -->
          <div class="tw-mb-3 tw-flex tw-items-center tw-gap-2">
            <input
              type="checkbox"
              id="showOtherCounts"
              v-model="showOtherCounts"
              class="tw-w-4 tw-h-4 tw-text-purple-600 tw-bg-gray-100 tw-border-gray-300 tw-rounded focus:tw-ring-purple-500"
            />
            <label for="showOtherCounts" class="tw-text-sm tw-text-gray-700 tw-font-medium">
              Allow participant to see other participants' counts
            </label>
          </div>

          <div class="tw-flex tw-gap-3">
            <Dropdown
              v-model="selectedParticipants"
              :options="participants || []"
              optionLabel="name"
              optionValue="id"
              placeholder="Select users"
              class="tw-flex-1"
              multiple
              display="chip"
            >
              <template #option="slotProps">
                <div class="tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-user tw-text-gray-400"></i>
                  <span>{{ slotProps.option.name }}</span>
                </div>
              </template>
            </Dropdown>
            <Button
              label="Assign Recount"
              icon="pi pi-refresh"
              @click="assignRecount"
              :disabled="!selectedParticipants || selectedParticipants.length === 0"
              class="tw-bg-purple-500 tw-text-white"
            />
          </div>
        </div>
      </TabPanel>
    </TabView>

    <template #footer>
      <div class="tw-flex tw-justify-between">
        <Button
          label="Close"
          icon="pi pi-times"
          @click="$emit('close')"
          class="p-button-text"
        />
        <Button
          v-if="data?.disputed_products === 0"
          label="Finalize Audit"
          icon="pi pi-check"
          @click="$emit('finalize')"
          class="tw-bg-green-500 tw-text-white"
        />
      </div>
    </template>
  </Dialog>
</template>

<script setup>
import { ref } from 'vue';
import Dialog from 'primevue/dialog';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Dropdown from 'primevue/dropdown';

const props = defineProps({
  visible: Boolean,
  data: Object,
  participants: Array
});

const emit = defineEmits(['close', 'assign-recount', 'finalize']);

const selectedDisputed = ref([]);
const selectedParticipants = ref([]);
const showOtherCounts = ref(false);

function assignRecount() {
  // Ensure selectedParticipants is an array
  const participants = Array.isArray(selectedParticipants.value) 
    ? selectedParticipants.value 
    : (selectedParticipants.value ? [selectedParticipants.value] : []);
  
  if (!participants || participants.length === 0) {
    console.error('No participants selected');
    return;
  }

  if (!selectedDisputed.value || selectedDisputed.value.length === 0) {
    console.error('No products selected');
    return;
  }

  console.log('Assigning recount:', {
    participants: participants,
    products: selectedDisputed.value.map(p => p.product_id),
    showOtherCounts: showOtherCounts.value
  });

  // Emit for each selected participant
  participants.forEach(participantId => {
    emit('assign-recount', {
      participant_id: participantId,
      product_ids: selectedDisputed.value.map(p => p.product_id),
      show_other_counts: showOtherCounts.value
    });
  });
  
  // Clear selections after all assignments
  selectedDisputed.value = [];
  selectedParticipants.value = [];
  showOtherCounts.value = false;
}
</script>
