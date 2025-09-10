<script setup>
const props = defineProps({
  showReplies: {
    type: Boolean,
    required: true
  },
  selectedRequest: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['closeModal']);

const closeModal = () => {
  emit('closeModal');
};

const formatTime = (dateString) => {
  return new Date(dateString).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit'
  });
};

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

const getStatusColor = (status) => {
  const colors = {
    pending: 'bg-yellow-100 text-yellow-800 border-yellow-200',
    approved: 'bg-green-100 text-green-800 border-green-200',
    replied: 'bg-blue-100 text-blue-800 border-blue-200',
    declined: 'bg-red-100 text-red-800 border-red-200'
  };
  return colors[status] || 'bg-gray-100 text-gray-800 border-gray-200';
};

const getStatusIcon = (status) => {
  const icons = {
    pending: 'fas fa-clock',
    approved: 'fas fa-check-circle',
    replied: 'fas fa-comment-dots',
    declined: 'fas fa-times-circle'
  };
  return icons[status] || 'fas fa-question-circle';
};
</script>

<template>
  <Transition name="modal">
    <div v-if="showReplies && selectedRequest" class="modal-overlay" @click.self="closeModal">
      <div class="modal-container">
        <!-- Chat Header -->
        <div class="chat-header">
          <div class="doctor-info">
            <div class="doctor-avatar">
              <i class="fas fa-user-md"></i>
            </div>
            <div class="doctor-details">
              <h3 class="doctor-name">Dr. {{ selectedRequest.reciver_doctor_name }}</h3>
              <p class="doctor-specialty">{{ selectedRequest.specialization || 'General Medicine' }}</p>
              <div class="status-info">
                <span class="status-badge" :class="getStatusColor(selectedRequest.status)">
                  <i :class="getStatusIcon(selectedRequest.status)" class="me-1"></i>
                  {{ selectedRequest.status.charAt(0).toUpperCase() + selectedRequest.status.slice(1) }}
                </span>
              </div>
            </div>
          </div>
          <button class="modal-close" @click="closeModal">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <!-- Chat Body -->
        <div class="chat-body">
          <div class="chat-date-header">
            {{ formatDate(selectedRequest.created_at) }}
          </div>

          <!-- Patient Message -->
          <div class="message-container patient-container">
            <div class="message patient-message">
              <div class="message-content">
                <div class="message-text">
                  {{ selectedRequest.message || selectedRequest.description || 'I would like to request your medical opinion on my case.' }}
                </div>
                <div v-if="selectedRequest.attachments && selectedRequest.attachments.length" class="message-attachments">
                  <div class="attachments-header">
                    <i class="fas fa-paperclip me-1"></i>
                    Attachments ({{ selectedRequest.attachments.length }})
                  </div>
                  <div v-for="attachment in selectedRequest.attachments" :key="attachment.id" class="attachment-item">
                    <i class="fas fa-file-medical me-2"></i>
                    {{ attachment.name }}
                  </div>
                </div>
              </div>
              <div class="message-meta">
                <span class="message-time">{{ formatTime(selectedRequest.created_at) }}</span>
                <i class="fas fa-check message-status"></i>
              </div>
            </div>
            <div class="message-avatar patient-avatar">
              <i class="fas fa-user"></i>
            </div>
          </div>

          <!-- System Message for Status -->
          <div v-if="selectedRequest.status === 'pending'" class="system-message">
            <div class="system-content">
              <i class="fas fa-clock me-2"></i>
              Waiting for Dr. {{ selectedRequest.reciver_doctor_name }}'s response...
            </div>
            <div class="system-time">{{ formatTime(selectedRequest.created_at) }}</div>
          </div>

          <div v-if="selectedRequest.status === 'approved' && !selectedRequest.reply" class="system-message approved">
            <div class="system-content">
              <i class="fas fa-check-circle me-2"></i>
              Dr. {{ selectedRequest.reciver_doctor_name }} has accepted your request and is preparing a response
            </div>
            <div class="system-time">{{ selectedRequest.approved_at ? formatTime(selectedRequest.approved_at) : 'Recently' }}</div>
          </div>

          <div v-if="selectedRequest.status === 'declined'" class="system-message declined">
            <div class="system-content">
              <i class="fas fa-times-circle me-2"></i>
              Request was declined by Dr. {{ selectedRequest.reciver_doctor_name }}
            </div>
            <div class="system-time">{{ selectedRequest.declined_at ? formatTime(selectedRequest.declined_at) : 'Recently' }}</div>
          </div>

          <!-- Doctor Reply -->
          <div v-if="selectedRequest.reply" class="message-container doctor-container">
            <div class="message-avatar doctor-avatar">
              <i class="fas fa-user-md"></i>
            </div>
            <div class="message doctor-message">
              <div class="message-content">
                <div class="doctor-badge">
                  <i class="fas fa-stethoscope me-1"></i>
                  Medical Opinion
                </div>
                <div class="message-text">
                  {{ selectedRequest.reply }}
                </div>
              </div>
              <div class="message-meta">
                <span class="message-time">{{ selectedRequest.replied_at ? formatTime(selectedRequest.replied_at) : 'Recently' }}</span>
                <i class="fas fa-check-double message-status read"></i>
              </div>
            </div>
          </div>

          <!-- Typing Indicator (for pending approved requests) -->
          <div v-if="selectedRequest.status === 'approved' && !selectedRequest.reply" class="typing-indicator">
            <div class="message-avatar doctor-avatar">
              <i class="fas fa-user-md"></i>
            </div>
            <div class="typing-bubble">
              <div class="typing-dots">
                <span></span>
                <span></span>
                <span></span>
              </div>
              <div class="typing-text">Dr. {{ selectedRequest.reciver_doctor_name }} is typing...</div>
            </div>
          </div>
        </div>

        <!-- Chat Footer -->
        <div class="chat-footer">
          <div class="conversation-info">
            <span class="info-item">
              <i class="fas fa-calendar-plus me-1"></i>
              Started: {{ formatDate(selectedRequest.created_at) }}
            </span>
            <span v-if="selectedRequest.replied_at" class="info-item">
              <i class="fas fa-reply me-1"></i>
              Replied: {{ formatDate(selectedRequest.replied_at) }}
            </span>
          </div>
          <button class="btn-close" @click="closeModal">
            <i class="fas fa-times me-2"></i>
            Close Conversation
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
/* Modal Base */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
  backdrop-filter: blur(4px);
}

.modal-container {
  background: white;
  border-radius: 20px;
  max-width: 800px;
  width: 90%;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
  animation: modalSlideIn 0.3s ease-out;
  overflow: hidden;
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* Chat Header */
.chat-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 2rem;
  background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
  color: white;
}

.doctor-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.doctor-avatar {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(10px);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.5rem;
  border: 2px solid rgba(255, 255, 255, 0.3);
}

.doctor-name {
  font-size: 1.4rem;
  font-weight: 700;
  margin: 0 0 0.25rem;
}

.doctor-specialty {
  margin: 0 0 0.5rem;
  opacity: 0.9;
  font-size: 1rem;
}

.status-badge {
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 600;
  border: 1px solid;
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  background: white;
}

.modal-close {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  cursor: pointer;
  transition: all 0.2s ease;
  backdrop-filter: blur(10px);
}

.modal-close:hover {
  background: rgba(255, 255, 255, 0.3);
}

/* Chat Body */
.chat-body {
  flex: 1;
  overflow-y: auto;
  padding: 2rem;
  background: #f8fafc;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.chat-date-header {
  text-align: center;
  color: #64748b;
  font-size: 0.9rem;
  font-weight: 500;
  padding: 0.5rem 1rem;
  background: rgba(100, 116, 139, 0.1);
  border-radius: 20px;
  align-self: center;
}

/* Message Containers */
.message-container {
  display: flex;
  gap: 1rem;
  align-items: flex-end;
}

.patient-container {
  flex-direction: row-reverse;
  justify-content: flex-start;
}

.doctor-container {
  flex-direction: row;
  justify-content: flex-start;
}

.message {
  max-width: 70%;
  border-radius: 18px;
  padding: 1rem 1.25rem;
  position: relative;
  word-wrap: break-word;
}

.patient-message {
  background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
  color: white;
  border-bottom-right-radius: 6px;
}

.doctor-message {
  background: white;
  border: 2px solid #e2e8f0;
  color: #374151;
  border-bottom-left-radius: 6px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.message-content {
  margin-bottom: 0.5rem;
}

.message-text {
  line-height: 1.6;
  font-size: 1rem;
}

.doctor-badge {
  background: rgba(79, 70, 229, 0.1);
  color: #4f46e5;
  padding: 0.5rem 0.75rem;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 600;
  margin-bottom: 0.75rem;
  display: inline-flex;
  align-items: center;
}

.message-attachments {
  margin-top: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px solid rgba(255, 255, 255, 0.2);
}

.attachments-header {
  font-size: 0.9rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
  opacity: 0.9;
}

.attachment-item {
  font-size: 0.9rem;
  opacity: 0.8;
  display: flex;
  align-items: center;
  margin-bottom: 0.25rem;
  padding: 0.25rem 0;
}

.message-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 0.5rem;
  font-size: 0.8rem;
  opacity: 0.7;
}

.message-time {
  font-size: 0.8rem;
}

.message-status {
  font-size: 0.8rem;
}

.message-status.read {
  color: #4f46e5;
}

.message-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  flex-shrink: 0;
}

.patient-avatar {
  background: linear-gradient(135deg, #64748b 0%, #475569 100%);
  color: white;
}

.doctor-avatar {
  background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
  color: white;
}

/* System Messages */
.system-message {
  align-self: center;
  background: rgba(100, 116, 139, 0.1);
  color: #64748b;
  padding: 0.75rem 1rem;
  border-radius: 20px;
  font-size: 0.9rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
  border: 1px solid rgba(100, 116, 139, 0.2);
}

.system-message.approved {
  background: rgba(34, 197, 94, 0.1);
  color: #16a34a;
  border-color: rgba(34, 197, 94, 0.2);
}

.system-message.declined {
  background: rgba(239, 68, 68, 0.1);
  color: #dc2626;
  border-color: rgba(239, 68, 68, 0.2);
}

.system-content {
  display: flex;
  align-items: center;
  font-weight: 500;
}

.system-time {
  font-size: 0.8rem;
  opacity: 0.7;
}

/* Typing Indicator */
.typing-indicator {
  display: flex;
  align-items: flex-end;
  gap: 1rem;
}

.typing-bubble {
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 18px;
  border-bottom-left-radius: 6px;
  padding: 1rem 1.25rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.typing-dots {
  display: flex;
  gap: 0.25rem;
}

.typing-dots span {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #4f46e5;
  animation: typing 1.4s infinite ease-in-out;
}

.typing-dots span:nth-child(1) {
  animation-delay: -0.32s;
}

.typing-dots span:nth-child(2) {
  animation-delay: -0.16s;
}

@keyframes typing {
  0%, 80%, 100% {
    transform: scale(0.8);
    opacity: 0.5;
  }
  40% {
    transform: scale(1);
    opacity: 1;
  }
}

.typing-text {
  font-size: 0.85rem;
  color: #64748b;
  font-style: italic;
}

/* Chat Footer */
.chat-footer {
  padding: 1.5rem 2rem;
  border-top: 1px solid #e2e8f0;
  background: white;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.conversation-info {
  display: flex;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.info-item {
  color: #64748b;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
}

.btn-close {
  background: #f1f5f9;
  color: #374151;
  border: 2px solid #e2e8f0;
  padding: 0.75rem 1.5rem;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
}

.btn-close:hover {
  background: #e2e8f0;
  border-color: #cbd5e1;
}

/* Modal Transitions */
.modal-enter-active, .modal-leave-active {
  transition: all 0.3s ease;
}

.modal-enter-from, .modal-leave-to {
  opacity: 0;
  transform: scale(0.9);
}

/* Responsive */
@media (max-width: 768px) {
  .modal-container {
    width: 95%;
    height: 90vh;
    margin: 5vh auto;
  }

  .chat-header {
    padding: 1rem;
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
  }

  .chat-body {
    padding: 1rem;
  }

  .message {
    max-width: 85%;
  }

  .message-container {
    gap: 0.5rem;
  }

  .message-avatar {
    width: 35px;
    height: 35px;
    font-size: 0.9rem;
  }

  .chat-footer {
    padding: 1rem;
    flex-direction: column;
    align-items: stretch;
  }

  .conversation-info {
    justify-content: center;
  }

  .btn-close {
    justify-content: center;
  }
}
</style>