<template>
  <div class="p-8 space-y-8 bg-gray-50 min-h-screen">
    <div class="max-w-4xl mx-auto">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">Toast Component Examples</h1>
      <p class="text-gray-600 mb-8">Comprehensive examples of the glassy toast notification system.</p>

      <!-- Basic Toast Types -->
      <section class="bg-white rounded-lg p-6 shadow-sm mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Basic Toast Types</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <button
            @click="showSuccess"
            class="bg-green-500 hover:bg-green-600 text-white font-medium py-2 px-4 rounded-lg transition-colors"
          >
            Success Toast
          </button>
          <button
            @click="showError"
            class="bg-red-500 hover:bg-red-600 text-white font-medium py-2 px-4 rounded-lg transition-colors"
          >
            Error Toast
          </button>
          <button
            @click="showWarning"
            class="bg-yellow-500 hover:bg-yellow-600 text-white font-medium py-2 px-4 rounded-lg transition-colors"
          >
            Warning Toast
          </button>
          <button
            @click="showInfo"
            class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg transition-colors"
          >
            Info Toast
          </button>
        </div>
      </section>

      <!-- Advanced Features -->
      <section class="bg-white rounded-lg p-6 shadow-sm mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Advanced Features</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <button
            @click="showWithTitle"
            class="bg-indigo-500 hover:bg-indigo-600 text-white font-medium py-2 px-4 rounded-lg transition-colors"
          >
            With Title
          </button>
          <button
            @click="showWithAction"
            class="bg-purple-500 hover:bg-purple-600 text-white font-medium py-2 px-4 rounded-lg transition-colors"
          >
            With Action
          </button>
          <button
            @click="showPersistent"
            class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-lg transition-colors"
          >
            Persistent
          </button>
          <button
            @click="showLoading"
            class="bg-orange-500 hover:bg-orange-600 text-white font-medium py-2 px-4 rounded-lg transition-colors"
          >
            Loading
          </button>
          <button
            @click="showCustomPosition"
            class="bg-pink-500 hover:bg-pink-600 text-white font-medium py-2 px-4 rounded-lg transition-colors"
          >
            Different Position
          </button>
          <button
            @click="showLongDuration"
            class="bg-teal-500 hover:bg-teal-600 text-white font-medium py-2 px-4 rounded-lg transition-colors"
          >
            Long Duration
          </button>
        </div>
      </section>

      <!-- Promise Handling -->
      <section class="bg-white rounded-lg p-6 shadow-sm mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Promise Handling</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <button
            @click="simulateSuccess"
            class="bg-emerald-500 hover:bg-emerald-600 text-white font-medium py-2 px-4 rounded-lg transition-colors"
          >
            Simulate Success
          </button>
          <button
            @click="simulateError"
            class="bg-rose-500 hover:bg-rose-600 text-white font-medium py-2 px-4 rounded-lg transition-colors"
          >
            Simulate Error
          </button>
          <button
            @click="simulateNetwork"
            class="bg-amber-500 hover:bg-amber-600 text-white font-medium py-2 px-4 rounded-lg transition-colors"
          >
            Network Request
          </button>
        </div>
      </section>

      <!-- Batch Operations -->
      <section class="bg-white rounded-lg p-6 shadow-sm mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Batch Operations</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <button
            @click="showMultiple"
            class="bg-violet-500 hover:bg-violet-600 text-white font-medium py-2 px-4 rounded-lg transition-colors"
          >
            Show Multiple
          </button>
          <button
            @click="clearAll"
            class="bg-red-500 hover:bg-red-600 text-white font-medium py-2 px-4 rounded-lg transition-colors"
          >
            Clear All
          </button>
          <button
            @click="clearByType"
            class="bg-orange-500 hover:bg-orange-600 text-white font-medium py-2 px-4 rounded-lg transition-colors"
          >
            Clear Info Only
          </button>
          <button
            @click="showCrud"
            class="bg-cyan-500 hover:bg-cyan-600 text-white font-medium py-2 px-4 rounded-lg transition-colors"
          >
            CRUD Operations
          </button>
        </div>
      </section>

      <!-- Code Examples -->
      <section class="bg-white rounded-lg p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Usage Examples</h2>
        <div class="space-y-4">
          <div>
            <h3 class="font-medium text-gray-900 mb-2">Basic Usage</h3>
            <pre class="bg-gray-900 text-green-400 p-4 rounded-lg text-sm overflow-x-auto"><code>import { useToast } from '@/Composables/useToast'

const { success, error, warning, info } = useToast()

// Simple notifications
success('Operation completed successfully!')
error('Something went wrong')
warning('Please check your input')
info('New update available')</code></pre>
          </div>

          <div>
            <h3 class="font-medium text-gray-900 mb-2">Advanced Options</h3>
            <pre class="bg-gray-900 text-green-400 p-4 rounded-lg text-sm overflow-x-auto"><code>const { toast } = useToast()

toast.add({
  type: 'success',
  title: 'Success!',
  message: 'Your data has been saved',
  position: 'bottom-right',
  duration: 8000,
  actionText: 'View Details',
  onAction: () => navigateToDetails()
})</code></pre>
          </div>

          <div>
            <h3 class="font-medium text-gray-900 mb-2">Promise Handling</h3>
            <pre class="bg-gray-900 text-green-400 p-4 rounded-lg text-sm overflow-x-auto"><code>const { promise } = useToast()

await promise(
  fetch('/api/users'),
  {
    loading: 'Loading users...',
    success: 'Users loaded successfully!',
    error: (error) => `Failed to load users: ${error.message}`
  }
)</code></pre>
          </div>

          <div>
            <h3 class="font-medium text-gray-900 mb-2">API Error Handling</h3>
            <pre class="bg-gray-900 text-green-400 p-4 rounded-lg text-sm overflow-x-auto"><code>const { apiError, validationErrors } = useToast()

try {
  await api.post('/users', userData)
} catch (error) {
  if (error.response?.status === 422) {
    validationErrors(error.response.data.errors)
  } else {
    apiError(error, 'Failed to create user')
  }
}</code></pre>
          </div>
        </div>
      </section>
    </div>

    <!-- Current Toast Count -->
    <div class="fixed bottom-4 left-4 bg-white/90 backdrop-blur-sm rounded-lg px-3 py-2 text-sm text-gray-600 shadow-lg">
      Active Toasts: {{ toast.count }}
    </div>
  </div>
</template>

<script setup>
import { useToast } from '@/Composables/useToast'

const {
  toast,
  success,
  error,
  warning,
  info,
  loading,
  promise,
  clearAll: clearAllToasts,
  clearByType: clearToastsByType,
  saved,
  deleted,
  updated,
  apiError
} = useToast()

// Basic toast methods
const showSuccess = () => {
  success('Operation completed successfully!')
}

const showError = () => {
  error('Something went wrong. Please try again.')
}

const showWarning = () => {
  warning('Please review your input before continuing.')
}

const showInfo = () => {
  info('New features are now available.')
}

// Advanced features
const showWithTitle = () => {
  toast.add({
    type: 'success',
    title: 'Great job!',
    message: 'Your profile has been updated successfully.',
    duration: 6000
  })
}

const showWithAction = () => {
  toast.add({
    type: 'info',
    title: 'New Message',
    message: 'You have received a new message from John Doe.',
    actionText: 'View Message',
    onAction: () => {
      success('Navigating to messages...')
    },
    duration: 10000
  })
}

const showPersistent = () => {
  toast.add({
    type: 'warning',
    title: 'Important Notice',
    message: 'This notification will stay until you dismiss it.',
    persistent: true,
    actionText: 'Acknowledge',
    onAction: () => {
      success('Thank you for acknowledging!')
    }
  })
}

const showLoading = () => {
  const loadingId = loading('Processing your request...')
  
  // Simulate completion after 3 seconds
  setTimeout(() => {
    toast.remove(loadingId)
    success('Request processed successfully!')
  }, 3000)
}

const showCustomPosition = () => {
  toast.add({
    type: 'info',
    message: 'This toast appears in the bottom-left corner',
    position: 'bottom-left',
    duration: 5000
  })
}

const showLongDuration = () => {
  warning('This warning will be visible for 15 seconds', {
    duration: 15000
  })
}

// Promise handling examples
const simulateSuccess = async () => {
  await promise(
    new Promise(resolve => setTimeout(resolve, 2000)),
    {
      loading: 'Processing...',
      success: 'Operation completed!',
      error: 'Operation failed'
    }
  )
}

const simulateError = async () => {
  try {
    await promise(
      new Promise((_, reject) => setTimeout(() => reject(new Error('Network timeout')), 2000)),
      {
        loading: 'Attempting connection...',
        success: 'Connected successfully!',
        error: (error) => `Connection failed: ${error.message}`
      }
    )
  } catch (e) {
    // Error is already handled by toast
  }
}

const simulateNetwork = async () => {
  try {
    await promise(
      fetch('https://jsonplaceholder.typicode.com/posts/1').then(r => r.json()),
      {
        loading: 'Fetching data...',
        success: (data) => `Loaded post: "${data.title.substring(0, 30)}..."`,
        error: 'Failed to fetch data'
      }
    )
  } catch (e) {
    // Error handled by toast
  }
}

// Batch operations
const showMultiple = () => {
  success('First notification')
  setTimeout(() => info('Second notification'), 500)
  setTimeout(() => warning('Third notification'), 1000)
  setTimeout(() => error('Fourth notification'), 1500)
}

const clearAll = () => {
  clearAllToasts()
  info('All toasts cleared')
}

const clearByType = () => {
  clearToastsByType('info')
  success('All info toasts cleared')
}

const showCrud = () => {
  saved('User profile')
  setTimeout(() => updated('Account settings'), 1000)
  setTimeout(() => deleted('Old notification'), 2000)
}
</script>

<script>
export default {
  name: 'ToastExamples'
}
</script>