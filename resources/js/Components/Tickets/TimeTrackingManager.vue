<template>
    <div class="space-y-6">
        <!-- Active Timers Section -->
        <div v-if="activeTimers.length > 0">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                Active Timers
            </h3>
            <div class="space-y-3">
                <div
                    v-for="timer in activeTimers"
                    :key="timer.id"
                    class="flex items-center justify-between p-4 bg-green-50 border border-green-200 rounded-lg"
                >
                    <div class="flex items-center space-x-3">
                        <div
                            class="w-3 h-3 bg-green-500 rounded-full animate-pulse"
                        ></div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">
                                {{ timer.user?.name || "Unknown User" }}
                            </p>
                            <p
                                v-if="timer.description"
                                class="text-xs text-gray-600"
                            >
                                {{ timer.description }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <span class="text-lg font-bold text-green-700">{{
                            formatDuration(timer.duration)
                        }}</span>
                        <div class="flex items-center space-x-2">
                            <button
                                v-if="
                                    timer.user_id === currentUserId &&
                                    timer.status === 'running'
                                "
                                @click="pauseTimer(timer)"
                                class="bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1 rounded-md text-sm transition-colors"
                            >
                                Pause
                            </button>
                            <button
                                v-if="
                                    timer.user_id === currentUserId &&
                                    timer.status === 'paused'
                                "
                                @click="resumeTimer(timer)"
                                class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded-md text-sm transition-colors"
                            >
                                Resume
                            </button>
                            <button
                                v-if="timer.user_id === currentUserId"
                                @click="stopTimer(timer)"
                                class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded-md text-sm transition-colors"
                            >
                                Stop
                            </button>
                            <button
                                v-if="
                                    canManageAllTimers &&
                                    timer.user_id !== currentUserId
                                "
                                @click="showStopConfirmation(timer)"
                                class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-1 rounded-md text-sm transition-colors"
                            >
                                Stop
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Time Entries Section -->
        <div>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">
                    Time Entries
                </h3>
                <button
                    v-if="canAddTimeEntry"
                    @click="showAddTimeEntryModal = true"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center space-x-2"
                >
                    <svg
                        class="w-4 h-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                        />
                    </svg>
                    <span>Add Entry</span>
                </button>
            </div>

            <!-- Filters -->
            <div class="mb-4 flex items-center space-x-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"
                        >User</label
                    >
                    <select
                        v-model="filters.user_id"
                        @change="loadTimeEntries"
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="">All Users</option>
                        <option
                            v-for="user in availableUsers"
                            :key="user.id"
                            :value="user.id"
                        >
                            {{ user.name }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"
                        >Status</label
                    >
                    <select
                        v-model="filters.status"
                        @change="loadTimeEntries"
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="billed">Billed</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"
                        >Date Range</label
                    >
                    <select
                        v-model="filters.dateRange"
                        @change="loadTimeEntries"
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="">All Time</option>
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                        <option value="quarter">This Quarter</option>
                    </select>
                </div>
            </div>

            <!-- Time Entries Table -->
            <div class="overflow-hidden border border-gray-200 rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                User
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                Date
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                Duration
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                Description
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                Status
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                Billable
                            </th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-if="loading" class="animate-pulse">
                            <td
                                colspan="7"
                                class="px-6 py-4 text-center text-gray-500"
                            >
                                Loading time entries...
                            </td>
                        </tr>
                        <tr v-else-if="timeEntries.length === 0">
                            <td
                                colspan="7"
                                class="px-6 py-4 text-center text-gray-500"
                            >
                                No time entries found.
                            </td>
                        </tr>
                        <tr
                            v-else
                            v-for="entry in timeEntries"
                            :key="entry.id"
                            class="hover:bg-gray-50"
                        >
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div
                                        class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center mr-3"
                                    >
                                        <span
                                            class="text-xs font-medium text-gray-700"
                                        >
                                            {{
                                                entry.user?.name
                                                    ?.charAt(0)
                                                    ?.toUpperCase() || "?"
                                            }}
                                        </span>
                                    </div>
                                    <div>
                                        <div
                                            class="text-sm font-medium text-gray-900"
                                        >
                                            {{ entry.user?.name || "Unknown" }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td
                                class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                            >
                                {{ formatDate(entry.started_at) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ formatDuration(entry.duration) }}
                                </div>
                                <div
                                    v-if="entry.break_duration"
                                    class="text-xs text-gray-500"
                                >
                                    -{{ formatDuration(entry.break_duration) }}
                                    break
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div
                                    class="text-sm text-gray-900 max-w-xs truncate"
                                    :title="entry.description"
                                >
                                    {{ entry.description || "No description" }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    :class="getStatusClasses(entry.status)"
                                    class="px-2 py-1 rounded-full text-xs font-medium"
                                >
                                    {{ formatStatus(entry.status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span
                                        v-if="entry.billable"
                                        class="text-green-600 text-sm"
                                    >
                                        ${{ entry.billable_amount || "0.00" }}
                                    </span>
                                    <span v-else class="text-gray-400 text-sm">
                                        Non-billable
                                    </span>
                                </div>
                            </td>
                            <td
                                class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2"
                            >
                                <button
                                    v-if="canEditTimeEntry(entry)"
                                    @click="editTimeEntry(entry)"
                                    class="text-blue-600 hover:text-blue-700 transition-colors"
                                >
                                    Edit
                                </button>
                                <button
                                    v-if="canApproveTimeEntry(entry)"
                                    @click="approveTimeEntry(entry)"
                                    class="text-green-600 hover:text-green-700 transition-colors"
                                >
                                    Approve
                                </button>
                                <button
                                    v-if="canRejectTimeEntry(entry)"
                                    @click="rejectTimeEntry(entry)"
                                    class="text-red-600 hover:text-red-700 transition-colors"
                                >
                                    Reject
                                </button>
                                <button
                                    v-if="canDeleteTimeEntry(entry)"
                                    @click="deleteTimeEntry(entry)"
                                    class="text-red-600 hover:text-red-700 transition-colors"
                                >
                                    Delete
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="pagination.last_page > 1"
                class="mt-4 flex items-center justify-between"
            >
                <div class="text-sm text-gray-700">
                    Showing {{ pagination.from }} to {{ pagination.to }} of
                    {{ pagination.total }} entries
                </div>
                <div class="flex items-center space-x-2">
                    <button
                        @click="changePage(pagination.current_page - 1)"
                        :disabled="pagination.current_page === 1"
                        class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Previous
                    </button>
                    <span class="px-3 py-2 text-sm font-medium text-gray-700">
                        Page {{ pagination.current_page }} of
                        {{ pagination.last_page }}
                    </span>
                    <button
                        @click="changePage(pagination.current_page + 1)"
                        :disabled="
                            pagination.current_page === pagination.last_page
                        "
                        class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>

        <!-- Time Summary -->
        <div class="bg-gray-50 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Summary</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900">
                        {{ formatDuration(summary.total_time) }}
                    </div>
                    <div class="text-sm text-gray-600">Total Time</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">
                        {{ formatDuration(summary.billable_time) }}
                    </div>
                    <div class="text-sm text-gray-600">Billable</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600">
                        ${{ summary.total_amount }}
                    </div>
                    <div class="text-sm text-gray-600">Total Value</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600">
                        {{ summary.entries_count }}
                    </div>
                    <div class="text-sm text-gray-600">Entries</div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <UnifiedTimeEntryDialog
            :show="showAddTimeEntryModal"
            mode="create"
            :context-ticket="ticket"
            @close="showAddTimeEntryModal = false"
            @saved="handleTimeEntrySaved"
        />

        <UnifiedTimeEntryDialog
            :show="showEditTimeEntryModal"
            mode="edit"
            :context-ticket="ticket"
            :time-entry="selectedTimeEntry"
            @close="showEditTimeEntryModal = false"
            @saved="handleTimeEntrySaved"
        />

        <ConfirmationModal
            v-if="showStopTimerModal"
            title="Stop Timer"
            :message="`Are you sure you want to stop ${timerToStop?.user?.name}'s timer?`"
            @confirmed="confirmStopTimer"
            @cancelled="showStopTimerModal = false"
        />
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import axios from "axios";

// Import child components
import UnifiedTimeEntryDialog from "@/Components/TimeEntries/UnifiedTimeEntryDialog.vue";
import ConfirmationModal from "../ConfirmationModal.vue";

// Props
const props = defineProps({
    ticket: {
        type: Object,
        required: true,
    },
    permissions: {
        type: Object,
        default: () => ({}),
    },
});

// Emits
const emit = defineEmits(["updated"]);

// Reactive data
const activeTimers = ref([]);
const timeEntries = ref([]);
const availableUsers = ref([]);
const loading = ref(false);
const currentUserId = ref(window.auth?.user?.id);

// Modal states
const showAddTimeEntryModal = ref(false);
const showEditTimeEntryModal = ref(false);
const showStopTimerModal = ref(false);
const selectedTimeEntry = ref(null);
const timerToStop = ref(null);

// Filters
const filters = ref({
    user_id: "",
    status: "",
    dateRange: "",
    page: 1,
});

// Pagination
const pagination = ref({
    current_page: 1,
    last_page: 1,
    from: 0,
    to: 0,
    total: 0,
});

// Summary data
const summary = ref({
    total_time: 0,
    billable_time: 0,
    total_amount: "0.00",
    entries_count: 0,
});

// Computed properties
const canAddTimeEntry = computed(() => {
    return props.permissions?.canCreateTimeEntries || false;
});

const canManageAllTimers = computed(() => {
    return props.permissions?.canViewTimers || false;
});

// Methods
const formatDuration = (seconds) => {
    if (!seconds) return "0m";

    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);

    if (hours > 0) {
        return `${hours}h ${minutes}m`;
    } else {
        return `${minutes}m`;
    }
};

const formatDate = (date) => {
    if (!date) return "N/A";
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};

const formatStatus = (status) => {
    return (
        status?.replace("_", " ").replace(/\b\w/g, (l) => l.toUpperCase()) ||
        "Unknown"
    );
};

const getStatusClasses = (status) => {
    const statusMap = {
        pending: "bg-yellow-100 text-yellow-800",
        approved: "bg-green-100 text-green-800",
        rejected: "bg-red-100 text-red-800",
        billed: "bg-blue-100 text-blue-800",
    };

    return statusMap[status] || "bg-gray-100 text-gray-800";
};

// Permission checking methods
const canEditTimeEntry = (entry) => {
    return entry.user_id === currentUserId.value && entry.status === "pending";
};

const canApproveTimeEntry = (entry) => {
    return entry.status === "pending" && canManageAllTimers.value;
};

const canRejectTimeEntry = (entry) => {
    return entry.status === "pending" && canManageAllTimers.value;
};

const canDeleteTimeEntry = (entry) => {
    return (
        (entry.user_id === currentUserId.value && entry.status === "pending") ||
        canManageAllTimers.value
    );
};

// Data loading methods
const loadActiveTimers = async () => {
    if (!props.ticket?.id) return;

    try {
        const response = await axios.get(
            `/api/tickets/${props.ticket.id}/timers`
        );
        activeTimers.value = response.data.data || [];
    } catch (error) {
        console.error("Failed to load active timers:", error);
        activeTimers.value = [];
    }
};

const loadTimeEntries = async () => {
    if (!props.ticket?.id) return;

    loading.value = true;

    try {
        const params = new URLSearchParams({
            page: filters.value.page,
            ...Object.fromEntries(
                Object.entries(filters.value).filter(([_, v]) => v !== "")
            ),
        });

        const response = await axios.get(
            `/api/tickets/${props.ticket.id}/time-entries?${params}`
        );
        const data = response.data;

        timeEntries.value = data.data || [];
        pagination.value = {
            current_page: data.current_page || 1,
            last_page: data.last_page || 1,
            from: data.from || 0,
            to: data.to || 0,
            total: data.total || 0,
        };

        // Load summary
        await loadSummary();
    } catch (error) {
        console.error("Failed to load time entries:", error);
        timeEntries.value = [];
    } finally {
        loading.value = false;
    }
};

const loadAvailableUsers = async () => {
    try {
        const response = await axios.get("/api/users/assignable");
        availableUsers.value = response.data.data || [];
    } catch (error) {
        console.error("Failed to load available users:", error);
        availableUsers.value = [];
    }
};

const loadSummary = async () => {
    if (!props.ticket?.id) return;

    try {
        const response = await axios.get(
            `/api/tickets/${props.ticket.id}/time-summary`
        );
        summary.value = response.data.data || {
            total_time: 0,
            billable_time: 0,
            total_amount: "0.00",
            entries_count: 0,
        };
    } catch (error) {
        console.error("Failed to load time summary:", error);
    }
};

// Timer action methods
const pauseTimer = async (timer) => {
    try {
        await axios.post(`/api/timers/${timer.id}/pause`);
        await loadActiveTimers();
        emit("updated");
    } catch (error) {
        console.error("Failed to pause timer:", error);
    }
};

const resumeTimer = async (timer) => {
    try {
        await axios.post(`/api/timers/${timer.id}/resume`);
        await loadActiveTimers();
        emit("updated");
    } catch (error) {
        console.error("Failed to resume timer:", error);
    }
};

const stopTimer = async (timer) => {
    try {
        await axios.post(`/api/timers/${timer.id}/stop`);
        await loadActiveTimers();
        await loadTimeEntries();
        emit("updated");
    } catch (error) {
        console.error("Failed to stop timer:", error);
    }
};

const showStopConfirmation = (timer) => {
    timerToStop.value = timer;
    showStopTimerModal.value = true;
};

const confirmStopTimer = async () => {
    if (timerToStop.value) {
        await stopTimer(timerToStop.value);
        timerToStop.value = null;
    }
    showStopTimerModal.value = false;
};

// Time entry action methods
const editTimeEntry = (entry) => {
    selectedTimeEntry.value = entry;
    showEditTimeEntryModal.value = true;
};

const approveTimeEntry = async (entry) => {
    try {
        await axios.post(`/api/time-entries/${entry.id}/approve`);
        await loadTimeEntries();
        emit("updated");
    } catch (error) {
        console.error("Failed to approve time entry:", error);
    }
};

const rejectTimeEntry = async (entry) => {
    try {
        await axios.post(`/api/time-entries/${entry.id}/reject`);
        await loadTimeEntries();
        emit("updated");
    } catch (error) {
        console.error("Failed to reject time entry:", error);
    }
};

const deleteTimeEntry = async (entry) => {
    if (!confirm("Are you sure you want to delete this time entry?")) return;

    try {
        await axios.delete(`/api/time-entries/${entry.id}`);
        await loadTimeEntries();
        emit("updated");
    } catch (error) {
        console.error("Failed to delete time entry:", error);
    }
};

// Pagination
const changePage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        filters.value.page = page;
        loadTimeEntries();
    }
};

// Modal handlers
const handleTimeEntrySaved = () => {
    showAddTimeEntryModal.value = false;
    showEditTimeEntryModal.value = false;
    selectedTimeEntry.value = null;
    loadTimeEntries();
    emit("updated");
};

// Lifecycle
onMounted(() => {
    loadActiveTimers();
    loadTimeEntries();
    loadAvailableUsers();
});

// Watchers
watch(
    () => props.ticket?.id,
    (newId) => {
        if (newId) {
            loadActiveTimers();
            loadTimeEntries();
        }
    }
);
</script>
