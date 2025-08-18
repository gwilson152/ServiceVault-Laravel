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
        header: 'Ticket Details',
        cell: info => info.getValue(),
        enableSorting: true,
        // Main content column - no fixed width, will expand to fill space
      }),
    ]

    // Add account column if service provider
    if (canViewAllAccounts) {
      cols.push(
        columnHelper.accessor(row => row.account?.name || '', {
          id: 'account',
          header: 'Account/Customer',
          enableSorting: true,
          size: 200, // Fixed width for account column
          minSize: 150,
          maxSize: 250,
        })
      )
    }

    // Add remaining columns
    cols.push(
      columnHelper.display({
        id: 'timer',
        header: 'Timer/Actions',
        size: 120, // Fixed width for actions
        minSize: 100,
        maxSize: 150,
      }),
      
      columnHelper.accessor('updated_at', {
        header: 'Updated',
        cell: info => info.getValue(),
        enableSorting: true,
        size: 100, // Fixed width for date
        minSize: 80,
        maxSize: 120,
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
  // Note: Status and priority filtering now handled through global search
  // since they are combined into the ticket_number column display

  const setAssignmentFilter = (assignment, userId) => {
    if (assignment === 'mine') {
      table.setColumnFilters([
        ...columnFilters.value.filter(f => f.id !== 'account'),
        { 
          id: 'account', 
          value: user.value?.name,
        }
      ])
    } else if (assignment === 'unassigned') {
      table.setColumnFilters([
        ...columnFilters.value.filter(f => f.id !== 'account'),
        { 
          id: 'account', 
          value: 'Unassigned',
        }
      ])
    } else {
      table.setColumnFilters(columnFilters.value.filter(f => f.id !== 'account'))
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
    setAssignmentFilter,
    setAccountFilter,
    clearAllFilters,
  }
}