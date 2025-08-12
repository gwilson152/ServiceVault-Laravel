<template>
  <div class="space-y-8">
    <!-- Invoice Settings Header -->
    <div>
      <h2 class="text-2xl font-semibold text-gray-900">Invoice Settings</h2>
      <p class="text-gray-600 mt-2">Configure invoice generation, numbering, and payment settings.</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-8">
      <svg class="animate-spin h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
    </div>

    <template v-else>
      <!-- Company Information -->
      <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-6">Company Information</h3>
        
        <form @submit.prevent="saveCompanySettings" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label for="company_name" class="block text-sm font-medium text-gray-700">Company Name</label>
              <input
                id="company_name"
                v-model="form.company_name"
                type="text"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                required
              />
            </div>
            
            <div>
              <label for="company_email" class="block text-sm font-medium text-gray-700">Company Email</label>
              <input
                id="company_email"
                v-model="form.company_email"
                type="email"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                required
              />
            </div>
          </div>
          
          <div>
            <label for="company_address" class="block text-sm font-medium text-gray-700">Company Address</label>
            <textarea
              id="company_address"
              v-model="form.company_address"
              rows="3"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
              placeholder="123 Business St&#10;City, State 12345"
            />
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label for="company_phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
              <input
                id="company_phone"
                v-model="form.company_phone"
                type="tel"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
              />
            </div>
            
            <div>
              <label for="company_website" class="block text-sm font-medium text-gray-700">Website</label>
              <input
                id="company_website"
                v-model="form.company_website"
                type="url"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
              />
            </div>
          </div>
          
          <div>
            <label for="tax_id" class="block text-sm font-medium text-gray-700">Tax ID / Business Number</label>
            <input
              id="tax_id"
              v-model="form.tax_id"
              type="text"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
            />
          </div>
          
          <div class="flex justify-end">
            <button
              type="submit"
              :disabled="saving"
              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
            >
              <svg v-if="saving" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ saving ? 'Saving...' : 'Save Company Info' }}
            </button>
          </div>
        </form>
      </div>

      <!-- Invoice Numbering -->
      <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-6">Invoice Numbering</h3>
        
        <form @submit.prevent="saveInvoiceSettings" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
              <label for="invoice_prefix" class="block text-sm font-medium text-gray-700">Invoice Prefix</label>
              <input
                id="invoice_prefix"
                v-model="form.invoice_prefix"
                type="text"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="INV"
              />
            </div>
            
            <div>
              <label for="next_invoice_number" class="block text-sm font-medium text-gray-700">Next Invoice Number</label>
              <input
                id="next_invoice_number"
                v-model.number="form.next_invoice_number"
                type="number"
                min="1"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Preview</label>
              <div class="mt-1 px-3 py-2 bg-gray-100 border border-gray-300 rounded-md text-sm text-gray-900">
                {{ form.invoice_prefix }}-{{ String(form.next_invoice_number).padStart(4, '0') }}
              </div>
            </div>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label for="payment_terms" class="block text-sm font-medium text-gray-700">Default Payment Terms (Days)</label>
              <input
                id="payment_terms"
                v-model.number="form.payment_terms"
                type="number"
                min="1"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
              />
            </div>
            
            <div>
              <label for="currency" class="block text-sm font-medium text-gray-700">Currency</label>
              <select
                id="currency"
                v-model="form.currency"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
              >
                <option value="USD">USD - US Dollar</option>
                <option value="CAD">CAD - Canadian Dollar</option>
                <option value="EUR">EUR - Euro</option>
                <option value="GBP">GBP - British Pound</option>
                <option value="AUD">AUD - Australian Dollar</option>
              </select>
            </div>
          </div>
          
          <div class="flex justify-end">
            <button
              type="submit"
              :disabled="saving"
              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
            >
              {{ saving ? 'Saving...' : 'Save Invoice Settings' }}
            </button>
          </div>
        </form>
      </div>

      <!-- Payment Methods -->
      <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-6">Payment Methods</h3>
        
        <div class="space-y-4">
          <div 
            v-for="method in paymentMethods" 
            :key="method.key"
            class="flex items-center justify-between p-4 border rounded-lg"
          >
            <div class="flex items-center">
              <input
                :id="`payment_${method.key}`"
                v-model="form.payment_methods"
                :value="method.key"
                type="checkbox"
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
              />
              <label :for="`payment_${method.key}`" class="ml-3 text-sm font-medium text-gray-900">
                {{ method.name }}
              </label>
            </div>
            <div class="text-sm text-gray-500">
              {{ method.description }}
            </div>
          </div>
        </div>
        
        <div class="mt-6 flex justify-end">
          <button
            @click="savePaymentMethods"
            :disabled="saving"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
          >
            Save Payment Methods
          </button>
        </div>
      </div>

      <!-- Automation Settings -->
      <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-6">Automation & Reminders</h3>
        
        <div class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <label for="auto_send_invoices" class="text-sm font-medium text-gray-900">Auto-send Invoices</label>
              <p class="text-sm text-gray-500">Automatically send invoices when generated</p>
            </div>
            <div class="relative inline-block w-10 mr-2 align-middle select-none transition duration-200 ease-in">
              <input
                id="auto_send_invoices"
                v-model="form.auto_send_invoices"
                type="checkbox"
                class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer"
              />
              <label
                for="auto_send_invoices"
                class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer"
              ></label>
            </div>
          </div>
          
          <div class="flex items-center justify-between">
            <div>
              <label for="send_payment_reminders" class="text-sm font-medium text-gray-900">Payment Reminders</label>
              <p class="text-sm text-gray-500">Send automated payment reminders</p>
            </div>
            <div class="relative inline-block w-10 mr-2 align-middle select-none transition duration-200 ease-in">
              <input
                id="send_payment_reminders"
                v-model="form.send_payment_reminders"
                type="checkbox"
                class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer"
              />
              <label
                for="send_payment_reminders"
                class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer"
              ></label>
            </div>
          </div>
        </div>
        
        <div class="mt-6 flex justify-end">
          <button
            @click="saveAutomationSettings"
            :disabled="saving"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
          >
            Save Automation Settings
          </button>
        </div>
      </div>

      <!-- Invoice Footer & Instructions -->
      <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-6">Invoice Footer & Instructions</h3>
        
        <form @submit.prevent="saveFooterSettings" class="space-y-6">
          <div>
            <label for="invoice_footer" class="block text-sm font-medium text-gray-700">Invoice Footer</label>
            <textarea
              id="invoice_footer"
              v-model="form.invoice_footer"
              rows="3"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
              placeholder="Thank you for your business!"
            />
            <p class="mt-2 text-sm text-gray-500">This text will appear at the bottom of your invoices.</p>
          </div>
          
          <div>
            <label for="payment_instructions" class="block text-sm font-medium text-gray-700">Payment Instructions</label>
            <textarea
              id="payment_instructions"
              v-model="form.payment_instructions"
              rows="4"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
              placeholder="Please remit payment within the specified terms."
            />
            <p class="mt-2 text-sm text-gray-500">Provide customers with payment instructions.</p>
          </div>
          
          <div class="flex justify-end">
            <button
              type="submit"
              :disabled="saving"
              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
            >
              Save Footer Settings
            </button>
          </div>
        </form>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'

const props = defineProps({
  settings: {
    type: Object,
    default: () => ({})
  },
  loading: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['refresh'])

const saving = ref(false)

const form = reactive({
  company_name: props.settings.company_name || '',
  company_email: props.settings.company_email || '',
  company_address: props.settings.company_address || '',
  company_phone: props.settings.company_phone || '',
  company_website: props.settings.company_website || '',
  tax_id: props.settings.tax_id || '',
  invoice_prefix: props.settings.invoice_prefix || 'INV',
  next_invoice_number: props.settings.next_invoice_number || 1001,
  payment_terms: props.settings.payment_terms || 30,
  currency: props.settings.currency || 'USD',
  payment_methods: props.settings.payment_methods || ['bank_transfer'],
  auto_send_invoices: props.settings.auto_send_invoices || false,
  send_payment_reminders: props.settings.send_payment_reminders || true,
  invoice_footer: props.settings.invoice_footer || '',
  payment_instructions: props.settings.payment_instructions || '',
})

const paymentMethods = [
  {
    key: 'bank_transfer',
    name: 'Bank Transfer',
    description: 'Wire transfer or ACH'
  },
  {
    key: 'check',
    name: 'Check',
    description: 'Physical or electronic check'
  },
  {
    key: 'cash',
    name: 'Cash',
    description: 'Cash payment'
  },
  {
    key: 'stripe',
    name: 'Stripe',
    description: 'Credit card via Stripe'
  },
  {
    key: 'paypal',
    name: 'PayPal',
    description: 'PayPal payment'
  },
  {
    key: 'other',
    name: 'Other',
    description: 'Other payment methods'
  }
]

const saveCompanySettings = async () => {
  saving.value = true
  try {
    // TODO: Implement API call to save company settings
    console.log('Saving company settings:', {
      company_name: form.company_name,
      company_email: form.company_email,
      company_address: form.company_address,
      company_phone: form.company_phone,
      company_website: form.company_website,
      tax_id: form.tax_id,
    })
    
    // Show success message
    emit('refresh')
  } catch (error) {
    console.error('Error saving company settings:', error)
  } finally {
    saving.value = false
  }
}

const saveInvoiceSettings = async () => {
  saving.value = true
  try {
    // TODO: Implement API call to save invoice settings
    console.log('Saving invoice settings:', {
      invoice_prefix: form.invoice_prefix,
      next_invoice_number: form.next_invoice_number,
      payment_terms: form.payment_terms,
      currency: form.currency,
    })
    
    emit('refresh')
  } catch (error) {
    console.error('Error saving invoice settings:', error)
  } finally {
    saving.value = false
  }
}

const savePaymentMethods = async () => {
  saving.value = true
  try {
    // TODO: Implement API call to save payment methods
    console.log('Saving payment methods:', form.payment_methods)
    
    emit('refresh')
  } catch (error) {
    console.error('Error saving payment methods:', error)
  } finally {
    saving.value = false
  }
}

const saveAutomationSettings = async () => {
  saving.value = true
  try {
    // TODO: Implement API call to save automation settings
    console.log('Saving automation settings:', {
      auto_send_invoices: form.auto_send_invoices,
      send_payment_reminders: form.send_payment_reminders,
    })
    
    emit('refresh')
  } catch (error) {
    console.error('Error saving automation settings:', error)
  } finally {
    saving.value = false
  }
}

const saveFooterSettings = async () => {
  saving.value = true
  try {
    // TODO: Implement API call to save footer settings
    console.log('Saving footer settings:', {
      invoice_footer: form.invoice_footer,
      payment_instructions: form.payment_instructions,
    })
    
    emit('refresh')
  } catch (error) {
    console.error('Error saving footer settings:', error)
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.toggle-checkbox:checked {
  @apply: right-0 border-green-400;
  right: 0;
  border-color: #68d391;
}
.toggle-checkbox:checked + .toggle-label {
  @apply: bg-green-400;
  background-color: #68d391;
}
</style>