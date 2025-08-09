<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { 
    ClockIcon, 
    ArrowRightIcon
} from '@heroicons/vue/24/outline';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});
</script>

<template>
    <Head title="Service Vault" />
    
    <!-- Clean professional background -->
    <div class="bg-white dark:bg-gray-900 min-h-screen">
        <!-- Navigation Header -->
        <header class="relative z-10 px-4 pt-6 sm:px-6 lg:px-8">
            <nav class="flex items-center justify-between max-w-7xl mx-auto">
                <!-- Service Vault Logo -->
                <div class="flex items-center space-x-3">
                    <div class="bg-indigo-600 p-2 rounded-lg">
                        <ClockIcon class="w-6 h-6 text-white" />
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900 dark:text-white">Service Vault</h1>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Professional Services Platform</p>
                    </div>
                </div>

                <!-- Auth Navigation -->
                <div v-if="canLogin" class="flex items-center space-x-4">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 transition-colors"
                    >
                        Go to Dashboard
                        <ArrowRightIcon class="ml-2 w-4 h-4" />
                    </Link>

                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="text-gray-700 hover:text-indigo-600 text-sm font-medium transition-colors dark:text-gray-300 dark:hover:text-indigo-400"
                        >
                            Sign In
                        </Link>

                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 transition-colors"
                        >
                            Register
                        </Link>
                    </template>
                </div>
            </nav>
        </header>

        <!-- Application Overview -->
        <main class="relative">
            <div class="max-w-7xl mx-auto px-4 pt-12 pb-16 sm:px-6 lg:px-8 lg:pt-20 lg:pb-28">
                <div class="text-center">
                    <h1 class="text-4xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-5xl md:text-6xl">
                        <span class="block">Service Vault</span>
                    </h1>
                    <p class="mt-6 max-w-2xl mx-auto text-base text-gray-600 dark:text-gray-300 sm:text-lg md:mt-8 md:text-xl">
                        Service ticket management and time tracking platform
                    </p>
                    
                    <!-- Access Portal -->
                    <div class="mt-12 max-w-md mx-auto">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('dashboard')"
                            class="w-full flex items-center justify-center px-8 py-3 text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 transition-colors md:py-4 md:text-lg md:px-10"
                        >
                            Go to Dashboard
                            <ArrowRightIcon class="ml-2 w-5 h-5" />
                        </Link>
                        <div v-else-if="canLogin" class="space-y-4">
                            <Link
                                :href="route('login')"
                                class="w-full flex items-center justify-center px-8 py-3 text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 transition-colors md:py-4 md:text-lg md:px-10"
                            >
                                Sign In
                            </Link>
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="w-full flex items-center justify-center px-8 py-3 text-base font-medium rounded-md text-indigo-600 bg-white border border-indigo-600 hover:bg-indigo-50 transition-colors md:py-4 md:text-lg md:px-10 dark:bg-gray-800 dark:border-indigo-400 dark:text-indigo-400 dark:hover:bg-gray-700"
                            >
                                Create Account
                            </Link>
                        </div>
                    </div>
                </div>

            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-16">
            <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        © 2025 Service Vault
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
