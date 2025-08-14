import { QueryClient } from '@tanstack/vue-query'

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      // Cache data for 5 minutes by default
      staleTime: 1000 * 60 * 5,
      // Keep data in cache for 10 minutes
      gcTime: 1000 * 60 * 10,
      // Retry failed requests 3 times
      retry: 3,
      // Retry delay function (exponential backoff)
      retryDelay: (attemptIndex) => Math.min(1000 * 2 ** attemptIndex, 30000),
      // Refetch on window focus for important data
      refetchOnWindowFocus: true,
      // Don't refetch on reconnect for most queries
      refetchOnReconnect: false,
      // Network mode - always try to fetch, even if offline
      networkMode: 'always',
    },
    mutations: {
      // Retry mutations on network errors
      retry: 1,
      // Network mode for mutations
      networkMode: 'online',
    },
  },
})

// Query key factory for consistent query keys across the app
export const queryKeys = {
  // Timer-related queries
  timers: {
    all: ['timers'],
    active: () => ['timers', 'active'],
    activeCurrent: () => ['timers', 'active', 'current'],
    byId: (id) => ['timers', 'byId', id],
  },
  
  // Navigation-related queries
  navigation: {
    all: ['navigation'],
    items: (grouped = false) => ['navigation', 'items', { grouped }],
    breadcrumbs: (route) => ['navigation', 'breadcrumbs', route],
    access: (routes) => ['navigation', 'access', routes],
  },
  
  // Dashboard-related queries
  dashboard: {
    all: ['dashboard'],
    widgets: () => ['dashboard', 'widgets'],
    widgetData: (widgetKey) => ['dashboard', 'widgetData', widgetKey],
    meta: () => ['dashboard', 'meta'],
  },
  
  // User-related queries
  users: {
    all: ['users'],
    current: () => ['users', 'current'],
    byId: (id) => ['users', 'byId', id],
    list: (filters) => ['users', 'list', filters],
  },
  
  // Role and permission queries
  roles: {
    all: ['roles'],
    list: () => ['roles', 'list'],
    byId: (id) => ['roles', 'byId', id],
    permissions: (roleId) => ['roles', 'permissions', roleId],
  },
  
  // Ticket-related queries
  tickets: {
    all: ['tickets'],
    list: (filters) => ['tickets', 'list', filters],
    byId: (id) => ['tickets', 'byId', id],
    stats: () => ['tickets', 'stats'],
  },
  
  // Time entries
  timeEntries: {
    all: ['timeEntries'],
    list: (filters) => ['timeEntries', 'list', filters],
    byId: (id) => ['timeEntries', 'byId', id],
    stats: () => ['timeEntries', 'stats'],
  },
  
  // Accounts
  accounts: {
    all: ['accounts'],
    list: (filters) => ['accounts', 'list', filters],
    byId: (id) => ['accounts', 'byId', id],
    selector: ['accounts', 'selector'],
  },
  
  // Role Templates
  roleTemplates: {
    all: ['roleTemplates'],
    list: (filters) => ['roleTemplates', 'list', filters],
    byId: (id) => ['roleTemplates', 'byId', id],
    permissions: ['roleTemplates', 'permissions'],
    widgets: (id) => ['roleTemplates', 'widgets', id],
  },
  
  // Billing and Financial
  billing: {
    all: ['billing'],
    config: ['billing', 'config'],
    settings: ['billing', 'settings'],
    rates: ['billing', 'rates'],
    rateById: (id) => ['billing', 'rates', id],
  },
  
  // Addon Templates
  addonTemplates: {
    all: ['addonTemplates'],
    list: (filters) => ['addonTemplates', 'list', filters],
    byId: (id) => ['addonTemplates', 'byId', id],
    categories: ['addonTemplates', 'categories'],
  },
}

// Helper functions for query invalidation patterns
export const invalidateQueries = {
  // Invalidate all timer-related queries
  timers: () => queryClient.invalidateQueries({ queryKey: ['timers'] }),
  
  // Invalidate navigation queries (useful after permission changes)
  navigation: () => queryClient.invalidateQueries({ queryKey: ['navigation'] }),
  
  // Invalidate dashboard queries
  dashboard: () => queryClient.invalidateQueries({ queryKey: ['dashboard'] }),
  
  // Invalidate specific widget data
  widget: (widgetKey) => queryClient.invalidateQueries({ 
    queryKey: ['dashboard', 'widgetData', widgetKey] 
  }),
  
  // Invalidate user-related queries
  users: () => queryClient.invalidateQueries({ queryKey: ['users'] }),
  
  // Invalidate role-related queries
  roles: () => queryClient.invalidateQueries({ queryKey: ['roles'] }),
  
  // Invalidate ticket-related queries
  tickets: () => queryClient.invalidateQueries({ queryKey: ['tickets'] }),
  
  // Invalidate time entry queries
  timeEntries: () => queryClient.invalidateQueries({ queryKey: ['timeEntries'] }),
  
  // Invalidate account queries
  accounts: () => queryClient.invalidateQueries({ queryKey: ['accounts'] }),
  
  // Invalidate role template queries
  roleTemplates: () => queryClient.invalidateQueries({ queryKey: ['roleTemplates'] }),
  
  // Invalidate billing queries
  billing: () => queryClient.invalidateQueries({ queryKey: ['billing'] }),
  
  // Invalidate addon template queries
  addonTemplates: () => queryClient.invalidateQueries({ queryKey: ['addonTemplates'] }),
}

export default queryClient