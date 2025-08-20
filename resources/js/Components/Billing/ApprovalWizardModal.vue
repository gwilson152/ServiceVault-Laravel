<template>
  <TransitionRoot as="template" :show="show">
    <Dialog as="div" class="relative z-10" @close="$emit('close')">
      <TransitionChild
        as="template"
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
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-4xl">
              <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                  <div>
                    <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900">
                      Approval Wizard
                    </DialogTitle>
                    <p v-if="accountName" class="mt-1 text-sm text-gray-600">
                      Reviewing items for: {{ accountName }}
                    </p>
                  </div>
                  <button
                    @click="$emit('close')"
                    class="text-gray-400 hover:text-gray-500"
                  >
                    <XMarkIcon class="h-6 w-6" />
                  </button>
                </div>

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

                  <!-- Items List -->
                  <div class="space-y-3 max-h-96 overflow-y-auto">
                    <div
                      v-for="item in pendingItems"
                      :key="`${item.type}-${item.id}`"
                      class="border border-gray-200 rounded-lg p-4"
                      :class="{ 'bg-blue-50 border-blue-200': selectedItems.includes(`${item.type}-${item.id}`) }"
                    >
                      <div class="flex items-start justify-between">
                        <div class="flex items-start">
                          <input
                            :id="`item-${item.type}-${item.id}`"
                            v-model="selectedItems"
                            :value="`${item.type}-${item.id}`"
                            type="checkbox"
                            class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                          />
                          <div class="ml-3 flex-1">
                            <div class="flex items-center">
                              <h4 class="text-sm font-medium text-gray-900">{{ item.description }}</h4>
                              <span v-if="item.type === 'time_entry'" class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                Time Entry
                              </span>
                              <span v-else class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                Add-on
                              </span>
                            </div>
                            
                            <div class="mt-1 text-sm text-gray-500">
                              <div class="flex items-center space-x-4">
                                <span>{{ formatQuantity(item) }} × ${{ item.unit_price }}</span>
                                <span v-if="item.ticket" class="text-indigo-600">{{ item.ticket.ticket_number }}</span>
                                <span v-if="item.user" class="text-gray-600">{{ item.user.name }}</span>
                                <span v-else-if="item.added_by" class="text-gray-600">{{ item.added_by.name }}</span>
                              </div>
                            </div>
                            
                            <!-- Rate Override Field (Time Entries Only, Time Managers/Admins Only) -->
                            <div v-if="item.type === 'time_entry' && canOverrideRates" class="mt-2">
                              <label :for="`rate-override-${item.id}`" class="block text-xs font-medium text-gray-700 mb-1">
                                Rate Override ($/hr)
                              </label>
                              <input
                                :id="`rate-override-${item.id}`"
                                v-model="rateOverrides[`${item.type}-${item.id}`]"
                                type="number"
                                step="0.01"
                                min="0"
                                :placeholder="item.unit_price"
                                class="w-24 px-2 py-1 text-xs border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                              />
                              <span class="ml-1 text-xs text-gray-500">
                                (Current: ${{ item.unit_price }}/hr)
                              </span>
                            </div>
                          </div>
                        </div>
                        
                        <div class="flex items-center space-x-3">
                          <div class="text-right">
                            <div class="text-sm font-medium text-gray-900">${{ item.total_amount }}</div>
                            <div v-if="item.started_at" class="text-xs text-gray-500">
                              {{ formatDate(item.started_at) }}
                            </div>
                            <div v-else-if="item.created_at" class="text-xs text-gray-500">
                              {{ formatDate(item.created_at) }}
                            </div>
                          </div>
                          
                          <div class="flex flex-col space-y-1">
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
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>

  <!-- Individual Reject Modal -->
  <TransitionRoot as="template" :show="showIndividualRejectModal">
    <Dialog as="div" class="relative z-20" @close="showIndividualRejectModal = false">
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 z-20 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
              <div>
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">
                  Reject Item
                </h3>
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
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>

  <!-- Bulk Reject Modal -->
  <TransitionRoot as="template" :show="showBulkRejectModal">
    <Dialog as="div" class="relative z-20" @close="showBulkRejectModal = false">
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 z-20 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
              <div>
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">
                  Reject {{ selectedItems.length }} Item(s)
                </h3>
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
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'
import { 
  XMarkIcon, 
  CheckIcon, 
  CheckCircleIcon 
} from '@heroicons/vue/24/outline'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  accountId: {
    type: String,
    required: true
  },
  accountName: {
    type: String,
    default: ''
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
const rateOverrides = ref({}) // Store rate overrides by item key
const processingItems = ref([])
const completedCount = ref(0)
const bulkProcessing = ref(false)
const processingReject = ref(false)

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

// Methods
const loadPendingItems = async () => {
  if (!props.accountId) return
  
  loading.value = true
  try {
    const response = await fetch(`/api/billing/unbilled-items?account_id=${props.accountId}&include_unapproved=true`, {
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

const approveItem = async (item) => {
  const itemKey = `${item.type}-${item.id}`
  processingItems.value.push(itemKey)
  
  try {
    const endpoint = item.type === 'time_entry' 
      ? `/api/time-entries/${item.id}/approve`
      : `/api/ticket-addons/${item.id}/approve`
    
    // Prepare request body
    const requestBody = {
      notes: 'Approved via billing wizard'
    }
    
    // Add rate override if provided for time entries
    if (item.type === 'time_entry' && rateOverrides.value[itemKey]) {
      requestBody.rate_override = parseFloat(rateOverrides.value[itemKey])
    }
    
    const response = await fetch(endpoint, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify(requestBody)
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

// Watch for modal show/hide
watch(() => props.show, (isShowing) => {
  if (isShowing) {
    loadPendingItems()
    completedCount.value = 0
    selectedItems.value = []
  }
})

onMounted(() => {
  if (props.show) {
    loadPendingItems()
  }
})
</script>