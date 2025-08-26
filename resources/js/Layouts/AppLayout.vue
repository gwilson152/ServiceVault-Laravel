<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Main Content -->
    <div class="flex h-screen overflow-hidden">
      <!-- Sidebar -->
      <div :class="[
        'hidden md:flex md:flex-col transition-all duration-300',
        sidebarCollapsed ? 'md:w-20' : 'md:w-64'
      ]">
        <div class="flex flex-col flex-grow pt-5 overflow-y-auto bg-white border-r border-gray-200 shadow-sm">
          <!-- Logo -->
          <div class="flex items-center justify-between flex-shrink-0 px-4">
            <h1 v-show="!sidebarCollapsed" class="text-xl font-semibold text-gray-900 transition-opacity duration-300">Service Vault</h1>
            <div v-show="sidebarCollapsed" class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center">
              <span class="text-white font-bold text-sm">SV</span>
            </div>
            
            <!-- Pin/Collapse Button -->
            <button
              @click="toggleSidebar"
              class="p-1.5 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 transition-all duration-200 hover:scale-105 active:scale-95"
              :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
            >
              <ChevronDoubleLeftIcon v-if="!sidebarCollapsed" class="h-5 w-5" />
              <ChevronDoubleRightIcon v-else class="h-5 w-5" />
            </button>
          </div>
          
          <!-- Navigation -->
          <nav class="flex-1 px-2 mt-5 space-y-1">
            <template v-if="!navigationLoading">
              <Link
                v-for="item in navigation"
                :key="item.key"
                :href="route(item.route)"
                :class="[
                  isActive(item)
                    ? 'bg-indigo-50 border-indigo-600 text-indigo-600 shadow-sm'
                    : 'border-transparent text-gray-600 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-300',
                  sidebarCollapsed ? 'justify-center px-3 py-3 mx-2 rounded-lg border-l-0' : 'px-3 py-2.5',
                  'group flex items-center text-sm font-medium border-l-4 transition-all duration-200 hover:translate-x-1'
                ]"
                :title="sidebarCollapsed ? item.label : ''"
              >
                <component
                  v-if="getIconComponent(item.icon)"
                  :is="getIconComponent(item.icon)"
                  :class="[
                    isActive(item) ? 'text-indigo-500' : 'text-gray-400 group-hover:text-gray-500',
                    sidebarCollapsed ? 'h-6 w-6' : 'mr-3 h-5 w-5'
                  ]"
                />
                <span v-show="!sidebarCollapsed" class="transition-opacity duration-200">{{ item.label }}</span>
              </Link>
            </template>
            
            <!-- Loading state -->
            <div v-else class="space-y-2">
              <div v-for="n in 5" :key="n" :class="[
                sidebarCollapsed ? 'h-12 w-12 mx-auto rounded-lg' : 'h-10 rounded',
                'bg-gray-200 animate-pulse'
              ]"></div>
            </div>
          </nav>
        </div>
      </div>

      <!-- Main content area -->
      <div class="flex flex-col flex-1 overflow-hidden">
        <!-- Top header -->
        <header class="bg-white shadow-sm border-b border-gray-200">
          <div class="flex justify-between items-center px-4 sm:px-6 lg:px-8 h-16">
            <div class="flex items-center">
              <!-- Mobile menu button -->
              <button
                @click="sidebarOpen = true"
                class="md:hidden -ml-2 mr-2 inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100"
              >
                <span class="sr-only">Open sidebar</span>
                <Bars3Icon class="h-6 w-6" />
              </button>
              
              <!-- Desktop sidebar toggle when collapsed -->
              <button
                v-if="sidebarCollapsed"
                @click="toggleSidebar"
                class="hidden md:inline-flex -ml-2 mr-2 items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100"
                title="Expand sidebar"
              >
                <span class="sr-only">Expand sidebar</span>
                <Bars3Icon class="h-6 w-6" />
              </button>
              
              <!-- Page title from props -->
              <h1 class="text-lg font-semibold text-gray-900">
                {{ $page.props.title || 'Dashboard' }}
              </h1>
            </div>

            <!-- User menu -->
            <div class="flex items-center space-x-4">
              <!-- User dropdown -->
              <UserBadgeDropdown />
            </div>
          </div>
        </header>

        <!-- Page content -->
        <main class="flex-1 overflow-y-auto focus:outline-none">
          <div class="py-6 px-4 sm:px-6">
            <!-- Flash messages -->
            <div v-if="$page.props.flash?.success" class="mb-4 bg-green-50 border border-green-200 rounded-md p-4 max-w-7xl mx-auto">
              <div class="flex">
                <div class="flex-shrink-0">
                  <CheckCircleIcon class="h-5 w-5 text-green-400" />
                </div>
                <div class="ml-3">
                  <p class="text-sm font-medium text-green-800">
                    {{ $page.props.flash?.success }}
                  </p>
                </div>
              </div>
            </div>

            <div v-if="$page.props.flash?.error" class="mb-4 bg-red-50 border border-red-200 rounded-md p-4 max-w-7xl mx-auto">
              <div class="flex">
                <div class="flex-shrink-0">
                  <XCircleIcon class="h-5 w-5 text-red-400" />
                </div>
                <div class="ml-3">
                  <p class="text-sm font-medium text-red-800">
                    {{ $page.props.flash?.error }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Page content slot -->
            <slot />
          </div>
        </main>
      </div>
    </div>

    <!-- Mobile sidebar -->
    <TransitionRoot as="template" :show="sidebarOpen">
      <Dialog as="div" class="relative z-40 md:hidden" @close="sidebarOpen = false">
        <TransitionChild
          as="template"
          enter="transition-opacity ease-linear duration-300"
          enter-from="opacity-0"
          enter-to="opacity-100"
          leave="transition-opacity ease-linear duration-300"
          leave-from="opacity-100"
          leave-to="opacity-0"
        >
          <div class="fixed inset-0 bg-gray-600 bg-opacity-75" />
        </TransitionChild>

        <div class="fixed inset-0 z-40 flex">
          <TransitionChild
            as="template"
            enter="transition ease-in-out duration-300 transform"
            enter-from="-translate-x-full"
            enter-to="translate-x-0"
            leave="transition ease-in-out duration-300 transform"
            leave-from="translate-x-0"
            leave-to="-translate-x-full"
          >
            <DialogPanel class="relative flex flex-col flex-1 w-full max-w-xs bg-white">
              <TransitionChild
                as="template"
                enter="ease-in-out duration-300"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="ease-in-out duration-300"
                leave-from="opacity-100"
                leave-to="opacity-0"
              >
                <div class="absolute top-0 right-0 pt-2 -mr-12">
                  <button
                    type="button"
                    class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white"
                    @click="sidebarOpen = false"
                  >
                    <span class="sr-only">Close sidebar</span>
                    <XMarkIcon class="h-6 w-6 text-white" aria-hidden="true" />
                  </button>
                </div>
              </TransitionChild>
              <div class="flex-1 h-0 pt-5 pb-4 overflow-y-auto">
                <div class="flex-shrink-0 flex items-center px-4">
                  <h1 class="text-xl font-semibold text-gray-900">Service Vault</h1>
                </div>
                <nav class="mt-5 px-2 space-y-1">
                  <template v-if="!navigationLoading">
                    <Link
                      v-for="item in navigation"
                      :key="item.key"
                      :href="route(item.route)"
                      :class="[
                        isActive(item)
                          ? 'bg-indigo-50 border-indigo-600 text-indigo-600'
                          : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                        'group flex items-center px-3 py-2 text-sm font-medium border-l-4'
                      ]"
                      @click="sidebarOpen = false"
                    >
                      <component
                        v-if="getIconComponent(item.icon)"
                        :is="getIconComponent(item.icon)"
                        :class="[
                          isActive(item) ? 'text-indigo-500' : 'text-gray-400 group-hover:text-gray-500',
                          'mr-3 h-5 w-5'
                        ]"
                      />
                      {{ item.label }}
                    </Link>
                  </template>
                  
                  <!-- Loading state -->
                  <div v-else class="space-y-2">
                    <div v-for="n in 5" :key="n" class="h-10 bg-gray-200 animate-pulse rounded"></div>
                  </div>
                </nav>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </Dialog>
    </TransitionRoot>
    
    <!-- Timer Broadcast Overlay (only for users with timer permissions) -->
    <TimerBroadcastOverlay 
      v-if="userCanUseTimers"
      key="timer-overlay-persistent" 
    />
    
    <!-- Permissions Debug Overlay -->
    <PermissionsDebugOverlay key="permissions-debug-overlay-persistent" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import {
  Dialog,
  DialogPanel,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'
import {
  Bars3Icon,
  CheckCircleIcon,
  XCircleIcon,
  XMarkIcon,
  HomeIcon,
  ClockIcon,
  DocumentTextIcon,
  CogIcon,
  ChartBarIcon,
  TicketIcon,
  UserGroupIcon,
  ShieldCheckIcon,
  ChevronDoubleLeftIcon,
  ChevronDoubleRightIcon,
  CalendarIcon,
  BuildingOfficeIcon,
  CurrencyDollarIcon,
  ArrowDownTrayIcon,
  EnvelopeIcon,
} from '@heroicons/vue/24/outline'

// Components
import TimerBroadcastOverlay from '@/Components/Timer/TimerBroadcastOverlay.vue'
import PermissionsDebugOverlay from '@/Components/Debug/PermissionsDebugOverlay.vue'
import UserBadgeDropdown from '@/Components/UserBadgeDropdown.vue'

// Composables
import { useNavigationQuery } from '@/Composables/queries/useNavigationQuery.js'

const sidebarOpen = ref(false)
const sidebarCollapsed = ref(false)
const page = usePage()

// Check if user has timer permissions
const userCanUseTimers = computed(() => {
  const user = page.props.auth?.user
  if (!user) return false
  
  // Check for timer-related permissions
  // This is a simple check - in a real app you'd want to check specific permissions
  // For now, exclude account_user type from timer access
  return user.user_type !== 'account_user'
})

// Load sidebar preference from localStorage
onMounted(() => {
  const savedState = localStorage.getItem('sidebar-collapsed')
  if (savedState !== null) {
    sidebarCollapsed.value = JSON.parse(savedState)
  }
})

// Toggle sidebar and save preference
const toggleSidebar = () => {
  sidebarCollapsed.value = !sidebarCollapsed.value
  localStorage.setItem('sidebar-collapsed', JSON.stringify(sidebarCollapsed.value))
}

// Use navigation query composable for dynamic permission-based navigation
const { navigation, loading: navigationLoading, isActive } = useNavigationQuery()

// Icon mapping for dynamic navigation items
const iconMap = {
  // Primary mappings - exact matches from NavigationService
  'HomeIcon': HomeIcon,
  'DocumentTextIcon': DocumentTextIcon,
  'ClockIcon': ClockIcon,
  'CalendarIcon': CalendarIcon,
  'BuildingOfficeIcon': BuildingOfficeIcon,
  'UserGroupIcon': UserGroupIcon,
  'CurrencyDollarIcon': CurrencyDollarIcon,
  'ChartBarIcon': ChartBarIcon,
  'ShieldCheckIcon': ShieldCheckIcon,
  'CogIcon': CogIcon,
  'ArrowDownTrayIcon': ArrowDownTrayIcon,
  'EnvelopeIcon': EnvelopeIcon,
  
  // Legacy/alternative mappings for backward compatibility
  'home': HomeIcon,
  'dashboard': HomeIcon,
  'ticket': DocumentTextIcon,
  'tickets': DocumentTextIcon,
  'clock': ClockIcon,
  'timer': ClockIcon,
  'timers': ClockIcon,
  'document-text': DocumentTextIcon,
  'time-entries': CalendarIcon,
  'calendar': CalendarIcon,
  'building-office': BuildingOfficeIcon,
  'accounts': BuildingOfficeIcon,
  'user-group': UserGroupIcon,
  'users': UserGroupIcon,
  'currency-dollar': CurrencyDollarIcon,
  'billing': CurrencyDollarIcon,
  'chart-bar': ChartBarIcon,
  'reports': ChartBarIcon,
  'shield-check': ShieldCheckIcon,
  'roles': ShieldCheckIcon,
  'cog': CogIcon,
  'settings': CogIcon,
  'arrow-down-tray': ArrowDownTrayIcon,
  'import': ArrowDownTrayIcon,
  'envelope': EnvelopeIcon,
  'email': EnvelopeIcon,
  'mail': EnvelopeIcon,
}

// Get icon component based on icon string
const getIconComponent = (iconName) => {
  return iconMap[iconName] || HomeIcon
}
</script>