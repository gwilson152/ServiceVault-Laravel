<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Ticket Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Navigation Bar -->
        <div class="flex items-center justify-between py-4">
          <div class="flex items-center space-x-4">
            <!-- Back Button -->
            <Link 
              :href="route('tickets.index')" 
              class="text-gray-600 hover:text-gray-900 transition-colors"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
              </svg>
            </Link>
            
            <!-- Ticket Info -->
            <div>
              <div class="flex items-center space-x-3">
                <h1 class="text-2xl font-bold text-gray-900">
                  {{ ticket?.title || 'Loading...' }}
                </h1>
                <button
                  v-if="canEdit && ticket?.title"
                  @click="startEditingTitle"
                  class="text-blue-600 hover:text-blue-700 text-sm transition-colors"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
              </div>
              <div class="flex items-center space-x-3 mt-1">
                <span class="text-sm font-medium text-gray-500">{{ ticket?.ticket_number }}</span>
                <span v-if="ticket" :class="statusClasses" class="px-2 py-1 rounded-full text-xs font-medium">
                  {{ formatStatus(ticket.status) }}
                </span>
                <span v-if="ticket" :class="priorityClasses" class="px-2 py-1 rounded-full text-xs font-medium">
                  {{ formatPriority(ticket.priority) }}
                </span>
                <span v-if="ticket?.due_date" :class="dueDateClasses" class="px-2 py-1 rounded-full text-xs font-medium">
                  Due {{ formatDate(ticket.due_date) }}
                </span>
              </div>
            </div>
          </div>
          
          <!-- Action Buttons -->
          <div class="flex items-center space-x-3">
            <!-- Timer Controls -->
            <div v-if="canManageTime" class="flex items-center space-x-2">
              <button
                v-if="!activeTimer"
                @click="startTimer"
                class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition-colors flex items-center space-x-2"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Start Timer</span>
              </button>
              
              <div v-else class="flex items-center space-x-2">
                <div class="bg-green-50 border border-green-200 rounded-lg px-3 py-2 flex items-center space-x-2">
                  <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                  <span class="text-green-700 font-medium">{{ formatDuration(activeTimer.duration) }}</span>
                </div>
                <button
                  @click="stopTimer"
                  class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg transition-colors"
                >
                  Stop
                </button>
              </div>
            </div>
            
            <!-- Status Change -->
            <div v-if="canChangeStatus" class="relative">
              <select
                :value="ticket?.status"
                @change="changeStatus($event.target.value)"
                class="bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="open">Open</option>
                <option value="in_progress">In Progress</option>
                <option value="waiting_customer">Waiting Customer</option>
                <option value="on_hold">On Hold</option>
                <option value="resolved">Resolved</option>
                <option value="closed">Closed</option>
              </select>
            </div>
            
            <!-- More Actions -->
            <div class="relative" ref="actionsDropdown">
              <button
                @click="showActionsMenu = !showActionsMenu"
                class="bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                </svg>
              </button>
              
              <div v-if="showActionsMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg border border-gray-200 z-10">
                <div class="py-1">
                  <button 
                    @click="duplicateTicket"
                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                  >
                    Duplicate Ticket
                  </button>
                  <button 
                    @click="exportTicket"
                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                  >
                    Export to PDF
                  </button>
                  <hr class="my-1">
                  <button 
                    @click="deleteTicket"
                    class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50"
                  >
                    Delete Ticket
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <div v-if="!ticket" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <p class="mt-2 text-gray-600">Loading ticket details...</p>
      </div>
      
      <div v-else class="grid grid-cols-1 xl:grid-cols-4 gap-6">
        <!-- Main Content Area (3 columns) -->
        <div class="xl:col-span-3 space-y-6">
          <!-- Ticket Description -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6">
              <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Description</h2>
                <button
                  v-if="canEdit && !editingDescription"
                  @click="startEditingDescription"
                  class="text-blue-600 hover:text-blue-700 text-sm transition-colors"
                >
                  Edit
                </button>
              </div>
              
              <div v-if="editingDescription">
                <textarea
                  v-model="editedDescription"
                  rows="6"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  placeholder="Describe the issue or request..."
                ></textarea>
                <div class="flex items-center space-x-2 mt-3">
                  <button
                    @click="saveDescription"
                    :disabled="savingDescription"
                    class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-medium py-2 px-4 rounded-lg transition-colors"
                  >
                    {{ savingDescription ? 'Saving...' : 'Save' }}
                  </button>
                  <button
                    @click="cancelEditingDescription"
                    class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium py-2 px-4 rounded-lg transition-colors"
                  >
                    Cancel
                  </button>
                </div>
              </div>
              
              <div v-else class="prose max-w-none text-gray-700">
                <div v-if="ticket.description" v-html="formatDescription(ticket.description)"></div>
                <div v-else class="text-gray-500 italic">No description provided.</div>
              </div>
            </div>
          </div>

          <!-- Tabbed Content Area -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <!-- Tab Navigation -->
            <div class="border-b border-gray-200">
              <nav class="flex space-x-8 px-6" aria-label="Tabs">
                <button
                  v-for="tab in tabs"
                  :key="tab.id"
                  @click="activeTab = tab.id"
                  :class="[
                    activeTab === tab.id
                      ? 'border-blue-500 text-blue-600'
                      : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                    'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors'
                  ]"
                >
                  {{ tab.label }}
                  <span v-if="tab.badge" class="ml-2 bg-gray-100 text-gray-900 py-0.5 px-2 rounded-full text-xs font-medium">
                    {{ tab.badge }}
                  </span>
                </button>
              </nav>
            </div>
            
            <!-- Tab Content -->
            <div class="p-6">
              <!-- Comments & Messages Tab -->
              <div v-if="activeTab === 'messages'" class="space-y-6">
                <!-- Message Thread -->
                <div class="space-y-4">
                  <div v-if="messages.length === 0" class="text-center py-8 text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-3.582 8-8 8a8.959 8.959 0 01-4.906-1.524A11.956 11.956 0 012 21c0-1.68.322-3.283.913-4.755A12.026 12.026 0 012 12C2 7.582 5.582 4 10 4s8 3.582 8 8z" />
                    </svg>
                    <p>No messages yet. Start the conversation!</p>
                  </div>
                  
                  <div 
                    v-for="message in messages" 
                    :key="message.id"
                    :class="[
                      'flex space-x-3 p-4 rounded-lg',
                      message.is_internal ? 'bg-yellow-50 border border-yellow-200' : 'bg-gray-50'
                    ]"
                  >
                    <!-- Avatar -->
                    <div class="flex-shrink-0">
                      <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center">
                        <span class="text-sm font-medium text-gray-700">
                          {{ message.user?.name?.charAt(0)?.toUpperCase() || '?' }}
                        </span>
                      </div>
                    </div>
                    
                    <!-- Message Content -->
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center space-x-2 mb-2">
                        <p class="text-sm font-medium text-gray-900">{{ message.user?.name || 'Unknown User' }}</p>
                        <span v-if="message.is_internal" class="px-2 py-1 bg-yellow-200 text-yellow-800 text-xs rounded-full font-medium">
                          Internal
                        </span>
                        <span class="text-xs text-gray-500">{{ formatDateTime(message.created_at) }}</span>
                        <span v-if="message.was_edited" class="text-xs text-gray-400">(edited)</span>
                      </div>
                      <div class="text-sm text-gray-700" v-html="formatMessage(message.content)"></div>
                      
                      <!-- Message Attachments -->
                      <div v-if="message.attachments?.length" class="mt-3 space-y-2">
                        <div 
                          v-for="attachment in message.attachments" 
                          :key="attachment.id"
                          class="flex items-center space-x-2 text-sm text-blue-600 hover:text-blue-700"
                        >
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                          </svg>
                          <a :href="attachment.url" target="_blank">{{ attachment.filename }}</a>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- Message Composer -->
                <div class="border-t border-gray-200 pt-6">
                  <form @submit.prevent="sendMessage">
                    <div class="mb-4">
                      <textarea
                        v-model="newMessage"
                        placeholder="Type your message..."
                        rows="4"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        required
                      ></textarea>
                    </div>
                    
                    <!-- Message Options -->
                    <div class="flex items-center justify-between">
                      <div class="flex items-center space-x-4">
                        <label class="flex items-center">
                          <input 
                            v-model="messageIsInternal" 
                            type="checkbox" 
                            class="mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                          >
                          <span class="text-sm text-gray-600">Internal (not visible to customer)</span>
                        </label>
                        
                        <label class="flex items-center cursor-pointer">
                          <input type="file" multiple class="hidden" @change="handleFileUpload">
                          <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                          </svg>
                          <span class="text-sm text-gray-600">Attach files</span>
                        </label>
                      </div>
                      
                      <button
                        type="submit"
                        :disabled="!newMessage.trim() || sendingMessage"
                        class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-medium py-2 px-6 rounded-lg transition-colors flex items-center space-x-2"
                      >
                        <span>{{ sendingMessage ? 'Sending...' : 'Send' }}</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                      </button>
                    </div>
                  </form>
                </div>
              </div>
              
              <!-- Time Tracking Tab -->
              <div v-if="activeTab === 'time'" class="space-y-6">
                <TimeTrackingManager :ticket="ticket" @updated="loadTimeTrackingData" />
              </div>
              
              <!-- Addons Tab -->
              <div v-if="activeTab === 'addons'" class="space-y-6">
                <TicketAddonManager :ticket="ticket" @updated="loadAddons" />
              </div>
              
              <!-- Activity Timeline Tab -->
              <div v-if="activeTab === 'activity'" class="space-y-6">
                <ActivityTimeline :ticket="ticket" />
              </div>
              
              <!-- Billing Tab -->
              <div v-if="activeTab === 'billing'" class="space-y-6">
                <BillingOverview :ticket="ticket" />
              </div>
            </div>
          </div>
        </div>
        
        <!-- Sidebar (1 column) -->
        <div class="xl:col-span-1 space-y-6">
          <!-- Ticket Details Card -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-semibold text-gray-900">Details</h3>
            </div>
            <div class="p-6 space-y-4">
              <!-- Status -->
              <div>
                <label class="text-sm font-medium text-gray-600">Status</label>
                <div class="mt-1 flex items-center space-x-2">
                  <span :class="statusClasses" class="px-2 py-1 rounded-full text-xs font-medium">
                    {{ formatStatus(ticket.status) }}
                  </span>
                  <button
                    v-if="canChangeStatus"
                    @click="showStatusModal = true"
                    class="text-blue-600 hover:text-blue-700 text-xs"
                  >
                    Change
                  </button>
                </div>
              </div>
              
              <!-- Priority -->
              <div>
                <label class="text-sm font-medium text-gray-600">Priority</label>
                <div class="mt-1">
                  <span :class="priorityClasses" class="px-2 py-1 rounded-full text-xs font-medium">
                    {{ formatPriority(ticket.priority) }}
                  </span>
                </div>
              </div>
              
              <!-- Account -->
              <div>
                <label class="text-sm font-medium text-gray-600">Account</label>
                <div class="mt-1">
                  <Link 
                    v-if="ticket.account?.id"
                    :href="route('accounts.show', ticket.account.id)" 
                    class="text-sm text-blue-600 hover:text-blue-700"
                  >
                    {{ ticket.account.name }}
                  </Link>
                  <span v-else class="text-sm text-gray-500">
                    No Account Assigned
                  </span>
                </div>
              </div>
              
              <!-- Assigned Agent -->
              <div>
                <label class="text-sm font-medium text-gray-600">Assigned Agent</label>
                <div class="mt-1 flex items-center space-x-2">
                  <span class="text-sm text-gray-900">{{ ticket.agent?.name || 'Unassigned' }}</span>
                  <button
                    v-if="canAssign"
                    @click="showAssignModal = true"
                    class="text-blue-600 hover:text-blue-700 text-xs"
                  >
                    {{ ticket.agent ? 'Reassign' : 'Assign' }}
                  </button>
                </div>
              </div>
              
              <!-- Customer -->
              <div>
                <label class="text-sm font-medium text-gray-600">Customer</label>
                <div class="mt-1">
                  <p class="text-sm text-gray-900">
                    {{ ticket.customer?.name || ticket.customer_name || 'No customer assigned' }}
                  </p>
                  <p v-if="ticket.customer_email" class="text-xs text-gray-500">{{ ticket.customer_email }}</p>
                </div>
              </div>
              
              <!-- Due Date -->
              <div v-if="ticket.due_date">
                <label class="text-sm font-medium text-gray-600">Due Date</label>
                <div class="mt-1">
                  <span :class="dueDateClasses" class="text-sm">
                    {{ formatDate(ticket.due_date) }}
                  </span>
                </div>
              </div>
              
              <!-- Estimated Hours -->
              <div v-if="ticket.estimated_hours">
                <label class="text-sm font-medium text-gray-600">Estimated Hours</label>
                <p class="mt-1 text-sm text-gray-900">{{ ticket.estimated_hours }}h</p>
              </div>
              
              <!-- Created -->
              <div>
                <label class="text-sm font-medium text-gray-600">Created</label>
                <p class="mt-1 text-sm text-gray-900">{{ formatDateTime(ticket.created_at) }}</p>
                <p class="text-xs text-gray-500">by {{ ticket.createdBy?.name || 'Unknown' }}</p>
              </div>
            </div>
          </div>
          
          <!-- Time Summary Card -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-semibold text-gray-900">Time Summary</h3>
            </div>
            <div class="p-6 space-y-4">
              <div>
                <label class="text-sm font-medium text-gray-600">Total Logged</label>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ formatDuration(totalTimeLogged) }}</p>
              </div>
              
              <div v-if="activeTimers.length > 0">
                <label class="text-sm font-medium text-gray-600">Active Timers</label>
                <div class="mt-2 space-y-2">
                  <div 
                    v-for="timer in activeTimers" 
                    :key="timer.id"
                    class="flex items-center justify-between p-2 bg-green-50 border border-green-200 rounded-lg"
                  >
                    <div class="flex items-center space-x-2">
                      <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                      <span class="text-sm text-gray-700">{{ timer.user?.name || 'Unknown' }}</span>
                    </div>
                    <span class="text-sm font-medium text-green-700">{{ formatDuration(timer.duration) }}</span>
                  </div>
                </div>
              </div>
              
              <div v-if="estimatedVsActual">
                <label class="text-sm font-medium text-gray-600">Progress</label>
                <div class="mt-2">
                  <div class="flex justify-between text-sm text-gray-600">
                    <span>{{ formatDuration(totalTimeLogged) }} / {{ formatDuration(ticket.estimated_hours * 3600) }}</span>
                    <span>{{ Math.round(estimatedVsActual) }}%</span>
                  </div>
                  <div class="mt-1 bg-gray-200 rounded-full h-2">
                    <div 
                      :class="estimatedVsActual > 100 ? 'bg-red-500' : 'bg-blue-500'"
                      :style="{ width: Math.min(estimatedVsActual, 100) + '%' }"
                      class="h-2 rounded-full transition-all duration-300"
                    ></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Related Tickets Card -->
          <div v-if="relatedTickets.length > 0" class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-semibold text-gray-900">Related Tickets</h3>
            </div>
            <div class="p-6">
              <div class="space-y-3">
                <div 
                  v-for="relatedTicket in relatedTickets" 
                  :key="relatedTicket.id"
                  class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50"
                >
                  <div class="flex-1">
                    <Link 
                      v-if="relatedTicket.id"
                      :href="route('tickets.show', relatedTicket.id)"
                      class="text-sm font-medium text-blue-600 hover:text-blue-700"
                    >
                      {{ relatedTicket.title }}
                    </Link>
                    <span v-else class="text-sm font-medium text-gray-900">
                      {{ relatedTicket.title }}
                    </span>
                    <p class="text-xs text-gray-500">{{ relatedTicket.ticket_number }}</p>
                  </div>
                  <span :class="getStatusClasses(relatedTicket.status)" class="px-2 py-1 rounded-full text-xs font-medium">
                    {{ formatStatus(relatedTicket.status) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Modals and Overlays -->
    <TitleEditModal 
      v-if="editingTitle"
      :ticket="ticket"
      @saved="handleTitleSaved"
      @cancelled="editingTitle = false"
    />
    
    <StatusChangeModal 
      v-if="showStatusModal"
      :ticket="ticket"
      @changed="handleStatusChanged"
      @cancelled="showStatusModal = false"
    />
    
    <AssignmentModal 
      v-if="showAssignModal"
      :ticket="ticket"
      @assigned="handleAssignmentChanged"
      @cancelled="showAssignModal = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import axios from 'axios'

// Import components
import TimeTrackingManager from '@/Components/Tickets/TimeTrackingManager.vue'
import TicketAddonManager from '@/Components/Tickets/TicketAddonManager.vue'
import ActivityTimeline from '@/Components/Tickets/ActivityTimeline.vue'
import BillingOverview from '@/Components/Tickets/BillingOverview.vue'
import TitleEditModal from '@/Components/Tickets/TitleEditModal.vue'
import StatusChangeModal from '@/Components/Tickets/StatusChangeModal.vue'
import AssignmentModal from '@/Components/Tickets/AssignmentModal.vue'

// Define persistent layout
defineOptions({
  layout: AppLayout
})

// Props
const props = defineProps({
  ticket: {
    type: Object,
    default: null
  }
})

// Access user data from Inertia page props
const page = usePage()
const user = computed(() => page.props.auth?.user)

// Reactive data
const ticket = ref(props.ticket)
const activeTab = ref('messages')
const messages = ref([])
const newMessage = ref('')
const messageIsInternal = ref(false)
const sendingMessage = ref(false)
const activeTimers = ref([])
const totalTimeLogged = ref(0)
const relatedTickets = ref([])

// UI States
const showActionsMenu = ref(false)
const editingDescription = ref(false)
const editedDescription = ref('')
const savingDescription = ref(false)
const editingTitle = ref(false)
const showStatusModal = ref(false)
const showAssignModal = ref(false)
const activeTimer = ref(null)

// Computed properties
const tabs = computed(() => [
  { 
    id: 'messages', 
    label: 'Messages', 
    badge: messages.value.length > 0 ? messages.value.length : null 
  },
  { 
    id: 'time', 
    label: 'Time Tracking',
    badge: activeTimers.value.length > 0 ? activeTimers.value.length : null
  },
  { 
    id: 'addons', 
    label: 'Add-ons' 
  },
  { 
    id: 'activity', 
    label: 'Activity' 
  },
  { 
    id: 'billing', 
    label: 'Billing' 
  }
])

const statusClasses = computed(() => {
  if (!ticket.value) return ''
  return getStatusClasses(ticket.value.status)
})

const priorityClasses = computed(() => {
  if (!ticket.value) return ''
  
  const priorityMap = {
    'low': 'bg-gray-100 text-gray-800',
    'normal': 'bg-blue-100 text-blue-800', 
    'medium': 'bg-yellow-100 text-yellow-800',
    'high': 'bg-orange-100 text-orange-800',
    'urgent': 'bg-red-100 text-red-800'
  }
  
  return priorityMap[ticket.value.priority] || 'bg-gray-100 text-gray-800'
})

const dueDateClasses = computed(() => {
  if (!ticket.value?.due_date) return ''
  
  const dueDate = new Date(ticket.value.due_date)
  const now = new Date()
  const diffDays = Math.ceil((dueDate - now) / (1000 * 60 * 60 * 24))
  
  if (diffDays < 0) {
    return 'text-red-600 font-medium' // Overdue
  } else if (diffDays <= 1) {
    return 'text-orange-600 font-medium' // Due soon
  } else {
    return 'text-gray-600' // Normal
  }
})

const estimatedVsActual = computed(() => {
  if (!ticket.value?.estimated_hours || !totalTimeLogged.value) return null
  return (totalTimeLogged.value / (ticket.value.estimated_hours * 3600)) * 100
})

const canEdit = computed(() => {
  // TODO: Implement proper permission checking
  return true
})

const canChangeStatus = computed(() => {
  // TODO: Implement proper permission checking
  return true
})

const canAssign = computed(() => {
  // TODO: Implement proper permission checking
  return true
})

const canManageTime = computed(() => {
  // Only Agents can create timers and manage time entries
  // Account Users (customers) can view time entries but cannot create/manage them
  return user.value?.user_type === 'agent'
})

// Methods
const getStatusClasses = (status) => {
  const statusMap = {
    'open': 'bg-blue-100 text-blue-800',
    'in_progress': 'bg-yellow-100 text-yellow-800',
    'waiting_customer': 'bg-purple-100 text-purple-800',
    'on_hold': 'bg-gray-100 text-gray-800',
    'resolved': 'bg-green-100 text-green-800',
    'closed': 'bg-gray-100 text-gray-800',
    'cancelled': 'bg-red-100 text-red-800'
  }
  
  return statusMap[status] || 'bg-gray-100 text-gray-800'
}

const formatStatus = (status) => {
  return status?.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) || 'Unknown'
}

const formatPriority = (priority) => {
  return priority?.charAt(0).toUpperCase() + priority?.slice(1) || 'Unknown'
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short', 
    day: 'numeric'
  })
}

const formatDateTime = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short', 
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatDuration = (seconds) => {
  if (!seconds) return '0m'
  
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  
  if (hours > 0) {
    return `${hours}h ${minutes}m`
  } else {
    return `${minutes}m`
  }
}

const formatDescription = (description) => {
  if (!description) return ''
  // Simple HTML formatting for line breaks
  return description.replace(/\n/g, '<br>')
}

const formatMessage = (content) => {
  if (!content) return ''
  // Simple HTML formatting for line breaks and basic markdown
  return content
    .replace(/\n/g, '<br>')
    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
    .replace(/\*(.*?)\*/g, '<em>$1</em>')
}

// Data loading methods
const loadTicketDetails = async () => {
  if (!ticket.value?.id) return
  
  try {
    const response = await axios.get(`/api/tickets/${ticket.value.id}`)
    ticket.value = response.data.data
    
    // Load all related data
    await Promise.all([
      loadMessages(),
      loadTimeTrackingData(),
      loadRelatedTickets()
    ])
    
  } catch (error) {
    console.error('Failed to load ticket details:', error)
  }
}

const loadMessages = async () => {
  if (!ticket.value?.id) return
  
  try {
    const response = await axios.get(`/api/tickets/${ticket.value.id}/comments`)
    messages.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load messages:', error)
    messages.value = []
  }
}

const loadTimeTrackingData = async () => {
  if (!ticket.value?.id) return
  
  try {
    // Load active timers
    const timersResponse = await axios.get(`/api/tickets/${ticket.value.id}/timers`)
    activeTimers.value = timersResponse.data.data || []
    
    // Find current user's active timer
    const currentUserId = window.auth?.user?.id
    activeTimer.value = activeTimers.value.find(timer => timer.user_id === currentUserId)
    
    // Load time entries for total calculation
    const timeEntriesResponse = await axios.get(`/api/tickets/${ticket.value.id}/time-entries`)
    const timeEntries = timeEntriesResponse.data.data || []
    totalTimeLogged.value = timeEntries.reduce((total, entry) => total + (entry.duration || 0), 0)
    
  } catch (error) {
    console.error('Failed to load time tracking data:', error)
    activeTimers.value = []
    totalTimeLogged.value = 0
    activeTimer.value = null
  }
}

const loadRelatedTickets = async () => {
  if (!ticket.value?.id) return
  
  try {
    const response = await axios.get(`/api/tickets/${ticket.value.id}/related`)
    relatedTickets.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load related tickets:', error)
    relatedTickets.value = []
  }
}

// Action methods
const sendMessage = async () => {
  if (!newMessage.value.trim() || sendingMessage.value || !ticket.value?.id) return
  
  sendingMessage.value = true
  
  try {
    const response = await axios.post(`/api/tickets/${ticket.value.id}/comments`, {
      content: newMessage.value.trim(),
      is_internal: messageIsInternal.value
    })
    
    // Add new message to the list
    messages.value.unshift(response.data.data)
    
    // Reset form
    newMessage.value = ''
    messageIsInternal.value = false
    
  } catch (error) {
    console.error('Failed to send message:', error)
    // TODO: Show error notification
  } finally {
    sendingMessage.value = false
  }
}

const startTimer = async () => {
  if (!ticket.value?.id || activeTimer.value) return
  
  try {
    const response = await axios.post('/api/timers', {
      ticket_id: ticket.value.id,
      description: `Working on ${ticket.value.title || 'ticket'}`
    })
    
    // Refresh timer data
    await loadTimeTrackingData()
    
  } catch (error) {
    console.error('Failed to start timer:', error)
  }
}

const stopTimer = async () => {
  if (!activeTimer.value) return
  
  try {
    await axios.post(`/api/timers/${activeTimer.value.id}/stop`)
    
    // Refresh timer data
    await loadTimeTrackingData()
    
  } catch (error) {
    console.error('Failed to stop timer:', error)
  }
}

const changeStatus = async (newStatus) => {
  if (!ticket.value?.id || ticket.value.status === newStatus) return
  
  try {
    const response = await axios.put(`/api/tickets/${ticket.value.id}`, {
      status: newStatus
    })
    
    ticket.value.status = newStatus
    
    // Reload messages to show status change activity
    await loadMessages()
    
  } catch (error) {
    console.error('Failed to change status:', error)
  }
}

// Inline editing methods
const startEditingDescription = () => {
  editingDescription.value = true
  editedDescription.value = ticket.value.description || ''
}

const cancelEditingDescription = () => {
  editingDescription.value = false
  editedDescription.value = ''
}

const saveDescription = async () => {
  if (!ticket.value?.id) return
  
  savingDescription.value = true
  
  try {
    await axios.put(`/api/tickets/${ticket.value.id}`, {
      description: editedDescription.value.trim()
    })
    
    ticket.value.description = editedDescription.value.trim()
    editingDescription.value = false
    editedDescription.value = ''
    
    // Reload messages to show description change activity
    await loadMessages()
    
  } catch (error) {
    console.error('Failed to save description:', error)
  } finally {
    savingDescription.value = false
  }
}

const startEditingTitle = () => {
  editingTitle.value = true
}

// Modal handlers
const handleTitleSaved = (newTitle) => {
  ticket.value.title = newTitle
  editingTitle.value = false
}

const handleStatusChanged = (newStatus) => {
  ticket.value.status = newStatus
  showStatusModal.value = false
  loadMessages() // Reload to show status change activity
}

const handleAssignmentChanged = (newAgent) => {
  ticket.value.agent = newAgent
  ticket.value.agent_id = newAgent?.id
  showAssignModal.value = false
  loadMessages() // Reload to show assignment change activity
}

// File upload handler
const handleFileUpload = async (event) => {
  const files = Array.from(event.target.files)
  if (files.length === 0) return
  
  // TODO: Implement file upload functionality
  console.log('Files to upload:', files)
}

// Action menu methods
const duplicateTicket = () => {
  // TODO: Implement ticket duplication
  console.log('Duplicate ticket')
  showActionsMenu.value = false
}

const exportTicket = () => {
  // TODO: Implement ticket export
  console.log('Export ticket')
  showActionsMenu.value = false
}

const deleteTicket = () => {
  // TODO: Implement ticket deletion with confirmation
  console.log('Delete ticket')
  showActionsMenu.value = false
}

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
  if (showActionsMenu.value && !event.target.closest('[ref="actionsDropdown"]')) {
    showActionsMenu.value = false
  }
}

// Lifecycle
onMounted(() => {
  loadTicketDetails()
  document.addEventListener('click', handleClickOutside)
})

// Watchers for real-time updates
watch(() => ticket.value?.id, (newId) => {
  if (newId) {
    loadTicketDetails()
  }
})
</script>