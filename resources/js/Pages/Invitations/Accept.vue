<template>
  <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <div class="flex justify-center">
        <h2 class="text-3xl font-extrabold text-gray-900 text-center">
          Accept Invitation
        </h2>
      </div>
      <p class="mt-2 text-center text-sm text-gray-600">
        Complete your account setup to get started
      </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
        <!-- Loading State -->
        <div v-if="loading" class="text-center">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
          <p class="mt-2 text-sm text-gray-600">Loading invitation details...</p>
        </div>

        <!-- Invalid Invitation -->
        <div v-else-if="invitationError" class="text-center">
          <div class="rounded-md bg-red-50 p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">Invalid Invitation</h3>
                <p class="mt-2 text-sm text-red-700">{{ invitationError }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Already Accepted -->
        <div v-else-if="invitation && invitation.status === 'accepted'" class="text-center">
          <div class="rounded-md bg-yellow-50 p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">Already Accepted</h3>
                <p class="mt-2 text-sm text-yellow-700">This invitation has already been accepted.</p>
                <div class="mt-4">
                  <Link href="/login" class="text-yellow-800 font-medium hover:text-yellow-900">
                    Sign in to your account →
                  </Link>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Accept Invitation Form -->
        <div v-else-if="invitation">
          <div class="mb-6 p-4 bg-blue-50 rounded-lg">
            <h3 class="text-sm font-medium text-blue-900">You're invited to join</h3>
            <p class="mt-1 text-sm text-blue-700">{{ invitation.account?.name || 'Organization' }}</p>
            <p class="mt-1 text-xs text-blue-600">{{ invitation.email }}</p>
          </div>

          <form @submit.prevent="acceptInvitation" class="space-y-6">
            <!-- Name -->
            <div>
              <label for="name" class="block text-sm font-medium text-gray-700">
                Full Name
              </label>
              <div class="mt-1">
                <input
                  id="name"
                  name="name"
                  type="text"
                  required
                  v-model="form.name"
                  class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                  :class="{ 'border-red-300': errors.name }"
                />
                <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name[0] }}</p>
              </div>
            </div>

            <!-- Password -->
            <div>
              <label for="password" class="block text-sm font-medium text-gray-700">
                Password
              </label>
              <div class="mt-1">
                <input
                  id="password"
                  name="password"
                  type="password"
                  required
                  v-model="form.password"
                  class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                  :class="{ 'border-red-300': errors.password }"
                />
                <p v-if="errors.password" class="mt-1 text-sm text-red-600">{{ errors.password[0] }}</p>
              </div>
            </div>

            <!-- Confirm Password -->
            <div>
              <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                Confirm Password
              </label>
              <div class="mt-1">
                <input
                  id="password_confirmation"
                  name="password_confirmation"
                  type="password"
                  required
                  v-model="form.password_confirmation"
                  class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                  :class="{ 'border-red-300': errors.password_confirmation }"
                />
                <p v-if="errors.password_confirmation" class="mt-1 text-sm text-red-600">{{ errors.password_confirmation[0] }}</p>
              </div>
            </div>

            <!-- Timezone -->
            <div>
              <label for="timezone" class="block text-sm font-medium text-gray-700">
                Timezone
              </label>
              <div class="mt-1">
                <select
                  id="timezone"
                  name="timezone"
                  v-model="form.timezone"
                  class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                  :class="{ 'border-red-300': errors.timezone }"
                >
                  <option value="">Select your timezone</option>
                  <optgroup label="North America">
                    <option value="America/New_York">Eastern Time</option>
                    <option value="America/Chicago">Central Time</option>
                    <option value="America/Denver">Mountain Time</option>
                    <option value="America/Los_Angeles">Pacific Time</option>
                  </optgroup>
                  <optgroup label="Europe">
                    <option value="Europe/London">London</option>
                    <option value="Europe/Paris">Paris</option>
                    <option value="Europe/Berlin">Berlin</option>
                    <option value="Europe/Rome">Rome</option>
                  </optgroup>
                  <optgroup label="Asia Pacific">
                    <option value="Asia/Tokyo">Tokyo</option>
                    <option value="Asia/Shanghai">Shanghai</option>
                    <option value="Australia/Sydney">Sydney</option>
                    <option value="Asia/Kolkata">Mumbai</option>
                  </optgroup>
                </select>
                <p v-if="errors.timezone" class="mt-1 text-sm text-red-600">{{ errors.timezone[0] }}</p>
              </div>
            </div>

            <!-- Submit Button -->
            <div>
              <button
                type="submit"
                :disabled="processing"
                class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span v-if="processing" class="absolute left-0 inset-y-0 flex items-center pl-3">
                  <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                </span>
                {{ processing ? 'Creating Account...' : 'Accept Invitation & Create Account' }}
              </button>
            </div>

            <!-- General Error -->
            <div v-if="generalError" class="rounded-md bg-red-50 p-4">
              <div class="flex">
                <div class="flex-shrink-0">
                  <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                  </svg>
                </div>
                <div class="ml-3">
                  <h3 class="text-sm font-medium text-red-800">Error</h3>
                  <p class="mt-2 text-sm text-red-700">{{ generalError }}</p>
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import axios from 'axios'

// Props
const props = defineProps({
  token: {
    type: String,
    required: true
  }
})

// State
const invitation = ref(null)
const loading = ref(true)
const processing = ref(false)
const invitationError = ref(null)
const generalError = ref(null)
const errors = ref({})

const form = ref({
  name: '',
  password: '',
  password_confirmation: '',
  timezone: ''
})

// Methods
const loadInvitation = async () => {
  loading.value = true
  invitationError.value = null
  
  try {
    const response = await axios.get(`/invitations/api/${props.token}`)
    invitation.value = response.data.data
    
    // Auto-detect timezone if not set
    if (!form.value.timezone) {
      try {
        form.value.timezone = Intl.DateTimeFormat().resolvedOptions().timeZone
      } catch (e) {
        // Fallback to UTC if detection fails
        form.value.timezone = 'UTC'
      }
    }
  } catch (err) {
    console.error('Failed to load invitation:', err)
    if (err.response?.status === 404) {
      invitationError.value = 'This invitation link is invalid or has expired.'
    } else if (err.response?.status === 410) {
      invitationError.value = 'This invitation link has expired.'
    } else {
      invitationError.value = err.response?.data?.message || 'Failed to load invitation details.'
    }
  } finally {
    loading.value = false
  }
}

const acceptInvitation = async () => {
  if (processing.value) return
  
  processing.value = true
  generalError.value = null
  errors.value = {}
  
  try {
    const response = await axios.post(`/invitations/api/${props.token}/accept`, {
      name: form.value.name,
      password: form.value.password,
      password_confirmation: form.value.password_confirmation,
      timezone: form.value.timezone
    })
    
    // Success - redirect to login or dashboard
    window.location.href = response.data.redirect_url || '/dashboard'
  } catch (err) {
    console.error('Failed to accept invitation:', err)
    
    if (err.response?.status === 422) {
      // Validation errors
      errors.value = err.response.data.errors || {}
    } else if (err.response?.status === 410) {
      invitationError.value = 'This invitation has expired.'
    } else {
      generalError.value = err.response?.data?.message || 'Failed to accept invitation. Please try again.'
    }
  } finally {
    processing.value = false
  }
}

// Lifecycle
onMounted(() => {
  loadInvitation()
})
</script>