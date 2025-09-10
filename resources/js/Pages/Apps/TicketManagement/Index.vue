`<template>
  <div class="ticket-management">
    <!-- Dashboard Header -->
    <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
      <h1 class="text-2xl font-bold mb-4">Ticket Management</h1>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Statistics Cards -->
        <div v-for="stat in statistics" :key="stat.label" 
             class="bg-gray-50 p-4 rounded-lg">
          <div class="text-sm text-gray-600">{{ stat.label }}</div>
          <div class="text-2xl font-bold">{{ stat.value }}</div>
        </div>
      </div>
    </div>

    <!-- Main Content Area -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
      <!-- Left Sidebar -->
      <div class="lg:col-span-1">
        <div class="bg-white shadow-sm rounded-lg p-4">
          <button @click="openNewTicketModal" 
                  class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg mb-4 hover:bg-blue-700 
                         transition duration-150 ease-in-out">
            Create New Ticket
          </button>
          
          <nav class="space-y-2">
            <router-link v-for="item in navigation" 
                         :key="item.name"
                         :to="item.href"
                         :class="[
                           item.current ? 'bg-gray-100 text-blue-600' : 'text-gray-600 hover:bg-gray-50',
                           'group flex items-center px-3 py-2 text-sm font-medium rounded-md'
                         ]">
              <component :is="item.icon" 
                        :class="[
                          item.current ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-500',
                          'flex-shrink-0 -ml-1 mr-3 h-6 w-6'
                        ]" />
              <span class="truncate">{{ item.name }}</span>
              <span v-if="item.count" 
                    :class="[
                      item.current ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-900',
                      'ml-auto inline-block py-0.5 px-3 text-xs rounded-full'
                    ]">
                {{ item.count }}
              </span>
            </router-link>
          </nav>
        </div>
      </div>

      <!-- Main Content -->
      <div class="lg:col-span-3">
        <router-view></router-view>
      </div>
    </div>

    <!-- New Ticket Modal -->
    <TransitionRoot as="template" :show="showNewTicketModal">
      <Dialog as="div" class="relative z-10" @close="showNewTicketModal = false">
        <TransitionChild
          enter="ease-out duration-300"
          enter-from="opacity-0"
          enter-to="opacity-100"
          leave="ease-in duration-200"
          leave-from="opacity-100"
          leave-to="opacity-0"
        >
          <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
        </TransitionChild>

        <div class="fixed inset-0 z-10 overflow-y-auto">
          <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <TransitionChild
              enter="ease-out duration-300"
              enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
              enter-to="opacity-100 translate-y-0 sm:scale-100"
              leave="ease-in duration-200"
              leave-from="opacity-100 translate-y-0 sm:scale-100"
              leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
              <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:p-6">
                <div>
                  <div class="mt-3 text-center sm:mt-5">
                    <DialogTitle as="h3" class="text-xl font-semibold leading-6 text-gray-900">
                      Create New Ticket
                    </DialogTitle>
                    <div class="mt-4">
                      <form @submit.prevent="createTicket" class="space-y-4">
                        <div class="text-left">
                          <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
                          <input type="text" 
                                 id="title" 
                                 v-model="newTicket.title"
                                 class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                        </div>

                        <div class="text-left">
                          <label for="assignee" class="block text-sm font-medium text-gray-700">Assign To</label>
                          <select id="assignee" 
                                  v-model="newTicket.assignee"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Select Assignee</option>
                            <option v-for="user in users" 
                                    :key="user.id" 
                                    :value="user.id">
                              {{ user.name }}
                            </option>
                          </select>
                        </div>

                        <div class="text-left">
                          <label for="priority" class="block text-sm font-medium text-gray-700">Priority</label>
                          <select id="priority" 
                                  v-model="newTicket.priority"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                          </select>
                        </div>

                        <div class="text-left">
                          <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                          <textarea id="description" 
                                    v-model="newTicket.description"
                                    rows="4"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        </div>

                        <div class="text-left">
                          <label class="block text-sm font-medium text-gray-700">Attachments</label>
                          <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                            <div class="space-y-1 text-center">
                              <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                              </svg>
                              <div class="flex text-sm text-gray-600">
                                <label for="file-upload" class="relative cursor-pointer rounded-md font-medium text-blue-600 hover:text-blue-500">
                                  <span>Upload a file</span>
                                  <input id="file-upload" type="file" class="sr-only" @change="handleFileUpload" multiple />
                                </label>
                                <p class="pl-1">or drag and drop</p>
                              </div>
                              <p class="text-xs text-gray-500">PNG, JPG, PDF up to 10MB</p>
                            </div>
                          </div>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
                <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                  <button
                    type="submit"
                    class="inline-flex w-full justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 sm:col-start-2"
                    @click="createTicket"
                  >
                    Create
                  </button>
                  <button
                    type="button"
                    class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:col-start-1 sm:mt-0"
                    @click="showNewTicketModal = false"
                  >
                    Cancel
                  </button>
                </div>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { useRouter } from 'vue-router'

// Sample data - replace with actual API calls
const statistics = ref([
  { label: 'Open Tickets', value: 12 },
  { label: 'In Progress', value: 4 },
  { label: 'Pending Review', value: 2 },
  { label: 'Closed Today', value: 3 },
])

const navigation = ref([
  { name: 'All Tickets', href: '/tickets', icon: 'TicketIcon', current: true, count: 21 },
  { name: 'Open', href: '/tickets/open', icon: 'InboxIcon', current: false, count: 12 },
  { name: 'In Progress', href: '/tickets/in-progress', icon: 'ClockIcon', current: false, count: 4 },
  { name: 'Pending Review', href: '/tickets/pending', icon: 'EyeIcon', current: false, count: 2 },
  { name: 'Resolved', href: '/tickets/resolved', icon: 'CheckCircleIcon', current: false },
  { name: 'Archived', href: '/tickets/archived', icon: 'ArchiveBoxIcon', current: false },
])

const users = ref([
  { id: 1, name: 'Support Team A' },
  { id: 2, name: 'Technical Team' },
  { id: 3, name: 'Customer Service' },
])

const showNewTicketModal = ref(false)
const newTicket = ref({
  title: '',
  assignee: '',
  priority: 'medium',
  description: '',
  attachments: []
})

const openNewTicketModal = () => {
  showNewTicketModal.value = true
}

const handleFileUpload = (event) => {
  const files = Array.from(event.target.files)
  newTicket.value.attachments = [...newTicket.value.attachments, ...files]
}

const createTicket = async () => {
  try {
    // TODO: Implement API call to create ticket
    console.log('Creating ticket:', newTicket.value)
    showNewTicketModal.value = false
    // Reset form
    newTicket.value = {
      title: '',
      assignee: '',
      priority: 'medium',
      description: '',
      attachments: []
    }
  } catch (error) {
    console.error('Error creating ticket:', error)
  }
}

onMounted(() => {
  // TODO: Fetch initial data
})
</script>`
