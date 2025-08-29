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
                            <form @submit.prevent="saveTemplate">
                                <div>
                                    <DialogTitle
                                        as="h3"
                                        class="text-base font-semibold leading-6 text-gray-900"
                                    >
                                        {{
                                            isEditing
                                                ? "Edit Addon Template"
                                                : "Create Addon Template"
                                        }}
                                    </DialogTitle>

                                    <div class="mt-6 space-y-6">
                                        <!-- Name -->
                                        <div>
                                            <label
                                                for="name"
                                                class="block text-sm font-medium leading-6 text-gray-900"
                                            >
                                                Template Name
                                                <span class="text-red-500"
                                                    >*</span
                                                >
                                            </label>
                                            <div class="mt-2">
                                                <input
                                                    id="name"
                                                    v-model="form.name"
                                                    type="text"
                                                    required
                                                    placeholder="e.g., SSL Certificate, Cloud Storage, Setup Service"
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
                                                Description
                                            </label>
                                            <div class="mt-2">
                                                <textarea
                                                    id="description"
                                                    v-model="form.description"
                                                    rows="3"
                                                    placeholder="Optional description of this template..."
                                                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                />
                                            </div>
                                        </div>

                                        <div
                                            class="grid grid-cols-1 gap-6 sm:grid-cols-2"
                                        >
                                            <!-- Category -->
                                            <div>
                                                <label
                                                    for="category"
                                                    class="block text-sm font-medium leading-6 text-gray-900"
                                                >
                                                    Category
                                                    <span class="text-red-500"
                                                        >*</span
                                                    >
                                                </label>
                                                <div class="mt-2">
                                                    <select
                                                        id="category"
                                                        v-model="form.category"
                                                        required
                                                        class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                    >
                                                        <option value="">
                                                            Select category...
                                                        </option>
                                                        <option
                                                            v-for="(
                                                                label, key
                                                            ) in addonCategories"
                                                            :key="key"
                                                            :value="key"
                                                        >
                                                            {{ label }}
                                                        </option>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- SKU -->
                                            <div>
                                                <label
                                                    for="sku"
                                                    class="block text-sm font-medium leading-6 text-gray-900"
                                                >
                                                    SKU
                                                </label>
                                                <div class="mt-2">
                                                    <input
                                                        id="sku"
                                                        v-model="form.sku"
                                                        type="text"
                                                        placeholder="Product SKU or code"
                                                        class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                    />
                                                </div>
                                            </div>

                                            <!-- Default Unit Price -->
                                            <div>
                                                <label
                                                    for="default_unit_price"
                                                    class="block text-sm font-medium leading-6 text-gray-900"
                                                >
                                                    Default Unit Price
                                                    <span class="text-red-500"
                                                        >*</span
                                                    >
                                                </label>
                                                <div class="mt-2">
                                                    <div class="relative">
                                                        <div
                                                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
                                                        >
                                                            <span
                                                                class="text-gray-500 sm:text-sm"
                                                                >$</span
                                                            >
                                                        </div>
                                                        <input
                                                            id="default_unit_price"
                                                            v-model="
                                                                form.default_unit_price
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

                                            <!-- Default Quantity -->
                                            <div>
                                                <label
                                                    for="default_quantity"
                                                    class="block text-sm font-medium leading-6 text-gray-900"
                                                >
                                                    Default Quantity
                                                    <span class="text-red-500"
                                                        >*</span
                                                    >
                                                </label>
                                                <div class="mt-2">
                                                    <input
                                                        id="default_quantity"
                                                        v-model="
                                                            form.default_quantity
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

                                            <!-- Billing Category -->
                                            <div>
                                                <label
                                                    for="billing_category"
                                                    class="block text-sm font-medium leading-6 text-gray-900"
                                                >
                                                    Billing Category
                                                </label>
                                                <div class="mt-2">
                                                    <select
                                                        id="billing_category"
                                                        v-model="
                                                            form.billing_category
                                                        "
                                                        class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                    >
                                                        <option value="addon">
                                                            Addon
                                                        </option>
                                                        <option value="expense">
                                                            Expense
                                                        </option>
                                                        <option value="product">
                                                            Product
                                                        </option>
                                                        <option value="service">
                                                            Service
                                                        </option>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Default Tax Rate -->
                                            <div>
                                                <label
                                                    for="default_tax_rate"
                                                    class="block text-sm font-medium leading-6 text-gray-900"
                                                >
                                                    Default Tax Rate (%)
                                                </label>
                                                <div class="mt-2">
                                                    <input
                                                        id="default_tax_rate"
                                                        v-model="
                                                            form.default_tax_rate
                                                        "
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        max="100"
                                                        placeholder="0"
                                                        class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                    />
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Checkboxes -->
                                        <div class="space-y-4">
                                            <div class="flex items-center">
                                                <input
                                                    id="billable"
                                                    v-model="form.billable"
                                                    type="checkbox"
                                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                                                />
                                                <label
                                                    for="billable"
                                                    class="ml-2 block text-sm text-gray-900"
                                                >
                                                    Billable by default
                                                </label>
                                            </div>
                                            <div class="flex items-center">
                                                <input
                                                    id="is_taxable"
                                                    v-model="form.is_taxable"
                                                    type="checkbox"
                                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                                                />
                                                <label
                                                    for="is_taxable"
                                                    class="ml-2 block text-sm text-gray-900"
                                                >
                                                    Taxable by default
                                                </label>
                                            </div>
                                            <div class="flex items-center">
                                                <input
                                                    id="is_active"
                                                    v-model="form.is_active"
                                                    type="checkbox"
                                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                                                />
                                                <label
                                                    for="is_active"
                                                    class="ml-2 block text-sm text-gray-900"
                                                >
                                                    Template is active and
                                                    available for use
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="mt-6 flex items-center justify-end space-x-3"
                                >
                                    <button
                                        type="button"
                                        class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                                        @click="$emit('close')"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="saving"
                                        class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50"
                                    >
                                        <span v-if="saving">Saving...</span>
                                        <span v-else>{{
                                            isEditing
                                                ? "Update Template"
                                                : "Create Template"
                                        }}</span>
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
import {
    useCreateAddonTemplateMutation,
    useUpdateAddonTemplateMutation,
} from "@/Composables/queries/useAddonTemplatesQuery";
import { useAddonCategoriesQuery } from "@/Composables/queries/useBillingQuery";
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    show: Boolean,
    template: Object,
});

const emit = defineEmits(["close"]);

// Toast notifications
const { apiError } = useToast();

// Mutations
const createTemplateMutation = useCreateAddonTemplateMutation();
const updateTemplateMutation = useUpdateAddonTemplateMutation();

// TanStack Query for addon categories
const addonCategoriesQuery = useAddonCategoriesQuery();
const addonCategories = computed(() => addonCategoriesQuery.data.value || {});

// Form data
const form = reactive({
    name: "",
    description: "",
    category: "service",
    sku: "",
    default_unit_price: 0,
    default_quantity: 1,
    default_tax_rate: 0,
    billing_category: "addon",
    billable: true,
    is_taxable: false,
    is_active: true,
});

// Computed
const isEditing = computed(() => !!props.template?.id);
const saving = computed(
    () =>
        createTemplateMutation.isPending.value ||
        updateTemplateMutation.isPending.value
);

// Watch for template changes to populate form
watch(
    () => props.template,
    (template) => {
        if (template) {
            form.name = template.name || "";
            form.description = template.description || "";
            form.category = template.category || "service";
            form.sku = template.sku || "";
            form.default_unit_price =
                parseFloat(template.default_unit_price) || 0;
            form.default_quantity = parseFloat(template.default_quantity) || 1;
            form.default_tax_rate = parseFloat(template.default_tax_rate) || 0;
            form.billing_category = template.billing_category || "addon";
            form.billable = template.billable ?? true;
            form.is_taxable = template.is_taxable ?? false;
            form.is_active = template.is_active ?? true;
        }
    },
    { immediate: true }
);

// Reset form when modal opens/closes
watch(
    () => props.show,
    (show) => {
        if (show && !props.template) {
            resetForm();
        }
    }
);

const resetForm = () => {
    form.name = "";
    form.description = "";
    form.category = "service";
    form.sku = "";
    form.default_unit_price = 0;
    form.default_quantity = 1;
    form.default_tax_rate = 0;
    form.billing_category = "addon";
    form.billable = true;
    form.is_taxable = false;
    form.is_active = true;
};

// Save template
const saveTemplate = async () => {
    try {
        const templateData = { ...form };

        if (isEditing.value) {
            await updateTemplateMutation.mutateAsync({
                id: props.template.id,
                data: templateData,
            });
        } else {
            await createTemplateMutation.mutateAsync(templateData);
        }

        emit("close");
    } catch (error) {
        console.error("Failed to save addon template:", error);
        apiError(error, "Failed to save template. Please try again.");
    }
};
</script>
