import { ref, computed, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'

export function useNavigation() {
    const page = usePage()
    const navigation = ref([])
    const groupedNavigation = ref({})
    const groupLabels = ref({})
    const loading = ref(false)
    const error = ref(null)

    // Get current route name
    const currentRoute = computed(() => {
        return page.props.route || route().current() || 'dashboard'
    })

    // Check if a navigation item is active
    const isActive = (item) => {
        if (!item.active_patterns) return false
        
        return item.active_patterns.some(pattern => {
            // Simple pattern matching - can be enhanced for more complex patterns
            if (pattern.endsWith('*')) {
                const prefix = pattern.slice(0, -1)
                return currentRoute.value.startsWith(prefix)
            }
            return currentRoute.value === pattern
        })
    }

    // Load navigation items
    const loadNavigation = async (grouped = false) => {
        loading.value = true
        error.value = null
        
        try {
            const response = await axios.get('/api/navigation', {
                params: { grouped }
            })

            if (grouped) {
                groupedNavigation.value = response.data.navigation
                groupLabels.value = response.data.group_labels
            } else {
                navigation.value = response.data.navigation
            }
        } catch (err) {
            error.value = 'Failed to load navigation'
            console.error('Navigation loading error:', err)
        } finally {
            loading.value = false
        }
    }

    // Check if user can access specific routes
    const canAccessRoutes = async (routes) => {
        try {
            const response = await axios.post('/api/navigation/can-access', {
                routes
            })
            return response.data.access
        } catch (err) {
            console.error('Access check error:', err)
            return {}
        }
    }

    // Get breadcrumbs for current route
    const getBreadcrumbs = async (routeName = null) => {
        try {
            const response = await axios.get('/api/navigation/breadcrumbs', {
                params: { 
                    route: routeName || currentRoute.value 
                }
            })
            return response.data.breadcrumbs
        } catch (err) {
            console.error('Breadcrumbs loading error:', err)
            return []
        }
    }

    // Get navigation item by key
    const getNavigationItem = (key) => {
        return navigation.value.find(item => item.key === key)
    }

    // Check if user has permission for any navigation item
    const hasAnyNavigation = computed(() => {
        return navigation.value.length > 1 // More than just dashboard
    })

    // Filter navigation by group
    const getNavigationByGroup = (groupKey) => {
        return navigation.value.filter(item => item.group === groupKey)
    }

    // Get main navigation items (most commonly used)
    const mainNavigation = computed(() => {
        return navigation.value.filter(item => 
            ['dashboard', 'tickets', 'timers', 'time-entries'].includes(item.key)
        ).sort((a, b) => (a.sort_order || 999) - (b.sort_order || 999))
    })

    // Get secondary navigation items  
    const secondaryNavigation = computed(() => {
        return navigation.value.filter(item => 
            !['dashboard', 'tickets', 'timers', 'time-entries'].includes(item.key)
        ).sort((a, b) => (a.sort_order || 999) - (b.sort_order || 999))
    })

    // Auto-load navigation on mount
    onMounted(() => {
        loadNavigation()
    })

    return {
        // Data
        navigation,
        groupedNavigation,
        groupLabels,
        loading,
        error,
        currentRoute,

        // Computed
        hasAnyNavigation,
        mainNavigation,
        secondaryNavigation,

        // Methods
        loadNavigation,
        isActive,
        canAccessRoutes,
        getBreadcrumbs,
        getNavigationItem,
        getNavigationByGroup
    }
}