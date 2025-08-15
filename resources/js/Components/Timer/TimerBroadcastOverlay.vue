<template>
  <!-- Debug Overlay - Super Admin Only -->
  <div 
    v-if="showDebugOverlay && user?.is_super_admin"
    ref="debugOverlay"
    :style="{ 
      left: debugPosition.x + 'px', 
      top: debugPosition.y + 'px',
      width: debugMinimized ? '200px' : '320px'
    }"
    class="fixed z-50 bg-gray-900 text-white rounded-lg shadow-2xl border border-gray-700 select-none"
    @mousedown="startDrag"
  >
    <!-- Debug Header -->
    <div class="bg-gray-800 px-3 py-2 rounded-t-lg flex items-center justify-between cursor-move">
      <div class="flex items-center space-x-2">
        <svg class="w-4 h-4 text-green-400" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.293l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd" />
        </svg>
        <span class="text-sm font-medium">Timer Debug</span>
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
          @click.stop="setShowDebugOverlay(false)"
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
    <div v-if="!debugMinimized" class="p-3 text-xs space-y-1 max-h-80 overflow-auto">
      <div class="grid grid-cols-2 gap-2 mb-2">
        <div class="text-green-400 font-mono">shouldShowOverlay:</div>
        <div class="font-mono">{{ shouldShowOverlay }}</div>
        
        <div class="text-blue-400 font-mono">user_type:</div>
        <div class="font-mono">{{ user?.user_type || 'null' }}</div>
        
        <div class="text-purple-400 font-mono">canCreateTimers:</div>
        <div class="font-mono">{{ canCreateTimers }}</div>
        
        <div class="text-yellow-400 font-mono">overlay setting:</div>
        <div class="font-mono">{{ timerSettings?.show_timer_overlay }}</div>
        
        <div class="text-cyan-400 font-mono">active timers:</div>
        <div class="font-mono">{{ timers?.length || 0 }}</div>
      </div>
      
      <div class="border-t border-gray-700 pt-2">
        <div class="text-orange-400 font-mono mb-1">Permissions:</div>
        <div class="text-xs">
          <div>exists: <span class="font-mono">{{ !!user?.permissions }}</span></div>
          <div>is array: <span class="font-mono">{{ Array.isArray(user?.permissions) }}</span></div>
          <div>length: <span class="font-mono">{{ user?.permissions?.length || 0 }}</span></div>
          <div>timers.write: <span class="font-mono">{{ user?.permissions?.includes?.('timers.write') }}</span></div>
          <div>timers.manage: <span class="font-mono">{{ user?.permissions?.includes?.('timers.manage') }}</span></div>
        </div>
      </div>
      
      <div class="border-t border-gray-700 pt-2 text-gray-400">
        <div class="text-xs">💡 Drag to move • Click [-] to minimize • Settings > Advanced to disable</div>
      </div>
    </div>
  </div>

  <div 
    v-if="shouldShowOverlay" 
    class="fixed bottom-4 right-4 z-50"
  >
    <!-- Connection Status and Controls -->
    <div class="flex items-center mb-2" :class="{ 'min-w-80': timers.length === 0, 'justify-between': timers.length > 1, 'justify-end': timers.length <= 1 }">
      <!-- Totals (if multiple timers) -->
      <div 
        v-if="timers.length > 1"
        class="bg-blue-50 dark:bg-blue-900/20 rounded-lg px-2 py-1 text-xs border border-blue-200 dark:border-blue-800"
        title="Combined total of all active timers"
      >
        <div class="flex items-center space-x-2">
          <span class="font-medium text-blue-900 dark:text-blue-100">
            Total ({{ timers.length }}):
          </span>
          <div class="font-mono font-bold text-blue-900 dark:text-blue-100">
            {{ formatDuration(totalDuration) }}
          </div>
          <div class="text-blue-700 dark:text-blue-300">
            ${{ totalAmount.toFixed(2) }}
          </div>
        </div>
      </div>

      <!-- Right side controls -->
      <div class="flex items-center space-x-2">
        <!-- Connection Status Indicator -->
        <div 
          :class="connectionStatusClasses"
          class="px-2 py-1 rounded-full text-xs font-medium flex items-center space-x-1"
          :title="`Timer sync status: ${connectionStatusText.toLowerCase()}`"
        >
          <div 
            :class="connectionDotClasses"
            class="w-2 h-2 rounded-full"
          ></div>
          <span>{{ connectionStatusText }}</span>
        </div>

        <!-- Expand/Collapse All -->
        <button
          v-if="timers.length > 1"
          @click="toggleAllTimers"
          class="p-1 bg-gray-500 hover:bg-gray-600 text-white rounded-md transition-colors"
          :title="allExpanded ? 'Minimize All' : 'Expand All'"
        >
          <svg v-if="allExpanded" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3 3-3"/>
          </svg>
          <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3-3 3"/>
          </svg>
        </button>
        
        <!-- New Timer Button -->
        <button
          v-if="canCreateTimers"
          @click="showQuickStart = !showQuickStart"
          class="p-1 bg-green-500 hover:bg-green-600 text-white rounded-md transition-colors"
          title="New Timer"
        >
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Quick Start Form -->
    <div 
      v-if="showQuickStart"
      class="mb-2 bg-gradient-to-br from-white/95 via-white/90 to-white/85 dark:from-gray-800/95 dark:via-gray-800/90 dark:to-gray-900/85 backdrop-blur-sm rounded-lg shadow-lg border border-gray-200/50 dark:border-gray-700/50 p-3"
    >
      <TimerConfigurationForm
        ref="quickStartFormRef"
        mode="create"
        :compact-mode="true"
        :show-labels="false"
        :show-user-selection="false"
        description-placeholder="Timer description..."
        account-placeholder="No account (general timer)"
        ticket-placeholder="No specific ticket"
        billing-rate-placeholder="No billing rate"
        @submit="startQuickTimer"
        @cancel="closeQuickStart"
      />
    </div>

    <!-- Timer Settings Form (stacked like quick start) -->
    <div 
      v-if="showTimerSettings"
      class="mb-2 bg-gradient-to-br from-white/95 via-white/90 to-white/85 dark:from-gray-800/95 dark:via-gray-800/90 dark:to-gray-900/85 backdrop-blur-sm rounded-lg shadow-lg border border-gray-200/50 dark:border-gray-700/50 p-3"
    >
      <!-- Settings Header -->
      <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
          Timer Settings
        </h3>
        <button
          @click="closeTimerSettings"
          class="text-gray-400 hover:text-gray-600 transition-colors"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <!-- Timer Configuration Form -->
      <TimerConfigurationForm
        ref="timerSettingsFormRef"
        mode="edit"
        :timer-data="currentTimerSettings"
        :show-labels="true"
        :show-account-selection="true"
        :show-ticket-selection="true"
        :show-user-selection="false"
        :show-cancel-button="true"
        :compact-mode="true"
        description-placeholder="Enter timer description..."
        @submit="saveTimerSettings"
        @cancel="closeTimerSettings"
      />
    </div>

    <!-- Timer Cards Container - Horizontal Right-to-Left -->
    <div v-if="timers.length > 0" class="flex flex-row-reverse space-x-reverse space-x-2">
      <!-- Individual Timer -->
      <div 
        v-for="timer in reactiveTimerData" 
        :key="timer.id"
        class="relative"
      >
        <!-- Mini Badge (Default State) -->
        <div 
          v-if="!expandedTimers[timer.id]"
          @click="toggleTimerExpansion(timer.id)"
          class="bg-gradient-to-br from-white/95 via-white/90 to-white/85 dark:from-gray-800/95 dark:via-gray-800/90 dark:to-gray-900/85 backdrop-blur-sm rounded-lg shadow-lg border border-gray-200/50 dark:border-gray-700/50 p-2 cursor-pointer hover:shadow-xl transition-all flex items-center space-x-2 min-w-48"
        >
          <!-- Status Indicator (Left) -->
          <div 
            :class="timerStatusClasses(timer.status)"
            class="w-2 h-2 rounded-full flex-shrink-0"
          ></div>
          
          <!-- Duration Display (Center) -->
          <div class="text-sm font-mono font-bold text-gray-900 dark:text-gray-100">
            {{ formatDuration(timer.currentDuration, true) }}
          </div>
          
          <!-- Price Display (Right) -->
          <div 
            v-if="timer.billing_rate"
            class="text-xs text-green-600 dark:text-green-400 font-medium flex-shrink-0"
          >
            ${{ (timer.currentAmount || 0).toFixed(2) }}
          </div>
        </div>

        <!-- Expanded Panel -->
        <div 
          v-else
          class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm rounded-lg shadow-lg border border-gray-200/50 dark:border-gray-700/50 p-4 min-w-80"
        >
          <!-- Timer Header -->
          <div class="flex items-center justify-between mb-2">
            <div class="flex items-center space-x-2">
              <div 
                :class="timerStatusClasses(timer.status)"
                class="w-3 h-3 rounded-full"
                :title="`Timer status: ${timer.status}`"
              ></div>
              <span 
                class="text-sm font-medium text-gray-900 dark:text-gray-100"
                :title="timer.description || 'No description set'"
              >
                {{ timer.description || 'Timer' }}
              </span>
            </div>
            <div class="flex items-center space-x-1">
              <button
                v-if="canControlOwnTimer(timer) || canManageAllTimers(timer)"
                @click="openTimerSettings(timer)"
                class="text-gray-400 hover:text-blue-600 transition-colors"
                title="Timer Settings"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
              </button>
              <button
                @click="toggleTimerExpansion(timer.id)"
                class="text-gray-400 hover:text-gray-600 transition-colors"
                :title="expandedTimers[timer.id] ? 'Minimize timer' : 'Expand timer'"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                </svg>
              </button>
            </div>
          </div>

          <!-- Timer Display -->
          <div class="text-center mb-3">
            <div 
              class="text-2xl font-mono font-bold text-gray-900 dark:text-gray-100"
              :title="`Current duration: ${formatDuration(timer.currentDuration, false, true)}`"
            >
              {{ formatDuration(timer.currentDuration, false, true) }}
            </div>
            <div 
              v-if="timer.billing_rate"
              class="text-sm text-gray-600 dark:text-gray-400"
              :title="`Billing: $${timer.billing_rate.rate}/hour`"
            >
              ${{ (timer.currentAmount || 0).toFixed(2) }} @ ${{ timer.billing_rate.rate }}/hr
            </div>
          </div>

          <!-- Timer Controls -->
          <div class="flex space-x-2">
            <button
              v-if="timer.status === 'running' && (canControlOwnTimer(timer) || canManageAllTimers(timer))"
              @click="pauseTimer(timer.id)"
              class="flex-1 p-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-md transition-colors"
              title="Pause Timer"
            >
              <svg class="w-4 h-4 mx-auto" fill="currentColor" viewBox="0 0 24 24">
                <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/>
              </svg>
            </button>
            <button
              v-if="timer.status === 'paused' && (canControlOwnTimer(timer) || canManageAllTimers(timer))"
              @click="resumeTimer(timer.id)"
              class="flex-1 p-2 bg-green-500 hover:bg-green-600 text-white rounded-md transition-colors"
              title="Resume Timer"
            >
              <svg class="w-4 h-4 mx-auto" fill="currentColor" viewBox="0 0 24 24">
                <path d="M8 5v14l11-7z"/>
              </svg>
            </button>
            <button
              v-if="canCommitOwnTimer(timer)"
              @click="handleStopTimer(timer)"
              class="flex-1 p-2 bg-red-500 hover:bg-red-600 text-white rounded-md transition-colors"
              title="Stop & Commit Timer"
            >
              <svg class="w-4 h-4 mx-auto" fill="currentColor" viewBox="0 0 24 24">
                <path d="M6 6h12v12H6z"/>
              </svg>
            </button>
          </div>

          <!-- Ticket Info -->
          <div 
            v-if="timer.ticket"
            class="mt-2 text-xs text-gray-500 dark:text-gray-400"
            :title="`Associated ticket: ${timer.ticket.ticket_number}`"
          >
            <span>{{ timer.ticket.ticket_number }} - {{ timer.ticket.title }}</span>
          </div>
        </div>
      </div>
    </div>



    <!-- Unified Time Entry Dialog for Timer Commit -->
    <UnifiedTimeEntryDialog
      :show="showCommitDialog"
      mode="timer-commit"
      :timer-data="timerToCommit"
      @close="closeCommitDialog"
      @timer-committed="handleTimerCommitted"
    />
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted, reactive, watch } from 'vue'
import { useTimerBroadcasting } from '@/Composables/useTimerBroadcasting.js'
import { usePage } from '@inertiajs/vue3'
import { useBillingRatesQuery } from '@/Composables/queries/useBillingQuery'
import { useTicketsQuery } from '@/Composables/queries/useTicketsQuery'
import { useLocalStorageReactive } from '@/Composables/useLocalStorageReactive.js'
import TimerConfigurationForm from '@/Components/Timer/TimerConfigurationForm.vue'
import Modal from '@/Components/Modal.vue'
import UnifiedTimeEntryDialog from '@/Components/TimeEntries/UnifiedTimeEntryDialog.vue'
import { useTimerSettings } from '@/Composables/useTimerSettings.js'

const {
  timers,
  connectionStatus,
  pauseTimer,
  resumeTimer,
  stopTimer,
  startTimer,
  addOrUpdateTimer,
  removeTimer
} = useTimerBroadcasting()

// Timer settings integration
const { settings: timerSettings, formatDuration: formatDurationFromSettings } = useTimerSettings()

// Quick start form state (managed by TimerConfigurationForm)
const quickStartFormRef = ref(null)

// Timer expansion state
const expandedTimers = reactive({})
const showQuickStart = ref(false)

// Real-time update state
const currentTime = ref(new Date())
let updateInterval = null

// Debug overlay state - use reactive localStorage
const [showDebugOverlay, setShowDebugOverlay] = useLocalStorageReactive('debug_overlay_enabled', false)
const debugMinimized = ref(false)
const debugPosition = reactive({
  x: parseInt(localStorage.getItem('debug_overlay_x')) || 16,
  y: parseInt(localStorage.getItem('debug_overlay_y')) || 16
})
const debugOverlay = ref(null)
let isDragging = false
let dragOffset = { x: 0, y: 0 }

// Timer settings state
const showTimerSettings = ref(false)
const currentTimerSettings = ref(null)
const timerSettingsFormRef = ref(null)

// Commit dialog state
const showCommitDialog = ref(false)
const timerToCommit = ref(null)

// Manual time override setting (should be configurable via system settings)
const allowManualTimeOverride = ref(true) // TODO: Load from system settings

// Access user data from Inertia
const page = usePage()
const user = computed(() => page.props.auth?.user)

// ABAC Permission computeds
const isAdmin = computed(() => {
  return user.value?.permissions?.includes('admin.read') || user.value?.permissions?.includes('admin.manage')
})

const canViewMyTimers = computed(() => {
  // Users can always view their own timers
  return user.value?.permissions?.includes('timers.read') || 
         user.value?.permissions?.includes('timers.write')
})

const canViewAllTimers = computed(() => {
  // Admins and managers can view all timers
  return isAdmin.value || 
         user.value?.permissions?.includes('timers.manage') ||
         user.value?.permissions?.includes('teams.manage')
})

const canManageTimers = computed(() => {
  // Admins can manage any timer, users can manage their own
  return isAdmin.value || user.value?.permissions?.includes('timers.manage')
})

const canControlTimers = computed(() => {
  // Users can control (pause/resume/stop) their own timers
  return user.value?.permissions?.includes('timers.write') ||
         user.value?.permissions?.includes('timers.manage')
})

const canCommitTimers = computed(() => {
  // Users can commit their own timers to time entries
  return user.value?.permissions?.includes('timers.write') ||
         user.value?.permissions?.includes('timers.manage')
})

const canCreateTimers = computed(() => {
  // Users can create timers if they have write permissions
  return user.value?.permissions?.includes('timers.write') ||
         user.value?.permissions?.includes('timers.manage')
})

// Determine when to show the overlay - show if there are active timers OR if user can create timers
const shouldShowOverlay = computed(() => {
  // Check timer settings first
  if (!timerSettings.value.show_timer_overlay) {
    return false
  }
  
  // Always show if there are active timers that the user can see
  if (timers.value?.length > 0) {
    return true
  }
  
  // Show overlay if user can create timers (agents, not customers)
  return canCreateTimers.value && user.value?.user_type === 'agent'
})


// Computed properties for timer stats (reactive to currentTime for real-time updates)
const totalDuration = computed(() => {
  // Include currentTime.value to make this reactive to time changes
  const _ = currentTime.value
  return timers.value.reduce((total, timer) => {
    return total + calculateDuration(timer)
  }, 0)
})

const totalAmount = computed(() => {
  // Include currentTime.value to make this reactive to time changes
  const _ = currentTime.value
  return timers.value.reduce((total, timer) => {
    return total + calculateAmount(timer)
  }, 0)
})

// Timer utility functions
const calculateDuration = (timer) => {
  if (!timer) return 0
  if (timer.status !== 'running') {
    // Timer is stopped/paused - duration is in seconds from backend
    return timer.duration || 0
  }
  
  const startedAt = new Date(timer.started_at)
  const now = currentTime.value
  const totalPausedSeconds = timer.total_paused_duration || 0
  
  return Math.max(0, Math.floor((now - startedAt) / 1000) - totalPausedSeconds)
}

const calculateAmount = (timer) => {
  if (!timer || !timer.billing_rate) return 0
  
  const duration = calculateDuration(timer)
  const hours = duration / 3600
  return hours * timer.billing_rate.rate
}

// Create reactive timer data for real-time updates
const reactiveTimerData = computed(() => {
  // Include currentTime.value to make this reactive to time changes
  const _ = currentTime.value
  return timers.value.map(timer => ({
    ...timer,
    currentDuration: calculateDuration(timer),
    currentAmount: calculateAmount(timer)
  }))
})

// Delete timer function
const deleteTimer = async (timerId) => {
  try {
    await window.axios.delete(`/api/timers/${timerId}?force=true`)
    removeTimer(timerId)
  } catch (error) {
    console.error('Failed to delete timer:', error)
  }
}

// Toggle expansion of individual timer
const toggleTimerExpansion = (timerId) => {
  expandedTimers[timerId] = !expandedTimers[timerId]
}

// Toggle all timers expansion
const toggleAllTimers = () => {
  const shouldExpand = !allExpanded.value
  timers.value.forEach(timer => {
    expandedTimers[timer.id] = shouldExpand
  })
}

// Computed properties
const allExpanded = computed(() => 
  timers.value.length > 0 && timers.value.every(timer => expandedTimers[timer.id])
)

const connectionStatusClasses = computed(() => ({
  'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400': connectionStatus.value === 'connected',
  'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400': connectionStatus.value === 'connecting',
  'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400': connectionStatus.value === 'disconnected',
  'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400': connectionStatus.value === 'disabled'
}))

const connectionDotClasses = computed(() => ({
  'bg-green-500 animate-pulse': connectionStatus.value === 'connected',
  'bg-yellow-500 animate-pulse': connectionStatus.value === 'connecting',
  'bg-red-500': connectionStatus.value === 'disconnected',
  'bg-gray-500': connectionStatus.value === 'disabled'
}))

const connectionStatusText = computed(() => {
  switch (connectionStatus.value) {
    case 'connected': return 'Live'
    case 'connecting': return 'Connecting'
    case 'disconnected': return 'Offline'
    case 'disabled': return 'Mock'
    default: return 'Unknown'
  }
})

// Debug overlay drag functionality
const startDrag = (e) => {
  if (e.target.closest('button')) return // Don't drag when clicking buttons
  
  isDragging = true
  const rect = debugOverlay.value.getBoundingClientRect()
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
  const maxX = window.innerWidth - (debugMinimized.value ? 200 : 320)
  const maxY = window.innerHeight - 100
  
  debugPosition.x = Math.max(0, Math.min(newX, maxX))
  debugPosition.y = Math.max(0, Math.min(newY, maxY))
  
  // Save position to localStorage
  localStorage.setItem('debug_overlay_x', debugPosition.x)
  localStorage.setItem('debug_overlay_y', debugPosition.y)
}

const stopDrag = () => {
  isDragging = false
  document.removeEventListener('mousemove', drag)
  document.removeEventListener('mouseup', stopDrag)
}

// Timer status classes
const timerStatusClasses = (status) => ({
  'bg-green-500 animate-pulse': status === 'running',
  'bg-yellow-500': status === 'paused',
  'bg-red-500': status === 'stopped'
})

// Handle account selection
// These handlers are now managed by TimerConfigurationForm
// Keeping placeholder functions for any additional logic if needed in the future
const handleAccountSelected = (account) => {
  // Additional logic can be added here if needed
}

const handleTicketSelected = (ticket) => {
  // Additional logic can be added here if needed
}

const handleRateSelected = (rate) => {
  // Additional logic can be added here if needed
}

// Close quick start modal
const closeQuickStart = () => {
  showQuickStart.value = false
  // Reset form is handled by TimerConfigurationForm component
  if (quickStartFormRef.value) {
    quickStartFormRef.value.resetSubmitState()
  }
}

// Quick start timer
const startQuickTimer = async (formData) => {
  if (!formData.description?.trim()) return
  
  try {
    const timerData = {
      description: formData.description,
      billing_rate_id: formData.billingRateId || null
    }
    
    // Add account ID if selected
    if (formData.accountId) {
      timerData.account_id = formData.accountId
    }
    
    // Add ticket ID if selected  
    if (formData.ticketId) {
      timerData.ticket_id = formData.ticketId
    }
    
    await startTimer(timerData)
    closeQuickStart()
  } catch (error) {
    console.error('Failed to start timer:', error)
    // Reset submit state on error
    if (quickStartFormRef.value) {
      quickStartFormRef.value.resetSubmitState()
    }
  }
}

// Timer settings functions
const openTimerSettings = (timer) => {
  currentTimerSettings.value = timer
  showTimerSettings.value = true
}

const closeTimerSettings = () => {
  showTimerSettings.value = false
  currentTimerSettings.value = null
  // Reset form is handled by TimerConfigurationForm component
  if (timerSettingsFormRef.value) {
    timerSettingsFormRef.value.resetSubmitState()
  }
}

const saveTimerSettings = async (formData) => {
  if (!currentTimerSettings.value) return
  
  try {
    const response = await window.axios.put(`/api/timers/${currentTimerSettings.value.id}`, {
      description: formData.description,
      account_id: formData.accountId || null,
      ticket_id: formData.ticketId || null,
      billing_rate_id: formData.billingRateId || null
    })
    
    // Update the timer in the list
    addOrUpdateTimer(response.data.data)
    closeTimerSettings()
  } catch (error) {
    console.error('Failed to save timer settings:', error)
    // Reset submit state on error
    if (timerSettingsFormRef.value) {
      timerSettingsFormRef.value.resetSubmitState()
    }
  }
}

const getBillingRateValue = (billingRateId) => {
  if (!billingRates.value || !billingRateId) return 0
  
  const rate = billingRates.value.find(r => r.id === billingRateId)
  return rate ? parseFloat(rate.rate) : 0
}

// Permission helper functions
const canControlOwnTimer = (timer) => {
  // Users can control their own timers if they have write permissions
  return timer.user_id === user.value?.id && canControlTimers.value
}

const canManageAllTimers = (timer) => {
  // Admins can manage any timer
  return canManageTimers.value
}

const canCommitOwnTimer = (timer) => {
  // Users can commit their own timers
  return timer.user_id === user.value?.id && canCommitTimers.value
}

// Timer commit workflow functions
const handleStopTimer = async (timer) => {
  // Check permissions
  if (!canCommitOwnTimer(timer) && !canManageAllTimers(timer)) {
    console.warn('User does not have permission to commit this timer')
    return
  }
  
  try {
    // Only pause if the timer is currently running
    if (timer.status === 'running') {
      await pauseTimer(timer.id)
    }
    
    // Open unified time entry dialog for timer commit
    timerToCommit.value = timer
    showCommitDialog.value = true
  } catch (error) {
    console.error('Failed to pause timer for commit:', error)
  }
}

const closeCommitDialog = () => {
  showCommitDialog.value = false
  timerToCommit.value = null
}

// Handle timer commit from UnifiedTimeEntryDialog
const handleTimerCommitted = ({ timeEntry, timerData }) => {
  // Remove the timer from the overlay since it's now committed
  removeTimer(timerData.id)
  
  // Close the dialog
  closeCommitDialog()
  
  console.log('Timer committed successfully:', timeEntry)
}

// Format duration function with compact mode and expanded HH:MM:SS support
const formatDuration = (seconds, compact = false, expanded = false) => {
  if (!seconds || seconds < 0) return compact ? '0:00' : (expanded ? '0:00:00' : '0s')
  
  // Use settings-based formatting for non-compact/expanded modes
  if (!compact && !expanded && formatDurationFromSettings) {
    return formatDurationFromSettings(seconds)
  }
  
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  const secs = seconds % 60
  
  if (expanded) {
    // Expanded format: always show HH:MM:SS
    return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
  } else if (compact) {
    // Compact format: show just minutes and seconds for mini badges
    if (hours > 0) {
      return `${hours}:${minutes.toString().padStart(2, '0')}`
    } else {
      return `${minutes}:${secs.toString().padStart(2, '0')}`
    }
  } else {
    // Fallback format if settings not available
    if (hours > 0) {
      return `${hours}h ${minutes}m ${secs}s`
    } else if (minutes > 0) {
      return `${minutes}m ${secs}s`
    } else {
      return `${secs}s`
    }
  }
}

// localStorage changes are now handled by useLocalStorageReactive

// Lifecycle hooks
onMounted(() => {
  // Start real-time timer updates every second
  updateInterval = setInterval(() => {
    currentTime.value = new Date()
  }, 1000)
})

onUnmounted(() => {
  // Clean up the timer update interval
  if (updateInterval) {
    clearInterval(updateInterval)
    updateInterval = null
  }
  
  // Clean up drag event listeners
  document.removeEventListener('mousemove', drag)
  document.removeEventListener('mouseup', stopDrag)
})
</script>

<style scoped>
/* Smooth transitions for all interactive elements */
.transition-all {
  transition: all 0.2s ease-in-out;
}

/* Ensure proper z-index stacking */
.relative {
  position: relative;
}

/* Zero-delay tooltips for instant feedback */
[title] {
  position: relative;
}

[title]:hover::after {
  content: attr(title);
  position: absolute;
  bottom: 100%;
  left: 50%;
  transform: translateX(-50%);
  padding: 4px 8px;
  background-color: rgba(0, 0, 0, 0.9);
  color: white;
  border-radius: 4px;
  font-size: 12px;
  white-space: nowrap;
  z-index: 1000;
  pointer-events: none;
  animation: tooltipFadeIn 0s forwards;
}

[title]:hover::before {
  content: '';
  position: absolute;
  bottom: calc(100% - 4px);
  left: 50%;
  transform: translateX(-50%);
  border-left: 4px solid transparent;
  border-right: 4px solid transparent;
  border-top: 4px solid rgba(0, 0, 0, 0.9);
  z-index: 1000;
  pointer-events: none;
  animation: tooltipFadeIn 0s forwards;
}

@keyframes tooltipFadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}
</style>