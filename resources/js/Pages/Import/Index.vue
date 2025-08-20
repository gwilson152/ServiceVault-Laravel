<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex justify-between items-center">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Import Management</h1>
            <p class="mt-1 text-sm text-gray-600">
              Import data from external systems with PostgreSQL database connections
            </p>
          </div>
          <button
            @click="showCreateProfile = true"
            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <ArrowDownTrayIcon class="-ml-1 mr-2 h-4 w-4" />
            Create Import Profile
          </button>
        </div>
      </div>

      <!-- Import Profiles Section -->
      <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-lg font-medium text-gray-900">Import Profiles</h2>
          <p class="mt-1 text-sm text-gray-600">
            Manage database connections and import configurations
          </p>
        </div>
        
        <div class="p-6">
          <div v-if="profilesLoading" class="text-center py-8">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
            <p class="mt-2 text-sm text-gray-600">Loading import profiles...</p>
          </div>
          
          <div v-else-if="profiles?.length === 0" class="text-center py-8">
            <ArrowDownTrayIcon class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-2 text-sm font-medium text-gray-900">No import profiles</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by creating your first import profile.</p>
            <div class="mt-6">
              <button
                @click="showCreateProfile = true"
                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"
              >
                <ArrowDownTrayIcon class="-ml-1 mr-2 h-4 w-4" />
                Create Import Profile
              </button>
            </div>
          </div>
          
          <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div
              v-for="profile in profiles"
              :key="profile.id"
              class="relative group bg-white border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow"
            >
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                  <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                      <CircleStackIcon class="w-6 h-6 text-indigo-600" />
                    </div>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">{{ profile.name }}</p>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">{{ profile.type }}</p>
                  </div>
                </div>
                
                <!-- Actions dropdown -->
                <div class="relative">
                  <button
                    @click="toggleProfileMenu(profile.id)"
                    class="p-1 rounded-full text-gray-400 hover:text-gray-600 hover:bg-gray-100"
                  >
                    <EllipsisVerticalIcon class="w-5 h-5" />
                  </button>
                  
                  <div
                    v-if="activeProfileMenu === profile.id"
                    class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10"
                  >
                    <div class="py-1">
                      <button
                        @click="previewImport(profile)"
                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                      >
                        <EyeIcon class="inline w-4 h-4 mr-2" />
                        Preview Data
                      </button>
                      <button
                        @click="executeImport(profile)"
                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                      >
                        <PlayIcon class="inline w-4 h-4 mr-2" />
                        Execute Import
                      </button>
                      <button
                        @click="editProfile(profile)"
                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                      >
                        <PencilIcon class="inline w-4 h-4 mr-2" />
                        Edit Profile
                      </button>
                      <button
                        @click="deleteProfile(profile)"
                        class="w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-red-50"
                      >
                        <TrashIcon class="inline w-4 h-4 mr-2" />
                        Delete Profile
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="mt-4">
                <div class="flex items-center text-xs text-gray-500 space-x-4">
                  <span class="flex items-center">
                    <ServerIcon class="w-3 h-3 mr-1" />
                    {{ profile.host }}:{{ profile.port }}
                  </span>
                  <span class="flex items-center">
                    <CircleStackIcon class="w-3 h-3 mr-1" />
                    {{ profile.database }}
                  </span>
                </div>
                <p v-if="profile.description" class="mt-2 text-xs text-gray-600 line-clamp-2">
                  {{ profile.description }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Import Jobs Section -->
      <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-lg font-medium text-gray-900">Recent Import Jobs</h2>
          <p class="mt-1 text-sm text-gray-600">
            Track import job progress and results
          </p>
        </div>
        
        <div class="p-6">
          <div v-if="jobsLoading" class="text-center py-8">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
            <p class="mt-2 text-sm text-gray-600">Loading recent jobs...</p>
          </div>
          
          <div v-else-if="jobs?.length === 0" class="text-center py-8">
            <ClockIcon class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-2 text-sm font-medium text-gray-900">No import jobs</h3>
            <p class="mt-1 text-sm text-gray-500">Import jobs will appear here once you start importing data.</p>
          </div>
          
          <div v-else class="overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Profile</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Records</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Started</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="job in jobs" :key="job.id">
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ job.profile?.name }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="getJobStatusClass(job.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                      {{ job.status }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="w-full bg-gray-200 rounded-full h-2 mr-2">
                        <div
                          :class="getProgressBarClass(job.status)"
                          class="h-2 rounded-full transition-all duration-300"
                          :style="{ width: `${job.progress_percentage || 0}%` }"
                        ></div>
                      </div>
                      <span class="text-xs text-gray-600 min-w-0 whitespace-nowrap">
                        {{ job.progress_percentage || 0 }}%
                      </span>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <div class="space-y-1">
                      <div class="text-xs text-green-600">{{ job.records_imported || 0 }} imported</div>
                      <div v-if="job.records_failed > 0" class="text-xs text-red-600">{{ job.records_failed || 0 }} failed</div>
                      <div v-if="job.records_skipped > 0" class="text-xs text-yellow-600">{{ job.records_skipped || 0 }} skipped</div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ formatDate(job.started_at) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button
                      v-if="job.status === 'running'"
                      @click="cancelJob(job)"
                      class="text-red-600 hover:text-red-900 mr-3"
                    >
                      Cancel
                    </button>
                    <button
                      @click="viewJobDetails(job)"
                      class="text-indigo-600 hover:text-indigo-900"
                    >
                      View Details
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Profile Modal -->
    <ImportProfileModal
      :show="showCreateProfile"
      @close="showCreateProfile = false"
      @saved="handleProfileSaved"
    />
    
    <!-- Preview Modal -->
    <ImportPreviewModal
      :show="showPreview"
      :profile="selectedProfile"
      @close="showPreview = false"
      @execute="handleExecuteFromPreview"
    />
    
    <!-- Job Details Modal -->
    <ImportJobDetailsModal
      :show="showJobDetails"
      :job="selectedJob"
      @close="showJobDetails = false"
    />
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import ImportProfileModal from './Components/ImportProfileModal.vue'
import ImportPreviewModal from './Components/ImportPreviewModal.vue'
import ImportJobDetailsModal from './Components/ImportJobDetailsModal.vue'
import {
  ArrowDownTrayIcon,
  CircleStackIcon,
  EllipsisVerticalIcon,
  EyeIcon,
  PlayIcon,
  PencilIcon,
  TrashIcon,
  ServerIcon,
  ClockIcon,
} from '@heroicons/vue/24/outline'
import { useImportQueries } from '@/Composables/queries/useImportQueries.js'

// Reactive state
const showCreateProfile = ref(false)
const showPreview = ref(false)
const showJobDetails = ref(false)
const selectedProfile = ref(null)
const selectedJob = ref(null)
const activeProfileMenu = ref(null)

// Use import queries composable
const { 
  profiles, 
  profilesLoading, 
  jobs, 
  jobsLoading, 
  refreshProfiles, 
  refreshJobs,
  executeImport: executeImportMutation,
  deleteProfile: deleteProfileMutation,
  cancelJob: cancelJobMutation
} = useImportQueries()

// Load data on mount
onMounted(() => {
  refreshProfiles()
  refreshJobs()
})

// Methods
const toggleProfileMenu = (profileId) => {
  activeProfileMenu.value = activeProfileMenu.value === profileId ? null : profileId
}

const previewImport = (profile) => {
  selectedProfile.value = profile
  showPreview.value = true
  activeProfileMenu.value = null
}

const executeImport = async (profile) => {
  // Direct import execution
  try {
    await executeImportMutation(profile.id)
    refreshJobs()
  } catch (error) {
    console.error('Import execution failed:', error)
  }
  activeProfileMenu.value = null
}

const editProfile = (profile) => {
  selectedProfile.value = profile
  showCreateProfile.value = true
  activeProfileMenu.value = null
}

const deleteProfile = async (profile) => {
  if (confirm(`Are you sure you want to delete the import profile "${profile.name}"?`)) {
    try {
      await deleteProfileMutation(profile.id)
      refreshProfiles()
    } catch (error) {
      console.error('Delete failed:', error)
    }
  }
  activeProfileMenu.value = null
}

const cancelJob = async (job) => {
  try {
    await cancelJobMutation(job.id)
    refreshJobs()
  } catch (error) {
    console.error('Cancel failed:', error)
  }
}

const viewJobDetails = (job) => {
  selectedJob.value = job
  showJobDetails.value = true
}

const handleProfileSaved = () => {
  showCreateProfile.value = false
  selectedProfile.value = null
  refreshProfiles()
}

const handleExecuteFromPreview = () => {
  showPreview.value = false
  executeImport(selectedProfile.value)
  selectedProfile.value = null
}

// Helper methods
const getJobStatusClass = (status) => {
  switch (status) {
    case 'completed':
      return 'bg-green-100 text-green-800'
    case 'failed':
      return 'bg-red-100 text-red-800'
    case 'running':
      return 'bg-blue-100 text-blue-800'
    case 'cancelled':
      return 'bg-gray-100 text-gray-800'
    default:
      return 'bg-yellow-100 text-yellow-800'
  }
}

const getProgressBarClass = (status) => {
  switch (status) {
    case 'completed':
      return 'bg-green-600'
    case 'failed':
      return 'bg-red-600'
    case 'running':
      return 'bg-blue-600'
    default:
      return 'bg-gray-600'
  }
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString() + ' ' + new Date(dateString).toLocaleTimeString()
}
</script>