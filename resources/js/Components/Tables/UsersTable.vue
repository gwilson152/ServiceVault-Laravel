<template>
  <div class="overflow-x-auto">
    <table 
      class="min-w-full divide-y divide-gray-200" 
      :class="{
        'table-comfortable': density === 'comfortable',
        'table-compact': density === 'compact'
      }"
      style="min-width: 900px"
    >
      <thead class="bg-gray-50 border-b border-gray-300">
        <tr
          v-for="headerGroup in table.getHeaderGroups()"
          :key="headerGroup.id"
        >
          <th
            v-for="header in headerGroup.headers"
            :key="header.id"
            :colspan="header.colSpan"
            :class="[
              density === 'compact' ? 'px-3 py-2' : 'px-6 py-3',
              'text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-b border-gray-200',
              {
                'cursor-pointer hover:bg-gray-100': header.column.getCanSort(),
                'text-right': header.id === 'status_actions'
              }
            ]"
            @click="header.column.getToggleSortingHandler()?.($event)"
          >
            <div class="flex items-center" :class="{ 'justify-end': header.id === 'status_actions' }">
              <FlexRender
                :render="header.column.columnDef.header"
                :props="header.getContext()"
              />
              <span v-if="header.column.getCanSort()" class="ml-2">
                <svg v-if="header.column.getIsSorted() === 'asc'" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M7 10l5-5 5 5H7z"/>
                </svg>
                <svg v-else-if="header.column.getIsSorted() === 'desc'" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M7 10l5 5 5-5H7z"/>
                </svg>
                <svg v-else class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"/>
                </svg>
              </span>
            </div>
          </th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-100">
        <tr
          v-for="row in table.getRowModel().rows"
          :key="row.id"
          class="hover:bg-blue-50 transition-all duration-150 cursor-pointer border-l-2 border-transparent hover:border-blue-300 hover:shadow-sm"
          @click="navigateToUser(row.original)"
        >
          <td
            v-for="cell in row.getVisibleCells()"
            :key="cell.id"
            :class="[
              density === 'compact' ? 'px-3 py-2' : 'px-6 py-4',
              'whitespace-nowrap border-b border-gray-100',
              {
                'text-right': cell.column.id === 'status_actions',
              }
            ]"
          >
            <!-- User Details Column -->
            <div v-if="cell.column.id === 'name'">
              <!-- Avatar, Name and Email -->
              <div class="flex items-center mb-2">
                <div class="flex-shrink-0 h-8 w-8 mr-3">
                  <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center">
                    <span class="text-indigo-600 font-medium text-sm">
                      {{ cell.row.original.name.charAt(0).toUpperCase() }}
                    </span>
                  </div>
                </div>
                <div class="min-w-0 flex-1">
                  <div class="text-sm font-semibold text-gray-900">{{ cell.row.original.name }}</div>
                  <div class="text-xs text-gray-500 truncate">{{ cell.row.original.email || 'No email' }}</div>
                </div>
              </div>
              
              <!-- Status Badges Row -->
              <div class="flex items-center gap-2">
                <span 
                  v-if="cell.row.original.is_active"
                  class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800"
                >
                  Active
                </span>
                <span 
                  v-else
                  class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800"
                >
                  Inactive
                </span>
                <span 
                  v-if="cell.row.original.role_template?.is_super_admin"
                  class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800"
                >
                  Super Admin
                </span>
              </div>
            </div>

            <!-- Account & Role Column -->
            <div v-else-if="cell.column.id === 'account_role'">
              <!-- Account Information -->
              <div class="mb-3">
                <div v-if="cell.row.original.account" class="space-y-1">
                  <div class="flex items-center justify-between">
                    <div class="min-w-0 flex-1">
                      <div class="text-sm font-medium text-gray-900 truncate">
                        {{ cell.row.original.account.display_name || cell.row.original.account.name }}
                      </div>
                      <div class="flex items-center space-x-2 mt-0.5">
                        <span class="text-xs text-gray-500">{{ formatAccountType(cell.row.original.account.account_type) }}</span>
                        <span v-if="cell.row.original.account.company_name" class="text-xs text-gray-400">
                          • {{ cell.row.original.account.company_name }}
                        </span>
                      </div>
                    </div>
                    <div class="flex flex-col items-end space-y-1 ml-2">
                      <span v-if="!cell.row.original.account.is_active" class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">
                        Inactive
                      </span>
                      <span v-if="cell.row.original.account.hierarchy_level" class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                        L{{ cell.row.original.account.hierarchy_level }}
                      </span>
                    </div>
                  </div>
                </div>
                <div v-else class="text-sm text-gray-400 italic">
                  No Account Assigned
                </div>
              </div>
              
              <!-- Role Information -->
              <div>
                <div v-if="cell.row.original.role_template" class="space-y-1">
                  <div class="flex items-center justify-between">
                    <div class="min-w-0 flex-1">
                      <div class="text-sm font-medium text-gray-900 truncate">{{ cell.row.original.role_template.name }}</div>
                      <div class="flex items-center space-x-2 mt-0.5">
                        <span class="text-xs text-gray-500">{{ formatContext(cell.row.original.role_template.context) }}</span>
                        <span v-if="cell.row.original.role_template.description" class="text-xs text-gray-400">
                          • {{ cell.row.original.role_template.description }}
                        </span>
                      </div>
                    </div>
                    <div class="flex flex-col items-end space-y-1 ml-2">
                      <span v-if="cell.row.original.role_template.is_system_role" class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-600">
                        System
                      </span>
                      <span v-if="cell.row.original.role_template.is_super_admin" class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700">
                        Super Admin
                      </span>
                      <span v-if="!cell.row.original.role_template.is_modifiable" class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                        Protected
                      </span>
                    </div>
                  </div>
                  <!-- Permissions Summary -->
                  <div v-if="cell.row.original.permissions && cell.row.original.permissions.length > 0" class="mt-1">
                    <span class="text-xs text-gray-400">{{ cell.row.original.permissions.length }} permissions</span>
                  </div>
                </div>
                <div v-else class="text-sm text-gray-400 italic">
                  No Role Assigned
                </div>
              </div>
            </div>

            <!-- Activity Column -->
            <div v-else-if="cell.column.id === 'activity'">
              <div class="space-y-1">
                <!-- Tickets Activity -->
                <div v-if="cell.row.original.statistics?.total_assigned_tickets > 0" class="text-sm text-gray-900">
                  {{ cell.row.original.statistics.total_assigned_tickets }} tickets
                </div>
                
                <!-- Time Entries -->
                <div v-if="cell.row.original.statistics?.total_time_entries > 0" class="text-xs text-gray-500">
                  {{ cell.row.original.statistics.total_time_entries }} time entries
                </div>
                
                <!-- Active Timers -->
                <div v-if="cell.row.original.statistics?.active_timers_count > 0" class="text-xs text-green-600 font-medium">
                  {{ cell.row.original.statistics.active_timers_count }} active timer(s)
                </div>
                
                <!-- Last Activity -->
                <div v-if="cell.row.original.last_active_at" class="text-xs text-gray-400">
                  Last seen {{ formatRelativeDate(cell.row.original.last_active_at) }}
                </div>
                
                <!-- No Activity State -->
                <div v-if="!hasAnyActivity(cell.row.original)" class="text-xs text-gray-400 italic">
                  No recent activity
                </div>
              </div>
            </div>

            <!-- Status & Actions Column -->
            <div v-else-if="cell.column.id === 'status_actions'">
              <div class="flex items-center justify-end space-x-2">
                <!-- Status Toggle -->
                <button 
                  @click.stop="$emit('toggle-status', cell.row.original)"
                  :class="[
                    'text-xs font-medium px-2 py-1 rounded',
                    cell.row.original.is_active 
                      ? 'text-orange-700 hover:text-orange-900 bg-orange-50 hover:bg-orange-100' 
                      : 'text-green-700 hover:text-green-900 bg-green-50 hover:bg-green-100'
                  ]"
                >
                  {{ cell.row.original.is_active ? 'Deactivate' : 'Activate' }}
                </button>
                
                <!-- Edit Button -->
                <button 
                  @click.stop="$emit('edit-user', cell.row.original)"
                  class="text-xs font-medium text-indigo-600 hover:text-indigo-900 px-2 py-1 rounded hover:bg-indigo-50"
                >
                  Edit
                </button>
                
                <!-- View Profile -->
                <a 
                  :href="`/users/${cell.row.original.id}`"
                  @click.stop
                  class="text-xs font-medium text-gray-600 hover:text-gray-900 px-2 py-1 rounded hover:bg-gray-50"
                >
                  View
                </a>
                
                <!-- Delete Button -->
                <button 
                  @click.stop="$emit('delete-user', cell.row.original)"
                  class="text-xs font-medium text-red-600 hover:text-red-900 px-2 py-1 rounded hover:bg-red-50"
                >
                  Delete
                </button>
              </div>
            </div>

            <!-- Default cell rendering for other columns -->
            <div v-else>
              <FlexRender
                :render="cell.column.columnDef.cell"
                :props="cell.getContext()"
              />
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { FlexRender } from '@tanstack/vue-table'
import { router } from '@inertiajs/vue3'

defineProps({
  table: {
    type: Object,
    required: true
  },
  density: {
    type: String,
    default: 'compact',
    validator: value => ['compact', 'comfortable'].includes(value)
  }
})

defineEmits(['toggle-status', 'edit-user', 'delete-user'])

// Navigation function
const navigateToUser = (user) => {
  router.visit(`/users/${user.id}`)
}

// Helper functions
const formatAccountType = (type) => {
  const types = {
    'customer': 'Customer',
    'prospect': 'Prospect', 
    'partner': 'Partner',
    'internal': 'Internal',
    'individual': 'Individual'
  }
  return types[type] || type
}

const formatContext = (context) => {
  const contexts = {
    'service_provider': 'Service Provider',
    'account_user': 'Account User'
  }
  return contexts[context] || context
}

const formatRelativeDate = (dateString) => {
  if (!dateString) return ''
  
  const date = new Date(dateString)
  const now = new Date()
  const diffDays = Math.floor((now - date) / (1000 * 60 * 60 * 24))
  
  if (diffDays === 0) return 'today'
  if (diffDays === 1) return 'yesterday'
  if (diffDays < 7) return `${diffDays} days ago`
  if (diffDays < 30) return `${Math.floor(diffDays / 7)} weeks ago`
  
  return date.toLocaleDateString()
}

const hasAnyActivity = (user) => {
  return (
    (user.statistics?.total_assigned_tickets > 0) ||
    (user.statistics?.total_time_entries > 0) ||
    (user.statistics?.active_timers_count > 0) ||
    user.last_active_at
  )
}
</script>

<style scoped>
/* Density-based styling */
.table-compact {
  --table-padding-x: 0.75rem;
  --table-padding-y: 0.5rem;
  --table-font-size: 0.875rem;
}

.table-comfortable {
  --table-padding-x: 1.5rem;
  --table-padding-y: 1rem;  
  --table-font-size: 0.875rem;
}

/* Custom table styling matching TicketsTable */
table {
  border-collapse: separate;
  border-spacing: 0;
}

/* Row hover effects */
tbody tr:hover {
  background-color: rgb(239 246 255 / 1);
}

/* Smooth transitions */
tbody tr {
  transition: all 0.15s ease;
}

/* Text truncation for long content */
.truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* Line clamping for descriptions */
.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>