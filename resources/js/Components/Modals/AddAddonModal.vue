<template>
    <TransitionRoot as="template" :show="show">
        <Dialog as="div" class="relative z-50" @close="$emit('close')">
            <TransitionChild
                as="template"
                enter="ease-out duration-300"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="ease-in duration-200"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div
                    class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                />
            </TransitionChild>

            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div
                    class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0"
                >
                    <TransitionChild
                        as="template"
                        enter="ease-out duration-300"
                        enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        enter-to="opacity-100 translate-y-0 sm:scale-100"
                        leave="ease-in duration-200"
                        leave-from="opacity-100 translate-y-0 sm:scale-100"
                        leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    >
                        <DialogPanel
                            class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:p-6"
                        >
                            <form @submit.prevent="submitAddon">
                                <div class="sm:flex sm:items-start">
                                    <div class="w-full">
                                        <DialogTitle
                                            as="h3"
                                            class="text-base font-semibold leading-6 text-gray-900"
                                        >
                                            Add Addon to Ticket
                                        </DialogTitle>

                                        <div class="mt-4 space-y-6">
                                            <!-- Template Selection -->
                                            <div>
                                                <label
                                                    for="template"
                                                    class="block text-sm font-medium leading-6 text-gray-900"
                                                >
                                                    Use Template (Optional)
                                                </label>
                                                <div class="mt-2">
                                                    <select
                                                        id="template"
                                                        v-model="
                                                            form.selectedTemplate
                                                        "
                                                        @change="applyTemplate"
                                                        class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                    >
                                                        <option value="">
                                                            Custom addon...
                                                        </option>
                                                        <optgroup
                                                            v-for="(
                                                                templates,
                                                                category
                                                            ) in groupedTemplates"
                                                            :key="category"
                                                            :label="
                                                                categoryLabels[
                                                                    category
                                                                ]
                                                            "
                                                        >
                                                            <option
                                                                v-for="template in templates"
                                                                :key="
                                                                    template.id
                                                                "
                                                                :value="
                                                                    template.id
                                                                "
                                                            >
                                                                {{
                                                                    template.name
                                                                }}
                                                                -
                                                                {{
                                                                    formatPrice(
                                                                        template.default_unit_price
                                                                    )
                                                                }}
                                                            </option>
                                                        </optgroup>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="space-y-4">
                                                <!-- Name -->
                                                <div>
                                                    <label
                                                        for="name"
                                                        class="block text-sm font-medium leading-6 text-gray-900"
                                                    >
                                                        Item Name
                                                        <span
                                                            class="text-red-500"
                                                            >*</span
                                                        >
                                                    </label>
                                                    <div class="mt-2">
                                                        <input
                                                            id="name"
                                                            v-model="form.name"
                                                            type="text"
                                                            required
                                                            placeholder="e.g., SSL Certificate, Additional Storage, Setup Service"
                                                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                        />
                                                    </div>
                                                </div>

                                                <!-- Description -->
                                                <div>
                                                    <label
                                                        for="description"
                                                        class="block text-sm font-medium leading-6 text-gray-900"
                                                    >
                                                        Description (Optional)
                                                    </label>
                                                    <div class="mt-2">
                                                        <textarea
                                                            id="description"
                                                            v-model="
                                                                form.description
                                                            "
                                                            rows="2"
                                                            placeholder="Brief description of this addon..."
                                                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                        />
                                                    </div>
                                                </div>

                                                <div
                                                    class="grid grid-cols-1 gap-4 sm:grid-cols-3"
                                                >
                                                    <!-- Category -->
                                                    <div>
                                                        <label
                                                            for="category"
                                                            class="block text-sm font-medium leading-6 text-gray-900"
                                                        >
                                                            Category
                                                        </label>
                                                        <div class="mt-2">
                                                            <select
                                                                id="category"
                                                                v-model="
                                                                    form.category
                                                                "
                                                                class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                            >
                                                                <option
                                                                    v-for="(
                                                                        label,
                                                                        key
                                                                    ) in addonCategories"
                                                                    :key="key"
                                                                    :value="key"
                                                                >
                                                                    {{ label }}
                                                                </option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <!-- Unit Price -->
                                                    <div>
                                                        <label
                                                            for="unit_price"
                                                            class="block text-sm font-medium leading-6 text-gray-900"
                                                        >
                                                            Price
                                                            <span
                                                                class="text-red-500"
                                                                >*</span
                                                            >
                                                        </label>
                                                        <div class="mt-2">
                                                            <div
                                                                class="relative"
                                                            >
                                                                <div
                                                                    class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
                                                                >
                                                                    <span
                                                                        class="text-gray-500 sm:text-sm"
                                                                        >$</span
                                                                    >
                                                                </div>
                                                                <input
                                                                    id="unit_price"
                                                                    v-model="
                                                                        form.unit_price
                                                                    "
                                                                    type="number"
                                                                    step="0.01"
                                                                    min="0"
                                                                    required
                                                                    placeholder="0.00"
                                                                    class="block w-full rounded-md border-0 py-1.5 pl-8 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                                />
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Quantity -->
                                                    <div>
                                                        <label
                                                            for="quantity"
                                                            class="block text-sm font-medium leading-6 text-gray-900"
                                                        >
                                                            Quantity
                                                            <span
                                                                class="text-red-500"
                                                                >*</span
                                                            >
                                                        </label>
                                                        <div class="mt-2">
                                                            <input
                                                                id="quantity"
                                                                v-model="
                                                                    form.quantity
                                                                "
                                                                type="number"
                                                                step="0.01"
                                                                min="0.01"
                                                                required
                                                                placeholder="1"
                                                                class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                            />
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Checkboxes -->
                                                <div
                                                    class="flex items-center space-x-6"
                                                >
                                                    <label
                                                        class="flex items-center"
                                                    >
                                                        <input
                                                            v-model="
                                                                form.billable
                                                            "
                                                            type="checkbox"
                                                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                                                        />
                                                        <span
                                                            class="ml-2 text-sm text-gray-700"
                                                            >Charge customer for
                                                            this item</span
                                                        >
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Calculated Total -->
                                            <div
                                                class="bg-gray-50 px-4 py-3 rounded-lg"
                                            >
                                                <div
                                                    class="flex justify-between text-base font-medium"
                                                >
                                                    <span>Total Cost:</span>
                                                    <span>{{
                                                        formatPrice(
                                                            calculatedTotal
                                                        )
                                                    }}</span>
                                                </div>
                                                <div
                                                    class="text-sm text-gray-500 mt-1"
                                                >
                                                    {{ form.quantity }} ×
                                                    {{
                                                        formatPrice(
                                                            form.unit_price
                                                        )
                                                    }}
                                                    <span
                                                        v-if="!form.billable"
                                                        class="text-orange-600 ml-2"
                                                        >(Internal - not
                                                        billable)</span
                                                    >
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="mt-5 sm:mt-6 sm:flex sm:flex-row-reverse"
                                >
                                    <button
                                        type="submit"
                                        :disabled="
                                            createTicketAddonMutation.isPending
                                        "
                                        class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 sm:ml-3 sm:w-auto disabled:opacity-50"
                                    >
                                        <span
                                            v-if="
                                                createTicketAddonMutation.isPending
                                            "
                                            >Adding...</span
                                        >
                                        <span v-else>Add Addon</span>
                                    </button>
                                    <button
                                        type="button"
                                        class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto"
                                        @click="$emit('close')"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<script setup>
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionChild,
    TransitionRoot,
} from "@headlessui/vue";
import { ref, reactive, computed, watch } from "vue";
import { useAddonCategoriesQuery } from "@/Composables/queries/useBillingQuery";
import { useAddonTemplatesQuery } from "@/Composables/queries/useAddonTemplatesQuery";
import { useMutation, useQueryClient } from "@tanstack/vue-query";
import axios from "axios";

const props = defineProps({
    show: Boolean,
    ticket: Object,
});

const emit = defineEmits(["close", "added"]);

// Form data
const form = reactive({
    selectedTemplate: "",
    name: "",
    description: "",
    category: "service",
    unit_price: 0,
    quantity: 1,
    billable: true,
    billing_category: "addon",
});

const processing = ref(false);
const queryClient = useQueryClient();

// TanStack Query for addon categories
const addonCategoriesQuery = useAddonCategoriesQuery();
const addonCategories = computed(
    () =>
        addonCategoriesQuery.data.value || {
            product: "Product",
            service: "Service",
            license: "License",
            hardware: "Hardware",
            software: "Software",
            expense: "Expense",
            other: "Other",
        }
);

// TanStack Query for addon templates
const addonTemplatesQuery = useAddonTemplatesQuery();
const templates = computed(() => addonTemplatesQuery.data.value || []);

// Group templates by category
const groupedTemplates = computed(() => {
    return templates.value.reduce((groups, template) => {
        const category = template.category;
        if (!groups[category]) {
            groups[category] = [];
        }
        groups[category].push(template);
        return groups;
    }, {});
});

// Create ticket addon mutation
const createTicketAddonMutation = useMutation({
    mutationFn: (data) =>
        axios.post("/api/ticket-addons", data).then((res) => res.data),
    onSuccess: (data) => {
        emit("added", data.data);
        emit("close");
        resetForm();
    },
    onError: (error) => {
        console.error("Failed to add addon:", error);
        // Handle error (could emit error event or show notification)
    },
});

// Calculated values
const calculatedTotal = computed(() => {
    return (
        (parseFloat(form.unit_price) || 0) * (parseFloat(form.quantity) || 0)
    );
});

// Apply template
const applyTemplate = () => {
    if (!form.selectedTemplate) return;

    const template = templates.value.find(
        (t) => t.id === parseInt(form.selectedTemplate)
    );
    if (!template) return;

    form.name = template.name;
    form.description = template.description || "";
    form.category = template.category;
    form.unit_price = parseFloat(
        template.default_unit_price || template.default_price
    );
    form.quantity = parseFloat(template.default_quantity);
    form.billable = template.billable ?? true;
    form.billing_category = template.billing_category || "addon";
};

// Reset form
const resetForm = () => {
    form.selectedTemplate = "";
    form.name = "";
    form.description = "";
    form.category = "service";
    form.unit_price = 0;
    form.quantity = 1;
    form.billable = true;
    form.billing_category = "addon";
};

// Submit addon
const submitAddon = () => {
    const data = {
        ticket_id: props.ticket.id,
        name: form.name,
        description: form.description,
        category: form.category,
        unit_price: parseFloat(form.unit_price),
        quantity: parseFloat(form.quantity),
        billable: form.billable,
        billing_category: form.billing_category,
    };

    createTicketAddonMutation.mutate(data);
};

// Format price helper
const formatPrice = (amount) => {
    return new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: "USD",
    }).format(amount || 0);
};

// Watch for modal open/close
watch(
    () => props.show,
    (show) => {
        if (show) {
            resetForm();
        }
    }
);
</script>
