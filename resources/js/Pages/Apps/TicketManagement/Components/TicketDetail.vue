`<template>
  <div class="bg-white shadow-sm rounded-lg">
    <!-- Ticket Header -->
    <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-lg font-medium leading-6 text-gray-900">{{ ticket.title }}</h3>
          <p class="mt-1 max-w-2xl text-sm text-gray-500">Ticket #{{ ticket.id }}</p>
        </div>
        <div class="flex items-center space-x-3">
          <div class="flex items-center">
            <span class="mr-2 text-sm text-gray-500">Status:</span>
            <select
              v-model="ticket.status"
              @change="updateTicketStatus"
              class="rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
            >
              <option value="open">Open</option>
              <option value="in-progress">In Progress</option>
              <option value="pending">Pending Review</option>
              <option value="resolved">Resolved</option>
            </select>
          </div>
          <button
            @click="toggleWatching"
            class="inline-flex items-center rounded-md border px-3 py-1 text-sm"
            :class="[
              isWatching
                ? 'border-blue-500 bg-blue-50 text-blue-700'
                : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
            ]"
          >
            <EyeIcon
              :class="[
                isWatching ? 'text-blue-500' : 'text-gray-400',
                'mr-1.5 h-4 w-4'
              ]"
            />
            {{ isWatching ? 'Watching' : 'Watch' }}
          </button>
        </div>
      </div>

      <!-- Ticket Metadata -->
      <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
          <dt class="text-sm font-medium text-gray-500">Assigned To</dt>
          <dd class="mt-1 text-sm text-gray-900">
            <div class="flex items-center">
              <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">
                {{ ticket.assignee.charAt(0) }}
              </div>
              <span class="ml-2">{{ ticket.assignee }}</span>
            </div>
          </dd>
        </div>
        <div>
          <dt class="text-sm font-medium text-gray-500">Priority</dt>
          <dd class="mt-1 text-sm text-gray-900">
            <span
              :class="[
                ticket.priority === 'urgent' ? 'bg-red-100 text-red-800' :
                ticket.priority === 'high' ? 'bg-orange-100 text-orange-800' :
                ticket.priority === 'medium' ? 'bg-yellow-100 text-yellow-800' :
                'bg-green-100 text-green-800',
                'inline-flex rounded-full px-2 py-0.5 text-xs font-medium'
              ]"
            >
              {{ ticket.priority }}
            </span>
          </dd>
        </div>
        <div>
          <dt class="text-sm font-medium text-gray-500">Created</dt>
          <dd class="mt-1 text-sm text-gray-900">
            {{ formatDate(ticket.createdAt) }}
          </dd>
        </div>
      </div>
    </div>

    <!-- Ticket Content -->
    <div class="px-4 py-5 sm:px-6">
      <div class="prose max-w-none text-gray-900">
        {{ ticket.description }}
      </div>

      <!-- Attachments -->
      <div v-if="ticket.attachments && ticket.attachments.length" class="mt-6">
        <h4 class="text-sm font-medium text-gray-900">Attachments</h4>
        <ul role="list" class="mt-2 divide-y divide-gray-200 rounded-md border border-gray-200">
          <li v-for="attachment in ticket.attachments" :key="attachment.id" class="flex items-center justify-between py-3 pl-3 pr-4 text-sm">
            <div class="flex w-0 flex-1 items-center">
              <PaperClipIcon class="h-5 w-5 flex-shrink-0 text-gray-400" />
              <span class="ml-2 w-0 flex-1 truncate">{{ attachment.name }}</span>
            </div>
            <div class="ml-4 flex-shrink-0">
              <a :href="attachment.url" class="font-medium text-blue-600 hover:text-blue-500">
                Download
              </a>
            </div>
          </li>
        </ul>
      </div>
    </div>

    <!-- Comments Section -->
    <div class="border-t border-gray-200">
      <div class="px-4 py-5 sm:px-6">
        <h4 class="text-lg font-medium text-gray-900">Activity</h4>
        
        <!-- Comment Thread -->
        <div class="mt-6 space-y-6">
          <div v-for="comment in ticket.comments" :key="comment.id" class="relative flex gap-4">
            <div class="flex h-8 w-8 flex-none items-center justify-center rounded-full bg-gray-200">
              {{ comment.author.charAt(0) }}
            </div>
            <div class="flex-grow">
              <div class="flex items-center gap-2">
                <span class="font-medium text-gray-900">{{ comment.author }}</span>
                <span class="text-sm text-gray-500">{{ formatDate(comment.createdAt) }}</span>
              </div>
              <div class="mt-1 text-gray-700">{{ comment.content }}</div>
              
              <!-- Comment Attachments -->
              <div v-if="comment.attachments && comment.attachments.length" class="mt-2">
                <ul role="list" class="mt-2 divide-y divide-gray-200 rounded-md border border-gray-200">
                  <li v-for="attachment in comment.attachments" :key="attachment.id" class="flex items-center justify-between py-2 pl-3 pr-4 text-sm">
                    <div class="flex w-0 flex-1 items-center">
                      <PaperClipIcon class="h-4 w-4 flex-shrink-0 text-gray-400" />
                      <span class="ml-2 w-0 flex-1 truncate">{{ attachment.name }}</span>
                    </div>
                    <div class="ml-4 flex-shrink-0">
                      <a :href="attachment.url" class="font-medium text-blue-600 hover:text-blue-500">
                        Download
                      </a>
                    </div>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <!-- New Comment Form -->
        <div class="mt-6">
          <div class="flex gap-4">
            <div class="flex h-8 w-8 flex-none items-center justify-center rounded-full bg-gray-200">
              {{ currentUser.charAt(0) }}
            </div>
            <div class="flex-grow">
              <form @submit.prevent="addComment">
                <textarea
                  v-model="newComment"
                  rows="3"
                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  placeholder="Add a comment..."
                ></textarea>
                
                <!-- Attachment Upload -->
                <div class="mt-2 flex items-center gap-4">
                  <div class="flex">
                    <label class="relative cursor-pointer rounded-md font-medium text-blue-600 hover:text-blue-500">
                      <span>Attach files</span>
                      <input type="file" class="sr-only" multiple @change="handleAttachments" />
                    </label>
                  </div>
                  <button
                    type="submit"
                    class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                  >
                    Comment
                  </button>
                </div>
                
                <!-- Preview Attachments -->
                <div v-if="selectedFiles.length" class="mt-2">
                  <ul role="list" class="divide-y divide-gray-200 rounded-md border border-gray-200">
                    <li v-for="(file, index) in selectedFiles" :key="index" class="flex items-center justify-between py-2 pl-3 pr-4 text-sm">
                      <div class="flex w-0 flex-1 items-center">
                        <PaperClipIcon class="h-4 w-4 flex-shrink-0 text-gray-400" />
                        <span class="ml-2 w-0 flex-1 truncate">{{ file.name }}</span>
                      </div>
                      <div class="ml-4 flex-shrink-0">
                        <button
                          type="button"
                          @click="removeFile(index)"
                          class="font-medium text-red-600 hover:text-red-500"
                        >
                          Remove
                        </button>
                      </div>
                    </li>
                  </ul>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { EyeIcon, PaperClipIcon } from '@heroicons/vue/20/solid'

// Sample data - replace with actual API data
const ticket = ref({
  id: '1234',
  title: 'Unable to access dashboard',
  status: 'open',
  priority: 'high',
  assignee: 'Support Team A',
  description: 'When trying to access the dashboard, I get a 404 error. This started happening after the latest update.',
  createdAt: '2025-07-21T10:30:00',
  attachments: [
    { id: 1, name: 'error-screenshot.png', url: '#' },
    { id: 2, name: 'console-log.txt', url: '#' }
  ],
  comments: [
    {
      id: 1,
      author: 'John Doe',
      content: 'I\'m looking into this issue. Could you please provide your browser version?',
      createdAt: '2025-07-21T11:00:00',
      attachments: []
    },
    {
      id: 2,
      author: 'Jane Smith',
      content: 'Using Chrome 115.0.5790.110',
      createdAt: '2025-07-21T11:15:00',
      attachments: [
        { id: 3, name: 'browser-info.txt', url: '#' }
      ]
    }
  ]
})

const currentUser = ref('Current User')
const isWatching = ref(false)
const newComment = ref('')
const selectedFiles = ref([])

const formatDate = (date) => {
  return new Date(date).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const toggleWatching = () => {
  isWatching.value = !isWatching.value
  // TODO: Implement API call to update watching status
}

const updateTicketStatus = () => {
  // TODO: Implement API call to update ticket status
  console.log('Updating ticket status to:', ticket.value.status)
}

const handleAttachments = (event) => {
  const files = Array.from(event.target.files)
  selectedFiles.value = [...selectedFiles.value, ...files]
}

const removeFile = (index) => {
  selectedFiles.value.splice(index, 1)
}

const addComment = async () => {
  if (!newComment.value.trim() && selectedFiles.value.length === 0) return

  try {
    // TODO: Implement API call to add comment with attachments
    const comment = {
      id: Date.now(),
      author: currentUser.value,
      content: newComment.value,
      createdAt: new Date().toISOString(),
      attachments: selectedFiles.value.map((file, index) => ({
        id: Date.now() + index,
        name: file.name,
        url: '#'
      }))
    }

    ticket.value.comments.push(comment)
    newComment.value = ''
    selectedFiles.value = []
  } catch (error) {
    console.error('Error adding comment:', error)
  }
}

onMounted(() => {
  // TODO: Fetch ticket data and current user info
})
</script>`
