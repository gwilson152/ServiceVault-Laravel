<template>
  <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
      <!-- Modal header -->
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Assign Ticket</h3>
        <p class="text-sm text-gray-600 mt-1">{{ ticket.title }} ({{ ticket.ticket_number }})</p>
      </div>

      <!-- Modal body -->
      <form @submit.prevent="submitForm" class="px-6 py-4 space-y-4">
        <!-- Current Assignment Display -->
        <div class="bg-gray-50 rounded-lg p-3">
          <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Current Assignment</label>
          <div v-if="ticket.assigned_user" class="flex items-center space-x-2">
            <div class="w-6 h-6 bg-gray-300 rounded-full flex items-center justify-center">
              <span class="text-xs font-medium text-gray-700">
                {{ ticket.assigned_user.name?.charAt(0)?.toUpperCase() || '?' }}
              </span>
            </div>
            <span class="text-sm text-gray-900">{{ ticket.assigned_user.name }}</span>
            <span class="text-xs text-gray-500">({{ ticket.assigned_user.email }})</span>
          </div>
          <div v-else class="text-sm text-gray-500">
            Unassigned
          </div>
        </div>

        <!-- Assignment Type -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Assignment Action</label>
          <div class="space-y-2">
            <label class="flex items-center">
              <input 
                v-model="assignmentType" 
                type="radio" 
                value="assign"
                class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500"
              />
              <span class="ml-2 text-sm text-gray-700">Assign to user</span>
            </label>
            <label class="flex items-center">
              <input 
                v-model="assignmentType" 
                type="radio" 
                value="unassign"
                class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500"
              />
              <span class="ml-2 text-sm text-gray-700">Remove assignment (unassign)</span>
            </label>
          </div>
        </div>

        <!-- User Selection -->
        <div v-if="assignmentType === 'assign'">
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Assign To <span class="text-red-500">*</span>
          </label>
          <select 
            v-model="form.agent_id" 
            required
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">Select user...</option>
            <optgroup v-if="teamMembers.length > 0" label="Team Members">
              <option 
                v-for="user in teamMembers" 
                :key="user.id" 
                :value="user.id"
                :disabled="user.id === ticket.assigned_user?.id"
              >
                {{ user.name }}{{ user.id === ticket.assigned_user?.id ? ' (Current)' : '' }}
                <span v-if="user.current_workload" class="text-xs text-gray-500">
                  ({{ user.current_workload }} active tickets)
                </span>
              </option>
            </optgroup>
            <optgroup v-if="managers.length > 0" label="Managers">
              <option 
                v-for="user in managers" 
                :key="user.id" 
                :value="user.id"
                :disabled="user.id === ticket.assigned_user?.id"
              >
                {{ user.name }}{{ user.id === ticket.assigned_user?.id ? ' (Current)' : '' }}
                <span v-if="user.current_workload" class="text-xs text-gray-500">
                  ({{ user.current_workload }} active tickets)
                </span>
              </option>
            </optgroup>
          </select>
          <p v-if="errors.agent_id" class="text-red-500 text-xs mt-1">{{ errors.agent_id }}</p>
        </div>

        <!-- Selected User Preview -->
        <div v-if="selectedUser" class="bg-blue-50 border border-blue-200 rounded-md p-4">
          <h4 class="text-sm font-medium text-blue-900 mb-2">Assignment Preview</h4>
          <div class="flex items-center space-x-3">
            <div class="w-8 h-8 bg-blue-300 rounded-full flex items-center justify-center">
              <span class="text-xs font-medium text-blue-700">
                {{ selectedUser.name?.charAt(0)?.toUpperCase() || '?' }}
              </span>
            </div>
            <div>
              <div class="text-sm font-medium text-blue-900">{{ selectedUser.name }}</div>
              <div class="text-xs text-blue-600">{{ selectedUser.email }}</div>
              <div v-if="selectedUser.current_workload" class="text-xs text-blue-600">
                Current workload: {{ selectedUser.current_workload }} active tickets
              </div>
            </div>
          </div>
        </div>

        <!-- Assignment Reason -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Assignment Reason
          </label>
          <textarea 
            v-model="form.assignment_reason" 
            rows="2"
            placeholder="Optional reason for this assignment change..."
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          ></textarea>
        </div>

        <!-- Priority Adjustment -->
        <div v-if="assignmentType === 'assign' && selectedUser">
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Adjust Priority (optional)
          </label>
          <select 
            v-model="form.priority" 
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">Keep current priority</option>
            <option value="low">Low</option>
            <option value="normal">Normal</option>
            <option value="high">High</option>
            <option value="urgent">Urgent</option>
          </select>
        </div>

        <!-- Notification Options -->
        <div class="space-y-2">
          <div v-if="assignmentType === 'assign'" class="flex items-center">
            <input 
              v-model="form.notify_new_assignee" 
              type="checkbox" 
              id="notify_new"
              class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
            />
            <label for="notify_new" class="ml-2 text-sm text-gray-700">
              Notify new assignee
            </label>
          </div>
          
          <div v-if="ticket.assigned_user && assignmentType === 'assign'" class="flex items-center">
            <input 
              v-model="form.notify_previous_assignee" 
              type="checkbox" 
              id="notify_previous"
              class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
            />
            <label for="notify_previous" class="ml-2 text-sm text-gray-700">
              Notify previous assignee ({{ ticket.assigned_user.name }})
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
              Notify customer of assignment change
            </label>
          </div>
        </div>

        <!-- Workload Warning -->
        <div v-if="workloadWarning" class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
          <div class="flex">
            <svg class="w-5 h-5 text-yellow-400 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div class="text-sm">
              <p class="text-yellow-700 font-medium">High Workload</p>
              <p class="text-yellow-600 mt-1">{{ selectedUser?.name }} currently has {{ selectedUser?.current_workload }} active tickets.</p>
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
          :disabled="submitting || (assignmentType === 'assign' && !form.agent_id)"
          class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ submitting ? 'Updating...' : (assignmentType === 'assign' ? 'Assign Ticket' : 'Unassign Ticket') }}
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
const emit = defineEmits(['updated', 'cancelled'])

// Reactive data
const submitting = ref(false)
const assignmentType = ref('assign')
const availableUsers = ref([])

// Form data
const form = ref({
  agent_id: '',
  assignment_reason: '',
  priority: '',
  notify_new_assignee: true,
  notify_previous_assignee: true,
  notify_customer: false
})

// Form errors
const errors = ref({})

// Computed properties
const teamMembers = computed(() => {
  return availableUsers.value.filter(user => user.role === 'team_member' || user.role === 'developer')
})

const managers = computed(() => {
  return availableUsers.value.filter(user => user.role === 'manager' || user.role === 'supervisor')
})

const selectedUser = computed(() => {
  return availableUsers.value.find(user => user.id == form.value.agent_id)
})

const workloadWarning = computed(() => {
  return selectedUser.value && selectedUser.value.current_workload > 10
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

  if (assignmentType.value === 'assign') {
    if (!form.value.agent_id) {
      errors.value.agent_id = 'Please select a user to assign'
    }
    
    if (form.value.agent_id == props.ticket.agent_id) {
      errors.value.agent_id = 'This user is already assigned to this ticket'
    }
  }

  return Object.keys(errors.value).length === 0
}

const submitForm = async () => {
  if (!validateForm()) return

  submitting.value = true

  try {
    const payload = {
      agent_id: assignmentType.value === 'assign' ? form.value.agent_id : null,
      assignment_reason: form.value.assignment_reason.trim() || null,
      notify_new_assignee: form.value.notify_new_assignee,
      notify_previous_assignee: form.value.notify_previous_assignee,
      notify_customer: form.value.notify_customer
    }

    // Include priority if specified
    if (form.value.priority) {
      payload.priority = form.value.priority
    }

    await axios.put(`/api/tickets/${props.ticket.id}/assignment`, payload)
    emit('updated')
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      console.error('Failed to update ticket assignment:', error)
      errors.value = { general: 'Failed to update ticket assignment. Please try again.' }
    }
  } finally {
    submitting.value = false
  }
}

// Lifecycle
onMounted(() => {
  loadAvailableUsers()
  
  // Set initial assignment type based on current state
  if (props.ticket.assigned_user) {
    assignmentType.value = 'assign'
  }
})
</script>