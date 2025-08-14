import { useQuery, useMutation } from '@tanstack/vue-query'
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { queryKeys } from '@/Services/queryClient.js'

export function useNavigationQuery() {
    const page = usePage()
    
    // Get current route name
    const currentRoute = computed(() => {
        return page.component || 'dashboard'
    })

    // Query for navigation items
    const { 
        data: navigation, 
        isLoading: loading, 
        error,
        refetch: refetchNavigation
    } = useQuery({
        queryKey: queryKeys.navigation.items(),
        queryFn: async () => {
            const response = await axios.get('/api/navigation')
            return response.data.navigation || []
        },
        staleTime: 1000 * 60 * 10, // 10 minutes - navigation doesn't change often
        gcTime: 1000 * 60 * 30,    // 30 minutes cache time
        refetchOnWindowFocus: false,  // Don't refetch navigation on focus
        refetchOnReconnect: false,    // Don't refetch on reconnect
    })

    // Query for grouped navigation items
    const { 
        data: groupedNavigationData,
        isLoading: loadingGrouped,
        refetch: refetchGroupedNavigation
    } = useQuery({
        queryKey: queryKeys.navigation.items(true),
        queryFn: async () => {
            const response = await axios.get('/api/navigation', { 
                params: { grouped: true } 
            })
            return {
                navigation: response.data.navigation || {},
                group_labels: response.data.group_labels || {}
            }
        },
        staleTime: 1000 * 60 * 10,
        gcTime: 1000 * 60 * 30,
        refetchOnWindowFocus: false,
        refetchOnReconnect: false,
        enabled: false // Only load when explicitly requested
    })

    // Mutation for checking route access
    const accessCheckMutation = useMutation({
        mutationFn: async (routes) => {
            const response = await axios.post('/api/navigation/can-access', { routes })
            return response.data.access || {}
        }
    })

    // Query for breadcrumbs (cached per route)
    const useBreadcrumbsQuery = (routeName = null) => {
        const targetRoute = routeName || currentRoute.value
        
        return useQuery({
            queryKey: queryKeys.navigation.breadcrumbs(targetRoute),
            queryFn: async () => {
                const response = await axios.get('/api/navigation/breadcrumbs', {
                    params: { route: targetRoute }
                })
                return response.data.breadcrumbs || []
            },
            staleTime: 1000 * 60 * 5,  // 5 minutes for breadcrumbs
            enabled: !!targetRoute
        })
    }

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

    // Get navigation item by key
    const getNavigationItem = (key) => {
        return navigation.value?.find(item => item.key === key)
    }

    // Check if user has permission for any navigation item
    const hasAnyNavigation = computed(() => {
        return (navigation.value?.length || 0) > 1 // More than just dashboard
    })

    // Filter navigation by group
    const getNavigationByGroup = (groupKey) => {
        return navigation.value?.filter(item => item.group === groupKey) || []
    }

    // Get main navigation items (most commonly used)
    const mainNavigation = computed(() => {
        return navigation.value?.filter(item => 
            ['dashboard', 'tickets', 'time-entries'].includes(item.key)
        ).sort((a, b) => (a.sort_order || 999) - (b.sort_order || 999)) || []
    })

    // Get secondary navigation items  
    const secondaryNavigation = computed(() => {
        return navigation.value?.filter(item => 
            !['dashboard', 'tickets', 'time-entries'].includes(item.key)
        ).sort((a, b) => (a.sort_order || 999) - (b.sort_order || 999)) || []
    })

    // Check route access (with caching via mutation)
    const canAccessRoutes = async (routes) => {
        try {
            const result = await accessCheckMutation.mutateAsync(routes)
            return result
        } catch (err) {
            console.error('Access check error:', err)
            return {}
        }
    }

    // Load grouped navigation on demand
    const loadGroupedNavigation = () => {
        return refetchGroupedNavigation()
    }

    return {
        // Data
        navigation: computed(() => navigation.value || []),
        groupedNavigation: computed(() => groupedNavigationData.value?.navigation || {}),
        groupLabels: computed(() => groupedNavigationData.value?.group_labels || {}),
        loading,
        loadingGrouped,
        error,
        currentRoute,

        // Computed
        hasAnyNavigation,
        mainNavigation,
        secondaryNavigation,

        // Methods
        isActive,
        getNavigationItem,
        getNavigationByGroup,
        canAccessRoutes,
        loadGroupedNavigation,
        useBreadcrumbsQuery,
        refetchNavigation
    }
}