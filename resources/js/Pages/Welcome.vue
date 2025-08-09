<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { 
    ClockIcon, 
    ChartBarIcon, 
    UserGroupIcon, 
    CurrencyDollarIcon,
    ShieldCheckIcon,
    DevicePhoneMobileIcon,
    ArrowRightIcon,
    CheckIcon
} from '@heroicons/vue/24/outline';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Head title="Service Vault - Professional Time Tracking & Invoicing" />
    
    <!-- Modern gradient background -->
    <div class="bg-gradient-to-br from-indigo-50 via-white to-blue-50 dark:from-gray-900 dark:via-gray-800 dark:to-indigo-900 min-h-screen">
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
                        <p class="text-xs text-gray-500 dark:text-gray-400">Time & Billing Platform</p>
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
                            Get Started
                        </Link>
                    </template>
                </div>
            </nav>
        </header>

        <!-- Hero Section -->
        <main class="relative">
            <div class="max-w-7xl mx-auto px-4 pt-12 pb-16 sm:px-6 lg:px-8 lg:pt-20 lg:pb-28">
                <div class="text-center">
                    <h1 class="text-4xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-5xl md:text-6xl">
                        <span class="block">Professional Time Tracking</span>
                        <span class="block text-indigo-600 dark:text-indigo-400">Made Simple</span>
                    </h1>
                    <p class="mt-6 max-w-md mx-auto text-base text-gray-500 dark:text-gray-300 sm:text-lg md:mt-8 md:text-xl md:max-w-3xl">
                        Service Vault provides comprehensive time management, real-time tracking, hierarchical account management, and enterprise-level invoicing for modern businesses.
                    </p>
                    
                    <!-- CTA Buttons -->
                    <div class="mt-8 max-w-md mx-auto sm:flex sm:justify-center md:mt-12">
                        <div class="rounded-md shadow">
                            <Link
                                v-if="canRegister && !$page.props.auth.user"
                                :href="route('register')"
                                class="w-full flex items-center justify-center px-8 py-3 text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 transition-colors md:py-4 md:text-lg md:px-10"
                            >
                                Start Free Trial
                            </Link>
                            <Link
                                v-else-if="$page.props.auth.user"
                                :href="route('dashboard')"
                                class="w-full flex items-center justify-center px-8 py-3 text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 transition-colors md:py-4 md:text-lg md:px-10"
                            >
                                Go to Dashboard
                            </Link>
                            <div v-else class="w-full flex items-center justify-center px-8 py-3 text-base font-medium rounded-md text-white bg-indigo-600 md:py-4 md:text-lg md:px-10">
                                Start Free Trial
                            </div>
                        </div>
                        <div class="mt-3 rounded-md shadow sm:mt-0 sm:ml-3">
                            <Link
                                v-if="!$page.props.auth.user"
                                :href="route('login')"
                                class="w-full flex items-center justify-center px-8 py-3 text-base font-medium rounded-md text-indigo-600 bg-white hover:bg-gray-50 transition-colors md:py-4 md:text-lg md:px-10 dark:bg-gray-800 dark:text-indigo-400 dark:hover:bg-gray-700"
                            >
                                Sign In
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Feature Grid -->
                <div class="mt-16 lg:mt-24">
                    <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                        <!-- Real-time Timer System -->
                        <div class="relative group">
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 h-full hover:shadow-lg transition-shadow">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="flex items-center justify-center h-12 w-12 rounded-md bg-indigo-500 text-white">
                                            <ClockIcon class="h-6 w-6" />
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Real-time Timer</h3>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <p class="text-gray-500 dark:text-gray-300">
                                        App-wide floating timer with live duration tracking, ticket integration, and real-time billing calculations across all devices.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Advanced Analytics -->
                        <div class="relative group">
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 h-full hover:shadow-lg transition-shadow">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="flex items-center justify-center h-12 w-12 rounded-md bg-green-500 text-white">
                                            <ChartBarIcon class="h-6 w-6" />
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Advanced Analytics</h3>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <p class="text-gray-500 dark:text-gray-300">
                                        Comprehensive reporting with productivity metrics, project analytics, and multi-role dashboard system for insights.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Team Management -->
                        <div class="relative group">
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 h-full hover:shadow-lg transition-shadow">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="flex items-center justify-center h-12 w-12 rounded-md bg-blue-500 text-white">
                                            <UserGroupIcon class="h-6 w-6" />
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Team Management</h3>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <p class="text-gray-500 dark:text-gray-300">
                                        Hierarchical account structures with unlimited depth, ABAC permission system, and domain-based user assignment.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Billing & Invoicing -->
                        <div class="relative group">
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 h-full hover:shadow-lg transition-shadow">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="flex items-center justify-center h-12 w-12 rounded-md bg-yellow-500 text-white">
                                            <CurrencyDollarIcon class="h-6 w-6" />
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Smart Billing</h3>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <p class="text-gray-500 dark:text-gray-300">
                                        Two-tier rate structures with account overrides, comprehensive invoicing, and automated billing workflows.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Enterprise Security -->
                        <div class="relative group">
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 h-full hover:shadow-lg transition-shadow">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="flex items-center justify-center h-12 w-12 rounded-md bg-red-500 text-white">
                                            <ShieldCheckIcon class="h-6 w-6" />
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Enterprise Security</h3>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <p class="text-gray-500 dark:text-gray-300">
                                        Attribute-Based Access Control (ABAC) with role templates, hierarchical permissions, and enterprise authentication.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Cross-device Sync -->
                        <div class="relative group">
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 h-full hover:shadow-lg transition-shadow">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="flex items-center justify-center h-12 w-12 rounded-md bg-purple-500 text-white">
                                            <DevicePhoneMobileIcon class="h-6 w-6" />
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Cross-device Sync</h3>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <p class="text-gray-500 dark:text-gray-300">
                                        Redis-based synchronization with conflict resolution, real-time broadcasting, and seamless multi-device experience.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Key Features List -->
                <div class="mt-16 lg:mt-24">
                    <div class="text-center">
                        <h2 class="text-3xl font-bold text-gray-900 dark:text-white sm:text-4xl">
                            Everything You Need to Manage Time & Projects
                        </h2>
                        <p class="mt-4 max-w-2xl mx-auto text-xl text-gray-500 dark:text-gray-300">
                            Built with modern technologies for enterprise-level scalability
                        </p>
                    </div>

                    <div class="mt-12">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <CheckIcon class="h-6 w-6 text-green-500" />
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">Laravel 12 + Vue.js 3.5 Frontend</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <CheckIcon class="h-6 w-6 text-green-500" />
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">PostgreSQL + Redis Architecture</p>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <CheckIcon class="h-6 w-6 text-green-500" />
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">Multi-tenant Theme System</p>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <CheckIcon class="h-6 w-6 text-green-500" />
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">Real-time WebSocket Broadcasting</p>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <CheckIcon class="h-6 w-6 text-green-500" />
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">Comprehensive API (25+ Endpoints)</p>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <CheckIcon class="h-6 w-6 text-green-500" />
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">Mobile-responsive Design</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Multi-Role Dashboard Preview -->
                <div class="mt-16 lg:mt-24 bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
                    <div class="text-center">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white sm:text-3xl">
                            Multi-Role Dashboard System
                        </h2>
                        <p class="mt-4 max-w-2xl mx-auto text-lg text-gray-500 dark:text-gray-300">
                            Tailored interfaces for every role in your organization
                        </p>
                    </div>

                    <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="text-center">
                            <div class="bg-indigo-100 dark:bg-indigo-900 rounded-lg p-4">
                                <h3 class="font-semibold text-indigo-900 dark:text-indigo-100">Admin Dashboard</h3>
                                <p class="text-sm text-indigo-700 dark:text-indigo-300 mt-2">System management, user administration, ABAC configuration</p>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <div class="bg-green-100 dark:bg-green-900 rounded-lg p-4">
                                <h3 class="font-semibold text-green-900 dark:text-green-100">Manager Dashboard</h3>
                                <p class="text-sm text-green-700 dark:text-green-300 mt-2">Team oversight, approvals, project management</p>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <div class="bg-blue-100 dark:bg-blue-900 rounded-lg p-4">
                                <h3 class="font-semibold text-blue-900 dark:text-blue-100">Employee Dashboard</h3>
                                <p class="text-sm text-blue-700 dark:text-blue-300 mt-2">Time tracking, ticket management, personal analytics</p>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <div class="bg-purple-100 dark:bg-purple-900 rounded-lg p-4">
                                <h3 class="font-semibold text-purple-900 dark:text-purple-100">Customer Portal</h3>
                                <p class="text-sm text-purple-700 dark:text-purple-300 mt-2">Account-scoped access, ticket viewing, invoice management</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
            <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <div class="flex items-center justify-center space-x-3 mb-4">
                        <div class="bg-indigo-600 p-2 rounded-lg">
                            <ClockIcon class="w-5 h-5 text-white" />
                        </div>
                        <span class="text-lg font-bold text-gray-900 dark:text-white">Service Vault</span>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Professional Time Tracking & Invoicing Platform
                    </p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">
                        Built with Laravel v{{ laravelVersion }} (PHP v{{ phpVersion }})
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
