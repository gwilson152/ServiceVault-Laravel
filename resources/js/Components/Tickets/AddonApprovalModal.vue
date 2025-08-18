<template>
    <StackedDialog
        :show="true"
        :title="action === 'approve' ? 'Approve Add-on' : 'Reject Add-on'"
        max-width="md"
        @close="$emit('cancelled')"
    >
        <div class="space-y-4">
            <p class="text-sm text-gray-600">{{ addon?.name }}</p>

            <!-- Add-on Summary -->
            <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                <div
                    v-if="addon?.category"
                    class="flex justify-between text-sm"
                >
                    <span class="text-gray-600">Category:</span>
                    <span class="font-medium text-gray-900">{{
                        formatCategory(addon?.category)
                    }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Quantity:</span>
                    <span class="font-medium text-gray-900">{{
                        addon?.quantity || 1
                    }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Unit Price:</span>
                    <span class="font-medium text-green-600"
                        >${{ addon?.unit_price || 0 }}</span
                    >
                </div>
                <div
                    v-if="addon?.discount_amount > 0"
                    class="flex justify-between text-sm"
                >
                    <span class="text-gray-600">Discount:</span>
                    <span class="font-medium text-red-600"
                        >-${{ addon?.discount_amount }}</span
                    >
                </div>
                <div
                    class="flex justify-between text-sm font-medium border-t border-gray-200 pt-2"
                >
                    <span class="text-gray-600">Total:</span>
                    <span class="text-gray-900">${{ totalAmount }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Billable:</span>
                    <span
                        :class="
                            addon?.billable ? 'text-green-600' : 'text-red-600'
                        "
                        class="font-medium"
                    >
                        {{ addon?.billable ? "Yes" : "No" }}
                    </span>
                </div>
            </div>

            <!-- Description -->
            <div v-if="addon?.description">
                <label class="block text-sm font-medium text-gray-700 mb-1"
                    >Description</label
                >
                <div class="p-3 bg-gray-50 rounded-md text-sm text-gray-700">
                    {{ addon.description }}
                </div>
            </div>

            <!-- Approval Form -->
            <form @submit.prevent="submitApproval">
                <!-- Approval Notes -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        {{
                            action === "approve" ? "Approval" : "Rejection"
                        }}
                        Notes
                        <span v-if="action === 'reject'" class="text-red-500"
                            >*</span
                        >
                    </label>
                    <textarea
                        v-model="form.notes"
                        rows="3"
                        :required="action === 'reject'"
                        :placeholder="
                            action === 'approve'
                                ? 'Optional notes about the approval...'
                                : 'Please explain why this add-on is being rejected...'
                        "
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    ></textarea>
                    <p v-if="errors.notes" class="text-red-500 text-xs mt-1">
                        {{ errors.notes }}
                    </p>
                </div>
            </form>

            <!-- Confirmation Warning -->
            <div
                :class="
                    action === 'approve'
                        ? 'bg-green-50 border-green-200'
                        : 'bg-red-50 border-red-200'
                "
                class="border rounded-md p-4"
            >
                <div class="flex">
                    <svg
                        v-if="action === 'approve'"
                        class="w-5 h-5 text-green-400 mr-2 mt-0.5"
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
                    <svg
                        v-else
                        class="w-5 h-5 text-red-400 mr-2 mt-0.5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                        />
                    </svg>
                    <div class="text-sm">
                        <p
                            :class="
                                action === 'approve'
                                    ? 'text-green-700'
                                    : 'text-red-700'
                            "
                            class="font-medium"
                        >
                            {{
                                action === "approve"
                                    ? "Approve Add-on"
                                    : "Reject Add-on"
                            }}
                        </p>
                        <p
                            :class="
                                action === 'approve'
                                    ? 'text-green-600'
                                    : 'text-red-600'
                            "
                            class="mt-1"
                        >
                            {{
                                action === "approve"
                                    ? "This will set the add-on status to approved."
                                    : "This will permanently reject this add-on."
                            }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex items-center justify-end space-x-3">
                <button
                    @click="$emit('cancelled')"
                    type="button"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    Cancel
                </button>
                <button
                    @click="submitApproval"
                    :disabled="submitting"
                    :class="
                        action === 'approve'
                            ? 'bg-green-600 hover:bg-green-700 focus:ring-green-500'
                            : 'bg-red-600 hover:bg-red-700 focus:ring-red-500'
                    "
                    class="px-4 py-2 text-sm font-medium text-white border border-transparent rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {{
                        submitting
                            ? "Processing..."
                            : action === "approve"
                            ? "Approve Add-on"
                            : "Reject Add-on"
                    }}
                </button>
            </div>
        </template>
    </StackedDialog>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import axios from "axios";
import StackedDialog from "@/Components/StackedDialog.vue";

// Props
const props = defineProps({
    addon: {
        type: Object,
        required: true,
    },
    action: {
        type: String,
        required: true,
        validator: (value) => ["approve", "reject"].includes(value),
    },
});

// Emits
const emit = defineEmits(["approved", "cancelled"]);

// Reactive data
const submitting = ref(false);

// Form data
const form = ref({
    notes: "",
});

// Form errors
const errors = ref({});

// Computed properties
const totalAmount = computed(() => {
    const subtotal =
        (props.addon?.quantity || 0) * (props.addon?.unit_price || 0);
    return Math.max(0, subtotal - (props.addon?.discount_amount || 0));
});

// Methods
const formatCategory = (category) => {
    return (
        category?.charAt(0).toUpperCase() +
            category?.slice(1).replace("_", " ") || "Other"
    );
};

const validateForm = () => {
    errors.value = {};

    if (props.action === "reject" && !form.value.notes.trim()) {
        errors.value.notes = "Rejection reason is required";
    }

    return Object.keys(errors.value).length === 0;
};

const submitApproval = async () => {
    if (!validateForm()) return;

    submitting.value = true;

    try {
        const endpoint =
            props.action === "approve"
                ? `/api/ticket-addons/${props.addon.id}/approve`
                : `/api/ticket-addons/${props.addon.id}/reject`;

        const payload = {
            approval_notes: form.value.notes.trim() || null,
        };

        await axios.post(endpoint, payload);
        emit("approved");
    } catch (error) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        } else {
            console.error(`Failed to ${props.action} addon:`, error);
            errors.value = {
                general: `Failed to ${props.action} add-on. Please try again.`,
            };
        }
    } finally {
        submitting.value = false;
    }
};
</script>
