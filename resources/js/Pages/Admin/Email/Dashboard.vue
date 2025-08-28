<template>
  <Head title="Email System Monitoring" />

  <StandardPageLayout 
    title="Email System Monitoring" 
    subtitle="Monitor email processing, performance metrics, and system health"
    :show-sidebar="true"
    :show-filters="true"
  >
    <template #header-actions>
      <div class="flex items-center space-x-3">
        <!-- Unprocessed Emails Button -->
        <button
          v-if="queuedEmails.length > 0"
          @click="showUnprocessedEmails"
          class="inline-flex items-center px-4 py-2 border border-yellow-300 text-sm font-medium rounded-md text-yellow-700 bg-yellow-50 hover:bg-yellow-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500"
        >
          <ExclamationTriangleIcon class="w-4 h-4 mr-2" />
          {{ queuedEmails.length }} Unprocessed Email{{ queuedEmails.length === 1 ? '' : 's' }}
        </button>
        
        <!-- Refresh Data -->
        <button
          @click="refreshData"
          :disabled="refreshing"
          class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <ArrowPathIcon :class="['w-4 h-4 mr-2', refreshing ? 'animate-spin' : '']" />
          Refresh
        </button>

        <!-- System Health Check -->
        <button
          @click="runHealthCheck"
          :disabled="healthChecking"
          class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <HeartIcon class="w-4 h-4 mr-2" />
          Health Check
        </button>

        <!-- Manual Email Retrieval -->
        <div class="relative">
          <button
            @click="showRetrievalOptions = !showRetrievalOptions"
            :disabled="retrievingEmails"
            class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
          >
            <EnvelopeIcon class="w-4 h-4 mr-2" />
            {{ retrievingEmails ? 'Retrieving...' : 'Fetch Emails' }}
            <ChevronDownIcon class="w-4 h-4 ml-1" />
          </button>
          
          <!-- Dropdown Menu -->
          <div
            v-if="showRetrievalOptions"
            class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-gray-200"
          >
            <div class="py-1">
              <button
                @click="manualEmailRetrieval('test')"
                :disabled="retrievingEmails"
                class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50"
              >
                🧪 Test Mode
                <div class="text-xs text-gray-500">Retrieve and log only</div>
              </button>
              <button
                @click="manualEmailRetrieval('process')"
                :disabled="retrievingEmails"
                class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50"
              >
                ⚡ Process Mode
                <div class="text-xs text-gray-500">Create tickets and users</div>
              </button>
            </div>
          </div>
        </div>

        <!-- Email System Configuration -->
        <a
          :href="route('settings.index', 'email')"
          class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <CogIcon class="w-4 h-4 mr-2" />
          Configure Email System
        </a>
      </div>
    </template>

    <template #filters>
      <FilterSection>
        <!-- Time Range Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Time Range</label>
          <select
            v-model="filters.timeRange"
            @change="loadDashboardData"
            class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          >
            <option value="1h">Last Hour</option>
            <option value="24h">Last 24 Hours</option>
            <option value="7d">Last 7 Days</option>
            <option value="30d">Last 30 Days</option>
          </select>
        </div>

        <!-- Status Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
          <MultiSelect
            v-model="filters.statuses"
            :options="statusOptions"
            placeholder="All statuses"
            value-key="value"
            label-key="label"
          />
        </div>

        <!-- Email Service Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Service Type</label>
          <select
            v-model="filters.serviceType"
            @change="loadDashboardData"
            class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          >
            <option value="">All Services</option>
            <option value="incoming">Incoming Email</option>
            <option value="outgoing">Outgoing Email</option>
          </select>
        </div>

        <!-- Show Unprocessed Emails Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Show Unprocessed</label>
          <div class="flex items-center h-10">
            <input
              type="checkbox"
              v-model="filters.showQueuedEmails"
              @change="loadQueuedEmails"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <span class="ml-2 text-sm text-gray-600">Unprocessed emails</span>
          </div>
        </div>
      </FilterSection>
    </template>

    <template #main-content>
      <!-- Unprocessed Emails Management Section -->
      <div v-if="queuedEmails.length > 0 || filters.showQueuedEmails" class="mb-8" data-section="unprocessed-emails">
        <div class="bg-white shadow rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h3 class="text-lg leading-6 font-medium text-gray-900">Unprocessed Emails</h3>
                <p class="text-sm text-gray-600 mt-1">
                  Emails from unmapped domains waiting for manual assignment or review.
                </p>
              </div>
              <div class="flex items-center space-x-3">
                <button
                  @click="checkForDomainMappings"
                  :disabled="checkingDomainMappings"
                  class="inline-flex items-center px-3 py-2 border border-green-300 text-sm leading-4 font-medium rounded-md text-green-700 bg-green-50 hover:bg-green-100"
                  title="Check if any unprocessed emails now match domain mappings"
                >
                  <CheckCircleIcon :class="['w-4 h-4 mr-2', checkingDomainMappings ? 'animate-spin' : '']" />
                  {{ checkingDomainMappings ? 'Checking...' : 'Check Mappings' }}
                </button>
                <button
                  @click="refreshQueuedEmails"
                  :disabled="loadingQueuedEmails"
                  class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                >
                  <ArrowPathIcon :class="['w-4 h-4 mr-2', loadingQueuedEmails ? 'animate-spin' : '']" />
                  Refresh
                </button>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                  {{ queuedEmails.length }} pending
                </span>
              </div>
            </div>

            <!-- Queued Emails Table -->
            <div v-if="queuedEmails.length > 0" class="overflow-hidden">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">From</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Received</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Domain</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="email in queuedEmails" :key="email.email_id" class="hover:bg-gray-50">
                    <td class="px-4 py-4 whitespace-nowrap">
                      <div class="flex items-center">
                        <div class="flex-shrink-0 h-8 w-8">
                          <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">
                            <EnvelopeIcon class="h-4 w-4 text-gray-500" />
                          </div>
                        </div>
                        <div class="ml-3">
                          <div class="text-sm font-medium text-gray-900">{{ email.from_name || 'Unknown Sender' }}</div>
                          <div class="text-sm text-gray-500">{{ email.from_address }}</div>
                        </div>
                      </div>
                    </td>
                    <td class="px-4 py-4">
                      <div class="text-sm text-gray-900 max-w-xs truncate">{{ email.subject || '(No Subject)' }}</div>
                      <div class="text-sm text-gray-500">{{ email.to_addresses?.[0] || 'Unknown recipient' }}</div>
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ formatTimeAgo(email.received_at) }}
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap">
                      <span class="inline-flex px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">
                        {{ getDomainFromEmail(email.from_address) }}
                      </span>
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <div class="flex items-center justify-end space-x-2">
                        <button
                          @click="previewEmail(email)"
                          class="text-indigo-600 hover:text-indigo-900"
                        >
                          Preview
                        </button>
                        <button
                          @click="assignEmail(email)"
                          class="text-green-600 hover:text-green-900"
                        >
                          Assign
                        </button>
                        <button
                          @click="rejectEmail(email)"
                          class="text-red-600 hover:text-red-900"
                        >
                          Reject
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Empty State -->
            <div v-else-if="!loadingQueuedEmails" class="text-center py-6">
              <EnvelopeIcon class="mx-auto h-12 w-12 text-gray-400" />
              <h3 class="mt-2 text-sm font-medium text-gray-900">No unprocessed emails</h3>
              <p class="mt-1 text-sm text-gray-500">
                All emails are either processed or from mapped domains.
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- System Status Alerts -->
      <div v-if="systemAlerts.length > 0" class="mb-6 space-y-3">
        <div v-for="(alert, index) in systemAlerts" :key="alert.id || index" 
          :class="[
            'p-4 rounded-lg border-l-4',
            alert.type === 'error' ? 'bg-red-50 border-red-400' :
            alert.type === 'warning' ? 'bg-yellow-50 border-yellow-400' :
            'bg-blue-50 border-blue-400'
          ]">
          <div class="flex items-start">
            <div class="flex-shrink-0">
              <ExclamationTriangleIcon v-if="alert.type === 'error'" class="h-5 w-5 text-red-400" />
              <ExclamationTriangleIcon v-else-if="alert.type === 'warning'" class="h-5 w-5 text-yellow-400" />
              <InformationCircleIcon v-else class="h-5 w-5 text-blue-400" />
            </div>
            <div class="ml-3 flex-1">
              <h3 :class="[
                'text-sm font-medium',
                alert.type === 'error' ? 'text-red-800' :
                alert.type === 'warning' ? 'text-yellow-800' :
                'text-blue-800'
              ]">
                {{ alert.message }}
              </h3>
              <p v-if="alert.details" :class="[
                'text-sm mt-1',
                alert.type === 'error' ? 'text-red-700' :
                alert.type === 'warning' ? 'text-yellow-700' :
                'text-blue-700'
              ]">
                {{ alert.details }}
              </p>
            </div>
            <button
              @click="dismissAlert(index)"
              class="flex-shrink-0 ml-4 text-gray-400 hover:text-gray-600"
            >
              <XMarkIcon class="h-4 w-4" />
            </button>
          </div>
        </div>
      </div>

      <!-- Key Metrics Overview -->
      <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <!-- Total Emails -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <EnvelopeIcon class="h-6 w-6 text-gray-400" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Total Emails</dt>
                  <dd class="flex items-baseline">
                    <div class="text-2xl font-semibold text-gray-900">
                      {{ dashboardData.metrics?.total_emails?.toLocaleString() || 0 }}
                    </div>
                    <div class="ml-2 flex items-baseline text-sm font-semibold">
                      <span :class="[
                        dashboardData.metrics?.email_change >= 0 ? 'text-green-600' : 'text-red-600'
                      ]">
                        {{ dashboardData.metrics?.email_change >= 0 ? '+' : '' }}{{ dashboardData.metrics?.email_change || 0 }}%
                      </span>
                      <span class="ml-1 text-gray-500">vs yesterday</span>
                    </div>
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <!-- Success Rate -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <CheckCircleIcon class="h-6 w-6 text-green-400" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Success Rate</dt>
                  <dd class="text-2xl font-semibold text-gray-900">
                    {{ dashboardData.metrics?.success_rate || 0 }}%
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <!-- Commands Executed -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <CommandLineIcon class="h-6 w-6 text-indigo-400" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Commands Executed</dt>
                  <dd class="text-2xl font-semibold text-gray-900">
                    {{ dashboardData.metrics?.commands_executed?.toLocaleString() || 0 }}
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <!-- Queue Status -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <QueueListIcon class="h-6 w-6 text-blue-400" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Pending Jobs</dt>
                  <dd class="flex items-center">
                    <div class="text-2xl font-semibold text-gray-900">
                      {{ dashboardData.queue?.pending || 0 }}
                    </div>
                    <div v-if="dashboardData.queue?.failed > 0" class="ml-2">
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                        {{ dashboardData.queue?.failed || 0 }} failed
                      </span>
                    </div>
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Email Processing Chart -->
      <div class="bg-white shadow rounded-lg mb-8">
        <div class="px-4 py-5 sm:p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Email Processing Overview</h3>
            <div class="flex items-center space-x-2">
              <button
                v-for="period in chartPeriods"
                :key="period.value"
                @click="chartPeriod = period.value; loadChartData()"
                :class="[
                  'px-3 py-1 text-sm font-medium rounded-md',
                  chartPeriod === period.value
                    ? 'bg-indigo-100 text-indigo-700'
                    : 'text-gray-500 hover:text-gray-700'
                ]"
              >
                {{ period.label }}
              </button>
            </div>
          </div>
          
          <!-- Chart Container -->
          <div class="h-64 w-full">
            <canvas ref="emailChart" class="w-full h-full"></canvas>
          </div>
        </div>
      </div>

      <!-- Recent Activity & Queue Management -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Recent Processing Activity -->
        <div class="bg-white shadow rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Activity</h3>
              <button
                @click="showProcessingLogs"
                class="text-sm text-indigo-600 hover:text-indigo-500"
              >
                View All Logs
              </button>
            </div>
            
            <div class="space-y-3">
              <div v-for="activity in recentActivity" :key="activity.email_id || activity.id" 
                class="flex items-center space-x-3 p-3 bg-gray-50 rounded-md">
                <div class="flex-shrink-0">
                  <div :class="[
                    'w-2 h-2 rounded-full',
                    activity.status === 'processed' ? 'bg-green-400' :
                    activity.status === 'failed' ? 'bg-red-400' :
                    activity.status === 'processing' ? 'bg-blue-400' :
                    'bg-gray-400'
                  ]"></div>
                </div>
                <div class="min-w-0 flex-1">
                  <p class="text-sm text-gray-900 truncate">{{ activity.subject || 'Email processed' }}</p>
                  <p class="text-xs text-gray-500">
                    {{ activity.from_address || activity.from }} • {{ formatTimeAgo(activity.created_at) }}
                  </p>
                </div>
                <div class="flex-shrink-0">
                  <span :class="[
                    'inline-flex px-2 py-1 text-xs font-medium rounded-full',
                    activity.status === 'processed' ? 'bg-green-100 text-green-800' :
                    activity.status === 'failed' ? 'bg-red-100 text-red-800' :
                    activity.status === 'processing' ? 'bg-blue-100 text-blue-800' :
                    'bg-gray-100 text-gray-800'
                  ]">
                    {{ activity.status }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Queue Management -->
        <div class="bg-white shadow rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg leading-6 font-medium text-gray-900">Queue Management</h3>
              <button
                @click="refreshQueueData"
                :disabled="queueRefreshing"
                class="text-sm text-indigo-600 hover:text-indigo-500"
              >
                Refresh
              </button>
            </div>

            <div class="space-y-4">
              <!-- Queue Status -->
              <div class="grid grid-cols-3 gap-4 text-sm">
                <div class="text-center">
                  <div class="text-2xl font-bold text-blue-600">{{ queueData?.pending || 0 }}</div>
                  <div class="text-gray-500">Pending</div>
                </div>
                <div class="text-center">
                  <div class="text-2xl font-bold text-green-600">{{ queueData?.processing || 0 }}</div>
                  <div class="text-gray-500">Processing</div>
                </div>
                <div class="text-center">
                  <div class="text-2xl font-bold text-red-600">{{ queueData?.failed || 0 }}</div>
                  <div class="text-gray-500">Failed</div>
                </div>
              </div>

              <!-- Queue Actions -->
              <div class="flex space-x-2">
                <button
                  @click="retryFailedJobs"
                  :disabled="!queueData?.failed || retryingJobs"
                  class="flex-1 inline-flex justify-center items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50"
                >
                  <span v-if="retryingJobs" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Retrying...
                  </span>
                  <span v-else>Retry Failed</span>
                </button>
                <button
                  @click="clearFailedJobs"
                  :disabled="!queueData?.failed || clearingJobs"
                  class="flex-1 inline-flex justify-center items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                >
                  Clear Failed
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Command Execution Stats -->
      <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
          <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Command Execution Statistics</h3>
          
          <div class="overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
              <thead>
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Command</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Uses</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Success Rate</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Processing Time</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Used</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr v-for="command in commandStats" :key="command.name" class="hover:bg-gray-50">
                  <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    {{ command.name }}
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ command.total_uses?.toLocaleString() || 0 }}
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="text-sm text-gray-900">{{ command.success_rate || 0 }}%</div>
                      <div class="ml-2 w-16 bg-gray-200 rounded-full h-2">
                        <div 
                          class="bg-green-500 h-2 rounded-full" 
                          :style="{ width: `${command.success_rate || 0}%` }"
                        ></div>
                      </div>
                    </div>
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ command.avg_processing_time || 0 }}ms
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ command.last_used ? formatTimeAgo(command.last_used) : 'Never' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </template>

    <template #sidebar>
      <!-- System Status Summary -->
      <div class="bg-white overflow-hidden shadow rounded-lg mb-6">
        <div class="p-5">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg leading-6 font-medium text-gray-900">System Status</h3>
            <div :class="[
              'flex items-center px-3 py-1 rounded-full text-xs font-medium',
              systemHealth.system_active ? 'bg-green-100 text-green-800' :
              systemHealth.fully_configured ? 'bg-yellow-100 text-yellow-800' :
              'bg-red-100 text-red-800'
            ]">
              <div :class="[
                'w-2 h-2 rounded-full mr-2',
                systemHealth.system_active ? 'bg-green-400' :
                systemHealth.fully_configured ? 'bg-yellow-400' :
                'bg-red-400'
              ]"></div>
              {{ systemHealth.system_active ? 'Active' : systemHealth.fully_configured ? 'Configured' : 'Inactive' }}
            </div>
          </div>
          
          <div class="text-sm text-gray-600 space-y-2">
            <p v-if="systemHealth.system_active">
              Email system is active and processing emails with 
              <strong>{{ systemHealth.active_domain_mappings || 0 }}</strong> domain mappings configured.
            </p>
            <p v-else-if="systemHealth.fully_configured">
              Email system is configured but inactive. 
              <a :href="route('settings.index', 'email')" class="text-indigo-600 hover:text-indigo-500">Activate in settings</a> to start processing.
            </p>
            <p v-else>
              Email system requires configuration. 
              <a :href="route('settings.index', 'email')" class="text-indigo-600 hover:text-indigo-500">Complete setup</a> to begin processing emails.
            </p>
            
            <!-- Provider Information -->
            <div v-if="systemHealth.incoming_enabled || systemHealth.outgoing_enabled" class="pt-2 border-t border-gray-200">
              <div class="grid grid-cols-2 gap-4 text-xs">
                <div v-if="systemHealth.incoming_enabled">
                  <span class="font-medium text-gray-700">Incoming:</span>
                  <span class="capitalize">{{ systemHealth.email_provider || 'Not configured' }}</span>
                </div>
                <div v-if="systemHealth.outgoing_enabled">
                  <span class="font-medium text-gray-700">Outgoing:</span>
                  <span v-if="systemHealth.use_same_provider && systemHealth.email_provider === 'm365'" class="text-green-600">
                    Same as incoming (M365)
                  </span>
                  <span v-else class="capitalize">{{ systemHealth.outgoing_provider || 'Not configured' }}</span>
                </div>
              </div>
              
              <!-- Timestamp Processing Info -->
              <div v-if="systemHealth.timestamp_source" class="mt-2 pt-2 border-t border-gray-100">
                <span class="font-medium text-gray-700">Timestamps:</span>
                <span class="capitalize">{{ systemHealth.timestamp_source === 'original' ? 'Original Email' : 'Service Vault Processing' }}</span>
                <span class="text-gray-500">({{ systemHealth.timestamp_timezone }})</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="bg-white overflow-hidden shadow rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
          <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Quick Actions</h3>
          <div class="space-y-3">
            <button
              @click="showProcessingLogs"
              class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md"
            >
              📋 View Processing Logs
            </button>
            <button
              @click="showSystemHealth"
              class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md"
            >
              ❤️ System Health Details
            </button>
            <button
              @click="exportLogs"
              class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md"
            >
              📊 Export Analytics
            </button>
            <button
              @click="testEmailSystem"
              class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md"
            >
              🧪 Test Email System
            </button>
            <div class="space-y-1">
              <button
                @click="manualEmailRetrieval('test')"
                :disabled="retrievingEmails"
                class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md disabled:opacity-50"
              >
                🧪 {{ retrievingEmails ? 'Retrieving...' : 'Test Email Retrieval' }}
              </button>
              <button
                @click="manualEmailRetrieval('process')"
                :disabled="retrievingEmails"
                class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md disabled:opacity-50"
              >
                ⚡ {{ retrievingEmails ? 'Processing...' : 'Process Email Retrieval' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Errors -->
      <div v-if="recentErrors.length > 0" class="bg-white overflow-hidden shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
          <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Recent Errors</h3>
          <div class="space-y-3">
            <div v-for="error in recentErrors" :key="error.id" class="p-3 bg-red-50 rounded-md">
              <p class="text-sm font-medium text-red-800">{{ error.type }}</p>
              <p class="text-xs text-red-600 mt-1">{{ error.message }}</p>
              <p class="text-xs text-gray-500 mt-1">{{ formatTimeAgo(error.occurred_at) }}</p>
            </div>
          </div>
        </div>
      </div>
    </template>
  </StandardPageLayout>

  <!-- Email Preview Modal -->
  <div v-if="showEmailPreview" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
      <div class="mt-3">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900">Email Preview</h3>
          <button @click="closeEmailPreview" class="text-gray-400 hover:text-gray-600">
            <XMarkIcon class="h-6 w-6" />
          </button>
        </div>
        
        <div v-if="selectedEmail" class="space-y-4">
          <!-- Email Headers -->
          <div class="bg-gray-50 p-4 rounded-md">
            <div class="grid grid-cols-1 gap-2 text-sm">
              <div><strong>From:</strong> {{ selectedEmail.from_address }}</div>
              <div><strong>To:</strong> {{ selectedEmail.to_addresses?.join(', ') }}</div>
              <div v-if="selectedEmail.cc_addresses?.length"><strong>CC:</strong> {{ selectedEmail.cc_addresses.join(', ') }}</div>
              <div><strong>Subject:</strong> {{ selectedEmail.subject || '(No Subject)' }}</div>
              <div><strong>Received:</strong> {{ formatDateTime(selectedEmail.received_at) }}</div>
              <div><strong>Domain:</strong> {{ getDomainFromEmail(selectedEmail.from_address) }}</div>
            </div>
          </div>
          
          <!-- Email Body -->
          <div class="max-h-64 overflow-y-auto border rounded-md p-4">
            <div v-if="selectedEmail.parsed_body_html" v-html="selectedEmail.parsed_body_html" class="prose prose-sm max-w-none"></div>
            <div v-else-if="selectedEmail.parsed_body_text" class="whitespace-pre-wrap text-sm text-gray-700">{{ selectedEmail.parsed_body_text }}</div>
            <div v-else class="text-gray-500 italic">No content available</div>
          </div>
          
          <!-- Actions -->
          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button
              @click="rejectEmail(selectedEmail)"
              class="px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
            >
              Reject Email
            </button>
            <button
              @click="assignEmail(selectedEmail)"
              class="px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700"
            >
              Assign to Account
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Email Assignment Modal -->
  <div v-if="showAssignModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
      <div class="mt-3">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900">Assign Email to Account</h3>
          <button @click="closeAssignModal" class="text-gray-400 hover:text-gray-600">
            <XMarkIcon class="h-6 w-6" />
          </button>
        </div>
        
        <div v-if="emailToAssign">
          <div class="mb-4">
            <p class="text-sm text-gray-600 mb-2">
              Assigning email from <strong>{{ emailToAssign.from_address }}</strong>
            </p>
            <p class="text-sm text-gray-600">
              Subject: <strong>{{ emailToAssign.subject || '(No Subject)' }}</strong>
            </p>
          </div>
          
          <div class="mb-4">
            <SimpleAccountUserSelector
              v-model:accountId="selectedAccountIdForAssignment"
              :showUserSelector="false"
            />
          </div>
          
          <div class="flex justify-end space-x-3">
            <button
              @click="closeAssignModal"
              class="px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
            >
              Cancel
            </button>
            <button
              @click="confirmAssignment"
              :disabled="!selectedAccountIdForAssignment || assigningEmail"
              class="px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50"
            >
              {{ assigningEmail ? 'Assigning...' : 'Assign Email' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modals -->
  <EmailSystemSettingsModal
    :show="showSettingsModal"
    @close="showSettingsModal = false"
  />

  <ProcessingLogsModal
    :show="showLogsModal"
    @close="showLogsModal = false"
  />

  <SystemHealthModal
    :show="showHealthModal"
    :health-data="systemHealth"
    @close="showHealthModal = false"
  />
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { useQuery, useQueryClient } from '@tanstack/vue-query'
import {
  ArrowPathIcon,
  HeartIcon,
  CogIcon,
  EnvelopeIcon,
  CheckCircleIcon,
  CommandLineIcon,
  QueueListIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
  XMarkIcon,
  ChevronDownIcon
} from '@heroicons/vue/24/outline'

// Components
import AppLayout from '@/Layouts/AppLayout.vue'
import StandardPageLayout from '@/Layouts/StandardPageLayout.vue'
import FilterSection from '@/Components/Layout/FilterSection.vue'
import MultiSelect from '@/Components/UI/MultiSelect.vue'
import UnifiedSelector from '@/Components/UI/UnifiedSelector.vue'
import SimpleAccountUserSelector from '@/Components/UI/SimpleAccountUserSelector.vue'
import EmailSystemSettingsModal from './Components/EmailSystemSettingsModal.vue'
import ProcessingLogsModal from './Components/ProcessingLogsModal.vue'
import SystemHealthModal from './Components/SystemHealthModal.vue'

// Define persistent layout
defineOptions({
  layout: (h, page) => h(AppLayout, () => page)
})

// State
const refreshing = ref(false)
const loadingQueuedEmails = ref(false)
const checkingDomainMappings = ref(false)
const queuedEmails = ref([])
const showEmailPreview = ref(false)
const showAssignModal = ref(false)
const selectedEmail = ref(null)
const emailToAssign = ref(null)
const selectedAccountForAssignment = ref(null)
const selectedAccountIdForAssignment = ref(null)
const assigningEmail = ref(false)
const healthChecking = ref(false)
const queueRefreshing = ref(false)
const retryingJobs = ref(false)
const clearingJobs = ref(false)
const retrievingEmails = ref(false)
const chartPeriod = ref('24h')

const showSettingsModal = ref(false)
const showLogsModal = ref(false)
const showHealthModal = ref(false)
const showRetrievalOptions = ref(false)

// Chart reference
const emailChart = ref(null)

// Filters
const filters = reactive({
  timeRange: '24h',
  statuses: [],
  serviceType: '',
  showQueuedEmails: false
})

// Filter Options
const statusOptions = [
  { value: 'success', label: 'Success' },
  { value: 'failed', label: 'Failed' },
  { value: 'processing', label: 'Processing' },
  { value: 'pending', label: 'Pending' }
]

const chartPeriods = [
  { value: '1h', label: '1H' },
  { value: '24h', label: '24H' },
  { value: '7d', label: '7D' },
  { value: '30d', label: '30D' }
]

// Query Client
const queryClient = useQueryClient()

// API Queries
const { data: dashboardDataRaw } = useQuery({
  queryKey: ['email-admin-dashboard', filters.timeRange],
  queryFn: () => fetchDashboardData(),
  refetchInterval: 30000, // Refresh every 30 seconds
  retry: false, // Don't retry on 404/500 errors
  onError: (error) => {
    console.warn('Dashboard data API not available:', error)
  }
})

const { data: queueDataRaw } = useQuery({
  queryKey: ['email-queue-status'],
  queryFn: fetchQueueStatus,
  refetchInterval: 10000, // Refresh every 10 seconds
  retry: false,
  onError: (error) => {
    console.warn('Queue status API not available:', error)
  }
})

// Extract queue data with proper defaults - FIXED to match API structure
const queueData = computed(() => {
  if (!queueDataRaw.value?.data) {
    return { pending: 0, processing: 0, failed: 0 }
  }
  
  const apiData = queueDataRaw.value.data
  const totalPending = Object.values(apiData.queues || {}).reduce((sum, queue) => sum + (queue.pending || 0), 0)
  const totalFailed = apiData.failed_jobs?.total || 0
  
  return {
    pending: totalPending,
    processing: 0, // API doesn't provide this directly
    failed: totalFailed
  }
})

// Use system health from dashboard data instead of separate query
const systemHealth = computed(() => {
  if (dashboardDataRaw.value?.data?.system_health) {
    return dashboardDataRaw.value.data.system_health
  }
  return { 
    system_active: false, 
    fully_configured: false, 
    incoming_enabled: false, 
    outgoing_enabled: false, 
    email_provider: 'none', // incoming provider
    outgoing_provider: 'none',
    use_same_provider: false,
    timestamp_source: 'original',
    timestamp_timezone: 'preserve',
    domain_mappings_count: 0,
    active_domain_mappings: 0
  }
})

// Disable command stats for now since the endpoint doesn't exist
const commandStats = ref([])

// Safe data accessors with proper defaults - FIXED to match API structure
const dashboardData = computed(() => {
  if (!dashboardDataRaw.value?.data) {
    return {
      metrics: {
        total_emails: 0,
        email_change: 0,
        success_rate: 0,
        commands_executed: 0
      },
      queue: {
        pending: 0,
        failed: 0
      }
    }
  }
  
  const apiData = dashboardDataRaw.value.data
  
  return {
    metrics: {
      total_emails: apiData.overview?.total_emails_processed || 0,
      email_change: 0, // Not provided by API yet
      success_rate: apiData.performance?.success_rate || 0,
      commands_executed: apiData.overview?.commands_executed || 0
    },
    queue: {
      pending: apiData.queue_health?.pending_jobs || 0,
      failed: apiData.queue_health?.failed_jobs || 0
    }
  }
})

// Recent activity from API data - FIXED to use real API data
const recentActivity = computed(() => {
  return dashboardDataRaw.value?.data?.recent_activity || []
})

// System alerts from API data - FIXED to use real API data  
const systemAlerts = computed(() => {
  return dashboardDataRaw.value?.data?.alerts || []
})

const recentErrors = ref([])

// API Functions
async function fetchDashboardData() {
  const params = new URLSearchParams({ 
    time_range: filters.timeRange,
    service_type: filters.serviceType,
    statuses: filters.statuses.join(',')
  })
  const response = await fetch(`/api/email-admin/dashboard?${params}`)
  if (!response.ok) throw new Error('Failed to fetch dashboard data')
  return response.json()
}

async function fetchQueueStatus() {
  const response = await fetch('/api/email-admin/queue-status')
  if (!response.ok) throw new Error('Failed to fetch queue status')
  return response.json()
}


async function fetchCommandStats() {
  const response = await fetch('/api/email-commands/stats')
  if (!response.ok) throw new Error('Failed to fetch command stats')
  return response.json()
}

// Methods
async function loadQueuedEmails() {
  if (!filters.showQueuedEmails) {
    queuedEmails.value = []
    return
  }
  
  loadingQueuedEmails.value = true
  try {
    const response = await fetch('/api/email-admin/queued-emails')
    if (response.ok) {
      const result = await response.json()
      queuedEmails.value = result.data || []
    }
  } catch (error) {
    console.error('Error loading queued emails:', error)
  } finally {
    loadingQueuedEmails.value = false
  }
}

async function refreshQueuedEmails() {
  await loadQueuedEmails()
}

function previewEmail(email) {
  selectedEmail.value = email
  showEmailPreview.value = true
}

function closeEmailPreview() {
  showEmailPreview.value = false
  selectedEmail.value = null
}

function assignEmail(email) {
  emailToAssign.value = email
  selectedAccountForAssignment.value = null
  showEmailPreview.value = false
  showAssignModal.value = true
}

function closeAssignModal() {
  showAssignModal.value = false
  emailToAssign.value = null
  selectedAccountForAssignment.value = null
  selectedAccountIdForAssignment.value = null
}

async function confirmAssignment() {
  if (!emailToAssign.value || !selectedAccountIdForAssignment.value) return
  
  assigningEmail.value = true
  try {
    const response = await fetch(`/api/email-admin/emails/${emailToAssign.value.email_id}/assign`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        account_id: selectedAccountIdForAssignment.value,
        reason: 'Manual assignment from admin dashboard'
      })
    })
    
    if (response.ok) {
      // Remove from queued emails list
      queuedEmails.value = queuedEmails.value.filter(email => email.email_id !== emailToAssign.value.email_id)
      closeAssignModal()
      alert('Email assigned successfully and will be processed.')
    } else {
      const error = await response.json()
      alert(`Assignment failed: ${error.message}`)
    }
  } catch (error) {
    console.error('Error assigning email:', error)
    alert('Assignment failed: Network error')
  } finally {
    assigningEmail.value = false
  }
}

async function rejectEmail(email) {
  const reason = prompt('Please provide a reason for rejecting this email:')
  if (!reason) return
  
  try {
    const response = await fetch(`/api/email-admin/emails/${email.email_id}/reject`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({ reason })
    })
    
    if (response.ok) {
      // Remove from queued emails list
      queuedEmails.value = queuedEmails.value.filter(e => e.email_id !== email.email_id)
      if (showEmailPreview.value) {
        closeEmailPreview()
      }
      alert('Email rejected successfully.')
    } else {
      const error = await response.json()
      alert(`Rejection failed: ${error.message}`)
    }
  } catch (error) {
    console.error('Error rejecting email:', error)
    alert('Rejection failed: Network error')
  }
}

function getDomainFromEmail(email) {
  if (!email) return 'Unknown'
  const match = email.match(/@([^>]+)/)
  return match ? match[1] : 'Unknown'
}

function formatDateTime(date) {
  if (!date) return ''
  return new Date(date).toLocaleString()
}

async function refreshData() {
  refreshing.value = true
  try {
    await queryClient.invalidateQueries({ queryKey: ['email-admin-dashboard'] })
    await queryClient.invalidateQueries({ queryKey: ['email-queue-status'] })
    await queryClient.invalidateQueries({ queryKey: ['email-system-health'] })
    if (filters.showQueuedEmails) {
      await loadQueuedEmails()
    }
  } finally {
    refreshing.value = false
  }
}

async function runHealthCheck() {
  healthChecking.value = true
  try {
    // Just refresh the dashboard data - the health check is included in dashboard API
    await queryClient.invalidateQueries({ queryKey: ['email-admin-dashboard'] })
  } finally {
    healthChecking.value = false
  }
}

async function refreshQueueData() {
  queueRefreshing.value = true
  try {
    await queryClient.invalidateQueries({ queryKey: ['email-queue-status'] })
  } finally {
    queueRefreshing.value = false
  }
}

async function retryFailedJobs() {
  retryingJobs.value = true
  try {
    const response = await fetch('/api/email-admin/retry-processing', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({ retry_all: true })
    })
    
    if (response.ok) {
      await refreshQueueData()
      // Show success notification
    }
  } catch (error) {
    console.error('Error retrying failed jobs:', error)
    // Show error notification
  } finally {
    retryingJobs.value = false
  }
}

async function clearFailedJobs() {
  if (!confirm('Are you sure you want to clear all failed jobs? This action cannot be undone.')) {
    return
  }

  clearingJobs.value = true
  try {
    const response = await fetch('/api/email-admin/failed-jobs', {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    })
    
    if (response.ok) {
      await refreshQueueData()
      // Show success notification
    }
  } catch (error) {
    console.error('Error clearing failed jobs:', error)
    // Show error notification
  } finally {
    clearingJobs.value = false
  }
}

function loadDashboardData() {
  queryClient.invalidateQueries({ queryKey: ['email-admin-dashboard'] })
}

function loadChartData() {
  // Load chart data based on chartPeriod
  // This would integrate with a charting library like Chart.js
}

function showProcessingLogs() {
  showLogsModal.value = true
}

function showSystemHealth() {
  showHealthModal.value = true
}

function showUnprocessedEmails() {
  // Enable the unprocessed emails filter to show the section
  filters.showQueuedEmails = true
  
  // Scroll to the unprocessed emails section
  setTimeout(() => {
    const unprocessedSection = document.querySelector('[data-section="unprocessed-emails"]')
    if (unprocessedSection) {
      unprocessedSection.scrollIntoView({ behavior: 'smooth' })
    }
  }, 100)
}

async function checkForDomainMappings() {
  if (queuedEmails.value.length === 0) {
    alert('No unprocessed emails to check.')
    return
  }

  checkingDomainMappings.value = true
  try {
    const response = await fetch('/api/email-admin/process-emails', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        operation: 'check_mappings'
      })
    })
    
    if (response.ok) {
      const result = await response.json()
      if (result.processed_count > 0) {
        alert(`Successfully reprocessed ${result.processed_count} email(s) that now match domain mappings.`)
        // Refresh the queued emails list to remove processed ones
        await refreshQueuedEmails()
      } else {
        alert('No unprocessed emails matched existing domain mappings.')
      }
    } else {
      const error = await response.json()
      alert(`Failed to check domain mappings: ${error.message || 'Unknown error'}`)
    }
  } catch (error) {
    console.error('Error checking domain mappings:', error)
    alert('Failed to check domain mappings: Network error')
  } finally {
    checkingDomainMappings.value = false
  }
}

function exportLogs() {
  // Implement log export functionality
  const params = new URLSearchParams(filters)
  window.open(`/api/email-admin/export-logs?${params}`, '_blank')
}

function testEmailSystem() {
  // Implement email system testing
  // This could open a modal for testing email configurations
}

async function manualEmailRetrieval(mode = 'test') {
  // Close dropdown
  showRetrievalOptions.value = false
  
  const modeDescription = mode === 'test' ? 'test mode (retrieve and log only)' : 'process mode (create tickets and users)'
  if (!confirm(`Manually trigger email retrieval in ${modeDescription}? This will check for new emails even if the email service is disabled.`)) {
    return
  }

  retrievingEmails.value = true
  try {
    const response = await fetch('/api/email-admin/process-emails', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        operation: 'retrieve',
        mode: mode,
        limit: 10 // Default limit
      })
    })
    
    if (response.ok) {
      const result = await response.json()
      
      // Build detailed success message
      let message = `Email retrieval (${result.mode} mode) completed successfully:\n`
      message += `• ${result.emails_retrieved || 0} emails retrieved\n`
      message += `• ${result.emails_processed || 0} emails processed`
      
      if (result.mode === 'process') {
        message += `\n• ${result.tickets_created || 0} tickets created`
        
        if (result.processing_details && result.processing_details.length > 0) {
          message += '\n\nProcessing Details:'
          result.processing_details.forEach((detail, index) => {
            if (index < 3) { // Show first 3
              message += `\n• ${detail.subject} (${detail.from}) - ${detail.success ? 'Success' : 'Failed'}`
            }
          })
          if (result.processing_details.length > 3) {
            message += `\n... and ${result.processing_details.length - 3} more`
          }
        }
      }
      
      alert(message)
      // Refresh dashboard data
      await refreshData()
    } else {
      const error = await response.json()
      alert(`Email retrieval failed: ${error.message || 'Unknown error'}`)
    }
  } catch (error) {
    console.error('Error during manual email retrieval:', error)
    alert('Email retrieval failed: Network error')
  } finally {
    retrievingEmails.value = false
  }
}

function dismissAlert(alertIndex) {
  // Since systemAlerts is now a computed property from API data, 
  // we can't directly modify it. This would need to be implemented
  // by storing dismissed alerts state separately or making an API call.
  console.log('Dismiss alert not implemented for API-driven alerts')
}

function formatTimeAgo(date) {
  if (!date) return ''
  const now = new Date()
  const past = new Date(date)
  const diffInSeconds = Math.floor((now - past) / 1000)
  
  if (diffInSeconds < 60) return 'Just now'
  if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`
  if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`
  return `${Math.floor(diffInSeconds / 86400)}d ago`
}

// Initialize chart when mounted - REMOVED mock data, now using real API data
onMounted(() => {
  // Initialize chart if canvas is available
  if (emailChart.value) {
    loadChartData()
  }
  
  // Check URL parameter for showing unprocessed emails automatically
  const urlParams = new URLSearchParams(window.location.search)
  if (urlParams.get('showUnprocessed') === 'true') {
    filters.showQueuedEmails = true
  }
  
  // Load queued emails if filter is enabled
  if (filters.showQueuedEmails) {
    loadQueuedEmails()
  }
})
</script>