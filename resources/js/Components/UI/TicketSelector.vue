<template>
    <div class="relative">
        <label
            v-if="label"
            :for="inputId"
            class="block text-sm font-medium text-gray-700 mb-2"
        >
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>

        <!-- Search Input (only show when no ticket is selected) -->
        <div v-if="!selectedTicket" class="relative">
            <input
                :id="inputId"
                v-model="searchTerm"
                type="text"
                :placeholder="placeholder"
                :required="required"
                :disabled="isLoading || disabled"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
                @focus="handleFocus"
                @blur="handleBlur"
                @keydown="handleKeydown"
            />

            <!-- Dropdown Icon -->
            <div
                class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none"
            >
                <div
                    v-if="isLoading"
                    class="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"
                ></div>
                <svg
                    v-else
                    class="w-4 h-4 text-gray-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 9l-7 7-7-7"
                    />
                </svg>
            </div>
        </div>

        <!-- Selected Ticket Display -->
        <div
            v-if="selectedTicket"
            class="p-2 bg-blue-50 border border-blue-200 rounded-lg"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-blue-900">
                        #{{ selectedTicket.ticket_number }} -
                        {{ selectedTicket.title }}
                    </p>
                    <div class="flex items-center space-x-2 mt-1">
                        <span
                            v-if="selectedTicket.status"
                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                            :class="getStatusClasses(selectedTicket.status)"
                        >
                            {{ selectedTicket.status }}
                        </span>
                        <span
                            v-if="selectedTicket.priority"
                            class="text-xs text-blue-700"
                        >
                            {{ selectedTicket.priority }} Priority
                        </span>
                    </div>
                </div>
                <button
                    type="button"
                    @click="clearSelection"
                    class="text-blue-600 hover:text-blue-800"
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
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Dropdown -->
        <div
            v-if="showDropdown && !selectedTicket"
            ref="dropdown"
            class="absolute z-50 w-full bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto"
            :class="dropdownPosition"
        >
            <div v-if="isLoading" class="p-4 text-center text-gray-500">
                <div
                    class="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"
                ></div>
                <span class="ml-2">Loading tickets...</span>
            </div>

            <div v-else>
                <!-- Create New Ticket Option (always show if enabled) -->
                <div
                    v-if="showCreateOption"
                    @mousedown.prevent="openCreateModal"
                    class="px-4 py-3 hover:bg-green-50 cursor-pointer border-b border-gray-100 bg-green-25"
                >
                    <div class="flex items-center">
                        <div
                            class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3"
                        >
                            <svg
                                class="w-4 h-4 text-green-600"
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
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-green-700">
                                Create New Ticket
                            </p>
                            <p class="text-xs text-green-600">
                                Add a new ticket to the system
                            </p>
                        </div>
                    </div>
                </div>

                <!-- No tickets message (only show if no create option and no tickets) -->
                <div
                    v-if="filteredTickets.length === 0 && !showCreateOption"
                    class="p-4 text-center text-gray-500"
                >
                    {{ searchTerm ? "No tickets found" : "No tickets available" }}
                </div>

                <!-- No existing tickets message (when create option is available and no tickets) -->
                <div
                    v-if="filteredTickets.length === 0 && showCreateOption"
                    class="px-4 py-2 text-xs text-gray-500 text-center"
                >
                    {{
                        searchTerm
                            ? "No existing tickets match your search"
                            : "No existing tickets"
                    }}
                </div>

                <!-- Existing Tickets -->
                <div v-if="filteredTickets.length > 0">
                    <div
                        v-for="ticket in filteredTickets"
                        :key="ticket.id"
                        @mousedown.prevent="selectTicket(ticket)"
                        class="px-4 py-3 hover:bg-gray-100 cursor-pointer border-b border-gray-100 last:border-b-0"
                        :class="{
                            'bg-blue-50': selectedTicket?.id === ticket.id,
                        }"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">
                                    #{{ ticket.ticket_number }} -
                                    {{ ticket.title }}
                                </p>
                                <div class="flex items-center space-x-2 mt-1">
                                    <span
                                        v-if="ticket.status"
                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                        :class="getStatusClasses(ticket.status)"
                                    >
                                        {{ ticket.status }}
                                    </span>
                                    <span
                                        v-if="ticket.priority"
                                        class="text-xs text-gray-500"
                                    >
                                        {{ ticket.priority }} Priority
                                    </span>
                                    <span
                                        v-if="ticket.account"
                                        class="text-xs text-gray-500"
                                    >
                                        {{ ticket.account.name }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Error Message -->
            <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>

            <!-- Create Ticket Modal (Teleported to body to avoid clipping) -->
            <Teleport to="body">
                <CreateTicketModal
                    v-if="showCreateOption"
                    :show="showCreateTicketModal"
                    :prefilled-account-id="prefilledAccountId"
                    :available-accounts="availableAccounts"
                    :can-assign-tickets="canAssignTickets"
                    :nested="true"
                    @close="closeCreateModal"
                    @created="handleTicketCreated"
                />
            </Teleport>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, Teleport } from "vue";
import CreateTicketModal from "@/Components/Modals/CreateTicketModal.vue";

// Props
const props = defineProps({
    modelValue: {
        type: [String, Object],
        default: null,
    },
    tickets: {
        type: Array,
        default: () => [],
    },
    isLoading: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    label: {
        type: String,
        default: "Ticket",
    },
    placeholder: {
        type: String,
        default: "Search and select a ticket...",
    },
    required: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: null,
    },
    showCreateOption: {
        type: Boolean,
        default: false,
    },
    reopenOnClear: {
        type: Boolean,
        default: true,
    },
    prefilledAccountId: {
        type: [String, Number],
        default: null,
    },
    availableAccounts: {
        type: Array,
        default: () => [],
    },
    canAssignTickets: {
        type: Boolean,
        default: false,
    },
});

// Emits
const emit = defineEmits([
    "update:modelValue",
    "ticket-selected",
    "ticket-created",
]);

// State
const inputId = `ticket-selector-${Math.random().toString(36).substr(2, 9)}`;
const searchTerm = ref("");
const showDropdown = ref(false);
const selectedTicket = ref(null);
const dropdown = ref(null);
const dropupMode = ref(false);
const showCreateTicketModal = ref(false);

// Computed
const filteredTickets = computed(() => {
    if (!searchTerm.value) return props.tickets;

    const term = searchTerm.value.toLowerCase();
    return props.tickets.filter(
        (ticket) =>
            ticket.title.toLowerCase().includes(term) ||
            ticket.ticket_number.toLowerCase().includes(term) ||
            (ticket.status && ticket.status.toLowerCase().includes(term)) ||
            (ticket.priority && ticket.priority.toLowerCase().includes(term)) ||
            (ticket.account && ticket.account.name.toLowerCase().includes(term))
    );
});

const dropdownPosition = computed(() => {
    return dropupMode.value ? "bottom-full mb-1" : "top-full mt-1";
});

// Methods
const selectTicket = (ticket) => {
    selectedTicket.value = ticket;
    searchTerm.value = "";
    showDropdown.value = false;

    emit("update:modelValue", ticket.id);
    emit("ticket-selected", ticket);
};

const clearSelection = () => {
    selectedTicket.value = null;
    searchTerm.value = "";
    emit("update:modelValue", null);
    emit("ticket-selected", null);

    // Optionally reopen dropdown and focus input
    if (props.reopenOnClear) {
        showDropdown.value = true;
        // Focus the input on next tick
        setTimeout(() => {
            const input = document.getElementById(inputId);
            if (input) {
                input.focus();
                checkDropdownPosition();
            }
        }, 10);
    }
};

const openCreateModal = async () => {
    showDropdown.value = false;

    // Ensure CSRF token is ready before opening modal
    try {
        await window.axios.get("/sanctum/csrf-cookie");
    } catch (error) {
        console.error("Failed to initialize CSRF token:", error);
    }

    showCreateTicketModal.value = true;
};

const closeCreateModal = () => {
    showCreateTicketModal.value = false;
};

const handleTicketCreated = (newTicket) => {
    // Close modal
    showCreateTicketModal.value = false;

    // Select the newly created ticket
    selectTicket(newTicket);

    // Emit event to parent to refresh ticket lists if needed
    emit("ticket-created", newTicket);
};

const handleFocus = () => {
    if (props.disabled || props.isLoading) return;
    showDropdown.value = true;
    // Check position on next tick when dropdown is rendered
    setTimeout(checkDropdownPosition, 10);
};

const handleBlur = () => {
    // Delay hiding dropdown to allow for selection
    setTimeout(() => {
        showDropdown.value = false;
    }, 150);
};

const handleKeydown = (event) => {
    if (event.key === "Escape") {
        showDropdown.value = false;
    }
};

const checkDropdownPosition = () => {
    const input = document.getElementById(inputId);
    if (!input) return;

    const inputRect = input.getBoundingClientRect();
    const viewportHeight = window.innerHeight;
    const spaceBelow = viewportHeight - inputRect.bottom;
    const spaceAbove = inputRect.top;

    // If there's not enough space below (for 250px dropdown) and more space above, use dropup
    dropupMode.value = spaceBelow < 250 && spaceAbove > spaceBelow;
};

const getStatusClasses = (statusName) => {
    const status = statusName.toLowerCase();
    if (status.includes("open") || status.includes("new")) {
        return "bg-green-100 text-green-800";
    } else if (status.includes("progress") || status.includes("assigned")) {
        return "bg-blue-100 text-blue-800";
    } else if (status.includes("pending") || status.includes("waiting")) {
        return "bg-yellow-100 text-yellow-800";
    } else if (status.includes("closed") || status.includes("resolved")) {
        return "bg-gray-100 text-gray-800";
    } else if (status.includes("cancelled")) {
        return "bg-red-100 text-red-800";
    }
    return "bg-gray-100 text-gray-800";
};

// Initialize selected ticket from modelValue
const initializeSelectedTicket = () => {
    console.log('TicketSelector initializeSelectedTicket called:', {
        modelValue: props.modelValue,
        ticketsLength: props.tickets.length,
        tickets: props.tickets.map(t => ({ id: t.id, title: t.title, status: t.status }))
    });
    
    if (props.modelValue && props.tickets.length > 0) {
        const ticket = props.tickets.find((t) => t.id == props.modelValue);
        console.log('Found ticket:', ticket);
        if (ticket) {
            selectedTicket.value = ticket;
            console.log('Set selectedTicket to:', ticket);
        } else {
            console.warn('Ticket not found for modelValue:', props.modelValue, 'Available ticket IDs:', props.tickets.map(t => t.id));
        }
    } else if (!props.modelValue) {
        selectedTicket.value = null;
        console.log('Cleared selectedTicket (no modelValue)');
    } else if (props.modelValue && props.tickets.length === 0) {
        console.warn('TicketSelector has modelValue but no tickets loaded yet');
    }
};

// Watchers
watch(() => props.modelValue, (newValue) => {
    console.log('TicketSelector modelValue changed to:', newValue);
    initializeSelectedTicket();
});
watch(() => props.tickets, (newTickets) => {
    console.log('TicketSelector tickets updated:', newTickets.length, 'tickets');
    initializeSelectedTicket();
});
</script>
