<template>
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Ticket Filters
                    </h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">
                        Quick filters and saved views
                    </p>
                </div>
                <button
                    @click="showSaveFilterModal = true"
                    class="inline-flex items-center px-2 py-1 text-xs font-medium text-indigo-700 bg-indigo-100 rounded hover:bg-indigo-200"
                >
                    <BookmarkIcon class="h-3 w-3 mr-1" />
                    Save View
                </button>
            </div>
        </div>

        <div class="px-4 py-5 sm:p-6">
            <!-- Quick Filters -->
            <div class="space-y-4">
                <div>
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Quick Filters</h4>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="filter in quickFilters"
                            :key="filter.key"
                            @click="applyQuickFilter(filter)"
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium transition-colors"
                            :class="activeQuickFilter === filter.key 
                                ? 'bg-indigo-100 text-indigo-800 border border-indigo-200' 
                                : 'bg-gray-100 text-gray-800 hover:bg-gray-200 border border-gray-200'"
                        >
                            <component :is="filter.icon" class="h-3 w-3 mr-1" />
                            {{ filter.label }}
                            <span v-if="filter.count !== undefined" class="ml-1 px-1 py-0.5 bg-white rounded text-xs">
                                {{ filter.count }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Saved Views -->
                <div v-if="savedViews.length > 0">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Saved Views</h4>
                    <div class="space-y-1">
                        <div
                            v-for="view in savedViews"
                            :key="view.id"
                            class="flex items-center justify-between p-2 rounded hover:bg-gray-50 group"
                        >
                            <button
                                @click="applySavedView(view)"
                                class="flex-1 text-left text-sm"
                                :class="activeSavedView === view.id ? 'text-indigo-600 font-medium' : 'text-gray-700'"
                            >
                                <div class="flex items-center">
                                    <FolderIcon class="h-4 w-4 mr-2 text-gray-400" />
                                    {{ view.name }}
                                </div>
                                <div class="text-xs text-gray-500 ml-6">
                                    {{ view.description || 'Custom filter view' }}
                                </div>
                            </button>
                            <div class="flex items-center space-x-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button
                                    @click="editSavedView(view)"
                                    class="p-1 text-gray-400 hover:text-gray-600"
                                    title="Edit view"
                                >
                                    <PencilIcon class="h-3 w-3" />
                                </button>
                                <button
                                    @click="deleteSavedView(view)"
                                    class="p-1 text-gray-400 hover:text-red-600"
                                    title="Delete view"
                                >
                                    <TrashIcon class="h-3 w-3" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Current Filters Summary -->
                <div v-if="currentFiltersCount > 0" class="mt-4 pt-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-600">
                            {{ currentFiltersCount }} filter{{ currentFiltersCount !== 1 ? 's' : '' }} active
                        </div>
                        <button
                            @click="clearAllFilters"
                            class="text-xs text-red-600 hover:text-red-700"
                        >
                            Clear All
                        </button>
                    </div>
                    <div class="mt-2 flex flex-wrap gap-1">
                        <span
                            v-for="(value, key) in activeFilters"
                            :key="key"
                            class="inline-flex items-center px-2 py-1 rounded text-xs bg-blue-100 text-blue-800"
                        >
                            {{ formatFilterLabel(key, value) }}
                            <button
                                @click="removeFilter(key)"
                                class="ml-1 text-blue-600 hover:text-blue-800"
                            >
                                <XMarkIcon class="h-3 w-3" />
                            </button>
                        </span>
                    </div>
                </div>

                <!-- Advanced Filters Toggle -->
                <div class="mt-4">
                    <button
                        @click="showAdvancedFilters = !showAdvancedFilters"
                        class="flex items-center text-sm text-indigo-600 hover:text-indigo-700"
                    >
                        <ChevronDownIcon 
                            class="h-4 w-4 mr-1 transition-transform"
                            :class="showAdvancedFilters ? 'rotate-180' : ''"
                        />
                        Advanced Filters
                    </button>

                    <div v-if="showAdvancedFilters" class="mt-3 space-y-3">
                        <!-- Status Filter -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                            <select
                                v-model="advancedFilters.status"
                                @change="applyAdvancedFilters"
                                class="block w-full text-sm rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">All Statuses</option>
                                <option value="open">Open</option>
                                <option value="in_progress">In Progress</option>
                                <option value="waiting_customer">Waiting Customer</option>
                                <option value="resolved">Resolved</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>

                        <!-- Priority Filter -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Priority</label>
                            <select
                                v-model="advancedFilters.priority"
                                @change="applyAdvancedFilters"
                                class="block w-full text-sm rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">All Priorities</option>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>

                        <!-- Assigned To Filter -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Assigned To</label>
                            <select
                                v-model="advancedFilters.assigned_to"
                                @change="applyAdvancedFilters"
                                class="block w-full text-sm rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Anyone</option>
                                <option value="me">Me</option>
                                <option value="unassigned">Unassigned</option>
                                <option 
                                    v-for="user in assignableUsers" 
                                    :key="user.id" 
                                    :value="user.id"
                                >
                                    {{ user.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Date Range Filter -->
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Created After</label>
                                <input
                                    v-model="advancedFilters.created_after"
                                    @change="applyAdvancedFilters"
                                    type="date"
                                    class="block w-full text-sm rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Created Before</label>
                                <input
                                    v-model="advancedFilters.created_before"
                                    @change="applyAdvancedFilters"
                                    type="date"
                                    class="block w-full text-sm rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Filter Modal -->
        <SaveFilterModal
            v-if="showSaveFilterModal"
            :current-filters="activeFilters"
            @close="showSaveFilterModal = false"
            @saved="handleFilterSaved"
        />
    </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted, watch } from 'vue'
import { 
    BookmarkIcon, 
    FolderIcon, 
    PencilIcon, 
    TrashIcon, 
    XMarkIcon, 
    ChevronDownIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    UserIcon,
    DocumentTextIcon,
    CheckCircleIcon
} from '@heroicons/vue/24/outline'
import SaveFilterModal from '@/Components/Modals/SaveFilterModal.vue'
import axios from 'axios'

const props = defineProps({
    widgetData: { type: [Object, Array], default: null },
    widgetConfig: { type: Object, default: () => ({}) },
    accountContext: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['filters-changed'])

const showSaveFilterModal = ref(false)
const showAdvancedFilters = ref(false)
const activeQuickFilter = ref(null)
const activeSavedView = ref(null)
const assignableUsers = ref([])
const savedViews = ref([])

const activeFilters = ref({})
const advancedFilters = reactive({
    status: '',
    priority: '',
    assigned_to: '',
    created_after: '',
    created_before: ''
})

// Quick filters configuration
const quickFilters = ref([
    {
        key: 'my_tickets',
        label: 'My Tickets',
        icon: UserIcon,
        filters: { assigned_to: 'me' },
        count: 0
    },
    {
        key: 'open_tickets',
        label: 'Open',
        icon: DocumentTextIcon,
        filters: { status: 'open' },
        count: 0
    },
    {
        key: 'in_progress',
        label: 'In Progress',
        icon: ClockIcon,
        filters: { status: 'in_progress' },
        count: 0
    },
    {
        key: 'high_priority',
        label: 'High Priority',
        icon: ExclamationTriangleIcon,
        filters: { priority: ['high', 'urgent'] },
        count: 0
    },
    {
        key: 'unassigned',
        label: 'Unassigned',
        icon: UserIcon,
        filters: { assigned_to: 'unassigned' },
        count: 0
    },
    {
        key: 'overdue',
        label: 'Overdue',
        icon: ExclamationTriangleIcon,
        filters: { overdue: true },
        count: 0
    },
    {
        key: 'resolved',
        label: 'Resolved',
        icon: CheckCircleIcon,
        filters: { status: 'resolved' },
        count: 0
    }
])

// Computed properties
const currentFiltersCount = computed(() => {
    return Object.keys(activeFilters.value).filter(key => {
        const value = activeFilters.value[key]
        return value && value !== '' && !(Array.isArray(value) && value.length === 0)
    }).length
})

// Load data
const loadAssignableUsers = async () => {
    try {
        const response = await axios.get('/api/users/assignable')
        assignableUsers.value = response.data.data || []
    } catch (error) {
        console.error('Failed to load assignable users:', error)
    }
}

const loadSavedViews = async () => {
    try {
        // This would be a real API endpoint for saved filter views
        // For now, simulate with localStorage
        const saved = localStorage.getItem('ticket_filter_views')
        savedViews.value = saved ? JSON.parse(saved) : []
    } catch (error) {
        console.error('Failed to load saved views:', error)
    }
}

const loadFilterCounts = async () => {
    try {
        // This would fetch actual counts from the API
        const response = await axios.get('/api/tickets/filter-counts', {
            params: props.accountContext
        })
        
        const counts = response.data.counts || {}
        quickFilters.value.forEach(filter => {
            filter.count = counts[filter.key] || 0
        })
    } catch (error) {
        console.error('Failed to load filter counts:', error)
    }
}

// Filter methods
const applyQuickFilter = (filter) => {
    activeQuickFilter.value = activeQuickFilter.value === filter.key ? null : filter.key
    activeSavedView.value = null
    
    if (activeQuickFilter.value) {
        activeFilters.value = { ...filter.filters }
    } else {
        activeFilters.value = {}
    }
    
    emitFiltersChanged()
}

const applyAdvancedFilters = () => {
    activeQuickFilter.value = null
    activeSavedView.value = null
    
    // Convert advanced filters to active filters
    const filters = {}
    Object.keys(advancedFilters).forEach(key => {
        if (advancedFilters[key] && advancedFilters[key] !== '') {
            filters[key] = advancedFilters[key]
        }
    })
    
    activeFilters.value = filters
    emitFiltersChanged()
}

const applySavedView = (view) => {
    activeSavedView.value = view.id
    activeQuickFilter.value = null
    activeFilters.value = { ...view.filters }
    
    // Update advanced filters to match
    Object.keys(advancedFilters).forEach(key => {
        advancedFilters[key] = view.filters[key] || ''
    })
    
    emitFiltersChanged()
}

const clearAllFilters = () => {
    activeFilters.value = {}
    activeQuickFilter.value = null
    activeSavedView.value = null
    
    Object.keys(advancedFilters).forEach(key => {
        advancedFilters[key] = ''
    })
    
    emitFiltersChanged()
}

const removeFilter = (key) => {
    delete activeFilters.value[key]
    advancedFilters[key] = ''
    
    // Check if this matches any quick filter
    const matchingQuickFilter = quickFilters.value.find(qf => 
        JSON.stringify(qf.filters) === JSON.stringify(activeFilters.value)
    )
    
    if (!matchingQuickFilter) {
        activeQuickFilter.value = null
    }
    
    emitFiltersChanged()
}

const emitFiltersChanged = () => {
    emit('filters-changed', activeFilters.value)
}

// Saved view methods
const handleFilterSaved = (savedView) => {
    savedViews.value.push(savedView)
    
    // Save to localStorage (in real app, this would be API call)
    localStorage.setItem('ticket_filter_views', JSON.stringify(savedViews.value))
    
    showSaveFilterModal.value = false
}

const editSavedView = (view) => {
    // Open edit modal - not implemented for this example
    console.log('Edit view:', view)
}

const deleteSavedView = (view) => {
    if (confirm(`Delete the saved view "${view.name}"?`)) {
        const index = savedViews.value.findIndex(v => v.id === view.id)
        if (index !== -1) {
            savedViews.value.splice(index, 1)
            localStorage.setItem('ticket_filter_views', JSON.stringify(savedViews.value))
        }
        
        if (activeSavedView.value === view.id) {
            activeSavedView.value = null
        }
    }
}

// Helper methods
const formatFilterLabel = (key, value) => {
    const labels = {
        status: 'Status',
        priority: 'Priority',
        assigned_to: 'Assigned',
        created_after: 'After',
        created_before: 'Before'
    }
    
    let displayValue = value
    if (key === 'assigned_to' && value === 'me') {
        displayValue = 'Me'
    } else if (Array.isArray(value)) {
        displayValue = value.join(', ')
    }
    
    return `${labels[key] || key}: ${displayValue}`
}

onMounted(() => {
    loadAssignableUsers()
    loadSavedViews()
    loadFilterCounts()
})

// Watch for external filter changes
watch(() => props.widgetData, (newData) => {
    if (newData?.activeFilters) {
        activeFilters.value = { ...newData.activeFilters }
    }
}, { immediate: true })
</script>