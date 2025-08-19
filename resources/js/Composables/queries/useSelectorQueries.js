import { useQuery } from '@tanstack/vue-query'
import { computed, ref } from 'vue'
import axios from 'axios'
import { usePage } from '@inertiajs/vue3'
import { initializeCSRF } from '@/Services/api'
import { queryKeys } from '@/Services/queryClient'

// Initialize CSRF on module load
initializeCSRF()

// Recent items management
const recentItems = ref({
  tickets: JSON.parse(localStorage.getItem('selector_recent_tickets') || '[]'),
  accounts: JSON.parse(localStorage.getItem('selector_recent_accounts') || '[]'),
  users: JSON.parse(localStorage.getItem('selector_recent_users') || '[]'),
  agents: JSON.parse(localStorage.getItem('selector_recent_agents') || '[]'),
  'billing-rates': JSON.parse(localStorage.getItem('selector_recent_billing_rates') || '[]'),
  'role-templates': JSON.parse(localStorage.getItem('selector_recent_role_templates') || '[]'),
})

// Helper to update recent items
const updateRecentItems = (type, item) => {
  if (!item || !item.id) return
  
  const current = recentItems.value[type] || []
  const filtered = current.filter(existing => existing.id !== item.id)
  const updated = [item, ...filtered].slice(0, 10) // Keep last 10
  
  recentItems.value[type] = updated
  localStorage.setItem(`selector_recent_${type.replace('-', '_')}`, JSON.stringify(updated))
}

// Permission helpers
const getUserPermissionLevel = (user, type) => {
  if (!user) return 'none'
  
  switch (type) {
    case 'tickets':
      if (user.permissions?.includes('tickets.view.all') || user.permissions?.includes('admin.manage')) return 'all'
      if (user.permissions?.includes('tickets.view.account')) return 'account'
      if (user.permissions?.includes('tickets.view.assigned')) return 'assigned'
      return 'own'
      
    case 'accounts':
      if (user.permissions?.includes('accounts.manage') || user.permissions?.includes('admin.manage')) return 'all'
      if (user.permissions?.includes('accounts.view')) return 'account'
      // Allow ticket creation - users need to see accounts to create tickets
      if (user.permissions?.includes('tickets.create') || user.permissions?.includes('tickets.manage')) return 'all'
      return 'own'
      
    case 'users':
    case 'agents':
      if (user.permissions?.includes('users.manage') || user.permissions?.includes('admin.manage')) return 'all'
      if (user.permissions?.includes('users.manage.account')) return 'account'
      // Allow ticket assignment - users with ticket management permissions need to see agents
      if (user.permissions?.includes('tickets.assign') || user.permissions?.includes('tickets.manage')) return 'all'
      return 'none'
      
    case 'billing-rates':
      if (user.permissions?.includes('billing.rates.view') || user.permissions?.includes('admin.manage')) return 'all'
      return 'none'
      
    case 'role-templates':
      if (user.permissions?.includes('users.manage') || user.permissions?.includes('admin.manage')) return 'all'
      return 'none'
      
    default:
      return 'none'
  }
}

// Tickets selector query
export function useSelectorTicketsQuery(options = {}) {
  const {
    searchTerm = ref(''),
    filterSet = ref({}),
    sortField = ref(null),
    sortDirection = ref('desc'),
    recentLimit = 10,
    enabled = ref(true)
  } = options
  
  const page = usePage()
  const user = computed(() => page.props.auth?.user)
  
  return useQuery({
    queryKey: computed(() => ['selector', 'tickets', searchTerm.value, filterSet.value, sortField.value, sortDirection.value]),
    queryFn: async () => {
      const permissionLevel = getUserPermissionLevel(user.value, 'tickets')
      if (permissionLevel === 'none') return { data: [], recent: [] }
      
      const params = {
        q: searchTerm.value,
        limit: searchTerm.value ? 50 : recentLimit,
        permission_level: permissionLevel,
        ...(sortField.value && { sort_field: sortField.value }),
        ...(sortDirection.value && { sort_direction: sortDirection.value }),
        ...filterSet.value
      }
      
      const response = await axios.get('/api/search/tickets', { params })
      
      return {
        data: response.data.data || [],
        recent: recentItems.value.tickets || []
      }
    },
    enabled: computed(() => enabled.value),
    staleTime: 1000 * 60 * 2, // 2 minutes
    select: (data) => {
      // Merge search results with recent items, prioritizing search results
      if (searchTerm.value) {
        const searchIds = new Set(data.data.map(item => item.id))
        const uniqueRecent = data.recent.filter(item => !searchIds.has(item.id))
        return [...data.data, ...uniqueRecent.slice(0, 3)] // Add 3 recent items to search results
      }
      // If no search term, return recent items first, then API data if no recent items
      const recentData = data.recent.slice(0, recentLimit)
      if (recentData.length > 0) {
        return recentData
      }
      // Fallback to API data if no recent items
      return data.data.slice(0, recentLimit)
    }
  })
}

// Accounts selector query
export function useSelectorAccountsQuery(options = {}) {
  const {
    searchTerm = ref(''),
    filterSet = ref({}),
    sortField = ref(null),
    sortDirection = ref('desc'),
    recentLimit = 10,
    enabled = ref(true)
  } = options
  
  const page = usePage()
  const user = computed(() => page.props.auth?.user)
  
  return useQuery({
    queryKey: computed(() => ['selector', 'accounts', searchTerm.value, filterSet.value, sortField.value, sortDirection.value]),
    queryFn: async () => {
      const permissionLevel = getUserPermissionLevel(user.value, 'accounts')
      if (permissionLevel === 'none') return { data: [], recent: [] }
      
      const params = {
        q: searchTerm.value,
        limit: searchTerm.value ? 50 : recentLimit,
        permission_level: permissionLevel,
        ...(sortField.value && { sort_field: sortField.value }),
        ...(sortDirection.value && { sort_direction: sortDirection.value }),
        ...filterSet.value
      }
      
      const response = await axios.get('/api/search/accounts', { params })
      
      return {
        data: response.data.data || [],
        recent: recentItems.value.accounts || []
      }
    },
    enabled: computed(() => enabled.value),
    staleTime: 1000 * 60 * 3, // 3 minutes - accounts change less frequently
    select: (data) => {
      if (searchTerm.value) {
        const searchIds = new Set(data.data.map(item => item.id))
        const uniqueRecent = data.recent.filter(item => !searchIds.has(item.id))
        return [...data.data, ...uniqueRecent.slice(0, 3)]
      }
      // If no search term, return recent items first, then API data if no recent items
      const recentData = data.recent.slice(0, recentLimit)
      if (recentData.length > 0) {
        return recentData
      }
      // Fallback to API data if no recent items
      return data.data.slice(0, recentLimit)
    }
  })
}

// Users selector query
export function useSelectorUsersQuery(options = {}) {
  const {
    searchTerm = ref(''),
    filterSet = ref({}),
    agentType = ref(null),
    sortField = ref(null),
    sortDirection = ref('desc'),
    recentLimit = 10,
    enabled = ref(true)
  } = options
  
  const page = usePage()
  const user = computed(() => page.props.auth?.user)
  
  return useQuery({
    queryKey: computed(() => ['selector', 'users', searchTerm.value, filterSet.value, agentType.value, sortField.value, sortDirection.value]),
    queryFn: async () => {
      const permissionLevel = getUserPermissionLevel(user.value, 'users')
      if (permissionLevel === 'none') return { data: [], recent: [] }
      
      const params = {
        q: searchTerm.value,
        limit: searchTerm.value ? 50 : recentLimit,
        permission_level: permissionLevel,
        agent_type: agentType.value,
        ...(sortField.value && { sort_field: sortField.value }),
        ...(sortDirection.value && { sort_direction: sortDirection.value }),
        ...filterSet.value
      }
      
      const endpoint = agentType.value ? '/api/search/agents' : '/api/search/users'
      const response = await axios.get(endpoint, { params })
      
      const storageKey = agentType.value ? 'agents' : 'users'
      return {
        data: response.data.data || [],
        recent: recentItems.value[storageKey] || []
      }
    },
    enabled: computed(() => enabled.value),
    staleTime: 1000 * 60 * 5, // 5 minutes - users change less frequently
    select: (data) => {
      if (searchTerm.value) {
        const searchIds = new Set(data.data.map(item => item.id))
        const uniqueRecent = data.recent.filter(item => !searchIds.has(item.id))
        return [...data.data, ...uniqueRecent.slice(0, 3)]
      }
      // If no search term, return recent items first, then API data if no recent items
      const recentData = data.recent.slice(0, recentLimit)
      if (recentData.length > 0) {
        return recentData
      }
      // Fallback to API data if no recent items
      return data.data.slice(0, recentLimit)
    }
  })
}

// Billing rates selector query
export function useSelectorBillingRatesQuery(options = {}) {
  const {
    searchTerm = ref(''),
    filterSet = ref({}),
    sortField = ref(null),
    sortDirection = ref('desc'),
    recentLimit = 10,
    enabled = ref(true)
  } = options
  
  const page = usePage()
  const user = computed(() => page.props.auth?.user)
  
  return useQuery({
    queryKey: computed(() => ['selector', 'billing-rates', searchTerm.value, filterSet.value, sortField.value, sortDirection.value]),
    queryFn: async () => {
      const permissionLevel = getUserPermissionLevel(user.value, 'billing-rates')
      if (permissionLevel === 'none') return { data: [], recent: [] }
      
      const params = {
        q: searchTerm.value,
        limit: searchTerm.value ? 30 : recentLimit, // Billing rates typically have fewer items
        ...(sortField.value && { sort_field: sortField.value }),
        ...(sortDirection.value && { sort_direction: sortDirection.value }),
        ...filterSet.value
      }
      
      const response = await axios.get('/api/search/billing-rates', { params })
      
      return {
        data: response.data.data || [],
        recent: recentItems.value['billing-rates'] || []
      }
    },
    enabled: computed(() => enabled.value),
    staleTime: 1000 * 60 * 10, // 10 minutes - billing rates change less frequently
    select: (data) => {
      if (searchTerm.value) {
        const searchIds = new Set(data.data.map(item => item.id))
        const uniqueRecent = data.recent.filter(item => !searchIds.has(item.id))
        return [...data.data, ...uniqueRecent.slice(0, 3)]
      }
      // If no search term, return recent items first, then API data if no recent items
      const recentData = data.recent.slice(0, recentLimit)
      if (recentData.length > 0) {
        return recentData
      }
      // Fallback to API data if no recent items
      return data.data.slice(0, recentLimit)
    }
  })
}

// Active selection fetcher - ensures specific item is available even if not in recent/search
export function useActiveSelectorItem(type, itemId, options = {}) {
  const { enabled = ref(true) } = options
  
  // Map selector types to API endpoints
  const getApiEndpoint = (type, id) => {
    const endpoints = {
      'ticket': `/api/tickets/${id}`,
      'account': `/api/accounts/${id}`,
      'user': `/api/users/${id}`,
      'agent': `/api/users/${id}`,
      'billing-rate': `/api/billing-rates/${id}`,
      'role-template': `/api/role-templates/${id}`,
    }
    
    return endpoints[type] || `/api/${type}s/${id}`
  }
  
  return useQuery({
    queryKey: computed(() => ['selector', 'active-item', type, itemId.value]),
    queryFn: async () => {
      if (!itemId.value) return null
      
      const endpoint = getApiEndpoint(type, itemId.value)
      const response = await axios.get(endpoint)
      return response.data.data || response.data
    },
    enabled: computed(() => enabled.value && !!itemId.value),
    staleTime: 1000 * 60 * 5,
  })
}

// Role templates selector query
export function useSelectorRoleTemplatesQuery(options = {}) {
  const {
    searchTerm = ref(''),
    filterSet = ref({}),
    sortField = ref(null),
    sortDirection = ref('desc'),
    recentLimit = 10,
    enabled = ref(true)
  } = options
  
  const page = usePage()
  const user = computed(() => page.props.auth?.user)
  
  return useQuery({
    queryKey: computed(() => ['selector', 'role-templates', searchTerm.value, filterSet.value, sortField.value, sortDirection.value]),
    queryFn: async () => {
      const permissionLevel = getUserPermissionLevel(user.value, 'role-templates')
      if (permissionLevel === 'none') return { data: [], recent: [] }
      
      const params = {
        q: searchTerm.value,
        limit: searchTerm.value ? 30 : recentLimit, // Role templates typically have fewer items
        ...(sortField.value && { sort_field: sortField.value }),
        ...(sortDirection.value && { sort_direction: sortDirection.value }),
        ...filterSet.value
      }
      
      const response = await axios.get('/api/search/role-templates', { params })
      
      return {
        data: response.data.data || [],
        recent: recentItems.value['role-templates'] || []
      }
    },
    enabled: computed(() => enabled.value),
    staleTime: 1000 * 60 * 10, // 10 minutes - role templates change less frequently
    select: (data) => {
      if (searchTerm.value) {
        const searchIds = new Set(data.data.map(item => item.id))
        const uniqueRecent = data.recent.filter(item => !searchIds.has(item.id))
        return [...data.data, ...uniqueRecent.slice(0, 3)]
      }
      // If no search term, return recent items first, then API data if no recent items
      const recentData = data.recent.slice(0, recentLimit)
      if (recentData.length > 0) {
        return recentData
      }
      // Fallback to API data if no recent items
      return data.data.slice(0, recentLimit)
    }
  })
}

// Export recent items utilities
export const selectorUtils = {
  updateRecentItems,
  getRecentItems: (type) => recentItems.value[type] || [],
  clearRecentItems: (type) => {
    recentItems.value[type] = []
    localStorage.removeItem(`selector_recent_${type.replace('-', '_')}`)
  }
}