<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { useToastr } from '../toster';

const props = defineProps({
  requestId: {
    type: [Number, String],
    required: true,
  },
});

// Emit an event to inform the parent about the reply status
const emit = defineEmits(['submitted', 'reply-status-changed']);

const replyText = ref('');
const toaster = useToastr();
// New state to track if a reply has been submitted for this request
const hasReplied = ref(false);

const submitReply = async () => {
  if (!replyText.value.trim()) {
    toaster.error('Reply cannot be empty');
    return;
  }
  try {
    await axios.post(`/api/opinion-requests/${props.requestId}/reply`, {
      reply: replyText.value,
      status: 'replied',
    });
    toaster.success('Reply submitted successfully');
    replyText.value = '';
    hasReplied.value = true; // Set to true after successful submission
    emit('submitted'); // Existing emit for general submission
    emit('reply-status-changed', { requestId: props.requestId, status: 'replied' }); // New emit
  } catch (err) {
    // toaster.error('Failed to submit reply');
  }
};

const clearReply = () => {
  replyText.value = '';
};
</script>

<template>
  <div class="reply-tab">
    <h3 class="tab-title">Reply to Opinion Request</h3>
    <div v-if="!hasReplied">
      <div class="mb-3">
        <label for="replyText" class="form-label">Your Reply</label>
        <textarea
          id="replyText"
          v-model="replyText"
          class="form-control"
          rows="5"
          placeholder="Enter your reply here..."
        ></textarea>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-primary p-2 rounded-lg" @click="submitReply">
          Reply
        </button>
        <button class="btn btn-secondary ml-1 rounded-lg" @click="clearReply">
          Clear
        </button>
      </div>
    </div>
    <div v-else class="reply-status-message">
      <p>You have already replied to this opinion request.</p>
      </div>
  </div>
</template>
<style scoped>
.reply-tab {
  padding: 20px;
  width: 100%;
}

.tab-title {
  margin-bottom: 20px;
  font-size: 1.25rem;
  font-weight: 600;
}

.mb-3 {
  margin-bottom: 1rem;
}

.form-label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
}

.form-control {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #ccc;
  border-radius: 4px;
  font-size: 1rem;
}

.form-control:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}

.d-flex {
  display: flex;
}

.gap-2 {
  gap: 0.5rem;
}

.btn {
  padding: 0.5rem 1rem;
  border: none;
  cursor: pointer;
  font-size: 1rem;
  transition: background-color 0.2s;
}

.btn-primary {
  background-color: #007bff;
  color: white;
}

.reply-status-message {
  padding: 1rem;
  background-color: #e0ffe0; /* Light green background for success message */
  border: 1px solid #a0ffa0;
  border-radius: 0.5rem;
  color: #28a745; /* Green text color */
  text-align: center;
  font-weight: 500;
}
.btn-primary:hover {
  background-color: #0056b3;
}

.btn-secondary {
  background-color: #6c757d;
  color: white;
}

.btn-secondary:hover {
  background-color: #545b62;
}

.ml-1 {
  margin-left: 0.25rem;
}

.p-2 {
  padding: 0.5rem;
}

.rounded-lg {
  border-radius: 0.5rem;
}
</style>