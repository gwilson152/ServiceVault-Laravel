<template>
  <div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200">
      <h3 class="text-lg font-medium text-gray-900">Payments</h3>
    </div>
    <div class="p-6">
      <div class="text-center text-gray-500">
        <CreditCardIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">No payments</h3>
        <p class="mt-1 text-sm text-gray-500">Get started by recording a payment.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { CreditCardIcon } from '@heroicons/vue/24/outline'

defineProps({
  payments: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  }
})

defineEmits(['refresh'])
</script>