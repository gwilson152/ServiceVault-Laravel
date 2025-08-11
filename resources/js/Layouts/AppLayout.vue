<template>
  <div class="min-h-screen bg-gray-50">
    <!-- App-wide Timer Broadcast Overlay -->
    <TimerBroadcastOverlay />
    
    <!-- Main Content -->
    <div class="flex h-screen overflow-hidden">
      <!-- Sidebar -->
      <div class="hidden md:flex md:w-64 md:flex-col">
        <div class="flex flex-col flex-grow pt-5 overflow-y-auto bg-white border-r border-gray-200">
          <!-- Logo -->
          <div class="flex items-center flex-shrink-0 px-4">
            <h1 class="text-xl font-semibold text-gray-900">Service Vault</h1>
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
                    ? 'bg-indigo-50 border-indigo-600 text-indigo-600'
                    : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                  'group flex items-center px-3 py-2 text-sm font-medium border-l-4'
                ]"
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
              
              <!-- Page title -->
              <h1 class="text-lg font-semibold text-gray-900">
                {{ $page.props.title || 'Dashboard' }}
              </h1>
            </div>

            <!-- User menu -->
            <div class="flex items-center space-x-4">
              <!-- Timer status in header -->
              <TimerStatus />
              
              <!-- User dropdown -->
              <Menu as="div" class="relative ml-3">
                <div>
                  <MenuButton class="flex items-center max-w-xs text-sm bg-white rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <span class="sr-only">Open user menu</span>
                    <div class="w-8 h-8 bg-indigo-500 rounded-full flex items-center justify-center">
                      <span class="text-sm font-medium text-white">
                        {{ $page.props.auth.user.name.charAt(0) }}
                      </span>
                    </div>
                  </MenuButton>
                </div>
                <transition
                  enter-active-class="transition ease-out duration-100"
                  enter-from-class="transform opacity-0 scale-95"
                  enter-to-class="transform opacity-100 scale-100"
                  leave-active-class="transition ease-in duration-75"
                  leave-from-class="transform opacity-100 scale-100"
                  leave-to-class="transform opacity-0 scale-95"
                >
                  <MenuItems class="absolute right-0 z-10 mt-2 w-48 origin-top-right bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                    <div class="py-1">
                      <MenuItem v-slot="{ active }">
                        <Link
                          :href="route('profile')"
                          :class="[active ? 'bg-gray-100' : '', 'block px-4 py-2 text-sm text-gray-700']"
                        >
                          Your Profile
                        </Link>
                      </MenuItem>
                      <MenuItem v-slot="{ active }">
                        <Link
                          :href="route('logout')"
                          method="post"
                          :class="[active ? 'bg-gray-100' : '', 'block px-4 py-2 text-sm text-gray-700']"
                        >
                          Sign out
                        </Link>
                      </MenuItem>
                    </div>
                  </MenuItems>
                </transition>
              </Menu>
            </div>
          </div>
        </header>

        <!-- Page content -->
        <main class="flex-1 overflow-y-auto focus:outline-none">
          <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
              <!-- Flash messages -->
              <div v-if="$page.props.flash?.success" class="mb-4 bg-green-50 border border-green-200 rounded-md p-4">
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

              <div v-if="$page.props.flash?.error" class="mb-4 bg-red-50 border border-red-200 rounded-md p-4">
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
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import {
  Dialog,
  DialogPanel,
  Menu,
  MenuButton,
  MenuItem,
  MenuItems,
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
} from '@heroicons/vue/24/outline'

// Components
import TimerBroadcastOverlay from '@/Components/Timer/TimerBroadcastOverlay.vue'
import TimerStatus from '@/Components/Timer/TimerStatus.vue'

// Composables
import { useNavigation } from '@/Composables/useNavigation.js'

const sidebarOpen = ref(false)
const page = usePage()

// Use navigation composable for dynamic permission-based navigation
const { navigation, loading: navigationLoading, isActive } = useNavigation()

// Icon mapping for dynamic navigation items
const iconMap = {
  'home': HomeIcon,
  'dashboard': HomeIcon,
  'ticket': TicketIcon,
  'tickets': TicketIcon,
  'clock': ClockIcon,
  'timer': ClockIcon,
  'timers': ClockIcon,
  'document-text': DocumentTextIcon,
  'time-entries': DocumentTextIcon,
  'chart-bar': ChartBarIcon,
  'reports': ChartBarIcon,
  'cog': CogIcon,
  'settings': CogIcon,
  'user-group': UserGroupIcon,
  'users': UserGroupIcon,
  'shield-check': ShieldCheckIcon,
  'roles': ShieldCheckIcon,
}

// Get icon component based on icon string
const getIconComponent = (iconName) => {
  return iconMap[iconName] || HomeIcon
}
</script>