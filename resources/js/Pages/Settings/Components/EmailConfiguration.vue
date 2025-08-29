<template>
  <div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-semibold text-gray-900">Email System Configuration</h2>
          <p class="text-gray-600 mt-2">
            Configure email services, processing, user creation, and domain routing.
            <a :href="route('settings.email.domain-mappings')" class="text-indigo-600 hover:text-indigo-500">
              Manage domain mappings
            </a>
            for advanced email routing rules.
          </p>
        </div>
        
        <div class="flex items-center space-x-3">
          <!-- Email System Monitoring -->
          <a
            :href="route('admin.email.dashboard')"
            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
          >
            <ChartBarIcon class="w-4 h-4 mr-2" />
            View Monitoring Dashboard
          </a>
          
          <!-- Manage Unprocessed Emails (if any exist) -->
          <div v-if="config?.unprocessed_emails_count > 0" class="relative">
            <a
              :href="route('admin.email.dashboard') + '?showUnprocessed=true'"
              class="inline-flex items-center px-4 py-2 border border-yellow-300 text-sm font-medium rounded-md text-yellow-700 bg-yellow-50 hover:bg-yellow-100"
            >
              <ExclamationTriangleIcon class="w-4 h-4 mr-2" />
              Manage Unprocessed Emails
              <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-200 text-yellow-800">
                {{ config.unprocessed_emails_count }}
              </span>
            </a>
          </div>

          <!-- Test Configuration Button -->
          <button
            @click="testConfiguration"
            :disabled="testing"
            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="testing" class="flex items-center">
              <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Testing...
            </span>
            <span v-else class="flex items-center">
              <CheckCircleIcon class="w-4 h-4 mr-2" />
              Test Configuration
            </span>
          </button>

          <!-- Save Button -->
          <button
            @click="saveConfiguration"
            :disabled="saving || !hasChanges"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="saving" class="flex items-center">
              <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Saving...
            </span>
            <span v-else>Save Configuration</span>
          </button>
        </div>
      </div>
    </div>

    <!-- System Status Alert -->
    <div v-if="config.system_active" class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
      <div class="flex items-start">
        <CheckCircleIcon class="h-5 w-5 text-green-400 mt-0.5" />
        <div class="ml-3">
          <h3 class="text-sm font-medium text-green-800">Email System Active</h3>
          <p class="text-sm text-green-700 mt-1">
            Your email system is configured and actively processing emails.
            <span v-if="config.last_tested_at">
              Last tested: {{ formatDateTime(config.last_tested_at) }}
            </span>
          </p>
        </div>
      </div>
    </div>

    <div v-else-if="!config.system_active && (config.incoming_enabled || config.outgoing_enabled)" 
         class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
      <div class="flex items-start">
        <ExclamationTriangleIcon class="h-5 w-5 text-yellow-400 mt-0.5" />
        <div class="ml-3">
          <h3 class="text-sm font-medium text-yellow-800">Email System Configured but Inactive</h3>
          <p class="text-sm text-yellow-700 mt-1">
            Your email services are configured but the system is not active. Enable the system below to start processing emails.
          </p>
        </div>
      </div>
    </div>


    <!-- Tab Navigation -->
    <div class="mb-6">
      <TabNavigation
        v-model="activeTab"
        :tabs="emailTabs"
        variant="underline"
        @tab-change="handleTabChange"
      />
    </div>

    <!-- Main Configuration Form -->
    <form @submit.prevent="saveConfiguration" class="space-y-8">
      
      <!-- Basic Configuration Tab -->
      <div v-show="activeTab === 'basic'" class="space-y-8">
        <!-- System Activation -->
      <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">System Status</h3>
          <p class="text-sm text-gray-500 mt-1">Control the overall email system activation</p>
        </div>
        <div class="p-6">
          <div class="flex items-center justify-between">
            <div>
              <label for="system-active" class="text-base font-medium text-gray-900">
                Email System Active
              </label>
              <p class="text-sm text-gray-500">
                Enable this to activate email processing. Both incoming and outgoing services must be configured.
              </p>
            </div>
            <button
              type="button"
              @click="form.system_active = !form.system_active"
              :class="[
                'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2',
                form.system_active ? 'bg-indigo-600' : 'bg-gray-200'
              ]"
            >
              <span
                :class="[
                  'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                  form.system_active ? 'translate-x-5' : 'translate-x-0'
                ]"
              />
            </button>
          </div>
        </div>
      </div>
          
        </div>
        
        <!-- Incoming Email Configuration Tab -->
        <div v-show="activeTab === 'incoming'" class="space-y-8">

      <!-- Incoming Email Configuration -->
      <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-medium text-gray-900">Incoming Email Service</h3>
              <p class="text-sm text-gray-500 mt-1">Configure how your application receives emails</p>
            </div>
            <button
              type="button"
              @click="form.incoming_enabled = !form.incoming_enabled"
              :class="[
                'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2',
                form.incoming_enabled ? 'bg-indigo-600' : 'bg-gray-200'
              ]"
            >
              <span
                :class="[
                  'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                  form.incoming_enabled ? 'translate-x-5' : 'translate-x-0'
                ]"
              />
            </button>
          </div>
        </div>
        
        <div v-show="form.incoming_enabled" class="p-6 space-y-6">
          
          <!-- Incoming Email Provider Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Incoming Email Provider</label>
            <select 
              v-model="form.email_provider" 
              @change="applyProviderDefaults('incoming')"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
              <option value="">Select a provider...</option>
              <option value="imap">Generic IMAP</option>
              <option value="gmail">Gmail</option>
              <option value="outlook">Outlook/Office 365</option>
              <option value="m365">Microsoft 365 (Graph API)</option>
            </select>
          </div>

          <!-- IMAP Configuration -->
          <div v-show="form.email_provider === 'imap'" class="space-y-6">
            <!-- Server Configuration -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">IMAP Server</label>
                <input 
                  v-model="form.imap_host" 
                  type="text" 
                  placeholder="imap.gmail.com"
                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Port</label>
                <input 
                  v-model="form.imap_port" 
                  type="number" 
                  placeholder="993"
                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
            </div>

            <!-- Authentication -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Username/Email</label>
                <input 
                  v-model="form.imap_username" 
                  type="email" 
                  placeholder="your-email@gmail.com"
                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <div class="relative">
                  <input 
                    v-model="form.imap_password" 
                    :type="showIncomingPassword ? 'text' : 'password'"
                    placeholder="Your password or app password"
                    autocomplete="email-password"
                    data-form-type="imap"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 pr-10"
                  />
                  <button
                    type="button"
                    @click="showIncomingPassword = !showIncomingPassword"
                    class="absolute inset-y-0 right-0 flex items-center pr-3"
                  >
                    <EyeIcon v-if="!showIncomingPassword" class="h-4 w-4 text-gray-400" />
                    <EyeSlashIcon v-else class="h-4 w-4 text-gray-400" />
                  </button>
                </div>
              </div>
            </div>

            <!-- Advanced Settings -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Encryption</label>
                <select 
                  v-model="form.imap_encryption" 
                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                  <option value="ssl">SSL</option>
                  <option value="tls">TLS</option>
                  <option value="starttls">STARTTLS</option>
                  <option value="none">None</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Folder</label>
                <input 
                  v-model="form.imap_folder" 
                  type="text" 
                  placeholder="INBOX"
                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
            </div>

            <!-- Test IMAP Connection -->
            <div>
              <button
                type="button"
                @click="testImap"
                :disabled="testingImap"
                class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span v-if="testingImap" class="flex items-center">
                  <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Testing IMAP...
                </span>
                <span v-else>Test IMAP Connection</span>
              </button>
            </div>
          </div>

          <!-- Microsoft 365 Configuration -->
          <div v-show="form.email_provider === 'm365'" class="space-y-6">
            
            <!-- M365 Setup Instructions -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg">
              <div class="p-4">
                <button
                  type="button"
                  @click="showM365Instructions = !showM365Instructions"
                  class="w-full flex items-center justify-between text-left focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded"
                >
                  <div class="flex items-start">
                    <svg class="h-5 w-5 text-blue-400 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                      <h4 class="text-sm font-medium text-blue-800">Microsoft 365 Setup Instructions</h4>
                      <p class="text-xs text-blue-600 mt-1">Click to {{ showM365Instructions ? 'hide' : 'show' }} Azure AD app registration guide</p>
                    </div>
                  </div>
                  <svg 
                    class="h-5 w-5 text-blue-400 transform transition-transform duration-200"
                    :class="{ 'rotate-180': showM365Instructions }"
                    fill="none" 
                    stroke="currentColor" 
                    viewBox="0 0 24 24"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                  </svg>
                </button>
              </div>

              <!-- Collapsible Instructions Content -->
              <div 
                v-show="showM365Instructions" 
                class="border-t border-blue-200 p-4 bg-white rounded-b-lg"
              >
                <div class="space-y-4 text-sm text-gray-700">
                  <div class="bg-blue-50 p-3 rounded-md">
                    <p class="text-blue-800 font-medium mb-2">Quick Setup Summary:</p>
                    <ol class="list-decimal list-inside space-y-1 text-xs text-blue-700">
                      <li>Create Azure AD App Registration at <a href="https://portal.azure.com" target="_blank" class="text-blue-600 hover:text-blue-500 underline">Azure Portal</a></li>
                      <li>Get Tenant ID and Client ID from app overview</li>
                      <li>Create Client Secret and copy the value</li>
                      <li>Add Mail.Read and Mail.ReadWrite permissions</li>
                      <li>Grant admin consent for the organization</li>
                    </ol>
                  </div>

                  <div>
                    <h5 class="font-medium mb-2 text-gray-800">Step 1: Create Azure AD App Registration</h5>
                    <ul class="ml-4 list-disc space-y-1 text-xs">
                      <li>Go to <a href="https://portal.azure.com" target="_blank" class="text-blue-600 hover:text-blue-500 underline">Azure Portal</a> → Azure Active Directory → App registrations</li>
                      <li>Click "New registration"</li>
                      <li>Name: "Service Vault Email Integration"</li>
                      <li>Supported account types: "Accounts in this organizational directory only"</li>
                      <li>Redirect URI: Leave blank</li>
                      <li>Click "Register"</li>
                    </ul>
                  </div>
                  
                  <div>
                    <h5 class="font-medium mb-2 text-gray-800">Step 2: Get Application Details</h5>
                    <ul class="ml-4 list-disc space-y-1 text-xs">
                      <li>Copy the "Application (client) ID" - this is your <strong>Client ID</strong></li>
                      <li>Copy the "Directory (tenant) ID" - this is your <strong>Tenant ID</strong></li>
                    </ul>
                  </div>
                  
                  <div>
                    <h5 class="font-medium mb-2 text-gray-800">Step 3: Create Client Secret</h5>
                    <ul class="ml-4 list-disc space-y-1 text-xs">
                      <li>Go to "Certificates & secrets" → "Client secrets"</li>
                      <li>Click "New client secret"</li>
                      <li>Description: "Service Vault Email Access"</li>
                      <li>Expires: Choose appropriate duration (24 months recommended)</li>
                      <li>Copy the secret VALUE (not the ID) - this is your <strong>Client Secret</strong></li>
                    </ul>
                  </div>
                  
                  <div>
                    <h5 class="font-medium mb-2 text-gray-800">Step 4: Configure API Permissions</h5>
                    <ul class="ml-4 list-disc space-y-1 text-xs">
                      <li>Go to "API permissions" → "Add a permission"</li>
                      <li>Select "Microsoft Graph" → "Application permissions"</li>
                      <li>Add these permissions:
                        <ul class="ml-4 list-disc mt-1">
                          <li><code class="bg-gray-100 px-1 rounded">Mail.Read</code> - Read mail in all mailboxes</li>
                          <li><code class="bg-gray-100 px-1 rounded">Mail.ReadWrite</code> - Read and write mail in all mailboxes</li>
                        </ul>
                      </li>
                      <li class="text-red-600 font-medium">Click "Grant admin consent for [your organization]"</li>
                    </ul>
                  </div>
                  
                  <div class="border-t pt-3">
                    <h5 class="font-medium mb-2 text-gray-800">Troubleshooting:</h5>
                    <ul class="ml-4 list-disc space-y-1 text-xs">
                      <li><strong>Authentication failed:</strong> Verify Tenant ID, Client ID, and Client Secret are correct</li>
                      <li><strong>Insufficient privileges:</strong> Ensure admin consent was granted for the permissions</li>
                      <li><strong>Mailbox not found:</strong> Verify the mailbox email address exists and is accessible</li>
                      <li><strong>Folders not loading:</strong> Test the M365 connection first, then try loading folders</li>
                    </ul>
                  </div>
                  
                  <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
                    <p class="text-yellow-800 text-xs"><strong>⚠️ Security Note:</strong> This app will have access to read emails from the specified mailbox. Use a dedicated service account mailbox for ticket processing.</p>
                  </div>
                </div>
              </div>
            </div>


            <!-- Graph API Configuration -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tenant ID</label>
                <input 
                  v-model="form.m365_tenant_id" 
                  type="text" 
                  placeholder="12345678-1234-1234-1234-123456789012"
                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
                <p class="text-xs text-gray-500 mt-1">Your Azure AD tenant ID</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Client ID</label>
                <input 
                  v-model="form.m365_client_id" 
                  type="text" 
                  placeholder="12345678-1234-1234-1234-123456789012"
                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
                <p class="text-xs text-gray-500 mt-1">Application (client) ID from Azure AD</p>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Client Secret</label>
                <div class="relative">
                  <input 
                    v-model="form.m365_client_secret" 
                    :type="showM365Secret ? 'text' : 'password'"
                    placeholder="Your client secret from Azure AD"
                    autocomplete="new-password"
                    data-form-type="other"
                    data-lpignore="true"
                    spellcheck="false"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 pr-10"
                  />
                  <button
                    type="button"
                    @click="showM365Secret = !showM365Secret"
                    class="absolute inset-y-0 right-0 flex items-center pr-3"
                  >
                    <EyeIcon v-if="!showM365Secret" class="h-4 w-4 text-gray-400" />
                    <EyeSlashIcon v-else class="h-4 w-4 text-gray-400" />
                  </button>
                </div>
                <p class="text-xs text-gray-500 mt-1">Client secret from Azure AD app registration</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mailbox</label>
                <input 
                  v-model="form.m365_mailbox" 
                  type="email" 
                  placeholder="support@yourcompany.com"
                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
                <p class="text-xs text-gray-500 mt-1">Email address of the mailbox to monitor</p>
              </div>
            </div>

            <!-- Folder Selection -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <div class="flex items-center justify-between mb-1">
                  <label class="block text-sm font-medium text-gray-700">Folder</label>
                  <button
                    type="button"
                    @click="loadM365Folders"
                    :disabled="loadingFolders || !canLoadFolders"
                    class="text-xs text-indigo-600 hover:text-indigo-500 disabled:text-gray-400 disabled:cursor-not-allowed"
                  >
                    {{ loadingFolders ? 'Loading...' : 'Load Folders' }}
                  </button>
                </div>
                
                <!-- Custom Folder Picker -->
                <div class="relative">
                  <button
                    type="button"
                    @click="showFolderDropdown = !showFolderDropdown"
                    class="w-full text-left px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    :class="{ 'ring-2 ring-indigo-500 border-indigo-500': showFolderDropdown }"
                  >
                    <span v-if="selectedFolderDisplay" class="font-mono text-sm">{{ selectedFolderDisplay }}</span>
                    <span v-else class="text-gray-500">Select a folder...</span>
                    <svg class="w-5 h-5 float-right mt-0.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                  </button>
                  
                  <!-- Dropdown Menu -->
                  <div 
                    v-show="showFolderDropdown" 
                    class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-64 overflow-y-auto"
                    @click.stop
                  >
                    <!-- Search Box -->
                    <div class="p-2 border-b border-gray-200">
                      <input
                        v-model="folderSearchQuery"
                        type="text"
                        placeholder="Search folders..."
                        class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                      />
                    </div>
                    
                    <!-- Default Inbox Option -->
                    <div
                      @click="selectFolder('inbox', '📧 Inbox')"
                      class="px-3 py-2 hover:bg-gray-100 cursor-pointer font-mono text-sm border-b border-gray-100"
                      :class="{ 'bg-indigo-50': form.m365_folder_id === 'inbox' }"
                    >
                      📧 Inbox
                    </div>
                    
                    <!-- Folder List -->
                    <div v-for="folder in filteredFolders" :key="folder.id">
                      <div
                        @click="selectFolder(folder.id, folder)"
                        class="hover:bg-gray-100 cursor-pointer font-mono text-sm flex items-center relative"
                        :class="{
                          'bg-indigo-50': form.m365_folder_id === folder.id,
                          'bg-gray-50': folder.level > 0
                        }"
                        :style="{ 
                          paddingTop: '8px',
                          paddingBottom: '8px',
                          paddingLeft: Math.max(12 + (folder.level || 0) * 20, 12) + 'px',
                          paddingRight: '12px'
                        }"
                      >
                        <!-- Tree Structure -->
                        <span v-if="folder.level > 0" class="text-gray-400 mr-2 select-none">
                          {{ '\u00A0'.repeat((folder.level - 1) * 2) }}├─
                        </span>
                        
                        <!-- Folder Icon -->
                        <span class="mr-2">{{ getFolderIcon(folder.original_name || folder.name) }}</span>
                        
                        <!-- Folder Name -->
                        <span class="flex-1">{{ folder.original_name || folder.name }}</span>
                        
                        <!-- Message Counts -->
                        <span v-if="folder.total_count > 0" class="text-xs text-gray-500 ml-2 flex-shrink-0">
                          ({{ folder.unread_count }}/{{ folder.total_count }})
                        </span>
                      </div>
                    </div>
                    
                    <!-- No Results -->
                    <div v-if="filteredFolders.length === 0 && folderSearchQuery" class="px-3 py-4 text-gray-500 text-sm text-center">
                      No folders found matching "{{ folderSearchQuery }}"
                    </div>
                  </div>
                  
                  <!-- Click Outside Handler -->
                  <div 
                    v-if="showFolderDropdown" 
                    class="fixed inset-0 z-40" 
                    @click="showFolderDropdown = false"
                  ></div>
                </div>
                
                <p class="text-xs text-gray-500 mt-1">Select the folder to monitor for incoming emails. Use search to find specific folders.</p>
              </div>
            </div>

            <!-- Test M365 Connection -->
            <div class="flex space-x-3">
              <button
                type="button"
                @click="testM365"
                :disabled="testingM365"
                class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span v-if="testingM365" class="flex items-center">
                  <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Testing M365...
                </span>
                <span v-else>Test M365 Connection</span>
              </button>
              
              <!-- Test Email Retrieval -->
              <button
                type="button"
                @click="testM365EmailRetrieval"
                :disabled="testingEmailRetrieval || !canTestEmailRetrieval"
                class="inline-flex items-center px-4 py-2 border border-indigo-600 text-sm font-medium rounded-md text-indigo-600 bg-white hover:bg-indigo-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span v-if="testingEmailRetrieval" class="flex items-center">
                  <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Testing Retrieval...
                </span>
                <span v-else>Test Retrieval</span>
              </button>
            </div>
          </div>

        </div>
      </div>

      <!-- Test Email Results -->
      <div v-show="form.email_provider === 'm365' && testEmails.length > 0" class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Latest Emails from Selected Folder</h3>
          <p class="text-sm text-gray-500 mt-1">Last {{ testEmails.length }} email(s) retrieved from {{ form.m365_folder_name || 'selected folder' }}</p>
        </div>
        <div class="p-6">
          <div class="space-y-4">
            <div v-for="(email, index) in testEmails" :key="email.id" class="border border-gray-200 rounded-lg p-4">
              <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                  <!-- Email Header -->
                  <div class="flex items-center space-x-3 mb-2">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                          :class="email.is_read ? 'bg-gray-100 text-gray-800' : 'bg-blue-100 text-blue-800'">
                      {{ email.is_read ? 'Read' : 'Unread' }}
                    </span>
                    <span v-if="email.has_attachments" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                      📎 Attachments
                    </span>
                  </div>
                  
                  <!-- Subject -->
                  <h4 class="text-lg font-medium text-gray-900 truncate" :title="email.subject">
                    {{ email.subject }}
                  </h4>
                  
                  <!-- From -->
                  <div class="mt-1 text-sm text-gray-600">
                    <span class="font-medium">From:</span>
                    {{ email.from_name }}
                    <span class="text-gray-500">&lt;{{ email.from_address }}&gt;</span>
                  </div>
                  
                  <!-- Date -->
                  <div class="mt-1 text-sm text-gray-500">
                    <span class="font-medium">Received:</span>
                    {{ email.received_formatted }}
                  </div>
                  
                  <!-- Body Preview -->
                  <div class="mt-3 text-sm text-gray-700 bg-gray-50 rounded-md p-3">
                    <span class="font-medium text-gray-800">Preview:</span>
                    <p class="mt-1 line-clamp-3">{{ email.body_preview || '(No preview available)' }}</p>
                  </div>
                </div>
                
                <!-- Email Number -->
                <div class="ml-4 flex-shrink-0">
                  <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-800 text-sm font-medium">
                    {{ index + 1 }}
                  </span>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Clear Results Button -->
          <div class="mt-6 pt-4 border-t border-gray-200">
            <button
              type="button"
              @click="testEmails = []"
              class="text-sm text-gray-500 hover:text-gray-700"
            >
              Clear Results
            </button>
          </div>
        </div>
      </div>

        </div>
        
        <!-- Processing Configuration Tab -->
        <div v-show="activeTab === 'processing'" class="space-y-8">
          <EmailProcessing
            :settings="emailProcessingSettings"
            :loading="saving"
            @update="updateEmailProcessingSettings"
          />
        </div>

        <!-- Legacy Processing Config (remove after migration) -->
        <div v-show="false" class="space-y-8">
        
          <!-- Email Processing Settings -->
          <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900">Email Processing Settings</h3>
              <p class="text-sm text-gray-500 mt-1">Configure how incoming emails are processed by the system</p>
            </div>
            <div class="p-6 space-y-6">
              
              <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="flex items-center justify-between">
                  <div>
                    <label class="text-sm font-medium text-gray-900">Auto-create Tickets</label>
                    <p class="text-xs text-gray-500">Create tickets from incoming emails</p>
                  </div>
                  <button
                    type="button"
                    @click="form.auto_create_tickets = !form.auto_create_tickets"
                    :class="[
                      'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2',
                      form.auto_create_tickets ? 'bg-indigo-600' : 'bg-gray-200'
                    ]"
                  >
                    <span
                      :class="[
                        'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                        form.auto_create_tickets ? 'translate-x-5' : 'translate-x-0'
                      ]"
                    />
                  </button>
                </div>

                <div class="flex items-center justify-between">
                  <div>
                    <label class="text-sm font-medium text-gray-900">Process Commands</label>
                    <p class="text-xs text-gray-500">Execute commands in emails</p>
                  </div>
                  <button
                    type="button"
                    @click="form.process_commands = !form.process_commands"
                    :class="[
                      'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2',
                      form.process_commands ? 'bg-indigo-600' : 'bg-gray-200'
                    ]"
                  >
                    <span
                      :class="[
                        'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                        form.process_commands ? 'translate-x-5' : 'translate-x-0'
                      ]"
                    />
                  </button>
                </div>

                <div class="flex items-center justify-between">
                  <div>
                    <label class="text-sm font-medium text-gray-900">Send Confirmations</label>
                    <p class="text-xs text-gray-500">Send confirmation emails</p>
                  </div>
                  <button
                    type="button"
                    @click="form.send_confirmations = !form.send_confirmations"
                    :class="[
                      'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2',
                      form.send_confirmations ? 'bg-indigo-600' : 'bg-gray-200'
                    ]"
                  >
                    <span
                      :class="[
                        'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                        form.send_confirmations ? 'translate-x-5' : 'translate-x-0'
                      ]"
                    />
                  </button>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Max Retry Attempts</label>
                <input 
                  v-model="form.max_retries" 
                  type="number" 
                  min="0" 
                  max="10"
                  class="block w-32 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
                <p class="text-xs text-gray-500 mt-1">Number of times to retry failed email processing</p>
              </div>

            </div>
          </div>
        
      <!-- Email Retrieval Settings -->
      <div v-show="form.incoming_enabled" class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Email Retrieval Settings</h3>
          <p class="text-sm text-gray-500 mt-1">Configure which emails to retrieve from the server</p>
        </div>
        <div class="p-6 space-y-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Email Retrieval Mode</label>
            <select 
              v-model="form.email_retrieval_mode" 
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
              <option value="unread_only">Unread Only (Default - Most Efficient)</option>
              <option value="recent">Recent Emails (Last 7 Days)</option>
              <option value="all">All Emails (Use with Caution)</option>
            </select>
            <p class="text-xs text-gray-500 mt-1">
              <strong>Unread Only:</strong> Only processes new, unread emails (recommended)<br>
              <strong>Recent:</strong> Processes all emails from the last 7 days<br>
              <strong>All:</strong> Processes all emails in the folder (may cause duplicates)
            </p>
          </div>
        </div>
      </div>

      <!-- Email Processing Actions -->
      <div v-show="form.incoming_enabled" class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Post-Processing Actions</h3>
          <p class="text-sm text-gray-500 mt-1">Configure what happens to emails after they're processed into tickets</p>
        </div>
        <div class="p-6 space-y-6">
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Action After Processing</label>
            <select 
              v-model="form.post_processing_action" 
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
              <option value="mark_read">Mark as Read (Keep in folder)</option>
              <option value="move_folder">Move to Another Folder</option>
              <option value="delete">Delete Email</option>
            </select>
          </div>

          <!-- Move to Folder Options -->
          <div v-show="form.post_processing_action === 'move_folder'" class="space-y-4">
            <div v-if="form.email_provider === 'imap'">
              <label class="block text-sm font-medium text-gray-700 mb-1">Target Folder Name</label>
              <input 
                v-model="form.move_to_folder_name" 
                type="text" 
                placeholder="Processed"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
              <p class="text-xs text-gray-500 mt-1">IMAP folder name (e.g., "Processed", "Archive", "INBOX/Processed")</p>
            </div>
            
            <div v-else-if="form.email_provider === 'm365'">
              <div class="flex items-center justify-between mb-1">
                <label class="block text-sm font-medium text-gray-700">Target Folder</label>
                <button
                  type="button"
                  @click="loadM365Folders"
                  :disabled="loadingFolders || !canLoadFolders"
                  class="text-xs text-indigo-600 hover:text-indigo-500 disabled:text-gray-400 disabled:cursor-not-allowed"
                >
                  {{ loadingFolders ? 'Loading...' : 'Refresh Folders' }}
                </button>
                <div v-if="foldersLastLoaded" class="text-xs text-gray-500 mt-1">
                  Last refreshed: {{ foldersLastLoaded.toLocaleTimeString() }}
                </div>
              </div>
              
              <!-- Custom Move-to Folder Picker -->
              <div class="relative">
                <button
                  type="button"
                  @click="showMoveFolderDropdown = !showMoveFolderDropdown"
                  class="w-full text-left px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                  :class="{ 'ring-2 ring-indigo-500 border-indigo-500': showMoveFolderDropdown }"
                >
                  <span v-if="selectedMoveFolderDisplay" class="font-mono text-sm">{{ selectedMoveFolderDisplay }}</span>
                  <span v-else class="text-gray-500">Select destination folder...</span>
                  <svg class="w-5 h-5 float-right mt-0.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                  </svg>
                </button>
                
                <!-- Dropdown Menu -->
                <div 
                  v-show="showMoveFolderDropdown" 
                  class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-64 overflow-y-auto"
                  @click.stop
                >
                  <!-- Search Box -->
                  <div class="p-2 border-b border-gray-200">
                    <input
                      v-model="moveFolderSearchQuery"
                      type="text"
                      placeholder="Search destination folders..."
                      class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                    />
                  </div>
                  
                  <!-- Folder List -->
                  <div v-for="folder in filteredMoveFolders" :key="folder.id">
                    <div
                      @click="selectMoveFolder(folder.id, folder)"
                      class="hover:bg-gray-100 cursor-pointer font-mono text-sm flex items-center relative"
                      :class="{
                        'bg-indigo-50': form.move_to_folder_id === folder.id,
                        'bg-gray-50': folder.level > 0
                      }"
                      :style="{ 
                        paddingTop: '8px',
                        paddingBottom: '8px',
                        paddingLeft: Math.max(12 + (folder.level || 0) * 20, 12) + 'px',
                        paddingRight: '12px'
                      }"
                    >
                      <!-- Debug Level Display -->
                      <span class="text-xs text-red-500 mr-1" v-if="folder.level > 0">[{{ folder.level }}]</span>
                      
                      <!-- Tree Structure -->
                      <span v-if="folder.level > 0" class="text-gray-400 mr-2 select-none">
                        {{ '\u00A0'.repeat((folder.level - 1) * 2) }}├─
                      </span>
                      
                      <!-- Folder Icon -->
                      <span class="mr-2">{{ getFolderIcon(folder.original_name || folder.name) }}</span>
                      
                      <!-- Folder Name -->
                      <span class="flex-1">{{ folder.original_name || folder.name }}</span>
                      
                      <!-- Message Counts -->
                      <span v-if="folder.total_count > 0" class="text-xs text-gray-500 ml-2 flex-shrink-0">
                        ({{ folder.unread_count }}/{{ folder.total_count }})
                      </span>
                    </div>
                  </div>
                  
                  <!-- No Results -->
                  <div v-if="filteredMoveFolders.length === 0 && moveFolderSearchQuery" class="px-3 py-4 text-gray-500 text-sm text-center">
                    No folders found matching "{{ moveFolderSearchQuery }}"
                  </div>
                </div>
                
                <!-- Click Outside Handler -->
                <div 
                  v-if="showMoveFolderDropdown" 
                  class="fixed inset-0 z-40" 
                  @click="showMoveFolderDropdown = false"
                ></div>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- Timestamp Processing Options -->
      <div v-show="form.incoming_enabled" class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Timestamp Processing</h3>
          <p class="text-sm text-gray-500 mt-1">Configure which timestamp to use when creating tickets from emails</p>
        </div>
        <div class="p-6 space-y-6">
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Ticket Creation Time</label>
            <select 
              v-model="form.timestamp_source" 
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
              <option value="service_vault">Service Vault Processing Time</option>
              <option value="original">Original Email Timestamp</option>
            </select>
            <div class="mt-2 text-sm text-gray-600">
              <div v-if="form.timestamp_source === 'service_vault'" class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <div>
                  <p class="font-medium text-gray-700">Service Vault Processing Time (Recommended)</p>
                  <p class="text-xs text-gray-500 mt-1">
                    Uses the timestamp when Service Vault processed the email. This ensures accurate chronological order of tickets and prevents timestamp manipulation.
                  </p>
                </div>
              </div>
              <div v-else class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
                <div>
                  <p class="font-medium text-gray-700">Original Email Timestamp</p>
                  <p class="text-xs text-gray-500 mt-1">
                    Uses the original timestamp from the email headers. May result in tickets appearing out of chronological processing order if emails are processed with delays.
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Timezone Handling -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Timezone Handling</label>
            <select 
              v-model="form.timestamp_timezone" 
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
              <option value="preserve">Preserve Original Timezone</option>
              <option value="convert_local">Convert to Server Timezone</option>
              <option value="convert_utc">Convert to UTC</option>
            </select>
            <p class="text-xs text-gray-500 mt-1">
              How to handle timezone information from email timestamps when using original email timestamp
            </p>
          </div>

        </div>
      </div>

        </div>
        
        <!-- Outgoing Email Configuration Tab -->
        <div v-show="activeTab === 'outgoing'" class="space-y-8">
        
      <!-- Outgoing Email Configuration -->
      <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-medium text-gray-900">Outgoing Email Service</h3>
              <p class="text-sm text-gray-500 mt-1">Configure how your application sends emails</p>
            </div>
            <button
              type="button"
              @click="form.outgoing_enabled = !form.outgoing_enabled"
              :class="[
                'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2',
                form.outgoing_enabled ? 'bg-indigo-600' : 'bg-gray-200'
              ]"
            >
              <span
                :class="[
                  'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                  form.outgoing_enabled ? 'translate-x-5' : 'translate-x-0'
                ]"
              />
            </button>
          </div>
        </div>
        
        <div v-show="form.outgoing_enabled" class="p-6 space-y-6">
          
          <!-- Provider Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Outgoing Email Provider</label>
            <select 
              v-model="form.outgoing_provider" 
              @change="applyProviderDefaults('outgoing')"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
              <option value="">Select a provider...</option>
              <option v-if="form.email_provider === 'm365'" value="same_as_incoming">📧 Use same as incoming (Microsoft 365)</option>
              <option value="smtp">Generic SMTP</option>
              <option value="gmail">Gmail</option>
              <option value="outlook">Outlook/Office 365</option>
              <option value="m365">Microsoft 365 (Graph API)</option>
              <option value="ses">Amazon SES</option>
              <option value="sendgrid">SendGrid</option>
              <option value="postmark">Postmark</option>
              <option value="mailgun">Mailgun</option>
            </select>
            <p v-if="form.email_provider === 'm365'" class="text-xs text-gray-500 mt-1">
              When using Microsoft 365 for incoming email, you can use the same configuration for outgoing emails
            </p>
          </div>

          <!-- Same as Incoming Info -->
          <div v-show="form.outgoing_provider === 'same_as_incoming'" class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-green-800">Using Microsoft 365 for Both Incoming and Outgoing</h3>
                <div class="mt-2 text-sm text-green-700">
                  <p>The same Microsoft 365 configuration from the Incoming tab will be used for sending emails:</p>
                  <ul class="list-disc list-inside mt-1 space-y-1">
                    <li>Tenant ID: <span class="font-mono">{{ form.m365_tenant_id || '(not configured)' }}</span></li>
                    <li>Client ID: <span class="font-mono">{{ form.m365_client_id || '(not configured)' }}</span></li>
                    <li>Mailbox: <span class="font-mono">{{ form.m365_mailbox || '(not configured)' }}</span></li>
                  </ul>
                  <p class="mt-2 text-xs">Configure these settings in the <strong>Incoming</strong> tab.</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Server Configuration -->
          <div v-show="form.outgoing_provider !== 'same_as_incoming'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">SMTP Server</label>
              <input 
                v-model="form.smtp_host" 
                type="text" 
                placeholder="smtp.gmail.com"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Port</label>
              <input 
                v-model="form.smtp_port" 
                type="number" 
                placeholder="587"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>
          </div>

          <!-- Authentication -->
          <div v-show="form.outgoing_provider !== 'same_as_incoming'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Username/Email</label>
              <input 
                v-model="form.smtp_username" 
                type="email" 
                placeholder="your-email@gmail.com"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
              <div class="relative">
                <input 
                  v-model="form.smtp_password" 
                  :type="showOutgoingPassword ? 'text' : 'password'"
                  placeholder="Your password or app password"
                  autocomplete="email-password"
                  data-form-type="smtp"
                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 pr-10"
                />
                <button
                  type="button"
                  @click="showOutgoingPassword = !showOutgoingPassword"
                  class="absolute inset-y-0 right-0 flex items-center pr-3"
                >
                  <EyeIcon v-if="!showOutgoingPassword" class="h-4 w-4 text-gray-400" />
                  <EyeSlashIcon v-else class="h-4 w-4 text-gray-400" />
                </button>
              </div>
            </div>
          </div>

          <!-- Encryption -->
          <div v-show="form.outgoing_provider !== 'same_as_incoming'">
            <label class="block text-sm font-medium text-gray-700 mb-1">Encryption</label>
            <select 
              v-model="form.smtp_encryption" 
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
              <option value="tls">TLS</option>
              <option value="ssl">SSL</option>
              <option value="starttls">STARTTLS</option>
              <option value="none">None</option>
            </select>
          </div>

        </div>
      </div>

      <!-- From Address Configuration -->
      <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Sender Information</h3>
          <p class="text-sm text-gray-500 mt-1">Configure the default sender information for outgoing emails</p>
        </div>
        <div class="p-6 space-y-6">
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">From Email Address *</label>
              <input 
                v-model="form.from_address" 
                type="email" 
                placeholder="noreply@yourcompany.com"
                required
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">From Name</label>
              <input 
                v-model="form.from_name" 
                type="text" 
                placeholder="Your Company Support"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Reply-To Address</label>
            <input 
              v-model="form.reply_to_address" 
              type="email" 
              placeholder="support@yourcompany.com"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
            <p class="text-xs text-gray-500 mt-1">If different from the From address</p>
          </div>

        </div>
      </div>

        
        </div>

    </form>

    <!-- Test Results -->
    <div v-if="testResults" class="mt-8 bg-white shadow rounded-lg">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Test Results</h3>
        <p class="text-sm text-gray-500 mt-1">Latest configuration test results</p>
      </div>
      <div class="p-6">
        <div class="space-y-4">
          <!-- Overall Status -->
          <div class="flex items-center">
            <CheckCircleIcon v-if="testResults.overall" class="h-5 w-5 text-green-500 mr-2" />
            <XCircleIcon v-else class="h-5 w-5 text-red-500 mr-2" />
            <span class="font-medium" :class="testResults.overall ? 'text-green-900' : 'text-red-900'">
              Overall: {{ testResults.overall ? 'Success' : 'Failed' }}
            </span>
          </div>

          <!-- Incoming Test -->
          <div v-if="testResults.incoming" class="flex items-start">
            <CheckCircleIcon v-if="testResults.incoming.success" class="h-5 w-5 text-green-500 mr-2 mt-0.5" />
            <XCircleIcon v-else class="h-5 w-5 text-red-500 mr-2 mt-0.5" />
            <div>
              <div class="font-medium" :class="testResults.incoming.success ? 'text-green-900' : 'text-red-900'">
                Incoming: {{ testResults.incoming.message }}
              </div>
              <div class="text-sm text-gray-600">{{ testResults.incoming.details }}</div>
            </div>
          </div>

          <!-- Outgoing Test -->
          <div v-if="testResults.outgoing" class="flex items-start">
            <CheckCircleIcon v-if="testResults.outgoing.success" class="h-5 w-5 text-green-500 mr-2 mt-0.5" />
            <XCircleIcon v-else class="h-5 w-5 text-red-500 mr-2 mt-0.5" />
            <div>
              <div class="font-medium" :class="testResults.outgoing.success ? 'text-green-900' : 'text-red-900'">
                Outgoing: {{ testResults.outgoing.message }}
              </div>
              <div class="text-sm text-gray-600">{{ testResults.outgoing.details }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { 
  CheckCircleIcon, 
  ExclamationTriangleIcon, 
  EyeIcon, 
  EyeSlashIcon, 
  XCircleIcon,
  ChartBarIcon
} from '@heroicons/vue/24/outline'
import TabNavigation from '@/Components/Layout/TabNavigation.vue'
import EmailProcessing from './EmailProcessing.vue'
import { useToast } from "@/Composables/useToast"

// Props
const props = defineProps({
  config: {
    type: Object,
    default: () => ({})
  }
})

// Toast notifications
const { success, error, promise, apiError } = useToast()

// State
const saving = ref(false)
const testing = ref(false)
const testingImap = ref(false)
const testingM365 = ref(false)
const loadingFolders = ref(false)
const showIncomingPassword = ref(false)
const showOutgoingPassword = ref(false)
const showM365Secret = ref(false)
const testResults = ref(null)
const m365Folders = ref([])
const showM365Instructions = ref(false)
const showFolderDropdown = ref(false)
const folderSearchQuery = ref('')
const selectedFolderDisplay = ref('')
const showMoveFolderDropdown = ref(false)
const moveFolderSearchQuery = ref('')
const selectedMoveFolderDisplay = ref('')
const foldersLastLoaded = ref(null)
const testingEmailRetrieval = ref(false)
const testEmails = ref([])

// Tab management
const activeTab = ref('basic')

// Form data
const form = useForm({
  // Email provider type
  email_provider: 'imap', // incoming email provider
  outgoing_provider: 'smtp', // outgoing email provider
  use_same_provider: false, // use same provider for outgoing when M365
  
  // System status
  system_active: false, // master email system toggle
  incoming_enabled: true,
  outgoing_enabled: true,
  
  // SMTP/Outgoing configuration
  smtp_host: '',
  smtp_port: 587,
  smtp_username: '',
  smtp_password: '',
  smtp_encryption: 'tls',
  from_address: '',
  from_name: '',
  reply_to_address: '',
  
  // IMAP configuration
  imap_host: '',
  imap_port: 993,
  imap_username: '',
  imap_password: '',
  imap_encryption: 'ssl',
  imap_folder: 'INBOX',
  
  // Microsoft 365 configuration
  m365_tenant_id: '',
  m365_client_id: '',
  m365_client_secret: '',
  m365_mailbox: '',
  m365_folder_id: 'inbox',
  m365_folder_name: 'Inbox',
  
  // Post-processing actions
  post_processing_action: 'mark_read',
  move_to_folder_id: '',
  move_to_folder_name: '',
  
  // Email retrieval settings
  email_retrieval_mode: 'unread_only',
  
  // Email processing settings
  enable_email_to_ticket: true,
  auto_create_tickets: true, // alias for enable_email_to_ticket
  auto_create_users: true,
  default_role_for_new_users: '',
  require_approval_for_new_users: true,
  process_commands: true,
  send_confirmations: true,
  max_retries: 3,
  
  // New unified email processing fields
  enable_email_processing: true,
  unmapped_domain_strategy: 'assign_default_account',
  default_account_id: '',
  default_role_template_id: '',
  require_email_verification: true,
  require_admin_approval: true,
  
  // Timestamp processing
  timestamp_source: 'original', // 'service_vault' or 'original'
  timestamp_timezone: 'preserve', // 'preserve', 'convert_local', or 'convert_utc'
})

// Computed
const config = computed(() => props.config)
const hasChanges = computed(() => form.isDirty)
const canLoadFolders = computed(() => {
  return form.email_provider === 'm365' && 
         form.m365_tenant_id && 
         form.m365_client_id && 
         form.m365_client_secret && 
         form.m365_mailbox
})

const canTestEmailRetrieval = computed(() => {
  return canLoadFolders.value && form.m365_folder_id
})

const filteredFolders = computed(() => {
  if (!folderSearchQuery.value) {
    return m365Folders.value
  }
  
  const query = folderSearchQuery.value.toLowerCase()
  return m365Folders.value.filter(folder => {
    const name = (folder.original_name || folder.name).toLowerCase()
    return name.includes(query)
  })
})

const filteredMoveFolders = computed(() => {
  if (!moveFolderSearchQuery.value) {
    return m365Folders.value
  }
  
  const query = moveFolderSearchQuery.value.toLowerCase()
  return m365Folders.value.filter(folder => {
    const name = (folder.original_name || folder.name).toLowerCase()
    return name.includes(query)
  })
})

// Email processing settings for the EmailProcessing component
const emailProcessingSettings = computed(() => {
  return {
    accounts: config.value?.accounts || [],
    role_templates: config.value?.role_templates || [],
    domain_mappings_preview: config.value?.domain_mappings_preview || [],
    user_stats: config.value?.user_stats || null,
    email_processing_settings: {
      enable_email_processing: form.enable_email_processing ?? config.value?.enable_email_processing ?? true,
      auto_create_tickets: form.auto_create_tickets ?? config.value?.auto_create_tickets ?? true,
      auto_create_users: form.auto_create_users ?? config.value?.auto_create_users ?? true,
      unmapped_domain_strategy: form.unmapped_domain_strategy ?? config.value?.unmapped_domain_strategy ?? 'assign_default_account',
      default_account_id: form.default_account_id ?? config.value?.default_account_id ?? '',
      default_role_template_id: form.default_role_template_id ?? config.value?.default_role_template_id ?? '',
      require_email_verification: form.require_email_verification ?? config.value?.require_email_verification ?? true,
      require_admin_approval: form.require_admin_approval ?? config.value?.require_admin_approval ?? true,
    }
  }
})

// Email configuration tabs
const emailTabs = [
  {
    id: 'basic',
    name: 'Basic Configuration',
    icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'
  },
  {
    id: 'incoming',
    name: 'Incoming Email',
    icon: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'
  },
  {
    id: 'outgoing',
    name: 'Outgoing Email',
    icon: 'M12 19l9 2-9-18-9 18 9-2zm0 0v-8'
  },
  {
    id: 'processing',
    name: 'Processing & Actions',
    icon: 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z'
  }
]

// Handle tab changes
const handleTabChange = (tabId) => {
  activeTab.value = tabId
}

// Provider defaults
const providerDefaults = {
  outgoing: {
    smtp: { port: 587, encryption: 'tls' },
    gmail: { host: 'smtp.gmail.com', port: 587, encryption: 'tls' },
    outlook: { host: 'smtp-mail.outlook.com', port: 587, encryption: 'starttls' },
    ses: { port: 587, encryption: 'tls' },
    sendgrid: { host: 'smtp.sendgrid.net', port: 587, encryption: 'tls' },
    postmark: { host: 'smtp.postmarkapp.com', port: 587, encryption: 'tls' },
    mailgun: { host: 'smtp.mailgun.org', port: 587, encryption: 'tls' },
  },
  incoming: {
    imap: { port: 993, encryption: 'ssl', folder: 'INBOX' },
    gmail: { host: 'imap.gmail.com', port: 993, encryption: 'ssl', folder: 'INBOX' },
    outlook: { host: 'outlook.office365.com', port: 993, encryption: 'ssl', folder: 'INBOX' },
    exchange: { port: 993, encryption: 'ssl', folder: 'INBOX' },
  },
}

// Methods
const applyProviderDefaults = (type) => {
  const provider = type === 'incoming' ? form.email_provider : form.outgoing_provider
  
  // Handle "same as incoming" selection
  if (type === 'outgoing' && provider === 'same_as_incoming') {
    form.use_same_provider = true
    return
  } else {
    form.use_same_provider = false
  }
  
  const defaults = providerDefaults[type][provider]
  
  if (defaults) {
    Object.keys(defaults).forEach(key => {
      let formKey
      if (type === 'incoming') {
        formKey = `imap_${key}`
      } else {
        formKey = `smtp_${key}`
      }
      
      if (form[formKey] !== undefined) {
        form[formKey] = defaults[key]
      }
    })
  }
}

const saveConfiguration = async () => {
  saving.value = true
  console.log('Starting email configuration save...')
  try {
    const result = await promise(
      fetch('/api/settings/email', {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(form.data())
      }).then(async response => {
        console.log('Save response status:', response.status)
        if (!response.ok) {
          const errorData = await response.json()
          console.error('Save failed response:', errorData)
          if (errorData.errors) {
            form.setError(errorData.errors)
          }
          throw new Error(errorData.message || 'Failed to save email configuration')
        }
        return response.json()
      }).then(result => {
        console.log('Save result:', result)
        // Mark form as clean since we just saved
        form.defaults()
        return result
      }),
      {
        loading: 'Saving email configuration...',
        success: (result) => {
          console.log('Success toast should show with:', result)
          return result.message || 'Email configuration saved successfully!'
        },
        error: (err) => {
          console.error('Error toast should show with:', err)
          return `Failed to save configuration: ${err.message}`
        }
      }
    )
    console.log('Promise resolved successfully:', result)
  } catch (err) {
    console.error('Save failed with exception:', err)
  } finally {
    saving.value = false
  }
}

// Update email processing settings
const updateEmailProcessingSettings = (processingSettings) => {
  console.log('Updating email processing settings:', processingSettings)
  
  // Update form with processing settings
  Object.keys(processingSettings).forEach(key => {
    if (form.hasOwnProperty(key)) {
      form[key] = processingSettings[key]
    }
  })
  
  // Map settings to existing form fields
  if (processingSettings.enable_email_processing !== undefined) {
    form.system_active = processingSettings.enable_email_processing
    form.enable_email_processing = processingSettings.enable_email_processing
  }
  if (processingSettings.auto_create_tickets !== undefined) {
    form.auto_create_tickets = processingSettings.auto_create_tickets
  }
  if (processingSettings.auto_create_users !== undefined) {
    form.auto_create_users = processingSettings.auto_create_users
  }
  if (processingSettings.unmapped_domain_strategy !== undefined) {
    form.unmapped_domain_strategy = processingSettings.unmapped_domain_strategy
  }
  if (processingSettings.default_account_id !== undefined) {
    form.default_account_id = processingSettings.default_account_id
  }
  if (processingSettings.default_role_template_id !== undefined) {
    form.default_role_template_id = processingSettings.default_role_template_id
  }
  if (processingSettings.require_email_verification !== undefined) {
    form.require_email_verification = processingSettings.require_email_verification
  }
  if (processingSettings.require_admin_approval !== undefined) {
    form.require_admin_approval = processingSettings.require_admin_approval
  }
  
  // Trigger save to backend
  saveConfiguration()
}

const testConfiguration = async () => {
  testing.value = true
  try {
    const response = await fetch('/api/email-system/test', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify(form.data())
    })
    
    if (!response.ok) {
      const errorData = await response.json()
      error(`Email test failed: ${errorData.message || 'Unknown error'}`)
      return
    }
    
    const result = await response.json()
    testResults.value = result
    
    // Analyze results and show appropriate toast
    const successMessages = []
    const errorMessages = []
    
    if (result.incoming) {
      if (result.incoming.success) {
        successMessages.push('incoming connection')
      } else {
        errorMessages.push(`incoming: ${result.incoming.message}`)
      }
    }
    
    if (result.outgoing) {
      if (result.outgoing.success) {
        successMessages.push('outgoing connection')
      } else {
        errorMessages.push(`outgoing: ${result.outgoing.message}`)
      }
    }
    
    // Show appropriate toast based on results
    if (successMessages.length > 0 && errorMessages.length === 0) {
      success(`Email test successful: ${successMessages.join(', ')} verified`)
    } else if (successMessages.length > 0 && errorMessages.length > 0) {
      error(`Email test partially failed - Success: ${successMessages.join(', ')}. Errors: ${errorMessages.join(', ')}`)
    } else if (errorMessages.length > 0) {
      error(`Email test failed: ${errorMessages.join(', ')}`)
    } else {
      error('Email test completed but no results received')
    }
    
  } catch (err) {
    console.error('Test failed:', err)
    apiError(err, 'Email configuration test failed')
  } finally {
    testing.value = false
  }
}

const formatDateTime = (dateTime) => {
  if (!dateTime) return ''
  return new Date(dateTime).toLocaleString()
}

// Test IMAP connection
const testImap = async () => {
  testingImap.value = true
  try {
    const response = await fetch('/api/settings/email/test-imap', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        imap_host: form.imap_host,
        imap_port: form.imap_port,
        imap_username: form.imap_username,
        imap_password: form.imap_password,
        imap_encryption: form.imap_encryption,
        imap_folder: form.imap_folder
      })
    })
    
    const result = await response.json()
    if (response.ok && result.success) {
      success(`IMAP test successful! ${result.message}`)
    } else {
      error(`IMAP test failed: ${result.message}`)
    }
  } catch (err) {
    console.error('IMAP test failed:', err)
    apiError(err, 'IMAP test failed')
  } finally {
    testingImap.value = false
  }
}

// Test M365 connection
const testM365 = async () => {
  testingM365.value = true
  try {
    const response = await fetch('/api/settings/email/test-m365', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        m365_tenant_id: form.m365_tenant_id,
        m365_client_id: form.m365_client_id,
        m365_client_secret: form.m365_client_secret,
        m365_mailbox: form.m365_mailbox
      })
    })
    
    const result = await response.json()
    if (response.ok && result.success) {
      success(`M365 test successful! ${result.message}`)
    } else {
      error(`M365 test failed: ${result.message}`)
    }
  } catch (err) {
    console.error('M365 test failed:', err)
    apiError(err, 'M365 test failed')
  } finally {
    testingM365.value = false
  }
}

// Load M365 folders
const loadM365Folders = async () => {
  if (!canLoadFolders.value) {
    error('Please fill in all M365 credentials first')
    return
  }

  loadingFolders.value = true
  try {
    const response = await fetch(`/api/settings/email/m365-folders?_=${Date.now()}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        m365_tenant_id: form.m365_tenant_id,
        m365_client_id: form.m365_client_id,
        m365_client_secret: form.m365_client_secret,
        m365_mailbox: form.m365_mailbox,
        force_refresh: true  // Force fresh data
      })
    })
    
    const result = await response.json()
    if (response.ok && result.success) {
      m365Folders.value = result.folders
      foldersLastLoaded.value = new Date()
      
      // Update the selected folder display with latest folder data
      if (form.m365_folder_id && form.m365_folder_id !== 'inbox') {
        const selectedFolder = result.folders.find(f => f.id === form.m365_folder_id)
        if (selectedFolder) {
          const icon = getFolderIcon(selectedFolder.original_name || selectedFolder.name)
          const counts = selectedFolder.total_count > 0 ? ` (${selectedFolder.unread_count}/${selectedFolder.total_count})` : ''
          const indent = selectedFolder.level > 0 ? '  '.repeat(selectedFolder.level) : ''
          selectedFolderDisplay.value = `${indent}${icon} ${selectedFolder.original_name || selectedFolder.name}${counts}`
        }
      }
    } else {
      error(`Failed to load folders: ${result.message}`)
    }
  } catch (err) {
    console.error('Failed to load folders:', err)
    apiError(err, 'Failed to load folders')
  } finally {
    loadingFolders.value = false
  }
}

// Test email retrieval from selected M365 folder
const testM365EmailRetrieval = async () => {
  if (!canTestEmailRetrieval.value) {
    error('Please select a folder first')
    return
  }

  testingEmailRetrieval.value = true
  testEmails.value = []
  
  try {
    const response = await fetch(`/api/settings/email/test-m365-retrieval?_=${Date.now()}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        m365_tenant_id: form.m365_tenant_id,
        m365_client_id: form.m365_client_id,
        m365_client_secret: form.m365_client_secret,
        m365_mailbox: form.m365_mailbox,
        m365_folder_id: form.m365_folder_id
      })
    })
    
    const result = await response.json()
    if (response.ok && result.success) {
      testEmails.value = result.emails
      if (result.emails.length === 0) {
        error('No emails found in the selected folder')
      } else {
        success(`Successfully retrieved ${result.emails.length} email(s) from the selected folder`)
      }
    } else {
      error(`Failed to retrieve emails: ${result.message}`)
    }
  } catch (err) {
    console.error('Failed to retrieve emails:', err)
    apiError(err, 'Failed to retrieve emails')
  } finally {
    testingEmailRetrieval.value = false
  }
}

// Select folder from custom dropdown
const selectFolder = (folderId, folderData) => {
  form.m365_folder_id = folderId
  
  if (folderId === 'inbox') {
    form.m365_folder_name = 'Inbox'
    selectedFolderDisplay.value = '📧 Inbox'
  } else {
    const folderName = folderData.original_name || folderData.name
    form.m365_folder_name = folderName
    
    // Create display text with icon and counts
    const icon = getFolderIcon(folderName)
    const counts = folderData.total_count > 0 ? ` (${folderData.unread_count}/${folderData.total_count})` : ''
    const indent = folderData.level > 0 ? '  '.repeat(folderData.level) : ''
    selectedFolderDisplay.value = `${indent}${icon} ${folderName}${counts}`
  }
  
  showFolderDropdown.value = false
  folderSearchQuery.value = ''
}

// Update M365 folder name when selection changes (legacy method - kept for compatibility)
const updateM365FolderName = () => {
  const selectedFolder = m365Folders.value.find(f => f.id === form.m365_folder_id)
  if (selectedFolder) {
    form.m365_folder_name = selectedFolder.original_name || selectedFolder.name
    // Also update display
    const icon = getFolderIcon(selectedFolder.original_name || selectedFolder.name)
    const counts = selectedFolder.total_count > 0 ? ` (${selectedFolder.unread_count}/${selectedFolder.total_count})` : ''
    const indent = selectedFolder.level > 0 ? '  '.repeat(selectedFolder.level) : ''
    selectedFolderDisplay.value = `${indent}${icon} ${selectedFolder.original_name || selectedFolder.name}${counts}`
  }
}

// Select move-to folder from custom dropdown
const selectMoveFolder = (folderId, folderData) => {
  form.move_to_folder_id = folderId
  const folderName = folderData.original_name || folderData.name
  form.move_to_folder_name = folderName
  
  // Create display text with icon and counts
  const icon = getFolderIcon(folderName)
  const counts = folderData.total_count > 0 ? ` (${folderData.unread_count}/${folderData.total_count})` : ''
  const indent = folderData.level > 0 ? '  '.repeat(folderData.level) : ''
  selectedMoveFolderDisplay.value = `${indent}${icon} ${folderName}${counts}`
  
  showMoveFolderDropdown.value = false
  moveFolderSearchQuery.value = ''
}

// Update move-to folder name when selection changes (legacy method - kept for compatibility)
const updateMoveFolderName = () => {
  const selectedFolder = m365Folders.value.find(f => f.id === form.move_to_folder_id)
  if (selectedFolder) {
    form.move_to_folder_name = selectedFolder.original_name || selectedFolder.name
    // Also update display
    const icon = getFolderIcon(selectedFolder.original_name || selectedFolder.name)
    const counts = selectedFolder.total_count > 0 ? ` (${selectedFolder.unread_count}/${selectedFolder.total_count})` : ''
    const indent = selectedFolder.level > 0 ? '  '.repeat(selectedFolder.level) : ''
    selectedMoveFolderDisplay.value = `${indent}${icon} ${selectedFolder.original_name || selectedFolder.name}${counts}`
  }
}

// Format folder display name with proper hierarchy and icons
const getFolderDisplayName = (folder) => {
  const icon = getFolderIcon(folder.original_name || folder.name.trim())
  const counts = folder.total_count > 0 ? ` (${folder.unread_count}/${folder.total_count})` : ''
  
  // Use non-breaking spaces and tree characters for visual hierarchy in HTML selects
  const level = folder.level || 0
  let prefix = ''
  
  if (level > 0) {
    // Use Unicode tree characters that show in HTML select options
    // ├── (BOX DRAWINGS LIGHT VERTICAL AND RIGHT) for middle children
    // └── (BOX DRAWINGS LIGHT UP AND RIGHT) for last child
    const indent = '\u00A0'.repeat(level * 2) // Non-breaking spaces for indentation
    prefix = indent + '├─\u00A0' // Tree character + non-breaking space
  }
  
  const folderName = folder.original_name || folder.name.trim()
  return `${prefix}${icon}\u00A0${folderName}${counts}`
}

// Get appropriate icon for folder type
const getFolderIcon = (folderName) => {
  const name = folderName.toLowerCase()
  if (name === 'inbox') return '📧'
  if (name === 'sent items' || name === 'sent') return '📤'
  if (name === 'drafts') return '✏️'
  if (name === 'deleted items' || name === 'trash') return '🗑️'
  if (name === 'junk email' || name === 'spam') return '🚫'
  if (name === 'archive') return '📦'
  if (name.includes('calendar')) return '📅'
  if (name.includes('contact')) return '👥'
  if (name.includes('task')) return '✅'
  if (name.includes('note')) return '📝'
  return '📁' // Default folder icon
}

// Initialize form with existing config
const initializeFormData = () => {
  if (config.value) {
    Object.keys(form.data()).forEach(key => {
      if (config.value[key] !== undefined) {
        let value = config.value[key]
        
        // Convert string booleans to actual booleans
        if (typeof value === 'string' && (value === 'true' || value === 'false')) {
          value = value === 'true'
        }
        
        form[key] = value
      }
    })
    
    // Special handling for email provider - set defaults based on existing config
    if (!config.value.email_provider) {
      // If we have M365 settings, default to m365, otherwise imap
      if (config.value.m365_tenant_id || config.value.m365_client_id) {
        form.email_provider = 'm365'
      } else {
        form.email_provider = 'imap'
      }
    }
    
    // Set test results if available
    if (config.value.test_results) {
      testResults.value = config.value.test_results
    }
    
    // Initialize folder display based on saved folder selection
    if (form.m365_folder_id && form.m365_folder_name) {
      if (form.m365_folder_id === 'inbox') {
        selectedFolderDisplay.value = '📧 Inbox'
      } else {
        // For non-inbox folders, create a basic display
        // The proper display will be updated when folders are loaded
        const icon = getFolderIcon(form.m365_folder_name)
        selectedFolderDisplay.value = `${icon} ${form.m365_folder_name}`
      }
    }
    
    console.log('Initialized form data:', form.data())
  }
}

// Watch for config changes (when loaded from API)
watch(config, (newConfig) => {
  if (newConfig) {
    initializeFormData()
  }
}, { immediate: true })

onMounted(() => {
  initializeFormData()
})
</script>