<template>
  <AppLayout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">FreeScout API Import</h1>
          <p class="mt-1 text-sm text-gray-600">
            Import conversations, customers, and mailboxes from FreeScout instances via REST API
          </p>
        </div>
        <button
          @click="createProfile"
          class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150"
        >
          <PlusIcon class="w-4 h-4 mr-2" />
          Add API Profile
        </button>
      </div>

      <!-- Tab Navigation -->
      <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex space-x-8">
          <button
            @click="activeTab = 'profiles'"
            :class="[
              'py-2 px-1 border-b-2 font-medium text-sm',
              activeTab === 'profiles'
                ? 'border-indigo-500 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            API Profiles
          </button>
          <button
            @click="switchToLogsTab"
            :class="[
              'py-2 px-1 border-b-2 font-medium text-sm',
              activeTab === 'logs'
                ? 'border-indigo-500 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            Import Logs
          </button>
        </nav>
      </div>

      <!-- Tab Content -->
      <div v-if="activeTab === 'profiles'" class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <div class="lg:col-span-3 space-y-6">
          <!-- API Profiles Section -->
        <div class="bg-white shadow-sm sm:rounded-lg">
          <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">FreeScout API Profiles</h3>
            <p class="mt-1 text-sm text-gray-500">
              Configure FreeScout instances to import conversations, customers, and mailboxes.
            </p>
          </div>

          <div class="p-6 pb-20">
            <!-- Profile Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
              <!-- Loading state -->
              <div v-if="loading" class="col-span-full text-center py-8">
                <div class="inline-flex items-center px-4 py-2 font-semibold leading-6 text-sm shadow rounded-md text-white bg-indigo-500">
                  <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Loading profiles...
                </div>
              </div>

              <!-- Error state -->
              <div v-else-if="error" class="col-span-full bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex">
                  <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                  </div>
                  <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Error loading profiles</h3>
                    <p class="mt-1 text-sm text-red-700">{{ error }}</p>
                    <div class="mt-3">
                      <button @click="loadProfiles" class="bg-red-100 px-3 py-2 rounded-md text-sm font-medium text-red-800 hover:bg-red-200">
                        Try again
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <div
                v-for="profile in profiles"
                :key="profile.id"
                class="bg-gray-50 rounded-lg p-4 border border-gray-200 hover:border-gray-300 transition-colors relative"
              >
                <div class="flex items-start justify-between">
                  <div class="flex-1">
                    <div class="flex items-center">
                      <h4 class="text-sm font-medium text-gray-900">{{ profile.name }}</h4>
                      <div class="ml-2 flex items-center">
                        <div 
                          :class="[
                            'w-2 h-2 rounded-full',
                            profile.status === 'connected' ? 'bg-green-400' : 
                            profile.status === 'error' ? 'bg-red-400' : 
                            profile.status === 'testing' ? 'bg-blue-400 animate-pulse' : 'bg-yellow-400'
                          ]"
                        ></div>
                        <span 
                          :class="[
                            'ml-1 text-xs font-medium',
                            profile.status === 'connected' ? 'text-green-700' : 
                            profile.status === 'error' ? 'text-red-700' : 
                            profile.status === 'testing' ? 'text-blue-700' : 'text-yellow-700'
                          ]"
                        >
                          {{ profile.status === 'connected' ? 'Connected' : 
                             profile.status === 'error' ? 'Error' : 
                             profile.status === 'testing' ? 'Testing...' : 'Pending' }}
                        </span>
                      </div>
                    </div>
                    
                    <p class="mt-1 text-sm text-gray-500 break-all">{{ profile.instance_url }}</p>
                    
                    <div class="mt-2 text-xs text-gray-400">
                      <div>API Key: {{ profile.api_key_masked }}</div>
                      <div>Last Test: {{ profile.last_tested }}</div>
                    </div>

                    <!-- Statistics -->
                    <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                      <div class="bg-white rounded px-2 py-1">
                        <div class="text-xs font-medium text-gray-900">{{ profile.stats.conversations }}</div>
                        <div class="text-xs text-gray-500">Conversations</div>
                      </div>
                      <div class="bg-white rounded px-2 py-1">
                        <div class="text-xs font-medium text-gray-900">{{ profile.stats.customers }}</div>
                        <div class="text-xs text-gray-500">Customers</div>
                      </div>
                      <div class="bg-white rounded px-2 py-1">
                        <div class="text-xs font-medium text-gray-900">{{ profile.stats.mailboxes }}</div>
                        <div class="text-xs text-gray-500">Mailboxes</div>
                      </div>
                    </div>
                  </div>

                  <!-- Actions Dropdown -->
                  <div class="relative ml-4">
                    <button
                      @click="toggleProfileMenu(profile.id)"
                      class="text-gray-400 hover:text-gray-500 focus:outline-none focus:text-gray-500"
                    >
                      <EllipsisVerticalIcon class="w-5 h-5" />
                    </button>

                    <div
                      v-if="activeProfileMenu === profile.id"
                      class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50"
                    >
                      <div class="py-1">
                        <button
                          @click="testConnection(profile)"
                          class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left"
                        >
                          Test Connection
                        </button>
                        <button
                          @click="configureImport(profile)"
                          :class="[
                            'block px-4 py-2 text-sm w-full text-left',
                            profile.status === 'connected' 
                              ? 'text-gray-700 hover:bg-gray-100' 
                              : 'text-amber-600 hover:bg-amber-50'
                          ]"
                        >
                          Configure Import
                          <span v-if="profile.status !== 'connected'" class="text-xs text-amber-600 block">
                            (Will test connection automatically)
                          </span>
                        </button>
                        <button
                          @click="executeSync(profile)"
                          :class="[
                            'block px-4 py-2 text-sm w-full text-left',
                            profile.status === 'connected'
                              ? 'text-green-700 hover:bg-green-50'
                              : 'text-gray-400 cursor-not-allowed'
                          ]"
                          :disabled="profile.status !== 'connected'"
                        >
                          Execute Sync
                          <span v-if="profile.status !== 'connected'" class="text-xs text-gray-400 block">
                            (Requires connection)
                          </span>
                        </button>
                        <button
                          @click="editProfile(profile)"
                          class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left"
                        >
                          Edit Profile
                        </button>
                        <div class="border-t border-gray-100"></div>
                        <button
                          @click="deleteProfile(profile)"
                          class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 w-full text-left"
                        >
                          Delete Profile
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Empty State -->
              <div
                v-if="!loading && !error && profiles.length === 0"
                class="col-span-full bg-gray-50 rounded-lg p-8 text-center border-2 border-dashed border-gray-300"
              >
                <CloudArrowUpIcon class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-medium text-gray-900">No API profiles</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by creating your first FreeScout API profile.</p>
                <div class="mt-6">
                  <button
                    @click="createProfile"
                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                  >
                    <PlusIcon class="w-4 h-4 mr-2" />
                    Add API Profile
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        </div>
        
        <div class="lg:col-span-1">
          <!-- Import Statistics -->
          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <ChartBarIcon class="h-6 w-6 text-gray-400" />
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">
                      Total Imports
                    </dt>
                    <dd class="text-lg font-medium text-gray-900">
                      {{ importStats.total_imports }}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
              <div class="text-sm">
                <div class="flex justify-between">
                  <span class="text-gray-500">Successful:</span>
                  <span class="font-medium text-green-600">{{ importStats.successful }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-500">Failed:</span>
                  <span class="font-medium text-red-600">{{ importStats.failed }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-500">In Progress:</span>
                  <span class="font-medium text-yellow-600">{{ importStats.in_progress }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Recent Activity -->
          <div class="mt-6 bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5 border-b border-gray-200">
              <h3 class="text-sm font-medium text-gray-900">Recent Activity</h3>
            </div>
            <div class="divide-y divide-gray-200">
              <div
                v-for="activity in recentActivity"
                :key="activity.id"
                class="p-4"
              >
                <div class="flex items-center space-x-3">
                  <div 
                    :class="[
                      'flex-shrink-0 w-2 h-2 rounded-full',
                      activity.status === 'completed' ? 'bg-green-400' :
                      activity.status === 'failed' ? 'bg-red-400' : 'bg-yellow-400'
                    ]"
                  ></div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">
                      {{ activity.action }}
                    </p>
                    <p class="text-sm text-gray-500 truncate">
                      {{ activity.profile }} • {{ activity.timestamp }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Import Logs Tab Content -->
      <div v-else-if="activeTab === 'logs'" class="bg-white shadow-sm sm:rounded-lg">
        <div class="p-6 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-medium text-gray-900">Import Logs</h3>
              <p class="mt-1 text-sm text-gray-500">
                View detailed logs of all import operations and their status.
              </p>
            </div>
            <div class="flex space-x-3">
              <!-- Import Statistics Summary -->
              <div class="bg-gray-50 rounded-lg px-4 py-2">
                <div class="text-xs text-gray-500">Total</div>
                <div class="text-sm font-medium text-gray-900">{{ importStats.total_imports }}</div>
              </div>
              <div class="bg-green-50 rounded-lg px-4 py-2">
                <div class="text-xs text-green-600">Success</div>
                <div class="text-sm font-medium text-green-700">{{ importStats.successful }}</div>
              </div>
              <div class="bg-red-50 rounded-lg px-4 py-2">
                <div class="text-xs text-red-600">Failed</div>
                <div class="text-sm font-medium text-red-700">{{ importStats.failed }}</div>
              </div>
              <div class="bg-yellow-50 rounded-lg px-4 py-2">
                <div class="text-xs text-yellow-600">Running</div>
                <div class="text-sm font-medium text-yellow-700">{{ importStats.in_progress }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Logs Table -->
        <div class="overflow-hidden">
          <div v-if="loadingLogs" class="p-12 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-indigo-100 rounded-full mb-4">
              <svg class="animate-spin w-8 h-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Loading Import Logs</h3>
            <p class="text-sm text-gray-600">Please wait while we fetch the import logs...</p>
          </div>
          
          <div v-else-if="importLogs.data?.length === 0" class="p-12 text-center">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No Import Logs</h3>
            <p class="text-sm text-gray-600 mb-4">No import operations have been performed yet.</p>
            <button
              @click="activeTab = 'profiles'"
              class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              Create First Import Profile
            </button>
          </div>
          
          <div v-else class="divide-y divide-gray-200">
            <div
              v-for="log in importLogs.data || []"
              :key="log.id"
              class="p-6 hover:bg-gray-50 transition-colors"
            >
              <div class="flex items-center justify-between">
                <div class="flex-1">
                  <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                      <div 
                        :class="[
                          'w-3 h-3 rounded-full',
                          log.status === 'completed' ? 'bg-green-400' :
                          log.status === 'failed' ? 'bg-red-400' :
                          log.status === 'running' ? 'bg-blue-400 animate-pulse' :
                          'bg-gray-400'
                        ]"
                      ></div>
                    </div>
                    <div class="min-w-0 flex-1">
                      <div class="flex items-center space-x-2">
                        <span class="text-base font-medium text-gray-900">{{ log.profile_name }}</span>
                        <span 
                          :class="[
                            'px-2 py-1 text-xs font-medium rounded-full',
                            log.status === 'completed' ? 'bg-green-100 text-green-800' :
                            log.status === 'failed' ? 'bg-red-100 text-red-800' :
                            log.status === 'running' ? 'bg-blue-100 text-blue-800' :
                            'bg-gray-100 text-gray-800'
                          ]"
                        >
                          {{ log.status.charAt(0).toUpperCase() + log.status.slice(1) }}
                        </span>
                      </div>
                      <p class="text-sm text-gray-600 mt-1">
                        {{ log.current_operation || 'No operation details available' }}
                      </p>
                      <div class="flex items-center space-x-6 mt-2 text-sm text-gray-500">
                        <span class="flex items-center">
                          <span class="font-medium text-gray-700">{{ log.records_processed || 0 }}</span> processed
                        </span>
                        <span class="flex items-center">
                          <span class="font-medium text-green-600">{{ log.records_imported || 0 }}</span> imported
                        </span>
                        <span v-if="log.records_updated" class="flex items-center">
                          <span class="font-medium text-blue-600">{{ log.records_updated }}</span> updated
                        </span>
                        <span v-if="log.records_skipped" class="flex items-center">
                          <span class="font-medium text-yellow-600">{{ log.records_skipped }}</span> skipped
                        </span>
                        <span v-if="log.records_failed" class="flex items-center">
                          <span class="font-medium text-red-600">{{ log.records_failed }}</span> failed
                        </span>
                        <span v-if="log.duration" class="flex items-center">
                          {{ Math.floor(log.duration / 60) }}m {{ log.duration % 60 }}s
                        </span>
                        <span class="flex items-center">
                          by {{ log.started_by }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="flex flex-col items-end space-y-2">
                  <p class="text-sm text-gray-500">{{ new Date(log.started_at).toLocaleString() }}</p>
                  <div v-if="log.status === 'running' && log.progress_percentage !== null" class="w-32">
                    <div class="flex justify-between items-center mb-1">
                      <span class="text-xs text-gray-500">Progress</span>
                      <span class="text-xs font-medium text-blue-600">{{ log.progress_percentage }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                      <div 
                        class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                        :style="`width: ${log.progress_percentage || 0}%`"
                      ></div>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Error Details -->
              <div v-if="log.status === 'failed' && log.errors" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-md">
                <div class="flex">
                  <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                  </div>
                  <div class="ml-3">
                    <h4 class="text-sm font-medium text-red-800">Import Failed</h4>
                    <div class="mt-2 text-sm text-red-700">
                      <p>{{ typeof log.errors === 'string' ? log.errors : JSON.stringify(log.errors) }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>

  <!-- API Profile Modal -->
  <FreescoutApiProfileModal
    v-if="showCreateProfileModal"
    :profile="editingProfile"
    @close="closeProfileModal"
    @save="handleSaveProfile"
  />

  <!-- Loading Configuration Dialog -->
  <StackedDialog 
    v-if="showImportConfig && selectedProfile && !previewData && loadingPreview"
    :show="true"
    title="Loading Import Configuration"
    :closeable="true"
    @close="closeImportConfig"
  >
    <div class="text-center py-12">
      <div class="inline-flex items-center justify-center w-16 h-16 bg-indigo-100 rounded-full mb-4">
        <svg class="animate-spin w-8 h-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
      </div>
      
      <h3 class="text-lg font-medium text-gray-900 mb-2">Loading FreeScout Data</h3>
      <p class="text-sm text-gray-600 mb-4">
        <span v-if="loadingPhase" class="inline-block min-h-[1.25rem]">{{ loadingPhase }}</span>
        <span v-else>Preparing to fetch data from</span> <br>
        <span class="font-medium text-gray-900">{{ selectedProfile.name }}</span>
      </p>
      
      <div class="max-w-xs mx-auto">
        <div class="bg-gray-200 rounded-full h-2 mb-2">
          <div 
            class="bg-indigo-600 h-2 rounded-full transition-all duration-500 ease-out"
            :style="{ width: loadingPhase.includes('Connecting') ? '25%' : 
                               loadingPhase.includes('Testing') ? '40%' : 
                               loadingPhase.includes('Fetching') ? '65%' : 
                               loadingPhase.includes('Processing') ? '85%' : 
                               loadingPhase.includes('Finalizing') ? '95%' : '20%' }"
          ></div>
        </div>
        <p class="text-xs text-gray-500">
          <span v-if="loadingPhase.includes('Error')" class="text-red-600">
            Please check your connection and try again
          </span>
          <span v-else>
            This may take up to 30 seconds for large datasets...
          </span>
        </p>
      </div>

      <!-- Loading phase indicators -->
      <div class="mt-6 flex justify-center space-x-2">
        <div 
          v-for="(phase, index) in ['connect', 'test', 'fetch', 'process', 'finalize']" 
          :key="phase"
          :class="[
            'w-2 h-2 rounded-full transition-colors duration-300',
            getCurrentPhaseIndex() >= index ? 'bg-indigo-600' : 'bg-gray-300'
          ]"
        ></div>
      </div>
    </div>
  </StackedDialog>

  <!-- Import Configuration Dialog -->
  <FreescoutImportConfigDialog
    v-if="showImportConfig && selectedProfile"
    :profile="selectedProfile"
    :preview-data="previewData || {}"
    :loading-preview="loadingPreview"
    @close="closeImportConfig"
    @preview="handlePreviewImport"
    @save="handleSaveConfiguration"
    @execute="handleExecuteImport"
  />

  <!-- Import Preview Dialog -->
  <FreescoutImportPreviewDialog
    v-if="showPreviewDialog && previewProfile && previewConfig"
    :profile="previewProfile"
    :config="previewConfig"
    :import-data="previewData"
    @close="closePreviewDialog"
    @execute="handleExecuteFromPreview"
  />

  <!-- Import Progress Dialog -->
  <FreescoutImportProgressDialog
    ref="progressDialogRef"
    :show="showProgressDialog"
    :job="currentImportJob"
    :import-config="importConfig"
    @close="closeProgressDialog"
    @view-results="handleViewResults"
    @job-updated="handleJobUpdated"
  />

  <!-- Import Execution Dialog -->
  <FreescoutImportExecutionDialog
    v-if="showExecutionDialog && executionProfile && executionConfig"
    :profile="executionProfile"
    :config="executionConfig"
    @close="closeExecutionDialog"
    @complete="handleExecutionComplete"
    @failed="handleExecutionFailed"
  />

  <!-- Execute Sync Confirmation Dialog -->
  <StackedDialog
    v-if="showExecuteSyncDialog && syncProfile"
    :show="true"
    title="Execute FreeScout Sync"
    max-width="md"
    @close="closeExecuteSyncDialog"
  >
    <div class="p-6">
      <div class="flex items-center mb-4">
        <div class="flex-shrink-0">
          <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
          </div>
        </div>
        <div class="ml-4">
          <h3 class="text-lg font-medium text-gray-900">Sync from {{ syncProfile.name }}</h3>
          <p class="text-sm text-gray-500">{{ syncProfile.instance_url }}</p>
        </div>
      </div>

      <div class="mb-6">
        <p class="text-sm text-gray-600 mb-4">
          Configure import limits for this sync operation. Leave fields empty to import all available records.
        </p>
        
        <div class="grid grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Conversations
            </label>
            <input
              v-model.number="syncLimits.conversations"
              type="number"
              min="1"
              placeholder="All"
              class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
            />
            <p class="mt-1 text-xs text-blue-600">
              Time entries will be imported automatically
            </p>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Customers
            </label>
            <input
              v-model.number="syncLimits.customers"
              type="number"
              min="1"
              placeholder="All"
              class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
            />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Mailboxes
            </label>
            <input
              v-model.number="syncLimits.mailboxes"
              type="number"
              min="1"
              placeholder="All"
              class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
            />
          </div>
        </div>
      </div>

      <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4 mb-6">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-yellow-800">
              Important
            </h3>
            <div class="mt-2 text-sm text-yellow-700">
              <p>
                This will execute a sync using the profile's default configuration. 
                Make sure you have configured the import settings properly before proceeding.
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Action buttons -->
      <div class="flex justify-end space-x-3">
        <button
          @click="closeExecuteSyncDialog"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
        >
          Cancel
        </button>
        <button
          @click="confirmExecuteSync"
          class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
        >
          Execute Sync
        </button>
      </div>
    </div>
  </StackedDialog>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'
import FreescoutApiProfileModal from './Components/FreescoutApiProfileModal.vue'
import FreescoutImportConfigDialog from './Components/FreescoutImportConfigDialog.vue'
import FreescoutImportPreviewDialog from './Components/FreescoutImportPreviewDialog.vue'
import FreescoutImportProgressDialog from '@/Components/Import/FreescoutImportProgressDialog.vue'
import FreescoutImportExecutionDialog from './Components/FreescoutImportExecutionDialog.vue'
import StackedDialog from '@/Components/StackedDialog.vue'
import { 
  PlusIcon, 
  EllipsisVerticalIcon, 
  XMarkIcon, 
  CloudArrowUpIcon,
  ChartBarIcon 
} from '@heroicons/vue/24/outline'

// Reactive data
const showCreateProfileModal = ref(false)
const editingProfile = ref(null)
const activeProfileMenu = ref(null)
const selectedProfile = ref(null)
const showImportConfig = ref(false)
const showPreviewDialog = ref(false)
const previewProfile = ref(null)
const previewConfig = ref(null)
const showExecutionDialog = ref(false)
const executionProfile = ref(null)
const executionConfig = ref(null)
const showExecuteSyncDialog = ref(false)
const syncProfile = ref(null)
const syncLimits = ref({
  conversations: null,
  customers: null,
  mailboxes: null
})

// Progress tracking
const showProgressDialog = ref(false)
const currentImportJob = ref(null)
const importConfig = ref(null)
const progressPollingInterval = ref(null)
const progressDialogRef = ref(null)

// Real data
const profiles = ref([])
const loading = ref(false)
const error = ref(null)
const previewData = ref(null)
const loadingPreview = ref(false)
const loadingPhase = ref('')

const importStats = ref({
  total_imports: 0,
  successful: 0,
  failed: 0,
  in_progress: 0,
  pending: 0
})

const recentActivity = ref([])
const importLogs = ref({ data: [] })
const loadingLogs = ref(false)
const showImportLogs = ref(false)
const activeTab = ref('profiles')

const mockImportData = ref({
  conversations: [
    {
      id: 12547,
      subject: 'Unable to login to my account - Password reset not working',
      customer_email: 'john.doe@acme.com',
      customer_name: 'John Doe',
      mailbox: 'General Support',
      mailbox_id: 101,
      status: 'active',
      priority: 'medium',
      assigned_to: 'John Support',
      created_at: '2025-08-25T14:30:00Z',
      updated_at: '2025-08-25T16:45:00Z',
      threads_count: 5,
      time_entries_count: 2,
      preview: 'Hi, I\'ve been trying to login to my account for the past hour but keep getting an error message saying "Invalid credentials". I\'ve tried resetting my password multiple times but the reset email never arrives...',
      tags: ['account', 'password', 'urgent'],
      resolution_time_hours: null,
      external_id: 'fs_conv_12547',
      has_attachments: true
    },
    {
      id: 12546,
      subject: 'Invoice #INV-2025-0847 - Billing discrepancy found',
      customer_email: 'sarah.wilson@widgets.co',
      customer_name: 'Sarah Wilson',
      mailbox: 'Billing Support',
      mailbox_id: 102,
      status: 'pending',
      priority: 'high',
      assigned_to: 'Sarah Billing',
      created_at: '2025-08-25T13:15:00Z',
      updated_at: '2025-08-25T15:22:00Z',
      threads_count: 3,
      time_entries_count: 1,
      preview: 'Hello, I received invoice INV-2025-0847 but I notice there are some charges I don\'t recognize. Specifically, there\'s a $299 charge for "Premium API Access" that we never ordered...',
      tags: ['billing', 'invoice', 'review', 'dispute'],
      resolution_time_hours: null,
      external_id: 'fs_conv_12546',
      has_attachments: true
    },
    {
      id: 12545,
      subject: 'Feature request: Dark mode support for dashboard and mobile app',
      customer_email: 'mike.tech@startup.app',
      customer_name: 'Mike Rodriguez',
      mailbox: 'Product Feedback',
      mailbox_id: 104,
      status: 'closed',
      priority: 'low',
      assigned_to: 'Product Team',
      created_at: '2025-08-24T16:20:00Z',
      updated_at: '2025-08-25T09:30:00Z',
      threads_count: 8,
      time_entries_count: 0,
      preview: 'Would love to see dark mode support in the application dashboard and mobile app. Many of us work late hours and the bright interface can be straining on the eyes. This would be a great accessibility improvement...',
      tags: ['feature-request', 'ui', 'enhancement', 'accessibility'],
      resolution_time_hours: 17.2,
      external_id: 'fs_conv_12545',
      has_attachments: false
    },
    {
      id: 12544,
      subject: 'API Integration - OAuth 2.0 setup assistance needed urgently',
      customer_email: 'lisa.admin@example.org',
      customer_name: 'Lisa Chen',
      mailbox: 'Technical Support',
      mailbox_id: 103,
      status: 'active',
      priority: 'medium',
      assigned_to: 'Mike Tech',
      created_at: '2025-08-24T11:45:00Z',
      updated_at: '2025-08-25T14:10:00Z',
      threads_count: 12,
      time_entries_count: 1,
      preview: 'We\'re trying to set up the API integration with our Salesforce CRM system but running into OAuth 2.0 authentication issues. The access token keeps returning a 401 error even though we\'re following the documentation exactly...',
      tags: ['api', 'integration', 'oauth', 'crm', 'salesforce'],
      resolution_time_hours: null,
      external_id: 'fs_conv_12544',
      has_attachments: true
    },
    {
      id: 12543,
      subject: 'CRITICAL: Complete server downtime affecting production environment',
      customer_email: 'ops@enterprise.com',
      customer_name: 'Operations Team',
      mailbox: 'Technical Support',
      mailbox_id: 103,
      status: 'closed',
      priority: 'critical',
      assigned_to: 'Mike Tech',
      created_at: '2025-08-23T22:15:00Z',
      updated_at: '2025-08-24T02:30:00Z',
      threads_count: 15,
      time_entries_count: 1,
      preview: 'We\'re experiencing complete downtime on our production servers as of 10:15 PM EST. This is affecting all our customers and we need immediate assistance. Our internal monitoring shows database connection timeouts...',
      tags: ['urgent', 'downtime', 'production', 'server', 'critical'],
      resolution_time_hours: 4.25,
      external_id: 'fs_conv_12543',
      has_attachments: true
    },
    {
      id: 12542,
      subject: 'Mobile app crashes on iOS 17.5 - Unable to submit support tickets',
      customer_email: 'tech@mobilecorp.net',
      customer_name: 'Tech Support',
      mailbox: 'Technical Support',
      mailbox_id: 103,
      status: 'active',
      priority: 'high',
      assigned_to: 'App Dev Team',
      created_at: '2025-08-23T14:30:00Z',
      updated_at: '2025-08-25T11:20:00Z',
      threads_count: 6,
      time_entries_count: 3,
      preview: 'Our mobile app consistently crashes when users try to submit support tickets on iOS 17.5. The crash occurs immediately after tapping the "Submit" button. We\'ve tested on multiple devices with the same result...',
      tags: ['mobile', 'ios', 'crash', 'bug', 'tickets'],
      resolution_time_hours: null,
      external_id: 'fs_conv_12542',
      has_attachments: true
    },
    {
      id: 12541,
      subject: 'Data export request - GDPR compliance for user account deletion',
      customer_email: 'privacy@datacompany.eu',
      customer_name: 'Privacy Officer',
      mailbox: 'General Support',
      mailbox_id: 101,
      status: 'pending',
      priority: 'medium',
      assigned_to: 'Legal Team',
      created_at: '2025-08-22T09:15:00Z',
      updated_at: '2025-08-25T08:45:00Z',
      threads_count: 4,
      time_entries_count: 2,
      preview: 'We need to process a data export request for user ID 847392 as part of a GDPR account deletion request. Please provide all stored personal data in a machine-readable format within 30 days as required by regulation...',
      tags: ['gdpr', 'data-export', 'privacy', 'legal', 'compliance'],
      resolution_time_hours: null,
      external_id: 'fs_conv_12541',
      has_attachments: false
    },
    {
      id: 12540,
      subject: 'SSL certificate expiring soon - Need renewal guidance',
      customer_email: 'admin@securesite.org',
      customer_name: 'Admin Team',
      mailbox: 'Technical Support',
      mailbox_id: 103,
      status: 'closed',
      priority: 'medium',
      assigned_to: 'Security Team',
      created_at: '2025-08-20T16:00:00Z',
      updated_at: '2025-08-21T10:30:00Z',
      threads_count: 5,
      time_entries_count: 1,
      preview: 'Our SSL certificate for securesite.org is expiring on September 15th. We need guidance on the renewal process and whether we need to update any configurations on your end for the integration to continue working...',
      tags: ['ssl', 'certificate', 'security', 'renewal', 'expiration'],
      resolution_time_hours: 18.5,
      external_id: 'fs_conv_12540',
      has_attachments: false
    }
  ],
  customers: [
    {
      id: 5734,
      email: 'john.doe@acme.com',
      first_name: 'John',
      last_name: 'Doe',
      company: 'Acme Corporation',
      phone: '+1 (555) 123-4567',
      website: 'https://acme-corp.com',
      address: '123 Business Ave, Suite 100, New York, NY 10001',
      created_at: '2025-07-15T09:30:00Z',
      updated_at: '2025-08-25T16:45:00Z',
      conversation_count: 8,
      status: 'active',
      tags: ['vip', 'enterprise', 'priority-support'],
      notes: 'VIP customer with priority support contract. Direct line to executive team.',
      external_id: 'fs_cust_5734',
      social_profiles: {
        twitter: '@johndoe',
        linkedin: 'john-doe-acme'
      }
    },
    {
      id: 5733,
      email: 'sarah.wilson@widgets.co',
      first_name: 'Sarah',
      last_name: 'Wilson',
      company: 'Widget Solutions Inc.',
      phone: '+1 (555) 987-6543',
      website: 'https://widgets.co',
      address: '456 Tech Park Dr, San Francisco, CA 94107',
      created_at: '2025-08-01T14:22:00Z',
      updated_at: '2025-08-25T15:22:00Z',
      conversation_count: 3,
      status: 'active',
      tags: ['billing-issues', 'payment-dispute'],
      notes: 'Ongoing billing dispute regarding charges. Handle with care.',
      external_id: 'fs_cust_5733',
      social_profiles: {
        linkedin: 'sarah-wilson-widgets'
      }
    },
    {
      id: 5732,
      email: 'mike.tech@startup.app',
      first_name: 'Mike',
      last_name: 'Rodriguez',
      company: 'Startup Accelerator',
      phone: null,
      website: 'https://startup.app',
      address: 'Remote - Austin, TX',
      created_at: '2025-06-20T10:15:00Z',
      updated_at: '2025-08-25T09:30:00Z',
      conversation_count: 15,
      status: 'active',
      tags: ['developer', 'feature-requests', 'api-user', 'power-user'],
      notes: 'Technical lead and power user. Provides valuable feature feedback.',
      external_id: 'fs_cust_5732',
      social_profiles: {
        twitter: '@miketech',
        github: 'mike-rodriguez'
      }
    },
    {
      id: 5731,
      email: 'lisa.admin@example.org',
      first_name: 'Lisa',
      last_name: 'Chen',
      company: 'Example Foundation',
      phone: '+1 (555) 246-8135',
      website: 'https://example.org',
      address: '789 Nonprofit Blvd, Washington, DC 20001',
      created_at: '2025-08-10T16:30:00Z',
      updated_at: '2025-08-25T14:10:00Z',
      conversation_count: 6,
      status: 'active',
      tags: ['technical', 'integration', 'nonprofit'],
      notes: 'System administrator for nonprofit organization. Needs integration support.',
      external_id: 'fs_cust_5731',
      social_profiles: {
        linkedin: 'lisa-chen-foundation'
      }
    },
    {
      id: 5730,
      email: 'ops@enterprise.com',
      first_name: 'Operations',
      last_name: 'Team',
      company: 'Enterprise Solutions Ltd.',
      phone: '+1 (555) 999-0000',
      website: 'https://enterprise.com',
      address: '1000 Corporate Plaza, Chicago, IL 60601',
      created_at: '2025-05-01T08:00:00Z',
      updated_at: '2025-08-24T02:30:00Z',
      conversation_count: 24,
      status: 'active',
      tags: ['enterprise', 'critical', 'operations', '24-7-support'],
      notes: 'Enterprise client with 24/7 support agreement. Escalate critical issues immediately.',
      external_id: 'fs_cust_5730',
      social_profiles: {
        linkedin: 'enterprise-solutions-ltd'
      }
    },
    {
      id: 5729,
      email: 'tech@mobilecorp.net',
      first_name: 'Tech',
      last_name: 'Support',
      company: 'Mobile Corp',
      phone: '+1 (555) 777-8888',
      website: 'https://mobilecorp.net',
      address: '321 Mobile Way, Portland, OR 97201',
      created_at: '2025-08-15T12:00:00Z',
      updated_at: '2025-08-25T11:20:00Z',
      conversation_count: 5,
      status: 'active',
      tags: ['mobile', 'app-developer', 'ios'],
      notes: 'Mobile app development company. Focus on iOS-related issues.',
      external_id: 'fs_cust_5729',
      social_profiles: {
        twitter: '@mobilecorp'
      }
    },
    {
      id: 5728,
      email: 'privacy@datacompany.eu',
      first_name: 'Privacy',
      last_name: 'Officer',
      company: 'Data Company EU',
      phone: '+49 30 12345678',
      website: 'https://datacompany.eu',
      address: 'Hauptstraße 123, 10115 Berlin, Germany',
      created_at: '2025-06-01T14:30:00Z',
      updated_at: '2025-08-25T08:45:00Z',
      conversation_count: 7,
      status: 'active',
      tags: ['gdpr', 'privacy', 'legal', 'eu'],
      notes: 'GDPR compliance officer. Handle all data requests with legal team involvement.',
      external_id: 'fs_cust_5728',
      social_profiles: {
        linkedin: 'datacompany-eu'
      }
    },
    {
      id: 5727,
      email: 'admin@securesite.org',
      first_name: 'Admin',
      last_name: 'Team',
      company: 'Secure Site Organization',
      phone: '+1 (555) 555-5555',
      website: 'https://securesite.org',
      address: 'PO Box 567, Security City, CA 90210',
      created_at: '2025-07-01T10:00:00Z',
      updated_at: '2025-08-21T10:30:00Z',
      conversation_count: 3,
      status: 'active',
      tags: ['security', 'ssl', 'certificates'],
      notes: 'Security-focused organization. Requires SSL certificate and security assistance.',
      external_id: 'fs_cust_5727',
      social_profiles: {}
    }
  ],
  mailboxes: [
    {
      id: 101,
      name: 'General Support',
      email: 'support@company.com',
      conversation_count: 1247,
      user_count: 8,
      status: 'active',
      description: 'Primary support channel for general inquiries and account issues',
      created_at: '2024-01-15T00:00:00Z'
    },
    {
      id: 102,
      name: 'Billing Support',
      email: 'billing@company.com',
      conversation_count: 342,
      user_count: 3,
      status: 'active',
      description: 'Dedicated support for billing, invoices, and payment issues',
      created_at: '2024-01-15T00:00:00Z'
    },
    {
      id: 103,
      name: 'Technical Support',
      email: 'tech@company.com',
      conversation_count: 856,
      user_count: 12,
      status: 'active',
      description: 'Technical assistance, API support, and integration help',
      created_at: '2024-01-15T00:00:00Z'
    },
    {
      id: 104,
      name: 'Product Feedback',
      email: 'feedback@company.com',
      conversation_count: 189,
      user_count: 2,
      status: 'active',
      description: 'Feature requests, bug reports, and product suggestions',
      created_at: '2024-02-01T00:00:00Z'
    },
    {
      id: 105,
      name: 'Sales Inquiries',
      email: 'sales@company.com',
      conversation_count: 523,
      user_count: 5,
      status: 'active',
      description: 'Pre-sales questions, demos, and pricing inquiries',
      created_at: '2024-01-20T00:00:00Z'
    }
  ],
  time_entries: [
    {
      id: 8451,
      conversation_id: 12547,
      conversation_subject: 'Unable to login to my account - Password reset not working',
      user_id: 15,
      user_name: 'John Support',
      user_email: 'john.support@company.com',
      description: 'Assisted with account login issue - troubleshooting password reset email delivery',
      duration_minutes: 25,
      time_spent: 25 * 60, // FreeScout stores in seconds
      created_at: '2025-08-25T14:30:00Z',
      updated_at: '2025-08-25T14:55:00Z',
      mailbox: 'General Support',
      mailbox_id: 101,
      customer_email: 'john.doe@acme.com',
      customer_name: 'John Doe',
      billable: true,
      paused: false,
      finished: true,
      external_id: 'fs_time_8451'
    },
    {
      id: 8452,
      conversation_id: 12546,
      conversation_subject: 'Invoice #INV-2025-0847 - Billing discrepancy found',
      user_id: 22,
      user_name: 'Sarah Billing',
      user_email: 'sarah.billing@company.com',
      description: 'Reviewed invoice discrepancy - researched Premium API Access charges',
      duration_minutes: 15,
      time_spent: 15 * 60,
      created_at: '2025-08-25T15:45:00Z',
      updated_at: '2025-08-25T16:00:00Z',
      mailbox: 'Billing Support',
      mailbox_id: 102,
      customer_email: 'sarah.wilson@widgets.co',
      customer_name: 'Sarah Wilson',
      billable: true,
      paused: false,
      finished: true,
      external_id: 'fs_time_8452'
    },
    {
      id: 8453,
      conversation_id: 12544,
      conversation_subject: 'API Integration - OAuth 2.0 setup assistance needed urgently',
      user_id: 18,
      user_name: 'Mike Tech',
      user_email: 'mike.tech@company.com',
      description: 'API integration troubleshooting - OAuth 2.0 token generation and Salesforce integration',
      duration_minutes: 45,
      time_spent: 45 * 60,
      created_at: '2025-08-25T16:20:00Z',
      updated_at: '2025-08-25T17:05:00Z',
      mailbox: 'Technical Support',
      mailbox_id: 103,
      customer_email: 'lisa.admin@example.org',
      customer_name: 'Lisa Chen',
      billable: true,
      paused: false,
      finished: true,
      external_id: 'fs_time_8453'
    },
    {
      id: 8454,
      conversation_id: 12543,
      conversation_subject: 'CRITICAL: Complete server downtime affecting production environment',
      user_id: 18,
      user_name: 'Mike Tech',
      user_email: 'mike.tech@company.com',
      description: 'Emergency server downtime response - database connection timeout resolution',
      duration_minutes: 120,
      time_spent: 120 * 60,
      created_at: '2025-08-23T23:30:00Z',
      updated_at: '2025-08-24T01:30:00Z',
      mailbox: 'Technical Support',
      mailbox_id: 103,
      customer_email: 'ops@enterprise.com',
      customer_name: 'Operations Team',
      billable: true,
      paused: false,
      finished: true,
      external_id: 'fs_time_8454'
    },
    {
      id: 8455,
      conversation_id: 12547,
      conversation_subject: 'Unable to login to my account - Password reset not working',
      user_id: 15,
      user_name: 'John Support',
      user_email: 'john.support@company.com',
      description: 'Follow-up on password reset solution - confirmed email delivery and account access',
      duration_minutes: 10,
      time_spent: 10 * 60,
      created_at: '2025-08-25T16:45:00Z',
      updated_at: '2025-08-25T16:55:00Z',
      mailbox: 'General Support',
      mailbox_id: 101,
      customer_email: 'john.doe@acme.com',
      customer_name: 'John Doe',
      billable: true,
      paused: false,
      finished: true,
      external_id: 'fs_time_8455'
    },
    {
      id: 8456,
      conversation_id: 12542,
      conversation_subject: 'Mobile app crashes on iOS 17.5 - Unable to submit support tickets',
      user_id: 25,
      user_name: 'App Dev Team',
      user_email: 'appdev@company.com',
      description: 'iOS crash debugging - analyzing crash logs and testing on iOS 17.5 devices',
      duration_minutes: 35,
      time_spent: 35 * 60,
      created_at: '2025-08-24T10:30:00Z',
      updated_at: '2025-08-24T11:05:00Z',
      mailbox: 'Technical Support',
      mailbox_id: 103,
      customer_email: 'tech@mobilecorp.net',
      customer_name: 'Tech Support',
      billable: true,
      paused: false,
      finished: true,
      external_id: 'fs_time_8456'
    },
    {
      id: 8457,
      conversation_id: 12542,
      conversation_subject: 'Mobile app crashes on iOS 17.5 - Unable to submit support tickets',
      user_id: 25,
      user_name: 'App Dev Team',
      user_email: 'appdev@company.com',
      description: 'iOS crash fix implementation - updated submission validation and error handling',
      duration_minutes: 85,
      time_spent: 85 * 60,
      created_at: '2025-08-24T14:00:00Z',
      updated_at: '2025-08-24T15:25:00Z',
      mailbox: 'Technical Support',
      mailbox_id: 103,
      customer_email: 'tech@mobilecorp.net',
      customer_name: 'Tech Support',
      billable: true,
      paused: false,
      finished: true,
      external_id: 'fs_time_8457'
    },
    {
      id: 8458,
      conversation_id: 12541,
      conversation_subject: 'Data export request - GDPR compliance for user account deletion',
      user_id: 28,
      user_name: 'Legal Team',
      user_email: 'legal@company.com',
      description: 'GDPR data export preparation - gathering user data and preparing compliant export format',
      duration_minutes: 55,
      time_spent: 55 * 60,
      created_at: '2025-08-22T11:15:00Z',
      updated_at: '2025-08-22T12:10:00Z',
      mailbox: 'General Support',
      mailbox_id: 101,
      customer_email: 'privacy@datacompany.eu',
      customer_name: 'Privacy Officer',
      billable: false, // Legal work often non-billable
      paused: false,
      finished: true,
      external_id: 'fs_time_8458'
    },
    {
      id: 8459,
      conversation_id: 12541,
      conversation_subject: 'Data export request - GDPR compliance for user account deletion',
      user_id: 18,
      user_name: 'Mike Tech',
      user_email: 'mike.tech@company.com',
      description: 'GDPR data export technical implementation - extracting and formatting user data',
      duration_minutes: 30,
      time_spent: 30 * 60,
      created_at: '2025-08-25T08:15:00Z',
      updated_at: '2025-08-25T08:45:00Z',
      mailbox: 'General Support',
      mailbox_id: 101,
      customer_email: 'privacy@datacompany.eu',
      customer_name: 'Privacy Officer',
      billable: true,
      paused: false,
      finished: true,
      external_id: 'fs_time_8459'
    },
    {
      id: 8460,
      conversation_id: 12540,
      conversation_subject: 'SSL certificate expiring soon - Need renewal guidance',
      user_id: 30,
      user_name: 'Security Team',
      user_email: 'security@company.com',
      description: 'SSL certificate renewal guidance - provided renewal process documentation and configuration updates',
      duration_minutes: 20,
      time_spent: 20 * 60,
      created_at: '2025-08-21T09:00:00Z',
      updated_at: '2025-08-21T09:20:00Z',
      mailbox: 'Technical Support',
      mailbox_id: 103,
      customer_email: 'admin@securesite.org',
      customer_name: 'Admin Team',
      billable: true,
      paused: false,
      finished: true,
      external_id: 'fs_time_8460'
    }
  ]
})

// API methods
const loadProfiles = async () => {
  loading.value = true
  error.value = null
  
  try {
    const response = await axios.get('/api/import/freescout/profiles')
    
    if (response.data && response.data.data) {
      profiles.value = response.data.data
    } else {
      profiles.value = response.data || []
    }
  } catch (err) {
    console.error('Failed to load FreeScout profiles:', err)
    error.value = err.response?.data?.message || 'Failed to load profiles'
  } finally {
    loading.value = false
  }
}

const loadPreviewData = async (profile, sampleSize = 10) => {
  loadingPreview.value = true
  loadingPhase.value = 'Connecting to FreeScout API...'
  
  try {
    // Phase 1: Initial connection
    loadingPhase.value = 'Testing API connection...'
    
    // Phase 2: Data fetching
    loadingPhase.value = 'Fetching conversations and metadata...'
    
    const response = await axios.get(`/api/import/freescout/profiles/${profile.id}/preview-data`, {
      params: { sample_size: sampleSize }
    })
    
    // Phase 3: Processing data
    loadingPhase.value = 'Processing retrieved data...'
    
    if (response.data.success) {
      // Phase 4: Final preparation
      loadingPhase.value = 'Finalizing configuration data...'
      
      // Small delay to show the final phase
      await new Promise(resolve => setTimeout(resolve, 300))
      
      previewData.value = response.data.preview_data
      return response.data.preview_data
    } else {
      console.error('Failed to load preview data:', response.data.error)
      return null
    }
  } catch (err) {
    console.error('Failed to load preview data:', err)
    loadingPhase.value = 'Error loading data - please try again'
    return null
  } finally {
    loadingPreview.value = false
    loadingPhase.value = ''
  }
}

// Methods
const getCurrentPhaseIndex = () => {
  const phase = loadingPhase.value.toLowerCase()
  if (phase.includes('connecting')) return 0
  if (phase.includes('testing')) return 1
  if (phase.includes('fetching')) return 2
  if (phase.includes('processing')) return 3
  if (phase.includes('finalizing')) return 4
  return -1
}

const toggleProfileMenu = (profileId) => {
  activeProfileMenu.value = activeProfileMenu.value === profileId ? null : profileId
}

const testConnection = async (profile) => {
  console.log('Testing connection for:', profile.name)
  
  // Update profile status to testing
  profile.status = 'testing'
  profile.last_tested = 'testing...'
  
  // Update recent activity
  recentActivity.value.unshift({
    id: Date.now(),
    action: 'Connection test started',
    profile: profile.name,
    status: 'in_progress',
    timestamp: 'just now'
  })
  
  try {
    const response = await axios.post(`/api/import/freescout/profiles/${profile.id}/test-connection`)
    
    if (response.data.success) {
      profile.status = 'connected'
      profile.last_tested = 'just now'
      
      // Update stats if provided
      if (response.data.connection_test.stats) {
        profile.stats = {
          conversations: response.data.connection_test.stats.conversations.toLocaleString(),
          customers: response.data.connection_test.stats.customers.toLocaleString(),
          mailboxes: response.data.connection_test.stats.mailboxes.toString()
        }
      }
      
      // Update recent activity
      recentActivity.value[0] = {
        id: recentActivity.value[0].id,
        action: 'Connection test passed',
        profile: profile.name,
        status: 'completed',
        timestamp: 'just now'
      }
    } else {
      profile.status = 'error'
      profile.last_tested = 'just now'
      
      // Update recent activity
      recentActivity.value[0] = {
        id: recentActivity.value[0].id,
        action: 'Connection test failed',
        profile: profile.name,
        status: 'failed',
        timestamp: 'just now'
      }
      
      console.error('Connection test failed:', response.data.connection_test?.error)
    }
  } catch (error) {
    console.error('Failed to test connection:', error)
    profile.status = 'error'
    profile.last_tested = 'just now'
    
    // Update recent activity
    recentActivity.value[0] = {
      id: recentActivity.value[0].id,
      action: 'Connection test failed',
      profile: profile.name,
      status: 'failed',
      timestamp: 'just now'
    }
  }
  
  activeProfileMenu.value = null
}

const configureImport = async (profile) => {
  console.log('Configure import called for profile:', profile.name, 'status:', profile.status)
  
  selectedProfile.value = profile
  activeProfileMenu.value = null
  
  // Show loading dialog immediately
  showImportConfig.value = true
  loadingPreview.value = true
  previewData.value = null // Clear any previous data
  loadingPhase.value = 'Initializing import configuration...'
  
  try {
    // If profile isn't connected, test connection first
    if (profile.status !== 'connected') {
      console.log('Profile not connected, testing connection first...')
      loadingPhase.value = 'Verifying API connection...'
      
      await testConnection(profile)
      
      // If still not connected after test, show error
      if (profile.status !== 'connected') {
        loadingPhase.value = 'Connection failed - please check API settings'
        setTimeout(() => {
          alert('Unable to connect to FreeScout API. Please check your API configuration and try again.')
          showImportConfig.value = false
          loadingPreview.value = false
          loadingPhase.value = ''
        }, 1500)
        return
      }
    }
    
    // Load preview data for the profile (use large sample size for configuration)
    console.log('Loading preview data...')
    const data = await loadPreviewData(profile, 100) // Load reasonable sample for configuration display
    console.log('Preview data loaded:', data ? 'success' : 'failed')
    
    if (!data) {
      loadingPhase.value = 'Failed to load data - please try again'
      setTimeout(() => {
        alert('Failed to load preview data from FreeScout. Please check the connection and try again.')
        showImportConfig.value = false
        loadingPreview.value = false
        loadingPhase.value = ''
      }, 1500)
    }
    // If successful, the dialog will automatically switch to the configuration view
    // because previewData will be populated and loadingPreview will be false
    
  } catch (error) {
    console.error('Error configuring import:', error)
    loadingPhase.value = 'Error occurred - please try again'
    setTimeout(() => {
      alert('An error occurred while loading the import configuration. Please try again.')
      showImportConfig.value = false
      loadingPreview.value = false
      loadingPhase.value = ''
    }, 1500)
  }
}

const createProfile = () => {
  editingProfile.value = null
  showCreateProfileModal.value = true
}

const editProfile = (profile) => {
  editingProfile.value = { ...profile }
  showCreateProfileModal.value = true
  activeProfileMenu.value = null
}

const deleteProfile = async (profile) => {
  if (confirm(`Are you sure you want to delete "${profile.name}"? This action cannot be undone.`)) {
    try {
      const response = await axios.delete(`/api/import/freescout/profiles/${profile.id}`)
      
      if (response.data.success) {
        // Remove the profile from the list
        const index = profiles.value.findIndex(p => p.id === profile.id)
        if (index > -1) {
          profiles.value.splice(index, 1)
        }
      }
    } catch (error) {
      console.error('Failed to delete profile:', error)
      alert('Failed to delete profile: ' + (error.response?.data?.message || error.message))
    }
  }
  activeProfileMenu.value = null
}

const closeProfileModal = () => {
  showCreateProfileModal.value = false
  editingProfile.value = null
}

const handleSaveProfile = async (profileData) => {
  try {
    let response
    
    if (editingProfile.value) {
      // Update existing profile
      response = await axios.put(`/api/import/freescout/profiles/${editingProfile.value.id}`, profileData)
      
      if (response.data.success) {
        // Update the profile in the list
        const index = profiles.value.findIndex(p => p.id === editingProfile.value.id)
        if (index > -1) {
          profiles.value[index] = response.data.profile
        }
      }
    } else {
      // Create new profile
      response = await axios.post('/api/import/freescout/profiles', profileData)
      
      if (response.data.success) {
        profiles.value.push(response.data.profile)
      }
    }
    
    closeProfileModal()
  } catch (error) {
    console.error('Failed to save profile:', error)
    // You might want to show an error message to the user
    alert('Failed to save profile: ' + (error.response?.data?.message || error.message))
  }
}

const closeImportConfig = () => {
  showImportConfig.value = false
  selectedProfile.value = null
}

const handlePreviewImport = (config) => {
  previewProfile.value = selectedProfile.value
  previewConfig.value = config
  showPreviewDialog.value = true
}

const handleSaveConfiguration = async (config) => {
  try {
    // Save the configuration to the selected profile
    console.log('Saving import configuration for profile:', selectedProfile.value.name)
    
    const response = await axios.put(`/api/import/freescout/profiles/${selectedProfile.value.id}`, {
      name: selectedProfile.value.name,
      instance_url: selectedProfile.value.instance_url || selectedProfile.value.host,
      description: selectedProfile.value.description,
      is_active: selectedProfile.value.is_active !== false,  // Default to true
      configuration: {
        import_config: config
      }
    })
    
    if (response.data.success) {
      // Update the local profile data with the new configuration
      Object.assign(selectedProfile.value, response.data.profile)
      
      // Show success feedback
      const successMessage = document.createElement('div')
      successMessage.innerHTML = `
        <div class="fixed top-4 right-4 bg-green-50 border border-green-200 rounded-md p-4 shadow-md z-50">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm font-medium text-green-800">Configuration saved successfully!</p>
            </div>
          </div>
        </div>
      `
      document.body.appendChild(successMessage)
      
      // Auto-remove notification after 3 seconds
      setTimeout(() => {
        document.body.removeChild(successMessage)
      }, 3000)
      
      closeImportConfig()
    } else {
      throw new Error(response.data.message || 'Failed to save configuration')
    }
    
  } catch (error) {
    console.error('Error saving configuration:', error)
    alert('Failed to save configuration: ' + (error.response?.data?.message || error.message))
  }
}

const handleExecuteImport = async (config) => {
  try {
    // Start the import job
    const response = await fetch('/api/import/freescout/execute', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        profile_id: selectedProfile.value.id,
        config: config
      })
    })

    if (!response.ok) {
      throw new Error('Failed to start import')
    }

    const result = await response.json()
    
    if (result.success) {
      // Set up progress tracking
      currentImportJob.value = result.job
      importConfig.value = config
      showProgressDialog.value = true
      
      // Start polling for progress updates
      startProgressPolling(result.job.id)
      
      // Add to recent activity
      recentActivity.value.unshift({
        id: Date.now(),
        action: 'Import started',
        profile: selectedProfile.value.name,
        status: 'in_progress',
        timestamp: 'just now'
      })
    } else {
      console.error('Failed to start import:', result.message)
      alert('Failed to start import: ' + result.message)
    }
  } catch (error) {
    console.error('Error starting import:', error)
    alert('Error starting import: ' + error.message)
  }
}

const closePreviewDialog = () => {
  showPreviewDialog.value = false
  previewProfile.value = null
  previewConfig.value = null
}

const handleExecuteFromPreview = (data) => {
  // Close preview dialog and start execution
  closePreviewDialog()
  
  executionProfile.value = data.profile
  executionConfig.value = data.config
  showExecutionDialog.value = true
}

const closeExecutionDialog = () => {
  showExecutionDialog.value = false
  executionProfile.value = null
  executionConfig.value = null
}

const handleExecutionComplete = (stats) => {
  console.log('Import completed with stats:', stats)
  // Could refresh the profile statistics here
  // Update recent activity
  recentActivity.value.unshift({
    id: Date.now(),
    action: 'Import completed',
    profile: executionProfile.value?.name || 'Unknown',
    status: 'completed',
    timestamp: 'just now'
  })
}

// Progress tracking methods
const startProgressPolling = (jobId) => {
  // Clear any existing polling
  if (progressPollingInterval.value) {
    clearInterval(progressPollingInterval.value)
  }
  
  // If WebSocket is available, poll less frequently as a backup
  const pollInterval = window.Echo ? 10000 : 2000 // 10s with WebSocket, 2s without
  
  progressPollingInterval.value = setInterval(() => {
    pollImportProgress(jobId)
  }, pollInterval)
  
  // Initial poll
  pollImportProgress(jobId)
}

const pollImportProgress = async (jobId) => {
  try {
    const response = await fetch(`/api/import/freescout/job/${jobId}/status`, {
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    })
    
    if (!response.ok) {
      throw new Error('Failed to fetch import progress')
    }
    
    const result = await response.json()
    
    if (result.success) {
      currentImportJob.value = result.job
      
      // Stop polling if import is complete
      if (result.job.status === 'completed' || result.job.status === 'failed') {
        stopProgressPolling()
        
        // Update recent activity
        const activityIndex = recentActivity.value.findIndex(
          activity => activity.action === 'Import started' && 
                     activity.profile === selectedProfile.value?.name
        )
        
        if (activityIndex !== -1) {
          recentActivity.value[activityIndex] = {
            ...recentActivity.value[activityIndex],
            action: result.job.status === 'completed' ? 'Import completed' : 'Import failed',
            status: result.job.status,
            timestamp: 'just now'
          }
        }
      }
    }
  } catch (error) {
    console.error('Error polling import progress:', error)
  }
}

const stopProgressPolling = () => {
  if (progressPollingInterval.value) {
    clearInterval(progressPollingInterval.value)
    progressPollingInterval.value = null
  }
}

const closeProgressDialog = () => {
  showProgressDialog.value = false
  stopProgressPolling()
  currentImportJob.value = null
  importConfig.value = null
}

const handleViewResults = (job) => {
  console.log('Viewing results for job:', job)
  // TODO: Navigate to import results page or show results modal
  closeProgressDialog()
}

const handleJobUpdated = (updatedJob) => {
  // Update the current import job with WebSocket data
  currentImportJob.value = updatedJob
  
  // Update recent activity if job status changed
  if (updatedJob.status === 'completed' || updatedJob.status === 'failed') {
    const activityIndex = recentActivity.value.findIndex(
      activity => activity.action === 'Import started' && 
                 activity.profile === selectedProfile.value?.name
    )
    
    if (activityIndex !== -1) {
      recentActivity.value[activityIndex] = {
        ...recentActivity.value[activityIndex],
        action: updatedJob.status === 'completed' ? 'Import completed' : 'Import failed',
        status: updatedJob.status,
        timestamp: 'just now'
      }
    }
  }
}

const handleExecutionFailed = (error) => {
  console.log('Import failed:', error)
  // Update recent activity
}

// Execute Sync methods
const executeSync = (profile) => {
  syncProfile.value = profile
  // Reset sync limits
  syncLimits.value = {
    conversations: null,
    customers: null,
    mailboxes: null
  }
  showExecuteSyncDialog.value = true
  activeProfileMenu.value = null
}

const closeExecuteSyncDialog = () => {
  showExecuteSyncDialog.value = false
  syncProfile.value = null
  syncLimits.value = {
    conversations: null,
    customers: null,
    mailboxes: null
  }
}

const confirmExecuteSync = async () => {
  try {
    // Build the configuration with limits
    const config = {
      limits: {
        conversations: syncLimits.value.conversations || null,
        customers: syncLimits.value.customers || null,
        mailboxes: syncLimits.value.mailboxes || null
      },
      // Default configuration - should be loaded from profile if available
      account_strategy: 'map_mailboxes',
      agent_role_strategy: 'standard_agent',
      unmapped_users: 'auto_create',
      time_entry_defaults: {
        billable: true,
        approved: false
      },
      billing_rate_strategy: 'auto_select',
      comment_processing: {
        preserve_html: true,
        extract_attachments: true,
        add_context_prefix: true
      },
      sync_strategy: 'upsert',
      sync_mode: 'incremental',
      duplicate_detection: 'external_id',
      excluded_mailboxes: []
    }

    // Show loading state
    const loadingMessage = document.createElement('div')
    loadingMessage.innerHTML = `
      <div class="fixed top-4 right-4 bg-blue-50 border border-blue-200 rounded-md p-4 shadow-md z-50">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-blue-800">Starting sync...</p>
          </div>
        </div>
      </div>
    `
    document.body.appendChild(loadingMessage)

    // Execute the sync
    const response = await axios.post('/api/import/freescout/execute', {
      profile_id: syncProfile.value.id,
      config: config
    })

    // Remove loading message
    document.body.removeChild(loadingMessage)

    if (response.data.success) {
      // Set up progress tracking
      currentImportJob.value = response.data.job
      importConfig.value = config
      showProgressDialog.value = true
      
      // Start polling for progress updates
      startProgressPolling(response.data.job.id)
      
      // Add to recent activity
      recentActivity.value.unshift({
        id: Date.now(),
        action: 'Sync started',
        profile: syncProfile.value.name,
        status: 'in_progress',
        timestamp: 'just now'
      })

      // Show success feedback
      const successMessage = document.createElement('div')
      successMessage.innerHTML = `
        <div class="fixed top-4 right-4 bg-green-50 border border-green-200 rounded-md p-4 shadow-md z-50">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm font-medium text-green-800">Sync started successfully!</p>
            </div>
          </div>
        </div>
      `
      document.body.appendChild(successMessage)
      
      // Auto-remove notification after 3 seconds
      setTimeout(() => {
        document.body.removeChild(successMessage)
      }, 3000)

      // Close the dialog
      closeExecuteSyncDialog()
      
    } else {
      throw new Error(response.data.message || 'Failed to start sync')
    }
    
  } catch (error) {
    // Remove loading message if it exists
    const loadingMsg = document.querySelector('.fixed.top-4.right-4.bg-blue-50')
    if (loadingMsg) {
      document.body.removeChild(loadingMsg)
    }
    
    console.error('Error starting sync:', error)
    
    // Show error notification
    const errorMessage = document.createElement('div')
    errorMessage.innerHTML = `
      <div class="fixed top-4 right-4 bg-red-50 border border-red-200 rounded-md p-4 shadow-md z-50">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-red-800">Failed to start sync</p>
            <p class="text-xs text-red-600">${error.response?.data?.message || error.message}</p>
          </div>
        </div>
      </div>
    `
    document.body.appendChild(errorMessage)
    
    // Auto-remove notification after 5 seconds
    setTimeout(() => {
      document.body.removeChild(errorMessage)
    }, 5000)
  }
}

// Load import statistics from API
const loadImportStats = async () => {
  try {
    const response = await axios.get('/api/import/freescout/stats')
    if (response.data.success) {
      importStats.value = response.data.stats
    }
  } catch (error) {
    console.error('Failed to load import stats:', error)
  }
}

// Load recent activity from API
const loadRecentActivity = async () => {
  try {
    const response = await axios.get('/api/import/freescout/activity')
    if (response.data.success) {
      recentActivity.value = response.data.activity
    }
  } catch (error) {
    console.error('Failed to load recent activity:', error)
  }
}

// Load import logs from API
const loadImportLogs = async () => {
  if (loadingLogs.value) return
  
  loadingLogs.value = true
  try {
    console.log('Making API request to /api/import/freescout/logs')
    const response = await axios.get('/api/import/freescout/logs')
    console.log('API response:', response.data)
    if (response.data.success) {
      importLogs.value = response.data.jobs
      console.log('Updated importLogs.value:', importLogs.value)
    }
  } catch (error) {
    console.error('Failed to load import logs:', error)
  } finally {
    loadingLogs.value = false
  }
}

// Toggle import logs view
const toggleImportLogs = () => {
  showImportLogs.value = !showImportLogs.value
  if (showImportLogs.value && importLogs.value.data?.length === 0) {
    loadImportLogs()
  }
}

// Switch to logs tab and load logs
const switchToLogsTab = () => {
  activeTab.value = 'logs'
  // Always load logs when switching to the tab if we don't have data yet
  console.log('Switching to logs tab. Current importLogs:', importLogs.value)
  if (!importLogs.value || !importLogs.value.data || importLogs.value.data.length === 0) {
    console.log('Loading import logs...')
    loadImportLogs()
  }
}

// Lifecycle hooks
onMounted(async () => {
  // Set CSRF token for axios requests
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
  if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token
  }
  
  // Load profiles and stats on component mount
  await Promise.all([
    loadProfiles(),
    loadImportStats(),
    loadRecentActivity()
  ])
  
  // Close dropdown when clicking outside
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.relative')) {
      activeProfileMenu.value = null
    }
  })
})

// Cleanup on component unmount
onUnmounted(() => {
  stopProgressPolling()
})
</script>