<template>
  <div
    v-if="show"
    class="fixed top-4 right-4 z-50 w-96 bg-white rounded-lg shadow-lg border border-gray-200 overflow-hidden transform transition-all duration-300"
    :class="show ? 'translate-x-0' : 'translate-x-full'"
  >
    <div class="p-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <CheckCircleIcon class="h-6 w-6 text-green-400" />
        </div>
        <div class="ml-3 flex-1">
          <p class="text-sm font-medium text-gray-900">
            {{ title }}
          </p>
          <p v-if="message" class="mt-1 text-sm text-gray-500">
            {{ message }}
          </p>
        </div>
        <div class="ml-4 flex-shrink-0 flex">
          <button
            @click="dismiss"
            class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none"
          >
            <XMarkIcon class="h-5 w-5" />
          </button>
        </div>
      </div>
    </div>
    <div class="bg-green-50 px-4 py-2 border-t border-green-200">
      <div class="flex justify-end">
        <button
          v-if="actionText && actionHandler"
          @click="handleAction"
          class="text-sm font-medium text-green-600 hover:text-green-500"
        >
          {{ actionText }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { CheckCircleIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    required: true
  },
  message: {
    type: String,
    default: ''
  },
  actionText: {
    type: String,
    default: ''
  },
  actionHandler: {
    type: Function,
    default: null
  },
  autoDismiss: {
    type: Boolean,
    default: true
  },
  duration: {
    type: Number,
    default: 5000
  }
})

const emit = defineEmits(['dismiss'])

let dismissTimer = null

const dismiss = () => {
  if (dismissTimer) {
    clearTimeout(dismissTimer)
    dismissTimer = null
  }
  emit('dismiss')
}

const handleAction = () => {
  if (props.actionHandler) {
    props.actionHandler()
  }
  dismiss()
}

watch(() => props.show, (show) => {
  if (show && props.autoDismiss) {
    dismissTimer = setTimeout(() => {
      dismiss()
    }, props.duration)
  } else if (!show && dismissTimer) {
    clearTimeout(dismissTimer)
    dismissTimer = null
  }
})

onMounted(() => {
  if (props.show && props.autoDismiss) {
    dismissTimer = setTimeout(() => {
      dismiss()
    }, props.duration)
  }
})
</script>