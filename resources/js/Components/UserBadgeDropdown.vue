<template>
  <Menu as="div" class="relative ml-3">
    <div>
      <MenuButton class="flex items-center max-w-xs text-sm bg-white rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 hover:ring-2 hover:ring-indigo-300 transition-all duration-200">
        <span class="sr-only">Open user menu</span>
        <div class="flex items-center space-x-3 px-3 py-2">
          <!-- User Avatar -->
          <div class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center shadow-sm">
            <span class="text-sm font-semibold text-white">
              {{ userInitials }}
            </span>
          </div>
          
          <!-- User Info (hidden on mobile) -->
          <div class="hidden sm:flex sm:flex-col sm:items-start">
            <p class="text-sm font-medium text-gray-900 truncate max-w-32">
              {{ user.name }}
            </p>
            <p class="text-xs text-gray-500 truncate max-w-32">
              {{ user.email }}
            </p>
          </div>
          
          <!-- Dropdown Arrow -->
          <ChevronDownIcon 
            class="w-4 h-4 text-gray-400 transition-transform duration-200" 
            :class="{ 'rotate-180': isOpen }"
          />
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
      <MenuItems class="absolute right-0 z-50 mt-2 w-56 origin-top-right bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none border border-gray-200">
        <div class="p-4 border-b border-gray-100">
          <!-- Mobile User Info -->
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center">
              <span class="text-sm font-semibold text-white">
                {{ userInitials }}
              </span>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 truncate">
                {{ user.name }}
              </p>
              <p class="text-sm text-gray-500 truncate">
                {{ user.email }}
              </p>
              <p class="text-xs text-indigo-600 font-medium mt-1" v-if="user.roles?.length">
                {{ user.roles[0].name }}
              </p>
            </div>
          </div>
        </div>
        
        <div class="py-1">
          <!-- Profile Link -->
          <MenuItem v-slot="{ active }">
            <Link
              :href="route('profile.edit')"
              :class="[
                active ? 'bg-gray-50 text-gray-900' : 'text-gray-700',
                'group flex items-center px-4 py-2 text-sm transition-colors duration-150'
              ]"
            >
              <UserIcon class="mr-3 h-4 w-4 text-gray-400 group-hover:text-gray-500" />
              Your Profile
            </Link>
          </MenuItem>

          <!-- Settings Link (if user has permission) -->
          <MenuItem v-slot="{ active }" v-if="canAccessSettings">
            <Link
              :href="route('settings.index')"
              :class="[
                active ? 'bg-gray-50 text-gray-900' : 'text-gray-700',
                'group flex items-center px-4 py-2 text-sm transition-colors duration-150'
              ]"
            >
              <CogIcon class="mr-3 h-4 w-4 text-gray-400 group-hover:text-gray-500" />
              Settings
            </Link>
          </MenuItem>

          <!-- Time Entries Link -->
          <MenuItem v-slot="{ active }" v-if="canAccessTimeEntries">
            <Link
              :href="route('time-and-addons.index')"
              :class="[
                active ? 'bg-gray-50 text-gray-900' : 'text-gray-700',
                'group flex items-center px-4 py-2 text-sm transition-colors duration-150'
              ]"
            >
              <ClockIcon class="mr-3 h-4 w-4 text-gray-400 group-hover:text-gray-500" />
              Time Entries
            </Link>
          </MenuItem>

          <!-- Dashboard Link -->
          <MenuItem v-slot="{ active }">
            <Link
              :href="route('dashboard')"
              :class="[
                active ? 'bg-gray-50 text-gray-900' : 'text-gray-700',
                'group flex items-center px-4 py-2 text-sm transition-colors duration-150'
              ]"
            >
              <HomeIcon class="mr-3 h-4 w-4 text-gray-400 group-hover:text-gray-500" />
              Dashboard
            </Link>
          </MenuItem>

          <div class="border-t border-gray-100 my-1"></div>

          <!-- Sign Out -->
          <MenuItem v-slot="{ active }">
            <button
              @click="handleSignOut"
              :class="[
                active ? 'bg-red-50 text-red-700' : 'text-gray-700',
                'group flex w-full items-center px-4 py-2 text-sm transition-colors duration-150'
              ]"
            >
              <ArrowRightOnRectangleIcon class="mr-3 h-4 w-4 text-gray-400 group-hover:text-red-500" />
              Sign out
            </button>
          </MenuItem>
        </div>
      </MenuItems>
    </transition>
  </Menu>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import {
  ChevronDownIcon,
  UserIcon,
  CogIcon,
  ClockIcon,
  HomeIcon,
  ArrowRightOnRectangleIcon,
} from '@heroicons/vue/24/outline'

const page = usePage()
const isOpen = ref(false)

// User data from Inertia props
const user = computed(() => page.props.auth.user)

// Generate user initials
const userInitials = computed(() => {
  const name = user.value?.name || 'U'
  const words = name.split(' ')
  if (words.length >= 2) {
    return (words[0].charAt(0) + words[words.length - 1].charAt(0)).toUpperCase()
  }
  return name.charAt(0).toUpperCase()
})

// Permission checks
const canAccessSettings = computed(() => {
  return user.value?.permissions?.includes('settings.view') || 
         user.value?.roles?.some(role => role.name === 'Admin' || role.name === 'Super Admin')
})

const canAccessTimeEntries = computed(() => {
  return user.value?.permissions?.includes('time-entries.view') ||
         user.value?.roles?.some(role => ['Admin', 'Manager', 'Employee'].includes(role.name))
})

// Handle sign out
const handleSignOut = () => {
  router.post(route('logout'))
}
</script>