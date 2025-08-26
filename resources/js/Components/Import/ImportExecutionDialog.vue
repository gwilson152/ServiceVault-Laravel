<template>
  <StackedDialog 
    :show="show" 
    @close="handleClose"
    title="Starting Import"
    max-width="lg"
    :closable="!isExecuting"
  >
    <div class="text-center py-8">
      <div class="max-w-md mx-auto">
        <!-- Status Icon -->
        <div class="mx-auto flex items-center justify-center h-16 w-16 mb-6">
          <div v-if="isExecuting" class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
          <CheckCircleIcon v-else-if="isSuccess" class="h-12 w-12 text-green-500" />
          <XCircleIcon v-else-if="isError" class="h-12 w-12 text-red-500" />
        </div>

        <!-- Title -->
        <h3 class="text-lg font-medium text-gray-900 mb-4">
          <span v-if="isExecuting">Starting Import Job</span>
          <span v-else-if="isSuccess">Import Started Successfully</span>
          <span v-else-if="isError">Import Failed to Start</span>
        </h3>
        
        <!-- Progress Bar (only when executing) -->
        <div v-if="isExecuting" class="w-full bg-gray-200 rounded-full h-3 mb-6">
          <div 
            class="bg-indigo-600 h-3 rounded-full transition-all duration-500 ease-out"
            :style="{ width: `${progress}%` }"
          ></div>
        </div>
        
        <!-- Progress Message -->
        <div class="space-y-2 mb-6">
          <p class="text-sm text-gray-600">{{ message }}</p>
          <p v-if="isExecuting" class="text-xs text-gray-500">{{ progress }}% complete</p>
          <p v-if="estimatedRecords && isExecuting" class="text-xs text-gray-500">
            {{ formatNumber(estimatedRecords) }} records ready to import
          </p>
        </div>

        <!-- Error Details -->
        <div v-if="isError && errorDetails" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg text-left">
          <h4 class="text-sm font-medium text-red-800 mb-2">Error Details:</h4>
          <p class="text-sm text-red-700">{{ errorDetails }}</p>
        </div>

        <!-- Success Details -->
        <div v-if="isSuccess" class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg text-left">
          <h4 class="text-sm font-medium text-green-800 mb-2">Import Job Created:</h4>
          <div class="text-sm text-green-700 space-y-1">
            <p><strong>Job ID:</strong> {{ jobId }}</p>
            <p v-if="estimatedRecords"><strong>Records:</strong> {{ formatNumber(estimatedRecords) }}</p>
            <p><strong>Status:</strong> Processing started</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <template #actions>
      <div class="flex justify-end space-x-3">
        <button
          v-if="!isExecuting"
          type="button"
          @click="handleClose"
          class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <span v-if="isSuccess">View Import Jobs</span>
          <span v-else>Close</span>
        </button>
      </div>
    </template>
  </StackedDialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import StackedDialog from '@/Components/StackedDialog.vue'
import { CheckCircleIcon, XCircleIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  isExecuting: {
    type: Boolean,
    default: false
  },
  progress: {
    type: Number,
    default: 0
  },
  message: {
    type: String,
    default: ''
  },
  isSuccess: {
    type: Boolean,
    default: false
  },
  isError: {
    type: Boolean,
    default: false
  },
  errorDetails: {
    type: String,
    default: ''
  },
  jobId: {
    type: String,
    default: ''
  },
  estimatedRecords: {
    type: Number,
    default: 0
  }
})

const emit = defineEmits(['close', 'view-jobs'])

const formatNumber = (num) => {
  if (!num) return '0'
  return new Intl.NumberFormat().format(num)
}

const handleClose = () => {
  if (props.isSuccess) {
    emit('view-jobs')
  } else {
    emit('close')
  }
}
</script>