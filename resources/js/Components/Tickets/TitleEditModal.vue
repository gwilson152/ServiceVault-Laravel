<template>
  <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
      <!-- Modal header -->
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Edit Ticket Title</h3>
        <p class="text-sm text-gray-600 mt-1">{{ ticket.ticket_number }}</p>
      </div>

      <!-- Modal body -->
      <form @submit.prevent="submitForm" class="px-6 py-4 space-y-4">
        <!-- Current Title Display -->
        <div class="bg-gray-50 rounded-lg p-3">
          <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Current Title</label>
          <p class="text-sm text-gray-900">{{ ticket.title }}</p>
        </div>

        <!-- New Title -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            New Title <span class="text-red-500">*</span>
          </label>
          <input 
            v-model="form.title" 
            type="text" 
            required
            maxlength="255"
            placeholder="Enter new ticket title..."
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            @keydown.enter="submitForm"
          />
          <p class="text-xs text-gray-500 mt-1">{{ form.title.length }}/255 characters</p>
          <p v-if="errors.title" class="text-red-500 text-xs mt-1">{{ errors.title }}</p>
        </div>

        <!-- Change Reason (optional) -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Reason for Change (optional)
          </label>
          <textarea 
            v-model="form.change_reason" 
            rows="2"
            placeholder="Optional note about why the title is being changed..."
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          ></textarea>
        </div>

        <!-- Notification Options -->
        <div class="space-y-2">
          <div class="flex items-center">
            <input 
              v-model="form.notify_assigned_user" 
              type="checkbox" 
              id="notify_assigned"
              class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
            />
            <label for="notify_assigned" class="ml-2 text-sm text-gray-700">
              Notify assigned user of title change
            </label>
          </div>
          
          <div class="flex items-center">
            <input 
              v-model="form.notify_customer" 
              type="checkbox" 
              id="notify_customer"
              class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
            />
            <label for="notify_customer" class="ml-2 text-sm text-gray-700">
              Notify customer of title change
            </label>
          </div>
        </div>

        <!-- Preview Changes -->
        <div v-if="form.title !== ticket.title" class="bg-blue-50 border border-blue-200 rounded-md p-4">
          <h4 class="text-sm font-medium text-blue-900 mb-2">Preview Changes</h4>
          <div class="space-y-2 text-sm">
            <div>
              <span class="text-blue-700">Old:</span>
              <span class="text-gray-600 line-through">{{ ticket.title }}</span>
            </div>
            <div>
              <span class="text-blue-700">New:</span>
              <span class="font-medium text-blue-900">{{ form.title }}</span>
            </div>
          </div>
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
          :disabled="submitting || form.title === ticket.title || !form.title.trim()"
          class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ submitting ? 'Updating...' : 'Update Title' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

// Props
const props = defineProps({
  ticket: {
    type: Object,
    required: true
  }
})

// Emits
const emit = defineEmits(['updated', 'cancelled'])

// Reactive data
const submitting = ref(false)

// Form data
const form = ref({
  title: '',
  change_reason: '',
  notify_assigned_user: true,
  notify_customer: false
})

// Form errors
const errors = ref({})

// Methods
const validateForm = () => {
  errors.value = {}

  if (!form.value.title.trim()) {
    errors.value.title = 'Title is required'
  } else if (form.value.title.length > 255) {
    errors.value.title = 'Title must be 255 characters or less'
  } else if (form.value.title === props.ticket.title) {
    errors.value.title = 'New title must be different from current title'
  }

  return Object.keys(errors.value).length === 0
}

const submitForm = async () => {
  if (!validateForm()) return

  submitting.value = true

  try {
    const payload = {
      title: form.value.title.trim(),
      change_reason: form.value.change_reason.trim() || null,
      notify_assigned_user: form.value.notify_assigned_user,
      notify_customer: form.value.notify_customer
    }

    await axios.put(`/api/tickets/${props.ticket.id}`, payload)
    emit('updated')
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      console.error('Failed to update ticket title:', error)
      errors.value = { general: 'Failed to update ticket title. Please try again.' }
    }
  } finally {
    submitting.value = false
  }
}

// Lifecycle
onMounted(() => {
  form.value.title = props.ticket.title || ''
})
</script>