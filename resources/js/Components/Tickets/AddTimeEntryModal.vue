<template>
  <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
      <!-- Modal header -->
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Add Time Entry</h3>
      </div>

      <!-- Modal body -->
      <form @submit.prevent="submitForm" class="px-6 py-4 space-y-4">
        <!-- User Selection (if can assign to others) -->
        <div v-if="canAssignToOthers">
          <label class="block text-sm font-medium text-gray-700 mb-1">
            User <span class="text-red-500">*</span>
          </label>
          <select 
            v-model="form.user_id" 
            required
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">Select User</option>
            <option v-for="user in availableUsers" :key="user.id" :value="user.id">
              {{ user.name }}
            </option>
          </select>
          <p v-if="errors.user_id" class="text-red-500 text-xs mt-1">{{ errors.user_id }}</p>
        </div>

        <!-- Date -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Date <span class="text-red-500">*</span>
          </label>
          <input 
            v-model="form.date" 
            type="date" 
            required
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
          <p v-if="errors.date" class="text-red-500 text-xs mt-1">{{ errors.date }}</p>
        </div>

        <!-- Start Time -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Start Time <span class="text-red-500">*</span>
          </label>
          <input 
            v-model="form.start_time" 
            type="time" 
            required
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
          <p v-if="errors.start_time" class="text-red-500 text-xs mt-1">{{ errors.start_time }}</p>
        </div>

        <!-- Duration -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Duration <span class="text-red-500">*</span>
          </label>
          <div class="grid grid-cols-2 gap-2">
            <div>
              <input 
                v-model.number="form.hours" 
                type="number" 
                min="0" 
                max="23"
                placeholder="Hours"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
            <div>
              <input 
                v-model.number="form.minutes" 
                type="number" 
                min="0" 
                max="59"
                placeholder="Minutes"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
          </div>
          <p v-if="errors.duration" class="text-red-500 text-xs mt-1">{{ errors.duration }}</p>
        </div>

        <!-- Description -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Description <span class="text-red-500">*</span>
          </label>
          <textarea 
            v-model="form.description" 
            rows="3"
            required
            placeholder="Describe the work performed..."
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          ></textarea>
          <p v-if="errors.description" class="text-red-500 text-xs mt-1">{{ errors.description }}</p>
        </div>

        <!-- Billable Checkbox -->
        <div class="flex items-center">
          <input 
            v-model="form.billable" 
            type="checkbox" 
            id="billable"
            class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
          />
          <label for="billable" class="ml-2 text-sm text-gray-700">
            This time is billable to the client
          </label>
        </div>

      </form>

      <!-- Modal footer -->
      <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-end space-x-3">
        <button
          @click="$emit('cancelled')"
          type="button"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          Cancel
        </button>
        <button
          @click="submitForm"
          :disabled="submitting"
          class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ submitting ? 'Adding...' : 'Add Time Entry' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

// Props
const props = defineProps({
  ticket: {
    type: Object,
    required: true
  }
})

// Emits
const emit = defineEmits(['saved', 'cancelled'])

// Reactive data
const submitting = ref(false)
const availableUsers = ref([])

// Form data
const form = ref({
  user_id: window.auth?.user?.id || '',
  date: new Date().toISOString().split('T')[0],
  start_time: '',
  hours: 0,
  minutes: 0,
  description: '',
  billable: true
})

// Form errors
const errors = ref({})

// Computed properties
const canAssignToOthers = computed(() => {
  // TODO: Implement proper permission checking
  return true
})

const totalDuration = computed(() => {
  return (form.value.hours * 3600) + (form.value.minutes * 60)
})

// Methods
const loadAvailableUsers = async () => {
  try {
    const response = await axios.get('/api/users/assignable')
    availableUsers.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load available users:', error)
    availableUsers.value = []
  }
}

const validateForm = () => {
  errors.value = {}

  if (!form.value.user_id && canAssignToOthers.value) {
    errors.value.user_id = 'User is required'
  }

  if (!form.value.date) {
    errors.value.date = 'Date is required'
  }

  if (!form.value.start_time) {
    errors.value.start_time = 'Start time is required'
  }

  if (totalDuration.value <= 0) {
    errors.value.duration = 'Duration must be greater than 0'
  }

  if (!form.value.description.trim()) {
    errors.value.description = 'Description is required'
  }

  return Object.keys(errors.value).length === 0
}

const submitForm = async () => {
  if (!validateForm()) return

  submitting.value = true

  try {
    const payload = {
      ticket_id: props.ticket.id,
      user_id: form.value.user_id || window.auth?.user?.id,
      started_at: `${form.value.date} ${form.value.start_time}:00`,
      duration: totalDuration.value,
      description: form.value.description.trim(),
      billable: form.value.billable
    }

    await axios.post('/api/time-entries', payload)
    emit('saved')
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      console.error('Failed to create time entry:', error)
      errors.value = { general: 'Failed to create time entry. Please try again.' }
    }
  } finally {
    submitting.value = false
  }
}

// Lifecycle
onMounted(() => {
  if (canAssignToOthers.value) {
    loadAvailableUsers()
  }

  // Set default start time to current time
  const now = new Date()
  form.value.start_time = now.toTimeString().slice(0, 5)
})
</script>