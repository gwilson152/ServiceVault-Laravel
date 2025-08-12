<template>
  <div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
      <h2 class="text-2xl font-semibold text-gray-900">Email Configuration</h2>
      <p class="text-gray-600 mt-2">Configure outgoing and incoming email services independently.</p>
    </div>

    <!-- Progress Steps -->
    <div class="mb-8">
      <nav aria-label="Email setup progress">
        <ol class="flex items-center">
          <li :class="['relative', currentStep >= 1 ? 'text-indigo-600' : 'text-gray-400']">
            <button
              @click="goToStep(1)"
              class="flex items-center hover:text-indigo-500 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 rounded-md p-1 -m-1"
              :title="'Go to Step 1: Outgoing Email'"
            >
              <span :class="[
                'w-8 h-8 rounded-full border-2 flex items-center justify-center text-sm font-medium transition-colors',
                currentStep >= 1 
                  ? 'border-indigo-600 bg-indigo-600 text-white' 
                  : 'border-gray-300 hover:border-indigo-300'
              ]">1</span>
              <span class="ml-4 text-sm font-medium">Outgoing Email</span>
            </button>
            
            <!-- Connecting line to next step -->
            <div class="absolute top-4 left-full ml-4 w-8 h-0.5 bg-gray-300 hidden sm:block"></div>
          </li>
          
          <li :class="['relative ml-8 sm:ml-16', currentStep >= 2 ? 'text-indigo-600' : 'text-gray-400']">
            <button
              @click="goToStep(2)"
              class="flex items-center hover:text-indigo-500 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 rounded-md p-1 -m-1"
              :title="'Go to Step 2: Incoming Email'"
            >
              <span :class="[
                'w-8 h-8 rounded-full border-2 flex items-center justify-center text-sm font-medium transition-colors',
                currentStep >= 2 
                  ? 'border-indigo-600 bg-indigo-600 text-white' 
                  : 'border-gray-300 hover:border-indigo-300'
              ]">2</span>
              <span class="ml-4 text-sm font-medium">Incoming Email</span>
            </button>
            
            <!-- Connecting line to next step -->
            <div class="absolute top-4 left-full ml-4 w-8 h-0.5 bg-gray-300 hidden sm:block"></div>
          </li>
          
          <li :class="['relative ml-8 sm:ml-16', currentStep >= 3 ? 'text-indigo-600' : 'text-gray-400']">
            <button
              @click="goToStep(3)"
              class="flex items-center hover:text-indigo-500 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 rounded-md p-1 -m-1"
              :title="'Go to Step 3: Test & Save'"
            >
              <span :class="[
                'w-8 h-8 rounded-full border-2 flex items-center justify-center text-sm font-medium transition-colors',
                currentStep >= 3 
                  ? 'border-indigo-600 bg-indigo-600 text-white' 
                  : 'border-gray-300 hover:border-indigo-300'
              ]">3</span>
              <span class="ml-4 text-sm font-medium">Test & Save</span>
            </button>
          </li>
        </ol>
      </nav>
    </div>

    <!-- Step Content -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
      
      <!-- Step 1: Outgoing Email (SMTP) -->
      <div v-if="currentStep === 1" class="p-6">
        <div class="mb-6">
          <h3 class="text-lg font-medium text-gray-900">Outgoing Email Configuration (SMTP)</h3>
          <p class="text-sm text-gray-600 mt-1">Choose how your system sends emails (notifications, alerts, etc.)</p>
        </div>
        
        <!-- SMTP Provider Selection -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-3">Select SMTP Provider</label>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <button
              v-for="provider in smtpProviders"
              :key="`smtp-${provider.key}`"
              @click="selectSmtpProvider(provider)"
              :class="[
                'p-4 border rounded-lg text-left transition-all',
                selectedSmtpProvider?.key === provider.key
                  ? 'border-indigo-500 bg-indigo-50 ring-1 ring-indigo-500'
                  : 'border-gray-200 hover:border-gray-300 hover:shadow-sm'
              ]"
            >
              <div class="flex flex-col items-center text-center space-y-2">
                <div :class="[
                  'w-10 h-10 rounded-lg flex items-center justify-center',
                  provider.color || 'bg-gray-100'
                ]">
                  <component :is="provider.icon" class="w-6 h-6 text-gray-600" />
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-900">{{ provider.name }}</h4>
                  <p class="text-xs text-gray-500 mt-1">{{ provider.description }}</p>
                </div>
              </div>
            </button>
          </div>
        </div>

        <!-- SMTP Configuration Form -->
        <div v-if="selectedSmtpProvider" class="space-y-6">
          <!-- Provider Instructions -->
          <div v-if="selectedSmtpProvider.instructions" class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex">
              <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
              </svg>
              <div class="ml-3">
                <h4 class="text-sm font-medium text-blue-800">Setup Instructions</h4>
                <div class="text-sm text-blue-700 mt-1" v-html="selectedSmtpProvider.instructions"></div>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- From Email -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">From Email Address *</label>
              <input 
                type="email" 
                v-model="form.from_address"
                :placeholder="selectedSmtpProvider ? `noreply@${selectedSmtpProvider.domain || 'example.com'}` : 'noreply@example.com'"
                class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                required
              />
              <p class="text-xs text-gray-500 mt-1">Email address used for sending notifications</p>
            </div>

            <!-- From Name -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">From Name</label>
              <input 
                type="text" 
                v-model="form.from_name"
                placeholder="Service Vault Support"
                class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>
          </div>

          <!-- SMTP Settings -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">SMTP Host *</label>
              <input 
                type="text" 
                v-model="form.smtp_host"
                :placeholder="selectedSmtpProvider?.smtp?.host || 'smtp.example.com'"
                :readonly="selectedSmtpProvider.key !== 'custom'"
                :class="[
                  'block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500',
                  selectedSmtpProvider.key !== 'custom' ? 'bg-gray-50' : ''
                ]"
                required
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Port *</label>
              <div class="relative">
                <input 
                  type="number" 
                  v-model="form.smtp_port"
                  placeholder="587"
                  min="1"
                  max="65535"
                  class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none [-moz-appearance:textfield]"
                  required
                />
                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                  <div class="relative smtp-port-dropdown">
                    <button
                      type="button"
                      @click="showSmtpPortOptions = !showSmtpPortOptions"
                      class="text-gray-400 hover:text-gray-600 focus:outline-none"
                      title="Common ports"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                      </svg>
                    </button>
                    <div v-if="showSmtpPortOptions" class="absolute right-0 mt-1 w-48 bg-white border border-gray-200 rounded-md shadow-lg z-10">
                      <button
                        v-for="port in commonSmtpPorts"
                        :key="port.value"
                        type="button"
                        @click="selectSmtpPort(port)"
                        class="block w-full px-3 py-2 text-left text-sm hover:bg-gray-100"
                      >
                        <div class="font-medium">{{ port.value }} - {{ port.name }}</div>
                        <div class="text-xs text-gray-500">{{ port.description }}</div>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              <p class="text-xs text-gray-500 mt-1">Common: 587 (TLS), 465 (SSL), 25 (Plain)</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Encryption</label>
              <select 
                v-model="form.smtp_encryption"
                :disabled="selectedSmtpProvider.key !== 'custom'"
                :class="[
                  'block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500',
                  selectedSmtpProvider.key !== 'custom' ? 'bg-gray-50' : ''
                ]"
              >
                <option value="">None</option>
                <option value="tls">TLS (STARTTLS)</option>
                <option value="ssl">SSL/TLS</option>
              </select>
            </div>
          </div>

          <!-- SMTP Credentials -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
              <input 
                type="text" 
                v-model="form.smtp_username"
                :placeholder="selectedSmtpProvider?.usernamePlaceholder || 'your.email@domain.com'"
                class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
              <p class="text-xs text-gray-500 mt-1">Leave blank if no authentication required</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ selectedSmtpProvider?.passwordLabel || 'Password' }}
              </label>
              <div v-if="selectedSmtpProvider?.requiresOAuth" class="relative">
                <input 
                  type="text" 
                  :value="'OAuth authentication - no password required'"
                  readonly
                  class="block w-full border-gray-300 rounded-md shadow-sm bg-green-50 text-green-700 cursor-not-allowed"
                />
                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                  <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
              </div>
              <input 
                v-else
                type="password" 
                v-model="form.smtp_password"
                :placeholder="selectedSmtpProvider?.passwordPlaceholder || 'Your password'"
                class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
              <p v-if="selectedSmtpProvider?.passwordHint" class="text-xs text-gray-500 mt-1">
                {{ selectedSmtpProvider.passwordHint }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Step 2: Incoming Email (IMAP) -->
      <div v-if="currentStep === 2" class="p-6">
        <div class="mb-6">
          <h3 class="text-lg font-medium text-gray-900">Incoming Email Configuration (IMAP)</h3>
          <p class="text-sm text-gray-600 mt-1">Optional: Enable automatic ticket creation from incoming emails</p>
        </div>

        <!-- Enable IMAP Toggle -->
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
          <div class="flex items-center">
            <input 
              type="checkbox" 
              id="enable_imap"
              v-model="enableImap"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <label for="enable_imap" class="ml-3 text-sm font-medium text-gray-700">
              Enable incoming email processing (Create tickets from emails)
            </label>
          </div>
          <p class="text-xs text-gray-500 mt-2 ml-7">Check this to automatically create support tickets from incoming emails</p>
        </div>

        <div v-if="enableImap">
          <!-- IMAP Provider Selection -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-3">Select IMAP Provider</label>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
              <button
                v-for="provider in imapProviders"
                :key="`imap-${provider.key}`"
                @click="selectImapProvider(provider)"
                :class="[
                  'p-4 border rounded-lg text-left transition-all',
                  selectedImapProvider?.key === provider.key
                    ? 'border-green-500 bg-green-50 ring-1 ring-green-500'
                    : 'border-gray-200 hover:border-gray-300 hover:shadow-sm'
                ]"
              >
                <div class="flex flex-col items-center text-center space-y-2">
                  <div :class="[
                    'w-10 h-10 rounded-lg flex items-center justify-center',
                    provider.color || 'bg-gray-100'
                  ]">
                    <component :is="provider.icon" class="w-6 h-6 text-gray-600" />
                  </div>
                  <div>
                    <h4 class="text-sm font-medium text-gray-900">{{ provider.name }}</h4>
                    <p class="text-xs text-gray-500 mt-1">{{ provider.description }}</p>
                  </div>
                </div>
              </button>
            </div>
          </div>

          <!-- IMAP Configuration Form -->
          <div v-if="selectedImapProvider" class="space-y-6">
            <!-- Provider Instructions -->
            <div v-if="selectedImapProvider.instructions" class="p-4 bg-green-50 border border-green-200 rounded-lg">
              <div class="flex">
                <svg class="w-5 h-5 text-green-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                </svg>
                <div class="ml-3">
                  <h4 class="text-sm font-medium text-green-800">IMAP Setup Instructions</h4>
                  <div class="text-sm text-green-700 mt-1" v-html="selectedImapProvider.instructions"></div>
                </div>
              </div>
            </div>

            <!-- IMAP Settings -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">IMAP Host *</label>
                <input 
                  type="text" 
                  v-model="form.imap_host"
                  :placeholder="selectedImapProvider?.imap?.host || 'imap.example.com'"
                  :readonly="selectedImapProvider.key !== 'custom'"
                  :class="[
                    'block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500',
                    selectedImapProvider.key !== 'custom' ? 'bg-gray-50' : ''
                  ]"
                  required
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Port *</label>
                <div class="relative">
                  <input 
                    type="number" 
                    v-model="form.imap_port"
                    placeholder="993"
                    min="1"
                    max="65535"
                    class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none [-moz-appearance:textfield]"
                    required
                  />
                  <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                    <div class="relative imap-port-dropdown">
                      <button
                        type="button"
                        @click="showImapPortOptions = !showImapPortOptions"
                        class="text-gray-400 hover:text-gray-600 focus:outline-none"
                        title="Common ports"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                      </button>
                      <div v-if="showImapPortOptions" class="absolute right-0 mt-1 w-48 bg-white border border-gray-200 rounded-md shadow-lg z-10">
                        <button
                          v-for="port in commonImapPorts"
                          :key="port.value"
                          type="button"
                          @click="selectImapPort(port)"
                          class="block w-full px-3 py-2 text-left text-sm hover:bg-gray-100"
                        >
                          <div class="font-medium">{{ port.value }} - {{ port.name }}</div>
                          <div class="text-xs text-gray-500">{{ port.description }}</div>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
                <p class="text-xs text-gray-500 mt-1">Common: 993 (SSL), 143 (TLS)</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Encryption</label>
                <select 
                  v-model="form.imap_encryption"
                  :disabled="selectedImapProvider.key !== 'custom'"
                  :class="[
                    'block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500',
                    selectedImapProvider.key !== 'custom' ? 'bg-gray-50' : ''
                  ]"
                >
                  <option value="ssl">SSL/TLS</option>
                  <option value="tls">TLS (STARTTLS)</option>
                  <option value="">None</option>
                </select>
              </div>
            </div>

            <!-- IMAP Credentials -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">IMAP Username *</label>
                <input 
                  type="text" 
                  v-model="form.imap_username"
                  :placeholder="selectedImapProvider?.usernamePlaceholder || 'support@example.com'"
                  class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  required
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  {{ selectedImapProvider?.passwordLabel || 'Password' }} <span v-if="!selectedImapProvider?.requiresOAuth">*</span>
                </label>
                <div v-if="selectedImapProvider?.requiresOAuth" class="relative">
                  <input 
                    type="text" 
                    :value="'OAuth authentication - no password required'"
                    readonly
                    class="block w-full border-gray-300 rounded-md shadow-sm bg-green-50 text-green-700 cursor-not-allowed"
                  />
                  <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                </div>
                <input 
                  v-else
                  type="password" 
                  v-model="form.imap_password"
                  :placeholder="selectedImapProvider?.passwordPlaceholder || 'Your password'"
                  class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  required
                />
                <p v-if="selectedImapProvider?.passwordHint" class="text-xs text-gray-500 mt-1">
                  {{ selectedImapProvider.passwordHint }}
                </p>
              </div>
            </div>

            <!-- Folder Settings -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email Folder</label>
                <input 
                  type="text" 
                  v-model="form.imap_folder"
                  placeholder="INBOX"
                  class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
                <p class="text-xs text-gray-500 mt-1">Email folder to monitor (usually INBOX)</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Check Interval</label>
                <select 
                  v-model="form.email_check_interval"
                  class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                  <option value="5">Every 5 minutes</option>
                  <option value="10">Every 10 minutes</option>
                  <option value="15">Every 15 minutes</option>
                  <option value="30">Every 30 minutes</option>
                </select>
              </div>
            </div>

            <!-- Smart Credential Sync -->
            <div v-if="selectedSmtpProvider && selectedImapProvider && canSyncCredentials" class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
              <div class="flex items-center">
                <input 
                  type="checkbox" 
                  id="sync_credentials"
                  v-model="syncCredentials"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                />
                <label for="sync_credentials" class="ml-3 text-sm text-blue-800">
                  Use the same credentials as SMTP ({{ selectedSmtpProvider.name }})
                </label>
              </div>
              <p class="text-xs text-blue-700 mt-2 ml-7">Most email providers use the same credentials for both SMTP and IMAP</p>
            </div>
          </div>
        </div>

        <!-- Skip IMAP Option -->
        <div v-if="!enableImap" class="text-center py-8 text-gray-500">
          <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2m13-8l-4 4-4-4m8 8l-4-4-4 4" />
          </svg>
          <p class="text-sm">Incoming email is disabled. Your system will only send emails.</p>
          <p class="text-xs mt-1">You can enable this later to create tickets from incoming emails.</p>
        </div>
      </div>

      <!-- Step 3: Test & Save -->
      <div v-if="currentStep === 3" class="p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-6">Test Configuration & Save</h3>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <!-- SMTP Test -->
          <div class="space-y-4">
            <h4 class="font-medium text-gray-900">Test Outgoing Email (SMTP)</h4>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Send test email to</label>
              <div class="flex space-x-2">
                <input 
                  type="email" 
                  v-model="testEmails.smtp"
                  placeholder="admin@example.com"
                  class="flex-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
                <button
                  @click="testSmtpConfiguration"
                  :disabled="testing.smtp || !testEmails.smtp || !canTestSmtp"
                  class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 disabled:opacity-50"
                >
                  {{ testing.smtp ? 'Testing...' : 'Test' }}
                </button>
              </div>
            </div>
            
            <!-- SMTP Test Results -->
            <div v-if="testResults.smtp" class="p-3 rounded-md" :class="[
              testResults.smtp.success 
                ? 'bg-green-50 border border-green-200' 
                : 'bg-red-50 border border-red-200'
            ]">
              <div class="flex">
                <svg v-if="testResults.smtp.success" class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <svg v-else class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                <div class="ml-3">
                  <p class="text-sm font-medium" :class="testResults.smtp.success ? 'text-green-800' : 'text-red-800'">
                    {{ testResults.smtp.success ? 'SMTP Test Successful!' : 'SMTP Test Failed' }}
                  </p>
                  <p class="text-sm mt-1" :class="testResults.smtp.success ? 'text-green-700' : 'text-red-700'">
                    {{ testResults.smtp.message }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- IMAP Test -->
          <div v-if="enableImap && selectedImapProvider" class="space-y-4">
            <h4 class="font-medium text-gray-900">Test Incoming Email (IMAP)</h4>
            <div>
              <button
                @click="testImapConfiguration"
                :disabled="testing.imap || !canTestImap"
                class="w-full px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700 disabled:opacity-50"
              >
                {{ testing.imap ? 'Testing Connection...' : 'Test IMAP Connection' }}
              </button>
            </div>
            
            <!-- IMAP Test Results -->
            <div v-if="testResults.imap" class="p-3 rounded-md" :class="[
              testResults.imap.success 
                ? 'bg-green-50 border border-green-200' 
                : 'bg-red-50 border border-red-200'
            ]">
              <div class="flex">
                <svg v-if="testResults.imap.success" class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <svg v-else class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                <div class="ml-3">
                  <p class="text-sm font-medium" :class="testResults.imap.success ? 'text-green-800' : 'text-red-800'">
                    {{ testResults.imap.success ? 'IMAP Test Successful!' : 'IMAP Test Failed' }}
                  </p>
                  <p class="text-sm mt-1" :class="testResults.imap.success ? 'text-green-700' : 'text-red-700'">
                    {{ testResults.imap.message }}
                  </p>
                  <div v-if="testResults.imap.success && testResults.imap.details" class="text-xs mt-1 text-green-600">
                    Found {{ testResults.imap.details.total_messages }} messages ({{ testResults.imap.details.unread_messages }} unread)
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Configuration Summary -->
        <div class="mt-8 bg-gray-50 rounded-lg p-6">
          <h4 class="text-sm font-medium text-gray-900 mb-4">Configuration Summary</h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <h5 class="text-sm font-medium text-gray-700 mb-2">Outgoing Email (SMTP)</h5>
              <dl class="text-sm space-y-1">
                <div class="flex justify-between">
                  <dt class="text-gray-500">Provider:</dt>
                  <dd class="text-gray-900">{{ selectedSmtpProvider?.name || 'Not configured' }}</dd>
                </div>
                <div class="flex justify-between">
                  <dt class="text-gray-500">From:</dt>
                  <dd class="text-gray-900">{{ form.from_address || 'Not set' }}</dd>
                </div>
                <div class="flex justify-between">
                  <dt class="text-gray-500">Host:</dt>
                  <dd class="text-gray-900">{{ form.smtp_host || 'Not set' }}</dd>
                </div>
              </dl>
            </div>
            <div>
              <h5 class="text-sm font-medium text-gray-700 mb-2">Incoming Email (IMAP)</h5>
              <dl class="text-sm space-y-1">
                <div class="flex justify-between">
                  <dt class="text-gray-500">Provider:</dt>
                  <dd class="text-gray-900">{{ selectedImapProvider?.name || (enableImap ? 'Not configured' : 'Disabled') }}</dd>
                </div>
                <div class="flex justify-between">
                  <dt class="text-gray-500">Host:</dt>
                  <dd class="text-gray-900">{{ form.imap_host || (enableImap ? 'Not set' : 'N/A') }}</dd>
                </div>
                <div class="flex justify-between">
                  <dt class="text-gray-500">Folder:</dt>
                  <dd class="text-gray-900">{{ form.imap_folder || (enableImap ? 'INBOX' : 'N/A') }}</dd>
                </div>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="border-t border-gray-200 px-6 py-4 flex justify-between">
        <button
          v-if="currentStep > 1"
          @click="currentStep--"
          type="button"
          class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          Back
        </button>
        <div v-else></div>

        <div class="flex space-x-3">
          <button
            v-if="currentStep < 3"
            @click="nextStep"
            :disabled="!canProceed"
            type="button"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
          >
            {{ currentStep === 2 && !enableImap ? 'Skip to Test' : 'Continue' }}
          </button>

          <button
            v-if="currentStep === 3"
            @click="saveConfiguration"
            :disabled="loading || !canSave"
            type="button"
            :class="[
              'inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm',
              canSave 
                ? 'text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500'
                : 'text-gray-500 bg-gray-300 cursor-not-allowed'
            ]"
          >
            <span v-if="loading" class="mr-2">
              <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </span>
            {{ loading ? 'Saving...' : 'Save Email Configuration' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted, onUnmounted } from 'vue'
import { 
  EnvelopeIcon, 
  ServerIcon, 
  CogIcon,
  BuildingOfficeIcon 
} from '@heroicons/vue/24/outline'

const props = defineProps({
  settings: {
    type: Object,
    default: () => ({})
  },
  loading: {
    type: Boolean,
    default: false
  },
  testing: {
    type: Object,
    default: () => ({ smtp: false, imap: false })
  }
})

const emit = defineEmits(['update', 'test-smtp', 'test-imap'])

// Wizard state
const currentStep = ref(1)
const selectedSmtpProvider = ref(null)
const selectedImapProvider = ref(null)
const enableImap = ref(false)
const syncCredentials = ref(false)
const showSmtpPortOptions = ref(false)
const showImapPortOptions = ref(false)

// Testing state
const testing = reactive({
  smtp: false,
  imap: false
})

const testEmails = reactive({
  smtp: '',
  imap: ''
})

const testResults = reactive({
  smtp: null,
  imap: null
})

// Form data
const form = reactive({
  from_address: '',
  from_name: 'Service Vault Support',
  smtp_host: '',
  smtp_port: '587',
  smtp_encryption: 'tls',
  smtp_username: '',
  smtp_password: '',
  imap_host: '',
  imap_port: '993',
  imap_encryption: 'ssl',
  imap_username: '',
  imap_password: '',
  imap_folder: 'INBOX',
  email_check_interval: '5'
})

// Email providers (both SMTP and IMAP capable)
const emailProviders = [
  {
    key: 'microsoft365_oauth',
    name: 'Microsoft 365 (OAuth)',
    description: 'Modern OAuth2 authentication',
    icon: BuildingOfficeIcon,
    domain: 'yourdomain.com',
    color: 'bg-blue-100',
    usernamePlaceholder: 'user@yourdomain.com',
    passwordLabel: 'OAuth Setup',
    passwordPlaceholder: 'Will be configured automatically',
    passwordHint: 'OAuth tokens are managed automatically - no password needed',
    requiresOAuth: true,
    smtp: {
      host: 'smtp-mail.outlook.com',
      port: 587,
      encryption: 'tls'
    },
    imap: {
      host: 'outlook.office365.com',
      port: 993,
      encryption: 'ssl'
    },
    instructions: `
      <strong>Microsoft 365 OAuth Setup:</strong>
      <div class="mt-2 p-3 bg-green-50 border border-green-200 rounded-md">
        <p class="text-sm text-green-800 font-medium">✅ Recommended for modern Microsoft 365 environments</p>
      </div>
      <ol class="mt-2 ml-4 text-sm space-y-1">
        <li>1. Your Microsoft 365 admin must register Service Vault as an app</li>
        <li>2. The admin provides you with the Client ID and Tenant ID</li>
        <li>3. Enter your full email address as username</li>
        <li>4. Leave password field empty (OAuth handles authentication)</li>
        <li>5. Test connection will redirect you to Microsoft for authentication</li>
        <li>6. Grant email permissions when prompted</li>
      </ol>
      <p class="mt-2 text-xs text-blue-600">
        <strong>Benefits:</strong> More secure, no passwords to manage, automatic token refresh, supports MFA.
      </p>
    `
  },
  {
    key: 'microsoft365_legacy',
    name: 'Microsoft 365 (App Password)',
    description: 'Legacy authentication with app passwords',
    icon: BuildingOfficeIcon,
    domain: 'yourdomain.com',
    color: 'bg-blue-50',
    usernamePlaceholder: 'user@yourdomain.com',
    passwordLabel: 'App Password',
    passwordPlaceholder: '16-character app password',
    passwordHint: 'Generate an App Password from your Microsoft 365 security settings',
    smtp: {
      host: 'smtp-mail.outlook.com',
      port: 587,
      encryption: 'tls'
    },
    imap: {
      host: 'outlook.office365.com',
      port: 993,
      encryption: 'ssl'
    },
    instructions: `
      <strong>Microsoft 365 App Password Setup:</strong>
      <div class="mt-2 p-3 bg-amber-50 border border-amber-200 rounded-md">
        <p class="text-sm text-amber-800 font-medium">⚠️ Legacy method - OAuth is recommended for new setups</p>
      </div>
      <ol class="mt-2 ml-4 text-sm space-y-1">
        <li>1. Sign in to <a href="https://security.microsoft.com" target="_blank" class="text-blue-600 underline">Microsoft 365 Security</a></li>
        <li>2. Go to <strong>Sign-in options</strong> → <strong>App passwords</strong></li>
        <li>3. Click <strong>"Create app password"</strong></li>
        <li>4. Enter a name like "Service Vault Email"</li>
        <li>5. Copy the generated 16-character password</li>
        <li>6. Use your full email address as username</li>
        <li>7. Use the app password (not your regular password)</li>
      </ol>
      <p class="mt-2 text-xs text-blue-600">
        <strong>Note:</strong> If App Passwords option is not available, your admin may have disabled it. Consider using OAuth instead.
      </p>
    `
  },
  {
    key: 'gmail',
    name: 'Gmail / Google Workspace',
    description: 'Gmail, G Suite Business',
    icon: EnvelopeIcon,
    domain: 'yourdomain.com',
    color: 'bg-red-100',
    usernamePlaceholder: 'user@yourdomain.com',
    passwordLabel: 'App Password',
    passwordPlaceholder: 'App Password',
    passwordHint: 'Use App Password from Google account settings',
    smtp: {
      host: 'smtp.gmail.com',
      port: 587,
      encryption: 'tls'
    },
    imap: {
      host: 'imap.gmail.com',
      port: 993,
      encryption: 'ssl'
    },
    instructions: `
      <strong>Gmail / Google Workspace App Password Setup:</strong>
      <ol class="mt-2 ml-4 text-sm space-y-1">
        <li>1. Sign in to <a href="https://myaccount.google.com" target="_blank" class="text-blue-600 underline">Google Account</a></li>
        <li>2. Go to <strong>Security</strong> → <strong>2-Step Verification</strong></li>
        <li>3. Enable 2-Factor Authentication if not already enabled</li>
        <li>4. Go to <strong>Security</strong> → <strong>App passwords</strong></li>
        <li>5. Select app: <strong>Mail</strong>, device: <strong>Other (custom name)</strong></li>
        <li>6. Enter "Service Vault" as the custom name</li>
        <li>7. Copy the generated 16-character password</li>
        <li>8. Use your Gmail address as username</li>
        <li>9. Use the app password (not your regular password)</li>
      </ol>
      <p class="mt-2 text-xs text-blue-600">
        <strong>Note:</strong> You must have 2-Factor Authentication enabled to generate app passwords.
      </p>
    `
  },
  {
    key: 'outlook',
    name: 'Outlook.com',
    description: 'Personal Outlook accounts',
    icon: EnvelopeIcon,
    domain: 'outlook.com',
    color: 'bg-blue-100',
    usernamePlaceholder: 'user@outlook.com',
    passwordLabel: 'Password',
    passwordPlaceholder: 'Your password',
    smtp: {
      host: 'smtp-mail.outlook.com',
      port: 587,
      encryption: 'tls'
    },
    imap: {
      host: 'imap-mail.outlook.com',
      port: 993,
      encryption: 'ssl'
    }
  },
  {
    key: 'custom',
    name: 'Custom Server',
    description: 'Manual configuration',
    icon: ServerIcon,
    color: 'bg-gray-100'
  }
]

// Computed provider lists
const smtpProviders = computed(() => emailProviders)
const imapProviders = computed(() => emailProviders.filter(p => p.imap || p.key === 'custom'))

// Common port options
const commonSmtpPorts = [
  { value: '587', name: 'TLS', description: 'STARTTLS - Most common, recommended' },
  { value: '465', name: 'SSL', description: 'SSL/TLS - Legacy but still used' },
  { value: '25', name: 'Plain', description: 'No encryption - Not recommended' },
  { value: '2525', name: 'Alternative', description: 'Alternative port for some providers' }
]

const commonImapPorts = [
  { value: '993', name: 'SSL', description: 'IMAP over SSL/TLS - Recommended' },
  { value: '143', name: 'Plain/TLS', description: 'IMAP with STARTTLS or plain' },
  { value: '585', name: 'Alternative', description: 'Alternative IMAP SSL port' }
]

// Computed properties
const canProceed = computed(() => {
  if (currentStep.value === 1) {
    return selectedSmtpProvider.value && form.from_address && form.smtp_host && form.smtp_port
  }
  if (currentStep.value === 2) {
    if (!enableImap.value) return true
    return selectedImapProvider.value && form.imap_host && form.imap_port && form.imap_username
  }
  return true
})

const canTestSmtp = computed(() => {
  return form.from_address && form.smtp_host && form.smtp_port && testEmails.smtp
})

const canTestImap = computed(() => {
  if (!enableImap.value || !selectedImapProvider.value || !form.imap_host || !form.imap_port || !form.imap_username) {
    return false
  }
  
  // OAuth providers don't need passwords for testing
  if (selectedImapProvider.value.requiresOAuth) {
    return true
  }
  
  // Non-OAuth providers need passwords
  return !!form.imap_password
})

const canSave = computed(() => {
  const smtpValid = selectedSmtpProvider.value && form.from_address && form.smtp_host && form.smtp_port
  
  if (!enableImap.value) {
    return smtpValid
  }
  
  // Check IMAP validation
  if (!selectedImapProvider.value || !form.imap_host || !form.imap_port || !form.imap_username) {
    return false
  }
  
  // OAuth providers don't need passwords for saving
  if (selectedImapProvider.value.requiresOAuth) {
    return smtpValid
  }
  
  // Non-OAuth providers need passwords
  return smtpValid && form.imap_password
})

const canSyncCredentials = computed(() => {
  return selectedSmtpProvider.value && selectedImapProvider.value && 
         selectedSmtpProvider.value.key === selectedImapProvider.value.key &&
         selectedSmtpProvider.value.key !== 'custom'
})

// Methods
const selectSmtpProvider = (provider) => {
  selectedSmtpProvider.value = provider
  
  // Auto-fill settings for known providers
  if (provider.smtp && provider.key !== 'custom') {
    form.smtp_host = provider.smtp.host
    form.smtp_port = provider.smtp.port.toString()
    form.smtp_encryption = provider.smtp.encryption || 'tls'
    
    // For OAuth providers, clear password fields as they're not needed
    if (provider.requiresOAuth) {
      form.smtp_password = ''
    }
  } else if (provider.key === 'custom') {
    // Clear preset values for custom provider
    form.smtp_host = ''
    form.smtp_port = '587'
    form.smtp_encryption = 'tls'
  }
}

const selectImapProvider = (provider) => {
  selectedImapProvider.value = provider
  
  // Auto-fill settings for known providers
  if (provider.imap && provider.key !== 'custom') {
    form.imap_host = provider.imap.host
    form.imap_port = provider.imap.port.toString()
    form.imap_encryption = provider.imap.encryption || 'ssl'
    
    // For OAuth providers, clear password fields as they're not needed
    if (provider.requiresOAuth) {
      form.imap_password = ''
    }
  } else if (provider.key === 'custom') {
    // Clear preset values for custom provider
    form.imap_host = ''
    form.imap_port = '993'
    form.imap_encryption = 'ssl'
  }
  
  // Check if we can sync credentials
  if (canSyncCredentials.value) {
    syncCredentials.value = true
  }
}

const nextStep = () => {
  if (canProceed.value && currentStep.value < 3) {
    currentStep.value++
  }
}

const goToStep = (step) => {
  // Allow direct navigation to any step
  if (step >= 1 && step <= 3) {
    currentStep.value = step
  }
}

const testSmtpConfiguration = async () => {
  if (!canTestSmtp.value) return
  
  testing.smtp = true
  testResults.smtp = null
  
  const config = {
    smtp_host: form.smtp_host,
    smtp_port: parseInt(form.smtp_port),
    smtp_username: form.smtp_username || null,
    smtp_password: form.smtp_password || null,
    smtp_encryption: form.smtp_encryption,
    from_address: form.from_address,
    from_name: form.from_name,
    test_email: testEmails.smtp
  }
  
  try {
    testResults.smtp = await emit('test-smtp', config)
  } catch (error) {
    testResults.smtp = {
      success: false,
      message: error.message || 'SMTP test failed'
    }
  } finally {
    testing.smtp = false
  }
}

const testImapConfiguration = async () => {
  if (!canTestImap.value) return
  
  testing.imap = true
  testResults.imap = null
  
  const config = {
    imap_host: form.imap_host,
    imap_port: parseInt(form.imap_port),
    imap_username: form.imap_username,
    imap_password: form.imap_password,
    imap_encryption: form.imap_encryption,
    imap_folder: form.imap_folder || 'INBOX'
  }
  
  try {
    testResults.imap = await emit('test-imap', config)
  } catch (error) {
    testResults.imap = {
      success: false,
      message: error.message || 'IMAP test failed'
    }
  } finally {
    testing.imap = false
  }
}

const selectSmtpPort = (port) => {
  form.smtp_port = port.value.toString()
  showSmtpPortOptions.value = false
}

const selectImapPort = (port) => {
  form.imap_port = port.value.toString()
  showImapPortOptions.value = false
}

// Close dropdowns when clicking outside
const closeDropdowns = (event) => {
  const smtpDropdown = event.target.closest('.smtp-port-dropdown')
  const imapDropdown = event.target.closest('.imap-port-dropdown')
  
  if (!smtpDropdown) {
    showSmtpPortOptions.value = false
  }
  if (!imapDropdown) {
    showImapPortOptions.value = false
  }
}

// Lifecycle hooks for dropdown management
onMounted(() => {
  document.addEventListener('click', closeDropdowns)
})

onUnmounted(() => {
  document.removeEventListener('click', closeDropdowns)
})

const saveConfiguration = () => {
  if (!canSave.value) return
  
  const settings = {
    smtp_host: form.smtp_host,
    smtp_port: form.smtp_port,
    smtp_username: form.smtp_username || null,
    smtp_password: form.smtp_password || null,
    smtp_encryption: form.smtp_encryption,
    from_address: form.from_address,
    from_name: form.from_name,
    
    // IMAP settings if enabled
    imap_host: enableImap.value ? form.imap_host : null,
    imap_port: enableImap.value ? form.imap_port : null,
    imap_username: enableImap.value ? form.imap_username : null,
    imap_password: enableImap.value ? form.imap_password : null,
    imap_encryption: enableImap.value ? form.imap_encryption : null,
    imap_folder: enableImap.value ? form.imap_folder : null,
    
    // Email processing
    enable_email_to_ticket: enableImap.value,
    email_check_interval: enableImap.value ? form.email_check_interval : null
  }
  
  emit('update', settings)
}

// Watch for credential syncing
watch(syncCredentials, (sync) => {
  if (sync && canSyncCredentials.value) {
    form.imap_username = form.smtp_username
    form.imap_password = form.smtp_password
  }
})

// Watch for SMTP credential changes when syncing
watch(() => [form.smtp_username, form.smtp_password], ([username, password]) => {
  if (syncCredentials.value && canSyncCredentials.value) {
    form.imap_username = username
    form.imap_password = password
  }
})

// Watch for settings prop changes
watch(() => props.settings, (newSettings) => {
  if (newSettings && Object.keys(newSettings).length > 0) {
    Object.assign(form, newSettings)
    enableImap.value = !!newSettings.enable_email_to_ticket
    
    // Try to detect SMTP provider
    if (newSettings.smtp_host) {
      const smtpProvider = emailProviders.find(p => p.smtp?.host === newSettings.smtp_host)
      if (smtpProvider) {
        selectedSmtpProvider.value = smtpProvider
      } else {
        // If no known provider found, select custom
        selectedSmtpProvider.value = emailProviders.find(p => p.key === 'custom')
      }
    }
    
    // Try to detect IMAP provider
    if (newSettings.imap_host) {
      const imapProvider = emailProviders.find(p => p.imap?.host === newSettings.imap_host)
      if (imapProvider) {
        selectedImapProvider.value = imapProvider
      } else {
        // If no known provider found, select custom
        selectedImapProvider.value = emailProviders.find(p => p.key === 'custom')
      }
    }
    
    // Jump to appropriate step if already configured
    if (selectedSmtpProvider.value && newSettings.smtp_host) {
      currentStep.value = Math.max(currentStep.value, 2)
      
      // If IMAP is also configured, jump to step 3
      if ((newSettings.imap_host && selectedImapProvider.value) || !enableImap.value) {
        currentStep.value = Math.max(currentStep.value, 3)
      }
    } else if (newSettings.smtp_host && !selectedSmtpProvider.value) {
      // If we have SMTP data but no provider detected, stay on step 1 to show the issue
      currentStep.value = 1
    }
  }
}, { immediate: true, deep: true })
</script>