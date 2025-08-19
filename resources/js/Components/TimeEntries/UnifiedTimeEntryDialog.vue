<template>
    <StackedDialog
        :show="show"
        :title="dialogTitle"
        max-width="3xl"
        :allow-dropdowns="true"
        @close="closeDialog"
        :show-footer="false"
    >
        <div class="p-6">
            <!-- Subtitle -->
            <div class="mb-6">
                <p class="text-sm text-gray-600">
                    {{ dialogSubtitle }}
                </p>
            </div>

            <!-- Form -->
            <form @submit.prevent="submitTimeEntry" class="space-y-6">
                <!-- Context Information (Timer Commit Mode) -->
                <div
                    v-if="mode === 'timer-commit' && timerData"
                    class="bg-gray-50 dark:bg-gray-600 rounded-md p-4"
                >
                    <div
                        class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >
                        Timer Information
                    </div>
                    <div class="text-sm text-gray-900 dark:text-gray-100">
                        {{ timerData.description || "Timer" }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Duration: {{ formatDuration(calculateTimerDuration()) }}
                        <span
                            v-if="
                                timerData.billing_rate_id &&
                                form.estimatedValue > 0
                            "
                            class="ml-2"
                        >
                            Estimated Value: ${{
                                form.estimatedValue.toFixed(2)
                            }}
                        </span>
                    </div>
                </div>

                <!-- Two-column layout for larger screens -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left Column -->
                    <div class="space-y-6">
                        <!-- Timer Context Section -->
                        <div class="space-y-4">
                            <h4 class="text-md font-medium text-gray-900">
                                Context & Assignment
                            </h4>

                            <!-- Account Selection (if not pre-selected) -->
                            <div v-if="!contextAccount">
                                <UnifiedSelector
                                    v-model="form.accountId"
                                    type="account"
                                    :items="availableAccounts"
                                    label="Account"
                                    placeholder="Select account..."
                                    :required="true"
                                    :error="errors.accountId"
                                    @item-selected="handleAccountSelected"
                                />
                            </div>

                            <!-- Ticket Selection (if not pre-selected) -->
                            <div v-if="!contextTicket">
                                <UnifiedSelector
                                    v-model="form.ticketId"
                                    type="ticket"
                                    :items="availableTickets"
                                    :loading="ticketsLoading"
                                    label="Ticket"
                                    placeholder="Select ticket..."
                                    :required="true"
                                    :disabled="!form.accountId"
                                    :error="errors.ticketId"
                                    @item-selected="handleTicketSelected"
                                />
                            </div>

                            <!-- Agent Assignment (for managers/admins) -->
                            <div v-if="canAssignToOthers">
                                <UnifiedSelector
                                    v-model="form.userId"
                                    type="agent"
                                    :items="availableAgents"
                                    :loading="agentsLoading"
                                    label="Service Agent"
                                    placeholder="Select the agent who performed this work..."
                                    :error="errors.userId"
                                    :agent-type="'time'"
                                    @item-selected="handleAgentSelected"
                                />
                                <p class="mt-1 text-xs text-gray-500">
                                    The service agent who performed this work
                                </p>
                            </div>
                        </div>

                        <!-- Time Entry Details -->
                        <div class="space-y-4">
                            <h4 class="text-md font-medium text-gray-900">
                                Time Details
                            </h4>

                            <!-- Description -->
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                >
                                    Description
                                    <span class="text-red-500">*</span>
                                </label>
                                <textarea
                                    v-model="form.description"
                                    rows="3"
                                    :required="validation.description.required"
                                    placeholder="Describe the work performed..."
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                                ></textarea>
                                <div
                                    v-if="errors.description"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ errors.description }}
                                </div>
                            </div>

                            <!-- Date and Time -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-1"
                                    >
                                        Date <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.date"
                                        type="date"
                                        required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                                    />
                                    <div
                                        v-if="errors.date"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ errors.date }}
                                    </div>
                                </div>

                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-1"
                                    >
                                        Start Time
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.startTime"
                                        type="time"
                                        required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                                    />
                                    <div
                                        v-if="errors.startTime"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ errors.startTime }}
                                    </div>
                                </div>
                            </div>

                            <!-- Duration -->
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                >
                                    Duration <span class="text-red-500">*</span>
                                </label>
                                <div class="flex items-center space-x-3">
                                    <div class="flex items-center space-x-2">
                                        <input
                                            v-model.number="form.durationHours"
                                            type="number"
                                            min="0"
                                            max="23"
                                            placeholder="0"
                                            class="w-20 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                                        />
                                        <span class="text-sm text-gray-600"
                                            >hours</span
                                        >
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <input
                                            v-model.number="
                                                form.durationMinutes
                                            "
                                            type="number"
                                            min="0"
                                            max="59"
                                            placeholder="0"
                                            class="w-20 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                                        />
                                        <span class="text-sm text-gray-600"
                                            >minutes</span
                                        >
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        Total:
                                        {{
                                            formatDuration(totalDurationSeconds)
                                        }}
                                    </div>
                                </div>
                                <div
                                    v-if="errors.duration"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ errors.duration }}
                                </div>
                                <div
                                    v-if="durationValidationErrors.length > 0"
                                    class="mt-1"
                                >
                                    <div
                                        v-for="error in durationValidationErrors"
                                        :key="error"
                                        class="text-sm text-red-600"
                                    >
                                        {{ error }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-6">
                        <!-- Billing Information -->
                        <div class="space-y-4">
                            <h4 class="text-md font-medium text-gray-900">
                                Billing & Rates
                            </h4>

                            <!-- Billing Rate -->
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                >
                                    Billing Rate
                                </label>
                                <UnifiedSelector
                                    v-model="form.billingRateId"
                                    type="billing-rate"
                                    :items="availableBillingRates"
                                    :loading="billingRatesLoading"
                                    :show-rate-hierarchy="true"
                                    placeholder="No billing rate"
                                    :error="errors.billingRateId"
                                    @item-selected="handleRateSelected"
                                />
                            </div>

                            <!-- Billable Status -->
                            <div class="flex items-center">
                                <input
                                    id="billable"
                                    v-model="form.billable"
                                    type="checkbox"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                />
                                <label
                                    for="billable"
                                    class="ml-2 text-sm text-gray-700"
                                >
                                    This time is billable to the client
                                </label>
                            </div>

                            <!-- Billing Amount (if billable and rate selected) -->
                            <div v-if="form.billable && selectedBillingRate">
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                >
                                    Billing Amount
                                </label>
                                <div class="flex items-center space-x-4">
                                    <div class="flex-1">
                                        <div class="relative">
                                            <span
                                                class="absolute left-3 top-2 text-gray-500"
                                                >$</span
                                            >
                                            <input
                                                v-model.number="
                                                    form.billingAmount
                                                "
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                readonly
                                                class="w-full pl-8 pr-3 py-2 bg-gray-50 border border-gray-300 rounded-md text-gray-600"
                                            />
                                        </div>
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        Rate: ${{
                                            selectedBillingRate.rate
                                        }}/hour
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                            >
                                Internal Notes (Optional)
                            </label>
                            <textarea
                                v-model="form.notes"
                                rows="3"
                                placeholder="Add any internal notes about this time entry..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                            ></textarea>
                        </div>

                        <!-- Manual Time Override (Timer Commit Mode) -->
                        <div
                            v-if="
                                mode === 'timer-commit' &&
                                allowManualTimeOverride
                            "
                        >
                            <div class="border-t border-gray-200 pt-4">
                                <div class="flex items-center">
                                    <input
                                        id="manual-override"
                                        v-model="form.useManualOverride"
                                        type="checkbox"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                    />
                                    <label
                                        for="manual-override"
                                        class="ml-2 text-sm text-gray-700"
                                    >
                                        Override timer duration with manual
                                        entry above
                                    </label>
                                </div>
                                <div
                                    v-if="form.useManualOverride"
                                    class="mt-2 text-sm text-yellow-600"
                                >
                                    The timer will be marked as committed, but
                                    the duration from your manual entry above
                                    will be used instead.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Full-width sections -->
                <!-- Approval Workflow -->
                <div
                    v-if="requiresApproval"
                    class="bg-yellow-50 border border-yellow-200 rounded-md p-4"
                >
                    <div class="flex items-start">
                        <svg
                            class="w-5 h-5 text-yellow-400 mt-0.5 mr-2"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd"
                            ></path>
                        </svg>
                        <div>
                            <h4 class="text-sm font-medium text-yellow-800">
                                Approval Required
                            </h4>
                            <p class="text-sm text-yellow-700 mt-1">
                                This time entry will require approval from a
                                manager before being finalized.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div
                    class="flex items-center justify-between pt-6 border-t border-gray-200"
                >
                    <button
                        type="button"
                        @click="closeDialog"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                    >
                        Cancel
                    </button>

                    <div class="flex items-center space-x-3">
                        <div
                            v-if="totalBillingAmount > 0"
                            class="text-sm text-gray-600"
                        >
                            Total: ${{ totalBillingAmount.toFixed(2) }}
                        </div>
                        <button
                            type="submit"
                            :disabled="!canSubmit || isSubmitting"
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="isSubmitting">Saving...</span>
                            <span v-else>{{ submitButtonText }}</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </StackedDialog>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from "vue";
import { router } from "@inertiajs/vue3";
import { usePage } from "@inertiajs/vue3";
import StackedDialog from "@/Components/StackedDialog.vue";
import UnifiedSelector from "@/Components/UI/UnifiedSelector.vue";
import { useTimerSettings } from "@/Composables/useTimerSettings.js";
import axios from "axios";

// Props
const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    mode: {
        type: String,
        default: "create", // 'create' | 'edit' | 'timer-commit'
        validator: (value) =>
            ["create", "edit", "timer-commit"].includes(value),
    },
    timerData: {
        type: Object,
        default: null,
    },
    timeEntry: {
        type: Object,
        default: null,
    },
    contextTicket: {
        type: Object,
        default: null,
    },
    contextAccount: {
        type: Object,
        default: null,
    },
});

// Emits
const emit = defineEmits(["close", "saved", "timer-committed"]);

// Settings and validation
const {
    settings,
    validation,
    formatDuration,
    validateDuration,
    requiresApproval,
} = useTimerSettings();

// Page and user data
const page = usePage();
const user = computed(() => page.props.auth?.user);

// State
const form = ref({
    description: "",
    accountId: null,
    ticketId: null,
    userId: null,
    billingRateId: null,
    billable: true,
    date: new Date().toISOString().split("T")[0],
    startTime: new Date().toTimeString().slice(0, 5),
    durationHours: 0,
    durationMinutes: 0,
    billingAmount: 0,
    notes: "",
    useManualOverride: false,
    estimatedValue: 0,
});

const errors = ref({});
const isSubmitting = ref(false);

// Data loading states
const availableAccounts = ref([]);
const availableTickets = ref([]);
const availableBillingRates = ref([]);
const availableAgents = ref([]);
const ticketsLoading = ref(false);
const billingRatesLoading = ref(false);
const agentsLoading = ref(false);

// Settings flags
const allowManualTimeOverride = computed(
    () => settings.value.allow_manual_time_override !== false
);

// Computed properties
const dialogTitle = computed(() => {
    switch (props.mode) {
        case "create":
            return "Add Time Entry";
        case "edit":
            return "Edit Time Entry";
        case "timer-commit":
            return "Commit Timer to Time Entry";
        default:
            return "Time Entry";
    }
});

const dialogSubtitle = computed(() => {
    switch (props.mode) {
        case "create":
            return "Manually log time spent on this ticket";
        case "edit":
            return "Update the time entry details";
        case "timer-commit":
            return props.timerData
                ? `Timer has been running for ${formatDuration(
                      calculateTimerDuration()
                  )}`
                : "Convert the timer to a time entry";
        default:
            return "";
    }
});

const submitButtonText = computed(() => {
    switch (props.mode) {
        case "create":
            return "Add Time Entry";
        case "edit":
            return "Update Time Entry";
        case "timer-commit":
            return "Commit Time Entry";
        default:
            return "Save";
    }
});

const canAssignToOthers = computed(() => {
    return (
        user.value?.permissions?.includes("time.admin") ||
        user.value?.permissions?.includes("admin.manage")
    );
});

const totalDurationSeconds = computed(() => {
    return form.value.durationHours * 3600 + form.value.durationMinutes * 60;
});

const selectedBillingRate = computed(() => {
    return availableBillingRates.value.find(
        (rate) => rate.id == form.value.billingRateId
    );
});

const totalBillingAmount = computed(() => {
    return form.value.billable ? form.value.billingAmount : 0;
});

const durationValidationErrors = computed(() => {
    return validateDuration(totalDurationSeconds.value);
});

const canSubmit = computed(() => {
    return (
        form.value.description.trim() &&
        form.value.accountId &&
        form.value.ticketId &&
        totalDurationSeconds.value > 0 &&
        durationValidationErrors.value.length === 0 &&
        !isSubmitting.value
    );
});

const requiresApprovalComputed = computed(() => {
    return requiresApproval({
        duration: totalDurationSeconds.value,
        amount: totalBillingAmount.value,
        userId: form.value.userId,
    });
});

// Methods
const calculateTimerDuration = () => {
    if (!props.timerData) return 0;

    const startTime = new Date(props.timerData.started_at);
    const now = new Date();
    const elapsed = Math.floor((now - startTime) / 1000);

    // Factor in pause periods if available
    const pauseDuration = props.timerData.pause_duration || 0;
    return Math.max(0, elapsed - pauseDuration);
};

const loadAvailableAccounts = async () => {
    try {
        const response = await axios.get("/api/accounts", {
            params: {
                per_page: 100,
            },
        });
        availableAccounts.value = response.data.data || [];
    } catch (error) {
        console.error("Failed to load available accounts:", error);
        availableAccounts.value = [];
    }
};

const loadTicketsForAccount = async (accountId, includeTicketId = null) => {
    if (!accountId) {
        availableTickets.value = [];
        return;
    }

    ticketsLoading.value = true;
    try {
        const params = {
            account_id: accountId,
            status: ["open", "in_progress", "assigned", "pending", "new", "resolved", "waiting_customer", "on_hold"], // Include more statuses for time entry creation
            per_page: 100,
        };

        // Include specific ticket ID even if it doesn't match status filter
        if (includeTicketId) {
            params.include_ticket_id = includeTicketId;
        }

        const response = await axios.get("/api/tickets", { params });
        availableTickets.value = response.data.data || [];
        console.log(
            "Loaded tickets for account",
            accountId,
            ":",
            availableTickets.value.length,
            "tickets",
            includeTicketId ? `(including ticket ${includeTicketId})` : "",
            "Params:", params,
            "Response data:", response.data.data?.slice(0, 3)
        );
    } catch (error) {
        console.error("Failed to load tickets:", error);
        availableTickets.value = [];
    } finally {
        ticketsLoading.value = false;
    }
};

const loadBillingRatesForAccount = async (accountId) => {
    billingRatesLoading.value = true;
    try {
        const params = accountId ? { account_id: accountId } : {};
        const response = await axios.get("/api/billing-rates", { params });
        availableBillingRates.value = response.data.data || [];

        // Auto-select default billing rate if no rate is currently selected
        if (
            !form.value.billingRateId &&
            availableBillingRates.value.length > 0
        ) {
            const defaultRate = availableBillingRates.value.find(
                (rate) => rate.is_default
            );
            if (defaultRate) {
                form.value.billingRateId = defaultRate.id;
                // Recalculate billing amount with the default rate
                calculateBillingAmount();
            }
        }
    } catch (error) {
        console.error("Failed to load billing rates:", error);
        availableBillingRates.value = [];
    } finally {
        billingRatesLoading.value = false;
    }
};

const loadAgentsForAccount = async (accountId) => {
    agentsLoading.value = true;
    try {
        const params = {
            per_page: 100,
            agent_type: "time", // Specify time entry agent type
        };

        // Only filter by account if one is specified
        if (accountId) {
            params.account_id = accountId;
        }

        const response = await axios.get("/api/users/agents", { params });
        availableAgents.value = response.data.data || [];
    } catch (error) {
        console.error("Failed to load time entry agents:", error);
        availableAgents.value = [];
    } finally {
        agentsLoading.value = false;
    }
};

const handleAccountSelected = (account) => {
    // Clear dependent selections
    form.value.ticketId = null;
    form.value.billingRateId = null;

    // Load dependent data
    if (account) {
        loadTicketsForAccount(account.id);
        loadBillingRatesForAccount(account.id);
        if (canAssignToOthers.value) {
            loadAgentsForAccount(account.id);
        }
    } else {
        availableTickets.value = [];
        loadBillingRatesForAccount(null);
        availableAgents.value = [];
    }
};

const handleTicketSelected = (ticket) => {
    // Auto-set account from ticket if needed
    if (ticket?.account_id && !form.value.accountId) {
        form.value.accountId = ticket.account_id;
        // Load dependent data for the new account
        loadBillingRatesForAccount(ticket.account_id);
        if (canAssignToOthers.value) {
            loadAgentsForAccount(ticket.account_id);
        }
    }

    // Auto-set billing rate from ticket if available
    if (ticket?.billing_rate_id && !form.value.billingRateId) {
        form.value.billingRateId = ticket.billing_rate_id;
    }

    calculateBillingAmount();
};

const handleRateSelected = (rate) => {
    calculateBillingAmount();
};

const handleAgentSelected = (agent) => {
    // Additional logic if needed
};

const calculateBillingAmount = () => {
    if (
        form.value.billable &&
        selectedBillingRate.value &&
        totalDurationSeconds.value > 0
    ) {
        const hours = totalDurationSeconds.value / 3600;
        form.value.billingAmount = hours * selectedBillingRate.value.rate;
    } else {
        form.value.billingAmount = 0;
    }
};

const submitTimeEntry = async () => {
    if (!canSubmit.value) return;

    errors.value = {};
    isSubmitting.value = true;

    try {
        let duration = totalDurationSeconds.value;

        // Use timer duration for timer commit mode (unless manual override)
        if (props.mode === "timer-commit" && !form.value.useManualOverride) {
            duration = calculateTimerDuration();
        }

        const payload = {
            description: form.value.description.trim(),
            account_id: form.value.accountId,
            ticket_id: form.value.ticketId,
            user_id: form.value.userId || user.value.id,
            billing_rate_id: form.value.billingRateId || null,
            billable: form.value.billable,
            billed_amount: form.value.billingAmount,
            duration: duration,
            started_at: `${form.value.date} ${form.value.startTime}:00`,
            notes: form.value.notes,
            requires_approval: requiresApprovalComputed.value,
        };

        // Add timer ID for timer commit mode
        if (props.mode === "timer-commit" && props.timerData) {
            payload.timer_id = props.timerData.id;
        }

        let response;
        if (props.mode === "edit" && props.timeEntry) {
            response = await axios.put(
                `/api/time-entries/${props.timeEntry.id}`,
                payload
            );
        } else {
            response = await axios.post("/api/time-entries", payload);
        }

        const timeEntry = response.data.data;

        if (props.mode === "timer-commit") {
            emit("timer-committed", {
                timeEntry,
                timerData: props.timerData,
            });
        } else {
            emit("saved", {
                timeEntry,
                mode: props.mode,
            });
        }

        closeDialog();
    } catch (error) {
        console.error("Error saving time entry:", error);
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        } else {
            errors.value.general =
                error.response?.data?.message ||
                "An error occurred while saving the time entry";
        }
    } finally {
        isSubmitting.value = false;
    }
};

const closeDialog = () => {
    emit("close");
};

const resetForm = () => {
    form.value = {
        description: "",
        accountId: null,
        ticketId: null,
        userId: user.value?.id, // Default to current user
        billingRateId: null,
        billable: settings.value.default_billable !== false,
        date: new Date().toISOString().split("T")[0],
        startTime: new Date().toTimeString().slice(0, 5),
        durationHours: 0,
        durationMinutes: 0,
        billingAmount: 0,
        notes: "",
        useManualOverride: false,
        estimatedValue: 0,
    };
    errors.value = {};
};

const initializeForm = async () => {
    // Reset form first
    resetForm();

    // Load initial data first
    await loadAvailableAccounts();

    // Apply context data and load related data
    if (props.contextAccount) {
        form.value.accountId = props.contextAccount.id;
        await Promise.all([
            loadTicketsForAccount(props.contextAccount.id),
            loadBillingRatesForAccount(props.contextAccount.id),
        ]);
    }

    if (props.contextTicket) {
        form.value.accountId = props.contextTicket.account_id;
        form.value.ticketId = props.contextTicket.id;
        if (props.contextTicket.billing_rate_id) {
            form.value.billingRateId = props.contextTicket.billing_rate_id;
        }
        
        // Load related data for the ticket's account
        if (props.contextTicket.account_id) {
            await Promise.all([
                loadTicketsForAccount(props.contextTicket.account_id, props.contextTicket.id),
                loadBillingRatesForAccount(props.contextTicket.account_id),
            ]);
            
            // Load agents if user can assign to others
            if (canAssignToOthers.value) {
                await loadAgentsForAccount(props.contextTicket.account_id);
            }
        }
    }

    // Timer commit mode initialization
    if (props.mode === "timer-commit" && props.timerData) {
        console.log("Initializing timer commit with data:", props.timerData);
        form.value.description = props.timerData.description || "";

        // Set account and load related data
        if (props.timerData.account_id) {
            form.value.accountId = props.timerData.account_id;

            // Load all related data in parallel and wait for completion
            const loadPromises = [
                loadTicketsForAccount(
                    props.timerData.account_id,
                    props.timerData.ticket_id
                ),
                loadBillingRatesForAccount(props.timerData.account_id),
            ];

            // If user can assign to others, load users too
            if (canAssignToOthers.value) {
                loadPromises.push(
                    loadAgentsForAccount(props.timerData.account_id)
                );
            }

            await Promise.all(loadPromises);
        }

        // Set ticket ID (now that tickets are loaded)
        if (props.timerData.ticket_id) {
            // Wait for the next tick to ensure tickets are reactive
            await nextTick();
            form.value.ticketId = props.timerData.ticket_id;
            console.log(
                "Set form ticketId to:",
                props.timerData.ticket_id,
                "Available tickets:",
                availableTickets.value.length
            );

            // Force another tick to let UnifiedSelector react
            await nextTick();
        }

        // Set billing rate ID (now that billing rates are loaded)
        if (props.timerData.billing_rate_id) {
            form.value.billingRateId = props.timerData.billing_rate_id;
        }

        // Set assigned user to timer's user (the person who ran the timer)
        if (props.timerData.user_id) {
            form.value.userId = props.timerData.user_id;
        }

        // Calculate timer duration and estimated value
        const timerDuration = calculateTimerDuration();
        form.value.durationHours = Math.floor(timerDuration / 3600);
        form.value.durationMinutes = Math.floor((timerDuration % 3600) / 60);

        // Set the start time to timer's start time
        if (props.timerData.started_at) {
            const startDate = new Date(props.timerData.started_at);
            form.value.date = startDate.toISOString().split("T")[0];
            form.value.startTime = startDate.toTimeString().slice(0, 5);
        }

        console.log("Form initialized with:", {
            accountId: form.value.accountId,
            ticketId: form.value.ticketId,
            billingRateId: form.value.billingRateId,
            userId: form.value.userId,
            description: form.value.description,
        });
    }

    // Edit mode initialization
    if (props.mode === "edit" && props.timeEntry) {
        form.value.description = props.timeEntry.description || "";
        form.value.accountId = props.timeEntry.account_id;
        form.value.ticketId = props.timeEntry.ticket_id;
        form.value.userId = props.timeEntry.user_id;
        form.value.billingRateId = props.timeEntry.billing_rate_id;
        form.value.billable = props.timeEntry.billable;
        form.value.billingAmount = props.timeEntry.billed_amount || 0;
        form.value.notes = props.timeEntry.notes || "";

        // Parse duration
        const duration = props.timeEntry.duration || 0;
        form.value.durationHours = Math.floor(duration / 3600);
        form.value.durationMinutes = Math.floor((duration % 3600) / 60);

        // Parse started_at for date and time
        if (props.timeEntry.started_at) {
            const startDate = new Date(props.timeEntry.started_at);
            form.value.date = startDate.toISOString().split("T")[0];
            form.value.startTime = startDate.toTimeString().slice(0, 5);
        }
    }

    // Only load billing rates and tickets if not already loaded from context
    if (!props.contextTicket && !props.contextAccount && !(props.mode === "timer-commit" && props.timerData)) {
        await loadBillingRatesForAccount(form.value.accountId);
        if (form.value.accountId) {
            await loadTicketsForAccount(form.value.accountId);
        }
    }
    
    // Ensure current user is always set for create mode after all data is loaded
    if (props.mode === "create") {
        // If user can assign to others and we have an account, load agents first
        if (canAssignToOthers.value && form.value.accountId) {
            await loadAgentsForAccount(form.value.accountId);
        }
        
        // Now set the user ID after agents are loaded
        form.value.userId = user.value?.id;
        console.log("Set default userId for create mode after data loading:", form.value.userId);
    }
};

// Watchers
watch(
    () => props.show,
    async (newValue) => {
        if (newValue) {
            await initializeForm();
        }
    }
);

watch(
    () => [form.value.durationHours, form.value.durationMinutes],
    () => {
        calculateBillingAmount();
    }
);

watch(
    () => form.value.billable,
    () => {
        calculateBillingAmount();
    }
);

// Initialize on mount if dialog is already open
onMounted(async () => {
    if (props.show) {
        await initializeForm();
    }
});
</script>
