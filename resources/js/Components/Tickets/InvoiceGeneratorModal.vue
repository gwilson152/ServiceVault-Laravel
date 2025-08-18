<template>
    <div
        class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center p-4 z-50"
    >
        <div
            class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto"
        >
            <!-- Modal header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">
                    Generate Invoice
                </h3>
                <p class="text-sm text-gray-600 mt-1">
                    Create invoice for {{ ticket.title }} ({{
                        ticket.ticket_number
                    }})
                </p>
            </div>

            <!-- Modal body -->
            <form @submit.prevent="submitForm" class="px-6 py-4 space-y-6">
                <!-- Invoice Summary -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-900 mb-3">
                        Invoice Summary
                    </h4>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div class="text-center">
                            <div class="text-lg font-bold text-blue-600">
                                {{ unbilledEntries.length }}
                            </div>
                            <div class="text-gray-600">Time Entries</div>
                        </div>
                        <div class="text-center">
                            <div class="text-lg font-bold text-purple-600">
                                {{ unbilledAddons.length }}
                            </div>
                            <div class="text-gray-600">Add-ons</div>
                        </div>
                        <div class="text-center">
                            <div class="text-lg font-bold text-green-600">
                                ${{ totalAmount }}
                            </div>
                            <div class="text-gray-600">Total Amount</div>
                        </div>
                        <div class="text-center">
                            <div class="text-lg font-bold text-orange-600">
                                {{ totalHours }}h
                            </div>
                            <div class="text-gray-600">Total Hours</div>
                        </div>
                    </div>
                </div>

                <!-- Invoice Details -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Invoice Date -->
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-1"
                        >
                            Invoice Date <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.invoice_date"
                            type="date"
                            required
                            :max="today"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        />
                        <p
                            v-if="errors.invoice_date"
                            class="text-red-500 text-xs mt-1"
                        >
                            {{ errors.invoice_date }}
                        </p>
                    </div>

                    <!-- Due Date -->
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-1"
                        >
                            Due Date <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.due_date"
                            type="date"
                            required
                            :min="form.invoice_date"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        />
                        <p
                            v-if="errors.due_date"
                            class="text-red-500 text-xs mt-1"
                        >
                            {{ errors.due_date }}
                        </p>
                    </div>
                </div>

                <!-- Payment Terms -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Payment Terms
                    </label>
                    <select
                        v-model="form.payment_terms"
                        @change="updateDueDate"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="net_15">Net 15 Days</option>
                        <option value="net_30">Net 30 Days</option>
                        <option value="net_45">Net 45 Days</option>
                        <option value="net_60">Net 60 Days</option>
                        <option value="due_on_receipt">Due on Receipt</option>
                        <option value="custom">Custom</option>
                    </select>
                </div>

                <!-- Invoice Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Invoice Description
                    </label>
                    <textarea
                        v-model="form.description"
                        rows="2"
                        placeholder="Professional services rendered for ticket resolution..."
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    ></textarea>
                </div>

                <!-- Time Entries Section -->
                <div v-if="unbilledEntries.length > 0">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-sm font-medium text-gray-900">
                            Time Entries
                        </h4>
                        <button
                            type="button"
                            @click="selectAllTimeEntries"
                            class="text-xs text-blue-600 hover:text-blue-700"
                        >
                            {{
                                allTimeEntriesSelected
                                    ? "Deselect All"
                                    : "Select All"
                            }}
                        </button>
                    </div>

                    <div
                        class="space-y-2 max-h-40 overflow-y-auto border border-gray-200 rounded-md"
                    >
                        <div
                            v-for="entry in unbilledEntries"
                            :key="entry.id"
                            class="flex items-center justify-between p-3 hover:bg-gray-50"
                        >
                            <div class="flex items-center space-x-3">
                                <input
                                    v-model="form.selected_time_entries"
                                    :value="entry.id"
                                    type="checkbox"
                                    class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                />
                                <div class="flex-1">
                                    <div
                                        class="text-sm font-medium text-gray-900"
                                    >
                                        {{ entry.user?.name }} -
                                        {{ formatDate(entry.started_at) }}
                                    </div>
                                    <div class="text-xs text-gray-600">
                                        {{ formatDuration(entry.duration) }} •
                                        {{
                                            entry.description ||
                                            "No description"
                                        }}
                                    </div>
                                </div>
                            </div>
                            <div class="text-sm font-medium text-green-600">
                                ${{ entry.billable_amount || "0.00" }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add-ons Section -->
                <div v-if="unbilledAddons.length > 0">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-sm font-medium text-gray-900">
                            Add-ons
                        </h4>
                        <button
                            type="button"
                            @click="selectAllAddons"
                            class="text-xs text-blue-600 hover:text-blue-700"
                        >
                            {{
                                allAddonsSelected
                                    ? "Deselect All"
                                    : "Select All"
                            }}
                        </button>
                    </div>

                    <div
                        class="space-y-2 max-h-40 overflow-y-auto border border-gray-200 rounded-md"
                    >
                        <div
                            v-for="addon in unbilledAddons"
                            :key="addon.id"
                            class="flex items-center justify-between p-3 hover:bg-gray-50"
                        >
                            <div class="flex items-center space-x-3">
                                <input
                                    v-model="form.selected_addons"
                                    :value="addon.id"
                                    type="checkbox"
                                    class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                />
                                <div class="flex-1">
                                    <div
                                        class="text-sm font-medium text-gray-900"
                                    >
                                        {{ addon.title }}
                                    </div>
                                    <div class="text-xs text-gray-600">
                                        {{ formatAddonType(addon.type) }} •
                                        {{
                                            addon.actual_hours ||
                                            addon.estimated_hours
                                        }}h
                                    </div>
                                </div>
                            </div>
                            <div class="text-sm font-medium text-green-600">
                                ${{
                                    addon.actual_cost ||
                                    addon.estimated_cost ||
                                    "0.00"
                                }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tax and Discount -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Tax Rate -->
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-1"
                        >
                            Tax Rate (%)
                        </label>
                        <input
                            v-model.number="form.tax_rate"
                            type="number"
                            min="0"
                            max="100"
                            step="0.01"
                            placeholder="0.00"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>

                    <!-- Discount Amount -->
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-1"
                        >
                            Discount Amount ($)
                        </label>
                        <input
                            v-model.number="form.discount_amount"
                            type="number"
                            min="0"
                            step="0.01"
                            placeholder="0.00"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>
                </div>

                <!-- Invoice Totals -->
                <div class="bg-blue-50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-900 mb-3">
                        Invoice Totals
                    </h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Subtotal:</span>
                            <span class="font-medium">${{ subtotal }}</span>
                        </div>
                        <div
                            v-if="form.discount_amount > 0"
                            class="flex justify-between"
                        >
                            <span class="text-gray-600">Discount:</span>
                            <span class="font-medium text-red-600"
                                >-${{ form.discount_amount.toFixed(2) }}</span
                            >
                        </div>
                        <div
                            v-if="form.tax_rate > 0"
                            class="flex justify-between"
                        >
                            <span class="text-gray-600"
                                >Tax ({{ form.tax_rate }}%):</span
                            >
                            <span class="font-medium">${{ taxAmount }}</span>
                        </div>
                        <div
                            class="flex justify-between text-lg font-bold border-t border-blue-200 pt-2"
                        >
                            <span>Total:</span>
                            <span class="text-green-600"
                                >${{ finalTotal }}</span
                            >
                        </div>
                    </div>
                </div>

                <!-- Action Options -->
                <div class="space-y-3">
                    <div class="flex items-center">
                        <input
                            v-model="form.save_as_draft"
                            type="checkbox"
                            id="save_draft"
                            class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                        />
                        <label
                            for="save_draft"
                            class="ml-2 text-sm text-gray-700"
                        >
                            Save as draft (don't send yet)
                        </label>
                    </div>

                    <div v-if="!form.save_as_draft" class="flex items-center">
                        <input
                            v-model="form.auto_send"
                            type="checkbox"
                            id="auto_send"
                            class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                        />
                        <label
                            for="auto_send"
                            class="ml-2 text-sm text-gray-700"
                        >
                            Automatically send invoice to client
                        </label>
                    </div>
                </div>
            </form>

            <!-- Modal footer -->
            <div
                class="px-6 py-4 border-t border-gray-200 flex items-center justify-end space-x-3"
            >
                <button
                    @click="$emit('cancelled')"
                    type="button"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    Cancel
                </button>
                <button
                    @click="submitForm"
                    :disabled="
                        submitting ||
                        (!form.selected_time_entries.length &&
                            !form.selected_addons.length)
                    "
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {{
                        submitting
                            ? "Generating..."
                            : form.save_as_draft
                            ? "Save Draft"
                            : "Generate Invoice"
                    }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import axios from "axios";

// Props
const props = defineProps({
    ticket: {
        type: Object,
        required: true,
    },
    unbilledEntries: {
        type: Array,
        default: () => [],
    },
    unbilledAddons: {
        type: Array,
        default: () => [],
    },
});

// Emits
const emit = defineEmits(["generated", "cancelled"]);

// Reactive data
const submitting = ref(false);

// Form data
const form = ref({
    invoice_date: new Date().toISOString().split("T")[0],
    due_date: "",
    payment_terms: "net_30",
    description: "",
    selected_time_entries: [],
    selected_addons: [],
    tax_rate: 0,
    discount_amount: 0,
    save_as_draft: false,
    auto_send: true,
});

// Form errors
const errors = ref({});

// Computed properties
const today = computed(() => {
    return new Date().toISOString().split("T")[0];
});

const allTimeEntriesSelected = computed(() => {
    return (
        props.unbilledEntries.length > 0 &&
        form.value.selected_time_entries.length === props.unbilledEntries.length
    );
});

const allAddonsSelected = computed(() => {
    return (
        props.unbilledAddons.length > 0 &&
        form.value.selected_addons.length === props.unbilledAddons.length
    );
});

const totalAmount = computed(() => {
    const timeTotal = props.unbilledEntries.reduce((sum, entry) => {
        return form.value.selected_time_entries.includes(entry.id)
            ? sum + parseFloat(entry.billable_amount || 0)
            : sum;
    }, 0);

    const addonTotal = props.unbilledAddons.reduce((sum, addon) => {
        return form.value.selected_addons.includes(addon.id)
            ? sum + parseFloat(addon.actual_cost || addon.estimated_cost || 0)
            : sum;
    }, 0);

    return (timeTotal + addonTotal).toFixed(2);
});

const totalHours = computed(() => {
    const timeHours = props.unbilledEntries.reduce((sum, entry) => {
        return form.value.selected_time_entries.includes(entry.id)
            ? sum + entry.duration / 3600
            : sum;
    }, 0);

    const addonHours = props.unbilledAddons.reduce((sum, addon) => {
        return form.value.selected_addons.includes(addon.id)
            ? sum + parseFloat(addon.actual_hours || addon.estimated_hours || 0)
            : sum;
    }, 0);

    return (timeHours + addonHours).toFixed(1);
});

const subtotal = computed(() => {
    return parseFloat(totalAmount.value);
});

const taxAmount = computed(() => {
    return (
        ((subtotal.value - (form.value.discount_amount || 0)) *
            (form.value.tax_rate || 0)) /
        100
    ).toFixed(2);
});

const finalTotal = computed(() => {
    return (
        subtotal.value -
        (form.value.discount_amount || 0) +
        parseFloat(taxAmount.value)
    ).toFixed(2);
});

// Methods
const formatDate = (date) => {
    if (!date) return "N/A";
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};

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

const formatAddonType = (type) => {
    const typeMap = {
        additional_work: "Additional Work",
        emergency_support: "Emergency Support",
        consultation: "Consultation",
        training: "Training",
        custom: "Custom",
    };
    return typeMap[type] || type;
};

const selectAllTimeEntries = () => {
    if (allTimeEntriesSelected.value) {
        form.value.selected_time_entries = [];
    } else {
        form.value.selected_time_entries = props.unbilledEntries.map(
            (entry) => entry.id
        );
    }
};

const selectAllAddons = () => {
    if (allAddonsSelected.value) {
        form.value.selected_addons = [];
    } else {
        form.value.selected_addons = props.unbilledAddons.map(
            (addon) => addon.id
        );
    }
};

const updateDueDate = () => {
    const invoiceDate = new Date(form.value.invoice_date);
    let dueDate = new Date(invoiceDate);

    switch (form.value.payment_terms) {
        case "net_15":
            dueDate.setDate(dueDate.getDate() + 15);
            break;
        case "net_30":
            dueDate.setDate(dueDate.getDate() + 30);
            break;
        case "net_45":
            dueDate.setDate(dueDate.getDate() + 45);
            break;
        case "net_60":
            dueDate.setDate(dueDate.getDate() + 60);
            break;
        case "due_on_receipt":
            dueDate = new Date(invoiceDate);
            break;
    }

    if (form.value.payment_terms !== "custom") {
        form.value.due_date = dueDate.toISOString().split("T")[0];
    }
};

const validateForm = () => {
    errors.value = {};

    if (!form.value.invoice_date) {
        errors.value.invoice_date = "Invoice date is required";
    }

    if (!form.value.due_date) {
        errors.value.due_date = "Due date is required";
    }

    if (
        form.value.selected_time_entries.length === 0 &&
        form.value.selected_addons.length === 0
    ) {
        errors.value.general =
            "Please select at least one time entry or add-on to invoice";
    }

    return Object.keys(errors.value).length === 0;
};

const submitForm = async () => {
    if (!validateForm()) return;

    submitting.value = true;

    try {
        const payload = {
            ticket_id: props.ticket.id,
            invoice_date: form.value.invoice_date,
            due_date: form.value.due_date,
            payment_terms: form.value.payment_terms,
            description: form.value.description || null,
            time_entry_ids: form.value.selected_time_entries,
            addon_ids: form.value.selected_addons,
            tax_rate: form.value.tax_rate || 0,
            discount_amount: form.value.discount_amount || 0,
            save_as_draft: form.value.save_as_draft,
            auto_send: !form.value.save_as_draft && form.value.auto_send,
        };

        await axios.post("/api/invoices", payload);
        emit("generated");
    } catch (error) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        } else {
            console.error("Failed to generate invoice:", error);
            errors.value = {
                general: "Failed to generate invoice. Please try again.",
            };
        }
    } finally {
        submitting.value = false;
    }
};

// Lifecycle
onMounted(() => {
    // Auto-select all items by default
    form.value.selected_time_entries = props.unbilledEntries.map(
        (entry) => entry.id
    );
    form.value.selected_addons = props.unbilledAddons.map((addon) => addon.id);

    // Set initial due date
    updateDueDate();

    // Set default description
    form.value.description = `Professional services rendered for ticket: ${props.ticket.title}`;
});
</script>
