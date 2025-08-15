<template>
  <!-- Permissions Debug Overlay - Super Admin Only -->
  <div 
    v-if="showPermissionsDebugOverlay && user?.is_super_admin"
    ref="permissionsDebugOverlay"
    :style="{ 
      left: debugPosition.x + 'px', 
      top: debugPosition.y + 'px',
      width: debugMinimized ? '240px' : '400px'
    }"
    class="fixed z-50 bg-gray-900 text-white rounded-lg shadow-2xl border border-gray-700 select-none"
    @mousedown="startDrag"
  >
    <!-- Debug Header -->
    <div class="bg-gray-800 px-3 py-2 rounded-t-lg flex items-center justify-between cursor-move">
      <div class="flex items-center space-x-2">
        <svg class="w-4 h-4 text-purple-400" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd" />
        </svg>
        <span class="text-sm font-medium">Permissions Debug</span>
      </div>
      <div class="flex items-center space-x-1">
        <button 
          @click.stop="debugMinimized = !debugMinimized"
          class="p-1 hover:bg-gray-700 rounded text-gray-400 hover:text-white transition-colors"
          :title="debugMinimized ? 'Expand' : 'Minimize'"
        >
          <svg v-if="debugMinimized" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
          </svg>
          <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
          </svg>
        </button>
        <button 
          @click.stop="setShowPermissionsDebugOverlay(false)"
          class="p-1 hover:bg-gray-700 rounded text-gray-400 hover:text-white transition-colors"
          title="Close (can re-enable in Settings > Advanced)"
        >
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>
    
    <!-- Debug Content -->
    <div v-if="!debugMinimized" class="p-3 text-xs space-y-3 max-h-96 overflow-auto">
      <!-- User Overview -->
      <div class="border-b border-gray-700 pb-2">
        <div class="text-blue-400 font-mono mb-1">User Overview</div>
        <div class="grid grid-cols-2 gap-1 text-xs">
          <div>ID:</div>
          <div class="font-mono">{{ user?.id || 'null' }}</div>
          <div>Name:</div>
          <div class="font-mono">{{ user?.name || 'null' }}</div>
          <div>Email:</div>
          <div class="font-mono">{{ user?.email || 'null' }}</div>
          <div>Type:</div>
          <div class="font-mono">{{ user?.user_type || 'null' }}</div>
          <div>Super Admin:</div>
          <div class="font-mono">{{ user?.is_super_admin || false }}</div>
        </div>
      </div>

      <!-- Account Information -->
      <div class="border-b border-gray-700 pb-2">
        <div class="text-green-400 font-mono mb-1">Account Info</div>
        <div class="grid grid-cols-2 gap-1 text-xs">
          <div>Account ID:</div>
          <div class="font-mono">{{ user?.account_id || 'null' }}</div>
          <div>Account Name:</div>
          <div class="font-mono">{{ user?.account?.name || 'null' }}</div>
          <div>Account Type:</div>
          <div class="font-mono">{{ user?.account?.account_type || 'null' }}</div>
        </div>
      </div>

      <!-- Role Template -->
      <div class="border-b border-gray-700 pb-2">
        <div class="text-yellow-400 font-mono mb-1">Role Template</div>
        <div class="grid grid-cols-2 gap-1 text-xs">
          <div>Template ID:</div>
          <div class="font-mono">{{ user?.role_template_id || 'null' }}</div>
          <div>Template Name:</div>
          <div class="font-mono">{{ user?.role_template?.name || 'null' }}</div>
          <div>Context:</div>
          <div class="font-mono">{{ user?.role_template?.context || 'null' }}</div>
          <div>System Role:</div>
          <div class="font-mono">{{ user?.role_template?.is_system_role || false }}</div>
        </div>
      </div>

      <!-- Permissions Summary -->
      <div class="border-b border-gray-700 pb-2">
        <div class="text-purple-400 font-mono mb-1">Permissions Summary</div>
        <div class="grid grid-cols-2 gap-1 text-xs">
          <div>Has Array:</div>
          <div class="font-mono">{{ Array.isArray(user?.permissions) }}</div>
          <div>Total Count:</div>
          <div class="font-mono">{{ user?.permissions?.length || 0 }}</div>
          <div>Admin Perms:</div>
          <div class="font-mono">{{ adminPermissionCount }}</div>
          <div>Timer Perms:</div>
          <div class="font-mono">{{ timerPermissionCount }}</div>
          <div>Time Perms:</div>
          <div class="font-mono">{{ timePermissionCount }}</div>
          <div>Billing Perms:</div>
          <div class="font-mono">{{ billingPermissionCount }}</div>
        </div>
      </div>

      <!-- Permission Categories -->
      <div v-if="user?.permissions?.length" class="space-y-2">
        <div class="text-orange-400 font-mono mb-1">Permission Categories</div>
        
        <!-- Timer Permissions -->
        <div v-if="timerPermissions.length" class="bg-gray-800 p-2 rounded text-xs">
          <button 
            @click="toggleSection('timers')"
            class="w-full text-left flex items-center justify-between text-cyan-400 font-mono mb-1 hover:text-cyan-300"
          >
            <span>Timers ({{ timerPermissions.length }})</span>
            <svg class="w-3 h-3 transition-transform" :class="{ 'rotate-180': expandedSections.timers }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
          <div v-if="expandedSections.timers" class="grid grid-cols-2 gap-1">
            <div v-for="perm in timerPermissions" :key="perm" class="font-mono text-gray-300">{{ perm }}</div>
          </div>
          <div v-else class="text-gray-500 text-xs">Click to expand {{ timerPermissions.length }} permissions</div>
        </div>

        <!-- Time Permissions -->
        <div v-if="timePermissions.length" class="bg-gray-800 p-2 rounded text-xs">
          <button 
            @click="toggleSection('time')"
            class="w-full text-left flex items-center justify-between text-purple-400 font-mono mb-1 hover:text-purple-300"
          >
            <span>Time ({{ timePermissions.length }})</span>
            <svg class="w-3 h-3 transition-transform" :class="{ 'rotate-180': expandedSections.time }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
          <div v-if="expandedSections.time" class="grid grid-cols-2 gap-1">
            <div v-for="perm in timePermissions" :key="perm" class="font-mono text-gray-300">{{ perm }}</div>
          </div>
          <div v-else class="text-gray-500 text-xs">Click to expand {{ timePermissions.length }} permissions</div>
        </div>

        <!-- Admin Permissions -->
        <div v-if="adminPermissions.length" class="bg-gray-800 p-2 rounded text-xs">
          <button 
            @click="toggleSection('admin')"
            class="w-full text-left flex items-center justify-between text-red-400 font-mono mb-1 hover:text-red-300"
          >
            <span>Admin ({{ adminPermissions.length }})</span>
            <svg class="w-3 h-3 transition-transform" :class="{ 'rotate-180': expandedSections.admin }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
          <div v-if="expandedSections.admin" class="grid grid-cols-2 gap-1">
            <div v-for="perm in adminPermissions" :key="perm" class="font-mono text-gray-300">{{ perm }}</div>
          </div>
          <div v-else class="text-gray-500 text-xs">Click to expand {{ adminPermissions.length }} permissions</div>
        </div>

        <!-- Billing Permissions -->
        <div v-if="billingPermissions.length" class="bg-gray-800 p-2 rounded text-xs">
          <button 
            @click="toggleSection('billing')"
            class="w-full text-left flex items-center justify-between text-green-400 font-mono mb-1 hover:text-green-300"
          >
            <span>Billing ({{ billingPermissions.length }})</span>
            <svg class="w-3 h-3 transition-transform" :class="{ 'rotate-180': expandedSections.billing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
          <div v-if="expandedSections.billing" class="grid grid-cols-2 gap-1">
            <div v-for="perm in billingPermissions" :key="perm" class="font-mono text-gray-300">{{ perm }}</div>
          </div>
          <div v-else class="text-gray-500 text-xs">Click to expand {{ billingPermissions.length }} permissions</div>
        </div>

        <!-- Ticket Permissions -->
        <div v-if="ticketPermissions.length" class="bg-gray-800 p-2 rounded text-xs">
          <button 
            @click="toggleSection('tickets')"
            class="w-full text-left flex items-center justify-between text-blue-400 font-mono mb-1 hover:text-blue-300"
          >
            <span>Tickets ({{ ticketPermissions.length }})</span>
            <svg class="w-3 h-3 transition-transform" :class="{ 'rotate-180': expandedSections.tickets }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
          <div v-if="expandedSections.tickets" class="grid grid-cols-2 gap-1">
            <div v-for="perm in ticketPermissions" :key="perm" class="font-mono text-gray-300">{{ perm }}</div>
          </div>
          <div v-else class="text-gray-500 text-xs">Click to expand {{ ticketPermissions.length }} permissions</div>
        </div>

        <!-- Other Permissions -->
        <div v-if="otherPermissions.length" class="bg-gray-800 p-2 rounded text-xs">
          <button 
            @click="toggleSection('other')"
            class="w-full text-left flex items-center justify-between text-gray-400 font-mono mb-1 hover:text-gray-300"
          >
            <span>Other ({{ otherPermissions.length }})</span>
            <svg class="w-3 h-3 transition-transform" :class="{ 'rotate-180': expandedSections.other }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
          <div v-if="expandedSections.other" class="grid grid-cols-2 gap-1">
            <div v-for="perm in otherPermissions" :key="perm" class="font-mono text-gray-300">{{ perm }}</div>
          </div>
          <div v-else class="text-gray-500 text-xs">Click to expand {{ otherPermissions.length }} permissions</div>
        </div>
      </div>
      
      <div class="border-t border-gray-700 pt-2 text-gray-400">
        <div class="text-xs">💡 Drag to move • Click [-] to minimize • Settings > Advanced to disable</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onUnmounted, reactive, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useLocalStorageReactive } from '@/Composables/useLocalStorageReactive.js'

// Get user data from Inertia
const page = usePage()
const user = computed(() => page.props.auth?.user)

// Debug overlay state - use reactive localStorage
const [showPermissionsDebugOverlay, setShowPermissionsDebugOverlay] = useLocalStorageReactive('permissions_debug_overlay_enabled', false)
const debugMinimized = ref(false)

// Expandable sections state
const expandedSections = ref({
  timers: false,
  time: false,
  admin: false,
  billing: false,
  tickets: false,
  other: false
})
const debugPosition = reactive({
  x: parseInt(localStorage.getItem('permissions_debug_overlay_x')) || 350,
  y: parseInt(localStorage.getItem('permissions_debug_overlay_y')) || 16
})
const permissionsDebugOverlay = ref(null)
let isDragging = false
let dragOffset = { x: 0, y: 0 }

// Permission categorization
const timerPermissions = computed(() => {
  if (!user.value?.permissions) return []
  return user.value.permissions.filter(p => p.startsWith('timers.'))
})

const timePermissions = computed(() => {
  if (!user.value?.permissions) return []
  return user.value.permissions.filter(p => p.startsWith('time.'))
})

const adminPermissions = computed(() => {
  if (!user.value?.permissions) return []
  return user.value.permissions.filter(p => p.startsWith('admin.') || p.startsWith('system.') || p.startsWith('settings.'))
})

const billingPermissions = computed(() => {
  if (!user.value?.permissions) return []
  return user.value.permissions.filter(p => p.startsWith('billing.') || p.startsWith('invoices.') || p.startsWith('payments.'))
})

const ticketPermissions = computed(() => {
  if (!user.value?.permissions) return []
  return user.value.permissions.filter(p => p.startsWith('tickets.'))
})

const otherPermissions = computed(() => {
  if (!user.value?.permissions) return []
  return user.value.permissions.filter(p => 
    !p.startsWith('timers.') && !p.startsWith('time.') &&
    !p.startsWith('admin.') && !p.startsWith('system.') && !p.startsWith('settings.') &&
    !p.startsWith('billing.') && !p.startsWith('invoices.') && !p.startsWith('payments.') &&
    !p.startsWith('tickets.')
  )
})

// Permission counts
const timerPermissionCount = computed(() => timerPermissions.value.length)
const timePermissionCount = computed(() => timePermissions.value.length)
const adminPermissionCount = computed(() => adminPermissions.value.length)
const billingPermissionCount = computed(() => billingPermissions.value.length)

// Toggle section expansion
const toggleSection = (section) => {
  expandedSections.value[section] = !expandedSections.value[section]
}

// Debug overlay drag functionality
const startDrag = (e) => {
  if (e.target.closest('button')) return // Don't drag when clicking buttons
  
  isDragging = true
  const rect = permissionsDebugOverlay.value.getBoundingClientRect()
  dragOffset.x = e.clientX - rect.left
  dragOffset.y = e.clientY - rect.top
  
  document.addEventListener('mousemove', drag)
  document.addEventListener('mouseup', stopDrag)
  e.preventDefault()
}

const drag = (e) => {
  if (!isDragging) return
  
  const newX = e.clientX - dragOffset.x
  const newY = e.clientY - dragOffset.y
  
  // Keep overlay within viewport bounds
  const maxX = window.innerWidth - (debugMinimized.value ? 240 : 400)
  const maxY = window.innerHeight - 100
  
  debugPosition.x = Math.max(0, Math.min(newX, maxX))
  debugPosition.y = Math.max(0, Math.min(newY, maxY))
  
  // Save position to localStorage
  localStorage.setItem('permissions_debug_overlay_x', debugPosition.x)
  localStorage.setItem('permissions_debug_overlay_y', debugPosition.y)
}

const stopDrag = () => {
  isDragging = false
  document.removeEventListener('mousemove', drag)
  document.removeEventListener('mouseup', stopDrag)
}

// localStorage changes are now handled by useLocalStorageReactive

// Clean up on unmount
onUnmounted(() => {
  document.removeEventListener('mousemove', drag)
  document.removeEventListener('mouseup', stopDrag)
})
</script>