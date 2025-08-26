<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Page Header -->
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">
                            Import Management
                        </h1>
                        <p class="mt-1 text-sm text-gray-600">
                            Import data from external systems with PostgreSQL
                            database connections
                        </p>
                    </div>
                    <button
                        @click="showCreateProfile = true"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        <ArrowDownTrayIcon class="-ml-1 mr-2 h-4 w-4" />
                        Create Import Profile
                    </button>
                </div>
            </div>

            <!-- Import Methods -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- PostgreSQL Database Import -->
                <div class="bg-white shadow rounded-lg p-6">
                    <div class="flex items-center mb-4">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">Database Import</h3>
                            <p class="text-sm text-gray-600">Import from PostgreSQL databases</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-500 mb-4">
                        Connect to PostgreSQL databases and import data using visual query builder or pre-built templates.
                    </p>
                    <div class="text-sm text-gray-600">
                        <div class="flex items-center mb-1">
                            <svg class="h-4 w-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Visual query builder
                        </div>
                        <div class="flex items-center mb-1">
                            <svg class="h-4 w-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Real-time monitoring
                        </div>
                        <div class="flex items-center">
                            <svg class="h-4 w-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Duplicate detection
                        </div>
                    </div>
                </div>

                <!-- FreeScout API Import -->
                <div class="bg-white shadow rounded-lg p-6">
                    <div class="flex items-center mb-4">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">FreeScout API</h3>
                            <p class="text-sm text-gray-600">Import from FreeScout instances</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-500 mb-4">
                        Import conversations, customers, and mailboxes from FreeScout helpdesk systems via REST API.
                    </p>
                    <div class="text-sm text-gray-600 mb-4">
                        <div class="flex items-center mb-1">
                            <svg class="h-4 w-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            API key authentication
                        </div>
                        <div class="flex items-center mb-1">
                            <svg class="h-4 w-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Account mapping strategies
                        </div>
                        <div class="flex items-center">
                            <svg class="h-4 w-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Import limits & filtering
                        </div>
                    </div>
                    <Link
                        :href="route('import.freescout-api')"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
                        </svg>
                        Configure FreeScout API
                    </Link>
                </div>
            </div>

            <!-- Import Profiles Section -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">
                        Import Profiles
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Manage database connections and import configurations
                    </p>
                </div>

                <div class="p-6">
                    <div v-if="profilesLoading" class="text-center py-8">
                        <div
                            class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"
                        ></div>
                        <p class="mt-2 text-sm text-gray-600">
                            Loading import profiles...
                        </p>
                    </div>

                    <div
                        v-else-if="profiles?.length === 0"
                        class="text-center py-8"
                    >
                        <ArrowDownTrayIcon
                            class="mx-auto h-12 w-12 text-gray-400"
                        />
                        <h3 class="mt-2 text-sm font-medium text-gray-900">
                            No import profiles
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Get started by creating your first import profile.
                        </p>
                        <div class="mt-6">
                            <button
                                @click="showCreateProfile = true"
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"
                            >
                                <ArrowDownTrayIcon class="-ml-1 mr-2 h-4 w-4" />
                                Create Import Profile
                            </button>
                        </div>
                    </div>

                    <div
                        v-else
                        class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
                    >
                        <div
                            v-for="profile in profiles"
                            :key="profile.id"
                            class="relative group bg-white border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="flex-shrink-0">
                                        <div
                                            class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center"
                                        >
                                            <CircleStackIcon
                                                class="w-6 h-6 text-indigo-600"
                                            />
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div
                                            class="flex items-center space-x-2"
                                        >
                                            <p
                                                class="text-sm font-medium text-gray-900 truncate"
                                            >
                                                {{ profile.name }}
                                            </p>
                                            <div
                                                v-if="
                                                    profile.has_custom_queries
                                                "
                                                class="flex-shrink-0"
                                            >
                                                <span
                                                    class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                                                    title="Has saved custom query"
                                                >
                                                    <CogIcon class="w-3 h-3" />
                                                </span>
                                            </div>
                                            <div
                                                v-if="profile.template_id"
                                                class="flex-shrink-0"
                                            >
                                                <span
                                                    class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                                                    title="Uses template"
                                                >
                                                    <DocumentTextIcon
                                                        class="w-3 h-3"
                                                    />
                                                </span>
                                            </div>
                                        </div>
                                        <p
                                            class="text-xs text-gray-500 uppercase tracking-wide"
                                        >
                                            {{ profile.database_type }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Actions dropdown -->
                                <div class="relative">
                                    <button
                                        @click="toggleProfileMenu(profile.id)"
                                        class="p-1 rounded-full text-gray-400 hover:text-gray-600 hover:bg-gray-100"
                                    >
                                        <EllipsisVerticalIcon class="w-5 h-5" />
                                    </button>

                                    <div
                                        v-if="activeProfileMenu === profile.id"
                                        class="origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10"
                                    >
                                        <div class="py-1">
                                            <!-- Template Configuration -->
                                            <div
                                                class="px-4 py-2 text-xs font-medium text-gray-500 uppercase tracking-wide"
                                            >
                                                Template Configuration
                                            </div>
                                            <button
                                                @click="
                                                    openTemplateSelector(
                                                        profile
                                                    )
                                                "
                                                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                            >
                                                <DocumentTextIcon
                                                    class="inline w-4 h-4 mr-2"
                                                />
                                                Apply Template
                                            </button>
                                            <button
                                                @click="
                                                    openQueryBuilder(profile)
                                                "
                                                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                            >
                                                <CogIcon
                                                    class="inline w-4 h-4 mr-2"
                                                />
                                                Query Builder
                                            </button>

                                            <!-- Divider -->
                                            <div
                                                class="border-t border-gray-100 my-1"
                                            ></div>

                                            <!-- Import Actions -->
                                            <div
                                                class="px-4 py-2 text-xs font-medium text-gray-500 uppercase tracking-wide"
                                            >
                                                Import Actions
                                            </div>
                                            <button
                                                @click="
                                                    openImportWizard(profile)
                                                "
                                                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                            >
                                                <EyeIcon
                                                    class="inline w-4 h-4 mr-2"
                                                />
                                                Preview Import Data
                                            </button>
                                            <button
                                                @click="executeImport(profile)"
                                                :disabled="
                                                    !profile.template_id &&
                                                    !profile.has_custom_queries
                                                "
                                                class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                                                :class="
                                                    profile.template_id ||
                                                    profile.has_custom_queries
                                                        ? 'text-gray-700'
                                                        : 'text-gray-400'
                                                "
                                            >
                                                <PlayIcon
                                                    class="inline w-4 h-4 mr-2"
                                                />
                                                Execute Import
                                            </button>
                                            <button
                                                @click="editProfile(profile)"
                                                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                            >
                                                <PencilIcon
                                                    class="inline w-4 h-4 mr-2"
                                                />
                                                Edit Profile
                                            </button>
                                            <button
                                                @click="openDuplicateModal(profile)"
                                                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                            >
                                                <DocumentDuplicateIcon
                                                    class="inline w-4 h-4 mr-2"
                                                />
                                                Duplicate Profile
                                            </button>
                                            <button
                                                @click="deleteProfile(profile)"
                                                class="w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-red-50"
                                            >
                                                <TrashIcon
                                                    class="inline w-4 h-4 mr-2"
                                                />
                                                Delete Profile
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <div
                                    class="flex items-center text-xs text-gray-500 space-x-4"
                                >
                                    <span class="flex items-center">
                                        <ServerIcon class="w-3 h-3 mr-1" />
                                        {{ profile.host }}:{{ profile.port }}
                                    </span>
                                    <span class="flex items-center">
                                        <CircleStackIcon class="w-3 h-3 mr-1" />
                                        {{ profile.database }}
                                    </span>
                                </div>
                                <p
                                    v-if="profile.description"
                                    class="mt-2 text-xs text-gray-600 line-clamp-2"
                                >
                                    {{ profile.description }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Import Jobs Section -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">
                        Recent Import Jobs
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Track import job progress and results
                    </p>
                </div>

                <div class="p-6">
                    <div v-if="jobsLoading" class="text-center py-8">
                        <div
                            class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"
                        ></div>
                        <p class="mt-2 text-sm text-gray-600">
                            Loading recent jobs...
                        </p>
                    </div>

                    <div
                        v-else-if="jobs?.length === 0"
                        class="text-center py-8"
                    >
                        <ClockIcon class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900">
                            No import jobs
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Import jobs will appear here once you start
                            importing data.
                        </p>
                    </div>

                    <div v-else class="overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Profile
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Status
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Progress
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Records
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Started
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="job in jobs" :key="job.id">
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                    >
                                        {{ job.profile?.name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            :class="
                                                getJobStatusClass(job.status)
                                            "
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        >
                                            {{ job.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div
                                                class="w-full bg-gray-200 rounded-full h-2 mr-2"
                                            >
                                                <div
                                                    :class="
                                                        getProgressBarClass(
                                                            job.status
                                                        )
                                                    "
                                                    class="h-2 rounded-full transition-all duration-300"
                                                    :style="{
                                                        width: `${
                                                            job.progress_percentage ||
                                                            0
                                                        }%`,
                                                    }"
                                                ></div>
                                            </div>
                                            <span
                                                class="text-xs text-gray-600 min-w-0 whitespace-nowrap"
                                            >
                                                {{
                                                    job.progress_percentage ||
                                                    0
                                                }}%
                                            </span>
                                        </div>
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                    >
                                        <div class="space-y-1">
                                            <div class="text-xs text-green-600">
                                                {{
                                                    job.records_imported || 0
                                                }}
                                                imported
                                            </div>
                                            <div
                                                v-if="job.records_failed > 0"
                                                class="text-xs text-red-600"
                                            >
                                                {{
                                                    job.records_failed || 0
                                                }}
                                                failed
                                            </div>
                                            <div
                                                v-if="job.records_skipped > 0"
                                                class="text-xs text-yellow-600"
                                            >
                                                {{
                                                    job.records_skipped || 0
                                                }}
                                                skipped
                                            </div>
                                        </div>
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"
                                    >
                                        {{ formatDate(job.started_at) }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
                                    >
                                        <button
                                            v-if="job.status === 'running'"
                                            @click="cancelJob(job)"
                                            class="text-red-600 hover:text-red-900 mr-3"
                                        >
                                            Cancel
                                        </button>
                                        <button
                                            @click="viewJobDetails(job)"
                                            class="text-indigo-600 hover:text-indigo-900"
                                        >
                                            View Details
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Profile Modal -->
        <ImportProfileModal
            :show="showCreateProfile"
            :profile="selectedProfile"
            @close="showCreateProfile = false"
            @saved="handleProfileSaved"
        />


        <!-- Real-time Job Monitor -->
        <ImportJobMonitor
            :auto-show="true"
            @job-details="handleJobDetails"
            @monitor-closed="handleMonitorClosed"
        />

        <!-- Job Details Modal -->
        <ImportJobDetailsModal
            :show="showJobDetails"
            :job="selectedJob"
            @close="showJobDetails = false"
        />

        <!-- Query Builder Modal -->
        <QueryBuilderModal
            :show="showQueryBuilder"
            :profile="selectedProfile"
            :initial-config="selectedProfile?.configuration || {}"
            @close="showQueryBuilder = false"
            @query-saved="handleQuerySaved"
            @execution-started="handleExecutionStarted"
        />

        <!-- Template Selector Modal -->
        <TemplateSelector
            :show="showTemplateSelector"
            :profile="selectedProfile"
            @close="showTemplateSelector = false"
            @template-applied="handleTemplateApplied"
        />

        <!-- Import Execution Dialog -->
        <ImportExecutionDialog
            :show="showExecutionDialog"
            :is-executing="executionState.isExecuting"
            :progress="executionState.progress"
            :message="executionState.message"
            :is-success="executionState.isSuccess"
            :is-error="executionState.isError"
            :error-details="executionState.errorDetails"
            :job-id="executionState.jobId"
            :estimated-records="executionState.estimatedRecords"
            @close="handleExecutionDialogClose"
            @view-jobs="handleExecutionViewJobs"
        />

        <!-- Duplicate Profile Modal -->
        <DuplicateProfileModal
            :show="showDuplicateModal"
            :profile="selectedProfile"
            @close="showDuplicateModal = false"
            @duplicated="handleProfileDuplicated"
        />

        <!-- Unified Import Preview Modal -->
        <QueryImportPreviewModal
            :show="showUnifiedPreview"
            :profile="selectedProfile"
            :query-config="selectedProfile?.configuration || {}"
            :import-type="previewImportType"
            @close="showUnifiedPreview = false"
            @executed="handlePreviewExecuted"
            @execution-started="handleExecutionStarted"
        />

        <!-- Success Notification -->
        <SuccessNotification
            :show="showSuccessNotification"
            :title="successNotification.title"
            :message="successNotification.message"
            @dismiss="showSuccessNotification = false"
        />
    </AppLayout>
</template>

<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";
import { Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import ImportProfileModal from "./Components/ImportProfileModal.vue";
import ImportJobDetailsModal from "./Components/ImportJobDetailsModal.vue";
import DuplicateProfileModal from "./Components/DuplicateProfileModal.vue";
import QueryBuilderModal from "@/Components/Import/QueryBuilderModal.vue";
import TemplateSelector from "@/Components/Import/TemplateSelector.vue";
import SuccessNotification from "@/Components/SuccessNotification.vue";
import ImportJobMonitor from "@/Components/Import/ImportJobMonitor.vue";
import ImportExecutionDialog from "@/Components/Import/ImportExecutionDialog.vue";
import QueryImportPreviewModal from "./Components/QueryImportPreviewModal.vue";
import {
    ArrowDownTrayIcon,
    CircleStackIcon,
    EllipsisVerticalIcon,
    PlayIcon,
    PencilIcon,
    TrashIcon,
    DocumentDuplicateIcon,
    ServerIcon,
    CogIcon,
    EyeIcon,
    DocumentTextIcon,
    ClockIcon,
} from "@heroicons/vue/24/outline";
import { useImportQueries } from "@/Composables/queries/useImportQueries.js";

// Reactive state
const showCreateProfile = ref(false);
const showJobDetails = ref(false);
const showQueryBuilder = ref(false);
const showTemplateSelector = ref(false);
const showDuplicateModal = ref(false);
const showSuccessNotification = ref(false);
const showExecutionDialog = ref(false);
const showUnifiedPreview = ref(false);
const previewImportType = ref('auto');
const selectedProfile = ref(null);
const selectedJob = ref(null);
const activeProfileMenu = ref(null);
const successNotification = ref({
    title: "",
    message: "",
});
const executionState = ref({
    isExecuting: false,
    progress: 0,
    message: '',
    isSuccess: false,
    isError: false,
    errorDetails: '',
    jobId: '',
    estimatedRecords: 0
});

// Use import queries composable
const {
    profiles,
    profilesLoading,
    jobs,
    jobsLoading,
    refreshProfiles,
    refreshJobs,
    executeImport: executeImportMutation,
    deleteProfile: deleteProfileMutation,
    cancelJob: cancelJobMutation,
    fetchProfile,
} = useImportQueries();

// Load data on mount
onMounted(() => {
    refreshProfiles();
    refreshJobs();
});

// Methods
const toggleProfileMenu = (profileId) => {
    activeProfileMenu.value =
        activeProfileMenu.value === profileId ? null : profileId;
};

const openImportWizard = (profile) => {
    selectedProfile.value = profile;
    previewImportType.value = 'template'; // Explicitly set as template-based import
    showUnifiedPreview.value = true;
    activeProfileMenu.value = null;
};

const introspectEmails = async (profile) => {
    try {
        console.log("Starting email introspection for profile:", profile.name);
        const response = await fetch(
            `/api/import/profiles/${profile.id}/introspect-emails`,
            {
                method: "GET",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                },
            }
        );

        const result = await response.json();

        if (response.ok) {
            console.log("Email introspection results:", result);

            // Display results in browser console for debugging
            console.log("=== EMAIL TABLES FOUND ===");
            Object.entries(result.email_tables || {}).forEach(
                ([tableName, tableData]) => {
                    console.log(`Table: ${tableName}`);
                    console.log(
                        `  Email columns:`,
                        tableData.email_columns.map((col) => col.name)
                    );
                    console.log(
                        `  Customer related:`,
                        tableData.customer_related
                    );
                    console.log(`  Row count:`, tableData.row_count);
                    console.log(
                        `  Sample data:`,
                        tableData.sample_data.slice(0, 2)
                    );
                }
            );

            console.log("=== FOREIGN KEYS ===");
            result.foreign_keys?.forEach((fk) => {
                console.log(
                    `${fk.table_name}.${fk.column_name} → ${fk.foreign_table_name}.${fk.foreign_column_name}`
                );
            });

            console.log("=== ANALYSIS ===");
            result.analysis?.recommendations?.forEach((rec) =>
                console.log(`• ${rec}`)
            );

            if (result.analysis?.customer_email_join) {
                console.log(
                    "Suggested customer-email join:",
                    result.analysis.customer_email_join
                );
            }
        } else {
            console.error("Email introspection failed:", result);
        }
    } catch (error) {
        console.error("Error during email introspection:", error);
    }
};

const introspectTimeTracking = async (profile) => {
    try {
        console.log(
            "Starting time tracking introspection for profile:",
            profile.name
        );
        const response = await fetch(
            `/api/import/profiles/${profile.id}/introspect-time-tracking`,
            {
                method: "GET",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                },
            }
        );

        const result = await response.json();

        if (response.ok) {
            console.log("Time tracking introspection results:", result);

            // Display results in browser console for debugging
            console.log("=== TIME TRACKING TABLES FOUND ===");
            Object.entries(result.time_tables || {}).forEach(
                ([tableName, tableData]) => {
                    console.log(
                        `Table: ${tableName} (${tableData.confidence} confidence)`
                    );
                    console.log(`  Time columns:`, tableData.time_columns);
                    console.log(
                        `  Duration columns:`,
                        tableData.duration_columns.map((col) => col.name)
                    );
                    console.log(`  User related:`, tableData.user_related);
                    console.log(
                        `  Conversation related:`,
                        tableData.conversation_related
                    );
                    console.log(`  Row count:`, tableData.row_count);
                    console.log(
                        `  Sample data:`,
                        tableData.sample_data.slice(0, 2)
                    );
                }
            );

            console.log("=== FOREIGN KEYS ===");
            result.foreign_keys?.forEach((fk) => {
                console.log(
                    `${fk.table_name}.${fk.column_name} → ${fk.foreign_table_name}.${fk.foreign_column_name}`
                );
            });

            console.log("=== ANALYSIS ===");
            result.analysis?.recommendations?.forEach((rec) =>
                console.log(`• ${rec}`)
            );

            if (result.analysis?.likely_time_table) {
                console.log(
                    "Most likely time table:",
                    result.analysis.likely_time_table
                );
                console.log(
                    "Suggested field mappings:",
                    result.analysis.suggested_mappings
                );
            }
        } else {
            console.error("Time tracking introspection failed:", result);
        }
    } catch (error) {
        console.error("Error during time tracking introspection:", error);
    }
};

const executeImport = async (profile) => {
    // Direct import execution
    try {
        await executeImportMutation(profile.id);
        refreshJobs();
    } catch (error) {
        console.error("Import execution failed:", error);
    }
    activeProfileMenu.value = null;
};

const openTemplateSelector = (profile) => {
    selectedProfile.value = profile;
    showTemplateSelector.value = true;
    activeProfileMenu.value = null;
};

const openDuplicateModal = (profile) => {
    selectedProfile.value = profile;
    showDuplicateModal.value = true;
    activeProfileMenu.value = null;
};

const openQueryBuilder = async (profile) => {
    try {
        // Fetch latest profile data to ensure we have the most recent configuration
        const latestProfile = await fetchProfile(profile.id);
        selectedProfile.value = latestProfile;
        showQueryBuilder.value = true;
        activeProfileMenu.value = null;
    } catch (error) {
        console.error("Error fetching profile for query builder:", error);
        // Fallback to using cached profile if fetch fails
        selectedProfile.value = profile;
        showQueryBuilder.value = true;
        activeProfileMenu.value = null;
    }
};

const editProfile = (profile) => {
    selectedProfile.value = profile;
    showCreateProfile.value = true;
    activeProfileMenu.value = null;
};

const deleteProfile = async (profile) => {
    if (
        confirm(
            `Are you sure you want to delete the import profile "${profile.name}"?`
        )
    ) {
        try {
            await deleteProfileMutation(profile.id);
            refreshProfiles();
        } catch (error) {
            console.error("Delete failed:", error);
        }
    }
    activeProfileMenu.value = null;
};

const cancelJob = async (job) => {
    try {
        await cancelJobMutation(job.id);
        refreshJobs();
    } catch (error) {
        console.error("Cancel failed:", error);
    }
};

const viewJobDetails = (job) => {
    selectedJob.value = job;
    showJobDetails.value = true;
};

const handleProfileSaved = () => {
    showCreateProfile.value = false;
    selectedProfile.value = null;
    refreshProfiles();
};

const showSuccessMessage = (title, message) => {
    successNotification.value = { title, message };
    showSuccessNotification.value = true;
};

const handleQuerySaved = (result) => {
    showQueryBuilder.value = false;
    selectedProfile.value = null;
    refreshProfiles();
    showSuccessMessage(
        "Query Saved",
        "Your custom query configuration has been saved successfully!"
    );
};

const handleTemplateApplied = ({ profile, template }) => {
    showTemplateSelector.value = false;
    selectedProfile.value = null;
    refreshProfiles();
    showSuccessMessage(
        "Template Applied",
        `Template "${template.name}" has been applied to profile "${profile.name}"`
    );
};

const handleProfileDuplicated = ({ message, profile }) => {
    showDuplicateModal.value = false;
    selectedProfile.value = null;
    refreshProfiles();
    showSuccessMessage(
        "Profile Duplicated",
        `New profile "${profile.name}" has been created successfully`
    );
};


// Job monitor event handlers
const handleJobDetails = (job) => {
    viewJobDetails(job);
};

const handleMonitorClosed = () => {
    // Optional: Could track monitor state if needed
    console.log("Import job monitor closed");
};

// Helper methods
const getJobStatusClass = (status) => {
    switch (status) {
        case "completed":
            return "bg-green-100 text-green-800";
        case "failed":
            return "bg-red-100 text-red-800";
        case "running":
            return "bg-blue-100 text-blue-800";
        case "cancelled":
            return "bg-gray-100 text-gray-800";
        default:
            return "bg-yellow-100 text-yellow-800";
    }
};

const getProgressBarClass = (status) => {
    switch (status) {
        case "completed":
            return "bg-green-600";
        case "failed":
            return "bg-red-600";
        case "running":
            return "bg-blue-600";
        default:
            return "bg-gray-600";
    }
};

const formatDate = (dateString) => {
    if (!dateString) return "-";
    return (
        new Date(dateString).toLocaleDateString() +
        " " +
        new Date(dateString).toLocaleTimeString()
    );
};

// Execution Dialog handlers
const handleExecutionStarted = (executionData) => {
    executionState.value = {
        isExecuting: !executionData.error,
        progress: executionData.error ? 0 : 20,
        message: executionData.message,
        isSuccess: false,
        isError: !!executionData.error,
        errorDetails: executionData.errorDetails || '',
        jobId: executionData.job_id || '',
        estimatedRecords: executionData.estimatedRecords || 0
    };
    
    showExecutionDialog.value = true;
    
    if (!executionData.error) {
        // Simulate progress for successful job creation
        setTimeout(() => {
            executionState.value.progress = 100;
            executionState.value.isExecuting = false;
            executionState.value.isSuccess = true;
            executionState.value.message = 'Import job started successfully!';
        }, 1500);
    }
};

const handleExecutionDialogClose = () => {
    showExecutionDialog.value = false;
    // Reset execution state after a delay
    setTimeout(() => {
        executionState.value = {
            isExecuting: false,
            progress: 0,
            message: '',
            isSuccess: false,
            isError: false,
            errorDetails: '',
            jobId: '',
            estimatedRecords: 0
        };
    }, 300);
};

const handleExecutionViewJobs = () => {
    showExecutionDialog.value = false;
    refreshJobs(); // Refresh the jobs list to show the new job
};

const handlePreviewExecuted = (executionData) => {
    // Handle the legacy executed event from unified preview
    console.log('Unified preview executed:', executionData);
    refreshJobs(); // Refresh the jobs list
    showSuccessMessage(
        "Import Started",
        `Import job has been started successfully`
    );
};
</script>
