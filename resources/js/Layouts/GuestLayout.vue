<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0" 
         style="background-color: var(--color-auth-background)">
        <div class="flex flex-col items-center mb-6">
            <Link href="/" class="flex items-center">
                <div class="w-16 h-16 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-xl flex items-center justify-center text-white font-bold text-2xl shadow-lg">
                    SV
                </div>
                <div class="ml-4">
                    <h1 class="text-3xl font-bold text-gray-900">Service Vault</h1>
                    <p class="text-sm text-gray-600">Time Management & Invoicing</p>
                </div>
            </Link>
        </div>
        
        <div class="w-full sm:max-w-md mt-6 px-6 py-8 overflow-hidden shadow-lg sm:rounded-xl"
             style="background-color: var(--color-auth-card); border: 1px solid var(--color-auth-border)">
            <slot />
        </div>
    </div>
</template>
