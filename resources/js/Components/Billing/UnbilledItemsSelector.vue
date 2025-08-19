<template>
  <div class="space-y-4">
    <!-- Header with totals -->
    <div class="bg-gray-50 p-4 rounded-lg border">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-lg font-medium text-gray-900">Unbilled Items</h3>
          <p v-if="accountName" class="text-sm text-gray-600">Account: {{ accountName }}</p>
        </div>
        <div class="text-right">
          <div class="text-sm text-gray-600">
            <div class="flex items-center space-x-4">
              <div class="text-green-600">
                <span class="font-medium">{{ totals.approved_count }}</span> Approved
                <span class="text-gray-500">(${{ formatCurrency(totals.approved_amount) }})</span>
              </div>
              <div v-if="totals.pending_count > 0" class="text-yellow-600">
                <span class="font-medium">{{ totals.pending_count }}</span> Pending
                <span class="text-gray-500">(${{ formatCurrency(totals.pending_amount) }})</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Show unapproved items notice if any exist -->
    <div v-if="totals.pending_count > 0" class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <ExclamationTriangleIcon class="h-5 w-5 text-yellow-400" />
        </div>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-yellow-800">
            Items Requiring Approval
          </h3>
          <p class="mt-1 text-sm text-yellow-700">
            {{ totals.pending_count }} items need approval before they can be included in invoices.
            <button 
              @click="$emit('launchApprovalWizard')"
              class="font-medium underline hover:no-underline ml-1"
            >
              Review and approve items
            </button>
          </p>
        </div>
      </div>
    </div>

    <!-- Toggle for showing unapproved items -->
    <div class="flex items-center justify-between">
      <div class="flex items-center">
        <input
          id="show-unapproved"
          v-model="showUnapproved"
          type="checkbox"
          class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
        />
        <label for="show-unapproved" class="ml-2 block text-sm text-gray-900">
          Show items requiring approval
        </label>
      </div>
      <div class="flex items-center space-x-3">
        <button
          v-if="hasSelectedItems"
          @click="selectAll"
          class="text-sm text-indigo-600 hover:text-indigo-500"
        >
          Select All
        </button>
        <button
          v-if="hasSelectedItems"
          @click="clearSelection"
          class="text-sm text-gray-600 hover:text-gray-500"
        >
          Clear Selection
        </button>
      </div>
    </div>

    <!-- Approved Items Section -->
    <div v-if="approvedItems.length > 0">
      <h4 class="text-sm font-medium text-gray-900 mb-3 flex items-center">
        <CheckCircleIcon class="h-4 w-4 text-green-500 mr-1" />
        Approved Items ({{ approvedItems.length }})
      </h4>
      
      <div class="space-y-2">
        <div
          v-for="item in approvedItems"
          :key="`${item.type}-${item.id}`"
          class="border border-gray-200 rounded-lg p-3 hover:bg-gray-50"
        >
          <div class="flex items-start justify-between">
            <div class="flex items-start">
              <input
                :id="`approved-${item.type}-${item.id}`"
                v-model="selectedItems"
                :value="`${item.type}-${item.id}`"
                type="checkbox"
                class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
              />
              <div class="ml-3 flex-1">
                <div class="flex items-center">
                  <h5 class="text-sm font-medium text-gray-900">{{ item.description }}</h5>
                  <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                    Approved
                  </span>
                  <span v-if="item.type === 'time_entry'" class="ml-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                    Time Entry
                  </span>
                  <span v-else class="ml-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
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
              </div>
            </div>
            
            <div class="text-right">
              <div class="text-sm font-medium text-gray-900">${{ item.total_amount }}</div>
              <div v-if="item.started_at" class="text-xs text-gray-500">
                {{ formatDate(item.started_at) }}
              </div>
              <div v-else-if="item.created_at" class="text-xs text-gray-500">
                {{ formatDate(item.created_at) }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Unapproved Items Section -->
    <div v-if="showUnapproved && unapprovedItems.length > 0">
      <h4 class="text-sm font-medium text-gray-900 mb-3 flex items-center">
        <ExclamationTriangleIcon class="h-4 w-4 text-yellow-500 mr-1" />
        Items Requiring Approval ({{ unapprovedItems.length }})
      </h4>
      
      <div class="space-y-2">
        <div
          v-for="item in unapprovedItems"
          :key="`${item.type}-${item.id}`"
          class="border border-yellow-200 bg-yellow-50 rounded-lg p-3 opacity-60"
        >
          <div class="flex items-start justify-between">
            <div class="flex items-start">
              <div class="mt-1 h-4 w-4 flex items-center justify-center">
                <ExclamationTriangleIcon class="h-4 w-4 text-yellow-500" />
              </div>
              <div class="ml-3 flex-1">
                <div class="flex items-center">
                  <h5 class="text-sm font-medium text-gray-700">{{ item.description }}</h5>
                  <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                    Needs Approval
                  </span>
                  <span v-if="item.type === 'time_entry'" class="ml-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700">
                    Time Entry
                  </span>
                  <span v-else class="ml-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700">
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
              </div>
            </div>
            
            <div class="text-right">
              <div class="text-sm font-medium text-gray-700">${{ item.total_amount }}</div>
              <div v-if="item.started_at" class="text-xs text-gray-500">
                {{ formatDate(item.started_at) }}
              </div>
              <div v-else-if="item.created_at" class="text-xs text-gray-500">
                {{ formatDate(item.created_at) }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <div v-if="approvedItems.length === 0 && unapprovedItems.length === 0 && !loading" class="text-center py-8">
      <DocumentIcon class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">No unbilled items</h3>
      <p class="mt-1 text-sm text-gray-500">
        All items for this account have been billed or there are no billable items.
      </p>
    </div>

    <!-- Loading state -->
    <div v-if="loading" class="flex justify-center py-8">
      <svg class="animate-spin h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { 
  CheckCircleIcon, 
  ExclamationTriangleIcon,
  DocumentIcon 
} from '@heroicons/vue/24/outline'

const props = defineProps({
  accountId: {
    type: String,
    required: true
  },
  accountName: {
    type: String,
    default: ''
  },
  data: {
    type: Object,
    default: () => ({
      approved: { time_entries: [], ticket_addons: [] },
      unapproved: { time_entries: [], ticket_addons: [] },
      totals: { approved_count: 0, pending_count: 0, approved_amount: 0, pending_amount: 0 }
    })
  },
  loading: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:selectedItems', 'launchApprovalWizard'])

// Reactive state
const selectedItems = ref([])
const showUnapproved = ref(false)

// Computed properties
const approvedItems = computed(() => {
  if (!props.data?.approved) return []
  return [
    ...(props.data.approved.time_entries || []),
    ...(props.data.approved.ticket_addons || [])
  ].sort((a, b) => new Date(b.started_at || b.created_at) - new Date(a.started_at || a.created_at))
})

const unapprovedItems = computed(() => {
  if (!props.data?.unapproved) return []
  return [
    ...(props.data.unapproved.time_entries || []),
    ...(props.data.unapproved.ticket_addons || [])
  ].sort((a, b) => new Date(b.started_at || b.created_at) - new Date(a.started_at || a.created_at))
})

const totals = computed(() => {
  return props.data?.totals || {
    approved_count: 0,
    pending_count: 0,
    approved_amount: 0,
    pending_amount: 0
  }
})

const hasSelectedItems = computed(() => selectedItems.value.length > 0)

// Methods
const selectAll = () => {
  selectedItems.value = approvedItems.value.map(item => `${item.type}-${item.id}`)
}

const clearSelection = () => {
  selectedItems.value = []
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount || 0)
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

// Watch for selectedItems changes and emit to parent
watch(selectedItems, (newValue) => {
  // Convert selected items back to structured format for parent
  const selected = {
    time_entries: [],
    ticket_addons: []
  }
  
  newValue.forEach(itemId => {
    const [type, id] = itemId.split('-')
    const item = approvedItems.value.find(i => i.type === type && i.id === id)
    if (item) {
      if (type === 'time_entry') {
        selected.time_entries.push(item)
      } else if (type === 'ticket_addon') {
        selected.ticket_addons.push(item)
      }
    }
  })
  
  emit('update:selectedItems', selected)
}, { deep: true })

// Auto-select all approved items when data loads
watch(() => props.data, (newData) => {
  if (newData?.approved && selectedItems.value.length === 0) {
    // Auto-select all approved items
    selectAll()
  }
}, { immediate: true })
</script>