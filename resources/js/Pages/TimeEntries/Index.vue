<template>
    <!-- Page Header -->
    <div class="bg-white shadow-sm border-b border-gray-200 mb-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2
                        class="font-semibold text-xl text-gray-800 leading-tight"
                    >
                        Time Management
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        View and manage time entries and active timers.
                    </p>
                </div>
                <div class="flex space-x-3">
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
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8">
                <Link
                    :href="route('time-entries.index', 'time-entries')"
                    :class="[
                        activeTab === 'time-entries'
                            ? 'border-indigo-500 text-indigo-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                        'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm',
                    ]"
                >
                    Time Entries
                </Link>
                <Link
                    :href="route('time-entries.index', 'timers')"
                    :class="[
                        activeTab === 'timers'
                            ? 'border-indigo-500 text-indigo-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                        'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm',
                    ]"
                >
                    Active Timers
                </Link>
            </nav>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
        <!-- Time Entries Tab -->
        <div v-if="activeTab === 'time-entries'">
            <!-- Statistics Cards -->
            <div
                v-if="stats"
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8"
            >
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg
                                    class="h-6 w-6 text-gray-400"
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
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt
                                        class="text-sm font-medium text-gray-500 truncate"
                                    >
                                        Total Hours (30d)
                                    </dt>
                                    <dd
                                        class="text-lg font-medium text-gray-900"
                                    >
                                        {{
                                            formatHours(stats.total_hours_month)
                                        }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg
                                    class="h-6 w-6 text-green-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt
                                        class="text-sm font-medium text-gray-500 truncate"
                                    >
                                        Entries (30d)
                                    </dt>
                                    <dd
                                        class="text-lg font-medium text-gray-900"
                                    >
                                        {{ stats.entries_count_month }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg
                                    class="h-6 w-6 text-yellow-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt
                                        class="text-sm font-medium text-gray-500 truncate"
                                    >
                                        Pending Approval
                                    </dt>
                                    <dd
                                        class="text-lg font-medium text-gray-900"
                                    >
                                        {{ stats.pending_approval_count }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg
                                    class="h-6 w-6 text-indigo-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"
                                    />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt
                                        class="text-sm font-medium text-gray-500 truncate"
                                    >
                                        Avg/Entry
                                    </dt>
                                    <dd
                                        class="text-lg font-medium text-gray-900"
                                    >
                                        {{
                                            formatHours(
                                                stats.entries_count_month > 0
                                                    ? stats.total_hours_month /
                                                          stats.entries_count_month
                                                    : 0
                                            )
                                        }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Filters</h3>
                </div>
                <div class="p-6">
                    <div
                        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4"
                    >
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
                        {{ timeEntries.total }} total entries
                        <span
                            v-if="timeEntries.total !== timeEntries.data.length"
                        >
                            (showing {{ timeEntries.data.length }})
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
                    v-else-if="timeEntries.data.length > 0"
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
                    v-if="timeEntries.data.length > 0"
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

        <!-- Timers Tab -->
        <div v-else-if="activeTab === 'timers'">
            <TimersTab @timer-committed="onTimerCommitted" />
        </div>
    </div>

    <!-- Unified Time Entry Dialog -->
    <UnifiedTimeEntryDialog
        :show="showCreateModal || !!editingEntry"
        :mode="editingEntry ? 'edit' : 'create'"
        :time-entry="editingEntry"
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
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { usePage, Link, router } from "@inertiajs/vue3";
import TimersTab from "@/Components/TimeEntries/TimersTab.vue";
import UnifiedTimeEntryDialog from "@/Components/TimeEntries/UnifiedTimeEntryDialog.vue";
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
    layout: AppLayout,
});

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
const timeEntries = computed(
    () => timeEntriesData.value?.data || { data: [], total: 0 }
);
const stats = computed(() => timeEntriesStats.value || {});

// Computed properties
const canApprove = computed(() => {
    return (
        user.value?.permissions?.includes("teams.manage") ||
        user.value?.permissions?.includes("admin.manage")
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
    router.visit(route("time-entries.index", "time-entries"));
};

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
