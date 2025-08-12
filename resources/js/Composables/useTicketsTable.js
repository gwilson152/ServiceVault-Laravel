import { ref, computed } from 'vue'
import {
  getCoreRowModel,
  getFilteredRowModel,
  getSortedRowModel,
  getPaginationRowModel,
  createColumnHelper,
} from '@tanstack/table-core'
import { useVueTable } from '@tanstack/vue-table'

export function useTicketsTable(tickets, user, canViewAllAccounts) {
  const columnHelper = createColumnHelper()
  
  // Table state
  const sorting = ref([])
  const globalFilter = ref('')
  const columnFilters = ref([])
  const pagination = ref({
    pageIndex: 0,
    pageSize: 10,
  })

  // Define columns
  const columns = computed(() => {
    const cols = [
      columnHelper.accessor('ticket_number', {
        header: 'Ticket',
        cell: info => info.getValue(),
        enableSorting: true,
      }),
      
      columnHelper.accessor('status', {
        header: 'Status',
        cell: info => info.getValue(),
        enableSorting: true,
        filterFn: 'equals',
      }),
      
      columnHelper.accessor('priority', {
        header: 'Priority',
        cell: info => info.getValue(),
        enableSorting: true,
        filterFn: 'equals',
      }),
      
      columnHelper.accessor(row => row.assigned_to?.name || 'Unassigned', {
        id: 'assigned_to',
        header: 'Assigned To',
        enableSorting: true,
      }),
    ]

    // Add account column if service provider
    if (canViewAllAccounts) {
      cols.push(
        columnHelper.accessor(row => row.account?.name || '', {
          id: 'account',
          header: 'Account',
          enableSorting: true,
        })
      )
    }

    // Add remaining columns
    cols.push(
      columnHelper.accessor('total_time_logged', {
        header: 'Time Tracked',
        cell: info => info.getValue(),
        enableSorting: true,
      }),
      
      columnHelper.display({
        id: 'timer',
        header: 'Timer',
      }),
      
      columnHelper.accessor('updated_at', {
        header: 'Updated',
        cell: info => info.getValue(),
        enableSorting: true,
      }),
      
      columnHelper.display({
        id: 'actions',
        header: () => 'Actions',
      })
    )

    return cols
  })

  // Create table instance
  const table = useVueTable({
    get data() {
      return tickets.value || []
    },
    get columns() {
      return columns.value
    },
    getCoreRowModel: getCoreRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    state: {
      get sorting() {
        return sorting.value
      },
      get globalFilter() {
        return globalFilter.value
      },
      get columnFilters() {
        return columnFilters.value
      },
      get pagination() {
        return pagination.value
      },
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
    globalFilterFn: 'includesString',
  })

  // Helper functions for filtering
  const setStatusFilter = (status) => {
    if (status) {
      table.setColumnFilters([
        ...columnFilters.value.filter(f => f.id !== 'status'),
        { id: 'status', value: status }
      ])
    } else {
      table.setColumnFilters(columnFilters.value.filter(f => f.id !== 'status'))
    }
  }

  const setPriorityFilter = (priority) => {
    if (priority) {
      table.setColumnFilters([
        ...columnFilters.value.filter(f => f.id !== 'priority'),
        { id: 'priority', value: priority }
      ])
    } else {
      table.setColumnFilters(columnFilters.value.filter(f => f.id !== 'priority'))
    }
  }

  const setAssignmentFilter = (assignment, userId) => {
    if (assignment === 'mine') {
      table.setColumnFilters([
        ...columnFilters.value.filter(f => f.id !== 'assigned_to'),
        { 
          id: 'assigned_to', 
          value: user.value?.name,
        }
      ])
    } else if (assignment === 'unassigned') {
      table.setColumnFilters([
        ...columnFilters.value.filter(f => f.id !== 'assigned_to'),
        { 
          id: 'assigned_to', 
          value: 'Unassigned',
        }
      ])
    } else {
      table.setColumnFilters(columnFilters.value.filter(f => f.id !== 'assigned_to'))
    }
  }

  const setAccountFilter = (accountId) => {
    if (accountId) {
      // Find account name from the ticket data
      const accountName = tickets.value.find(t => t.account_id === parseInt(accountId))?.account?.name
      if (accountName) {
        table.setColumnFilters([
          ...columnFilters.value.filter(f => f.id !== 'account'),
          { id: 'account', value: accountName }
        ])
      }
    } else {
      table.setColumnFilters(columnFilters.value.filter(f => f.id !== 'account'))
    }
  }

  const clearAllFilters = () => {
    table.resetGlobalFilter()
    table.resetColumnFilters()
  }

  return {
    table,
    sorting,
    globalFilter,
    columnFilters,
    pagination,
    setStatusFilter,
    setPriorityFilter,
    setAssignmentFilter,
    setAccountFilter,
    clearAllFilters,
  }
}