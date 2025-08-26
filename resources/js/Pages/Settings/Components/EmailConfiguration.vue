<template>
  <div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-semibold text-gray-900">Email System Configuration</h2>
          <p class="text-gray-600 mt-2">
            Configure your application's incoming and outgoing email services.
            <a :href="route('settings.email.domain-mappings')" class="text-indigo-600 hover:text-indigo-500">
              Manage domain mappings
            </a>
            to route emails to business accounts.
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

    <!-- Success Message -->
    <div v-if="saveSuccess" class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
      <div class="flex items-start">
        <CheckCircleIcon class="h-5 w-5 text-green-400 mt-0.5" />
        <div class="ml-3">
          <h3 class="text-sm font-medium text-green-800">Configuration Saved</h3>
          <p class="text-sm text-green-700 mt-1">
            {{ saveMessage }}
          </p>
        </div>
      </div>
    </div>

    <!-- Main Configuration Form -->
    <form @submit.prevent="saveConfiguration" class="space-y-8">
      
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
          
          <!-- Provider Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Email Provider</label>
            <select 
              v-model="form.email_provider" 
              @change="applyProviderDefaults('incoming')"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
              <option value="">Select a provider...</option>
              <option value="imap">IMAP (Traditional Email)</option>
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
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
              <div class="flex items-start">
                <svg class="h-5 w-5 text-blue-400 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
                <div>
                  <h4 class="text-sm font-medium text-blue-800 mb-2">Microsoft 365 Setup Required</h4>
                  <div class="text-sm text-blue-700 space-y-2">
                    <p>To use Microsoft 365 Graph API, you need to create an Azure AD app registration. Follow these steps:</p>
                    
                    <div class="ml-4 space-y-1">
                      <p><strong>1. Create Azure AD App Registration:</strong></p>
                      <ul class="ml-4 list-disc space-y-1 text-xs">
                        <li>Go to <a href="https://portal.azure.com" target="_blank" class="text-blue-600 hover:text-blue-500 underline">Azure Portal</a> → Azure Active Directory → App registrations</li>
                        <li>Click "New registration"</li>
                        <li>Name: "Service Vault Email Integration"</li>
                        <li>Supported account types: "Accounts in this organizational directory only"</li>
                        <li>Redirect URI: Leave blank</li>
                        <li>Click "Register"</li>
                      </ul>
                      
                      <p><strong>2. Get Application Details:</strong></p>
                      <ul class="ml-4 list-disc space-y-1 text-xs">
                        <li>Copy the "Application (client) ID" - this is your Client ID</li>
                        <li>Copy the "Directory (tenant) ID" - this is your Tenant ID</li>
                      </ul>
                      
                      <p><strong>3. Create Client Secret:</strong></p>
                      <ul class="ml-4 list-disc space-y-1 text-xs">
                        <li>Go to "Certificates & secrets" → "Client secrets"</li>
                        <li>Click "New client secret"</li>
                        <li>Description: "Service Vault Email Access"</li>
                        <li>Expires: Choose appropriate duration (24 months recommended)</li>
                        <li>Copy the secret VALUE (not the ID) - this is your Client Secret</li>
                      </ul>
                      
                      <p><strong>4. Configure API Permissions:</strong></p>
                      <ul class="ml-4 list-disc space-y-1 text-xs">
                        <li>Go to "API permissions" → "Add a permission"</li>
                        <li>Select "Microsoft Graph" → "Application permissions"</li>
                        <li>Add these permissions:
                          <ul class="ml-4 list-disc">
                            <li><code class="bg-blue-100 px-1 rounded">Mail.Read</code> - Read mail in all mailboxes</li>
                            <li><code class="bg-blue-100 px-1 rounded">Mail.ReadWrite</code> - Read and write mail in all mailboxes</li>
                          </ul>
                        </li>
                        <li><strong>Important:</strong> Click "Grant admin consent" for your organization</li>
                      </ul>
                    </div>
                    
                    <div class="mt-3 p-2 bg-yellow-50 border border-yellow-200 rounded text-xs">
                      <p class="text-yellow-800"><strong>Security Note:</strong> This app will have access to read emails from the specified mailbox. Ensure you use a dedicated service account mailbox for ticket processing.</p>
                    </div>
                  </div>
                  
                  <div class="mt-3 flex items-center space-x-2">
                    <button
                      type="button"
                      @click="showM365Instructions = !showM365Instructions"
                      class="text-xs text-blue-600 hover:text-blue-500"
                    >
                      {{ showM365Instructions ? 'Hide' : 'Show' }} detailed instructions
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Detailed Instructions (Collapsible) -->
            <div v-show="showM365Instructions" class="bg-gray-50 border border-gray-200 rounded-lg p-4">
              <h4 class="text-sm font-medium text-gray-800 mb-3">Detailed Configuration Guide</h4>
              
              <div class="space-y-4 text-sm text-gray-700">
                <div>
                  <h5 class="font-medium mb-2">Step-by-Step Azure Configuration:</h5>
                  
                  <div class="space-y-3">
                    <div>
                      <p class="font-medium text-gray-800">1. Access Azure Portal</p>
                      <ul class="ml-4 list-disc space-y-1 text-xs">
                        <li>Navigate to <a href="https://portal.azure.com" target="_blank" class="text-indigo-600 hover:text-indigo-500 underline">https://portal.azure.com</a></li>
                        <li>Sign in with your Microsoft 365 administrator account</li>
                        <li>Search for "Azure Active Directory" in the top search bar</li>
                      </ul>
                    </div>
                    
                    <div>
                      <p class="font-medium text-gray-800">2. Create App Registration</p>
                      <ul class="ml-4 list-disc space-y-1 text-xs">
                        <li>In Azure AD, click "App registrations" in the left menu</li>
                        <li>Click "New registration" at the top</li>
                        <li>Fill in:
                          <ul class="ml-4 list-disc">
                            <li><strong>Name:</strong> Service Vault Email Integration</li>
                            <li><strong>Supported account types:</strong> Accounts in this organizational directory only</li>
                            <li><strong>Redirect URI:</strong> Leave blank</li>
                          </ul>
                        </li>
                        <li>Click "Register"</li>
                      </ul>
                    </div>
                    
                    <div>
                      <p class="font-medium text-gray-800">3. Get Required IDs</p>
                      <ul class="ml-4 list-disc space-y-1 text-xs">
                        <li>On the app overview page, copy:
                          <ul class="ml-4 list-disc">
                            <li><strong>Application (client) ID:</strong> Use as Client ID below</li>
                            <li><strong>Directory (tenant) ID:</strong> Use as Tenant ID below</li>
                          </ul>
                        </li>
                      </ul>
                    </div>
                    
                    <div>
                      <p class="font-medium text-gray-800">4. Create Client Secret</p>
                      <ul class="ml-4 list-disc space-y-1 text-xs">
                        <li>Click "Certificates & secrets" in the left menu</li>
                        <li>Under "Client secrets", click "New client secret"</li>
                        <li>Description: "Service Vault Email Access"</li>
                        <li>Expires: 24 months (recommended)</li>
                        <li>Click "Add"</li>
                        <li><strong>Important:</strong> Copy the secret <strong>Value</strong> immediately (it won't be shown again)</li>
                      </ul>
                    </div>
                    
                    <div>
                      <p class="font-medium text-gray-800">5. Configure Permissions</p>
                      <ul class="ml-4 list-disc space-y-1 text-xs">
                        <li>Click "API permissions" in the left menu</li>
                        <li>Click "Add a permission"</li>
                        <li>Select "Microsoft Graph"</li>
                        <li>Select "Application permissions" (not Delegated)</li>
                        <li>Search for and add:
                          <ul class="ml-4 list-disc">
                            <li><strong>Mail.Read:</strong> Read mail in all mailboxes</li>
                            <li><strong>Mail.ReadWrite:</strong> Read and write mail in all mailboxes</li>
                          </ul>
                        </li>
                        <li>Click "Add permissions"</li>
                        <li><strong>Critical:</strong> Click "Grant admin consent for [Your Organization]"</li>
                        <li>Confirm by clicking "Yes"</li>
                      </ul>
                    </div>
                    
                    <div>
                      <p class="font-medium text-gray-800">6. Best Practices</p>
                      <ul class="ml-4 list-disc space-y-1 text-xs">
                        <li>Use a dedicated service mailbox (e.g., support@company.com) rather than a personal mailbox</li>
                        <li>Consider creating a separate folder in the mailbox for processed emails</li>
                        <li>Test the configuration thoroughly before going live</li>
                        <li>Monitor the app's usage in Azure AD regularly</li>
                        <li>Set up appropriate alerting for authentication failures</li>
                      </ul>
                    </div>
                  </div>
                </div>
                
                <div class="border-t pt-4">
                  <h5 class="font-medium mb-2">Troubleshooting Common Issues:</h5>
                  <ul class="ml-4 list-disc space-y-1 text-xs">
                    <li><strong>Authentication failed:</strong> Verify Tenant ID, Client ID, and Client Secret are correct</li>
                    <li><strong>Insufficient privileges:</strong> Ensure admin consent was granted for the permissions</li>
                    <li><strong>Mailbox not found:</strong> Verify the mailbox email address exists and is accessible</li>
                    <li><strong>Folders not loading:</strong> Test the M365 connection first, then try loading folders</li>
                  </ul>
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
            </div>
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
            <label class="block text-sm font-medium text-gray-700 mb-2">Email Provider</label>
            <select 
              v-model="form.outgoing_provider" 
              @change="applyProviderDefaults('outgoing')"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
              <option value="">Select a provider...</option>
              <option value="smtp">Generic SMTP</option>
              <option value="gmail">Gmail</option>
              <option value="outlook">Outlook/Office 365</option>
              <option value="ses">Amazon SES</option>
              <option value="sendgrid">SendGrid</option>
              <option value="postmark">Postmark</option>
              <option value="mailgun">Mailgun</option>
            </select>
          </div>

          <!-- Server Configuration -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">SMTP Server</label>
              <input 
                v-model="form.outgoing_host" 
                type="text" 
                placeholder="smtp.gmail.com"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Port</label>
              <input 
                v-model="form.outgoing_port" 
                type="number" 
                placeholder="587"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>
          </div>

          <!-- Authentication -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Username/Email</label>
              <input 
                v-model="form.outgoing_username" 
                type="email" 
                placeholder="your-email@gmail.com"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
              <div class="relative">
                <input 
                  v-model="form.outgoing_password" 
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
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Encryption</label>
            <select 
              v-model="form.outgoing_encryption" 
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

      <!-- Processing Settings -->
      <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Processing Settings</h3>
          <p class="text-sm text-gray-500 mt-1">Configure how emails are processed by the system</p>
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

// Props
const props = defineProps({
  config: {
    type: Object,
    default: () => ({})
  }
})

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
const saveSuccess = ref(false)
const saveMessage = ref('')
const m365Folders = ref([])
const showM365Instructions = ref(false)
const showFolderDropdown = ref(false)
const folderSearchQuery = ref('')
const selectedFolderDisplay = ref('')
const showMoveFolderDropdown = ref(false)
const moveFolderSearchQuery = ref('')
const selectedMoveFolderDisplay = ref('')

// Form data
const form = useForm({
  // Email provider type
  email_provider: 'imap',
  
  // System status
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
  
  // Email processing settings
  enable_email_to_ticket: true,
  auto_create_users: false,
  default_role_for_new_users: '',
  require_approval_for_new_users: true,
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
  const provider = type === 'incoming' ? form.incoming_provider : form.outgoing_provider
  const defaults = providerDefaults[type][provider]
  
  if (defaults) {
    Object.keys(defaults).forEach(key => {
      const formKey = `${type}_${key}`
      if (form[formKey] !== undefined) {
        form[formKey] = defaults[key]
      }
    })
  }
}

const saveConfiguration = async () => {
  saving.value = true
  try {
    const response = await fetch('/api/settings/email', {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify(form.data())
    })
    
    if (response.ok) {
      const result = await response.json()
      saveSuccess.value = true
      saveMessage.value = result.message
      
      // Update the config prop with the fresh data
      if (result.config) {
        Object.keys(result.config).forEach(key => {
          if (form[key] !== undefined) {
            form[key] = result.config[key]
          }
        })
        // Mark form as clean since we just saved
        form.defaults()
      }

      // Hide success message after 5 seconds
      setTimeout(() => {
        saveSuccess.value = false
        saveMessage.value = ''
      }, 5000)
    } else {
      const errorData = await response.json()
      console.error('Save failed:', errorData)
      // Handle validation errors
      if (errorData.errors) {
        form.setError(errorData.errors)
      }
    }
  } catch (error) {
    console.error('Save failed:', error)
  } finally {
    saving.value = false
  }
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
    
    if (response.ok) {
      testResults.value = await response.json()
    }
  } catch (error) {
    console.error('Test failed:', error)
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
      alert(`IMAP test successful! ${result.message}`)
    } else {
      alert(`IMAP test failed: ${result.message}`)
    }
  } catch (error) {
    console.error('IMAP test failed:', error)
    alert('IMAP test failed: ' + error.message)
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
      alert(`M365 test successful! ${result.message}`)
    } else {
      alert(`M365 test failed: ${result.message}`)
    }
  } catch (error) {
    console.error('M365 test failed:', error)
    alert('M365 test failed: ' + error.message)
  } finally {
    testingM365.value = false
  }
}

// Load M365 folders
const loadM365Folders = async () => {
  if (!canLoadFolders.value) {
    alert('Please fill in all M365 credentials first')
    return
  }

  loadingFolders.value = true
  try {
    const response = await fetch('/api/settings/email/m365-folders', {
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
      m365Folders.value = result.folders
    } else {
      alert(`Failed to load folders: ${result.message}`)
    }
  } catch (error) {
    console.error('Failed to load folders:', error)
    alert('Failed to load folders: ' + error.message)
  } finally {
    loadingFolders.value = false
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