import { ref, computed } from 'vue'
import {
  getCoreRowModel,
  getFilteredRowModel,
  getSortedRowModel,
  getPaginationRowModel,
  createColumnHelper,
} from '@tanstack/table-core'
import { useVueTable } from '@tanstack/vue-table'

export function useUsersTable(users, canViewAllAccounts = false) {
  const columnHelper = createColumnHelper()
  
  // Table state
  const sorting = ref([])
  const globalFilter = ref('')
  const columnFilters = ref([])
  const pagination = ref({
    pageIndex: 0,
    pageSize: 15,
  })

  // Define columns with business-focused dense layout
  const columns = computed(() => {
    const cols = [
      // User Details Column (name, email, avatar, status badges)
      columnHelper.accessor('name', {
        header: 'User Details',
        cell: info => info.getValue(),
        enableSorting: true,
        size: 280,
      }),
      
      // Account & Role Column
      columnHelper.accessor(row => row.account?.name || 'No Account', {
        id: 'account_role',
        header: 'Account & Role Details', 
        enableSorting: true,
        size: 320,
      }),

      // Activity Column (tickets, timers, last activity)
      columnHelper.display({
        id: 'activity',
        header: 'Activity',
        size: 200,
      }),

      // Status & Actions Column
      columnHelper.display({
        id: 'status_actions',
        header: 'Status & Actions',
        size: 180,
      })
    ]

    return cols
  })

  // Custom filter functions
  const customGlobalFilter = (row, columnId, value) => {
    const searchValue = value.toLowerCase()
    const name = row.original.name.toLowerCase()
    const email = row.original.email?.toLowerCase() || ''
    
    // Search single account/role (legacy support)
    const accountName = row.original.account?.name?.toLowerCase() || ''
    const roleName = row.original.role_template?.name?.toLowerCase() || ''
    
    // Search multiple accounts
    const accountNames = row.original.accounts?.map(acc => 
      (acc.display_name || acc.name || '').toLowerCase()
    ).join(' ') || ''
    
    // Search multiple roles
    const roleNames = row.original.role_templates?.map(role => 
      (role.name || '').toLowerCase()
    ).join(' ') || ''
    
    return name.includes(searchValue) || 
           email.includes(searchValue) || 
           accountName.includes(searchValue) || 
           roleName.includes(searchValue) ||
           accountNames.includes(searchValue) ||
           roleNames.includes(searchValue)
  }

  const customColumnFilter = (row, columnId, value) => {
    switch (columnId) {
      case 'status':
        if (value === 'active') return row.original.is_active === true
        if (value === 'inactive') return row.original.is_active === false
        return true
      case 'role':
        // Check both single role and multiple roles
        if (row.original.role_template_id === value) return true
        if (row.original.role_templates?.some(role => role.id === value)) return true
        return false
      case 'account':
        // Check both single account and multiple accounts
        if (row.original.account_id === value) return true
        if (row.original.accounts?.some(account => account.id === value)) return true
        return false
      default:
        return true
    }
  }

  // Create the table instance
  const table = useVueTable({
    get data() {
      return users.value || []
    },
    get columns() {
      return columns.value
    },
    state: {
      get sorting() { return sorting.value },
      get globalFilter() { return globalFilter.value },
      get columnFilters() { return columnFilters.value },
      get pagination() { return pagination.value },
    },
    onSortingChange: updaterOrValue => {
      sorting.value = typeof updaterOrValue === 'function'
        ? updaterOrValue(sorting.value)
        : updaterOrValue
    },
    onGlobalFilterChange: updaterOrValue => {
      globalFilter.value = typeof updaterOrValue === 'function' 
        ? updaterOrValue(globalFilter.value)
        : updaterOrValue
    },
    onColumnFiltersChange: updaterOrValue => {
      columnFilters.value = typeof updaterOrValue === 'function'
        ? updaterOrValue(columnFilters.value)
        : updaterOrValue
    },
    onPaginationChange: updaterOrValue => {
      pagination.value = typeof updaterOrValue === 'function'
        ? updaterOrValue(pagination.value)
        : updaterOrValue
    },
    getCoreRowModel: getCoreRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    globalFilterFn: customGlobalFilter,
    filterFns: {
      customColumnFilter
    }
  })

  // Filter helpers - use columnFilters for custom filtering
  const setStatusFilter = (status) => {
    const existingFilters = columnFilters.value.filter(f => f.id !== 'status')
    if (status === 'all' || !status) {
      columnFilters.value = existingFilters
    } else {
      columnFilters.value = [
        ...existingFilters,
        {
          id: 'status',
          value: status
        }
      ]
    }
  }

  const setRoleFilter = (roleTemplateId) => {
    const existingFilters = columnFilters.value.filter(f => f.id !== 'role')
    if (!roleTemplateId) {
      columnFilters.value = existingFilters
    } else {
      columnFilters.value = [
        ...existingFilters,
        {
          id: 'role',
          value: roleTemplateId
        }
      ]
    }
  }

  const setAccountFilter = (accountId) => {
    const existingFilters = columnFilters.value.filter(f => f.id !== 'account')
    if (!accountId) {
      columnFilters.value = existingFilters
    } else {
      columnFilters.value = [
        ...existingFilters,
        {
          id: 'account',
          value: accountId
        }
      ]
    }
  }

  const clearAllFilters = () => {
    table.resetGlobalFilter()
    table.resetColumnFilters()
  }

  // Computed properties
  const totalUsers = computed(() => table.getFilteredRowModel().rows.length)
  const currentPage = computed(() => table.getState().pagination.pageIndex + 1)
  const totalPages = computed(() => table.getPageCount())
  
  return {
    table,
    globalFilter,
    setStatusFilter,
    setRoleFilter, 
    setAccountFilter,
    clearAllFilters,
    totalUsers,
    currentPage,
    totalPages,
  }
}