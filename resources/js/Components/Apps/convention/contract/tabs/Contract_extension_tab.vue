<script setup>
import { ref, defineProps } from "vue";
import axios from "axios";
import { useToastr } from '../../../../toster';

const toast = useToastr();

const props = defineProps({
  contractState: String,
  contractid: String,
});

// Form data for contract extension
const extensionData = ref({
  new_end_date: null,
  reason: '',
  notes: ''
});

// Computed property for date formatting
const formatDateForAPI = (date) => {
  if (!date) return null;
  const d = new Date(date);
  if (isNaN(d.getTime())) return null;
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, "0");
  const day = String(d.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
};

const extendContract = async () => {
  try {
    if (!extensionData.value.new_end_date) {
      toast.error('New end date is required');
      return;
    }

    const payload = {
      new_end_date: formatDateForAPI(extensionData.value.new_end_date),
      reason: extensionData.value.reason,
      notes: extensionData.value.notes
    };

    const response = await axios.post(`/api/conventions/${props.contractid}/extend`, payload);

    toast.success('Contract extended successfully');

    // Reset form
    extensionData.value = {
      new_end_date: null,
      reason: '',
      notes: ''
    };

  } catch (error) {
    toast.error(`Failed to extend contract: ${error.response?.data?.message || error.message}`);
    console.error("Error extending contract:", error.response?.data || error);
  }
};
</script>

<template>
  <div class="container-fluid py-4">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0">Contract Extension</h5>
        <small class="text-muted">Extend the contract end date without creating an avenant</small>
      </div>
      <div class="card-body">
        <form @submit.prevent="extendContract">
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="newEndDate" class="form-label">New End Date <span class="text-danger">*</span></label>
                <input
                  type="date"
                  class="form-control"
                  id="newEndDate"
                  v-model="extensionData.new_end_date"
                  :min="formatDateForAPI(new Date())"
                  required
                />
                <small class="form-text text-muted">Select the new end date for the contract</small>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="reason" class="form-label">Reason for Extension</label>
                <select class="form-select" id="reason" v-model="extensionData.reason">
                  <option value="">Select a reason</option>
                  <option value="performance">Performance Extension</option>
                  <option value="budget">Budget Adjustment</option>
                  <option value="regulatory">Regulatory Requirements</option>
                  <option value="client_request">Client Request</option>
                  <option value="other">Other</option>
                </select>
              </div>
            </div>
          </div>

          <div class="mb-3">
            <label for="notes" class="form-label">Notes</label>
            <textarea
              class="form-control"
              id="notes"
              rows="3"
              v-model="extensionData.notes"
              placeholder="Additional notes about the extension..."
            ></textarea>
          </div>

          <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-calendar-plus me-2"></i>
              Extend Contract
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.card {
  border-radius: 0.75rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
}

.form-control, .form-select {
  border-radius: 0.5rem;
  padding: 0.625rem 0.75rem;
}

.btn-primary {
  background-color: #007bff;
  border-color: #007bff;
}

.btn-primary:hover {
  background-color: #0056b3;
  border-color: #0056b3;
}
</style>