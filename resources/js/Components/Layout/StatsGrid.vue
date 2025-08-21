<script setup>
import { computed } from 'vue'

const props = defineProps({
    stats: {
        type: Array,
        required: true,
        // Expected format: [{ 
        //   label: 'Total Time Entries', 
        //   value: '1,234', 
        //   icon: 'path...', 
        //   color: 'blue',
        //   change: '+12%',
        //   changeType: 'positive' | 'negative' | 'neutral'
        // }]
    },
    columns: {
        type: Number,
        default: 4,
        validator: (value) => [1, 2, 3, 4, 6].includes(value)
    },
    variant: {
        type: String,
        default: 'card', // 'card' | 'minimal' | 'bordered'
    },
    showIcons: {
        type: Boolean,
        default: true,
    },
    showChanges: {
        type: Boolean,
        default: false,
    },
})

// Color mapping for stat cards
const colorClasses = {
    blue: {
        icon: 'text-blue-600 bg-blue-100',
        text: 'text-blue-600'
    },
    green: {
        icon: 'text-green-600 bg-green-100',
        text: 'text-green-600'
    },
    yellow: {
        icon: 'text-yellow-600 bg-yellow-100',
        text: 'text-yellow-600'
    },
    red: {
        icon: 'text-red-600 bg-red-100',
        text: 'text-red-600'
    },
    purple: {
        icon: 'text-purple-600 bg-purple-100',
        text: 'text-purple-600'
    },
    gray: {
        icon: 'text-gray-600 bg-gray-100',
        text: 'text-gray-600'
    },
    indigo: {
        icon: 'text-indigo-600 bg-indigo-100',
        text: 'text-indigo-600'
    }
}

// Grid column classes
const gridClasses = computed(() => {
    const columnMap = {
        1: 'grid-cols-1',
        2: 'grid-cols-1 sm:grid-cols-2',
        3: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
        4: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
        6: 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-6'
    }
    return `grid ${columnMap[props.columns]} gap-4`
})

// Card variant classes
const cardClasses = computed(() => {
    if (props.variant === 'minimal') {
        return 'p-4'
    } else if (props.variant === 'bordered') {
        return 'p-4 border border-gray-200 rounded-lg'
    } else {
        return 'p-4 bg-white border border-gray-200 rounded-lg shadow-sm'
    }
})

// Change indicator classes
const getChangeClasses = (changeType) => {
    if (changeType === 'positive') {
        return 'text-green-600'
    } else if (changeType === 'negative') {
        return 'text-red-600'
    } else {
        return 'text-gray-500'
    }
}

// Get color classes for a stat
const getColorClasses = (color) => {
    return colorClasses[color] || colorClasses.gray
}
</script>

<template>
    <div :class="gridClasses">
        <div
            v-for="(stat, index) in stats"
            :key="index"
            :class="cardClasses"
        >
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center">
                        <!-- Stat Icon -->
                        <div 
                            v-if="showIcons && stat.icon"
                            :class="[
                                'flex items-center justify-center w-8 h-8 rounded-lg mr-3',
                                getColorClasses(stat.color).icon
                            ]"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="stat.icon"/>
                            </svg>
                        </div>
                        
                        <div class="flex-1">
                            <!-- Stat Label -->
                            <p class="text-sm font-medium text-gray-600 truncate">
                                {{ stat.label }}
                            </p>
                            
                            <!-- Stat Value -->
                            <p class="mt-1 text-2xl font-semibold text-gray-900">
                                {{ stat.value }}
                            </p>
                        </div>
                    </div>
                    
                    <!-- Change Indicator -->
                    <div v-if="showChanges && stat.change" class="mt-2 flex items-center">
                        <span 
                            :class="[
                                'text-sm font-medium',
                                getChangeClasses(stat.changeType)
                            ]"
                        >
                            {{ stat.change }}
                        </span>
                        <span class="ml-1 text-sm text-gray-500">
                            vs last period
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>