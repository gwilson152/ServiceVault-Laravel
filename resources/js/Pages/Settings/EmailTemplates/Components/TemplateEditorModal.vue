<template>
  <StackedDialog
    :show="show"
    @close="$emit('close')"
    size="xl"
    :title="isEditing ? 'Edit Email Template' : 'Create Email Template'"
  >
    <form @submit.prevent="saveTemplate" class="space-y-6">
      <!-- Template Basic Info -->
      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <!-- Template Name -->
        <div>
          <label for="name" class="block text-sm font-medium text-gray-700">
            Template Name *
          </label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            required
            placeholder="e.g., Ticket Created Notification"
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          />
        </div>

        <!-- Template Type -->
        <div>
          <label for="type" class="block text-sm font-medium text-gray-700">
            Template Type *
          </label>
          <select
            id="type"
            v-model="form.type"
            required
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          >
            <option value="">Select a type</option>
            <option v-for="type in templateTypes" :key="type.value" :value="type.value">
              {{ type.label }}
            </option>
          </select>
        </div>
      </div>

      <!-- Description -->
      <div>
        <label for="description" class="block text-sm font-medium text-gray-700">
          Description
        </label>
        <textarea
          id="description"
          v-model="form.description"
          rows="2"
          placeholder="Brief description of when this template is used..."
          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
        ></textarea>
      </div>

      <!-- Account Assignment -->
      <div>
        <label class="block text-sm font-medium text-gray-700">Account</label>
        <div class="mt-2 space-y-2">
          <div class="flex items-center">
            <input
              id="global_template"
              v-model="form.isGlobal"
              type="radio"
              :value="true"
              name="account_assignment"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
            />
            <label for="global_template" class="ml-3 text-sm text-gray-700">
              Global Template (Available to all accounts)
            </label>
          </div>
          <div class="flex items-center">
            <input
              id="account_specific"
              v-model="form.isGlobal"
              type="radio"
              :value="false"
              name="account_assignment"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
            />
            <label for="account_specific" class="ml-3 text-sm text-gray-700">
              Account-specific template
            </label>
          </div>
        </div>

        <!-- Account Selector -->
        <div v-if="!form.isGlobal" class="mt-3">
          <UnifiedSelector
            v-model="form.account"
            type="accounts"
            placeholder="Select account..."
            :required="!form.isGlobal"
          />
        </div>
      </div>

      <!-- Email Subject -->
      <div>
        <div class="flex items-center justify-between">
          <label for="subject" class="block text-sm font-medium text-gray-700">
            Email Subject *
          </label>
          <button
            type="button"
            @click="showVariableHelper = !showVariableHelper"
            class="text-sm text-indigo-600 hover:text-indigo-500"
          >
            {{ showVariableHelper ? 'Hide' : 'Show' }} Variables
          </button>
        </div>
        
        <!-- Variable Helper -->
        <VariableHelper
          v-if="showVariableHelper"
          @insert="insertVariable"
          target="subject"
        />
        
        <textarea
          id="subject"
          ref="subjectField"
          v-model="form.subject"
          rows="2"
          required
          placeholder="e.g., New Ticket Created: {{ticket.subject}}"
          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
        ></textarea>
        <p class="mt-1 text-xs text-gray-500">
          Use variables like {{'{{'}}ticket.number{{'}}'}} or {{'{{'}}user.name{{'}}'}} to make the subject dynamic
        </p>
      </div>

      <!-- Email Body Tabs -->
      <div>
        <div class="flex items-center justify-between">
          <label class="block text-sm font-medium text-gray-700">
            Email Body *
          </label>
          <div class="flex space-x-2">
            <button
              type="button"
              @click="showVariableHelper = !showVariableHelper"
              class="text-sm text-indigo-600 hover:text-indigo-500"
            >
              {{ showVariableHelper ? 'Hide' : 'Show' }} Variables
            </button>
            <button
              type="button"
              @click="previewTemplate"
              :disabled="!form.subject || !form.body"
              class="text-sm text-indigo-600 hover:text-indigo-500 disabled:text-gray-400"
            >
              Preview
            </button>
          </div>
        </div>

        <!-- Variable Helper -->
        <VariableHelper
          v-if="showVariableHelper"
          @insert="insertVariable"
          target="body"
        />

        <!-- Body Editor Tabs -->
        <div class="mt-2">
          <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
              <button
                type="button"
                @click="activeTab = 'visual'"
                :class="[
                  activeTab === 'visual'
                    ? 'border-indigo-500 text-indigo-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                  'whitespace-nowrap pb-2 px-1 border-b-2 font-medium text-sm'
                ]"
              >
                Visual Editor
              </button>
              <button
                type="button"
                @click="activeTab = 'html'"
                :class="[
                  activeTab === 'html'
                    ? 'border-indigo-500 text-indigo-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                  'whitespace-nowrap pb-2 px-1 border-b-2 font-medium text-sm'
                ]"
              >
                HTML Source
              </button>
              <button
                type="button"
                @click="activeTab = 'plain'"
                :class="[
                  activeTab === 'plain'
                    ? 'border-indigo-500 text-indigo-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                  'whitespace-nowrap pb-2 px-1 border-b-2 font-medium text-sm'
                ]"
              >
                Plain Text
              </button>
            </nav>
          </div>

          <div class="mt-4">
            <!-- Visual Editor -->
            <div v-show="activeTab === 'visual'">
              <div
                ref="visualEditor"
                contenteditable="true"
                @input="updateBodyFromVisual"
                class="min-h-[200px] w-full border border-gray-300 rounded-md p-3 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                style="outline: none;"
              ></div>
            </div>

            <!-- HTML Source -->
            <div v-show="activeTab === 'html'">
              <textarea
                ref="htmlEditor"
                v-model="form.body"
                rows="12"
                placeholder="<html>
<body>
  <h1>Hello {{user.name}}</h1>
  <p>Your ticket {{ticket.number}} has been created.</p>
</body>
</html>"
                class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono"
              ></textarea>
            </div>

            <!-- Plain Text -->
            <div v-show="activeTab === 'plain'">
              <textarea
                ref="plainEditor"
                v-model="form.plain_text_body"
                rows="12"
                placeholder="Hello {{user.name}},

Your ticket {{ticket.number}} has been created.

Thanks,
Support Team"
                class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              ></textarea>
            </div>
          </div>
        </div>

        <div class="mt-2 flex justify-between text-xs text-gray-500">
          <div>
            Supports 6 variable formats: {{'{{'}}var{{'}}'}}, {var}, [var], $var$, ${var}, %var%
          </div>
          <div v-if="activeTab === 'html'">
            Characters: {{ (form.body || '').length }}
          </div>
        </div>
      </div>

      <!-- Template Settings -->
      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <!-- Active Status -->
        <div class="flex items-center">
          <input
            id="is_active"
            v-model="form.is_active"
            type="checkbox"
            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
          />
          <label for="is_active" class="ml-3 text-sm text-gray-700">
            Template is active
          </label>
        </div>

        <!-- System Template (if editing) -->
        <div v-if="isEditing && template?.is_system_template" class="flex items-center">
          <ExclamationTriangleIcon class="h-4 w-4 text-amber-500 mr-2" />
          <span class="text-sm text-amber-700">System Template (Protected)</span>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
        <button
          type="button"
          @click="$emit('close')"
          class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          Cancel
        </button>
        
        <button
          v-if="!isEditing"
          type="button"
          @click="saveAndCreateAnother"
          :disabled="saving"
          class="px-4 py-2 border border-indigo-300 rounded-md shadow-sm text-sm font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
        >
          Save & Create Another
        </button>
        
        <button
          type="submit"
          :disabled="saving"
          class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
        >
          <span v-if="saving" class="flex items-center">
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Saving...
          </span>
          <span v-else>
            {{ isEditing ? 'Update Template' : 'Create Template' }}
          </span>
        </button>
      </div>
    </form>

    <!-- Template Preview Modal -->
    <TemplatePreviewModal
      :show="showPreview"
      :template="previewData"
      @close="showPreview = false"
    />
  </StackedDialog>
</template>

<script setup>
import { ref, reactive, computed, watch, nextTick } from 'vue'
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import StackedDialog from '@/Components/Modals/StackedDialog.vue'
import UnifiedSelector from '@/Components/Form/UnifiedSelector.vue'
import VariableHelper from './VariableHelper.vue'
import TemplatePreviewModal from './TemplatePreviewModal.vue'

const props = defineProps({
  show: Boolean,
  template: Object
})

const emit = defineEmits(['close', 'saved'])

// State
const saving = ref(false)
const showVariableHelper = ref(false)
const showPreview = ref(false)
const activeTab = ref('visual')
const subjectField = ref(null)
const visualEditor = ref(null)
const htmlEditor = ref(null)
const plainEditor = ref(null)

// Form Data
const form = reactive({
  name: '',
  type: '',
  description: '',
  subject: '',
  body: '',
  plain_text_body: '',
  account: null,
  isGlobal: true,
  is_active: true
})

// Computed
const isEditing = computed(() => !!props.template)

const previewData = computed(() => ({
  ...form,
  account_id: form.isGlobal ? null : form.account?.id
}))

const templateTypes = [
  { value: 'ticket_created', label: 'Ticket Created' },
  { value: 'ticket_updated', label: 'Ticket Updated' },
  { value: 'ticket_closed', label: 'Ticket Closed' },
  { value: 'ticket_assigned', label: 'Ticket Assigned' },
  { value: 'time_entry_added', label: 'Time Entry Added' },
  { value: 'invoice_generated', label: 'Invoice Generated' },
  { value: 'invoice_sent', label: 'Invoice Sent' },
  { value: 'payment_received', label: 'Payment Received' },
  { value: 'user_invitation', label: 'User Invitation' },
  { value: 'password_reset', label: 'Password Reset' },
  { value: 'email_verification', label: 'Email Verification' },
  { value: 'custom', label: 'Custom Template' }
]

// Watch for template changes
watch(() => props.template, (newTemplate) => {
  if (newTemplate) {
    form.name = newTemplate.name || ''
    form.type = newTemplate.type || ''
    form.description = newTemplate.description || ''
    form.subject = newTemplate.subject || ''
    form.body = newTemplate.body || ''
    form.plain_text_body = newTemplate.plain_text_body || ''
    form.account = newTemplate.account || null
    form.isGlobal = !newTemplate.account_id
    form.is_active = newTemplate.is_active !== false
  } else {
    // Reset form for new template
    Object.keys(form).forEach(key => {
      if (key === 'isGlobal' || key === 'is_active') {
        form[key] = true
      } else if (key === 'account') {
        form[key] = null
      } else {
        form[key] = ''
      }
    })
  }

  // Update visual editor when template changes
  nextTick(() => {
    updateVisualEditor()
  })
}, { immediate: true })

// Watch for active tab changes
watch(activeTab, () => {
  nextTick(() => {
    if (activeTab.value === 'visual') {
      updateVisualEditor()
    }
  })
})

// Methods
function updateVisualEditor() {
  if (visualEditor.value && form.body) {
    visualEditor.value.innerHTML = form.body
  }
}

function updateBodyFromVisual() {
  if (visualEditor.value) {
    form.body = visualEditor.value.innerHTML
  }
}

function insertVariable(variable, target = 'body') {
  const variableText = `{{${variable}}}`
  
  if (target === 'subject' && subjectField.value) {
    const field = subjectField.value
    const start = field.selectionStart
    const end = field.selectionEnd
    const text = field.value
    
    form.subject = text.substring(0, start) + variableText + text.substring(end)
    
    // Set cursor position after inserted variable
    nextTick(() => {
      field.focus()
      field.setSelectionRange(start + variableText.length, start + variableText.length)
    })
  } else if (target === 'body') {
    if (activeTab.value === 'visual' && visualEditor.value) {
      // Insert into visual editor
      const selection = window.getSelection()
      const range = selection.getRangeAt(0)
      range.deleteContents()
      range.insertNode(document.createTextNode(variableText))
      range.collapse(false)
      selection.removeAllRanges()
      selection.addRange(range)
      updateBodyFromVisual()
    } else if (activeTab.value === 'html' && htmlEditor.value) {
      // Insert into HTML editor
      const field = htmlEditor.value
      const start = field.selectionStart
      const end = field.selectionEnd
      const text = field.value
      
      form.body = text.substring(0, start) + variableText + text.substring(end)
      
      nextTick(() => {
        field.focus()
        field.setSelectionRange(start + variableText.length, start + variableText.length)
      })
    } else if (activeTab.value === 'plain' && plainEditor.value) {
      // Insert into plain text editor
      const field = plainEditor.value
      const start = field.selectionStart
      const end = field.selectionEnd
      const text = field.value
      
      form.plain_text_body = text.substring(0, start) + variableText + text.substring(end)
      
      nextTick(() => {
        field.focus()
        field.setSelectionRange(start + variableText.length, start + variableText.length)
      })
    }
  }
}

function previewTemplate() {
  showPreview.value = true
}

async function saveTemplate(createAnother = false) {
  try {
    saving.value = true
    
    const templateData = {
      name: form.name,
      type: form.type,
      description: form.description || null,
      subject: form.subject,
      body: form.body,
      plain_text_body: form.plain_text_body || null,
      account_id: form.isGlobal ? null : form.account?.id,
      is_active: form.is_active
    }

    const url = isEditing.value 
      ? `/api/email-templates/${props.template.id}`
      : '/api/email-templates'
    
    const method = isEditing.value ? 'PUT' : 'POST'

    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify(templateData)
    })

    if (!response.ok) {
      const error = await response.json()
      throw new Error(error.message || 'Failed to save template')
    }

    emit('saved')

    if (createAnother) {
      // Reset form for new template
      Object.keys(form).forEach(key => {
        if (key === 'isGlobal' || key === 'is_active') {
          form[key] = true
        } else if (key === 'account') {
          form[key] = null
        } else {
          form[key] = ''
        }
      })
      updateVisualEditor()
    }

  } catch (error) {
    console.error('Error saving template:', error)
    // Show error notification
    alert('Error saving template: ' + error.message)
  } finally {
    saving.value = false
  }
}

async function saveAndCreateAnother() {
  await saveTemplate(true)
}
</script>

<style scoped>
/* Visual editor styles */
[contenteditable="true"] {
  min-height: 200px;
}

[contenteditable="true"]:focus {
  outline: 2px solid #6366f1;
  outline-offset: 2px;
}
</style>