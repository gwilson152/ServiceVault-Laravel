<template>
    <StandardPageLayout
        title="Time & Addons Management"
        subtitle="View and manage time entries, ticket addons, and active timers."
        :show-sidebar="false"
        :show-filters="false"
    >
        <template #header-actions>
            <!-- Approval Wizard Button -->
            <button
                v-if="canApprove"
                @click="showAccountSelector = true"
                class="inline-flex items-center px-4 py-2 border border-yellow-300 text-sm font-medium rounded-md text-yellow-700 bg-yellow-50 hover:bg-yellow-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500"
            >
                <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Approval Wizard
            </button>
            
            <button
                v-if="activeTab === 'time-entries'"
                @click="showCreateModal = true"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
                <svg
                    class="-ml-1 mr-2 h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 4v16m8-8H4"
                    />
                </svg>
                Add Time Entry
            </button>
        </template>

        <template #tabs>
            <TabNavigation
                v-model="activeTab"
                :tabs="navigationTabs"
                variant="underline"
                @tab-change="handleTabChange"
            />
        </template>

        <template #main-content>
            <!-- Time Entries Tab -->
            <div v-if="activeTab === 'time-entries'">
                <!-- Statistics Grid -->
                <div v-if="stats" class="mb-8">
                    <StatsGrid 
                        :stats="timeEntriesStatsForGrid" 
                        :columns="4"
                        :show-icons="true"
                    />
                </div>

                <!-- Filters -->
                <div class="bg-white shadow rounded-lg mb-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Filters</h3>
                    </div>
                    <div class="p-6">
                        <div class="grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-4">
                            
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                    >Status</label
                                >
                                <select
                                    v-model="filters.status"
                                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                >
                                    <option value="">All Statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                    >Billable</label
                                >
                                <select
                                    v-model="filters.billable"
                                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                >
                                    <option value="">All Entries</option>
                                    <option value="true">Billable Only</option>
                                    <option value="false">Non-billable Only</option>
                                </select>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                    >Date From</label
                                >
                                <input
                                    v-model="filters.date_from"
                                    type="date"
                                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                />
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                    >Date To</label
                                >
                                <input
                                    v-model="filters.date_to"
                                    type="date"
                                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                />
                            </div>
                        </div>

                        <div class="mt-4 flex justify-end space-x-3">
                            <button
                                @click="clearFilters"
                                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                Clear Filters
                            </button>
                            <button
                                v-if="selectedEntries.length > 0 && canApprove"
                                @click="showBulkApprovalModal = true"
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                            >
                                Approve Selected ({{ selectedEntries.length }})
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Time Entries Table -->
                <div class="bg-white shadow overflow-hidden sm:rounded-md">
                    <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Time Entries
                        </h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">
                            {{ timeEntries.total || 0 }} total entries
                            <span
                                v-if="timeEntries.total !== (timeEntries.data?.length || 0)"
                            >
                                (showing {{ timeEntries.data?.length || 0 }})
                            </span>
                        </p>
                    </div>

                    <div v-if="loading" class="flex justify-center py-12">
                        <svg
                            class="animate-spin h-8 w-8 text-indigo-600"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                    </div>

                    <ul
                        v-else-if="timeEntries.data?.length > 0"
                        class="divide-y divide-gray-200"
                    >
                        <li
                            v-for="entry in timeEntries.data"
                            :key="entry.id"
                            class="px-4 py-4 sm:px-6 hover:bg-gray-50"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center min-w-0">
                                    <input
                                        v-if="
                                            canApprove && entry.status === 'pending'
                                        "
                                        v-model="selectedEntries"
                                        :value="entry.id"
                                        type="checkbox"
                                        class="mr-4 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center">
                                            <p
                                                class="text-sm font-medium text-gray-900 truncate"
                                            >
                                                {{ entry.description }}
                                            </p>
                                            <span
                                                :class="[
                                                    'ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                                    entry.status === 'approved'
                                                        ? 'bg-green-100 text-green-800'
                                                        : entry.status ===
                                                          'rejected'
                                                        ? 'bg-red-100 text-red-800'
                                                        : 'bg-yellow-100 text-yellow-800',
                                                ]"
                                            >
                                                {{ entry.status }}
                                            </span>
                                            <span
                                                v-if="entry.billable"
                                                class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                                            >
                                                Billable
                                            </span>
                                        </div>
                                        <div
                                            class="mt-1 flex items-center text-sm text-gray-500"
                                        >
                                            <p>
                                                {{ entry.duration_formatted }} •
                                                {{ formatDate(entry.started_at) }}
                                            </p>
                                            <span v-if="entry.account" class="mx-2"
                                                >•</span
                                            >
                                            <p v-if="entry.account">
                                                {{ entry.account.name }}
                                            </p>
                                            <span
                                                v-if="entry.calculated_cost"
                                                class="mx-2"
                                                >•</span
                                            >
                                            <p
                                                v-if="entry.calculated_cost"
                                                class="font-medium"
                                            >
                                                ${{ entry.calculated_cost }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button
                                        v-if="entry.can_edit"
                                        @click="editEntry(entry)"
                                        class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        v-if="
                                            canApprove && entry.status === 'pending'
                                        "
                                        @click="approveEntryHandler(entry)"
                                        class="text-green-600 hover:text-green-900 text-sm font-medium"
                                    >
                                        Approve
                                    </button>
                                    <button
                                        v-if="
                                            canApprove && entry.status === 'pending'
                                        "
                                        @click="rejectEntryHandler(entry)"
                                        class="text-red-600 hover:text-red-900 text-sm font-medium"
                                    >
                                        Reject
                                    </button>
                                </div>
                            </div>
                        </li>
                    </ul>

                    <div v-else class="text-center py-12">
                        <svg
                            class="mx-auto h-12 w-12 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">
                            No time entries
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Get started by adding your first time entry.
                        </p>
                        <div class="mt-6">
                            <button
                                @click="showCreateModal = true"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                Add Time Entry
                            </button>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="timeEntries.data?.length > 0"
                        class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <button
                                    v-if="timeEntries.prev_page_url"
                                    @click="loadPage(timeEntries.current_page - 1)"
                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    Previous
                                </button>
                                <button
                                    v-if="timeEntries.next_page_url"
                                    @click="loadPage(timeEntries.current_page + 1)"
                                    class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    Next
                                </button>
                            </div>
                            <div
                                class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between"
                            >
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Showing
                                        <span class="font-medium">{{
                                            timeEntries.from
                                        }}</span>
                                        to
                                        <span class="font-medium">{{
                                            timeEntries.to
                                        }}</span>
                                        of
                                        <span class="font-medium">{{
                                            timeEntries.total
                                        }}</span>
                                        results
                                    </p>
                                </div>
                                <div>
                                    <nav
                                        class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px"
                                        aria-label="Pagination"
                                    >
                                        <button
                                            v-if="timeEntries.prev_page_url"
                                            @click="
                                                loadPage(
                                                    timeEntries.current_page - 1
                                                )
                                            "
                                            class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                                        >
                                            Previous
                                        </button>
                                        <button
                                            v-if="timeEntries.next_page_url"
                                            @click="
                                                loadPage(
                                                    timeEntries.current_page + 1
                                                )
                                            "
                                            class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                                        >
                                            Next
                                        </button>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Addons Tab -->
            <div v-else-if="activeTab === 'addons'">
                <AddonsTab />
            </div>

            <!-- Timers Tab -->
            <div v-else-if="activeTab === 'timers'">
                <TimersTab @timer-committed="onTimerCommitted" />
            </div>
        </template>
    </StandardPageLayout>

    <!-- Unified Time Entry Dialog -->
    <UnifiedTimeEntryDialog
        :show="showCreateModal || !!editingEntry"
        :mode="editingEntry ? 'edit' : 'create'"
        :time-entry="editingEntry"
        :context-account="editingEntry?.account"
        :context-ticket="editingEntry?.ticket"
        @close="closeModal"
        @saved="handleTimeEntrySaved"
    />

    <!-- Bulk Approval Modal -->
    <div
        v-if="showBulkApprovalModal"
        class="fixed inset-0 z-10 overflow-y-auto"
    >
        <div
            class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0"
        >
            <div
                class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                @click="showBulkApprovalModal = false"
            ></div>
            <div
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
            >
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Bulk Approve Time Entries
                    </h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Are you sure you want to approve
                        {{ selectedEntries.length }} time entries?
                    </p>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700"
                            >Notes (optional)</label
                        >
                        <textarea
                            v-model="bulkApprovalNotes"
                            rows="3"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="Add approval notes..."
                        ></textarea>
                    </div>
                </div>
                <div
                    class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse"
                >
                    <button
                        @click="bulkApprove"
                        :disabled="isBulkApproving"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                    >
                        {{ isBulkApproving ? "Processing..." : "Approve All" }}
                    </button>
                    <button
                        @click="showBulkApprovalModal = false"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Account Selector Modal for Approval Wizard -->
    <StackedDialog
        :show="showAccountSelector"
        title="Select Account for Approval Review"
        max-width="lg"
        :allow-dropdowns="true"
        @close="showAccountSelector = false"
    >
        <template #header-subtitle>
            <p class="mt-1 text-sm text-gray-600">
                Choose an account to review and approve pending time entries and addons, or select "All Accounts" to review items across all accounts.
            </p>
        </template>

        <div class="space-y-6">
            <!-- All Accounts Option -->
            <div>
                <button
                    @click="selectAllAccounts"
                    class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    All Accounts
                </button>
            </div>
            
            <div class="relative">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-300" />
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-white text-gray-500">or select specific account</span>
                </div>
            </div>
            
            <div>
                <UnifiedSelector
                    v-model="selectedAccountId"
                    type="account"
                    label="Specific Account"
                    placeholder="Select an account..."
                    :clearable="true"
                    @item-selected="onAccountSelectedForApproval"
                />
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end space-x-3">
                <button
                    @click="showAccountSelector = false"
                    class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    Cancel
                </button>
                <button
                    @click="launchApprovalWizard"
                    :disabled="!selectedAccountForApproval"
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Launch Approval Wizard
                </button>
            </div>
        </template>
    </StackedDialog>

    <!-- Approval Wizard Modal -->
    <ApprovalWizardModal
        :show="showApprovalWizard"
        :account-id="selectedAccountForApproval?.id || ''"
        :account-name="selectedAccountForApproval?.name || ''"
        @close="closeApprovalWizard"
        @completed="onApprovalWizardCompleted"
        @createInvoice="onCreateInvoiceFromWizard"
    />
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import StandardPageLayout from "@/Layouts/StandardPageLayout.vue";
import TabNavigation from "@/Components/Layout/TabNavigation.vue";
import StatsGrid from "@/Components/Layout/StatsGrid.vue";
import { usePage, Link, router } from "@inertiajs/vue3";
import TimersTab from "@/Components/TimeEntries/TimersTab.vue";
import AddonsTab from "@/Components/TimeEntries/AddonsTab.vue";
import UnifiedTimeEntryDialog from "@/Components/TimeEntries/UnifiedTimeEntryDialog.vue";
import ApprovalWizardModal from "@/Components/Billing/ApprovalWizardModal.vue";
import UnifiedSelector from "@/Components/UI/UnifiedSelector.vue";
import StackedDialog from "@/Components/StackedDialog.vue";
import { useTimeEntriesQuery } from "@/Composables/queries/useTimeEntriesQuery.js";

// Props
const props = defineProps({
    activeTab: {
        type: String,
        default: "time-entries",
    },
});

// Define persistent layout
defineOptions({
    layout: (h, page) => h(AppLayout, () => page),
});

// Navigation tabs configuration
const navigationTabs = computed(() => [
    {
        id: 'time-entries',
        name: 'Time Entries',
        icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'
    },
    {
        id: 'addons',
        name: 'Ticket Addons',
        icon: 'M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'
    },
    {
        id: 'timers',
        name: 'Active Timers',
        icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'
    }
]);

const page = usePage();
const user = computed(() => page.props.auth?.user);

// Initialize TanStack Query composable
const {
    useTimeEntriesListQuery,
    timeEntriesStats,
    loadingStats,
    statsError,
    refetchStats,
    approveTimeEntry,
    rejectTimeEntry,
    unapproveTimeEntry,
    bulkApproveTimeEntries,
    isBulkApproving,
    createTimeEntryError,
    updateTimeEntryError,
} = useTimeEntriesQuery();

// Reactive state
const activeTab = ref(props.activeTab || "time-entries");
const selectedEntries = ref([]);
const showCreateModal = ref(false);
const showBulkApprovalModal = ref(false);
const editingEntry = ref(null);
const bulkApprovalNotes = ref("");
const currentPage = ref(1);

// Tab change handler
const handleTabChange = (tabId) => {
    router.visit(route('time-and-addons.index', tabId), {
        preserveState: true,
        preserveScroll: true
    });
};

// Statistics data for StatsGrid component
const timeEntriesStatsForGrid = computed(() => {
    if (!stats.value) return [];
    
    return [
        {
            label: 'Total Hours (30d)',
            value: formatHours(stats.value.total_hours_month),
            icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
            color: 'gray'
        },
        {
            label: 'Entries (30d)',
            value: stats.value.entries_count_month,
            icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            color: 'green'
        },
        {
            label: 'Pending Approval',
            value: stats.value.pending_approval_count,
            icon: 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            color: 'yellow'
        },
        {
            label: 'Avg/Entry',
            value: formatHours(
                stats.value.entries_count_month > 0
                    ? stats.value.total_hours_month / stats.value.entries_count_month
                    : 0
            ),
            icon: 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
            color: 'indigo'
        }
    ];
});

// Approval wizard state
const showApprovalWizard = ref(false);
const showAccountSelector = ref(false);
const selectedAccountForApproval = ref(null);
const selectedAccountId = ref('');

const filters = reactive({
    status: "",
    billable: "",
    date_from: "",
    date_to: "",
});

// Create reactive query options
const queryOptions = reactive({
    ...filters,
    page: currentPage,
    per_page: 20,
});

// Use TanStack Query for time entries list
const {
    data: timeEntriesData,
    isLoading: loading,
    error: timeEntriesError,
    refetch: refetchTimeEntries,
} = useTimeEntriesListQuery(queryOptions);

// Computed properties for backward compatibility
const timeEntries = computed(() => {
    if (!timeEntriesData.value) {
        return { data: [], total: 0 };
    }
    return {
        data: timeEntriesData.value.data || [],
        total: timeEntriesData.value.meta?.total || 0,
        ...timeEntriesData.value.meta
    };
});
const stats = computed(() => timeEntriesData.value?.meta?.stats || {});

// Computed properties
const canApprove = computed(() => {
    return (
        user.value?.user_type === 'service_provider' ||
        user.value?.permissions?.includes("time.manage") ||
        user.value?.permissions?.includes("time.approve") ||
        user.value?.permissions?.includes("teams.manage") ||
        user.value?.permissions?.includes("admin.manage") ||
        user.value?.permissions?.includes("admin.write")
    );
});

// Methods (using TanStack Query)
const loadPage = (page) => {
    currentPage.value = page;
    // The query will automatically refetch when currentPage changes
};

// Reactive query that updates when filters or page change
watch(
    [filters, currentPage],
    () => {
        // Clear selections when filters/page change
        selectedEntries.value = [];
    },
    { deep: true }
);

const clearFilters = () => {
    Object.keys(filters).forEach((key) => {
        filters[key] = "";
    });
    currentPage.value = 1;
    // Query will automatically refetch when filters and page change
};

const editEntry = (entry) => {
    editingEntry.value = entry;
};

const closeModal = () => {
    showCreateModal.value = false;
    editingEntry.value = null;
};

const handleTimeEntrySaved = () => {
    closeModal();
    // Query will automatically refetch due to cache invalidation in the mutation
};

const approveEntryHandler = async (entry) => {
    try {
        await approveTimeEntry(entry.id);
    } catch (error) {
        console.error("Error approving entry:", error);
    }
};

const rejectEntryHandler = async (entry) => {
    const reason = prompt("Rejection reason:");
    if (reason) {
        try {
            await rejectTimeEntry({ timeEntryId: entry.id, reason });
        } catch (error) {
            console.error("Error rejecting entry:", error);
        }
    }
};

const bulkApprove = async () => {
    if (selectedEntries.value.length === 0) return;

    try {
        await bulkApproveTimeEntries({
            time_entry_ids: selectedEntries.value,
            notes: bulkApprovalNotes.value,
        });
        showBulkApprovalModal.value = false;
        bulkApprovalNotes.value = "";
        selectedEntries.value = [];
    } catch (error) {
        console.error("Error bulk approving entries:", error);
    }
};

const onTimerCommitted = () => {
    // Query will automatically refetch due to cache invalidation in the mutation
    // Navigate to time entries tab to show the new entry
    router.visit(route("time-and-addons.index", "time-entries"));
};

// Approval wizard methods

const closeApprovalWizard = () => {
    showApprovalWizard.value = false;
    // Reset approval wizard state
    selectedAccountForApproval.value = null;
    selectedAccountId.value = '';
};

const onAccountSelectedForApproval = (account) => {
    selectedAccountForApproval.value = account;
};

const selectAllAccounts = () => {
    selectedAccountForApproval.value = { 
        id: '', 
        name: 'All Accounts' 
    };
    selectedAccountId.value = '';
    // Clear any cached selection to ensure "All Accounts" behavior
};

const launchApprovalWizard = () => {
    if (!selectedAccountForApproval.value) return;
    
    showAccountSelector.value = false;
    showApprovalWizard.value = true;
};

const onApprovalWizardCompleted = () => {
    // Refresh time entries list
    refetchTimeEntries();
};

const onCreateInvoiceFromWizard = (data) => {
    closeApprovalWizard();
    // Navigate to billing page to create invoice
    router.visit('/billing', { 
        state: { 
            accountId: data.accountId,
            accountName: data.accountName,
            openCreateInvoice: true 
        } 
    });
};

// No additional watchers needed for simplified approval wizard

// Utility functions
const formatHours = (minutes) => {
    if (!minutes) return "0h";
    const hours = Math.floor(minutes / 60);
    const mins = Math.round(minutes % 60);
    if (hours === 0) return `${mins}m`;
    if (mins === 0) return `${hours}h`;
    return `${hours}h ${mins}m`;
};

const formatDate = (dateString) => {
    if (!dateString) return "";
    const date = new Date(dateString);
    return date.toLocaleDateString();
};

// Initialize - queries will automatically fetch on mount
</script>