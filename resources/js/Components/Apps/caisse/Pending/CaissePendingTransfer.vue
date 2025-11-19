<script setup>
import { ref, onMounted } from 'vue'
import { caisseTransferService } from '../../../../Components/Apps/services/caisse/CaisseTransferService'

const pendingRequests = ref([])
const loading = ref(false)
const processingIds = ref([])
const hasPendingRequests = ref(false)
const pendingCount = ref(0)
const message = ref('')
const successMessage = ref('')
const errorMessage = ref('')

// New state for modal
const showAcceptModal = ref(false)
const selectedRequest = ref(null)
const receivedAmount = ref(null)

const clearMessages = () => {
    successMessage.value = ''
    errorMessage.value = ''
}

const loadPendingRequests = async () => {
    loading.value = true
    clearMessages()
    
    try {
        const response = await caisseTransferService.caisseTransfersPendingRequest()
        
        if (response.success) {
            pendingRequests.value = response.data || []
            hasPendingRequests.value = response.has_pending_requests || false
            pendingCount.value = response.pending_count || 0
            message.value = response.message || ''
        } else {
            errorMessage.value = response.message || 'Failed to load pending requests'
        }
    } catch (error) {
        console.error('Error loading pending requests:', error)
        errorMessage.value = 'An error occurred while loading pending requests'
    } finally {
        loading.value = false
    }
}

const refreshRequests = async () => {
    await loadPendingRequests()
}

// Function to open the modal
const openAcceptModal = (request) => {
    clearMessages();
    selectedRequest.value = request;
    receivedAmount.value = request.amount; // Pre-fill with the original amount
    showAcceptModal.value = true;
};

// Function to close the modal
const closeAcceptModal = () => {
    showAcceptModal.value = false;
    selectedRequest.value = null;
    receivedAmount.value = null;
};

// Modified accept function to handle the modal's logic
const handleAccept = async () => {
    
    
    // Validate the received amount
    if (receivedAmount.value === null || receivedAmount.value === '' || isNaN(receivedAmount.value)) {
        errorMessage.value = 'Please enter a valid amount.';
        return;
    }

    processingIds.value.push(selectedRequest.value.id)
    clearMessages()

    try {
        const response = await caisseTransferService.accept(selectedRequest.value.id, selectedRequest.value.transfer_token, receivedAmount.value)

        if (response.success) {
            successMessage.value = 'Transfer request accepted successfully'
            pendingRequests.value = pendingRequests.value.filter(r => r.id !== selectedRequest.value.id)
            pendingCount.value = Math.max(0, pendingCount.value - 1)
            hasPendingRequests.value = pendingCount.value > 0
            closeAcceptModal();
        } else {
            errorMessage.value = response.message || 'Failed to accept transfer request'
        }
    } catch (error) {
        console.error('Error accepting request:', error)
        errorMessage.value = 'An error occurred while accepting the request'
    } finally {
        processingIds.value = processingIds.value.filter(id => id !== selectedRequest.value.id)
    }
};

const rejectRequest = async (request) => {
    processingIds.value.push(request.id)
    clearMessages()

    try {
        const response = await caisseTransferService.reject(request.id)

        if (response.success) {
            successMessage.value = 'Transfer request rejected successfully'
            pendingRequests.value = pendingRequests.value.filter(r => r.id !== request.id)
            pendingCount.value = Math.max(0, pendingCount.value - 1)
            hasPendingRequests.value = pendingCount.value > 0
        } else {
            errorMessage.value = response.message || 'Failed to reject transfer request'
        }
    } catch (error) {
        console.error('Error rejecting request:', error)
        errorMessage.value = 'An error occurred while rejecting the request'
    } finally {
        processingIds.value = processingIds.value.filter(id => id !== request.id)
    }
}

const copyToken = (token) => {
    navigator.clipboard.writeText(token).then(() => {
        successMessage.value = 'Token copied to clipboard'
        setTimeout(() => clearMessages(), 3000)
    }).catch(() => {
        errorMessage.value = 'Failed to copy token'
    })
}

const formatDate = (dateString) => {
    if (!dateString) return 'N/A'
    return new Date(dateString).toLocaleString()
}

const formatCurrency = (amount) => {
    if (!amount) return '0.00'
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'DZD'
    }).format(amount)
}

onMounted(() => {
    loadPendingRequests()
})
</script>

<template>
    <div class="tw-pending-requests-container tw-bg-gray-50 tw-min-h-screen tw-p-6">
        <div class="tw-flex tw-items-center tw-justify-between tw-mb-8">
            <div>
                <h2 class="tw-text-3xl tw-font-extrabold tw-text-gray-900">Pending Transfer Requests</h2>
                <p class="tw-mt-1 tw-text-base tw-text-gray-500">Requests waiting for your approval</p>
            </div>
            <div class="tw-flex tw-items-center tw-space-x-4">
                <span v-if="pendingCount > 0" class="tw-inline-flex tw-items-center tw-px-4 tw-py-1.5 tw-rounded-full tw-text-sm tw-font-medium tw-bg-yellow-100 tw-text-yellow-800">
                    {{ pendingCount }} Pending
                </span>
                <button 
                    @click="refreshRequests"
                    :disabled="loading"
                    class="tw-inline-flex tw-items-center tw-px-5 tw-py-2 tw-border tw-border-gray-300 tw-rounded-md tw-shadow-sm tw-text-sm tw-font-medium tw-text-gray-700 tw-bg-white hover:tw-bg-gray-100 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-blue-500 disabled:tw-opacity-50 disabled:tw-cursor-not-allowed tw-transition-colors"
                >
                    <svg v-if="!loading" class="tw-h-4 tw-w-4 tw-mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <svg v-else class="tw-animate-spin tw-h-4 tw-w-4 tw-mr-2 tw-text-gray-500" fill="none" viewBox="0 0 24 24">
                        <circle class="tw-opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="tw-opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Refresh
                </button>
            </div>
        </div>

        <div v-if="loading && !pendingRequests.length" class="tw-flex tw-flex-col tw-items-center tw-justify-center tw-py-20">
            <div class="tw-animate-spin tw-rounded-full tw-h-12 tw-w-12 tw-border-b-4 tw-border-blue-600"></div>
            <span class="tw-mt-4 tw-text-lg tw-text-gray-600 tw-font-medium">Loading pending requests...</span>
        </div>

        <div v-else-if="!loading && !hasPendingRequests" class="tw-text-center tw-py-20 tw-bg-white tw-rounded-lg tw-shadow-sm tw-border tw-border-gray-200">
            <svg class="tw-mx-auto tw-h-16 tw-w-16 tw-text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="tw-mt-4 tw-text-lg tw-font-medium tw-text-gray-900">No pending requests</h3>
            <p class="tw-mt-2 tw-text-sm tw-text-gray-500">{{ message }}</p>
        </div>

        <div v-else class="tw-space-y-6">
            <div 
                v-for="request in pendingRequests" 
                :key="request.id"
                class="tw-bg-white tw-rounded-lg tw-border tw-border-gray-200 tw-shadow-md hover:tw-shadow-lg tw-transition-all tw-duration-300"
            >
                <div class="tw-p-6 md:tw-p-8">
                    <div class="tw-flex tw-flex-col md:tw-flex-row md:tw-items-center tw-justify-between">
                        <div class="tw-flex-1">
                            <div class="tw-flex tw-items-center tw-space-x-4 tw-mb-4">
                                <div class="tw-flex-shrink-0">
                                    <div class="tw-h-12 tw-w-12 tw-rounded-full tw-bg-yellow-100 tw-flex tw-items-center tw-justify-center">
                                        <svg class="tw-h-6 tw-w-6 tw-text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                        </svg>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="tw-text-xl tw-font-semibold tw-text-gray-900">
                                        Transfer Request from {{ request.from_user?.name || 'Unknown User' }}
                                    </h3>
                                    <p class="tw-mt-0.5 tw-text-sm tw-text-gray-500">
                                        {{ formatDate(request.created_at) }}
                                    </p>
                                </div>
                            </div>
                            <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-4 tw-gap-4 tw-mb-6">
                                <div>
                                    <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-500">From User</label>
                                    <p class="tw-mt-1 tw-text-sm tw-font-medium tw-text-gray-900">{{ request.from_user?.name || 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-500">Caisse</label>
                                    <p class="tw-mt-1 tw-text-sm tw-font-medium tw-text-gray-900">{{ request.caisse?.name || 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-500">amount sended</label>
                                    <p class="tw-mt-1 tw-text-base tw-font-bold tw-text-green-600">
                                        {{ formatCurrency(request.amount_sended) }}
                                    </p>
                                </div>
                                <div>
                                    <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-500">Status</label>
                                    <span class="tw-mt-1 tw-inline-flex tw-items-center tw-px-3 tw-py-0.5 tw-rounded-full tw-text-xs tw-font-medium tw-bg-yellow-100 tw-text-yellow-800">
                                        {{ request.status }}
                                    </span>
                                </div>
                            </div>

                            <div v-if="request.transfer_token" class="tw-mb-4">
                                <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-500">Transfer Token</label>
                                <div class="tw-mt-1 tw-flex tw-items-center tw-space-x-2">
                                    <code class="tw-px-3 tw-py-1.5 tw-bg-gray-100 tw-rounded tw-text-sm tw-font-mono tw-text-gray-700 tw-break-all">{{ request.transfer_token }}</code>
                                    <button 
                                        @click="copyToken(request.transfer_token)"
                                        class="tw-text-blue-600 hover:tw-text-blue-500 tw-text-sm tw-font-medium"
                                    >
                                        Copy
                                    </button>
                                </div>
                            </div>

                            <div v-if="request.description">
                                <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-500">Description</label>
                                <p class="tw-mt-1 tw-text-sm tw-text-gray-600 tw-italic">{{ request.description }}</p>
                            </div>
                        </div>

                        <div class="tw-flex-shrink-0 tw-mt-6 md:tw-mt-0 md:tw-ml-8">
                            <div class="tw-flex tw-flex-col md:tw-flex-row tw-space-y-3 md:tw-space-y-0 md:tw-space-x-3">
                                <button
                                    @click="openAcceptModal(request)"
                                    :disabled="processingIds.includes(request.id)"
                                    class="tw-inline-flex tw-items-center tw-justify-center tw-px-5 tw-py-2.5 tw-border tw-border-transparent tw-text-sm tw-font-medium tw-rounded-md tw-text-white tw-bg-green-600 hover:tw-bg-green-700 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-green-500 disabled:tw-opacity-50 disabled:tw-cursor-not-allowed tw-transition-colors"
                                >
                                    <svg class="tw-h-5 tw-w-5 tw-mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Accept
                                </button>
                                <button
                                    @click="rejectRequest(request)"
                                    :disabled="processingIds.includes(request.id)"
                                    class="tw-inline-flex tw-items-center tw-justify-center tw-px-5 tw-py-2.5 tw-border tw-border-gray-300 tw-text-sm tw-font-medium tw-rounded-md tw-text-gray-700 tw-bg-white hover:tw-bg-gray-100 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-blue-500 disabled:tw-opacity-50 disabled:tw-cursor-not-allowed tw-transition-colors"
                                >
                                    <svg v-if="!processingIds.includes(request.id)" class="tw-h-5 tw-w-5 tw-mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    <svg v-else class="tw-animate-spin tw-h-5 tw-w-5 tw-mr-2 tw-text-gray-500" fill="none" viewBox="0 0 24 24">
                                        <circle class="tw-opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="tw-opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Reject
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="successMessage" class="tw-mt-6 tw-p-4 tw-bg-green-50 tw-border tw-border-green-200 tw-rounded-lg tw-flex tw-items-center">
            <svg class="tw-h-5 tw-w-5 tw-text-green-400 tw-flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <p class="tw-ml-3 tw-text-sm tw-font-medium tw-text-green-700">{{ successMessage }}</p>
        </div>

        <div v-if="errorMessage" class="tw-mt-6 tw-p-4 tw-bg-red-50 tw-border tw-border-red-200 tw-rounded-lg tw-flex tw-items-center">
            <svg class="tw-h-5 tw-w-5 tw-text-red-400 tw-flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            <p class="tw-ml-3 tw-text-sm tw-font-medium tw-text-red-700">{{ errorMessage }}</p>
        </div>

        <div v-if="showAcceptModal" class="tw-fixed tw-inset-0 tw-z-50 tw-overflow-y-auto tw-bg-gray-900 tw-bg-opacity-50 tw-flex tw-items-center tw-justify-center">
            <div class="tw-bg-white tw-rounded-lg tw-shadow-xl tw-max-w-md tw-w-full tw-p-6">
                <div class="tw-flex tw-justify-between tw-items-start tw-mb-4">
                    <h3 class="tw-text-xl tw-font-bold tw-text-gray-900">Accept Transfer Request</h3>
                    <button @click="closeAcceptModal" class="tw-text-gray-400 hover:tw-text-gray-600 tw-transition-colors">
                        <svg class="tw-h-6 tw-w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <div v-if="selectedRequest" class="tw-mb-6">
                    <p class="tw-text-gray-600 tw-mb-4">You are about to accept a transfer of <span class="tw-font-bold tw-text-green-600">{{ formatCurrency(selectedRequest.amount) }}</span> from <span class="tw-font-bold">{{ selectedRequest.from_user?.name }}</span>.</p>
                    
                    <div>
                        <label for="received-amount" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">Received Amount</label>
                        <div class="tw-mt-1">
                            <input 
                                type="number" 
                                id="received-amount" 
                                v-model.number="receivedAmount" 
                                :placeholder="selectedRequest.amount"
                                class="tw-block tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-blue-500 focus:tw-ring-blue-500 sm:tw-text-sm tw-p-2 tw-border"
                            >
                        </div>
                    </div>
                </div>

                <div v-if="errorMessage" class="tw-mb-4 p-2 tw-bg-red-50 tw-border tw-border-red-200 tw-rounded-md">
                    <p class="tw-text-sm tw-text-red-700">{{ errorMessage }}</p>
                </div>
                
                <div class="tw-flex tw-justify-end tw-space-x-3">
                    <button @click="closeAcceptModal" class="tw-px-4 tw-py-2 tw-border tw-border-gray-300 tw-rounded-md tw-text-sm tw-font-medium tw-text-gray-700 hover:tw-bg-gray-50 tw-transition-colors">
                        Cancel
                    </button>
                    <button 
                        @click="handleAccept"
                        :disabled="processingIds.includes(selectedRequest?.id)"
                        class="tw-inline-flex tw-items-center tw-justify-center tw-px-4 tw-py-2 tw-border tw-border-transparent tw-text-sm tw-font-medium tw-rounded-md tw-text-white tw-bg-green-600 hover:tw-bg-green-700 disabled:tw-opacity-50 disabled:tw-cursor-not-allowed tw-transition-colors"
                    >
                        <svg v-if="processingIds.includes(selectedRequest?.id)" class="tw-animate-spin tw-h-5 tw-w-5 tw-mr-2 tw-text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="tw-opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="tw-opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Confirm Accept
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.tw-pending-requests-container {
    max-width: 1024px;
    margin: 0 auto;
    padding: 1.5rem;
}

@media (min-width: 768px) {
    .tw-pending-requests-container {
        padding: 2.5rem;
    }
}
</style>
