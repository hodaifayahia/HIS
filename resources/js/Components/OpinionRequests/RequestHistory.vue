<script setup>
import { ref, computed, watch, onMounted } from 'vue'; // Import watch and onMounted
import { defineProps, defineEmits } from 'vue';
import axios from 'axios'; // Import axios

const props = defineProps({
  filteredRequests: {
    type: Array,
    required: true
  },
  filterStatus: {
    type: String,
    default: 'all'
  },
  sortOrder: {
    type: String,
    default: 'newest'
  },
  appointmentId: {
    type: [Number, String],
    default: null
  }
});

const emit = defineEmits(['update:filterStatus', 'update:sortOrder', 'viewReplies']);

const showFilters = ref(false);
const expandedConversations = ref(new Set());

// New state for fetched opinion requests
const opinionRequests = ref([]);
const loading = ref(false);
const error = ref(null);

// Function to fetch opinion requests
const fetchOpinionRequests = async () => {
  if (!props.appointmentId) {
    // If appointmentId is null, clear opinionRequests and return
    opinionRequests.value = [];
    return;
  }

  loading.value = true;
  error.value = null;

  try {
    const response = await axios.get(`/api/opinion-requests/${props.appointmentId}`);
    opinionRequests.value = response.data.data || [];
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to fetch opinion requests';
    console.error('Error fetching opinion requests:', err);
    opinionRequests.value = []; // Clear on error
  } finally {
    loading.value = false;
  }
};

// Determine which source of requests to use
const sourceRequests = computed(() => {
  return props.appointmentId ? opinionRequests.value : props.filteredRequests;
});

// Group requests by doctor (sender-receiver pair) using sourceRequests
const groupedConversations = computed(() => {
  const groups = {};

  sourceRequests.value.forEach(request => { // Use sourceRequests here
    const doctorKey = request.reciver_doctor_name || 'Unknown Doctor';

    if (!groups[doctorKey]) {
      groups[doctorKey] = {
        doctorName: request.reciver_doctor_name,
        specialization: request.specialization || 'General Medicine',
        messages: [],
        lastActivity: request.created_at,
        hasUnread: false,
        conversationStatus: 'pending'
      };
    }

    groups[doctorKey].messages.push(request);

    // Update last activity to the most recent
    if (new Date(request.created_at) > new Date(groups[doctorKey].lastActivity)) {
      groups[doctorKey].lastActivity = request.created_at;
    }

    if (request.replied_at && new Date(request.replied_at) > new Date(groups[doctorKey].lastActivity)) {
      groups[doctorKey].lastActivity = request.replied_at;
    }

    // Determine conversation status (prioritize active/replied over pending)
    if (request.status === 'replied' || request.status === 'approved') {
      groups[doctorKey].conversationStatus = 'active';
    } else if (request.status === 'declined' && groups[doctorKey].conversationStatus === 'pending') {
      groups[doctorKey].conversationStatus = 'declined';
    }
  });

  // Sort messages within each group by date
  Object.values(groups).forEach(group => {
    group.messages.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
  });

  // Convert to array and sort by last activity
  const conversationsArray = Object.values(groups);

  return conversationsArray.sort((a, b) => {
    if (props.sortOrder === 'newest') {
      return new Date(b.lastActivity) - new Date(a.lastActivity);
    } else {
      return new Date(a.lastActivity) - new Date(b.lastActivity);
    }
  });
});

const getStatusColor = (status) => {
  const colors = {
    pending: 'bg-yellow-100 text-yellow-800 border-yellow-200',
    approved: 'bg-green-100 text-green-800 border-green-200',
    replied: 'bg-blue-100 text-blue-800 border-blue-200',
    declined: 'bg-red-100 text-red-800 border-red-200',
    active: 'bg-green-100 text-green-800 border-green-200'
  };
  return colors[status] || 'bg-gray-100 text-gray-800 border-gray-200';
};

const getStatusIcon = (status) => {
  const icons = {
    pending: 'fas fa-clock',
    approved: 'fas fa-check-circle',
    replied: 'fas fa-comment-dots',
    declined: 'fas fa-times-circle',
    active: 'fas fa-comments'
  };
  return icons[status] || 'fas fa-question-circle';
};

const toggleConversationExpansion = (doctorName) => {
  if (expandedConversations.value.has(doctorName)) {
    expandedConversations.value.delete(doctorName);
  } else {
    expandedConversations.value.add(doctorName);
  }
};

const isConversationExpanded = (doctorName) => {
  return expandedConversations.value.has(doctorName);
};

const formatTime = (dateString) => {
  return new Date(dateString).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit'
  });
};

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

const formatRelativeTime = (dateString) => {
  const now = new Date();
  const date = new Date(dateString);
  const diffInMs = now - date;
  const diffInHours = diffInMs / (1000 * 60 * 60);
  const diffInDays = diffInMs / (1000 * 60 * 60 * 24);

  if (diffInHours < 24) {
    return 'Today';
  } else if (diffInDays < 7) {
    return `${Math.floor(diffInDays)} days ago`;
  } else {
    return formatDate(dateString);
  }
};

const getMessagePreview = (conversation) => {
  const lastMessage = conversation.messages[conversation.messages.length - 1];
  if (lastMessage.reply) {
    return `Dr. ${conversation.doctorName}: ${lastMessage.reply.substring(0, 80)}...`;
  } else {
    return `You: ${(lastMessage.request || lastMessage.description || 'Sent a consultation request').substring(0, 80)}...`;
  }
};

const getMessageCount = (conversation) => {
  let count = conversation.messages.length; // Patient messages
  count += conversation.messages.filter(msg => msg.reply).length; // Doctor replies
  return count;
};

const handleFilterStatusChange = (event) => {
  emit('update:filterStatus', event.target.value);
};

const handleSortOrderChange = (event) => {
  emit('update:sortOrder', event.target.value);
};

// Watch for changes in appointmentId and refetch
watch(() => props.appointmentId, (newVal, oldVal) => {
  if (newVal !== oldVal) {
    fetchOpinionRequests();
  }
}, { immediate: true }); // immediate: true runs the watcher once immediately on component mount

// You can also call fetchOpinionRequests on mounted if you prefer,
// but the immediate watcher above handles it well.
// onMounted(() => {
//   fetchOpinionRequests();
// });
</script>
<template>
  <div class="history-section">
    <div class="premium-card">
      <div class="section-header">
        <div class="header-content">
          <h2 class="section-title">
            <i class="fas fa-comments-medical me-2"></i>
            Medical Consultations
          </h2>
          <p class="section-description">Your conversation history with doctors</p>
        </div>
        <div class="header-actions">
          <button
            class="btn-filter"
            @click="showFilters = !showFilters"
          >
            <i class="fas fa-filter me-2"></i>
            Filters
          </button>
        </div>
      </div>

      <div v-show="showFilters" class="filters-panel">
        <div class="filter-group">
          <label class="filter-label">Status</label>
          <select :value="filterStatus" @change="handleFilterStatusChange" class="filter-select">
            <option value="all">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="replied">Replied</option>
            <option value="declined">Declined</option>
          </select>
        </div>
        <div class="filter-group">
          <label class="filter-label">Sort By</label>
          <select :value="sortOrder" @change="handleSortOrderChange" class="filter-select">
            <option value="newest">Newest First</option>
            <option value="oldest">Oldest First</option>
          </select>
        </div>
      </div>

      <div v-if="groupedConversations.length === 0" class="empty-state">
        <div class="empty-icon">
          <i class="fas fa-comments"></i>
        </div>
        <h3>No consultations found</h3>
        <p>{{ filterStatus === 'all' ? 'You haven\'t started any consultations yet' : `No ${filterStatus} consultations found` }}</p>
      </div>

      <div v-else class="chat-conversations">
        <div
          v-for="conversation in groupedConversations"
          :key="conversation.doctorName"
          class="chat-conversation"
        >
          <!-- Conversation Header -->
          <div class="conversation-header" @click="toggleConversationExpansion(conversation.doctorName)">
            <div class="conversation-preview">
              <div class="doctor-info">
                <div class="doctor-avatar">
                  <i class="fas fa-user-md"></i>
                </div>
                <div class="doctor-details">
                  <h4 class="doctor-name">Dr. {{ conversation.doctorName }}</h4>
                  <p class="doctor-specialty">{{ conversation.specialization }}</p>
                  <p class="message-preview">{{ getMessagePreview(conversation) }}</p>
                </div>
              </div>
              <div class="conversation-meta">
                <div class="meta-top">
                  <span class="status-badge" :class="getStatusColor(conversation.conversationStatus)">
                    <i :class="getStatusIcon(conversation.conversationStatus)" class="me-1"></i>
                    {{ conversation.conversationStatus.charAt(0).toUpperCase() + conversation.conversationStatus.slice(1) }}
                  </span>
                  <span class="conversation-date">{{ formatRelativeTime(conversation.lastActivity) }}</span>
                </div>
                <div class="meta-bottom">
                  <span class="message-count">
                    <i class="fas fa-comment me-1"></i>
                    {{ getMessageCount(conversation) }} messages
                  </span>
                  <i class="fas fa-chevron-down expand-icon" :class="{ 'expanded': isConversationExpanded(conversation.doctorName) }"></i>
                </div>
              </div>
            </div>
          </div>

          <!-- Chat Messages -->
          <div v-show="isConversationExpanded(conversation.doctorName)" class="chat-messages">
            <div v-for="message in conversation.messages" :key="message.id" class="message-thread">
              <!-- Patient Message -->
              <div class="message-group patient-group">
                <div class="message patient-message">
                  <div class="message-header">
                    <span class="sender-name">You</span>
                    <span class="message-time">{{ formatDate(message.created_at) }} at {{ formatTime(message.created_at) }}</span>
                  </div>
                  <div class="message-content">
                    <div class="message-text">
                      {{ message.request || message.description || 'Opinion request sent to doctor.' }}
                    </div>
                    <div v-if="message.attachments && message.attachments.length" class="message-attachments">
                      <div v-for="attachment in message.attachments" :key="attachment.id" class="attachment-item">
                        <i class="fas fa-paperclip me-1"></i>
                        {{ attachment.name }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Doctor Reply -->
              <div v-if="message.reply" class="message-group doctor-group">
                <div class="message doctor-message">
                  <div class="message-header">
                    <span class="sender-name">Dr. {{ conversation.doctorName }}</span>
                    <span class="message-time" v-if="message.replied_at">
                      {{ formatDate(message.replied_at) }} at {{ formatTime(message.replied_at) }}
                    </span>
                  </div>
                  <div class="message-content">
                    <div class="message-text">
                      {{ message.reply }}
                    </div>
                  </div>
                </div>
              </div>

              <!-- Status Messages -->
              <div v-else-if="message.status === 'pending'" class="message-group system-group">
                <div class="system-message">
                  <i class="fas fa-clock me-2"></i>
                  Waiting for doctor's response...
                </div>
              </div>

              <div v-else-if="message.status === 'declined'" class="message-group system-group">
                <div class="system-message declined">
                  <i class="fas fa-times-circle me-2"></i>
                  Request was declined by the doctor
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Base Styles */
.history-section {
  margin-top: 2rem;
}

.premium-card {
  background: white;
  border-radius: 20px;
  box-shadow: 
    0 20px 25px -5px rgba(0, 0, 0, 0.1),
    0 10px 10px -5px rgba(0, 0, 0, 0.04);
  padding: 2rem;
  margin-bottom: 2rem;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.section-title {
  font-size: 1.8rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}

.section-description {
  color: #64748b;
  margin: 0.5rem 0 0;
  font-size: 1rem;
}

.btn-filter {
  padding: 0.875rem 1.5rem;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
  border: 2px solid #e2e8f0;
  background: white;
  color: #374151;
  font-size: 0.95rem;
}

.btn-filter:hover {
  border-color: #4f46e5;
  color: #4f46e5;
}

/* Filters Panel */
.filters-panel {
  background: #f8fafc;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  display: flex;
  gap: 2rem;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  min-width: 160px;
}

.filter-label {
  font-weight: 600;
  color: #374151;
  font-size: 0.9rem;
}

.filter-select {
  padding: 0.75rem;
  border-radius: 8px;
  border: 2px solid #e2e8f0;
  background: white;
  font-size: 0.95rem;
  color: #374151;
  transition: all 0.3s ease;
}

.filter-select:focus {
  outline: none;
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 3rem 2rem;
  color: #64748b;
}

.empty-icon {
  font-size: 4rem;
  color: #d1d5db;
  margin-bottom: 1rem;
}

.empty-state h3 {
  font-size: 1.5rem;
  font-weight: 600;
  color: #374151;
  margin: 0 0 0.5rem;
}

/* Chat Conversations */
.chat-conversations {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.chat-conversation {
  border: 2px solid #e2e8f0;
  border-radius: 16px;
  overflow: hidden;
  transition: all 0.3s ease;
}

.chat-conversation:hover {
  border-color: #4f46e5;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Conversation Header */
.conversation-header {
  padding: 1.5rem;
  background: #f8fafc;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.conversation-header:hover {
  background: #f1f5f9;
}

.conversation-preview {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
}

.doctor-info {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  flex: 1;
}

.doctor-avatar {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.2rem;
  flex-shrink: 0;
}

.doctor-details {
  flex: 1;
  min-width: 0;
}

.doctor-name {
  font-size: 1.1rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0 0 0.25rem;
}

.doctor-specialty {
  color: #64748b;
  margin: 0 0 0.5rem;
  font-size: 0.9rem;
}

.message-preview {
  color: #64748b;
  margin: 0;
  font-size: 0.9rem;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.conversation-meta {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.5rem;
  flex-shrink: 0;
}

.meta-top {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.meta-bottom {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.status-badge {
  padding: 0.4rem 0.8rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  border: 1px solid;
  display: flex;
  align-items: center;
  gap: 0.25rem;
  white-space: nowrap;
}

.conversation-date {
  color: #64748b;
  font-size: 0.85rem;
  white-space: nowrap;
}

.message-count {
  color: #64748b;
  font-size: 0.85rem;
  display: flex;
  align-items: center;
  white-space: nowrap;
}

.expand-icon {
  color: #64748b;
  transition: transform 0.3s ease;
}

.expand-icon.expanded {
  transform: rotate(180deg);
}

/* Chat Messages */
.chat-messages {
  padding: 1.5rem;
  background: #fafbfc;
  border-top: 1px solid #e2e8f0;
}

.message-thread {
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #e2e8f0;
}

.message-thread:last-child {
  margin-bottom: 0;
  padding-bottom: 0;
  border-bottom: none;
}

.message-group {
  margin-bottom: 1rem;
}

.message-group:last-child {
  margin-bottom: 0;
}

.message {
  max-width: 80%;
  border-radius: 16px;
  padding: 1rem 1.25rem;
  position: relative;
  word-wrap: break-word;
}

.patient-group {
  display: flex;
  justify-content: flex-end;
}

.patient-message {
  background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
  color: white;
  border-bottom-right-radius: 6px;
}

.doctor-group {
  display: flex;
  justify-content: flex-start;
}

.doctor-message {
  background: white;
  border: 2px solid #e2e8f0;
  color: #374151;
  border-bottom-left-radius: 6px;
}

.message-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
  gap: 1rem;
}

.sender-name {
  font-weight: 600;
  font-size: 0.9rem;
}

.patient-message .sender-name {
  color: rgba(255, 255, 255, 0.9);
}

.message-time {
  font-size: 0.8rem;
  opacity: 0.7;
  white-space: nowrap;
}

.message-content {
  line-height: 1.5;
}

.message-text {
  margin-bottom: 0.5rem;
}

.message-text:last-child {
  margin-bottom: 0;
}

.message-attachments {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-top: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px solid rgba(255, 255, 255, 0.2);
}

.patient-message .message-attachments {
  border-color: rgba(255, 255, 255, 0.2);
}

.doctor-message .message-attachments {
  border-color: #e2e8f0;
}

.attachment-item {
  font-size: 0.9rem;
  opacity: 0.9;
  display: flex;
  align-items: center;
}

/* System Messages */
.system-group {
  display: flex;
  justify-content: center;
}

.system-message {
  background: #f1f5f9;
  color: #64748b;
  padding: 0.75rem 1rem;
  border-radius: 20px;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  border: 1px solid #e2e8f0;
}

.system-message.declined {
  background: #fef2f2;
  color: #dc2626;
  border-color: #fecaca;
}

/* Responsive Design */
@media (max-width: 768px) {
  .section-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .filters-panel {
    flex-direction: column;
    gap: 1rem;
  }

  .conversation-preview {
    flex-direction: column;
    gap: 1rem;
  }

  .conversation-meta {
    width: 100%;
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
  }

  .meta-top, .meta-bottom {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }

  .message {
    max-width: 90%;
  }

  .message-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
  }
}
</style>