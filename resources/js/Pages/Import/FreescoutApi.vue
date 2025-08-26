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

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <div class="lg:col-span-3 space-y-6">
          <!-- API Profiles Section -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">FreeScout API Profiles</h3>
            <p class="mt-1 text-sm text-gray-500">
              Configure FreeScout instances to import conversations, customers, and mailboxes.
            </p>
          </div>

          <div class="p-6">
            <!-- Profile Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
              <div
                v-for="profile in mockProfiles"
                :key="profile.id"
                class="bg-gray-50 rounded-lg p-4 border border-gray-200 hover:border-gray-300 transition-colors"
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
                      class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10"
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
                          :disabled="profile.status !== 'connected'"
                          :class="[
                            'block px-4 py-2 text-sm w-full text-left',
                            profile.status === 'connected' 
                              ? 'text-gray-700 hover:bg-gray-100' 
                              : 'text-gray-400 cursor-not-allowed'
                          ]"
                        >
                          Configure Import
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
                v-if="mockProfiles.length === 0"
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

        <!-- Import Configuration Section -->
        <div 
          v-if="selectedProfile && showImportConfig"
          class="bg-white overflow-hidden shadow-sm sm:rounded-lg"
        >
          <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-lg font-medium text-gray-900">Import Configuration</h3>
                <p class="mt-1 text-sm text-gray-500">
                  Configure import settings for {{ selectedProfile.name }}
                </p>
              </div>
              <button
                @click="closeImportConfig"
                class="text-gray-400 hover:text-gray-500"
              >
                <XMarkIcon class="w-5 h-5" />
              </button>
            </div>
          </div>

          <!-- This will be the FreescoutImportConfigPanel component -->
          <div class="p-6">
            <FreescoutImportConfigPanel
              v-if="selectedProfile"
              :profile="selectedProfile"
              :mock-data="mockImportData"
              @preview="handlePreviewImport"
              @execute="handleExecuteImport"
            />
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
                      {{ mockImportStats.total_imports }}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
              <div class="text-sm">
                <div class="flex justify-between">
                  <span class="text-gray-500">Successful:</span>
                  <span class="font-medium text-green-600">{{ mockImportStats.successful }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-500">Failed:</span>
                  <span class="font-medium text-red-600">{{ mockImportStats.failed }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-500">In Progress:</span>
                  <span class="font-medium text-yellow-600">{{ mockImportStats.in_progress }}</span>
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
                v-for="activity in mockRecentActivity"
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
    </div>
  </AppLayout>

  <!-- API Profile Modal -->
  <FreescoutApiProfileModal
    v-if="showCreateProfileModal"
    :profile="editingProfile"
    @close="closeProfileModal"
    @save="handleSaveProfile"
  />

  <!-- Import Preview Dialog -->
  <FreescoutImportPreviewDialog
    v-if="showPreviewDialog"
    :profile="previewProfile"
    :config="previewConfig"
    :import-data="mockImportData"
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
    v-if="showExecutionDialog"
    :profile="executionProfile"
    :config="executionConfig"
    @close="closeExecutionDialog"
    @complete="handleExecutionComplete"
    @failed="handleExecutionFailed"
  />
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import FreescoutApiProfileModal from './Components/FreescoutApiProfileModal.vue'
import FreescoutImportConfigPanel from './Components/FreescoutImportConfigPanel.vue'
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

// Progress tracking
const showProgressDialog = ref(false)
const currentImportJob = ref(null)
const importConfig = ref(null)
const progressPollingInterval = ref(null)
const progressDialogRef = ref(null)

// Mock Data
const mockProfiles = ref([
  {
    id: 1,
    name: 'Production FreeScout',
    instance_url: 'https://support.mycompany.com',
    api_key_masked: '****-****-****-7a2f',
    status: 'connected',
    last_tested: '2 minutes ago',
    stats: {
      conversations: '1,247',
      customers: '523',
      mailboxes: '8'
    },
    created_at: '2025-08-20T10:30:00Z'
  },
  {
    id: 2,
    name: 'Staging Environment',
    instance_url: 'https://staging-support.mycompany.com',
    api_key_masked: '****-****-****-9b1c',
    status: 'testing',
    last_tested: '5 minutes ago',
    stats: {
      conversations: '342',
      customers: '156',
      mailboxes: '4'
    },
    created_at: '2025-08-19T14:15:00Z'
  },
  {
    id: 3,
    name: 'Legacy System',
    instance_url: 'https://old-support.mycompany.com',
    api_key_masked: '****-****-****-3x8d',
    status: 'error',
    last_tested: '1 hour ago',
    stats: {
      conversations: 'N/A',
      customers: 'N/A',
      mailboxes: 'N/A'
    },
    created_at: '2025-08-18T09:45:00Z'
  }
])

const mockImportStats = ref({
  total_imports: 12,
  successful: 8,
  failed: 2,
  in_progress: 2
})

const mockRecentActivity = ref([
  {
    id: 1,
    action: 'Import completed',
    profile: 'Production FreeScout',
    status: 'completed',
    timestamp: '5 minutes ago'
  },
  {
    id: 2,
    action: 'Connection test failed',
    profile: 'Legacy System',
    status: 'failed',
    timestamp: '1 hour ago'
  },
  {
    id: 3,
    action: 'Import started',
    profile: 'Staging Environment',
    status: 'in_progress',
    timestamp: '2 hours ago'
  }
])

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

// Methods
const toggleProfileMenu = (profileId) => {
  activeProfileMenu.value = activeProfileMenu.value === profileId ? null : profileId
}

const testConnection = (profile) => {
  console.log('Testing connection for:', profile.name)
  // Mock connection test with loading state
  profile.status = 'testing'
  profile.last_tested = 'testing...'
  
  // Update recent activity
  mockRecentActivity.value.unshift({
    id: Date.now(),
    action: 'Connection test started',
    profile: profile.name,
    status: 'in_progress',
    timestamp: 'just now'
  })
  
  setTimeout(() => {
    const success = Math.random() > 0.3
    profile.status = success ? 'connected' : 'error'
    profile.last_tested = 'just now'
    
    if (success) {
      // Update stats with fresh data
      profile.stats = {
        conversations: (Math.floor(Math.random() * 2000) + 500).toLocaleString(),
        customers: (Math.floor(Math.random() * 800) + 200).toLocaleString(),
        mailboxes: (Math.floor(Math.random() * 10) + 3).toString()
      }
    }
    
    // Update recent activity
    mockRecentActivity.value[0] = {
      id: mockRecentActivity.value[0].id,
      action: success ? 'Connection test passed' : 'Connection test failed',
      profile: profile.name,
      status: success ? 'completed' : 'failed',
      timestamp: 'just now'
    }
  }, 2000)
  
  activeProfileMenu.value = null
}

const configureImport = (profile) => {
  selectedProfile.value = profile
  showImportConfig.value = true
  activeProfileMenu.value = null
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

const deleteProfile = (profile) => {
  if (confirm(`Are you sure you want to delete "${profile.name}"? This action cannot be undone.`)) {
    const index = mockProfiles.value.findIndex(p => p.id === profile.id)
    if (index > -1) {
      mockProfiles.value.splice(index, 1)
    }
  }
  activeProfileMenu.value = null
}

const closeProfileModal = () => {
  showCreateProfileModal.value = false
  editingProfile.value = null
}

const handleSaveProfile = (profileData) => {
  if (editingProfile.value) {
    // Update existing profile
    const index = mockProfiles.value.findIndex(p => p.id === editingProfile.value.id)
    if (index > -1) {
      mockProfiles.value[index] = { ...mockProfiles.value[index], ...profileData }
    }
  } else {
    // Create new profile
    const newProfile = {
      id: Date.now(),
      ...profileData,
      status: 'testing',
      last_tested: 'never',
      stats: { conversations: '0', customers: '0', mailboxes: '0' },
      created_at: new Date().toISOString()
    }
    mockProfiles.value.push(newProfile)
  }
  closeProfileModal()
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
      mockRecentActivity.value.unshift({
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
  mockRecentActivity.value.unshift({
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
        const activityIndex = mockRecentActivity.value.findIndex(
          activity => activity.action === 'Import started' && 
                     activity.profile === selectedProfile.value?.name
        )
        
        if (activityIndex !== -1) {
          mockRecentActivity.value[activityIndex] = {
            ...mockRecentActivity.value[activityIndex],
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
    const activityIndex = mockRecentActivity.value.findIndex(
      activity => activity.action === 'Import started' && 
                 activity.profile === selectedProfile.value?.name
    )
    
    if (activityIndex !== -1) {
      mockRecentActivity.value[activityIndex] = {
        ...mockRecentActivity.value[activityIndex],
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

// Cleanup on component unmount
onUnmounted(() => {
  stopProgressPolling()
})

// Close dropdown when clicking outside
onMounted(() => {
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.relative')) {
      activeProfileMenu.value = null
    }
  })
})
</script>