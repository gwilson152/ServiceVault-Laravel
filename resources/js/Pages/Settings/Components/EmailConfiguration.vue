<template>
  <div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-semibold text-gray-900">Email Configuration</h2>
          <p class="text-gray-600 mt-2">Configure outgoing and incoming email services independently.</p>
        </div>
        <div class="flex items-center space-x-3">
          <!-- Account Selector -->
          <div class="relative">
            <label class="block text-sm font-medium text-gray-700 mb-2">Configuration Scope</label>
            <select
              v-model="configurationScope"
              @change="loadConfigurationForScope"
              class="block w-56 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            >
              <option value="global">Global Settings (All Accounts)</option>
              <option v-for="account in availableAccounts" :key="account.id" :value="account.id">
                Account: {{ account.name }}
              </option>
            </select>
            <p class="text-xs text-gray-500 mt-1">
              {{ configurationScope === 'global' ? 'Default settings for all accounts' : 'Override settings for this account' }}
            </p>
          </div>
          
          <!-- Advanced Settings Button -->
          <button
            @click="showAdvancedSettings = true"
            class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
          >
            <CogIcon class="w-4 h-4 mr-2" />
            Advanced
          </button>
        </div>
      </div>
    </div>

    <!-- Progress Steps -->
    <div class="mb-8">
      <nav aria-label="Email setup progress">
        <ol class="flex items-center">
          <li :class="['relative', currentStep >= 1 ? 'text-indigo-600' : 'text-gray-400']">
            <button
              @click="goToStep(1)"
              class="flex items-center hover:text-indigo-500 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 rounded-md p-1 -m-1"
              :title="'Go to Step 1: Driver Selection'"
            >
              <span :class="[
                'w-8 h-8 rounded-full border-2 flex items-center justify-center text-sm font-medium transition-colors',
                currentStep >= 1 
                  ? 'border-indigo-600 bg-indigo-600 text-white' 
                  : 'border-gray-300 hover:border-indigo-300'
              ]">1</span>
              <span class="ml-4 text-sm font-medium">Driver Selection</span>
            </button>
            
            <!-- Connecting line to next step -->
            <div class="absolute top-4 left-full ml-4 w-8 h-0.5 bg-gray-300 hidden sm:block"></div>
          </li>
          
          <li :class="['relative ml-8 sm:ml-16', currentStep >= 2 ? 'text-indigo-600' : 'text-gray-400']">
            <button
              @click="goToStep(2)"
              class="flex items-center hover:text-indigo-500 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 rounded-md p-1 -m-1"
              :title="'Go to Step 2: Configuration'"
            >
              <span :class="[
                'w-8 h-8 rounded-full border-2 flex items-center justify-center text-sm font-medium transition-colors',
                currentStep >= 2 
                  ? 'border-indigo-600 bg-indigo-600 text-white' 
                  : 'border-gray-300 hover:border-indigo-300'
              ]">2</span>
              <span class="ml-4 text-sm font-medium">Configuration</span>
            </button>
            
            <!-- Connecting line to next step -->
            <div class="absolute top-4 left-full ml-4 w-8 h-0.5 bg-gray-300 hidden sm:block"></div>
          </li>
          
          <li :class="['relative ml-8 sm:ml-16', currentStep >= 3 ? 'text-indigo-600' : 'text-gray-400']">
            <button
              @click="goToStep(3)"
              class="flex items-center hover:text-indigo-500 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 rounded-md p-1 -m-1"
              :title="'Go to Step 3: Advanced Settings'"
            >
              <span :class="[
                'w-8 h-8 rounded-full border-2 flex items-center justify-center text-sm font-medium transition-colors',
                currentStep >= 3 
                  ? 'border-indigo-600 bg-indigo-600 text-white' 
                  : 'border-gray-300 hover:border-indigo-300'
              ]">3</span>
              <span class="ml-4 text-sm font-medium">Advanced</span>
            </button>
            
            <!-- Connecting line to next step -->
            <div class="absolute top-4 left-full ml-4 w-8 h-0.5 bg-gray-300 hidden sm:block"></div>
          </li>
          
          <li :class="['relative ml-8 sm:ml-16', currentStep >= 4 ? 'text-indigo-600' : 'text-gray-400']">
            <button
              @click="goToStep(4)"
              class="flex items-center hover:text-indigo-500 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 rounded-md p-1 -m-1"
              :title="'Go to Step 4: Test & Save'"
            >
              <span :class="[
                'w-8 h-8 rounded-full border-2 flex items-center justify-center text-sm font-medium transition-colors',
                currentStep >= 4 
                  ? 'border-indigo-600 bg-indigo-600 text-white' 
                  : 'border-gray-300 hover:border-indigo-300'
              ]">4</span>
              <span class="ml-4 text-sm font-medium">Test & Save</span>
            </button>
          </li>
        </ol>
      </nav>
    </div>

    <!-- Step Content -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
      
      <!-- Step 1: Driver Selection -->
      <div v-if="currentStep === 1" class="p-6">
        <div class="mb-6">
          <h3 class="text-lg font-medium text-gray-900">Email Driver Selection</h3>
          <p class="text-sm text-gray-600 mt-1">Choose the email service you want to use for sending and receiving emails</p>
        </div>
        
        <!-- Driver Selection Grid -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-4">Select Email Driver</label>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <button
              v-for="driver in availableDrivers"
              :key="driver.key"
              @click="selectEmailDriver(driver)"
              :class="[
                'p-6 border rounded-lg text-left transition-all relative',
                selectedDriver?.key === driver.key
                  ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-500'
                  : 'border-gray-200 hover:border-gray-300 hover:shadow-sm'
              ]"
            >
              <!-- Popular Badge -->
              <div v-if="driver.popular" class="absolute top-2 right-2">
                <span class="inline-flex px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                  Popular
                </span>
              </div>
              
              <div class="flex flex-col space-y-3">
                <div class="flex items-center space-x-3">
                  <div :class="[
                    'w-12 h-12 rounded-lg flex items-center justify-center',
                    driver.color || 'bg-gray-100'
                  ]">
                    <component :is="driver.icon" class="w-6 h-6 text-gray-600" />
                  </div>
                  <div class="flex-1">
                    <h4 class="text-lg font-medium text-gray-900">{{ driver.name }}</h4>
                    <p class="text-sm text-gray-500">{{ driver.description }}</p>
                  </div>
                </div>
                
                <!-- Features -->
                <div class="space-y-2">
                  <div class="flex flex-wrap gap-1">
                    <span v-for="feature in driver.features" :key="feature" 
                      class="inline-flex px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded-md">
                      {{ feature }}
                    </span>
                  </div>
                  
                  <!-- Pricing/Limits Info -->
                  <div v-if="driver.limits" class="text-xs text-gray-600">
                    {{ driver.limits }}
                  </div>
                </div>
                
                <!-- Best For -->
                <div v-if="driver.bestFor" class="text-xs text-blue-600 font-medium">
                  Best for: {{ driver.bestFor }}
                </div>
              </div>
            </button>
          </div>
        </div>
        
        <!-- Driver Description -->
        <div v-if="selectedDriver" class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
          <div class="flex">
            <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
            </svg>
            <div class="ml-3">
              <h4 class="text-sm font-medium text-blue-800">{{ selectedDriver.name }} Setup</h4>
              <div class="text-sm text-blue-700 mt-1" v-html="selectedDriver.setupInfo"></div>
              
              <!-- Requirements -->
              <div v-if="selectedDriver.requirements" class="mt-2">
                <p class="text-xs font-medium text-blue-800 mb-1">Requirements:</p>
                <ul class="text-xs text-blue-700 ml-4 space-y-1">
                  <li v-for="req in selectedDriver.requirements" :key="req" class="flex">
                    <span class="mr-2">•</span>
                    <span>{{ req }}</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Step 2: Configuration -->
      <div v-if="currentStep === 2" class="p-6">
        <div class="mb-6">
          <h3 class="text-lg font-medium text-gray-900">{{ selectedDriver?.name }} Configuration</h3>
          <p class="text-sm text-gray-600 mt-1">Configure your {{ selectedDriver?.name }} settings</p>
        </div>
        
        <!-- Configuration Form based on selected driver -->
        <div v-if="selectedDriver" class="space-y-6">
          <!-- Common Settings -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">From Email Address *</label>
              <input 
                type="email" 
                v-model="form.from_address"
                :placeholder="selectedDriver?.defaultFromAddress || 'noreply@yourdomain.com'"
                class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                required
              />
              <p class="text-xs text-gray-500 mt-1">Email address used for sending notifications</p>
            </div>

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
          
          <!-- Driver-specific configuration (simplified for now) -->
          <div v-if="selectedDriver.key === 'smtp'" class="space-y-4">
            <!-- SMTP Configuration -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">SMTP Host *</label>
                <input 
                  type="text" 
                  v-model="form.driver_config.smtp_host"
                  placeholder="smtp.example.com"
                  class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  required
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Port *</label>
                <input 
                  type="number" 
                  v-model="form.driver_config.smtp_port"
                  placeholder="587"
                  class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  required
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Encryption</label>
                <select 
                  v-model="form.driver_config.smtp_encryption"
                  class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                  <option value="">None</option>
                  <option value="tls">TLS (STARTTLS)</option>
                  <option value="ssl">SSL/TLS</option>
                </select>
              </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                <input 
                  type="text" 
                  v-model="form.driver_config.smtp_username"
                  placeholder="your.email@domain.com"
                  class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                <input 
                  type="password" 
                  v-model="form.driver_config.smtp_password"
                  placeholder="Your password"
                  class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
            </div>
          </div>
          
          <div v-else-if="selectedDriver.key === 'ses'" class="space-y-4">
            <!-- Amazon SES Configuration -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">AWS Access Key *</label>
                <input 
                  type="text" 
                  v-model="form.driver_config.ses_key"
                  placeholder="AKIAIOSFODNN7EXAMPLE"
                  class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  required
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">AWS Secret Key *</label>
                <input 
                  type="password" 
                  v-model="form.driver_config.ses_secret"
                  placeholder="wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY"
                  class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  required
                />
              </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">AWS Region *</label>
                <select 
                  v-model="form.driver_config.ses_region"
                  class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  required
                >
                  <option value="us-east-1">US East (N. Virginia)</option>
                  <option value="us-west-2">US West (Oregon)</option>
                  <option value="eu-west-1">Europe (Ireland)</option>
                  <option value="ap-southeast-1">Asia Pacific (Singapore)</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Configuration Set (Optional)</label>
                <input 
                  type="text" 
                  v-model="form.driver_config.ses_configuration_set"
                  placeholder="my-config-set"
                  class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
            </div>
          </div>
          
          <div v-else class="text-center py-8 text-gray-500">
            <CogIcon class="w-12 h-12 mx-auto mb-4 text-gray-400" />
            <p class="text-sm">Configuration for {{ selectedDriver.name }} will be available soon.</p>
            <p class="text-xs mt-1">For now, you can test basic functionality with SMTP or Amazon SES.</p>
          </div>
          
          <!-- Test Configuration Button -->
          <div class="mt-6 flex justify-center">
            <button
              @click="testCurrentConfiguration"
              :disabled="testing || !canTestConfiguration"
              class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
            >
              <span v-if="testing" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-700" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Testing...
              </span>
              <span v-else>
                <ShieldCheckIcon class="w-4 h-4 mr-2" />
                Test Configuration
              </span>
            </button>
          </div>
          
          <!-- Test Results -->
          <div v-if="testResult" class="mt-4 p-3 rounded-md" :class="[
            testResult.success 
              ? 'bg-green-50 border border-green-200' 
              : 'bg-red-50 border border-red-200'
          ]">
            <div class="flex">
              <svg v-if="testResult.success" class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
              </svg>
              <svg v-else class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
              </svg>
              <div class="ml-3">
                <p class="text-sm font-medium" :class="testResult.success ? 'text-green-800' : 'text-red-800'">
                  {{ testResult.success ? 'Configuration Test Successful!' : 'Configuration Test Failed' }}
                </p>
                <p class="text-sm mt-1" :class="testResult.success ? 'text-green-700' : 'text-red-700'">
                  {{ testResult.message }}
                </p>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Fallback if no driver selected -->
        <div v-else class="text-center py-8 text-gray-500">
          <CogIcon class="w-12 h-12 mx-auto mb-4 text-gray-400" />
          <p class="text-sm">Select an email driver to continue with configuration.</p>
        </div>
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

      <!-- Step 3: Advanced Settings -->
      <div v-if="currentStep === 3" class="p-6">
        <div class="mb-6">
          <h3 class="text-lg font-medium text-gray-900">Advanced Email Settings</h3>
          <p class="text-sm text-gray-600 mt-1">Configure advanced features like webhooks, routing rules, and processing options</p>
        </div>
        
        <!-- Webhooks Configuration -->
        <div class="mb-8">
          <h4 class="text-base font-medium text-gray-900 mb-4">Webhook Endpoints</h4>
          <p class="text-sm text-gray-600 mb-4">Configure webhook endpoints for real-time email event processing</p>
          
          <div class="bg-gray-50 p-4 rounded-lg space-y-4">
            <!-- Incoming Email Webhook -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Incoming Email Webhook</label>
              <div class="flex space-x-2">
                <input 
                  type="text" 
                  :value="getWebhookUrl('incoming')"
                  readonly
                  class="flex-1 border-gray-300 rounded-md shadow-sm bg-white font-mono text-sm"
                />
                <button
                  @click="copyToClipboard(getWebhookUrl('incoming'))"
                  class="px-3 py-2 border border-gray-300 text-sm rounded-md hover:bg-gray-50"
                >
                  Copy
                </button>
              </div>
              <p class="text-xs text-gray-500 mt-1">Use this URL in your email provider's webhook settings</p>
            </div>
            
            <!-- Email Status Webhook -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Email Status Webhook</label>
              <div class="flex space-x-2">
                <input 
                  type="text" 
                  :value="getWebhookUrl('status')"
                  readonly
                  class="flex-1 border-gray-300 rounded-md shadow-sm bg-white font-mono text-sm"
                />
                <button
                  @click="copyToClipboard(getWebhookUrl('status'))"
                  class="px-3 py-2 border border-gray-300 text-sm rounded-md hover:bg-gray-50"
                >
                  Copy
                </button>
              </div>
              <p class="text-xs text-gray-500 mt-1">Receives delivery confirmations, bounces, and spam reports</p>
            </div>
            
            <!-- Webhook Security -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Webhook Security Token</label>
              <div class="flex space-x-2">
                <input 
                  type="text" 
                  :value="webhookToken"
                  readonly
                  class="flex-1 border-gray-300 rounded-md shadow-sm bg-white font-mono text-sm"
                />
                <button
                  @click="regenerateWebhookToken"
                  class="px-3 py-2 border border-gray-300 text-sm rounded-md hover:bg-gray-50"
                >
                  Regenerate
                </button>
              </div>
              <p class="text-xs text-gray-500 mt-1">Include this token in webhook requests for security validation</p>
            </div>
          </div>
        </div>
        
        <!-- Email Routing Rules -->
        <div class="mb-8">
          <h4 class="text-base font-medium text-gray-900 mb-4">Email Routing Rules</h4>
          <p class="text-sm text-gray-600 mb-4">Configure how incoming emails are processed and routed to accounts</p>
          
          <div class="space-y-4">
            <!-- Enable Email Processing -->
            <div class="flex items-center">
              <input 
                type="checkbox" 
                id="enable_email_processing"
                v-model="form.enable_email_processing"
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
              />
              <label for="enable_email_processing" class="ml-3 text-sm font-medium text-gray-700">
                Enable automatic email processing
              </label>
            </div>
            
            <div v-if="form.enable_email_processing" class="ml-7 space-y-4">
              <!-- Default Account -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Default Account for New Tickets</label>
                <select
                  v-model="form.default_account_id"
                  class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                >
                  <option value="">Select default account...</option>
                  <option v-for="account in availableAccounts" :key="account.id" :value="account.id">
                    {{ account.name }}
                  </option>
                </select>
              </div>
              
              <!-- Processing Options -->
              <div class="space-y-2">
                <label class="flex items-center">
                  <input
                    v-model="form.auto_create_tickets"
                    type="checkbox"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                  />
                  <span class="ml-2 text-sm text-gray-700">Auto-create tickets from emails</span>
                </label>
                <label class="flex items-center">
                  <input
                    v-model="form.auto_process_commands"
                    type="checkbox"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                  />
                  <span class="ml-2 text-sm text-gray-700">Process subject line commands (time:45, priority:high, etc.)</span>
                </label>
                <label class="flex items-center">
                  <input
                    v-model="form.auto_attach_files"
                    type="checkbox"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                  />
                  <span class="ml-2 text-sm text-gray-700">Auto-attach email files to tickets</span>
                </label>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Security Settings -->
        <div class="mb-8">
          <h4 class="text-base font-medium text-gray-900 mb-4">Security Settings</h4>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Max Attachment Size (MB)</label>
              <input
                v-model.number="form.max_attachment_size"
                type="number"
                min="1"
                max="100"
                class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Blocked File Extensions</label>
              <input
                v-model="form.blocked_extensions"
                type="text"
                placeholder=".exe, .bat, .com, .scr"
                class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              />
            </div>
          </div>
          
          <div class="mt-4 space-y-2">
            <label class="flex items-center">
              <input
                v-model="form.scan_attachments"
                type="checkbox"
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
              />
              <span class="ml-2 text-sm text-gray-700">Scan attachments for viruses</span>
            </label>
            <label class="flex items-center">
              <input
                v-model="form.require_authentication"
                type="checkbox"
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
              />
              <span class="ml-2 text-sm text-gray-700">Require sender authentication for command processing</span>
            </label>
          </div>
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

      <!-- Step 4: Test & Save -->
      <div v-if="currentStep === 4" class="p-6">
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
            v-if="currentStep < 4"
            @click="nextStep"
            :disabled="!canProceed"
            type="button"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
          >
            Continue
          </button>

          <button
            v-if="currentStep === 4"
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
    
    <!-- Advanced Settings Modal -->
    <StackedDialog
      :show="showAdvancedSettings"
      @close="showAdvancedSettings = false"
      size="xl"
      title="Advanced Email Settings"
    >
      <div class="space-y-6">
        <!-- Email Queue Settings -->
        <div>
          <h4 class="text-base font-medium text-gray-900 mb-4">Queue Settings</h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Queue Driver</label>
              <select class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option>Database</option>
                <option>Redis</option>
                <option>Sync (Immediate)</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Max Retries</label>
              <input type="number" min="1" max="10" value="3" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
            </div>
          </div>
        </div>
        
        <!-- Rate Limiting -->
        <div>
          <h4 class="text-base font-medium text-gray-900 mb-4">Rate Limiting</h4>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Per Minute</label>
              <input type="number" min="1" value="60" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Per Hour</label>
              <input type="number" min="1" value="1000" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Per Day</label>
              <input type="number" min="1" value="10000" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
            </div>
          </div>
        </div>
        
        <!-- Debugging -->
        <div>
          <h4 class="text-base font-medium text-gray-900 mb-4">Debugging</h4>
          <div class="space-y-2">
            <label class="flex items-center">
              <input type="checkbox" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded" />
              <span class="ml-2 text-sm text-gray-700">Enable debug logging</span>
            </label>
            <label class="flex items-center">
              <input type="checkbox" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded" />
              <span class="ml-2 text-sm text-gray-700">Log all email headers</span>
            </label>
            <label class="flex items-center">
              <input type="checkbox" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded" />
              <span class="ml-2 text-sm text-gray-700">Save raw email content</span>
            </label>
          </div>
        </div>
      </div>
      
      <template #actions>
        <button
          @click="showAdvancedSettings = false"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
        >
          Close
        </button>
        <button
          class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700"
        >
          Save Advanced Settings
        </button>
      </template>
    </StackedDialog>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted, onUnmounted } from 'vue'
import { 
  EnvelopeIcon, 
  ServerIcon, 
  CogIcon,
  BuildingOfficeIcon,
  CloudIcon,
  ShieldCheckIcon,
  BoltIcon,
  GlobeAltIcon
} from '@heroicons/vue/24/outline'

// Import shared components
import StackedDialog from '@/Components/Modals/StackedDialog.vue'

// Import driver-specific configuration components (these will be created as needed)
// import SmtpDriverConfig from './EmailDrivers/SmtpDriverConfig.vue'
// import SesDriverConfig from './EmailDrivers/SesDriverConfig.vue'
// import PostmarkDriverConfig from './EmailDrivers/PostmarkDriverConfig.vue'
// import MailgunDriverConfig from './EmailDrivers/MailgunDriverConfig.vue'
// import SendgridDriverConfig from './EmailDrivers/SendgridDriverConfig.vue'

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
const selectedDriver = ref(null)
const configurationScope = ref('global')
const showAdvancedSettings = ref(false)
const availableAccounts = ref([])
const webhookToken = ref('')
const loading = ref(false)
const testing = ref(false)
const testResult = ref(null)

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
  // Common settings
  driver: '',
  from_address: '',
  from_name: 'Service Vault Support',
  
  // Driver-specific settings (will be populated based on selected driver)
  driver_config: {},
  
  // Advanced settings
  enable_email_processing: false,
  default_account_id: '',
  auto_create_tickets: true,
  auto_process_commands: true,
  auto_attach_files: true,
  
  // Security settings
  max_attachment_size: 25,
  blocked_extensions: '.exe, .bat, .com, .scr, .pif',
  scan_attachments: true,
  require_authentication: false
})

// Available email drivers
const availableDrivers = [
  {
    key: 'smtp',
    name: 'SMTP',
    description: 'Traditional SMTP server',
    icon: ServerIcon,
    color: 'bg-gray-100',
    popular: true,
    features: ['Custom servers', 'Self-hosted', 'Full control'],
    limits: 'No sending limits (depends on server)',
    bestFor: 'Self-hosted solutions, custom servers',
    configComponent: 'SmtpDriverConfig',
    defaultFromAddress: 'noreply@yourdomain.com',
    requirements: [
      'SMTP server hostname and port',
      'Authentication credentials (if required)',
      'SSL/TLS configuration'
    ],
    setupInfo: 'Configure your existing SMTP server or use a third-party SMTP service. Most flexible but requires server management.'
  },
  {
    key: 'ses',
    name: 'Amazon SES',
    description: 'AWS Simple Email Service',
    icon: CloudIcon,
    color: 'bg-orange-100',
    popular: true,
    features: ['High deliverability', 'AWS integration', 'Cost-effective'],
    limits: 'Pay per email sent, very low cost',
    bestFor: 'High volume, AWS environments',
    configComponent: 'SesDriverConfig',
    defaultFromAddress: 'noreply@verified-domain.com',
    requirements: [
      'AWS account with SES access',
      'Verified sending domain or email',
      'AWS access key and secret key',
      'SES region configuration'
    ],
    setupInfo: 'Amazon SES provides reliable, scalable email delivery through AWS infrastructure. Great for high-volume sending with excellent deliverability.'
  },
  {
    key: 'postmark',
    name: 'Postmark',
    description: 'Transactional email service',
    icon: BoltIcon,
    color: 'bg-yellow-100',
    popular: true,
    features: ['Fast delivery', 'Great analytics', 'Template management'],
    limits: '100 emails/month free, then paid plans',
    bestFor: 'Transactional emails, fast delivery',
    configComponent: 'PostmarkDriverConfig',
    defaultFromAddress: 'noreply@yourdomain.com',
    requirements: [
      'Postmark account',
      'Server API token',
      'Verified sending signature',
      'Domain authentication (recommended)'
    ],
    setupInfo: 'Postmark specializes in transactional email delivery with excellent speed and deliverability. Ideal for application notifications.'
  },
  {
    key: 'mailgun',
    name: 'Mailgun',
    description: 'Email API service by Mailgun',
    icon: GlobeAltIcon,
    color: 'bg-red-100',
    features: ['Powerful API', 'Email validation', 'Advanced analytics'],
    limits: '5,000 emails/month free, then paid',
    bestFor: 'API integration, email validation',
    configComponent: 'MailgunDriverConfig',
    defaultFromAddress: 'noreply@mg.yourdomain.com',
    requirements: [
      'Mailgun account',
      'Domain verification',
      'API key and domain',
      'Webhook endpoints (optional)'
    ],
    setupInfo: 'Mailgun provides powerful email APIs with advanced features like email validation, detailed analytics, and webhook support.'
  },
  {
    key: 'sendgrid',
    name: 'SendGrid',
    description: 'Twilio SendGrid email service',
    icon: EnvelopeIcon,
    color: 'bg-blue-100',
    features: ['Marketing tools', 'Template engine', 'A/B testing'],
    limits: '100 emails/day free, then paid plans',
    bestFor: 'Marketing emails, template management',
    configComponent: 'SendgridDriverConfig',
    defaultFromAddress: 'noreply@yourdomain.com',
    requirements: [
      'SendGrid account',
      'API key',
      'Verified sender identity',
      'Domain authentication (recommended)'
    ],
    setupInfo: 'SendGrid offers comprehensive email delivery with advanced marketing features, template management, and detailed analytics.'
  },
  {
    key: 'log',
    name: 'Log (Testing)',
    description: 'Log emails to files (development)',
    icon: CogIcon,
    color: 'bg-gray-50',
    features: ['Local testing', 'No external dependencies', 'File logging'],
    limits: 'No limits (local files only)',
    bestFor: 'Development, testing environments',
    configComponent: 'LogDriverConfig',
    defaultFromAddress: 'test@localhost',
    requirements: [
      'Write permissions to log directory',
      'Local development environment'
    ],
    setupInfo: 'Log driver saves emails to local files instead of sending them. Perfect for development and testing without external services.'
  }
]

// Legacy email providers (for backward compatibility)
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

// Computed properties
const canProceed = computed(() => {
  if (currentStep.value === 1) {
    return selectedDriver.value !== null
  }
  if (currentStep.value === 2) {
    return form.from_address && validateDriverConfig()
  }
  if (currentStep.value === 3) {
    return true // Advanced settings are optional
  }
  return true
})

const canSave = computed(() => {
  return selectedDriver.value && form.from_address && validateDriverConfig()
})

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
// Methods
const selectEmailDriver = (driver) => {
  selectedDriver.value = driver
  form.driver = driver.key
  
  // Initialize driver-specific config
  form.driver_config = {}
  
  // Set some defaults based on driver
  if (driver.key === 'smtp') {
    form.driver_config.smtp_port = '587'
    form.driver_config.smtp_encryption = 'tls'
  } else if (driver.key === 'ses') {
    form.driver_config.ses_region = 'us-east-1'
  }
  
  // Set default from address based on driver
  if (!form.from_address) {
    form.from_address = driver.defaultFromAddress
  }
}

const validateDriverConfig = () => {
  if (!selectedDriver.value) return false
  
  const config = form.driver_config || {}
  
  // Validate based on selected driver
  if (selectedDriver.value.key === 'smtp') {
    return config.smtp_host && config.smtp_port
  }
  if (selectedDriver.value.key === 'ses') {
    return config.ses_key && config.ses_secret && config.ses_region
  }
  
  // For other drivers, assume they're valid if config exists
  return Object.keys(config).length > 0
}

const canTestConfiguration = computed(() => {
  return selectedDriver.value && form.from_address && validateDriverConfig()
})

const testCurrentConfiguration = async () => {
  const result = await testDriverConfiguration(form.driver_config)
  testResult.value = result
}

const testDriverConfiguration = async (config) => {
  try {
    testing.value = true
    
    const response = await fetch('/api/email-admin/test-connection', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        driver: selectedDriver.value.key,
        config: config,
        scope: configurationScope.value
      })
    })
    
    const result = await response.json()
    return {
      success: response.ok,
      message: result.message || (response.ok ? 'Connection successful!' : 'Connection failed'),
      details: result.details
    }
    
  } catch (error) {
    return {
      success: false,
      message: error.message || 'Connection test failed'
    }
  } finally {
    testing.value = false
  }
}

const loadConfigurationForScope = async () => {
  try {
    const url = configurationScope.value === 'global' 
      ? '/api/email-admin/settings'
      : `/api/email-admin/settings/${configurationScope.value}`
      
    const response = await fetch(url)
    if (response.ok) {
      const settings = await response.json()
      Object.assign(form, settings)
      
      // Select the appropriate driver
      if (settings.driver) {
        const driver = availableDrivers.find(d => d.key === settings.driver)
        if (driver) {
          selectedDriver.value = driver
        }
      }
    }
  } catch (error) {
    console.error('Error loading configuration:', error)
  }
}

const loadAvailableAccounts = async () => {
  try {
    const response = await fetch('/api/search/accounts?limit=100')
    if (response.ok) {
      const data = await response.json()
      availableAccounts.value = data.results || []
    }
  } catch (error) {
    console.error('Error loading accounts:', error)
  }
}

const getWebhookUrl = (type) => {
  const baseUrl = window.location.origin
  return `${baseUrl}/api/webhooks/email/${type}`
}

const copyToClipboard = async (text) => {
  try {
    await navigator.clipboard.writeText(text)
    // Could add a toast notification here
  } catch (error) {
    console.error('Failed to copy to clipboard:', error)
  }
}

const regenerateWebhookToken = async () => {
  try {
    const response = await fetch('/api/email-admin/webhook-token', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      webhookToken.value = data.token
    }
  } catch (error) {
    console.error('Error regenerating webhook token:', error)
  }
}

const nextStep = () => {
  if (canProceed.value && currentStep.value < 4) {
    currentStep.value++
  }
}

const goToStep = (step) => {
  if (step >= 1 && step <= 4) {
    currentStep.value = step
  }
}

const saveConfiguration = async () => {
  if (!canSave.value) return
  
  try {
    loading.value = true
    
    const url = configurationScope.value === 'global' 
      ? '/api/email-admin/settings'
      : `/api/email-admin/settings/${configurationScope.value}`
    
    const response = await fetch(url, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify(form)
    })
    
    if (response.ok) {
      emit('update', form)
      // Could add success notification here
    } else {
      throw new Error('Failed to save configuration')
    }
    
  } catch (error) {
    console.error('Error saving configuration:', error)
    // Could add error notification here
  } finally {
    loading.value = false
  }
}

// Initialize webhook token
const initializeWebhookToken = async () => {
  try {
    const response = await fetch('/api/email-admin/webhook-token')
    if (response.ok) {
      const data = await response.json()
      webhookToken.value = data.token
    }
  } catch (error) {
    console.error('Error loading webhook token:', error)
    webhookToken.value = 'webhook-token-not-available'
  }
}

// Lifecycle hooks
onMounted(() => {
  loadAvailableAccounts()
  initializeWebhookToken()
  
  // Load configuration for current scope
  if (props.settings && Object.keys(props.settings).length > 0) {
    loadConfigurationForScope()
  }
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

// Watch for configuration scope changes
watch(configurationScope, () => {
  loadConfigurationForScope()
})

// Watch for settings prop changes
watch(() => props.settings, (newSettings) => {
  if (newSettings && Object.keys(newSettings).length > 0) {
    Object.assign(form, newSettings)
    
    // Try to detect driver
    if (newSettings.driver) {
      const driver = availableDrivers.find(d => d.key === newSettings.driver)
      if (driver) {
        selectedDriver.value = driver
      }
    }
    
    // Jump to appropriate step if already configured
    if (selectedDriver.value && newSettings.from_address) {
      currentStep.value = Math.max(currentStep.value, 2)
    }
  }
}, { immediate: true, deep: true })
</script>