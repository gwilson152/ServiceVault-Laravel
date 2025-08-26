<template>
  <StackedDialog
    :show="show"
    @close="$emit('close')"
    size="sm"
    title="Send Test Email"
  >
    <form @submit.prevent="sendTest" class="space-y-4">
      <div>
        <label for="email" class="block text-sm font-medium text-gray-700">
          Recipient Email Address *
        </label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          required
          placeholder="test@example.com"
          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
        />
      </div>

      <div>
        <label for="name" class="block text-sm font-medium text-gray-700">
          Recipient Name (Optional)
        </label>
        <input
          id="name"
          v-model="form.name"
          type="text"
          placeholder="Test User"
          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
        />
      </div>

      <div class="bg-blue-50 rounded-md p-3">
        <div class="flex">
          <InformationCircleIcon class="h-5 w-5 text-blue-400 mt-0.5" />
          <div class="ml-3">
            <p class="text-sm text-blue-800">
              This will send a test email using the current template preview with sample data.
            </p>
          </div>
        </div>
      </div>
    </form>

    <template #actions>
      <button
        @click="$emit('close')"
        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
      >
        Cancel
      </button>
      
      <button
        @click="sendTest"
        :disabled="!form.email"
        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50"
      >
        Send Test Email
      </button>
    </template>
  </StackedDialog>
</template>

<script setup>
import { reactive } from 'vue'
import { InformationCircleIcon } from '@heroicons/vue/24/outline'
import StackedDialog from '@/Components/StackedDialog.vue'

const props = defineProps({
  show: Boolean
})

const emit = defineEmits(['close', 'send'])

// Form data
const form = reactive({
  email: '',
  name: ''
})

// Methods
function sendTest() {
  if (form.email) {
    emit('send', { ...form })
    // Reset form
    form.email = ''
    form.name = ''
  }
}
</script>