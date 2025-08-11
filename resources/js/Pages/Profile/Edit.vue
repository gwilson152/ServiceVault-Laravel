<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Profile" />

    <AppLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Page Header -->
                <div class="mb-6">
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        Profile
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Manage your account profile information and settings
                    </p>
                </div>
                
                <div class="space-y-6">
                    <div
                        class="bg-white p-4 shadow sm:rounded-lg sm:p-8"
                    >
                        <UpdateProfileInformationForm
                            :must-verify-email="mustVerifyEmail"
                            :status="status"
                            class="max-w-xl"
                        />
                    </div>

                    <div
                        class="bg-white p-4 shadow sm:rounded-lg sm:p-8"
                    >
                        <UpdatePasswordForm class="max-w-xl" />
                    </div>

                    <div
                        class="bg-white p-4 shadow sm:rounded-lg sm:p-8"
                    >
                        <DeleteUserForm class="max-w-xl" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
