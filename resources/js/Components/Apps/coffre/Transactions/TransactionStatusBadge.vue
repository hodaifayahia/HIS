<script setup>
import { computed } from 'vue';
import Tag from 'primevue/tag';

const props = defineProps({
  status: {
    type: String,
    required: true
  },
  transaction: {
    type: Object,
    default: null
  }
});

const statusConfig = computed(() => {
  switch (props.status) {
    case 'pending':
      return {
        label: 'Pending Approval',
        severity: 'warning',
        icon: 'pi pi-clock'
      };
    case 'completed':
      return {
        label: 'Completed',
        severity: 'success',
        icon: 'pi pi-check'
      };
    case 'rejected':
      return {
        label: 'Rejected',
        severity: 'danger',
        icon: 'pi pi-times'
      };
    default:
      return {
        label: props.status || 'Unknown',
        severity: 'info',
        icon: 'pi pi-question'
      };
  }
});

const showApprovalInfo = computed(() => {
  return props.status === 'pending' && props.transaction?.approval_request;
});
</script>

<template>
  <div class="tw-flex tw-items-center tw-gap-2">
    <Tag 
      :value="statusConfig.label" 
      :severity="statusConfig.severity"
      :icon="statusConfig.icon"
      class="tw-text-xs"
    />
    
    <!-- Show approval info for pending transactions -->
    <div v-if="showApprovalInfo" class="tw-text-xs tw-text-gray-500">
      <i class="pi pi-users"></i>
      {{ transaction.approval_request.candidate_user_ids?.length || 0 }} approvers
    </div>
    
    <!-- Show bank transfer indicator -->
    <div v-if="transaction?.destination_banque_id" class="tw-text-xs tw-text-blue-600">
      <i class="pi pi-building"></i>
      Bank Transfer
    </div>
  </div>
</template>
