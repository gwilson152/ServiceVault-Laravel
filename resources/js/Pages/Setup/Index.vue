<template>
  <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 pb-12" 
       style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%)">
    <div class="w-full max-w-4xl mt-6 mb-12 px-6 py-8 bg-white shadow-2xl overflow-hidden rounded-xl">
      <div class="flex flex-col items-center mb-8">
        <div class="w-20 h-20 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-xl flex items-center justify-center text-white font-bold text-3xl shadow-lg mb-4">
          SV
        </div>
        <h1 class="text-4xl font-bold text-gray-900 mb-2">Welcome to Service Vault</h1>
        <p class="text-lg text-gray-600 text-center">Let's set up your time management and invoicing system</p>
      </div>

      <form @submit.prevent="submit" class="space-y-8">
        <!-- Company Information -->
        <div class="bg-gray-50 p-6 rounded-lg">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">Company Information</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <InputLabel for="company_name" value="Company Name" />
              <TextInput
                id="company_name"
                type="text"
                class="mt-1 block w-full"
                v-model="form.company_name"
                required
                autofocus
              />
              <InputError class="mt-2" :message="form.errors.company_name" />
            </div>

            <div>
              <InputLabel for="company_email" value="Company Email" />
              <TextInput
                id="company_email"
                type="email"
                class="mt-1 block w-full"
                v-model="form.company_email"
                required
              />
              <InputError class="mt-2" :message="form.errors.company_email" />
            </div>

            <div>
              <InputLabel for="company_website" value="Company Website" />
              <TextInput
                id="company_website"
                type="url"
                class="mt-1 block w-full"
                v-model="form.company_website"
              />
              <InputError class="mt-2" :message="form.errors.company_website" />
            </div>

            <div>
              <InputLabel for="company_phone" value="Company Phone" />
              <TextInput
                id="company_phone"
                type="tel"
                class="mt-1 block w-full"
                v-model="form.company_phone"
              />
              <InputError class="mt-2" :message="form.errors.company_phone" />
            </div>

            <div class="md:col-span-2">
              <InputLabel for="company_address" value="Company Address" />
              <textarea
                id="company_address"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                rows="3"
                v-model="form.company_address"
              ></textarea>
              <InputError class="mt-2" :message="form.errors.company_address" />
            </div>
          </div>
        </div>

        <!-- System Configuration -->
        <div class="bg-gray-50 p-6 rounded-lg">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">System Configuration</h2>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
              <InputLabel for="timezone" value="Timezone" />
              <select
                id="timezone"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                v-model="form.timezone"
                required
              >
                <option value="UTC">UTC</option>
                <option value="America/New_York">Eastern Time</option>
                <option value="America/Chicago">Central Time</option>
                <option value="America/Denver">Mountain Time</option>
                <option value="America/Los_Angeles">Pacific Time</option>
                <option value="Europe/London">London</option>
                <option value="Europe/Paris">Paris</option>
                <option value="Asia/Tokyo">Tokyo</option>
              </select>
              <InputError class="mt-2" :message="form.errors.timezone" />
            </div>

            <div>
              <InputLabel for="currency" value="Currency" />
              <select
                id="currency"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                v-model="form.currency"
                required
              >
                <option value="USD">USD - US Dollar</option>
                <option value="EUR">EUR - Euro</option>
                <option value="GBP">GBP - British Pound</option>
                <option value="CAD">CAD - Canadian Dollar</option>
                <option value="AUD">AUD - Australian Dollar</option>
              </select>
              <InputError class="mt-2" :message="form.errors.currency" />
            </div>

            <div>
              <InputLabel for="language" value="Language" />
              <select
                id="language"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                v-model="form.language"
                required
              >
                <option value="en">English</option>
                <option value="es">Spanish</option>
                <option value="fr">French</option>
                <option value="de">German</option>
                <option value="ja">Japanese</option>
              </select>
              <InputError class="mt-2" :message="form.errors.language" />
            </div>

            <div>
              <InputLabel for="date_format" value="Date Format" />
              <select
                id="date_format"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                v-model="form.date_format"
                required
              >
                <option value="Y-m-d">2024-12-31</option>
                <option value="m/d/Y">12/31/2024</option>
                <option value="d/m/Y">31/12/2024</option>
                <option value="d-m-Y">31-12-2024</option>
              </select>
              <InputError class="mt-2" :message="form.errors.date_format" />
            </div>

            <div>
              <InputLabel for="time_format" value="Time Format" />
              <select
                id="time_format"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                v-model="form.time_format"
                required
              >
                <option value="H:i">24 Hour (14:30)</option>
                <option value="g:i A">12 Hour (2:30 PM)</option>
              </select>
              <InputError class="mt-2" :message="form.errors.time_format" />
            </div>

            <div>
              <InputLabel for="max_users" value="Maximum Users" />
              <TextInput
                id="max_users"
                type="number"
                class="mt-1 block w-full"
                v-model="form.max_users"
                min="1"
                max="10000"
                required
              />
              <InputError class="mt-2" :message="form.errors.max_users" />
            </div>
          </div>
        </div>

        <!-- User Limits -->
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-6 rounded-lg border border-blue-200">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">User Limits</h2>
          <p class="text-sm text-gray-600 mb-4">
            Set the maximum number of users for your Service Vault installation. 
            <span class="text-blue-600">Full licensing system will be implemented in a future release.</span>
          </p>
          <div class="max-w-md">
            <div>
              <InputLabel for="max_users" value="Maximum Users" />
              <TextInput
                id="max_users"
                type="number"
                class="mt-1 block w-full"
                v-model="form.max_users"
                min="1"
                max="10000"
                required
              />
              <InputError class="mt-2" :message="form.errors.max_users" />
              <p class="mt-1 text-xs text-gray-500">Default: 250 users (will be determined by license in future releases)</p>
            </div>
          </div>
        </div>

        <!-- Admin User Information -->
        <div class="bg-green-50 p-6 rounded-lg border border-green-200">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">Administrator Account</h2>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
              <InputLabel for="admin_name" value="Admin Name" />
              <TextInput
                id="admin_name"
                type="text"
                class="mt-1 block w-full"
                v-model="form.admin_name"
                required
              />
              <InputError class="mt-2" :message="form.errors.admin_name" />
            </div>

            <div>
              <InputLabel for="admin_email" value="Admin Email" />
              <TextInput
                id="admin_email"
                type="email"
                class="mt-1 block w-full"
                v-model="form.admin_email"
                required
              />
              <InputError class="mt-2" :message="form.errors.admin_email" />
            </div>

            <div>
              <InputLabel for="admin_password" value="Admin Password" />
              <TextInput
                id="admin_password"
                type="password"
                class="mt-1 block w-full"
                v-model="form.admin_password"
                required
                autocomplete="new-password"
              />
              <InputError class="mt-2" :message="form.errors.admin_password" />
            </div>

            <div class="md:col-span-3">
              <InputLabel for="admin_password_confirmation" value="Confirm Admin Password" />
              <TextInput
                id="admin_password_confirmation"
                type="password"
                class="mt-1 block w-full"
                v-model="form.admin_password_confirmation"
                required
                autocomplete="new-password"
              />
              <InputError class="mt-2" :message="form.errors.admin_password_confirmation" />
            </div>
          </div>
        </div>

        <!-- Advanced Settings -->
        <div class="bg-gray-50 p-6 rounded-lg">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">Advanced Settings</h2>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
              <InputLabel for="max_account_depth" value="Max Account Depth" />
              <TextInput
                id="max_account_depth"
                type="number"
                class="mt-1 block w-full"
                v-model="form.max_account_depth"
                min="1"
                max="20"
                required
              />
              <InputError class="mt-2" :message="form.errors.max_account_depth" />
            </div>

            <div>
              <InputLabel for="timer_sync_interval" value="Timer Sync Interval (seconds)" />
              <TextInput
                id="timer_sync_interval"
                type="number"
                class="mt-1 block w-full"
                v-model="form.timer_sync_interval"
                min="1"
                max="60"
                required
              />
              <InputError class="mt-2" :message="form.errors.timer_sync_interval" />
            </div>

            <div>
              <InputLabel for="permission_cache_ttl" value="Permission Cache TTL (seconds)" />
              <TextInput
                id="permission_cache_ttl"
                type="number"
                class="mt-1 block w-full"
                v-model="form.permission_cache_ttl"
                min="60"
                max="3600"
                required
              />
              <InputError class="mt-2" :message="form.errors.permission_cache_ttl" />
            </div>
          </div>

          <div class="mt-6 flex items-center space-x-6">
            <label class="flex items-center">
              <Checkbox name="enable_real_time" v-model:checked="form.enable_real_time" />
              <span class="ms-2 text-sm text-gray-600">Enable Real-time Features</span>
            </label>

            <label class="flex items-center">
              <Checkbox name="enable_notifications" v-model:checked="form.enable_notifications" />
              <span class="ms-2 text-sm text-gray-600">Enable Notifications</span>
            </label>
          </div>
        </div>

        <div class="flex items-center justify-center">
          <PrimaryButton 
            class="px-8 py-3 text-lg"
            :class="{ 'opacity-25': form.processing }" 
            :disabled="form.processing"
          >
            <span v-if="form.processing">Setting up Service Vault...</span>
            <span v-else>Complete Setup</span>
          </PrimaryButton>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import Checkbox from '@/Components/Checkbox.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import TextInput from '@/Components/TextInput.vue'

const form = useForm({
  // Company Information
  company_name: '',
  company_email: '',
  company_website: '',
  company_address: '',
  company_phone: '',
  
  // System Configuration
  timezone: 'UTC',
  currency: 'USD',
  date_format: 'Y-m-d',
  time_format: 'H:i',
  language: 'en',
  
  // Features & Limits
  enable_real_time: true,
  enable_notifications: true,
  max_account_depth: 10,
  timer_sync_interval: 5,
  permission_cache_ttl: 300,
  
  // User Limits (licensing will be implemented later)
  max_users: 250,
  
  // Admin User Information
  admin_name: '',
  admin_email: '',
  admin_password: '',
  admin_password_confirmation: '',
})

const submit = () => {
  form.post(route('setup.store'), {
    onFinish: () => form.reset('admin_password', 'admin_password_confirmation'),
  })
}
</script>