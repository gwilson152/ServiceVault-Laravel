<template>
  <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0" 
       style="background: linear-gradient(135deg, #10b981 0%, #059669 100%)">
    <div class="w-full max-w-2xl mt-6 px-8 py-12 bg-white shadow-2xl overflow-hidden rounded-xl text-center">
      <div class="flex flex-col items-center mb-8">
        <div class="w-20 h-20 bg-gradient-to-r from-green-600 to-emerald-600 rounded-xl flex items-center justify-center text-white font-bold text-3xl shadow-lg mb-6">
          ✓
        </div>
        <h1 class="text-4xl font-bold text-gray-900 mb-4">Setup Already Complete</h1>
        <p class="text-lg text-gray-600">Service Vault has already been set up and configured.</p>
      </div>

      <div class="bg-green-50 p-6 rounded-lg border border-green-200 mb-8">
        <h2 class="text-lg font-semibold text-green-800 mb-2">What's Next?</h2>
        <p class="text-green-700">
          Your Service Vault installation is ready to use. You can sign in with your administrator account
          to start managing your time tracking and invoicing system.
        </p>
      </div>

      <div class="flex justify-center space-x-4">
        <Link
          :href="route('login')"
          class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200"
        >
          Go to Login
        </Link>
        
        <Link
          :href="route('dashboard')"
          class="inline-flex items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200"
        >
          Go to Dashboard
        </Link>
      </div>

      <div class="mt-8 text-sm text-gray-500">
        <p>Need to reconfigure your installation?</p>
        <p class="mt-1">Contact your system administrator or check the documentation.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
</script>