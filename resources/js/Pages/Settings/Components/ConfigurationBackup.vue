<template>
    <div class="space-y-8">
        <!-- Export Configuration Section -->
        <div class="bg-white shadow-sm rounded-lg p-6">
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Export Configuration</h3>
                <p class="text-sm text-gray-600">
                    Download your current system configuration as a JSON file. Select which settings to include in the backup.
                </p>
            </div>

            <div class="space-y-4">
                <!-- Category Selection -->
                <div>
                    <label class="text-sm font-medium text-gray-700 mb-3 block">Select Categories to Export</label>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <label v-for="category in availableCategories" :key="category.id" 
                               class="flex items-center space-x-2 p-3 border border-gray-200 rounded-lg hover:border-gray-300 cursor-pointer">
                            <input 
                                type="checkbox" 
                                :value="category.id" 
                                v-model="exportState.selectedCategories"
                                class="rounded border-gray-300 text-blue-600 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                            />
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-900">{{ category.name }}</div>
                                <div class="text-xs text-gray-500">{{ category.description }}</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Quick Selection Buttons -->
                <div class="flex flex-wrap gap-2">
                    <button @click="selectAllCategories" 
                            class="px-3 py-1 text-xs font-medium text-blue-600 bg-blue-50 rounded hover:bg-blue-100">
                        Select All
                    </button>
                    <button @click="selectNoneCategories" 
                            class="px-3 py-1 text-xs font-medium text-gray-600 bg-gray-50 rounded hover:bg-gray-100">
                        Select None
                    </button>
                    <button @click="selectSystemOnly" 
                            class="px-3 py-1 text-xs font-medium text-green-600 bg-green-50 rounded hover:bg-green-100">
                        System Only
                    </button>
                </div>

                <!-- Export Options -->
                <div class="border-t pt-4 space-y-3">
                    <label class="flex items-center space-x-2">
                        <input 
                            type="checkbox" 
                            v-model="exportState.includeMetadata"
                            class="rounded border-gray-300 text-blue-600 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                        />
                        <span class="text-sm text-gray-700">Include export metadata (timestamp, user, version)</span>
                    </label>
                    
                    <!-- Security Notice for Import Profiles -->
                    <div v-if="exportState.selectedCategories.includes('import-profiles')" class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                        <div class="flex">
                            <svg class="flex-shrink-0 h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            <div class="ml-3">
                                <h4 class="text-sm font-medium text-yellow-800">Security Notice</h4>
                                <p class="text-sm text-yellow-700 mt-1">
                                    Import profile passwords and API keys will be masked in exports for security. You'll need to reconfigure credentials after importing.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Export Button -->
                <div class="flex justify-between items-center pt-4 border-t">
                    <div class="text-sm text-gray-600">
                        {{ exportState.selectedCategories.length }} categories selected
                    </div>
                    <button @click="exportConfiguration" 
                            :disabled="exportState.selectedCategories.length === 0 || exportState.isExporting"
                            class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed">
                        <div class="flex items-center space-x-2">
                            <svg v-if="exportState.isExporting" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                            </svg>
                            <span>{{ exportState.isExporting ? 'Exporting...' : 'Export Configuration' }}</span>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        <!-- Import Configuration Section -->
        <div class="bg-white shadow-sm rounded-lg p-6">
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Import Configuration</h3>
                <p class="text-sm text-gray-600">
                    Upload and restore configuration from a JSON backup file. You can preview changes before applying them.
                </p>
            </div>

            <div class="space-y-4">
                <!-- File Upload -->
                <div>
                    <label class="text-sm font-medium text-gray-700 mb-2 block">Configuration File</label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-gray-400"
                         :class="{ 'border-blue-400 bg-blue-50': isDragOver }"
                         @drop.prevent="handleFileDrop"
                         @dragover.prevent="isDragOver = true"
                         @dragleave.prevent="isDragOver = false">
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-gray-600">
                                <label for="config-file-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                    <span>Upload a file</span>
                                    <input id="config-file-upload" name="config-file-upload" type="file" class="sr-only" accept=".json" @change="handleFileSelect" />
                                </label>
                                <p class="pl-1">or drag and drop</p>
                            </div>
                            <p class="text-xs text-gray-500">JSON files only, up to 10MB</p>
                        </div>
                    </div>
                    <div v-if="importState.uploadedFile" class="mt-2 text-sm text-gray-600">
                        Selected: {{ importState.uploadedFile.name }}
                    </div>
                </div>

                <!-- Available Categories (shown after file validation) -->
                <div v-if="importState.availableCategories.length > 0" class="space-y-4">
                    <div class="border-t pt-4">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Available Categories in Upload</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <label v-for="category in importState.availableCategories" :key="category.id"
                                   class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:border-gray-300 cursor-pointer">
                                <div class="flex items-center space-x-2">
                                    <input 
                                        type="checkbox" 
                                        :value="category.id" 
                                        v-model="importState.selectedCategories"
                                        class="rounded border-gray-300 text-blue-600 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                    />
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ category.name }}</div>
                                        <div class="text-xs text-gray-500">{{ category.count }} settings</div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Import Options -->
                    <div class="border-t pt-4 space-y-3">
                        <label class="flex items-center space-x-2">
                            <input 
                                type="checkbox" 
                                v-model="importState.overwriteExisting"
                                class="rounded border-gray-300 text-blue-600 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                            />
                            <span class="text-sm text-gray-700">Overwrite existing settings</span>
                        </label>
                        <p class="text-xs text-gray-500 ml-6">
                            If unchecked, only new settings will be added, existing settings will be preserved.
                        </p>
                        
                        <!-- Import Profiles Credential Warning -->
                        <div v-if="importState.selectedCategories.includes('import-profiles')" class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                            <div class="flex">
                                <svg class="flex-shrink-0 h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                                <div class="ml-3">
                                    <h4 class="text-sm font-medium text-blue-800">Import Notice</h4>
                                    <p class="text-sm text-blue-700 mt-1">
                                        Credentials (passwords/API keys) are masked in backups. You'll need to manually configure connection settings after import.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Preview Changes -->
                    <div class="border-t pt-4">
                        <button @click="previewImport" 
                                :disabled="importState.selectedCategories.length === 0 || importState.isValidating"
                                class="px-4 py-2 bg-yellow-600 text-white font-medium rounded-lg hover:bg-yellow-700 disabled:bg-gray-300 disabled:cursor-not-allowed mr-3">
                            <div class="flex items-center space-x-2">
                                <svg v-if="importState.isValidating" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                <span>{{ importState.isValidating ? 'Previewing...' : 'Preview Changes' }}</span>
                            </div>
                        </button>

                        <!-- Password Input for Import -->
                        <div v-if="importState.previewData" class="mt-4 space-y-4">
                            <div>
                                <label for="import-password" class="block text-sm font-medium text-gray-700">
                                    Confirm Password to Apply Changes
                                </label>
                                <input 
                                    id="import-password"
                                    type="password" 
                                    v-model="importState.password"
                                    placeholder="Enter your password"
                                    class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                />
                            </div>

                            <button @click="applyImport" 
                                    :disabled="!importState.password || importState.isImporting"
                                    class="px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 disabled:bg-gray-300 disabled:cursor-not-allowed">
                                <div class="flex items-center space-x-2">
                                    <svg v-if="importState.isImporting" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                    <span>{{ importState.isImporting ? 'Applying...' : 'Apply Import' }}</span>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview Changes Modal -->
        <div v-if="importState.previewData" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closePreview"></div>
                
                <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full sm:p-6">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Import Preview</h3>
                        <p class="text-sm text-gray-600">Review the changes that will be applied to your configuration.</p>
                    </div>

                    <div class="max-h-96 overflow-y-auto space-y-4">
                        <div v-for="(changes, category) in importState.previewData" :key="category" class="border border-gray-200 rounded-lg p-4">
                            <h4 class="font-medium text-gray-900 capitalize mb-3">{{ category }} Settings</h4>
                            
                            <!-- Additions -->
                            <div v-if="changes.additions?.length > 0" class="mb-3">
                                <h5 class="text-sm font-medium text-green-800 mb-2">New Settings ({{ changes.additions.length }})</h5>
                                <div class="space-y-1">
                                    <div v-for="addition in changes.additions" :key="addition.key" 
                                         class="flex justify-between items-center text-sm bg-green-50 p-2 rounded">
                                        <span class="font-mono text-green-900">{{ addition.key }}</span>
                                        <span class="text-green-700">{{ formatValue(addition.new_value) }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Modifications -->
                            <div v-if="changes.modifications?.length > 0" class="mb-3">
                                <h5 class="text-sm font-medium text-yellow-800 mb-2">Modified Settings ({{ changes.modifications.length }})</h5>
                                <div class="space-y-1">
                                    <div v-for="modification in changes.modifications" :key="modification.key" 
                                         class="bg-yellow-50 p-2 rounded">
                                        <div class="font-mono text-sm text-yellow-900 mb-1">{{ modification.key }}</div>
                                        <div class="flex justify-between items-center text-xs">
                                            <span class="text-red-600">Current: {{ formatValue(modification.current_value) }}</span>
                                            <span class="text-green-600">New: {{ formatValue(modification.new_value) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Unchanged -->
                            <div v-if="changes.unchanged?.length > 0" class="mb-3">
                                <h5 class="text-sm font-medium text-gray-600 mb-2">Unchanged Settings ({{ changes.unchanged.length }})</h5>
                                <div class="text-xs text-gray-500">
                                    {{ changes.unchanged.length }} settings will remain the same
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button @click="closePreview" 
                                class="px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50">
                            Close Preview
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <div v-if="message" class="rounded-md p-4" :class="messageClass">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg v-if="message.type === 'success'" class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <svg v-else class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium" :class="message.type === 'success' ? 'text-green-800' : 'text-red-800'">
                        {{ message.text }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { useToast } from "@/Composables/useToast";

// Toast notifications
const { success, error, promise, apiError } = useToast();

// Available configuration categories
const availableCategories = [
    { id: 'system', name: 'System', description: 'Timezone, currency, company info' },
    { id: 'email', name: 'Email', description: 'SMTP, IMAP, processing rules' },
    { id: 'timer', name: 'Timer', description: 'Timer preferences and limits' },
    { id: 'advanced', name: 'Advanced', description: 'Debug settings and system options' },
    { id: 'tax', name: 'Tax', description: 'Tax rates and configuration' },
    { id: 'tickets', name: 'Tickets', description: 'Statuses, categories, priorities' },
    { id: 'billing', name: 'Billing', description: 'Billing rates and templates' },
    { id: 'import-profiles', name: 'Import Profiles', description: 'Import templates and profiles' },
];

// Export state
const exportState = reactive({
    selectedCategories: ['system', 'email', 'timer'],
    includeMetadata: true,
    isExporting: false,
});

// Import state
const importState = reactive({
    uploadedFile: null,
    availableCategories: [],
    selectedCategories: [],
    previewData: null,
    password: '',
    isValidating: false,
    isImporting: false,
    overwriteExisting: true,
});

// UI state
const isDragOver = ref(false);
const message = ref(null);

// Computed properties
const messageClass = computed(() => {
    if (!message.value) return '';
    return message.value.type === 'success' 
        ? 'bg-green-50 border border-green-200'
        : 'bg-red-50 border border-red-200';
});

// Category selection methods
const selectAllCategories = () => {
    exportState.selectedCategories = availableCategories.map(c => c.id);
};

const selectNoneCategories = () => {
    exportState.selectedCategories = [];
};

const selectSystemOnly = () => {
    exportState.selectedCategories = ['system'];
};

// Export configuration
const exportConfiguration = async () => {
    if (exportState.selectedCategories.length === 0) {
        error('Please select at least one category to export.');
        return;
    }

    try {
        exportState.isExporting = true;
        message.value = null;

        await promise(
            fetch('/api/settings/export', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify({
                    categories: exportState.selectedCategories,
                    include_metadata: exportState.includeMetadata,
                }),
            }).then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || 'Export failed')
                    })
                }
                return response.json()
            }),
            {
                loading: 'Exporting configuration...',
                success: (result) => {
                    // Download the file
                    const blob = new Blob([JSON.stringify(result.data, null, 2)], { type: 'application/json' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = result.filename;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                    
                    return result.message || 'Configuration exported successfully';
                },
                error: (err) => `Export failed: ${err.message}`
            }
        );
    } catch (err) {
        console.error('Export error:', err);
    } finally {
        exportState.isExporting = false;
    }
};

// File handling
const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        handleFile(file);
    }
};

const handleFileDrop = (event) => {
    isDragOver.value = false;
    const file = event.dataTransfer.files[0];
    if (file && file.type === 'application/json') {
        handleFile(file);
    }
};

const handleFile = async (file) => {
    importState.uploadedFile = file;
    importState.availableCategories = [];
    importState.selectedCategories = [];
    importState.previewData = null;
    importState.password = '';
    message.value = null;

    // Validate the uploaded file
    const formData = new FormData();
    formData.append('config_file', file);

    try {
        const result = await promise(
            fetch('/api/settings/validate-import', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: formData,
            }).then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || 'File validation failed')
                    })
                }
                return response.json()
            }),
            {
                loading: 'Validating configuration file...',
                success: (result) => {
                    importState.availableCategories = result.available_categories;
                    importState.selectedCategories = result.available_categories.map(c => c.id);
                    return result.message || 'File validated successfully';
                },
                error: (err) => `File validation failed: ${err.message}`
            }
        );
    } catch (err) {
        console.error('File validation error:', err);
        importState.uploadedFile = null;
    }
};

// Preview import
const previewImport = async () => {
    if (!importState.uploadedFile || importState.selectedCategories.length === 0) {
        error('Please select a file and at least one category to preview.');
        return;
    }

    const formData = new FormData();
    formData.append('config_file', importState.uploadedFile);
    formData.append('categories', JSON.stringify(importState.selectedCategories));

    try {
        importState.isValidating = true;
        message.value = null;

        const result = await promise(
            fetch('/api/settings/preview-import', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: formData,
            }).then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || 'Preview failed')
                    })
                }
                return response.json()
            }),
            {
                loading: 'Generating import preview...',
                success: (result) => {
                    importState.previewData = result.preview;
                    return result.message || 'Preview generated successfully';
                },
                error: (err) => `Preview failed: ${err.message}`
            }
        );
    } catch (err) {
        console.error('Preview error:', err);
    } finally {
        importState.isValidating = false;
    }
};

// Apply import
const applyImport = async () => {
    if (!importState.uploadedFile || importState.selectedCategories.length === 0 || !importState.password) {
        error('Please provide a file, select categories, and enter your password to import.');
        return;
    }

    const formData = new FormData();
    formData.append('config_file', importState.uploadedFile);
    formData.append('categories', JSON.stringify(importState.selectedCategories));
    formData.append('password', importState.password);
    formData.append('overwrite_existing', importState.overwriteExisting ? '1' : '0');

    try {
        importState.isImporting = true;
        message.value = null;

        await promise(
            fetch('/api/settings/import', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: formData,
            }).then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || 'Import failed')
                    })
                }
                return response.json()
            }),
            {
                loading: 'Importing configuration...',
                success: (result) => {
                    // Reset import state after successful import
                    setTimeout(() => {
                        importState.uploadedFile = null;
                        importState.availableCategories = [];
                        importState.selectedCategories = [];
                        importState.previewData = null;
                        importState.password = '';
                        importState.overwriteExisting = false;
                    }, 1000);
                    return result.message || 'Configuration imported successfully';
                },
                error: (err) => `Import failed: ${err.message}`
            }
        );
    } catch (err) {
        console.error('Import error:', err);
    } finally {
        importState.isImporting = false;
    }
};

// Close preview modal
const closePreview = () => {
    importState.previewData = null;
};

// Format value for display
const formatValue = (value) => {
    if (typeof value === 'boolean') return value ? 'true' : 'false';
    if (typeof value === 'object') return JSON.stringify(value);
    if (typeof value === 'string' && value.length > 50) return value.substring(0, 47) + '...';
    return String(value);
};
</script>