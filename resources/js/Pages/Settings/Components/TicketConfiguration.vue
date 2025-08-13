<template>
  <div class="space-y-8">
    <!-- Ticket Configuration Header -->
    <div>
      <h2 class="text-2xl font-semibold text-gray-900">Ticket Configuration</h2>
      <p class="text-gray-600 mt-2">Manage ticket statuses, categories, workflow, and attachment settings.</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-8">
      <svg class="animate-spin h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
    </div>

    <template v-else>
      <!-- Sub-Tab Navigation -->
      <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
          <button
            v-for="subTab in subTabs"
            :key="subTab.id"
            @click="activeSubTab = subTab.id"
            :class="[
              activeSubTab === subTab.id
                ? 'border-indigo-500 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
              'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm flex items-center'
            ]"
          >
            <component
              :is="subTab.icon"
              class="w-5 h-5 mr-2"
              :class="activeSubTab === subTab.id ? 'text-indigo-500' : 'text-gray-400'"
            />
            {{ subTab.name }}
          </button>
        </nav>
      </div>
      <!-- Statuses & Categories Tab -->
      <div v-show="activeSubTab === 'basics'" class="space-y-8">
        <!-- Ticket Statuses -->
        <div class="bg-white border border-gray-200 rounded-lg p-6">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-medium text-gray-900">Ticket Statuses</h3>
          <div class="flex items-center space-x-2">
            <button
              @click="openStatusManager"
              class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              Add Status
            </button>
            <button
              @click="openWorkflowManager"
              class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
              </svg>
              Workflow Rules
            </button>
          </div>
        </div>

        <div v-if="statuses && statuses.length > 0">
          <draggable
            v-model="statusList"
            @change="handleStatusReorder"
            item-key="id"
            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
            ghost-class="opacity-50"
            chosen-class="scale-105"
          >
            <template #item="{ element: status }">
              <div
                class="group flex items-center p-3 border rounded-lg cursor-move hover:shadow-md transition-all"
                :class="status.is_default ? 'border-indigo-200 bg-indigo-50' : 'border-gray-200 bg-gray-50 hover:border-gray-300'"
                @dblclick="editStatus(status)"
              >
                <div class="flex items-center mr-2 text-gray-400">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                  </svg>
                </div>
                <div
                  class="w-3 h-3 rounded-full mr-3"
                  :style="{ backgroundColor: status.color }"
                ></div>
                <div class="flex-1">
                  <div class="text-sm font-medium text-gray-900">{{ status.name }}</div>
                  <div class="text-xs text-gray-500 space-x-2">
                    <span>{{ status.is_closed ? 'Closed' : 'Open' }}</span>
                    <span v-if="status.is_default" class="text-indigo-600">(Default)</span>
                    <span v-if="status.order_index !== null" class="text-gray-400">Order: {{ status.order_index }}</span>
                  </div>
                </div>
                <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                  <button @click.stop="editStatus(status)" class="p-1 text-gray-400 hover:text-blue-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                  </button>
                </div>
              </div>
            </template>
          </draggable>
          <p class="text-xs text-gray-500 mt-2">
            💡 Drag to reorder, double-click to edit
          </p>
        </div>

        <div v-else class="text-sm text-gray-500 text-center py-4">
          No ticket statuses configured.
        </div>
      </div>

      <!-- Ticket Categories -->
      <div class="bg-white border border-gray-200 rounded-lg p-6">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-medium text-gray-900">Ticket Categories</h3>
          <button
            @click="openCategoryManager"
            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Manage Categories
          </button>
        </div>

        <div v-if="categories && categories.length > 0">
          <draggable
            v-model="categoryList"
            @change="handleCategoryReorder"
            item-key="id"
            class="grid grid-cols-1 md:grid-cols-2 gap-4"
            ghost-class="opacity-50"
            chosen-class="scale-105"
          >
            <template #item="{ element: category }">
              <div
                class="group p-4 border rounded-lg cursor-move hover:shadow-md transition-all"
                :class="category.is_default ? 'border-indigo-200 bg-indigo-50' : 'border-gray-200 bg-gray-50 hover:border-gray-300'"
                @dblclick="editCategory(category)"
              >
                <div class="flex items-center mb-2">
                  <div class="flex items-center mr-2 text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                    </svg>
                  </div>
                  <div
                    class="w-3 h-3 rounded-full mr-3"
                    :style="{ backgroundColor: category.color }"
                  ></div>
                  <h4 class="text-sm font-medium text-gray-900 flex-1">{{ category.name }}</h4>
                  <span v-if="category.is_default" class="ml-2 text-xs text-indigo-600">(Default)</span>
                  <button @click.stop="editCategory(category)" class="opacity-0 group-hover:opacity-100 transition-opacity p-1 text-gray-400 hover:text-blue-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                  </button>
                </div>

                <div class="text-xs text-gray-600 space-y-1">
                  <div v-if="category.sla_hours">SLA: {{ category.sla_hours }} hours</div>
                  <div v-if="category.default_estimated_hours">Est: {{ category.default_estimated_hours }}h</div>
                  <div v-if="category.requires_approval" class="text-yellow-600">Requires Approval</div>
                </div>
              </div>
            </template>
          </draggable>
          <p class="text-xs text-gray-500 mt-2">
            💡 Drag to reorder, double-click to edit
          </p>
        </div>

        <div v-else class="text-sm text-gray-500 text-center py-4">
          No ticket categories configured.
        </div>
      </div>
      </div>

      <!-- Priorities Tab -->
      <div v-show="activeSubTab === 'priorities'" class="space-y-8">
        <!-- Move Ticket Priorities section here -->
        <div class="bg-white border border-gray-200 rounded-lg p-6">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-medium text-gray-900">Ticket Priorities</h3>
            <button
              @click="openPriorityManager"
              class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              Manage Priorities
            </button>
          </div>

          <div v-if="priorities && priorities.length > 0">
            <draggable
              v-model="priorityList"
              @change="handlePriorityReorder"
              item-key="id"
              class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
              ghost-class="opacity-50"
              chosen-class="scale-105"
            >
              <template #item="{ element: priority }">
                <div
                  class="group flex items-center p-3 border rounded-lg cursor-move hover:shadow-md transition-all"
                  :class="priority.is_default ? 'border-indigo-200 bg-indigo-50' : 'border-gray-200 bg-gray-50 hover:border-gray-300'"
                  @dblclick="editPriority(priority)"
                >
                  <div class="flex items-center mr-2 text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                    </svg>
                  </div>
                  <div
                    class="w-3 h-3 rounded-full mr-3"
                    :style="{ backgroundColor: priority.color }"
                  ></div>
                  <div class="flex-1">
                    <div class="flex items-center">
                      <div class="text-sm font-medium text-gray-900">{{ priority.name }}</div>
                      <span v-if="priority.is_default" class="ml-2 text-xs text-indigo-600">(Default)</span>
                    </div>
                    <div class="text-xs text-gray-500 space-x-2">
                      <span>Level {{ priority.severity_level }}</span>
                      <span v-if="priority.escalation_multiplier !== 1.00">
                        SLA: {{ priority.escalation_multiplier }}x
                      </span>
                    </div>
                  </div>
                  <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                    <button @click.stop="editPriority(priority)" class="p-1 text-gray-400 hover:text-blue-600">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                      </svg>
                    </button>
                  </div>
                </div>
              </template>
            </draggable>
            <p class="text-xs text-gray-500 mt-2">
              💡 Drag to reorder, double-click to edit
            </p>
          </div>

          <div v-else class="text-sm text-gray-500 text-center py-4">
            No ticket priorities configured.
          </div>
        </div>
      </div>

      <!-- Workflow Tab -->
      <div v-show="activeSubTab === 'workflow'" class="space-y-8">
        <!-- Workflow Configuration -->
        <div v-if="workflowTransitions" class="bg-white border border-gray-200 rounded-lg p-6">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-medium text-gray-900">Workflow Transitions</h3>
            <button
              @click="openWorkflowEditor"
              class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
              </svg>
              Edit Workflow
            </button>
          </div>

          <!-- Workflow Visualization -->
          <div class="space-y-4">
            <div
              v-for="(transitions, fromStatus) in workflowTransitions"
              :key="fromStatus"
              class="group p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer"
              @click="editTransitions(fromStatus, transitions)"
            >
              <div class="flex items-center justify-between mb-3">
                <div class="flex items-center">
                  <div
                    class="w-3 h-3 rounded-full mr-3"
                    :style="{ backgroundColor: getStatusColor(fromStatus) }"
                  ></div>
                  <div class="text-sm font-medium text-gray-900">
                    {{ formatStatusName(fromStatus) }}
                  </div>
                </div>
                <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                  <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                  </svg>
                </div>
              </div>

              <div v-if="transitions.length > 0" class="flex items-center space-x-2">
                <span class="text-xs text-gray-500">Can transition to:</span>
                <div class="flex flex-wrap gap-2">
                  <span
                    v-for="toStatus in transitions"
                    :key="toStatus"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                    :style="{
                      backgroundColor: getStatusColor(toStatus) + '20',
                      color: getStatusColor(toStatus)
                    }"
                  >
                    <div
                      class="w-2 h-2 rounded-full mr-1.5"
                      :style="{ backgroundColor: getStatusColor(toStatus) }"
                    ></div>
                    {{ formatStatusName(toStatus) }}
                  </span>
                </div>
              </div>
              <div v-else class="text-xs text-gray-500 italic">
                No transitions allowed (final state)
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Attachments Tab -->
      <div v-show="activeSubTab === 'attachments'" class="space-y-8">
        <!-- Attachment Settings -->
      <div class="bg-white border border-gray-200 rounded-lg p-6">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h3 class="text-lg font-medium text-gray-900">Message Attachment Settings</h3>
            <p class="text-sm text-gray-500 mt-1">Configure file upload limits and allowed file types for ticket messages.</p>
          </div>
          <button
            @click="saveAttachmentSettings"
            :disabled="savingAttachments"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <svg v-if="savingAttachments" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ savingAttachments ? 'Saving...' : 'Save Settings' }}
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- File Limits -->
          <div class="space-y-4">
            <h4 class="text-sm font-medium text-gray-900">Upload Limits</h4>

            <div>
              <label class="block text-sm text-gray-700 mb-2">Maximum Files Per Message</label>
              <input
                v-model.number="attachmentSettings.max_files"
                type="number"
                min="1"
                max="20"
                class="block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
              />
              <p class="text-xs text-gray-500 mt-1">Users can upload up to this many files in a single message (1-20)</p>
            </div>

            <div>
              <label class="block text-sm text-gray-700 mb-2">Maximum File Size</label>
              <div class="flex items-center space-x-2">
                <input
                  v-model.number="attachmentSettings.max_file_size_mb"
                  type="number"
                  min="1"
                  max="100"
                  class="block w-20 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                />
                <span class="text-sm text-gray-500">MB per file</span>
              </div>
              <p class="text-xs text-gray-500 mt-1">Maximum size for individual files (1-100 MB)</p>
            </div>

            <div>
              <label class="block text-sm text-gray-700 mb-2">Total Upload Limit</label>
              <div class="flex items-center space-x-2">
                <input
                  v-model.number="attachmentSettings.total_size_limit_mb"
                  type="number"
                  min="1"
                  max="500"
                  class="block w-20 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                />
                <span class="text-sm text-gray-500">MB total per message</span>
              </div>
              <p class="text-xs text-gray-500 mt-1">Maximum combined size for all files in one message</p>
            </div>
          </div>

          <!-- File Types -->
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <h4 class="text-sm font-medium text-gray-900">Allowed File Types</h4>
              <button
                @click="resetToDefaults"
                class="text-xs text-indigo-600 hover:text-indigo-700 font-medium"
              >
                Reset to Defaults
              </button>
            </div>

            <div class="space-y-3">
              <div v-for="category in fileTypeCategories" :key="category.name" class="border border-gray-200 rounded-lg p-3">
                <div class="flex items-center justify-between mb-2">
                  <label class="flex items-center text-sm font-medium text-gray-700">
                    <input
                      type="checkbox"
                      :checked="category.extensions.every(ext => attachmentSettings.allowed_extensions.includes(ext))"
                      :indeterminate="category.extensions.some(ext => attachmentSettings.allowed_extensions.includes(ext)) && !category.extensions.every(ext => attachmentSettings.allowed_extensions.includes(ext))"
                      @change="toggleCategory(category)"
                      class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 mr-2"
                    />
                    {{ category.name }}
                  </label>
                  <span class="text-xs text-gray-500">{{ category.extensions.length }} types</span>
                </div>
                <div class="flex flex-wrap gap-1">
                  <span
                    v-for="ext in category.extensions"
                    :key="ext"
                    :class="[
                      'inline-flex items-center px-2 py-1 rounded text-xs font-medium',
                      attachmentSettings.allowed_extensions.includes(ext)
                        ? 'bg-green-100 text-green-800'
                        : 'bg-gray-100 text-gray-600'
                    ]"
                  >
                    .{{ ext }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Custom Extensions -->
            <div class="border-t pt-4">
              <h5 class="text-sm font-medium text-gray-700 mb-2">Custom Extensions</h5>
              <div class="flex items-center space-x-2">
                <input
                  v-model="newExtension"
                  @keyup.enter="addCustomExtension"
                  type="text"
                  placeholder="e.g., dwg"
                  class="block flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                />
                <button
                  @click="addCustomExtension"
                  :disabled="!newExtension.trim()"
                  class="px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                >
                  Add
                </button>
              </div>

              <!-- Custom Extensions List -->
              <div v-if="customExtensions.length > 0" class="mt-2 flex flex-wrap gap-1">
                <span
                  v-for="ext in customExtensions"
                  :key="ext"
                  class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800"
                >
                  .{{ ext }}
                  <button
                    @click="removeCustomExtension(ext)"
                    class="ml-1 text-blue-600 hover:text-blue-800"
                  >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Preview Settings -->
        <div class="mt-6 pt-6 border-t border-gray-200">
          <div class="bg-gray-50 rounded-lg p-4">
            <h5 class="text-sm font-medium text-gray-900 mb-2">Current Settings Summary</h5>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
              <div>
                <span class="text-gray-600">Max files:</span>
                <span class="font-medium text-gray-900 ml-1">{{ attachmentSettings.max_files }}</span>
              </div>
              <div>
                <span class="text-gray-600">Max size:</span>
                <span class="font-medium text-gray-900 ml-1">{{ attachmentSettings.max_file_size_mb }}MB</span>
              </div>
              <div>
                <span class="text-gray-600">Total limit:</span>
                <span class="font-medium text-gray-900 ml-1">{{ attachmentSettings.total_size_limit_mb }}MB</span>
              </div>
            </div>
            <div class="mt-2">
              <span class="text-gray-600">Allowed types:</span>
              <span class="font-medium text-gray-900 ml-1">{{ attachmentSettings.allowed_extensions.length }} extensions</span>
            </div>
          </div>
        </div>
      </div>
      </div>

      <!-- Refresh Button -->
      <div class="flex justify-end mt-8">
        <button
          type="button"
          @click="$emit('refresh')"
          class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
          </svg>
          Refresh Configuration
        </button>
      </div>

      <!-- Modals -->
    <StatusManagerModal
      :show="showStatusManager"
      :editing-status="editingStatus"
      @close="showStatusManager = false"
      @saved="handleStatusSaved"
    />

    <CategoryManagerModal
      :show="showCategoryManager"
      :editing-category="editingCategory"
      @close="showCategoryManager = false"
      @saved="handleCategorySaved"
    />

    <PriorityManagerModal
      :show="showPriorityManager"
      :editing-priority="editingPriority"
      @close="showPriorityManager = false"
      @saved="handlePrioritySaved"
    />

    <WorkflowEditorModal
      :show="showWorkflowEditor"
      :statuses="statuses"
      :initial-transitions="workflowTransitions"
      @close="showWorkflowEditor = false"
      @saved="handleWorkflowSaved"
    />
    </template>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import draggable from 'vuedraggable'
import StatusManagerModal from '@/Components/Modals/StatusManagerModal.vue'
import CategoryManagerModal from '@/Components/Modals/CategoryManagerModal.vue'
import PriorityManagerModal from '@/Components/Modals/PriorityManagerModal.vue'
import WorkflowEditorModal from '@/Components/Modals/WorkflowEditorModal.vue'
import {
  TagIcon,
  ExclamationTriangleIcon,
  ArrowPathIcon,
  PaperClipIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  config: {
    type: Object,
    default: () => ({})
  },
  loading: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['refresh'])

// State for modals/dialogs
const showStatusManager = ref(false)
const showCategoryManager = ref(false)
const showPriorityManager = ref(false)
const showWorkflowEditor = ref(false)
const editingStatus = ref(null)
const editingCategory = ref(null)
const editingPriority = ref(null)
const editingTransitions = ref(null)

// Sub-tab navigation state
const activeSubTab = ref('basics')

// Attachment settings state
const savingAttachments = ref(false)
const newExtension = ref('')
const attachmentSettings = ref({
  max_files: 10,
  max_file_size_mb: 10,
  total_size_limit_mb: 50,
  allowed_extensions: []
})

// Sub-tabs definition
const subTabs = ref([
  {
    id: 'basics',
    name: 'Statuses & Categories',
    icon: TagIcon
  },
  {
    id: 'priorities',
    name: 'Priorities',
    icon: ExclamationTriangleIcon
  },
  {
    id: 'workflow',
    name: 'Workflow',
    icon: ArrowPathIcon
  },
  {
    id: 'attachments',
    name: 'Attachments',
    icon: PaperClipIcon
  }
])

// File type categories
const fileTypeCategories = ref([
  {
    name: 'Documents',
    extensions: ['pdf', 'doc', 'docx', 'txt', 'rtf', 'odt']
  },
  {
    name: 'Spreadsheets',
    extensions: ['xls', 'xlsx', 'csv', 'ods']
  },
  {
    name: 'Presentations',
    extensions: ['ppt', 'pptx', 'odp']
  },
  {
    name: 'Images',
    extensions: ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp']
  },
  {
    name: 'Videos',
    extensions: ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv']
  },
  {
    name: 'Audio',
    extensions: ['mp3', 'wav', 'flac', 'aac', 'ogg', 'm4a']
  },
  {
    name: 'Archives',
    extensions: ['zip', 'rar', '7z', 'tar', 'gz']
  },
  {
    name: 'Code',
    extensions: ['js', 'ts', 'php', 'py', 'java', 'cpp', 'css', 'html', 'json', 'xml']
  }
])

// Default allowed extensions
const defaultExtensions = [
  'pdf', 'doc', 'docx', 'txt', 'xls', 'xlsx', 'csv',
  'jpg', 'jpeg', 'png', 'gif', 'zip', 'rar'
]

const statuses = computed(() => props.config.statuses || [])
const categories = computed(() => props.config.categories || [])
const priorities = computed(() => props.config.priorities || [])
const workflowTransitions = computed(() => props.config.workflow_transitions || {})

// Computed for custom extensions (not in predefined categories)
const customExtensions = computed(() => {
  const predefinedExtensions = fileTypeCategories.value.flatMap(cat => cat.extensions)
  return attachmentSettings.value.allowed_extensions.filter(ext => !predefinedExtensions.includes(ext))
})

// Reactive lists for drag-drop
const statusList = ref([])
const categoryList = ref([])
const priorityList = ref([])

// Watch for changes in props and update reactive lists
watch(() => props.config.statuses, (newStatuses) => {
  statusList.value = [...(newStatuses || [])]
}, { immediate: true, deep: true })

watch(() => props.config.categories, (newCategories) => {
  categoryList.value = [...(newCategories || [])]
}, { immediate: true, deep: true })

watch(() => props.config.priorities, (newPriorities) => {
  priorityList.value = [...(newPriorities || [])]
}, { immediate: true, deep: true })

// Watch for attachment settings changes
watch(() => props.config.attachment_settings, (newSettings) => {
  if (newSettings) {
    attachmentSettings.value = {
      max_files: newSettings.max_files || 10,
      max_file_size_mb: Math.ceil((newSettings.max_file_size_kb || 10240) / 1024),
      total_size_limit_mb: Math.ceil((newSettings.total_size_limit_kb || 51200) / 1024),
      allowed_extensions: newSettings.allowed_extensions || [...defaultExtensions]
    }
  } else {
    // Set defaults if no settings exist
    attachmentSettings.value.allowed_extensions = [...defaultExtensions]
  }
}, { immediate: true, deep: true })

// Helper methods
const formatStatusName = (status) => {
  const statusObj = statuses.value.find(s => s.key === status)
  return statusObj?.name || status.replace('_', ' ')
}

const getStatusColor = (status) => {
  const statusObj = statuses.value.find(s => s.key === status)
  return statusObj?.color || '#6B7280'
}

// Event handlers
const openStatusManager = () => {
  showStatusManager.value = true
  editingStatus.value = null
}

const openCategoryManager = () => {
  showCategoryManager.value = true
  editingCategory.value = null
}

const openPriorityManager = () => {
  showPriorityManager.value = true
  editingPriority.value = null
}

const openWorkflowEditor = () => {
  showWorkflowEditor.value = true
}

const editStatus = (status) => {
  editingStatus.value = status
  showStatusManager.value = true
}

const editCategory = (category) => {
  editingCategory.value = category
  showCategoryManager.value = true
}

const editPriority = (priority) => {
  editingPriority.value = priority
  showPriorityManager.value = true
}

const editTransitions = (fromStatus, transitions) => {
  editingTransitions.value = { fromStatus, transitions }
  showWorkflowEditor.value = true
}

// Drag-drop reorder handlers
const handleStatusReorder = async (event) => {
  if (event.moved) {
    const reorderedItems = statusList.value.map((item, index) => ({
      id: item.id,
      sort_order: index
    }))

    // Store original order for rollback
    const originalOrder = [...statuses.value]

    try {
      const response = await fetch('/api/ticket-statuses/reorder', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ statuses: reorderedItems })
      })

      if (!response.ok) {
        throw new Error('Server returned error')
      }

      // Update succeeded - no need for full page refresh
      // The optimistic update is already applied via draggable
    } catch (error) {
      console.error('Failed to reorder statuses:', error)
      // Revert on error by restoring original order
      statusList.value = [...originalOrder]
    }
  }
}

const handleCategoryReorder = async (event) => {
  if (event.moved) {
    const reorderedItems = categoryList.value.map((item, index) => ({
      id: item.id,
      sort_order: index
    }))

    // Store original order for rollback
    const originalOrder = [...categories.value]

    try {
      const response = await fetch('/api/ticket-categories/reorder', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ categories: reorderedItems })
      })

      if (!response.ok) {
        throw new Error('Server returned error')
      }

      // Update succeeded - no need for full page refresh
      // The optimistic update is already applied via draggable
    } catch (error) {
      console.error('Failed to reorder categories:', error)
      // Revert on error by restoring original order
      categoryList.value = [...originalOrder]
    }
  }
}

const handlePriorityReorder = async (event) => {
  if (event.moved) {
    const reorderedItems = priorityList.value.map((item, index) => ({
      id: item.id,
      sort_order: index
    }))

    // Store original order for rollback
    const originalOrder = [...priorities.value]

    try {
      const response = await fetch('/api/ticket-priorities/reorder', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ priorities: reorderedItems })
      })

      if (!response.ok) {
        throw new Error('Server returned error')
      }

      // Update succeeded - no need for full page refresh
      // The optimistic update is already applied via draggable
    } catch (error) {
      console.error('Failed to reorder priorities:', error)
      // Revert on error by restoring original order
      priorityList.value = [...originalOrder]
    }
  }
}

// Modal save handlers
const handleStatusSaved = (savedStatus) => {
  emit('refresh')
}

const handleCategorySaved = (savedCategory) => {
  emit('refresh')
}

const handlePrioritySaved = (savedPriority) => {
  emit('refresh')
}

const handleWorkflowSaved = (savedWorkflow) => {
  emit('refresh')
}

// API calls for status management
const createStatus = async (statusData) => {
  try {
    const response = await fetch('/api/ticket-statuses', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify(statusData)
    })

    if (response.ok) {
      emit('refresh')
      showStatusManager.value = false
    }
  } catch (error) {
    console.error('Failed to create status:', error)
  }
}

const updateStatus = async (statusId, statusData) => {
  try {
    const response = await fetch(`/api/ticket-statuses/${statusId}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify(statusData)
    })

    if (response.ok) {
      emit('refresh')
      showStatusManager.value = false
    }
  } catch (error) {
    console.error('Failed to update status:', error)
  }
}

const updateWorkflowTransitions = async (transitionsData) => {
  try {
    const response = await fetch('/api/settings/workflow-transitions', {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify(transitionsData)
    })

    if (response.ok) {
      emit('refresh')
      showWorkflowEditor.value = false
    }
  } catch (error) {
    console.error('Failed to update workflow transitions:', error)
  }
}

// Attachment settings methods
const saveAttachmentSettings = async () => {
  savingAttachments.value = true

  try {
    const settingsData = {
      'tickets.attachments.max_files': attachmentSettings.value.max_files,
      'tickets.attachments.max_file_size_kb': attachmentSettings.value.max_file_size_mb * 1024,
      'tickets.attachments.total_size_limit_kb': attachmentSettings.value.total_size_limit_mb * 1024,
      'tickets.attachments.allowed_extensions': attachmentSettings.value.allowed_extensions
    }

    const response = await fetch('/api/settings/bulk-update', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({ settings: settingsData })
    })

    if (response.ok) {
      // Show success notification
      console.log('Attachment settings saved successfully')
      emit('refresh')
    } else {
      throw new Error('Failed to save settings')
    }
  } catch (error) {
    console.error('Failed to save attachment settings:', error)
  } finally {
    savingAttachments.value = false
  }
}

const toggleCategory = (category) => {
  const allIncluded = category.extensions.every(ext => attachmentSettings.value.allowed_extensions.includes(ext))

  if (allIncluded) {
    // Remove all extensions from this category
    attachmentSettings.value.allowed_extensions = attachmentSettings.value.allowed_extensions.filter(
      ext => !category.extensions.includes(ext)
    )
  } else {
    // Add all missing extensions from this category
    const newExtensions = category.extensions.filter(
      ext => !attachmentSettings.value.allowed_extensions.includes(ext)
    )
    attachmentSettings.value.allowed_extensions.push(...newExtensions)
  }
}

const addCustomExtension = () => {
  const extension = newExtension.value.trim().toLowerCase().replace(/^\./, '') // Remove leading dot if present

  if (extension && !attachmentSettings.value.allowed_extensions.includes(extension)) {
    attachmentSettings.value.allowed_extensions.push(extension)
    newExtension.value = ''
  }
}

const removeCustomExtension = (extension) => {
  const index = attachmentSettings.value.allowed_extensions.indexOf(extension)
  if (index > -1) {
    attachmentSettings.value.allowed_extensions.splice(index, 1)
  }
}

const resetToDefaults = () => {
  attachmentSettings.value.max_files = 10
  attachmentSettings.value.max_file_size_mb = 10
  attachmentSettings.value.total_size_limit_mb = 50
  attachmentSettings.value.allowed_extensions = [...defaultExtensions]
}
</script>
