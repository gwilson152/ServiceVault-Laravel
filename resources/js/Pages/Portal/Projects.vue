<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between py-6">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Projects</h1>
            <p class="text-sm text-gray-600 mt-1">View your active and completed projects</p>
          </div>
          
          <div class="flex items-center space-x-4">
            <div class="flex items-center space-x-2">
              <label class="text-sm text-gray-600">Show:</label>
              <select 
                v-model="statusFilter"
                @change="loadProjects"
                class="border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="">All Projects</option>
                <option value="active">Active Projects</option>
                <option value="completed">Completed Projects</option>
                <option value="on_hold">On Hold</option>
              </select>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Loading State -->
      <div v-if="loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <p class="mt-2 text-gray-600">Loading projects...</p>
      </div>
      
      <!-- Empty State -->
      <div v-else-if="projects.length === 0" class="text-center py-12">
        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No projects found</h3>
        <p class="text-gray-600 mb-4">
          {{ statusFilter ? `No ${statusFilter.replace('_', ' ')} projects` : 'No projects have been created yet' }}
        </p>
        <Link 
          :href="route('portal.index')"
          class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors"
        >
          Back to Dashboard
        </Link>
      </div>
      
      <!-- Projects Grid -->
      <div v-else>
        <!-- Summary Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
              <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Active Projects</p>
                <p class="text-2xl font-semibold text-gray-900">{{ summary.active || 0 }}</p>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
              <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Completed</p>
                <p class="text-2xl font-semibold text-gray-900">{{ summary.completed || 0 }}</p>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
              <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Total Hours</p>
                <p class="text-2xl font-semibold text-gray-900">{{ formatHours(summary.total_hours) }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Projects List -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div 
            v-for="project in projects" 
            :key="project.id"
            class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow"
          >
            <!-- Project Header -->
            <div class="p-6 border-b border-gray-200">
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <div class="flex items-center space-x-3 mb-2">
                    <h3 class="text-lg font-semibold text-gray-900">{{ project.name }}</h3>
                    <span :class="getStatusClasses(project.status)" class="px-2 py-1 rounded-full text-xs font-medium">
                      {{ formatStatus(project.status) }}
                    </span>
                  </div>
                  <p v-if="project.description" class="text-sm text-gray-600 mb-3 line-clamp-2">
                    {{ project.description }}
                  </p>
                  <div class="flex items-center text-sm text-gray-500 space-x-4">
                    <span v-if="project.start_date">Started {{ formatDate(project.start_date) }}</span>
                    <span v-if="project.due_date" :class="getDueDateClasses(project.due_date)">
                      Due {{ formatDate(project.due_date) }}
                    </span>
                  </div>
                </div>
                
                <div class="ml-4 flex-shrink-0">
                  <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                  </div>
                </div>
              </div>
            </div>

            <!-- Project Stats -->
            <div class="p-6">
              <div class="grid grid-cols-3 gap-4 mb-4">
                <div class="text-center">
                  <p class="text-lg font-semibold text-gray-900">{{ project.tickets_count || 0 }}</p>
                  <p class="text-xs text-gray-500">Tickets</p>
                </div>
                <div class="text-center">
                  <p class="text-lg font-semibold text-gray-900">{{ formatHours(project.total_hours) }}</p>
                  <p class="text-xs text-gray-500">Hours Logged</p>
                </div>
                <div class="text-center">
                  <p class="text-lg font-semibold text-gray-900">${{ formatCurrency(project.total_cost) }}</p>
                  <p class="text-xs text-gray-500">Total Cost</p>
                </div>
              </div>

              <!-- Progress Bar -->
              <div v-if="project.progress !== undefined" class="mb-4">
                <div class="flex justify-between text-sm text-gray-600 mb-1">
                  <span>Progress</span>
                  <span>{{ Math.round(project.progress) }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div 
                    :style="{ width: project.progress + '%' }"
                    class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                  ></div>
                </div>
              </div>

              <!-- Recent Activity -->
              <div v-if="project.recent_activity?.length" class="mb-4">
                <h4 class="text-sm font-medium text-gray-900 mb-2">Recent Activity</h4>
                <div class="space-y-2">
                  <div 
                    v-for="activity in project.recent_activity.slice(0, 3)" 
                    :key="activity.id"
                    class="flex items-start space-x-2"
                  >
                    <div class="w-1.5 h-1.5 bg-blue-500 rounded-full mt-2 flex-shrink-0"></div>
                    <div class="flex-1 min-w-0">
                      <p class="text-xs text-gray-600">{{ activity.description }}</p>
                      <p class="text-xs text-gray-400">{{ formatDateTime(activity.created_at) }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="flex items-center space-x-3">
                <button
                  @click="viewProjectDetails(project)"
                  class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2 px-3 rounded-md transition-colors"
                >
                  View Details
                </button>
                <Link
                  :href="route('portal.time-tracking', { project: project.id })"
                  class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium py-2 px-3 rounded-md text-center transition-colors"
                >
                  View Time
                </Link>
              </div>
            </div>
          </div>
        </div>

        <!-- Load More Button -->
        <div v-if="hasMore" class="mt-8 text-center">
          <button
            @click="loadMoreProjects"
            :disabled="loadingMore"
            class="bg-white hover:bg-gray-50 text-gray-700 font-medium py-2 px-6 border border-gray-300 rounded-lg transition-colors disabled:opacity-50"
          >
            {{ loadingMore ? 'Loading...' : 'Load More Projects' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Project Details Modal -->
    <ProjectDetailsModal 
      v-if="selectedProject"
      :project="selectedProject"
      @closed="selectedProject = null"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ProjectDetailsModal from '@/Components/Portal/ProjectDetailsModal.vue'
import axios from 'axios'

// Define persistent layout
defineOptions({
  layout: AppLayout
})

// Access user data from Inertia page props
const page = usePage()
const user = computed(() => page.props.auth?.user)

// State
const loading = ref(true)
const loadingMore = ref(false)
const projects = ref([])
const summary = ref({})
const statusFilter = ref('')
const hasMore = ref(false)
const currentPage = ref(1)
const selectedProject = ref(null)

// Methods
const loadProjects = async (append = false) => {
  if (append) {
    loadingMore.value = true
  } else {
    loading.value = true
    currentPage.value = 1
  }
  
  try {
    const params = new URLSearchParams({
      page: currentPage.value,
      per_page: 12
    })
    
    if (statusFilter.value) {
      params.append('status', statusFilter.value)
    }
    
    const response = await axios.get(`/api/portal/projects?${params}`)
    const data = response.data
    
    if (append) {
      projects.value.push(...(data.data || []))
    } else {
      projects.value = data.data || []
    }
    
    summary.value = data.summary || {}
    hasMore.value = data.has_more || false
    
  } catch (error) {
    console.error('Failed to load projects:', error)
    if (!append) {
      projects.value = []
      summary.value = {}
    }
  } finally {
    loading.value = false
    loadingMore.value = false
  }
}

const loadMoreProjects = () => {
  currentPage.value++
  loadProjects(true)
}

const formatHours = (hours) => {
  if (!hours) return '0h'
  return `${Math.round(hours)}h`
}

const formatCurrency = (amount) => {
  if (!amount) return '0.00'
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount)
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatDateTime = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatStatus = (status) => {
  return status?.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) || 'Unknown'
}

const getStatusClasses = (status) => {
  const statusMap = {
    'active': 'bg-green-100 text-green-800',
    'completed': 'bg-blue-100 text-blue-800',
    'on_hold': 'bg-yellow-100 text-yellow-800',
    'cancelled': 'bg-red-100 text-red-800',
    'planning': 'bg-purple-100 text-purple-800'
  }
  
  return statusMap[status] || 'bg-gray-100 text-gray-800'
}

const getDueDateClasses = (dueDateString) => {
  if (!dueDateString) return ''
  
  const dueDate = new Date(dueDateString)
  const now = new Date()
  const diffDays = Math.ceil((dueDate - now) / (1000 * 60 * 60 * 24))
  
  if (diffDays < 0) {
    return 'text-red-600 font-medium' // Overdue
  } else if (diffDays <= 7) {
    return 'text-orange-600 font-medium' // Due soon
  } else {
    return 'text-gray-500' // Normal
  }
}

const viewProjectDetails = (project) => {
  selectedProject.value = project
}

// Lifecycle
onMounted(() => {
  loadProjects()
})
</script>