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

  <!-- Docked Mode: Original corner overlay -->
  <div
    v-if="shouldShowOverlay && isDocked"
    :class="overlayPositionClasses"
  >
    <!-- Main Horizontal Container -->
    <div class="flex flex-row items-end space-x-3">

      <!-- Quick Action Buttons (Left Side) -->
      <div v-if="showQuickStart || showTimerSettings" class="flex flex-col space-y-2">
        <!-- Quick Start Button -->
        <button
          v-if="showQuickStart"
          @click="showStartTimerModal = true"
          class="bg-gradient-to-br from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-lg px-4 py-2 text-sm font-medium shadow-lg border border-green-400/30 transition-all duration-200 flex items-center space-x-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
          </svg>
          <span>New Timer</span>
        </button>

        <!-- Timer Settings Button -->
        <button
          v-if="showTimerSettings"
          @click="showTimerSettingsModal = true"
          class="bg-gradient-to-br from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-lg px-4 py-2 text-sm font-medium shadow-lg border border-blue-400/30 transition-all duration-200 flex items-center space-x-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
          </svg>
          <span>Settings</span>
        </button>
      </div>

      <!-- Timers and Controls Container (Right Side) -->
      <div class="flex flex-col space-y-2">
        <!-- Connection Status and Controls -->
        <div class="flex items-center justify-between" :class="{ 'min-w-80': timers.length === 0 }">
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
              <div
                v-if="totalAmount > 0"
                class="text-xs text-blue-700 dark:text-blue-300 font-medium"
              >
                ${{ totalAmount.toFixed(2) }}
              </div>
            </div>
          </div>

          <!-- Dock Toggle, Connection Status and New Timer Button -->
          <div class="flex items-center space-x-2" :class="{ 'ml-auto': timers.length <= 1 }">
            <!-- Dock Toggle Button -->
            <button
              @click="toggleDockPosition"
              class="bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 px-2 py-1 rounded-lg text-xs font-medium transition-colors flex items-center space-x-1"
              :title="isDocked ? 'Undock to moveable panel' : 'Dock to corner'"
              :disabled="isLoadingPreferences"
            >
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path v-if="isDocked" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
              </svg>
              <span>{{ isDocked ? 'Undock' : 'Dock' }}</span>
            </button>
            <!-- Connection Status -->
            <div
              :class="connectionStatusClasses"
              class="text-xs px-2 py-1 rounded font-medium flex items-center space-x-1"
              :title="`Timer sync status: ${connectionStatusText.toLowerCase()}`"
            >
              <div
                :class="connectionDotClasses"
                class="w-1.5 h-1.5 rounded-full"
              ></div>
              <span>{{ connectionStatusText }}</span>
            </div>

            <!-- New Timer Button -->
            <button
              v-if="canCreateTimers"
              @click="showStartTimerModal = true"
              class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-lg text-sm font-medium transition-colors flex items-center space-x-1 shadow-sm"
              title="Start new timer"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
              </svg>
              <span>New Timer</span>
            </button>
          </div>
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
      </div>
    </div>
  </div>

  <!-- Undocked Mode: Moveable panel with unified timer list -->
  <div
    v-if="shouldShowOverlay && !isDocked"
    ref="overlayPanel"
    :class="[
      overlayPositionClasses,
      'bg-white/95 dark:bg-gray-800/95 backdrop-blur-sm rounded-lg shadow-xl border border-gray-200/50 dark:border-gray-700/50 min-w-72 max-w-80',
      { 'cursor-move': !panelDragging, 'cursor-grabbing': panelDragging }
    ]"
    :style="overlayPanelStyle"
    @mousedown="startPanelDrag"
  >
    <!-- Panel Header -->
    <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-t-lg px-3 py-1.5 flex items-center justify-between cursor-move">
      <div class="flex items-center space-x-2">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.293l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd" />
        </svg>
        <span class="font-medium text-sm">Timers</span>
        <div
          :class="connectionDotClasses"
          class="w-2 h-2 rounded-full ml-2"
          :title="`Status: ${connectionStatusText}`"
        ></div>
      </div>
      <div class="flex items-center space-x-1">
        <!-- New Timer Button -->
        <button
          v-if="canCreateTimers"
          @click.stop="showStartTimerModal = true"
          class="bg-white/20 hover:bg-white/30 text-white px-2 py-1 rounded text-xs font-medium transition-colors flex items-center space-x-1"
          title="Start new timer"
        >
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
          </svg>
        </button>
        <!-- Dock Button -->
        <button
          @click.stop="toggleDockPosition"
          class="bg-white/20 hover:bg-white/30 text-white p-1 rounded transition-colors"
          title="Dock to corner"
          :disabled="isLoadingPreferences"
        >
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
          </svg>
        </button>
      </div>
    </div>



    <!-- Timer List -->
    <div class="p-3">
      <!-- Summary Stats -->
      <div
        v-if="timers.length > 1"
        class="bg-blue-50 dark:bg-blue-900/20 rounded-lg px-2 py-1.5 mb-2 border border-blue-200 dark:border-blue-800"
      >
        <div class="flex items-center justify-between text-xs">
          <span class="font-medium text-blue-900 dark:text-blue-100">
            Total ({{ timers.length }} timers):
          </span>
          <div class="flex items-center space-x-2">
            <div class="font-mono font-bold text-blue-900 dark:text-blue-100">
              {{ formatDuration(totalDuration) }}
            </div>
            <div
              v-if="totalAmount > 0"
              class="text-xs text-blue-700 dark:text-blue-300 font-medium"
            >
              ${{ totalAmount.toFixed(2) }}
            </div>
          </div>
        </div>
      </div>

      <!-- Timer List Items -->
      <div v-if="timers.length > 0" class="space-y-1.5">
        <div
          v-for="timer in reactiveTimerData"
          :key="timer.id"
          class="bg-gray-50 dark:bg-gray-700/50 rounded-md p-2 border border-gray-200 dark:border-gray-600"
        >
          <!-- Timer Header -->
          <div class="flex items-center justify-between mb-1">
            <div class="flex items-center space-x-2 flex-1 min-w-0">
              <div
                :class="timerStatusClasses(timer.status)"
                class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                :title="`Timer status: ${timer.status}`"
              ></div>
              <span
                class="text-xs font-medium text-gray-900 dark:text-gray-100 truncate"
                :title="timer.description || 'No description set'"
              >
                {{ timer.description || 'Timer' }}
              </span>
            </div>
            <button
              v-if="canControlOwnTimer(timer) || canManageAllTimers(timer)"
              @click.stop="openTimerSettings(timer)"
              class="text-gray-400 hover:text-blue-600 transition-colors flex-shrink-0"
              title="Timer Settings"
            >
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
              </svg>
            </button>
          </div>

          <!-- Timer Duration and Cost -->
          <div class="flex items-center justify-between mb-1">
            <div
              class="text-sm font-mono font-bold text-gray-900 dark:text-gray-100"
              :title="`Current duration: ${formatDuration(timer.currentDuration, false, true)}`"
            >
              {{ formatDuration(timer.currentDuration, false, true) }}
            </div>
            <div
              v-if="timer.billing_rate"
              class="text-xs text-green-600 dark:text-green-400 font-medium"
              :title="`Billing: $${timer.billing_rate.rate}/hour`"
            >
              ${{ (timer.currentAmount || 0).toFixed(2) }}
            </div>
          </div>

          <!-- Timer Controls -->
          <div class="flex space-x-1">
            <button
              v-if="timer.status === 'running' && (canControlOwnTimer(timer) || canManageAllTimers(timer))"
              @click.stop="pauseTimer(timer.id)"
              class="flex-1 p-1.5 bg-yellow-500 hover:bg-yellow-600 text-white rounded text-xs font-medium transition-colors"
              title="Pause Timer"
            >
              <svg class="w-3 h-3 mx-auto" fill="currentColor" viewBox="0 0 24 24">
                <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/>
              </svg>
            </button>
            <button
              v-if="timer.status === 'paused' && (canControlOwnTimer(timer) || canManageAllTimers(timer))"
              @click.stop="resumeTimer(timer.id)"
              class="flex-1 p-1.5 bg-green-500 hover:bg-green-600 text-white rounded text-xs font-medium transition-colors"
              title="Resume Timer"
            >
              <svg class="w-3 h-3 mx-auto" fill="currentColor" viewBox="0 0 24 24">
                <path d="M8 5v14l11-7z"/>
              </svg>
            </button>
            <button
              v-if="canCommitOwnTimer(timer)"
              @click.stop="handleStopTimer(timer)"
              class="flex-1 p-1.5 bg-red-500 hover:bg-red-600 text-white rounded text-xs font-medium transition-colors"
              title="Stop & Commit Timer"
            >
              <svg class="w-3 h-3 mx-auto" fill="currentColor" viewBox="0 0 24 24">
                <path d="M6 6h12v12H6z"/>
              </svg>
            </button>
          </div>

          <!-- Ticket Info -->
          <div
            v-if="timer.ticket"
            class="mt-1 text-xs text-gray-500 dark:text-gray-400"
            :title="`Associated ticket: ${timer.ticket.ticket_number}`"
          >
            <span>{{ timer.ticket.ticket_number }} - {{ timer.ticket.title }}</span>
          </div>
        </div>
      </div>

      <!-- No Timers Message -->
      <div
        v-if="timers.length === 0"
        class="text-center py-3 text-gray-500 dark:text-gray-400"
      >
        <svg class="w-6 h-6 mx-auto mb-1 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <p class="text-xs">No active timers</p>
        <p class="text-xs mt-0.5 opacity-75">Click + to start timing</p>
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

    <!-- Start Timer Modal -->
    <StartTimerModal
      :show="showStartTimerModal"
      @close="showStartTimerModal = false"
      @started="handleTimerStarted"
    />

    <!-- Timer Settings Modal -->
    <StartTimerModal
      :show="showTimerSettingsModal"
      mode="edit"
      :timer="currentTimerSettings"
      @close="showTimerSettingsModal = false"
      @updated="handleTimerUpdated"
    />

</template>

<script setup>
import { computed, ref, onMounted, onUnmounted, reactive, watch } from 'vue'
import { useTimerBroadcasting } from '@/Composables/useTimerBroadcasting.js'
import { usePage } from '@inertiajs/vue3'
import { useBillingRatesQuery } from '@/Composables/queries/useBillingQuery'
import { useTicketsQuery } from '@/Composables/queries/useTicketsQuery'
import { useLocalStorageReactive } from '@/Composables/useLocalStorageReactive.js'
import StartTimerModal from '@/Components/Timer/StartTimerModal.vue'
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

// Modal states
const showStartTimerModal = ref(false)
const showTimerSettingsModal = ref(false)

// Manual time override setting (should be configurable via system settings)
const allowManualTimeOverride = ref(true) // TODO: Load from system settings

// Access user data from Inertia
const page = usePage()
const user = computed(() => page.props.auth?.user)

// Overlay position state with user preference persistence
const isDocked = ref(true) // true = docked to corner, false = undocked moveable panel
const isLoadingPreferences = ref(true)
const panelPosition = reactive({
  x: 100,
  y: 100
})
const panelDragging = ref(false)
let panelDragOffset = { x: 0, y: 0 }
const overlayPanel = ref(null)

// Load user preferences on mount
const loadUserPreferences = async () => {
  if (!user.value?.id) {
    isLoadingPreferences.value = false
    return
  }

  try {
    // Load dock status preference
    const dockResponse = await window.axios.get('/api/user-preferences/timer_overlay_docked')
    isDocked.value = dockResponse.data?.data?.value !== false // Default to docked (true)

    // Load panel position preference
    try {
      const positionResponse = await window.axios.get('/api/user-preferences/timer_overlay_position')
      if (positionResponse.data?.data?.value) {
        Object.assign(panelPosition, positionResponse.data.data.value)
      }
    } catch (posError) {
      // Keep default position if preference doesn't exist
    }
  } catch (error) {
    // If preference doesn't exist, default to docked
    isDocked.value = true
  } finally {
    isLoadingPreferences.value = false
  }
}

// Save overlay preferences
const saveOverlayPosition = async (docked) => {
  if (!user.value?.id) return

  try {
    await window.axios.post('/api/user-preferences', {
      key: 'timer_overlay_docked',
      value: docked
    })
  } catch (error) {
    console.error('Failed to save overlay dock preference:', error)
  }
}

const savePanelPosition = async () => {
  if (!user.value?.id) return

  try {
    await window.axios.post('/api/user-preferences', {
      key: 'timer_overlay_position',
      value: {
        x: panelPosition.x,
        y: panelPosition.y
      }
    })
  } catch (error) {
    console.error('Failed to save panel position preference:', error)
  }
}

// Toggle dock position
const toggleDockPosition = async () => {
  const newPosition = !isDocked.value
  isDocked.value = newPosition
  await saveOverlayPosition(newPosition)
}

// Panel drag functionality
const startPanelDrag = (e) => {
  if (isDocked.value || e.target.closest('button') || e.target.closest('input') || e.target.closest('select') || e.target.closest('textarea') || e.target.closest('[contenteditable]')) {
    return // Don't drag when docked or clicking interactive elements
  }

  panelDragging.value = true
  const rect = overlayPanel.value.getBoundingClientRect()
  panelDragOffset.x = e.clientX - rect.left
  panelDragOffset.y = e.clientY - rect.top

  document.addEventListener('mousemove', dragPanel)
  document.addEventListener('mouseup', stopPanelDrag)
  e.preventDefault()
}

const dragPanel = (e) => {
  if (!panelDragging.value || isDocked.value) return

  const newX = e.clientX - panelDragOffset.x
  const newY = e.clientY - panelDragOffset.y

  // Keep panel within viewport bounds with some padding
  const padding = 20
  const panelWidth = overlayPanel.value?.offsetWidth || 400
  const panelHeight = overlayPanel.value?.offsetHeight || 300

  const maxX = window.innerWidth - panelWidth - padding
  const maxY = window.innerHeight - panelHeight - padding

  panelPosition.x = Math.max(padding, Math.min(newX, maxX))
  panelPosition.y = Math.max(padding, Math.min(newY, maxY))
}

const stopPanelDrag = async () => {
  if (panelDragging.value) {
    panelDragging.value = false
    await savePanelPosition() // Save position when drag ends
  }
  document.removeEventListener('mousemove', dragPanel)
  document.removeEventListener('mouseup', stopPanelDrag)
}

// Computed position classes and styles
const overlayPositionClasses = computed(() => {
  if (isLoadingPreferences.value) {
    return 'fixed bottom-4 right-4 z-50' // Default position while loading
  }

  if (isDocked.value) {
    return 'fixed bottom-4 right-4 z-50' // Docked to bottom-right corner
  } else {
    return 'fixed z-50' // Undocked moveable panel
  }
})

const overlayPanelStyle = computed(() => {
  if (isDocked.value || isLoadingPreferences.value) {
    return {}
  }

  return {
    left: panelPosition.x + 'px',
    top: panelPosition.y + 'px'
  }
})

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
const toggleQuickStart = () => {
  showQuickStart.value = !showQuickStart.value

  // If closing, reset the form
  if (!showQuickStart.value && quickStartFormRef.value) {
    quickStartFormRef.value.resetSubmitState()
  }
}

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
  showTimerSettingsModal.value = true
}

const closeTimerSettings = () => {
  showTimerSettingsModal.value = false
  currentTimerSettings.value = null
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

// Handle new timer started from StartTimerModal
const handleTimerStarted = (newTimer) => {
  // Close the modal
  showStartTimerModal.value = false

  // Add the new timer to the overlay
  addOrUpdateTimer(newTimer)

  console.log('New timer started:', newTimer)
}

// Handle timer updated from StartTimerModal
const handleTimerUpdated = (updatedTimer) => {
  // Close the modal
  showTimerSettingsModal.value = false

  // Update the timer in the overlay
  addOrUpdateTimer(updatedTimer)

  console.log('Timer updated:', updatedTimer)
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
onMounted(async () => {
  // Load user preferences first
  await loadUserPreferences()

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
  document.removeEventListener('mousemove', dragPanel)
  document.removeEventListener('mouseup', stopPanelDrag)
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
