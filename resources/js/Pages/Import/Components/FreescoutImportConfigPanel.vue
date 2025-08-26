<template>
  <div class="space-y-8">
    <!-- Import Limits Section -->
    <div class="bg-gray-50 rounded-lg p-6">
      <div class="flex items-center mb-4">
        <AdjustmentsHorizontalIcon class="w-5 h-5 text-gray-400 mr-2" />
        <h3 class="text-lg font-medium text-gray-900">Import Limits</h3>
      </div>
      <p class="text-sm text-gray-600 mb-6">
        Set limits for each data type to control the size of your import. Leave empty for no limit.
      </p>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Conversations Limit -->
        <div>
          <label for="conversations-limit" class="block text-sm font-medium text-gray-700 mb-2">
            Conversations
          </label>
          <input
            id="conversations-limit"
            v-model.number="config.limits.conversations"
            type="number"
            min="0"
            placeholder="No limit"
            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
          />
          <p class="mt-1 text-xs text-gray-500">
            Available: {{ previewData.conversations?.length?.toLocaleString() || 'Loading...' }}
          </p>
        </div>

        <!-- Time Entries Limit -->
        <div>
          <label for="time-entries-limit" class="block text-sm font-medium text-gray-700 mb-2">
            Time Entries
          </label>
          <input
            id="time-entries-limit"
            v-model.number="config.limits.time_entries"
            type="number"
            min="0"
            placeholder="No limit"
            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
          />
          <p class="mt-1 text-xs text-gray-500">
            Available: {{ previewData.time_entries?.length?.toLocaleString() || '0' }}
          </p>
        </div>

        <!-- Customers Limit -->
        <div>
          <label for="customers-limit" class="block text-sm font-medium text-gray-700 mb-2">
            Customers
          </label>
          <input
            id="customers-limit"
            v-model.number="config.limits.customers"
            type="number"
            min="0"
            placeholder="No limit"
            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
          />
          <p class="mt-1 text-xs text-gray-500">
            Available: {{ previewData.customers?.length?.toLocaleString() || 'Loading...' }}
          </p>
        </div>

        <!-- Mailboxes Limit -->
        <div>
          <label for="mailboxes-limit" class="block text-sm font-medium text-gray-700 mb-2">
            Mailboxes
          </label>
          <input
            id="mailboxes-limit"
            v-model.number="config.limits.mailboxes"
            type="number"
            min="0"
            placeholder="No limit"
            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
          />
          <p class="mt-1 text-xs text-gray-500">
            Available: {{ previewData.mailboxes?.length?.toLocaleString() || 'Loading...' }}
          </p>
        </div>
      </div>
    </div>

    <!-- Account Mapping Strategy Section -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
      <div class="flex items-center mb-4">
        <BuildingOfficeIcon class="w-5 h-5 text-gray-400 mr-2" />
        <h3 class="text-lg font-medium text-gray-900">Account Mapping Strategy</h3>
      </div>
      <p class="text-sm text-gray-600 mb-4">
        Choose how FreeScout data should be organized into Service Vault accounts.
      </p>
      
      <!-- Relationship Challenge Alert -->
      <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
        <div class="flex items-start">
          <ExclamationTriangleIcon class="w-5 h-5 text-amber-600 mt-0.5 mr-3" />
          <div>
            <h4 class="text-sm font-medium text-amber-900 mb-2">Data Relationship Challenge</h4>
            <div class="text-sm text-amber-700 space-y-1">
              <p>FreeScout has no "account" concept, but Service Vault requires every record to belong to an account:</p>
              <ul class="list-disc list-inside ml-2 space-y-1">
                <li><strong>Time Entries</strong> need account_id + user_id + ticket_id</li>
                <li><strong>Tickets</strong> (conversations) need account_id + customer_id</li>
                <li><strong>Users</strong> (agents + customers) need account_id</li>
                <li><strong>Ticket Comments</strong> (threads) need proper ticket relationship</li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <div class="space-y-4">
        <!-- Option 1: Map Mailboxes to Accounts -->
        <div class="flex items-start">
          <input
            id="map-mailboxes"
            v-model="config.account_strategy"
            value="map_mailboxes"
            type="radio"
            class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
          />
          <div class="ml-3">
            <label for="map-mailboxes" class="block text-sm font-medium text-gray-900">
              Map mailboxes to accounts
            </label>
            <p class="mt-1 text-sm text-gray-500">
              Create a Service Vault account for each FreeScout mailbox. Conversations will be grouped by their mailbox.
            </p>
            <!-- Preview of mailbox mapping with relationships -->
            <div v-if="config.account_strategy === 'map_mailboxes'" class="mt-3 space-y-3">
              <div class="bg-blue-50 rounded-md p-3 border border-blue-200">
                <p class="text-xs font-medium text-blue-900 mb-2">Mailbox → Account Mapping:</p>
                <div class="space-y-1">
                  <div 
                    v-for="mailbox in previewData.mailboxes.slice(0, 3)"
                    :key="mailbox.id"
                    class="flex items-center justify-between text-xs"
                  >
                    <span class="text-blue-800 font-medium">{{ mailbox.name }}</span>
                    <span class="text-blue-600">→ "{{ mailbox.name }} Account"</span>
                  </div>
                  <div v-if="(previewData.mailboxes?.length || 0) > 3" class="text-xs text-blue-500">
                    +{{ (previewData.mailboxes?.length || 0) - 3 }} more accounts will be created
                  </div>
                </div>
              </div>
              
              <div class="bg-gray-50 rounded-md p-3 border border-gray-200">
                <p class="text-xs font-medium text-gray-700 mb-2">Data Relationship Flow:</p>
                <div class="text-xs text-gray-600 space-y-1">
                  <div>• <strong>Conversations:</strong> Assigned to their mailbox's account</div>
                  <div>• <strong>Time Entries:</strong> Inherit account_id from conversation</div>
                  <div>• <strong>Customers:</strong> Assigned to conversation's account</div>
                  <div>• <strong>Agents:</strong> Can access all mailbox accounts</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Option 2: Use Domain Mapping -->
        <div class="flex items-start">
          <input
            id="use-domain-mapping"
            v-model="config.account_strategy"
            value="domain_mapping"
            type="radio"
            class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
          />
          <div class="ml-3">
            <label for="use-domain-mapping" class="block text-sm font-medium text-gray-900">
              Use existing domain mapping settings
            </label>
            <p class="mt-1 text-sm text-gray-500">
              Skip mailbox import and use Service Vault's domain mapping to match customer emails to existing accounts.
            </p>
            <!-- Domain mapping preview with relationships -->
            <div v-if="config.account_strategy === 'domain_mapping'" class="mt-3 space-y-3">
              <div class="bg-green-50 rounded-md p-3 border border-green-200">
                <p class="text-xs font-medium text-green-900 mb-2">Domain → Account Mapping:</p>
                <div class="space-y-1">
                  <div 
                    v-for="mapping in mockDomainMappings.slice(0, 3)"
                    :key="mapping.id"
                    class="flex items-center justify-between text-xs"
                  >
                    <span class="text-green-800 font-medium">{{ mapping.pattern }}</span>
                    <span class="text-green-600">→ {{ mapping.account }}</span>
                  </div>
                  <div v-if="mockDomainMappings.length > 3" class="text-xs text-green-500">
                    +{{ mockDomainMappings.length - 3 }} more existing mappings
                  </div>
                </div>
              </div>
              
              <div class="bg-gray-50 rounded-md p-3 border border-gray-200">
                <p class="text-xs font-medium text-gray-700 mb-2">Data Relationship Flow:</p>
                <div class="text-xs text-gray-600 space-y-1">
                  <div>• <strong>Customer Email:</strong> Extract domain → find account mapping</div>
                  <div>• <strong>Conversations:</strong> Customer's account becomes ticket account</div>
                  <div>• <strong>Time Entries:</strong> Inherit account from ticket</div>
                  <div>• <strong>Unmapped Domains:</strong> Handle per unmapped user strategy</div>
                </div>
              </div>
              
              <div class="bg-yellow-50 rounded-md p-2 border border-yellow-200">
                <p class="text-xs font-medium text-yellow-800">⚠️ Requires existing domain mappings in Service Vault</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- User Import Strategy Section -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
      <div class="flex items-center mb-4">
        <UserPlusIcon class="w-5 h-5 text-gray-400 mr-2" />
        <h3 class="text-lg font-medium text-gray-900">User Import Strategy</h3>
      </div>
      <p class="text-sm text-gray-600 mb-4">
        Configure how FreeScout users (agents and customers) should be imported and classified in Service Vault.
      </p>
      
      <!-- Compatibility Verification -->
      <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
        <div class="flex items-start">
          <CheckCircleIcon class="w-5 h-5 text-green-600 mt-0.5 mr-3" />
          <div>
            <h4 class="text-sm font-medium text-green-900 mb-2">Service Vault Compatibility Verified</h4>
            <div class="text-sm text-green-800 space-y-1">
              <div>• User types 'agent' and 'account_user' match database constraints</div>
              <div>• Role templates 'Agent' and 'Account User' exist in default system</div>
              <div>• Account assignment system supports both strategies (single/multiple accounts)</div>
              <div>• Three-dimensional permission system accommodates imported user relationships</div>
            </div>
          </div>
        </div>
      </div>

      <!-- User Type Detection -->
      <div class="mb-6">
        <h4 class="text-sm font-medium text-gray-900 mb-3">User Type Classification</h4>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <div class="text-sm text-blue-900 space-y-2">
            <div><strong>FreeScout Agents →</strong> Service Vault agents (user_type: 'agent')</div>
            <div><strong>FreeScout Customers →</strong> Service Vault account users (user_type: 'account_user')</div>
          </div>
          <div class="mt-3 text-xs text-blue-700 space-y-1">
            <div><strong>Role Assignment:</strong> Agents get 'Agent' role template, customers get 'Account User' role template</div>
            <div><strong>Validation:</strong> Import will verify role templates exist before assignment</div>
          </div>
        </div>
      </div>

      <!-- Agent Account Access -->
      <div class="mb-6">
        <h4 class="text-sm font-medium text-gray-900 mb-3">Agent Account Access</h4>
        <div class="space-y-3">
          <div class="flex items-start">
            <input
              id="agent-all-accounts"
              v-model="config.agent_access"
              value="all_accounts"
              type="radio"
              class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
            />
            <div class="ml-3">
              <label for="agent-all-accounts" class="block text-sm font-medium text-gray-900">
                Grant agents access to all imported accounts
              </label>
              <p class="mt-1 text-sm text-gray-500">
                FreeScout agents can work across mailboxes, so give them access to all created accounts.
              </p>
            </div>
          </div>
          
          <div class="flex items-start">
            <input
              id="agent-primary-account"
              v-model="config.agent_access"
              value="primary_account"
              type="radio"
              class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
            />
            <div class="ml-3">
              <label for="agent-primary-account" class="block text-sm font-medium text-gray-900">
                Assign agents to primary account only
              </label>
              <p class="mt-1 text-sm text-gray-500">
                Create a single "FreeScout Import" account for all agents, keeping account separation clear.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Data Relationship Analysis Section -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
      <div class="flex items-center mb-4">
        <ShareIcon class="w-5 h-5 text-gray-400 mr-2" />
        <h3 class="text-lg font-medium text-gray-900">Data Relationship Analysis</h3>
      </div>
      <p class="text-sm text-gray-600 mb-6">
        Preview how FreeScout data will be mapped to Service Vault's relationship requirements.
      </p>

      <!-- Relationship Flow Visualization -->
      <div class="space-y-6">
        <!-- Conversations to Tickets -->
        <div class="bg-gradient-to-r from-blue-50 to-purple-50 border border-blue-200 rounded-lg p-4">
          <div class="flex items-center mb-3">
            <div class="w-3 h-3 bg-blue-500 rounded-full mr-3"></div>
            <h4 class="text-sm font-medium text-gray-900">Conversations → Tickets</h4>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <div class="space-y-2">
              <div class="font-medium text-blue-900">FreeScout Data:</div>
              <div class="space-y-1 text-blue-800">
                <div>• <strong>ID:</strong> {{ previewData.conversations[0]?.id || 'conversation_123' }}</div>
                <div>• <strong>Subject:</strong> "{{ previewData.conversations[0]?.subject || 'Payment issue with invoice #456' }}"</div>
                <div>• <strong>Customer:</strong> {{ previewData.conversations[0]?.customer?.email || 'john@acme.com' }}</div>
                <div>• <strong>Mailbox:</strong> {{ previewData.conversations[0]?.mailbox?.name || 'Support' }}</div>
                <div>• <strong>Status:</strong> {{ previewData.conversations[0]?.status || 'active' }}</div>
              </div>
            </div>
            
            <div class="space-y-2">
              <div class="font-medium text-purple-900">Service Vault Mapping:</div>
              <div class="space-y-1 text-purple-800">
                <div>• <strong>tickets.external_id:</strong> conversation_123</div>
                <div>• <strong>tickets.title:</strong> "Payment issue with invoice #456"</div>
                <div>• <strong>tickets.account_id:</strong> {{ config.account_strategy === 'map_mailboxes' ? 'Support Account' : 'Acme Corporation' }}</div>
                <div>• <strong>tickets.customer_id:</strong> user.id (john@acme.com)</div>
                <div>• <strong>tickets.status:</strong> active → "Open"</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Time Entries Mapping -->
        <div class="bg-gradient-to-r from-green-50 to-teal-50 border border-green-200 rounded-lg p-4">
          <div class="flex items-center mb-3">
            <div class="w-3 h-3 bg-green-500 rounded-full mr-3"></div>
            <h4 class="text-sm font-medium text-gray-900">Time Entries → Time Entries</h4>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <div class="space-y-2">
              <div class="font-medium text-green-900">FreeScout Data:</div>
              <div class="space-y-1 text-green-800">
                <div>• <strong>ID:</strong> {{ previewData.time_entries?.[0]?.id || 'time_789' }}</div>
                <div>• <strong>Time:</strong> {{ previewData.time_entries?.[0]?.time || '2.5 hours' }}</div>
                <div>• <strong>User:</strong> {{ previewData.time_entries?.[0]?.user?.name || 'Agent Smith' }}</div>
                <div>• <strong>Conversation:</strong> #{{ previewData.time_entries?.[0]?.conversation_id || 'conversation_123' }}</div>
                <div>• <strong>Note:</strong> "{{ previewData.time_entries?.[0]?.note || 'Investigated payment gateway issue' }}"</div>
              </div>
            </div>
            
            <div class="space-y-2">
              <div class="font-medium text-teal-900">Service Vault Mapping:</div>
              <div class="space-y-1 text-teal-800">
                <div>• <strong>time_entries.external_id:</strong> time_789</div>
                <div>• <strong>time_entries.duration:</strong> 150 minutes</div>
                <div>• <strong>time_entries.user_id:</strong> agent.id (Agent Smith)</div>
                <div>• <strong>time_entries.ticket_id:</strong> ticket.id (from conversation)</div>
                <div>• <strong>time_entries.account_id:</strong> ticket.account_id</div>
                <div>• <strong>time_entries.description:</strong> "Investigated payment gateway issue"</div>
              </div>
            </div>
          </div>
          
          <div class="mt-3 p-3 bg-white border border-green-200 rounded-md">
            <div class="text-xs text-green-800">
              <strong>Critical Relationship:</strong> Time entries inherit account_id from their associated ticket, 
              ensuring proper billing and access control in Service Vault's account-centric architecture.
            </div>
          </div>
        </div>

        <!-- Conversation Threads to Comments -->
        <div class="bg-gradient-to-r from-yellow-50 to-orange-50 border border-yellow-200 rounded-lg p-4">
          <div class="flex items-center mb-3">
            <div class="w-3 h-3 bg-yellow-500 rounded-full mr-3"></div>
            <h4 class="text-sm font-medium text-gray-900">Conversation Threads → Ticket Comments</h4>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <div class="space-y-2">
              <div class="font-medium text-yellow-900">FreeScout Data:</div>
              <div class="space-y-1 text-yellow-800">
                <div>• <strong>Thread ID:</strong> {{ previewData.conversations[0]?.threads?.[0]?.id || 'thread_456' }}</div>
                <div>• <strong>Type:</strong> {{ previewData.conversations[0]?.threads?.[0]?.type || 'message' }}</div>
                <div>• <strong>Author:</strong> {{ previewData.conversations[0]?.threads?.[0]?.created_by?.name || 'Customer' }}</div>
                <div>• <strong>Body:</strong> "{{ previewData.conversations[0]?.threads?.[0]?.body?.substr(0, 40) || 'The payment failed again today...' }}..."</div>
                <div>• <strong>Created:</strong> {{ previewData.conversations[0]?.threads?.[0]?.created_at || '2024-01-15 14:30' }}</div>
              </div>
            </div>
            
            <div class="space-y-2">
              <div class="font-medium text-orange-900">Service Vault Mapping:</div>
              <div class="space-y-1 text-orange-800">
                <div>• <strong>ticket_comments.external_id:</strong> thread_456</div>
                <div>• <strong>ticket_comments.ticket_id:</strong> ticket.id (from conversation)</div>
                <div>• <strong>ticket_comments.user_id:</strong> user.id (Customer)</div>
                <div>• <strong>ticket_comments.content:</strong> "The payment failed again today..."</div>
                <div>• <strong>ticket_comments.created_at:</strong> 2024-01-15 14:30</div>
                <div>• <strong>ticket_comments.is_internal:</strong> false (customer message)</div>
              </div>
            </div>
          </div>
          
          <div class="mt-3 p-3 bg-white border border-yellow-200 rounded-md">
            <div class="text-xs text-yellow-800">
              <strong>Thread Type Mapping:</strong> FreeScout 'message' → public comment, 'note' → internal comment, 
              maintaining conversation flow and agent/customer distinction.
            </div>
          </div>
        </div>

        <!-- Users and Accounts -->
        <div class="bg-gradient-to-r from-indigo-50 to-blue-50 border border-indigo-200 rounded-lg p-4">
          <div class="flex items-center mb-3">
            <div class="w-3 h-3 bg-indigo-500 rounded-full mr-3"></div>
            <h4 class="text-sm font-medium text-gray-900">Users & Account Assignment</h4>
          </div>
          
          <div class="space-y-4">
            <!-- Agent Mapping Example -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
              <div class="space-y-2">
                <div class="font-medium text-indigo-900">FreeScout Agent:</div>
                <div class="space-y-1 text-indigo-800">
                  <div>• <strong>ID:</strong> {{ previewData.users?.filter(u => u.type === 'user')?.[0]?.id || 'user_101' }}</div>
                  <div>• <strong>Email:</strong> {{ previewData.users?.filter(u => u.type === 'user')?.[0]?.email || 'agent@company.com' }}</div>
                  <div>• <strong>Name:</strong> {{ previewData.users?.filter(u => u.type === 'user')?.[0]?.name || 'Agent Smith' }}</div>
                  <div>• <strong>Role:</strong> Admin/Agent</div>
                </div>
              </div>
              
              <div class="space-y-2">
                <div class="font-medium text-blue-900">Service Vault Agent:</div>
                <div class="space-y-1 text-blue-800">
                  <div>• <strong>users.external_id:</strong> user_101</div>
                  <div>• <strong>users.email:</strong> agent@company.com</div>
                  <div>• <strong>users.name:</strong> Agent Smith</div>
                  <div>• <strong>users.user_type:</strong> 'agent'</div>
                  <div>• <strong>Account Access:</strong> {{ config.agent_access === 'all_accounts' ? 'All imported accounts' : 'Primary account only' }}</div>
                </div>
              </div>
            </div>

            <!-- Customer Mapping Example -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs border-t border-indigo-200 pt-4">
              <div class="space-y-2">
                <div class="font-medium text-indigo-900">FreeScout Customer:</div>
                <div class="space-y-1 text-indigo-800">
                  <div>• <strong>ID:</strong> {{ previewData.customers?.[0]?.id || 'customer_202' }}</div>
                  <div>• <strong>Email:</strong> {{ previewData.customers?.[0]?.emails?.[0]?.email || 'john@acme.com' }}</div>
                  <div>• <strong>Name:</strong> {{ previewData.customers?.[0]?.first_name || 'John' }} {{ previewData.customers?.[0]?.last_name || 'Doe' }}</div>
                  <div>• <strong>Domain:</strong> @acme.com</div>
                </div>
              </div>
              
              <div class="space-y-2">
                <div class="font-medium text-blue-900">Service Vault User:</div>
                <div class="space-y-1 text-blue-800">
                  <div>• <strong>users.external_id:</strong> customer_202</div>
                  <div>• <strong>users.email:</strong> john@acme.com</div>
                  <div>• <strong>users.name:</strong> John Doe</div>
                  <div>• <strong>users.user_type:</strong> 'account_user'</div>
                  <div>• <strong>Account Assignment:</strong> {{ config.account_strategy === 'domain_mapping' ? 'Acme Corporation (@acme.com)' : 'Support Account (mailbox)' }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Import Order & Dependencies -->
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
          <div class="flex items-center mb-3">
            <div class="w-3 h-3 bg-gray-500 rounded-full mr-3"></div>
            <h4 class="text-sm font-medium text-gray-900">Import Order & Dependencies</h4>
          </div>
          
          <div class="text-xs text-gray-700 space-y-2">
            <div class="font-medium text-gray-900 mb-2">Required Import Sequence:</div>
            <div class="flex items-center space-x-2">
              <div class="bg-indigo-100 text-indigo-800 px-2 py-1 rounded text-xs font-medium">1. Accounts</div>
              <ArrowRightIcon class="w-3 h-3 text-gray-400" />
              <div class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-medium">2. Users</div>
              <ArrowRightIcon class="w-3 h-3 text-gray-400" />
              <div class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-medium">3. Tickets</div>
              <ArrowRightIcon class="w-3 h-3 text-gray-400" />
              <div class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs font-medium">4. Comments</div>
              <ArrowRightIcon class="w-3 h-3 text-gray-400" />
              <div class="bg-purple-100 text-purple-800 px-2 py-1 rounded text-xs font-medium">5. Time Entries</div>
            </div>
            
            <div class="mt-3 text-xs text-gray-600">
              Each step establishes required foreign key relationships for the next step, 
              ensuring referential integrity throughout the import process.
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Time Entry Mapping Configuration Section -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
      <div class="flex items-center mb-4">
        <ClockIcon class="w-5 h-5 text-gray-400 mr-2" />
        <h3 class="text-lg font-medium text-gray-900">Time Entry Mapping Configuration</h3>
      </div>
      <p class="text-sm text-gray-600 mb-6">
        Configure how FreeScout time entries will be mapped to Service Vault's comprehensive time tracking system.
      </p>

      <!-- Time Entry Requirements Alert -->
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex items-start">
          <InformationCircleIcon class="w-5 h-5 text-blue-600 mt-0.5 mr-3" />
          <div>
            <h4 class="text-sm font-medium text-blue-900 mb-2">Service Vault Time Entry Requirements</h4>
            <div class="text-sm text-blue-800 space-y-2">
              <div>Every time entry in Service Vault requires these relationships:</div>
              <ul class="list-disc list-inside ml-2 space-y-1">
                <li><strong>account_id:</strong> Inherited from the associated ticket</li>
                <li><strong>user_id:</strong> The agent who logged the time (must be imported first)</li>
                <li><strong>ticket_id:</strong> Links to the conversation being worked on</li>
                <li><strong>billing_rate_id:</strong> Optional, can be auto-selected based on account defaults</li>
                <li><strong>duration:</strong> Converted from hours to minutes for precise tracking</li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <!-- Time Conversion Settings -->
      <div class="mb-6">
        <h4 class="text-sm font-medium text-gray-900 mb-3">Time Conversion & Formatting</h4>
        <div class="space-y-4">
          <!-- Duration Format -->
          <div class="bg-gray-50 rounded-lg p-4">
            <div class="flex items-center justify-between mb-2">
              <h5 class="text-sm font-medium text-gray-900">Duration Conversion</h5>
              <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">Auto-Configured</span>
            </div>
            <div class="text-sm text-gray-700 space-y-1">
              <div>• <strong>FreeScout format:</strong> "2.5" or "2:30" (hours)</div>
              <div>• <strong>Service Vault format:</strong> 150 (minutes)</div>
              <div>• <strong>Conversion logic:</strong> Decimal hours × 60, or parse H:MM format</div>
            </div>
          </div>

          <!-- Time Entry Status -->
          <div class="bg-gray-50 rounded-lg p-4">
            <div class="flex items-center justify-between mb-2">
              <h5 class="text-sm font-medium text-gray-900">Status & Billability</h5>
              <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">Configurable</span>
            </div>
            <div class="space-y-3">
              <div class="flex items-center">
                <input
                  id="time-entries-billable"
                  v-model="config.time_entry_defaults.billable"
                  type="checkbox"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                />
                <label for="time-entries-billable" class="ml-2 text-sm text-gray-900">
                  Mark all imported time entries as billable by default
                </label>
              </div>
              
              <div class="flex items-center">
                <input
                  id="time-entries-approved"
                  v-model="config.time_entry_defaults.approved"
                  type="checkbox"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                />
                <label for="time-entries-approved" class="ml-2 text-sm text-gray-900">
                  Auto-approve imported time entries (skip approval workflow)
                </label>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Billing Rate Strategy -->
      <div class="mb-6">
        <h4 class="text-sm font-medium text-gray-900 mb-3">Billing Rate Assignment</h4>
        <div class="space-y-3">
          <div class="flex items-start">
            <input
              id="billing-rate-auto"
              v-model="config.billing_rate_strategy"
              value="auto_select"
              type="radio"
              class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
            />
            <div class="ml-3">
              <label for="billing-rate-auto" class="block text-sm font-medium text-gray-900">
                Auto-select billing rates (Recommended)
              </label>
              <p class="mt-1 text-sm text-gray-500">
                Follow Service Vault's hierarchy: Account default → Global default → No rate
              </p>
            </div>
          </div>
          
          <div class="flex items-start">
            <input
              id="billing-rate-none"
              v-model="config.billing_rate_strategy"
              value="no_rate"
              type="radio"
              class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
            />
            <div class="ml-3">
              <label for="billing-rate-none" class="block text-sm font-medium text-gray-900">
                Import without billing rates
              </label>
              <p class="mt-1 text-sm text-gray-500">
                Time entries will be created without billing rates for manual assignment later
              </p>
            </div>
          </div>
          
          <div class="flex items-start">
            <input
              id="billing-rate-fixed"
              v-model="config.billing_rate_strategy"
              value="fixed_rate"
              type="radio"
              class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
            />
            <div class="ml-3">
              <label for="billing-rate-fixed" class="block text-sm font-medium text-gray-900">
                Use fixed billing rate for all imports
              </label>
              <p class="mt-1 text-sm text-gray-500">
                Apply the same billing rate to all imported time entries
              </p>
              
              <!-- Fixed Rate Selection (shown only when fixed_rate is selected) -->
              <div v-if="config.billing_rate_strategy === 'fixed_rate'" class="mt-3">
                <select 
                  v-model="config.fixed_billing_rate_id"
                  class="block w-full max-w-xs text-sm border border-gray-300 rounded-md px-3 py-2"
                >
                  <option value="">Select a billing rate...</option>
                  <option v-for="rate in mockBillingRates" :key="rate.id" :value="rate.id">
                    {{ rate.name }} • ${{ rate.rate_per_hour }}/hr
                  </option>
                </select>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Time Entry Relationship Preview -->
      <div class="bg-gradient-to-r from-purple-50 to-pink-50 border border-purple-200 rounded-lg p-4">
        <div class="flex items-center mb-3">
          <div class="w-3 h-3 bg-purple-500 rounded-full mr-3"></div>
          <h4 class="text-sm font-medium text-gray-900">Complete Time Entry Relationship Mapping</h4>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
          <div class="space-y-2">
            <div class="font-medium text-purple-900">FreeScout Time Entry:</div>
            <div class="space-y-1 text-purple-800">
              <div>• <strong>ID:</strong> {{ previewData.time_entries?.[0]?.id || 'time_789' }}</div>
              <div>• <strong>Time:</strong> {{ previewData.time_entries?.[0]?.time || '2.5' }} hours</div>
              <div>• <strong>User:</strong> {{ previewData.time_entries?.[0]?.user?.name || 'Agent Smith' }}</div>
              <div>• <strong>Conversation:</strong> #{{ previewData.time_entries?.[0]?.conversation_id || 'conversation_123' }}</div>
              <div>• <strong>Note:</strong> "{{ previewData.time_entries?.[0]?.note || 'Investigated payment gateway issue' }}"</div>
              <div>• <strong>Created:</strong> {{ previewData.time_entries?.[0]?.created_at || '2024-01-15 16:45' }}</div>
            </div>
          </div>
          
          <div class="space-y-2">
            <div class="font-medium text-pink-900">Complete Service Vault Mapping:</div>
            <div class="space-y-1 text-pink-800">
              <div>• <strong>time_entries.external_id:</strong> time_789</div>
              <div>• <strong>time_entries.duration:</strong> 150 minutes (2.5h × 60)</div>
              <div>• <strong>time_entries.user_id:</strong> agent.id (Agent Smith)</div>
              <div>• <strong>time_entries.ticket_id:</strong> ticket.id (from conversation)</div>
              <div>• <strong>time_entries.account_id:</strong> ticket.account_id (inherited)</div>
              <div>• <strong>time_entries.billing_rate_id:</strong> {{ config.billing_rate_strategy === 'auto_select' ? 'auto-selected' : config.billing_rate_strategy === 'fixed_rate' ? 'fixed rate' : 'null' }}</div>
              <div>• <strong>time_entries.description:</strong> "Investigated payment gateway issue"</div>
              <div>• <strong>time_entries.started_at:</strong> 2024-01-15 16:45 (from FreeScout)</div>
              <div>• <strong>time_entries.billable:</strong> {{ config.time_entry_defaults.billable ? 'true' : 'false' }}</div>
              <div>• <strong>time_entries.approval_status:</strong> {{ config.time_entry_defaults.approved ? 'approved' : 'pending' }}</div>
            </div>
          </div>
        </div>
        
        <div class="mt-3 p-3 bg-white border border-purple-200 rounded-md">
          <div class="text-xs text-purple-800">
            <strong>Relationship Flow:</strong> FreeScout time entry → Service Vault ticket (via conversation) → 
            account (inherited from ticket) → agent (user mapping) → billing rate (strategy-based)
          </div>
        </div>
      </div>
    </div>

    <!-- Conversation Threads to Comments Mapping Section -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
      <div class="flex items-center mb-4">
        <ChatBubbleLeftRightIcon class="w-5 h-5 text-gray-400 mr-2" />
        <h3 class="text-lg font-medium text-gray-900">Conversation Threads to Comments Mapping</h3>
      </div>
      <p class="text-sm text-gray-600 mb-6">
        Configure how FreeScout conversation threads (messages, notes, and replies) will be mapped to Service Vault ticket comments.
      </p>

      <!-- Thread Type Mapping -->
      <div class="mb-6">
        <h4 class="text-sm font-medium text-gray-900 mb-3">Thread Type Classification</h4>
        <div class="bg-gradient-to-r from-blue-50 to-green-50 border border-blue-200 rounded-lg p-4">
          <div class="space-y-3">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
              <div class="bg-white rounded-lg p-3 border border-blue-200">
                <div class="font-medium text-blue-900 mb-2">FreeScout "message"</div>
                <div class="text-blue-800 text-xs space-y-1">
                  <div>• Customer or agent replies</div>
                  <div>• Visible to customers</div>
                  <div>• Part of conversation flow</div>
                </div>
                <div class="mt-2 text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
                  → Public Comment
                </div>
              </div>
              
              <div class="bg-white rounded-lg p-3 border border-yellow-200">
                <div class="font-medium text-yellow-900 mb-2">FreeScout "note"</div>
                <div class="text-yellow-800 text-xs space-y-1">
                  <div>• Internal agent notes</div>
                  <div>• Hidden from customers</div>
                  <div>• Agent collaboration</div>
                </div>
                <div class="mt-2 text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded">
                  → Internal Comment
                </div>
              </div>
              
              <div class="bg-white rounded-lg p-3 border border-green-200">
                <div class="font-medium text-green-900 mb-2">FreeScout "forward"</div>
                <div class="text-green-800 text-xs space-y-1">
                  <div>• Forwarded messages</div>
                  <div>• External communication</div>
                  <div>• Context reference</div>
                </div>
                <div class="mt-2 text-xs bg-green-100 text-green-800 px-2 py-1 rounded">
                  → Internal Comment (with prefix)
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Comment Attribution -->
      <div class="mb-6">
        <h4 class="text-sm font-medium text-gray-900 mb-3">Author Attribution & Timestamps</h4>
        <div class="space-y-4">
          <div class="bg-gray-50 rounded-lg p-4">
            <div class="flex items-center justify-between mb-2">
              <h5 class="text-sm font-medium text-gray-900">Author Mapping</h5>
              <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">Relationship-Based</span>
            </div>
            <div class="text-sm text-gray-700 space-y-2">
              <div>• <strong>FreeScout User ID:</strong> Maps to imported Service Vault user</div>
              <div>• <strong>Customer Messages:</strong> Attributed to customer's Service Vault account</div>
              <div>• <strong>Agent Messages:</strong> Attributed to agent's Service Vault profile</div>
              <div>• <strong>Deleted Users:</strong> Handle gracefully with "[Deleted User]" fallback</div>
            </div>
          </div>
          
          <div class="bg-gray-50 rounded-lg p-4">
            <div class="flex items-center justify-between mb-2">
              <h5 class="text-sm font-medium text-gray-900">Timestamp Preservation</h5>
              <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">Exact Copy</span>
            </div>
            <div class="text-sm text-gray-700 space-y-2">
              <div>• <strong>Created At:</strong> Preserve original FreeScout timestamp</div>
              <div>• <strong>Updated At:</strong> Copy from FreeScout or set to created_at if missing</div>
              <div>• <strong>Chronological Order:</strong> Maintain thread sequence in ticket timeline</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Content Processing -->
      <div class="mb-6">
        <h4 class="text-sm font-medium text-gray-900 mb-3">Content Processing Options</h4>
        <div class="space-y-3">
          <div class="flex items-center">
            <input
              id="preserve-html"
              v-model="config.comment_processing.preserve_html"
              type="checkbox"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <label for="preserve-html" class="ml-2 text-sm text-gray-900">
              Preserve HTML formatting in comment content
            </label>
          </div>
          <p v-if="config.comment_processing.preserve_html" class="ml-6 text-xs text-gray-500">
            FreeScout HTML content will be imported as-is. Service Vault supports HTML in comments.
          </p>
          
          <div class="flex items-center">
            <input
              id="extract-attachments"
              v-model="config.comment_processing.extract_attachments"
              type="checkbox"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <label for="extract-attachments" class="ml-2 text-sm text-gray-900">
              Extract and reference attachments in comment content
            </label>
          </div>
          <p v-if="config.comment_processing.extract_attachments" class="ml-6 text-xs text-gray-500">
            Attachment references will be added as footnotes. Actual file import requires separate configuration.
          </p>
          
          <div class="flex items-center">
            <input
              id="add-context-prefix"
              v-model="config.comment_processing.add_context_prefix"
              type="checkbox"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <label for="add-context-prefix" class="ml-2 text-sm text-gray-900">
              Add contextual prefixes to imported comments
            </label>
          </div>
          <p v-if="config.comment_processing.add_context_prefix" class="ml-6 text-xs text-gray-500">
            Prefixes like "[Imported from FreeScout]" or "[Forwarded]" will help identify comment origins.
          </p>
        </div>
      </div>

      <!-- Comments Relationship Preview -->
      <div class="bg-gradient-to-r from-orange-50 to-red-50 border border-orange-200 rounded-lg p-4">
        <div class="flex items-center mb-3">
          <div class="w-3 h-3 bg-orange-500 rounded-full mr-3"></div>
          <h4 class="text-sm font-medium text-gray-900">Complete Comment Relationship Mapping</h4>
        </div>
        
        <div class="space-y-4">
          <!-- Customer Message Example -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <div class="space-y-2">
              <div class="font-medium text-orange-900">FreeScout Customer Message:</div>
              <div class="space-y-1 text-orange-800">
                <div>• <strong>Thread ID:</strong> {{ previewData.conversations[0]?.threads?.[0]?.id || 'thread_456' }}</div>
                <div>• <strong>Type:</strong> "message" (customer reply)</div>
                <div>• <strong>Author:</strong> {{ previewData.conversations[0]?.threads?.[0]?.created_by?.name || 'Customer' }}</div>
                <div>• <strong>Body:</strong> "{{ previewData.conversations[0]?.threads?.[0]?.body?.substr(0, 50) || 'The payment failed again today. Can you help me understand why...' }}..."</div>
                <div>• <strong>Created:</strong> {{ previewData.conversations[0]?.threads?.[0]?.created_at || '2024-01-15 14:30' }}</div>
              </div>
            </div>
            
            <div class="space-y-2">
              <div class="font-medium text-red-900">Service Vault Public Comment:</div>
              <div class="space-y-1 text-red-800">
                <div>• <strong>ticket_comments.external_id:</strong> thread_456</div>
                <div>• <strong>ticket_comments.ticket_id:</strong> ticket.id (from conversation)</div>
                <div>• <strong>ticket_comments.user_id:</strong> customer.id (Customer)</div>
                <div>• <strong>ticket_comments.is_internal:</strong> false</div>
                <div>• <strong>ticket_comments.content:</strong> {{ config.comment_processing.add_context_prefix ? '"[Imported from FreeScout] The payment failed..."' : '"The payment failed again today..."' }}</div>
                <div>• <strong>ticket_comments.created_at:</strong> 2024-01-15 14:30</div>
              </div>
            </div>
          </div>

          <!-- Agent Note Example -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs border-t border-orange-200 pt-4">
            <div class="space-y-2">
              <div class="font-medium text-orange-900">FreeScout Agent Note:</div>
              <div class="space-y-1 text-orange-800">
                <div>• <strong>Thread ID:</strong> thread_789</div>
                <div>• <strong>Type:</strong> "note" (internal)</div>
                <div>• <strong>Author:</strong> Agent Smith</div>
                <div>• <strong>Body:</strong> "Checked payment gateway logs. Issue is with their expired credit card..."</div>
                <div>• <strong>Created:</strong> 2024-01-15 15:45</div>
              </div>
            </div>
            
            <div class="space-y-2">
              <div class="font-medium text-red-900">Service Vault Internal Comment:</div>
              <div class="space-y-1 text-red-800">
                <div>• <strong>ticket_comments.external_id:</strong> thread_789</div>
                <div>• <strong>ticket_comments.ticket_id:</strong> ticket.id (same conversation)</div>
                <div>• <strong>ticket_comments.user_id:</strong> agent.id (Agent Smith)</div>
                <div>• <strong>ticket_comments.is_internal:</strong> true</div>
                <div>• <strong>ticket_comments.content:</strong> {{ config.comment_processing.add_context_prefix ? '"[Agent Note] Checked payment gateway logs..."' : '"Checked payment gateway logs..."' }}</div>
                <div>• <strong>ticket_comments.created_at:</strong> 2024-01-15 15:45</div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="mt-3 p-3 bg-white border border-orange-200 rounded-md">
          <div class="text-xs text-orange-800">
            <strong>Chronological Preservation:</strong> All comments maintain original timestamps and order, 
            preserving the complete conversation flow while respecting Service Vault's internal/public comment distinction.
          </div>
        </div>
      </div>
    </div>

    <!-- Unmapped User Handling Section -->
    <div 
      v-if="config.account_strategy === 'domain_mapping'"
      class="bg-yellow-50 border border-yellow-200 rounded-lg p-6"
    >
      <div class="flex items-center mb-4">
        <ExclamationTriangleIcon class="w-5 h-5 text-yellow-600 mr-2" />
        <h3 class="text-lg font-medium text-gray-900">Unmapped User Handling</h3>
      </div>
      <p class="text-sm text-gray-600 mb-6">
        Some customer emails may not match existing domain mappings. Choose how to handle them.
      </p>

      <div class="space-y-4">
        <!-- Auto-create accounts -->
        <div class="flex items-start">
          <input
            id="auto-create"
            v-model="config.unmapped_users"
            value="auto_create"
            type="radio"
            class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
          />
          <div class="ml-3">
            <label for="auto-create" class="block text-sm font-medium text-gray-900">
              Automatically create accounts
            </label>
            <p class="mt-1 text-sm text-gray-500">
              Create new accounts for customers whose email domains don't match existing mappings.
            </p>
          </div>
        </div>

        <!-- Skip unmapped users -->
        <div class="flex items-start">
          <input
            id="skip-unmapped"
            v-model="config.unmapped_users"
            value="skip"
            type="radio"
            class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
          />
          <div class="ml-3">
            <label for="skip-unmapped" class="block text-sm font-medium text-gray-900">
              Skip unmapped customers
            </label>
            <p class="mt-1 text-sm text-gray-500">
              Only import conversations from customers whose domains match existing account mappings.
            </p>
          </div>
        </div>

        <!-- Use default account -->
        <div class="flex items-start">
          <input
            id="default-account"
            v-model="config.unmapped_users"
            value="default_account"
            type="radio"
            class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
          />
          <div class="ml-3">
            <label for="default-account" class="block text-sm font-medium text-gray-900">
              Assign to default account
            </label>
            <p class="mt-1 text-sm text-gray-500">
              Add all unmapped customers to a single "Imported Customers" account.
            </p>
          </div>
        </div>
      </div>

      <!-- Unmapped customers preview -->
      <div class="mt-4 bg-white rounded-md p-3 border">
        <p class="text-xs font-medium text-gray-700 mb-2">
          Potentially unmapped customers ({{ mockUnmappedCustomers.length }}):
        </p>
        <div class="flex flex-wrap gap-2">
          <span 
            v-for="customer in mockUnmappedCustomers.slice(0, 5)"
            :key="customer.id"
            class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-800"
          >
            {{ customer.email }}
          </span>
          <span 
            v-if="mockUnmappedCustomers.length > 5"
            class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-600"
          >
            +{{ mockUnmappedCustomers.length - 5 }} more
          </span>
        </div>
      </div>
    </div>

    <!-- Mailbox Exclusion Section -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
      <div class="flex items-center mb-4">
        <InboxIcon class="w-5 h-5 text-gray-400 mr-2" />
        <h3 class="text-lg font-medium text-gray-900">Mailbox Exclusions</h3>
      </div>
      <p class="text-sm text-gray-600 mb-6">
        All mailboxes are included by default. Check mailboxes below to exclude them from the import.
      </p>

      <div class="space-y-3">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center space-x-3">
            <button
              @click="includeAllMailboxes"
              class="text-sm text-indigo-600 hover:text-indigo-700 font-medium"
            >
              Include All
            </button>
            <span class="text-gray-300">|</span>
            <button
              @click="excludeAllMailboxes"
              class="text-sm text-indigo-600 hover:text-indigo-700 font-medium"
            >
              Exclude All
            </button>
          </div>
          <span class="text-sm text-gray-500">
            {{ excludedMailboxes.length }} of {{ previewData.mailboxes.length }} excluded
          </span>
        </div>

        <div class="space-y-2">
          <div
            v-for="mailbox in previewData.mailboxes"
            :key="mailbox.id"
            :class="[
              'flex items-start p-3 rounded-lg border transition-colors',
              excludedMailboxes.includes(mailbox.id) 
                ? 'border-red-200 bg-red-50' 
                : 'border-green-200 bg-green-50'
            ]"
          >
            <div class="flex items-center h-5">
              <input
                :id="`mailbox-${mailbox.id}`"
                v-model="excludedMailboxes"
                :value="mailbox.id"
                type="checkbox"
                class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded"
              />
            </div>
            <div class="ml-3 flex-1">
              <label 
                :for="`mailbox-${mailbox.id}`" 
                :class="[
                  'block text-sm font-medium cursor-pointer',
                  excludedMailboxes.includes(mailbox.id) ? 'text-red-900 line-through' : 'text-green-900'
                ]"
              >
                {{ mailbox.name }}
              </label>
              <div 
                :class="[
                  'text-xs mt-1',
                  excludedMailboxes.includes(mailbox.id) ? 'text-red-700 opacity-75' : 'text-green-700'
                ]"
              >
                <div class="flex items-center space-x-4">
                  <span>{{ mailbox.email }}</span>
                  <span>•</span>
                  <span>{{ (mailbox.conversation_count || 0).toLocaleString() }} conversations</span>
                  <span>•</span>
                  <span>{{ mailbox.user_count }} users</span>
                </div>
                <div v-if="mailbox.description" class="mt-1 text-xs opacity-75">
                  {{ mailbox.description }}
                </div>
              </div>
            </div>
            <div class="ml-3 flex-shrink-0">
              <div 
                :class="[
                  'text-xs px-2 py-1 rounded-full font-medium',
                  excludedMailboxes.includes(mailbox.id)
                    ? 'bg-red-100 text-red-700'
                    : 'bg-green-100 text-green-700'
                ]"
              >
                {{ excludedMailboxes.includes(mailbox.id) ? 'Excluded' : 'Included' }}
              </div>
            </div>
          </div>
        </div>

        <!-- Exclusion Warning -->
        <div v-if="excludedMailboxes.length > 0" class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
          <div class="flex items-center">
            <ExclamationTriangleIcon class="w-4 h-4 text-yellow-600 mr-2 flex-shrink-0" />
            <div class="text-sm text-yellow-800">
              <strong>{{ excludedMailboxes.length }} mailbox(es) excluded.</strong>
              Conversations from excluded mailboxes will not be imported.
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Sync Strategy & Duplicate Detection Section -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
      <div class="flex items-center mb-4">
        <ArrowPathIcon class="w-5 h-5 text-gray-400 mr-2" />
        <h3 class="text-lg font-medium text-gray-900">Sync Strategy & Duplicate Detection</h3>
      </div>
      <p class="text-sm text-gray-600 mb-6">
        Configure how the import handles existing records and prevents duplicates across multiple import runs.
      </p>
      
      <!-- Time Entry Sync Challenge Alert -->
      <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
        <div class="flex items-start">
          <ExclamationTriangleIcon class="w-5 h-5 text-amber-600 mt-0.5 mr-3" />
          <div>
            <h4 class="text-sm font-medium text-amber-900 mb-2">Time Entry Sync Challenge</h4>
            <p class="text-sm text-amber-700 mb-2">
              Time entries are nested under conversations in FreeScout. If someone adds a time entry to an older conversation, 
              incremental sync (using <code>?updatedSince</code>) may miss it since the conversation's update timestamp might be older than your last sync.
            </p>
            <p class="text-sm text-amber-700">
              <strong>Recommendation:</strong> Use "Incremental + Periodic Full Scan" mode to efficiently catch all time entry additions.
            </p>
          </div>
        </div>
      </div>

      <!-- Sync Strategy -->
      <div class="space-y-6">
        <div>
          <h4 class="text-sm font-medium text-gray-900 mb-3">Import Mode</h4>
          <div class="space-y-3">
            <!-- Create Only -->
            <div class="flex items-start">
              <input
                id="sync-create-only"
                v-model="config.sync_strategy"
                value="create_only"
                type="radio"
                class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
              />
              <div class="ml-3">
                <label for="sync-create-only" class="block text-sm font-medium text-gray-900">
                  Create Only (Skip Existing)
                </label>
                <p class="mt-1 text-sm text-gray-500">
                  Only create new records. Skip any records that already exist in Service Vault. Safest option for initial imports.
                </p>
              </div>
            </div>

            <!-- Update Only -->
            <div class="flex items-start">
              <input
                id="sync-update-only"
                v-model="config.sync_strategy"
                value="update_only"
                type="radio"
                class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
              />
              <div class="ml-3">
                <label for="sync-update-only" class="block text-sm font-medium text-gray-900">
                  Update Only (Skip New)
                </label>
                <p class="mt-1 text-sm text-gray-500">
                  Only update existing records. Skip any records that don't exist in Service Vault. Useful for syncing changes.
                </p>
              </div>
            </div>

            <!-- Upsert -->
            <div class="flex items-start">
              <input
                id="sync-upsert"
                v-model="config.sync_strategy"
                value="upsert"
                type="radio"
                class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
              />
              <div class="ml-3">
                <label for="sync-upsert" class="block text-sm font-medium text-gray-900">
                  Create or Update (Upsert)
                </label>
                <p class="mt-1 text-sm text-gray-500">
                  Create new records if they don't exist, update them if they do. Best for ongoing synchronization.
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Sync Mode for Time Entries -->
        <div>
          <h4 class="text-sm font-medium text-gray-900 mb-3">Sync Mode</h4>
          <div class="space-y-3">
            <!-- Incremental Only -->
            <div class="flex items-start">
              <input
                id="sync-incremental"
                v-model="config.sync_mode"
                value="incremental"
                type="radio"
                class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
              />
              <div class="ml-3">
                <label for="sync-incremental" class="block text-sm font-medium text-gray-900">
                  Incremental Only (Fast)
                </label>
                <p class="mt-1 text-sm text-gray-500">
                  Use <code>?updatedSince</code> to only sync recently changed conversations. Faster but may miss time entries added to older conversations.
                </p>
              </div>
            </div>

            <!-- Full Scan -->
            <div class="flex items-start">
              <input
                id="sync-full"
                v-model="config.sync_mode"
                value="full_scan"
                type="radio"
                class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
              />
              <div class="ml-3">
                <label for="sync-full" class="block text-sm font-medium text-gray-900">
                  Full Scan (Thorough)
                </label>
                <p class="mt-1 text-sm text-gray-500">
                  Check ALL conversations for time entries. Slower but guaranteed to catch all time entry additions, even on older conversations.
                </p>
              </div>
            </div>

            <!-- Hybrid Recommended -->
            <div class="flex items-start">
              <input
                id="sync-hybrid"
                v-model="config.sync_mode"
                value="hybrid"
                type="radio"
                class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
              />
              <div class="ml-3">
                <label for="sync-hybrid" class="block text-sm font-medium text-gray-900">
                  Incremental + Periodic Full Scan (Recommended)
                </label>
                <p class="mt-1 text-sm text-gray-500">
                  Daily incremental sync for efficiency, plus weekly/monthly full scan to catch missed time entries. Best balance of speed and completeness.
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Duplicate Detection Strategy -->
        <div>
          <h4 class="text-sm font-medium text-gray-900 mb-3">Duplicate Detection Method</h4>
          <div class="space-y-3">
            <!-- External ID -->
            <div class="flex items-start">
              <input
                id="detect-external-id"
                v-model="config.duplicate_detection"
                value="external_id"
                type="radio"
                class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
              />
              <div class="ml-3">
                <label for="detect-external-id" class="block text-sm font-medium text-gray-900">
                  External ID Matching (Recommended)
                </label>
                <p class="mt-1 text-sm text-gray-500">
                  Track records using FreeScout IDs. Most reliable method for detecting duplicates across imports.
                </p>
              </div>
            </div>

            <!-- Email/Subject -->
            <div class="flex items-start">
              <input
                id="detect-content"
                v-model="config.duplicate_detection"
                value="content_match"
                type="radio"
                class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
              />
              <div class="ml-3">
                <label for="detect-content" class="block text-sm font-medium text-gray-900">
                  Content Matching
                </label>
                <p class="mt-1 text-sm text-gray-500">
                  Match conversations by subject + customer email, customers by email, time entries by conversation + agent + date.
                </p>
              </div>
            </div>

          </div>
        </div>

        <!-- External ID Field Mapping -->
        <div v-if="config.duplicate_detection === 'external_id'" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <div class="flex items-center mb-3">
            <InformationCircleIcon class="w-4 h-4 text-blue-600 mr-2" />
            <h5 class="text-sm font-medium text-blue-900">External ID Storage</h5>
          </div>
          <div class="text-sm text-blue-800 space-y-2">
            <p>FreeScout IDs will be stored in the following Service Vault fields:</p>
            <ul class="list-disc list-inside ml-4 space-y-1">
              <li><strong>Conversations:</strong> <code>tickets.external_id</code> (FreeScout conversation ID)</li>
              <li><strong>Customers:</strong> <code>users.external_id</code> (FreeScout customer ID)</li>
              <li><strong>Time Entries:</strong> <code>time_entries.external_id</code> (FreeScout time entry ID)</li>
              <li><strong>Accounts:</strong> <code>accounts.external_id</code> (FreeScout mailbox ID)</li>
            </ul>
          </div>
        </div>

        <!-- Import Profile Persistence -->
        <div>
          <h4 class="text-sm font-medium text-gray-900 mb-3">Import Tracking</h4>
          <div class="space-y-3">
            <div class="flex items-center">
              <input
                id="track-imports"
                v-model="config.track_import_runs"
                type="checkbox"
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
              />
              <label for="track-imports" class="ml-2 text-sm text-gray-900">
                Track import runs and maintain sync history
              </label>
            </div>
            <p v-if="config.track_import_runs" class="ml-6 text-xs text-gray-500">
              Each import will be logged with timestamps, record counts, and duplicate statistics for audit purposes.
            </p>

            <div class="flex items-center">
              <input
                id="auto-schedule"
                v-model="config.enable_scheduling"
                type="checkbox"
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
              />
              <label for="auto-schedule" class="ml-2 text-sm text-gray-900">
                Enable automatic synchronization
              </label>
            </div>
            
            <div v-if="config.enable_scheduling" class="ml-6 space-y-2">
              <div class="flex items-center space-x-3">
                <select 
                  v-model="config.sync_frequency"
                  class="text-sm border border-gray-300 rounded-md px-2 py-1"
                >
                  <option value="hourly">Every hour</option>
                  <option value="daily">Daily</option>
                  <option value="weekly">Weekly</option>
                  <option value="manual">Manual only</option>
                </select>
                <span class="text-xs text-gray-500">sync frequency</span>
              </div>
              <div class="text-xs text-gray-500">
                Automatic syncs will use the current configuration and run in the background.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Date Range Filter Section -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
      <div class="flex items-center mb-4">
        <CalendarIcon class="w-5 h-5 text-gray-400 mr-2" />
        <h3 class="text-lg font-medium text-gray-900">Date Range Filter</h3>
      </div>
      <p class="text-sm text-gray-600 mb-6">
        Optionally filter conversations by creation date to import only recent data.
      </p>

      <div class="flex items-center space-x-4 mb-4">
        <input
          id="enable-date-filter"
          v-model="config.enable_date_filter"
          type="checkbox"
          class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
        />
        <label for="enable-date-filter" class="text-sm font-medium text-gray-900">
          Filter by date range
        </label>
      </div>

      <div v-if="config.enable_date_filter" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label for="start-date" class="block text-sm font-medium text-gray-700 mb-2">
            From Date
          </label>
          <input
            id="start-date"
            v-model="config.date_range.start"
            type="date"
            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
          />
        </div>
        <div>
          <label for="end-date" class="block text-sm font-medium text-gray-700 mb-2">
            To Date
          </label>
          <input
            id="end-date"
            v-model="config.date_range.end"
            type="date"
            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
          />
        </div>
      </div>
    </div>

    <!-- Import Summary -->
    <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-6">
      <div class="flex items-center mb-4">
        <InformationCircleIcon class="w-5 h-5 text-indigo-600 mr-2" />
        <h3 class="text-lg font-medium text-gray-900">Import Summary</h3>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg p-4 text-center">
          <div class="text-2xl font-bold text-indigo-600">{{ estimatedImportCounts.conversations }}</div>
          <div class="text-sm text-gray-600">Conversations</div>
        </div>
        <div class="bg-white rounded-lg p-4 text-center">
          <div class="text-2xl font-bold text-indigo-600">{{ estimatedImportCounts.time_entries }}</div>
          <div class="text-sm text-gray-600">Time Entries</div>
        </div>
        <div class="bg-white rounded-lg p-4 text-center">
          <div class="text-2xl font-bold text-indigo-600">{{ estimatedImportCounts.customers }}</div>
          <div class="text-sm text-gray-600">Customers</div>
        </div>
        <div class="bg-white rounded-lg p-4 text-center">
          <div class="text-2xl font-bold text-indigo-600">{{ estimatedImportCounts.accounts }}</div>
          <div class="text-sm text-gray-600">Accounts</div>
        </div>
      </div>

      <div class="text-sm text-gray-600 space-y-2">
        <div class="flex items-center">
          <CheckIcon class="w-4 h-4 text-green-500 mr-2" />
          <span>{{ config.account_strategy === 'map_mailboxes' ? 'Mailboxes will be mapped to accounts' : 'Using existing domain mapping' }}</span>
        </div>
        <div v-if="config.account_strategy === 'domain_mapping'" class="flex items-center">
          <CheckIcon class="w-4 h-4 text-green-500 mr-2" />
          <span>Unmapped users will be {{ config.unmapped_users === 'auto_create' ? 'auto-created' : config.unmapped_users === 'skip' ? 'skipped' : 'assigned to default account' }}</span>
        </div>
        <div class="flex items-center">
          <CheckIcon class="w-4 h-4 text-green-500 mr-2" />
          <span>
            Sync mode: {{ config.sync_strategy === 'create_only' ? 'Create new records only' : 
                          config.sync_strategy === 'update_only' ? 'Update existing records only' : 
                          'Create new and update existing (upsert)' }}
          </span>
        </div>
        <div class="flex items-center">
          <CheckIcon class="w-4 h-4 text-green-500 mr-2" />
          <span>
            Duplicate detection: {{ config.duplicate_detection === 'external_id' ? 'External ID matching' : 'Content matching' }}
          </span>
        </div>
        <div class="flex items-center">
          <CheckIcon class="w-4 h-4 text-green-500 mr-2" />
          <span>
            Sync mode: {{ config.sync_mode === 'incremental' ? 'Incremental only (fast)' : 
                          config.sync_mode === 'full_scan' ? 'Full scan (thorough)' : 
                          'Incremental + Periodic full scan (recommended)' }}
          </span>
        </div>
        <div v-if="excludedMailboxes.length > 0" class="flex items-center">
          <CheckIcon class="w-4 h-4 text-green-500 mr-2" />
          <span>{{ excludedMailboxes.length }} mailbox(es) excluded from import</span>
        </div>
        <div v-if="config.enable_date_filter" class="flex items-center">
          <CheckIcon class="w-4 h-4 text-green-500 mr-2" />
          <span>Filtering conversations from {{ config.date_range.start }} to {{ config.date_range.end }}</span>
        </div>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex justify-end space-x-3">
      <button
        @click="$emit('preview', config)"
        class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
      >
        Preview Import Data
      </button>
      <button
        @click="$emit('execute', config)"
        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
      >
        Execute Import
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import {
  AdjustmentsHorizontalIcon,
  BuildingOfficeIcon,
  UserPlusIcon,
  CalendarIcon,
  InformationCircleIcon,
  CheckIcon,
  InboxIcon,
  ExclamationTriangleIcon,
  ArrowPathIcon,
  ShareIcon,
  ArrowRightIcon,
  ClockIcon,
  ChatBubbleLeftRightIcon,
  CheckCircleIcon,
  CogIcon,
  ChartBarIcon,
  DocumentTextIcon
} from '@heroicons/vue/24/outline'

// Props
const props = defineProps({
  profile: {
    type: Object,
    required: true
  },
  previewData: {
    type: Object,
    required: true
  },
  loadingPreview: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits(['preview', 'execute'])

// Configuration state
const config = ref({
  limits: {
    conversations: null,
    time_entries: null,
    customers: null,
    mailboxes: null
  },
  account_strategy: 'map_mailboxes', // 'map_mailboxes', 'domain_mapping', 'hybrid'
  agent_access: 'all_accounts', // 'all_accounts', 'primary_account'
  unmapped_users: 'auto_create', // 'auto_create', 'skip', 'default_account'
  enable_date_filter: false,
  date_range: {
    start: '',
    end: ''
  },
  excluded_mailboxes: [], // Array of excluded mailbox IDs
  
  // Time Entry Configuration
  time_entry_defaults: {
    billable: true,
    approved: false
  },
  billing_rate_strategy: 'auto_select', // 'auto_select', 'no_rate', 'fixed_rate'
  fixed_billing_rate_id: null,
  
  // Comment Processing Configuration
  comment_processing: {
    preserve_html: true,
    extract_attachments: false,
    add_context_prefix: true
  },
  
  // Sync Strategy & Duplicate Detection
  sync_strategy: 'create_only', // 'create_only', 'update_only', 'upsert'
  sync_mode: 'hybrid', // 'incremental', 'full_scan', 'hybrid'
  duplicate_detection: 'external_id', // 'external_id', 'content_match'
  track_import_runs: true,
  enable_scheduling: false,
  sync_frequency: 'manual' // 'hourly', 'daily', 'weekly', 'manual'
})

// Mailbox exclusion
const excludedMailboxes = ref([])

// Initialize with no mailboxes excluded by default
const initializeMailboxes = () => {
  if (props.previewData && props.previewData.mailboxes) {
    excludedMailboxes.value = [] // Start with no exclusions
    config.value.excluded_mailboxes = []
  }
}

// Watch for changes in excludedMailboxes and update config
watch(excludedMailboxes, (newVal) => {
  config.value.excluded_mailboxes = [...newVal]
}, { deep: true })

// Initialize mailboxes on mount
onMounted(() => {
  initializeMailboxes()
})

// Mock domain mappings
const mockDomainMappings = ref([
  { id: 1, pattern: '@acme.com', account: 'Acme Corporation' },
  { id: 2, pattern: '@example.org', account: 'Example Foundation' },
  { id: 3, pattern: '@widgets.co', account: 'Widget Solutions' },
  { id: 4, pattern: '@test.io', account: 'Test Industries' },
  { id: 5, pattern: '@startup.app', account: 'Startup Accelerator' }
])

// Mock customers that wouldn't match domain mappings
const mockUnmappedCustomers = ref([
  { id: 1, email: 'john@randomcorp.biz' },
  { id: 2, email: 'sarah@newcompany.net' },
  { id: 3, email: 'mike@freelancer.gmail.com' },
  { id: 4, email: 'lisa@consultancy.org' },
  { id: 5, email: 'david@agency.co' },
  { id: 6, email: 'emma@solutions.info' }
])

// Mock billing rates for fixed rate selection
const mockBillingRates = ref([
  { id: 1, name: 'Standard Hourly', rate_per_hour: 75.00 },
  { id: 2, name: 'Senior Developer', rate_per_hour: 125.00 },
  { id: 3, name: 'Project Management', rate_per_hour: 95.00 },
  { id: 4, name: 'Support Technician', rate_per_hour: 55.00 },
  { id: 5, name: 'Consultation', rate_per_hour: 150.00 }
])

// Methods
const includeAllMailboxes = () => {
  excludedMailboxes.value = [] // Clear all exclusions
}

const excludeAllMailboxes = () => {
  excludedMailboxes.value = (props.previewData.mailboxes || []).map(m => m.id) // Exclude all
}

// Computed estimated import counts
const estimatedImportCounts = computed(() => {
  // Filter based on included mailboxes (all except excluded ones)
  const includedMailboxData = (props.previewData.mailboxes || []).filter(m => 
    !excludedMailboxes.value.includes(m.id)
  )
  
  // Calculate conversations from included mailboxes only
  const totalConversationsFromIncluded = includedMailboxData.reduce((sum, mailbox) => 
    sum + mailbox.conversation_count, 0
  )
  
  const conversationLimit = config.value.limits.conversations || totalConversationsFromIncluded
  const timeEntriesLimit = config.value.limits.time_entries || (props.previewData.time_entries?.length || 0)
  const customerLimit = config.value.limits.customers || (props.previewData.customers?.length || 0)
  const mailboxLimit = config.value.limits.mailboxes || includedMailboxData.length
  
  let accountCount = 0
  if (config.value.account_strategy === 'map_mailboxes') {
    accountCount = Math.min(mailboxLimit, includedMailboxData.length)
  } else {
    // Domain mapping strategy
    const mappedCount = mockDomainMappings.value.length
    const unmappedCount = mockUnmappedCustomers.value.length
    
    if (config.value.unmapped_users === 'auto_create') {
      accountCount = mappedCount + unmappedCount
    } else if (config.value.unmapped_users === 'default_account') {
      accountCount = mappedCount + 1 // +1 for default account
    } else {
      accountCount = mappedCount // Skip unmapped
    }
  }
  
  return {
    conversations: Math.min(conversationLimit, totalConversationsFromIncluded).toLocaleString(),
    time_entries: Math.min(timeEntriesLimit, props.previewData.time_entries ? props.previewData.time_entries.length : 0).toLocaleString(),
    customers: Math.min(customerLimit, (props.previewData.customers || []).length).toLocaleString(),
    accounts: accountCount.toLocaleString()
  }
})
</script>