<template>
  <StackedDialog
    :show="show"
    title="Approval Wizard"
    max-width="4xl"
    @close="$emit('close')"
  >
    <template #header-subtitle v-if="accountName">
      <p class="mt-1 text-sm text-gray-600">
        Reviewing items for: {{ accountName }}
      </p>
    </template>

    <!-- Progress Bar -->
    <div class="mb-6">
      <div class="flex justify-between items-center mb-2">
        <span class="text-sm font-medium text-gray-700">Progress</span>
        <span class="text-sm text-gray-500">
          {{ reviewedCount }} of {{ totalPendingItems }} reviewed
        </span>
      </div>
      <div class="w-full bg-gray-200 rounded-full h-2">
        <div 
          class="bg-indigo-600 h-2 rounded-full transition-all duration-300"
          :style="{ width: `${progressPercentage}%` }"
        ></div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-12">
      <svg class="animate-spin h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
    </div>

    <!-- Content -->
    <div v-else-if="pendingItems.length > 0" class="space-y-6">
      <!-- Bulk Actions -->
      <div class="flex items-center justify-between bg-gray-50 p-4 rounded-lg">
        <div class="flex items-center space-x-4">
          <div class="flex items-center">
            <input
              id="select-all-items"
              v-model="selectAllChecked"
              type="checkbox"
              :indeterminate="isIndeterminate"
              @change="handleSelectAll"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <label for="select-all-items" class="ml-2 text-sm font-medium text-gray-900">
              Select All ({{ pendingItems.length }} items)
            </label>
          </div>
        </div>
        <div class="flex items-center space-x-3">
          <button
            v-if="selectedItems.length > 0"
            @click="showBulkRejectModal = true"
            class="inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50"
          >
            Reject Selected ({{ selectedItems.length }})
          </button>
          <button
            v-if="selectedItems.length > 0"
            @click="bulkApprove"
            :disabled="bulkProcessing"
            class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 disabled:opacity-50"
          >
            <span v-if="bulkProcessing" class="mr-2">
              <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
            </span>
            Approve Selected ({{ selectedItems.length }})
          </button>
        </div>
      </div>

      <!-- Items List - Organized by Type -->
      <div class="space-y-6 max-h-96 overflow-y-auto">
        <!-- Time Entries Section -->
        <div v-if="timeEntries.length > 0">
          <div class="flex items-center justify-between mb-3">
            <div class="flex items-center space-x-3">
              <h3 class="text-sm font-medium text-gray-900 flex items-center">
                <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Time Entries
              </h3>
              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                {{ timeEntries.length }} pending
              </span>
            </div>
            <div class="flex items-center space-x-2">
              <button
                @click="selectAllTimeEntries"
                class="text-xs text-blue-600 hover:text-blue-800"
              >
                Select All
              </button>
              <span class="text-gray-300">|</span>
              <button
                @click="deselectAllTimeEntries"
                class="text-xs text-gray-600 hover:text-gray-800"
              >
                Deselect All
              </button>
            </div>
          </div>
          
          <div class="space-y-2">
            <div
              v-for="item in timeEntries"
              :key="`${item.type}-${item.id}`"
              class="border border-gray-200 rounded-lg p-4 bg-blue-50/30"
              :class="{ 'bg-blue-100 border-blue-300': selectedItems.includes(`${item.type}-${item.id}`) }"
            >
              <div class="flex items-start justify-between">
                <div class="flex items-start">
                  <input
                    :id="`item-${item.type}-${item.id}`"
                    v-model="selectedItems"
                    :value="`${item.type}-${item.id}`"
                    type="checkbox"
                    class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                  />
                  <div class="ml-3 flex-1">
                    <div class="flex items-center">
                      <h4 class="text-sm font-medium text-gray-900">{{ item.description }}</h4>
                    </div>
                    
                    <div class="mt-1 text-sm text-gray-500">
                      <div class="flex items-center space-x-4">
                        <span>{{ formatQuantity(item) }} × ${{ item.unit_price }}</span>
                        <span v-if="item.ticket" class="text-indigo-600">{{ item.ticket.ticket_number }}</span>
                        <span v-if="item.user" class="text-gray-600">{{ item.user.name }}</span>
                      </div>
                    </div>
                  </div>
                </div>
                
                <div class="flex items-center space-x-3">
                  <div class="text-right">
                    <div class="text-sm font-medium text-gray-900">${{ item.total_amount }}</div>
                    <div v-if="item.started_at" class="text-xs text-gray-500">
                      {{ formatDate(item.started_at) }}
                    </div>
                  </div>
                  
                  <div class="flex flex-col space-y-1">
                    <button
                      v-if="canOverrideRates"
                      @click="editTimeEntry(item)"
                      :disabled="processingItems.includes(`${item.type}-${item.id}`)"
                      class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 disabled:opacity-50"
                    >
                      <PencilIcon class="h-3 w-3 mr-1" />
                      Edit
                    </button>
                    <button
                      @click="approveItem(item)"
                      :disabled="processingItems.includes(`${item.type}-${item.id}`)"
                      class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-md text-green-700 bg-green-100 hover:bg-green-200 disabled:opacity-50"
                    >
                      <CheckIcon class="h-3 w-3 mr-1" />
                      Approve
                    </button>
                    <button
                      @click="showRejectModal(item)"
                      :disabled="processingItems.includes(`${item.type}-${item.id}`)"
                      class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 disabled:opacity-50"
                    >
                      <XMarkIcon class="h-3 w-3 mr-1" />
                      Reject
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Addons Section -->
        <div v-if="addons.length > 0">
          <div class="flex items-center justify-between mb-3">
            <div class="flex items-center space-x-3">
              <h3 class="text-sm font-medium text-gray-900 flex items-center">
                <svg class="w-4 h-4 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add-ons
              </h3>
              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                {{ addons.length }} pending
              </span>
            </div>
            <div class="flex items-center space-x-2">
              <button
                @click="selectAllAddons"
                class="text-xs text-purple-600 hover:text-purple-800"
              >
                Select All
              </button>
              <span class="text-gray-300">|</span>
              <button
                @click="deselectAllAddons"
                class="text-xs text-gray-600 hover:text-gray-800"
              >
                Deselect All
              </button>
            </div>
          </div>
          
          <div class="space-y-2">
            <div
              v-for="item in addons"
              :key="`${item.type}-${item.id}`"
              class="border border-gray-200 rounded-lg p-4 bg-purple-50/30"
              :class="{ 'bg-purple-100 border-purple-300': selectedItems.includes(`${item.type}-${item.id}`) }"
            >
              <div class="flex items-start justify-between">
                <div class="flex items-start">
                  <input
                    :id="`item-${item.type}-${item.id}`"
                    v-model="selectedItems"
                    :value="`${item.type}-${item.id}`"
                    type="checkbox"
                    class="mt-1 h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded"
                  />
                  <div class="ml-3 flex-1">
                    <div class="flex items-center">
                      <h4 class="text-sm font-medium text-gray-900">{{ item.description }}</h4>
                    </div>
                    
                    <div class="mt-1 text-sm text-gray-500">
                      <div class="flex items-center space-x-4">
                        <span>{{ formatQuantity(item) }} × ${{ item.unit_price }}</span>
                        <span v-if="item.ticket" class="text-indigo-600">{{ item.ticket.ticket_number }}</span>
                        <span v-if="item.added_by" class="text-gray-600">{{ item.added_by.name }}</span>
                      </div>
                    </div>
                  </div>
                </div>
                
                <div class="flex items-center space-x-3">
                  <div class="text-right">
                    <div class="text-sm font-medium text-gray-900">${{ item.total_amount }}</div>
                    <div v-if="item.created_at" class="text-xs text-gray-500">
                      {{ formatDate(item.created_at) }}
                    </div>
                  </div>
                  
                  <div class="flex flex-col space-y-1">
                    <button
                      @click="editAddon(item)"
                      :disabled="processingItems.includes(`${item.type}-${item.id}`)"
                      class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-md text-purple-700 bg-purple-100 hover:bg-purple-200 disabled:opacity-50"
                    >
                      <PencilIcon class="h-3 w-3 mr-1" />
                      Edit
                    </button>
                    <button
                      @click="approveItem(item)"
                      :disabled="processingItems.includes(`${item.type}-${item.id}`)"
                      class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-md text-green-700 bg-green-100 hover:bg-green-200 disabled:opacity-50"
                    >
                      <CheckIcon class="h-3 w-3 mr-1" />
                      Approve
                    </button>
                    <button
                      @click="showRejectModal(item)"
                      :disabled="processingItems.includes(`${item.type}-${item.id}`)"
                      class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 disabled:opacity-50"
                    >
                      <XMarkIcon class="h-3 w-3 mr-1" />
                      Reject
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!loading && pendingItems.length === 0" class="text-center py-12">
      <CheckCircleIcon class="mx-auto h-12 w-12 text-green-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">All items approved!</h3>
      <p class="mt-1 text-sm text-gray-500">
        There are no pending items requiring approval for this account.
      </p>
    </div>

    <!-- Footer Actions -->
    <div v-if="!loading" class="mt-6 flex justify-between items-center pt-6 border-t border-gray-200">
      <div class="text-sm text-gray-600">
        <span v-if="completedCount > 0">{{ completedCount }} item(s) processed</span>
      </div>
      
      <div class="flex gap-3">
        <button
          @click="$emit('close')"
          class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
        >
          {{ pendingItems.length === 0 ? 'Done' : 'Close' }}
        </button>
        
        <button
          v-if="pendingItems.length === 0 && completedCount > 0"
          @click="createInvoiceAfterApproval"
          class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700"
        >
          Create Invoice
        </button>
      </div>
    </div>
  </StackedDialog>

  <!-- Individual Reject Modal -->
  <StackedDialog
    :show="showIndividualRejectModal"
    title="Reject Item"
    max-width="lg"
    :nested="true"
    @close="showIndividualRejectModal = false"
  >
    <div>
      <p class="text-sm text-gray-600 mb-4">
        Please provide a reason for rejecting this item:
      </p>
      <textarea
        v-model="rejectReason"
        rows="3"
        class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        placeholder="Enter rejection reason..."
      />
    </div>
    <div class="mt-5 sm:mt-6 flex gap-3">
      <button
        @click="showIndividualRejectModal = false"
        class="flex-1 inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 sm:text-sm"
      >
        Cancel
      </button>
      <button
        @click="confirmRejectItem"
        :disabled="!rejectReason.trim() || processingReject"
        class="flex-1 inline-flex justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-red-700 disabled:opacity-50 sm:text-sm"
      >
        {{ processingReject ? 'Rejecting...' : 'Reject Item' }}
      </button>
    </div>
  </StackedDialog>

  <!-- Bulk Reject Modal -->
  <StackedDialog
    :show="showBulkRejectModal"
    :title="`Reject ${selectedItems.length} Item(s)`"
    max-width="lg"
    :nested="true"
    @close="showBulkRejectModal = false"
  >
    <div>
      <p class="text-sm text-gray-600 mb-4">
        Please provide a reason for rejecting these items:
      </p>
      <textarea
        v-model="bulkRejectReason"
        rows="3"
        class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        placeholder="Enter rejection reason..."
      />
    </div>
    <div class="mt-5 sm:mt-6 flex gap-3">
      <button
        @click="showBulkRejectModal = false"
        class="flex-1 inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 sm:text-sm"
      >
        Cancel
      </button>
      <button
        @click="confirmBulkReject"
        :disabled="!bulkRejectReason.trim() || bulkProcessing"
        class="flex-1 inline-flex justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-red-700 disabled:opacity-50 sm:text-sm"
      >
        {{ bulkProcessing ? 'Rejecting...' : 'Reject Items' }}
      </button>
    </div>
  </StackedDialog>

  <!-- Time Entry Edit Modal -->
  <UnifiedTimeEntryDialog
    :show="showTimeEntryEditModal"
    :time-entry="timeEntryToEdit"
    mode="edit"
    @close="showTimeEntryEditModal = false"
    @saved="onTimeEntryUpdated"
  />

  <!-- Addon Edit Modal -->
  <AddonModal
    :show="showAddonEditModal"
    :addon="addonToEdit"
    mode="edit"
    @close="closeAddonEditModal"
    @saved="onAddonUpdated"
  />
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { 
  XMarkIcon, 
  CheckIcon, 
  CheckCircleIcon,
  PencilIcon
} from '@heroicons/vue/24/outline'
import { usePage } from '@inertiajs/vue3'
import StackedDialog from '@/Components/StackedDialog.vue'
import UnifiedTimeEntryDialog from '@/Components/TimeEntries/UnifiedTimeEntryDialog.vue'
import AddonModal from '@/Components/TimeEntries/AddonModal.vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  accountId: {
    type: String,
    required: false
  },
  accountName: {
    type: String,
    default: ''
  },
  allAccounts: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close', 'completed', 'createInvoice'])

// User permissions
const { props: pageProps } = usePage()
const user = computed(() => pageProps.auth?.user)

// Check if user can override rates (time managers and admins)
const canOverrideRates = computed(() => {
  if (!user.value) return false
  return user.value.permissions?.includes('time.manage') || 
         user.value.permissions?.includes('admin.manage')
})

// Reactive state
const loading = ref(false)
const pendingItems = ref([])
const selectedItems = ref([])
const processingItems = ref([])
const completedCount = ref(0)
const bulkProcessing = ref(false)
const processingReject = ref(false)

// Time entry edit modal
const showTimeEntryEditModal = ref(false)
const timeEntryToEdit = ref(null)

// Addon edit modal
const showAddonEditModal = ref(false)
const addonToEdit = ref(null)

// Reject modals
const showIndividualRejectModal = ref(false)
const showBulkRejectModal = ref(false)
const rejectReason = ref('')
const bulkRejectReason = ref('')
const itemToReject = ref(null)

// Computed properties
const totalPendingItems = computed(() => {
  return pendingItems.value.length + completedCount.value
})

const reviewedCount = computed(() => {
  return completedCount.value
})

const progressPercentage = computed(() => {
  if (totalPendingItems.value === 0) return 100
  return Math.round((reviewedCount.value / totalPendingItems.value) * 100)
})

const selectAllChecked = computed({
  get() {
    return pendingItems.value.length > 0 && selectedItems.value.length === pendingItems.value.length
  },
  set() {
    // Handled by handleSelectAll method
  }
})

const isIndeterminate = computed(() => {
  return selectedItems.value.length > 0 && selectedItems.value.length < pendingItems.value.length
})

// Computed properties for logical separation
const timeEntries = computed(() => {
  return pendingItems.value.filter(item => item.type === 'time_entry')
})

const addons = computed(() => {
  return pendingItems.value.filter(item => item.type === 'ticket_addon')
})

// Methods
const loadPendingItems = async () => {
  // Skip only if we have no account context at all (null/undefined), but allow empty string for "All Accounts"
  if (props.accountId === null || props.accountId === undefined) {
    if (!props.allAccounts) return
  }
  
  loading.value = true
  try {
    // Empty accountId means "All Accounts"
    const url = props.allAccounts || props.accountId === '' 
      ? '/api/billing/unbilled-items?include_unapproved=true'
      : `/api/billing/unbilled-items?account_id=${props.accountId}&include_unapproved=true`
      
    const response = await fetch(url, {
      method: 'GET',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      
      // Combine time entries and addons that need approval
      const timeEntries = data.data.unapproved?.time_entries || []
      const addons = data.data.unapproved?.ticket_addons || []
      
      pendingItems.value = [...timeEntries, ...addons].sort((a, b) => {
        const dateA = new Date(a.started_at || a.created_at)
        const dateB = new Date(b.started_at || b.created_at)
        return dateB - dateA
      })
    }
  } catch (error) {
    console.error('Error loading pending items:', error)
  } finally {
    loading.value = false
  }
}

const handleSelectAll = () => {
  if (selectAllChecked.value) {
    selectedItems.value = []
  } else {
    selectedItems.value = pendingItems.value.map(item => `${item.type}-${item.id}`)
  }
}

// Type-specific selection methods
const selectAllTimeEntries = () => {
  const timeEntryKeys = timeEntries.value.map(item => `${item.type}-${item.id}`)
  timeEntryKeys.forEach(key => {
    if (!selectedItems.value.includes(key)) {
      selectedItems.value.push(key)
    }
  })
}

const deselectAllTimeEntries = () => {
  const timeEntryKeys = timeEntries.value.map(item => `${item.type}-${item.id}`)
  selectedItems.value = selectedItems.value.filter(key => !timeEntryKeys.includes(key))
}

const selectAllAddons = () => {
  const addonKeys = addons.value.map(item => `${item.type}-${item.id}`)
  addonKeys.forEach(key => {
    if (!selectedItems.value.includes(key)) {
      selectedItems.value.push(key)
    }
  })
}

const deselectAllAddons = () => {
  const addonKeys = addons.value.map(item => `${item.type}-${item.id}`)
  selectedItems.value = selectedItems.value.filter(key => !addonKeys.includes(key))
}

const approveItem = async (item) => {
  const itemKey = `${item.type}-${item.id}`
  processingItems.value.push(itemKey)
  
  try {
    const endpoint = item.type === 'time_entry' 
      ? `/api/time-entries/${item.id}/approve`
      : `/api/ticket-addons/${item.id}/approve`
    
    const response = await fetch(endpoint, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({
        notes: 'Approved via billing wizard'
      })
    })
    
    if (response.ok) {
      // Remove from pending items
      pendingItems.value = pendingItems.value.filter(i => i.id !== item.id || i.type !== item.type)
      selectedItems.value = selectedItems.value.filter(id => id !== itemKey)
      completedCount.value++
    } else {
      console.error('Error approving item:', await response.text())
    }
  } catch (error) {
    console.error('Error approving item:', error)
  } finally {
    processingItems.value = processingItems.value.filter(id => id !== itemKey)
  }
}

const showRejectModal = (item) => {
  itemToReject.value = item
  rejectReason.value = ''
  showIndividualRejectModal.value = true
}

const confirmRejectItem = async () => {
  if (!itemToReject.value || !rejectReason.value.trim()) return
  
  processingReject.value = true
  const item = itemToReject.value
  const itemKey = `${item.type}-${item.id}`
  
  try {
    const endpoint = item.type === 'time_entry' 
      ? `/api/time-entries/${item.id}/reject`
      : `/api/ticket-addons/${item.id}/reject`
    
    const response = await fetch(endpoint, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({
        notes: rejectReason.value
      })
    })
    
    if (response.ok) {
      // Remove from pending items
      pendingItems.value = pendingItems.value.filter(i => i.id !== item.id || i.type !== item.type)
      selectedItems.value = selectedItems.value.filter(id => id !== itemKey)
      completedCount.value++
      showIndividualRejectModal.value = false
    } else {
      console.error('Error rejecting item:', await response.text())
    }
  } catch (error) {
    console.error('Error rejecting item:', error)
  } finally {
    processingReject.value = false
  }
}

const bulkApprove = async () => {
  if (selectedItems.value.length === 0) return
  
  bulkProcessing.value = true
  const timeEntryIds = []
  const addonIds = []
  
  // Separate items by type
  selectedItems.value.forEach(itemId => {
    const [type, id] = itemId.split('-')
    if (type === 'time_entry') {
      timeEntryIds.push(id)
    } else if (type === 'ticket_addon') {
      addonIds.push(id)
    }
  })
  
  try {
    const promises = []
    
    // Bulk approve time entries
    if (timeEntryIds.length > 0) {
      promises.push(
        fetch('/api/time-entries/bulk/approve', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          },
          body: JSON.stringify({
            time_entry_ids: timeEntryIds,
            notes: 'Approved via billing wizard'
          })
        })
      )
    }
    
    // Bulk approve addons
    if (addonIds.length > 0) {
      promises.push(
        fetch('/api/ticket-addons/bulk/approve', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          },
          body: JSON.stringify({
            ticket_addon_ids: addonIds,
            notes: 'Approved via billing wizard'
          })
        })
      )
    }
    
    await Promise.all(promises)
    
    // Remove approved items from pending list
    const approvedCount = selectedItems.value.length
    pendingItems.value = pendingItems.value.filter(item => {
      return !selectedItems.value.includes(`${item.type}-${item.id}`)
    })
    
    completedCount.value += approvedCount
    selectedItems.value = []
    
  } catch (error) {
    console.error('Error bulk approving items:', error)
  } finally {
    bulkProcessing.value = false
  }
}

const confirmBulkReject = async () => {
  if (selectedItems.value.length === 0 || !bulkRejectReason.value.trim()) return
  
  bulkProcessing.value = true
  const timeEntryIds = []
  const addonIds = []
  
  // Separate items by type
  selectedItems.value.forEach(itemId => {
    const [type, id] = itemId.split('-')
    if (type === 'time_entry') {
      timeEntryIds.push(id)
    } else if (type === 'ticket_addon') {
      addonIds.push(id)
    }
  })
  
  try {
    const promises = []
    
    // Bulk reject time entries
    if (timeEntryIds.length > 0) {
      promises.push(
        fetch('/api/time-entries/bulk/reject', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          },
          body: JSON.stringify({
            time_entry_ids: timeEntryIds,
            notes: bulkRejectReason.value
          })
        })
      )
    }
    
    // Bulk reject addons
    if (addonIds.length > 0) {
      promises.push(
        fetch('/api/ticket-addons/bulk/reject', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          },
          body: JSON.stringify({
            ticket_addon_ids: addonIds,
            notes: bulkRejectReason.value
          })
        })
      )
    }
    
    await Promise.all(promises)
    
    // Remove rejected items from pending list
    const rejectedCount = selectedItems.value.length
    pendingItems.value = pendingItems.value.filter(item => {
      return !selectedItems.value.includes(`${item.type}-${item.id}`)
    })
    
    completedCount.value += rejectedCount
    selectedItems.value = []
    showBulkRejectModal.value = false
    bulkRejectReason.value = ''
    
  } catch (error) {
    console.error('Error bulk rejecting items:', error)
  } finally {
    bulkProcessing.value = false
  }
}

const createInvoiceAfterApproval = () => {
  emit('createInvoice', {
    accountId: props.accountId,
    accountName: props.accountName
  })
}

const formatQuantity = (item) => {
  if (item.type === 'time_entry') {
    const hours = Math.floor(item.quantity)
    const minutes = Math.round((item.quantity - hours) * 60)
    if (hours === 0) return `${minutes}m`
    if (minutes === 0) return `${hours}h`
    return `${hours}h ${minutes}m`
  }
  return item.quantity
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString()
}

const editTimeEntry = async (item) => {
  try {
    // Fetch the full time entry data for editing (include relationships)
    const response = await fetch(`/api/time-entries/${item.id}?include=ticket,account,user,billing_rate`, {
      method: 'GET',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    
    if (response.ok) {
      const responseData = await response.json()
      // Handle API response structure (may be wrapped in 'data' property)
      const fullTimeEntry = responseData.data || responseData
      
      // Ensure ticket data is preserved from original item if not in API response
      if (!fullTimeEntry.ticket && item.ticket) {
        fullTimeEntry.ticket = item.ticket
      }
      
      timeEntryToEdit.value = fullTimeEntry
      showTimeEntryEditModal.value = true
    } else {
      console.error('Failed to fetch time entry details')
      // Fallback to original item data which includes ticket relationship
      timeEntryToEdit.value = item
      showTimeEntryEditModal.value = true
    }
  } catch (error) {
    console.error('Error fetching time entry:', error)
    // Fallback to original item data which includes ticket relationship
    timeEntryToEdit.value = item
    showTimeEntryEditModal.value = true
  }
}

const onTimeEntryUpdated = (updatedTimeEntry) => {
  // Close the edit modal
  showTimeEntryEditModal.value = false
  timeEntryToEdit.value = null
  
  // Reload the pending items to get the latest data
  loadPendingItems()
}

const editAddon = async (item) => {
  try {
    // Fetch the full addon data for editing (include relationships)
    const response = await fetch(`/api/ticket-addons/${item.id}?include=ticket`, {
      method: 'GET',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    
    if (response.ok) {
      const responseData = await response.json()
      // Handle API response structure (may be wrapped in 'data' property)
      const fullAddon = responseData.data || responseData
      
      // Ensure ticket data is preserved from original item if not in API response
      if (!fullAddon.ticket && item.ticket) {
        fullAddon.ticket = item.ticket
      }
      
      addonToEdit.value = fullAddon
      showAddonEditModal.value = true
    } else {
      console.error('Failed to fetch addon details')
      // Fallback to original item data which includes ticket relationship
      addonToEdit.value = item
      showAddonEditModal.value = true
    }
  } catch (error) {
    console.error('Error fetching addon:', error)
    // Fallback to original item data which includes ticket relationship
    addonToEdit.value = item
    showAddonEditModal.value = true
  }
}

const closeAddonEditModal = () => {
  showAddonEditModal.value = false
  addonToEdit.value = null
}

const onAddonUpdated = (updatedAddon) => {
  // Close the edit modal
  showAddonEditModal.value = false
  addonToEdit.value = null
  
  // Reload the pending items to get the latest data
  loadPendingItems()
}

// Watch for modal show/hide
watch(() => props.show, (isShowing) => {
  if (isShowing) {
    loadPendingItems()
    completedCount.value = 0
    selectedItems.value = []
  } else {
    // Clear edit modal state when approval wizard is closed
    showTimeEntryEditModal.value = false
    timeEntryToEdit.value = null
    showAddonEditModal.value = false
    addonToEdit.value = null
  }
})

onMounted(() => {
  if (props.show) {
    loadPendingItems()
  }
})
</script>