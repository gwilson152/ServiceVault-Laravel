<template>
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Recent Time Entries
                    </h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">
                        Latest time entries across tickets
                    </p>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                          :class="pendingCount > 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'">
                        {{ pendingCount }} Pending
                    </span>
                    <button
                        @click="refreshData"
                        :disabled="loading"
                        class="inline-flex items-center p-1 border border-transparent rounded-full text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                    >
                        <ArrowPathIcon class="h-4 w-4" :class="{ 'animate-spin': loading }" />
                    </button>
                </div>
            </div>
        </div>

        <div class="px-4 py-5 sm:p-6">
            <!-- Loading State -->
            <div v-if="loading && timeEntries.length === 0" class="flex justify-center py-4">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-600"></div>
            </div>

            <!-- No Time Entries -->
            <div v-else-if="timeEntries.length === 0" class="text-center py-6">
                <ClockIcon class="mx-auto h-8 w-8 text-gray-400" />
                <h4 class="mt-2 text-sm font-medium text-gray-900">No time entries</h4>
                <p class="mt-1 text-sm text-gray-500">Time entries will appear here when created.</p>
            </div>

            <!-- Time Entries List -->
            <div v-else class="space-y-3">
                <div
                    v-for="entry in timeEntries"
                    :key="entry.id"
                    class="flex items-start p-3 rounded-lg border transition-colors"
                    :class="getEntryClasses(entry)"
                >
                    <div class="flex-shrink-0 mr-3">
                        <div class="h-2 w-2 rounded-full mt-2"
                             :class="getStatusDotClasses(entry.status)">
                        </div>
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center space-x-2">
                                    <h4 class="text-sm font-medium text-gray-900 truncate">
                                        {{ entry.ticket?.title || 'No Ticket' }}
                                    </h4>
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                        :class="getStatusClasses(entry.status)"
                                    >
                                        {{ formatStatus(entry.status) }}
                                    </span>
                                </div>
                                
                                <div class="mt-1 text-xs text-gray-500">
                                    <span v-if="entry.ticket?.ticket_number" class="mr-3">
                                        {{ entry.ticket.ticket_number }}
                                    </span>
                                    <span class="mr-3">{{ formatDate(entry.date) }}</span>
                                    <span v-if="entry.user?.name">by {{ entry.user.name }}</span>
                                </div>
                                
                                <p v-if="entry.description" class="mt-1 text-sm text-gray-700 truncate">
                                    {{ entry.description }}
                                </p>
                                
                                <div v-if="entry.rejection_reason" class="mt-2 p-2 bg-red-50 border border-red-200 rounded text-xs text-red-700">
                                    <strong>Rejected:</strong> {{ entry.rejection_reason }}
                                </div>
                            </div>
                            
                            <div class="flex items-center space-x-4 ml-4">
                                <div class="text-right">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ formatDuration(entry.duration) }}
                                    </div>
                                    <div v-if="entry.billing_rate" class="text-xs text-gray-500">
                                        {{ formatCurrency(entry.calculated_cost) }}
                                    </div>
                                </div>
                                
                                <!-- Actions for pending entries -->
                                <div v-if="entry.status === 'pending' && canApprove" class="flex space-x-1">
                                    <button
                                        @click="approveEntry(entry)"
                                        :disabled="processing === entry.id"
                                        class="inline-flex items-center px-2 py-1 text-xs font-medium text-green-700 bg-green-100 rounded hover:bg-green-200 disabled:opacity-50"
                                        title="Approve entry"
                                    >
                                        <CheckIcon class="h-3 w-3" />
                                    </button>
                                    <button
                                        @click="rejectEntry(entry)"
                                        :disabled="processing === entry.id"
                                        class="inline-flex items-center px-2 py-1 text-xs font-medium text-red-700 bg-red-100 rounded hover:bg-red-200 disabled:opacity-50"
                                        title="Reject entry"
                                    >
                                        <XMarkIcon class="h-3 w-3" />
                                    </button>
                                </div>
                                
                                <!-- View/Edit button for own entries -->
                                <button
                                    v-if="entry.user_id === currentUserId && entry.status === 'pending'"
                                    @click="editEntry(entry)"
                                    class="inline-flex items-center p-1 text-gray-400 hover:text-gray-600"
                                    title="Edit entry"
                                >
                                    <PencilIcon class="h-3 w-3" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Load More Button -->
                <div v-if="hasMore" class="text-center pt-4">
                    <button
                        @click="loadMore"
                        :disabled="loading"
                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-indigo-600 hover:text-indigo-700 disabled:opacity-50"
                    >
                        <span v-if="loading">Loading...</span>
                        <span v-else>Load More</span>
                    </button>
                </div>
            </div>

            <!-- Summary Stats -->
            <div v-if="stats" class="mt-4 pt-4 border-t border-gray-200">
                <div class="grid grid-cols-3 gap-4 text-center text-sm">
                    <div>
                        <div class="text-gray-500">Today</div>
                        <div class="mt-1 font-semibold text-gray-900">
                            {{ formatDuration(stats.today_duration) }}
                        </div>
                    </div>
                    <div>
                        <div class="text-gray-500">This Week</div>
                        <div class="mt-1 font-semibold text-gray-900">
                            {{ formatDuration(stats.week_duration) }}
                        </div>
                    </div>
                    <div>
                        <div class="text-gray-500">Pending Value</div>
                        <div class="mt-1 font-semibold text-yellow-600">
                            {{ formatCurrency(stats.pending_value) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rejection Modal -->
        <RejectTimeEntryModal
            v-if="showRejectModal"
            :time-entry="selectedEntry"
            @close="closeRejectModal"
            @rejected="handleEntryRejected"
        />
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { 
    ClockIcon, 
    ArrowPathIcon, 
    CheckIcon, 
    XMarkIcon, 
    PencilIcon 
} from '@heroicons/vue/24/outline'
import RejectTimeEntryModal from '@/Components/Modals/RejectTimeEntryModal.vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
    widgetData: { type: [Object, Array], default: null },
    widgetConfig: { type: Object, default: () => ({}) },
    accountContext: { type: Object, default: () => ({}) }
})

const page = usePage()
const currentUserId = computed(() => page.props.auth.user?.id)

const loading = ref(false)
const processing = ref(null)
const timeEntries = ref([])
const stats = ref(null)
const hasMore = ref(false)
const currentPage = ref(1)
const showRejectModal = ref(false)
const selectedEntry = ref(null)

// Computed values
const pendingCount = computed(() => {
    return timeEntries.value.filter(entry => entry.status === 'pending').length
})

const canApprove = computed(() => {
    const user = page.props.auth.user
    return user?.permissions?.some(p => ['time_entries.approve', 'admin.write'].includes(p))
})

// Load time entries
const loadTimeEntries = async (page = 1) => {
    loading.value = true
    try {
        const params = {
            page,
            per_page: 10,
            include: 'user,ticket,billingRate',
            sort: '-created_at',
            ...props.accountContext
        }
        
        const response = await axios.get('/api/time-entries', { params })
        const data = response.data
        
        if (page === 1) {
            timeEntries.value = data.data || []
        } else {
            timeEntries.value.push(...(data.data || []))
        }
        
        hasMore.value = data.meta?.current_page < data.meta?.last_page
        currentPage.value = data.meta?.current_page || 1
        
        // Load stats if first page
        if (page === 1) {
            loadStats()
        }
    } catch (error) {
        console.error('Failed to load time entries:', error)
    } finally {
        loading.value = false
    }
}

const loadStats = async () => {
    try {
        const response = await axios.get('/api/time-entries/stats/recent', {
            params: props.accountContext
        })
        stats.value = response.data
    } catch (error) {
        console.error('Failed to load time entry stats:', error)
    }
}

const refreshData = () => {
    currentPage.value = 1
    loadTimeEntries(1)
}

const loadMore = () => {
    if (hasMore.value && !loading.value) {
        loadTimeEntries(currentPage.value + 1)
    }
}

// Entry actions
const approveEntry = async (entry) => {
    if (processing.value) return
    
    processing.value = entry.id
    try {
        await axios.post(`/api/time-entries/${entry.id}/approve`)
        
        // Update local entry
        const index = timeEntries.value.findIndex(e => e.id === entry.id)
        if (index !== -1) {
            timeEntries.value[index].status = 'approved'
        }
        
        loadStats() // Refresh stats
    } catch (error) {
        console.error('Failed to approve time entry:', error)
        alert('Failed to approve time entry. Please try again.')
    } finally {
        processing.value = null
    }
}

const rejectEntry = (entry) => {
    selectedEntry.value = entry
    showRejectModal.value = true
}

const closeRejectModal = () => {
    showRejectModal.value = false
    selectedEntry.value = null
}

const handleEntryRejected = (updatedEntry) => {
    const index = timeEntries.value.findIndex(e => e.id === updatedEntry.id)
    if (index !== -1) {
        timeEntries.value[index] = updatedEntry
    }
    closeRejectModal()
    loadStats() // Refresh stats
}

const editEntry = (entry) => {
    // Navigate to edit page or open edit modal
    // This would depend on your routing setup
    console.log('Edit entry:', entry)
}

// Helper functions
const getEntryClasses = (entry) => {
    const classes = {
        pending: 'border-yellow-200 bg-yellow-50',
        approved: 'border-green-200 bg-green-50',
        rejected: 'border-red-200 bg-red-50'
    }
    return classes[entry.status] || 'border-gray-200'
}

const getStatusDotClasses = (status) => {
    const classes = {
        pending: 'bg-yellow-400 animate-pulse',
        approved: 'bg-green-400',
        rejected: 'bg-red-400'
    }
    return classes[status] || 'bg-gray-400'
}

const getStatusClasses = (status) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800',
        approved: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800'
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatStatus = (status) => {
    return status.charAt(0).toUpperCase() + status.slice(1)
}

const formatDuration = (seconds) => {
    if (!seconds) return '0:00'
    
    const hours = Math.floor(seconds / 3600)
    const minutes = Math.floor((seconds % 3600) / 60)
    
    if (hours > 0) {
        return `${hours}:${minutes.toString().padStart(2, '0')}h`
    }
    return `${minutes}:${(seconds % 60).toString().padStart(2, '0')}m`
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: new Date(date).getFullYear() !== new Date().getFullYear() ? 'numeric' : undefined
    })
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount || 0)
}

onMounted(() => {
    loadTimeEntries()
})
</script>